<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Online Exam Portal') }}</title>

    <!-- Local Fonts -->
    <link rel="stylesheet" href="{{ asset('assets/css/inter-font.css') }}" />
    
    <!-- Font Awesome (Local) -->
    <link rel="stylesheet" href="{{ asset('assets/css/fontawesome.min.css') }}">

    <!-- Scripts and Styles (Using Vite for Tailwind CSS) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
    <style>
        body { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
        .modal-active { display: flex !important; }
    </style>
</head>
<body class="font-sans bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
    <div class="min-h-screen flex flex-col">
        <!-- Navigation -->
        <nav x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-md sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="shrink-0 flex items-center">
                            <a href="{{ auth()->check() && auth()->user()->isStudent() ? route('user.dashboard') : route('home') }}">
                                <span class="font-extrabold text-xl text-indigo-600 dark:text-indigo-400 flex items-center">
                                    <i class="fas fa-graduation-cap mr-2"></i>
                                    {{ config('app.name', 'Exam Portal') }}
                                </span>
                            </a>
                        </div>

                        <!-- Navigation Links -->
                        @auth
                            @if(Auth::user()->isStudent())
                            <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                                <a href="{{ route('user.dashboard') }}" 
                                   class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition duration-150 ease-in-out {{ request()->routeIs('user.dashboard') ? 'border-indigo-400 dark:border-indigo-600 text-gray-900 dark:text-white focus:border-indigo-700' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700 focus:text-gray-700 focus:border-gray-300' }}">
                                    Dashboard
                                </a>
                                {{-- Add other student nav links here --}}
                            </div>
                            @endif
                        @endauth
                    </div>

                    <!-- Right Side Of Navbar -->
                    <div class="hidden sm:flex sm:items-center sm:ml-6">
                        @auth
                            <!-- My Scores Modal Trigger -->
                            @if(Auth::user()->isStudent())
                            <button type="button" id="myScoresModalTriggerLayout"
                                    class="mr-4 px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 focus:outline-none transition ease-in-out duration-150 flex items-center rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                                <i class="fas fa-clipboard-list mr-1.5"></i> My Scores
                            </button>
                            @endif

                            <!-- User Dropdown -->
                            <div class="relative" x-data="{ dropdownOpen: false }" @click.outside="dropdownOpen = false">
                                <button @click="dropdownOpen = !dropdownOpen" class="flex items-center text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800 px-3 py-2 transition">
                                    <i class="fas fa-user-circle mr-2 text-lg opacity-75"></i>
                                    <span>{{ Auth::user()->name }}</span>
                                    <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <div x-show="dropdownOpen"
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 scale-95 transform"
                                     x-transition:enter-end="opacity-100 scale-100 transform"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="opacity-100 scale-100 transform"
                                     x-transition:leave-end="opacity-0 scale-95 transform"
                                     class="absolute right-0 mt-2 w-48 rounded-md shadow-xl py-1 bg-white dark:bg-gray-700 ring-1 ring-black ring-opacity-5 z-50"
                                     style="display: none;" @click="dropdownOpen = false">
                                    {{-- NO profile.edit link as it's not defined for manual auth unless you build it --}}
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <a href="{{ route('logout') }}"
                                           onclick="event.preventDefault(); this.closest('form').submit();"
                                           class="block w-full px-4 py-2 text-left text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-600">
                                            <i class="fas fa-sign-out-alt fa-fw mr-2 opacity-75"></i>Log Out
                                        </a>
                                    </form>
                                </div>
                            </div>
                        @else
                            <a href="{{ route('login') }}" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">Log in</a>
                            {{-- If you add registration:
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="ml-4 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">Register</a>
                            @endif
                            --}}
                        @endauth
                    </div>

                    <!-- Hamburger -->
                    <div class="-mr-2 flex items-center sm:hidden">
                        <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out">
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Responsive Navigation Menu -->
             <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden border-b border-gray-200 dark:border-gray-700">
                @auth
                    @if(Auth::user()->isStudent())
                    <div class="pt-2 pb-3 space-y-1">
                        <a href="{{ route('user.dashboard') }}" class="block pl-3 pr-4 py-2 border-l-4 {{ request()->routeIs('user.dashboard') ? 'border-indigo-400 text-indigo-700 bg-indigo-50 dark:text-indigo-300 dark:bg-indigo-900/50' : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 dark:hover:text-gray-200 dark:hover:bg-gray-700 dark:hover:border-gray-600' }} text-base font-medium focus:outline-none focus:text-gray-800 focus:bg-gray-50 focus:border-gray-300 transition duration-150 ease-in-out">
                            Dashboard
                        </a>
                        <button type="button" id="myScoresModalTriggerMobile"
                            class="block w-full pl-3 pr-4 py-2 border-l-4 border-transparent text-left text-base font-medium text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 hover:border-gray-300 dark:hover:border-gray-600 focus:outline-none focus:text-gray-800 dark:focus:text-gray-200 focus:bg-gray-50 dark:focus:bg-gray-700 focus:border-gray-300 dark:focus:border-gray-600 transition duration-150 ease-in-out">
                             My Scores
                        </button>
                    </div>
                    @endif
                    <!-- Responsive Settings Options -->
                    <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
                        <div class="px-4">
                            <div class="font-medium text-base text-gray-800 dark:text-gray-200">{{ Auth::user()->name }}</div>
                            <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
                        </div>
                        <div class="mt-3 space-y-1">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <a href="{{ route('logout') }}"
                                        onclick="event.preventDefault(); this.closest('form').submit();"
                                        class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-left text-base font-medium text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 hover:border-gray-300 dark:hover:border-gray-600 focus:outline-none focus:text-gray-800 dark:focus:text-gray-200 focus:bg-gray-50 dark:focus:bg-gray-700 focus:border-gray-300 dark:focus:border-gray-600 transition duration-150 ease-in-out">
                                    Log Out
                                </a>
                            </form>
                        </div>
                    </div>
                @endauth
            </div>
        </nav>

        <!-- Page Heading from Slot -->
        @if (isset($header))
            <header class="bg-white dark:bg-gray-800 shadow-sm">
                <div class="max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endif

        <!-- Page Content -->
        <main class="flex-grow container mx-auto px-4 sm:px-6 lg:px-8 py-8">
            {{ $slot }} {{-- This is where the content from user.dashboard.blade.php will go --}}
        </main>

        <footer class="text-center py-6 text-sm text-gray-500 dark:text-gray-400 border-t border-gray-200 dark:border-gray-700 mt-auto">
            © {{ date('Y') }} {{ config('app.name', 'Online Exam Portal') }}. All Rights Reserved.
        </footer>
    </div>

    {{-- Alpine.js for interactive components like dropdowns --}}
    {{-- Make sure Alpine is included if not already in your app.js by Vite --}}
    {{-- <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script> --}}
    @stack('scripts') {{-- For page-specific JavaScript --}}

    <!-- "My Scores" Modal (Moved from user.dashboard.blade.php) -->
    <div id="myScoresModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-screen bg-gray-900 bg-opacity-75 dark:bg-opacity-90">
        <div class="relative p-4 w-full max-w-4xl max-h-full">
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl">
                <div class="flex justify-between items-center p-5 border-b rounded-t dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-800 z-10">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                        <i class="fas fa-trophy mr-2 text-yellow-400"></i>My Overall Scores
                    </h3>
                    <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-700 dark:hover:text-white" 
                            onclick="document.getElementById('myScoresModal').classList.add('hidden'); document.getElementById('myScoresModal').classList.remove('flex'); document.body.classList.remove('overflow-hidden');">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                    </button>
                </div>
                <div class="p-6 space-y-6 max-h-[70vh] overflow-y-auto">
                    @if(isset($allUserScores) && $allUserScores->isEmpty())
                        <p class="text-gray-500 dark:text-gray-400 text-center py-8">You haven't completed any exams yet.</p>
                    @elseif(isset($allUserScores))
                        <div class="overflow-x-auto relative shadow-md sm:rounded-lg border dark:border-gray-700">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-300">
                                    <tr>
                                        <th scope="col" class="px-6 py-3">Subject</th>
                                        <th scope="col" class="px-6 py-3 text-center">Your Score</th>
                                        <th scope="col" class="px-6 py-3 text-center">Total Qs</th>
                                        <th scope="col" class="px-6 py-3 text-center">Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $grandTotalScored = 0; $grandTotalPossible = 0; @endphp
                                    @foreach($allUserScores as $scoreEntry)
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                            {{ $scoreEntry->subject->name ?? 'N/A' }}
                                        </th>
                                        <td class="px-6 py-4 text-center font-semibold">{{ $scoreEntry->score }}</td>
                                        <td class="px-6 py-4 text-center">{{ $scoreEntry->total_questions }}</td>
                                        <td class="px-6 py-4 text-center font-semibold">
                                            {{ $scoreEntry->total_questions > 0 ? round(($scoreEntry->score / $scoreEntry->total_questions) * 100, 1) : 0 }}%
                                        </td>
                                        @php $grandTotalScored += $scoreEntry->score; $grandTotalPossible += $scoreEntry->total_questions; @endphp
                                    </tr>
                                    @endforeach
                                </tbody>
                                 @if($grandTotalPossible > 0)
                                <tfoot class="font-bold text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-700">
                                    <tr>
                                        <th scope="row" class="px-6 py-3 text-base text-right">Overall Total:</th>
                                        <td class="px-6 py-3 text-center text-base">{{ $grandTotalScored }}</td>
                                        <td class="px-6 py-3 text-center text-base">{{ $grandTotalPossible }}</td>
                                        <td class="px-6 py-3 text-center text-base">
                                            {{ round(($grandTotalScored / $grandTotalPossible) * 100, 1) }}%
                                        </td>
                                    </tr>
                                </tfoot>
                                @endif
                            </table>
                        </div>
                    @else
                        <p class="text-gray-500 dark:text-gray-400 text-center py-8">No scores data available.</p>
                    @endif
                </div>
                 <div class="flex items-center p-6 space-x-2 border-t border-gray-200 rounded-b dark:border-gray-700 justify-end">
                    <button type="button" class="admin-btn-secondary"
                            onclick="document.getElementById('myScoresModal').classList.add('hidden'); document.getElementById('myScoresModal').classList.remove('flex'); document.body.classList.remove('overflow-hidden');">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('myScoresModal');
            const modalTriggerLayout = document.getElementById('myScoresModalTriggerLayout');
            const modalTriggerMobile = document.getElementById('myScoresModalTriggerMobile');

            function openModal() {
                if (modal) {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    document.body.classList.add('overflow-hidden');
                }
            }

            function closeModal() {
                if (modal) {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    document.body.classList.remove('overflow-hidden');
                }
            }

            if (modalTriggerLayout) {
                modalTriggerLayout.addEventListener('click', openModal);
            }
            if (modalTriggerMobile) {
                modalTriggerMobile.addEventListener('click', openModal);
            }

            const closeButtons = modal.querySelectorAll('[onclick*="myScoresModal"]');
            closeButtons.forEach(btn => {
                btn.addEventListener('click', closeModal);
            });
            
            window.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });
        });
    </script>
</body>
</html>
