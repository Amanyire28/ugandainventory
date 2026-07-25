@extends('layouts.app')

@section('title', 'VAT Management & Accounting Ledger')
@section('page-title', 'VAT Management & Accounting Ledger')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-8" style="color: #0f172a;">

  {{-- ── 1. Reporting Period Filter ────────────────────────────── --}}
  <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 16px; padding: 20px 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);" class="no-print">
    <form method="GET" action="{{ route('vat.index') }}" style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px;">
      <input type="hidden" name="tab" value="{{ $tab }}">
      
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
          <i class="fas fa-search mr-1"></i> Update View
        </button>
      </div>

      <div style="display: flex; align-items: center; gap: 10px;">
        <button type="button" onclick="window.print()"
          style="background: #f1f5f9; color: #0f172a; border: 1px solid #cbd5e1; padding: 10px 18px; border-radius: 10px; font-weight: 800; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 6px;">
          <i class="fas fa-print text-indigo-600"></i> Print VAT Statement
        </button>
      </div>
    </form>
  </div>

  {{-- ── 2. Top Header Banner ─────────────────────────────────── --}}
  <div style="background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); color: #ffffff; padding: 28px 32px; border-radius: 16px; border: 1px solid #1e293b; shadow: 0 8px 20px rgba(0,0,0,0.15);">
    <div style="display: flex; flex-wrap: wrap; items-center; justify-content: space-between; gap: 16px;">
      <div>
        <span style="background: rgba(99, 102, 241, 0.25); color: #c7d2fe; padding: 4px 12px; border-radius: 9999px; font-weight: 800; font-size: 11px; text-transform: uppercase; border: 1px solid rgba(199, 210, 254, 0.3);">
          <i class="fas fa-calculator mr-1"></i> Business VAT Accounting Ledger
        </span>
        <h1 style="font-size: 28px; font-weight: 900; color: #ffffff; margin: 8px 0 2px 0;">VAT Management & Ledger</h1>
        <p style="font-size: 13px; color: #cbd5e1; margin: 0; font-weight: 600;">
          Statement Period: <strong>{{ $startDate->format('M d, Y') }}</strong> — <strong>{{ $endDate->format('M d, Y') }}</strong> 
          · Business Tax Rate: <strong>{{ number_format($vatSummary['tax_rate'], 1) }}%</strong>
        </p>
      </div>

      <div style="text-align: right;">
        <div style="font-size: 11px; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Business Name</div>
        <div style="font-size: 18px; font-weight: 900; color: #ffffff;">{{ $business->name }}</div>
        @if($business->tax_number)
          <div style="font-size: 12px; color: #a5b4fc; font-weight: 700; font-mono;">TIN: {{ $business->tax_number }}</div>
        @endif
      </div>
    </div>
  </div>

  {{-- ── 3. Summary KPI Cards ────────────────────────────────── --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

    <!-- VAT Output (Credit / Sales Tax Collected) -->
    <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 16px; padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.04); border-top: 4px solid #4f46e5;">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
        <span style="font-size: 12px; font-weight: 900; color: #4338ca; text-transform: uppercase; tracking-wider;">
          VAT Output (Credit Side / Tax Collected)
        </span>
        <div style="width: 36px; height: 36px; background: #e0e7ff; color: #4338ca; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 800;">
          <i class="fas fa-arrow-trend-up"></i>
        </div>
      </div>
      <div style="font-size: 30px; font-weight: 900; color: #0f172a;">
        UGX {{ number_format($vatSummary['total_vat_output']) }}
      </div>
      <div style="font-size: 12px; color: #475569; font-weight: 700; margin-top: 6px;">
        Total Taxable Base: <strong>UGX {{ number_format($vatSummary['total_taxable_sales']) }}</strong>
      </div>
      <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
        Tax collected from sales & invoices (Credit in VAT ledger)
      </div>
    </div>

    <!-- VAT Input (Debit Side / Purchases & Expenses Tax Paid) -->
    <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 16px; padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.04); border-top: 4px solid #0284c7;">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
        <span style="font-size: 12px; font-weight: 900; color: #0369a1; text-transform: uppercase; tracking-wider;">
          VAT Input (Debit Side / Tax Paid)
        </span>
        <div style="width: 36px; height: 36px; background: #e0f2fe; color: #0369a1; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 800;">
          <i class="fas fa-arrow-trend-down"></i>
        </div>
      </div>
      <div style="font-size: 30px; font-weight: 900; color: #0f172a;">
        UGX {{ number_format($vatSummary['total_vat_input']) }}
      </div>
      <div style="font-size: 12px; color: #475569; font-weight: 700; margin-top: 6px;">
        Total Taxable Base: <strong>UGX {{ number_format($vatSummary['total_taxable_purchases']) }}</strong>
      </div>
      <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
        Tax paid on purchases & expenses (Debit in VAT ledger)
      </div>
    </div>

    <!-- Net VAT Payable / Refundable -->
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
        Ledger Balance: <strong>Credit Output - Debit Input</strong>
      </div>
      <div style="font-size: 11px; font-weight: 800; color: {{ $accentColor }}; margin-top: 4px;">
        @if($isPayable)
          <i class="fas fa-exclamation-circle mr-1"></i> Net Tax Amount Payable at end of ledger
        @else
          <i class="fas fa-check-circle mr-1"></i> Input tax exceeds output tax (VAT Credit)
        @endif
      </div>
    </div>

  </div>

  {{-- ── 4. Main Navigation Tabs ─────────────────────────────── --}}
  <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); overflow: hidden;">
    
    <!-- Tab Controls -->
    <div style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 12px 24px; display: flex; items-center; justify-content: space-between; flex-wrap: wrap; gap: 12px;" class="no-print">
      <div style="display: flex; align-items: center; gap: 8px;">
        <a href="{{ route('vat.index', ['period' => $period, 'tab' => 'ledger']) }}"
          style="padding: 10px 20px; border-radius: 10px; font-weight: 800; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; {{ $tab === 'ledger' ? 'background: #4f46e5; color: #ffffff; shadow: 0 2px 4px rgba(79,70,229,0.3);' : 'background: #ffffff; color: #475569; border: 1px solid #cbd5e1;' }}">
          <i class="fas fa-book font-bold"></i> VAT Accounting Ledger (Debit / Credit)
        </a>
        <a href="{{ route('vat.index', ['period' => $period, 'tab' => 'products']) }}"
          style="padding: 10px 20px; border-radius: 10px; font-weight: 800; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; {{ $tab === 'products' ? 'background: #4f46e5; color: #ffffff; shadow: 0 2px 4px rgba(79,70,229,0.3);' : 'background: #ffffff; color: #475569; border: 1px solid #cbd5e1;' }}">
          <i class="fas fa-boxes-packing font-bold"></i> VAT Subjected Products ({{ $vatProducts->total() }})
        </a>
      </div>

      @if($tab === 'products')
        <form method="GET" action="{{ route('vat.index') }}" style="display: flex; items-center; gap: 8px;">
          <input type="hidden" name="tab" value="products">
          <input type="hidden" name="period" value="{{ $period }}">
          <input type="text" name="search" value="{{ request('search') }}" placeholder="Search products by name or SKU…"
            style="padding: 8px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; font-weight: 600; width: 260px;">
          <button type="submit" style="background: #4f46e5; color: #ffffff; padding: 8px 14px; border-radius: 8px; font-weight: 800; border: none; cursor: pointer; font-size: 13px;">
            Search
          </button>
        </form>
      @endif
    </div>

    {{-- TAB 1: DUAL-SIDE VAT ACCOUNTING LEDGER --}}
    @if($tab === 'ledger')
      <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
          <thead style="background: #0f172a; color: #ffffff; font-size: 12px; text-transform: uppercase; font-weight: 900; letter-spacing: 0.05em;">
            <tr>
              <th style="padding: 16px 20px;">Transaction Date</th>
              <th style="padding: 16px 20px;">Reference / Type</th>
              <th style="padding: 16px 20px;">Particulars / Customer / Supplier</th>
              <th style="padding: 16px 20px; text-align: right;">Subtotal Base</th>
              <th style="padding: 16px 20px; text-align: right; background: #0369a1;">VAT Input (Debit - Tax Paid)</th>
              <th style="padding: 16px 20px; text-align: right; background: #4338ca;">VAT Output (Credit - Tax Collected)</th>
              <th style="padding: 16px 20px; text-align: right;">Total Amount</th>
            </tr>
          </thead>
          <tbody>
            @forelse($sortedLedger as $entry)
              <tr style="border-bottom: 1px solid #f1f5f9; background: {{ $entry['side'] === 'credit' ? '#ffffff' : '#f8fafc' }};">
                <td style="padding: 14px 20px; font-weight: 700; color: #1e293b; white-space: nowrap;">
                  {{ \Carbon\Carbon::parse($entry['date'])->format('M d, Y') }}
                  <span style="display: block; font-size: 11px; color: #64748b; font-weight: 500;">{{ \Carbon\Carbon::parse($entry['date'])->format('h:i A') }}</span>
                </td>
                <td style="padding: 14px 20px; font-weight: 900; font-mono; font-size: 12px; color: #0f172a;">
                  {{ $entry['ref'] }}
                  <span style="display: block; font-size: 11px; font-weight: 700; color: {{ $entry['side'] === 'credit' ? '#4338ca' : '#0369a1' }};">
                    {{ $entry['type'] }}
                  </span>
                </td>
                <td style="padding: 14px 20px; font-weight: 800; color: #334155;">
                  {{ $entry['party'] }}
                </td>
                <td style="padding: 14px 20px; text-align: right; font-weight: 700; color: #475569;">
                  UGX {{ number_format($entry['subtotal']) }}
                </td>
                
                <!-- DEBIT SIDE (VAT INPUT) -->
                <td style="padding: 14px 20px; text-align: right; font-weight: 900; color: #0369a1; background: {{ $entry['vat_in'] > 0 ? '#e0f2fe' : 'transparent' }};">
                  @if($entry['vat_in'] > 0)
                    UGX {{ number_format($entry['vat_in']) }}
                  @else
                    <span style="color: #cbd5e1;">—</span>
                  @endif
                </td>

                <!-- CREDIT SIDE (VAT OUTPUT) -->
                <td style="padding: 14px 20px; text-align: right; font-weight: 900; color: #4338ca; background: {{ $entry['vat_out'] > 0 ? '#e0e7ff' : 'transparent' }};">
                  @if($entry['vat_out'] > 0)
                    UGX {{ number_format($entry['vat_out']) }}
                  @else
                    <span style="color: #cbd5e1;">—</span>
                  @endif
                </td>

                <td style="padding: 14px 20px; text-align: right; font-weight: 900; color: #0f172a; white-space: nowrap;">
                  UGX {{ number_format($entry['total']) }}
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" style="padding: 48px; text-align: center; color: #64748b;">
                  <i class="fas fa-receipt" style="font-size: 36px; display: block; margin-bottom: 8px; opacity: 0.4;"></i>
                  <p style="font-size: 14px; font-weight: 700; color: #334155; margin: 0;">No VAT transactions recorded for the selected period.</p>
                </td>
              </tr>
            @endforelse
          </tbody>

          {{-- LEDGER SUMMARY FOOTER --}}
          <tfoot style="background: #0f172a; color: #ffffff; font-weight: 900; font-size: 14px; border-top: 3px solid #1e293b;">
            <tr>
              <td colspan="3" style="padding: 16px 20px; font-size: 15px;">
                TOTAL VAT LEDGER SUMMARY
              </td>
              <td style="padding: 16px 20px; text-align: right; color: #cbd5e1;">
                UGX {{ number_format($vatSummary['total_taxable_sales'] + $vatSummary['total_taxable_purchases']) }}
              </td>
              
              <!-- TOTAL DEBIT (VAT INPUT) -->
              <td style="padding: 16px 20px; text-align: right; color: #38bdf8; background: #0369a1; font-size: 15px;">
                UGX {{ number_format($vatSummary['total_vat_input']) }}
              </td>

              <!-- TOTAL CREDIT (VAT OUTPUT) -->
              <td style="padding: 16px 20px; text-align: right; color: #a5b4fc; background: #4338ca; font-size: 15px;">
                UGX {{ number_format($vatSummary['total_vat_output']) }}
              </td>

              <td style="padding: 16px 20px; text-align: right; color: #ffffff;">
                UGX {{ number_format($vatSummary['total_sales_gross'] + $vatSummary['total_purchases_gross']) }}
              </td>
            </tr>

            <!-- NET VAT PAYABLE LEDGER BALANCE ROW -->
            <tr style="background: {{ $isPayable ? '#78350f' : '#064e3b' }}; color: #ffffff;">
              <td colspan="4" style="padding: 18px 20px; font-size: 16px; font-weight: 900;">
                <i class="fas {{ $isPayable ? 'fa-building-columns' : 'fa-hand-holding-dollar' }} mr-2"></i>
                FINAL NET VAT PAYABLE AT END OF LEDGER:
              </td>
              <td colspan="3" style="padding: 18px 20px; text-align: right; font-size: 22px; font-weight: 900; font-mono: true;">
                UGX {{ number_format(abs($vatSummary['net_vat_payable'])) }}
                <span style="font-size: 12px; font-weight: 800; text-transform: uppercase; background: rgba(255,255,255,0.2); padding: 4px 10px; border-radius: 9999px; margin-left: 8px;">
                  {{ $isPayable ? 'VAT Payable (Tax Due)' : 'VAT Credit (Refundable)' }}
                </span>
              </td>
            </tr>
          </tfoot>
        </table>
      </div>
    @endif

    {{-- TAB 2: VAT SUBJECTED PRODUCTS --}}
    @if($tab === 'products')
      <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
          <thead style="background: #0f172a; color: #ffffff; font-size: 11px; text-transform: uppercase; font-weight: 900; letter-spacing: 0.05em;">
            <tr>
              <th style="padding: 14px 20px;">Product Name & SKU</th>
              <th style="padding: 14px 20px;">Category</th>
              <th style="padding: 14px 20px; text-align: right;">Selling Price (Excl. Tax)</th>
              <th style="padding: 14px 20px; text-align: right; background: #4338ca;">Unit VAT Output ({{ number_format($vatSummary['tax_rate'], 1) }}%)</th>
              <th style="padding: 14px 20px; text-align: right;">Final Price (Incl. Tax)</th>
              <th style="padding: 14px 20px; text-align: right;">Cost Price</th>
              <th style="padding: 14px 20px; text-align: right; background: #0369a1;">Unit VAT Input (Est.)</th>
              <th style="padding: 14px 20px; text-align: center;">Stock Qty</th>
              <th style="padding: 14px 20px; text-align: center;">VAT Status</th>
            </tr>
          </thead>
          <tbody style="divide-y divide-slate-100;">
            @forelse($vatProducts as $prod)
              @php
                $taxRate = $vatSummary['tax_rate'] / 100;
                $unitVatOutput = round($prod->selling_price * $taxRate, 2);
                $finalPrice    = $prod->selling_price + $unitVatOutput;
                $unitVatInput  = round($prod->cost_price * $taxRate, 2);
              @endphp
              <tr style="border-bottom: 1px solid #f1f5f9; hover: background: #f8fafc;">
                <td style="padding: 14px 20px; font-weight: 800; color: #0f172a;">
                  {{ $prod->name }}
                  <span style="display: block; font-size: 11px; color: #64748b; font-mono: true; font-weight: 500;">SKU: {{ $prod->sku }}</span>
                </td>
                <td style="padding: 14px 20px; font-weight: 700; color: #475569;">
                  {{ $prod->category->name ?? 'General' }}
                </td>
                <td style="padding: 14px 20px; text-align: right; font-weight: 700; color: #334155;">
                  UGX {{ number_format($prod->selling_price) }}
                </td>
                <td style="padding: 14px 20px; text-align: right; font-weight: 900; color: #4338ca; background: #e0e7ff;">
                  UGX {{ number_format($unitVatOutput) }}
                </td>
                <td style="padding: 14px 20px; text-align: right; font-weight: 900; color: #0f172a;">
                  UGX {{ number_format($finalPrice) }}
                </td>
                <td style="padding: 14px 20px; text-align: right; font-weight: 700; color: #475569;">
                  UGX {{ number_format($prod->cost_price) }}
                </td>
                <td style="padding: 14px 20px; text-align: right; font-weight: 900; color: #0369a1; background: #e0f2fe;">
                  UGX {{ number_format($unitVatInput) }}
                </td>
                <td style="padding: 14px 20px; text-align: center; font-weight: 900; color: #0f172a;">
                  {{ number_format($prod->quantity, 0) }} {{ $prod->unit }}
                </td>
                <td style="padding: 14px 20px; text-align: center;">
                  <span style="background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; padding: 4px 10px; border-radius: 9999px; font-size: 11px; font-weight: 900;">
                    <i class="fas fa-check-circle mr-1 text-emerald-600"></i> Subjected to VAT
                  </span>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="9" style="padding: 48px; text-align: center; color: #64748b;">
                  <i class="fas fa-box-open" style="font-size: 36px; display: block; margin-bottom: 8px; opacity: 0.4;"></i>
                  <p style="font-size: 14px; font-weight: 700; color: #334155; margin: 0;">No active products found in inventory.</p>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @if($vatProducts->hasPages())
        <div style="padding: 16px 24px; border-top: 1px solid #e2e8f0;">{{ $vatProducts->appends(request()->query())->links() }}</div>
      @endif
    @endif

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
