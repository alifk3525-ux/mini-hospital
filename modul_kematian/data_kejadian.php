<?php
session_start();

// Koneksi ke Database SIMRS Utama
$path_koneksi = __DIR__ . '/../koneksi.php';
if (file_exists($path_koneksi)) {
    include $path_koneksi;
}

$id_kasus = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$msg_error = "";

// 1. DEFAULT SUMMARY PASIEN
$summary = [
    'no_rm'          => '00000005',
    'tgl_kematian'   => date('Y-m-d H:i:s'),
    'jenis_kasus'    => 'Penyakit Umum',
    'status_proses'  => 'Sedang Diidentifikasi',
    'status_coding'  => 'Belum Coding'
];

// 2. DEFAULT FORM DATA KEJADIAN
$data = [
    'kronologi_kejadian'  => '',
    'tgl_kejadian'        => date('Y-m-d'),
    'waktu_kejadian'      => date('H:i'),
    'sumber_informasi'    => '',
    'bukti_dokumen'       => '',
    'mekanisme_kejadian'  => '',
    'lokasi_kejadian'     => '',
    'aktivitas_kejadian' => '',
    'objek_alat_bahan'    => '',
    'faktor_lingkungan'   => '',
    'info_tambahan'       => ''
];

// 3. TARIK DATA DARI DATABASE
if ($id_kasus > 0 && isset($koneksi) && $koneksi) {
    try {
        // Tarik Ringkasan Kasus Utama
        $q_summary = mysqli_query($koneksi, "
            SELECT mkk.*, p.no_rm 
            FROM kasus_kematian_kerja mkk 
            LEFT JOIN pasien p ON mkk.no_rm = p.no_rm 
            WHERE mkk.id_kasus = '$id_kasus'
        ");
        if ($q_summary && mysqli_num_rows($q_summary) > 0) {
            $row_s = mysqli_fetch_assoc($q_summary);
            $summary['no_rm']         = !empty($row_s['no_rm']) ? $row_s['no_rm'] : $summary['no_rm'];
            $summary['tgl_kematian']  = !empty($row_s['tgl_kematian']) ? $row_s['tgl_kematian'] : $summary['tgl_kematian'];
            $summary['jenis_kasus']   = !empty($row_s['kategori_kasus']) ? $row_s['kategori_kasus'] : $summary['jenis_kasus'];
            $summary['status_proses'] = !empty($row_s['status_proses']) ? $row_s['status_proses'] : $summary['status_proses'];
            $summary['status_coding'] = !empty($row_s['status_coding']) ? $row_s['status_coding'] : $summary['status_coding'];
        }

        // Tarik Data Kejadian
        $q_kejadian = mysqli_query($koneksi, "SELECT * FROM informasi_pekerjaan_kejadian WHERE id_kasus = '$id_kasus'");
        if ($q_kejadian && mysqli_num_rows($q_kejadian) > 0) {
            $row_k = mysqli_fetch_assoc($q_kejadian);
            $data = array_merge($data, $row_k);
        }
    } catch (mysqli_sql_exception $e) {
        $msg_error = "Error database: " . $e->getMessage();
    }
}

// 4. PROSES SIMPAN / UPDATE DATA KEJADIAN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kronologi   = mysqli_real_escape_string($koneksi ?? null, $_POST['kronologi_kejadian'] ?? '');
    $tgl_k       = $_POST['tgl_kejadian'] ?? date('Y-m-d');
    $waktu_k     = $_POST['waktu_kejadian'] ?? date('H:i');
    $sumber_info = mysqli_real_escape_string($koneksi ?? null, $_POST['sumber_informasi'] ?? '');
    $bukti_doc   = mysqli_real_escape_string($koneksi ?? null, $_POST['bukti_dokumen'] ?? '');
    $mekanisme   = mysqli_real_escape_string($koneksi ?? null, $_POST['mekanisme_kejadian'] ?? '');
    $lokasi      = mysqli_real_escape_string($koneksi ?? null, $_POST['lokasi_kejadian'] ?? '');
    $aktivitas   = mysqli_real_escape_string($koneksi ?? null, $_POST['aktivitas_kejadian'] ?? '');
    $objek_alat  = mysqli_real_escape_string($koneksi ?? null, $_POST['objek_alat_bahan'] ?? '');
    $lingkungan  = mysqli_real_escape_string($koneksi ?? null, $_POST['faktor_lingkungan'] ?? '');
    $info_tmbhn  = mysqli_real_escape_string($koneksi ?? null, $_POST['info_tambahan'] ?? '');

    if (isset($koneksi) && $koneksi && $id_kasus > 0) {
        try {
            $q_cek = mysqli_query($koneksi, "SELECT id_kejadian FROM informasi_pekerjaan_kejadian WHERE id_kasus = '$id_kasus'");
            if ($q_cek && mysqli_num_rows($q_cek) > 0) {
                $sql = "UPDATE informasi_pekerjaan_kejadian SET 
                        kronologi_kejadian='$kronologi', tgl_kejadian='$tgl_k', waktu_kejadian='$waktu_k', 
                        sumber_informasi='$sumber_info', bukti_dokumen='$bukti_doc', mekanisme_kejadian='$mekanisme', 
                        lokasi_kejadian='$lokasi', aktivitas_kejadian='$aktivitas', objek_alat_bahan='$objek_alat', 
                        faktor_lingkungan='$lingkungan', info_tambahan='$info_tmbhn' 
                        WHERE id_kasus='$id_kasus'";
            } else {
                $sql = "INSERT INTO informasi_pekerjaan_kejadian 
                        (id_kasus, kronologi_kejadian, tgl_kejadian, waktu_kejadian, sumber_informasi, bukti_dokumen, mekanisme_kejadian, lokasi_kejadian, aktivitas_kejadian, objek_alat_bahan, faktor_lingkungan, info_tambahan) 
                        VALUES 
                        ('$id_kasus', '$kronologi', '$tgl_k', '$waktu_k', '$sumber_info', '$bukti_doc', '$mekanisme', '$lokasi', '$aktivitas', '$objek_alat', '$lingkungan', '$info_tmbhn')";
            }

            if (mysqli_query($koneksi, $sql)) {
                $redirect = isset($_POST['is_draft']) ? "data_kejadian.php?id=$id_kasus" : "data_klinis.php?id=$id_kasus";
                echo "<script>alert('Data Kejadian Berhasil Disimpan!'); window.location='$redirect';</script>";
                exit;
            } else {
                $msg_error = "Gagal menyimpan data: " . mysqli_error($koneksi);
            }
        } catch (mysqli_sql_exception $e) {
            $msg_error = "Gagal menyimpan data: " . $e->getMessage();
        }
    } else {
        $redirect = isset($_POST['is_draft']) ? "data_kejadian.php?id=1" : "data_klinis.php?id=" . ($id_kasus ?: 1);
        echo "<script>alert('[SIMULASI] Data Kejadian Berhasil Disimpan!'); window.location='$redirect';</script>";
        exit;
    }
}

// Format Tanggal Indonesia
function formatTglIndo($datetime) {
    if (!$datetime) return '-';
    $time = strtotime($datetime);
    $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return date('j', $time) . ' ' . $bulan[(int)date('n', $time)] . ' ' . date('Y', $time);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Kejadian - Modul K3RS</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: #f8fafc; color: #1e293b; padding: 24px; }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        .page-title h2 { font-size: 22px; font-weight: 700; color: #0f172a; }
        .page-title p { font-size: 13px; color: #64748b; margin-top: 3px; }

        /* SUMMARY BANNER */
        .summary-banner {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }
        .summary-item { display: flex; align-items: center; gap: 10px; }
        .summary-icon {
            width: 36px; height: 36px; border-radius: 8px; background: #f1f5f9;
            color: #334155; display: flex; align-items: center; justify-content: center; font-size: 16px;
        }
        .summary-label { font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; }
        .summary-value { font-size: 13.5px; font-weight: 700; color: #0f172a; margin-top: 1px; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 700; }
        .badge-warning { background: #fef3c7; color: #d97706; }
        .badge-gray { background: #f1f5f9; color: #475569; }

        .btn-history {
            background: #ffffff; border: 1px solid #cbd5e1; color: #334155;
            padding: 8px 14px; border-radius: 8px; font-size: 12.5px; font-weight: 600;
            cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;
        }

        /* STEPPER NAVIGASI MODUL */
        .stepper {
            display: flex; background: #ffffff; border: 1px solid #e2e8f0;
            border-radius: 12px; padding: 12px; margin-bottom: 20px; gap: 8px; overflow-x: auto;
        }
        .step-item {
            flex: 1; display: flex; align-items: center; gap: 10px; padding: 10px 14px;
            border-radius: 8px; font-size: 12.5px; font-weight: 600; color: #64748b;
            text-decoration: none; background: #f8fafc; white-space: nowrap;
        }
        .step-item.active { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
        .step-num {
            width: 24px; height: 24px; border-radius: 50%; background: #e2e8f0;
            color: #475569; display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 700;
        }
        .step-item.active .step-num { background: #2563eb; color: #ffffff; }

        /* CARD FORM */
        .card {
            background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02); padding: 20px; margin-bottom: 20px;
        }
        .card-title {
            font-size: 15px; font-weight: 700; color: #0f172a; margin-bottom: 18px;
            display: flex; align-items: center; gap: 8px;
        }

        .form-grid-top { display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 20px; margin-bottom: 16px; }
        .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }

        .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 14px; }
        .form-group label { font-size: 12px; font-weight: 700; color: #334155; }
        .form-group label span { color: #ef4444; }

        .form-control {
            padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px;
            font-size: 13px; outline: none; transition: all 0.2s; background: #ffffff; color: #0f172a;
        }
        .form-control:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }

        textarea.form-control { resize: vertical; }
        .char-count { font-size: 11px; color: #94a3b8; text-align: right; margin-top: 3px; }

        /* INFO BOX */
        .info-box {
            background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 14px;
            font-size: 12px; color: #1e40af; margin-top: 8px;
        }
        .info-box-title { font-weight: 700; display: flex; align-items: center; gap: 6px; margin-bottom: 6px; }
        .info-box-text { color: #3b82f6; font-size: 11.5px; margin-bottom: 10px; }
        .checkbox-group { display: flex; flex-direction: column; gap: 8px; color: #1e293b; font-size: 12px; }
        .checkbox-item { display: flex; align-items: flex-start; gap: 8px; cursor: pointer; }
        .checkbox-item small { display: block; color: #64748b; font-size: 11px; }

        /* FOOTER ACTIONS */
        .form-actions { display: flex; justify-content: flex-end; align-items: center; gap: 10px; margin-top: 10px; }
        .btn {
            padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 700;
            cursor: pointer; border: none; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-secondary { background: #ffffff; color: #475569; border: 1px solid #cbd5e1; }
        .btn-secondary:hover { background: #f8fafc; }
        .btn-primary { background: #2563eb; color: #ffffff; box-shadow: 0 2px 6px rgba(37, 99, 235, 0.2); }
        .btn-primary:hover { background: #1d4ed8; }

        .alert-error {
            background-color: #fef2f2; border: 1px solid #fca5a5; color: #991b1b;
            padding: 12px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 20px;
        }
    </style>
</head>
<body>

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div class="page-title">
            <h2>Data Kejadian</h2>
            <p>Lengkapi informasi kejadian untuk mendukung penelusuran penyebab kematian.</p>
        </div>
    </div>

    <!-- SUMMARY BANNER PASIEN -->
    <div class="summary-banner">
        <div class="summary-item">
            <div class="summary-icon">📋</div>
            <div>
                <div class="summary-label">NO. RM</div>
                <div class="summary-value"><?= htmlspecialchars($summary['no_rm']) ?></div>
            </div>
        </div>

        <div class="summary-item">
            <div class="summary-icon">📅</div>
            <div>
                <div class="summary-label">TANGGAL KEMATIAN</div>
                <div class="summary-value"><?= formatTglIndo($summary['tgl_kematian']) ?></div>
            </div>
        </div>

        <div class="summary-item">
            <div class="summary-icon">👤</div>
            <div>
                <div class="summary-label">JENIS KASUS</div>
                <div class="summary-value"><?= htmlspecialchars($summary['jenis_kasus']) ?></div>
            </div>
        </div>

        <div class="summary-item">
            <div class="summary-icon">🛡️</div>
            <div>
                <div class="summary-label">STATUS IDENTIFIKASI</div>
                <div class="summary-value"><span class="badge badge-warning"><?= htmlspecialchars($summary['status_proses']) ?></span></div>
            </div>
        </div>

        <div class="summary-item">
            <div class="summary-icon">📷</div>
            <div>
                <div class="summary-label">STATUS CODING</div>
                <div class="summary-value"><span class="badge badge-gray"><?= htmlspecialchars($summary['status_coding']) ?></span></div>
            </div>
        </div>

        <a href="riwayat_klinis.php?id=<?= $id_kasus ?>" class="btn-history">
            🕒 Lihat Riwayat
        </a>
    </div>

    <!-- STEPPER NAVIGASI MODUL (6 LANGKAH) -->
    <div class="stepper">
        <a href="identifikasi_kematian.php?id=<?= $id_kasus ?>" class="step-item">
            <span class="step-num">1</span> 1. Identifikasi Kasus
        </a>
        <a href="data_kejadian.php?id=<?= $id_kasus ?>" class="step-item active">
            <span class="step-num">2</span> 2. Data Kejadian
        </a>
        <a href="data_klinis.php?id=<?= $id_kasus ?>" class="step-item">
            <span class="step-num">3</span> 3. Data Klinis
        </a>
        <a href="rangkaian_penyebab.php?id=<?= $id_kasus ?>" class="step-item">
            <span class="step-num">4</span> 4. Rangkaian Penyebab
        </a>
        <a href="external_cause.php?id=<?= $id_kasus ?>" class="step-item">
            <span class="step-num">5</span> 5. External Cause
        </a>
        <a href="verifikasi.php?id=<?= $id_kasus ?>" class="step-item">
            <span class="step-num">6</span> 6. Verifikasi
        </a>
    </div>

    <?php if(!empty($msg_error)): ?>
        <div class="alert-error">⚠️ <?= htmlspecialchars($msg_error) ?></div>
    <?php endif; ?>

    <form method="POST" action="" id="formKejadian">
        <input type="hidden" name="is_draft" id="is_draft" value="0">

        <!-- CARD: INFORMASI KEJADIAN -->
        <div class="card">
            <div class="card-title">
                📄 Informasi Kejadian
            </div>

            <div class="form-grid-top">
                <!-- KRONOLOGI KEJADIAN -->
                <div class="form-group" style="margin-bottom:0;">
                    <label>Kronologi Kejadian <span>*</span></label>
                    <textarea name="kronologi_kejadian" id="kronologi_kejadian" class="form-control" style="height: 190px;" maxlength="2000" placeholder="Jelaskan kronologi kejadian..." required oninput="updateCharCount(this, 'count_kronologi')"><?= htmlspecialchars($data['kronologi_kejadian']) ?></textarea>
                    <div class="char-count"><span id="count_kronologi">0</span>/2000</div>
                </div>

                <!-- TANGGAL, WAKTU, SUMBER, & BUKTI -->
                <div>
                    <div class="form-grid-2" style="margin-bottom:0;">
                        <div class="form-group">
                            <label>Tanggal Kejadian <span>*</span></label>
                            <input type="date" name="tgl_kejadian" class="form-control" value="<?= htmlspecialchars($data['tgl_kejadian']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Waktu Kejadian <span>*</span></label>
                            <input type="time" name="waktu_kejadian" class="form-control" value="<?= htmlspecialchars($data['waktu_kejadian']) ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Sumber Informasi <span>*</span></label>
                        <select name="sumber_informasi" class="form-control" required>
                            <option value="">Pilih sumber informasi</option>
                            <option value="Laporan Kepolisian" <?= $data['sumber_informasi'] == 'Laporan Kepolisian' ? 'selected' : '' ?>>Laporan Kepolisian</option>
                            <option value="Keluarga Pasien" <?= $data['sumber_informasi'] == 'Keluarga Pasien' ? 'selected' : '' ?>>Keluarga Pasien</option>
                            <option value="Rekan Kerja" <?= $data['sumber_informasi'] == 'Rekan Kerja' ? 'selected' : '' ?>>Rekan Kerja / Saksi Mata</option>
                            <option value="Laporan Tim K3" <?= $data['sumber_informasi'] == 'Laporan Tim K3' ? 'selected' : '' ?>>Laporan Tim K3</option>
                            <option value="Dokumen Rekam Medis" <?= $data['sumber_informasi'] == 'Dokumen Rekam Medis' ? 'selected' : '' ?>>Dokumen Rekam Medis</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom:0;">
                        <label>Bukti / Dokumen Pendukung</label>
                        <textarea name="bukti_dokumen" id="bukti_dokumen" class="form-control" style="height: 60px;" maxlength="1000" placeholder="Contoh: laporan kejadian, foto, hasil pemeriksaan, dll." oninput="updateCharCount(this, 'count_bukti')"><?= htmlspecialchars($data['bukti_dokumen']) ?></textarea>
                        <div class="char-count"><span id="count_bukti">0</span>/1000</div>
                    </div>
                </div>
            </div>

            <!-- LOWER GRID (DROPDOWNS & INFO TAMBAHAN) -->
            <div class="form-grid-2" style="margin-bottom:0;">
                <div>
                    <div class="form-group">
                        <label>Mekanisme Kejadian <span>*</span></label>
                        <select name="mekanisme_kejadian" class="form-control" required>
                            <option value="">Pilih mekanisme kejadian</option>
                            <option value="Terjatuh dari ketinggian" <?= $data['mekanisme_kejadian'] == 'Terjatuh dari ketinggian' ? 'selected' : '' ?>>Terjatuh dari ketinggian</option>
                            <option value="Tersengat arus listrik" <?= $data['mekanisme_kejadian'] == 'Tersengat arus listrik' ? 'selected' : '' ?>>Tersengat arus listrik</option>
                            <option value="Terjepit mesin / alat" <?= $data['mekanisme_kejadian'] == 'Terjepit mesin / alat' ? 'selected' : '' ?>>Terjepit mesin / alat</option>
                            <option value="Kecelakaan lalu lintas dinas" <?= $data['mekanisme_kejadian'] == 'Kecelakaan lalu lintas dinas' ? 'selected' : '' ?>>Kecelakaan lalu lintas dinas</option>
                            <option value="Tertimpa benda jatuh" <?= $data['mekanisme_kejadian'] == 'Tertimpa benda jatuh' ? 'selected' : '' ?>>Tertimpa benda jatuh</option>
                            <option value="Paparan bahan kimia / toksik" <?= $data['mekanisme_kejadian'] == 'Paparan bahan kimia / toksik' ? 'selected' : '' ?>>Paparan bahan kimia / toksik</option>
                            <option value="Lainnya" <?= $data['mekanisme_kejadian'] == 'Lainnya' ? 'selected' : '' ?>>Lainnya</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Aktivitas saat Kejadian <span>*</span></label>
                        <select name="aktivitas_kejadian" class="form-control" required>
                            <option value="">Pilih aktivitas</option>
                            <option value="Pengoperasian mesin / alat" <?= $data['aktivitas_kejadian'] == 'Pengoperasian mesin / alat' ? 'selected' : '' ?>>Pengoperasian mesin / alat</option>
                            <option value="Pemeliharaan / Perbaikan" <?= $data['aktivitas_kejadian'] == 'Pemeliharaan / Perbaikan' ? 'selected' : '' ?>>Pemeliharaan / Perbaikan</option>
                            <option value="Pengangkutan barang / material" <?= $data['aktivitas_kejadian'] == 'Pengangkutan barang / material' ? 'selected' : '' ?>>Pengangkutan barang / material</option>
                            <option value="Perjalanan dinas / kerja" <?= $data['aktivitas_kejadian'] == 'Perjalanan dinas / kerja' ? 'selected' : '' ?>>Perjalanan dinas / kerja</option>
                            <option value="Aktivitas rutin harian" <?= $data['aktivitas_kejadian'] == 'Aktivitas rutin harian' ? 'selected' : '' ?>>Aktivitas rutin harian</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom:0;">
                        <label>Faktor Lingkungan <span>*</span></label>
                        <select name="faktor_lingkungan" class="form-control" required>
                            <option value="">Pilih faktor lingkungan</option>
                            <option value="Lantai licin / Genangan" <?= $data['faktor_lingkungan'] == 'Lantai licin / Genangan' ? 'selected' : '' ?>>Lantai licin / Genangan</option>
                            <option value="Pencahayaan minim" <?= $data['faktor_lingkungan'] == 'Pencahayaan minim' ? 'selected' : '' ?>>Pencahayaan minim</option>
                            <option value="Ventilasi buruk / Gas berbahaya" <?= $data['faktor_lingkungan'] == 'Ventilasi buruk / Gas berbahaya' ? 'selected' : '' ?>>Ventilasi buruk / Gas berbahaya</option>
                            <option value="Suhu ekstrem" <?= $data['faktor_lingkungan'] == 'Suhu ekstrem' ? 'selected' : '' ?>>Suhu ekstrem</option>
                            <option value="Area sempit / Terbatas" <?= $data['faktor_lingkungan'] == 'Area sempit / Terbatas' ? 'selected' : '' ?>>Area sempit / Terbatas</option>
                            <option value="Tidak ada faktor khusus" <?= $data['faktor_lingkungan'] == 'Tidak ada faktor khusus' ? 'selected' : '' ?>>Tidak ada faktor khusus</option>
                        </select>
                    </div>
                </div>

                <div>
                    <div class="form-group">
                        <label>Lokasi Kejadian <span>*</span></label>
                        <select name="lokasi_kejadian" class="form-control" required>
                            <option value="">Pilih lokasi kejadian</option>
                            <option value="Area Pabrik / Industri" <?= $data['lokasi_kejadian'] == 'Area Pabrik / Industri' ? 'selected' : '' ?>>Area Pabrik / Industri</option>
                            <option value="Area Proyek / Konstruksi" <?= $data['lokasi_kejadian'] == 'Area Proyek / Konstruksi' ? 'selected' : '' ?>>Area Proyek / Konstruksi</option>
                            <option value="Jalan Raya (Perjalanan Dinas)" <?= $data['lokasi_kejadian'] == 'Jalan Raya (Perjalanan Dinas)' ? 'selected' : '' ?>>Jalan Raya (Perjalanan Dinas)</option>
                            <option value="Kantor / Gedung Kerja" <?= $data['lokasi_kejadian'] == 'Kantor / Gedung Kerja' ? 'selected' : '' ?>>Kantor / Gedung Kerja</option>
                            <option value="Lainnya" <?= $data['lokasi_kejadian'] == 'Lainnya' ? 'selected' : '' ?>>Lainnya</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Objek / Alat / Bahan <span>*</span></label>
                        <select name="objek_alat_bahan" class="form-control" required>
                            <option value="">Pilih objek / alat / bahan</option>
                            <option value="Mesin produksi" <?= $data['objek_alat_bahan'] == 'Mesin produksi' ? 'selected' : '' ?>>Mesin produksi</option>
                            <option value="Alat berat / Crane / Forklift" <?= $data['objek_alat_bahan'] == 'Alat berat / Crane / Forklift' ? 'selected' : '' ?>>Alat berat / Crane / Forklift</option>
                            <option value="Arus listrik tegangan tinggi" <?= $data['objek_alat_bahan'] == 'Arus listrik tegangan tinggi' ? 'selected' : '' ?>>Arus listrik tegangan tinggi</option>
                            <option value="Bahan kimia / Berbahaya" <?= $data['objek_alat_bahan'] == 'Bahan kimia / Berbahaya' ? 'selected' : '' ?>>Bahan kimia / Berbahaya</option>
                            <option value="Kendaraan bermotor" <?= $data['objek_alat_bahan'] == 'Kendaraan bermotor' ? 'selected' : '' ?>>Kendaraan bermotor</option>
                            <option value="Lainnya" <?= $data['objek_alat_bahan'] == 'Lainnya' ? 'selected' : '' ?>>Lainnya</option>
                        </select>
                    </div>

                    <!-- BOX INFORMASI TAMBAHAN -->
                    <div class="info-box">
                        <div class="info-box-title">ℹ️ Informasi Tambahan</div>
                        <div class="info-box-text">
                            Jika terdapat informasi yang belum diketahui atau belum tersedia, Anda dapat menandainya pada opsi di bawah ini.
                        </div>
                        <div class="checkbox-group">
                            <label class="checkbox-item">
                                <input type="radio" name="info_tambahan" value="Tidak diketahui" <?= $data['info_tambahan'] == 'Tidak diketahui' ? 'checked' : '' ?>>
                                <div>
                                    <strong>Tidak diketahui</strong>
                                    <small>Informasi tidak diketahui saat ini.</small>
                                </div>
                            </label>
                            <label class="checkbox-item">
                                <input type="radio" name="info_tambahan" value="Belum tersedia" <?= $data['info_tambahan'] == 'Belum tersedia' ? 'checked' : '' ?>>
                                <div>
                                    <strong>Belum tersedia</strong>
                                    <small>Informasi belum tersedia di rekam medis / sumber lain.</small>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- BUTTON ACTIONS -->
        <div class="form-actions">
            <button type="button" class="btn btn-secondary" onclick="simpanDraft()">
                ☕ Simpan Draft
            </button>
            <a href="identifikasi_kematian.php?id=<?= $id_kasus ?>" class="btn btn-secondary">
                ‹ Kembali
            </a>
            <button type="submit" class="btn btn-primary">
                Lanjut ke Data Klinis ›
            </button>
        </div>
    </form>

    <script>
        function updateCharCount(textarea, targetId) {
            document.getElementById(targetId).innerText = textarea.value.length;
        }

        document.addEventListener('DOMContentLoaded', function() {
            ['kronologi_kejadian', 'bukti_dokumen'].forEach(id => {
                const el = document.getElementById(id);
                if (el) updateCharCount(el, 'count_' + id.split('_')[0]);
            });
        });

        function simpanDraft() {
            document.getElementById('is_draft').value = "1";
            document.getElementById('formKejadian').submit();
        }
    </script>

</body>
</html>