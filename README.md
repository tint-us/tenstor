
# TenStor — Tenable.sc Publishing Receiver

---

**ID (Bahasa Indonesia)**

**TenStor** adalah webservice kecil (PHP) untuk menerima **Publish Report** dari **Tenable.sc** via **HTTP POST multipart/form-data**.
File PDF yang diterima akan:
1) Disimpan ke direktori upload (volume),
2) Diparsing halaman pertamanya untuk membaca pola teks `(Scan: ...)`,
3) Di-rename otomatis menjadi `YY-MM-DD-<hasil-scan>.pdf`.

> Secara default berjalan sebagai container Docker di port **8085**.

## Fitur
- Basic Auth (username/password) lewat environment variable
- Rename otomatis berdasarkan isi PDF (regex `(Scan:\s*(...))`)
- Logging ke file (volume) dan ke stdout container
- Batas upload & tuning via `php.ini`
- Healthcheck endpoint sederhana (`GET /?health=1`)

## Arsitektur Singkat
- Image dasar: `php:8.3-apache` + `smalot/pdfparser`
- Endpoint: `POST /` field **reportContent** (multipart)
- Volume:
  - `/tenable/hasil` → hasil upload/rename
  - `/var/log` → `report-tenable.log`

## Quick Start

### 1) Prasyarat
- Docker + Docker Compose
- Port 8085 terbuka dan belum terpakai di IP Address server yang akan digunakan

### 2) Clone & Konfigurasi
```bash
git clone https://github.com/tint-us/tenstor.git
cd tenstor
cp .env.example .env
# Edit .env → TENSTOR_USER / TENSTOR_PASSWORD
```

### 3) Jalankan
```bash
docker compose up -d --build
```

### 4) Tes Manual
```bash
curl -u "$TENSTOR_USER:$TENSTOR_PASSWORD" -F "reportContent=@/path/to/report.pdf" http://YOUR_HOST:8085/
```

### 5) Konfigurasi di Tenable.sc
1. **System → Publishing Sites → New**
2. **Type**: HTTP Post
3. **URI**: `http://YOUR_HOST:8085/`
4. **Authentication**: Password (isi Username/Password yang sama dengan `.env`)
5. **Publish** melalui **Reporting → Report Results → Publish**

## Konfigurasi

`.env`:
```env
TENSTOR_USER=changeme
TENSTOR_PASSWORD=changeme
TENSTOR_UPLOAD_DIR=/tenable/hasil
TENSTOR_LOG_FILE=/var/log/report-tenable.log
PHP_TZ=Asia/Jakarta
```

`docker/php.ini` (default):
```ini
file_uploads = On
upload_max_filesize = 100M
post_max_size = 110M
memory_limit = 256M
max_execution_time = 120
date.timezone = Asia/Jakarta
```

## Perilaku Penamaan File
- Regex: `\(Scan:\s*(.*?)\)` dibaca dari halaman pertama PDF.
- Nama akhir: `YY-MM-DD-<hasil>.pdf`.
- Jika tidak ditemukan pola → file tetap diupload (log dicatat), tidak di-rename.

## Keamanan & Best Practice
- Batasi akses port 8085 ke IP Tenable.sc (firewall / security group).
- Gunakan HTTPS via reverse proxy (opsional).
- Rotasi log di volume `data/log/`.

## Troubleshooting
- **401 Unauthorized**: Cred tidak dikirim → pastikan Tenable.sc memakai Authentication: Password.
- **403 Forbidden**: Username/Password salah → samakan dengan `.env` di TenStor.
- **400 Invalid upload**: Field form harus bernama `reportContent`.
- **Rename failed**: Cek permission volume atau nama file tabrakan.
- **Pattern not found**: Pastikan halaman pertama PDF mengandung teks `(Scan: ...)`.

## Lisensi

Hak Cipta (c) 2025 - Tintus

PERIZINAN
- Penggunaan: Anda diizinkan menggunakan Perangkat Lunak, termasuk untuk tujuan pribadi maupun komersial, tanpa biaya.
- Modifikasi & Distribusi: Anda diizinkan memodifikasi dan mendistribusikan Perangkat Lunak dan Turunannya.
- Hosting/SaaS: Anda diizinkan menyediakan Perangkat Lunak sebagai layanan (hosting/SaaS).

KETENTUAN
- Larangan Penjualan (No Resale): Anda TIDAK boleh Menjual Perangkat Lunak atau Turunannya — baik dalam bentuk kode sumber maupun biner/eksekutabel — kecuali Anda memperoleh Lisensi Komersial dari Pemberi Lisensi.
- Atribusi: Saat mendistribusikan Perangkat Lunak atau Turunannya, Anda wajib:
  (a) mempertahankan pemberitahuan hak cipta, pemberitahuan lisensi ini, dan
  (b) menambahkan catatan perubahan yang Anda lakukan, serta
  (c) menyertakan URL ke repositori sumber asli.
- Merek Dagang: Lisensi ini tidak memberikan hak atas nama, logo, atau merek Pemberi Lisensi.

PENAFIAN JAMINAN
PERANGKAT LUNAK DISEDIAKAN "SEBAGAIMANA ADANYA" TANPA JAMINAN APA PUN, BAIK TERSURAT MAUPUN TERSIRAT, TERMASUK NAMUN TIDAK TERBATAS PADA JAMINAN KELAYAKAN DIPERJUALBELIKAN, KESESUAIAN UNTUK TUJUAN TERTENTU, DAN NON-PELANGGARAN.

PEMBATASAN TANGGUNG JAWAB
SEJAUH DIIZINKAN OLEH HUKUM YANG BERLAKU, PEMBERI LISENSI TIDAK BERTANGGUNG JAWAB ATAS KERUSAKAN APA PUN YANG TIMBUL DARI ATAU TERKAIT DENGAN PENGGUNAAN PERANGKAT LUNAK, BAIK SECARA KONTRAK, PERBUATAN MELAWAN HUKUM, MAUPUN DASAR HUKUM LAINNYA.

(LANGKA) LISENSI PATEN (OPSIONAL)
Pemberi Lisensi memberikan lisensi paten non-eksklusif, bebas royalti, untuk membuat, menggunakan, dan mendistribusikan Perangkat Lunak. Kontributor, bila ada, memberikan lisensi paten yang sama untuk kontribusi mereka. Jika Anda tidak ingin klausul ini berlaku, hapus bagian “Lisensi Paten (Opsional)” ini.

DEFINISI
- “Perangkat Lunak” berarti materi berhak cipta yang disediakan berdasarkan lisensi ini.
- “Turunan” berarti karya yang didasarkan pada atau menggabungkan Perangkat Lunak, termasuk modifikasi, adaptasi, perbaikan, atau karya turunan lainnya.
- “Menjual”/“Penjualan” berarti menyediakan salinan Perangkat Lunak atau Turunannya, atau akses ke salinan tersebut, dengan imbalan biaya atau kompensasi. PENJELASAN: Penyediaan Hosting/SaaS DIPERBOLEHKAN dan tidak dianggap sebagai “Penjualan” selama Anda tidak menjual salinan Perangkat Lunak atau Turunannya.
- “Lisensi Komersial” berarti perjanjian terpisah dari Pemberi Lisensi yang mengizinkan Penjualan.

Untuk kebutuhan komersial, yuk kita ngobrol via: tintus.ardi@gmail.com.

---
