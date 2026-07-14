<?php
$langDir = __DIR__ . '/lang';
$langFiles = ['en.json', 'id.json', 'ja.json', 'ko.json', 'zh.json'];

$garbagePatterns = [
    "Are u ready to spin the",
    "Copyright ©",
    "I have read and agree to the",
    "We\\'ve sent the magic link to <strong>:email</strong>.",
    "Didn\\'t",
    "Let\\'s set up your creative identity.",
    "Looks like it\\'s a bit quiet in here...",
    "e.g. My Hero\\'s Journey",
    "\\",
    "]"
];

$goodKeysToTranslate = [
    "Are u ready to spin the :yarn?" => "Apakah kamu siap merajut :yarn?",
    "Copyright © :year Spindle. Empowering storytellers to weave unforgettable narratives. Developed and maintained by the Spindle Team. All rights reserved." => "Hak Cipta © :year Spindle. Memberdayakan pencerita untuk menenun narasi yang tak terlupakan. Dikembangkan dan dikelola oleh Tim Spindle. Seluruh hak cipta dilindungi.",
    "I have read and agree to the" => "Saya telah membaca dan menyetujui",
    "We've sent the magic link to <strong>:email</strong>." => "Kami telah mengirimkan tautan ajaib ke <strong>:email</strong>.",
    "Didn't receive the email yet?" => "Belum menerima email?",
    "Drop your email, and we'll send a magic link to help you write the next line." => "Masukkan email Anda, dan kami akan mengirim tautan ajaib untuk membantu Anda menulis kalimat berikutnya.",
    "Email" => "Email",
    "Emoji" => "Emoji",
    "Instagram" => "Instagram",
    "Leaving the writer's desk? Your draft will wait here." => "Meninggalkan meja penulis? Draf Anda akan menunggu di sini.",
    "Let's set up your creative identity." => "Mari atur identitas kreatif Anda.",
    "Looks like it's a bit quiet in here..." => "Sepertinya agak sepi di sini...",
    "Tag..." => "Tag...",
    "The Great Tangle" => "The Great Tangle",
    "The Spindle" => "The Spindle",
    "The Weaver" => "The Weaver",
    "You Didn't Have Any Chapters!" => "Anda Belum Memiliki Bab!",
    "You Didn't Have Any Characters!" => "Anda Belum Memiliki Karakter!",
    "You Didn't Have Any Notes!" => "Anda Belum Memiliki Catatan!",
    "You Didn't Have Any Project!" => "Anda Belum Memiliki Proyek!",
    "e.g. My Hero's Journey" => "contoh: Perjalanan Pahlawanku",
    "terms and conditions" => "syarat dan ketentuan"
];

foreach ($langFiles as $file) {
    $path = $langDir . '/' . $file;
    $existing = json_decode(file_get_contents($path), true) ?: [];
    
    // Remove garbage keys
    foreach ($existing as $key => $val) {
        foreach ($garbagePatterns as $pattern) {
            if (strpos($key, $pattern) !== false) {
                unset($existing[$key]);
                break;
            }
        }
    }
    
    // Add missing good keys
    foreach ($goodKeysToTranslate as $en => $id) {
        if ($file === 'id.json') {
            $existing[$en] = $id;
        } else {
            if (!isset($existing[$en])) {
                $existing[$en] = $en;
            }
        }
    }
    
    ksort($existing);
    file_put_contents($path, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "Cleaned up $file\n";
}
