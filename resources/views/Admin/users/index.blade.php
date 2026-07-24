@extends('admin.layout')

@section('title', 'Users Management - Admin Panel')

@section('content')

<style>
  .page-header{
    margin-bottom: 32px;
  }
  .page-title{
    font-size: 28px;
    font-weight:  700;
    margin:  0;
    letter-spacing:-0.02em;
  }
  .page-subtitle{
    color: var(--muted);
    margin-top: 6px;
    font-size:  15px;
  }

  /* Filters Section */
  .filters-section{
    background: var(--panel);
    border:  1px solid var(--border);
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
  }
  .filters-grid{
    display: grid;
    grid-template-columns:repeat(auto-fit, minmax(250px, 1fr));
    gap:16px;
  }
  .filter-group{
    display:flex;
    flex-direction:column;
    gap:8px;
  }
  .filter-label{
    font-size:14px;
    font-weight:600;
    color:var(--text);
  }
  .filter-input,
  .filter-select{
    padding:10px 14px;
    border:1px solid var(--border);
    border-radius:8px;
    background: var(--panel);
    color:var(--text);
    font-size:14px;
    transition:all 0.2s ease;
  }
  .filter-input:focus,
  .filter-select:focus{
    outline:  none;
    border-color:  var(--primary);
    box-shadow: 0 0 0 3px rgba(79,70,229,0.1);
  }
  .filter-input::placeholder{
    color:var(--muted);
  }

  /* Users Table */
  .users-table-container{
    background:var(--panel);
    border:1px solid var(--border);
    border-radius:  12px;
    overflow:hidden;
    box-shadow:0 1px 2px rgba(0,0,0,0.04), 0 8px 24px rgba(0,0,0,0.06);
  }
  .table-wrapper{
    overflow-x:auto;
  }
  table{
    width:100%;
    border-collapse:collapse;
  }
  table thead{
    background:linear-gradient(135deg, rgba(79,70,229,0.08) 0%, rgba(59,130,246,0.04) 100%);
  }
  table th{
    padding:14px 16px;
    text-align:left;
    font-size:13px;
    font-weight:  700;
    color:var(--muted);
    text-transform:uppercase;
    letter-spacing:0.05em;
    border-bottom:  1px solid var(--border);
  }
  table td{
    padding:16px;
    border-bottom: 1px solid rgba(0,0,0,0.02);
    font-size:14px;
  }
  table tbody tr{
    transition:all 0.2s ease;
  }
  table tbody tr:hover{
    background:  rgba(79,70,229,0.04);
  }
  table tbody tr:last-child td{
    border-bottom: none;
  }

  /* User Info */
  .user-info{
    display:flex;
    align-items:center;
    gap:12px;
  }
  .user-avatar{
    width:40px;
    height:40px;
    border-radius:50%;
    background:linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    display:flex;
    align-items:center;
    justify-content:center;
    color:white;
    font-weight:700;
    font-size:16px;
  }
  .user-details h4{
    margin:0;
    font-size:14px;
    font-weight:600;
    color:var(--text);
  }
  .user-details p{
    margin:4px 0 0 0;
    font-size:13px;
    color:var(--muted);
  }

  /* Badges */
  .badge{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:6px 12px;
    border-radius:6px;
    font-size:  12px;
    font-weight:600;
    white-space:nowrap;
  }
  .badge-success{
    background:  rgba(16,185,129,0.12);
    color:var(--success);
  }
  .badge-danger{
    background:  rgba(239,68,68,0.12);
    color:var(--danger);
  }
  .badge-warning{
    background: rgba(245,158,11,0.12);
    color:var(--warning);
  }
  .badge-info{
    background: rgba(59,130,246,0.12);
    color:var(--info);
  }
  .badge-primary{
    background:  rgba(79,70,229,0.12);
    color:var(--primary);
  }

  /* Actions */
  .actions-cell{
    display:flex;
    align-items:center;
    gap:8px;
  }
  .action-btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    width:32px;
    height:  32px;
    border-radius:6px;
    border:1px solid var(--border);
    background:transparent;
    color:var(--text);
    cursor:pointer;
    transition:all 0.2s ease;
    font-size:14px;
  }
  .action-btn:hover{
    background:rgba(79,70,229,0.08);
    border-color:var(--primary);
    color:var(--primary);
  }
  .action-btn.danger:hover{
    background: rgba(239,68,68,0.08);
    border-color:var(--danger);
    color:var(--danger);
  }
  .action-btn.success:hover{
    background:rgba(16,185,129,0.08);
    border-color:var(--success);
    color:var(--success);
  }

  /* Empty State */
  .empty-state{
    padding:60px 20px;
    text-align:center;
    color:var(--muted);
  }
  .empty-state i{
    font-size:48px;
    opacity:0.3;
    margin-bottom:16px;
  }
  .empty-state p{
    font-size:15px;
    margin:  0;
  }

  /* Modal */
  .modal{
    display:none;
    position:fixed;
    inset:0;
    background: rgba(0,0,0,0.5);
    z-index:1000;
    align-items:center;
    justify-content:center;
    padding:20px;
  }
  .modal.show{
    display:flex;
  }
  .modal-content{
    background:var(--panel);
    border-radius: 12px;
    max-width:600px;
    width:100%;
    max-height:90vh;
    overflow-y: auto;
    box-shadow:0 20px 25px -5px rgba(0,0,0,0.1);
  }
  .modal-header{
    padding:20px 24px;
    border-bottom:1px solid var(--border);
    display:flex;
    align-items:center;
    justify-content:space-between;
  }
  .modal-header h2{
    margin:0;
    font-size:18px;
    font-weight: 600;
  }
  .modal-close{
    background:none;
    border:none;
    color:var(--muted);
    cursor:pointer;
    font-size:24px;
    transition:color 0.2s;
  }
  .modal-close:hover{
    color: var(--text);
  }
  .modal-body{
    padding:24px;
  }
  .modal-footer{
    padding: 16px 24px;
    border-top:1px solid var(--border);
    display:flex;
    gap:12px;
    justify-content:flex-end;
  }

  /* Form */
  .form-group{
    margin-bottom:20px;
  }
  .form-label{
    display:block;
    margin-bottom:8px;
    font-size:14px;
    font-weight:600;
    color: var(--text);
  }
  .form-control{
    width:100%;
    padding:10px 14px;
    border:1px solid var(--border);
    border-radius:8px;
    background:var(--panel);
    color:var(--text);
    font-size:14px;
    transition:all 0.2s ease;
  }
  .form-control:focus{
    outline:none;
    border-color:var(--primary);
    box-shadow:0 0 0 3px rgba(79,70,229,0.1);
  }
  .form-control:disabled{
    opacity:0.6;
    cursor:not-allowed;
  }
  .form-text{
    font-size:13px;
    color:var(--muted);
    margin-top:6px;
  }

  /* Button */
  .btn{
    padding:10px 16px;
    border:1px solid var(--border);
    border-radius:8px;
    background:transparent;
    color:var(--text);
    cursor:pointer;
    transition:all 0.2s ease;
    font-weight:600;
    font-size:14px;
    display:inline-flex;
    align-items:center;
    gap:8px;
  }
  .btn-primary{
    background:var(--primary);
    border-color:var(--primary);
    color:white;
  }
  .btn-primary:hover{
    background:var(--primary-600);
    border-color:var(--primary-600);
  }
  .btn-secondary{
    background:transparent;
    border-color:var(--border);
  }
  .btn-secondary:hover{
    background:rgba(79,70,229,0.08);
    border-color:var(--primary);
  }
  .btn:disabled{
    opacity:0.5;
    cursor:not-allowed;
  }

  /* Info Box */
  .info-box{
    background:rgba(79,70,229,0.08);
    border:1px solid rgba(79,70,229,0.2);
    border-radius:8px;
    padding:16px;
    margin-bottom:16px;
  }
  .info-box h4{
    margin: 0 0 8px 0;
    font-size:14px;
    font-weight: 600;
  }
  .info-box p{
    margin:0;
    font-size:13px;
    color:var(--muted);
  }

  @media (max-width:768px){
    .filters-grid{
      grid-template-columns:1fr;
    }
    .actions-cell{
      flex-direction:column;
    }
    table{
      font-size:12px;
    }
    table th, table td{
      padding:12px 8px;
    }
    .modal-content{
      max-width:100%;
    }
  }
</style>

<!-- Page Header -->
<div class="page-header">
  <h1 class="page-title">Users Management</h1>
  <p class="page-subtitle">Manage and monitor all registered users in the system</p>
</div>

<!-- Filters Section -->
<div class="filters-section">
  <div class="filters-grid">
    <!-- Search Input -->
    <div class="filter-group">
      <label class="filter-label">
        <i class="fas fa-search" style="margin-right:6px;"></i> Search Users
      </label>
      <input 
        type="text" 
        id="searchInput" 
        class="filter-input" 
        placeholder="Search by name or email..."
        autocomplete="off"
      >
    </div>

    <!-- Status Filter -->
    <div class="filter-group">
      <label class="filter-label">
        <i class="fas fa-filter" style="margin-right:6px;"></i> Status
      </label>
      <select id="statusFilter" class="filter-select">
        <option value="">All Status</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>
    </div>

    <!-- Role Filter -->
    <div class="filter-group">
      <label class="filter-label">
        <i class="fas fa-user-tag" style="margin-right:6px;"></i> Role
      </label>
      <select id="roleFilter" class="filter-select">
        <option value="">All Roles</option>
        @foreach($roles as $role)
          <option value="{{ $role->id }}">{{ $role->name }}</option>
        @endforeach
      </select>
    </div>

    <!-- Results Count -->
    <div class="filter-group" style="justify-content: flex-end;">
      <label class="filter-label" style="color:var(--muted); font-weight:500;">
        Showing <strong id="resultCount">{{ $users->count() }}</strong> users
      </label>
    </div>
  </div>
</div>

<!-- Users Table -->
<div class="users-table-container">
  <div class="table-wrapper">
    <table id="usersTable">
      <thead>
        <tr>
          <th style="width:35%;">User Information</th>
          <th style="width:20%;">Role</th>
          <th style="width:15%;">Status</th>
          <th style="width:20%;">Joined</th>
          <th style="width:10%;">Actions</th>
        </tr>
      </thead>
      <tbody id="tableBody">
        @php
          $groupedUsers = $users->groupBy(function($user) {
              return $user->business->name ?? 'System Admins / No Business';
          });
        @endphp
        @forelse($groupedUsers as $businessName => $businessUsers)
          <tr class="business-group-header-row" style="background: rgba(79,70,229,0.06); font-weight: bold; pointer-events: none;">
            <td colspan="5" style="padding: 12px 16px; color: var(--primary); font-size: 14px; font-weight: 700; border-bottom: 1px solid var(--border);">
              <i class="fas fa-building" style="margin-right: 8px;"></i> {{ $businessName }} ({{ $businessUsers->count() }} Staff)
            </td>
          </tr>
          @foreach($businessUsers as $user)
            <tr class="user-row" data-user-id="{{ $user->id }}" data-user-name="{{ $user->name }}" data-user-email="{{ $user->email }}">
              <td>
                <div class="user-info">
                  <div class="user-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                  <div class="user-details">
                    <h4>{{ $user->name }}</h4>
                    <p>{{ $user->email }}</p>
                  </div>
                </div>
              </td>
              <td>
                <span class="badge badge-info" data-role-id="{{ $user->role_id }}">
                  <i class="fas fa-tag"></i>
                  {{ $user->role?->name ?? 'No Role' }}
                </span>
              </td>
              <td>
                @if($user->is_active)
                  <span class="badge badge-success status-badge">
                    <i class="fas fa-circle"></i> Active
                  </span>
                @else
                  <span class="badge badge-danger status-badge">
                    <i class="fas fa-circle"></i> Inactive
                  </span>
                @endif
              </td>
              <td>
                <small style="color:var(--muted);">
                  {{ $user->created_at->format('M d, Y') }}
                </small>
              </td>
              <td>
                <div class="actions-cell">
                  @if($user->is_active)
                    <button class="action-btn danger" onclick="deactivateUser({{ $user->id }}, '{{ $user->name }}')" title="Deactivate User">
                      <i class="fas fa-ban"></i>
                    </button>
                  @else
                    <button class="action-btn success" onclick="activateUser({{ $user->id }}, '{{ $user->name }}')" title="Activate User">
                      <i class="fas fa-check"></i>
                    </button>
                  @endif
                  <button class="action-btn" onclick="editUser({{ $user->id }})" title="Edit User">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="action-btn" onclick="viewUser({{ $user->id }})" title="View User">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
              </td>
            </tr>
          @endforeach
        @empty
          <tr>
            <td colspan="5">
              <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>No users found. Start adding users to your system.</p>
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<!-- ============================================
     VIEW USER MODAL
     ============================================ -->
<div id="viewModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h2>User Details</h2>
      <button class="modal-close" onclick="closeViewModal()">✕</button>
    </div>
    <div class="modal-body">
      <div id="viewModalContent">
        <div style="text-align: center; padding:40px;">
          <i class="fas fa-spinner fa-spin" style="font-size:32px; color:var(--primary);"></i>
          <p style="color:var(--muted); margin-top:16px;">Loading user details...</p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ============================================
     EDIT USER MODAL
     ============================================ -->
<div id="editModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Edit User</h2>
      <button class="modal-close" onclick="closeEditModal()">✕</button>
    </div>
    <div class="modal-body">
      <form id="editForm" onsubmit="submitEditForm(event)">
        @csrf
        @method('PATCH')

        <div class="form-group">
          <label class="form-label">Full Name</label>
          <input type="text" id="editName" class="form-control" required>
        </div>

        <div class="form-group">
          <label class="form-label">Email Address</label>
          <input type="email" id="editEmail" class="form-control" required>
        </div>

        <div class="form-group">
          <label class="form-label">Role</label>
          <select id="editRole" class="form-control" required>
            <option value="">Select a role</option>
            @foreach($roles as $role)
              <option value="{{ $role->id }}">{{ $role->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="info-box">
          <h4><i class="fas fa-lock" style="margin-right:6px;"></i> Change Password</h4>
          <p>Leave blank to keep the current password</p>
        </div>

        <div class="form-group">
          <label class="form-label">New Password</label>
          <input type="password" id="editPassword" class="form-control" placeholder="Enter new password (optional)">
          <div class="form-text">Minimum 8 characters</div>
        </div>

        <div class="form-group">
          <label class="form-label">Confirm Password</label>
          <input type="password" id="editPasswordConfirm" class="form-control" placeholder="Confirm new password">
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ============================================
     CONFIRMATION MODAL
     ============================================ -->
<div id="confirmModal" class="modal">
  <div class="modal-content" style="max-width:400px;">
    <div class="modal-header">
      <h2 id="confirmTitle">Confirm Action</h2>
      <button class="modal-close" onclick="closeConfirmModal()">✕</button>
    </div>
    <div class="modal-body">
      <p id="confirmMessage"></p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeConfirmModal()">Cancel</button>
      <button id="confirmBtn" class="btn btn-primary" onclick="confirmAction()">Confirm</button>
    </div>
  </div>
</div>

<script>
  const allUsersData = @json($users->items());
  let pendingAction = null;
  let pendingUserId = null;
  let currentEditUserId = null;

  // Live Search - Real time as you type
  document.getElementById('searchInput').addEventListener('keyup', function(){
    filterTable();
  });

  document.getElementById('statusFilter').addEventListener('change', filterTable);
  document.getElementById('roleFilter').addEventListener('change', filterTable);

  function filterTable(){
    const searchValue = document.getElementById('searchInput').value.toLowerCase();
    const statusValue = document.getElementById('statusFilter').value;
    const roleValue = document.getElementById('roleFilter').value;
    const rows = document.querySelectorAll('#tableBody tr');
    let visibleCount = 0;
    
    let currentHeader = null;
    let headerHasMatch = false;

    rows.forEach(row => {
      // If it is a header row, we temporarily record it and keep it visible
      if (row.classList.contains('business-group-header-row')) {
        if (currentHeader && !headerHasMatch) {
          currentHeader.style.display = 'none';
        }
        currentHeader = row;
        headerHasMatch = false;
        row.style.display = '';
        return;
      }

      const userNameEl = row.querySelector('.user-details h4');
      if(!userNameEl) return;

      const name = userNameEl.textContent.toLowerCase();
      const email = row.querySelector('.user-details p').textContent.toLowerCase();
      const statusBadge = row.querySelector('.status-badge').textContent.toLowerCase();
      const roleEl = row.querySelector('.badge-info');
      const roleId = roleEl.getAttribute('data-role-id');

      // Match search
      let matchesSearch = name.includes(searchValue) || email.includes(searchValue);

      // Match status
      let matchesStatus = !statusValue || 
        (statusValue === 'active' && statusBadge.includes('active')) || 
        (statusValue === 'inactive' && statusBadge.includes('inactive'));

      // Match role
      let matchesRole = !roleValue || roleId === roleValue;

      if(matchesSearch && matchesStatus && matchesRole){
        row.style.display = '';
        visibleCount++;
        headerHasMatch = true;
      } else {
        row.style.display = 'none';
      }
    });

    // Handle last section header
    if (currentHeader && !headerHasMatch) {
      currentHeader.style.display = 'none';
    }

    document.getElementById('resultCount').textContent = visibleCount;
  }

  // View User
  function viewUser(userId){
    const user = allUsersData.find(u => u.id === userId);
    if(!user) return;

    const content = `
      <div class="user-info" style="margin-bottom:24px;">
        <div class="user-avatar" style="width:60px; height:60px; font-size:24px;">
          ${user.name.charAt(0).toUpperCase()}
        </div>
        <div style="flex: 1;">
          <h3 style="margin:0 0 4px 0; font-size:18px; font-weight:600;">${user. name}</h3>
          <p style="margin:0; color:var(--muted);">${user.email}</p>
        </div>
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:24px;">
        <div class="info-box">
          <h4><i class="fas fa-briefcase"></i> Role</h4>
          <p>${user. role?. name || 'No Role'}</p>
        </div>
        <div class="info-box">
          <h4><i class="fas fa-building"></i> Business</h4>
          <p>${user.business?.name || 'No Business'}</p>
        </div>
        <div class="info-box">
          <h4><i class="fas fa-calendar"></i> Joined</h4>
          <p>${new Date(user.created_at).toLocaleDateString()}</p>
        </div>
        <div class="info-box">
          <h4><i class="fas fa-toggle-${user.is_active ? 'on' : 'off'}"></i> Status</h4>
          <p>${user.is_active ? '<span class="badge badge-success"><i class="fas fa-circle"></i> Active</span>' : '<span class="badge badge-danger"><i class="fas fa-circle"></i> Inactive</span>'}</p>
        </div>
      </div>

      <div class="info-box">
        <h4><i class="fas fa-info-circle"></i> Account Details</h4>
        <table style="width:100%; font-size:13px;">
          <tr style="border-bottom:1px solid rgba(0,0,0,0.05);">
            <td style="padding:8px 0; color:var(--muted);">Email Verified: </td>
            <td style="padding:8px 0; text-align:right; font-weight:600;">${user.email_verified_at ? 'Yes' : 'No'}</td>
          </tr>
          <tr style="border-bottom:1px solid rgba(0,0,0,0.05);">
            <td style="padding:8px 0; color:var(--muted);">2FA Enabled:</td>
            <td style="padding:8px 0; text-align:right; font-weight:600;">${user.two_factor_enabled ? 'Yes' : 'No'}</td>
          </tr>
          <tr>
            <td style="padding: 8px 0; color:var(--muted);">Last Login:</td>
            <td style="padding:8px 0; text-align:right; font-weight:600;">${user.last_login_at ? new Date(user.last_login_at).toLocaleDateString() : 'Never'}</td>
          </tr>
        </table>
      </div>
    `;

    document.getElementById('viewModalContent').innerHTML = content;
    document.getElementById('viewModal').classList.add('show');
  }

  function closeViewModal(){
    document.getElementById('viewModal').classList.remove('show');
  }

  // Edit User
  function editUser(userId){
    const user = allUsersData.find(u => u.id === userId);
    if(!user) return;

    currentEditUserId = userId;
    document.getElementById('editName').value = user.name;
    document.getElementById('editEmail').value = user.email;
    document.getElementById('editRole').value = user.role_id || '';
    document.getElementById('editPassword').value = '';
    document.getElementById('editPasswordConfirm').value = '';
    document.getElementById('editForm').action = `/admin/users/${userId}`;

    document.getElementById('editModal').classList.add('show');
  }

  function closeEditModal(){
    document.getElementById('editModal').classList.remove('show');
    currentEditUserId = null;
  }

  function submitEditForm(event){
    event.preventDefault();

    const name = document.getElementById('editName').value;
    const email = document.getElementById('editEmail').value;
    const roleId = document.getElementById('editRole').value;
    const password = document.getElementById('editPassword').value;
    const passwordConfirm = document.getElementById('editPasswordConfirm').value;

    if(password && password !== passwordConfirm){
      showAlert('error', 'Passwords do not match!');
      return;
    }

    if(password && password.length < 8){
      showAlert('error', 'Password must be at least 8 characters! ');
      return;
    }

    const formData = {
      name,
      email,
      role_id: roleId
    };

    if(password){
      formData.password = password;
      formData.password_confirmation = passwordConfirm;
    }

    fetch(`/admin/users/${currentEditUserId}`, {
      method: 'PATCH',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body:  JSON.stringify(formData)
    })
    .then(response => {
      if(! response.ok) throw new Error('Network response was not ok');
      return response.json();
    })
    .then(data => {
      closeEditModal();
      showAlert('success', data.message || 'User updated successfully! ');
      setTimeout(() => location.reload(), 1500);
    })
    .catch(error => {
      console. error('Error:', error);
      showAlert('error', 'Failed to update user');
    });
  }

  // Activate User
  function activateUser(userId, userName){
    pendingAction = 'activate';
    pendingUserId = userId;
    document.getElementById('confirmTitle').textContent = 'Activate User';
    document.getElementById('confirmMessage').innerHTML = `Are you sure you want to activate <strong>${userName}</strong>?  They will regain access to the system. `;
    document.getElementById('confirmModal').classList.add('show');
  }

  // Deactivate User
  function deactivateUser(userId, userName){
    pendingAction = 'deactivate';
    pendingUserId = userId;
    document.getElementById('confirmTitle').textContent = 'Deactivate User';
    document.getElementById('confirmMessage').innerHTML = `Are you sure you want to deactivate <strong>${userName}</strong>? They will lose access to the system.`;
    document.getElementById('confirmModal').classList.add('show');
  }

  function closeConfirmModal(){
    document.getElementById('confirmModal').classList.remove('show');
    pendingAction = null;
    pendingUserId = null;
  }

  function confirmAction(){
    if(! pendingAction || !pendingUserId) return;

    closeConfirmModal();

    const url = `/admin/users/${pendingUserId}/toggle`;

    fetch(url, {
      method: 'PATCH',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }
    })
    .then(response => {
      if(!response.ok) throw new Error('Network response was not ok');
      return response. json();
    })
    .then(data => {
      showAlert('success', data.message || 'User status updated successfully!');
      setTimeout(() => location.reload(), 1500);
    })
    .catch(error => {
      console. error('Error:', error);
      showAlert('error', 'Failed to update user status');
    });
  }

  // Show Alert
  function showAlert(type, message){
    const mainContent = document.querySelector('. main');
    const alertDiv = document. createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.style.marginBottom = '16px';
    
    const icon = type === 'success' 
      ? '<i class="fas fa-check-circle"></i>' 
      :  '<i class="fas fa-exclamation-circle"></i>';
    
    alertDiv.innerHTML = `
      ${icon}
      <div style="flex:  1;">
        <strong>${type === 'success' ? 'Success!' : 'Error!'}</strong> ${message}
      </div>
      <button class="alert-close" onclick="this.parentElement.remove();">
        <i class="fas fa-times"></i>
      </button>
    `;
    
    mainContent.insertBefore(alertDiv, mainContent.firstChild);
    setTimeout(() => alertDiv.remove(), 5000);
  }

  // Close modals on escape
  document.addEventListener('keydown', (e) => {
    if(e.key === 'Escape'){
      closeViewModal();
      closeEditModal();
      closeConfirmModal();
    }
  });
</script>

@endsection