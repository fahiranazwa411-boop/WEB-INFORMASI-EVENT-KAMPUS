<?php

require_once __DIR__ . '/includes/config/koneksi.php'; // Semicolon added here

$full_name = 'fahira';
$email = 'fahira@example.com';
$password = 'fahira123';
$role = 'admin';

$password_hash = password_hash($password, PASSWORD_BCRYPT);

// Corrected SQL query with proper placeholders
$sql = "INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);

// Corrected variable binding
mysqli_stmt_bind_param($stmt, 'ssss', $full_name, $email, $password_hash, $role);

// Execute the prepared statement
if (mysqli_stmt_execute($stmt)){
    echo "Pengguna berhasil ditambahkan!\n"; // Fixed typo in echo statement
}else{
    echo "Terjadi kesalahan: " . mysqli_error($conn) . "\n";
}

// Close the prepared statement
mysqli_stmt_close($stmt);
?>
