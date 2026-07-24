@extends('Admin.layout')

@section('title', 'Revenue Performance - Admin Panel')

@section('content')

<style>
  .page-header {
    margin-bottom: 32px;
  }
  .page-title {
    font-size: 28px;
    font-weight: 700;
    margin: 0;
    letter-spacing: -0.02em;
  }
  .page-subtitle {
    color: var(--muted);
    margin-top: 6px;
    font-size: 15px;
  }

  /* Filters Widget */
  .filters-card {
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
  }
  .filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    align-items: flex-end;
  }
  .filter-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }
  .filter-label {
    font-size: 13px;
    font-weight: 700;
    color: var(--text);
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }
  .filter-input,
  .filter-select {
    padding: 10px 14px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--panel);
    color: var(--text);
    font-size: 14px;
    transition: all 0.2s ease;
  }
  .filter-input:focus,
  .filter-select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(79,70,229,0.1);
  }
  .btn-filter {
    background: var(--primary);
    color: white;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
  }
  .btn-filter:hover {
    background: var(--primary-dark, #4338ca);
  }

  /* Metrics Summary Cards */
  .metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
  }
  .metric-card {
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    display: flex;
    align-items: center;
    gap: 20px;
  }
  .metric-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
  }
  .metric-icon.indigo {
    background: rgba(79,70,229,0.08);
    color: var(--primary);
  }
  .metric-icon.emerald {
    background: rgba(16,185,129,0.08);
    color: var(--success);
  }
  .metric-value {
    font-size: 24px;
    font-weight: 800;
    color: #111827;
  }
  .metric-title {
    font-size: 13px;
    font-weight: 600;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }

  /* Table Layout */
  .table-card {
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
  }
  .table-card-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .table-card-title {
    font-size: 16px;
    font-weight: 700;
    margin: 0;
  }
  .table-wrapper {
    overflow-x: auto;
  }
  table {
    width: 100%;
    border-collapse: collapse;
  }
  table th {
    background: rgba(0,0,0,0.02);
    padding: 14px 20px;
    text-align: left;
    font-size: 12px;
    font-weight: 700;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 1px solid var(--border);
  }
  table td {
    padding: 16px 20px;
    border-bottom: 1px solid rgba(0,0,0,0.02);
    font-size: 14px;
  }
  table tbody tr:hover {
    background: rgba(0,0,0,0.01);
  }
  .drilldown-btn {
    color: var(--primary);
    text-decoration: none;
    font-weight: 600;
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  .drilldown-btn:hover {
    text-decoration: underline;
  }
</style>

<div class="page-header">
  <h1 class="page-title">Revenue & Performance Reports</h1>
  <p class="page-subtitle">Track and audit transaction value generated across tenant businesses.</p>
</div>

<!-- Filters widget -->
<div class="filters-card">
  <form method="GET" action="{{ route('admin.reports.revenue') }}" data-pjax>
    <div class="filters-grid">
      <div class="filter-group">
        <label class="filter-label" for="business_id">Drilldown Business</label>
        <select class="filter-select" id="business_id" name="business_id">
          <option value="all" {{ $business_id === 'all' || empty($business_id) ? 'selected' : '' }}>All Businesses (Combined Aggregate)</option>
          @foreach($businesses as $b)
            <option value="{{ $b->id }}" {{ $business_id == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
          @endforeach
        </select>
      </div>

      <div class="filter-group">
        <label class="filter-label" for="start_date">Start Date</label>
        <input type="date" class="filter-input" id="start_date" name="start_date" value="{{ $start_date }}">
      </div>

      <div class="filter-group">
        <label class="filter-label" for="end_date">End Date</label>
        <input type="date" class="filter-input" id="end_date" name="end_date" value="{{ $end_date }}">
      </div>

      <div>
        <button type="submit" class="btn-filter">Generate Report</button>
      </div>
    </div>
  </form>
</div>

<!-- Core summary stats -->
<div class="metrics-grid">
  <div class="metric-card">
    <div class="metric-icon emerald">
      <i class="fas fa-coins"></i>
    </div>
    <div>
      <div class="metric-value">UGX {{ number_format($totalRevenue, 2) }}</div>
      <div class="metric-title">
        @if($selectedBusiness)
          Revenue ({{ $selectedBusiness->name }})
        @else
          Combined Cumulative Revenue
        @endif
      </div>
    </div>
  </div>

  <div class="metric-card">
    <div class="metric-icon indigo">
      <i class="fas fa-shopping-basket"></i>
    </div>
    <div>
      <div class="metric-value">{{ number_format($totalSalesCount) }}</div>
      <div class="metric-title">Logged Transactions Count</div>
    </div>
  </div>

  <div class="metric-card">
    <div class="metric-icon indigo" style="background: rgba(6,182,212,0.08); color: #06b6d4;">
      <i class="fas fa-calculator"></i>
    </div>
    <div>
      <div class="metric-value">
        UGX {{ $totalSalesCount > 0 ? number_format($totalRevenue / $totalSalesCount, 2) : '0.00' }}
      </div>
      <div class="metric-title">Average Value Per Sale</div>
    </div>
  </div>
</div>

<!-- Detailed Data Grid -->
<div class="table-card">
  <div class="table-card-header">
    <h3 class="table-card-title">
      @if($selectedBusiness)
        Daily Performance Log for: {{ $selectedBusiness->name }}
      @else
        Individual Business Revenue Contribution
      @endif
    </h3>
    <span style="font-size: 13px; color: var(--muted); font-weight: 500;">
      Period: {{ \Carbon\Carbon::parse($start_date)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($end_date)->format('M d, Y') }}
    </span>
  </div>

  <div class="table-wrapper">
    @if($selectedBusiness)
      <!-- Drilldown Detail -->
      <table>
        <thead>
          <tr>
            <th>Date</th>
            <th>Number of Sales</th>
            <th>Revenue Generated</th>
          </tr>
        </thead>
        <tbody>
          @forelse($reportData as $row)
            <tr>
              <td style="font-weight: 600;">{{ \Carbon\Carbon::parse($row['date'])->format('F d, Y') }}</td>
              <td>{{ number_format($row['sales_count']) }} transactions</td>
              <td style="font-weight: 700; color: var(--success);">UGX {{ number_format($row['revenue'], 2) }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="3" style="text-align: center; color: var(--muted); padding: 32px;">No transactions recorded by this business in the selected timeframe.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    @else
      <!-- Combined aggregate -->
      <table>
        <thead>
          <tr>
            <th>Business Name</th>
            <th>Subscription Package</th>
            <th>Total Transaction Count</th>
            <th>Total Revenue Contribution</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($reportData as $row)
            <tr>
              <td style="font-weight: 600;">{{ $row['business']->name }}</td>
              <td>
                <span class="badge" style="background: rgba(79,70,229,0.08); color: var(--primary); padding: 4px 10px; border-radius: 6px; font-weight: 600; font-size: 12px; text-transform: uppercase;">
                  {{ $row['business']->subscription_plan }}
                </span>
              </td>
              <td>{{ number_format($row['sales_count']) }} sales</td>
              <td style="font-weight: 700; color: var(--success);">UGX {{ number_format($row['revenue'], 2) }}</td>
              <td>
                <a href="{{ route('admin.reports.revenue', ['business_id' => $row['business']->id, 'start_date' => $start_date, 'end_date' => $end_date]) }}" class="drilldown-btn" data-pjax>
                  <i class="fas fa-search-plus"></i> Drill Down Performance
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" style="text-align: center; color: var(--muted); padding: 32px;">No active businesses configured.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    @endif
  </div>
</div>

@endsection
