<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Online Exam Portal')) - Student Portal</title>

    <!-- Local Font Awesome -->
    <link rel="stylesheet" href="{{ asset('assets/css/fontawesome.min.css') }}">
    
    <!-- Inter Font CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/inter-font.css') }}">

    <!-- Local Tailwind CSS Build -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Professional Student Portal Styling -->
    <style>
        /* Complete CSS Reset */
        *, *::before, *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        /* Remove default browser spacing */
        html, body, div, span, applet, object, iframe,
        h1, h2, h3, h4, h5, h6, p, blockquote, pre,
        a, abbr, acronym, address, big, cite, code,
        del, dfn, em, img, ins, kbd, q, s, samp,
        small, strike, strong, sub, sup, tt, var,
        b, u, i, center,
        dl, dt, dd, ol, ul, li,
        fieldset, form, label, legend,
        table, caption, tbody, tfoot, thead, tr, th, td,
        article, aside, canvas, details, embed, 
        figure, figcaption, footer, header, hgroup, 
        menu, nav, output, ruby, section, summary,
        time, mark, audio, video {
            margin: 0;
            padding: 0;
            border: 0;
            font-size: 100%;
            font: inherit;
            vertical-align: baseline;
        }
        
        /* Base Styles with Zero Spacing */
        html {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            color: #1e293b;
            line-height: 1.6;
            height: 100%;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        
        .app-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0;
            padding: 0;
            position: relative;
        }
        
        .main-wrapper {
            flex: 1;
            margin: 0;
            padding: 0;
            position: relative;
        }
        
        /* Navigation - Fixed positioning */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(148, 163, 184, 0.2);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            margin: 0;
            padding: 0;
            height: 72px;
        }
        
        /* Hide any elements that might be causing spacing */
        .navbar + * {
            margin-top: 0;
        }
        
        /* Adjust main content to account for fixed navbar */
        .main-wrapper {
            margin-top: 72px;
        }
        
        /* Force remove any hidden responsive elements */
        .hidden {
            display: none !important;
        }
        
        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 72px;
        }
        
        .nav-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: #4f46e5;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .nav-brand i {
            font-size: 1.75rem;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .nav-links {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .nav-link {
            color: #64748b;
            text-decoration: none;
            padding: 0.75rem 1.25rem;
            border-radius: 0.75rem;
            transition: all 0.3s ease;
            font-weight: 500;
            position: relative;
        }
        
        .nav-link:hover, .nav-link.active {
            color: #4f46e5;
            background: rgba(79, 70, 229, 0.1);
            transform: translateY(-1px);
        }
        
        /* Exam Info Navbar */
        .exam-info-navbar {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            font-size: 0.875rem;
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.1), rgba(124, 58, 237, 0.1));
            padding: 0.75rem 1.5rem;
            border-radius: 1rem;
            border: 1px solid rgba(79, 70, 229, 0.2);
            backdrop-filter: blur(10px);
        }
        
        .exam-subject, .exam-progress {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            color: #1e293b;
            background: rgba(255, 255, 255, 0.9);
            padding: 0.5rem 1rem;
            border-radius: 0.75rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .exam-timer {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: white;
            padding: 0.75rem 1.25rem;
            border-radius: 0.75rem;
            font-weight: 700;
            font-family: 'JetBrains Mono', 'Courier New', monospace;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4);
            transition: all 0.3s ease;
        }
        
        .exam-timer.timer-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            animation: pulse 2s infinite;
        }
        
        .exam-timer.timer-critical {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            animation: pulse 1s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.8; transform: scale(1.02); }
        }
        
        /* User Dropdown */
        .user-dropdown {
            position: relative;
        }
        
        .dropdown-btn {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.25rem;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .dropdown-btn:hover {
            background: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-1px);
        }
        
        .dropdown-menu {
            position: absolute;
            right: 0;
            top: calc(100% + 0.5rem);
            background: white;
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 1rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            min-width: 220px;
            z-index: 1001;
            display: none;
            overflow: hidden;
        }
        
        .dropdown-menu.show {
            display: block;
            animation: fadeInUp 0.2s ease;
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
            color: #374151;
            text-decoration: none;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
            transition: all 0.2s ease;
        }
        
        .dropdown-item:hover {
            background: #f8fafc;
            color: #4f46e5;
        }
        
        .dropdown-item:last-child {
            border-bottom: none;
        }
        
        /* Main Content */
        .main-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem 2rem 2rem;
        }
        
        /* Professional Header */
        .page-title-card {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #ec4899 100%);
            color: white;
            padding: 2.5rem;
            border-radius: 1.5rem;
            margin: 0 0 2rem 0;
            box-shadow: 0 10px 40px rgba(79, 70, 229, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .page-title-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            pointer-events: none;
        }
        
        /* Cards */
        .card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 1.25rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 2rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }
        
        /* Gradient Cards */
        .card-gradient {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            border: none;
        }
        
        .card-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
        }
        
        .card-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            border: none;
        }
        
        .card-info {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border: none;
        }
        
        .card-error {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: none;
        }
        
        /* Grid System */
        .grid {
            display: grid;
            gap: 2rem;
        }
        
        .grid-1 { grid-template-columns: 1fr; }
        .grid-2 { grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); }
        .grid-3 { grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); }
        .grid-4 { grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); }
        
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.875rem 1.75rem;
            border-radius: 0.75rem;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(79, 70, 229, 0.5);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.5);
        }
        
        .btn-disabled {
            background: #94a3b8;
            color: white;
            cursor: not-allowed;
            box-shadow: none;
        }
        
        .btn-disabled:hover {
            transform: none;
            box-shadow: none;
        }
        
        .btn-disabled::before {
            display: none;
        }
        
        /* Subject Cards */
        .subject-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 1.25rem;
            padding: 2rem;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }
        
        .subject-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #10b981, #059669);
            transition: width 0.3s ease;
        }
        
        .subject-card.completed::before {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        
        .subject-card.pending::before {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }
        
        .subject-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }
        
        .subject-card:hover::before {
            width: 8px;
        }
        
        /* Status Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-success {
            background: rgba(16, 185, 129, 0.1);
            color: #065f46;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        
        .badge-warning {
            background: rgba(245, 158, 11, 0.1);
            color: #92400e;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        
        /* Typography */
        .text-3xl { font-size: 2rem; font-weight: 800; line-height: 1.2; }
        .text-2xl { font-size: 1.75rem; font-weight: 700; line-height: 1.3; }
        .text-xl { font-size: 1.375rem; font-weight: 600; line-height: 1.4; }
        .text-lg { font-size: 1.125rem; font-weight: 500; line-height: 1.5; }
        .text-sm { font-size: 0.875rem; line-height: 1.5; }
        .text-xs { font-size: 0.75rem; line-height: 1.4; }
        
        /* Utilities */
        .mb-2 { margin-bottom: 0.5rem; }
        .mb-4 { margin-bottom: 1rem; }
        .mb-6 { margin-bottom: 1.5rem; }
        .mb-8 { margin-bottom: 2rem; }
        .mt-2 { margin-top: 0.5rem; }
        .mt-4 { margin-top: 1rem; }
        .flex { display: flex; }
        .items-center { align-items: center; }
        .justify-between { justify-content: space-between; }
        .justify-center { justify-content: center; }
        .text-center { text-align: center; }
        .w-full { width: 100%; }
        .opacity-75 { opacity: 0.75; }
        .opacity-90 { opacity: 0.9; }
        
        /* Footer */
        .footer {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-top: 1px solid rgba(148, 163, 184, 0.2);
            padding: 2rem;
            text-align: center;
            color: #64748b;
            font-size: 0.875rem;
            margin-top: auto;
        }
        
        /* Responsive Design */
        /* Responsive Design */
        @media (max-width: 1024px) {
            .nav-container { padding: 0 1.5rem; }
            .main-content { padding: 0 1.5rem 1.5rem 1.5rem; }
            .header-content { padding: 0 1.5rem; }
            .grid-2, .grid-3, .grid-4 { grid-template-columns: 1fr; }
        }
        
        @media (max-width: 768px) {
            .navbar { height: 64px; }
            .main-wrapper { margin-top: 64px; }
            .nav-container { 
                padding: 0 1rem; 
                height: 64px;
            }
            .nav-brand { font-size: 1.25rem; }
            .main-content { padding: 0 1rem 1rem 1rem; }
            .header-content { padding: 0 1rem; }
            .dashboard-header { padding: 1.5rem 0; }
            .card { padding: 1.5rem; }
            .stat-card { padding: 1.5rem; }
            .exam-card { padding: 1.5rem; }
            
            .exam-info-navbar {
                gap: 0.75rem;
                font-size: 0.75rem;
                padding: 0.5rem 1rem;
            }
            
            .exam-subject, .exam-progress {
                padding: 0.375rem 0.75rem;
                font-size: 0.75rem;
            }
            
            .exam-timer {
                padding: 0.5rem 0.875rem;
                font-size: 0.75rem;
            }
        }
        
        /* Mobile Menu */
        .mobile-menu { 
            display: none; 
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-top: 1px solid rgba(148, 163, 184, 0.2);
            padding: 1rem;
            z-index: 999;
        }
        
        .mobile-menu.show { 
            display: block;
        }
        
        .mobile-toggle { 
            display: none;
            background: none;
            border: none;
            font-size: 1.25rem;
            color: #64748b;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }
        
        .mobile-toggle:hover {
            background: rgba(148, 163, 184, 0.1);
            color: #4f46e5;
        }
        
        @media (max-width: 640px) {
            .nav-links { display: none; }
            .mobile-toggle { display: block; }
        }
        
        /* Hide any Alpine.js or responsive elements that might be showing */
        [x-cloak] { display: none !important; }
        .sm\\:hidden { display: none !important; }
        .border-b { display: none !important; }
        .pt-2, .pb-3, .pt-4, .pb-1 { display: none !important; }
    </style>

    @stack('styles')
    
    <!-- Modal Styles -->
    <style>
        /* Modal Overlay */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .modal-overlay.modal-active {
            opacity: 1;
        }
        
        .modal-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            max-width: 90vw;
            max-height: 90vh;
            width: 800px;
            overflow: hidden;
            transform: scale(0.9);
            transition: transform 0.3s ease;
        }
        
        .modal-overlay.modal-active .modal-container {
            transform: scale(1);
        }
        
        .modal-content-scores {
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .modal-close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 1.25rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 6px;
            transition: background-color 0.2s;
        }
        
        .modal-close-btn:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .modal-body {
            padding: 1.5rem;
            overflow-y: auto;
            flex: 1;
            max-height: 60vh;
        }
        
        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            background-color: #f9fafb;
        }
        
        /* Scores Table Styles */
        .scores-table-container {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        
        .scores-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }
        
        .scores-table th {
            background-color: #f9fafb;
            padding: 0.75rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .scores-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .scores-table tbody tr:hover {
            background-color: #f9fafb;
        }
        
        .subject-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .subject-icon {
            color: #6366f1;
        }
        
        .score-number {
            font-weight: 600;
            color: #1f2937;
        }
        
        .performance-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .percentage-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .percentage-pass {
            background-color: #dcfce7;
            color: #166534;
        }
        
        .percentage-fail {
            background-color: #fef2f2;
            color: #dc2626;
        }
        
        .percentage-summary {
            background-color: #dbeafe;
            color: #1e40af;
        }
        
        .summary-row {
            background-color: #f3f4f6;
            font-weight: 600;
        }
        
        .summary-row td {
            border-top: 2px solid #e5e7eb;
        }
        
        /* Empty State */
        .empty-scores {
            text-align: center;
            padding: 3rem 1rem;
        }
        
        .empty-icon {
            font-size: 3rem;
            color: #9ca3af;
            margin-bottom: 1rem;
        }
        
        .empty-scores h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .empty-scores p {
            color: #6b7280;
        }
        
        /* Loading Spinner */
        .loading-spinner {
            color: #6b7280;
        }
        
        .loading-spinner i {
            color: #6366f1;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .modal-container {
                width: 95vw;
                max-height: 85vh;
            }
            
            .modal-header {
                padding: 1rem;
            }
            
            .modal-body {
                padding: 1rem;
            }
            
            .scores-table {
                font-size: 0.75rem;
            }
            
            .scores-table th,
            .scores-table td {
                padding: 0.5rem;
            }
        }
    </style>

    <style>
        /* Enhanced Global Styles */
        body { 
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        /* Dashboard Header */
        .dashboard-header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #ec4899 100%);
            color: white;
            padding: 2rem 0;
            margin: 0;
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="dots" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23dots)"/></svg>');
            pointer-events: none;
        }
        
        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 1;
        }
        
        .welcome-section {
            flex: 1;
        }
        
        .dashboard-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.75rem;
            background: linear-gradient(135deg, #ffffff, #e2e8f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .dashboard-subtitle {
            font-size: 1.125rem;
            opacity: 0.9;
            font-weight: 400;
        }
        
        .class-info {
            flex-shrink: 0;
        }
        
        .class-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 1rem;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            min-width: 200px;
        }
        
        .class-icon {
            font-size: 2rem;
            opacity: 0.8;
        }
        
        .class-details {
            display: flex;
            flex-direction: column;
        }
        
        .class-label {
            font-size: 0.875rem;
            opacity: 0.8;
            margin-bottom: 0.25rem;
        }
        
        .class-name {
            font-size: 1.25rem;
            font-weight: 700;
        }
        
        /* Component Spacing */
        .nav-container { padding: 0 2rem; height: 72px; }
        .header-content { padding: 0 2rem; }
        .stats-section { margin: 1rem 0 2rem 0; }
        .card { padding: 1.5rem; margin-bottom: 1rem; }
        .stat-card { padding: 2rem; }
        .exam-card { padding: 2rem; }
        .btn { padding: 0.875rem 1.75rem; }
        .badge { padding: 0.5rem 1rem; }
        .alert { padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; }
        .limit-status { padding: 1.5rem; margin-bottom: 2rem; }
        .empty-state { padding: 4rem 2rem; }
        .score-display { padding: 1rem; margin-bottom: 1rem; }
        
        .section-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 1.5rem;
            position: relative;
            padding-left: 1rem;
        }
        
        .section-title::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            border-radius: 2px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 1.25rem;
            padding: 2rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            transition: height 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }
        
        .stat-card:hover::before {
            height: 6px;
        }
        
        .stat-card.stat-success::before {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        
        .stat-card.stat-warning::before {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }
        
        .stat-card.stat-info::before {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            flex-shrink: 0;
        }
        
        .stat-success .stat-icon {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        
        .stat-warning .stat-icon {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }
        
        .stat-info .stat-icon {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
        }
        
        .stat-content {
            flex: 1;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: #1e293b;
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: #64748b;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Alert Messages */
        .alert {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.25rem 1.5rem;
            border-radius: 1rem;
            margin-bottom: 1.5rem;
            border: 1px solid;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.3);
            color: #991b1b;
        }
        
        .alert-info {
            background: rgba(59, 130, 246, 0.1);
            border-color: rgba(59, 130, 246, 0.3);
            color: #1e40af;
        }
        
        .alert i {
            font-size: 1.25rem;
            flex-shrink: 0;
        }
        
        .alert-content {
            flex: 1;
        }
        
        .alert-content strong {
            display: block;
            margin-bottom: 0.25rem;
            font-weight: 600;
        }
        
        /* Limit Status */
        .limit-status {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            padding: 1.5rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
            border: 1px solid;
            transition: all 0.3s ease;
        }
        
        .limit-status.limit-ok {
            background: rgba(16, 185, 129, 0.1);
            border-color: rgba(16, 185, 129, 0.3);
            color: #065f46;
        }
        
        .limit-status.limit-reached {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.3);
            color: #991b1b;
        }
        
        .limit-icon {
            width: 50px;
            height: 50px;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
            flex-shrink: 0;
        }
        
        .limit-ok .limit-icon {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        
        .limit-reached .limit-icon {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }
        
        .limit-content {
            flex: 1;
        }
        
        .limit-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .limit-details {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 0.5rem;
        }
        
        .limit-item {
            font-size: 0.875rem;
            opacity: 0.8;
        }
        
        .limit-warning {
            font-size: 0.875rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }
        
        /* Exams Section */
        .exams-section {
            margin-bottom: 3rem;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .btn-secondary {
            background: rgba(148, 163, 184, 0.1);
            color: #475569;
            border: 1px solid rgba(148, 163, 184, 0.3);
        }
        
        .btn-secondary:hover {
            background: rgba(148, 163, 184, 0.2);
            color: #334155;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 1.25rem;
        }
        
        .empty-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            border-radius: 50%;
            background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #64748b;
        }
        
        .empty-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.75rem;
        }
        
        .empty-description {
            color: #64748b;
            font-size: 1rem;
            max-width: 400px;
            margin: 0 auto;
        }
        
        /* Exams Grid */
        .exams-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
        }
        
        /* Exam Cards */
        .exam-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 1.25rem;
            padding: 2rem;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }
        
        .exam-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            transition: width 0.3s ease;
        }
        
        .exam-card.exam-completed::before {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        
        .exam-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 50px rgba(0, 0, 0, 0.15);
        }
        
        .exam-card:hover::before {
            width: 8px;
        }
        
        .exam-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }
        
        .exam-title h3 {
            font-size: 1.375rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-completed {
            background: rgba(16, 185, 129, 0.1);
            color: #065f46;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        
        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: #92400e;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        
        .exam-meta {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-bottom: 2rem;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #64748b;
            font-size: 0.875rem;
        }
        
        .meta-item i {
            width: 16px;
            text-align: center;
            color: #94a3b8;
        }
        
        .reset-info {
            color: #f59e0b;
        }
        
        .reset-info i {
            color: #f59e0b;
        }
        
        .exam-actions {
            margin-top: auto;
        }
        
        .score-display {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 0.75rem;
            margin-bottom: 1rem;
        }
        
        .score-label {
            font-size: 0.875rem;
            color: #065f46;
            font-weight: 500;
        }
        
        .score-value {
            font-size: 1.5rem;
            font-weight: 800;
            color: #065f46;
        }
        
        .btn-full {
            width: 100%;
            justify-content: center;
        }
        
        /* Modal Styling */
        .modal-active { 
            display: flex !important;
            align-items: center;
            justify-content: center;
        }
        
        .modal-inactive { 
            display: none !important;
        }
        
        /* Enhanced Modal Styles */
        .modal-content {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 90vw;
            max-height: 90vh;
            overflow: hidden;
            position: relative;
        }
        
        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        
        .modal-body {
            padding: 1.5rem;
            overflow-y: auto;
            max-height: 60vh;
        }
        
        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }
        
        /* Scores Table Styling */
        .scores-table-container {
            overflow-x: auto;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
        }
        
        .scores-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }
        
        .scores-table th {
            background: #f8fafc;
            padding: 1rem 0.75rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .scores-table td {
            padding: 0.875rem 0.75rem;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .scores-table tbody tr:hover {
            background: #f8fafc;
        }
        
        .subject-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .subject-icon {
            color: #4f46e5;
        }
        
        .score-number {
            font-weight: 700;
            color: #059669;
        }
        
        .percentage-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-weight: 600;
            font-size: 0.75rem;
        }
        
        .percentage-pass {
            background: #d1fae5;
            color: #065f46;
        }
        
        .percentage-fail {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .percentage-summary {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .summary-row {
            background: #f8fafc;
            font-weight: 600;
        }
        
        .summary-row td {
            border-top: 2px solid #e2e8f0;
            padding: 1rem 0.75rem;
        }
        
        /* Responsive Design */
        @media (max-width: 1024px) {
            .header-content {
                flex-direction: column;
                gap: 2rem;
                text-align: center;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
            
            .exams-grid {
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-header {
                padding: 2rem 0;
            }
            
            .dashboard-title {
                font-size: 2rem;
            }
            
            .header-content {
                padding: 0 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .exams-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .section-header {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
            
            .limit-status {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
            
            .limit-details {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Navigation -->
        <nav class="navbar">
            <div class="nav-container">
                <a href="{{ (Auth::check() && Auth::user()->isStudent()) ? route('user.dashboard') : route('home') }}" class="nav-brand">
                    <i class="fas fa-graduation-cap"></i>
                    MyOnlineExam
                </a>

                <!-- Exam Info Section (only shown on exam pages) -->
                @if(request()->routeIs('user.exam.show'))
                    <div class="exam-info-navbar" id="exam-info-navbar">
                        <div class="exam-subject">
                            <i class="fas fa-book"></i>
                            <span id="navbar-subject-name">Exam</span>
                        </div>
                        <div class="exam-progress">
                            <i class="fas fa-list-ol"></i>
                            Q <span id="navbar-current-q">1</span>/<span id="navbar-total-q">20</span>
                        </div>
                        <div class="exam-progress">
                            <i class="fas fa-check-circle"></i>
                            Answered: <span id="navbar-answered">0</span>
                        </div>
                        <div class="exam-timer">
                            <i class="fas fa-clock"></i>
                            <span id="navbar-timer">30:00</span>
                        </div>
                    </div>
                @else
                    <div class="nav-links">
                        @auth
                            @if(Auth::user()->isStudent())
                            <a href="{{ route('user.dashboard') }}" class="nav-link {{ request()->routeIs('user.dashboard') ? 'active' : '' }}">
                                Dashboard
                            </a>
                            <button type="button" id="myScoresModalTriggerLayout" class="nav-link" style="background: none; border: none;">
                                <i class="fas fa-clipboard-list"></i> My Scores
                            </button>
                            @endif
                        @endauth
                    </div>
                @endif

                @auth
                    <div class="user-dropdown">
                        <button class="dropdown-btn" onclick="toggleDropdown()">
                            <i class="fas fa-user-circle"></i>
                            <span>{{ Auth::user()->name }}</span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu" id="userDropdown">
                            @if(Auth::user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="dropdown-item">
                                <i class="fas fa-shield-alt"></i> Admin Portal
                            </a>
                            @endif
                            @if(Auth::user()->isStudent())
                            <a href="{{ route('user.dashboard') }}" class="dropdown-item">
                                <i class="fas fa-graduation-cap"></i> Student Dashboard
                            </a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <a href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();" class="dropdown-item">
                                    <i class="fas fa-sign-out-alt"></i> Log Out
                                </a>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="nav-link">
                        <i class="fas fa-sign-in-alt"></i> Log in
                    </a>
                @endauth

                <button class="mobile-toggle" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars"></i>
                </button>
            </div>

            <div class="mobile-menu" id="mobileMenu">
                @auth
                    @if(Auth::user()->isStudent())
                    <a href="{{ route('user.dashboard') }}" class="nav-link">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <button type="button" id="myScoresModalTriggerMobile" class="nav-link w-full text-left">
                        <i class="fas fa-chart-bar"></i> My Scores
                    </button>
                    @endif
                    @if(Auth::user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="nav-link">
                        <i class="fas fa-shield-alt"></i> Admin Portal
                    </a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <a href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();" class="nav-link">
                            <i class="fas fa-sign-out-alt"></i> Log Out
                        </a>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="nav-link">
                        <i class="fas fa-sign-in-alt"></i> Log in
                    </a>
                @endauth
            </div>


        </nav>

        <!-- Page Content -->
        <main class="main-wrapper">
            @hasSection('header')
                @yield('header')
            @endif
            
            <div class="main-content">
                @yield('content')
            </div>
        </main>

        <footer class="footer">
            © {{ date('Y') }} {{ config('app.name', 'Online Exam Portal') }}. All Rights Reserved.
        </footer>
    </div>

    <script>
        function toggleDropdown() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('show');
        }
        
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('show');
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.user-dropdown')) {
                document.getElementById('userDropdown').classList.remove('show');
            }
            if (!event.target.closest('.mobile-toggle') && !event.target.closest('.mobile-menu')) {
                document.getElementById('mobileMenu').classList.remove('show');
            }
        });
    </script>
    
    @stack('scripts')

    <!-- Global Scores Modal -->
    <div id="myScoresModal" class="modal-overlay" style="display: none;">
        <div class="modal-container">
            <div class="modal-content-scores">
                <div class="modal-header">
                    <h3 class="modal-title">
                        <i class="fas fa-trophy"></i>
                        My Scores Overview
                    </h3>
                    <button type="button" class="modal-close-btn" id="closeMyScoresModalButton">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body" id="scoresModalBody">
                    <div class="loading-spinner" style="text-align: center; padding: 2rem;">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p style="margin-top: 1rem;">Loading your scores...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="printScoresButton">
                        <i class="fas fa-print"></i> Print Scores
                    </button>
                    <button type="button" class="btn btn-secondary" id="anotherCloseMyScoresModalButton">
                        <i class="fas fa-times"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Global Modal JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('myScoresModal');
            const modalBody = document.getElementById('scoresModalBody');
            const modalTriggers = document.querySelectorAll('#myScoresModalTriggerLayout, #myScoresModalTriggerMobile, #myScoresModalTriggerDashboard');
            const closeModalButtonInModal = document.getElementById('closeMyScoresModalButton');
            const anotherCloseButton = document.getElementById('anotherCloseMyScoresModalButton');

            function openModal() {
                if (modal) {
                    modal.style.display = 'flex';
                    modal.classList.remove('modal-inactive');
                    modal.classList.add('modal-active');
                    document.body.style.overflow = 'hidden';
                    loadScores();
                }
            }

            function closeModal() {
                if (modal) {
                    modal.style.display = 'none';
                    modal.classList.add('modal-inactive');
                    modal.classList.remove('modal-active');
                    document.body.style.overflow = '';
                }
            }

            function loadScores() {
                modalBody.innerHTML = `
                    <div class="loading-spinner" style="text-align: center; padding: 2rem;">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p style="margin-top: 1rem;">Loading your scores...</p>
                    </div>
                `;

                fetch('/student/scores/modal', {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        modalBody.innerHTML = data.html;
                    } else {
                        modalBody.innerHTML = `
                            <div class="empty-scores">
                                <div class="empty-icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <h3>Error Loading Scores</h3>
                                <p>${data.message || 'Unable to load your scores at this time.'}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading scores:', error);
                    modalBody.innerHTML = `
                        <div class="empty-scores">
                            <div class="empty-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <h3>Error Loading Scores</h3>
                            <p>Unable to load your scores at this time. Please try again later.</p>
                        </div>
                    `;
                });
            }

            // Add event listeners to all triggers
            modalTriggers.forEach(trigger => {
                if(trigger) {
                    trigger.addEventListener('click', function(e) {
                        e.preventDefault();
                        openModal();
                    });
                }
            });
            
            // Add event listeners to close buttons
            if(closeModalButtonInModal) {
                closeModalButtonInModal.addEventListener('click', closeModal);
            }
            if(anotherCloseButton) {
                anotherCloseButton.addEventListener('click', closeModal);
            }
            
            // Add print functionality
            const printButton = document.getElementById('printScoresButton');
            if(printButton) {
                printButton.addEventListener('click', function() {
                    printScores();
                });
            }
            
            // Close on Escape key
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && modal && modal.style.display === 'flex') {
                    closeModal();
                }
            });

            // Close modal if backdrop is clicked
            if (modal) {
                modal.addEventListener('click', function(event) {
                    if (event.target === modal) { 
                        closeModal();
                    }
                });
            }
            
            // Print scores function
            function printScores() {
                const modalBody = document.getElementById('scoresModalBody');
                if (!modalBody) return;
                
                const userName = '{{ Auth::user()->name ?? "Student" }}';
                const userClass = '{{ Auth::user()->classModel->name ?? "N/A" }}';
                const currentDate = new Date().toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                });
                
                const printContent = modalBody.innerHTML;
                const printWindow = window.open('', '_blank');
                
                printWindow.document.write(`
                    <html>
                        <head>
                            <title>My Scores - ${userName}</title>
                            <style>
                                body { 
                                    font-family: Arial, sans-serif; 
                                    margin: 20px; 
                                    color: #333;
                                    line-height: 1.4;
                                }
                                .print-header {
                                    text-align: center;
                                    margin-bottom: 30px;
                                    border-bottom: 2px solid #333;
                                    padding-bottom: 20px;
                                }
                                .print-header h1 {
                                    margin: 0;
                                    color: #2563eb;
                                    font-size: 28px;
                                }
                                .print-header .student-info {
                                    margin: 10px 0;
                                    font-size: 16px;
                                }
                                .print-header .print-date {
                                    font-size: 14px;
                                    color: #666;
                                }
                                
                                /* Table Styles */
                                .scores-table-container {
                                    margin: 20px 0;
                                }
                                .scores-table { 
                                    width: 100%; 
                                    border-collapse: collapse; 
                                    margin: 20px 0;
                                }
                                .scores-table th, 
                                .scores-table td { 
                                    border: 1px solid #ddd; 
                                    padding: 12px 8px; 
                                    text-align: left; 
                                }
                                .scores-table th { 
                                    background-color: #f8f9fa; 
                                    font-weight: bold; 
                                    color: #333;
                                }
                                .scores-table tr:nth-child(even) {
                                    background-color: #f9f9f9;
                                }
                                .scores-table tr:hover {
                                    background-color: #f5f5f5;
                                }
                                
                                /* Subject Info */
                                .subject-info {
                                    display: flex;
                                    align-items: center;
                                    gap: 8px;
                                }
                                .subject-icon {
                                    color: #2563eb;
                                }
                                
                                /* Performance Badges */
                                .performance-badge {
                                    padding: 4px 8px;
                                    border-radius: 12px;
                                    font-size: 12px;
                                    font-weight: 600;
                                    text-transform: uppercase;
                                }
                                .percentage-pass {
                                    background: #d1fae5;
                                    color: #065f46;
                                }
                                .percentage-fail {
                                    background: #fee2e2;
                                    color: #991b1b;
                                }
                                .percentage-summary {
                                    background: #dbeafe;
                                    color: #1e40af;
                                }
                                
                                /* Summary Row */
                                .summary-row {
                                    background-color: #f1f5f9 !important;
                                    font-weight: bold;
                                    border-top: 2px solid #333;
                                }
                                
                                /* Score Numbers */
                                .score-number {
                                    font-weight: 600;
                                    color: #1e40af;
                                }
                                
                                /* Empty State */
                                .empty-scores {
                                    text-align: center;
                                    padding: 40px 20px;
                                }
                                .empty-icon {
                                    font-size: 48px;
                                    color: #9ca3af;
                                    margin-bottom: 20px;
                                }
                                .empty-scores h3 {
                                    color: #374151;
                                    margin-bottom: 10px;
                                }
                                .empty-scores p {
                                    color: #6b7280;
                                }
                                
                                /* Print specific */
                                @media print {
                                    body { margin: 0; }
                                    .print-header { page-break-after: avoid; }
                                }
                            </style>
                        </head>
                        <body>
                            <div class="print-header">
                                <h1>Academic Performance Report</h1>
                                <div class="student-info">
                                    <strong>Student:</strong> ${userName} | 
                                    <strong>Class:</strong> ${userClass}
                                </div>
                                <div class="print-date">Generated on: ${currentDate}</div>
                            </div>
                            ${printContent}
                        </body>
                    </html>
                `);
                
                printWindow.document.close();
                printWindow.focus();
                
                // Wait for content to load then print
                setTimeout(() => {
                    printWindow.print();
                }, 500);
            }
        });
    </script>
</body>
</html>
