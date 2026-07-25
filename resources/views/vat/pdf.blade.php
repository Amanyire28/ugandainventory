<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>VAT Accounting Statement - {{ $business->name }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1e3a8a;
            margin: 0;
            padding: 20px;
            background: #ffffff;
        }
        .header {
            width: 100%;
            margin-bottom: 20px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 12px;
        }
        .header table {
            width: 100%;
            border-collapse: collapse;
        }
        .title {
            font-size: 20px;
            font-weight: bold;
            color: #1e3a8a;
            margin: 0;
        }
        .subtitle {
            font-size: 11px;
            color: #2563eb;
            margin-top: 4px;
            font-weight: bold;
        }
        .biz-name {
            font-size: 14px;
            font-weight: bold;
            color: #1e3a8a;
            text-align: right;
        }
        .biz-tin {
            font-size: 10px;
            color: #475569;
            text-align: right;
        }
        .kpi-container {
            width: 100%;
            margin-bottom: 20px;
        }
        .kpi-table {
            width: 100%;
            border-collapse: collapse;
        }
        .kpi-card {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 10px;
        }
        .kpi-label {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            color: #1d4ed8;
        }
        .kpi-value {
            font-size: 16px;
            font-weight: bold;
            color: #1e3a8a;
            margin-top: 4px;
        }
        .ledger-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 10px;
        }
        .ledger-table th {
            background-color: #dbeafe;
            color: #1e40af;
            font-weight: bold;
            text-transform: uppercase;
            padding: 8px 6px;
            border: 1px solid #bfdbfe;
            font-size: 9px;
        }
        .ledger-table td {
            padding: 6px;
            border-bottom: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
            border-left: 1px solid #e2e8f0;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .vat-in {
            background-color: #e0f2fe;
            color: #0284c7;
            font-weight: bold;
        }
        .vat-out {
            background-color: #eff6ff;
            color: #2563eb;
            font-weight: bold;
        }
        .summary-foot {
            background-color: #dbeafe;
            color: #1e3a8a;
            font-weight: bold;
        }
        .payable-box {
            margin-top: 15px;
            padding: 10px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: bold;
        }
        .footer-note {
            margin-top: 30px;
            font-size: 9px;
            color: #64748b;
            text-align: center;
            border-top: 1px solid #cbd5e1;
            padding-top: 8px;
        }
    </style>
</head>
<body>

    <div class="header">
        <table>
            <tr>
                <td>
                    <div class="title">VAT ACCOUNTING LEDGER STATEMENT</div>
                    <div class="subtitle">
                        Period: {{ $startDate->format('M d, Y') }} — {{ $endDate->format('M d, Y') }} | Tax Rate: {{ number_format($vatSummary['tax_rate'], 1) }}%
                    </div>
                </td>
                <td class="text-right">
                    <div class="biz-name">{{ $business->name }}</div>
                    @if($business->tax_number)
                        <div class="biz-tin">TIN: {{ $business->tax_number }}</div>
                    @endif
                    <div class="biz-tin">Generated: {{ now()->format('M d, Y h:i A') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- KPI Summary Grid -->
    <div class="kpi-container">
        <table class="kpi-table">
            <tr>
                <td style="width: 33%; padding-right: 6px;">
                    <div class="kpi-card">
                        <div class="kpi-label">VAT Output (Credit / Sales)</div>
                        <div class="kpi-value">UGX {{ number_format($vatSummary['total_vat_output']) }}</div>
                    </div>
                </td>
                <td style="width: 33%; padding: 0 3px;">
                    <div class="kpi-card" style="background: #e0f2fe; border-color: #7dd3fc;">
                        <div class="kpi-label" style="color: #0369a1;">VAT Input (Debit / Purchases)</div>
                        <div class="kpi-value" style="color: #0369a1;">UGX {{ number_format($vatSummary['total_vat_input']) }}</div>
                    </div>
                </td>
                @php $isPayable = $vatSummary['net_vat_payable'] >= 0; @endphp
                <td style="width: 33%; padding-left: 6px;">
                    <div class="kpi-card" style="background: {{ $isPayable ? '#fef3c7' : '#d1fae5' }}; border-color: {{ $isPayable ? '#fde68a' : '#a7f3d0' }};">
                        <div class="kpi-label" style="color: {{ $isPayable ? '#b45309' : '#047857' }};">
                            {{ $isPayable ? 'Net VAT Payable (Tax Due)' : 'Net VAT Credit' }}
                        </div>
                        <div class="kpi-value" style="color: {{ $isPayable ? '#92400e' : '#065f46' }};">
                            UGX {{ number_format(abs($vatSummary['net_vat_payable'])) }}
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Detailed Ledger Table -->
    <table class="ledger-table">
        <thead>
            <tr>
                <th style="width: 14%;">Date</th>
                <th style="width: 16%;">Doc Ref #</th>
                <th>Party / Description</th>
                <th class="text-right" style="width: 15%;">Subtotal Excl. Tax</th>
                <th class="text-right" style="width: 15%; background-color: #bfdbfe;">DEBIT: VAT Input</th>
                <th class="text-right" style="width: 15%; background-color: #93c5fd;">CREDIT: VAT Output</th>
                <th class="text-right" style="width: 15%;">Gross Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ledgerEntries as $entry)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($entry['date'])->format('M d, Y') }}</td>
                    <td style="font-weight: bold; font-family: monospace;">{{ $entry['ref'] }}</td>
                    <td>{{ $entry['party'] }}</td>
                    <td class="text-right">UGX {{ number_format($entry['subtotal']) }}</td>
                    <td class="text-right {{ $entry['vat_in'] > 0 ? 'vat-in' : '' }}">
                        {{ $entry['vat_in'] > 0 ? 'UGX ' . number_format($entry['vat_in']) : '-' }}
                    </td>
                    <td class="text-right {{ $entry['vat_out'] > 0 ? 'vat-out' : '' }}">
                        {{ $entry['vat_out'] > 0 ? 'UGX ' . number_format($entry['vat_out']) : '-' }}
                    </td>
                    <td class="text-right" style="font-weight: bold;">UGX {{ number_format($entry['total']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px; color: #64748b;">
                        No VAT transactions recorded for the selected period.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="summary-foot">
                <td colspan="3">TOTAL VAT LEDGER SUMMARY</td>
                <td class="text-right">UGX {{ number_format($vatSummary['total_taxable_sales'] + $vatSummary['total_taxable_purchases']) }}</td>
                <td class="text-right vat-in">UGX {{ number_format($vatSummary['total_vat_input']) }}</td>
                <td class="text-right vat-out">UGX {{ number_format($vatSummary['total_vat_output']) }}</td>
                <td class="text-right">UGX {{ number_format($vatSummary['total_sales_gross'] + $vatSummary['total_purchases_gross']) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="payable-box" style="background: {{ $isPayable ? '#fef3c7' : '#d1fae5' }}; color: {{ $isPayable ? '#92400e' : '#065f46' }};">
        FINAL NET VAT POSITION: UGX {{ number_format(abs($vatSummary['net_vat_payable'])) }}
        ({{ $isPayable ? 'VAT TAX PAYABLE DUE TO AUTHORITY' : 'VAT CREDIT REFUNDABLE / CARRY FORWARD' }})
    </div>

    <div class="footer-note">
        Official VAT Accounting Statement generated by {{ config('app.name', 'Inventory System') }} for {{ $business->name }}.
    </div>

</body>
</html>
