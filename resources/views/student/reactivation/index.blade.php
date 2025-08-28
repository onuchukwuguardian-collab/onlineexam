@extends('layouts.student_app')

@section('title', 'Reactivation Requests')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Subject Reactivation</h1>
                    <p class="text-gray-600 mt-1">Request reactivation for subjects you've been banned from</p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-gray-500">Total Banned Subjects</div>
                    <div class="text-2xl font-bold text-red-600">{{ $bannedSubjects->count() }}</div>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    {{ session('error') }}
                </div>
            </div>
        @endif

        <!-- Banned Subjects -->
        @if($bannedSubjects->count() > 0)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-ban text-red-500 mr-2"></i>
                    Banned Subjects
                </h2>
                
                <div class="space-y-4">
                    @foreach($bannedSubjects as $ban)
                        <div class="border border-red-200 rounded-lg p-4 bg-red-50">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900">{{ $ban->subject->name }}</h3>
                                    <p class="text-sm text-gray-600 mt-1">{{ $ban->ban_reason }}</p>
                                    <div class="flex items-center space-x-4 mt-2 text-xs text-gray-500">
                                        <span><i class="fas fa-calendar mr-1"></i>Banned: {{ $ban->banned_at->format('M j, Y g:i A') }}</span>
                                        <span><i class="fas fa-exclamation-circle mr-1"></i>Violations: {{ $ban->total_violations }}</span>
                                        @if($ban->ban_count > 1)
                                            <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs">
                                                <i class="fas fa-redo mr-1"></i>Ban #{{ $ban->ban_count }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="ml-4">
                                    @php
                                        $existingRequest = $existingRequests->where('subject_id', $ban->subject_id)->first();
                                    @endphp
                                    
                                    @if($existingRequest)
                                        @if($existingRequest->status === 'pending')
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                <i class="fas fa-clock mr-1"></i>
                                                Pending Review
                                            </span>
                                        @elseif($existingRequest->status === 'approved')
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-check mr-1"></i>
                                                Approved
                                            </span>
                                        @elseif($existingRequest->status === 'rejected')
                                            <a href="{{ route('user.student.reactivation.create', $ban->subject_id) }}" 
                                               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                                <i class="fas fa-redo mr-2"></i>
                                                Request Again
                                            </a>
                                        @endif
                                    @else
                                        <a href="{{ route('user.student.reactivation.create', $ban->subject_id) }}" 
                                           class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                            <i class="fas fa-paper-plane mr-2"></i>
                                            Request Reactivation
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-6">
                <div class="text-center">
                    <i class="fas fa-check-circle text-green-500 text-4xl mb-4"></i>
                    <h3 class="text-lg font-semibold text-green-900 mb-2">No Active Bans</h3>
                    <p class="text-green-700">You are not currently banned from any subjects. You can take all available exams.</p>
                </div>
            </div>
        @endif

        <!-- Reactivation Request History -->
        @if($existingRequests->count() > 0)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-history text-blue-500 mr-2"></i>
                    Request History
                </h2>
                
                <div class="space-y-4">
                    @foreach($existingRequests as $request)
                        <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3">
                                        <h4 class="font-medium text-gray-900">{{ $request->subject->name }}</h4>
                                        @if($request->status === 'pending')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                <i class="fas fa-clock mr-1"></i>Pending
                                            </span>
                                        @elseif($request->status === 'approved')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-check mr-1"></i>Approved
                                            </span>
                                        @elseif($request->status === 'rejected')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <i class="fas fa-times mr-1"></i>Rejected
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-gray-600 mt-1">{{ Str::limit($request->request_message, 100) }}</p>
                                    <div class="flex items-center space-x-4 mt-2 text-xs text-gray-500">
                                        <span><i class="fas fa-calendar mr-1"></i>{{ $request->requested_at->format('M j, Y g:i A') }}</span>
                                        @if($request->reviewed_at)
                                            <span><i class="fas fa-user-check mr-1"></i>Reviewed: {{ $request->reviewed_at->format('M j, Y') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <a href="{{ route('user.student.reactivation.show', $request) }}" 
                                       class="inline-flex items-center px-3 py-1 text-sm text-blue-600 hover:text-blue-800 transition-colors">
                                        <i class="fas fa-eye mr-1"></i>
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection