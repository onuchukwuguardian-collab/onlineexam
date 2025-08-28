@extends('layouts.student_app')

@section('title', 'Request Reactivation - ' . $subject->name)

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-2xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <div class="flex items-center">
                <a href="{{ route('user.student.reactivation.index') }}" 
                   class="text-gray-500 hover:text-gray-700 mr-4">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Request Reactivation</h1>
                    <p class="text-gray-600 mt-1">Subject: <span class="font-semibold">{{ $subject->name }}</span></p>
                </div>
            </div>
        </div>

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                <div class="flex items-center mb-2">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span class="font-medium">Please correct the following errors:</span>
                </div>
                <ul class="list-disc list-inside text-sm">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Ban Details -->
        @if($ban)
            <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">
                <h2 class="text-lg font-semibold text-red-900 mb-3">
                    <i class="fas fa-ban mr-2"></i>
                    Current Ban Details
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="font-medium text-red-800">Ban Date:</span>
                        <div class="text-red-700">{{ $ban->banned_at->format('M j, Y g:i A') }}</div>
                    </div>
                    <div>
                        <span class="font-medium text-red-800">Total Violations:</span>
                        <div class="text-red-700">{{ $ban->total_violations }}</div>
                    </div>
                    <div class="md:col-span-2">
                        <span class="font-medium text-red-800">Ban Reason:</span>
                        <div class="text-red-700 mt-1">{{ $ban->ban_reason }}</div>
                    </div>
                    @if($ban->ban_count > 1)
                        <div class="md:col-span-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-200 text-red-800">
                                <i class="fas fa-redo mr-1"></i>
                                This is ban #{{ $ban->ban_count }} for this subject
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- Reactivation Request Form -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-paper-plane text-blue-500 mr-2"></i>
                Reactivation Request Form
            </h2>

            <form action="{{ route('user.student.reactivation.store') }}" method="POST" class="space-y-6">
                @csrf
                <input type="hidden" name="subject_id" value="{{ $subject->id }}">

                <!-- Guidelines -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="font-medium text-blue-900 mb-2">
                        <i class="fas fa-info-circle mr-2"></i>
                        Request Guidelines
                    </h3>
                    <ul class="text-sm text-blue-800 space-y-1">
                        <li>• Be honest and specific about what happened</li>
                        <li>• Explain why you believe you should be given another chance</li>
                        <li>• Describe what you'll do differently to avoid future violations</li>
                        <li>• Minimum 50 characters, maximum 1000 characters</li>
                        <li>• Use professional language and proper grammar</li>
                    </ul>
                </div>

                <!-- Request Message -->
                <div>
                    <label for="request_message" class="block text-sm font-medium text-gray-700 mb-2">
                        Reactivation Request Message <span class="text-red-500">*</span>
                    </label>
                    <textarea 
                        id="request_message" 
                        name="request_message" 
                        rows="8" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"
                        placeholder="Please explain why you should be reactivated for this subject. Be specific about what happened and how you plan to avoid future violations..."
                        required
                        minlength="50"
                        maxlength="1000">{{ old('request_message') }}</textarea>
                    
                    <div class="flex justify-between items-center mt-2">
                        <div class="text-xs text-gray-500">
                            Minimum 50 characters required
                        </div>
                        <div class="text-xs text-gray-500">
                            <span id="char-count">0</span> / 1000 characters
                        </div>
                    </div>
                </div>

                <!-- Important Notice -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <h3 class="font-medium text-yellow-900 mb-2">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Important Notice
                    </h3>
                    <div class="text-sm text-yellow-800 space-y-1">
                        <p>• Your request will be reviewed by an administrator</p>
                        <p>• You can only submit one request per subject at a time</p>
                        <p>• If rejected, you may submit a new request after addressing the feedback</p>
                        <p>• False or misleading information may result in permanent bans</p>
                        @if($ban && $ban->ban_count > 1)
                            <p class="font-medium text-yellow-900">• This is a repeat offense - approval is less likely</p>
                        @endif
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                    <a href="{{ route('user.student.reactivation.index') }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 bg-white rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fas fa-times mr-2"></i>
                        Cancel
                    </a>
                    
                    <button type="submit" 
                            class="inline-flex items-center px-6 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.getElementById('request_message');
    const charCount = document.getElementById('char-count');
    
    function updateCharCount() {
        const count = textarea.value.length;
        charCount.textContent = count;
        
        if (count < 50) {
            charCount.className = 'text-red-500 font-medium';
        } else if (count > 900) {
            charCount.className = 'text-yellow-600 font-medium';
        } else {
            charCount.className = 'text-green-600 font-medium';
        }
    }
    
    textarea.addEventListener('input', updateCharCount);
    updateCharCount(); // Initial count
});
</script>
@endsection