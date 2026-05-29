# Sistema de Gestión de Inventario

Sistema web de control y gestión de inventario institucional desarrollado con Laravel 12. Permite administrar el ciclo completo de abastecimiento: desde solicitudes de entrada/salida hasta órdenes de compra, SICD, reportes BINCARD y armado de equipos computacionales.

---

## Características principales

| Módulo | Descripción |
|---|---|
| **Inventario** | Stock de productos, contenedores, unidades de medida, marcas y categorías |
| **Solicitudes** | Flujo de solicitudes de entrada/salida con aprobación y devoluciones |
| **SICD** | Recepción y control de documentos de abastecimiento |
| **Órdenes de Compra** | Gestión de OC con integración a Mercado Público (Chile) |
| **Gastos Menores** | Control de compras de bajo monto |
| **Reportería** | BINCARD, actividad, variación presupuestaria, exportación Excel/PDF |
| **Computadores** | Armado de equipos y gestión de componentes |
| **Catálogo** | Familias, categorías, marcas y unidades de medida |
| **Centros de Costo** | Segregación presupuestaria por unidades organizacionales |

---

## Stack tecnológico

- **Backend:** Laravel 12 / PHP 8.2+
- **Base de datos:** SQLite (desarrollo) / MySQL (producción)
- **Frontend:** Blade + Tailwind CSS 4 + Vite 6
- **Reportes:** DomPDF (PDF) + Maatwebsite Excel (XLSX/CSV)
- **Cola de trabajos:** Database queue driver
- **Autenticación:** Sesiones web con roles y permisos granulares

---

## Roles de usuario

| Rol | Acceso |
|---|---|
| `dev` | Acceso total al sistema |
| `admin` | Acceso completo al panel administrativo |
| `usuario` | Vista de productos y gestión de solicitudes propias |

Los permisos granulares se configuran por usuario e incluyen: historial, solicitudes, SICD, órdenes de compra, gastos menores, catálogo, precios, computadores y reportería.

---

## Estructura del proyecto

```
app/
├── Http/Controllers/     # 21 controladores (Admin, Productos, SICD, OC, etc.)
├── Models/               # 26 modelos Eloquent
├── Services/             # BincardService, MercadoPublicoService, ReporteriaService, PDFOcrService
├── Imports/              # Importación de SICD desde Excel
└── Exports/              # Exportación BINCARD y Actividad

resources/views/admin/    # Vistas del panel administrativo
database/migrations/      # 83+ migraciones
routes/web.php            # Rutas web (públicas + auth + admin)
```

---

## Módulos en detalle

### Solicitudes
Flujo completo con estados: `pendiente → aprobada/rechazada`. Soporta solicitudes de entrada (ingreso de stock) y salida (despacho). Incluye módulo de devoluciones con trazabilidad.

### SICD (Sistema de Control de Documentos)
Recepción de documentos de compra (facturas, guías de despacho, boletas). Permite resolución de conflictos, generación de PDF y vinculación con órdenes de compra.

### Mercado Público
Integración con la API de Mercado Público de Chile para consulta y vinculación de órdenes de compra oficiales.

### BINCARD
Informe de movimientos de inventario por producto en un período, exportable a Excel y PDF. Incluye indexación para consultas históricas eficientes.

### Computadores
Módulo especializado para el armado de equipos computacionales. Registra componentes individuales, vincula al inventario y gestiona el estado del equipo.

---

## Instalación rápida

```bash
git clone <repositorio> inventario
cd inventario
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

Para instrucciones detalladas, incluyendo configuración para producción con MySQL, consulte [INSTALL_GUIDE.md](INSTALL_GUIDE.md).

---

## Requisitos mínimos

- PHP 8.2+
- Composer 2.x
- Node.js 20+ / npm 10+
- SQLite 3 (desarrollo) o MySQL 8+ (producción)

---

## Licencia

Uso interno institucional. No distribuir sin autorización.
