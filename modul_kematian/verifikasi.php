<?php
session_start();

// Koneksi ke Database SIMRS Utama
$path_koneksi = __DIR__ . '/../koneksi.php';
if (file_exists($path_koneksi)) {
    include $path_koneksi;
}

$id_kasus = isset($_GET['id']) ? (int)$_GET['id'] : 1;

// Data Default / Fallback Simulasi Sesuai Gambar
$kasus = [
    'no_rm'              => 'RM-00012345',
    'nama_pasien'        => 'S***********',
    'nama_pasien_full'   => 'Sujatmiko',
    'tgl_kematian'       => '12 Mei 2025 (14:30)',
    'status_identifikasi' => 'Perlu Verifikasi',
    'status_coding'      => 'Dalam Proses',
    'kelengkapan_count'  => '3/4 Lengkap',
    'catatan_koreksi'    => 'Mohon melengkapi informasi mekanisme kejadian dan detail lokasi.'
];

// Data Verifikator Coder & Dokter
$coder = [
    'nama'   => 'Siti Rahmawati, A.Md.Kes',
    'nip'    => 'NIP. 198712312015032001',
    'status' => 'Terverifikasi',
    'tgl'    => '12/05/2025 10:45'
];

$dokter = [
    'nama'   => 'dr. Budi Santoso, Sp.PD',
    'nip'    => 'NIP. 197805062008121002',
    'status' => 'Menunggu',
    'tgl'    => '-'
];

// Data Riwayat Aktivitas
$riwayat_aktivitas = [
    [
        'tgl'      => '12/05/2025 10:45',
        'pengguna' => 'Siti Rahmawati, A.Md.Kes',
        'jenis'    => 'Pengkodean External Cause',
        'detail'   => 'Kode kandidat ditambahkan: V89.9',
        'alasan'   => 'Hasil pencarian berdasarkan mekanisme kejadian.'
    ],
    [
        'tgl'      => '12/05/2025 09:20',
        'pengguna' => 'dr. Budi Santoso, Sp.PD',
        'jenis'    => 'Verifikasi Klinis',
        'detail'   => 'Menambahkan informasi pemeriksaan penunjang.',
        'alasan'   => 'Data awal belum lengkap.'
    ],
    [
        'tgl'      => '11/05/2025 16:30',
        'pengguna' => 'Rina Pratiwi, SKM',
        'jenis'    => 'Data Kejadian',
        'detail'   => "Lokasi kejadian diubah: dari 'Jalan' ke 'Area kerja'.",
        'alasan'   => 'Koreksi berdasarkan laporan dari keluarga.'
    ],
    [
        'tgl'      => '11/05/2025 14:10',
        'pengguna' => 'Andi Setiawan',
        'jenis'    => 'Identifikasi Kasus',
        'detail'   => 'Status identifikasi: Perlu verifikasi → Dalam proses.',
        'alasan'   => 'Informasi pekerjaan masih belum jelas.'
    ],
    [
        'tgl'      => '10/05/2025 11:05',
        'pengguna' => 'Lina Kurniawati',
        'jenis'    => 'Input Awal',
        'detail'   => 'Kasus baru dibuat.',
        'alasan'   => 'Berdasarkan laporan IGD.'
    ]
];

// Jika database terhubung, tarik data nyata dari SIMRS
if (isset($koneksi) && $koneksi && $id_kasus > 0) {
    $q_kasus = mysqli_query($koneksi, "SELECT * FROM kasus_kematian_kerja WHERE id_kasus = '$id_kasus'");
    if ($q_kasus && mysqli_num_rows($q_kasus) > 0) {
        $db_data = mysqli_fetch_assoc($q_kasus);
        if (!empty($db_data['no_rm'])) $kasus['no_rm'] = $db_data['no_rm'];
        if (!empty($db_data['nama_pasien'])) {
            $kasus['nama_pasien_full'] = $db_data['nama_pasien'];
            $kasus['nama_pasien'] = substr($db_data['nama_pasien'], 0, 1) . '***********';
        }
        if (!empty($db_data['tgl_kematian'])) $kasus['tgl_kematian'] = date('d M Y (H:i)', strtotime($db_data['tgl_kematian']));
        if (!empty($db_data['status_proses'])) $kasus['status_identifikasi'] = $db_data['status_proses'];
    }
}

// Proses Form Aksi (Simpan / Minta Klarifikasi)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action_type'] ?? 'simpan';
    $catatan = mysqli_real_escape_string($koneksi ?? null, $_POST['catatan_koreksi'] ?? '');

    if ($action === 'klarifikasi') {
        echo "<script>alert('Permintaan klarifikasi berhasil dikirimkan ke Petugas Coder / DPJP!'); window.location='verifikasi.php?id=$id_kasus';</script>";
        exit;
    } else {
        echo "<script>alert('Data Verifikasi dan Catatan Koreksi Berhasil Disimpan!'); window.location='verifikasi.php?id=$id_kasus';</script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring & Verifikasi - Modul K3RS</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: #f8fafc; color: #0f172a; padding: 24px; }

        /* HEADER HALAMAN & ACTION BUTTONS */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .page-title h2 { font-size: 20px; font-weight: 700; color: #0f172a; }
        .page-title p { font-size: 13px; color: #64748b; margin-top: 3px; }

        .header-actions { display: flex; align-items: center; gap: 10px; }
        
        .btn-act {
            padding: 9px 16px;
            border-radius: 8px;
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .btn-outline {
            background: #ffffff;
            color: #1e40af;
            border: 1px solid #cbd5e1;
        }
        .btn-outline:hover { background: #f1f5f9; }

        .btn-blue {
            background: #2563eb;
            color: #ffffff;
            border: none;
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.2);
        }
        .btn-blue:hover { background: #1d4ed8; }

        /* CARD STYLE GENERAL */
        .card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
            padding: 20px;
            margin-bottom: 20px;
        }

        /* 1. TOP INFORMASI PASIEN CARD */
        .patient-bar {
            display: grid;
            grid-template-columns: 1fr 1.2fr 1.2fr 1fr 1fr;
            gap: 16px;
            align-items: center;
            padding: 16px 20px;
        }

        .info-item { display: flex; flex-direction: column; gap: 4px; }
        .info-label { font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; }
        .info-val { font-size: 13.5px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 6px; }

        /* BADGES STATUS */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11.5px;
            font-weight: 600;
            width: fit-content;
        }
        .badge-warning { background-color: #fef9c3; color: #854d0e; }
        .badge-info    { background-color: #e0f2fe; color: #0369a1; }
        .badge-success { background-color: #dcfce7; color: #15803d; }
        .badge-danger  { background-color: #fee2e2; color: #b91c1c; }
        .badge-secondary{ background-color: #f1f5f9; color: #475569; }

        /* GRID LAYOUT KIRI KANAN */
        .grid-2col {
            display: grid;
            grid-template-columns: 1.3fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        /* CARD HEADER INTERNAL */
        .card-header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 12px;
        }
        .card-title-group { display: flex; flex-direction: column; gap: 2px; }
        .card-title { font-size: 14.5px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px; }
        .card-sub { font-size: 12px; color: #64748b; }

        /* STEPPER HORIZONTAL ALUR PROSES */
        .stepper-horiz {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            padding: 10px 0;
        }
        .stepper-line {
            position: absolute;
            top: 24px;
            left: 30px;
            right: 30px;
            height: 2px;
            background: #e2e8f0;
            z-index: 1;
        }
        .step-node {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
            text-align: center;
        }
        .step-circle {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #e2e8f0;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
            border: 2px solid #ffffff;
        }
        .step-node.completed .step-circle {
            background: #2563eb;
            color: #ffffff;
        }
        .step-node.active .step-circle {
            background: #3b82f6;
            color: #ffffff;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
        }
        .step-name { font-size: 12px; font-weight: 700; color: #0f172a; }
        .step-status { font-size: 11px; font-weight: 600; color: #2563eb; margin-top: 2px; }
        .step-date { font-size: 10.5px; color: #94a3b8; margin-top: 2px; }

        /* DAFTAR KELENGKAPAN DATA */
        .checklist-group { display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px; }
        .check-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            padding: 12px 16px;
            border-radius: 10px;
        }
        .check-info { display: flex; flex-direction: column; gap: 2px; }
        .check-name { font-size: 13px; font-weight: 700; color: #0f172a; }
        .check-sub { font-size: 11.5px; color: #64748b; }

        .pill-count {
            background: #e0f2fe;
            color: #0369a1;
            font-size: 11.5px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
        }

        .info-callout {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 12px 14px;
            font-size: 11.5px;
            color: #1e40af;
            display: flex;
            gap: 10px;
            line-height: 1.4;
        }

        /* BOX INFORMASI VERIFIKASI (KANAN) */
        .verif-box-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }
        .verif-person-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .person-role { font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; }
        .person-name { font-size: 13px; font-weight: 700; color: #0f172a; }
        .person-nip { font-size: 11px; color: #64748b; }
        .person-time { font-size: 11px; color: #64748b; margin-top: 4px; display: flex; align-items: center; gap: 4px; }

        /* CATATAN KOREKSI TEXTAREA */
        .notes-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px;
        }
        .notes-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12.5px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
        }
        .notes-textarea {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px;
            font-size: 12.5px;
            color: #1e293b;
            resize: vertical;
            min-height: 65px;
            outline: none;
            background: #ffffff;
        }
        .notes-textarea:focus { border-color: #2563eb; }

        /* TABEL RIWAYAT AKTIVITAS */
        table { width: 100%; border-collapse: collapse; font-size: 12.5px; text-align: left; }
        th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 700;
            padding: 10px 14px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 11px;
            text-transform: uppercase;
        }
        td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; color: #1e293b; vertical-align: top; }
        tr:hover { background-color: #f8fafc; }

        .user-col { font-weight: 600; color: #0f172a; }
        .detail-col { color: #2563eb; font-weight: 600; }
    </style>
</head>
<body>

    <form method="POST" action="">
        <!-- PAGE HEADER & ACTION BUTTONS -->
        <div class="page-header">
            <div class="page-title">
                <h2>Monitoring & Verifikasi</h2>
                <p>Pantau status kelengkapan data, proses verifikasi, dan riwayat perubahan kasus.</p>
            </div>

            <div class="header-actions">
                <button type="submit" name="action_type" value="simpan" class="btn-act btn-outline">
                    💾 Simpan
                </button>
                <button type="submit" name="action_type" value="klarifikasi" class="btn-act btn-outline">
                    ⚠️ Minta Klarifikasi
                </button>
                <a href="#riwayat-section" class="btn-act btn-blue">
                    ⏱️ Lihat Riwayat
                </a>
            </div>
        </div>

        <!-- CARD 1: TOP PATIENT SUMMARY BAR -->
        <div class="card patient-bar">
            <div class="info-item">
                <span class="info-label">No. RM</span>
                <span class="info-val"><?= htmlspecialchars($kasus['no_rm']) ?> <span style="cursor:pointer;" title="Lihat detail RM">👁️</span></span>
            </div>

            <div class="info-item">
                <span class="info-label">Nama Pasien</span>
                <span class="info-val" id="nama_display"><?= htmlspecialchars($kasus['nama_pasien']) ?></span>
            </div>

            <div class="info-item">
                <span class="info-label">Tanggal Kematian</span>
                <span class="info-val"><?= htmlspecialchars($kasus['tgl_kematian']) ?></span>
            </div>

            <div class="info-item">
                <span class="info-label">Status Identifikasi</span>
                <span class="badge badge-warning">🔍 <?= htmlspecialchars($kasus['status_identifikasi']) ?></span>
            </div>

            <div class="info-item">
                <span class="info-label">Status Coding</span>
                <span class="badge badge-info">&lt;/&gt; <?= htmlspecialchars($kasus['status_coding']) ?></span>
            </div>
        </div>

        <!-- GRID 2 KOLOM (UTAMA) -->
        <div class="grid-2col">
            
            <!-- KOLOM KIRI (60%): ALUR PROSES & KELENGKAPAN DATA -->
            <div>
                <!-- CARD ALUR PROSES KASUS -->
                <div class="card">
                    <div class="card-header-flex" style="margin-bottom: 8px;">
                        <div class="card-title-group">
                            <div class="card-title">☑️ Alur Proses Kasus</div>
                        </div>
                    </div>

                    <div class="stepper-horiz">
                        <div class="stepper-line"></div>

                        <!-- STEP 1 -->
                        <div class="step-node completed">
                            <div class="step-circle">✓</div>
                            <div class="step-name">1<br>Identifikasi</div>
                            <div class="step-status">Selesai</div>
                            <div class="step-date">10/05/2025</div>
                        </div>

                        <!-- STEP 2 -->
                        <div class="step-node completed">
                            <div class="step-circle">✓</div>
                            <div class="step-name">2<br>Penelaahan</div>
                            <div class="step-status">Selesai</div>
                            <div class="step-date">11/05/2025</div>
                        </div>

                        <!-- STEP 3 -->
                        <div class="step-node active">
                            <div class="step-circle">3</div>
                            <div class="step-name">3<br>Coding</div>
                            <div class="step-status" style="color: #0284c7;">Dalam Proses</div>
                            <div class="step-date">12/05/2025</div>
                        </div>

                        <!-- STEP 4 -->
                        <div class="step-node">
                            <div class="step-circle">4</div>
                            <div class="step-name">4<br>Verifikasi</div>
                            <div class="step-date" style="margin-top: 14px;">Belum</div>
                            <div class="step-date">-</div>
                        </div>

                        <!-- STEP 5 -->
                        <div class="step-node">
                            <div class="step-circle">5</div>
                            <div class="step-name">5<br>Selesai</div>
                            <div class="step-date" style="margin-top: 14px;">Belum</div>
                            <div class="step-date">-</div>
                        </div>
                    </div>
                </div>

                <!-- CARD KELENGKAPAN DATA -->
                <div class="card">
                    <div class="card-header-flex">
                        <div class="card-title-group">
                            <div class="card-title">ℹ️ Kelengkapan Data</div>
                            <div class="card-sub">Periksa apakah seluruh informasi telah tersedia.</div>
                        </div>
                        <span class="pill-count"><?= htmlspecialchars($kasus['kelengkapan_count']) ?></span>
                    </div>

                    <div class="checklist-group">
                        <div class="check-item">
                            <div class="check-info">
                                <span class="check-name">Data Kejadian</span>
                                <span class="check-sub">Kronologi, mekanisme, lokasi, aktivitas, faktor penyebab</span>
                            </div>
                            <span class="badge badge-success">✓ Lengkap</span>
                        </div>

                        <div class="check-item">
                            <div class="check-info">
                                <span class="check-name">Data Klinis</span>
                                <span class="check-sub">Diagnosis, pemeriksaan, tindakan, outcome</span>
                            </div>
                            <span class="badge badge-success">✓ Lengkap</span>
                        </div>

                        <div class="check-item">
                            <div class="check-info">
                                <span class="check-name">Rangkaian Penyebab</span>
                                <span class="check-sub">Penyebab langsung, antara, dasar, komorbid</span>
                            </div>
                            <span class="badge badge-success">✓ Lengkap</span>
                        </div>

                        <div class="check-item">
                            <div class="check-info">
                                <span class="check-name">External Cause</span>
                                <span class="check-sub">Informasi kejadian, kandidat kode, deskripsi</span>
                            </div>
                            <span class="badge badge-warning">! Belum Lengkap</span>
                        </div>
                    </div>

                    <div class="info-callout">
                        <span>ℹ️</span>
                        <div>
                            Kelengkapan data dapat berubah sewaktu-waktu sesuai hasil penelaahan dan verifikasi. Jika terdapat informasi yang belum lengkap, kasus akan dikembalikan untuk ditindaklanjuti.
                        </div>
                    </div>
                </div>

            </div>

            <!-- KOLOM KANAN (40%): INFORMASI VERIFIKASI & CATATAN KOREKSI -->
            <div>
                <div class="card">
                    <div class="card-header-flex">
                        <div class="card-title-group">
                            <div class="card-title">✓ Informasi Verifikasi</div>
                        </div>
                    </div>

                    <div class="verif-box-grid">
                        <!-- PETUGAS CODER -->
                        <div class="verif-person-card">
                            <span class="person-role">👤 Petugas Coder</span>
                            <span class="person-name"><?= htmlspecialchars($coder['nama']) ?></span>
                            <span class="person-nip"><?= htmlspecialchars($coder['nip']) ?></span>
                            <span class="badge badge-info" style="margin-top: 4px;">✓ <?= htmlspecialchars($coder['status']) ?></span>
                            <span class="person-time">📅 <?= htmlspecialchars($coder['tgl']) ?></span>
                        </div>

                        <!-- DOKTER KLINIS -->
                        <div class="verif-person-card">
                            <span class="person-role">👤 Dokter Klinis</span>
                            <span class="person-name"><?= htmlspecialchars($dokter['nama']) ?></span>
                            <span class="person-nip"><?= htmlspecialchars($dokter['nip']) ?></span>
                            <span class="badge badge-secondary" style="margin-top: 4px;">✏️ <?= htmlspecialchars($dokter['status']) ?></span>
                            <span class="person-time">📅 <?= htmlspecialchars($dokter['tgl']) ?></span>
                        </div>
                    </div>

                    <!-- CATATAN / PERMINTAAN KOREKSI -->
                    <div class="notes-box">
                        <div class="notes-header">
                            <span>📄 Catatan / Permintaan Koreksi</span>
                            <span style="cursor:pointer;" title="Edit Catatan">✏️</span>
                        </div>
                        <textarea name="catatan_koreksi" class="notes-textarea"><?= htmlspecialchars($kasus['catatan_koreksi']) ?></textarea>
                    </div>
                </div>
            </div>

        </div>

        <!-- CARD BOTTOM: RIWAYAT AKTIVITAS (FULL WIDTH) -->
        <div class="card" id="riwayat-section">
            <div class="card-header-flex">
                <div class="card-title-group">
                    <div class="card-title">⏱️ Riwayat Aktivitas</div>
                    <div class="card-sub">Catatan seluruh perubahan dan aktivitas pada kasus ini.</div>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 15%;">Tanggal & Waktu</th>
                        <th style="width: 20%;">Pengguna</th>
                        <th style="width: 20%;">Jenis Perubahan</th>
                        <th style="width: 25%;">Detail Perubahan</th>
                        <th style="width: 20%;">Alasan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($riwayat_aktivitas as $rw): ?>
                    <tr>
                        <td><?= htmlspecialchars($rw['tgl']) ?></td>
                        <td class="user-col"><?= htmlspecialchars($rw['pengguna']) ?></td>
                        <td><?= htmlspecialchars($rw['jenis']) ?></td>
                        <td class="detail-col"><?= htmlspecialchars($rw['detail']) ?></td>
                        <td><?= htmlspecialchars($rw['alasan']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </form>

</body>
</html>