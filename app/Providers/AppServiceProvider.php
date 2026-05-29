<?php

namespace App\Providers;

use App\Models\HistorialCambio;
use App\Models\Solicitud;
use App\Observers\HistorialCambioObserver;
use App\Observers\SolicitudObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        HistorialCambio::observe(HistorialCambioObserver::class);
        Solicitud::observe(SolicitudObserver::class);
    }
}
