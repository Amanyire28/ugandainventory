@extends('layouts.app')

@section('title', 'My Subscription')
@section('page-title', 'Subscription & Billing')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6 space-y-8" style="color: #0f172a;">

  {{-- ── Flash Notifications ─────────────────────────────────── --}}
  @if(session('success'))
    <div style="background-color: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; border-radius: 12px; padding: 16px; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 12px;">
      <i class="fas fa-check-circle" style="color: #059669; font-size: 18px;"></i>
      <div>{{ session('success') }}</div>
    </div>
  @endif

  @if(session('error'))
    <div style="background-color: #fef2f2; border: 1px solid #fecaca; color: #991b1b; border-radius: 12px; padding: 16px; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 12px;">
      <i class="fas fa-exclamation-circle" style="color: #dc2626; font-size: 18px;"></i>
      <div>{{ session('error') }}</div>
    </div>
  @endif

  @if($errors->any())
    <div style="background-color: #fef2f2; border: 1px solid #fecaca; color: #991b1b; border-radius: 12px; padding: 16px;">
      <div style="font-weight: 800; font-size: 14px; margin-bottom: 6px; display: flex; align-items: center; gap: 8px;">
        <i class="fas fa-triangle-exclamation" style="color: #dc2626;"></i> Please check the form errors below:
      </div>
      <ul style="list-style-type: disc; padding-left: 20px; font-size: 14px;">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
      </ul>
    </div>
  @endif

  {{-- ── 1. Formal Dark Slate Header Card with Live Countdown ────── --}}
  <div style="background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); color: #ffffff; padding: 32px; border-radius: 16px; border: 1px solid #1e293b; box-shadow: 0 10px 25px rgba(0,0,0,0.2); position: relative; overflow: hidden;">
    <div style="display: flex; flex-direction: row; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 24px; position: relative; z-index: 10;">
      
      <!-- Left: Plan Information -->
      <div style="display: flex; flex-direction: column; gap: 12px; max-width: 600px;">
        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
          <span style="background: rgba(99, 102, 241, 0.25); color: #c7d2fe; padding: 4px 12px; border-radius: 9999px; font-weight: 700; font-size: 12px; border: 1px solid rgba(199, 210, 254, 0.3); text-transform: uppercase; letter-spacing: 0.05em;">
            <i class="fas fa-building" style="color: #a5b4fc; margin-right: 4px;"></i> {{ $business->name }}
          </span>
          <span style="color: #cbd5e1; font-size: 12px; font-weight: 600;">Official Business Account</span>
        </div>

        <div>
          <span style="color: #94a3b8; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; display: block; margin-bottom: 4px;">
            Current Subscription Plan
          </span>
          <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
            <h1 style="color: #ffffff; font-size: 32px; font-weight: 900; margin: 0; text-transform: capitalize; letter-spacing: -0.02em;">
              {{ $business->subscription_plan ?? 'Free Trial' }}
            </h1>
            @if($business->isSubscriptionActive())
              <span style="background: rgba(16, 185, 129, 0.25); color: #6ee7b7; padding: 4px 14px; border-radius: 9999px; font-weight: 800; font-size: 12px; border: 1px solid rgba(110, 231, 183, 0.4); display: inline-flex; align-items: center; gap: 6px;">
                <span style="width: 8px; height: 8px; border-radius: 50%; background-color: #34d399;"></span> Active
              </span>
            @else
              <span style="background: rgba(239, 68, 68, 0.25); color: #fca5a5; padding: 4px 14px; border-radius: 9999px; font-weight: 800; font-size: 12px; border: 1px solid rgba(252, 165, 165, 0.4); display: inline-flex; align-items: center; gap: 6px;">
                <span style="width: 8px; height: 8px; border-radius: 50%; background-color: #f87171;"></span> Expired
              </span>
            @endif
          </div>
        </div>

        <!-- Renewal Information -->
        <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap; margin-top: 4px;">
          @if($business->subscription_expires_at)
            @php $expires = \Carbon\Carbon::parse($business->subscription_expires_at); @endphp
            <div style="background: rgba(30, 41, 59, 0.9); color: #f1f5f9; padding: 8px 16px; border-radius: 8px; border: 1px solid #334155; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
              <i class="fas fa-calendar-check" style="color: #818cf8;"></i>
              <span>Next Renewal Due: <strong style="color: #ffffff; font-weight: 800;">{{ $expires->format('M d, Y') }}</strong></span>
            </div>
          @else
            <div style="background: rgba(30, 41, 59, 0.9); color: #f1f5f9; padding: 8px 16px; border-radius: 8px; border: 1px solid #334155; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
              <i class="fas fa-infinity" style="color: #818cf8;"></i>
              <span style="color: #ffffff; font-weight: 800;">Lifetime Subscription (No Expiry)</span>
            </div>
          @endif
        </div>
      </div>

      <!-- Right: Live Remaining Countdown Timer -->
      @if($business->subscription_expires_at)
        @php
          $expires = \Carbon\Carbon::parse($business->subscription_expires_at);
          $isPast = $expires->isPast();
        @endphp
        <div style="background: rgba(15, 23, 42, 0.95); border: 1px solid #334155; padding: 20px; border-radius: 14px; text-align: center; min-width: 280px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.3);">
          <div style="color: #fbbf24; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 12px; display: flex; align-items: center; justify-content: center; gap: 6px;">
            <i class="fas fa-clock" style="color: #f59e0b;"></i> Remaining Duration
          </div>

          @if($isPast)
            <div style="color: #f87171; font-weight: 900; font-size: 20px; padding: 8px 0;">
              <i class="fas fa-exclamation-circle" style="margin-right: 4px;"></i> Subscription Expired
            </div>
            <div style="color: #94a3b8; font-size: 12px;">Renew below to restore full business access</div>
          @else
            <div id="countdownTimer" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; text-align: center;" data-expiry="{{ $expires->toIso8601String() }}">
              <div style="background: #020617; border: 1px solid #334155; border-radius: 8px; padding: 10px 4px;">
                <span id="countDays" style="color: #ffffff; font-size: 24px; font-weight: 900; display: block; line-height: 1.1;">00</span>
                <span style="color: #cbd5e1; font-size: 10px; font-weight: 700; text-transform: uppercase;">Days</span>
              </div>
              <div style="background: #020617; border: 1px solid #334155; border-radius: 8px; padding: 10px 4px;">
                <span id="countHours" style="color: #ffffff; font-size: 24px; font-weight: 900; display: block; line-height: 1.1;">00</span>
                <span style="color: #cbd5e1; font-size: 10px; font-weight: 700; text-transform: uppercase;">Hours</span>
              </div>
              <div style="background: #020617; border: 1px solid #334155; border-radius: 8px; padding: 10px 4px;">
                <span id="countMins" style="color: #ffffff; font-size: 24px; font-weight: 900; display: block; line-height: 1.1;">00</span>
                <span style="color: #cbd5e1; font-size: 10px; font-weight: 700; text-transform: uppercase;">Mins</span>
              </div>
              <div style="background: #020617; border: 1px solid #334155; border-radius: 8px; padding: 10px 4px;">
                <span id="countSecs" style="color: #34d399; font-size: 24px; font-weight: 900; display: block; line-height: 1.1;">00</span>
                <span style="color: #cbd5e1; font-size: 10px; font-weight: 700; text-transform: uppercase;">Secs</span>
              </div>
            </div>
            <div style="color: #cbd5e1; font-size: 11px; margin-top: 10px; font-weight: 600;">
              <i class="fas fa-shield-halved" style="color: #34d399; margin-right: 4px;"></i> Countdown to next billing cycle
            </div>
          @endif
        </div>
      @endif
    </div>
  </div>

  {{-- ── 2. Formal Subscription Payment & Renewal Form (Collapsible Accordion) ── --}}
  <div id="paymentFormSection" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); overflow: hidden;">
    <div onclick="togglePaymentForm()" style="background: #0f172a; padding: 18px 24px; color: #ffffff; display: flex; align-items: center; justify-content: space-between; cursor: pointer; user-select: none;">
      <div style="display: flex; align-items: center; gap: 12px;">
        <div style="width: 40px; height: 40px; background: rgba(99, 102, 241, 0.25); border: 1px solid rgba(165, 180, 252, 0.4); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #a5b4fc; font-size: 18px;">
          <i class="fas fa-file-invoice-dollar"></i>
        </div>
        <div>
          <h2 style="font-weight: 900; font-size: 18px; margin: 0; color: #ffffff; tracking-tight;">Renew or Subscribe</h2>
          <p style="font-size: 12px; margin: 2px 0 0 0; color: #cbd5e1;">Select subscription plan and duration period</p>
        </div>
      </div>
      <div style="display: flex; align-items: center; gap: 12px;">
        <span style="font-size: 12px; background: rgba(99, 102, 241, 0.25); color: #c7d2fe; padding: 4px 12px; border-radius: 9999px; border: 1px solid rgba(199, 210, 254, 0.3); font-weight: 700;" class="hidden sm:inline-block">
          Official Business Billing
        </span>
        <div style="width: 34px; height: 34px; background: rgba(255,255,255,0.1); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #ffffff;">
          <i id="paymentFormArrow" class="fas fa-chevron-down" style="transition: transform 0.3s ease; font-size: 14px; transform: {{ ($errors->any() || old('package_slug')) ? 'rotate(180deg)' : 'rotate(0deg)' }};"></i>
        </div>
      </div>
    </div>

    <div id="paymentFormBody" style="padding: 24px 32px; display: {{ ($errors->any() || old('package_slug')) ? 'block' : 'none' }}; border-top: 1px solid #e2e8f0;">
      <form method="POST" action="{{ route('subscription.pay') }}" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 24px;">
        @csrf

        <!-- Row 1: Plan Selection & Duration -->
        <div class="grid md:grid-cols-2 gap-6">
          <!-- Package Selector -->
          <div>
            <label style="display: block; font-size: 14px; font-weight: 800; color: #0f172a; margin-bottom: 6px;">
              1. Select Subscription Package <span style="color: #dc2626;">*</span>
            </label>
            <select name="package_slug" id="packageSelect" required
              style="width: 100%; padding: 12px 16px; border: 1px solid #cbd5e1; border-radius: 12px; background: #ffffff; color: #0f172a; font-size: 14px; font-weight: 700; outline: none;">
              <option value="">Choose a plan…</option>
              @foreach($packages as $pkg)
                <option value="{{ $pkg->slug }}" data-price="{{ $pkg->price }}" data-cycle="{{ $pkg->billing_cycle_days }}"
                  @selected(old('package_slug') == $pkg->slug || $business->subscription_plan == $pkg->slug)>
                  {{ $pkg->name }} — UGX {{ number_format($pkg->price) }} / month
                </option>
              @endforeach
            </select>
          </div>

          <!-- Subscription Duration Selector (1 to 6 Months or 1 Year) -->
          <div>
            <label style="display: block; font-size: 14px; font-weight: 800; color: #0f172a; margin-bottom: 6px;">
              2. Select Subscription Period (Months) <span style="color: #dc2626;">*</span>
            </label>
            <select name="duration_months" id="durationSelect" required
              style="width: 100%; padding: 12px 16px; border: 1px solid #cbd5e1; border-radius: 12px; background: #ffffff; color: #0f172a; font-size: 14px; font-weight: 700; outline: none;">
              <option value="1">1 Month</option>
              <option value="2">2 Months</option>
              <option value="3">3 Months (Quarterly)</option>
              <option value="4">4 Months</option>
              <option value="5">5 Months</option>
              <option value="6">6 Months (Half Year)</option>
              <option value="12">12 Months (1 Year - Annual Plan)</option>
            </select>
          </div>
        </div>

        <!-- Row 2: Auto-calculated Amount & Partial Payment Checkbox -->
        <div style="background: #f8fafc; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0; display: flex; flex-direction: column; gap: 16px;">
          <div class="grid md:grid-cols-2 gap-6 items-end">
            <!-- Amount Paid (Autofilled by default) -->
            <div>
              <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                <label style="font-size: 14px; font-weight: 800; color: #0f172a; margin: 0;">
                  Total Subscription Amount (UGX) <span style="color: #dc2626;">*</span>
                </label>
                <span id="autofillBadge" style="font-size: 11px; font-weight: 800; color: #065f46; background: #d1fae5; border: 1px solid #a7f3d0; padding: 2px 10px; border-radius: 9999px;">
                  <i class="fas fa-calculator" style="margin-right: 4px;"></i> Auto-calculated
                </span>
              </div>
              <div style="position: relative;">
                <span style="position: absolute; left: 16px; top: 14px; color: #475569; font-weight: 900; font-size: 14px;">UGX</span>
                <input type="number" name="amount" id="subscriptionAmount" min="0" step="100" placeholder="0"
                  value="{{ old('amount') }}" required readonly
                  style="width: 100%; padding-left: 56px; padding-right: 16px; padding-top: 12px; padding-bottom: 12px; border: 1px solid #cbd5e1; border-radius: 12px; background: #f1f5f9; color: #0f172a; font-weight: 900; font-size: 20px; outline: none; cursor: not-allowed;">
              </div>
            </div>

            <!-- Partial Payment Checkbox toggle -->
            <div style="background: #ffffff; padding: 14px 16px; border-radius: 12px; border: 1px solid #cbd5e1; display: flex; align-items: center; gap: 12px;">
              <input type="checkbox" id="allowPartialPayment" name="is_partial" value="1"
                style="width: 20px; height: 20px; accent-color: #4f46e5; cursor: pointer;">
              <label for="allowPartialPayment" style="font-size: 14px; font-weight: 800; color: #0f172a; cursor: pointer; margin: 0; user-select: none;">
                Tick for Partial Payment
                <span style="display: block; font-size: 12px; font-weight: 600; color: #64748b;">Check this box if you are making a partial payment</span>
              </label>
            </div>
          </div>

          <!-- Automated Date Preview -->
          <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; color: #334155; pt-2; border-top: 1px solid #e2e8f0; padding-top: 10px;">
            <i class="fas fa-calendar-range" style="color: #4f46e5; font-size: 16px;"></i>
            <span>Calculated Coverage Period: <strong id="periodPreviewText" style="color: #4338ca; font-weight: 900;">Select plan & duration</strong></span>
          </div>
        </div>

        <!-- Row 3: Payment Method & Reference -->
        <div class="grid md:grid-cols-2 gap-6">
          <div>
            <label style="display: block; font-size: 14px; font-weight: 800; color: #0f172a; margin-bottom: 6px;">
              Payment Method <span style="color: #dc2626;">*</span>
            </label>
            <select name="payment_method" required
              style="width: 100%; padding: 12px 16px; border: 1px solid #cbd5e1; border-radius: 12px; background: #ffffff; color: #0f172a; font-size: 14px; font-weight: 700; outline: none;">
              <option value="">Select payment channel…</option>
              <option value="Mobile Money" @selected(old('payment_method')=='Mobile Money')>MTN / Airtel Mobile Money (0787320647)</option>
              <option value="Bank Transfer" @selected(old('payment_method')=='Bank Transfer')>Centenary Bank Transfer (3204796984)</option>
              <option value="Cash" @selected(old('payment_method')=='Cash')>Direct Cash Payment</option>
              <option value="Other" @selected(old('payment_method')=='Other')>Other Payment Channel</option>
            </select>
          </div>

          <div>
            <label style="display: block; font-size: 14px; font-weight: 800; color: #0f172a; margin-bottom: 6px;">
              Transaction ID / Reference Number
            </label>
            <input type="text" name="reference" placeholder="e.g. 248901239 or Bank Ref"
              value="{{ old('reference') }}"
              style="width: 100%; padding: 12px 16px; border: 1px solid #cbd5e1; border-radius: 12px; background: #ffffff; color: #0f172a; font-size: 14px; font-weight: 700; outline: none;">
          </div>
        </div>

        <!-- Row 4: Proof Upload -->
        <div>
          <label style="display: block; font-size: 14px; font-weight: 800; color: #0f172a; margin-bottom: 6px;">
            Attach Payment Proof Screenshot / Receipt <span style="color: #64748b; font-weight: 600;">(Optional)</span>
          </label>
          <div id="dropzone"
            style="border: 2px dashed #cbd5e1; border-radius: 14px; padding: 24px; text-align: center; cursor: pointer; background: #f8fafc; transition: all 0.2s ease;"
            onclick="document.getElementById('proofFile').click()">
            <i class="fas fa-cloud-arrow-up" style="font-size: 32px; color: #4f46e5; margin-bottom: 8px; display: block;"></i>
            <p style="font-size: 14px; font-weight: 800; color: #0f172a; margin: 0;">Click to upload or drag & drop payment confirmation</p>
            <p style="font-size: 12px; color: #64748b; margin-top: 4px;">Supports JPG, PNG, WEBP — max 5 MB</p>
            <div id="fileName" style="margin-top: 8px; font-size: 12px; color: #4338ca; font-weight: 800; display: none;"></div>
            <img id="previewImg" style="margin: 12px auto 0 auto; max-height: 160px; border-radius: 12px; display: none;" alt="Preview">
          </div>
          <input type="file" id="proofFile" name="proof_image" accept="image/*" style="display: none;" onchange="handleFileSelect(this)">
        </div>

        <!-- Row 5: Notes & Submit -->
        <div>
          <label style="display: block; font-size: 14px; font-weight: 800; color: #0f172a; margin-bottom: 6px;">Additional Billing Remarks</label>
          <textarea name="notes" rows="2" placeholder="Provide any additional payment details or reference notes for the admin…"
            style="width: 100%; padding: 12px 16px; border: 1px solid #cbd5e1; border-radius: 12px; background: #ffffff; color: #0f172a; font-size: 14px; font-weight: 600; outline: none; resize: none;">{{ old('notes') }}</textarea>
        </div>

        <div style="padding-top: 8px; display: flex; justify-content: flex-end;">
          <button type="submit"
            style="background: #4f46e5; color: #ffffff; padding: 14px 32px; border-radius: 12px; font-weight: 900; font-size: 16px; border: none; cursor: pointer; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3); display: inline-flex; align-items: center; gap: 8px;">
            <i class="fas fa-paper-plane"></i> Submit Subscription Payment
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- ── 3. Official Payment Instructions Card ───────────────────── --}}
  <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 16px; padding: 24px 32px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid #fef3c7;">
      <div style="display: flex; align-items: center; gap: 12px;">
        <div style="width: 40px; height: 40px; background: rgba(245, 158, 11, 0.2); color: #92400e; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 800;">
          <i class="fas fa-credit-card"></i>
        </div>
        <div>
          <h3 style="font-weight: 900; color: #0f172a; font-size: 18px; margin: 0;">Official Payment Accounts</h3>
          <p style="font-size: 12px; color: #78350f; font-weight: 600; margin: 2px 0 0 0;">Use any of the official channels below to send your subscription payment</p>
        </div>
      </div>
    </div>

    <div class="grid md:grid-cols-2 gap-6">
      <!-- Mobile Money Official Box -->
      <div style="background: #ffffff; border-radius: 12px; padding: 20px; border: 1px solid #fde68a; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
          <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 32px; height: 32px; background: #fef3c7; color: #92400e; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 800;">
              <i class="fas fa-mobile-screen-button"></i>
            </div>
            <span style="font-weight: 900; color: #0f172a; font-size: 14px;">MTN / Airtel Mobile Money</span>
          </div>
          <span style="font-size: 11px; font-weight: 900; color: #78350f; background: #fef3c7; padding: 2px 10px; border-radius: 9999px; border: 1px solid #fde68a;">
            Official Line
          </span>
        </div>

        <div style="background: #fffbeb; padding: 14px 16px; border-radius: 10px; border: 1px solid #fef3c7; display: flex; align-items: center; justify-content: space-between;">
          <div>
            <div style="font-size: 24px; font-weight: 900; color: #0f172a; letter-spacing: 0.05em; font-family: monospace;" id="officialPhone">0787320647</div>
            <div style="font-size: 12px; color: #475569; font-weight: 700; margin-top: 2px;">Account Name: <strong style="color: #0f172a;">Rebecca Sarah Kasangirwe</strong></div>
          </div>
          <button type="button" onclick="copyText('0787320647', 'Phone number copied!')"
            style="background: #d97706; color: #ffffff; padding: 8px 14px; border-radius: 8px; font-weight: 800; font-size: 12px; border: none; cursor: pointer; display: flex; align-items: center; gap: 4px;">
            <i class="fas fa-copy"></i> Copy
          </button>
        </div>
      </div>

      <!-- Bank Transfer Box -->
      <div style="background: #ffffff; border-radius: 12px; padding: 20px; border: 1px solid #fde68a; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
          <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 32px; height: 32px; background: #dbeafe; color: #1e40af; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 800;">
              <i class="fas fa-building-columns"></i>
            </div>
            <span style="font-weight: 900; color: #0f172a; font-size: 14px;">Centenary Bank</span>
          </div>
          <span style="font-size: 11px; font-weight: 900; color: #1e40af; background: #dbeafe; padding: 2px 10px; border-radius: 9999px; border: 1px solid #bfdbfe;">
            Bank Account
          </span>
        </div>

        <div style="background: #eff6ff; padding: 14px 16px; border-radius: 10px; border: 1px solid #dbeafe; display: flex; align-items: center; justify-content: space-between;">
          <div>
            <div style="font-size: 24px; font-weight: 900; color: #0f172a; letter-spacing: 0.05em; font-family: monospace;">3204796984</div>
            <div style="font-size: 12px; color: #475569; font-weight: 700; margin-top: 2px;">Account Name: <strong style="color: #0f172a;">MATHEW AMANYIRE</strong></div>
          </div>
          <button type="button" onclick="copyText('3204796984', 'Account number copied!')"
            style="background: #2563eb; color: #ffffff; padding: 8px 14px; border-radius: 8px; font-weight: 800; font-size: 12px; border: none; cursor: pointer; display: flex; align-items: center; gap: 4px;">
            <i class="fas fa-copy"></i> Copy
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- ── 4. Payment & Renewal History ────────────────────────────── --}}
  <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); overflow: hidden;">
    <div style="padding: 16px 24px; border-bottom: 1px solid #e2e8f0; background: #f8fafc; display: flex; align-items: center; justify-content: space-between;">
      <h3 style="font-weight: 900; color: #0f172a; font-size: 16px; margin: 0; display: flex; align-items: center; gap: 8px;">
        <i class="fas fa-history" style="color: #4f46e5;"></i> Payment & Renewal History
      </h3>
      <span style="font-size: 12px; color: #475569; font-weight: 700;">Recorded Subscriptions</span>
    </div>

    @if($history->count())
      <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 14px; text-align: left;">
          <thead style="background: #f1f5f9; color: #334155; font-size: 11px; text-transform: uppercase; font-weight: 900; letter-spacing: 0.05em; border-bottom: 1px solid #cbd5e1;">
            <tr>
              <th style="padding: 14px 20px;">Submission Date</th>
              <th style="padding: 14px 20px;">Package</th>
              <th style="padding: 14px 20px;">Amount</th>
              <th style="padding: 14px 20px;">Payment Method</th>
              <th style="padding: 14px 20px;">Reference ID</th>
              <th style="padding: 14px 20px;">Subscription Period</th>
              <th style="padding: 14px 20px;">Status</th>
              <th style="padding: 14px 20px;">Proof</th>
            </tr>
          </thead>
          <tbody>
            @foreach($history as $p)
              <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 16px 20px; font-weight: 700; color: #1e293b; white-space: nowrap;">
                  {{ $p->created_at->format('M d, Y') }}
                  <span style="display: block; font-size: 11px; color: #64748b; font-weight: 500;">{{ $p->created_at->format('h:i A') }}</span>
                </td>
                <td style="padding: 16px 20px; font-weight: 900; color: #4338ca; text-transform: capitalize;">
                  {{ $p->package_slug ?? '—' }}
                </td>
                <td style="padding: 16px 20px; font-weight: 900; color: #0f172a; white-space: nowrap;">
                  {{ $p->currency }} {{ number_format($p->amount) }}
                </td>
                <td style="padding: 16px 20px; color: #334155; font-weight: 700;">
                  {{ $p->payment_method ?? '—' }}
                </td>
                <td style="padding: 16px 20px; color: #475569; font-family: monospace; font-size: 12px; font-weight: 700;">
                  {{ $p->reference ?? '—' }}
                </td>
                <td style="padding: 16px 20px; font-size: 12px; font-weight: 700; color: #334155; white-space: nowrap;">
                  @if($p->period_start && $p->period_end)
                    {{ $p->period_start->format('M d, Y') }} — {{ $p->period_end->format('M d, Y') }}
                  @else —
                  @endif
                </td>
                <td style="padding: 16px 20px;">
                  @php
                    $cls = match($p->status) {
                      'paid'      => 'background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0;',
                      'pending'   => 'background: #fef3c7; color: #78350f; border: 1px solid #fde68a;',
                      'failed'    => 'background: #fee2e2; color: #991b1b; border: 1px solid #fecaca;',
                      'cancelled' => 'background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1;',
                      default     => 'background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe;',
                    };
                  @endphp
                  <span style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 900; {{ $cls }}">
                    @if($p->status === 'paid') <i class="fas fa-check-circle" style="color: #059669;"></i>
                    @elseif($p->status === 'pending') <i class="fas fa-clock" style="color: #d97706;"></i>
                    @elseif($p->status === 'cancelled') <i class="fas fa-times-circle" style="color: #dc2626;"></i>
                    @else <i class="fas fa-circle"></i>
                    @endif
                    {{ ucfirst($p->status) }}
                  </span>
                  @if($p->status === 'cancelled' && $p->rejection_reason)
                    <div style="font-size: 11px; color: #dc2626; margin-top: 4px; font-weight: 700;">{{ $p->rejection_reason }}</div>
                  @endif
                </td>
                <td style="padding: 16px 20px;">
                  @if($p->proof_image)
                    <a href="{{ asset('storage/'.$p->proof_image) }}" target="_blank"
                      style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 12px; font-size: 12px; font-weight: 900; color: #4338ca; background: #e0e7ff; border-radius: 8px; text-decoration: none; border: 1px solid #c7d2fe;">
                      <i class="fas fa-image"></i> View
                    </a>
                  @else
                    <span style="color: #94a3b8; font-size: 12px;">—</span>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      @if($history->hasPages())
        <div style="padding: 12px 24px; border-top: 1px solid #e2e8f0;">{{ $history->links() }}</div>
      @endif
    @else
      <div style="padding: 48px; text-align: center; color: #64748b;">
        <i class="fas fa-receipt" style="font-size: 36px; display: block; margin-bottom: 8px; opacity: 0.4;"></i>
        <p style="font-size: 14px; font-weight: 700; color: #334155; margin: 0;">No previous subscription payment requests found.</p>
      </div>
    @endif
  </div>

  {{-- ── 5. Available Subscription Packages (Placed AFTER all others) ─ --}}
  @if($packages->count())
    <div id="availablePlansSection" style="padding-top: 16px;">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
        <div>
          <h2 style="font-size: 24px; font-weight: 900; color: #0f172a; margin: 0; tracking-tight;">Available Subscription Plans</h2>
          <p style="font-size: 14px; color: #475569; font-weight: 600; margin: 4px 0 0 0;">Explore all feature tiers available for your business</p>
        </div>
        <span style="font-size: 12px; color: #4338ca; font-weight: 900; background: #e0e7ff; padding: 6px 14px; border-radius: 9999px; border: 1px solid #c7d2fe;">
          {{ $packages->count() }} Packages Available
        </span>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($packages as $pkg)
          @php $isCurrent = ($business->subscription_plan === $pkg->slug); @endphp
          <div style="background: #ffffff; border: {{ $isCurrent ? '2px solid #4f46e5' : '1px solid #cbd5e1' }}; border-radius: 16px; padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); display: flex; flex-direction: column; justify-content: space-between; position: relative;">
            @if($isCurrent)
              <div style="position: absolute; top: -12px; right: 16px; background: #4f46e5; color: #ffffff; font-size: 11px; font-weight: 900; padding: 2px 12px; border-radius: 9999px; box-shadow: 0 2px 4px rgba(0,0,0,0.15);">
                Current Plan
              </div>
            @endif

            <div>
              <div style="font-size: 12px; font-weight: 900; color: #4338ca; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 4px;">{{ $pkg->name }}</div>
              <div style="display: flex; align-items: baseline; gap: 4px; margin: 8px 0 12px 0;">
                <span style="font-size: 28px; font-weight: 900; color: #0f172a;">UGX {{ number_format($pkg->price) }}</span>
                <span style="font-size: 12px; font-weight: 700; color: #64748b;">/ month</span>
              </div>

              @if($pkg->description)
                <p style="font-size: 12px; color: #475569; font-weight: 600; margin-bottom: 16px; line-height: 1.5;">{{ $pkg->description }}</p>
              @endif

              <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 16px 0;">

              <div style="font-size: 12px; font-weight: 900; color: #0f172a; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">Included Features:</div>
              @if(!empty($pkg->features))
                <ul style="list-style: none; padding: 0; margin: 0 0 24px 0; display: flex; flex-direction: column; gap: 8px;">
                  @foreach($pkg->features as $feat)
                    <li style="font-size: 12px; color: #334155; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                      <i class="fas fa-check-circle" style="color: #059669;"></i> {{ ucfirst($feat) }}
                    </li>
                  @endforeach
                </ul>
              @endif
            </div>

            <div>
              <button type="button" onclick="selectPlan('{{ $pkg->slug }}')"
                style="width: 100%; padding: 12px; text-align: center; font-weight: 900; font-size: 14px; border-radius: 12px; border: none; cursor: pointer; {{ $isCurrent ? 'background: #e0e7ff; color: #3730a3; border: 1px solid #c7d2fe;' : 'background: #4f46e5; color: #ffffff;' }}">
                <i class="fas fa-check" style="margin-right: 4px;"></i> {{ $isCurrent ? 'Current Plan Selected' : 'Select Plan & Pay' }}
              </button>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  @endif

</div>

{{-- ── JavaScript Logic for Live Countdown, Autofill & Duration ────── --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
  const pkgSelect = document.getElementById('packageSelect');
  const durationSelect = document.getElementById('durationSelect');
  const amountInput = document.getElementById('subscriptionAmount');
  const partialCheckbox = document.getElementById('allowPartialPayment');
  const autofillBadge = document.getElementById('autofillBadge');
  const periodPreviewText = document.getElementById('periodPreviewText');

  // Package prices mapping
  const packagesMap = {
    @foreach($packages as $pkg)
      "{{ $pkg->slug }}": {{ $pkg->price }},
    @endforeach
  };

  function calculateAmountAndPeriod() {
    const selectedSlug = pkgSelect.value;
    const months = parseInt(durationSelect.value) || 1;
    const pricePerMonth = packagesMap[selectedSlug] || 0;

    // 1. Calculate price if not manually editing partial payment
    if (!partialCheckbox.checked) {
      const totalPrice = pricePerMonth * months;
      amountInput.value = totalPrice;
      amountInput.readOnly = true;
      amountInput.style.backgroundColor = '#f1f5f9';
      amountInput.style.cursor = 'not-allowed';
      if (autofillBadge) autofillBadge.style.display = 'inline-flex';
    }

    // 2. Calculate period preview
    if (selectedSlug && pricePerMonth > 0) {
      const today = new Date();
      const endDate = new Date();
      endDate.setMonth(today.getMonth() + months);

      const options = { day: 'numeric', month: 'short', year: 'numeric' };
      const startStr = today.toLocaleDateString('en-GB', options);
      const endStr = endDate.toLocaleDateString('en-GB', options);
      const durationLabel = months === 12 ? '1 Year' : (months + (months === 1 ? ' Month' : ' Months'));

      periodPreviewText.textContent = `${startStr} to ${endStr} (${durationLabel})`;
    } else {
      periodPreviewText.textContent = 'Select a package to preview period';
    }
  }

  // Handle Partial Payment toggle
  partialCheckbox.addEventListener('change', function () {
    if (this.checked) {
      amountInput.readOnly = false;
      amountInput.style.backgroundColor = '#ffffff';
      amountInput.style.cursor = 'text';
      amountInput.focus();
      if (autofillBadge) autofillBadge.style.display = 'none';
    } else {
      calculateAmountAndPeriod();
    }
  });

  pkgSelect.addEventListener('change', calculateAmountAndPeriod);
  durationSelect.addEventListener('change', calculateAmountAndPeriod);

  // Initial calculation on page load
  calculateAmountAndPeriod();

  // Live Countdown Timer logic
  const timerContainer = document.getElementById('countdownTimer');
  if (timerContainer) {
    const expiryDateStr = timerContainer.getAttribute('data-expiry');
    const expiryTime = new Date(expiryDateStr).getTime();

    function updateCountdown() {
      const now = new Date().getTime();
      const distance = expiryTime - now;

      if (distance <= 0) {
        timerContainer.innerHTML = '<div style="grid-column: span 4; color: #f87171; font-weight: 900; font-size: 16px;">Subscription Expired</div>';
        return;
      }

      const days = Math.floor(distance / (1000 * 60 * 60 * 24));
      const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
      const seconds = Math.floor((distance % (1000 * 60)) / 1000);

      document.getElementById('countDays').textContent = String(days).padStart(2, '0');
      document.getElementById('countHours').textContent = String(hours).padStart(2, '0');
      document.getElementById('countMins').textContent = String(minutes).padStart(2, '0');
      document.getElementById('countSecs').textContent = String(seconds).padStart(2, '0');
    }

    updateCountdown();
    setInterval(updateCountdown, 1000);
  }
});

// Toggle Collapsible Payment Form Accordion
function togglePaymentForm(forceOpen = false) {
  const formBody = document.getElementById('paymentFormBody');
  const arrow = document.getElementById('paymentFormArrow');
  if (!formBody) return;

  if (forceOpen || formBody.style.display === 'none' || formBody.style.display === '') {
    formBody.style.display = 'block';
    if (arrow) arrow.style.transform = 'rotate(180deg)';
  } else {
    formBody.style.display = 'none';
    if (arrow) arrow.style.transform = 'rotate(0deg)';
  }
}

// Select package from available plans cards below
function selectPlan(slug) {
  togglePaymentForm(true); // Automatically expand the form
  const pkgSelect = document.getElementById('packageSelect');
  if (pkgSelect) {
    pkgSelect.value = slug;
    pkgSelect.dispatchEvent(new Event('change'));
  }
  const formSection = document.getElementById('paymentFormSection');
  if (formSection) {
    formSection.scrollIntoView({ behavior: 'smooth' });
  }
}

// Copy to clipboard helper
function copyText(text, message) {
  navigator.clipboard.writeText(text).then(() => {
    alert(message);
  }).catch(() => {
    prompt('Copy number:', text);
  });
}

function handleFileSelect(input) {
  const file = input.files[0];
  if (!file) return;
  const nameEl = document.getElementById('fileName');
  const preview = document.getElementById('previewImg');
  nameEl.textContent = file.name;
  nameEl.style.display = 'block';
  const reader = new FileReader();
  reader.onload = e => {
    preview.src = e.target.result;
    preview.style.display = 'block';
  };
  reader.readAsDataURL(file);
}

// Drag & drop support
const dz = document.getElementById('dropzone');
if (dz) {
  dz.addEventListener('dragover', e => { e.preventDefault(); dz.style.borderColor = '#4f46e5'; });
  dz.addEventListener('dragleave', () => dz.style.borderColor = '#cbd5e1');
  dz.addEventListener('drop', e => {
    e.preventDefault();
    dz.style.borderColor = '#cbd5e1';
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) {
      const input = document.getElementById('proofFile');
      const dt = new DataTransfer();
      dt.items.add(file);
      input.files = dt.files;
      handleFileSelect(input);
    }
  });
}
</script>
@endsection
