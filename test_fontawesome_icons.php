<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TESTING FONTAWESOME ICONS IN ADMIN LAYOUT ===\n";

try {
    // Test if the admin layout view exists and renders
    if (view()->exists('layouts.admin')) {
        echo "✅ Admin layout view exists\n";
        
        // Render the layout
        $html = view('layouts.admin')->render();
        echo "✅ Admin layout rendered successfully\n";
        echo "✅ HTML length: " . strlen($html) . " characters\n";
        
        // Check for FontAwesome CDN link
        if (strpos($html, 'cdnjs.cloudflare.com/ajax/libs/font-awesome') !== false) {
            echo "✅ FontAwesome CDN link found\n";
        } else {
            echo "❌ FontAwesome CDN link not found\n";
        }
        
        // Check for local FontAwesome fallback
        if (strpos($html, 'assets/css/fontawesome.min.css') !== false) {
            echo "✅ Local FontAwesome fallback found\n";
        } else {
            echo "❌ Local FontAwesome fallback not found\n";
        }
        
        // Check for specific icons in the navigation
        $iconChecks = [
            'fas fa-tachometer-alt' => 'Dashboard icon',
            'fas fa-user-graduate' => 'Students & Users icon',
            'fas fa-chalkboard-teacher' => 'Classes icon',
            'fas fa-book-open' => 'Subjects & Questions icon',
            'fas fa-trophy' => 'Scoreboard icon',
            'fas fa-redo-alt' => 'Exam Reset icon',
            'fas fa-server' => 'System Management icon'
        ];
        
        echo "\n=== ICON VERIFICATION ===\n";
        foreach ($iconChecks as $iconClass => $description) {
            if (strpos($html, $iconClass) !== false) {
                echo "✅ $description ($iconClass) found\n";
            } else {
                echo "❌ $description ($iconClass) missing\n";
            }
        }
        
        // Check for enhanced styling classes
        echo "\n=== STYLING VERIFICATION ===\n";
        $styleChecks = [
            'admin-sidebar' => 'Sidebar container',
            'nav-menu' => 'Navigation menu',
            'nav-link' => 'Navigation links',
            'linear-gradient' => 'Gradient backgrounds',
            'transition:' => 'CSS transitions',
            'transform:' => 'CSS transforms'
        ];
        
        foreach ($styleChecks as $styleClass => $description) {
            if (strpos($html, $styleClass) !== false) {
                echo "✅ $description styling found\n";
            } else {
                echo "❌ $description styling missing\n";
            }
        }
        
        // Check for responsive design
        echo "\n=== RESPONSIVE DESIGN ===\n";
        if (strpos($html, '@media (max-width: 768px)') !== false) {
            echo "✅ Mobile responsive styles found\n";
        } else {
            echo "❌ Mobile responsive styles missing\n";
        }
        
        // Check for accessibility features
        echo "\n=== ACCESSIBILITY ===\n";
        if (strpos($html, 'aria-') !== false || strpos($html, 'role=') !== false) {
            echo "✅ Accessibility attributes found\n";
        } else {
            echo "⚠️  Consider adding accessibility attributes\n";
        }
        
    } else {
        echo "❌ Admin layout view not found\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== TESTING COMPLETE ===\n";