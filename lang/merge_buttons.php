<?php
$translations = [
    'id.json' => [
        'No, leave it' => 'Tidak, biarkan saja',
        'Yes, change it!' => 'Ya, ubah!',
        'Yes, save change!' => 'Ya, simpan perubahan!'
    ],
    'ja.json' => [
        'No, leave it' => 'いいえ、そのままにする',
        'Yes, change it!' => 'はい、変更する！',
        'Yes, save change!' => 'はい、変更を保存する！'
    ],
    'ko.json' => [
        'No, leave it' => '아니요, 그대로 두기',
        'Yes, change it!' => '네, 변경하기!',
        'Yes, save change!' => '네, 변경 사항 저장하기!'
    ],
    'zh.json' => [
        'No, leave it' => '不，保持原样',
        'Yes, change it!' => '是的，更改它！',
        'Yes, save change!' => '是的，保存更改！'
    ],
    'en.json' => [
        'No, leave it' => 'No, leave it',
        'Yes, change it!' => 'Yes, change it!',
        'Yes, save change!' => 'Yes, save change!'
    ]
];

foreach ($translations as $file => $trans) {
    if (file_exists($file)) {
        $content = json_decode(file_get_contents($file), true);
        foreach ($trans as $k => $v) {
            $content[$k] = $v;
        }
        file_put_contents($file, json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
echo "Buttons translated.\n";
