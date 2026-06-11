<?php
$pdo = new PDO('mysql:host=localhost;dbname=ibd_hrm_v1', 'root', 'root');
$stmt = $pdo->query('SELECT credentials FROM settings WHERE id = 32');
$credentials = $stmt->fetchColumn();

$pos = strpos($credentials, 'codeifly.in');
if ($pos !== false) {
    echo "Found at pos $pos:\n";
    echo substr($credentials, max(0, $pos - 50), 100) . "\n";
} else {
    echo "Not found in id 32.\n";
}
