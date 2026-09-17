# SDK dan Rencana Evaluasi — Absensi Wajah Driver

Pendamping [PRD](PRD.md) dan [rancangan teknis](technical-design.md). Referensi upstream diperiksa pada 17 September 2026. Dokumen ini menyiapkan pilihan library, artifact, kontrak integrasi, dan kriteria evaluasi. Tidak ada SDK yang sudah diinstal, benchmark yang sudah dijalankan, atau model yang sudah disahkan untuk produksi dalam pekerjaan dokumentasi ini.

## 1. Pilihan yang direkomendasikan

Gunakan **Laravel existing + API Python privat + DeepFace sebagai adapter pilot**, dengan kandidat **SFace untuk identitas**, **YuNet untuk deteksi wajah**, serta **MiniFASNet untuk evaluasi anti-spoofing**. Semua pemrosesan dilakukan di infrastruktur perusahaan; tidak memanggil managed cloud API secara default.

DeepFace merupakan wrapper yang mendukung beberapa model, bukan satu model tunggal. Pemilihan harus eksplisit; jangan memakai default model tanpa audit. Pencocokan hanya 1:1 dengan template driver yang login. [Dokumentasi DeepFace](https://github.com/serengil/deepface)

Konfigurasi pilot konseptual:

```yaml
pipeline_id: driver-face-pilot-v1
engine: deepface
recognition_model: SFace
detector_backend: yunet
distance_metric: cosine
enforce_detection: true
align: true
anti_spoofing: true
production_enabled: false
```

Ini spesifikasi konfigurasi, bukan file runtime siap deploy. Ambang, dependency lock, artifact hash, dan versi pipeline final harus dihasilkan oleh pilot. `anti_spoofing: true` adalah permintaan pemeriksaan, bukan bukti liveness production-grade.

### Mengapa kombinasi ini

- Arsitektur tetap cocok dengan PHP/Laravel: API dipanggil dari adapter service; Python menangani model yang memiliki dependency berbeda.
- Adapter SFace DeepFace merujuk bobot OpenCV Zoo, dengan input model 112×112 dan output embedding 128 dimensi. Foto input pengguna tetap lebih besar; preprocessing model tidak menentukan ukuran arsip. [Implementasi SFace](https://github.com/serengil/deepface/blob/master/deepface/models/facial_recognition/SFace.py)
- Adapter YuNet merujuk model OpenCV Zoo dan membutuhkan OpenCV yang kompatibel. [Implementasi YuNet](https://github.com/serengil/deepface/blob/master/deepface/models/face_detection/YuNet.py)
- Direktori model SFace memuat lisensi Apache-2.0, sedangkan YuNet memuat MIT. Ini dasar pemilihan artifact untuk audit, bukan klaim bahwa semua distribusi model lain memiliki izin sama. [Lisensi SFace](https://github.com/opencv/opencv_zoo/blob/main/models/face_recognition_sface/LICENSE), [lisensi YuNet](https://github.com/opencv/opencv_zoo/blob/main/models/face_detection_yunet/LICENSE)

Jika wrapper DeepFace terbukti terlalu berat dalam benchmark, kandidat alternatif untuk identitas adalah OpenCV SFace/YuNet langsung di balik kontrak API yang sama. Perubahan preprocessing/engine tetap menghasilkan versi pipeline baru dan uji kompatibilitas template; jangan menganggap embedding otomatis dapat dipertukarkan.

## 2. Dependency dan artifact yang harus disiapkan

| Komponen | Kandidat | Status/penggunaan |
|---|---|---|
| Web UI | Livewire, Alpine, UI module, Flux existing | Tidak menambah React atau framework UI baru |
| Kamera/lokasi | `getUserMedia`, Canvas/Blob, Geolocation API | Native browser API; HTTPS dan izin pengguna |
| Laravel client | Laravel HTTP client existing | Internal API adapter; timeout, auth dan schema validation |
| Python API | `fastapi`, `uvicorn`, `python-multipart` | Endpoint multipart privat dan readiness |
| Face engine | `deepface` | Pilot dengan model eksplisit |
| Image/model runtime | OpenCV, NumPy, backend runtime yang diminta versi DeepFace terpilih | Resolve/pin satu kombinasi yang benar-benar diuji |
| Detector weights | `face_detection_yunet_2023mar.onnx` dari OpenCV Zoo | Kandidat; pin commit/hash/source notice |
| Recognizer weights | `face_recognition_sface_2021dec.onnx` dari OpenCV Zoo | Kandidat; pin commit/hash/source notice |
| Anti-spoof weights | `2.7_80x80_MiniFASNetV2.h5`, `4_0_0_80x80_MiniFASNetV1SE.h5` dari distribusi yang dipakai adapter DeepFace | Kandidat; provenance/license dan kemampuan harus diuji |
| Storage | `minio_private` + Flysystem S3 existing | File bukti privat; DB hanya metadata/template terenkripsi |
| Unit/contract tests | Pest existing; `pytest`/HTTP test client pada service Python | CI terpisah dari test model berbobot besar |

FastAPI mendukung file upload multipart dan deployment container. Gunakan image layanan sendiri yang dipin, bukan menganggap contoh dependency di dokumen sebagai lockfile. [Upload FastAPI](https://fastapi.tiangolo.com/tutorial/request-files/), [deployment FastAPI](https://fastapi.tiangolo.com/deployment/docker/)

Build hanya memasang satu keluarga wheel OpenCV karena package-package tersebut berbagi namespace `cv2`. Kandidat server adalah headless/contrib yang kompatibel dengan fitur model; audit dependency transitif DeepFace agar tidak memasang beberapa wheel yang bertabrakan. [Panduan package OpenCV](https://pypi.org/project/opencv-contrib-python-headless/)

Saat implementasi pilot, buat:

```text
services/face-verification/
  pyproject.toml                 # deklarasi dependency
  <lockfile>                     # exact versions dan hashes dari resolver teruji
  Dockerfile                     # atau runbook service bare-metal
  app/api.py
  app/pipeline.py
  app/quality.py
  app/contracts.py
  tests/
  models/manifest.json           # ID artifact, upstream commit, SHA-256, lisensi
  THIRD_PARTY_NOTICES.md
```

Jangan menaruh foto/embedding produksi atau bobot besar di Git. Ambil artifact yang telah diaudit saat build/provisioning, verifikasi checksum, lalu mount read-only. Readiness gagal bila model hilang atau hash tidak cocok. Jangan melakukan auto-download bobot saat driver sedang absen.

Versi Python, DeepFace, backend runtime, OpenCV dan base image harus dipilih bersama setelah smoke test pada target CPU/OS. Dokumen ini sengaja tidak memberikan angka versi exact yang belum diuji atau `pip install latest` sebagai instruksi produksi.

## 3. Batas liveness dan keputusan produksi

Adapter anti-spoof DeepFace menggunakan dua model MiniFASNet. Skor yang dikembalikan perlu dibaca bersama kelas hasilnya; jangan menafsirkan skor tinggi untuk kelas spoof sebagai confidence orang hidup. [Implementasi FasNet](https://github.com/serengil/deepface/blob/master/deepface/models/spoofing/FasNet.py)

Silent-Face-Anti-Spoofing memakai pemeriksaan visual RGB. Pengembangnya menyatakan hasil bergantung pada kamera dan kondisi adegan. Lisensi repository Apache-2.0 tidak otomatis menyelesaikan provenance setiap bobot yang didistribusikan ulang oleh pihak lain. [Penjelasan upstream](https://github.com/minivision-ai/Silent-Face-Anti-Spoofing/blob/master/README_EN.md), [lisensi upstream](https://github.com/minivision-ai/Silent-Face-Anti-Spoofing/blob/master/LICENSE)

Aturan desain:

- Face matching, quality, dan liveness adalah hasil terpisah. Verifikasi lolos hanya bila semuanya memenuhi kebijakan.
- Evaluasi anti-spoofing pada beberapa frame dapat menjadi baseline pilot, tetapi tidak membuktikan capture temporally live atau tahan replay.
- Instruksi kedip/menoleh atau landmark di browser saja bukan pengaman liveness yang memadai. Jangan membuat sendiri challenge gerakan lalu mengklaim setara SDK yang diuji.
- Challenge nonce mengikat sesi/protokol dan mengurangi reuse request; browser yang dimodifikasi masih dapat menyuntikkan kamera/koordinat palsu. Server harus memeriksa bukti, bukan boolean JavaScript.
- HR dapat menilai foto dan konteks, tetapi satu foto tidak memungkinkan HR membuktikan liveness. Human review bukan pengganti anti-spoofing.
- Jika serangan yang termasuk threat model lolos atau tingkat penolakan pengguna asli terlalu tinggi, fitur produksi tidak diluncurkan dengan kandidat itu. Ganti adapter liveness dengan SDK khusus yang mendukung web/on-prem dan bukti evaluasi relevan.

Kriteria pemilihan SDK pengganti: tidak mengirim bukti ke cloud tanpa keputusan baru, dukungan Safari iOS/Chrome Android, dokumentasi capture/session binding, output reference image yang berasal dari sesi sama, kemampuan menghadapi replay/injection sesuai threat model, bukti pengujian PAD independen yang relevan dengan versi SDK, deployment/support/lisensi jelas. Tidak ada vendor yang dinyatakan memenuhi seluruhnya tanpa evaluasi.

InsightFace tidak dipilih sebagai default karena bobot pretrained publik memiliki pembatasan noncommercial research meskipun kode library berlisensi MIT. [Kebijakan InsightFace](https://github.com/deepinsight/insightface#license)

## 4. Kualitas gambar dan bukti

Browser memberikan panduan ringan; server memeriksa ulang semua kriteria:

1. File benar-benar image yang didukung, decoded dimension terbatas, tidak rusak.
2. Tepat satu wajah; ukuran wajah memadai relatif terhadap frame.
3. Ketajaman/cahaya/pose/occlusion dalam batas hasil uji. Jangan menolak otomatis berdasarkan warna kulit atau mengasumsikan satu ambang brightness bekerja untuk semua kondisi.
4. Frame berasal dari satu capture, urutan input konsisten, tidak sekadar semua frame identik.
5. Matching memakai template driver yang dipilih oleh Laravel, bukan employee/template ID bebas dari browser.
6. Reference frame yang diarsipkan benar-benar termasuk frame yang diverifikasi; catat hash input dan hash arsip setelah normalisasi.

Pilot input dapat berupa beberapa JPEG yang masih menyertakan konteks sekitar wajah; jangan hanya mengirim crop 112×112, karena model anti-spoof dapat memerlukan area sekitar wajah. Detail crop/preprocessing mengikuti model yang dipin.

Arsip JPEG target 80–150 KB, sisi panjang 640–960 px. Batas 300 KB boleh menjadi target arsip, bukan otomatis batas seluruh input liveness. Bila SDK memakai video, ukuran/durasi/format input harus mengikuti protokol SDK dan diuji pada Safari; tidak dapat diganti sembarang dengan foto kecil. Buang EXIF dan input sementara sesuai retensi setelah keputusan/perselisihan memungkinkan.

## 5. Kontrak API internal yang diusulkan

Python tidak mendapat akses tulis Oracle atau permission HR. Laravel menentukan identitas, template, kebijakan, waktu bisnis, keputusan dan penyimpanan. Endpoint hanya bisa diakses Laravel/worker melalui authenticated private network; TLS/mTLS atau service credential yang dikelola, request size limits dan rate/concurrency limits.

| Endpoint | Fungsi |
|---|---|
| `GET /health/live` | Proses berjalan |
| `GET /health/ready` | Model terverifikasi hash dan sudah dimuat; respons minimal untuk jaringan internal |
| `POST /v1/enroll` | Quality+liveness → candidate template dan reference-frame ID; tidak mengaktifkan identitas |
| `POST /v1/verify` | Quality+liveness+matching terhadap template yang disediakan Laravel |

Request multipart berisi media binary dan metadata terstruktur: `request_id`, `capture_id`, `purpose`, `payload_hash`, `policy_version`, `pipeline_version`, daftar hash frame, serta template/model metadata dari Laravel untuk verify. Service menghitung ulang hashes, memastikan pipeline/model cocok, membatasi file count/dimensi dan menolak URL/path input arbitrary agar tidak membuka SSRF/local-file read.

Contoh bentuk respons (angka hanya ilustrasi schema, bukan threshold produksi):

```json
{
  "request_id": "request-reference",
  "capture_id": "capture-reference",
  "payload_hash": "sha256-of-canonical-manifest",
  "pipeline_version": "driver-face-pilot-v1",
  "policy_version": "pilot-policy-v1",
  "quality": {"passed": true, "face_count": 1, "reason_codes": []},
  "liveness": {
    "passed": true,
    "predicted_class": "live",
    "score": 0.97,
    "model_id": "audited-fas-artifact-id"
  },
  "identity": {
    "matched": true,
    "metric": "cosine_distance",
    "distance": 0.12,
    "threshold": 0.25,
    "model_id": "audited-sface-artifact-id"
  },
  "reference_frame_id": "frame-2",
  "reason_codes": [],
  "processed_at": "2026-09-17T03:00:02Z"
}
```

Semua skor/threshold disimpan bersama metric dan versi; distance kecil dapat berarti lebih cocok. Jangan menampilkan similarity sebagai persentase pasti tanpa kalibrasi. Enrollment tidak mengembalikan `identity.matched=true` karena identitas awal masih diverifikasi HR; field identity null dan candidate template hanya kembali lewat channel internal.

API transport success tidak berarti pemeriksaan lolos. Bedakan hasil pemeriksaan negatif dari timeout, model unavailable, malformed image dan service error. Laravel mengecek request/capture/hash/version/reference frame sebelum menerima hasil. Retry inference memakai input yang sama; finalisasi bisnis tetap hanya sekali. HTTP timeout tidak membuat worker menerima default `passed=true`.

## 6. Rencana benchmark dan keputusan go/no-go

Sebelum pengujian, tetapkan threat model, dataset berizin, perangkat, jumlah percobaan, target dan interval keyakinan. Usulan pilot awal 20–30 driver dengan beberapa perangkat/cahaya hanya menguji pengalaman operasional; belum cukup untuk mengklaim tingkat false acceptance yang sangat kecil.

| Kelompok uji | Kasus | Hasil yang dicatat |
|---|---|---|
| Pengguna asli | Pagi/malam, outdoor/indoor, kacamata, perubahan pose, variasi perangkat | False rejection, retry rate, waktu selesai |
| Identitas salah | Pasangan orang berbeda, bukan hanya wajah yang sangat berbeda | False acceptance pada threshold yang dipilih |
| Presentation attack | Foto cetak, foto layar, replay video, crop/ukuran/cahaya berbeda | Attack acceptance per jenis serangan |
| Injection/replay | Frame lama, nonce lama, capture ID tertukar, camera virtual bila didukung | Penolakan protokol vs keterbatasan model dibedakan |
| Infrastruktur | CPU target, peak concurrency, warm/cold start, OOM/timeout | p50/p95/p99 latency, RAM/CPU, queue backlog |
| Browser | Chrome Android, Safari iOS, desktop target | Izin, camera lifecycle, orientation, upload/retry |
| Lokasi | Titik valid, batas radius, akurasi buruk, stale sample, spoofed input | Hasil kebijakan, false rejection dan risiko tersisa |

Gunakan data kalibrasi terpisah dari evaluasi. Catat confusion matrix, ukuran sampel dan ketidakpastian; jangan menyalin angka akurasi README sebagai hasil perusahaan. Tidak ada klaim 100% anti-spoofing walaupun seluruh skenario pilot lulus.

Usulan target operasional: respons setelah upload p95 <=5 detik pada beban yang disepakati, tanpa menghambat ERP; target ini belum terukur. Ambang FAR/FRR/PAD ditetapkan sebelum uji, bukan dipilih setelah melihat hasil supaya terlihat lulus. Bila kebutuhan keamanan tidak terbukti, pilih SDK liveness lain atau batasi rollout; HR review saja tidak otomatis memenuhi acceptance keamanan.

## 7. Batas keamanan browser dan deployment

Kamera browser memerlukan secure context dan izin pengguna. Kamera yang tersedia tidak otomatis membuktikan input berasal dari sensor fisik. [Dokumentasi getUserMedia](https://developer.mozilla.org/en-US/docs/Web/API/MediaDevices/getUserMedia)

Geolocation browser menyediakan posisi/accuracy/timestamp yang dilaporkan, bukan attestation lokasi anti-fake-GPS. Penilaian ini berarti geofence web harus diposisikan sebagai pemeriksaan atas laporan lokasi, dengan risiko manipulasi tersisa. [Spesifikasi Geolocation](https://www.w3.org/TR/geolocation/)

Pisahkan worker verification dari queue ERP umum, muat model satu kali per worker, ukur memory per worker sebelum menambah concurrency, dan matikan inference baru bila readiness gagal. Frontend hanya menangkap bukti; template/skor keputusan tidak dikirim ke browser. Gunakan temporary private evidence paths; jangan menyimpan data produksi dalam container image, cache model, exception stack, atau access log.

## 8. Hasil yang harus tersedia sebelum instalasi produksi

- Dependency lock dan base image digest yang lulus CI/smoke test.
- Manifest bobot dengan asal, commit/release, SHA-256, lisensi dan notices; audit distribusi bobot yang benar-benar dipakai, bukan hanya nama algoritme.
- Pipeline/preprocessing/threshold policy version beserta laporan benchmark.
- Uji enrollment, pergantian model/template, liveness, cross-browser dan kegagalan layanan.
- Keputusan risiko lokasi/kamera web, parameter retensi, kapasitas dan runbook deployment.
- Kontrak Laravel/Python yang diuji dengan fake pada CI dan model nyata pada environment evaluasi.

Default sampai semua persyaratan terpenuhi: feature flag false, engine fake hanya di test, tidak ada auto-approval maupun fallback verifikasi yang selalu berhasil.
