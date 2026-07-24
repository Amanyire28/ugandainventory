@extends('Admin.layout')

@section('title', 'Dashboard - Admin Panel')

@section('content')

<style>
  /* Premium Design Tokens */
  :root {
    --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.025);
    --card-shadow-hover: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
  }

  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;
    flex-wrap: wrap;
    gap: 16px;
  }
  .page-title {
    font-size: 28px;
    font-weight: 800;
    margin: 0;
    letter-spacing: -0.03em;
    color: #111827;
  }
  .page-subtitle {
    color: var(--muted);
    margin-top: 4px;
    font-size: 15px;
  }
  
  /* Status Indicators */
  .status-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 16px;
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    border-radius: 9999px;
    color: #047857;
    font-size: 13px;
    font-weight: 600;
  }
  .status-dot {
    width: 8px;
    height: 8px;
    background-color: #10b981;
    border-radius: 50%;
    position: relative;
  }
  .status-dot::after {
    content: '';
    position: absolute;
    inset: -4px;
    border-radius: 50%;
    border: 2px solid #10b981;
    animation: pulse-glow 1.5s infinite;
  }
  @keyframes pulse-glow {
    0% { transform: scale(1); opacity: 1; }
    100% { transform: scale(2.2); opacity: 0; }
  }

  /* Stats Grid */
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 24px;
    margin-bottom: 32px;
  }
  .stat-card {
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 24px;
    box-shadow: var(--card-shadow);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
  }
  .stat-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--card-shadow-hover);
  }
  .stat-card::after {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 4px;
    background: transparent;
  }
  
  /* Glowing Top Accents */
  .stat-card.indigo::after { background: linear-gradient(90deg, #4f46e5, #818cf8); }
  .stat-card.emerald::after { background: linear-gradient(90deg, #10b981, #34d399); }
  .stat-card.rose::after { background: linear-gradient(90deg, #f43f5e, #fb7185); }
  .stat-card.violet::after { background: linear-gradient(90deg, #7c3aed, #a78bfa); }
  .stat-card.amber::after { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
  .stat-card.cyan::after { background: linear-gradient(90deg, #06b6d4, #67e8f9); }

  .stat-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 16px;
  }
  .stat-label {
    color: var(--muted);
    font-size: 14px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }
  .stat-icon-wrapper {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
  }
  
  /* Icon colors */
  .indigo .stat-icon-wrapper { background: rgba(79, 70, 229, 0.08); color: #4f46e5; }
  .emerald .stat-icon-wrapper { background: rgba(16, 185, 129, 0.08); color: #10b981; }
  .rose .stat-icon-wrapper { background: rgba(244, 63, 94, 0.08); color: #f43f5e; }
  .violet .stat-icon-wrapper { background: rgba(124, 58, 237, 0.08); color: #7c3aed; }
  .amber .stat-icon-wrapper { background: rgba(245, 158, 11, 0.08); color: #f59e0b; }
  .cyan .stat-icon-wrapper { background: rgba(6, 182, 212, 0.08); color: #06b6d4; }

  .stat-body {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }
  .stat-number {
    font-size: 36px;
    font-weight: 800;
    letter-spacing: -0.03em;
    color: #111827;
    line-height: 1;
  }
  .stat-meta {
    font-size: 13px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 4px;
    color: var(--muted);
  }

  /* Charts Section */
  .charts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
    gap: 24px;
    margin-bottom: 32px;
  }
  .chart-card {
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 24px;
    box-shadow: var(--card-shadow);
  }
  .chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
  }
  .chart-title {
    font-size: 16px;
    font-weight: 700;
    margin: 0;
    color: #111827;
    display: flex;
    align-items: center;
    gap: 8px;
    letter-spacing: -0.01em;
  }
  .chart-title i {
    font-size: 18px;
    color: var(--primary);
  }

  /* Tables Section */
  .tables-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
    gap: 24px;
    margin-bottom: 32px;
  }
  .table-card {
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: var(--card-shadow);
    display: flex;
    flex-direction: column;
  }
  .table-card-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .table-card-title {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: #111827;
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .table-card-title i {
    color: var(--primary);
  }
  .view-all-link {
    font-size: 13px;
    font-weight: 600;
    color: var(--primary);
    text-decoration: none;
    transition: color 0.2s;
  }
  .view-all-link:hover {
    color: var(--primary-600);
    text-decoration: underline;
  }

  /* Premium Tables Layout */
  table {
    width: 100%;
    border-collapse: collapse;
  }
  table th {
    padding: 12px 24px;
    text-align: left;
    font-size: 11px;
    font-weight: 700;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    border-bottom: 1px solid var(--border);
    background: rgba(0, 0, 0, 0.01);
  }
  table td {
    padding: 16px 24px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.02);
    font-size: 14px;
  }
  table tbody tr {
    transition: background-color 0.2s;
  }
  table tbody tr:hover {
    background-color: rgba(79, 70, 229, 0.02);
  }
  table tbody tr:last-child td {
    border-bottom: none;
  }

  /* Avatar / Info */
  .info-cell {
    display: flex;
    align-items: center;
    gap: 12px;
  }
  .avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 13px;
    background: linear-gradient(135deg, #4f46e5, #3b82f6);
  }
  .avatar.business {
    background: linear-gradient(135deg, #7c3aed, #a78bfa);
    border-radius: 8px;
  }
  .avatar.admin {
    background: linear-gradient(135deg, #374151, #4b5563);
  }
  .details {
    display: flex;
    flex-direction: column;
  }
  .details-title {
    font-weight: 600;
    color: #111827;
  }
  .details-subtitle {
    font-size: 12px;
    color: var(--muted);
    margin-top: 2px;
  }

  /* Badges */
  .badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 9999px;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
  }
  .badge-success {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success);
  }
  .badge-danger {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger);
  }
  .badge-info {
    background: rgba(59, 130, 246, 0.1);
    color: var(--info);
  }

  .empty-state {
    padding: 48px;
    text-align: center;
    color: var(--muted);
  }
  .empty-state i {
    font-size: 40px;
    opacity: 0.3;
    margin-bottom: 12px;
  }

  @media (max-width: 1024px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
  }
  @media (max-width: 640px) {
    .stats-grid { grid-template-columns: 1fr; }
    .charts-grid { grid-template-columns: 1fr; }
    .tables-grid { grid-template-columns: 1fr; }
    .page-header { flex-direction: column; align-items: flex-start; }
  }
</style>

<!-- Page Header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Dashboard Overview</h1>
    <p class="page-subtitle">Welcome back, {{ Auth::guard('admin')->user()->name }}. Here is your system health summary.</p>
  </div>
  <div>
    <div class="status-pill">
      <span class="status-dot"></span>
      System Online
    </div>
  </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
  <!-- Total Users -->
  <div class="stat-card indigo">
    <div class="stat-header">
      <span class="stat-label">Total Users</span>
      <div class="stat-icon-wrapper">
        <i class="fas fa-users"></i>
      </div>
    </div>
    <div class="stat-body">
      <span class="stat-number">{{ $stats['total_users'] }}</span>
      <span class="stat-meta"><i class="fas fa-calendar-alt mr-1"></i> System-wide accounts</span>
    </div>
  </div>

  <!-- Active Users -->
  <div class="stat-card emerald">
    <div class="stat-header">
      <span class="stat-label">Active Users</span>
      <div class="stat-icon-wrapper">
        <i class="fas fa-user-check"></i>
      </div>
    </div>
    <div class="stat-body">
      <span class="stat-number">{{ $stats['active_users'] }}</span>
      <span class="stat-meta" style="color: var(--success);">
        <i class="fas fa-arrow-up mr-1"></i> {{ round(($stats['active_users'] / max($stats['total_users'], 1)) * 100) }}% Active Ratio
      </span>
    </div>
  </div>

  <!-- Suspended Users -->
  <div class="stat-card rose">
    <div class="stat-header">
      <span class="stat-label">Inactive Users</span>
      <div class="stat-icon-wrapper">
        <i class="fas fa-user-minus"></i>
      </div>
    </div>
    <div class="stat-body">
      <span class="stat-number">{{ $stats['inactive_users'] }}</span>
      <span class="stat-meta" style="color: var(--danger);">
        <i class="fas fa-ban mr-1"></i> {{ round(($stats['inactive_users'] / max($stats['total_users'], 1)) * 100) }}% Deactivated
      </span>
    </div>
  </div>

  <!-- Total Businesses / Tenants -->
  <div class="stat-card violet">
    <div class="stat-header">
      <span class="stat-label">Active Businesses</span>
      <div class="stat-icon-wrapper">
        <i class="fas fa-building"></i>
      </div>
    </div>
    <div class="stat-body">
      <span class="stat-number">{{ $stats['total_businesses'] }}</span>
      <span class="stat-meta"><i class="fas fa-chart-line mr-1"></i> Registered Tenants</span>
    </div>
  </div>

  <!-- Admin Users -->
  <div class="stat-card amber">
    <div class="stat-header">
      <span class="stat-label">System Admins</span>
      <div class="stat-icon-wrapper">
        <i class="fas fa-user-shield"></i>
      </div>
    </div>
    <div class="stat-body">
      <span class="stat-number">{{ $stats['total_admins'] }}</span>
      <span class="stat-meta"><i class="fas fa-circle mr-1" style="font-size: 8px; color: var(--success)"></i> {{ $stats['admins_active'] }} Active Admins</span>
    </div>
  </div>

  <!-- 2FA Enabled -->
  <div class="stat-card cyan">
    <div class="stat-header">
      <span class="stat-label">2FA Protected</span>
      <div class="stat-icon-wrapper">
        <i class="fas fa-shield-alt"></i>
      </div>
    </div>
    <div class="stat-body">
      <span class="stat-number">{{ $twoFactorStats['users_2fa_enabled'] + $twoFactorStats['admins_2fa_enabled'] }}</span>
      <span class="stat-meta" style="color: var(--info);"><i class="fas fa-lock mr-1"></i> Two-Factor Enabled</span>
    </div>
  </div>
</div>

<!-- Charts Section -->
<div class="charts-grid">
  <!-- Users Growth Chart -->
  <div class="chart-card">
    <div class="chart-header">
      <h3 class="chart-title">
        <i class="fas fa-chart-line"></i> Users Growth (Last 30 Days)
      </h3>
    </div>
    <div style="height: 300px;">
      <canvas id="usersGrowthChart"></canvas>
    </div>
  </div>

  <!-- Users by Role Chart -->
  <div class="chart-card">
    <div class="chart-header">
      <h3 class="chart-title">
        <i class="fas fa-users-cog"></i> Users by Role Distribution
      </h3>
    </div>
    <div style="height: 300px;">
      <canvas id="usersByRoleChart"></canvas>
    </div>
  </div>
</div>

<!-- Business Status Chart -->
<div class="chart-card" style="margin-bottom: 32px;">
  <div class="chart-header">
    <h3 class="chart-title">
      <i class="fas fa-chart-bar"></i> Business Status Overview
    </h3>
  </div>
  <div style="height: 300px;">
    <canvas id="businessStatusChart"></canvas>
  </div>
</div>

<!-- Tables Section -->
<div class="tables-grid">
  <!-- Recent Users Table -->
  <div class="table-card">
    <div class="table-card-header">
      <h3 class="table-card-title">
        <i class="fas fa-user-tie"></i> Recent Registered Users
      </h3>
      <a href="{{ route('admin.users.index') }}" class="view-all-link">View All</a>
    </div>
    <div style="overflow-x: auto;">
      <table>
        <thead>
          <tr>
            <th>User</th>
            <th>Role</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse($recentUsers as $user)
            <tr>
              <td>
                <div class="info-cell">
                  <div class="avatar">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                  </div>
                  <div class="details">
                    <span class="details-title">{{ $user->name }}</span>
                    <span class="details-subtitle">{{ $user->email }}</span>
                  </div>
                </div>
              </td>
              <td>
                <span class="badge badge-info">
                  <i class="fas fa-tag mr-1" style="font-size: 10px;"></i>
                  {{ ucfirst($user->role->name ?? 'Staff') }}
                </span>
              </td>
              <td>
                @if($user->is_active)
                  <span class="badge badge-success"><i class="fas fa-check-circle mr-1" style="font-size: 10px;"></i> Active</span>
                @else
                  <span class="badge badge-danger"><i class="fas fa-ban mr-1" style="font-size: 10px;"></i> Suspended</span>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="3">
                <div class="empty-state">
                  <i class="fas fa-inbox"></i>
                  <p>No recent user registrations found.</p>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <!-- Recent Businesses Table -->
  <div class="table-card">
    <div class="table-card-header">
      <h3 class="table-card-title">
        <i class="fas fa-building"></i> Recent Businesses (Tenants)
      </h3>
      <a href="{{ route('admin.businesses.index') }}" class="view-all-link">View All</a>
    </div>
    <div style="overflow-x: auto;">
      <table>
        <thead>
          <tr>
            <th>Business / Owner</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse($recentBusinesses as $business)
            <tr>
              <td>
                <div class="info-cell">
                  <div class="avatar business">
                    {{ strtoupper(substr($business->name, 0, 2)) }}
                  </div>
                  <div class="details">
                    <span class="details-title">{{ $business->name }}</span>
                    <span class="details-subtitle">Owner: {{ $business->owner->name ?? 'N/A' }}</span>
                  </div>
                </div>
              </td>
              <td>
                @if($business->is_active)
                  <span class="badge badge-success"><i class="fas fa-check-circle mr-1" style="font-size: 10px;"></i> Active</span>
                @else
                  <span class="badge badge-danger"><i class="fas fa-ban mr-1" style="font-size: 10px;"></i> Suspended</span>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="2">
                <div class="empty-state">
                  <i class="fas fa-inbox"></i>
                  <p>No registered businesses found.</p>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Admin Activity Table -->
<div class="table-card" style="margin-bottom: 32px;">
  <div class="table-card-header">
    <h3 class="table-card-title">
      <i class="fas fa-user-secret"></i> Administrator Activity Log
    </h3>
  </div>
  <div style="overflow-x: auto;">
    <table>
      <thead>
        <tr>
          <th>Administrator</th>
          <th>Last Login Time</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse($adminActivity as $adm)
          <tr>
            <td>
              <div class="info-cell">
                <div class="avatar admin">
                  {{ strtoupper(substr($adm->name, 0, 2)) }}
                </div>
                <div class="details">
                  <span class="details-title">{{ $adm->name }}</span>
                  <span class="details-subtitle">{{ $adm->email }}</span>
                </div>
              </div>
            </td>
            <td>
              <span class="text-gray-600 font-semibold" style="font-size: 13px;">
                <i class="far fa-clock mr-1"></i>
                {{ $adm->last_login_at ? $adm->last_login_at->format('M d, Y - g:i A') : 'Never Logged In' }}
              </span>
            </td>
            <td>
              @if($adm->is_active)
                <span class="badge badge-success"><i class="fas fa-check-circle mr-1" style="font-size: 10px;"></i> Active</span>
              @else
                <span class="badge badge-danger"><i class="fas fa-ban mr-1" style="font-size: 10px;"></i> Inactive</span>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="3">
              <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>No administrator activity logged.</p>
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@endsection

@section('scripts')
<script>
  // Setup Chart.js helper styles
  Chart.defaults.color = '#6b7280';
  Chart.defaults.font.family = "system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif";

  // Users Growth Chart
  const usersGrowthCtx = document.getElementById('usersGrowthChart').getContext('2d');
  const usersGrowthGradient = usersGrowthCtx.createLinearGradient(0, 0, 0, 300);
  usersGrowthGradient.addColorStop(0, 'rgba(79, 70, 229, 0.25)');
  usersGrowthGradient.addColorStop(1, 'rgba(79, 70, 229, 0.0)');

  new Chart(usersGrowthCtx, {
    type: 'line',
    data: {
      labels: @json($usersGrowth->pluck('date')),
      datasets: [{
        label: 'New Registrations',
        data: @json($usersGrowth->pluck('count')),
        borderColor: '#4f46e5',
        borderWidth: 3,
        backgroundColor: usersGrowthGradient,
        tension: 0.38,
        fill: true,
        pointRadius: 4,
        pointBackgroundColor: '#4f46e5',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointHoverRadius: 6
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false }
      },
      scales: {
        y: { 
          beginAtZero: true, 
          grid: { color: 'rgba(243, 244, 246, 0.8)' },
          border: { dash: [4, 4] }
        },
        x: { 
          grid: { display: false }
        }
      }
    }
  });

  // Users by Role Chart
  const usersByRoleCtx = document.getElementById('usersByRoleChart').getContext('2d');
  new Chart(usersByRoleCtx, {
    type: 'doughnut',
    data: {
      labels: @json($usersByRole->pluck('role')),
      datasets: [{
        data: @json($usersByRole->pluck('count')),
        backgroundColor: [
          '#4f46e5', '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#06b6d4'
        ],
        borderWidth: 3,
        borderColor: '#ffffff',
        hoverOffset: 4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { 
          position: 'bottom', 
          labels: { 
            padding: 20,
            usePointStyle: true,
            pointStyle: 'circle',
            font: { weight: '600', size: 12 }
          } 
        }
      },
      cutout: '70%'
    }
  });

  // Business Status Chart
  const businessStatusCtx = document.getElementById('businessStatusChart').getContext('2d');
  new Chart(businessStatusCtx, {
    type: 'bar',
    data: {
      labels: @json($businessStatus->pluck('status')),
      datasets: [{
        label: 'Businesses count',
        data: @json($businessStatus->pluck('count')),
        backgroundColor: ['#10b981', '#f43f5e'],
        borderRadius: 8,
        borderSkipped: false,
        maxBarThickness: 50
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false }
      },
      scales: {
        y: { 
          beginAtZero: true, 
          grid: { color: 'rgba(243, 244, 246, 0.8)' },
          border: { dash: [4, 4] }
        },
        x: { 
          grid: { display: false }
        }
      }
    }
  });
</script>
@endsection