@extends('admin.layout')

@section('title', 'Payments Management - Admin Panel')

@section('content')
<style>
  /* ── Page header ─────────────────────────────── */
  .page-header { margin-bottom: 32px; }
  .page-title  { font-size: 28px; font-weight: 700; margin: 0; letter-spacing: -.02em; }
  .page-subtitle { color: var(--muted); margin-top: 6px; font-size: 15px; }

  /* ── Summary stat cards ──────────────────────── */
  .pay-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 28px; }
  .pay-stat  {
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 22px 24px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 1px 2px rgba(0,0,0,.04), 0 4px 12px rgba(0,0,0,.06);
  }
  .pay-stat-icon {
    position: absolute; right: 18px; top: 50%; transform: translateY(-50%);
    font-size: 36px; opacity: .12;
  }
  .pay-stat-label { font-size: 12px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; margin-bottom: 8px; }
  .pay-stat-val   { font-size: 24px; font-weight: 800; color: var(--text); }

  /* ── Package revenue cards ───────────────────── */
  .section-label { font-size: 12px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; margin-bottom: 10px; }
  .pkg-revenue   { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; margin-bottom: 28px; }
  .pkg-card      {
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 16px 18px;
    box-shadow: 0 1px 2px rgba(0,0,0,.04);
  }
  .pkg-name  { font-size: 12px; font-weight: 700; color: var(--primary); text-transform: capitalize; margin-bottom: 6px; }
  .pkg-amt   { font-size: 18px; font-weight: 800; color: var(--text); }
  .pkg-count { font-size: 11px; color: var(--muted); margin-top: 4px; }

  /* ── Filter bar ──────────────────────────────── */
  .filters-section {
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 20px 24px;
    margin-bottom: 24px;
    box-shadow: 0 1px 2px rgba(0,0,0,.04);
  }
  .filters-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; align-items: flex-end; }
  .filter-group { display: flex; flex-direction: column; gap: 6px; }
  .filter-label { font-size: 13px; font-weight: 600; color: var(--text); }
  .filter-input,
  .filter-select {
    padding: 10px 12px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--panel);
    color: var(--text);
    font-size: 14px;
    transition: border .2s, box-shadow .2s;
  }
  .filter-input:focus, .filter-select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(79,70,229,.1);
  }

  /* ── Buttons ─────────────────────────────────── */
  .btn-primary {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 10px 20px; background: var(--primary); color: #fff;
    border: none; border-radius: 8px; font-weight: 600; font-size: 14px;
    cursor: pointer; transition: opacity .2s;
  }
  .btn-primary:hover { opacity: .88; }
  .btn-success {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 10px 20px; background: var(--success); color: #fff;
    border: none; border-radius: 8px; font-weight: 600; font-size: 14px;
    cursor: pointer; transition: opacity .2s;
  }
  .btn-success:hover { opacity: .88; }

  /* ── Table card ──────────────────────────────── */
  .table-card {
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 2px rgba(0,0,0,.04), 0 8px 24px rgba(0,0,0,.06);
    margin-bottom: 28px;
  }
  .table-card-header {
    padding: 18px 24px;
    font-size: 15px; font-weight: 700; color: var(--text);
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; gap: 10px;
  }
  .table-card-header span.right { margin-left: auto; font-size: 13px; color: var(--muted); font-weight: 400; }
  table { width: 100%; border-collapse: collapse; }
  table thead { background: linear-gradient(135deg, rgba(79,70,229,.06) 0%, rgba(59,130,246,.03) 100%); }
  table th  { padding: 13px 16px; text-align: left; font-size: 12px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid var(--border); }
  table td  { padding: 14px 16px; border-bottom: 1px solid var(--border); font-size: 14px; color: var(--text); }
  table tbody tr:hover td { background: rgba(79,70,229,.03); }
  table tbody tr:last-child td { border-bottom: none; }

  /* ── Badges ──────────────────────────────────── */
  .badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; border-radius: 20px;
    font-size: 12px; font-weight: 600; text-transform: capitalize;
  }
  .badge-paid      { background: rgba(16,185,129,.12); color: var(--success); }
  .badge-pending   { background: rgba(245,158,11,.12);  color: var(--warning); }
  .badge-failed    { background: rgba(239,68,68,.12);   color: var(--danger);  }
  .badge-refunded  { background: rgba(59,130,246,.12);  color: var(--info);    }
  .badge-cancelled { background: rgba(148,163,184,.12); color: var(--muted);   }

  /* ── Row action buttons ──────────────────────── */
  .actions-cell { display: flex; align-items: center; gap: 6px; }
  .action-btn {
    display: inline-flex; align-items: center; justify-content: center;
    gap: 4px; padding: 5px 12px;
    border-radius: 6px; border: 1px solid var(--border);
    background: transparent; color: var(--text); cursor: pointer;
    font-size: 12px; font-weight: 600; transition: all .2s;
  }
  .action-btn.verify:hover { background: rgba(16,185,129,.1); border-color: var(--success); color: var(--success); }
  .action-btn.cancel:hover { background: rgba(239,68,68,.1);  border-color: var(--danger);  color: var(--danger);  }

  /* ── Empty state ─────────────────────────────── */
  .empty-state { padding: 64px 24px; text-align: center; color: var(--muted); }
  .empty-state i { font-size: 44px; opacity: .3; display: block; margin-bottom: 14px; }
  .empty-state p { font-size: 15px; margin: 0 0 18px; }
  .empty-state a { color: var(--primary); font-weight: 600; }

  /* ── Modal ───────────────────────────────────── */
  .modal-overlay {
    display: none; position: fixed; inset: 0;
    background: var(--overlay); z-index: 1000;
    align-items: center; justify-content: center;
  }
  .modal-overlay.open { display: flex; }
  .modal-box {
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 16px; padding: 32px;
    width: 100%; max-width: 560px;
    box-shadow: 0 24px 64px rgba(0,0,0,.25);
    max-height: 90vh; overflow-y: auto;
  }
  .modal-box h3 { font-size: 18px; font-weight: 700; color: var(--text); margin: 0 0 24px; }
  .form-row  { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px; }
  .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 14px; }
  .form-group label { font-size: 13px; font-weight: 600; color: var(--text); }
  .form-group input,
  .form-group select,
  .form-group textarea {
    padding: 10px 12px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--panel);
    color: var(--text);
    font-size: 14px;
    transition: border .2s;
    resize: none;
  }
  .form-group input:focus,
  .form-group select:focus,
  .form-group textarea:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79,70,229,.1); }
  .form-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 8px; }
  .btn-secondary {
    padding: 10px 20px; background: transparent; border: 1px solid var(--border);
    border-radius: 8px; color: var(--muted); cursor: pointer; font-weight: 600; font-size: 14px;
    transition: border-color .2s;
  }
  .btn-secondary:hover { border-color: var(--primary); color: var(--primary); }

  .pagination-wrap { padding: 16px 24px; border-top: 1px solid var(--border); }
</style>

<!-- Page header -->
<div class="page-header" style="display:flex; align-items:flex-start; justify-content:space-between; gap:16px;">
  <div>
    <h1 class="page-title">
      <i class="fas fa-money-bill-wave" style="color:var(--primary);margin-right:10px;font-size:22px;"></i>
      Payments Management
    </h1>
    <p class="page-subtitle">Track all subscription billing from tenants, grouped by package</p>
  </div>
  <button class="btn-success" onclick="document.getElementById('addPaymentModal').classList.add('open')">
    <i class="fas fa-plus"></i> Record Payment
  </button>
</div>

<!-- Summary cards -->
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
    <div class="pay-stat-val">{{ number_format($totalCount) }}</div>
    <i class="fas fa-receipt pay-stat-icon" style="color:var(--primary);"></i>
  </div>
</div>

<!-- ── Awaiting Approval (submitted by businesses) ──────── -->
@if($pendingFromBusinesses->count() > 0)
<div style="background: rgba(245,158,11,.06); border: 2px solid var(--warning); border-radius: 14px; padding: 20px 24px; margin-bottom: 28px;">
  <div style="display:flex; align-items:center; gap:10px; margin-bottom:18px;">
    <i class="fas fa-bell" style="color:var(--warning); font-size:18px;"></i>
    <h3 style="margin:0; font-size:16px; font-weight:700; color:var(--text);">
      Awaiting Approval — {{ $pendingFromBusinesses->count() }} payment{{ $pendingFromBusinesses->count() > 1 ? 's' : '' }} from businesses
    </h3>
  </div>
  <div style="display:flex; flex-direction:column; gap:14px;">
    @foreach($pendingFromBusinesses as $p)
    <div style="background:var(--panel); border:1px solid var(--border); border-radius:12px; padding:18px 20px;">
      <div style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-start;">
        {{-- Proof image thumbnail --}}
        @if($p->proof_image)
        <a href="{{ asset('storage/'.$p->proof_image) }}" target="_blank"
           style="flex-shrink:0; display:block; width:80px; height:80px; border-radius:8px; overflow:hidden; border:2px solid var(--border);">
          <img src="{{ asset('storage/'.$p->proof_image) }}" alt="Proof" style="width:100%;height:100%;object-fit:cover;">
        </a>
        @else
        <div style="flex-shrink:0; width:80px; height:80px; border-radius:8px; background:var(--border); display:flex; align-items:center; justify-content:center; font-size:24px; color:var(--muted);">
          <i class="fas fa-image"></i>
        </div>
        @endif
        {{-- Info --}}
        <div style="flex:1; min-width:180px;">
          <div style="font-weight:700; font-size:15px; color:var(--text);">{{ $p->business->name ?? '—' }}</div>
          <div style="font-size:12px; color:var(--muted); margin-top:2px;">{{ $p->business->email ?? '' }}</div>
          <div style="margin-top:8px; display:flex; flex-wrap:wrap; gap:10px;">
            <span style="font-size:13px;"><strong style="color:var(--text);">Package:</strong> <span style="color:var(--primary); font-weight:600; text-transform:capitalize;">{{ $p->package_slug }}</span></span>
            <span style="font-size:13px;"><strong style="color:var(--text);">Amount:</strong> {{ $p->currency }} {{ number_format($p->amount) }}</span>
            <span style="font-size:13px;"><strong style="color:var(--text);">Method:</strong> {{ $p->payment_method }}</span>
            @if($p->reference)
            <span style="font-size:12px; color:var(--muted);">Ref: {{ $p->reference }}</span>
            @endif
          </div>
          @if($p->period_start && $p->period_end)
          <div style="font-size:12px; color:var(--muted); margin-top:4px;">Period: {{ $p->period_start->format('M d, Y') }} – {{ $p->period_end->format('M d, Y') }}</div>
          @endif
          @if($p->notes)
          <div style="font-size:12px; color:var(--muted); margin-top:4px; font-style:italic;">{{ $p->notes }}</div>
          @endif
          @if($p->submittedByUser)
          <div style="font-size:11px; color:var(--muted); margin-top:4px;">Submitted by: {{ $p->submittedByUser->name }} · {{ $p->created_at->diffForHumans() }}</div>
          @endif
        </div>
        {{-- Actions --}}
        <div style="display:flex; flex-direction:column; gap:8px; flex-shrink:0;">
          <form method="POST" action="{{ route('admin.payments.approve', $p->id) }}">
            @csrf @method('PATCH')
            <button type="submit" class="action-btn verify" style="width:120px; justify-content:center; padding:8px 12px;"
              onclick="return confirm('Approve this payment and activate the business subscription?')">
              <i class="fas fa-check"></i> Approve
            </button>
          </form>
          <button type="button" class="action-btn cancel" style="width:120px; justify-content:center; padding:8px 12px;"
            onclick="openRejectModal({{ $p->id }})">
            <i class="fas fa-times"></i> Reject
          </button>
          @if($p->proof_image)
          <a href="{{ asset('storage/'.$p->proof_image) }}" target="_blank"
            style="font-size:11px; color:var(--info); text-align:center; text-decoration:none;">
            <i class="fas fa-expand-alt"></i> Full image
          </a>
          @endif
        </div>
      </div>
    </div>
    @endforeach
  </div>
</div>
@endif

<!-- Rejection modal (shared) -->
<div class="modal-overlay" id="rejectModal">
  <div class="modal-box" style="max-width:440px;">
    <h3><i class="fas fa-times-circle" style="color:var(--danger);margin-right:8px;"></i>Reject Payment</h3>
    <p style="font-size:14px; color:var(--muted); margin-bottom:18px;">Please provide a reason so the business knows why their payment was rejected.</p>
    <form id="rejectForm" method="POST" action="">
      @csrf @method('PATCH')
      <div class="form-group">
        <label>Rejection Reason *</label>
        <textarea name="rejection_reason" rows="3" required placeholder="e.g. Wrong amount, could not verify transaction…"></textarea>
      </div>
      <div class="form-actions">
        <button type="button" class="btn-secondary" onclick="document.getElementById('rejectModal').classList.remove('open')">Cancel</button>
        <button type="submit" style="padding:10px 20px; background:var(--danger); color:#fff; border:none; border-radius:8px; font-weight:600; cursor:pointer;">
          <i class="fas fa-times"></i> Reject Payment
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Revenue by package -->
@if($packageRevenue->count() > 0)
<div class="section-label">Revenue by Package (paid)</div>
<div class="pkg-revenue">
  @foreach($packageRevenue as $pr)
  <div class="pkg-card">
    <div class="pkg-name"><i class="fas fa-box" style="margin-right:5px;opacity:.6;"></i>{{ $pr->package_slug ?? 'Unknown' }}</div>
    <div class="pkg-amt">UGX {{ number_format($pr->total) }}</div>
    <div class="pkg-count">{{ $pr->count }} {{ $pr->count == 1 ? 'payment' : 'payments' }}</div>
  </div>
  @endforeach
</div>
@endif

<!-- Filters -->
<div class="filters-section">
  <form method="GET" action="{{ route('admin.payments.index') }}">
    <div class="filters-grid">
      <div class="filter-group">
        <label class="filter-label">Start Date</label>
        <input type="date" name="start_date" value="{{ $start_date }}" class="filter-input">
      </div>
      <div class="filter-group">
        <label class="filter-label">End Date</label>
        <input type="date" name="end_date" value="{{ $end_date }}" class="filter-input">
      </div>
      <div class="filter-group">
        <label class="filter-label">Package</label>
        <select name="package_slug" class="filter-select">
          <option value="">All Packages</option>
          @foreach($packages as $pk)
            <option value="{{ $pk->slug }}" @selected($pkg == $pk->slug)>{{ $pk->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="filter-group">
        <label class="filter-label">Status</label>
        <select name="status" class="filter-select">
          <option value="">All Statuses</option>
          @foreach(['pending','paid','failed','refunded','cancelled'] as $s)
            <option value="{{ $s }}" @selected($status == $s)>{{ ucfirst($s) }}</option>
          @endforeach
        </select>
      </div>
      <div class="filter-group">
        <label class="filter-label">Business</label>
        <select name="business_id" class="filter-select">
          <option value="">All Businesses</option>
          @foreach($businesses as $b)
            <option value="{{ $b->id }}" @selected($biz_id == $b->id)>{{ $b->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="filter-group" style="justify-content:flex-end;">
        <button type="submit" class="btn-primary" style="width:100%;">
          <i class="fas fa-filter"></i> Apply Filter
        </button>
      </div>
    </div>
  </form>
</div>

<!-- Payments table -->
<div class="table-card">
  <div class="table-card-header">
    <i class="fas fa-receipt" style="color:var(--primary);"></i>
    Subscription Payments
    <span class="right">{{ \Carbon\Carbon::parse($start_date)->format('M d, Y') }} → {{ \Carbon\Carbon::parse($end_date)->format('M d, Y') }}</span>
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
          <td style="color:var(--muted);font-size:13px;">#{{ $p->id }}</td>
          <td>
            <div style="font-weight:600;">{{ $p->business->name ?? '—' }}</div>
            <div style="font-size:12px;color:var(--muted);">{{ $p->business->email ?? '' }}</div>
          </td>
          <td>
            <span style="font-weight:700;color:var(--primary);text-transform:capitalize;">
              {{ $p->package_slug ?? '—' }}
            </span>
          </td>
          <td style="font-weight:700;">{{ $p->currency }} {{ number_format($p->amount) }}</td>
          <td style="color:var(--muted);">{{ $p->payment_method ?? '—' }}</td>
          <td style="font-size:12px;color:var(--muted);">{{ $p->reference ?? '—' }}</td>
          <td><span class="badge badge-{{ $p->status }}">{{ $p->status }}</span></td>
          <td style="font-size:12px;white-space:nowrap;">
            @if($p->period_start && $p->period_end)
              {{ \Carbon\Carbon::parse($p->period_start)->format('M d') }} – {{ \Carbon\Carbon::parse($p->period_end)->format('M d, Y') }}
            @else —
            @endif
          </td>
          <td style="font-size:12px;white-space:nowrap;">{{ $p->paid_at ? $p->paid_at->format('M d, Y') : '—' }}</td>
          <td>
            <div class="actions-cell">
              @if($p->status === 'pending')
              <form method="POST" action="{{ route('admin.payments.verify', $p->id) }}">
                @csrf @method('PATCH')
                <button type="submit" class="action-btn verify"
                  onclick="return confirm('Mark this payment as verified/paid?')">
                  <i class="fas fa-check"></i> Verify
                </button>
              </form>
              @endif
              @if(!in_array($p->status, ['cancelled','refunded']))
              <form method="POST" action="{{ route('admin.payments.cancel', $p->id) }}">
                @csrf @method('PATCH')
                <button type="submit" class="action-btn cancel"
                  onclick="return confirm('Cancel this payment record?')">
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
    <p>No payment records found for the selected period.</p>
    <a href="#" onclick="document.getElementById('addPaymentModal').classList.add('open');return false;">
      <i class="fas fa-plus"></i> Record the first payment
    </a>
  </div>
  @endif
</div>

<!-- ── Add Payment Modal ──────────────────────────────── -->
<div class="modal-overlay" id="addPaymentModal">
  <div class="modal-box">
    <h3><i class="fas fa-plus-circle" style="color:var(--primary);margin-right:8px;"></i>Record New Payment</h3>
    <form method="POST" action="{{ route('admin.payments.record') }}">
      @csrf
      <div class="form-group">
        <label>Business / Tenant *</label>
        <select name="business_id" required>
          <option value="">Select business…</option>
          @foreach($businesses as $b)
            <option value="{{ $b->id }}">{{ $b->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-row">
        <div class="form-group" style="margin-bottom:0;">
          <label>Package *</label>
          <select name="package_slug" required>
            <option value="">Select package…</option>
            @foreach($packages as $pk)
              <option value="{{ $pk->slug }}">{{ $pk->name }} (UGX {{ number_format($pk->price) }})</option>
            @endforeach
          </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
          <label>Amount (UGX) *</label>
          <input type="number" name="amount" min="0" step="100" placeholder="e.g. 50000" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group" style="margin-bottom:0;">
          <label>Payment Method *</label>
          <select name="payment_method" required>
            <option value="">Select…</option>
            <option value="Mobile Money">Mobile Money</option>
            <option value="Bank Transfer">Bank Transfer</option>
            <option value="Cash">Cash</option>
            <option value="Card">Card</option>
            <option value="Other">Other</option>
          </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
          <label>Status *</label>
          <select name="status" required>
            <option value="paid">Paid</option>
            <option value="pending">Pending</option>
            <option value="failed">Failed</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group" style="margin-bottom:0;">
          <label>Period Start</label>
          <input type="date" name="period_start" value="{{ now()->format('Y-m-d') }}">
        </div>
        <div class="form-group" style="margin-bottom:0;">
          <label>Period End</label>
          <input type="date" name="period_end" value="{{ now()->addMonth()->format('Y-m-d') }}">
        </div>
      </div>
      <div class="form-group">
        <label>Reference / Receipt No.</label>
        <input type="text" name="reference" placeholder="e.g. TXN-20240724-001">
      </div>
      <div class="form-group">
        <label>Notes (optional)</label>
        <textarea name="notes" rows="2" placeholder="Any extra information…"></textarea>
      </div>
      <div class="form-actions">
        <button type="button" class="btn-secondary"
          onclick="document.getElementById('addPaymentModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn-primary">
          <i class="fas fa-save"></i> Save Payment
        </button>
      </div>
    </form>
  </div>
</div>

<script>
  // Close addPaymentModal on backdrop click
  document.getElementById('addPaymentModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('open');
  });

  // Close rejectModal on backdrop click
  document.getElementById('rejectModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('open');
  });

  // Open reject modal and set correct form action
  function openRejectModal(paymentId) {
    const modal = document.getElementById('rejectModal');
    const form  = document.getElementById('rejectForm');
    form.action = '/admin/payments/' + paymentId + '/reject';
    modal.classList.add('open');
  }
</script>
@endsection
