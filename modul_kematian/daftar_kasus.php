<?php
session_start();

// 1. KONEKSI KE DATABASE SIMRS
$path_koneksi = __DIR__ . '/../koneksi.php';
if (file_exists($path_koneksi)) {
    include $path_koneksi;
} else {
    include 'koneksi.php';
}

// =========================================================================
// 2. AUTO-SYNC: OTOMATIS TARIK DATA PASIEN MENINGGAL DARI SIMRS KE MODUL
// =========================================================================
if (isset($koneksi) && $koneksi) {
    @mysqli_query($koneksi, "
        INSERT INTO kasus_kematian_kerja (no_registrasi, no_rm, status_proses, status_coding, hasil_identifikasi_pekerjaan)
        SELECT k.no_registrasi, k.no_rm, 'Perlu Identifikasi', 'Belum Coding', 'Belum Dapat Ditentukan'
        FROM kunjungan k 
        LEFT JOIN rekam_medis rm ON k.no_registrasi = rm.no_registrasi 
        WHERE (k.status_kunjungan LIKE '%Meninggal%' 
               OR k.tujuan_layanan LIKE '%Jenazah%' 
               OR rm.status_keluar LIKE '%Meninggal%')
          AND k.no_registrasi NOT IN (SELECT no_registrasi FROM kasus_kematian_kerja)
    ");
}

// =========================================================================
// 3. LOGIKA FILTER PENCARIAN
// =========================================================================
$tgl_awal    = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : '';
$tgl_akhir   = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : '';
$st_ident    = isset($_GET['st_ident']) ? mysqli_real_escape_string($koneksi, $_GET['st_ident']) : '';
$st_coding   = isset($_GET['st_coding']) ? mysqli_real_escape_string($koneksi, $_GET['st_coding']) : '';
$pekerjaan   = isset($_GET['pekerjaan']) ? mysqli_real_escape_string($koneksi, $_GET['pekerjaan']) : '';
$keterkaitan = isset($_GET['keterkaitan']) ? mysqli_real_escape_string($koneksi, $_GET['keterkaitan']) : '';

$where_clause = "WHERE 1=1";

if (!empty($tgl_awal) && !empty($tgl_akhir)) {
    $where_clause .= " AND DATE(COALESCE(k.tgl_keluar, k.tgl_masuk)) BETWEEN '$tgl_awal' AND '$tgl_akhir'";
}
if (!empty($st_ident)) {
    $where_clause .= " AND mkk.status_proses = '$st_ident'";
}
if (!empty($st_coding)) {
    $where_clause .= " AND mkk.status_coding = '$st_coding'";
}
if (!empty($pekerjaan)) {
    $where_clause .= " AND p.pekerjaan LIKE '%$pekerjaan%'";
}
if (!empty($keterkaitan)) {
    $where_clause .= " AND mkk.hasil_identifikasi_pekerjaan = '$keterkaitan'";
}

// =========================================================================
// 4. LOGIKA PAGINASI
// =========================================================================
$limit = 10;
$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$start = ($page - 1) * $limit;

$q_total = (isset($koneksi) && $koneksi) ? @mysqli_query($koneksi, "
    SELECT COUNT(*) AS total 
    FROM kasus_kematian_kerja mkk
    JOIN kunjungan k ON mkk.no_registrasi = k.no_registrasi
    JOIN pasien p ON mkk.no_rm = p.no_rm
    $where_clause
") : false;

$row_total  = ($q_total) ? mysqli_fetch_assoc($q_total) : ['total' => 0];
$total_data = (int)($row_total['total'] ?? 0);
$pages      = ceil($total_data / $limit);

// =========================================================================
// 5. QUERY UTAMA PENARIKAN DATA KASUS (JOIN PASIEN & KUNJUNGAN)
// =========================================================================
$query_str = "
    SELECT 
        mkk.id_kasus,
        mkk.no_rm,
        mkk.no_registrasi,
        p.nama_lengkap AS nama_pasien,
        p.pekerjaan AS pekerjaan_simrs,
        COALESCE(k.tgl_keluar, k.tgl_masuk) AS tgl_kematian,
        mkk.hasil_identifikasi_pekerjaan,
        mkk.status_proses,
        mkk.status_coding
    FROM kasus_kematian_kerja mkk
    JOIN pasien p ON mkk.no_rm = p.no_rm
    LEFT JOIN kunjungan k ON mkk.no_registrasi = k.no_registrasi
    $where_clause
    ORDER BY mkk.id_kasus DESC
    LIMIT $start, $limit
";

$query_data = (isset($koneksi) && $koneksi) ? @mysqli_query($koneksi, $query_str) : false;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Kasus Kematian</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { 
            background-color: #f8fafc; 
            color: #0f172a; 
            padding: 28px 32px; 
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* PAGE HEADER */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .page-title h2 { font-size: 22px; font-weight: 800; color: #0f172a; }
        .page-title p { font-size: 13px; color: #64748b; margin-top: 4px; }

        /* FILTER CARD */
        .filter-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            margin-bottom: 24px;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: 1.4fr 1fr 1fr 1.2fr 1fr auto;
            gap: 12px;
            align-items: end;
        }

        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: 12px; font-weight: 700; color: #475569; }
        
        .form-control {
            padding: 9px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 12.5px;
            outline: none;
            background-color: #ffffff;
            color: #0f172a;
        }
        .form-control:focus { border-color: #2563eb; }

        .btn-filter {
            background-color: #2563eb;
            color: #ffffff;
            border: none;
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 12.5px;
            font-weight: 700;
            cursor: pointer;
            height: 38px;
            transition: background 0.2s;
        }
        .btn-filter:hover { background-color: #1d4ed8; }

        .btn-reset {
            background-color: #ffffff;
            color: #64748b;
            border: 1px solid #cbd5e1;
            padding: 9px 14px;
            border-radius: 8px;
            font-size: 12.5px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 38px;
        }
        .btn-reset:hover { background-color: #f1f5f9; color: #0f172a; }

        /* TABEL KASUS */
        .table-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            padding: 20px;
            margin-bottom: 20px;
        }

        table { width: 100%; border-collapse: collapse; font-size: 12.5px; text-align: left; }
        th {
            background-color: #f8fafc;
            color: #64748b;
            font-weight: 700;
            padding: 10px 14px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 11px;
            text-transform: uppercase;
        }
        td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; color: #1e293b; vertical-align: middle; }
        tr:hover { background-color: #f8fafc; }

        /* BADGES STYLE */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 16px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-perlu { background: #fef3c7; color: #d97706; }
        .badge-terkonfirmasi { background: #dcfce7; color: #15803d; }
        .badge-verifikasi { background: #f3e8ff; color: #7e22ce; }
        .badge-belum { background: #fee2e2; color: #dc2626; }
        .badge-proses { background: #e0f2fe; color: #0369a1; }
        .badge-selesai { background: #dcfce7; color: #15803d; }

        .btn-detail {
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 700;
            color: #ffffff;
            background: #2563eb;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s;
        }
        .btn-detail:hover { background: #1d4ed8; }

        /* FOOTER PAGINATION */
        .table-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 16px;
            font-size: 12px;
            color: #64748b;
        }

        .pagination { display: flex; gap: 4px; }
        .page-item {
            padding: 5px 10px;
            min-width: 28px;
            height: 28px;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11.5px;
            font-weight: 600;
            color: #334155;
            text-decoration: none;
            background: #ffffff;
        }
        .page-item.active { background: #2563eb; color: #ffffff; border-color: #2563eb; }
    </style>
</head>
<body>

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div class="page-title">
            <h2>Daftar Kasus Kematian</h2>
            <p>Pencarian, pemantauan, dan penelusuran seluruh pasien meninggal dari SIMRS.</p>
        </div>
    </div>

    <!-- FILTER CARD -->
    <div class="filter-card">
        <form method="GET" action="daftar_kasus.php" class="filter-grid">
            
            <div class="form-group">
                <label>Periode Tanggal</label>
                <div style="display: flex; gap: 6px;">
                    <input type="date" name="tgl_awal" class="form-control" value="<?= htmlspecialchars($tgl_awal) ?>">
                    <input type="date" name="tgl_akhir" class="form-control" value="<?= htmlspecialchars($tgl_akhir) ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Status Identifikasi</label>
                <select name="st_ident" class="form-control">
                    <option value="">Semua Status</option>
                    <option value="Perlu Identifikasi" <?= $st_ident == 'Perlu Identifikasi' ? 'selected' : '' ?>>Perlu Identifikasi</option>
                    <option value="Sedang Diidentifikasi" <?= $st_ident == 'Sedang Diidentifikasi' ? 'selected' : '' ?>>Sedang Diidentifikasi</option>
                    <option value="Menunggu Verifikasi" <?= $st_ident == 'Menunggu Verifikasi' ? 'selected' : '' ?>>Menunggu Verifikasi</option>
                    <option value="Selesai Verifikasi" <?= $st_ident == 'Selesai Verifikasi' ? 'selected' : '' ?>>Selesai</option>
                </select>
            </div>

            <div class="form-group">
                <label>Status Coding</label>
                <select name="st_coding" class="form-control">
                    <option value="">Semua Status</option>
                    <option value="Belum Coding" <?= $st_coding == 'Belum Coding' ? 'selected' : '' ?>>Belum</option>
                    <option value="Proses Coding" <?= $st_coding == 'Proses Coding' ? 'selected' : '' ?>>Proses</option>
                    <option value="Selesai Coding" <?= $st_coding == 'Selesai Coding' ? 'selected' : '' ?>>Selesai</option>
                </select>
            </div>

            <div class="form-group">
                <label>Keterkaitan Kerja</label>
                <select name="keterkaitan" class="form-control">
                    <option value="">Semua Indikasi</option>
                    <option value="Terindikasi Terkait Pekerjaan" <?= $keterkaitan == 'Terindikasi Terkait Pekerjaan' ? 'selected' : '' ?>>Terkait Kerja</option>
                    <option value="Tidak Terindikasi Terkait Pekerjaan" <?= $keterkaitan == 'Tidak Terindikasi Terkait Pekerjaan' ? 'selected' : '' ?>>Tidak Terkait Kerja</option>
                    <option value="Belum Dapat Ditentukan" <?= $keterkaitan == 'Belum Dapat Ditentukan' ? 'selected' : '' ?>>Belum Ditentukan</option>
                </select>
            </div>

            <button type="submit" class="btn-filter">Cari</button>
            <a href="daftar_kasus.php" class="btn-reset">Reset</a>

        </form>
    </div>

    <!-- TABEL DAFTAR KASUS -->
    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">No.</th>
                    <th>No. RM</th>
                    <th>Nama Pasien</th>
                    <th>Tanggal Kejadian</th>
                    <th>Jenis Kasus</th>
                    <th>Status Identifikasi</th>
                    <th>Status Coding</th>
                    <th style="text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $count = 0;
                if ($query_data && mysqli_num_rows($query_data) > 0) {
                    $no = $start + 1;
                    while ($row = mysqli_fetch_assoc($query_data)) {
                        $count++;
                        $st_i  = $row['status_proses'] ?? 'Perlu Identifikasi';
                        $st_c  = $row['status_coding'] ?? 'Belum Coding';
                        $jenis = ($row['hasil_identifikasi_pekerjaan'] == 'Terindikasi Terkait Pekerjaan') ? 'Trauma' : 'Non Trauma';
                        $tgl_m = !empty($row['tgl_kematian']) ? date('d/m/Y', strtotime($row['tgl_kematian'])) : '-';
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><strong><?= htmlspecialchars($row['no_rm'] ?? '-') ?></strong></td>
                    <td><b><?= htmlspecialchars($row['nama_pasien'] ?? '-') ?></b></td>
                    <td><?= $tgl_m ?></td>
                    <td><?= htmlspecialchars($jenis) ?></td>
                    <td>
                        <?php if ($st_i == 'Perlu Identifikasi'): ?>
                            <span class="badge badge-perlu">Perlu Identifikasi</span>
                        <?php elseif ($st_i == 'Menunggu Verifikasi'): ?>
                            <span class="badge badge-verifikasi">Perlu Verifikasi</span>
                        <?php else: ?>
                            <span class="badge badge-terkonfirmasi">Teridentifikasi</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($st_c == 'Selesai Coding'): ?>
                            <span class="badge badge-selesai">Selesai</span>
                        <?php elseif ($st_c == 'Proses Coding'): ?>
                            <span class="badge badge-proses">Proses</span>
                        <?php else: ?>
                            <span class="badge badge-belum">Belum</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: center;">
                        <a href="identifikasi_kematian.php?id=<?= $row['id_kasus'] ?>" class="btn-detail">Lihat Detail</a>
                    </td>
                </tr>
                <?php
                    }
                } else {
                ?>
                <tr>
                    <td colspan="8" style="text-align: center; color: #64748b; padding: 25px;">
                        Belum ada data kasus kematian di database SIMRS.
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>

        <!-- FOOTER PAGINATION -->
        <div class="table-footer">
            <div>Menampilkan <?= $count > 0 ? ($start + 1) . "-" . ($start + $count) : "0" ?> dari <?= number_format($total_data) ?> kasus</div>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&tgl_awal=<?= urlencode($tgl_awal) ?>&tgl_akhir=<?= urlencode($tgl_akhir) ?>&st_ident=<?= urlencode($st_ident) ?>&st_coding=<?= urlencode($st_coding) ?>&keterkaitan=<?= urlencode($keterkaitan) ?>" class="page-item">‹</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= max(1, $pages); $i++): ?>
                    <a href="?page=<?= $i ?>&tgl_awal=<?= urlencode($tgl_awal) ?>&tgl_akhir=<?= urlencode($tgl_akhir) ?>&st_ident=<?= urlencode($st_ident) ?>&st_coding=<?= urlencode($st_coding) ?>&keterkaitan=<?= urlencode($keterkaitan) ?>" class="page-item <?= ($i == $page) ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($page < $pages): ?>
                    <a href="?page=<?= $page + 1 ?>&tgl_awal=<?= urlencode($tgl_awal) ?>&tgl_akhir=<?= urlencode($tgl_akhir) ?>&st_ident=<?= urlencode($st_ident) ?>&st_coding=<?= urlencode($st_coding) ?>&keterkaitan=<?= urlencode($keterkaitan) ?>" class="page-item">›</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>
</html>