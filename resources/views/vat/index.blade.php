@extends('layouts.app')

@section('title', 'VAT Management & Accounting Ledger')
@section('page-title', 'VAT Management & Accounting Ledger')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-8" style="color: #1e3a8a;">

  {{-- ── 1. Reporting Period Filter ────────────────────────────── --}}
  <div style="background: #ffffff; border: 1px solid #bfdbfe; border-radius: 16px; padding: 20px 24px; box-shadow: 0 2px 8px rgba(37, 99, 235, 0.05);" class="no-print">
    <form method="GET" action="{{ route('vat.index') }}" style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px;">
      <input type="hidden" name="tab" value="{{ $tab }}">
      
      <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
        <span style="font-weight: 800; font-size: 14px; color: #1e3a8a; display: flex; align-items: center; gap: 6px;">
          <i class="fas fa-filter text-blue-600"></i> Reporting Period:
        </span>
        
        <select name="period" id="periodSelect" onchange="toggleCustomDates(this.value)"
          style="padding: 10px 16px; border: 1px solid #bfdbfe; border-radius: 10px; background: #ffffff; font-weight: 700; font-size: 14px; color: #1e3a8a; outline: none;">
          <option value="today" @selected($period === 'today')>Today</option>
          <option value="week" @selected($period === 'week')>This Week</option>
          <option value="month" @selected($period === 'month')>This Month</option>
          <option value="quarter" @selected($period === 'quarter')>This Quarter</option>
          <option value="year" @selected($period === 'year')>This Year</option>
          <option value="custom" @selected($period === 'custom')>Custom Date Range</option>
        </select>

        <div id="customDateFields" style="display: {{ $period === 'custom' ? 'flex' : 'none' }}; align-items: center; gap: 8px;">
          <input type="date" name="start_date" value="{{ request('start_date', $startDate->format('Y-m-d')) }}"
            style="padding: 8px 12px; border: 1px solid #bfdbfe; border-radius: 8px; font-size: 13px; font-weight: 600;">
          <span style="color: #64748b; font-weight: 700;">to</span>
          <input type="date" name="end_date" value="{{ request('end_date', $endDate->format('Y-m-d')) }}"
            style="padding: 8px 12px; border: 1px solid #bfdbfe; border-radius: 8px; font-size: 13px; font-weight: 600;">
        </div>

        <button type="submit"
          style="background: #2563eb; color: #ffffff; padding: 10px 20px; border-radius: 10px; font-weight: 800; font-size: 13px; border: none; cursor: pointer; box-shadow: 0 2px 4px rgba(37,99,235,0.2);">
          <i class="fas fa-search mr-1"></i> Update View
        </button>
      </div>

      <div style="display: flex; align-items: center; gap: 10px;">
        <button type="button" onclick="window.print()"
          style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; padding: 10px 18px; border-radius: 10px; font-weight: 800; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 6px;">
          <i class="fas fa-print text-blue-600"></i> Print VAT Statement
        </button>
      </div>
    </form>
  </div>

  {{-- ── 2. Top Header Banner (Soft Light Blue, No dark backgrounds) ── --}}
  <div style="background: #eff6ff; color: #1e3a8a; padding: 24px 28px; border-radius: 16px; border: 1.5px solid #bfdbfe; box-shadow: 0 4px 12px rgba(37,99,235,0.06);">
    <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px;">
      <div>
        <span style="background: #dbeafe; color: #1d4ed8; padding: 4px 12px; border-radius: 9999px; font-weight: 800; font-size: 11px; text-transform: uppercase; border: 1px solid #93c5fd;">
          <i class="fas fa-calculator mr-1"></i> Business VAT Accounting Ledger
        </span>
        <h1 style="font-size: 26px; font-weight: 900; color: #1e3a8a; margin: 8px 0 2px 0;">VAT Management & Ledger</h1>
        <p style="font-size: 13px; color: #3b82f6; margin: 0; font-weight: 700;">
          Statement Period: <strong>{{ $startDate->format('M d, Y') }}</strong> — <strong>{{ $endDate->format('M d, Y') }}</strong> 
          · Business Tax Rate: <strong>{{ number_format($vatSummary['tax_rate'], 1) }}%</strong>
        </p>
      </div>

      <div style="text-align: right;">
        <div style="font-size: 11px; color: #60a5fa; font-weight: 700; text-transform: uppercase;">Business Name</div>
        <div style="font-size: 18px; font-weight: 900; color: #1e3a8a;">{{ $business->name }}</div>
        @if($business->tax_number)
          <div style="font-size: 12px; color: #2563eb; font-weight: 700; font-mono: true;">TIN: {{ $business->tax_number }}</div>
        @endif
      </div>
    </div>
  </div>

  {{-- ── 3. Summary KPI Cards ────────────────────────────────── --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

    <!-- VAT Output (Credit / Sales Tax Collected) -->
    <div style="background: #ffffff; border: 1px solid #bfdbfe; border-radius: 16px; padding: 24px; box-shadow: 0 4px 12px rgba(37,99,235,0.04); border-top: 4px solid #2563eb;">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
        <span style="font-size: 12px; font-weight: 900; color: #1d4ed8; text-transform: uppercase;">
          VAT Output (Credit Side / Tax Collected)
        </span>
        <div style="width: 36px; height: 36px; background: #eff6ff; color: #2563eb; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 800;">
          <i class="fas fa-arrow-trend-up"></i>
        </div>
      </div>
      <div style="font-size: 28px; font-weight: 900; color: #1e3a8a;">
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
    <div style="background: #ffffff; border: 1px solid #bfdbfe; border-radius: 16px; padding: 24px; box-shadow: 0 4px 12px rgba(37,99,235,0.04); border-top: 4px solid #0284c7;">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
        <span style="font-size: 12px; font-weight: 900; color: #0369a1; text-transform: uppercase;">
          VAT Input (Debit Side / Tax Paid)
        </span>
        <div style="width: 36px; height: 36px; background: #e0f2fe; color: #0284c7; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 800;">
          <i class="fas fa-arrow-trend-down"></i>
        </div>
      </div>
      <div style="font-size: 28px; font-weight: 900; color: #1e3a8a;">
        UGX {{ number_format($vatSummary['total_vat_input']) }}
      </div>
      <div style="font-size: 12px; color: #475569; font-weight: 700; margin-top: 6px;">
        Total Taxable Purchases: <strong>UGX {{ number_format($vatSummary['total_taxable_purchases']) }}</strong>
      </div>
      <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
        Tax paid on purchases & expenses (Debit in VAT ledger)
      </div>
    </div>

    <!-- Net VAT Payable / Refundable -->
    @php
      $isPayable = $vatSummary['net_vat_payable'] >= 0;
    @endphp
    <div style="background: #ffffff; border: 1px solid #bfdbfe; border-radius: 16px; padding: 24px; box-shadow: 0 4px 12px rgba(37,99,235,0.04); border-top: 4px solid {{ $isPayable ? '#d97706' : '#059669' }};">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
        <span style="font-size: 12px; font-weight: 900; color: {{ $isPayable ? '#b45309' : '#047857' }}; text-transform: uppercase;">
          {{ $isPayable ? 'Net VAT Payable (Tax Due)' : 'Net VAT Credit (Refundable)' }}
        </span>
        <div style="width: 36px; height: 36px; background: {{ $isPayable ? '#fef3c7' : '#d1fae5' }}; color: {{ $isPayable ? '#d97706' : '#059669' }}; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 800;">
          <i class="fas {{ $isPayable ? 'fa-scale-unbalanced' : 'fa-circle-check' }}"></i>
        </div>
      </div>
      <div style="font-size: 28px; font-weight: 900; color: {{ $isPayable ? '#92400e' : '#065f46' }};">
        UGX {{ number_format(abs($vatSummary['net_vat_payable'])) }}
      </div>
      <div style="font-size: 12px; color: #475569; font-weight: 700; margin-top: 6px;">
        Formula: <strong>Output VAT (Credit) − Input VAT (Debit)</strong>
      </div>
      <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
        {{ $isPayable ? 'Amount owed to tax authority for period' : 'Tax credit claimable / carry forward' }}
      </div>
    </div>

  </div>

  {{-- ── 4. Tab Navigation (Ledger T-Account vs Subjected Products) ── --}}
  <div style="background: #ffffff; border: 1px solid #bfdbfe; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(37,99,235,0.04);">
    <div style="display: flex; border-bottom: 1px solid #bfdbfe; background: #eff6ff;" class="no-print">
      <a href="{{ route('vat.index', array_merge(request()->query(), ['tab' => 'ledger'])) }}"
        style="padding: 16px 28px; font-weight: 800; font-size: 14px; text-decoration: none; display: flex; align-items: center; gap: 8px; border-bottom: 3px solid {{ $tab === 'ledger' ? '#2563eb' : 'transparent' }}; color: {{ $tab === 'ledger' ? '#2563eb' : '#64748b' }}; background: {{ $tab === 'ledger' ? '#ffffff' : 'transparent' }};">
        <i class="fas fa-book-bookmark"></i> VAT Accounting T-Account Ledger
      </a>
      <a href="{{ route('vat.index', array_merge(request()->query(), ['tab' => 'products'])) }}"
        style="padding: 16px 28px; font-weight: 800; font-size: 14px; text-decoration: none; display: flex; align-items: center; gap: 8px; border-bottom: 3px solid {{ $tab === 'products' ? '#2563eb' : 'transparent' }}; color: {{ $tab === 'products' ? '#2563eb' : '#64748b' }}; background: {{ $tab === 'products' ? '#ffffff' : 'transparent' }};">
        <i class="fas fa-boxes-stacked"></i> Products Subjected to VAT ({{ $vatProducts->count() }})
      </a>
    </div>

    {{-- TAB 1: VAT ACCOUNTING LEDGER (T-ACCOUNT TABLE) --}}
    @if($tab === 'ledger')
      <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
          
          {{-- Soft Light Blue Table Header --}}
          <thead style="background: #dbeafe; color: #1e40af; font-size: 11px; text-transform: uppercase; font-weight: 900; border-bottom: 2px solid #bfdbfe;">
            <tr>
              <th style="padding: 14px 20px;">Date & Time</th>
              <th style="padding: 14px 20px;">Reference / Doc #</th>
              <th style="padding: 14px 20px;">Party / Description</th>
              <th style="padding: 14px 20px; text-align: right;">Subtotal Excl. Tax</th>
              
              <!-- DEBIT COLUMN HEADER (INPUT VAT) -->
              <th style="padding: 14px 20px; text-align: right; background: #bfdbfe; color: #1e3a8a;">
                DEBIT: VAT Input (Paid)
              </th>

              <!-- CREDIT COLUMN HEADER (OUTPUT VAT) -->
              <th style="padding: 14px 20px; text-align: right; background: #93c5fd; color: #1e3a8a;">
                CREDIT: VAT Output (Collected)
              </th>

              <th style="padding: 14px 20px; text-align: right;">Gross Total</th>
            </tr>
          </thead>

          <tbody style="divide-y: 1px solid #e2e8f0;">
            @forelse($ledgerEntries as $entry)
              <tr style="border-bottom: 1px solid #f1f5f9; background: {{ $entry['side'] === 'credit' ? '#ffffff' : '#f8fafc' }};">
                <td style="padding: 14px 20px; font-weight: 700; color: #1e293b; white-space: nowrap;">
                  {{ \Carbon\Carbon::parse($entry['date'])->format('M d, Y') }}
                  <span style="display: block; font-size: 11px; color: #64748b; font-weight: 500;">{{ \Carbon\Carbon::parse($entry['date'])->format('h:i A') }}</span>
                </td>
                <td style="padding: 14px 20px; font-weight: 900; font-mono: true; font-size: 12px; color: #1e3a8a;">
                  {{ $entry['ref'] }}
                  <span style="display: block; font-size: 11px; font-weight: 700; color: {{ $entry['side'] === 'credit' ? '#2563eb' : '#0284c7' }};">
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
                <td style="padding: 14px 20px; text-align: right; font-weight: 900; color: #0284c7; background: {{ $entry['vat_in'] > 0 ? '#e0f2fe' : 'transparent' }};">
                  @if($entry['vat_in'] > 0)
                    UGX {{ number_format($entry['vat_in']) }}
                  @else
                    <span style="color: #cbd5e1;">—</span>
                  @endif
                </td>

                <!-- CREDIT SIDE (VAT OUTPUT) -->
                <td style="padding: 14px 20px; text-align: right; font-weight: 900; color: #2563eb; background: {{ $entry['vat_out'] > 0 ? '#eff6ff' : 'transparent' }};">
                  @if($entry['vat_out'] > 0)
                    UGX {{ number_format($entry['vat_out']) }}
                  @else
                    <span style="color: #cbd5e1;">—</span>
                  @endif
                </td>

                <td style="padding: 14px 20px; text-align: right; font-weight: 900; color: #1e3a8a; white-space: nowrap;">
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

          {{-- Soft Light Blue Table Summary Footer --}}
          <tfoot style="background: #dbeafe; color: #1e3a8a; font-weight: 900; font-size: 14px; border-top: 3px solid #bfdbfe;">
            <tr>
              <td colspan="3" style="padding: 16px 20px; font-size: 15px;">
                TOTAL VAT LEDGER SUMMARY
              </td>
              <td style="padding: 16px 20px; text-align: right; color: #1e3a8a;">
                UGX {{ number_format($vatSummary['total_taxable_sales'] + $vatSummary['total_taxable_purchases']) }}
              </td>
              
              <!-- TOTAL DEBIT (VAT INPUT) -->
              <td style="padding: 16px 20px; text-align: right; color: #0284c7; background: #e0f2fe; font-size: 15px;">
                UGX {{ number_format($vatSummary['total_vat_input']) }}
              </td>

              <!-- TOTAL CREDIT (VAT OUTPUT) -->
              <td style="padding: 16px 20px; text-align: right; color: #2563eb; background: #eff6ff; font-size: 15px;">
                UGX {{ number_format($vatSummary['total_vat_output']) }}
              </td>

              <td style="padding: 16px 20px; text-align: right; color: #1e3a8a;">
                UGX {{ number_format($vatSummary['total_sales_gross'] + $vatSummary['total_purchases_gross']) }}
              </td>
            </tr>

            <!-- NET VAT PAYABLE LEDGER BALANCE ROW -->
            <tr style="background: {{ $isPayable ? '#fef3c7' : '#d1fae5' }}; color: {{ $isPayable ? '#92400e' : '#065f46' }};">
              <td colspan="4" style="padding: 18px 20px; font-size: 16px; font-weight: 900;">
                <i class="fas {{ $isPayable ? 'fa-building-columns' : 'fa-hand-holding-dollar' }} mr-2"></i>
                FINAL NET VAT PAYABLE AT END OF LEDGER:
              </td>
              <td colspan="3" style="padding: 18px 20px; text-align: right; font-size: 22px; font-weight: 900; font-mono: true;">
                UGX {{ number_format(abs($vatSummary['net_vat_payable'])) }}
                <span style="font-size: 12px; font-weight: 800; text-transform: uppercase; background: rgba(37,99,235,0.1); padding: 4px 10px; border-radius: 9999px; margin-left: 8px;">
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
      <div style="overflow-x-auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
          <thead style="background: #dbeafe; color: #1e40af; font-size: 11px; text-transform: uppercase; font-weight: 900; border-bottom: 2px solid #bfdbfe;">
            <tr>
              <th style="padding: 14px 20px;">Product Name & SKU</th>
              <th style="padding: 14px 20px;">Category</th>
              <th style="padding: 14px 20px; text-align: right;">Selling Price Excl. VAT</th>
              <th style="padding: 14px 20px; text-align: right;">VAT Amount (18%)</th>
              <th style="padding: 14px 20px; text-align: right;">Final Price Incl. VAT</th>
              <th style="padding: 14px 20px; text-align: center;">Stock Quantity</th>
              <th style="padding: 14px 20px; text-align: center;">VAT Status</th>
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
                <td style="padding: 14px 20px; font-weight: 800; color: #1e3a8a;">
                  {{ $product->name }}
                  <span style="display: block; font-size: 11px; font-mono: true; color: #64748b; font-weight: 600;">SKU: {{ $product->sku ?? 'N/A' }} | Barcode: {{ $product->barcode ?? 'N/A' }}</span>
                </td>
                <td style="padding: 14px 20px; font-weight: 700; color: #334155;">
                  {{ $product->category->name ?? 'Uncategorized' }}
                </td>
                <td style="padding: 14px 20px; text-align: right; font-weight: 700; color: #475569;">
                  UGX {{ number_format($exclVat, 2) }}
                </td>
                <td style="padding: 14px 20px; text-align: right; font-weight: 900; color: #2563eb; background: #eff6ff;">
                  UGX {{ number_format($vatAmt, 2) }}
                </td>
                <td style="padding: 14px 20px; text-align: right; font-weight: 900; color: #1e3a8a;">
                  UGX {{ number_format($inclVat, 2) }}
                </td>
                <td style="padding: 14px 20px; text-align: center; font-weight: 800; color: #334155;">
                  {{ number_format($product->quantity) }} {{ $product->unit ?? 'pcs' }}
                </td>
                <td style="padding: 14px 20px; text-align: center;">
                  <span style="background: #dbeafe; color: #1d4ed8; border: 1px solid #93c5fd; padding: 4px 12px; border-radius: 9999px; font-weight: 800; font-size: 11px; text-transform: uppercase;">
                    <i class="fas fa-check-circle mr-1"></i> VAT Enabled (18%)
                  </span>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" style="padding: 48px; text-align: center; color: #64748b;">
                  <i class="fas fa-box-open" style="font-size: 36px; display: block; margin-bottom: 8px; opacity: 0.4;"></i>
                  <p style="font-size: 14px; font-weight: 700; color: #334155; margin: 0;">No products with VAT enabled found.</p>
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
