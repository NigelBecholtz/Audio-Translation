@extends('layouts.app')

@section('title', 'CSV Translations')

@section('content')
<div class="px-4 py-6 sm:px-0">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-white">CSV Translations</h1>
                    <p class="text-gray-300">Upload CSV files to automatically translate to multiple languages</p>
                </div>
                <div class="flex items-center gap-4">
                    <a href="{{ route('admin.dashboard') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="bg-green-500 text-white px-6 py-4 rounded-lg mb-6 shadow-lg">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-3 text-xl"></i>
                    <p class="font-medium">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-500 text-white px-6 py-4 rounded-lg mb-6 shadow-lg">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-3 text-xl"></i>
                    <p class="font-medium">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        <!-- Upload Section -->
        <div class="bg-white shadow-lg rounded-xl p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-900 mb-4">
                <i class="fas fa-upload mr-2 text-blue-500"></i>
                Upload CSV File
            </h2>
            
            <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <h3 class="font-semibold text-blue-900 mb-2">CSV Format Requirements:</h3>
                <ul class="text-sm text-blue-800 space-y-1 list-disc list-inside">
                    <li>First column: <strong>key</strong> (translation identifier)</li>
                    <li>Second column: <strong>en</strong> (English text to translate)</li>
                    <li>Remaining columns: target language codes (es_AR, fr, de, it, nl, ro, gr, sk, lv, bg, fi, al, ca, etc.)</li>
                    <li>Use semicolon (;) as delimiter</li>
                    <li>Maximum file size: 10MB</li>
                </ul>
            </div>

            <form id="csvUploadForm" action="{{ route('admin.csv-translations.upload') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                
                <div>
                    <label for="csv_file" class="block text-sm font-medium text-gray-700 mb-2">
                        Select CSV File
                    </label>
                    <input 
                        type="file" 
                        name="csv_file" 
                        id="csv_file" 
                        accept=".csv,.txt"
                        required
                        class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent px-3 py-2"
                    >
                    @error('csv_file')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <button 
                        type="submit" 
                        id="uploadBtn"
                        class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <i class="fas fa-language mr-2"></i>
                        <span id="uploadBtnText">Upload & Translate</span>
                    </button>
                    <p class="mt-2 text-sm text-gray-500">
                        <i class="fas fa-info-circle mr-1"></i>
                        Processing will happen in the background. Large files may take several minutes.
                    </p>
                </div>
            </form>

            <!-- Upload Progress -->
            <div id="uploadProgress" class="hidden mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-center">
                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 mr-3"></div>
                    <span class="text-blue-800 font-medium">Uploading file...</span>
                </div>
            </div>

            <!-- Success Message -->
            <div id="successMessage" class="hidden mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-600 mr-3"></i>
                    <span class="text-green-800 font-medium">File uploaded successfully! Processing in background...</span>
                </div>
            </div>

            <!-- Error Message -->
            <div id="errorMessage" class="hidden mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-600 mr-3"></i>
                    <span class="text-red-800 font-medium" id="errorText">Upload failed. Please try again.</span>
                </div>
            </div>
        </div>

        <!-- Previous Translations -->
        <div class="bg-white shadow-lg rounded-xl p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">
                <i class="fas fa-history mr-2 text-green-500"></i>
                Translation History
            </h2>

            @if(count($translationFiles) > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Filename
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Size
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Created
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($translationFiles as $file)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <i class="fas fa-file-csv text-green-500 mr-2"></i>
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $file['name'] }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ number_format($file['size'] / 1024, 2) }} KB
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ \Carbon\Carbon::createFromTimestamp($file['modified'])->format('d M Y H:i') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            Completed
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                        <a 
                                            href="{{ route('admin.csv-translations.download', $file['name']) }}" 
                                            class="inline-flex items-center text-blue-600 hover:text-blue-900 transition-colors"
                                        >
                                            <i class="fas fa-download mr-1"></i>
                                            Download
                                        </a>
                                        <form 
                                            action="{{ route('admin.csv-translations.delete', $file['name']) }}" 
                                            method="POST" 
                                            class="inline"
                                            onsubmit="return confirm('Are you sure you want to delete this file?')"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button 
                                                type="submit" 
                                                class="text-red-600 hover:text-red-900 transition-colors"
                                            >
                                                <i class="fas fa-trash mr-1"></i>
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12">
                    <i class="fas fa-inbox text-gray-300 text-6xl mb-4"></i>
                    <p class="text-gray-500 text-lg">No translation files yet</p>
                    <p class="text-gray-400 text-sm mt-2">Upload your first CSV file to get started</p>
                </div>
            @endif
        </div>

        <!-- Instructions Section -->
        <div class="mt-8 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-3">
                <i class="fas fa-lightbulb mr-2 text-yellow-500"></i>
                How It Works
            </h3>
            <ol class="space-y-2 text-sm text-gray-700">
                <li class="flex items-start">
                    <span class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 mt-0.5">1</span>
                    <span>Prepare your CSV file with columns: <strong>key</strong>, <strong>en</strong>, and target language codes</span>
                </li>
                <li class="flex items-start">
                    <span class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 mt-0.5">2</span>
                    <span>Upload the CSV file using the form above</span>
                </li>
                <li class="flex items-start">
                    <span class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 mt-0.5">3</span>
                    <span>The system will automatically translate all English texts to the target languages using AI</span>
                </li>
                <li class="flex items-start">
                    <span class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 mt-0.5">4</span>
                    <span>Download the translated CSV file from the history table below</span>
                </li>
            </ol>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('csvUploadForm');
    const uploadBtn = document.getElementById('uploadBtn');
    const uploadBtnText = document.getElementById('uploadBtnText');
    const uploadProgress = document.getElementById('uploadProgress');
    const successMessage = document.getElementById('successMessage');
    const errorMessage = document.getElementById('errorMessage');
    const errorText = document.getElementById('errorText');

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Show progress
        uploadProgress.classList.remove('hidden');
        successMessage.classList.add('hidden');
        errorMessage.classList.add('hidden');
        
        // Disable button
        uploadBtn.disabled = true;
        uploadBtnText.textContent = 'Uploading...';
        
        // Create FormData
        const formData = new FormData(form);
        
        // Submit via fetch
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => {
            if (response.ok) {
                return response.json();
            }
            throw new Error('Upload failed');
        })
        .then(data => {
            // Hide progress
            uploadProgress.classList.add('hidden');
            
            // Show success
            successMessage.classList.remove('hidden');
            
            // Reset form
            form.reset();
            
            // Re-enable button
            uploadBtn.disabled = false;
            uploadBtnText.textContent = 'Upload & Translate';
            
            // Reload page after 2 seconds to show new file
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        })
        .catch(error => {
            console.error('Upload error:', error);
            
            // Hide progress
            uploadProgress.classList.add('hidden');
            
            // Show error
            errorText.textContent = error.message || 'Upload failed. Please try again.';
            errorMessage.classList.remove('hidden');
            
            // Re-enable button
            uploadBtn.disabled = false;
            uploadBtnText.textContent = 'Upload & Translate';
        });
    });
});
</script>
@endsection

