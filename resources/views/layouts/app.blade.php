<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }} - Student Portal</title>

        <!-- Local Fonts -->
        <link rel="stylesheet" href="{{ asset('assets/css/inter-font.css') }}" />
        
        <!-- Font Awesome (loaded via local assets) -->
        {{-- Font Awesome should be installed locally via npm --}}

        <!-- REMOVE CDN Tailwind Link if present -->
        {{-- <link href="https://unpkg.com/tailwindcss/dist/tailwind.min.css" rel="stylesheet"> --}}

        <!-- Vite Compiled Assets -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @stack('styles') {{-- For page-specific additional CSS --}}
    </head>
    <body class="font-sans antialiased bg-gray-100 dark:bg-gray-900">
        <div class="min-h-screen">
            @include('layouts.navigation') {{-- Or your custom student navigation --}}

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white dark:bg-gray-800 shadow">
                    <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main class="py-4">
                @yield('content')
            </main>
        </div>
        @stack('scripts') {{-- For page-specific additional JS --}}
    </body>
</html>
