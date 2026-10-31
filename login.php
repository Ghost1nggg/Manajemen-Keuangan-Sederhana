
<?php
require 'koneksi.php';

$error = '';
$pesanLogout = '';

if (isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare(
        "SELECT * FROM users WHERE username = ?"
    );
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user &&
        hash('sha1', $password) === $user['password']) {
        session_regenerate_id(true);
        $_SESSION['login'] = true;
        header("Location: index.php");
        exit;
    } else {
        $error = teks(
            "Username atau password salah",
            "Incorrect username or password"
        );
    }
}

if (isset($_SESSION['pesan_logout'])) {
    $pesanLogout = teks(
        "Anda sudah berhasil keluar dari akun.",
        "You have successfully logged out."
    );

    unset($_SESSION['pesan_logout']);
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Keuangan</title>
</head>
<body>
    <form method="post">
        <h2><?= teks("Login Keuangan", "Finance Login") ?></h2>
		<?php if ($pesanLogout !== ''): ?>
			<p style="color: green;">
				<?= htmlspecialchars($pesanLogout, ENT_QUOTES, 'UTF-8') ?>
			</p>
		<?php endif; ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>

        <input name="username" placeholder="Username" required>
        <input type="password" name="password"
               placeholder="Password" required>

        <button name="login" type="submit">
            <?= teks("Masuk", "Login") ?>
        </button>
        <p>
            <a href="login.php?bahasa=id">Indonesia</a> |
            <a href="login.php?bahasa=en">English</a>
        </p>
    </form>
</body>
</html>
