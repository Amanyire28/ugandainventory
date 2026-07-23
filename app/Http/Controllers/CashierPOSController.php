<?php

namespace App\Http\Controllers;

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
            // Generate sale number
            $lastSale = Sale::where('business_id', $businessId)->latest('id')->first();
            $nextId = $lastSale ? $lastSale->id + 1 : 1;
            $saleNumber = 'SALE-' . date('Ymd') . '-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

            // Calculate totals and validate stock
            $subtotal = 0;
            foreach ($items as $item) {
                $product = Product::findOrFail($item['id']);
                if ($product->quantity < $item['quantity']) {
                    return back()->with('error', "Insufficient stock for {$product->name}. Only {$product->quantity} left in stock.");
                }
                $subtotal += $item['price'] * $item['quantity'];
            }

            $discount = $validated['discount'] ?? 0;
            $tax = $validated['tax'] ?? 0;
            $total = $subtotal + $tax - $discount;

            // Create sale record
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

            // Create sale items and update stock
            foreach ($items as $item) {
                $product = Product::findOrFail($item['id']);

                // Check if product is out of stock
                if ($product->quantity <= 0) {
                    throw new \Exception("{$product->name} is out of stock and cannot be sold.");
                }

                // Check stock availability
                if ($product->quantity < $item['quantity']) {
                    throw new \Exception("Insufficient stock for {$product->name}. Available: {$product->quantity}");
                }

                // Create sale item
                SaleItem::create([
                    'sale_id'    => $sale->id,
                    'product_id' => $product->id,
                    'quantity'   => $item['quantity'],
                    'unit_price' => $item['price'],
                    'total'      => $item['price'] * $item['quantity'],
                ]);

                // Reduce stock
                $product->decrement('quantity', $item['quantity']);
            }

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