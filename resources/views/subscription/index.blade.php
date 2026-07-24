@extends('layouts.app')

@section('title', 'My Subscription')
@section('page-title', 'Subscription & Billing')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6 space-y-8">

  {{-- Flash messages --}}
  @if(session('success'))
    <div class="flex items-start gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl px-5 py-4">
      <i class="fas fa-check-circle mt-0.5 text-emerald-500"></i>
      <div>{{ session('success') }}</div>
    </div>
  @endif
  @if(session('error'))
    <div class="flex items-start gap-3 bg-red-50 border border-red-200 text-red-800 rounded-xl px-5 py-4">
      <i class="fas fa-exclamation-circle mt-0.5 text-red-500"></i>
      <div>{{ session('error') }}</div>
    </div>
  @endif
  @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl px-5 py-4">
      <ul class="list-disc list-inside text-sm space-y-1">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
      </ul>
    </div>
  @endif

  {{-- ── Current Plan Card ──────────────────────────────── --}}
  <div class="bg-gradient-to-br from-indigo-600 to-indigo-800 rounded-2xl p-6 text-white shadow-xl">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="text-indigo-200 text-sm font-semibold uppercase tracking-widest mb-1">Current Plan</div>
        <h2 class="text-3xl font-extrabold capitalize">
          {{ $business->subscription_plan ?? 'Free Trial' }}
        </h2>
        @if($business->subscription_expires_at)
          @php $expires = \Carbon\Carbon::parse($business->subscription_expires_at); @endphp
          @if($expires->isPast())
            <div class="mt-2 inline-flex items-center gap-2 bg-red-500/30 text-red-100 px-3 py-1 rounded-full text-sm font-semibold">
              <i class="fas fa-exclamation-triangle"></i> Expired on {{ $expires->format('M d, Y') }}
            </div>
          @else
            <div class="mt-2 inline-flex items-center gap-2 bg-white/20 px-3 py-1 rounded-full text-sm font-semibold">
              <i class="fas fa-calendar-alt"></i> Expires {{ $expires->format('M d, Y') }} · {{ $expires->diffForHumans() }}
            </div>
          @endif
        @else
          <div class="mt-2 inline-flex items-center gap-2 bg-white/20 px-3 py-1 rounded-full text-sm font-semibold">
            <i class="fas fa-infinity"></i> No expiry set
          </div>
        @endif
      </div>
      <div class="text-right">
        @if($business->hasFeature('pos'))
          <div class="inline-flex items-center gap-2 bg-emerald-500 px-4 py-2 rounded-full text-sm font-bold">
            <i class="fas fa-check-circle"></i> Active
          </div>
        @else
          <div class="inline-flex items-center gap-2 bg-red-500 px-4 py-2 rounded-full text-sm font-bold">
            <i class="fas fa-lock"></i> Limited Access
          </div>
        @endif
      </div>
    </div>
  </div>

  {{-- ── Available Packages ──────────────────────────────── --}}
  @if($packages->count())
  <div>
    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-4">Available Packages</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      @foreach($packages as $pkg)
      <div class="bg-white dark:bg-gray-800 border {{ $business->subscription_plan === $pkg->slug ? 'border-indigo-500 ring-2 ring-indigo-400' : 'border-gray-200 dark:border-gray-700' }} rounded-xl p-5 shadow-sm relative">
        @if($business->subscription_plan === $pkg->slug)
          <div class="absolute top-3 right-3 bg-indigo-100 text-indigo-700 text-xs font-bold px-2 py-0.5 rounded-full">Current</div>
        @endif
        <div class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest mb-1">{{ $pkg->name }}</div>
        <div class="text-2xl font-extrabold text-gray-900 dark:text-white">UGX {{ number_format($pkg->price) }}<span class="text-sm font-normal text-gray-500"> / {{ $pkg->billing_cycle_days }} days</span></div>
        @if($pkg->description)
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">{{ $pkg->description }}</p>
        @endif
        @if(!empty($pkg->features))
        <ul class="mt-3 space-y-1">
          @foreach($pkg->features as $feat)
          <li class="text-sm text-gray-600 dark:text-gray-300 flex items-center gap-2">
            <i class="fas fa-check text-emerald-500 text-xs"></i> {{ ucfirst($feat) }}
          </li>
          @endforeach
        </ul>
        @endif
      </div>
      @endforeach
    </div>
  </div>
  @endif

  {{-- ── Payment Instructions ────────────────────────────── --}}
  <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-2xl p-6">
    <h3 class="font-bold text-amber-800 dark:text-amber-200 flex items-center gap-2 mb-4 text-base">
      <i class="fas fa-info-circle"></i> Payment Instructions
    </h3>
    <div class="grid sm:grid-cols-2 gap-4">
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-amber-100 dark:border-amber-700">
        <div class="flex items-center gap-3 mb-2">
          <div class="w-9 h-9 bg-green-100 rounded-full flex items-center justify-center">
            <i class="fas fa-mobile-alt text-green-600"></i>
          </div>
          <div class="font-bold text-gray-800 dark:text-gray-100 text-sm">Mobile Money</div>
        </div>
        <div class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-wide">0978 732 0647</div>
        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Rebecca Sarah Kasangirwe</div>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-amber-100 dark:border-amber-700">
        <div class="flex items-center gap-3 mb-2">
          <div class="w-9 h-9 bg-blue-100 rounded-full flex items-center justify-center">
            <i class="fas fa-university text-blue-600"></i>
          </div>
          <div class="font-bold text-gray-800 dark:text-gray-100 text-sm">Centenary Bank</div>
        </div>
        <div class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-wide">3204796984</div>
        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">MATHEW AMANYIRE</div>
      </div>
    </div>
    <p class="text-xs text-amber-700 dark:text-amber-300 mt-3">
      <i class="fas fa-exclamation-triangle mr-1"></i>
      After making payment, fill the form below and attach a screenshot or photo of your payment confirmation. Your subscription will be activated once the admin approves your payment.
    </p>
  </div>

  {{-- ── Submit Payment Form ──────────────────────────────── --}}
  <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-3">
      <div class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900 rounded-lg flex items-center justify-center">
        <i class="fas fa-paper-plane text-indigo-600 dark:text-indigo-400 text-sm"></i>
      </div>
      <div>
        <h3 class="font-bold text-gray-800 dark:text-gray-100">Submit Payment</h3>
        <p class="text-xs text-gray-500 dark:text-gray-400">Fill this form after making your payment</p>
      </div>
    </div>
    <div class="p-6">
      <form method="POST" action="{{ route('subscription.pay') }}" enctype="multipart/form-data" class="space-y-5">
        @csrf
        <div class="grid sm:grid-cols-2 gap-5">
          <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Package You're Paying For *</label>
            <select name="package_slug" required
              class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
              <option value="">Select package…</option>
              @foreach($packages as $pkg)
                <option value="{{ $pkg->slug }}" @selected(old('package_slug') == $pkg->slug || $business->subscription_plan == $pkg->slug)>
                  {{ $pkg->name }} — UGX {{ number_format($pkg->price) }}
                </option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Amount Paid (UGX) *</label>
            <input type="number" name="amount" min="0" step="100" placeholder="e.g. 50000"
              value="{{ old('amount') }}" required
              class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
          </div>
        </div>
        <div class="grid sm:grid-cols-2 gap-5">
          <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Payment Method *</label>
            <select name="payment_method" required
              class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
              <option value="">Select…</option>
              <option value="Mobile Money" @selected(old('payment_method')=='Mobile Money')>Mobile Money (MTN/Airtel)</option>
              <option value="Bank Transfer" @selected(old('payment_method')=='Bank Transfer')>Bank Transfer (Centenary)</option>
              <option value="Cash" @selected(old('payment_method')=='Cash')>Cash</option>
              <option value="Other" @selected(old('payment_method')=='Other')>Other</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Transaction / Reference No.</label>
            <input type="text" name="reference" placeholder="e.g. QK12345678"
              value="{{ old('reference') }}"
              class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
          </div>
        </div>
        <div class="grid sm:grid-cols-2 gap-5">
          <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Period Start</label>
            <input type="date" name="period_start" value="{{ old('period_start', now()->toDateString()) }}"
              class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Period End</label>
            <input type="date" name="period_end" value="{{ old('period_end', now()->addMonth()->toDateString()) }}"
              class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
          </div>
        </div>
        {{-- Proof of payment upload --}}
        <div>
          <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
            Proof of Payment <span class="text-gray-400 font-normal">(screenshot or photo)</span>
          </label>
          <div id="dropzone"
            class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-6 text-center cursor-pointer hover:border-indigo-400 transition-colors"
            onclick="document.getElementById('proofFile').click()">
            <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-3 block"></i>
            <p class="text-sm text-gray-600 dark:text-gray-400">Click to upload or drag and drop</p>
            <p class="text-xs text-gray-400 mt-1">JPG, PNG, GIF or WEBP — max 5 MB</p>
            <div id="fileName" class="mt-2 text-sm text-indigo-600 dark:text-indigo-400 font-semibold hidden"></div>
            <img id="previewImg" class="mx-auto mt-3 max-h-40 rounded-lg object-cover hidden" alt="Preview">
          </div>
          <input type="file" id="proofFile" name="proof_image" accept="image/*" class="hidden"
            onchange="handleFileSelect(this)">
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Additional Notes</label>
          <textarea name="notes" rows="2" placeholder="Any extra information for the admin…"
            class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none">{{ old('notes') }}</textarea>
        </div>
        <div class="flex justify-end">
          <button type="submit"
            class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow transition-all">
            <i class="fas fa-paper-plane"></i> Submit Payment Request
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- ── Payment History ──────────────────────────────────── --}}
  <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
      <h3 class="font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
        <i class="fas fa-history text-indigo-500"></i> Payment History
      </h3>
    </div>
    @if($history->count())
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-700/50">
          <tr>
            <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
            <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Package</th>
            <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</th>
            <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Method</th>
            <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ref</th>
            <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Period</th>
            <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
            <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Proof</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
          @foreach($history as $p)
          <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition">
            <td class="px-5 py-4 text-gray-600 dark:text-gray-300 whitespace-nowrap">{{ $p->created_at->format('M d, Y') }}</td>
            <td class="px-5 py-4 font-semibold text-indigo-600 dark:text-indigo-400 capitalize">{{ $p->package_slug ?? '—' }}</td>
            <td class="px-5 py-4 font-bold text-gray-900 dark:text-white">{{ $p->currency }} {{ number_format($p->amount) }}</td>
            <td class="px-5 py-4 text-gray-500 dark:text-gray-400">{{ $p->payment_method ?? '—' }}</td>
            <td class="px-5 py-4 text-gray-500 dark:text-gray-400 font-mono text-xs">{{ $p->reference ?? '—' }}</td>
            <td class="px-5 py-4 text-gray-500 dark:text-gray-400 text-xs whitespace-nowrap">
              @if($p->period_start && $p->period_end)
                {{ $p->period_start->format('M d') }} – {{ $p->period_end->format('M d, Y') }}
              @else —
              @endif
            </td>
            <td class="px-5 py-4">
              @php
                $cls = match($p->status) {
                  'paid'      => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                  'pending'   => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                  'failed'    => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                  'cancelled' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                  default     => 'bg-blue-100 text-blue-700',
                };
              @endphp
              <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold {{ $cls }}">
                @if($p->status === 'paid') <i class="fas fa-check-circle"></i>
                @elseif($p->status === 'pending') <i class="fas fa-clock"></i>
                @elseif($p->status === 'cancelled') <i class="fas fa-times-circle"></i>
                @else <i class="fas fa-exclamation-circle"></i>
                @endif
                {{ ucfirst($p->status) }}
              </span>
              @if($p->status === 'cancelled' && $p->rejection_reason)
              <div class="text-xs text-red-500 mt-1">{{ $p->rejection_reason }}</div>
              @endif
            </td>
            <td class="px-5 py-4">
              @if($p->proof_image)
                <a href="{{ asset('storage/'.$p->proof_image) }}" target="_blank"
                  class="inline-flex items-center gap-1 text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                  <i class="fas fa-image"></i> View
                </a>
              @else
                <span class="text-gray-400 text-xs">—</span>
              @endif
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @if($history->hasPages())
    <div class="px-6 py-3 border-t border-gray-100 dark:border-gray-700">{{ $history->links() }}</div>
    @endif
    @else
    <div class="py-14 text-center text-gray-400">
      <i class="fas fa-receipt text-4xl block mb-3 opacity-30"></i>
      <p>No payment records yet.</p>
    </div>
    @endif
  </div>

</div>

<script>
function handleFileSelect(input) {
  const file = input.files[0];
  if (!file) return;
  const nameEl = document.getElementById('fileName');
  const preview = document.getElementById('previewImg');
  nameEl.textContent = file.name;
  nameEl.classList.remove('hidden');
  const reader = new FileReader();
  reader.onload = e => {
    preview.src = e.target.result;
    preview.classList.remove('hidden');
  };
  reader.readAsDataURL(file);
}
// Drag and drop
const dz = document.getElementById('dropzone');
dz.addEventListener('dragover', e => { e.preventDefault(); dz.classList.add('border-indigo-400'); });
dz.addEventListener('dragleave', () => dz.classList.remove('border-indigo-400'));
dz.addEventListener('drop', e => {
  e.preventDefault();
  dz.classList.remove('border-indigo-400');
  const file = e.dataTransfer.files[0];
  if (file && file.type.startsWith('image/')) {
    const input = document.getElementById('proofFile');
    const dt = new DataTransfer();
    dt.items.add(file);
    input.files = dt.files;
    handleFileSelect(input);
  }
});
</script>
@endsection
