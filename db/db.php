<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "inr";

$conn = new mysqli($host, $user, $pass, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}
?>
