<?php
/**
 * Include this at the top of any Professor portal page or API file.
 * Mirrors require_student.php's shape, gating on the professor's own
 * session (professor_id) rather than require_role.php's staff role_name —
 * a professor session must never be interchangeable with a staff one.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_professor(bool $isApi = false): void
{
    if (empty($_SESSION['professor_id'])) {
        if ($isApi) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['error' => 'Please log in.']);
        } else {
            header('Location: /SIAdrafts/Frontend/View/login.php');
        }
        exit;
    }
}
