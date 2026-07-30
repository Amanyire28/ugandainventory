@extends('layouts.app')

@section('title', 'Purchase — ' . $purchase->purchase_number)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-file-invoice text-indigo-600 mr-2"></i>{{ $purchase->purchase_number }}
            </h2>
            <p class="text-sm text-gray-500 mt-1">
                Recorded on {{ $purchase->created_at->format('d M Y, h:i A') }}
                by <span class="font-semibold text-gray-700">{{ $purchase->user->name ?? 'Unknown' }}</span>
            </p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('purchases.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium transition">
                <i class="fas fa-arrow-left mr-2"></i>Back
            </a>
            <form method="POST" action="{{ route('purchases.destroy', $purchase->id) }}"
                  onsubmit="return confirm('Cancel this purchase? Stock will be reversed.')">
                @csrf @method('DELETE')
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-red-100 text-red-600 hover:bg-red-200 rounded-lg text-sm font-medium transition">
                    <i class="fas fa-times mr-2"></i>Cancel Purchase
                </button>
            </form>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded-r-lg flex items-center">
            <i class="fas fa-check-circle mr-3 text-green-500"></i>{{ session('success') }}
        </div>
    @elseif(session('error'))
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-r-lg flex items-center">
            <i class="fas fa-exclamation-circle mr-3 text-red-500"></i>{{ session('error') }}
        </div>
    @endif

    {{-- Info Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        {{-- Supplier --}}
        <div class="bg-white rounded-xl shadow p-5 border-l-4 border-indigo-500">
            <p class="text-xs font-semibold text-gray-400 uppercase mb-1"><i class="fas fa-truck mr-1"></i>Supplier</p>
            @if($purchase->supplier)
                <p class="font-bold text-gray-800">{{ $purchase->supplier->name }}</p>
                @if($purchase->supplier->phone)
                    <p class="text-sm text-gray-500 mt-0.5">{{ $purchase->supplier->phone }}</p>
                @endif
            @else
                <p class="text-gray-500 italic text-sm">No supplier recorded</p>
            @endif
        </div>

        {{-- Date --}}
        <div class="bg-white rounded-xl shadow p-5 border-l-4 border-blue-500">
            <p class="text-xs font-semibold text-gray-400 uppercase mb-1"><i class="fas fa-calendar mr-1"></i>Purchase Date</p>
            <p class="font-bold text-gray-800">{{ \Carbon\Carbon::parse($purchase->purchase_date)->format('d M Y') }}</p>
        </div>

        {{-- Payment Status --}}
        @php
            $statusColor = match($purchase->payment_status) {
                'paid'    => ['border-green-500', 'bg-green-100 text-green-700'],
                'partial' => ['border-yellow-500', 'bg-yellow-100 text-yellow-700'],
                default   => ['border-red-500', 'bg-red-100 text-red-700'],
            };
        @endphp
        <div class="bg-white rounded-xl shadow p-5 border-l-4 {{ $statusColor[0] }}">
            <p class="text-xs font-semibold text-gray-400 uppercase mb-1"><i class="fas fa-credit-card mr-1"></i>Payment Status</p>
            <span class="inline-block px-3 py-1 rounded-full text-xs font-bold uppercase {{ $statusColor[1] }}">
                {{ ucfirst($purchase->payment_status) }}
            </span>
        </div>
    </div>

    {{-- Line Items Table --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-700 text-base"><i class="fas fa-boxes text-indigo-500 mr-2"></i>Items Purchased</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left font-semibold text-gray-500 uppercase text-xs">#</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-500 uppercase text-xs">Product</th>
                        <th class="px-6 py-3 text-center font-semibold text-gray-500 uppercase text-xs">SKU</th>
                        <th class="px-6 py-3 text-center font-semibold text-gray-500 uppercase text-xs">Qty</th>
                        <th class="px-6 py-3 text-right font-semibold text-gray-500 uppercase text-xs">Unit Cost</th>
                        <th class="px-6 py-3 text-right font-semibold text-gray-500 uppercase text-xs">Line Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 text-gray-700">
                    @foreach($purchase->items as $i => $item)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 text-gray-400">{{ $i + 1 }}</td>
                            <td class="px-6 py-4">
                                <p class="font-semibold text-gray-800">{{ $item->product->name ?? '(deleted product)' }}</p>
                                @if($item->product?->unit)
                                    <p class="text-xs text-gray-400">per {{ $item->product->unit }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center text-gray-500 font-mono text-xs">
                                {{ $item->product->sku ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-center font-semibold">{{ number_format($item->quantity, 2) }}</td>
                            <td class="px-6 py-4 text-right text-gray-600">UGX {{ number_format($item->unit_cost) }}</td>
                            <td class="px-6 py-4 text-right font-bold text-gray-900">UGX {{ number_format($item->total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-right font-bold text-gray-600 uppercase text-xs">Grand Total</td>
                        <td class="px-6 py-4 text-right font-black text-indigo-700 text-base">
                            UGX {{ number_format($purchase->total) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Notes --}}
    @if($purchase->notes)
        <div class="bg-white rounded-xl shadow p-5">
            <h3 class="font-bold text-gray-700 text-sm uppercase mb-2"><i class="fas fa-sticky-note text-yellow-400 mr-2"></i>Notes</h3>
            <p class="text-gray-600 text-sm whitespace-pre-line">{{ $purchase->notes }}</p>
        </div>
    @endif

</div>
@endsection
