<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Guide;
use App\Models\Transfer;
use App\Models\User;
use App\Models\Vehicle;
use App\Policies\BookingPolicy;
use App\Policies\GuidePolicy;
use App\Policies\TransferPolicy;
use App\Policies\UserPolicy;
use App\Policies\VehiclePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
        Booking::class => BookingPolicy::class,
        Guide::class => GuidePolicy::class,
        Vehicle::class => VehiclePolicy::class,
        Transfer::class => TransferPolicy::class,
    ];

    public function boot(): void
    {
        //
    }
}
