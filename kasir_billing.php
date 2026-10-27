<?php
include 'cek_sesi.php';
include 'koneksi.php';

$petugas = $_SESSION['nama_user'] ?? 'Kasir';
$reg_pilih = isset($_GET['reg']) ? $_GET['reg'] : '';

// 1. Tarik Data Kunjungan & Hitung Estimasi Biaya
$pasien = null;
if (!empty($reg_pilih)) {
    $q_kunj = mysqli_query($koneksi, "SELECT k.*, p.nama_lengkap, p.alamat, p.cara_bayar 
               FROM kunjungan k JOIN pasien p ON k.no_rm = p.no_rm 
               WHERE k.no_registrasi = '$reg_pilih'");
    $pasien = mysqli_fetch_assoc($q_kunj);
}

// 2. Proses Simpan Nota / Invoice Baru
if (isset($_POST['simpan_nota'])) {
    $no_kwitansi   = "INV/" . date('Ymd') . "/" . rand(1000, 9999);
    $no_reg        = $_POST['no_registrasi'];
    $no_rm         = $_POST['no_rm'];
    $b_admin       = (int)$_POST['biaya_admin'];
    $b_dokter      = (int)$_POST['biaya_dokter'];
    $b_kamar       = (int)$_POST['biaya_kamar'];
    $b_obat        = (int)$_POST['biaya_obat'];
    $b_tindakan    = (int)$_POST['biaya_tindakan'];
    $total         = $b_admin + $b_dokter + $b_kamar + $b_obat + $b_tindakan;
    $metode        = $_POST['metode_bayar'];
    $jumlah_bayar  = (int)$_POST['jumlah_bayar'];
    $kembalian     = $jumlah_bayar - $total;
    $catatan       = mysqli_real_escape_string($koneksi, $_POST['catatan']);

    $simpan = mysqli_query($koneksi, "INSERT INTO billing_kasir 
        (no_kwitansi, no_registrasi, no_rm, biaya_admin, biaya_dokter, biaya_kamar, biaya_obat, biaya_tindakan, total_tagihan, metode_bayar, jumlah_bayar, kembalian, catatan, petugas_kasir) 
        VALUES ('$no_kwitansi', '$no_reg', '$no_rm', '$b_admin', '$b_dokter', '$b_kamar', '$b_obat', '$b_tindakan', '$total', '$metode', '$jumlah_bayar', '$kembalian', '$catatan', '$petugas')");

    if ($simpan) {
        // Update status kunjungan jadi Selesai
        mysqli_query($koneksi, "UPDATE kunjungan SET status_kunjungan='Selesai' WHERE no_registrasi='$no_reg'");
        echo "<script>alert('Nota & Invoice ($no_kwitansi) berhasil disimpan ke Riwayat!'); window.location='kasir_billing.php';</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kasir & Invoice Billing</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f8fafc; padding: 20px; color: #334155; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 25px; }
        h2, h3 { margin-top: 0; color: #1e293b; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; }
        label { font-size: 12px; font-weight: bold; color: #475569; display: block; margin-top: 8px; }
        input, select, textarea { width: 100%; padding: 10px; margin-top: 4px; border: 1px solid #cbd5e1; border-radius: 5px; box-sizing: border-box; }
        .btn { padding: 10px 16px; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; font-size: 13px; }
        .btn-simpan { background: #10b981; color: white; width: 100%; margin-top: 15px; font-size: 15px; }
        .btn-simpan:hover { background: #059669; }
        .btn-thermal { background: #8b5cf6; color: white; }
        .btn-dot { background: #0284c7; color: white; }
        .btn-edit { background: #f59e0b; color: white; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 13px; }
        th, td { border: 1px solid #e2e8f0; padding: 10px; text-align: left; }
        th { background: #1e293b; color: white; }
        .badge-lunas { background: #dcfce7; color: #166534; padding: 3px 8px; border-radius: 4px; font-weight: bold; }
    </style>
    <script>
        function hitungTotal() {
            let admin = parseInt(document.getElementById('biaya_admin').value) || 0;
            let dokter = parseInt(document.getElementById('biaya_dokter').value) || 0;
            let kamar = parseInt(document.getElementById('biaya_kamar').value) || 0;
            let obat = parseInt(document.getElementById('biaya_obat').value) || 0;
            let tindakan = parseInt(document.getElementById('biaya_tindakan').value) || 0;
            
            let total = admin + dokter + kamar + obat + tindakan;
            document.getElementById('total_tagihan').value = total;

            let bayar = parseInt(document.getElementById('jumlah_bayar').value) || 0;
            let kembalian = bayar - total;
            document.getElementById('kembalian').value = kembalian;
        }

        // Jalankan kalkulasi otomatis saat halaman pertama kali terbuka
        window.onload = function() {
            if (document.getElementById('biaya_admin')) {
                hitungTotal();
            }
        };
    </script>
</head>
<body>

    <!-- SECTION 1: FORM PEMBUATAN NOTA / INVOICE -->
    <div class="card">
        <h3>💵 Buat Nota & Invoice Pembayaran Pasien</h3>
        
        <!-- Cari Pasien Belum Bayar -->
        <form method="GET" style="margin-bottom: 15px;">
            <label>Pilih Pasien Kunjungan Aktif / Belum Bayar:</label>
            <div style="display:flex; gap:10px;">
                <select name="reg" required>
                    <option value="">-- Pilih No. Registrasi Pasien --</option>
                    <?php
                    // PERBAIKAN: Menyorot hanya pasien yang belum selesai / belum batal
                    $q_pas = mysqli_query($koneksi, "SELECT k.no_registrasi, k.no_rm, k.jenis_layanan, p.nama_lengkap 
                                                   FROM kunjungan k JOIN pasien p ON k.no_rm = p.no_rm 
                                                   WHERE k.status_kunjungan NOT IN ('Batal', 'Selesai') 
                                                   ORDER BY k.tgl_masuk DESC LIMIT 30");
                    while ($dp = mysqli_fetch_assoc($q_pas)) {
                        $sel = ($dp['no_registrasi'] == $reg_pilih) ? 'selected' : '';
                        echo "<option value='{$dp['no_registrasi']}' $sel>{$dp['no_registrasi']} - {$dp['nama_lengkap']} ({$dp['jenis_layanan']})</option>";
                    }
                    ?>
                </select>
                <button type="submit" class="btn" style="background:#3b82f6; color:white;">🔍 Tarik Data</button>
            </div>
        </form>

        <?php if ($pasien): ?>
        <form method="POST">
            <input type="hidden" name="no_registrasi" value="<?php echo $pasien['no_registrasi']; ?>">
            <input type="hidden" name="no_rm" value="<?php echo $pasien['no_rm']; ?>">

            <div style="background:#f1f5f9; padding:12px; border-radius:6px; margin-bottom:15px; font-size:13px;">
                <strong>Pasien:</strong> <?php echo $pasien['nama_lengkap']; ?> (RM: <?php echo $pasien['no_rm']; ?>) | 
                <strong>Layanan:</strong> <?php echo $pasien['jenis_layanan']; ?> (<?php echo $pasien['tujuan_layanan']; ?>) | 
                <strong>Penjamin:</strong> <?php echo $pasien['cara_bayar']; ?>
            </div>

            <div class="grid-3">
                <div>
                    <label>Biaya Pendaftaran / Karcis (Rp)</label>
                    <input type="number" id="biaya_admin" name="biaya_admin" value="15000" oninput="hitungTotal()" required>
                </div>
                <div>
                    <label>Jasa Dokter / Visite (Rp)</label>
                    <input type="number" id="biaya_dokter" name="biaya_dokter" value="50000" oninput="hitungTotal()" required>
                </div>
                <div>
                    <label>Biaya Kamar Ranap (Rp)</label>
                    <input type="number" id="biaya_kamar" name="biaya_kamar" value="0" oninput="hitungTotal()" required>
                </div>
                <div>
                    <label>Total Obat Farmasi (Rp)</label>
                    <input type="number" id="biaya_obat" name="biaya_obat" value="35000" oninput="hitungTotal()" required>
                </div>
                <div>
                    <label>Tindakan Medis (Rp)</label>
                    <input type="number" id="biaya_tindakan" name="biaya_tindakan" value="0" oninput="hitungTotal()" required>
                </div>
                <div>
                    <label>TOTAL TAGIHAN (Rp)</label>
                    <input type="number" id="total_tagihan" name="total_tagihan" value="0" readonly style="background:#e2e8f0; font-weight:bold; color:#1e293b;">
                </div>
            </div>

            <div class="grid-3" style="margin-top:10px;">
                <div>
                    <label>Metode Pembayaran</label>
                    <select name="metode_bayar">
                        <option value="Tunai">Tunai / Cash</option>
                        <option value="Transfer Bank">Transfer Bank</option>
                        <option value="QRIS">QRIS</option>
                        <option value="BPJS Kesehatan">BPJS Kesehatan</option>
                        <option value="Asuransi Swasta">Asuransi Swasta</option>
                    </select>
                </div>
                <div>
                    <label>Nominal Bayar / Tunai (Rp)</label>
                    <input type="number" id="jumlah_bayar" name="jumlah_bayar" value="100000" oninput="hitungTotal()" required>
                </div>
                <div>
                    <label>Kembalian (Rp)</label>
                    <input type="number" id="kembalian" name="kembalian" value="0" readonly style="background:#e2e8f0; font-weight:bold;">
                </div>
            </div>

            <label>Catatan / Rincian Tambahan Nota</label>
            <textarea name="catatan" rows="2" placeholder="Catatan pembayaran atau keterangan khusus..."></textarea>

            <button type="submit" name="simpan_nota" class="btn btn-simpan">💾 Simpan Nota & Selesaikan Transaksi</button>
        </form>
        <?php endif; ?>
    </div>

    <!-- SECTION 2: HISTORY RIWAYAT NOTA & INVOICE -->
    <div class="card">
        <h3>📋 Riwayat History Nota & Invoice Terbit</h3>
        <table>
            <tr>
                <th>No. Invoice / Waktu</th>
                <th>No. RM & Nama Pasien</th>
                <th>Total Tagihan</th>
                <th>Metode</th>
                <th>Petugas</th>
                <th>Status</th>
                <th>Aksi Edit & Cetak</th>
            </tr>
            <?php
            $q_hist = mysqli_query($koneksi, "SELECT b.*, p.nama_lengkap 
                                            FROM billing_kasir b 
                                            JOIN pasien p ON b.no_rm = p.no_rm 
                                            ORDER BY b.waktu_bayar DESC LIMIT 50");
            if (mysqli_num_rows($q_hist) == 0) {
                echo "<tr><td colspan='7' style='text-align:center;'>Belum ada riwayat nota/invoice yang tersimpan.</td></tr>";
            }
            while ($dh = mysqli_fetch_assoc($q_hist)) {
            ?>
            <tr>
                <td>
                    <strong><?php echo $dh['no_kwitansi']; ?></strong><br>
                    <small style="color:#64748b;"><?php echo date('d-m-Y H:i', strtotime($dh['waktu_bayar'])); ?></small>
                </td>
                <td>
                    <strong><?php echo $dh['nama_lengkap']; ?></strong><br>
                    <small>RM: <?php echo $dh['no_rm']; ?> | Reg: <?php echo $dh['no_registrasi']; ?></small>
                </td>
                <td><strong>Rp <?php echo number_format($dh['total_tagihan'], 0, ',', '.'); ?></strong></td>
                <td><?php echo $dh['metode_bayar']; ?></td>
                <td><?php echo $dh['petugas_kasir']; ?></td>
                <td><span class="badge-lunas"><?php echo $dh['status_bayar']; ?></span></td>
                <td>
                    <a href="edit_billing.php?id=<?php echo $dh['id_billing']; ?>" class="btn btn-edit">✏️ Edit</a>
                    <a href="cetak_nota_thermal.php?id=<?php echo $dh['id_billing']; ?>" target="_blank" class="btn btn-thermal">🧾 Thermal</a>
                    <a href="cetak_invoice_dotmatrix.php?id=<?php echo $dh['id_billing']; ?>" target="_blank" class="btn btn-dot">🖨️ Dot Matrix/A5</a>
                </td>
            </tr>
            <?php } ?>
        </table>
    </div>

</body>
</html>