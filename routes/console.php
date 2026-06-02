<?php

use App\Models\ReporteriaIndexada;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('reporteria:purgar {--dias=90 : Días de antigüedad mínima para purgar}', function () {
    $dias   = (int) $this->option('dias');
    $corte  = now()->subDays($dias);
    $viejos = ReporteriaIndexada::where('created_at', '<', $corte)->get();

    if ($viejos->isEmpty()) {
        $this->info("No hay reportes con más de {$dias} días.");
        return;
    }

    $eliminados = 0;
    foreach ($viejos as $reporte) {
        if ($reporte->ruta_archivo && Storage::disk('local')->exists($reporte->ruta_archivo)) {
            Storage::disk('local')->delete($reporte->ruta_archivo);
        }
        $reporte->delete();
        $eliminados++;
    }

    $this->info("Purgados {$eliminados} reportes con más de {$dias} días.");
})->purpose('Elimina reportes indexados antiguos y sus archivos físicos');
