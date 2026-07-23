<?php

namespace App\Http\Controllers;

use App\Models\{Product, Category};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Storage};
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

    // ✅ SET BUSINESS ID
    $validated['business_id'] = $user->business_id;
    $validated['is_active'] = true;

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

    return redirect()->route('products.index')
        ->with('success', "Product '{$product->name}' added successfully! ✅");
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
            abort(403);
        }

        // Cashiers cannot edit products
        if ($userRole === 'cashier') {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'barcode' => 'nullable|string|max:100',
            'unit' => 'required|string',
            
            // ✅ Category handling - conditional validation
            'category_option' => 'required|in:existing,new',
            'category_id' => 'nullable|exists:categories,id',
            'new_category_name' => 'nullable|string|max:255',
            'new_category_description' => 'nullable|string',
            
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
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

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        // ✅ REMOVE FIELDS NOT IN PRODUCT TABLE
        unset($validated['new_category_name']);
        unset($validated['new_category_description']);
        unset($validated['category_option']);
        unset($validated['track_expiry']);

        // ✅ SYNC opening_stock when quantity is manually edited
        // Formula: opening_stock = new_quantity + all_sales - all_purchases
        // This keeps the activities formula consistent: opening + purchases - sales = current
        if (isset($validated['quantity'])) {
            $totalSales = \App\Models\SaleItem::where('product_id', $product->id)->sum('quantity');
            $totalPurchases = \App\Models\PurchaseItem::where('product_id', $product->id)->sum('quantity');
            $validated['opening_stock'] = max(0, $validated['quantity'] + $totalSales - $totalPurchases);
        }

        $product->update($validated);

        return redirect()->route('products.index')
            ->with('success', "Product '{$product->name}' updated successfully! ✅");
    }


    /**
     * Delete product
     */
    public function destroy(Product $product)
    {
        $user = Auth::user();
        $userRole = $user->role->name;

        if ($product->business_id !== $user->business_id) {
            abort(403);
        }

        // Only owner/admin can delete
        if (!in_array($userRole, ['owner', 'admin'])) {
            abort(403, 'You do not have permission to delete products.');
        }

        $product->update(['is_active' => false]);

        return redirect()->route('products.index')
            ->with('success', 'Product deactivated successfully!');
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