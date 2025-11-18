<?php
require_once 'config/db.php';
$db = getDB();
$stmt = $db->query("DESCRIBE payments");
echo "<pre>";
print_r($stmt->fetchAll());
echo "</pre>";
?>
