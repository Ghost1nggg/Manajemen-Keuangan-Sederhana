<?php
//insertdata.php
include "koneksi.php";

if ($conn) {
	echo "Koneksi Database OKE!";
}

$username = "admin";
$password = sha1("admin12");

$st = $conn->prepare(
    "INSERT INTO users (username, password) VALUES (?, ?)"
);

$st->bind_param("ss", $username, $password);
$st->execute();

echo "Akun berhasil dibuat!";
$st->close();

