<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>CSV Translation - Admin Panel</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">
    <div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <a href="{{ route('admin.dashboard') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-block">
                    ← Back to Dashboard
                </a>
                <h1 class="text-3xl font-bold text-gray-900">File Translation</h1>
                <p class="mt-2 text-gray-600">Upload a CSV or XLSX file to translate from English to multiple languages</p>
            </div>

            <!-- Success/Error Messages -->
            @if(session('success'))
                <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Upload Form -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <form action="{{ route('admin.csv-translations.process') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                    @csrf

                    <div class="space-y-6">
                        <!-- File Input -->
                        <div>
                            <label for="csv_file" class="block text-sm font-medium text-gray-700 mb-2">
                                Select CSV or XLSX File
                            </label>
                            <input 
                                type="file" 
                                name="csv_file" 
                                id="csv_file" 
                                accept=".csv,.xlsx"
                                required
                                class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none focus:border-blue-500"
                            >
                            <p class="mt-1 text-sm text-gray-500">Maximum file size: 10MB. Supports CSV and XLSX files.</p>
                        </div>

                        <!-- Info Box -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h3 class="text-sm font-semibold text-blue-900 mb-2">File Format Requirements:</h3>
                            <ul class="text-sm text-blue-800 space-y-1">
                                <li>• <strong>CSV:</strong> Delimiter: <strong>semicolon (;)</strong></li>
                                <li>• <strong>XLSX:</strong> Standard Excel format</li>
                                <li>• First column: <strong>key</strong> (unique identifier)</li>
                                <li>• Second column: <strong>en</strong> (English source text)</li>
                                <li>• Other columns: target languages (es_AR, fr, de, it, nl, ro, gr, sk, lv, bg, fi, al, ca)</li>
                                <li>• Only <strong>empty cells</strong> will be translated</li>
                                <li>• Existing translations will be preserved</li>
                            </ul>
                        </div>

                        <!-- Example Structure -->
                        <details class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <summary class="text-sm font-semibold text-gray-900 cursor-pointer">
                                View Example File Structure
                            </summary>
                            <div class="mt-3">
                                <pre class="text-xs bg-white p-3 rounded border border-gray-300 overflow-x-auto"><code>key;en;es_AR;fr;de;it;nl
welcome;Welcome;;;;
hello;Hello world;;;;
goodbye;Goodbye;;;;;</code></pre>
                                <p class="text-xs text-gray-600 mt-2">Same structure applies to both CSV and XLSX files</p>
                            </div>
                        </details>

                        <!-- Submit Button -->
                        <div>
                            <button 
                                type="submit" 
                                id="submitBtn"
                                class="w-full flex justify-center items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span id="btnText">Translate File</span>
                                <svg id="spinner" class="hidden animate-spin ml-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </button>
                            <p class="mt-2 text-sm text-gray-500 text-center">
                                Processing may take a few minutes depending on file size
                            </p>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Supported Languages -->
            <div class="mt-8 bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Supported Languages</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    <div class="text-sm">
                        <span class="font-medium">es_AR:</span> Spanish
                    </div>
                    <div class="text-sm">
                        <span class="font-medium">fr:</span> French
                    </div>
                    <div class="text-sm">
                        <span class="font-medium">de:</span> German
                    </div>
                    <div class="text-sm">
                        <span class="font-medium">it:</span> Italian
                    </div>
                    <div class="text-sm">
                        <span class="font-medium">nl:</span> Dutch
                    </div>
                    <div class="text-sm">
                        <span class="font-medium">ro:</span> Romanian
                    </div>
                    <div class="text-sm">
                        <span class="font-medium">gr:</span> Greek
                    </div>
                    <div class="text-sm">
                        <span class="font-medium">sk:</span> Slovak
                    </div>
                    <div class="text-sm">
                        <span class="font-medium">lv:</span> Latvian
                    </div>
                    <div class="text-sm">
                        <span class="font-medium">bg:</span> Bulgarian
                    </div>
                    <div class="text-sm">
                        <span class="font-medium">fi:</span> Finnish
                    </div>
                    <div class="text-sm">
                        <span class="font-medium">al:</span> Albanian
                    </div>
                    <div class="text-sm">
                        <span class="font-medium">ca:</span> Catalan
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Show loading state on form submit
        document.getElementById('uploadForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const spinner = document.getElementById('spinner');
            
            btn.disabled = true;
            btnText.textContent = 'Translating...';
            spinner.classList.remove('hidden');
        });

        // File size validation
        document.getElementById('csv_file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const maxSize = 10 * 1024 * 1024; // 10MB
                if (file.size > maxSize) {
                    alert('File size exceeds 10MB limit');
                    e.target.value = '';
                }
                
                // Check file type
                const allowedTypes = ['text/csv', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
                const fileType = file.type;
                const fileName = file.name.toLowerCase();
                
                if (!allowedTypes.includes(fileType) && !fileName.endsWith('.csv') && !fileName.endsWith('.xlsx')) {
                    alert('Please select a CSV or XLSX file');
                    e.target.value = '';
                }
            }
        });
    </script>
</body>
</html>

