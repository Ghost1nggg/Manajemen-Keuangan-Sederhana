
<?php
require_once __DIR__ . '/koneksi.php';

// Menghapus status login
unset($_SESSION['login']);

// Menyimpan pesan setelah logout
$_SESSION['pesan_logout'] = true;

// Memperbarui ID session
session_regenerate_id(true);

// Kembali ke halaman login
header("Location: login.php");
exit;
