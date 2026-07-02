<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: /SIAdrafts/Frontend/View/index.php');
    exit;
}
