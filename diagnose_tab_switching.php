<?php

// Quick diagnostic test for tab switching issues

echo "🔍 DIAGNOSING TAB SWITCHING ISSUES\n";
echo "==================================\n\n";

// Check 1: Verify ExamController validation
echo "1. Checking ExamController validation rules...\n";
$controllerPath = __DIR__ . '/app/Http/Controllers/ExamController.php';
if (file_exists($controllerPath)) {
    $content = file_get_contents($controllerPath);
    
    if (strpos($content, 'tab_switch_attempt') !== false) {
        echo "   ✅ tab_switch_attempt found in validation\n";
    } else {
        echo "   ❌ tab_switch_attempt NOT found in validation\n";
    }
    
    if (strpos($content, 'navigation_attempt') !== false) {
        echo "   ✅ navigation_attempt found in validation\n";
    } else {
        echo "   ❌ navigation_attempt NOT found in validation\n";
    }
    
    if (strpos($content, 'window_open_attempt') !== false) {
        echo "   ✅ window_open_attempt found in validation\n";
    } else {
        echo "   ❌ window_open_attempt NOT found in validation\n";
    }
} else {
    echo "   ❌ ExamController.php not found\n";
}

echo "\n2. Checking SecurityViolationController banned students query...\n";
$securityControllerPath = __DIR__ . '/app/Http/Controllers/Admin/SecurityViolationController.php';
if (file_exists($securityControllerPath)) {
    $content = file_get_contents($securityControllerPath);
    
    if (strpos($content, 'tab_switch_attempt') !== false) {
        echo "   ✅ tab_switch_attempt found in admin query\n";
    } else {
        echo "   ❌ tab_switch_attempt NOT found in admin query\n";
    }
    
    if (strpos($content, 'immediate ban') !== false) {
        echo "   ✅ 'immediate ban' pattern found in admin query\n";
    } else {
        echo "   ❌ 'immediate ban' pattern NOT found in admin query\n";
    }
} else {
    echo "   ❌ SecurityViolationController.php not found\n";
}

echo "\n3. Checking frontend violation types...\n";
$examViewPath = __DIR__ . '/resources/views/user/exam_simple.blade.php';
if (file_exists($examViewPath)) {
    $content = file_get_contents($examViewPath);
    
    if (strpos($content, "recordSecurityViolation('tab_switch_attempt'") !== false) {
        echo "   ✅ Frontend calls tab_switch_attempt\n";
    } else {
        echo "   ❌ Frontend does NOT call tab_switch_attempt\n";
    }
    
    if (strpos($content, "recordSecurityViolation('navigation_attempt'") !== false) {
        echo "   ✅ Frontend calls navigation_attempt\n";
    } else {
        echo "   ❌ Frontend does NOT call navigation_attempt\n";
    }
    
    if (strpos($content, "recordSecurityViolation('window_open_attempt'") !== false) {
        echo "   ✅ Frontend calls window_open_attempt\n";
    } else {
        echo "   ❌ Frontend does NOT call window_open_attempt\n";
    }
} else {
    echo "   ❌ exam_simple.blade.php not found\n";
}

echo "\n4. Checking route definition...\n";
$routesPath = __DIR__ . '/routes/web.php';
if (file_exists($routesPath)) {
    $content = file_get_contents($routesPath);
    
    if (strpos($content, 'security-violation') !== false) {
        echo "   ✅ Security violation route exists\n";
    } else {
        echo "   ❌ Security violation route NOT found\n";
    }
    
    if (strpos($content, 'recordSecurityViolation') !== false) {
        echo "   ✅ Route points to recordSecurityViolation method\n";
    } else {
        echo "   ❌ Route does NOT point to recordSecurityViolation method\n";
    }
} else {
    echo "   ❌ routes/web.php not found\n";
}

echo "\n✅ Diagnostic complete!\n";
echo "\nIf you see ❌ marks above, those are the issues causing the problems.\n";
echo "\nTo test tab switching:\n";
echo "1. Start exam as student: http://localhost:8000/user/dashboard\n";
echo "2. Try pressing Ctrl+T or Alt+Tab during exam\n";
echo "3. Check admin dashboard: http://localhost:8000/admin/security/banned-students\n";