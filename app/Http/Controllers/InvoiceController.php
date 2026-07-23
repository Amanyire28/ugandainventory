<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{
    Invoice, InvoiceItem, Customer, Product, User, Business, Sale, SaleItem
};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\MailerService;

class InvoiceController extends Controller
{
    // INDEX: List all invoices, with search and status filterstroy
    public function index(Request $request)
    {
        $status = $request->query('status');
        $search = $request->query('search');
        $customerId = $request->query('customer_id');
        $query = Invoice::with('customer')->orderByDesc('created_at');
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

        return view('invoices.index', compact('invoices', 'status', 'search', 'customerId', 'customers'));
    }

    // PAID AJAX FILTER (for partial table reloads)
    public function paid(Request $request)
    {
        $search = $request->query('search');
        $query = Invoice::with('customer')->where('status', 'paid')->orderByDesc('created_at');
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
                'html' => view('invoices.partials.table', compact('invoices'))->render()
            ]);
        }

        return view('invoices.index', [
            'status' => 'paid',
            'invoices' => $invoices
        ]);
    }

    // UNPAID AJAX FILTER
    public function unpaid(Request $request)
    {
        $search = $request->query('search');
        $query = Invoice::with('customer')->where('status', 'unpaid')->orderByDesc('created_at');
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
                'html' => view('invoices.partials.table', compact('invoices'))->render()
            ]);
        }

        return view('invoices.index', [
            'status' => 'unpaid',
            'invoices' => $invoices
        ]);
    }

    // SHOW a single invoice, with details/print view
    public function show($id)
    {
        $invoice = Invoice::with(['customer','user','items.product'])->findOrFail($id);
        $business = Business::findOrFail($invoice->business_id);
        return view('invoices.show', compact('invoice', 'business'));
    }

    // PRINTABLE invoice view
    public function print($id)
    {
        $invoice = Invoice::with(['customer','user','items.product'])->findOrFail($id);
        $business = Business::findOrFail($invoice->business_id);
        return view('invoices.print', compact('invoice', 'business'));
    }

    public function destroy($id)
    {
        $invoice = Invoice::findOrFail($id);
        if ($invoice->status === 'unpaid') {
            return back()->with('error', 'Cannot delete a unpaid invoice.');
        }
        $invoice->delete();
        return back()->with('success', 'Invoice deleted!');
    }

    // EDIT invoice UI, with history
    public function edit($id)
    {
        $invoice = Invoice::with(['items.product', 'customer'])->findOrFail($id);
        if ($invoice->status !== 'unpaid') {
            return redirect()->route('invoices.show', $invoice->id)
                ->with('error', 'Cannot edit a paid or partially paid invoice.');
        }
        $products = Product::all();
        $history = DB::table('invoice_histories')->where('invoice_id', $invoice->id)->orderByDesc('created_at')->get();
        return view('invoices.edit', compact('invoice', 'products', 'history'));
    }

    // SMART UPDATE invoice items (pos cart updating with history)
    public function updateCart(Request $request, $id)
    {
        $invoice = Invoice::with('items.product', 'customer')->findOrFail($id);
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
            // Save history before update
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

            // Update or create items
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

            // Remove items not present in the request
            $existingItems->each(function($oldItem, $productId) use ($newProductIds) {
                if (!in_array($productId, $newProductIds)) {
                    $oldItem->delete();
                }
            });

            // Update totals and notes
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

    // ADD PRODUCT (via separate form)
    public function addProduct(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);
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

    /**
     * CREATE POS INVOICE (AJAX version)
     * Automatically sends the invoice as PDF and HTML via email after commit.
     */
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
                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient stock for {$product->name}. Only {$product->quantity} left in stock.",
                    ], 400);
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

            // --- Automatically Send Invoice Email with PDF ---
            try {
                if ($invoice->customer && $invoice->customer->email) {
                    $invoice->load(['business', 'customer', 'items.product', 'user']);
                    MailerService::sendInvoice($invoice);
                }
            } catch (\Exception $e) {
                // You may log this, do not break process
            }

            return response()->json([
                'success'        => true,
                'message'        => 'Invoice created and sent!',
                'invoice_id'     => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'total'          => $invoice->total,
                'customer'       => $invoice->customer->name ?? 'Unknown',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteItem($invoiceId, $itemId)
    {
        $item = InvoiceItem::where('invoice_id', $invoiceId)->where('id', $itemId)->firstOrFail();
        $item->delete();
        $invoice = Invoice::find($invoiceId);
        if ($invoice) {
            $subtotal = $invoice->items()->sum(DB::raw('(unit_price - IFNULL(discount,0)) * quantity'));
            $invoice->subtotal = $subtotal;
            $invoice->total = $subtotal;
            $invoice->save();
        }
        return back()->with('success', 'Invoice item deleted!');
    }

  public function payForm($id)
{
    $invoice = \App\Models\Invoice::with(['customer', 'business', 'items'])->findOrFail($id);
    return view('invoices.pay', compact('invoice'));
}



public function pay(Request $request, $id)
{
    $invoice = Invoice::findOrFail($id);
    $maxOutstanding = $invoice->total - $invoice->paid;

    $input = $request->validate([
        'payment_type'  => 'required|in:full,partial',
        'amount'        => 'required|numeric|min:1|max:' . $maxOutstanding,
    ]);

    $amountToPay = $input['payment_type'] === 'full' ? $maxOutstanding : $input['amount'];

    // 1. Record the payment
    DB::table('payments')->insert([
        'invoice_id'  => $invoice->id,
        'amount_paid' => $amountToPay,
        'paid_at'     => now(),
        'user_id'     => auth::id(),
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);

    // 2. Recalculate summary fields
    $newPaid = DB::table('payments')
        ->where('invoice_id', $invoice->id)
        ->sum('amount_paid');

    $newBalance = $invoice->total - $newPaid;
    $newStatus = ($newPaid >= $invoice->total) ? 'paid' : 'partial';

    // 3. Update invoice
    $invoice->paid = $newPaid;
    $invoice->balance = $newBalance;
    $invoice->status = $newStatus;
    $invoice->save();

    // ========== ADDED CODE: fetch the latest payment for this invoice ==========
    $latestPayment = DB::table('payments')
        ->where('invoice_id', $invoice->id)
        ->orderByDesc('paid_at')
        ->first(); // <<< IMPORTANT: returns stdClass with amount_paid!
    // ===========================================================================

    // ========== CHANGED CODE: pass latestPayment to MailerService ==============
    try {
        if ($invoice->customer && $invoice->customer->email) {
            \App\Services\MailerService::sendInvoiceReceipt($invoice, $latestPayment); // <<<
        }
    } catch (\Exception $e) {
        // Log or handle error if you want
    }
    // ===========================================================================

    return redirect()->route('invoices.show', $invoice->id)
        ->with('success', 'Payment of ' . number_format($amountToPay) . ' UGX recorded and receipt sent!');
}


public function customerFinancialSummary($customerId)
     {
    $customer = \App\Models\Customer::with(['invoices.payments'])->findOrFail($customerId);

    // Outstanding invoices (not paid)
    $outstandingInvoices = $customer->invoices()->where('status', '!=', 'paid')->get();
    // Cleared invoices (paid)
    $paidInvoices = $customer->invoices()->where('status', 'paid')->get();
    // All payments by this customer
    $payments = \App\Models\Payment::whereIn('invoice_id', $customer->invoices->pluck('id'))->orderBy('paid_at', 'desc')->get();

    return view('invoices.customer', compact('customer', 'outstandingInvoices', 'paidInvoices', 'payments'));
}

    public function customersWithInvoices()
    {
        // Get only customers who have invoices
        $customers = Customer::whereHas('invoices')->with('invoices')->get();

        return view('invoices.customers', compact('customers'));
    }

    public function creditors()
    {
        // Get customers who have unpaid or partially paid invoices
        $customers = Customer::whereHas('invoices', function ($q) {
            $q->where('status', '!=', 'paid');
        })->with(['invoices' => function ($q) {
            $q->where('status', '!=', 'paid');
        }])->get();

        return view('invoices.creditors', compact('customers'));
    }
}

