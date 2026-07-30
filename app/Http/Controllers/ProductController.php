<?php

namespace App\Http\Controllers;

use App\Models\{Product, Category};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Storage, DB};
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Display products list
     */
   public function index(Request $request)
{
    $user = Auth::user();
    $userRole = $user->role->name;

    // ✅ Start query WITHOUT forcing is_active = true
    $query = Product::where('business_id', $user->business_id)
        ->with('category');

    // ✅ Search functionality
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('sku', 'like', "%{$search}%")
              ->orWhere('barcode', 'like', "%{$search}%");
        });
    }

    // ✅ Category filter (use category_id, not category)
    if ($request->filled('category_id')) {
        $query->where('category_id', $request->category_id);
    }

    // ✅ Status filter
    if ($request->filled('status')) {
        switch ($request->status) {
            case 'active':
                $query->where('is_active', true);
                break;
            case 'inactive':
                $query->where('is_active', false);
                break;
            case 'low_stock':
                $query->where('is_active', true)
                      ->whereColumn('quantity', '<=', 'reorder_level')
                      ->where('quantity', '>', 0);
                break;
            case 'out_of_stock':
                $query->where('is_active', true)
                      ->where('quantity', '<=', 0);
                break;
        }
    } else {
        // ✅ Default: show only active products if no status filter
        $query->where('is_active', true);
    }

    $products = $query->orderBy('name')->paginate(20);

    // Get categories for filter
    $categories = Category::where('business_id', $user->business_id)
        ->orderBy('name')
        ->get();

    // ✅ AJAX REQUEST - Return JSON with HTML (for cashier tabular view)
    if ($request->ajax() || $request->has('ajax')) {
        $html = '';
        
        if ($products->count() > 0) {
            foreach ($products as $product) {
                $stockClass = $product->quantity < 10 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800';
                $categoryName = $product->category ? $product->category->name : 'Uncategorized';
                
                $html .= '
                <tr class="hover:bg-gray-50 transition cursor-pointer" onclick="window.location=\'' . route('products.show', $product->id) . '\'">
                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                        ' . e($product->name) . '
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-gray-500 font-mono">
                        ' . e($product->sku ?? 'N/A') . '
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-gray-500">
                        ' . e($categoryName) . '
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right font-bold text-gray-900">
                        UGX ' . number_format($product->selling_price, 0) . '
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right">
                        <span class="px-2.5 py-1 text-xs font-bold rounded-full ' . $stockClass . '">
                            ' . number_format($product->quantity, 0) . ' ' . e($product->unit ?? 'pcs') . '
                        </span>
                    </td>
                </tr>';
            }
        } else {
            $html = '
            <tr>
                <td colspan="5" class="px-6 py-10 text-center text-gray-400">
                    <div class="flex flex-col items-center">
                        <i class="fas fa-search-minus text-4xl text-gray-300 mb-2"></i>
                        <p class="text-sm font-medium">No products found</p>
                    </div>
                </td>
            </tr>';
        }
        
        return response()->json([
            'success' => true,
            'html' => $html,
            'count' => $products->total()
        ]);
    }

    // ✅ LOAD DIFFERENT VIEW BASED ON ROLE
    if ($userRole === 'cashier') {
        return view('cashier.products-index', compact('products', 'categories'));
    }

    return view('products.index', compact('products', 'categories'));
}

    /**
     * Show product details
     */
    public function show(Product $product)
    {
        $user = Auth::user();
        $userRole = $user->role->name;

        if ($product->business_id !== $user->business_id) {
            abort(403);
        }

        $product->load('category');

        // Get recent sales of this product
        $recentSales = $product->saleItems()
            ->with('sale.customer')
            ->latest()
            ->limit(10)
            ->get();

        // ✅ LOAD DIFFERENT VIEW BASED ON ROLE
        if ($userRole === 'cashier') {
            return view('cashier.products-show', compact('product', 'recentSales'));
        }

        return view('products.show', compact('product', 'recentSales'));
    }

    /**
     * Show create form (Admin/Owner/Manager only)
     */
    public function create()
    {
        $user = Auth::user();
        $userRole = $user->role->name;

        // Cashiers cannot create products
        if ($userRole === 'cashier') {
            abort(403, 'Cashiers cannot add products.');
        }

        $categories = Category::where('business_id', $user->business_id)
            ->orderBy('name')
            ->get();

        return view('products.create', compact('categories'));
    }

    /**
     * Show tabulated form for adding multiple products at once (Admin/Owner/Manager only)
     */
    public function bulkCreate()
    {
        $user = Auth::user();
        if ($user->role->name === 'cashier') {
            abort(403, 'Cashiers cannot add products.');
        }

        $categories = Category::where('business_id', $user->business_id)
            ->orderBy('name')
            ->get();

        return view('products.bulk-create', compact('categories'));
    }

    /**
     * Store multiple products at once
     */
    public function bulkStore(Request $request)
    {
        $user = Auth::user();
        if ($user->role->name === 'cashier') {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Permission denied.'], 403);
            }
            abort(403);
        }

        $request->validate([
            'products'                      => 'required|array|min:1',
            'products.*.name'               => 'required|string|max:255',
            'products.*.sku'                => 'nullable|string|max:100',
            'products.*.barcode'            => 'nullable|string|max:100',
            'products.*.category_id'        => 'nullable',
            'products.*.new_category_name'  => 'nullable|string|max:255',
            'products.*.cost_price'         => 'required|numeric|min:0',
            'products.*.selling_price'      => 'required|numeric|min:0',
            'products.*.quantity'           => 'nullable|numeric|min:0',
            'products.*.unit'               => 'required|string',
            'products.*.requires_vat'       => 'nullable',
        ]);

        // ── Pre-flight validation: catch duplicates BEFORE writing anything ──
        $rowErrors   = [];   // ['row' => N, 'name' => '...', 'errors' => [...]]
        $batchSkus   = [];   // track SKUs seen within this batch
        $batchNames  = [];   // track names seen within this batch

        foreach ($request->products as $index => $row) {
            if (empty($row['name'])) continue;

            $rowNum = $index + 1;
            $errors = [];
            $name   = trim($row['name']);
            $sku    = !empty($row['sku']) ? trim($row['sku']) : null;

            // Duplicate name within this batch
            $nameLower = strtolower($name);
            if (in_array($nameLower, $batchNames)) {
                $errors[] = "Duplicate product name \"$name\" in this batch.";
            } else {
                $batchNames[] = $nameLower;
            }

            // Duplicate name in DB (active products)
            if (Product::where('business_id', $user->business_id)
                        ->where('is_active', true)
                        ->whereRaw('LOWER(name) = ?', [$nameLower])
                        ->exists()) {
                $errors[] = "A product named \"$name\" already exists in your inventory.";
            }

            // SKU checks (only if a SKU was explicitly entered)
            if ($sku) {
                // Duplicate SKU within this batch
                $skuLower = strtolower($sku);
                if (in_array($skuLower, $batchSkus)) {
                    $errors[] = "Duplicate SKU \"$sku\" in this batch.";
                } else {
                    $batchSkus[] = $skuLower;
                }

                // Duplicate SKU in DB
                if (Product::where('business_id', $user->business_id)
                            ->where('sku', $sku)
                            ->exists()) {
                    $errors[] = "SKU \"$sku\" is already used by another product in your inventory.";
                }
            }

            // Selling price lower than cost price warning (not blocking, but inform)
            $cost    = (float)($row['cost_price']   ?? 0);
            $selling = (float)($row['selling_price'] ?? 0);
            if ($selling > 0 && $selling < $cost) {
                $errors[] = "Selling price (UGX " . number_format($selling) . ") is lower than cost price (UGX " . number_format($cost) . "). Please check.";
            }

            if (!empty($errors)) {
                $rowErrors[] = ['row' => $rowNum, 'name' => $name, 'errors' => $errors];
            }
        }

        // If any rows have errors, return them ALL — do not save anything
        if (!empty($rowErrors)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success'    => false,
                    'row_errors' => $rowErrors,
                    'message'    => count($rowErrors) . ' row(s) have errors. Nothing was saved. Please fix and try again.',
                ], 422);
            }
            return redirect()->back()->withInput()
                ->with('error', 'Some rows have errors. Please review and try again.');
        }

        // ── All rows clean — save in a transaction ───────────────────────────
        $createdCount = 0;
        $usedSkus     = [];

        try {
            DB::transaction(function () use ($request, $user, &$createdCount, &$usedSkus) {
                foreach ($request->products as $row) {
                    if (empty($row['name'])) continue;

                    // Category Resolution
                    $categoryId = null;
                    if (!empty($row['category_id']) && is_numeric($row['category_id'])) {
                        $categoryId = (int) $row['category_id'];
                    } elseif (!empty($row['new_category_name'])) {
                        $catName  = trim($row['new_category_name']);
                        $category = Category::firstOrCreate(
                            ['business_id' => $user->business_id, 'name' => $catName],
                            ['is_active'   => true]
                        );
                        $categoryId = $category->id;
                    }

                    // SKU — auto-generate unique one if blank
                    $sku = !empty($row['sku']) ? trim($row['sku']) : null;
                    if (!$sku) {
                        do { $sku = 'PROD-' . rand(100000, 999999); }
                        while (in_array($sku, $usedSkus) ||
                               Product::where('business_id', $user->business_id)->where('sku', $sku)->exists());
                    }
                    $usedSkus[] = $sku;

                    $requiresVat = isset($row['requires_vat']) &&
                                   ($row['requires_vat'] == '1' || $row['requires_vat'] === 'on');

                    $product = Product::create([
                        'business_id'  => $user->business_id,
                        'category_id'  => $categoryId,
                        'name'         => trim($row['name']),
                        'sku'          => $sku,
                        'barcode'      => !empty($row['barcode']) ? trim($row['barcode']) : null,
                        'cost_price'   => $row['cost_price']   ?? 0,
                        'selling_price'=> $row['selling_price'] ?? 0,
                        'quantity'     => $row['quantity']     ?? 0,
                        'opening_stock'=> $row['quantity']     ?? 0,
                        'unit'         => $row['unit']         ?? 'pcs',
                        'requires_vat' => $requiresVat,
                        'reorder_level'=> 10,
                        'is_active'    => true,
                    ]);

                    if ($product->quantity > 0) {
                        \App\Models\InventoryTransaction::create([
                            'business_id' => $user->business_id,
                            'product_id' => $product->id,
                            'transaction_type' => 'ADJUSTMENT',
                            'quantity_in' => $product->quantity,
                            'quantity_out' => 0,
                            'reference_type' => Product::class,
                            'reference_id' => $product->id,
                            'description' => "Initial/opening stock on bulk import",
                            'created_by' => $user->id,
                        ]);
                    }

                    // Audit Log
                    \App\Models\AuditLog::log('create_product', Product::class, $product->id, null, $product->toArray());

                    $createdCount++;
                }
            });

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'count'   => $createdCount,
                    'message' => "Successfully added {$createdCount} product" . ($createdCount !== 1 ? 's' : '') . " in bulk!",
                ]);
            }

            return redirect()->route('products.index')
                ->with('success', "🎉 Successfully added {$createdCount} products in bulk!");
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "Server error while saving: " . $e->getMessage(),
                ], 500);
            }
            return redirect()->back()->withInput()
                ->with('error', "Failed to add bulk products: " . $e->getMessage());
        }
    }

    /**
     * Store new product
     */
    /**
 * Store new product
 */
public function store(Request $request)
{
    $user = Auth::user();
    $userRole = $user->role->name;

    // Cashiers cannot create products
    if ($userRole === 'cashier') {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to add products.'], 403);
        }
        abort(403);
    }

    // ✅ UPDATED VALIDATION RULES WITH CONDITIONAL CATEGORY VALIDATION
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'sku' => 'nullable|string|max:100|unique:products,sku,NULL,id,business_id,' . $user->business_id,
        'barcode' => 'nullable|string|max:100',
        'unit' => 'required|string',
        
        // ✅ Category handling - conditional validation
        'category_option' => 'required|in:existing,new',
        'category_id' => 'nullable|exists:categories,id',
        'new_category_name' => 'nullable|string|max:255',
        'new_category_description' => 'nullable|string',
        
        'cost_price' => 'required|numeric|min:0',
        'selling_price' => 'required|numeric|min:0',
        'requires_vat' => 'nullable|boolean',
        'quantity' => 'nullable|numeric|min:0',
        'reorder_level' => 'nullable|integer|min:0',
        
        // ✅ Expiry tracking
        'track_expiry' => 'nullable|boolean',
        'manufacture_date' => 'nullable|date',
        'expiry_date' => 'nullable|date|after:manufacture_date',
        'expiry_alert_days' => 'nullable|integer|min:1|max:365',
        
        'description' => 'nullable|string',
        'image' => 'nullable|image|max:2048',
    ]);

    try {
        DB::beginTransaction();

        // ✅ VALIDATE CONDITIONAL CATEGORY REQUIREMENTS
        if ($request->category_option === 'existing') {
            if (empty($validated['category_id'])) {
                return redirect()->back()->withErrors([
                    'category_id' => 'Please select an existing category.'
                ])->withInput();
            }
        } elseif ($request->category_option === 'new') {
            if (empty($validated['new_category_name'])) {
                return redirect()->back()->withErrors([
                    'new_category_name' => 'Please enter a new category name.'
                ])->withInput();
            }
            
            // ✅ CREATE NEW CATEGORY
            $category = Category::create([
                'name' => $validated['new_category_name'],
                'description' => $validated['new_category_description'] ?? null,
                'business_id' => $user->business_id,
            ]);
            
            $validated['category_id'] = $category->id;
        }

        // ✅ SET BUSINESS ID & VAT FLAG
        $validated['business_id']  = $user->business_id;
        $validated['is_active']    = true;
        $validated['requires_vat'] = $request->has('requires_vat') ? $request->boolean('requires_vat') : true;

        // ✅ Handle image upload
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        // ✅ MAP QUANTITY TO OPENING_STOCK (for initial stock entry)
        if (isset($validated['quantity']) && $validated['quantity']) {
            $validated['opening_stock'] = $validated['quantity'];
        } else {
            $validated['opening_stock'] = 0;
        }
        
        // ✅ SET QUANTITY TO OPENING_STOCK VALUE (they should be the same initially)
        $validated['quantity'] = $validated['opening_stock'];

        // ✅ REMOVE FIELDS NOT IN PRODUCT TABLE
        unset($validated['new_category_name']);
        unset($validated['new_category_description']);
        unset($validated['category_option']);
        unset($validated['track_expiry']);

        // ✅ CREATE PRODUCT
        $product = Product::create($validated);

        if ($product->quantity > 0) {
            \App\Models\InventoryTransaction::create([
                'business_id' => $user->business_id,
                'product_id' => $product->id,
                'transaction_type' => 'ADJUSTMENT',
                'quantity_in' => $product->quantity,
                'quantity_out' => 0,
                'reference_type' => Product::class,
                'reference_id' => $product->id,
                'description' => "Initial/opening stock on creation",
                'created_by' => $user->id,
            ]);
        }

        // Audit Log
        \App\Models\AuditLog::log('create_product', Product::class, $product->id, null, $product->toArray());

        DB::commit();

        if ($request->expectsJson()) {
            return response()->json([
                'success'  => true,
                'name'     => $product->name,
                'redirect' => route('products.index'),
                'message'  => "Product '{$product->name}' added successfully!",
            ]);
        }

        return redirect()->route('products.index')
            ->with('success', "Product '{$product->name}' added successfully! ✅");
    } catch (\Exception $e) {
        DB::rollBack();
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => 'Failed to save product: ' . $e->getMessage()], 500);
        }
        return redirect()->back()
            ->withInput()
            ->with('error', "Failed to add product: " . $e->getMessage());
    }
}

    /**
     * Show edit form
     */
    public function edit(Product $product)
    {
        $user = Auth::user();
        $userRole = $user->role->name;

        if ($product->business_id !== $user->business_id) {
            abort(403);
        }

        // Cashiers cannot edit products
        if ($userRole === 'cashier') {
            abort(403, 'Cashiers cannot edit products.');
        }

        $categories = Category::where('business_id', $user->business_id)
            ->orderBy('name')
            ->get();

        return view('products.edit', compact('product', 'categories'));
    }

    /**
     * Update product
     */
    public function update(Request $request, Product $product)
    {
        $user = Auth::user();
        $userRole = $user->role->name;

        if ($product->business_id !== $user->business_id) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
            }
            abort(403);
        }

        // Cashiers cannot edit products
        if ($userRole === 'cashier') {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'You do not have permission to edit products.'], 403);
            }
            abort(403);
        }

        $validated = $request->validate([
            'name'                       => 'required|string|max:255',
            'sku'                        => 'nullable|string|max:100|unique:products,sku,' . $product->id . ',id,business_id,' . $user->business_id,
            'barcode'                    => 'nullable|string|max:100',
            'unit'                       => 'required|string',
            'category_option'            => 'required|in:existing,new',
            'category_id'               => 'nullable|exists:categories,id',
            'new_category_name'          => 'nullable|string|max:255',
            'new_category_description'   => 'nullable|string',
            'cost_price'                 => 'required|numeric|min:0',
            'selling_price'              => 'required|numeric|min:0',
            'requires_vat'               => 'nullable|boolean',
            'quantity'                   => 'nullable|numeric|min:0',
            'reorder_level'              => 'nullable|integer|min:0',
            'track_expiry'               => 'nullable|boolean',
            'manufacture_date'           => 'nullable|date',
            'expiry_date'                => 'nullable|date|after:manufacture_date',
            'expiry_alert_days'          => 'nullable|integer|min:1|max:365',
            'description'                => 'nullable|string',
            'image'                      => 'nullable|image|max:2048',
            'is_active'                  => 'nullable|boolean',
        ]);

        $validated['requires_vat'] = $request->boolean('requires_vat');

        // Category conditional validation
        if ($request->category_option === 'existing') {
            if (empty($validated['category_id'])) {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'errors' => ['category_id' => ['Please select an existing category.']]], 422);
                }
                return redirect()->back()->withErrors(['category_id' => 'Please select an existing category.'])->withInput();
            }
        } elseif ($request->category_option === 'new') {
            if (empty($validated['new_category_name'])) {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'errors' => ['new_category_name' => ['Please enter a new category name.']]], 422);
                }
                return redirect()->back()->withErrors(['new_category_name' => 'Please enter a new category name.'])->withInput();
            }
            $category = Category::create([
                'name'        => $validated['new_category_name'],
                'description' => $validated['new_category_description'] ?? null,
                'business_id' => $user->business_id,
            ]);
            $validated['category_id'] = $category->id;
        }

        try {
            // Handle image upload
            if ($request->hasFile('image')) {
                if ($product->image) {
                    Storage::disk('public')->delete($product->image);
                }
                $validated['image'] = $request->file('image')->store('products', 'public');
            }

            // Remove non-product fields
            unset($validated['new_category_name'], $validated['new_category_description'],
                  $validated['category_option'], $validated['track_expiry']);

            // Track changes for auditing
            $oldValues = [];
            $newValues = [];
            $priceChanged = false;

            if ($product->cost_price != $validated['cost_price']) {
                $oldValues['cost_price'] = $product->cost_price;
                $newValues['cost_price'] = $validated['cost_price'];
                $priceChanged = true;
            }
            if ($product->selling_price != $validated['selling_price']) {
                $oldValues['selling_price'] = $product->selling_price;
                $newValues['selling_price'] = $validated['selling_price'];
                $priceChanged = true;
            }

            // Sync opening_stock when quantity is manually edited
            if (isset($validated['quantity'])) {
                $totalSales     = \App\Models\SaleItem::where('product_id', $product->id)->sum('quantity');
                $totalPurchases = \App\Models\PurchaseItem::where('product_id', $product->id)->sum('quantity');
                $validated['opening_stock'] = max(0, $validated['quantity'] + $totalSales - $totalPurchases);

                if ($product->quantity != $validated['quantity']) {
                    $oldValues['quantity'] = $product->quantity;
                    $newValues['quantity'] = $validated['quantity'];
                    
                    $qtyDiff = $validated['quantity'] - $product->quantity;
                    \App\Models\InventoryTransaction::create([
                        'business_id' => $user->business_id,
                        'product_id' => $product->id,
                        'transaction_type' => 'ADJUSTMENT',
                        'quantity_in' => $qtyDiff > 0 ? $qtyDiff : 0,
                        'quantity_out' => $qtyDiff < 0 ? abs($qtyDiff) : 0,
                        'reference_type' => Product::class,
                        'reference_id' => $product->id,
                        'description' => "Manual stock quantity adjustment from {$product->quantity} to {$validated['quantity']}",
                        'created_by' => $user->id,
                    ]);
                }
            }

            $product->update($validated);

            if ($priceChanged || !empty($oldValues)) {
                \App\Models\AuditLog::log('price_change_or_product_edit', Product::class, $product->id, $oldValues, $newValues);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success'  => true,
                    'name'     => $product->name,
                    'redirect' => route('products.index'),
                    'message'  => "Product '{$product->name}' updated successfully!",
                ]);
            }

            return redirect()->route('products.index')
                ->with('success', "Product '{$product->name}' updated successfully! ✅");

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to update product: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->withInput()
                ->with('error', 'Failed to update product: ' . $e->getMessage());
        }
    }


    /**
     * Delete product.
     *
     * Rule: if the product has ANY transaction history (sales, purchases,
     * invoice lines, stock adjustments, stock transfers), we do not delete it.
     * Instead, we deactivate it ($product->update(['is_active' => false])), 
     * which moves it to the Inactive list where it can be reactivated later.
     *
     * If the product has zero history, it is safe to permanently remove it ($product->forceDelete()).
     */
    public function destroy(Product $product, Request $request)
    {
        $user     = Auth::user();
        $userRole = $user->role->name;

        if ($product->business_id !== $user->business_id) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
            }
            abort(403);
        }

        // Only owner/admin can delete
        if (!in_array($userRole, ['owner', 'admin'])) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'You do not have permission to delete products.'], 403);
            }
            abort(403, 'You do not have permission to delete products.');
        }

        $productName = $product->name;

        // ── Count all transaction history linked to this product ─────────────
        $salesCount     = \App\Models\SaleItem::where('product_id', $product->id)->count();
        $purchasesCount = \App\Models\PurchaseItem::where('product_id', $product->id)->count();
        $invoicesCount  = \App\Models\InvoiceItem::where('product_id', $product->id)->count();
        $adjustCount    = \App\Models\StockAdjustment::where('product_id', $product->id)->count();
        $transferCount  = \App\Models\StockTransferItem::where('product_id', $product->id)->count();

        $totalTransactions = $salesCount + $purchasesCount + $invoicesCount + $adjustCount + $transferCount;
        $hasTransactions   = $totalTransactions > 0;

        if ($hasTransactions) {
            // ── DEACTIVATE ONLY — move to Inactive list to preserve history ──
            $product->update(['is_active' => false]);

            $message = "\"$productName\" has been deactivated because it has transaction history. It can be found and reactivated from the Inactive Products tab.";

            if ($request->expectsJson()) {
                return response()->json([
                    'success'          => true,
                    'action'           => 'deactivated',
                    'has_transactions' => true,
                    'message'          => $message,
                ]);
            }

            return redirect()->route('products.index')
                ->with('success', $message);
        }

        // ── FORCE DELETE — no history, safe to remove permanently ────────────
        $product->forceDelete();

        $message = "\"$productName\" has been permanently deleted.";

        if ($request->expectsJson()) {
            return response()->json([
                'success'          => true,
                'action'           => 'deleted',
                'has_transactions' => false,
                'message'          => $message,
            ]);
        }

        return redirect()->route('products.index')
            ->with('success', $message);
    }

    /**
     * Activate product
     */
    public function activate(Product $product, Request $request)
    {
        $user     = Auth::user();
        $userRole = $user->role->name;

        if ($product->business_id !== $user->business_id) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
            }
            abort(403);
        }

        // Only owner/admin can activate
        if (!in_array($userRole, ['owner', 'admin'])) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'You do not have permission to activate products.'], 403);
            }
            abort(403, 'You do not have permission to activate products.');
        }

        $product->update(['is_active' => true]);

        $message = "\"{$product->name}\" has been activated successfully! ✅";

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        return redirect()->route('products.index')
            ->with('success', $message);
    }



    /**
     * Show expired products
     */
    /**
 * Show expired products
 */
public function expired()
{
    $user = Auth::user();
    $userRole = $user->role->name;

    // Cashiers cannot access this
    if ($userRole === 'cashier') {
        abort(403, 'Cashiers cannot access expired products list.');
    }

    // ✅ USE paginate() INSTEAD OF get()
    $products = Product::where('business_id', $user->business_id)
        ->where('is_active', true)
        ->whereNotNull('expiry_date')
        ->where('expiry_date', '<', now())
        ->with('category')
        ->orderBy('expiry_date')
        ->paginate(20); // ✅ Changed from get() to paginate()

    return view('products.expired', compact('products'));
}

    /**
     * Show expiring soon products
     */
  /**
 * Show expiring soon products
 */
public function expiringSoon()
{
    $user = Auth::user();
    $userRole = $user->role->name;

    // Cashiers cannot access this
    if ($userRole === 'cashier') {
        abort(403, 'Cashiers cannot access expiring products list.');
    }

    // ✅ USE paginate() INSTEAD OF get()
    $products = Product::where('business_id', $user->business_id)
        ->where('is_active', true)
        ->whereNotNull('expiry_date')
        ->whereBetween('expiry_date', [now(), now()->addDays(30)])
        ->with('category')
        ->orderBy('expiry_date')
        ->paginate(20); // ✅ Changed from get() to paginate()

    return view('products.expiring-soon', compact('products'));
}

}