<?php
session_start();
include 'koneksi.php';

if (isset($_POST['login'])) {
    $role = $_POST['role'];

    if ($role == 'admin') {
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $password = mysqli_real_escape_string($conn, $_POST['password']);

        // Cek login admin (Note: idealnya password di-hash dengan password_hash() untuk produksi asli)
        $query = "SELECT * FROM Admin WHERE username='$username' AND password='$password'";
        $result = mysqli_query($conn, $query);

        if (mysqli_num_rows($result) > 0) {
            $_SESSION['admin'] = $username;
            header("Location: admin/dashboard.php");
            exit();
        } else {
            echo "<script>alert('Username atau Password Admin salah!'); window.location='index.php';</script>";
        }

    } else if ($role == 'siswa') {
        $nis = mysqli_real_escape_string($conn, $_POST['nis']);

        // Cek login siswa menggunakan NIS
        $query = "SELECT * FROM Siswa WHERE nis='$nis'";
        $result = mysqli_query($conn, $query);

        if (mysqli_num_rows($result) > 0) {
            $data = mysqli_fetch_assoc($result);
            $_SESSION['siswa'] = $data['nis'];
            $_SESSION['nama_siswa'] = $data['nama'];
            header("Location: siswa/dashboard.php");
            exit();
        } else {
            echo "<script>alert('NIS tidak ditemukan! Silakan lapor wali kelas.'); window.location='index.php';</script>";
        }
    }
}
?>