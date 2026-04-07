<?php
session_start();
// Pastikan hanya admin yang bisa mengakses halaman ini
if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit();
}

include '../koneksi.php';

// ====================================================
// PROSES HAPUS DATA SISWA
// ====================================================
if (isset($_GET['hapus'])) {
    $nis_hapus = mysqli_real_escape_string($conn, $_GET['hapus']);
    
    $query_hapus = "DELETE FROM siswa WHERE nis = '$nis_hapus'";
    
    if (mysqli_query($conn, $query_hapus)) {
        echo "<script>
                alert('Data siswa berhasil dihapus!'); 
                window.location='tambah_siswa.php';
              </script>";
        exit();
    } else {
        echo "<script>
                alert('Gagal menghapus data: " . mysqli_error($conn) . "');
                window.location='tambah_siswa.php';
              </script>";
    }
}

// ====================================================
// PROSES TAMBAH DATA SISWA
// ====================================================
// Cek apakah tombol simpan ditekan
if (isset($_POST['simpan'])) {
    $nis = mysqli_real_escape_string($conn, $_POST['nis']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);

    // Validasi apakah NIS sudah pernah didaftarkan sebelumnya
    $cek_nis = mysqli_query($conn, "SELECT * FROM siswa WHERE nis = '$nis'");
    
    if (mysqli_num_rows($cek_nis) > 0) {
        echo "<script>alert('Gagal! NIS $nis sudah terdaftar di sistem.');</script>";
    } else {
        // Query untuk memasukkan data siswa baru
        $query = "INSERT INTO siswa (nis, nama) VALUES ('$nis', '$nama')";
        
        if (mysqli_query($conn, $query)) {
            // Setelah berhasil, arahkan kembali ke halaman ini agar tabel ter-refresh
            echo "<script>
                    alert('Data siswa berhasil ditambahkan!'); 
                    window.location='tambah_siswa.php';
                  </script>";
            exit();
        } else {
            echo "<script>alert('Gagal menyimpan data: " . mysqli_error($conn) . "');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Kelola Data Siswa</title>
</head>
<body>
    <h2>Kelola Data Siswa</h2>
    <a href="dashboard.php">Kembali ke Dashboard</a>
    <hr>

    <h3>Tambah Siswa Baru</h3>
    <form action="" method="POST">
        <div style="margin-bottom: 10px;">
            <label>NIS (Nomor Induk Siswa):</label><br>
            <input type="text" name="nis" required placeholder="Masukkan NIS">
        </div>

        <div style="margin-bottom: 10px;">
            <label>Nama Lengkap Siswa:</label><br>
            <input type="text" name="nama" required placeholder="Masukkan Nama Lengkap">
        </div>

        <button type="submit" name="simpan">Simpan Data Siswa</button>
    </form>

    <hr>

    <h3>Daftar Siswa Terdaftar</h3>
    <table border="1" cellpadding="8" cellspacing="0" style="width: 100%; max-width: 700px;">
        <thead>
            <tr style="background-color: #f2f2f2;">
                <th style="width: 5%;">No</th>
                <th style="width: 25%;">NIS</th>
                <th style="width: 50%;">Nama Lengkap</th>
                <th style="width: 20%;">Aksi</th> </tr>
        </thead>
        <tbody>
            <?php
            // Query untuk mengambil semua data siswa, diurutkan berdasarkan NIS
            $query_tampil = "SELECT * FROM siswa ORDER BY nis ASC";
            $result_tampil = mysqli_query($conn, $query_tampil);
            
            // Cek apakah ada data siswa
            if (mysqli_num_rows($result_tampil) > 0) {
                $no = 1;
                while ($row = mysqli_fetch_assoc($result_tampil)) {
                    echo "<tr>";
                    echo "<td style='text-align: center;'>" . $no++ . "</td>";
                    echo "<td style='text-align: center;'>" . htmlspecialchars($row['nis']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['nama']) . "</td>";
                    
                    // Tambahan Link Hapus dengan Konfirmasi JavaScript
                    echo "<td style='text-align: center;'>
                            <a href='?hapus=" . urlencode($row['nis']) . "' 
                               onclick=\"return confirm('Apakah Anda yakin ingin menghapus siswa dengan NIS " . htmlspecialchars($row['nis']) . "?');\"
                               style='color: red;'>Hapus</a>
                          </td>";
                    echo "</tr>";
                }
            } else {
                // Jika tabel siswa masih kosong
                echo "<tr><td colspan='4' style='text-align: center;'>Belum ada data siswa.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</body>
</html>