<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_auth() {
    if (empty($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['error' => 'Please log in.']);
        exit;
    }
}
