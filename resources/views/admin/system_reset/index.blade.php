@extends('layouts.admin')
@section('title', '- System Reset & Backup')

@section('headerContent')
    <h3 class="font-bold pl-2 text-2xl text-white">System Reset & Backup</h3>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Backup Section -->
        <div class="admin-card">
            <div class="mb-6">
                <h4 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-2">
                    <i class="fas fa-download text-blue-600"></i> Database Backup
                </h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Create a complete backup of your database including all users, questions, scores, and settings.
                </p>
            </div>

            <div class="space-y-4">
                <button type="button" id="createBackupBtn" class="admin-btn-primary w-full">
                    <i class="fas fa-download mr-2"></i>
                    Create Database Backup
                </button>

                <div id="backupStatus" class="hidden p-4 rounded-lg"></div>

                <!-- Backup List -->
                <div class="mt-6">
                    <h5 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">Available Backups</h5>
                    <div id="backupsList" class="space-y-2">
                        <div class="text-center text-gray-500 py-4">
                            <i class="fas fa-spinner fa-spin"></i> Loading backups...
                        </div>
                    </div>
                </div>

                <!-- Upload & Restore Section -->
                <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-600">
                    <h5 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">
                        <i class="fas fa-upload text-green-600"></i> Restore Database
                    </h5>
                    <div class="bg-yellow-50 dark:bg-yellow-900 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4 mb-4">
                        <p class="text-sm text-yellow-700 dark:text-yellow-300">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <strong>Warning:</strong> Restoring will replace ALL current data with the backup data. Create a backup first!
                        </p>
                    </div>
                    
                    <form id="restoreForm" enctype="multipart/form-data" class="space-y-4">
                        <div>
                            <label for="backupFile" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Select Backup File (.sql)
                            </label>
                            <input type="file" 
                                   id="backupFile" 
                                   name="backup_file"
                                   accept=".sql"
                                   class="admin-input">
                        </div>
                        
                        <div>
                            <label for="restoreCode" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Restore Code <span class="text-red-500">*</span>
                            </label>
                            <input type="password" 
                                   id="restoreCode" 
                                   name="restore_code"
                                   class="admin-input" 
                                   placeholder="Enter restore code"
                                   autocomplete="off">
                        </div>
                        
                        <button type="submit" class="admin-btn-success w-full">
                            <i class="fas fa-upload mr-2"></i>
                            Restore Database from File
                        </button>
                    </form>
                    
                    <div id="restoreStatus" class="hidden p-4 rounded-lg mt-4"></div>
                </div>
            </div>
        </div>

        <!-- System Reset Section -->
        <div class="admin-card">
            <div class="mb-6">
                <h4 class="text-xl font-semibold text-red-600 mb-2">
                    <i class="fas fa-exclamation-triangle"></i> System Reset
                </h4>
                <div class="bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 rounded-lg p-4">
                    <p class="text-sm text-red-700 dark:text-red-300 font-semibold mb-2">⚠️ DANGER ZONE</p>
                    <p class="text-sm text-red-600 dark:text-red-400">
                        This will permanently delete ALL data including:
                    </p>
                    <ul class="list-disc list-inside text-sm text-red-600 dark:text-red-400 mt-2 space-y-1">
                        <li>All students and their exam scores</li>
                        <li>All questions and subjects</li>
                        <li>All uploaded images</li>
                        <li>All exam history</li>
                    </ul>
                    <p class="text-sm text-red-700 dark:text-red-300 font-semibold mt-3">
                        Only the current admin account will be preserved.
                    </p>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <label for="resetCode" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        System Reset Code <span class="text-red-500">*</span>
                    </label>
                    <input type="password" 
                           id="resetCode" 
                           class="admin-input" 
                           placeholder="Enter reset code from .env file"
                           autocomplete="off">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Current code: <code class="bg-gray-200 dark:bg-gray-600 px-1 rounded">{{ env('SYSTEM_RESET_CODE', '1122BER') }}</code>
                    </p>
                </div>

                <button type="button" id="systemResetBtn" class="admin-btn-danger w-full">
                    <i class="fas fa-trash-alt mr-2"></i>
                    Reset Entire System
                </button>

                <div id="resetStatus" class="hidden p-4 rounded-lg"></div>
            </div>
        </div>
    </div>

    <!-- Configuration Info -->
    <div class="admin-card mt-6">
        <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">
            <i class="fas fa-info-circle text-blue-600"></i> System Information
        </h4>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
            <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded">
                <div class="font-semibold text-gray-700 dark:text-gray-300">Database</div>
                <div class="text-gray-600 dark:text-gray-400">{{ env('DB_DATABASE') }}</div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded">
                <div class="font-semibold text-gray-700 dark:text-gray-300">Host</div>
                <div class="text-gray-600 dark:text-gray-400">{{ env('DB_HOST') }}:{{ env('DB_PORT') }}</div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded">
                <div class="font-semibold text-gray-700 dark:text-gray-300">Environment</div>
                <div class="text-gray-600 dark:text-gray-400">{{ env('APP_ENV') }}</div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded">
                <div class="font-semibold text-gray-700 dark:text-gray-300">Backup Path</div>
                <div class="text-gray-600 dark:text-gray-400">storage/app/backups/</div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    loadBackupsList();
    
    // Create Backup
    document.getElementById('createBackupBtn').addEventListener('click', function() {
        const btn = this;
        const originalText = btn.innerHTML;
        const statusDiv = document.getElementById('backupStatus');
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Creating Backup...';
        
        fetch('{{ route("admin.system.backup") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            statusDiv.className = 'p-4 rounded-lg ' + (data.success ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700');
            statusDiv.innerHTML = '<i class="fas fa-' + (data.success ? 'check-circle' : 'exclamation-triangle') + ' mr-2"></i>' + data.message;
            statusDiv.classList.remove('hidden');
            
            if (data.success) {
                loadBackupsList();
            }
        })
        .catch(error => {
            statusDiv.className = 'p-4 rounded-lg bg-red-100 text-red-700';
            statusDiv.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i>Error: ' + error.message;
            statusDiv.classList.remove('hidden');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    });
    
    // System Reset
    document.getElementById('systemResetBtn').addEventListener('click', function() {
        const resetCode = document.getElementById('resetCode').value;
        const btn = this;
        const originalText = btn.innerHTML;
        const statusDiv = document.getElementById('resetStatus');
        
        if (!resetCode) {
            alert('Please enter the reset code');
            return;
        }
        
        if (!confirm('Are you absolutely sure you want to reset the entire system? This action cannot be undone!')) {
            return;
        }
        
        if (!confirm('This will delete ALL student data, questions, and scores. Type YES to confirm:')) {
            return;
        }
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Resetting System...';
        
        // Create abort controller for timeout
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 120000); // 2 minutes timeout
        
        fetch('{{ route("admin.system.reset") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                reset_code: resetCode
            }),
            signal: controller.signal
        })
        .then(response => response.json())
        .then(data => {
            statusDiv.className = 'p-4 rounded-lg ' + (data.success ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700');
            statusDiv.innerHTML = '<i class="fas fa-' + (data.success ? 'check-circle' : 'exclamation-triangle') + ' mr-2"></i>' + data.message;
            statusDiv.classList.remove('hidden');
            
            if (data.success) {
                document.getElementById('resetCode').value = '';
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            }
        })
        .catch(error => {
            clearTimeout(timeoutId);
            let errorMessage = 'Unknown error occurred';
            
            if (error.name === 'AbortError') {
                errorMessage = 'Reset operation timed out. The system may still be processing. Please wait a few minutes and refresh the page.';
            } else if (error.message) {
                errorMessage = error.message;
            }
            
            statusDiv.className = 'p-4 rounded-lg bg-red-100 text-red-700';
            statusDiv.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i>Error: ' + errorMessage;
            statusDiv.classList.remove('hidden');
        })
        .finally(() => {
            clearTimeout(timeoutId);
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    });
    
    // Restore from file upload
    document.getElementById('restoreForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const statusDiv = document.getElementById('restoreStatus');
        
        if (!formData.get('backup_file') || !formData.get('restore_code')) {
            alert('Please select a backup file and enter the restore code');
            return;
        }
        
        if (!confirm('Are you sure you want to restore from the uploaded file? This will replace ALL current data!')) {
            return;
        }
        
        statusDiv.className = 'p-4 rounded-lg bg-blue-100 text-blue-700';
        statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Restoring database from uploaded file...';
        statusDiv.classList.remove('hidden');
        
        fetch('{{ route("admin.system.restore") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            statusDiv.className = 'p-4 rounded-lg ' + (data.success ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700');
            statusDiv.innerHTML = '<i class="fas fa-' + (data.success ? 'check-circle' : 'exclamation-triangle') + ' mr-2"></i>' + data.message;
            
            if (data.success) {
                document.getElementById('restoreForm').reset();
                setTimeout(() => {
                    if (confirm('Database restored successfully! Reload the page to see changes?')) {
                        window.location.reload();
                    }
                }, 2000);
            }
        })
        .catch(error => {
            statusDiv.className = 'p-4 rounded-lg bg-red-100 text-red-700';
            statusDiv.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i>Error: ' + error.message;
        });
    });
});

function loadBackupsList() {
    fetch('{{ route("admin.system.backup.list") }}')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('backupsList');
            
            if (data.success && data.backups.length > 0) {
                container.innerHTML = data.backups.map(backup => `
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex-1">
                            <div class="font-medium text-gray-800 dark:text-gray-200">${backup.filename}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">${backup.size} • ${backup.created}</div>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="restoreExistingBackup('${backup.filename}')" 
                                    class="admin-btn-success btn-sm">
                                <i class="fas fa-upload mr-1"></i>Restore
                            </button>
                            <a href="{{ route('admin.system.backup.download', '') }}/${backup.filename}" 
                               class="admin-btn-secondary btn-sm">
                                <i class="fas fa-download mr-1"></i>Download
                            </a>
                        </div>
                    </div>
                `).join('');
            } else {
                container.innerHTML = '<div class="text-center text-gray-500 py-4">No backups found</div>';
            }
        })
        .catch(error => {
            document.getElementById('backupsList').innerHTML = '<div class="text-center text-red-500 py-4">Error loading backups</div>';
        });
}

function restoreExistingBackup(filename) {
    const restoreCode = prompt('Enter restore code to restore from ' + filename + ':');
    
    if (!restoreCode) {
        return;
    }
    
    if (!confirm('Are you sure you want to restore from ' + filename + '? This will replace ALL current data!')) {
        return;
    }
    
    const statusDiv = document.getElementById('restoreStatus');
    statusDiv.className = 'p-4 rounded-lg bg-blue-100 text-blue-700';
    statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Restoring database from ' + filename + '...';
    statusDiv.classList.remove('hidden');
    
    fetch('{{ route("admin.system.restore.existing") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            backup_filename: filename,
            restore_code: restoreCode
        })
    })
    .then(response => response.json())
    .then(data => {
        statusDiv.className = 'p-4 rounded-lg ' + (data.success ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700');
        statusDiv.innerHTML = '<i class="fas fa-' + (data.success ? 'check-circle' : 'exclamation-triangle') + ' mr-2"></i>' + data.message;
        
        if (data.success) {
            setTimeout(() => {
                if (confirm('Database restored successfully! Reload the page to see changes?')) {
                    window.location.reload();
                }
            }, 2000);
        }
    })
    .catch(error => {
        statusDiv.className = 'p-4 rounded-lg bg-red-100 text-red-700';
        statusDiv.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i>Error: ' + error.message;
    });
}
</script>
@endpush