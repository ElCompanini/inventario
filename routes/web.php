<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\SolicitudController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ContainerController;
use App\Http\Controllers\SicdController;
use App\Http\Controllers\OrdenCompraController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\RetiroController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\GastoMenorController;

// Raíz → login
Route::get('/', fn() => redirect()->route('login'));

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Búsqueda global (auth)
Route::middleware('auth')->group(function () {
    Route::get('/buscar', SearchController::class)->name('buscar');
    Route::get('/buscar/live', [SearchController::class, 'live'])->name('buscar.live');
});

// Rutas autenticadas (admin y usuario)
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [ProductoController::class, 'index'])->name('dashboard');
    Route::get('/mis-solicitudes', [SolicitudController::class, 'index'])->name('solicitudes.mis');
    Route::post('/solicitudes', [SolicitudController::class, 'store'])->name('solicitudes.store');

    // Retiro de piezas (todos los usuarios autenticados)
    Route::get('/retiro', [RetiroController::class, 'form'])->name('retiro.form');
    Route::get('/retiro/buscar', [RetiroController::class, 'buscar'])->name('retiro.buscar');
    Route::post('/retiro', [RetiroController::class, 'procesar'])->name('retiro.procesar');
});

// Rutas solo admin
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/solicitudes', [AdminController::class, 'solicitudes'])->name('solicitudes');
    Route::get('/solicitudes/rechazadas', [AdminController::class, 'rechazadas'])->name('solicitudes.rechazadas');
    Route::post('/solicitudes/{id}/aprobar', [AdminController::class, 'aprobar'])->name('solicitudes.aprobar');
    Route::post('/solicitudes/{id}/rechazar', [AdminController::class, 'rechazar'])->name('solicitudes.rechazar');
    Route::get('/historial', [AdminController::class, 'historial'])->name('historial');
    Route::get('/productos/{id}/editar', [AdminController::class, 'editarStock'])->name('productos.editar');
    Route::post('/productos/{id}/stock', [AdminController::class, 'modificarStock'])->name('productos.stock');
    Route::post('/productos/{id}/trasladar', [AdminController::class, 'trasladarContainer'])->name('productos.trasladar');
    Route::post('/productos/carga-masiva', [AdminController::class, 'cargaMasiva'])->name('productos.carga.masiva');
    Route::post('/productos/carga-manual', [AdminController::class, 'cargaManual'])->name('productos.carga.manual');

    // SICD
    Route::get('/sicd', [SicdController::class, 'index'])->name('sicd.index');
    Route::get('/sicd/crear', [SicdController::class, 'create'])->name('sicd.create');
    Route::post('/sicd', [SicdController::class, 'store'])->name('sicd.store');
    Route::post('/sicd/recibir-directo', [SicdController::class, 'recibirDirecto'])->name('sicd.recibir.directo');
    Route::get('/sicd/resolver-conflictos', [SicdController::class, 'resolver'])->name('sicd.resolver');
    Route::post('/sicd/confirmar', [SicdController::class, 'confirmar'])->name('sicd.confirmar');
    Route::get('/sicd/{id}', [SicdController::class, 'show'])->name('sicd.show');
    Route::get('/sicd/{id}/descargar', [SicdController::class, 'descargar'])->name('sicd.descargar');

    // Órdenes de Compra
    Route::get('/ordenes', [OrdenCompraController::class, 'index'])->name('ordenes.index');
    Route::get('/ordenes/crear', [OrdenCompraController::class, 'create'])->name('ordenes.create');
    Route::post('/ordenes/subir-temp', [OrdenCompraController::class, 'subirArchivoTemp'])->name('ordenes.subir.temp');
    Route::post('/ordenes', [OrdenCompraController::class, 'store'])->name('ordenes.store');
    Route::get('/ordenes/{id}', [OrdenCompraController::class, 'show'])->name('ordenes.show');
    Route::post('/ordenes/{id}/factura', [OrdenCompraController::class, 'subirFactura'])->name('ordenes.factura.subir');
    Route::post('/ordenes/{id}/guia', [OrdenCompraController::class, 'subirGuia'])->name('ordenes.guia.subir');
    Route::get('/ordenes/{id}/recepcion', [OrdenCompraController::class, 'recepcion'])->name('ordenes.recepcion');
    Route::post('/ordenes/{id}/recepcion', [OrdenCompraController::class, 'procesarRecepcion'])->name('ordenes.recepcion.procesar');
    Route::get('/ordenes/{id}/descargar-factura', [OrdenCompraController::class, 'descargarFactura'])->name('ordenes.factura.descargar');
    Route::get('/ordenes/{id}/descargar-guia', [OrdenCompraController::class, 'descargarGuia'])->name('ordenes.guia.descargar');
    Route::get('/ordenes/{id}/descargar', [OrdenCompraController::class, 'descargarOc'])->name('ordenes.descargar');

    // Usuarios
    Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
    Route::get('/usuarios/crear', [UsuarioController::class, 'create'])->name('usuarios.create');
    Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
    Route::get('/usuarios/{id}/editar', [UsuarioController::class, 'edit'])->name('usuarios.edit');
    Route::put('/usuarios/{id}', [UsuarioController::class, 'update'])->name('usuarios.update');
    Route::delete('/usuarios/{id}', [UsuarioController::class, 'destroy'])->name('usuarios.destroy');

    // Gastos Menores
    Route::get('/gastos-menores', [GastoMenorController::class, 'index'])->name('gastos-menores.index');
    Route::post('/gastos-menores', [GastoMenorController::class, 'store'])->name('gastos-menores.store');
    Route::get('/gastos-menores/{id}/boleta', [GastoMenorController::class, 'descargarBoleta'])->name('gastos-menores.boleta');
    Route::get('/gastos-menores/{folio}/editar', [GastoMenorController::class, 'edit'])->name('gastos-menores.edit');
    Route::put('/gastos-menores/{folio}', [GastoMenorController::class, 'update'])->name('gastos-menores.update');

    // Containers
    Route::get('/containers', [ContainerController::class, 'index'])->name('containers.index');
    Route::get('/containers/crear', [ContainerController::class, 'create'])->name('containers.create');
    Route::post('/containers', [ContainerController::class, 'store'])->name('containers.store');
    Route::delete('/containers/{id}', [ContainerController::class, 'destroy'])->name('containers.destroy');
    Route::post('/containers/{id}/trasladar', [ContainerController::class, 'trasladar'])->name('containers.trasladar');
});
