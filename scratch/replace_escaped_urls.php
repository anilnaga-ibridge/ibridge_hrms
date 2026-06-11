<?php
$pdo = new PDO('mysql:host=localhost;dbname=ibd_hrm_v1', 'root', 'root');

// Replace escaped URLs in the settings table
$stmt = $pdo->query('SELECT * FROM settings');
$settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$updated = 0;

foreach ($settings as $setting) {
    $credentials = $setting['credentials'];
    $newCredentials = str_replace(
        [
            'https:\/\/hrmifly.codeifly.in\/images\/',
            'https:\/\/saas.lead-pro.in\/uploads\/website\/'
        ],
        [
            'http:\/\/localhost:8888\/hrm\/public\/images\/',
            'http:\/\/localhost:8888\/hrm\/public\/images\/'
        ],
        $credentials
    );

    if ($credentials !== $newCredentials) {
        $updateStmt = $pdo->prepare('UPDATE settings SET credentials = ? WHERE id = ?');
        $updateStmt->execute([$newCredentials, $setting['id']]);
        $updated++;
    }
}

echo "Successfully updated $updated rows in the settings table.\n";
