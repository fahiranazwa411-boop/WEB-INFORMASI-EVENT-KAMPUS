<?php
// Ganti dengan Koneksi database kamu
$host = "127.0.0.1"; 
$user = "root";
$pass = "";
$db   = "polibatam_event";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
  die("Koneksi gagal: " . mysqli_connect_error());
}
?>