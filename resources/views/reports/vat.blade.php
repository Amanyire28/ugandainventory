@extends('layouts.app')

@section('title', 'VAT Compliance Report')
@section('page-title', 'VAT Return & Compliance Report')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-8" style="color: #0f172a;">

  {{-- ── Date Filter & Actions Header ──────────────────────────── --}}
  <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 16px; padding: 20px 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);" class="no-print">
    <form method="GET" action="{{ route('reports.vat') }}" style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px;">
      
      <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
        <span style="font-weight: 800; font-size: 14px; color: #0f172a; display: flex; align-items: center; gap: 6px;">
          <i class="fas fa-filter text-indigo-600"></i> Reporting Period:
        </span>
        
        <select name="period" id="periodSelect" onchange="toggleCustomDates(this.value)"
          style="padding: 10px 16px; border: 1px solid #cbd5e1; border-radius: 10px; background: #ffffff; font-weight: 700; font-size: 14px; color: #0f172a; outline: none;">
          <option value="today" @selected($period === 'today')>Today</option>
          <option value="week" @selected($period === 'week')>This Week</option>
          <option value="month" @selected($period === 'month')>This Month</option>
          <option value="quarter" @selected($period === 'quarter')>This Quarter</option>
          <option value="year" @selected($period === 'year')>This Year</option>
          <option value="custom" @selected($period === 'custom')>Custom Date Range</option>
        </select>

        <div id="customDateFields" style="display: {{ $period === 'custom' ? 'flex' : 'none' }}; align-items: center; gap: 8px;">
          <input type="date" name="start_date" value="{{ request('start_date', $startDate->format('Y-m-d')) }}"
            style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; font-weight: 600;">
          <span style="color: #64748b; font-weight: 700;">to</span>
          <input type="date" name="end_date" value="{{ request('end_date', $endDate->format('Y-m-d')) }}"
            style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; font-weight: 600;">
        </div>

        <button type="submit"
          style="background: #4f46e5; color: #ffffff; padding: 10px 20px; border-radius: 10px; font-weight: 800; font-size: 13px; border: none; cursor: pointer;">
          <i class="fas fa-search mr-1"></i> Apply Filter
        </button>
      </div>

      <div style="display: flex; align-items: center; gap: 10px;">
        <button type="button" onclick="window.print()"
          style="background: #f1f5f9; color: #0f172a; border: 1px solid #cbd5e1; padding: 10px 18px; border-radius: 10px; font-weight: 800; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 6px;">
          <i class="fas fa-print text-indigo-600"></i> Print VAT Report
        </button>
      </div>
    </form>
  </div>

  {{-- ── Title Banner ────────────────────────────────────────── --}}
  <div style="background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); color: #ffffff; padding: 28px 32px; border-radius: 16px; border: 1px solid #1e293b; shadow: 0 8px 20px rgba(0,0,0,0.15);">
    <div style="display: flex; flex-wrap: wrap; items-center; justify-content: space-between; gap: 16px;">
      <div>
        <span style="background: rgba(99, 102, 241, 0.25); color: #c7d2fe; padding: 4px 12px; border-radius: 9999px; font-weight: 800; font-size: 11px; text-transform: uppercase; border: 1px solid rgba(199, 210, 254, 0.3);">
          <i class="fas fa-file-contract mr-1"></i> Value Added Tax Compliance
        </span>
        <h1 style="font-size: 28px; font-weight: 900; color: #ffffff; margin: 8px 0 2px 0;">VAT Return Summary</h1>
        <p style="font-size: 13px; color: #cbd5e1; margin: 0; font-weight: 600;">
          Period: <strong>{{ $startDate->format('M d, Y') }}</strong> — <strong>{{ $endDate->format('M d, Y') }}</strong> 
          · Configured VAT Rate: <strong>{{ number_format($vatSummary['tax_rate'], 1) }}%</strong>
        </p>
      </div>

      <div style="text-align: right;">
        <div style="font-size: 11px; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Business Account</div>
        <div style="font-size: 16px; font-weight: 900; color: #ffffff;">{{ $vatSummary['business']->name }}</div>
        @if($vatSummary['business']->tax_number)
          <div style="font-size: 12px; color: #a5b4fc; font-weight: 700; font-mono;">TIN: {{ $vatSummary['business']->tax_number }}</div>
        @endif
      </div>
    </div>
  </div>

  {{-- ── KPI Cards Grid ──────────────────────────────────────── --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

    <!-- Card 1: VAT Output (Sales Tax Collected) -->
    <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 16px; padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.04); border-top: 4px solid #4f46e5;">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
        <span style="font-size: 12px; font-weight: 900; color: #4338ca; text-transform: uppercase; tracking-wider;">
          VAT Output (Sales Tax)
        </span>
        <div style="width: 36px; height: 36px; background: #e0e7ff; color: #4338ca; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 800;">
          <i class="fas fa-arrow-trend-up"></i>
        </div>
      </div>
      <div style="font-size: 30px; font-weight: 900; color: #0f172a;">
        UGX {{ number_format($vatSummary['total_vat_output']) }}
      </div>
      <div style="font-size: 12px; color: #475569; font-weight: 700; margin-top: 6px;">
        Total Taxable Sales: <strong>UGX {{ number_format($vatSummary['total_taxable_sales']) }}</strong>
      </div>
      <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
        Tax collected from completed sales & customer invoices
      </div>
    </div>

    <!-- Card 2: VAT Input (Purchases & Expenses Tax Paid) -->
    <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 16px; padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.04); border-top: 4px solid #0284c7;">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
        <span style="font-size: 12px; font-weight: 900; color: #0369a1; text-transform: uppercase; tracking-wider;">
          VAT Input (Purchases & Expenses Tax)
        </span>
        <div style="width: 36px; height: 36px; background: #e0f2fe; color: #0369a1; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 800;">
          <i class="fas fa-arrow-trend-down"></i>
        </div>
      </div>
      <div style="font-size: 30px; font-weight: 900; color: #0f172a;">
        UGX {{ number_format($vatSummary['total_vat_input']) }}
      </div>
      <div style="font-size: 12px; color: #475569; font-weight: 700; margin-top: 6px;">
        Total Taxable Purchases: <strong>UGX {{ number_format($vatSummary['total_taxable_purchases']) }}</strong>
      </div>
      <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
        Tax paid on inventory purchases & claimable expenses
      </div>
    </div>

    <!-- Card 3: Net VAT Payable / Refundable -->
    @php
      $isPayable = $vatSummary['net_vat_payable'] >= 0;
      $accentColor = $isPayable ? '#d97706' : '#059669';
      $bgColor = $isPayable ? '#fffbeb' : '#ecfdf5';
      $borderColor = $isPayable ? '#fde68a' : '#a7f3d0';
    @endphp
    <div style="background: {{ $bgColor }}; border: 1px solid {{ $borderColor }}; border-radius: 16px; padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.04); border-top: 4px solid {{ $accentColor }};">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
        <span style="font-size: 12px; font-weight: 900; color: {{ $accentColor }}; text-transform: uppercase; tracking-wider;">
          {{ $isPayable ? 'Net VAT Payable (Tax Due)' : 'Net VAT Credit / Refundable' }}
        </span>
        <div style="width: 36px; height: 36px; background: #ffffff; color: {{ $accentColor }}; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 800; border: 1px solid {{ $borderColor }};">
          <i class="fas {{ $isPayable ? 'fa-building-columns' : 'fa-hand-holding-dollar' }}"></i>
        </div>
      </div>
      <div style="font-size: 30px; font-weight: 900; color: #0f172a;">
        UGX {{ number_format(abs($vatSummary['net_vat_payable'])) }}
      </div>
      <div style="font-size: 12px; color: #475569; font-weight: 700; margin-top: 6px;">
        Formula: <strong>VAT Output (UGX {{ number_format($vatSummary['total_vat_output']) }}) - VAT Input (UGX {{ number_format($vatSummary['total_vat_input']) }})</strong>
      </div>
      <div style="font-size: 11px; font-weight: 800; color: {{ $accentColor }}; margin-top: 4px;">
        @if($isPayable)
          <i class="fas fa-exclamation-triangle mr-1"></i> Amount payable to Revenue Authority
        @else
          <i class="fas fa-check-circle mr-1"></i> Input tax exceeds output tax (VAT Credit)
        @endif
      </div>
    </div>

  </div>

  {{-- ── Detailed Tax Breakdown Tables ──────────────────────── --}}
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

    <!-- Table 1: VAT Output Breakdown -->
    <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); overflow: hidden;">
      <div style="padding: 16px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; font-weight: 900; color: #0f172a; font-size: 15px; display: flex; align-items: center; justify-content: space-between;">
        <span><i class="fas fa-receipt text-indigo-600 mr-2"></i> VAT Output Breakdown (Sales)</span>
        <span style="font-size: 12px; background: #e0e7ff; color: #4338ca; padding: 2px 10px; border-radius: 9999px; font-weight: 800;">Tax Collected</span>
      </div>

      <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
        <thead style="background: #f1f5f9; color: #475569; font-size: 11px; text-transform: uppercase; font-weight: 800;">
          <tr>
            <th style="padding: 12px 16px; text-align: left;">Category / Stream</th>
            <th style="padding: 12px 16px; text-align: right;">Gross Revenue</th>
            <th style="padding: 12px 16px; text-align: right;">VAT Amount</th>
          </tr>
        </thead>
        <tbody>
          <tr style="border-bottom: 1px solid #f1f5f9;">
            <td style="padding: 14px 16px; font-weight: 700; color: #1e293b;">POS & Cashier Direct Sales</td>
            <td style="padding: 14px 16px; text-align: right; font-weight: 700;">UGX {{ number_format($vatSummary['total_sales_gross']) }}</td>
            <td style="padding: 14px 16px; text-align: right; font-weight: 900; color: #4338ca;">UGX {{ number_format($vatSummary['sales_vat_output']) }}</td>
          </tr>
          <tr style="border-bottom: 1px solid #f1f5f9;">
            <td style="padding: 14px 16px; font-weight: 700; color: #1e293b;">Customer Invoices & Credit Sales</td>
            <td style="padding: 14px 16px; text-align: right; font-weight: 700;">UGX {{ number_format($vatSummary['total_sales_gross'] > 0 ? 0 : 0) }}</td>
            <td style="padding: 14px 16px; text-align: right; font-weight: 900; color: #4338ca;">UGX {{ number_format($vatSummary['invoices_vat_output']) }}</td>
          </tr>
        </tbody>
        <tfoot style="background: #f8fafc; font-weight: 900; border-top: 2px solid #cbd5e1;">
          <tr>
            <td style="padding: 14px 16px;">TOTAL VAT OUTPUT:</td>
            <td style="padding: 14px 16px; text-align: right;">UGX {{ number_format($vatSummary['total_sales_gross']) }}</td>
            <td style="padding: 14px 16px; text-align: right; color: #4338ca; font-size: 15px;">UGX {{ number_format($vatSummary['total_vat_output']) }}</td>
          </tr>
        </tfoot>
      </table>
    </div>

    <!-- Table 2: VAT Input Breakdown -->
    <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); overflow: hidden;">
      <div style="padding: 16px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; font-weight: 900; color: #0f172a; font-size: 15px; display: flex; align-items: center; justify-content: space-between;">
        <span><i class="fas fa-boxes-packing text-sky-600 mr-2"></i> VAT Input Breakdown (Purchases & Expenses)</span>
        <span style="font-size: 12px; background: #e0f2fe; color: #0369a1; padding: 2px 10px; border-radius: 9999px; font-weight: 800;">Tax Paid</span>
      </div>

      <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
        <thead style="background: #f1f5f9; color: #475569; font-size: 11px; text-transform: uppercase; font-weight: 800;">
          <tr>
            <th style="padding: 12px 16px; text-align: left;">Stream</th>
            <th style="padding: 12px 16px; text-align: right;">Total Amount</th>
            <th style="padding: 12px 16px; text-align: right;">VAT Amount Claimed</th>
          </tr>
        </thead>
        <tbody>
          <tr style="border-bottom: 1px solid #f1f5f9;">
            <td style="padding: 14px 16px; font-weight: 700; color: #1e293b;">Supplier Purchases / Inventory</td>
            <td style="padding: 14px 16px; text-align: right; font-weight: 700;">UGX {{ number_format($vatSummary['total_purchases_gross']) }}</td>
            <td style="padding: 14px 16px; text-align: right; font-weight: 900; color: #0369a1;">UGX {{ number_format($vatSummary['purchases_vat_input']) }}</td>
          </tr>
          <tr style="border-bottom: 1px solid #f1f5f9;">
            <td style="padding: 14px 16px; font-weight: 700; color: #1e293b;">Claimable Business Expenses</td>
            <td style="padding: 14px 16px; text-align: right; font-weight: 700;">UGX {{ number_format($vatSummary['total_expenses_gross']) }}</td>
            <td style="padding: 14px 16px; text-align: right; font-weight: 900; color: #0369a1;">UGX {{ number_format($vatSummary['expenses_vat_input']) }}</td>
          </tr>
        </tbody>
        <tfoot style="background: #f8fafc; font-weight: 900; border-top: 2px solid #cbd5e1;">
          <tr>
            <td style="padding: 14px 16px;">TOTAL VAT INPUT:</td>
            <td style="padding: 14px 16px; text-align: right;">UGX {{ number_format($vatSummary['total_purchases_gross'] + $vatSummary['total_expenses_gross']) }}</td>
            <td style="padding: 14px 16px; text-align: right; color: #0369a1; font-size: 15px;">UGX {{ number_format($vatSummary['total_vat_input']) }}</td>
          </tr>
        </tfoot>
      </table>
    </div>

  </div>

  {{-- ── Net VAT Statement Footer ────────────────────────────── --}}
  <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 16px; padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); text-align: center;">
    <h3 style="font-size: 18px; font-weight: 900; color: #0f172a; margin-bottom: 6px;">VAT Compliance Calculation Summary</h3>
    <p style="font-size: 13px; color: #475569; max-width: 600px; margin: 0 auto 16px auto; font-weight: 600;">
      This summary represents your business value added tax obligations calculated from recorded sales, customer invoices, supplier purchases, and business expenses.
    </p>

    <div style="display: inline-flex; items-center; gap: 20px; background: #f8fafc; padding: 16px 32px; border-radius: 12px; border: 1px solid #e2e8f0; flex-wrap: wrap;">
      <div>
        <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Total Output Tax</span>
        <div style="font-size: 20px; font-weight: 900; color: #4338ca;">UGX {{ number_format($vatSummary['total_vat_output']) }}</div>
      </div>
      <div style="font-size: 24px; font-weight: 900; color: #cbd5e1;">-</div>
      <div>
        <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Total Input Tax</span>
        <div style="font-size: 20px; font-weight: 900; color: #0369a1;">UGX {{ number_format($vatSummary['total_vat_input']) }}</div>
      </div>
      <div style="font-size: 24px; font-weight: 900; color: #cbd5e1;">=</div>
      <div>
        <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Net Tax Payable</span>
        <div style="font-size: 20px; font-weight: 900; color: {{ $isPayable ? '#d97706' : '#059669' }};">
          UGX {{ number_format($vatSummary['net_vat_payable']) }}
        </div>
      </div>
    </div>
  </div>

</div>

<script>
function toggleCustomDates(value) {
  const customFields = document.getElementById('customDateFields');
  if (customFields) {
    customFields.style.display = (value === 'custom') ? 'flex' : 'none';
  }
}
</script>

<style>
@media print {
  .no-print { display: none !important; }
  body { background: #ffffff !important; color: #000000 !important; }
  .max-w-7xl { max-width: 100% !important; padding: 0 !important; }
}
</style>
@endsection
