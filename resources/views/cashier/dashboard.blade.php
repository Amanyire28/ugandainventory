@extends('layouts.cashier-layout')

@section('title', 'My Dashboard')
@section('page-title')
    <i class="fas fa-chart-line text-yellow-600 mr-2"></i>My Performance Dashboard
@endsection

@section('content')

    <!-- Welcome Message -->
    <div class="bg-indigo-900 rounded-xl shadow-lg p-6 text-white mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold">
                    Hello, {{ auth()->user()->name }}! 👋
                </h2>
                <p class="mt-2">Here's your performance summary. Keep up the great work!</p>
                @if($myPosition)
                <p class="mt-2 text-yellow-100">
                    🏆 You're ranked #{{ $myPosition }} out of {{ $totalCashiers }} cashiers this month!
                </p>
                @endif
            </div>
            <div class="hidden md:block">
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <i class="fas fa-trophy text-5xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================
         MY PERFORMANCE STATS
    ======================================== -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-6">
        <!-- Today's Performance -->
        <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-xl shadow-lg p-6 transition hover:-translate-y-1 hover:shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm">My Sales Today</p>
                    <p class="text-3xl font-bold mt-2">{{ $mySalesToday }}</p>
                    <p class="text-green-100 text-xs mt-1">UGX {{ number_format($myRevenueToday, 0) }}</p>
                    @if($mySalesToday > 0)
                    <p class="text-green-100 text-xs">Avg: UGX {{ number_format($myAvgSaleToday, 0) }}</p>
                    @endif
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <i class="fas fa-calendar-day text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- This Week -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-xl shadow-lg p-6 transition hover:-translate-y-1 hover:shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm">This Week</p>
                    <p class="text-3xl font-bold mt-2">{{ $mySalesWeek }}</p>
                    <p class="text-blue-100 text-xs mt-1">UGX {{ number_format($myRevenueWeek, 0) }}</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <i class="fas fa-calendar-week text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- This Month -->
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-xl shadow-lg p-6 transition hover:-translate-y-1 hover:shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm">This Month</p>
                    <p class="text-3xl font-bold mt-2">{{ $mySalesMonth }}</p>
                    <p class="text-purple-100 text-xs mt-1">UGX {{ number_format($myRevenueMonth, 0) }}</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <i class="fas fa-calendar-alt text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- All Time -->
        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 text-white rounded-xl shadow-lg p-6 transition hover:-translate-y-1 hover:shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-yellow-100 text-sm">All Time</p>
                    <p class="text-3xl font-bold mt-2">{{ $myTotalSales }}</p>
                    <p class="text-yellow-100 text-xs mt-1">UGX {{ number_format($myTotalRevenue, 0) }}</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <i class="fas fa-trophy text-3xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================
         QUICK ACTIONS
    ======================================== -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">
            <i class="fas fa-bolt text-yellow-600 mr-2"></i>Quick Actions
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="{{ route('pos.index') }}" class="flex flex-col items-center p-6 bg-green-50 rounded-lg hover:bg-green-100 transition border-2 border-green-200 group">
                <i class="fas fa-cash-register text-4xl text-green-600 mb-2 group-hover:scale-110 transition"></i>
                <span class="text-sm font-bold text-green-900">NEW SALE</span>
            </a>
            <a href="{{ route('sales.index') }}" class="flex flex-col items-center p-6 bg-blue-50 rounded-lg hover:bg-blue-100 transition border-2 border-blue-200 group">
                <i class="fas fa-list text-4xl text-blue-600 mb-2 group-hover:scale-110 transition"></i>
                <span class="text-sm font-medium text-blue-900">My Sales</span>
            </a>
            <a href="{{ route('customers.create') }}" class="flex flex-col items-center p-6 bg-purple-50 rounded-lg hover:bg-purple-100 transition border-2 border-purple-200 group">
                <i class="fas fa-user-plus text-4xl text-purple-600 mb-2 group-hover:scale-110 transition"></i>
                <span class="text-sm font-medium text-purple-900">Add Customer</span>
            </a>
            <a href="{{ route('products.index') }}" class="flex flex-col items-center p-6 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition border-2 border-yellow-200 group">
                <i class="fas fa-search text-4xl text-yellow-600 mb-2 group-hover:scale-110 transition"></i>
                <span class="text-sm font-medium text-yellow-900">Find Product</span>
            </a>
        </div>
    </div>

    <!-- ========================================
         CHARTS
    ======================================== -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">
                <i class="fas fa-chart-line text-indigo-600 mr-2"></i>Daily Sales Trend (Last 7 Days)
            </h3>
            <canvas id="dailyTrendChart" height="120"></canvas>
        </div>
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">
                <i class="fas fa-clock text-green-600 mr-2"></i>Hourly Performance (Today)
            </h3>
            <canvas id="hourlyChart" height="120"></canvas>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">
            <i class="fas fa-chart-bar text-purple-600 mr-2"></i>Monthly Sales Overview (Last 6 Months)
        </h3>
        <canvas id="monthlyChart" height="80"></canvas>
    </div>

    <!-- ========================================
         RECENT SALES + TOP PRODUCTS
    ======================================== -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

        <!-- My Recent Sales -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-800">
                    <i class="fas fa-receipt text-yellow-600 mr-2"></i>My Recent Sales
                </h3>
                <a href="{{ route('sales.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">
                    View All <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
            <div class="space-y-3 max-h-96 overflow-y-auto">
                @forelse($myRecentSales as $sale)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition cursor-pointer"
                     onclick="window.location='{{ route('sales.show', $sale) }}'">
                    <div>
                        <p class="font-semibold text-gray-900">{{ $sale->sale_number }}</p>
                        <p class="text-xs text-gray-500">{{ $sale->sale_date->format('M d, h:i A') }}</p>
                        <p class="text-xs text-gray-600">{{ $sale->customer->name ?? 'Walk-in' }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-green-600">UGX {{ number_format($sale->total, 0) }}</p>
                        <span class="text-xs text-indigo-600">View <i class="fas fa-arrow-right"></i></span>
                    </div>
                </div>
                @empty
                <div class="text-center py-8">
                    <i class="fas fa-inbox text-5xl text-gray-300 mb-3"></i>
                    <p class="text-gray-500">No sales yet today</p>
                    <a href="{{ route('pos.index') }}" class="inline-block mt-3 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        <i class="fas fa-plus-circle mr-1"></i>Make Your First Sale
                    </a>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Top Products Sold Today -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-800">
                    <i class="fas fa-box text-yellow-600 mr-2"></i>Top Products Sold Today
                </h3>
                <a href="{{ route('sales.today') }}" class="text-sm text-indigo-600 hover:text-indigo-800">
                    View Details <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
            <div class="space-y-3 max-h-96 overflow-y-auto">
                @forelse($myTopProducts as $product)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                    <div class="flex items-center space-x-3">
                        @if($product->image)
                        <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}"
                             class="w-10 h-10 rounded object-cover">
                        @else
                        <div class="w-10 h-10 bg-gradient-to-br from-indigo-100 to-purple-100 rounded flex items-center justify-center">
                            <i class="fas fa-box text-indigo-400"></i>
                        </div>
                        @endif
                        <div>
                            <p class="font-semibold text-gray-800 text-sm">{{ $product->name }}</p>
                            <p class="text-xs text-gray-500">
                                <i class="fas fa-shopping-cart mr-1"></i>{{ $product->times_sold }} times
                            </p>
                        </div>
                    </div>
                    <p class="text-sm font-bold text-gray-700">UGX {{ number_format($product->selling_price, 0) }}</p>
                </div>
                @empty
                <div class="text-center py-8">
                    <i class="fas fa-box-open text-5xl text-gray-300 mb-3"></i>
                    <p class="text-gray-500">No products sold today yet</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Keyboard Shortcuts -->
    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">
            <i class="fas fa-keyboard text-indigo-600 mr-2"></i>Keyboard Shortcuts & Tips
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white p-3 rounded-lg">
                <p class="text-xs text-gray-500">Search Products</p>
                <p class="font-bold text-indigo-600 text-lg">F2</p>
            </div>
            <div class="bg-white p-3 rounded-lg">
                <p class="text-xs text-gray-500">Clear Cart</p>
                <p class="font-bold text-indigo-600 text-lg">F9</p>
            </div>
            <div class="bg-white p-3 rounded-lg">
                <p class="text-xs text-gray-500">Complete Sale</p>
                <p class="font-bold text-indigo-600 text-lg">F12</p>
            </div>
            <div class="bg-white p-3 rounded-lg">
                <p class="text-xs text-gray-500">Barcode Scanner</p>
                <p class="font-bold text-green-600 text-lg">Ready</p>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    // Daily Trend Chart (Last 7 Days)
    new Chart(document.getElementById('dailyTrendChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: [@for($i = 6; $i >= 0; $i--) '{{ now()->subDays($i)->format("D, M d") }}', @endfor],
            datasets: [{
                label: 'Sales (UGX)',
                data: [
                    @for($i = 6; $i >= 0; $i--)
                    {{ \App\Models\Sale::where('user_id', auth()->id())->whereDate('sale_date', now()->subDays($i))->sum('total') }},
                    @endfor
                ],
                borderColor: 'rgb(99, 102, 241)',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                tension: 0.4, fill: true, pointRadius: 5, pointHoverRadius: 7
            }, {
                label: 'Count',
                data: [
                    @for($i = 6; $i >= 0; $i--)
                    {{ \App\Models\Sale::where('user_id', auth()->id())->whereDate('sale_date', now()->subDays($i))->count() }},
                    @endfor
                ],
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                tension: 0.4, fill: true, yAxisID: 'y1', pointRadius: 5, pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { display: true, position: 'bottom' } },
            scales: {
                y: { ticks: { callback: v => 'UGX ' + v.toLocaleString() } },
                y1: { type: 'linear', position: 'right', grid: { drawOnChartArea: false } }
            }
        }
    });

    // Hourly Performance Chart (Today)
    new Chart(document.getElementById('hourlyChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: ['8AM','9AM','10AM','11AM','12PM','1PM','2PM','3PM','4PM','5PM','6PM'],
            datasets: [{
                label: 'Sales (UGX)',
                data: [
                    @for($hour = 8; $hour <= 18; $hour++)
                    {{ \App\Models\Sale::where('user_id', auth()->id())->whereDate('sale_date', today())->whereRaw('HOUR(sale_date) = ?', [$hour])->sum('total') }},
                    @endfor
                ],
                backgroundColor: 'rgba(34, 197, 94, 0.7)',
                borderColor: 'rgb(34, 197, 94)',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { callback: v => 'UGX ' + v.toLocaleString() } } }
        }
    });

    // Monthly Overview Chart (Last 6 Months)
    new Chart(document.getElementById('monthlyChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: [@for($i = 5; $i >= 0; $i--) '{{ now()->subMonths($i)->format("M Y") }}', @endfor],
            datasets: [{
                label: 'Revenue (UGX)',
                data: [
                    @for($i = 5; $i >= 0; $i--)
                    {{ \App\Models\Sale::where('user_id', auth()->id())->whereYear('sale_date', now()->subMonths($i)->year)->whereMonth('sale_date', now()->subMonths($i)->month)->sum('total') }},
                    @endfor
                ],
                backgroundColor: 'rgba(168, 85, 247, 0.7)',
                borderColor: 'rgb(168, 85, 247)',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { callback: v => 'UGX ' + v.toLocaleString() } } }
        }
    });
</script>
@endpush
