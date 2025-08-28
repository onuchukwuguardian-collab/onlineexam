@extends('layouts.admin')

@section('title', 'Violation Details - Security Management')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header Section -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="{{ route('admin.security.index') }}" class="flex-shrink-0 w-10 h-10 bg-gray-100 hover:bg-gray-200 rounded-lg flex items-center justify-center transition-colors duration-200">
                        <i class="fas fa-arrow-left text-gray-600"></i>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Security Violation Details</h1>
                        <p class="text-sm text-gray-600">Detailed information about this security incident</p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    @if($banRecord)
                    <a href="{{ route('admin.security.ban-details', $banRecord) }}" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                        <i class="fas fa-ban mr-2"></i>
                        View Ban Record
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="px-6 py-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Violation Information -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Violation Summary Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-start space-x-4 mb-6">
                        <div class="flex-shrink-0">
                            @if($violation->violation_type === 'tab_switch')
                                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-external-link-alt text-red-600 text-xl"></i>
                                </div>
                            @else
                                <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
                                </div>
                            @endif
                        </div>
                        <div class="flex-1">
                            <h2 class="text-xl font-bold text-gray-900 mb-2">{{ ucfirst(str_replace('_', ' ', $violation->violation_type)) }}</h2>
                            <p class="text-gray-600 mb-3">{{ $violation->description }}</p>
                            <div class="flex flex-wrap items-center gap-2">
                                @if(isset($violation->metadata['violation_count']))
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                        @if($violation->metadata['violation_count'] >= 3) bg-red-100 text-red-800
                                        @elseif($violation->metadata['violation_count'] == 2) bg-orange-100 text-orange-800
                                        @else bg-yellow-100 text-yellow-800 @endif">
                                        Strike {{ $violation->metadata['violation_count'] }}
                                    </span>
                                @endif
                                @if($banRecord)
                                    <span class="inline-flex items-center px-2 py-1 bg-red-100 text-red-800 text-xs font-medium rounded-full">
                                        <i class="fas fa-ban mr-1"></i>
                                        Student Banned
                                    </span>
                                @endif
                                <span class="inline-flex items-center px-2 py-1 bg-gray-100 text-gray-800 text-xs font-medium rounded-full">
                                    <i class="fas fa-clock mr-1"></i>
                                    {{ $violation->occurred_at->format('M j, Y g:i A') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Student & Exam Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Student</label>
                                <div class="bg-gray-50 rounded-lg px-3 py-2">
                                    <p class="text-sm font-medium text-gray-900">{{ $violation->user->name ?? 'Unknown User' }}</p>
                                    <p class="text-xs text-gray-600">{{ $violation->user->unique_id ?? 'Unknown ID' }} • {{ $violation->user->email ?? 'Unknown Email' }}</p>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                                <p class="text-sm text-gray-900 bg-gray-50 rounded-lg px-3 py-2">{{ $violation->subject->name ?? 'Unknown Subject' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Exam Session</label>
                                <p class="text-sm text-gray-900 bg-gray-50 rounded-lg px-3 py-2">
                                    <i class="fas fa-clipboard-list mr-2 text-gray-400"></i>
                                    Session #{{ $violation->exam_session_id }}
                                </p>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">IP Address</label>
                                <p class="text-sm text-gray-900 bg-gray-50 rounded-lg px-3 py-2 font-mono">{{ $violation->ip_address }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Violation Time</label>
                                <p class="text-sm text-gray-900 bg-gray-50 rounded-lg px-3 py-2">
                                    <i class="fas fa-clock mr-2 text-gray-400"></i>
                                    {{ $violation->occurred_at->format('F j, Y \a\t g:i:s A') }}
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Days Ago</label>
                                <p class="text-sm text-gray-900 bg-gray-50 rounded-lg px-3 py-2">
                                    <i class="fas fa-calendar mr-2 text-gray-400"></i>
                                    {{ $violation->occurred_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Technical Details -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <i class="fas fa-cogs mr-2 text-blue-600"></i>
                            Technical Details
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Browser Information</label>
                                <div class="bg-gray-50 rounded-lg px-3 py-2">
                                    <p class="text-xs text-gray-700 break-all">{{ $violation->user_agent }}</p>
                                </div>
                            </div>
                            @if(isset($violation->metadata['screen_resolution']))
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Screen Resolution</label>
                                <p class="text-sm text-gray-900 bg-gray-50 rounded-lg px-3 py-2">
                                    <i class="fas fa-desktop mr-2 text-gray-400"></i>
                                    {{ $violation->metadata['screen_resolution'] }}
                                </p>
                            </div>
                            @endif
                            @if(isset($violation->metadata['window_size']))
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Window Size</label>
                                <p class="text-sm text-gray-900 bg-gray-50 rounded-lg px-3 py-2">
                                    <i class="fas fa-window-maximize mr-2 text-gray-400"></i>
                                    {{ $violation->metadata['window_size'] }}
                                </p>
                            </div>
                            @endif
                            @if(isset($violation->metadata['timestamp']))
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Client Timestamp</label>
                                <p class="text-sm text-gray-900 bg-gray-50 rounded-lg px-3 py-2 font-mono">{{ $violation->metadata['timestamp'] }}</p>
                            </div>
                            @endif
                        </div>
                        
                        @if($violation->metadata && count($violation->metadata) > 0)
                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Raw Metadata (JSON)</label>
                            <div class="bg-gray-900 text-green-400 rounded-lg p-4 overflow-x-auto">
                                <pre class="text-xs">{{ json_encode($violation->metadata, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Related Violations -->
                @if(count($relatedViolations) > 1)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <i class="fas fa-list mr-2 text-orange-600"></i>
                            All Violations by This Student ({{ count($relatedViolations) }} total)
                        </h3>
                    </div>
                    <div class="divide-y divide-gray-200">
                        @foreach($relatedViolations as $related)
                        <div class="p-4 {{ $related->id === $violation->id ? 'bg-blue-50 border-l-4 border-blue-500' : 'hover:bg-gray-50' }} transition-colors duration-200">
                            <div class="flex items-start justify-between">
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 {{ $related->id === $violation->id ? 'bg-blue-100' : 'bg-red-100' }} rounded-full flex items-center justify-center">
                                            @if($related->id === $violation->id)
                                                <i class="fas fa-arrow-right text-blue-600 text-xs"></i>
                                            @else
                                                <span class="text-red-600 text-xs font-bold">{{ $loop->iteration }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm {{ $related->id === $violation->id ? 'font-medium text-blue-900' : 'text-gray-900' }}">
                                            {{ $related->description }}
                                        </p>
                                        <div class="flex items-center space-x-4 mt-1 text-xs text-gray-500">
                                            <span><i class="fas fa-clock mr-1"></i>{{ $related->occurred_at->format('M j, g:i A') }}</span>
                                            <span><i class="fas fa-globe mr-1"></i>{{ $related->ip_address }}</span>
                                            @if(isset($related->metadata['violation_count']))
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                                    @if($related->metadata['violation_count'] >= 3) bg-red-100 text-red-800
                                                    @elseif($related->metadata['violation_count'] == 2) bg-orange-100 text-orange-800
                                                    @else bg-yellow-100 text-yellow-800 @endif">
                                                    Strike {{ $related->metadata['violation_count'] }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @if($related->id !== $violation->id)
                                <a href="{{ route('admin.security.violation-details', $related) }}" 
                                   class="text-xs text-blue-600 hover:text-blue-800">View</a>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <!-- Actions Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-info-circle mr-2 text-gray-600"></i>
                        Violation Summary
                    </h3>
                    
                    <div class="space-y-4">
                        <!-- Severity Indicator -->
                        <div class="p-4 rounded-lg {{ isset($violation->metadata['violation_count']) && $violation->metadata['violation_count'] >= 3 ? 'bg-red-50 border border-red-200' : (isset($violation->metadata['violation_count']) && $violation->metadata['violation_count'] == 2 ? 'bg-orange-50 border border-orange-200' : 'bg-yellow-50 border border-yellow-200') }}">
                            <h4 class="text-sm font-medium {{ isset($violation->metadata['violation_count']) && $violation->metadata['violation_count'] >= 3 ? 'text-red-800' : (isset($violation->metadata['violation_count']) && $violation->metadata['violation_count'] == 2 ? 'text-orange-800' : 'text-yellow-800') }} mb-2">
                                @if(isset($violation->metadata['violation_count']) && $violation->metadata['violation_count'] >= 3)
                                    <i class="fas fa-ban mr-2"></i>Critical Violation
                                @elseif(isset($violation->metadata['violation_count']) && $violation->metadata['violation_count'] == 2)
                                    <i class="fas fa-exclamation-triangle mr-2"></i>Final Warning
                                @else
                                    <i class="fas fa-exclamation-circle mr-2"></i>First Warning
                                @endif
                            </h4>
                            <p class="text-xs {{ isset($violation->metadata['violation_count']) && $violation->metadata['violation_count'] >= 3 ? 'text-red-700' : (isset($violation->metadata['violation_count']) && $violation->metadata['violation_count'] == 2 ? 'text-orange-700' : 'text-yellow-700') }}">
                                @if(isset($violation->metadata['violation_count']) && $violation->metadata['violation_count'] >= 3)
                                    This was the third violation, resulting in automatic ban.
                                @elseif(isset($violation->metadata['violation_count']) && $violation->metadata['violation_count'] == 2)
                                    This was the second violation. Student was warned that one more would result in ban.
                                @else
                                    This was the first violation. Student received warning.
                                @endif
                            </p>
                        </div>

                        <!-- Current Status -->
                        @if($banRecord)
                        <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                            <h4 class="text-sm font-medium text-red-800 mb-2">
                                <i class="fas fa-user-slash mr-2"></i>
                                Student Status: BANNED
                            </h4>
                            <p class="text-xs text-red-700 mb-3">Student is currently banned from {{ $violation->subject->name ?? 'this subject' }}.</p>
                            <a href="{{ route('admin.security.ban-details', $banRecord) }}" 
                               class="w-full inline-flex items-center justify-center px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded-lg transition-colors duration-200">
                                <i class="fas fa-unlock-alt mr-2"></i>
                                Manage Ban
                            </a>
                        </div>
                        @else
                        <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                            <h4 class="text-sm font-medium text-green-800 mb-2">
                                <i class="fas fa-check-circle mr-2"></i>
                                Student Status: Active
                            </h4>
                            <p class="text-xs text-green-700">Student can still take exams despite this violation.</p>
                        </div>
                        @endif

                        <!-- Quick Stats -->
                        <div class="pt-4 border-t border-gray-200">
                            <h4 class="text-sm font-medium text-gray-700 mb-3">Violation Stats</h4>
                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-600">Violation ID</span>
                                    <span class="font-mono text-gray-900">#{{ $violation->id }}</span>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-600">Strike Number</span>
                                    <span class="font-medium text-gray-900">{{ $violation->metadata['violation_count'] ?? 'Unknown' }}</span>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-600">Total Violations</span>
                                    <span class="font-medium text-red-600">{{ count($relatedViolations) }}</span>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-600">Subject</span>
                                    <span class="font-medium text-gray-900 text-right">{{ $violation->subject->name ?? 'Unknown' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection