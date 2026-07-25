<?php

namespace App\Http\Controllers;

use App\Helpers\Helpers;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\Guide;
use App\Models\ActivityAssignment;
use App\Models\Location;
use App\Models\Payment;
use App\Models\Supplier;
use App\Models\User;
use App\Models\UserSetting;
use App\Models\Vehicle;
use App\Services\AccessManagementService;
use App\Services\BookingVisibilityService;
use App\Services\SpreadsheetImportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PlannerController extends Controller
{
    public function __construct(
        private BookingVisibilityService $bookingVisibility,
        private SpreadsheetImportService $spreadsheetImports,
    )
    {
    }

    public function guide(Guide $guide)
    {
        return view('planner.resource-profile', [
            'user' => auth()->user(),
            'resourceType' => 'guides',
            'resource' => $guide,
        ]);
    }

    public function vehicle(Vehicle $vehicle)
    {
        return view('planner.resource-profile', [
            'user' => auth()->user(),
            'resourceType' => 'vehicles',
            'resource' => $vehicle,
        ]);
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        if ($request->routeIs('planner.bookings')) {
            return view('planner.bookings', compact('user'));
        }

        if ($request->routeIs('planner.guides')) {
            return $this->resourceIndex($request, 'guides');
        }

        if ($request->routeIs('planner.vehicles')) {
            return $this->resourceIndex($request, 'vehicles');
        }

        $section = match (true) {
            $request->routeIs('planner.calendar') => 'calendar',
            $request->routeIs('planner.transfers') => 'transfers',
            $request->routeIs('planner.reports') => 'reports',
            $request->routeIs('planner.documents') => 'documents',
            $request->routeIs('planner.team') => 'team',
            $request->routeIs('planner.settings') => 'settings',
            $request->routeIs('planner.profile') => 'profile',
            default => null,
        };

        if ($section === 'team' && ! $user->hasPermission('users.view')) {
            abort(403, 'You do not have permission to view team access.');
        }

        if ($section === 'settings' && ! $user->hasPermission('workspace.view')) {
            abort(403, 'You do not have permission to access workspace settings.');
        }

        if ($section === 'reports' && ! $user->canExport()) {
            abort(403, 'You do not have permission to view reports.');
        }

        if ($section) {
            $moduleData = match ($section) {
                'calendar' => $this->calendarData($request, $user),
                'transfers' => [
                    ...$this->transferBoardData($request, $user),
                ],
                'resources' => [
                    'guides' => Guide::orderBy('name')->get(),
                    'vehicles' => Vehicle::orderBy('registration_number')->get(),
                ],
                'reports' => [
                    'bookingCount' => Booking::count(),
                    'activeBookingCount' => Booking::whereIn('status', ['confirmed', 'in_progress'])->count(),
                    'guestCount' => (int) Booking::sum('pax'),
                    'outstandingPayments' => (float) Payment::where('status', '!=', 'paid')->sum('amount'),
                ],
                'documents' => [
                    'documents' => Document::with(['booking', 'uploader'])->latest()->limit(50)->get(),
                    'bookings' => Booking::orderBy('reference')->get(),
                ],
                'team' => [
                    'team' => User::orderBy('name')->get(),
                ],
                'settings' => [
                    'settings' => UserSetting::firstOrCreate(['user_id' => $user->id]),
                ],
                'profile' => [],
            };

            if ($section === 'transfers') {
                return view('planner.transfer-board', array_merge([
                    'user' => $user,
                    'section' => $section,
                ], $moduleData));
            }

            return view('planner.module', array_merge([
                'user' => $user,
                'section' => $section,
            ], $moduleData));
        }

        /* Legacy placeholder overview data retained below for reference.
        $data = [
            'user' => $user,
            'greeting' => Helpers::getTimeBasedGreeting(),
            'current_time' => Helpers::getCurrentTime(),
            'hour' => Carbon::now()->hour,
            'metrics' => [
                [
                    'label' => "Today's operations",
                    'value' => '24',
                    'detail' => '6 arrivals · 8 departures',
                    'trend' => '+12%',
                    'accent' => 'sage',
                    'icon' => 'calendar',
                ],
                [
                    'label' => 'Active bookings',
                    'value' => '186',
                    'detail' => '32 new this month',
                    'trend' => '+8.4%',
                    'accent' => 'blue',
                    'icon' => 'suitcase',
                ],
                [
                    'label' => 'Guests travelling',
                    'value' => '412',
                    'detail' => 'Across 58 itineraries',
                    'trend' => '+5.7%',
                    'accent' => 'gold',
                    'icon' => 'users',
                ],
                [
                    'label' => 'Resource utilisation',
                    'value' => '78%',
                    'detail' => 'Guides & vehicles',
                    'trend' => 'Healthy',
                    'accent' => 'purple',
                    'icon' => 'car',
                ],
            ],
            'agenda' => [
                [
                    'time' => '06:45',
                    'type' => 'Arrival',
                    'title' => 'KLM 567 · JRO Airport',
                    'group' => 'The Anderson Family',
                    'detail' => '4 guests · Transfer to Gran Melia',
                    'color' => 'mint',
                    'person' => 'JM',
                ],
                // ... more agenda items
            ],
            'bookings' => [
                [
                    'id' => 'TS-2026-0184',
                    'group' => 'The Anderson Family',
                    'company' => 'Tanzania Specialist',
                    'dates' => '14 – 21 Jun 2026',
                    'pax' => 4,
                    'stage' => 'Confirmed',
                    'guide' => 'Joel Mollel',
                    'color' => 'green',
                ],
                // ... more bookings
            ],
        ];
        */

        $data = array_merge([
            'user' => $user,
            'greeting' => Helpers::getTimeBasedGreeting(),
            'current_time' => Helpers::getCurrentTime(),
            'hour' => Carbon::now()->hour,
        ], $this->overviewPayload($user));

        return view('planner.index', $data);
    }

    public function overviewData(Request $request)
    {
        $payload = $this->overviewPayload($request->user());

        return response()->json([
            'dashboard_html' => view('planner.partials.control-centre', $payload['dashboard'])->render(),
            'metrics_html' => view('partials.metrics', ['metrics' => $payload['metrics']])->render(),
            'agenda_html' => view('planner.partials.todays-operations', ['agenda' => $payload['agenda']])->render(),
            'calendar_html' => view('planner.partials.planning-overview', ['calendar' => $payload['calendar']])->render(),
            'alerts_html' => view('planner.partials.alerts', ['alerts' => $payload['alerts']])->render(),
            'utilisation_html' => view('planner.partials.utilisation', ['utilisation' => $payload['utilisation']])->render(),
            'bookings_html' => view('planner.partials.recent-bookings', ['bookings' => $payload['bookings']])->render(),
            'refreshed_at' => now()->format('d M Y, H:i:s'),
        ]);
    }

    private function overviewPayload(User $user): array
    {
        $now = now();
        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();
        $monthStart = $now->copy()->startOfMonth();
        $weekStart = $now->copy()->startOfWeek();
        $weekEnd = $now->copy()->endOfWeek()->endOfDay();
        $visibleBookings = fn () => $this->bookingVisibility->apply(Booking::query(), $user)->whereNull('archived_at');
        $todayBookings = fn () => ($visibleBookings())
            ->where('arrival_date', '<=', $todayStart->toDateString())
            ->where(function ($query) use ($todayStart): void {
                $query->whereNull('departure_date')->orWhere('departure_date', '>=', $todayStart->toDateString());
            });
        $activityScope = fn () => Activity::query()
            ->with(['booking.company', 'booking.guide', 'booking.vehicle', 'startLocation', 'endLocation', 'assignments.guide', 'assignments.vehicle'])
            ->whereHas('booking', function ($query) use ($user): void {
                $this->bookingVisibility->apply($query, $user)->whereNull('archived_at');
            });
        $todayActivities = ($activityScope())->whereBetween('starts_at', [$todayStart, $todayEnd])->orderBy('starts_at')->get();
        $weekActivities = ($activityScope())->whereBetween('starts_at', [$weekStart, $weekEnd])->orderBy('starts_at')->limit(100)->get();
        $workloadActivities = ($activityScope())->whereBetween('starts_at', [$todayStart, $todayStart->copy()->addDays(6)->endOfDay()])->get();

        $activeBookings = $visibleBookings()->whereIn('status', ['confirmed', 'in_progress']);
        $todayBookingRecords = ($todayBookings())->get(['id', 'pax', 'guide_id', 'vehicle_id']);
        $totalGuides = Guide::count();
        $totalVehicles = Vehicle::count();
        $guideUsage = $todayBookingRecords->whereNotNull('guide_id')->pluck('guide_id')->unique()->count();
        $vehicleUsage = $todayBookingRecords->whereNotNull('vehicle_id')->pluck('vehicle_id')->unique()->count();
        $guidePercent = $totalGuides ? min(100, (int) round($guideUsage / $totalGuides * 100)) : 0;
        $vehiclePercent = $totalVehicles ? min(100, (int) round($vehicleUsage / $totalVehicles * 100)) : 0;
        $averageUtilisation = (int) round(($guidePercent + $vehiclePercent) / 2);

        $metrics = [
            ['label' => "Today's operations", 'value' => (string) $todayActivities->count(), 'detail' => $todayBookings()->count().' itineraries in progress', 'trend' => $todayActivities->count() ? 'Live' : 'Clear', 'accent' => 'sage', 'icon' => 'calendar'],
            ['label' => 'Active bookings', 'value' => (string) $activeBookings->count(), 'detail' => $visibleBookings()->where('created_at', '>=', $monthStart)->count().' added this month', 'trend' => 'Live', 'accent' => 'blue', 'icon' => 'suitcase'],
            ['label' => 'Guests travelling', 'value' => (string) $todayBookingRecords->sum('pax'), 'detail' => $todayBookingRecords->count().' itineraries today', 'trend' => 'Live', 'accent' => 'gold', 'icon' => 'users'],
            ['label' => 'Resource utilisation', 'value' => $averageUtilisation.'%', 'detail' => 'Guides '.$guidePercent.'% · Vehicles '.$vehiclePercent.'%', 'trend' => $averageUtilisation ? 'Live' : 'Ready', 'accent' => 'purple', 'icon' => 'car'],
        ];

        $agenda = $todayActivities->take(6)->map(fn (Activity $activity): array => [
            'time' => $activity->starts_at?->format('H:i') ?: '—',
            'type' => ucfirst(str_replace('_', ' ', $activity->type ?: 'Activity')),
            'title' => $activity->title,
            'group' => $activity->booking?->group_name ?: 'Unlinked booking',
            'detail' => ($activity->booking?->pax ?? 0).' guests · '.($activity->assignments->first()?->guide?->name ?: 'Resource unassigned'),
            'color' => $activity->type === 'transfer' ? 'blue' : ($activity->type === 'arrival' ? 'mint' : 'sand'),
            'person' => $this->initials($activity->assignments->first()?->guide?->name ?: 'OP'),
            'url' => $this->bookingBoardUrl($activity->booking),
        ])->values()->all();

        $calendarEvents = $weekActivities->map(function (Activity $activity) use ($weekStart): array {
            $start = $activity->starts_at ?: $weekStart;
            $hoursFromSix = max(0, min(11, $start->hour - 6));
            $duration = max(1, (int) ceil(max(60, $start->diffInMinutes($activity->ends_at ?: $start->copy()->addHour())) / 60));

            return [
                'day' => min(6, $weekStart->diffInDays($start->copy()->startOfDay())),
                'start' => $hoursFromSix,
                'span' => min(12 - $hoursFromSix, $duration),
                'label' => $activity->title,
                'type' => $activity->type === 'transfer' ? 'transfer' : 'safari',
                'guests' => ($activity->booking?->pax ?? 0).' pax',
                'url' => $this->bookingBoardUrl($activity->booking),
            ];
        })->values()->all();

        $recentBookings = ($visibleBookings())->with(['company', 'guide'])->latest('updated_at')->limit(6)->get()->map(fn (Booking $booking): array => [
            'id' => $booking->reference,
            'group' => $booking->group_name,
            'company' => $booking->company?->name ?: 'Workspace booking',
            'dates' => $booking->arrival_date?->format('d M Y').' – '.($booking->departure_date?->format('d M Y') ?: 'Open'),
            'pax' => $booking->pax,
            'stage' => ucfirst(str_replace('_', ' ', $booking->status ?: 'draft')),
            'guide' => $booking->guide?->name ?: 'Unassigned',
            'color' => $booking->status === 'in_progress' ? 'blue' : ($booking->status === 'draft' ? 'amber' : 'green'),
            'url' => $this->bookingBoardUrl($booking),
        ])->values()->all();

        $capacityBooking = ($todayBookings())->with('vehicle')->get()->first(fn (Booking $booking): bool => $booking->vehicle && $booking->pax > $booking->vehicle->capacity);
        $paymentBooking = ($visibleBookings())->with('payments')->whereHas('payments', function ($query) use ($todayEnd): void {
            $query->where('status', '!=', 'paid')->whereNotNull('due_date')->where('due_date', '<=', $todayEnd->toDateString());
        })->latest('updated_at')->first();
        $translatorBooking = ($todayBookings())->whereNotNull('language')->whereNull('translator_id')->first();
        $alerts = collect([
            $capacityBooking ? ['type' => 'warn', 'title' => 'Vehicle capacity mismatch', 'detail' => $capacityBooking->reference.' · '.$capacityBooking->pax.' guests assigned to '.($capacityBooking->vehicle?->registration_number ?: 'vehicle').' (capacity '.$capacityBooking->vehicle->capacity.')', 'action' => 'Review', 'url' => $this->bookingBoardUrl($capacityBooking)] : null,
            $paymentBooking ? ['type' => 'info', 'title' => 'Payment follow-up due', 'detail' => $paymentBooking->reference.' · outstanding payment needs review', 'action' => 'Review', 'url' => $this->bookingBoardUrl($paymentBooking)] : null,
            $translatorBooking ? ['type' => 'neutral', 'title' => 'Unassigned translator', 'detail' => $translatorBooking->reference.' · '.$translatorBooking->language.' language requested', 'action' => 'Assign', 'url' => $this->bookingBoardUrl($translatorBooking)] : null,
        ])->filter()->values()->all();

        $dailyCounts = collect(range(11, 0))->map(function (int $daysAgo) use ($now, $activityScope): int {
            $date = $now->copy()->subDays($daysAgo);
            return ($activityScope())->whereDate('starts_at', $date->toDateString())->count();
        });
        $maxDailyCount = max(1, $dailyCounts->max());

        return [
            'metrics' => $metrics,
            'agenda' => $agenda,
            'bookings' => $recentBookings,
            'alerts' => $alerts,
            'utilisation' => ['average' => $averageUtilisation, 'guide' => $guidePercent, 'vehicle' => $vehiclePercent, 'bars' => $dailyCounts->map(fn (int $count): int => $count ? max(8, (int) round($count / $maxDailyCount * 100)) : 3)->all()],
            'calendar' => ['weekStart' => $weekStart, 'days' => collect(range(0, 6))->map(fn (int $day): array => ['label' => $weekStart->copy()->addDays($day)->format('D'), 'date' => $weekStart->copy()->addDays($day)->format('j'), 'today' => $weekStart->copy()->addDays($day)->isToday()])->all(), 'events' => $calendarEvents],
            'dashboard' => $this->controlCentrePayload($user),
        ];
    }

    private function controlCentrePayload(User $user): array
    {
        $now = now();
        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();
        $visibleBookings = fn () => $this->bookingVisibility->apply(Booking::query(), $user)->whereNull('archived_at');
        $activityScope = fn () => Activity::query()
            ->with(['booking.guide', 'booking.vehicle', 'startLocation', 'endLocation', 'assignments.guide', 'assignments.vehicle'])
            ->whereHas('booking', function ($query) use ($user): void {
                $this->bookingVisibility->apply($query, $user)->whereNull('archived_at');
            });
        $todayActivities = ($activityScope())->whereBetween('starts_at', [$todayStart, $todayEnd])->orderBy('starts_at')->get();
        $todayBookings = ($visibleBookings())->whereDate('arrival_date', $todayStart->toDateString())->get(['id', 'pax']);
        $todayDepartures = ($visibleBookings())->whereDate('departure_date', $todayStart->toDateString())->get(['id', 'pax']);
        $todayTransfers = $todayActivities->where('type', 'transfer');
        $activeSafaris = ($activityScope())->where('type', 'safari')
            ->whereBetween('starts_at', [$todayStart, $todayEnd])
            ->whereIn('status', ['confirmed', 'in_progress', 'planned'])
            ->count();
        $guides = Guide::query()->get(['id', 'status']);
        $vehicles = Vehicle::query()->get(['id', 'status']);
        $assignedGuideIds = $todayActivities->flatMap(fn (Activity $activity) => $activity->assignments->pluck('guide_id'))->filter()->unique();
        $assignedVehicleIds = $todayActivities->flatMap(fn (Activity $activity) => $activity->assignments->pluck('vehicle_id'))->filter()->unique();

        $statusClass = function (Activity $activity): string {
            $assignment = $activity->assignments->first();
            if (! $assignment || ! $assignment->guide_id || ! $assignment->vehicle_id) {
                return 'unassigned';
            }

            return match ($activity->status) {
                'confirmed', 'completed' => 'confirmed',
                'in_progress' => 'progress',
                'delayed', 'cancelled' => 'danger',
                default => 'warning',
            };
        };
        $guideName = fn (Activity $activity): string => $activity->assignments->first()?->guide?->name ?: $activity->booking?->guide?->name ?: 'Unassigned';
        $vehicleName = fn (Activity $activity): string => $activity->assignments->first()?->vehicle?->registration_number ?: $activity->booking?->vehicle?->registration_number ?: 'Unassigned';
        $flight = fn (Activity $activity): string => trim((string) ($activity->flight_number ?: $activity->booking?->primary_flight_time)) ?: 'Missing flight';
        $operationRow = function (Activity $activity, bool $arrival) use ($statusClass, $guideName, $vehicleName, $flight): array {
            return [
                'time' => $activity->starts_at?->format('H:i') ?: '—',
                'guest' => $activity->booking?->group_name ?: 'Unlinked booking',
                'pax' => (int) ($activity->booking?->pax ?: 0),
                'flight' => $flight($activity),
                'location' => ($arrival ? $activity->startLocation?->name : $activity->endLocation?->name) ?: 'Location not set',
                'guide' => $guideName($activity),
                'vehicle' => $vehicleName($activity),
                'status' => ucfirst(str_replace('_', ' ', $activity->status ?: 'planned')),
                'status_class' => $statusClass($activity),
                'url' => $this->bookingBoardUrl($activity->booking),
            ];
        };
        $arrivals = $todayTransfers->filter(fn (Activity $activity): bool => (bool) $activity->booking?->arrival_date?->isToday())->take(20)->map(fn (Activity $activity): array => $operationRow($activity, true))->values()->all();
        $departures = $todayTransfers->filter(fn (Activity $activity): bool => (bool) $activity->booking?->departure_date?->isToday())->take(20)->map(fn (Activity $activity): array => $operationRow($activity, false))->values()->all();
        $timeline = $todayActivities->take(18)->map(fn (Activity $activity): array => [
            'time' => $activity->starts_at?->format('H:i') ?: '—',
            'type' => ucfirst(str_replace('_', ' ', $activity->type ?: 'operation')),
            'title' => $activity->title ?: 'Operation',
            'guest' => $activity->booking?->group_name ?: 'Unlinked booking',
            'resource' => $guideName($activity).' · '.$vehicleName($activity),
            'status' => ucfirst(str_replace('_', ' ', $activity->status ?: 'planned')),
            'status_class' => $statusClass($activity),
            'url' => $this->bookingBoardUrl($activity->booking),
        ])->values()->all();

        $needsAttention = [];
        foreach ($todayActivities as $activity) {
            $assignment = $activity->assignments->first();
            if (! $assignment || ! $assignment->guide_id || ! $assignment->vehicle_id) {
                $missing = [];
                if (! $assignment || ! $assignment->guide_id) {
                    $missing[] = 'guide';
                }
                if (! $assignment || ! $assignment->vehicle_id) {
                    $missing[] = 'vehicle';
                }
                $needsAttention[] = ['type' => 'neutral', 'title' => 'Unassigned '.implode(' and ', $missing), 'detail' => ($activity->booking?->reference ?: 'Operation').' · '.($activity->booking?->group_name ?: $activity->title), 'action' => 'Assign', 'url' => $this->bookingBoardUrl($activity->booking)];
            }
            if ($activity->type === 'transfer' && $activity->booking?->arrival_date?->isToday() && $flight($activity) === 'Missing flight') {
                $needsAttention[] = ['type' => 'warn', 'title' => 'Missing flight details', 'detail' => ($activity->booking?->reference ?: 'Operation').' · '.$activity->booking?->group_name, 'action' => 'Review', 'url' => $this->bookingBoardUrl($activity->booking)];
            }
            if ($activity->status === 'delayed') {
                $needsAttention[] = ['type' => 'danger', 'title' => 'Delayed operation', 'detail' => ($activity->booking?->reference ?: 'Operation').' · '.$activity->booking?->group_name, 'action' => 'Review', 'url' => $this->bookingBoardUrl($activity->booking)];
            }
        }
        for ($firstIndex = 0; $firstIndex < $todayActivities->count(); $firstIndex++) {
            $first = $todayActivities->values()->get($firstIndex);
            $firstAssignment = $first->assignments->first();
            for ($secondIndex = $firstIndex + 1; $secondIndex < $todayActivities->count(); $secondIndex++) {
                $second = $todayActivities->values()->get($secondIndex);
                $secondAssignment = $second->assignments->first();
                $sameResource = $firstAssignment && $secondAssignment && (($firstAssignment->guide_id && $firstAssignment->guide_id === $secondAssignment->guide_id) || ($firstAssignment->vehicle_id && $firstAssignment->vehicle_id === $secondAssignment->vehicle_id));
                if ($sameResource && $first->starts_at && $first->ends_at && $second->starts_at && $second->ends_at && $first->starts_at->lt($second->ends_at) && $second->starts_at->lt($first->ends_at)) {
                    $needsAttention[] = ['type' => 'danger', 'title' => 'Booking conflict', 'detail' => ($first->booking?->reference ?: 'Operation').' overlaps '.($second->booking?->reference ?: 'another operation'), 'action' => 'Resolve', 'url' => $this->bookingBoardUrl($first->booking)];
                    break 2;
                }
            }
        }
        $needsAttention = array_slice($needsAttention, 0, 12);

        $availability = function ($records, $assignedIds, bool $guide): array {
            $count = fn (array $statuses): int => $records->whereIn('status', $statuses)->count();
            $segments = [
                ['label' => 'Assigned', 'value' => $assignedIds->count(), 'tone' => 'green'],
                ['label' => 'Available', 'value' => $count(['available']), 'tone' => 'blue'],
                ['label' => $guide ? 'On safari' : 'On trip', 'value' => $count(['on_trip', 'on_safari']), 'tone' => 'amber'],
                ['label' => 'Leave', 'value' => $guide ? $count(['on_leave', 'leave']) : 0, 'tone' => 'grey'],
                ['label' => 'Maintenance', 'value' => $guide ? 0 : $count(['maintenance', 'in_service']), 'tone' => 'red'],
                ['label' => 'Unavailable', 'value' => $count(['unavailable']), 'tone' => 'grey'],
            ];
            $total = max(1, $records->count());
            foreach ($segments as &$segment) {
                $segment['percent'] = round($segment['value'] / $total * 100, 2);
            }

            return ['total' => $records->count(), 'segments' => $segments];
        };
        $workloadActivities = ($activityScope())->whereBetween('starts_at', [$todayStart, $todayStart->copy()->addDays(6)->endOfDay()])->get(['starts_at']);
        $workload = collect(range(0, 6))->map(function (int $offset) use ($todayStart, $workloadActivities): array {
            $date = $todayStart->copy()->addDays($offset);
            return ['label' => $date->format('D j'), 'count' => $workloadActivities->filter(fn (Activity $activity): bool => (bool) $activity->starts_at?->isSameDay($date))->count()];
        })->all();
        $maxWorkload = max(1, max(array_column($workload, 'count')));
        foreach ($workload as &$day) {
            $day['height'] = $day['count'] ? max(8, round($day['count'] / $maxWorkload * 100)) : 3;
        }
        $bookingStatuses = ($visibleBookings())->whereBetween('arrival_date', [$monthStart->toDateString(), $monthEnd->toDateString()])->get(['status'])->groupBy('status')->map->count()->sortDesc()->all();
        $recentActivity = AuditLog::with('user')->latest('created_at')->limit(8)->get()->map(fn (AuditLog $log): array => ['action' => ucfirst(str_replace(['.', '_'], ' ', (string) $log->action)), 'user' => $log->user?->name ?: 'System', 'time' => $log->created_at?->diffForHumans() ?: 'Recently'])->all();

        return [
            'user' => $user,
            'first_name' => Str::before(trim((string) $user->name), ' ') ?: 'there',
            'greeting' => Helpers::getTimeBasedGreeting(),
            'clock_timezone' => $user->timezone ?: config('app.timezone'),
            'clock_time' => now($user->timezone ?: config('app.timezone'))->format('H:i:s'),
            'date_label' => $todayStart->format('l, j F Y'),
            'kpis' => [
                ['label' => "Today's transfers", 'value' => $todayTransfers->count(), 'detail' => $todayTransfers->sum(fn (Activity $activity): int => (int) ($activity->booking?->pax ?: 0)).' guests', 'tone' => 'green'],
                ['label' => 'Active safaris', 'value' => $activeSafaris, 'detail' => 'Scheduled today', 'tone' => 'blue'],
                ['label' => 'Arriving guests', 'value' => $todayBookings->sum('pax'), 'detail' => $todayBookings->count().' arrivals', 'tone' => 'amber'],
                ['label' => 'Available vehicles', 'value' => $vehicles->where('status', 'available')->count(), 'detail' => $vehicles->count().' in fleet', 'tone' => 'green'],
                ['label' => 'Available guides', 'value' => $guides->where('status', 'available')->count(), 'detail' => $guides->count().' in roster', 'tone' => 'blue'],
                ['label' => 'Unassigned operations', 'value' => $todayActivities->filter(fn (Activity $activity): bool => ! $activity->assignments->first() || ! $activity->assignments->first()->guide_id || ! $activity->assignments->first()->vehicle_id)->count(), 'detail' => 'Needs allocation', 'tone' => 'red'],
            ],
            'arrivals' => $arrivals,
            'departures' => $departures,
            'timeline' => $timeline,
            'needs_attention' => $needsAttention,
            'guide_availability' => $availability($guides, $assignedGuideIds, true),
            'vehicle_availability' => $availability($vehicles, $assignedVehicleIds, false),
            'workload' => $workload,
            'booking_statuses' => $bookingStatuses,
            'recent_activity' => $recentActivity,
            'today_departure_count' => $todayDepartures->count(),
        ];
    }

    private function calendarData(Request $request, User $user): array
    {
        $view = in_array($request->string('view')->toString(), ['month', 'week', 'day'], true)
            ? $request->string('view')->toString()
            : 'week';
        $focusDate = now()->startOfDay();

        if ($request->filled('date')) {
            try {
                $focusDate = Carbon::createFromFormat('Y-m-d', $request->string('date')->toString())->startOfDay();
            } catch (\Throwable) {
                $focusDate = now()->startOfDay();
            }
        }

        $rangeStart = match ($view) {
            'month' => $focusDate->copy()->startOfMonth(),
            'day' => $focusDate->copy()->startOfDay(),
            default => $focusDate->copy()->startOfWeek(),
        };
        $rangeEnd = match ($view) {
            'month' => $focusDate->copy()->endOfMonth(),
            'day' => $focusDate->copy()->endOfDay(),
            default => $focusDate->copy()->endOfWeek()->endOfDay(),
        };

        $activityQuery = Activity::query()
            ->with([
                'booking.company',
                'booking.guide',
                'booking.vehicle',
                'assignments.guide',
                'assignments.vehicle',
                'startLocation',
                'endLocation',
                'supplier',
            ])
            ->whereBetween('starts_at', [$rangeStart, $rangeEnd])
            ->whereHas('booking', function ($query) use ($user): void {
                $this->bookingVisibility->apply($query, $user)->whereNull('archived_at');
            })
            ->orderBy('starts_at')
            ->limit(500);
        $allActivities = $activityQuery->get();

        $guideId = $request->string('guide')->toString();
        $vehicleId = $request->string('vehicle')->toString();
        $bookingId = $request->string('booking')->toString();
        $activityType = $request->string('activity_type')->toString();
        $status = $request->string('status')->toString();
        $assignmentState = $request->string('assignment_state')->toString();
        $search = Str::lower(trim($request->string('q')->toString()));

        $hasCompleteAssignment = static function (Activity $activity): bool {
            $assignment = $activity->assignments->first();

            return (bool) ($assignment?->guide_id && $assignment?->vehicle_id);
        };

        $activities = $allActivities->filter(function (Activity $activity) use ($activityType, $status, $guideId, $vehicleId, $bookingId, $assignmentState, $search, $hasCompleteAssignment): bool {
            $assignment = $activity->assignments->first();
            $resolvedGuideId = (string) ($assignment?->guide_id ?: $activity->booking?->guide_id ?: '');
            $resolvedVehicleId = (string) ($assignment?->vehicle_id ?: $activity->booking?->vehicle_id ?: '');
            $bookingText = trim(($activity->booking?->reference ?: '').' '.($activity->booking?->group_name ?: ''));
            $searchText = Str::lower(implode(' ', array_filter([
                $activity->title,
                $activity->type,
                $activity->status,
                $activity->flight_number,
                $activity->notes,
                $bookingText,
                $activity->startLocation?->name,
                $activity->endLocation?->name,
                $assignment?->guide?->name,
                $assignment?->vehicle?->registration_number,
            ])));

            return ($activityType === '' || $activity->type === $activityType)
                && ($status === '' || $activity->status === $status)
                && ($guideId === '' || $resolvedGuideId === $guideId)
                && ($vehicleId === '' || $resolvedVehicleId === $vehicleId)
                && ($bookingId === '' || (string) $activity->booking_id === $bookingId)
                && ($assignmentState === ''
                    || ($assignmentState === 'assigned' && $hasCompleteAssignment($activity))
                    || ($assignmentState === 'unassigned' && ! $hasCompleteAssignment($activity)))
                && ($search === '' || Str::contains($searchText, $search));
        })->values();

        $resourceIds = static function (Activity $activity): array {
            $assignment = $activity->assignments->first();

            return [
                'guide' => $assignment?->guide_id ?: $activity->booking?->guide_id,
                'vehicle' => $assignment?->vehicle_id ?: $activity->booking?->vehicle_id,
            ];
        };
        $conflicts = [];
        for ($firstIndex = 0; $firstIndex < $allActivities->count(); $firstIndex++) {
            $first = $allActivities->values()->get($firstIndex);
            if (! $first->starts_at || ! $first->ends_at) {
                continue;
            }
            $firstResources = $resourceIds($first);
            for ($secondIndex = $firstIndex + 1; $secondIndex < $allActivities->count(); $secondIndex++) {
                $second = $allActivities->values()->get($secondIndex);
                if (! $second->starts_at || ! $second->ends_at) {
                    continue;
                }
                $secondResources = $resourceIds($second);
                $sameGuide = $firstResources['guide'] && $firstResources['guide'] === $secondResources['guide'];
                $sameVehicle = $firstResources['vehicle'] && $firstResources['vehicle'] === $secondResources['vehicle'];
                $overlaps = $first->starts_at->lt($second->ends_at) && $second->starts_at->lt($first->ends_at);
                if ($overlaps && ($sameGuide || $sameVehicle)) {
                    $reason = $sameGuide ? 'Guide conflict' : 'Vehicle conflict';
                    $conflicts[$first->id][] = $reason;
                    $conflicts[$second->id][] = $reason;
                }
            }
        }
        $conflicts = collect($conflicts)->map(fn (array $reasons): array => array_values(array_unique($reasons)))->all();

        $statusClass = static function (Activity $activity, bool $unassigned, bool $conflicted): string {
            if ($conflicted) {
                return 'danger';
            }
            if ($unassigned) {
                return 'unassigned';
            }

            return match ($activity->status) {
                'confirmed', 'completed' => 'confirmed',
                'in_progress' => 'progress',
                'delayed', 'cancelled' => 'danger',
                default => 'warning',
            };
        };
        $guideName = static fn (Activity $activity): string => $activity->assignments->first()?->guide?->name ?: $activity->booking?->guide?->name ?: 'Unassigned';
        $vehicleName = static fn (Activity $activity): string => $activity->assignments->first()?->vehicle?->registration_number ?: $activity->booking?->vehicle?->registration_number ?: 'Unassigned';

        $rows = $activities->map(function (Activity $activity) use ($hasCompleteAssignment, $conflicts, $statusClass, $guideName, $vehicleName): array {
            $unassigned = ! $hasCompleteAssignment($activity);
            $conflictReasons = $conflicts[$activity->id] ?? [];
            $statusLabel = ucfirst(str_replace('_', ' ', $activity->status ?: 'planned'));

            return [
                'id' => $activity->id,
                'date' => $activity->starts_at?->format('d M Y') ?: '—',
                'time' => $activity->starts_at?->format('H:i') ?: '—',
                'type' => ucfirst(str_replace('_', ' ', $activity->type ?: 'activity')),
                'title' => $activity->title ?: 'Untitled activity',
                'group' => $activity->booking?->group_name ?: 'Unlinked booking',
                'pax' => (int) ($activity->booking?->pax ?: 0),
                'booking' => $activity->booking?->reference ?: '—',
                'pickup' => $activity->startLocation?->name ?: 'Not set',
                'destination' => $activity->endLocation?->name ?: 'Not set',
                'guide' => $guideName($activity),
                'vehicle' => $vehicleName($activity),
                'status' => $statusLabel,
                'status_class' => $statusClass($activity, $unassigned, (bool) $conflictReasons),
                'unassigned' => $unassigned,
                'conflict' => (bool) $conflictReasons,
                'attention' => $conflictReasons ? implode(', ', $conflictReasons) : ($unassigned ? 'Missing guide or vehicle' : ''),
                'url' => $this->bookingBoardUrl($activity->booking),
            ];
        })->values()->all();

        $periodDays = $view === 'day' ? 1 : ($view === 'week' ? 7 : $rangeStart->daysInMonth);
        $timeDays = [];
        if ($view !== 'month') {
            for ($offset = 0; $offset < $periodDays; $offset++) {
                $date = $rangeStart->copy()->addDays($offset);
                $dayRows = collect($rows)->filter(fn (array $row): bool => $row['date'] === $date->format('d M Y'))->values();
                $timeDays[] = [
                    'date' => $date->toDateString(),
                    'label' => $date->format('D'),
                    'number' => $date->format('j'),
                    'today' => $date->isToday(),
                    'blocks' => $dayRows->map(function (array $row) use ($activities, $rangeStart): array {
                        $activity = $activities->firstWhere('id', $row['id']);
                        $startMinutes = (($activity?->starts_at?->hour ?: 6) * 60) + ($activity?->starts_at?->minute ?: 0);
                        $endMinutes = (($activity?->ends_at?->hour ?: ($activity?->starts_at?->hour ?: 6)) * 60) + ($activity?->ends_at?->minute ?: (($activity?->starts_at?->minute ?: 0) + 60));
                        $windowStart = 6 * 60;
                        $windowMinutes = 16 * 60;
                        $top = max(0, min(100, ($startMinutes - $windowStart) / $windowMinutes * 100));
                        $height = max(5, min(100 - $top, max(60, $endMinutes - $startMinutes) / $windowMinutes * 100));

                        return $row + ['top' => round($top, 2), 'height' => round($height, 2)];
                    })->all(),
                ];
            }
        }

        $visibleBookings = $this->bookingVisibility->apply(Booking::query(), $user)->whereNull('archived_at');
        $bookingOptions = (clone $visibleBookings)->whereHas('activities', fn ($query) => $query->whereBetween('starts_at', [$rangeStart, $rangeEnd]))->orderBy('reference')->get(['id', 'reference', 'group_name']);
        $guideOptions = Guide::query()->orderBy('name')->get(['id', 'name']);
        $vehicleOptions = Vehicle::query()->orderBy('registration_number')->get(['id', 'registration_number']);
        $typeOptions = $allActivities->pluck('type')->filter()->unique()->sort()->values()->all();
        $statusOptions = ['planned' => 'Planned', 'confirmed' => 'Confirmed', 'in_progress' => 'In progress', 'completed' => 'Completed', 'delayed' => 'Delayed', 'cancelled' => 'Cancelled'];
        $filteredConflicts = collect($rows)->where('conflict', true)->count();
        $needsAssignment = $activities->filter(fn (Activity $activity): bool => ! $hasCompleteAssignment($activity))->count();
        $arrivals = $activities->filter(fn (Activity $activity): bool => (bool) $activity->booking?->arrival_date?->betweenIncluded($rangeStart->toDateString(), $rangeEnd->toDateString()))->count();
        $departures = $activities->filter(fn (Activity $activity): bool => (bool) $activity->booking?->departure_date?->betweenIncluded($rangeStart->toDateString(), $rangeEnd->toDateString()))->count();
        $baseQuery = $request->except(['date', 'view']);
        $periodUrl = function (Carbon $date, ?string $targetView = null) use ($baseQuery): string {
            $params = array_merge($baseQuery, ['date' => $date->toDateString()]);
            if ($targetView) {
                $params['view'] = $targetView;
            }

            return route('planner.calendar').'?'.http_build_query($params);
        };

        return [
            'activities' => $rows,
            'timeDays' => $timeDays,
            'view' => $view,
            'focusDate' => $focusDate,
            'rangeStart' => $rangeStart,
            'rangeEnd' => $rangeEnd,
            'periodLabel' => $view === 'day'
                ? $focusDate->format('l, j F Y')
                : ($view === 'month' ? $focusDate->format('F Y') : $rangeStart->format('j M').' – '.$rangeEnd->format('j M Y')),
            'hours' => range(6, 22),
            'kpis' => [
                ['label' => 'Scheduled activities', 'value' => count($rows), 'tone' => 'blue'],
                ['label' => 'Confirmed activities', 'value' => collect($rows)->whereIn('status', ['Confirmed', 'Completed'])->count(), 'tone' => 'green'],
                ['label' => 'Needs assignment', 'value' => $needsAssignment, 'tone' => 'amber'],
                ['label' => 'Arrivals', 'value' => $arrivals, 'tone' => 'blue'],
                ['label' => 'Departures', 'value' => $departures, 'tone' => 'green'],
                ['label' => 'Resource conflicts', 'value' => $filteredConflicts, 'tone' => 'red'],
            ],
            'typeOptions' => $typeOptions,
            'statusOptions' => $statusOptions,
            'guideOptions' => $guideOptions,
            'vehicleOptions' => $vehicleOptions,
            'bookingOptions' => $bookingOptions,
            'navigation' => [
                'previous' => $periodUrl($view === 'month' ? $focusDate->copy()->subMonth() : ($view === 'day' ? $focusDate->copy()->subDay() : $focusDate->copy()->subWeek())),
                'next' => $periodUrl($view === 'month' ? $focusDate->copy()->addMonth() : ($view === 'day' ? $focusDate->copy()->addDay() : $focusDate->copy()->addWeek())),
                'today' => $periodUrl(now()->startOfDay()),
                'month' => $periodUrl($focusDate, 'month'),
                'week' => $periodUrl($focusDate, 'week'),
                'day' => $periodUrl($focusDate, 'day'),
            ],
            'actions' => [
                'new_activity' => route('planner.transfers', [$focusDate->year, strtolower($focusDate->format('F'))]).'?new=1',
                'new_transfer' => route('planner.transfers', [$focusDate->year, strtolower($focusDate->format('F'))]).'?new=1',
                'assign' => route('safari-board.index', [$focusDate->year, strtolower($focusDate->format('F'))]),
                'import' => route('safari-board.index', [$focusDate->year, strtolower($focusDate->format('F'))]).'?open=import',
                'export' => route('planner.transfers.export', [$focusDate->year, strtolower($focusDate->format('F'))]),
                'can_edit' => $user->hasPermission('planner.edit'),
                'can_transfer_edit' => $user->hasPermission('transfers.edit'),
                'can_import' => $user->hasPermission('planner.import'),
                'can_export' => $user->canExport(),
            ],
            'filters' => [
                'q' => $search,
                'activity_type' => $activityType,
                'status' => $status,
                'guide' => $guideId,
                'vehicle' => $vehicleId,
                'booking' => $bookingId,
                'assignment_state' => $assignmentState,
            ],
        ];
    }

    private function bookingBoardUrl(?Booking $booking): string
    {
        if (! $booking?->plan_year || ! $booking?->plan_month) {
            return route('planner.bookings');
        }

        return route('safari-board.index', [$booking->plan_year, strtolower(date('F', mktime(0, 0, 0, (int) $booking->plan_month, 1)))]);
    }

    private function initials(string $name): string
    {
        return collect(preg_split('/\s+/', trim($name)) ?: [])->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))->take(2)->implode('') ?: 'OP';
    }

    public function exportResource(Request $request, string $format)
    {
        $type = $request->routeIs('planner.guides.export') ? 'guides' : 'vehicles';
        abort_unless(in_array($type, ['guides', 'vehicles'], true), 404);
        abort_unless(in_array($format, ['excel', 'pdf'], true), 404);

        $records = $this->resourceQuery($request, $type)
            ->orderBy($type === 'guides' ? 'name' : 'registration_number')
            ->get();
        [$headers, $rows] = $this->resourceRows($type, $records);
        $filename = $type.'-'.now()->format('Y-m-d');

        if ($format === 'excel') {
            return $this->downloadXlsx($filename.'.xlsx', $headers, $rows);
        }

        return response()
            ->view('planner.resource-export-pdf', [
                'title' => ucfirst($type).' export',
                'headers' => $headers,
                'rows' => $rows,
                'generatedAt' => now()->format('d M Y H:i'),
            ])
            ->header('Content-Disposition', 'inline; filename="'.$filename.'.pdf"');
    }

    public function importResource(Request $request)
    {
        $type = $request->routeIs('planner.guides.import') ? 'guides' : 'vehicles';
        abort_unless(in_array($type, ['guides', 'vehicles'], true), 404);

        $request->validate([
            'file' => ['required', 'file', 'extensions:xlsx,csv,txt', 'max:20480'],
        ]);

        $report = [
            'type' => $type,
            'total' => 0,
            'imported' => 0,
            'duplicates' => 0,
            'failed' => [],
            'atomic' => true,
        ];

        try {
            $prepared = [];
            $seen = [];
            foreach ($this->spreadsheetImports->rows(
                $request->file('file')->getRealPath(),
                $request->file('file')->getClientOriginalExtension(),
                fn ($header) => $this->resourceImportKey($header),
            ) as $row) {
                $report['total']++;
                try {
                    $candidate = $this->resourceImportAttributes($type, $row['data'], $row['line']);
                    $keys = $candidate['duplicate_keys'] ?? [$candidate['duplicate_key']];
                    if (array_intersect_key($seen, array_flip($keys)) !== [] || $this->resourceDuplicateExists($type, $candidate)) {
                        $report['duplicates']++;
                        continue;
                    }
                    foreach ($keys as $key) {
                        $seen[$key] = true;
                    }
                    $prepared[] = $candidate['attributes'];
                } catch (\Throwable $exception) {
                    $report['failed'][] = ['line' => $row['line'], 'reason' => $exception->getMessage()];
                }
            }

            if ($report['total'] === 0) {
                $report['failed'][] = ['line' => 1, 'reason' => 'The file does not contain any data rows.'];
            }

            if ($report['failed'] === []) {
                DB::transaction(function () use ($type, $prepared, &$report): void {
                    foreach ($prepared as $attributes) {
                        $type === 'guides' ? Guide::create($attributes) : Vehicle::create($attributes);
                        $report['imported']++;
                    }
                });
            }
        } catch (\Throwable $exception) {
            Log::error('planner.import.failed', [
                'resource' => $type,
                'user_id' => $request->user()?->id,
                'filename' => $request->file('file')?->getClientOriginalName(),
                'exception' => $exception->getMessage(),
            ]);
            $report['failed'][] = ['line' => 0, 'reason' => $exception->getMessage() ?: 'The file could not be imported.'];
        }

        if ($report['failed'] !== []) {
            $report['imported'] = 0;
        }
        Log::info('planner.import.completed', [
            'resource' => $type,
            'user_id' => $request->user()?->id,
            'filename' => $request->file('file')?->getClientOriginalName(),
            'total' => $report['total'],
            'imported' => $report['imported'],
            'duplicates' => $report['duplicates'],
            'failed' => count($report['failed']),
        ]);

        return redirect()
            ->route('planner.'.$type, $request->only(['q', 'status', 'per_page']))
            ->with('import_report', $report)
            ->with('success', $report['failed'] === []
                ? 'Import completed successfully.'
                : 'Import was not applied because one or more rows failed validation.');
    }

    private function resourceImportKey($header): string
    {
        $normalized = $this->normalizeImportHeader((string) $header);

        return match ($normalized) {
            'registration', 'registration_no', 'vehicle_registration', 'vehicle_number' => 'registration_number',
            'license', 'license_no', 'licence_number' => 'license_number',
            'license_expiry_date', 'licence_expiry' => 'license_expiry',
            'last_service', 'last_service_date' => 'last_service_at',
            'next_service', 'next_service_date' => 'next_service_at',
            default => $normalized,
        };
    }

    private function resourceImportAttributes(string $type, array $row, int $line): array
    {
        if ($type === 'guides') {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                throw new \RuntimeException('Name is required.');
            }
            $email = trim((string) ($row['email'] ?? '')) ?: null;
            if ($email !== null && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException('Email must be a valid email address.');
            }
            $status = strtolower(str_replace(['-', ' '], '_', trim((string) ($row['status'] ?? 'available')))) ?: 'available';
            if (! in_array($status, ['available', 'on_trip', 'on_leave', 'unavailable'], true)) {
                throw new \RuntimeException('Status is invalid: '.$status.'.');
            }
            $attributes = [
                'name' => $name,
                'email' => $email,
                'phone' => trim((string) ($row['phone'] ?? '')) ?: null,
                'license_number' => trim((string) ($row['license_number'] ?? '')) ?: null,
                'status' => $status,
                'languages' => array_values(array_filter(array_map('trim', explode(',', (string) ($row['languages'] ?? ''))))),
                'license_expiry' => $this->importDate($this->excelSerialDate($row['license_expiry'] ?? null)),
            ];
            $duplicateKeys = ['guide:name:'.strtolower($attributes['name'].'|'.($attributes['phone'] ?: ''))];
            if ($attributes['license_number']) {
                $duplicateKeys[] = 'guide:license:'.strtolower($attributes['license_number']);
            }
            if ($attributes['email']) {
                $duplicateKeys[] = 'guide:email:'.strtolower($attributes['email']);
            }

            return ['attributes' => $attributes, 'duplicate_keys' => $duplicateKeys];
        }

        $registration = trim((string) ($row['registration_number'] ?? ''));
        if ($registration === '') {
            throw new \RuntimeException('Registration number is required.');
        }
        if (! is_numeric($row['capacity'] ?? null) || (int) $row['capacity'] < 1 || (int) $row['capacity'] > 100) {
            throw new \RuntimeException('Capacity must be a whole number between 1 and 100.');
        }
        $status = strtolower(str_replace(['-', ' '], '_', trim((string) ($row['status'] ?? 'available')))) ?: 'available';
        if (! in_array($status, ['available', 'in_service', 'on_trip', 'on_transfer', 'maintenance', 'unavailable'], true)) {
            throw new \RuntimeException('Status is invalid: '.$status.'.');
        }
        $attributes = [
            'registration_number' => $registration,
            'type' => trim((string) ($row['type'] ?? '4x4 safari vehicle')) ?: '4x4 safari vehicle',
            'capacity' => (int) $row['capacity'],
            'status' => $status,
            'last_service_at' => $this->importDate($this->excelSerialDate($row['last_service_at'] ?? null)),
            'next_service_at' => $this->importDate($this->excelSerialDate($row['next_service_at'] ?? null)),
        ];

        return ['attributes' => $attributes, 'duplicate_keys' => ['vehicle:'.strtolower($registration)]];
    }

    private function resourceDuplicateExists(string $type, array $candidate): bool
    {
        $attributes = $candidate['attributes'];
        if ($type === 'vehicles') {
            return Vehicle::whereRaw('LOWER(registration_number) = ?', [strtolower($attributes['registration_number'])])->exists();
        }

        $query = Guide::query()->where(function ($builder) use ($attributes): void {
            if ($attributes['license_number']) {
                $builder->whereRaw('LOWER(license_number) = ?', [strtolower($attributes['license_number'])]);
            }
            if ($attributes['email']) {
                $builder->orWhereRaw('LOWER(email) = ?', [strtolower($attributes['email'])]);
            }
            $builder->orWhere(function ($nameQuery) use ($attributes): void {
                $nameQuery->whereRaw('LOWER(name) = ?', [strtolower($attributes['name'])]);
                if ($attributes['phone']) {
                    $nameQuery->where('phone', $attributes['phone']);
                }
            });
        });

        return $query->exists();
    }

    public function resourceImportTemplate(Request $request)
    {
        $type = $request->routeIs('planner.guides.template') ? 'guides' : 'vehicles';
        $headers = $type === 'guides'
            ? ['Name', 'Email', 'Phone', 'Languages', 'License number', 'License expiry', 'Status']
            : ['Registration number', 'Type', 'Capacity', 'Status', 'Last service', 'Next service'];
        $rows = $type === 'guides'
            ? [['Example Guide', 'guide@example.com', '+255 700 000 000', 'English, French', 'TS-G-001', '2027-12-31', 'available']]
            : [['T 001 ABC', '4x4 safari vehicle', 7, 'available', '2026-01-15', '2026-07-15']];

        return $this->downloadXlsx($type.'-import-template.xlsx', $headers, $rows);
    }

    private function resourceIndex(Request $request, string $type)
    {
        $query = $this->resourceQuery($request, $type);
        $perPage = $this->resourcePerPage($request);
        $records = $query
            ->orderBy($type === 'guides' ? 'name' : 'registration_number')
            ->paginate($perPage)
            ->withQueryString();
        $summaryQuery = $this->resourceQuery($request, $type);
        $statusOptions = ($type === 'guides' ? Guide::query() : Vehicle::query())
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        $stats = [
            'total' => (clone $summaryQuery)->count(),
            'available' => (clone $summaryQuery)->where('status', 'available')->count(),
            'secondary' => $type === 'guides'
                ? (clone $summaryQuery)->where('status', 'on_trip')->count()
                : (int) (clone $summaryQuery)->sum('capacity'),
            'secondaryLabel' => $type === 'guides' ? 'on trip' : 'guest seats',
        ];

        return view('planner.resources', [
            'user' => auth()->user(),
            'resourceType' => $type,
            'guides' => $type === 'guides' ? $records : collect(),
            'vehicles' => $type === 'vehicles' ? $records : collect(),
            'records' => $records,
            'filters' => [
                'q' => (string) $request->input('q', ''),
                'status' => (string) $request->input('status', ''),
                'per_page' => $perPage,
            ],
            'statusOptions' => $statusOptions,
            'stats' => $stats,
        ]);
    }

    private function resourceQuery(Request $request, string $type)
    {
        $query = $type === 'guides' ? Guide::query() : Vehicle::query();
        $search = trim((string) $request->input('q', ''));
        $status = trim((string) $request->input('status', ''));

        if ($search !== '') {
            $query->where(function ($builder) use ($search, $type) {
                $like = '%'.$search.'%';

                if ($type === 'guides') {
                    $builder->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhere('license_number', 'like', $like)
                        ->orWhere('languages', 'like', $like);
                } else {
                    $builder->where('registration_number', 'like', $like)
                        ->orWhere('type', 'like', $like)
                        ->orWhere('capacity', 'like', $like)
                        ->orWhere('status', 'like', $like);
                }
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        return $query;
    }

    private function resourcePerPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', 10);

        return in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;
    }

    private function resourceRows(string $type, $records): array
    {
        if ($type === 'guides') {
            return [
                ['Name', 'Email', 'Phone', 'Languages', 'License number', 'License expiry', 'Status'],
                $records->map(fn (Guide $guide) => [
                    $guide->name,
                    $guide->email ?: '',
                    $guide->phone ?: '',
                    implode(', ', $guide->languages ?: []),
                    $guide->license_number ?: '',
                    $guide->license_expiry?->format('Y-m-d') ?: '',
                    ucfirst(str_replace('_', ' ', $guide->status)),
                ])->all(),
            ];
        }

        return [
            ['Registration number', 'Type', 'Capacity', 'Status', 'Last service', 'Next service'],
            $records->map(fn (Vehicle $vehicle) => [
                $vehicle->registration_number,
                $vehicle->type,
                $vehicle->capacity,
                ucfirst(str_replace('_', ' ', $vehicle->status)),
                $vehicle->last_service_at?->format('Y-m-d') ?: '',
                $vehicle->next_service_at?->format('Y-m-d') ?: '',
            ])->all(),
        ];
    }

    private function downloadXlsx(string $filename, array $headers, array $rows)
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'safari_export_');
        $zip = new \ZipArchive;

        if ($tempPath === false || $zip->open($tempPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Excel export is unavailable.');
        }

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Export" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');

        $sheetRows = [];
        foreach (array_merge([$headers], $rows) as $rowNumber => $row) {
            $cells = [];
            foreach (array_values($row) as $columnNumber => $value) {
                $cellReference = $this->excelColumnName($columnNumber + 1).($rowNumber + 1);
                if (is_int($value) || is_float($value)) {
                    $cells[] = '<c r="'.$cellReference.'"><v>'.$value.'</v></c>';
                } else {
                    $escaped = htmlspecialchars((string) $value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
                    $cells[] = '<c r="'.$cellReference.'" t="inlineStr"><is><t xml:space="preserve">'.$escaped.'</t></is></c>';
                }
            }
            $sheetRows[] = '<row r="'.($rowNumber + 1).'">'.implode('', $cells).'</row>';
        }

        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.implode('', $sheetRows).'</sheetData></worksheet>');
        $zip->close();

        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function excelColumnName(int $number): string
    {
        $name = '';
        while ($number > 0) {
            $remainder = ($number - 1) % 26;
            $name = chr(65 + $remainder).$name;
            $number = intdiv($number - 1, 26);
        }

        return $name;
    }

    private function readXlsx(string $path): array
    {
        $zip = new \ZipArchive;
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Invalid workbook.');
        }

        $sharedStrings = [];
        if (($sharedXml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $shared = simplexml_load_string($sharedXml);
            $namespace = $shared->getDocNamespaces(true)[''] ?? 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
            $shared->registerXPathNamespace('x', $namespace);
            foreach ($shared->xpath('//x:si') as $item) {
                $sharedStrings[] = implode('', array_map('strval', $item->xpath('.//x:t')));
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        if ($sheetXml === false) {
            throw new \RuntimeException('Workbook has no first worksheet.');
        }

        $sheet = simplexml_load_string($sheetXml);
        $namespace = $sheet->getDocNamespaces(true)[''] ?? 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $sheet->registerXPathNamespace('x', $namespace);
        $rawRows = [];

        foreach ($sheet->xpath('//x:sheetData/x:row') as $row) {
            $values = [];
            foreach ($row->xpath('./x:c') as $cell) {
                $reference = (string) $cell['r'];
                preg_match('/^[A-Z]+/', $reference, $match);
                $column = $this->excelColumnNumber($match[0] ?? 'A');
                $value = (string) $cell->v;
                $cellType = (string) $cell['t'];

                if ($cellType === 's') {
                    $value = $sharedStrings[(int) $value] ?? '';
                } elseif ($cellType === 'inlineStr') {
                    $texts = $cell->xpath('./x:is//x:t');
                    $value = implode('', array_map('strval', $texts));
                }

                $values[$column] = $value;
            }

            if ($values === []) {
                continue;
            }

            $rowValues = array_fill(0, max(array_keys($values)) + 1, '');
            foreach ($values as $column => $value) {
                $rowValues[$column] = $value;
            }
            $rawRows[] = $rowValues;
        }

        $headers = array_map(fn ($header) => $this->normalizeImportHeader($header), array_shift($rawRows) ?: []);

        return array_values(array_filter(array_map(function ($row) use ($headers) {
            $row = array_pad($row, count($headers), '');

            return array_combine($headers, array_slice($row, 0, count($headers)));
        }, $rawRows), fn ($row) => array_filter($row, fn ($value) => trim((string) $value) !== '') !== []));
    }

    private function excelColumnNumber(string $letters): int
    {
        $number = 0;
        foreach (str_split($letters) as $letter) {
            $number = ($number * 26) + ord($letter) - 65 + 1;
        }

        return $number - 1;
    }

    private function normalizeImportHeader(string $header): string
    {
        return trim(strtolower((string) preg_replace('/[^a-z0-9]+/i', '_', trim($header))), '_');
    }

    private function upsertImportedResources(string $type, array $rows): int
    {
        $count = 0;

        foreach ($rows as $row) {
            if ($type === 'guides') {
                $name = trim((string) ($row['name'] ?? ''));
                if ($name === '') {
                    continue;
                }

                $attributes = [
                    'name' => $name,
                    'email' => trim((string) ($row['email'] ?? '')) ?: null,
                    'phone' => trim((string) ($row['phone'] ?? '')) ?: null,
                    'license_number' => trim((string) ($row['license_number'] ?? '')) ?: null,
                    'status' => trim((string) ($row['status'] ?? 'available')) ?: 'available',
                    'languages' => array_values(array_filter(array_map('trim', explode(',', (string) ($row['languages'] ?? ''))))),
                    'license_expiry' => $this->importDate($this->excelSerialDate($row['license_expiry'] ?? null)),
                ];
                $key = $attributes['license_number'] ? ['license_number' => $attributes['license_number']] : ($attributes['email'] ? ['email' => $attributes['email']] : null);
                $key ? Guide::updateOrCreate($key, $attributes) : Guide::create($attributes);
            } else {
                $registration = trim((string) ($row['registration_number'] ?? ''));
                if ($registration === '' || ! is_numeric($row['capacity'] ?? null)) {
                    continue;
                }

                Vehicle::updateOrCreate(
                    ['registration_number' => $registration],
                    [
                        'type' => trim((string) ($row['type'] ?? '4x4 safari vehicle')) ?: '4x4 safari vehicle',
                        'capacity' => (int) $row['capacity'],
                        'status' => trim((string) ($row['status'] ?? 'available')) ?: 'available',
                        'last_service_at' => $this->importDate($this->excelSerialDate($row['last_service_at'] ?? null)),
                        'next_service_at' => $this->importDate($this->excelSerialDate($row['next_service_at'] ?? null)),
                    ],
                );
            }
            $count++;
        }

        return $count;
    }

    public function storeGuide(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'license_number' => ['nullable', 'string', 'max:120'],
            'status' => ['required', 'in:available,on_trip,on_leave,unavailable'],
            'languages' => ['nullable'],
            'license_expiry' => ['nullable', 'date'],
        ]);

        $languages = $validated['languages'] ?? [];
        $languages = is_string($languages) ? explode(',', $languages) : (array) $languages;
        $validated['languages'] = array_values(array_filter(array_map('trim', $languages)));

        Guide::create($validated);

        return back()->with('success', 'Guide added successfully.');
    }

    public function storeVehicle(Request $request)
    {
        $validated = $request->validate([
            'registration_number' => ['required', 'string', 'max:40', 'unique:vehicles,registration_number'],
            'type' => ['required', 'string', 'max:120'],
            'capacity' => ['required', 'integer', 'min:1', 'max:20'],
            'status' => ['required', 'in:available,in_service,maintenance,unavailable'],
            'last_service_at' => ['nullable', 'date'],
            'next_service_at' => ['nullable', 'date'],
        ]);

        Vehicle::create($validated);

        return back()->with('success', 'Vehicle added successfully.');
    }

    public function updateGuide(Request $request, Guide $guide)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'license_number' => ['nullable', 'string', 'max:120'],
            'status' => ['required', 'in:available,on_trip,on_leave,unavailable'],
            'languages' => ['nullable'],
            'license_expiry' => ['nullable', 'date'],
        ]);

        $languages = $validated['languages'] ?? [];
        $validated['languages'] = array_values(array_filter(array_map('trim', is_string($languages) ? explode(',', $languages) : (array) $languages)));
        $guide->update($validated);

        return back()->with('success', 'Guide updated successfully.');
    }

    public function destroyGuide(Guide $guide)
    {
        $guide->delete();

        return back()->with('success', 'Guide removed successfully.');
    }

    public function updateVehicle(Request $request, Vehicle $vehicle)
    {
        $validated = $request->validate([
            'registration_number' => ['required', 'string', 'max:40', 'unique:vehicles,registration_number,'.$vehicle->id],
            'type' => ['required', 'string', 'max:120'],
            'capacity' => ['required', 'integer', 'min:1', 'max:20'],
            'status' => ['required', 'in:available,in_service,maintenance,unavailable'],
            'last_service_at' => ['nullable', 'date'],
            'next_service_at' => ['nullable', 'date'],
        ]);

        $vehicle->update($validated);

        return back()->with('success', 'Vehicle updated successfully.');
    }

    public function destroyVehicle(Vehicle $vehicle)
    {
        $vehicle->delete();

        return back()->with('success', 'Vehicle removed successfully.');
    }

    public function storeTransfer(Request $request)
    {
        $validated = $request->validate([
            'group_name' => ['required', 'string', 'max:255'],
            'pax' => ['required', 'integer', 'min:1', 'max:999'],
            'arrival_date' => ['required', 'date'],
            'departure_date' => ['nullable', 'date', 'after_or_equal:arrival_date'],
            'pickup_location' => ['required', 'string', 'max:255'],
            'dropoff_location' => ['required', 'string', 'max:255'],
            'pickup_time' => ['required', 'date_format:H:i'],
            'flight_number' => ['nullable', 'string', 'max:40'],
            'guide_id' => ['nullable', 'exists:guides,id'],
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'contact_number' => ['nullable', 'string', 'max:80'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
        ]);

        DB::transaction(function () use ($validated, $request) {
            $reference = 'TR-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
            while (Booking::where('reference', $reference)->exists()) {
                $reference = 'TR-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
            }

            $booking = Booking::create([
                'reference' => $reference,
                'group_name' => $validated['group_name'],
                'pax' => $validated['pax'],
                'company_id' => $request->user()->company_id,
                'arrival_date' => $validated['arrival_date'],
                'departure_date' => $validated['departure_date'] ?? $validated['arrival_date'],
                'status' => 'confirmed',
                'payment_status' => 'pending',
                'notes' => $validated['notes'] ?? null,
            ]);

            $start = Carbon::createFromFormat('Y-m-d H:i', $validated['arrival_date'].' '.$validated['pickup_time']);
            $pickup = Location::firstOrCreate(['name' => $validated['pickup_location']], ['type' => 'transfer_point']);
            $dropoff = Location::firstOrCreate(['name' => $validated['dropoff_location']], ['type' => 'transfer_point']);

            $activity = Activity::create([
                'booking_id' => $booking->id,
                'type' => 'transfer',
                'title' => 'Transfer · '.$validated['group_name'],
                'start_location_id' => $pickup->id,
                'end_location_id' => $dropoff->id,
                'starts_at' => $start,
                'ends_at' => $start->copy()->addMinutes(90),
                'status' => 'planned',
                'flight_number' => $validated['flight_number'] ?? null,
                'contact_number' => $validated['contact_number'] ?? null,
                'supplier_id' => $validated['supplier_id'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            if (! empty($validated['guide_id']) || ! empty($validated['vehicle_id'])) {
                ActivityAssignment::create([
                    'activity_id' => $activity->id,
                    'guide_id' => $validated['guide_id'] ?? null,
                    'vehicle_id' => $validated['vehicle_id'] ?? null,
                    'assignment_status' => 'assigned',
                ]);
            }
        });

        return redirect()->route('planner.transfers')->with('success', 'Transfer scheduled successfully.');
    }

    public function updateTransfer(Request $request, Activity $transfer)
    {
        $this->ensureTransferWriteAccess($request, $transfer);
        $validated = $this->validateTransfer($request);

        DB::transaction(function () use ($validated, $transfer): void {
            $transfer->booking?->update([
                'group_name' => $validated['group_name'],
                'pax' => $validated['pax'],
                'arrival_date' => $validated['arrival_date'],
                'departure_date' => $validated['departure_date'] ?: $validated['arrival_date'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $start = Carbon::createFromFormat('Y-m-d H:i', $validated['arrival_date'].' '.$validated['pickup_time']);
            $transfer->update([
                'starts_at' => $start,
                'ends_at' => $start->copy()->addMinutes(90),
                'start_location_id' => Location::firstOrCreate(['name' => $validated['pickup_location']], ['type' => 'transfer_point'])->id,
                'end_location_id' => Location::firstOrCreate(['name' => $validated['dropoff_location']], ['type' => 'transfer_point'])->id,
                'flight_number' => $validated['flight_number'] ?? null,
                'contact_number' => $validated['contact_number'] ?? null,
                'supplier_id' => $validated['supplier_id'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => $validated['status'],
            ]);

            $this->syncTransferAssignment($transfer, $validated['guide_id'] ?? null, $validated['vehicle_id'] ?? null);
        });

        return back()->with('success', 'Transfer updated successfully.');
    }

    public function duplicateTransfer(Request $request, Activity $transfer)
    {
        $this->ensureTransferWriteAccess($request, $transfer);
        $copy = $transfer->replicate(['created_at', 'updated_at']);
        $copy->title = 'Transfer · '.($transfer->booking?->group_name ?: 'Duplicate');
        $copy->status = 'planned';
        $copy->save();

        foreach ($transfer->assignments as $assignment) {
            $copy->assignments()->create($assignment->only(['guide_id', 'vehicle_id', 'translator_id', 'assignment_status']));
        }

        return back()->with('success', 'Transfer duplicated as a planned row.');
    }

    public function cancelTransfer(Request $request, Activity $transfer)
    {
        $this->ensureTransferWriteAccess($request, $transfer);
        $transfer->update(['status' => 'cancelled']);

        return back()->with('success', 'Transfer cancelled.');
    }

    public function archiveTransfer(Request $request, Activity $transfer)
    {
        $this->ensureTransferWriteAccess($request, $transfer);
        $transfer->update(['status' => 'archived']);

        return back()->with('success', 'Transfer archived.');
    }

    public function exportTransfers(Request $request, int $year, string $month)
    {
        abort_unless($request->user()->canExport(), 403);
        $monthNumber = $this->plannerMonthNumber($month);
        abort_unless($monthNumber !== null, 404);
        $records = $this->transferQuery($request, $request->user(), $year, $monthNumber)->get();

        return response()->streamDownload(function () use ($records): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['Date', 'Pickup Time', 'Group / Guest Name', 'Driver / Guide', 'Contact Number', 'Supplier', 'Vehicle', 'Start Location', 'End Location', 'Notes', 'Status']);
            foreach ($records as $transfer) {
                $assignment = $transfer->assignments->first();
                fputcsv($handle, [
                    $transfer->starts_at?->format('d/m/Y'),
                    $transfer->starts_at?->format('H:i'),
                    $transfer->booking?->group_name,
                    $assignment?->guide?->name,
                    $transfer->contact_number ?: $transfer->booking?->company?->contact_phone,
                    $transfer->supplier?->name,
                    $assignment?->vehicle?->registration_number,
                    $transfer->startLocation?->name,
                    $transfer->endLocation?->name,
                    trim(implode(' · ', array_filter([$transfer->flight_number, $transfer->notes]))),
                    $transfer->status,
                ]);
            }
            fclose($handle);
        }, 'transfers-'.$year.'-'.strtolower(self::MONTHS[$monthNumber]).'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function legacyImportTransfers(Request $request, ?int $year = null, ?string $month = null)
    {
        abort_unless($request->user()->hasPermission('transfers.edit'), 403);
        $validated = $request->validate(['file' => ['required', 'file', 'extensions:xlsx,csv,txt', 'max:5120']]);
        $rows = $this->readTransferImportRows($validated['file']->getRealPath(), $validated['file']->getClientOriginalExtension());
        $created = 0;
        foreach ($rows as $row) {
            if (! is_array($row) || trim((string) ($row['group_name'] ?? '')) === '') {
                continue;
            }

            $date = $this->transferImportDate($row['date'] ?? '', $year ?: now()->year);
            $time = trim((string) ($row['pickup_time'] ?? '')) ?: '08:00';
            if (! $date || ! preg_match('/^\d{1,2}:\d{2}$/', $time)) {
                continue;
            }

            $supplierName = trim((string) ($row['supplier'] ?? ''));
            $supplierId = $supplierName !== ''
                ? Supplier::firstOrCreate(['name' => $supplierName], ['type' => 'transport', 'status' => 'active'])->id
                : null;
            $guideName = trim((string) ($row['driver_guide'] ?? ''));
            $guideId = $guideName !== '' ? Guide::whereRaw('LOWER(name) = ?', [strtolower($guideName)])->value('id') : null;
            $vehicleName = trim((string) ($row['vehicle'] ?? ''));
            $vehicleId = $vehicleName !== '' ? Vehicle::whereRaw('LOWER(registration_number) = ?', [strtolower($vehicleName)])->value('id') : null;
            $pickup = Location::firstOrCreate(['name' => trim((string) ($row['start_location'] ?? 'Pickup location'))], ['type' => 'transfer_point']);
            $dropoff = Location::firstOrCreate(['name' => trim((string) ($row['end_location'] ?? 'Destination'))], ['type' => 'transfer_point']);
            $booking = Booking::create([
                'reference' => $this->nextTransferReference(),
                'group_name' => trim((string) $row['group_name']),
                'pax' => $this->paxFromGroup((string) $row['group_name']),
                'company_id' => $request->user()->company_id,
                'arrival_date' => $date,
                'departure_date' => $date,
                'status' => 'confirmed',
                'payment_status' => 'pending',
                'notes' => trim((string) ($row['notes'] ?? '')) ?: null,
            ]);
            $start = Carbon::createFromFormat('Y-m-d H:i', $date.' '.$time);
            $status = strtolower(trim((string) ($row['status'] ?? 'planned')));
            if (! in_array($status, ['planned', 'confirmed', 'in_progress', 'completed', 'delayed', 'cancelled', 'archived'], true)) {
                $status = 'planned';
            }
            $transfer = $booking->activities()->create([
                'type' => 'transfer',
                'title' => 'Transfer · '.$booking->group_name,
                'supplier_id' => $supplierId,
                'start_location_id' => $pickup->id,
                'end_location_id' => $dropoff->id,
                'starts_at' => $start,
                'ends_at' => $start->copy()->addMinutes(90),
                'status' => $status,
                'contact_number' => trim((string) ($row['contact_number'] ?? '')) ?: null,
                'flight_number' => trim((string) ($row['notes'] ?? '')) ?: null,
                'notes' => trim((string) ($row['notes'] ?? '')) ?: null,
            ]);
            if ($guideId || $vehicleId) {
                $transfer->assignments()->create(['guide_id' => $guideId, 'vehicle_id' => $vehicleId, 'assignment_status' => 'assigned']);
            }
            $created++;
        }

        return back()->with('success', $created.' transfer rows imported.');
    }

    public function importTransfers(Request $request, ?int $year = null, ?string $month = null)
    {
        abort_unless($request->user()->hasPermission('transfers.edit'), 403);
        $validated = $request->validate(['file' => ['required', 'file', 'extensions:xlsx,csv,txt', 'max:20480']]);
        $report = ['type' => 'transfers', 'total' => 0, 'imported' => 0, 'duplicates' => 0, 'failed' => [], 'atomic' => true];
        $prepared = [];
        $seen = [];

        try {
            foreach ($this->spreadsheetImports->rows(
                $validated['file']->getRealPath(),
                $validated['file']->getClientOriginalExtension(),
                fn ($header) => $this->transferImportKey($header),
            ) as $row) {
                $report['total']++;
                try {
                    $candidate = $this->transferImportCandidate($row['data'], $row['line'], $year ?: now()->year);
                    $existing = Activity::query()
                        ->where('type', 'transfer')
                        ->where('starts_at', $candidate['starts_at'])
                        ->whereHas('booking', fn ($query) => $query->where('group_name', $candidate['group_name'])->whereDate('arrival_date', $candidate['date']))
                        ->exists();
                    if (isset($seen[$candidate['duplicate_key']]) || $existing) {
                        $report['duplicates']++;
                        continue;
                    }
                    $seen[$candidate['duplicate_key']] = true;
                    $prepared[] = $candidate;
                } catch (\Throwable $exception) {
                    $report['failed'][] = ['line' => $row['line'], 'reason' => $exception->getMessage()];
                }
            }

            if ($report['total'] === 0) {
                $report['failed'][] = ['line' => 1, 'reason' => 'The file does not contain any data rows.'];
            }

            if ($report['failed'] === []) {
                DB::transaction(function () use ($prepared, $request, &$report): void {
                    foreach ($prepared as $candidate) {
                        $supplier = $candidate['supplier'] !== ''
                            ? Supplier::firstOrCreate(['name' => $candidate['supplier']], ['type' => 'transport', 'status' => 'active'])
                            : null;
                        $pickup = Location::firstOrCreate(['name' => $candidate['start_location']], ['type' => 'transfer_point']);
                        $dropoff = Location::firstOrCreate(['name' => $candidate['end_location']], ['type' => 'transfer_point']);
                        $booking = Booking::create([
                            'reference' => $this->nextTransferReference(),
                            'group_name' => $candidate['group_name'],
                            'pax' => $candidate['pax'],
                            'company_id' => $request->user()->company_id,
                            'arrival_date' => $candidate['date'],
                            'departure_date' => $candidate['date'],
                            'status' => 'confirmed',
                            'payment_status' => 'pending',
                            'notes' => $candidate['notes'] ?: null,
                        ]);
                        $transfer = $booking->activities()->create([
                            'type' => 'transfer',
                            'title' => 'Transfer - '.$booking->group_name,
                            'supplier_id' => $supplier?->id,
                            'start_location_id' => $pickup->id,
                            'end_location_id' => $dropoff->id,
                            'starts_at' => $candidate['starts_at'],
                            'ends_at' => $candidate['starts_at']->copy()->addMinutes(90),
                            'status' => $candidate['status'],
                            'contact_number' => $candidate['contact_number'] ?: null,
                            'flight_number' => $candidate['notes'] ?: null,
                            'notes' => $candidate['notes'] ?: null,
                        ]);
                        if ($candidate['guide_id'] || $candidate['vehicle_id']) {
                            $transfer->assignments()->create([
                                'guide_id' => $candidate['guide_id'],
                                'vehicle_id' => $candidate['vehicle_id'],
                                'assignment_status' => 'assigned',
                            ]);
                        }
                        $report['imported']++;
                    }
                });
            }
        } catch (\Throwable $exception) {
            Log::error('planner.transfers.import.failed', [
                'user_id' => $request->user()?->id,
                'filename' => $validated['file']->getClientOriginalName(),
                'exception' => $exception->getMessage(),
            ]);
            $report['failed'][] = ['line' => 0, 'reason' => $exception->getMessage() ?: 'The file could not be imported.'];
        }

        if ($report['failed'] !== []) {
            $report['imported'] = 0;
        }
        Log::info('planner.transfers.import.completed', [
            'user_id' => $request->user()?->id,
            'filename' => $validated['file']->getClientOriginalName(),
            'total' => $report['total'],
            'imported' => $report['imported'],
            'duplicates' => $report['duplicates'],
            'failed' => count($report['failed']),
        ]);

        return back()
            ->with('import_report', $report)
            ->with('success', $report['failed'] === [] ? 'Import completed successfully.' : 'Import was not applied because one or more rows failed validation.');
    }

    private function transferImportCandidate(array $row, int $line, int $defaultYear): array
    {
        if (isset($row['date']) && is_numeric($row['date'])) {
            $row['date'] = $this->excelSerialDate($row['date']);
        }
        if (isset($row['pickup_time']) && is_numeric($row['pickup_time']) && (float) $row['pickup_time'] >= 0 && (float) $row['pickup_time'] < 1) {
            $row['pickup_time'] = Carbon::create(1899, 12, 30)->startOfDay()->addSeconds((int) round((float) $row['pickup_time'] * 86400))->format('H:i');
        }
        $groupName = trim((string) ($row['group_name'] ?? ''));
        $date = $this->transferImportDate((string) ($row['date'] ?? ''), $defaultYear);
        $time = trim((string) ($row['pickup_time'] ?? ''));
        $startLocation = trim((string) ($row['start_location'] ?? ''));
        $endLocation = trim((string) ($row['end_location'] ?? ''));
        if ($groupName === '' || ! $date || $time === '' || $startLocation === '' || $endLocation === '') {
            throw new \RuntimeException('Date, Pickup Time, Group / Guest Name, Start Location and End Location are required.');
        }
        if (! preg_match('/^\d{1,2}:\d{2}$/', $time)) {
            throw new \RuntimeException('Pickup Time must use HH:MM format.');
        }
        $startsAt = Carbon::createFromFormat('Y-m-d H:i', $date.' '.str_pad($time, 5, '0', STR_PAD_LEFT));
        $status = strtolower(trim((string) ($row['status'] ?? 'planned')));
        if (! in_array($status, ['planned', 'confirmed', 'in_progress', 'completed', 'delayed', 'cancelled', 'archived'], true)) {
            throw new \RuntimeException('Status is invalid: '.($row['status'] ?? '').'.');
        }
        $guideName = trim((string) ($row['driver_guide'] ?? ''));
        $guideId = $guideName !== '' ? Guide::whereRaw('LOWER(name) = ?', [strtolower($guideName)])->value('id') : null;
        if ($guideName !== '' && ! $guideId) {
            throw new \RuntimeException('Unknown Driver / Guide: '.$guideName.'.');
        }
        $vehicleName = trim((string) ($row['vehicle'] ?? ''));
        $vehicleId = $vehicleName !== '' ? Vehicle::whereRaw('LOWER(registration_number) = ?', [strtolower($vehicleName)])->value('id') : null;
        if ($vehicleName !== '' && ! $vehicleId) {
            throw new \RuntimeException('Unknown Vehicle: '.$vehicleName.'.');
        }
        $notes = trim((string) ($row['notes'] ?? ''));

        return [
            'date' => $date,
            'starts_at' => $startsAt,
            'group_name' => $groupName,
            'pax' => $this->paxFromGroup($groupName),
            'guide_id' => $guideId,
            'vehicle_id' => $vehicleId,
            'contact_number' => trim((string) ($row['contact_number'] ?? '')),
            'supplier' => trim((string) ($row['supplier'] ?? '')),
            'start_location' => $startLocation,
            'end_location' => $endLocation,
            'notes' => $notes,
            'status' => $status,
            'duplicate_key' => implode('|', [strtolower($date), $startsAt->format('H:i'), strtolower($groupName), strtolower($startLocation), strtolower($endLocation)]),
        ];
    }

    public function transferImportTemplate(int $year, string $month)
    {
        $monthNumber = $this->plannerMonthNumber($month);
        abort_unless($monthNumber !== null, 404);
        $headers = ['Date', 'Pickup Time', 'Group / Guest Name', 'Driver / Guide', 'Contact Number', 'Supplier', 'Vehicle', 'Start Location', 'End Location', 'Notes', 'Status'];
        $rows = [[Carbon::create($year, $monthNumber, 1)->format('d/m/Y'), '08:00', 'Example Group (2 pax)', 'Example Guide', '+255 700 000 000', 'TS Transfers', 'T 001 ABC', 'Kilimanjaro Airport', 'Arusha Coffee Lodge', 'FLIGHT-KQ123 at 08:00', 'planned']];

        return $this->downloadXlsx('transfers-import-template.xlsx', $headers, $rows);
    }

    public function inviteTeammate(Request $request, AccessManagementService $access)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:admin,editor,commenter,viewer'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'timezone' => ['nullable', 'timezone'],
            'record_scope' => ['nullable', 'in:workspace,assigned,own'],
        ]);

        $result = $access->invite($request->user(), $validated);

        return redirect()->route('planner.team')->with($result['deliveryError'] ? 'error' : 'success', $result['deliveryError']
            ? 'Teammate created, but the invitation email could not be delivered.'
            : 'Teammate added successfully.');
    }

    public function uploadDocument(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'booking_id' => ['required', 'exists:bookings,id'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $file = $validated['file'];
        $path = $file->store('documents', 'public');

        Document::create([
            'booking_id' => $validated['booking_id'],
            'uploaded_by' => $request->user()->id,
            'name' => $validated['name'] ?: $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        return back()->with('success', 'Document uploaded successfully.');
    }

    private function transferBoardData(Request $request, User $user): array
    {
        $year = (int) ($request->route('year') ?: now()->year);
        $month = $this->plannerMonthNumber((string) ($request->route('month') ?: now()->format('F')));
        abort_unless($year >= 2020 && $year <= 2100 && $month !== null, 404);

        $selectedDay = null;
        if ($request->filled('day')) {
            try {
                $candidate = Carbon::parse($request->string('day')->toString());
                if ($candidate->year === $year && $candidate->month === $month) {
                    $selectedDay = $candidate->startOfDay();
                }
            } catch (\Throwable) {
                $selectedDay = null;
            }
        }

        $query = $this->transferQuery($request, $user, $year, $month, $selectedDay);
        $allVisible = (clone $query)->get();
        $transfers = $query->paginate(150)->withQueryString();
        $monthCounts = [];
        for ($index = 1; $index <= 12; $index++) {
            $monthCounts[$index] = $this->transferQuery($request, $user, $year, $index)->count();
        }

        $years = Activity::query()
            ->where('type', 'transfer')
            ->pluck('starts_at')
            ->map(fn ($date) => Carbon::parse($date)->year)
            ->unique()
            ->values()
            ->all();
        $years = collect(array_unique(array_merge($years, [now()->year, now()->year + 1, $year])))->sortDesc()->values()->all();

        return [
            'transfers' => $transfers,
            'transferStats' => [
                'total' => $allVisible->count(),
                'pax' => $allVisible->sum(fn (Activity $item) => (int) ($item->booking?->pax ?? 0)),
                'unassigned' => $allVisible->filter(fn (Activity $item) => $item->assignments->isEmpty())->count(),
                'completed' => $allVisible->where('status', 'completed')->count(),
                'delayed' => $allVisible->where('status', 'delayed')->count(),
            ],
            'year' => $year,
            'month' => $month,
            'monthName' => Carbon::create($year, $month, 1)->format('F'),
            'months' => array_combine(range(1, 12), array_map(fn (int $item) => Carbon::create()->month($item)->format('F'), range(1, 12))),
            'monthCounts' => $monthCounts,
            'years' => $years,
            'selectedDay' => $selectedDay,
            'focusDate' => $selectedDay ?: (($year === now()->year && $month === now()->month) ? now()->startOfDay() : Carbon::create($year, $month, 1)),
            'admin' => $user->hasPermission('transfers.edit'),
            'canExportTransfers' => $user->canExport(),
            'guides' => Guide::where('status', 'available')->orderBy('name')->get(),
            'vehicles' => Vehicle::where('status', 'available')->orderBy('registration_number')->get(),
            'suppliers' => Supplier::where('status', 'active')->orderBy('name')->get(),
            'transferStatusOptions' => ['planned' => 'Planned', 'confirmed' => 'Confirmed', 'in_progress' => 'In progress', 'completed' => 'Completed', 'delayed' => 'Delayed', 'cancelled' => 'Cancelled', 'archived' => 'Archived'],
        ];
    }

    private function transferQuery(Request $request, User $user, int $year, int $month, ?Carbon $selectedDay = null): \Illuminate\Database\Eloquent\Builder
    {
        $query = Activity::with(['booking.company', 'assignments.guide', 'assignments.vehicle', 'startLocation', 'endLocation', 'supplier'])
            ->where('activities.type', 'transfer')
            ->whereYear('activities.starts_at', $year)
            ->whereMonth('activities.starts_at', $month)
            ->whereHas('booking', function ($bookingQuery) use ($user): void {
                $this->bookingVisibility->apply($bookingQuery, $user)->whereNull('archived_at');
            });

        if ($selectedDay) {
            $query->whereDate('activities.starts_at', $selectedDay->toDateString());
        }

        if ($request->filled('status')) {
            $query->where('activities.status', $request->string('status')->toString());
        }

        if ($request->filled('q')) {
            $term = '%'.$request->string('q')->toString().'%';
            $query->where(function ($search) use ($term): void {
                $search->where('activities.starts_at', 'like', $term)
                    ->orWhere('activities.contact_number', 'like', $term)
                    ->orWhere('activities.flight_number', 'like', $term)
                    ->orWhere('activities.notes', 'like', $term)
                    ->orWhere('activities.status', 'like', $term)
                    ->orWhereHas('booking', fn ($booking) => $booking->where('group_name', 'like', $term)->orWhere('reference', 'like', $term))
                    ->orWhereHas('supplier', fn ($supplier) => $supplier->where('name', 'like', $term))
                    ->orWhereHas('startLocation', fn ($location) => $location->where('name', 'like', $term))
                    ->orWhereHas('endLocation', fn ($location) => $location->where('name', 'like', $term))
                    ->orWhereHas('assignments.guide', fn ($guide) => $guide->where('name', 'like', $term))
                    ->orWhereHas('assignments.vehicle', fn ($vehicle) => $vehicle->where('registration_number', 'like', $term));
            });
        }

        $direction = strtolower($request->string('direction', 'asc')->toString()) === 'desc' ? 'desc' : 'asc';
        $sort = $request->string('sort', 'starts_at')->toString();
        $sortMap = [
            'date' => 'activities.starts_at',
            'pickup_time' => 'activities.starts_at',
            'contact_number' => 'activities.contact_number',
            'status' => 'activities.status',
            'notes' => 'activities.notes',
        ];
        if ($sort === 'group_name') {
            $query->orderBy(Booking::select('group_name')->whereColumn('bookings.id', 'activities.booking_id'), $direction);
        } elseif ($sort === 'driver_guide') {
            $query->orderBy(Guide::select('name')->join('activity_assignments', 'guides.id', '=', 'activity_assignments.guide_id')->whereColumn('activity_assignments.activity_id', 'activities.id')->limit(1), $direction);
        } elseif ($sort === 'vehicle') {
            $query->orderBy(Vehicle::select('registration_number')->join('activity_assignments', 'vehicles.id', '=', 'activity_assignments.vehicle_id')->whereColumn('activity_assignments.activity_id', 'activities.id')->limit(1), $direction);
        } elseif ($sort === 'supplier') {
            $query->orderBy(Supplier::select('name')->whereColumn('suppliers.id', 'activities.supplier_id'), $direction);
        } elseif ($sort === 'start_location') {
            $query->orderBy(Location::select('name')->whereColumn('locations.id', 'activities.start_location_id'), $direction);
        } elseif ($sort === 'end_location') {
            $query->orderBy(Location::select('name')->whereColumn('locations.id', 'activities.end_location_id'), $direction);
        } else {
            $query->orderBy($sortMap[$sort] ?? 'activities.starts_at', $direction);
        }

        return $query;
    }

    private function validateTransfer(Request $request): array
    {
        return $request->validate([
            'group_name' => ['required', 'string', 'max:255'],
            'pax' => ['required', 'integer', 'min:1', 'max:999'],
            'arrival_date' => ['required', 'date'],
            'departure_date' => ['nullable', 'date', 'after_or_equal:arrival_date'],
            'pickup_location' => ['required', 'string', 'max:255'],
            'dropoff_location' => ['required', 'string', 'max:255'],
            'pickup_time' => ['required', 'date_format:H:i'],
            'flight_number' => ['nullable', 'string', 'max:40'],
            'contact_number' => ['nullable', 'string', 'max:80'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'guide_id' => ['nullable', 'exists:guides,id'],
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'in:planned,confirmed,in_progress,completed,delayed,cancelled,archived'],
        ]);
    }

    private function ensureTransferWriteAccess(Request $request, Activity $transfer): void
    {
        abort_unless($request->user()->hasPermission('transfers.edit'), 403);
        abort_unless($transfer->type === 'transfer', 404);
        abort_unless($this->bookingVisibility->apply(Booking::query()->whereKey($transfer->booking_id), $request->user())->exists(), 403);
    }

    private function syncTransferAssignment(Activity $transfer, ?int $guideId, ?int $vehicleId): void
    {
        if (! $guideId && ! $vehicleId) {
            $transfer->assignments()->delete();
            return;
        }

        $transfer->assignments()->updateOrCreate(
            ['activity_id' => $transfer->id],
            ['guide_id' => $guideId, 'vehicle_id' => $vehicleId, 'assignment_status' => 'assigned']
        );
    }

    private function plannerMonthNumber(string $month): ?int
    {
        $normalized = strtolower(trim($month));
        foreach (range(1, 12) as $number) {
            $label = strtolower(Carbon::create()->month($number)->format('F'));
            if ($normalized === $label || $normalized === substr($label, 0, 3) || (ctype_digit($normalized) && (int) $normalized === $number)) {
                return $number;
            }
        }
        return null;
    }

    private function transferImportKey($header): string
    {
        return match (strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '_', (string) $header), '_'))) {
            'date', 'transfer_date' => 'date',
            'pickup_time', 'ptime', 'p_time' => 'pickup_time',
            'group_guest_name', 'group_name', 'group' => 'group_name',
            'driver_guide', 'driver', 'guide' => 'driver_guide',
            'contact_number', 'contact', 'contacts', 'phone' => 'contact_number',
            'supplier' => 'supplier',
            'vehicle', 'vehicle_registration' => 'vehicle',
            'start_location', 'start' => 'start_location',
            'end_location', 'end' => 'end_location',
            'notes', 'flight_notes', 'operational_notes' => 'notes',
            'status' => 'status',
            default => strtolower(trim((string) $header)),
        };
    }

    private function readTransferImportRows(string $path, string $extension): array
    {
        if (strtolower($extension) === 'xlsx') {
            return array_map(function (array $row): array {
                $normalized = [];
                foreach ($row as $header => $value) {
                    $key = $this->transferImportKey($header);
                    if ($key === 'date') {
                        $normalized[$key] = $this->excelSerialDate($value);
                    } elseif ($key === 'pickup_time' && is_numeric($value) && (float) $value >= 0 && (float) $value < 1) {
                        $normalized[$key] = Carbon::create(1899, 12, 30)->startOfDay()->addSeconds((int) round((float) $value * 86400))->format('H:i');
                    } else {
                        $normalized[$key] = $value;
                    }
                }

                return $normalized;
            }, $this->readXlsx($path));
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new \RuntimeException('The uploaded transfer file could not be read.');
        }
        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            throw new \RuntimeException('The transfer file must contain a header row.');
        }
        $headers = array_map(fn ($header) => $this->transferImportKey($header), $headers);
        $rows = [];
        while (($values = fgetcsv($handle)) !== false) {
            $values = array_pad($values, count($headers), '');
            $row = array_combine($headers, array_slice($values, 0, count($headers)));
            if (is_array($row)) {
                $rows[] = $row;
            }
        }
        fclose($handle);

        return $rows;
    }

    private function excelSerialDate($value): string
    {
        if (is_numeric($value) && (float) $value > 20000) {
            return Carbon::create(1899, 12, 30)->addDays((int) $value)->format('d/m/Y');
        }

        return trim((string) $value);
    }

    private function transferImportDate(string $value, int $defaultYear): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        foreach (['d/m/Y', 'Y-m-d', 'd-M-Y', 'd/M/Y', 'd/M'] as $format) {
            try {
                $date = Carbon::createFromFormat('!'.$format, $value);
                if ($format === 'd/M') {
                    $date->year($defaultYear);
                }
                return $date->toDateString();
            } catch (\Throwable) {
            }
        }
        return $this->importDate($value);
    }

    private function paxFromGroup(string $groupName): int
    {
        if (preg_match('/\((\d+)\s*pax\)/i', $groupName, $matches)) {
            return max(1, (int) $matches[1]);
        }
        return 1;
    }

    private function nextTransferReference(): string
    {
        do {
            $reference = 'TR-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
        } while (Booking::where('reference', $reference)->exists());
        return $reference;
    }

    private function importDate($value): ?string
    {
        if (trim((string) $value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
