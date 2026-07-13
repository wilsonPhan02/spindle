<?php
$id = json_decode(file_get_contents('id.json'), true);
$en = json_decode(file_get_contents('en.json'), true);
$missingInEn = array_keys(array_diff_key($id, $en));

$en_translations = [];
foreach ($missingInEn as $key) {
    $en_translations[$key] = $key;
}

$content = json_decode(file_get_contents('en.json'), true);
foreach ($en_translations as $k => $v) {
    if (!isset($content[$k])) {
        $content[$k] = $v;
    }
}
file_put_contents('en.json', json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "English synced.\n";
