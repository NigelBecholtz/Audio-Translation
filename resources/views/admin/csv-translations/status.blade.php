<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Translation Status - Admin Panel</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">
    <div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <a href="{{ route('admin.csv-translations.index') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-block">
                    ← Back to Upload
                </a>
                <h1 class="text-3xl font-bold text-gray-900">Translation Status</h1>
                <p class="mt-2 text-gray-600">File: <strong>{{ $job->original_filename }}</strong></p>
            </div>

            <!-- Success/Error Messages -->
            @if(session('success'))
                <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Status Card -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <!-- Status Badge -->
                <div class="mb-6">
                    <span class="text-sm font-medium text-gray-700">Status:</span>
                    <span id="statusBadge" class="ml-2 px-3 py-1 rounded-full text-sm font-semibold
                        @if($job->status === 'completed') bg-green-100 text-green-800
                        @elseif($job->status === 'failed') bg-red-100 text-red-800
                        @elseif($job->status === 'processing') bg-blue-100 text-blue-800
                        @else bg-yellow-100 text-yellow-800
                        @endif">
                        <span id="statusText">{{ ucfirst($job->status) }}</span>
                    </span>
                </div>

                <!-- Progress Bar -->
                <div id="progressSection" class="mb-6 {{ $job->isCompleted() || $job->isFailed() ? 'hidden' : '' }}">
                    <div class="flex justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Progress</span>
                        <span class="text-sm font-medium text-gray-700">
                            <span id="progressPercentage">{{ $job->progress_percentage }}</span>%
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
                        <div id="progressBar" 
                             class="bg-blue-600 h-4 rounded-full transition-all duration-500 ease-out"
                             style="width: {{ $job->progress_percentage }}%">
                        </div>
                    </div>
                    <div class="mt-2 text-sm text-gray-600">
                        <span id="processedItems">{{ number_format($job->processed_items) }}</span> / 
                        <span id="totalItems">{{ number_format($job->total_items) }}</span> items translated
                    </div>
                </div>

                <!-- Loading Animation -->
                <div id="loadingAnimation" class="mb-6 {{ $job->isCompleted() || $job->isFailed() ? 'hidden' : '' }}">
                    <div class="flex items-center justify-center">
                        <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="ml-3 text-gray-700">Processing your file... This may take several minutes for large files.</span>
                    </div>
                </div>

                <!-- Completed Section -->
                <div id="completedSection" class="mb-6 {{ !$job->isCompleted() ? 'hidden' : '' }}">
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                        <div class="flex">
                            <svg class="h-6 w-6 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div>
                                <h3 class="text-lg font-semibold text-green-900">Translation Completed!</h3>
                                <p class="text-sm text-green-800 mt-1">Your file has been successfully translated.</p>
                                <p class="text-sm text-green-800 mt-1">
                                    <strong><span id="finalProcessedItems">{{ number_format($job->processed_items) }}</span></strong> items translated
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <a href="{{ route('admin.csv-translations.download', $job->id) }}" 
                       class="w-full flex justify-center items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Download Translated File
                    </a>
                </div>

                <!-- Failed Section -->
                <div id="failedSection" class="mb-6 {{ !$job->isFailed() ? 'hidden' : '' }}">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex">
                            <svg class="h-6 w-6 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div>
                                <h3 class="text-lg font-semibold text-red-900">Translation Failed</h3>
                                <p class="text-sm text-red-800 mt-1" id="errorMessage">{{ $job->error_message }}</p>
                                <div class="mt-4">
                                    <a href="{{ route('admin.csv-translations.index') }}" 
                                       class="text-sm font-medium text-red-700 hover:text-red-900">
                                        ← Try again with a different file
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Job Details -->
                <div class="border-t pt-4 mt-4">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">Job Details</h3>
                    <dl class="grid grid-cols-1 gap-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Job ID:</dt>
                            <dd class="text-gray-900 font-medium">{{ $job->id }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Uploaded:</dt>
                            <dd class="text-gray-900 font-medium">{{ $job->created_at->format('Y-m-d H:i:s') }}</dd>
                        </div>
                        @if($job->started_at)
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Started:</dt>
                            <dd class="text-gray-900 font-medium">{{ $job->started_at->format('Y-m-d H:i:s') }}</dd>
                        </div>
                        @endif
                        @if($job->completed_at)
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Completed:</dt>
                            <dd class="text-gray-900 font-medium">{{ $job->completed_at->format('Y-m-d H:i:s') }}</dd>
                        </div>
                        @endif
                        @if($job->target_languages)
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Target Languages:</dt>
                            <dd class="text-gray-900 font-medium">{{ implode(', ', $job->target_languages) }}</dd>
                        </div>
                        @endif
                        @if($job->use_smart_fallback)
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Mode:</dt>
                            <dd class="text-gray-900 font-medium">Smart Fallback</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-blue-900 mb-2">📌 Note:</h3>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li>• This page updates automatically every 3 seconds</li>
                    <li>• Large files (100k+ rows) may take 20-30 minutes</li>
                    <li>• You can close this page and return later</li>
                    <li>• Download link will be available once completed</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        let pollingInterval;
        const jobId = {{ $job->id }};
        const statusUrl = "{{ route('admin.csv-translations.status-api', $job->id) }}";

        function updateStatus() {
            fetch(statusUrl)
                .then(response => response.json())
                .then(data => {
                    // Update status badge
                    const statusBadge = document.getElementById('statusBadge');
                    const statusText = document.getElementById('statusText');
                    statusText.textContent = data.status.charAt(0).toUpperCase() + data.status.slice(1);
                    
                    // Update badge colors
                    statusBadge.className = 'ml-2 px-3 py-1 rounded-full text-sm font-semibold';
                    if (data.status === 'completed') {
                        statusBadge.classList.add('bg-green-100', 'text-green-800');
                    } else if (data.status === 'failed') {
                        statusBadge.classList.add('bg-red-100', 'text-red-800');
                    } else if (data.status === 'processing') {
                        statusBadge.classList.add('bg-blue-100', 'text-blue-800');
                    } else {
                        statusBadge.classList.add('bg-yellow-100', 'text-yellow-800');
                    }

                    // Update progress
                    if (data.total_items > 0) {
                        document.getElementById('progressPercentage').textContent = data.progress_percentage.toFixed(2);
                        document.getElementById('progressBar').style.width = data.progress_percentage + '%';
                        document.getElementById('processedItems').textContent = data.processed_items.toLocaleString();
                        document.getElementById('totalItems').textContent = data.total_items.toLocaleString();
                    }

                    // Show/hide sections based on status
                    const progressSection = document.getElementById('progressSection');
                    const loadingAnimation = document.getElementById('loadingAnimation');
                    const completedSection = document.getElementById('completedSection');
                    const failedSection = document.getElementById('failedSection');

                    if (data.is_completed) {
                        progressSection.classList.add('hidden');
                        loadingAnimation.classList.add('hidden');
                        completedSection.classList.remove('hidden');
                        failedSection.classList.add('hidden');
                        
                        document.getElementById('finalProcessedItems').textContent = data.processed_items.toLocaleString();
                        
                        // Stop polling
                        if (pollingInterval) {
                            clearInterval(pollingInterval);
                        }
                        
                        // Show success notification
                        if (!window.completedNotified) {
                            window.completedNotified = true;
                            alert('Translation completed! You can now download your file.');
                        }
                    } else if (data.is_failed) {
                        progressSection.classList.add('hidden');
                        loadingAnimation.classList.add('hidden');
                        completedSection.classList.add('hidden');
                        failedSection.classList.remove('hidden');
                        
                        document.getElementById('errorMessage').textContent = data.error_message || 'Unknown error occurred';
                        
                        // Stop polling
                        if (pollingInterval) {
                            clearInterval(pollingInterval);
                        }
                    } else {
                        progressSection.classList.remove('hidden');
                        loadingAnimation.classList.remove('hidden');
                        completedSection.classList.add('hidden');
                        failedSection.classList.add('hidden');
                    }
                })
                .catch(error => {
                    console.error('Error fetching status:', error);
                });
        }

        // Initial update
        updateStatus();

        // Poll every 3 seconds if not completed or failed
        @if($job->isProcessing())
        pollingInterval = setInterval(updateStatus, 3000);
        @endif
    </script>
</body>
</html>

