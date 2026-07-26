<?php

namespace App\Http\Controllers;

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

            $transferNumber = 'TRF-' . strtoupper(uniqid());

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

            foreach ($validated['products'] as $item) {
                $product = Product::findOrFail($item['id']);
                $transferQty = (float)$item['qty'];

                // Record transfer item
                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id'        => $product->id,
                    'quantity'          => $transferQty,
                    'unit_cost'         => $product->cost_price ?? 0,
                ]);

                // 1. Deduct stock from Source Branch
                $sourceInv = Inventory::firstOrCreate(
                    [
                        'product_id'  => $product->id,
                        'location_id' => $validated['from_location_id'],
                    ],
                    [
                        'quantity'      => 0,
                        'reorder_level' => $product->reorder_level ?? 5,
                    ]
                );

                $sourceInv->decrement('quantity', $transferQty);

                // 2. Add stock to Destination Branch
                $destInv = Inventory::firstOrCreate(
                    [
                        'product_id'  => $product->id,
                        'location_id' => $validated['to_location_id'],
                    ],
                    [
                        'quantity'      => 0,
                        'reorder_level' => $product->reorder_level ?? 5,
                    ]
                );

                $destInv->increment('quantity', $transferQty);
            }

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
