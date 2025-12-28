<?php
session_start();
require_once __DIR__ . '/../config/koneksi.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
  header("Location: ../../view/auth/login/login.html?error=forbidden");
  exit;
}

$adminId = (int)$_SESSION['user']['id'];

$eventId     = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
$category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
$title       = isset($_POST['title']) ? trim($_POST['title']) : '';
$desc        = isset($_POST['description']) ? trim($_POST['description']) : '';
$location    = isset($_POST['location']) ? trim($_POST['location']) : '';
$event_date  = isset($_POST['event_date']) ? $_POST['event_date'] : '';
$event_time  = isset($_POST['event_time']) && $_POST['event_time'] !== '' ? $_POST['event_time'] : null;

// validasi minimal
if ($category_id <= 0 || $title === '' || $desc === '' || $location === '' || $event_date === '') {
  header("Location: ../../view/dashboard/admin/dashboard.php?show=kelola-event&error=empty_fields");
  exit;
}

// handle upload
$imagePath = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
  if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    header("Location: ../../view/dashboard/admin/dashboard.php?show=kelola-event&error=upload_failed");
    exit;
  }

  $allowed = ['image/jpeg','image/png','image/webp'];
  $mime = mime_content_type($_FILES['image']['tmp_name']);
  if (!in_array($mime, $allowed, true)) {
    header("Location: ../../view/dashboard/admin/dashboard.php?show=kelola-event&error=upload_failed");
    exit;
  }

  $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
  $filename = 'event_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

  $uploadDir = __DIR__ . '/../../view/assets/uploads/';
  if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);

  $target = $uploadDir . $filename;
  if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
    header("Location: ../../view/dashboard/admin/dashboard.php?show=kelola-event&error=upload_failed");
    exit;
  }

  $imagePath = $filename;
}

if ($eventId > 0) {
  // update
  if ($imagePath) {
    $sql = "UPDATE events SET category_id=?, title=?, description=?, location=?, event_date=?, event_time=?, image_path=? WHERE id=?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "issssssi", $category_id, $title, $desc, $location, $event_date, $event_time, $imagePath, $eventId);
  } else {
    $sql = "UPDATE events SET category_id=?, title=?, description=?, location=?, event_date=?, event_time=? WHERE id=?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "isssssi", $category_id, $title, $desc, $location, $event_date, $event_time, $eventId);
  }

  if ($stmt && mysqli_stmt_execute($stmt)) {
    header("Location: ../../view/dashboard/admin/dashboard.php?show=kelola-event&sukses=event_updated");
    exit;
  }

  header("Location: ../../view/dashboard/admin/dashboard.php?show=kelola-event&error=db_error");
  exit;
}

// insert
$sql = "INSERT INTO events (category_id, title, description, location, event_date, event_time, image_path, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "issssssi", $category_id, $title, $desc, $location, $event_date, $event_time, $imagePath, $adminId);

if ($stmt && mysqli_stmt_execute($stmt)) {
  header("Location: ../../view/dashboard/admin/dashboard.php?show=kelola-event&sukses=event_added");
  exit;
}

header("Location: ../../view/dashboard/admin/dashboard.php?show=kelola-event&error=db_error");
exit;
