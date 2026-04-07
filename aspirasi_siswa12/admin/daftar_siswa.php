<?php
session_start();

// Cek apakah admin sudah login
if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../koneksi.php';

// ==========================================
// 1. PROSES HAPUS SISWA
// ==========================================
if (isset($_GET['hapus'])) {
    $nis_hapus = mysqli_real_escape_string($conn, trim($_GET['hapus']));
    
    // Cek apakah siswa ini punya riwayat laporan
    $cek_laporan = mysqli_query($conn, "SELECT id_pelaporan FROM Input_Aspirasi WHERE nis = '$nis_hapus'");
    
    if (mysqli_num_rows($cek_laporan) > 0) {
        echo "<script>alert('GAGAL: Siswa tidak dapat dihapus karena masih memiliki riwayat laporan!'); window.location='daftar_siswa.php';</script>";
        exit();
    } 

    // Eksekusi Hapus
    if (mysqli_query($conn, "DELETE FROM siswa WHERE nis = '$nis_hapus'")) {
        echo "<script>alert('Berhasil! Akun siswa telah dihapus.'); window.location='daftar_siswa.php';</script>";
    } else {
        echo "<script>alert('Gagal menghapus data dari database.'); window.location='daftar_siswa.php';</script>";
    }
    exit();
}

// ==========================================
// 2. PROSES TAMBAH SISWA
// ==========================================
if (isset($_POST['tambah_siswa'])) {
    $nis  = mysqli_real_escape_string($conn, trim($_POST['nis']));
    $nama = mysqli_real_escape_string($conn, trim($_POST['nama']));

    if (!$nis || !$nama) {
        echo "<script>alert('NIS dan Nama wajib diisi!');</script>";
    } else {
        // Cek Duplikat NIS
        $cek = mysqli_query($conn, "SELECT nis FROM siswa WHERE nis = '$nis'");
        if (mysqli_num_rows($cek) > 0) {
            echo "<script>alert('Gagal! NIS sudah terdaftar.');</script>";
        } else {
            // Insert Data Baru
            if (mysqli_query($conn, "INSERT INTO siswa (nis, nama) VALUES ('$nis', '$nama')")) {
                echo "<script>alert('Berhasil menambah siswa baru!'); window.location='daftar_siswa.php';</script>";
            } else {
                echo "<script>alert('Gagal menyimpan ke database.');</script>";
            }
        }
    }
}

// ==========================================
// 3. PENCARIAN & PAGINASI
// ==========================================
$search = $_GET['search'] ?? '';
$where = "1=1";

if ($search !== '') {
    $s = mysqli_real_escape_string($conn, $search);
    $where .= " AND (nis LIKE '%$s%' OR nama LIKE '%$s%')";
}

$limit = 15; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Hitung total data
$q_count = "SELECT COUNT(nis) as total FROM siswa WHERE $where";
$total_data = mysqli_fetch_assoc(mysqli_query($conn, $q_count))['total'];
$total_pages = ceil($total_data / $limit) ?: 1;

// Ambil data siswa
$q_data = "SELECT * FROM siswa WHERE $where ORDER BY nama ASC LIMIT $limit OFFSET $offset";
$daftar_siswa = mysqli_query($conn, $q_data);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Siswa - Admin AspirasiKu</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>

    <nav class="admin-nav fade-in">
        <div class="nav-brand">
            <h1>AspirasiKu <span>Admin</span></h1>
            <p>Halo, <?= htmlspecialchars($_SESSION['admin']) ?></p>
        </div>
        <div class="nav-links">
            <a href="dashboard.php" class="btn btn-outline">Dashboard</a>
            <a href="daftar_siswa.php" class="btn btn-primary">Kelola Siswa</a>
            <a href="../logout.php" class="btn btn-secondary">Keluar</a>
        </div>
    </nav>

    <div class="container">
        
        <div class="card fade-in" style="animation-delay: 0.1s;">
            <h2 class="card-title">Daftarkan Siswa Baru</h2>
            <form method="POST" class="filter-form">
                <div class="form-group">
                    <label>Nomor Induk Siswa (NIS)</label>
                    <input type="text" name="nis" class="form-control" required autocomplete="off" placeholder="Masukkan NIS unik">
                </div>

                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" class="form-control" required autocomplete="off" placeholder="Masukkan nama siswa">
                </div>

                <div class="filter-actions" style="margin-bottom: 0.2rem;">
                    <button type="submit" name="tambah_siswa" class="btn btn-primary">Simpan Data Siswa</button>
                </div>
            </form>
        </div>

        <div class="card fade-in" style="animation-delay: 0.2s;">
            <h2 class="card-title">Daftar Siswa Terdaftar</h2>
            
            <form method="GET" class="filter-form" style="margin-bottom: 1.5rem;">
                <div class="form-group" style="max-width: 400px;">
                    <label>Cari NIS atau Nama:</label>
                    <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" autocomplete="off" placeholder="Ketik NIS atau nama...">
                </div>
                <div class="filter-actions" style="margin-bottom: 0.2rem;">
                    <button type="submit" class="btn btn-primary">Cari</button>
                    <a href="daftar_siswa.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th width="10%">No</th>
                            <th width="20%">NIS</th>
                            <th width="50%">Nama Lengkap</th>
                            <th width="20%" align="center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if (mysqli_num_rows($daftar_siswa) == 0) {
                            echo "<tr><td colspan='4' align='center' style='padding: 3rem; color: var(--text-muted);'>Data siswa tidak ditemukan.</td></tr>";
                        }
                        $no = $offset + 1; 
                        while ($s = mysqli_fetch_assoc($daftar_siswa)): 
                        ?>
                        <tr>
                            <td align="center"><?= $no++ ?></td>
                            <td><strong><?= htmlspecialchars($s['nis']) ?></strong></td>
                            <td><?= htmlspecialchars($s['nama']) ?></td>
                            <td align="center">
                                <a href="?hapus=<?= urlencode($s['nis']) ?>" onclick="return confirm('Yakin ingin menghapus akun siswa ini? Semua data terkait (kecuali jika dicegah oleh sistem) akan hilang.')" class="btn btn-danger btn-action">
                                    Hapus Siswa
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="pagination">
                <span>Menampilkan halaman <?= $page ?> dari <?= $total_pages ?></span>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" 
                       class="page-link <?= $i == $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>

        </div>

    </div>

</body>
</html>