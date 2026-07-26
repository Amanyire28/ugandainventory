<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ExpenseController extends Controller
{
    // Relaxed resolver so lists show while wiring business context; tighten later as needed
    private function resolveBusinessIdNullable(): ?int
    {
        $u = auth()->user();
        return $u->business_id
            ?? optional($u->business)->id
            ?? session('business_id')
            ?? data_get(session('business'), 'id');
    }

        public function my(Request $req)
    {
        $role = optional($req->user()->role)->name;
        if ($role === 'cashier') {
            // Reuse the cashier-only implementation
            return $this->cashierMy($req);
        }

        // Owner/Manager behavior — either implement a dedicated owner "my" or redirect
        // If owners don't have a "my" page, send them to all expenses
        return redirect()->route('expenses.index');
    }

    // Strict resolver (use for storing records)
    private function ensureBusinessId(): int
    {
        $bid = $this->resolveBusinessIdNullable();
        if (!$bid) abort(400, 'No business selected');
        return (int) $bid;
    }

    // Base query (tolerant if no business_id yet)
    private function baseQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $q = Expense::query();
        if ($bid = $this->resolveBusinessIdNullable()) {
            $q->where('business_id', $bid);
        }
        return $q;
    }

    // Apply optional filters to listing query: search + date range ONLY IF provided
    private function applyFilters(\Illuminate\Database\Eloquent\Builder $q, Request $req): \Illuminate\Database\Eloquent\Builder
    {
        // Client-side list search exists in views; this is server-side search if you add `search` param
        if ($req->filled('search')) {
            $term = trim($req->search);
            $q->where(function($qq) use ($term) {
                $qq->where('purpose', 'like', "%{$term}%")
                   ->orWhere('spent_by', 'like', "%{$term}%")
                   ->orWhere('notes', 'like', "%{$term}%");
            });
        }

        // Date range filter only affects list IF user provided it
        if ($req->filled(['start_date','end_date'])) {
            $q->whereBetween('date_spent', [
                Carbon::parse($req->start_date)->startOfDay(),
                Carbon::parse($req->end_date)->endOfDay(),
            ]);
        }

        return $q;
    }

    // Listing builder (shared)
    private function listQuery(Request $req): \Illuminate\Database\Eloquent\Builder
    {
        return $this->applyFilters($this->baseQuery(), $req)
            ->orderByDesc('date_spent')
            ->orderByDesc('id');
    }

    // Helpers for charts
    private function groupByDay(Collection $rows): array
    {
        return $rows->groupBy(fn($e) => Carbon::parse($e->date_spent)->toDateString())
            ->map(fn($g) => (float) $g->sum('amount'))
            ->sortKeys()
            ->all();
    }

    private function groupByMonth(Collection $rows): array
    {
        return $rows->groupBy(fn($e) => Carbon::parse($e->date_spent)->format('Y-m'))
            ->map(fn($g) => (float) $g->sum('amount'))
            ->sortKeys()
            ->all();
    }

    // OWNER: All Expenses
    // - Table: not affected by date range initially (shows all)
    // - Charts: use selected date range (or default current month) and single-year trend (selectable)
    public function index(Request $req)
    {
        // TABLE (initially unfiltered by date until provided)
        $listQ    = $this->listQuery($req);
        $expenses = $listQ->paginate(20);
        $total    = (clone $listQ)->sum('amount');

        // CHART RANGE for comparison line (independent from table unless user set dates)
        $rangeStart = $req->filled('start_date') ? Carbon::parse($req->start_date)->startOfDay() : Carbon::now()->startOfMonth();
        $rangeEnd   = $req->filled('end_date')   ? Carbon::parse($req->end_date)->endOfDay()     : Carbon::now()->endOfMonth();
        $days       = max(1, $rangeStart->diffInDays($rangeEnd) + 1);
        $prevStart  = (clone $rangeStart)->subDays($days);
        $prevEnd    = (clone $rangeEnd)->subDays($days);

        $currentRows = $this->baseQuery()->whereBetween('date_spent', [$rangeStart, $rangeEnd])->get();
        $prevRows    = $this->baseQuery()->whereBetween('date_spent', [$prevStart, $prevEnd])->get();

        $dayCompare = [
            'labels'   => array_values(array_unique(array_merge(
                array_keys($this->groupByDay($currentRows)),
                array_keys($this->groupByDay($prevRows))
            ))),
            'current'  => $this->groupByDay($currentRows),
            'previous' => $this->groupByDay($prevRows),
            'current_total' => (float) $currentRows->sum('amount'),
            'previous_total'=> (float) $prevRows->sum('amount'),
        ];

        // Single-year monthly trend (independent of table list)
        // Default to latest year that has data for a better first-load experience
        $latestYearWithData = (int) ($this->baseQuery()
            ->selectRaw('YEAR(date_spent) as y')
            ->orderBy('y','desc')
            ->value('y') ?? now()->year);

        $year     = (int) ($req->input('year') ?? $latestYearWithData);
        $yearRows = $this->baseQuery()->whereYear('date_spent', $year)->get();
        $monthMap = $this->groupByMonth($yearRows);
        $labels   = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        $monthKeys= array_map(fn($i) => sprintf('%d-%02d', $year, $i), range(1,12));
        $series   = array_map(fn($k) => (float) ($monthMap[$k] ?? 0), $monthKeys);

        $yearTrend = [
            'labels' => $labels,
            'series' => $series,
            'year'   => $year,
            'total'  => (float) $yearRows->sum('amount'),
        ];

        return view('owner.expenses.index', [
            'expenses' => $expenses,
            'total'    => $total,
            'filters'  => $req->only(['start_date','end_date','year']), // list is initially unaffected by dates
            'dayCompare' => $dayCompare,
            'yearTrend'  => $yearTrend,
            'range' => ['start' => $rangeStart, 'end' => $rangeEnd, 'prevStart'=>$prevStart, 'prevEnd'=>$prevEnd],
        ]);
    }

    // OWNER: Today page
    // - Table: not affected by date range initially (shows all)
    // - Chart: uses today by default or selected date range if provided
    public function today(Request $req)
    {
        // TABLE
        $listQ    = $this->listQuery($req);
        $expenses = $listQ->paginate(20);
        $total    = (clone $listQ)->sum('amount');

        // CHART: today or selected range
        $start = $req->filled('start_date') ? Carbon::parse($req->start_date)->startOfDay() : Carbon::today()->startOfDay();
        $end   = $req->filled('end_date')   ? Carbon::parse($req->end_date)->endOfDay()     : Carbon::today()->endOfDay();
        $days  = max(1, $start->diffInDays($end) + 1);
        $prevStart = (clone $start)->subDays($days);
        $prevEnd   = (clone $end)->subDays($days);

        $prevTotal = (float) $this->baseQuery()->whereBetween('date_spent', [$prevStart, $prevEnd])->sum('amount');
        $curTotal  = (float) $this->baseQuery()->whereBetween('date_spent', [$start, $end])->sum('amount');

        $chart = [
            'labels' => ['Previous','Current'],
            'values' => [$prevTotal, $curTotal],
        ];

        return view('owner.expenses.today', [
            'expenses' => $expenses,
            'total'    => $total,
            'filters'  => $req->only(['start_date','end_date']),
            'range'    => ['start' => $start, 'end' => $end, 'prevStart'=>$prevStart, 'prevEnd'=>$prevEnd],
            'chart'    => $chart,
        ]);
    }

    // OWNER: Weekly page
    // - Table: not affected by date range initially (shows all)
    // - Chart: current week by default or selected range if provided
    public function weekly(Request $req)
    {
        // TABLE
        $listQ    = $this->listQuery($req);
        $expenses = $listQ->paginate(20);
        $total    = (clone $listQ)->sum('amount');

        // CHART: default current week or selected range
        $defaultStart = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $defaultEnd   = Carbon::now()->endOfWeek(Carbon::SUNDAY);
        $start = $req->filled('start_date') ? Carbon::parse($req->start_date)->startOfDay() : $defaultStart;
        $end   = $req->filled('end_date')   ? Carbon::parse($req->end_date)->endOfDay()     : $defaultEnd;
        $days  = max(1, $start->diffInDays($end) + 1);
        $prevStart = (clone $start)->subDays($days);
        $prevEnd   = (clone $end)->subDays($days);

        $labels = [];
        $curVals = [];
        $prevVals= [];
        $i = 0;
        for ($d = (clone $start); $d <= $end; $d->addDay()) {
            $labels[]   = $d->format('M d');
            $curVals[]  = (float) $this->baseQuery()->whereBetween('date_spent', [$start,$end])->whereDate('date_spent',$d->toDateString())->sum('amount');
            $prevDate   = (clone $prevStart)->addDays($i)->toDateString();
            $prevVals[] = (float) $this->baseQuery()->whereBetween('date_spent', [$prevStart,$prevEnd])->whereDate('date_spent',$prevDate)->sum('amount');
            $i++;
        }

        $chart = [
            'labels' => $labels,
            'cur'    => $curVals,
            'prev'   => $prevVals,
            'totals' => [
                'cur'  => (float) $this->baseQuery()->whereBetween('date_spent', [$start,$end])->sum('amount'),
                'prev' => (float) $this->baseQuery()->whereBetween('date_spent', [$prevStart,$prevEnd])->sum('amount'),
            ],
        ];

        return view('owner.expenses.weekly', [
            'expenses' => $expenses,
            'total'    => $total,
            'filters'  => $req->only(['start_date','end_date']),
            'range'    => ['start' => $start, 'end' => $end, 'prevStart'=>$prevStart, 'prevEnd'=>$prevEnd],
            'chart'    => $chart,
        ]);
    }

    // OWNER: Monthly page
    // - Table: not affected by date range initially (shows all)
    // - Chart: month/year by default or selected range if provided
    public function monthly(Request $req)
    {
        // TABLE
        $listQ    = $this->listQuery($req);
        $expenses = $listQ->paginate(20);
        $total    = (clone $listQ)->sum('amount');

        // CHART: month/year or explicit range
        $year  = (int) ($req->input('year') ?? now()->year);
        $month = (int) ($req->input('month') ?? now()->month);
        $defaultStart = Carbon::createFromDate($year,$month,1)->startOfMonth();
        $defaultEnd   = Carbon::createFromDate($year,$month,1)->endOfMonth();
        $start = $req->filled('start_date') ? Carbon::parse($req->start_date)->startOfDay() : $defaultStart;
        $end   = $req->filled('end_date')   ? Carbon::parse($req->end_date)->endOfDay()     : $defaultEnd;
        $days  = max(1, $start->diffInDays($end) + 1);
        $prevStart = (clone $start)->subDays($days);
        $prevEnd   = (clone $end)->subDays($days);

        $labels   = [];
        $curVals  = [];
        $prevVals = [];
        $i = 0;
        for ($d = (clone $start); $d <= $end; $d->addDay()) {
            $labels[]   = $d->format('d');
            $curVals[]  = (float) $this->baseQuery()->whereBetween('date_spent', [$start,$end])->whereDate('date_spent',$d->toDateString())->sum('amount');
            $prevDate   = (clone $prevStart)->addDays($i)->toDateString();
            $prevVals[] = (float) $this->baseQuery()->whereBetween('date_spent', [$prevStart,$prevEnd])->whereDate('date_spent',$prevDate)->sum('amount');
            $i++;
        }

        $chart = [
            'labels' => $labels,
            'cur'    => $curVals,
            'prev'   => $prevVals,
            'totals' => [
                'cur'  => (float) $this->baseQuery()->whereBetween('date_spent', [$start,$end])->sum('amount'),
                'prev' => (float) $this->baseQuery()->whereBetween('date_spent', [$prevStart,$prevEnd])->sum('amount'),
            ],
            'meta'   => ['year'=>$year,'month'=>$month],
        ];

        return view('owner.expenses.monthly', [
            'expenses' => $expenses,
            'total'    => $total,
            'filters'  => $req->only(['start_date','end_date','year','month']),
            'range'    => ['start' => $start, 'end' => $end, 'prevStart'=>$prevStart, 'prevEnd'=>$prevEnd],
            'chart'    => $chart,
            'year'     => $year,
            'month'    => $month,
        ]);
    }






    // cashier methods 

       private function cashierBaseQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $q = Expense::query()->where('user_id', auth()->id());
        $u = auth()->user();
        $bid = $u->business_id ?? session('business_id');
        if ($bid) {
            $q->where('business_id', $bid);
        }
        return $q;
    }

    // Apply optional filters (search + date range), but only when provided
    private function cashierApplyFilters(\Illuminate\Database\Eloquent\Builder $q, Request $req): \Illuminate\Database\Eloquent\Builder
    {
        if ($req->filled('search')) {
            $term = trim($req->search);
            $q->where(function($qq) use ($term) {
                $qq->where('purpose', 'like', "%{$term}%")
                   ->orWhere('spent_by', 'like', "%{$term}%")
                   ->orWhere('notes', 'like', "%{$term}%");
            });
        }
        if ($req->filled(['start_date','end_date'])) {
            $q->whereBetween('date_spent', [
                Carbon::parse($req->start_date)->startOfDay(),
                Carbon::parse($req->end_date)->endOfDay(),
            ]);
        }
        return $q;
    }

    private function cashierListQuery(Request $req): \Illuminate\Database\Eloquent\Builder
    {
        return $this->cashierApplyFilters($this->cashierBaseQuery(), $req)
            ->orderByDesc('date_spent')
            ->orderByDesc('id');
    }

    // Cashier: My expenses (list initially not date-filtered)
    public function cashierMy(Request $req)
    {
        $listQ    = $this->cashierListQuery($req);
        $expenses = $listQ->paginate(20);
        $total    = (clone $listQ)->sum('amount');

        // Chart range for day comparison (uses selected dates or defaults to current month)
        $start = $req->filled('start_date') ? Carbon::parse($req->start_date)->startOfDay() : Carbon::now()->startOfMonth();
        $end   = $req->filled('end_date')   ? Carbon::parse($req->end_date)->endOfDay()     : Carbon::now()->endOfMonth();
        $days  = max(1, $start->diffInDays($end) + 1);
        $prevStart = (clone $start)->subDays($days);
        $prevEnd   = (clone $end)->subDays($days);

        $curRows  = $this->cashierBaseQuery()->whereBetween('date_spent', [$start,$end])->get();
        $prevRows = $this->cashierBaseQuery()->whereBetween('date_spent', [$prevStart,$prevEnd])->get();

        $labels = array_values(array_unique(array_merge(
            array_keys($curRows->groupBy(fn($e) => Carbon::parse($e->date_spent)->toDateString())->keys()->toArray()),
            array_keys($prevRows->groupBy(fn($e) => Carbon::parse($e->date_spent)->toDateString())->keys()->toArray())
        )));

        $curMap  = collect($curRows)->groupBy(fn($e) => Carbon::parse($e->date_spent)->toDateString())->map->sum('amount')->all();
        $prevMap = collect($prevRows)->groupBy(fn($e) => Carbon::parse($e->date_spent)->toDateString())->map->sum('amount')->all();

        $chart = [
            'labels' => $labels,
            'cur'    => $curMap,
            'prev'   => $prevMap,
            'totals' => ['cur' => (float) $curRows->sum('amount'), 'prev' => (float) $prevRows->sum('amount')],
            'range'  => ['start'=>$start,'end'=>$end,'prevStart'=>$prevStart,'prevEnd'=>$prevEnd],
        ];

        return view('cashier.expenses.my', compact('expenses','total','chart'))
            ->with(['filters' => $req->only(['search','start_date','end_date'])]);
    }

    // Cashier: Today (chart defaults to today; list initially not date-filtered)
    public function cashierToday(Request $req)
    {
        // List
        $listQ    = $this->cashierListQuery($req);
        $expenses = $listQ->paginate(20);
        $total    = (clone $listQ)->sum('amount');

        // Chart range
        $start = $req->filled('start_date') ? Carbon::parse($req->start_date)->startOfDay() : Carbon::today()->startOfDay();
        $end   = $req->filled('end_date')   ? Carbon::parse($req->end_date)->endOfDay()     : Carbon::today()->endOfDay();
        $days  = max(1, $start->diffInDays($end) + 1);
        $prevStart = (clone $start)->subDays($days);
        $prevEnd   = (clone $end)->subDays($days);

        $prevTotal = (float) $this->cashierBaseQuery()->whereBetween('date_spent', [$prevStart,$prevEnd])->sum('amount');
        $curTotal  = (float) $this->cashierBaseQuery()->whereBetween('date_spent', [$start,$end])->sum('amount');

        $chart = [
            'labels' => ['Previous','Current'],
            'values' => [ $prevTotal, $curTotal ],
            'range'  => ['start'=>$start,'end'=>$end,'prevStart'=>$prevStart,'prevEnd'=>$prevEnd],
        ];

        return view('cashier.expenses.today', compact('expenses','total','chart'))
            ->with(['filters' => $req->only(['start_date','end_date'])]);
    }

    // Render create view per role
    public function create()
    {
        if (optional(auth()->user()->role)->name === 'cashier') {
            return view('cashier.expenses.create');
        }
        return view('owner.expenses.create'); // or your owner create view
    }

    // Store (works for both roles; cashier’s business_id resolved via user/session)
    public function store(Request $req)
    {
        $data = $req->validate([
            'spent_by'   => ['required','string','max:100'],
            'purpose'    => ['required','string','max:100'],
            'amount'     => ['required','numeric','min:0'],
            'date_spent' => ['required','date'],
            'notes'      => ['nullable','string','max:255'],
        ]);

        $u = auth()->user();
        $bid = $u->business_id ?? session('business_id');
        if (!$bid) abort(400, 'No business selected');

        $data['business_id'] = (int) $bid;
        $data['user_id'] = auth()->id();
        $data['location_id'] = session('active_location_id') ?? $u->location_id ?? \App\Models\Location::where('business_id', $bid)->where('is_main', true)->value('id') ?? \App\Models\Location::where('business_id', $bid)->value('id');

        try {
            $expense = Expense::create($data);

            // Redirect role-aware
            if (optional($u->role)->name === 'cashier') {
                return redirect()->route('cashier.expenses.create')->with('success', "Expense of UGX " . number_format($data['amount'], 0) . " recorded successfully! ✅");
            }
            return redirect()->route('expenses.create')->with('success', "Expense of UGX " . number_format($data['amount'], 0) . " recorded successfully! ✅");
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Failed to record expense: ' . $e->getMessage());
        }
    }
}