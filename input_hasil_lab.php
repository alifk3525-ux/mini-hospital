<?php
include 'koneksi.php';
$no_order = isset($_GET['no_order']) ? mysqli_real_escape_string($koneksi, $_GET['no_order']) : '';

// Tarik data order lab dan pasien
$q_order = mysqli_query($koneksi, "SELECT o.*, p.nama_lengkap, p.jk, p.tgl_lahir, p.alamat 
           FROM order_lab o 
           JOIN pasien p ON o.no_rm = p.no_rm 
           WHERE o.no_order = '$no_order'");
$data = mysqli_fetch_assoc($q_order);

if (!$data) {
    echo "<script>alert('Data order lab tidak ditemukan!'); window.location='dasbor_lab.php';</script>";
    exit;
}

// Proses Simpan Hasil Pengujian
if (isset($_POST['simpan_hasil'])) {
    $petugas = mysqli_real_escape_string($koneksi, $_POST['petugas_analis']);
    $kesan = mysqli_real_escape_string($koneksi, $_POST['kesan_analis']);
    $tgl_hasil = date('Y-m-d H:i:s');

    // Update masing-masing parameter
    if (isset($_POST['hasil'])) {
        foreach ($_POST['hasil'] as $id_detail => $nilai) {
            $id = (int)$id_detail;
            $val = mysqli_real_escape_string($koneksi, $nilai);
            $status_val = mysqli_real_escape_string($koneksi, $_POST['status_nilai'][$id]);

            mysqli_query($koneksi, "UPDATE hasil_lab_detail SET hasil = '$val', status_nilai = '$status_val' WHERE id_detail = '$id'");
        }
    }

    // Update status order menjadi Selesai
    mysqli_query($koneksi, "UPDATE order_lab SET status_lab = 'Selesai', petugas_analis = '$petugas', tgl_hasil = '$tgl_hasil', kesan_analis = '$kesan' WHERE no_order = '$no_order'");

    echo "<script>alert('Hasil Laboratorium Berhasil Disimpan!'); window.location='input_hasil_lab.php?no_order=$no_order';</script>";
    exit;
}

// Ambil rincian parameter uji
$q_detail = mysqli_query($koneksi, "SELECT * FROM hasil_lab_detail WHERE no_order = '$no_order' ORDER BY kategori, id_detail ASC");

// Auto trigger print jika parameter cetak ada
$auto_print = isset($_GET['cetak']) ? true : false;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Hasil Laboratorium - <?php echo $no_order; ?></title>
    <style>
        @media screen {
            body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f0f4f8; padding: 20px; color: #1e293b; }
            .wadah { background: white; padding: 25px; border-radius: 8px; max-width: 900px; margin: 0 auto; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
            .header-form { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #0284c7; padding-bottom: 10px; margin-bottom: 15px; }
            .card-pasien { background: #f0f9ff; border-left: 5px solid #0284c7; padding: 12px 15px; border-radius: 6px; margin-bottom: 20px; display: flex; justify-content: space-between; font-size: 13px; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 13px; }
            th, td { border: 1px solid #cbd5e1; padding: 8px 10px; }
            th { background: #0369a1; color: white; }
            input[type="text"], select, textarea { width: 100%; padding: 6px 8px; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; }
            button.btn-simpan { background: #0284c7; color: white; border: none; padding: 12px 20px; border-radius: 6px; font-size: 15px; font-weight: bold; cursor: pointer; width: 100%; }
            button.btn-simpan:hover { background: #0369a1; }
            .btn-cetak { background: #10b981; color: white; border: none; padding: 8px 16px; border-radius: 5px; font-weight: bold; cursor: pointer; }
            #print-area { display: none; }
        }

        @media print {
            body { font-family: Arial, sans-serif; font-size: 12px; color: black; background: white; margin: 0; padding: 10px; }
            .no-print { display: none !important; }
            #print-area { display: block !important; }
            .kop { text-align: center; border-bottom: 2px solid black; padding-bottom: 6px; margin-bottom: 12px; }
            .kop h2, .kop p { margin: 2px; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th, td { border: 1px solid black; padding: 5px 8px; text-align: left; }
            th { background: #f1f5f9; }
            .ttd-box { width: 100%; margin-top: 30px; border-collapse: collapse; text-align: center; }
            .ttd-box td { border: none; width: 50%; vertical-align: bottom; height: 75px; }
        }
    </style>
</head>
<body <?php if($auto_print) echo 'onload="window.print()"'; ?>>

    <!-- ================= LAYAR PENGISIAN HASIL OLEH ANALIS ================= -->
    <div class="wadah no-print">
        <div class="header-form">
            <h3 style="margin: 0; color: #0369a1;">🧪 Lembar Hasil Pengujian Laboratorium</h3>
            <?php if($data['status_lab'] == 'Selesai'): ?>
                <button class="btn-cetak" onclick="window.print()">🖨️ Cetak Lembar Hasil</button>
            <?php endif; ?>
        </div>

        <div class="card-pasien">
            <div>
                <strong><?php echo $data['nama_lengkap']; ?> (<?php echo $data['jk']; ?>)</strong><br>
                RM: <b><?php echo $data['no_rm']; ?></b> | Reg: <?php echo $data['no_registrasi']; ?>
            </div>
            <div style="text-align: right;">
                <b style="color: #0284c7;"><?php echo $data['no_order']; ?></b><br>
                Dokter: <?php echo $data['dokter_pengirim']; ?> (<?php echo $data['unit_pengirim']; ?>)
            </div>
        </div>

        <form method="POST">
            <table>
                <thead>
                    <tr>
                        <th style="width: 25%;">Kategori & Parameter</th>
                        <th style="width: 25%;">Hasil Pemeriksaan</th>
                        <th style="width: 20%;">Nilai Rujukan</th>
                        <th style="width: 10%;">Satuan</th>
                        <th style="width: 20%;">Status Penilaian</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($q_detail)): ?>
                    <tr>
                        <td>
                            <small style="color: #64748b;"><?php echo $row['kategori']; ?></small><br>
                            <b><?php echo $row['nama_parameter']; ?></b>
                        </td>
                        <td>
                            <input type="text" name="hasil[<?php echo $row['id_detail']; ?>]" value="<?php echo $row['hasil'] != '-' ? $row['hasil'] : ''; ?>" required placeholder="Ketik angka hasil...">
                        </td>
                        <td><?php echo $row['nilai_rujukan']; ?></td>
                        <td><?php echo $row['satuan']; ?></td>
                        <td>
                            <select name="status_nilai[<?php echo $row['id_detail']; ?>]">
                                <option value="Normal" <?php echo ($row['status_nilai'] == 'Normal') ? 'selected' : ''; ?>>Normal</option>
                                <option value="Tinggi" <?php echo ($row['status_nilai'] == 'Tinggi') ? 'selected' : ''; ?>>Tinggi (H)</option>
                                <option value="Rendah" <?php echo ($row['status_nilai'] == 'Rendah') ? 'selected' : ''; ?>>Rendah (L)</option>
                                <option value="Kritis" <?php echo ($row['status_nilai'] == 'Kritis') ? 'selected' : ''; ?>>Kritis (*)</option>
                            </select>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="font-weight: bold; font-size: 13px;">Petugas Analis Laboratorium</label>
                    <input type="text" name="petugas_analis" value="<?php echo $data['petugas_analis'] ? $data['petugas_analis'] : 'Analis Jaga'; ?>" required>
                </div>
                <div>
                    <label style="font-weight: bold; font-size: 13px;">Kesan / Interpretasi Hasil Analis</label>
                    <textarea name="kesan_analis" rows="2" placeholder="Catatan kesan mikrobiologi, temuan abnormal, saran lanjutan..."><?php echo $data['kesan_analis']; ?></textarea>
                </div>
            </div>

            <button type="submit" name="simpan_hasil" class="btn-simpan">💾 Simpan Hasil & Validasi Laboratorium</button>
        </form>
    </div>

    <!-- ================= DOKUMEN CETAK RESMI HASIL LABORATORIUM ================= -->
    <div id="print-area">
        <div class="kop">
            <h2>RUMAH SAKIT SIMULASI MINI HOSPITAL</h2>
            <p>Jl. Kesdam V Brawijaya No. 1, Malang, Jawa Timur | Telp: (0341) 1234567</p>
            <h3 style="text-decoration: underline; margin-top: 6px;">LEMBAR HASIL PEMERIKSAAN LABORATORIUM</h3>
        </div>

        <table style="border: none; margin-bottom: 10px; font-size: 12px;">
            <tr>
                <td style="width: 18%; border: none;"><b>No. Order Lab</b></td><td style="width: 32%; border: none;">: <b><?php echo $data['no_order']; ?></b></td>
                <td style="width: 18%; border: none;"><b>No. Rekam Medis</b></td><td style="width: 32%; border: none;">: <b><?php echo $data['no_rm']; ?></b></td>
            </tr>
            <tr>
                <td style="border: none;"><b>Nama Pasien</b></td><td style="border: none;">: <?php echo $data['nama_lengkap']; ?> (<?php echo $data['jk']; ?>)</td>
                <td style="border: none;"><b>Unit Pengirim</b></td><td style="border: none;">: <?php echo $data['unit_pengirim']; ?></td>
            </tr>
            <tr>
                <td style="border: none;"><b>Tanggal Lahir</b></td><td style="border: none;">: <?php echo date('d-m-Y', strtotime($data['tgl_lahir'])); ?></td>
                <td style="border: none;"><b>Dokter Pengirim</b></td><td style="border: none;">: <?php echo $data['dokter_pengirim']; ?></td>
            </tr>
            <tr>
                <td style="border: none;"><b>Waktu Selesai</b></td><td style="border: none;">: <?php echo date('d-m-Y H:i', strtotime($data['tgl_hasil'])); ?> WIB</td>
                <td style="border: none;"><b>Diagnosis Klinis</b></td><td style="border: none;">: <?php echo $data['diagnosa_klinis']; ?></td>
            </tr>
        </table>

        <table>
            <thead>
                <tr>
                    <th style="width: 35%;">Parameter Pemeriksaan</th>
                    <th style="width: 20%; text-align: center;">Hasil</th>
                    <th style="width: 25%;">Nilai Rujukan Normal</th>
                    <th style="width: 10%;">Satuan</th>
                    <th style="width: 10%; text-align: center;">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Query ulang untuk cetak
                $q_cetak = mysqli_query($koneksi, "SELECT * FROM hasil_lab_detail WHERE no_order = '$no_order' ORDER BY kategori, id_detail ASC");
                $kategori_sekarang = '';
                while($c = mysqli_fetch_assoc($q_cetak)):
                    if ($kategori_sekarang != $c['kategori']) {
                        $kategori_sekarang = $c['kategori'];
                        echo "<tr style='background: #f8fafc; font-weight: bold;'><td colspan='5'>• " . strtoupper($kategori_sekarang) . "</td></tr>";
                    }
                ?>
                <tr>
                    <td style="padding-left: 20px;"><?php echo $c['nama_parameter']; ?></td>
                    <td style="text-align: center; font-weight: bold; <?php echo ($c['status_nilai'] != 'Normal') ? 'color: red;' : ''; ?>">
                        <?php echo $c['hasil']; ?>
                    </td>
                    <td><?php echo $c['nilai_rujukan']; ?></td>
                    <td><?php echo $c['satuan']; ?></td>
                    <td style="text-align: center; <?php echo ($c['status_nilai'] != 'Normal') ? 'color: red; font-weight: bold;' : ''; ?>">
                        <?php echo $c['status_nilai'] != 'Normal' ? $c['status_nilai'] : '-'; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <?php if(!empty($data['kesan_analis'])): ?>
            <div style="margin-top: 10px; font-size: 11px;">
                <b>Kesan / Catatan Laboratorium:</b><br>
                <i><?php echo nl2br($data['kesan_analis']); ?></i>
            </div>
        <?php endif; ?>

        <table class="ttd-box">
            <tr>
                <td></td>
                <td>
                    Malang, <?php echo date('d F Y'); ?><br>
                    Petugas Pemeriksa / Analis Laboratorium
                    <br><br><br><br>
                    <b>( <?php echo $data['petugas_analis']; ?> )</b>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>