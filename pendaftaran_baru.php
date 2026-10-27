<?php
include 'koneksi.php';

if (isset($_POST['simpan'])) {
    $no_rm = $_POST['no_rm'];
    $nik = $_POST['nik'];
    $nama = $_POST['nama'];
    $tempat = $_POST['tempat_lahir'];
    $tgl = $_POST['tgl_lahir'];
    $jk = $_POST['jk'];
    $agama = $_POST['agama'];
    $gol_darah = $_POST['gol_darah'];
    $kewarganegaraan = $_POST['kewarganegaraan'];
    $pendidikan = $_POST['pendidikan'];
    $ibu_kandung = $_POST['nama_ibu_kandung'];
    $status_nikah = $_POST['status_nikah'];
    $pekerjaan = $_POST['pekerjaan'];
    $alamat_ktp = $_POST['alamat_ktp'];
    $alamat_domisili = $_POST['alamat_domisili'];
    $provinsi = $_POST['provinsi'];
    $kabupaten = $_POST['kabupaten'];
    $kecamatan = $_POST['kecamatan'];
    $telp = $_POST['telp'];
    $email = $_POST['email'];
    $nama_pj = $_POST['nama_pj'];
    $hub_pj = $_POST['hub_pj'];
    $telp_pj = $_POST['telp_pj'];
    $cara_bayar = $_POST['cara_bayar'];
    $no_kartu = $_POST['no_kartu'];

    $query = "INSERT INTO pasien (
                no_rm, nik, nama_lengkap, tempat_lahir, tgl_lahir, jk, agama, gol_darah, 
                kewarganegaraan, pendidikan, nama_ibu_kandung, status_nikah, pekerjaan, 
                alamat, alamat_domisili, provinsi, kabupaten, kecamatan, no_telp, email, 
                nama_pj, hub_pj, telp_pj, cara_bayar, no_kartu
              ) VALUES (
                '$no_rm', '$nik', '$nama', '$tempat', '$tgl', '$jk', '$agama', '$gol_darah',
                '$kewarganegaraan', '$pendidikan', '$ibu_kandung', '$status_nikah', '$pekerjaan',
                '$alamat_ktp', '$alamat_domisili', '$provinsi', '$kabupaten', '$kecamatan', '$telp', '$email',
                '$nama_pj', '$hub_pj', '$telp_pj', '$cara_bayar', '$no_kartu'
              )";
    
    if (mysqli_query($koneksi, $query)) {
        echo "<script>alert('Data Pasien Baru Terperinci Berhasil Disimpan!'); window.location='pendaftaran_baru.php';</script>";
    } else {
        echo "<script>alert('Gagal menyimpan data! Cek struktur tabel.');</script>";
    }
}

// Generate No RM Otomatis
$query_rm = mysqli_query($koneksi, "SELECT MAX(no_rm) as max_rm FROM pasien");
$data_rm = mysqli_fetch_assoc($query_rm);
$last_rm = $data_rm['max_rm'];
$new_rm = $last_rm ? sprintf("%08s", (int)$last_rm + 1) : "00000001";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Pendaftaran Pasien Baru Lengkap</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f0f4f8; padding: 20px; margin: 0; }
        .form-container { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); max-width: 950px; margin: 0 auto; }
        h2 { color: #2c3e50; border-bottom: 2px solid #3b82f6; padding-bottom: 10px; margin-top: 0; }
        .section-title { background: #e2e8f0; padding: 8px 12px; font-weight: bold; color: #334155; border-radius: 4px; margin: 20px 0 15px 0; border-left: 4px solid #3b82f6; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; }
        .form-group { margin-bottom: 10px; }
        .form-group.full { grid-column: 1 / -1; }
        label { display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 5px; }
        input, select, textarea { width: 100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 5px; box-sizing: border-box; font-size: 14px; }
        input:focus, select:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,0.2); }
        .btn-simpan { background: #10b981; color: white; border: none; padding: 15px; font-size: 16px; font-weight: bold; border-radius: 6px; cursor: pointer; width: 100%; margin-top: 25px; }
        .btn-simpan:hover { background: #059669; }
        .input-readonly { background-color: #e9ecef; font-weight: bold; color: #b91c1c; cursor: not-allowed; text-align: center; }
    </style>
</head>
<body>

    <div class="form-container">
        <h2>Registrasi Pasien Baru (Form Lengkap)</h2>
        <form method="POST">
            
            <div class="section-title">A. Identitas Diri Utama</div>
            <div class="grid-2">
                <div class="form-group">
                    <label>No. Rekam Medis (Otomatis)</label>
                    <input type="text" name="no_rm" value="<?php echo $new_rm; ?>" class="input-readonly" readonly>
                </div>
                <div class="form-group"><label>NIK (No. KTP)</label><input type="text" name="nik" maxlength="16" required></div>
                <div class="form-group full"><label>Nama Lengkap Pasien (Sesuai KTP)</label><input type="text" name="nama" required></div>
                <div class="form-group"><label>Tempat Lahir</label><input type="text" name="tempat_lahir" required></div>
                
                <div class="form-group" style="display: flex; gap: 10px;">
                    <div style="flex: 1;">
                        <label>Tanggal Lahir</label>
                        <input type="date" name="tgl_lahir" id="input_tgl" onchange="hitungUmur()" required>
                    </div>
                    <div style="flex: 1;">
                        <label>Estimasi Umur</label>
                        <input type="text" id="tampil_umur" class="input-readonly" placeholder="Otomatis..." readonly>
                    </div>
                </div>
                
                <div class="form-group"><label>Jenis Kelamin</label>
                    <select name="jk"><option value="L">Laki-laki</option><option value="P">Perempuan</option></select>
                </div>
                <div class="form-group"><label>Golongan Darah</label>
                    <select name="gol_darah">
                        <option value="-">Tidak Tahu</option><option value="A">A</option><option value="B">B</option><option value="AB">AB</option><option value="O">O</option>
                    </select>
                </div>
            </div>

            <div class="section-title">B. Data Demografi & Pendidikan</div>
            <div class="grid-2">
                <div class="form-group"><label>Agama</label>
                    <select name="agama">
                        <option value="Islam">Islam</option><option value="Kristen">Kristen</option><option value="Katolik">Katolik</option><option value="Hindu">Hindu</option><option value="Buddha">Buddha</option><option value="Konghucu">Konghucu</option>
                    </select>
                </div>
                <div class="form-group"><label>Kewarganegaraan</label>
                    <select name="kewarganegaraan"><option value="WNI">WNI (Indonesia)</option><option value="WNA">WNA (Asing)</option></select>
                </div>
                <div class="form-group"><label>Pendidikan Terakhir</label>
                    <select name="pendidikan">
                        <option value="-">Tidak Sekolah</option><option value="SD">SD/Sederajat</option><option value="SMP">SMP/Sederajat</option><option value="SMA">SMA/Sederajat</option><option value="D3">Diploma (D3)</option><option value="S1">Sarjana (S1)</option><option value="S2/S3">Pascasarjana (S2/S3)</option>
                    </select>
                </div>
                <div class="form-group"><label>Pekerjaan</label><input type="text" name="pekerjaan"></div>
                <div class="form-group"><label>Status Pernikahan</label>
                    <select name="status_nikah">
                        <option value="Belum Kawin">Belum Kawin</option><option value="Kawin">Kawin</option><option value="Cerai Hidup">Cerai Hidup</option><option value="Cerai Mati">Cerai Mati</option>
                    </select>
                </div>
                <div class="form-group"><label>Nama Gadis Ibu Kandung (Verifikasi)</label><input type="text" name="nama_ibu_kandung" required></div>
            </div>

            <div class="section-title">C. Data Domisili & Kontak</div>
            <div class="grid-2">
                <div class="form-group full"><label>Alamat Lengkap Sesuai KTP</label><textarea name="alamat_ktp" rows="2" required></textarea></div>
                <div class="form-group full">
                    <label>Alamat Domisili Saat Ini</label>
                    <textarea name="alamat_domisili" rows="2" placeholder="Kosongkan jika sama dengan KTP"></textarea>
                </div>
            </div>
            <div class="grid-3">
                <div class="form-group"><label>Provinsi</label><input type="text" name="provinsi"></div>
                <div class="form-group"><label>Kabupaten / Kota</label><input type="text" name="kabupaten"></div>
                <div class="form-group"><label>Kecamatan</label><input type="text" name="kecamatan"></div>
            </div>
            <div class="grid-2">
                <div class="form-group"><label>No. WhatsApp / HP Utama</label><input type="text" name="telp" required></div>
                <div class="form-group"><label>Email Pasien (Opsional)</label><input type="email" name="email"></div>
            </div>

            <div class="section-title">D. Data Penanggung Jawab (Kondisi Darurat)</div>
            <div class="grid-3">
                <div class="form-group"><label>Nama Penanggung Jawab</label><input type="text" name="nama_pj" required></div>
                <div class="form-group"><label>Hubungan dengan Pasien</label>
                    <select name="hub_pj">
                        <option value="Suami/Istri">Suami/Istri</option><option value="Orang Tua">Orang Tua</option><option value="Anak">Anak</option><option value="Saudara Kandung">Saudara Kandung</option><option value="Lainnya">Lainnya</option>
                    </select>
                </div>
                <div class="form-group"><label>No. HP Penanggung Jawab</label><input type="text" name="telp_pj" required></div>
            </div>

            <div class="section-title">E. Informasi Layanan Dasar / Asuransi</div>
            <div class="grid-2">
                <div class="form-group"><label>Cara Bayar Default</label>
                    <select name="cara_bayar">
                        <option value="UMUM / PRIBADI">UMUM / PRIBADI</option><option value="BPJS KESEHATAN">BPJS KESEHATAN</option><option value="ASURANSI SWASTA">ASURANSI SWASTA</option>
                    </select>
                </div>
                <div class="form-group"><label>No. Kartu BPJS/Asuransi</label><input type="text" name="no_kartu" placeholder="Wajib diisi jika pakai BPJS"></div>
            </div>

            <button type="submit" name="simpan" class="btn-simpan">💾 Simpan Data Pasien Terpusat</button>
        </form>
    </div>

    <script>
    function hitungUmur() {
        var tglLahir = document.getElementById('input_tgl').value;
        if (tglLahir) {
            var lahir = new Date(tglLahir);
            var sekarang = new Date();
            var tahun = sekarang.getFullYear() - lahir.getFullYear();
            var bulan = sekarang.getMonth() - lahir.getMonth();
            if (bulan < 0 || (bulan === 0 && sekarang.getDate() < lahir.getDate())) {
                tahun--;
                bulan = 12 + bulan;
            }
            document.getElementById('tampil_umur').value = tahun + " Thn, " + bulan + " Bln";
        }
    }
    // Salin otomatis alamat KTP ke Domisili jika domisili diklik saat kosong
    document.querySelector('textarea[name="alamat_domisili"]').addEventListener('focus', function() {
        if(this.value === '') { this.value = document.querySelector('textarea[name="alamat_ktp"]').value; }
    });
    </script>
</body>
</html>