<?php
include 'koneksi.php';

// 1. Menghitung total pasien rawat inap aktif secara real-time
$q_total_pasien = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kunjungan WHERE jenis_layanan = 'Rawat Inap' AND status_kunjungan = 'Aktif'");
$d_total = mysqli_fetch_assoc($q_total_pasien);
$pasien_aktif = (int)$d_total['total'];

// 2. Menghitung total kapasitas kasur bangsal (Mengecualikan IGD & Jenazah)
$q_kamar = mysqli_query($koneksi, "SELECT SUM(kapasitas) as total_bed FROM kamar_rawat WHERE nama_ruangan NOT LIKE '%IGD%' AND nama_ruangan NOT LIKE '%Jenazah%'");
$d_bed = mysqli_fetch_assoc($q_kamar);
$total_bed = (int)$d_bed['total_bed'];

// 3. Kalkulasi sinkronisasi otomatis
$bed_terisi = $pasien_aktif; // Diselaraskan langsung dengan jumlah pasien aktif
$sisa_bed   = max(0, $total_bed - $bed_terisi);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Monitoring Bangsal Rawat Inap - Mini Hospital</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f4f8; padding: 20px; margin: 0; color: #1e293b; }
        .header { background: #b45309; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h2 { margin: 0; color: #fef3c7; font-size: 22px; }
        
        /* Kartu Indikator Bangsal */
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); border-left: 5px solid #d97706; }
        .stat-title { font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: bold; }
        .stat-value { font-size: 24px; font-weight: bold; color: #1e293b; margin-top: 5px; }

        /* Tabel Pasien Inap */
        .table-container { background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 8px rgba(0,0,0,0.08); padding: 5px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 12px 14px; text-align: left; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
        th { background: #d97706; color: white; font-weight: 600; }
        tr:hover { background: #fffbeb; }

        .badge-kamar { background: #fef3c7; color: #92400e; padding: 4px 8px; border-radius: 4px; font-weight: bold; display: inline-block; }
        .badge-hari { background: #fee2e2; color: #dc2626; padding: 3px 6px; border-radius: 4px; font-weight: bold; font-size: 11px; margin-top: 3px; display: inline-block; }
        .badge-bayar { background: #e0f2fe; color: #0369a1; padding: 3px 6px; border-radius: 4px; font-size: 11px; font-weight: bold; display: inline-block; margin-top: 3px; }
        
        /* CSS Tombol Aksi Medis */
        .btn-aksi { display: block; text-decoration: none; padding: 6px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; text-align: center; margin-bottom: 4px; color: white; transition: 0.2s; }
        .btn-cppt { background: #2563eb; }
        .btn-cppt:hover { background: #1d4ed8; }
        .btn-skrining { background: #0284c7; }
        .btn-skrining:hover { background: #0369a1; }
        .btn-consent { background: #dc2626; }
        .btn-consent:hover { background: #b91c1c; }
        .btn-surat { background: #475569; }
        .btn-surat:hover { background: #334155; }
        .btn-mutasi { background: #d97706; }
        .btn-mutasi:hover { background: #b45309; }
        .btn-pulang { background: #059669; }
        .btn-pulang:hover { background: #047857; }
    </style>
</head>
<body>

    <div class="header">
        <div>
            <h2>🛏️ Dasbor Pemantauan Bangsal Rawat Inap</h2>
            <p style="margin: 5px 0 0 0; font-size: 13px; color: #fde68a;">Pantau pasien aktif menginap, riwayat hari rawat, dan proses pemulangan pasien.</p>
        </div>
    </div>

    <!-- KARTU INDIKATOR BANGSAL -->
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-title">Pasien Menginap Aktif</div>
            <div class="stat-value"><?php echo $pasien_aktif; ?> <span style="font-size: 14px; font-weight: normal; color: #64748b;">Orang</span></div>
        </div>
        <div class="stat-card" style="border-left-color: #2563eb;">
            <div class="stat-title">Kasur Bangsal Terpakai</div>
            <div class="stat-value"><?php echo $bed_terisi; ?> <span style="font-size: 14px; font-weight: normal; color: #64748b;">/ <?php echo $total_bed; ?> Bed</span></div>
        </div>
        <div class="stat-card" style="border-left-color: #10b981;">
            <div class="stat-title">Sisa Kasur Tersedia</div>
            <div class="stat-value"><?php echo $sisa_bed; ?> <span style="font-size: 14px; font-weight: normal; color: #64748b;">Bed Kosong</span></div>
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>No. Registrasi</th>
                    <th>Pasien & No. RM</th>
                    <th>Kamar Bangsal</th>
                    <th>DPJP & Penjamin</th>
                    <th>Tgl Masuk & Lama Rawat</th>
                    <th>Alasan / Keterangan</th>
                    <th style="text-align: center; width: 175px;">Aksi Medis</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = mysqli_query($koneksi, "SELECT k.*, p.nama_lengkap, p.jk, p.tgl_lahir 
                                                 FROM kunjungan k 
                                                 JOIN pasien p ON k.no_rm = p.no_rm 
                                                 WHERE k.jenis_layanan = 'Rawat Inap' 
                                                 AND k.status_kunjungan = 'Aktif' 
                                                 ORDER BY k.tgl_masuk DESC");
                
                if(! $query || mysqli_num_rows($query) == 0) {
                    echo "<tr><td colspan='7' style='text-align:center; padding: 30px; color: #64748b;'>Tidak ada pasien rawat inap aktif saat ini. Pastikan pasien sudah ditransfer melalui Admisi SPRI.</td></tr>";
                } else {
                    while($d = mysqli_fetch_assoc($query)) {
                        // Kalkulator Hari Rawat Otomatis
                        $tgl_masuk = new DateTime($d['tgl_masuk']);
                        $hari_ini  = new DateTime();
                        $selisih   = $hari_ini->diff($tgl_masuk);
                        $hari_ke   = $selisih->days + 1;

                        // Hitung Umur
                        $umur = date('Y') - date('Y', strtotime($d['tgl_lahir']));
                ?>
                <tr>
                    <td>
                        <strong style="color: #b45309;"><?php echo $d['no_registrasi']; ?></strong>
                    </td>
                    <td>
                        <strong><?php echo strtoupper($d['nama_lengkap']); ?></strong> (<?php echo $d['jk']; ?>)<br>
                        <small style="color: #64748b;">RM: <?php echo $d['no_rm']; ?> | <?php echo $umur; ?> Th</small>
                    </td>
                    <td>
                        <span class="badge-kamar">🛏️ <?php echo $d['tujuan_layanan']; ?></span>
                    </td>
                    <td>
                        <strong><?php echo $d['dpjp']; ?></strong><br>
                        <span class="badge-bayar"><?php echo $d['cara_bayar']; ?></span>
                    </td>
                    <td>
                        <?php echo date('d-m-Y H:i', strtotime($d['tgl_masuk'])); ?><br>
                        <span class="badge-hari">Hari Rawat ke-<?php echo $hari_ke; ?></span>
                    </td>
                    <td>
                        <small><i>"<?php echo htmlspecialchars($d['keluhan_utama'] ?? '-'); ?>"</i></small>
                    </td>
                    <td style="text-align: center;">
                        <a href="form_cppt_ri.php?reg=<?php echo $d['no_registrasi']; ?>" class="btn-aksi btn-cppt">🩺 Visite & CPPT</a>
                        <a href="skrining_awal.php?reg=<?php echo $d['no_registrasi']; ?>" target="_blank" class="btn-aksi btn-skrining">📋 Skrining Masuk</a>
                        <a href="informed_consent.php?reg=<?php echo $d['no_registrasi']; ?>" target="_blank" class="btn-aksi btn-consent">✍️ Informed Consent</a>
                        <a href="surat_dokter.php?reg=<?php echo $d['no_registrasi']; ?>" target="_blank" class="btn-aksi btn-surat">📄 Surat Medis</a>
                        <a href="mutasi_kamar.php?reg=<?php echo $d['no_registrasi']; ?>" class="btn-aksi btn-mutasi">🔄 Mutasi Kamar</a>
                        <a href="form_pulang_ri.php?reg=<?php echo $d['no_registrasi']; ?>" class="btn-aksi btn-pulang">🏁 Pulangkan Pasien</a>
                    </td>
                </tr>
                <?php 
                    } 
                } 
                ?>
            </tbody>
        </table>
    </div>

</body>
</html>