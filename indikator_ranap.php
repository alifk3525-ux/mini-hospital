<?php
include 'koneksi.php';

// 1. Tentukan Periode Laporan (Contoh: Bulan Ini)
$tgl_awal  = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-t');

// Hitung jumlah hari dalam periode (t)
$datetime1 = new DateTime($tgl_awal);
$datetime2 = new DateTime($tgl_akhir);
$jumlah_hari_periode = $datetime1->diff($datetime2)->days + 1;

// 2. Ambil Total Tempat Tidur Aktif (A)
$q_bed = mysqli_query($koneksi, "SELECT SUM(kapasitas) AS total_tt FROM kamar_rawat WHERE nama_ruangan NOT LIKE '%IGD%' AND nama_ruangan NOT LIKE '%Jenazah%'");
$d_bed = mysqli_fetch_assoc($q_bed);
$total_tt = $d_bed['total_tt'] ? $d_bed['total_tt'] : 1; // Cegah division by zero

// 3. Ambil Pasien Keluar (D) dan Total Lama Dirawat (LD)
$q_pasien_keluar = mysqli_query($koneksi, "SELECT 
    COUNT(*) as total_keluar,
    SUM(DATEDIFF(IFNULL(tgl_keluar, CURRENT_TIMESTAMP), tgl_masuk) + 1) as total_ld
    FROM kunjungan 
    WHERE jenis_layanan = 'Rawat Inap' 
    AND status_kunjungan = 'Selesai'
    AND DATE(tgl_masuk) BETWEEN '$tgl_awal' AND '$tgl_akhir'");

$d_keluar = mysqli_fetch_assoc($q_pasien_keluar);
$pasien_keluar = $d_keluar['total_keluar'];
$total_ld      = $d_keluar['total_ld'] ? $d_keluar['total_ld'] : 0;

// 4. Hitung Hari Perawatan (HP) Akumulatif Periode Ini
$q_hp = mysqli_query($koneksi, "SELECT SUM(DATEDIFF(LEAST(IFNULL(tgl_keluar, CURRENT_TIMESTAMP), '$tgl_akhir'), GREATEST(tgl_masuk, '$tgl_awal')) + 1) as total_hp
    FROM kunjungan 
    WHERE jenis_layanan = 'Rawat Inap' 
    AND tgl_masuk <= '$tgl_akhir' 
    AND (tgl_keluar >= '$tgl_awal' OR status_kunjungan = 'Aktif')");

$d_hp = mysqli_fetch_assoc($q_hp);
$hari_perawatan = $d_hp['total_hp'] ? $d_hp['total_hp'] : 0;

// 5. Kalkulasi Indikator Barber-Johnson
$bor  = ($total_tt * $jumlah_hari_periode > 0) ? ($hari_perawatan / ($total_tt * $jumlah_hari_periode)) * 100 : 0;
$alos = ($pasien_keluar > 0) ? $total_ld / $pasien_keluar : 0;
$toi  = ($pasien_keluar > 0) ? (($total_tt * $jumlah_hari_periode) - $hari_perawatan) / $pasien_keluar : 0;
$bto  = ($total_tt > 0) ? $pasien_keluar / $total_tt : 0;
?>

<!-- TAMPILAN DASHBOARD INDIKATOR -->
<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-top: 20px;">
    <div style="background: white; padding: 20px; border-radius: 8px; border-left: 5px solid #2563eb;">
        <small style="color: #64748b; font-weight: bold;">BOR (Bed Occupancy Rate)</small>
        <h2 style="margin: 5px 0; color: #1e293b;"><?php echo number_format($bor, 2); ?>%</h2>
        <small style="color: <?php echo ($bor >= 60 && $bor <= 85) ? 'green' : 'red'; ?>;">
            <?php echo ($bor >= 60 && $bor <= 85) ? 'Ideal (60-85%)' : 'Tidak Ideal'; ?>
        </small>
    </div>

    <div style="background: white; padding: 20px; border-radius: 8px; border-left: 5px solid #10b981;">
        <small style="color: #64748b; font-weight: bold;">ALOS (Length of Stay)</small>
        <h2 style="margin: 5px 0; color: #1e293b;"><?php echo number_format($alos, 1); ?> <span style="font-size: 14px;">Hari</span></h2>
        <small style="color: <?php echo ($alos >= 6 && $alos <= 9) ? 'green' : 'orange'; ?>;">
            <?php echo ($alos >= 6 && $alos <= 9) ? 'Ideal (6-9 Hari)' : 'Evaluasi Efisiensi'; ?>
        </small>
    </div>

    <div style="background: white; padding: 20px; border-radius: 8px; border-left: 5px solid #f59e0b;">
        <small style="color: #64748b; font-weight: bold;">TOI (Turn Over Interval)</small>
        <h2 style="margin: 5px 0; color: #1e293b;"><?php echo number_format($toi, 1); ?> <span style="font-size: 14px;">Hari</span></h2>
        <small style="color: <?php echo ($toi >= 1 && $toi <= 3) ? 'green' : 'red'; ?>;">
            <?php echo ($toi >= 1 && $toi <= 3) ? 'Ideal (1-3 Hari)' : 'Perlu Perhatian'; ?>
        </small>
    </div>

    <div style="background: white; padding: 20px; border-radius: 8px; border-left: 5px solid #8b5cf6;">
        <small style="color: #64748b; font-weight: bold;">BTO (Bed Turn Over)</small>
        <h2 style="margin: 5px 0; color: #1e293b;"><?php echo number_format($bto, 1); ?> <span style="font-size: 14px;">Kali</span></h2>
        <small style="color: gray;">Frekuensi Pemakaian Bed</small>
    </div>
</div>