<?php
// Memanggil pengaman sesi & timeout
include 'cek_sesi.php';

// Menarik identitas user aktif dari Session
$nama_user = $_SESSION['nama_user'] ?? 'Petugas';
$role_user = $_SESSION['role_user'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Terpadu Petugas - SIMRS Mini Hospital</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body { 
            margin: 0; 
            display: flex; 
            height: 100vh; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            overflow: hidden; 
            background: #f8fafc;
        }

        /* OVERLAY GELAP UNTUK TAMPILAN MOBILE */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 99;
            transition: opacity 0.3s ease;
        }
        .sidebar-overlay.active {
            display: block;
        }

        /* SIDEBAR UTAMA */
        .sidebar { 
            width: 270px; 
            background: #2c3e50; 
            color: white; 
            display: flex; 
            flex-direction: column; 
            box-shadow: 2px 0 8px rgba(0,0,0,0.2);
            z-index: 100;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            flex-shrink: 0;
        }

        .sidebar .logo-area { 
            text-align: center; 
            padding: 18px 12px; 
            background: #1a252f; 
            border-bottom: 3px solid #FFD700;
            position: relative;
            white-space: nowrap;
        }
        .sidebar .logo-area h2 { 
            margin: 0; 
            font-size: 18px; 
            color: #FFD700; 
            line-height: 1.3;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .sidebar .logo-area p { 
            margin: 6px 0 0 0; 
            font-size: 11px; 
            color: #bdc3c7; 
            letter-spacing: 1px; 
            line-height: 1.3; 
        }

        /* WIDGET PROFIL USER */
        .user-card { 
            background: #1e293b; 
            padding: 12px 16px; 
            border-bottom: 1px solid #34495e; 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            white-space: nowrap;
            transition: all 0.3s ease;
        }
        .user-avatar { 
            background: #3b82f6; 
            width: 38px; 
            height: 38px; 
            border-radius: 50%; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            font-weight: bold; 
            font-size: 16px; 
            color: white; 
            flex-shrink: 0; 
        }
        .user-info { 
            transition: opacity 0.2s; 
        }
        .user-name { font-size: 13px; font-weight: bold; color: #ffffff; white-space: nowrap; line-height: 1.3; }
        .user-role { font-size: 11px; color: #38bdf8; font-weight: 600; margin-top: 2px; line-height: 1.2; }
        
        /* NAVIGASI MENU */
        .menu-list { 
            display: flex; 
            flex-direction: column; 
            padding: 0; 
            margin: 0; 
            overflow-y: auto;
            flex-grow: 1;
            overflow-x: hidden;
        }
        
        /* Scrollbar Kustom */
        .menu-list::-webkit-scrollbar { width: 6px; }
        .menu-list::-webkit-scrollbar-thumb { background: #34495e; border-radius: 4px; }

        .menu-list a { 
            color: #ecf0f1; 
            text-decoration: none; 
            padding: 12px 20px; 
            border-bottom: 1px solid #34495e; 
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s ease; 
            display: flex;
            align-items: center;
            gap: 12px;
            white-space: nowrap;
            line-height: 1.4;
        }
        .menu-list a:hover, .menu-list a:focus { 
            background: #3b82f6; 
            color: white;
        }
        .menu-icon {
            font-size: 16px;
            min-width: 24px;
            text-align: center;
            flex-shrink: 0;
        }
        .menu-text {
            transition: opacity 0.2s;
        }
        
        /* HEADINGS KATEGORI MENU (SUDAH DIPERBAIKI) */
        .menu-header {
            background: #1a252f;
            color: #94a3b8;
            font-size: 11px;
            padding: 12px 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 10px;
            font-weight: 700;
            line-height: 1.5;
            display: block;
        }

        /* WRAPPER KONTEN UTAMA & HEADER ATAS */
        .main-wrapper {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
        }

        .top-header {
            height: 50px;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            padding: 0 15px;
            gap: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            z-index: 5;
        }

        .btn-toggle {
            background: #2c3e50;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
        }
        .btn-toggle:hover {
            background: #3b82f6;
        }

        .portal-title-header {
            font-size: 14px;
            font-weight: 700;
            color: #1e293b;
        }
        
        /* KONTEN KANAN (IFRAME) */
        .content { 
            flex-grow: 1; 
            background: #f4f4f9; 
            position: relative;
        }
        iframe { 
            width: 100%; 
            height: 100%; 
            border: none; 
            display: block;
        }

        /* -------------------------------------------------------------
           MODUS SHRINK / COLLAPSED (DESKTOP)
        ------------------------------------------------------------- */
        .sidebar.shrink {
            width: 70px;
        }
        .sidebar.shrink .logo-area h2 {
            font-size: 22px;
            justify-content: center;
        }
        .sidebar.shrink .logo-text,
        .sidebar.shrink .logo-sub,
        .sidebar.shrink .user-info,
        .sidebar.shrink .menu-text,
        .sidebar.shrink .menu-header {
            display: none !important;
        }
        .sidebar.shrink .user-card {
            justify-content: center;
            padding: 12px 0;
        }
        .sidebar.shrink .menu-list a {
            justify-content: center;
            padding: 12px 0;
        }

        /* -------------------------------------------------------------
           RESPONSIF MOBILE (< 768px)
        ------------------------------------------------------------- */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 0;
                bottom: 0;
                left: -270px;
                width: 270px !important;
                z-index: 1000;
            }
            .sidebar.mobile-open {
                left: 0;
            }
            .top-header {
                padding: 0 10px;
            }
            .portal-title-header {
                font-size: 13px;
            }
        }
    </style>
</head>
<body>

    <!-- OVERLAY LAYAR GELAP SAAT MOBILE MENU DIBUKA -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- SIDEBAR KIRI -->
    <div class="sidebar" id="sidebar">
        <div class="logo-area">
            <h2><span>🏥</span><span class="logo-text">Panel Terpadu</span></h2>
            <p class="logo-sub">SIMULASI MINI HOSPITAL</p>
        </div>

        <!-- BADGE PROFIL PETUGAS AKTIF -->
        <div class="user-card">
            <div class="user-avatar"><?php echo strtoupper(substr($nama_user, 0, 1)); ?></div>
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($nama_user); ?></div>
                <div class="user-role">● <?php echo htmlspecialchars($role_user); ?></div>
            </div>
        </div>
        
        <!-- DAFTAR MENU UTAMA -->
        <div class="menu-list">
            
            <div class="menu-header" style="margin-top: 0;">Front Office (TPP)</div>
            <a href="dasbor_petugas.php" target="frame_konten"><span class="menu-icon">📢</span><span class="menu-text">Dasbor Antrean</span></a>
            <a href="pendaftaran_baru.php" target="frame_konten"><span class="menu-icon">📝</span><span class="menu-text">Registrasi Pasien Baru</span></a>
            <a href="pendaftaran_lama.php" target="frame_konten"><span class="menu-icon">🔍</span><span class="menu-text">Pencarian Pasien Lama</span></a>
            <a href="kunjungan_aktif.php" target="frame_konten"><span class="menu-icon">🛑</span><span class="menu-text">Batal & Kunjungan Aktif</span></a>
            <a href="cetak_stiker.php" target="frame_konten"><span class="menu-icon">🏷️</span><span class="menu-text">Cetak Stiker Pasien</span></a>
            <a href="cetak_sep.php" target="frame_konten"><span class="menu-icon">📄</span><span class="menu-text">Cetak SEP BPJS</span></a>
            
            <div class="menu-header">Pelayanan Medis</div>
            <a href="dashboard_dokter.php" target="frame_konten"><span class="menu-icon">👨‍⚕️</span><span class="menu-text">E-Rekam Medis (CPPT)</span></a>
            <a href="riwayat_medis.php" target="frame_konten"><span class="menu-icon">📂</span><span class="menu-text">Riwayat & Edit CPPT</span></a>
            <a href="ruang_periksa_ri.php" target="frame_konten"><span class="menu-icon">🛏️</span><span class="menu-text">Pelayanan Rawat Inap (RI)</span></a>
            <a href="ruang_periksa_igd.php" target="frame_konten"><span class="menu-icon">⚡</span><span class="menu-text">Pelayanan Gawat Darurat (IGD)</span></a>
            <a href="admisi_ranap.php" target="frame_konten"><span class="menu-icon">🚪</span><span class="menu-text">Admisi & Transfer SPRI (RI)</span></a>
            
            <div class="menu-header">Rekam Medis & Koding</div>
            <a href="dashboard_coder_rj.php" target="frame_konten"><span class="menu-icon">🏷️</span><span class="menu-text">Coder Rawat Jalan (RJ)</span></a>
            <a href="dashboard_coder_ri.php" target="frame_konten"><span class="menu-icon">🏷️</span><span class="menu-text">Coder Rawat Inap (RI)</span></a>
            <a href="dashboard_coder_igd.php" target="frame_konten"><span class="menu-icon">🏷️</span><span class="menu-text">Coder IGD</span></a>
            <a href="indeks_rm.php" target="frame_konten"><span class="menu-icon">📑</span><span class="menu-text">Indeks Rekam Medis (Pelaporan)</span></a>
            <a href="general_consent.php" target="frame_konten"><span class="menu-icon">📝</span><span class="menu-text">Dokumen General Consent</span></a>

            <div class="menu-header">Penunjang Diagnostik</div>
            <a href="dasbor_lab.php" target="frame_konten"><span class="menu-icon">🔬</span><span class="menu-text">Instalasi Laboratorium</span></a>
			<a href="dasbor_radiologi.php" target="frame_konten">📷 Instalasi Radiologi</a>
			<!-- KATEGORI PENELITIAN & K3RS -->
<div style="background: #1a252f; color: #7f8c8d; font-size: 11px; padding: 8px 20px; text-transform: uppercase; margin-top: 10px;">
    Penelitian & K3RS
</div>
<a href="modul_kematian/ringkasan.php" target="frame_konten">
    📑 Monitoring Kematian
</a>

            <div class="menu-header">Instalasi Farmasi</div>
            <a href="dasbor_apotek.php" target="frame_konten"><span class="menu-icon">💊</span><span class="menu-text">Apotek & Resep (E-R)</span></a>

            <div class="menu-header">Kasir & Keuangan</div>
            <a href="kasir_billing.php" target="frame_konten"><span class="menu-icon">💵</span><span class="menu-text">Kasir & Pembayaran</span></a>
            <a href="invoice_dotmatrix.php" target="frame_konten"><span class="menu-icon">🖨️</span><span class="menu-text">Dot Matrix</span></a>
            <a href="invoice_thermal.php" target="frame_konten"><span class="menu-icon">🧾</span><span class="menu-text">Thermal Print</span></a>
            
            <div class="menu-header">Laporan & Audit</div>
			<a href="laporan_bor_los_toi.php" target="frame_konten">📊 Indikator Efisiensi RS (BOR/LOS/TOI)</a>
            <a href="riwayat_kunjungan_all.php" target="frame_konten"><span class="menu-icon">📊</span><span class="menu-text">Master Log Semua Kunjungan</span></a>
			

            <div class="menu-header">Administrator</div>
            <a href="admin.php" target="frame_konten"><span class="menu-icon">⚙️</span><span class="menu-text">Manajemen Master Data</span></a>
            <a href="kelola_user.php" target="frame_konten"><span class="menu-icon">👥</span><span class="menu-text">Manajemen User Akses</span></a>

            <div class="menu-header">Pintasan Publik</div>
            <a href="apm.php" target="_blank"><span class="menu-icon">🎫</span><span class="menu-text">Layar APM Kiosk</span></a>
            <a href="monitor_antrean.php" target="_blank"><span class="menu-icon">📺</span><span class="menu-text">Layar Monitor Antrean</span></a>

            <!-- Tombol Keluar Sistem -->
            <div class="menu-header" style="border-top: 1px solid #34495e; margin-top: 15px;">Sistem</div>
            <a href="logout.php" style="background: #e74c3c; color: white;"><span class="menu-icon">🚪</span><span class="menu-text">Keluar (Logout)</span></a>

        </div>
    </div>

    <!-- AREA KONTEN UTAMA (HEADER + IFRAME) -->
    <div class="main-wrapper">
        <header class="top-header">
            <button class="btn-toggle" id="btnToggleSidebar" title="Ciutkan/Tampilkan Sidebar">
                <span>☰</span>
            </button>
            <div class="portal-title-header">SIMRS Mini Hospital — Portal Terpadu</div>
        </header>

        <div class="content">
            <iframe name="frame_konten" src="dasbor_petugas.php"></iframe>
        </div>
    </div>

    <!-- JAVASCRIPT UNTUK FITUR SHRINK & MOBILE TOGGLE -->
    <script>
        const sidebar = document.getElementById('sidebar');
        const btnToggle = document.getElementById('btnToggleSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const menuLinks = document.querySelectorAll('.menu-list a');

        function toggleSidebar() {
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('mobile-open');
                overlay.classList.toggle('active');
            } else {
                sidebar.classList.toggle('shrink');
            }
        }

        btnToggle.addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar);

        menuLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768 && sidebar.classList.contains('mobile-open')) {
                    sidebar.classList.remove('mobile-open');
                    overlay.classList.remove('active');
                }
            });
        });

        // JS TIMER AUTO-LOGOUT CLIENT-SIDE (15 MENIT INAKTIF)
        let waktuTimeout = 15 * 60 * 1000;
        let timerWaktu;

        function resetTimer() {
            clearTimeout(timerWaktu);
            timerWaktu = setTimeout(function() {
                alert("Sesi Anda telah berakhir karena tidak ada aktivitas selama 15 menit.");
                window.location.href = "logout.php";
            }, waktuTimeout);
        }

        window.onload = resetTimer;
        document.onmousemove = resetTimer;
        document.onkeypress = resetTimer;
        document.onclick = resetTimer;
        document.onscroll = resetTimer;
    </script>

</body>
</html>