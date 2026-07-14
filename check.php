<?php
$data = json_decode(file_get_contents('lang/id.json'), true);
foreach($data as $k => $v) {
    if($k === $v) echo $k . PHP_EOL;
}
