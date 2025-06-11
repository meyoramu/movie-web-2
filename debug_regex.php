<?php

// Debug script to test regex patterns
echo "Testing regex patterns...\n";

// Test the problematic pattern
$patterns = [
    '/^\/auth\/reset\-password\/([^/]+)$/',
    '/^\/auth\/reset\-password\/\{token\}$/',
    '/^\\/auth\\/reset\\-password\\/\\{token\\}$/',
    '/^\/\{path\}$/',
    '/^(.*)$/'
];

foreach ($patterns as $pattern) {
    echo "\nTesting pattern: $pattern\n";
    
    // Clear previous errors
    error_clear_last();
    
    // Test the pattern
    $result = @preg_match($pattern, '/test/path');
    $error = error_get_last();
    
    if ($result === false) {
        echo "  ERROR: Pattern failed\n";
        if ($error) {
            echo "  Error message: " . $error['message'] . "\n";
        }
    } else {
        echo "  SUCCESS: Pattern is valid (result: $result)\n";
    }
}

// Test the specific route compilation
echo "\n\nTesting route compilation...\n";

function testCompilePattern($path, $constraints = []) {
    echo "Testing path: $path\n";
    if (!empty($constraints)) {
        echo "With constraints: " . json_encode($constraints) . "\n";
    }
    
    // Escape special regex characters except {}
    $pattern = preg_quote($path, '/');
    echo "After preg_quote: $pattern\n";

    // Replace parameter placeholders with regex groups
    if (empty($constraints)) {
        $pattern = preg_replace('/\\\{(\w+)\\\}/', '([^/]+)', $pattern);
    } else {
        // Replace parameters with their constraints
        $pattern = preg_replace_callback('/\\\{(\w+)\\\}/', function($matches) use ($constraints) {
            $paramName = $matches[1];
            if (isset($constraints[$paramName])) {
                return '(' . $constraints[$paramName] . ')';
            }
            return '([^/]+)';
        }, $pattern);
    }
    
    echo "After parameter replacement: $pattern\n";

    $finalPattern = '/^' . $pattern . '$/';
    echo "Final pattern: $finalPattern\n";
    
    // Test the final pattern
    error_clear_last();
    $result = @preg_match($finalPattern, '/test');
    $error = error_get_last();
    
    if ($result === false) {
        echo "  ERROR: Final pattern failed\n";
        if ($error) {
            echo "  Error message: " . $error['message'] . "\n";
        }
    } else {
        echo "  SUCCESS: Final pattern is valid\n";
    }
    
    echo "---\n";
}

// Test various route patterns
testCompilePattern('/auth/reset-password/{token}');
testCompilePattern('/{path}', ['path' => '.*']);
testCompilePattern('/movies/{id}');
