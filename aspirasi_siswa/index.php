<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - Sistem Pengaduan Aspirasi Siswa</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: #fff; padding: 20px 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 300px; }
        h2 { text-align: center; margin-bottom: 20px; }
        input, button { width: 100%; padding: 10px; margin: 8px 0; box-sizing: border-box; }
        button { background-color: #007BFF; color: white; border: none; cursor: pointer; border-radius: 4px; font-weight: bold; }
        button:hover { background-color: #0056b3; }
        .hidden { display: none; }
        .error-msg { color: #dc3545; font-size: 12px; margin-top: -5px; margin-bottom: 5px; display: none; font-weight: bold; }
    </style>
</head>
<body>

<div class="login-box">
    <h2>Login Portal</h2>
    <form action="proses_login.php" method="POST" id="loginForm">
        <input type="hidden" name="login" value="true">
        <input type="hidden" name="role" id="role-input" value="">

        <input type="text" name="identifier" id="identifier" placeholder="Masukkan Username atau NIS" required>
        <div class="error-msg" id="error-msg">Pengguna tidak ditemukan!</div>

        <div id="password-container" class="hidden">
            <input type="password" name="password" id="password" placeholder="Masukkan Password">
        </div>

        <button type="submit" id="btn-login">Lanjut</button>
    </form>
</div>

<script>
    // 1. Deklarasi Elemen DOM agar tidak dipanggil berulang-ulang
    const elements = {
        form: document.getElementById("loginForm"),
        role: document.getElementById("role-input"),
        id: document.getElementById("identifier"),
        error: document.getElementById("error-msg"),
        passCont: document.getElementById("password-container"),
        pass: document.getElementById("password"),
        btn: document.getElementById("btn-login")
    };

    let loginStep = 1; 

    // 2. Helper untuk mengubah status tombol
    const setButton = (text, disabled) => {
        elements.btn.innerText = text;
        elements.btn.disabled = disabled;
    };

    // 3. Event Listener Klik Tombol
    elements.btn.addEventListener("click", (e) => {
        if (loginStep === 1) {
            e.preventDefault(); 
            const identifierVal = elements.id.value.trim();

            if (!identifierVal) return alert("Silakan isi Username atau NIS terlebih dahulu.");
            
            setButton("Mengecek...", true);
            cekPengguna(identifierVal);
        }
    });

    // 4. Fungsi Utama Fetch Data
    function cekPengguna(identifier) {
        fetch("proses_login.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "action=check_user&identifier=" + encodeURIComponent(identifier)
        })
        .then(res => res.ok ? res.json() : Promise.reject("HTTP Error " + res.status))
        .then(data => {
            if (data.status === "ada") {
                elements.error.style.display = "none";
                elements.role.value = data.role; 
                elements.id.readOnly = true;

                if (data.role === "admin") {
                    elements.passCont.classList.remove("hidden");
                    elements.pass.focus();
                    setButton("Masuk", false);
                    loginStep = 2; // Lanjut ke tahap input password
                } else {
                    elements.form.submit(); // Jika siswa, langsung login
                }
            } else {
                elements.error.style.display = "block";
                setButton("Lanjut", false);
            }
        })
        .catch(err => {
            console.error("Terjadi kesalahan:", err);
            alert("Terjadi kesalahan saat memeriksa pengguna.");
            setButton("Lanjut", false);
        });
    }
</script>

</body>
</html>