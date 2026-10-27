<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Memanggil koneksi database (naik 1 folder ke minihospital/koneksi.php)
$path_koneksi = __DIR__ . '/../koneksi.php';
if (file_exists($path_koneksi)) {
    include $path_koneksi;
} else {
    die("<div style='color:red; font-family:sans-serif; padding:20px;'><b>Error System:</b> File <code>koneksi.php</code> tidak ditemukan di folder utama SIMRS!</div>");
}

// Jika pengguna sudah login di modul ini, langsung lempar ke dashboard modul
if (isset($_SESSION['login_modul_k3rs']) &&$_SESSION['login_modul_k3rs'] === true) {
    header("Location: dashboard.php");
    exit;
}

// Proses Otentikasi Login Modul
$error = '';
if (isset($_POST['login_modul'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);$password = mysqli_real_escape_string($koneksi,$_POST['password']);

    $cek = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username' AND password='$password'");
    
    if ($cek && mysqli_num_rows($cek) > 0) {$data = mysqli_fetch_assoc($cek);$_SESSION['login_modul_k3rs'] = true;
        $_SESSION['user_k3rs']        =$data['nama_lengkap'];
        $_SESSION['role_k3rs']        =$data['role'];

        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Username atau Password Anda salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk ke Sistem - SI Monitoring AKK</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
        }

        body {
            background-color: #1a73e8;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(255, 255, 255, 0.08) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(255, 255, 255, 0.12) 0%, transparent 50%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
            padding: 20px;
        }

        /* Latar Belakang Gelombang (Wave Background) */
        .wave-bg {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            overflow: hidden;
            line-height: 0;
            z-index: 1;
            opacity: 0.25;
            pointer-events: none;
        }

        .wave-bg svg {
            position: relative;
            display: block;
            width: calc(150% + 1.3px);
            height: 280px;
        }

        /* Kartu Form Login */
        .login-card {
            background: #ffffff;
            width: 100%;
            max-width: 440px;
            border-radius: 20px;
            padding: 40px 35px 30px 35px;
            box-shadow: 0 20px 40px rgba(0, 38, 110, 0.2);
            position: relative;
            z-index: 10;
            text-align: center;
        }

        /* Icon Perisai Detak Jantung */
        .icon-header {
            width: 72px;
            height: 72px;
            background: #0052cc;
            border-radius: 20px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
            box-shadow: 0 8px 18px rgba(0, 82, 204, 0.3);
            clip-path: polygon(50% 0%, 100% 15%, 100% 65%, 50% 100%, 0% 65%, 0% 15%);
        }

        .icon-header svg {
            width: 38px;
            height: 38px;
            fill: none;
            stroke: #ffffff;
            stroke-width: 2.2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .title {
            font-size: 24px;
            font-weight: 700;
            color: #0c2340;
            margin-bottom: 8px;
        }

        .subtitle {
            font-size: 13.5px;
            color: #627d98;
            margin-bottom: 20px;
        }

        /* Dot Indicator / Separator */
        .dot-separator {
            width: 6px;
            height: 6px;
            background-color: #2b6cb0;
            border-radius: 50%;
            margin: 0 auto 25px auto;
        }

        /* Error Box */
        .error-box {
            background-color: #fff5f5;
            color: #e53e3e;
            border: 1px solid #feb2b2;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
            text-align: left;
        }

        /* Form Controls */
        .form-group {
            text-align: left;
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #0c2340;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper input {
            width: 100%;
            padding: 13px 42px;
            border: 1px solid #d9e2ec;
            border-radius: 10px;
            font-size: 14px;
            color: #102a43;
            outline: none;
            transition: all 0.2s ease-in-out;
            background-color: #f8fafc;
        }

        .input-wrapper input::placeholder {
            color: #9fb3c8;
        }

        .input-wrapper input:focus {
            border-color: #0052cc;
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(0, 82, 204, 0.12);
        }

        /* Icon Inside Input */
        .input-icon-left {
            position: absolute;
            left: 14px;
            width: 18px;
            height: 18px;
            stroke: #829ab1;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
            pointer-events: none;
        }

        .input-icon-right {
            position: absolute;
            right: 14px;
            width: 20px;
            height: 20px;
            stroke: #829ab1;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
            cursor: pointer;
            transition: stroke 0.2s;
        }

        .input-icon-right:hover {
            stroke: #0052cc;
        }

        /* Tombol Login */
        .btn-login {
            width: 100%;
            padding: 14px;
            background-color: #0047bb;
            color: #ffffff;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 10px;
            box-shadow: 0 4px 12px rgba(0, 71, 187, 0.25);
        }

        .btn-login:hover {
            background-color: #003799;
            transform: translateY(-1px);
            box-shadow: 0 6px 15px rgba(0, 71, 187, 0.35);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        /* Footer Copyright */
        .card-footer {
            margin-top: 35px;
            padding-top: 18px;
            border-top: 1px solid #f0f4f8;
            font-size: 12.5px;
            color: #829ab1;
        }
    </style>
</head>
<body>

    <!-- Kartu Login Utama -->
    <div class="login-card">
        
        <!-- Logo Perisai ECG -->
        <div class="icon-header">
            <svg viewBox="0 0 24 24">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                <path d="M7 12h2.5l1.5-3 2 6 1.5-3H17"></path>
            </svg>
        </div>

        <h2 class="title">Masuk ke Sistem</h2>
        <p class="subtitle">Masuk untuk mengakses dashboard monitoring</p>
        
        <div class="dot-separator"></div>

        <?php if (!empty($error)): ?>
            <div class="error-box">⚠️ <?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <!-- Username Input -->
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-wrapper">
                    <svg class="input-icon-left" viewBox="0 0 24 24">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <input type="text" id="username" name="username" placeholder="Masukkan username Anda" required autofocus>
                </div>
            </div>

            <!-- Password Input -->
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <svg class="input-icon-left" viewBox="0 0 24 24">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    <input type="password" id="password" name="password" placeholder="Masukkan password Anda" required>
                    
                    <!-- Toggle Mata (Intip Password) -->
                    <svg class="input-icon-right" id="togglePassword" viewBox="0 0 24 24" onclick="togglePasswordVisibility()">
                        <path id="eyePath" d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                        <line x1="1" y1="1" x2="23" y2="23"></line>
                    </svg>
                </div>
            </div>

            <!-- Tombol Submit -->
            <button type="submit" name="login_modul" class="btn-login">Login</button>
        </form>

        <div class="card-footer">
            &copy; <?php echo date('Y'); ?> SI Monitoring AKK
        </div>
    </div>

    <!-- Hiasan Gelombang Biru Bawah -->
    <div class="wave-bg">
        <svg viewBox="0 0 1200 120" preserveAspectRatio="none">
            <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" fill="#ffffff"></path>
        </svg>
    </div>

    <!-- Script JavaScript Toggle Password -->
    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const eyePath = document.getElementById('eyePath');
            const toggleIcon = document.getElementById('togglePassword');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                // Icon Mata Terbuka
                toggleIcon.innerHTML = `
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                `;
            } else {
                passwordInput.type = 'password';
                // Icon Mata Coret / Tertutup
                toggleIcon.innerHTML = `
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                    <line x1="1" y1="1" x2="23" y2="23"></line>
                `;
            }
        }
    </script>
</body>
</html>