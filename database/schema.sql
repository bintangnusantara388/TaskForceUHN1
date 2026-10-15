-- =============================================================================
-- SKEMA DATABASE: SISTEM INVENTARISASI DATA PENDUKUNG BRANDING & REPUTASI
-- Universitas Harkat Negeri - Tim Task Force Kerjasama & Reputasi
-- Target RDBMS: PostgreSQL 14+ / MySQL 8.0+ Compatible
-- =============================================================================

-- 1. TABEL MASTER: UNIT KERJA
CREATE TABLE master_units (
    unit_code VARCHAR(30) PRIMARY KEY, -- 'PPM', 'PSDM', 'KEMAHASISWAAN', 'ALUMNI_KARIR', 'KOMUNIKASI', 'TASK_FORCE'
    unit_name VARCHAR(150) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. TABEL MASTER: PERIODE PELAPORAN (Sep 2026 s.d. Agu 2027)
CREATE TABLE reporting_periods (
    period_id VARCHAR(7) PRIMARY KEY, -- Format 'YYYY-MM', misal '2026-09', '2026-10'
    display_name VARCHAR(50) NOT NULL, -- 'September 2026'
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE
);

-- 3. TABEL USER DAN AUTENTIKASI
CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone_number VARCHAR(20),
    role VARCHAR(30) NOT NULL, -- 'ADMIN_TASK_FORCE', 'PIC_UNIT', 'KOMUNIKASI', 'VIEWER'
    unit_code VARCHAR(30) REFERENCES master_units(unit_code),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. TABEL UTAMA / HEADER CAPAIAN (ENTITAS UMUM)
CREATE TABLE reputation_entries (
    id BIGSERIAL PRIMARY KEY,
    unit_code VARCHAR(30) NOT NULL REFERENCES master_units(unit_code),
    period_id VARCHAR(7) NOT NULL REFERENCES reporting_periods(period_id),
    tier_type VARCHAR(20) NOT NULL CHECK (tier_type IN ('SIGNATURE', 'REGULER')),
    title VARCHAR(300) NOT NULL,
    short_description TEXT NOT NULL, -- Validasi maks 3 kalimat di app level
    
    -- Status Workflow
    screening_status VARCHAR(30) NOT NULL DEFAULT 'DRAFT' 
        CHECK (screening_status IN ('DRAFT', 'SIAP_DIOLAH')),
    screening_submitted_at TIMESTAMP,
    screening_by BIGINT REFERENCES users(id),
    
    comm_status VARCHAR(30) NOT NULL DEFAULT 'BELUM_DIOLAH' 
        CHECK (comm_status IN ('BELUM_DIOLAH', 'SEDANG_DIOLAH', 'SUDAH_TERBIT')),
    comm_processed_by BIGINT REFERENCES users(id),
    comm_published_url VARCHAR(500),
    
    -- Privasi & Persetujuan
    name_publication_consent VARCHAR(10) NOT NULL DEFAULT 'YA' 
        CHECK (name_publication_consent IN ('YA', 'TIDAK')),
        
    -- Lampiran & Tautan Pendukung
    attachment_path VARCHAR(500),
    attachment_name VARCHAR(255),
    attachment_size_bytes BIGINT, -- Validasi maks 10MB (10485760 bytes)
    external_url VARCHAR(500),
    
    -- Catatan Internal
    notes TEXT,
    
    -- Audit Trail
    created_by BIGINT NOT NULL REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_by BIGINT REFERENCES users(id),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indeks performa pencarian dan filter
CREATE INDEX idx_entries_period_unit ON reputation_entries (period_id, unit_code);
CREATE INDEX idx_entries_screening ON reputation_entries (screening_status);
CREATE INDEX idx_entries_comm ON reputation_entries (comm_status);
CREATE INDEX idx_entries_tier ON reputation_entries (tier_type);

-- 5. DETAIL MODUL 1: PPM (Paten, HKI, Riset)
CREATE TABLE entry_ppm_details (
    entry_id BIGINT PRIMARY KEY REFERENCES reputation_entries(id) ON DELETE CASCADE,
    ki_type VARCHAR(50) NOT NULL 
        CHECK (ki_type IN ('PATEN', 'PATEN_SEDERHANA', 'HAK_CIPTA', 'MEREK', 'DESAIN_INDUSTRI', 'LAINNYA')),
    registration_number VARCHAR(100) NOT NULL,
    filing_date DATE NOT NULL,
    grant_date DATE,
    ki_status VARCHAR(30) NOT NULL 
        CHECK (ki_status IN ('DALAM_PROSES', 'TERDAFTAR', 'GRANTED'))
);

-- Tabel Inventor / Pencipta PPM (1 capaian bisa banyak inventor)
CREATE TABLE ppm_inventors (
    id BIGSERIAL PRIMARY KEY,
    entry_id BIGINT NOT NULL REFERENCES reputation_entries(id) ON DELETE CASCADE,
    name VARCHAR(150) NOT NULL,
    identifier VARCHAR(30) NOT NULL, -- NIDN atau NIM (hanya angka)
    prodi VARCHAR(100) NOT NULL,
    inventor_type VARCHAR(20) NOT NULL CHECK (inventor_type IN ('DOSEN', 'MAHASISWA')),
    sort_order INT DEFAULT 1
);

-- 6. DETAIL MODUL 2: PSDM (Capaian Pegawai)
CREATE TABLE entry_psdm_details (
    entry_id BIGINT PRIMARY KEY REFERENCES reputation_entries(id) ON DELETE CASCADE,
    employee_name VARCHAR(150) NOT NULL,
    employee_id VARCHAR(30) NOT NULL, -- NIP atau NIDN (hanya angka)
    unit_or_prodi VARCHAR(100) NOT NULL,
    employee_type VARCHAR(20) NOT NULL CHECK (employee_type IN ('DOSEN', 'TENDIK')),
    achievement_type VARCHAR(50) NOT NULL 
        CHECK (achievement_type IN ('SERTIFIKASI', 'JABATAN_FUNGSIONAL', 'PENGHARGAAN', 'NARASUMBER', 'PELATIHAN', 'LAINNYA')),
    issuer_organizer VARCHAR(200) NOT NULL,
    level VARCHAR(30) NOT NULL 
        CHECK (level IN ('LOKAL', 'NASIONAL', 'INTERNASIONAL')),
    achievement_date DATE NOT NULL
);

-- 7. DETAIL MODUL 3: KEMAHASISWAAN (Prestasi Mahasiswa)
CREATE TABLE entry_kemahasiswaan_details (
    entry_id BIGINT PRIMARY KEY REFERENCES reputation_entries(id) ON DELETE CASCADE,
    student_name VARCHAR(150) NOT NULL,
    student_nim VARCHAR(30) NOT NULL, -- NIM (hanya angka)
    prodi VARCHAR(100) NOT NULL,
    cohort_year INT NOT NULL, -- Angkatan
    event_name VARCHAR(250) NOT NULL,
    organizer VARCHAR(200) NOT NULL,
    event_location VARCHAR(150) NOT NULL,
    event_date DATE NOT NULL,
    achievement_rank VARCHAR(30) NOT NULL 
        CHECK (achievement_rank IN ('JUARA_1', 'JUARA_2', 'JUARA_3', 'HARAPAN', 'FINALIS', 'MEDALI_EMAS', 'MEDALI_PERAK', 'MEDALI_PERUNGGU', 'LAINNYA')),
    level VARCHAR(30) NOT NULL 
        CHECK (level IN ('KAMPUS', 'KABUPATEN_KOTA', 'PROVINSI', 'NASIONAL', 'INTERNASIONAL')),
    category VARCHAR(30) NOT NULL 
        CHECK (category IN ('AKADEMIK', 'NON_AKADEMIK')),
    advisor_name VARCHAR(150) -- Dosen pembimbing (opsional)
);

-- 8. DETAIL MODUL 4: ALUMNI KARIR
CREATE TABLE entry_alumni_details (
    entry_id BIGINT PRIMARY KEY REFERENCES reputation_entries(id) ON DELETE CASCADE,
    alumni_name VARCHAR(150) NOT NULL,
    prodi VARCHAR(100) NOT NULL,
    graduation_year INT NOT NULL,
    achievement_type VARCHAR(50) NOT NULL 
        CHECK (achievement_type IN ('PENEMPATAN_KERJA', 'WIRAUSAHA', 'STUDI_LANJUT', 'PENGHARGAAN', 'LAINNYA')),
    company_or_institution VARCHAR(200) NOT NULL,
    position VARCHAR(150) NOT NULL,
    testimonial TEXT
);

-- 9. AUDIT LOG & KEAMANAN
CREATE TABLE audit_logs (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT REFERENCES users(id),
    action VARCHAR(50) NOT NULL, -- 'LOGIN', 'CREATE', 'UPDATE', 'DELETE', 'VIEW_PII', 'EXPORT'
    table_name VARCHAR(50),
    record_id BIGINT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    old_values JSONB,
    new_values JSONB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 10. NOTIFIKASI PENGINGAT TARGET BULANAN
CREATE TABLE reminder_notifications (
    id BIGSERIAL PRIMARY KEY,
    period_id VARCHAR(7) REFERENCES reporting_periods(period_id),
    unit_code VARCHAR(30) REFERENCES master_units(unit_code),
    reminder_phase VARCHAR(20) CHECK (reminder_phase IN ('MID_MONTH', 'END_MONTH')),
    is_signature_fulfilled BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    recipient_email VARCHAR(100),
    status VARCHAR(20) DEFAULT 'SENT' -- 'SENT', 'FAILED'
);

-- 11. MANAJEMEN MULTI-AI ENGINE & AUTO-FAILOVER
CREATE TABLE ai_engines (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    provider_type VARCHAR(50) NOT NULL, -- 'GEMINI', 'OPENAI_COMPATIBLE', 'LOCAL_SYNTHESIZER'
    model_name VARCHAR(100) NOT NULL,
    api_key VARCHAR(255),
    endpoint_url TEXT,
    is_active SMALLINT DEFAULT 1,
    priority INTEGER DEFAULT 1,
    max_retries INTEGER DEFAULT 2,
    timeout_seconds INTEGER DEFAULT 6,
    temperature NUMERIC(3, 2) DEFAULT 0.40,
    max_tokens INTEGER DEFAULT 400,
    last_status VARCHAR(30) DEFAULT 'UNTESTED', -- 'HEALTHY', 'ERROR', 'UNTESTED', 'DISABLED'
    last_latency_ms INTEGER DEFAULT 0,
    last_checked_at TIMESTAMP,
    last_error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE ai_failover_logs (
    id BIGSERIAL PRIMARY KEY,
    request_type VARCHAR(50) NOT NULL,
    failed_engine_id BIGINT,
    failed_engine_name VARCHAR(150),
    error_message TEXT,
    retry_count INTEGER DEFAULT 0,
    rescued_engine_id BIGINT,
    rescued_engine_name VARCHAR(150),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
