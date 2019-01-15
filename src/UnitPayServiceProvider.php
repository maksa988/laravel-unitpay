<?php

namespace Maksa988\UnitPay;

use Illuminate\Support\ServiceProvider;

class UnitPayServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/unitpay.php' => config_path('unitpay.php'),
        ], 'config');

        //
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/unitpay.php', 'unitpay');

        $this->app->singleton('unitpay', function () {
            return $this->app->make(UnitPay::class);
        });

        $this->app->alias('unitpay', 'UnitPay');

        //
    }
}
