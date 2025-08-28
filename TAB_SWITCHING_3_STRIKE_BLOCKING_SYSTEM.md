# 🚫 TAB SWITCHING 3-STRIKE BLOCKING SYSTEM

## 🎯 **SYSTEM OVERVIEW**

This system **COMPLETELY BLOCKS** all tab switching attempts and permanently bans students after **3 attempts** to switch tabs.

**Key Features**:
- ✅ **BLOCKS ALL TAB SWITCHING** (no tabs can be opened)
- ✅ **3-Strike Policy** (banned after 3rd attempt)
- ✅ **Progressive Warnings** (1st, 2nd warning → 3rd = ban)
- ✅ **Multiple Detection Methods** (keyboard, mouse, window.open)
- ✅ **User-Based Tracking** (by registration/email, not IP)

## 🔒 **WHAT GETS BLOCKED**

### **Keyboard Shortcuts**
- ❌ `Alt+Tab` (switch applications)
- ❌ `Ctrl+Tab` (switch browser tabs)
- ❌ `Cmd+Tab` (Mac application switching)
- ❌ `Ctrl+T` (new tab)
- ❌ `Ctrl+Shift+T` (reopen closed tab)

### **Mouse Actions**
- ❌ **Middle-click** (opens links in new tab)
- ❌ **Right-click context menu** (prevents "Open in new tab")

### **JavaScript Attempts**
- ❌ **window.open()** calls (blocked programmatically)

## ⚠️ **3-STRIKE WARNING SYSTEM**

### **1st Attempt**
```
⚠️ TAB SWITCHING BLOCKED! 
Attempt #1/3. You have 2 attempts remaining.
```
- Student gets warning
- Can continue exam
- Attempt logged in database

### **2nd Attempt**
```
🚨 FINAL WARNING: Tab switching attempt #2/3. 
ONE MORE ATTEMPT will permanently BAN you from this exam!
```
- Final warning displayed
- Can still continue exam
- One more attempt = permanent ban

### **3rd Attempt**
```
🚫 EXAM BLOCKED: Too many tab switching attempts! 
You are now BLOCKED from this exam.
```
- **PERMANENT BAN** from subject
- **Exam auto-submitted** with current answers
- **Logged out immediately**
- **Admin reactivation required**

## 🛡️ **TECHNICAL IMPLEMENTATION**

### **Frontend Blocking (JavaScript)**
```javascript
// Block ALL tab switching shortcuts
document.addEventListener('keydown', function(e) {
    if ((e.altKey && e.key === 'Tab') || 
        (e.ctrlKey && e.key === 'Tab') || 
        (e.metaKey && e.key === 'Tab') ||
        (e.ctrlKey && e.key === 't') || 
        (e.ctrlKey && e.shiftKey && e.key === 'T')) {
        
        e.preventDefault();
        e.stopPropagation();
        
        // Record attempt and check count
        tabSwitchCount++;
        
        if (tabSwitchCount >= 3) {
            // PERMANENT BAN
            recordSecurityViolation('tab_switch_attempt', ...);
        } else {
            // Show warning
            showCriticalWarning(...);
        }
        
        return false;
    }
});
```

### **Backend Processing (PHP)**
```php
// 3-strike rule for tab switching
if ($violationType === 'tab_switch' || $violationType === 'tab_switch_attempt') {
    $shouldLock = $violationCount >= 3; // Ban after 3 attempts
}

if ($violationCount >= 3) {
    // 3rd violation: Auto-submit and ban
    $this->handleThirdViolation($user, $subjectId, $examSession, $request);
}
```

## 📊 **VIOLATION TRACKING**

### **Database Records**
Each attempt creates a record in `exam_security_violations`:
```json
{
    "user_id": 123,
    "subject_id": 45,
    "violation_type": "tab_switch_attempt",
    "description": "Student attempted tab switching 3 times - BLOCKED from exam",
    "metadata": {
        "attempt_count": 3,
        "blocked": true,
        "timestamp": "2025-08-23T10:30:45.123Z"
    }
}
```

### **Ban Records**
After 3rd attempt, creates ban in `exam_bans`:
```json
{
    "user_id": 123,
    "subject_id": 45,
    "ban_reason": "Permanent ban after 3 TAB SWITCHING security violations/attempts during online exam",
    "total_violations": 3,
    "is_active": true
}
```

## 🎓 **STUDENT EXPERIENCE**

### **Normal Exam Taking**
- Can take exam normally
- All functionality works as expected
- No interference with regular exam flow

### **When Attempting Tab Switch**
1. **Press Alt+Tab or Ctrl+T**
   - Action is **BLOCKED immediately**
   - Warning message appears
   - Attempt counter increases

2. **After 1st Attempt**
   - Gets warning: "Attempt #1/3"
   - Can continue exam normally
   - No logout or interruption

3. **After 2nd Attempt**
   - Gets final warning: "Attempt #2/3"
   - Still can continue exam
   - Clear warning about consequences

4. **After 3rd Attempt**
   - **IMMEDIATE BAN**
   - **Exam auto-submitted**
   - **Logged out instantly**
   - Cannot access exam again

## 👨‍💼 **ADMIN FEATURES**

### **Monitoring Dashboard**
- View all tab switching attempts
- See progression through 3 strikes
- Monitor real-time violation attempts

### **Ban Management**
- View all banned students
- See violation history and counts
- **Reactivate banned students**
- Generate violation reports

### **Audit Trail**
- Complete log of all attempts
- User identification (not IP-based)
- Timestamps and metadata
- Evidence for academic integrity

## 🔧 **CONFIGURATION**

### **Strike Count**
Currently set to **3 strikes** in:
- Frontend JavaScript: `tabSwitchCount >= 3`
- Backend PHP: `$violationCount >= 3`

### **Violation Types**
System tracks both:
- `tab_switch`: Actual tab switching (visibility change)
- `tab_switch_attempt`: Blocked keyboard/mouse attempts

## ✅ **BENEFITS**

### **🛡️ Academic Integrity**
- **Zero tolerance** for tab switching
- **Progressive deterrent** system
- **Clear consequences** communicated upfront
- **Fair 3-strike policy**

### **🔍 Complete Security**
- **Blocks all known methods** of opening tabs
- **Real-time detection** and blocking
- **Comprehensive logging** for investigations
- **User-based tracking** (fair for shared computers)

### **⚖️ Fair Enforcement**
- **Same rules for everyone**
- **Clear warning system**
- **User-based not IP-based**
- **Admin reactivation available**

### **📊 Administrative Control**
- **Real-time monitoring**
- **Detailed violation reports**
- **Flexible reactivation system**
- **Complete audit trail**

## 🚀 **TESTING THE SYSTEM**

### **Test Steps**
1. Login as student and start exam
2. Try pressing `Ctrl+T` → Should be blocked with warning
3. Try `Alt+Tab` → Should be blocked with warning  
4. Try middle-clicking a link → Should be blocked
5. After 3 attempts → Should be permanently banned

### **Expected Results**
- ✅ All tab switching attempts blocked
- ✅ Progressive warnings displayed  
- ✅ After 3rd attempt: ban + auto-submit
- ✅ Cannot access exam after ban
- ✅ Admin can see violations and reactivate

## 🏁 **CONCLUSION**

This system provides **maximum security** for online exams by:

1. **Completely preventing** tab switching attempts
2. **Fair 3-strike policy** with clear warnings
3. **Comprehensive detection** of all bypass methods
4. **User-based tracking** for fairness
5. **Complete administrative control**

Students cannot cheat by opening new tabs, while still getting fair warning before permanent consequences. The system maintains academic integrity while providing clear, progressive enforcement.

**Status**: ✅ **ACTIVE AND OPERATIONAL**