<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\SupplierTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PurchaseController extends Controller
{
    // ─────────────────────────────────────────────
    // LIST — All purchases (AJAX tab support)
    // ─────────────────────────────────────────────
    public function index(Request $request)
    {
        $user       = Auth::user();
        $businessId = $user->business_id;
        $status     = $request->query('status');   // paid | partial | unpaid
        $search     = $request->query('search');

        $query = Purchase::with(['supplier', 'user', 'items'])
            ->where('business_id', $businessId)
            ->orderByDesc('purchase_date')
            ->orderByDesc('id');

        if ($status && in_array($status, ['paid', 'partial', 'unpaid'])) {
            $query->where('payment_status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('purchase_number', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('supplier', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $purchases = $query->paginate(20)->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('purchases.partials.table', compact('purchases'))->render(),
            ]);
        }

        return view('purchases.index', compact('purchases', 'status', 'search'));
    }

    // ─────────────────────────────────────────────
    // CREATE — Show form
    // ─────────────────────────────────────────────
    public function create()
    {
        $user       = Auth::user();
        $businessId = $user->business_id;

        $suppliers = Supplier::where('business_id', $businessId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $products = Product::where('business_id', $businessId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'unit', 'cost_price', 'quantity']);

        return view('purchases.create', compact('suppliers', 'products'));
    }

    // ─────────────────────────────────────────────
    // STORE — Save purchase + update stock + ledger
    // ─────────────────────────────────────────────
    public function store(Request $request)
    {
        $user       = Auth::user();
        $businessId = $user->business_id;

        $validated = $request->validate([
            'supplier_id'    => 'nullable|exists:suppliers,id',
            'purchase_date'  => 'required|date',
            'payment_status' => 'required|in:paid,partial,unpaid',
            'amount_paid'    => 'nullable|numeric|min:0',
            'notes'          => 'nullable|string|max:1000',
            'items'          => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|numeric|min:0.01',
            'items.*.unit_cost'  => 'required|numeric|min:0',
            'items.*.has_vat'    => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            // Generate unique purchase number
            $lastNumber = Purchase::where('business_id', $businessId)
                ->orderByDesc('id')->value('purchase_number') ?? 'PO-00000000-0000';
            $sequence = ((int) substr($lastNumber, -4)) + 1;
            $purchaseNumber = 'PO-' . now()->format('Ymd') . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);

            // Calculate totals and VAT
            $subtotal = 0;
            $taxAmount = 0;
            foreach ($validated['items'] as $item) {
                $lineTotal = $item['quantity'] * $item['unit_cost'];
                $subtotal += $lineTotal;

                if (!empty($item['has_vat']) && $item['has_vat'] == 1) {
                    $taxAmount += $lineTotal * 0.18;
                }
            }
            $total = $subtotal + $taxAmount;

            // Amount paid
            $amountPaid = 0;
            if ($validated['payment_status'] === 'paid') {
                $amountPaid = $total;
            } elseif ($validated['payment_status'] === 'partial') {
                $amountPaid = (float) ($validated['amount_paid'] ?? 0);
            }

            // Resolve location_id: use user's assigned location or fall back to first business location
            $locationId = $user->location_id
                ?? \App\Models\Location::where('business_id', $businessId)->value('id')
                ?? 1;

            // Create purchase header
            $purchase = Purchase::create([
                'business_id'    => $businessId,
                'location_id'    => $locationId,
                'supplier_id'    => $validated['supplier_id'] ?? null,
                'user_id'        => $user->id,
                'purchase_number'=> $purchaseNumber,
                'purchase_date'  => Carbon::parse($validated['purchase_date']),
                'subtotal'       => $subtotal,
                'tax_amount'     => $taxAmount,
                'total'          => $total,
                'payment_status' => $validated['payment_status'],
                'notes'          => $validated['notes'] ?? null,
            ]);

            // Create items & update product stock / cost price
            foreach ($validated['items'] as $itemData) {
                $lineTotal = $itemData['quantity'] * $itemData['unit_cost'];

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id'  => $itemData['product_id'],
                    'quantity'    => $itemData['quantity'],
                    'unit_cost'   => $itemData['unit_cost'],
                    'total'       => $lineTotal,
                ]);

                // Increment product stock and update cost price
                $product = Product::find($itemData['product_id']);
                if ($product && $product->business_id === $businessId) {
                    $product->increment('quantity', $itemData['quantity']);
                    // Update cost price to latest purchase cost
                    $product->update(['cost_price' => $itemData['unit_cost']]);
                }
            }

            // Supplier ledger transaction
            if ($validated['supplier_id']) {
                $lastBalance = SupplierTransaction::where('supplier_id', $validated['supplier_id'])
                    ->orderByDesc('id')
                    ->value('balance') ?? 0;

                $newBalance = $lastBalance + $total - $amountPaid;

                SupplierTransaction::create([
                    'supplier_id'      => $validated['supplier_id'],
                    'purchase_id'      => $purchase->id,
                    'transaction_type' => 'purchase',
                    'debit'            => $total,
                    'credit'           => $amountPaid,
                    'balance'          => $newBalance,
                    'notes'            => "Purchase #{$purchaseNumber}" . ($amountPaid > 0 ? " — paid UGX " . number_format($amountPaid) : ''),
                ]);
            }

            // Audit log
            AuditLog::log(
                'purchase_recorded',
                Purchase::class,
                $purchase->id,
                null,
                ['purchase_number' => $purchaseNumber, 'total' => $total, 'payment_status' => $validated['payment_status']]
            );

            DB::commit();

            return redirect()->route('purchases.show', $purchase->id)
                ->with('success', "Purchase #{$purchaseNumber} recorded successfully! Stock updated. ✅");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to record purchase: ' . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────
    // SHOW — Purchase detail
    // ─────────────────────────────────────────────
    public function show(Purchase $purchase)
    {
        $user = Auth::user();
        if ($purchase->business_id !== $user->business_id) {
            abort(403);
        }

        $purchase->load(['supplier', 'user', 'items.product']);

        return view('purchases.show', compact('purchase'));
    }

    // ─────────────────────────────────────────────
    // DESTROY — Soft-delete + reverse stock
    // ─────────────────────────────────────────────
    public function destroy(Purchase $purchase)
    {
        $user = Auth::user();
        if ($purchase->business_id !== $user->business_id) {
            abort(403);
        }

        DB::beginTransaction();
        try {
            $purchase->load('items.product');

            // Reverse stock for each item
            foreach ($purchase->items as $item) {
                if ($item->product && $item->product->business_id === $user->business_id) {
                    $item->product->decrement('quantity', $item->quantity);
                }
            }

            // Soft-delete
            $purchase->delete();

            // Audit log
            AuditLog::log(
                'purchase_cancelled',
                Purchase::class,
                $purchase->id,
                ['purchase_number' => $purchase->purchase_number],
                ['status' => 'cancelled']
            );

            DB::commit();

            return redirect()->route('purchases.index')
                ->with('success', "Purchase #{$purchase->purchase_number} cancelled and stock reversed.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to cancel purchase: ' . $e->getMessage());
        }
    }
}
