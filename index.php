<?php
require_once __DIR__ . '/koneksi.php';

// Lindungi seluruh halaman: pengguna harus login terlebih dahulu.
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header('Location: login.php');
    exit;
}

// Proses tambah transaksi
if (isset($_POST['tambah'])) {
    $jenis = $_POST['jenis'] ?? '';
    $keterangan = trim($_POST['keterangan'] ?? '');
    $jumlah = filter_var($_POST['jumlah'] ?? null, FILTER_VALIDATE_FLOAT);
    $tgl = $_POST['tgl'] ?? '';

    $tanggalValid = false;
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $tgl, $m)) {
        $tanggalValid = checkdate((int)$m[2], (int)$m[3], (int)$m[1]);
    }

    if (
        in_array($jenis, ['Pemasukan', 'Pengeluaran'], true) &&
        $keterangan !== '' &&
        mb_strlen($keterangan, 'UTF-8') <= 100 &&
        $jumlah !== false && $jumlah > 0 &&
        $tanggalValid
    ) {
        $stmt = $conn->prepare(
            "INSERT INTO transaksi (jenis, keterangan, jumlah, tgl) VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param('ssds', $jenis, $keterangan, $jumlah, $tgl);
        $stmt->execute();
        $stmt->close();
    } else {
        $_SESSION['pesan'] = 'Data tidak valid. Periksa kembali isian transaksi.';
    }

    header('Location: index.php');
    exit;
}

// Proses edit transaksi
if (isset($_POST['edit'])) {
    $id = (int)($_POST['id'] ?? 0);
    $jenis = $_POST['jenis'] ?? '';
    $keterangan = trim($_POST['keterangan'] ?? '');
    $jumlah = filter_var($_POST['jumlah'] ?? null, FILTER_VALIDATE_FLOAT);
    $tgl = $_POST['tgl'] ?? '';

    $tanggalValid = false;
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $tgl, $m)) {
        $tanggalValid = checkdate((int)$m[2], (int)$m[3], (int)$m[1]);
    }

    if (
        $id > 0 &&
        in_array($jenis, ['Pemasukan', 'Pengeluaran'], true) &&
        $keterangan !== '' &&
        mb_strlen($keterangan, 'UTF-8') <= 100 &&
        $jumlah !== false && $jumlah > 0 &&
        $tanggalValid
    ) {
        $stmt = $conn->prepare(
            "UPDATE transaksi SET jenis=?, keterangan=?, jumlah=?, tgl=? WHERE id=?"
        );
        $stmt->bind_param('ssdsi', $jenis, $keterangan, $jumlah, $tgl, $id);
        $stmt->execute();
        $stmt->close();
    } else {
        $_SESSION['pesan'] = 'Data perubahan tidak valid. Periksa kembali isian.';
    }

    header('Location: index.php');
    exit;
}


// Proses hapus transaksi
if (isset($_POST['hapus'])) {
    $id = (int)($_POST['id'] ?? 0);

    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM transaksi WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $_SESSION['pesan'] = teks(
                "Data transaksi berhasil dihapus.",
                "Transaction data has been successfully deleted."
            );
        } else {
            $_SESSION['pesan'] = teks(
                "Data transaksi tidak ditemukan.",
                "Transaction data was not found."
            );
        }

        $stmt->close();
    }

    header('Location: index.php');
    exit;
}

// Mengambil data untuk form edit
$editData = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    if ($id > 0) {
        $stmt = $conn->prepare("SELECT * FROM transaksi WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $editData = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

// Menghitung pemasukan dan pengeluaran
$resultPemasukan = $conn->query(
    "SELECT COALESCE(SUM(jumlah), 0) AS total FROM transaksi WHERE jenis='Pemasukan'"
);
$pemasukan = $resultPemasukan->fetch_assoc()['total'];

$resultPengeluaran = $conn->query(
    "SELECT COALESCE(SUM(jumlah), 0) AS total FROM transaksi WHERE jenis='Pengeluaran'"
);
$pengeluaran = $resultPengeluaran->fetch_assoc()['total'];
$saldo = (float)$pemasukan - (float)$pengeluaran;

// Membaca seluruh transaksi
$data = $conn->query("SELECT * FROM transaksi ORDER BY tgl DESC, id DESC");

function rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}
?>
<html lang="<?= $bahasa === 'en' ? 'en' : 'id' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>KeuanganKu</title>
</head>
<body>

<header>
    <h2><?= teks("Pengelolaan Uang", "Money Manager") ?></h2>

    <nav>
        <a href="index.php?bahasa=id">Indonesia</a>
        <a href="index.php?bahasa=en">English</a>
        <a href="logout.php"><?= teks("Keluar", "Logout") ?></a>
    </nav>
</header>

<main>
    <?php if (!empty($_SESSION['pesan'])): ?>
        <p style="color:#b42318;background:#fee4e2;padding:10px;border-radius:6px;">
            <?= htmlspecialchars($_SESSION['pesan'], ENT_QUOTES, 'UTF-8') ?>
        </p>
        <?php unset($_SESSION['pesan']); ?>
    <?php endif; ?>
    <section class="cards">
        <div class="card">
            <p><?= teks("Total Pemasukan", "Total Income") ?></p>
            <h2><?= rupiah($pemasukan) ?></h2>
        </div>

        <div class="card">
            <p><?= teks("Total Pengeluaran", "Total Expenses") ?></p>
            <h2><?= rupiah($pengeluaran) ?></h2>
        </div>

        <div class="card">
            <p><?= teks("Saldo Saat Ini", "Current Balance") ?></p>
            <h2><?= rupiah($saldo) ?></h2>
        </div>
    </section>

    <section class="panel">
        <h3>
            <?= $editData
                ? teks("Edit Transaksi", "Edit Transaction")
                : teks("Tambah Transaksi", "Add Transaction") ?>
        </h3>

        <form method="post">
            <?php if ($editData): ?>
                <input type="hidden" name="id"
                       value="<?= (int)$editData['id'] ?>">
            <?php endif; ?>

            <div class="form-grid">
                <select name="jenis" required>
                    <option value="Pemasukan"
                        <?= ($editData['jenis'] ?? '') === 'Pemasukan'
                            ? 'selected' : '' ?>>
                        <?= teks("Pemasukan", "Income") ?>
                    </option>

                    <option value="Pengeluaran"
                        <?= ($editData['jenis'] ?? '') === 'Pengeluaran'
                            ? 'selected' : '' ?>>
                        <?= teks("Pengeluaran", "Expense") ?>
                    </option>
                </select>

                <input type="text" name="keterangan"
                       maxlength="100"
                       placeholder="<?= teks("Keterangan", "Description") ?>"
                       value="<?= htmlspecialchars($editData['keterangan'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       required>

                <input type="number" name="jumlah" min="0.01" step="0.01"
                       placeholder="<?= teks("Jumlah uang", "Amount") ?>"
                       value="<?= htmlspecialchars((string)($editData['jumlah'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                       required>

                <input type="date" name="tgl"
                       value="<?= htmlspecialchars($editData['tgl'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>"
                       required>
            </div>

            <button class="save" type="submit"
                    name="<?= $editData ? 'edit' : 'tambah' ?>">
                <?= $editData
                    ? teks("Simpan Perubahan", "Save Changes")
                    : teks("Tambah Data", "Add Transaction") ?>
            </button>

            <?php if ($editData): ?>
                <a href="index.php">
                    <?= teks("Batal", "Cancel") ?>
                </a>
            <?php endif; ?>
        </form>
    </section>

    <section class="panel">
        <h3><?= teks("Daftar Transaksi", "Transaction List") ?></h3>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th><?= teks("Tanggal", "Date") ?></th>
                        <th><?= teks("Jenis", "Type") ?></th>
                        <th><?= teks("Keterangan", "Description") ?></th>
                        <th><?= teks("Jumlah", "Amount") ?></th>
                        <th><?= teks("Aksi", "Action") ?></th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($data->num_rows === 0): ?>
                    <tr>
                        <td colspan="5">
                            <?= teks("Belum ada transaksi", "No transactions yet") ?>
                        </td>
                    </tr>
                <?php endif; ?>

                <?php while ($row = $data->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['tgl'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= teks(
                            $row['jenis'],
                            $row['jenis'] === 'Pemasukan' ? 'Income' : 'Expense'
                        ) ?></td>
                        <td><?= htmlspecialchars($row['keterangan'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= rupiah($row['jumlah']) ?></td>

                        <td>
                            <div class="actions">
                                <a href="index.php?edit=<?= (int)$row['id'] ?>">
                                    <?= teks("Edit", "Edit") ?>
                                </a>

                                <form method="post"
                                      onsubmit="return confirm('Hapus transaksi ini?')">
                                    <input type="hidden" name="id"
                                           value="<?= (int)$row['id'] ?>">
                                    <button class="delete" type="submit"
                                            name="hapus">
                                        <?= teks("Hapus", "Delete") ?>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
