<?php

echo "=== DEBUGGING SCOREBOARD COLUMN VISIBILITY ===\n\n";

if (file_exists('resources/views/admin/scoreboard/index.blade.php')) {
    $content = file_get_contents('resources/views/admin/scoreboard/index.blade.php');
    
    echo "🔍 CHECKING COLUMN TOGGLE MENU STRUCTURE\n";
    echo "========================================\n";
    
    // Extract the column toggle menu section
    $start = strpos($content, '<div id="columnToggleMenu"');
    $end = strpos($content, '</div>', $start + 500); // Find the closing div
    
    if ($start !== false && $end !== false) {
        $menuSection = substr($content, $start, $end - $start + 6);
        echo "✅ Column toggle menu found\n";
        
        // Count column options
        $optionCount = substr_count($menuSection, 'class="column-option"');
        echo "✅ Found {$optionCount} column options\n";
        
        // Check specific labels
        $labels = [
            'Rank' => 'for="col-rank">Rank</label>',
            'Student Name' => 'for="col-student">Student Name</label>',
            'Registration No.' => 'for="col-registration">Registration No.</label>',
            'Class' => 'for="col-class">Class</label>',
            'Total Score' => 'for="col-total">Total Score</label>',
            'Average %' => 'for="col-average">Average %</label>',
            'Subjects Taken' => 'for="col-subjects-taken">Subjects Taken</label>'
        ];
        
        echo "\n📋 CHECKING INDIVIDUAL LABELS:\n";
        foreach ($labels as $name => $pattern) {
            if (strpos($menuSection, $pattern) !== false) {
                echo "✅ {$name}: Label present\n";
            } else {
                echo "❌ {$name}: Label missing or malformed\n";
            }
        }
        
        // Check for subject labels (dynamic)
        $subjectLabels = substr_count($menuSection, 'col-subject-');
        echo "✅ Found {$subjectLabels} subject column labels\n";
        
    } else {
        echo "❌ Column toggle menu not found\n";
    }
    
    echo "\n🔍 CHECKING CSS STYLING\n";
    echo "=======================\n";
    
    // Check CSS properties
    $cssChecks = [
        'Menu Width' => strpos($content, 'min-width: 400px') !== false,
        'Text Wrapping' => strpos($content, 'white-space: normal') !== false,
        'Word Wrap' => strpos($content, 'word-wrap: break-word') !== false,
        'Flex Start Alignment' => strpos($content, 'align-items: flex-start') !== false,
        'Minimum Height' => strpos($content, 'min-height: 40px') !== false,
        'Label Font Size' => strpos($content, 'font-size: 14px') !== false,
        'Label Color' => strpos($content, 'color: #333') !== false
    ];
    
    foreach ($cssChecks as $check => $passed) {
        if ($passed) {
            echo "✅ {$check}\n";
        } else {
            echo "❌ {$check}\n";
        }
    }
    
    echo "\n🔍 POTENTIAL ISSUES ANALYSIS\n";
    echo "============================\n";
    
    // Check for potential issues
    $issues = [];
    
    if (strpos($content, 'text-overflow: ellipsis') !== false) {
        $issues[] = "Text overflow ellipsis still present - may truncate labels";
    }
    
    if (strpos($content, 'overflow: hidden') !== false) {
        $issues[] = "Overflow hidden may clip content";
    }
    
    if (strpos($content, 'max-width') !== false && strpos($content, 'max-width: 500px') === false) {
        $issues[] = "Max-width constraint may be too small";
    }
    
    if (empty($issues)) {
        echo "✅ No obvious CSS issues found\n";
    } else {
        foreach ($issues as $issue) {
            echo "⚠️ {$issue}\n";
        }
    }
    
    echo "\n🔍 JAVASCRIPT FUNCTIONALITY CHECK\n";
    echo "=================================\n";
    
    // Check for JavaScript functions
    $jsChecks = [
        'Setup Function' => strpos($content, 'setupColumnToggle()') !== false,
        'Toggle Button Handler' => strpos($content, 'columnToggleBtn') !== false,
        'Menu Toggle' => strpos($content, 'columnToggleMenu') !== false,
        'Show All Function' => strpos($content, 'showAllColumns') !== false,
        'Hide All Function' => strpos($content, 'hideAllColumns') !== false
    ];
    
    foreach ($jsChecks as $check => $passed) {
        if ($passed) {
            echo "✅ {$check}\n";
        } else {
            echo "❌ {$check}\n";
        }
    }
    
} else {
    echo "❌ Scoreboard file not found\n";
}

echo "\n🔧 RECOMMENDED FIXES\n";
echo "====================\n";
echo "If column names are still not displaying properly:\n\n";
echo "1. Check browser developer tools for CSS conflicts\n";
echo "2. Ensure JavaScript is loading properly\n";
echo "3. Clear browser cache\n";
echo "4. Check for z-index issues with the dropdown menu\n";
echo "5. Verify the menu is not being clipped by parent containers\n";

echo "\n📱 BROWSER TESTING STEPS\n";
echo "========================\n";
echo "1. Open scoreboard page: http://web-portal.test/admin/scoreboard\n";
echo "2. Click 'Show/Hide Columns' button\n";
echo "3. Check if dropdown menu appears\n";
echo "4. Verify all column names are visible and not truncated\n";
echo "5. Test checkbox functionality\n";
echo "6. Check 'Show All' and 'Hide All' buttons\n";

?>