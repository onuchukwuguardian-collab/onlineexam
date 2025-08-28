<?php
// Test file to validate JavaScript syntax in exam pages
echo "Testing JavaScript syntax in exam pages...\n";

// Read the exam_simple.blade.php file
$examFile = 'resources/views/user/exam_simple.blade.php';
$content = file_get_contents($examFile);

// Check for common JavaScript syntax issues
$issues = [];

// Check for duplicate function definitions
if (substr_count($content, 'function nextQuestion(') > 1) {
    $issues[] = "Multiple nextQuestion function definitions found";
}

// Check for unmatched brackets in JavaScript sections
$jsStart = 0;
while (($jsStart = strpos($content, '<script>', $jsStart)) !== false) {
    $jsEnd = strpos($content, '</script>', $jsStart);
    if ($jsEnd !== false) {
        $jsContent = substr($content, $jsStart + 8, $jsEnd - $jsStart - 8);
        
        // Count brackets
        $openBraces = substr_count($jsContent, '{');
        $closeBraces = substr_count($jsContent, '}');
        $openParens = substr_count($jsContent, '(');
        $closeParens = substr_count($jsContent, ')');
        
        if ($openBraces !== $closeBraces) {
            $issues[] = "Unmatched curly braces in JavaScript section starting at position $jsStart";
        }
        
        if ($openParens !== $closeParens) {
            $issues[] = "Unmatched parentheses in JavaScript section starting at position $jsStart";
        }
    }
    $jsStart = $jsEnd + 9;
}

// Check for common syntax errors
if (strpos($content, 'timerEl.textContent = `${minutes.toString().padStart(2, \'0\')}:${seconds.toString().padStart(2, \'0\')}`;') !== false) {
    echo "✅ Timer display function looks correct\n";
} else {
    $issues[] = "Timer display function may have syntax issues";
}

// Report results
if (empty($issues)) {
    echo "✅ No obvious JavaScript syntax issues found!\n";
    echo "The nextQuestion function freeze issue should be resolved.\n";
} else {
    echo "❌ Found the following issues:\n";
    foreach ($issues as $issue) {
        echo "- $issue\n";
    }
}

echo "\nNext steps:\n";
echo "1. Test the exam page in a browser\n";
echo "2. Check browser console for any remaining JavaScript errors\n";
echo "3. Test the next button functionality\n";