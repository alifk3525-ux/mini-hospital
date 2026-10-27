<?php
include 'koneksi.php';

// =========================================================================
// 1. LOGIKA HAPUS SATUAN KUNJUNGAN
// =========================================================================
if (isset($_GET['hapus'])) {
    $no_reg = mysqli_real_escape_string($koneksi, $_GET['hapus']);

    // Cek detail layanan untuk mengembalikan kasur jika pasien yang dihapus berstatus Rawat Inap & Aktif
    $cek = mysqli_query($koneksi, "SELECT jenis_layanan, tujuan_layanan, status_kunjungan FROM kunjungan WHERE no_registrasi='$no_reg'");
    $data_kunjungan = mysqli_fetch_assoc($cek);

    if ($data_kunjungan && $data_kunjungan['jenis_layanan'] == 'Rawat Inap' && $data_kunjungan['status_kunjungan'] == 'Aktif') {
        $nama_ruang = trim(explode(" - ", $data_kunjungan['tujuan_layanan'])[0]);
        $nama_ruang_esc = mysqli_real_escape_string($koneksi, $nama_ruang);
        mysqli_query($koneksi, "UPDATE kamar_rawat SET terisi = GREATEST(terisi - 1, 0) WHERE nama_ruangan = '$nama_ruang_esc'");
    }

    // Hapus data transaksi terkait
    mysqli_query($koneksi, "DELETE FROM kunjungan WHERE no_registrasi = '$no_reg'");
    mysqli_query($koneksi, "DELETE FROM rekam_medis WHERE no_registrasi = '$no_reg'");
    mysqli_query($koneksi, "DELETE FROM koding_coder WHERE no_registrasi = '$no_reg'");

    echo "<script>alert('Data kunjungan " . $no_reg . " berhasil dihapus!'); window.location='riwayat_kunjungan_all.php';</script>";
    exit;
}

// =========================================================================
// 2. LOGIKA RESET SELURUH KUNJUNGAN (DATA PASIEN TETAP UTUH & AMAN)
// =========================================================================
if (isset($_POST['reset_semua_kunjungan'])) {
    // Menghapus data transaksi log kunjungan & rekam medis
    mysqli_query($koneksi, "DELETE FROM kunjungan");
    mysqli_query($koneksi, "DELETE FROM rekam_medis");
    mysqli_query($koneksi, "DELETE FROM koding_coder");
    
    // Otomatis mengeset ulang statistik kasur bangsal menjadi 0
    mysqli_query($koneksi, "UPDATE kamar_rawat SET terisi = 0");

    // Memicu notifikasi sukses dan refresh halaman
    echo "<script>
            alert('BERHASIL! Seluruh log kunjungan & keterisian kamar telah direset.\\n\\nData Biodata Pasien & No. RM TETAP UTUH dan tersimpan aman di database.');
            window.location='riwayat_kunjungan_all.php';
          </script>";
    exit;
}

// =========================================================================
// 3. LOGIKA FILTER PERIODE BULANAN & LAYANAN
// =========================================================================
$tgl_mulai   = isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : date('Y-m-01');
$tgl_selesai = isset($_GET['tgl_selesai']) ? $_GET['tgl_selesai'] : date('Y-m-d');
$layanan     = isset($_GET['layanan']) ? $_GET['layanan'] : '';

$where = "WHERE DATE(k.tgl_masuk) BETWEEN '$tgl_mulai' AND '$tgl_selesai'";
if (!empty($layanan)) {
    $layanan_esc = mysqli_real_escape_string($koneksi, $layanan);
    $where .= " AND k.jenis_layanan = '$layanan_esc'";
}

$query_sql = "SELECT 
                k.no_registrasi,
                k.no_rm,
                p.nama_lengkap,
                p.jk,
                p.tgl_lahir,
                TIMESTAMPDIFF(YEAR, p.tgl_lahir, k.tgl_masuk) AS umur,
                k.jenis_layanan,
                k.tujuan_layanan,
                k.dpjp,
                k.cara_bayar,
                k.tgl_masuk,
                k.status_kunjungan,
                COALESCE(kc.kode_icd10_utama, rm.icd_utama, 'Belum Koding') AS kode_icd,
                COALESCE(kc.diagnosa_icd10_utama, rm.diagnosa_utama, 'Belum Koding') AS diagnosa
              FROM kunjungan k
              LEFT JOIN pasien p ON k.no_rm = p.no_rm
              LEFT JOIN rekam_medis rm ON k.no_registrasi = rm.no_registrasi
              LEFT JOIN koding_coder kc ON k.no_registrasi = kc.no_registrasi
              $where
              ORDER BY k.tgl_masuk DESC";

$result = mysqli_query($koneksi, $query_sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Master Log Semua Kunjungan - SIMRS Mini Hospital</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; padding: 20px; color: #1e293b; margin: 0; }
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); max-width: 1250px; margin: 0 auto; }
        
        .header-bar { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #1e293b; padding-bottom: 12px; margin-bottom: 20px; }
        .header-bar h2 { margin: 0; color: #1e293b; font-size: 20px; }
        .header-bar p { margin: 4px 0 0 0; color: #64748b; font-size: 13px; }

        .form-filter-container { display: flex; justify-content: space-between; align-items: center; background: #f8fafc; padding: 15px; border-radius: 6px; border: 1px solid #e2e8f0; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
        .filter-group { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .filter-group label { font-size: 13px; font-weight: bold; color: #475569; }
        .filter-group input, .filter-group select { padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 5px; font-size: 13px; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 12.5px; }
        th, td { border: 1px solid #e2e8f0; padding: 10px 10px; text-align: left; vertical-align: middle; }
        th { background: #1e293b; color: white; font-weight: 600; }
        tr:nth-child(even) { background: #f8fafc; }
        tr:hover { background: #f1f5f9; }

        .btn { padding: 8px 14px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; font-size: 12.5px; font-weight: bold; transition: 0.2s; display: inline-flex; align-items: center; gap: 6px; }
        .btn-filter { background: #0284c7; color: white; }
        .btn-filter:hover { background: #0369a1; }
        .btn-export { background: #16a34a; color: white; }
        .btn-export:hover { background: #15803d; }
        .btn-reset { background: #dc2626; color: white; }
        .btn-reset:hover { background: #b91c1c; }
        .btn-hapus { background: #ef4444; color: white; text-decoration: none; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .btn-hapus:hover { background: #b91c1c; }

        .badge { padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; display: inline-block; }
        .bg-aktif { background: #fef3c7; color: #d97706; }
        .bg-selesai { background: #dcfce7; color: #15803d; }
        .bg-batal { background: #fee2e2; color: #b91c1c; }
        .bg-koding { background: #e0f2fe; color: #0369a1; }
    </style>
</head>
<body>

<div class="card">
    <div class="header-bar">
        <div>
            <h2>📊 Master Log Semua Kunjungan (Manajerial)</h2>
            <p>Rekapitulasi seluruh riwayat pendaftaran dan transaksi medis pasien.</p>
        </div>

        <!-- TOMBOL RESET SELURUH KUNJUNGAN -->
        <form method="POST" onsubmit="return confirm('⚠️ PERINGATAN RESET KUNJUNGAN!\n\nApakah Anda yakin ingin MERESET SELURUH RIWAYAT KUNJUNGAN?\n\n- Seluruh transaksi kunjungan, rekam medis, dan keterisian kamar bangsal akan dikosongkan.\n- Data BIODATA PASIEN (Master Pasien) AKAN TETAP UTUH & AMAN.');">
            <button type="submit" name="reset_semua_kunjungan" class="btn btn-reset">🗑️ Reset Seluruh Kunjungan</button>
        </form>
    </div>
    
    <!-- FORM FILTER BULANAN & LAYANAN -->
    <div class="form-filter-container">
        <form method="GET" class="filter-group">
            <label>Dari:</label>
            <input type="date" name="tgl_mulai" value="<?php echo $tgl_mulai; ?>">
            
            <label>Sampai:</label>
            <input type="date" name="tgl_selesai" value="<?php echo $tgl_selesai; ?>">
            
            <label>Layanan:</label>
            <select name="layanan">
                <option value="">-- Semua Layanan --</option>
                <option value="Rawat Jalan" <?php if($layanan=='Rawat Jalan') echo 'selected'; ?>>Rawat Jalan</option>
                <option value="Rawat Inap" <?php if($layanan=='Rawat Inap') echo 'selected'; ?>>Rawat Inap</option>
                <option value="IGD" <?php if($layanan=='IGD') echo 'selected'; ?>>IGD</option>
            </select>
            
            <button type="submit" class="btn btn-filter">🔍 Filter Data</button>
            <a href="riwayat_kunjungan_all.php" class="btn" style="background: #64748b; color: white;">🔄 Reset Filter</a>
        </form>

        <button type="button" onclick="exportTableToCSV('master_log_kunjungan.csv')" class="btn btn-export">📥 Ekspor CSV/Excel</button>
    </div>

    <table id="tblData">
        <thead>
            <tr>
                <th>No</th>
                <th>No. Reg</th>
                <th>No. RM</th>
                <th>Nama Pasien</th>
                <th>JK/Umur</th>
                <th>Layanan / Unit</th>
                <th>DPJP</th>
                <th>Cara Bayar</th>
                <th>Tgl Masuk</th>
                <th>Kode ICD-10</th>
                <th>Diagnosa</th>
                <th>Status</th>
                <th style="text-align: center;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            if (!$result || mysqli_num_rows($result) == 0) {
                echo "<tr><td colspan='13' style='text-align:center; padding: 25px; color: #64748b;'>Belum ada riwayat kunjungan tercatat pada periode ini.</td></tr>";
            } else {
                while($row = mysqli_fetch_assoc($result)): 
                    // Penentuan warna badge berdasarkan status kunjungan
                    $st = $row['status_kunjungan'];
                    $badge_class = 'bg-aktif';
                    if (strpos($st, 'Selesai') !== false || strpos($st, 'Pulang') !== false) {
                        $badge_class = 'bg-selesai';
                    } elseif ($st == 'Batal') {
                        $badge_class = 'bg-batal';
                    } elseif ($st == 'Menunggu Koding') {
                        $badge_class = 'bg-koding';
                    }
            ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td><strong style="color: #0284c7;"><?php echo $row['no_registrasi']; ?></strong></td>
                <td><?php echo $row['no_rm']; ?></td>
                <td><b><?php echo strtoupper($row['nama_lengkap']); ?></b></td>
                <td><?php echo $row['jk'] . ' / ' . ($row['umur'] ?? '0') . ' th'; ?></td>
                <td><?php echo $row['jenis_layanan'] . ' - ' . $row['tujuan_layanan']; ?></td>
                <td><?php echo $row['dpjp']; ?></td>
                <td><?php echo $row['cara_bayar']; ?></td>
                <td><?php echo date('d/m/Y H:i', strtotime($row['tgl_masuk'])); ?></td>
                <td><b><?php echo $row['kode_icd']; ?></b></td>
                <td><?php echo $row['diagnosa']; ?></td>
                <td>
                    <span class="badge <?php echo $badge_class; ?>">
                        <?php echo $st; ?>
                    </span>
                </td>
                <td style="text-align: center;">
                    <a href="?hapus=<?php echo $row['no_registrasi']; ?>" class="btn-hapus" onclick="return confirm('Yakin ingin menghapus riwayat kunjungan <?php echo $row['no_registrasi']; ?>?');">🗑️ Hapus</a>
                </td>
            </tr>
            <?php 
                endwhile;
            } 
            ?>
        </tbody>
    </table>
</div>

<script>
function exportTableToCSV(filename) {
    var csv = [];
    var rows = document.querySelectorAll("table tr");
    for (var i = 0; i < rows.length; i++) {
        var row = [], cols = rows[i].querySelectorAll("td, th");
        for (var j = 0; j < cols.length; j++) 
            row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
        csv.push(row.join(","));
    }
    var csvFile = new Blob([csv.join("\n")], {type: "text/csv"});
    var downloadLink = document.createElement("a");
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
}
</script>

</body>
</html>