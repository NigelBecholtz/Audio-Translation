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
        
        .status-completed { background: #dcfce7; color: #166534; border: 2px solid #bbf7d0; }
        .status-processing { background: #fef3c7; color: #92400e; border: 2px solid #fde68a; }
        .status-failed { background: #fee2e2; color: #991b1b; border: 2px solid #fecaca; }
        .status-uploaded { background: #dbeafe; color: #1e40af; border: 2px solid #bfdbfe; }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-900 via-slate-900 to-black min-h-screen">
    <!-- Navigation -->
    <nav style="background: #1f2937; box-shadow: 0 4px 20px rgba(0,0,0,0.3); border-bottom: 3px solid #374151; position: sticky; top: 0; z-index: 50;">
        <div style="max-width: 1200px; margin: 0 auto; padding: 0 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; height: 80px;">
                <div style="display: flex; align-items: center;">
                    <a href="{{ route('audio.index') }}" style="display: flex; align-items: center; gap: 12px; text-decoration: none; color: #f9fafb; font-weight: bold; font-size: 24px;">
                        <div style="width: 48px; height: 48px; background: #1e3a8a; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(30, 58, 138, 0.3);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 1C10.9 1 10 1.9 10 3V12C10 13.1 10.9 14 12 14C13.1 14 14 13.1 14 12V3C14 1.9 13.1 1 12 1ZM19 10V12C19 15.9 15.9 19 12 19C8.1 19 5 15.9 5 12V10H7V12C7 14.8 9.2 17 12 17C14.8 17 17 14.8 17 12V10H19ZM12 21C13.1 21 14 20.1 14 19H10C10 20.1 10.9 21 12 21Z" fill="white"/>
                            </svg>
                        </div>
                        <div style="display: flex; flex-direction: column; line-height: 1.1;">
                            <span style="font-size: 18px; font-weight: 700;">AUDIO</span>
                            <span style="font-size: 18px; font-weight: 700;">TRANSLATOR</span>
                        </div>
                    </a>
                </div>
                <div style="display: flex; align-items: center; gap: 16px;">
                    @auth
                        <a href="{{ route('audio.index') }}" style="display: flex; align-items: center; gap: 8px; color: #d1d5db; text-decoration: none; font-weight: 600; padding: 8px 16px; border-radius: 8px; transition: all 0.2s;">
                            <i class="fas fa-list"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="{{ route('audio.create') }}" class="btn-primary" style="font-size: 18px; padding: 16px 32px;">
                            <i class="fas fa-plus"></i>
                            New Translation
                        </a>
                        <a href="{{ route('payment.credits') }}" style="display: flex; align-items: center; gap: 8px; color: #d1d5db; text-decoration: none; font-weight: 600; padding: 8px 16px; border-radius: 8px; transition: all 0.2s; background: #374151; border: 2px solid #4b5563;">
                            <i class="fas fa-coins"></i>
                            <span>Credits</span>
                        </a>
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" style="display: flex; align-items: center; gap: 8px; color: #d1d5db; text-decoration: none; font-weight: 600; padding: 8px 16px; border-radius: 8px; transition: all 0.2s; background: #4b5563; border: 2px solid #6b7280;">
                                <i class="fas fa-chart-bar"></i>
                                <span>Admin</span>
                            </a>
                        @endif
                        <div style="display: flex; align-items: center; gap: 8px; color: #d1d5db; font-weight: 600;">
                            <i class="fas fa-user"></i>
                            <span>{{ auth()->user()->name }}</span>
                            <span style="background: #10b981; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px;">
                                {{ auth()->user()->getRemainingTranslations() }} left
                            </span>
                        </div>
                        <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                            @csrf
                            <button type="submit" style="display: flex; align-items: center; gap: 8px; color: #d1d5db; text-decoration: none; font-weight: 600; padding: 8px 16px; border-radius: 8px; transition: all 0.2s; background: none; border: none; cursor: pointer;">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Logout</span>
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" style="display: flex; align-items: center; gap: 8px; color: #d1d5db; text-decoration: none; font-weight: 600; padding: 8px 16px; border-radius: 8px; transition: all 0.2s;">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>Login</span>
                        </a>
                        <a href="{{ route('register') }}" class="btn-primary" style="font-size: 18px; padding: 16px 32px;">
                            <i class="fas fa-user-plus"></i>
                            Register
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-8 sm:px-6 lg:px-8">
        <!-- Flash Messages -->
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
            <div class="text-center text-white">
                <p class="text-white" style="color: #ffffff !important;">&copy; 2025 Audio Translator. Made by <a href="https://becholtz.com" class="text-white hover:text-gray-100" style="color: #ffffff !important;">Nigel Becholtz.</a></p>
            </div>
        </div>
    </footer>
</body>
</html>

