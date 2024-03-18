<?php

namespace OpenSoutheners\LaravelConsoleFileMenu;

use Illuminate\Console\Command;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Command::macro('fileMenu', function (string $basePath = '') {
            return new FileMenu($basePath);
        });
    }
}
