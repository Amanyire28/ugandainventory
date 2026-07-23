<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Uganda Inventory</title>
  <!-- <script src="https://cdn.tailwindcss.com"></script> -->
   @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-500 via-indigo-600 to-purple-600 min-h-screen">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full">
            <!-- Back to Home -->
            <div class="text-center mb-6">
                <a href="{{ route('welcome') }}" class="text-white hover:text-gray-200 font-medium">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Home
                </a>
            </div>

            <!-- Login Card -->
            <div class="bg-white rounded-2xl shadow-2xl p-8">
                <div class="text-center mb-8">
                    <i class="fas fa-sign-in-alt text-5xl text-indigo-600 mb-4"></i>
                    <h2 class="text-3xl font-extrabold text-gray-900">
                        Welcome Back!
                    </h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Sign in to access your dashboard
                    </p>
                </div>

                @if(session('error'))
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <span>{{ session('error') }}</span>
                        </div>
                    </div>
                @endif

                @if(session('success'))
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle mr-2"></i>
                            <span>{{ session('success') }}</span>
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-envelope text-indigo-600 mr-1"></i>
                            Email Address <span class="text-red-500">*</span>
                        </label>
                        <input id="email" name="email" type="email" required autofocus
                               value="{{ old('email') }}"
                               class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                               placeholder="your@email.com">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-lock text-indigo-600 mr-1"></i>
                            Password <span class="text-red-500">*</span>
                        </label>
                        <input id="password" name="password" type="password" required
                               class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                               placeholder="Enter your password">
                        @error('password')
                            <p class="mt-1 text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input id="remember" name="remember" type="checkbox"
                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="remember" class="ml-2 block text-sm text-gray-900">
                                Remember me
                            </label>
                        </div>

                        <div class="text-sm">
                            <a href="#" class="font-medium text-indigo-600 hover:text-indigo-500">
                                Forgot password?
                            </a>
                        </div>
                    </div>

                    <!-- Info Box -->
                    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded">
                        <div class="flex">
                            <i class="fas fa-lightbulb text-yellow-500 mt-1 mr-3"></i>
                            <div class="text-sm text-yellow-700">
                                <p class="font-medium">Login Tips:</p>
                                <ul class="list-disc list-inside mt-1 text-xs space-y-1">
                                    <li>Use the email you registered/were assigned</li>
                                    <li>Contact your business owner if you forgot your password</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div>
                        <button type="submit"
                                class="group relative w-full flex justify-center py-4 px-4 border border-transparent text-lg font-bold rounded-lg text-white bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-lg transform transition hover:scale-105">
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            Sign In to Dashboard
                        </button>
                    </div>

                    <!-- Register Link -->
                    <div class="text-center">
                        <span class="text-gray-600">Don't have an account?</span>
                        <a href="{{ route('register') }}" class="font-medium text-indigo-600 hover:text-indigo-500 ml-1">
                            Register your business <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </form>
            </div>

            <!-- Support -->
            <div class="mt-8 text-center text-white">
                <p class="text-sm">
                    <i class="fas fa-question-circle mr-1"></i>
                    Need help? 
                    <a href="#" class="underline hover:text-gray-200">Contact Support</a>
                    or call +256 777143020
                </p>
            </div>
        </div>
    </div>
</body>
</html>