<?php
include 'koneksi.php';

// PROSES ALOKASI KAMAR & MASUK RAWAT INAP
if (isset($_POST['proses_transfer'])) {
    $no_spri      = mysqli_real_escape_string($koneksi, $_POST['no_spri']);
    $no_rm        = mysqli_real_escape_string($koneksi, $_POST['no_rm']);
    $kamar_tujuan = mysqli_real_escape_string($koneksi, $_POST['kamar_tujuan']);
    $dpjp_bangsal = mysqli_real_escape_string($koneksi, $_POST['dpjp_bangsal']);
    $cara_bayar   = mysqli_real_escape_string($koneksi, $_POST['cara_bayar']);
    $keluhan      = mysqli_real_escape_string($koneksi, $_POST['diagnosa_indikasi']);
    
    // 1. Buat No Registrasi Rawat Inap Baru
    $no_reg_ri = "REG-RI-" . time();

    // 2. Daftarkan sebagai Kunjungan Rawat Inap Aktif
    $insert_ranap = mysqli_query($koneksi, "INSERT INTO kunjungan (no_registrasi, no_rm, jenis_layanan, tujuan_layanan, dpjp, cara_bayar, keluhan_utama, status_kunjungan)
                    VALUES ('$no_reg_ri', '$no_rm', 'Rawat Inap', '$kamar_tujuan', '$dpjp_bangsal', '$cara_bayar', 'Transfer via $no_spri ($keluhan)', 'Aktif')");

    if ($insert_ranap) {
        // 3. Tambah jumlah kasur terisi di kamar bangsal yang dipilih
        mysqli_query($koneksi, "UPDATE kamar_rawat SET terisi = terisi + 1 WHERE nama_ruangan = '$kamar_tujuan'");

        // 4. Update status SPRI menjadi Selesai Transfer
        mysqli_query($koneksi, "UPDATE spri SET status_spri = 'Selesai Transfer', rencana_kamar = '$kamar_tujuan' WHERE no_spri = '$no_spri'");

        echo "<script>alert('Pasien Berhasil Masuk Bangsal $kamar_tujuan! Nomor Rawat Inap: $no_reg_ri'); window.location='admisi_ranap.php';</script>";
        exit;
    } else {
        echo "<script>alert('Gagal melakukan alokasi kamar bangsal.');</script>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admisi & Transfer Rawat Inap</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f4f8; padding: 20px; }
        .wadah { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); max-width: 1200px; margin: 0 auto; }
        h2 { color: #b45309; margin-top: 0; border-bottom: 2px solid #fef3c7; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 13px; }
        th, td { border: 1px solid #e2e8f0; padding: 10px; text-align: left; vertical-align: top; }
        th { background: #d97706; color: white; }
        tr:hover { background: #fffbeb; }
        .badge-unit { background: #fee2e2; color: #dc2626; padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; }
        .badge-spri { background: #fef3c7; color: #b45309; font-family: monospace; font-weight: bold; padding: 3px 6px; border-radius: 4px; }
        .btn-modal { background: #10b981; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-modal:hover { background: #059669; }
        .btn-print { background: #475569; color: white; text-decoration: none; padding: 6px 10px; border-radius: 4px; font-size: 12px; display: inline-block; margin-top: 5px; }
        
        /* Modal Popup */
        .modal { display: none; position: fixed; z-index: 99; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; margin: 8% auto; padding: 25px; border-radius: 8px; width: 500px; box-shadow: 0 8px 24px rgba(0,0,0,0.2); }
        .modal-content select, .modal-content input { width: 100%; padding: 10px; margin: 8px 0 15px 0; border: 1px solid #cbd5e1; border-radius: 5px; box-sizing: border-box; }
        .btn-simpan { background: #b45309; color: white; border: none; padding: 10px; border-radius: 5px; width: 100%; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>

    <div class="wadah">
        <h2>🛏️ Admisi & Antrean Transfer Rawat Inap (SPRI)</h2>
        <p style="color: #64748b; font-size: 14px;">Daftar pasien IGD atau Rawat Jalan yang telah mendapatkan Surat Perintah Rawat Inap (SPRI) dan menunggu ketersediaan tempat tidur bangsal.</p>

        <table>
            <tr>
                <th>No. SPRI & Tgl</th>
                <th>Pasien & No. RM</th>
                <th>Asal Unit & Perujuk</th>
                <th>DPJP Bangsal</th>
                <th>Indikasi Rawat & Catatan</th>
                <th>Aksi Transfer</th>
            </tr>
            <?php
            $query = mysqli_query($koneksi, "SELECT s.*, p.nama_lengkap, p.jk, p.cara_bayar 
                                             FROM spri s 
                                             JOIN pasien p ON s.no_rm = p.no_rm 
                                             WHERE s.status_spri = 'Menunggu Kamar' 
                                             ORDER BY s.tgl_spri DESC");

            if (mysqli_num_rows($query) == 0) {
                echo "<tr><td colspan='6' style='text-align:center; padding: 30px; color: #64748b;'>Belum ada pasien yang menunggu kamar rawat inap saat ini.</td></tr>";
            }

            while($d = mysqli_fetch_assoc($query)) {
            ?>
            <tr>
                <td>
                    <span class="badge-spri"><?php echo $d['no_spri']; ?></span><br>
                    <small style="color: #64748b;"><?php echo date('d-m-Y H:i', strtotime($d['tgl_spri'])); ?></small>
                </td>
                <td>
                    <strong><?php echo $d['nama_lengkap']; ?></strong> (<?php echo $d['jk']; ?>)<br>
                    <small>RM: <?php echo $d['no_rm']; ?> | Bayar: <b><?php echo $d['cara_bayar']; ?></b></small>
                </td>
                <td>
                    <span class="badge-unit"><?php echo $d['unit_asal']; ?></span><br>
                    <small>Dokter: <?php echo $d['dokter_perujuk']; ?></small>
                </td>
                <td><strong><?php echo $d['dpjp_bangsal']; ?></strong></td>
                <td>
                    <b>Indikasi:</b> <?php echo $d['diagnosa_indikasi']; ?><br>
                    <small><i>"<?php echo $d['catatan_khusus'] ? $d['catatan_khusus'] : '-'; ?>"</i></small>
                </td>
                <td style="text-align: center;">
                    <button class="btn-modal" onclick="bukaModal('<?php echo $d['no_spri']; ?>', '<?php echo $d['no_rm']; ?>', '<?php echo addslashes($d['nama_lengkap']); ?>', '<?php echo addslashes($d['dpjp_bangsal']); ?>', '<?php echo addslashes($d['cara_bayar']); ?>', '<?php echo addslashes($d['diagnosa_indikasi']); ?>')">
                        🚪 Tempatkan Kamar
                    </button>
                    <br>
                    <a href="cetak_spri.php?spri=<?php echo $d['no_spri']; ?>" target="_blank" class="btn-print">🖨️ Cetak SPRI</a>
                </td>
            </tr>
            <?php } ?>
        </table>
    </div>

    <!-- MODAL POPUP PENEMPATAN KAMAR -->
    <div id="modalKamar" class="modal">
        <div class="modal-content">
            <h3 style="margin-top: 0; color: #b45309;">Penempatan Kamar Rawat Inap</h3>
            <p id="modalInfoPasien" style="font-size: 14px; background: #fffbeb; padding: 10px; border-radius: 5px;"></p>

            <form method="POST">
                <input type="hidden" name="no_spri" id="m_no_spri">
                <input type="hidden" name="no_rm" id="m_no_rm">
                <input type="hidden" name="dpjp_bangsal" id="m_dpjp_bangsal">
                <input type="hidden" name="cara_bayar" id="m_cara_bayar">
                <input type="hidden" name="diagnosa_indikasi" id="m_diagnosa_indikasi">

                <label style="font-weight: bold; font-size: 13px;">Pilih Kamar / Bangsal yang Tersedia:</label>
                <select name="kamar_tujuan" required>
                    <option value="">-- Pilih Ruangan & Kelas --</option>
                    <?php
                    // Ambil kamar rawat inap yang sisa kasurnya > 0 (Abaikan IGD dan Kamar Jenazah)
                    $q_kamar = mysqli_query($koneksi, "SELECT * FROM kamar_rawat 
                                                       WHERE nama_ruangan NOT LIKE '%IGD%' 
                                                       AND nama_ruangan NOT LIKE '%Jenazah%' 
                                                       AND (kapasitas - terisi) > 0 
                                                       ORDER BY kelas ASC");
                    while($km = mysqli_fetch_assoc($q_kamar)) {
                        $sisa = $km['kapasitas'] - $km['terisi'];
                        echo "<option value='".$km['nama_ruangan']."'>".$km['nama_ruangan']." (".$km['kelas'].") - Sisa: ".$sisa." Bed</option>";
                    }
                    ?>
                </select>

                <button type="submit" name="proses_transfer" class="btn-simpan">🚀 Konfirmasi & Masuk Bangsal</button>
                <button type="button" onclick="tutupModal()" style="margin-top: 10px; background: none; border: none; color: #ef4444; width: 100%; cursor: pointer;">Batal</button>
            </form>
        </div>
    </div>

    <script>
        function bukaModal(spri, rm, nama, dpjp, bayar, diagnosa) {
            document.getElementById('m_no_spri').value = spri;
            document.getElementById('m_no_rm').value = rm;
            document.getElementById('m_dpjp_bangsal').value = dpjp;
            document.getElementById('m_cara_bayar').value = bayar;
            document.getElementById('m_diagnosa_indikasi').value = diagnosa;
            
            document.getElementById('modalInfoPasien').innerHTML = "<b>Pasien:</b> " + nama + " (" + rm + ")<br><b>DPJP Bangsal:</b> " + dpjp + "<br><b>No. SPRI:</b> " + spri;
            document.getElementById('modalKamar').style.display = "block";
        }

        function tutupModal() {
            document.getElementById('modalKamar').style.display = "none";
        }
    </script>
</body>
</html>