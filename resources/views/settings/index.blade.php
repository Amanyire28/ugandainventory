@extends('layouts.app')

@section('title', 'Business Settings')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-7xl mx-auto">
        
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">
                        <i class="fas fa-cog text-indigo-600 mr-3"></i>
                        Business Settings
                    </h1>
                    <p class="text-gray-600 mt-2">Manage your business information and preferences</p>
                </div>
                
                <div class="flex items-center space-x-3">
                    <a href="{{ route('settings.audit-logs') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow transition flex items-center">
                        <i class="fas fa-history mr-2"></i>View Audit Logs
                    </a>
                </div>
                
                <!-- Business Status Badge -->
                <div class="text-right">
                    <span class="px-4 py-2 rounded-full text-sm font-semibold
                        {{ $business->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        <i class="fas fa-circle text-xs mr-1"></i>
                        {{ $business->is_active ? 'Active' : 'Inactive' }}
                    </span>
                    
                    @if($business->subscription_expires_at)
                    <div class="mt-2">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold
                            bg-{{ $business->subscription_status_color }}-100 
                            text-{{ $business->subscription_status_color }}-800">
                            {{ $business->subscription_status_text }}
                        </span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg flex items-center animate-fade-in">
            <i class="fas fa-check-circle text-2xl mr-3"></i>
            <span>{{ session('success') }}</span>
        </div>
        @endif

        @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg flex items-center animate-fade-in">
            <i class="fas fa-exclamation-circle text-2xl mr-3"></i>
            <span>{{ session('error') }}</span>
        </div>
        @endif

        @if(session('info'))
        <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6 rounded-lg flex items-center animate-fade-in">
            <i class="fas fa-info-circle text-2xl mr-3"></i>
            <span>{{ session('info') }}</span>
        </div>
        @endif

        <!-- Tabs Navigation -->
        <div class="bg-white rounded-lg shadow-md mb-6">
            <div class="border-b border-gray-200">
                <nav class="flex -mb-px overflow-x-auto" role="tablist">
                    <button class="tab-button active px-6 py-4 text-sm font-medium border-b-2 border-indigo-600 text-indigo-600 whitespace-nowrap"
                            data-tab="business-info">
                        <i class="fas fa-building mr-2"></i>Business Info
                    </button>
                    <button class="tab-button px-6 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap"
                            data-tab="logo">
                        <i class="fas fa-image mr-2"></i>Logo
                    </button>
                    <button class="tab-button px-6 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap"
                            data-tab="email-settings">
                        <i class="fas fa-envelope mr-2"></i>Email Settings
                        @if(!$business->hasEmailConfigured())
                        <span class="ml-2 px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">
                            Setup Required
                        </span>
                        @endif
                    </button>
                    <button class="tab-button px-6 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap"
                            data-tab="tax-settings">
                        <i class="fas fa-percent mr-2"></i>Tax Settings
                    </button>
                    <button class="tab-button px-6 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap"
                            data-tab="advanced">
                        <i class="fas fa-sliders-h mr-2"></i>Advanced
                    </button>
                </nav>
            </div>
        </div>

                <!-- Tab Contents -->
        <div class="space-y-6">

            <!-- ============================================ -->
            <!-- TAB 1: BUSINESS INFORMATION -->
            <!-- ============================================ -->
            <div id="business-info-tab" class="tab-content active">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">
                        <i class="fas fa-building text-indigo-600 mr-2"></i>
                        Business Information
                    </h2>

                    <form action="{{ route('settings.update-info') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <!-- Business Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-store mr-2 text-indigo-600"></i>Business Name *
                                </label>
                                <input type="text" 
                                       name="name" 
                                       value="{{ old('name', $business->name) }}" 
                                       class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-indigo-500 @error('name') border-red-500 @enderror"
                                       required>
                                @error('name')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Business Email -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-envelope mr-2 text-indigo-600"></i>Business Email *
                                </label>
                                <input type="email" 
                                       name="email" 
                                       value="{{ old('email', $business->email) }}" 
                                       class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-indigo-500 @error('email') border-red-500 @enderror"
                                       required>
                                @error('email')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                                <p class="text-xs text-gray-500 mt-1">Primary contact email for your business</p>
                            </div>

                            <!-- Phone Number -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-phone mr-2 text-indigo-600"></i>Phone Number *
                                </label>
                                <input type="text" 
                                       name="phone" 
                                       value="{{ old('phone', $business->phone) }}" 
                                       placeholder="0700000000"
                                       class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-indigo-500 @error('phone') border-red-500 @enderror"
                                       required>
                                @error('phone')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Website -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-globe mr-2 text-indigo-600"></i>Website
                                </label>
                                <input type="url" 
                                       name="website" 
                                       value="{{ old('website', $business->website) }}" 
                                       placeholder="https://yourbusiness.com"
                                       class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-indigo-500 @error('website') border-red-500 @enderror">
                                @error('website')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Address (Full Width) -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-map-marker-alt mr-2 text-indigo-600"></i>Business Address
                                </label>
                                <textarea name="address" 
                                          rows="3"
                                          class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-indigo-500 @error('address') border-red-500 @enderror"
                                          placeholder="Enter your full business address">{{ old('address', $business->address) }}</textarea>
                                @error('address')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Tax Number -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-file-invoice mr-2 text-indigo-600"></i>Tax Identification Number (TIN)
                                </label>
                                <input type="text" 
                                       name="tax_number" 
                                       value="{{ old('tax_number', $business->tax_number) }}" 
                                       placeholder="1000XXXXXX"
                                       class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-indigo-500 @error('tax_number') border-red-500 @enderror">
                                @error('tax_number')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Currency -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-money-bill-wave mr-2 text-indigo-600"></i>Currency
                                </label>
                                <select name="currency" 
                                        class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                                    <option value="UGX" {{ old('currency', $business->currency) == 'UGX' ? 'selected' : '' }}>
                                        UGX - Ugandan Shilling
                                    </option>
                                    <option value="USD" {{ old('currency', $business->currency) == 'USD' ? 'selected' : '' }}>
                                        USD - US Dollar
                                    </option>
                                    <option value="KES" {{ old('currency', $business->currency) == 'KES' ? 'selected' : '' }}>
                                        KES - Kenyan Shilling
                                    </option>
                                    <option value="TZS" {{ old('currency', $business->currency) == 'TZS' ? 'selected' : '' }}>
                                        TZS - Tanzanian Shilling
                                    </option>
                                    <option value="RWF" {{ old('currency', $business->currency) == 'RWF' ? 'selected' : '' }}>
                                        RWF - Rwandan Franc
                                    </option>
                                </select>
                            </div>

                        </div>

                        <!-- Info Box -->
                        <div class="mt-6 bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
                            <div class="flex">
                                <i class="fas fa-info-circle text-blue-600 mr-3 mt-1"></i>
                                <div class="text-sm text-blue-900">
                                    <p class="font-semibold mb-1">Important Information</p>
                                    <p>This information will appear on receipts, invoices, and reports. Make sure all details are accurate.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="mt-6 flex justify-end">
                            <button type="submit" 
                                    class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold flex items-center">
                                <i class="fas fa-save mr-2"></i>Save Business Information
                            </button>
                        </div>
                    </form>
                </div>
            </div>

                        <!-- ============================================ -->
            <!-- TAB 2: LOGO MANAGEMENT -->
            <!-- ============================================ -->
            <div id="logo-tab" class="tab-content hidden">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">
                        <i class="fas fa-image text-indigo-600 mr-2"></i>
                        Business Logo
                    </h2>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        
                        <!-- Left: Current Logo Display -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-700 mb-4">Current Logo</h3>
                            
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center bg-gray-50">
                                @if($business->hasLogo())
                                    <!-- Show Current Logo -->
                                    <div class="relative inline-block">
                                        <img src="{{ $business->logo_url }}" 
                                             alt="{{ $business->name }}" 
                                             class="max-h-48 mx-auto rounded-lg shadow-md">
                                        
                                        <!-- Remove Logo Button -->
                                        <form action="{{ route('settings.remove-logo') }}" 
                                              method="POST" 
                                              class="mt-4"
                                              onsubmit="return confirm('Are you sure you want to remove the logo?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 text-sm">
                                                <i class="fas fa-trash mr-2"></i>Remove Logo
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <!-- No Logo Placeholder -->
                                    <div class="flex flex-col items-center justify-center py-8">
                                        <div class="w-32 h-32 bg-indigo-100 rounded-full flex items-center justify-center mb-4">
                                            <span class="text-5xl font-bold text-indigo-600">
                                                {{ $business->logo_initials }}
                                            </span>
                                        </div>
                                        <p class="text-gray-500 text-sm">No logo uploaded yet</p>
                                        <p class="text-gray-400 text-xs mt-1">Upload a logo to personalize your business</p>
                                    </div>
                                @endif
                            </div>

                            <!-- Logo Info -->
                            <div class="mt-4 bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
                                <p class="text-sm text-blue-900">
                                    <i class="fas fa-lightbulb mr-2"></i>
                                    <strong>Logo Tips:</strong>
                                </p>
                                <ul class="text-xs text-blue-800 mt-2 space-y-1 ml-6 list-disc">
                                    <li>Use square or rectangular images</li>
                                    <li>Recommended size: 500x500px or larger</li>
                                    <li>Transparent background works best</li>
                                    <li>File formats: JPG, PNG, SVG, GIF</li>
                                    <li>Maximum size: 2MB</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Right: Upload New Logo -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-700 mb-4">
                                {{ $business->hasLogo() ? 'Update Logo' : 'Upload Logo' }}
                            </h3>

                            <form action="{{ route('settings.update-logo') }}" 
                                  method="POST" 
                                  enctype="multipart/form-data"
                                  id="logo-upload-form">
                                @csrf

                                <!-- File Upload Area -->
                                <div class="border-2 border-dashed border-indigo-300 rounded-lg p-8 text-center bg-indigo-50 hover:bg-indigo-100 transition cursor-pointer"
                                     onclick="document.getElementById('logo-input').click()">
                                    
                                    <div id="upload-placeholder">
                                        <i class="fas fa-cloud-upload-alt text-6xl text-indigo-600 mb-4"></i>
                                        <p class="text-gray-700 font-semibold mb-2">Click to upload or drag and drop</p>
                                        <p class="text-gray-500 text-sm">JPG, PNG, SVG, or GIF (Max 2MB)</p>
                                    </div>

                                    <!-- Preview Area (Hidden Initially) -->
                                    <div id="image-preview" class="hidden">
                                        <img id="preview-image" 
                                             src="" 
                                             alt="Preview" 
                                             class="max-h-48 mx-auto rounded-lg shadow-md mb-4">
                                        <p class="text-sm text-gray-600" id="file-name"></p>
                                    </div>

                                    <input type="file" 
                                           id="logo-input"
                                           name="logo" 
                                           accept="image/*"
                                           class="hidden"
                                           onchange="previewLogo(event)">
                                </div>

                                @error('logo')
                                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                                @enderror

                                <!-- Upload Button -->
                                <div class="mt-6">
                                    <button type="submit" 
                                            id="upload-btn"
                                            class="w-full px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
                                            disabled>
                                        <i class="fas fa-upload mr-2"></i>
                                        {{ $business->hasLogo() ? 'Update Logo' : 'Upload Logo' }}
                                    </button>
                                </div>
                            </form>

                            <!-- Benefits -->
                            <div class="mt-6 space-y-3">
                                <div class="flex items-start">
                                    <i class="fas fa-check-circle text-green-600 mr-3 mt-1"></i>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700">Professional Appearance</p>
                                        <p class="text-xs text-gray-500">Your logo appears on receipts and invoices</p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-check-circle text-green-600 mr-3 mt-1"></i>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700">Brand Recognition</p>
                                        <p class="text-xs text-gray-500">Build trust with your customers</p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-check-circle text-green-600 mr-3 mt-1"></i>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700">Easy Updates</p>
                                        <p class="text-xs text-gray-500">Change your logo anytime</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Logo Preview JavaScript -->
            <script>
                function previewLogo(event) {
                    const file = event.target.files[0];
                    
                    if (file) {
                        // Validate file size (2MB max)
                        if (file.size > 2048000) {
                            alert('File size must not exceed 2MB');
                            event.target.value = '';
                            return;
                        }

                        // Validate file type
                        const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml'];
                        if (!validTypes.includes(file.type)) {
                            alert('Please upload a valid image file (JPG, PNG, SVG, or GIF)');
                            event.target.value = '';
                            return;
                        }

                        // Show preview
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            document.getElementById('upload-placeholder').classList.add('hidden');
                            document.getElementById('image-preview').classList.remove('hidden');
                            document.getElementById('preview-image').src = e.target.result;
                            document.getElementById('file-name').textContent = file.name;
                            document.getElementById('upload-btn').disabled = false;
                        };
                        reader.readAsDataURL(file);
                    }
                }
            </script>


            <!-- ============================================ -->
            <!-- TAB 3: EMAIL SETTINGS -->
            <!-- ============================================ -->
            <div id="email-settings-tab" class="tab-content hidden">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">
                        <i class="fas fa-envelope-open-text text-indigo-600 mr-2"></i>
                        Email Settings
                    </h2>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        
                        <!-- LEFT: Setup Guide -->
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg p-6">
                            <h3 class="text-xl font-bold text-indigo-900 mb-4">
                                <i class="fas fa-book-open mr-2"></i>Setup Guide
                            </h3>
                            
                            <div class="space-y-4">
                                
                                <!-- Step 1 -->
                                <div class="bg-white rounded-lg p-4 shadow-sm">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <div class="w-10 h-10 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold text-lg">
                                                1
                                            </div>
                                        </div>
                                        <div class="ml-4 flex-1">
                                            <h4 class="font-bold text-gray-900 mb-2">Enable 2-Step Verification</h4>
                                            <p class="text-sm text-gray-600 mb-3">Required for Gmail App Passwords</p>
                                            <a href="https://myaccount.google.com/security" 
                                               target="_blank"
                                               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition">
                                                <i class="fas fa-external-link-alt mr-2"></i>
                                                Open Google Security
                                            </a>
                                            <div class="mt-3 text-xs text-gray-500 space-y-1">
                                                <p>• Click "2-Step Verification"</p>
                                                <p>• Follow the setup wizard</p>
                                                <p>• Verify with your phone number</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Step 2 -->
                                <div class="bg-white rounded-lg p-4 shadow-sm">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <div class="w-10 h-10 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold text-lg">
                                                2
                                            </div>
                                        </div>
                                        <div class="ml-4 flex-1">
                                            <h4 class="font-bold text-gray-900 mb-2">Generate App Password</h4>
                                            <p class="text-sm text-gray-600 mb-3">Create 16-character password</p>
                                            <a href="https://myaccount.google.com/apppasswords" 
                                               target="_blank"
                                               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition">
                                                <i class="fas fa-key mr-2"></i>
                                                Create App Password
                                            </a>
                                            <div class="mt-3 text-xs text-gray-500 space-y-1">
                                                <p>• App name: "{{ $business->name }} POS"</p>
                                                <p>• Click "Generate"</p>
                                                <p>• Copy the 16-character password</p>
                                                <p>• You won't see it again!</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Step 3 -->
                                <div class="bg-white rounded-lg p-4 shadow-sm">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <div class="w-10 h-10 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold text-lg">
                                                3
                                            </div>
                                        </div>
                                        <div class="ml-4 flex-1">
                                            <h4 class="font-bold text-gray-900 mb-2">Enter Credentials</h4>
                                            <p class="text-sm text-gray-600">Paste your Gmail and App Password in the form →</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Example Password -->
                                <div class="bg-yellow-50 border-2 border-yellow-300 rounded-lg p-4">
                                    <p class="font-semibold text-yellow-900 mb-2">
                                        <i class="fas fa-lightbulb mr-2"></i>Example App Password:
                                    </p>
                                    <div class="bg-white p-3 rounded border border-yellow-200 font-mono text-sm text-center">
                                        abcd efgh ijkl mnop
                                    </div>
                                    <p class="text-xs text-yellow-800 mt-2">
                                        ✅ Paste with or without spaces - both work!
                                    </p>
                                </div>

                                <!-- Video Tutorial Link -->
                                <div class="bg-green-50 border-2 border-green-300 rounded-lg p-4">
                                    <p class="font-semibold text-green-900 mb-2">
                                        <i class="fas fa-video mr-2"></i>Need Help?
                                    </p>
                                    <p class="text-sm text-green-800 mb-3">Watch our video tutorial</p>
                                    <a href="#" class="text-sm text-green-700 underline hover:text-green-900">
                                        Watch Setup Tutorial →
                                    </a>
                                </div>

                            </div>
                        </div>

                        <!-- RIGHT: Configuration Form -->
                        <div>
                            
                            @if($business->hasEmailConfigured())
                            <!-- Already Configured -->
                            <div class="bg-green-50 border-2 border-green-300 rounded-lg p-5 mb-6">
                                <div class="flex items-start mb-4">
                                    <i class="fas fa-check-circle text-green-600 text-3xl mr-4"></i>
                                    <div class="flex-1">
                                        <p class="font-bold text-green-900 text-lg">Email Configured!</p>
                                        <p class="text-sm text-green-700 mt-1">
                                            Receipts will be sent from your business email
                                        </p>
                                    </div>
                                </div>
                                <div class="bg-white rounded-lg p-4 border border-green-200">
                                    <p class="text-xs text-gray-600 mb-1">Configured Email:</p>
                                    <p class="font-semibold text-gray-900 flex items-center">
                                        <i class="fas fa-envelope text-green-600 mr-2"></i>
                                        {{ $business->smtp_email }}
                                    </p>
                                </div>
                            </div>

                            <!-- Test Email Form -->
                            <div class="bg-white border-2 border-indigo-200 rounded-lg p-5 mb-6">
                                <h4 class="font-bold text-gray-900 mb-4">
                                    <i class="fas fa-paper-plane text-indigo-600 mr-2"></i>
                                    Test Email Configuration
                                </h4>
                                
                                <form action="{{ route('settings.test-email') }}" method="POST">
                                    @csrf
                                    <div class="flex gap-2">
                                        <input type="email" 
                                               name="test_email" 
                                               placeholder="Enter email to test" 
                                               class="flex-1 px-4 py-3 border rounded-lg focus:ring-2 focus:ring-indigo-500"
                                               required>
                                        <button type="submit" 
                                                class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 whitespace-nowrap font-semibold">
                                            <i class="fas fa-paper-plane mr-2"></i>Send Test
                                        </button>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-2">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        We'll send a test email to verify your configuration
                                    </p>
                                </form>
                            </div>

                            <!-- Update Configuration Button -->
                            <button onclick="toggleEmailForm()" 
                                    id="update-email-btn"
                                    class="w-full px-4 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 mb-4 font-semibold">
                                <i class="fas fa-edit mr-2"></i>Update Email Configuration
                            </button>

                            <!-- Remove Configuration -->
                            <form action="{{ route('settings.remove-email') }}" 
                                  method="POST" 
                                  onsubmit="return confirm('Are you sure? System will use default email for sending receipts.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="w-full px-4 py-3 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 font-semibold">
                                    <i class="fas fa-trash mr-2"></i>Remove Email Configuration
                                </button>
                            </form>
                            @endif

                            <!-- Configuration Form -->
                            <form action="{{ route('settings.update-email') }}" 
                                  method="POST" 
                                  id="email-config-form"
                                  class="{{ $business->hasEmailConfigured() ? 'hidden' : '' }}">
                                @csrf
                                @method('PUT')

                                <div class="space-y-5">
                                    
                                    <!-- Gmail Address -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-envelope mr-2 text-indigo-600"></i>
                                            Gmail Address *
                                        </label>
                                        <input type="email" 
                                               name="smtp_email" 
                                               value="{{ old('smtp_email', $business->smtp_email) }}" 
                                               placeholder="yourbusiness@gmail.com"
                                               class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-indigo-500 @error('smtp_email') border-red-500 @enderror"
                                               required>
                                        @error('smtp_email')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                        <p class="text-xs text-gray-500 mt-1">
                                            The Gmail account you'll use to send receipts
                                        </p>
                                    </div>

                                    <!-- Gmail App Password -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-key mr-2 text-indigo-600"></i>
                                            Gmail App Password *
                                        </label>
                                        <div class="relative">
                                            <input type="password" 
                                                   id="smtp_password"
                                                   name="smtp_password" 
                                                   placeholder="abcdefghijklmnop"
                                                   class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-indigo-500 font-mono pr-12 @error('smtp_password') border-red-500 @enderror"
                                                   required>
                                            <button type="button"
                                                    onclick="togglePassword()"
                                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                                                <i class="fas fa-eye" id="password-icon"></i>
                                            </button>
                                        </div>
                                        @error('smtp_password')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                        <p class="text-xs text-gray-500 mt-1">
                                            16-character password from Google (spaces will be removed automatically)
                                        </p>
                                    </div>

                                    <!-- Important Notice -->
                                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
                                        <div class="flex items-start">
                                            <i class="fas fa-info-circle text-blue-600 mr-3 mt-1"></i>
                                            <div class="text-sm text-blue-900">
                                                <p class="font-semibold mb-1">Important:</p>
                                                <ul class="list-disc list-inside space-y-1">
                                                    <li>Use App Password, NOT your regular Gmail password</li>
                                                    <li>2-Step Verification must be enabled first</li>
                                                    <li>This keeps your account secure</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Submit Button -->
                                    <button type="submit" 
                                            class="w-full px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold">
                                        <i class="fas fa-save mr-2"></i>Save Email Configuration
                                    </button>

                                </div>
                            </form>

                            <!-- Benefits Section -->
                            @if(!$business->hasEmailConfigured())
                            <div class="mt-6 bg-gradient-to-r from-purple-50 to-pink-50 rounded-lg p-5">
                                <h4 class="font-bold text-gray-900 mb-3">
                                    <i class="fas fa-star text-yellow-500 mr-2"></i>
                                    Why Configure Email?
                                </h4>
                                <div class="space-y-2">
                                    <div class="flex items-start">
                                        <i class="fas fa-check text-green-600 mr-2 mt-1"></i>
                                        <p class="text-sm text-gray-700">Customers see YOUR business email</p>
                                    </div>
                                    <div class="flex items-start">
                                        <i class="fas fa-check text-green-600 mr-2 mt-1"></i>
                                        <p class="text-sm text-gray-700">More professional and trustworthy</p>
                                    </div>
                                    <div class="flex items-start">
                                        <i class="fas fa-check text-green-600 mr-2 mt-1"></i>
                                        <p class="text-sm text-gray-700">Customers can reply directly to you</p>
                                    </div>
                                    <div class="flex items-start">
                                        <i class="fas fa-check text-green-600 mr-2 mt-1"></i>
                                        <p class="text-sm text-gray-700">Build stronger customer relationships</p>
                                    </div>
                                </div>
                            </div>
                            @endif

                        </div>
                    </div>
                </div>
            </div>

            <!-- Email Tab JavaScript -->
            <script>
                function toggleEmailForm() {
                    const form = document.getElementById('email-config-form');
                    const btn = document.getElementById('update-email-btn');
                    
                    if (form.classList.contains('hidden')) {
                        form.classList.remove('hidden');
                        btn.innerHTML = '<i class="fas fa-times mr-2"></i>Cancel Update';
                    } else {
                        form.classList.add('hidden');
                        btn.innerHTML = '<i class="fas fa-edit mr-2"></i>Update Email Configuration';
                    }
                }

                function togglePassword() {
                    const passwordInput = document.getElementById('smtp_password');
                    const icon = document.getElementById('password-icon');
                    
                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        passwordInput.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                }
            </script>


            <!-- ============================================ -->
            <!-- TAB 4: TAX SETTINGS -->
            <!-- ============================================ -->
            <div id="tax-settings-tab" class="tab-content hidden">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">
                        <i class="fas fa-percent text-indigo-600 mr-2"></i>
                        Tax Settings
                    </h2>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        
                        <!-- Left: Tax Configuration -->
                        <div>
                            <form action="{{ route('settings.update-tax') }}" method="POST">
                                @csrf
                                @method('PUT')

                                <!-- Enable/Disable Tax -->
                                <div class="bg-gray-50 rounded-lg p-5 mb-6">
                                    <div class="flex items-center justify-between mb-4">
                                        <div>
                                            <h3 class="font-bold text-gray-900">Enable Tax Calculation</h3>
                                            <p class="text-sm text-gray-600 mt-1">
                                                Automatically calculate tax on sales
                                            </p>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" 
                                                   name="tax_enabled" 
                                                   value="1"
                                                   {{ old('tax_enabled', $business->tax_enabled) ? 'checked' : '' }}
                                                   class="sr-only peer"
                                                   onchange="toggleTaxFields(this)">
                                            <div class="w-14 h-7 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-indigo-600"></div>
                                        </label>
                                    </div>

                                    <div id="tax-status-badge" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold
                                        {{ $business->tax_enabled ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700' }}">
                                        <i class="fas fa-circle text-xs mr-2"></i>
                                        <span id="tax-status-text">{{ $business->tax_enabled ? 'Tax Enabled' : 'Tax Disabled' }}</span>
                                    </div>
                                </div>

                                <!-- Tax Rate -->
                                <div id="tax-fields" class="{{ $business->tax_enabled ? '' : 'hidden' }}">
                                    <div class="mb-6">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-percentage mr-2 text-indigo-600"></i>
                                            Tax Rate (%)
                                        </label>
                                        <div class="relative">
                                            <input type="number" 
                                                   name="tax_rate" 
                                                   value="{{ old('tax_rate', $business->tax_rate ?? 18) }}" 
                                                   step="0.01"
                                                   min="0"
                                                   max="100"
                                                   class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-indigo-500 pr-10 @error('tax_rate') border-red-500 @enderror"
                                                   placeholder="18.00">
                                            <span class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-500">%</span>
                                        </div>
                                        @error('tax_rate')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                        <p class="text-xs text-gray-500 mt-1">
                                            Standard VAT rate in Uganda is 18%
                                        </p>
                                    </div>

                                    <!-- Tax Number (TIN) -->
                                    <div class="mb-6">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-file-invoice mr-2 text-indigo-600"></i>
                                            Tax Identification Number (TIN)
                                        </label>
                                        <input type="text" 
                                               name="tax_number" 
                                               value="{{ old('tax_number', $business->tax_number) }}" 
                                               placeholder="1000XXXXXX"
                                               class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-indigo-500 @error('tax_number') border-red-500 @enderror">
                                        @error('tax_number')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                        <p class="text-xs text-gray-500 mt-1">
                                            Your business TIN from URA (Uganda Revenue Authority)
                                        </p>
                                    </div>

                                    <!-- Tax Calculation Preview -->
                                    <div class="bg-indigo-50 border-2 border-indigo-200 rounded-lg p-5">
                                        <h4 class="font-bold text-indigo-900 mb-3 flex items-center justify-between">
                                            <span><i class="fas fa-calculator mr-2"></i>Tax Calculation & Compliance</span>
                                            <a href="{{ route('vat.index') }}" class="text-xs bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg font-bold transition inline-flex items-center gap-1">
                                                <i class="fas fa-calculator"></i> VAT Management
                                            </a>
                                        </h4>
                                        <div class="space-y-2 text-sm">
                                            <div class="flex justify-between">
                                                <span class="text-gray-700">Subtotal:</span>
                                                <span class="font-semibold">UGX 100,000</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-700">Tax (<span id="preview-rate">{{ $business->tax_rate ?? 18 }}</span>%):</span>
                                                <span class="font-semibold text-indigo-600" id="preview-tax">
                                                    UGX {{ number_format(100000 * (($business->tax_rate ?? 18) / 100), 0) }}
                                                </span>
                                            </div>
                                            <div class="border-t-2 border-indigo-300 pt-2 flex justify-between">
                                                <span class="font-bold text-gray-900">Total:</span>
                                                <span class="font-bold text-indigo-900" id="preview-total">
                                                    UGX {{ number_format(100000 + (100000 * (($business->tax_rate ?? 18) / 100)), 0) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="mt-6">
                                    <button type="submit" 
                                            class="w-full px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold">
                                        <i class="fas fa-save mr-2"></i>Save Tax Settings
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Right: Information & Help -->
                        <div class="space-y-6">
                            
                            <!-- About Tax -->
                            <div class="bg-blue-50 border-l-4 border-blue-400 rounded-lg p-5">
                                <h4 class="font-bold text-blue-900 mb-3">
                                    <i class="fas fa-info-circle mr-2"></i>About Tax Settings
                                </h4>
                                <div class="text-sm text-blue-800 space-y-2">
                                    <p>When tax is enabled:</p>
                                    <ul class="list-disc list-inside space-y-1 ml-4">
                                        <li>Tax is automatically calculated on all sales</li>
                                        <li>Tax amount shows on receipts and invoices</li>
                                        <li>Your TIN number appears on documents</li>
                                        <li>Better compliance with tax regulations</li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Common Tax Rates -->
                            <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-lg p-5">
                                <h4 class="font-bold text-gray-900 mb-3">
                                    <i class="fas fa-globe-africa mr-2 text-purple-600"></i>
                                    Common East African Tax Rates
                                </h4>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between items-center p-2 bg-white rounded">
                                        <div>
                                            <span class="font-semibold">🇺🇬 Uganda VAT</span>
                                        </div>
                                        <span class="font-bold text-indigo-600">18%</span>
                                    </div>
                                    <div class="flex justify-between items-center p-2 bg-white rounded">
                                        <div>
                                            <span class="font-semibold">🇰🇪 Kenya VAT</span>
                                        </div>
                                        <span class="font-bold text-gray-600">16%</span>
                                    </div>
                                    <div class="flex justify-between items-center p-2 bg-white rounded">
                                        <div>
                                            <span class="font-semibold">🇹🇿 Tanzania VAT</span>
                                        </div>
                                        <span class="font-bold text-gray-600">18%</span>
                                    </div>
                                    <div class="flex justify-between items-center p-2 bg-white rounded">
                                        <div>
                                            <span class="font-semibold">🇷🇼 Rwanda VAT</span>
                                        </div>
                                        <span class="font-bold text-gray-600">18%</span>
                                    </div>
                                </div>
                            </div>

                            <!-- URA Information -->
                            <div class="bg-green-50 border-2 border-green-300 rounded-lg p-5">
                                <h4 class="font-bold text-green-900 mb-3">
                                    <i class="fas fa-landmark mr-2"></i>Uganda Revenue Authority (URA)
                                </h4>
                                <p class="text-sm text-green-800 mb-3">
                                    Need to register for TIN or have questions about taxes?
                                </p>
                                <a href="https://www.ura.go.ug/" 
                                   target="_blank"
                                   class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700">
                                    <i class="fas fa-external-link-alt mr-2"></i>
                                    Visit URA Website
                                </a>
                            </div>

                            <!-- Warning -->
                            <div class="bg-yellow-50 border-l-4 border-yellow-400 rounded-lg p-5">
                                <div class="flex items-start">
                                    <i class="fas fa-exclamation-triangle text-yellow-600 mr-3 mt-1"></i>
                                    <div class="text-sm text-yellow-800">
                                        <p class="font-semibold mb-1">Important Notice:</p>
                                        <p>Consult with your accountant or tax advisor to ensure compliance with local tax regulations.</p>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <!-- Tax JavaScript -->
            <script>
                function toggleTaxFields(checkbox) {
                    const taxFields = document.getElementById('tax-fields');
                    const statusBadge = document.getElementById('tax-status-badge');
                    const statusText = document.getElementById('tax-status-text');
                    
                    if (checkbox.checked) {
                        taxFields.classList.remove('hidden');
                        statusBadge.className = 'inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800';
                        statusText.textContent = 'Tax Enabled';
                    } else {
                        taxFields.classList.add('hidden');
                        statusBadge.className = 'inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-gray-200 text-gray-700';
                        statusText.textContent = 'Tax Disabled';
                    }
                }

                // Update tax preview when rate changes
                const taxRateInput = document.querySelector('input[name="tax_rate"]');
                if (taxRateInput) {
                    taxRateInput.addEventListener('input', function() {
                        const rate = parseFloat(this.value) || 0;
                        const subtotal = 100000;
                        const tax = subtotal * (rate / 100);
                        const total = subtotal + tax;
                        
                        document.getElementById('preview-rate').textContent = rate.toFixed(2);
                        document.getElementById('preview-tax').textContent = 'UGX ' + tax.toLocaleString('en-UG', {maximumFractionDigits: 0});
                        document.getElementById('preview-total').textContent = 'UGX ' + total.toLocaleString('en-UG', {maximumFractionDigits: 0});
                    });
                }
            </script>



            <!-- ============================================ -->
            <!-- TAB 5: ADVANCED SETTINGS -->
            <!-- ============================================ -->
            <div id="advanced-tab" class="tab-content hidden">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">
                        <i class="fas fa-sliders-h text-indigo-600 mr-2"></i>
                        Advanced Settings
                    </h2>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        
                        <!-- Left: Business Status -->
                        <div>
                            <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-lg p-6 mb-6">
                                <h3 class="text-lg font-bold text-gray-900 mb-4">
                                    <i class="fas fa-power-off mr-2 text-red-600"></i>
                                    Business Status
                                </h3>
                                
                                <div class="flex items-center justify-between mb-4">
                                    <div>
                                        <p class="font-semibold text-gray-900">Current Status:</p>
                                        <p class="text-sm text-gray-600 mt-1">
                                            {{ $business->is_active ? 'Your business is active' : 'Your business is inactive' }}
                                        </p>
                                    </div>
                                    <span class="px-4 py-2 rounded-full text-sm font-semibold
                                        {{ $business->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        <i class="fas fa-circle text-xs mr-1"></i>
                                        {{ $business->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>

                                <form action="{{ route('settings.toggle-status') }}" method="POST" 
                                      onsubmit="return confirm('Are you sure you want to {{ $business->is_active ? 'deactivate' : 'activate' }} your business?');">
                                    @csrf
                                    <button type="submit" 
                                            class="w-full px-6 py-3 rounded-lg font-semibold
                                                {{ $business->is_active 
                                                    ? 'bg-red-100 text-red-700 hover:bg-red-200' 
                                                    : 'bg-green-100 text-green-700 hover:bg-green-200' }}">
                                        <i class="fas fa-{{ $business->is_active ? 'pause' : 'play' }} mr-2"></i>
                                        {{ $business->is_active ? 'Deactivate Business' : 'Activate Business' }}
                                    </button>
                                </form>

                                @if(!$business->is_active)
                                <div class="mt-4 bg-red-50 border-l-4 border-red-400 p-3 rounded">
                                    <p class="text-xs text-red-800">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                        <strong>Warning:</strong> Inactive businesses cannot process sales or access most features.
                                    </p>
                                </div>
                                @endif
                            </div>

                            <!-- PWA Update Status Widget -->
                            <div class="mb-6" id="pwa-update-widget-container"></div>

                            <!-- Subscription Information -->
                            <div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-lg p-6">
                                <h3 class="text-lg font-bold text-gray-900 mb-4">
                                    <i class="fas fa-crown mr-2 text-yellow-500"></i>
                                    Subscription Plan
                                </h3>

                                <div class="space-y-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Current Plan:</span>
                                        <span class="font-bold text-indigo-900">
                                            {{ ucfirst($business->subscription_plan ?? 'Free') }}
                                        </span>
                                    </div>

                                    @if($business->subscription_expires_at)
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Expires:</span>
                                        <span class="font-semibold text-gray-900">
                                            {{ $business->subscription_expires_at->format('d M Y') }}
                                        </span>
                                    </div>

                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Days Remaining:</span>
                                        <span class="font-bold text-{{ $business->subscription_status_color }}-600">
                                            {{ $business->daysUntilExpiry() }} days
                                        </span>
                                    </div>
                                    @else
                                    <div class="bg-green-100 border border-green-300 rounded p-3 text-center">
                                        <p class="text-sm font-semibold text-green-900">
                                            <i class="fas fa-infinity mr-2"></i>Lifetime Access
                                        </p>
                                    </div>
                                    @endif

                                    <div class="pt-3 border-t border-gray-300">
                                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold
                                            bg-{{ $business->subscription_status_color }}-100 
                                            text-{{ $business->subscription_status_color }}-800">
                                            <i class="fas fa-circle text-xs mr-2"></i>
                                            {{ $business->subscription_status_text }}
                                        </span>
                                    </div>

                                    @if($business->isSubscriptionExpiringSoon())
                                    <div class="bg-orange-50 border-l-4 border-orange-400 p-3 rounded">
                                        <p class="text-xs text-orange-800">
                                            <i class="fas fa-exclamation-circle mr-1"></i>
                                            <strong>Reminder:</strong> Your subscription expires soon. Please renew to avoid service interruption.
                                        </p>
                                    </div>
                                    @endif

                                    <button class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold">
                                        <i class="fas fa-arrow-up mr-2"></i>Upgrade Plan
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Business Statistics & Info -->
                        <div class="space-y-6">
                            
                            <!-- Statistics Cards -->
                            <div class="bg-gradient-to-br from-blue-50 to-cyan-50 rounded-lg p-6">
                                <h3 class="text-lg font-bold text-gray-900 mb-4">
                                    <i class="fas fa-chart-line mr-2 text-blue-600"></i>
                                    Business Statistics
                                </h3>

                                <div class="grid grid-cols-2 gap-4">
                                    <!-- Total Sales -->
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex items-center justify-between mb-2">
                                            <i class="fas fa-shopping-cart text-2xl text-blue-600"></i>
                                            <span class="text-xs text-gray-500">Total</span>
                                        </div>
                                        <p class="text-2xl font-bold text-gray-900">{{ number_format($business->total_sales) }}</p>
                                        <p class="text-xs text-gray-600 mt-1">Sales</p>
                                    </div>

                                    <!-- Total Revenue -->
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex items-center justify-between mb-2">
                                            <i class="fas fa-money-bill-wave text-2xl text-green-600"></i>
                                            <span class="text-xs text-gray-500">Total</span>
                                        </div>
                                        <p class="text-lg font-bold text-gray-900">
                                            {{ number_format($business->total_revenue / 1000000, 1) }}M
                                        </p>
                                        <p class="text-xs text-gray-600 mt-1">Revenue (UGX)</p>
                                    </div>

                                    <!-- Total Customers -->
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex items-center justify-between mb-2">
                                            <i class="fas fa-users text-2xl text-purple-600"></i>
                                            <span class="text-xs text-gray-500">Total</span>
                                        </div>
                                        <p class="text-2xl font-bold text-gray-900">{{ number_format($business->total_customers) }}</p>
                                        <p class="text-xs text-gray-600 mt-1">Customers</p>
                                    </div>

                                    <!-- Total Products -->
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex items-center justify-between mb-2">
                                            <i class="fas fa-box text-2xl text-orange-600"></i>
                                            <span class="text-xs text-gray-500">Total</span>
                                        </div>
                                        <p class="text-2xl font-bold text-gray-900">{{ number_format($business->total_products) }}</p>
                                        <p class="text-xs text-gray-600 mt-1">Products</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Business Information Summary -->
                            <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-lg p-6">
                                <h3 class="text-lg font-bold text-gray-900 mb-4">
                                    <i class="fas fa-info-circle mr-2 text-green-600"></i>
                                    Business Information
                                </h3>

                                <div class="space-y-3 text-sm">
                                    <div class="flex items-start">
                                        <i class="fas fa-building text-green-600 mr-3 mt-1"></i>
                                        <div class="flex-1">
                                            <p class="text-gray-600">Business Name</p>
                                            <p class="font-semibold text-gray-900">{{ $business->name }}</p>
                                        </div>
                                    </div>

                                    <div class="flex items-start">
                                        <i class="fas fa-tag text-green-600 mr-3 mt-1"></i>
                                        <div class="flex-1">
                                            <p class="text-gray-600">Category</p>
                                            <p class="font-semibold text-gray-900">
                                                {{ $business->businessCategory->name ?? 'Not set' }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-start">
                                        <i class="fas fa-calendar-alt text-green-600 mr-3 mt-1"></i>
                                        <div class="flex-1">
                                            <p class="text-gray-600">Member Since</p>
                                            <p class="font-semibold text-gray-900">
                                                {{ $business->created_at->format('d M Y') }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-start">
                                        <i class="fas fa-user-tie text-green-600 mr-3 mt-1"></i>
                                        <div class="flex-1">
                                            <p class="text-gray-600">Total Users</p>
                                            <p class="font-semibold text-gray-900">
                                                {{ $business->users()->count() }} user(s)
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-start">
                                        <i class="fas fa-map-marker-alt text-green-600 mr-3 mt-1"></i>
                                        <div class="flex-1">
                                            <p class="text-gray-600">Locations</p>
                                            <p class="font-semibold text-gray-900">
                                                {{ $business->locations()->count() }} location(s)
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Danger Zone -->
                            <div class="bg-red-50 border-2 border-red-300 rounded-lg p-6">
                                <h3 class="text-lg font-bold text-red-900 mb-4">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    Danger Zone
                                </h3>

                                <div class="space-y-3">
                                    <p class="text-sm text-red-800">
                                        These actions are permanent and cannot be undone. Please proceed with caution.
                                    </p>

                                    <button onclick="alert('Contact support to delete your business account.')" 
                                            class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold">
                                        <i class="fas fa-trash-alt mr-2"></i>
                                        Delete Business Account
                                    </button>

                                    <p class="text-xs text-red-700">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        This will permanently delete all data including sales, products, customers, and users.
                                    </p>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>
        <!-- End of Tab Contents -->

    </div>
</div>

<!-- ============================================ -->
<!-- TAB SWITCHING JAVASCRIPT -->
<!-- ============================================ -->
<script>
    // Tab Switching Functionality
    document.addEventListener('DOMContentLoaded', function() {
        if (window.renderUpdateStatusWidget) {
            window.renderUpdateStatusWidget('pwa-update-widget-container');
        }

        const tabButtons = document.querySelectorAll('.tab-button');
        const tabContents = document.querySelectorAll('.tab-content');

        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const targetTab = this.getAttribute('data-tab');

                // Remove active class from all buttons
                tabButtons.forEach(btn => {
                    btn.classList.remove('active', 'border-indigo-600', 'text-indigo-600');
                    btn.classList.add('border-transparent', 'text-gray-500');
                });

                // Add active class to clicked button
                this.classList.add('active', 'border-indigo-600', 'text-indigo-600');
                this.classList.remove('border-transparent', 'text-gray-500');

                // Hide all tab contents
                tabContents.forEach(content => {
                    content.classList.remove('active');
                    content.classList.add('hidden');
                });

                // Show target tab content
                const targetContent = document.getElementById(targetTab + '-tab');
                if (targetContent) {
                    targetContent.classList.remove('hidden');
                    targetContent.classList.add('active');
                }
            });
        });

        // Handle hash navigation (e.g., /settings#email-settings)
        const hash = window.location.hash.substring(1);
        if (hash) {
            const targetButton = document.querySelector(`[data-tab="${hash}"]`);
            if (targetButton) {
                targetButton.click();
            }
        }
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.animate-fade-in');
        alerts.forEach(alert => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);
</script>

<!-- ============================================ -->
<!-- CUSTOM ANIMATIONS -->
<!-- ============================================ -->
<style>
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

    .animate-fade-in {
        animation: fadeIn 0.3s ease-in-out;
    }

    .tab-button {
        transition: all 0.2s ease-in-out;
    }

    .tab-button:hover {
        background-color: rgba(99, 102, 241, 0.05);
    }

    .tab-content {
        animation: fadeIn 0.3s ease-in-out;
    }

    /* Custom scrollbar for tabs */
    .overflow-x-auto::-webkit-scrollbar {
        height: 4px;
    }

    .overflow-x-auto::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    .overflow-x-auto::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 4px;
    }

    .overflow-x-auto::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
</style>

@endsection