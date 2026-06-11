<?php
$pdo = new PDO('mysql:host=localhost;dbname=ibd_hrm_v1', 'root', 'root');
$stmt = $pdo->query('SELECT * FROM settings');
$settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$updated = 0;

foreach ($settings as $setting) {
    $credentials = $setting['credentials'];
    $newCredentials = str_replace(
        ['https://saas.lead-pro.in/uploads/website/', 'https://hrmifly.codeifly.in/images/dark.png'],
        ['http://localhost:8888/hrm/public/images/', 'http://localhost:8888/hrm/public/images/dark.png'],
        $credentials
    );

    if ($credentials !== $newCredentials) {
        $updateStmt = $pdo->prepare('UPDATE settings SET credentials = ? WHERE id = ?');
        $updateStmt->execute([$newCredentials, $setting['id']]);
        $updated++;
    }
}

echo "Successfully updated $updated rows in the settings table.\n";
