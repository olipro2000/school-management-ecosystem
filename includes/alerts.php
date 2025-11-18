<?php
if (!function_exists('showAlert')) {
    function showAlert($message, $type = 'error') {
        $icons = [
            'error' => '⚠️',
            'success' => '✅',
            'info' => 'ℹ️',
            'warning' => '⚡'
        ];
        
        echo "<div class='alert alert-{$type}' id='alert-message'>
                <span class='alert-icon'>{$icons[$type]}</span>
                <span class='alert-text'>{$message}</span>
                <button class='alert-close' onclick='closeAlert()'>&times;</button>
              </div>";
        
        unset($_SESSION['alert']);
    }
}

if (isset($_SESSION['alert'])) {
    showAlert($_SESSION['alert']['message'], $_SESSION['alert']['type']);
}
?>