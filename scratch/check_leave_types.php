<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$leaveTypes = \App\Models\LeaveType::all();
foreach ($leaveTypes as $type) {
    echo "ID: " . $type->id . " | XID: " . $type->xid . " | Name: " . $type->name . " | isMonthlyLeave: " . ($type->isMonthlyLeave() ? 'YES' : 'NO') . "\n";
}
