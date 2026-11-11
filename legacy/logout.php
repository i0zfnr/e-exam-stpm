<?php

// ======================================================
// FAIL LOG KELUAR
// Sistem Peperiksaan STPM
// ======================================================

require_once 'config.php';

// Log aktiviti sebelum logout
if (isset($_SESSION['user_id'])) {
    logActivity($_SESSION['user_id'], 'LOGOUT', 'Pengguna telah log keluar dari sistem');
}

// Hapus semua session data
$_SESSION = [];

// Hapus session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy session
session_destroy();

// Redirect ke halaman login
header('Location: login.php');
exit();
