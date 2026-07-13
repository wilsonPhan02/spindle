<?php

$dirsToScan = [__DIR__ . '/resources', __DIR__ . '/app', __DIR__ . '/routes'];
$langDir = __DIR__ . '/lang';
$langFiles = ['en.json', 'id.json', 'ja.json', 'ko.json', 'zh.json'];

$strings = [];

function scanDirectory($dir, &$strings) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && in_array($file->getExtension(), ['php'])) {
            $content = file_get_contents($file->getPathname());
            
            // Match __('String') or @lang('String')
            // Using a more precise regex: single quotes or double quotes, taking care of escaped quotes.
            // Example: __('Hello') or __("Hello")
            preg_match_all('/__\(\s*\'((?:[^\\\\\'\n]|\\\\.)*)\'\s*(?:,|\))/U', $content, $matches1);
            preg_match_all('/__\(\s*"((?:[^\\\\"\n]|\\\\.)*)"\s*(?:,|\))/U', $content, $matches2);
            preg_match_all('/@lang\(\s*\'((?:[^\\\\\'\n]|\\\\.)*)\'\s*(?:,|\))/U', $content, $matches3);
            preg_match_all('/@lang\(\s*"((?:[^\\\\"\n]|\\\\.)*)"\s*(?:,|\))/U', $content, $matches4);
            
            $matches = array_merge($matches1[1], $matches2[1], $matches3[1], $matches4[1]);
            
            foreach ($matches as $match) {
                // Unescape quotes
                $match = stripslashes($match);
                if (!empty($match)) $strings[$match] = true;
            }
        }
    }
}

foreach ($dirsToScan as $dir) {
    if (is_dir($dir)) scanDirectory($dir, $strings);
}

$foundStrings = array_keys($strings);
sort($foundStrings);

echo "Found " . count($foundStrings) . " unique translatable strings.\n";

// Load existing keys from en.json (as baseline)
$baseline = json_decode(file_get_contents($langDir . '/en.json'), true) ?: [];
$allValidKeys = array_merge(array_keys($baseline), $foundStrings);

foreach ($langFiles as $file) {
    $path = $langDir . '/' . $file;
    $existing = [];
    if (file_exists($path)) {
        $existing = json_decode(file_get_contents($path), true) ?: [];
    }
    
    // Clean up invalid keys that have \n or too long and not in allValidKeys
    foreach ($existing as $key => $val) {
        if (strpos($key, "\n") !== false || strpos($key, "]) !!}") !== false) {
            unset($existing[$key]);
        }
    }
    
    $added = 0;
    foreach ($foundStrings as $str) {
        if (!isset($existing[$str])) {
            $existing[$str] = $str; // Default translation is the string itself
            $added++;
        }
    }
    
    // Sort keys alphabetically for cleanliness
    ksort($existing);
    
    file_put_contents($path, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "Updated $file: Added $added new keys.\n";
}
