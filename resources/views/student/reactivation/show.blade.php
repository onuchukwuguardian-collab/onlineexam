@extends('layouts.student_app')

@section('title', 'Reactivation Request Details')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <a href="{{ route('user.student.reactivation.index') }}" 
                       class="text-gray-500 hover:text-gray-700 mr-4">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Reactivation Request</h1>
                        <p class="text-gray-600 mt-1">Subject: <span class="font-semibold">{{ $request->subject->name }}</span></p>
                    </div>
                </div>
                <div class="text-right">
                    @if($request->status === 'pending')
                        <span class="inline-flex items-center px-3 py-2 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                            <i class="fas fa-clock mr-2"></i>
                            Pending Review
                        </span>
                    @elseif($request->status === 'approved')
                        <span class="inline-flex items-center px-3 py-2 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            <i class="fas fa-check mr-2"></i>
                            Approved
                        </span>
                    @elseif($request->status === 'rejected')
                        <span class="inline-flex items-center px-3 py-2 rounded-full text-sm font-medium bg-red-100 text-red-800">
                            <i class="fas fa-times mr-2"></i>
                            Rejected
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Request Details -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                Request Information
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Request Date</h3>
                    <p class="text-gray-900">{{ $request->requested_at->format('M j, Y g:i A') }}</p>
                </div>
                
                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Status</h3>
                    <div>
                        @if($request->status === 'pending')
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                <i class="fas fa-clock mr-1"></i>Pending Review
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
                </div>
                
                @if($request->reviewed_at)
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Review Date</h3>
                        <p class="text-gray-900">{{ $request->reviewed_at->format('M j, Y g:i A') }}</p>
                    </div>
                    
                    @if($request->reviewedByAdmin)
                        <div>
                            <h3 class="text-sm font-medium text-gray-700 mb-2">Reviewed By</h3>
                            <p class="text-gray-900">{{ $request->reviewedByAdmin->name }}</p>
                        </div>
                    @endif
                @endif
            </div>
        </div>

        <!-- Your Request Message -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-comment text-blue-500 mr-2"></i>
                Your Request Message
            </h2>
            
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-gray-800 whitespace-pre-wrap">{{ $request->request_message }}</p>
            </div>
        </div>

        <!-- Admin Response -->
        @if($request->admin_response)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-reply text-green-500 mr-2"></i>
                    Admin Response
                </h2>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-blue-800 whitespace-pre-wrap">{{ $request->admin_response }}</p>
                </div>
            </div>
        @endif

        <!-- Ban Details -->
        @if($request->examBan)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-ban text-red-500 mr-2"></i>
                    Related Ban Information
                </h2>
                
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="font-medium text-red-800">Ban Date:</span>
                            <div class="text-red-700">{{ $request->examBan->banned_at->format('M j, Y g:i A') }}</div>
                        </div>
                        <div>
                            <span class="font-medium text-red-800">Total Violations:</span>
                            <div class="text-red-700">{{ $request->examBan->total_violations }}</div>
                        </div>
                        <div class="md:col-span-2">
                            <span class="font-medium text-red-800">Ban Reason:</span>
                            <div class="text-red-700 mt-1">{{ $request->examBan->ban_reason }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Action Buttons -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <a href="{{ route('user.student.reactivation.index') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 bg-white rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Requests
                </a>
                
                @if($request->status === 'rejected')
                    <a href="{{ route('user.student.reactivation.create', $request->subject_id) }}" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-redo mr-2"></i>
                        Submit New Request
                    </a>
                @elseif($request->status === 'approved')
                    <div class="text-green-600 font-medium">
                        <i class="fas fa-check-circle mr-2"></i>
                        You can now retake this exam
                    </div>
                @else
                    <div class="text-yellow-600 font-medium">
                        <i class="fas fa-clock mr-2"></i>
                        Waiting for admin review
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection