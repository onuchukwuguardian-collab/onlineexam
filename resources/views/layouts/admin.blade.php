<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }} - Admin @yield('title')</title>

    <!-- Local Tailwind CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/tailwind.min.css') }}">
    
    <!-- Local FontAwesome -->
    <link rel="stylesheet" href="{{ asset('assets/css/fontawesome.min.css') }}">
    
    <!-- Inter Font -->
    <link rel="stylesheet" href="{{ asset('assets/css/inter-font.css') }}">
    
    <!-- Inline Critical CSS -->
    <style>
        /* Reset and Base Styles */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f3f4f6; }
        
        /* Layout */
        .admin-layout { display: flex; min-height: 100vh; }
        .admin-sidebar { 
            width: 260px; 
            background: linear-gradient(180deg, #1f2937 0%, #111827 100%);
            color: white; 
            padding: 0; 
            overflow-y: auto; 
            max-height: 100vh;
            position: fixed;
            top: 70px;
            left: 0;
            bottom: 0;
            z-index: 40;
            box-shadow: 4px 0 20px rgba(0,0,0,0.15);
            border-right: 1px solid #374151;
        }
        .admin-sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, #3b82f6, transparent);
        }
        .admin-main { flex: 1; background: #f9fafb; margin-left: 260px; min-height: calc(100vh - 70px); }
        .admin-nav { background: #1f2937; color: white; padding: 1rem; position: fixed; top: 0; left: 0; right: 0; z-index: 50; height: 70px; }
        .admin-content { padding: 2rem; padding-top: 2rem; }
        
        /* Header improvements */
        .admin-header { 
            background: linear-gradient(135deg, #3b82f6, #1d4ed8); 
            color: white; 
            padding: 1rem 1.5rem; 
            border-radius: 0.5rem; 
            margin-bottom: 1.5rem;
            margin-top: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .admin-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
            line-height: 1.2;
        }
        .admin-header p {
            font-size: 0.875rem;
            opacity: 0.9;
            margin: 0.25rem 0 0 0;
        }
        
        /* Navigation */
        .nav-brand { 
            font-size: 1.5rem; 
            font-weight: bold; 
            color: white; 
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        .nav-brand i {
            margin-right: 0.5rem;
            font-size: 1.25rem;
        }
        .nav-menu { 
            list-style: none; 
            padding: 1.5rem 0 2rem 0; 
            margin: 0;
        }
        .nav-item { margin: 0.125rem 0; }
        .nav-link { 
            display: flex; 
            align-items: center; 
            padding: 1rem 1.5rem; 
            color: #d1d5db; 
            text-decoration: none; 
            border-radius: 0.5rem; 
            margin: 0 1rem; 
            transition: all 0.3s ease;
            font-size: 0.95rem;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }
        .nav-link:hover, .nav-link.active { 
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white; 
            transform: translateX(6px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        .nav-link:hover {
            background: linear-gradient(135deg, #4f46e5, #3730a3);
        }
        .nav-link i { 
            margin-right: 1rem; 
            width: 1.5rem; 
            text-align: center;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            display: inline-block;
        }
        .nav-link:hover i, .nav-link.active i {
            transform: scale(1.1);
            color: #fbbf24;
        }
        .nav-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: #3b82f6;
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }
        .nav-link.active::before {
            transform: scaleY(1);
        }
        
        /* Cards */
        .card { background: white; border-radius: 0.5rem; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 1.5rem; }
        .card-gradient { background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; }
        .card-green { background: linear-gradient(135deg, #10b981, #059669); color: white; }
        .card-purple { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; }
        .card-yellow { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
        
        /* Grid */
        .grid { display: grid; gap: 1.5rem; }
        .grid-4 { grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); }
        .grid-3 { grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); }
        .grid-2 { grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); }
        
        /* Typography */
        .text-4xl { font-size: 2.25rem; font-weight: 800; }
        .text-2xl { font-size: 1.5rem; font-weight: 700; }
        .text-xl { font-size: 1.25rem; font-weight: 600; }
        .text-lg { font-size: 1.125rem; font-weight: 500; }
        .text-sm { font-size: 0.875rem; }
        .text-xs { font-size: 0.75rem; }
        
        /* Utilities */
        .mb-2 { margin-bottom: 0.5rem; }
        .mb-4 { margin-bottom: 1rem; }
        .mb-6 { margin-bottom: 1.5rem; }
        .mb-8 { margin-bottom: 2rem; }
        .p-4 { padding: 1rem; }
        .p-6 { padding: 1.5rem; }
        .flex { display: flex; }
        .items-center { align-items: center; }
        .justify-between { justify-content: space-between; }
        .rounded-lg { border-radius: 0.5rem; }
        .shadow { box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .opacity-75 { opacity: 0.75; }
        
        /* Buttons */
        .btn { display: inline-flex; align-items: center; padding: 0.5rem 1rem; border-radius: 0.375rem; text-decoration: none; font-weight: 500; transition: all 0.2s; }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-primary:hover { background: #2563eb; }
        
        /* Quick Actions */
        .quick-action { 
            background: white; 
            padding: 1rem; 
            border-radius: 0.5rem; 
            box-shadow: 0 1px 3px rgba(0,0,0,0.1); 
            text-decoration: none; 
            color: inherit; 
            transition: all 0.2s; 
            border-left: 4px solid;
            border: none;
            cursor: pointer;
            width: 100%;
            text-align: left;
            font-family: inherit;
            font-size: inherit;
        }
        .quick-action:hover { box-shadow: 0 4px 6px rgba(0,0,0,0.1); transform: translateY(-1px); }
        .quick-action-blue { border-left-color: #3b82f6; }
        .quick-action-green { border-left-color: #10b981; }
        .quick-action-yellow { border-left-color: #f59e0b; }
        .quick-action-red { border-left-color: #ef4444; }
        
        /* User Dropdown */
        .dropdown { position: relative; }
        .dropdown-menu { position: absolute; right: 0; top: 100%; background: #374151; border-radius: 0.375rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); min-width: 200px; z-index: 50; }
        .dropdown-item { display: block; padding: 0.75rem 1rem; color: white; text-decoration: none; }
        .dropdown-item:hover { background: #4b5563; }
        
        /* Footer */
        .footer { background: white; border-top: 1px solid #e5e7eb; padding: 1rem 2rem; margin-top: 2rem; }
        
        /* Custom Scrollbar */
        .admin-sidebar::-webkit-scrollbar { width: 6px; }
        .admin-sidebar::-webkit-scrollbar-track { background: #374151; }
        .admin-sidebar::-webkit-scrollbar-thumb { 
            background: #6b7280; 
            border-radius: 3px; 
        }
        .admin-sidebar::-webkit-scrollbar-thumb:hover { background: #9ca3af; }
        
        /* Firefox Scrollbar */
        .admin-sidebar { 
            scrollbar-width: thin; 
            scrollbar-color: #6b7280 #374151; 
        }

        /* Content spacing fixes */
        .card:first-child {
            margin-top: 0;
        }
        
        /* Ensure content is visible below fixed nav */
        .admin-main {
            padding-top: 0;
        }
        
        /* Questions page specific */
        .questions-container {
            margin-top: 1rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .admin-sidebar { 
                width: 100%; 
                height: auto; 
                position: relative; 
                top: 0;
                max-height: none;
                overflow-y: visible;
            }
            .admin-layout { flex-direction: column; }
            .admin-main { margin-left: 0; min-height: auto; }
            .admin-content { padding: 1rem; padding-top: 1rem; }
            .grid-4, .grid-3, .grid-2 { grid-template-columns: 1fr; }
            .admin-header { margin-top: 0.5rem; }
        }
    </style>

    <!-- Admin Form Styles -->
    <style>
        /* Admin Form Components */
        .admin-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            margin: 2rem auto;
        }
        
        .admin-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.25rem;
        }
        
        .admin-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            background: white;
            transition: all 0.2s ease;
        }
        
        .admin-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .admin-input:disabled {
            background: #f3f4f6;
            color: #6b7280;
            cursor: not-allowed;
        }
        
        .admin-btn-primary {
            background: #3b82f6;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.375rem;
            border: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .admin-btn-primary:hover {
            background: #2563eb;
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .admin-btn-secondary {
            background: #6b7280;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.375rem;
            border: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .admin-btn-secondary:hover {
            background: #4b5563;
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(107, 114, 128, 0.3);
        }
        
        /* Form Layout Utilities */
        .space-y-6 > * + * {
            margin-top: 1.5rem;
        }
        
        .space-x-3 > * + * {
            margin-left: 0.75rem;
        }
        
        .max-w-2xl {
            max-width: 42rem;
        }
        
        .max-w-lg {
            max-width: 32rem;
        }
        
        .mx-auto {
            margin-left: auto;
            margin-right: auto;
        }
        
        .block {
            display: block;
        }
        
        .w-full {
            width: 100%;
        }
        
        .mt-1 {
            margin-top: 0.25rem;
        }
        
        .pt-4 {
            padding-top: 1rem;
        }
        
        .flex {
            display: flex;
        }
        
        .justify-end {
            justify-content: flex-end;
        }
        
        .hidden {
            display: none;
        }
        
        .text-red-500 {
            color: #ef4444;
        }
        
        .text-sm {
            font-size: 0.875rem;
        }
        
        .border-red-500 {
            border-color: #ef4444;
        }
        
        .bg-gray-200 {
            background-color: #e5e7eb;
        }
        
        .cursor-not-allowed {
            cursor: not-allowed;
        }
        
        .text-xs {
            font-size: 0.75rem;
        }
        
        .text-gray-500 {
            color: #6b7280;
        }
        
        .font-bold {
            font-weight: 700;
        }
        
        .pl-2 {
            padding-left: 0.5rem;
        }
        
        .text-2xl {
            font-size: 1.5rem;
            line-height: 2rem;
        }
        
        .text-white {
            color: white;
        }
        
        /* Dark mode support */
        .dark .text-gray-300 {
            color: #d1d5db;
        }
        
        .dark .text-gray-400 {
            color: #9ca3af;
        }
        
        .dark .bg-gray-600 {
            background-color: #4b5563;
        }
        
        .dark .text-gray-700 {
            color: #374151;
        }
        
        /* Enhanced Action Buttons */
        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
            border: none;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            min-width: 2.5rem;
            justify-content: center;
        }
        
        .action-btn:hover {
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        /* Edit Button */
        .edit-btn, .btn-edit-modern {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border: 1px solid #2563eb;
        }
        
        .edit-btn:hover, .btn-edit-modern:hover {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        /* Delete Button */
        .delete-btn, .btn-delete-modern {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: 1px solid #dc2626;
        }
        
        .delete-btn:hover, .btn-delete-modern:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: white;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }
        
        /* View Button */
        .view-btn, .btn-view-modern {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: 1px solid #059669;
        }
        
        .view-btn:hover, .btn-view-modern:hover {
            background: linear-gradient(135deg, #059669, #047857);
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        /* Warning Button */
        .warning-btn, .btn-warning-modern {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            border: 1px solid #d97706;
        }
        
        .warning-btn:hover, .btn-warning-modern:hover {
            background: linear-gradient(135deg, #d97706, #b45309);
            color: white;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }
        
        /* Info Button */
        .info-btn, .btn-info-modern {
            background: linear-gradient(135deg, #06b6d4, #0891b2);
            color: white;
            border: 1px solid #0891b2;
        }
        
        .info-btn:hover, .btn-info-modern:hover {
            background: linear-gradient(135deg, #0891b2, #0e7490);
            color: white;
            box-shadow: 0 4px 12px rgba(6, 182, 212, 0.3);
        }
        
        /* Button Groups */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            justify-content: center;
        }
        
        .btn-group {
            display: flex;
            gap: 0.25rem;
        }
        
        .btn-group .action-btn {
            border-radius: 0;
        }
        
        .btn-group .action-btn:first-child {
            border-radius: 0.375rem 0 0 0.375rem;
        }
        
        .btn-group .action-btn:last-child {
            border-radius: 0 0.375rem 0.375rem 0;
        }
        
        /* Small Action Buttons */
        .action-btn-sm {
            padding: 0.375rem 0.5rem;
            font-size: 0.75rem;
            min-width: 2rem;
        }
        
        /* Large Action Buttons */
        .action-btn-lg {
            padding: 0.75rem 1rem;
            font-size: 1rem;
            min-width: 3rem;
        }
        
        /* Icon-only buttons */
        .action-btn-icon {
            padding: 0.5rem;
            min-width: 2.5rem;
            width: 2.5rem;
            height: 2.5rem;
        }
        
        /* Disabled state */
        .action-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .action-btn:disabled:hover {
            transform: none;
            box-shadow: none;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .admin-card {
                margin: 1rem;
                padding: 1.5rem;
            }
            
            .space-x-3 {
                flex-direction: column;
            }
            
            .space-x-3 > * + * {
                margin-left: 0;
                margin-top: 0.75rem;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 0.25rem;
            }
            
            .action-btn {
                width: 100%;
                justify-content: center;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .btn-group .action-btn {
                border-radius: 0.375rem;
            }
        }
    </style>

    @stack('styles')
    <style>
        .dropdownlist { transition: opacity 0.2s ease-in-out, visibility 0.2s ease-in-out; }
        .dropdownlist.invisible { visibility: hidden; opacity: 0; }
        .dropdownlist.visible { visibility: visible; opacity: 1; }
    </style>
</head>
<body>
    {{-- Top Nav --}}
    <nav class="admin-nav">
        {{-- ... Your admin top nav content (logo, user dropdown, etc.) ... --}}
        {{-- Make sure links use route() helper and logout uses a form --}}
        <div class="flex items-center justify-between">
            <a href="{{ route('admin.dashboard') }}" class="nav-brand">
                <i class="fas fa-shield-alt"></i> Admin Portal
            </a>
            @auth
            <div class="dropdown">
                <button onclick="toggleDD('userDropdown')" class="flex items-center text-white">
                    <i class="fas fa-user-circle"></i> Hi, {{ Auth::user()->name }}
                    <i class="fas fa-chevron-down ml-2"></i>
                </button>
                <div id="userDropdown" class="dropdown-menu" style="display: none;">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <a href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();" class="dropdown-item">
                            <i class="fas fa-sign-out-alt"></i> Log Out
                        </a>
                    </form>
                </div>
            </div>
            @endauth
        </div>
    </nav>

    <div class="admin-layout">
        <aside class="admin-sidebar">
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <i class="fas fa-user-graduate"></i> Students & Users
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.classes.index') }}" class="nav-link {{ request()->routeIs('admin.classes.*') ? 'active' : '' }}">
                        <i class="fas fa-chalkboard-teacher"></i> Classes
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.subjects.index') }}" class="nav-link {{ request()->routeIs('admin.subjects.*') || request()->routeIs('admin.questions.*') ? 'active' : '' }}">
                        <i class="fas fa-book-open"></i> Subjects & Questions
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.scoreboard.index') }}" class="nav-link {{ request()->routeIs('admin.scoreboard.*') ? 'active' : '' }}">
                        <i class="fas fa-trophy"></i> Scoreboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.exam.reset.index') }}" class="nav-link {{ request()->routeIs('admin.exam.reset.*') ? 'active' : '' }}">
                        <i class="fas fa-redo-alt"></i> Exam Reset
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.system.reset.index') }}" class="nav-link {{ request()->routeIs('admin.system.*') ? 'active' : '' }}">
                        <i class="fas fa-server"></i> System Management
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.security.index') }}" class="nav-link {{ request()->routeIs('admin.security.*') ? 'active' : '' }}">
                        <i class="fas fa-shield-alt"></i> Security Violations
                    </a>
                </li>
            </ul>
        </aside>

        <main class="admin-main">
            <div class="admin-content">
                @if (View::hasSection('headerContent') || isset($headerContent))
                    <div class="admin-header">
                        @yield('headerContent', $headerContent ?? '')
                    </div>
                @endif
                {{-- Flash Messages --}}
                @if (session('success'))
                    <div id="flash-success" class="card max-w-full" style="background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; word-wrap: break-word; overflow-wrap: break-word;">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-lg"></i>
                            </div>
                            <div class="ml-3 flex-1 min-w-0">
                                <span class="break-words">{{ session('success') }}</span>
                            </div>
                            <div class="ml-3 flex-shrink-0">
                                <button onclick="document.getElementById('flash-success').remove()" class="text-green-600 hover:text-green-800 focus:outline-none">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
                @if (session('error'))
                    <div id="flash-error" class="card max-w-full" style="background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; word-wrap: break-word; overflow-wrap: break-word;">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-lg"></i>
                            </div>
                            <div class="ml-3 flex-1 min-w-0">
                                <span class="break-words">{{ session('error') }}</span>
                            </div>
                            <div class="ml-3 flex-shrink-0">
                                <button onclick="document.getElementById('flash-error').remove()" class="text-red-600 hover:text-red-800 focus:outline-none">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
                @if (session('info'))
                    <div id="flash-info" class="card max-w-full" style="background: #dbeafe; color: #1e40af; border-left: 4px solid #3b82f6; word-wrap: break-word; overflow-wrap: break-word;">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle text-lg"></i>
                            </div>
                            <div class="ml-3 flex-1 min-w-0">
                                <span class="break-words">{{ session('info') }}</span>
                            </div>
                            <div class="ml-3 flex-shrink-0">
                                <button onclick="document.getElementById('flash-info').remove()" class="text-blue-600 hover:text-blue-800 focus:outline-none">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
                
                @yield('content')
                
                {{-- Footer --}}
                <footer class="footer">
                    <div class="flex justify-between items-center">
                        <span>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</span>
                        <span><i class="fas fa-shield-alt" style="color: #10b981;"></i> Secured & Protected</span>
                    </div>
                </footer>
            </div>
        </main>
    </div>

    <script>
        function toggleDD(id) {
            const dropdown = document.getElementById(id);
            if (dropdown) {
                dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
            }
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.style.display = 'none';
                });
            }
        });
    </script>
    <script src="{{ asset('assets/js/chart.min.js') }}"></script> {{-- Chart.js Local --}}
    @stack('scripts')
</body>
</html>
