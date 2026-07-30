@extends('layouts.cashier-layout')
@section('title','Record Expense')
@section('page-title','Record Expense')

@section('content')
  @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 p-3 rounded mb-4">{{ session('success') }}</div>
  @endif

  <div class="card p-6">
    <form id="cashierExpenseForm" method="POST" action="{{ route('cashier.expenses.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      @csrf
      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700">Spent By</label>
        <input name="spent_by" type="text" class="mt-1 w-full border rounded p-2" value="{{ old('spent_by', optional(auth()->user())->name) }}" required>
        @error('spent_by')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Purpose</label>
        <input name="purpose" type="text" class="mt-1 w-full border rounded p-2" placeholder="e.g., Transport" value="{{ old('purpose') }}" required>
        @error('purpose')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Amount (UGX)</label>
        <input name="amount" type="number" step="0.01" class="mt-1 w-full border rounded p-2" value="{{ old('amount') }}" required>
        @error('amount')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Date Spent</label>
        <input name="date_spent" type="date" class="mt-1 w-full border rounded p-2" value="{{ old('date_spent', now()->toDateString()) }}" required>
        @error('date_spent')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
      </div>
      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700">Notes (optional)</label>
        <textarea name="notes" class="mt-1 w-full border rounded p-2" rows="3">{{ old('notes') }}</textarea>
        @error('notes')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
      </div>
 
      <div class="md:col-span-2 flex items-center justify-end gap-2">
        <button type="submit" id="cashierSubmitBtn" class="px-4 py-2 bg-indigo-600 text-white rounded flex items-center justify-center">
            <svg id="cashierSpinner" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span id="cashierBtnText">Save Expense</span>
        </button>
        <a href="{{ route('cashier.expenses.my') }}" class="px-4 py-2 bg-gray-100 text-gray-800 rounded">Cancel</a>
      </div>
    </form>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('cashierExpenseForm');
    if (form) {
        form.addEventListener('submit', function() {
            const btn = document.getElementById('cashierSubmitBtn');
            const spinner = document.getElementById('cashierSpinner');
            const text = document.getElementById('cashierBtnText');
            if (btn) {
                btn.style.setProperty('background-color', '#4338ca', 'important');
                btn.style.setProperty('color', '#ffffff', 'important');
                btn.classList.add('pointer-events-none');
                if (spinner) spinner.classList.remove('hidden');
                if (text) text.textContent = 'Saving...';
            }
        });
    }
});
</script>
  </div>
@endsection