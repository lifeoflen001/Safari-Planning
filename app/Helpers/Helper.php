<?php

namespace App\Helpers;

use Carbon\Carbon;

class Helpers
{
    /**
     * Get greeting based on time of day
     */
    public static function getTimeBasedGreeting()
    {
        $hour = Carbon::now()->hour;
        
        if ($hour >= 5 && $hour < 12) {
            return 'Good morning';
        } elseif ($hour >= 12 && $hour < 17) {
            return 'Good afternoon';
        } elseif ($hour >= 17 && $hour < 21) {
            return 'Good evening';
        } else {
            return 'Good night';
        }
    }
    
    /**
     * Get current time with timezone
     */
    public static function getCurrentTime()
    {
        return Carbon::now()->format('l, j F Y');
    }
}