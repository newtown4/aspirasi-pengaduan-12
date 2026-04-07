<?php
session_start();
require_once 'koneksi.php';

// Pastikan request benar-benar berasal dari form login
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['login'])) {
    header("Location: index.php");
    exit();
}

$role = $_POST['role'] ?? '';

// --- FUNGSI BANTUAN SEDERHANA ---

// 1. Fungsi untuk mengembalikan user ke halaman login beserta pesan error
function errorLogin($pesan) {
    $_SESSION['error_pesan'] = $pesan;
    header("Location: index.php");
    exit();
}

// 2. Fungsi ringkas untuk mengambil data dari database menggunakan Prepared Statement
function getUser($conn, $query, $param) {
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $param);
    mysqli_stmt_execute($stmt);
    return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

// --- LOGIKA LOGIN ---

if ($role === 'admin') {
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';

    if (empty($user) || empty($pass)) {
        errorLogin("Username dan Password tidak boleh kosong!");
    }

    // Ambil data admin berdasarkan username
    $data = getUser($conn, "SELECT username, password FROM Admin WHERE username = ?", $user);

    // Verifikasi kecocokan password hash
    if ($data && password_verify($pass, $data['password'])) {
        $_SESSION['admin'] = $data['username'];
        header("Location: admin/dashboard.php");
        exit();
    } else {
        errorLogin("Username atau password admin salah!");
    }

} elseif ($role === 'siswa') {
    $nis = trim($_POST['nis'] ?? '');

    if (empty($nis)) {
        errorLogin("NIS wajib diisi!");
    }

    // Ambil data siswa berdasarkan NIS saja (tanpa password)
    $data = getUser($conn, "SELECT nis, nama FROM siswa WHERE nis = ?", $nis);

    if ($data) {
        $_SESSION['siswa'] = $data['nis'];
        $_SESSION['nama_siswa'] = $data['nama'];
        header("Location: siswa/dashboard.php");
        exit();
    } else {
        errorLogin("Data Siswa dengan NIS tersebut tidak ditemukan!");
    }

} else {
    errorLogin("Akses role tidak valid!");
}
?>