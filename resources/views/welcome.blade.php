<!DOCTYPE html>
<html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audio Translator - Make your audio accessible to everyone</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'pulse-slow': 'pulse 3s ease-in-out infinite',
                    }
                }
            }
        }
    </script>
            <style>
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
            </style>
    </head>
<body class="bg-gradient-to-br from-gray-900 via-slate-900 to-black min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white/10 backdrop-blur-md fixed w-full top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-blue-900 rounded-full flex items-center justify-center mr-3">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 1C10.9 1 10 1.9 10 3V12C10 13.1 10.9 14 12 14C13.1 14 14 13.1 14 12V3C14 1.9 13.1 1 12 1ZM19 10V12C19 15.9 15.9 19 12 19C8.1 19 5 15.9 5 12V10H7V12C7 14.8 9.2 17 12 17C14.8 17 17 14.8 17 12V10H19ZM12 21C13.1 21 14 20.1 14 19H10C10 20.1 10.9 21 12 21Z" fill="white"/>
                        </svg>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-white text-lg font-bold leading-tight">AUDIO</span>
                        <span class="text-white text-lg font-bold leading-tight">TRANSLATOR</span>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    @auth
                        <a href="{{ route('audio.index') }}" class="text-white hover:text-gray-300 transition-colors">
                            Dashboard
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-white hover:text-gray-300 transition-colors">
                                Logout
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-white hover:text-gray-300 transition-colors">
                            Login
                        </a>
                        <a href="{{ route('register') }}" class="bg-gradient-to-r from-white to-gray-200 text-gray-900 px-6 py-2 rounded-full hover:from-gray-100 hover:to-gray-300 transition-all duration-200 font-semibold">
                                Register
                            </a>
                    @endauth
                </div>
            </div>
        </div>
                </nav>

    <!-- Hero Section -->
    <section class="pt-32 pb-20 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto text-center">
            <div class="animate-float">
                <div class="w-32 h-32 bg-blue-900 rounded-full flex items-center justify-center mx-auto mb-8 shadow-2xl">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 1C10.9 1 10 1.9 10 3V12C10 13.1 10.9 14 12 14C13.1 14 14 13.1 14 12V3C14 1.9 13.1 1 12 1ZM19 10V12C19 15.9 15.9 19 12 19C8.1 19 5 15.9 5 12V10H7V12C7 14.8 9.2 17 12 17C14.8 17 17 14.8 17 12V10H19ZM12 21C13.1 21 14 20.1 14 19H10C10 20.1 10.9 21 12 21Z" fill="white"/>
                    </svg>
                </div>
            </div>
            
            <h1 class="text-5xl md:text-7xl font-bold text-white mb-6 leading-tight">
                <div class="flex flex-col items-center">
                    <span>AUDIO</span>
                    <span>TRANSLATOR</span>
                </div>
            </h1>
            
            <p class="text-xl md:text-2xl text-gray-300 mb-8 max-w-3xl mx-auto">
                Transform your audio in seconds to 22 different languages. 
                Upload, translate and download - it's that simple!
            </p>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center mb-12">
                @auth
                    <a href="{{ route('audio.index') }}" class="bg-gradient-to-r from-white to-gray-200 text-gray-900 px-8 py-4 rounded-full text-lg font-bold hover:from-gray-100 hover:to-gray-300 transition-all duration-200 shadow-xl hover:shadow-2xl">
                        <i class="fas fa-play mr-2"></i>
                        Start Translating
                    </a>
                @else
                    <a href="{{ route('register') }}" class="bg-gradient-to-r from-white to-gray-200 text-gray-900 px-8 py-4 rounded-full text-lg font-bold hover:from-gray-100 hover:to-gray-300 transition-all duration-200 shadow-xl hover:shadow-2xl">
                        <i class="fas fa-rocket mr-2"></i>
                        Try Free
                    </a>
                @endauth
                <a href="#features" class="bg-white/20 backdrop-blur-md text-white px-8 py-4 rounded-full text-lg font-bold hover:bg-white/30 transition-all duration-200">
                    <i class="fas fa-info-circle mr-2"></i>
                    Learn More
                </a>
            </div>
            
            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-4xl mx-auto">
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-6">
                    <div class="text-3xl font-bold text-white mb-2">22</div>
                    <div class="text-gray-300">Languages Supported</div>
                </div>
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-6">
                    <div class="text-3xl font-bold text-white mb-2">{{ config('audio.max_upload_size', 100) }}MB</div>
                    <div class="text-gray-300">Max File Size</div>
                </div>
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-6">
                    <div class="text-3xl font-bold text-white mb-2">2</div>
                    <div class="text-gray-300">Free Translations</div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-20 px-4 sm:px-6 lg:px-8 bg-white/5">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold text-white mb-6">
                    About Audio Translator
                </h2>
                <p class="text-xl text-gray-300 max-w-3xl mx-auto">
                    Our advanced AI technology makes it possible to automatically transcribe, 
                    translate and convert audio files to spoken text in the desired language.
                </p>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h3 class="text-2xl font-bold text-white mb-6">How it works</h3>
                    <div class="space-y-6">
                        <div class="flex items-start">
                            <div class="w-12 h-12 bg-gradient-to-r from-white to-gray-200 rounded-full flex items-center justify-center mr-4 flex-shrink-0">
                                <i class="fas fa-upload text-gray-900"></i>
                            </div>
                            <div>
                                <h4 class="text-lg font-semibold text-white mb-2">1. Upload Audio</h4>
                                <p class="text-gray-300">Upload your audio file (MP3, WAV, M4A, MP4) up to {{ config('audio.max_upload_size', 100) }}MB</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="w-12 h-12 bg-gradient-to-r from-gray-300 to-gray-400 rounded-full flex items-center justify-center mr-4 flex-shrink-0">
                                <i class="fas fa-language text-gray-900"></i>
                            </div>
                            <div>
                                <h4 class="text-lg font-semibold text-white mb-2">2. Choose Languages</h4>
                                <p class="text-gray-300">Select source and target language from 22 available options</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="w-12 h-12 bg-gradient-to-r from-gray-400 to-gray-500 rounded-full flex items-center justify-center mr-4 flex-shrink-0">
                                <i class="fas fa-magic text-white"></i>
                            </div>
                            <div>
                                <h4 class="text-lg font-semibold text-white mb-2">3. AI Processing</h4>
                                <p class="text-gray-300">Our AI transcribes, translates and generates new audio</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="w-12 h-12 bg-gradient-to-r from-gray-500 to-gray-600 rounded-full flex items-center justify-center mr-4 flex-shrink-0">
                                <i class="fas fa-download text-white"></i>
                            </div>
                            <div>
                                <h4 class="text-lg font-semibold text-white mb-2">4. Download Result</h4>
                                <p class="text-gray-300">Download your translated audio file directly</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-8">
                    <h3 class="text-2xl font-bold text-white mb-6">Why Audio Translator?</h3>
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-white mr-3"></i>
                            <span class="text-gray-300">Fast processing in seconds</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-white mr-3"></i>
                            <span class="text-gray-300">High quality AI translations</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-white mr-3"></i>
                            <span class="text-gray-300">Natural voices for every language</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-white mr-3"></i>
                            <span class="text-gray-300">Secure and private</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-white mr-3"></i>
                            <span class="text-gray-300">Flexible credits system</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold text-white mb-6">
                    Powerful Features
                </h2>
                <p class="text-xl text-gray-300 max-w-3xl mx-auto">
                    Discover all the capabilities of our advanced audio translation technology
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-8 hover:bg-white/20 transition-all duration-300">
                    <div class="w-16 h-16 bg-gradient-to-r from-white to-gray-200 rounded-full flex items-center justify-center mb-6">
                        <i class="fas fa-globe text-gray-900 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4">22 Languages</h3>
                    <p class="text-gray-300">
                        Support for English, Spanish, French, German, Dutch, Italian, 
                        Portuguese, Russian, Japanese, Korean, Chinese, Arabic, Hindi, Swedish, 
                        Albanian, Bulgarian, Slovak, Latvian, Finnish, Greek, Romanian and Catalan.
                    </p>
                </div>
                
                <!-- Feature 2 -->
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-8 hover:bg-white/20 transition-all duration-300">
                    <div class="w-16 h-16 bg-gradient-to-r from-gray-300 to-gray-400 rounded-full flex items-center justify-center mb-6">
                        <i class="fas fa-brain text-gray-900 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4">AI-Powered</h3>
                    <p class="text-gray-300">
                        Uses the latest OpenAI technology for accurate transcription, 
                        natural translations and realistic text-to-speech conversion.
                    </p>
                </div>
                
                <!-- Feature 3 -->
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-8 hover:bg-white/20 transition-all duration-300">
                    <div class="w-16 h-16 bg-gradient-to-r from-gray-400 to-gray-500 rounded-full flex items-center justify-center mb-6">
                        <i class="fas fa-bolt text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4">Fast & Efficient</h3>
                    <p class="text-gray-300">
                        Processing in seconds, not minutes. Upload your file and get 
                        your translated audio back in no time.
                    </p>
                </div>
                
                <!-- Feature 4 -->
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-8 hover:bg-white/20 transition-all duration-300">
                    <div class="w-16 h-16 bg-gradient-to-r from-gray-500 to-gray-600 rounded-full flex items-center justify-center mb-6">
                        <i class="fas fa-shield-alt text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4">Secure & Private</h3>
                    <p class="text-gray-300">
                        Your files are processed securely and not stored. 
                        Your privacy and data are fully protected.
                    </p>
                </div>
                
                <!-- Feature 5 -->
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-8 hover:bg-white/20 transition-all duration-300">
                    <div class="w-16 h-16 bg-gradient-to-r from-gray-600 to-gray-700 rounded-full flex items-center justify-center mb-6">
                        <i class="fas fa-coins text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4">Credits System</h3>
                    <p class="text-gray-300">
                        Buy credits and use them whenever you want. No monthly costs, 
                        no hidden fees. Transparent and fair.
                    </p>
                </div>
                
                <!-- Feature 6 -->
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-8 hover:bg-white/20 transition-all duration-300">
                    <div class="w-16 h-16 bg-gradient-to-r from-gray-700 to-gray-800 rounded-full flex items-center justify-center mb-6">
                        <i class="fas fa-mobile-alt text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4">Responsive Design</h3>
                    <p class="text-gray-300">
                        Works perfectly on desktop, tablet and mobile. 
                        Upload and translate your audio wherever you are.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="py-20 px-4 sm:px-6 lg:px-8 bg-white/5">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold text-white mb-6">
                    Simple Pricing
                </h2>
                <p class="text-xl text-gray-300 max-w-3xl mx-auto">
                    No hidden costs, no subscriptions. Pay only for what you use.
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                <!-- Free Tier -->
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-8 text-center">
                    <h3 class="text-2xl font-bold text-white mb-4">Free</h3>
                    <div class="text-4xl font-bold text-white mb-6">€0</div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center justify-center">
                            <i class="fas fa-check text-white mr-2"></i>
                            <span class="text-gray-300">2 free translations</span>
                        </li>
                        <li class="flex items-center justify-center">
                            <i class="fas fa-check text-white mr-2"></i>
                            <span class="text-gray-300">All 22 languages</span>
                        </li>
                        <li class="flex items-center justify-center">
                            <i class="fas fa-check text-white mr-2"></i>
                            <span class="text-gray-300">{{ config('audio.max_upload_size', 100) }}MB files</span>
                        </li>
                    </ul>
                    @auth
                        <a href="{{ route('audio.index') }}" class="w-full bg-white/20 text-white py-3 rounded-full hover:bg-white/30 transition-colors">
                            Go to Dashboard
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="w-full bg-white/20 text-white py-3 rounded-full hover:bg-white/30 transition-colors">
                            Register Free
                        </a>
                    @endauth
                </div>
                
                <!-- Credits Package -->
                <div class="bg-gradient-to-r from-white to-gray-200 rounded-2xl p-8 text-center transform scale-105">
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Starter Pack</h3>
                    <div class="text-4xl font-bold text-gray-900 mb-6">€5</div>
                    <div class="text-gray-600 mb-6">10 credits</div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center justify-center">
                            <i class="fas fa-check text-gray-900 mr-2"></i>
                            <span class="text-gray-700">10 translations</span>
                        </li>
                        <li class="flex items-center justify-center">
                            <i class="fas fa-check text-gray-900 mr-2"></i>
                            <span class="text-gray-700">All 22 languages</span>
                        </li>
                        <li class="flex items-center justify-center">
                            <i class="fas fa-check text-gray-900 mr-2"></i>
                            <span class="text-gray-700">{{ config('audio.max_upload_size', 100) }}MB files</span>
                        </li>
                        <li class="flex items-center justify-center">
                            <i class="fas fa-check text-gray-900 mr-2"></i>
                            <span class="text-gray-700">No expiration</span>
                        </li>
                    </ul>
                    @auth
                        <a href="{{ route('payment.credits') }}" class="w-full bg-gray-900 text-white py-3 rounded-full hover:bg-gray-800 transition-colors font-bold">
                            Buy Credits
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="w-full bg-gray-900 text-white py-3 rounded-full hover:bg-gray-800 transition-colors font-bold">
                            Start Now
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-20 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold text-white mb-6">
                    Get in Touch
                </h2>
                <p class="text-xl text-gray-300 max-w-3xl mx-auto">
                    Have questions? We're happy to help!
                </p>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                <div>
                    <h3 class="text-2xl font-bold text-white mb-6">Contact Information</h3>
                    <div class="space-y-6">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-r from-white to-gray-200 rounded-full flex items-center justify-center mr-4">
                                <i class="fas fa-envelope text-gray-900"></i>
                            </div>
                            <div>
                                <h4 class="text-lg font-semibold text-white">Email</h4>
                                <p class="text-gray-300">support@audio-translator.com</p>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-r from-gray-300 to-gray-400 rounded-full flex items-center justify-center mr-4">
                                <i class="fas fa-clock text-gray-900"></i>
                            </div>
                            <div>
                                <h4 class="text-lg font-semibold text-white">Support Hours</h4>
                                <p class="text-gray-300">Monday - Friday: 9:00 - 18:00</p>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-r from-gray-400 to-gray-500 rounded-full flex items-center justify-center mr-4">
                                <i class="fas fa-headset text-white"></i>
                            </div>
                            <div>
                                <h4 class="text-lg font-semibold text-white">Live Chat</h4>
                                <p class="text-gray-300">Available during support hours</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-8">
                    <h3 class="text-2xl font-bold text-white mb-6">Send a Message</h3>
                    <form class="space-y-6">
                        <div>
                            <label class="block text-white mb-2">Name</label>
                            <input type="text" class="w-full px-4 py-3 bg-white/20 border border-white/30 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-white" placeholder="Your name">
                        </div>
                        <div>
                            <label class="block text-white mb-2">Email</label>
                            <input type="email" class="w-full px-4 py-3 bg-white/20 border border-white/30 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-white" placeholder="your@email.com">
                        </div>
                        <div>
                            <label class="block text-white mb-2">Subject</label>
                            <input type="text" class="w-full px-4 py-3 bg-white/20 border border-white/30 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-white" placeholder="Subject of your message">
                        </div>
                        <div>
                            <label class="block text-white mb-2">Message</label>
                            <textarea rows="4" class="w-full px-4 py-3 bg-white/20 border border-white/30 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-white" placeholder="Your message..."></textarea>
                        </div>
                        <button type="submit" class="w-full bg-gradient-to-r from-white to-gray-200 text-gray-900 py-3 rounded-lg hover:from-gray-100 hover:to-gray-300 transition-all duration-200 font-bold">
                            <i class="fas fa-paper-plane mr-2"></i>
                            Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-black/20 backdrop-blur-md py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="md:col-span-2">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 bg-blue-900 rounded-full flex items-center justify-center mr-3">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 1C10.9 1 10 1.9 10 3V12C10 13.1 10.9 14 12 14C13.1 14 14 13.1 14 12V3C14 1.9 13.1 1 12 1ZM19 10V12C19 15.9 15.9 19 12 19C8.1 19 5 15.9 5 12V10H7V12C7 14.8 9.2 17 12 17C14.8 17 17 14.8 17 12V10H19ZM12 21C13.1 21 14 20.1 14 19H10C10 20.1 10.9 21 12 21Z" fill="white"/>
                            </svg>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-white text-lg font-bold leading-tight">AUDIO</span>
                            <span class="text-white text-lg font-bold leading-tight">TRANSLATOR</span>
                        </div>
                    </div>
                    <p class="text-gray-300 mb-4">
                        Make your audio accessible to everyone with our advanced AI technology. 
                        Fast, secure and affordable.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-300 hover:text-white transition-colors">
                            <i class="fab fa-twitter text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-300 hover:text-white transition-colors">
                            <i class="fab fa-facebook text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-300 hover:text-white transition-colors">
                            <i class="fab fa-linkedin text-xl"></i>
                        </a>
                    </div>
                </div>
                
                <div>
                    <h4 class="text-white font-semibold mb-4">Product</h4>
                    <ul class="space-y-2">
                        <li><a href="#features" class="text-gray-300 hover:text-white transition-colors">Features</a></li>
                        <li><a href="#pricing" class="text-gray-300 hover:text-white transition-colors">Pricing</a></li>
                        <li><a href="#about" class="text-gray-300 hover:text-white transition-colors">About Us</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-white font-semibold mb-4">Support</h4>
                    <ul class="space-y-2">
                        <li><a href="#contact" class="text-gray-300 hover:text-white transition-colors">Contact</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-white transition-colors">Help Center</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-white transition-colors">Privacy Policy</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-white transition-colors">Terms of Service</a></li>
                    </ul>
                </div>
        </div>

            <div class="border-t border-white/20 mt-8 pt-8 text-center">
                <p class="text-gray-300">
                    © 2025 Audio Translator. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

    <!-- Smooth Scrolling -->
    <script>
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
    </body>
</html>
