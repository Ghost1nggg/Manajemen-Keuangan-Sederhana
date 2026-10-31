<?php
session_start();

$conn = new mysqli("localhost", "root", "", "db_keuangan");

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Mengatur bahasa
if (isset($_GET['bahasa'])) {
    $_SESSION['bahasa'] = $_GET['bahasa'] === 'en' ? 'en' : 'id';
}

$bahasa = $_SESSION['bahasa'] ?? 'id';

// Fungsi untuk menerjemahkan teks
function teks($id, $en) {
    global $bahasa;
    return $bahasa === 'en' ? $en : $id;
}
?>