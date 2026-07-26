@extends('admin.layout')

@section('title', 'Businesses Management - Admin Panel')

@section('content')

<style>
  .page-header{
    margin-bottom: 32px;
  }
  .page-title{
    font-size: 28px;
    font-weight: 700;
    margin: 0;
    letter-spacing:-0.02em;
  }
  .page-subtitle{
    color: var(--muted);
    margin-top: 6px;
    font-size: 15px;
  }

  /* Filters Section */
  .filters-section{
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
  }
  .filters-grid{
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    align-items: flex-end;
  }
  .filter-group{
    display: flex;
    flex-direction: column;
    gap: 8px;
  }
  .filter-label{
    font-size: 14px;
    font-weight: 600;
    color: var(--text);
  }
  .filter-input,
  .filter-select{
    padding: 10px 14px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--panel);
    color: var(--text);
    font-size: 14px;
    transition: all 0.2s ease;
  }
  .filter-input:focus,
  .filter-select:focus{
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(79,70,229,0.1);
  }
  .filter-input::placeholder{
    color: var(--muted);
  }

  /* Businesses Table */
  .businesses-table-container{
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04), 0 8px 24px rgba(0,0,0,0.06);
  }
  .table-wrapper{
    overflow-x: auto;
  }
  table{
    width: 100%;
    border-collapse: collapse;
  }
  table thead{
    background: linear-gradient(135deg, rgba(79,70,229,0.08) 0%, rgba(59,130,246,0.04) 100%);
  }
  table th{
    padding: 14px 16px;
    text-align: left;
    font-size: 13px;
    font-weight: 700;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 1px solid var(--border);
  }
  table td{
    padding: 16px;
    border-bottom: 1px solid rgba(0,0,0,0.02);
    font-size: 14px;
  }
  table tbody tr{
    transition: all 0.2s ease;
  }
  table tbody tr:hover{
    background: rgba(79,70,229,0.04);
  }
  table tbody tr:last-child td{
    border-bottom: none;
  }

  /* Business Info */
  .business-info{
    display: flex;
    align-items: center;
    gap: 12px;
  }
  .business-avatar{
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 16px;
  }
  .business-details h4{
    margin: 0;
    font-size: 14px;
    font-weight: 600;
    color: var(--text);
  }
  .business-details p{
    margin: 4px 0 0 0;
    font-size: 13px;
    color: var(--muted);
  }

  /* Badges */
  .badge{
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
  }
  .badge-success{
    background: rgba(16,185,129,0.12);
    color: var(--success);
  }
  .badge-danger{
    background: rgba(239,68,68,0.12);
    color: var(--danger);
  }
  .badge-info{
    background: rgba(59,130,246,0.12);
    color: var(--info);
  }
  .badge-warning{
    background: rgba(245,158,11,0.12);
    color: var(--warning);
  }

  /* Actions */
  .actions-cell{
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .action-btn{
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    border: 1px solid var(--border);
    background: transparent;
    color: var(--text);
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 14px;
  }
  .action-btn:hover{
    background: rgba(79,70,229,0.08);
    border-color: var(--primary);
    color: var(--primary);
  }
  .action-btn.danger:hover{
    background: rgba(239,68,68,0.08);
    border-color: var(--danger);
    color: var(--danger);
  }
  .action-btn.success:hover{
    background: rgba(16,185,129,0.08);
    border-color: var(--success);
    color: var(--success);
  }

  /* Empty State */
  .empty-state{
    padding: 60px 20px;
    text-align: center;
    color: var(--muted);
  }
  .empty-state i{
    font-size: 48px;
    opacity: 0.3;
    margin-bottom: 16px;
  }
  .empty-state p{
    font-size: 15px;
    margin: 0;
  }

  /* Modal */
  .modal{
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }
  .modal.show{
    display: flex;
  }
  .modal-content{
    background: var(--panel);
    border-radius: 12px;
    max-width: 500px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
  }
  .modal-header{
    padding: 20px 24px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .modal-title{
    margin: 0;
    font-size: 18px;
    font-weight: 700;
  }
  .modal-close{
    background: none;
    border: none;
    cursor: pointer;
    color: var(--muted);
    font-size: 16px;
    padding: 4px;
  }
  .modal-body{
    padding: 24px;
  }
  .form-group{
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 20px;
  }
  .form-label{
    font-size: 14px;
    font-weight: 600;
  }
  .form-input,
  .form-select{
    padding: 10px 14px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--panel);
    color: var(--text);
    font-size: 14px;
  }
  .modal-footer{
    padding: 16px 24px;
    border-top: 1px solid var(--border);
    display: flex;
    justify-content: flex-end;
    gap: 12px;
  }
  .btn{
    padding: 10px 18px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid transparent;
  }
  .btn-secondary{
    background: var(--panel);
    border-color: var(--border);
    color: var(--text);
  }
  .btn-secondary:hover{
    background: rgba(0,0,0,0.02);
  }
  .btn-primary{
    background: var(--primary);
    color: white;
  }
  .btn-primary:hover{
    background: var(--primary-600);
  }

  /* Pagination */
  .pagination-container{
    margin-top: 24px;
  }
</style>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
  <div>
    <h1 class="page-title">Businesses Management</h1>
    <p class="page-subtitle">Manage registered businesses, active statuses, and subscription plans.</p>
  </div>
  <button class="btn btn-primary" onclick="openAddBusinessModal()">
    <i class="fas fa-plus"></i> Add New Business
  </button>
</div>

<!-- Filters Section -->
<div class="filters-section">
  <form method="GET" action="{{ route('admin.businesses.index') }}">
    <div class="filters-grid">
      <div class="filter-group">
        <label class="filter-label" for="search">Search</label>
        <input type="text" name="search" id="search" class="filter-input" placeholder="Name, email, or phone..." value="{{ request('search') }}">
      </div>
      <div class="filter-group">
        <label class="filter-label" for="category_id">Category</label>
        <select name="category_id" id="category_id" class="filter-select">
          <option value="">All Categories</option>
          @foreach($categories as $category)
            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="filter-group">
        <label class="filter-label" for="status">Status</label>
        <select name="status" id="status" class="filter-select">
          <option value="">All Statuses</option>
          <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
          <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Suspended</option>
        </select>
      </div>
      <div class="filter-group">
        <label class="filter-label" for="plan">Subscription Plan</label>
        <select name="plan" id="plan" class="filter-select">
          <option value="">All Plans</option>
          <option value="Free Trial" {{ request('plan') === 'Free Trial' ? 'selected' : '' }}>Free Trial</option>
          <option value="Basic" {{ request('plan') === 'Basic' ? 'selected' : '' }}>Basic</option>
          <option value="Premium" {{ request('plan') === 'Premium' ? 'selected' : '' }}>Premium</option>
          <option value="Enterprise" {{ request('plan') === 'Enterprise' ? 'selected' : '' }}>Enterprise</option>
        </select>
      </div>
      <div class="filter-group" style="flex-direction: row; gap: 8px;">
        <button type="submit" class="btn btn-primary" style="flex: 1;">Filter</button>
        <a href="{{ route('admin.businesses.index') }}" class="btn btn-secondary" style="text-decoration: none; text-align: center;">Reset</a>
      </div>
    </div>
  </form>
</div>

<!-- Businesses Table -->
<div class="businesses-table-container">
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>Business Info</th>
          <th>Category</th>
          <th>Owner Details</th>
          <th>Status</th>
          <th>Subscription</th>
          <th>Expiry Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($businesses as $business)
          <tr>
            <td>
              <div class="business-info">
                <div class="business-avatar">
                  {{ strtoupper(substr($business->name, 0, 2)) }}
                </div>
                <div class="business-details">
                  <h4>{{ $business->name }}</h4>
                  <p><i class="fas fa-phone mr-1"></i> {{ $business->phone ?? 'N/A' }}</p>
                </div>
              </div>
            </td>
            <td>
              <span class="badge badge-info">{{ $business->businessCategory->name ?? 'Uncategorized' }}</span>
            </td>
            <td>
              <div class="business-details">
                <h4>{{ $business->owner->name ?? 'N/A' }}</h4>
                <p>{{ $business->email ?? $business->owner->email ?? 'N/A' }}</p>
              </div>
            </td>
            <td>
              @if($business->is_active)
                <span class="badge badge-success"><i class="fas fa-check-circle"></i> Active</span>
              @else
                <span class="badge badge-danger"><i class="fas fa-ban"></i> Suspended</span>
              @endif
            </td>
            <td>
              <span class="badge badge-warning">
                {{ $business->subscription_plan === 'trial' ? 'Free Trial' : ucfirst($business->subscription_plan ?? 'Free Trial') }}
              </span>
            </td>
            <td>
              @if($business->subscription_expires_at)
                <span class="{{ $business->daysUntilExpiry() !== null && $business->daysUntilExpiry() < 7 ? 'text-red-500 font-bold' : '' }}">
                  {{ $business->subscription_expires_at->format('M d, Y') }}
                  @if($business->daysUntilExpiry() !== null && $business->daysUntilExpiry() < 7)
                    ({{ $business->daysUntilExpiry() }} days left)
                  @endif
                </span>
              @else
                <span class="text-gray-400">Never Expires</span>
              @endif
            </td>
            <td>
              <div class="actions-cell">
                <!-- Toggle Status Action -->
                @if($business->is_active)
                  <form action="{{ route('admin.businesses.toggle', $business) }}" method="POST" onsubmit="return confirm('Are you sure you want to suspend {{ $business->name }}? All associated users will instantly lose access.');">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="action-btn danger" title="Suspend Business">
                      <i class="fas fa-user-slash"></i>
                    </button>
                  </form>
                @else
                  <form action="{{ route('admin.businesses.toggle', $business) }}" method="POST" onsubmit="return confirm('Are you sure you want to activate {{ $business->name }}?');">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="action-btn success" title="Activate Business">
                      <i class="fas fa-user-check"></i>
                    </button>
                  </form>
                @endif

                <!-- Monitor Operations Trigger -->
                <a href="{{ route('admin.businesses.monitor', $business) }}" class="action-btn" title="Monitor Operations" style="color: var(--primary); display: inline-flex; align-items: center; justify-content: center; text-decoration: none;">
                  <i class="fas fa-desktop"></i>
                </a>

                <!-- Edit Plan Trigger -->
                <button type="button" class="action-btn" title="Edit Subscription" 
                  onclick="openSubscriptionModal('{{ $business->id }}', '{{ addslashes($business->name) }}', '{{ $business->subscription_plan ?? 'trial' }}', '{{ $business->subscription_expires_at ? $business->subscription_expires_at->format('Y-m-d') : '' }}')">
                  <i class="fas fa-edit"></i>
                </button>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7">
              <div class="empty-state">
                <i class="fas fa-building"></i>
                <p>No businesses found matching your criteria.</p>
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="pagination-container">
  {{ $businesses->links() }}
</div>

<!-- Edit Subscription Modal -->
<div id="subscriptionModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3 class="modal-title">Edit Subscription: <span id="editBusinessName"></span></h3>
      <button class="modal-close" onclick="closeSubscriptionModal()"><i class="fas fa-times"></i></button>
    </div>
    <form id="editSubscriptionForm" method="POST" action="">
      @csrf
      @method('PUT')
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label" for="editPlan">Subscription Plan</label>
          <select name="subscription_plan" id="editPlan" class="form-select" required>
            <option value="trial">Free Trial</option>
            <option value="basic">Basic</option>
            <option value="standard">Standard</option>
            <option value="premium">Premium</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="editExpiry">Expiry Date</label>
          <input type="date" name="subscription_expires_at" id="editExpiry" class="form-input">
          <small style="color: var(--muted); margin-top: 4px;">Leave blank for a plan that never expires.</small>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeSubscriptionModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- ============================================
     ADD BUSINESS MODAL
     ============================================ -->
<div id="addBusinessModal" class="modal">
  <div class="modal-content" style="max-width: 600px;">
    <div class="modal-header">
      <h2 class="modal-title">Add New Business</h2>
      <button class="modal-close" onclick="closeAddBusinessModal()">✕</button>
    </div>
    <form action="{{ route('admin.businesses.store') }}" method="POST">
      @csrf
      <div class="modal-body">
        <div style="font-weight: 700; margin-bottom: 12px; color: var(--primary); font-size: 14px;">
          <i class="fas fa-building" style="margin-right: 6px;"></i> Business Details
        </div>
        
        <div class="form-group">
          <label class="form-label" for="addBusinessName">Business Name <span style="color:var(--danger);">*</span></label>
          <input type="text" name="name" id="addBusinessName" class="form-input" placeholder="e.g. Acme Supermarket" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="addCategory">Business Category <span style="color:var(--danger);">*</span></label>
          <select name="business_category_id" id="addCategory" class="form-select" required>
            <option value="">Select Category</option>
            @foreach($categories as $cat)
              <option value="{{ $cat->id }}">{{ $cat->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="addBusinessEmail">Business Email <span style="color:var(--danger);">*</span></label>
          <input type="email" name="email" id="addBusinessEmail" class="form-input" placeholder="e.g. info@acme.com" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="addBusinessPhone">Phone Number <span style="color:var(--danger);">*</span></label>
          <input type="text" name="phone" id="addBusinessPhone" class="form-input" placeholder="e.g. +256 700 000 000" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="addBusinessAddress">Address</label>
          <input type="text" name="address" id="addBusinessAddress" class="form-input" placeholder="e.g. Plot 12 Kampala Rd">
        </div>

        <div class="form-group">
          <label class="form-label" for="addPlan">Subscription Plan <span style="color:var(--danger);">*</span></label>
          <select name="subscription_plan" id="addPlan" class="form-select" required>
            <option value="trial">Free Trial</option>
            @foreach($packages as $pkg)
              <option value="{{ $pkg->slug }}">{{ $pkg->name }} ({{ number_format($pkg->price) }} UGX)</option>
            @endforeach
            @if(!count($packages))
              <option value="basic">Basic</option>
              <option value="standard">Standard</option>
              <option value="premium">Premium</option>
            @endif
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="addDuration">Subscription Duration (Days)</label>
          <input type="number" name="subscription_duration_days" id="addDuration" class="form-input" value="30" min="1" placeholder="30">
        </div>

        <hr style="border: 0; border-top: 1px solid var(--border); margin: 20px 0;">

        <div style="font-weight: 700; margin-bottom: 12px; color: var(--primary); font-size: 14px;">
          <i class="fas fa-user-shield" style="margin-right: 6px;"></i> Business Owner Credentials
        </div>

        <div class="form-group">
          <label class="form-label" for="addOwnerName">Owner Full Name <span style="color:var(--danger);">*</span></label>
          <input type="text" name="owner_name" id="addOwnerName" class="form-input" placeholder="e.g. Jane Doe" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="addOwnerPassword">Owner Password <span style="color:var(--danger);">*</span></label>
          <div style="position: relative;">
            <input type="password" name="owner_password" id="addOwnerPassword" class="form-input" placeholder="Minimum 8 characters" required style="padding-right: 40px;">
            <button type="button" onclick="togglePasswordVisibility('addOwnerPassword', this)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #64748b;" title="Toggle password visibility">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="addOwnerPasswordConfirm">Confirm Owner Password <span style="color:var(--danger);">*</span></label>
          <div style="position: relative;">
            <input type="password" name="owner_password_confirmation" id="addOwnerPasswordConfirm" class="form-input" placeholder="Repeat password" required style="padding-right: 40px;">
            <button type="button" onclick="togglePasswordVisibility('addOwnerPasswordConfirm', this)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #64748b;" title="Toggle password visibility">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeAddBusinessModal()">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Create Business</button>
      </div>
    </form>
  </div>
</div>

<script>
  function openSubscriptionModal(businessId, businessName, currentPlan, currentExpiry) {
    document.getElementById('editBusinessName').textContent = businessName;
    document.getElementById('editSubscriptionForm').action = `/admin/businesses/${businessId}/subscription`;
    document.getElementById('editPlan').value = currentPlan;
    document.getElementById('editExpiry').value = currentExpiry;
    document.getElementById('subscriptionModal').classList.add('show');
  }

  function closeSubscriptionModal() {
    document.getElementById('subscriptionModal').classList.remove('show');
  }

  function openAddBusinessModal() {
    document.getElementById('addBusinessModal').classList.add('show');
  }
  function closeAddBusinessModal() {
    document.getElementById('addBusinessModal').classList.remove('show');
  }

  // Close modals on escape key
  document.addEventListener('keydown', (e) => {
    if(e.key === 'Escape'){
      closeSubscriptionModal();
      closeAddBusinessModal();
    }
  });

  // Close modal when clicking backdrop
  document.getElementById('subscriptionModal').addEventListener('click', function(e) {
    if (e.target === this) {
      closeSubscriptionModal();
    }
  });
  document.getElementById('addBusinessModal').addEventListener('click', function(e) {
    if (e.target === this) {
      closeAddBusinessModal();
    }
  });
</script>

@endsection
