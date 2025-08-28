@extends('layouts.admin')

@section('title', 'Ban Details - Security Management')

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
                        <h1 class="text-2xl font-bold text-gray-900">Student Ban Details</h1>
                        <p class="text-sm text-gray-600">Review violation history and manage ban status</p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    @if($ban->is_active)
                    <button onclick="showReactivateModal()" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                        <i class="fas fa-unlock-alt mr-2"></i>
                        Reactivate Student
                    </button>
                    @else
                    <div class="inline-flex items-center px-4 py-2 bg-green-100 text-green-800 text-sm font-medium rounded-lg">
                        <i class="fas fa-check-circle mr-2"></i>
                        Already Reactivated
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="px-6 py-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Student & Ban Information -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Student Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-start justify-between mb-6">
                        <div class="flex items-center space-x-4">
                            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-user-slash text-red-600 text-xl"></i>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-900">{{ $ban->user->name ?? 'Unknown Student' }}</h2>
                                <p class="text-gray-600">{{ $ban->user->unique_id ?? 'Unknown ID' }} • {{ $ban->user->email ?? 'Unknown Email' }}</p>
                                <div class="flex items-center space-x-4 mt-2">
                                    <span class="inline-flex items-center px-2 py-1 bg-red-100 text-red-800 text-xs font-medium rounded-full">
                                        <i class="fas fa-ban mr-1"></i>
                                        {{ $ban->is_active ? 'BANNED' : 'REACTIVATED' }}
                                    </span>
                                    @if($ban->is_permanent)
                                    <span class="inline-flex items-center px-2 py-1 bg-gray-100 text-gray-800 text-xs font-medium rounded-full">
                                        <i class="fas fa-infinity mr-1"></i>
                                        Permanent
                                    </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ban Details -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Subject Banned From</label>
                                <p class="text-sm text-gray-900 bg-gray-50 rounded-lg px-3 py-2">{{ $ban->subject->name ?? 'Unknown Subject' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Banned Date</label>
                                <p class="text-sm text-gray-900 bg-gray-50 rounded-lg px-3 py-2">
                                    <i class="fas fa-calendar mr-2 text-gray-400"></i>
                                    {{ $ban->banned_at->format('F j, Y \a\t g:i A') }}
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Total Violations</label>
                                <p class="text-sm text-gray-900 bg-gray-50 rounded-lg px-3 py-2">
                                    <i class="fas fa-exclamation-triangle mr-2 text-red-400"></i>
                                    {{ $ban->total_violations }} violations
                                </p>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ban Reason</label>
                                <div class="text-sm text-gray-900 bg-gray-50 rounded-lg px-3 py-2 max-h-24 overflow-y-auto">{{ $ban->ban_reason }}</div>
                            </div>
                            @if($ban->admin_notes)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Admin Notes</label>
                                <div class="text-sm text-gray-900 bg-blue-50 rounded-lg px-3 py-2 max-h-24 overflow-y-auto">{{ $ban->admin_notes }}</div>
                            </div>
                            @endif
                            @if(!$ban->is_active && $ban->reactivated_at)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Reactivated On</label>
                                <p class="text-sm text-gray-900 bg-green-50 rounded-lg px-3 py-2">
                                    <i class="fas fa-unlock mr-2 text-green-400"></i>
                                    {{ $ban->reactivated_at->format('F j, Y \a\t g:i A') }}
                                </p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Violation History -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <i class="fas fa-history mr-2 text-blue-600"></i>
                            Violation History ({{ count($violations) }} violations)
                        </h3>
                    </div>
                    <div class="divide-y divide-gray-200">
                        @forelse($violations as $violation)
                        <div class="p-6 hover:bg-gray-50 transition-colors duration-200">
                            <div class="flex items-start space-x-4">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                                        <span class="text-red-600 text-sm font-bold">{{ $loop->iteration }}</span>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="text-sm font-medium text-gray-900">{{ ucfirst(str_replace('_', ' ', $violation->violation_type)) }}</h4>
                                        <span class="text-xs text-gray-500">{{ $violation->occurred_at->format('M j, Y g:i A') }}</span>
                                    </div>
                                    <p class="text-sm text-gray-700 mb-3">{{ $violation->description }}</p>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs text-gray-500">
                                        <div>
                                            <i class="fas fa-globe mr-1"></i>
                                            {{ $violation->ip_address }}
                                        </div>
                                        @if(isset($violation->metadata['violation_count']))
                                        <div>
                                            <i class="fas fa-exclamation-circle mr-1"></i>
                                            Strike {{ $violation->metadata['violation_count'] }}
                                        </div>
                                        @endif
                                        @if(isset($violation->metadata['screen_resolution']))
                                        <div>
                                            <i class="fas fa-desktop mr-1"></i>
                                            {{ $violation->metadata['screen_resolution'] }}
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="p-8 text-center">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-exclamation-triangle text-gray-400 text-xl"></i>
                            </div>
                            <p class="text-gray-500">No violations found</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Actions Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-tools mr-2 text-gray-600"></i>
                        Actions
                    </h3>
                    
                    @if($ban->is_active)
                    <div class="space-y-4">
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-red-800 mb-2">
                                <i class="fas fa-ban mr-2"></i>
                                Student Currently Banned
                            </h4>
                            <p class="text-xs text-red-700 mb-3">This student cannot take the {{ $ban->subject->name ?? 'Unknown Subject' }} exam due to {{ $ban->total_violations }} security violations.</p>
                            <button onclick="showReactivateModal()" class="w-full inline-flex items-center justify-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                                <i class="fas fa-unlock-alt mr-2"></i>
                                Grant Mercy & Reactivate
                            </button>
                        </div>
                        
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-yellow-800 mb-2">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                Warning
                            </h4>
                            <p class="text-xs text-yellow-700">Only reactivate if you believe the student deserves a second chance. This action will be logged for audit purposes.</p>
                        </div>
                    </div>
                    @else
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-green-800 mb-2">
                            <i class="fas fa-check-circle mr-2"></i>
                            Student Reactivated
                        </h4>
                        <p class="text-xs text-green-700 mb-3">This student was reactivated on {{ $ban->reactivated_at->format('M j, Y') }} and can now retake the exam.</p>
                        @if($ban->reactivation_reason)
                        <div class="mt-3 p-3 bg-white rounded border">
                            <p class="text-xs text-gray-600"><strong>Reason:</strong> {{ $ban->reactivation_reason }}</p>
                        </div>
                        @endif
                    </div>
                    @endif

                    <!-- Quick Stats -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <h4 class="text-sm font-medium text-gray-700 mb-3">Quick Stats</h4>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">Total Violations</span>
                                <span class="font-medium text-red-600">{{ count($violations) }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">Days Since Ban</span>
                                <span class="font-medium text-gray-900">{{ $ban->banned_at->diffInDays(now()) }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">Ban Status</span>
                                <span class="font-medium {{ $ban->is_active ? 'text-red-600' : 'text-green-600' }}">
                                    {{ $ban->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reactivation Modal -->
@if($ban->is_active)
<div id="reactivateModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-xl max-w-lg w-full mx-4 transform scale-95 opacity-0 transition-all duration-300" id="reactivateModalContent">
        <form action="{{ route('admin.security.reactivate', $ban) }}" method="POST">
            @csrf
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-unlock-alt mr-2 text-green-600"></i>
                    Reactivate Student Account
                </h3>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex items-start space-x-3">
                        <i class="fas fa-exclamation-triangle text-yellow-600 mt-1"></i>
                        <div class="text-sm text-yellow-800">
                            <p class="font-medium mb-1">⚠️ ADMIN OVERRIDE WARNING</p>
                            <p>You are about to reactivate a student who was permanently banned for {{ $ban->total_violations }} security violations. This action:</p>
                            <ul class="list-disc list-inside mt-2 space-y-1">
                                <li>Will allow the student to retake the exam</li>
                                <li>Will be permanently logged for audit purposes</li>
                                <li>Should only be done if you believe mercy is warranted</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div>
                    <label for="admin_reason" class="block text-sm font-medium text-gray-700 mb-2">
                        Reason for Reactivation <span class="text-red-500">*</span>
                    </label>
                    <select name="admin_reason" id="admin_reason" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <option value="">Select a reason...</option>
                        <option value="Technical issue - false positive">Technical issue - false positive</option>
                        <option value="Student appeals - first offense mercy">Student appeals - first offense mercy</option>
                        <option value="Administrative error in original ban">Administrative error in original ban</option>
                        <option value="Student demonstrated understanding of rules">Student demonstrated understanding of rules</option>
                        <option value="Special circumstances warrant second chance">Special circumstances warrant second chance</option>
                        <option value="Instructor recommendation for reactivation">Instructor recommendation for reactivation</option>
                        <option value="Other (see notes below)">Other (see notes below)</option>
                    </select>
                </div>
                
                <div>
                    <label for="admin_notes" class="block text-sm font-medium text-gray-700 mb-2">
                        Additional Notes
                    </label>
                    <textarea name="admin_notes" id="admin_notes" rows="3" 
                              placeholder="Optional: Add any additional context or conditions for this reactivation..."
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-transparent"></textarea>
                </div>
                
                <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                    <p class="text-sm text-green-800">
                        <i class="fas fa-info-circle mr-2"></i>
                        After reactivation, the student will receive a clean slate for this subject and can attempt the exam again.
                    </p>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-end space-x-3">
                <button type="button" onclick="closeReactivateModal()" class="px-4 py-2 text-gray-700 hover:text-gray-900 font-medium">Cancel</button>
                <button type="submit" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg flex items-center">
                    <i class="fas fa-unlock-alt mr-2"></i>
                    Reactivate Student
                </button>
            </div>
        </form>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
function showReactivateModal() {
    const modal = document.getElementById('reactivateModal');
    const modalContent = document.getElementById('reactivateModalContent');
    
    if (modal && modalContent) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        setTimeout(() => {
            modalContent.style.transform = 'scale(1)';
            modalContent.style.opacity = '1';
        }, 10);
    }
}

function closeReactivateModal() {
    const modal = document.getElementById('reactivateModal');
    const modalContent = document.getElementById('reactivateModalContent');
    
    if (modal && modalContent) {
        modalContent.style.transform = 'scale(0.95)';
        modalContent.style.opacity = '0';
        
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 300);
    }
}

// Close modal when clicking outside
document.getElementById('reactivateModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeReactivateModal();
    }
});

// Escape key closes modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeReactivateModal();
    }
});
</script>
@endpush