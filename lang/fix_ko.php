<?php
$content = json_decode(file_get_contents('ko.json'), true);
unset($content['Please confirm your new 단어.']);
file_put_contents('ko.json', json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo 'Fixed ko.json';
