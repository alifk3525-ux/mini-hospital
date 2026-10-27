<?php
include 'koneksi.php';
$reg = isset($_GET['reg']) ? mysqli_real_escape_string($koneksi, $_GET['reg']) : '';

// Tarik data pendaftaran, pasien, dan rekam medis
$q_pasien = mysqli_query($koneksi, "SELECT k.*, p.nama_lengkap, p.jk, p.tgl_lahir, p.alamat, p.nama_pj, p.telp_pj, p.hub_pj, rm.diagnosa_utama 
            FROM kunjungan k 
            JOIN pasien p ON k.no_rm = p.no_rm 
            LEFT JOIN rekam_medis rm ON k.no_registrasi = rm.no_registrasi 
            WHERE k.no_registrasi = '$reg'");
$pasien = mysqli_fetch_assoc($q_pasien);

// Ambil riwayat informed consent bila sudah pernah dibuat
$q_consent = mysqli_query($koneksi, "SELECT * FROM informed_consent WHERE no_registrasi = '$reg' ORDER BY id_consent DESC LIMIT 1");
$consent = mysqli_fetch_assoc($q_consent);

// Hitung usia pasien
$usia_pasien = 0;
if ($pasien) {
    $usia_pasien = date('Y') - date('Y', strtotime($pasien['tgl_lahir']));
}

// Proses Simpan Data
if (isset($_POST['simpan_consent'])) {
    $no_rm = $pasien['no_rm'];
    $dokter = mysqli_real_escape_string($koneksi, $_POST['dokter_pelaksana']);
    $pemberi_info = mysqli_real_escape_string($koneksi, $_POST['pemberi_informasi']);
    $tindakan = mysqli_real_escape_string($koneksi, $_POST['nama_tindakan']);
    $diagnosis = mysqli_real_escape_string($koneksi, $_POST['diagnosis_kerja']);
    $dasar_diag = mysqli_real_escape_string($koneksi, $_POST['dasar_diagnosis']);
    $indikasi = mysqli_real_escape_string($koneksi, $_POST['indikasi_tindakan']);
    $tata_cara = mysqli_real_escape_string($koneksi, $_POST['tata_cara_tindakan']);
    $tujuan = mysqli_real_escape_string($koneksi, $_POST['tujuan_tindakan']);
    $risiko = mysqli_real_escape_string($koneksi, $_POST['risiko_komplikasi']);
    $prognosis = mysqli_real_escape_string($koneksi, $_POST['prognosis_tindakan']);
    $alternatif = mysqli_real_escape_string($koneksi, $_POST['alternatif_risiko']);
    $jenis = mysqli_real_escape_string($koneksi, $_POST['jenis_pernyataan']);
    
    // Data Pernyataan Pihak Pasien / Wali
    $nama_pj = mysqli_real_escape_string($koneksi, $_POST['nama_pj']);
    $umur_pj = (int)$_POST['umur_pj'];
    $jk_pj = mysqli_real_escape_string($koneksi, $_POST['jk_pj']);
    $hub_pj = mysqli_real_escape_string($koneksi, $_POST['hubungan_pj']);
    $alamat_pj = mysqli_real_escape_string($koneksi, $_POST['alamat_pj']);
    $telp_pj = mysqli_real_escape_string($koneksi, $_POST['telp_pj']);
    $saksi_keluarga = mysqli_real_escape_string($koneksi, $_POST['saksi_keluarga']);
    $saksi_nakes = mysqli_real_escape_string($koneksi, $_POST['saksi_nakes']);

    $sql = "INSERT INTO informed_consent 
            (no_registrasi, no_rm, dokter_pelaksana, pemberi_informasi, nama_tindakan, diagnosis_kerja, dasar_diagnosis, indikasi_tindakan, tata_cara_tindakan, tujuan_tindakan, risiko_komplikasi, prognosis_tindakan, alternatif_risiko, jenis_pernyataan, nama_pj, umur_pj, jk_pj, hubungan_pj, alamat_pj, telp_pj, saksi_keluarga, saksi_nakes)
            VALUES ('$reg', '$no_rm', '$dokter', '$pemberi_info', '$tindakan', '$diagnosis', '$dasar_diag', '$indikasi', '$tata_cara', '$tujuan', '$risiko', '$prognosis', '$alternatif', '$jenis', '$nama_pj', '$umur_pj', '$jk_pj', '$hub_pj', '$alamat_pj', '$telp_pj', '$saksi_keluarga', '$saksi_nakes')";
    
    if (mysqli_query($koneksi, $sql)) {
        echo "<script>alert('Formulir Informed Consent/Refusal Berhasil Disimpan!'); window.location='informed_consent.php?reg=$reg';</script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Informed Consent - Mini Hospital</title>
    <style>
        @media screen {
            body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f0f4f8; padding: 20px; color: #1e293b; }
            .container { background: white; max-width: 900px; margin: 0 auto; border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); padding: 30px; }
            .card-pasien { background: #f8fafc; border-left: 5px solid #2563eb; padding: 15px; border-radius: 6px; margin-bottom: 20px; display: flex; justify-content: space-between; }
            .section-title { font-size: 14px; font-weight: bold; color: #1e3a8a; margin: 20px 0 10px 0; border-bottom: 2px solid #e2e8f0; padding-bottom: 5px; }
            .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 12px; }
            .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 12px; }
            label { display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 4px; }
            input, select, textarea { width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 5px; box-sizing: border-box; font-size: 13px; }
            textarea { resize: vertical; }
            .btn-simpan { background: #2563eb; color: white; border: none; padding: 12px; font-weight: bold; border-radius: 6px; cursor: pointer; width: 100%; font-size: 15px; margin-top: 15px; }
            .btn-simpan:hover { background: #1d4ed8; }
            .btn-cetak { background: #059669; color: white; border: none; padding: 10px 18px; border-radius: 6px; font-weight: bold; cursor: pointer; }
            #print-area { display: none; }
        }

        @media print {
            body { font-family: Arial, sans-serif; font-size: 11px; color: black; background: white; padding: 0; }
            .no-print { display: none !important; }
            #print-area { display: block !important; }
            .kop { text-align: center; border-bottom: 2px solid black; padding-bottom: 8px; margin-bottom: 12px; }
            .kop h3, .kop p { margin: 2px; }
            .judul-dokumen { text-align: center; font-size: 13px; font-weight: bold; text-decoration: underline; margin-bottom: 10px; text-transform: uppercase; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
            th, td { border: 1px solid black; padding: 4px 6px; vertical-align: top; }
            th { background: #f1f5f9; }
            .table-polos, .table-polos td { border: none !important; padding: 2px 4px; }
            .ttd-box { width: 100%; margin-top: 25px; border-collapse: collapse; text-align: center; }
            .ttd-box td { border: none; width: 25%; vertical-align: bottom; height: 70px; }
            @page { margin: 1.2cm; }
        }
    </style>
</head>
<body>

    <!-- ================= LAYAR FORM INPUT ================= -->
    <div class="container no-print">
        <?php if(!$pasien): ?>
            <h3 style="color: #ef4444;">⚠️ Data Registrasi Tidak Ditemukan</h3>
            <p>Buka halaman ini dengan parameter nomor registrasi (Contoh: <code>informed_consent.php?reg=REG-XXXX</code>).</p>
        <?php else: ?>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h2 style="margin: 0; color: #0f172a;">📄 Persetujuan / Penolakan Tindakan Kedokteran</h2>
                <?php if($consent): ?>
                    <button class="btn-cetak" onclick="window.print()">🖨️ Cetak Dokumen Resmi</button>
                <?php endif; ?>
            </div>

            <div class="card-pasien">
                <div>
                    <strong style="font-size: 15px; color: #1e3a8a;"><?php echo $pasien['nama_lengkap']; ?> (<?php echo $pasien['jk']; ?>)</strong><br>
                    <span style="font-size: 13px; color: #475569;">No. RM: <b><?php echo $pasien['no_rm']; ?></b> | Usia: <?php echo $usia_pasien; ?> Tahun | Layanan: <?php echo $pasien['jenis_layanan']; ?> (<?php echo $pasien['tujuan_layanan']; ?>)</span>
                </div>
                <div style="text-align: right;">
                    <span style="font-size: 13px; font-weight: bold; color: #2563eb;"><?php echo $reg; ?></span><br>
                    <small>DPJP: <?php echo $pasien['dpjp']; ?></small>
                </div>
            </div>

            <form method="POST">
                <div class="section-title">1. Penanggung Jawab Medis & Jenis Pernyataan</div>
                <div class="grid-3">
                    <div>
                        <label>Dokter Pelaksana Tindakan</label>
                        <input type="text" name="dokter_pelaksana" value="<?php echo $consent ? $consent['dokter_pelaksana'] : $pasien['dpjp']; ?>" required>
                    </div>
                    <div>
                        <label>Pemberi Informasi / Edukasi</label>
                        <input type="text" name="pemberi_informasi" value="<?php echo $consent ? $consent['pemberi_informasi'] : $pasien['dpjp']; ?>" required>
                    </div>
                    <div>
                        <label style="color: #dc2626; font-weight: bold;">Jenis Pernyataan Pasien</label>
                        <select name="jenis_pernyataan" required style="font-weight: bold;">
                            <option value="PERSETUJUAN" <?php echo ($consent && $consent['jenis_pernyataan'] == 'PERSETUJUAN') ? 'selected' : ''; ?>>PERSETUJUAN TINDAKAN (INFORMED CONSENT)</option>
                            <option value="PENOLAKAN" <?php echo ($consent && $consent['jenis_pernyataan'] == 'PENOLAKAN') ? 'selected' : ''; ?>>PENOLAKAN TINDAKAN (INFORMED REFUSAL)</option>
                        </select>
                    </div>
                </div>

                <div class="section-title">2. Rincian Informasi & Edukasi Tindakan Kedokteran</div>
                <div class="grid-2">
                    <div>
                        <label>Tindakan Medis yang Direncanakan</label>
                        <input type="text" name="nama_tindakan" value="<?php echo $consent ? $consent['nama_tindakan'] : ''; ?>" placeholder="Contoh: Debridement Luka & Penjahitan (Hecting)" required>
                    </div>
                    <div>
                        <label>Diagnosis Kerja / Kondisi Klinis</label>
                        <input type="text" name="diagnosis_kerja" value="<?php echo $consent ? $consent['diagnosis_kerja'] : ($pasien['diagnosa_utama'] ?: $pasien['keluhan_utama']); ?>" required>
                    </div>
                </div>

                <div class="grid-2">
                    <div>
                        <label>Dasar Diagnosis</label>
                        <input type="text" name="dasar_diagnosis" value="<?php echo $consent ? $consent['dasar_diagnosis'] : 'Anamnesis & Pemeriksaan Fisik'; ?>" required>
                    </div>
                    <div>
                        <label>Indikasi Tindakan</label>
                        <input type="text" name="indikasi_tindakan" value="<?php echo $consent ? $consent['indikasi_tindakan'] : ''; ?>" placeholder="Indikasi klinis dilakukannya tindakan..." required>
                    </div>
                </div>

                <div class="grid-2">
                    <div>
                        <label>Tata Cara Pelaksanaan Tindakan</label>
                        <textarea name="tata_cara_tindakan" rows="2" placeholder="Prosedur pembiusan lokal/umum, insisi, reposisi..." required><?php echo $consent ? $consent['tata_cara_tindakan'] : ''; ?></textarea>
                    </div>
                    <div>
                        <label>Tujuan Tindakan Medis</label>
                        <textarea name="tujuan_tindakan" rows="2" placeholder="Mencegah infeksi, hemostasis, memulihkan fungsi..." required><?php echo $consent ? $consent['tujuan_tindakan'] : ''; ?></textarea>
                    </div>
                </div>

                <div class="grid-3">
                    <div>
                        <label>Risiko & Komplikasi yang Mungkin Terjadi</label>
                        <textarea name="risiko_komplikasi" rows="2" placeholder="Perdarahan, alergi obat bius, nyeri, infeksi sekunder..." required><?php echo $consent ? $consent['risiko_komplikasi'] : ''; ?></textarea>
                    </div>
                    <div>
                        <label>Prognosis Pasca Tindakan</label>
                        <textarea name="prognosis_tindakan" rows="2" placeholder="Dubia ad bonam / bonam / perbaikan klinis..." required><?php echo $consent ? $consent['prognosis_tindakan'] : 'Dubia ad Bonam'; ?></textarea>
                    </div>
                    <div>
                        <label>Alternatif Terapi & Risikonya</label>
                        <textarea name="alternatif_risiko" rows="2" placeholder="Terapi medikamentosa konservatif..."><?php echo $consent ? $consent['alternatif_risiko'] : 'Terapi Konservatif / Medikamentosa Oral'; ?></textarea>
                    </div>
                </div>

                <div class="section-title">3. Pihak Penandatangan (Penerima Informasi / Pasien / Keluarga)</div>
                <div class="grid-3">
                    <div>
                        <label>Nama Penandatangan</label>
                        <input type="text" name="nama_pj" value="<?php echo $consent ? $consent['nama_pj'] : ($usia_pasien >= 18 ? $pasien['nama_lengkap'] : $pasien['nama_pj']); ?>" required>
                    </div>
                    <div>
                        <label>Hubungan dengan Pasien</label>
                        <select name="hubungan_pj">
                            <?php 
                            $default_hub = ($usia_pasien >= 18) ? 'Diri Sendiri' : $pasien['hub_pj'];
                            foreach(['Diri Sendiri', 'Suami/Istri', 'Orang Tua', 'Anak', 'Saudara Kandung', 'Wali'] as $hub) {
                                $sel = (($consent ? $consent['hubungan_pj'] : $default_hub) == $hub) ? 'selected' : '';
                                echo "<option value='$hub' $sel>$hub</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label>Jenis Kelamin Penandatangan</label>
                        <select name="jk_pj">
                            <option value="L" <?php echo (($consent ? $consent['jk_pj'] : $pasien['jk']) == 'L') ? 'selected' : ''; ?>>Laki-Laki</option>
                            <option value="P" <?php echo (($consent ? $consent['jk_pj'] : $pasien['jk']) == 'P') ? 'selected' : ''; ?>>Perempuan</option>
                        </select>
                    </div>
                </div>

                <div class="grid-3">
                    <div>
                        <label>Umur Penandatangan (Tahun)</label>
                        <input type="number" name="umur_pj" value="<?php echo $consent ? $consent['umur_pj'] : ($usia_pasien >= 18 ? $usia_pasien : 35); ?>" required>
                    </div>
                    <div>
                        <label>No. Telepon / HP</label>
                        <input type="text" name="telp_pj" value="<?php echo $consent ? $consent['telp_pj'] : $pasien['telp_pj']; ?>" required>
                    </div>
                    <div>
                        <label>Alamat Penandatangan</label>
                        <input type="text" name="alamat_pj" value="<?php echo $consent ? $consent['alamat_pj'] : $pasien['alamat']; ?>" required>
                    </div>
                </div>

                <div class="grid-2">
                    <div>
                        <label>Saksi Pihak Pasien / Keluarga</label>
                        <input type="text" name="saksi_keluarga" value="<?php echo $consent ? $consent['saksi_keluarga'] : $pasien['nama_pj']; ?>" placeholder="Nama saksi keluarga..." required>
                    </div>
                    <div>
                        <label>Saksi Pihak Rumah Sakit (Perawat / Bidan)</label>
                        <input type="text" name="saksi_nakes" value="<?php echo $consent ? $consent['saksi_nakes'] : 'Ns. Rawat Jaga'; ?>" placeholder="Nama perawat saksi..." required>
                    </div>
                </div>

                <button type="submit" name="simpan_consent" class="btn-simpan">💾 Simpan Data Pernyataan Tindakan</button>
            </form>
        <?php endif; ?>
    </div>

    <!-- ================= FORMAT KERTAS CETAK RESMI ================= -->
    <?php if($consent && $pasien): ?>
    <div id="print-area">
        <div class="kop">
            <h3>RUMAH SAKIT SIMULASI MINI HOSPITAL</h3>
            <p>Jl. Kesdam V Brawijaya No. 1, Malang, Jawa Timur | Telp: (0341) 1234567</p>
        </div>

        <div class="judul-dokumen">
            LEMBAR PEMBERIAN INFORMASI DAN <?php echo $consent['jenis_pernyataan']; ?> TINDAKAN KEDOKTERAN
        </div>

        <table class="table-polos" style="margin-bottom: 8px;">
            <tr>
                <td style="width: 18%;"><b>Dokter Pelaksana</b></td><td style="width: 32%;">: <?php echo $consent['dokter_pelaksana']; ?></td>
                <td style="width: 18%;"><b>No. Rekam Medis</b></td><td style="width: 32%;">: <b><?php echo $pasien['no_rm']; ?></b></td>
            </tr>
            <tr>
                <td><b>Pemberi Informasi</b></td><td>: <?php echo $consent['pemberi_informasi']; ?></td>
                <td><b>Nama Pasien</b></td><td>: <?php echo $pasien['nama_lengkap']; ?> (<?php echo $pasien['jk']; ?>)</td>
            </tr>
            <tr>
                <td><b>Penerima Informasi</b></td><td>: <?php echo $consent['nama_pj']; ?> (<?php echo $consent['hubungan_pj']; ?>)</td>
                <td><b>Tgl Lahir / Usia</b></td><td>: <?php echo date('d-m-Y', strtotime($pasien['tgl_lahir'])); ?> (<?php echo $usia_pasien; ?> Thn)</td>
            </tr>
        </table>

        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 25%;">Jenis Informasi</th>
                    <th style="width: 60%;">Isi Informasi Medis</th>
                    <th style="width: 10%;">Tanda (v)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="text-align: center;">1</td><td>Diagnosis (WD & DD)</td><td><?php echo $consent['diagnosis_kerja']; ?></td><td style="text-align: center;">✓</td>
                </tr>
                <tr>
                    <td style="text-align: center;">2</td><td>Dasar Diagnosis</td><td><?php echo $consent['dasar_diagnosis']; ?></td><td style="text-align: center;">✓</td>
                </tr>
                <tr>
                    <td style="text-align: center;">3</td><td>Tindakan Kedokteran</td><td><b><?php echo $consent['nama_tindakan']; ?></b></td><td style="text-align: center;">✓</td>
                </tr>
                <tr>
                    <td style="text-align: center;">4</td><td>Indikasi Tindakan</td><td><?php echo $consent['indikasi_tindakan']; ?></td><td style="text-align: center;">✓</td>
                </tr>
                <tr>
                    <td style="text-align: center;">5</td><td>Tata Cara</td><td><?php echo nl2br($consent['tata_cara_tindakan']); ?></td><td style="text-align: center;">✓</td>
                </tr>
                <tr>
                    <td style="text-align: center;">6</td><td>Tujuan</td><td><?php echo $consent['tujuan_tindakan']; ?></td><td style="text-align: center;">✓</td>
                </tr>
                <tr>
                    <td style="text-align: center;">7</td><td>Risiko & Komplikasi</td><td><?php echo nl2br($consent['risiko_komplikasi']); ?></td><td style="text-align: center;">✓</td>
                </tr>
                <tr>
                    <td style="text-align: center;">8</td><td>Prognosis</td><td><?php echo $consent['prognosis_tindakan']; ?></td><td style="text-align: center;">✓</td>
                </tr>
                <tr>
                    <td style="text-align: center;">9</td><td>Alternatif & Risiko</td><td><?php echo $consent['alternatif_risiko']; ?></td><td style="text-align: center;">✓</td>
                </tr>
            </tbody>
        </table>

        <div style="font-size: 11px; margin-top: 10px; text-align: justify; line-height: 1.4;">
            Dengan ini menyatakan bahwa saya telah menerangkan hal-hal di atas secara benar dan jelas, serta memberikan kesempatan untuk berpikir dan bertanya sehubungan dengan tindakan tersebut.<br>
            <div style="text-align: right; margin-top: 5px;">Dokter Pemberi Edukasi: <b><?php echo $consent['pemberi_informasi']; ?></b></div>
        </div>

        <hr style="border: 0; border-top: 1px dashed black; margin: 10px 0;">

        <!-- PERNYATAAN PERSETUJUAN / PENOLAKAN -->
        <div style="font-weight: bold; text-decoration: underline; margin-bottom: 5px;">
            SURAT PERNYATAAN <?php echo $consent['jenis_pernyataan']; ?> TINDAKAN KEDOKTERAN
        </div>
        <div style="text-align: justify; line-height: 1.4;">
            Yang bertanda tangan di bawah ini, saya nama <b><?php echo $consent['nama_pj']; ?></b>, jenis kelamin <?php echo ($consent['jk_pj'] == 'L') ? 'Laki-Laki' : 'Perempuan'; ?>, umur <?php echo $consent['umur_pj']; ?> tahun, alamat <?php echo $consent['alamat_pj']; ?>, dengan ini menyatakan <b><?php echo $consent['jenis_pernyataan']; ?></b> terhadap tindakan <b><?php echo $consent['nama_tindakan']; ?></b> yang akan dilakukan terhadap diri saya / <?php echo $consent['hubungan_pj']; ?> saya yang bernama <b><?php echo $pasien['nama_lengkap']; ?></b> (No. RM: <?php echo $pasien['no_rm']; ?>).
            <br>
            Saya memahami perlunya tindakan tersebut dan risiko yang dapat ditimbulkan. Saya bertanggung jawab penuh atas keputusan ini tanpa adanya paksaan dari pihak mana pun.
        </div>

        <table class="ttd-box">
            <tr>
                <td>
                    Dokter Pelaksana Tindakan<br><br><br><br>
                    <b>( <?php echo $consent['dokter_pelaksana']; ?> )</b>
                </td>
                <td>
                    Malang, <?php echo date('d-m-Y H:i', strtotime($consent['waktu_dibuat'])); ?><br>
                    Yang Membuat Pernyataan<br><br><br><br>
                    <b>( <?php echo $consent['nama_pj']; ?> )</b>
                </td>
                <td>
                    Saksi (Keluarga/Wali)<br><br><br><br>
                    <b>( <?php echo $consent['saksi_keluarga']; ?> )</b>
                </td>
                <td>
                    Saksi (Tenaga Kesehatan)<br><br><br><br>
                    <b>( <?php echo $consent['saksi_nakes']; ?> )</b>
                </td>
            </tr>
        </table>
    </div>
    <?php endif; ?>

</body>
</html>