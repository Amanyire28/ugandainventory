@extends('admin.layout')

@section('title', 'Business Monitor - Admin Panel')

@section('content')
<style>
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
  }
  .page-title {
    font-size: 28px;
    font-weight: 700;
    margin: 0;
    letter-spacing: -0.02em;
    display: flex;
    align-items: center;
    gap: 12px;
  }
  .page-title span.badge {
    font-size: 13px;
    padding: 4px 12px;
    border-radius: 9999px;
  }
  .badge-success { background: rgba(16, 185, 129, 0.1); color: var(--success); }
  .badge-danger { background: rgba(239, 68, 68, 0.1); color: var(--danger); }
  
  .page-subtitle {
    color: var(--muted);
    margin-top: 6px;
    font-size: 15px;
  }

  .back-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--muted);
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    transition: color 0.2s;
  }
  .back-btn:hover {
    color: var(--primary);
  }

  /* Stats Grid */
  .monitor-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
  }
  .m-stat-card {
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 20px;
    box-shadow: var(--card-shadow);
  }
  .m-stat-label {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--muted);
    letter-spacing: 0.05em;
  }
  .m-stat-val {
    font-size: 26px;
    font-weight: 800;
    color: #111827;
    margin-top: 8px;
  }

  /* Tabs Layout */
  .tabs-nav {
    display: flex;
    gap: 8px;
    border-bottom: 1px solid var(--border);
    margin-bottom: 24px;
  }
  .tab-btn {
    background: transparent;
    border: none;
    border-bottom: 2px solid transparent;
    padding: 12px 20px;
    font-size: 14px;
    font-weight: 600;
    color: var(--muted);
    cursor: pointer;
    transition: all 0.2s;
  }
  .tab-btn:hover {
    color: var(--primary);
  }
  .tab-btn.active {
    color: var(--primary);
    border-bottom-color: var(--primary);
  }
  
  .tab-content {
    display: none;
  }
  .tab-content.active {
    display: block;
  }

  /* Table styling */
  .card-panel {
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: var(--card-shadow);
    margin-bottom: 32px;
  }
  .card-panel-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--border);
    font-size: 16px;
    font-weight: 700;
    color: #111827;
  }
  table {
    width: 100%;
    border-collapse: collapse;
  }
  table th {
    padding: 14px 20px;
    background: #f9fafb;
    font-size: 12px;
    font-weight: 700;
    color: var(--muted);
    text-transform: uppercase;
    text-align: left;
    border-bottom: 1px solid var(--border);
  }
  table td {
    padding: 14px 20px;
    border-bottom: 1px solid rgba(0,0,0,0.02);
    font-size: 13px;
  }
  table tr:last-child td {
    border-bottom: none;
  }

  /* Danger Zone */
  .danger-zone {
    border: 1px solid rgba(239, 68, 68, 0.2);
    border-radius: 16px;
    background: rgba(239, 68, 68, 0.02);
    padding: 24px;
  }
  .danger-title {
    color: var(--danger);
    font-size: 18px;
    font-weight: 700;
    margin-top: 0;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .danger-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 0;
    border-bottom: 1px solid rgba(239, 68, 68, 0.1);
  }
  .danger-item:last-child {
    border-bottom: none;
  }
  .danger-text h4 {
    margin: 0;
    font-size: 14px;
    font-weight: 700;
    color: #111827;
  }
  .danger-text p {
    margin: 4px 0 0 0;
    font-size: 13px;
    color: var(--muted);
  }
  .btn-danger {
    background: var(--danger);
    color: white;
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 13px;
    border: none;
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(239, 68, 68, 0.25);
    transition: all 0.2s;
  }
  .btn-danger:hover {
    background: #dc2626;
    box-shadow: 0 4px 10px rgba(239, 68, 68, 0.35);
  }

  /* Logs view */
  .log-item {
    padding: 14px 20px;
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
  }
  .log-item:last-child {
    border-bottom: none;
  }
  .log-meta {
    font-family: monospace;
    font-size: 12px;
    color: var(--muted);
  }
  .log-action {
    font-weight: 600;
    font-size: 13px;
    color: #111827;
  }
  .log-details {
    margin-top: 6px;
    font-size: 12px;
    color: #4b5563;
  }
</style>

<div class="page-header">
  <div>
    <a href="{{ route('admin.businesses.index') }}" class="back-btn">
      <i class="fas fa-arrow-left"></i> Back to Tenants List
    </a>
    <h1 class="page-title" style="margin-top: 12px;">
      {{ $business->name }} Console
      <span class="badge {{ $business->is_active ? 'badge-success' : 'badge-danger' }}">
        {{ $business->is_active ? 'Active' : 'Suspended' }}
      </span>
    </h1>
    <p class="page-subtitle">Monitoring logs, transactions, settings resets, and user operations.</p>
  </div>
</div>

<!-- Date Filter Panel -->
<div style="background: var(--panel); border: 1px solid var(--border); border-radius: 12px; padding: 18px 24px; margin-bottom: 24px; box-shadow: 0 1px 2px rgba(0,0,0,0.04);">
  <form method="GET" action="{{ route('admin.businesses.monitor', $business) }}" data-pjax>
    <div style="display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap;">
      <div style="display: flex; flex-direction: column; gap: 6px;">
        <label style="font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em;">Start Date</label>
        <input type="date" name="start_date" value="{{ $start_date }}" style="padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px; background: var(--panel); color: var(--text); font-size: 13px;">
      </div>
      <div style="display: flex; flex-direction: column; gap: 6px;">
        <label style="font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em;">End Date</label>
        <input type="date" name="end_date" value="{{ $end_date }}" style="padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px; background: var(--panel); color: var(--text); font-size: 13px;">
      </div>
      <div>
        <button type="submit" style="background: var(--primary); color: white; padding: 9px 18px; border: none; border-radius: 6px; font-weight: 600; font-size: 13px; cursor: pointer;">Filter Period</button>
      </div>
      <div style="margin-left: auto; font-size: 13px; color: var(--muted); font-weight: 500; align-self: center;">
        Showing performance data from: <strong style="color: var(--text);">{{ \Carbon\Carbon::parse($start_date)->format('M d, Y') }}</strong> to <strong style="color: var(--text);">{{ \Carbon\Carbon::parse($end_date)->format('M d, Y') }}</strong>
      </div>
    </div>
  </form>
</div>

<!-- Stats Indicators -->
<div class="monitor-stats">
  <div class="m-stat-card">
    <div class="m-stat-label">Active Users</div>
    <div class="m-stat-val">{{ $users->count() }}</div>
  </div>
  <div class="m-stat-card">
    <div class="m-stat-label">Sales Recorded</div>
    <div class="m-stat-val">{{ $totalSales }}</div>
  </div>
  <div class="m-stat-card">
    <div class="m-stat-label">Total Revenue</div>
    <div class="m-stat-val">UGX {{ number_format($totalRevenue) }}</div>
  </div>
  <div class="m-stat-card">
    <div class="m-stat-label">Invoices Raised</div>
    <div class="m-stat-val">{{ $totalInvoices }}</div>
  </div>
</div>

<!-- Tabs Navigation -->
<div class="tabs-nav">
  <button class="tab-btn active" onclick="switchTab(event, 'sales-tab')">Sales & Transactions</button>
  <button class="tab-btn" onclick="switchTab(event, 'users-tab')">User List</button>
  <button class="tab-btn" onclick="switchTab(event, 'audit-tab')">Audit logs</button>
  <button class="tab-btn" onclick="switchTab(event, 'danger-tab')">Danger zone</button>
</div>

<!-- Sales Tab Content -->
<div id="sales-tab" class="tab-content active">
  <div class="card-panel">
    <div class="card-panel-header">Recent Sales Logs</div>
    <div style="overflow-x: auto;">
      <table>
        <thead>
          <tr>
            <th>Receipt No</th>
            <th>Grand Total</th>
            <th>Payment Status</th>
            <th>Staff Agent</th>
            <th>Date Created</th>
          </tr>
        </thead>
        <tbody>
          @forelse($recentSales as $sale)
            <tr>
              <td style="font-weight: 600; font-family: monospace;">#{{ $sale->sale_number }}</td>
              <td>UGX {{ number_format($sale->total) }}</td>
              <td>
                <span class="badge" style="background: rgba(16, 185, 129, 0.1); color: var(--success); font-weight: 600; font-size: 11px; padding: 2px 8px; border-radius: 4px;">
                  Paid
                </span>
              </td>
              <td>{{ $sale->user->name ?? 'System' }}</td>
              <td>{{ $sale->created_at->format('M d, Y H:i') }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="5" style="text-align: center; color: var(--muted); padding: 32px;">No sales transactions logged.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Users Tab Content -->
<div id="users-tab" class="tab-content">
  <div class="card-panel">
    <div class="card-panel-header">Tenant User Accounts</div>
    <div style="overflow-x: auto;">
      <table>
        <thead>
          <tr>
            <th>User Account</th>
            <th>Access Role</th>
            <th>Account Status</th>
            <th>Last Active Logged</th>
          </tr>
        </thead>
        <tbody>
          @forelse($users as $usr)
            <tr>
              <td style="font-weight: 600;">{{ $usr->name }} <br><span style="font-size: 11px; font-weight: 400; color: var(--muted);">{{ $usr->email }}</span></td>
              <td>
                <span style="font-weight: 600; font-size: 12px; color: var(--primary);">
                  {{ $usr->role->display_name ?? 'Staff User' }}
                </span>
              </td>
              <td>
                <span class="badge {{ $usr->is_active ? 'badge-success' : 'badge-danger' }}" style="padding: 2px 8px; border-radius: 4px; font-size: 11px;">
                  {{ $usr->is_active ? 'Active' : 'Suspended' }}
                </span>
              </td>
              <td>{{ $usr->last_login_at ? $usr->last_login_at->format('M d, Y H:i') : 'Never' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="4" style="text-align: center; color: var(--muted); padding: 32px;">No user accounts mapped.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Audit Log Tab Content -->
<div id="audit-tab" class="tab-content">
  <div class="card-panel">
    <div class="card-panel-header">Tenant Audit Trail (storage/logs/activity.log)</div>
    <div>
      @forelse($activityLogs as $log)
        <div class="log-item">
          <div>
            <div class="log-action">
              <span style="color: var(--primary);">[{{ $log['role'] }}]</span> {{ $log['user_name'] }} - {{ $log['action_title'] }}
            </div>
            <div class="log-details">
              <strong>URL:</strong> {{ $log['url'] }} <br>
              <strong>IP Address:</strong> {{ $log['ip_address'] }} | <strong>Device:</strong> {{ Str::limit($log['user_agent'], 80) }}
            </div>
          </div>
          <div class="log-meta">
            {{ $log['timestamp'] }}
          </div>
        </div>
      @empty
        <div style="text-align: center; color: var(--muted); padding: 48px;">
          <i class="fas fa-file-invoice" style="font-size: 24px; margin-bottom: 12px; display: block;"></i>
          No audit activities currently logged for this tenant business in `activity.log`.
        </div>
      @endforelse
    </div>
  </div>
</div>

<!-- Danger Zone Tab Content -->
<div id="danger-tab" class="tab-content">
  <div class="danger-zone">
    <h3 class="danger-title"><i class="fas fa-exclamation-triangle"></i> Administrative Actions - Danger Zone</h3>
    <p style="color: var(--muted); font-size: 14px; margin-bottom: 24px;">These operations have irreversible effects. Exercise caution when modifying live tenant data.</p>
    
    <div class="danger-item">
      <div class="danger-text">
        <h4>Wipe / Reset Business Transactions</h4>
        <p>Permanently deletes all Sales, Invoices, payments, expenses, and stock taking sessions for this business. Sets quantities to zero.</p>
      </div>
      <form action="{{ route('admin.businesses.reset_transactions', $business) }}" method="POST" onsubmit="return confirm('WARNING: All sales logs, invoices, and payments will be permanently deleted for this business. This cannot be undone! Are you sure?');">
        @csrf
        <button type="submit" class="btn-danger">Reset Transactions</button>
      </form>
    </div>

    <div class="danger-item">
      <div class="danger-text">
        <h4>Reset Configuration settings</h4>
        <p>Wipe custom SMTP integrations, reset tax configuration settings (toggles tax off and resets rates to 18%).</p>
      </div>
      <form action="{{ route('admin.businesses.reset_settings', $business) }}" method="POST" onsubmit="return confirm('Wipe SMTP configs and reset tax configurations to default values?');">
        @csrf
        <button type="submit" class="btn-danger">Reset settings</button>
      </form>
    </div>
  </div>
</div>

<script>
  function switchTab(evt, tabId) {
    // Hide all contents
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    // Deactivate all buttons
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    
    // Show active content
    document.getElementById(tabId).classList.add('active');
    // Set active button state
    evt.currentTarget.classList.add('active');
  }
</script>
@endsection
