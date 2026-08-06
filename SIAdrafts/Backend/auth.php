<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Staff/Admin accounts carry a users.user_id session; professors log in
// against the professor table directly and carry professor_id instead.
if (empty($_SESSION['user_id']) && empty($_SESSION['professor_id'])) {
    header('Location: /SIAdrafts/Frontend/View/login.php');
    exit;
}
