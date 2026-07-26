@extends('layouts.app')
@section('title','Owner Profile')
@section('page-title','Owner Profile')

@section('content')
  @if(session('success'))
    <div class="mb-4 p-3 rounded border border-green-200 bg-green-50 text-green-800">{{ session('success') }}</div>
  @endif
  @if($errors->any())
    <div class="mb-4 p-3 rounded border border-rose-200 bg-rose-50 text-rose-800">
      <ul class="list-disc pl-5 text-sm">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Profile photo -->
    <div class="card p-6">
      <h3 class="text-lg font-bold mb-4">Profile Photo</h3>

      <div class="flex items-center gap-4 mb-4">
        <img src="{{ route('profile.avatar') }}" alt="Avatar" class="h-20 w-20 rounded-full object-cover border" />
        <div class="text-sm text-gray-600">
          <p class="font-semibold">{{ $user->name }}</p>
          <p class="text-xs">{{ $user->email }}</p>
        </div>
      </div>

      <form method="POST" action="{{ route('profile.update_photo') }}" enctype="multipart/form-data" class="space-y-3">
        @csrf
        <div>
          <input type="file" name="photo" accept="image/*" class="w-full border rounded p-2" required>
          @error('photo')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="flex items-center justify-end gap-2">
          <button class="px-4 py-2 bg-indigo-600 text-white rounded">Upload</button>
        </div>
      </form>

      @if($user->profile_image)
        <form method="POST" action="{{ route('profile.delete_photo') }}" class="mt-3 text-right">
          @csrf
          @method('DELETE')
          <button class="px-4 py-2 bg-gray-100 text-gray-800 rounded">Remove Photo</button>
        </form>
      @endif
    </div>

    <!-- Basic info (name, phone) -->
    <div class="card p-6">
      <h3 class="text-lg font-bold mb-4">Basic Information</h3>
      <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
        @csrf
        <div>
          <label class="block text-sm text-gray-700">Name</label>
          <input type="text" name="name" value="{{ old('name', $user->name) }}" class="mt-1 w-full border rounded p-2" required>
          @error('name')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
          <label class="block text-sm text-gray-700">Phone</label>
          <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="mt-1 w-full border rounded p-2" required>
          @error('phone')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="text-right">
          <button class="px-4 py-2 bg-indigo-600 text-white rounded">Save Changes</button>
        </div>
      </form>
    </div>

    <!-- Owner-only: Email & Password -->
    <div class="card p-6">
      <h3 class="text-lg font-bold mb-4">Owner Settings</h3>

      <!-- Update Email -->
      <div class="mb-6">
        <h4 class="text-sm font-semibold text-gray-700 mb-2">Update Email</h4>
        <form method="POST" action="{{ route('profile.update_email') }}" class="space-y-3">
          @csrf
          <div>
            <label class="block text-sm text-gray-700">Email</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" class="mt-1 w-full border rounded p-2" required>
            @error('email')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
          </div>
          <div class="text-right">
            <button class="px-4 py-2 bg-indigo-600 text-white rounded">Update Email</button>
          </div>
        </form>
      </div>

      <!-- Update Password (requires current password) -->
      <div>
        <h4 class="text-sm font-semibold text-gray-700 mb-2">Change Password</h4>
        <form method="POST" action="{{ route('profile.update_password') }}" class="space-y-3">
          @csrf
          <div>
            <label class="block text-sm text-gray-700">Current Password</label>
            <div class="relative">
              <input id="profile_curr_pass" type="password" name="current_password" class="mt-1 w-full border rounded p-2 pr-10" required>
              <button type="button" onclick="togglePasswordVisibility('profile_curr_pass', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none p-1" title="Toggle password visibility">
                <i class="fas fa-eye text-sm"></i>
              </button>
            </div>
            @error('current_password')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
          </div>
          <div>
            <label class="block text-sm text-gray-700">New Password</label>
            <div class="relative">
              <input id="profile_new_pass" type="password" name="password" class="mt-1 w-full border rounded p-2 pr-10" required>
              <button type="button" onclick="togglePasswordVisibility('profile_new_pass', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none p-1" title="Toggle password visibility">
                <i class="fas fa-eye text-sm"></i>
              </button>
            </div>
            @error('password')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
          </div>
          <div>
            <label class="block text-sm text-gray-700">Confirm New Password</label>
            <div class="relative">
              <input id="profile_conf_pass" type="password" name="password_confirmation" class="mt-1 w-full border rounded p-2 pr-10" required>
              <button type="button" onclick="togglePasswordVisibility('profile_conf_pass', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none p-1" title="Toggle password visibility">
                <i class="fas fa-eye text-sm"></i>
              </button>
            </div>
          </div>
          <div class="text-right">
            <button class="px-4 py-2 bg-indigo-600 text-white rounded font-bold text-sm">Update Password</button>
          </div>
        </form>
      </div>

      <!-- Danger zone: Delete account (owner only) -->
      <div class="mt-6 border-t pt-4">
        <h4 class="text-sm font-semibold text-rose-700 mb-2">Danger Zone</h4>
        <form method="POST" action="{{ route('profile.destroy') }}" class="space-y-3">
          @csrf
          @method('DELETE')
          <div>
            <label class="block text-sm text-gray-700">Confirm with Password</label>
            <div class="relative">
              <input id="profile_del_pass" type="password" name="password" class="mt-1 w-full border rounded p-2 pr-10" required>
              <button type="button" onclick="togglePasswordVisibility('profile_del_pass', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none p-1" title="Toggle password visibility">
                <i class="fas fa-eye text-sm"></i>
              </button>
            </div>
          </div>
          <div class="text-right">
            <button class="px-4 py-2 bg-rose-600 text-white rounded font-bold text-sm">Delete Account</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection