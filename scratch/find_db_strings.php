<?php
$pdo = new PDO('mysql:host=localhost;dbname=ibd_hrm_v1', 'root', 'root');
$stmt = $pdo->query('SHOW TABLES');
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

$searchStrings = ['saas.lead-pro.in', 'hrmifly.codeifly.in'];

foreach ($tables as $table) {
    $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        if (strpos($column['Type'], 'char') !== false || strpos($column['Type'], 'text') !== false || strpos($column['Type'], 'json') !== false) {
            $colName = $column['Field'];
            foreach ($searchStrings as $searchString) {
                $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE `$colName` LIKE ?");
                $stmt2->execute(['%' . $searchString . '%']);
                $count = $stmt2->fetchColumn();
                if ($count > 0) {
                    echo "Found $count matches for '$searchString' in table '$table', column '$colName'\n";
                    
                    // Fetch and show one example
                    $stmt3 = $pdo->prepare("SELECT `$colName` FROM `$table` WHERE `$colName` LIKE ? LIMIT 1");
                    $stmt3->execute(['%' . $searchString . '%']);
                    $example = $stmt3->fetchColumn();
                    echo "Example: " . substr($example, 0, 150) . "\n\n";
                }
            }
        }
    }
}
