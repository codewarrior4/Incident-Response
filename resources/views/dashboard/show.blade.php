@extends('layouts.app')

@section('title', 'Incident Details')

@section('content')
<div x-data="{ 
    status: '{{ $incident->status->value }}',
    updating: false 
}">
    <!-- Back Button -->
    <div class="mb-6">
        <a href="{{ route('dashboard.index') }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
            ← Back to Dashboard
        </a>
    </div>

    <!-- Incident Header -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
        <div class="flex justify-between items-start">
            <div class="flex-1">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ $incident->title }}</h1>
                <div class="flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                    <span>Service: <span class="font-medium text-gray-900 dark:text-white">{{ $incident->service }}</span></span>
                    <span>•</span>
                    <span>Created: {{ $incident->created_at->format('M d, Y H:i') }}</span>
                    <span>•</span>
                    <span>Occurrences: <span class="font-medium text-gray-900 dark:text-white">{{ $incident->occurrences_count + 1 }}</span></span>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                @php
                    $severityColors = [
                        'low' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                        'medium' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                        'high' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
                        'critical' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                    ];
                @endphp
                <span class="px-3 py-1 text-sm font-semibold rounded-full {{ $severityColors[$incident->severity->value] ?? 'bg-gray-100 text-gray-800' }}">
                    {{ ucfirst($incident->severity->value) }}
                </span>
            </div>
        </div>
    </div>

    <!-- Status Update Form -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Status</h2>
        <form 
            @submit.prevent="
                updating = true;
                fetch('{{ route('dashboard.show', $incident) }}', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ status: status })
                })
                .then(response => response.json())
                .then(() => {
                    updating = false;
                    window.location.reload();
                })
                .catch(() => {
                    updating = false;
                    alert('Failed to update status');
                });
            "
            class="flex items-center space-x-4"
        >
            <select 
                x-model="status"
                class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
                <option value="open">Open</option>
                <option value="investigating">Investigating</option>
                <option value="resolved">Resolved</option>
            </select>
            <button 
                type="submit"
                :disabled="updating"
                class="bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-400 text-white font-medium py-2 px-4 rounded-md"
            >
                <span x-show="!updating">Update Status</span>
                <span x-show="updating">Updating...</span>
            </button>
        </form>
    </div>

    <!-- Error Message -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Error Message</h2>
        <pre class="bg-gray-50 dark:bg-gray-900 p-4 rounded-md overflow-x-auto text-sm text-gray-800 dark:text-gray-200">{{ $incident->message }}</pre>
    </div>

    <!-- Analysis Section -->
    @if($incident->analysis)
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <div class="flex justify-between items-start mb-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Analysis</h2>
                <div class="flex items-center space-x-2">
                    @if($incident->analysis->ai_generated)
                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300">
                            AI Generated
                        </span>
                    @else
                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                            Rule-Based
                        </span>
                    @endif
                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                        Confidence: {{ $incident->analysis->confidence_score }}%
                    </span>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Root Cause</h3>
                    <p class="text-gray-900 dark:text-white">{{ $incident->analysis->root_cause }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Suggested Fix</h3>
                    <p class="text-gray-900 dark:text-white">{{ $incident->analysis->suggested_fix }}</p>
                </div>
            </div>
        </div>
    @else
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6 mb-6">
            <p class="text-yellow-800 dark:text-yellow-300">Analysis is being processed. Please check back shortly.</p>
        </div>
    @endif

    <!-- Occurrences Timeline -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Occurrences Timeline</h2>
        
        @if($incident->occurrences->count() > 0)
            <div class="space-y-4">
                <!-- Original Incident -->
                <div class="border-l-4 border-indigo-500 pl-4 py-2">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">Original Incident</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $incident->created_at->format('M d, Y H:i:s') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Subsequent Occurrences -->
                @foreach($incident->occurrences as $occurrence)
                    <div class="border-l-4 border-gray-300 dark:border-gray-600 pl-4 py-2">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Occurrence #{{ $loop->iteration }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">{{ $occurrence->created_at->format('M d, Y H:i:s') }}</p>
                                @if($occurrence->context)
                                    <details class="text-xs">
                                        <summary class="cursor-pointer text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                                            View Context
                                        </summary>
                                        <pre class="mt-2 bg-gray-50 dark:bg-gray-900 p-2 rounded text-gray-800 dark:text-gray-200 overflow-x-auto">{{ json_encode($occurrence->context, JSON_PRETTY_PRINT) }}</pre>
                                    </details>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-500 dark:text-gray-400">This incident has occurred once.</p>
        @endif
    </div>
</div>
@endsection
