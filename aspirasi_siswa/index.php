<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - Sistem Pengaduan Aspirasi Siswa</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: #fff; padding: 20px 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 300px; }
        .login-box h2 { text-align: center; margin-bottom: 20px; }
        input, select, button { width: 100%; padding: 10px; margin: 8px 0; box-sizing: border-box; }
        button { background-color: #007BFF; color: white; border: none; cursor: pointer; border-radius: 4px; }
        button:hover { background-color: #0056b3; }
        .hidden { display: none; }
    </style>
</head>
<body>

<div class="login-box">
    <h2>Login Portal</h2>
    <form action="proses_login.php" method="POST">
        <label>Login Sebagai:</label>
        <select name="role" id="role" onchange="toggleFields()">
            <option value="siswa">Siswa</option>
            <option value="admin">Admin</option>
        </select>

        <div id="form-siswa">
            <input type="text" name="nis" placeholder="Masukkan NIS Anda">
        </div>

        <div id="form-admin" class="hidden">
            <input type="text" name="username" placeholder="Username">
            <input type="password" name="password" placeholder="Password">
        </div>

        <button type="submit" name="login">Masuk</button>
    </form>
</div>

<script>
    function toggleFields() {
        var role = document.getElementById("role").value;
        if (role === "admin") {
            document.getElementById("form-admin").classList.remove("hidden");
            document.getElementById("form-siswa").classList.add("hidden");
        } else {
            document.getElementById("form-admin").classList.add("hidden");
            document.getElementById("form-siswa").classList.remove("hidden");
        }
    }
</script>

</body>
</html>