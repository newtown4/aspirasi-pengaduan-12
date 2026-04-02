<?php
session_start();
// Menghapus semua session yang aktif
session_unset();
session_destroy();

// Mengarahkan kembali ke halaman login
header("Location: index.php");
exit();
?>