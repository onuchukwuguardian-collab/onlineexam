<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Subject;
use App\Models\Question;
use App\Models\UserScore;
use App\Models\UserAnswer;
use App\Models\ExamSession;
use App\Models\ExamSecurityViolation;
use App\Models\ExamBan;

class SystemResetController extends Controller
{
    /**
     * System Management Dashboard
     */
    public function index()
    {
        // Get system statistics
        $stats = [
            'total_students' => User::where('role', 'student')->count(),
            'total_exams' => UserScore::count(),
            'total_questions' => Question::count(),
            'total_answers' => UserAnswer::count(),
            'active_sessions' => ExamSession::where('is_active', true)->count(),
            'security_violations' => ExamSecurityViolation::count(),
            'active_bans' => ExamBan::where('is_active', true)->count(),
            'database_size' => $this->getDatabaseSize(),
            'last_backup' => 'Never', // You can implement this later
            'backup_count' => 0, // You can implement this later
        ];
        
        return view('admin.system-reset.index', compact('stats'));
    }
    
    /**
     * Reset all exam data (DANGEROUS)
     */
    public function resetAllExamData(Request $request)
    {
        $request->validate([
            'confirmation' => 'required|in:RESET_ALL_DATA',
            'reason' => 'required|string|max:500'
        ]);
        
        $admin = auth()->user();
        
        DB::beginTransaction();
        try {
            // Count records before deletion
            $counts = [
                'scores' => UserScore::count(),
                'answers' => UserAnswer::count(),
                'sessions' => ExamSession::count(),
                'violations' => ExamSecurityViolation::count(),
                'bans' => ExamBan::count(),
            ];
            
            // Delete all exam-related data
            UserScore::truncate();
            UserAnswer::truncate();
            ExamSession::truncate();
            ExamSecurityViolation::truncate();
            ExamBan::truncate();
            
            // Log the system reset
            Log::critical('SYSTEM RESET: All exam data deleted', [
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'reason' => $request->reason,
                'deleted_counts' => $counts,
                'reset_at' => now(),
                'ip_address' => $request->ip()
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'All exam data has been successfully reset.',
                'deleted_counts' => $counts
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reset system data: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset system data. Please try again.'
            ], 500);
        }
    }
    
    /**
     * Clear cache and optimize system
     */
    public function optimizeSystem(Request $request)
    {
        $admin = auth()->user();
        
        try {
            // Clear various caches
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            
            // Optimize for production
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            
            Log::info('System optimization completed', [
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'optimized_at' => now()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'System has been optimized successfully.'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to optimize system: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to optimize system. Please try again.'
            ], 500);
        }
    }
    
    /**
     * Create system backup
     */
    public function createBackup(Request $request)
    {
        $admin = auth()->user();
        
        try {
            $backupData = [
                'created_at' => now()->toISOString(),
                'created_by' => $admin->name,
                'users' => User::all()->toArray(),
                'subjects' => Subject::all()->toArray(),
                'scores' => UserScore::with(['user:id,name', 'subject:id,name'])->get()->toArray(),
                'sessions' => ExamSession::with(['user:id,name', 'subject:id,name'])->get()->toArray(),
                'violations' => ExamSecurityViolation::with(['user:id,name', 'subject:id,name'])->get()->toArray(),
                'bans' => ExamBan::with(['user:id,name', 'subject:id,name'])->get()->toArray(),
            ];
            
            $filename = 'system_backup_' . now()->format('Y_m_d_H_i_s') . '.json';
            $path = 'backups/' . $filename;
            
            Storage::disk('local')->put($path, json_encode($backupData, JSON_PRETTY_PRINT));
            
            Log::info('System backup created', [
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'backup_file' => $filename,
                'backup_size' => Storage::disk('local')->size($path),
                'created_at' => now()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'System backup created successfully.',
                'filename' => $filename
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to create backup: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create backup. Please try again.'
            ], 500);
        }
    }
    
    /**
     * List available backups
     */
    public function listBackups()
    {
        try {
            $files = Storage::disk('local')->files('backups');
            $backups = [];
            
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                    $backups[] = [
                        'filename' => basename($file),
                        'size' => $this->formatBytes(Storage::disk('local')->size($file)),
                        'created_at' => date('Y-m-d H:i:s', Storage::disk('local')->lastModified($file))
                    ];
                }
            }
            
            return response()->json([
                'success' => true,
                'backups' => $backups
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to list backups.'
            ], 500);
        }
    }
    
    /**
     * Download backup file
     */
    public function downloadBackup($filename)
    {
        $path = 'backups/' . $filename;
        
        if (!Storage::disk('local')->exists($path)) {
            abort(404, 'Backup file not found.');
        }
        
        return Storage::disk('local')->download($path);
    }
    
    /**
     * Clean up old sessions and temporary data
     */
    public function cleanupSystem(Request $request)
    {
        $admin = auth()->user();
        
        DB::beginTransaction();
        try {
            // Clean up expired sessions
            $expiredSessions = ExamSession::where('expires_at', '<', now())
                ->where('is_active', true)
                ->count();
            
            ExamSession::where('expires_at', '<', now())
                ->where('is_active', true)
                ->update(['is_active' => false]);
            
            // Clean up old violations (older than 1 year)
            $oldViolations = ExamSecurityViolation::where('occurred_at', '<', now()->subYear())
                ->count();
            
            ExamSecurityViolation::where('occurred_at', '<', now()->subYear())->delete();
            
            Log::info('System cleanup completed', [
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'expired_sessions_cleaned' => $expiredSessions,
                'old_violations_cleaned' => $oldViolations,
                'cleaned_at' => now()
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => "Cleanup completed. Cleaned {$expiredSessions} expired sessions and {$oldViolations} old violations."
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to cleanup system: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to cleanup system. Please try again.'
            ], 500);
        }
    }
    
    /**
     * Get database size (approximate)
     */
    private function getDatabaseSize()
    {
        try {
            $size = DB::select("
                SELECT 
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
            ")[0]->size_mb ?? 0;
            
            return $size . ' MB';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }
    
    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}