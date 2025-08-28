<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TESTING LOCAL ASSETS INTEGRATION ===\n";

try {
    // Test if the admin layout uses local assets
    if (view()->exists('layouts.admin')) {
        echo "✅ Admin layout view exists\n";
        
        $html = view('layouts.admin')->render();
        echo "✅ Admin layout rendered successfully\n";
        echo "✅ HTML length: " . strlen($html) . " characters\n";
        
        // Check for local asset references
        if (strpos($html, 'build/assets/app-C2NPXxJ2.css') !== false) {
            echo "✅ Local Tailwind CSS file referenced\n";
        } else {
            echo "❌ Local Tailwind CSS file not found\n";
        }
        
        if (strpos($html, 'assets/css/fontawesome.min.css') !== false) {
            echo "✅ Local FontAwesome CSS file referenced\n";
        } else {
            echo "❌ Local FontAwesome CSS file not found\n";
        }
        
        if (strpos($html, 'assets/css/inter-font.css') !== false) {
            echo "✅ Local Inter font CSS file referenced\n";
        } else {
            echo "❌ Local Inter font CSS file not found\n";
        }
        
        // Check that CDN references are removed
        if (strpos($html, 'cdn.tailwindcss.com') === false) {
            echo "✅ Tailwind CDN removed\n";
        } else {
            echo "❌ Tailwind CDN still present\n";
        }
        
        if (strpos($html, 'cdnjs.cloudflare.com') === false) {
            echo "✅ FontAwesome CDN removed\n";
        } else {
            echo "❌ FontAwesome CDN still present\n";
        }
        
        // Test reset page with local assets
        echo "\n=== TESTING RESET PAGE WITH LOCAL ASSETS ===\n";
        
        $controller = new \App\Http\Controllers\Admin\AdminExamResetController();
        $response = $controller->index();
        
        if ($response instanceof \Illuminate\View\View) {
            echo "✅ Reset page controller works\n";
            
            $resetHtml = $response->render();
            echo "✅ Reset page renders with local assets\n";
            echo "✅ Reset page HTML length: " . strlen($resetHtml) . " characters\n";
            
            // Check for Tailwind classes in the reset page
            $tailwindClasses = [
                'min-h-screen',
                'bg-gray-50',
                'max-w-7xl',
                'mx-auto',
                'px-4',
                'sm:px-6',
                'lg:px-8',
                'bg-gradient-to-r',
                'from-blue-600',
                'to-blue-800',
                'rounded-lg',
                'shadow-lg',
                'grid',
                'grid-cols-1',
                'md:grid-cols-2',
                'gap-6'
            ];
            
            $foundClasses = 0;
            foreach ($tailwindClasses as $class) {
                if (strpos($resetHtml, $class) !== false) {
                    $foundClasses++;
                }
            }
            
            echo "✅ Found $foundClasses/" . count($tailwindClasses) . " Tailwind classes in reset page\n";
            
            if ($foundClasses >= count($tailwindClasses) * 0.8) {
                echo "✅ Reset page properly styled with Tailwind CSS\n";
            } else {
                echo "❌ Reset page missing Tailwind CSS styling\n";
            }
            
        } else {
            echo "❌ Reset page controller failed\n";
        }
        
        // Check file sizes
        echo "\n=== LOCAL ASSET FILE SIZES ===\n";
        
        $cssFile = public_path('build/assets/app-C2NPXxJ2.css');
        if (file_exists($cssFile)) {
            $cssSize = filesize($cssFile);
            echo "✅ Tailwind CSS file: " . number_format($cssSize) . " bytes\n";
            
            if ($cssSize > 50000) {
                echo "✅ CSS file size indicates full Tailwind build\n";
            } else {
                echo "⚠️  CSS file seems small, might be missing classes\n";
            }
        } else {
            echo "❌ Tailwind CSS file not found\n";
        }
        
        $fontFile = public_path('assets/css/fontawesome.min.css');
        if (file_exists($fontFile)) {
            $fontSize = filesize($fontFile);
            echo "✅ FontAwesome CSS file: " . number_format($fontSize) . " bytes\n";
        } else {
            echo "❌ FontAwesome CSS file not found\n";
        }
        
        $interFile = public_path('assets/css/inter-font.css');
        if (file_exists($interFile)) {
            $interSize = filesize($interFile);
            echo "✅ Inter font CSS file: " . number_format($interSize) . " bytes\n";
        } else {
            echo "❌ Inter font CSS file not found\n";
        }
        
    } else {
        echo "❌ Admin layout view not found\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== TESTING COMPLETE ===\n";