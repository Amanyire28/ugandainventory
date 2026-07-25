<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Purchase;
use App\Models\Sale;
use Carbon\Carbon;

class VatService
{
    /**
     * Calculate VAT summary data for a given business and date range.
     * Only includes sales, invoices, purchases, and expenses that have VAT (> 0).
     *
     * @param int $businessId
     * @param string|Carbon $startDate
     * @param string|Carbon $endDate
     * @return array
     */
    public static function calculateVatSummary(int $businessId, $startDate, $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->endOfDay();

        $business = Business::find($businessId);
        $taxRate  = $business->tax_rate ?? 18.0;

        // 1. VAT Output (Sales with tax_amount > 0)
        $salesQuery = Sale::where('business_id', $businessId)
            ->where('tax_amount', '>', 0)
            ->whereBetween('sale_date', [$start, $end]);

        $totalSalesGross   = (float) (clone $salesQuery)->sum('total');
        $salesVatOutput    = (float) (clone $salesQuery)->sum('tax_amount');
        $totalSalesTaxable = (float) (clone $salesQuery)->sum('subtotal');

        // 2. VAT Output (Invoices / Credit Sales with tax_amount > 0)
        $invoicesQuery = Invoice::where('business_id', $businessId)
            ->where('tax_amount', '>', 0)
            ->whereBetween('created_at', [$start, $end]);

        $totalInvoicesGross   = (float) (clone $invoicesQuery)->sum('total');
        $invoicesVatOutput    = (float) (clone $invoicesQuery)->sum('tax_amount');
        $totalInvoicesTaxable = (float) (clone $invoicesQuery)->sum('subtotal');

        $totalVatOutput    = $salesVatOutput + $invoicesVatOutput;
        $totalTaxableSales = $totalSalesTaxable + $totalInvoicesTaxable;

        // 3. VAT Input (Purchases with tax_amount > 0)
        $purchasesQuery = Purchase::where('business_id', $businessId)
            ->where('tax_amount', '>', 0)
            ->whereBetween('created_at', [$start, $end]);

        $totalPurchasesGross   = (float) (clone $purchasesQuery)->sum('total');
        $purchasesVatInput     = (float) (clone $purchasesQuery)->sum('tax_amount');
        $totalPurchasesTaxable = (float) (clone $purchasesQuery)->sum('subtotal');

        // 4. VAT Input (Expenses with vat_amount > 0)
        $expensesQuery = Expense::forBusiness($businessId)
            ->where('vat_amount', '>', 0)
            ->whereBetween('date_spent', [$start, $end]);

        $totalExpensesGross = (float) (clone $expensesQuery)->sum('amount');
        $expensesVatInput   = (float) (clone $expensesQuery)->sum('vat_amount');

        $totalVatInput = $purchasesVatInput + $expensesVatInput;
        $totalTaxablePurchases = $totalPurchasesTaxable + ($totalExpensesGross - $expensesVatInput);

        // 5. Net VAT Payable / Refundable
        $netVatPayable = $totalVatOutput - $totalVatInput;

        $vatStatus = match (true) {
            $netVatPayable > 0 => 'payable',
            $netVatPayable < 0 => 'refundable',
            default            => 'zero',
        };

        return [
            'business'               => $business,
            'tax_rate'               => $taxRate,
            'start_date'             => $start,
            'end_date'               => $end,
            'total_sales_gross'      => $totalSalesGross + $totalInvoicesGross,
            'total_taxable_sales'    => $totalTaxableSales,
            'sales_vat_output'       => $salesVatOutput,
            'invoices_vat_output'    => $invoicesVatOutput,
            'total_vat_output'       => $totalVatOutput,
            'total_purchases_gross'  => $totalPurchasesGross,
            'total_expenses_gross'   => $totalExpensesGross,
            'purchases_vat_input'    => $purchasesVatInput,
            'expenses_vat_input'     => $expensesVatInput,
            'total_vat_input'        => $totalVatInput,
            'total_taxable_purchases'=> $totalTaxablePurchases,
            'net_vat_payable'        => $netVatPayable,
            'vat_status'             => $vatStatus,
        ];
    }
}
