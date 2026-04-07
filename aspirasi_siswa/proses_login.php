<?php
session_start();
include 'koneksi.php';

// =========================================================================
// BAGIAN 1: MENANGANI AJAX UNTUK CEK PENGGUNA (Tahap 1 - Klik "Lanjut")
// =========================================================================
if (isset($_POST['action']) && $_POST['action'] == 'check_user') {
    header('Content-Type: application/json');
    $id = mysqli_real_escape_string($conn, $_POST['identifier']);
    
    // Cek Admin
    if (mysqli_num_rows(mysqli_query($conn, "SELECT 1 FROM Admin WHERE username = '$id'"))) {
        exit(json_encode(["status" => "ada", "role" => "admin"]));
    }
    
    // Cek Siswa
    if (mysqli_num_rows(mysqli_query($conn, "SELECT 1 FROM Siswa WHERE nis = '$id'"))) {
        exit(json_encode(["status" => "ada", "role" => "siswa"]));
    }

    exit(json_encode(["status" => "tidak_ada"]));
}

// =========================================================================
// BAGIAN 2: MENANGANI PROSES LOGIN UTAMA (Tahap 2 - Klik "Masuk")
// =========================================================================
if (isset($_POST['login'])) {
    $role = $_POST['role'];
    $id   = mysqli_real_escape_string($conn, $_POST['identifier']);

    // Tentukan tabel dan kolom pencarian berdasarkan role (DRY)
    $table = ($role === 'admin') ? 'Admin' : 'Siswa';
    $kolom = ($role === 'admin') ? 'username' : 'nis';

    $result = mysqli_query($conn, "SELECT * FROM $table WHERE $kolom = '$id'");
    $data   = mysqli_fetch_assoc($result);

    if ($data) {
        // Jika role Admin, verifikasi password terlebih dahulu
        if ($role === 'admin' && !password_verify($_POST['password'], $data['password'])) {
            $error = "Password Admin salah!";
        } else {
            // Jika berhasil melewati rintangan, proses Session & Redirect (DRY)
            if ($role === 'admin') {
                $_SESSION['admin'] = $data['username'];
                header("Location: admin/dashboard.php");
            } else {
                $_SESSION['siswa'] = $data['nis'];
                $_SESSION['nama_siswa'] = $data['nama'];
                header("Location: siswa/dashboard.php");
            }
            exit();
        }
    } else {
        // Jika data tidak ditemukan di database
        $error = ($role === 'admin') ? "Username Admin tidak ditemukan!" : "NIS tidak ditemukan! Silakan lapor wali kelas.";
    }

    // Output Error Tunggal (DRY)
    echo "<script>alert('$error'); window.location='index.php';</script>";
    exit();
}