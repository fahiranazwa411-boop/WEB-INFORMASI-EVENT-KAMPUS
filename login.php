<?php
session_start();

require_once __DIR__ . '/../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../auth/login/Login.html?error=invalid_request");
    exit;
}

if (!isset($conn) || !$conn) {
    header("Location: ../../auth/login/Login.html?error=db_connection");
    exit;
}

$email    = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if ($email === '' || $password === '') {
    header("Location: ../../auth/login/Login.html?error=empty_fields");
    exit;
}

$sql  = "SELECT id, full_name, email, password_hash, role FROM users WHERE email = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    header("Location: ../../view/auth/login/Login.html?error=query_prepare_failed");
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) === 1) {
    $user = mysqli_fetch_assoc($result);

    if (!password_verify($password, $user['password_hash'])) {
        header("Location: ../../view/auth/login/Login.html?error=password_salah");
        exit;
    }

    $_SESSION['user'] = [
        'id'    => (int)$user['id'],
        'name'  => $user['full_name'],
        'email' => $user['email'],
        'role'  => $user['role']
    ];

    if ($user['role'] === 'admin') {
        header("Location: ../../view/dashboard/admin/dashboard.php");
        exit;
    }

    if ($user['role'] === 'mahasiswa') {
        header("Location: ../../view/dashboard/mahasiswa/dashboard.php");
        exit;
    }

    session_destroy();
    header("Location: ../../view/auth/login/Login.html?error=invalid_role");
    exit;

} else {
    header("Location: ../../view/auth/login/Login.html?error=akun_tidak_ditemukan");
    exit;
}
