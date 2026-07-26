@extends('layouts.app')

@section('title', 'Staff Management')

@section('page-title')
    <i class="fas fa-users text-indigo-600 mr-2"></i>Staff Management
@endsection

@section('content')
<div class="space-y-6">
    
    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Total Staff -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Staff</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $totalStaff }}</p>
                </div>
                <div class="bg-indigo-100 rounded-full p-4">
                    <i class="fas fa-users text-indigo-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Active Staff -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Active Staff</p>
                    <p class="text-3xl font-bold text-green-600 mt-2">{{ $activeStaff }}</p>
                </div>
                <div class="bg-green-100 rounded-full p-4">
                    <i class="fas fa-user-check text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Administrators -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Administrators</p>
                    <p class="text-3xl font-bold text-purple-600 mt-2">{{ $adminCount }}</p>
                </div>
                <div class="bg-purple-100 rounded-full p-4">
                    <i class="fas fa-user-shield text-purple-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Cashiers -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Cashiers</p>
                    <p class="text-3xl font-bold text-blue-600 mt-2">{{ $cashierCount }}</p>
                </div>
                <div class="bg-blue-100 rounded-full p-4">
                    <i class="fas fa-cash-register text-blue-600 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Staff Table -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 space-y-4 md:space-y-0">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Staff Members</h2>
                <p class="text-gray-600 text-sm mt-1">Manage your team members</p>
            </div>
            <div>
                <a href="{{ route('staff.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center">
                    <i class="fas fa-user-plus mr-2"></i>Add Staff
                </a>
            </div>
        </div>

        <!-- Search -->
        <div class="mb-6">
            <input type="text" 
                   id="searchInput" 
                   placeholder="Search staff..." 
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>

        <!-- Staff Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Staff Member</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Assigned Branch</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact Info</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Sales (This Month)</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Revenue (This Month)</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($staff as $member)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center mr-3">
                                    <span class="text-white font-bold text-lg">{{ substr($member->name, 0, 1) }}</span>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $member->name }}</p>
                                    <p class="text-xs text-gray-500">Joined {{ $member->created_at->format('M d, Y') }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-3 py-1 text-xs font-semibold rounded-full
                                {{ $member->role->name === 'admin' ? 'bg-purple-100 text-purple-800' : 
                                   ($member->role->name === 'manager' ? 'bg-blue-100 text-blue-800' : 
                                   ($member->role->name === 'cashier' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800')) }}">
                                <i class="fas 
                                    {{ $member->role->name === 'admin' ? 'fa-user-shield' : 
                                       ($member->role->name === 'manager' ? 'fa-user-tie' : 
                                       ($member->role->name === 'cashier' ? 'fa-cash-register' : 'fa-user')) }} mr-1"></i>
                                {{ $member->role->display_name }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs font-bold">
                            @if($member->location)
                                <span class="px-2.5 py-1 bg-indigo-50 text-indigo-700 rounded-lg border border-indigo-100 flex items-center w-fit">
                                    <i class="fas fa-building mr-1 text-indigo-500"></i> {{ $member->location->name }}
                                </span>
                            @else
                                <span class="px-2.5 py-1 bg-gray-100 text-gray-600 rounded-lg flex items-center w-fit">
                                    <i class="fas fa-globe mr-1 text-gray-400"></i> All Branches (HQ)
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-sm text-gray-600">
                                <i class="fas fa-envelope mr-1"></i>{{ $member->email }}
                            </p>
                            <p class="text-sm text-gray-600">
                                <i class="fas fa-phone mr-1"></i>{{ $member->phone }}
                            </p>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span class="font-semibold text-gray-900">{{ $member->sales_count ?? 0 }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span class="font-semibold text-green-600">UGX {{ number_format($member->sales_sum_total ?? 0, 0) }}</span>
                        </td>
                        
                        <!-- ✅ FIXED STATUS COLUMN (LINE 144) -->
                        <td class="px-4 py-3">
                            @if($member->id === Auth::id())
                                <!-- Cannot toggle own status -->
                                <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                    {{ $member->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    <i class="fas {{ $member->is_active ? 'fa-check-circle' : 'fa-times-circle' }} mr-1"></i>
                                    {{ $member->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            @else
                                <!-- Simple link-style toggle without form -->
                                <a href="{{ url('/staff/' . $member->id . '/toggle') }}" 
                                   onclick="event.preventDefault(); if(confirm('{{ $member->is_active ? 'Deactivate' : 'Activate' }} {{ $member->name }}?')) { document.getElementById('toggle-form-{{ $member->id }}').submit(); }"
                                   class="px-2 py-1 text-xs font-semibold rounded-full transition cursor-pointer inline-block
                                   {{ $member->is_active ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-red-100 text-red-800 hover:bg-red-200' }}">
                                    <i class="fas {{ $member->is_active ? 'fa-check-circle' : 'fa-times-circle' }} mr-1"></i>
                                    {{ $member->is_active ? 'Active' : 'Inactive' }}
                                </a>
                                
                                <!-- Hidden form for toggle -->
                                <form id="toggle-form-{{ $member->id }}" 
                                      action="{{ route('staff.update', $member) }}" 
                                      method="POST" 
                                      style="display: none;">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="name" value="{{ $member->name }}">
                                    <input type="hidden" name="email" value="{{ $member->email }}">
                                    <input type="hidden" name="phone" value="{{ $member->phone }}">
                                    <input type="hidden" name="role_id" value="{{ $member->role_id }}">
                                    <input type="hidden" name="is_active" value="{{ $member->is_active ? '0' : '1' }}">
                                    <input type="hidden" name="quick_toggle" value="1">
                                </form>
                            @endif
                        </td>
                        
                        <td class="px-4 py-3">
                            <div class="flex space-x-2">
                                <a href="{{ route('staff.show', $member) }}" 
                                   class="text-blue-600 hover:text-blue-800" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('staff.edit', $member) }}" 
                                   class="text-indigo-600 hover:text-indigo-800" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="{{ route('staff.destroy', $member) }}" 
                                      onsubmit="return confirm('Delete {{ $member->name }}? This action cannot be undone.')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                            No staff members found. <a href="{{ route('staff.create') }}" class="text-indigo-600">Add your first staff member</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $staff->links() }}
        </div>
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