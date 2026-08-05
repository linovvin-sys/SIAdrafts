<?php
/**
 * Include this at the top of any Student portal page or API file.
 * Mirrors require_role.php's shape, but gates on the separate
 * student_portal_account session (student_id), never on user_id/role_name —
 * a student session must never be interchangeable with a staff one.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_student(bool $isApi = false): void
{
    if (empty($_SESSION['student_id'])) {
        if ($isApi) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['error' => 'Please log in.']);
        } else {
            header('Location: /SIAdrafts/Frontend/View/Student/login.php');
        }
        exit;
    }

    // Forced password change blocks every page except the change-password
    // screen itself until the temp credential is replaced.
    $cur = basename($_SERVER['PHP_SELF']);
    if (!empty($_SESSION['must_change_password']) && $cur !== 'change_password.php') {
        if ($isApi) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['error' => 'Please set a new password first.']);
        } else {
            header('Location: /SIAdrafts/Frontend/View/Student/change_password.php');
        }
        exit;
    }
}
