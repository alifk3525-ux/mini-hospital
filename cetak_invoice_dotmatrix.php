<?php
include 'koneksi.php';
$id = $_GET['id'] ?? '';
$q = mysqli_query($koneksi, "SELECT b.*, p.nama_lengkap, p.alamat, p.cara_bayar, k.jenis_layanan, k.tujuan_layanan 
                            FROM billing_kasir b 
                            JOIN pasien p ON b.no_rm = p.no_rm 
                            JOIN kunjungan k ON b.no_registrasi = k.no_registrasi 
                            WHERE b.id_billing='$id'");
$d = mysqli_fetch_assoc($q);

if (!$d) { echo "Invoice tidak ditemukan!"; exit; }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Invoice - <?php echo $d['no_kwitansi']; ?></title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; font-size: 13px; color: #000; padding: 20px; }
        .box-invoice { border: 2px solid #000; padding: 20px; width: 210mm; min-height: 148mm; margin: 0 auto; box-sizing: border-box; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px double #000; padding-bottom: 10px; margin-bottom: 15px; }
        .title { font-size: 18px; font-weight: bold; text-decoration: underline; text-align: center; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .table-item th, .table-item td { border: 1px solid #000; padding: 6px 10px; }
        .text-right { text-align: right; }
        .ttd-box { display: flex; justify-content: space-between; margin-top: 25px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="margin-bottom: 15px; text-align: center;">
        <button onclick="window.print()" style="padding:10px 20px; font-weight:bold; cursor:pointer;">🖨️ Cetak Invoice (Dot Matrix / A5)</button>
        <button onclick="window.close()" style="padding:10px 20px;">Tutup Jendela</button>
    </div>

    <div class="box-invoice">
        <div class="header">
            <div>
                <strong style="font-size: 16px;">RUMAH SAKIT SIMULASI MINI HOSPITAL</strong><br>
                Jl. Kesdam V Brawijaya No. 1, Malang, Jawa Timur<br>
                Telp: (0341) 561386 / Fax: (0341) 561387
            </div>
            <div style="text-align: right;">
                <strong>INVOICE / KWITANSI</strong><br>
                No: <b><?php echo $d['no_kwitansi']; ?></b><br>
                Tanggal: <?php echo date('d-m-Y H:i', strtotime($d['waktu_bayar'])); ?>
            </div>
        </div>

        <table style="border:none; margin-bottom:15px;">
            <tr>
                <td style="width:18%;">No. Rekam Medis</td><td>: <b><?php echo $d['no_rm']; ?></b></td>
                <td style="width:18%;">Layanan</td><td>: <?php echo $d['jenis_layanan']; ?> (<?php echo $d['tujuan_layanan']; ?>)</td>
            </tr>
            <tr>
                <td>Nama Pasien</td><td>: <b><?php echo strtoupper($d['nama_lengkap']); ?></b></td>
                <td>Penjamin</td><td>: <?php echo $d['cara_bayar']; ?></td>
            </tr>
            <tr>
                <td>Alamat</td><td>: <?php echo $d['alamat']; ?></td>
                <td>Kasir</td><td>: <?php echo $d['petugas_kasir']; ?></td>
            </tr>
        </table>

        <table class="table-item">
            <thead>
                <tr style="background:#f0f0f0;">
                    <th>No</th>
                    <th>Rincian Komponen Biaya Pelayanan</th>
                    <th class="text-right">Nominal (Rp)</th>
                </tr>
            </thead>
            <tbody>
                <tr><td style="text-align:center;">1</td><td>Biaya Administrasi & Karcis Pendaftaran</td><td class="text-right"><?php echo number_format($d['biaya_admin'], 0, ',', '.'); ?></td></tr>
                <tr><td style="text-align:center;">2</td><td>Jasa Konsultasi / Visite Dokter</td><td class="text-right"><?php echo number_format($d['biaya_dokter'], 0, ',', '.'); ?></td></tr>
                <tr><td style="text-align:center;">3</td><td>Biaya Akomodasi Kamar Rawat Inap</td><td class="text-right"><?php echo number_format($d['biaya_kamar'], 0, ',', '.'); ?></td></tr>
                <tr><td style="text-align:center;">4</td><td>Obat-obatan & Alkes Instalasi Farmasi</td><td class="text-right"><?php echo number_format($d['biaya_obat'], 0, ',', '.'); ?></td></tr>
                <tr><td style="text-align:center;">5</td><td>Tindakan Medis & Keperawatan</td><td class="text-right"><?php echo number_format($d['biaya_tindakan'], 0, ',', '.'); ?></td></tr>
            </tbody>
            <tfoot>
                <tr style="font-weight:bold;">
                    <td colspan="2" class="text-right">GRAND TOTAL TAGIHAN:</td>
                    <td class="text-right">Rp <?php echo number_format($d['total_tagihan'], 0, ',', '.'); ?></td>
                </tr>
                <tr>
                    <td colspan="2" class="text-right">Jumlah Bayar (<?php echo $d['metode_bayar']; ?>):</td>
                    <td class="text-right">Rp <?php echo number_format($d['jumlah_bayar'], 0, ',', '.'); ?></td>
                </tr>
                <tr>
                    <td colspan="2" class="text-right">Kembalian:</td>
                    <td class="text-right">Rp <?php echo number_format($d['kembalian'], 0, ',', '.'); ?></td>
                </tr>
            </tfoot>
        </table>

        <?php if(!empty($d['catatan'])): ?>
            <p style="font-size:11px;"><b>Catatan:</b> <?php echo $d['catatan']; ?></p>
        <?php endif; ?>

        <div class="ttd-box">
            <div style="text-align:center; width:200px;">
                Pasien / Penanggung Jawab,<br><br><br><br>
                ( .................................... )
            </div>
            <div style="text-align:center; width:200px;">
                Malang, <?php echo date('d-m-Y'); ?><br>
                Petugas Kasir / Keuangan,<br><br><br><br>
                <b>( <?php echo $d['petugas_kasir']; ?> )</b>
            </div>
        </div>
    </div>

</body>
</html>