<?php
$zh = json_decode(file_get_contents('lang/zh.json'), true);
$untranslated = [];
foreach($zh as $k => $v) {
    if ($k === $v && !is_numeric($k)) {
        $untranslated[$k] = $v;
    }
}
echo "Untranslated count: " . count($untranslated) . "\n";
file_put_contents('untranslated_zh.json', json_encode($untranslated, JSON_PRETTY_PRINT));
