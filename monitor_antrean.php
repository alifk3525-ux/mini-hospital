<!DOCTYPE html>
<html>
<head>
    <title>Layar Monitor - Mini Hospital</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #4B5320; /* Hijau Army */
            color: white;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
        }
        
        /* Header Instansi */
        .header {
            background-color: #333816;
            padding: 10px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 4px solid #FFD700;
        }
        .judul { font-size: 28px; font-weight: bold; color: #FFD700; margin: 0; text-shadow: 2px 2px 4px rgba(0,0,0,0.5); }
        .sub-judul { font-size: 14px; font-weight: bold; }

        /* Area Konten */
        .konten {
            display: flex;
            flex: 1;
            padding: 15px;
            gap: 15px;
        }

        /* Area Kiri: Video */
        .area-video {
            flex: 5;
            background-color: black;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 16px rgba(0,0,0,0.5);
        }
        video { width: 100%; height: 100%; object-fit: cover; }

        /* Area Kanan: 3 Kotak Antrean */
        .area-kanan {
            flex: 5;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .card-antrean {
            flex: 1;
            background: white;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 25px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            border-left: 12px solid #333;
            transition: 0.3s;
        }

        /* Animasi Kedip / Flash saat nomor berubah */
        @keyframes berkedip {
            0% { background-color: white; }
            50% { background-color: #FFD700; transform: scale(1.03); }
            100% { background-color: white; }
        }
        .efek-kedip {
            animation: berkedip 0.8s ease-in-out 3;
        }

        /* Warna Border Kiri Sesuai Tema Tombol */
        .card-rj { border-left-color: #28a745; } /* Hijau */
        .card-ri { border-left-color: #ffc107; } /* Kuning */
        .card-g  { border-left-color: #17a2b8; } /* Biru */

        .label-poli { font-size: 24px; font-weight: bold; color: #333; }
        .angka-poli { font-size: 55px; font-weight: bold; color: #000; }
    </style>
</head>
<body>

    <div class="header">
        <div>
            <div class="judul">Mini Hospital</div>
            <div class="sub-judul">Institut Teknologi Sains dan Kesehatan RS dr. SOEPRAOEN</div>
        </div>
        <div style="font-size: 22px; font-weight: bold;" id="jam-digital">00:00:00</div>
    </div>

    <div class="konten">
        <!-- Kotak Kiri: Video Lokal -->
        <div class="area-video">
            <video autoplay muted loop>
                <source src="lizard-wave.mp4" type="video/mp4">
                Browser kamu tidak mendukung pemutaran video.
            </video>
        </div>

        <!-- Kotak Kanan: 3 Jenis Antrean Sekaligus -->
        <div class="area-kanan">
            <!-- Rawat Jalan -->
            <div class="card-antrean card-rj">
                <div class="label-poli">RAWAT JALAN</div>
                <div class="angka-poli" id="layar-rj">---</div>
            </div>

            <!-- Rawat Inap -->
            <div class="card-antrean card-ri">
                <div class="label-poli">RAWAT INAP</div>
                <div class="angka-poli" id="layar-ri">---</div>
            </div>

            <!-- Informasi -->
            <div class="card-antrean card-g">
                <div class="label-poli">INFORMASI</div>
                <div class="angka-poli" id="layar-g">---</div>
            </div>
        </div>
    </div>

    <script>
    // Jam Digital
    setInterval(function() {
        let waktu = new Date();
        document.getElementById('jam-digital').innerHTML = waktu.toLocaleTimeString('id-ID');
    }, 1000);

    let daftarSuara = [];
    window.speechSynthesis.onvoiceschanged = function() {
        daftarSuara = window.speechSynthesis.getVoices();
    };

    // Tambahan parameter isRecall untuk membedakan kalimat sapaan
    function bunyikanSuara(nomor, poli, isRecall = false) {
        let teksNomor = nomor.split('').join(' ');
        let kalimat = isRecall 
            ? "Panggilan ulang, nomor antrean, " + teksNomor + ", silakan menuju ke loket " + poli + "."
            : "Nomor antrean, " + teksNomor + ", silakan menuju ke loket " + poli + ".";
        
        let suara = new SpeechSynthesisUtterance(kalimat);
        if (daftarSuara.length === 0) { daftarSuara = window.speechSynthesis.getVoices(); }
        let suaraCewek = daftarSuara.find(voice => voice.lang.includes('id') && (voice.name.includes('Gadis') || voice.name.includes('Google')));
        
        suara.voice = suaraCewek ? suaraCewek : {lang: 'id-ID'};
        suara.rate = 0.85;
        suara.pitch = 1.2;
        window.speechSynthesis.speak(suara);
    }

    function triggerFlash(kelasCard) {
        let kartu = document.querySelector('.' + kelasCard);
        kartu.classList.add('efek-kedip');
        setTimeout(() => {
            kartu.classList.remove('efek-kedip');
        }, 2400); 
    }

    let cacheRJ = '', cacheRI = '', cacheG = '';
    let cacheRecall = ''; // Penyimpan riwayat panggilan ulang

    function cekSemuaAntrean() {
        // 1. Cek Antrean Reguler (Database)
        fetch('data_monitor.php')
            .then(response => response.json())
            .then(data => {
                if(data.rj !== cacheRJ && data.rj !== '---') {
                    triggerFlash('card-rj');
                    cacheRJ = data.rj;
                    bunyikanSuara(data.rj, 'Rawat Jalan');
                }
                if(data.ri !== cacheRI && data.ri !== '---') {
                    triggerFlash('card-ri');
                    cacheRI = data.ri;
                    bunyikanSuara(data.ri, 'Rawat Inap');
                }
                if(data.g !== cacheG && data.g !== '---') {
                    triggerFlash('card-g');
                    cacheG = data.g;
                    bunyikanSuara(data.g, 'Informasi');
                }

                document.getElementById('layar-rj').innerHTML = data.rj;
                document.getElementById('layar-ri').innerHTML = data.ri;
                document.getElementById('layar-g').innerHTML = data.g;
            })
            .catch(error => console.log("Gagal mengambil data database", error));

        // 2. Cek Sinyal Panggilan Ulang (File Teks Eksternal)
        // Parameter ?v= ditambahkan agar browser tidak me-load file versi lama
        fetch('pemicu_suara.txt?v=' + new Date().getTime())
            .then(response => response.text())
            .then(data => {
                // Pastikan file tidak kosong dan isinya berubah dari sebelumnya
                if(data !== cacheRecall && cacheRecall !== '') {
                    let pisah = data.split('|');
                    if(pisah.length >= 2) {
                        let nomor = pisah[0];
                        let poli = pisah[1];
                        
                        bunyikanSuara(nomor, poli, true); // True = Panggilan Ulang
                        
                        // Picu efek visual kedip
                        if (poli === 'Rawat Jalan') triggerFlash('card-rj');
                        if (poli === 'Rawat Inap') triggerFlash('card-ri');
                        if (poli === 'Informasi') triggerFlash('card-g');
                    }
                }
                cacheRecall = data;
            })
            .catch(error => {}); // Abaikan jika file teks pemicu belum dibuat
    }

    setInterval(cekSemuaAntrean, 2000);
    cekSemuaAntrean();
</script>

</body>
</html>