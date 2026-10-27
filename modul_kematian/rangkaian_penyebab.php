<?php
session_start();

// Koneksi ke Database SIMRS Utama
$path_koneksi = __DIR__ . '/../koneksi.php';
if (file_exists($path_koneksi)) {
    include $path_koneksi;
}

$id_kasus = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$msg_error = "";

// Variabel default form WHO Cause of Death
$data = [
    'cause_a'          => '',
    'interval_a'       => '',
    'cause_b'          => '',
    'interval_b'       => '',
    'cause_c'          => '',
    'interval_c'       => '',
    'cause_d'          => '',
    'interval_d'       => '',
    'underlying_cause' => '',
    'icd_underlying'   => '',
    'part_2_conditions' => '',
    'catatan_penyebab' => ''
];

// Ambil data jika ID Kasus tersedia di database
if ($id_kasus > 0 && isset($koneksi) && $koneksi) {
    $q_penyebab = mysqli_query($koneksi, "SELECT * FROM rangkaian_penyebab_kematian WHERE id_kasus = '$id_kasus'");
    if ($q_penyebab && mysqli_num_rows($q_penyebab) > 0) {
        $row = mysqli_fetch_assoc($q_penyebab);
        $data = array_merge($data, $row);
    }
}

// Proses Simpan / Update Rangkaian Penyebab
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cause_a   = mysqli_real_escape_string($koneksi ?? null, $_POST['cause_a'] ?? '');
    $interval_a= mysqli_real_escape_string($koneksi ?? null, $_POST['interval_a'] ?? '');
    $cause_b   = mysqli_real_escape_string($koneksi ?? null, $_POST['cause_b'] ?? '');
    $interval_b= mysqli_real_escape_string($koneksi ?? null, $_POST['interval_b'] ?? '');
    $cause_c   = mysqli_real_escape_string($koneksi ?? null, $_POST['cause_c'] ?? '');
    $interval_c= mysqli_real_escape_string($koneksi ?? null, $_POST['interval_c'] ?? '');
    $cause_d   = mysqli_real_escape_string($koneksi ?? null, $_POST['cause_d'] ?? '');
    $interval_d= mysqli_real_escape_string($koneksi ?? null, $_POST['interval_d'] ?? '');
    
    $ucd       = mysqli_real_escape_string($koneksi ?? null, $_POST['underlying_cause'] ?? '');
    $icd_ucd   = mysqli_real_escape_string($koneksi ?? null, $_POST['icd_underlying'] ?? '');
    $part_2    = mysqli_real_escape_string($koneksi ?? null, $_POST['part_2_conditions'] ?? '');
    $catatan   = mysqli_real_escape_string($koneksi ?? null, $_POST['catatan_penyebab'] ?? '');

    if (isset($koneksi) && $koneksi && $id_kasus > 0) {
        $q_cek = mysqli_query($koneksi, "SELECT id_penyebab FROM rangkaian_penyebab_kematian WHERE id_kasus = '$id_kasus'");
        if ($q_cek && mysqli_num_rows($q_cek) > 0) {
            $sql = "UPDATE rangkaian_penyebab_kematian SET 
                    cause_a='$cause_a', interval_a='$interval_a',
                    cause_b='$cause_b', interval_b='$interval_b',
                    cause_c='$cause_c', interval_c='$interval_c',
                    cause_d='$cause_d', interval_d='$interval_d',
                    underlying_cause='$ucd', icd_underlying='$icd_ucd',
                    part_2_conditions='$part_2', catatan_penyebab='$catatan' 
                    WHERE id_kasus='$id_kasus'";
        } else {
            $sql = "INSERT INTO rangkaian_penyebab_kematian 
                    (id_kasus, cause_a, interval_a, cause_b, interval_b, cause_c, interval_c, cause_d, interval_d, underlying_cause, icd_underlying, part_2_conditions, catatan_penyebab) 
                    VALUES 
                    ('$id_kasus', '$cause_a', '$interval_a', '$cause_b', '$interval_b', '$cause_c', '$interval_c', '$cause_d', '$interval_d', '$ucd', '$icd_ucd', '$part_2', '$catatan')";
        }

        if (mysqli_query($koneksi, $sql)) {
            // Update juga underlying cause di tabel utama
            mysqli_query($koneksi, "UPDATE kasus_kematian_kerja SET underlying_cause='$ucd' WHERE id_kasus='$id_kasus'");
            
            echo "<script>alert('Rangkaian Penyebab Kematian Berhasil Disimpan!'); window.location='external_cause.php?id=$id_kasus';</script>";
            exit;
        } else {
            $msg_error = "Gagal menyimpan data: " . mysqli_error($koneksi);
        }
    } else {
        // Simulasi Mode tanpa DB
        echo "<script>alert('[SIMULASI] Rangkaian Penyebab Berhasil Disimpan!'); window.location='external_cause.php?id=" . ($id_kasus ?: 1) . "';</script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rangkaian Penyebab Kematian - WHO Format</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: #f8fafc; color: #0f172a; padding: 24px; }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .page-title h2 { font-size: 20px; font-weight: 700; color: #0f172a; }
        .page-title p { font-size: 13px; color: #64748b; margin-top: 2px; }

        /* STEPPER NAVIGASI MODUL */
        .stepper {
            display: flex;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 24px;
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

        /* CARD FORM */
        .card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            padding: 24px;
            margin-bottom: 20px;
        }
        .card-header {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 12px;
        }

        .who-row {
            display: grid;
            grid-template-columns: 80px 1fr 220px;
            gap: 12px;
            align-items: center;
            margin-bottom: 12px;
        }

        .who-label {
            font-weight: 700;
            font-size: 13px;
            color: #1e293b;
            background: #f1f5f9;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
        }

        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: 12.5px; font-weight: 600; color: #334155; }

        .form-control {
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 13px;
            outline: none;
            transition: all 0.2s;
            background: #ffffff;
        }
        .form-control:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }

        .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }

        textarea.form-control { resize: vertical; min-height: 80px; }

        .info-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 12.5px;
            color: #1e40af;
            margin-bottom: 18px;
            line-height: 1.5;
        }

        /* BUTTON FOOTER */
        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
        }
        .btn {
            padding: 11px 22px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .btn-secondary { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
        .btn-secondary:hover { background: #e2e8f0; }
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

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div class="page-title">
            <h2>Rangkaian Penyebab Kematian (WHO Format)</h2>
            <p>Langkah 4: Penentuan urutan kausal kematian (Part I: Line A-D) dan faktor penunjang lainnya (Part II).</p>
        </div>
        <a href="data_klinis.php?id=<?= $id_kasus ?>" class="btn btn-secondary">
            ← Kembali ke Langkah 3
        </a>
    </div>

    <!-- STEPPER NAVIGASI -->
    <div class="stepper">
        <a href="identifikasi_kematian.php?id=<?= $id_kasus ?>" class="step-item">
            <span class="step-num">1</span> 1. Identifikasi Kasus
        </a>
        <a href="data_kejadian.php?id=<?= $id_kasus ?>" class="step-item">
            <span class="step-num">2</span> 2. Data Kejadian
        </a>
        <a href="data_klinis.php?id=<?= $id_kasus ?>" class="step-item">
            <span class="step-num">3</span> 3. Data Klinis
        </a>
        <a href="#" class="step-item active">
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

    <form method="POST" action="">
        <!-- CARD 1: PART I - URUTAN PENYEBAB LANGSUNG HINGGA DASAR (LINE A - D) -->
        <div class="card">
            <div class="card-header">
                📜 WHO Part I: Rangkaian Urutan Penyebab Kematian
            </div>

            <div class="info-box">
                💡 <strong>Petunjuk WHO:</strong> Isilah urutan penyakit dari yang paling akhir (langsung menyebabkan kematian) di Line A, hingga kondisi paling awal (Underlying Cause) di Line D.
            </div>

            <!-- LINE A -->
            <div class="who-row">
                <div class="who-label">Baris (a)</div>
                <div class="form-group">
                    <input type="text" name="cause_a" class="form-control" placeholder="Penyebab Langsung Kematian (Direct Cause)... e.g. Syok Hipovolemik / Edema Paru" value="<?= htmlspecialchars($data['cause_a']) ?>" required>
                </div>
                <div class="form-group">
                    <input type="text" name="interval_a" class="form-control" placeholder="Interval Waktu (e.g. 15 menit)" value="<?= htmlspecialchars($data['interval_a']) ?>">
                </div>
            </div>

            <!-- LINE B -->
            <div class="who-row">
                <div class="who-label">Baris (b)</div>
                <div class="form-group">
                    <input type="text" name="cause_b" class="form-control" placeholder="Akibat dari (Due to / As a consequence of)... e.g. Perdarahan Masif Intraabdomen" value="<?= htmlspecialchars($data['cause_b']) ?>">
                </div>
                <div class="form-group">
                    <input type="text" name="interval_b" class="form-control" placeholder="Interval Waktu (e.g. 2 jam)" value="<?= htmlspecialchars($data['interval_b']) ?>">
                </div>
            </div>

            <!-- LINE C -->
            <div class="who-row">
                <div class="who-label">Baris (c)</div>
                <div class="form-group">
                    <input type="text" name="cause_c" class="form-control" placeholder="Akibat dari (Due to / As a consequence of)... e.g. Ruptur Hepar & Spleen" value="<?= htmlspecialchars($data['cause_c']) ?>">
                </div>
                <div class="form-group">
                    <input type="text" name="interval_c" class="form-control" placeholder="Interval Waktu (e.g. 3 jam)" value="<?= htmlspecialchars($data['interval_c']) ?>">
                </div>
            </div>

            <!-- LINE D -->
            <div class="who-row">
                <div class="who-label">Baris (d)</div>
                <div class="form-group">
                    <input type="text" name="cause_d" class="form-control" placeholder="Penyebab Utama Paling Dasar (Underlying Cause)... e.g. Trauma Tumpul Abdomen ec Kecelakaan Kerja" value="<?= htmlspecialchars($data['cause_d']) ?>">
                </div>
                <div class="form-group">
                    <input type="text" name="interval_d" class="form-control" placeholder="Interval Waktu (e.g. 4 jam)" value="<?= htmlspecialchars($data['interval_d']) ?>">
                </div>
            </div>
        </div>

        <!-- CARD 2: UNDERLYING CAUSE & PART II (KONDISI BERKONTRIBUSI) -->
        <div class="card">
            <div class="card-header">
                🎯 Penetapan Underlying Cause of Death & Part II
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label>Underlying Cause of Death (Penyebab Dasar Yang Disimpulkan) <span>*</span></label>
                    <input type="text" name="underlying_cause" class="form-control" placeholder="Diagnosis dasar yang memicu seluruh rangkaian kematian..." value="<?= htmlspecialchars($data['underlying_cause']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Kode ICD-10 Underlying Cause</label>
                    <input type="text" name="icd_underlying" class="form-control" placeholder="Kode ICD-10 untuk underlying cause..." value="<?= htmlspecialchars($data['icd_underlying']) ?>">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label>WHO Part II: Kondisi Komorbid / Signifikan Lain Yang Memperberat Kematian</label>
                <textarea name="part_2_conditions" class="form-control" placeholder="Tuliskan penyakit/kondisi lain yang berkontribusi tetapi tidak secara langsung masuk dalam urutan Line A-D (e.g. Anemia Berat, Hipertensi)..."><?= htmlspecialchars($data['part_2_conditions']) ?></textarea>
            </div>

            <div class="form-group">
                <label>Catatan Penalaran Medis DPJP / Tim K3RS</label>
                <textarea name="catatan_penyebab" class="form-control" placeholder="Pertimbangan klinis yang mendasari penentuan penyebab dasar kematian..."><?= htmlspecialchars($data['catatan_penyebab']) ?></textarea>
            </div>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="form-actions">
            <a href="data_klinis.php?id=<?= $id_kasus ?>" class="btn btn-secondary">← Langkah Sebelumnya</a>
            <button type="submit" class="btn btn-primary">
                Simpan & Lanjut ke External Cause →
            </button>
        </div>
    </form>

</body>
</html>