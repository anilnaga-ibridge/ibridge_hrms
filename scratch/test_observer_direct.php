<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Leave;

$dispatcher = Leave::getEventDispatcher();
if (!$dispatcher) {
    echo "No event dispatcher registered on Leave model!\n";
} else {
    echo "Event dispatcher is registered.\n";
    // Check if deleting listener is registered
    $listeners = $dispatcher->getListeners('eloquent.deleting: App\Models\Leave');
    echo "Listeners for deleting: " . count($listeners) . "\n";
    foreach ($listeners as $listener) {
        // Reflection to see what listener it is
        echo "Listener type: " . get_class($listener) . "\n";
    }
}
