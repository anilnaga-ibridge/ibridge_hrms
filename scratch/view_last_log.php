<?php

$logPath = __DIR__ . '/../storage/logs/laravel.log';
if (!file_exists($logPath)) {
    echo "Log file not found!\n";
    exit;
}

clearstatcache(true, $logPath);
$fileSize = filesize($logPath);
$readLength = 15000;

$file = fopen($logPath, 'r');
if ($file) {
    if ($fileSize > $readLength) {
        fseek($file, -$readLength, SEEK_END);
    } else {
        $readLength = $fileSize;
    }

    $lastPart = '';
    if ($readLength > 0) {
        $lastPart = fread($file, $readLength);
    }
    fclose($file);

    echo $lastPart;
}
