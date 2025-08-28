<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator; // For re-validation if needed & bulk
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rule; // <-- CORRECT IMPORT FOR Rule facade
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('classModel')->orderBy('name');

        if ($request->filled('role_filter') && in_array($request->role_filter, ['admin', 'user'])) {
            $query->where('role', $request->role_filter);
        }
        if ($request->filled('class_filter')) {
            $query->where('class_id', $request->class_filter);
        }
        if ($request->filled('search_user')) {
            $searchTerm = $request->search_user;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%")
                    ->orWhere('registration_number', 'like', "%{$searchTerm}%")
                    ->orWhere('unique_id', 'like', "%{$searchTerm}%");
            });
        }

        $users = $query->paginate(15)->withQueryString();
        $classes = ClassModel::orderBy('name')->get();
        return view('admin.users.index', compact('users', 'classes'));
    }

    public function create()
    {
        $classes = ClassModel::orderBy('name')->get();
        return view('admin.users.create', compact('classes'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'string', Rule::in(['user', 'admin'])],
            'class_id' => ['nullable', 'required_if:role,user', 'exists:classes,id'],
            'registration_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('users', 'registration_number')->where(function ($query) use ($request) {
                    return !empty($request->registration_number); // Only check if provided, auto-gen will be checked later
                })
            ],
            'unique_id' => ['nullable', 'string', 'max:50', Rule::unique('users', 'unique_id')],
        ]);

        $registrationNumber = $validatedData['registration_number'] ?? null;

        if ($validatedData['role'] === 'user' && $validatedData['class_id'] && empty($registrationNumber)) {
            $registrationNumber = $this->generateUniqueRegistrationNumber($validatedData['class_id']);
            // Re-validate the generated registration number for uniqueness
            $tempValidator = Validator::make(['registration_number' => $registrationNumber], [
                'registration_number' => 'unique:users,registration_number'
            ]);
            if ($tempValidator->fails()) {
                return back()->withInput()->withErrors(['registration_number' => 'Auto-generated registration number conflicted. Please try again or enter manually.']);
            }
        } elseif ($validatedData['role'] !== 'user') {
            $registrationNumber = null; // Ensure admins don't get a reg number by mistake
        }

        User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'role' => $validatedData['role'],
            'class_id' => $validatedData['role'] === 'user' ? $validatedData['class_id'] : null,
            'registration_number' => $registrationNumber, // Use the processed $registrationNumber
            'unique_id' => $validatedData['unique_id'] ?? null,
            'email_verified_at' => now(),
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $classes = ClassModel::orderBy('name')->get();
        return view('admin.users.edit', compact('user', 'classes'));
    }

    public function update(Request $request, User $user)
    {
        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', 'string', Rule::in(['user', 'admin'])],
            'class_id' => ['nullable', 'required_if:role,user', 'exists:classes,id'],
            'registration_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('users', 'registration_number')->ignore($user->id)->where(function ($query) use ($request) {
                    return !empty($request->registration_number);
                })
            ],
            'unique_id' => ['nullable', 'string', 'max:50', Rule::unique('users', 'unique_id')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        $dataToUpdate = [
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'role' => $validatedData['role'],
            'unique_id' => $validatedData['unique_id'] ?? null,
        ];

        if ($validatedData['role'] === 'user') {
            $dataToUpdate['class_id'] = $validatedData['class_id'];
            if (empty($validatedData['registration_number']) && $validatedData['class_id']) {
                // Only generate if it was previously empty or if class changed and it's now empty
                if (empty($user->registration_number) || ($user->class_id != $validatedData['class_id'] && empty($validatedData['registration_number']))) {
                    $dataToUpdate['registration_number'] = $this->generateUniqueRegistrationNumber($validatedData['class_id']);
                    $tempValidator = Validator::make(['registration_number' => $dataToUpdate['registration_number']], [
                        'registration_number' => Rule::unique('users')->ignore($user->id)
                    ]);
                    if ($tempValidator->fails()) {
                        return back()->withInput()->withErrors(['registration_number' => 'Auto-generated registration number conflicted during update.']);
                    }
                } else {
                    $dataToUpdate['registration_number'] = $validatedData['registration_number'] ?? $user->registration_number; // Keep old one if not changed
                }
            } else {
                $dataToUpdate['registration_number'] = $validatedData['registration_number'] ?? null;
            }
        } else { // Admin role
            $dataToUpdate['class_id'] = null;
            $dataToUpdate['registration_number'] = null;
        }


        if (!empty($validatedData['password'])) {
            $dataToUpdate['password'] = Hash::make($validatedData['password']);
        }

        $user->update($dataToUpdate);
        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->isAdmin() && User::where('role', 'admin')->count() <= 1) {
            return redirect()->route('admin.users.index')->with('error', 'Cannot delete the last admin user.');
        }
        if ($user->id === Auth::id()) {
            return redirect()->route('admin.users.index')->with('error', 'You cannot delete your own account.');
        }
        if ($user->userScores()->exists()) { // Check if user has scores
            return redirect()->route('admin.users.index')->with('error', 'Cannot delete user. They have existing exam scores.');
        }
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }

    /**
     * Bulk delete multiple users
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'required|integer|exists:users,id'
        ]);

        $userIds = $request->user_ids;
        $currentUserId = Auth::id();
        $deletedCount = 0;
        $errors = [];

        // Check if trying to delete current user
        if (in_array($currentUserId, $userIds)) {
            $errors[] = 'You cannot delete your own account.';
            $userIds = array_diff($userIds, [$currentUserId]);
        }

        // Check admin count constraint
        $selectedAdmins = User::whereIn('id', $userIds)->where('role', 'admin')->pluck('id')->toArray();
        $totalAdmins = User::where('role', 'admin')->count();
        
        if (!empty($selectedAdmins) && count($selectedAdmins) >= $totalAdmins) {
            $errors[] = 'Cannot delete all admin users. At least one admin must remain.';
            // Remove one admin from deletion to preserve at least one
            array_pop($selectedAdmins);
            $userIds = array_diff($userIds, [$selectedAdmins[0] ?? null]);
        }

        // Process deletions
        foreach ($userIds as $userId) {
            try {
                $user = User::find($userId);
                if (!$user) {
                    $errors[] = "User with ID {$userId} not found.";
                    continue;
                }

                // Check for existing exam scores
                if ($user->userScores()->exists()) {
                    $errors[] = "Cannot delete user '{$user->name}' - they have existing exam scores.";
                    continue;
                }

                // Check if trying to delete the last admin
                if ($user->isAdmin() && User::where('role', 'admin')->count() <= 1) {
                    $errors[] = "Cannot delete user '{$user->name}' - they are the last admin.";
                    continue;
                }

                $user->delete();
                $deletedCount++;
            } catch (\Exception $e) {
                $errors[] = "Failed to delete user with ID {$userId}: " . $e->getMessage();
            }
        }

        // Prepare response message
        $message = "";
        if ($deletedCount > 0) {
            $message = "Successfully deleted {$deletedCount} user(s).";
        }
        
        if (!empty($errors)) {
            $errorMessage = "Some users could not be deleted: " . implode(' ', $errors);
            if ($deletedCount > 0) {
                return redirect()->route('admin.users.index')
                    ->with('success', $message)
                    ->with('warning', $errorMessage);
            } else {
                return redirect()->route('admin.users.index')->with('error', $errorMessage);
            }
        }

        return redirect()->route('admin.users.index')->with('success', $message);
    }

    private function generateUniqueRegistrationNumber($classId, $attempt = 0)
    {
        $classModel = ClassModel::find($classId);
        if (!$classModel)
            return 'ERR' . strtoupper(Str::random(7));

        $prefix = (strtoupper($classModel->level_group) === 'JSS') ? '5' : '2';

        $lastUser = User::where('registration_number', 'like', $prefix . '%')
            ->selectRaw('CAST(REGEXP_SUBSTR(registration_number, "[0-9]+") AS UNSIGNED) as numeric_suffix_val')
            ->orderBy('numeric_suffix_val', 'desc')
            ->first();

        $nextNumericPart = $lastUser && !is_null($lastUser->numeric_suffix_val) ? $lastUser->numeric_suffix_val + 1 : 1;

        $regNo = $prefix . str_pad($nextNumericPart, 4, '0', STR_PAD_LEFT);

        if ($attempt > 0) {
            $regNo .= Str::upper(Str::random(1));
        }

        if (User::where('registration_number', $regNo)->exists()) {
            if ($attempt < 5) {
                return $this->generateUniqueRegistrationNumber($classId, $attempt + 1);
            } else {
                return $prefix . time() . Str::upper(Str::random(2));
            }
        }
        return $regNo;
    }

    public function showBulkUploadForm()
    {
        // $classes = ClassModel::orderBy('name')->get(); // Not strictly needed unless form has class pre-selection
        return view('admin.users.bulk_upload_form');
    }

    public function processBulkUpload(Request $request)
    {
        $request->validate(['user_csv' => 'required|file|mimes:csv,txt|max:5120']);
        $file = $request->file('user_csv');
        $filePath = $file->getRealPath();

        if (!($fileHandle = fopen($filePath, "r"))) {
            return redirect()->route('admin.users.bulkUploadForm')->with('error', 'Could not open the CSV file.');
        }

        // Read CSV header
        $csvHeader = fgetcsv($fileHandle);
        if (!$csvHeader) {
            fclose($fileHandle);
            return redirect()->route('admin.users.bulkUploadForm')->with('error', 'CSV file is empty or header is missing.');
        }
        
        // Clean and normalize headers
        $header = array_map(function($h) {
            return trim(strtolower(str_replace([' ', '-'], '_', $h)));
        }, $csvHeader);

        // Expected headers
        $expectedHeaders = ['name', 'email', 'password', 'role', 'class_name', 'registration_number', 'unique_id'];
        $missingHeaders = array_diff($expectedHeaders, $header);
        
        if (!empty($missingHeaders)) {
            fclose($fileHandle);
            return redirect()->route('admin.users.bulkUploadForm')
                ->with('error', 'CSV header mismatch. Missing headers: ' . implode(', ', $missingHeaders) . '. Found: ' . implode(', ', $header));
        }

        $importedCount = 0;
        $errors = [];
        $rowNumber = 1;
        $existingEmails = [];
        $existingRegNumbers = [];
        $existingUniqueIds = [];

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($fileHandle)) !== FALSE) {
                $rowNumber++;
                
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                
                // Ensure row has same number of columns as header
                if (count($row) !== count($header)) {
                    $errors[] = "Row {$rowNumber}: Column count mismatch. Expected " . count($header) . " columns, got " . count($row) . ".";
                    continue;
                }
                
                // Combine header with row data and trim all values
                $rowData = array_combine($header, array_map('trim', $row));
                
                // Validate required fields are not empty
                if (empty($rowData['name'])) {
                    $errors[] = "Row {$rowNumber}: Name is required.";
                    continue;
                }
                
                if (empty($rowData['password'])) {
                    $errors[] = "Row {$rowNumber}: Password is required.";
                    continue;
                }
                
                // Validate role first to determine email requirements
                $role = strtolower(trim($rowData['role']));
                if (!in_array($role, ['user', 'admin'])) {
                    $errors[] = "Row {$rowNumber}: Invalid role '{$rowData['role']}'. Must be 'user' or 'admin'.";
                    continue;
                }
                
                // Handle email - required for admins, optional for students
                $email = trim($rowData['email'] ?? '');
                if ($role === 'admin' && empty($email)) {
                    $errors[] = "Row {$rowNumber}: Email is required for admin users.";
                    continue;
                }
                
                // Generate email for students if not provided
                if ($role === 'user' && empty($email)) {
                    // Generate email from name and registration number
                    $namePart = strtolower(str_replace(' ', '.', $rowData['name']));
                    $regNumber = trim($rowData['registration_number'] ?? '');
                    if (!empty($regNumber)) {
                        $email = $namePart . '.' . $regNumber . '@student.school.com';
                    } else {
                        $email = $namePart . '.' . time() . rand(100, 999) . '@student.school.com';
                    }
                }
                
                // Validate email format if provided
                if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Row {$rowNumber}: Invalid email format '{$email}'.";
                    continue;
                }
                
                // Check for duplicate emails in CSV
                if (in_array(strtolower($email), $existingEmails)) {
                    $errors[] = "Row {$rowNumber}: Duplicate email '{$email}' found in CSV.";
                    continue;
                }
                
                // Check if email already exists in database
                if (User::where('email', $email)->exists()) {
                    $errors[] = "Row {$rowNumber}: Email '{$email}' already exists in database.";
                    continue;
                }
                
                $existingEmails[] = strtolower($email);
                
                // Role validation already done above
                
                // Validate class for students
                $classId = null;
                if ($role === 'user') {
                    if (empty($rowData['class_name'])) {
                        $errors[] = "Row {$rowNumber}: Class name is required for students.";
                        continue;
                    }
                    
                    $class = ClassModel::where('name', trim($rowData['class_name']))->first();
                    if (!$class) {
                        $errors[] = "Row {$rowNumber}: Class '{$rowData['class_name']}' not found.";
                        continue;
                    }
                    $classId = $class->id;
                }
                
                // Validate registration number
                $registrationNumber = trim($rowData['registration_number'] ?? '');
                if ($role === 'user' && !empty($registrationNumber)) {
                    // Check registration number format
                    if (strlen($registrationNumber) !== 10) {
                        $errors[] = "Row {$rowNumber}: Registration number must be exactly 10 digits. Got '{$registrationNumber}'.";
                        continue;
                    }
                    
                    // Check prefix based on class
                    $className = strtoupper(trim($rowData['class_name']));
                    $expectedPrefix = '';
                    if (strpos($className, 'JSS') === 0) {
                        $expectedPrefix = '5';
                    } elseif (strpos($className, 'SS') === 0) {
                        $expectedPrefix = '2';
                    }
                    
                    if (!empty($expectedPrefix) && substr($registrationNumber, 0, 1) !== $expectedPrefix) {
                        $errors[] = "Row {$rowNumber}: Registration number for {$className} must start with '{$expectedPrefix}'. Got '{$registrationNumber}'.";
                        continue;
                    }
                    
                    // Check for duplicate registration numbers in CSV
                    if (in_array($registrationNumber, $existingRegNumbers)) {
                        $errors[] = "Row {$rowNumber}: Duplicate registration number '{$registrationNumber}' found in CSV.";
                        continue;
                    }
                    
                    // Check if registration number already exists in database
                    if (User::where('registration_number', $registrationNumber)->exists()) {
                        $errors[] = "Row {$rowNumber}: Registration number '{$registrationNumber}' already exists in database.";
                        continue;
                    }
                    
                    $existingRegNumbers[] = $registrationNumber;
                }
                
                // Generate registration number if not provided for students
                if ($role === 'user' && empty($registrationNumber)) {
                    $registrationNumber = $this->generateUniqueRegistrationNumber($classId);
                }
                
                // Validate unique_id
                $uniqueId = trim($rowData['unique_id'] ?? '');
                if (!empty($uniqueId)) {
                    // Check for duplicate unique IDs in CSV
                    if (in_array($uniqueId, $existingUniqueIds)) {
                        $errors[] = "Row {$rowNumber}: Duplicate unique ID '{$uniqueId}' found in CSV.";
                        continue;
                    }
                    
                    // Check if unique ID already exists in database
                    if (User::where('unique_id', $uniqueId)->exists()) {
                        $errors[] = "Row {$rowNumber}: Unique ID '{$uniqueId}' already exists in database.";
                        continue;
                    }
                    
                    $existingUniqueIds[] = $uniqueId;
                }
                
                // Validate password length
                if (strlen($rowData['password']) < 6) {
                    $errors[] = "Row {$rowNumber}: Password must be at least 6 characters long.";
                    continue;
                }
                
                // Create user
                User::create([
                    'name' => $rowData['name'],
                    'email' => $email,
                    'password' => Hash::make($rowData['password']),
                    'role' => $role,
                    'class_id' => $classId,
                    'registration_number' => ($role === 'user') ? $registrationNumber : null,
                    'unique_id' => !empty($uniqueId) ? $uniqueId : null,
                    'email_verified_at' => now(),
                ]);
                
                $importedCount++;
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            if (is_resource($fileHandle)) {
                fclose($fileHandle);
            }
            return redirect()->route('admin.users.bulkUploadForm')
                ->with('error', 'An error occurred (Row approx. ' . $rowNumber . '): ' . $e->getMessage());
        } finally {
            if (is_resource($fileHandle)) {
                fclose($fileHandle);
            }
        }

        $feedbackMessage = "Bulk upload processed. Successfully imported: {$importedCount} users.";
        if (!empty($errors)) {
            $feedbackMessage .= " Some rows had errors and were skipped.";
            session()->flash('bulk_upload_errors_detailed', $errors);
        }
        
        return redirect()->route('admin.users.index')->with(empty($errors) ? 'success' : 'warning', $feedbackMessage);
    }
}
