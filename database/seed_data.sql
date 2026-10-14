-- =============================================================================
-- DATA SEEDER DUMMY: 3 BARIS PER MODUL
-- Universitas Harkat Negeri - Modul Inventarisasi Reputasi Institusi
-- =============================================================================

-- Master Units
INSERT INTO master_units (unit_code, unit_name) VALUES
('PPM', 'Pusat Penelitian dan Pengabdian Masyarakat'),
('PSDM', 'Pengembangan Sumber Daya Manusia'),
('KEMAHASISWAAN', 'Biro Kemahasiswaan dan Alumni'),
('ALUMNI_KARIR', 'Pusat Karir dan Hubungan Alumni'),
('KOMUNIKASI', 'Bagian Komunikasi dan Promosi'),
('TASK_FORCE', 'Tim Task Force Kerjasama & Reputasi');

-- Master Periode Pelaporan (Sep 2026 s.d. Agu 2027)
INSERT INTO reporting_periods (period_id, display_name, start_date, end_date) VALUES
('2026-09', 'September 2026', '2026-09-01', '2026-09-30'),
('2026-10', 'Oktober 2026', '2026-10-01', '2026-10-31'),
('2026-11', 'November 2026', '2026-11-01', '2026-11-30'),
('2026-12', 'Desember 2026', '2026-12-01', '2026-12-31'),
('2027-01', 'Januari 2027', '2027-01-01', '2027-01-31'),
('2027-02', 'Februari 2027', '2027-02-01', '2027-02-28'),
('2027-03', 'Maret 2027', '2027-03-01', '2027-03-31'),
('2027-04', 'April 2027', '2027-04-01', '2027-04-30'),
('2027-05', 'Mei 2027', '2027-05-01', '2027-05-31'),
('2027-06', 'Juni 2027', '2027-06-01', '2027-06-30'),
('2027-07', 'Juli 2027', '2027-07-01', '2027-07-31'),
('2027-08', 'Agustus 2027', '2027-08-01', '2027-08-31');

-- Users Dummy
INSERT INTO users (id, username, full_name, email, password_hash, role, unit_code) VALUES
(1, 'admin_tf', 'Dr. Hendra Gunawan, S.T., M.T.', 'admin.tf@harkatnegeri.ac.id', '$2y$10$hashadmin', 'ADMIN_TASK_FORCE', 'TASK_FORCE'),
(2, 'pic_ppm', 'Siti Rahmawati, M.Si.', 'ppm@harkatnegeri.ac.id', '$2y$10$hashppm', 'PIC_UNIT', 'PPM'),
(3, 'pic_psdm', 'Bambang Sudiro, S.Kom., M.M.', 'psdm@harkatnegeri.ac.id', '$2y$10$hashpsdm', 'PIC_UNIT', 'PSDM'),
(4, 'pic_mhs', 'Dewi Sartika, M.Pd.', 'kemahasiswaan@harkatnegeri.ac.id', '$2y$10$hashmhs', 'PIC_UNIT', 'KEMAHASISWAAN'),
(5, 'pic_alumni', 'Rudi Hartono, S.E., M.B.A.', 'alumni@harkatnegeri.ac.id', '$2y$10$hashalumni', 'PIC_UNIT', 'ALUMNI_KARIR'),
(6, 'komunikasi_staff', 'Nadia Putri, S.I.Kom.', 'komunikasi@harkatnegeri.ac.id', '$2y$10$hashkom', 'KOMUNIKASI', 'KOMUNIKASI'),
(7, 'rektor_viewer', 'Prof. Dr. Ir. H. Mulyadi, M.Sc. (Rektor)', 'pimpinan@harkatnegeri.ac.id', '$2y$10$hashpimp', 'VIEWER', 'TASK_FORCE');

-- =============================================================================
-- 1. MODUL PPM (3 BARIS)
-- =============================================================================
-- PPM 1: Signature, Granted, Siap Diolah -> Sudah Terbit
INSERT INTO reputation_entries (
    id, unit_code, period_id, tier_type, title, short_description,
    screening_status, screening_submitted_at, screening_by,
    comm_status, comm_processed_by, comm_published_url,
    name_publication_consent, attachment_path, attachment_name, attachment_size_bytes, external_url,
    notes, created_by
) VALUES (
    101, 'PPM', '2026-10', 'SIGNATURE',
    'Alat Sortir Kopi Berbasis AI Berdaya Rendah (Agritech)',
    'Inovasi mesin sortir cerdas yang mampu mendeteksi biji kopi cacat dalam hitungan milidetik secara presisi. Telah diuji coba pada 5 kelompok tani binaan dan meningkatkan efisiensi panen hingga 40%. Teknologi ini merupakan kolaborasi riset dosen dan mahasiswa.',
    'SIAP_DIOLAH', '2026-10-05 09:30:00', 2,
    'SUDAH_TERBIT', 6, 'https://harkatnegeri.ac.id/berita/paten-ai-kopi-petani',
    'YA', '/uploads/ppm/paten_ai_kopi_cert.pdf', 'Sertifikat_Paten_IDP000088921.pdf', 3145728, 'https://pdki-indonesia.dgip.go.id/detail/IDP000088921',
    'Prioritas siaran pers dan borang WURI Industrial Application.', 2
);
INSERT INTO entry_ppm_details (entry_id, ki_type, registration_number, filing_date, grant_date, ki_status)
VALUES (101, 'PATEN', 'IDP000088921', '2025-03-12', '2026-09-28', 'GRANTED');
INSERT INTO ppm_inventors (entry_id, name, identifier, prodi, inventor_type, sort_order) VALUES
(101, 'Dr. Ir. Ahmad Baiquni, M.T.', '0012057801', 'Teknik Elektro', 'DOSEN', 1),
(101, 'Muhammad Fathur', '230105021', 'Teknik Informatika', 'MAHASISWA', 2);

-- PPM 2: Reguler, Terdaftar, Siap Diolah -> Sedang Diolah
INSERT INTO reputation_entries (
    id, unit_code, period_id, tier_type, title, short_description,
    screening_status, screening_submitted_at, screening_by,
    comm_status, comm_processed_by, comm_published_url,
    name_publication_consent, attachment_path, attachment_name, attachment_size_bytes, external_url,
    notes, created_by
) VALUES (
    102, 'PPM', '2026-10', 'REGULER',
    'Hak Cipta: Buku Ajar Interaktif Etika Bisnis Berbasis Metaverse',
    'Buku ajar digital terintegrasi simulasi VR untuk pembelajaran etika bisnis dan kepemimpinan. Terdaftar resmi pada Pangkalan Data Kekayaan Intelektual Kemenkumham. Digunakan dalam kurikulum nasional kolaborasi antar-kampus.',
    'SIAP_DIOLAH', '2026-10-08 14:15:00', 2,
    'SEDANG_DIOLAH', 6, NULL,
    'YA', '/uploads/ppm/hakcipta_buku_metaverse.pdf', 'Surat_Pencatatan_EC0020269910.pdf', 1572864, NULL,
    'Disiapkan untuk konten edukasi reels medsos dan artikel majalah kampus.', 2
);
INSERT INTO entry_ppm_details (entry_id, ki_type, registration_number, filing_date, grant_date, ki_status)
VALUES (102, 'HAK_CIPTA', 'EC0020269910', '2026-09-10', '2026-10-02', 'TERDAFTAR');
INSERT INTO ppm_inventors (entry_id, name, identifier, prodi, inventor_type, sort_order) VALUES
(102, 'Dr. Ratna Susilowati, S.E., M.M.', '0019088203', 'Manajemen', 'DOSEN', 1);

-- PPM 3: Reguler, Dalam Proses, Draft (Belum Discreening)
INSERT INTO reputation_entries (
    id, unit_code, period_id, tier_type, title, short_description,
    screening_status, screening_submitted_at, screening_by,
    comm_status, comm_processed_by, comm_published_url,
    name_publication_consent, attachment_path, attachment_name, attachment_size_bytes, external_url,
    notes, created_by
) VALUES (
    103, 'PPM', '2026-10', 'REGULER',
    'Paten Sederhana: Formula Gel Antiinflamasi dari Ekstrak Kulit Manggis',
    'Formulasi sediaan herbal alami berdaya serap tinggi untuk meredakan nyeri dan pembengkakan sendi. Saat ini memasuki tahap pengujian substantif tahap akhir di DJKI. Berpotensi dihilirisasi bersama mitra farmasi daerah.',
    'DRAFT', NULL, NULL,
    'BELUM_DIOLAH', NULL, NULL,
    'TIDAK', '/uploads/ppm/draft_paten_gel.pdf', 'Bukti_Submit_P0020260771.pdf', 2097152, NULL,
    'PERHATIAN: Peneliti meminta anonimitas nama sampai masa publikasi paten resmi dibuka.', 2
);
INSERT INTO entry_ppm_details (entry_id, ki_type, registration_number, filing_date, grant_date, ki_status)
VALUES (103, 'PATEN_SEDERHANA', 'P0020260771', '2026-08-15', NULL, 'DALAM_PROSES');
INSERT INTO ppm_inventors (entry_id, name, identifier, prodi, inventor_type, sort_order) VALUES
(103, 'Apt. Farida Khairunnisa, M.Farm.', '0024048901', 'Farmasi', 'DOSEN', 1);

-- =============================================================================
-- 2. MODUL PSDM (3 BARIS)
-- =============================================================================
-- PSDM 1: Signature, Internasional, Siap Diolah -> Sudah Terbit
INSERT INTO reputation_entries (
    id, unit_code, period_id, tier_type, title, short_description,
    screening_status, screening_submitted_at, screening_by,
    comm_status, comm_processed_by, comm_published_url,
    name_publication_consent, attachment_path, attachment_name, attachment_size_bytes, external_url,
    notes, created_by
) VALUES (
    201, 'PSDM', '2026-10', 'SIGNATURE',
    'Keynote Speaker pada Forum UNESCO tentang AI Ethics in Higher Education',
    'Dosen Harkat Negeri mewakili akademisi Asia Tenggara memaparkan kerangka tata kelola kecerdasan buatan pada forum global UNESCO di Paris. Paparan menyoroti keadilan akses komputasi di negara berkembang. Menghasilkan inisiasi konsorsium riset global lintas 6 benua.',
    'SIAP_DIOLAH', '2026-10-04 11:00:00', 3,
    'SUDAH_TERBIT', 6, 'https://harkatnegeri.ac.id/berita/dosen-uhn-keynote-unesco-paris',
    'YA', '/uploads/psdm/sertifikat_unesco.pdf', 'UNESCO_Keynote_Certificate_Paris.pdf', 2621440, 'https://unesco.org/events/ai-ethics-2026',
    'Bukti kuat untuk pemeringkatan reputasi internasional (Uniranks & WURI).', 3
);
INSERT INTO entry_psdm_details (entry_id, employee_name, employee_id, unit_or_prodi, employee_type, achievement_type, issuer_organizer, level, achievement_date)
VALUES (201, 'Prof. Dr. Ir. Teguh Wicaksono, M.Eng.', '197508101999031002', 'Teknik Informatika', 'DOSEN', 'NARASUMBER', 'UNESCO Headquarters Paris', 'INTERNASIONAL', '2026-09-25');

-- PSDM 2: Reguler, Nasional, Siap Diolah -> Sedang Diolah
INSERT INTO reputation_entries (
    id, unit_code, period_id, tier_type, title, short_description,
    screening_status, screening_submitted_at, screening_by,
    comm_status, comm_processed_by, comm_published_url,
    name_publication_consent, attachment_path, attachment_name, attachment_size_bytes, external_url,
    notes, created_by
) VALUES (
    202, 'PSDM', '2026-10', 'REGULER',
    'Sertifikasi Global Project Management Professional (PMP) PMI USA',
    'Tendik bidang perencanaan berhasil meraih kredensial PMP bergengsi dari PMI Amerika Serikat setelah uji kompetensi internasional. Sertifikasi ini memperkuat tata kelola manajemen proyek strategis kampus berstandar global. Sangat relevan bagi perbaikan tata kelola institusi.',
    'SIAP_DIOLAH', '2026-10-09 10:20:00', 3,
    'SEDANG_DIOLAH', 6, NULL,
    'YA', '/uploads/psdm/pmp_certificate.pdf', 'PMI_PMP_Credential_2026.pdf', 1843200, 'https://pmi.org/verify/399120',
    'Bagus untuk sorotan profil tendik inspiratif di kanal LinkedIn kampus.', 3
);
INSERT INTO entry_psdm_details (entry_id, employee_name, employee_id, unit_or_prodi, employee_type, achievement_type, issuer_organizer, level, achievement_date)
VALUES (202, 'Bayu Prasetyo, S.T., PMP', '198705122014041001', 'Biro Perencanaan & Sistem Informasi', 'TENDIK', 'SERTIFIKASI', 'Project Management Institute (PMI) USA', 'INTERNASIONAL', '2026-09-30');

-- PSDM 3: Reguler, Nasional, Draft
INSERT INTO reputation_entries (
    id, unit_code, period_id, tier_type, title, short_description,
    screening_status, screening_submitted_at, screening_by,
    comm_status, comm_processed_by, comm_published_url,
    name_publication_consent, attachment_path, attachment_name, attachment_size_bytes, external_url,
    notes, created_by
) VALUES (
    203, 'PSDM', '2026-10', 'REGULER',
    'Penghargaan Dosen Berprestasi Bidang Sains & Teknologi LLDIKTI Wilayah IV',
    'Penghargaan atas produktivitas publikasi jurnal internasional bereputasi Q1 dan sitasi tertinggi tahun 2026. Diberikan dalam upacara peringatan Hari Pendidikan Nasional tingkat wilayah. Menjadi teladan bagi periset muda universitas.',
    'DRAFT', NULL, NULL,
    'BELUM_DIOLAH', NULL, NULL,
    'YA', '/uploads/psdm/sk_lldikti.pdf', 'Piagam_LLDIKTI_Wilayah4.pdf', 3145728, NULL,
    'Menunggu tanda tangan digital rekapitulasi dari dekanat.', 3
);
INSERT INTO entry_psdm_details (entry_id, employee_name, employee_id, unit_or_prodi, employee_type, achievement_type, issuer_organizer, level, achievement_date)
VALUES (203, 'Dr. Sri Wahyuni, M.Biotech.', '198203152008122001', 'Bioteknologi', 'DOSEN', 'PENGHARGAAN', 'LLDIKTI Wilayah IV', 'NASIONAL', '2026-10-02');

-- =============================================================================
-- 3. MODUL KEMAHASISWAAN (3 BARIS)
-- =============================================================================
-- MHS 1: Signature, Internasional, Siap Diolah -> Sudah Terbit
INSERT INTO reputation_entries (
    id, unit_code, period_id, tier_type, title, short_description,
    screening_status, screening_submitted_at, screening_by,
    comm_status, comm_processed_by, comm_published_url,
    name_publication_consent, attachment_path, attachment_name, attachment_size_bytes, external_url,
    notes, created_by
) VALUES (
    301, 'KEMAHASISWAAN', '2026-10', 'SIGNATURE',
    'Juara 1 & Gold Medal World Youth Invention and Innovation Award (WYIIA) 2026',
    'Tim mahasiswa Fakultas Teknik berhasil meraih medali emas kategori Environmental Science atas inovasi sensor deteksi limbah mikroplastik sungai. Kompetisi ini diikuti oleh 250 tim dari 18 negara di Kuala Lumpur. Solusi ini dinilai memiliki dampak langsung pada pelestarian ekosistem air.',
    'SIAP_DIOLAH', '2026-10-06 08:00:00', 4,
    'SUDAH_TERBIT', 6, 'https://harkatnegeri.ac.id/berita/mahasiswa-teknik-emas-wyyia-malaysia',
    'YA', '/uploads/mhs/gold_medal_wyyia.pdf', 'Certificate_Gold_WYIIA_2026.pdf', 4194304, 'https://wyyia.org/winners-2026',
    'Sangat kuat untuk indikator WURI Student Mobility & Openness dan siaran pers nasional.', 4
);
INSERT INTO entry_kemahasiswaan_details (
    entry_id, student_name, student_nim, prodi, cohort_year,
    event_name, organizer, event_location, event_date,
    achievement_rank, level, category, advisor_name
) VALUES (
    301, 'Andi Pratama Kusuma', '230401088', 'Teknik Lingkungan', 2023,
    'World Youth Invention and Innovation Award (WYIIA) 2026', 'IYSA & Universiti Malaya', 'Kuala Lumpur, Malaysia', '2026-09-22',
    'JUARA_1', 'INTERNASIONAL', 'AKADEMIK', 'Dr. Ir. Kusuma Wardhana, M.T.'
);

-- MHS 2: Reguler, Nasional, Siap Diolah -> Belum Diolah
INSERT INTO reputation_entries (
    id, unit_code, period_id, tier_type, title, short_description,
    screening_status, screening_submitted_at, screening_by,
    comm_status, comm_processed_by, comm_published_url,
    name_publication_consent, attachment_path, attachment_name, attachment_size_bytes, external_url,
    notes, created_by
) VALUES (
    302, 'KEMAHASISWAAN', '2026-10', 'REGULER',
    'Medali Emas Cabang Catur Kilat Pekan Olahraga Mahasiswa Nasional (POMNAS)',
    'Atlet mahasiswa UHN berhasil mengamankan podium tertinggi setelah mengalahkan 32 perwakilan perguruan tinggi negeri dan swasta se-Indonesia. Kemenangan ini mencetak rekor baru perolehan medali emas catur pertama universitas di POMNAS. Pertandingan berlangsung sengit selama 9 babak.',
    'SIAP_DIOLAH', '2026-10-10 16:40:00', 4,
    'BELUM_DIOLAH', NULL, NULL,
    'YA', '/uploads/mhs/pomnas_catur_emas.pdf', 'Piagam_Emas_POMNAS_Catur.pdf', 2883584, 'https://pomnas.kemdikbud.go.id/result/chess',
    'Bisa dijadwalkan wawancara singkat di podcast kemahasiswaan kampus.', 4
);
INSERT INTO entry_kemahasiswaan_details (
    entry_id, student_name, student_nim, prodi, cohort_year,
    event_name, organizer, event_location, event_date,
    achievement_rank, level, category, advisor_name
) VALUES (
    302, 'Galang Rambu Anarki', '240502012', 'Ilmu Hukum', 2024,
    'Pekan Olahraga Mahasiswa Nasional (POMNAS) XX', 'BAPOMI & Kemendiktisaintek', 'Semarang, Jawa Tengah', '2026-10-01',
    'MEDALI_EMAS', 'NASIONAL', 'NON_AKADEMIK', 'Drs. Hendro Suyono, M.Or.'
);

-- MHS 3: Reguler, Nasional, Siap Diolah (Consent: TIDAK)
INSERT INTO reputation_entries (
    id, unit_code, period_id, tier_type, title, short_description,
    screening_status, screening_submitted_at, screening_by,
    comm_status, comm_processed_by, comm_published_url,
    name_publication_consent, attachment_path, attachment_name, attachment_size_bytes, external_url,
    notes, created_by
) VALUES (
    303, 'KEMAHASISWAAN', '2026-10', 'REGULER',
    'Juara 2 National Data Science & AI Challenge 2026',
    'Mengembangkan model deteksi fraud transaksi keuangan dengan algoritma ensemble learning berakurasi 98,7%. Menyisihkan 120 tim dari universitas papan atas se-Indonesia. Hasil riset diminati inkubator industri perbankan.',
    'SIAP_DIOLAH', '2026-10-11 13:00:00', 4,
    'BELUM_DIOLAH', NULL, NULL,
    'TIDAK', '/uploads/mhs/datascience_cert.pdf', 'Sertifikat_Juara2_DataScience.pdf', 3407872, NULL,
    'PERHATIAN PRIVASI: Mahasiswa menolak nama pribadinya dipublikasikan di medsos terbuka (pemberitaan harap gunakan inisial tim).', 4
);
INSERT INTO entry_kemahasiswaan_details (
    entry_id, student_name, student_nim, prodi, cohort_year,
    event_name, organizer, event_location, event_date,
    achievement_rank, level, category, advisor_name
) VALUES (
    303, 'Nurul Hidayati', '220108044', 'Sistem Informasi', 2022,
    'National Data Science & AI Challenge 2026', 'Asosiasi Data Science Indonesia', 'Jakarta Selatan', '2026-09-29',
    'JUARA_2', 'NASIONAL', 'AKADEMIK', 'Anindya Kirana, M.Kom.'
);

-- =============================================================================
-- 4. MODUL ALUMNI KARIR (3 BARIS)
-- =============================================================================
-- ALUMNI 1: Signature, Penempatan Kerja Global, Siap Diolah -> Sudah Terbit
INSERT INTO reputation_entries (
    id, unit_code, period_id, tier_type, title, short_description,
    screening_status, screening_submitted_at, screening_by,
    comm_status, comm_processed_by, comm_published_url,
    name_publication_consent, attachment_path, attachment_name, attachment_size_bytes, external_url,
    notes, created_by
) VALUES (
    401, 'ALUMNI_KARIR', '2026-10', 'SIGNATURE',
    'Alumni UHN Berkarir sebagai Senior AI Infrastructure Engineer di Google Zurich',
    'Lulusan Teknik Informatika UHN tahun 2020 berhasil menembus posisi Senior Engineer di Google Zurich setelah bersaing ketat dalam seleksi global. Bertanggung jawab atas optimasi pipeline akselerasi TPU untuk pemodelan bahasa berskala besar. Bukti nyata daya saing kurikulum dan kualitas lulusan.',
    'SIAP_DIOLAH', '2026-10-03 10:15:00', 5,
    'SUDAH_TERBIT', 6, 'https://harkatnegeri.ac.id/berita/alumni-informatika-google-zurich',
    'YA', '/uploads/alumni/offer_photo_google.pdf', 'Bukti_Verifikasi_Google_Zurich.pdf', 2097152, 'https://linkedin.com/in/dimas-alumni-uhn',
    'Sangat berdampak untuk pemeringkatan QS Graduate Employability & WURI.', 5
);
INSERT INTO entry_alumni_details (entry_id, alumni_name, prodi, graduation_year, achievement_type, company_or_institution, position, testimonial)
VALUES (
    401, 'Dimas Surya Pratama, S.Kom.', 'Teknik Informatika', 2020,
    'PENEMPATAN_KERJA', 'Google Switzerland GmbH (Zurich HQ)', 'Senior AI Infrastructure Engineer',
    'Pondasi algoritma dan etos kerja yang ditempa dosen-dosen UHN memberi modal tangguh bagi saya untuk bersaing di panggung teknologi global.'
);

-- ALUMNI 2: Reguler, Wirausaha Berdampak Sosial, Siap Diolah -> Sedang Diolah
INSERT INTO reputation_entries (
    id, unit_code, period_id, tier_type, title, short_description,
    screening_status, screening_submitted_at, screening_by,
    comm_status, comm_processed_by, comm_published_url,
    name_publication_consent, attachment_path, attachment_name, attachment_size_bytes, external_url,
    notes, created_by
) VALUES (
    402, 'ALUMNI_KARIR', '2026-10', 'REGULER',
    'Pendiri Startup HydroNusantara Raih Pendanaan Seed Round Rp 3,5 Miliar',
    'Alumni Agribisnis mendirikan startup teknologi pertanian presisi berbasis IoT yang memberdayakan 150 petani hidroponik lokal. Baru saja mengamankan pendanaan tahap awal dari konsorsium venture capital terkemuka. Memberikan kontribusi nyata pada ketahanan pangan perkotaan.',
    'SIAP_DIOLAH', '2026-10-07 15:30:00', 5,
    'SEDANG_DIOLAH', 6, NULL,
    'YA', '/uploads/alumni/funding_hydronusantara.pdf', 'PressRelease_SeedFunding_HydroNusantara.pdf', 3670016, 'https://hydronusantara.id',
    'Disiapkan untuk artikel feature alumni wirausaha muda berdampak sosial.', 5
);
INSERT INTO entry_alumni_details (entry_id, alumni_name, prodi, graduation_year, achievement_type, company_or_institution, position, testimonial)
VALUES (
    402, 'Rian Firmansyah, S.P.', 'Agribisnis', 2021,
    'WIRAUSAHA', 'PT Hydro Nusantara Mandiri', 'Founder & Chief Executive Officer (CEO)',
    'Program inkubator bisnis kampus Harkat Negeri adalah batu loncatan pertama yang mengubah gagasan skripsi saya menjadi bisnis nyata.'
);

-- ALUMNI 3: Reguler, Studi Lanjut Prestisius, Draft
INSERT INTO reputation_entries (
    id, unit_code, period_id, tier_type, title, short_description,
    screening_status, screening_submitted_at, screening_by,
    comm_status, comm_processed_by, comm_published_url,
    name_publication_consent, attachment_path, attachment_name, attachment_size_bytes, external_url,
    notes, created_by
) VALUES (
    403, 'ALUMNI_KARIR', '2026-10', 'REGULER',
    'Lolos Beasiswa Penuh LPDP Magister di University of Oxford (M.Sc in Public Policy)',
    'Alumni Ilmu Pemerintahan berhasil meraih beasiswa bergengsi LPDP RI untuk studi lanjutan di Blavatnik School of Government, University of Oxford. Berkomitmen mendalami evaluasi kebijakan pengentasan kemiskinan berbasis data spasial. Menginspirasi adik-adik tingkat.',
    'DRAFT', NULL, NULL,
    'BELUM_DIOLAH', NULL, NULL,
    'YA', '/uploads/alumni/loa_oxford.pdf', 'LoA_Unconditional_Oxford_LPDP.pdf', 1258291, NULL,
    'Menunggu rilis resmi jadwal keberangkatan sebelum dipublikasikan.', 5
);
INSERT INTO entry_alumni_details (entry_id, alumni_name, prodi, graduation_year, achievement_type, company_or_institution, position, testimonial)
VALUES (
    403, 'Sarah Amanda, S.IP.', 'Ilmu Pemerintahan', 2023,
    'STUDI_LANJUT', 'University of Oxford (United Kingdom)', 'Awardee Master of Public Policy (MPP)',
    'Dukungan rekomendasi dosen serta kultur berpikir kritis di kampus menjadi pemantik utama keberhasilan seleksi wawancara beasiswa luar negeri.'
);
