@extends('Admin.layout')

@section('title', 'Payments Management')

@section('content')
<style>
  :root {
    --primary: #6366f1;
    --primary-light: #818cf8;
    --success: #22c55e;
    --warning: #f59e0b;
    --danger: #ef4444;
    --info: #3b82f6;
    --panel: #1e2235;
    --panel2: #252a3e;
    --border: rgba(255,255,255,0.08);
    --text: #e2e8f0;
    --muted: #94a3b8;
  }
  .page-title { font-size: 22px; font-weight: 700; color: var(--text); margin: 0; }
  .page-subtitle { font-size: 13px; color: var(--muted); margin: 4px 0 0; }

  /* Summary Cards */
  .pay-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px,1fr)); gap: 16px; margin-bottom: 28px; }
  .pay-stat { background: var(--panel); border: 1px solid var(--border); border-radius: 14px; padding: 22px 24px; position: relative; overflow: hidden; }
  .pay-stat::before { content: ''; position: absolute; inset: 0; background: linear-gradient(135deg, rgba(99,102,241,.08), transparent); pointer-events: none; }
  .pay-stat-label { font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 8px; }
  .pay-stat-val { font-size: 26px; font-weight: 800; color: var(--text); }
  .pay-stat-icon { position: absolute; right: 18px; top: 18px; font-size: 24px; opacity: .18; }

  /* Filter Bar */
  .filter-bar { background: var(--panel); border: 1px solid var(--border); border-radius: 12px; padding: 18px 24px; margin-bottom: 24px; }
  .filter-row { display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end; }
  .filter-group { display: flex; flex-direction: column; gap: 5px; min-width: 140px; }
  .filter-group label { font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .05em; }
  .filter-group input, .filter-group select {
    padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px;
    background: var(--panel2); color: var(--text); font-size: 13px; outline: none;
    transition: border .2s;
  }
  .filter-group input:focus, .filter-group select:focus { border-color: var(--primary-light); }
  .btn-filter { padding: 9px 20px; background: var(--primary); color: #fff; border: none; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; transition: background .2s; }
  .btn-filter:hover { background: var(--primary-light); }
  .btn-add { padding: 9px 20px; background: var(--success); color: #fff; border: none; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; margin-left: auto; }

  /* Package Revenue */
  .pkg-revenue { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px,1fr)); gap: 12px; margin-bottom: 28px; }
  .pkg-card { background: var(--panel); border: 1px solid var(--border); border-radius: 12px; padding: 16px 18px; }
  .pkg-name { font-size: 12px; font-weight: 700; color: var(--primary-light); text-transform: capitalize; margin-bottom: 6px; }
  .pkg-amt { font-size: 20px; font-weight: 800; color: var(--text); }
  .pkg-count { font-size: 11px; color: var(--muted); margin-top: 4px; }

  /* Table */
  .card-panel { background: var(--panel); border: 1px solid var(--border); border-radius: 14px; overflow: hidden; margin-bottom: 28px; }
  .card-panel-header { padding: 18px 24px; font-size: 14px; font-weight: 700; color: var(--text); border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 10px; }
  table { width: 100%; border-collapse: collapse; }
  th { padding: 12px 16px; font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .04em; border-bottom: 1px solid var(--border); text-align: left; background: var(--panel2); }
  td { padding: 12px 16px; font-size: 13px; color: var(--text); border-bottom: 1px solid rgba(255,255,255,.04); vertical-align: middle; }
  tr:hover td { background: rgba(99,102,241,.04); }
  tr:last-child td { border-bottom: none; }

  /* Status badges */
  .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: capitalize; }
  .badge-paid     { background: rgba(34,197,94,.15); color: #22c55e; }
  .badge-pending  { background: rgba(245,158,11,.15); color: #f59e0b; }
  .badge-failed   { background: rgba(239,68,68,.15); color: #ef4444; }
  .badge-refunded { background: rgba(59,130,246,.15); color: #3b82f6; }
  .badge-cancelled{ background: rgba(148,163,184,.15); color: #94a3b8; }

  /* Action buttons */
  .btn-sm { padding: 5px 12px; border-radius: 6px; font-size: 11px; font-weight: 600; border: none; cursor: pointer; transition: opacity .2s; }
  .btn-sm:hover { opacity: .8; }
  .btn-verify { background: rgba(34,197,94,.15); color: #22c55e; }
  .btn-cancel  { background: rgba(239,68,68,.12); color: #ef4444; }

  /* Modal */
  .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.65); z-index: 1000; align-items: center; justify-content: center; }
  .modal-overlay.open { display: flex; }
  .modal { background: var(--panel2); border: 1px solid var(--border); border-radius: 16px; padding: 32px; width: 100%; max-width: 540px; box-shadow: 0 20px 60px rgba(0,0,0,.5); }
  .modal h3 { font-size: 17px; font-weight: 700; color: var(--text); margin: 0 0 22px; }
  .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px; }
  .form-group { display: flex; flex-direction: column; gap: 6px; }
  .form-group label { font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase; }
  .form-group input, .form-group select, .form-group textarea {
    padding: 9px 12px; border: 1px solid var(--border); border-radius: 8px;
    background: var(--panel); color: var(--text); font-size: 13px; outline: none; resize: none;
  }
  .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--primary-light); }
  .form-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
  .btn-cancel-modal { padding: 9px 20px; background: transparent; border: 1px solid var(--border); border-radius: 8px; color: var(--muted); cursor: pointer; font-weight: 600; }
  .btn-submit { padding: 9px 22px; background: var(--primary); color: #fff; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }

  .empty-state { padding: 56px; text-align: center; color: var(--muted); }
  .empty-state i { font-size: 40px; margin-bottom: 12px; opacity: .4; }
  .empty-state p { font-size: 14px; }

  .pagination-wrap { padding: 16px 24px; border-top: 1px solid var(--border); }
</style>

<!-- Header -->
<div style="margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between;">
  <div>
    <h1 class="page-title"><i class="fas fa-money-bill-wave" style="color:var(--primary);margin-right:10px;"></i>Payments Management</h1>
    <p class="page-subtitle">Track subscription billing from all tenants, grouped by package</p>
  </div>
  <button class="btn-add" onclick="document.getElementById('addPaymentModal').classList.add('open')">
    <i class="fas fa-plus" style="margin-right:6px;"></i> Record Payment
  </button>
</div>

<!-- Summary Cards -->
<div class="pay-stats">
  <div class="pay-stat">
    <div class="pay-stat-label">Total Collected</div>
    <div class="pay-stat-val">UGX {{ number_format($totalCollected) }}</div>
    <i class="fas fa-check-circle pay-stat-icon" style="color:var(--success);"></i>
  </div>
  <div class="pay-stat">
    <div class="pay-stat-label">Pending Payments</div>
    <div class="pay-stat-val">UGX {{ number_format($totalPending) }}</div>
    <i class="fas fa-clock pay-stat-icon" style="color:var(--warning);"></i>
  </div>
  <div class="pay-stat">
    <div class="pay-stat-label">Total Records</div>
    <div class="pay-stat-val">{{ $totalCount }}</div>
    <i class="fas fa-list pay-stat-icon" style="color:var(--primary);"></i>
  </div>
</div>

<!-- Package Revenue Breakdown -->
@if($packageRevenue->count() > 0)
<div style="margin-bottom: 8px; font-size: 12px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .05em;">Revenue by Package (paid)</div>
<div class="pkg-revenue">
  @foreach($packageRevenue as $pr)
  <div class="pkg-card">
    <div class="pkg-name"><i class="fas fa-box" style="margin-right:5px;"></i>{{ $pr->package_slug ?? 'Unknown' }}</div>
    <div class="pkg-amt">UGX {{ number_format($pr->total) }}</div>
    <div class="pkg-count">{{ $pr->count }} {{ $pr->count == 1 ? 'payment' : 'payments' }}</div>
  </div>
  @endforeach
</div>
@endif

<!-- Filters -->
<div class="filter-bar">
  <form method="GET" action="{{ route('admin.payments.index') }}">
    <div class="filter-row">
      <div class="filter-group">
        <label>Start Date</label>
        <input type="date" name="start_date" value="{{ $start_date }}">
      </div>
      <div class="filter-group">
        <label>End Date</label>
        <input type="date" name="end_date" value="{{ $end_date }}">
      </div>
      <div class="filter-group">
        <label>Package</label>
        <select name="package_slug">
          <option value="">All Packages</option>
          @foreach($packages as $pk)
            <option value="{{ $pk->slug }}" @selected($pkg == $pk->slug)>{{ $pk->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="filter-group">
        <label>Status</label>
        <select name="status">
          <option value="">All Statuses</option>
          @foreach(['pending','paid','failed','refunded','cancelled'] as $s)
            <option value="{{ $s }}" @selected($status == $s)>{{ ucfirst($s) }}</option>
          @endforeach
        </select>
      </div>
      <div class="filter-group">
        <label>Business</label>
        <select name="business_id">
          <option value="">All Businesses</option>
          @foreach($businesses as $b)
            <option value="{{ $b->id }}" @selected($biz_id == $b->id)>{{ $b->name }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <button type="submit" class="btn-filter"><i class="fas fa-filter" style="margin-right:5px;"></i>Filter</button>
      </div>
    </div>
  </form>
</div>

<!-- Payments Table -->
<div class="card-panel">
  <div class="card-panel-header">
    <i class="fas fa-receipt" style="color:var(--primary);"></i>
    Subscription Payments
    <span style="margin-left:auto;font-size:12px;color:var(--muted);">
      {{ $start_date }} → {{ $end_date }}
    </span>
  </div>
  @if($payments->count() > 0)
  <div style="overflow-x:auto;">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Business</th>
          <th>Package</th>
          <th>Amount</th>
          <th>Method</th>
          <th>Reference</th>
          <th>Status</th>
          <th>Period</th>
          <th>Paid At</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach($payments as $p)
        <tr>
          <td style="color:var(--muted);">#{{ $p->id }}</td>
          <td>
            <div style="font-weight:600;">{{ $p->business->name ?? '—' }}</div>
            <div style="font-size:11px;color:var(--muted);">{{ $p->business->email ?? '' }}</div>
          </td>
          <td>
            <span style="font-weight:600;color:var(--primary-light);text-transform:capitalize;">
              {{ $p->package_slug ?? '—' }}
            </span>
          </td>
          <td style="font-weight:700;">{{ $p->currency }} {{ number_format($p->amount) }}</td>
          <td>{{ $p->payment_method ?? '—' }}</td>
          <td style="font-size:12px;color:var(--muted);">{{ $p->reference ?? '—' }}</td>
          <td>
            <span class="badge badge-{{ $p->status }}">{{ $p->status }}</span>
          </td>
          <td style="font-size:12px;">
            @if($p->period_start && $p->period_end)
              {{ \Carbon\Carbon::parse($p->period_start)->format('M d') }} – {{ \Carbon\Carbon::parse($p->period_end)->format('M d, Y') }}
            @else
              —
            @endif
          </td>
          <td style="font-size:12px;">{{ $p->paid_at ? $p->paid_at->format('M d, Y') : '—' }}</td>
          <td>
            <div style="display:flex;gap:6px;">
              @if($p->status === 'pending')
              <form method="POST" action="{{ route('admin.payments.verify', $p->id) }}" style="display:inline;">
                @csrf @method('PATCH')
                <button type="submit" class="btn-sm btn-verify" onclick="return confirm('Mark this payment as paid/verified?')">
                  <i class="fas fa-check"></i> Verify
                </button>
              </form>
              @endif
              @if(!in_array($p->status, ['cancelled','refunded']))
              <form method="POST" action="{{ route('admin.payments.cancel', $p->id) }}" style="display:inline;">
                @csrf @method('PATCH')
                <button type="submit" class="btn-sm btn-cancel" onclick="return confirm('Cancel this payment?')">
                  <i class="fas fa-times"></i> Cancel
                </button>
              </form>
              @endif
            </div>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  @if($payments->hasPages())
  <div class="pagination-wrap">{{ $payments->links() }}</div>
  @endif
  @else
  <div class="empty-state">
    <i class="fas fa-money-bill-wave"></i>
    <p>No payment records found for the selected period.<br>
    <a href="#" onclick="document.getElementById('addPaymentModal').classList.add('open');return false;" style="color:var(--primary);">Record the first payment</a></p>
  </div>
  @endif
</div>

<!-- Add Payment Modal -->
<div class="modal-overlay" id="addPaymentModal">
  <div class="modal">
    <h3><i class="fas fa-plus-circle" style="color:var(--primary);margin-right:8px;"></i>Record New Payment</h3>
    <form method="POST" action="{{ route('admin.payments.record') }}">
      @csrf
      <div class="form-row">
        <div class="form-group" style="grid-column:1/-1;">
          <label>Business / Tenant</label>
          <select name="business_id" required>
            <option value="">Select business…</option>
            @foreach($businesses as $b)
              <option value="{{ $b->id }}">{{ $b->name }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Package</label>
          <select name="package_slug" required>
            <option value="">Select package…</option>
            @foreach($packages as $pk)
              <option value="{{ $pk->slug }}">{{ $pk->name }} (UGX {{ number_format($pk->price) }})</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label>Amount (UGX)</label>
          <input type="number" name="amount" min="0" step="100" placeholder="e.g. 50000" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Payment Method</label>
          <select name="payment_method" required>
            <option value="">Select…</option>
            <option value="Mobile Money">Mobile Money</option>
            <option value="Bank Transfer">Bank Transfer</option>
            <option value="Cash">Cash</option>
            <option value="Card">Card</option>
            <option value="Other">Other</option>
          </select>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="status" required>
            <option value="paid">Paid</option>
            <option value="pending">Pending</option>
            <option value="failed">Failed</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Period Start</label>
          <input type="date" name="period_start" value="{{ now()->format('Y-m-d') }}">
        </div>
        <div class="form-group">
          <label>Period End</label>
          <input type="date" name="period_end" value="{{ now()->addMonth()->format('Y-m-d') }}">
        </div>
      </div>
      <div class="form-group" style="margin-bottom:14px;">
        <label>Reference / Receipt No.</label>
        <input type="text" name="reference" placeholder="e.g. TXN-20240724-001">
      </div>
      <div class="form-group">
        <label>Notes (optional)</label>
        <textarea name="notes" rows="2" placeholder="Any extra info…"></textarea>
      </div>
      <div class="form-actions">
        <button type="button" class="btn-cancel-modal" onclick="document.getElementById('addPaymentModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn-submit"><i class="fas fa-save" style="margin-right:6px;"></i>Save Payment</button>
      </div>
    </form>
  </div>
</div>

<script>
  // Close modal on outside click
  document.getElementById('addPaymentModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('open');
  });
</script>
@endsection
