@extends('layouts.app')

@section('title', 'VAT Management & Accounting Ledger')
@section('page-title', 'VAT Management & Accounting Ledger')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-4 space-y-5" style="color: #1e3a8a;">

  {{-- ── 1. Single Compact Header & Period Filter Bar ────────────────── --}}
  <div style="background: #ffffff; border: 1.5px solid #bfdbfe; border-radius: 12px; padding: 12px 18px; box-shadow: 0 2px 6px rgba(37,99,235,0.04);" class="no-print">
    <form method="GET" action="{{ route('vat.index') }}" style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px;">
      <input type="hidden" name="tab" value="{{ $tab }}">
      
      <!-- Left: Title & Statement Period -->
      <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
        <div>
          <h1 style="font-size: 17px; font-weight: 900; color: #1e3a8a; margin: 0; line-height: 1.2; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-calculator text-blue-600 text-base"></i> VAT Management & Ledger
          </h1>
          <p style="font-size: 11px; color: #3b82f6; margin: 2px 0 0 0; font-weight: 700;">
            Statement Period: <strong>{{ $startDate->format('M d, Y') }}</strong> — <strong>{{ $endDate->format('M d, Y') }}</strong> · Tax Rate: <strong>{{ number_format($vatSummary['tax_rate'], 1) }}%</strong>
          </p>
        </div>
      </div>

      <!-- Right: Compact Filter Controls & Print Button -->
      <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
        <span style="font-weight: 800; font-size: 12px; color: #1e3a8a; display: flex; align-items: center; gap: 4px;">
          <i class="fas fa-filter text-blue-600 text-xs"></i> Period:
        </span>
        
        <select name="period" id="periodSelect" onchange="toggleCustomDates(this.value)"
          style="padding: 6px 12px; border: 1px solid #bfdbfe; border-radius: 8px; background: #ffffff; font-weight: 700; font-size: 12px; color: #1e3a8a; outline: none;">
          <option value="today" @selected($period === 'today')>Today</option>
          <option value="week" @selected($period === 'week')>This Week</option>
          <option value="month" @selected($period === 'month')>This Month</option>
          <option value="quarter" @selected($period === 'quarter')>This Quarter</option>
          <option value="year" @selected($period === 'year')>This Year</option>
          <option value="custom" @selected($period === 'custom')>Custom Date Range</option>
        </select>

        <div id="customDateFields" style="display: {{ $period === 'custom' ? 'flex' : 'none' }}; align-items: center; gap: 6px;">
          <input type="date" name="start_date" value="{{ request('start_date', $startDate->format('Y-m-d')) }}"
            style="padding: 5px 8px; border: 1px solid #bfdbfe; border-radius: 6px; font-size: 12px; font-weight: 600;">
          <span style="color: #64748b; font-weight: 700; font-size: 11px;">to</span>
          <input type="date" name="end_date" value="{{ request('end_date', $endDate->format('Y-m-d')) }}"
            style="padding: 5px 8px; border: 1px solid #bfdbfe; border-radius: 6px; font-size: 12px; font-weight: 600;">
        </div>

        <button type="submit"
          style="background: #2563eb; color: #ffffff; padding: 6px 14px; border-radius: 8px; font-weight: 800; font-size: 12px; border: none; cursor: pointer; box-shadow: 0 1px 3px rgba(37,99,235,0.2);">
          <i class="fas fa-search mr-1 text-[11px]"></i> Update View
        </button>

        <button type="button" onclick="window.print()"
          style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; padding: 6px 12px; border-radius: 8px; font-weight: 800; font-size: 12px; cursor: pointer; display: flex; align-items: center; gap: 4px;">
          <i class="fas fa-print text-blue-600 text-xs"></i> Print Statement
        </button>
      </div>
    </form>
  </div>

  {{-- ── 2. Compact Minimal KPI Summary Cards ────────────────────────── --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

    <!-- VAT Output (Credit / Sales Tax Collected) -->
    <div style="background: #ffffff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 14px 18px; box-shadow: 0 2px 6px rgba(37,99,235,0.03); border-top: 3px solid #2563eb;">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
        <span style="font-size: 11px; font-weight: 900; color: #1d4ed8; text-transform: uppercase;">
          VAT Output (Credit / Tax Collected)
        </span>
        <div style="width: 28px; height: 28px; background: #eff6ff; color: #2563eb; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 12px;">
          <i class="fas fa-arrow-trend-up"></i>
        </div>
      </div>
      <div style="font-size: 20px; font-weight: 900; color: #1e3a8a;">
        UGX {{ number_format($vatSummary['total_vat_output']) }}
      </div>
      <div style="font-size: 11px; color: #475569; font-weight: 700; margin-top: 4px;">
        Taxable Base: <strong>UGX {{ number_format($vatSummary['total_taxable_sales']) }}</strong>
      </div>
    </div>

    <!-- VAT Input (Debit Side / Purchases & Expenses Tax Paid) -->
    <div style="background: #ffffff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 14px 18px; box-shadow: 0 2px 6px rgba(37,99,235,0.03); border-top: 3px solid #0284c7;">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
        <span style="font-size: 11px; font-weight: 900; color: #0369a1; text-transform: uppercase;">
          VAT Input (Debit / Tax Paid)
        </span>
        <div style="width: 28px; height: 28px; background: #e0f2fe; color: #0284c7; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 12px;">
          <i class="fas fa-arrow-trend-down"></i>
        </div>
      </div>
      <div style="font-size: 20px; font-weight: 900; color: #1e3a8a;">
        UGX {{ number_format($vatSummary['total_vat_input']) }}
      </div>
      <div style="font-size: 11px; color: #475569; font-weight: 700; margin-top: 4px;">
        Taxable Purchases: <strong>UGX {{ number_format($vatSummary['total_taxable_purchases']) }}</strong>
      </div>
    </div>

    <!-- Net VAT Payable / Refundable -->
    @php
      $isPayable = $vatSummary['net_vat_payable'] >= 0;
    @endphp
    <div style="background: #ffffff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 14px 18px; box-shadow: 0 2px 6px rgba(37,99,235,0.03); border-top: 3px solid {{ $isPayable ? '#d97706' : '#059669' }};">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
        <span style="font-size: 11px; font-weight: 900; color: {{ $isPayable ? '#b45309' : '#047857' }}; text-transform: uppercase;">
          {{ $isPayable ? 'Net VAT Payable' : 'Net VAT Credit' }}
        </span>
        <div style="width: 28px; height: 28px; background: {{ $isPayable ? '#fef3c7' : '#d1fae5' }}; color: {{ $isPayable ? '#d97706' : '#059669' }}; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 12px;">
          <i class="fas {{ $isPayable ? 'fa-scale-unbalanced' : 'fa-circle-check' }}"></i>
        </div>
      </div>
      <div style="font-size: 20px; font-weight: 900; color: {{ $isPayable ? '#92400e' : '#065f46' }};">
        UGX {{ number_format(abs($vatSummary['net_vat_payable'])) }}
      </div>
      <div style="font-size: 11px; color: #475569; font-weight: 700; margin-top: 4px;">
        Status: <strong>{{ $isPayable ? 'Tax Due to Authority' : 'Refundable Credit' }}</strong>
      </div>
    </div>

  </div>

  {{-- ── 3. Tab Navigation (Ledger T-Account vs Subjected Products) ── --}}
  <div style="background: #ffffff; border: 1px solid #bfdbfe; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 6px rgba(37,99,235,0.04);">
    <div style="display: flex; border-bottom: 1px solid #bfdbfe; background: #eff6ff;" class="no-print">
      <a href="{{ route('vat.index', array_merge(request()->query(), ['tab' => 'ledger'])) }}"
        style="padding: 12px 22px; font-weight: 800; font-size: 13px; text-decoration: none; display: flex; align-items: center; gap: 8px; border-bottom: 3px solid {{ $tab === 'ledger' ? '#2563eb' : 'transparent' }}; color: {{ $tab === 'ledger' ? '#2563eb' : '#64748b' }}; background: {{ $tab === 'ledger' ? '#ffffff' : 'transparent' }};">
        <i class="fas fa-book-bookmark"></i> VAT Accounting T-Account Ledger
      </a>
      <a href="{{ route('vat.index', array_merge(request()->query(), ['tab' => 'products'])) }}"
        style="padding: 12px 22px; font-weight: 800; font-size: 13px; text-decoration: none; display: flex; align-items: center; gap: 8px; border-bottom: 3px solid {{ $tab === 'products' ? '#2563eb' : 'transparent' }}; color: {{ $tab === 'products' ? '#2563eb' : '#64748b' }}; background: {{ $tab === 'products' ? '#ffffff' : 'transparent' }};">
        <i class="fas fa-boxes-stacked"></i> Products Subjected to VAT ({{ $vatProducts->count() }})
      </a>
    </div>

    {{-- TAB 1: VAT ACCOUNTING LEDGER (T-ACCOUNT TABLE) --}}
    @if($tab === 'ledger')
      <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 12px; text-align: left;">
          
          {{-- Soft Light Blue Table Header --}}
          <thead style="background: #dbeafe; color: #1e40af; font-size: 11px; text-transform: uppercase; font-weight: 900; border-bottom: 2px solid #bfdbfe;">
            <tr>
              <th style="padding: 10px 16px;">Date & Time</th>
              <th style="padding: 10px 16px;">Reference / Doc #</th>
              <th style="padding: 10px 16px;">Party / Description</th>
              <th style="padding: 10px 16px; text-align: right;">Subtotal Excl. Tax</th>
              
              <!-- DEBIT COLUMN HEADER (INPUT VAT) -->
              <th style="padding: 10px 16px; text-align: right; background: #bfdbfe; color: #1e3a8a;">
                DEBIT: VAT Input (Paid)
              </th>

              <!-- CREDIT COLUMN HEADER (OUTPUT VAT) -->
              <th style="padding: 10px 16px; text-align: right; background: #93c5fd; color: #1e3a8a;">
                CREDIT: VAT Output (Collected)
              </th>

              <th style="padding: 10px 16px; text-align: right;">Gross Total</th>
            </tr>
          </thead>

          <tbody style="divide-y: 1px solid #e2e8f0;">
            @forelse($ledgerEntries as $entry)
              <tr style="border-bottom: 1px solid #f1f5f9; background: {{ $entry['side'] === 'credit' ? '#ffffff' : '#f8fafc' }};">
                <td style="padding: 10px 16px; font-weight: 700; color: #1e293b; white-space: nowrap;">
                  {{ \Carbon\Carbon::parse($entry['date'])->format('M d, Y') }}
                  <span style="display: block; font-size: 10px; color: #64748b; font-weight: 500;">{{ \Carbon\Carbon::parse($entry['date'])->format('h:i A') }}</span>
                </td>
                <td style="padding: 10px 16px; font-weight: 900; font-mono: true; font-size: 11px; color: #1e3a8a;">
                  {{ $entry['ref'] }}
                  <span style="display: block; font-size: 10px; font-weight: 700; color: {{ $entry['side'] === 'credit' ? '#2563eb' : '#0284c7' }};">
                    {{ $entry['type'] }}
                  </span>
                </td>
                <td style="padding: 10px 16px; font-weight: 800; color: #334155;">
                  {{ $entry['party'] }}
                </td>
                <td style="padding: 10px 16px; text-align: right; font-weight: 700; color: #475569;">
                  UGX {{ number_format($entry['subtotal']) }}
                </td>
                
                <!-- DEBIT SIDE (VAT INPUT) -->
                <td style="padding: 10px 16px; text-align: right; font-weight: 900; color: #0284c7; background: {{ $entry['vat_in'] > 0 ? '#e0f2fe' : 'transparent' }};">
                  @if($entry['vat_in'] > 0)
                    UGX {{ number_format($entry['vat_in']) }}
                  @else
                    <span style="color: #cbd5e1;">—</span>
                  @endif
                </td>

                <!-- CREDIT SIDE (VAT OUTPUT) -->
                <td style="padding: 10px 16px; text-align: right; font-weight: 900; color: #2563eb; background: {{ $entry['vat_out'] > 0 ? '#eff6ff' : 'transparent' }};">
                  @if($entry['vat_out'] > 0)
                    UGX {{ number_format($entry['vat_out']) }}
                  @else
                    <span style="color: #cbd5e1;">—</span>
                  @endif
                </td>

                <td style="padding: 10px 16px; text-align: right; font-weight: 900; color: #1e3a8a; white-space: nowrap;">
                  UGX {{ number_format($entry['total']) }}
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" style="padding: 36px; text-align: center; color: #64748b;">
                  <i class="fas fa-receipt" style="font-size: 32px; display: block; margin-bottom: 6px; opacity: 0.4;"></i>
                  <p style="font-size: 13px; font-weight: 700; color: #334155; margin: 0;">No VAT transactions recorded for the selected period.</p>
                </td>
              </tr>
            @endforelse
          </tbody>

          {{-- Soft Light Blue Table Summary Footer --}}
          <tfoot style="background: #dbeafe; color: #1e3a8a; font-weight: 900; font-size: 13px; border-top: 2px solid #bfdbfe;">
            <tr>
              <td colspan="3" style="padding: 12px 16px; font-size: 13px;">
                TOTAL VAT LEDGER SUMMARY
              </td>
              <td style="padding: 12px 16px; text-align: right; color: #1e3a8a;">
                UGX {{ number_format($vatSummary['total_taxable_sales'] + $vatSummary['total_taxable_purchases']) }}
              </td>
              
              <!-- TOTAL DEBIT (VAT INPUT) -->
              <td style="padding: 12px 16px; text-align: right; color: #0284c7; background: #e0f2fe; font-size: 13px;">
                UGX {{ number_format($vatSummary['total_vat_input']) }}
              </td>

              <!-- TOTAL CREDIT (VAT OUTPUT) -->
              <td style="padding: 12px 16px; text-align: right; color: #2563eb; background: #eff6ff; font-size: 13px;">
                UGX {{ number_format($vatSummary['total_vat_output']) }}
              </td>

              <td style="padding: 12px 16px; text-align: right; color: #1e3a8a;">
                UGX {{ number_format($vatSummary['total_sales_gross'] + $vatSummary['total_purchases_gross']) }}
              </td>
            </tr>

            <!-- NET VAT PAYABLE LEDGER BALANCE ROW -->
            <tr style="background: {{ $isPayable ? '#fef3c7' : '#d1fae5' }}; color: {{ $isPayable ? '#92400e' : '#065f46' }};">
              <td colspan="4" style="padding: 14px 16px; font-size: 14px; font-weight: 900;">
                <i class="fas {{ $isPayable ? 'fa-building-columns' : 'fa-hand-holding-dollar' }} mr-2"></i>
                FINAL NET VAT PAYABLE AT END OF LEDGER:
              </td>
              <td colspan="3" style="padding: 14px 16px; text-align: right; font-size: 18px; font-weight: 900; font-mono: true;">
                UGX {{ number_format(abs($vatSummary['net_vat_payable'])) }}
                <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; background: rgba(37,99,235,0.1); padding: 3px 8px; border-radius: 9999px; margin-left: 6px;">
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
        <table style="width: 100%; border-collapse: collapse; font-size: 12px; text-align: left;">
          <thead style="background: #dbeafe; color: #1e40af; font-size: 11px; text-transform: uppercase; font-weight: 900; border-bottom: 2px solid #bfdbfe;">
            <tr>
              <th style="padding: 10px 16px;">Product Name & SKU</th>
              <th style="padding: 10px 16px;">Category</th>
              <th style="padding: 10px 16px; text-align: right;">Selling Price Excl. VAT</th>
              <th style="padding: 10px 16px; text-align: right;">VAT Amount (18%)</th>
              <th style="padding: 10px 16px; text-align: right;">Final Price Incl. VAT</th>
              <th style="padding: 10px 16px; text-align: center;">Stock Quantity</th>
              <th style="padding: 10px 16px; text-align: center;">VAT Status</th>
            </tr>
          </thead>
          <tbody style="divide-y: 1px solid #e2e8f0;">
            @forelse($vatProducts as $product)
              @php
                $exclVat = $product->selling_price;
                $vatAmt = $exclVat * ($vatSummary['tax_rate'] / 100);
                $inclVat = $exclVat + $vatAmt;
              @endphp
              <tr style="border-bottom: 1px solid #f1f5f9;" class="hover:bg-blue-50/40 transition">
                <td style="padding: 10px 16px; font-weight: 800; color: #1e3a8a;">
                  {{ $product->name }}
                  <span style="display: block; font-size: 10px; font-mono: true; color: #64748b; font-weight: 600;">SKU: {{ $product->sku ?? 'N/A' }} | Barcode: {{ $product->barcode ?? 'N/A' }}</span>
                </td>
                <td style="padding: 10px 16px; font-weight: 700; color: #334155;">
                  {{ $product->category->name ?? 'Uncategorized' }}
                </td>
                <td style="padding: 10px 16px; text-align: right; font-weight: 700; color: #475569;">
                  UGX {{ number_format($exclVat, 2) }}
                </td>
                <td style="padding: 10px 16px; text-align: right; font-weight: 900; color: #2563eb; background: #eff6ff;">
                  UGX {{ number_format($vatAmt, 2) }}
                </td>
                <td style="padding: 10px 16px; text-align: right; font-weight: 900; color: #1e3a8a;">
                  UGX {{ number_format($inclVat, 2) }}
                </td>
                <td style="padding: 10px 16px; text-align: center; font-weight: 800; color: #334155;">
                  {{ number_format($product->quantity) }} {{ $product->unit ?? 'pcs' }}
                </td>
                <td style="padding: 10px 16px; text-align: center;">
                  <span style="background: #dbeafe; color: #1d4ed8; border: 1px solid #93c5fd; padding: 3px 10px; border-radius: 9999px; font-weight: 800; font-size: 10px; text-transform: uppercase;">
                    <i class="fas fa-check-circle mr-1"></i> VAT Enabled (18%)
                  </span>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" style="padding: 36px; text-align: center; color: #64748b;">
                  <i class="fas fa-box-open" style="font-size: 32px; display: block; margin-bottom: 6px; opacity: 0.4;"></i>
                  <p style="font-size: 13px; font-weight: 700; color: #334155; margin: 0;">No products with VAT enabled found.</p>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    @endif
  </div>

</div>

<script>
function toggleCustomDates(val) {
  const customFields = document.getElementById('customDateFields');
  if (customFields) {
    customFields.style.display = val === 'custom' ? 'flex' : 'none';
  }
}
</script>
@endsection
