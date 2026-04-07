<?php
session_start();
// Cek apakah yang akses benar-benar siswa
if (!isset($_SESSION['siswa'])) {
    header("Location: ../index.php");
    exit();
}

// Panggil file koneksi
include '../koneksi.php'; 
$nis = $_SESSION['siswa']; // Ambil NIS dari session

// Jika tombol kirim ditekan
if (isset($_POST['kirim'])) {
    $id_kategori = $_POST['id_kategori'];
    $lokasi = mysqli_real_escape_string($conn, $_POST['lokasi']);
    $pesan = mysqli_real_escape_string($conn, $_POST['pesan']);
    
    // Urusan Upload Foto
    $foto = $_FILES['foto']['name'];
    $tmp_name = $_FILES['foto']['tmp_name'];
    $folder = "../uploads/" . $foto; 

    if (move_uploaded_file($tmp_name, $folder)) {
        // Simpan ke database Input_Aspirasi
        $query = "INSERT INTO Input_Aspirasi (nis, id_kategori, lokasi, pesan, foto) 
                  VALUES ('$nis', '$id_kategori', '$lokasi', '$pesan', '$foto')";
        
        if (mysqli_query($conn, $query)) {
            echo "<script>alert('Aspirasi berhasil dikirim!'); window.location='dashboard.php';</script>";
        } else {
            echo "<script>alert('Gagal mengirim aspirasi.');</script>";
        }
    }
}
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
    </style>
</head>
<body>
    <h2>Selamat Datang, <?php echo $_SESSION['nama_siswa']; ?> (NIS: <?php echo $nis; ?>)</h2>
    <a href="../logout.php">Logout</a> 
    <hr>

    <h3>Kirim Aspirasi / Pengaduan Baru</h3>
    <form action="" method="POST" enctype="multipart/form-data">
        <label>Kategori:</label><br>
        <select name="id_kategori" required>
            <option value="">-- Pilih Kategori --</option>
            <?php
            $kategori_query = mysqli_query($conn, "SELECT * FROM Kategori");
            while ($k = mysqli_fetch_assoc($kategori_query)) {
                echo "<option value='".$k['id_kategori']."'>".$k['ket_kategori']."</option>";
            }
            ?>
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
                <th>No</th>
                <th>Kategori</th>
                <th>Lokasi</th>
                <th>Pesan Anda</th>
                <th>Foto</th>
                <th>Status</th>
                <th>Balasan Admin</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Mengambil laporan KHUSUS milik siswa yang sedang login
            // LEFT JOIN digunakan agar laporan yang belum dibalas admin (belum ada di tabel Aspirasi) tetap muncul
            $query_riwayat = "SELECT ia.*, k.ket_kategori, a.status, a.feedback 
                              FROM Input_Aspirasi ia 
                              JOIN Kategori k ON ia.id_kategori = k.id_kategori 
                              LEFT JOIN Aspirasi a ON ia.id_pelaporan = a.id_pelaporan 
                              WHERE ia.nis = '$nis' 
                              ORDER BY ia.id_pelaporan DESC";
            
            $result_riwayat = mysqli_query($conn, $query_riwayat);
            $no = 1;

            if (mysqli_num_rows($result_riwayat) > 0) {
                while ($row = mysqli_fetch_assoc($result_riwayat)) {
                    // Cek status, jika kosong berarti admin belum merespon sama sekali
                    $status = !empty($row['status']) ? $row['status'] : 'Menunggu';
                    $feedback = !empty($row['feedback']) ? $row['feedback'] : '<i>Belum ada balasan</i>';
                    
                    // Mewarnai teks status
                    $class_status = '';
                    if ($status == 'Menunggu') $class_status = 'status-menunggu';
                    else if ($status == 'Proses') $class_status = 'status-proses';
                    else if ($status == 'Selesai') $class_status = 'status-selesai';

                    echo "<tr>";
                    echo "<td>".$no++."</td>";
                    echo "<td>".$row['ket_kategori']."</td>";
                    echo "<td>".$row['lokasi']."</td>";
                    echo "<td>".$row['pesan']."</td>";
                    echo "<td><img src='../uploads/".$row['foto']."' width='80'></td>";
                    echo "<td class='$class_status'>".$status."</td>";
                    echo "<td>".$feedback."</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='7' style='text-align:center;'>Anda belum pernah mengirimkan pengaduan.</td></tr>";
            }
            ?>
        </tbody>
    </table>

</body>
</html>