<?php
// Helper functions for error handling

function setAlert($message, $type = 'error') {
    $_SESSION['alert'] = [
        'message' => $message,
        'type' => $type
    ];
}

function setError($message) {
    setAlert($message, 'error');
}

function setSuccess($message) {
    setAlert($message, 'success');
}

function setInfo($message) {
    setAlert($message, 'info');
}

function setWarning($message) {
    setAlert($message, 'warning');
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function redirectWithError($url, $message) {
    setError($message);
    redirect($url);
}

function redirectWithSuccess($url, $message) {
    setSuccess($message);
    redirect($url);
}
?>