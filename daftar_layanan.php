<?php
include 'koneksi.php';
$no_rm = isset($_GET['rm']) ? $_GET['rm'] : '';

// Ambil data biodata pasien
$query = mysqli_query($koneksi, "SELECT * FROM pasien WHERE no_rm='$no_rm'");
$pasien = mysqli_fetch_assoc($query);

// Proses Simpan Kunjungan
if (isset($_POST['daftar'])) {
    $jenis = $_POST['jenis_layanan'];
    $keluhan = $_POST['keluhan_utama'];
    $cara_bayar = $_POST['cara_bayar'];
    $no_reg = "REG-" . date('YmdHis'); 

    if ($jenis == 'Rawat Jalan') {
        $tujuan = $_POST['poli_tujuan'];
        $dpjp = $_POST['dpjp_jalan'];
    } elseif ($jenis == 'Rawat Inap') {
        $tujuan = $_POST['kamar_tujuan']; 
        $dpjp = $_POST['dpjp_inap'];
    } else {
        $tujuan = 'IGD (Gawat Darurat)';
        // Menentukan Triase berdasarkan pilihan IGD
        $triase = $_POST['triase_igd'];
        $tujuan = $tujuan . ' - ' . $triase;
        $dpjp = 'Dokter Jaga IGD';
    }

    $simpan = mysqli_query($koneksi, "INSERT INTO kunjungan (no_registrasi, no_rm, jenis_layanan, tujuan_layanan, dpjp, cara_bayar, keluhan_utama) 
              VALUES ('$no_reg', '$no_rm', '$jenis', '$tujuan', '$dpjp', '$cara_bayar', '$keluhan')");
    
    if ($simpan) {
        if ($jenis == 'Rawat Inap') {
            $nama_ruang = explode(" - ", $tujuan)[0];
            mysqli_query($koneksi, "UPDATE kamar_rawat SET terisi = terisi + 1 WHERE nama_ruangan = '$nama_ruang'");
        }
        echo "<script>alert('Berhasil! Pasien didaftarkan ke $jenis.'); window.location='pendaftaran_lama.php';</script>";
    } else {
        echo "<script>alert('Gagal mendaftar!');</script>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Daftar Layanan</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f4f8; padding: 20px; }
        .kotak { background: white; padding: 25px; border-radius: 8px; max-width: 600px; margin: 0 auto; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h3 { border-bottom: 2px solid #3b82f6; padding-bottom: 10px; color: #2c3e50; margin-top: 0; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px; color: #475569; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 5px; box-sizing: border-box; }
        button { background: #10b981; color: white; border: none; padding: 12px; font-weight: bold; cursor: pointer; border-radius: 5px; width: 100%; font-size: 15px; margin-top: 10px; }
        button:hover { background: #059669; }
        
        #form-jalan, #form-inap, #form-igd, #form-umum { display: none; background: #f8fafc; padding: 15px; border: 1px dashed #cbd5e1; border-radius: 5px; margin-bottom: 15px; }
        
        /* Menyembunyikan option dokter yang tidak sesuai poli */
        .opsi-dokter { display: none; }
    </style>
</head>
<body>
    <div class="kotak">
        <h3>Pendaftaran Kunjungan Baru</h3>
        <p style="background: #e2e8f0; padding: 10px; border-radius: 5px; font-weight: bold;">
            👤 <?php echo $pasien['nama_lengkap']; ?> (RM: <?php echo $no_rm; ?>)
        </p>

        <form method="POST">
            <div class="form-group">
                <label>Pilih Jenis Layanan</label>
                <select name="jenis_layanan" id="jenis_layanan" onchange="ubahForm()" required>
                    <option value="">-- Pilih Tujuan --</option>
                    <option value="IGD">IGD (Gawat Darurat)</option>
                    <option value="Rawat Jalan">Rawat Jalan (Poli)</option>
                    <option value="Rawat Inap">Rawat Inap</option>
                </select>
            </div>

            <!-- AREA KHUSUS IGD -->
            <div id="form-igd">
                <div class="form-group">
                    <label>Pilih Triase IGD</label>
                    <select name="triase_igd">
                        <option value="Triase Merah (Resusitasi)">Triase Merah (Kritis & Mengancam Nyawa)</option>
                        <option value="Triase Kuning (Emergensi)">Triase Kuning (Darurat Tidak Mengancam Nyawa)</option>
                        <option value="Triase Hijau (Urgensi)">Triase Hijau (Tidak Gawat & Tidak Darurat)</option>
                        <option value="Triase Hitam (Kamar Jenazah)">Triase Hitam (DOA / Kamar Jenazah)</option>
                    </select>
                </div>
            </div>

            <!-- AREA KHUSUS RAWAT JALAN -->
            <div id="form-jalan">
                <div class="form-group">
                    <label>Pilih Poliklinik/Unit</label>
                    <select name="poli_tujuan" id="poli_tujuan" onchange="filterDokter()">
                        <option value="">-- Pilih Poliklinik --</option>
                        <option value="Brakhiterapi">Brakhiterapi</option>
                        <option value="Endoscopie">Endoscopie</option>
                        <option value="Hemodialisa">Hemodialisa</option>
                        <option value="Kemoterapi">Kemoterapi</option>
                        <option value="Radioterapi">Radioterapi</option>
                        <option value="Poli Anak">Klinik Spesialis Anak</option>
                        <option value="Poli Penyakit Dalam">Klinik Spesialis Penyakit Dalam</option>
                        <option value="Poli Jantung & Pembuluh Darah">Klinik Spesialis Jantung dan Pembuluh Darah</option>
                        <option value="Poli Bedah">Klinik Spesialis Bedah</option>
                        <option value="Poli Bedah Onkologi">Klinik Spesialis Bedah Onkologi (Tumor)</option>
                        <option value="Poli Bedah Syaraf">Klinik Spesialis Bedah Syaraf</option>
                        <option value="Poli Kebidanan (Obgyn)">Klinik Spesialis Kebidanan dan Kandungan</option>
                        <option value="Poli Gigi">Klinik Gigi</option>
                        <option value="Poli Mata">Klinik Spesialis Mata</option>
                        <option value="Poli THT">Klinik Spesialis THT</option>
                        <option value="Poli Paru">Klinik Spesialis Paru</option>
                        <option value="Poli Penyakit Ginjal & Hipertensi">Klinik Spesialis Penyakit Ginjal dan Hipertensi</option>
                        <option value="Poli Penyakit Kulit & Kelamin">Klinik Spesialis Penyakit Kulit dan Kelamin</option>
                        <option value="Poli Urologi">Klinik Spesialis Urologi</option>
                        <option value="Poli Psikiatri">Klinik Psikiatri</option>
                        <option value="Poli Psikologi">Klinik Psikologi</option>
                        <option value="Poli Gizi">Klinik Konsultasi Gizi</option>
                        <option value="Rehabilitasi Medik">Klinik Rehabilitasi Medik</option>
                        <option value="Medical Check Up (MCU)">Medical Check Up (MCU)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Dokter (DPJP)</label>
                    <select name="dpjp_jalan" id="dpjp_jalan">
                        <option value="">-- Pilih Dokter --</option>
                        <?php
                        // Memanggil semua jadwal dokter, lalu menanamkan atribut data-poli
                        $q_dok = mysqli_query($koneksi, "SELECT * FROM jadwal_dokter ORDER BY nama_dokter ASC");
                        while($dok = mysqli_fetch_assoc($q_dok)) {
                            // Perhatikan ada data-poli di sini! Ini kunci untuk JavaScript filter
                            echo "<option class='opsi-dokter' data-poli='".$dok['poli']."' value='".$dok['nama_dokter']."'>".$dok['nama_dokter']."</option>";
                        }
                        ?>
                    </select>
                    <small style="color: #ef4444;" id="pesan-dokter"></small>
                </div>
            </div>

            <!-- AREA KHUSUS RAWAT INAP -->
            <div id="form-inap">
                <div class="form-group">
                    <label>Pilih Kamar & Kelas</label>
                    <select name="kamar_tujuan">
                        <?php
                        $q_kam = mysqli_query($koneksi, "SELECT * FROM kamar_rawat WHERE kapasitas > terisi ORDER BY kelas ASC");
                        if(mysqli_num_rows($q_kam) == 0) {
                            echo "<option value=''>-- SEMUA KAMAR PENUH --</option>";
                        }
                        while($kam = mysqli_fetch_assoc($q_kam)) {
                            $sisa = $kam['kapasitas'] - $kam['terisi'];
                            echo "<option value='".$kam['nama_ruangan']." - ".$kam['kelas']."'>Ruang ".$kam['nama_ruangan']." (".$kam['kelas'].") - Sisa: $sisa Kasur</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Dokter Penanggung Jawab (DPJP)</label>
                    <select name="dpjp_inap">
                        <?php
                        $q_dok2 = mysqli_query($koneksi, "SELECT * FROM jadwal_dokter ORDER BY nama_dokter ASC");
                        while($dok2 = mysqli_fetch_assoc($q_dok2)) {
                            echo "<option value='".$dok2['nama_dokter']."'>".$dok2['nama_dokter']." (".$dok2['poli'].")</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <!-- AREA UMUM (BERLAKU UNTUK SEMUA) -->
            <div id="form-umum">
                <div class="form-group">
                    <label>Jenis Pembayaran</label>
                    <select name="cara_bayar" required>
                        <option value="UMUM / PRIBADI">UMUM / PRIBADI</option>
                        <option value="BPJS KESEHATAN">BPJS KESEHATAN</option>
                        <option value="ASURANSI SWASTA">ASURANSI SWASTA</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Keluhan Utama / Alasan Masuk</label>
                    <textarea name="keluhan_utama" rows="3" required placeholder="Contoh: Demam 3 hari, rujukan poli..."></textarea>
                </div>
                <button type="submit" name="daftar">✅ Proses Pendaftaran Layanan</button>
            </div>
        </form>
    </div>

    <script>
        // Logika untuk menampilkan dan menyembunyikan area formulir
        function ubahForm() {
            var jenis = document.getElementById('jenis_layanan').value;
            var fj = document.getElementById('form-jalan');
            var fi = document.getElementById('form-inap');
            var figd = document.getElementById('form-igd');
            var fu = document.getElementById('form-umum');

            fj.style.display = "none";
            fi.style.display = "none";
            figd.style.display = "none";
            fu.style.display = "none";

            if (jenis === "Rawat Jalan") {
                fj.style.display = "block";
                fu.style.display = "block";
            } else if (jenis === "Rawat Inap") {
                fi.style.display = "block";
                fu.style.display = "block";
            } else if (jenis === "IGD") {
                figd.style.display = "block";
                fu.style.display = "block";
            }
        }

        // LOGIKA FILTER DOKTER BERDASARKAN POLI (DEPENDENT DROPDOWN)
        function filterDokter() {
            var poliDipilih = document.getElementById('poli_tujuan').value;
            var dropdownDokter = document.getElementById('dpjp_jalan');
            var opsiDokter = dropdownDokter.getElementsByTagName('option');
            var jumlahDitemukan = 0;

            // Reset pilihan dokter setiap kali poli diubah
            dropdownDokter.value = "";

            for (var i = 0; i < opsiDokter.length; i++) {
                // Abaikan option pertama ("-- Pilih Dokter --")
                if (opsiDokter[i].value === "") continue;

                // Ambil data-poli dari masing-masing option
                var poliDokter = opsiDokter[i].getAttribute('data-poli');

                // Jika poli cocok, tampilkan dokternya. Jika tidak, sembunyikan.
                if (poliDokter === poliDipilih) {
                    opsiDokter[i].style.display = "block";
                    jumlahDitemukan++;
                } else {
                    opsiDokter[i].style.display = "none";
                }
            }

            // Pesan peringatan jika belum ada dokter yang terdaftar di poli tersebut
            if (jumlahDitemukan === 0 && poliDipilih !== "") {
                document.getElementById('pesan-dokter').innerText = "*Belum ada jadwal dokter untuk poli ini. Silakan tambahkan di Panel Admin.";
            } else {
                document.getElementById('pesan-dokter').innerText = "";
            }
        }
    </script>
</body>
</html>