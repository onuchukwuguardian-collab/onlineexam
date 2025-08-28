# 🎯 USER-BASED VIOLATION TRACKING SYSTEM

## 🚨 **IMPORTANT CLARIFICATION**

The 3-strike security violation system uses **USER-BASED TRACKING**, NOT IP-based tracking. This ensures fair and accurate enforcement even when multiple students share the same computer or network.

## 🔍 **HOW VIOLATIONS ARE TRACKED**

### ✅ **TRACKED BY (Primary Identifiers):**
- 📧 **Student Email Address**: Unique identifier
- 🆔 **User ID**: Database primary key  
- 📝 **Registration Number**: School-assigned student ID
- 👤 **Student Account**: Individual login credentials

### ❌ **NOT TRACKED BY:**
- 🌐 **IP Address**: Only logged for audit trail
- 💻 **Device/Computer**: Multiple students can use same PC
- 🏠 **Location**: Students can take exams from different places
- 📱 **Browser**: Same browser can be used by different students

## 📊 **DATABASE STRUCTURE**

### **exam_security_violations Table**
```sql
- user_id (PRIMARY TRACKING FIELD)
- subject_id (PRIMARY TRACKING FIELD) 
- violation_type (tab_switch, copy_attempt, etc.)
- occurred_at (timestamp)
- ip_address (AUDIT ONLY - not used for counting)
- user_agent (AUDIT ONLY)
- metadata (enhanced user identification)
```

### **Violation Counting Query**
```php
// Violations are counted per student per subject
ExamSecurityViolation::where('user_id', $studentId)
    ->where('subject_id', $subjectId) 
    ->where('violation_type', 'tab_switch')
    ->count();
```

## 🎓 **SHARED COMPUTER SCENARIO**

### **Example: Computer Lab Usage**
```
🖥️ Computer #1 in Lab:
├── Student A (john@school.edu) - 0 violations ✅
├── Student B (mary@school.edu) - 2 violations ⚠️  
├── Student C (mike@school.edu) - 3 violations ❌ BANNED
└── Student D (sara@school.edu) - 1 violation ⚠️

Result: Only Student C is banned, others can continue exams
```

### **What This Means:**
- ✅ **Multiple students** can use the same computer safely
- ✅ **Each student** has their own violation count  
- ✅ **Fair enforcement** regardless of shared devices
- ✅ **No false positives** from shared IP addresses

## 🔐 **ENHANCED USER IDENTIFICATION**

### **Violation Recording Includes:**
```json
{
  "user_identification": {
    "user_name": "John Doe",
    "user_email": "john.doe@school.edu", 
    "registration_number": "50001",
    "unique_id": "[REDACTED]"
  },
  "violation_tracked_by": "user_credentials",
  "tracking_method": "user_account_based"
}
```

### **Log Entries Show Clear Tracking:**
```
CRITICAL: STUDENT BANNED FOR TAB SWITCHING (USER-BASED TRACKING): 
User 123 (John Doe, john@school.edu) permanently banned from subject 45 
after 3 TAB SWITCHING violations. Tracking is by student account credentials, not IP address.
```

## 📋 **VIOLATION MESSAGES**

### **Student-Specific Messages:**
- ⚠️ **1st Violation**: `"FIRST TAB SWITCH VIOLATION (Account: john@school.edu): ...Violations are tracked by your student account, not device."`
- 🚨 **2nd Violation**: `"SECOND TAB SWITCH VIOLATION (Account: john@school.edu): ...This is tracked by your student credentials."`  
- 🔒 **3rd Violation**: `"THIRD TAB SWITCH VIOLATION (Account: john@school.edu): ...Ban is linked to your student credentials."`

## 🏫 **SCHOOL DEPLOYMENT BENEFITS**

### **Computer Labs**
- ✅ Multiple students per computer
- ✅ Fair violation tracking
- ✅ Individual accountability

### **Home/Shared Networks**  
- ✅ Family computers
- ✅ Shared internet connections
- ✅ Accurate student identification

### **Mobile Devices**
- ✅ BYOD (Bring Your Own Device)
- ✅ Personal vs shared devices
- ✅ Consistent tracking across platforms

## 🔧 **TECHNICAL IMPLEMENTATION**

### **Core Tracking Logic**
```php
// Count violations by user account, not IP
public static function getViolationCount($userId, $subjectId, $violationType = null)
{
    $query = self::where('user_id', $userId)      // ✅ User-based
                ->where('subject_id', $subjectId); // ✅ Subject-specific
    
    if ($violationType) {
        $query->where('violation_type', $violationType);
    }
    
    return $query->count();
}
```

### **Ban Creation**
```php
// Bans are linked to user account
ExamBan::createViolationBan(
    $userId,        // ✅ Specific student account
    $subjectId,     // ✅ Specific subject
    $violations,    // ✅ All violations for THIS student
    'Permanent ban after 3 TAB SWITCHING security violations during online exam'
);
```

## 🎯 **SUMMARY**

### **Key Points:**
1. 🎯 **User-Centric**: Each student account has independent violation tracking
2. 🔐 **Credential-Based**: Uses email, user ID, and registration number
3. 🌐 **IP-Independent**: IP addresses logged for audit, not counting
4. 🏫 **School-Friendly**: Perfect for shared computer environments
5. ⚖️ **Fair & Accurate**: No false violations from shared devices

### **Why This Matters:**
- 📚 **Educational Integrity**: Each student accountable for their own actions
- 💻 **Practical Implementation**: Works in real school environments  
- 🔍 **Audit Compliance**: Complete tracking with user identification
- 🛡️ **Security**: Cannot be bypassed by changing devices/locations

The system ensures academic integrity while being practical for real-world educational environments where computer sharing is common.