@extends('layouts.app')

@section('title', 'My Subscription')
@section('page-title', 'Subscription & Billing')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6 space-y-8" style="color: #1e3a8a;">

  {{-- ── Flash Notifications ─────────────────────────────────── --}}
  @if(session('success'))
    <div style="background-color: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; border-radius: 12px; padding: 16px; font-weight: 700; font-size: 14px; display: flex; align-items: center; gap: 12px;">
      <i class="fas fa-check-circle" style="color: #2563eb; font-size: 18px;"></i>
      <div>{{ session('success') }}</div>
    </div>
  @endif

  @if(session('error'))
    <div style="background-color: #fef2f2; border: 1px solid #fecaca; color: #991b1b; border-radius: 12px; padding: 16px; font-weight: 700; font-size: 14px; display: flex; align-items: center; gap: 12px;">
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

  {{-- ── 1. Soft Light Blue Header Card with Live Countdown (No Dark Backgrounds) ────── --}}
  <div style="background: #eff6ff; color: #1e3a8a; padding: 28px 32px; border-radius: 16px; border: 1.5px solid #bfdbfe; box-shadow: 0 4px 12px rgba(37,99,235,0.06); position: relative; overflow: hidden;">
    <div style="display: flex; flex-direction: row; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 24px; position: relative; z-index: 10;">
      
      <!-- Left: Plan Information -->
      <div style="display: flex; flex-direction: column; gap: 12px; max-width: 600px;">
        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
          <span style="background: #dbeafe; color: #1d4ed8; padding: 4px 12px; border-radius: 9999px; font-weight: 800; font-size: 11px; border: 1px solid #93c5fd; text-transform: uppercase;">
            <i class="fas fa-building" style="color: #2563eb; margin-right: 4px;"></i> {{ $business->name }}
          </span>
          <span style="color: #3b82f6; font-size: 12px; font-weight: 700;">Official Business Account</span>
        </div>

        <div>
          <span style="color: #60a5fa; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; display: block; margin-bottom: 4px;">
            Current Subscription Plan
          </span>
          <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
            <h1 style="color: #1e3a8a; font-size: 30px; font-weight: 900; margin: 0; text-transform: capitalize; letter-spacing: -0.02em;">
              {{ $business->subscription_plan ?? 'Free Trial' }}
            </h1>
            @if($business->isSubscriptionActive())
              <span style="background: #d1fae5; color: #065f46; padding: 4px 14px; border-radius: 9999px; font-weight: 800; font-size: 12px; border: 1px solid #a7f3d0; display: inline-flex; align-items: center; gap: 6px;">
                <span style="width: 8px; height: 8px; border-radius: 50%; background-color: #059669;"></span> Active
              </span>
            @else
              <span style="background: #fee2e2; color: #991b1b; padding: 4px 14px; border-radius: 9999px; font-weight: 800; font-size: 12px; border: 1px solid #fecaca; display: inline-flex; align-items: center; gap: 6px;">
                <span style="width: 8px; height: 8px; border-radius: 50%; background-color: #dc2626;"></span> Expired
              </span>
            @endif
          </div>
        </div>

        <!-- Renewal Information -->
        <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap; margin-top: 4px;">
          @if($business->subscription_expires_at)
            @php $expires = \Carbon\Carbon::parse($business->subscription_expires_at); @endphp
            <div style="background: #ffffff; color: #1e3a8a; padding: 8px 16px; border-radius: 8px; border: 1px solid #bfdbfe; font-size: 13px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px;">
              <i class="fas fa-calendar-check" style="color: #2563eb;"></i>
              <span>Next Renewal Due: <strong style="color: #1e3a8a; font-weight: 900;">{{ $expires->format('M d, Y') }}</strong></span>
            </div>
          @else
            <div style="background: #ffffff; color: #1e3a8a; padding: 8px 16px; border-radius: 8px; border: 1px solid #bfdbfe; font-size: 13px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px;">
              <i class="fas fa-infinity" style="color: #2563eb;"></i>
              <span style="color: #1e3a8a; font-weight: 900;">Lifetime Subscription (No Expiry)</span>
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
        <div style="background: #ffffff; border: 1.5px solid #bfdbfe; padding: 20px; border-radius: 14px; text-align: center; min-width: 280px; box-shadow: 0 2px 8px rgba(37,99,235,0.04);">
          <div style="color: #2563eb; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 12px; display: flex; align-items: center; justify-content: center; gap: 6px;">
            <i class="fas fa-clock" style="color: #2563eb;"></i> Remaining Duration
          </div>

          @if($isPast)
            <div style="color: #dc2626; font-weight: 900; font-size: 20px; padding: 8px 0;">
              <i class="fas fa-exclamation-circle" style="margin-right: 4px;"></i> Subscription Expired
            </div>
            <div style="color: #64748b; font-size: 12px; font-weight: 600;">Renew below to restore full business access</div>
          @else
            <div id="liveCountdownContainer" style="display: flex; justify-content: center; gap: 8px;">
              <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 8px 12px; min-width: 55px;">
                <span id="cntDays" style="font-size: 22px; font-weight: 900; color: #1e3a8a; display: block; line-height: 1;">00</span>
                <span style="font-size: 9px; font-weight: 800; color: #3b82f6; text-transform: uppercase;">Days</span>
              </div>
              <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 8px 12px; min-width: 55px;">
                <span id="cntHours" style="font-size: 22px; font-weight: 900; color: #1e3a8a; display: block; line-height: 1;">00</span>
                <span style="font-size: 9px; font-weight: 800; color: #3b82f6; text-transform: uppercase;">Hours</span>
              </div>
              <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 8px 12px; min-width: 55px;">
                <span id="cntMins" style="font-size: 22px; font-weight: 900; color: #1e3a8a; display: block; line-height: 1;">00</span>
                <span style="font-size: 9px; font-weight: 800; color: #3b82f6; text-transform: uppercase;">Mins</span>
              </div>
              <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 8px 12px; min-width: 55px;">
                <span id="cntSecs" style="font-size: 22px; font-weight: 900; color: #2563eb; display: block; line-height: 1;">00</span>
                <span style="font-size: 9px; font-weight: 800; color: #3b82f6; text-transform: uppercase;">Secs</span>
              </div>
            </div>
          @endif
        </div>
      @endif

    </div>
  </div>

  {{-- ── 2. Collapsible Renewal / Subscription Payment Form Card ────── --}}
  <div style="background: #ffffff; border: 1px solid #bfdbfe; border-radius: 16px; box-shadow: 0 4px 12px rgba(37,99,235,0.04); overflow: hidden;">
    <div style="padding: 20px 28px; background: #eff6ff; border-bottom: 1px solid #bfdbfe; display: flex; align-items: center; justify-content: space-between;">
      <div>
        <h3 style="font-weight: 900; color: #1e3a8a; font-size: 18px; margin: 0; display: flex; align-items: center; gap: 8px;">
          <i class="fas fa-credit-card" style="color: #2563eb;"></i> Renew / Upgrade Subscription Payment
        </h3>
        <p style="font-size: 12px; color: #3b82f6; font-weight: 600; margin: 2px 0 0 0;">
          Select your plan, choose duration, and submit payment for instant activation
        </p>
      </div>

      <button type="button" id="togglePaymentFormBtn" onclick="togglePaymentForm()"
        style="background: #2563eb; color: #ffffff; padding: 10px 20px; border-radius: 10px; font-weight: 800; font-size: 13px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 2px 4px rgba(37,99,235,0.2);">
        <i class="fas fa-plus-circle"></i> Make Subscription Payment
      </button>
    </div>

    <div id="paymentFormBody" style="padding: 24px 32px; display: {{ ($errors->any() || old('package_slug')) ? 'block' : 'none' }}; border-top: 1px solid #bfdbfe;">
      <form method="POST" action="{{ route('subscription.pay') }}" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 24px;">
        @csrf

        <!-- Row 1: Plan Selection & Duration -->
        <div class="grid md:grid-cols-2 gap-6">
          <div>
            <label style="display: block; font-size: 13px; font-weight: 800; color: #1e3a8a; margin-bottom: 6px;">
              1. Select Subscription Package <span style="color: #dc2626;">*</span>
            </label>
            <select name="package_slug" id="packageSelect" required
              style="width: 100%; padding: 12px 16px; border: 1px solid #bfdbfe; border-radius: 10px; background: #ffffff; color: #1e3a8a; font-size: 14px; font-weight: 700; outline: none;">
              <option value="">Choose a plan…</option>
              @foreach($packages as $pkg)
                <option value="{{ $pkg->slug }}" data-price="{{ $pkg->price }}" data-cycle="{{ $pkg->billing_cycle_days }}"
                  @selected(old('package_slug') == $pkg->slug || $business->subscription_plan == $pkg->slug)>
                  {{ $pkg->name }} — UGX {{ number_format($pkg->price) }} / month
                </option>
              @endforeach
            </select>
          </div>

          <div>
            <label style="display: block; font-size: 13px; font-weight: 800; color: #1e3a8a; margin-bottom: 6px;">
              2. Select Subscription Period (Months) <span style="color: #dc2626;">*</span>
            </label>
            <select name="duration_months" id="durationSelect" required
              style="width: 100%; padding: 12px 16px; border: 1px solid #bfdbfe; border-radius: 10px; background: #ffffff; color: #1e3a8a; font-size: 14px; font-weight: 700; outline: none;">
              <option value="1">1 Month (Monthly Standard)</option>
              <option value="2">2 Months</option>
              <option value="3">3 Months (Quarterly)</option>
              <option value="4">4 Months</option>
              <option value="5">5 Months</option>
              <option value="6">6 Months (Half Year)</option>
              <option value="12">12 Months (1 Year - Pay 10 Months, 2 Months FREE! 🎉)</option>
            </select>
          </div>
        </div>

        <!-- Row 2: Auto-calculated Amount & Partial Payment Checkbox -->
        <div style="background: #eff6ff; border-radius: 12px; padding: 20px; border: 1px solid #bfdbfe; display: flex; flex-direction: column; gap: 16px;">
          <div class="grid md:grid-cols-2 gap-6 items-end">
            <div>
              <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                <label style="font-size: 13px; font-weight: 800; color: #1e3a8a; margin: 0;">
                  Total Subscription Amount (UGX) <span style="color: #dc2626;">*</span>
                </label>
                <span id="autofillBadge" style="font-size: 11px; font-weight: 800; color: #1d4ed8; background: #dbeafe; border: 1px solid #93c5fd; padding: 2px 10px; border-radius: 9999px;">
                  <i class="fas fa-calculator" style="margin-right: 4px;"></i> Auto-calculated
                </span>
              </div>
              <div style="position: relative;">
                <span style="position: absolute; left: 16px; top: 14px; color: #2563eb; font-weight: 900; font-size: 14px;">UGX</span>
                <input type="number" name="amount" id="subscriptionAmount" min="0" step="100" placeholder="0"
                  value="{{ old('amount') }}" required readonly
                  style="width: 100%; padding-left: 56px; padding-right: 16px; padding-top: 12px; padding-bottom: 12px; border: 1px solid #bfdbfe; border-radius: 10px; background: #ffffff; color: #1e3a8a; font-weight: 900; font-size: 20px; outline: none;">
              </div>
            </div>

            <div style="background: #ffffff; padding: 14px 16px; border-radius: 10px; border: 1px solid #bfdbfe; display: flex; align-items: center; gap: 12px;">
              <input type="checkbox" id="allowPartialPayment" name="is_partial" value="1"
                style="width: 20px; height: 20px; accent-color: #2563eb; cursor: pointer;">
              <label for="allowPartialPayment" style="font-size: 13px; font-weight: 800; color: #1e3a8a; cursor: pointer; margin: 0; user-select: none;">
                Tick for Partial Payment
                <span style="display: block; font-size: 11px; font-weight: 600; color: #64748b;">Check this box if you are making a partial payment</span>
              </label>
            </div>
          </div>

          <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; color: #1e3a8a; border-top: 1px solid #bfdbfe; padding-top: 10px;">
            <i class="fas fa-calendar-range" style="color: #2563eb; font-size: 16px;"></i>
            <span>Calculated Coverage Period: <strong id="periodPreviewText" style="color: #2563eb; font-weight: 900;">Select plan & duration</strong></span>
          </div>
        </div>

        <!-- Row 3: Payment Method & Reference -->
        <div class="grid md:grid-cols-2 gap-6">
          <div>
            <label style="display: block; font-size: 13px; font-weight: 800; color: #1e3a8a; margin-bottom: 6px;">
              Payment Method <span style="color: #dc2626;">*</span>
            </label>
            <select name="payment_method" required
              style="width: 100%; padding: 12px 16px; border: 1px solid #bfdbfe; border-radius: 10px; background: #ffffff; color: #1e3a8a; font-size: 14px; font-weight: 700; outline: none;">
              <option value="">Select payment channel…</option>
              <option value="Mobile Money" @selected(old('payment_method')=='Mobile Money')>MTN / Airtel Mobile Money (0787320647)</option>
              <option value="Bank Transfer" @selected(old('payment_method')=='Bank Transfer')>Centenary Bank Transfer (3204796984)</option>
              <option value="Cash" @selected(old('payment_method')=='Cash')>Direct Cash Payment</option>
              <option value="Other" @selected(old('payment_method')=='Other')>Other Payment Channel</option>
            </select>
          </div>

          <div>
            <label style="display: block; font-size: 13px; font-weight: 800; color: #1e3a8a; margin-bottom: 6px;">
              Transaction ID / Reference Number
            </label>
            <input type="text" name="reference" placeholder="e.g. 248901239 or Bank Ref"
              value="{{ old('reference') }}"
              style="width: 100%; padding: 12px 16px; border: 1px solid #bfdbfe; border-radius: 10px; background: #ffffff; color: #1e3a8a; font-size: 14px; font-weight: 700; outline: none;">
          </div>
        </div>

        <!-- Row 4: Proof Upload -->
        <div>
          <label style="display: block; font-size: 13px; font-weight: 800; color: #1e3a8a; margin-bottom: 6px;">
            Attach Payment Proof Screenshot / Receipt <span style="color: #64748b; font-weight: 600;">(Optional)</span>
          </label>
          <div id="dropzone"
            style="border: 2px dashed #bfdbfe; border-radius: 12px; padding: 24px; text-align: center; cursor: pointer; background: #eff6ff; transition: all 0.2s ease;"
            onclick="document.getElementById('proofFile').click()">
            <i class="fas fa-cloud-arrow-up" style="font-size: 32px; color: #2563eb; margin-bottom: 8px; display: block;"></i>
            <p style="font-size: 14px; font-weight: 800; color: #1e3a8a; margin: 0;">Click to upload or drag & drop payment confirmation</p>
            <p style="font-size: 12px; color: #64748b; margin-top: 4px;">Supports JPG, PNG, WEBP — max 5 MB</p>
            <div id="fileName" style="margin-top: 8px; font-size: 12px; color: #2563eb; font-weight: 800; display: none;"></div>
            <img id="previewImg" style="margin: 12px auto 0 auto; max-height: 160px; border-radius: 10px; display: none;" alt="Preview">
          </div>
          <input type="file" id="proofFile" name="proof_image" accept="image/*" style="display: none;" onchange="handleFileSelect(this)">
        </div>

        <!-- Row 5: Notes & Submit -->
        <div>
          <label style="display: block; font-size: 13px; font-weight: 800; color: #1e3a8a; margin-bottom: 6px;">Additional Billing Remarks</label>
          <textarea name="notes" rows="2" placeholder="Provide any additional payment details or reference notes for the admin…"
            style="width: 100%; padding: 12px 16px; border: 1px solid #bfdbfe; border-radius: 10px; background: #ffffff; color: #1e3a8a; font-size: 14px; font-weight: 600; outline: none; resize: none;">{{ old('notes') }}</textarea>
        </div>

        <div style="padding-top: 8px; display: flex; justify-content: flex-end;">
          <button type="submit"
            style="background: #2563eb; color: #ffffff; padding: 12px 32px; border-radius: 10px; font-weight: 900; font-size: 15px; border: none; cursor: pointer; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25); display: inline-flex; align-items: center; gap: 8px;">
            <i class="fas fa-paper-plane"></i> Submit Subscription Payment
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- ── 3. Official Payment Instructions Card ───────────────────── --}}
  <div style="background: #eff6ff; border: 1.5px solid #bfdbfe; border-radius: 16px; padding: 24px 32px; box-shadow: 0 2px 4px rgba(37,99,235,0.04);">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid #bfdbfe;">
      <div style="display: flex; align-items: center; gap: 12px;">
        <div style="width: 40px; height: 40px; background: #dbeafe; color: #1d4ed8; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 800;">
          <i class="fas fa-credit-card"></i>
        </div>
        <div>
          <h3 style="font-weight: 900; color: #1e3a8a; font-size: 18px; margin: 0;">Official Payment Accounts</h3>
          <p style="font-size: 12px; color: #3b82f6; font-weight: 600; margin: 2px 0 0 0;">Use any of the official channels below to send your subscription payment</p>
        </div>
      </div>
    </div>

    <div class="grid md:grid-cols-2 gap-6">
      <!-- Mobile Money Official Box -->
      <div style="background: #ffffff; border-radius: 12px; padding: 20px; border: 1px solid #bfdbfe; box-shadow: 0 1px 3px rgba(37,99,235,0.04);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
          <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 32px; height: 32px; background: #eff6ff; color: #2563eb; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 800;">
              <i class="fas fa-mobile-screen-button"></i>
            </div>
            <span style="font-weight: 900; color: #1e3a8a; font-size: 14px;">MTN / Airtel Mobile Money</span>
          </div>
          <span style="font-size: 11px; font-weight: 900; color: #1d4ed8; background: #dbeafe; padding: 2px 10px; border-radius: 9999px; border: 1px solid #93c5fd;">
            Official Line
          </span>
        </div>

        <div style="background: #eff6ff; padding: 14px 16px; border-radius: 10px; border: 1px solid #bfdbfe; display: flex; align-items: center; justify-content: space-between;">
          <div>
            <div style="font-size: 22px; font-weight: 900; color: #1e3a8a; letter-spacing: 0.05em; font-family: monospace;" id="officialPhone">0787320647</div>
            <div style="font-size: 12px; color: #475569; font-weight: 700; margin-top: 2px;">Account Name: <strong style="color: #1e3a8a;">Rebecca Sarah Kasangirwe</strong></div>
          </div>
          <button type="button" onclick="copyText('0787320647', 'Phone number copied!')"
            style="background: #2563eb; color: #ffffff; padding: 8px 14px; border-radius: 8px; font-weight: 800; font-size: 12px; border: none; cursor: pointer; display: flex; align-items: center; gap: 4px;">
            <i class="fas fa-copy"></i> Copy
          </button>
        </div>
      </div>

      <!-- Bank Transfer Box -->
      <div style="background: #ffffff; border-radius: 12px; padding: 20px; border: 1px solid #bfdbfe; box-shadow: 0 1px 3px rgba(37,99,235,0.04);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
          <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 32px; height: 32px; background: #eff6ff; color: #2563eb; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 800;">
              <i class="fas fa-building-columns"></i>
            </div>
            <span style="font-weight: 900; color: #1e3a8a; font-size: 14px;">Centenary Bank</span>
          </div>
          <span style="font-size: 11px; font-weight: 900; color: #1d4ed8; background: #dbeafe; padding: 2px 10px; border-radius: 9999px; border: 1px solid #93c5fd;">
            Bank Account
          </span>
        </div>

        <div style="background: #eff6ff; padding: 14px 16px; border-radius: 10px; border: 1px solid #bfdbfe; display: flex; align-items: center; justify-content: space-between;">
          <div>
            <div style="font-size: 22px; font-weight: 900; color: #1e3a8a; letter-spacing: 0.05em; font-family: monospace;">3204796984</div>
            <div style="font-size: 12px; color: #475569; font-weight: 700; margin-top: 2px;">Account Name: <strong style="color: #1e3a8a;">MATHEW AMANYIRE</strong></div>
          </div>
          <button type="button" onclick="copyText('3204796984', 'Account number copied!')"
            style="background: #2563eb; color: #ffffff; padding: 8px 14px; border-radius: 8px; font-weight: 800; font-size: 12px; border: none; cursor: pointer; display: flex; align-items: center; gap: 4px;">
            <i class="fas fa-copy"></i> Copy
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- ── 4. Payment & Renewal History Table ────────────────────────── --}}
  <div style="background: #ffffff; border: 1px solid #bfdbfe; border-radius: 16px; box-shadow: 0 4px 12px rgba(37,99,235,0.04); overflow: hidden;">
    <div style="padding: 16px 24px; border-bottom: 1px solid #bfdbfe; background: #eff6ff; display: flex; align-items: center; justify-content: space-between;">
      <h3 style="font-weight: 900; color: #1e3a8a; font-size: 16px; margin: 0; display: flex; align-items: center; gap: 8px;">
        <i class="fas fa-history" style="color: #2563eb;"></i> Payment & Renewal History
      </h3>
      <span style="font-size: 12px; color: #3b82f6; font-weight: 700;">Recorded Subscriptions</span>
    </div>

    @if($history->count())
      <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
          <thead style="background: #dbeafe; color: #1e40af; font-size: 11px; text-transform: uppercase; font-weight: 900; border-bottom: 2px solid #bfdbfe;">
            <tr>
              <th style="padding: 12px 18px;">Submission Date</th>
              <th style="padding: 12px 18px;">Package</th>
              <th style="padding: 12px 18px;">Amount</th>
              <th style="padding: 12px 18px;">Payment Method</th>
              <th style="padding: 12px 18px;">Reference ID</th>
              <th style="padding: 12px 18px;">Subscription Period</th>
              <th style="padding: 12px 18px;">Status</th>
              <th style="padding: 12px 18px;">Proof</th>
            </tr>
          </thead>
          <tbody>
            @foreach($history as $p)
              <tr style="border-bottom: 1px solid #f1f5f9;" class="hover:bg-blue-50/40 transition">
                <td style="padding: 14px 18px; font-weight: 700; color: #1e293b; white-space: nowrap;">
                  {{ $p->created_at->format('M d, Y') }}
                  <span style="display: block; font-size: 11px; color: #64748b; font-weight: 500;">{{ $p->created_at->format('h:i A') }}</span>
                </td>
                <td style="padding: 14px 18px; font-weight: 900; color: #2563eb; text-transform: capitalize;">
                  {{ $p->package_slug ?? '—' }}
                </td>
                <td style="padding: 14px 18px; font-weight: 900; color: #1e3a8a; white-space: nowrap;">
                  {{ $p->currency }} {{ number_format($p->amount) }}
                </td>
                <td style="padding: 14px 18px; color: #334155; font-weight: 700;">
                  {{ $p->payment_method ?? '—' }}
                </td>
                <td style="padding: 14px 18px; color: #475569; font-family: monospace; font-size: 12px; font-weight: 700;">
                  {{ $p->reference ?? '—' }}
                </td>
                <td style="padding: 14px 18px; font-size: 12px; font-weight: 700; color: #334155; white-space: nowrap;">
                  @if($p->period_start && $p->period_end)
                    {{ $p->period_start->format('M d, Y') }} — {{ $p->period_end->format('M d, Y') }}
                  @else —
                  @endif
                </td>
                <td style="padding: 14px 18px;">
                  @php
                    $cls = match($p->status) {
                      'paid'      => 'background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0;',
                      'pending'   => 'background: #fef3c7; color: #78350f; border: 1px solid #fde68a;',
                      'failed'    => 'background: #fee2e2; color: #991b1b; border: 1px solid #fecaca;',
                      'cancelled' => 'background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1;',
                      default     => 'background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe;',
                    };
                  @endphp
                  <span style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 9999px; font-size: 11px; font-weight: 900; {{ $cls }}">
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
                <td style="padding: 14px 18px;">
                  @if($p->proof_image)
                    <a href="{{ asset('storage/'.$p->proof_image) }}" target="_blank"
                      style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 12px; font-size: 11px; font-weight: 900; color: #1d4ed8; background: #eff6ff; border-radius: 8px; text-decoration: none; border: 1px solid #bfdbfe;">
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
        <div style="padding: 12px 24px; border-top: 1px solid #bfdbfe;">{{ $history->links() }}</div>
      @endif
    @else
      <div style="padding: 48px; text-align: center; color: #64748b;">
        <i class="fas fa-receipt" style="font-size: 36px; display: block; margin-bottom: 8px; opacity: 0.4;"></i>
        <p style="font-size: 14px; font-weight: 700; color: #334155; margin: 0;">No previous subscription payment requests found.</p>
      </div>
    @endif
  </div>

  {{-- ── 5. Available Subscription Packages ──────────────────────── --}}
  @if($packages->count())
    <div id="availablePlansSection" style="padding-top: 16px;">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
        <div>
          <h2 style="font-size: 22px; font-weight: 900; color: #1e3a8a; margin: 0;">Available Subscription Plans</h2>
          <p style="font-size: 13px; color: #3b82f6; font-weight: 600; margin: 4px 0 0 0;">Explore all feature tiers available for your business</p>
        </div>
        <span style="font-size: 12px; color: #1d4ed8; font-weight: 900; background: #eff6ff; padding: 6px 14px; border-radius: 9999px; border: 1px solid #bfdbfe;">
          {{ $packages->count() }} Packages Available
        </span>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($packages as $pkg)
          @php $isCurrent = ($business->subscription_plan === $pkg->slug); @endphp
          <div style="background: #ffffff; border: {{ $isCurrent ? '2px solid #2563eb' : '1px solid #bfdbfe' }}; border-radius: 16px; padding: 24px; box-shadow: 0 4px 12px rgba(37,99,235,0.04); display: flex; flex-direction: column; justify-content: space-between; position: relative;">
            @if($isCurrent)
              <div style="position: absolute; top: -12px; right: 16px; background: #2563eb; color: #ffffff; font-size: 11px; font-weight: 900; padding: 2px 12px; border-radius: 9999px; box-shadow: 0 2px 4px rgba(37,99,235,0.2);">
                Current Plan
              </div>
            @endif

            <div>
              <div style="font-size: 12px; font-weight: 900; color: #2563eb; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 4px;">{{ $pkg->name }}</div>
              <div style="display: flex; align-items: baseline; gap: 4px; margin: 8px 0 6px 0;">
                <span style="font-size: 26px; font-weight: 900; color: #1e3a8a;">UGX {{ number_format($pkg->price) }}</span>
                <span style="font-size: 12px; font-weight: 700; color: #64748b;">/ month</span>
              </div>

              @if($pkg->price > 0)
                <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 10px 12px; margin-bottom: 12px;">
                  <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                    <span style="font-size: 11px; font-weight: 800; color: #1e3a8a; text-transform: uppercase;">
                      <i class="fas fa-calendar-check" style="color: #2563eb; margin-right: 4px;"></i> Annual Billing
                    </span>
                    <span style="font-size: 10px; font-weight: 900; color: #ffffff; background: #2563eb; padding: 1px 8px; border-radius: 9999px;">
                      2 Months FREE
                    </span>
                  </div>
                  <div style="display: flex; align-items: baseline; justify-content: space-between;">
                    <span style="font-size: 15px; font-weight: 900; color: #1e3a8a;">UGX {{ number_format($pkg->price * 10) }} <span style="font-size: 11px; font-weight: 700; color: #3b82f6;">/ year</span></span>
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-decoration: line-through;">UGX {{ number_format($pkg->price * 12) }}</span>
                  </div>
                  <div style="font-size: 11px; font-weight: 800; color: #1d4ed8; border-top: 1px solid #bfdbfe; padding-top: 4px; margin-top: 4px;">
                    <i class="fas fa-piggy-bank" style="color: #2563eb; margin-right: 4px;"></i> Save up to <strong style="color: #1e3a8a; font-weight: 900;">UGX {{ number_format($pkg->price * 2) }}</strong> subscribing annually!
                  </div>
                </div>
              @endif

              @if($pkg->description)
                <p style="font-size: 12px; color: #475569; font-weight: 600; margin-bottom: 16px; line-height: 1.5;">{{ $pkg->description }}</p>
              @endif

              <hr style="border: 0; border-top: 1px solid #bfdbfe; margin: 16px 0;">

              <div style="font-size: 11px; font-weight: 900; color: #1e3a8a; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">Included Features:</div>
              @if(!empty($pkg->features))
                <ul style="list-style: none; padding: 0; margin: 0 0 24px 0; display: flex; flex-direction: column; gap: 8px;">
                  @foreach($pkg->features as $feat)
                    <li style="font-size: 12px; color: #334155; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                      <i class="fas fa-check-circle" style="color: #2563eb;"></i> {{ ucfirst($feat) }}
                    </li>
                  @endforeach
                </ul>
              @endif
            </div>

            <div>
              <button type="button" onclick="selectPlan('{{ $pkg->slug }}')"
                style="width: 100%; padding: 12px; text-align: center; font-weight: 900; font-size: 13px; border-radius: 10px; border: none; cursor: pointer; {{ $isCurrent ? 'background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;' : 'background: #2563eb; color: #ffffff;' }}">
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

  const packagesMap = {
    @foreach($packages as $pkg)
      "{{ $pkg->slug }}": {{ $pkg->price }},
    @endforeach
  };

  function calculateAmountAndPeriod() {
    const selectedSlug = pkgSelect.value;
    const months = parseInt(durationSelect.value) || 1;
    const pricePerMonth = packagesMap[selectedSlug] || 0;
    
    // Annual 2 months free discount
    const monthsToPay = (months === 12) ? 10 : months;

    if (!partialCheckbox.checked) {
      const totalPrice = pricePerMonth * monthsToPay;
      amountInput.value = totalPrice;
      amountInput.readOnly = true;
      amountInput.style.backgroundColor = '#ffffff';
      amountInput.style.cursor = 'not-allowed';
      if (autofillBadge) autofillBadge.style.display = 'inline-flex';
    }

    if (selectedSlug) {
      const today = new Date();
      const futureDate = new Date();
      futureDate.setMonth(futureDate.getMonth() + months);
      const options = { year: 'numeric', month: 'short', day: 'numeric' };
      let previewMsg = `${today.toLocaleDateString(undefined, options)} — ${futureDate.toLocaleDateString(undefined, options)} (${months} month${months > 1 ? 's' : ''})`;
      if (months === 12 && pricePerMonth > 0) {
        const savings = pricePerMonth * 2;
        previewMsg += ` 🎉 Annual Discount Applied! (Saved UGX ${savings.toLocaleString()})`;
      }
      periodPreviewText.textContent = previewMsg;
    } else {
      periodPreviewText.textContent = 'Select plan & duration';
    }
  }

  if (pkgSelect) pkgSelect.addEventListener('change', calculateAmountAndPeriod);
  if (durationSelect) durationSelect.addEventListener('change', calculateAmountAndPeriod);

  if (partialCheckbox) {
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
  }

  calculateAmountAndPeriod();

  @if($business->subscription_expires_at)
    const expiresAtMs = new Date("{{ \Carbon\Carbon::parse($business->subscription_expires_at)->toIso8601String() }}").getTime();

    function updateLiveCountdown() {
      const nowMs = new Date().getTime();
      const diffMs = expiresAtMs - nowMs;

      if (diffMs <= 0) {
        return;
      }

      const days  = Math.floor(diffMs / (1000 * 60 * 60 * 24));
      const hours = Math.floor((diffMs % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      const mins  = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
      const secs  = Math.floor((diffMs % (1000 * 60)) / 1000);

      const dEl = document.getElementById('cntDays');
      const hEl = document.getElementById('cntHours');
      const mEl = document.getElementById('cntMins');
      const sEl = document.getElementById('cntSecs');

      if (dEl) dEl.textContent = String(days).padStart(2, '0');
      if (hEl) hEl.textContent = String(hours).padStart(2, '0');
      if (mEl) mEl.textContent = String(mins).padStart(2, '0');
      if (sEl) sEl.textContent = String(secs).padStart(2, '0');
    }

    updateLiveCountdown();
    setInterval(updateLiveCountdown, 1000);
  @endif
});

function togglePaymentForm() {
  const formBody = document.getElementById('paymentFormBody');
  const btn = document.getElementById('togglePaymentFormBtn');
  if (formBody) {
    const isHidden = formBody.style.display === 'none' || formBody.style.display === '';
    formBody.style.display = isHidden ? 'block' : 'none';
    if (btn) {
      btn.innerHTML = isHidden ? '<i class="fas fa-minus-circle"></i> Hide Form' : '<i class="fas fa-plus-circle"></i> Make Subscription Payment';
    }
  }
}

function selectPlan(slug) {
  const formBody = document.getElementById('paymentFormBody');
  const btn = document.getElementById('togglePaymentFormBtn');
  const pkgSelect = document.getElementById('packageSelect');

  if (formBody) {
    formBody.style.display = 'block';
    if (btn) btn.innerHTML = '<i class="fas fa-minus-circle"></i> Hide Form';
  }

  if (pkgSelect) {
    pkgSelect.value = slug;
    pkgSelect.dispatchEvent(new Event('change'));
  }

  window.scrollTo({ top: formBody.offsetTop - 100, behavior: 'smooth' });
}

function copyText(text, msg) {
  navigator.clipboard.writeText(text).then(() => {
    alert(msg);
  }).catch(() => {
    const el = document.createElement('textarea');
    el.value = text;
    document.body.appendChild(el);
    el.select();
    document.execCommand('copy');
    document.body.removeChild(el);
    alert(msg);
  });
}

function handleFileSelect(input) {
  const file = input.files[0];
  const nameDiv = document.getElementById('fileName');
  const previewImg = document.getElementById('previewImg');
  if (file) {
    if (nameDiv) {
      nameDiv.textContent = 'Selected: ' + file.name;
      nameDiv.style.display = 'block';
    }
    if (previewImg && file.type.startsWith('image/')) {
      const reader = new FileReader();
      reader.onload = function (e) {
        previewImg.src = e.target.result;
        previewImg.style.display = 'block';
      };
      reader.readAsDataURL(file);
    }
  }
}
</script>
@endsection
