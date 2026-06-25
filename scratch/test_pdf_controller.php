<?php

// Bootstrapping Laravel application
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\Api\PdfController;

// Create controller instance
$controller = new PdfController();

// Use Reflection to test the private sanitizeFilename method
$reflection = new ReflectionClass(PdfController::class);
$method = $reflection->getMethod('sanitizeFilename');
$method->setAccessible(true);

$testCases = [
    'standard_filename.pdf' => 'standard_filename.pdf',
    'file_with_spaces and letters.pdf' => 'file_with_spaces and letters.pdf',
    'traversal/../../etc/passwd.pdf' => 'traversaletcpasswd.pdf',
    "newline\nin\nfilename.pdf" => 'newlineinfilename.pdf',
    "carriage\rin\rfilename.pdf" => 'carriageinfilename.pdf',
    'quotes"in"filename.pdf' => 'quotesinfilename.pdf',
    "control\x00character.pdf" => 'controlcharacter.pdf',
    "backslash\\path.pdf" => 'backslashpath.pdf',
];

$allPassed = true;
foreach ($testCases as $input => $expected) {
    $result = $method->invoke($controller, $input);
    if ($result === $expected) {
        echo "PASS: Input [" . json_encode($input) . "] -> Output [" . json_encode($result) . "]\n";
    } else {
        echo "FAIL: Input [" . json_encode($input) . "]\n  Expected: [" . json_encode($expected) . "]\n  Got:      [" . json_encode($result) . "]\n";
        $allPassed = false;
    }
}

if ($allPassed) {
    echo "\nAll PDF controller filename sanitization test cases passed!\n";
} else {
    echo "\nSome PDF controller filename sanitization test cases failed.\n";
    exit(1);
}
