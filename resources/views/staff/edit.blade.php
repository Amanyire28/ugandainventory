@extends('layouts.app')

@section('title', 'Edit Staff Member')

@section('page-title')
    <i class="fas fa-user-edit text-indigo-600 mr-2"></i>Edit Staff Member
@endsection

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-lg p-6">
        
        <form method="POST" action="{{ route('staff.update', $staff) }}" id="staffUpdateForm">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <!-- Full Name -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-user text-indigo-600 mr-1"></i>
                        Full Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name', $staff->name) }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-envelope text-indigo-600 mr-1"></i>
                        Email Address <span class="text-red-500">*</span>
                    </label>
                    <input type="email" name="email" value="{{ old('email', $staff->email) }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('email') border-red-500 @enderror">
                    @error('email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Phone -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-phone text-indigo-600 mr-1"></i>
                        Phone Number <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="phone" value="{{ old('phone', $staff->phone) }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('phone') border-red-500 @enderror">
                    @error('phone')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Role -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-user-tag text-indigo-600 mr-1"></i>
                        Role <span class="text-red-500">*</span>
                    </label>
                    <select name="role_id" required 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('role_id') border-red-500 @enderror">
                        <option value="">Select Role</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ old('role_id', $staff->role_id) == $role->id ? 'selected' : '' }}>
                                {{ $role->display_name }} - {{ $role->description }}
                            </option>
                        @endforeach
                    </select>
                    @error('role_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-toggle-on text-indigo-600 mr-1"></i>
                        Status <span class="text-red-500">*</span>
                    </label>
                    <select name="is_active" required 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('is_active') border-red-500 @enderror">
                        <option value="1" {{ old('is_active', $staff->is_active) == 1 ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ old('is_active', $staff->is_active) == 0 ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('is_active')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- ✅ PASSWORD CHANGE SECTION (WITH OLD PASSWORD VERIFICATION) -->
                <div class="md:col-span-2">
                    <div class="border-t border-gray-200 pt-6 mt-2">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-800">
                                <i class="fas fa-key text-indigo-600 mr-2"></i>
                                Change Password (Optional)
                            </h3>
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" 
                                       id="changePasswordCheckbox" 
                                       class="mr-2 h-4 w-4 text-indigo-600 focus:ring-indigo-500 rounded"
                                       onchange="togglePasswordFields()">
                                <span class="text-sm text-gray-700">Change password</span>
                            </label>
                        </div>
                        
                        <div id="passwordFields" class="hidden space-y-4">
                            <div class="p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded">
                                <p class="text-sm text-yellow-800">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    You must verify the current password before setting a new one.
                                </p>
                            </div>

                            <!-- ✅ Current/Old Password -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-lock text-red-600 mr-1"></i>
                                    Current Password <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input type="password" 
                                           name="current_password" 
                                           id="current_password"
                                           class="w-full px-4 py-2 pr-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('current_password') border-red-500 @enderror"
                                           placeholder="Enter current password to verify">
                                    <button type="button" onclick="togglePasswordVisibility('current_password', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none p-1" title="Toggle password visibility">
                                        <i class="fas fa-eye text-base"></i>
                                    </button>
                                </div>
                                @error('current_password')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- New Password -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-key text-green-600 mr-1"></i>
                                        New Password <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <input type="password" 
                                               name="password" 
                                               id="password"
                                               class="w-full px-4 py-2 pr-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('password') border-red-500 @enderror"
                                               placeholder="Minimum 8 characters">
                                        <button type="button" onclick="togglePasswordVisibility('password', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none p-1" title="Toggle password visibility">
                                            <i class="fas fa-eye text-base"></i>
                                        </button>
                                    </div>
                                    @error('password')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Confirm New Password -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-check-circle text-green-600 mr-1"></i>
                                        Confirm New Password <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <input type="password" 
                                               name="password_confirmation" 
                                               id="password_confirmation"
                                               class="w-full px-4 py-2 pr-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                               placeholder="Re-enter new password">
                                        <button type="button" onclick="togglePasswordVisibility('password_confirmation', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none p-1" title="Toggle password visibility">
                                            <i class="fas fa-eye text-base"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Password Strength Indicator -->
                            <div id="passwordStrength" class="hidden">
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm text-gray-600">Password Strength:</span>
                                    <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                                        <div id="strengthBar" class="h-full transition-all duration-300"></div>
                                    </div>
                                    <span id="strengthText" class="text-sm font-medium"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end space-x-4 mt-6 pt-6 border-t">
                <a href="{{ route('staff.index') }}" 
                   class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
                <button type="submit" 
                        class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    <i class="fas fa-save mr-2"></i>Update Staff Member
                </button>
            </div>
        </form>

    </div>
</div>

@push('scripts')
<script>
    // Toggle password fields
    function togglePasswordFields() {
        const checkbox = document.getElementById('changePasswordCheckbox');
        const fields = document.getElementById('passwordFields');
        const currentPassword = document.getElementById('current_password');
        const password = document.getElementById('password');
        const passwordConfirm = document.getElementById('password_confirmation');
        
        if (checkbox.checked) {
            fields.classList.remove('hidden');
            currentPassword.required = true;
            password.required = true;
            passwordConfirm.required = true;
        } else {
            fields.classList.add('hidden');
            currentPassword.required = false;
            password.required = false;
            passwordConfirm.required = false;
            currentPassword.value = '';
            password.value = '';
            passwordConfirm.value = '';
        }
    }

    // Password strength checker
    document.getElementById('password')?.addEventListener('input', function() {
        const password = this.value;
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        const strengthDiv = document.getElementById('passwordStrength');
        
        if (password.length === 0) {
            strengthDiv.classList.add('hidden');
            return;
        }
        
        strengthDiv.classList.remove('hidden');
        
        let strength = 0;
        if (password.length >= 8) strength++;
        if (password.match(/[a-z]/)) strength++;
        if (password.match(/[A-Z]/)) strength++;
        if (password.match(/[0-9]/)) strength++;
        if (password.match(/[^a-zA-Z0-9]/)) strength++;
        
        let color, text, width;
        
        if (strength <= 2) {
            color = 'bg-red-500';
            text = 'Weak';
            width = '33%';
        } else if (strength <= 3) {
            color = 'bg-yellow-500';
            text = 'Medium';
            width = '66%';
        } else {
            color = 'bg-green-500';
            text = 'Strong';
            width = '100%';
        }
        
        strengthBar.className = `h-full transition-all duration-300 ${color}`;
        strengthBar.style.width = width;
        strengthText.textContent = text;
        strengthText.className = `text-sm font-medium ${color.replace('bg-', 'text-')}`;
    });

    // Form validation
    document.getElementById('staffUpdateForm').addEventListener('submit', function(e) {
        const checkbox = document.getElementById('changePasswordCheckbox');
        
        if (checkbox.checked) {
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('password').value;
            const confirmPassword = document.getElementById('password_confirmation').value;
            
            if (!currentPassword) {
                e.preventDefault();
                alert('Please enter the current password');
                document.getElementById('current_password').focus();
                return false;
            }
            
            if (!newPassword) {
                e.preventDefault();
                alert('Please enter a new password');
                document.getElementById('password').focus();
                return false;
            }
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New passwords do not match');
                document.getElementById('password_confirmation').focus();
                return false;
            }
            
            if (newPassword.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters');
                document.getElementById('password').focus();
                return false;
            }
        }
    });
</script>
@endpush
@endsection