<?php
$pdo = new PDO('mysql:host=localhost;dbname=ibd_hrm_v1', 'root', 'root');
$stmt = $pdo->query('SHOW TABLES');
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

$searchStrings = ['codeifly.in', 'lead-pro.in'];

$updated = 0;

foreach ($tables as $table) {
    $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        if (strpos($column['Type'], 'char') !== false || strpos($column['Type'], 'text') !== false || strpos($column['Type'], 'json') !== false) {
            $colName = $column['Field'];
            
            $stmt2 = $pdo->prepare("SELECT id, `$colName` FROM `$table` WHERE `$colName` LIKE ? OR `$colName` LIKE ?");
            $stmt2->execute(['%codeifly.in%', '%lead-pro.in%']);
            $results = $stmt2->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($results as $row) {
                $val = $row[$colName];
                
                // If it's JSON, decode and walk
                $decoded = json_decode($val, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    array_walk_recursive($decoded, function(&$item) {
                        if (is_string($item)) {
                            // Replace specific patterns with local paths
                            $item = preg_replace('/https?:\/\/[a-zA-Z0-9\-\.]*(codeifly\.in|lead-pro\.in)\/images\//', 'http://localhost:8888/hrm/public/images/', $item);
                            $item = preg_replace('/https?:\/\/[a-zA-Z0-9\-\.]*(codeifly\.in|lead-pro\.in)\/uploads\/website\//', 'http://localhost:8888/hrm/public/images/', $item);
                            $item = preg_replace('/https?:\/\/[a-zA-Z0-9\-\.]*(codeifly\.in|lead-pro\.in)[^\s]*/', 'http://localhost:8888/hrm/public', $item);
                        }
                    });
                    $newVal = json_encode($decoded, JSON_UNESCAPED_SLASHES); // or without it depending on how it was stored
                } else {
                    $newVal = preg_replace('/https?:\/\/[a-zA-Z0-9\-\.]*(codeifly\.in|lead-pro\.in)\/images\//', 'http://localhost:8888/hrm/public/images/', $val);
                    $newVal = preg_replace('/https?:\/\/[a-zA-Z0-9\-\.]*(codeifly\.in|lead-pro\.in)\/uploads\/website\//', 'http://localhost:8888/hrm/public/images/', $newVal);
                    $newVal = preg_replace('/https?:\/\/[a-zA-Z0-9\-\.]*(codeifly\.in|lead-pro\.in)[^\s]*/', 'http://localhost:8888/hrm/public', $newVal);
                }
                
                // In case json_encode removed escaped slashes but DB had them, let's just do a string replace on the raw JSON as well to be safe
                if ($val !== $newVal) {
                    $updateStmt = $pdo->prepare("UPDATE `$table` SET `$colName` = ? WHERE id = ?");
                    $updateStmt->execute([$newVal, $row['id']]);
                    $updated++;
                    echo "Updated table $table, col $colName, id {$row['id']}\n";
                } else {
                    // Fallback to string replace if json_encode produced identical result or different formatting
                    $newRaw = $val;
                    $newRaw = preg_replace('/https?:\\\\\/\\\\\/[a-zA-Z0-9\-\.]*(codeifly\.in|lead-pro\.in)\\\\\/images\\\\\//', 'http:\/\/localhost:8888\/hrm\/public\/images\/', $newRaw);
                    $newRaw = preg_replace('/https?:\\\\\/\\\\\/[a-zA-Z0-9\-\.]*(codeifly\.in|lead-pro\.in)\\\\\/uploads\\\\\/website\\\\\//', 'http:\/\/localhost:8888\/hrm\/public\/images\/', $newRaw);
                    $newRaw = preg_replace('/https?:\\\\\/\\\\\/[a-zA-Z0-9\-\.]*(codeifly\.in|lead-pro\.in)[^\\s\"\']*/', 'http:\/\/localhost:8888\/hrm\/public', $newRaw);
                    if ($val !== $newRaw) {
                        $updateStmt = $pdo->prepare("UPDATE `$table` SET `$colName` = ? WHERE id = ?");
                        $updateStmt->execute([$newRaw, $row['id']]);
                        $updated++;
                        echo "Updated table $table, col $colName, id {$row['id']} (Raw string replace)\n";
                    }
                }
            }
        }
    }
}
echo "Total updated: $updated\n";
