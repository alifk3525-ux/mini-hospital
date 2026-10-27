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
    'no_rm'          => 'RM-00012546',
    'tgl_kematian'   => date('Y-m-d H:i:s'),
    'jenis_kasus'    => 'Kasus Kematian',
    'kategori'       => 'Non Trauma',
    'status_proses'  => 'Teridentifikasi',
    'status_coding'  => 'Dalam Proses'
];

// 2. DEFAULT FORM DATA KLINIS
$data = [
    'diagnosa_utama'          => '',
    'diagnosa_sekunder'       => '',
    'diagnosa_cedera'         => '',
    'pemeriksaan_penunjang'   => '',
    'pemeriksaan_fisik'       => '',
    'tindakan_medis'          => '',
    'tgl_waktu_tindakan'      => date('Y-m-d\TH:i'),
    'tempat_tindakan'         => 'Ruang ICU',
    'kondisi_terakhir'        => 'Tidak sadar',
    'kesadaran'               => 'GCS 3',
    'td'                      => '90/60',
    'rr'                      => '24',
    'suhu'                    => '37.5',
    'status_akhir'            => 'Meninggal',
    'tgl_waktu_meninggal'     => date('Y-m-d\TH:i'),
    'tempat_meninggal'        => 'Ruang ICU',
    'dokumen_sumber'          => ['Ringkasan Masuk & Keluar (RM)', 'Hasil Pemeriksaan Penunjang', 'Catatan Perawat'],
    'dokumen_lainnya'         => '',
    'kelengkapan_diagnosis'   => 'Tersedia',
    'kelengkapan_pemeriksaan' => 'Tersedia',
    'kelengkapan_tindakan'    => 'Tersedia',
    'kelengkapan_kondisi'     => 'Tersedia',
    'kelengkapan_outcome'     => 'Tersedia'
];

// 3. TARIK DATA OTOMATIS DARI SISTEM (DATABASE)
if ($id_kasus > 0 && isset($koneksi) && $koneksi) {
    try {
        // A. Tarik Ringkasan Kasus Utama
        $q_summary = mysqli_query($koneksi, "
            SELECT mkk.*, p.no_rm 
            FROM kasus_kematian_kerja mkk 
            LEFT JOIN pasien p ON mkk.no_rm = p.no_rm 
            WHERE mkk.id_kasus = '$id_kasus'
        ");
        if ($q_summary && mysqli_num_rows($q_summary) > 0) {
            $row_s = mysqli_fetch_assoc($q_summary);
            $summary['no_rm']          = !empty($row_s['no_rm']) ? $row_s['no_rm'] : $summary['no_rm'];
            $summary['tgl_kematian']   = !empty($row_s['tgl_kematian']) ? $row_s['tgl_kematian'] : $summary['tgl_kematian'];
            $summary['jenis_kasus']    = !empty($row_s['kategori_kasus']) ? $row_s['kategori_kasus'] : $summary['jenis_kasus'];
            $summary['status_proses']  = !empty($row_s['status_proses']) ? $row_s['status_proses'] : $summary['status_proses'];
            $summary['status_coding']  = !empty($row_s['status_coding']) ? $row_s['status_coding'] : 'Dalam Proses';
        }

        // B. Tarik Data Klinis Jika Sudah Ada di DB
        $q_klinis = mysqli_query($koneksi, "SELECT * FROM data_klinis_kematian WHERE id_kasus = '$id_kasus'");
        if ($q_klinis && mysqli_num_rows($q_klinis) > 0) {
            $row_k = mysqli_fetch_assoc($q_klinis);
            $row_k['dokumen_sumber'] = !empty($row_k['dokumen_sumber']) ? json_decode($row_k['dokumen_sumber'], true) : [];
            $data = array_merge($data, $row_k);
        } else {
            // Auto-pull dari tabel rekam_medis SIMRS jika ada
            $q_rm = mysqli_query($koneksi, "
                SELECT rm.* 
                FROM kasus_kematian_kerja mkk 
                JOIN rekam_medis rm ON mkk.no_registrasi = rm.no_registrasi 
                WHERE mkk.id_kasus = '$id_kasus'
            ");
            if ($q_rm && mysqli_num_rows($q_rm) > 0) {
                $rm = mysqli_fetch_assoc($q_rm);
                $data['diagnosa_utama']      = $rm['icd_utama'] ?? ($rm['diagnosa_utama'] ?? '');
                $data['diagnosa_sekunder']   = $rm['icd_sekunder_1'] ?? ($rm['diagnosa_sekunder_1'] ?? '');
                $data['pemeriksaan_penunjang'] = $rm['pemeriksaan_penunjang'] ?? '';
                $data['pemeriksaan_fisik']   = $rm['objektif'] ?? '';
                $data['tindakan_medis']      = $rm['plan_non_obat'] ?? '';
            }
        }
    } catch (mysqli_sql_exception $e) {
        $msg_error = "Error database: " . $e->getMessage();
    }
}

// 4. PROSES SIMPAN / UPDATE DATA KLINIS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $du           = mysqli_real_escape_string($koneksi ?? null, $_POST['diagnosa_utama'] ?? '');
    $ds           = mysqli_real_escape_string($koneksi ?? null, $_POST['diagnosa_sekunder'] ?? '');
    $d_cedera     = mysqli_real_escape_string($koneksi ?? null, $_POST['diagnosa_cedera'] ?? '');
    $penunjang    = mysqli_real_escape_string($koneksi ?? null, $_POST['pemeriksaan_penunjang'] ?? '');
    $fisik        = mysqli_real_escape_string($koneksi ?? null, $_POST['pemeriksaan_fisik'] ?? '');
    $tindakan     = mysqli_real_escape_string($koneksi ?? null, $_POST['tindakan_medis'] ?? '');
    $tgl_tindakan = $_POST['tgl_waktu_tindakan'] ?? date('Y-m-d H:i:s');
    $tmpt_tindak  = mysqli_real_escape_string($koneksi ?? null, $_POST['tempat_tindakan'] ?? '');
    $kondisi_t    = mysqli_real_escape_string($koneksi ?? null, $_POST['kondisi_terakhir'] ?? '');
    $kesadaran    = mysqli_real_escape_string($koneksi ?? null, $_POST['kesadaran'] ?? '');
    $td           = mysqli_real_escape_string($koneksi ?? null, $_POST['td'] ?? '');
    $rr           = mysqli_real_escape_string($koneksi ?? null, $_POST['rr'] ?? '');
    $suhu         = mysqli_real_escape_string($koneksi ?? null, $_POST['suhu'] ?? '');
    $st_akhir     = mysqli_real_escape_string($koneksi ?? null, $_POST['status_akhir'] ?? '');
    $tgl_mnggl    = $_POST['tgl_waktu_meninggal'] ?? date('Y-m-d H:i:s');
    $tmpt_mnggl   = mysqli_real_escape_string($koneksi ?? null, $_POST['tempat_meninggal'] ?? '');
    
    $doc_sumber   = isset($_POST['dokumen_sumber']) ? json_encode($_POST['dokumen_sumber']) : json_encode([]);
    $doc_lainnya  = mysqli_real_escape_string($koneksi ?? null, $_POST['dokumen_lainnya'] ?? '');

    $k_diag       = $_POST['kelengkapan_diagnosis'] ?? 'Tersedia';
    $k_pem        = $_POST['kelengkapan_pemeriksaan'] ?? 'Tersedia';
    $k_tind       = $_POST['kelengkapan_tindakan'] ?? 'Tersedia';
    $k_kond       = $_POST['kelengkapan_kondisi'] ?? 'Tersedia';
    $k_outc       = $_POST['kelengkapan_outcome'] ?? 'Tersedia';

    if (isset($koneksi) && $koneksi && $id_kasus > 0) {
        try {
            $q_cek = mysqli_query($koneksi, "SELECT id_klinis FROM data_klinis_kematian WHERE id_kasus = '$id_kasus'");
            if ($q_cek && mysqli_num_rows($q_cek) > 0) {
                $sql = "UPDATE data_klinis_kematian SET 
                        diagnosa_utama='$du', diagnosa_sekunder='$ds', diagnosa_cedera='$d_cedera', 
                        pemeriksaan_penunjang='$penunjang', pemeriksaan_fisik='$fisik', tindakan_medis='$tindakan', 
                        tgl_waktu_tindakan='$tgl_tindakan', tempat_tindakan='$tmpt_tindak', kondisi_terakhir='$kondisi_t', 
                        kesadaran='$kesadaran', td='$td', rr='$rr', suhu='$suhu', status_akhir='$st_akhir', 
                        tgl_waktu_meninggal='$tgl_mnggl', tempat_meninggal='$tmpt_mnggl', dokumen_sumber='$doc_sumber', 
                        dokumen_lainnya='$doc_lainnya', kelengkapan_diagnosis='$k_diag', kelengkapan_pemeriksaan='$k_pem', 
                        kelengkapan_tindakan='$k_tind', kelengkapan_kondisi='$k_kond', kelengkapan_outcome='$k_outc' 
                        WHERE id_kasus='$id_kasus'";
            } else {
                $sql = "INSERT INTO data_klinis_kematian 
                        (id_kasus, diagnosa_utama, diagnosa_sekunder, diagnosa_cedera, pemeriksaan_penunjang, pemeriksaan_fisik, tindakan_medis, tgl_waktu_tindakan, tempat_tindakan, kondisi_terakhir, kesadaran, td, rr, suhu, status_akhir, tgl_waktu_meninggal, tempat_meninggal, dokumen_sumber, dokumen_lainnya, kelengkapan_diagnosis, kelengkapan_pemeriksaan, kelengkapan_tindakan, kelengkapan_kondisi, kelengkapan_outcome) 
                        VALUES 
                        ('$id_kasus', '$du', '$ds', '$d_cedera', '$penunjang', '$fisik', '$tindakan', '$tgl_tindakan', '$tmpt_tindak', '$kondisi_t', '$kesadaran', '$td', '$rr', '$suhu', '$st_akhir', '$tgl_mnggl', '$tmpt_mnggl', '$doc_sumber', '$doc_lainnya', '$k_diag', '$k_pem', '$k_tind', '$k_kond', '$k_outc')";
            }

            if (mysqli_query($koneksi, $sql)) {
                $redirect = isset($_POST['is_draft']) ? "data_klinis.php?id=$id_kasus" : "rangkaian_penyebab.php?id=$id_kasus";
                echo "<script>alert('Data Klinis Berhasil Disimpan!'); window.location='$redirect';</script>";
                exit;
            }
        } catch (mysqli_sql_exception $e) {
            $msg_error = "Gagal menyimpan data: " . $e->getMessage();
        }
    } else {
        $redirect = isset($_POST['is_draft']) ? "data_klinis.php?id=1" : "rangkaian_penyebab.php?id=" . ($id_kasus ?: 1);
        echo "<script>alert('[SIMULASI] Data Klinis Berhasil Disimpan!'); window.location='$redirect';</script>";
        exit;
    }
}

// Format Tanggal Indonesia
function formatTglIndo($datetime) {
    if (!$datetime) return '-';
    $time = strtotime($datetime);
    $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return date('j', $time) . ' ' . $bulan[(int)date('n', $time)] . ' ' . date('Y', $time) . ' (' . date('H:i', $time) . ')';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Klinis - Modul K3RS</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: #f4f6f9; color: #1e293b; padding: 24px; }

        /* PAGE HEADER */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        .page-title h2 { font-size: 22px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 10px; }
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
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }
        .summary-label { font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; }
        .summary-value { font-size: 13.5px; font-weight: 700; color: #0f172a; margin-top: 1px; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 700; }
        .badge-success { background: #dcfce7; color: #15803d; }
        .badge-warning { background: #fef3c7; color: #d97706; }

        .btn-history {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: #334155;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }

        /* STEPPER NAVIGASI MODUL (6 LANGKAH) */
        .stepper {
            display: flex;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 20px;
            gap: 8px;
            overflow-x: auto;
        }
        .step-item {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 12.5px;
            font-weight: 600;
            color: #64748b;
            text-decoration: none;
            background: #f8fafc;
            white-space: nowrap;
        }
        .step-item.active { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
        .step-num {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #e2e8f0;
            color: #475569;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
        }
        .step-item.active .step-num { background: #2563eb; color: #ffffff; }

        /* GRID MAIN FORM LAYOUT */
        .main-grid {
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }

        /* CARD STYLE */
        .card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            padding: 20px;
            margin-bottom: 20px;
        }
        .card-title {
            font-size: 14.5px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 14px; }
        .form-group label { font-size: 12px; font-weight: 700; color: #334155; }
        .form-group label span { color: #ef4444; }

        .form-control {
            padding: 9px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 13px;
            outline: none;
            transition: all 0.2s;
            background: #ffffff;
            color: #0f172a;
        }
        .form-control:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }

        textarea.form-control { resize: vertical; }
        .char-count { font-size: 11px; color: #94a3b8; text-align: right; margin-top: 3px; }

        .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }

        /* CARD RIGHT SUB-GRID */
        .right-subgrid {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 16px;
        }

        /* INFO ALERT BOX */
        .info-alert {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 11.5px;
            color: #1e40af;
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-top: 12px;
        }

        /* KELENGKAPAN LIST */
        .kelengkapan-list { display: flex; flex-direction: column; gap: 12px; }
        .kelengkapan-item {
            display: flex;
            flex-direction: column;
            gap: 6px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f1f5f9;
        }
        .kelengkapan-item:last-child { border-bottom: none; }
        .kelengkapan-label { font-size: 12px; font-weight: 700; color: #334155; display: flex; align-items: center; gap: 6px; }
        .options-inline { display: flex; gap: 12px; font-size: 11.5px; color: #475569; }
        .options-inline label { display: flex; align-items: center; gap: 4px; cursor: pointer; font-weight: 500; }

        /* FOOTER ACTIONS */
        .form-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 12px;
        }
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-secondary { background: #ffffff; color: #475569; border: 1px solid #cbd5e1; }
        .btn-secondary:hover { background: #f8fafc; }
        .btn-primary { background: #2563eb; color: #ffffff; box-shadow: 0 2px 6px rgba(37, 99, 235, 0.2); }
        .btn-primary:hover { background: #1d4ed8; }

        .alert-error {
            background-color: #fef2f2;
            border: 1px solid #fca5a5;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

    <!-- KAMUS DATA ICD-10 AUTOMATIC DROPDOWN DARI DATABASE -->
    <datalist id="kamus-icd10">
        <?php
        if (isset($koneksi) && $koneksi) {
            $q_icd = mysqli_query($koneksi, "SELECT * FROM icd10");
            if ($q_icd) {
                while($icd = mysqli_fetch_assoc($q_icd)) {
                    echo "<option value='".$icd['kode']." - ".$icd['diagnosa']."'>";
                }
            }
        }
        ?>
    </datalist>

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div class="page-title">
            <h2>🩺 Data Klinis</h2>
            <p>Informasi klinis yang tercatat dalam rekam medis untuk mendukung penelusuran penyebab kematian. Data yang ditampilkan merupakan informasi yang terdokumentasi, bukan kesimpulan kausal.</p>
        </div>
    </div>

    <!-- SUMMARY BANNER PASIEN -->
    <div class="summary-banner">
        <div class="summary-item">
            <div class="summary-icon">🏥</div>
            <div>
                <div class="summary-label">No. RM</div>
                <div class="summary-value"><?= htmlspecialchars($summary['no_rm']) ?></div>
            </div>
        </div>

        <div class="summary-item">
            <div class="summary-icon">📅</div>
            <div>
                <div class="summary-label">Tanggal Kematian</div>
                <div class="summary-value"><?= formatTglIndo($summary['tgl_kematian']) ?></div>
            </div>
        </div>

        <div class="summary-item">
            <div class="summary-icon">👤</div>
            <div>
                <div class="summary-label">Jenis Kasus</div>
                <div class="summary-value"><?= htmlspecialchars($summary['jenis_kasus']) ?></div>
            </div>
        </div>

        <div class="summary-item">
            <div class="summary-icon">🏷️</div>
            <div>
                <div class="summary-label">Kategori</div>
                <div class="summary-value"><?= htmlspecialchars($summary['kategori']) ?></div>
            </div>
        </div>

        <div class="summary-item">
            <div class="summary-icon">🏠</div>
            <div>
                <div class="summary-label">Status Identifikasi</div>
                <div class="summary-value"><span class="badge badge-success"><?= htmlspecialchars($summary['status_proses']) ?></span></div>
            </div>
        </div>

        <div class="summary-item">
            <div class="summary-icon">🛡️</div>
            <div>
                <div class="summary-label">Status Coding</div>
                <div class="summary-value"><span class="badge badge-warning"><?= htmlspecialchars($summary['status_coding']) ?></span></div>
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
        <a href="data_kejadian.php?id=<?= $id_kasus ?>" class="step-item">
            <span class="step-num">2</span> 2. Data Kejadian
        </a>
        <a href="data_klinis.php?id=<?= $id_kasus ?>" class="step-item active">
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

    <form method="POST" action="" id="formKlinis">
        <input type="hidden" name="is_draft" id="is_draft" value="0">

        <!-- MAIN GRID CONTAINER -->
        <div class="main-grid">
            
            <!-- KOLOM KIRI -->
            <div>
                <!-- CARD 1: DIAGNOSIS REKAM MEDIS -->
                <div class="card">
                    <div class="card-title">
                        📄 1. Diagnosis yang Tercatat dalam Rekam Medis
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>Diagnosis Utama <span>*</span></label>
                            <input type="text" name="diagnosa_utama" list="kamus-icd10" class="form-control" placeholder="I10 - Hipertensi esensial (primer)" value="<?= htmlspecialchars($data['diagnosa_utama']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Diagnosis Sekunder / Penyerta</label>
                            <input type="text" name="diagnosa_sekunder" list="kamus-icd10" class="form-control" placeholder="E11.9 - Diabetes melitus tipe 2..." value="<?= htmlspecialchars($data['diagnosa_sekunder']) ?>">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom:0;">
                        <label>Diagnosis Cedera / Kondisi Lain</label>
                        <textarea name="diagnosa_cedera" id="diagnosa_cedera" class="form-control" style="height: 70px;" maxlength="500" placeholder="Tidak ada cedera traumatik yang tercatat pada rekam medis." oninput="updateCharCount(this, 'count_cedera')"><?= htmlspecialchars($data['diagnosa_cedera']) ?></textarea>
                        <div class="char-count"><span id="count_cedera">0</span>/500</div>
                    </div>
                </div>

                <!-- CARD 3: TINDAKAN MEDIS -->
                <div class="card">
                    <div class="card-title">
                        🩺 3. Tindakan Medis
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Tindakan yang Diberikan</label>
                            <textarea name="tindakan_medis" id="tindakan_medis" class="form-control" style="height: 105px;" maxlength="1000" placeholder="- Terapi antihipertensi&#10;- Terapi cairan&#10;- Monitoring ketat di ruang intensif" oninput="updateCharCount(this, 'count_tindakan')"><?= htmlspecialchars($data['tindakan_medis']) ?></textarea>
                            <div class="char-count"><span id="count_tindakan">0</span>/1000</div>
                        </div>

                        <div>
                            <div class="form-group">
                                <label>Tanggal dan Waktu Tindakan</label>
                                <input type="datetime-local" name="tgl_waktu_tindakan" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($data['tgl_waktu_tindakan'])) ?>">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Tempat Tindakan</label>
                                <select name="tempat_tindakan" class="form-control">
                                    <option value="Ruang ICU" <?= $data['tempat_tindakan'] == 'Ruang ICU' ? 'selected' : '' ?>>Ruang ICU</option>
                                    <option value="Ruang Rawat Inap" <?= $data['tempat_tindakan'] == 'Ruang Rawat Inap' ? 'selected' : '' ?>>Ruang Rawat Inap</option>
                                    <option value="IGD" <?= $data['tempat_tindakan'] == 'IGD' ? 'selected' : '' ?>>IGD</option>
                                    <option value="Kamar Operasi" <?= $data['tempat_tindakan'] == 'Kamar Operasi' ? 'selected' : '' ?>>Kamar Operasi</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CARD 5: OUTCOME -->
                <div class="card" style="margin-bottom: 0;">
                    <div class="card-title">
                        📍 5. Outcome
                    </div>

                    <div class="form-grid-3">
                        <div class="form-group">
                            <label>Status Akhir</label>
                            <select name="status_akhir" class="form-control">
                                <option value="Meninggal" <?= $data['status_akhir'] == 'Meninggal' ? 'selected' : '' ?>>Meninggal</option>
                                <option value="Dead on Arrival (DOA)" <?= $data['status_akhir'] == 'Dead on Arrival (DOA)' ? 'selected' : '' ?>>Meninggal Saat Tiba (DOA)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Tanggal dan Waktu Meninggal</label>
                            <input type="datetime-local" name="tgl_waktu_meninggal" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($data['tgl_waktu_meninggal'])) ?>">
                        </div>

                        <div class="form-group">
                            <label>Tempat Meninggal</label>
                            <select name="tempat_meninggal" class="form-control">
                                <option value="Ruang ICU" <?= $data['tempat_meninggal'] == 'Ruang ICU' ? 'selected' : '' ?>>Ruang ICU</option>
                                <option value="Ruang Rawat Inap" <?= $data['tempat_meninggal'] == 'Ruang Rawat Inap' ? 'selected' : '' ?>>Ruang Rawat Inap</option>
                                <option value="IGD" <?= $data['tempat_meninggal'] == 'IGD' ? 'selected' : '' ?>>IGD</option>
                                <option value="Luar RS" <?= $data['tempat_meninggal'] == 'Luar RS' ? 'selected' : '' ?>>Luar RS</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KOLOM KANAN -->
            <div>
                <!-- CARD 2: HASIL PEMERIKSAAN -->
                <div class="card">
                    <div class="card-title">
                        📑 2. Hasil Pemeriksaan
                    </div>

                    <div class="form-group">
                        <label>Pemeriksaan Penunjang</label>
                        <textarea name="pemeriksaan_penunjang" id="pemeriksaan_penunjang" class="form-control" style="height: 75px;" maxlength="1000" placeholder="- Laboratorium: Hb 11.2 g/dL, Leukosit 12.500/µL&#10;- EKG: Sinus takikardi" oninput="updateCharCount(this, 'count_penunjang')"><?= htmlspecialchars($data['pemeriksaan_penunjang']) ?></textarea>
                        <div class="char-count"><span id="count_penunjang">0</span>/1000</div>
                    </div>

                    <div class="form-group" style="margin-bottom:0;">
                        <label>Pemeriksaan Fisik</label>
                        <textarea name="pemeriksaan_fisik" id="pemeriksaan_fisik" class="form-control" style="height: 60px;" maxlength="1000" placeholder="Tekanan darah 180/110 mmHg, nadi 102x/menit, pernapasan 22x/menit, suhu 36.8°C." oninput="updateCharCount(this, 'count_fisik')"><?= htmlspecialchars($data['pemeriksaan_fisik']) ?></textarea>
                        <div class="char-count"><span id="count_fisik">0</span>/1000</div>
                    </div>
                </div>

                <!-- SUB-GRID RIGHT (CARD 4 & 6 vs CARD 7) -->
                <div class="right-subgrid">
                    <div>
                        <!-- CARD 4: KONDISI PASIEN -->
                        <div class="card">
                            <div class="card-title">
                                🩺 4. Kondisi Pasien
                            </div>

                            <div class="form-group">
                                <label>Kondisi Saat Terakhir</label>
                                <select name="kondisi_terakhir" class="form-control">
                                    <option value="Tidak sadar" <?= $data['kondisi_terakhir'] == 'Tidak sadar' ? 'selected' : '' ?>>Tidak sadar</option>
                                    <option value="Sadar" <?= $data['kondisi_terakhir'] == 'Sadar' ? 'selected' : '' ?>>Sadar</option>
                                    <option value="Gelisah / Gelisah Berat" <?= $data['kondisi_terakhir'] == 'Gelisah / Gelisah Berat' ? 'selected' : '' ?>>Gelisah / Gelisah Berat</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Kesadaran</label>
                                <select name="kesadaran" class="form-control">
                                    <option value="GCS 3" <?= $data['kesadaran'] == 'GCS 3' ? 'selected' : '' ?>>GCS 3</option>
                                    <option value="Compos Mentis" <?= $data['kesadaran'] == 'Compos Mentis' ? 'selected' : '' ?>>Compos Mentis</option>
                                    <option value="Somnolen" <?= $data['kesadaran'] == 'Somnolen' ? 'selected' : '' ?>>Somnolen</option>
                                    <option value="Sopor" <?= $data['kesadaran'] == 'Sopor' ? 'selected' : '' ?>>Sopor</option>
                                    <option value="Koma" <?= $data['kesadaran'] == 'Koma' ? 'selected' : '' ?>>Koma</option>
                                </select>
                            </div>

                            <div class="form-group" style="margin-bottom:0;">
                                <label>Tanda Vital Terakhir</label>
                                <div class="form-grid-3">
                                    <input type="text" name="td" class="form-control" placeholder="TD: 90/60" value="<?= htmlspecialchars($data['td']) ?>">
                                    <input type="text" name="rr" class="form-control" placeholder="RR: 24" value="<?= htmlspecialchars($data['rr']) ?>">
                                    <input type="text" name="suhu" class="form-control" placeholder="Suhu: 37.5" value="<?= htmlspecialchars($data['suhu']) ?>">
                                </div>
                            </div>
                        </div>

                        <!-- CARD 6: DOKUMEN SUMBER / REFERENSI -->
                        <div class="card" style="margin-bottom:0;">
                            <div class="card-title">
                                📂 6. Dokumen Sumber / Referensi
                            </div>

                            <div style="display: flex; flex-direction: column; gap: 8px; font-size: 12px; color: #334155;">
                                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                    <input type="checkbox" name="dokumen_sumber[]" value="Ringkasan Masuk & Keluar (RM)" <?= in_array('Ringkasan Masuk & Keluar (RM)', $data['dokumen_sumber']) ? 'checked' : '' ?>>
                                    Ringkasan Masuk & Keluar (RM)
                                </label>
                                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                    <input type="checkbox" name="dokumen_sumber[]" value="Hasil Pemeriksaan Penunjang" <?= in_array('Hasil Pemeriksaan Penunjang', $data['dokumen_sumber']) ? 'checked' : '' ?>>
                                    Hasil Pemeriksaan Penunjang
                                </label>
                                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                    <input type="checkbox" name="dokumen_sumber[]" value="Catatan Perawat" <?= in_array('Catatan Perawat', $data['dokumen_sumber']) ? 'checked' : '' ?>>
                                    Catatan Perawat
                                </label>
                                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                    <input type="checkbox" name="dokumen_sumber[]" value="Laporan Tindakan" <?= in_array('Laporan Tindakan', $data['dokumen_sumber']) ? 'checked' : '' ?>>
                                    Laporan Tindakan
                                </label>
                                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                    <input type="checkbox" name="dokumen_sumber[]" value="Dokumen Lainnya" <?= in_array('Dokumen Lainnya', $data['dokumen_sumber']) ? 'checked' : '' ?>>
                                    Dokumen Lainnya
                                </label>
                                <input type="text" name="dokumen_lainnya" class="form-control" style="margin-top:2px;" placeholder="Sebutkan dokumen lain..." value="<?= htmlspecialchars($data['dokumen_lainnya']) ?>">
                            </div>

                            <div class="info-alert">
                                ℹ️ Pastikan dokumen sumber telah diverifikasi oleh petugas yang berwenang.
                            </div>
                        </div>
                    </div>

                    <!-- CARD 7: KELENGKAPAN INFORMASI KLINIS -->
                    <div class="card" style="margin-bottom:0;">
                        <div class="card-title">
                            📋 7. Kelengkapan Informasi Klinis
                        </div>
                        <div style="font-size: 11px; font-weight:700; color: #64748b; margin-bottom: 12px;">Status ketersediaan data klinis</div>

                        <div class="kelengkapan-list">
                            <div class="kelengkapan-item">
                                <div class="kelengkapan-label">✔ Diagnosis</div>
                                <div class="options-inline">
                                    <label><input type="radio" name="kelengkapan_diagnosis" value="Tersedia" <?= $data['kelengkapan_diagnosis'] == 'Tersedia' ? 'checked' : '' ?>> Tersedia</label>
                                    <label><input type="radio" name="kelengkapan_diagnosis" value="Belum tersedia" <?= $data['kelengkapan_diagnosis'] == 'Belum tersedia' ? 'checked' : '' ?>> Belum tersedia</label>
                                    <label><input type="radio" name="kelengkapan_diagnosis" value="Tidak diketahui" <?= $data['kelengkapan_diagnosis'] == 'Tidak diketahui' ? 'checked' : '' ?>> Tidak diketahui</label>
                                </div>
                            </div>

                            <div class="kelengkapan-item">
                                <div class="kelengkapan-label">⚡ Pemeriksaan</div>
                                <div class="options-inline">
                                    <label><input type="radio" name="kelengkapan_pemeriksaan" value="Tersedia" <?= $data['kelengkapan_pemeriksaan'] == 'Tersedia' ? 'checked' : '' ?>> Tersedia</label>
                                    <label><input type="radio" name="kelengkapan_pemeriksaan" value="Belum tersedia" <?= $data['kelengkapan_pemeriksaan'] == 'Belum tersedia' ? 'checked' : '' ?>> Belum tersedia</label>
                                    <label><input type="radio" name="kelengkapan_pemeriksaan" value="Tidak diketahui" <?= $data['kelengkapan_pemeriksaan'] == 'Tidak diketahui' ? 'checked' : '' ?>> Tidak diketahui</label>
                                </div>
                            </div>

                            <div class="kelengkapan-item">
                                <div class="kelengkapan-label">🌱 Tindakan</div>
                                <div class="options-inline">
                                    <label><input type="radio" name="kelengkapan_tindakan" value="Tersedia" <?= $data['kelengkapan_tindakan'] == 'Tersedia' ? 'checked' : '' ?>> Tersedia</label>
                                    <label><input type="radio" name="kelengkapan_tindakan" value="Belum tersedia" <?= $data['kelengkapan_tindakan'] == 'Belum tersedia' ? 'checked' : '' ?>> Belum tersedia</label>
                                    <label><input type="radio" name="kelengkapan_tindakan" value="Tidak diketahui" <?= $data['kelengkapan_tindakan'] == 'Tidak diketahui' ? 'checked' : '' ?>> Tidak diketahui</label>
                                </div>
                            </div>

                            <div class="kelengkapan-item">
                                <div class="kelengkapan-label">💎 Kondisi pasien</div>
                                <div class="options-inline">
                                    <label><input type="radio" name="kelengkapan_kondisi" value="Tersedia" <?= $data['kelengkapan_kondisi'] == 'Tersedia' ? 'checked' : '' ?>> Tersedia</label>
                                    <label><input type="radio" name="kelengkapan_kondisi" value="Belum tersedia" <?= $data['kelengkapan_kondisi'] == 'Belum tersedia' ? 'checked' : '' ?>> Belum tersedia</label>
                                    <label><input type="radio" name="kelengkapan_kondisi" value="Tidak diketahui" <?= $data['kelengkapan_kondisi'] == 'Tidak diketahui' ? 'checked' : '' ?>> Tidak diketahui</label>
                                </div>
                            </div>

                            <div class="kelengkapan-item">
                                <div class="kelengkapan-label">🔴 Outcome</div>
                                <div class="options-inline">
                                    <label><input type="radio" name="kelengkapan_outcome" value="Tersedia" <?= $data['kelengkapan_outcome'] == 'Tersedia' ? 'checked' : '' ?>> Tersedia</label>
                                    <label><input type="radio" name="kelengkapan_outcome" value="Belum tersedia" <?= $data['kelengkapan_outcome'] == 'Belum tersedia' ? 'checked' : '' ?>> Belum tersedia</label>
                                    <label><input type="radio" name="kelengkapan_outcome" value="Tidak diketahui" <?= $data['kelengkapan_outcome'] == 'Tidak diketahui' ? 'checked' : '' ?>> Tidak diketahui</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- ACTION BUTTONS -->
        <div class="form-actions">
            <button type="button" class="btn btn-secondary" onclick="simpanDraft()">
                💾 Simpan Draft
            </button>
            <a href="data_kejadian.php?id=<?= $id_kasus ?>" class="btn btn-secondary">
                ← Kembali
            </a>
            <button type="submit" class="btn btn-primary">
                Lanjut ke Rangkaian Penyebab →
            </button>
        </div>
    </form>

    <script>
        function updateCharCount(textarea, targetId) {
            document.getElementById(targetId).innerText = textarea.value.length;
        }

        document.addEventListener('DOMContentLoaded', function() {
            ['diagnosa_cedera', 'tindakan_medis', 'pemeriksaan_penunjang', 'pemeriksaan_fisik'].forEach(id => {
                const el = document.getElementById(id);
                if (el) updateCharCount(el, 'count_' + id.split('_')[1]);
            });
        });

        function simpanDraft() {
            document.getElementById('is_draft').value = "1";
            document.getElementById('formKlinis').submit();
        }
    </script>

</body>
</html>