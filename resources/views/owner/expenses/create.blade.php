@extends('layouts.app')
@section('title','Record Expense')
@section('page-title','Record Expense')
@section('content')
@if(session('success'))<div class="bg-green-50 border border-green-200 text-green-800 p-3 rounded mb-4">{{ session('success') }}</div>@endif
<div class="card p-6">
  <form id="expenseForm" method="POST" action="{{ route('expenses.store') }}" class="space-y-4">@csrf
    <div><label>Spent By</label><input name="spent_by" class="w-full border rounded p-2" value="{{ old('spent_by', optional(auth()->user())->name) }}" required></div>
    <div><label>Purpose</label><input name="purpose" class="w-full border rounded p-2" required></div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div><label>Amount (UGX)</label><input name="amount" type="number" step="0.01" class="w-full border rounded p-2" required></div>
      <div><label>Date Spent</label><input name="date_spent" type="date" class="w-full border rounded p-2" value="{{ now()->toDateString() }}" required></div>
    </div>
    <div><label>Notes</label><textarea name="notes" class="w-full border rounded p-2" rows="3"></textarea></div>
    <button type="submit" id="submitBtn" class="px-4 py-2 bg-indigo-600 text-white rounded flex items-center justify-center">
        <svg id="btnSpinner" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span id="btnText">Save</span>
    </button>
  </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('expenseForm');
    if (form) {
        form.addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            const spinner = document.getElementById('btnSpinner');
            const text = document.getElementById('btnText');
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
@endsection