<?php
// More detailed brace checker for exam_simple.blade.php

$file = 'resources/views/user/exam_simple.blade.php';
$content = file_get_contents($file);
$lines = explode("\n", $content);

$inScript = false;
$braceCount = 0;
$parenCount = 0;
$issues = [];

for ($i = 0; $i < count($lines); $i++) {
    $line = $lines[$i];
    $lineNum = $i + 1;
    
    // Track script blocks
    if (strpos($line, '<script') !== false) {
        $inScript = true;
        continue;
    }
    if (strpos($line, '</script>') !== false) {
        $inScript = false;
        // Check if braces are balanced at end of script
        if ($braceCount !== 0) {
            $issues[] = "Script block ending at line $lineNum has unbalanced braces (count: $braceCount)";
        }
        if ($parenCount !== 0) {
            $issues[] = "Script block ending at line $lineNum has unbalanced parentheses (count: $parenCount)";
        }
        $braceCount = 0;
        $parenCount = 0;
        continue;
    }
    
    if ($inScript) {
        // Count braces and parentheses in this line
        $openBraces = substr_count($line, '{');
        $closeBraces = substr_count($line, '}');
        $openParens = substr_count($line, '(');
        $closeParens = substr_count($line, ')');
        
        $braceCount += $openBraces - $closeBraces;
        $parenCount += $openParens - $closeParens;
        
        // Check for lines with suspicious patterns
        if (trim($line) === '}' && $braceCount < 0) {
            $issues[] = "Extra closing brace at line $lineNum";
        }
        
        if (trim($line) === ')' && $parenCount < 0) {
            $issues[] = "Extra closing parenthesis at line $lineNum";
        }
        
        // Check for function definitions
        if (preg_match('/function\s+\w+\s*\([^)]*\)\s*{/', $line)) {
            echo "Function definition found at line $lineNum: " . trim($line) . "\n";
        }
        
        // Check for specific problem areas
        if (strpos($line, 'window.nextQuestion') !== false) {
            echo "nextQuestion definition at line $lineNum: " . trim($line) . "\n";
        }
    }
}

echo "\nBrace analysis results:\n";
if (empty($issues)) {
    echo "✅ No brace/parenthesis issues detected in script blocks\n";
} else {
    echo "❌ Issues found:\n";
    foreach ($issues as $issue) {
        echo "- $issue\n";
    }
}

// Now let's specifically check the areas around the positions mentioned in the error
echo "\nChecking specific problematic areas...\n";

// Look for the problematic sections
$scriptSections = [];
$pos = 0;
while (($start = strpos($content, '<script', $pos)) !== false) {
    $end = strpos($content, '</script>', $start);
    if ($end !== false) {
        $scriptContent = substr($content, $start, $end - $start + 9);
        $scriptSections[] = ['start' => $start, 'end' => $end, 'content' => $scriptContent];
        $pos = $end + 9;
    } else {
        break;
    }
}

foreach ($scriptSections as $idx => $section) {
    $jsContent = substr($section['content'], strpos($section['content'], '>') + 1);
    $jsContent = substr($jsContent, 0, strrpos($jsContent, '</script>'));
    
    $openBraces = substr_count($jsContent, '{');
    $closeBraces = substr_count($jsContent, '}');
    
    echo "Script section " . ($idx + 1) . " (position {$section['start']}-{$section['end']}): ";
    echo "Open braces: $openBraces, Close braces: $closeBraces";
    
    if ($openBraces !== $closeBraces) {
        echo " ❌ UNBALANCED!\n";
        
        // Show the last few lines of this section
        $jsLines = explode("\n", $jsContent);
        $lastLines = array_slice($jsLines, -5);
        echo "Last 5 lines of this section:\n";
        foreach ($lastLines as $lineIdx => $line) {
            echo "  " . (count($jsLines) - 5 + $lineIdx + 1) . ": " . trim($line) . "\n";
        }
    } else {
        echo " ✅\n";
    }
}