<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'ADOQA ERP') }} — Login</title>
    <link rel="icon" type="image/png" href="{{ asset('images/adoqa-logo.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 text-slate-800 antialiased font-sans">
    <div class="max-w-md w-full space-y-8 bg-white adoqa-glass-card p-8 rounded-xl shadow-sm border border-slate-200">
        <div class="text-center">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-xl bg-white p-2 mb-3 shadow-sm border border-slate-200">
                <img src="{{ asset('images/adoqa-logo.png') }}" alt="ADOQA Logo" class="w-full h-full object-contain">
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Sign in to ADOQA ERP</h1>
            <p class="mt-2 text-sm text-slate-500">Commercial Operations & Management Platform</p>
        </div>

        @if ($errors->any())
            <div class="rounded-lg bg-rose-50 border border-rose-200 p-4" role="alert">
                <div class="flex">
                    <div class="text-sm font-medium text-rose-800">
                        {{ $errors->first() }}
                    </div>
                </div>
            </div>
        @endif

        <form class="mt-6 space-y-5" action="{{ route('login') }}" method="POST">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">Email Address</label>
                <div class="mt-1">
                    <input id="email" name="email" type="email" autocomplete="email" required
                        value="{{ old('email') }}"
                        class="appearance-none block w-full px-3 py-2 border border-slate-300 rounded-lg shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#22B14C] focus:border-[#22B14C] text-sm">
                </div>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                <div class="mt-1">
                    <input id="password" name="password" type="password" autocomplete="current-password" required
                        class="appearance-none block w-full px-3 py-2 border border-slate-300 rounded-lg shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#22B14C] focus:border-[#22B14C] text-sm">
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input id="remember" name="remember" type="checkbox" value="1"
                        class="h-4 w-4 text-[#22B14C] focus:ring-[#22B14C] border-slate-300 rounded">
                    <label for="remember" class="ml-2 block text-sm text-slate-600">Remember this device</label>
                </div>
            </div>

            <div>
                <button type="submit" id="login-submit"
                    class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-[#22B14C] hover:bg-[#1E9E44] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#22B14C] transition-colors">
                    Sign in
                </button>
            </div>
        </form>

        <div class="mt-6 pt-6 border-t border-slate-100 text-center text-xs text-slate-400">
            Strict tenant isolation. Access restricted to authorized business accounts.
        </div>
    </div>
</body>
</html>
