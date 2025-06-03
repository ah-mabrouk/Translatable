<?php

namespace Mabrouk\Translatable;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Mabrouk\Translatable\Console\Commands\MakeTranslationModelCommand;
use Mabrouk\Translatable\Http\Middleware\TranslatableMiddleware;

class TranslatableServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        require_once __DIR__ . '/Helpers/TranslatableHelperFunctions.php';

        if ($this->app->runningInConsole()) {
            /**
             * Translatable Config
             */
            $this->publishes([
                __DIR__ . '/config/translatable.php' => config_path('translatable.php'),
            ], 'translatable-config');
            
            // Register commands
            $this->commands([
                MakeTranslationModelCommand::class,
            ]);
        }

        $this->app->make(Router::class)->aliasMiddleware('translatable', TranslatableMiddleware::class);
    }
}
