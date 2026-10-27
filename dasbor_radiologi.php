<?php
include 'koneksi.php';

// Buat folder penyimpanan foto radiologi jika belum ada
$dir_upload = "uploads/radiologi/";
if (!file_exists($dir_upload)) {
    mkdir($dir_upload, 0777, true);
}

// Proses Input Hasil & Upload Foto
if (isset($_POST['simpan_hasil'])) {
    $no_order = mysqli_real_escape_string($koneksi, $_POST['no_order']);
    $radiolog = mysqli_real_escape_string($koneksi, $_POST['petugas_radiolog']);
    $ekspertise = mysqli_real_escape_string($koneksi, $_POST['ekspertise_klinis']);
    $kesan = mysqli_real_escape_string($koneksi, $_POST['kesan_radiologi']);
    
    // Upload File Foto Gambar
    $nama_file = "";
    if (isset($_FILES['berkas_foto']['name']) && $_FILES['berkas_foto']['name'] != "") {
        $ext = pathinfo($_FILES['berkas_foto']['name'], PATHINFO_EXTENSION);
        $nama_file = $no_order . "_" . time() . "." . $ext;
        move_uploaded_file($_FILES['berkas_foto']['tmp_name'], $dir_upload . $nama_file);
    }

    $q_update = "UPDATE order_radiologi SET 
                 status_radiologi = 'Selesai',
                 petugas_radiolog = '$radiolog',
                 ekspertise_klinis = '$ekspertise',
                 kesan_radiologi = '$kesan',
                 tgl_hasil = NOW()";
    
    if ($nama_file != "") {
        $q_update .= ", berkas_foto = '$nama_file'";
    }
    $q_update .= " WHERE no_order = '$no_order'";

    if (mysqli_query($koneksi, $q_update)) {
        echo "<script>alert('Hasil Radiologi & Ekspertise Berhasil Disimpan!'); window.location='dasbor_radiologi.php';</script>";
    }
}

// Tarik daftar antrean radiologi
$q_list = mysqli_query($koneksi, "SELECT r.*, p.nama_lengkap, p.jk, p.tgl_lahir 
          FROM order_radiologi r JOIN pasien p ON r.no_rm = p.no_rm 
          ORDER BY r.id_order DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Instalasi Radiologi & Imaging</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f0f4f8; padding: 20px; color: #1e293b; }
        .wadah { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h3 { color: #0284c7; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 13px; }
        th, td { border: 1px solid #cbd5e1; padding: 10px; text-align: left; }
        th { background: #0284c7; color: white; }
        tr:nth-child(even) { background: #f8fafc; }
        .badge-pending { background: #fef3c7; color: #d97706; padding: 4px 8px; border-radius: 4px; font-weight: bold; }
        .badge-done { background: #dcfce7; color: #15803d; padding: 4px 8px; border-radius: 4px; font-weight: bold; }
        .btn-input { background: #0284c7; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; text-decoration: none; font-weight: bold; font-size: 12px; }
        
        /* Modal Popup Input */
        .modal { display: none; position: fixed; z-index: 10; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; margin: 3% auto; padding: 20px; border-radius: 8px; width: 60%; max-height: 85vh; overflow-y: auto; }
        .close { float: right; font-size: 24px; font-weight: bold; cursor: pointer; color: #ef4444; }
        textarea, input[type="text"] { width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; margin-bottom: 10px; }
    </style>
    <script>
        function bukaModal(order) {
            document.getElementById('modal_no_order').value = order.no_order;
            document.getElementById('modal_info').innerHTML = "<b>Order:</b> " + order.no_order + " | <b>Pasien:</b> " + order.nama_lengkap + " (" + order.no_rm + ")<br><b>Pemeriksaan:</b> " + order.jenis_pemeriksaan;
            document.getElementById('modal_ekspertise').value = order.ekspertise_klinis || '';
            document.getElementById('modal_kesan').value = order.kesan_radiologi || '';
            document.getElementById('modal_radiolog').value = order.petugas_radiolog || 'dr. Sp.Rad';
            document.getElementById('modalInput').style.display = 'block';
        }
        function tutupModal() {
            document.getElementById('modalInput').style.display = 'none';
        }
    </script>
</head>
<body>
    <div class="wadah">
        <h3>📷 Instalasi Radiologi & Penunjang Imaging</h3>
        
        <table>
            <thead>
                <tr>
                    <th>Waktu / No. Order</th>
                    <th>Pasien / No. RM</th>
                    <th>Unit & Dokter Pengirim</th>
                    <th>Jenis Pemeriksaan</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while($d = mysqli_fetch_assoc($q_list)): ?>
                <tr>
                    <td>
                        <b><?php echo $d['no_order']; ?></b><br>
                        <small color="#64748b"><?php echo date('d/m/Y H:i', strtotime($d['tgl_order'])); ?></small>
                    </td>
                    <td>
                        <b><?php echo $d['nama_lengkap']; ?></b><br>
                        RM: <?php echo $d['no_rm']; ?> (<?php echo $d['no_registrasi']; ?>)
                    </td>
                    <td>
                        <?php echo $d['unit_pengirim']; ?><br>
                        <small><i><?php echo $d['dokter_pengirim']; ?></i></small>
                    </td>
                    <td><b><?php echo $d['jenis_pemeriksaan']; ?></b></td>
                    <td>
                        <?php if($d['status_radiologi'] == 'Selesai'): ?>
                            <span class="badge-done">✔ Selesai</span>
                        <?php else: ?>
                            <span class="badge-pending">⏳ Menunggu Hasil</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="btn-input" onclick='bukaModal(<?php echo json_encode($d); ?>)'>
                            📝 Input Foto & Ekspertise
                        </button>
                        <?php if(!empty($d['berkas_foto'])): ?>
                            <br><a href="uploads/radiologi/<?php echo $d['berkas_foto']; ?>" target="_blank" style="font-size:11px; color:#0284c7; margin-top:4px; display:inline-block;">🖼️ Lihat Foto</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- POPUP MODAL INPUT HASIL -->
    <div id="modalInput" class="modal">
        <div class="modal-content">
            <span class="close" onclick="tutupModal()">&times;</span>
            <h4 style="color: #0284c7; margin-top:0;">📝 Form Hasil & Ekspertise Dokter Radiologi</h4>
            <div id="modal_info" style="background:#f1f5f9; padding:10px; border-radius:5px; font-size:13px; margin-bottom:15px;"></div>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="no_order" id="modal_no_order">
                
                <label><b>Unggah Berkas Gambar / Foto Rontgen (JPG/PNG/PDF):</b></label>
                <input type="file" name="berkas_foto" accept="image/*,.pdf" style="margin-bottom: 12px;">

                <label><b>Ekspertise / Bacaan Klinis Radiologi:</b></label>
                <textarea name="ekspertise_klinis" id="modal_ekspertise" rows="5" placeholder="Hasil deskripsi gambaran radiologi..." required></textarea>

                <label><b>Kesan / Kesimpulan Radiologis:</b></label>
                <textarea name="kesan_radiologi" id="modal_kesan" rows="2" placeholder="Kesan (Contoh: Cardiomegaly tanpa bendungan paru)..."></textarea>

                <label><b>Dokter Spesialis Radiologi / Radiografer:</b></label>
                <input type="text" name="petugas_radiolog" id="modal_radiolog" required>

                <button type="submit" name="simpan_hasil" class="btn-input" style="width:100%; padding:10px; font-size:14px; background:#10b981;">💾 Simpan Hasil Radiologi</button>
            </form>
        </div>
    </div>
</body>
</html>