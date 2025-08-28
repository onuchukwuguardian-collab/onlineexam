<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TESTING ADMIN LAYOUT ===\n";

try {
    // Test if the admin layout view exists
    if (view()->exists('layouts.admin')) {
        echo "✅ Admin layout view exists\n";
        
        // Try to render the layout with some test content
        $html = view('layouts.admin', [
            'headerContent' => '<h2>Test Page</h2><p>Testing admin layout</p>'
        ])->render();
        
        echo "✅ Admin layout rendered successfully\n";
        echo "✅ HTML length: " . strlen($html) . " characters\n";
        
        // Check for key navigation elements
        if (strpos($html, 'fas fa-user-graduate') !== false) {
            echo "✅ Updated Students & Users icon found\n";
        }
        
        if (strpos($html, 'fas fa-chalkboard-teacher') !== false) {
            echo "✅ Updated Classes icon found\n";
        }
        
        if (strpos($html, 'fas fa-book-open') !== false) {
            echo "✅ Updated Subjects & Questions icon found\n";
        }
        
        if (strpos($html, 'fas fa-trophy') !== false) {
            echo "✅ Updated Scoreboard icon found\n";
        }
        
        if (strpos($html, 'fas fa-redo-alt') !== false) {
            echo "✅ Updated Exam Reset icon found\n";
        }
        
        if (strpos($html, 'fas fa-server') !== false) {
            echo "✅ Updated System Management icon found\n";
        }
        
        // Check for navigation structure
        if (strpos($html, 'admin-sidebar') !== false) {
            echo "✅ Admin sidebar found\n";
        }
        
        if (strpos($html, 'nav-menu') !== false) {
            echo "✅ Navigation menu found\n";
        }
        
    } else {
        echo "❌ Admin layout view not found\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== TESTING COMPLETE ===\n";