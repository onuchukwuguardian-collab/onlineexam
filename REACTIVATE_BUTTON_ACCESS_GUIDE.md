# 🎯 HOW TO ACCESS THE REACTIVATE BUTTON - STEP BY STEP GUIDE

## 🚨 SYSTEM CONFIRMATION
Your 3-strike system is **correctly configured**:
- ✅ **1st Tab Switch**: Warning + Logout (can continue)
- ✅ **2nd Tab Switch**: Final Warning + Logout (can continue) 
- ✅ **3rd Tab Switch**: AUTO-SUBMIT + PERMANENT BAN (cannot continue)
- ✅ **Tab Switch Only**: Bans ONLY apply to tab switching, not other violations
- ✅ **Admin Only**: Only admins can reactivate banned students

## 🔓 ACCESSING THE REACTIVATE BUTTON

### **Step 1: Login as Admin**
- Must be logged in with an account that has `role = 'admin'`
- Non-admin users will get 403 Unauthorized error

### **Step 2: Navigate to Security Dashboard**
**Option A - Via Menu:**
1. Go to Admin Panel
2. Click "Security Violations" in the left sidebar menu

**Option B - Direct URL:**
- Visit: `http://your-domain/admin/security`

### **Step 3: Find Banned Students**
On the security dashboard, you'll see:
- 📊 Statistics showing total banned students
- 📋 List of all security violations
- 🔍 Search functionality to find specific students
- 🚫 Banned students will be clearly marked

### **Step 4: Access Ban Details**
**Currently you have 3 banned students:**

1. **Emeka Nwosu** (Basic Science)
   - Direct link: `/admin/security/bans/1`
   - Click "Manage" button next to his name

2. **Mium John** (Biology) 
   - Direct link: `/admin/security/bans/2`
   - Click "Manage" button next to his name

3. **Mium John** (Mathematics)
   - Direct link: `/admin/security/bans/3` 
   - Click "Manage" button next to his name

### **Step 5: Locate the Reactivate Button**
Once on the ban details page, you'll see:

**🟢 GREEN BUTTON** with text:
- "**Grant Mercy & Reactivate**" (in the actions panel)
- "**Reactivate Student**" (in the header)

**The button appears in TWO places:**
1. **Header Section**: Top right of the page
2. **Actions Panel**: Right sidebar under "Actions"

## 🎯 WHAT THE REACTIVATE BUTTON LOOKS like

```html
<!-- Header Button -->
<button class="bg-green-600 hover:bg-green-700 text-white">
    <i class="fas fa-unlock-alt"></i>
    Reactivate Student  
</button>

<!-- Actions Panel Button -->
<button class="bg-green-600 hover:bg-green-700 text-white">
    <i class="fas fa-unlock-alt"></i>
    Grant Mercy & Reactivate
</button>
```

## 📝 REACTIVATION PROCESS

### **Step 1: Click Reactivate Button**
- A modal window will appear with admin override warning

### **Step 2: Warning Confirmation**
You'll see:
```
⚠️ ADMIN OVERRIDE WARNING
You are about to reactivate a student who was permanently banned for 3 security violations. This action:
• Will allow the student to retake the exam
• Will be permanently logged for audit purposes  
• Should only be done if you believe mercy is warranted
```

### **Step 3: Select Reason (Required)**
Choose from dropdown:
- Technical issue - false positive
- Student appeals - first offense mercy
- Administrative error in original ban
- Student demonstrated understanding of rules
- Special circumstances warrant second chance
- Instructor recommendation for reactivation
- Other (with required notes)

### **Step 4: Add Notes (Optional)**
- Additional context or conditions
- Why you're granting mercy
- Any special instructions

### **Step 5: Submit Reactivation**
- Click "Reactivate Student" button
- System will log the action with CRITICAL level
- Student immediately gains access to retake the exam

## 🚫 IF YOU DON'T SEE THE BUTTON

### **Check 1: Admin Role**
```php
// Your user account must have:
role = 'admin'
```

### **Check 2: Already Reactivated**
If the student was already reactivated, you'll see:
```
✅ Student Reactivated
This student was reactivated on [date] and can now retake the exam.
```

### **Check 3: Ban Status**
The button only appears if:
- `is_active = true` in the exam_bans table
- Student is currently banned (not already reactivated)

### **Check 4: Route Access**
Ensure you can access:
- `/admin/security` (main dashboard)
- `/admin/security/bans/{ban_id}` (individual ban details)

## 🔍 TROUBLESHOOTING

### **Problem: 404 Error on /admin/security**
- Routes may not be registered
- Check `routes/web.php` for security routes
- Ensure you're logged in as admin

### **Problem: 403 Forbidden**  
- Your account doesn't have admin role
- Only users with `role = 'admin'` can access

### **Problem: Button Not Visible**
- Student may already be reactivated (`is_active = false`)
- Check browser developer tools for JavaScript errors
- Clear browser cache and refresh

### **Problem: Modal Doesn't Open**
- JavaScript error - check browser console
- Missing FontAwesome icons
- CSS conflicts

## 📞 QUICK ACCESS URLS

**Main Security Dashboard:**
```
/admin/security
```

**Direct Ban Details:**
```  
/admin/security/bans/1  (Emeka Nwosu - Basic Science)
/admin/security/bans/2  (Mium John - Biology)  
/admin/security/bans/3  (Mium John - Mathematics)
```

## ✅ SUCCESS CONFIRMATION

After reactivation, you'll see:
- ✅ Green success message
- 📝 Critical log entry created
- 🔓 Student can immediately retake exam
- 📊 Dashboard statistics updated

The reactivation gives the student a **clean slate** for that subject - they start with 0 violations and can attempt the exam normally with the full 3-strike protection system in place.