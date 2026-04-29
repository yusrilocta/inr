<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Halaman yang tidak butuh login
$public_pages = ['login'];

// Ambil parameter page dari URL
$page = isset($_GET['page']) ? $_GET['page'] : 'login';

// Cek apakah user sudah login
$is_logged_in = isset($_SESSION['user_id']);

// Redirect ke login jika user belum login dan akses ke halaman selain public
if (!$is_logged_in && !in_array($page, $public_pages)) {
    header("Location: index.php?page=login");
    exit;
}

// Routing halaman
$page_file = "pages/{$page}.php";
if (file_exists($page_file)) {
    // Untuk halaman selain login, sertakan header & footer
   // if ($page !== 'login') include 'core/header.php';
    
    include $page_file;
    
   // if ($page !== 'login') include 'core/footer.php';
} else {
    // Halaman tidak ditemukan
    include 'pages/notfound.php';
}
?>
