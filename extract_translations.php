<?php

$dirs = [
    __DIR__ . '/resources/views',
    __DIR__ . '/app'
];

$strings = [];

foreach ($dirs as $dir) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $content = file_get_contents($file->getPathname());
            // Match __('string') or __("string") or trans('string') or trans("string")
            preg_match_all('/(?:__|trans)\(\s*[\'"](.+?)[\'"]\s*(?:,|\))/s', $content, $matches);
            
            if (!empty($matches[1])) {
                foreach ($matches[1] as $str) {
                    // Ignore empty or dynamic strings
                    if (trim($str) !== '' && strpos($str, '$') === false) {
                        $strings[$str] = $str;
                    }
                }
            }
        }
    }
}

$langs = ['en', 'id', 'ja', 'zh', 'ko'];

foreach ($langs as $lang) {
    $file = __DIR__ . '/lang/' . $lang . '.json';
    $existing = [];
    if (file_exists($file)) {
        $existing = json_decode(file_get_contents($file), true) ?? [];
    }
    
    $merged = array_merge($strings, $existing); // existing takes precedence
    
    // Sort keys alphabetically for cleaner file
    ksort($merged);
    
    file_put_contents($file, json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "Extracted " . count($strings) . " unique strings. Updated $lang\n";
}
