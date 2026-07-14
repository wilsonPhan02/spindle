<?php

$langs = ['en', 'id', 'ja', 'zh', 'ko'];

$translations = [
    'en' => [
        'Terms & Conditions' => 'Terms & Conditions',
        'I Understand' => 'I Understand',
    ],
    'id' => [
        'Terms & Conditions' => 'Syarat & Ketentuan',
        'I Understand' => 'Saya Mengerti',
    ],
    'ja' => [
        'Terms & Conditions' => '利用規約',
        'I Understand' => '理解しました',
    ],
    'zh' => [
        'Terms & Conditions' => '条款和条件',
        'I Understand' => '我明白了',
    ],
    'ko' => [
        'Terms & Conditions' => '이용 약관',
        'I Understand' => '이해했습니다',
    ],
];

foreach ($langs as $lang) {
    $file = __DIR__ . "/lang/{$lang}.json";
    if (file_exists($file)) {
        $json = json_decode(file_get_contents($file), true) ?: [];
        foreach ($translations[$lang] as $key => $val) {
            $json[$key] = $val;
        }
        // sort keys alphabetically
        ksort($json);
        file_put_contents($file, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "Updated $lang.json\n";
    }
}
