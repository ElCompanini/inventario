<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    Illuminate\Support\Facades\Mail::raw('Prueba desde Sistema Inventario', function($m) {
        $m->to('lucas.hernan80@gmail.com')->subject('Test envio correo');
    });
    echo 'CORREO ENVIADO OK' . PHP_EOL;
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
}
