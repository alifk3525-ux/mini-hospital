<?php
include 'koneksi.php';
$reg = isset($_GET['reg']) ? $_GET['reg'] : '';

// Tarik data kunjungan, pasien, dan diagnosa rekam medis jika ada
$query = mysqli_query($koneksi, "SELECT k.*, p.nama_lengkap, p.jk, p.tgl_lahir, p.alamat, p.pekerjaan, rm.diagnosa_utama, rm.icd_utama 
         FROM kunjungan k 
         JOIN pasien p ON k.no_rm = p.no_rm 
         LEFT JOIN rekam_medis rm ON k.no_registrasi = rm.no_registrasi 
         WHERE k.no_registrasi = '$reg'");
$data = mysqli_fetch_assoc($query);

if (!$data) {
    echo "<script>alert('Data kunjungan tidak ditemukan!'); window.history.back();</script>";
    exit;
}

// Hitung umur
$umur = date('Y') - date('Y', strtotime($data['tgl_lahir']));

// Mode cetak jika form disubmit
$mode_cetak = isset($_POST['cetak_surat']) ? $_POST['jenis_surat'] : '';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Penerbitan Surat Dokter Resmi</title>
    <style>
        @media screen {
            body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f0f4f8; padding: 20px; margin: 0; }
            .form-box { background: white; padding: 25px; border-radius: 8px; max-width: 800px; margin: 0 auto 30px auto; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
            h2 { color: #1e3a8a; margin-top: 0; border-bottom: 2px solid #cbd5e1; padding-bottom: 10px; }
            label { display: block; font-weight: bold; margin-top: 12px; font-size: 13px; color: #334155; }
            input, select, textarea { width: 100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 5px; box-sizing: border-box; font-size: 14px; margin-top: 4px; }
            .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
            .btn-generate { background: #2563eb; color: white; border: none; padding: 12px; border-radius: 5px; font-weight: bold; cursor: pointer; width: 100%; margin-top: 20px; font-size: 15px; }
            .btn-generate:hover { background: #1d4ed8; }

            /* Kotak Kertas Preview */
            .kertas { background: white; padding: 40px; max-width: 700px; margin: 0 auto; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
            .nav-aksi { max-width: 700px; margin: 0 auto 10px auto; display: flex; justify-content: space-between; }
            .btn-print { background: #10b981; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-weight: bold; }
        }

        @media print {
            body { font-family: 'Arial', sans-serif; margin: 0; padding: 0; background: white; font-size: 13px; }
            .form-box, .nav-aksi { display: none !important; }
            .kertas { width: 100%; max-width: 100%; box-shadow: none; padding: 10px; }
            @page { margin: 1.5cm; }
        }

        .kop-surat { text-align: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 20px; }
        .kop-surat h3, .kop-surat h4, .kop-surat p { margin: 2px 0; }
        .judul-surat { text-align: center; font-size: 16px; font-weight: bold; text-decoration: underline; margin-bottom: 5px; }
        .nomor-surat { text-align: center; font-size: 13px; margin-bottom: 25px; }
        .tabel-info { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .tabel-info td { padding: 5px 2px; vertical-align: top; }
        .tabel-info td:first-child { width: 28%; }
        .isi-paragraf { text-align: justify; line-height: 1.6; margin-bottom: 20px; }
        .ttd-box { margin-top: 40px; float: right; width: 250px; text-align: center; }
    </style>
    <script>
        function gantiJenisSurat() {
            var jenis = document.getElementById("jenis_surat").value;
            var boxSakit = document.getElementById("box-sakit");
            var boxKontrol = document.getElementById("box-kontrol");

            if (jenis === "sakit") {
                boxSakit.style.display = "block";
                boxKontrol.style.display = "none";
            } else {
                boxSakit.style.display = "none";
                boxKontrol.style.display = "block";
            }
        }
    </script>
</head>
<body>

    <!-- PANEL INPUT FORM DOKTER -->
    <div class="form-box">
        <h2>📄 Penerbitan Surat Dokter</h2>
        <p style="font-size: 13px; color: #64748b; margin-top: -5px;">
            Pasien: <strong><?php echo $data['nama_lengkap']; ?></strong> (RM: <?php echo $data['no_rm']; ?>) | DPJP: <b><?php echo $data['dpjp']; ?></b>
        </p>

        <form method="POST">
            <label>Pilih Jenis Surat Medis</label>
            <select name="jenis_surat" id="jenis_surat" onchange="gantiJenisSurat()" required>
                <option value="sakit" <?php echo ($mode_cetak == 'sakit' || empty($mode_cetak)) ? 'selected' : ''; ?>>Surat Keterangan Istirahat Sakit</option>
                <option value="kontrol" <?php echo ($mode_cetak == 'kontrol') ? 'selected' : ''; ?>>Surat Rencana Kontrol Lanjutan (SKDP)</option>
            </select>

            <!-- KHUSUS SURAT SAKIT -->
            <div id="box-sakit" style="display: <?php echo ($mode_cetak == 'kontrol') ? 'none' : 'block'; ?>;">
                <div class="grid-2">
                    <div>
                        <label>Lama Istirahat (Hari)</label>
                        <input type="number" name="lama_hari" min="1" max="14" value="3" placeholder="Contoh: 3">
                    </div>
                    <div>
                        <label>Mulai Tanggal</label>
                        <input type="date" name="tgl_mulai" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <label>Diagnosa / Catatan Medis (Opsional untuk Surat Sakit)</label>
                <input type="text" name="diagnosa_surat" value="<?php echo $data['diagnosa_utama'] ?: 'Febris / Kondisi Sakit Akut'; ?>" placeholder="Keterangan klinis singkat...">
            </div>

            <!-- KHUSUS SURAT KONTROL -->
            <div id="box-kontrol" style="display: <?php echo ($mode_cetak == 'kontrol') ? 'block' : 'none'; ?>;">
                <div class="grid-2">
                    <div>
                        <label>Tanggal Rencana Kontrol Kembali</label>
                        <input type="date" name="tgl_kontrol" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
                    </div>
                    <div>
                        <label>Poliklinik Tujuan Kontrol</label>
                        <input type="text" name="poli_tujuan" value="<?php echo $data['tujuan_layanan']; ?>">
                    </div>
                </div>
                <label>Alasan Kontrol / Evaluasi Terapi</label>
                <textarea name="alasan_kontrol" rows="2" placeholder="Contoh: Evaluasi hasil terapi antibiotik, pelepasan jahitan, cek laboratorium ulang..."></textarea>
            </div>

            <button type="submit" name="cetak_surat" class="btn-generate">🖨️ Buat Format & Pratinjau Surat</button>
        </form>
    </div>

    <!-- AREA DOKUMEN CETAK (MUNCUL SETELAH FORM DISUBMIT) -->
    <?php if (!empty($mode_cetak)): ?>
    <div class="nav-aksi">
        <button class="btn-print" onclick="window.print()">🖨️ Cetak Surat Sekarang</button>
    </div>

    <div class="kertas">
        <div class="kop-surat">
            <h3>RUMAH SAKIT SIMULASI MINI HOSPITAL</h3>
            <h4>INSTITUT TEKNOLOGI SAINS DAN KESEHATAN RS dr. SOEPRAOEN</h4>
            <p style="font-size: 11px;">Jl. S. Supriadi No. 22, Sukun, Kota Malang, Jawa Timur | Telp: (0341) 561386</p>
        </div>

        <?php if ($mode_cetak == 'sakit'): 
            $lama_hari = $_POST['lama_hari'];
            $tgl_mulai = $_POST['tgl_mulai'];
            $tgl_selesai = date('Y-m-d', strtotime($tgl_mulai . " + " . ($lama_hari - 1) . " days"));
            $no_surat_sakit = "SKS/" . date('Y/m/') . rand(1000, 9999);
        ?>
            <!-- FORMAT SURAT SAKIT -->
            <div class="judul-surat">SURAT KETERANGAN ISTIRAHAT SAKIT</div>
            <div class="nomor-surat">Nomor: <?php echo $no_surat_sakit; ?></div>

            <div class="isi-paragraf">
                Yang bertanda tangan di bawah ini, Dokter Rumah Sakit dr. Soepraoen Malang, menerangkan bahwa:
            </div>

            <table class="tabel-info">
                <tr><td>Nama Lengkap</td><td>: <strong><?php echo $data['nama_lengkap']; ?></strong></td></tr>
                <tr><td>Umur / Jenis Kelamin</td><td>: <?php echo $umur; ?> Tahun / <?php echo ($data['jk'] == 'L') ? 'Laki-Laki' : 'Perempuan'; ?></td></tr>
                <tr><td>Pekerjaan / Jabatan</td><td>: <?php echo $data['pekerjaan'] ?: '-'; ?></td></tr>
                <tr><td>Alamat</td><td>: <?php echo $data['alamat']; ?></td></tr>
                <tr><td>Nomor Rekam Medis</td><td>: <b><?php echo $data['no_rm']; ?></b></td></tr>
            </table>

            <div class="isi-paragraf">
                Berhubung dengan kondisi kesehatannya yang sedang sakit, pasien tersebut di atas perlu diberikan istirahat selama <strong><?php echo $lama_hari; ?> (<?php echo $lama_hari; ?>) hari</strong>, terhitung mulai tanggal <strong><?php echo date('d F Y', strtotime($tgl_mulai)); ?></strong> sampai dengan tanggal <strong><?php echo date('d F Y', strtotime($tgl_selesai)); ?></strong>.
            </div>

            <div class="isi-paragraf">
                Demikian surat keterangan ini diberikan dengan sebenarnya untuk dapat dipergunakan sebagaimana mestinya.
            </div>

        <?php else: 
            $tgl_kontrol = $_POST['tgl_kontrol'];
            $poli_tujuan = $_POST['poli_tujuan'];
            $alasan = $_POST['alasan_kontrol'] ?: 'Evaluasi lanjutan pasca perawatan';
            $no_skdp = "SKDP/" . date('Y/m/') . rand(1000, 9999);
        ?>
            <!-- FORMAT SURAT KONTROL (SKDP) -->
            <div class="judul-surat">SURAT RENCANA KONTROL (SKDP)</div>
            <div class="nomor-surat">Nomor: <?php echo $no_skdp; ?></div>

            <div class="isi-paragraf">
                Mohon dilakukan pemeriksaan dan penanganan kontrol lanjutan terhadap pasien:
            </div>

            <table class="tabel-info">
                <tr><td>Nomor Rekam Medis</td><td>: <b><?php echo $data['no_rm']; ?></b></td></tr>
                <tr><td>Nama Pasien</td><td>: <strong><?php echo $data['nama_lengkap']; ?></strong></td></tr>
                <tr><td>Diagnosa Awal</td><td>: <?php echo $data['diagnosa_utama'] ?: 'Dalam Perawatan Medis'; ?></td></tr>
                <tr><td>Rencana Tanggal Kontrol</td><td>: <strong><?php echo date('d F Y', strtotime($tgl_kontrol)); ?></strong></td></tr>
                <tr><td>Tujuan Klinik / Spesialis</td><td>: <strong><?php echo $poli_tujuan; ?></strong></td></tr>
                <tr><td>Dokter DPJP Kontrol</td><td>: <strong><?php echo $data['dpjp']; ?></strong></td></tr>
                <tr><td>Indikasi / Alasan Kontrol</td><td>: <?php echo $alasan; ?></td></tr>
            </table>

            <div class="isi-paragraf">
                Surat keterangan kontrol ini diterbitkan sebagai rujukan kontrol internal dan berlaku untuk 1 (satu) kali kunjungan kontrol poliklinik.
            </div>
        <?php endif; ?>

        <div class="ttd-box">
            Malang, <?php echo date('d F Y'); ?><br>
            Dokter Pemeriksa,<br><br><br><br><br>
            <strong>( <?php echo $data['dpjp']; ?> )</strong><br>
            <small>SIP / NIP Terlampir</small>
        </div>
        <div style="clear: both;"></div>
    </div>
    <?php endif; ?>

</body>
</html>