<?php

// Bootstrapping Laravel application
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;

// Define a MockCompany that overrides the light_logo_url accessor
class MockCompany extends Company
{
    public $customLightLogoUrl;

    public function getLightLogoUrlAttribute()
    {
        return $this->customLightLogoUrl;
    }
}

// Mock a company object with malicious values
$company = new MockCompany();
$company->name = 'Malicious <script>alert("XSS")</script> Company';
$company->customLightLogoUrl = 'http://example.com/logo.png" onload="alert(1)';

$htmlcontent = "
Welcome to ##COMPANY_NAME##!
Here is our logo: ##COMPANY_LOGO##
";

// Emulate the PdfController replacement block
$logoUrl = e($company->light_logo_url);
$logoHtml = $logoUrl ? '<img src="' . $logoUrl . '" style="max-height: 50px; max-width: 180px;" />' : '';
$htmlcontent = str_replace('##COMPANY_LOGO##', $logoHtml, $htmlcontent);
$htmlcontent = str_replace('##COMPANY_NAME##', e($company->name), $htmlcontent);

echo "--- Escaped HTML Content Output ---\n";
echo $htmlcontent . "\n";
echo "-----------------------------------\n";

// Assert that e() escaping worked
if (strpos($htmlcontent, '<script>') !== false) {
    echo "FAIL: Raw script tags found in output!\n";
    exit(1);
}

if (strpos($htmlcontent, 'logo.png" onload=') !== false) {
    echo "FAIL: Attribute breakout found in output!\n";
    exit(1);
}

if (strpos($htmlcontent, '&quot; onload=&quot;') !== false && strpos($htmlcontent, '&lt;script&gt;') !== false) {
    echo "PASS: XSS vectors successfully escaped!\n";
} else {
    echo "FAIL: Expected escaped strings not found.\n";
    exit(1);
}
