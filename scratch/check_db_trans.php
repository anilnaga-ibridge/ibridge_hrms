<?php

// Bootstrapping Laravel application
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Translation;

$translations = Translation::where('group', 'leave')
    ->where('key', 'like', '%monthly%')
    ->get();

if ($translations->isEmpty()) {
    echo "No translations found for group 'leave' and keys containing 'monthly'!\n";
} else {
    echo "Translations found in DB:\n";
    foreach ($translations as $t) {
        echo "- Lang ID {$t->lang_id} | Group: {$t->group} | Key: {$t->key} | Value: \"{$t->value}\"\n";
    }
}
