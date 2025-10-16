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
                            <p class="mt-1 text-sm text-gray-500">Maximum file size: 100MB. Supports CSV and XLSX files.</p>
                        </div>

                        <!-- Language Selector -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Select Languages to Translate (Optional)
                            </label>
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-3">
                                <p class="text-sm text-yellow-800">
                                    <strong>Optional:</strong> Select specific languages to translate. If none selected, all empty columns will be translated.
                                </p>
                            </div>
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 max-h-48 overflow-y-auto border border-gray-300 rounded-lg p-4">
                                <!-- Preset Languages Only: EN → ES → DE → FR → IT → NL → RO → EL → SQ → SK → LV → BG → FI → CA -->
                                <label class="flex items-center">
                                    <input type="checkbox" name="languages[]" value="es" class="mr-2">
                                    <span class="text-sm">Spanish (es)</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="languages[]" value="de" class="mr-2">
                                    <span class="text-sm">German (de)</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="languages[]" value="fr" class="mr-2">
                                    <span class="text-sm">French (fr)</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="languages[]" value="it" class="mr-2">
                                    <span class="text-sm">Italian (it)</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="languages[]" value="nl" class="mr-2">
                                    <span class="text-sm">Dutch (nl)</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="languages[]" value="ro" class="mr-2">
                                    <span class="text-sm">Romanian (ro)</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="languages[]" value="el" class="mr-2">
                                    <span class="text-sm">Greek (el)</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="languages[]" value="sq" class="mr-2">
                                    <span class="text-sm">Albanian (sq)</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="languages[]" value="sk" class="mr-2">
                                    <span class="text-sm">Slovak (sk)</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="languages[]" value="lv" class="mr-2">
                                    <span class="text-sm">Latvian (lv)</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="languages[]" value="bg" class="mr-2">
                                    <span class="text-sm">Bulgarian (bg)</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="languages[]" value="fi" class="mr-2">
                                    <span class="text-sm">Finnish (fi)</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="languages[]" value="ca" class="mr-2">
                                    <span class="text-sm">Catalan (ca)</span>
                                </label>
                            </div>
                            <div class="mt-2 flex gap-2">
                                <button type="button" id="selectAll" class="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200">Select All</button>
                                <button type="button" id="selectNone" class="text-xs px-2 py-1 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">Select None</button>
                            </div>
                        </div>

            <!-- Background Processing Notice -->
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 mb-6">
                <h3 class="text-sm font-semibold text-purple-900 mb-2">⚡ Background Processing:</h3>
                <ul class="text-sm text-purple-800 space-y-1">
                    <li>• Files are processed in the background - <strong>no timeout issues!</strong></li>
                    <li>• Upload files up to <strong>100MB</strong> (previously 10MB)</li>
                    <li>• Perfect for <strong>huge files</strong> with 100,000+ rows</li>
                    <li>• Track progress in real-time on the status page</li>
                    <li>• Close your browser - come back later to download</li>
                </ul>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-blue-900 mb-2">File Format Requirements:</h3>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li>• <strong>CSV:</strong> Delimiter: <strong>semicolon (;)</strong></li>
                    <li>• <strong>XLSX:</strong> Standard Excel format</li>
                    <li>• First column: <strong>en</strong> (English source text) - REQUIRED</li>
                    <li>• Other columns: target languages (es, de, fr, it, nl, ro, el, sq, sk, lv, bg, fi, ca)</li>
                    <li>• Only <strong>empty cells</strong> will be translated</li>
                    <li>• Existing translations will be preserved</li>
                    <li>• <strong>KEY column is optional</strong> - not required for translation</li>
                </ul>
            </div>

                        <!-- Smart Fallback Info -->
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <h3 class="text-sm font-semibold text-green-900 mb-2">🧠 Smart Fallback Mode:</h3>
                            <p class="text-sm text-green-800 mb-2">
                                <strong>If your file doesn't match the standard format, we'll automatically:</strong>
                            </p>
                            <ul class="text-sm text-green-800 space-y-1">
                                <li>• <strong>Detect the source language</strong> automatically</li>
                                <li>• <strong>Translate to preset languages</strong> in specific order</li>
                                <li>• <strong>Create separate sheets</strong> for each language</li>
                                <li>• <strong>Download as XLSX</strong> with multiple sheets in one file</li>
                            </ul>
                            <p class="text-xs text-green-700 mt-2">
                                <strong>Preset Order:</strong> EN → ES → DE → FR → IT → NL → RO → EL → SQ → SK → LV → BG → FI → CA
                            </p>
                            <p class="text-xs text-green-700 mt-1">
                                Perfect for any text file - just upload and let AI do the work! One file with multiple sheets.
                            </p>
                        </div>

                        <!-- Example Structure -->
                        <details class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <summary class="text-sm font-semibold text-gray-900 cursor-pointer">
                                View Example File Structure
                            </summary>
                            <div class="mt-3">
                                <pre class="text-xs bg-white p-3 rounded border border-gray-300 overflow-x-auto"><code>en;es;fr;de;it;pt;ru
Welcome;;;;
Hello world;;;;
Goodbye;;;;;</code></pre>
                                <p class="text-xs text-gray-600 mt-2">Same structure applies to both CSV and XLSX files. KEY column is optional.</p>
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
                                Large files are processed in the background. You'll be redirected to a status page.
                            </p>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Supported Languages -->
            <div class="mt-8 bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Available Translation Languages</h3>
                <p class="text-sm text-gray-600 mb-4">
                    <strong>Preset Order:</strong> EN → ES → DE → FR → IT → NL → RO → EL → SQ → SK → LV → BG → FI → CA
                </p>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                    <div class="text-sm">
                        <span class="font-medium">es:</span> Spanish
                    </div>
                    <div class="text-sm">
                        <span class="font-medium">de:</span> German
                    </div>
                    <div class="text-sm">
                        <span class="font-medium">fr:</span> French
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
                        <span class="font-medium">el:</span> Greek
                    </div>
                    <div class="text-sm">
                        <span class="font-medium">sq:</span> Albanian
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
                const maxSize = 100 * 1024 * 1024; // 100MB
                if (file.size > maxSize) {
                    alert('File size exceeds 100MB limit');
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

        // Language selector functionality
        document.getElementById('selectAll').addEventListener('click', function() {
            const checkboxes = document.querySelectorAll('input[name="languages[]"]');
            checkboxes.forEach(checkbox => checkbox.checked = true);
        });

        document.getElementById('selectNone').addEventListener('click', function() {
            const checkboxes = document.querySelectorAll('input[name="languages[]"]');
            checkboxes.forEach(checkbox => checkbox.checked = false);
        });
    </script>
</body>
</html>

