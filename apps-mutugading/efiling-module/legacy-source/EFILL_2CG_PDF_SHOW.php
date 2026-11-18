<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PDF Viewer</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.14.305/pdf.min.js"></script>
    <style>
        #viewer { width: 100%; height: 90vh; border: 1px solid #ccc; overflow: auto; }

        /* CSS untuk mencegah print */
        @media print {
            body {
                display: none; /* Sembunyikan seluruh halaman saat cetak */
            }
        }
    </style>
</head>
<body>
    <div id="viewer"></div>
    <script>
        // Deteksi perangkat mobile
        function isMobileDevice() {
            return /Android|iPhone|iPad|iPod|Opera Mini|IEMobile|WPDesktop/i.test(navigator.userAgent);
        }

        // Mencegah akses dari perangkat mobile
        if (isMobileDevice()) {
            alert('Halaman ini tidak dapat diakses dari perangkat mobile.');
            document.body.innerHTML = '<h1>Halaman tidak tersedia di perangkat mobile.</h1>';
            throw new Error('Diblokir di perangkat mobile'); // Menghentikan eksekusi script lainnya
        }

        // Ganti URL dengan URL file PDF yang ingin ditampilkan
        const url = "<?php Print($VIEW_LINK); ?>"

        // Set lokasi dari pdf.worker.js
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.14.305/pdf.worker.min.js';

        // Fungsi untuk merender satu halaman
        function renderPage(page, viewer) {
            const scale = 1.5; // Skala zoom
            const viewport = page.getViewport({ scale });

            // Membuat elemen canvas untuk merender halaman PDF
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');
            canvas.height = viewport.height;
            canvas.width = viewport.width;
            viewer.appendChild(canvas);

            const renderContext = {
                canvasContext: context,
                viewport: viewport
            };

            return page.render(renderContext);
        }

        // Memuat dokumen PDF
        const loadingTask = pdfjsLib.getDocument(url);
        loadingTask.promise.then(pdf => {
            const viewer = document.getElementById('viewer');

            // Perulangan untuk merender setiap halaman PDF
            for (let pageNumber = 1; pageNumber <= pdf.numPages; pageNumber++) {
                pdf.getPage(pageNumber).then(page => {
                    renderPage(page, viewer);
                });
            }
        }).catch(error => {
            console.error('Error loading PDF:', error);
            document.getElementById('viewer').innerText = 'Gagal memuat PDF.';
        });

        // JavaScript untuk mencegah penggunaan Ctrl+P atau Command+P (Print)
        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                alert('Fungsi print dinonaktifkan.');
            }
        });

        // Cegah klik kanan untuk opsi cetak
        document.addEventListener('contextmenu', function (e) {
            e.preventDefault();
            alert('Klik kanan dinonaktifkan.');
        });

        // JavaScript untuk mencegah penggunaan tombol Print Screen
        document.addEventListener('keyup', function (e) {
            // Mendeteksi tombol Print Screen
            if (e.key === 'PrintScreen') {
                alert('Fungsi screenshot dinonaktifkan.');
                // Mengosongkan clipboard (ini mungkin hanya berfungsi pada beberapa browser)
                navigator.clipboard.writeText('');
            }
        });

        // Menghapus konten clipboard saat halaman dimuat ulang
        window.addEventListener('focus', function () {
            navigator.clipboard.writeText('');
        });
    </script>
</body>
</html>
