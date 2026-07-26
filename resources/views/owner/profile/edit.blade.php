@extends('layouts.app')
@section('title','Owner Profile')
@section('page-title','Owner Profile')

@section('content')
  @if(session('success'))
    <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800 shadow-sm">
      <div class="flex items-center gap-2">
        <svg class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M12 22C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
        </svg>
        <span class="font-medium">{{ session('success') }}</span>
      </div>
    </div>
  @endif

  @if($errors->any())
    <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800 shadow-sm">
      <div class="flex items-start gap-3">
        <svg class="h-5 w-5 text-rose-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2v6m0-6V4m0 0L6 8m12 0l-4-4"/>
        </svg>
        <ul class="list-disc pl-5 text-sm">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    </div>
  @endif

  <!-- User header: shows profile image if exists, else initials; links to this page -->
  <a href="{{ route('owner.profile.edit') }}" class="group mb-8 block rounded-2xl bg-gradient-to-r from-indigo-50 via-white to-white p-4 shadow-sm ring-1 ring-gray-100 hover:shadow-md transition">
    <div class="flex items-center gap-4">
      @if(auth()->user()?->profile_image)
        <img src="{{ route('owner.profile.avatar') }}" alt="Profile" class="h-12 w-12 rounded-full object-cover ring-2 ring-indigo-500/30" />
      @else
        <div class="h-12 w-12 rounded-full bg-indigo-600/90 text-white ring-2 ring-indigo-500/30 flex items-center justify-center">
          <span class="text-lg font-bold">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</span>
        </div>
      @endif
      <div class="flex-1">
        <p class="text-sm font-semibold text-gray-900 truncate">{{ auth()->user()->name }}</p>
        <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</p>
      </div>
      <div class="hidden sm:flex items-center gap-1 text-xs text-indigo-600">
        <span class="opacity-80">Manage Profile</span>
        <svg class="h-4 w-4 opacity-80 group-hover:translate-x-0.5 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
      </div>
    </div>
  </a>

  <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <!-- Profile Photo Card -->
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900">Profile Photo</h3>
        <span class="text-xs px-2 py-1 rounded-full bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100">Owner</span>
      </div>

      <div class="mt-4 flex items-center gap-4">
        @if($user->profile_image)
          <img src="{{ route('owner.profile.avatar') }}" alt="Avatar" class="h-20 w-20 rounded-xl object-cover ring-2 ring-indigo-500/30 shadow-sm" />
        @else
          <div class="h-20 w-20 rounded-xl bg-gray-100 text-gray-500 ring-1 ring-gray-200 flex items-center justify-center shadow-sm">
            <span class="text-2xl font-bold">{{ $user->initials }}</span>
          </div>
        @endif
        <div class="text-sm text-gray-600">
          <p class="font-medium text-gray-900">{{ $user->name }}</p>
          <p class="text-xs">{{ $user->email }}</p>
          <p class="mt-1 text-xs text-gray-500">Upload a clear square photo for best results.</p>
        </div>
      </div>

      <form method="POST" action="{{ route('owner.profile.update_photo') }}" enctype="multipart/form-data" class="mt-6 space-y-4">
        @csrf
        <div>
          <label class="block text-sm font-medium text-gray-700">Select Photo</label>
          <input type="file" name="photo" accept="image/*" class="mt-1 block w-full rounded-lg border-gray-300 text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-600 file:px-3 file:py-2 file:text-white hover:file:bg-indigo-700" required>
          @error('photo')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="flex justify-end">
          <button class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v16h16M8 12h8M8 8h8M8 16h8"/>
            </svg>
            Upload
          </button>
        </div>
      </form>
    </div>

    <!-- Basic Information Card -->
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900">Basic Information</h3>
        <span class="text-xs px-2 py-1 rounded-full bg-sky-50 text-sky-700 ring-1 ring-sky-100">Profile</span>
      </div>

      <form method="POST" action="{{ route('owner.profile.update') }}" class="mt-4 space-y-5">
        @csrf
        <div>
          <label class="block text-sm font-medium text-gray-700">Name</label>
          <input type="text" name="name" value="{{ old('name', $user->name) }}" class="mt-1 w-full rounded-lg border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
          @error('name')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Phone</label>
          <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="mt-1 w-full rounded-lg border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
          @error('phone')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="flex justify-end">
          <button class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Save Changes
          </button>
        </div>
      </form>
    </div>

    <!-- Owner Settings (Email & Password) -->
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900">Owner Settings</h3>
        <span class="text-xs px-2 py-1 rounded-full bg-violet-50 text-violet-700 ring-1 ring-violet-100">Secure</span>
      </div>

      <!-- Update Email -->
      <div class="mt-4 rounded-xl bg-indigo-50 via-white to-white p-4 ring-1 ring-indigo-100">
        <h4 class="text-sm font-semibold text-indigo-900 mb-3">Update Email</h4>
        <form method="POST" action="{{ route('owner.profile.update_email') }}" class="space-y-4">
          @csrf
          <div>
            <label class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" class="mt-1 w-full rounded-lg border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
            @error('email')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
          </div>
          <div class="flex justify-end">
            <button class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12H8m8-4H8m8 8H8"/>
              </svg>
              Update Email
            </button>
          </div>
        </form>
      </div>

      <!-- Update Password -->
      <div class="mt-6 rounded-xl bg-gradient-to-br from-rose-50 via-white to-white p-4 ring-1 ring-rose-100">
        <h4 class="text-sm font-semibold text-gray-900 mb-3">Change Password</h4>
        <form method="POST" action="{{ route('owner.profile.update_password') }}" class="space-y-4">
          @csrf
          <div>
            <label class="block text-sm font-medium text-gray-700">Current Password</label>
            <div class="relative">
              <input id="owner_curr_pass" type="password" name="current_password" class="mt-1 w-full rounded-lg border-gray-300 px-3 py-2 pr-10 text-sm focus:border-rose-500 focus:ring-rose-500 border" required>
              <button type="button" onclick="togglePasswordVisibility('owner_curr_pass', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none p-1" title="Toggle password visibility">
                <i class="fas fa-eye text-sm"></i>
              </button>
            </div>
            @error('current_password')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">New Password</label>
              <div class="relative">
                <input id="owner_new_pass" type="password" name="password" class="mt-1 w-full rounded-lg border-gray-300 px-3 py-2 pr-10 text-sm focus:border-rose-500 focus:ring-rose-500 border" required>
                <button type="button" onclick="togglePasswordVisibility('owner_new_pass', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none p-1" title="Toggle password visibility">
                  <i class="fas fa-eye text-sm"></i>
                </button>
              </div>
              @error('password')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Confirm New Password</label>
              <div class="relative">
                <input id="owner_conf_pass" type="password" name="password_confirmation" class="mt-1 w-full rounded-lg border-gray-300 px-3 py-2 pr-10 text-sm focus:border-rose-500 focus:ring-rose-500 border" required>
                <button type="button" onclick="togglePasswordVisibility('owner_conf_pass', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none p-1" title="Toggle password visibility">
                  <i class="fas fa-eye text-sm"></i>
                </button>
              </div>
            </div>
          </div>
          <div class="flex justify-end">
            <button class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 .828-.895 1.5-2 1.5s-2-.672-2-1.5m8 0c0 .828-.895 1.5-2 1.5s-2-.672-2-1.5M21 12a9 9 0 10-18 0 9 9 0 0018 0z"/>
              </svg>
              Update Password
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection