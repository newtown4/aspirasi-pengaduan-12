<?php
session_start();
require_once 'koneksi.php'; 

// 1. AJAX API HANDLER (Cek user di database)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'check_user') {
    $id = trim($_POST['identifier']);
    
    // Fungsi ringkas untuk cek data dengan prepared statement
    $check = function($table, $col) use ($conn, $id) {
        $stmt = mysqli_prepare($conn, "SELECT $col FROM $table WHERE $col = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $id);
        mysqli_stmt_execute($stmt);
        return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    };

    if ($check('Admin', 'username')) echo 'admin';
    elseif ($check('Siswa', 'nis')) echo 'siswa';
    else echo 'not_found';
    
    exit(); 
}

// 2. PAGE CONFIGURATION
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Pengaduan</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <div class="modal-container">
        <div class="modal-left">
            </div>

        <div class="modal-right">
            <h2>Welcome Back</h2> <?php if (isset($_SESSION['error_pesan'])): ?>
                <div class="error-msg"><b>Error:</b> <?= htmlspecialchars($_SESSION['error_pesan']) ?></div>
                <?php unset($_SESSION['error_pesan']); ?>
            <?php endif; ?>

            <form id="loginForm" action="proses_login.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="login" value="1">
                <input type="hidden" name="role" id="h-role">
                <input type="hidden" name="nis" id="h-nis">
                <input type="hidden" name="username" id="h-user">

                <div class="input-group">
                    <label>NIS / Username</label>
                    <input type="text" id="identifier" required autocomplete="off" placeholder="Your NIS or Username">
                </div>

                <div id="passwordGroup" class="input-group" hidden>
                    <label>Password</label>
                    <input type="password" name="password" id="password" placeholder="Your Password">
                </div>

                <button type="submit" id="submitBtn">Log in</button>
            </form>
        </div>
    </div>

    <script>
        const form = document.getElementById('loginForm');
        const idInput = document.getElementById('identifier');
        const passGroup = document.getElementById('passwordGroup');
        const passInput = document.getElementById('password');
        const submitBtn = document.getElementById('submitBtn');
        
        let stepTwo = false; 

        form.addEventListener('submit', async (e) => {
            if (stepTwo) return; 
            
            e.preventDefault(); 
            const idVal = idInput.value.trim();

            // Efek loading pada tombol
            const originalBtnText = submitBtn.innerText;
            submitBtn.innerText = "Memeriksa...";
            submitBtn.disabled = true;

            const formData = new FormData();
            formData.append('action', 'check_user');
            formData.append('identifier', idVal);

            try {
                const response = await fetch('', { method: 'POST', body: formData });
                const status = await response.text().then(text => text.trim());

                if (status === 'admin') {
                    document.getElementById('h-role').value = 'admin';
                    document.getElementById('h-user').value = idVal;
                    
                    idInput.readOnly = true;        
                    idInput.classList.add('readonly-input');
                    passGroup.hidden = false;       
                    passInput.required = true;
                    stepTwo = true;                 
                    
                    submitBtn.innerText = "Log In Admin";
                    submitBtn.disabled = false; // <--- TAMBAHKAN BARIS INI
                }
                else if (status === 'siswa') {
                    document.getElementById('h-role').value = 'siswa';
                    document.getElementById('h-nis').value = idVal;
                    form.submit(); 
                } 
                else {
                    alert("Error: Identitas tidak ditemukan!"); 
                    submitBtn.innerText = originalBtnText;
                }
            } catch (error) {
                alert("Terjadi kesalahan jaringan.");
                submitBtn.innerText = originalBtnText;
            } finally {
                if(!stepTwo) submitBtn.disabled = false;
            }
        });
    </script>

</body>
</html>