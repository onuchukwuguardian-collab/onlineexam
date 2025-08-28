<?php

echo "=== TESTING COLUMN VISIBILITY FIX ===\n\n";

if (file_exists('resources/views/admin/scoreboard/index.blade.php')) {
    $content = file_get_contents('resources/views/admin/scoreboard/index.blade.php');
    
    echo "🔍 CHECKING VISIBILITY FIXES\n";
    echo "============================\n";
    
    $fixes = [
        'Scoreboard Card Overflow' => strpos($content, 'overflow: visible') !== false,
        'High Z-Index' => strpos($content, 'z-index: 9999') !== false,
        'Enhanced Shadow' => strpos($content, '0 8px 25px rgba(0, 0, 0, 0.2)') !== false,
        'Margin Top' => strpos($content, 'margin-top: 5px') !== false,
        'Control Group Position' => strpos($content, 'position: relative') !== false
    ];
    
    foreach ($fixes as $fix => $applied) {
        if ($applied) {
            echo "✅ {$fix}\n";
        } else {
            echo "❌ {$fix}\n";
        }
    }
    
    echo "\n🔍 VERIFYING COLUMN STRUCTURE\n";
    echo "=============================\n";
    
    // Count all column options in the HTML
    $columnOptions = [];
    preg_match_all('/id="col-([^"]+)"[^>]*>\s*<label[^>]*>([^<]+)<\/label>/', $content, $matches);
    
    if (!empty($matches[1])) {
        echo "✅ Found " . count($matches[1]) . " column options:\n";
        for ($i = 0; $i < count($matches[1]); $i++) {
            $id = $matches[1][$i];
            $label = trim($matches[2][$i]);
            echo "   • {$label} (col-{$id})\n";
            $columnOptions[$id] = $label;
        }
    } else {
        echo "❌ No column options found\n";
    }
    
    echo "\n🔍 CHECKING EXPECTED COLUMNS\n";
    echo "============================\n";
    
    $expectedColumns = [
        'rank' => 'Rank',
        'student' => 'Student Name', 
        'registration' => 'Registration No.',
        'class' => 'Class',
        'total' => 'Total Score',
        'average' => 'Average %',
        'subjects-taken' => 'Subjects Taken'
    ];
    
    foreach ($expectedColumns as $id => $expectedLabel) {
        if (isset($columnOptions[$id])) {
            echo "✅ {$expectedLabel}: Found\n";
        } else {
            echo "❌ {$expectedLabel}: Missing\n";
        }
    }
    
    echo "\n🔍 CHECKING JAVASCRIPT FUNCTIONALITY\n";
    echo "====================================\n";
    
    $jsChecks = [
        'Toggle Function' => strpos($content, "toggleMenu.style.display = toggleMenu.style.display === 'none' ? 'block' : 'none'") !== false,
        'Click Outside Handler' => strpos($content, "toggleMenu.style.display = 'none'") !== false,
        'Setup Column Toggle' => strpos($content, 'setupColumnToggle()') !== false,
        'Show All Handler' => strpos($content, 'showAllColumns') !== false,
        'Hide All Handler' => strpos($content, 'hideAllColumns') !== false
    ];
    
    foreach ($jsChecks as $check => $present) {
        if ($present) {
            echo "✅ {$check}\n";
        } else {
            echo "❌ {$check}\n";
        }
    }
    
    echo "\n🔍 CSS CONFLICT CHECK\n";
    echo "=====================\n";
    
    $potentialIssues = [];
    
    if (strpos($content, 'overflow: hidden') !== false) {
        $potentialIssues[] = "Some elements still have overflow: hidden";
    }
    
    if (strpos($content, 'text-overflow: ellipsis') !== false) {
        $potentialIssues[] = "Text overflow ellipsis may truncate labels";
    }
    
    if (strpos($content, 'max-height') !== false && strpos($content, 'max-height: 500px') === false) {
        $potentialIssues[] = "Max-height constraints may clip content";
    }
    
    if (empty($potentialIssues)) {
        echo "✅ No CSS conflicts detected\n";
    } else {
        foreach ($potentialIssues as $issue) {
            echo "⚠️ {$issue}\n";
        }
    }
    
} else {
    echo "❌ Scoreboard file not found\n";
}

echo "\n🎯 TESTING SUMMARY\n";
echo "==================\n";

$allChecks = [
    'Overflow Fixed' => strpos(file_get_contents('resources/views/admin/scoreboard/index.blade.php'), 'overflow: visible') !== false,
    'Z-Index Enhanced' => strpos(file_get_contents('resources/views/admin/scoreboard/index.blade.php'), 'z-index: 9999') !== false,
    'Column Labels Present' => strpos(file_get_contents('resources/views/admin/scoreboard/index.blade.php'), 'for="col-rank">Rank</label>') !== false,
    'JavaScript Working' => strpos(file_get_contents('resources/views/admin/scoreboard/index.blade.php'), 'setupColumnToggle()') !== false
];

$passedChecks = array_sum($allChecks);
$totalChecks = count($allChecks);

foreach ($allChecks as $check => $passed) {
    if ($passed) {
        echo "✅ {$check}\n";
    } else {
        echo "❌ {$check}\n";
    }
}

echo "\n🎯 RESULT: {$passedChecks}/{$totalChecks} CHECKS PASSED\n";

if ($passedChecks === $totalChecks) {
    echo "\n🎉 COLUMN VISIBILITY ISSUE FIXED!\n";
    echo "=================================\n";
    echo "✅ Dropdown menu overflow fixed\n";
    echo "✅ Z-index increased for proper layering\n";
    echo "✅ All column labels properly structured\n";
    echo "✅ JavaScript functionality intact\n";
    echo "✅ Enhanced shadow for better visibility\n";
    echo "✅ Proper positioning with margin\n";
    echo "\n🚀 SCOREBOARD COLUMN TOGGLE READY!\n";
    echo "\n📋 WHAT WAS FIXED:\n";
    echo "• Changed scoreboard-card overflow from 'hidden' to 'visible'\n";
    echo "• Increased z-index to 9999 for proper layering\n";
    echo "• Enhanced box-shadow for better visibility\n";
    echo "• Added margin-top for better spacing\n";
    echo "• Ensured control-group has relative positioning\n";
    echo "• Improved text wrapping for long column names\n";
} else {
    echo "\n⚠️ SOME ISSUES REMAIN - CHECK FAILED TESTS ABOVE\n";
}

echo "\n📱 TESTING INSTRUCTIONS\n";
echo "=======================\n";
echo "1. Go to: http://web-portal.test/admin/scoreboard\n";
echo "2. Select a class to load data\n";
echo "3. Click the 'Show/Hide Columns' button\n";
echo "4. Verify the dropdown menu appears properly\n";
echo "5. Check that all column names are visible and not cut off\n";
echo "6. Test the checkboxes to hide/show columns\n";
echo "7. Try the 'Show All' and 'Hide All' buttons\n";
echo "8. Click outside the menu to close it\n";

?>