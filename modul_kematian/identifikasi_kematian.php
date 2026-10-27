<?php
session_start();

// 1. KONEKSI KE DATABASE SIMRS UTAMA
$path_koneksi = __DIR__ . '/../koneksi.php';
if (file_exists($path_koneksi)) {
    include $path_koneksi;
} else {
    include 'koneksi.php';
}

$id_kasus = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$no_rm_param = isset($_GET['rm']) ? mysqli_real_escape_string($koneksi, $_GET['rm']) : '';
$msg_sukses = "";
$msg_error = "";

// 2. VARIABEL DEFAULT FORM IDENTIFIKASI
$data = [
    'id_kasus'          => $id_kasus,
    'no_kasus'         => 'KSM-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
    'no_rm'            => '',
    'nama_pasien'      => '',
    'nik'              => '',
    'tgl_lahir'        => '',
    'jk'               => 'Laki-Laki',
    'pekerjaan'        => '',
    'unit_perusahaan'  => '',
    'tgl_kematian'     => date('Y-m-d\TH:i'),
    'lokasi_kematian'  => 'IGD / Rumah Sakit',
    'kategori_kasus'   => 'Kecelakaan Kerja (KK)',
    'status_proses'    => 'Perlu Identifikasi',
    'ringkasan_singkat' => ''
];

// =========================================================================
// 3. AUTO-PULL DATA DARI DATABASE SIMRS (PASIEN, KUNJUNGAN & REKAM MEDIS)
// =========================================================================
if (isset($koneksi) && $koneksi) {
    
    // A. Jika ID Kasus Sudah Ada di Database Modul
    if ($id_kasus > 0) {
        $q_kasus = mysqli_query($koneksi, "
            SELECT 
                mkk.*,
                p.nama_lengkap AS simrs_nama,
                p.nik AS simrs_nik,
                p.tgl_lahir AS simrs_tgl_lahir,
                p.jk AS simrs_jk,
                p.pekerjaan AS simrs_pekerjaan,
                COALESCE(k.tgl_keluar, k.tgl_masuk) AS simrs_tgl_kematian,
                rm.diagnosa_utama AS simrs_diagnosa,
                rm.icd_utama AS simrs_icd
            FROM kasus_kematian_kerja mkk
            LEFT JOIN pasien p ON mkk.no_rm = p.no_rm
            LEFT JOIN kunjungan k ON mkk.no_registrasi = k.no_registrasi
            LEFT JOIN rekam_medis rm ON mkk.no_registrasi = rm.no_registrasi
            WHERE mkk.id_kasus = '$id_kasus'
        ");

        if ($q_kasus && mysqli_num_rows($q_kasus) > 0) {
            $row = mysqli_fetch_assoc($q_kasus);
            
            // Prioritaskan data tersimpan modul, jika masih kosong tarik fallback dari SIMRS
            $data['no_kasus']         = $row['no_kasus'] ?? $data['no_kasus'];
            $data['no_rm']            = $row['no_rm'] ?? '';
            $data['nama_pasien']      = !empty($row['nama_pasien']) ? $row['nama_pasien'] : ($row['simrs_nama'] ?? '');
            $data['nik']              = !empty($row['nik']) ? $row['nik'] : ($row['simrs_nik'] ?? '');
            $data['tgl_lahir']        = !empty($row['tgl_lahir']) ? $row['tgl_lahir'] : ($row['simrs_tgl_lahir'] ?? '');
            
            $jk_val = !empty($row['jk']) ? $row['jk'] : ($row['simrs_jk'] ?? 'L');
            $data['jk']               = ($jk_val == 'L' || $jk_val == 'Laki-Laki') ? 'Laki-Laki' : 'Perempuan';
            
            $data['pekerjaan']        = !empty($row['pekerjaan']) ? $row['pekerjaan'] : ($row['simrs_pekerjaan'] ?? '');
            $data['unit_perusahaan']  = $row['unit_perusahaan'] ?? '';
            
            $tgl_m = !empty($row['tgl_kematian']) ? $row['tgl_kematian'] : ($row['simrs_tgl_kematian'] ?? date('Y-m-d H:i:s'));
            $data['tgl_kematian']     = date('Y-m-d\TH:i', strtotime($tgl_m));
            
            $data['lokasi_kematian']  = $row['lokasi_kematian'] ?? 'IGD / Rumah Sakit';
            $data['kategori_kasus']   = $row['kategori_kasus'] ?? 'Kecelakaan Kerja (KK)';
            $data['status_proses']    = $row['status_proses'] ?? 'Perlu Identifikasi';

            // Auto-fill ringkasan awal jika masih kosong dengan Diagnosa SIMRS
            if (!empty($row['ringkasan_singkat'])) {
                $data['ringkasan_singkat'] = $row['ringkasan_singkat'];
            } elseif (!empty($row['simrs_diagnosa'])) {
                $data['ringkasan_singkat'] = "Diagnosa SIMRS: " . $row['simrs_diagnosa'] . " (ICD-10: " . ($row['simrs_icd'] ?? '-') . ")";
            }
        }
    } 
    // B. Jika Pengisian Baru Berdasarkan Parameter ?rm= DariSIMRS
    elseif (!empty($no_rm_param)) {
        $q_p = mysqli_query($koneksi, "
            SELECT p.*, k.tgl_keluar, k.tgl_masuk, rm.diagnosa_utama, rm.icd_utama
            FROM pasien p
            LEFT JOIN kunjungan k ON p.no_rm = k.no_rm
            LEFT JOIN rekam_medis rm ON k.no_registrasi = rm.no_registrasi
            WHERE p.no_rm = '$no_rm_param'
            ORDER BY k.id_kunjungan DESC LIMIT 1
        ");
        if ($q_p && mysqli_num_rows($q_p) > 0) {
            $p = mysqli_fetch_assoc($q_p);
            $data['no_rm']          = $p['no_rm'];
            $data['nama_pasien']    = $p['nama_lengkap'];
            $data['nik']            = $p['nik'] ?? '';
            $data['tgl_lahir']      = $p['tgl_lahir'] ?? '';
            $data['jk']             = ($p['jk'] == 'L') ? 'Laki-Laki' : 'Perempuan';
            $data['pekerjaan']      = $p['pekerjaan'] ?? '';
            
            $tgl_m = $p['tgl_keluar'] ?? $p['tgl_masuk'] ?? date('Y-m-d H:i:s');
            $data['tgl_kematian']   = date('Y-m-d\TH:i', strtotime($tgl_m));
            if (!empty($p['diagnosa_utama'])) {
                $data['ringkasan_singkat'] = "Diagnosa SIMRS: " . $p['diagnosa_utama'] . " (ICD-10: " . ($p['icd_utama'] ?? '-') . ")";
            }
        }
    }

    // C. Tarik List Pasien untuk Auto-Complete / Dropdown Pencarian RM
    $pasien_list = [];
    $q_all_p = mysqli_query($koneksi, "
        SELECT p.no_rm, p.nama_lengkap, p.nik, p.tgl_lahir, p.jk, p.pekerjaan 
        FROM pasien p 
        ORDER BY p.id_pasien DESC LIMIT 100
    ");
    if ($q_all_p) {
        while ($row_p = mysqli_fetch_assoc($q_all_p)) {
            $pasien_list[] = $row_p;
        }
    }
}

// =========================================================================
// 4. PROSES SIMPAN / UPDATE HASIL EDITAN KE DATABASE
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $no_kasus         = mysqli_real_escape_string($koneksi, $_POST['no_kasus'] ?? '');
    $no_rm            = mysqli_real_escape_string($koneksi, $_POST['no_rm'] ?? '');
    $nama_pasien      = mysqli_real_escape_string($koneksi, $_POST['nama_pasien'] ?? '');
    $nik              = mysqli_real_escape_string($koneksi, $_POST['nik'] ?? '');
    $tgl_lahir        = !empty($_POST['tgl_lahir']) ? $_POST['tgl_lahir'] : null;
    $jk               = $_POST['jk'] ?? 'Laki-Laki';
    $pekerjaan        = mysqli_real_escape_string($koneksi, $_POST['pekerjaan'] ?? '');
    $unit_perusahaan  = mysqli_real_escape_string($koneksi, $_POST['unit_perusahaan'] ?? '');
    $tgl_kematian     = $_POST['tgl_kematian'] ?? date('Y-m-d H:i:s');
    $lokasi_kematian  = mysqli_real_escape_string($koneksi, $_POST['lokasi_kematian'] ?? '');
    $kategori_kasus   = $_POST['kategori_kasus'] ?? 'Kecelakaan Kerja (KK)';
    $ringkasan_singkat= mysqli_real_escape_string($koneksi, $_POST['ringkasan_singkat'] ?? '');

    if (isset($koneksi) && $koneksi) {
        $tgl_lahir_sql = $tgl_lahir ? "'$tgl_lahir'" : "NULL";

        if ($id_kasus > 0) {
            $sql = "UPDATE kasus_kematian_kerja SET 
                    no_rm='$no_rm', 
                    nama_pasien='$nama_pasien', 
                    nik='$nik', 
                    tgl_lahir=$tgl_lahir_sql, 
                    jk='$jk', 
                    pekerjaan='$pekerjaan', 
                    unit_perusahaan='$unit_perusahaan', 
                    tgl_kematian='$tgl_kematian', 
                    lokasi_kematian='$lokasi_kematian', 
                    kategori_kasus='$kategori_kasus', 
                    ringkasan_singkat='$ringkasan_singkat',
                    status_proses='Sedang Diidentifikasi'
                    WHERE id_kasus='$id_kasus'";
        } else {
            $sql = "INSERT INTO kasus_kematian_kerja 
                    (no_kasus, no_rm, nama_pasien, nik, tgl_lahir, jk, pekerjaan, unit_perusahaan, tgl_kematian, lokasi_kematian, kategori_kasus, ringkasan_singkat, status_proses) 
                    VALUES 
                    ('$no_kasus', '$no_rm', '$nama_pasien', '$nik', $tgl_lahir_sql, '$jk', '$pekerjaan', '$unit_perusahaan', '$tgl_kematian', '$lokasi_kematian', '$kategori_kasus', '$ringkasan_singkat', 'Sedang Diidentifikasi')";
        }

        if (mysqli_query($koneksi, $sql)) {
            $saved_id = $id_kasus > 0 ? $id_kasus : mysqli_insert_id($koneksi);
            echo "<script>alert('Data Identifikasi Kasus Berhasil Disimpan!'); window.location='data_kejadian.php?id=$saved_id';</script>";
            exit;
        } else {
            $msg_error = "Gagal menyimpan ke database: " . mysqli_error($koneksi);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Identifikasi Kasus Kematian</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: #f8fafc; color: #0f172a; padding: 24px; }

        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .page-title h2 { font-size: 20px; font-weight: 700; color: #0f172a; }
        .page-title p { font-size: 13px; color: #64748b; margin-top: 2px; }

        /* STEPPER HEADER NAVIGASI */
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
            width: 24px; height: 24px; border-radius: 50%; background: #e2e8f0;
            color: #475569; display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 700;
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
            font-size: 15px; font-weight: 700; color: #0f172a;
            margin-bottom: 18px; display: flex; align-items: center; gap: 8px;
            border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;
        }

        .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
        .form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 16px; }

        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: 12.5px; font-weight: 600; color: #334155; }
        .form-group label span { color: #ef4444; }

        .form-control {
            padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px;
            font-size: 13px; outline: none; transition: all 0.2s; background: #ffffff;
        }
        .form-control:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
        .form-control[readonly] { background-color: #f1f5f9; color: #64748b; }

        textarea.form-control { resize: vertical; min-height: 85px; }

        .badge-info {
            background: #eff6ff; color: #2563eb; padding: 4px 10px; border-radius: 6px;
            font-size: 11px; font-weight: 600; display: inline-block; margin-bottom: 12px;
        }

        /* BUTTON FOOTER */
        .form-actions { display: flex; justify-content: space-between; align-items: center; margin-top: 10px; }
        .btn {
            padding: 11px 22px; border-radius: 8px; font-size: 13px; font-weight: 600;
            cursor: pointer; border: none; text-decoration: none; display: inline-flex;
            align-items: center; gap: 8px; transition: all 0.2s;
        }
        .btn-secondary { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
        .btn-secondary:hover { background: #e2e8f0; }
        .btn-primary { background: #2563eb; color: #ffffff; box-shadow: 0 2px 6px rgba(37, 99, 235, 0.2); }
        .btn-primary:hover { background: #1d4ed8; }

        .alert-error {
            background-color: #fef2f2; border: 1px solid #fca5a5; color: #991b1b;
            padding: 12px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 20px;
        }
    </style>
</head>
<body>

    <!-- KAMUS AUTO-COMPLETE PASIEN SIMRS -->
    <datalist id="list_pasien_simrs">
        <?php if (!empty($pasien_list)): ?>
            <?php foreach ($pasien_list as $pl): ?>
                <option value="<?= htmlspecialchars($pl['no_rm']) ?>" 
                        data-nama="<?= htmlspecialchars($pl['nama_lengkap']) ?>" 
                        data-nik="<?= htmlspecialchars($pl['nik'] ?? '') ?>" 
                        data-tgl="<?= htmlspecialchars($pl['tgl_lahir'] ?? '') ?>" 
                        data-jk="<?= htmlspecialchars(($pl['jk'] == 'L' || $pl['jk'] == 'Laki-Laki') ? 'Laki-Laki' : 'Perempuan') ?>" 
                        data-pekerjaan="<?= htmlspecialchars($pl['pekerjaan'] ?? '') ?>">
                    <?= htmlspecialchars($pl['no_rm']) ?> - <?= htmlspecialchars($pl['nama_lengkap']) ?>
                </option>
            <?php endforeach; ?>
        <?php endif; ?>
    </datalist>

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div class="page-title">
            <h2>Identifikasi Kasus Kematian</h2>
            <p>Langkah 1: Tarik data otomatis dari SIMRS & lengkapi/edit identitas serta klasifikasi kasus.</p>
        </div>
        <a href="daftar_kasus.php" class="btn btn-secondary">
            ← Kembali ke Daftar
        </a>
    </div>

    <!-- STEPPER PROGRES PENGISIAN MODUL -->
    <div class="stepper">
        <a href="#" class="step-item active">
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

        <!-- CARD 1: IDENTITAS PASIEN / KORBAN -->
        <div class="card">
            <div class="card-header">
                👤 Data Identitas Pasien
            </div>
            <span class="badge-info">ℹ️ Data di bawah ditarik otomatis dari SIMRS, namun tetap dapat Anda edit jika ada penyesuaian.</span>

            <div class="form-grid-3">
                <div class="form-group">
                    <label>No. Registrasi Kasus <span>*</span></label>
                    <input type="text" name="no_kasus" class="form-control" value="<?= htmlspecialchars($data['no_kasus']) ?>" readonly required>
                </div>
                <div class="form-group">
                    <label>Pilih / Ketik No. RM Pasien SIMRS <span>*</span></label>
                    <input type="text" id="no_rm" name="no_rm" list="list_pasien_simrs" class="form-control" placeholder="Pilih/Ketik No. RM..." value="<?= htmlspecialchars($data['no_rm']) ?>" oninput="autoFillPasien()" required>
                </div>
                <div class="form-group">
                    <label>NIK / No. KTP</label>
                    <input type="text" id="nik" name="nik" class="form-control" placeholder="16 Digit NIK Pasien..." value="<?= htmlspecialchars($data['nik']) ?>">
                </div>
            </div>

            <div class="form-grid-3">
                <div class="form-group">
                    <label>Nama Lengkap Pasien <span>*</span></label>
                    <input type="text" id="nama_pasien" name="nama_pasien" class="form-control" placeholder="Nama sesuai identitas..." value="<?= htmlspecialchars($data['nama_pasien']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Tanggal Lahir</label>
                    <input type="date" id="tgl_lahir" name="tgl_lahir" class="form-control" value="<?= htmlspecialchars($data['tgl_lahir']) ?>">
                </div>
                <div class="form-group">
                    <label>Jenis Kelamin <span>*</span></label>
                    <select id="jk" name="jk" class="form-control">
                        <option value="Laki-Laki" <?= $data['jk'] == 'Laki-Laki' ? 'selected' : '' ?>>Laki-Laki</option>
                        <option value="Perempuan" <?= $data['jk'] == 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label>Pekerjaan / Jabatan  <span>*</span></label>
                    <input type="text" id="pekerjaan" name="pekerjaan" class="form-control" placeholder="Contoh: Operator Alat Berat, Teknisi Listrik..." value="<?= htmlspecialchars($data['pekerjaan']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Unit Kerja / Perusahaan / Instansi <span>*</span></label>
                    <input type="text" name="unit_perusahaan" class="form-control" placeholder="Contoh: PT. Konstruksi Utama / Subkon Proyek A" value="<?= htmlspecialchars($data['unit_perusahaan']) ?>" required>
                </div>
            </div>
        </div>

        <!-- CARD 2: INFORMASI KEMATIAN & KLASIFIKASI AWAL -->
        <div class="card">
            <div class="card-header">
                🏥 Informasi Kematian & Status Awal
            </div>

            <div class="form-grid-3">
                <div class="form-group">
                    <label>Waktu Kematian (Tanggal & Jam) <span>*</span></label>
                    <input type="datetime-local" name="tgl_kematian" class="form-control" value="<?= htmlspecialchars($data['tgl_kematian']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Lokasi Meninggal Dunia <span>*</span></label>
                    <select name="lokasi_kematian" class="form-control">
                        <option value="IGD / Rumah Sakit" <?= $data['lokasi_kematian'] == 'IGD / Rumah Sakit' ? 'selected' : '' ?>>IGD / Rumah Sakit</option>
                        <option value="Rawat Inap / Bangsal" <?= $data['lokasi_kematian'] == 'Rawat Inap / Bangsal' ? 'selected' : '' ?>>Rawat Inap / Bangsal</option>
                        <option value="ICU / HCU" <?= $data['lokasi_kematian'] == 'ICU / HCU' ? 'selected' : '' ?>>ICU / HCU</option>
                        <option value="Lokasi Kejadian" <?= $data['lokasi_kematian'] == 'Lokasi Kejadian' ? 'selected' : '' ?>>Lokasi Kejadian</option>
                        <option value="Perjalanan ke Rumah Sakit (DOA)" <?= $data['lokasi_kematian'] == 'Perjalanan ke Rumah Sakit (DOA)' ? 'selected' : '' ?>>Perjalanan ke RS (Dead on Arrival)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Klasifikasi Kasus <span>*</span></label>
                    <select name="kategori_kasus" class="form-control">
                        <option value="Penyakit Umum" <?= $data['kategori_kasus'] == 'Penyakit Umum' ? 'selected' : '' ?>>Penyakit Umum</option>
                        <option value="Dalam Penelusuran" <?= $data['kategori_kasus'] == 'Dalam Penelusuran' ? 'selected' : '' ?>>Dalam Penelusuran</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Ringkasan Informasi Awal Kasus / Diagnosa Medis (Dapat Disesuaikan)</label>
                <textarea name="ringkasan_singkat" class="form-control" placeholder="Dapat diedit: Catatan singkat kronologi atau diagnosa dokter DPJP..."><?= htmlspecialchars($data['ringkasan_singkat']) ?></textarea>
            </div>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="form-actions">
            <a href="daftar_kasus.php" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">
                Simpan & Lanjut ke Data Kejadian →
            </button>
        </div>
    </form>

    <!-- SCRIPT AUTO-FILL JS DARI DATALIST SEWAKTU MENGETIK NO RM -->
    <script>
        function autoFillPasien() {
            const valNoRM = document.getElementById('no_rm').value;
            const datalist = document.getElementById('list_pasien_simrs');
            if (!datalist) return;
            
            const options = datalist.options;

            for (let i = 0; i < options.length; i++) {
                if (options[i].value === valNoRM) {
                    document.getElementById('nama_pasien').value = options[i].getAttribute('data-nama') || '';
                    document.getElementById('nik').value = options[i].getAttribute('data-nik') || '';
                    document.getElementById('tgl_lahir').value = options[i].getAttribute('data-tgl') || '';
                    if (options[i].getAttribute('data-jk')) {
                        document.getElementById('jk').value = options[i].getAttribute('data-jk');
                    }
                    document.getElementById('pekerjaan').value = options[i].getAttribute('data-pekerjaan') || '';
                    break;
                }
            }
        }
    </script>
</body>
</html>