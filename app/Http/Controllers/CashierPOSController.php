<?php

namespace App\Http\Controllers;

use App\Services\NumberGenerator;
use App\Services\InventoryService;
use App\Models\{Product, Customer, Sale, SaleItem, Category};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB};

class CashierPOSController extends Controller
{
    /**
     * Check if user is cashier
     */
    private function checkCashierRole()
    {
        if (Auth::user()->role->name !== 'cashier') {
            abort(403, 'Only cashiers can access POS.');
        }
    }

    /**
     * Show POS interface for cashier
     */
    public function index()
    {
        $this->checkCashierRole();

        $user = Auth::user();
        $businessId = $user->business_id;

        // Get active products with stock
        $products = Product::where('business_id', $businessId)
            ->where('is_active', true)
            ->where('quantity', '>', 0)
            ->with('category')
            ->orderBy('name')
            ->get();

        // Get categories for filtering
        $categories = Category::where('business_id', $businessId)
            ->withCount(['products' => function($query) {
                $query->where('is_active', true)->where('quantity', '>', 0);
            }])
            ->having('products_count', '>', 0)
            ->orderBy('name')
            ->get();

        // Get active customers
        $customers = Customer::where('business_id', $businessId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('cashier.pos', compact('products', 'categories', 'customers'));
    }

    /**
     * Process sale transaction
     */
    public function process(Request $request)
    {
        $this->checkCashierRole();

        $user = Auth::user();
        $businessId = $user->business_id;

        // Validate request
        $validated = $request->validate([
            'items' => 'required|json',
            'customer_id' => 'nullable|exists:customers,id',
            'payment_method' => 'required|in:cash,mobile_money,card,bank_transfer',
            'total' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $items = json_decode($validated['items'], true);

        if (empty($items)) {
            return back()->with('error', 'Cart is empty! Add products to cart first.');
        }

        DB::beginTransaction();

        try {
            // Generate sale number via centralized service (concurrency-safe)
            $saleNumber = (new NumberGenerator())->nextSaleNumber($businessId);

            // Calculate totals — note: stock is NOT validated here.
            // Validation happens inside InventoryService after the lock is acquired.
            $subtotal = 0;
            foreach ($items as $item) {
                $subtotal += $item['price'] * $item['quantity'];
            }

            $discount = $validated['discount'] ?? 0;
            $tax      = $validated['tax'] ?? 0;
            $total    = $subtotal + $tax - $discount;

            // Create sale header
            $sale = Sale::create([
                'business_id'     => $businessId,
                'user_id'         => $user->id,
                'customer_id'     => $validated['customer_id'],
                'sale_number'     => $saleNumber,
                'sale_date'       => now(),
                'subtotal'        => $subtotal,
                'tax_amount'      => $tax,
                'discount_amount' => $discount,
                'total'           => $total,
                'payment_method'  => $validated['payment_method'],
                'payment_status'  => 'paid',
                'notes'           => $validated['notes'] ?? null,
            ]);

            // Deduct stock via InventoryService — locks rows in ascending product_id
            // order before validating and decrementing. Throws on insufficient stock.
            $inventoryItems = array_map(fn($i) => [
                'product_id' => $i['id'],
                'quantity'   => $i['quantity'],
                'price'      => $i['price'],
            ], $items);

            (new InventoryService())->deductForSale($sale, $inventoryItems, $user->id);

            // Record Customer Transaction if applicable
            if ($sale->customer_id) {
                $prevBal = \App\Models\CustomerTransaction::where('customer_id', $sale->customer_id)
                    ->orderBy('id', 'desc')
                    ->value('balance') ?? 0;
                \App\Models\CustomerTransaction::create([
                    'customer_id' => $sale->customer_id,
                    'sale_id' => $sale->id,
                    'transaction_type' => 'SALE',
                    'debit' => $sale->total,
                    'credit' => $sale->total, // Fully paid cash sale
                    'balance' => $prevBal,
                    'notes' => "Cashier POS Cash Sale #{$sale->sale_number}",
                ]);
            }

            // Log Audit
            \App\Models\AuditLog::log('pos_sale', Sale::class, $sale->id, null, $sale->toArray());

            DB::commit();

            // Redirect to receipt
            return redirect()->route('cashier.pos.receipt', $sale->id)
                ->with('success', "Sale completed successfully! 💰");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Sale failed: ' . $e->getMessage());
        }
    }

    /**
     * Get product details (AJAX)
     */
    public function getProduct($id)
    {
        $this->checkCashierRole();

        $product = Product::where('id', $id)
            ->where('business_id', Auth::user()->business_id)
            ->where('is_active', true)
            ->with('category')
            ->first();

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'price' => $product->selling_price,
            'stock' => $product->quantity,
            'category' => $product->category->name ?? 'Uncategorized',
            'image' => $product->image_url,
        ]);
    }

    /**
     * Show receipt after sale
     */
    public function receipt($id)
    {
        $this->checkCashierRole();

        $sale = Sale::where('id', $id)
            ->where('user_id', Auth::user()->id)
            ->where('business_id', Auth::user()->business_id)
            ->with(['customer', 'user', 'items.product', 'user.business'])
            ->firstOrFail();

        return view('cashier.receipt', compact('sale'));
    }

    /**
     * Print receipt (printer-friendly)
     */
    public function printReceipt($id)
    {
        $this->checkCashierRole();

        $sale = Sale::where('id', $id)
            ->where('user_id', Auth::user()->id)
            ->where('business_id', Auth::user()->business_id)
            ->with(['customer', 'user', 'items.product', 'user.business'])
            ->firstOrFail();

        return view('cashier.receipt-print', compact('sale'));
    }
}