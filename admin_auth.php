<?php
// Admin Authentication Check
require_once __DIR__ . '/config.php';

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_email']);
}

function getAdminId() {
    return $_SESSION['admin_id'] ?? null;
}

function isSuperAdmin() {
    return $_SESSION['admin_role'] ?? '' === 'super_admin';
}

function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireSuperAdmin() {
    requireAdminLogin();
    if (!isSuperAdmin()) {
        die('Access denied. Super admin only.');
    }
}