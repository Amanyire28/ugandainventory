@extends('admin.layout')

@section('title', 'Subscription Packages - Admin Panel')

@section('content')
<style>
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;
  }
  .page-title {
    font-size: 28px;
    font-weight: 700;
    margin: 0;
    letter-spacing: -0.02em;
  }
  .page-subtitle {
    color: var(--muted);
    margin-top: 6px;
    font-size: 15px;
  }
  .btn-primary {
    background: var(--primary);
    color: white;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
    transition: all 0.2s;
    text-decoration: none;
  }
  .btn-primary:hover {
    background: var(--primary-600);
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(79, 70, 229, 0.35);
  }

  /* Grid layout */
  .packages-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 24px;
    margin-bottom: 40px;
  }
  .package-card {
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 28px;
    box-shadow: var(--card-shadow);
    display: flex;
    flex-direction: column;
    position: relative;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }
  .package-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.05), 0 10px 10px -5px rgba(0,0,0,0.02);
    border-color: var(--primary);
  }
  .package-header {
    border-bottom: 1px solid var(--border);
    padding-bottom: 20px;
    margin-bottom: 20px;
  }
  .package-badge {
    position: absolute;
    top: 24px;
    right: 24px;
    background: rgba(79, 70, 229, 0.1);
    color: var(--primary);
    padding: 4px 10px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }
  .package-name {
    font-size: 20px;
    font-weight: 700;
    color: #111827;
    margin: 0 0 6px 0;
  }
  .package-price {
    font-size: 32px;
    font-weight: 800;
    color: #111827;
    margin: 12px 0 4px 0;
    display: flex;
    align-items: baseline;
  }
  .package-price-sub {
    font-size: 14px;
    color: var(--muted);
    font-weight: 500;
    margin-left: 4px;
  }
  .package-desc {
    color: var(--muted);
    font-size: 14px;
    line-height: 1.5;
    margin: 0;
  }
  .features-list {
    list-style: none;
    padding: 0;
    margin: 0 0 32px 0;
    flex-grow: 1;
  }
  .features-list li {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    color: #4b5563;
    padding: 8px 0;
    border-bottom: 1px dashed rgba(0,0,0,0.02);
  }
  .features-list li i.fa-check-circle {
    color: var(--success);
  }
  .features-list li i.fa-times-circle {
    color: #9ca3af;
  }
  .package-actions {
    display: flex;
    gap: 12px;
    margin-top: auto;
  }
  .btn-outline {
    flex: 1;
    border: 1px solid var(--border);
    background: transparent;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    cursor: pointer;
    text-align: center;
    transition: all 0.2s;
  }
  .btn-outline:hover {
    background: #f9fafb;
    border-color: #d1d5db;
  }
  .btn-danger-outline {
    border: 1px solid rgba(239, 68, 68, 0.2);
    color: var(--danger);
    background: transparent;
    padding: 8px 12px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
  }
  .btn-danger-outline:hover {
    background: rgba(239, 68, 68, 0.05);
    border-color: var(--danger);
  }

  /* Modal styling */
  .modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s ease;
  }
  .modal.show {
    opacity: 1;
    pointer-events: auto;
  }
  .modal-dialog {
    background: var(--panel);
    border-radius: 16px;
    width: 100%;
    max-width: 580px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    overflow: hidden;
    transform: translateY(20px);
    transition: transform 0.3s ease;
  }
  .modal.show .modal-dialog {
    transform: translateY(0);
  }
  .modal-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .modal-title {
    font-size: 18px;
    font-weight: 700;
    color: #111827;
    margin: 0;
  }
  .close-btn {
    background: transparent;
    border: none;
    font-size: 18px;
    color: var(--muted);
    cursor: pointer;
  }
  .modal-body {
    padding: 24px;
    max-height: 70vh;
    overflow-y: auto;
  }
  .form-group {
    margin-bottom: 18px;
  }
  .form-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
  }
  .form-control, .form-select {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 14px;
    background: var(--panel);
    color: var(--text);
  }
  .form-control:focus, .form-select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
  }
  .features-grid-select {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    background: #f9fafb;
    padding: 16px;
    border-radius: 8px;
    border: 1px solid var(--border);
  }
  .feature-checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #374151;
    cursor: pointer;
  }
  .modal-footer {
    padding: 16px 24px;
    border-top: 1px solid var(--border);
    display: flex;
    justify-content: flex-end;
    gap: 12px;
  }
</style>

<div class="page-header">
  <div>
    <h1 class="page-title">SaaS Packages & Feature Gates</h1>
    <p class="page-subtitle">Configure pricing plans and toggle core modules enabled per package.</p>
  </div>
  <button type="button" class="btn-primary" onclick="openCreateModal()">
    <i class="fas fa-plus"></i> Create Package
  </button>
</div>

<div class="packages-grid">
  @forelse($packages as $package)
    <div class="package-card">
      <span class="package-badge">{{ $package->billing_cycle_days }} Days Cycle</span>
      <div class="package-header">
        <h3 class="package-name">{{ $package->name }}</h3>
        <p class="package-desc">{{ $package->description ?? 'No description provided.' }}</p>
        <div class="package-price">
          UGX {{ number_format($package->price) }}
          <span class="package-price-sub">/ cycle</span>
        </div>
      </div>

      <ul class="features-list">
        <strong style="font-size: 12px; text-transform: uppercase; color: var(--muted); display: block; margin-bottom: 8px;">Enabled Features:</strong>
        @foreach($availableFeatures as $key => $label)
          @php
            $isEnabled = in_array($key, $package->features ?? []);
          @endphp
          <li>
            @if($isEnabled)
              <i class="fas fa-check-circle mr-2"></i>
              <span>{{ $label }}</span>
            @else
              <i class="fas fa-times-circle mr-2" style="opacity: 0.4;"></i>
              <span style="color: #9ca3af; text-decoration: line-through;">{{ $label }}</span>
            @endif
          </li>
        @endforeach
      </ul>

      <div class="package-actions">
        <button type="button" class="btn-outline" 
          onclick="openEditModal('{{ $package->id }}', '{{ addslashes($package->name) }}', '{{ $package->slug }}', '{{ addslashes($package->description) }}', '{{ $package->price }}', '{{ $package->billing_cycle_days }}', {{ json_encode($package->features) }})">
          <i class="fas fa-cog"></i> Configure
        </button>
        
        <form action="{{ route('admin.packages.destroy', $package) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this package?');">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn-danger-outline" title="Delete Package">
            <i class="fas fa-trash-alt"></i>
          </button>
        </form>
      </div>
    </div>
  @empty
    <div style="grid-column: 1/-1; text-align: center; padding: 48px; border: 1px dashed var(--border); border-radius: 12px; background: var(--panel);">
      <i class="fas fa-box-open" style="font-size: 40px; color: var(--muted); margin-bottom: 16px;"></i>
      <h3 style="margin: 0; font-size: 16px;">No Packages Available</h3>
      <p style="color: var(--muted); font-size: 14px; margin-top: 6px;">Create your first package to define feature tier gating.</p>
    </div>
  @endforelse
</div>

<!-- Add/Edit Package Modal -->
<div id="packageModal" class="modal">
  <div class="modal-dialog">
    <div class="modal-header">
      <h3 class="modal-title" id="modalTitle">Create Subscription Package</h3>
      <button type="button" class="close-btn" onclick="closeModal()"><i class="fas fa-times"></i></button>
    </div>
    <form id="packageForm" method="POST" action="">
      @csrf
      <input type="hidden" id="formMethod" name="_method" value="POST">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label" for="packageName">Package Name</label>
          <input type="text" name="name" id="packageName" class="form-control" placeholder="e.g. Standard Pro" required oninput="generateSlug(this.value)">
        </div>
        <div class="form-group">
          <label class="form-label" for="packageSlug">Package Slug (Unique identifier)</label>
          <input type="text" name="slug" id="packageSlug" class="form-control" placeholder="e.g. standard" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="packagePrice">Price (UGX)</label>
          <input type="number" name="price" id="packagePrice" class="form-control" min="0" placeholder="0" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="packageCycle">Billing Cycle (Days)</label>
          <input type="number" name="billing_cycle_days" id="packageCycle" class="form-control" min="1" value="30" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="packageDesc">Description</label>
          <textarea name="description" id="packageDesc" class="form-control" rows="2" placeholder="Brief outline of subscription plan..."></textarea>
        </div>
        
        <div class="form-group">
          <label class="form-label">Gate Features / Access Modules</label>
          <div class="features-grid-select">
            @foreach($availableFeatures as $key => $label)
              <label class="feature-checkbox-label">
                <input type="checkbox" name="features[]" value="{{ $key }}" class="feature-checkbox">
                {{ $label }}
              </label>
            @endforeach
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-outline" style="flex: none;" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn-primary">Save Package</button>
      </div>
    </form>
  </div>
</div>

<script>
  function generateSlug(text) {
    // Only auto-generate slug for creating new package
    if (document.getElementById('formMethod').value === 'POST') {
      const slug = text.toLowerCase()
        .replace(/[^a-z0-9 -]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-');
      document.getElementById('packageSlug').value = slug;
    }
  }

  function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Create Subscription Package';
    document.getElementById('packageForm').action = "{{ route('admin.packages.store') }}";
    document.getElementById('formMethod').value = 'POST';
    
    // Clear inputs
    document.getElementById('packageName').value = '';
    document.getElementById('packageSlug').value = '';
    document.getElementById('packageSlug').readOnly = false;
    document.getElementById('packagePrice').value = '';
    document.getElementById('packageCycle').value = '30';
    document.getElementById('packageDesc').value = '';
    
    // Clear checkboxes
    document.querySelectorAll('.feature-checkbox').forEach(cb => cb.checked = false);
    
    document.getElementById('packageModal').classList.add('show');
  }

  function openEditModal(id, name, slug, desc, price, cycle, features) {
    document.getElementById('modalTitle').textContent = 'Edit Subscription Package';
    document.getElementById('packageForm').action = `/admin/packages/${id}`;
    document.getElementById('formMethod').value = 'PUT';
    
    // Set inputs
    document.getElementById('packageName').value = name;
    document.getElementById('packageSlug').value = slug;
    document.getElementById('packageSlug').readOnly = true; // prevent changing slug as it breaks references
    document.getElementById('packagePrice').value = parseFloat(price);
    document.getElementById('packageCycle').value = parseInt(cycle);
    document.getElementById('packageDesc').value = desc;
    
    // Set checkboxes
    document.querySelectorAll('.feature-checkbox').forEach(cb => {
      cb.checked = features.includes(cb.value);
    });
    
    document.getElementById('packageModal').classList.add('show');
  }

  function closeModal() {
    document.getElementById('packageModal').classList.remove('show');
  }
</script>
@endsection
