<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{
    Invoice, InvoiceItem, Customer, Product, User, Business, Sale, SaleItem
};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\MailerService;

class CashierInvoiceController extends Controller
{
    // List only this cashier's invoices, with search and status filters
    public function index(Request $request)
    {
        $status = $request->query('status');
        $search = $request->query('search');
        $customerId = $request->query('customer_id');
        $cashierId = Auth::id();

        $query = Invoice::with('customer')->where('user_id', $cashierId)->orderByDesc('created_at');

        if ($status) {
            $query->where('status', $status);
        }
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', "%$search%")
                  ->orWhereHas('customer', function($q2) use ($search) {
                      $q2->where('name', 'like', "%$search%");
                  });
            });
        }
        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        $invoices = $query->paginate(20);
        $customers = Customer::orderBy('name')->get();

        return view('cashier.invoices.index', compact('invoices', 'status', 'search', 'customerId', 'customers'));
    }

    // PAID AJAX FILTER (for this cashier)
    public function paid(Request $request)
    {
        $search = $request->query('search');
        $cashierId = Auth::id();
        $query = Invoice::with('customer')
                    ->where('status', 'paid')
                    ->where('user_id', $cashierId)
                    ->orderByDesc('created_at');
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', "%$search%")
                  ->orWhereHas('customer', function($q2) use ($search) {
                      $q2->where('name', 'like', "%$search%");
                  });
            });
        }
        $invoices = $query->limit(20)->get();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('cashier.invoices.partials.table', compact('invoices'))->render()
            ]);
        }

        return view('cashier.invoices.index', [
            'status' => 'paid',
            'invoices' => $invoices
        ]);
    }

    // UNPAID AJAX FILTER (for this cashier)
    public function unpaid(Request $request)
    {
        $search = $request->query('search');
        $cashierId = Auth::id();
        $query = Invoice::with('customer')
                    ->where('status', 'unpaid')
                    ->where('user_id', $cashierId)
                    ->orderByDesc('created_at');
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', "%$search%")
                  ->orWhereHas('customer', function($q2) use ($search) {
                      $q2->where('name', 'like', "%$search%");
                  });
            });
        }
        $invoices = $query->limit(20)->get();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('cashier.invoices.partials.table', compact('invoices'))->render()
            ]);
        }

        return view('cashier.invoices.index', [
            'status' => 'unpaid',
            'invoices' => $invoices
        ]);
    }

    // SHOW a single invoice, for this cashier only
    public function show($id)
    {
        $cashierId = Auth::id();
        $invoice = Invoice::with(['customer','user','items.product'])
            ->where('user_id', $cashierId)
            ->findOrFail($id);
        $business = Business::findOrFail($invoice->business_id);
        return view('cashier.invoices.show', compact('invoice', 'business'));
    }

    // PRINTABLE invoice view (cashier only)
    public function print($id)
    {
        $cashierId = Auth::id();
        $invoice = Invoice::with(['customer','user','items.product'])
            ->where('user_id', $cashierId)
            ->findOrFail($id);
        $business = Business::findOrFail($invoice->business_id);
        return view('cashier.invoices.print', compact('invoice', 'business'));
    }

public function destroy($id)
{
    $cashierId = auth::id();
    $invoice = Invoice::where('user_id', $cashierId)->findOrFail($id);

    if ($invoice->status !== 'paid') {
        return back()->with('error', 'You can only delete invoices that are PAID.');
    }

    $invoice->delete();
    return redirect()->route('cashier.invoices.index')->with('success', 'Invoice deleted!');
}

    // EDIT invoice UI, with history
    public function edit($id)
    {
        $cashierId = Auth::id();
        $invoice = Invoice::with(['items.product', 'customer'])
            ->where('user_id', $cashierId)
            ->findOrFail($id);
        if ($invoice->status !== 'unpaid') {
            return redirect()->route('cashier.invoices.show', $invoice->id)
                ->with('error', 'Cannot edit a paid or partially paid invoice.');
        }
        $products = Product::all();
        $history = DB::table('invoice_histories')->where('invoice_id', $invoice->id)->orderByDesc('created_at')->get();
        return view('cashier.invoices.edit', compact('invoice', 'products', 'history'));
    }

    // SMART UPDATE invoice items (pos cart updating with history)
    public function updateCart(Request $request, $id)
    {
        $cashierId = Auth::id();
        $invoice = Invoice::with('items.product', 'customer')
            ->where('user_id', $cashierId)
            ->findOrFail($id);

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            DB::table('invoice_histories')->insert([
                'invoice_id' => $invoice->id,
                'snapshot' => json_encode([
                    'items' => $invoice->items->map(function($item){
                        return [
                            'product_id' => $item->product_id,
                            'product_name' => $item->description ?? optional($item->product)->name,
                            'quantity' => $item->quantity,
                            'price' => $item->unit_price,
                            'discount' => $item->discount,
                            'total' => $item->total,
                        ];
                    }),
                    'customer' => optional($invoice->customer)->name,
                    'notes' => $invoice->notes,
                    'subtotal' => $invoice->subtotal,
                    'total' => $invoice->total,
                    'status' => $invoice->status,
                ]),
                'edited_by' => Auth::user() ? Auth::user()->name : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $existingItems = $invoice->items->keyBy('product_id');
            $newProductIds = collect($validated['items'])->pluck('product_id')->map(fn($p) => (int) $p)->toArray();

            foreach ($validated['items'] as $item) {
                $lineTotal = ($item['price'] - ($item['discount'] ?? 0)) * $item['quantity'];
                $invoiceItem = $existingItems->get($item['product_id']);
                if ($invoiceItem) {
                    $invoiceItem->update([
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['price'],
                        'discount' => $item['discount'] ?? 0,
                        'total' => $lineTotal,
                    ]);
                } else {
                    $invoice->items()->create([
                        'product_id' => $item['product_id'],
                        'description' => Product::find($item['product_id'])->name,
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['price'],
                        'discount' => $item['discount'] ?? 0,
                        'total' => $lineTotal,
                    ]);
                }
            }

            $existingItems->each(function($oldItem, $productId) use ($newProductIds) {
                if (!in_array($productId, $newProductIds)) {
                    $oldItem->delete();
                }
            });

            $subtotal = collect($validated['items'])->sum(function($item) {
                return ($item['price'] - ($item['discount'] ?? 0)) * $item['quantity'];
            });
            $invoice->subtotal = $subtotal;
            $invoice->total = $subtotal;
            $invoice->notes = $validated['notes'] ?? null;
            $invoice->save();

            DB::commit();
            return redirect()->back()->with('success', 'Invoice updated successfully! History recorded.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Could not update invoice: '.$e->getMessage());
        }
    }

    public function addProduct(Request $request, $id)
    {
        $cashierId = Auth::id();
        $invoice = Invoice::where('user_id', $cashierId)->findOrFail($id);
        if ($invoice->status !== 'unpaid') {
            return back()->with('error', 'Cannot add products to a paid or partially paid invoice.');
        }
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|numeric|min:1',
        ]);
        $product = Product::findOrFail($request->product_id);
        $item = new InvoiceItem([
            'product_id'  => $product->id,
            'description' => $product->name,
            'quantity'    => $request->quantity,
            'unit_price'  => $product->selling_price,
            'total'       => $product->selling_price * $request->quantity,
            'added_by'    => Auth::id(),
        ]);
        $invoice->items()->save($item);

        // Recalculate totals
        $subtotal = $invoice->items()->sum(DB::raw('quantity * unit_price'));
        $discount = $invoice->discount_amount ?? 0;
        $tax     = $invoice->tax_amount    ?? 0;
        $total   = $subtotal - $discount + $tax;
        $invoice->update([
            'subtotal' => $subtotal,
            'total'    => $total,
        ]);
        return back()->with('success', 'Product added to invoice.');
    }

    public function posInvoice(Request $request)
    {
        $user = Auth::user();
        $businessId = $user->business_id;

        $validated = $request->validate([
            'customer_option'    => ['required', 'in:existing,new'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity'   => ['required', 'numeric', 'min:0.01'],
            'items.*.price'      => ['required', 'numeric', 'min:0'],
            'discount'           => ['nullable', 'numeric', 'min:0'],
            'add_tax'            => ['nullable', 'boolean'],
            'notes'              => ['nullable', 'string', 'max:500'],
        ]);

        DB::beginTransaction();
        try {
            if ($validated['customer_option'] === 'existing') {
                $customerId = $request->input('customer_id');
                if (!$customerId) throw new \Exception('Please select a customer for credit invoice.');
            } else {
                $customer = Customer::create([
                    'business_id' => $businessId,
                    'name'        => $request->input('new_customer_name'),
                    'phone'       => $request->input('new_customer_phone'),
                    'email'       => $request->input('new_customer_email'),
                    'address'     => $request->input('new_customer_address'),
                    'is_active'   => true,
                ]);
                $customerId = $customer->id;
            }

            $latestId = Invoice::where('business_id', $businessId)->max('id') ?? 1;
            $invoiceNum = 'INV-' . now()->format('Ymd') . '-' . str_pad($latestId+1, 4, '0', STR_PAD_LEFT);
            $invoice = Invoice::create([
                'business_id'    => $businessId,
                'user_id'        => $user->id,
                'customer_id'    => $customerId,
                'invoice_number' => $invoiceNum,
                'status'         => 'unpaid',
                'due_date'       => now()->addDays(14),
                'subtotal'       => 0,
                'discount_amount'=> 0,
                'tax_amount'     => 0,
                'total'          => 0,
                'paid'           => 0,
                'notes'          => $request->input('notes', null),
            ]);

            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);
                if ($product && $product->quantity < $item['quantity']) {
                    return back()->with('error', "Insufficient stock for {$product->name}. Only {$product->quantity} left in stock.");
                }
                $lineTotal = $item['quantity'] * $item['price'];
                InvoiceItem::create([
                    'invoice_id'  => $invoice->id,
                    'description' => $product->name,
                    'product_id'  => $product->id,
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['price'],
                    'total'       => $lineTotal,
                    'added_by'    => $user->id
                ]);
                $subtotal += $lineTotal;
                if ($product) {
                    $product->decrement('quantity', $item['quantity']);
                }
            }

            $discount = $request->input('discount', 0);
            $taxAmount = $request->input('add_tax') ? max(0, ($subtotal - $discount) * 0.18) : 0;
            $total = $subtotal - $discount + $taxAmount;

            $invoice->update([
                'subtotal'        => $subtotal,
                'discount_amount' => $discount,
                'tax_amount'      => $taxAmount,
                'total'           => $total,
            ]);

            DB::commit();

            try {
                if ($invoice->customer && $invoice->customer->email) {
                    $invoice->load(['business', 'customer', 'items.product', 'user']);
                    MailerService::sendInvoice($invoice);
                }
            } catch (\Exception $e) {}

            // DIRECT REDIRECT TO PRINT PAGE
            return redirect()->route('cashier.invoices.print', $invoice->id)
                ->with('success', 'Invoice created! Ready to print.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to create invoice: '.$e->getMessage());
        }
    }

    public function deleteItem($invoiceId, $itemId)
    {
        $cashierId = Auth::id();
        $item = InvoiceItem::where('invoice_id', $invoiceId)->where('id', $itemId)->firstOrFail();
        $invoice = Invoice::where('user_id', $cashierId)->find($invoiceId);
        if (!$invoice) {
            abort(403, 'Not allowed.');
        }
        $item->delete();
        $subtotal = $invoice->items()->sum(DB::raw('(unit_price - IFNULL(discount,0)) * quantity'));
        $invoice->subtotal = $subtotal;
        $invoice->total = $subtotal;
        $invoice->save();
        return back()->with('success', 'Invoice item deleted!');
    }

    // MARK as PAID for this cashier (with automatic sale + receipt)
    public function markPaid($id, Request $request)
    {
        $cashierId = Auth::id();
        $invoice = Invoice::with(['items', 'business', 'customer', 'user'])
            ->where('user_id', $cashierId)
            ->findOrFail($id);
        $paymentAmount = (float) $request->input('amount', $invoice->total);

        if ($invoice->status === 'paid') {
            return back()->with('info', 'Invoice already settled.');
        }

        $maxOutstanding = $invoice->total - $invoice->paid;
        $amountToPay = min($paymentAmount, $maxOutstanding);

        // 1. Record payment in payments table (same as InvoiceController::pay)
        DB::table('payments')->insert([
            'invoice_id'  => $invoice->id,
            'amount_paid' => $amountToPay,
            'paid_at'     => now(),
            'user_id'     => $cashierId,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // 2. Recalculate totals from payments table
        $newPaid   = DB::table('payments')->where('invoice_id', $invoice->id)->sum('amount_paid');
        $newStatus = ($newPaid >= $invoice->total) ? 'paid' : 'partial';

        $invoice->paid    = $newPaid;
        $invoice->balance = $invoice->total - $newPaid;
        $invoice->status  = $newStatus;
        $invoice->save();


        // 3. Fetch the payment record just inserted (for the email)
        $latestPayment = DB::table('payments')
            ->where('invoice_id', $invoice->id)
            ->orderByDesc('paid_at')
            ->first();

        // 4. Create Sale record only when fully paid
        if ($newStatus === 'paid') {
            $sale = Sale::create([
                'business_id'     => $invoice->business_id,
                'user_id'         => $invoice->user_id,
                'customer_id'     => $invoice->customer_id,
                'sale_number'     => 'SALE-' . now()->format('Ymd') . '-' . rand(1000, 9999),
                'sale_date'       => now(),
                'subtotal'        => $invoice->subtotal,
                'tax_amount'      => $invoice->tax_amount,
                'discount_amount' => $invoice->discount_amount,
                'total'           => $invoice->total,
                'payment_status'  => 'paid',
                'payment_method'  => 'credit',
                'notes'           => 'From invoice ' . $invoice->invoice_number,
            ]);
            foreach ($invoice->items as $item) {
                SaleItem::create([
                    'sale_id'    => $sale->id,
                    'product_id' => $item->product_id,
                    'quantity'   => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total'      => $item->total,
                ]);
            }
        }

        // 5. Send receipt email for BOTH partial and paid (moved outside the 'paid'-only block)
        try {
            if ($invoice->customer && $invoice->customer->email) {
                MailerService::sendInvoiceReceipt($invoice, $latestPayment);
            }
        } catch (\Exception $e) {
            // Log but don't break the flow
            \Illuminate\Support\Facades\Log::error('Cashier invoice receipt email failed', [
                'invoice_id' => $invoice->id,
                'error'      => $e->getMessage(),
            ]);
        }

        $msg = $newStatus === 'paid'
            ? 'Invoice fully paid. Receipt emailed!'
            : 'Partial payment recorded. Receipt emailed!';

        return redirect()->route('cashier.invoices.index')->with('success', $msg);
    }
}