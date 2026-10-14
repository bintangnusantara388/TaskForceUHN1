<?php
// app/Database.php
require_once __DIR__ . '/Config.php';

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $dbDir = dirname(DB_PATH);
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0777, true);
            }
            if (!is_dir(UPLOAD_DIR)) {
                mkdir(UPLOAD_DIR, 0777, true);
            }

            $firstInit = !file_exists(DB_PATH);
            self::$instance = new PDO('sqlite:' . DB_PATH);
            self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Enable foreign keys
            self::$instance->exec("PRAGMA foreign_keys = ON;");

            // Pastikan tabel konfigurasi sistem selalu tersedia
            self::$instance->exec("
                CREATE TABLE IF NOT EXISTS system_settings (
                    setting_key TEXT PRIMARY KEY,
                    setting_value TEXT,
                    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
                );
            ");

            if ($firstInit) {
                self::initSchema(self::$instance);
                self::seedInitialData(self::$instance);
            }
        }
        return self::$instance;
    }

    private static function initSchema(PDO $db): void {
        $sql = "
        CREATE TABLE IF NOT EXISTS master_units (
            unit_code TEXT PRIMARY KEY,
            unit_name TEXT NOT NULL,
            is_active INTEGER DEFAULT 1,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS reporting_periods (
            period_id TEXT PRIMARY KEY,
            display_name TEXT NOT NULL,
            start_date TEXT NOT NULL,
            end_date TEXT NOT NULL,
            is_active INTEGER DEFAULT 1
        );

        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            full_name TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            phone TEXT DEFAULT NULL,
            role TEXT NOT NULL,
            unit_code TEXT,
            password_hash TEXT,
            failed_login_attempts INTEGER DEFAULT 0,
            locked_until TEXT DEFAULT NULL,
            last_login_at TEXT DEFAULT NULL,
            last_login_ip TEXT DEFAULT NULL,
            is_active INTEGER DEFAULT 1,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (unit_code) REFERENCES master_units(unit_code)
        );

        CREATE TABLE IF NOT EXISTS reputation_entries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            unit_code TEXT NOT NULL,
            period_id TEXT NOT NULL,
            tier_type TEXT NOT NULL CHECK (tier_type IN ('SIGNATURE', 'REGULER')),
            title TEXT NOT NULL,
            short_description TEXT NOT NULL,
            screening_status TEXT NOT NULL DEFAULT 'DRAFT' CHECK (screening_status IN ('DRAFT', 'SIAP_DIOLAH')),
            screening_submitted_at TEXT,
            screening_by INTEGER,
            comm_status TEXT NOT NULL DEFAULT 'BELUM_DIOLAH' CHECK (comm_status IN ('BELUM_DIOLAH', 'SEDANG_DIOLAH', 'SUDAH_TERBIT')),
            comm_processed_by INTEGER,
            comm_published_url TEXT,
            name_publication_consent TEXT NOT NULL DEFAULT 'YA' CHECK (name_publication_consent IN ('YA', 'TIDAK')),
            attachment_path TEXT,
            attachment_name TEXT,
            attachment_size_bytes INTEGER,
            external_url TEXT,
            notes TEXT,
            created_by INTEGER NOT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_by INTEGER,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (unit_code) REFERENCES master_units(unit_code),
            FOREIGN KEY (period_id) REFERENCES reporting_periods(period_id),
            FOREIGN KEY (created_by) REFERENCES users(id)
        );

        CREATE TABLE IF NOT EXISTS entry_ppm_details (
            entry_id INTEGER PRIMARY KEY,
            ki_type TEXT NOT NULL,
            registration_number TEXT NOT NULL,
            filing_date TEXT NOT NULL,
            grant_date TEXT,
            ki_status TEXT NOT NULL,
            FOREIGN KEY (entry_id) REFERENCES reputation_entries(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS ppm_inventors (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            entry_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            identifier TEXT NOT NULL,
            prodi TEXT NOT NULL,
            inventor_type TEXT NOT NULL,
            sort_order INTEGER DEFAULT 1,
            FOREIGN KEY (entry_id) REFERENCES reputation_entries(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS entry_psdm_details (
            entry_id INTEGER PRIMARY KEY,
            employee_name TEXT NOT NULL,
            employee_id TEXT NOT NULL,
            unit_or_prodi TEXT NOT NULL,
            employee_type TEXT NOT NULL,
            achievement_type TEXT NOT NULL,
            issuer_organizer TEXT NOT NULL,
            level TEXT NOT NULL,
            achievement_date TEXT NOT NULL,
            FOREIGN KEY (entry_id) REFERENCES reputation_entries(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS entry_kemahasiswaan_details (
            entry_id INTEGER PRIMARY KEY,
            student_name TEXT NOT NULL,
            student_nim TEXT NOT NULL,
            prodi TEXT NOT NULL,
            cohort_year INTEGER NOT NULL,
            event_name TEXT NOT NULL,
            organizer TEXT NOT NULL,
            event_location TEXT NOT NULL,
            event_date TEXT NOT NULL,
            achievement_rank TEXT NOT NULL,
            level TEXT NOT NULL,
            category TEXT NOT NULL,
            advisor_name TEXT,
            FOREIGN KEY (entry_id) REFERENCES reputation_entries(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS entry_alumni_details (
            entry_id INTEGER PRIMARY KEY,
            alumni_name TEXT NOT NULL,
            prodi TEXT NOT NULL,
            graduation_year INTEGER NOT NULL,
            achievement_type TEXT NOT NULL,
            company_or_institution TEXT NOT NULL,
            position TEXT NOT NULL,
            testimonial TEXT,
            FOREIGN KEY (entry_id) REFERENCES reputation_entries(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS audit_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            action TEXT NOT NULL,
            table_name TEXT,
            record_id INTEGER,
            details TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS reminder_notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            period_id TEXT,
            unit_code TEXT,
            reminder_phase TEXT,
            is_signature_fulfilled INTEGER DEFAULT 0,
            sent_at TEXT DEFAULT CURRENT_TIMESTAMP,
            recipient_name TEXT,
            recipient_email TEXT,
            recipient_phone TEXT,
            channel TEXT DEFAULT 'EMAIL',
            status TEXT DEFAULT 'SENT',
            message_body TEXT
        );
        ";

        $db->exec($sql);
    }

    private static function seedInitialData(PDO $db): void {
        // Master units
        foreach (UNITS as $code => $name) {
            $stmt = $db->prepare("INSERT INTO master_units (unit_code, unit_name) VALUES (?, ?)");
            $stmt->execute([$code, $name]);
        }

        // Master periods
        foreach (PERIODS as $id => $label) {
            $parts = explode('-', $id);
            $year = (int)$parts[0];
            $month = (int)$parts[1];
            $startDate = sprintf('%04d-%02d-01', $year, $month);
            $endDate = date('Y-m-t', strtotime($startDate));
            $stmt = $db->prepare("INSERT INTO reporting_periods (period_id, display_name, start_date, end_date) VALUES (?, ?, ?, ?)");
            $stmt->execute([$id, $label, $startDate, $endDate]);
        }

        // Users
        $defaultHash = password_hash('harkat123', PASSWORD_BCRYPT);
        foreach (DEFAULT_USERS as $u) {
            $stmt = $db->prepare("INSERT INTO users (id, username, full_name, email, role, unit_code, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $u['id'],
                $u['username'],
                $u['name'],
                $u['username'] . '@harkatnegeri.ac.id',
                $u['role'],
                $u['unit'],
                $defaultHash
            ]);
        }

        // 12 Initial Dummy Entries (3 per unit)
        self::seedDummyEntries($db);
    }

    private static function seedDummyEntries(PDO $db): void {
        // 1. PPM Data
        // 1.1 PPM Signature Granted Terbit
        $stmt = $db->prepare("INSERT INTO reputation_entries 
            (id, unit_code, period_id, tier_type, title, short_description, screening_status, screening_submitted_at, screening_by, comm_status, comm_processed_by, comm_published_url, name_publication_consent, attachment_name, attachment_size_bytes, external_url, notes, created_by, created_at)
            VALUES (101, 'PPM', '2026-10', 'SIGNATURE', ?, ?, 'SIAP_DIOLAH', '2026-10-05 09:30:00', 2, 'SUDAH_TERBIT', 6, 'https://harkatnegeri.ac.id/berita/paten-ai-kopi-petani', 'YA', 'Sertifikat_Paten_IDP000088921.pdf', 3145728, 'https://pdki-indonesia.dgip.go.id/detail/IDP000088921', 'Prioritas siaran pers dan borang WURI Industrial Application.', 2, '2026-10-05 09:15:00')");
        $stmt->execute([
            'Alat Sortir Kopi Berbasis AI Berdaya Rendah (Agritech)',
            'Inovasi mesin sortir cerdas yang mampu mendeteksi biji kopi cacat dalam hitungan milidetik secara presisi. Telah diuji coba pada 5 kelompok tani binaan dan meningkatkan efisiensi panen hingga 40%. Teknologi ini merupakan kolaborasi riset dosen dan mahasiswa.'
        ]);
        $db->exec("INSERT INTO entry_ppm_details VALUES (101, 'PATEN', 'IDP000088921', '2025-03-12', '2026-09-28', 'GRANTED')");
        $db->exec("INSERT INTO ppm_inventors (entry_id, name, identifier, prodi, inventor_type, sort_order) VALUES 
            (101, 'Dr. Ir. Ahmad Baiquni, M.T.', '0012057801', 'Teknik Elektro', 'DOSEN', 1),
            (101, 'Muhammad Fathur', '230105021', 'Teknik Informatika', 'MAHASISWA', 2)");

        // 1.2 PPM Reguler Terdaftar Sedang Olah
        $stmt = $db->prepare("INSERT INTO reputation_entries 
            (id, unit_code, period_id, tier_type, title, short_description, screening_status, screening_submitted_at, screening_by, comm_status, comm_processed_by, comm_published_url, name_publication_consent, attachment_name, attachment_size_bytes, notes, created_by, created_at)
            VALUES (102, 'PPM', '2026-10', 'REGULER', ?, ?, 'SIAP_DIOLAH', '2026-10-08 14:15:00', 2, 'SEDANG_DIOLAH', 6, NULL, 'YA', 'Surat_Pencatatan_EC0020269910.pdf', 1572864, 'Disiapkan untuk konten edukasi reels medsos.', 2, '2026-10-08 14:00:00')");
        $stmt->execute([
            'Hak Cipta: Buku Ajar Interaktif Etika Bisnis Berbasis Metaverse',
            'Buku ajar digital terintegrasi simulasi VR untuk pembelajaran etika bisnis dan kepemimpinan. Terdaftar resmi pada Pangkalan Data Kekayaan Intelektual Kemenkumham. Digunakan dalam kurikulum nasional kolaborasi antar-kampus.'
        ]);
        $db->exec("INSERT INTO entry_ppm_details VALUES (102, 'HAK_CIPTA', 'EC0020269910', '2026-09-10', '2026-10-02', 'TERDAFTAR')");
        $db->exec("INSERT INTO ppm_inventors (entry_id, name, identifier, prodi, inventor_type, sort_order) VALUES 
            (102, 'Dr. Ratna Susilowati, S.E., M.M.', '0019088203', 'Manajemen', 'DOSEN', 1)");

        // 1.3 PPM Reguler Dalam Proses Draft (Consent: TIDAK)
        $stmt = $db->prepare("INSERT INTO reputation_entries 
            (id, unit_code, period_id, tier_type, title, short_description, screening_status, screening_submitted_at, screening_by, comm_status, comm_processed_by, comm_published_url, name_publication_consent, attachment_name, attachment_size_bytes, notes, created_by, created_at)
            VALUES (103, 'PPM', '2026-10', 'REGULER', ?, ?, 'DRAFT', NULL, NULL, 'BELUM_DIOLAH', NULL, NULL, 'TIDAK', 'Bukti_Submit_P0020260771.pdf', 2097152, 'Peneliti meminta nama dirahasiakan sampai paten publish.', 2, '2026-10-10 11:00:00')");
        $stmt->execute([
            'Paten Sederhana: Formula Gel Antiinflamasi dari Ekstrak Kulit Manggis',
            'Formulasi sediaan herbal alami berdaya serap tinggi untuk meredakan nyeri dan pembengkakan sendi. Saat ini memasuki tahap pengujian substantif tahap akhir di DJKI. Berpotensi dihilirisasi bersama mitra farmasi daerah.'
        ]);
        $db->exec("INSERT INTO entry_ppm_details VALUES (103, 'PATEN_SEDERHANA', 'P0020260771', '2026-08-15', NULL, 'DALAM_PROSES')");
        $db->exec("INSERT INTO ppm_inventors (entry_id, name, identifier, prodi, inventor_type, sort_order) VALUES 
            (103, 'Apt. Farida Khairunnisa, M.Farm.', '0024048901', 'Farmasi', 'DOSEN', 1)");

        // 2. PSDM Data
        // 2.1 PSDM Signature Terbit
        $stmt = $db->prepare("INSERT INTO reputation_entries 
            (id, unit_code, period_id, tier_type, title, short_description, screening_status, screening_submitted_at, screening_by, comm_status, comm_processed_by, comm_published_url, name_publication_consent, attachment_name, attachment_size_bytes, external_url, notes, created_by, created_at)
            VALUES (201, 'PSDM', '2026-10', 'SIGNATURE', ?, ?, 'SIAP_DIOLAH', '2026-10-04 11:00:00', 3, 'SUDAH_TERBIT', 6, 'https://harkatnegeri.ac.id/berita/dosen-uhn-keynote-unesco-paris', 'YA', 'UNESCO_Keynote_Certificate_Paris.pdf', 2621440, 'https://unesco.org/events/ai-ethics-2026', 'Bukti kuat pemeringkatan internasional Uniranks & WURI.', 3, '2026-10-04 10:45:00')");
        $stmt->execute([
            'Keynote Speaker pada Forum UNESCO tentang AI Ethics in Higher Education',
            'Dosen Harkat Negeri mewakili akademisi Asia Tenggara memaparkan kerangka tata kelola kecerdasan buatan pada forum global UNESCO di Paris. Paparan menyoroti keadilan akses komputasi di negara berkembang. Menghasilkan inisiasi konsorsium riset global lintas 6 benua.'
        ]);
        $db->exec("INSERT INTO entry_psdm_details VALUES (201, 'Prof. Dr. Ir. Teguh Wicaksono, M.Eng.', '197508101999031002', 'Teknik Informatika', 'DOSEN', 'NARASUMBER', 'UNESCO Headquarters Paris', 'INTERNASIONAL', '2026-09-25')");

        // 2.2 PSDM Reguler Sedang Olah
        $stmt = $db->prepare("INSERT INTO reputation_entries 
            (id, unit_code, period_id, tier_type, title, short_description, screening_status, screening_submitted_at, screening_by, comm_status, comm_processed_by, comm_published_url, name_publication_consent, attachment_name, attachment_size_bytes, notes, created_by, created_at)
            VALUES (202, 'PSDM', '2026-10', 'REGULER', ?, ?, 'SIAP_DIOLAH', '2026-10-09 10:20:00', 3, 'SEDANG_DIOLAH', 6, NULL, 'YA', 'PMI_PMP_Credential_2026.pdf', 1843200, 'Sorotan tendik inspiratif di kanal LinkedIn kampus.', 3, '2026-10-09 10:00:00')");
        $stmt->execute([
            'Sertifikasi Global Project Management Professional (PMP) PMI USA',
            'Tendik bidang perencanaan berhasil meraih kredensial PMP bergengsi dari PMI Amerika Serikat setelah uji kompetensi internasional. Sertifikasi ini memperkuat tata kelola manajemen proyek strategis kampus berstandar global. Sangat relevan bagi perbaikan tata kelola institusi.'
        ]);
        $db->exec("INSERT INTO entry_psdm_details VALUES (202, 'Bayu Prasetyo, S.T., PMP', '198705122014041001', 'Biro Sistem Informasi', 'TENDIK', 'SERTIFIKASI', 'Project Management Institute (PMI) USA', 'INTERNASIONAL', '2026-09-30')");

        // 2.3 PSDM Reguler Draft
        $stmt = $db->prepare("INSERT INTO reputation_entries 
            (id, unit_code, period_id, tier_type, title, short_description, screening_status, screening_submitted_at, screening_by, comm_status, comm_processed_by, comm_published_url, name_publication_consent, attachment_name, attachment_size_bytes, notes, created_by, created_at)
            VALUES (203, 'PSDM', '2026-10', 'REGULER', ?, ?, 'DRAFT', NULL, NULL, 'BELUM_DIOLAH', NULL, NULL, 'YA', 'Piagam_LLDIKTI_Wilayah4.pdf', 3145728, 'Menunggu rekap tanda tangan dekanat.', 3, '2026-10-10 14:00:00')");
        $stmt->execute([
            'Penghargaan Dosen Berprestasi Bidang Sains & Teknologi LLDIKTI Wilayah IV',
            'Penghargaan atas produktivitas publikasi jurnal internasional bereputasi Q1 dan sitasi tertinggi tahun 2026. Diberikan dalam upacara peringatan Hari Pendidikan Nasional tingkat wilayah. Menjadi teladan bagi periset muda universitas.'
        ]);
        $db->exec("INSERT INTO entry_psdm_details VALUES (203, 'Dr. Sri Wahyuni, M.Biotech.', '198203152008122001', 'Bioteknologi', 'DOSEN', 'PENGHARGAAN', 'LLDIKTI Wilayah IV', 'NASIONAL', '2026-10-02')");

        // 3. Kemahasiswaan Data
        // 3.1 Kemahasiswaan Signature Terbit
        $stmt = $db->prepare("INSERT INTO reputation_entries 
            (id, unit_code, period_id, tier_type, title, short_description, screening_status, screening_submitted_at, screening_by, comm_status, comm_processed_by, comm_published_url, name_publication_consent, attachment_name, attachment_size_bytes, external_url, notes, created_by, created_at)
            VALUES (301, 'KEMAHASISWAAN', '2026-10', 'SIGNATURE', ?, ?, 'SIAP_DIOLAH', '2026-10-06 08:00:00', 4, 'SUDAH_TERBIT', 6, 'https://harkatnegeri.ac.id/berita/mahasiswa-teknik-emas-wyyia-malaysia', 'YA', 'Certificate_Gold_WYIIA_2026.pdf', 4194304, 'https://wyyia.org/winners-2026', 'Sangat kuat untuk borang WURI Student Mobility.', 4, '2026-10-06 07:30:00')");
        $stmt->execute([
            'Juara 1 & Gold Medal World Youth Invention and Innovation Award (WYIIA) 2026',
            'Tim mahasiswa Fakultas Teknik berhasil meraih medali emas kategori Environmental Science atas inovasi sensor deteksi limbah mikroplastik sungai. Kompetisi ini diikuti oleh 250 tim dari 18 negara di Kuala Lumpur. Solusi ini dinilai memiliki dampak langsung pada pelestarian ekosistem air.'
        ]);
        $db->exec("INSERT INTO entry_kemahasiswaan_details VALUES (301, 'Andi Pratama Kusuma', '230401088', 'Teknik Lingkungan', 2023, 'World Youth Invention and Innovation Award (WYIIA) 2026', 'IYSA & Universiti Malaya', 'Kuala Lumpur, Malaysia', '2026-09-22', 'JUARA_1', 'INTERNASIONAL', 'AKADEMIK', 'Dr. Ir. Kusuma Wardhana, M.T.')");

        // 3.2 Kemahasiswaan Reguler Belum Olah
        $stmt = $db->prepare("INSERT INTO reputation_entries 
            (id, unit_code, period_id, tier_type, title, short_description, screening_status, screening_submitted_at, screening_by, comm_status, comm_processed_by, comm_published_url, name_publication_consent, attachment_name, attachment_size_bytes, external_url, notes, created_by, created_at)
            VALUES (302, 'KEMAHASISWAAN', '2026-10', 'REGULER', ?, ?, 'SIAP_DIOLAH', '2026-10-10 16:40:00', 4, 'BELUM_DIOLAH', NULL, NULL, 'YA', 'Piagam_Emas_POMNAS_Catur.pdf', 2883584, 'https://pomnas.kemdikbud.go.id/result/chess', 'Jadwalkan wawancara singkat di podcast kemahasiswaan.', 4, '2026-10-10 16:20:00')");
        $stmt->execute([
            'Medali Emas Cabang Catur Kilat Pekan Olahraga Mahasiswa Nasional (POMNAS)',
            'Atlet mahasiswa UHN berhasil mengamankan podium tertinggi setelah mengalahkan 32 perwakilan perguruan tinggi negeri dan swasta se-Indonesia. Kemenangan ini mencetak rekor baru perolehan medali emas catur pertama universitas di POMNAS. Pertandingan berlangsung sengit selama 9 babak.'
        ]);
        $db->exec("INSERT INTO entry_kemahasiswaan_details VALUES (302, 'Galang Rambu Anarki', '240502012', 'Ilmu Hukum', 2024, 'Pekan Olahraga Mahasiswa Nasional (POMNAS) XX', 'BAPOMI & Kemendiktisaintek', 'Semarang, Jawa Tengah', '2026-10-01', 'MEDALI_EMAS', 'NASIONAL', 'NON_AKADEMIK', 'Drs. Hendro Suyono, M.Or.')");

        // 3.3 Kemahasiswaan Reguler Belum Olah (Consent: TIDAK)
        $stmt = $db->prepare("INSERT INTO reputation_entries 
            (id, unit_code, period_id, tier_type, title, short_description, screening_status, screening_submitted_at, screening_by, comm_status, comm_processed_by, comm_published_url, name_publication_consent, attachment_name, attachment_size_bytes, notes, created_by, created_at)
            VALUES (303, 'KEMAHASISWAAN', '2026-10', 'REGULER', ?, ?, 'SIAP_DIOLAH', '2026-10-11 13:00:00', 4, 'BELUM_DIOLAH', NULL, NULL, 'TIDAK', 'Sertifikat_Juara2_DataScience.pdf', 3407872, 'PERHATIAN PRIVASI: Mahasiswa menolak publikasi nama pribadi di medsos terbuka.', 4, '2026-10-11 12:45:00')");
        $stmt->execute([
            'Juara 2 National Data Science & AI Challenge 2026',
            'Mengembangkan model deteksi fraud transaksi keuangan dengan algoritma ensemble learning berakurasi 98,7%. Menyisihkan 120 tim dari universitas papan atas se-Indonesia. Hasil riset diminati inkubator industri perbankan.'
        ]);
        $db->exec("INSERT INTO entry_kemahasiswaan_details VALUES (303, 'Nurul Hidayati', '220108044', 'Sistem Informasi', 2022, 'National Data Science & AI Challenge 2026', 'Asosiasi Data Science Indonesia', 'Jakarta Selatan', '2026-09-29', 'JUARA_2', 'NASIONAL', 'AKADEMIK', 'Anindya Kirana, M.Kom.')");

        // 4. Alumni Karir Data
        // 4.1 Alumni Signature Terbit
        $stmt = $db->prepare("INSERT INTO reputation_entries 
            (id, unit_code, period_id, tier_type, title, short_description, screening_status, screening_submitted_at, screening_by, comm_status, comm_processed_by, comm_published_url, name_publication_consent, attachment_name, attachment_size_bytes, external_url, notes, created_by, created_at)
            VALUES (401, 'ALUMNI_KARIR', '2026-10', 'SIGNATURE', ?, ?, 'SIAP_DIOLAH', '2026-10-03 10:15:00', 5, 'SUDAH_TERBIT', 6, 'https://harkatnegeri.ac.id/berita/alumni-informatika-google-zurich', 'YA', 'Bukti_Verifikasi_Google_Zurich.pdf', 2097152, 'https://linkedin.com/in/dimas-alumni-uhn', 'Sangat berdampak untuk pemeringkatan QS Employability & WURI.', 5, '2026-10-03 09:50:00')");
        $stmt->execute([
            'Alumni UHN Berkarir sebagai Senior AI Infrastructure Engineer di Google Zurich',
            'Lulusan Teknik Informatika UHN tahun 2020 berhasil menembus posisi Senior Engineer di Google Zurich setelah bersaing ketat dalam seleksi global. Bertanggung jawab atas optimasi pipeline akselerasi TPU untuk pemodelan bahasa berskala besar. Bukti nyata daya saing kurikulum dan kualitas lulusan.'
        ]);
        $db->exec("INSERT INTO entry_alumni_details VALUES (401, 'Dimas Surya Pratama, S.Kom.', 'Teknik Informatika', 2020, 'PENEMPATAN_KERJA', 'Google Switzerland GmbH (Zurich HQ)', 'Senior AI Infrastructure Engineer', 'Pondasi algoritma dan etos kerja yang ditempa dosen-dosen UHN memberi modal tangguh bagi saya untuk bersaing di panggung teknologi global.')");

        // 4.2 Alumni Reguler Sedang Olah
        $stmt = $db->prepare("INSERT INTO reputation_entries 
            (id, unit_code, period_id, tier_type, title, short_description, screening_status, screening_submitted_at, screening_by, comm_status, comm_processed_by, comm_published_url, name_publication_consent, attachment_name, attachment_size_bytes, external_url, notes, created_by, created_at)
            VALUES (402, 'ALUMNI_KARIR', '2026-10', 'REGULER', ?, ?, 'SIAP_DIOLAH', '2026-10-07 15:30:00', 5, 'SEDANG_DIOLAH', 6, NULL, 'YA', 'PressRelease_SeedFunding_HydroNusantara.pdf', 3670016, 'https://hydronusantara.id', 'Disiapkan untuk artikel feature alumni wirausaha muda.', 5, '2026-10-07 15:00:00')");
        $stmt->execute([
            'Pendiri Startup HydroNusantara Raih Pendanaan Seed Round Rp 3,5 Miliar',
            'Alumni Agribisnis mendirikan startup teknologi pertanian presisi berbasis IoT yang memberdayakan 150 petani hidroponik lokal. Baru saja mengamankan pendanaan tahap awal dari konsorsium venture capital terkemuka. Memberikan kontribusi nyata pada ketahanan pangan perkotaan.'
        ]);
        $db->exec("INSERT INTO entry_alumni_details VALUES (402, 'Rian Firmansyah, S.P.', 'Agribisnis', 2021, 'WIRAUSAHA', 'PT Hydro Nusantara Mandiri', 'Founder & Chief Executive Officer (CEO)', 'Program inkubator bisnis kampus Harkat Negeri adalah batu loncatan pertama yang mengubah gagasan skripsi saya menjadi bisnis nyata.')");

        // 4.3 Alumni Reguler Draft
        $stmt = $db->prepare("INSERT INTO reputation_entries 
            (id, unit_code, period_id, tier_type, title, short_description, screening_status, screening_submitted_at, screening_by, comm_status, comm_processed_by, comm_published_url, name_publication_consent, attachment_name, attachment_size_bytes, notes, created_by, created_at)
            VALUES (403, 'ALUMNI_KARIR', '2026-10', 'REGULER', ?, ?, 'DRAFT', NULL, NULL, 'BELUM_DIOLAH', NULL, NULL, 'YA', 'LoA_Unconditional_Oxford_LPDP.pdf', 1258291, 'Menunggu jadwal resmi keberangkatan sebelum dipublikasikan.', 5, '2026-10-11 10:00:00')");
        $stmt->execute([
            'Lolos Beasiswa Penuh LPDP Magister di University of Oxford (M.Sc in Public Policy)',
            'Alumni Ilmu Pemerintahan berhasil meraih beasiswa bergengsi LPDP RI untuk studi lanjutan di Blavatnik School of Government, University of Oxford. Berkomitmen mendalami evaluasi kebijakan pengentasan kemiskinan berbasis data spasial. Menginspirasi adik-adik tingkat.'
        ]);
        $db->exec("INSERT INTO entry_alumni_details VALUES (403, 'Sarah Amanda, S.IP.', 'Ilmu Pemerintahan', 2023, 'STUDI_LANJUT', 'University of Oxford (United Kingdom)', 'Awardee Master of Public Policy (MPP)', 'Dukungan rekomendasi dosen serta kultur berpikir kritis di kampus menjadi pemantik utama keberhasilan seleksi wawancara beasiswa luar negeri.')");

        // Audit log inisial
        $db->exec("INSERT INTO audit_logs (user_id, action, table_name, details) VALUES 
            (1, 'SYSTEM_INIT', 'reputation_entries', 'Inisialisasi sistem dan penyemaian 12 data awal untuk 4 modul')");
    }
}
