# Informe de Seguridad — Sistema de Gestión de Inventario

**Auditor:** Análisis automatizado con metodología de experto en ciberseguridad  
**Fecha:** 2026-05-22  
**Versión del sistema:** Laravel 12 / PHP 8.2  
**Alcance:** Revisión estática de código fuente (SAST) — capa de aplicación  
**Clasificación:** CONFIDENCIAL — uso interno

---

## Resumen Ejecutivo

Se realizó una revisión de seguridad completa sobre el código fuente del sistema de inventario, cubriendo todos los controladores, modelos, middleware y configuración de rutas. Se identificaron **10 vulnerabilidades**, distribuidas en 3 niveles de criticidad.

| Severidad | Cantidad |
|-----------|----------|
| CRÍTICA   | 1        |
| ALTA      | 2        |
| MEDIA     | 4        |
| BAJA      | 3        |

**Riesgo general del sistema: ALTO**

El hallazgo más grave (SEC-01) permite a un usuario con acceso administrativo leer o mover archivos arbitrarios del servidor mediante manipulación de parámetros, constituyendo un vector de escalada de privilegios y exfiltración de datos.

---

## Metodología

- Revisión manual de todos los controladores (`app/Http/Controllers/`)
- Análisis de modelos, middleware y rutas (`routes/web.php`)
- Trazado de flujo de datos desde la entrada del usuario hasta su uso
- Clasificación de severidad basada en criterios CVSS v3.1 (CIA: Confidencialidad, Integridad, Disponibilidad)
- Identificación de vectores de ataque realistas con contexto del sistema

---

## Hallazgos Detallados

---

### SEC-01 — Path Traversal en carga de Órdenes de Compra

| Campo         | Valor |
|---------------|-------|
| **Severidad** | CRÍTICA |
| **CVSS v3.1** | 8.1 (AV:N/AC:L/PR:H/UI:N/S:C/C:H/I:H/A:N) |
| **Archivo**   | `app/Http/Controllers/OrdenCompraController.php` |
| **Método**    | `store()`, `subirFactura()`, `subirGuia()` |
| **CWE**       | CWE-22: Path Traversal |

#### Descripción

El método `store()` acepta un parámetro `archivo_oc_temp` directamente desde el cuerpo de la solicitud HTTP. Este valor es un **path de sistema de archivos** que el controlador usa sin sanitización para operaciones de `Storage::disk('local')->exists()` y `->move()`.

```php
// OrdenCompraController::store() — código vulnerable
$tempPath = $request->input('archivo_oc_temp');  // ← PATH CONTROLADO POR EL USUARIO

if ($tempPath && Storage::disk('local')->exists($tempPath)) {
    $nuevoNombre = 'ordenes/' . $oc->id . '.' . pathinfo($tempPath, PATHINFO_EXTENSION);
    Storage::disk('local')->move($tempPath, $nuevoNombre);  // ← MOVE ARBITRARIO
    $oc->update(['archivo_oc' => $nuevoNombre]);
}
```

#### Vector de ataque

Un usuario con permiso `ordenes` puede:
1. Iniciar una carga legítima de OC para obtener la estructura de la petición.
2. Reemplazar `archivo_oc_temp` con `../../../.env` o rutas absolutas del servidor.
3. El servidor evaluará `exists('../../../.env')` → `true` y ejecutará `move('../../../.env', 'ordenes/123.env')`, moviendo el archivo `.env` (con credenciales de base de datos, APP_KEY, etc.) a una ruta pública o accesible.

#### Impacto

- Exfiltración de `.env`: exposición de `APP_KEY`, credenciales MySQL, secretos de sesión.
- Movimiento de archivos de sistema críticos, potencialmente causando denegación de servicio.
- En configuraciones con `Storage::disk('public')`, los archivos movidos podrían quedar descargables vía URL.

#### Remediación

```php
// 1. Validar que el path temporal esté dentro del directorio temp esperado
$tempPath = $request->input('archivo_oc_temp');
$allowedPrefix = 'temp/oc_uploads/';

if ($tempPath && str_starts_with($tempPath, $allowedPrefix)) {
    $realPath = Storage::disk('local')->path($tempPath);
    $allowedDir = Storage::disk('local')->path($allowedPrefix);
    
    // Resolver symlinks y verificar que el path real esté dentro del directorio permitido
    if (str_starts_with(realpath($realPath), realpath($allowedDir))) {
        Storage::disk('local')->move($tempPath, $nuevoNombre);
    }
}

// 2. Alternativa más robusta: usar solo el nombre de archivo, nunca paths completos
$tempFilename = basename($request->input('archivo_oc_temp'));
$tempPath = 'temp/oc_uploads/' . $tempFilename;
```

---

### SEC-02 — IDOR en descarga de PDFs del SICD externo

| Campo         | Valor |
|---------------|-------|
| **Severidad** | ALTA |
| **CVSS v3.1** | 7.5 (AV:N/AC:L/PR:L/UI:N/S:U/C:H/I:N/A:N) |
| **Archivo**   | `app/Http/Controllers/SicdController.php` |
| **Métodos**   | `verPdfExterno()`, `verificarPdf()`, `validarCodigo()` |
| **CWE**       | CWE-639: Authorization Bypass Through User-Controlled Key |

#### Descripción

Los métodos de consulta al SICD externo solo verifican `auth()->check()` (autenticación), pero no comprueban si el usuario tiene el permiso `sicd` ni si el documento pertenece a su centro de costo.

```php
// SicdController — solo verifica que el usuario esté logueado
public function verPdfExterno(Request $request)
{
    abort_unless(auth()->check(), 403);  // ← SOLO AUTENTICACIÓN, SIN PERMISO NI CC

    $codigo = $request->input('codigo');
    // Consulta directa al SICD externo usando el código provisto
    $sicd = \DB::connection('sicd_externa')
        ->table('sicd')
        ->where('codigo', $codigo)
        ->first();

    // Retorna el PDF del registro sin verificar ownership
    return response($sicd->pdf_blob, 200, ['Content-Type' => 'application/pdf']);
}
```

#### Vector de ataque

Cualquier usuario con sesión activa (incluso sin permiso `sicd`) puede:
1. Enviar `GET /sicd/pdf-externo?codigo=SICD-2024-00001` iterando códigos secuenciales.
2. Descargar documentos de licitación de **cualquier centro de costo** de la institución.
3. Enumerar toda la base de datos SICD externa.

#### Impacto

- Acceso no autorizado a documentos de licitación y contratos de otros departamentos.
- Violación del principio de mínimo privilegio (separación por centro de costo).
- Posible exposición de información presupuestaria sensible.

#### Remediación

```php
public function verPdfExterno(Request $request)
{
    abort_unless(auth()->user()->tienePermiso('sicd'), 403);

    $codigo = $request->input('codigo');
    
    $query = \DB::connection('sicd_externa')->table('sicd')->where('codigo', $codigo);
    
    // Aplicar filtro de centro de costo para usuarios no-dev
    $ccFiltro = auth()->user()->ccFiltro();
    if ($ccFiltro !== null) {
        $query->where('centro_costo_id', $ccFiltro);
    }
    
    $sicd = $query->first();
    abort_if(!$sicd, 404);
    
    return response($sicd->pdf_blob, 200, ['Content-Type' => 'application/pdf']);
}
```

---

### SEC-03 — IDOR en descarga de boletas de Gastos Menores

| Campo         | Valor |
|---------------|-------|
| **Severidad** | ALTA |
| **CVSS v3.1** | 6.5 (AV:N/AC:L/PR:L/UI:N/S:U/C:H/I:N/A:N) |
| **Archivo**   | `app/Http/Controllers/GastoMenorController.php` |
| **Método**    | `descargarBoleta(int $id)` |
| **CWE**       | CWE-639: Authorization Bypass Through User-Controlled Key |

#### Descripción

La descarga de boletas verifica el permiso `gastos_menores` pero no comprueba que el gasto pertenezca al centro de costo del usuario solicitante.

```php
public function descargarBoleta(int $id)
{
    abort_unless(auth()->user()->tienePermiso('gastos_menores'), 403);
    
    $gasto = GastoMenor::findOrFail($id);  // ← SIN FILTRO DE CENTRO DE COSTO
    
    // Retorna el archivo de boleta sin verificar ownership
    return Storage::download($gasto->boleta_path);
}
```

#### Vector de ataque

Un usuario con permiso `gastos_menores` puede iterar IDs numéricos para descargar boletas de cualquier otro departamento.

#### Remediación

```php
public function descargarBoleta(int $id)
{
    abort_unless(auth()->user()->tienePermiso('gastos_menores'), 403);
    
    $query = GastoMenor::where('id', $id);
    
    $ccFiltro = auth()->user()->ccFiltro();
    if ($ccFiltro !== null) {
        $query->where('centro_costo_id', $ccFiltro);
    }
    
    $gasto = $query->firstOrFail();  // Retorna 404 si no pertenece al CC del usuario
    
    abort_unless(Storage::exists($gasto->boleta_path), 404);
    return Storage::download($gasto->boleta_path);
}
```

---

### SEC-04 — Falta de verificación de permiso en ruta de creación de usuarios

| Campo         | Valor |
|---------------|-------|
| **Severidad** | MEDIA |
| **CVSS v3.1** | 5.4 (AV:N/AC:L/PR:L/UI:N/S:U/C:L/I:L/A:N) |
| **Archivo**   | `app/Http/Controllers/UsuarioController.php` |
| **Método**    | `create()` (GET) |
| **CWE**       | CWE-284: Improper Access Control |

#### Descripción

El método `store()` (POST) verifica correctamente el permiso `usuarios`, pero el método `create()` (GET, que renderiza el formulario de creación) carece de verificación explícita de permisos. Cualquier usuario autenticado con al menos un permiso de admin puede acceder al formulario.

```php
// UsuarioController
public function create()
{
    // ← SIN abort_unless aquí
    $centrosCosto = CentroCosto::orderBy('nombre')->get();
    return view('admin.usuarios.create', compact('centrosCosto'));
}

public function store(Request $request)
{
    abort_unless(auth()->user()->tienePermiso('usuarios'), 403);  // ← Solo aquí
    // ...
}
```

#### Impacto

- Exposición de la estructura del formulario de creación de usuarios.
- Enumeración de todos los centros de costo disponibles mediante la petición GET.
- El riesgo de creación real está bloqueado por `store()`, pero la información expuesta puede ser usada en ataques de reconocimiento.

#### Remediación

```php
public function create()
{
    abort_unless(auth()->user()->tienePermiso('usuarios'), 403);
    $centrosCosto = CentroCosto::orderBy('nombre')->get();
    return view('admin.usuarios.create', compact('centrosCosto'));
}
```

---

### SEC-05 — Filtro de centro de costo omitido en búsqueda global

| Campo         | Valor |
|---------------|-------|
| **Severidad** | MEDIA |
| **CVSS v3.1** | 5.3 (AV:N/AC:L/PR:L/UI:N/S:U/C:L/I:N/A:N) |
| **Archivo**   | `app/Http/Controllers/SearchController.php` |
| **Método**    | `query()` / `__invoke()` |
| **CWE**       | CWE-284: Improper Access Control |

#### Descripción

La búsqueda global aplica filtros de centro de costo para solicitudes y productos, pero las consultas sobre SICDs y Órdenes de Compra no tienen ningún filtrado por CC.

```php
// SearchController — búsqueda de SICDs sin filtro de CC
$sicds = Sicd::where('numero_sicd', 'like', "%{$q}%")
    ->orWhere('descripcion', 'like', "%{$q}%")
    ->limit(5)
    ->get();  // ← DEVUELVE SICDS DE TODOS LOS CENTROS DE COSTO

// Búsqueda por ID numérico: cualquier usuario puede encontrar registros por ID
if (is_numeric($q)) {
    $sicds = Sicd::where('id', $q)->get();  // ← ACCESO DIRECTO POR ID
}
```

#### Impacto

- Usuarios de un departamento pueden descubrir SICDs y órdenes de compra de otros departamentos.
- La búsqueda por ID numérico permite enumeración directa de registros.

#### Remediación

```php
public function __invoke(Request $request)
{
    $q = trim($request->input('q', ''));
    $user = auth()->user();
    $ccFiltro = $user->ccFiltro();

    $sicdQuery = Sicd::where(function($query) use ($q) {
        $query->where('numero_sicd', 'like', "%{$q}%")
              ->orWhere('descripcion', 'like', "%{$q}%");
    });
    
    if ($ccFiltro !== null) {
        $sicdQuery->where('centro_costo_id', $ccFiltro);
    }
    
    $sicds = $sicdQuery->limit(5)->get();
    // Aplicar el mismo patrón a OrdeneCompra
}
```

---

### SEC-06 — Condición de carrera en generación de número correlativo de Gastos Menores

| Campo         | Valor |
|---------------|-------|
| **Severidad** | MEDIA |
| **CVSS v3.1** | 5.0 (AV:N/AC:H/PR:L/UI:N/S:U/C:N/I:H/A:N) |
| **Archivo**   | `app/Http/Controllers/GastoMenorController.php` |
| **Método**    | `store()` |
| **CWE**       | CWE-362: Race Condition |

#### Descripción

El número correlativo `id_gm` se calcula fuera de una transacción con bloqueo, creando una ventana de carrera entre la lectura del `MAX(id_gm)` y la inserción del nuevo registro.

```php
// Código vulnerable — fuera de transacción
$nextNumero = (GastoMenor::max('id_gm') ?? 0) + 1;  // ← LECTURA SIN LOCK
// ... validaciones ...
GastoMenor::create([
    'id_gm' => $nextNumero,  // ← INSERCIÓN: puede colisionar con otra petición concurrente
    // ...
]);
```

#### Impacto

- Dos usuarios enviando simultáneamente pueden obtener el mismo `id_gm`.
- Si `id_gm` tiene restricción UNIQUE, una de las dos solicitudes fallará con error 500.
- Si no tiene restricción, el sistema tendrá registros con números duplicados (integridad de datos comprometida).

#### Remediación

```php
DB::transaction(function () use ($data) {
    // Bloqueo pesimista para generar el correlativo de forma segura
    $maxGm = GastoMenor::lockForUpdate()->max('id_gm') ?? 0;
    $nextNumero = $maxGm + 1;
    
    GastoMenor::create([
        'id_gm' => $nextNumero,
        // ...
    ]);
});
```

Alternativa más robusta: usar una columna `AUTO_INCREMENT` en la base de datos para `id_gm` y eliminar la lógica de generación manual.

---

### SEC-07 — Inyección de wildcards LIKE en búsqueda de retiros

| Campo         | Valor |
|---------------|-------|
| **Severidad** | BAJA |
| **CVSS v3.1** | 3.1 (AV:N/AC:H/PR:L/UI:N/S:U/C:N/I:N/A:L) |
| **Archivo**   | `app/Http/Controllers/RetiroController.php` |
| **Método**    | `buscar()` |
| **CWE**       | CWE-89: SQL Injection (variante wildcard) |

#### Descripción

La búsqueda de productos para retiro interpola directamente la entrada del usuario dentro de un patrón LIKE sin escapar los metacaracteres `%` y `_` de SQL.

```php
// RetiroController::buscar()
$q = $request->input('q');
$productos = Producto::where('nombre', 'like', "%{$q}%")->get();
// Si $q = "%" → devuelve TODOS los productos
// Si $q = "____%" → devuelve todos los productos con nombre de 4+ caracteres
// Si $q = "%_%" → carga completa de tabla (DoS leve en tablas grandes)
```

#### Impacto

- Un usuario puede forzar búsquedas que devuelvan el catálogo completo de productos.
- En tablas grandes, esto puede causar respuestas lentas o timeouts (denegación de servicio leve).
- No es inyección SQL clásica (Eloquent usa prepared statements), pero sí abusa del motor de búsqueda.

#### Remediación

```php
// Escapar metacaracteres LIKE antes de interpoler
$q = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $request->input('q', ''));
$productos = Producto::where('nombre', 'like', "%{$q}%")->get();
```

---

### SEC-08 — Política de contraseñas débil

| Campo         | Valor |
|---------------|-------|
| **Severidad** | BAJA |
| **CVSS v3.1** | 3.7 (AV:N/AC:H/PR:N/UI:N/S:U/C:L/I:N/A:N) |
| **Archivo**   | `app/Http/Controllers/UsuarioController.php` |
| **Método**    | `store()`, `update()` |
| **CWE**       | CWE-521: Weak Password Requirements |

#### Descripción

La validación de contraseñas solo exige un mínimo de 6 caracteres, sin requisitos de complejidad.

```php
// UsuarioController::store()
$request->validate([
    'password' => 'required|string|min:6|confirmed',  // ← Solo 6 caracteres
]);
```

#### Impacto

- Facilita ataques de fuerza bruta y diccionario.
- Usuarios pueden crear contraseñas triviales como `123456` o `admin1`.

#### Remediación

```php
use Illuminate\Validation\Rules\Password;

$request->validate([
    'password' => [
        'required',
        'confirmed',
        Password::min(10)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->uncompromised(),  // Verifica contra la base de datos Have I Been Pwned
    ],
]);
```

---

### SEC-09 — Ausencia de cabeceras de seguridad HTTP

| Campo         | Valor |
|---------------|-------|
| **Severidad** | BAJA |
| **CVSS v3.1** | 3.1 (AV:N/AC:H/PR:N/UI:R/S:U/C:L/I:N/A:N) |
| **Archivo**   | `bootstrap/app.php`, configuración del servidor web |
| **CWE**       | CWE-693: Protection Mechanism Failure |

#### Descripción

La aplicación no configura cabeceras de seguridad HTTP estándar. Esto expone a los usuarios a ataques de clickjacking, XSS reflejado y MIME sniffing.

Cabeceras ausentes:
- `Content-Security-Policy` (CSP)
- `X-Frame-Options`
- `X-Content-Type-Options`
- `Referrer-Policy`
- `Permissions-Policy`

#### Remediación

Agregar middleware global en `bootstrap/app.php`:

```php
// app/Http/Middleware/SecurityHeaders.php
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; script-src 'self' 'nonce-{$request->attributes->get('csp_nonce')}'; style-src 'self' 'unsafe-inline';"
        );
        
        return $response;
    }
}
```

---

### SEC-10 — Almacenamiento de BLOBs en base de datos (información de riesgo operacional)

| Campo         | Valor |
|---------------|-------|
| **Severidad** | INFORMATIVA |
| **Archivo**   | `app/Http/Controllers/SicdController.php`, `app/Models/Sicd.php` |
| **CWE**       | CWE-312: Cleartext Storage of Sensitive Information |

#### Descripción

Los PDFs del SICD externo se almacenan como BLOBs en la base de datos MySQL. Si bien esto no es una vulnerabilidad directa, presenta riesgos operacionales:

- Los backups de base de datos incluyen datos binarios voluminosos → exposición de documentos en backup leaks.
- No hay control de acceso al nivel del archivo de sistema (permisos de OS).
- Los documentos no tienen cifrado at-rest independiente del cifrado de la base de datos.

#### Recomendación

Migrar el almacenamiento de PDFs a `Storage::disk('local')` con paths encriptados o al menos obfuscados, separando el almacenamiento de archivos del almacenamiento de metadata relacional.

---

## Resumen de Remediaciones Prioritarias

| ID     | Hallazgo                                      | Prioridad | Esfuerzo estimado |
|--------|-----------------------------------------------|-----------|-------------------|
| SEC-01 | Path Traversal en carga de OC                 | URGENTE   | 2 horas           |
| SEC-02 | IDOR en PDFs SICD externo                     | ALTA      | 1 hora            |
| SEC-03 | IDOR en descarga de boletas                   | ALTA      | 30 min            |
| SEC-04 | Falta de permiso en `create()` de usuarios    | MEDIA     | 15 min            |
| SEC-05 | Sin filtro CC en búsqueda global              | MEDIA     | 2 horas           |
| SEC-06 | Race condition en `id_gm`                     | MEDIA     | 1 hora            |
| SEC-07 | Wildcard injection en búsqueda LIKE           | BAJA      | 15 min            |
| SEC-08 | Política de contraseñas débil                 | BAJA      | 30 min            |
| SEC-09 | Ausencia de cabeceras HTTP de seguridad       | BAJA      | 1 hora            |
| SEC-10 | BLOBs en base de datos                        | INFO      | Largo plazo       |

**Total estimado de remediación:** 8-10 horas de desarrollo

---

## Fortalezas Identificadas

El sistema presenta varias buenas prácticas de seguridad que merecen destacarse:

- **CSRF**: Laravel provee protección CSRF automática en todos los formularios POST/PUT/DELETE.
- **SQL Injection**: El uso consistente de Eloquent ORM y Query Builder con prepared statements previene inyección SQL clásica.
- **Autenticación**: Laravel's built-in session authentication con hashing bcrypt de contraseñas.
- **Concurrencia en stock**: Tras las correcciones previas, las operaciones críticas de stock usan `lockForUpdate()` dentro de transacciones.
- **Soft Deletes**: Los registros eliminados se conservan, previniendo pérdida accidental de datos.
- **Global Scopes**: Uso apropiado para filtrar usuarios inactivos y aplicar restricciones de CC automáticamente en modelos.
- **Permisos granulares**: Sistema de permisos bien diseñado con constantes documentadas en el modelo `User`.

---

## Notas sobre el Alcance

Este análisis cubre exclusivamente la **revisión estática de código (SAST)**. No se realizaron:

- Pruebas de penetración dinámicas (DAST)
- Análisis de configuración del servidor web/OS
- Revisión de dependencias de terceros (Composer/npm audit)
- Pruebas de autenticación en tiempo de ejecución
- Análisis de la base de datos SICD externa (fuera del alcance)

Se recomienda complementar este análisis con:
```bash
composer audit          # Vulnerabilidades en dependencias PHP
npm audit               # Vulnerabilidades en dependencias JS
php artisan route:list  # Verificar que todas las rutas tienen middleware apropiado
```

---

*Informe generado el 2026-05-22 — Sistema de Gestión de Inventario*
