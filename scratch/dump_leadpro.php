<?php
$pdo = new PDO('mysql:host=localhost;dbname=ibd_hrm_v1', 'root', 'root');
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

$searchString = 'lead-pro.in';

foreach ($tables as $table) {
    $columns = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        if (strpos($column['Type'], 'char') !== false || strpos($column['Type'], 'text') !== false || strpos($column['Type'], 'json') !== false) {
            $colName = $column['Field'];
            $stmt = $pdo->prepare("SELECT id, `$colName` FROM `$table` WHERE `$colName` LIKE ?");
            $stmt->execute(['%' . $searchString . '%']);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                echo "Found $searchString in table `$table`, column `$colName`, id `{$row['id']}`\n";
            }
        }
    }
}
echo "Done.\n";
