<?php
session_start();
require_once __DIR__ . '/../config/koneksi.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
  header("Location: ../../view/auth/login/login.html?error=forbidden");
  exit;
}

$eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
if ($eventId <= 0) {
  header("Location: ../../view/dashboard/admin/dashboard.php?show=kelola-event&error=invalid_request");
  exit;
}

$stmt = mysqli_prepare($conn, "DELETE FROM events WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $eventId);

if ($stmt && mysqli_stmt_execute($stmt)) {
  header("Location: ../../view/dashboard/admin/dashboard.php?show=kelola-event&sukses=event_deleted");
  exit;
}

header("Location: ../../view/dashboard/admin/dashboard.php?show=kelola-event&error=db_error");
exit;
