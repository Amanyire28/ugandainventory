<?php

namespace App\Http\Controllers;

use App\Services\NumberGenerator;
use App\Services\InventoryService;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    /**
     * Display a listing of inter-branch stock transfers.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $businessId = $user->business_id;

        $transfers = StockTransfer::where('business_id', $businessId)
            ->with(['fromLocation', 'toLocation', 'createdBy', 'items.product'])
            ->orderByDesc('created_at')
            ->paginate(15);

        $locations = Location::where('business_id', $businessId)
            ->where('is_active', true)
            ->get();

        $products = Product::where('business_id', $businessId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('stock_transfers.index', compact('transfers', 'locations', 'products'));
    }

    /**
     * Store a newly created stock transfer between branches.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $businessId = $user->business_id;

        $validated = $request->validate([
            'from_location_id' => 'required|exists:locations,id',
            'to_location_id'   => 'required|exists:locations,id|different:from_location_id',
            'notes'            => 'nullable|string|max:500',
            'products'         => 'required|array|min:1',
            'products.*.id'    => 'required|exists:products,id',
            'products.*.qty'   => 'required|numeric|min:0.01',
        ]);

        try {
            DB::beginTransaction();

            // Generate transfer number via centralized service (concurrency-safe)
            $transferNumber = (new NumberGenerator())->nextTransferNumber($businessId);

            $transfer = StockTransfer::create([
                'business_id'        => $businessId,
                'transfer_number'    => $transferNumber,
                'from_location_id'   => $validated['from_location_id'],
                'to_location_id'     => $validated['to_location_id'],
                'created_by_user_id' => $user->id,
                'status'             => 'completed',
                'notes'              => $validated['notes'] ?? null,
                'transferred_at'     => now(),
            ]);

            // Deduct from source and add to destination via InventoryService.
            // Locks each product row in ascending product_id order before
            // validating available quantity — prevents concurrent transfer race conditions.
            $transferItems = array_map(fn($p) => [
                'product_id' => $p['id'],
                'qty'        => $p['qty'],
            ], $validated['products']);

            (new InventoryService())->deductForTransfer(
                $transfer,
                $transferItems,
                (int) $validated['from_location_id'],
                (int) $validated['to_location_id'],
                $user->id
            );

            // Audit Log
            \App\Models\AuditLog::log('stock_transfer', StockTransfer::class, $transfer->id, null, $transfer->toArray());

            DB::commit();

            return redirect()->route('stock-transfers.index')
                ->with('success', "Stock transfer {$transferNumber} processed successfully!");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to execute stock transfer: ' . $e->getMessage());
        }
    }
}
