<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Classes\CommonHrm;
use App\Classes\RaviCommonHrm;
use Illuminate\Http\Request;

// Mock a request with a specific IP address
$request = Request::create('/test', 'GET', [], [], [], ['REMOTE_ADDR' => '203.0.113.195']);
$app->instance('request', $request);

$ip1 = CommonHrm::getIpAddress();
$ip2 = RaviCommonHrm::getIpAddress();

echo "Mocked IP: 203.0.113.195\n";
echo "CommonHrm::getIpAddress(): " . $ip1 . "\n";
echo "RaviCommonHrm::getIpAddress(): " . $ip2 . "\n";

if ($ip1 === '203.0.113.195' && $ip2 === '203.0.113.195') {
    echo "IP resolution test PASSED!\n";
} else {
    echo "IP resolution test FAILED!\n";
}
