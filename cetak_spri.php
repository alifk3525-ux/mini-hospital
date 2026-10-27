<?php
include 'koneksi.php';
$spri = isset($_GET['spri']) ? $_GET['spri'] : '';

$q = mysqli_query($koneksi, "SELECT s.*, p.nama_lengkap, p.jk, p.tgl_lahir, p.alamat, p.cara_bayar 
                            FROM spri s 
                            JOIN pasien p ON s.no_rm = p.no_rm 
                            WHERE s.no_spri = '$spri'");
$d = mysqli_fetch_assoc($q);

if (!$d) {
    echo "Data SPRI tidak ditemukan!";
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Surat Perintah Rawat Inap (SPRI) - <?php echo $d['no_spri']; ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 30px; font-size: 14px; line-height: 1.6; }
        .kop { text-align: center; border-bottom: 2px solid black; padding-bottom: 10px; margin-bottom: 20px; }
        .kop h2, .kop h3, .kop p { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        td { padding: 6px; vertical-align: top; }
        .ttd-box { margin-top: 40px; display: flex; justify-content: space-between; }
        .ttd { text-align: center; width: 250px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="margin-bottom: 15px;">
        <button onclick="window.print()">🖨️ Cetak SPRI</button>
        <button onclick="window.close()">Tutup Jendela</button>
    </div>

    <div class="kop">
        <h2>MINI HOSPITAL SIMULASI</h2>
        <h3>INSTITUT TEKNOLOGI SAINS DAN KESEHATAN RS dr. SOEPRAOEN</h3>
        <p>Jl. S. Supriadi No. 22, Malang - Jawa Timur</p>
    </div>

    <h3 style="text-align: center; text-decoration: underline; margin-bottom: 25px;">SURAT PERINTAH RAWAT INAP (SPRI)</h3>

    <p>Mohon untuk didaftarkan dan dirawat inap pasien dengan identitas berikut:</p>

    <table>
        <tr><td style="width: 25%;"><strong>Nomor SPRI</strong></td><td>: <?php echo $d['no_spri']; ?></td></tr>
        <tr><td><strong>Nomor Rekam Medis</strong></td><td>: <b><?php echo $d['no_rm']; ?></b></td></tr>
        <tr><td><strong>Nama Lengkap Pasien</strong></td><td>: <?php echo $d['nama_lengkap']; ?></td></tr>
        <tr><td><strong>Jenis Kelamin / Umur</strong></td><td>: <?php echo $d['jk']; ?> / <?php echo date('Y') - date('Y', strtotime($d['tgl_lahir'])); ?> Tahun</td></tr>
        <tr><td><strong>Alamat</strong></td><td>: <?php echo $d['alamat']; ?></td></tr>
        <tr><td><strong>Jaminan / Cara Bayar</strong></td><td>: <?php echo $d['cara_bayar']; ?></td></tr>
        <tr><td><strong>Unit Asal Perujuk</strong></td><td>: <?php echo $d['unit_asal']; ?> (Dokter: <?php echo $d['dokter_perujuk']; ?>)</td></tr>
        <tr><td><strong>Dokter DPJP Bangsal</strong></td><td>: <b><?php echo $d['dpjp_bangsal']; ?></b></td></tr>
        <tr><td><strong>Diagnosa / Indikasi Rawat</strong></td><td>: <b><?php echo $d['diagnosa_indikasi']; ?></b></td></tr>
        <tr><td><strong>Instruksi / Catatan Khusus</strong></td><td>: <?php echo $d['catatan_khusus'] ? $d['catatan_khusus'] : '-'; ?></td></tr>
        <tr><td><strong>Rencana Ruangan</strong></td><td>: <?php echo $d['rencana_kamar'] ? $d['rencana_kamar'] : '<i>Menunggu Penetapan Admisi</i>'; ?></td></tr>
    </table>

    <div class="ttd-box">
        <div class="ttd">
            <p>Petugas Admisi / TPP,</p>
            <br><br><br>
            <p>( ........................................ )</p>
        </div>
        <div class="ttd">
            <p>Malang, <?php echo date('d-m-Y'); ?><br>Dokter Pemeriksa,</p>
            <br><br><br>
            <p><b><?php echo $d['dokter_perujuk']; ?></b></p>
        </div>
    </div>
</body>
</html>