<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - {{ auth()->user()->business->name }}</title>
  
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

     @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            height: 100%;
            width: 100%;
        }
        
        body {
            background-color: #f3f4f6;
        }
        
        body > div {
            display: flex;
            height: 100vh;
            width: 100%;
            overflow: hidden;
        }
        
        .sidebar {
            flex: 0 0 16rem;
            width: 16rem;
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
            transition: flex 0.3s ease-in-out, width 0.3s ease-in-out;
            position: relative;
            z-index: 20;
        }
        
        .sidebar + div {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            min-width: 0;
            overflow: hidden;
            position: relative;
            z-index: 10;
        }
        
        .sidebar.collapsed {
            flex: 0 0 5rem;
            width: 5rem;
        }
        
        .sidebar.collapsed .sidebar-text {
            display: none;
        }
        
        .sidebar.collapsed .sidebar-icon-only {
            justify-content: center;
        }
        
        .sidebar.collapsed:hover {
            flex: 0 0 16rem;
            width: 16rem;
            position: absolute;
            left: 0;
            top: 0;
            z-index: 50;
        }
        
        .sidebar.collapsed:hover .sidebar-text {
            display: inline;
        }

        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                left: 0;
                top: 0;
                z-index: 40;
                height: 100vh;
            }
            
            .sidebar.hidden-mobile {
                transform: translateX(-100%);
            }
        }
        .dropdown:hover .dropdown-menu {
            display: block;
        }
        /* Accordion Styles */
        .accordion-header {
            cursor: pointer;
            user-select: none;
            transition: all 0.3s ease;
        }
        .accordion-header:hover {
            background-color: rgba(79, 70, 229, 0.3);
        }
        .accordion-icon {
            transition: transform 0.3s ease;
        }
        .accordion-icon.collapsed {
            transform: rotate(-90deg);
        }
        .accordion-content {
            max-height: 500px;
            overflow: hidden;
            transition: max-height 0.3s ease, opacity 0.3s ease;
            opacity: 1;
        }
        .accordion-content.collapsed {
            max-height: 0;
            opacity: 0;
        }
        
        /* Animation for success/alert messages */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fadeIn {
            animation: fadeIn 0.3s ease-in-out;
        }
        
        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: translateY(0);
            }
            to {
                opacity: 0;
                transform: translateY(-10px);
            }
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes slideOut {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(100%);
            }
        }
        
        .animate-slideIn {
            animation: slideIn 0.3s ease-in-out;
        }
        
        /* Fullscreen Mode */
        body.fullscreen-mode {
            overflow: hidden;
        }
        
        body.fullscreen-mode header {
            display: none;
        }
        
        body.fullscreen-mode .flex-1 {
            width: 100%;
        }
        
        body.fullscreen-mode main {
            height: 100vh;
            padding: 0 !important;
        }
        
        /* Floating exit button in fullscreen mode */
        .fullscreen-exit-btn {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 100;
            padding: 12px 16px;
            background-color: rgba(239, 68, 68, 0.9);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        
        .fullscreen-exit-btn:hover {
            background-color: rgba(220, 38, 38, 1);
        }
        
        body.fullscreen-mode .fullscreen-exit-btn {
            display: block;
        }
    </style>
    @stack('styles')
</head>
<body class="bg-gray-100">
    <div class="flex h-screen overflow-hidden">
        
        <!-- SIDEBAR -->
        <aside id="sidebar" class="sidebar bg-indigo-900 text-white">
            <!-- Logo -->
            <div class="p-4 border-b border-indigo-800">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="fas fa-boxes text-2xl text-yellow-400"></i>
                        <span class="sidebar-text ml-2 text-lg font-bold truncate">{{ auth()->user()->business->name }}</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <!-- Toggle Sidebar Collapse Button (Desktop Only) -->
                        <button onclick="toggleSidebarCollapse()" class="hidden md:block text-white hover:text-yellow-400 transition">
                            <i class="fas fa-chevron-left text-lg"></i>
                        </button>
                        <!-- Close Sidebar Button (Mobile Only) -->
                        <button onclick="toggleSidebar()" class="md:hidden text-white">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>
                <p class="sidebar-text text-xs text-indigo-300 mt-1">{{ auth()->user()->role->display_name }}</p>
            </div>

            <!-- Navigation -->
            <nav class="p-4 space-y-1">
                <!-- Dashboard -->
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 p-3 rounded-lg {{ request()->routeIs('dashboard') ? 'bg-indigo-800' : 'hover:bg-indigo-800' }} sidebar-icon-only">
                    <i class="fas fa-home text-lg flex-shrink-0"></i>
                    <span class="sidebar-text">Dashboard</span>
                </a>

                <!-- Quick Access -->
                <a href="{{ route('pos.index') }}" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-indigo-800 text-green-300 font-semibold sidebar-icon-only">
                    <i class="fas fa-cash-register text-lg flex-shrink-0"></i>
                    <span class="sidebar-text">POS - New Sale</span>
                </a>

                <!-- Products Accordion -->
                <div class="accordion-group">
                    <div onclick="toggleAccordion(event, 'products')" class="accordion-header flex items-center justify-between space-x-3 p-3 rounded-lg {{ request()->routeIs('products.*') ? 'bg-indigo-700' : '' }} sidebar-icon-only">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-box text-lg flex-shrink-0"></i>
                            <span class="sidebar-text">Products</span>
                        </div>
                        <i class="fas fa-chevron-down accordion-icon text-xs sidebar-text {{ request()->routeIs('products.*') ? '' : 'collapsed' }}"></i>
                    </div>
                    <div id="products" class="accordion-content {{ request()->routeIs('products.*') ? '' : 'collapsed' }} space-y-1">
                        <a href="{{ route('products.index') }}" class="flex items-center space-x-3 p-3 pl-12 rounded-lg {{ request()->routeIs('products.index') ? 'bg-indigo-800' : 'hover:bg-indigo-800' }}">
                            <i class="fas fa-list text-sm flex-shrink-0"></i>
                            <span class="sidebar-text">All Products</span>
                        </a>
                        <a href="{{ route('products.create') }}" class="flex items-center space-x-3 p-3 pl-12 rounded-lg {{ request()->routeIs('products.create') ? 'bg-indigo-800' : 'hover:bg-indigo-800' }}">
                            <i class="fas fa-plus text-sm flex-shrink-0"></i>
                            <span class="sidebar-text">Add Product</span>
                        </a>
                        <a href="{{ route('products.expired') }}" class="flex items-center space-x-3 p-3 pl-12 rounded-lg {{ request()->routeIs('products.expired') ? 'bg-indigo-800' : 'hover:bg-indigo-800' }}">
                            <i class="fas fa-exclamation-triangle text-sm text-red-400 flex-shrink-0"></i>
                            <span class="sidebar-text">Expired Products</span>
                        </a>
                        <a href="{{ route('products.expiring-soon') }}" class="flex items-center space-x-3 p-3 pl-12 rounded-lg {{ request()->routeIs('products.expiring-soon') ? 'bg-indigo-800' : 'hover:bg-indigo-800' }}">
                            <i class="fas fa-clock text-sm text-yellow-400 flex-shrink-0"></i>
                            <span class="sidebar-text">Expiring Soon</span>
                        </a>
                    </div>
                </div>

                <!-- Sales Accordion -->
                <div class="accordion-group">
                    <div onclick="toggleAccordion(event, 'sales')" class="accordion-header flex items-center justify-between space-x-3 p-3 rounded-lg {{ request()->routeIs('sales.*') ? 'bg-indigo-700' : '' }} sidebar-icon-only">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-shopping-cart text-lg flex-shrink-0"></i>
                            <span class="sidebar-text">Sales</span>
                        </div>
                        <i class="fas fa-chevron-down accordion-icon text-xs sidebar-text {{ request()->routeIs('sales.*') ? '' : 'collapsed' }}"></i>
                    </div>
                    <div id="sales" class="accordion-content {{ request()->routeIs('sales.*') ? '' : 'collapsed' }} space-y-1">
                        <a href="{{ route('sales.index') }}" class="flex items-center space-x-3 p-3 pl-12 rounded-lg hover:bg-indigo-800">
                            <i class="fas fa-list text-sm flex-shrink-0"></i>
                            <span class="sidebar-text">All Sales</span>
                        </a>
                        <a href="{{ route('sales.today') }}" class="flex items-center space-x-3 p-3 pl-12 rounded-lg hover:bg-indigo-800">
                            <i class="fas fa-calendar-day text-sm flex-shrink-0"></i>
                            <span class="sidebar-text">Today's Sales</span>
                        </a>
                        <a href="{{ route('sales.weekly') }}" class="flex items-center space-x-3 p-3 pl-12 rounded-lg hover:bg-indigo-800">
                            <i class="fas fa-calendar-week text-sm flex-shrink-0"></i>
                            <span class="sidebar-text">Weekly Sales</span>
                        </a>
                        <a href="{{ route('sales.monthly') }}" class="flex items-center space-x-3 p-3 pl-12 rounded-lg hover:bg-indigo-800">
                            <i class="fas fa-calendar-alt text-sm flex-shrink-0"></i>
                            <span class="sidebar-text">Monthly Sales</span>
                        </a>
                    </div>
                </div>

                <!-- Invoices/Credit Sales -->
                <a href="{{route('invoices.index')}}" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-indigo-800 sidebar-icon-only">
                    <i class="fas fa-file-invoice-dollar text-lg flex-shrink-0"></i>
                    <span class="sidebar-text">Invoices/Credit Sales</span>
                </a>

                <!-- Inventory Accordion -->
                <div class="accordion-group">
                    <div onclick="toggleAccordion(event, 'inventory')" class="accordion-header flex items-center justify-between space-x-3 p-3 rounded-lg {{ request()->routeIs('inventory.*') ? 'bg-indigo-700' : '' }} sidebar-icon-only">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-warehouse text-lg flex-shrink-0"></i>
                            <span class="sidebar-text">Inventory</span>
                        </div>
                        <i class="fas fa-chevron-down accordion-icon text-xs sidebar-text {{ request()->routeIs('inventory.*') ? '' : 'collapsed' }}"></i>
                    </div>
                    <div id="inventory" class="accordion-content {{ request()->routeIs('inventory.*') ? '' : 'collapsed' }} space-y-1">
                        <a href="{{ route('inventory.index') }}" class="flex items-center space-x-3 p-3 pl-12 rounded-lg {{ request()->routeIs('inventory.index') ? 'bg-indigo-800' : 'hover:bg-indigo-800' }}">
                            <i class="fas fa-boxes text-sm flex-shrink-0"></i>
                            <span class="sidebar-text">Inventory Management</span>
                        </a>
                        <a href="{{ route('inventory.activities') }}" class="flex items-center space-x-3 p-3 pl-12 rounded-lg {{ request()->routeIs('inventory.activities') ? 'bg-indigo-800' : 'hover:bg-indigo-800' }}">
                            <i class="fas fa-history text-sm flex-shrink-0"></i>
                            <span class="sidebar-text">Inventory Activities</span>
                        </a>
                        <a href="{{ route('stock-taking.index') }}" class="flex items-center space-x-3 p-3 pl-12 rounded-lg {{ request()->routeIs('stock-taking.*') ? 'bg-indigo-800' : 'hover:bg-indigo-800' }}">
                            <i class="fas fa-list-check text-sm flex-shrink-0"></i>
                            <span class="sidebar-text">Stock Taking</span>
                        </a>
                        {{-- Period Closing History - Disabled for later activation --}}
                        {{-- <a href="{{ route('inventory.periods') }}" class="flex items-center space-x-3 p-3 pl-12 rounded-lg {{ request()->routeIs('inventory.periods', 'inventory.reconciliation') ? 'bg-indigo-800' : 'hover:bg-indigo-800' }}">
                            <i class="fas fa-balance-scale text-sm flex-shrink-0"></i>
                            <span class="sidebar-text">Period Closing History</span>
                        </a> --}}
                    </div>
                </div>

                <!-- Customers -->
                <a href="{{ route('customers.index') }}" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-indigo-800 sidebar-icon-only">
                    <i class="fas fa-users text-lg flex-shrink-0"></i>
                    <span class="sidebar-text">Customers</span>
                </a>

                <!-- Suppliers -->
                <a href="{{ route('suppliers.index') }}" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-indigo-800 sidebar-icon-only">
                    <i class="fas fa-truck text-lg flex-shrink-0"></i>
                    <span class="sidebar-text">Suppliers</span>
                </a>

                <!-- Expenses Accordion -->
                <div class="accordion-group">
                    <div onclick="toggleAccordion(event, 'expenses')" class="accordion-header flex items-center justify-between space-x-3 p-3 rounded-lg {{ request()->routeIs('expenses.*') ? 'bg-indigo-700' : '' }} sidebar-icon-only">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-wallet text-lg flex-shrink-0"></i>
                            <span class="sidebar-text">Expenses</span>
                        </div>
                        <i class="fas fa-chevron-down accordion-icon text-xs sidebar-text {{ request()->routeIs('expenses.*') ? '' : 'collapsed' }}"></i>
                    </div>
                    <div id="expenses" class="accordion-content {{ request()->routeIs('expenses.*') ? '' : 'collapsed' }} space-y-1">
                        <a href="{{ route('expenses.create') }}" class="flex items-center space-x-3 p-3 pl-12 rounded-lg hover:bg-indigo-800">
                            <i class="fas fa-plus text-sm flex-shrink-0"></i>
                            <span class="sidebar-text">Record Expense</span>
                        </a>
                        <a href="{{ route('expenses.index') }}" class="flex items-center space-x-3 p-3 pl-12 rounded-lg hover:bg-indigo-800">
                            <i class="fas fa-list text-sm flex-shrink-0"></i>
                            <span class="sidebar-text">All Expenses</span>
                        </a>
                        <a href="{{ route('expenses.today') }}" class="flex items-center space-x-3 p-3 pl-12 rounded-lg hover:bg-indigo-800">
                            <i class="fas fa-calendar-day text-sm flex-shrink-0"></i>
                            <span class="sidebar-text">Today's Expenses</span>
                        </a>
                        <a href="{{ route('expenses.weekly') }}" class="flex items-center space-x-3 p-3 pl-12 rounded-lg hover:bg-indigo-800">
                            <i class="fas fa-calendar-week text-sm flex-shrink-0"></i>
                            <span class="sidebar-text">Weekly</span>
                        </a>
                        <a href="{{ route('expenses.monthly') }}" class="flex items-center space-x-3 p-3 pl-12 rounded-lg hover:bg-indigo-800">
                            <i class="fas fa-calendar-alt text-sm flex-shrink-0"></i>
                            <span class="sidebar-text">Monthly</span>
                        </a>
                    </div>
                </div>

                <!-- Reports Accordion -->
                <div class="accordion-group">
                    <div onclick="toggleAccordion(event, 'reports')" class="accordion-header flex items-center justify-between space-x-3 p-3 rounded-lg {{ request()->routeIs('reports.*', 'dashboard.annual', 'profit.*') ? 'bg-indigo-700' : '' }} sidebar-icon-only">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-chart-bar text-lg flex-shrink-0"></i>
                            <span class="sidebar-text">Reports</span>
                        </div>
                        <i class="fas fa-chevron-down accordion-icon text-xs sidebar-text {{ request()->routeIs('reports.*', 'dashboard.annual', 'profit.*') ? '' : 'collapsed' }}"></i>
                    </div>
                    <div id="reports" class="accordion-content {{ request()->routeIs('reports.*', 'dashboard.annual', 'profit.*') ? '' : 'collapsed' }} space-y-1">
                        <a href="{{ route('dashboard.annual') }}" class="flex items-center space-x-3 p-3 pl-12 rounded-lg hover:bg-indigo-800">
                            <i class="fas fa-chart-line text-sm flex-shrink-0"></i>
                            <span class="sidebar-text">Annual Performance</span>
                        </a>
                        <a href="{{ route('profit.index') }}" class="flex items-center space-x-3 p-3 pl-12 rounded-lg hover:bg-indigo-800">
                            <i class="fas fa-chart-pie text-sm flex-shrink-0"></i>
                            <span class="sidebar-text">Profit Report</span>
                        </a>
                        <a href="{{ route('reports.sales') }}" class="flex items-center space-x-3 p-3 pl-12 rounded-lg hover:bg-indigo-800">
                            <i class="fas fa-chart-line text-sm flex-shrink-0"></i>
                            <span class="sidebar-text">Sales Report</span>
                        </a>
                        <a href="{{ route('reports.products') }}" class="flex items-center space-x-3 p-3 pl-12 rounded-lg hover:bg-indigo-800">
                            <i class="fas fa-box-open text-sm flex-shrink-0"></i>
                            <span class="sidebar-text">Product Report</span>
                        </a>
                        <a href="{{ route('reports.top-selling') }}" class="flex items-center space-x-3 p-3 pl-12 rounded-lg hover:bg-indigo-800">
                            <i class="fas fa-fire text-sm text-orange-400 flex-shrink-0"></i>
                            <span class="sidebar-text">Top Selling</span>
                        </a>
                        <a href="{{ route('reports.custom') }}" class="flex items-center space-x-3 p-3 pl-12 rounded-lg hover:bg-indigo-800">
                            <i class="fas fa-calendar-check text-sm flex-shrink-0"></i>
                            <span class="sidebar-text">Custom Range</span>
                        </a>
                    </div>
                </div>

                @if(auth()->user()->isOwner() || auth()->user()->role->name === 'manager')
                <a href="{{ route('staff.index') }}" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-indigo-800 sidebar-icon-only">
                    <i class="fas fa-user-tie text-lg flex-shrink-0"></i>
                    <span class="sidebar-text">Staff Management</span>
                </a>
                @endif

                @if(auth()->user()->isOwner())
                <a href="{{ route('settings.index') }}" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-indigo-800 sidebar-icon-only">
                    <i class="fas fa-cog text-lg flex-shrink-0"></i>
                    <span class="sidebar-text">Settings</span>
                </a>
                @endif
            </nav>

            <!-- User Profile & Logout -->
            <div class="p-4 border-t border-indigo-800 mt-auto">
            <a href="{{ route('owner.profile.edit') }}" class="flex items-center space-x-3 mb-3 hover:opacity-90 transition sidebar-icon-only">
  @if(auth()->user()?->profile_image)
    <img
      src="{{ route('owner.profile.avatar') }}"
      alt="Profile"
      class="w-10 h-10 rounded-full object-cover border border-indigo-600 flex-shrink-0"
    />
  @else
    <div class="w-10 h-10 bg-indigo-700 rounded-full flex items-center justify-center flex-shrink-0">
      <span class="text-lg font-bold">
        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
      </span>
    </div>
  @endif

  <div class="flex-1 sidebar-text">
    <p class="text-sm font-semibold truncate">{{ auth()->user()->name }}</p>
    <p class="text-xs text-indigo-300 truncate">{{ auth()->user()->email }}</p>
  </div>
</a>



                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-center space-x-2 p-2 bg-red-600 rounded-lg hover:bg-red-700 sidebar-icon-only">
                        <i class="fas fa-sign-out-alt flex-shrink-0"></i>
                        <span class="sidebar-text">Logout</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <div class="flex-1 flex flex-col overflow-hidden">
            
            <!-- Top Bar -->
            <header class="bg-white shadow-sm z-10">
                <div class="flex items-center justify-between p-4">
                    <button onclick="toggleSidebar()" class="md:hidden text-gray-600">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800">
                        @yield('page-title', 'Dashboard')
                    </h1>
                     
                    <div class="flex items-center space-x-4">
                        <span class="hidden md:inline text-sm text-gray-600">
                            <i class="fas fa-calendar mr-1"></i>
                            {{ now()->format('D, M d, Y') }}
                        </span>



                        <a href="{{ route('pos.index') }}" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            <i class="fas fa-cash-register mr-1"></i>
                            <span class="hidden md:inline">POS</span>
                        </a>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto p-4 md:p-6">
                
                <!-- Success/Error Messages -->
                @if(session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
                @endif

                @if(session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                </div>
                @endif

                @if($errors->any())
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-circle mr-2 mt-1"></i>
                        <div>
                            <p class="font-semibold">Please fix the following errors:</p>
                            <ul class="list-disc list-inside mt-2">
                                @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Page Content -->
                @yield('content')

            </main>
        </div>
    </div>



    <script>
    // ========================================
    // ✅ ACCORDION TOGGLE
    // ========================================
    function toggleAccordion(event, accordionId) {
        event.preventDefault();
        const content = document.getElementById(accordionId);
        const icon = event.currentTarget.querySelector('.accordion-icon');
        
        content.classList.toggle('collapsed');
        icon.classList.toggle('collapsed');
        
        // Save accordion state to localStorage
        const isCollapsed = content.classList.contains('collapsed');
        localStorage.setItem(`accordion-${accordionId}`, isCollapsed ? 'collapsed' : 'expanded');
    }

    // ========================================
    // ✅ RESPONSIVE SIDEBAR TOGGLE
    // ========================================
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('hidden-mobile');
        updateBodyScroll();
    }

    // ✅ Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = event.target.closest('button[onclick="toggleSidebar()"]');
        
        // Only apply on mobile screens
        if (window.innerWidth < 768) {
            // If click is not on sidebar and not on toggle button
            if (!sidebar.contains(event.target) && !toggleBtn) {
                sidebar.classList.add('hidden-mobile');
                updateBodyScroll();
            }
        }
    });

    // ✅ Close sidebar on mobile when clicking any link inside
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const sidebarLinks = sidebar.querySelectorAll('a, button[type="submit"]');
        
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 768) {
                    sidebar.classList.add('hidden-mobile');
                    updateBodyScroll();
                }
            });
        });
    });

    // ✅ Ensure sidebar is hidden on mobile on page load
    window.addEventListener('load', function() {
        const sidebar = document.getElementById('sidebar');
        if (window.innerWidth < 768) {
            sidebar.classList.add('hidden-mobile');
        }
    });

    // ✅ Handle window resize
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            const sidebar = document.getElementById('sidebar');
            if (window.innerWidth < 768) {
                sidebar.classList.add('hidden-mobile');
            } else {
                sidebar.classList.remove('hidden-mobile');
            }
            updateBodyScroll();
        }, 250);
    });

    // ✅ Prevent body scroll when sidebar is open on mobile
    function updateBodyScroll() {
        const sidebar = document.getElementById('sidebar');
        if (window.innerWidth < 768 && ! sidebar.classList.contains('hidden-mobile')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = 'auto';
        }
    }

    // ✅ Close sidebar with ESC key on mobile
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && window.innerWidth < 768) {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.add('hidden-mobile');
            updateBodyScroll();
        }
    });

    // ✅ TOGGLE SIDEBAR COLLAPSE (Manual)
    function toggleSidebarCollapse() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('collapsed');
        console.log('Sidebar toggle:', sidebar.classList.contains('collapsed') ? 'collapsed' : 'expanded');
    }

    // ✅ DETECT BROWSER NATIVE FULLSCREEN (F11 OR HTML5 API)
    function checkFullscreenState() {
        const isHTML5 = !!document.fullscreenElement;
        const isNative = Math.abs(screen.height - window.innerHeight) < 15 || 
                         window.matchMedia('(display-mode: fullscreen)').matches;
        const isFullscreen = isHTML5 || isNative;

        const sidebar = document.getElementById('sidebar');
        const header = document.querySelector('header');
        const main = document.querySelector('main');

        if (isFullscreen) {
            if (sidebar) sidebar.classList.add('hidden');
            if (header) header.classList.add('hidden');
            if (main) {
                main.classList.remove('p-4', 'md:p-6');
                main.classList.add('p-2');
            }
        } else {
            if (sidebar) {
                sidebar.classList.remove('hidden');
                if (window.innerWidth < 768) {
                    sidebar.classList.add('hidden-mobile');
                } else {
                    sidebar.classList.remove('hidden-mobile');
                }
            }
            if (header) header.classList.remove('hidden');
            if (main) {
                main.classList.add('p-4', 'md:p-6');
                main.classList.remove('p-2');
            }
        }
    }

    window.addEventListener('resize', checkFullscreenState);
    document.addEventListener('fullscreenchange', checkFullscreenState);

    // ✅ AJAX PAGE LOADING
    function loadPageViaAjax(url) {
        // Show loading state
        const main = document.querySelector('main');
        if (!main) return;
        
        main.style.opacity = '0.6';
        main.style.pointerEvents = 'none';

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.text();
        })
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            // Update main content
            const newMain = doc.querySelector('main');
            if (newMain) {
                main.innerHTML = newMain.innerHTML;
            }

            // Update page title
            const newTitle = doc.querySelector('title');
            if (newTitle) {
                document.title = newTitle.textContent;
            }

            // Update URL
            window.history.pushState({ path: url }, '', url);

            main.style.opacity = '1';
            main.style.pointerEvents = 'auto';

            // Re-attach click handlers for new content
            attachAjaxListeners();

            console.log('Page loaded via AJAX: ' + url);
        })
        .catch(error => {
            console.error('AJAX load failed:', error);
            main.style.opacity = '1';
            main.style.pointerEvents = 'auto';
            // Fallback to normal navigation
            window.location.href = url;
        });
    }

    // ✅ ATTACH AJAX CLICK LISTENERS
    function attachAjaxListeners() {
        // Intercept all internal links
        document.querySelectorAll('a[href]').forEach(link => {
            // Skip if already has listener
            if (link.dataset.ajaxAttached) return;
            
            const href = link.getAttribute('href');
            
            // Skip external links, anchors, special attributes
            if (!href || href.startsWith('http') || href.startsWith('mailto') || 
                href === '#' || link.hasAttribute('target') || link.hasAttribute('download')) {
                return;
            }

            // Skip logout form
            if (link.closest('form[action*="logout"]')) {
                return;
            }

            link.dataset.ajaxAttached = 'true';
            link.addEventListener('click', function(e) {
                e.preventDefault();
                loadPageViaAjax(this.getAttribute('href'));
            });
        });

        // Intercept form submissions (except logout)
        document.querySelectorAll('form').forEach(form => {
            if (form.dataset.ajaxAttached) return;
            if (form.action.includes('logout')) return;

            form.dataset.ajaxAttached = 'true';
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const url = this.action;

                fetch(url, {
                    method: this.method,
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    
                    const newMain = doc.querySelector('main');
                    const main = document.querySelector('main');
                    if (newMain && main) {
                        main.innerHTML = newMain.innerHTML;
                    }

                    const newTitle = doc.querySelector('title');
                    if (newTitle) {
                        document.title = newTitle.textContent;
                    }

                    window.history.pushState({ path: url }, '', url);
                    attachAjaxListeners();

                    console.log('Form submitted via AJAX');
                })
                .catch(error => {
                    console.error('Form submission failed:', error);
                    this.submit(); // Fallback
                });
            });
        });
    }

    // ✅ HANDLE BROWSER BACK/FORWARD
    window.addEventListener('popstate', function(event) {
        if (event.state && event.state.path) {
            loadPageViaAjax(event.state.path);
        }
    });

    // ✅ INITIALIZE STATE & LISTENER ON LOAD
    document.addEventListener('DOMContentLoaded', function() {
        attachAjaxListeners();
        checkFullscreenState();
    });

    // ✅ EXPAND SIDEBAR ON HOVER (when collapsed)
    window.addEventListener('load', function() {
        const sidebar = document.getElementById('sidebar');
        
        sidebar.addEventListener('mouseenter', function() {
            if (this.classList.contains('collapsed')) {
                this.classList.remove('collapsed');
            }
        });
        
        console.log('Sidebar hover listener initialized');
    });
</script>
</body>
</html>