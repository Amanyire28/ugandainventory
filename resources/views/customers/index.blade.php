@extends('layouts.app')

@section('title', 'Customers')

@section('page-title')
    <i class="fas fa-users text-indigo-600 mr-2"></i>Customers
@endsection

@section('content')
<div class="bg-white rounded-xl shadow-lg p-6">
    
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 space-y-4 md:space-y-0">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Customer List</h2>
            <p class="text-gray-600 text-sm mt-1">Manage your customers</p>
        </div>
        <div>
            <a href="{{ route('customers.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center">
                <i class="fas fa-plus mr-2"></i>Add Customer
            </a>
        </div>
    </div>

    <!-- Search -->
    <div class="mb-6">
        <input type="text" 
               id="searchInput" 
               placeholder="Search customers..." 
               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
    </div>

    <!-- Customers Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Address</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($customers as $customer)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-indigo-600 rounded-full flex items-center justify-center mr-3">
                                <span class="text-white font-bold">{{ substr($customer->name, 0, 1) }}</span>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900">{{ $customer->name }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $customer->phone }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $customer->email ?? 'N/A' }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $customer->address ?? 'N/A' }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                            {{ $customer->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $customer->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    <td class="px-4 py-3">
                        <div class="flex space-x-3 items-center">
                            <a href="{{ route('customers.statement', $customer->id) }}" 
                               class="text-emerald-600 hover:text-emerald-800" title="View Statement">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </a>
                            <a href="{{ route('customers.edit', $customer) }}" 
                               class="text-indigo-600 hover:text-indigo-800" title="Edit Customer">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="{{ route('customers.destroy', $customer) }}" 
                                  onsubmit="return confirm('Delete this customer?')" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800" title="Delete Customer">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                        No customers found. <a href="{{ route('customers.create') }}" class="text-indigo-600">Add your first customer</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $customers->links() }}
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('searchInput').addEventListener('input', function(e) {
        let searchValue = e.target.value.toLowerCase();
        let rows = document.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            let text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchValue) ? '' : 'none';
        });
    });
</script>
@endpush
@endsection