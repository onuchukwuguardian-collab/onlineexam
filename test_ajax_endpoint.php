<?php

echo "=== TESTING AJAX ENDPOINT ACCESS ===\n\n";

// Test if we can access the endpoint with proper authentication
echo "🔍 TESTING ENDPOINT WITH AUTHENTICATION\n";
echo "=======================================\n";

// Start a session to simulate being logged in
session_start();

// Test URL
$testUrl = "http://web-portal.test/admin/exam-reset/subjects/8";
echo "Testing URL: {$testUrl}\n";

// Try with curl to simulate browser request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'X-Requested-With: XMLHttpRequest',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
]);
curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status Code: {$httpCode}\n";
if ($error) {
    echo "cURL Error: {$error}\n";
}

if ($response) {
    echo "Response received: " . substr($response, 0, 200) . "\n";
    
    // Check if it's a redirect to login
    if (strpos($response, 'login') !== false || $httpCode == 302) {
        echo "❌ Redirected to login - Authentication required\n";
    } else {
        $data = json_decode($response, true);
        if (is_array($data)) {
            echo "✅ Valid JSON response with " . count($data) . " subjects\n";
        } else {
            echo "❌ Invalid JSON response\n";
        }
    }
} else {
    echo "❌ No response received\n";
}

// Clean up
if (file_exists('cookie.txt')) {
    unlink('cookie.txt');
}

echo "\n=== SOLUTION ===\n";
echo "The issue is likely that the AJAX request needs proper authentication.\n";
echo "The JavaScript should include CSRF token and session cookies.\n";
echo "Let me check the view for proper CSRF token handling...\n";

?>