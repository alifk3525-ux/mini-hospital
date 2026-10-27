<?php
include 'cek_sesi.php';
include 'koneksi.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Tarik data billing & pasien berdasarkan ID
$q = mysqli_query($koneksi, "SELECT b.*, p.nama_lengkap, p.no_rm, k.jenis_layanan 
                            FROM billing_kasir b 
                            JOIN pasien p ON b.no_rm = p.no_rm 
                            JOIN kunjungan k ON b.no_registrasi = k.no_registrasi 
                            WHERE b.id_billing='$id'");
$data = mysqli_fetch_assoc($q);

if (!$data) {
    echo "<script>alert('Data billing / nota tidak ditemukan!'); window.location='kasir_billing.php';</script>";
    exit;
}

// Proses Update Nota / Invoice
if (isset($_POST['update_nota'])) {
    $b_admin    = (int)$_POST['biaya_admin'];
    $b_dokter   = (int)$_POST['biaya_dokter'];
    $b_kamar    = (int)$_POST['biaya_kamar'];
    $b_obat     = (int)$_POST['biaya_obat'];
    $b_tindakan = (int)$_POST['biaya_tindakan'];
    $total      = $b_admin + $b_dokter + $b_kamar + $b_obat + $b_tindakan;
    $metode     = $_POST['metode_bayar'];
    $bayar      = (int)$_POST['jumlah_bayar'];
    $kembali    = $bayar - $total;
    $catatan    = mysqli_real_escape_string($koneksi, $_POST['catatan']);

    $update = mysqli_query($koneksi, "UPDATE billing_kasir SET 
        biaya_admin='$b_admin', 
        biaya_dokter='$b_dokter', 
        biaya_kamar='$b_kamar', 
        biaya_obat='$b_obat', 
        biaya_tindakan='$b_tindakan', 
        total_tagihan='$total', 
        metode_bayar='$metode', 
        jumlah_bayar='$bayar', 
        kembalian='$kembali', 
        catatan='$catatan' 
        WHERE id_billing='$id'");

    if ($update) {
        echo "<script>alert('Nota / Invoice ({$data['no_kwitansi']}) berhasil diperbarui!'); window.location='kasir_billing.php';</script>";
    } else {
        echo "<script>alert('Gagal memperbarui nota!');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Nota / Invoice - <?php echo $data['no_kwitansi']; ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f8fafc; padding: 20px; color: #334155; }
        .card { background: white; padding: 25px; border-radius: 8px; max-width: 750px; margin: 0 auto; box-shadow: 0 4px 10px rgba(0,0,0,0.08); }
        h3 { margin-top: 0; color: #1e293b; border-bottom: 2px solid #f59e0b; padding-bottom: 10px; }
        .info-box { background: #f1f5f9; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 13px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
        label { font-size: 12px; font-weight: bold; color: #475569; display: block; margin-top: 8px; }
        input, select, textarea { width: 100%; padding: 10px; margin-top: 4px; border: 1px solid #cbd5e1; border-radius: 5px; box-sizing: border-box; }
        .btn-submit { background: #f59e0b; color: white; border: none; padding: 12px; font-weight: bold; font-size: 15px; width: 100%; border-radius: 6px; cursor: pointer; margin-top: 20px; }
        .btn-submit:hover { background: #d97706; }
        .btn-batal { display: block; text-align: center; margin-top: 12px; color: #64748b; text-decoration: none; font-weight: bold; font-size: 13px; }
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

        window.onload = function() {
            hitungTotal();
        };
    </script>
</head>
<body>

    <div class="card">
        <h3>✏️ Edit Nota & Invoice Pembayaran</h3>
        
        <div class="info-box">
            <strong>No. Invoice:</strong> <span style="color:#2563eb; font-weight:bold;"><?php echo $data['no_kwitansi']; ?></span> | 
            <strong>Pasien:</strong> <?php echo $data['nama_lengkap']; ?> (RM: <?php echo $data['no_rm']; ?>) | 
            <strong>No. Reg:</strong> <?php echo $data['no_registrasi']; ?>
        </div>

        <form method="POST">
            <div class="grid-3">
                <div>
                    <label>Biaya Pendaftaran / Admin (Rp)</label>
                    <input type="number" id="biaya_admin" name="biaya_admin" value="<?php echo $data['biaya_admin']; ?>" oninput="hitungTotal()" required>
                </div>
                <div>
                    <label>Jasa Dokter / Visite (Rp)</label>
                    <input type="number" id="biaya_dokter" name="biaya_dokter" value="<?php echo $data['biaya_dokter']; ?>" oninput="hitungTotal()" required>
                </div>
                <div>
                    <label>Biaya Kamar Ranap (Rp)</label>
                    <input type="number" id="biaya_kamar" name="biaya_kamar" value="<?php echo $data['biaya_kamar']; ?>" oninput="hitungTotal()" required>
                </div>
                <div>
                    <label>Total Obat Farmasi (Rp)</label>
                    <input type="number" id="biaya_obat" name="biaya_obat" value="<?php echo $data['biaya_obat']; ?>" oninput="hitungTotal()" required>
                </div>
                <div>
                    <label>Tindakan Medis (Rp)</label>
                    <input type="number" id="biaya_tindakan" name="biaya_tindakan" value="<?php echo $data['biaya_tindakan']; ?>" oninput="hitungTotal()" required>
                </div>
                <div>
                    <label>TOTAL TAGIHAN (Rp)</label>
                    <input type="number" id="total_tagihan" name="total_tagihan" value="<?php echo $data['total_tagihan']; ?>" readonly style="background:#e2e8f0; font-weight:bold; color:#1e293b;">
                </div>
            </div>

            <div class="grid-3" style="margin-top:10px;">
                <div>
                    <label>Metode Pembayaran</label>
                    <select name="metode_bayar">
                        <option value="Tunai" <?php if($data['metode_bayar']=='Tunai') echo 'selected'; ?>>Tunai / Cash</option>
                        <option value="Transfer Bank" <?php if($data['metode_bayar']=='Transfer Bank') echo 'selected'; ?>>Transfer Bank</option>
                        <option value="QRIS" <?php if($data['metode_bayar']=='QRIS') echo 'selected'; ?>>QRIS</option>
                        <option value="BPJS Kesehatan" <?php if($data['metode_bayar']=='BPJS Kesehatan') echo 'selected'; ?>>BPJS Kesehatan</option>
                        <option value="Asuransi Swasta" <?php if($data['metode_bayar']=='Asuransi Swasta') echo 'selected'; ?>>Asuransi Swasta</option>
                    </select>
                </div>
                <div>
                    <label>Nominal Bayar / Tunai (Rp)</label>
                    <input type="number" id="jumlah_bayar" name="jumlah_bayar" value="<?php echo $data['jumlah_bayar']; ?>" oninput="hitungTotal()" required>
                </div>
                <div>
                    <label>Kembalian (Rp)</label>
                    <input type="number" id="kembalian" name="kembalian" value="<?php echo $data['kembalian']; ?>" readonly style="background:#e2e8f0; font-weight:bold;">
                </div>
            </div>

            <label>Catatan / Rincian Tambahan Nota</label>
            <textarea name="catatan" rows="3" placeholder="Catatan atau keterangan penyesuaian nota..."><?php echo htmlspecialchars($data['catatan'] ?? ''); ?></textarea>

            <button type="submit" name="update_nota" class="btn-submit">💾 Simpan Perubahan Nota & Invoice</button>
            <a href="kasir_billing.php" class="btn-batal">← Batal & Kembali ke Kasir</a>
        </form>
    </div>

</body>
</html>