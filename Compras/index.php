<?php
// Simple Router / Landing
require_once 'config/db.php';
session_start();

// Check if logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['user_role'] ?? '';

// If not logged in, redirect to login (unless already there)
$request = $_SERVER['REQUEST_URI'];
if (!$isLoggedIn && strpos($request, 'login.php') === false) {
    header('Location: login.php');
    exit;
}

// Simple routing based on role
if ($isLoggedIn) {
    if ($userRole === 'comprador') {
        header('Location: views/dashboard/index.php');
    } else {
        header('Location: views/requests/my_requests.php');
    }
    exit;
}
?>