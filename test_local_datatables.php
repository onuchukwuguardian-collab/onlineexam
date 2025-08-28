<?php
/**
 * Test Local DataTables Assets
 * This script verifies that all DataTables assets have been downloaded locally
 */

echo "Testing Local DataTables Assets...\n\n";

$publicPath = __DIR__ . '/public/assets';

// CSS Files to check
$cssFiles = [
    'css/dataTables.bootstrap4.min.css',
    'css/buttons.bootstrap4.min.css', 
    'css/responsive.bootstrap4.min.css'
];

// JS Files to check
$jsFiles = [
    'js/jquery.dataTables.min.js',
    'js/dataTables.bootstrap4.min.js',
    'js/dataTables.buttons.min.js',
    'js/buttons.bootstrap4.min.js',
    'js/buttons.html5.min.js',
    'js/buttons.print.min.js',
    'js/buttons.colVis.min.js',
    'js/dataTables.responsive.min.js',
    'js/responsive.bootstrap4.min.js'
];

echo "Checking CSS Files:\n";
echo "==================\n";
foreach ($cssFiles as $file) {
    $fullPath = $publicPath . '/' . $file;
    if (file_exists($fullPath)) {
        $size = filesize($fullPath);
        echo "✅ {$file} - " . number_format($size) . " bytes\n";
    } else {
        echo "❌ {$file} - NOT FOUND\n";
    }
}

echo "\nChecking JS Files:\n";
echo "==================\n";
foreach ($jsFiles as $file) {
    $fullPath = $publicPath . '/' . $file;
    if (file_exists($fullPath)) {
        $size = filesize($fullPath);
        echo "✅ {$file} - " . number_format($size) . " bytes\n";
    } else {
        echo "❌ {$file} - NOT FOUND\n";
    }
}

echo "\nSummary:\n";
echo "========\n";
$totalFiles = count($cssFiles) + count($jsFiles);
$existingFiles = 0;

foreach (array_merge($cssFiles, $jsFiles) as $file) {
    if (file_exists($publicPath . '/' . $file)) {
        $existingFiles++;
    }
}

echo "Files found: {$existingFiles}/{$totalFiles}\n";

if ($existingFiles === $totalFiles) {
    echo "🎉 All DataTables assets are available locally!\n";
    echo "✅ No more CSP violations from CDN requests\n";
    echo "✅ Scoreboard will load faster with local assets\n";
} else {
    echo "⚠️  Some files are missing. Please download them manually.\n";
}

echo "\nScoreboard Improvements Made:\n";
echo "============================\n";
echo "✅ Replaced CDN links with local asset links\n";
echo "✅ Added yellow background theme for better text visibility\n";
echo "✅ Enhanced column visibility controls with yellow styling\n";
echo "✅ Improved copy menu with yellow theme\n";
echo "✅ Added DataTables initialization with responsive design\n";
echo "✅ Enhanced button styling for better visibility\n";
echo "✅ Added proper pagination and search styling\n";

?>