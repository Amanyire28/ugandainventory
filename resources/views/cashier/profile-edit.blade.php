@extends('layouts.cashier-layout')

@section('title', 'My Profile')

@section('page-title')
    <i class="fas fa-user-circle text-green-600 mr-2"></i>My Profile
@endsection

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    
    <!-- Profile Card -->
    <div class="bg-white rounded-xl shadow-lg p-8">
        <div class="flex items-center space-x-6 mb-8 pb-8 border-b">
            <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center">
                <span class="text-4xl font-bold text-green-600">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </span>
            </div>
            <div>
                <h2 class="text-3xl font-bold text-gray-900">{{ $user->name }}</h2>
                <p class="text-gray-600 mt-1">
                    <i class="fas fa-user-tag text-green-600 mr-1"></i>
                    {{ $user->role->display_name }}
                </p>
                <p class="text-gray-600">
                    <i class="fas fa-building text-green-600 mr-1"></i>
                    {{ $user->business->name }}
                </p>
            </div>
        </div>

        <!-- Update Profile Form -->
        <form method="POST" action="{{ route('profile.update') }}" class="space-y-6">
            @csrf
            @method('PATCH')

            <h3 class="text-lg font-bold text-gray-800 mb-4">
                <i class="fas fa-edit text-green-600 mr-2"></i>Update Profile Information
            </h3>

            <!-- Name -->
            <div>
                <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">
                    Full Name <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       id="name" 
                       name="name" 
                       value="{{ old('name', $user->name) }}"
                       required
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email (Read-only) -->
            <div>
                <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                    Email Address
                </label>
                <input type="email" 
                       id="email" 
                       value="{{ $user->email }}"
                       readonly
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed">
                <p class="mt-1 text-xs text-gray-500">
                    <i class="fas fa-info-circle mr-1"></i>Email cannot be changed. Contact your administrator.
                </p>
            </div>

            <!-- Phone -->
            <div>
                <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">
                    Phone Number <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       id="phone" 
                       name="phone" 
                       value="{{ old('phone', $user->phone) }}"
                       required
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                @error('phone')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="w-full py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-bold">
                <i class="fas fa-save mr-2"></i>Update Profile
            </button>
        </form>
    </div>

    <!-- Change Password Card -->
    <div class="bg-white rounded-xl shadow-lg p-8">
        <h3 class="text-lg font-bold text-gray-800 mb-6">
            <i class="fas fa-lock text-green-600 mr-2"></i>Change Password
        </h3>

        <form method="POST" action="{{ route('profile.password.update') }}" class="space-y-6">
            @csrf
            @method('PATCH')

            <!-- Current Password -->
            <div>
                <label for="current_password" class="block text-sm font-semibold text-gray-700 mb-2">
                    Current Password <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="password" 
                           id="current_password" 
                           name="current_password" 
                           required
                           class="w-full px-4 py-3 pr-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    <button type="button" onclick="togglePasswordVisibility('current_password', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none p-1" title="Toggle password visibility">
                        <i class="fas fa-eye text-base"></i>
                    </button>
                </div>
                @error('current_password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- New Password -->
            <div>
                <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                    New Password <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required
                           class="w-full px-4 py-3 pr-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    <button type="button" onclick="togglePasswordVisibility('password', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none p-1" title="Toggle password visibility">
                        <i class="fas fa-eye text-base"></i>
                    </button>
                </div>
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">
                    <i class="fas fa-info-circle mr-1"></i>Password must be at least 8 characters
                </p>
            </div>

            <!-- Confirm Password -->
            <div>
                <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-2">
                    Confirm New Password <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="password" 
                           id="password_confirmation" 
                           name="password_confirmation" 
                           required
                           class="w-full px-4 py-3 pr-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    <button type="button" onclick="togglePasswordVisibility('password_confirmation', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none p-1" title="Toggle password visibility">
                        <i class="fas fa-eye text-base"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="w-full py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-bold">
                <i class="fas fa-key mr-2"></i>Change Password
            </button>
        </form>
    </div>
</div>
@endsection