<?php
session_start();

// Cek apakah admin sudah login
if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../koneksi.php';

// ==========================================
// 1. VALIDASI ID & AMBIL DATA LAPORAN
// ==========================================
$id_pelaporan = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id_pelaporan) {
    header("Location: dashboard.php");
    exit();
}

// Mengambil detail laporan beserta data siswa dan tanggapan sebelumnya (jika ada)
$query = "SELECT ia.*, s.nama, k.ket_kategori, a.status AS status_tanggapan, a.feedback AS feedback_tanggapan 
          FROM Input_Aspirasi ia 
          JOIN Siswa s ON ia.nis = s.nis
          JOIN Kategori k ON ia.id_kategori = k.id_kategori
          LEFT JOIN Aspirasi a ON ia.id_pelaporan = a.id_pelaporan
          WHERE ia.id_pelaporan = $id_pelaporan";
$data = mysqli_fetch_assoc(mysqli_query($conn, $query));

if (!$data) {
    echo "<script>alert('Laporan tidak ditemukan!'); window.location='dashboard.php';</script>";
    exit();
}

// ==========================================
// 2. PROSES SIMPAN TANGGAPAN (POST)
// ==========================================
if (isset($_POST['simpan'])) {
    $status      = mysqli_real_escape_string($conn, $_POST['status']);
    $feedback    = mysqli_real_escape_string($conn, trim($_POST['feedback']));
    $id_kategori = (int)$data['id_kategori']; 

    // Query cerdas: INSERT jika data belum ada, UPDATE jika data sudah ada
    $upsert_query = "INSERT INTO Aspirasi (id_pelaporan, status, id_kategori, feedback) 
                     VALUES ($id_pelaporan, '$status', $id_kategori, '$feedback') 
                     ON DUPLICATE KEY UPDATE status = '$status', feedback = '$feedback'";
    
    if (mysqli_query($conn, $upsert_query)) {
        echo "<script>alert('Tanggapan berhasil disimpan!'); window.location='dashboard.php';</script>";
        exit();
    } else {
        echo "<script>alert('Gagal menyimpan tanggapan!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proses Tanggapan - Admin AspirasiKu</title>
    <link rel="stylesheet" href="../css/admin.css">
    
    <style>
        .tanggapan-wrapper {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
        @media (min-width: 992px) {
            .tanggapan-wrapper {
                grid-template-columns: 3fr 2fr; /* Membagi layar: Kiri lebih lebar (Detail), Kanan (Form) */
                align-items: start;
            }
        }
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
            background: var(--bg-page);
            padding: 1rem;
            border-radius: 12px;
        }
        .detail-item span {
            display: block;
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }
        .detail-item p {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 500;
        }
        .pesan-box {
            background-color: var(--bg-input);
            border-left: 4px solid var(--primary-color);
            padding: 1.5rem;
            border-radius: 0 12px 12px 0;
            margin-bottom: 1.5rem;
            font-style: italic;
            line-height: 1.6;
            color: var(--text-dark);
        }
        .foto-lampiran {
            max-width: 100%;
            height: auto;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            transition: transform 0.2s;
            border: 1px solid var(--border-color);
        }
        .foto-lampiran:hover {
            transform: scale(1.02);
        }
    </style>
</head>
<body>

    <nav class="admin-nav fade-in">
        <div class="nav-brand">
            <h1>AspirasiKu <span>Admin</span></h1>
            <p>Halo, <?= htmlspecialchars($_SESSION['admin']) ?></p>
        </div>
        <div class="nav-links">
            <a href="dashboard.php" class="btn btn-outline">Dashboard</a>
            <a href="daftar_siswa.php" class="btn btn-outline">Kelola Siswa</a>
            <a href="../logout.php" class="btn btn-secondary">Keluar</a>
        </div>
    </nav>

    <div class="container">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;" class="fade-in">
            <h2 style="font-size: 1.4rem; font-weight: 600; color: var(--text-dark);">Tindak Lanjut Laporan</h2>
            <a href="dashboard.php" class="btn btn-secondary">Kembali</a>
        </div>

        <div class="tanggapan-wrapper">
            
            <div class="card fade-in" style="animation-delay: 0.1s; margin-bottom: 0;">
                <h2 class="card-title">Informasi Detail Laporan</h2>
                
                <div class="detail-grid">
                    <div class="detail-item">
                        <span>Pengirim</span>
                        <p><?= htmlspecialchars($data['nama']) ?></p>
                        <p style="font-size: 0.8rem; color: var(--text-muted);">NIS: <?= htmlspecialchars($data['nis']) ?></p>
                    </div>
                    <div class="detail-item">
                        <span>Tanggal Masuk</span>
                        <p><?= date('d M Y, H:i', strtotime($data['tanggal'])) ?></p>
                    </div>
                    <div class="detail-item">
                        <span>Kategori</span>
                        <p><?= htmlspecialchars($data['ket_kategori']) ?></p>
                    </div>
                    <div class="detail-item">
                        <span>Lokasi Kejadian</span>
                        <p><?= htmlspecialchars($data['lokasi']) ?></p>
                    </div>
                </div>

                <h3 style="font-size: 0.95rem; font-weight: 600; margin-bottom: 0.5rem; color: var(--text-muted); text-transform: uppercase;">Isi Pesan / Keluhan</h3>
                <div class="pesan-box">
                    "<?= nl2br(htmlspecialchars($data['pesan'])) ?>"
                </div>

                <h3 style="font-size: 0.95rem; font-weight: 600; margin-bottom: 0.5rem; color: var(--text-muted); text-transform: uppercase;">Bukti Lampiran</h3>
                <?php if (!empty($data['foto'])): ?>
                    <a href="../uploads/<?= htmlspecialchars(basename($data['foto'])) ?>" target="_blank" style="display: inline-block;">
                        <img src="../uploads/<?= htmlspecialchars(basename($data['foto'])) ?>" alt="Bukti Foto Laporan" class="foto-lampiran">
                        <span style="display: block; margin-top: 0.5rem; font-size: 0.85rem; color: var(--blue-color); font-weight: 500;">Klik gambar untuk memperbesar</span>
                    </a>
                <?php else: ?>
                    <div style="padding: 2rem; text-align: center; background: var(--bg-page); border-radius: 12px; color: var(--text-muted); font-style: italic;">
                        Siswa tidak menyertakan foto lampiran pada laporan ini.
                    </div>
                <?php endif; ?>
            </div>

            <div class="card fade-in" style="animation-delay: 0.2s; position: sticky; top: 100px;">
                <h2 class="card-title">Berikan Tanggapan Admin</h2>
                
                <form method="POST">
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label style="font-weight: 600;">Update Status Laporan</label>
                        <select name="status" class="form-control" required style="cursor: pointer;">
                            <?php 
                            $status_sekarang = $data['status_tanggapan'] ?? 'Menunggu';
                            $opsi_status = ['Menunggu', 'Proses', 'Selesai'];
                            
                            foreach ($opsi_status as $opsi): 
                            ?>
                                <option value="<?= $opsi ?>" <?= $status_sekarang === $opsi ? 'selected' : '' ?>>
                                    <?= $opsi ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.5rem; display: block;">
                            Pilih 'Proses' jika laporan sedang ditindaklanjuti, dan 'Selesai' jika sudah tuntas.
                        </span>
                    </div>

                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label style="font-weight: 600;">Tulis Feedback / Tanggapan</label>
                        <textarea name="feedback" class="form-control" rows="8" required placeholder="Tuliskan respon atau hasil tindak lanjut di sini..."><?= htmlspecialchars($data['feedback_tanggapan'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" name="simpan" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1rem;">
                        Simpan Tanggapan Laporan
                    </button>
                </form>
            </div>

        </div>
    </div>

</body>
</html>