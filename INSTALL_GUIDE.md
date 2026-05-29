# Guia de instalacion y despliegue

Esta guia documenta la instalacion local y el despliegue del sistema **Inventario**.

Proyecto:

- Laravel 12
- PHP 8.4 recomendado
- MySQL / MariaDB
- Vite + Tailwind
- Sistema de usuarios, roles, permisos, autenticacion, seguridad y panel administrativo

## Requisitos

Instalar o validar antes de comenzar:

- PHP 8.4 recomendado. El proyecto permite PHP `^8.2`, pero el entorno objetivo actual es PHP 8.4.
- Composer 2.
- Node.js 20 LTS o superior.
- npm 10 o superior.
- MySQL o MariaDB.
- Git.
- XAMPP en Windows, si se trabaja en entorno local.

Extensiones PHP requeridas o recomendadas:

- `pdo_mysql`
- `mbstring`
- `openssl`
- `tokenizer`
- `xml`
- `ctype`
- `json`
- `bcmath`
- `fileinfo`
- `zip`
- `gd`
- `curl`
- `dom`

## Roles del sistema

Los roles se guardan como enteros en base de datos:

| Valor | Rol |
| --- | --- |
| `0` | Usuario |
| `1` | Administrador |
| `2` | Super Administrador |

No usar roles como texto en base de datos. El Super Administrador corresponde a `rol = 2`.

## Instalacion local en Windows con XAMPP

Ruta recomendada del proyecto:

```powershell
C:\xampp\htdocs\inventario
```

Iniciar Apache y MySQL desde el panel de XAMPP.

Validar PHP:

```powershell
C:\xampp\php\php.exe -v
```

Instalar dependencias PHP:

```powershell
composer install
```

Instalar dependencias frontend:

```powershell
npm install
```

Crear archivo de entorno:

```powershell
Copy-Item .env.example .env
```

Configurar `.env` para desarrollo local:

```env
APP_NAME="Inventario"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost/inventario/public

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inventario
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
FILESYSTEM_DISK=local

LOG_CHANNEL=stack
LOG_LEVEL=debug
```

Crear la base de datos:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE inventario CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Generar la llave de aplicacion:

```powershell
C:\xampp\php\php.exe artisan key:generate
```

Ejecutar migraciones y seeders:

```powershell
C:\xampp\php\php.exe artisan migrate --seed
```

Compilar assets para uso normal:

```powershell
npm run build
```

Para desarrollo frontend con recarga automatica:

```powershell
npm run dev
```

Acceso local con XAMPP:

```text
http://localhost/inventario/public
```

Alternativa con servidor interno de Laravel:

```powershell
C:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=8000
```

Luego abrir:

```text
http://127.0.0.1:8000
```

## Variables externas

El sistema puede integrarse con servicios externos. No guardar credenciales reales en este documento ni en repositorios.

Mercado Publico:

```env
MP_BASE_URL=https://api.mercadopublico.cl
MP_TICKET=
MP_TIMEOUT=20
```

Conexion SICD externa:

```env
DB_SICD_HOST=
DB_SICD_PORT=3306
DB_SICD_DATABASE=
DB_SICD_USERNAME=
DB_SICD_PASSWORD=
```

Si estas variables no estan configuradas, las funcionalidades que dependan de esos servicios pueden fallar aunque el sistema principal funcione.

## Usuarios iniciales

El seeder crea usuarios de apoyo para ambiente local. Estas credenciales deben cambiarse inmediatamente en cualquier entorno compartido, QA o produccion.

| Usuario | Email | Rol |
| --- | --- | --- |
| Administrador | `admin@inventario.com` | `1` |
| Usuario Demo | `usuario@inventario.com` | `0` |
| Fernando | `fernando@inventario.com` | `2` |
| Lucas | `lucas@lucas.com` | `1` |

No mantener contrasenas de seeders en produccion.

## Instalacion en Linux / servidor

Clonar o copiar el proyecto en la ruta del servidor, por ejemplo:

```bash
/var/www/inventario
```

Instalar dependencias de produccion:

```bash
composer install --no-dev --optimize-autoloader
```

Instalar y compilar assets:

```bash
npm ci
npm run build
```

Si no existe `package-lock.json`, usar:

```bash
npm install
npm run build
```

Crear `.env`:

```bash
cp .env.example .env
```

Configurar produccion:

```env
APP_NAME="Inventario"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://dominio.example

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inventario
DB_USERNAME=usuario_seguro
DB_PASSWORD=contrasena_segura

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
```

Generar llave solo en primera instalacion:

```bash
php artisan key:generate
```

Ejecutar migraciones:

```bash
php artisan migrate --force
```

Ejecutar seeders solo si el entorno lo requiere:

```bash
php artisan db:seed --force
```

Crear enlace de storage:

```bash
php artisan storage:link
```

Optimizar Laravel:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Permisos recomendados:

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rw storage bootstrap/cache
```

El document root de Apache o Nginx debe apuntar siempre a:

```text
/var/www/inventario/public
```

## Configuracion de Apache

Ejemplo de VirtualHost:

```apache
<VirtualHost *:80>
    ServerName dominio.example
    DocumentRoot /var/www/inventario/public

    <Directory /var/www/inventario/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/inventario_error.log
    CustomLog ${APACHE_LOG_DIR}/inventario_access.log combined
</VirtualHost>
```

Activar `mod_rewrite`:

```bash
sudo a2enmod rewrite
sudo systemctl reload apache2
```

## Configuracion de Nginx

Ejemplo basico:

```nginx
server {
    listen 80;
    server_name dominio.example;
    root /var/www/inventario/public;

    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

## Colas y tareas programadas

Si el sistema usa colas, ejecutar un worker permanente con Supervisor o servicio equivalente:

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=90
```

Ejemplo de cron para scheduler de Laravel:

```cron
* * * * * cd /var/www/inventario && php artisan schedule:run >> /dev/null 2>&1
```

## Actualizacion del sistema

Antes de actualizar:

- Respaldar base de datos.
- Respaldar archivos cargados por usuarios si corresponde.
- Revisar cambios de `.env`.
- Ejecutar la actualizacion en horario controlado.

Flujo recomendado:

```bash
php artisan down
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
```

Si no existe `package-lock.json`, reemplazar `npm ci` por `npm install`.

## Seguridad

Checklist minimo:

- `APP_DEBUG=false` en produccion.
- No versionar `.env`.
- No guardar contrasenas, tokens ni llaves reales en documentacion.
- Cambiar o eliminar credenciales creadas por seeders.
- Usar HTTPS en produccion.
- Configurar `SESSION_SECURE_COOKIE=true` cuando el sitio opere solo por HTTPS.
- Mantener permisos restringidos en `storage` y `bootstrap/cache`.
- Verificar que el servidor web exponga solo la carpeta `public`.
- Mantener actualizado PHP, Composer, Node y dependencias.
- Registrar auditoria sin guardar datos sensibles.

## Comandos utiles

Limpiar caches:

```powershell
C:\xampp\php\php.exe artisan optimize:clear
```

Limpiar vistas:

```powershell
C:\xampp\php\php.exe artisan view:clear
```

Recrear base local desde cero:

```powershell
C:\xampp\php\php.exe artisan migrate:fresh --seed
```

Recrear autoload de Composer:

```powershell
composer dump-autoload
```

Compilar frontend:

```powershell
npm run build
```

## Solucion de problemas

### `php` no se reconoce en Windows

Usar la ruta completa:

```powershell
C:\xampp\php\php.exe artisan --version
```

Tambien se puede agregar `C:\xampp\php` al `PATH` del sistema.

### `No application encryption key has been specified`

Ejecutar:

```powershell
C:\xampp\php\php.exe artisan key:generate
```

### Error de conexion a base de datos

Validar:

- MySQL esta iniciado.
- La base de datos existe.
- Las credenciales de `.env` son correctas.
- Se limpio cache de configuracion despues de modificar `.env`.

```powershell
C:\xampp\php\php.exe artisan config:clear
```

### Tablas no encontradas

Ejecutar migraciones:

```powershell
C:\xampp\php\php.exe artisan migrate
```

### Assets no cargan o se ven estilos antiguos

Compilar nuevamente:

```powershell
npm run build
```

Luego limpiar vistas:

```powershell
C:\xampp\php\php.exe artisan view:clear
```

### Cambios de `.env` no se reflejan

Limpiar cache:

```powershell
C:\xampp\php\php.exe artisan config:clear
```

En produccion, regenerar cache:

```bash
php artisan config:cache
```

### Error con SICD externo o Mercado Publico

Validar:

- Variables `DB_SICD_*`.
- Variables `MP_*`.
- Conectividad de red desde el servidor.
- Permisos de firewall.
- Vigencia de tickets o credenciales.

### Permisos en Linux

Validar permisos de escritura:

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rw storage bootstrap/cache
```

## Notas finales

Este documento no debe contener secretos reales. Toda credencial productiva debe mantenerse exclusivamente en `.env` o en el gestor seguro de secretos definido por la organizacion.
