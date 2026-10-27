<?php
session_start();

// Koneksi ke Database SIMRS Utama
$path_koneksi = __DIR__ . '/../koneksi.php';
if (file_exists($path_koneksi)) {
    include $path_koneksi;
}

$id_kasus = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$msg_error = "";

// Variabel default form External Cause
$data = [
    'kode_external_cause'   => '',
    'deskripsi_external'    => '',
    'kode_tempat'           => '.0 Tempat Kerja / Area Industri / Konstruksi',
    'kode_aktivitas'        => '.1 Sedang Bekerja Dengan Gaji / Tugas Dinas',
    'kategori_penyebab_luar'=> 'Kecelakaan Kerja Traumatik',
    'detail_mekanisme'      => '',
    'pertimbangan_coder'    => ''
];

// Ambil data jika ID Kasus tersedia
if ($id_kasus > 0 && isset($koneksi) && $koneksi) {
    $q_ext = mysqli_query($koneksi, "SELECT * FROM external_cause_k3rs WHERE id_kasus = '$id_kasus'");
    if ($q_ext && mysqli_num_rows($q_ext) > 0) {
        $row = mysqli_fetch_assoc($q_ext);
        $data = array_merge($data, $row);
    }
}

// Proses Simpan / Update Data External Cause
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_ext     = mysqli_real_escape_string($koneksi ?? null, $_POST['kode_external_cause'] ?? '');
    $desk_ext     = mysqli_real_escape_string($koneksi ?? null, $_POST['deskripsi_external'] ?? '');
    $kode_tempat  = mysqli_real_escape_string($koneksi ?? null, $_POST['kode_tempat'] ?? '');
    $kode_akt     = mysqli_real_escape_string($koneksi ?? null, $_POST['kode_aktivitas'] ?? '');
    $kat_luar     = mysqli_real_escape_string($koneksi ?? null, $_POST['kategori_penyebab_luar'] ?? '');
    $detail_mek   = mysqli_real_escape_string($koneksi ?? null, $_POST['detail_mekanisme'] ?? '');
    $pertimbangan = mysqli_real_escape_string($koneksi ?? null, $_POST['pertimbangan_coder'] ?? '');

    if (isset($koneksi) && $koneksi && $id_kasus > 0) {
        $q_cek = mysqli_query($koneksi, "SELECT id_external FROM external_cause_k3rs WHERE id_kasus = '$id_kasus'");
        if ($q_cek && mysqli_num_rows($q_cek) > 0) {
            $sql = "UPDATE external_cause_k3rs SET 
                    kode_external_cause='$kode_ext', deskripsi_external='$desk_ext', 
                    kode_tempat='$kode_tempat', kode_aktivitas='$kode_akt', 
                    kategori_penyebab_luar='$kat_luar', detail_mekanisme='$detail_mek', 
                    pertimbangan_coder='$pertimbangan' 
                    WHERE id_kasus='$id_kasus'";
        } else {
            $sql = "INSERT INTO external_cause_k3rs 
                    (id_kasus, kode_external_cause, deskripsi_external, kode_tempat, kode_aktivitas, kategori_penyebab_luar, detail_mekanisme, pertimbangan_coder) 
                    VALUES 
                    ('$id_kasus', '$kode_ext', '$desk_ext', '$kode_tempat', '$kode_akt', '$kat_luar', '$detail_mek', '$pertimbangan')";
        }

        if (mysqli_query($koneksi, $sql)) {
            // Update kode external cause di tabel utama kasus_kematian_kerja
            mysqli_query($koneksi, "UPDATE kasus_kematian_kerja SET kode_external_cause='$kode_ext' WHERE id_kasus='$id_kasus'");
            
            echo "<script>alert('Pengkodean External Cause ICD-10 Berhasil Disimpan!'); window.location='verifikasi.php?id=$id_kasus';</script>";
            exit;
        } else {
            $msg_error = "Gagal menyimpan data: " . mysqli_error($koneksi);
        }
    } else {
        // Simulasi Mode tanpa DB
        echo "<script>alert('[SIMULASI] Data External Cause Berhasil Disimpan!'); window.location='verifikasi.php?id=" . ($id_kasus ?: 1) . "';</script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengkodean External Cause - Modul K3RS</title>
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

        .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
        .form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 16px; }

        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: 12.5px; font-weight: 600; color: #334155; }
        .form-group label span { color: #ef4444; }

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

        textarea.form-control { resize: vertical; min-height: 80px; }

        /* HELPER BOX REFERENSI KODE K3 */
        .icd-helper-box {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 14px;
            margin-bottom: 16px;
        }
        .icd-helper-title { font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 8px; text-transform: uppercase; }
        .icd-tags { display: flex; flex-wrap: wrap; gap: 6px; }
        .icd-tag-item {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 600;
            color: #1e293b;
            cursor: pointer;
            transition: all 0.15s;
        }
        .icd-tag-item:hover { background: #2563eb; color: #ffffff; border-color: #2563eb; }

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
            <h2>Penelusuran Kode External Cause ICD-10</h2>
            <p>Langkah 5: Penentuan kode ICD-10 penyebab luar (V01–Y98), karakter tempat (.0-.9), dan aktivitas (.0-.9).</p>
        </div>
        <a href="rangkaian_penyebab.php?id=<?= $id_kasus ?>" class="btn btn-secondary">
            ← Kembali ke Langkah 4
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
        <a href="rangkaian_penyebab.php?id=<?= $id_kasus ?>" class="step-item">
            <span class="step-num">4</span> 4. Rangkaian Penyebab
        </a>
        <a href="#" class="step-item active">
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
        <!-- CARD 1: PENGKODEAN KODE EXTERNAL CAUSE UTAMA -->
        <div class="card">
            <div class="card-header">
                🏷️ Kode Utama External Cause (ICD-10 V01–Y98)
            </div>

            <!-- HELPER PINDAH CEPAT KODE K3 POPULER -->
            <div class="icd-helper-box">
                <div class="icd-helper-title">⚡ Referensi Cepat Kode External Cause K3 Populer (Klik untuk memilih):</div>
                <div class="icd-tags">
                    <span class="icd-tag-item" onclick="pilihKode('W87.0', 'Tersengat Arus Listrik Bertegangan di Area Industri/Konstruksi')">⚡ W87.0 (Tersengat Arus Listrik)</span>
                    <span class="icd-tag-item" onclick="pilihKode('W13.0', 'Jatuh dari / Melalui Bangunan atau Struktur Perancah')">🏗️ W13.0 (Jatuh dari Perancah)</span>
                    <span class="icd-tag-item" onclick="pilihKode('W23.0', 'Tersangkut, Terjepit, atau Terbentur Mesin/Conveyor Industri')">⚙️ W23.0 (Terjepit Mesin)</span>
                    <span class="icd-tag-item" onclick="pilihKode('V89.2', 'Kecelakaan Lalu Lintas Kendaraan Motor Perjalanan Dinas')">🚗 V89.2 (KLL Perjalanan Kerja)</span>
                    <span class="icd-tag-item" onclick="pilihKode('X59.0', 'Paparan Faktor Penyebab Lain Yang Tidak Spesifik')">❓ X59.0 (Penyebab Tidak Spesifik)</span>
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label>Kode ICD-10 External Cause Utama <span>*</span></label>
                    <input type="text" id="kode_external_cause" name="kode_external_cause" class="form-control" placeholder="Contoh: W87.0 / W13.0 / V89.2" value="<?= htmlspecialchars($data['kode_external_cause']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Deskripsi Resmi ICD-10 <span>*</span></label>
                    <input type="text" id="deskripsi_external" name="deskripsi_external" class="form-control" placeholder="Deskripsi lengkap berdasarkan buku ICD-10..." value="<?= htmlspecialchars($data['deskripsi_external']) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Kategori Klasifikasi Penyebab Luar K3RS</label>
                <select name="kategori_penyebab_luar" class="form-control">
                    <option value="Kecelakaan Kerja Traumatik" <?= $data['kategori_penyebab_luar'] == 'Kecelakaan Kerja Traumatik' ? 'selected' : '' ?>>Kecelakaan Kerja Traumatik (Mekanik / Listrik / Jatuh)</option>
                    <option value="Kecelakaan Transportasi Dinas" <?= $data['kategori_penyebab_luar'] == 'Kecelakaan Transportasi Dinas' ? 'selected' : '' ?>>Kecelakaan Transportasi Dinas / Commuting</option>
                    <option value="Paparan Bahan Kimia / Berbahaya" <?= $data['kategori_penyebab_luar'] == 'Paparan Bahan Kimia / Berbahaya' ? 'selected' : '' ?>>Paparan Bahan Kimia / Toksik / Biologi</option>
                    <option value="Faktor Fisik / Lingkungan Ekstrem" <?= $data['kategori_penyebab_luar'] == 'Faktor Fisik / Lingkungan Ekstrem' ? 'selected' : '' ?>>Faktor Fisik (Suhu Ekstrem, Radiasi, Kebisingan)</option>
                    <option value="Penyakit Akibat Kerja (PAK)" <?= $data['kategori_penyebab_luar'] == 'Penyakit Akibat Kerja (PAK)' ? 'selected' : '' ?>>Penyakit Akibat Kerja (PAK) Kronis</option>
                </select>
            </div>
        </div>

        <!-- CARD 2: KODE TAMBAHAN PLACE & ACTIVITY (.0 - .9) -->
        <div class="card">
            <div class="card-header">
                📍 Kode Karakter Tambahan (Place & Activity Subdivision)
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label>Kode Tempat Kejadian (Place of Occurrence .0-.9)</label>
                    <select name="kode_tempat" class="form-control">
                        <option value=".0 Tempat Kerja / Area Industri / Konstruksi" <?= strpos($data['kode_tempat'], '.0') !== false ? 'selected' : '' ?>>.0 Industrial and construction area (Pabrik, Proyek, Bengkel)</option>
                        <option value=".1 Area Pertanian / Perkebunan" <?= strpos($data['kode_tempat'], '.1') !== false ? 'selected' : '' ?>>.1 Farm / Agricultural area</option>
                        <option value=".2 Jalan Raya / Sarana Transportasi" <?= strpos($data['kode_tempat'], '.2') !== false ? 'selected' : '' ?>>.2 Street and highway (Jalan Raya)</option>
                        <option value=".3 Tempat Olahraga" <?= strpos($data['kode_tempat'], '.3') !== false ? 'selected' : '' ?>>.3 Trade and service area / Sports area</option>
                        <option value=".4 Bangunan Publik / Kantor" <?= strpos($data['kode_tempat'], '.4') !== false ? 'selected' : '' ?>>.4 School, other institution and public administrative area</option>
                        <option value=".9 Tempat Tidak Ditentukan" <?= strpos($data['kode_tempat'], '.9') !== false ? 'selected' : '' ?>>.9 Unspecified place</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Kode Aktivitas Saat Kejadian (Activity Code .0-.9)</label>
                    <select name="kode_aktivitas" class="form-control">
                        <option value=".0 Sedang Berolahraga" <?= strpos($data['kode_aktivitas'], '.0') !== false ? 'selected' : '' ?>>.0 While engaged in sports activity</option>
                        <option value=".1 Sedang Bekerja Dengan Gaji / Tugas Dinas" <?= strpos($data['kode_aktivitas'], '.1') !== false ? 'selected' : '' ?>>.1 While engaged in working for income / duty (Tugas Resmi)</option>
                        <option value=".2 Sedang Melakukan Pekerjaan Rumah" <?= strpos($data['kode_aktivitas'], '.2') !== false ? 'selected' : '' ?>>.2 While engaged in other types of work</option>
                        <option value=".8 Aktivitas Spesifik Lain" <?= strpos($data['kode_aktivitas'], '.8') !== false ? 'selected' : '' ?>>.8 While engaged in other specified activities</option>
                        <option value=".9 Aktivitas Tidak Ditentukan" <?= strpos($data['kode_aktivitas'], '.9') !== false ? 'selected' : '' ?>>.9 While engaged in unspecified activity</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- CARD 3: DETAIL ANALISIS CODER & PENALARAN K3 -->
        <div class="card">
            <div class="card-header">
                🔬 Analisis Coder / Perekam Medis (PMIK)
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label>Uraian Mekanisme Detail Penyebab Luar</label>
                <textarea name="detail_mekanisme" class="form-control" placeholder="Jelaskan secara spesifik interaksi antara manusia dan hazard lingkungan kerja yang memicu terjadinya cedera fatal..."><?= htmlspecialchars($data['detail_mekanisme']) ?></textarea>
            </div>

            <div class="form-group">
                <label>Pertimbangan Coder / Catatan Pemilihan Kode ICD-10</label>
                <textarea name="pertimbangan_coder" class="form-control" placeholder="Tuliskan catatan pertimbangan koding (kaidah pengkodean ICD-10 Volume 2)..."><?= htmlspecialchars($data['pertimbangan_coder']) ?></textarea>
            </div>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="form-actions">
            <a href="rangkaian_penyebab.php?id=<?= $id_kasus ?>" class="btn btn-secondary">← Langkah Sebelumnya</a>
            <button type="submit" class="btn btn-primary">
                Simpan & Lanjut ke Verifikasi →
            </button>
        </div>
    </form>

    <script>
        function pilihKode(kode, deskripsi) {
            document.getElementById('kode_external_cause').value = kode;
            document.getElementById('deskripsi_external').value = deskripsi;
        }
    </script>

</body>
</html>