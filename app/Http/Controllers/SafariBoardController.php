<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Company;
use App\Models\FilterView;
use App\Models\Guide;
use App\Models\Translator;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\WorkspaceActivityNotification;
use App\Services\BookingVisibilityService;
use App\Services\SpreadsheetImportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SafariBoardController extends Controller
{
    public const MONTHS = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ];

    private const STATUSES = ['draft', 'confirmed', 'in_progress', 'completed', 'archived', 'cancelled'];

    private const PAYMENT_STATUSES = ['pending', 'deposit_paid', 'part_paid', 'paid', 'overdue'];

    public function __construct(
        private BookingVisibilityService $bookingVisibility,
        private SpreadsheetImportService $spreadsheetImports,
    )
    {
    }

    public function defaultIndex(Request $request)
    {
        $latest = Booking::query()
            ->whereNotNull('plan_year')
            ->orderByDesc('plan_year')
            ->orderByDesc('plan_month')
            ->first();
        $date = $latest ? Carbon::create($latest->plan_year, $latest->plan_month, 1) : now();

        return $this->index($request, $date->year, strtolower($date->format('F')));
    }

    public function redirectToDefault(): RedirectResponse
    {
        $latest = Booking::query()
            ->whereNotNull('plan_year')
            ->orderByDesc('plan_year')
            ->orderByDesc('plan_month')
            ->first();
        $date = $latest ? Carbon::create($latest->plan_year, $latest->plan_month, 1) : now();

        return redirect()->route('safari-board.index', [
            'year' => $date->year,
            'month' => strtolower($date->format('F')),
        ]);
    }

    public function index(Request $request, int $year, string $month)
    {
        abort_unless($year >= 2020 && $year <= 2100, 404);
        $monthNumber = $this->monthNumber($month);
        abort_unless($monthNumber !== null, 404);

        $user = $request->user();
        $this->mergeSavedView($request, $user);
        $query = $this->boardQuery($request, $user, $year, $monthNumber);
        $sort = $this->sortField($request->input('sort'));
        $direction = strtolower((string) $request->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        $records = $query->orderBy($sort, $direction)->get();
        $conflictIds = $this->conflictIds($records);
        if ($request->boolean('assignment_conflict')) {
            $records = $records->filter(fn ($record) => in_array($record->id, $conflictIds, true))->values();
        }
        if ($request->boolean('capacity_warning')) {
            $records = $records->filter(fn ($record) => $record->vehicle && (int) $record->pax > (int) $record->vehicle->capacity)->values();
        }

        $visibilityQuery = fn () => $this->visibleQuery(Booking::query(), $user);
        $monthCounts = [];
        for ($index = 1; $index <= 12; $index++) {
            $monthCounts[$index] = $visibilityQuery()
                ->where('plan_year', $year)
                ->where('plan_month', $index)
                ->whereNull('archived_at')
                ->count();
        }

        $years = Booking::query()
            ->whereNotNull('plan_year')
            ->distinct()
            ->orderByDesc('plan_year')
            ->pluck('plan_year')
            ->map(fn ($item) => (int) $item)
            ->values()
            ->all();
        $years = collect(array_unique(array_merge($years, [now()->year, now()->year + 1, $year])))
            ->sortDesc()
            ->values()
            ->all();

        return view('planner.safari-board', [
            'user' => $user,
            'admin' => $user->hasPermission('planner.edit'),
            'records' => $records,
            'conflictIds' => $conflictIds,
            'year' => $year,
            'month' => $monthNumber,
            'monthName' => self::MONTHS[$monthNumber],
            'months' => self::MONTHS,
            'monthCounts' => $monthCounts,
            'years' => $years,
            'guides' => Guide::orderBy('name')->get(),
            'vehicles' => Vehicle::orderBy('registration_number')->get(),
            'translators' => Translator::orderBy('name')->get(),
            'companies' => Company::orderBy('name')->get(),
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
            'savedViews' => FilterView::where('user_id', $user->id)->where('context', 'safari_board')->orderBy('name')->get(),
            'activeFilters' => $this->activeFilters($request),
            'filterPayload' => $this->filterPayload($request),
        ]);
    }

    public function store(Request $request, int $year, string $month): RedirectResponse
    {
        $this->ensureAdmin($request);
        $monthNumber = $this->monthNumber($month);
        abort_unless($monthNumber !== null, 404);
        $data = $this->validatedRecord($request, false, null, $year, $monthNumber);
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;
        $data['reference'] = $data['reference'] ?? $this->nextReference($year);
        $data = $this->syncGuidePhone($data);

        $record = Booking::create($data);
        $this->audit($request, $record, 'created', $record->getAttributes());

        $message = 'Planning row added to '.$record->group_name.'.';
        if ($request->input('submit_action') === 'add_another') {
            return redirect()->route('safari-board.index', [$year, strtolower(self::MONTHS[$monthNumber])])
                ->with(['success' => $message, 'open_modal' => 'planningRowModal']);
        }

        return redirect()->route('safari-board.index', [$year, strtolower(self::MONTHS[$monthNumber])])->with('success', $message);
    }

    public function update(Request $request, Booking $booking): RedirectResponse
    {
        $this->ensureAdmin($request);
        $before = $booking->only($this->recordFields());
        $data = $this->validatedRecord($request, true, $booking, (int) $booking->plan_year, (int) $booking->plan_month);
        $data['updated_by'] = $request->user()->id;
        $data = $this->syncGuidePhone($data);
        $booking->update($data);
        $this->audit($request, $booking, 'updated', ['before' => $before, 'after' => $booking->only($this->recordFields())]);

        return redirect()->route('safari-board.index', [
            'year' => $booking->plan_year,
            'month' => strtolower(self::MONTHS[(int) $booking->plan_month]),
        ])->with('success', 'Planning row updated.');
    }

    public function inlineUpdate(Request $request, Booking $booking): JsonResponse
    {
        abort_unless($request->user()->hasPermission('planner.edit'), 403);
        $field = (string) $request->input('field');
        abort_unless(in_array($field, $this->inlineFields(), true), 422);

        $rules = match ($field) {
            'plan_year' => ['required', 'integer', 'between:2020,2100'],
            'plan_month' => ['required', 'integer', 'between:1,12'],
            'group_name', 'reference' => ['required', 'string', 'max:255'],
            'pax' => ['required', 'integer', 'min:1', 'max:999'],
            'arrival_date', 'departure_date' => ['nullable', 'date'],
            'guide_id' => ['nullable', 'exists:guides,id'],
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'translator_id' => ['nullable', 'exists:translators,id'],
            'company_id' => ['nullable', 'exists:companies,id'],
            'visible_to_user_id' => ['nullable', 'exists:users,id'],
            'language' => ['nullable', 'string', 'max:80'],
            'visibility' => ['required', 'in:workspace,assigned,private'],
            'status' => ['required', 'in:'.implode(',', self::STATUSES)],
            'payment_status' => ['required', 'in:'.implode(',', self::PAYMENT_STATUSES)],
            default => ['nullable', 'string', 'max:1000'],
        };
        $validator = Validator::make([$field => $request->input('value')], [$field => $rules]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first($field)], 422);
        }

        $before = $booking->getAttribute($field);
        $value = $request->input('value');
        $value = in_array($field, ['guide_id', 'vehicle_id', 'translator_id', 'company_id', 'visible_to_user_id'], true) && $value === '' ? null : $value;
        if ($field === 'guide_id') {
            $guide = $value ? Guide::find($value) : null;
            $booking->guide_phone = $guide?->phone;
        }
        $booking->setAttribute($field, $value);
        $booking->updated_by = $request->user()->id;
        $booking->save();
        $booking->load(['guide', 'vehicle', 'translator', 'company']);
        $this->audit($request, $booking, 'inline_updated', ['field' => $field, 'before' => $before, 'after' => $value]);

        return response()->json([
            'ok' => true,
            'value' => $value,
            'display' => $this->inlineDisplay($booking, $field),
        ]);
    }

    public function duplicate(Request $request, Booking $booking): RedirectResponse
    {
        $this->ensureAdmin($request);
        $validated = $request->validate([
            'plan_year' => ['required', 'integer', 'between:2020,2100'],
            'plan_month' => ['required', 'integer', 'between:1,12'],
        ]);
        $copy = $this->duplicateRecord($booking, (int) $validated['plan_year'], (int) $validated['plan_month'], $request->user()->id);
        $this->audit($request, $copy, 'duplicated', ['source_id' => $booking->id]);

        return redirect()->route('safari-board.index', [$copy->plan_year, strtolower(self::MONTHS[(int) $copy->plan_month])])
            ->with('success', 'Planning row duplicated.');
    }

    public function copyMonth(Request $request, int $year, string $month): RedirectResponse
    {
        $this->ensureAdmin($request);
        $sourceMonth = $this->monthNumber($month);
        abort_unless($sourceMonth !== null, 404);
        $validated = $request->validate([
            'target_year' => ['required', 'integer', 'between:2020,2100'],
            'target_month' => ['required', 'integer', 'between:1,12'],
        ]);
        $targetYear = (int) $validated['target_year'];
        $targetMonth = (int) $validated['target_month'];
        abort_if($targetYear === $year && $targetMonth === $sourceMonth, 422, 'Choose a different destination month.');

        $records = Booking::where('plan_year', $year)->where('plan_month', $sourceMonth)->get();
        foreach ($records as $record) {
            $this->duplicateRecord($record, $targetYear, $targetMonth, $request->user()->id);
        }

        return redirect()->route('safari-board.index', [$targetYear, strtolower(self::MONTHS[$targetMonth])])
            ->with('success', $records->count().' planning rows copied to '.self::MONTHS[$targetMonth].' '.$targetYear.'.');
    }

    public function archiveYear(Request $request, int $year): RedirectResponse
    {
        $this->ensureAdmin($request);
        $count = Booking::where('plan_year', $year)->whereNull('archived_at')->update([
            'status' => 'archived',
            'archived_at' => now(),
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('safari-board.index', [$year, 'january'])->with('success', $count.' rows archived for '.$year.'.');
    }

    public function destroy(Request $request, Booking $booking): RedirectResponse
    {
        $this->ensureAdmin($request);
        $year = $booking->plan_year ?: now()->year;
        $month = $booking->plan_month ?: now()->month;
        $this->audit($request, $booking, 'deleted', $booking->only($this->recordFields()));
        $booking->delete();

        return redirect()->route('safari-board.index', [$year, strtolower(self::MONTHS[(int) $month])])->with('success', 'Planning row deleted.');
    }

    private function legacyImport(Request $request, int $year, string $month): RedirectResponse
    {
        $this->ensureAdmin($request);
        $monthNumber = $this->monthNumber($month);
        abort_unless($monthNumber !== null, 404);

        $request->validate([
            'file' => ['required', 'file', 'extensions:xlsx,csv,txt', 'max:5120'],
        ]);

        try {
            $extension = strtolower($request->file('file')->getClientOriginalExtension());
            $rows = $extension === 'xlsx'
                ? $this->readXlsx($request->file('file')->getRealPath())
                : $this->readCsv($request->file('file')->getRealPath());
            $count = DB::transaction(function () use ($rows, $request, $year, $monthNumber) {
                $count = 0;

                foreach ($rows as $row) {
                    $attributes = $this->importRecord($row['data'], $year, $monthNumber, $request->user()->id, $row['line']);
                    $existing = ! empty($attributes['reference'])
                        ? Booking::where('reference', $attributes['reference'])->first()
                        : null;

                    if ($existing) {
                        $before = $existing->only($this->recordFields());
                        $existing->update($attributes);
                        $this->audit($request, $existing, 'imported', ['before' => $before, 'after' => $existing->only($this->recordFields())]);
                    } else {
                        $record = Booking::create($attributes);
                        $this->audit($request, $record, 'imported', $record->only($this->recordFields()));
                    }

                    $count++;
                }

                return $count;
            });
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('safari-board.index', [$year, strtolower(self::MONTHS[$monthNumber])])
                ->with('error', $exception->getMessage() ?: 'The Excel or CSV file could not be imported. Check the column headings and row values.');
        }

        return redirect()
            ->route('safari-board.index', [$year, strtolower(self::MONTHS[$monthNumber])])
                ->with('success', $count.' planning row'.($count === 1 ? '' : 's').' imported successfully.');
    }

    public function import(Request $request, int $year, string $month): RedirectResponse
    {
        $this->ensureAdmin($request);
        $monthNumber = $this->monthNumber($month);
        abort_unless($monthNumber !== null, 404);
        $request->validate(['file' => ['required', 'file', 'extensions:xlsx,csv,txt', 'max:20480']]);
        $file = $request->file('file');
        $report = ['type' => 'safari_board', 'total' => 0, 'imported' => 0, 'duplicates' => 0, 'failed' => [], 'atomic' => true];
        $prepared = [];
        $seen = [];

        try {
            foreach ($this->spreadsheetImports->rows(
                $file->getRealPath(),
                $file->getClientOriginalExtension(),
                fn ($header) => $this->importHeaderKey($header),
            ) as $row) {
                $report['total']++;
                try {
                    $attributes = $this->importRecord($row['data'], $year, $monthNumber, $request->user()->id, $row['line']);
                    $reference = strtolower(trim((string) ($attributes['reference'] ?? '')));
                    if ($reference !== '' && (isset($seen[$reference]) || Booking::whereRaw('LOWER(reference) = ?', [$reference])->exists())) {
                        $report['duplicates']++;
                        continue;
                    }
                    if ($reference !== '') {
                        $seen[$reference] = true;
                    }
                    $prepared[] = $attributes;
                } catch (\Throwable $exception) {
                    $report['failed'][] = ['line' => $row['line'], 'reason' => $exception->getMessage()];
                }
            }

            if ($report['total'] === 0) {
                $report['failed'][] = ['line' => 1, 'reason' => 'The file does not contain any data rows.'];
            }

            if ($report['failed'] === []) {
                DB::transaction(function () use ($prepared, $request, &$report): void {
                    foreach ($prepared as $attributes) {
                        $record = Booking::create($attributes);
                        $this->audit($request, $record, 'imported', $record->only($this->recordFields()));
                        $report['imported']++;
                    }
                });
            }
        } catch (\Throwable $exception) {
            Log::error('safari_board.import.failed', [
                'user_id' => $request->user()?->id,
                'filename' => $file->getClientOriginalName(),
                'exception' => $exception->getMessage(),
            ]);
            $report['failed'][] = ['line' => 0, 'reason' => $exception->getMessage() ?: 'The file could not be imported.'];
        }

        if ($report['failed'] !== []) {
            $report['imported'] = 0;
        }
        Log::info('safari_board.import.completed', [
            'user_id' => $request->user()?->id,
            'filename' => $file->getClientOriginalName(),
            'total' => $report['total'],
            'imported' => $report['imported'],
            'duplicates' => $report['duplicates'],
            'failed' => count($report['failed']),
        ]);

        return redirect()
            ->route('safari-board.index', [$year, strtolower(self::MONTHS[$monthNumber])])
            ->with('import_report', $report)
            ->with('success', $report['failed'] === []
                ? $report['imported'].' planning row'.($report['imported'] === 1 ? '' : 's').' imported successfully.'
                : 'Import was not applied because one or more rows failed validation.');
    }

    public function importTemplate(int $year, string $month)
    {
        $monthNumber = $this->monthNumber($month);
        abort_unless($monthNumber !== null, 404);
        $headers = ['P/START', 'Notes', 'Group Name', 'PAX', 'Arrival Date', 'Departure Date', 'Guide Name', 'Guide Phone', 'Vehicle Number', 'Language', 'Time / Flight', 'Additional Time / Flight', 'TL / Translator', 'Company', 'Assignment Notes', 'Payment Status', 'Status', 'Visibility', 'Reference', 'Plan Year', 'Plan Month'];
        $rows = [['P/START', 'Example arrival notes', 'Example Safari Group', 2, Carbon::create($year, $monthNumber, 4)->format('d/m/Y'), Carbon::create($year, $monthNumber, 8)->format('d/m/Y'), '', '', '', 'EN', 'KQ 123 at 08:00', '', '', '', '', 'pending', 'draft', 'workspace', '', $year, $monthNumber]];

        return $this->downloadXlsx('safari-board-import-template.xlsx', $headers, $rows);
    }

    public function saveView(Request $request): RedirectResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:80']]);
        $filters = json_decode((string) $request->input('filters', '{}'), true) ?: [];
        FilterView::updateOrCreate(
            ['user_id' => $request->user()->id, 'context' => 'safari_board', 'name' => $validated['name']],
            ['filters' => $filters],
        );

        return back()->with('success', 'Filter view saved.');
    }

    public function deleteView(Request $request, FilterView $filterView): RedirectResponse
    {
        abort_unless($filterView->user_id === $request->user()->id, 403);
        $filterView->delete();

        return back()->with('success', 'Filter view deleted.');
    }

    private function boardQuery(Request $request, User $user, int $year, int $month): \Illuminate\Database\Eloquent\Builder
    {
        $query = Booking::with(['guide', 'vehicle', 'translator', 'company', 'creator', 'updater'])
            ->where('plan_year', $year)
            ->where('plan_month', $month);
        $this->visibleQuery($query, $user);

        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));
            $query->where(function ($searchQuery) use ($search) {
                $like = '%'.$search.'%';
                $searchQuery->where('group_name', 'like', $like)
                    ->orWhere('reference', 'like', $like)
                    ->orWhere('guide_phone', 'like', $like)
                    ->orWhere('language', 'like', $like)
                    ->orWhere('start_instruction', 'like', $like)
                    ->orWhere('assignment_notes', 'like', $like)
                    ->orWhere('operational_notes', 'like', $like)
                    ->orWhere('primary_flight_time', 'like', $like)
                    ->orWhere('secondary_flight_time', 'like', $like)
                    ->orWhereHas('guide', fn ($relation) => $relation->where('name', 'like', $like)->orWhere('phone', 'like', $like))
                    ->orWhereHas('vehicle', fn ($relation) => $relation->where('registration_number', 'like', $like))
                    ->orWhereHas('company', fn ($relation) => $relation->where('name', 'like', $like));
            });
        }

        foreach (['status', 'guide_id', 'vehicle_id', 'translator_id', 'company_id', 'language', 'payment_status', 'visibility', 'created_by', 'updated_by'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }
        if ($request->filled('arrival_from')) {
            $query->whereDate('arrival_date', '>=', $request->input('arrival_from'));
        }
        if ($request->filled('arrival_to')) {
            $query->whereDate('arrival_date', '<=', $request->input('arrival_to'));
        }
        if ($request->filled('departure_from')) {
            $query->whereDate('departure_date', '>=', $request->input('departure_from'));
        }
        if ($request->filled('departure_to')) {
            $query->whereDate('departure_date', '<=', $request->input('departure_to'));
        }
        if ($request->filled('pax_min')) {
            $query->where('pax', '>=', (int) $request->input('pax_min'));
        }
        if ($request->filled('pax_max')) {
            $query->where('pax', '<=', (int) $request->input('pax_max'));
        }
        if ($request->boolean('missing_guide')) {
            $query->whereNull('guide_id');
        }
        if ($request->boolean('missing_vehicle')) {
            $query->whereNull('vehicle_id');
        }
        if ($request->boolean('archived')) {
            $query->whereNotNull('archived_at');
        } else {
            $query->whereNull('archived_at');
        }

        return $query;
    }

    private function visibleQuery($query, User $user)
    {
        return $this->bookingVisibility->apply($query, $user);
    }

    private function validatedRecord(Request $request, bool $update, ?Booking $booking, int $defaultYear, int $defaultMonth): array
    {
        $isDraft = $request->input('submit_action') === 'draft' || $request->input('status') === 'draft';
        $referenceRule = 'unique:bookings,reference'.($update && $booking ? ','.$booking->id : '');
        $validated = $request->validate([
            'plan_year' => ['required', 'integer', 'between:2020,2100'],
            'plan_month' => ['required', 'integer', 'between:1,12'],
            'start_instruction' => ['nullable', 'string', 'max:255'],
            'group_name' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255', $referenceRule],
            'pax' => ['required', 'integer', 'min:1', 'max:999'],
            'arrival_date' => [$isDraft ? 'nullable' : 'required', 'date'],
            'departure_date' => ['nullable', 'date', 'after_or_equal:arrival_date'],
            'guide_id' => ['nullable', 'exists:guides,id'],
            'guide_phone' => ['nullable', 'string', 'max:40'],
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'language' => ['nullable', 'string', 'max:80'],
            'primary_flight_time' => ['nullable', 'string', 'max:120'],
            'secondary_flight_time' => ['nullable', 'string', 'max:120'],
            'translator_id' => ['nullable', 'exists:translators,id'],
            'company_id' => ['nullable', 'exists:companies,id'],
            'assignment_notes' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'payment_status' => ['required', 'in:'.implode(',', self::PAYMENT_STATUSES)],
            'operational_notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'in:'.implode(',', self::STATUSES)],
            'visibility' => ['required', 'in:workspace,assigned,private'],
            'visible_to_user_id' => ['nullable', 'exists:users,id'],
            'country' => ['nullable', 'string', 'max:120'],
        ]);

        $validated['plan_year'] = (int) ($validated['plan_year'] ?: $defaultYear);
        $validated['plan_month'] = (int) ($validated['plan_month'] ?: $defaultMonth);
        $validated['company_id'] = $validated['company_id'] ?? null;
        $validated['guide_id'] = $validated['guide_id'] ?? null;
        $validated['vehicle_id'] = $validated['vehicle_id'] ?? null;
        $validated['translator_id'] = $validated['translator_id'] ?? null;
        $validated['visible_to_user_id'] = $validated['visible_to_user_id'] ?? null;
        $validated['departure_date'] = $validated['departure_date'] ?? $validated['arrival_date'] ?? null;
        if ($request->input('submit_action') === 'assign' && $validated['status'] === 'draft') {
            $validated['status'] = 'confirmed';
        }
        if ($validated['visibility'] === 'assigned' && ! $validated['visible_to_user_id']) {
            $validated['visibility'] = 'workspace';
        }

        return $validated;
    }

    private function duplicateRecord(Booking $booking, int $year, int $month, int $userId): Booking
    {
        $attributes = $booking->getAttributes();
        unset($attributes['id'], $attributes['created_at'], $attributes['updated_at'], $attributes['archived_at']);
        $attributes['reference'] = $this->nextReference($year);
        $attributes['plan_year'] = $year;
        $attributes['plan_month'] = $month;
        $attributes['created_by'] = $userId;
        $attributes['updated_by'] = $userId;
        $attributes['status'] = 'draft';
        $attributes['archived_at'] = null;
        $monthDelta = ($year * 12 + $month) - ((int) $booking->plan_year * 12 + (int) $booking->plan_month);
        if ($booking->arrival_date) {
            $source = Carbon::parse($booking->arrival_date);
            $attributes['arrival_date'] = $source->copy()->addMonths($monthDelta)->toDateString();
        }
        if ($booking->departure_date) {
            $source = Carbon::parse($booking->departure_date);
            $attributes['departure_date'] = $source->copy()->addMonths($monthDelta)->toDateString();
        }

        return Booking::create($attributes);
    }

    private function syncGuidePhone(array $data): array
    {
        if (array_key_exists('guide_id', $data)) {
            $data['guide_phone'] = $data['guide_id'] ? Guide::find($data['guide_id'])?->phone : null;
        }

        return $data;
    }

    private function nextReference(int $year): string
    {
        do {
            $reference = 'TS-'.$year.'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (Booking::where('reference', $reference)->exists());

        return $reference;
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->hasPermission('planner.edit'), 403);
    }

    private function audit(Request $request, Booking $booking, string $action, array $changes): void
    {
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'safari_board.'.$action,
            'auditable_type' => Booking::class,
            'auditable_id' => $booking->id,
            'changes' => $changes,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        $verb = match ($action) {
            'created' => 'added',
            'updated', 'inline_updated' => 'updated',
            'duplicated' => 'duplicated',
            'deleted' => 'deleted',
            'imported' => 'imported',
            default => $action,
        };
        $type = in_array($action, ['deleted'], true) ? 'alert' : (in_array($action, ['created', 'imported'], true) ? 'gold' : 'blue');
        User::where('is_active', true)->get()->each(function (User $recipient) use ($request, $booking, $verb, $type): void {
            $recipient->notify(new WorkspaceActivityNotification(
                'Safari board update',
                $request->user()->name.' '.$verb.' '.$booking->group_name.'.',
                $type,
                route('planner.bookings'),
            ));
        });
    }

    private function monthNumber(string $month): ?int
    {
        $month = strtolower(trim($month));
        foreach (self::MONTHS as $number => $label) {
            if ($month === strtolower($label) || $month === strtolower(substr($label, 0, 3)) || (int) $month === $number && ctype_digit($month)) {
                return $number;
            }
        }

        return null;
    }

    private function sortField(?string $sort): string
    {
        return in_array($sort, ['status', 'group_name', 'pax', 'arrival_date', 'departure_date', 'language', 'payment_status', 'plan_month'], true)
            ? $sort
            : 'arrival_date';
    }

    private function inlineFields(): array
    {
        return array_values(array_diff($this->recordFields(), ['guide_phone']));
    }

    private function recordFields(): array
    {
        return [
            'plan_year', 'plan_month', 'start_instruction', 'group_name', 'reference', 'pax',
            'arrival_date', 'departure_date', 'guide_id', 'guide_phone', 'vehicle_id', 'language',
            'primary_flight_time', 'secondary_flight_time', 'translator_id', 'company_id',
            'notes', 'assignment_notes', 'payment_status', 'operational_notes', 'status', 'visibility',
            'visible_to_user_id', 'country',
        ];
    }

    private function inlineDisplay(Booking $booking, string $field): string
    {
        return match ($field) {
            'guide_id' => $booking->guide?->name ?: 'Unassigned',
            'vehicle_id' => $booking->vehicle?->registration_number ?: 'Unassigned',
            'translator_id' => $booking->translator?->name ?: '—',
            'company_id' => $booking->company?->name ?: '—',
            'visible_to_user_id' => $booking->visibleTo?->name ?: 'Workspace',
            'arrival_date', 'departure_date' => $booking->{$field} ? Carbon::parse($booking->{$field})->format('d/m/Y') : '—',
            'payment_status', 'status', 'visibility' => ucfirst(str_replace('_', ' ', (string) $booking->{$field})),
            default => (string) ($booking->{$field} ?: '—'),
        };
    }

    private function conflictIds($records): array
    {
        $conflicts = [];
        foreach ($records as $index => $record) {
            foreach ($records->slice($index + 1) as $other) {
                if (! $this->dateRangesOverlap($record, $other)) {
                    continue;
                }
                if (($record->guide_id && $record->guide_id === $other->guide_id) || ($record->vehicle_id && $record->vehicle_id === $other->vehicle_id)) {
                    $conflicts[] = $record->id;
                    $conflicts[] = $other->id;
                }
            }
        }

        return array_values(array_unique($conflicts));
    }

    private function dateRangesOverlap(Booking $first, Booking $second): bool
    {
        $firstStart = $first->arrival_date ? Carbon::parse($first->arrival_date) : Carbon::create($first->plan_year, $first->plan_month, 1);
        $firstEnd = $first->departure_date ? Carbon::parse($first->departure_date) : $firstStart->copy();
        $secondStart = $second->arrival_date ? Carbon::parse($second->arrival_date) : Carbon::create($second->plan_year, $second->plan_month, 1);
        $secondEnd = $second->departure_date ? Carbon::parse($second->departure_date) : $secondStart->copy();

        return $firstStart->lte($secondEnd) && $secondStart->lte($firstEnd);
    }

    private function readXlsx(string $path): array
    {
        $zip = new \ZipArchive;
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Invalid Excel workbook.');
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
                preg_match('/^[A-Z]+/', (string) $cell['r'], $match);
                $column = $this->excelColumnNumber($match[0] ?? 'A');
                $value = (string) $cell->v;
                $cellType = (string) $cell['t'];
                if ($cellType === 's') {
                    $value = $sharedStrings[(int) $value] ?? '';
                } elseif ($cellType === 'inlineStr') {
                    $value = implode('', array_map('strval', $cell->xpath('./x:is//x:t')));
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

        $headers = array_map(fn ($header) => $this->importHeaderKey($header), array_shift($rawRows) ?: []);
        $rows = [];
        foreach ($rawRows as $index => $row) {
            $row = array_pad($row, count($headers), '');
            $data = array_combine($headers, array_slice($row, 0, count($headers)));
            if (is_array($data) && array_filter($data, fn ($value) => trim((string) $value) !== '') !== []) {
                foreach (['arrival_date', 'departure_date'] as $dateKey) {
                    if (isset($data[$dateKey]) && is_numeric($data[$dateKey])) {
                        $data[$dateKey] = Carbon::create(1899, 12, 30)->addDays((int) $data[$dateKey])->format('d/m/Y');
                    }
                }
                $rows[] = ['line' => $index + 2, 'data' => $data];
            }
        }

        if ($rows === []) {
            throw new \RuntimeException('The Excel file does not contain any planning rows.');
        }

        return $rows;
    }

    private function excelColumnNumber(string $letters): int
    {
        $number = 0;
        foreach (str_split($letters) as $letter) {
            $number = ($number * 26) + ord($letter) - 65 + 1;
        }

        return $number - 1;
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

    private function downloadXlsx(string $filename, array $headers, array $rows)
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'safari_template_');
        $zip = new \ZipArchive;
        if ($tempPath === false || $zip->open($tempPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Excel template generation is unavailable.');
        }

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Import template" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $sheetRows = [];
        foreach (array_merge([$headers], $rows) as $rowNumber => $row) {
            $cells = [];
            foreach (array_values($row) as $columnNumber => $value) {
                $reference = $this->excelColumnName($columnNumber + 1).($rowNumber + 1);
                if (is_int($value) || is_float($value)) {
                    $cells[] = '<c r="'.$reference.'"><v>'.$value.'</v></c>';
                } else {
                    $escaped = htmlspecialchars((string) $value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
                    $cells[] = '<c r="'.$reference.'" t="inlineStr"><is><t xml:space="preserve">'.$escaped.'</t></is></c>';
                }
            }
            $sheetRows[] = '<row r="'.($rowNumber + 1).'">'.implode('', $cells).'</row>';
        }
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.implode('', $sheetRows).'</sheetData></worksheet>');
        $zip->close();

        return response()->download($tempPath, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
    }

    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new \RuntimeException('The uploaded CSV file could not be read.');
        }

        $headers = fgetcsv($handle);
        if ($headers === false || count(array_filter($headers, fn ($header) => trim((string) $header) !== '')) === 0) {
            fclose($handle);
            throw new \RuntimeException('The CSV file must include a header row.');
        }

        $headers = array_map(fn ($header) => $this->importHeaderKey($header), $headers);
        $rows = [];
        $line = 1;
        while (($values = fgetcsv($handle)) !== false) {
            $line++;
            $values = array_pad($values, count($headers), '');
            $data = array_combine($headers, array_slice($values, 0, count($headers)));
            if (is_array($data) && array_filter($data, fn ($value) => trim((string) $value) !== '') !== []) {
                $rows[] = ['line' => $line, 'data' => $data];
            }
        }
        fclose($handle);

        if ($rows === []) {
            throw new \RuntimeException('The CSV file does not contain any planning rows.');
        }

        return $rows;
    }

    private function importHeaderKey($header): string
    {
        $normalized = trim(strtolower((string) preg_replace('/[^a-z0-9]+/i', '_', preg_replace('/^\xEF\xBB\xBF/', '', (string) $header))), '_');

        return match ($normalized) {
            's', 'start', 'start_instruction', 'status_start_instruction' => 'start_instruction_or_status',
            'group', 'groupname', 'group_name', 'client', 'client_name' => 'group_name',
            'guests', 'passengers', 'pax' => 'pax',
            'arrival', 'arrival_d', 'arrival_date' => 'arrival_date',
            'departure', 'departure_d', 'departure_date' => 'departure_date',
            'guide', 'guide_name', 'guides_name' => 'guide_name',
            'guide_phone', 'guides_phone' => 'guide_phone',
            'vehicle', 'vehicle_no', 'vehicle_number' => 'vehicle_number',
            'lg', 'lang', 'language' => 'language',
            'time_f', 'time_flight', 'time_and_flight', 'primary_flight_time' => 'primary_flight_time',
            'additional_time_f', 'additional_time_flight', 'secondary_flight_time' => 'secondary_flight_time',
            'tl', 'translator', 'translator_name', 'tl_translator' => 'translator_name',
            'company_name', 'company' => 'company_name',
            'note', 'notes' => 'notes',
            'signed_for', 'assignment_note', 'assignment_notes' => 'assignment_notes',
            'paid', 'payment', 'payment_status' => 'payment_status',
            'operational_note', 'operational_notes' => 'operational_notes',
            'record_status', 'status' => 'status',
            'booking_reference', 'reference' => 'reference',
            'year', 'plan_year' => 'plan_year',
            'month', 'plan_month' => 'plan_month',
            'visibility' => 'visibility',
            'country' => 'country',
            default => $normalized,
        };
    }

    private function importRecord(array $row, int $defaultYear, int $defaultMonth, int $userId, int $line): array
    {
        $value = fn (array $keys): string => trim((string) collect($keys)->map(fn ($key) => $row[$key] ?? null)->first(fn ($item) => trim((string) $item) !== ''));
        $startOrStatus = $value(['start_instruction_or_status']);
        $status = $value(['status']);
        $startInstruction = $value(['start_instruction']);
        if ($status === '' && in_array(strtolower(str_replace(' ', '_', $startOrStatus)), self::STATUSES, true)) {
            $status = $startOrStatus;
        } elseif ($startInstruction === '') {
            $startInstruction = $startOrStatus;
        }

        $groupName = $value(['group_name']);
        $pax = $value(['pax']);
        if ($groupName === '' || $pax === '') {
            throw new \RuntimeException('Row '.$line.' must include Group Name and PAX.');
        }

        $attributes = [
            'plan_year' => (int) ($value(['plan_year']) ?: $defaultYear),
            'plan_month' => $this->importMonth($value(['plan_month']) ?: (string) $defaultMonth, $line),
            'start_instruction' => $startInstruction ?: null,
            'group_name' => $groupName,
            'reference' => $value(['reference']) ?: $this->nextReference($defaultYear),
            'pax' => is_numeric($pax) ? (int) $pax : 0,
            'arrival_date' => $this->importDate($value(['arrival_date']), $line, 'Arrival Date'),
            'departure_date' => $this->importDate($value(['departure_date']), $line, 'Departure Date'),
            'guide_id' => $this->resolveImportResource(Guide::class, $value(['guide_name']), 'name', $line, 'Guide'),
            'guide_phone' => $value(['guide_phone']) ?: null,
            'vehicle_id' => $this->resolveImportResource(Vehicle::class, $value(['vehicle_number']), 'registration_number', $line, 'Vehicle'),
            'language' => $value(['language']) ?: null,
            'primary_flight_time' => $value(['primary_flight_time']) ?: null,
            'secondary_flight_time' => $value(['secondary_flight_time']) ?: null,
            'translator_id' => $this->resolveImportResource(Translator::class, $value(['translator_name']), 'name', $line, 'Translator'),
            'company_id' => $this->resolveImportResource(Company::class, $value(['company_name']), 'name', $line, 'Company'),
            'notes' => $value(['notes']) ?: null,
            'assignment_notes' => $value(['assignment_notes']) ?: null,
            'payment_status' => $this->importEnum($value(['payment_status']) ?: 'pending', self::PAYMENT_STATUSES, 'payment status', $line),
            'operational_notes' => $value(['operational_notes']) ?: null,
            'status' => $this->importEnum($status ?: 'draft', self::STATUSES, 'record status', $line),
            'visibility' => $this->importEnum($value(['visibility']) ?: 'workspace', ['workspace', 'assigned', 'private'], 'visibility', $line),
            'country' => $value(['country']) ?: null,
            'updated_by' => $userId,
        ];
        $attributes = $this->syncGuidePhone($attributes);

        $validator = Validator::make($attributes, [
            'plan_year' => ['required', 'integer', 'between:2020,2100'],
            'plan_month' => ['required', 'integer', 'between:1,12'],
            'group_name' => ['required', 'string', 'max:255'],
            'reference' => ['required', 'string', 'max:255'],
            'pax' => ['required', 'integer', 'min:1', 'max:999'],
            'arrival_date' => ['nullable', 'date'],
            'departure_date' => ['nullable', 'date', 'after_or_equal:arrival_date'],
            'assignment_notes' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'operational_notes' => ['nullable', 'string', 'max:2000'],
        ]);
        if ($validator->fails()) {
            throw new \RuntimeException('Row '.$line.' is invalid: '.$validator->errors()->first());
        }

        $existing = Booking::where('reference', $attributes['reference'])->exists();
        if (! $existing) {
            $attributes['created_by'] = $userId;
        }

        return $attributes;
    }

    private function importDate(string $value, int $line, string $label): ?string
    {
        if ($value === '') {
            return null;
        }

        foreach (['d/m/Y', 'Y-m-d', 'm/d/Y', 'd-m-Y'] as $format) {
            try {
                return Carbon::createFromFormat('!'.$format, $value)->toDateString();
            } catch (\Throwable) {
                // Try the next supported date format.
            }
        }

        throw new \RuntimeException('Row '.$line.' has an invalid '.$label.': '.$value.'. Use DD/MM/YYYY or YYYY-MM-DD.');
    }

    private function importMonth(string $value, int $line): int
    {
        $month = $this->monthNumber($value);
        if ($month === null) {
            throw new \RuntimeException('Row '.$line.' has an invalid planning month: '.$value.'.');
        }

        return $month;
    }

    private function importEnum(string $value, array $allowed, string $label, int $line): string
    {
        $normalized = strtolower(trim(str_replace(['-', ' '], '_', $value)));
        if (in_array($normalized, $allowed, true)) {
            return $normalized;
        }

        throw new \RuntimeException('Row '.$line.' has an invalid '.$label.': '.$value.'.');
    }

    private function resolveImportResource(string $model, string $value, string $column, int $line, string $label): ?int
    {
        if ($value === '') {
            return null;
        }

        $record = $model::whereRaw('LOWER('.$column.') = ?', [strtolower($value)])->first();
        if (! $record && $model === Guide::class) {
            $name = trim((string) preg_replace('/\s+\+?[0-9][0-9\s-]*$/', '', $value));
            $record = $name !== '' ? $model::whereRaw('LOWER(name) = ?', [strtolower($name)])->first() : null;
        }
        if (! $record) {
            throw new \RuntimeException('Row '.$line.' references an unknown '.$label.': '.$value.'.');
        }

        return (int) $record->id;
    }

    private function filterPayload(Request $request): array
    {
        return $request->only([
            'q', 'status', 'arrival_from', 'arrival_to', 'departure_from', 'departure_to',
            'guide_id', 'vehicle_id', 'translator_id', 'company_id', 'language', 'payment_status',
            'missing_guide', 'missing_vehicle', 'assignment_conflict', 'capacity_warning', 'pax_min', 'pax_max', 'visibility', 'created_by',
            'updated_by', 'archived',
        ]);
    }

    private function activeFilters(Request $request): array
    {
        $labels = [
            'q' => 'Search', 'status' => 'Status', 'arrival_from' => 'Arrival from', 'arrival_to' => 'Arrival to',
            'departure_from' => 'Departure from', 'departure_to' => 'Departure to', 'guide_id' => 'Guide',
            'vehicle_id' => 'Vehicle', 'translator_id' => 'TL / Translator', 'company_id' => 'Company',
            'language' => 'Language', 'payment_status' => 'Payment', 'pax_min' => 'Min PAX', 'pax_max' => 'Max PAX',
            'visibility' => 'Visibility', 'created_by' => 'Created by', 'updated_by' => 'Updated by',
            'missing_guide' => 'Missing guide', 'missing_vehicle' => 'Missing vehicle', 'assignment_conflict' => 'Assignment conflict', 'capacity_warning' => 'Capacity warning', 'archived' => 'Archived',
        ];
        $filters = [];
        foreach ($labels as $key => $label) {
            if (! $request->filled($key) && ! in_array($key, ['missing_guide', 'missing_vehicle', 'assignment_conflict', 'capacity_warning', 'archived'], true)) {
                continue;
            }
            if (in_array($key, ['missing_guide', 'missing_vehicle', 'assignment_conflict', 'capacity_warning', 'archived'], true) && ! $request->boolean($key)) {
                continue;
            }
            $filters[] = ['key' => $key, 'label' => $label, 'value' => $request->boolean($key) ? 'Yes' : $request->input($key)];
        }

        return $filters;
    }

    private function mergeSavedView(Request $request, User $user): void
    {
        if (! $request->filled('view_id')) {
            return;
        }
        $view = FilterView::where('user_id', $user->id)->where('context', 'safari_board')->find($request->integer('view_id'));
        if (! $view) {
            return;
        }
        $request->merge($view->filters ?: []);
    }
}
