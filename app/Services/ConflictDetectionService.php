<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Vehicle;
use Illuminate\Support\Collection;

class ConflictDetectionService
{
    /** Validate resource overlap and capacity rules before a calendar change is committed. */
    public function for(Activity $activity, array $assignment): Collection
    {
        $conflicts = collect();
        foreach (['guide_id' => 'Guide', 'vehicle_id' => 'Vehicle', 'translator_id' => 'Translator'] as $column => $label) {
            if (empty($assignment[$column])) {
                continue;
            }
            $overlap = Activity::query()
                ->where('id', '!=', $activity->getKey())
                ->whereHas('assignments', fn ($query) => $query->where($column, $assignment[$column]))
                ->where('starts_at', '<', $activity->ends_at)
                ->where('ends_at', '>', $activity->starts_at)
                ->with('booking:id,reference,group_name')
                ->first();
            if ($overlap) {
                $conflicts->push(['rule' => 'resource_overlap', 'resource' => $label, 'message' => sprintf('%s is already assigned to %s (%s) during this time.', $label, $overlap->booking?->group_name ?? 'another booking', $overlap->booking?->reference ?? 'existing activity'), 'activity_id' => $overlap->id]);
            }
        }

        $vehicle = ! empty($assignment['vehicle_id']) ? Vehicle::find($assignment['vehicle_id']) : null;
        if ($vehicle && $activity->booking && $vehicle->capacity < $activity->booking->pax) {
            $conflicts->push(['rule' => 'vehicle_capacity', 'resource' => 'Vehicle', 'message' => 'Vehicle capacity is below the booking party size.']);
        }

        return $conflicts;
    }
}
