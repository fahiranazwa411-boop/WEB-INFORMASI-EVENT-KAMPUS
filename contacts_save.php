1<?php
session_start();
require_once __DIR__ . '/../config/koneksi.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
  header("Location: ../../view/auth/login/login.html?error=forbidden");
  exit;
}

$adminId = (int)$_SESSION['user']['id'];

$ids   = $_POST['contact_id'] ?? [];
$names = $_POST['contact_name'] ?? [];
$phones= $_POST['contact_phone'] ?? [];

if (!is_array($ids) || !is_array($names) || !is_array($phones) || count($ids) !== count($names) || count($ids) !== count($phones)) {
  header("Location: ../../view/dashboard/admin/dashboard.php?show=kontak&error=invalid_request");
  exit;
}

$stmt = mysqli_prepare($conn, "UPDATE admin_contacts SET name=?, phone=?, updated_by=? WHERE id=?");
if (!$stmt) {
  header("Location: ../../view/dashboard/admin/dashboard.php?show=kontak&error=db_error");
  exit;
}

for ($i=0; $i<count($ids); $i++) {
  $id = (int)$ids[$i];
  $name = trim($names[$i]);
  $phone = trim($phones[$i]);
  if ($id <= 0 || $name === '' || $phone === '') continue;

  mysqli_stmt_bind_param($stmt, "ssii", $name, $phone, $adminId, $id);
  mysqli_stmt_execute($stmt);
}

header("Location: ../../view/dashboard/admin/dashboard.php?show=kontak&sukses=contacts_saved");
exit;
