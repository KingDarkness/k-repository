<?php

namespace KRepository;

use Illuminate\Support\ServiceProvider;

class KRepositoryServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishConfig();
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('command.kRepository', function ($app) {
            return $app['KRepository\Commands\KGeneratorRepositoryCommand'];
        });
        $this->commands('command.kRepository');
    }
    /**
     * [publishConfig description]
     * @return [type] [description]
     */
    private function publishConfig()
    {
        $this->publishes([
            __DIR__ . '/config/krepository.php' => config_path('krepository.php'),
            __DIR__ . '/config/kproviders.php' => config_path('kproviders.php')
        ]);
    }
}
