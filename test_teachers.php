<?php
require_once 'config/db.php';

$db = getDB();
$teachers = $db->query("SELECT id, name, email, role FROM users WHERE role = 'teacher'")->fetchAll();

echo "<h3>Teachers in Database:</h3>";
echo "<pre>";
print_r($teachers);
echo "</pre>";

echo "<h3>All Users:</h3>";
$all = $db->query("SELECT id, name, email, role FROM users")->fetchAll();
echo "<pre>";
print_r($all);
echo "</pre>";
?>
