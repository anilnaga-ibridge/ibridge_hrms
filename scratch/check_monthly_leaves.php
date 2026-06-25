<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$leaves = \App\Models\EmployeeMonthlyLeave::with('employee')->orderBy('credited_date', 'asc')->get();

echo "Total monthly leaves credited: " . $leaves->count() . "\n\n";

foreach ($leaves as $leave) {
    echo "Employee: " . $leave->employee->name . "\n";
    echo "Credited Date: " . $leave->credited_date->format('Y-m-d') . "\n";
    echo "Status: " . $leave->status . "\n";
    echo "Used Date: " . ($leave->used_date ? $leave->used_date->format('Y-m-d') : 'N/A') . "\n";
    echo "-------------------------------------\n";
}
