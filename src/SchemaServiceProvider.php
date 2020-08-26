<?php

namespace Agontuk\Schema;

use Illuminate\Support\ServiceProvider;

class SchemaServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to the controller routes in your routes file.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'Agontuk\Schema\Controllers';

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/resources', 'schema');

        $app = $this->app;
        $isLumen = (strpos($app->version(), 'Lumen') !== false);
        $isEnabled = env('SCHEMA_ROUTES_ENABLED', false) && 'local' == env('APP_ENV');

        if ($isLumen && $isEnabled) {
            $app->group(['namespace' => $this->namespace], function () use ($app, $isLumen) {
                require __DIR__ . '/routes.php';
            });
        } elseif ($isEnabled) {
            $app->router->group(['namespace' => $this->namespace], function () use ($app, $isLumen) {
                require __DIR__ . '/routes.php';
            });
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
