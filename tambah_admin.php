<?php
session_start();
require_once __DIR__ . '/../config/koneksi.php';

// Cek login admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../view/auth/login/login.html?error=forbidden");
    exit;
}

// Ambil POST
$name     = trim($_POST['name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role     = $_POST['role'] ?? 'admin';

// Validasi
if ($name === '' || $email === '' || $password === '') {
    header("Location: ../../view/dashboard/admin/dashboard.php?error=empty");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: ../../view/dashboard/admin/dashboard.php?error=email");
    exit;
}

// Hash password
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// Insert
$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO users (full_name, email, password_hash, role)
     VALUES (?, ?, ?, ?)"
);

if (!$stmt) {
    header("Location: ../../view/dashboard/admin/dashboard.php?error=db");
    exit;
}

mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $passwordHash, $role);
mysqli_stmt_execute($stmt);

mysqli_stmt_close($stmt);

// Redirect sukses
header("Location: ../../view/dashboard/admin/dashboard.php?sukses=admin_added");
exit;
