<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Cashier Dashboard') - {{ auth()->user()->business->name }}</title>
   <!-- <script src="https://cdn.tailwindcss.com"></script>-->

 @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar {
            transition: transform 0.3s ease-in-out;
        }
        @media (max-width: 768px) {
            .sidebar.hidden-mobile {
                transform: translateX(-100%);
            }
        }
        /* Accordion Styles */
        .accordion-header {
            cursor: pointer;
            user-select: none;
            transition: all 0.3s ease;
        }
        .accordion-header:hover {
            background-color: rgba(120, 53, 15, 0.5);
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
    </style>
    @stack('styles')
</head>
<body class="bg-gray-100">
    <div class="flex h-screen overflow-hidden">
        
        <!-- SIDEBAR -->
        <aside id="sidebar" class="sidebar fixed md:relative z-20 w-64 bg-indigo-900 text-white overflow-y-auto h-screen">
            <!-- Logo -->
            <div class="p-4 border-b border-yellow-800">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="fas fa-cash-register text-2xl text-yellow-300"></i>
                        <span class="ml-2 text-lg font-bold">{{ auth()->user()->business->name }}</span>
                    </div>
                    <button onclick="toggleSidebar()" class="md:hidden text-white">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <p class="text-xs text-yellow-200 mt-1">💰 Cashier Portal</p>
            </div>

            <!-- Navigation -->
            <nav class="p-4 space-y-1">
                <!-- Dashboard -->
                <a href="{{ route('cashier.dashboard') }}" class="flex items-center space-x-3 p-3 rounded-lg {{ request()->routeIs('cashier.dashboard') ? 'bg-yellow-800' : 'hover:bg-yellow-800' }}">
                    <i class="fas fa-home text-lg"></i>
                    <span>My Dashboard</span>
                </a>

                <!-- My Profit -->
                <a href="{{ route('profit.index') }}" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-yellow-800">
                    <i class="fas fa-chart-pie text-lg"></i>
                    <span>My Profit</span>
                </a>

                <!-- NEW SALE (BIGGEST BUTTON) -->
                <a href="{{ route('pos.index') }}" class="flex items-center justify-center space-x-3 p-4 rounded-lg bg-green-600 hover:bg-green-700 text-white font-bold text-lg shadow-lg">
                    <i class="fas fa-plus-circle text-2xl"></i>
                    <span>NEW SALE</span>
                </a>

                <!-- Invoices/Credit Sales -->
                <a href="{{ route('cashier.invoices.index') }}" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-yellow-800">
                    <i class="fas fa-file-invoice-dollar text-lg"></i>
                    <span>Invoices</span>
                </a>

                <!-- My Sales Accordion -->
                <div class="accordion-group">
                    <div onclick="toggleAccordion(event, 'sales')" class="accordion-header flex items-center justify-between space-x-3 p-3 rounded-lg {{ request()->routeIs('sales.*') ? 'bg-yellow-700' : '' }}">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-receipt text-lg"></i>
                            <span>My Sales</span>
                        </div>
                        <i class="fas fa-chevron-down accordion-icon text-xs {{ request()->routeIs('sales.*') ? '' : 'collapsed' }}"></i>
                    </div>
                    <div id="sales" class="accordion-content {{ request()->routeIs('sales.*') ? '' : 'collapsed' }} space-y-1">
                        <a href="{{ route('sales.index') }}" class="flex items-center space-x-3 p-3 pl-12 rounded-lg hover:bg-yellow-800">
                            <i class="fas fa-list text-sm"></i>
                            <span>All My Sales</span>
                        </a>
                        <a href="{{ route('sales.today') }}" class="flex items-center space-x-3 p-3 pl-12 rounded-lg hover:bg-yellow-800">
                            <i class="fas fa-calendar-day text-sm"></i>
                            <span>Today's Sales</span>
                        </a>
                    </div>
                </div>

                <!-- Expenses Accordion -->
                <div class="accordion-group">
                    <div onclick="toggleAccordion(event, 'expenses')" class="accordion-header flex items-center justify-between space-x-3 p-3 rounded-lg {{ request()->routeIs('cashier.expenses.*') ? 'bg-yellow-700' : '' }}">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-wallet text-lg"></i>
                            <span>Expenses</span>
                        </div>
                        <i class="fas fa-chevron-down accordion-icon text-xs {{ request()->routeIs('cashier.expenses.*') ? '' : 'collapsed' }}"></i>
                    </div>
                    <div id="expenses" class="accordion-content {{ request()->routeIs('cashier.expenses.*') ? '' : 'collapsed' }} space-y-1">
                        <a href="{{ route('cashier.expenses.create') }}" class="flex items-center space-x-3 p-3 pl-12 rounded-lg hover:bg-yellow-800">
                            <i class="fas fa-plus text-sm"></i>
                            <span>Record Expense</span>
                        </a>
                    </div>
                </div>

                <!-- My Performance -->
                <a href="{{ route('cashier.performance') }}" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-yellow-800">
                    <i class="fas fa-chart-line text-lg"></i>
                    <span>My Performance</span>
                </a>

                <!-- Customers -->
                <a href="{{ route('customers.index') }}" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-yellow-800">
                    <i class="fas fa-users text-lg"></i>
                    <span>Customers</span>
                </a>

                <!-- Search Product -->
                <a href="{{ route('products.index') }}" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-yellow-800">
                    <i class="fas fa-search text-lg"></i>
                    <span>Search Product</span>
                </a>

                <!-- My Profile -->
                <a href="{{ route('profile.edit') }}" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-yellow-800">
                    <i class="fas fa-user-circle text-lg"></i>
                    <span>My Profile</span>
                </a>
            </nav>

            <!-- User Profile & Logout -->
            <div class="p-4 border-t border-yellow-800 mt-auto">
                <div class="flex items-center space-x-3 mb-3">
                    <div class="w-10 h-10 bg-yellow-800 rounded-full flex items-center justify-center">
                        <span class="text-lg font-bold">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-yellow-200">{{ auth()->user()->email }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-center space-x-2 p-2 bg-red-600 rounded-lg hover:bg-red-700">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
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
                        @yield('page-title', 'Cashier Dashboard')
                    </h1>
                    <div class="flex items-center space-x-4">
                        <span class="hidden md:inline text-sm text-gray-600">
                            <i class="fas fa-calendar mr-1"></i>
                            {{ now()->format('D, d M Y h:i A') }}
                        </span>
                        <a href="{{ route('pos.index') }}" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            <i class="fas fa-cash-register mr-1"></i>
                            <span class="hidden md:inline">NEW SALE</span>
                        </a>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto p-4 md:p-6">
                
                <!-- Success/Error Messages -->
                @if(session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded animate-pulse">
                    <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                </div>
                @endif

                @if(session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded animate-pulse">
                    <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
                </div>
                @endif

                @if(session('info'))
                <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-4 rounded">
                    <i class="fas fa-info-circle mr-2"></i>{{ session('info') }}
                </div>
                @endif

                @if(session('warning'))
                <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4 rounded">
                    <i class="fas fa-exclamation-triangle mr-2"></i>{{ session('warning') }}
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
        // ✅ SIDEBAR TOGGLE
        // ========================================
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('hidden-mobile');
        }

        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = event.target.closest('button');
            
            if (window.innerWidth < 768 && !sidebar.contains(event.target) && !toggleBtn) {
                sidebar.classList.add('hidden-mobile');
            }
        });

        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('[class*="bg-green-100"], [class*="bg-red-100"], [class*="bg-blue-100"], [class*="bg-yellow-100"]');
            alerts.forEach(alert => {
                if (alert.classList.contains('animate-pulse')) {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }
            });
        }, 5000);

        function checkFullscreenState() {
            const isHTML5 = !!document.fullscreenElement;
            const isNative = window.matchMedia('(display-mode: fullscreen)').matches;
            const isFullscreen = isHTML5 || isNative;

            const sidebar = document.getElementById('sidebar');
            const header = document.querySelector('header');
            const main = document.querySelector('main');

            if (isFullscreen) {
                if (sidebar) sidebar.classList.add('hidden');
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
        document.addEventListener('DOMContentLoaded', checkFullscreenState);
        // Initial run
        checkFullscreenState();
    </script>

    @stack('scripts')
</body>
</html>