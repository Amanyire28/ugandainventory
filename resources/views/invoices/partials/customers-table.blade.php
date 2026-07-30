<!-- Customers Table -->
<div class="overflow-x-auto rounded-xl border border-gray-200">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">Customer Name</th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">Phone</th>
                <th scope="col" class="px-6 py-3 text-right font-semibold text-gray-500 uppercase tracking-wider">Total Outstanding</th>
                <th scope="col" class="px-6 py-3 text-center font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200 text-gray-700">
            @forelse($customers as $customer)
                @php
                    $outstanding = $customer->invoices->sum(fn($inv) => ($inv->status != 'paid') ? ($inv->total - $inv->paid) : 0);
                @endphp
                <tr class="hover:bg-gray-50 transition-colors duration-150">
                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-indigo-50 rounded-full flex items-center justify-center mr-3 text-indigo-600 font-bold text-xs">
                                {{ substr($customer->name, 0, 1) }}
                            </div>
                            <span class="font-semibold">{{ $customer->name }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-gray-500">
                        {{ $customer->phone ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right font-bold {{ $outstanding > 0 ? 'text-red-600' : 'text-green-600' }}">
                        UGX {{ number_format($outstanding) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <a href="{{ route('invoices.customerSummary', $customer->id) }}" 
                           class="inline-flex items-center px-4 py-1.5 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 rounded-lg text-xs font-bold transition duration-150">
                            <i class="fas fa-eye mr-1.5"></i> View Summary
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-6 py-10 text-center text-gray-400">
                        <div class="flex flex-col items-center">
                            <i class="fas fa-users-slash text-4xl text-gray-300 mb-2"></i>
                            <p class="text-sm font-medium">No customers with invoices found</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
