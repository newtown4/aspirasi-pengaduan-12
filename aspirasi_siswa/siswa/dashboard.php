<?php
session_start();
if (!isset($_SESSION['siswa'])) {
    header("Location: ../index.php");
    exit();
}

include '../koneksi.php'; 
$nis = $_SESSION['siswa']; 

// Fungsi Helper untuk Alert & Redirect (DRY)
function notif($pesan) {
    echo "<script>alert('$pesan'); window.location='dashboard.php';</script>";
    exit();
}

// ====================================================
// Proses Hapus Laporan
// ====================================================
if (isset($_GET['hapus'])) {
    $id = mysqli_real_escape_string($conn, $_GET['hapus']);
    
    $cek = mysqli_query($conn, "SELECT ia.foto, IFNULL(a.status, 'Menunggu') as status 
                                FROM Input_Aspirasi ia 
                                LEFT JOIN Aspirasi a ON ia.id_pelaporan = a.id_pelaporan 
                                WHERE ia.id_pelaporan = '$id' AND ia.nis = '$nis'");
    $row = mysqli_fetch_assoc($cek);
    
    if (!$row) notif('Data laporan tidak ditemukan atau bukan milik Anda.');
    if ($row['status'] !== 'Menunggu') notif('Laporan tidak dapat dihapus karena sudah diproses/selesai!');

    // Hapus file foto
    $file = "../uploads/" . $row['foto'];
    if (!empty($row['foto']) && file_exists($file)) unlink($file);
        
    // Hapus data
    if (mysqli_query($conn, "DELETE FROM Input_Aspirasi WHERE id_pelaporan = '$id'")) {
        notif('Laporan berhasil dihapus!');
    } else {
        notif('Gagal menghapus laporan: ' . mysqli_error($conn));
    }
}

// ====================================================
// Proses Kirim Aspirasi
// ====================================================
if (isset($_POST['kirim'])) {
    $id_kat = $_POST['id_kategori'];
    $lokasi = mysqli_real_escape_string($conn, $_POST['lokasi']);
    $pesan  = mysqli_real_escape_string($conn, $_POST['pesan']);
    
    $foto   = $_FILES['foto']['name'];
    $tmp    = $_FILES['foto']['tmp_name'];

    if (move_uploaded_file($tmp, "../uploads/" . $foto)) {
        $query = "INSERT INTO Input_Aspirasi (nis, id_kategori, lokasi, pesan, foto) 
                  VALUES ('$nis', '$id_kat', '$lokasi', '$pesan', '$foto')";
        mysqli_query($conn, $query) ? notif('Aspirasi berhasil dikirim!') : notif('Gagal mengirim aspirasi.');
    } else {
        notif('Gagal mengupload foto.');
    }
}

// Array Mapping untuk warna status
$status_map = ['Menunggu' => 'status-menunggu', 'Proses' => 'status-proses', 'Selesai' => 'status-selesai'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Siswa</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f4f4f4; }
        .status-menunggu { color: orange; font-weight: bold; }
        .status-proses { color: blue; font-weight: bold; }
        .status-selesai { color: green; font-weight: bold; }
        .btn-hapus { color: red; text-decoration: none; font-weight: bold; }
        .btn-hapus:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h2>Selamat Datang, <?= htmlspecialchars($_SESSION['nama_siswa'] ?? 'Siswa'); ?> (NIS: <?= htmlspecialchars($nis); ?>)</h2>
    <a href="../logout.php">Logout</a> 
    <hr>

    <h3>Kirim Aspirasi / Pengaduan Baru</h3>
    <form action="" method="POST" enctype="multipart/form-data">
        <label>Kategori:</label><br>
        <select name="id_kategori" required>
            <option value="">-- Pilih Kategori --</option>
            <?php
            $kat_query = mysqli_query($conn, "SELECT * FROM Kategori");
            while ($k = mysqli_fetch_assoc($kat_query)): 
            ?>
                <option value="<?= $k['id_kategori'] ?>"><?= htmlspecialchars($k['ket_kategori']) ?></option>
            <?php endwhile; ?>
        </select><br><br>

        <label>Lokasi Kejadian:</label><br>
        <input type="text" name="lokasi" required><br><br>

        <label>Pesan/Laporan:</label><br>
        <textarea name="pesan" rows="4" cols="50" required></textarea><br><br>

        <label>Bukti Foto:</label><br>
        <input type="file" name="foto" accept="image/*" required><br><br>

        <button type="submit" name="kirim">Kirim Laporan</button>
    </form>

    <hr>

    <h3>Riwayat Pengaduan Anda</h3>
    <table>
        <thead>
            <tr>
                <th>No</th><th>Kategori</th><th>Lokasi</th><th>Pesan Anda</th>
                <th>Foto</th><th>Status</th><th>Balasan Admin</th><th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $query_riwayat = "SELECT ia.*, k.ket_kategori, COALESCE(a.status, 'Menunggu') as status, a.feedback 
                              FROM Input_Aspirasi ia 
                              JOIN Kategori k ON ia.id_kategori = k.id_kategori 
                              LEFT JOIN Aspirasi a ON ia.id_pelaporan = a.id_pelaporan 
                              WHERE ia.nis = '$nis' ORDER BY ia.id_pelaporan DESC";
            $result = mysqli_query($conn, $query_riwayat);

            if (mysqli_num_rows($result) > 0):
                $no = 1;
                while ($row = mysqli_fetch_assoc($result)):
                    $status = $row['status'];
                    $feedback = $row['feedback'] ? htmlspecialchars($row['feedback']) : '<i>Belum ada balasan</i>';
            ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['ket_kategori']) ?></td>
                        <td><?= htmlspecialchars($row['lokasi']) ?></td>
                        <td><?= htmlspecialchars($row['pesan']) ?></td>
                        <td><img src="../uploads/<?= htmlspecialchars($row['foto']) ?>" width="80" alt="Bukti"></td>
                        <td class="<?= $status_map[$status] ?? '' ?>"><?= $status ?></td>
                        <td><?= $feedback ?></td>
                        <td style="text-align: center;">
                            <?php if ($status === 'Menunggu'): ?>
                                <a href="?hapus=<?= $row['id_pelaporan'] ?>" class="btn-hapus"
                                   onclick="return confirm('Yakin ingin menghapus laporan ini?');">Hapus</a>
                            <?php else: ?>
                                <span style="color: gray; font-size: 12px;">Tidak dapat dihapus</span>
                            <?php endif; ?>
                        </td>
                    </tr>
            <?php 
                endwhile; 
            else: 
            ?>
                <tr><td colspan="8" style="text-align:center;">Anda belum pernah mengirimkan pengaduan.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>