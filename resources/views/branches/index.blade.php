@extends('layouts.app')

@section('title', 'Branch & Location Management - DukaFlow')

@section('content')
<div class="space-y-6">
    <!-- Header Title & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <div>
            <h1 class="text-2xl font-black text-gray-900 tracking-tight flex items-center">
                <i class="fas fa-code-branch text-indigo-600 mr-3 text-2xl"></i>
                Multi-Branch Location Management
            </h1>
            <p class="text-sm text-gray-500 font-medium mt-1">
                Oversee, monitor performance, and manage all business branches within your jurisdiction.
            </p>
        </div>
        <button onclick="openAddBranchModal()" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-md transition-all flex items-center justify-center text-sm transform active:scale-95">
            <i class="fas fa-plus mr-2"></i> Add New Branch
        </button>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 rounded-xl text-sm font-semibold shadow-sm flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-emerald-600 mr-2 text-base"></i>
                <span>{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700"><i class="fas fa-times"></i></button>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-red-50 border-l-4 border-red-500 text-red-800 rounded-xl text-sm font-semibold shadow-sm flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-600 mr-2 text-base"></i>
                <span>{{ session('error') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700"><i class="fas fa-times"></i></button>
        </div>
    @endif

    <!-- Metric Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center space-x-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl font-bold">
                <i class="fas fa-store"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Branches</p>
                <h3 class="text-2xl font-black text-gray-900">{{ count($branches) }}</h3>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center space-x-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl font-bold">
                <i class="fas fa-crown"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Main Headquarters</p>
                <h3 class="text-lg font-black text-gray-900 truncate max-w-[150px]">
                    {{ optional($branches->firstWhere('is_main', true))->name ?? 'None Set' }}
                </h3>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center space-x-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl font-bold">
                <i class="fas fa-boxes"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Stock Units Across Branches</p>
                <h3 class="text-2xl font-black text-gray-900">
                    {{ number_format($branches->sum('total_stock_qty')) }}
                </h3>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center space-x-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold">
                <i class="fas fa-chart-line"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Today's Total Branch Sales</p>
                <h3 class="text-xl font-black text-gray-900">
                    UGX {{ number_format($branches->sum('today_sales')) }}
                </h3>
            </div>
        </div>
    </div>

    <!-- Branch List Table -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-lg font-black text-gray-900 flex items-center">
                <i class="fas fa-list text-indigo-600 mr-2"></i> All Active Branches & Outlets
            </h2>
            <span class="text-xs font-bold text-gray-400 uppercase">{{ count($branches) }} Branches Managed</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-[11px] font-black text-gray-400 uppercase tracking-wider">
                        <th class="py-3.5 px-6">Branch Name & Type</th>
                        <th class="py-3.5 px-4">Contact Info</th>
                        <th class="py-3.5 px-4">Assigned Staff</th>
                        <th class="py-3.5 px-4">Current Stock Qty</th>
                        <th class="py-3.5 px-4">Today's Sales</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm font-medium">
                    @forelse($branches as $b)
                        <tr class="hover:bg-gray-50/80 transition-colors">
                            <td class="py-4 px-6">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center font-bold text-sm {{ $b->is_main ? 'bg-amber-100 text-amber-700' : 'bg-indigo-50 text-indigo-600' }}">
                                        <i class="fas {{ $b->is_main ? 'fa-crown' : 'fa-building' }}"></i>
                                    </div>
                                    <div>
                                        <div class="font-black text-gray-900 flex items-center space-x-2">
                                            <span>{{ $b->name }}</span>
                                            @if($b->is_main)
                                                <span class="text-[10px] font-black uppercase px-2 py-0.5 bg-amber-100 text-amber-800 rounded-md border border-amber-200">Main Branch (HQ)</span>
                                            @endif
                                        </div>
                                        <span class="text-xs text-gray-400 font-normal truncate block max-w-xs">{{ $b->address ?? 'No address set' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-4 text-xs font-semibold text-gray-700">
                                @if($b->phone)
                                    <div><i class="fas fa-phone text-indigo-500 mr-1"></i> {{ $b->phone }}</div>
                                @else
                                    <span class="text-gray-400 font-normal">N/A</span>
                                @endif
                            </td>
                            <td class="py-4 px-4 text-xs font-bold text-gray-800">
                                <span class="px-2.5 py-1 bg-indigo-50 text-indigo-700 rounded-lg border border-indigo-100">
                                    <i class="fas fa-users mr-1 text-indigo-500"></i> {{ $b->users_count }} Staff
                                </span>
                            </td>
                            <td class="py-4 px-4 text-xs font-black text-gray-900">
                                {{ number_format($b->total_stock_qty) }} units
                            </td>
                            <td class="py-4 px-4 text-xs font-black text-emerald-700">
                                UGX {{ number_format($b->today_sales) }}
                            </td>
                            <td class="py-4 px-4">
                                @if($b->is_active)
                                    <span class="text-[10px] font-black uppercase px-2.5 py-1 bg-emerald-100 text-emerald-800 rounded-full border border-emerald-200">Active</span>
                                @else
                                    <span class="text-[10px] font-black uppercase px-2.5 py-1 bg-gray-100 text-gray-600 rounded-full border border-gray-200">Inactive</span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-right space-x-2">
                                <button onclick="openEditBranchModal({{ json_encode($b) }})" class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="Edit Branch">
                                    <i class="fas fa-edit text-base"></i>
                                </button>
                                @if(!$b->is_main)
                                    <form action="{{ route('branches.destroy', $b->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete branch {{ $b->name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Delete Branch">
                                            <i class="fas fa-trash-alt text-base"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-gray-500 font-semibold">
                                No branches found. Click <strong>Add New Branch</strong> above to create your first outlet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Branch Modal -->
<div id="addBranchModal" class="fixed inset-0 z-[99999] hidden items-center justify-center p-4 sm:p-6 overflow-y-auto" style="position: fixed; inset: 0; z-index: 99999; background-color: rgba(15, 23, 42, 0.75); backdrop-filter: blur(4px); display: none; align-items: center; justify-center: center; padding: 1rem;">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 sm:p-8 relative my-8" style="background-color: #ffffff; border-radius: 1.5rem; max-width: 32rem; width: 100%; padding: 1.75rem; position: relative; border: 2px solid #4f46e5; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
        <button type="button" onclick="closeAddBranchModal()" class="absolute top-5 right-5 text-gray-400 hover:text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-full w-9 h-9 flex items-center justify-center transition-all" style="position: absolute; top: 1.25rem; right: 1.25rem; width: 2.25rem; height: 2.25rem; border-radius: 9999px; background-color: #f3f4f6; color: #9ca3af; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-times text-lg"></i>
        </button>

        <div class="text-center mb-6" style="text-align: center; margin-bottom: 1.5rem;">
            <div class="w-14 h-14 bg-indigo-600 text-white rounded-2xl flex items-center justify-center text-2xl mx-auto mb-3 shadow-md" style="width: 3.5rem; height: 3.5rem; background-color: #4f46e5; color: #ffffff; border-radius: 1rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.75rem auto; font-size: 1.5rem;">
                <i class="fas fa-store"></i>
            </div>
            <h2 class="text-2xl font-black text-gray-900 tracking-tight" style="font-size: 1.5rem; font-weight: 900; color: #111827; margin: 0;">Add New Branch / Outlet</h2>
            <p class="text-xs text-gray-500 mt-1 font-semibold" style="font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem; font-weight: 600;">Create a new business location under your jurisdiction.</p>
        </div>

        <form action="{{ route('branches.store') }}" method="POST" class="space-y-4" style="display: flex; flex-direction: column; gap: 1rem;">
            @csrf
            <div>
                <label class="block text-xs font-black text-gray-800 uppercase tracking-wider mb-1.5" style="display: block; font-size: 0.75rem; font-weight: 900; color: #1f2937; text-transform: uppercase; margin-bottom: 0.375rem;">
                    <i class="fas fa-building text-indigo-600 mr-1" style="color: #4f46e5;"></i> Branch Name <span class="text-red-500" style="color: #ef4444;">*</span>
                </label>
                <input type="text" name="name" required class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl text-sm font-bold text-gray-900 focus:border-indigo-600 focus:ring-0 focus:outline-none" style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #d1d5db; border-radius: 0.75rem; font-size: 0.875rem; font-weight: 700; color: #111827; box-sizing: border-box;" placeholder="e.g., Ntinda Shopping Center Branch">
            </div>

            <div>
                <label class="block text-xs font-black text-gray-800 uppercase tracking-wider mb-1.5" style="display: block; font-size: 0.75rem; font-weight: 900; color: #1f2937; text-transform: uppercase; margin-bottom: 0.375rem;">
                    <i class="fas fa-map-marker-alt text-indigo-600 mr-1" style="color: #4f46e5;"></i> Physical Address
                </label>
                <input type="text" name="address" class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl text-sm font-medium text-gray-900 focus:border-indigo-600 focus:ring-0 focus:outline-none" style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #d1d5db; border-radius: 0.75rem; font-size: 0.875rem; font-weight: 600; color: #111827; box-sizing: border-box;" placeholder="e.g., Plot 45 Ntinda Road, Kampala">
            </div>

            <div>
                <label class="block text-xs font-black text-gray-800 uppercase tracking-wider mb-1.5" style="display: block; font-size: 0.75rem; font-weight: 900; color: #1f2937; text-transform: uppercase; margin-bottom: 0.375rem;">
                    <i class="fas fa-phone text-indigo-600 mr-1" style="color: #4f46e5;"></i> Contact Phone Number
                </label>
                <input type="text" name="phone" class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl text-sm font-medium text-gray-900 focus:border-indigo-600 focus:ring-0 focus:outline-none" style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #d1d5db; border-radius: 0.75rem; font-size: 0.875rem; font-weight: 600; color: #111827; box-sizing: border-box;" placeholder="0700123456">
            </div>

            <div class="p-3.5 bg-indigo-50 rounded-xl border border-indigo-100 flex items-center space-x-3 mt-2" style="padding: 0.875rem; background-color: #eff6ff; border-radius: 0.75rem; border: 1px solid #dbeafe; display: flex; align-items: center; gap: 0.75rem; margin-top: 0.5rem;">
                <input type="checkbox" name="is_main" id="add_is_main" value="1" class="w-5 h-5 text-indigo-600 border-2 border-gray-300 rounded accent-indigo-600" style="width: 1.25rem; height: 1.25rem; cursor: pointer;">
                <label for="add_is_main" class="text-xs font-black text-indigo-900 cursor-pointer" style="font-size: 0.75rem; font-weight: 900; color: #1e3a8a; cursor: pointer;">
                    Designate as Main Branch (Headquarters)
                </label>
            </div>

            <div class="pt-4 flex items-center justify-end space-x-3" style="padding-top: 1rem; display: flex; align-items: center; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" onclick="closeAddBranchModal()" class="px-5 py-3 bg-gray-100 text-gray-700 font-bold rounded-xl text-xs hover:bg-gray-200 transition-colors" style="padding: 0.75rem 1.25rem; background-color: #f3f4f6; color: #374151; font-weight: 700; border-radius: 0.75rem; font-size: 0.75rem; border: none; cursor: pointer;">Cancel</button>
                <button type="submit" class="px-7 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-black rounded-xl text-xs shadow-lg transform active:scale-95 transition-all" style="padding: 0.75rem 1.75rem; background-color: #4f46e5; color: #ffffff; font-weight: 900; border-radius: 0.75rem; font-size: 0.75rem; border: none; cursor: pointer; box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.4);">Create Branch</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Branch Modal -->
<div id="editBranchModal" class="fixed inset-0 z-[99999] hidden items-center justify-center p-4 sm:p-6 overflow-y-auto" style="position: fixed; inset: 0; z-index: 99999; background-color: rgba(15, 23, 42, 0.75); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; padding: 1rem;">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 sm:p-8 relative my-8" style="background-color: #ffffff; border-radius: 1.5rem; max-width: 32rem; width: 100%; padding: 1.75rem; position: relative; border: 2px solid #4f46e5; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
        <button type="button" onclick="closeEditBranchModal()" class="absolute top-5 right-5 text-gray-400 hover:text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-full w-9 h-9 flex items-center justify-center transition-all" style="position: absolute; top: 1.25rem; right: 1.25rem; width: 2.25rem; height: 2.25rem; border-radius: 9999px; background-color: #f3f4f6; color: #9ca3af; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-times text-lg"></i>
        </button>

        <div class="text-center mb-6" style="text-align: center; margin-bottom: 1.5rem;">
            <div class="w-14 h-14 bg-indigo-600 text-white rounded-2xl flex items-center justify-center text-2xl mx-auto mb-3 shadow-md" style="width: 3.5rem; height: 3.5rem; background-color: #4f46e5; color: #ffffff; border-radius: 1rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.75rem auto; font-size: 1.5rem;">
                <i class="fas fa-edit"></i>
            </div>
            <h2 class="text-2xl font-black text-gray-900 tracking-tight" style="font-size: 1.5rem; font-weight: 900; color: #111827; margin: 0;">Edit Branch Details</h2>
        </div>

        <form id="editBranchForm" method="POST" class="space-y-4" style="display: flex; flex-direction: column; gap: 1rem;">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-black text-gray-800 uppercase tracking-wider mb-1.5" style="display: block; font-size: 0.75rem; font-weight: 900; color: #1f2937; text-transform: uppercase; margin-bottom: 0.375rem;">
                    <i class="fas fa-building text-indigo-600 mr-1" style="color: #4f46e5;"></i> Branch Name <span class="text-red-500" style="color: #ef4444;">*</span>
                </label>
                <input type="text" name="name" id="edit_branch_name" required class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl text-sm font-bold text-gray-900 focus:border-indigo-600 focus:ring-0 focus:outline-none" style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #d1d5db; border-radius: 0.75rem; font-size: 0.875rem; font-weight: 700; color: #111827; box-sizing: border-box;">
            </div>

            <div>
                <label class="block text-xs font-black text-gray-800 uppercase tracking-wider mb-1.5" style="display: block; font-size: 0.75rem; font-weight: 900; color: #1f2937; text-transform: uppercase; margin-bottom: 0.375rem;">
                    <i class="fas fa-map-marker-alt text-indigo-600 mr-1" style="color: #4f46e5;"></i> Physical Address
                </label>
                <input type="text" name="address" id="edit_branch_address" class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl text-sm font-medium text-gray-900 focus:border-indigo-600 focus:ring-0 focus:outline-none" style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #d1d5db; border-radius: 0.75rem; font-size: 0.875rem; font-weight: 600; color: #111827; box-sizing: border-box;">
            </div>

            <div>
                <label class="block text-xs font-black text-gray-800 uppercase tracking-wider mb-1.5" style="display: block; font-size: 0.75rem; font-weight: 900; color: #1f2937; text-transform: uppercase; margin-bottom: 0.375rem;">
                    <i class="fas fa-phone text-indigo-600 mr-1" style="color: #4f46e5;"></i> Contact Phone Number
                </label>
                <input type="text" name="phone" id="edit_branch_phone" class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl text-sm font-medium text-gray-900 focus:border-indigo-600 focus:ring-0 focus:outline-none" style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #d1d5db; border-radius: 0.75rem; font-size: 0.875rem; font-weight: 600; color: #111827; box-sizing: border-box;">
            </div>

            <div class="space-y-2 pt-2" style="display: flex; flex-direction: column; gap: 0.5rem; padding-top: 0.5rem;">
                <div class="p-3 bg-indigo-50 rounded-xl border border-indigo-100 flex items-center space-x-3" style="padding: 0.75rem; background-color: #eff6ff; border-radius: 0.75rem; border: 1px solid #dbeafe; display: flex; align-items: center; gap: 0.75rem;">
                    <input type="checkbox" name="is_main" id="edit_is_main" value="1" class="w-5 h-5 text-indigo-600 border-2 border-gray-300 rounded accent-indigo-600" style="width: 1.25rem; height: 1.25rem; cursor: pointer;">
                    <label for="edit_is_main" class="text-xs font-black text-indigo-900 cursor-pointer" style="font-size: 0.75rem; font-weight: 900; color: #1e3a8a; cursor: pointer;">
                        Designate as Main Branch (Headquarters)
                    </label>
                </div>

                <div class="p-3 bg-gray-50 rounded-xl border border-gray-200 flex items-center space-x-3" style="padding: 0.75rem; background-color: #f9fafb; border-radius: 0.75rem; border: 1px solid #e5e7eb; display: flex; align-items: center; gap: 0.75rem;">
                    <input type="checkbox" name="is_active" id="edit_is_active" value="1" class="w-5 h-5 text-indigo-600 border-2 border-gray-300 rounded accent-indigo-600" style="width: 1.25rem; height: 1.25rem; cursor: pointer;">
                    <label for="edit_is_active" class="text-xs font-bold text-gray-800 cursor-pointer" style="font-size: 0.75rem; font-weight: 700; color: #1f2937; cursor: pointer;">
                        Branch Active Status
                    </label>
                </div>
            </div>

            <div class="pt-4 flex items-center justify-end space-x-3" style="padding-top: 1rem; display: flex; align-items: center; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" onclick="closeEditBranchModal()" class="px-5 py-3 bg-gray-100 text-gray-700 font-bold rounded-xl text-xs hover:bg-gray-200 transition-colors" style="padding: 0.75rem 1.25rem; background-color: #f3f4f6; color: #374151; font-weight: 700; border-radius: 0.75rem; font-size: 0.75rem; border: none; cursor: pointer;">Cancel</button>
                <button type="submit" class="px-7 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-black rounded-xl text-xs shadow-lg transform active:scale-95 transition-all" style="padding: 0.75rem 1.75rem; background-color: #4f46e5; color: #ffffff; font-weight: 900; border-radius: 0.75rem; font-size: 0.75rem; border: none; cursor: pointer; box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.4);">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddBranchModal() {
        const modal = document.getElementById('addBranchModal');
        modal.style.display = 'flex';
    }
    function closeAddBranchModal() {
        const modal = document.getElementById('addBranchModal');
        modal.style.display = 'none';
    }
    function openEditBranchModal(branch) {
        document.getElementById('editBranchForm').action = "/branches/" + branch.id;
        document.getElementById('edit_branch_name').value = branch.name;
        document.getElementById('edit_branch_address').value = branch.address || '';
        document.getElementById('edit_branch_phone').value = branch.phone || '';
        document.getElementById('edit_is_main').checked = !!branch.is_main;
        document.getElementById('edit_is_active').checked = !!branch.is_active;
        const modal = document.getElementById('editBranchModal');
        modal.style.display = 'flex';
    }
    function closeEditBranchModal() {
        const modal = document.getElementById('editBranchModal');
        modal.style.display = 'none';
    }
</script>
@endsection
