<?php
include 'koneksi.php';

// Menangkap RM dan mencari data di database
$s_rm = ""; $s_nama = ""; $s_tgl = ""; $s_jk = ""; $s_telp = ""; $s_nokartu = ""; $s_peserta = "";
if(isset($_GET['rm'])) {
    $rm = mysqli_real_escape_string($koneksi, $_GET['rm']);
    $query = mysqli_query($koneksi, "SELECT * FROM pasien WHERE no_rm='$rm'");
    if($d = mysqli_fetch_assoc($query)) {
        $s_rm = $d['no_rm'];
        $s_nama = $d['nama_lengkap'];
        $s_tgl = date('d-m-Y', strtotime($d['tgl_lahir'])); // Format DD-MM-YYYY
        $s_jk = ($d['jk'] == 'L') ? 'Laki-laki' : 'Perempuan';
        $s_telp = $d['no_telp'];
        $s_nokartu = $d['no_kartu'];
        // Sinkronisasi otomatis status BPJS
        if($d['cara_bayar'] == 'BPJS KESEHATAN') {
            $s_peserta = "PBPU (Mandiri)"; 
        } else {
            $s_peserta = "UMUM / PRIBADI";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulasi Cetak SEP BPJS</title>
    <style>
        @media screen {
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f0f4f8; padding: 20px; display: flex; justify-content: center; }
            .form-container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 8px 20px rgba(0,0,0,0.1); width: 100%; max-width: 800px; }
            h2 { color: #2c3e50; text-align: center; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-top: 0; }
            .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
            .form-group { margin-bottom: 5px; }
            label { display: block; margin-bottom: 5px; font-weight: 600; color: #34495e; font-size: 13px; }
            input, select, textarea { width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-size: 14px; }
            input:focus, select:focus, textarea:focus { outline: none; border-color: #10b981; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1); }
            .btn-cetak { grid-column: span 2; background-color: #10b981; color: white; border: none; padding: 15px; font-size: 16px; font-weight: bold; border-radius: 6px; cursor: pointer; transition: background 0.3s; margin-top: 20px; }
            .btn-cetak:hover { background-color: #059669; }
            #sep-print-area { display: none; }
        }
        @media print {
            @page { size: 210mm 148mm landscape; margin: 10mm; }
            body { margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background: white; color: black; font-size: 12px; }
            #form-area { display: none !important; }
            #sep-print-area { display: block !important; width: 100%; }
            .header { display: flex; align-items: center; margin-bottom: 15px; }
            .logo-placeholder { width: 150px; height: 40px; border: 2px solid black; display: flex; justify-content: center; align-items: center; font-weight: bold; font-size: 14px; margin-right: 20px; }
            .title-area h3, .title-area h4 { margin: 0; padding: 0; }
            .title-area h3 { font-size: 18px; }
            .title-area h4 { font-size: 14px; font-weight: normal; }
            .content-grid { display: flex; justify-content: space-between; }
            .col-left { width: 60%; }
            .col-right { width: 38%; }
            .data-row { display: flex; margin-bottom: 4px; }
            .data-label { width: 120px; flex-shrink: 0; }
            .data-separator { width: 10px; text-align: center; }
            .data-value { flex-grow: 1; }
            .data-value.bold { font-weight: bold; }
            .data-value.large { font-size: 16px; font-weight: bold; }
            .footer-notes { margin-top: 15px; font-size: 10px; display: flex; justify-content: space-between; }
            .note-text { width: 60%; font-style: italic; }
            .signature-area { width: 35%; display: flex; justify-content: space-between; text-align: center; margin-top: 10px; }
            .sign-box { width: 45%; }
            .sign-space { height: 50px; }
            .sign-name { border-bottom: 1px solid black; min-height: 15px; }
        }
    </style>
</head>
<body>

    <div id="form-area" class="form-container">
        <h2>Simulasi Penerbitan SEP</h2>
        <div class="form-grid">
            <div class="form-col">
                <div class="form-group">
                    <label>No. Kartu BPJS</label>
                    <input type="text" id="inNoKartu" value="<?php echo $s_nokartu; ?>" maxlength="13">
                </div>
                <div class="form-group">
                    <label>Nama Pasien</label>
                    <input type="text" id="inNama" value="<?php echo $s_nama; ?>">
                </div>
                <div class="form-group">
                    <label>Tanggal Lahir (DD-MM-YYYY)</label>
                    <input type="text" id="inTglLahir" value="<?php echo $s_tgl; ?>">
                </div>
                <div class="form-group">
                    <label>Jenis Kelamin</label>
                    <select id="inJK">
                        <option value="Laki-laki" <?php if($s_jk == 'Laki-laki') echo 'selected'; ?>>Laki-laki</option>
                        <option value="Perempuan" <?php if($s_jk == 'Perempuan') echo 'selected'; ?>>Perempuan</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>No. Telepon</label>
                    <input type="text" id="inTelp" value="<?php echo $s_telp; ?>">
                </div>
                <div class="form-group">
                    <label>Jenis Peserta</label>
                    <input type="text" id="inPeserta" value="<?php echo $s_peserta; ?>">
                </div>
            </div>

            <div class="form-col">
                <div class="form-group">
                    <label>No. Rekam Medis</label>
                    <input type="text" id="inNoRM" value="<?php echo $s_rm; ?>">
                </div>
                <div class="form-group">
                    <label>Jenis Rawat</label>
                    <select id="inJnsRawat">
                        <option value="Rawat Jalan">Rawat Jalan</option>
                        <option value="Rawat Inap">Rawat Inap</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Poli Tujuan / Spesialis (Wajib Diisi Manual)</label>
                    <input type="text" id="inPoli" placeholder="Contoh: POLI PENYAKIT DALAM" autofocus required>
                </div>
                <div class="form-group">
                    <label>Faskes Perujuk (Wajib Diisi Manual)</label>
                    <input type="text" id="inPerujuk" placeholder="Contoh: PUSKESMAS KENDALKROSO" required>
                </div>
                <div class="form-group">
                    <label>Diagnosa Awal (Wajib Diisi Manual)</label>
                    <input type="text" id="inDiagnosa" placeholder="Contoh: A09 - Diarrhoea" required>
                </div>
                <div class="form-group">
                    <label>Catatan</label>
                    <textarea id="inCatatan" rows="2"></textarea>
                </div>
            </div>
            <button class="btn-cetak" onclick="cetakSEP()">🖨️ Terbitkan & Cetak SEP</button>
        </div>
    </div>

    <!-- AREA KERTAS CETAK SEP (Sama dengan sebelumnya) -->
    <div id="sep-print-area">
        <div class="header">
            <div class="logo-placeholder">BPJS Kesehatan</div>
            <div class="title-area">
                <h3>SURAT ELIGIBILITAS PESERTA</h3>
                <h4>MINI RS LAB REKAM MEDIS</h4>
            </div>
        </div>
        <div class="content-grid">
            <div class="col-left">
                <div class="data-row"><div class="data-label">No. SEP</div><div class="data-separator">:</div><div class="data-value large" id="prtNoSep"></div></div>
                <div class="data-row"><div class="data-label">Tgl. SEP</div><div class="data-separator">:</div><div class="data-value" id="prtTglSep"></div></div>
                <div class="data-row"><div class="data-label">No. Kartu</div><div class="data-separator">:</div><div class="data-value bold" id="prtNoKartu"></div></div>
                <div class="data-row"><div class="data-label">Nama Peserta</div><div class="data-separator">:</div><div class="data-value" id="prtNama"></div></div>
                <div class="data-row"><div class="data-label">Tgl. Lahir</div><div class="data-separator">:</div><div class="data-value"><span id="prtTglLahir"></span> &nbsp;&nbsp;&nbsp;&nbsp; Kelamin : <span id="prtJK"></span></div></div>
                <div class="data-row"><div class="data-label">No. Telepon</div><div class="data-separator">:</div><div class="data-value" id="prtTelp"></div></div>
                <div class="data-row"><div class="data-label">Sub/Spesialis</div><div class="data-separator">:</div><div class="data-value" id="prtPoli"></div></div>
                <div class="data-row"><div class="data-label">Faskes Perujuk</div><div class="data-separator">:</div><div class="data-value" id="prtPerujuk"></div></div>
                <div class="data-row"><div class="data-label">Diagnosa Awal</div><div class="data-separator">:</div><div class="data-value" id="prtDiagnosa"></div></div>
                <div class="data-row"><div class="data-label">Catatan</div><div class="data-separator">:</div><div class="data-value" id="prtCatatan"></div></div>
            </div>
            <div class="col-right">
                <div class="data-row"><div class="data-label">Peserta</div><div class="data-separator">:</div><div class="data-value" id="prtPeserta"></div></div>
                <div class="data-row"><div class="data-label">COB</div><div class="data-separator">:</div><div class="data-value">-</div></div>
                <div class="data-row"><div class="data-label">Jns. Rawat</div><div class="data-separator">:</div><div class="data-value" id="prtJnsRawat"></div></div>
                <div class="data-row"><div class="data-label">Kelas Rawat</div><div class="data-separator">:</div><div class="data-value">Kelas 3</div></div>
                <div class="data-row"><div class="data-label">No. RM</div><div class="data-separator">:</div><div class="data-value" id="prtNoRM"></div></div>
            </div>
        </div>
        <div class="footer-notes">
            <div class="note-text">
                *Saya menyetujui Ketentuan dan Syarat Penggunaan Kartu BPJS Kesehatan.<br>
                *Surat eligibilitas ini bukan jaminan bahwa tagihan pasti ditanggung.<br>
                Cetak: <span id="prtWaktuCetak"></span>
            </div>
            <div class="signature-area">
                <div class="sign-box"><div>Pasien/Keluarga Pasien</div><div class="sign-space"></div><div class="sign-name" id="prtSignPasien"></div></div>
                <div class="sign-box"><div>Petugas RS</div><div class="sign-space"></div><div class="sign-name"></div></div>
            </div>
        </div>
    </div>

    <script>
        function generateNoSEP() {
            let faskesCode = "0131R012";
            let today = new Date();
            let mm = String(today.getMonth() + 1).padStart(2, '0');
            let yy = String(today.getFullYear()).slice(-2);
            let dateCode = mm + yy;
            let charCode = "V";
            let randomNum = Math.floor(100000 + Math.random() * 900000);
            return faskesCode + dateCode + charCode + randomNum;
        }

        function cetakSEP() {
            let noKartu = document.getElementById('inNoKartu').value || "-";
            let nama = document.getElementById('inNama').value || "-";
            let tglLahir = document.getElementById('inTglLahir').value || "-";
            let jk = document.getElementById('inJK').value || "-";
            let telp = document.getElementById('inTelp').value || "-";
            let peserta = document.getElementById('inPeserta').value || "-";
            let noRm = document.getElementById('inNoRM').value || "-";
            let jnsRawat = document.getElementById('inJnsRawat').value || "-";
            let poli = document.getElementById('inPoli').value || "-";
            let perujuk = document.getElementById('inPerujuk').value || "-";
            let diagnosa = document.getElementById('inDiagnosa').value || "-";
            let catatan = document.getElementById('inCatatan').value || "-";

            let noSep = generateNoSEP();
            let now = new Date();
            let tglSep = String(now.getDate()).padStart(2, '0') + "-" + String(now.getMonth() + 1).padStart(2, '0') + "-" + now.getFullYear();
            let waktuCetak = tglSep + " " + now.toLocaleTimeString('id-ID');

            document.getElementById('prtNoSep').innerText = noSep;
            document.getElementById('prtTglSep').innerText = tglSep;
            document.getElementById('prtNoKartu').innerText = noKartu;
            document.getElementById('prtNama').innerText = nama.toUpperCase();
            document.getElementById('prtTglLahir').innerText = tglLahir;
            document.getElementById('prtJK').innerText = jk.charAt(0);
            document.getElementById('prtTelp').innerText = telp;
            document.getElementById('prtPoli').innerText = poli.toUpperCase();
            document.getElementById('prtPerujuk').innerText = perujuk.toUpperCase();
            document.getElementById('prtDiagnosa').innerText = diagnosa.toUpperCase();
            document.getElementById('prtCatatan').innerText = catatan;
            document.getElementById('prtPeserta').innerText = peserta;
            document.getElementById('prtJnsRawat').innerText = jnsRawat;
            document.getElementById('prtNoRM').innerText = noRm;
            document.getElementById('prtWaktuCetak').innerText = waktuCetak;
            document.getElementById('prtSignPasien').innerText = nama.toUpperCase();

            window.print();
        }
    </script>
</body>
</html>