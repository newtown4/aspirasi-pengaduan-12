<?php
session_start();
// Cek apakah yang akses benar-benar admin
if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit();
}

include '../koneksi.php'; 
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Admin</title>
</head>
<body>
    <h2>Selamat Datang, Admin (<?php echo $_SESSION['admin']; ?>)</h2>
    <a href="../logout.php">Logout</a>
    <hr>

    <h3>Daftar Laporan Aspirasi Masuk</h3>
    <table border="1" cellpadding="10" cellspacing="0">
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th> <th>Nama Siswa</th>
                <th>Kategori</th>
                <th>Lokasi</th>
                <th>Pesan</th>
                <th>Foto</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Query JOIN untuk menggabungkan data dari 3 tabel: Input_Aspirasi, Siswa, dan Kategori
            $query = "SELECT Input_Aspirasi.*, Siswa.nama, Kategori.ket_kategori 
                      FROM Input_Aspirasi 
                      JOIN Siswa ON Input_Aspirasi.nis = Siswa.nis
                      JOIN Kategori ON Input_Aspirasi.id_kategori = Kategori.id_kategori
                      ORDER BY id_pelaporan DESC";
            
            $result = mysqli_query($conn, $query);
            $no = 1;

            while ($row = mysqli_fetch_assoc($result)) {
                // Memformat tanggal agar lebih mudah dibaca (Contoh: 02 Apr 2026, 09:15)
                $tanggal_format = date('d M Y, H:i', strtotime($row['tanggal']));

                echo "<tr>";
                echo "<td>".$no++."</td>";
                echo "<td>".$tanggal_format."</td>"; // Menampilkan tanggal
                echo "<td>".$row['nama']."</td>";
                echo "<td>".$row['ket_kategori']."</td>";
                echo "<td>".$row['lokasi']."</td>";
                
                // Menampilkan pesan dengan nl2br() agar baris baru/enter dari input textarea tetap terbaca rapi
                echo "<td>".nl2br(htmlspecialchars($row['pesan']))."</td>"; 
                
                echo "<td><img src='../uploads/".$row['foto']."' width='100' alt='Bukti Laporan'></td>";
                // Tombol aksi untuk memproses laporan ke tabel 'Aspirasi'
                echo "<td>
                        <a href='proses_tanggapan.php?id=".$row['id_pelaporan']."'>Beri Tanggapan</a>
                      </td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
</body>
</html>