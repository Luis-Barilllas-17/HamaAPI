<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

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
        Passport::tokensCan([
            'hamas.read' => 'Consultar patrones Hama',
            'hamas.write' => 'Crear y actualizar patrones Hama',
            'hamas.delete' => 'Eliminar patrones Hama',
        ]);

        Passport::setDefaultScope(['hamas.read']);

        Passport::clientCredentialsTokensExpireIn(now()->addHour());
    }
}
