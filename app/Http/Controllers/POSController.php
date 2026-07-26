<?php

namespace App\Http\Controllers;
use App\Services\MailerService;
use App\Models\{Product, Customer, Sale, SaleItem, Category};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Mail, Log};
use App\Mail\SaleReceiptMail; // ✅ ADD THIS

class POSController extends Controller
{
    /**
     * Show POS interface - different view based on role
     */
    public function index()
    {
        $user = Auth::user();
        $businessId = $user->business_id;
        $userRole = $user->role->name;

        // Get all active products for the business
        $products = Product::where('business_id', $businessId)
            ->where('is_active', true)
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

        // Get 5 most recent sales for F12 Void modal
        $recentSales = Sale::where('business_id', $businessId)
            ->with(['customer', 'user', 'items.product'])
            ->latest('sale_date')
            ->take(5)
            ->get();

        // Use same POS view for both owner and cashier
        return view('pos.index', compact('products', 'categories', 'customers', 'recentSales'));
    }

    /**
     * Fetch recent 5 sales for F12 Void Modal
     */
    public function getRecentSales()
    {
        $businessId = Auth::user()->business_id;
        $sales = Sale::where('business_id', $businessId)
            ->with(['customer', 'user', 'items.product'])
            ->latest('sale_date')
            ->take(5)
            ->get()
            ->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'sale_number' => $sale->sale_number,
                    'sale_date' => $sale->sale_date->format('M d, Y h:i A'),
                    'customer_name' => $sale->customer ? $sale->customer->name : 'Walk-in Customer',
                    'cashier_name' => $sale->user ? $sale->user->name : 'Cashier',
                    'items_count' => $sale->items->count(),
                    'total' => $sale->total,
                    'status' => $sale->status ?? 'completed',
                    'is_voided' => $sale->isVoided(),
                    'void_reason' => $sale->void_reason,
                    'voided_at' => $sale->voided_at ? \Carbon\Carbon::parse($sale->voided_at)->format('M d, Y h:i A') : null,
                ];
            });

        return response()->json($sales);
    }

    /**
     * Process sale transaction
     * ✅ FIXED: Now sends email receipt
     */
    public function process(Request $request)
    {
        $user = Auth::user();
        $businessId = $user->business_id;

        // Validation rules
        $rules = [
            'customer_option' => ['required', 'in:walk_in,existing,new'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'add_tax' => ['nullable', 'boolean'],
            'amount_paid' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];

        // Add customer validation
        if ($request->customer_option === 'existing') {
            $rules['customer_id'] = ['required', 'exists:customers,id'];
        } elseif ($request->customer_option === 'new') {
            $rules['new_customer_name'] = ['required', 'string', 'max:255'];
            $rules['new_customer_phone'] = ['required', 'string', 'max:20'];
            $rules['new_customer_email'] = ['nullable', 'email', 'max:255'];
            $rules['new_customer_address'] = ['nullable', 'string', 'max:500'];
        }

        $validated = $request->validate($rules);

        DB::beginTransaction();

        try {
            $customerId = null;

            // Handle customer
            if ($validated['customer_option'] === 'existing') {
                $customerId = $validated['customer_id'];
            } elseif ($validated['customer_option'] === 'new') {
                $customer = Customer::create([
                    'business_id' => $businessId,
                    'name' => $validated['new_customer_name'],
                    'phone' => $validated['new_customer_phone'],
                    'email' => $validated['new_customer_email'] ?? null,
                    'address' => $validated['new_customer_address'] ?? null,
                    'is_active' => true,
                ]);
                $customerId = $customer->id;
            }

            // Calculate totals and validate stock
            $subtotal = 0;
            $autoVat = 0;
            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                if ($product->quantity < $item['quantity']) {
                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient stock for {$product->name}. Only {$product->quantity} left in stock.",
                    ], 400);
                }
                $itemTotal = $item['quantity'] * $item['price'];
                $subtotal += $itemTotal;

                if ($product->requires_vat ?? true) {
                    $autoVat += $itemTotal * 0.18;
                }
            }

            $discount = $validated['discount'] ?? 0;
            
            // Tax calculation (Per-item VAT or manual tax)
            $taxAmount = $autoVat;
            if ($taxAmount == 0 && isset($validated['add_tax']) && $validated['add_tax'] == true) {
                $taxAmount = max(0, ($subtotal - $discount) * 0.18);
            }
            
            $total = $subtotal - $discount + $taxAmount;

            // Validate payment
            if ($validated['amount_paid'] < $total) {
                return response()->json([
                    'success' => false,
                    'message' => 'Amount paid (UGX ' . number_format($validated['amount_paid'], 0) . ') is less than total (UGX ' . number_format($total, 0) . ')!',
                ], 400);
            }

            // Generate sale number
            $today = now()->format('Ymd');
            $count = Sale::where('business_id', $businessId)
                ->whereDate('created_at', today())
                ->count() + 1;
            $saleNumber = 'SAL-' . $today . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

            // Create sale
            $sale = Sale::create([
                'business_id' => $businessId,
                'user_id' => $user->id,
                'customer_id' => $customerId,
                'sale_number' => $saleNumber,
                'sale_date' => now(),
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discount,
                'total' => $total,
                'payment_status' => 'paid',
                'payment_method' => 'cash',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create sale items
            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);

                // Check if product is out of stock
                if ($product->quantity <= 0) {
                    throw new \Exception("{$product->name} is out of stock and cannot be sold.");
                }

                // Check stock
                if ($product->quantity < $item['quantity']) {
                    throw new \Exception("Insufficient stock for {$product->name}. Available: {$product->quantity}");
                }

                // Create sale item
                $saleItem = new SaleItem();
                $saleItem->sale_id = $sale->id;
                $saleItem->product_id = $item['product_id'];
                $saleItem->quantity = $item['quantity'];
                $saleItem->unit_price = $item['price'];
                $saleItem->total = $item['quantity'] * $item['price'];
                $saleItem->save();

                // Update stock
                $product->decrement('quantity', $item['quantity']);
            }

            DB::commit();

            // ✅ SEND EMAIL RECEIPT (After successful sale)
            $emailMessage = '';
            if ($sale->customer && $sale->customer->email) {
                try {
                    // Load relationships needed for email
                    $sale->load(['business', 'customer', 'items.product', 'user']);
                    
                           MailerService::sendSaleReceipt($sale);
                    
                    $emailMessage = ' | Receipt sent to ' . $sale->customer->email;
                    
                    Log::info('Receipt email sent successfully', [
                        'sale_id' => $sale->id,
                        'customer_email' => $sale->customer->email,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send receipt email', [
                        'sale_id' => $sale->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    // Don't fail the sale if email fails
                    $emailMessage = ' | (Email failed to send)';
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Sale completed successfully!' . $emailMessage,
                'sale_id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'total' => $total,
                'amount_paid' => $validated['amount_paid'],
                'change' => $validated['amount_paid'] - $total,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('POS Sale Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get product details
     */
    public function getProduct($id)
    {
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
            'unit' => $product->unit ?? 'pcs',
            'category' => $product->category->name ?? 'Uncategorized',
            'image' => $product->image_url ?? null,
        ]);
    }

    /**
     * Show receipt
     */
    public function receipt($id)
    {
        $user = Auth::user();
        $userRole = $user->role->name;

        $sale = Sale::where('id', $id)
            ->where('business_id', $user->business_id)
            ->with(['customer', 'user', 'items.product', 'user.business'])
            ->firstOrFail();

        if ($userRole === 'cashier' && $sale->user_id !== $user->id) {
            abort(403, 'You can only view your own sales.');
        }

        if ($userRole === 'cashier') {
            return view('cashier.receipt', compact('sale'));
        }

        return view('pos.receipt', compact('sale'));
    }

    /**
     * Print receipt
     */
    public function printReceipt($id)
    {
        $user = Auth::user();
        $userRole = $user->role->name;

        $sale = Sale::where('id', $id)
            ->where('business_id', $user->business_id)
            ->with(['customer', 'user', 'items.product', 'user.business'])
            ->firstOrFail();

        if ($userRole === 'cashier' && $sale->user_id !== $user->id) {
            abort(403);
        }

        return view('pos.receipt-print', compact('sale'));
    }
}