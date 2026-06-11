<?php
$pdo = new PDO('mysql:host=localhost;dbname=ibd_hrm_v1', 'root', 'root');
$stmt = $pdo->query('SHOW TABLES');
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

$searchString = 'codeifly.in';

foreach ($tables as $table) {
    $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        if (strpos($column['Type'], 'char') !== false || strpos($column['Type'], 'text') !== false || strpos($column['Type'], 'json') !== false) {
            $colName = $column['Field'];
            
            $stmt2 = $pdo->prepare("SELECT id, `$colName` FROM `$table` WHERE `$colName` LIKE ?");
            $stmt2->execute(['%' . $searchString . '%']);
            $results = $stmt2->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($results as $row) {
                echo "Found in table `$table`, column `$colName`, id `{$row['id']}`: " . substr($row[$colName], 0, 100) . "...\n";
            }
        }
    }
}
echo "Done.\n";
