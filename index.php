<?php
session_start();
include 'koneksi.php';

// 1. Jika sudah login, langsung alihkan ke Portal Petugas
if (isset($_SESSION['login_simrs'])) {
    header("Location: portal_petugas.php");
    exit;
}

// 2. Proses Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// 3. Proses Login
if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']);
    
    $cek = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username' AND password='$password'");
    if (mysqli_num_rows($cek) > 0) {
        $data = mysqli_fetch_assoc($cek);
        
        // Simpan Data Sesi Lengkap
        $_SESSION['login_simrs']   = true;
        $_SESSION['id_user']       = $data['id_user'];
        $_SESSION['nama_user']     = $data['nama_lengkap'];
        $_SESSION['role_user']     = $data['role'];
        $_SESSION['last_activity'] = time(); // Penanda waktu aktivitas awal
        
        header("Location: portal_petugas.php");
        exit;
    } else {
        $error = "Username atau Password tidak valid!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Gateway Login - SIMRS Mini Hospital</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, sans-serif; 
            background: #4B5320; 
            color: white; 
            text-align: center; 
            margin: 0; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
        }
        .box { 
            background: white; 
            color: #333; 
            padding: 40px; 
            border-radius: 12px; 
            width: 100%; 
            max-width: 380px; 
            box-shadow: 0 8px 24px rgba(0,0,0,0.3); 
        }
        h2 { color: #4B5320; margin-top: 0; }
        input { 
            width: 100%; 
            padding: 12px; 
            margin: 8px 0 16px; 
            border: 1px solid #ccc; 
            border-radius: 5px; 
            box-sizing: border-box; 
        }
        .btn { 
            display: block; 
            width: 100%; 
            padding: 14px; 
            background: #FFD700; 
            color: #333; 
            text-decoration: none; 
            font-weight: bold; 
            font-size: 15px; 
            border-radius: 8px; 
            border: none; 
            cursor: pointer; 
            transition: 0.2s; 
            box-sizing: border-box; 
        }
        .btn:hover { background: #e6c200; }
        .error { 
            background: #fef2f2; 
            color: #dc2626; 
            border: 1px solid #fca5a5; 
            padding: 10px; 
            border-radius: 6px; 
            font-size: 13px; 
            font-weight: bold; 
            margin-bottom: 15px; 
        }
        .public-links { 
            margin-top: 25px; 
            border-top: 1px dashed #ccc; 
            padding-top: 15px; 
            font-size: 13px; 
            color: #666; 
        }
        .public-links a { color: #2c3e50; font-weight: bold; text-decoration: none; margin: 0 4px; }
        .public-links a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="box">
    <h2>🏥 SIMRS Login</h2>
    <p style="color: #666; margin-bottom: 20px; font-size: 14px;">Masukkan kredensial staf Anda</p>
    
    <!-- PESAN PERINGATAN TIMEOUT -->
    <?php if (isset($_GET['pesan']) && $_GET['pesan'] == 'timeout') { ?>
        <div class="error">
            ⚠ Sesi Anda telah berakhir karena tidak ada aktivitas selama 15 menit. Silakan login kembali.
        </div>
    <?php } ?>

    <?php if(isset($error)) { echo "<div class='error'>$error</div>"; } ?>
    
    <form method="POST">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="login" class="btn">Masuk Sistem ➔</button>
    </form>

    <div class="public-links">
        Akses Layar Publik:<br>
        <a href="apm.php" target="_blank">🎫 APM (Kiosk)</a> | 
        <a href="monitor_antrean.php" target="_blank">📺 Monitor Antrean</a>
    </div>
</div>

</body>
</html>