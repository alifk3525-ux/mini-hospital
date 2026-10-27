<?php
session_start();
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Editor Invoice Dot Matrix</title>
<style>
:root {
  --pw: 241mm;
  --ph: 139mm;
  --bg-app: #1e293b;
  --panel-bg: #ffffff;
  --border-color: #cbd5e1;
  --primary: #2563eb;
  --primary-hover: #1d4ed8;
  --danger: #dc2626;
  --font-inv: 'Courier New', Courier, monospace, sans-serif;
}

* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; background-color: var(--bg-app); font-family: system-ui, -apple-system, sans-serif; color: #334155; }

.app-header {
  background: #0f172a;
  color: #fff;
  padding: 12px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #334155;
}
.app-header h1 { font-size: 18px; margin: 0; font-weight: 700; }
.btn-group { display: flex; gap: 8px; }

.btn {
  background: #334155;
  color: #fff;
  border: none;
  padding: 8px 14px;
  border-radius: 6px;
  font-weight: 600;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s;
}
.btn:hover { background: #475569; }
.btn.primary { background: var(--primary); }
.btn.primary:hover { background: var(--primary-hover); }

.workspace {
  display: grid;
  grid-template-columns: 420px 1fr;
  height: calc(100vh - 57px);
  overflow: hidden;
}

.editor-panel {
  background: var(--panel-bg);
  border-right: 1px solid var(--border-color);
  overflow-y: auto;
  padding: 16px;
}

.accordion-item {
  border: 1px solid var(--border-color);
  border-radius: 8px;
  margin-bottom: 12px;
  overflow: hidden;
  background: #fff;
}
.accordion-item summary {
  padding: 12px 16px;
  font-weight: 700;
  font-size: 13px;
  background: #f8fafc;
  cursor: pointer;
  user-select: none;
  border-bottom: 1px solid transparent;
}
.accordion-item[open] summary { border-bottom-color: var(--border-color); }
.accordion-content { padding: 12px; }

.form-group { margin-bottom: 10px; }
.form-group label { display: block; font-size: 11px; font-weight: 600; margin-bottom: 4px; color: #475569; }
.form-control {
  width: 100%;
  padding: 6px 8px;
  border: 1px solid #cbd5e1;
  border-radius: 5px;
  font-size: 12px;
}
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px; }

.item-row-edit {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  padding: 8px;
  margin-bottom: 8px;
  position: relative;
}
.item-row-edit .btn-del {
  position: absolute; right: 6px; top: 6px;
  color: var(--danger); background: none; border: none; font-size: 16px; cursor: pointer;
}

.stage-panel {
  background: #334155;
  overflow: auto;
  padding: 30px;
  display: flex;
  justify-content: center;
  align-items: flex-start;
}

.paper {
  width: var(--pw);
  min-height: var(--ph);
  background: #ffffff;
  color: #000000;
  font-family: var(--font-inv);
  font-size: 12px;
  line-height: 1.3;
  padding: 10mm 12mm;
  box-shadow: 0 10px 25px rgba(0,0,0,0.3);
  position: relative;
  box-sizing: border-box;
  overflow: hidden;
}

.watermark-stamp {
  position: absolute;
  top: 40%;
  left: 50%;
  transform: translate(-50%, -50%) rotate(-15deg);
  font-size: 48px;
  font-weight: 900;
  border: 4px double;
  padding: 5px 25px;
  border-radius: 8px;
  opacity: 0.18;
  pointer-events: none;
  text-transform: uppercase;
  letter-spacing: 4px;
}
.stamp-lunas { color: #16a34a; border-color: #16a34a; }
.stamp-pending { color: #dc2626; border-color: #dc2626; }
.stamp-dp { color: #d97706; border-color: #d97706; }
.stamp-draft { color: #475569; border-color: #475569; }

.dm-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 10px;
}

.store-name { font-weight: 800; font-size: 14px; text-transform: uppercase; }
.store-sub { font-size: 10.5px; }

.doc-title-main {
  font-size: 15px;
  font-weight: 900;
  text-transform: uppercase;
  text-align: right;
}

.dm-info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-bottom: 10px;
  font-size: 11px;
}

.info-table { border-collapse: collapse; width: 100%; }
.info-table td { padding: 1px 0; vertical-align: top; }
.info-table td.lbl { width: 90px; font-weight: 600; }
.info-table td.sep { width: 10px; text-align: center; }

table.dm-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 8px;
}
table.dm-table th, table.dm-table td {
  border: 1px solid #000000;
  padding: 3px 5px;
  font-size: 10.5px;
}
table.dm-table th { text-align: center; font-weight: 700; }
table.dm-table td.center { text-align: center; }
table.dm-table td.right { text-align: right; }

.dm-summary {
  display: grid;
  grid-template-columns: 1.2fr 1fr;
  gap: 12px;
  font-size: 10.5px;
}

.terbilang-container {
  border-top: 1px dashed #000;
  padding-top: 4px;
}

.totals-table { width: 100%; border-collapse: collapse; }
.totals-table td { padding: 1px 0; text-align: right; }
.totals-table td.lbl { text-align: left; }
.totals-table tr.grand-total td {
  font-weight: 800;
  font-size: 11.5px;
  border-top: 1px solid #000;
  padding-top: 3px;
}

.bank-info-box {
  margin-top: 6px;
  padding-top: 4px;
  border-top: 1px dotted #888;
  font-size: 10px;
}

.dm-signatures {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-top: 15px;
}
.sig-box { text-align: center; font-size: 10.5px; }
.sig-box .sig-title { font-weight: 600; margin-bottom: 35px; }
.sig-box .sig-line { display: inline-block; min-width: 140px; border-top: 1px solid #000; padding-top: 2px; font-weight: 700; }

.dm-footer-note {
  margin-top: 12px;
  text-align: center;
  font-size: 10px;
  font-style: italic;
  border-top: 1px dotted #888;
  padding-top: 4px;
}

@media print {
  html, body { background: #fff !important; color: #000 !important; }
  .no-print, .app-header, .editor-panel { display: none !important; }
  .workspace { display: block; height: auto; }
  .stage-panel { padding: 0; background: none; overflow: visible; display: block; }
  .paper {
    box-shadow: none !important;
    margin: 0 !important;
    width: var(--pw) !important;
    min-height: var(--ph) !important;
    padding: 5mm 6mm !important;
  }
  @page { size: var(--pw) var(--ph); margin: 0; }
}
</style>
</head>
<body>

<div class="app-header no-print">
  <h1>🖨️ Editor Nota / Invoice Studio</h1>
  <div class="btn-group">
    <button class="btn" id="btnReset">🔄 Reset Default</button>
    <button class="btn primary" id="btnPrint">🖨️ Cetak Nota</button>
  </div>
</div>

<div class="workspace">
  <!-- PANEL CONTROL EDITOR -->
  <aside class="editor-panel no-print">
    
    <!-- EDIT JUDUL & STEMPEL -->
    <details class="accordion-item" open>
      <summary>🏷️ Judul & Stempel Status Nota</summary>
      <div class="accordion-content">
        <div class="form-group">
          <label>Judul Dokumen</label>
          <input type="text" id="docTitle" class="form-control" value="INVOICE / NOTA PENJUALAN">
        </div>
        <div class="grid-2">
          <div class="form-group">
            <label><input type="checkbox" id="showStamp" checked> Tampilkan Stempel</label>
          </div>
          <div class="form-group">
            <label>Teks Stempel Status</label>
            <select id="stampText" class="form-control">
              <option value="LUNAS">LUNAS</option>
              <option value="PENDING">PENDING</option>
              <option value="DP / PANJAR">DP / PANJAR</option>
              <option value="DRAFT">DRAFT</option>
            </select>
          </div>
        </div>
      </div>
    </details>

    <!-- UKURAN KERTAS & TAMPILAN TABEL -->
    <details class="accordion-item" open>
      <summary>📄 Ukuran Kertas & Opsi Tabel</summary>
      <div class="accordion-content">
        <div class="form-group">
          <label>Ukuran Kertas Continuous Form</label>
          <select id="paperSize" class="form-control">
            <option value="half">Half Wartel (241 x 139 mm)</option>
            <option value="full">Continuous Full (241 x 279 mm)</option>
            <option value="a4">A4 (210 x 297 mm)</option>
          </select>
        </div>
        <div class="form-group">
          <label><input type="checkbox" id="showUnitCol" checked> Tampilkan Kolom Satuan (Unit)</label>
        </div>
        <div class="form-group">
          <label>Label Kolom Barang</label>
          <input type="text" id="colItemLabel" class="form-control" value="Nama Barang & Deskripsi">
        </div>
      </div>
    </details>

    <!-- DATA TOKO / PENGIRIM -->
    <details class="accordion-item" open>
      <summary>🏢 Profil Toko / Pengirim</summary>
      <div class="accordion-content">
        <div class="form-group">
          <label>Nama Toko / Pengirim</label>
          <input type="text" id="sellerName" class="form-control" value="Whindies PRINT">
        </div>
        <div class="form-group">
          <label>Alamat Pengirim</label>
          <input type="text" id="sellerAddress" class="form-control" value="JL. SIMP. KEPUH 57 SUKUN, MALANG">
        </div>
        <div class="form-group">
          <label>Telepon / Kontak</label>
          <input type="text" id="sellerPhone" class="form-control" value="TELP. (0341) 800524">
        </div>
      </div>
    </details>

    <!-- DETAIL INVOICE & PELANGGAN -->
    <details class="accordion-item" open>
      <summary>👤 Detail Transaksi & Pelanggan</summary>
      <div class="accordion-content">
        <div class="grid-2">
          <div class="form-group">
            <label>No. Invoice</label>
            <input type="text" id="invNo" class="form-control" value="INV/2026/001">
          </div>
          <div class="form-group">
            <label>Tanggal</label>
            <input type="text" id="invDate" class="form-control" value="3 Oktober 2026">
          </div>
        </div>
        <div class="grid-2">
          <div class="form-group">
            <label>Metode / Term</label>
            <input type="text" id="invTerm" class="form-control" value="Tunai">
          </div>
          <div class="form-group">
            <label>Jatuh Tempo (Opsional)</label>
            <input type="text" id="invDueDate" class="form-control" value="-">
          </div>
        </div>
        <hr style="border:none; border-top:1px dashed #cbd5e1; margin:8px 0;">
        <div class="form-group">
          <label>Nama Customer</label>
          <input type="text" id="custName" class="form-control" value="PRODI D3 RMIK SOEPRAOEN">
        </div>
        <div class="form-group">
          <label>Alamat Customer</label>
          <input type="text" id="custAddress" class="form-control" value="Sukun, Malang">
        </div>
      </div>
    </details>

    <!-- RINCIAN BARANG -->
    <details class="accordion-item" open>
      <summary>📦 Rincian Item Barang / Jasa</summary>
      <div class="accordion-content">
        <div id="itemListEditor"></div>
        <button class="btn primary" id="btnAddItem" style="width:100%; margin-top:5px; font-size:12px;">+ Tambah Barang</button>
      </div>
    </details>

    <!-- OPSIONAL TANDA TANGAN -->
    <details class="accordion-item" open>
      <summary>✍️ Opsi Tanda Tangan</summary>
      <div class="accordion-content">
        <div class="form-group">
          <label><input type="checkbox" id="showReceiverSign" checked> Tampilkan TTD Pelanggan (Penerima)</label>
        </div>
        <div class="form-group">
          <label>Judul TTD Pelanggan</label>
          <input type="text" id="sigReceiverTitle" class="form-control" value="Penerima / Customer">
        </div>
        <hr style="border:none; border-top:1px dashed #cbd5e1; margin:8px 0;">
        <div class="form-group">
          <label><input type="checkbox" id="showSenderSign" checked> Tampilkan TTD Owner / Pengirim</label>
        </div>
        <div class="form-group">
          <label>Judul TTD Owner</label>
          <input type="text" id="sigSenderTitle" class="form-control" value="Hormat Kami">
        </div>
        <div class="form-group">
          <label>Nama Penandatangan / Owner</label>
          <input type="text" id="sigSenderName" class="form-control" value="Kasir / Keuangan">
        </div>
      </div>
    </details>

    <!-- PAJAK, REKENING & CATATAN -->
    <details class="accordion-item" open>
      <summary>💰 Pajak (PPN), Bank & Catatan</summary>
      <div class="accordion-content">
        <div class="grid-2">
          <div class="form-group">
            <label>Diskon Total (Rp)</label>
            <input type="number" id="discTotal" class="form-control" value="0">
          </div>
          <div class="form-group">
            <label>PPN (%)</label>
            <input type="number" id="taxPercent" class="form-control" value="0" placeholder="0">
          </div>
        </div>
        <hr style="border:none; border-top:1px dashed #cbd5e1; margin:8px 0;">
        <div class="form-group">
          <label><input type="checkbox" id="showBank" checked> Tampilkan Info Rekening Bank</label>
        </div>
        <div class="form-group">
          <label>Info Bank & No. Rekening</label>
          <input type="text" id="bankDetails" class="form-control" value="BCA: 123-456-7890 a.n Whindies Print">
        </div>
        <div class="form-group">
          <label>Catatan Syarat & Ketentuan</label>
          <textarea id="bottomNote" class="form-control" rows="2">Barang yang sudah dibeli tidak dapat ditukar/dikembalikan.</textarea>
        </div>
      </div>
    </details>

  </aside>

  <!-- PREVIEW PREVIEW NOTA SIAP CETAK -->
  <main class="stage-panel">
    <article class="paper" id="paper">
      
      <!-- WATERMARK STEMPEL -->
      <div id="viewStamp" class="watermark-stamp stamp-lunas" style="display:block;">LUNAS</div>

      <!-- HEADER -->
      <header class="dm-header">
        <div>
          <div class="store-name" id="viewSellerName">Whindies PRINT</div>
          <div class="store-sub" id="viewSellerAddress">JL. SIMP. KEPUH 57 SUKUN, MALANG</div>
          <div class="store-sub" id="viewSellerPhone">TELP. (0341) 800524</div>
        </div>
        <div class="doc-title-main" id="viewDocTitle">INVOICE / NOTA PENJUALAN</div>
      </header>

      <!-- INFO METADATA -->
      <section class="dm-info-grid">
        <div>
          <table class="info-table">
            <tr><td class="lbl">No. Invoice</td><td class="sep">:</td><td id="viewInvNo">-</td></tr>
            <tr><td class="lbl">Tanggal</td><td class="sep">:</td><td id="viewInvDate">-</td></tr>
            <tr><td class="lbl">Term / Cara Bayar</td><td class="sep">:</td><td id="viewInvTerm">Tunai</td></tr>
            <tr id="rowDueDate"><td class="lbl">Jatuh Tempo</td><td class="sep">:</td><td id="viewInvDueDate">-</td></tr>
          </table>
        </div>
        <div>
          <table class="info-table">
            <tr><td class="lbl">Kepada Yth.</td><td class="sep">:</td><td id="viewCustName">-</td></tr>
            <tr><td class="lbl">Alamat</td><td class="sep">:</td><td id="viewCustAddress">-</td></tr>
          </table>
        </div>
      </section>

      <!-- TABEL BARANG -->
      <table class="dm-table">
        <thead id="viewTableHeader"></thead>
        <tbody id="viewTableBody"></tbody>
      </table>

      <!-- SUMMARY -->
      <section class="dm-summary">
        <div>
          <div class="terbilang-container">
            Terbilang : <strong id="viewTerbilang">-</strong>
          </div>
          <div class="bank-info-box" id="viewBankBox">
            Pembayaran Transfer: <span id="viewBankDetails">-</span>
          </div>
        </div>
        <div>
          <table class="totals-table">
            <tr><td class="lbl">Subtotal</td><td id="viewSubtotal">Rp 0</td></tr>
            <tr id="rowDiscount"><td class="lbl">Diskon</td><td id="viewDiscount">Rp 0</td></tr>
            <tr id="rowTax"><td class="lbl" id="viewTaxLabel">PPN 11%</td><td id="viewTaxVal">Rp 0</td></tr>
            <tr class="grand-total"><td class="lbl">Total Akhir</td><td id="viewGrandTotal">Rp 0</td></tr>
          </table>
        </div>
      </section>

      <!-- TANDA TANGAN (Bisa opsional per pihak) -->
      <section class="dm-signatures" id="viewSignatures">
        <div class="sig-box" id="boxSigReceiver">
          <div class="sig-title" id="viewSigReceiverTitle">Penerima / Customer</div>
          <div class="sig-line">( .................... )</div>
        </div>
        <div class="sig-box" id="boxSigSender">
          <div class="sig-title" id="viewSigSenderTitle">Hormat Kami</div>
          <div class="sig-line" id="viewSigSenderName">Kasir / Keuangan</div>
        </div>
      </section>

      <!-- FOOTER NOTE -->
      <footer class="dm-footer-note" id="viewBottomNote">
        Barang yang sudah dibeli tidak dapat ditukar/dikembalikan.
      </footer>

    </article>
  </main>
</div>

<script>
(() => {
  'use strict';

  const defaultState = {
    docTitle: 'INVOICE / NOTA PENJUALAN',
    showStamp: true,
    stampText: 'LUNAS',
    paper: 'half',
    showUnitCol: true,
    colItemLabel: 'Nama Barang & Deskripsi',
    seller: {
      name: 'Whindies PRINT',
      address: 'JL. SIMP. KEPUH 57 SUKUN, MALANG',
      phone: 'TELP. (0341) 800524'
    },
    invoice: {
      no: 'INV/2026/001',
      date: '3 Oktober 2026',
      term: 'Tunai',
      dueDate: '-'
    },
    customer: {
      name: 'PRODI D3 RMIK SOEPRAOEN',
      address: 'Sukun, Malang'
    },
    items: [
      { name: 'Cetak Formulir Rekam Medis HVS 70gr', qty: 500, unit: 'Pcs', price: 300 },
      { name: 'Map Rekam Medis Pasien AP260gr', qty: 100, unit: 'Pcs', price: 8000 }
    ],
    signature: {
      showReceiver: true,
      receiverTitle: 'Penerima / Customer',
      showSender: true,
      senderTitle: 'Hormat Kami',
      senderName: 'Whindies PRINT'
    },
    discount: 0,
    taxPercent: 0,
    showBank: true,
    bankDetails: 'BCA: 123-456-7890 a.n Whindies Print',
    bottomNote: 'Barang yang sudah dibeli tidak dapat ditukar/dikembalikan.'
  };

  let state = JSON.parse(localStorage.getItem('dm_invoice_custom_v2')) || defaultState;

  const $ = selector => document.querySelector(selector);

  function fmtRp(num) {
    return 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(num || 0);
  }

  function terbilangRupiah(n) {
    n = Math.floor(Math.abs(n));
    if (n === 0) return 'nol rupiah';
    const s = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
    const f = (x) => {
      if (x < 12) return s[x];
      if (x < 20) return f(x - 10) + ' belas';
      if (x < 100) return f(Math.floor(x / 10)) + ' puluh' + (x % 10 ? ' ' + f(x % 10) : '');
      if (x < 200) return 'seratus' + (x % 100 ? ' ' + f(x % 100) : '');
      if (x < 1000) return f(Math.floor(x / 100)) + ' ratus' + (x % 100 ? ' ' + f(x % 100) : '');
      if (x < 1e6) return f(Math.floor(x / 1000)) + ' ribu' + (x % 1000 ? ' ' + f(x % 1000) : '');
      if (x < 1e9) return f(Math.floor(x / 1e6)) + ' juta' + (x % 1e6 ? ' ' + f(x % 1e6) : '');
      return String(x);
    };
    return f(n).trim() + ' rupiah';
  }

  function updatePaperSize() {
    const root = document.documentElement.style;
    if (state.paper === 'half') { root.setProperty('--pw', '241mm'); root.setProperty('--ph', '139mm'); }
    else if (state.paper === 'full') { root.setProperty('--pw', '241mm'); root.setProperty('--ph', '279mm'); }
    else if (state.paper === 'a4') { root.setProperty('--pw', '210mm'); root.setProperty('--ph', '297mm'); }
  }

  function syncFormControls() {
    $('#docTitle').value = state.docTitle || '';
    $('#showStamp').checked = state.showStamp;
    $('#stampText').value = state.stampText || 'LUNAS';

    $('#paperSize').value = state.paper;
    $('#showUnitCol').checked = state.showUnitCol;
    $('#colItemLabel').value = state.colItemLabel || 'Nama Barang & Deskripsi';

    $('#sellerName').value = state.seller.name;
    $('#sellerAddress').value = state.seller.address;
    $('#sellerPhone').value = state.seller.phone;

    $('#invNo').value = state.invoice.no;
    $('#invDate').value = state.invoice.date;
    $('#invTerm').value = state.invoice.term;
    $('#invDueDate').value = state.invoice.dueDate;

    $('#custName').value = state.customer.name;
    $('#custAddress').value = state.customer.address;

    // TTD Opsional
    if (!state.signature) state.signature = defaultState.signature;
    $('#showReceiverSign').checked = state.signature.showReceiver !== false;
    $('#sigReceiverTitle').value = state.signature.receiverTitle || 'Penerima / Customer';
    $('#showSenderSign').checked = state.signature.showSender !== false;
    $('#sigSenderTitle').value = state.signature.senderTitle || 'Hormat Kami';
    $('#sigSenderName').value = state.signature.senderName || state.seller.name;

    $('#discTotal').value = state.discount;
    $('#taxPercent').value = state.taxPercent;

    $('#showBank').checked = state.showBank;
    $('#bankDetails').value = state.bankDetails;
    $('#bottomNote').value = state.bottomNote;

    renderItemListEditor();
  }

  function renderItemListEditor() {
    const container = $('#itemListEditor');
    container.innerHTML = state.items.map((item, idx) => `
      <div class="item-row-edit" data-idx="${idx}">
        <button type="button" class="btn-del" title="Hapus Item">&times;</button>
        <div class="form-group" style="margin-bottom:4px;">
          <label>Item ${idx + 1}</label>
          <input type="text" class="form-control item-name" value="${item.name}">
        </div>
        <div class="grid-3">
          <div class="form-group" style="margin:0;">
            <label>Qty</label>
            <input type="number" class="form-control item-qty" value="${item.qty}" min="1">
          </div>
          <div class="form-group" style="margin:0;">
            <label>Satuan</label>
            <input type="text" class="form-control item-unit" value="${item.unit || 'Pcs'}">
          </div>
          <div class="form-group" style="margin:0;">
            <label>Harga (Rp)</label>
            <input type="number" class="form-control item-price" value="${item.price}" min="0">
          </div>
        </div>
      </div>
    `).join('');
  }

  function renderPreview() {
    updatePaperSize();

    // Render Stempel
    const stampEl = $('#viewStamp');
    if (state.showStamp) {
      stampEl.style.display = 'block';
      stampEl.textContent = state.stampText;
      stampEl.className = 'watermark-stamp ';
      if (state.stampText === 'LUNAS') stampEl.classList.add('stamp-lunas');
      else if (state.stampText === 'PENDING') stampEl.classList.add('stamp-pending');
      else if (state.stampText === 'DP / PANJAR') stampEl.classList.add('stamp-dp');
      else stampEl.classList.add('stamp-draft');
    } else {
      stampEl.style.display = 'none';
    }

    $('#viewDocTitle').textContent = state.docTitle;
    $('#viewSellerName').textContent = state.seller.name;
    $('#viewSellerAddress').textContent = state.seller.address;
    $('#viewSellerPhone').textContent = state.seller.phone;

    $('#viewInvNo').textContent = state.invoice.no;
    $('#viewInvDate').textContent = state.invoice.date;
    $('#viewInvTerm').textContent = state.invoice.term;

    if (state.invoice.dueDate && state.invoice.dueDate !== '-') {
      $('#rowDueDate').style.display = 'table-row';
      $('#viewInvDueDate').textContent = state.invoice.dueDate;
    } else {
      $('#rowDueDate').style.display = 'none';
    }

    $('#viewCustName').textContent = state.customer.name;
    $('#viewCustAddress').textContent = state.customer.address;

    // Header Tabel
    const showUnit = state.showUnitCol;
    $('#viewTableHeader').innerHTML = `
      <tr>
        <th style="width: 5%;">No</th>
        <th style="width: ${showUnit ? '40%' : '50%'};">${state.colItemLabel || 'Nama Barang'}</th>
        <th style="width: 10%;">Qty</th>
        ${showUnit ? '<th style="width: 10%;">Satuan</th>' : ''}
        <th style="width: 17%;">Harga</th>
        <th style="width: 18%;">Jumlah</th>
      </tr>
    `;

    // Body Tabel
    let subtotal = 0;
    const rowsHtml = state.items.map((it, idx) => {
      const lineTotal = (it.qty || 0) * (it.price || 0);
      subtotal += lineTotal;
      return `
        <tr>
          <td class="center">${idx + 1}</td>
          <td>${it.name || ''}</td>
          <td class="center">${it.qty || 0}</td>
          ${showUnit ? `<td class="center">${it.unit || 'Pcs'}</td>` : ''}
          <td class="right">${fmtRp(it.price)}</td>
          <td class="right">${fmtRp(lineTotal)}</td>
        </tr>
      `;
    }).join('');

    $('#viewTableBody').innerHTML = rowsHtml || '<tr><td colspan="6" style="text-align:center;">Belum ada item</td></tr>';

    // Summary Calculations
    const discount = Math.max(0, state.discount || 0);
    const afterDisc = Math.max(0, subtotal - discount);
    
    const taxPercent = Math.max(0, state.taxPercent || 0);
    const taxValue = (afterDisc * taxPercent) / 100;
    const grandTotal = afterDisc + taxValue;

    $('#viewSubtotal').textContent = fmtRp(subtotal);

    if (discount > 0) {
      $('#rowDiscount').style.display = 'table-row';
      $('#viewDiscount').textContent = fmtRp(discount);
    } else {
      $('#rowDiscount').style.display = 'none';
    }

    if (taxPercent > 0) {
      $('#rowTax').style.display = 'table-row';
      $('#viewTaxLabel').textContent = `PPN (${taxPercent}%)`;
      $('#viewTaxVal').textContent = fmtRp(taxValue);
    } else {
      $('#rowTax').style.display = 'none';
    }

    $('#viewGrandTotal').textContent = fmtRp(grandTotal);
    $('#viewTerbilang').textContent = terbilangRupiah(grandTotal);

    // Bank Info
    if (state.showBank) {
      $('#viewBankBox').style.display = 'block';
      $('#viewBankDetails').textContent = state.bankDetails;
    } else {
      $('#viewBankBox').style.display = 'none';
    }

    // Render TTD Opsional
    const sigSection = $('#viewSignatures');
    const boxReceiver = $('#boxSigReceiver');
    const boxSender = $('#boxSigSender');

    const hasReceiver = state.signature.showReceiver !== false;
    const hasSender = state.signature.showSender !== false;

    if (!hasReceiver && !hasSender) {
      sigSection.style.display = 'none';
    } else {
      sigSection.style.display = 'grid';

      if (hasReceiver) {
        boxReceiver.style.display = 'block';
        $('#viewSigReceiverTitle').textContent = state.signature.receiverTitle || 'Penerima / Customer';
      } else {
        boxReceiver.style.display = 'none';
      }

      if (hasSender) {
        boxSender.style.display = 'block';
        $('#viewSigSenderTitle').textContent = state.signature.senderTitle || 'Hormat Kami';
        $('#viewSigSenderName').textContent = state.signature.senderName || state.seller.name;
      } else {
        boxSender.style.display = 'none';
      }
    }

    $('#viewBottomNote').textContent = state.bottomNote;

    localStorage.setItem('dm_invoice_custom_v2', JSON.stringify(state));
  }

  // Event Listeners
  $('#docTitle').addEventListener('input', e => { state.docTitle = e.target.value; renderPreview(); });
  $('#showStamp').addEventListener('change', e => { state.showStamp = e.target.checked; renderPreview(); });
  $('#stampText').addEventListener('change', e => { state.stampText = e.target.value; renderPreview(); });

  $('#paperSize').addEventListener('change', e => { state.paper = e.target.value; renderPreview(); });
  $('#showUnitCol').addEventListener('change', e => { state.showUnitCol = e.target.checked; renderPreview(); });
  $('#colItemLabel').addEventListener('input', e => { state.colItemLabel = e.target.value; renderPreview(); });

  $('#sellerName').addEventListener('input', e => { state.seller.name = e.target.value; renderPreview(); });
  $('#sellerAddress').addEventListener('input', e => { state.seller.address = e.target.value; renderPreview(); });
  $('#sellerPhone').addEventListener('input', e => { state.seller.phone = e.target.value; renderPreview(); });

  $('#invNo').addEventListener('input', e => { state.invoice.no = e.target.value; renderPreview(); });
  $('#invDate').addEventListener('input', e => { state.invoice.date = e.target.value; renderPreview(); });
  $('#invTerm').addEventListener('input', e => { state.invoice.term = e.target.value; renderPreview(); });
  $('#invDueDate').addEventListener('input', e => { state.invoice.dueDate = e.target.value; renderPreview(); });

  $('#custName').addEventListener('input', e => { state.customer.name = e.target.value; renderPreview(); });
  $('#custAddress').addEventListener('input', e => { state.customer.address = e.target.value; renderPreview(); });

  // Event TTD Opsional
  $('#showReceiverSign').addEventListener('change', e => { state.signature.showReceiver = e.target.checked; renderPreview(); });
  $('#sigReceiverTitle').addEventListener('input', e => { state.signature.receiverTitle = e.target.value; renderPreview(); });
  $('#showSenderSign').addEventListener('change', e => { state.signature.showSender = e.target.checked; renderPreview(); });
  $('#sigSenderTitle').addEventListener('input', e => { state.signature.senderTitle = e.target.value; renderPreview(); });
  $('#sigSenderName').addEventListener('input', e => { state.signature.senderName = e.target.value; renderPreview(); });

  $('#discTotal').addEventListener('input', e => { state.discount = +e.target.value || 0; renderPreview(); });
  $('#taxPercent').addEventListener('input', e => { state.taxPercent = +e.target.value || 0; renderPreview(); });

  $('#showBank').addEventListener('change', e => { state.showBank = e.target.checked; renderPreview(); });
  $('#bankDetails').addEventListener('input', e => { state.bankDetails = e.target.value; renderPreview(); });
  $('#bottomNote').addEventListener('input', e => { state.bottomNote = e.target.value; renderPreview(); });

  $('#itemListEditor').addEventListener('input', e => {
    const row = e.target.closest('.item-row-edit');
    if (!row) return;
    const idx = +row.dataset.idx;
    if (e.target.classList.contains('item-name')) state.items[idx].name = e.target.value;
    if (e.target.classList.contains('item-qty')) state.items[idx].qty = +e.target.value || 0;
    if (e.target.classList.contains('item-unit')) state.items[idx].unit = e.target.value;
    if (e.target.classList.contains('item-price')) state.items[idx].price = +e.target.value || 0;
    renderPreview();
  });

  $('#itemListEditor').addEventListener('click', e => {
    if (e.target.classList.contains('btn-del')) {
      const row = e.target.closest('.item-row-edit');
      const idx = +row.dataset.idx;
      state.items.splice(idx, 1);
      renderItemListEditor();
      renderPreview();
    }
  });

  $('#btnAddItem').addEventListener('click', () => {
    state.items.push({ name: '', qty: 1, unit: 'Pcs', price: 0 });
    renderItemListEditor();
    renderPreview();
  });

  $('#btnPrint').addEventListener('click', () => { window.print(); });

  $('#btnReset').addEventListener('click', () => {
    if (confirm('Atur ulang seluruh isi nota ke format standar?')) {
      localStorage.removeItem('dm_invoice_custom_v2');
      state = JSON.parse(JSON.stringify(defaultState));
      syncFormControls();
      renderPreview();
    }
  });

  // Init
  syncFormControls();
  renderPreview();

})();
</script>
</body>
</html>