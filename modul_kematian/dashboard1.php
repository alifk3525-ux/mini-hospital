<?php
session_start();

// 1. KONEKSI KE DATABASE SIMRS
$path_koneksi = __DIR__ . '/../koneksi.php';
if (file_exists($path_koneksi)) {
    include $path_koneksi;
} else {
    include 'koneksi.php';
}

// =========================================================================
// 2. AUTO-SYNC: OTOMATIS TARIK DATA PASIEN MENINGGAL DARI SIMRS KE MODUL
// =========================================================================
if (isset($koneksi) && $koneksi) {
    @mysqli_query($koneksi, "
        INSERT INTO kasus_kematian_kerja (no_registrasi, no_rm, status_proses)
        SELECT k.no_registrasi, k.no_rm, 'Perlu Identifikasi'
        FROM kunjungan k 
        LEFT JOIN rekam_medis rm ON k.no_registrasi = rm.no_registrasi 
        WHERE (k.status_kunjungan = 'Selesai (Meninggal)' 
               OR k.tujuan_layanan LIKE '%Jenazah%' 
               OR rm.status_keluar LIKE '%Meninggal%')
          AND k.no_registrasi NOT IN (SELECT no_registrasi FROM kasus_kematian_kerja)
    ");
}

// =========================================================================
// 3. PENARIKAN METRIK STATISTIK UTAMA (5 CARDS ATAS)
// =========================================================================
$bln_ini  = date('Y-m');
$bln_lalu = date('Y-m', strtotime('-1 month'));

// Total Kasus
$q_total = (isset($koneksi) && $koneksi) ? @mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kasus_kematian_kerja") : false;
$total_kasus = ($q_total && $r = mysqli_fetch_assoc($q_total)) ? (int)$r['total'] : 0;

// Perbandingan Bulan Ini vs Bulan Lalu
$q_tot_ini = (isset($koneksi) && $koneksi) ? @mysqli_query($koneksi, "
    SELECT COUNT(*) as total FROM kasus_kematian_kerja mkk
    JOIN kunjungan k ON mkk.no_registrasi = k.no_registrasi
    WHERE DATE_FORMAT(COALESCE(k.tgl_keluar, k.tgl_masuk), '%Y-%m') = '$bln_ini'
") : false;
$tot_ini = ($q_tot_ini && $r = mysqli_fetch_assoc($q_tot_ini)) ? (int)$r['total'] : 0;

$q_tot_lalu = (isset($koneksi) && $koneksi) ? @mysqli_query($koneksi, "
    SELECT COUNT(*) as total FROM kasus_kematian_kerja mkk
    JOIN kunjungan k ON mkk.no_registrasi = k.no_registrasi
    WHERE DATE_FORMAT(COALESCE(k.tgl_keluar, k.tgl_masuk), '%Y-%m') = '$bln_lalu'
") : false;
$tot_lalu = ($q_tot_lalu && $r = mysqli_fetch_assoc($q_tot_lalu)) ? (int)$r['total'] : 0;
$pct_tot  = ($tot_lalu > 0) ? round((($tot_ini - $tot_lalu) / $tot_lalu) * 100) : 0;

// Perlu Identifikasi
$q_ident = (isset($koneksi) && $koneksi) ? @mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kasus_kematian_kerja WHERE status_proses = 'Perlu Identifikasi'") : false;
$perlu_identifikasi = ($q_ident && $r = mysqli_fetch_assoc($q_ident)) ? (int)$r['total'] : 0;

// Perlu Verifikasi
$q_ver = (isset($koneksi) && $koneksi) ? @mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kasus_kematian_kerja WHERE status_proses = 'Menunggu Verifikasi'") : false;
$perlu_verifikasi = ($q_ver && $r = mysqli_fetch_assoc($q_ver)) ? (int)$r['total'] : 0;

// Terkait Pekerjaan
$q_kerja = (isset($koneksi) && $koneksi) ? @mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kasus_kematian_kerja WHERE hasil_identifikasi_pekerjaan = 'Terindikasi Terkait Pekerjaan'") : false;
$terkait_pekerjaan = ($q_kerja && $r = mysqli_fetch_assoc($q_kerja)) ? (int)$r['total'] : 0;

// Selesai Coding
$q_code = (isset($koneksi) && $koneksi) ? @mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kasus_kematian_kerja WHERE status_coding = 'Selesai Coding'") : false;
$selesai_coding = ($q_code && $r = mysqli_fetch_assoc($q_code)) ? (int)$r['total'] : 0;

// Status Tambahan untuk Bar Chart
$q_dalam_kerja = (isset($koneksi) && $koneksi) ? @mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kasus_kematian_kerja WHERE status_proses = 'Sedang Diidentifikasi'") : false;
$dalam_proses = ($q_dalam_kerja && $r = mysqli_fetch_assoc($q_dalam_kerja)) ? (int)$r['total'] : 0;

$q_belum_tentu = (isset($koneksi) && $koneksi) ? @mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kasus_kematian_kerja WHERE hasil_identifikasi_pekerjaan = 'Belum Dapat Ditentukan'") : false;
$belum_tertentu = ($q_belum_tentu && $r = mysqli_fetch_assoc($q_belum_tentu)) ? (int)$r['total'] : 0;

// =========================================================================
// 4. PENARIKAN DATA GRAFIK BULANAN REAL (12 BULAN TERAKHIR)
// =========================================================================
$grafik_labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
$grafik_data   = array_fill(0, 12, 0);

if (isset($koneksi) && $koneksi) {
    $q_chart = @mysqli_query($koneksi, "
        SELECT MONTH(COALESCE(k.tgl_keluar, k.tgl_masuk)) as bulan, COUNT(*) as jumlah 
        FROM kasus_kematian_kerja mkk
        JOIN kunjungan k ON mkk.no_registrasi = k.no_registrasi
        WHERE YEAR(COALESCE(k.tgl_keluar, k.tgl_masuk)) = YEAR(CURRENT_DATE) 
        GROUP BY MONTH(COALESCE(k.tgl_keluar, k.tgl_masuk))
    ");
    if ($q_chart) {
        while ($r_c = mysqli_fetch_assoc($q_chart)) {
            $bln_idx = (int)$r_c['bulan'] - 1;
            if ($bln_idx >= 0 && $bln_idx < 12) {
                $grafik_data[$bln_idx] = (int)$r_c['jumlah'];
            }
        }
    }
}

// =========================================================================
// 5. PENARIKAN DATA TABEL KASUS TERBARU (JOIN PASIEN & KUNJUNGAN)
// =========================================================================
$q_tabel = (isset($koneksi) && $koneksi) ? @mysqli_query($koneksi, "
    SELECT 
        mkk.id_kasus,
        mkk.no_rm,
        mkk.no_registrasi,
        p.nama_lengkap AS nama_pasien,
        COALESCE(k.tgl_keluar, k.tgl_masuk) AS tgl_kematian,
        mkk.hasil_identifikasi_pekerjaan,
        mkk.status_proses,
        mkk.status_coding
    FROM kasus_kematian_kerja mkk
    JOIN pasien p ON mkk.no_rm = p.no_rm
    LEFT JOIN kunjungan k ON mkk.no_registrasi = k.no_registrasi
    ORDER BY mkk.id_kasus DESC 
    LIMIT 5
") : false;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Content</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { 
            background-color: #f8fafc; 
            color: #0f172a; 
            padding: 28px 32px; 
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* PAGE HEADER */
        .page-header { margin-bottom: 24px; padding-top: 4px; }
        .page-header h2 { font-size: 22px; font-weight: 800; color: #0f172a; }
        .page-header p { font-size: 13px; color: #64748b; margin-top: 4px; }

        /* 5 METRIC CARDS GRID */
        .metric-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .metric-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 18px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        .metric-info { display: flex; flex-direction: column; gap: 4px; }
        .metric-title { font-size: 12px; font-weight: 600; color: #64748b; }
        .metric-value { font-size: 24px; font-weight: 800; color: #0f172a; }
        .metric-sub { font-size: 11px; font-weight: 600; display: flex; align-items: center; gap: 4px; }
        .text-up { color: #16a34a; }
        .text-down { color: #dc2626; }

        .metric-icon-box {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .bg-icon-blue { background: #eff6ff; color: #2563eb; }
        .bg-icon-orange { background: #fff7ed; color: #ea580c; }
        .bg-icon-purple { background: #faf5ff; color: #9333ea; }
        .bg-icon-green-light { background: #f0fdf4; color: #16a34a; }
        .bg-icon-green-dark { background: #dcfce7; color: #15803d; }

        /* CHARTS GRID */
        .charts-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }

        .chart-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .chart-title { font-size: 14.5px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px; }

        .select-filter {
            padding: 5px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 11.5px;
            color: #475569;
            outline: none;
            background: #ffffff;
        }

        .chart-box {
            position: relative;
            height: 220px;
            width: 100%;
        }

        /* TABEL KASUS TERBARU */
        .table-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            margin-bottom: 20px;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .btn-link-all {
            color: #2563eb;
            text-decoration: none;
            font-size: 12.5px;
            font-weight: 600;
        }

        table { width: 100%; border-collapse: collapse; font-size: 12.5px; text-align: left; }
        th {
            background-color: #f8fafc;
            color: #64748b;
            font-weight: 700;
            padding: 10px 14px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 11px;
            text-transform: uppercase;
        }
        td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; color: #1e293b; vertical-align: middle; }
        tr:hover { background-color: #f8fafc; }

        /* BADGES STYLE */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 16px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-perlu { background: #fef3c7; color: #d97706; }
        .badge-terkonfirmasi { background: #dcfce7; color: #15803d; }
        .badge-verifikasi { background: #f3e8ff; color: #7e22ce; }
        .badge-belum { background: #fee2e2; color: #dc2626; }
        .badge-proses { background: #e0f2fe; color: #0369a1; }
        .badge-selesai { background: #dcfce7; color: #15803d; }

        .btn-view {
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 600;
            color: #2563eb;
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-view:hover { background: #2563eb; color: #ffffff; }

        .table-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 16px;
            font-size: 12px;
            color: #64748b;
        }

        .pagination { display: flex; gap: 4px; }
        .page-item {
            width: 26px;
            height: 26px;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11.5px;
            font-weight: 600;
            color: #334155;
            text-decoration: none;
            background: #ffffff;
        }
        .page-item.active { background: #2563eb; color: #ffffff; border-color: #2563eb; }
    </style>
</head>
<body>

    <!-- PAGE HEADER HEADER KONTEN -->
    <div class="page-header">
        <h2>Dashboard</h2>
        <p>Ringkasan dan monitoring kasus kematian, proses identifikasi, dan pengkodean external cause.</p>
    </div>

    <!-- 5 METRIC CARDS GRID -->
    <div class="metric-grid">
        <!-- CARD 1: TOTAL KASUS -->
        <div class="metric-card">
            <div class="metric-info">
                <span class="metric-title">Total Kasus</span>
                <span class="metric-value"><?= number_format($total_kasus) ?></span>
                <span class="metric-sub text-up">↑ <?= $pct_tot ?>% <span style="color:#94a3b8; font-weight:400;">dari bulan lalu</span></span>
            </div>
            <div class="metric-icon-box bg-icon-blue">📄</div>
        </div>

        <!-- CARD 2: PERLU IDENTIFIKASI -->
        <div class="metric-card">
            <div class="metric-info">
                <span class="metric-title">Perlu Identifikasi</span>
                <span class="metric-value"><?= number_format($perlu_identifikasi) ?></span>
                <span class="metric-sub text-up">↑ 6% <span style="color:#94a3b8; font-weight:400;">dari bulan lalu</span></span>
            </div>
            <div class="metric-icon-box bg-icon-orange">🔍</div>
        </div>

        <!-- CARD 3: PERLU VERIFIKASI -->
        <div class="metric-card">
            <div class="metric-info">
                <span class="metric-title">Perlu Verifikasi</span>
                <span class="metric-value"><?= number_format($perlu_verifikasi) ?></span>
                <span class="metric-sub text-down">↓ 20% <span style="color:#94a3b8; font-weight:400;">dari bulan lalu</span></span>
            </div>
            <div class="metric-icon-box bg-icon-purple">🛡️</div>
        </div>

        <!-- CARD 4: TERKAIT PEKERJAAN -->
        <div class="metric-card">
            <div class="metric-info">
                <span class="metric-title">Terkait Pekerjaan</span>
                <span class="metric-value"><?= number_format($terkait_pekerjaan) ?></span>
                <span class="metric-sub text-up">↑ 3% <span style="color:#94a3b8; font-weight:400;">dari bulan lalu</span></span>
            </div>
            <div class="metric-icon-box bg-icon-green-light">🔗</div>
        </div>

        <!-- CARD 5: SELESAI CODING -->
        <div class="metric-card">
            <div class="metric-info">
                <span class="metric-title">Selesai Coding</span>
                <span class="metric-value"><?= number_format($selesai_coding) ?></span>
                <span class="metric-sub text-up">↑ 18% <span style="color:#94a3b8; font-weight:400;">dari bulan lalu</span></span>
            </div>
            <div class="metric-icon-box bg-icon-green-dark">✅</div>
        </div>
    </div>

    <!-- CHARTS GRID (MIDDLE) -->
    <div class="charts-grid">
        <!-- LINE CHART: TREN KASUS KEMATIAN -->
        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-title">📈 Tren Kasus Kematian</div>
                <select class="select-filter"><option>12 Bulan Terakhir</option></select>
            </div>
            <div class="chart-box">
                <canvas id="lineChart"></canvas>
            </div>
        </div>

        <!-- BAR CHART: STATUS KASUS -->
        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-title">📊 Status Kasus</div>
                <select class="select-filter"><option>12 Bulan Terakhir</option></select>
            </div>
            <div class="chart-box">
                <canvas id="barChart"></canvas>
            </div>
        </div>
    </div>

    <!-- TABEL KASUS TERBARU (BOTTOM) -->
    <div class="table-card">
        <div class="table-header">
            <div class="chart-title">📋 Kasus Terbaru</div>
            <a href="daftar_kasus.php" class="btn-link-all">Lihat Semua ›</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">No.</th>
                    <th>No. RM</th>
                    <th>Nama Pasien</th>
                    <th>Tanggal Meninggal</th>
                    <th>Jenis Kasus</th>
                    <th>Status Identifikasi</th>
                    <th>Status Coding</th>
                    <th style="text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $count_tampil = 0;
                if ($q_tabel && mysqli_num_rows($q_tabel) > 0) {
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($q_tabel)) {
                        $count_tampil++;
                        $st_ident = $row['status_proses'] ?? 'Perlu Identifikasi';
                        $st_code  = $row['status_coding'] ?? 'Belum Coding';
                        $kat_kasus= ($row['hasil_identifikasi_pekerjaan'] == 'Terindikasi Terkait Pekerjaan') ? 'Trauma' : 'Non Trauma';
                        $tgl_m    = !empty($row['tgl_kematian']) ? date('d/m/Y', strtotime($row['tgl_kematian'])) : date('d/m/Y');
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><strong><?= htmlspecialchars($row['no_rm'] ?? '-') ?></strong></td>
                    <td><?= htmlspecialchars($row['nama_pasien'] ?? '-') ?></td>
                    <td><?= $tgl_m ?></td>
                    <td><?= htmlspecialchars($kat_kasus) ?></td>
                    <td>
                        <?php if ($st_ident == 'Perlu Identifikasi'): ?>
                            <span class="badge badge-perlu">Perlu Identifikasi</span>
                        <?php elseif ($st_ident == 'Menunggu Verifikasi'): ?>
                            <span class="badge badge-verifikasi">Perlu Verifikasi</span>
                        <?php else: ?>
                            <span class="badge badge-terkonfirmasi">Terkonfirmasi</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($st_code == 'Selesai Coding'): ?>
                            <span class="badge badge-selesai">Selesai</span>
                        <?php elseif ($st_code == 'Proses Coding'): ?>
                            <span class="badge badge-proses">Proses</span>
                        <?php else: ?>
                            <span class="badge badge-belum">Belum</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: center;">
                        <a href="penelusuran_kasus.php?id=<?= $row['id_kasus'] ?>" class="btn-view">👁️ Lihat</a>
                    </td>
                </tr>
                <?php
                    }
                } else {
                ?>
                <tr>
                    <td colspan="8" style="text-align: center; color: #64748b; padding: 25px;">
                        Belum ada data kasus kematian di database.
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>

        <div class="table-footer">
            <div>Menampilkan <?= $count_tampil > 0 ? "1-$count_tampil" : "0" ?> dari <?= number_format($total_kasus) ?> data</div>
            <div class="pagination">
                <a href="#" class="page-item">‹</a>
                <a href="#" class="page-item active">1</a>
                <a href="#" class="page-item">›</a>
            </div>
        </div>
    </div>

    <!-- SCRIPT INITIALIZE CHART.JS -->
    <script>
        // 1. Line Chart: Tren Kasus Kematian Real
        const lineCtx = document.getElementById('lineChart').getContext('2d');
        new Chart(lineCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($grafik_labels) ?>,
                datasets: [{
                    label: 'Jumlah Kasus',
                    data: <?= json_encode($grafik_data) ?>,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.08)',
                    borderWidth: 2,
                    pointBackgroundColor: '#2563eb',
                    pointRadius: 4,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { stepSize: 1 } },
                    x: { grid: { display: false } }
                }
            }
        });

        // 2. Bar Chart: Status Kasus Real
        const barCtx = document.getElementById('barChart').getContext('2d');
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: ['Perlu Identifikasi', 'Perlu Verifikasi', 'Dalam Pekerjaan', 'Belum Tertentu', 'Selesai Coding'],
                datasets: [{
                    data: [<?= $perlu_identifikasi ?>, <?= $perlu_verifikasi ?>, <?= $dalam_proses ?>, <?= $belum_tertentu ?>, <?= $selesai_coding ?>],
                    backgroundColor: ['#2563eb', '#9333ea', '#f59e0b', '#06b6d4', '#16a34a'],
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { stepSize: 1 } },
                    x: { grid: { display: false } }
                }
            }
        });
    </script>
</body>
</html>