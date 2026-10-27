<?php
include 'koneksi.php';
$id = $_GET['id'] ?? '';
$q = mysqli_query($koneksi, "SELECT b.*, p.nama_lengkap, k.jenis_layanan 
                            FROM billing_kasir b 
                            JOIN pasien p ON b.no_rm = p.no_rm 
                            JOIN kunjungan k ON b.no_registrasi = k.no_registrasi 
                            WHERE b.id_billing='$id'");
$d = mysqli_fetch_assoc($q);

if (!$d) { echo "Nota tidak ditemukan!"; exit; }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Struk Nota - <?php echo $d['no_kwitansi']; ?></title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; width: 58mm; margin: 0 auto; padding: 5px; font-size: 11px; color: #000; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .line { border-top: 1px dashed #000; margin: 6px 0; }
        table { width: 100%; font-size: 11px; border-collapse: collapse; }
        td { vertical-align: top; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="margin-bottom: 10px;">
        <button onclick="window.print()">🖨️ Cetak Thermal</button>
        <button onclick="window.close()">Tutup</button>
    </div>

    <div class="text-center">
        <strong>MINI HOSPITAL</strong><br>
        <small>Jl. Kesdam V Brawijaya, Malang</small><br>
        <small>Telp: (0341) 561386</small>
    </div>

    <div class="line"></div>
    <strong>NOTA PEMBAYARAN</strong><br>
    No: <?php echo $d['no_kwitansi']; ?><br>
    Tgl: <?php echo date('d/m/Y H:i', strtotime($d['waktu_bayar'])); ?><br>
    Pasien: <?php echo strtoupper($d['nama_lengkap']); ?><br>
    RM/Reg: <?php echo $d['no_rm']; ?> / <?php echo $d['no_registrasi']; ?><br>
    Kasir: <?php echo $d['petugas_kasir']; ?>
    <div class="line"></div>

    <table>
        <tr><td>Adm/Karcis</td><td class="text-right"><?php echo number_format($d['biaya_admin']); ?></td></tr>
        <tr><td>Jasa Dokter</td><td class="text-right"><?php echo number_format($d['biaya_dokter']); ?></td></tr>
        <?php if($d['biaya_kamar']>0): ?>
        <tr><td>Kamar Ranap</td><td class="text-right"><?php echo number_format($d['biaya_kamar']); ?></td></tr>
        <?php endif; ?>
        <tr><td>Obat Farmasi</td><td class="text-right"><?php echo number_format($d['biaya_obat']); ?></td></tr>
        <?php if($d['biaya_tindakan']>0): ?>
        <tr><td>Tindakan Medis</td><td class="text-right"><?php echo number_format($d['biaya_tindakan']); ?></td></tr>
        <?php endif; ?>
    </table>

    <div class="line"></div>
    <table>
        <tr><strong><td>TOTAL</td><td class="text-right">Rp <?php echo number_format($d['total_tagihan']); ?></td></strong></tr>
        <tr><td>Bayar (<?php echo $d['metode_bayar']; ?>)</td><td class="text-right"><?php echo number_format($d['jumlah_bayar']); ?></td></tr>
        <tr><td>Kembali</td><td class="text-right"><?php echo number_format($d['kembalian']); ?></td></tr>
    </table>

    <div class="line"></div>
    <div class="text-center">
        <strong>[ LUNAS ]</strong><br>
        <small>Terima kasih atas kunjungan Anda.<br>Semoga lekas sembuh!</small>
    </div>

</body>
</html>