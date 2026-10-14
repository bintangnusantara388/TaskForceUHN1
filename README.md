# ERP Inventarisasi Data Pendukung Branding & Reputasi Institusi
**Universitas Harkat Negeri — Tim Task Force Kerjasama Internasional & Reputasi Institusi**

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Database](https://img.shields.io/badge/Database-SQLite3-003B57?logo=sqlite&logoColor=white)](https://www.sqlite.org/)
[![UI](https://img.shields.io/badge/Styling-Tailwind_CSS-38B2AC?logo=tailwind-css&logoColor=white)](https://tailwindcss.com/)
[![AI Integration](https://img.shields.io/badge/AI-Google_Gemini_API-4285F4?logo=google&logoColor=white)](https://aistudio.google.com/)
[![Ranking Ready](https://img.shields.io/badge/Ranking-WURI%20%7C%20Webometrics%20%7C%20Uniranks-F59E0B)](#-pusat-ekspor-borang--laporan-pemeringkatan)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

Platform terintegrasi untuk menghimpun, menyaring (*screening*), mengkurasi, dan mengekspor data capaian tridarma institusi dari 4 unit strategis (*PPM*, *PSDM*, *Kemahasiswaan*, dan *Alumni Karir*) menjadi materi publikasi media dan bukti evidensi pemeringkatan internasional (*WURI*, *Webometrics*, dan *Uniranks*).

---

## 📑 Daftar Isi

- [Fitur Utama & Keunggulan](#-fitur-utama--keunggulan)
- [Prasyarat Sistem](#-prasyarat-sistem)
- [Panduan Instalasi & Setup Lokal](#-panduan-instalasi--setup-lokal)
- [Konfigurasi Environment Variable](#-konfigurasi-environment-variable)
- [Akun Demo & Simulasi Peran (RBAC)](#-akun-demo--simulasi-peran-rbac)
- [Struktur Direktori Proyek](#-struktur-direktori-proyek)
- [Pusat Ekspor Borang & Laporan Pemeringkatan](#-pusat-ekspor-borang--laporan-pemeringkatan)
- [Panduan Deployment ke Server Produksi](#-panduan-deployment-ke-server-produksi)
- [Keamanan & Privasi Data](#-keamanan--privasi-data)
- [Lisensi](#-lisensi)

---

## 🌟 Fitur Utama & Keunggulan

### 1. Dashboard "Progress Meter" Real-time
- **Target Bulanan Wajib**: Memantau keterpenuhan kuota minimal **1 data Signature (unggulan) per bulan** per unit pelapor, dilengkapi badge status visual interaktif `[✓ TERPENUHI]` atau `[✗ BELUM SETOR]`.
- **Statistik & Metrik Agregat**: Menghitung total data masuk, antrian kurasi komunikasi (*Siap Diolah*), serta berita yang *Sudah Terbit* dengan tautan publikasi aktif.
- **Dukungan Multi-Periode Dinamis**: Mencakup periode pelaporan tahun akademik penuh (September 2026 s.d. Agustus 2027).

### 2. Formulir Terstandarisasi 4 Modul Penghasil Reputasi
- **Modul 1 — PPM (Pusat Penelitian & Pengabdian Masyarakat)**:
  - Pencatatan Kekayaan Intelektual (Paten, Paten Sederhana, Hak Cipta, Merek, Desain Industri).
  - Status registrasi KI (*Granted*, Terdaftar, Dalam Proses) dan nomor permohonan resmi DJKI.
  - Tabel multi-inventor dinamis (*Dosen / Mahasiswa*) dengan validasi format NIDN / NIM numerik.
- **Modul 2 — PSDM (Pengembangan Sumber Daya Manusia)**:
  - Prestasi pegawai Dosen dan Tenaga Kependidikan.
  - Sertifikasi kompetensi internasional (misal: PMP, Cisco, AWS), narasumber forum global (UNESCO, WHO), jabatan fungsional (Guru Besar), dan penghargaan LLDIKTI / Kementerian.
- **Modul 3 — Kemahasiswaan**:
  - Prestasi kejuaraan mahasiswa di bidang akademik maupun non-akademik.
  - Peringkat capaian (*Juara 1 / Medali Emas*, dsb.), level kejuaraan (*Internasional / Nasional / Wilayah*), dan dosen pembimbing tim.
- **Modul 4 — Alumni & Karir**:
  - Rekam jejak alumni di kancah internasional (perusahaan Fortune 500, Google Zurich), wirausaha berdampak (*startup founders* bervaluasi tinggi), dan studi lanjut program beasiswa bergengsi (Oxford, LPDP, Chevening).

### 3. Teknologi AI Generator Deskripsi & Siaran Pers
- **Hybrid AI Architecture**:
  - **Online**: Didukung model canggih **Google Gemini 1.5 Flash / Pro** via Gemini API resmi.
  - **Offline Fallback**: Engine lokal cerdas (*UHN Neural Synthesizer*) yang otomatis aktif jika koneksi internet terputus atau API Key belum disetel, menjamin ketersediaan sistem 100%.
- **Otomasi Narasi 3 Kalimat Bahasa Awam**:
  - Menerjemahkan istilah teknis paten atau jurnal rumit menjadi 3 kalimat padat, komunikatif, dan menarik bagi publik dan media massa.
  - Pilihan gaya bahasa (*Tone*): Jurnalistik Berita, Edukatif Inspiratif, Populer Santai, atau Ringkas Padat.
- **Panel Konfigurasi AI untuk Administrator**:
  - Manajemen API Key mandiri melalui antarmuka web yang aman.
  - Pengaturan model AI, slider *temperature*, batas *token output*, dan kustomisasi *System Instruction*.
  - Tombol uji koneksi real-time dengan informasi latensi dan respon model.

### 4. Engine Notifikasi Pengingat Target Signature
- **Direct Messaging**: Notifikasi pengingat dikirimkan langsung dari Administrator Task Force ke PIC masing-masing unit via:
  - 📧 **Surel Resmi (Email)**
  - 💬 **WhatsApp Langsung** (tautan *click-to-chat* dengan pesan terpersonalisasi otomatis)
- **Penjadwalan 2 Fase Otomatis**:
  - **Mid-Month Reminder (Tanggal 15)**: Pengingat awal pertengahan bulan.
  - **End-Month Warning (Tanggal 26)**: Peringatan akhir bulan menjelang penutupan buku.
- **Smart Targeting**: Pengingat hanya dikirimkan kepada unit yang status Signature-nya masih `[✗ BELUM SETOR]`.

### 5. Meja Kerja Kurasi Bagian Komunikasi
- Tabulasi antrian data yang telah lolos *screening* (*Siap Diolah*).
- **Banner Peringatan Privasi Otomatis (UU PDP No. 27/2022)** jika responden menyatakan `Persetujuan Publikasi Nama: TIDAK`.
- Pembaruan status alur publikasi (*Belum Diolah* $\rightarrow$ *Sedang Diolah* $\rightarrow$ *Sudah Terbit*) dilengkapi rekaman URL berita rilis.

---

## 💻 Prasyarat Sistem

Aplikasi dibangun menggunakan prinsip arsitektur *Zero-Dependency PHP*, berjalan mandiri tanpa memerlukan manajer dependensi pihak ketiga (*composer*) atau instalasi node.js pada lingkungan produksi.

| Komponen | Persyaratan Minimal | Rekomendasi |
| :--- | :--- | :--- |
| **PHP** | PHP 8.2 | PHP 8.2, 8.3, 8.4, atau 8.5 |
| **Database** | SQLite 3 (terpasang built-in di PHP) | `ext-pdo_sqlite` aktif |
| **Ekstensi PHP Wajib** | `pdo_sqlite`, `curl`, `mbstring`, `json`, `filter` | Aktif pada `php.ini` |
| **Web Server** | PHP Built-in Server (Development) | Nginx / Apache 2.4 (Production) |
| **Peramban Web** | Chrome, Edge, Firefox, Safari modern | Resolusi layar desktop / tablet / ponsel |
| **Mobile SDK (Opsional)** | Flutter SDK 3.x | Diperlukan jika ingin mem-build folder `mobile_app/` |

---

## 🚀 Panduan Instalasi & Setup Lokal

Ikuti langkah-langkah berikut untuk menjalankan aplikasi di lingkungan komputer lokal Anda:

### Langkah 1: Unduh / Kloning Repositori
```bash
git clone https://github.com/username-anda/kordinasi-taskforce.git
cd kordinasi-taskforce
```

### Langkah 2: Salin Berkas Environment
Salin berkas konfigurasi template `.env.example` menjadi `.env`:

**Pada Windows (PowerShell):**
```powershell
Copy-Item .env.example .env
```

**Pada Linux / macOS:**
```bash
cp .env.example .env
```

### Langkah 3: Konfigurasi Environment (Opsional)
Buka berkas `.env` dengan editor teks favorit Anda. Secara default, aplikasi langsung siap berjalan dengan SQLite lokal.

Bila Anda ingin mengaktifkan generator AI daring Google Gemini:
1. Dapatkan API Key gratis di [Google AI Studio](https://aistudio.google.com/).
2. Masukkan ke baris `GEMINI_API_KEY`:
   ```dotenv
   GEMINI_API_KEY="AIzaSyYourGeminiApiKeyHere"
   ```
*(Catatan: Anda juga dapat memasukkan API Key ini langsung melalui menu web Admin $\rightarrow$ Konfigurasi AI).*

### Langkah 4: Jalankan Server Pengembangan
Jalankan server bawaan PHP dari direktori utama proyek:

```bash
php -S localhost:8088 -t public
```

### Langkah 5: Buka Aplikasi di Browser
Buka peramban (*web browser*) Anda dan akses:
👉 **[http://localhost:8088](http://localhost:8088)**

> [!NOTE]
> **Inisialisasi Database Otomatis**: Saat pertama kali dibuka, sistem akan otomatis membuat berkas basis data `database/app.sqlite`, mengkompilasi seluruh tabel relasional, serta mengisi 12 data contoh tridarma dan akun pengguna awal. Anda tidak perlu mengeksekusi import SQL secara manual!

---

## ⚙️ Konfigurasi Environment Variable

Berikut adalah rincian variabel lingkungan yang didukung pada berkas `.env`:

| Variabel | Tipe | Nilai Default | Penjelasan |
| :--- | :---: | :--- | :--- |
| `APP_NAME` | String | `"ERP Inventarisasi Reputasi Institusi"` | Nama resmi sistem aplikasi |
| `APP_ENV` | String | `local` | Lingkungan aplikasi (`local`, `testing`, `production`) |
| `APP_DEBUG` | Boolean | `true` | Mode penelusuran kesalahan (*debug error*) |
| `APP_URL` | String | `http://localhost:8088` | URL dasar aplikasi untuk pembuatan tautan publikasi |
| `APP_INSTITUTION` | String | `"Universitas Harkat Negeri"` | Nama institusi perguruan tinggi |
| `APP_TASKFORCE` | String | `"Tim Task Force Kerjasama..."` | Nama unit kerja task force |
| `DB_CONNECTION` | String | `sqlite` | Driver basis data |
| `DB_PATH` | String | `database/app.sqlite` | Lokasi berkas SQLite |
| `UPLOAD_DIR` | String | `public/uploads` | Folder penyimpanan lampiran sertifikat/bukti |
| `MAX_UPLOAD_SIZE` | Integer | `10485760` | Batas maksimum berkas unggahan (10 MB dalam bytes) |
| `GEMINI_API_KEY` | String | `""` | Kunci API Google Gemini (kosong = engine lokal aktif) |
| `SESSION_LIFETIME` | Integer | `7200` | Masa berlaku sesi login pengguna (dalam detik) |

---

## 👥 Akun Demo & Simulasi Peran (RBAC)

Aplikasi menerapkan kontrol akses berbasis peran (*Role-Based Access Control*). Untuk mempermudah pengujian, Anda dapat masuk menggunakan akun-akun demo berikut dengan kata sandi bawaan: **`harkat123`**, atau menggunakan bilah pintasan **Role Switcher** di bagian atas layar:

| Username | Peran (*Role*) | Unit Kerja | Tanggung Jawab & Hak Akses |
| :--- | :--- | :--- | :--- |
| `admin_tf` | **ADMIN_TASK_FORCE** | Task Force | Akses penuh seluruh unit, konfigurasi AI, monitoring target, manajemen pengguna, audit trail, dan cadangan database. |
| `pic_ppm` | **PIC_UNIT** | PPM | Menginput, mengedit, dan melakukan *screening* data riset, paten, dan hak cipta. |
| `pic_psdm` | **PIC_UNIT** | PSDM | Menginput dan mengkurasi capaian sertifikasi pegawai, narasumber global, dan dosen berprestasi. |
| `pic_mhs` | **PIC_UNIT** | Kemahasiswaan | Menginput capaian kejuaraan kompetisi mahasiswa dan data dosen pembimbing. |
| `pic_alumni` | **PIC_UNIT** | Alumni & Karir | Menginput kiprah wirausaha berdampak dan karir global para alumni universitas. |
| `komunikasi_staff` | **KOMUNIKASI** | Bagian Komunikasi | Memverifikasi data lolos *screening*, menyusun narasi rilis pers, dan menyematkan tautan publikasi. |
| `rektor_viewer` | **VIEWER** | Task Force / Pimpinan | Memantau dasbor eksekutif dan ketercapaian target indikator dengan proteksi sensor data PII. |

---

## 📂 Struktur Direktori Proyek

```
kordinasi-taskforce/
├── .env.example                       # Template konfigurasi environment
├── .gitignore                          # Filter file sensitif, database & cache Git
├── README.md                           # Dokumentasi panduan operasional proyek
├── app/                                # Inti Backend Aplikasi (MVC-Light)
│   ├── Auth.php                        # Manajemen sesi, login, hashing, & RBAC
│   ├── Config.php                      # Loader environment variable (.env) & konstanta
│   ├── Database.php                    # Inisialisasi basis data SQLite & auto-seeder
│   ├── Helpers.php                     # Utilitas sensor PII, format tanggal, & counter kalimat
│   ├── Models/
│   │   ├── Audit.php                   # Pencatatan riwayat aktivitas pengguna (Audit Trail)
│   │   ├── Entry.php                   # Manipulasi data 4 modul & relasi multi-inventor
│   │   └── Reminder.php                # Engine Progress Meter & logika pengingat tgl 15/26
│   └── Services/
│       └── AIService.php               # Integrasi Google Gemini API & UHN Neural Synthesizer
├── database/                           # Berkas & Skrip Basis Data
│   ├── schema.sql                      # DDL Skema Relasional universal (Postgres/MySQL compatible)
│   ├── seed_data.sql                   # 12 baris data uji dummy tridarma
│   └── migrate_phone.php               # Skrip migrasi kolom telepon direct WhatsApp
├── docs/                               # Spesifikasi Teknis & Dokumentasi Arsitektur
│   └── SPESIFIKASI_ARSITEKTUR_SISTEM.md
├── mobile_app/                         # Aplikasi Mobile Pendukung (Flutter)
│   ├── pubspec.yaml                    # Konfigurasi dependensi Flutter
│   └── lib/                            # Kode sumber Flutter (Screen, Model, Service)
├── public/                             # Public Document Root (Akses Web Terbuka)
│   ├── api.php                         # Endpoint REST API untuk integrasi mobile
│   ├── index.php                       # Front Controller & Request Dispatcher
│   ├── manifest.json                   # Web App Manifest (PWA Ready)
│   ├── sw.js                           # Service Worker untuk caching aset statis
│   └── uploads/                        # Direktori penyimpanan berkas unggahan (.gitkeep)
├── tests/                              # Direktori pengujian fungsional (.gitkeep)
└── views/                              # Antarmuka Pengguna (UI Tailwind CSS)
    ├── admin/
    │   └── ai_config.php               # Panel administrasi konfigurasi model AI
    ├── audit/
    │   └── index.php                   # Tampilan jejak audit & tombol download database
    ├── auth/
    │   └── login.php                   # Halaman masuk & verifikasi kredensial
    ├── components/
    │   └── ai_narrative_widget.php     # Modal asisten generator narasi AI
    ├── dashboard.php                   # Dasbor visual Progress Meter bulanan
    ├── entries/
    │   ├── form_ppm.php                # Formulir input capaian penelitian & KI
    │   ├── form_psdm.php               # Formulir capaian pegawai & sertifikasi
    │   ├── form_kemhs.php              # Formulir capaian kompetisi mahasiswa
    │   ├── form_alumni.php             # Formulir capaian karir & wirausaha alumni
    │   ├── index.php                   # Tabel data tridarma dengan filter lengkap
    │   └── show.php                    # Tampilan rincian entri & bukti dokumen
    ├── export/
    │   ├── index.php                   # Pusat unduh ekspor Borang WURI & Rekapitulasi
    │   ├── pdf_wuri.php                # Template cetak PDF Borang WURI (A4 Landscape)
    │   └── pdf_rekap.php               # Template cetak PDF Rekapitulasi Eksekutif
    ├── komunikasi/
    │   └── index.php                   # Meja kerja kurasi siaran pers Bagian Komunikasi
    ├── layout/
    │   ├── header.php                  # Navigasi atas, status AI, & role switcher
    │   └── footer.php                  # Footer institusi & navigasi mobile bottom sheet
    ├── profile/
    │   └── index.php                   # Pengaturan profil akun & ubah kata sandi
    ├── reminders/
    │   └── index.php                   # Monitoring & pengiriman pengingat Signature
    └── users/
        └── index.php                   # Manajemen user, peran, & reset password admin
```

---

## 📊 Pusat Ekspor Borang & Laporan Pemeringkatan

Sistem menyediakan fungsi ekspor data multi-format yang dirancang khusus untuk mempermudah akreditasi dan pemeringkatan:

1. **Borang WURI (World University Rankings for Innovation)**:
   - Mengelompokkan data capaian secara otomatis ke dalam kategori resmi WURI:
     - *A1. Industrial Application & Tech Transfer* (Paten, prototipe, lisensi industri).
     - *B1. Student Mobility & Global Openness* (Prestasi internasional mahasiswa).
     - *C1. Crisis Management, Ethics & Leadership* (Pengembangan SDM & wirausaha alumni).
   - Format ekspor: **PDF Siap Cetak (A4 Landscape Standar Laporan Internasional)** dan **Berkas Spreadsheet (.xls / .csv)**.
2. **Rekapitulasi Capaian Eksekutif**:
   - Tabulasi menyeluruh seluruh modul tridarma untuk rapat pimpinan universitas dan evaluasi bulanan Task Force.
   - Dilengkapi stempel QR verifikasi keaslian dokumen digital dan *security hash* SHA-256.

---

## 🔒 Keamanan & Privasi Data

- **Pembersihan Data Sensitif**: Seluruh kredensial dan API key ditarik secara dinamis dari environment variable `.env` atau database terenkripsi, tanpa ada kunci sensitif yang tercantum pada *source code*.
- **Pencegahan Kebocoran Git**: Berkas `.env`, berkas basis data SQLite aktif (`*.sqlite`), dan dokumen unggahan privat secara otomatis diabaikan oleh `.gitignore`.
- **Kepatuhan UU PDP No. 27/2022**:
  - Dukungan eksplisit `Persetujuan Publikasi Nama` (YA/TIDAK).
  - Masking otomatis identitas pribadi (*PII*) pada nomor induk (NIP/NIM/NIDN) bagi pengguna publik/viewer.
  - Peringatan banner warna oranye pada meja kerja kurasi media jika nama subjek dilarang diumumkan ke publik.
- **Audit Logging**: Setiap aksi pembuatan, pengubahan, *screening*, kurasi, reset password, hingga pengunduhan cadangan tercatat permanen di tabel jejak audit (`audit_trails`).

---

## 🚀 Panduan Deployment ke Server Produksi

Saat mendistribusikan aplikasi ke server berbasis **Nginx** atau **Apache**:

1. Arahkan **DocumentRoot** server web ke folder `public/`, **BUKAN** ke root direktori proyek.
   - *Hal ini memastikan berkas internal (`.env`, `app/`, `database/`, `views/`) tidak dapat diakses secara langsung oleh pengunjung via browser.*
2. Pastikan direktori `database/` dan `public/uploads/` memiliki izin tulis (*write permission*) bagi pengguna web server (`chown -R www-data:www-data database public/uploads`).
3. Pada lingkungan produksi, atur `APP_ENV=production` dan `APP_DEBUG=false` di berkas `.env`.

---

## 📄 Lisensi

Proyek ini dirancang dan dikembangkan untuk lingkungan **Universitas Harkat Negeri** dan didistribusikan di bawah lisensi terbuka [MIT License](LICENSE).
