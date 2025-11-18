<?php
session_start();
require_once '../config/db.php';
require_once '../utils/helpers.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin'])) {
    redirectWithError('../views/salaries.php', 'Unauthorized');
    exit;
}

$db = getDB();
$action = $_POST['action'] ?? '';

try {
    if ($action == 'add') {
        $net_salary = $_POST['basic_salary'] + $_POST['allowances'] - $_POST['deductions'];
        $stmt = $db->prepare("INSERT INTO salaries (user_id, basic_salary, allowances, deductions, net_salary, salary_month, processed_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([
            $_POST['user_id'],
            $_POST['basic_salary'],
            $_POST['allowances'],
            $_POST['deductions'],
            $net_salary,
            $_POST['salary_month'],
            $_SESSION['user_id']
        ]);
        redirectWithSuccess('../views/salaries.php', 'Salary processed successfully!');
    }
} catch (Exception $e) {
    redirectWithError('../views/salaries.php', 'Error: ' . $e->getMessage());
}
?>
