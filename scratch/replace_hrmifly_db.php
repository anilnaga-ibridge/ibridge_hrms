<?php
$pdo = new PDO('mysql:host=localhost;dbname=ibd_hrm_v1', 'root', 'root');
$stmt = $pdo->query('SHOW TABLES');
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

$searchString = 'Hrmifly';
$replaceString = 'iBridge HR';
$updatedCols = 0;

foreach ($tables as $table) {
    $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        if (strpos($column['Type'], 'char') !== false || strpos($column['Type'], 'text') !== false || strpos($column['Type'], 'json') !== false) {
            $colName = $column['Field'];
            
            $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE `$colName` LIKE ?");
            $stmt2->execute(['%' . $searchString . '%']);
            $count = $stmt2->fetchColumn();
            
            if ($count > 0) {
                $updateStmt = $pdo->prepare("UPDATE `$table` SET `$colName` = REPLACE(`$colName`, ?, ?)");
                $updateStmt->execute([$searchString, $replaceString]);
                $updatedCols++;
                echo "Replaced '$searchString' with '$replaceString' in table '$table', column '$colName' ($count rows updated).\n";
            }
        }
    }
}

echo "Done updating database.\n";
