<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DukaFlow - Manage Your Business Smartly</title>
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
    <nav class="bg-white shadow-lg fixed w-full z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <i class="fas fa-boxes text-3xl text-indigo-600"></i>
                    <span class="ml-2 text-2xl font-bold text-gray-800">DukaFlow</span>
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
                    Run Your Business Smarter with DukaFlow
                </h1>
                <p class="text-xl md:text-2xl mb-8 text-indigo-100 max-w-4xl mx-auto">
                    The all-in-one POS and Inventory Management System designed for retailers, wholesalers, supermarkets, pharmacies, hardware stores, restaurants, boutiques, and growing businesses. Sell faster, track inventory in real time, manage your business from anywhere, and make better decisions with powerful reports.
                </p>
                <div class="flex justify-center space-x-4">
                    <a href="#" onclick="openRegisterModal(event)" class="px-8 py-4 bg-white text-indigo-600 rounded-lg font-bold text-lg hover:bg-gray-100 shadow-xl hover-scale">
                        Start Free Trial
                    </a>
                    <a href="#demo" class="px-8 py-4 bg-indigo-500 text-white rounded-lg font-bold text-lg hover:bg-indigo-400 shadow-xl hover-scale">
                        Book a Demo
                    </a>
                </div>
                <p class="mt-8 text-indigo-100 flex justify-center flex-wrap gap-4 text-sm font-medium">
                    <span class="flex items-center"><i class="fas fa-check-circle mr-2"></i> No complicated setup</span>
                    <span class="flex items-center"><i class="fas fa-check-circle mr-2"></i> Secure Cloud Access</span>
                    <span class="flex items-center"><i class="fas fa-check-circle mr-2"></i> Works on Mobile, Tablet & Desktop</span>
                    <span class="flex items-center"><i class="fas fa-check-circle mr-2"></i> Install as an App (PWA)</span>
                </p>
            </div>
        </div>
    </section>

    <!-- Trust & About Section -->
    <section class="py-20 bg-gray-50 border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                <!-- Trust Card -->
                <div class="bg-white p-10 md:p-12 rounded-3xl shadow-lg border border-gray-100 flex flex-col justify-center h-full relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-50 rounded-bl-full -mr-10 -mt-10 transition-transform group-hover:scale-110"></div>
                    <div class="relative z-10">
                        <div class="w-16 h-16 bg-indigo-100 text-indigo-600 rounded-2xl flex items-center justify-center text-3xl mb-8 shadow-sm">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3 class="text-3xl md:text-4xl font-bold text-gray-900 mb-6 leading-tight">Trusted by Growing Businesses</h3>
                        <p class="text-gray-600 text-lg md:text-xl leading-relaxed">
                            Whether you run a small shop or manage multiple branches, DukaFlow helps simplify your daily operations so you can focus on growing your business.
                        </p>
                    </div>
                </div>
                
                <!-- About Card -->
                <div class="bg-indigo-600 p-10 md:p-12 rounded-3xl shadow-xl text-white flex flex-col justify-center h-full relative overflow-hidden group">
                    <div class="absolute bottom-0 right-0 w-40 h-40 bg-indigo-500 rounded-tl-full -mr-10 -mb-10 transition-transform group-hover:scale-110 opacity-50"></div>
                    <div class="relative z-10">
                        <div class="w-16 h-16 bg-white/20 text-white rounded-2xl flex items-center justify-center text-3xl mb-8 shadow-sm backdrop-blur-sm">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <h2 class="text-3xl md:text-4xl font-bold mb-6 leading-tight">One Platform. Complete Business Control.</h2>
                        <p class="text-indigo-100 text-lg mb-6 leading-relaxed">
                            DukaFlow is a modern Point of Sale (POS) and Inventory Management System built specifically for businesses that need speed, accuracy, and simplicity.
                        </p>
                        <p class="text-indigo-100 text-lg mb-8 leading-relaxed">
                            From processing sales and managing stock to tracking expenses and generating reports, DukaFlow brings every part of your business together in one powerful platform.
                        </p>
                        <div class="bg-indigo-700/50 backdrop-blur-md py-4 px-6 rounded-xl border border-indigo-500/50 inline-block">
                            <p class="text-lg md:text-xl font-bold text-white flex items-center">
                                <i class="fas fa-globe-africa mr-3 text-indigo-300"></i>
                                Whether you're at your shop, at home, or traveling, you can access your business anytime, anywhere.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Everything You Need to Manage Your Business</h2>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 sm:gap-6 md:gap-8">
                <!-- Feature 1 -->
                <div class="bg-gray-50 p-4 sm:p-6 rounded-xl hover-scale border border-gray-100 shadow-sm flex flex-col items-center text-center sm:items-start sm:text-left">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600 text-xl sm:text-2xl mb-3 sm:mb-4 shrink-0"><i class="fas fa-desktop"></i></div>
                    <h3 class="text-sm sm:text-xl font-bold text-gray-900 mb-1 sm:mb-2 leading-tight">Smart Point of Sale</h3>
                    <p class="text-xs sm:text-base text-gray-600">Process sales quickly using an intuitive POS interface.</p>
                </div>
                <!-- Feature 2 -->
                <div class="bg-gray-50 p-4 sm:p-6 rounded-xl hover-scale border border-gray-100 shadow-sm flex flex-col items-center text-center sm:items-start sm:text-left">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 bg-green-100 rounded-full flex items-center justify-center text-green-600 text-xl sm:text-2xl mb-3 sm:mb-4 shrink-0"><i class="fas fa-boxes"></i></div>
                    <h3 class="text-sm sm:text-xl font-bold text-gray-900 mb-1 sm:mb-2 leading-tight">Inventory Management</h3>
                    <p class="text-xs sm:text-base text-gray-600">Monitor stock levels in real time and prevent stock shortages.</p>
                </div>
                <!-- Feature 3 -->
                <div class="bg-gray-50 p-4 sm:p-6 rounded-xl hover-scale border border-gray-100 shadow-sm flex flex-col items-center text-center sm:items-start sm:text-left">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 text-xl sm:text-2xl mb-3 sm:mb-4 shrink-0"><i class="fas fa-shopping-cart"></i></div>
                    <h3 class="text-sm sm:text-xl font-bold text-gray-900 mb-1 sm:mb-2 leading-tight">Purchase Management</h3>
                    <p class="text-xs sm:text-base text-gray-600">Record supplier purchases and automatically update inventory.</p>
                </div>
                <!-- Feature 4 -->
                <div class="bg-gray-50 p-4 sm:p-6 rounded-xl hover-scale border border-gray-100 shadow-sm flex flex-col items-center text-center sm:items-start sm:text-left">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 bg-purple-100 rounded-full flex items-center justify-center text-purple-600 text-xl sm:text-2xl mb-3 sm:mb-4 shrink-0"><i class="fas fa-users"></i></div>
                    <h3 class="text-sm sm:text-xl font-bold text-gray-900 mb-1 sm:mb-2 leading-tight">Customer Management</h3>
                    <p class="text-xs sm:text-base text-gray-600">Build customer profiles and keep track of purchase history.</p>
                </div>
                <!-- Feature 5 -->
                <div class="bg-gray-50 p-4 sm:p-6 rounded-xl hover-scale border border-gray-100 shadow-sm flex flex-col items-center text-center sm:items-start sm:text-left">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 bg-orange-100 rounded-full flex items-center justify-center text-orange-600 text-xl sm:text-2xl mb-3 sm:mb-4 shrink-0"><i class="fas fa-truck"></i></div>
                    <h3 class="text-sm sm:text-xl font-bold text-gray-900 mb-1 sm:mb-2 leading-tight">Supplier Management</h3>
                    <p class="text-xs sm:text-base text-gray-600">Manage suppliers and monitor outstanding balances.</p>
                </div>
                <!-- Feature 6 -->
                <div class="bg-gray-50 p-4 sm:p-6 rounded-xl hover-scale border border-gray-100 shadow-sm flex flex-col items-center text-center sm:items-start sm:text-left">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 bg-red-100 rounded-full flex items-center justify-center text-red-600 text-xl sm:text-2xl mb-3 sm:mb-4 shrink-0"><i class="fas fa-receipt"></i></div>
                    <h3 class="text-sm sm:text-xl font-bold text-gray-900 mb-1 sm:mb-2 leading-tight">Expense Tracking</h3>
                    <p class="text-xs sm:text-base text-gray-600">Record business expenses and understand where your money goes.</p>
                </div>
                <!-- Feature 7 -->
                <div class="feature-card hidden md:flex bg-gray-50 p-4 sm:p-6 rounded-xl hover-scale border border-gray-100 shadow-sm flex-col items-center text-center sm:items-start sm:text-left">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 bg-teal-100 rounded-full flex items-center justify-center text-teal-600 text-xl sm:text-2xl mb-3 sm:mb-4 shrink-0"><i class="fas fa-chart-bar"></i></div>
                    <h3 class="text-sm sm:text-xl font-bold text-gray-900 mb-1 sm:mb-2 leading-tight">Sales Reports</h3>
                    <p class="text-xs sm:text-base text-gray-600">View daily, weekly, monthly, and yearly sales performance.</p>
                </div>
                <!-- Feature 8 -->
                <div class="feature-card hidden md:flex bg-gray-50 p-4 sm:p-6 rounded-xl hover-scale border border-gray-100 shadow-sm flex-col items-center text-center sm:items-start sm:text-left">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 bg-emerald-100 rounded-full flex items-center justify-center text-emerald-600 text-xl sm:text-2xl mb-3 sm:mb-4 shrink-0"><i class="fas fa-chart-pie"></i></div>
                    <h3 class="text-sm sm:text-xl font-bold text-gray-900 mb-1 sm:mb-2 leading-tight">Profit Analysis</h3>
                    <p class="text-xs sm:text-base text-gray-600">Know exactly how much profit your business makes.</p>
                </div>
                <!-- Feature 9 -->
                <div class="feature-card hidden md:flex bg-gray-50 p-4 sm:p-6 rounded-xl hover-scale border border-gray-100 shadow-sm flex-col items-center text-center sm:items-start sm:text-left">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 bg-gray-200 rounded-full flex items-center justify-center text-gray-800 text-xl sm:text-2xl mb-3 sm:mb-4 shrink-0"><i class="fas fa-barcode"></i></div>
                    <h3 class="text-sm sm:text-xl font-bold text-gray-900 mb-1 sm:mb-2 leading-tight">Barcode Support</h3>
                    <p class="text-xs sm:text-base text-gray-600">Generate and scan barcodes for faster selling.</p>
                </div>
                <!-- Feature 10 -->
                <div class="feature-card hidden md:flex bg-gray-50 p-4 sm:p-6 rounded-xl hover-scale border border-gray-100 shadow-sm flex-col items-center text-center sm:items-start sm:text-left">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 bg-cyan-100 rounded-full flex items-center justify-center text-cyan-600 text-xl sm:text-2xl mb-3 sm:mb-4 shrink-0"><i class="fas fa-user-shield"></i></div>
                    <h3 class="text-sm sm:text-xl font-bold text-gray-900 mb-1 sm:mb-2 leading-tight">Multi-user Access</h3>
                    <p class="text-xs sm:text-base text-gray-600">Allow cashiers, managers, and administrators to work with different permissions.</p>
                </div>
                <!-- Feature 11 -->
                <div class="feature-card hidden md:flex bg-gray-50 p-4 sm:p-6 rounded-xl hover-scale border border-gray-100 shadow-sm flex-col items-center text-center sm:items-start sm:text-left">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-800 text-xl sm:text-2xl mb-3 sm:mb-4 shrink-0"><i class="fas fa-code-branch"></i></div>
                    <h3 class="text-sm sm:text-xl font-bold text-gray-900 mb-1 sm:mb-2 leading-tight">Multi-Branch Management</h3>
                    <p class="text-xs sm:text-base text-gray-600">Manage multiple business locations from one dashboard.</p>
                </div>
                <!-- Feature 12 -->
                <div class="feature-card hidden md:flex bg-gray-50 p-4 sm:p-6 rounded-xl hover-scale border border-gray-100 shadow-sm flex-col items-center text-center sm:items-start sm:text-left">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 bg-red-100 rounded-full flex items-center justify-center text-red-500 text-xl sm:text-2xl mb-3 sm:mb-4 shrink-0"><i class="fas fa-exclamation-triangle"></i></div>
                    <h3 class="text-sm sm:text-xl font-bold text-gray-900 mb-1 sm:mb-2 leading-tight">Low Stock Alerts</h3>
                    <p class="text-xs sm:text-base text-gray-600">Receive notifications when products need restocking.</p>
                </div>
                <!-- Feature 13 -->
                <div class="feature-card hidden md:flex bg-gray-50 p-4 sm:p-6 rounded-xl hover-scale border border-gray-100 shadow-sm flex-col items-center text-center sm:items-start sm:text-left">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 bg-yellow-100 rounded-full flex items-center justify-center text-yellow-600 text-xl sm:text-2xl mb-3 sm:mb-4 shrink-0"><i class="fas fa-undo-alt"></i></div>
                    <h3 class="text-sm sm:text-xl font-bold text-gray-900 mb-1 sm:mb-2 leading-tight">Returns & Refunds</h3>
                    <p class="text-xs sm:text-base text-gray-600">Handle customer returns professionally.</p>
                </div>
                <!-- Feature 14 -->
                <div class="feature-card hidden md:flex bg-gray-50 p-4 sm:p-6 rounded-xl hover-scale border border-gray-100 shadow-sm flex-col items-center text-center sm:items-start sm:text-left">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 bg-blue-100 rounded-full flex items-center justify-center text-blue-500 text-xl sm:text-2xl mb-3 sm:mb-4 shrink-0"><i class="fas fa-tachometer-alt"></i></div>
                    <h3 class="text-sm sm:text-xl font-bold text-gray-900 mb-1 sm:mb-2 leading-tight">Dashboard Analytics</h3>
                    <p class="text-xs sm:text-base text-gray-600">See your business performance at a glance.</p>
                </div>
                <!-- Feature 15 -->
                <div class="feature-card hidden md:flex bg-gray-50 p-4 sm:p-6 rounded-xl hover-scale border border-gray-100 shadow-sm flex-col items-center text-center sm:items-start sm:text-left">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 bg-green-100 rounded-full flex items-center justify-center text-green-500 text-xl sm:text-2xl mb-3 sm:mb-4 shrink-0"><i class="fas fa-cloud-upload-alt"></i></div>
                    <h3 class="text-sm sm:text-xl font-bold text-gray-900 mb-1 sm:mb-2 leading-tight">Secure Cloud Backup</h3>
                    <p class="text-xs sm:text-base text-gray-600">Your data stays safe and is automatically backed up.</p>
                </div>
            </div>
            
            <div class="mt-10 text-center md:hidden" id="viewMoreFeaturesContainer">
                <button onclick="document.querySelectorAll('.feature-card.hidden').forEach(el => el.classList.remove('hidden', 'md:flex')); document.getElementById('viewMoreFeaturesContainer').style.display='none';" class="text-indigo-600 font-bold border-2 border-indigo-600 rounded-full px-8 py-3 hover:bg-indigo-50 shadow-sm active:scale-95 transition-transform">
                    View More Features
                </button>
            </div>
        </div>
    </section>

    <!-- Why Choose DukaFlow & Who Can Use -->
    <section class="py-20 bg-gray-50 border-y border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-2 gap-16">
            <!-- Why Choose -->
            <div>
                <h2 class="text-3xl font-bold text-gray-900 mb-2">Why Choose DukaFlow</h2>
                <p class="text-indigo-600 font-bold mb-8 text-lg">Built for Businesses That Want to Grow</p>
                <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100">
                    <ul class="space-y-4">
                        <li class="flex items-center text-gray-700"><i class="fas fa-check-circle text-green-500 mr-3 text-lg"></i> Fast and Easy to Use</li>
                        <li class="flex items-center text-gray-700"><i class="fas fa-check-circle text-green-500 mr-3 text-lg"></i> Beautiful Modern Interface</li>
                        <li class="flex items-center text-gray-700"><i class="fas fa-check-circle text-green-500 mr-3 text-lg"></i> Secure Cloud Technology</li>
                        <li class="flex items-center text-gray-700"><i class="fas fa-check-circle text-green-500 mr-3 text-lg"></i> Real-Time Inventory Tracking</li>
                        <li class="flex items-center text-gray-700"><i class="fas fa-check-circle text-green-500 mr-3 text-lg"></i> Powerful Reports</li>
                        <li class="flex items-center text-gray-700"><i class="fas fa-check-circle text-green-500 mr-3 text-lg"></i> Mobile Friendly</li>
                        <li class="flex items-center text-gray-700"><i class="fas fa-check-circle text-green-500 mr-3 text-lg"></i> Install as an App</li>
                        <li class="flex items-center text-gray-700"><i class="fas fa-check-circle text-green-500 mr-3 text-lg"></i> Supports Multiple Users</li>
                        <li class="flex items-center text-gray-700"><i class="fas fa-check-circle text-green-500 mr-3 text-lg"></i> Affordable Pricing</li>
                        <li class="flex items-center text-gray-700"><i class="fas fa-check-circle text-green-500 mr-3 text-lg"></i> Reliable Customer Support</li>
                    </ul>
                </div>
            </div>
            
            <!-- Who Can Use -->
            <div>
                <h2 class="text-3xl font-bold text-gray-900 mb-2">Perfect for Every Business</h2>
                <p class="text-indigo-600 font-bold mb-8 text-lg">DukaFlow is suitable for:</p>
                <div class="grid grid-cols-2 gap-x-4 gap-y-3">
                    <div class="flex items-center text-gray-700"><div class="w-2 h-2 rounded-full bg-indigo-500 mr-2"></div> Retail Shops</div>
                    <div class="flex items-center text-gray-700"><div class="w-2 h-2 rounded-full bg-indigo-500 mr-2"></div> Wholesale Businesses</div>
                    <div class="flex items-center text-gray-700"><div class="w-2 h-2 rounded-full bg-indigo-500 mr-2"></div> Supermarkets</div>
                    <div class="flex items-center text-gray-700"><div class="w-2 h-2 rounded-full bg-indigo-500 mr-2"></div> Pharmacies</div>
                    <div class="flex items-center text-gray-700"><div class="w-2 h-2 rounded-full bg-indigo-500 mr-2"></div> Hardware Stores</div>
                    <div class="flex items-center text-gray-700"><div class="w-2 h-2 rounded-full bg-indigo-500 mr-2"></div> Boutiques</div>
                    <div class="flex items-center text-gray-700"><div class="w-2 h-2 rounded-full bg-indigo-500 mr-2"></div> Restaurants</div>
                    <div class="flex items-center text-gray-700"><div class="w-2 h-2 rounded-full bg-indigo-500 mr-2"></div> Cafés</div>
                    <div class="flex items-center text-gray-700"><div class="w-2 h-2 rounded-full bg-indigo-500 mr-2"></div> Bakeries</div>
                    <div class="flex items-center text-gray-700"><div class="w-2 h-2 rounded-full bg-indigo-500 mr-2"></div> Cosmetic Shops</div>
                    <div class="flex items-center text-gray-700"><div class="w-2 h-2 rounded-full bg-indigo-500 mr-2"></div> Electronics Stores</div>
                    <div class="flex items-center text-gray-700"><div class="w-2 h-2 rounded-full bg-indigo-500 mr-2"></div> Agro Input Shops</div>
                    <div class="flex items-center text-gray-700"><div class="w-2 h-2 rounded-full bg-indigo-500 mr-2"></div> Bookshops</div>
                    <div class="flex items-center text-gray-700"><div class="w-2 h-2 rounded-full bg-indigo-500 mr-2"></div> Stationery Stores</div>
                    <div class="flex items-center text-gray-700"><div class="w-2 h-2 rounded-full bg-indigo-500 mr-2"></div> Liquor Stores</div>
                    <div class="flex items-center text-gray-700"><div class="w-2 h-2 rounded-full bg-indigo-500 mr-2"></div> Mini Markets</div>
                    <div class="flex items-center text-gray-700"><div class="w-2 h-2 rounded-full bg-indigo-500 mr-2"></div> Fashion Stores</div>
                    <div class="flex items-center text-gray-700"><div class="w-2 h-2 rounded-full bg-indigo-500 mr-2"></div> Auto Spare Shops</div>
                    <div class="flex items-center text-gray-700"><div class="w-2 h-2 rounded-full bg-indigo-500 mr-2"></div> Furniture Stores</div>
                    <div class="flex items-center text-gray-700 font-bold text-indigo-600"><div class="w-2 h-2 rounded-full bg-indigo-500 mr-2"></div> Any Growing SME</div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <p class="text-indigo-600 font-bold tracking-widest uppercase mb-2">How It Works</p>
                <h2 class="text-4xl font-bold text-gray-900">Start in Four Simple Steps</h2>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 md:gap-8 text-center relative">
                <!-- Connectors for Desktop -->
                <div class="hidden md:block absolute top-8 left-[12.5%] right-[12.5%] h-0.5 bg-indigo-100 z-0"></div>
                
                <div class="relative z-10">
                    <div class="w-16 h-16 bg-indigo-600 text-white rounded-full flex items-center justify-center mx-auto text-2xl font-bold mb-6 shadow-lg border-4 border-white">1</div>
                    <p class="font-bold text-gray-900 text-lg">Create your business account.</p>
                </div>
                <div class="relative z-10">
                    <div class="w-16 h-16 bg-indigo-600 text-white rounded-full flex items-center justify-center mx-auto text-2xl font-bold mb-6 shadow-lg border-4 border-white">2</div>
                    <p class="font-bold text-gray-900 text-lg">Add your products and stock.</p>
                </div>
                <div class="relative z-10">
                    <div class="w-16 h-16 bg-indigo-600 text-white rounded-full flex items-center justify-center mx-auto text-2xl font-bold mb-6 shadow-lg border-4 border-white">3</div>
                    <p class="font-bold text-gray-900 text-lg">Start selling using DukaFlow POS.</p>
                </div>
                <div class="relative z-10">
                    <div class="w-16 h-16 bg-indigo-600 text-white rounded-full flex items-center justify-center mx-auto text-2xl font-bold mb-6 shadow-lg border-4 border-white">4</div>
                    <p class="font-bold text-gray-900 text-lg">Track your business growth with detailed reports.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Dashboard & Benefits Section -->
    <section class="py-20 bg-gray-50 border-t border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-2 gap-16 items-center">
            <!-- Dashboard -->
            <div>
                <h2 class="text-3xl font-bold text-gray-900 mb-2">See Your Business at a Glance</h2>
                <p class="text-indigo-600 font-bold mb-6 text-lg">Everything updates in real time.</p>
                <div class="bg-white p-8 rounded-xl shadow-md border border-gray-100">
                    <h4 class="font-bold text-gray-800 mb-6 border-b border-gray-100 pb-3 text-lg">Monitor:</h4>
                    <div class="grid grid-cols-2 gap-y-6">
                        <div class="flex items-center text-gray-700 font-medium"><div class="w-10 h-10 rounded bg-indigo-50 text-indigo-500 flex items-center justify-center mr-3"><i class="fas fa-chart-line"></i></div> Daily Sales</div>
                        <div class="flex items-center text-gray-700 font-medium"><div class="w-10 h-10 rounded bg-blue-50 text-blue-500 flex items-center justify-center mr-3"><i class="fas fa-calendar-alt"></i></div> Monthly Revenue</div>
                        <div class="flex items-center text-gray-700 font-medium"><div class="w-10 h-10 rounded bg-green-50 text-green-500 flex items-center justify-center mr-3"><i class="fas fa-dollar-sign"></i></div> Profit</div>
                        <div class="flex items-center text-gray-700 font-medium"><div class="w-10 h-10 rounded bg-red-50 text-red-500 flex items-center justify-center mr-3"><i class="fas fa-file-invoice-dollar"></i></div> Expenses</div>
                        <div class="flex items-center text-gray-700 font-medium"><div class="w-10 h-10 rounded bg-yellow-50 text-yellow-500 flex items-center justify-center mr-3"><i class="fas fa-star"></i></div> Top Selling Products</div>
                        <div class="flex items-center text-gray-700 font-medium"><div class="w-10 h-10 rounded bg-orange-50 text-orange-500 flex items-center justify-center mr-3"><i class="fas fa-exclamation-circle"></i></div> Low Stock Items</div>
                        <div class="flex items-center text-gray-700 font-medium"><div class="w-10 h-10 rounded bg-teal-50 text-teal-500 flex items-center justify-center mr-3"><i class="fas fa-history"></i></div> Recent Transactions</div>
                        <div class="flex items-center text-gray-700 font-medium"><div class="w-10 h-10 rounded bg-purple-50 text-purple-500 flex items-center justify-center mr-3"><i class="fas fa-users"></i></div> Customer Statistics</div>
                    </div>
                    <div class="mt-6 pt-4 border-t border-gray-100 flex items-center text-indigo-700 font-bold"><div class="w-10 h-10 rounded bg-indigo-100 flex items-center justify-center mr-3"><i class="fas fa-tachometer-alt"></i></div> Complete Business Performance</div>
                </div>
            </div>
            
            <!-- Benefits -->
            <div>
                <h2 class="text-3xl font-bold text-gray-900 mb-8">More Than Just a POS</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="flex p-5 bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
                        <i class="fas fa-clock text-2xl text-indigo-500 mr-4 mt-1"></i>
                        <div><h4 class="font-bold text-gray-900">Save Time</h4></div>
                    </div>
                    <div class="flex p-5 bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
                        <i class="fas fa-shield-alt text-2xl text-green-500 mr-4 mt-1"></i>
                        <div><h4 class="font-bold text-gray-900">Reduce Human Errors</h4></div>
                    </div>
                    <div class="flex p-5 bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
                        <i class="fas fa-smile text-2xl text-yellow-500 mr-4 mt-1"></i>
                        <div><h4 class="font-bold text-gray-900">Improve Customer Service</h4></div>
                    </div>
                    <div class="flex p-5 bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
                        <i class="fas fa-box-open text-2xl text-blue-500 mr-4 mt-1"></i>
                        <div><h4 class="font-bold text-gray-900">Know Your Stock Anytime</h4></div>
                    </div>
                    <div class="flex p-5 bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
                        <i class="fas fa-arrow-up text-2xl text-emerald-500 mr-4 mt-1"></i>
                        <div><h4 class="font-bold text-gray-900">Increase Profits</h4></div>
                    </div>
                    <div class="flex p-5 bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
                        <i class="fas fa-lightbulb text-2xl text-orange-500 mr-4 mt-1"></i>
                        <div><h4 class="font-bold text-gray-900">Better Business Decisions</h4></div>
                    </div>
                    <div class="flex p-5 bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
                        <i class="fas fa-globe text-2xl text-cyan-500 mr-4 mt-1"></i>
                        <div><h4 class="font-bold text-gray-900">Access Anywhere</h4></div>
                    </div>
                    <div class="flex p-5 bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
                        <i class="fas fa-seedling text-2xl text-teal-500 mr-4 mt-1"></i>
                        <div><h4 class="font-bold text-gray-900">Grow With Confidence</h4></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="py-20 bg-white border-t border-gray-100">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl font-bold text-gray-900 mb-12">What Business Owners Say</h2>
            <div class="bg-gray-50 p-8 md:p-12 rounded-2xl shadow-sm border border-gray-100 italic text-xl md:text-2xl text-gray-700 relative text-center">
                <i class="fas fa-quote-left text-5xl text-indigo-100 absolute top-6 left-6"></i>
                <p class="relative z-10 px-4 md:px-8 font-light">"DukaFlow has transformed how we manage sales and inventory. Everything is now faster, more organized, and easier to track."</p>
                <div class="mt-8 font-bold text-gray-900 not-italic text-lg">— Retail Business Owner</div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="gradient-bg py-24 text-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl md:text-5xl font-bold mb-6">Ready to Grow Your Business?</h2>
            <p class="text-xl md:text-2xl mb-10 text-indigo-100">
                Join businesses using DukaFlow to simplify operations, improve inventory management, and increase profitability.
            </p>
            <div class="flex flex-col sm:flex-row justify-center items-center space-y-4 sm:space-y-0 sm:space-x-6">
                <a href="#" onclick="openRegisterModal(event)" class="w-full sm:w-auto px-10 py-4 bg-white text-indigo-600 rounded-lg font-bold text-lg hover:bg-gray-100 shadow-xl hover-scale">
                    Start Free Trial
                </a>
                <a href="#demo" class="w-full sm:w-auto px-10 py-4 bg-indigo-500 text-white rounded-lg font-bold text-lg hover:bg-indigo-400 shadow-xl hover-scale">
                    Request a Demo
                </a>
                <a href="mailto:support@dukaflow.com" class="w-full sm:w-auto px-10 py-4 bg-transparent border-2 border-white text-white rounded-lg font-bold text-lg hover:bg-white hover:text-indigo-600 shadow-xl hover-scale transition-colors duration-300">
                    Contact Sales
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4">DukaFlow</h3>
                    <p class="text-gray-400">
                        The all-in-one POS and inventory management system for your business.
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
                        <li><i class="fas fa-envelope mr-2"></i> support@dukaflow.com</li>
                        <li><i class="fas fa-map-marker-alt mr-2"></i> Kampala, Uganda</li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; {{ date('Y') }} DukaFlow. Powered by ResNet Systems (U) LTD. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Login Modal -->
    <div id="loginModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-blue-200 bg-opacity-40 backdrop-blur-xl flex items-center justify-center p-4">
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
    <div id="registerModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-blue-200 bg-opacity-40 backdrop-blur-xl flex items-center justify-center p-4">
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