@php
    $latestPlanningRow = \App\Models\Booking::whereNotNull('plan_year')->latest('arrival_date')->first();
    $defaultDate = $latestPlanningRow ? \Carbon\Carbon::create($latestPlanningRow->plan_year, $latestPlanningRow->plan_month, 1) : now();
@endphp
<script>window.location.replace(@json(route('safari-board.index', [$defaultDate->year, strtolower($defaultDate->format('F'))])));</script>
