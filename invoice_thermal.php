<?php
// Pengaman Sesi & Koneksi DB
session_start();
include 'koneksi.php';

// Ambil data transaksi dari DB jika parameter ID dikirim
$id_billing = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$db_data = null;

if ($id_billing > 0 && isset($koneksi)) {
    $q = mysqli_query($koneksi, "SELECT b.*, p.nama_lengkap, p.alamat 
                                FROM billing_kasir b 
                                JOIN pasien p ON b.no_rm = p.no_rm 
                                WHERE b.id_billing = '$id_billing'");
    if ($q && mysqli_num_rows($q) > 0) {
        $db_data = mysqli_fetch_assoc($q);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Nota Thermal Kasir Pro</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root{
    --ink:#1E2420;
    --ink-soft:#4B5650;
    --paper-app:#EDEBE2;
    --panel:#FFFFFF;
    --accent:#2F6B4F;
    --accent-dark:#234E3A;
    --line:#D8D4C8;
    --line-strong:#BFBAA9;
    --danger:#A6412A;
    --receipt-bg:#FFFFFF;
    --radius:3px;
    color-scheme: light;
  }
  *{box-sizing:border-box;}
  html,body{
    margin:0;
    min-height:100%;
    background:var(--paper-app);
    color:var(--ink);
    font-family:'IBM Plex Sans', -apple-system, sans-serif;
  }

  .app{
    display:flex;
    flex-direction:column;
    min-height:100vh;
  }

  header.topbar{
    padding:16px clamp(16px,4vw,32px);
    border-bottom:1px solid var(--line-strong);
    background:var(--panel);
    display:flex;
    align-items:baseline;
    gap:12px;
    flex-wrap:wrap;
  }
  header.topbar h1{
    font-size:1.15rem;
    margin:0;
    font-weight:700;
  }
  header.topbar p{
    margin:0;
    font-size:0.85rem;
    color:var(--ink-soft);
  }

  .layout{
    flex:1;
    display:grid;
    grid-template-columns: minmax(320px, 440px) 1fr;
    gap:0;
    min-height:0;
  }
  @media (max-width: 860px){
    .layout{grid-template-columns: 1fr;}
  }

  .panel{
    background:var(--panel);
    border-right:1px solid var(--line-strong);
    padding:20px clamp(16px,3vw,28px) 60px;
    overflow-y:auto;
  }
  @media (max-width: 860px){
    .panel{border-right:none; border-bottom:1px solid var(--line-strong);}
  }

  fieldset{
    border:none;
    padding:0;
    margin:0 0 24px;
  }
  legend{
    font-size:0.72rem;
    font-weight:700;
    letter-spacing:0.06em;
    color:var(--accent-dark);
    padding:0 0 8px;
    border-bottom:1px solid var(--line);
    width:100%;
    margin-bottom:14px;
    text-transform:uppercase;
  }

  .field{margin-bottom:12px;}
  .field label{
    display:block;
    font-size:0.76rem;
    color:var(--ink-soft);
    margin-bottom:4px;
  }
  .row2{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:10px;
  }
  input[type=text], input[type=number], input[type=date], select, textarea{
    width:100%;
    padding:8px 10px;
    border:1px solid var(--line-strong);
    border-radius:var(--radius);
    background:#fff;
    color:var(--ink);
    font-family:inherit;
    font-size:0.88rem;
  }
  textarea{resize:vertical; min-height:56px;}
  input:focus, select:focus, textarea:focus, button:focus-visible{
    outline:2px solid var(--accent);
    outline-offset:1px;
  }

  .paper-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:8px;
    margin-bottom:10px;
  }
  .paper-opt{
    border:1px solid var(--line-strong);
    border-radius:var(--radius);
    padding:8px 10px;
    font-size:0.8rem;
    background:#fff;
    cursor:pointer;
    text-align:left;
    color:var(--ink);
  }
  .paper-opt.active{
    border-color:var(--accent);
    background:#EAF2EC;
    color:var(--accent-dark);
    font-weight:600;
  }
  .paper-opt small{display:block; color:var(--ink-soft); font-weight:400;}

  .custom-size{
    display:none;
    grid-template-columns:1fr 1fr;
    gap:10px;
    margin-top:8px;
  }
  .custom-size.show{display:grid;}

  /* UI Upload Logo */
  .sr-only{
    position:absolute; width:1px; height:1px; padding:0; margin:-1px;
    overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; border:0;
  }
  .logo-upload{display:flex; gap:12px; align-items:center;}
  .logo-thumb{
    width:68px; height:68px; flex-shrink:0;
    border:1px dashed var(--line-strong);
    border-radius:var(--radius);
    background:#FAFAF6;
    display:flex; align-items:center; justify-content:center;
    overflow:hidden; text-align:center;
    font-size:0.68rem; line-height:1.3; color:var(--ink-soft);
    padding:4px;
  }
  .logo-thumb img, .logo-thumb svg{max-width:100%; max-height:100%; object-fit:contain; display:block;}
  .logo-actions{display:flex; gap:8px; flex-wrap:wrap;}
  .btn-small{
    display:inline-block;
    background:#fff;
    border:1px solid var(--line-strong);
    color:var(--accent-dark);
    padding:7px 12px;
    border-radius:var(--radius);
    font-size:0.8rem;
    font-weight:600;
    cursor:pointer;
    font-family:inherit;
  }
  .btn-small:hover{border-color:var(--accent);}
  .btn-small.danger{color:var(--danger);}
  .btn-small.danger:hover{border-color:var(--danger);}
  .logo-hint{font-size:0.72rem; color:var(--ink-soft); margin:8px 0 0;}
  .logo-options{display:none; margin-top:12px;}
  .logo-options.show{display:block;}
  .logo-options input[type=range]{width:100%; accent-color:var(--accent); padding:0;}
  .range-head{
    display:flex; justify-content:space-between;
    font-size:0.76rem; color:var(--ink-soft); margin-bottom:4px;
  }
  .check-row{
    display:flex; align-items:center; gap:8px;
    font-size:0.8rem; color:var(--ink-soft);
    margin-top:10px; cursor:pointer;
  }
  .check-row input{width:auto; margin:0;}

  /* Tabel Item */
  table.items{
    width:100%;
    border-collapse:collapse;
    margin-bottom:10px;
  }
  table.items th{
    font-size:0.68rem;
    text-transform:uppercase;
    color:var(--ink-soft);
    text-align:left;
    padding:0 4px 6px;
    font-weight:600;
  }
  table.items td{
    padding:3px 4px;
    vertical-align:top;
  }
  table.items td input{padding:6px 8px; font-size:0.85rem;}
  .th-qty{width:52px;}
  .th-price{width:96px;}
  .th-del{width:28px;}
  .del-row{
    background:none;
    border:none;
    color:var(--danger);
    font-size:1.1rem;
    cursor:pointer;
    padding:6px 2px;
  }
  .add-row{
    background:none;
    border:1px dashed var(--line-strong);
    color:var(--accent-dark);
    border-radius:var(--radius);
    padding:8px 10px;
    font-size:0.82rem;
    cursor:pointer;
    width:100%;
    font-weight:600;
  }
  .add-row:hover{border-color:var(--accent);}

  .toggle-pct{
    display:flex;
    gap:6px;
    margin-top:4px;
  }
  .toggle-pct button{
    flex:1;
    padding:6px 0;
    border:1px solid var(--line-strong);
    background:#fff;
    border-radius:var(--radius);
    font-size:0.76rem;
    cursor:pointer;
    color:var(--ink-soft);
  }
  .toggle-pct button.active{
    background:var(--accent);
    border-color:var(--accent);
    color:#fff;
  }

  .btn-primary{
    background:var(--accent);
    color:#fff;
    border:none;
    padding:11px 16px;
    border-radius:var(--radius);
    font-size:0.9rem;
    font-weight:600;
    cursor:pointer;
  }
  .btn-primary:hover{background:var(--accent-dark);}
  .btn-ghost{
    background:none;
    border:1px solid var(--line-strong);
    color:var(--ink-soft);
    padding:11px 14px;
    border-radius:var(--radius);
    font-size:0.9rem;
    cursor:pointer;
  }
  .btn-ghost:hover{border-color:var(--danger); color:var(--danger);}

  /* Area Preview Struk */
  .preview-wrap{
    display:flex;
    flex-direction:column;
    min-width:0;
  }
  .preview-toolbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    padding:14px clamp(16px,3vw,28px);
    border-bottom:1px solid var(--line);
    background:var(--panel);
  }
  .preview-toolbar .meta{font-size:0.78rem; color:var(--ink-soft);}

  .preview-stage{
    flex:1;
    overflow:auto;
    display:flex;
    justify-content:center;
    padding:36px 20px 60px;
    background:repeating-linear-gradient(0deg, transparent, transparent 27px, rgba(0,0,0,0.025) 28px);
  }

  /* Layout Nota Thermal */
  .receipt{
    background:var(--receipt-bg);
    color:#000;
    font-family:'IBM Plex Mono', 'Courier New', monospace;
    font-size:12px;
    line-height:1.35;
    padding:16px 10px 24px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.06), 0 12px 28px rgba(20,20,10,0.14);
    position:relative;
    align-self:flex-start;
  }
  .receipt::after{
    content:"";
    position:absolute;
    left:0; right:0; bottom:-9px;
    height:10px;
    background-image: linear-gradient(-45deg, var(--paper-app) 6px, transparent 0), linear-gradient(45deg, var(--paper-app) 6px, transparent 0);
    background-position: bottom left;
    background-size: 12px 12px;
    background-repeat: repeat-x;
  }

  .r-center{text-align:center;}
  .r-title{font-weight:700; font-size:14px; letter-spacing:0.02em; text-transform:uppercase;}
  .r-sub{font-size:11px; white-space:pre-line; margin-top:2px;}
  .r-hr{border:none; border-top:1px dashed #000; margin:6px 0;}
  .r-hr-double{border:none; border-top:2px dashed #000; margin:6px 0;}

  .r-meta-table{width:100%; font-size:11.5px; border-collapse:collapse;}
  .r-meta-table td{padding:1px 0; vertical-align:top;}
  .r-meta-table td.lbl{width:38%;}
  .r-meta-table td.cln{width:4%;}

  .r-item-block{margin-bottom:6px;}
  .r-item-title{font-weight:700; font-size:11.5px; word-break:break-word;}
  .r-item-details{display:flex; justify-content:space-between; font-size:11.5px; padding-left:10px;}

  .r-totals{margin-top:4px; font-size:11.5px;}
  .r-totals .line{display:flex; justify-content:space-between; padding:1px 0;}
  .r-totals .bold{font-weight:700;}
  .r-footer{margin-top:10px; font-size:11px; text-align:left; white-space:pre-line;}
  .r-tagline{margin-top:12px; font-size:11px; font-weight:700; text-align:center; white-space:pre-line;}

  .r-logo{display:block; margin:0 auto 6px; max-width:100%; height:auto;}
  .r-logo.bw{filter:grayscale(1) contrast(1.6);}

  @media print{
    header.topbar, .panel, .preview-toolbar{display:none !important;}
    .preview-stage{padding:0; background:none; display:block;}
    .app, .layout{display:block; min-height:0;}
    body{background:#fff; padding:0; margin:0;}
    .receipt{box-shadow:none; margin:0; padding:4px 2px;}
    .receipt::after{display:none;}
  }
</style>
</head>
<body>
<div class="app">
  <header class="topbar">
    <h1>🖨️ Cetak Nota Thermal Kasir</h1>
    <p>Kustomisasi isi nota, atur logo, dan cetak langsung ke printer thermal (58mm / 80mm).</p>
  </header>

  <div class="layout">
    <div class="panel" id="panel">

      <fieldset>
        <legend>Ukuran Kertas</legend>
        <div class="paper-grid" id="paperGrid"></div>
        <div class="custom-size" id="customSizeBox">
          <div class="field">
            <label for="customW">Lebar (mm)</label>
            <input type="number" id="customW" min="20" max="500" value="80">
          </div>
          <div class="field">
            <label for="customH">Tinggi (mm)</label>
            <input type="number" id="customH" min="0" max="1000" placeholder="Otomatis">
          </div>
        </div>
      </fieldset>

      <fieldset>
        <legend>Identitas Usaha & Logo</legend>

        <div class="field">
          <label>Logo Usaha</label>
          <div class="logo-upload">
            <div class="logo-thumb" id="logoThumb">Loading...</div>
            <div class="logo-actions">
              <label class="btn-small" for="logoFile" id="logoPickLabel">Pilih Gambar</label>
              <input type="file" id="logoFile" class="sr-only" accept="image/*">
              <button type="button" class="btn-small danger" id="logoRemove" style="display:none;">Hapus</button>
            </div>
          </div>
          <p class="logo-hint">PNG, JPG, atau SVG. Gunakan logo latar putih/transparan untuk hasil terbaik.</p>
          <div class="logo-options" id="logoOptions">
            <div class="range-head"><span>Ukuran logo</span><span id="logoSizeVal">40%</span></div>
            <input type="range" id="logoSize" min="10" max="100" step="5" value="40">
            <label class="check-row" for="logoBW">
              <input type="checkbox" id="logoBW">
              <span>Hitam-putih (efek kontras printer thermal)</span>
            </label>
          </div>
        </div>

        <div class="field">
          <label for="storeName">Nama Usaha / Unit Service</label>
          <input type="text" id="storeName" value="KASIR & PHARMACY APOTEK">
        </div>
        <div class="field">
          <label for="storeAddr">Alamat Lengkap</label>
          <textarea id="storeAddr">Jl. Kesdam V Brawijaya No. 1
Klojen, Malang, Jawa Timur</textarea>
        </div>
        <div class="field">
          <label for="storePhone">No. Telepon / Layanan</label>
          <input type="text" id="storePhone" value="(0341) 1234567">
        </div>
      </fieldset>

      <fieldset>
        <legend>Detail Nota Transaksi</legend>
        <div class="row2">
          <div class="field">
            <label for="notaDate">Tanggal & Jam</label>
            <input type="text" id="notaDate" value="<?php echo date('d/m/Y H:i'); ?>">
          </div>
          <div class="field">
            <label for="notaNo">No. Kwitansi / Nota</label>
            <input type="text" id="notaNo" value="KW-<?php echo date('dmy-His'); ?>">
          </div>
        </div>
        <div class="row2">
          <div class="field">
            <label for="customer">Nama Pelanggan / Pasien</label>
            <input type="text" id="customer" value="Pelanggan Umum">
          </div>
          <div class="field">
            <label for="salesType">Jenis Layanan / Bayar</label>
            <input type="text" id="salesType" value="Tunai">
          </div>
        </div>
        <div class="row2">
          <div class="field">
            <label for="user">User Akses</label>
            <input type="text" id="user" value="Petugas Kasir">
          </div>
          <div class="field">
            <label for="kasir">Cashier / Kasir</label>
            <input type="text" id="kasir" value="Petugas Kasir">
          </div>
        </div>
      </fieldset>

      <fieldset>
        <legend>Daftar Pesanan / Items</legend>
        <table class="items">
          <thead>
            <tr>
              <th class="th-qty">Qty</th>
              <th>Nama Barang / Layanan</th>
              <th class="th-price">Harga (Rp)</th>
              <th class="th-del"></th>
            </tr>
          </thead>
          <tbody id="itemsBody"></tbody>
        </table>
        <button type="button" class="add-row" id="addRow">+ Tambah Items</button>
      </fieldset>

      <fieldset>
        <legend>Diskon, Pajak &amp; Biaya Lain</legend>
        <div class="field">
          <label for="diskonVal">Diskon</label>
          <input type="number" id="diskonVal" min="0" value="0">
          <div class="toggle-pct" id="diskonMode">
            <button type="button" data-mode="rp" class="active">Rp</button>
            <button type="button" data-mode="pct">%</button>
          </div>
        </div>
        <div class="field">
          <label for="pajakVal">Pajak / Tax (%)</label>
          <input type="number" id="pajakVal" min="0" step="1" value="0">
        </div>
        <div class="field">
          <label for="biayaLain">Biaya Lain / Admin (Rp)</label>
          <input type="number" id="biayaLain" min="0" value="0">
        </div>
        <div class="field">
          <label for="cashVal">Uang Tunai / Cash (Rp)</label>
          <input type="number" id="cashVal" min="0" step="1000" value="0">
        </div>
      </fieldset>

      <fieldset>
        <legend>Catatan Kaki (Footer)</legend>
        <div class="field">
          <label for="footerNote">Kontak / Keterangan Tambahan</label>
          <textarea id="footerNote">Simpan nota ini sebagai bukti pembayaran yang sah.
Barang yang sudah dibeli tidak dapat ditukar.</textarea>
        </div>
        <div class="field">
          <label for="tagline">Slogan / Tagline Akhir</label>
          <textarea id="tagline">TERIMA KASIH ATAS KUNJUNGAN ANDA
SEMOGA LEKAS SEMBUH</textarea>
        </div>
      </fieldset>

      <button type="button" class="btn-ghost" id="resetBtn" style="width:100%;">Reset Data ke Default</button>
    </div>

    <div class="preview-wrap">
      <div class="preview-toolbar">
        <div class="meta" id="paperMeta">Thermal 80mm</div>
        <div class="actions">
          <button type="button" class="btn-primary" id="printBtn">🖨️ Cetak Nota Thermal</button>
        </div>
      </div>
      <div class="preview-stage">
        <div class="receipt" id="receipt"></div>
      </div>
    </div>
  </div>
</div>

<style id="pageSizeStyle"></style>

<script>
(function(){
  'use strict';

  // Injeksi data dari database PHP jika ada
  const dbInjected = <?php echo json_encode($db_data); ?>;

  const DEFAULT_LOGO_SVG = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="45" fill="none" stroke="%23000" stroke-width="2.5"/><path d="M 50 20 L 50 80 M 20 50 L 80 50" stroke="%23000" stroke-width="8" stroke-linecap="round"/><text x="50" y="92" font-family="sans-serif" font-size="7" font-weight="bold" text-anchor="middle" fill="%23000">HEALTHCARE</text></svg>';

  const PAPERS = [
    {id:'t58', label:'Thermal 58mm', sub:'Struk kasir kecil', w:58, h:null},
    {id:'t80', label:'Thermal 80mm', sub:'Struk kasir standar', w:80, h:null},
    {id:'a5',  label:'A5', sub:'148 × 210 mm', w:148, h:210},
    {id:'a4',  label:'A4', sub:'210 × 297 mm', w:210, h:297},
    {id:'letter', label:'Letter', sub:'215.9 × 279.4 mm', w:215.9, h:279.4},
    {id:'custom', label:'Ukuran Bebas', sub:'Tentukan sendiri', w:null, h:null},
  ];

  const $ = (id) => document.getElementById(id);
  const fmtRp = (n) => Math.round(n||0).toLocaleString('id-ID');

  let state = {
    paper: 't80',
    customW: 80,
    customH: null,
    items: [
      {qty:1, name:'Pemeriksaan & Admin', price: 15000},
      {qty:1, name:'Resep Obat / Farmasi', price: 35000}
    ],
    diskonMode: 'rp',
    logo: DEFAULT_LOGO_SVG,
    logoSize: 40,
    logoBW: false
  };

  function loadStorage(){
    try{
      const raw = localStorage.getItem('nota-thermal-kasir-state');
      if(raw){
        const saved = JSON.parse(raw);
        state = Object.assign(state, saved);
      }
    }catch(e){}

    // Mengisi data otomatis dari database jika parameter ID ada
    if (dbInjected) {
      if ($('notaNo')) $('notaNo').value = dbInjected.no_kwitansi || $('notaNo').value;
      if ($('notaDate')) $('notaDate').value = dbInjected.waktu_bayar || $('notaDate').value;
      if ($('customer')) $('customer').value = dbInjected.nama_lengkap || $('customer').value;
      if ($('salesType')) $('salesType').value = dbInjected.metode_bayar || 'Tunai';
      if ($('kasir')) $('kasir').value = dbInjected.petugas_kasir || 'Kasir DB';
      if ($('user')) $('user').value = dbInjected.petugas_kasir || 'Kasir DB';

      // Konstruksi rincian items dari DB
      const dbItems = [];
      if (+dbInjected.biaya_admin > 0) dbItems.push({qty: 1, name: 'Biaya Administrasi', price: +dbInjected.biaya_admin});
      if (+dbInjected.biaya_dokter > 0) dbItems.push({qty: 1, name: 'Jasa Konsultasi Dokter', price: +dbInjected.biaya_dokter});
      if (+dbInjected.biaya_obat > 0) dbItems.push({qty: 1, name: 'Total Obat & Alkes', price: +dbInjected.biaya_obat});
      if (+dbInjected.biaya_tindakan > 0) dbItems.push({qty: 1, name: 'Tindakan Medis', price: +dbInjected.biaya_tindakan});

      if (dbItems.length > 0) {
        state.items = dbItems;
      }
      
      const totalAwal = dbItems.reduce((acc, it) => acc + (it.qty * it.price), 0);
      if ($('cashVal')) $('cashVal').value = Math.max(totalAwal, +dbInjected.total_bayar || totalAwal);
    }
  }

  function saveStorage(){
    try{
      localStorage.setItem('nota-thermal-kasir-state', JSON.stringify({
        paper: state.paper, customW: state.customW, customH: state.customH,
        items: state.items, diskonMode: state.diskonMode,
        logo: state.logo, logoSize: state.logoSize, logoBW: state.logoBW
      }));
    }catch(e){}
  }

  // ---------- Kertas Selector ----------
  function renderPaperGrid(){
    const grid = $('paperGrid');
    grid.innerHTML = '';
    PAPERS.forEach(p=>{
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'paper-opt' + (state.paper===p.id ? ' active' : '');
      btn.innerHTML = p.label + '<small>' + p.sub + '</small>';
      btn.addEventListener('click', ()=>{
        state.paper = p.id;
        saveStorage();
        renderPaperGrid();
        applyPaperSize();
      });
      grid.appendChild(btn);
    });
    $('customSizeBox').classList.toggle('show', state.paper === 'custom');
  }

  function currentPaperDims(){
    if(state.paper === 'custom'){
      return {w: Number($('customW').value)||80, h: Number($('customH').value)||null};
    }
    const p = PAPERS.find(x=>x.id===state.paper);
    return {w: p.w, h: p.h};
  }

  function applyPaperSize(){
    const {w} = currentPaperDims();
    $('receipt').style.width = w + 'mm';
    const p = PAPERS.find(x=>x.id===state.paper);
    $('paperMeta').textContent = state.paper === 'custom'
      ? w + 'mm × ' + ($('customH').value ? $('customH').value + 'mm' : 'otomatis')
      : p.label + ' — ' + p.sub;
  }

  $('customW').addEventListener('input', ()=>{ state.customW = Number($('customW').value); saveStorage(); applyPaperSize(); });
  $('customH').addEventListener('input', ()=>{ state.customH = Number($('customH').value)||null; saveStorage(); applyPaperSize(); });

  // ---------- Tabel Items ----------
  function renderItemsInputs(){
    const body = $('itemsBody');
    body.innerHTML = '';
    state.items.forEach((item, idx)=>{
      const tr = document.createElement('tr');
      tr.innerHTML =
        '<td><input type="number" min="0" step="1" class="it-qty" value="'+item.qty+'"></td>' +
        '<td><input type="text" class="it-name" value="'+escapeAttr(item.name)+'"></td>' +
        '<td><input type="number" min="0" step="500" class="it-price" value="'+item.price+'"></td>' +
        '<td><button type="button" class="del-row" title="Hapus">&times;</button></td>';
      tr.querySelector('.it-qty').addEventListener('input', e=>{ item.qty = Number(e.target.value)||0; saveStorage(); renderReceipt(); });
      tr.querySelector('.it-name').addEventListener('input', e=>{ item.name = e.target.value; saveStorage(); renderReceipt(); });
      tr.querySelector('.it-price').addEventListener('input', e=>{ item.price = Number(e.target.value)||0; saveStorage(); renderReceipt(); });
      tr.querySelector('.del-row').addEventListener('click', ()=>{ state.items.splice(idx,1); saveStorage(); renderItemsInputs(); renderReceipt(); });
      body.appendChild(tr);
    });
  }

  $('addRow').addEventListener('click', ()=>{
    state.items.push({qty:1, name:'', price:0});
    saveStorage();
    renderItemsInputs();
    renderReceipt();
  });

  function escapeAttr(s){ return String(s||'').replace(/"/g,'&quot;'); }
  function escapeHtml(s){
    return String(s||'').replace(/[&<>]/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;'}[c]));
  }

  // ---------- Diskon Mode ----------
  $('diskonMode').addEventListener('click', (e)=>{
    const btn = e.target.closest('button');
    if(!btn) return;
    state.diskonMode = btn.dataset.mode;
    [...$('diskonMode').children].forEach(b=>b.classList.toggle('active', b===btn));
    saveStorage();
    renderReceipt();
  });

  // ---------- System Upload Logo ----------
  const LOGO_MAX_PX = 600;

  function renderLogoControls(){
    const thumb = $('logoThumb');
    if(state.logo){
      thumb.innerHTML = '';
      const im = document.createElement('img');
      im.src = state.logo;
      im.alt = 'Preview Logo';
      thumb.appendChild(im);
    } else {
      thumb.textContent = 'Tanpa Logo';
    }
    $('logoPickLabel').textContent = state.logo ? 'Ganti Gambar' : 'Pilih Gambar';
    $('logoRemove').style.display = state.logo ? '' : 'none';
    $('logoOptions').classList.toggle('show', !!state.logo);
    $('logoSize').value = state.logoSize;
    $('logoSizeVal').textContent = state.logoSize + '%';
    $('logoBW').checked = !!state.logoBW;
  }

  function handleLogoFile(file){
    if(!file) return;
    if(!/^image\//.test(file.type)){
      alert('File harus berupa gambar (PNG, JPG, SVG, dll).');
      return;
    }
    const reader = new FileReader();
    reader.onerror = ()=> alert('File tidak dapat dibaca.');
    reader.onload = ()=>{
      const img = new Image();
      img.onerror = ()=> alert('Gambar tidak valid.');
      img.onload = ()=>{
        let w = img.naturalWidth || 300;
        let h = img.naturalHeight || 300;
        const scale = Math.min(1, LOGO_MAX_PX / Math.max(w, h));
        w = Math.max(1, Math.round(w * scale));
        h = Math.max(1, Math.round(h * scale));
        const canvas = document.createElement('canvas');
        canvas.width = w; canvas.height = h;
        canvas.getContext('2d').drawImage(img, 0, 0, w, h);
        state.logo = canvas.toDataURL('image/png');
        saveStorage();
        renderLogoControls();
        renderReceipt();
      };
      img.src = reader.result;
    };
    reader.readAsDataURL(file);
  }

  $('logoFile').addEventListener('change', (e)=>{
    handleLogoFile(e.target.files && e.target.files[0]);
    e.target.value = '';
  });

  $('logoRemove').addEventListener('click', ()=>{
    state.logo = null;
    saveStorage();
    renderLogoControls();
    renderReceipt();
  });

  $('logoSize').addEventListener('input', (e)=>{
    state.logoSize = Number(e.target.value) || 40;
    $('logoSizeVal').textContent = state.logoSize + '%';
    saveStorage();
    renderReceipt();
  });

  $('logoBW').addEventListener('change', (e)=>{
    state.logoBW = e.target.checked;
    saveStorage();
    renderReceipt();
  });

  // ---------- Render Nota Struk ----------
  function renderReceipt(){
    const storeName = $('storeName').value || '';
    const storeAddr = $('storeAddr').value || '';
    const storePhone = $('storePhone').value || '';
    
    const notaDate = $('notaDate').value || '';
    const notaNo = $('notaNo').value || '';
    const customer = $('customer').value || '';
    const salesType = $('salesType').value || '';
    const user = $('user').value || '';
    const kasir = $('kasir').value || '';

    const footerNote = $('footerNote').value || '';
    const tagline = $('tagline').value || '';

    const subtotal = state.items.reduce((s,it)=> s + (it.qty * it.price), 0);
    const totalItemQty = state.items.reduce((s,it)=> s + Number(it.qty||0), 0);

    const diskonRaw = Number($('diskonVal').value)||0;
    const diskonAmount = state.diskonMode === 'pct' ? subtotal * diskonRaw / 100 : diskonRaw;
    const afterDiskon = Math.max(subtotal - diskonAmount, 0);

    const pajakPct = Number($('pajakVal').value)||0;
    const pajakAmount = afterDiskon * pajakPct / 100;
    const biayaLain = Number($('biayaLain').value)||0;

    const total = afterDiskon + pajakAmount + biayaLain;
    const cash = Number($('cashVal').value)||0;
    const change = Math.max(cash - total, 0);

    // Render Items
    let itemsHtml = '';
    if(state.items.length === 0){
      itemsHtml = '<div style="color:#999; text-align:center;">(Belum ada item)</div>';
    } else {
      state.items.forEach(it=>{
        itemsHtml += '<div class="r-item-block">' +
          '<div class="r-item-title">'+escapeHtml(it.name||'(tanpa nama)')+'</div>' +
          '<div class="r-item-details">' +
            '<span>'+it.qty+'x '+fmtRp(it.price)+'</span>' +
            '<span>'+fmtRp(it.qty * it.price)+'</span>' +
          '</div>' +
        '</div>';
      });
    }

    // Render Logo
    const logoHtml = state.logo
      ? '<img class="r-logo'+(state.logoBW ? ' bw' : '')+'" alt="Logo" src="'+state.logo+'" style="width:'+state.logoSize+'%;">'
      : '';

    $('receipt').innerHTML =
      '<div class="r-center">' +
        logoHtml +
        '<div class="r-title">'+escapeHtml(storeName)+'</div>' +
        '<div class="r-sub">'+escapeHtml(storeAddr)+(storePhone ? '\nPhone: '+escapeHtml(storePhone) : '')+'</div>' +
      '</div>' +

      '<div class="r-hr-double"></div>' +

      '<table class="r-meta-table">' +
        '<tr><td class="lbl">Tgl / Waktu</td><td class="cln">:</td><td>'+escapeHtml(notaDate)+'</td></tr>' +
        '<tr><td class="lbl">No. Nota</td><td class="cln">:</td><td>'+escapeHtml(notaNo)+'</td></tr>' +
        (customer ? '<tr><td class="lbl">Pelanggan</td><td class="cln">:</td><td>'+escapeHtml(customer)+'</td></tr>' : '') +
        (salesType ? '<tr><td class="lbl">Pembayaran</td><td class="cln">:</td><td>'+escapeHtml(salesType)+'</td></tr>' : '') +
        (user ? '<tr><td class="lbl">Petugas</td><td class="cln">:</td><td>'+escapeHtml(user)+'</td></tr>' : '') +
        (kasir ? '<tr><td class="lbl">Kasir</td><td class="cln">:</td><td>'+escapeHtml(kasir)+'</td></tr>' : '') +
      '</table>' +

      '<div class="r-hr-double"></div>' +

      itemsHtml +

      '<div class="r-hr"></div>' +
      '<div class="bold" style="font-size:12px; margin:2px 0;">Total Qty Item: '+totalItemQty+'</div>' +
      '<div class="r-hr"></div>' +

      '<div class="r-totals">' +
        '<div class="line"><span>Subtotal</span><span>'+fmtRp(subtotal)+'</span></div>' +
        (diskonAmount > 0 ? '<div class="line"><span>Diskon</span><span>-'+fmtRp(diskonAmount)+'</span></div>' : '') +
        (pajakPct > 0 ? '<div class="line"><span>Tax</span><span>'+fmtRp(pajakAmount)+'</span></div>' : '') +
        (biayaLain > 0 ? '<div class="line"><span>Biaya Admin</span><span>'+fmtRp(biayaLain)+'</span></div>' : '') +
        '<div class="line bold"><span>TOTAL</span><span>'+fmtRp(total)+'</span></div>' +
        (cash > 0 ? 
          '<div class="line" style="margin-top:4px;"><span>Tender / Tunai</span><span></span></div>' +
          '<div class="line"><span style="padding-left:8px;">Cash</span><span>'+fmtRp(cash)+'</span></div>' +
          '<div class="line"><span style="padding-left:8px;">Kembalian</span><span>'+fmtRp(change)+'</span></div>' 
          : ''
        ) +
      '</div>' +

      (footerNote ? '<div class="r-hr"></div><div class="r-footer">'+escapeHtml(footerNote)+'</div>' : '') +
      (tagline ? '<div class="r-tagline">'+escapeHtml(tagline)+'</div>' : '');
  }

  // ---------- Eksekusi Print ----------
  $('printBtn').addEventListener('click', ()=>{
    const {w, h} = currentPaperDims();
    let pageH = h;
    if(!pageH){
      const pxHeight = $('receipt').offsetHeight;
      const mmHeight = (pxHeight / 96 * 25.4) + 10;
      pageH = Math.max(mmHeight, 40);
    }
    $('pageSizeStyle').textContent = '@page{ size:'+w+'mm '+pageH.toFixed(1)+'mm; margin:0; }';
    setTimeout(()=>window.print(), 30);
  });

  // ---------- Reset Data ----------
  $('resetBtn').addEventListener('click', ()=>{
    if(!confirm('Kembalikan semua data nota ke tampilan awal?')) return;
    try{ localStorage.removeItem('nota-thermal-kasir-state'); }catch(e){}
    location.reload();
  });

  // Event Listeners
  ['storeName','storeAddr','storePhone','notaDate','notaNo','customer','salesType','user','kasir','diskonVal','pajakVal','biayaLain','cashVal','footerNote','tagline']
    .forEach(id=> $(id).addEventListener('input', renderReceipt));

  // Init App
  function init(){
    loadStorage();
    renderPaperGrid();
    applyPaperSize();
    renderItemsInputs();
    renderLogoControls();
    
    [...$('diskonMode').children].forEach(b=> b.classList.toggle('active', b.dataset.mode===state.diskonMode));
    renderReceipt();
  }
  
  init();
})();
</script>
</body>
</html>