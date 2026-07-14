<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$templates = App\Models\Template::whereNull('user_id')->with('sections')->get();
$strings = [];

foreach ($templates as $template) {
    if ($template->description) {
        $strings[$template->description] = $template->description;
    }
    foreach ($template->sections as $section) {
        if ($section->goal) {
            $strings[$section->goal] = $section->goal;
        }
    }
}

file_put_contents('seeded_strings.json', json_encode(array_values($strings), JSON_PRETTY_PRINT));
echo "Found " . count($strings) . " strings.\n";
