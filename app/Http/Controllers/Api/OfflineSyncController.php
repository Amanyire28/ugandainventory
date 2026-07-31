<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Customer;
use App\Models\User;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockAdjustment;
use App\Models\StockTakingSession;
use App\Models\PwaDevice;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\CustomerTransaction;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class OfflineSyncController extends Controller
{
    /**
     * Authenticate user and issue a Sanctum token for PWA offline use
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_uuid' => 'required|string|max:128',
            'device_name' => 'required|string|max:255',
            'app_version' => 'required|string|max:50',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid login credentials.',
            ], 401);
        }

        $user = User::where('email', $validated['email'])->firstOrFail();

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'User account is inactive.',
            ], 403);
        }

        // 1. Register/Update Device
        $device = PwaDevice::updateOrCreate(
            ['device_uuid' => $validated['device_uuid']],
            [
                'device_name' => $validated['device_name'],
                'business_id' => $user->business_id,
                'user_id' => $user->id,
                'app_version' => $validated['app_version'],
                'last_online_at' => now(),
                'status' => 'online',
            ]
        );

        // 2. Issue Sanctum token for this specific device
        $user->tokens()->where('name', $validated['device_name'])->delete();
        $token = $user->createToken($validated['device_name'])->plainTextToken;

        // 3. Return auth token + profile info + metadata
        return response()->json([
            'success' => true,
            'token' => $token,
            'device' => $device,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'business_id' => $user->business_id,
                'role' => $user->role->name ?? 'Cashier',
                'permissions' => $user->role ? $user->role->permissions()->pluck('name')->toArray() : [],
            ]
        ]);
    }

    /**
     * Revoke PWA device token
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'success' => true,
            'message' => 'Device logged out and token revoked.',
        ]);
    }

    /**
     * Register or update a PWA device status
     */
    public function registerDevice(Request $request)
    {
        $validated = $request->validate([
            'device_uuid' => 'required|string|max:128',
            'device_name' => 'required|string|max:255',
            'app_version' => 'required|string|max:50',
            'status' => 'nullable|string|in:online,offline,syncing',
        ]);

        $user = Auth::user();
        $businessId = $user->business_id;

        $device = PwaDevice::updateOrCreate(
            ['device_uuid' => $validated['device_uuid']],
            [
                'device_name' => $validated['device_name'],
                'business_id' => $businessId,
                'user_id' => $user->id,
                'app_version' => $validated['app_version'],
                'last_online_at' => now(),
                'status' => $validated['status'] ?? 'online',
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Device registered successfully',
            'device' => $device,
        ]);
    }

    /**
     * Sync customers created or updated offline
     */
    public function syncCustomers(Request $request)
    {
        $validated = $request->validate([
            'customers' => 'required|array',
            'customers.*.offline_uuid' => 'required|string|max:36',
            'customers.*.name' => 'required|string|max:255',
            'customers.*.email' => 'nullable|email|max:255',
            'customers.*.phone' => 'nullable|string|max:20',
            'customers.*.address' => 'nullable|string|max:500',
            'customers.*.device_id' => 'required|string|max:128',
        ]);

        $user = Auth::user();
        $businessId = $user->business_id;
        $mapping = [];

        DB::beginTransaction();
        try {
            foreach ($validated['customers'] as $custData) {
                // Check for conflict: customer email or phone updated elsewhere
                $existing = Customer::where('business_id', $businessId)
                    ->where('offline_uuid', $custData['offline_uuid'])
                    ->first();

                if (!$existing && !empty($custData['phone'])) {
                    // Fallback to check by phone for duplicate prevention
                    $existing = Customer::where('business_id', $businessId)
                        ->where('phone', $custData['phone'])
                        ->first();
                }

                if ($existing) {
                    // Update
                    $existing->update([
                        'name' => $custData['name'],
                        'email' => $custData['email'],
                        'phone' => $custData['phone'],
                        'address' => $custData['address'],
                        'device_id' => $custData['device_id'],
                    ]);
                    $customer = $existing;
                } else {
                    // Create
                    $customer = Customer::create([
                        'business_id' => $businessId,
                        'offline_uuid' => $custData['offline_uuid'],
                        'device_id' => $custData['device_id'],
                        'name' => $custData['name'],
                        'email' => $custData['email'],
                        'phone' => $custData['phone'],
                        'address' => $custData['address'],
                        'is_active' => true,
                    ]);
                }

                $mapping[$custData['offline_uuid']] = $customer->id;
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'mapping' => $mapping,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Offline customer sync failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync sales created offline
     */
    public function syncSales(Request $request)
    {
        $validated = $request->validate([
            'sales' => 'required|array',
            'device_id' => 'required|string|max:128',
        ]);

        $user = Auth::user();
        $businessId = $user->business_id;
        $deviceUuid = $validated['device_id'];

        // Keep track of device online status
        PwaDevice::where('device_uuid', $deviceUuid)->update([
            'last_online_at' => now(),
            'last_sync_at' => now(),
            'status' => 'syncing',
        ]);

        $results = [];
        $hasConflicts = false;

        foreach ($validated['sales'] as $saleData) {
            $offlineUuid = $saleData['offline_uuid'];

            // 1. Idempotency Check
            $existingSale = Sale::where('business_id', $businessId)
                ->where('offline_uuid', $offlineUuid)
                ->first();

            if ($existingSale) {
                $results[$offlineUuid] = [
                    'status' => 'synced',
                    'sale_id' => $existingSale->id,
                    'sale_number' => $existingSale->sale_number,
                ];
                continue;
            }

            // 2. Conflict Checking & Validation
            $saleConflicts = [];

            // Verify customer exists
            $customerId = null;
            if (!empty($saleData['customer_offline_uuid'])) {
                $customer = Customer::where('business_id', $businessId)
                    ->where('offline_uuid', $saleData['customer_offline_uuid'])
                    ->first();
                if ($customer) {
                    $customerId = $customer->id;
                } else {
                    $saleConflicts[] = [
                        'type' => 'customer_not_found',
                        'message' => "Customer not found on server.",
                        'offline_uuid' => $saleData['customer_offline_uuid'],
                    ];
                }
            } elseif (!empty($saleData['customer_id'])) {
                $customer = Customer::where('business_id', $businessId)->find($saleData['customer_id']);
                if ($customer) {
                    $customerId = $customer->id;
                } else {
                    $saleConflicts[] = [
                        'type' => 'customer_not_found',
                        'message' => "Customer #{$saleData['customer_id']} not found.",
                        'customer_id' => $saleData['customer_id'],
                    ];
                }
            }

            // Check products stock & existence
            $items = $saleData['items'] ?? [];
            foreach ($items as $item) {
                $product = Product::where('business_id', $businessId)->find($item['product_id']);
                if (!$product) {
                    $saleConflicts[] = [
                        'type' => 'product_not_found',
                        'message' => "Product ID {$item['product_id']} does not exist on server.",
                        'product_id' => $item['product_id'],
                    ];
                } else {
                    // Check stock
                    if ($product->quantity < $item['quantity']) {
                        $saleConflicts[] = [
                            'type' => 'insufficient_stock',
                            'message' => "Insufficient stock for {$product->name}. Server quantity: {$product->quantity}, Offline sold: {$item['quantity']}.",
                            'product_id' => $item['product_id'],
                            'server_quantity' => $product->quantity,
                            'sold_quantity' => $item['quantity'],
                        ];
                    }
                }
            }

            // If there are conflicts, record them and skip saving this sale
            if (!empty($saleConflicts)) {
                $hasConflicts = true;
                $results[$offlineUuid] = [
                    'status' => 'conflict',
                    'conflicts' => $saleConflicts,
                ];

                // Audit the conflict
                AuditLog::log('offline_sale_sync_conflict', Sale::class, null, null, [
                    'offline_uuid' => $offlineUuid,
                    'device_id' => $deviceUuid,
                    'conflicts' => $saleConflicts,
                    'sale_data' => $saleData,
                ]);

                continue;
            }

            // 3. Process the Sale
            DB::beginTransaction();
            try {
                // Determine location
                $locationId = $saleData['location_id'] ?? $user->location_id ?? DB::table('locations')->where('business_id', $businessId)->where('is_main', true)->value('id') ?? DB::table('locations')->where('business_id', $businessId)->value('id');

                $sale = Sale::create([
                    'business_id' => $businessId,
                    'location_id' => $locationId,
                    'user_id' => $user->id,
                    'customer_id' => $customerId,
                    'sale_number' => $saleData['sale_number'],
                    'sale_date' => Carbon::parse($saleData['sale_date']),
                    'subtotal' => $saleData['subtotal'],
                    'tax_amount' => $saleData['tax_amount'],
                    'discount_amount' => $saleData['discount_amount'],
                    'total' => $saleData['total'],
                    'payment_status' => $saleData['payment_status'] ?? 'paid',
                    'payment_method' => $saleData['payment_method'] ?? 'cash',
                    'notes' => $saleData['notes'] ?? null,
                    'offline_uuid' => $offlineUuid,
                    'device_id' => $deviceUuid,
                    'is_offline_sale' => true,
                    'sync_status' => 'synced',
                    'synced_at' => now(),
                ]);

                // Create items & deduct stock
                foreach ($items as $item) {
                    $product = Product::findOrFail($item['product_id']);

                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['price'],
                        'total' => $item['quantity'] * $item['price'],
                        'selling_price' => $item['price'],
                        'cost_price_at_sale' => $product->cost_price,
                        'subtotal' => $item['quantity'] * $item['price'],
                    ]);

                    // Record Inventory Transaction
                    InventoryTransaction::create([
                        'business_id' => $businessId,
                        'product_id' => $product->id,
                        'transaction_type' => 'SALE',
                        'quantity_in' => 0,
                        'quantity_out' => $item['quantity'],
                        'reference_type' => Sale::class,
                        'reference_id' => $sale->id,
                        'description' => "Offline POS Sale #{$sale->sale_number} (Synced)",
                        'created_by' => $user->id,
                    ]);

                    // Sync location inventory table
                    $inventory = Inventory::where('business_id', $businessId)
                        ->where('product_id', $product->id)
                        ->first();
                    if ($inventory) {
                        $inventory->decrement('quantity', $item['quantity']);
                    }

                    // Update product table quantity
                    $product->decrement('quantity', $item['quantity']);
                }

                // Record Customer Transaction if applicable
                if ($sale->customer_id) {
                    $prevBal = CustomerTransaction::where('customer_id', $sale->customer_id)
                        ->orderBy('id', 'desc')
                        ->value('balance') ?? 0;
                    CustomerTransaction::create([
                        'customer_id' => $sale->customer_id,
                        'sale_id' => $sale->id,
                        'transaction_type' => 'SALE',
                        'debit' => $sale->total,
                        'credit' => $sale->total,
                        'balance' => $prevBal,
                        'notes' => "Offline POS Sale #{$sale->sale_number} (Synced)",
                    ]);
                }

                // Log audit trail
                AuditLog::log('offline_sale_sync_success', Sale::class, $sale->id, null, [
                    'offline_uuid' => $offlineUuid,
                    'device_id' => $deviceUuid,
                    'sale_id' => $sale->id,
                ]);

                DB::commit();

                $results[$offlineUuid] = [
                    'status' => 'synced',
                    'sale_id' => $sale->id,
                    'sale_number' => $sale->sale_number,
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Failed to sync offline sale {$offlineUuid}: " . $e->getMessage());
                $results[$offlineUuid] = [
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                ];
            }
        }

        // Set device back to online
        PwaDevice::where('device_uuid', $deviceUuid)->update([
            'status' => 'online',
        ]);

        if ($hasConflicts) {
            return response()->json([
                'success' => false,
                'message' => 'Some transactions had sync conflicts.',
                'results' => $results,
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'All sales synchronized successfully.',
            'results' => $results,
        ]);
    }

    /**
     * Sync offline stock taking sessions
     */
    public function syncStockTakes(Request $request)
    {
        $validated = $request->validate([
            'sessions' => 'required|array',
            'device_id' => 'required|string|max:128',
        ]);

        $user = Auth::user();
        $businessId = $user->business_id;
        $syncedSessions = [];

        foreach ($validated['sessions'] as $sessionData) {
            DB::beginTransaction();
            try {
                // 1. Create Stock Taking Session
                $session = StockTakingSession::create([
                    'business_id' => $businessId,
                    'session_date' => Carbon::parse($sessionData['session_date'] ?? now()),
                    'notes' => ($sessionData['notes'] ?? 'Offline Stock Take') . ' (Synced)',
                    'status' => 'closed',
                    'initiated_by' => $user->id,
                ]);

                // 2. Process Counts & Create Adjustments
                $counts = $sessionData['counts'] ?? [];
                foreach ($counts as $count) {
                    $product = Product::where('business_id', $businessId)->find($count['product_id']);
                    if (!$product) continue;

                    $systemQty = $product->quantity;
                    $physicalQty = $count['physical_count'];
                    $variance = $physicalQty - $systemQty;

                    // Create approved adjustment record
                    $adjustment = StockAdjustment::create([
                        'business_id' => $businessId,
                        'stock_taking_session_id' => $session->id,
                        'product_id' => $product->id,
                        'adjustment_date' => now(),
                        'physical_count' => $physicalQty,
                        'system_quantity' => $systemQty,
                        'variance' => $variance,
                        'adjustment_quantity' => $variance,
                        'reason' => 'Stock Take',
                        'notes' => $count['notes'] ?? 'Offline recorded',
                        'status' => 'approved',
                        'recorded_by' => $user->id,
                        'approved_by' => $user->id,
                        'approved_at' => now(),
                    ]);

                    // Apply count to product quantity
                    $product->quantity = $physicalQty;
                    $product->save();

                    // Sync location inventory
                    $inventory = Inventory::where('business_id', $businessId)
                        ->where('product_id', $product->id)
                        ->first();
                    if ($inventory) {
                        $inventory->quantity = $physicalQty;
                        $inventory->save();
                    }

                    // Record Inventory Transaction
                    $qtyIn = $variance > 0 ? $variance : 0;
                    $qtyOut = $variance < 0 ? abs($variance) : 0;

                    InventoryTransaction::create([
                        'business_id' => $businessId,
                        'product_id' => $product->id,
                        'transaction_type' => 'ADJUSTMENT',
                        'quantity_in' => $qtyIn,
                        'quantity_out' => $qtyOut,
                        'reference_type' => StockAdjustment::class,
                        'reference_id' => $adjustment->id,
                        'description' => sprintf(
                            'Offline Stock Take. System: %.2f → Physical: %.2f (Variance: %+.2f).',
                            $systemQty,
                            $physicalQty,
                            $variance
                        ),
                        'created_by' => $user->id,
                    ]);

                    // Audit Log
                    AuditLog::log(
                        'stock_adjustment',
                        Product::class,
                        $product->id,
                        ['quantity' => $systemQty],
                        [
                            'quantity' => $physicalQty,
                            'variance' => $variance,
                            'session_id' => $session->id,
                        ]
                    );
                }

                AuditLog::log('offline_stock_take_sync', StockTakingSession::class, $session->id, null, [
                    'device_id' => $validated['device_id'],
                ]);

                DB::commit();
                $syncedSessions[] = $sessionData['offline_uuid'] ?? $session->id;

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Failed to sync offline stock take session: ' . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'synced_sessions' => $syncedSessions,
        ]);
    }

    /**
     * Download operational data for PWA offline cache (Supports Incremental Sync)
     */
    public function downloadData(Request $request)
    {
        $user = Auth::user();
        $businessId = $user->business_id;
        $lastSync = $request->query('last_sync');

        $queryDate = null;
        if ($lastSync) {
            try {
                $queryDate = Carbon::parse($lastSync);
            } catch (\Exception $e) {
                // Ignore parse errors
            }
        }

        // 1. Products
        $productsQuery = Product::where('business_id', $businessId)->where('is_active', true);
        if ($queryDate) {
            $productsQuery->where('updated_at', '>', $queryDate);
        }
        $products = $productsQuery->get();

        // 2. Categories
        $categoriesQuery = Category::where('business_id', $businessId);
        if ($queryDate) {
            $categoriesQuery->where('updated_at', '>', $queryDate);
        }
        $categories = $categoriesQuery->get();

        // 3. Customers
        $customersQuery = Customer::where('business_id', $businessId)->where('is_active', true);
        if ($queryDate) {
            $customersQuery->where('updated_at', '>', $queryDate);
        }
        $customers = $customersQuery->get();

        // 4. Units
        $units = Product::where('business_id', $businessId)
            ->whereNotNull('unit')
            ->distinct()
            ->pluck('unit')
            ->toArray();
        if (empty($units)) {
            $units = ['pcs', 'kg', 'liters', 'boxes', 'packets', 'g'];
        }

        // 5. Business Settings
        $business = DB::table('businesses')->where('id', $businessId)->first([
            'name', 'email', 'phone', 'address', 'tax_number', 'tax_enabled', 'tax_rate'
        ]);

        // 6. User Profile & Permissions
        $profile = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->name ?? 'Cashier',
            'permissions' => $user->role ? $user->role->permissions()->pluck('name')->toArray() : [],
        ];

        return response()->json([
            'success' => true,
            'last_sync' => now()->toDateTimeString(),
            'data' => [
                'products' => $products,
                'categories' => $categories,
                'customers' => $customers,
                'units' => $units,
                'settings' => $business,
                'profile' => $profile,
            ]
        ]);
    }
}
