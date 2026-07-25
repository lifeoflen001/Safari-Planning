<?php

namespace App\Helpers;

use Carbon\Carbon;

class Helpers
{
    public static function getTimeBasedGreeting(): string
    {
        $hour = Carbon::now()->hour;

        return match (true) {
            $hour >= 5 && $hour < 12 => 'Good morning',
            $hour >= 12 && $hour < 17 => 'Good afternoon',
            $hour >= 17 && $hour < 21 => 'Good evening',
            default => 'Good night',
        };
    }

    public static function getCurrentTime(): string
    {
        return Carbon::now()->format('l, j F Y');
    }
}
