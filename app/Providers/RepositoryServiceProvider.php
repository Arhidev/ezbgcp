<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
        $models = array(
            'Base',
            'User',
            'Route',
            'Stop',
            'Bus',
            'RouteStop',
            'Trip',
            'TripDetail',
            'SuspendedTrip',
            'Reservation',
            'Setting',
            'Currency',
            'Place',
            'RouteStopDirection',
            'TripSearchResult',
            'UserPayment',
            'PlannedTrip',
            'UserCharge',
            'DriverInformation',
            'DriverDocument',
            'Redemption',
            'UserRefund',
            'Complaint',
            'BankAccount',
            'PaypalAccount',
            'MobileMoneyAccount',
            'Notification',
            'Coupon',
            'CouponCustomer',
        );

        foreach ($models as $model) {
            $this->app->bind("App\Repository\\{$model}RepositoryInterface", "App\Repository\\Eloquent\\{$model}Repository");
        }
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
