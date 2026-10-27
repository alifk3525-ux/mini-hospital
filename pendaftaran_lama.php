<?php include 'koneksi.php'; ?>
<!DOCTYPE html>
<html>
<head>
    <title>Pencarian Pasien Lama</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f4f8; padding: 20px; margin: 0; }
        .container { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); max-width: 100%; margin: 0 auto; box-sizing: border-box; }
        h2 { color: #2c3e50; border-bottom: 2px solid #3b82f6; padding-bottom: 10px; margin-top: 0; }
        
        .search-box { display: flex; gap: 10px; margin-bottom: 20px; background: #e2e8f0; padding: 15px; border-radius: 6px; }
        .search-box input { flex-grow: 1; padding: 10px; border: 1px solid #cbd5e1; border-radius: 5px; font-size: 15px; }
        .search-box button { background: #3b82f6; color: white; border: none; padding: 10px 20px; border-radius: 5px; font-weight: bold; cursor: pointer; font-size: 15px; }
        .search-box button:hover { background: #2563eb; }
        .search-box a.reset { background: #64748b; color: white; text-decoration: none; padding: 10px 20px; border-radius: 5px; font-weight: bold; display: flex; align-items: center; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13px; }
        th, td { border: 1px solid #e2e8f0; padding: 10px 12px; text-align: left; vertical-align: middle; }
        th { background: #f8fafc; color: #475569; font-weight: bold; }
        tr:nth-child(even) { background: #f8fafc; }
        tr:hover { background: #f1f5f9; }
        
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .bg-bpjs { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .bg-umum { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }

        /* Wadah Grid Tombol Aksi */
        .aksi-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 5px;
            min-width: 200px;
        }

        .btn-act {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 6px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            text-decoration: none;
            color: white;
            white-space: nowrap;
            text-align: center;
            box-sizing: border-box;
            transition: opacity 0.2s;
        }
        .btn-act:hover { opacity: 0.85; }

        /* Warna masing-masing tombol */
        .btn-act.edit    { background: #f59e0b; }
        .btn-act.sep     { background: #0d9488; }
        .btn-act.stiker  { background: #2563eb; }
        .btn-act.daftar  { background: #16a34a; }
    </style>
</head>
<body>

    <div class="container">
        <h2>🔍 Pencarian Pasien Lama</h2>
        
        <!-- Form Pencarian (GET Method) -->
        <form method="GET" action="" class="search-box">
            <input type="text" name="cari" placeholder="Ketik No. RM, NIK, atau Nama Pasien..." value="<?php echo isset($_GET['cari']) ? $_GET['cari'] : ''; ?>" required>
            <button type="submit">Cari Pasien</button>
            <?php if(isset($_GET['cari'])): ?>
                <a href="pendaftaran_lama.php" class="reset">Tampilkan Semua</a>
            <?php endif; ?>
        </form>

        <table>
            <thead>
                <tr>
                    <th style="width: 10%;">No. RM</th>
                    <th style="width: 25%;">Nama & NIK</th>
                    <th style="width: 22%;">Tgl Lahir / Umur</th>
                    <th style="width: 18%;">Cara Bayar Dasar</th>
                    <th style="width: 25%;">Aksi Lanjutan</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Logika Pencarian Universal
                $kondisi = "";
                if(isset($_GET['cari'])) {
                    $cari = mysqli_real_escape_string($koneksi, $_GET['cari']);
                    $kondisi = "WHERE no_rm LIKE '%$cari%' OR nik LIKE '%$cari%' OR nama_lengkap LIKE '%$cari%'";
                }
                
                // Menampilkan 20 data terbaru jika tidak ada pencarian, atau hasil spesifik jika mencari
                $query = mysqli_query($koneksi, "SELECT * FROM pasien $kondisi ORDER BY id_pasien DESC LIMIT 20");
                
                if(mysqli_num_rows($query) == 0) {
                    echo "<tr><td colspan='5' style='text-align:center; padding: 20px; color: #64748b;'>Data pasien tidak ditemukan. Pastikan kata kunci benar atau daftarkan sebagai pasien baru.</td></tr>";
                }
                
                while($d = mysqli_fetch_assoc($query)) {
                    $badge_class = ($d['cara_bayar'] == 'BPJS KESEHATAN') ? 'bg-bpjs' : 'bg-umum';
                    
                    // Logika Perhitungan Umur Dinamis PHP
                    $tgl_lahir = new DateTime($d['tgl_lahir']);
                    $sekarang = new DateTime('today');
                    $umur = $sekarang->diff($tgl_lahir);
                    $text_umur = $umur->y . " Thn, " . $umur->m . " Bln";
                ?>
                <tr>
                    <td style="font-size: 15px; font-weight: bold; color: #0f172a;"><?php echo $d['no_rm']; ?></td>
                    <td>
                        <strong><?php echo strtoupper($d['nama_lengkap']); ?></strong><br>
                        <small style="color: #64748b;">NIK: <?php echo $d['nik']; ?></small>
                    </td>
                    <td>
                        <?php echo date('d-m-Y', strtotime($d['tgl_lahir'])); ?><br>
                        <small style="color: #64748b;">JK: <?php echo ($d['jk']=='L')?'Laki-laki':'Perempuan'; ?> | Umur: <strong><?php echo $text_umur; ?></strong></small>
                    </td>
                    <td>
                        <span class="badge <?php echo $badge_class; ?>"><?php echo $d['cara_bayar']; ?></span>
                    </td>
                    <td>
                        <div class="aksi-grid">
                            <a href="edit_pasien.php?rm=<?php echo $d['no_rm']; ?>" class="btn-act edit">✏️ Edit Data</a>
                            <a href="cetak_sep.php?rm=<?php echo $d['no_rm']; ?>" class="btn-act sep">📄 Buat SEP</a>
                            <a href="cetak_stiker.php?rm=<?php echo $d['no_rm']; ?>" class="btn-act stiker">🏷️ Stiker</a>
                            <a href="daftar_layanan.php?rm=<?php echo $d['no_rm']; ?>" class="btn-act daftar">🏥 Daftarkan</a>
                        </div>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

</body>
</html>