<?php
session_start();

// 1. Hapus semua variabel sesi yang terdaftar
session_unset(); 

// 2. Hancurkan sesi fisik di server
session_destroy();

// 3. Kembalikan user ke halaman index (login)
header("Location: index.php");
exit();
?>