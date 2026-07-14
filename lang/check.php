<?php
$id = json_decode(file_get_contents('id.json'), true);
$ja = json_decode(file_get_contents('ja.json'), true);
$ko = json_decode(file_get_contents('ko.json'), true);
$zh = json_decode(file_get_contents('zh.json'), true);

$missingInJa = array_keys(array_diff_key($id, $ja));
$missingInKo = array_keys(array_diff_key($id, $ko));
$missingInZh = array_keys(array_diff_key($id, $zh));

echo json_encode([
    'ja' => $missingInJa,
    'ko' => $missingInKo,
    'zh' => $missingInZh
], JSON_PRETTY_PRINT);
