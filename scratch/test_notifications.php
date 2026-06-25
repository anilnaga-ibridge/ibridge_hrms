<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Holiday;
use App\Models\Task;
use App\Classes\Notify;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;

// 1. Get a test user (recipient)
$recipient = User::where('email', 'dm@ibridge.digital')->first();
if (!$recipient) {
    $recipient = User::first();
}

if (!$recipient) {
    echo "No users found in database!\n";
    exit(1);
}

echo "Recipient User: " . $recipient->name . " (Email: " . $recipient->email . ")\n";

// Clear previous notifications to start fresh
$recipient->notifications()->delete();
echo "Cleared existing notifications for Y.\n";

// 2. Test Colleague Birthday Notification
$birthdayUser = User::where('id', '!=', $recipient->id)->first();
if ($birthdayUser) {
    echo "Triggering Birthday notification for colleague: " . $birthdayUser->name . "\n";
    $notificationData = [
        'birthday_user_id' => $birthdayUser->id,
        'recipient_id' => $recipient->id,
    ];
    Notify::send('employee_birthday_reminder', $notificationData);
}

// 3. Test Holiday Notification
echo "Creating a dummy holiday for tomorrow...\n";
$tomorrow = Carbon::tomorrow();
$holiday = Holiday::whereDate('date', $tomorrow)->first();
if (!$holiday) {
    $holiday = new Holiday();
    $holiday->name = 'Test Tomorrow Holiday';
    $holiday->date = $tomorrow;
    $holiday->year = $tomorrow->year;
    $holiday->month = $tomorrow->month;
    $holiday->company_id = $recipient->company_id;
    $holiday->save();
}

echo "Running holiday:notify command...\n";
Artisan::call('holiday:notify');
$output = Artisan::output();
echo "Artisan Output: " . trim($output) . "\n";

// 4. Test Task Assignment Notification
echo "Creating a dummy task and assigning it to user...\n";
$task = new Task();
$task->name = 'Test Assigned Notification Task';
$task->company_id = $recipient->company_id;
$task->assignees = [$recipient->xid]; // Casted array of hashed IDs
$task->created_by = $recipient->id;
$task->status = 'pending';
$task->priority = 'high';
$task->start_date = Carbon::today();
$task->due_date = Carbon::tomorrow();
$task->save(); // This triggers task storing/stored/updated hooks

// 5. Fetch and print the generated notifications
$notifications = $recipient->notifications()->orderBy('created_at', 'desc')->get();
echo "\nTotal Notifications Generated: " . $notifications->count() . "\n";
foreach ($notifications as $notification) {
    echo "----------------------------------------\n";
    echo "ID: " . $notification->id . "\n";
    echo "Type: " . $notification->type . "\n";
    echo "Send For: " . ($notification->data['send_for'] ?? 'N/A') . "\n";
    echo "Data: " . json_encode($notification->data['data'] ?? []) . "\n";
    echo "Read At: " . ($notification->read_at ? $notification->read_at->toIso8601String() : 'Unread') . "\n";
}

// Cleanup dummy holiday and task
if ($holiday && $holiday->name == 'Test Tomorrow Holiday') {
    $holiday->delete();
}
if ($task) {
    $task->delete();
}
echo "\nTest Completed!\n";
