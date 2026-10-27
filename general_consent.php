<?php
// Mengunci zona waktu server PHP ke Waktu Indonesia Barat (WIB)
date_default_timezone_set('Asia/Jakarta');
include 'koneksi.php';

$cetak_reg = isset($_GET['cetak']) ? $_GET['cetak'] : '';
$cari = isset($_GET['cari']) ? $_GET['cari'] : '';
?>
<!DOCTYPE html>
<html>
<head>
    <title>General Consent - SIMRS</title>
    <style>
        /* TAMPILAN UNTUK LAYAR (UI/UX) */
        @media screen {
            body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f0f4f8; padding: 20px; margin: 0; }
            .wadah { background: white; padding: 25px; border-radius: 8px; max-width: 1000px; margin: 0 auto; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
            .search-box { display: flex; gap: 10px; margin-bottom: 20px; }
            .search-box input { flex: 1; padding: 10px; border: 1px solid #cbd5e1; border-radius: 5px; font-size: 14px; }
            .search-box button { background: #3b82f6; color: white; border: none; padding: 10px 20px; font-weight: bold; border-radius: 5px; cursor: pointer; }
            .search-box a.reset { background: #94a3b8; color: white; text-decoration: none; padding: 10px 20px; font-weight: bold; border-radius: 5px; }
            
            table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px; }
            th, td { border: 1px solid #cbd5e1; padding: 12px; text-align: left; }
            th { background: #1e293b; color: white; }
            tr:hover { background-color: #f8fafc; }
            .btn-aksi { background: #10b981; color: white; text-decoration: none; padding: 6px 12px; border-radius: 4px; font-weight: bold; font-size: 13px; display: inline-block; }
            .btn-aksi:hover { background: #059669; }

            .kertas { background: white; padding: 40px; max-width: 800px; margin: 20px auto; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
            .nav-cetak { max-width: 800px; margin: 0 auto; display: flex; justify-content: space-between; }
            .btn-print-now { background: #3b82f6; color: white; border: none; padding: 10px 20px; font-weight: bold; cursor: pointer; border-radius: 5px; }
            .btn-kembali { background: #ef4444; color: white; text-decoration: none; padding: 10px 20px; font-weight: bold; border-radius: 5px; }
        }
        
        /* TAMPILAN SAAT TOMBOL PRINT DITEKAN */
        @media print {
            body { font-family: 'Arial', sans-serif; margin: 0; padding: 0; font-size: 10px; background: white; color: black; }
            .wadah, .nav-cetak { display: none !important; }
            .kertas { width: 100%; max-width: 100%; box-shadow: none; padding: 0; margin: 0; }
            @page { margin: 1cm; } 
        }

        /* Format Kertas Dokumen */
        .kop-surat { display: flex; border-bottom: 2px solid black; padding-bottom: 5px; margin-bottom: 10px; align-items: center; }
        .kop-kiri { width: 40%; font-weight: bold; font-size: 12px; }
        .kop-kanan { width: 60%; border: 1px solid black; padding: 3px; font-size: 9px; }
        .kop-kanan table { width: 100%; border-collapse: collapse; }
        .kop-kanan td { padding: 1px; }
        
        .judul-dokumen { text-align: center; font-size: 12px; font-weight: bold; margin-bottom: 10px; line-height: 1.2; }
        
        .tabel-identitas { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
        .tabel-identitas td { padding: 2px; vertical-align: top; }
        .tabel-identitas td:first-child { width: 25%; }
        .tabel-identitas td:nth-child(2) { width: 2%; }
        
        .section-title { font-weight: bold; margin-top: 8px; margin-bottom: 2px; text-transform: uppercase; font-size: 10px; }
        .isi-pernyataan { text-align: justify; line-height: 1.15; }
        .isi-pernyataan ol { padding-left: 18px; margin-top: 2px; margin-bottom: 4px; }
        .isi-pernyataan li { margin-bottom: 2px; }
        .box-cek { font-size: 12px; }

        .ttd-area { width: 100%; margin-top: 15px; border-collapse: collapse; text-align: center; }
        .ttd-area td { width: 33%; vertical-align: bottom; height: 50px; } 
        .nama-terang { font-weight: bold; text-decoration: underline; }
    </style>
</head>
<body>

    <?php if (empty($cetak_reg)): ?>
    <!-- ========================================== -->
    <!-- MODE 1: TABEL PENCARIAN & DAFTAR KUNJUNGAN -->
    <!-- ========================================== -->
    <div class="wadah">
        <h3 style="margin-top:0; color:#1e3a8a;">📄 Dokumen General Consent</h3>
        <p style="color: #64748b; font-size: 13px; margin-top: -10px;">Cari pasien yang sudah terdaftar di kunjungan untuk dicetakkan dokumen persetujuan umumnya.</p>
        
        <form method="GET" class="search-box">
            <input type="text" name="cari" value="<?php echo htmlspecialchars($cari); ?>" placeholder="Ketik No. Registrasi, No. RM, atau Nama Pasien..." required>
            <button type="submit">🔍 Cari</button>
            <?php if(!empty($cari)): ?>
                <a href="general_consent.php" class="reset">Reset</a>
            <?php endif; ?>
        </form>

        <table>
            <tr>
                <th>Tanggal Masuk</th>
                <th>No. Registrasi</th>
                <th>No. RM & Nama Pasien</th>
                <th>Jenis Layanan</th>
                <th>Aksi</th>
            </tr>
            <?php
            $sql = "SELECT k.no_registrasi, k.tgl_masuk, k.jenis_layanan, p.no_rm, p.nama_lengkap 
                    FROM kunjungan k JOIN pasien p ON k.no_rm = p.no_rm ";
            if (!empty($cari)) {
                $sql .= "WHERE k.no_registrasi LIKE '%$cari%' OR p.no_rm LIKE '%$cari%' OR p.nama_lengkap LIKE '%$cari%' ";
            }
            $sql .= "ORDER BY k.tgl_masuk DESC LIMIT 50";
            $query = mysqli_query($koneksi, $sql);

            if(mysqli_num_rows($query) == 0) {
                echo "<tr><td colspan='5' style='text-align:center; padding: 20px;'>Data pendaftaran tidak ditemukan.</td></tr>";
            }

            while($d = mysqli_fetch_assoc($query)) {
            ?>
            <tr>
                <td><?php echo date('d-m-Y H:i', strtotime($d['tgl_masuk'])); ?></td>
                <td><strong><?php echo $d['no_registrasi']; ?></strong></td>
                <td><?php echo $d['nama_lengkap']; ?><br><small style="color:#64748b;">RM: <?php echo $d['no_rm']; ?></small></td>
                <td><?php echo $d['jenis_layanan']; ?></td>
                <td>
                    <a href="general_consent.php?cetak=<?php echo $d['no_registrasi']; ?>" class="btn-aksi">🖨️ Cetak Form</a>
                </td>
            </tr>
            <?php } ?>
        </table>
    </div>

    <?php else: ?>
    <!-- ========================================== -->
    <!-- MODE 2: KERTAS DOKUMEN SIAP CETAK          -->
    <!-- ========================================== -->
    <?php
        $q_cetak = mysqli_query($koneksi, "SELECT k.no_registrasi, k.tgl_masuk, k.jenis_layanan, k.cara_bayar, 
                   p.nama_lengkap, p.no_rm, p.tgl_lahir, p.jk, p.alamat, p.nama_pj, p.telp_pj, p.hub_pj 
                   FROM kunjungan k JOIN pasien p ON k.no_rm = p.no_rm 
                   WHERE k.no_registrasi = '$cetak_reg'");
        $data = mysqli_fetch_assoc($q_cetak);

        if (!$data) {
            echo "<script>alert('Data tidak valid!'); window.location='general_consent.php';</script>";
            exit;
        }

        // LOGIKA PENENTUAN STATUS UMUR DAN NAMA PENANDATANGAN
        $tgl_lahir = new DateTime($data['tgl_lahir']);
        $hari_ini = new DateTime('today');
        $umur = $hari_ini->diff($tgl_lahir)->y;

        if ($umur >= 18) {
            $label_ttd = "Pasien / Penanggung Jawab";
            $nama_ttd = $data['nama_lengkap']; // Cetak nama pasien jika sudah dewasa
        } else {
            $label_ttd = "Orang Tua / Wali Pasien";
            $nama_ttd = $data['nama_pj']; // Cetak nama wali jika masih anak-anak
        }
    ?>
    
    <div class="nav-cetak">
        <a href="general_consent.php" class="btn-kembali">⬅️ Kembali ke Daftar</a>
        <button class="btn-print-now" onclick="window.print()">🖨️ Print Dokumen Sekarang</button>
    </div>

    <div class="kertas">
        <div class="kop-surat">
            <div class="kop-kiri">
                Rumah Sakit<br>
                Simulasi Mini Hospital
            </div>
            <div class="kop-kanan">
                <table>
                    <tr><td>NAMA PASIEN</td><td>:</td><td><strong><?php echo $data['nama_lengkap']; ?></strong></td></tr>
                    <tr><td>TGL LAHIR</td><td>:</td><td><?php echo date('d-m-Y', strtotime($data['tgl_lahir'])); ?></td></tr>
                    <tr><td>NO. REKAM MEDIS</td><td>:</td><td><strong><?php echo $data['no_rm']; ?></strong></td></tr>
                </table>
            </div>
        </div>

        <div class="judul-dokumen">
            PERSETUJUAN UMUM<br>
            (GENERAL CONSENT)<br>
            <span style="font-size: 9px; font-weight: normal;">PASIEN DAN ATAU WALI DIMINTA MEMBACA, MEMAHAMI DAN MENGISI INFORMASI BERIKUT</span>
        </div>

        <div class="isi-pernyataan">
            Saya yang bertanda tangan di bawah ini:
            <table class="tabel-identitas">
                <tr><td>Nama</td><td>:</td><td><?php echo $data['nama_pj']; ?></td></tr>
                <tr><td>Alamat</td><td>:</td><td><?php echo $data['alamat']; ?></td></tr>
                <tr><td>No. Telpon</td><td>:</td><td><?php echo $data['telp_pj']; ?></td></tr>
            </table>
            
            dengan ini sesungguhnya menyatakan persetujuan terhadap diri saya/istri/suami/anak/ayah/ibu saya*), yaitu:
            
            <table class="tabel-identitas">
                <tr><td>Nama</td><td>:</td><td><strong><?php echo $data['nama_lengkap']; ?></strong></td></tr>
                <tr><td>Tanggal Lahir</td><td>:</td><td><?php echo date('Y-m-d', strtotime($data['tgl_lahir'])); ?></td></tr>
                <tr><td>Nomor Rekam Medis</td><td>:</td><td><?php echo $data['no_rm']; ?></td></tr>
                <tr><td>Jenis Kelamin</td><td>:</td><td><?php echo ($data['jk'] == 'L') ? 'Laki-Laki' : 'Perempuan'; ?></td></tr>
            </table>

            <div class="section-title">I. PERSETUJUAN UNTUK PERAWATAN DAN PENGOBATAN</div>
            <ol>
                <li>Saya mengetahui bahwa saya memiliki kondisi yang membutuhkan perawatan medis, saya mengizinkan dokter dan profesional kesehatan lainnya untuk melakukan prosedur diagnostik dan untuk memberikan pengobatan medis.</li>
                <li>Prosedur diagnostik dan perawatan medis tersebut termasuk elektrokardiogram, X-ray, tes darah, terapi fisik, pasang infus, pemberian vaksinasi, pasang NGT, pasang urine catheter, pemberian oksigen, dll.</li>
                <li>Saya memahami bahwa Pasien/Keluarga tidak diperkenankan untuk membawa/memakai alat kesehatan pribadi selama dalam perawatan.</li>
                <li>Saya sadar bahwa bahwa praktik kedokteran bukanlah ilmu pasti dan saya mengakui bahwa tidak ada jaminan atas hasil apapun terhadap prosedur atau pemeriksaan yang dilakukan.</li>
                <li>Saya mengerti dan memahami bahwa:
                    <ol type="a">
                        <li>Saya memiliki hak untuk mengajukan pertanyaan tentang pengobatan yang diusulkan setiap saat.</li>
                        <li>Saya memiliki hak untuk persetujuan, atau menolak persetujuan, untuk setiap prosedur/terapi.</li>
                        <li>Banyak dokter pada staf medis rumah sakit yang bukan karyawan tetapi staf tamu yang telah diberikan hak untuk merawat pasien.</li>
                        <li>Terdapat peserta didik/pelatihan yang ikut berpartisipasi dalam asuhan pasien sebagai bagian dari pendidikan mereka dengan pengawasan staf rumah sakit.</li>
                    </ol>
                </li>
            </ol>

            <div class="section-title">II. BARANG-BARANG MILIK PASIEN</div>
            <ol>
                <li>Saya telah memahami bahwa rumah sakit tidak bertanggung jawab atas semua kehilangan barang barang milik saya, dan saya secara pribadi bertanggung jawab terhadap barang berharga yang saya miliki.</li>
                <li>Saya mengerti bahwa saya harus memberitahu/menitipkan pada rumah sakit jika saya memiliki gigi palsu, kacamata, lensa kontak, prosthetics atau barang lainnya yang diamankan.</li>
            </ol>

            <div class="section-title">III. PERSETUJUAN PELEPASAN INFORMASI MEDIS</div>
            <ol>
                <li>Saya memahami informasi yang ada dalam diri Saya, termasuk diagnosis, hasil laboratorium dan hasil tes diagnostik yang akan digunakan untuk perawatan medis, RS akan menjamin kerahasiaannya.</li>
                <li>Saya memahami bahwa rumah sakit ini merupakan bagian dari jejaring fasilitas kesehatan yang memiliki integrasi data.</li>
                <li>Saya memberi wewenang kepada rumah sakit untuk memberikan informasi diagnosis dan hasil pelayanan bila diperlukan untuk memproses klaim asuransi/lembaga penjamin.</li>
                <li>Saya memberi wewenang kepada rumah sakit untuk memberikan informasi tentang diagnosis dan pengobatan saya kepada anggota keluarga saya, yaitu kepada:<br>
                    (Nama, Hubungan Keluarga, No telephone)<br>
                    ................................................................................................................................
                </li>
                <li>Saya menyetujui bahwa berdasarkan Peraturan Menteri Kesehatan Nomor 24 Tahun 2022 tentang Rekam Medis, faskes wajib membuka akses dan mengirim data ke Kementerian Kesehatan melalui SATUSEHAT.</li>
                <li>Saya menyetujui untuk menerima dan membuka data Pasien dari Fasilitas Pelayanan Kesehatan lainnya melalui SATUSEHAT.</li>
            </ol>

            <div class="section-title">IV. HAK DAN TANGGUNG JAWAB SERTA TATA TERTIB PASIEN/PENUNGGU PASIEN</div>
            <ol>
                <li>Saya memiliki hak untuk mengambil bagian dalam keputusan mengenai penyakit saya dan dalam hal perawatan medis.</li>
                <li>Saya telah mendapat informasi tentang "Hak dan Tanggung Jawab Pasien" melalui leaflet dan banner.</li>
                <li>Saya mengerti bahwa saya tidak diperbolehkan mendokumentasikan (mengambil foto atau merekam dll) dalam bentuk apapun semua proses pelayanan kesehatan tanpa seizin rumah sakit.</li>
            </ol>

            <div class="section-title">V. INFORMASI RAWAT INAP</div>
            <ol>
                <li>Saya telah menerima informasi tentang peraturan yang diberlakukan oleh rumah sakit dan keluarga bersedia mematuhi jam berkunjung pasien sesuai aturan.</li>
                <li>Anggota keluarga yang menunggu, bersedia untuk selalu memakai tanda pengenal khusus, dan demi keamanan bersedia dimintai identitasnya.</li>
            </ol>

            <div class="section-title">VI. INFORMASI BIAYA</div>
            <ol>
                <li>Saya memahami tentang informasi biaya pengobatan atau tindakan yang dijelaskan oleh petugas RS dan bersedia membayar seluruh biaya perawatan.</li>
                <li>Saya memahami adanya tagihan yang bersifat sementara dan akan pro aktif menanyakan tagihan sementara.</li>
                <li>Rumah Sakit tidak pernah meminta Pasien/Keluarga Pasien untuk melakukan transfer sejumlah dana untuk tindakan medis melalui telepon.</li>
                <li>Rumah Sakit tidak melayani proses REIMBURSEMENT klaim pada pasien dengan jaminan BPJS kesehatan.</li>
                <li>Saya memahami bila pengobatan/tindakan medis/pemeriksaan diagnostik per item dengan biaya lebih dari:<br>
                    <span class="box-cek">☐</span> Rp. 500.000,- &nbsp; <span class="box-cek">☐</span> Rp. 1.000.000,- &nbsp; <span class="box-cek">☐</span> Tidak Terbatas &nbsp; <span class="box-cek">☐</span> Sesuai Ketentuan Penjamin<br>
                    Akan dilaksanakan setelah saya menyetujui pengobatan tersebut.
                </li>
            </ol>

            <div class="section-title">VII. PRIVASI KHUSUS</div>
            Pasien menginginkan privasi khusus: <span class="box-cek">☐</span> Tidak &nbsp; <span class="box-cek">☐</span> Ya, jika Ya (lanjut ke pilihan di bawah ini):<br>
            <span class="box-cek">☐</span> Pasien tidak berkenan dijenguk &nbsp; <span class="box-cek">☐</span> Pasien hanya boleh dijenguk oleh keluarga &nbsp; <span class="box-cek">☐</span> Pasien perlu istirahat<br>
            <span class="box-cek">☐</span> Menolak dilayani oleh petugas laki-laki/perempuan *)<br>
            <span class="box-cek">☐</span> Ditempatkan dalam satu kamar selama perawatan (hubungan keluarga: suami/istri/anak *)<br>
            <span class="box-cek">☐</span> Lain-lain ........................................................................

            <div class="section-title">VIII. NILAI-NILAI dan KEPERCAYAAN</div>
            Pasien mempunyai nilai-nilai dan kepercayaan: <span class="box-cek">☐</span> Tidak &nbsp; <span class="box-cek">☐</span> Ya, jika Ya (lanjut ke pilihan di bawah ini):<br>
            <span class="box-cek">☐</span> Menolak dilakukan transfusi darah &nbsp; <span class="box-cek">☐</span> Menolak pulang hari tertentu (sebutkan: ....................)<br>
            <span class="box-cek">☐</span> Menolak dilakukan imunisasi pada anaknya &nbsp; <span class="box-cek">☐</span> Tidak makan suatu jenis makanan tertentu (sebutkan: ....................)

            <div class="section-title">IX. KETENTUAN PADA MASA PASCA PENCABUTAN KEBIJAKAN PPKM:</div>
            <ol type="a">
                <li>Pasien rencana operasi elektif/cito, perawatan ICU dan pasien dengan panas serta batuk pilek harus dilakukan swab antigen.</li>
                <li>Pasien yang dirawat dapat ditunggu oleh 1 (satu) orang penunggu dalam keadaan sehat, kecuali bagi pasien bayi dan balita.</li>
                <li>Pasien dan penunggu selama dalam ruang perawatan wajib menggunakan masker.</li>
                <li>Jika pemeriksaan Antigen (+)/PCR positif (+) pasien dirawat di Ruang Isolasi dan tidak dapat ditunggu kecuali untuk pasien bayi/anak/geriatri.</li>
            </ol>
            
            <p style="margin-top: 5px;">Dengan tanda tangan saya di bawah, saya menyatakan bahwa saya telah membaca, memahami dan menyetujui item pada Persetujuan Umum/General Consent ini.</p>
        </div>

        <table class="ttd-area">
            <tr>
                <td>
                    Tanggal <?php echo date('d-m-Y'); ?> / Jam <?php echo date('H:i'); ?><br>
                    <?php echo $label_ttd; ?><br><br><br><br>
                    <span class="nama-terang">( <?php echo $nama_ttd; ?> )</span><br>
                    Nama dan Tanda Tangan
                </td>
                <td>
                    <br>Saksi (Pihak Pasien)<br><br><br><br>
                    <span class="nama-terang">( ........................................ )</span><br>
                    Nama dan Tanda Tangan
                </td>
                <td>
                    Tanggal <?php echo date('d-m-Y'); ?> / Jam <?php echo date('H:i'); ?><br>
                    Pemberi Informasi<br><br><br><br>
                    <span class="nama-terang">( Petugas Pendaftaran )</span><br>
                    Nama dan Tanda Tangan
                </td>
            </tr>
        </table>
        
        <p style="font-size: 8px; margin-top: 10px;">*) lingkari salah satu &nbsp;&nbsp;&nbsp; ☐ beri tanda check list</p>
    </div>
    <?php endif; ?>

</body>
</html>