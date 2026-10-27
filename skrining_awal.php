<?php
include 'koneksi.php';
$reg = isset($_GET['reg']) ? mysqli_real_escape_string($koneksi, $_GET['reg']) : '';

// Tarik data pendaftaran & identitas pasien
$q_pasien = mysqli_query($koneksi, "SELECT k.*, p.nama_lengkap, p.jk, p.tgl_lahir, p.alamat 
            FROM kunjungan k 
            JOIN pasien p ON k.no_rm = p.no_rm 
            WHERE k.no_registrasi = '$reg'");
$pasien = mysqli_fetch_assoc($q_pasien);

// Cek apakah skrining untuk kunjungan ini sudah ada
$cek_skrining = mysqli_query($koneksi, "SELECT * FROM skrining_awal WHERE no_registrasi = '$reg'");
$sudah_skrining = mysqli_num_rows($cek_skrining);
$d_skrining = mysqli_fetch_assoc($cek_skrining);

// Proses Simpan / Update Skrining
if (isset($_POST['simpan_skrining'])) {
    $petugas = mysqli_real_escape_string($koneksi, $_POST['petugas_skrining']);
    $cara_masuk = mysqli_real_escape_string($koneksi, $_POST['cara_masuk']);
    $kesadaran = mysqli_real_escape_string($koneksi, $_POST['kesadaran']);
    $gcs = mysqli_real_escape_string($koneksi, $_POST['gcs']);
    $td = mysqli_real_escape_string($koneksi, $_POST['tekanan_darah']);
    $nadi = (int)$_POST['nadi'];
    $suhu = (float)$_POST['suhu'];
    $rr = (int)$_POST['pernapasan'];
    $spo2 = (int)$_POST['spo2'];
    $bb = (float)$_POST['berat_badan'];
    $tb = (float)$_POST['tinggi_badan'];
    $imt = (float)$_POST['imt'];
    $status_gizi = mysqli_real_escape_string($koneksi, $_POST['status_gizi']);
    $nyeri = (int)$_POST['skala_nyeri'];
    $lok_nyeri = mysqli_real_escape_string($koneksi, $_POST['lokasi_nyeri']);
    $jatuh = mysqli_real_escape_string($koneksi, $_POST['risiko_jatuh']);
    $alergi = mysqli_real_escape_string($koneksi, $_POST['riwayat_alergi']);
    $keluhan = mysqli_real_escape_string($koneksi, $_POST['keluhan_masuk']);
    $catatan = mysqli_real_escape_string($koneksi, $_POST['catatan_khusus']);
    $no_rm = $pasien['no_rm'];

    if ($sudah_skrining > 0) {
        // Mode Update
        $sql = "UPDATE skrining_awal SET 
                petugas_skrining='$petugas', cara_masuk='$cara_masuk', kesadaran='$kesadaran', gcs='$gcs',
                tekanan_darah='$td', nadi='$nadi', suhu='$suhu', pernapasan='$rr', spo2='$spo2',
                berat_badan='$bb', tinggi_badan='$tb', imt='$imt', status_gizi='$status_gizi',
                skala_nyeri='$nyeri', lokasi_nyeri='$lok_nyeri', risiko_jatuh='$jatuh',
                riwayat_alergi='$alergi', keluhan_masuk='$keluhan', catatan_khusus='$catatan'
                WHERE no_registrasi='$reg'";
        mysqli_query($koneksi, $sql);
        echo "<script>alert('Lembar Skrining Berhasil Diperbarui!'); window.location='skrining_awal.php?reg=$reg';</script>";
    } else {
        // Mode Baru
        $sql = "INSERT INTO skrining_awal 
                (no_registrasi, no_rm, petugas_skrining, cara_masuk, kesadaran, gcs, tekanan_darah, nadi, suhu, pernapasan, spo2, berat_badan, tinggi_badan, imt, status_gizi, skala_nyeri, lokasi_nyeri, risiko_jatuh, riwayat_alergi, keluhan_masuk, catatan_khusus)
                VALUES ('$reg', '$no_rm', '$petugas', '$cara_masuk', '$kesadaran', '$gcs', '$td', '$nadi', '$suhu', '$rr', '$spo2', '$bb', '$tb', '$imt', '$status_gizi', '$nyeri', '$lok_nyeri', '$jatuh', '$alergi', '$keluhan', '$catatan')";
        mysqli_query($koneksi, $sql);
        echo "<script>alert('Lembar Skrining Awal Berhasil Disimpan!'); window.location='skrining_awal.php?reg=$reg';</script>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Skrining & Observasi Masuk - Mini Hospital</title>
    <style>
        @media screen {
            body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f0f4f8; padding: 20px; color: #1e293b; }
            .container { background: white; max-width: 950px; margin: 0 auto; border-radius: 10px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); padding: 30px; }
            .btn-cetak { background: #0284c7; color: white; border: none; padding: 10px 18px; border-radius: 6px; font-weight: bold; cursor: pointer; margin-bottom: 15px; }
            .btn-cetak:hover { background: #0369a1; }
            .card-header-pasien { background: #e0f2fe; border-left: 5px solid #0284c7; padding: 15px 20px; border-radius: 6px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
            .section-title { font-size: 15px; font-weight: bold; color: #0369a1; margin: 20px 0 10px 0; border-bottom: 2px solid #bae6fd; padding-bottom: 5px; }
            .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 15px; }
            .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 15px; }
            .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
            label { display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 5px; }
            input, select, textarea { width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-size: 13px; }
            input:focus, select:focus, textarea:focus { border-color: #0284c7; outline: none; }
            .badge-status { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
            .badge-sudah { background: #dcfce7; color: #15803d; }
            .badge-belum { background: #fee2e2; color: #b91c1c; }
            .nyeri-indicator { font-weight: bold; padding: 4px 8px; border-radius: 4px; text-align: center; }
            button.btn-simpan { background: #10b981; color: white; border: none; padding: 12px 20px; border-radius: 6px; font-size: 15px; font-weight: bold; cursor: pointer; width: 100%; margin-top: 20px; }
            button.btn-simpan:hover { background: #059669; }
            #print-area { display: none; }
        }

        @media print {
            body { font-family: Arial, sans-serif; font-size: 12px; color: black; background: white; padding: 10px; }
            .no-print { display: none !important; }
            #print-area { display: block !important; }
            .kop { text-align: center; border-bottom: 2px solid black; padding-bottom: 10px; margin-bottom: 15px; }
            .kop h2, .kop p { margin: 2px; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
            th, td { border: 1px solid black; padding: 6px 8px; vertical-align: top; }
            th { background: #f1f5f9; text-align: left; }
            .ttd-area { display: flex; justify-content: flex-end; margin-top: 30px; }
            .ttd-box { text-align: center; width: 220px; }
        }
    </style>
</head>
<body>

    <!-- ================= LAYAR INPUT & MONITOR SCREEN ================= -->
    <div class="container no-print">
        
        <?php if(!$pasien): ?>
            <h3 style="color: #ef4444;">⚠️ Data Registrasi Tidak Ditemukan</h3>
            <p>Silakan buka lembar ini melalui menu pendaftaran atau cantumkan parameter nomor registrasi (Contoh: <code>skrining_awal.php?reg=REG-XXXX</code>).</p>
        <?php else: ?>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h2 style="margin: 0; color: #0f172a;">📋 Lembar Observasi & Skrining Pasien Masuk</h2>
                <?php if($sudah_skrining > 0): ?>
                    <button class="btn-cetak" onclick="window.print()">🖨️ Cetak Lembar Skrining</button>
                <?php endif; ?>
            </div>

            <!-- Kartu Identitas Singkat Pasien -->
            <div class="card-header-pasien">
                <div>
                    <h3 style="margin: 0; color: #0369a1;"><?php echo $pasien['nama_lengkap']; ?> (<?php echo $pasien['jk']; ?>)</h3>
                    <span style="font-size: 13px; color: #475569;">No. RM: <b><?php echo $pasien['no_rm']; ?></b> | Lahir: <?php echo date('d-m-Y', strtotime($pasien['tgl_lahir'])); ?></span>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 13px; font-weight: bold; color: #0284c7;"><?php echo $pasien['no_registrasi']; ?></div>
                    <div style="font-size: 12px; color: #64748b;">Unit: <?php echo $pasien['jenis_layanan']; ?> (<?php echo $pasien['tujuan_layanan']; ?>)</div>
                    <span class="badge-status <?php echo ($sudah_skrining > 0) ? 'badge-sudah' : 'badge-belum'; ?>">
                        <?php echo ($sudah_skrining > 0) ? '✓ Sudah Diskrining' : '⏳ Belum Diskrining'; ?>
                    </span>
                </div>
            </div>

            <form method="POST">
                
                <!-- SEKSI 1: PETUGAS & CARA KEDATANGAN -->
                <div class="section-title">1. Kedatangan & Keadaan Umum</div>
                <div class="grid-3">
                    <div>
                        <label>Nama Petugas Pemeriksa / Perawat</label>
                        <input type="text" name="petugas_skrining" value="<?php echo ($sudah_skrining > 0) ? $d_skrining['petugas_skrining'] : ''; ?>" required placeholder="Nama perawat jaga...">
                    </div>
                    <div>
                        <label>Cara Masuk Pasien</label>
                        <select name="cara_masuk">
                            <?php 
                            $cm = ($sudah_skrining > 0) ? $d_skrining['cara_masuk'] : 'Jalan Mandiri';
                            foreach(['Jalan Mandiri', 'Kursi Roda', 'Brankar / Tempat Tidur Dorong', 'Digendong'] as $opt) {
                                $sel = ($cm == $opt) ? 'selected' : '';
                                echo "<option value='$opt' $sel>$opt</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label>Tingkat Kesadaran</label>
                        <select name="kesadaran">
                            <?php 
                            $ksd = ($sudah_skrining > 0) ? $d_skrining['kesadaran'] : 'Compos Mentis';
                            foreach(['Compos Mentis', 'Apatis', 'Somnolen', 'Sopor', 'Coma'] as $opt) {
                                $sel = ($ksd == $opt) ? 'selected' : '';
                                echo "<option value='$opt' $sel>$opt</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="grid-2">
                    <div>
                        <label>GCS (Glasgow Coma Scale)</label>
                        <input type="text" name="gcs" value="<?php echo ($sudah_skrining > 0) ? $d_skrining['gcs'] : 'E4V5M6'; ?>" placeholder="Contoh: E4V5M6 (Skor 15)">
                    </div>
                    <div>
                        <label>Keluhan Utama Saat Masuk</label>
                        <input type="text" name="keluhan_masuk" value="<?php echo ($sudah_skrining > 0) ? $d_skrining['keluhan_masuk'] : $pasien['keluhan_utama']; ?>" required>
                    </div>
                </div>

                <!-- SEKSI 2: TANDA-TANDA VITAL (TTV) & ANTROPOMETRI -->
                <div class="section-title">2. Tanda-Tanda Vital & Pengukuran Fisik</div>
                <div class="grid-4">
                    <div>
                        <label>Tekanan Darah (mmHg)</label>
                        <input type="text" name="tekanan_darah" value="<?php echo ($sudah_skrining > 0) ? $d_skrining['tekanan_darah'] : ''; ?>" placeholder="Contoh: 120/80" required>
                    </div>
                    <div>
                        <label>Nadi (x / menit)</label>
                        <input type="number" name="nadi" value="<?php echo ($sudah_skrining > 0) ? $d_skrining['nadi'] : ''; ?>" placeholder="80" required>
                    </div>
                    <div>
                        <label>Suhu Tubuh (°C)</label>
                        <input type="number" step="0.1" name="suhu" value="<?php echo ($sudah_skrining > 0) ? $d_skrining['suhu'] : ''; ?>" placeholder="36.5" required>
                    </div>
                    <div>
                        <label>Pernapasan / RR (x / menit)</label>
                        <input type="number" name="pernapasan" value="<?php echo ($sudah_skrining > 0) ? $d_skrining['pernapasan'] : ''; ?>" placeholder="20" required>
                    </div>
                </div>

                <div class="grid-4">
                    <div>
                        <label>SpO2 Saturasi Oksigen (%)</label>
                        <input type="number" name="spo2" value="<?php echo ($sudah_skrining > 0) ? $d_skrining['spo2'] : ''; ?>" placeholder="98">
                    </div>
                    <div>
                        <label>Berat Badan (kg)</label>
                        <input type="number" step="0.1" id="bb" name="berat_badan" value="<?php echo ($sudah_skrining > 0) ? $d_skrining['berat_badan'] : ''; ?>" oninput="hitungIMT()" placeholder="Contoh: 60">
                    </div>
                    <div>
                        <label>Tinggi Badan (cm)</label>
                        <input type="number" step="0.1" id="tb" name="tinggi_badan" value="<?php echo ($sudah_skrining > 0) ? $d_skrining['tinggi_badan'] : ''; ?>" oninput="hitungIMT()" placeholder="Contoh: 165">
                    </div>
                    <div>
                        <label>Indeks Massa Tubuh (IMT / BMI)</label>
                        <input type="text" id="imt" name="imt" value="<?php echo ($sudah_skrining > 0) ? $d_skrining['imt'] : ''; ?>" readonly style="background: #f1f5f9; font-weight: bold;">
                    </div>
                </div>

                <!-- SEKSI 3: SKRINING KHUSUS (NYERI, RISIKO JATUH, GIZI, ALERGI) -->
                <div class="section-title">3. Skrining Risiko Khusus & Alergi</div>
                
                <div class="grid-3">
                    <div>
                        <label>Status Gizi Awal</label>
                        <select name="status_gizi" id="status_gizi">
                            <?php 
                            $sg = ($sudah_skrining > 0) ? $d_skrining['status_gizi'] : 'Gizi Baik / Normal';
                            foreach(['Gizi Kurang / Underweight', 'Gizi Baik / Normal', 'Gizi Lebih / Overweight', 'Obesitas'] as $opt) {
                                $sel = ($sg == $opt) ? 'selected' : '';
                                echo "<option value='$opt' $sel>$opt</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div>
                        <label>Skrining Risiko Jatuh</label>
                        <select name="risiko_jatuh">
                            <?php 
                            $rj = ($sudah_skrining > 0) ? $d_skrining['risiko_jatuh'] : 'Rendah (Pita Hijau)';
                            foreach(['Rendah (Pita Hijau)', 'Sedang (Pita Kuning)', 'Tinggi (Pita Merah / Pengaman)'] as $opt) {
                                $sel = ($rj == $opt) ? 'selected' : '';
                                echo "<option value='$opt' $sel>$opt</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div>
                        <label>Skala Nyeri (NRS 0 - 10)</label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="range" id="skala_nyeri" name="skala_nyeri" min="0" max="10" value="<?php echo ($sudah_skrining > 0) ? $d_skrining['skala_nyeri'] : 0; ?>" oninput="updateNyeri(this.value)">
                            <span id="label_nyeri" class="nyeri-indicator" style="background:#22c55e; color:white; min-width:60px;">0</span>
                        </div>
                    </div>
                </div>

                <div class="grid-2">
                    <div>
                        <label>Lokasi / Karakteristik Nyeri (Bila ada)</label>
                        <input type="text" name="lokasi_nyeri" value="<?php echo ($sudah_skrining > 0) ? $d_skrining['lokasi_nyeri'] : ''; ?>" placeholder="Contoh: Perut kanan bawah, nyeri tumpul hilang-timbul">
                    </div>
                    <div>
                        <label style="color: #dc2626;">⚠️ Riwayat Alergi (Obat / Makanan / Debu)</label>
                        <input type="text" name="riwayat_alergi" value="<?php echo ($sudah_skrining > 0) ? $d_skrining['riwayat_alergi'] : 'Tidak Ada Alergi'; ?>" placeholder="Ketik jenis alergi atau tulis 'Tidak Ada Alergi'">
                    </div>
                </div>

                <div style="margin-bottom: 15px;">
                    <label>Catatan Tambahan / Kebutuhan Khusus</label>
                    <textarea name="catatan_khusus" rows="2" placeholder="Catatan orientasi ruangan, kebutuhan penerjemah bahasa, pendamping khusus, dll..."><?php echo ($sudah_skrining > 0) ? $d_skrining['catatan_khusus'] : ''; ?></textarea>
                </div>

                <button type="submit" name="simpan_skrining" class="btn-simpan">
                    💾 <?php echo ($sudah_skrining > 0) ? 'Perbarui Lembar Observasi' : 'Simpan Data Skrining Masuk'; ?>
                </button>
            </form>
        <?php endif; ?>
    </div>


    <!-- ================= TEMPLATE FORMAT CETAK FISIK (PRINT PREVIEW) ================= -->
    <?php if($sudah_skrining > 0 && $pasien): ?>
    <div id="print-area">
        <div class="kop">
            <h2>RUMAH SAKIT SIMULASI MINI HOSPITAL</h2>
            <p>Jl. Kesdam V Brawijaya No. 1, Malang | Instalasi Rekam Medis & Keperawatan</p>
            <h3 style="text-decoration: underline; margin-top: 10px;">LEMBAR OBSERVASI AWAL & SKRINING KLINIS MASUK</h3>
        </div>

        <table>
            <tr>
                <td style="width: 20%;"><b>No. Registrasi</b></td><td style="width: 30%;"><?php echo $pasien['no_registrasi']; ?></td>
                <td style="width: 20%;"><b>No. Rekam Medis</b></td><td style="width: 30%;"><b><?php echo $pasien['no_rm']; ?></b></td>
            </tr>
            <tr>
                <td><b>Nama Pasien</b></td><td><?php echo $pasien['nama_lengkap']; ?> (<?php echo $pasien['jk']; ?>)</td>
                <td><b>Tgl Lahir / Usia</b></td><td><?php echo date('d-m-Y', strtotime($pasien['tgl_lahir'])); ?></td>
            </tr>
            <tr>
                <td><b>Layanan Tujuan</b></td><td><?php echo $pasien['jenis_layanan']; ?> - <?php echo $pasien['tujuan_layanan']; ?></td>
                <td><b>Waktu Skrining</b></td><td><?php echo date('d-m-Y H:i', strtotime($d_skrining['waktu_skrining'])); ?></td>
            </tr>
        </table>

        <h4>I. KONDISI UMUM SAAT MASUK</h4>
        <table>
            <tr>
                <td style="width: 25%;"><b>Cara Masuk</b></td><td style="width: 25%;"><?php echo $d_skrining['cara_masuk']; ?></td>
                <td style="width: 25%;"><b>Tingkat Kesadaran</b></td><td style="width: 25%;"><?php echo $d_skrining['kesadaran']; ?> (GCS: <?php echo $d_skrining['gcs']; ?>)</td>
            </tr>
            <tr>
                <td><b>Keluhan Masuk</b></td><td colspan="3"><?php echo $d_skrining['keluhan_masuk']; ?></td>
            </tr>
        </table>

        <h4>II. TANDA-TANDA VITAL & FISIK</h4>
        <table>
            <tr>
                <td><b>Tekanan Darah</b></td><td><?php echo $d_skrining['tekanan_darah']; ?> mmHg</td>
                <td><b>Denyut Nadi</b></td><td><?php echo $d_skrining['nadi']; ?> x/menit</td>
                <td><b>Suhu Tubuh</b></td><td><?php echo $d_skrining['suhu']; ?> °C</td>
            </tr>
            <tr>
                <td><b>Pernapasan (RR)</b></td><td><?php echo $d_skrining['pernapasan']; ?> x/menit</td>
                <td><b>Saturasi SpO2</b></td><td><?php echo $d_skrining['spo2']; ?> %</td>
                <td><b>IMT / BMI</b></td><td><?php echo $d_skrining['imt']; ?> (<?php echo $d_skrining['status_gizi']; ?>)</td>
            </tr>
            <tr>
                <td><b>Berat / Tinggi</b></td><td colspan="5"><?php echo $d_skrining['berat_badan']; ?> kg / <?php echo $d_skrining['tinggi_badan']; ?> cm</td>
            </tr>
        </table>

        <h4>III. SKRINING KHUSUS</h4>
        <table>
            <tr>
                <td style="width: 25%;"><b>Tingkat Nyeri</b></td>
                <td>Skor <?php echo $d_skrining['skala_nyeri']; ?>/10 (Lokasi: <?php echo $d_skrining['lokasi_nyeri'] ? $d_skrining['lokasi_nyeri'] : 'Tidak Ada'; ?>)</td>
            </tr>
            <tr>
                <td><b>Risiko Jatuh</b></td>
                <td><b><?php echo $d_skrining['risiko_jatuh']; ?></b></td>
            </tr>
            <tr>
                <td><b>Riwayat Alergi</b></td>
                <td style="color: red; font-weight: bold;"><?php echo $d_skrining['riwayat_alergi']; ?></td>
            </tr>
            <tr>
                <td><b>Catatan Tambahan</b></td>
                <td><?php echo $d_skrining['catatan_khusus'] ? $d_skrining['catatan_khusus'] : '-'; ?></td>
            </tr>
        </table>

        <div class="ttd-area">
            <div class="ttd-box">
                Malang, <?php echo date('d F Y', strtotime($d_skrining['waktu_skrining'])); ?><br>
                Petugas / Perawat Pemeriksa
                <br><br><br><br>
                <b>( <?php echo $d_skrining['petugas_skrining']; ?> )</b>
            </div>
        </div>
    </div>
    <?php endif; ?>


    <!-- ================= SCRIPT LOGIKA OTOMATIS ================= -->
    <script>
    function hitungIMT() {
        let bb = parseFloat(document.getElementById('bb').value);
        let tb = parseFloat(document.getElementById('tb').value);

        if (bb > 0 && tb > 0) {
            let meter = tb / 100;
            let imt = (bb / (meter * meter)).toFixed(1);
            document.getElementById('imt').value = imt;

            let selectGizi = document.getElementById('status_gizi');
            if (imt < 18.5) {
                selectGizi.value = 'Gizi Kurang / Underweight';
            } else if (imt >= 18.5 && imt <= 24.9) {
                selectGizi.value = 'Gizi Baik / Normal';
            } else if (imt >= 25 && imt <= 29.9) {
                selectGizi.value = 'Gizi Lebih / Overweight';
            } else {
                selectGizi.value = 'Obesitas';
            }
        }
    }

    function updateNyeri(val) {
        let label = document.getElementById('label_nyeri');
        let txt = val + " / 10";
        label.innerText = txt;

        if (val == 0) {
            label.style.background = "#22c55e"; // Hijau
        } else if (val <= 3) {
            label.style.background = "#84cc16"; // Hijau muda (ringan)
        } else if (val <= 6) {
            label.style.background = "#eab308"; // Kuning (sedang)
        } else if (val <= 8) {
            label.style.background = "#f97316"; // Oranye (berat)
        } else {
            label.style.background = "#ef4444"; // Merah (sangat berat)
        }
    }

    // Panggil saat pertama kali halaman terbuka jika data sudah ada
    window.onload = function() {
        let nyeriVal = document.getElementById('skala_nyeri');
        if (nyeriVal) updateNyeri(nyeriVal.value);
    };
    </script>
</body>
</html>