<?php
session_start();

// Path aman
require_once __DIR__ . '/../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../views/auth/register/register.html?error=invalid_request");
    exit;
}

if (!isset($conn) || !$conn) {
    header("Location: ../../views/auth/register/register.html?error=db_connection");
    exit;
}

// Ambil input (tanpa role)
$full_name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email     = isset($_POST['email']) ? trim($_POST['email']) : '';
$password  = isset($_POST['password']) ? $_POST['password'] : '';

// Role dipaksa mahasiswa (admin tidak boleh register dari form)
$role = 'mahasiswa';

// Validasi dasar
if ($full_name === '' || $email === '' || $password === '') {
    header("Location: ../../view/auth/register/Register.html?error=empty_fields");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: ../../view/auth/register/Register.html?error=invalid_email");
    exit;
}

// Password minimal
if (strlen($password) < 6) {
    header("Location: ../../view/auth/register/Register.htmll?error=password_too_short");
    exit;
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Cek email sudah dipakai (prepared statement)
$checkSql  = "SELECT id FROM users WHERE email = ? LIMIT 1";
$checkStmt = mysqli_prepare($conn, $checkSql);

if (!$checkStmt) {
    header("Location: ../../view/auth/register/Register.html?error=query_prepare_failed");
    exit;
}

mysqli_stmt_bind_param($checkStmt, "s", $email);
mysqli_stmt_execute($checkStmt);
$checkRes = mysqli_stmt_get_result($checkStmt);

if ($checkRes && mysqli_num_rows($checkRes) > 0) {
    header("Location: ../../view/auth/register/Register.html?error=email_exists");
    exit;
}

// Insert user (sesuai kolom DB)
$insertSql  = "INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, ?)";
$insertStmt = mysqli_prepare($conn, $insertSql);

if (!$insertStmt) {
    header("Location: ../../view/auth/register/Register.html?error=insert_prepare_failed");
    exit;
}

mysqli_stmt_bind_param($insertStmt, "ssss", $full_name, $email, $password_hash, $role);

if (mysqli_stmt_execute($insertStmt)) {
    header("Location: ../../view/auth/login/login.html?sukses=register_success");
    exit;
}

header("Location: ../../view/auth/register/Register.html?error=register_failed");
exit;
