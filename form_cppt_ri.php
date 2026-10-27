<?php
include 'koneksi.php';
$reg = isset($_GET['reg']) ? $_GET['reg'] : '';

// Ambil data pasien dan tanggal masuk dari tabel kunjungan
$q = mysqli_query($koneksi, "SELECT k.*, p.nama_lengkap, p.jk FROM kunjungan k JOIN pasien p ON k.no_rm = p.no_rm WHERE k.no_registrasi = '$reg'");
$pasien = mysqli_fetch_assoc($q);

if (!$pasien) {
    echo "<script>alert('Data kunjungan rawat inap tidak ditemukan!'); window.location='ruang_periksa_ri.php';</script>";
    exit;
}

// Hitung Hari Rawat ke-N secara otomatis dari tgl_masuk
$tgl_masuk = strtotime($pasien['tgl_masuk']);
$sekarang = time();
$hari_ke = floor(($sekarang - $tgl_masuk) / (60 * 60 * 24)) + 1;
if ($hari_ke < 1) { $hari_ke = 1; }

// Proses Simpan CPPT Harian Rawat Inap
if(isset($_POST['simpan_cppt'])) {
    $s = mysqli_real_escape_string($koneksi, $_POST['subjektif']);
    $o = mysqli_real_escape_string($koneksi, $_POST['objektif']);
    $a = mysqli_real_escape_string($koneksi, $_POST['asesmen']);
    $p = mysqli_real_escape_string($koneksi, $_POST['planning']);
    $instruksi = mysqli_real_escape_string($koneksi, $_POST['instruksi']);
    $pemberi = mysqli_real_escape_string($koneksi, $_POST['pemberi_catatan']);
    $profesi = mysqli_real_escape_string($koneksi, $_POST['profesi']);
    $rm = $pasien['no_rm'];
    $dpjp = $pasien['dpjp'];

    mysqli_query($koneksi, "INSERT INTO cppt_rawat_inap (no_registrasi, no_rm, dpjp, pemberi_catatan, profesi, subjektif, objektif, asesmen, planning, instruksi_PО) 
              VALUES ('$reg', '$rm', '$dpjp', '$pemberi', '$profesi', '$s', '$o', '$a', '$p', '$instruksi')");
    
    echo "<script>alert('CPPT Hari ke-$hari_ke Berhasil Disimpan!'); window.location='form_cppt_ri.php?reg=$reg';</script>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>CPPT Rawat Inap Terintegrasi</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f4f8; padding: 20px; }
        .wadah { background: white; padding: 25px; border-radius: 8px; max-width: 950px; margin: 0 auto; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 10px; }
        label { display: block; font-weight: bold; margin: 10px 0 5px 0; font-size: 13px; color: #334155; }
        textarea, input, select { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 5px; box-sizing: border-box; font-size: 14px; }
        button { background: #d97706; color: white; border: none; padding: 12px; font-weight: bold; cursor: pointer; width: 100%; margin-top: 15px; border-radius: 5px; font-size: 15px; }
        button:hover { background: #b45309; }
        .info-box { background: #fef3c7; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 5px solid #d97706; }
        table.riwayat { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 13px; }
        table.riwayat th, table.riwayat td { border: 1px solid #cbd5e1; padding: 10px; text-align: left; vertical-align: top; }
        table.riwayat th { background: #f8fafc; color: #1e293b; }
        .badge-profesi { background: #e0f2fe; color: #0369a1; padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; display: inline-block; margin-top: 4px; }
    </style>
</head>
<body>
    <div class="wadah">
        <h3 style="margin-top: 0; color: #b45309;">🩺 CPPT & Perawatan Harian Rawat Inap</h3>
        
        <div class="info-box">
            <strong><?php echo $pasien['nama_lengkap']; ?> (<?php echo $pasien['jk']; ?>)</strong> | 
            Kamar: <strong><?php echo $pasien['tujuan_layanan']; ?></strong> | 
            No. Reg: <strong><?php echo $reg; ?></strong><br>
            <span style="color: #b45309; font-weight: bold; font-size: 15px;">📅 Status Perawatan: Hari Rawat ke-<?php echo $hari_ke; ?></span>
        </div>

        <!-- FORM INPUT CPPT BARU -->
        <form method="POST">
            <h4 style="color: #1e293b; border-bottom: 2px solid #cbd5e1; padding-bottom: 5px;">✍️ Input CPPT Hari ke-<?php echo $hari_ke; ?></h4>
            
            <div class="grid-2">
                <div>
                    <label>Nama Pemberi Catatan (Petugas/Dokter)</label>
                    <input type="text" name="pemberi_catatan" placeholder="Contoh: dr. Budi / Ns. Siska" required>
                </div>
                <div>
                    <label>Profesi / Tenaga Medis</label>
                    <select name="profesi" required>
                        <option value="">-- Pilih Profesi --</option>
                        <option value="Dokter DPJP">Dokter DPJP</option>
                        <option value="Dokter Anestesiolog">Dokter Anestesiolog</option>
                        <option value="Perawat / Bidan">Perawat / Bidan</option>
                        <option value="Apoteker / Farmasi">Apoteker / Farmasi</option>
                        <option value="Ahli Gizi">Ahli Gizi</option>
                    </select>
                </div>
            </div>

            <label>S - Subjektif (Keluhan Hari Ini)</label>
            <textarea name="subjektif" rows="3" required placeholder="Keluhan subjektif pasien hari ini..."></textarea>

            <label>O - Objektif (Pemeriksaan Fisik / Tanda Vital)</label>
            <textarea name="objektif" rows="3" required placeholder="TD, Nadi, Suhu, RR, Kondisi fisik..."></textarea>

            <label>A - Asesmen (Analisis / Perkembangan Kondisi)</label>
            <textarea name="asesmen" rows="3" required placeholder="Evaluasi klinis hari ini..."></textarea>

            <label>P - Planning (Rencana Perawatan / Terapi)</label>
            <textarea name="planning" rows="3" required placeholder="Rencana pengobatan atau pemeriksaan lanjutan..."></textarea>

            <label>Instruksi PО (Perintah Dokter / Pengobatan)</label>
            <textarea name="instruksi" rows="2" placeholder="Instruksi khusus perawat..."></textarea>

            <button type="submit" name="simpan_cppt">💾 Simpan Catatan CPPT Hari ke-<?php echo $hari_ke; ?></button>
			<a href="form_pulang_ri.php?reg=<?php echo $reg; ?>" style="display: block; background: #0f172a; color: white; text-align: center; padding: 12px; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 15px;">🏁 Proses Pasien Pulang & Diagnosis Akhir</a>
        </form>

        <hr style="margin: 40px 0; border: 0; border-top: 1px solid #cbd5e1;">

        <!-- TABEL RIWAYAT CPPT SEBELUMNYA -->
        <h4 style="color: #1e293b;">📚 Riwayat CPPT & Perkembangan Rawat Inap</h4>
        <table class="riwayat">
            <tr>
                <th style="width: 18%;">Waktu / Petugas</th>
                <th style="width: 25%;">SOAP (Subjektif & Objektif)</th>
                <th style="width: 27%;">Asesmen & Planning</th>
                <th style="width: 30%;">Instruksi PО & DPJP</th>
            </tr>
            <?php
            $q_riwayat = mysqli_query($koneksi, "SELECT * FROM cppt_rawat_inap WHERE no_registrasi = '$reg' ORDER BY tanggal_observasi DESC");
            if(mysqli_num_rows($q_riwayat) > 0) {
                while($row = mysqli_fetch_assoc($q_riwayat)) {
                    $profesi_label = isset($row['profesi']) && !empty($row['profesi']) ? $row['profesi'] : 'Tenaga Medis';
                    $pemberi = isset($row['pemberi_catatan']) && !empty($row['pemberi_catatan']) ? $row['pemberi_catatan'] : $row['dpjp'];
                    
                    echo "<tr>";
                    echo "<td>".$row['tanggal_observasi']."<br><br><b>Oleh:</b><br>".$pemberi."<br><span class='badge-profesi'>".$profesi_label."</span></td>";
                    echo "<td><b>S:</b> ".nl2br($row['subjektif'])."<br><br><b>O:</b> ".nl2br($row['objektif'])."</td>";
                    echo "<td><b>A:</b> ".nl2br($row['asesmen'])."<br><br><b>P:</b> ".nl2br($row['planning'])."</td>";
                    echo "<td>".nl2br($row['instruksi_PО'])."<br><br><small style='color:gray;'>DPJP: ".$row['dpjp']."</small></td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='4' style='text-align:center; color:gray;'>Belum ada catatan CPPT untuk pasien ini.</td></tr>";
            }
            ?>
        </table>
    </div>
</body>
</html>