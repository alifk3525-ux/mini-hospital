<?php
include 'koneksi.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Indeks Rekam Medis</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f0f4f8; padding: 20px; }
        .wadah { background: white; padding: 25px; border-radius: 8px; max-width: 1200px; margin: 0 auto; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h2 { color: #0f172a; margin-top: 0; border-bottom: 2px solid #3b82f6; padding-bottom: 10px; }
        .filter-box { background: #e2e8f0; padding: 15px; border-radius: 6px; margin-bottom: 20px; display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; align-items: end; }
        .form-group { display: flex; flex-direction: column; }
        label { font-size: 12px; font-weight: bold; color: #475569; margin-bottom: 5px; }
        input, select { padding: 9px; border: 1px solid #cbd5e1; border-radius: 5px; font-size: 13px; }
        button { background: #3b82f6; color: white; border: none; padding: 10px; border-radius: 5px; font-weight: bold; cursor: pointer; height: 37px; }
        button:hover { background: #2563eb; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 10px; }
        th, td { border: 1px solid #cbd5e1; padding: 10px; text-align: left; }
        th { background: #1e293b; color: white; }
        tr:nth-child(even) { background: #f8fafc; }
        .summary { font-weight: bold; color: #0f172a; background: #dbeafe; padding: 10px; border-radius: 5px; border-left: 4px solid #3b82f6; }
        .badge { padding: 3px 6px; border-radius: 4px; font-size: 11px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="wadah">
        <h2>📊 Indeks Klasifikasi Rekam Medis</h2>
        
        <form method="GET" class="filter-box">
            <div class="form-group">
                <label>Periode Tgl Kunjungan</label>
                <div style="display: flex; gap: 5px;">
                    <input type="date" name="tgl_awal" value="<?php echo isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : ''; ?>" style="width: 50%;">
                    <input type="date" name="tgl_akhir" value="<?php echo isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : ''; ?>" style="width: 50%;">
                </div>
            </div>
            <div class="form-group">
                <label>Layanan</label>
                <select name="layanan">
                    <option value="">Semua Layanan</option>
                    <option value="Rawat Jalan" <?php if(isset($_GET['layanan']) && $_GET['layanan']=='Rawat Jalan') echo 'selected'; ?>>Rawat Jalan</option>
                    <option value="Rawat Inap" <?php if(isset($_GET['layanan']) && $_GET['layanan']=='Rawat Inap') echo 'selected'; ?>>Rawat Inap</option>
                    <option value="IGD" <?php if(isset($_GET['layanan']) && $_GET['layanan']=='IGD') echo 'selected'; ?>>IGD</option>
                </select>
            </div>
            <div class="form-group">
                <label>Indeks Dokter (DPJP)</label>
                <select name="dpjp">
                    <option value="">Semua Dokter</option>
                    <?php
                    $q_dok = mysqli_query($koneksi, "SELECT DISTINCT dpjp FROM kunjungan WHERE dpjp IS NOT NULL AND dpjp != '' ORDER BY dpjp ASC");
                    while($dok = mysqli_fetch_assoc($q_dok)) {
                        $selected = (isset($_GET['dpjp']) && $_GET['dpjp'] == $dok['dpjp']) ? 'selected' : '';
                        echo "<option value='".$dok['dpjp']."' $selected>".$dok['dpjp']."</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label>Indeks Umur</label>
                <select name="kategori_umur">
                    <option value="">Semua Umur</option>
                    <option value="Balita" <?php if(isset($_GET['kategori_umur']) && $_GET['kategori_umur']=='Balita') echo 'selected'; ?>>Balita (0 - 5 Tahun)</option>
                    <option value="Anak" <?php if(isset($_GET['kategori_umur']) && $_GET['kategori_umur']=='Anak') echo 'selected'; ?>>Anak (6 - 17 Tahun)</option>
                    <option value="Dewasa" <?php if(isset($_GET['kategori_umur']) && $_GET['kategori_umur']=='Dewasa') echo 'selected'; ?>>Dewasa (18 - 59 Tahun)</option>
                    <option value="Lansia" <?php if(isset($_GET['kategori_umur']) && $_GET['kategori_umur']=='Lansia') echo 'selected'; ?>>Lansia (≥ 60 Tahun)</option>
                </select>
            </div>
            <div class="form-group" style="display: flex; flex-direction: row; gap: 10px;">
                <div style="flex: 1;">
                    <label>Indeks Penyakit (ICD)</label>
                    <input type="text" name="icd" placeholder="Kode / Nama Penyakit..." value="<?php echo isset($_GET['icd']) ? $_GET['icd'] : ''; ?>" style="width: 100%; box-sizing: border-box;">
                </div>
                <button type="submit" style="align-self: flex-end;">Filter</button>
            </div>
        </form>

        <?php
        $kondisi = "WHERE 1=1";
        
        if (!empty($_GET['tgl_awal']) && !empty($_GET['tgl_akhir'])) {
            $tgl_awal = mysqli_real_escape_string($koneksi, $_GET['tgl_awal']);
            $tgl_akhir = mysqli_real_escape_string($koneksi, $_GET['tgl_akhir']);
            $kondisi .= " AND DATE(k.tgl_masuk) BETWEEN '$tgl_awal' AND '$tgl_akhir'";
        }
        if (!empty($_GET['layanan'])) {
            $jns = mysqli_real_escape_string($koneksi, $_GET['layanan']);
            $kondisi .= " AND k.jenis_layanan = '$jns'";
        }
        if (!empty($_GET['dpjp'])) {
            $dpjp = mysqli_real_escape_string($koneksi, $_GET['dpjp']);
            $kondisi .= " AND k.dpjp = '$dpjp'";
        }
        if (!empty($_GET['kategori_umur'])) {
            $umur = $_GET['kategori_umur'];
            if($umur == 'Balita') $kondisi .= " AND TIMESTAMPDIFF(YEAR, p.tgl_lahir, CURDATE()) <= 5";
            if($umur == 'Anak') $kondisi .= " AND TIMESTAMPDIFF(YEAR, p.tgl_lahir, CURDATE()) BETWEEN 6 AND 17";
            if($umur == 'Dewasa') $kondisi .= " AND TIMESTAMPDIFF(YEAR, p.tgl_lahir, CURDATE()) BETWEEN 18 AND 59";
            if($umur == 'Lansia') $kondisi .= " AND TIMESTAMPDIFF(YEAR, p.tgl_lahir, CURDATE()) >= 60";
        }
        if (!empty($_GET['icd'])) {
            $icd = mysqli_real_escape_string($koneksi, $_GET['icd']);
            $kondisi .= " AND (rm.diagnosa_utama LIKE '%$icd%' OR rm.icd_akhir_utama LIKE '%$icd%' OR rm.icd10_usulan_dokter LIKE '%$icd%')";
        }

        $query = mysqli_query($koneksi, "
            SELECT k.tgl_masuk, k.no_rm, k.jenis_layanan, k.tujuan_layanan, k.dpjp, 
                   p.nama_lengkap, p.tgl_lahir, p.jk, 
                   rm.diagnosa_utama, rm.icd_akhir_utama, rm.icd10_usulan_dokter
            FROM kunjungan k 
            JOIN pasien p ON k.no_rm = p.no_rm 
            LEFT JOIN rekam_medis rm ON k.no_registrasi = rm.no_registrasi 
            $kondisi 
            ORDER BY k.tgl_masuk DESC
        ");
        
        $total_data = mysqli_num_rows($query);
        ?>

        <div class="summary">Total Rekam Jejak Ditemukan: <?php echo $total_data; ?> Kunjungan</div>

        <table>
            <tr>
                <th>Tanggal Masuk</th>
                <th>No. RM & Nama Pasien</th>
                <th>Kelamin & Umur</th>
                <th>Layanan & DPJP</th>
                <th>Diagnosis Utama (ICD-10)</th>
            </tr>
            <?php
            if($total_data == 0) {
                echo "<tr><td colspan='5' style='text-align:center; padding: 20px; color: #64748b;'>Tidak ada data yang sesuai dengan filter.</td></tr>";
            }
            while($d = mysqli_fetch_assoc($query)) {
                $lahir = new DateTime($d['tgl_lahir']);
                $sekarang = new DateTime($d['tgl_masuk']); 
                $umur_tampil = $sekarang->diff($lahir)->y . " Thn, " . $sekarang->diff($lahir)->m . " Bln";
                
                $layanan_bg = ($d['jenis_layanan'] == 'IGD') ? '#fee2e2' : (($d['jenis_layanan'] == 'Rawat Inap') ? '#fef3c7' : '#d1fae5');
                $layanan_txt = ($d['jenis_layanan'] == 'IGD') ? '#991b1b' : (($d['jenis_layanan'] == 'Rawat Inap') ? '#92400e' : '#065f46');
                
                // Menarik diagnosa akhir yang paling relevan (koding final jika ada)
                $diagnosa_tampil = $d['diagnosa_utama'] ?: ($d['icd_akhir_utama'] ?: $d['icd10_usulan_dokter']);
            ?>
            <tr>
                <td><?php echo date('d-m-Y', strtotime($d['tgl_masuk'])); ?></td>
                <td><strong><?php echo $d['no_rm']; ?></strong><br><?php echo $d['nama_lengkap']; ?></td>
                <td><?php echo $d['jk']; ?><br><span style="color:#64748b; font-weight:bold; font-size: 11px;"><?php echo $umur_tampil; ?></span></td>
                <td>
                    <span class="badge" style="background: <?php echo $layanan_bg; ?>; color: <?php echo $layanan_txt; ?>;"><?php echo $d['jenis_layanan']; ?></span><br>
                    <small style="color:#64748b;"><strong><?php echo $d['tujuan_layanan']; ?></strong><br><?php echo $d['dpjp']; ?></small>
                </td>
                <td>
                    <?php if($diagnosa_tampil) { ?>
                        <span style="font-weight: bold; color: #0f172a;"><?php echo $diagnosa_tampil; ?></span>
                    <?php } else { echo "<i style='color:#94a3b8; font-size: 12px;'>Koding belum difinalisasi</i>"; } ?>
                </td>
            </tr>
            <?php } ?>
        </table>
    </div>
</body>
</html>