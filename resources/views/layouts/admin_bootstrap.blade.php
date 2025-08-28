<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }} - Admin @yield('title')</title>

    <!-- Bootstrap 4 CSS (Local) -->
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    
    <!-- FontAwesome (Local) -->
    <link rel="stylesheet" href="{{ asset('assets/css/fontawesome.min.css') }}">
    
    <!-- DataTables Bootstrap 4 CSS (Local) -->
    <link rel="stylesheet" href="{{ asset('assets/css/dataTables.bootstrap4.min.css') }}">
    
    <!-- Custom Admin Styles -->
    <style>
        body {
            background-color: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        
        .sidebar {
            background: linear-gradient(180deg, #343a40 0%, #212529 100%);
            min-height: 100vh;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        
        .sidebar .nav-link {
            color: #adb5bd;
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            margin: 0.125rem 0.5rem;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: #007bff;
            color: white;
            transform: translateX(5px);
        }
        
        .sidebar .nav-link i {
            width: 20px;
            margin-right: 0.5rem;
        }
        
        .main-content {
            padding: 2rem;
        }
        
        .page-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .card {
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 0.5rem;
        }
        
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            font-weight: 600;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #007bff, #0056b3);
            border: none;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #0056b3, #004085);
            transform: translateY(-1px);
        }
        
        .table th {
            background-color: #f8f9fa;
            border-top: none;
            font-weight: 600;
            color: #495057;
        }
        
        .navbar-brand {
            font-weight: bold;
            font-size: 1.25rem;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .alert {
            border: none;
            border-radius: 0.5rem;
        }
        
        .badge {
            font-size: 0.75em;
        }
        
        /* Sidebar Styling */
        .sidebar {
            background: linear-gradient(180deg, #343a40 0%, #495057 100%);
            min-height: 100vh;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        
        .sidebar .nav-link {
            color: #adb5bd;
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            margin: 0.125rem 0.5rem;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
        }
        
        .sidebar .nav-link:hover {
            color: #fff;
            background-color: rgba(255,255,255,0.1);
            transform: translateX(2px);
        }
        
        .sidebar .nav-link.active {
            color: #fff;
            background: linear-gradient(135deg, #007bff, #0056b3);
            box-shadow: 0 2px 4px rgba(0,123,255,0.3);
        }
        
        .sidebar .nav-link i {
            width: 20px;
            margin-right: 0.75rem;
            text-align: center;
            font-size: 1rem;
        }
        
        .sidebar .nav-link.active i {
            color: #fff;
        }
        
        /* DataTables Styling */
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            padding: 0.375rem 0.75rem;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #007bff !important;
            border-color: #007bff !important;
            color: white !important;
        }
        
        .dataTables_wrapper .dataTables_info {
            padding-top: 0.75rem;
        }
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            padding: 0.375rem 0.75rem;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #007bff !important;
            border-color: #007bff !important;
            color: white !important;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: -250px;
                width: 250px;
                height: 100vh;
                z-index: 1050;
                transition: left 0.3s ease;
            }
            
            .sidebar.show {
                left: 0;
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('admin.dashboard') }}">
                <i class="fas fa-shield-alt"></i> Admin Portal
            </a>
            
            <button class="navbar-toggler d-lg-none" type="button" data-toggle="collapse" data-target="#sidebarMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="navbar-nav ml-auto">
                @auth
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown">
                        <i class="fas fa-user-circle"></i> {{ Auth::user()->name }}
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <a class="dropdown-item" href="{{ route('logout') }}" 
                               onclick="event.preventDefault(); this.closest('form').submit();">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </form>
                    </div>
                </div>
                @endauth
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="sidebar-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" 
                               href="{{ route('admin.dashboard') }}">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" 
                               href="{{ route('admin.users.index') }}">
                                <i class="fas fa-users"></i> Students & Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.classes.*') ? 'active' : '' }}" 
                               href="{{ route('admin.classes.index') }}">
                                <i class="fas fa-chalkboard-teacher"></i> Classes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.subjects.*') || request()->routeIs('admin.questions.*') ? 'active' : '' }}" 
                               href="{{ route('admin.subjects.index') }}">
                                <i class="fas fa-book-open"></i> Subjects & Questions
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.scoreboard.*') ? 'active' : '' }}" 
                               href="{{ route('admin.scoreboard.index') }}">
                                <i class="fas fa-trophy"></i> Scoreboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.exam.reset.*') ? 'active' : '' }}" 
                               href="{{ route('admin.exam.reset.index') }}">
                                <i class="fas fa-redo-alt"></i> Exam Reset
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.system.*') ? 'active' : '' }}" 
                               href="{{ route('admin.system.reset.index') }}">
                                <i class="fas fa-server"></i> System Management
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.security.*') ? 'active' : '' }}" 
                               href="{{ route('admin.security.index') }}">
                                <i class="fas fa-shield-alt"></i> Security
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 main-content">
                @if (View::hasSection('headerContent') || isset($headerContent))
                    <div class="page-header">
                        @yield('headerContent', $headerContent ?? '')
                    </div>
                @endif
                
                <!-- Flash Messages -->
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                @endif
                
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                @endif
                
                @if (session('info'))
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="fas fa-info-circle"></i> {{ session('info') }}
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                @endif
                
                @yield('content')
                
                <!-- Footer -->
                <footer class="mt-5 py-4 border-top">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="text-muted">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                        </div>
                        <div class="col-md-6 text-right">
                            <p class="text-muted">
                                <i class="fas fa-shield-alt text-success"></i> Secured & Protected
                            </p>
                        </div>
                    </div>
                </footer>
            </main>
        </div>
    </div>

    <!-- Bootstrap 4 JS (Local) -->
    <script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
    
    <!-- Chart.js (Local) -->
    <script src="{{ asset('assets/js/chart.min.js') }}"></script>
    
    @stack('scripts')
</body>
</html>