<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Services\VatService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VatController extends Controller
{
    /**
     * Display the main VAT Accounting Ledger & Management Dashboard.
     */
    public function index(Request $request)
    {
        $user       = Auth::user();
        $businessId = $user->business_id;
        $business   = $user->business;

        $period = $request->get('period', 'month');
        $tab    = $request->get('tab', 'ledger');

        $startDate = match ($period) {
            'today'   => now()->startOfDay(),
            'week'    => now()->startOfWeek(),
            'month'   => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            'year'    => now()->startOfYear(),
            'custom'  => $request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : now()->startOfMonth(),
            default   => now()->startOfMonth(),
        };

        $endDate = match ($period) {
            'custom' => $request->filled('end_date') ? Carbon::parse($request->end_date)->endOfDay() : now()->endOfDay(),
            default  => now()->endOfDay(),
        };

        // 1. VAT Summary Totals
        $vatSummary = VatService::calculateVatSummary($businessId, $startDate, $endDate);

        // 2. Fetch Detailed Ledger Transactions (Sales, Invoices, Purchases, Expenses)
        $ledgerEntries = collect();

        // Sales (VAT Output / Credit) - only sales with tax_amount > 0
        $sales = Sale::where('business_id', $businessId)
            ->where('tax_amount', '>', 0)
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->with('customer')
            ->get();

        foreach ($sales as $sale) {
            $ledgerEntries->push([
                'date'          => $sale->sale_date ?? $sale->created_at,
                'ref'           => 'SALE #' . $sale->sale_number,
                'type'          => 'Sales (Output)',
                'side'          => 'credit', // Credit = VAT Output
                'party'         => $sale->customer->name ?? 'Walk-in Customer',
                'subtotal'      => (float) $sale->subtotal,
                'vat_rate'      => $business->tax_rate ?? 18.0,
                'vat_in'        => 0.00,
                'vat_out'       => (float) $sale->tax_amount,
                'total'         => (float) $sale->total,
            ]);
        }

        // Invoices (VAT Output / Credit) - only invoices with tax_amount > 0
        $invoices = Invoice::where('business_id', $businessId)
            ->where('tax_amount', '>', 0)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('customer')
            ->get();

        foreach ($invoices as $inv) {
            $ledgerEntries->push([
                'date'          => $inv->created_at,
                'ref'           => 'INV #' . $inv->invoice_number,
                'type'          => 'Invoice (Output)',
                'side'          => 'credit',
                'party'         => $inv->customer->name ?? 'Customer Invoice',
                'subtotal'      => (float) $inv->subtotal,
                'vat_rate'      => $business->tax_rate ?? 18.0,
                'vat_in'        => 0.00,
                'vat_out'       => (float) $inv->tax_amount,
                'total'         => (float) $inv->total,
            ]);
        }

        // Purchases (VAT Input / Debit) - only purchases with tax_amount > 0
        $purchases = Purchase::where('business_id', $businessId)
            ->where('tax_amount', '>', 0)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('supplier')
            ->get();

        foreach ($purchases as $pur) {
            $ledgerEntries->push([
                'date'          => $pur->created_at,
                'ref'           => 'PUR #' . $pur->purchase_number,
                'type'          => 'Purchase (Input)',
                'side'          => 'debit', // Debit = VAT Input
                'party'         => $pur->supplier->name ?? 'Supplier Purchase',
                'subtotal'      => (float) $pur->subtotal,
                'vat_rate'      => $business->tax_rate ?? 18.0,
                'vat_in'        => (float) $pur->tax_amount,
                'vat_out'       => 0.00,
                'total'         => (float) $pur->total,
            ]);
        }

        // Expenses (VAT Input / Debit) - only expenses with vat_amount > 0
        $expenses = Expense::forBusiness($businessId)
            ->where('vat_amount', '>', 0)
            ->whereBetween('date_spent', [$startDate, $endDate])
            ->get();

        foreach ($expenses as $exp) {
            if ($exp->vat_amount > 0) {
                $ledgerEntries->push([
                    'date'          => $exp->date_spent,
                    'ref'           => 'EXP #' . $exp->id,
                    'type'          => 'Expense (Input)',
                    'side'          => 'debit',
                    'party'         => $exp->purpose ?? 'Business Expense',
                    'subtotal'      => (float) ($exp->amount - $exp->vat_amount),
                    'vat_rate'      => $business->tax_rate ?? 18.0,
                    'vat_in'        => (float) $exp->vat_amount,
                    'vat_out'       => 0.00,
                    'total'         => (float) $exp->amount,
                ]);
            }
        }

        // Sort ledger entries chronologically
        $ledgerEntries = $ledgerEntries->sortBy('date')->values();

        // 3. Products Subjected to VAT
        $productsQuery = Product::where('business_id', $businessId)
            ->where('is_active', true)
            ->with('category');

        if ($request->filled('search')) {
            $term = $request->search;
            $productsQuery->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('sku', 'like', "%{$term}%");
            });
        }

        $vatProducts = $productsQuery->orderBy('name')->paginate(25);

        return view('vat.index', compact(
            'business',
            'vatSummary',
            'ledgerEntries',
            'vatProducts',
            'period',
            'tab',
            'startDate',
            'endDate'
        ));
    }
}
