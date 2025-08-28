@extends('layouts.admin')

@section('title', 'Performance Scoreboard')

@push('styles')
<style>
    /* Clean, Simple Scoreboard Styling */
    .scoreboard-container {
        background: #f8f9fa;
        min-height: 100vh;
        padding: 20px;
    }
    
    .scoreboard-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        overflow: visible;
    }
    
    .scoreboard-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        text-align: center;
    }
    
    .scoreboard-title {
        font-size: 28px;
        font-weight: bold;
        margin: 0;
    }
    
    .scoreboard-subtitle {
        font-size: 16px;
        opacity: 0.9;
        margin-top: 8px;
    }
    
    /* Class Selection */
    .class-selection {
        background: #fff;
        padding: 25px;
        border-bottom: 2px solid #e9ecef;
    }
    
    .class-form {
        display: flex;
        align-items: center;
        gap: 15px;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .class-select {
        padding: 12px 20px;
        border: 2px solid #007bff;
        border-radius: 8px;
        font-size: 16px;
        background: white;
        color: #333;
        min-width: 250px;
    }
    
    .class-select:focus {
        outline: none;
        border-color: #0056b3;
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
    }
    
    .btn-clear {
        background: #dc3545;
        color: white;
        padding: 12px 20px;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
    }
    
    .btn-clear:hover {
        background: #c82333;
        color: white;
        text-decoration: none;
    }
    
    /* Statistics Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        padding: 30px;
        background: #f8f9fa;
    }
    
    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        text-align: center;
        border-left: 5px solid;
    }
    
    .stat-card.students { border-left-color: #007bff; }
    .stat-card.average { border-left-color: #28a745; }
    .stat-card.highest { border-left-color: #ffc107; }
    .stat-card.subjects { border-left-color: #6f42c1; }
    
    .stat-number {
        font-size: 36px;
        font-weight: bold;
        margin-bottom: 8px;
    }
    
    .stat-label {
        font-size: 14px;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .students .stat-number { color: #007bff; }
    .average .stat-number { color: #28a745; }
    .highest .stat-number { color: #ffc107; }
    .subjects .stat-number { color: #6f42c1; }
    
    /* Table Controls */
    .table-controls {
        background: #fff;
        padding: 20px 30px;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .control-group {
        display: flex;
        gap: 10px;
        align-items: center;
        position: relative;
    }
    
    .control-btn {
        background: #ffc107;
        color: #000;
        border: 2px solid #e0a800;
        padding: 10px 16px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
    }
    
    .control-btn:hover {
        background: #e0a800;
        color: #000;
        text-decoration: none;
        transform: translateY(-1px);
    }
    
    .control-btn.export {
        background: #28a745;
        color: white;
        border-color: #1e7e34;
    }
    
    .control-btn.export:hover {
        background: #1e7e34;
        color: white;
    }
    
    /* Simple Table Styling */
    .scoreboard-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
    }
    
    .scoreboard-table th {
        background: #f8f9fa;
        padding: 15px 12px;
        text-align: left;
        font-weight: 600;
        color: #333;
        border-bottom: 2px solid #dee2e6;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    .scoreboard-table td {
        padding: 12px;
        border-bottom: 1px solid #dee2e6;
        vertical-align: middle;
    }
    
    .scoreboard-table tbody tr:hover {
        background: #f8f9fa;
    }
    
    /* Position Badges */
    .position-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
    }
    
    .position-1 { background: #fff3cd; color: #856404; }
    .position-2 { background: #e2e3e5; color: #383d41; }
    .position-3 { background: #ffeaa7; color: #856404; }
    .position-other { background: #e3f2fd; color: #1565c0; }
    
    /* Student Info */
    .student-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .student-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #007bff;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        flex-shrink: 0;
    }
    
    .student-details h4 {
        margin: 0;
        font-size: 16px;
        color: #333;
    }
    
    .student-details p {
        margin: 0;
        font-size: 14px;
        color: #666;
    }
    
    /* Score Styling */
    .score-cell {
        text-align: center;
    }
    
    .score-number {
        font-size: 20px;
        font-weight: bold;
        display: block;
    }
    
    .score-percentage {
        font-size: 12px;
        padding: 2px 8px;
        border-radius: 12px;
        margin-top: 4px;
        display: inline-block;
    }
    
    .score-excellent { color: #28a745; }
    .score-excellent .score-percentage { background: #d4edda; color: #155724; }
    
    .score-good { color: #007bff; }
    .score-good .score-percentage { background: #cce7ff; color: #004085; }
    
    .score-average { color: #ffc107; }
    .score-average .score-percentage { background: #fff3cd; color: #856404; }
    
    .score-poor { color: #dc3545; }
    .score-poor .score-percentage { background: #f8d7da; color: #721c24; }
    
    .score-none {
        color: #6c757d;
        font-size: 24px;
    }
    
    /* Column Toggle - Wider Menu */
    .column-toggle-menu {
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        border: 2px solid #ffc107;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        z-index: 9999;
        min-width: 400px;
        max-width: 500px;
        max-height: 500px;
        overflow-y: auto;
        margin-top: 5px;
    }
    
    .column-toggle-menu h4 {
        margin: 0 0 15px 0;
        color: #333;
        font-size: 16px;
    }
    
    .column-option {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 12px;
        padding: 10px 8px;
        border-radius: 4px;
        transition: background-color 0.2s ease;
        min-height: 40px;
    }
    
    .column-option:hover {
        background-color: #fff8e1;
    }
    
    .column-option input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: #ffc107;
    }
    
    .column-option label {
        font-size: 14px;
        color: #333;
        cursor: pointer;
        flex: 1;
        font-weight: 500;
        white-space: normal;
        word-wrap: break-word;
        line-height: 1.4;
    }
    
    .toggle-buttons {
        display: flex;
        gap: 8px;
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #dee2e6;
    }
    
    .toggle-btn {
        padding: 6px 12px;
        border: none;
        border-radius: 4px;
        font-size: 12px;
        cursor: pointer;
        flex: 1;
    }
    
    .toggle-btn.show-all {
        background: #28a745;
        color: white;
    }
    
    .toggle-btn.hide-all {
        background: #dc3545;
        color: white;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .scoreboard-container {
            padding: 10px;
        }
        
        .class-form {
            flex-direction: column;
            align-items: stretch;
        }
        
        .class-select {
            min-width: auto;
        }
        
        .table-controls {
            flex-direction: column;
            align-items: stretch;
        }
        
        .control-group {
            justify-content: center;
        }
        
        .scoreboard-table {
            font-size: 14px;
        }
        
        .scoreboard-table th,
        .scoreboard-table td {
            padding: 8px 6px;
        }
        
        .student-info {
            flex-direction: column;
            text-align: center;
            gap: 8px;
        }
        
        /* Mobile Column Toggle Menu */
        .column-toggle-menu {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            right: auto;
            min-width: 90vw;
            max-width: 400px;
            max-height: 80vh;
        }
    }
    
    /* Hidden columns */
    .column-hidden {
        display: none !important;
    }
    
    /* No data message */
    .no-data {
        text-align: center;
        padding: 60px 20px;
        color: #666;
    }
    
    .no-data i {
        font-size: 48px;
        color: #ccc;
        margin-bottom: 20px;
    }
    
    .no-data h3 {
        font-size: 24px;
        margin-bottom: 10px;
        color: #333;
    }
    
    .no-data p {
        font-size: 16px;
        margin-bottom: 20px;
    }
</style>
@endpush

@section('headerContent')
    <div class="scoreboard-header">
        <h1 class="scoreboard-title">Performance Scoreboard</h1>
        <p class="scoreboard-subtitle">View and analyze student performance across classes and subjects</p>
    </div>
@endsection

@section('content')
<div class="scoreboard-container">
    <div class="scoreboard-card">
        <!-- Class Selection -->
        <div class="class-selection">
            <form method="GET" action="{{ route('admin.scoreboard.index') }}" class="class-form">
                <label for="class_id_filter" style="font-weight: 600; color: #333;">Select Class:</label>
                <select name="class_id_filter" id="class_id_filter" class="class-select" onchange="this.form.submit()">
                    <option value="">-- Select a Class --</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" {{ (isset($selectedClass) && $selectedClass->id == $class->id) ? 'selected' : '' }}>
                            {{ $class->name }}
                        </option>
                    @endforeach
                </select>
                @if($selectedClass)
                    <a href="{{ route('admin.scoreboard.index') }}" class="btn-clear">
                        <i class="fas fa-times"></i> Clear Filter
                    </a>
                @endif
            </form>
        </div>

        @if($selectedClass)
            @if($classHasScores && $studentsPerformance->isNotEmpty() && $classSubjects->isNotEmpty())
                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card students">
                        <div class="stat-number">{{ $studentsPerformance->count() }}</div>
                        <div class="stat-label">Total Students</div>
                    </div>
                    <div class="stat-card average">
                        <div class="stat-number">{{ number_format($studentsPerformance->avg('total_score'), 1) }}</div>
                        <div class="stat-label">Average Score</div>
                    </div>
                    <div class="stat-card highest">
                        <div class="stat-number">{{ $studentsPerformance->max('total_score') }}</div>
                        <div class="stat-label">Highest Score</div>
                    </div>
                    <div class="stat-card subjects">
                        <div class="stat-number">{{ $classSubjects->count() }}</div>
                        <div class="stat-label">Total Subjects</div>
                    </div>
                </div>

                <!-- Table Controls -->
                <div class="table-controls">
                    <div class="control-group">
                        <div style="position: relative;">
                            <button type="button" id="columnToggleBtn" class="action-btn warning-btn">
                                <i class="fas fa-columns"></i> Show/Hide Columns
                            </button>
                            <div id="columnToggleMenu" class="column-toggle-menu" style="display: none;">
                                <h4>Show/Hide Columns</h4>
                                <div class="column-option">
                                    <input type="checkbox" id="col-rank" checked>
                                    <label for="col-rank">Rank</label>
                                </div>
                                <div class="column-option">
                                    <input type="checkbox" id="col-student" checked>
                                    <label for="col-student">Student Name</label>
                                </div>
                                <div class="column-option">
                                    <input type="checkbox" id="col-registration" checked>
                                    <label for="col-registration">Registration No.</label>
                                </div>
                                <div class="column-option">
                                    <input type="checkbox" id="col-class" checked>
                                    <label for="col-class">Class</label>
                                </div>
                                @foreach($classSubjects as $subject)
                                <div class="column-option">
                                    <input type="checkbox" id="col-subject-{{ $subject->id }}" checked>
                                    <label for="col-subject-{{ $subject->id }}">{{ $subject->name }}</label>
                                </div>
                                @endforeach
                                <div class="column-option">
                                    <input type="checkbox" id="col-total" checked>
                                    <label for="col-total">Total Score</label>
                                </div>
                                <div class="column-option">
                                    <input type="checkbox" id="col-average" checked>
                                    <label for="col-average">Average %</label>
                                </div>
                                <div class="column-option">
                                    <input type="checkbox" id="col-subjects-taken" checked>
                                    <label for="col-subjects-taken">Subjects Taken</label>
                                </div>
                                <div class="toggle-buttons">
                                    <button type="button" id="showAllColumns" class="toggle-btn show-all">Show All</button>
                                    <button type="button" id="hideAllColumns" class="toggle-btn hide-all">Hide All</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="control-group">
                        <button onclick="exportData('csv')" class="action-btn view-btn">
                            <i class="fas fa-file-csv"></i> Export CSV
                        </button>
                        <button onclick="exportData('excel')" class="action-btn info-btn">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </button>
                        <button onclick="printTable()" class="action-btn edit-btn">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                </div>

                <!-- Scoreboard Table -->
                <div style="overflow-x: auto;">
                    <table class="scoreboard-table" id="scoreboardTable">
                        <thead>
                            <tr>
                                <th class="col-rank">Rank</th>
                                <th class="col-student">Student Name</th>
                                <th class="col-registration">Registration No.</th>
                                <th class="col-class">Class</th>
                                @foreach($classSubjects as $subject)
                                    <th class="col-subject-{{ $subject->id }}" style="text-align: center;">{{ $subject->name }}</th>
                                @endforeach
                                <th class="col-total" style="text-align: center;">Total Score</th>
                                <th class="col-average" style="text-align: center;">Average %</th>
                                <th class="col-subjects-taken" style="text-align: center;">Subjects Taken</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($studentsPerformance as $studentData)
                                <tr>
                                    <td class="col-rank">
                                        @php
                                            $positionClass = 'position-other';
                                            $positionIcon = 'fas fa-hashtag';
                                            if ($studentData->position == 1) {
                                                $positionClass = 'position-1';
                                                $positionIcon = 'fas fa-trophy';
                                            } elseif ($studentData->position == 2) {
                                                $positionClass = 'position-2';
                                                $positionIcon = 'fas fa-medal';
                                            } elseif ($studentData->position == 3) {
                                                $positionClass = 'position-3';
                                                $positionIcon = 'fas fa-award';
                                            }
                                        @endphp
                                        <span class="position-badge {{ $positionClass }}">
                                            <i class="{{ $positionIcon }}"></i>
                                            {{ $studentData->position }}{{ $studentData->position_suffix }}
                                        </span>
                                    </td>
                                    <td class="col-student">
                                        <div class="student-info">
                                            <div class="student-avatar">
                                                {{ strtoupper(substr($studentData->name, 0, 1)) }}
                                            </div>
                                            <div class="student-details">
                                                <h4>{{ $studentData->name }}</h4>
                                                <p>{{ $studentData->class_name }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="col-registration">{{ $studentData->registration_number ?? 'N/A' }}</td>
                                    <td class="col-class">{{ $studentData->class_name ?? 'N/A' }}</td>
                                    @foreach($classSubjects as $subject)
                                        <td class="col-subject-{{ $subject->id }} score-cell">
                                            @php
                                                $scoreData = $studentData->scores_data[$subject->id] ?? null;
                                                $score = $scoreData['score'] ?? '-';
                                                $total = $scoreData['total_questions'] ?? '-';
                                                $percentage = ($score !== '-' && $total !== '-' && $total > 0) ? round(($score / $total) * 100, 1) : null;
                                                
                                                $scoreClass = 'score-none';
                                                if ($percentage !== null) {
                                                    if ($percentage >= 80) {
                                                        $scoreClass = 'score-excellent';
                                                    } elseif ($percentage >= 70) {
                                                        $scoreClass = 'score-good';
                                                    } elseif ($percentage >= 50) {
                                                        $scoreClass = 'score-average';
                                                    } else {
                                                        $scoreClass = 'score-poor';
                                                    }
                                                }
                                            @endphp
                                            
                                            @if($score !== '-')
                                                <div class="{{ $scoreClass }}">
                                                    <span class="score-number">{{ $score }}</span>
                                                    <span class="score-percentage">{{ $percentage }}%</span>
                                                </div>
                                            @else
                                                <span class="score-none">
                                                    <i class="fas fa-minus"></i>
                                                </span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="col-total score-cell">
                                        <span class="score-number" style="color: #007bff;">{{ $studentData->total_score }}</span>
                                    </td>
                                    <td class="col-average score-cell">
                                        @php
                                            $avgClass = 'score-poor';
                                            if ($studentData->average_percentage >= 80) {
                                                $avgClass = 'score-excellent';
                                            } elseif ($studentData->average_percentage >= 70) {
                                                $avgClass = 'score-good';
                                            } elseif ($studentData->average_percentage >= 50) {
                                                $avgClass = 'score-average';
                                            }
                                        @endphp
                                        <div class="{{ $avgClass }}">
                                            <span class="score-percentage">{{ $studentData->average_percentage }}%</span>
                                        </div>
                                    </td>
                                    <td class="col-subjects-taken score-cell">
                                        <span style="background: #e3f2fd; color: #1565c0; padding: 4px 12px; border-radius: 12px; font-size: 14px; font-weight: 600;">
                                            {{ $studentData->subjects_taken_count ?? 0 }}/{{ $studentData->subjects_available_count ?? 0 }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            @elseif(!$classSubjects->isNotEmpty() && $selectedClass)
                <div class="no-data">
                    <i class="fas fa-book-open"></i>
                    <h3>No Subjects Found</h3>
                    <p>No subjects have been defined for the class '{{ $selectedClass->name }}'.</p>
                    <a href="{{ route('admin.subjects.index') }}" class="control-btn export">
                        <i class="fas fa-plus"></i> Add Subjects
                    </a>
                </div>
            @elseif($selectedClass && !$classHasScores)
                <div class="no-data">
                    <i class="fas fa-chart-line"></i>
                    <h3>No Scores Recorded</h3>
                    <p>No scores have been recorded yet for students in '{{ $selectedClass->name }}'.</p>
                    <p>Students need to take exams to appear on the scoreboard.</p>
                </div>
            @endif
        @else
            <div class="no-data">
                <i class="fas fa-school"></i>
                <h3>Select a Class</h3>
                <p>Please select a class from the dropdown above to view the scoreboard.</p>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    setupColumnToggle();
    setupExportFunctions();
});

function setupColumnToggle() {
    const toggleBtn = document.getElementById('columnToggleBtn');
    const toggleMenu = document.getElementById('columnToggleMenu');
    const showAllBtn = document.getElementById('showAllColumns');
    const hideAllBtn = document.getElementById('hideAllColumns');
    
    if (!toggleBtn || !toggleMenu) return;
    
    // Toggle menu visibility
    toggleBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        toggleMenu.style.display = toggleMenu.style.display === 'none' ? 'block' : 'none';
    });
    
    // Close menu when clicking outside
    document.addEventListener('click', function(e) {
        if (!toggleMenu.contains(e.target) && e.target !== toggleBtn) {
            toggleMenu.style.display = 'none';
        }
    });
    
    // Handle individual column toggles
    const checkboxes = toggleMenu.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const columnClass = this.id.replace('col-', '');
            const columns = document.querySelectorAll(`.col-${columnClass}`);
            
            columns.forEach(col => {
                if (this.checked) {
                    col.classList.remove('column-hidden');
                } else {
                    col.classList.add('column-hidden');
                }
            });
        });
    });
    
    // Show all columns
    if (showAllBtn) {
        showAllBtn.addEventListener('click', function() {
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
                const columnClass = checkbox.id.replace('col-', '');
                const columns = document.querySelectorAll(`.col-${columnClass}`);
                columns.forEach(col => col.classList.remove('column-hidden'));
            });
        });
    }
    
    // Hide all columns (except student name)
    if (hideAllBtn) {
        hideAllBtn.addEventListener('click', function() {
            checkboxes.forEach(checkbox => {
                if (checkbox.id !== 'col-student') {
                    checkbox.checked = false;
                    const columnClass = checkbox.id.replace('col-', '');
                    const columns = document.querySelectorAll(`.col-${columnClass}`);
                    columns.forEach(col => col.classList.add('column-hidden'));
                }
            });
        });
    }
}

function setupExportFunctions() {
    // Export functions will be handled by the existing controller methods
}

function exportData(format) {
    const classId = '{{ $selectedClass ? $selectedClass->id : "" }}';
    if (!classId) {
        alert('Please select a class first');
        return;
    }
    
    let url;
    if (format === 'csv') {
        url = '{{ route("admin.scoreboard.export.csv") }}?class_id=' + classId;
    } else if (format === 'excel') {
        url = '{{ route("admin.scoreboard.export.excel") }}?class_id=' + classId;
    } else {
        // Fallback to the general export route
        url = '{{ route("admin.scoreboard.export", ["format" => "csv"]) }}'.replace('csv', format) + '?class_id=' + classId;
    }
    
    console.log('Exporting to:', url);
    window.location.href = url;
}

function printTable() {
    const table = document.getElementById('scoreboardTable');
    if (!table) return;
    
    const className = '{{ $selectedClass ? $selectedClass->name : "All Classes" }}';
    const printWindow = window.open('', '_blank');
    
    printWindow.document.write(`
        <html>
            <head>
                <title>Scoreboard - ${className}</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    h1 { color: #333; text-align: center; margin-bottom: 30px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; font-weight: bold; }
                    .column-hidden { display: none !important; }
                    .position-badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; }
                    .position-1 { background: #fff3cd; }
                    .position-2 { background: #e2e3e5; }
                    .position-3 { background: #ffeaa7; }
                    .position-other { background: #e3f2fd; }
                </style>
            </head>
            <body>
                <h1>Performance Scoreboard - ${className}</h1>
                <p>Generated on: ${new Date().toLocaleDateString()}</p>
                ${table.outerHTML}
            </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
    printWindow.close();
}
</script>
@endpush