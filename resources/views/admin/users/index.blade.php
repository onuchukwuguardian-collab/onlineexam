@extends('layouts.admin')
@section('title', '- Manage Users')
@section('headerContent')
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl">Manage Users</h2>
            <p class="text-sm opacity-75">Total Users: {{ $users->total() }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.users.bulkUploadForm') }}" class="btn btn-secondary">
                <i class="fas fa-upload"></i> Bulk Upload
            </a>
            <a href="{{ route('admin.users.create') }}" class="btn btn-success">
                <i class="fas fa-user-plus"></i> Add User
            </a>
        </div>
    </div>
@endsection

@section('content')
    <!-- Enhanced DataTable Controls -->
    <div class="card mb-6">
        <div class="datatable-controls">
            <div class="controls-row">
                <div class="search-section">
                    <div class="search-box">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="globalSearch" placeholder="Search users..." class="search-input" value="{{ request('search_user') }}">
                    </div>
                </div>
                
                <div class="filter-section">
                    <select id="roleFilter" class="filter-select">
                        <option value="">All Roles</option>
                        <option value="admin" {{ request('role_filter') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="user" {{ request('role_filter') === 'user' ? 'selected' : '' }}>Student</option>
                    </select>
                    
                    <select id="classFilter" class="filter-select">
                        <option value="">All Classes</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ request('class_filter') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                        @endforeach
                    </select>
                    
                    <select id="pageSize" class="filter-select">
                        <option value="10">10 per page</option>
                        <option value="25" selected>25 per page</option>
                        <option value="50">50 per page</option>
                        <option value="100">100 per page</option>
                    </select>
                </div>
                
                <div class="action-section">
                    <button type="button" id="clearFilters" class="btn btn-secondary btn-sm">
                        <i class="fas fa-times"></i> Clear
                    </button>
                    <button type="button" id="exportUsers" class="btn btn-primary btn-sm">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if(session('bulk_upload_errors_detailed'))
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900 border border-red-300 dark:border-red-600 rounded-md">
            <h4 class="font-semibold text-red-700 dark:text-red-200">Bulk Upload Errors:</h4>
            <ul class="list-disc list-inside text-sm text-red-600 dark:text-red-300 max-h-48 overflow-y-auto mt-2">
                @foreach(session('bulk_upload_errors_detailed') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Enhanced DataTable -->
    <div class="datatable-container">
        <div class="datatable-wrapper">
            <table class="datatable" id="usersDataTable">
                <thead>
                    <tr>
                        <th class="sortable" data-column="name">
                            <div class="th-content">
                                <span>Name</span>
                                <i class="fas fa-sort sort-icon"></i>
                            </div>
                        </th>
                        <th class="sortable" data-column="email">
                            <div class="th-content">
                                <span>Email</span>
                                <i class="fas fa-sort sort-icon"></i>
                            </div>
                        </th>
                        <th class="sortable" data-column="role">
                            <div class="th-content">
                                <span>Role</span>
                                <i class="fas fa-sort sort-icon"></i>
                            </div>
                        </th>
                        <th class="sortable" data-column="class">
                            <div class="th-content">
                                <span>Class</span>
                                <i class="fas fa-sort sort-icon"></i>
                            </div>
                        </th>
                        <th data-column="registration">
                            <div class="th-content">
                                <span>Reg. No.</span>
                            </div>
                        </th>
                        <th data-column="passcode">
                            <div class="th-content">
                                <span>School ID</span>
                            </div>
                        </th>
                        <th data-column="actions">
                            <div class="th-content">
                                <span>Actions</span>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    @forelse($users as $user)
                        <tr data-user-id="{{ $user->id }}">
                            <td class="user-name">
                                <div class="user-info">
                                    <div class="user-avatar">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <span>{{ $user->name }}</span>
                                </div>
                            </td>
                            <td class="user-email">{{ $user->email }}</td>
                            <td class="user-role">
                                <span class="role-badge {{ $user->isAdmin() ? 'role-admin' : 'role-student' }}">
                                    {{ $user->isAdmin() ? 'Admin' : 'Student' }}
                                </span>
                            </td>
                            <td class="user-class">{{ $user->classModel->name ?? 'N/A' }}</td>
                            <td class="user-registration">
                                @if($user->registration_number)
                                    @php
                                        $regNo = $user->registration_number;
                                        $len = strlen($regNo);
                                        $obfuscatedRegNo = ($len > 4) ? substr($regNo, 0, 1) . str_repeat('*', $len - 3) . substr($regNo, -2) : (($len > 0) ? substr($regNo, 0, 1) . str_repeat('*', $len - 1) : 'N/A');
                                    @endphp
                                    <span class="reg-number" title="Click to reveal" data-full="{{ $regNo }}" onclick="toggleRegNumber(this)">{{ $obfuscatedRegNo }}</span>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td class="user-passcode">
                                <span class="passcode">{{ $user->unique_id ?? 'N/A' }}</span>
                            </td>
                            <td class="user-actions">
                                <div class="action-buttons">
                                    <a href="{{ route('admin.users.edit', $user->id) }}" class="action-btn edit-btn" title="Edit User">
                                        <i class="fas fa-user-edit"></i>
                                        <span class="hidden sm:inline">Edit</span>
                                    </a>
                                    @if(Auth::id() !== $user->id && !($user->isAdmin() && \App\Models\User::where('role', 'admin')->count() <= 1))
                                        <button type="button" class="action-btn delete-btn" title="Delete User" onclick="deleteUser({{ $user->id }}, '{{ $user->name }}')">
                                            <i class="fas fa-user-times"></i>
                                            <span class="hidden sm:inline">Delete</span>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="empty-row">
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="fas fa-users fa-3x"></i>
                                    <p>No users found</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Enhanced Pagination -->
        <div class="datatable-footer">
            <div class="pagination-info">
                <span>Showing {{ $users->firstItem() ?? 0 }} to {{ $users->lastItem() ?? 0 }} of {{ $users->total() }} users</span>
            </div>
            <div class="pagination-controls">
                {{ $users->links() }}
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    /* DataTable Styles */
    .datatable-controls {
        background: #f8fafc;
        border-radius: 0.5rem;
        padding: 1rem;
        border: 1px solid #e2e8f0;
    }
    
    .controls-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }
    
    .search-section {
        flex: 1;
        min-width: 250px;
    }
    
    .search-box {
        position: relative;
    }
    
    .search-icon {
        position: absolute;
        left: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        color: #6b7280;
    }
    
    .search-input {
        width: 100%;
        padding: 0.5rem 0.75rem 0.5rem 2.5rem;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        font-size: 0.875rem;
    }
    
    .search-input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    .filter-section {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }
    
    .filter-select {
        padding: 0.5rem;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        min-width: 120px;
    }
    
    .filter-select:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    .action-section {
        display: flex;
        gap: 0.5rem;
    }
    
    .btn-sm {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
    }
    
    /* DataTable Container */
    .datatable-container {
        background: white;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    
    .datatable-wrapper {
        overflow-x: auto;
    }
    
    .datatable {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .datatable th {
        background: #f1f5f9;
        border-bottom: 2px solid #e2e8f0;
        padding: 1rem 0.75rem;
        text-align: left;
        font-weight: 600;
        color: #374151;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    .datatable th.sortable {
        cursor: pointer;
        user-select: none;
    }
    
    .datatable th.sortable:hover {
        background: #e2e8f0;
    }
    
    .th-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .sort-icon {
        color: #9ca3af;
        font-size: 0.75rem;
        transition: all 0.2s;
    }
    
    .datatable th.sort-asc .sort-icon {
        color: #3b82f6;
        transform: rotate(180deg);
    }
    
    .datatable th.sort-desc .sort-icon {
        color: #3b82f6;
    }
    
    .datatable td {
        padding: 1rem 0.75rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    
    .datatable tr:hover td {
        background: #f8fafc;
    }
    
    /* User Info */
    .user-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .user-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #3b82f6;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.875rem;
    }
    
    /* Role Badges */
    .role-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .role-admin {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .role-student {
        background: #d1fae5;
        color: #065f46;
    }
    
    /* Registration Number */
    .reg-number {
        cursor: pointer;
        padding: 0.25rem 0.5rem;
        background: #f3f4f6;
        border-radius: 0.25rem;
        font-family: monospace;
        font-size: 0.875rem;
    }
    
    .reg-number:hover {
        background: #e5e7eb;
    }
    
    .passcode {
        font-family: monospace;
        font-size: 0.875rem;
        background: #f3f4f6;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
    }
    
    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 0.5rem;
    }
    
    .action-btn {
        width: 32px;
        height: 32px;
        border-radius: 0.375rem;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        text-decoration: none;
    }
    
    .edit-btn {
        background: #dbeafe;
        color: #1d4ed8;
    }
    
    .edit-btn:hover {
        background: #bfdbfe;
    }
    
    .delete-btn {
        background: #fee2e2;
        color: #dc2626;
    }
    
    .delete-btn:hover {
        background: #fecaca;
    }
    
    /* Bulk Delete Functionality */
    .bulk-select-container {
        display: flex;
        align-items: center;
        margin-right: 10px;
    }
    
    .bulk-checkbox {
        cursor: pointer;
    }
    
    .user-select-checkbox {
        cursor: pointer;
        transform: scale(1.1);
    }
    
    .btn-danger {
        background: #dc2626;
        color: white;
        border: 1px solid #dc2626;
    }
    
    .btn-danger:hover {
        background: #b91c1c;
        border-color: #b91c1c;
    }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #6b7280;
    }
    
    .empty-state i {
        margin-bottom: 1rem;
        color: #9ca3af;
    }
    
    /* Footer */
    .datatable-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
    }
    
    .pagination-info {
        font-size: 0.875rem;
        color: #6b7280;
    }
    
    .text-muted {
        color: #9ca3af;
        font-style: italic;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .controls-row {
            flex-direction: column;
            align-items: stretch;
        }
        
        .search-section {
            min-width: auto;
        }
        
        .filter-section {
            justify-content: space-between;
        }
        
        .datatable-footer {
            flex-direction: column;
            gap: 1rem;
        }
    }
</style>
@endpush

@push('scripts')
<script>
let currentSort = { column: null, direction: 'asc' };
let currentFilters = { search: '', role: '', class: '', pageSize: 25 };

// Initialize DataTable
document.addEventListener('DOMContentLoaded', function() {
    initializeDataTable();
    setupEventListeners();
});

function initializeDataTable() {
    // Setup sorting
    document.querySelectorAll('.sortable').forEach(header => {
        header.addEventListener('click', function() {
            const column = this.dataset.column;
            handleSort(column);
        });
    });
}

function setupEventListeners() {
    // Global search with server-side filtering
    const searchInput = document.getElementById('globalSearch');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                applyServerFilters();
            }, 500); // Increased delay to avoid too many requests
        });
    }
    
    // Role filter with server-side filtering
    const roleFilter = document.getElementById('roleFilter');
    if (roleFilter) {
        roleFilter.addEventListener('change', function() {
            applyServerFilters();
        });
    }
    
    // Class filter with server-side filtering
    const classFilter = document.getElementById('classFilter');
    if (classFilter) {
        classFilter.addEventListener('change', function() {
            applyServerFilters();
        });
    }
    
    // Page size (keep client-side for now)
    const pageSize = document.getElementById('pageSize');
    if (pageSize) {
        pageSize.addEventListener('change', function() {
            currentFilters.pageSize = this.value;
            filterTable();
        });
    }
    
    // Clear filters
    const clearFilters = document.getElementById('clearFilters');
    if (clearFilters) {
        clearFilters.addEventListener('click', function() {
            clearAllFilters();
        });
    }
    
    // Export users
    const exportUsers = document.getElementById('exportUsers');
    if (exportUsers) {
        exportUsers.addEventListener('click', function() {
            exportUsersData();
        });
    }
    
    // Bulk delete functionality
    setupBulkDeleteFunctionality();
}

// NEW FUNCTION: Apply server-side filters
function applyServerFilters() {
    const searchInput = document.getElementById('globalSearch');
    const roleFilter = document.getElementById('roleFilter');
    const classFilter = document.getElementById('classFilter');
    
    // Build URL with current filter parameters
    const url = new URL(window.location.href.split('?')[0]);
    
    if (searchInput && searchInput.value.trim()) {
        url.searchParams.set('search_user', searchInput.value.trim());
    }
    
    if (roleFilter && roleFilter.value) {
        url.searchParams.set('role_filter', roleFilter.value);
    }
    
    if (classFilter && classFilter.value) {
        url.searchParams.set('class_filter', classFilter.value);
    }
    
    // Redirect to filtered URL
    window.location.href = url.toString();
}

// NEW FUNCTION: Setup bulk delete functionality
function setupBulkDeleteFunctionality() {
    // Add bulk delete button to the action section
    const actionSection = document.querySelector('.action-section');
    if (actionSection) {
        const bulkDeleteBtn = document.createElement('button');
        bulkDeleteBtn.type = 'button';
        bulkDeleteBtn.id = 'bulkDeleteBtn';
        bulkDeleteBtn.className = 'btn btn-danger btn-sm';
        bulkDeleteBtn.style.display = 'none'; // Hidden by default
        bulkDeleteBtn.innerHTML = '<i class="fas fa-trash-alt"></i> Delete Selected';
        bulkDeleteBtn.addEventListener('click', performBulkDelete);
        actionSection.appendChild(bulkDeleteBtn);
    }
    
    // Add select all checkbox to table header
    const firstTh = document.querySelector('.datatable th:first-child');
    if (firstTh) {
        const selectAllContainer = document.createElement('div');
        selectAllContainer.className = 'bulk-select-container';
        selectAllContainer.innerHTML = `
            <input type="checkbox" id="selectAll" class="bulk-checkbox" title="Select All">
            <label for="selectAll" style="margin-left: 5px; font-size: 12px;">All</label>
        `;
        firstTh.querySelector('.th-content').prepend(selectAllContainer);
        
        // Setup select all functionality
        const selectAllCheckbox = document.getElementById('selectAll');
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.user-select-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = this.checked;
            });
            updateBulkDeleteButton();
        });
    }
    
    // Add individual checkboxes to each user row
    const userRows = document.querySelectorAll('#usersTableBody tr[data-user-id]');
    userRows.forEach(row => {
        const userId = row.getAttribute('data-user-id');
        const firstCell = row.querySelector('td:first-child');
        
        if (firstCell) {
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.className = 'user-select-checkbox';
            checkbox.setAttribute('data-user-id', userId);
            checkbox.style.marginRight = '8px';
            checkbox.addEventListener('change', updateBulkDeleteButton);
            
            const userInfo = firstCell.querySelector('.user-info');
            if (userInfo) {
                userInfo.prepend(checkbox);
            }
        }
    });
}

// NEW FUNCTION: Update bulk delete button visibility
function updateBulkDeleteButton() {
    const selectedCheckboxes = document.querySelectorAll('.user-select-checkbox:checked');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    
    if (bulkDeleteBtn) {
        bulkDeleteBtn.style.display = selectedCheckboxes.length > 0 ? 'inline-block' : 'none';
        bulkDeleteBtn.innerHTML = `<i class="fas fa-trash-alt"></i> Delete Selected (${selectedCheckboxes.length})`;
    }
    
    // Update select all checkbox state
    const selectAllCheckbox = document.getElementById('selectAll');
    const allCheckboxes = document.querySelectorAll('.user-select-checkbox');
    const allChecked = allCheckboxes.length > 0 && Array.from(allCheckboxes).every(cb => cb.checked);
    const someChecked = Array.from(allCheckboxes).some(cb => cb.checked);
    
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = allChecked;
        selectAllCheckbox.indeterminate = someChecked && !allChecked;
    }
}

// NEW FUNCTION: Perform bulk delete
function performBulkDelete() {
    const selectedCheckboxes = document.querySelectorAll('.user-select-checkbox:checked');
    const selectedIds = Array.from(selectedCheckboxes).map(cb => cb.getAttribute('data-user-id'));
    
    if (selectedIds.length === 0) {
        alert('Please select users to delete.');
        return;
    }
    
    const confirmMsg = `Are you sure you want to delete ${selectedIds.length} selected user(s)? This action cannot be undone and will delete all related data including exam scores.`;
    
    if (!confirm(confirmMsg)) {
        return;
    }
    
    // Create form for bulk delete
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("admin.users.bulk-delete") }}';
    form.style.display = 'none';
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    let formHtml = `<input type="hidden" name="_token" value="${csrfToken}">`;
    
    selectedIds.forEach(id => {
        formHtml += `<input type="hidden" name="user_ids[]" value="${id}">`;
    });
    
    form.innerHTML = formHtml;
    document.body.appendChild(form);
    form.submit();
}

function handleSort(column) {
    // Remove existing sort classes
    document.querySelectorAll('.datatable th').forEach(th => {
        th.classList.remove('sort-asc', 'sort-desc');
    });
    
    // Determine sort direction
    if (currentSort.column === column) {
        currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
    } else {
        currentSort.column = column;
        currentSort.direction = 'asc';
    }
    
    // Add sort class to current column
    const header = document.querySelector(`[data-column="${column}"]`);
    if (header) {
        header.classList.add(`sort-${currentSort.direction}`);
    }
    
    // Apply sort
    sortTable(column, currentSort.direction);
}

function sortTable(column, direction) {
    const tbody = document.getElementById('usersTableBody');
    if (!tbody) return;
    
    const rows = Array.from(tbody.querySelectorAll('tr:not(.empty-row)'));
    
    rows.sort((a, b) => {
        let aVal, bVal;
        
        switch(column) {
            case 'name':
                const aName = a.querySelector('.user-name span');
                const bName = b.querySelector('.user-name span');
                aVal = aName ? aName.textContent.toLowerCase() : '';
                bVal = bName ? bName.textContent.toLowerCase() : '';
                break;
            case 'email':
                const aEmail = a.querySelector('.user-email');
                const bEmail = b.querySelector('.user-email');
                aVal = aEmail ? aEmail.textContent.toLowerCase() : '';
                bVal = bEmail ? bEmail.textContent.toLowerCase() : '';
                break;
            case 'role':
                const aRole = a.querySelector('.role-badge');
                const bRole = b.querySelector('.role-badge');
                aVal = aRole ? aRole.textContent.toLowerCase() : '';
                bVal = bRole ? bRole.textContent.toLowerCase() : '';
                break;
            case 'class':
                const aClass = a.querySelector('.user-class');
                const bClass = b.querySelector('.user-class');
                aVal = aClass ? aClass.textContent.toLowerCase() : '';
                bVal = bClass ? bClass.textContent.toLowerCase() : '';
                break;
            default:
                return 0;
        }
        
        if (direction === 'asc') {
            return aVal.localeCompare(bVal);
        } else {
            return bVal.localeCompare(aVal);
        }
    });
    
    // Re-append sorted rows
    rows.forEach(row => tbody.appendChild(row));
}

function filterTable() {
    const rows = document.querySelectorAll('#usersTableBody tr:not(.empty-row)');
    let visibleCount = 0;
    
    rows.forEach(row => {
        let visible = true;
        
        // Search filter
        if (currentFilters.search) {
            const searchText = currentFilters.search.toLowerCase();
            const rowText = row.textContent.toLowerCase();
            if (!rowText.includes(searchText)) {
                visible = false;
            }
        }
        
        // Role filter
        if (currentFilters.role) {
            const roleElement = row.querySelector('.role-badge');
            if (roleElement) {
                const roleText = roleElement.textContent.toLowerCase();
                if (!roleText.includes(currentFilters.role.toLowerCase())) {
                    visible = false;
                }
            }
        }
        
        // Class filter
        if (currentFilters.class) {
            const classElement = row.querySelector('.user-class');
            if (classElement) {
                const classText = classElement.textContent;
                if (classText === 'N/A' || !classText.includes(currentFilters.class)) {
                    visible = false;
                }
            }
        }
        
        row.style.display = visible ? '' : 'none';
        if (visible) visibleCount++;
    });
    
    // Show/hide empty state
    const emptyRow = document.querySelector('.empty-row');
    if (emptyRow) {
        emptyRow.style.display = visibleCount === 0 ? '' : 'none';
    }
}

function clearAllFilters() {
    // Redirect to clean URL without any filters
    window.location.href = window.location.href.split('?')[0];
}

function toggleRegNumber(element) {
    const isObfuscated = element.textContent.includes('*');
    if (isObfuscated) {
        element.textContent = element.dataset.full;
        element.title = 'Click to hide';
    } else {
        const full = element.dataset.full;
        const len = full.length;
        const obfuscated = (len > 4) ? full.substr(0, 1) + '*'.repeat(len - 3) + full.substr(-2) : full.substr(0, 1) + '*'.repeat(len - 1);
        element.textContent = obfuscated;
        element.title = 'Click to reveal';
    }
}

function deleteUser(userId, userName) {
    if (!confirm(`Are you sure you want to delete user "${userName}"? This action cannot be undone and will delete related scores.`)) {
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/admin/users/${userId}`;
    form.style.display = 'none';
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    form.innerHTML = `
        <input type="hidden" name="_token" value="${csrfToken}">
        <input type="hidden" name="_method" value="DELETE">
    `;
    
    document.body.appendChild(form);
    form.submit();
}

function exportUsersData() {
    // Create export URL with current filters
    const params = new URLSearchParams();
    if (currentFilters.search) params.append('search', currentFilters.search);
    if (currentFilters.role) params.append('role', currentFilters.role);
    if (currentFilters.class) params.append('class', currentFilters.class);
    
    // For now, just show an alert - you can implement actual export functionality
    alert('Export functionality would be implemented here with current filters: ' + params.toString());
}
</script>
@endpush