@if($purchases->isEmpty())
    <div class="flex flex-col items-center justify-center py-16 text-gray-400">
        <i class="fas fa-box-open text-5xl mb-4 text-gray-300"></i>
        <p class="text-lg font-semibold text-gray-500">No purchases found</p>
        <p class="text-sm mt-1">Try adjusting your filters or <a href="{{ route('purchases.create') }}" class="text-indigo-600 hover:underline font-medium">record a new purchase</a>.</p>
    </div>
@else
<div class="overflow-x-auto rounded-xl border border-gray-200">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-5 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">Purchase #</th>
                <th class="px-5 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">Supplier</th>
                <th class="px-5 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                <th class="px-5 py-3 text-center font-semibold text-gray-500 uppercase tracking-wider">Items</th>
                <th class="px-5 py-3 text-right font-semibold text-gray-500 uppercase tracking-wider">Total</th>
                <th class="px-5 py-3 text-center font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-5 py-3 text-center font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-100 text-gray-700">
            @foreach($purchases as $purchase)
                @php
                    $statusClasses = match($purchase->payment_status) {
                        'paid'    => 'bg-green-100 text-green-700',
                        'partial' => 'bg-yellow-100 text-yellow-700',
                        default   => 'bg-red-100 text-red-700',
                    };
                @endphp
                <tr class="hover:bg-gray-50 transition-colors duration-100">
                    <td class="px-5 py-3 font-mono font-semibold text-indigo-700">
                        {{ $purchase->purchase_number }}
                    </td>
                    <td class="px-5 py-3">
                        @if($purchase->supplier)
                            <span class="font-medium text-gray-800">{{ $purchase->supplier->name }}</span>
                        @else
                            <span class="text-gray-400 italic">No Supplier</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-gray-500 whitespace-nowrap">
                        {{ \Carbon\Carbon::parse($purchase->purchase_date)->format('d M Y') }}
                    </td>
                    <td class="px-5 py-3 text-center font-semibold text-gray-700">
                        {{ $purchase->items->count() }}
                    </td>
                    <td class="px-5 py-3 text-right font-black text-gray-900">
                        UGX {{ number_format($purchase->total) }}
                    </td>
                    <td class="px-5 py-3 text-center">
                        <span class="px-3 py-1 rounded-full text-xs font-bold uppercase {{ $statusClasses }}">
                            {{ ucfirst($purchase->payment_status) }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('purchases.show', $purchase->id) }}"
                               class="inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 rounded-lg text-xs font-semibold transition">
                                <i class="fas fa-eye mr-1"></i>View
                            </a>
                            <form method="POST" action="{{ route('purchases.destroy', $purchase->id) }}"
                                  onsubmit="return confirm('Cancel purchase {{ $purchase->purchase_number }}? This will reverse the stock.')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="inline-flex items-center px-3 py-1.5 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg text-xs font-semibold transition">
                                    <i class="fas fa-times mr-1"></i>Cancel
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Pagination --}}
@if($purchases->hasPages())
    <div class="mt-4 flex justify-end">
        {{ $purchases->links() }}
    </div>
@endif
@endif
