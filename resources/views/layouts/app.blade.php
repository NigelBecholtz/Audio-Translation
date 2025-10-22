<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Audio Translator')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(135deg, #1f2937 0%, #374151 50%, #111827 100%);
            min-height: 100vh;
        }
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .glass-effect { backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.1); }
        .hover-lift { transition: transform 0.2s ease-in-out; }
        .hover-lift:hover { transform: translateY(-2px); }
        .pulse-animation { animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        .fade-in { animation: fadeIn 0.5s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .upload-highlight { border: 3px dashed #3B82F6; background: linear-gradient(135deg, #EBF8FF 0%, #DBEAFE 100%); }
        .upload-highlight:hover { border-color: #1D4ED8; background: linear-gradient(135deg, #DBEAFE 0%, #BFDBFE 100%); }
        
        /* Custom button styles */
        .btn-primary {
            background: linear-gradient(135deg, #374151 0%, #1f2937 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: bold;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(55, 65, 81, 0.3);
            transition: all 0.2s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(55, 65, 81, 0.4);
        }
        
        .btn-secondary {
            background: #374151;
            color: #f9fafb;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: bold;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 2px solid #4b5563;
            transition: all 0.2s ease;
        }
        .btn-secondary:hover {
            background: #4b5563;
            border-color: #6b7280;
        }
        
        .card {
            background: #1f2937;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            border: 2px solid #374151;
        }
        
        /* Better color contrast for accessibility */
        .text-gray-300 { color: #e5e7eb !important; }
        .text-gray-400 { color: #d1d5db !important; }
        
        /* Focus styles for keyboard navigation */
        a:focus, button:focus, input:focus, select:focus, textarea:focus {
            outline: 2px solid #60a5fa;
            outline-offset: 2px;
        }
        
        .status-completed { background: #dcfce7; color: #166534; border: 2px solid #bbf7d0; }
        .status-processing { background: #fef3c7; color: #92400e; border: 2px solid #fde68a; }
        .status-failed { background: #fee2e2; color: #991b1b; border: 2px solid #fecaca; }
        .status-uploaded { background: #dbeafe; color: #1e40af; border: 2px solid #bfdbfe; }
        
        /* Responsive Navigation */
        @media (max-width: 767px) {
            .hidden.md\\:flex { display: none !important; }
            .md\\:hidden { display: block !important; }
        }
        
        @media (min-width: 768px) {
            .hidden.md\\:flex { display: flex !important; }
            .md\\:hidden { display: none !important; }
        }
        
        .mobile-menu.hidden { display: none !important; }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-900 via-slate-900 to-black min-h-screen">
    <!-- Navigation -->
    <nav class="bg-gray-800 shadow-2xl border-b-2 border-gray-700 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="flex justify-between items-center h-20">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="{{ route('audio.index') }}" class="flex items-center gap-3 no-underline text-gray-50 font-bold text-xl hover:opacity-90 transition">
                        <div class="w-12 h-12 bg-blue-900 rounded-full flex items-center justify-center shadow-lg">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 1C10.9 1 10 1.9 10 3V12C10 13.1 10.9 14 12 14C13.1 14 14 13.1 14 12V3C14 1.9 13.1 1 12 1ZM19 10V12C19 15.9 15.9 19 12 19C8.1 19 5 15.9 5 12V10H7V12C7 14.8 9.2 17 12 17C14.8 17 17 14.8 17 12V10H19ZM12 21C13.1 21 14 20.1 14 19H10C10 20.1 10.9 21 12 21Z" fill="white"/>
                            </svg>
                        </div>
                        <div class="flex flex-col leading-tight">
                            <span class="text-base font-bold">AUDIO</span>
                            <span class="text-base font-bold">TRANSLATOR</span>
                        </div>
                    </a>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center gap-4">
                    @auth
                        <!-- Dashboard -->
                        <a href="{{ route('audio.index') }}" class="flex items-center gap-2 text-gray-300 no-underline font-semibold px-4 py-2 rounded-lg hover:text-white hover:bg-gray-700 transition">
                            <i class="fas fa-list"></i>
                            <span>{{ __('Dashboard') }}</span>
                        </a>

                        <!-- Audio Translation -->
                        <a href="{{ route('audio.create') }}" class="flex items-center gap-2 text-gray-300 no-underline font-semibold px-4 py-2 rounded-lg bg-gray-700 border-2 border-gray-600 hover:bg-gray-600 transition">
                            <i class="fas fa-microphone"></i>
                            <span>{{ __('Audio Translation') }}</span>
                        </a>

                        <!-- Text to Audio -->
                        <a href="{{ route('text-to-audio.create') }}" class="btn-primary text-sm px-4 py-2">
                            <i class="fas fa-plus"></i>
                            {{ __('Text to Audio') }}
                        </a>

                        <!-- Credits -->
                        <a href="{{ route('payment.credits') }}" class="flex items-center gap-2 text-gray-300 no-underline font-semibold px-4 py-2 rounded-lg bg-gray-700 border-2 border-gray-600 hover:bg-gray-600 transition">
                            <i class="fas fa-coins"></i>
                            <span>{{ __('Credits') }}</span>
                        </a>

                        <!-- Admin -->
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 text-gray-300 no-underline font-semibold px-4 py-2 rounded-lg bg-gray-600 border-2 border-gray-500 hover:bg-gray-500 transition">
                                <i class="fas fa-chart-bar"></i>
                                <span>{{ __('Admin') }}</span>
                            </a>
                        @endif

                        <!-- User Info -->
                        <div class="flex items-center gap-2 text-gray-300 font-semibold">
                            <i class="fas fa-user"></i>
                            <span>{{ auth()->user()->name }}</span>
                            <span class="bg-green-600 text-white px-2 py-1 rounded-full text-xs font-bold">
                                {{ auth()->user()->getRemainingTranslations() }} {{ __('left') }}
                            </span>
                        </div>

                        <!-- Logout -->
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="flex items-center gap-2 text-gray-300 font-semibold px-4 py-2 rounded-lg hover:text-white hover:bg-gray-700 transition bg-transparent border-0 cursor-pointer">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>{{ __('Logout') }}</span>
                            </button>
                        </form>
                    @else
                        <!-- Login -->
                        <a href="{{ route('login') }}" class="flex items-center gap-2 text-gray-300 no-underline font-semibold px-4 py-2 rounded-lg hover:text-white hover:bg-gray-700 transition">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>{{ __('Login') }}</span>
                        </a>

                        <!-- Register -->
                        <a href="{{ route('register') }}" class="btn-primary text-sm px-4 py-2">
                            <i class="fas fa-user-plus"></i>
                            {{ __('Register') }}
                        </a>
                    @endauth
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button type="button" class="mobile-menu-button text-gray-400 bg-transparent border-0 cursor-pointer p-2 hover:text-white transition" onclick="toggleMobileMenu()">
                        <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile Navigation Menu -->
            <div class="mobile-menu hidden md:hidden bg-gray-700 rounded-lg mt-2 p-4">
                @auth
                    <a href="{{ route('audio.index') }}" class="flex items-center gap-2 text-gray-300 no-underline font-semibold px-4 py-3 rounded-lg mb-2 hover:bg-gray-600 transition">
                        <i class="fas fa-list"></i>
                        <span>{{ __('Dashboard') }}</span>
                    </a>
                    <a href="{{ route('audio.create') }}" class="flex items-center gap-2 text-gray-300 no-underline font-semibold px-4 py-3 rounded-lg mb-2 hover:bg-gray-600 transition">
                        <i class="fas fa-microphone"></i>
                        <span>{{ __('Audio Translation') }}</span>
                    </a>
                    <a href="{{ route('text-to-audio.create') }}" class="flex items-center gap-2 text-gray-300 no-underline font-semibold px-4 py-3 rounded-lg mb-2 hover:bg-gray-600 transition">
                        <i class="fas fa-plus"></i>
                        <span>{{ __('Text to Audio') }}</span>
                    </a>
                    <a href="{{ route('payment.credits') }}" class="flex items-center gap-2 text-gray-300 no-underline font-semibold px-4 py-3 rounded-lg mb-2 hover:bg-gray-600 transition">
                        <i class="fas fa-coins"></i>
                        <span>{{ __('Credits') }}</span>
                    </a>
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 text-gray-300 no-underline font-semibold px-4 py-3 rounded-lg mb-2 hover:bg-gray-600 transition">
                            <i class="fas fa-chart-bar"></i>
                            <span>{{ __('Admin') }}</span>
                        </a>
                    @endif
                    <div class="flex items-center justify-between text-gray-300 font-semibold px-4 py-3 mb-2">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-user"></i>
                            <span>{{ auth()->user()->name }}</span>
                        </div>
                        <span class="bg-green-600 text-white px-2 py-1 rounded-full text-xs font-bold">
                            {{ auth()->user()->getRemainingTranslations() }} {{ __('left') }}
                        </span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex items-center gap-2 text-gray-300 font-semibold px-4 py-3 rounded-lg bg-transparent border-0 cursor-pointer w-full text-left hover:bg-gray-600 transition">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>{{ __('Logout') }}</span>
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="flex items-center gap-2 text-gray-300 no-underline font-semibold px-4 py-3 rounded-lg mb-2 hover:bg-gray-600 transition">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>{{ __('Login') }}</span>
                    </a>
                    <a href="{{ route('register') }}" class="flex items-center gap-2 text-gray-300 no-underline font-semibold px-4 py-3 rounded-lg mb-2 hover:bg-gray-600 transition">
                        <i class="fas fa-user-plus"></i>
                        <span>{{ __('Register') }}</span>
                    </a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-8 sm:px-6 lg:px-8">
        <!-- Flash Messages (fallback while debugging) -->
        @if(session('success'))
            <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-r-lg shadow-sm fade-in">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded-r-lg shadow-sm fade-in">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif
        
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800/50 backdrop-blur-sm border-t border-gray-700/20 mt-16">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <p class="text-white">&copy; 2025 {{ __('Audio Translator') }}. {{ __('Made by') }} <a href="https://becholtz.com" class="text-white hover:text-gray-300 transition">Nigel Becholtz.</a></p>
            </div>
        </div>
    </footer>

    <!-- Mobile Menu JavaScript -->
    <script>
        function toggleMobileMenu() {
            const mobileMenu = document.querySelector('.mobile-menu');
            mobileMenu.classList.toggle('hidden');
        }

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const mobileMenu = document.querySelector('.mobile-menu');
            const mobileMenuButton = document.querySelector('.mobile-menu-button');
            
            if (!mobileMenu.contains(event.target) && !mobileMenuButton.contains(event.target)) {
                mobileMenu.classList.add('hidden');
            }
        });

        // Close mobile menu when window is resized to desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 768) {
                document.querySelector('.mobile-menu').classList.add('hidden');
            }
        });
    </script>
</body>
</html>

