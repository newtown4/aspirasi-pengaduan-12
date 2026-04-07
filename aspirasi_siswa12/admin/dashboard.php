<?php
session_start();

// Cek apakah admin sudah login
if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../koneksi.php';

// ==========================================
// 1. PROSES HAPUS LAPORAN
// ==========================================
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus']; 
    
    // Cari file foto untuk dihapus
    $res = mysqli_query($conn, "SELECT foto FROM Input_Aspirasi WHERE id_pelaporan = $id_hapus");
    if ($data = mysqli_fetch_assoc($res)) {
        if (!empty($data['foto']) && file_exists("../uploads/" . $data['foto'])) {
            unlink("../uploads/" . $data['foto']); 
        }
        
        // Hapus data dari database
        mysqli_query($conn, "DELETE FROM Aspirasi WHERE id_pelaporan = $id_hapus");
        mysqli_query($conn, "DELETE FROM Input_Aspirasi WHERE id_pelaporan = $id_hapus");

        echo "<script>alert('Aspirasi berhasil dihapus!'); window.location='dashboard.php';</script>";
        exit();
    }
}

// ==========================================
// 2. AMBIL DATA STATISTIK
// ==========================================
$stats = ['Total' => 0, 'Menunggu' => 0, 'Proses' => 0, 'Selesai' => 0];
$q_stats = "SELECT IFNULL(a.status, 'Menunggu') as st, COUNT(ia.id_pelaporan) as jml 
            FROM Input_Aspirasi ia LEFT JOIN Aspirasi a ON ia.id_pelaporan = a.id_pelaporan GROUP BY st";
$res_stats = mysqli_query($conn, $q_stats);

while ($r = mysqli_fetch_assoc($res_stats)) {
    // Normalisasi string status untuk mencocokkan array (misal: 'Diproses' menjadi 'Proses')
    $status_key = $r['st'];
    if (strtolower($status_key) === 'diproses') $status_key = 'Proses';
    
    if(isset($stats[$status_key])) {
        $stats[$status_key] += (int)$r['jml'];
    }
    $stats['Total'] += (int)$r['jml'];
}

// ==========================================
// 3. FILTER & PENCARIAN
// ==========================================
$search   = $_GET['search'] ?? '';
$f_kat    = (int)($_GET['filter_kategori'] ?? 0);
$f_status = $_GET['filter_status'] ?? '';

$where = "1=1"; 

if ($search !== '') {
    $s = mysqli_real_escape_string($conn, $search);
    $where .= " AND (s.nama LIKE '%$s%' OR ia.pesan LIKE '%$s%' OR ia.lokasi LIKE '%$s%')";
}
if ($f_kat > 0) {
    $where .= " AND ia.id_kategori = $f_kat";
}
if ($f_status !== '') {
    if ($f_status === 'Menunggu') {
        $where .= " AND (a.status IS NULL OR a.status = 'Menunggu')";
    } else {
        $where .= " AND a.status = '$f_status'";
    }
}

// ==========================================
// 4. AMBIL DATA LAPORAN (Dengan Pagination)
// ==========================================
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$q_count = "SELECT COUNT(*) as total FROM Input_Aspirasi ia JOIN Siswa s ON ia.nis = s.nis LEFT JOIN Aspirasi a ON ia.id_pelaporan = a.id_pelaporan WHERE $where";
$total_data = mysqli_fetch_assoc(mysqli_query($conn, $q_count))['total'];
$total_pages = ceil($total_data / $limit) ?: 1;

$query = "SELECT ia.*, s.nama, k.ket_kategori, IFNULL(a.status, 'Menunggu') as status_laporan 
          FROM Input_Aspirasi ia 
          JOIN Siswa s ON ia.nis = s.nis 
          JOIN Kategori k ON ia.id_kategori = k.id_kategori 
          LEFT JOIN Aspirasi a ON ia.id_pelaporan = a.id_pelaporan
          WHERE $where 
          ORDER BY ia.id_pelaporan DESC LIMIT $limit OFFSET $offset";
$data_laporan = mysqli_query($conn, $query);

$kategori_list = mysqli_query($conn, "SELECT * FROM Kategori");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - AspirasiKu</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>

    <nav class="admin-nav fade-in">
        <div class="nav-brand">
            <h1>AspirasiKu <span>Admin</span></h1>
            <p>Halo, <?= htmlspecialchars($_SESSION['admin']) ?></p>
        </div>
        <div class="nav-links">
            <a href="dashboard.php" class="btn btn-primary">Dashboard</a>
            <a href="daftar_siswa.php" class="btn btn-outline">Kelola Siswa</a>
            <a href="../logout.php" class="btn btn-secondary">Keluar</a>
        </div>
    </nav>

    <div class="container">
        
        <div class="stats-grid fade-in" style="animation-delay: 0.1s;">
            <div class="stat-card stat-total">
                <h3>Total Laporan</h3>
                <div class="angka"><?= $stats['Total'] ?></div>
            </div>
            <div class="stat-card stat-menunggu">
                <h3>Menunggu</h3>
                <div class="angka"><?= $stats['Menunggu'] ?></div>
            </div>
            <div class="stat-card stat-proses">
                <h3>Diproses</h3>
                <div class="angka"><?= $stats['Proses'] ?></div>
            </div>
            <div class="stat-card stat-selesai">
                <h3>Selesai</h3>
                <div class="angka"><?= $stats['Selesai'] ?></div>
            </div>
        </div>

        <div class="card fade-in" style="animation-delay: 0.2s;">
            <h2 class="card-title">Filter & Pencarian Laporan</h2>
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label>Cari (Nama/Lokasi/Pesan)</label>
                    <input type="text" name="search" class="form-control" placeholder="Ketik kata kunci..." value="<?= htmlspecialchars($search) ?>">
                </div>
                
                <div class="form-group">
                    <label>Kategori</label>
                    <select name="filter_kategori" class="form-control">
                        <option value="">Semua Kategori</option>
                        <?php while ($k = mysqli_fetch_assoc($kategori_list)): ?>
                            <option value="<?= $k['id_kategori'] ?>" <?= $f_kat == $k['id_kategori'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($k['ket_kategori']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="filter_status" class="form-control">
                        <option value="">Semua Status</option>
                        <option value="Menunggu" <?= $f_status == 'Menunggu' ? 'selected' : '' ?>>Menunggu</option>
                        <option value="Proses" <?= ($f_status == 'Proses' || $f_status == 'Diproses') ? 'selected' : '' ?>>Diproses</option>
                        <option value="Selesai" <?= $f_status == 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Cari</button>
                    <a href="dashboard.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>

        <div class="card fade-in" style="animation-delay: 0.3s;">
            <h2 class="card-title">Daftar Laporan Aspirasi</h2>
            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="20%">Pengirim & Tgl</th>
                            <th width="20%">Kategori & Lokasi</th>
                            <th width="30%">Isi Pesan</th>
                            <th width="10%">Status</th>
                            <th width="15%" align="center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if (mysqli_num_rows($data_laporan) == 0) {
                            echo "<tr><td colspan='6' align='center' style='padding: 3rem; color: var(--text-muted);'>Data tidak ditemukan.</td></tr>";
                        }
                        $no = $offset + 1;
                        while ($row = mysqli_fetch_assoc($data_laporan)): 
                            
                            // Tentukan warna badge status
                            $status = $row['status_laporan'];
                            $badge_class = 'badge-menunggu';
                            if (strtolower($status) === 'diproses' || strtolower($status) === 'proses') $badge_class = 'badge-proses';
                            elseif (strtolower($status) === 'selesai') $badge_class = 'badge-selesai';
                        ?>
                        <tr>
                            <td align="center"><?= $no++ ?></td>
                            <td>
                                <strong style="color: var(--text-dark);"><?= htmlspecialchars($row['nama']) ?></strong><br>
                                <span style="font-size: 0.8rem; color: var(--text-muted);">NIS: <?= htmlspecialchars($row['nis']) ?></span><br>
                                <span style="font-size: 0.8rem; color: var(--text-muted);"><?= date('d M Y, H:i', strtotime($row['tanggal'])) ?></span>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($row['ket_kategori']) ?></strong><br>
                                <span style="font-size: 0.85rem; color: var(--text-muted);">Lokasi: <?= htmlspecialchars($row['lokasi']) ?></span>
                            </td>
                            <td>
                                <p style="font-size: 0.9rem; margin-bottom: 5px;"><?= nl2br(htmlspecialchars($row['pesan'])) ?></p>
                                <?php if ($row['foto']): ?>
                                    <a href="../uploads/<?= htmlspecialchars($row['foto']) ?>" target="_blank" class="link-foto">Lihat Lampiran Foto</a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($status) ?></span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 5px; flex-direction: column;">
                                    <a href="proses_tanggapan.php?id=<?= $row['id_pelaporan'] ?>" class="btn btn-primary btn-action">Proses / Tanggapi</a>
                                    <a href="?hapus=<?= $row['id_pelaporan'] ?>" onclick="return confirm('Peringatan: Yakin ingin menghapus aspirasi ini secara permanen?')" class="btn btn-danger btn-action">Hapus</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="pagination">
                <span>Menampilkan halaman <?= $page ?> dari <?= $total_pages ?></span>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&filter_kategori=<?= $f_kat ?>&filter_status=<?= urlencode($f_status) ?>" 
                       class="page-link <?= $i == $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>

    </div>

</body>
</html>