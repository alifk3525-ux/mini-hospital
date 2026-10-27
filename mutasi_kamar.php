<?php
include 'koneksi.php';
$reg = isset($_GET['reg']) ? mysqli_real_escape_string($koneksi, $_GET['reg']) : '';

// Ambil data pasien & kamar saat ini
$q_pasien = mysqli_query($koneksi, "SELECT k.*, p.nama_lengkap, p.jk, p.tgl_lahir 
            FROM kunjungan k JOIN pasien p ON k.no_rm = p.no_rm 
            WHERE k.no_registrasi = '$reg' AND k.jenis_layanan = 'Rawat Inap' AND k.status_kunjungan = 'Aktif'");
$pasien = mysqli_fetch_assoc($q_pasien);

if (!$pasien) {
    echo "<script>alert('Data pasien rawat inap tidak ditemukan atau sudah tidak aktif!'); window.location='ruang_periksa_ri.php';</script>";
    exit;
}

$kamar_asal = $pasien['tujuan_layanan'];

// Proses Eksekusi Mutasi Kamar
if (isset($_POST['proses_mutasi'])) {
    $kamar_tujuan = mysqli_real_escape_string($koneksi, $_POST['kamar_tujuan']);
    $alasan       = mysqli_real_escape_string($koneksi, $_POST['alasan_mutasi']);
    $petugas      = mysqli_real_escape_string($koneksi, $_POST['petugas_mutasi']);
    $no_rm        = $pasien['no_rm'];

    if ($kamar_tujuan == $kamar_asal) {
        echo "<script>alert('Kamar tujuan tidak boleh sama dengan kamar asal!');</script>";
    } else {
        // 1. Kurangi 1 kasur terisi di kamar asal
        mysqli_query($koneksi, "UPDATE kamar_rawat SET terisi = GREATEST(0, terisi - 1) WHERE nama_ruangan = '$kamar_asal'");

        // 2. Tambah 1 kasur terisi di kamar tujuan
        mysqli_query($koneksi, "UPDATE kamar_rawat SET terisi = terisi + 1 WHERE nama_ruangan = '$kamar_tujuan'");

        // 3. Update tujuan_layanan pasien di tabel kunjungan
        mysqli_query($koneksi, "UPDATE kunjungan SET tujuan_layanan = '$kamar_tujuan' WHERE no_registrasi = '$reg'");

        // 4. Catat Log Riwayat Mutasi Kamar
        mysqli_query($koneksi, "INSERT INTO mutasi_kamar (no_registrasi, no_rm, kamar_asal, kamar_tujuan, alasan_mutasi, petugas_mutasi)
                                VALUES ('$reg', '$no_rm', '$kamar_asal', '$kamar_tujuan', '$alasan', '$petugas')");

        echo "<script>alert('Berhasil! Pasien dipindahkan dari $kamar_asal ke $kamar_tujuan'); window.location='ruang_periksa_ri.php';</script>";
        exit;
    }
}

// Ambil riwayat mutasi khusus pasien ini
$q_log = mysqli_query($koneksi, "SELECT * FROM mutasi_kamar WHERE no_registrasi = '$reg' ORDER BY id_mutasi DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Mutasi & Pindah Ruangan Kamar Rawat Inap</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f0f4f8; padding: 20px; color: #1e293b; }
        .wadah { background: white; padding: 25px; border-radius: 8px; max-width: 800px; margin: 0 auto; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h3 { color: #b45309; border-bottom: 2px solid #fef3c7; padding-bottom: 8px; margin-top: 0; }
        .info-box { background: #fef3c7; border-left: 4px solid #d97706; padding: 12px; margin-bottom: 15px; border-radius: 4px; font-size: 14px; }
        label { font-size: 13px; font-weight: bold; color: #475569; display: block; margin-top: 10px; margin-bottom: 4px; }
        select, textarea, input[type="text"] { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; }
        button { background: #d97706; color: white; border: none; padding: 12px; font-weight: bold; cursor: pointer; border-radius: 5px; width: 100%; font-size: 15px; margin-top: 15px; }
        button:hover { background: #b45309; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 13px; }
        th, td { border: 1px solid #cbd5e1; padding: 8px; text-align: left; }
        th { background: #f1f5f9; }
    </style>
</head>
<body>
    <div class="wadah">
        <h3>🔄 Mutasi & Pindah Ruangan Pasien Rawat Inap</h3>
        
        <div class="info-box">
            <b>Nama Pasien:</b> <?php echo $pasien['nama_lengkap']; ?> (<?php echo $pasien['no_rm']; ?>)<br>
            <b>No. Registrasi:</b> <?php echo $reg; ?> | <b>DPJP:</b> <?php echo $pasien['dpjp']; ?><br>
            <b>Kamar Saat Ini:</b> <span style="color:#b45309; font-weight:bold;"><?php echo $kamar_asal; ?></span>
        </div>

        <form method="POST">
            <label>Pilih Kamar / Bangsal Tujuan:</label>
            <select name="kamar_tujuan" required>
                <option value="">-- Pilih Ruangan & Kelas Tujuan --</option>
                <?php
                // Ambil daftar kamar rawat inap yang sisa kasurnya > 0 dan bukan IGD/Jenazah
                $q_kamar = mysqli_query($koneksi, "SELECT * FROM kamar_rawat 
                                                   WHERE nama_ruangan NOT LIKE '%IGD%' 
                                                   AND nama_ruangan NOT LIKE '%Jenazah%' 
                                                   AND (kapasitas - terisi) > 0 
                                                   ORDER BY kelas ASC");
                while($km = mysqli_fetch_assoc($q_kamar)) {
                    $sisa = $km['kapasitas'] - $km['terisi'];
                    $selected = ($km['nama_ruangan'] == $kamar_asal) ? 'disabled style="color:#cbd5e1;"' : '';
                    echo "<option value='".$km['nama_ruangan']."' $selected>".$km['nama_ruangan']." (".$km['kelas'].") - Sisa Kasur: ".$sisa." Bed</option>";
                }
                ?>
            </select>

            <label>Alasan Pindah Ruangan / Mutasi:</label>
            <textarea name="alasan_mutasi" rows="3" placeholder="Contoh: Permintaan keluarga naik ke VIP / Kondisi memburuk pindah ke ICU / Bangsal penuh..." required></textarea>

            <label>Petugas / Perawat Penanggung Jawab Mutasi:</label>
            <input type="text" name="petugas_mutasi" placeholder="Nama Perawat / Petugas Admisi" required>

            <button type="submit" name="proses_mutasi">🚀 Konfirmasi Mutasi & Pindahkan Kamar</button>
            <a href="ruang_periksa_ri.php" style="display:block; text-align:center; margin-top:10px; color:#ef4444; text-decoration:none; font-weight:bold;">Batal</a>
        </form>

        <?php if (mysqli_num_rows($q_log) > 0): ?>
        <h4 style="margin-top: 25px; color: #475569; border-bottom: 1px solid #cbd5e1; padding-bottom: 5px;">📜 Riwayat Perpindahan Kamar Pasien Ini</h4>
        <table>
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Dari Kamar</th>
                    <th>Ke Kamar</th>
                    <th>Alasan</th>
                    <th>Petugas</th>
                </tr>
            </thead>
            <tbody>
                <?php while($log = mysqli_fetch_assoc($q_log)): ?>
                <tr>
                    <td><small><?php echo date('d/m/Y H:i', strtotime($log['tgl_mutasi'])); ?></small></td>
                    <td><span style="color:#ef4444; font-weight:bold;"><?php echo $log['kamar_asal']; ?></span></td>
                    <td><span style="color:#10b981; font-weight:bold;"><?php echo $log['kamar_tujuan']; ?></span></td>
                    <td><?php echo $log['alasan_mutasi']; ?></td>
                    <td><?php echo $log['petugas_mutasi']; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</body>
</html>