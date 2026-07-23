<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uganda Inventory Management System - Manage Your Business Smartly</title>
   <!--<script src="https://cdn.tailwindcss.com"></script> -->

  @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .fade-in-up {
            animation: fadeInUp 0.8s ease-out;
        }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .hover-scale {
            transition: transform 0.3s ease;
        }
        .hover-scale:hover {
            transform: scale(1.05);
        }
        @keyframes modalFloat {
            0% {
                opacity: 0;
                transform: translateY(-40px) scale(0.95);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        .modal-float {
            animation: modalFloat 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg fixed w-full z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <i class="fas fa-boxes text-3xl text-indigo-600"></i>
                    <span class="ml-2 text-2xl font-bold text-gray-800">Uganda Inventory</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="#" onclick="openLoginModal(event)" class="px-4 py-2 text-indigo-600 hover:text-indigo-800 font-medium">
                        <i class="fas fa-sign-in-alt mr-1"></i> Login
                    </a>
                    <a href="#" onclick="openRegisterModal(event)" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium shadow-md hover-scale">
                        <i class="fas fa-rocket mr-1"></i> Get Started Free
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="gradient-bg pt-32 pb-20 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center fade-in-up">
                <h1 class="text-5xl md:text-6xl font-extrabold mb-6">
                    Manage Your Ugandan Business Inventory Like a Pro
                </h1>
                <p class="text-xl md:text-2xl mb-8 text-indigo-100">
                    Complete multi-location inventory management system designed specifically for Ugandan businesses
                </p>
                <div class="flex justify-center space-x-4">
                    <a href="#" onclick="openRegisterModal(event)" class="px-8 py-4 bg-white text-indigo-600 rounded-lg font-bold text-lg hover:bg-gray-100 shadow-xl hover-scale">
                        <i class="fas fa-rocket mr-2"></i> Start 30-Day Free Trial
                    </a>
                    <a href="#features" class="px-8 py-4 bg-indigo-500 text-white rounded-lg font-bold text-lg hover:bg-indigo-400 shadow-xl hover-scale">
                        <i class="fas fa-play-circle mr-2"></i> Learn More
                    </a>
                </div>
                <p class="mt-6 text-indigo-100">
                    <i class="fas fa-check-circle mr-2"></i> No credit card required
                    <i class="fas fa-check-circle ml-4 mr-2"></i> Setup in 2 minutes
                    <i class="fas fa-check-circle ml-4 mr-2"></i> Cancel anytime
                </p>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-12 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 text-center">
                <div class="fade-in-up">
                    <div class="text-4xl font-bold text-indigo-600">500+</div>
                    <div class="text-gray-600 mt-2">Businesses Trust Us</div>
                </div>
                <div class="fade-in-up" style="animation-delay: 0.1s;">
                    <div class="text-4xl font-bold text-indigo-600">50K+</div>
                    <div class="text-gray-600 mt-2">Products Managed</div>
                </div>
                <div class="fade-in-up" style="animation-delay: 0.2s;">
                    <div class="text-4xl font-bold text-indigo-600">99.9%</div>
                    <div class="text-gray-600 mt-2">Uptime Guarantee</div>
                </div>
                <div class="fade-in-up" style="animation-delay: 0.3s;">
                    <div class="text-4xl font-bold text-indigo-600">24/7</div>
                    <div class="text-gray-600 mt-2">Support Available</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">
                    Everything You Need to Manage Your Inventory
                </h2>
                <p class="text-xl text-gray-600">
                    Powerful features designed for Ugandan businesses
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="bg-white p-8 rounded-xl shadow-lg hover-scale">
                    <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-warehouse text-3xl text-indigo-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Multi-Location Management</h3>
                    <p class="text-gray-600">
                        Track inventory across multiple warehouses, shops, or stores in real-time. Perfect for businesses with branches in Kampala, Entebbe, and beyond.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="bg-white p-8 rounded-xl shadow-lg hover-scale">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-mobile-alt text-3xl text-green-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Mobile Money Integration</h3>
                    <p class="text-gray-600">
                        Accept payments via MTN Mobile Money, Airtel Money, and more. Built specifically for Uganda's payment ecosystem.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="bg-white p-8 rounded-xl shadow-lg hover-scale">
                    <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-chart-line text-3xl text-yellow-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Real-Time Reports</h3>
                    <p class="text-gray-600">
                        Get instant insights on sales, profits, low stock alerts, and financial reports. Make data-driven decisions.
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="bg-white p-8 rounded-xl shadow-lg hover-scale">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-receipt text-3xl text-red-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">URA Tax Compliance</h3>
                    <p class="text-gray-600">
                        Generate VAT reports, track taxes, and stay compliant with Uganda Revenue Authority requirements effortlessly.
                    </p>
                </div>

                <!-- Feature 5 -->
                <div class="bg-white p-8 rounded-xl shadow-lg hover-scale">
                    <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-users text-3xl text-purple-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Staff Management</h3>
                    <p class="text-gray-600">
                        Create multiple user accounts with different roles: Owner, Manager, Cashier, Accountant. Full access control.
                    </p>
                </div>

                <!-- Feature 6 -->
                <div class="bg-white p-8 rounded-xl shadow-lg hover-scale">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-bell text-3xl text-blue-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">SMS Alerts</h3>
                    <p class="text-gray-600">
                        Get notified via SMS for low stock, new orders, and important updates. Stay connected even offline.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Advantages Section -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">
                    Why Choose Uganda Inventory Management?
                </h2>
                <p class="text-xl text-gray-600">
                    Built by Ugandans, for Ugandans
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-white text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Designed for Uganda</h3>
                        <p class="text-gray-600">
                            Supports UGX currency, Mobile Money, local tax requirements, and works even with intermittent internet.
                        </p>
                    </div>
                </div>

                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-white text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Affordable Pricing</h3>
                        <p class="text-gray-600">
                            Starting from UGX 50,000/month. No hidden fees. Cancel anytime. Perfect for small to medium businesses.
                        </p>
                    </div>
                </div>

                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-white text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Easy to Use</h3>
                        <p class="text-gray-600">
                            No technical knowledge required. Simple interface in English. Setup in minutes, not hours.
                        </p>
                    </div>
                </div>

                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-white text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-xl font-bold text-gray-900 mb-2">24/7 Local Support</h3>
                        <p class="text-gray-600">
                            WhatsApp and phone support available. We speak your language and understand your business.
                        </p>
                    </div>
                </div>

                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-white text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Secure & Reliable</h3>
                        <p class="text-gray-600">
                            Your data is encrypted and backed up daily. 99.9% uptime guarantee. Bank-level security.
                        </p>
                    </div>
                </div>

                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-white text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Regular Updates</h3>
                        <p class="text-gray-600">
                            New features added monthly based on user feedback. Always improving, always free updates.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Perfect For Section -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">
                    Perfect For All Ugandan Businesses
                </h2>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div class="bg-white p-6 rounded-lg shadow text-center hover-scale">
                    <i class="fas fa-laptop text-4xl text-indigo-600 mb-3"></i>
                    <h4 class="font-bold text-gray-900">Electronics</h4>
                </div>
                <div class="bg-white p-6 rounded-lg shadow text-center hover-scale">
                    <i class="fas fa-tshirt text-4xl text-pink-600 mb-3"></i>
                    <h4 class="font-bold text-gray-900">Fashion</h4>
                </div>
                <div class="bg-white p-6 rounded-lg shadow text-center hover-scale">
                    <i class="fas fa-shopping-cart text-4xl text-green-600 mb-3"></i>
                    <h4 class="font-bold text-gray-900">Supermarkets</h4>
                </div>
                <div class="bg-white p-6 rounded-lg shadow text-center hover-scale">
                    <i class="fas fa-pills text-4xl text-red-600 mb-3"></i>
                    <h4 class="font-bold text-gray-900">Pharmacies</h4>
                </div>
                <div class="bg-white p-6 rounded-lg shadow text-center hover-scale">
                    <i class="fas fa-tools text-4xl text-yellow-600 mb-3"></i>
                    <h4 class="font-bold text-gray-900">Hardware</h4>
                </div>
                <div class="bg-white p-6 rounded-lg shadow text-center hover-scale">
                    <i class="fas fa-car text-4xl text-blue-600 mb-3"></i>
                    <h4 class="font-bold text-gray-900">Auto Parts</h4>
                </div>
                <div class="bg-white p-6 rounded-lg shadow text-center hover-scale">
                    <i class="fas fa-utensils text-4xl text-orange-600 mb-3"></i>
                    <h4 class="font-bold text-gray-900">Restaurants</h4>
                </div>
                <div class="bg-white p-6 rounded-lg shadow text-center hover-scale">
                    <i class="fas fa-book text-4xl text-purple-600 mb-3"></i>
                    <h4 class="font-bold text-gray-900">Bookshops</h4>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="gradient-bg py-20 text-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl font-bold mb-6">
                Ready to Transform Your Business?
            </h2>
            <p class="text-xl mb-8 text-indigo-100">
                Join hundreds of Ugandan businesses already using our system
            </p>
            <a href="#" onclick="openRegisterModal(event)" class="inline-block px-10 py-4 bg-white text-indigo-600 rounded-lg font-bold text-xl hover:bg-gray-100 shadow-xl hover-scale">
                <i class="fas fa-rocket mr-2"></i> Start Your Free 30-Day Trial
            </a>
            <p class="mt-4 text-indigo-100">
                No credit card required • Setup in 2 minutes • Cancel anytime
            </p>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4">Uganda Inventory</h3>
                    <p class="text-gray-400">
                        The #1 inventory management system for Ugandan businesses.
                    </p>
                </div>
                <div>
                    <h4 class="font-bold mb-4">Product</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#features" class="hover:text-white">Features</a></li>
                        <li><a href="#" class="hover:text-white">Pricing</a></li>
                        <li><a href="#" class="hover:text-white">FAQ</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold mb-4">Company</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white">About Us</a></li>
                        <li><a href="#" class="hover:text-white">Contact</a></li>
                        <li><a href="#" class="hover:text-white">Blog</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold mb-4">Contact</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><i class="fas fa-phone mr-2"></i> +256 700 123 456</li>
                        <li><i class="fas fa-envelope mr-2"></i> support@inventory.ug</li>
                        <li><i class="fas fa-map-marker-alt mr-2"></i> Kampala, Uganda</li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; {{ date('Y') }} Uganda Inventory Management System. Developed by BARIGYE-DAVIS. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Login Modal -->
    <div id="loginModal" class="fixed inset-0 z-[100] hidden overflow-y-auto bg-blue-200 bg-opacity-40 backdrop-blur-xl flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md w-full relative modal-float">
            <button onclick="closeLoginModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
            <div class="text-center mb-8">
                <i class="fas fa-sign-in-alt text-5xl text-indigo-600 mb-4"></i>
                <h2 class="text-3xl font-extrabold text-gray-900">Welcome Back!</h2>
                <p class="mt-2 text-sm text-gray-600">Sign in to access your dashboard</p>
            </div>
            
            @if(session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf
                <input type="hidden" name="_form_type" value="login">
                <div>
                    <label for="login_email" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-envelope text-indigo-600 mr-1"></i> Email Address <span class="text-red-500">*</span>
                    </label>
                    <input id="login_email" name="email" type="email" required autofocus
                            value="{{ old('_form_type') == 'login' ? old('email') : '' }}"
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            placeholder="your@email.com">
                    @if(old('_form_type') == 'login')
                        @error('email')
                            <p class="mt-1 text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                        @enderror
                    @endif
                </div>

                <div>
                    <label for="login_password" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-lock text-indigo-600 mr-1"></i> Password <span class="text-red-500">*</span>
                    </label>
                    <input id="login_password" name="password" type="password" required
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            placeholder="Enter your password">
                    @if(old('_form_type') == 'login')
                        @error('password')
                            <p class="mt-1 text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                        @enderror
                    @endif
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember" name="remember" type="checkbox"
                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="remember" class="ml-2 block text-sm text-gray-900">Remember me</label>
                    </div>
                    <div class="text-sm">
                        <a href="#" class="font-medium text-indigo-600 hover:text-indigo-500">Forgot password?</a>
                    </div>
                </div>

                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-4 px-4 border border-transparent text-lg font-bold rounded-lg text-white bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-lg transform transition hover:scale-105">
                        <i class="fas fa-sign-in-alt mr-2"></i> Sign In to Dashboard
                    </button>
                </div>

                <div class="text-center">
                    <span class="text-gray-600">Don't have an account?</span>
                    <a href="#" onclick="openRegisterModal(event)" class="font-medium text-indigo-600 hover:text-indigo-500 ml-1">
                        Register your business <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Register Modal -->
    <div id="registerModal" class="fixed inset-0 z-[100] hidden overflow-y-auto bg-blue-200 bg-opacity-40 backdrop-blur-xl flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-2xl w-full relative modal-float my-8">
            <button onclick="closeRegisterModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
            <div class="text-center mb-8">
                <i class="fas fa-rocket text-5xl text-indigo-600 mb-4"></i>
                <h2 class="text-3xl font-extrabold text-gray-900">Register Your Business</h2>
                <p class="mt-2 text-sm text-gray-600">
                    <i class="fas fa-gift text-green-500 mr-1"></i>
                    Get started with <span class="font-bold text-green-600">30 days FREE trial</span>
                </p>
            </div>

            <form method="POST" action="{{ route('register') }}" class="space-y-6">
                @csrf
                <input type="hidden" name="_form_type" value="register">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="business_name" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-store text-indigo-600 mr-1"></i> Business Name <span class="text-red-500">*</span>
                        </label>
                        <input id="business_name" name="business_name" type="text" required
                                value="{{ old('_form_type') == 'register' ? old('business_name') : '' }}"
                                class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                placeholder="e.g., Davis Electronics Store">
                        @if(old('_form_type') == 'register')
                            @error('business_name')
                                <p class="mt-1 text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                            @enderror
                        @endif
                    </div>

                    <div>
                        <label for="business_category_id" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-tags text-indigo-600 mr-1"></i> Business Category <span class="text-red-500">*</span>
                        </label>
                        <select id="business_category_id" name="business_category_id" required
                                class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <option value="">-- Select Your Business Type --</option>
                            @foreach($businessCategories ?? [] as $category)
                                <option value="{{ $category->id }}" {{ (old('_form_type') == 'register' && old('business_category_id') == $category->id) ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @if(old('_form_type') == 'register')
                            @error('business_category_id')
                                <p class="mt-1 text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                            @enderror
                        @endif
                    </div>

                    <div>
                        <label for="business_email" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-envelope text-indigo-600 mr-1"></i> Business Email <span class="text-red-500">*</span>
                        </label>
                        <input id="business_email" name="business_email" type="email" required
                                value="{{ old('_form_type') == 'register' ? old('business_email') : '' }}"
                                class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                placeholder="business@example.com">
                        @if(old('_form_type') == 'register')
                            @error('business_email')
                                <p class="mt-1 text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                    
                    <div>
                        <label for="contact" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-phone text-indigo-600 mr-1"></i> Contact Number <span class="text-red-500">*</span>
                        </label>
                        <input id="contact" name="contact" type="tel" required
                                value="{{ old('_form_type') == 'register' ? old('contact') : '' }}"
                                class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                placeholder="0700123456">
                        @if(old('_form_type') == 'register')
                            @error('contact')
                                <p class="mt-1 text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                            @enderror
                        @endif
                    </div>

                    <div class="md:col-span-2">
                        <label for="personal_name" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-user text-indigo-600 mr-1"></i> Your Full Name <span class="text-red-500">*</span>
                        </label>
                        <input id="personal_name" name="personal_name" type="text" required
                                value="{{ old('_form_type') == 'register' ? old('personal_name') : '' }}"
                                class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                placeholder="Barigye Davis">
                        @if(old('_form_type') == 'register')
                            @error('personal_name')
                                <p class="mt-1 text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                            @enderror
                        @endif
                    </div>

                    <div>
                        <label for="register_password" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-lock text-indigo-600 mr-1"></i> Password <span class="text-red-500">*</span>
                        </label>
                        <input id="register_password" name="password" type="password" required
                                class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                placeholder="Min. 8 characters">
                        @if(old('_form_type') == 'register')
                            @error('password')
                                <p class="mt-1 text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                    
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-lock text-indigo-600 mr-1"></i> Confirm Password <span class="text-red-500">*</span>
                        </label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required
                                class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                placeholder="Re-enter password">
                    </div>
                </div>

                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-4 px-4 border border-transparent text-lg font-bold rounded-lg text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-lg transform transition hover:scale-105">
                        <i class="fas fa-rocket mr-2"></i> Create My Business Account
                    </button>
                </div>

                <div class="text-center">
                    <span class="text-gray-600">Already have an account?</span>
                    <a href="#" onclick="openLoginModal(event)" class="font-medium text-indigo-600 hover:text-indigo-500 ml-1">
                        Login here <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openLoginModal(e) {
            if(e) e.preventDefault();
            document.getElementById('loginModal').classList.remove('hidden');
            document.getElementById('registerModal').classList.add('hidden');
        }
        function closeLoginModal() {
            document.getElementById('loginModal').classList.add('hidden');
        }
        function openRegisterModal(e) {
            if(e) e.preventDefault();
            document.getElementById('registerModal').classList.remove('hidden');
            document.getElementById('loginModal').classList.add('hidden');
        }
        function closeRegisterModal() {
            document.getElementById('registerModal').classList.add('hidden');
        }

        document.getElementById('loginModal').addEventListener('click', function(e) {
            if (e.target === this) closeLoginModal();
        });
        document.getElementById('registerModal').addEventListener('click', function(e) {
            if (e.target === this) closeRegisterModal();
        });

        document.addEventListener('DOMContentLoaded', function() {
            @if(isset($showLoginModal) || old('_form_type') == 'login')
                openLoginModal();
            @endif
            @if(isset($showRegisterModal) || old('_form_type') == 'register')
                openRegisterModal();
            @endif
        });
    </script>
</body>
</html>