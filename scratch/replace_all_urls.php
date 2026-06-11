<?php
$pdo = new PDO('mysql:host=localhost;dbname=ibd_hrm_v1', 'root', 'root');
$stmt = $pdo->query('SHOW TABLES');
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

$replacements = [
    'https://hrmifly.codeifly.in/images/' => 'http://localhost:8888/hrm/public/images/',
    'https://hrmifly.codeifly.in' => 'http://localhost:8888/hrm/public',
    'https://saas.lead-pro.in/uploads/website/' => 'http://localhost:8888/hrm/public/images/'
];

foreach ($tables as $table) {
    $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        if (strpos($column['Type'], 'char') !== false || strpos($column['Type'], 'text') !== false || strpos($column['Type'], 'json') !== false) {
            $colName = $column['Field'];
            
            foreach ($replacements as $search => $replace) {
                $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE `$colName` LIKE ?");
                $stmt2->execute(['%' . $search . '%']);
                $count = $stmt2->fetchColumn();
                
                if ($count > 0) {
                    $updateStmt = $pdo->prepare("UPDATE `$table` SET `$colName` = REPLACE(`$colName`, ?, ?)");
                    $updateStmt->execute([$search, $replace]);
                    echo "Replaced '$search' with '$replace' in table '$table', column '$colName' ($count rows updated).\n";
                }
            }
        }
    }
}

echo "Database fully updated with local URLs.\n";
