<table class="min-w-full divide-y divide-gray-200">
    <thead class="bg-gray-50">
        <tr>
            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Invoice #</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Customer</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Amount</th>
            <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
            <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
        @forelse($invoices as $invoice)
            <tr class="hover:bg-gray-50 transition-colors duration-150">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-indigo-600">
                    <a href="{{ route('invoices.show', $invoice->id) }}" class="hover:underline">
                        {{ $invoice->invoice_number }}
                    </a>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-indigo-50 rounded-full flex items-center justify-center mr-3 text-indigo-600 font-bold text-xs">
                            {{ substr($invoice->customer->name ?? 'W', 0, 1) }}
                        </div>
                        <span class="font-medium text-gray-900">{{ $invoice->customer->name ?? 'Walk-in Customer' }}</span>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ $invoice->created_at->format('M d, Y') }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-gray-900">
                    UGX {{ number_format($invoice->total, 0) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                    <span class="px-2.5 py-1 text-xs font-bold rounded-full 
                        {{ $invoice->status === 'paid' ? 'bg-green-100 text-green-800' : 
                           ($invoice->status === 'partial' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                        {{ ucfirst($invoice->status) }}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium space-x-2">
                    <!-- View -->
                    <a href="{{ route('invoices.show', $invoice->id) }}"
                       class="inline-flex items-center p-2 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 rounded-lg transition-colors duration-200" 
                       title="View Invoice">
                        <i class="fas fa-eye text-sm"></i>
                    </a>
                   
                    <!-- Edit (Only unpaid) -->
                    @if($invoice->status === 'unpaid')
                    <a href="{{ route('invoices.edit', $invoice->id) }}"
                       class="inline-flex items-center p-2 bg-amber-50 text-amber-600 hover:bg-amber-100 rounded-lg transition-colors duration-200" 
                       title="Edit Invoice">
                        <i class="fas fa-edit text-sm"></i>
                    </a>
                    @endif

                    <!-- Pay -->
                    @if($invoice->status !== 'paid')
                        <a href="{{ route('invoices.payForm', $invoice->id) }}"
                           class="inline-flex items-center px-2.5 py-2 bg-green-50 text-green-700 hover:bg-green-100 rounded-lg text-xs font-semibold transition-colors duration-200"
                           title="Record Payment">
                           <i class="fas fa-coins mr-1"></i> Pay
                        </a>
                    @endif

                    <!-- Delete -->
                    <form action="{{ route('invoices.destroy', $invoice->id) }}" method="POST"
                          class="inline-block"
                          onsubmit="return confirm('Are you sure you want to delete this invoice? This action cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center p-2 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg transition-colors duration-200" 
                                title="Delete Invoice">
                            <i class="fas fa-trash text-sm"></i>
                        </button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                    <div class="flex flex-col items-center">
                        <i class="fas fa-file-invoice text-4xl text-gray-300 mb-2"></i>
                        <p class="text-sm font-medium">No invoices found</p>
                    </div>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>