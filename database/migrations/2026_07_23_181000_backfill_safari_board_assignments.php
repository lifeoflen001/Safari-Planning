<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('bookings')
            ->whereNull('plan_year')
            ->whereNotNull('arrival_date')
            ->orderBy('id')
            ->eachById(function ($booking) {
                DB::table('bookings')->where('id', $booking->id)->update([
                    'plan_year' => (int) date('Y', strtotime($booking->arrival_date)),
                    'plan_month' => (int) date('n', strtotime($booking->arrival_date)),
                ]);
            });

        DB::table('bookings')
            ->leftJoin('activities', 'activities.booking_id', '=', 'bookings.id')
            ->leftJoin('activity_assignments', 'activity_assignments.activity_id', '=', 'activities.id')
            ->where(function ($query) {
                $query->whereNull('bookings.guide_id')
                    ->orWhereNull('bookings.vehicle_id')
                    ->orWhereNull('bookings.translator_id');
            })
            ->select([
                'bookings.id',
                'activity_assignments.guide_id',
                'activity_assignments.vehicle_id',
                'activity_assignments.translator_id',
            ])
            ->orderBy('bookings.id')
            ->each(function ($assignment) {
                $values = array_filter([
                    'guide_id' => $assignment->guide_id,
                    'vehicle_id' => $assignment->vehicle_id,
                    'translator_id' => $assignment->translator_id,
                ], fn ($value) => $value !== null);
                if ($values) {
                    DB::table('bookings')->where('id', $assignment->id)->update($values);
                }
            });
    }

    public function down(): void
    {
        // Backfilled values are retained because they are now part of board data.
    }
};
