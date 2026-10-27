<?php
include 'koneksi.php';

// Menangkap RM dan mencari data di database
$p_nama = ""; $p_rm = ""; $p_tempat = ""; $p_tgl = ""; $p_jk = "";
if(isset($_GET['rm'])) {
    $rm = mysqli_real_escape_string($koneksi, $_GET['rm']);
    $query = mysqli_query($koneksi, "SELECT * FROM pasien WHERE no_rm='$rm'");
    if($d = mysqli_fetch_assoc($query)) {
        $p_nama = $d['nama_lengkap'];
        $p_rm = $d['no_rm'];
        $p_tempat = $d['tempat_lahir'];
        // Format Tgl Lahir jadi DDMMYYYY untuk form stiker
        $p_tgl = date('dmY', strtotime($d['tgl_lahir']));
        $p_jk = $d['jk'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Stiker Pasien V2</title>
    <style>
        @media screen {
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f0f4f8; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
            .form-container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 8px 20px rgba(0,0,0,0.1); width: 420px; }
            h2 { color: #2c3e50; text-align: center; margin-top: 0; margin-bottom: 20px; }
            .form-group { margin-bottom: 12px; }
            label { display: block; margin-bottom: 5px; font-weight: 600; color: #34495e; font-size: 13px; }
            input, select { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-size: 14px; transition: border-color 0.2s; }
            input:focus, select:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
            .row-input { display: flex; gap: 15px; }
            .btn-cetak { width: 100%; background-color: #3b82f6; color: white; border: none; padding: 12px; font-size: 16px; font-weight: bold; border-radius: 6px; cursor: pointer; transition: background 0.3s; margin-top: 15px; }
            .btn-cetak:hover { background-color: #2563eb; }
            #sticker-area { display: none; }
        }
        @media print {
            @page { size: 80mm 30mm; margin: 0; }
            body { margin: 0; padding: 0; background: white; }
            #form-area { display: none !important; }
            #sticker-area { display: flex !important; flex-direction: column; justify-content: center; width: 80mm; height: 30mm; padding: 2mm 4mm; box-sizing: border-box; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: black; }
            .print-row { display: flex; justify-content: space-between; align-items: center; width: 100%; margin-bottom: 3px; font-size: 11px; }
            .print-row.nama-row { margin-bottom: 4px; }
            .label-text { font-weight: normal; margin-right: 3px; white-space: nowrap; }
            .value-text { font-weight: bold; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .nama-value { font-size: 13px; text-transform: uppercase; flex-grow: 1; }
            .rm-group, .jk-group { display: flex; align-items: center; }
            .rm-value { font-size: 14px; letter-spacing: 1px; }
            .ttl-group { display: flex; align-items: center; width: 100%; }
            .ttl-value { flex-grow: 1; }
        }
    </style>
</head>
<body>

    <div id="form-area" class="form-container">
        <h2>Cetak Label Pasien</h2>
        <div class="form-group">
            <label>Nama Pasien</label>
            <input type="text" id="inputNama" value="<?php echo $p_nama; ?>" required>
        </div>
        <div class="form-group">
            <label>Nomor Rekam Medis (8 Digit)</label>
            <input type="text" id="inputNoRM" value="<?php echo $p_rm; ?>" maxlength="8" required>
        </div>
        <div class="row-input">
            <div class="form-group" style="flex: 2;">
                <label>Tempat Lahir</label>
                <input type="text" id="inputTempat" value="<?php echo $p_tempat; ?>" required>
            </div>
            <div class="form-group" style="flex: 1;">
                <label>Tanggal Lahir</label>
                <input type="text" id="inputTglLahir" value="<?php echo $p_tgl; ?>" placeholder="DDMMYYYY" maxlength="8" required>
            </div>
        </div>
        <div class="form-group">
            <label>Jenis Kelamin</label>
            <select id="inputJK">
                <option value="L" <?php if($p_jk == 'L') echo 'selected'; ?>>Laki-laki (L)</option>
                <option value="P" <?php if($p_jk == 'P') echo 'selected'; ?>>Perempuan (P)</option>
            </select>
        </div>
        <!-- Tambahkan autofocus dan alert print opsional pada button -->
        <button class="btn-cetak" onclick="cetakStiker()">🖨️ Cetak Stiker</button>
    </div>

    <div id="sticker-area">
        <div class="print-row nama-row">
            <div style="display: flex; align-items: baseline; width: 100%; overflow: hidden;">
                <span class="label-text">Nama:</span>
                <span class="value-text nama-value" id="printNama">NAMA PASIEN</span>
            </div>
        </div>
        <div class="print-row">
            <div class="rm-group">
                <span class="label-text">No RM:</span>
                <span class="value-text rm-value" id="printNoRM">00000000</span>
            </div>
            <div class="jk-group">
                <span class="label-text">Jenis Kelamin:</span>
                <span class="value-text" id="printJK">L</span>
            </div>
        </div>
        <div class="print-row">
            <div class="ttl-group">
                <span class="label-text">TTL:</span>
                <span class="value-text ttl-value" id="printTTL">KOTA, 00-00-0000</span>
            </div>
        </div>
    </div>

    <script>
        function formatTglLahir(input) {
            if (input.length === 8) return input.substring(0, 2) + "-" + input.substring(2, 4) + "-" + input.substring(4, 8);
            return input;
        }

        function cetakStiker() {
            let nama = document.getElementById('inputNama').value;
            let norm = document.getElementById('inputNoRM').value;
            let tempat = document.getElementById('inputTempat').value;
            let tglLahirRaw = document.getElementById('inputTglLahir').value;
            let jkSelect = document.getElementById('inputJK');
            let jk = jkSelect.options[jkSelect.selectedIndex].value;

            if (!nama || !norm || !tempat || !tglLahirRaw) { alert("Harap isi semua data pasien!"); return; }

            document.getElementById('printNama').innerText = " " + nama;
            document.getElementById('printNoRM').innerText = " " + norm;
            document.getElementById('printJK').innerText = " " + jk;
            let tempatFormat = tempat.charAt(0).toUpperCase() + tempat.slice(1);
            document.getElementById('printTTL').innerText = " " + tempatFormat + ", " + formatTglLahir(tglLahirRaw);

            window.print();
        }
        
        // Auto-print jika parameter rm terdeteksi di URL
        <?php if(isset($_GET['rm'])): ?>
        window.onload = function() {
            // setTimeout(cetakStiker, 500); // Hapus komentar ini jika ingin langsung nge-print tanpa klik tombol
        };
        <?php endif; ?>
    </script>
</body>
</html>