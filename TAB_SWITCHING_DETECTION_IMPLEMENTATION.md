# Tab Switching Detection and Auto-Logout Implementation

## 🎯 **Feature Overview**

This implementation adds a powerful security feature that **automatically detects when students switch tabs during exams** and immediately logs them out with progressive warnings and violation recording.

## ⚡ **How It Works**

### **Immediate Detection**
- Student switches to another tab/window during exam
- System **instantly detects** the switch using browser APIs
- Progress is **automatically saved** before any action
- **Security violation is recorded** in database with full details
- Student is **immediately logged out** and redirected to login
- **Critical warning message** is displayed explaining the violation

### **Progressive Warning System**
1. **First Violation**: Warning about logout and permanent recording
2. **Second Violation**: Final warning about potential account lock
3. **Third Violation**: Account locked for that subject

## 🔧 **Technical Implementation**

### **Frontend Detection (JavaScript)**
```javascript
// Detects tab switching
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        handleTabSwitch(); // Immediate logout
    }
});

// Detects window switching  
window.addEventListener('blur', function() {
    if (!document.hasFocus()) {
        handleTabSwitch(); // Immediate logout
    }
});

// Warns about keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if ((e.altKey && e.key === 'Tab') || (e.ctrlKey && e.key === 'Tab')) {
        showCriticalWarning('Tab switching will result in immediate logout!');
    }
});
```

### **Backend Processing (PHP)**
```php
public function recordSecurityViolation(Request $request)
{
    // Validate exam session
    // Record violation with metadata
    // Count violations for progressive warnings
    // Force logout for tab_switch violations
    // Return appropriate warning message
}
```

### **Database Storage**
```sql
CREATE TABLE exam_security_violations (
    id BIGINT PRIMARY KEY,
    user_id BIGINT,
    subject_id BIGINT, 
    exam_session_id BIGINT,
    violation_type VARCHAR(255), -- 'tab_switch'
    description TEXT,
    metadata JSON, -- Browser info, screen size, etc.
    occurred_at TIMESTAMP,
    ip_address VARCHAR(255),
    user_agent TEXT
);
```

## 🛡️ **Security Features**

### **What Gets Detected**
- ✅ **Tab Switching**: Opening new tabs, clicking other tabs
- ✅ **Window Switching**: Alt+Tab to other applications  
- ✅ **Browser Focus Loss**: Clicking outside browser window
- ✅ **Navigation Attempts**: Trying to leave exam page

### **What Gets Recorded**
- 👤 **Student Identity**: User ID and session details
- 📚 **Exam Context**: Subject and specific exam session
- ⏰ **Exact Timestamp**: When violation occurred
- 🌐 **Technical Details**: IP address, browser, screen resolution
- 📝 **Violation Description**: Human-readable explanation
- 🔢 **Violation Count**: Progressive tracking for warnings

### **Immediate Consequences**
- 🚪 **Instant Logout**: Session terminated immediately
- 💾 **Progress Saved**: Current answers preserved before logout
- ⚠️ **Critical Warning**: Full-screen warning message displayed
- 📊 **Database Record**: Permanent violation record created
- 🔄 **Forced Redirect**: Automatic redirect to login page

## 📋 **Progressive Warning Messages**

### **First Violation**
```
⚠️ SECURITY VIOLATION DETECTED ⚠️

WARNING: Tab switching detected! You have been logged out. 
If you do this again, your account will be LOCKED and this 
violation will be recorded.

This incident has been recorded and reported to administrators.
```

### **Second Violation**  
```
⚠️ SECURITY VIOLATION DETECTED ⚠️

FINAL WARNING: Second tab switch violation! One more violation 
and your account will be PERMANENTLY LOCKED for this subject.

This incident has been recorded and reported to administrators.
```

### **Third Violation**
```
⚠️ SECURITY VIOLATION DETECTED ⚠️

ACCOUNT LOCKED: Too many security violations. Contact administrator.

This incident has been recorded and reported to administrators.
```

## 🧪 **Testing Instructions**

### **Test Basic Tab Switch Detection**
1. **Start Exam**: Log in as student and begin any exam
2. **Answer Questions**: Complete 2-3 questions normally
3. **Switch Tab**: Open new tab (Ctrl+T) or click existing tab
4. **Verify Results**:
   - ✅ Critical warning appears immediately
   - ✅ Student is logged out automatically  
   - ✅ Redirected to login page
   - ✅ Can log back in and continue exam from where left off
   - ✅ Violation recorded in database

### **Test Window Switch Detection**
1. **Start Exam**: Begin exam as student
2. **Switch Application**: Click on another program or Alt+Tab
3. **Verify Results**:
   - ✅ Same logout behavior as tab switching
   - ✅ Violation recorded as window blur

### **Test Progressive Warnings**
1. **First Violation**: Switch tabs, get logged out, see first warning
2. **Log Back In**: Return to exam, continue from same position
3. **Second Violation**: Switch tabs again, see "FINAL WARNING"
4. **Third Violation**: Switch tabs again, see "ACCOUNT LOCKED"

### **Test Keyboard Shortcut Warnings**
1. **Start Exam**: Begin exam normally
2. **Try Alt+Tab**: Press Alt+Tab while in exam
3. **Verify Results**:
   - ✅ Warning appears about tab switching consequences
   - ✅ If you proceed, normal logout occurs

## 📊 **Administrative Benefits**

### **Security Monitoring**
- 📈 **Violation Reports**: Track which students attempt to cheat
- 🔍 **Pattern Analysis**: Identify repeat offenders
- 📋 **Audit Trail**: Complete forensic record of all attempts
- ⚖️ **Evidence Collection**: Data for academic integrity cases

### **Automatic Enforcement**
- 🤖 **No Manual Monitoring**: System enforces rules automatically
- ⚡ **Instant Response**: Violations handled immediately
- 📏 **Consistent Rules**: Same enforcement for all students
- 🛡️ **Deterrent Effect**: Students know they're being monitored

### **Fair Testing Environment**
- 🎯 **Level Playing Field**: Same restrictions for everyone
- 🔒 **Secure Exams**: Prevents common cheating method
- 📝 **Clear Consequences**: Students know the rules upfront
- 🏆 **Academic Integrity**: Maintains exam credibility

## 🗃️ **Database Schema**

### **exam_security_violations Table**
| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT | Primary key |
| `user_id` | BIGINT | Student who violated |
| `subject_id` | BIGINT | Which exam subject |
| `exam_session_id` | BIGINT | Specific exam session |
| `violation_type` | VARCHAR | Type: 'tab_switch', 'window_blur', etc. |
| `description` | TEXT | Human-readable description |
| `metadata` | JSON | Technical details (browser, screen, etc.) |
| `occurred_at` | TIMESTAMP | Exact time of violation |
| `ip_address` | VARCHAR | Student's IP address |
| `user_agent` | TEXT | Browser and OS information |

### **Sample Violation Record**
```json
{
    "user_id": 123,
    "subject_id": 45,
    "exam_session_id": 789,
    "violation_type": "tab_switch",
    "description": "Student switched away from exam tab. Violation #1",
    "metadata": {
        "timestamp": "2025-08-23T10:30:45.123Z",
        "violation_count": 1,
        "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)...",
        "screen_resolution": "1920x1080",
        "window_size": "1200x800"
    },
    "occurred_at": "2025-08-23 10:30:45",
    "ip_address": "192.168.1.100",
    "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)..."
}
```

## 🔐 **Security Considerations**

### **What This Prevents**
- ✅ **Tab Switching Cheating**: Looking up answers in other tabs
- ✅ **Application Switching**: Using other programs during exam
- ✅ **Multi-Tab Strategies**: Opening multiple exam instances
- ✅ **Focus Loss Cheating**: Any attempt to leave exam environment

### **Limitations to Consider**
- ⚠️ **Second Devices**: Cannot detect phones, tablets, other computers
- ⚠️ **Virtual Machines**: Cannot detect VM usage
- ⚠️ **Screen Sharing**: Cannot detect remote assistance
- ⚠️ **System Notifications**: May cause false positives

### **Recommended Additional Measures**
- 📹 **Webcam Proctoring**: For high-stakes exams
- 🔒 **Lockdown Browser**: Maximum security environment
- 👥 **Physical Supervision**: In-person monitoring when possible
- 📱 **Device Restrictions**: Limit to specific devices/networks

## 📁 **Files Created/Modified**

### **New Files**
- `database/migrations/2025_08_23_000001_create_exam_security_violations_table.php`
- `app/Models/ExamSecurityViolation.php`
- `test_tab_switching_detection.php`

### **Modified Files**
- `routes/web.php` - Added security violation route
- `app/Http/Controllers/ExamController.php` - Added violation handling
- `resources/views/user/exam_simple.blade.php` - Added tab detection JavaScript

## 🚀 **Performance Impact**

- **Minimal Overhead**: Lightweight event listeners
- **Fast Response**: Instant detection and processing
- **Efficient Storage**: Indexed database for quick queries
- **No External Dependencies**: Uses built-in browser APIs

## 🎓 **Student Experience**

### **Normal Exam Taking**
- No impact on regular exam experience
- All existing functionality preserved
- Same performance and responsiveness

### **When Violation Occurs**
- Clear, immediate feedback about violation
- Progress automatically saved before logout
- Can log back in and continue from same position
- Understands consequences of repeated violations

## 🏆 **Results**

This implementation creates a **robust, fair, and secure exam environment** that:

- ✅ **Prevents Common Cheating**: Tab switching is a primary cheating method
- ✅ **Maintains Academic Integrity**: Students know they're monitored
- ✅ **Provides Clear Consequences**: Progressive warning system
- ✅ **Creates Audit Trail**: Complete record for investigations
- ✅ **Ensures Fairness**: Same rules applied consistently
- ✅ **Reduces Admin Burden**: Automatic enforcement

**The exam system is now significantly more secure while maintaining a smooth experience for honest students!** 🎉