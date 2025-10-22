@extends('layouts.app')

@section('title', 'Admin - Audio Files')

@section('content')
<div class="px-4 py-6 sm:px-0">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Audio Files</h1>
                    <p class="text-gray-600">Overview of all audio files</p>
                </div>
                <a href="{{ route('admin.dashboard') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white shadow-lg rounded-xl p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All statuses</option>
                        <option value="uploaded">Uploaded</option>
                        <option value="processing">Processing</option>
                        <option value="completed">Completed</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Language</label>
                    <select class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All languages</option>
                        <option value="en">English</option>
                        <option value="nl">Dutch</option>
                        <option value="de">German</option>
                        <option value="fr">French</option>
                        <option value="es">Spanish</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">User</label>
                    <input type="text" placeholder="Search user..." class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex items-end">
                    <button class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-search mr-2"></i>
                        Filter
                    </button>
                </div>
            </div>
        </div>

        <!-- Audio Files Table -->
        <div class="bg-white shadow-lg rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                File
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                User
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Languages
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Size
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($audioFiles as $audioFile)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                <i class="fas fa-file-audio text-blue-600"></i>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $audioFile->original_filename }}</div>
                                            <div class="text-sm text-gray-500">{{ $audioFile->translations_count }} translations</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $audioFile->user->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $audioFile->user->email }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center space-x-2">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ strtoupper($audioFile->source_language) }}
                                        </span>
                                        <i class="fas fa-arrow-right text-gray-400"></i>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            {{ strtoupper($audioFile->target_language) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($audioFile->status === 'completed') bg-green-100 text-green-800
                                        @elseif($audioFile->status === 'processing') bg-yellow-100 text-yellow-800
                                        @elseif($audioFile->status === 'failed') bg-red-100 text-red-800
                                        @elseif($audioFile->status === 'uploaded') bg-blue-100 text-blue-800
                                        @else bg-gray-100 text-gray-800 @endif">
                                        @if($audioFile->status === 'completed')
                                            <i class="fas fa-check-circle mr-1"></i>
                                        @elseif($audioFile->status === 'processing')
                                            <i class="fas fa-spinner fa-spin mr-1"></i>
                                        @elseif($audioFile->status === 'failed')
                                            <i class="fas fa-times-circle mr-1"></i>
                                        @elseif($audioFile->status === 'uploaded')
                                            <i class="fas fa-upload mr-1"></i>
                                        @endif
                                        {{ ucfirst($audioFile->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ number_format($audioFile->file_size / 1024, 1) }} KB</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $audioFile->created_at->format('d-m-Y') }}</div>
                                    <div class="text-sm text-gray-500">{{ $audioFile->created_at->format('H:i') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="{{ route('audio.show', $audioFile->id) }}" class="text-blue-600 hover:text-blue-900" title="View details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($audioFile->status === 'completed')
                                            <a href="{{ route('audio.download', $audioFile->id) }}" class="text-green-600 hover:text-green-900" title="Download">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        @endif
                                        <button class="text-red-600 hover:text-red-900" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="text-gray-500">
                                        <i class="fas fa-file-audio text-4xl mb-4"></i>
                                        <p class="text-lg font-medium">No audio files found</p>
                                        <p class="text-sm">No audio files have been uploaded yet.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($audioFiles->hasPages())
                <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                    {{ $audioFiles->links() }}
                </div>
            @endif
        </div>

        <!-- Summary Stats -->
        <div class="mt-8 grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white shadow-lg rounded-xl p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-file-audio text-blue-500 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Files</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $audioFiles->total() }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white shadow-lg rounded-xl p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-500 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Completed</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $audioFiles->where('status', 'completed')->count() }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white shadow-lg rounded-xl p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-spinner text-yellow-500 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Processing</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $audioFiles->where('status', 'processing')->count() }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white shadow-lg rounded-xl p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-times-circle text-red-500 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Failed</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $audioFiles->where('status', 'failed')->count() }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
