<?php
session_start();
include 'koneksi.php';

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']);

    // Simulasi login kredensial (bisa disesuaikan)
    if ($username == 'petugas' && $password == '12345') {
        $_SESSION['login_petugas'] = true;
        $_SESSION['username'] = $username;
        header('location: portal_petugas.php');
        exit;
    } else {
        $error = "Username atau Password salah!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login Portal Petugas - Mini Hospital</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #2c3e50; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-card { background: white; padding: 30px; border-radius: 8px; width: 350px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        h3 { margin-top: 0; color: #2c3e50; text-align: center; }
        input { width: 100%; padding: 10px; margin: 8px 0 15px 0; border: 1px solid #cbd5e1; border-radius: 5px; box-sizing: border-box; }
        button { background: #3b82f6; color: white; border: none; padding: 12px; width: 100%; font-weight: bold; border-radius: 5px; cursor: pointer; }
        button:hover { background: #2563eb; }
        .error { color: #ef4444; font-size: 13px; text-align: center; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="login-card">
        <h3>🔐 Login Portal Petugas</h3>
        <?php if(isset($error)) { echo "<div class='error'>$error</div>"; } ?>
        <form method="POST">
            <label style="font-size: 13px; font-weight: bold; color: #334155;">Username</label>
            <input type="text" name="username" placeholder="Masukkan username..." required>
            
            <label style="font-size: 13px; font-weight: bold; color: #334155;">Password</label>
            <input type="password" name="password" placeholder="Masukkan password..." required>
            
            <button type="submit" name="login">Masuk Portal</button>
        </form>
    </div>
</body>
</html>