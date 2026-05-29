# Informe QA Senior

**Sistema:** Inventario  
**Proyecto:** Laravel 12 / PHP 8.4 objetivo / MySQL / Blade + Vite  
**Fecha:** 2026-05-25  
**Analista:** QA Senior  
**Alcance:** Revision tecnica del estado actual del proyecto: rutas, middleware, controladores criticos, modelo de usuarios, configuracion, migraciones, pruebas y build frontend.

---

## Resumen Ejecutivo

El sistema esta funcional y tiene una base solida para operaciones de inventario: usa autenticacion, permisos granulares, transacciones en flujos de stock, locks en varias operaciones sensibles, auditoria de movimientos y separacion parcial de servicios para reporterias.

Sin embargo, hay riesgos importantes que deben resolverse antes de considerar un despliegue productivo expuesto. El foco principal esta en recepcion de ordenes de compra, cobertura de tests, configuracion de entorno y controles QA automatizados.

| Severidad | Cantidad | Estado |
| --- | ---: | --- |
| Alta | 3 | Requiere correccion prioritaria |
| Media | 4 | Requiere plan de remediacion |
| Baja | 3 | Mejora recomendada |

**Veredicto QA:** Apto para desarrollo/control interno. No recomendado para produccion sin corregir primero los hallazgos de severidad alta.

---

## Remediacion Aplicada - 2026-05-25

Correcciones realizadas sobre los hallazgos de este informe:

| Hallazgo | Estado | Correccion |
| --- | --- | --- |
| QA-01 | Corregido | Se elimino la excepcion CSRF para `admin/ordenes/*/recepcion`. |
| QA-02 | Corregido | Se agrego validacion por detalle para `recibido.*`, precios, total, container y motivo. |
| QA-03 | Corregido | La OC se relee con `lockForUpdate()` dentro de la transaccion y los productos se bloquean antes de modificar stock. |
| QA-04 | Parcial | El test placeholder fue corregido. Aun falta ampliar cobertura funcional. |
| QA-05 | Corregido | `composer.json` y `composer.lock` quedaron alineados a plataforma PHP 8.4. |
| QA-06 | Corregido | Se elimino el fallback sensible `123456` de `DB_SICD_PASSWORD`. |
| QA-07 | Corregido parcial | Se removieron logs informativos de debug del flujo principal de recepcion. |
| QA-10 | Corregido | Se actualizo metadata basica de Composer. |

Verificacion posterior:

```powershell
C:\xampp\php\php.exe artisan test
npm.cmd run build
C:\xampp\php\php.exe artisan config:clear
```

Resultado posterior:

- Tests: OK, 2 passed.
- Build frontend: OK.
- Sintaxis PHP en archivos modificados: OK.

Nota: Composer no esta disponible en PATH en este entorno, por lo que no se pudo ejecutar `composer validate`.

### Segunda remediacion aplicada

Correcciones adicionales aplicadas sobre riesgos residuales de concurrencia:

| Area | Estado | Correccion |
| --- | --- | --- |
| Gastos menores | Corregido | `GastoMenorController::update()` bloquea el gasto y el producto con `lockForUpdate()` antes de recalcular stock. |
| Gastos menores | Corregido | `GastoMenorController::store()` bloquea el producto destino y mantiene el correlativo GM dentro de transaccion. |
| Armado de equipos | Corregido | `ComputadorController::agregarComponente()` revalida stock con producto bloqueado antes de descontar. |
| Armado de equipos | Corregido | `retirarComponente()` bloquea componente activo y producto antes de devolver stock. |
| Armado de equipos | Corregido | `desarmar()` bloquea computador, componentes activos y productos antes de devolver stock. |
| SICD directo | Corregido | `SicdController::recibirDirecto()` bloquea SICD, detalles y productos antes de recepcionar stock. |
| Logs | Corregido | El log `OCR Debug` paso de `info` a `debug`. |

Verificacion posterior:

```powershell
C:\xampp\php\php.exe -l app\Http\Controllers\GastoMenorController.php
C:\xampp\php\php.exe -l app\Http\Controllers\ComputadorController.php
C:\xampp\php\php.exe -l app\Http\Controllers\SicdController.php
C:\xampp\php\php.exe -l app\Http\Controllers\OrdenCompraController.php
C:\xampp\php\php.exe artisan test
npm.cmd run build
```

Resultado posterior:

- Sintaxis PHP en archivos modificados: OK.
- Tests: OK, 2 passed.
- Build frontend: OK.
- `git diff --check`: OK.

---

## Evidencia de Revision

Comandos ejecutados:

```powershell
C:\xampp\php\php.exe artisan test
C:\xampp\php\php.exe artisan migrate:status
C:\xampp\php\php.exe artisan config:clear
C:\xampp\php\php.exe artisan view:clear
npm.cmd run build
```

Revision adicional:

- Lectura de `.ai-context/system_context.md`.
- Revision de `routes/web.php`.
- Revision de `bootstrap/app.php`.
- Revision de `app/Http/Controllers/UsuarioController.php`.
- Revision de `app/Http/Controllers/OrdenCompraController.php`.
- Revision de `app/Http/Controllers/AdminController.php`.
- Revision de `app/Models/User.php`.
- Revision de configuracion sensible en `config/database.php` y `composer.json`.
- Lint de sintaxis PHP en `app`, `config`, `database` y `routes`.

Resultados:

| Verificacion | Resultado |
| --- | --- |
| Sintaxis PHP | OK |
| Migraciones | OK, todas aplicadas |
| Build frontend | OK |
| Config cache clear | OK |
| View cache clear | OK |
| Tests | Fallan por test placeholder desactualizado |

---

## Hallazgos Prioritarios

### QA-01 [ALTA] CSRF desactivado en recepcion de ordenes de compra

**Archivo:** `bootstrap/app.php`

```php
$middleware->validateCsrfTokens(except: [
    'admin/ordenes/*/recepcion',
]);
```

**Problema:** La ruta `POST admin/ordenes/{id}/recepcion` modifica stock, estados de OC/SICD, historial y precios. Actualmente esta excluida de CSRF.

El formulario de recepcion ya incluye `@csrf`, por lo tanto la excepcion no parece necesaria.

**Impacto:** Un usuario autenticado podria ser inducido a ejecutar una recepcion mediante una peticion externa si mantiene sesion activa.

**Recomendacion:** Eliminar la excepcion CSRF y mantener proteccion estandar de Laravel.

**Prioridad:** Inmediata.

---

### QA-02 [ALTA] Recepcion de OC acepta cantidades sin validacion estricta

**Archivo:** `app/Http/Controllers/OrdenCompraController.php`

Zona revisada:

```php
$recibido = (int) $request->input("recibido.{$ocDetalle->id}", 0);
$ocDetalle->cantidad_recibida = $recibido;
$detalle->cantidad_recibida = $recibidoOtrosOcs + $recibido;
$detalle->producto->stock_actual += $recibido;
```

**Problema:** La cantidad recibida se toma directo del request y se castea a entero. No se observa validacion por item del tipo:

- entero
- minimo `0`
- maximo `cantidad_asignada`
- no negativo

**Impacto:** Un request manipulado podria:

- ingresar mas unidades que las asignadas;
- inflar stock;
- registrar valores inconsistentes de `cantidad_recibida`;
- generar historial financiero incorrecto.

**Recomendacion:** Validar dinamicamente cada `recibido.{ocDetalleId}` antes de procesar la transaccion. Adicionalmente, revalidar dentro de la transaccion.

Ejemplo conceptual:

```php
'recibido.*' => ['required', 'integer', 'min:0']
```

Luego validar por cada detalle:

```php
if ($recibido > $ocDetalle->cantidad_asignada) {
    throw ValidationException::withMessages([
        "recibido.{$ocDetalle->id}" => 'No puede superar la cantidad asignada.',
    ]);
}
```

**Prioridad:** Inmediata.

---

### QA-03 [ALTA] Riesgo de doble procesamiento concurrente en recepcion de OC

**Archivo:** `app/Http/Controllers/OrdenCompraController.php`

**Problema:** La OC se carga antes de la transaccion y el flujo no bloquea explicitamente la orden con `lockForUpdate()` al comenzar el procesamiento.

Si dos envios llegan casi al mismo tiempo, ambos podrian leer la OC como no recibida antes de que uno actualice el estado.

**Impacto:** Duplicacion de stock, duplicacion de historial y estados inconsistentes.

**Recomendacion:** Dentro de `DB::transaction`, cargar la OC con lock:

```php
$oc = OrdenCompra::whereKey($id)->lockForUpdate()->firstOrFail();
```

Tambien bloquear los productos afectados antes de modificar `stock_actual`.

**Prioridad:** Inmediata.

---

### QA-04 [MEDIA] Tests automatizados insuficientes y test actual fallando

**Archivo:** `tests/Feature/ExampleTest.php`

Resultado:

```text
Expected response status code [200] but received 302.
```

**Problema:** El test placeholder espera HTTP 200 en `/`, pero la aplicacion redirige correctamente al login con 302.

Ademas, no se observaron tests funcionales para:

- login/logout;
- permisos por rol;
- cambio de contrasena de otros usuarios;
- modificacion de stock;
- recepcion de OC;
- aprobacion/rechazo de solicitudes;
- devoluciones;
- BINCARD;
- carga masiva;
- seguridad CSRF.

**Impacto:** Cambios en flujos criticos pueden romper inventario sin ser detectados por CI.

**Recomendacion:** Reemplazar el test placeholder por pruebas reales. Primer set minimo:

1. `RootRedirectsToLoginTest`
2. `UsuarioPasswordUpdatePermissionTest`
3. `OrdenCompraRecepcionTest`
4. `StockMovimientoTest`
5. `SolicitudAprobacionTest`
6. `DevolucionTest`

**Prioridad:** Alta dentro del proximo sprint.

---

### QA-05 [MEDIA] Inconsistencia entre PHP objetivo y composer platform

**Archivo:** `composer.json`

```json
"require": {
    "php": "^8.2"
},
"config": {
    "platform": {
        "php": "8.2.0"
    }
}
```

**Problema:** El proyecto se esta trabajando como PHP 8.4, pero Composer resuelve dependencias como si la plataforma fuera PHP 8.2.

**Impacto:** Puede ocultar incompatibilidades reales del entorno objetivo o impedir resolver versiones mas adecuadas para PHP 8.4.

**Recomendacion:** Definir una politica clara:

- Si produccion sera PHP 8.4, actualizar `config.platform.php` a `8.4.0`.
- Si se requiere compatibilidad con PHP 8.2, documentarlo y probar en ambos entornos.

**Prioridad:** Media.

---

### QA-06 [MEDIA] Password por defecto sensible en conexion SICD externa

**Archivo:** `config/database.php`

```php
'password' => env('DB_SICD_PASSWORD', '123456'),
```

**Problema:** Si falta la variable `DB_SICD_PASSWORD`, Laravel usara `123456` como fallback.

**Impacto:** Mala practica de seguridad. Puede conectar accidentalmente con una credencial debil o esconder errores de configuracion.

**Recomendacion:** Cambiar fallback a `null` o string vacio:

```php
'password' => env('DB_SICD_PASSWORD'),
```

**Prioridad:** Media.

---

### QA-07 [MEDIA] Logs de depuracion activos en flujo de recepcion

**Archivo:** `app/Http/Controllers/OrdenCompraController.php`

Ejemplos:

```php
\Log::info('procesarRecepcion START', ['id' => $id, 'user' => Auth::id()]);
\Log::info('procesarRecepcion OK', ['oc' => $oc->numero_oc]);
```

**Problema:** Hay logs informativos de depuracion en un flujo operativo sensible. No exponen contrasenas, pero pueden ensuciar logs productivos y revelar actividad interna.

**Impacto:** Ruido operacional y posible exposicion de datos de negocio en logs.

**Recomendacion:** Mantener solo logs de auditoria necesarios, con estructura clara y sin datos sensibles. Mover logs de debug a nivel `debug` o eliminarlos.

**Prioridad:** Media.

---

### QA-08 [BAJA] Uso frecuente de `classList.add('hidden')` en vistas

**Archivos:** varias vistas Blade.

**Problema:** El contexto tecnico del proyecto indica que los modales deben usar el patron `style="display:none"` y `el.style.display='flex'`, evitando depender de `hidden` en modales. Se detectaron multiples usos de `classList.add('hidden')`.

**Impacto:** Riesgo visual/interactivo en modales, especialmente con Tailwind JIT y estados dia/noche.

**Recomendacion:** Auditar primero solo modales con problemas reales y migrar gradualmente al patron documentado.

**Prioridad:** Baja.

---

### QA-09 [BAJA] `AdminMiddleware` permite entrar a `/admin` con cualquier permiso

**Archivo:** `app/Http/Middleware/AdminMiddleware.php`

```php
if ($user->esAdmin() || $user->tieneAlgunPermiso()) {
    return $next($request);
}
```

**Observacion:** El patron es valido si cada controlador valida permisos especificos con `abort_unless`. El riesgo aparece cuando una ruta nueva se agrega sin validacion fina.

**Recomendacion:** Agregar tests de permisos por ruta admin o crear middleware por permiso para modulos criticos.

**Prioridad:** Baja/media segun crecimiento del equipo.

---

### QA-10 [BAJA] `composer.json` conserva metadata de skeleton Laravel

**Archivo:** `composer.json`

```json
"name": "laravel/laravel",
"description": "The skeleton application for the Laravel framework."
```

**Problema:** No afecta ejecucion, pero reduce trazabilidad del paquete y profesionalismo del proyecto.

**Recomendacion:** Cambiar metadata a nombre y descripcion reales del sistema.

**Prioridad:** Baja.

---

## Hallazgos Positivos

- El modelo `User` castea `rol` como entero y `permisos` como array.
- El permiso `stock` ya esta declarado en `User::PERMISOS_DISPONIBLES`.
- El cambio de contrasena de otros usuarios esta aislado para Super Administrador (`rol = 2`) y registra auditoria sin guardar contrasenas.
- Se usa `Hash::make()` al actualizar contrasenas.
- Varios flujos de stock usan `DB::transaction()`.
- Flujos de devolucion revisados usan `lockForUpdate()` y recalculo dentro de transaccion.
- Hay headers de seguridad basicos (`X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, `CSP frame-ancestors`).
- El build frontend con Vite compila correctamente.
- Las migraciones estan aplicadas en el entorno revisado.
- La sintaxis PHP de codigo principal no presenta errores.

---

## Resultado de Pruebas

### PHPUnit

Comando:

```powershell
C:\xampp\php\php.exe artisan test
```

Resultado:

```text
PASS  Tests\Unit\ExampleTest
PASS  Tests\Feature\ExampleTest
Tests: 2 passed (3 assertions)
```

Interpretacion QA:

La suite automatizada disponible queda en verde. El test feature actual valida que `/` redirige a login, que es el comportamiento esperado del sistema.

### Build Frontend

Comando:

```powershell
npm.cmd run build
```

Resultado:

```text
vite v6.4.1 building for production...
53 modules transformed.
built successfully
```

Interpretacion QA:

No se detectaron errores de compilacion frontend.

### Migraciones

Comando:

```powershell
C:\xampp\php\php.exe artisan migrate:status
```

Resultado:

Todas las migraciones aparecen como ejecutadas en el entorno revisado.

---

## Plan de Remediacion Priorizado

### Fase 1 - Correcciones inmediatas

| Prioridad | Accion | Area |
| --- | --- | --- |
| 1 | Quitar excepcion CSRF de `admin/ordenes/*/recepcion` | Seguridad |
| 2 | Validar cantidades recibidas en recepcion de OC | Integridad de stock |
| 3 | Bloquear OC/productos con `lockForUpdate()` en recepcion | Concurrencia |
| 4 | Reemplazar test placeholder por test de redireccion a login | QA automatizado |

### Fase 2 - Proximo sprint

| Prioridad | Accion | Area |
| --- | --- | --- |
| 5 | Crear tests para permisos de usuarios y cambio de contrasena | Seguridad |
| 6 | Crear tests para recepcion de OC y stock | Inventario |
| 7 | Ajustar `DB_SICD_PASSWORD` sin fallback sensible | Configuracion |
| 8 | Definir PHP platform 8.4 o compatibilidad 8.2/8.4 | Dependencias |

### Fase 3 - Mejora continua

| Prioridad | Accion | Area |
| --- | --- | --- |
| 9 | Reducir logs de debug en produccion | Observabilidad |
| 10 | Auditar modales que usan `hidden` | UI |
| 11 | Crear matriz de permisos por ruta admin | Seguridad funcional |
| 12 | Mejorar metadata de Composer | Mantenibilidad |

---

## Recomendacion QA Final

Antes de produccion, resolver los tres hallazgos altos relacionados con recepcion de OC. Ese flujo toca directamente stock, historial, precios y estados documentales; por lo tanto tiene el mayor impacto operativo.

La segunda prioridad debe ser construir una suite minima de pruebas automatizadas. El sistema ya tiene suficiente complejidad de negocio como para depender solo de pruebas manuales.

---

## Remediacion Aplicada - Items 1, 4 y 5

Fecha: 2026-05-25

### Item 1 - Locks en carga masiva/manual de stock

Se reforzo `AdminController` en los flujos de carga masiva y carga manual:

- Los productos existentes se releen con `lockForUpdate()` antes de sumar stock.
- Los productos destino por cambio de contenedor tambien se bloquean antes de modificar stock.
- La actualizacion de `stock_actual`, fechas de stock e historial queda dentro de la transaccion existente.

Riesgo residual: la creacion de productos duplicados por nombre/contenedor depende de restricciones de base de datos. La mitigacion de concurrencia del stock queda aplicada.

### Item 4 - Red de seguridad en rutas admin

Se reforzo `AdminMiddleware`:

- Mantiene el acceso general para admins o usuarios con permisos.
- Agrega validacion por nombre de ruta para permisos granulares (`sicd`, `ordenes`, `usuarios`, `reportes`, `computadores`, `containers`, `catalogo`, `stock`, etc.).
- Agrega rutas admin-only para dashboard, precios, carga masiva/manual, acciones sensibles de containers y reporteria indexada.
- Mantiene `admin.dev.*` exclusivo para rol dev/super administrador.

Objetivo: si una nueva ruta admin queda sin validacion explicita en controlador, el middleware reduce la probabilidad de exposicion accidental.

### Item 5 - Patron de modales

Se reemplazo el patron fragil `classList.add/remove('hidden')` en modales administrativos visibles por:

```js
modal.style.display = 'flex';
modal.style.display = 'none';
```

Vistas ajustadas:

- `resources/views/admin/usuarios/index.blade.php`
- `resources/views/admin/containers/index.blade.php`
- `resources/views/admin/solicitudes.blade.php`
- `resources/views/admin/productos/editar.blade.php`

No se cambiaron usos de `hidden` que corresponden a filtros, acordeones, mensajes o badges, porque no son modales.

---

## Remediacion Aplicada - Hallazgos QA Globales

Fecha: 2026-05-25

### Busqueda global

Se corrigio `SearchController`:

- Se elimino la busqueda sobre `productos.descripcion`, columna removida del esquema.
- Se separo busqueda por `id` solo cuando el termino es numerico.
- Se escaparon comodines `%` y `_` en busquedas `LIKE`.

### Rutas storage locales

Se cambio `config/filesystems.php` para que el disco local no publique rutas `storage/{path}` por defecto:

```php
'serve' => env('FILESYSTEM_SERVE', false)
```

Tambien se agrego `FILESYSTEM_SERVE=false` a `.env.example`.

### Configuracion SICD externa

Se removieron defaults ambientales de `DB_SICD_HOST`, `DB_SICD_DATABASE` y `DB_SICD_USERNAME`. Ahora deben venir desde `.env`.

### Logs de debug

Se eliminaron logs `Log::debug` de consultas Mercado Publico y del OCR temporal de ordenes de compra.

### Modales dashboard

Se ajustaron los modales principales del dashboard (`modal-solicitud`, `modal-traslado`) al patron `style.display`.

### QA automatizado

Se agregaron pruebas unitarias para:

- Roles/permisos de usuario.
- Mapeo de permisos en `AdminMiddleware`.
- Proteccion contra reintroducir busqueda por `productos.descripcion`.

Resultado actual:

```text
Tests: 8 passed (22 assertions)
npm run build: OK
route:list: sin rutas storage locales publicadas
```

---

*Reporte generado el 2026-05-25 sobre el estado local del proyecto en `c:\xampp\htdocs\inventario`. No incluye pruebas de penetracion ni auditoria dinamica con navegador.*
