<?php
include 'koneksi.php';

$reg = isset($_GET['reg']) ? mysqli_real_escape_string($koneksi, $_GET['reg']) : '';

// 1. Tarik data identitas pasien, kunjungan, dan data rekam medis
$q_pasien = mysqli_query($koneksi, "SELECT k.*, p.nama_lengkap, p.jk, p.tgl_lahir, p.alamat, 
            rm.subjektif, rm.objektif, rm.asesmen, rm.plan_obat, rm.plan_non_obat,
            rm.diagnosa_utama, rm.icd_utama, 
            rm.diagnosa_sekunder_1, rm.icd_sekunder_1,
            rm.diagnosa_sekunder_2, rm.icd_sekunder_2,
            rm.diagnosa_sekunder_3, rm.icd_sekunder_3,
            rm.diagnosa_sekunder_4, rm.icd_sekunder_4,
            rm.status_keluar
            FROM kunjungan k 
            JOIN pasien p ON k.no_rm = p.no_rm 
            LEFT JOIN rekam_medis rm ON k.no_registrasi = rm.no_registrasi 
            WHERE k.no_registrasi = '$reg'");
$pasien = mysqli_fetch_assoc($q_pasien);

if (!$pasien) {
    echo "<script>alert('Data kunjungan pasien tidak ditemukan!'); window.location='ruang_periksa_ri.php';</script>";
    exit;
}

// 2. Cek apakah resume medis final sudah pernah disimpan sebelumnya
$q_resume = mysqli_query($koneksi, "SELECT * FROM resume_medis WHERE no_registrasi = '$reg'");
$resume = mysqli_fetch_assoc($q_resume);
$sudah_ada = $resume ? true : false;

// 3. Tarik catatan CPPT bangsal terakhir jika kolom SOAP rekam medis utama kosong
$q_cppt = mysqli_query($koneksi, "SELECT * FROM cppt_rawat_inap WHERE no_registrasi = '$reg' ORDER BY tanggal_observasi DESC LIMIT 1");
$cppt_terakhir = mysqli_fetch_assoc($q_cppt);

$auto_subjektif = !empty($pasien['subjektif']) ? $pasien['subjektif'] : ($cppt_terakhir ? $cppt_terakhir['subjektif'] : $pasien['keluhan_utama']);// Cek jika keluhan berisi teks transfer SPRI, lacak keluhan asli pasien
$keluhan_tampil = $pasien['keluhan_utama'];
if (strpos($keluhan_tampil, 'Transfer via SPRI') !== false) {
    // Ambil indikasi klinis asli dari tabel SPRI
    $q_spri_asal = mysqli_query($koneksi, "SELECT diagnosa_indikasi, catatan_khusus FROM spri WHERE no_registrasi_asal = '{$pasien['no_registrasi']}' OR no_rm = '{$pasien['no_rm']}' ORDER BY id_spri DESC LIMIT 1");
    if ($d_spri = mysqli_fetch_assoc($q_spri_asal)) {
        $keluhan_tampil = $d_spri['diagnosa_indikasi'] . (!empty($d_spri['catatan_khusus']) ? " - " . $d_spri['catatan_khusus'] : "");
    } else {
        $keluhan_tampil = "Kondisi memerlukan observasi dan perawatan intensif rawat inap.";
    }
}

$auto_subjektif = !empty($pasien['subjektif']) ? $pasien['subjektif'] : ($cppt_terakhir ? $cppt_terakhir['subjektif'] : $keluhan_tampil);
$auto_objektif  = !empty($pasien['objektif']) ? $pasien['objektif'] : ($cppt_terakhir ? $cppt_terakhir['objektif'] : 'Keadaan umum stabil, tanda vital dalam batas normal saat keluar rumah sakit.');
$auto_terapi    = !empty($pasien['plan_obat']) ? $pasien['plan_obat'] : ($cppt_terakhir ? ($cppt_terakhir['planning'] . " | " . $cppt_terakhir['instruksi_PО']) : 'Terapi cairan infus & medikamentosa bangsal');
$auto_objektif  = !empty($pasien['objektif']) ? $pasien['objektif'] : ($cppt_terakhir ? $cppt_terakhir['objektif'] : 'Pemeriksaan fisik dalam batas stabil saat dipulangkan.');
$auto_terapi    = !empty($pasien['plan_obat']) ? $pasien['plan_obat'] : ($cppt_terakhir ? ($cppt_terakhir['planning'] . " | " . $cppt_terakhir['instruksi_PО']) : '-');

// 4. Tarik otomatis hasil tes laboratorium jika ada
$ringkasan_lab = '';
$q_lab = mysqli_query($koneksi, "SELECT h.* FROM hasil_lab_detail h 
         JOIN order_lab o ON h.no_order = o.no_order 
         WHERE o.no_registrasi = '$reg' AND h.hasil != '-'");
if (mysqli_num_rows($q_lab) > 0) {
    $items = [];
    while ($lb = mysqli_fetch_assoc($q_lab)) {
        $items[] = $lb['nama_parameter'] . ": " . $lb['hasil'] . " " . $lb['satuan'] . " (" . $lb['status_nilai'] . ")";
    }
    $ringkasan_lab = implode("; ", $items);
} else {
    $ringkasan_lab = "Pemeriksaan laboratorium dalam batas rujukan / tidak dilakukan.";
}

// 5. Hitung hari rawat
$tgl_masuk = new DateTime($pasien['tgl_masuk']);
$hari_ini  = new DateTime();
$selisih   = $hari_ini->diff($tgl_masuk);
$lama_hari = max(1, $selisih->days + 1);

// 6. Rangkum diagnosa sekunder dari form pulang
$sekunder_teks = '';
$sekunder_icd  = '';
for ($i = 1; $i <= 4; $i++) {
    if (!empty($pasien['diagnosa_sekunder_' . $i])) {
        $sekunder_teks .= $pasien['diagnosa_sekunder_' . $i] . "; ";
        $sekunder_icd  .= $pasien['icd_sekunder_' . $i] . "; ";
    }
}

// 7. Simpan Resume Final
if (isset($_POST['simpan_resume'])) {
    $no_rm = $pasien['no_rm'];
    $tgl_keluar = mysqli_real_escape_string($koneksi, $_POST['tgl_keluar']);
    $lama_rawat = (int)$_POST['lama_rawat'];
    $alasan = mysqli_real_escape_string($koneksi, $_POST['alasan_masuk']);
    $riwayat = mysqli_real_escape_string($koneksi, $_POST['riwayat_penyakit']);
    $fisik = mysqli_real_escape_string($koneksi, $_POST['pemeriksaan_fisik']);
    $penunjang = mysqli_real_escape_string($koneksi, $_POST['penunjang_lab_radiologi']);
    $tindakan = mysqli_real_escape_string($koneksi, $_POST['tindakan_prosedur']);
    $du = mysqli_real_escape_string($koneksi, $_POST['diag_utama']);
    $iu = mysqli_real_escape_string($koneksi, $_POST['icd_utama']);
    $ds = mysqli_real_escape_string($koneksi, $_POST['diag_sekunder']);
    $is = mysqli_real_escape_string($koneksi, $_POST['icd_sekunder']);
    $terapi = mysqli_real_escape_string($koneksi, $_POST['terapi_selama_rawat']);
    $obat_plg = mysqli_real_escape_string($koneksi, $_POST['obat_pulang']);
    $kondisi = mysqli_real_escape_string($koneksi, $_POST['kondisi_keluar']);
    $cara = mysqli_real_escape_string($koneksi, $_POST['cara_keluar']);
    $tgl_kontrol = !empty($_POST['tgl_kontrol']) ? "'" . mysqli_real_escape_string($koneksi, $_POST['tgl_kontrol']) . "'" : "NULL";
    $edukasi = mysqli_real_escape_string($koneksi, $_POST['instruksi_edukasi']);
    $dpjp = mysqli_real_escape_string($koneksi, $_POST['dpjp']);

    if ($sudah_ada) {
        $sql = "UPDATE resume_medis SET 
                tgl_keluar='$tgl_keluar', lama_rawat='$lama_rawat', alasan_masuk='$alasan',
                riwayat_penyakit='$riwayat', pemeriksaan_fisik='$fisik', penunjang_lab_radiologi='$penunjang',
                tindakan_prosedur='$tindakan', diag_utama='$du', icd_utama='$iu',
                diag_sekunder='$ds', icd_sekunder='$is', terapi_selama_rawat='$terapi',
                obat_pulang='$obat_plg', kondisi_keluar='$kondisi', cara_keluar='$cara',
                tgl_kontrol=$tgl_kontrol, instruksi_edukasi='$edukasi', dpjp='$dpjp'
                WHERE no_registrasi='$reg'";
    } else {
        $sql = "INSERT INTO resume_medis 
                (no_registrasi, no_rm, tgl_keluar, lama_rawat, alasan_masuk, riwayat_penyakit, pemeriksaan_fisik, penunjang_lab_radiologi, tindakan_prosedur, diag_utama, icd_utama, diag_sekunder, icd_sekunder, terapi_selama_rawat, obat_pulang, kondisi_keluar, cara_keluar, tgl_kontrol, instruksi_edukasi, dpjp)
                VALUES 
                ('$reg', '$no_rm', '$tgl_keluar', '$lama_rawat', '$alasan', '$riwayat', '$fisik', '$penunjang', '$tindakan', '$du', '$iu', '$ds', '$is', '$terapi', '$obat_plg', '$kondisi', '$cara', $tgl_kontrol, '$edukasi', '$dpjp')";
    }

    if (mysqli_query($koneksi, $sql)) {
        echo "<script>alert('Resume Medis Pasien Pulang Berhasil Disimpan & Dikunci!'); window.location='resume_medis.php?reg=$reg';</script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Resume Medis Pasien Pulang - Mini Hospital</title>
    <style>
        @media screen {
            body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f0f4f8; padding: 20px; color: #1e293b; margin: 0; }
            .wadah { background: white; padding: 25px; border-radius: 8px; max-width: 950px; margin: 0 auto; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
            .header-bar { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #0f766e; padding-bottom: 10px; margin-bottom: 20px; }
            .card-pasien { background: #f0fdfa; border-left: 5px solid #0f766e; padding: 12px 15px; border-radius: 6px; margin-bottom: 20px; display: flex; justify-content: space-between; font-size: 13px; }
            .section-title { font-size: 13px; font-weight: bold; color: #0f766e; margin: 18px 0 8px 0; border-bottom: 1px solid #ccfbf1; padding-bottom: 4px; text-transform: uppercase; }
            .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 12px; }
            .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 12px; }
            label { display: block; font-size: 12px; font-weight: bold; color: #475569; margin-bottom: 4px; }
            input, select, textarea { width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 5px; box-sizing: border-box; font-size: 13px; }
            textarea { resize: vertical; }
            .btn-simpan { background: #0f766e; color: white; border: none; padding: 12px 20px; border-radius: 6px; font-size: 15px; font-weight: bold; cursor: pointer; width: 100%; margin-top: 15px; }
            .btn-simpan:hover { background: #115e59; }
            .btn-cetak { background: #0284c7; color: white; border: none; padding: 10px 18px; border-radius: 6px; font-weight: bold; cursor: pointer; }
            .btn-cetak:hover { background: #0369a1; }
            .btn-kembali { background: #64748b; color: white; text-decoration: none; padding: 10px 16px; border-radius: 6px; font-weight: bold; font-size: 13px; margin-right: 8px; }
            .btn-kembali:hover { background: #475569; }
            #print-area { display: none; }
        }

        @media print {
            body { font-family: Arial, sans-serif; font-size: 11px; color: black; background: white; margin: 0; padding: 0; }
            .no-print { display: none !important; }
            #print-area { display: block !important; }
            .kop { text-align: center; border-bottom: 2px solid black; padding-bottom: 6px; margin-bottom: 12px; }
            .kop h2, .kop p { margin: 2px; }
            .judul-lembar { text-align: center; font-size: 13px; font-weight: bold; text-decoration: underline; margin-bottom: 12px; text-transform: uppercase; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
            th, td { border: 1px solid black; padding: 5px 8px; vertical-align: top; text-align: left; }
            th { background: #f1f5f9; }
            .table-polos, .table-polos td { border: none !important; padding: 3px 4px; }
            .ttd-box { width: 100%; margin-top: 25px; border-collapse: collapse; text-align: center; }
            .ttd-box td { border: none; width: 50%; vertical-align: bottom; height: 75px; }
            @page { margin: 1.2cm; size: A4 portrait; }
        }
    </style>
</head>
<body>

    <div class="wadah no-print">
        <div class="header-bar">
            <div>
                <h2 style="margin: 0; color: #0f766e;">📄 Resume Medis Pasien Pulang (Discharge Summary)</h2>
                <small style="color: #64748b;">Rincian otomatis ditarik dari data kepulangan, riwayat visite CPPT, dan penunjang medis</small>
            </div>
            <div>
                <a href="ruang_periksa_ri.php" class="btn-kembali">➔ Ke Bangsal</a>
                <?php if($sudah_ada): ?>
                    <button class="btn-cetak" onclick="window.print()">🖨️ Cetak Lembar Resume</button>
                <?php endif; ?>
            </div>
        </div>

        <div class="card-pasien">
            <div>
                <strong style="font-size: 14px; color: #0f766e;"><?php echo $pasien['nama_lengkap']; ?> (<?php echo $pasien['jk']; ?>)</strong><br>
                No. RM: <b><?php echo $pasien['no_rm']; ?></b> | Reg: <b><?php echo $pasien['no_registrasi']; ?></b> | Ruangan: <?php echo $pasien['tujuan_layanan']; ?>
            </div>
            <div style="text-align: right;">
                DPJP: <b><?php echo $pasien['dpjp']; ?></b><br>
                Penjamin: <?php echo $pasien['cara_bayar']; ?> | Masuk: <?php echo date('d-m-Y H:i', strtotime($pasien['tgl_masuk'])); ?>
            </div>
        </div>

        <form method="POST">
            <div class="section-title">1. Waktu Rawat & Riwayat Penyakit</div>
            <div class="grid-3">
                <div>
                    <label>Tanggal & Jam Keluar</label>
                    <input type="datetime-local" name="tgl_keluar" value="<?php echo $sudah_ada ? date('Y-m-d\TH:i', strtotime($resume['tgl_keluar'])) : date('Y-m-d\TH:i'); ?>" required>
                </div>
                <div>
                    <label>Lama Hari Rawat (Hari)</label>
                    <input type="number" name="lama_rawat" value="<?php echo $sudah_ada ? $resume['lama_rawat'] : $lama_hari; ?>" required>
                </div>
                <div>
                    <label>Dokter Penanggung Jawab (DPJP)</label>
                    <input type="text" name="dpjp" value="<?php echo $sudah_ada ? $resume['dpjp'] : $pasien['dpjp']; ?>" required>
                </div>
            </div>

            <div class="grid-2">
                <div>
                    <label>Alasan Masuk Dirawat / Keluhan Utama</label>
                    <textarea name="alasan_masuk" rows="3" required><?php echo $sudah_ada ? $resume['alasan_masuk'] : $pasien['keluhan_utama']; ?></textarea>
                </div>
                <div>
                    <label>Ringkasan Perjalanan Klinis / Anamnesis Terakhir</label>
                    <textarea name="riwayat_penyakit" rows="3" required><?php echo $sudah_ada ? $resume['riwayat_penyakit'] : $auto_subjektif; ?></textarea>
                </div>
            </div>

            <div class="section-title">2. Pemeriksaan Fisik & Temuan Penunjang Diagnostik</div>
            <div class="grid-3">
                <div>
                    <label>Pemeriksaan Fisik Terpenting Saat Pulang</label>
                    <textarea name="pemeriksaan_fisik" rows="3" required><?php echo $sudah_ada ? $resume['pemeriksaan_fisik'] : $auto_objektif; ?></textarea>
                </div>
                <div>
                    <label>Hasil Pemeriksaan Penunjang (Lab / Radiologi)</label>
                    <textarea name="penunjang_lab_radiologi" rows="3"><?php echo $sudah_ada ? $resume['penunjang_lab_radiologi'] : $ringkasan_lab; ?></textarea>
                </div>
                <div>
                    <label>Tindakan Medis / Operasi Selama Rawat</label>
                    <textarea name="tindakan_prosedur" rows="3"><?php echo $sudah_ada ? $resume['tindakan_prosedur'] : ($pasien['plan_non_obat'] ?: 'Terapi medikamentosa dan observasi bangsal'); ?></textarea>
                </div>
            </div>

            <div class="section-title">3. Diagnosis Akhir Kepulangan (Otomatis dari Form Pulang)</div>
            <div class="grid-2">
                <div>
                    <label style="color: #dc2626;">Diagnosis Akhir Utama</label>
                    <input type="text" name="diag_utama" value="<?php echo $sudah_ada ? $resume['diag_utama'] : $pasien['diagnosa_utama']; ?>" required>
                </div>
                <div>
                    <label style="color: #dc2626;">Kode ICD-10 Utama</label>
                    <input type="text" name="icd_utama" value="<?php echo $sudah_ada ? $resume['icd_utama'] : $pasien['icd_utama']; ?>" placeholder="Contoh: A09">
                </div>
            </div>

            <div class="grid-2">
                <div>
                    <label>Diagnosis Sekunder / Komorbiditas</label>
                    <input type="text" name="diag_sekunder" value="<?php echo $sudah_ada ? $resume['diag_sekunder'] : trim($sekunder_teks, "; "); ?>" placeholder="Penyakit penyerta...">
                </div>
                <div>
                    <label>Kode ICD-10 Sekunder</label>
                    <input type="text" name="icd_sekunder" value="<?php echo $sudah_ada ? $resume['icd_sekunder'] : trim($sekunder_icd, "; "); ?>" placeholder="Contoh: E11; I10">
                </div>
            </div>

            <div class="section-title">4. Terapi Selama Rawat & Resep Obat Pulang</div>
            <div class="grid-2">
                <div>
                    <label>Terapi Selama Rawat Inap</label>
                    <textarea name="terapi_selama_rawat" rows="3"><?php echo $sudah_ada ? $resume['terapi_selama_rawat'] : $auto_terapi; ?></textarea>
                </div>
                <div>
                    <label style="color: #0f766e;">Obat yang Dibawa Pulang (Resep Pulang)</label>
                    <textarea name="obat_pulang" rows="3" placeholder="Rincian obat oral untuk di rumah..."><?php echo $sudah_ada ? $resume['obat_pulang'] : $pasien['plan_obat']; ?></textarea>
                </div>
            </div>

            <div class="section-title">5. Status Keluar, Jadwal Kontrol, & Edukasi</div>
            <div class="grid-3">
                <div>
                    <label>Kondisi Saat Keluar</label>
                    <select name="kondisi_keluar">
                        <?php 
                        $kk = $sudah_ada ? $resume['kondisi_keluar'] : ($pasien['status_keluar'] ?: 'Membaik');
                        foreach(['Sembuh', 'Membaik', 'Dirujuk ke RS Lain', 'Pulang Atas Permintaan Sendiri (APS)', 'Meninggal'] as $opt) {
                            $sel = ($kk == $opt) ? 'selected' : '';
                            echo "<option value='$opt' $sel>$opt</option>";
                        }
                        ?>
                    </select>
                </div>
                <div>
                    <label>Cara Pasien Keluar</label>
                    <select name="cara_keluar">
                        <?php 
                        $ck = $sudah_ada ? $resume['cara_keluar'] : 'Persetujuan Dokter / Izin Pulang';
                        foreach(['Persetujuan Dokter / Izin Pulang', 'Pulang Atas Permintaan Sendiri (APS)', 'Dirujuk ke Faskes Lain', 'Melarikan Diri'] as $opt) {
                            $sel = ($ck == $opt) ? 'selected' : '';
                            echo "<option value='$opt' $sel>$opt</option>";
                        }
                        ?>
                    </select>
                </div>
                <div>
                    <label>Tanggal Rencana Kontrol Ulang Poli</label>
                    <input type="date" name="tgl_kontrol" value="<?php echo ($sudah_ada && !empty($resume['tgl_kontrol'])) ? $resume['tgl_kontrol'] : date('Y-m-d', strtotime('+7 days')); ?>">
                </div>
            </div>

            <div style="margin-bottom: 15px;">
                <label>Instruksi Perawatan di Rumah / Edukasi Pasien</label>
                <textarea name="instruksi_edukasi" rows="2"><?php echo $sudah_ada ? $resume['instruksi_edukasi'] : 'Istirahat cukup, minum obat teratur sesuai aturan pakai, dan kontrol ke poliklinik sesuai jadwal.'; ?></textarea>
            </div>

            <button type="submit" name="simpan_resume" class="btn-simpan">💾 Kunci Resume Medis & Siapkan Berkas Cetak</button>
        </form>
    </div>

    <!-- FORMAT CETAK KERTAS A4 RESMI -->
    <?php if($sudah_ada && $pasien): ?>
    <div id="print-area">
        <div class="kop">
            <h2>RUMAH SAKIT SIMULASI MINI HOSPITAL</h2>
            <p>Jl. Kesdam V Brawijaya No. 1, Malang, Jawa Timur | Telp: (0341) 1234567</p>
            <div class="judul-lembar">RINGKASAN PULANG PASIEN (DISCHARGE SUMMARY / RESUME MEDIS)</div>
        </div>

        <table class="table-polos" style="margin-bottom: 8px;">
            <tr>
                <td style="width: 18%;"><b>No. Rekam Medis</b></td><td style="width: 32%;">: <b><?php echo $pasien['no_rm']; ?></b></td>
                <td style="width: 18%;"><b>No. Registrasi</b></td><td style="width: 32%;">: <?php echo $pasien['no_registrasi']; ?></td>
            </tr>
            <tr>
                <td><b>Nama Pasien</b></td><td>: <?php echo $pasien['nama_lengkap']; ?> (<?php echo $pasien['jk']; ?>)</td>
                <td><b>Tanggal Masuk</b></td><td>: <?php echo date('d-m-Y H:i', strtotime($pasien['tgl_masuk'])); ?></td>
            </tr>
            <tr>
                <td><b>Tanggal Lahir / Usia</b></td><td>: <?php echo date('d-m-Y', strtotime($pasien['tgl_lahir'])); ?> (<?php echo (date('Y') - date('Y', strtotime($pasien['tgl_lahir']))); ?> Th)</td>
                <td><b>Tanggal Keluar</b></td><td>: <?php echo date('d-m-Y H:i', strtotime($resume['tgl_keluar'])); ?></td>
            </tr>
            <tr>
                <td><b>Ruangan Terakhir</b></td><td>: <?php echo $pasien['tujuan_layanan']; ?></td>
                <td><b>Lama Rawat</b></td><td>: <?php echo $resume['lama_rawat']; ?> Hari</td>
            </tr>
            <tr>
                <td><b>Dokter DPJP</b></td><td>: <?php echo $resume['dpjp']; ?></td>
                <td><b>Penjamin</b></td><td>: <?php echo $pasien['cara_bayar']; ?></td>
            </tr>
        </table>

        <table>
            <tr>
                <th style="width: 30%;">I. ALASAN MASUK / KELUHAN</th>
                <td><?php echo nl2br($resume['alasan_masuk']); ?></td>
            </tr>
            <tr>
                <th>II. PERJALANAN PENYAKIT</th>
                <td><?php echo nl2br($resume['riwayat_penyakit']); ?></td>
            </tr>
            <tr>
                <th>III. PEMERIKSAAN FISIK</th>
                <td><?php echo nl2br($resume['pemeriksaan_fisik']); ?></td>
            </tr>
            <tr>
                <th>IV. PENUNJANG (LAB/RADIOLOGI)</th>
                <td><?php echo nl2br($resume['penunjang_lab_radiologi']); ?></td>
            </tr>
            <tr>
                <th>V. TINDAKAN / OPERASI</th>
                <td><?php echo nl2br($resume['tindakan_prosedur']); ?></td>
            </tr>
            <tr>
                <th>VI. DIAGNOSIS AKHIR KEPULANGAN</th>
                <td>
                    <b>Utama:</b> <?php echo $resume['diag_utama']; ?> <i>(Kode ICD-10: <?php echo $resume['icd_utama'] ?: '-'; ?>)</i><br>
                    <b>Sekunder:</b> <?php echo $resume['diag_sekunder'] ?: '-'; ?> <i>(Kode ICD-10: <?php echo $resume['icd_sekunder'] ?: '-'; ?>)</i>
                </td>
            </tr>
            <tr>
                <th>VII. TERAPI SELAMA RAWAT</th>
                <td><?php echo nl2br($resume['terapi_selama_rawat']); ?></td>
            </tr>
            <tr>
                <th>VIII. OBAT DIBAWA PULANG</th>
                <td style="font-weight: bold;"><?php echo nl2br($resume['obat_pulang']); ?></td>
            </tr>
            <tr>
                <th>IX. KONDISI & CARA KELUAR</th>
                <td>Kondisi: <b><?php echo $resume['kondisi_keluar']; ?></b> | Cara Keluar: <b><?php echo $resume['cara_keluar']; ?></b></td>
            </tr>
            <tr>
                <th>X. JADWAL KONTROL & EDUKASI</th>
                <td>
                    Jadwal Kontrol: <b><?php echo (!empty($resume['tgl_kontrol']) && $resume['tgl_kontrol'] != '0000-00-00') ? date('d-m-Y', strtotime($resume['tgl_kontrol'])) : 'Sesuai Kebutuhan'; ?></b><br>
                    Instruksi: <i><?php echo nl2br($resume['instruksi_edukasi']); ?></i>
                </td>
            </tr>
        </table>

        <table class="ttd-box">
            <tr>
                <td>
                    Pasien / Keluarga
                    <br><br><br><br>
                    <b>( <?php echo $pasien['nama_lengkap']; ?> )</b>
                </td>
                <td>
                    Malang, <?php echo date('d F Y', strtotime($resume['tgl_keluar'])); ?><br>
                    Dokter DPJP
                    <br><br><br><br>
                    <b>( <?php echo $resume['dpjp']; ?> )</b>
                </td>
            </tr>
        </table>
    </div>
    <?php endif; ?>

</body>
</html>