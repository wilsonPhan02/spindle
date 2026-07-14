<?php
$id = json_decode(file_get_contents('lang/id.json'), true);
$zh = json_decode(file_get_contents('lang/zh.json'), true);
$id['No description for this template'] = 'Tidak ada deskripsi untuk template ini';
$zh['No description for this template'] = '该模板暂无描述';
$id['No details for this section yet.'] = 'Belum ada detail untuk bagian ini.';
$zh['No details for this section yet.'] = '该部分暂无详细信息。';
$id['No detailed steps for this template yet.'] = 'Belum ada langkah terperinci untuk template ini.';
$zh['No detailed steps for this template yet.'] = '该模板暂无详细步骤。';
$id['Describe what needs to happen in this section...'] = 'Jelaskan apa yang harus terjadi di bagian ini...';
$zh['Describe what needs to happen in this section...'] = '描述这部分需要发生什么...';
file_put_contents('lang/id.json', json_encode($id, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
file_put_contents('lang/zh.json', json_encode($zh, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Done";
