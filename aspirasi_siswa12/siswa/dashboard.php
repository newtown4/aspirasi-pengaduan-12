<?php
session_start();

// Cek apakah siswa sudah login (menggunakan NIS)
if (!isset($_SESSION['siswa'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../koneksi.php';

$nis = $_SESSION['siswa'];
$nama_siswa = $_SESSION['nama_siswa'] ?? 'Siswa';

// ==========================================
// 1. PROSES HAPUS ASPIRASI
// ==========================================
if (isset($_GET['hapus'])) {
    $id_h = (int)$_GET['hapus'];
    
    // Cari nama file foto untuk dihapus dari folder
    $stmt = mysqli_prepare($conn, "SELECT foto FROM Input_Aspirasi WHERE id_pelaporan=? AND nis=?");
    mysqli_stmt_bind_param($stmt, "is", $id_h, $nis);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    
    if ($d = mysqli_fetch_assoc($res)) {
        if (file_exists("../uploads/".$d['foto'])) unlink("../uploads/".$d['foto']);
        
        // Hapus data dari database
        $stmt_del = mysqli_prepare($conn, "DELETE FROM Input_Aspirasi WHERE id_pelaporan=? AND nis=?");
        mysqli_stmt_bind_param($stmt_del, "is", $id_h, $nis);
        mysqli_stmt_execute($stmt_del);
        
        echo "<script>alert('Aspirasi berhasil dihapus!'); window.location='dashboard.php';</script>"; 
        exit();
    }
}

// ==========================================
// 2. PROSES KIRIM ASPIRASI
// ==========================================
if (isset($_POST['kirim'])) {
    $f = $_FILES['foto'];
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    
    // Validasi file (harus gambar)
    if ($f['error'] === 0 && in_array($ext, ['jpg','jpeg','png'])) {
        // Buat nama file unik (waktu + angka acak)
        $n_baru = time() . '_' . rand(100, 999) . '.' . $ext;
        
        if (move_uploaded_file($f['tmp_name'], "../uploads/".$n_baru)) {
            $stmt_in = mysqli_prepare($conn, "INSERT INTO Input_Aspirasi (nis, id_kategori, lokasi, pesan, foto) VALUES (?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt_in, "sssss", $nis, $_POST['id_kategori'], $_POST['lokasi'], $_POST['pesan'], $n_baru);
            mysqli_stmt_execute($stmt_in);
            
            echo "<script>alert('Aspirasi Anda berhasil dikirim!'); window.location='dashboard.php';</script>"; 
            exit();
        }
    } else {
        echo "<script>alert('Gagal! Pastikan file adalah gambar JPG/PNG.'); window.location='dashboard.php';</script>"; 
        exit();
    }
}

// ==========================================
// 3. PENGAMBILAN DATA (Kategori & Riwayat)
// ==========================================
$kategori = mysqli_query($conn, "SELECT * FROM Kategori");

$q_riwayat = "SELECT ia.*, k.ket_kategori, a.status, a.feedback 
              FROM Input_Aspirasi ia 
              JOIN Kategori k ON ia.id_kategori = k.id_kategori 
              LEFT JOIN Aspirasi a ON ia.id_pelaporan = a.id_pelaporan 
              WHERE ia.nis = ? ORDER BY ia.id_pelaporan DESC";
$stmt_r = mysqli_prepare($conn, $q_riwayat);
mysqli_stmt_bind_param($stmt_r, "s", $nis);
mysqli_stmt_execute($stmt_r);
$riwayat = mysqli_stmt_get_result($stmt_r);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa</title>
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>

    <div class="dashboard-wrapper">
        <header class="top-nav fade-in">
            <div>
                <h1>Halo, <?= htmlspecialchars($nama_siswa) ?></h1>
                <p class="subtitle">NIS: <?= htmlspecialchars($nis) ?></p>
            </div>
            <a href="../logout.php" class="btn btn-outline">Keluar</a>
        </header>

        <main>
            <div class="card fade-in" style="animation-delay: 0.1s;">
                <h2>Kirim Aspirasi Baru</h2>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Kategori</label>
                        <select name="id_kategori" class="form-control" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php while($k = mysqli_fetch_assoc($kategori)): ?>
                                <option value="<?= $k['id_kategori'] ?>"><?= htmlspecialchars($k['ket_kategori']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Lokasi Kejadian</label>
                        <input type="text" name="lokasi" class="form-control" placeholder="Contoh: Ruang Kelas 12 AKL" required>
                    </div>

                    <div class="form-group">
                        <label>Isi Pesan / Aspirasi</label>
                        <textarea name="pesan" class="form-control" placeholder="Jelaskan aspirasi atau laporan Anda secara detail..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label>Lampirkan Foto (Max 2MB, JPG/PNG)</label>
                        <input type="file" name="foto" class="form-control" accept=".jpg,.jpeg,.png" required>
                    </div>

                    <button type="submit" name="kirim" class="btn btn-primary">Kirim Aspirasi</button>
                </form>
            </div>

            <div class="card fade-in" style="animation-delay: 0.2s;">
                <h2>Riwayat & Balasan Admin</h2>
                <div class="table-responsive">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Kategori</th>
                                <th>Lokasi</th>
                                <th>Pesan</th>
                                <th>Foto</th>
                                <th>Status</th>
                                <th>Tanggapan Admin</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if (mysqli_num_rows($riwayat) == 0) {
                                echo "<tr><td colspan='8' align='center' style='padding: 2rem; color: #64748b;'>Belum ada riwayat aspirasi.</td></tr>";
                            }
                            while ($row = mysqli_fetch_assoc($riwayat)): 
                                $status = $row['status'] ?? 'Menunggu';
                                
                                // Menentukan class warna badge berdasarkan status
                                $badge_class = '';
                                if (strtolower($status) === 'menunggu') $badge_class = 'menunggu';
                                elseif (strtolower($status) === 'diproses' || strtolower($status) === 'proses') $badge_class = 'proses';
                                elseif (strtolower($status) === 'selesai') $badge_class = 'selesai';
                            ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                                <td><?= htmlspecialchars($row['ket_kategori']) ?></td>
                                <td><?= htmlspecialchars($row['lokasi']) ?></td>
                                <td><?= nl2br(htmlspecialchars($row['pesan'])) ?></td>
                                <td>
                                    <a href="../uploads/<?= htmlspecialchars($row['foto']) ?>" target="_blank">Lihat Foto</a>
                                </td>
                                <td>
                                    <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($status) ?></span>
                                </td>
                                <td>
                                    <?php 
                                    if (!empty($row['feedback'])) echo nl2br(htmlspecialchars($row['feedback']));
                                    else echo "<span style='color: #94a3b8; font-style: italic;'>Belum ada tanggapan</span>";
                                    ?>
                                </td>
                                <td>
                                    <?php if ($status === 'Menunggu'): ?>
                                        <a href="?hapus=<?= $row['id_pelaporan'] ?>" onclick="return confirm('Yakin ingin membatalkan dan menghapus aspirasi ini?');" class="btn btn-danger">
                                            Hapus
                                        </a>
                                    <?php else: ?>
                                        <span style="color: #94a3b8; font-size: 0.85rem;">Terkunci</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

</body>
</html>