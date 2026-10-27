<?php
include 'koneksi.php';

// Filter Bulan dan Tahun (Default: Bulan & Tahun Berjalan)
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');

// Hitung jumlah hari dalam bulan terpilih
$jumlah_hari = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);

// 1. Ambil Kapasitas Tempat Tidur Aktif (TT) - Mengabaikan IGD & Jenazah
$q_bed = mysqli_query($koneksi, "SELECT SUM(kapasitas) as total_tt FROM kamar_rawat WHERE nama_ruangan NOT LIKE '%IGD%' AND nama_ruangan NOT LIKE '%Jenazah%'");
$d_bed = mysqli_fetch_assoc($q_bed);
$TT = (int)$d_bed['total_tt']; // Total 42 Bed

// 2. Tarik Data Pasien Rawat Inap yang Sudah Pulang / Keluar pada Periode Terpilih
$tgl_awal  = "$tahun-$bulan-01 00:00:00";
$tgl_akhir = "$tahun-$bulan-$jumlah_hari 23:59:59";

$q_pasien_keluar = mysqli_query($koneksi, "SELECT k.*, p.nama_lengkap 
                    FROM kunjungan k 
                    JOIN pasien p ON k.no_rm = p.no_rm 
                    WHERE k.jenis_layanan = 'Rawat Inap' 
                      AND k.status_kunjungan != 'Aktif' 
                      AND k.tgl_keluar BETWEEN '$tgl_awal' AND '$tgl_akhir'
                    ORDER BY k.tgl_keluar DESC");

$total_pasien_keluar = mysqli_num_rows($q_pasien_keluar);

// Hitung Total Lama Dirawat (LD) & Hari Perawatan (HP)
$total_LD = 0;
$list_pasien = [];

while ($row = mysqli_fetch_assoc($q_pasien_keluar)) {
    $tgl_masuk  = new DateTime($row['tgl_masuk']);
    $tgl_keluar = new DateTime($row['tgl_keluar']);
    
    // Selisih hari (Lama Dirawat)
    $ld = $tgl_keluar->diff($tgl_masuk)->days;
    if ($ld == 0) { $ld = 1; } // Jika masuk & keluar di hari yang sama, dihitung 1 hari
    
    $row['lama_dirawat'] = $ld;
    $total_LD += $ld;
    $list_pasien[] = $row;
}

$total_HP = $total_LD; // Dalam pendekatan rekapitulasi pasien keluar

// 3. KALKULASI RUMUS BARBER-JOHNSON
// BOR = (Total HP / (TT * Jumlah Hari)) * 100%
$BOR = ($TT * $jumlah_hari > 0) ? round(($total_HP / ($TT * $jumlah_hari)) * 100, 2) : 0;

// ALOS = Total LD / Total Pasien Keluar
$ALOS = ($total_pasien_keluar > 0) ? round($total_LD / $total_pasien_keluar, 2) : 0;

// TOI = ((TT * Jumlah Hari) - Total HP) / Total Pasien Keluar
$TOI = ($total_pasien_keluar > 0) ? round((($TT * $jumlah_hari) - $total_HP) / $total_pasien_keluar, 2) : 0;

// BTO = Total Pasien Keluar / TT
$BTO = ($TT > 0) ? round($total_pasien_keluar / $TT, 2) : 0;

// Nama Bulan Bahasa Indonesia
$nama_bulan = [
    '01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni',
    '07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'
];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Indikator Efisiensi Bangsal (BOR, ALOS, TOI, BTO)</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f0f4f8; padding: 20px; color: #1e293b; margin: 0; }
        .wadah { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); max-width: 1100px; margin: 0 auto; }
        h2 { color: #b45309; margin-top: 0; border-bottom: 2px solid #fef3c7; padding-bottom: 8px; }
        
        /* Filter Form */
        .filter-card { background: #f8fafc; border: 1px solid #cbd5e1; padding: 15px; border-radius: 6px; margin-bottom: 20px; display: flex; align-items: center; gap: 15px; }
        select, button { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 14px; }
        button { background: #b45309; color: white; border: none; font-weight: bold; cursor: pointer; }
        button:hover { background: #92400e; }

        /* Card Indikator */
        .grid-indikator { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .card-ind { background: white; border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px; border-top: 5px solid #cbd5e1; box-shadow: 0 2px 6px rgba(0,0,0,0.05); }
        .card-ind.ideal { border-top-color: #10b981; }
        .card-ind.warning { border-top-color: #ef4444; }
        
        .ind-title { font-size: 12px; color: #64748b; font-weight: bold; text-transform: uppercase; }
        .ind-val { font-size: 28px; font-weight: bold; margin: 5px 0; }
        .ind-status { font-size: 11px; padding: 2px 6px; border-radius: 4px; font-weight: bold; display: inline-block; }
        .bg-green { background: #dcfce7; color: #15803d; }
        .bg-red { background: #fee2e2; color: #b91c1c; }

        /* Tabel Pasien Keluar */
        table { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 10px; }
        th, td { border: 1px solid #cbd5e1; padding: 10px; text-align: left; }
        th { background: #f1f5f9; color: #334155; }
        tr:nth-child(even) { background: #f8fafc; }
    </style>
</head>
<body>
    <div class="wadah">
        <h2>📊 Indikator Efisiensi Bangsal Rawat Inap (Barber-Johnson)</h2>
        <p style="font-size: 13px; color: #64748b; margin-top: -5px;">Pusat statistik evaluasi ketersediaan bed, lama rawat pasien, dan interval kekosongan kasur.</p>

        <!-- FORM FILTER PERIODE -->
        <form method="GET" class="filter-card">
            <label><b>Pilih Periode Laporan:</b></label>
            <select name="bulan">
                <?php foreach($nama_bulan as $k => $v): ?>
                    <option value="<?php echo $k; ?>" <?php echo ($k == $bulan) ? 'selected' : ''; ?>><?php echo $v; ?></option>
                <?php endforeach; ?>
            </select>
            <select name="tahun">
                <?php for($y = 2024; $y <= 2026; $y++): ?>
                    <option value="<?php echo $y; ?>" <?php echo ($y == $tahun) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>
            <button type="submit">🔍 Tampilkan Statistik</button>
        </form>

        <!-- KARTU HASIL INDIKATOR -->
        <div class="grid-indikator">
            <!-- BOR -->
            <?php $bor_ideal = ($BOR >= 60 && $BOR <= 85); ?>
            <div class="card-ind <?php echo $bor_ideal ? 'ideal' : 'warning'; ?>">
                <div class="ind-title">BOR (Bed Occupancy Rate)</div>
                <div class="ind-val" style="color: <?php echo $bor_ideal ? '#10b981' : '#ef4444'; ?>;"><?php echo $BOR; ?>%</div>
                <span class="ind-status <?php echo $bor_ideal ? 'bg-green' : 'bg-red'; ?>">
                    <?php echo $bor_ideal ? '✔ Ideal (60 - 85%)' : '⚠ Tidak Ideal (Standar 60-85%)'; ?>
                </span>
            </div>

            <!-- ALOS -->
            <?php $alos_ideal = ($ALOS >= 6 && $ALOS <= 9); ?>
            <div class="card-ind <?php echo $alos_ideal ? 'ideal' : 'warning'; ?>">
                <div class="ind-title">ALOS (Average Length of Stay)</div>
                <div class="ind-val" style="color: <?php echo $alos_ideal ? '#10b981' : '#ef4444'; ?>;"><?php echo $ALOS; ?> <small style="font-size:14px;">Hari</small></div>
                <span class="ind-status <?php echo $alos_ideal ? 'bg-green' : 'bg-red'; ?>">
                    <?php echo $alos_ideal ? '✔ Ideal (6 - 9 Hari)' : '⚠ Tidak Ideal (Standar 6-9 Hari)'; ?>
                </span>
            </div>

            <!-- TOI -->
            <?php $toi_ideal = ($TOI >= 1 && $TOI <= 3); ?>
            <div class="card-ind <?php echo $toi_ideal ? 'ideal' : 'warning'; ?>">
                <div class="ind-title">TOI (Turn Over Interval)</div>
                <div class="ind-val" style="color: <?php echo $toi_ideal ? '#10b981' : '#ef4444'; ?>;"><?php echo $TOI; ?> <small style="font-size:14px;">Hari</small></div>
                <span class="ind-status <?php echo $toi_ideal ? 'bg-green' : 'bg-red'; ?>">
                    <?php echo $toi_ideal ? '✔ Ideal (1 - 3 Hari)' : '⚠ Tidak Ideal (Standar 1-3 Hari)'; ?>
                </span>
            </div>

            <!-- BTO -->
            <?php $bto_ideal = ($BTO >= 3); // Disesuaikan periode bulanan ?>
            <div class="card-ind <?php echo $bto_ideal ? 'ideal' : 'warning'; ?>">
                <div class="ind-title">BTO (Bed Turn Over)</div>
                <div class="ind-val" style="color: <?php echo $bto_ideal ? '#10b981' : '#ef4444'; ?>;"><?php echo $BTO; ?> <small style="font-size:14px;">Kali/Bed</small></div>
                <span class="ind-status <?php echo $bto_ideal ? 'bg-green' : 'bg-red'; ?>">
                    <?php echo $bto_ideal ? '✔ Produktif' : '⚠ Produktivitas Rendah'; ?>
                </span>
            </div>
        </div>

        <!-- INFO RINGKASAN DATA -->
        <div style="background:#e0f2fe; border-left:4px solid #0284c7; padding:12px; border-radius:4px; font-size:13px; margin-bottom:20px;">
            <b>Variabel Perhitungan Periode <?php echo $nama_bulan[$bulan] . ' ' . $tahun; ?>:</b><br>
            • Capacity Tempat Tidur ($TT$): <b><?php echo $TT; ?> Bed Inap Aktif</b> (Eksklusi IGD & Jenazah)<br>
            • Total Hari Periode ($t$): <b><?php echo $jumlah_hari; ?> Hari</b> | Total Pasien Keluar: <b><?php echo $total_pasien_keluar; ?> Pasien</b> | Total Hari Perawatan (HP/LD): <b><?php echo $total_LD; ?> Hari</b>
        </div>

        <!-- TABEL RINCIAN PASIEN KELUAR -->
        <h4 style="color:#475569; margin-bottom:10px;">📜 Rincian Pasien Dipulangkan Periode Ini</h4>
        <table>
            <thead>
                <tr>
                    <th>No. Registrasi</th>
                    <th>Nama Pasien / RM</th>
                    <th>Kamar Bangsal</th>
                    <th>Tgl Masuk</th>
                    <th>Tgl Keluar</th>
                    <th>Lama Dirawat (LD)</th>
                    <th>Status Keluar</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($list_pasien) == 0): ?>
                    <tr><td colspan="7" style="text-align:center; padding:20px; color:#94a3b8;">Belum ada data pasien rawat inap yang dipulangkan pada periode bulan ini.</td></tr>
                <?php else: ?>
                    <?php foreach($list_pasien as $p): ?>
                    <tr>
                        <td><b><?php echo $p['no_registrasi']; ?></b></td>
                        <td><?php echo $p['nama_lengkap']; ?> (RM: <?php echo $p['no_rm']; ?>)</td>
                        <td><?php echo $p['tujuan_layanan']; ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($p['tgl_masuk'])); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($p['tgl_keluar'])); ?></td>
                        <td><b><?php echo $p['lama_dirawat']; ?> Hari</b></td>
                        <td><span style="background:#dcfce7; color:#166534; padding:3px 6px; border-radius:4px; font-weight:bold; font-size:11px;"><?php echo $p['status_kunjungan']; ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>