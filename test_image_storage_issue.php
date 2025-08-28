<?php
/**
 * Test to diagnose why student question images appear and then disappear
 */

echo "=== STUDENT QUESTION IMAGE ISSUE DIAGNOSIS ===\n\n";

echo "POSSIBLE CAUSES:\n\n";

echo "1. STORAGE SYMLINK ISSUE:\n";
echo "   - Laravel requires a symbolic link from public/storage to storage/app/public\n";
echo "   - Run: php artisan storage:link\n";
echo "   - Check if public/storage directory exists and points to storage/app/public\n\n";

echo "2. FILE PERMISSIONS:\n";
echo "   - storage/app/public/question_images/ needs proper permissions\n";
echo "   - Should be readable by web server (755 for directories, 644 for files)\n";
echo "   - Check: ls -la storage/app/public/question_images/\n\n";

echo "3. CACHE BUSTING PARAMETER:\n";
echo "   - Current code uses: ?t={{ time() }}\n";
echo "   - This changes on every page load, which is good\n";
echo "   - But might cause issues if server time is inconsistent\n\n";

echo "4. IMAGE PATH GENERATION:\n";
echo "   - Code: asset('storage/' . \$question->image_path)\n";
echo "   - If image_path = 'question_images/123_abc.jpg'\n";
echo "   - Final URL = 'http://domain.com/storage/question_images/123_abc.jpg'\n";
echo "   - This should work if symlink exists\n\n";

echo "5. BROWSER CACHING:\n";
echo "   - Images might be cached by browser\n";
echo "   - Hard refresh (Ctrl+F5) might show different results\n";
echo "   - Cache-busting parameter should prevent this\n\n";

echo "6. SERVER CONFIGURATION:\n";
echo "   - Web server might not be serving files from storage correctly\n";
echo "   - Check .htaccess or nginx configuration\n";
echo "   - Ensure storage directory is accessible\n\n";

echo "=== DEBUGGING STEPS ===\n\n";

echo "1. CHECK STORAGE LINK:\n";
echo "   Run in terminal:\n";
echo "   php artisan storage:link\n";
echo "   ls -la public/storage\n\n";

echo "2. CHECK IMAGE FILES:\n";
echo "   ls -la storage/app/public/question_images/\n";
echo "   # Should show image files with proper permissions\n\n";

echo "3. TEST DIRECT ACCESS:\n";
echo "   Try accessing image directly in browser:\n";
echo "   http://yourdomain.com/storage/question_images/[filename]\n";
echo "   # Should display the image\n\n";

echo "4. CHECK QUESTION DATA:\n";
echo "   In database, check questions table:\n";
echo "   SELECT id, question_text, image_path FROM questions WHERE image_path IS NOT NULL;\n";
echo "   # Verify image_path values are correct\n\n";

echo "5. BROWSER DEVELOPER TOOLS:\n";
echo "   - Open browser dev tools (F12)\n";
echo "   - Go to Network tab\n";
echo "   - Reload exam page\n";
echo "   - Check if image requests return 404 or other errors\n\n";

echo "=== QUICK FIXES ===\n\n";

echo "FIX 1: Ensure Storage Link\n";
echo "Run: php artisan storage:link\n\n";

echo "FIX 2: Check Permissions\n";
echo "chmod 755 storage/app/public/question_images/\n";
echo "chmod 644 storage/app/public/question_images/*\n\n";

echo "FIX 3: Alternative Image Path (if needed)\n";
echo "Instead of: asset('storage/' . \$question->image_path)\n";
echo "Try: url('storage/' . \$question->image_path)\n\n";

echo "FIX 4: Add Error Handling to Image Display\n";
echo "Add onerror handler to img tag:\n";
echo "<img src=\"...\" onerror=\"this.style.display='none'\" alt=\"Question Image\">\n\n";

echo "=== MOST LIKELY CAUSE ===\n\n";
echo "The most common cause is missing storage symlink.\n";
echo "Run: php artisan storage:link\n";
echo "This creates public/storage -> storage/app/public symlink\n\n";

echo "If images appear briefly then disappear, it might be:\n";
echo "1. JavaScript interference\n";
echo "2. CSS hiding the images\n";
echo "3. Network issues causing failed loads\n";
echo "4. Browser cache conflicts\n\n";

echo "Check browser console for any JavaScript errors!\n";
?>