<?php
$conn = mysqli_connect("localhost", "root", "", "db_aspirasi");

if (!$conn) {
    die("Koneksi database gagal bermasalah. Silakan hubungi administrator.");
}
?>