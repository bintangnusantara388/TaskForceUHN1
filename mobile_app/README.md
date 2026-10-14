# Aplikasi Mobile ERP - Task Force Reputasi & Branding Institusi
## Universitas Harkat Negeri

Aplikasi mobile pendamping (*companion app*) berbasis **Flutter & PWA** untuk Tim Task Force Kerjasama Internasional & Reputasi Institusi, Universitas Harkat Negeri. Aplikasi ini dirancang khusus untuk mempermudah PIC unit kerja (PPM, PSDM, Kemahasiswaan, Alumni Karir), Bagian Komunikasi, dan Pimpinan memantau dan menyetor data capaian langsung dari smartphone di lapangan.

---

### 📱 Fitur Khas Mobile
1. **Camera Scan & Quick Capture**:
   - Ambil foto sertifikat, medali, atau piagam lomba langsung menggunakan kamera ponsel.
   - Kompresi foto otomatis sebelum diunggah melalui multipart HTTP request ke server ERP.
2. **Progress Meter di Genggaman**:
   - Pemantauan real-time status kuota minimal 1 data **Signature** bulanan per unit.
   - Kartu progress visual dengan indikator keterpenuhan (✓/✗).
3. **Peringatan Privasi UU PDP Otomatis**:
   - Penegakan filter dan warning banner merah jika subjek menolak persetujuan publikasi nama pribadi di media publik.
4. **Meja Kurasi Komunikasi Mobile**:
   - Bagian Komunikasi dapat meninjau antrean data *Siap Diolah*, mengubah status menjadi *Sedang Diolah* atau *Sudah Terbit*, dan memasukkan tautan berita media langsung dari HP.
5. **Instant Role Switcher**:
   - Memungkinkan pengujian simulasi hak akses role (Admin, PIC Unit, Komunikasi, Viewer) dalam 1 sentuhan.

---

### 🏗️ Arsitektur Integrasi
```
┌─────────────────────────────────┐
│     Flutter Mobile App / PWA    │
│  (Android / iOS / Touch Web)    │
└───────────────┬─────────────────┘
                │ REST API (JSON & Multipart)
                ▼
┌─────────────────────────────────┐
│  PHP 8.5 Backend API Gateway    │
│        (/public/api.php)        │
└───────────────┬─────────────────┘
                │
                ▼
┌─────────────────────────────────┐
│       SQLite Database ERP       │
│     (database/app.sqlite)       │
└─────────────────────────────────┘
```

---

### 🚀 Cara Menjalankan Aplikasi Mobile

#### Opsi 1: Menjalankan via Flutter (Android / iOS / Web)
1. Buka terminal di folder `mobile_app/`:
   ```bash
   cd mobile_app
   flutter pub get
   ```
2. Pastikan server backend PHP sedang berjalan di port 8088:
   ```bash
   # Di root direktori KordinasiTaskforce:
   php -S 0.0.0.0:8088 -t public
   ```
3. Jika menjalankan di Android Emulator, arahkan `baseUrl` di `lib/services/api_service.dart` ke `http://10.0.2.2:8088`. Jika di smartphone fisik via WiFi, gunakan IP LAN komputer Anda (misal `http://192.168.1.15:8088`).
4. Jalankan aplikasi:
   ```bash
   flutter run
   ```

#### Opsi 2: Menggunakan PWA (Progressive Web App) Langsung di Smartphone
1. Buka browser di smartphone (Chrome di Android atau Safari di iOS).
2. Akses alamat web ERP (misal: `http://[IP-Komputer]:8088`).
3. Tekan menu browser lalu pilih **"Tambahkan ke Layar Utama" (Add to Home Screen)**.
4. Aplikasi akan terpasang di smartphone Anda sebagai aplikasi mandiri (*standalone app*) lengkap dengan Bottom Navigation Bar dan Floating Action Button kamera.
