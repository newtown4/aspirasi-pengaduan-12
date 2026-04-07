<?php
session_start();
// Cek apakah yang akses benar-benar admin
if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit();
}

include '../koneksi.php'; 

// Mengambil ID pelaporan dari URL (parameter ?id=...)
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$id_pelaporan = $_GET['id'];

// Mengambil detail data laporan berdasarkan ID
$query_laporan = "SELECT Input_Aspirasi.*, Siswa.nama, Kategori.ket_kategori 
                  FROM Input_Aspirasi 
                  JOIN Siswa ON Input_Aspirasi.nis = Siswa.nis
                  JOIN Kategori ON Input_Aspirasi.id_kategori = Kategori.id_kategori
                  WHERE id_pelaporan = '$id_pelaporan'";
$result_laporan = mysqli_query($conn, $query_laporan);
$data = mysqli_fetch_assoc($result_laporan);

// Mengecek apakah laporan ini sudah pernah ditanggapi sebelumnya di tabel 'Aspirasi'
$query_tanggapan = "SELECT * FROM Aspirasi WHERE id_pelaporan = '$id_pelaporan'";
$result_tanggapan = mysqli_query($conn, $query_tanggapan);
$data_tanggapan = mysqli_fetch_assoc($result_tanggapan);

// Logika ketika tombol 'Simpan Tanggapan' ditekan
if (isset($_POST['simpan'])) {
    $status = $_POST['status'];
    $feedback = mysqli_real_escape_string($conn, $_POST['feedback']);
    $id_kategori = $data['id_kategori']; // ID Kategori diambil dari data laporan aslinya

    if (mysqli_num_rows($result_tanggapan) > 0) {
        // Jika sudah ada tanggapan sebelumnya, kita UPDATE datanya
        $query_update = "UPDATE Aspirasi SET status='$status', feedback='$feedback' WHERE id_pelaporan='$id_pelaporan'";
        mysqli_query($conn, $query_update);
    } else {
        // Jika belum ada tanggapan sama sekali, kita INSERT data baru
        $query_insert = "INSERT INTO Aspirasi (id_pelaporan, status, id_kategori, feedback) 
                         VALUES ('$id_pelaporan', '$status', '$id_kategori', '$feedback')";
        mysqli_query($conn, $query_insert);
    }

    echo "<script>alert('Tanggapan dan status berhasil diperbarui!'); window.location='dashboard.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Proses Tanggapan</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .detail-container { display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 20px; }
        .info-teks { flex: 1; min-width: 300px; }
        .info-foto { flex: 1; min-width: 300px; }
        .info-foto img { max-width: 100%; border: 1px solid #ccc; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        textarea { width: 100%; padding: 10px; margin-top: 10px; }
        button { padding: 10px 20px; background-color: #28a745; color: white; border: none; cursor: pointer; border-radius: 4px; }
        button:hover { background-color: #218838; }
    </style>
</head>
<body>
    <h2>Beri Tanggapan Laporan</h2>
    <a href="dashboard.php">← Kembali ke Dashboard</a>
    <hr>

    <div class="detail-container">
        <div class="info-teks">
            <h3>Detail Laporan:</h3>
            <ul>
                <li><strong>Nama Siswa:</strong> <?php echo htmlspecialchars($data['nama']); ?></li>
                <li><strong>Kategori:</strong> <?php echo htmlspecialchars($data['ket_kategori']); ?></li>
                <li><strong>Lokasi:</strong> <?php echo htmlspecialchars($data['lokasi']); ?></li>
                <li><strong>Pesan:</strong><br>
                    <p style="background: #f9f9f9; padding: 10px; border-left: 4px solid #007bff;">
                        <?php echo nl2br(htmlspecialchars($data['pesan'])); ?>
                    </p>
                </li>
            </ul>
        </div>

        <div class="info-foto">
            <h3>Foto Bukti:</h3>
            <?php if (!empty($data['foto'])): ?>
                <img src="../uploads/<?php echo $data['foto']; ?>" alt="Foto Bukti">
            <?php else: ?>
                <p><i>Tidak ada foto bukti.</i></p>
            <?php endif; ?>
        </div>
    </div>

    <hr>

    <h3>Form Tanggapan:</h3>
    <form action="" method="POST">
        <label>Status Saat Ini:</label><br>
        <select name="status">
            <option value="Menunggu" <?php if(isset($data_tanggapan['status']) && $data_tanggapan['status'] == 'Menunggu') echo 'selected'; ?>>Menunggu</option>
            <option value="Proses" <?php if(isset($data_tanggapan['status']) && $data_tanggapan['status'] == 'Proses') echo 'selected'; ?>>Proses</option>
            <option value="Selesai" <?php if(isset($data_tanggapan['status']) && $data_tanggapan['status'] == 'Selesai') echo 'selected'; ?>>Selesai</option>
        </select><br><br>

        <label>Feedback / Balasan untuk Siswa:</label><br>
        <textarea name="feedback" rows="6" placeholder="Tuliskan tanggapan atau solusi untuk siswa..." required><?php echo isset($data_tanggapan['feedback']) ? htmlspecialchars($data_tanggapan['feedback']) : ''; ?></textarea><br><br>

        <button type="submit" name="simpan">Simpan Tanggapan & Perbarui Status</button>
    </form>
</body>
</html>