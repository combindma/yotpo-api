<?php

namespace Combindma\YotpoApi;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class YotpoApiServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('yotpo-api')
            ->hasConfigFile('yotpo');
    }

    public function registeringPackage()
    {
        $this->app->singleton('yotpoApi', function() {
            return new YotpoApi();
        });
    }
}
