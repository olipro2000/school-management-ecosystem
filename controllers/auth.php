<?php
session_start();
require_once '../config/db.php';

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'];
        
        // Log activity
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, 'login', ?)");
        $stmt->execute([$user['id'], $_SERVER['REMOTE_ADDR']]);
        
        header('Location: ../dashboard.php');
    } else {
        header('Location: ../index.php?error=invalid');
    }
}

if (isset($_GET['logout'])) {
    // Log activity before destroying session
    if (isset($_SESSION['user_id'])) {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, 'logout', ?)");
        $stmt->execute([$_SESSION['user_id'], $_SERVER['REMOTE_ADDR']]);
    }
    
    session_destroy();
    header('Location: ../index.php');
    exit;
}
?>