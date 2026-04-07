<?php
session_start();
// Cek apakah yang akses benar-benar admin
if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit();
}

include '../koneksi.php'; 

// ====================================================
// Proses Hapus Laporan oleh Admin
// ====================================================
if (isset($_GET['hapus'])) {
    $id_hapus = mysqli_real_escape_string($conn, $_GET['hapus']);
    
    // 1. Ambil nama file foto untuk dihapus dari folder server
    $cek_foto = mysqli_query($conn, "SELECT foto FROM Input_Aspirasi WHERE id_pelaporan = '$id_hapus'");
    if ($row_foto = mysqli_fetch_assoc($cek_foto)) {
        $file_foto = "../uploads/" . $row_foto['foto'];
        if (!empty($row_foto['foto']) && file_exists($file_foto)) {
            unlink($file_foto); // Hapus foto fisik
        }
    }
    
    // 2. Hapus tanggapan terkait di tabel Aspirasi (mencegah error constraint)
    mysqli_query($conn, "DELETE FROM Aspirasi WHERE id_pelaporan = '$id_hapus'");
    
    // 3. Hapus data laporan utama dari tabel Input_Aspirasi
    if (mysqli_query($conn, "DELETE FROM Input_Aspirasi WHERE id_pelaporan = '$id_hapus'")) {
        echo "<script>alert('Laporan beserta tanggapannya berhasil dihapus!'); window.location='dashboard.php';</script>";
    } else {
        echo "<script>alert('Gagal menghapus laporan: " . mysqli_error($conn) . "'); window.location='dashboard.php';</script>";
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f4f4f4; }
        .btn-aksi { text-decoration: none; font-weight: bold; padding: 0 5px; }
        .btn-tanggapan { color: blue; }
        .btn-hapus { color: red; }
        .btn-aksi:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h2>Selamat Datang, Admin (<?= htmlspecialchars($_SESSION['admin']); ?>)</h2>
    <a href="../logout.php">Logout</a> | 
    <a href="tambah_siswa.php">Tambah Data Siswa</a> 
    <hr>

    <h3>Daftar Laporan Aspirasi Masuk</h3>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th> 
                <th>Nama Siswa</th>
                <th>Kategori</th>
                <th>Lokasi</th>
                <th>Pesan</th>
                <th>Foto</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Query JOIN dengan menggunakan alias huruf (i, s, k) agar singkat (DRY)
            $query = "SELECT i.*, s.nama, k.ket_kategori 
                      FROM Input_Aspirasi i 
                      JOIN Siswa s ON i.nis = s.nis
                      JOIN Kategori k ON i.id_kategori = k.id_kategori
                      ORDER BY i.id_pelaporan DESC";
            
            $result = mysqli_query($conn, $query);

            if (mysqli_num_rows($result) > 0):
                $no = 1;
                while ($row = mysqli_fetch_assoc($result)):
            ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= date('d M Y, H:i', strtotime($row['tanggal'])) ?></td> 
                        <td><?= htmlspecialchars($row['nama']) ?></td>
                        <td><?= htmlspecialchars($row['ket_kategori']) ?></td>
                        <td><?= htmlspecialchars($row['lokasi']) ?></td>
                        <td><?= nl2br(htmlspecialchars($row['pesan'])) ?></td> 
                        <td><img src="../uploads/<?= htmlspecialchars($row['foto']) ?>" width="100" alt="Bukti Laporan"></td>
                        <td style="text-align: center;">
                            <a href="proses_tanggapan.php?id=<?= $row['id_pelaporan'] ?>" class="btn-aksi btn-tanggapan">Tanggapi</a> 
                            | 
                            <a href="?hapus=<?= $row['id_pelaporan'] ?>" class="btn-aksi btn-hapus" 
                               onclick="return confirm('Yakin ingin menghapus laporan ini beserta semua tanggapannya?');">Hapus</a>
                        </td>
                    </tr>
            <?php 
                endwhile; 
            else: 
            ?>
                <tr>
                    <td colspan="8" style="text-align: center;">Belum ada laporan aspirasi masuk.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>