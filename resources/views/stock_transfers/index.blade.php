@extends('layouts.app')

@section('title', 'Inter-Branch Stock Transfers - DukaFlow')

@section('content')
<div class="space-y-6">
    <!-- Header Title & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <div>
            <h1 class="text-2xl font-black text-gray-900 tracking-tight flex items-center">
                <i class="fas fa-dolly text-indigo-600 mr-3 text-2xl"></i>
                Inter-Branch Stock Transfers
            </h1>
            <p class="text-sm text-gray-500 font-medium mt-1">
                Transfer inventory between your business branches and keep stock levels synchronized.
            </p>
        </div>
        <button onclick="openTransferModal()" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-md transition-all flex items-center justify-center text-sm transform active:scale-95">
            <i class="fas fa-exchange-alt mr-2"></i> Initiate Stock Transfer
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

    <!-- Stock Transfer History Table -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-lg font-black text-gray-900 flex items-center">
                <i class="fas fa-history text-indigo-600 mr-2"></i> Stock Transfer History
            </h2>
            <span class="text-xs font-bold text-gray-400 uppercase">Total Transfers: {{ $transfers->total() }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-[11px] font-black text-gray-400 uppercase tracking-wider">
                        <th class="py-3.5 px-6">Transfer # & Date</th>
                        <th class="py-3.5 px-4">From Branch</th>
                        <th class="py-3.5 px-4">To Branch</th>
                        <th class="py-3.5 px-4">Items & Quantities</th>
                        <th class="py-3.5 px-4">Initiated By</th>
                        <th class="py-3.5 px-6 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm font-medium">
                    @forelse($transfers as $trf)
                        <tr class="hover:bg-gray-50/80 transition-colors">
                            <td class="py-4 px-6">
                                <div class="font-black text-gray-900">{{ $trf->transfer_number }}</div>
                                <div class="text-xs text-gray-400">{{ $trf->transferred_at ? $trf->transferred_at->format('M d, Y h:i A') : $trf->created_at->format('M d, Y') }}</div>
                            </td>
                            <td class="py-4 px-4 font-bold text-gray-800">
                                <span class="px-2.5 py-1 bg-red-50 text-red-700 rounded-lg border border-red-100 text-xs">
                                    <i class="fas fa-arrow-up text-red-500 mr-1"></i> {{ optional($trf->fromLocation)->name ?? 'Unknown' }}
                                </span>
                            </td>
                            <td class="py-4 px-4 font-bold text-gray-800">
                                <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 rounded-lg border border-emerald-100 text-xs">
                                    <i class="fas fa-arrow-down text-emerald-500 mr-1"></i> {{ optional($trf->toLocation)->name ?? 'Unknown' }}
                                </span>
                            </td>
                            <td class="py-4 px-4 text-xs font-semibold text-gray-700">
                                <ul class="space-y-1">
                                    @foreach($trf->items as $item)
                                        <li class="flex items-center space-x-1">
                                            <i class="fas fa-box text-indigo-500 text-[10px]"></i>
                                            <span class="font-black text-gray-900">{{ optional($item->product)->name }}:</span>
                                            <span class="text-indigo-700 font-extrabold">{{ number_format($item->quantity) }} units</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </td>
                            <td class="py-4 px-4 text-xs font-semibold text-gray-700">
                                <i class="fas fa-user-circle text-gray-400 mr-1"></i> {{ optional($trf->createdBy)->name ?? 'System' }}
                            </td>
                            <td class="py-4 px-6 text-right">
                                <span class="text-[10px] font-black uppercase px-2.5 py-1 bg-emerald-100 text-emerald-800 rounded-full border border-emerald-200">
                                    Completed
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-gray-500 font-semibold">
                                No stock transfers recorded yet. Click <strong>Initiate Stock Transfer</strong> above to send inventory between branches.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transfers->hasPages())
            <div class="p-4 border-t border-gray-100">
                {{ $transfers->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Stock Transfer Modal -->
<div id="transferModal" class="fixed inset-0 z-[99999] hidden items-center justify-center p-4 sm:p-6 overflow-y-auto" style="position: fixed; inset: 0; z-index: 99999; background-color: rgba(15, 23, 42, 0.75); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; padding: 1rem;">
    <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full p-6 sm:p-8 relative my-8" style="background-color: #ffffff; border-radius: 1.5rem; max-width: 42rem; width: 100%; padding: 1.75rem; position: relative; border: 2px solid #4f46e5; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); max-height: 90vh; overflow-y: auto;">
        <button type="button" onclick="closeTransferModal()" class="absolute top-5 right-5 text-gray-400 hover:text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-full w-9 h-9 flex items-center justify-center transition-all" style="position: absolute; top: 1.25rem; right: 1.25rem; width: 2.25rem; height: 2.25rem; border-radius: 9999px; background-color: #f3f4f6; color: #9ca3af; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-times text-lg"></i>
        </button>

        <div class="text-center mb-6" style="text-align: center; margin-bottom: 1.5rem;">
            <div class="w-14 h-14 bg-indigo-600 text-white rounded-2xl flex items-center justify-center text-2xl mx-auto mb-3 shadow-md" style="width: 3.5rem; height: 3.5rem; background-color: #4f46e5; color: #ffffff; border-radius: 1rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.75rem auto; font-size: 1.5rem;">
                <i class="fas fa-exchange-alt"></i>
            </div>
            <h2 class="text-2xl font-black text-gray-900 tracking-tight" style="font-size: 1.5rem; font-weight: 900; color: #111827; margin: 0;">Initiate Inter-Branch Stock Transfer</h2>
            <p class="text-xs text-gray-500 mt-1 font-semibold" style="font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem; font-weight: 600;">Select source and destination branches, then add products to transfer.</p>
        </div>

        <form action="{{ route('stock-transfers.store') }}" method="POST" class="space-y-4" style="display: flex; flex-direction: column; gap: 1rem;">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-black text-gray-800 uppercase tracking-wider mb-1.5" style="display: block; font-size: 0.75rem; font-weight: 900; color: #1f2937; text-transform: uppercase; margin-bottom: 0.375rem;">
                        <i class="fas fa-building text-red-500 mr-1" style="color: #ef4444;"></i> From Branch (Source) <span class="text-red-500" style="color: #ef4444;">*</span>
                    </label>
                    <select name="from_location_id" required class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl text-sm font-bold text-gray-900 focus:border-indigo-600 focus:ring-0 focus:outline-none" style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #d1d5db; border-radius: 0.75rem; font-size: 0.875rem; font-weight: 700; color: #111827; box-sizing: border-box;">
                        <option value="">-- Select Source Branch --</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}">{{ $loc->name }} {{ $loc->is_main ? '(Main HQ)' : '' }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-black text-gray-800 uppercase tracking-wider mb-1.5" style="display: block; font-size: 0.75rem; font-weight: 900; color: #1f2937; text-transform: uppercase; margin-bottom: 0.375rem;">
                        <i class="fas fa-building text-emerald-500 mr-1" style="color: #10b981;"></i> To Branch (Destination) <span class="text-red-500" style="color: #ef4444;">*</span>
                    </label>
                    <select name="to_location_id" required class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl text-sm font-bold text-gray-900 focus:border-indigo-600 focus:ring-0 focus:outline-none" style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #d1d5db; border-radius: 0.75rem; font-size: 0.875rem; font-weight: 700; color: #111827; box-sizing: border-box;">
                        <option value="">-- Select Destination Branch --</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}">{{ $loc->name }} {{ $loc->is_main ? '(Main HQ)' : '' }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="border-t-2 border-b-2 border-gray-100 py-4 my-2" style="border-top: 2px solid #f3f4f6; border-bottom: 2px solid #f3f4f6; padding-top: 1rem; padding-bottom: 1rem; margin-top: 0.5rem; margin-bottom: 0.5rem;">
                <div class="flex items-center justify-between mb-3" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                    <label class="text-xs font-black text-gray-800 uppercase tracking-wider" style="font-size: 0.75rem; font-weight: 900; color: #1f2937; text-transform: uppercase;">
                        <i class="fas fa-boxes text-indigo-600 mr-1" style="color: #4f46e5;"></i> Products to Transfer <span class="text-red-500" style="color: #ef4444;">*</span>
                    </label>
                    <button type="button" onclick="addTransferRow()" class="text-xs font-black text-indigo-600 hover:text-indigo-800 flex items-center bg-indigo-50 px-3 py-1.5 rounded-lg border border-indigo-100" style="font-size: 0.75rem; font-weight: 900; color: #4f46e5; background-color: #eff6ff; border: 1px solid #dbeafe; border-radius: 0.5rem; padding: 0.375rem 0.75rem; cursor: pointer;">
                        <i class="fas fa-plus-circle mr-1"></i> Add Another Product
                    </button>
                </div>

                <div id="transferRows" class="space-y-3" style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <div class="flex items-center space-x-3 transfer-row" style="display: flex; align-items: center; gap: 0.75rem;">
                        <div class="flex-1" style="flex: 1;">
                            <select name="products[0][id]" required class="w-full px-3.5 py-2.5 border-2 border-gray-300 rounded-xl text-xs font-bold focus:border-indigo-600 focus:outline-none" style="width: 100%; padding: 0.625rem 0.875rem; border: 2px solid #d1d5db; border-radius: 0.75rem; font-size: 0.75rem; font-weight: 700; box-sizing: border-box;">
                                <option value="">-- Select Product --</option>
                                @foreach($products as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }} (Available: {{ number_format($p->quantity) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="w-32" style="width: 8rem;">
                            <input type="number" step="0.01" min="0.01" name="products[0][qty]" required placeholder="Qty" class="w-full px-3.5 py-2.5 border-2 border-gray-300 rounded-xl text-xs font-black focus:border-indigo-600 focus:outline-none" style="width: 100%; padding: 0.625rem 0.875rem; border: 2px solid #d1d5db; border-radius: 0.75rem; font-size: 0.75rem; font-weight: 900; box-sizing: border-box;">
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-black text-gray-800 uppercase tracking-wider mb-1.5" style="display: block; font-size: 0.75rem; font-weight: 900; color: #1f2937; text-transform: uppercase; margin-bottom: 0.375rem;">
                    <i class="fas fa-sticky-note text-indigo-600 mr-1" style="color: #4f46e5;"></i> Transfer Notes / Remarks
                </label>
                <textarea name="notes" rows="2" class="w-full px-4 py-2.5 border-2 border-gray-300 rounded-xl text-xs font-medium text-gray-900 focus:border-indigo-600 focus:ring-0 focus:outline-none" style="width: 100%; padding: 0.625rem 1rem; border: 2px solid #d1d5db; border-radius: 0.75rem; font-size: 0.75rem; font-weight: 500; color: #111827; box-sizing: border-box;" placeholder="Reason or details for stock transfer..."></textarea>
            </div>

            <div class="pt-4 flex items-center justify-end space-x-3" style="padding-top: 1rem; display: flex; align-items: center; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" onclick="closeTransferModal()" class="px-5 py-3 bg-gray-100 text-gray-700 font-bold rounded-xl text-xs hover:bg-gray-200 transition-colors" style="padding: 0.75rem 1.25rem; background-color: #f3f4f6; color: #374151; font-weight: 700; border-radius: 0.75rem; font-size: 0.75rem; border: none; cursor: pointer;">Cancel</button>
                <button type="submit" class="px-7 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-black rounded-xl text-xs shadow-lg transform active:scale-95 transition-all" style="padding: 0.75rem 1.75rem; background-color: #4f46e5; color: #ffffff; font-weight: 900; border-radius: 0.75rem; font-size: 0.75rem; border: none; cursor: pointer; box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.4);">Complete Transfer</button>
            </div>
        </form>
    </div>
</div>

<script>
    let transferRowIdx = 1;
    function openTransferModal() {
        const modal = document.getElementById('transferModal');
        modal.style.display = 'flex';
    }
    function closeTransferModal() {
        const modal = document.getElementById('transferModal');
        modal.style.display = 'none';
    }
    function addTransferRow() {
        const container = document.getElementById('transferRows');
        const firstSelectHTML = container.querySelector('select').innerHTML;
        const row = document.createElement('div');
        row.className = 'flex items-center space-x-3 transfer-row';
        row.style.display = 'flex';
        row.style.alignItems = 'center';
        row.style.gap = '0.75rem';
        row.innerHTML = `
            <div class="flex-1" style="flex: 1;">
                <select name="products[${transferRowIdx}][id]" required class="w-full px-3.5 py-2.5 border-2 border-gray-300 rounded-xl text-xs font-bold focus:border-indigo-600 focus:outline-none" style="width: 100%; padding: 0.625rem 0.875rem; border: 2px solid #d1d5db; border-radius: 0.75rem; font-size: 0.75rem; font-weight: 700; box-sizing: border-box;">
                    ${firstSelectHTML}
                </select>
            </div>
            <div class="w-32" style="width: 8rem;">
                <input type="number" step="0.01" min="0.01" name="products[${transferRowIdx}][qty]" required placeholder="Qty" class="w-full px-3.5 py-2.5 border-2 border-gray-300 rounded-xl text-xs font-black focus:border-indigo-600 focus:outline-none" style="width: 100%; padding: 0.625rem 0.875rem; border: 2px solid #d1d5db; border-radius: 0.75rem; font-size: 0.75rem; font-weight: 900; box-sizing: border-box;">
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700 p-1" style="color: #ef4444; border: none; background: none; cursor: pointer;"><i class="fas fa-trash-alt"></i></button>
        `;
        container.appendChild(row);
        transferRowIdx++;
    }
</script>
@endsection
