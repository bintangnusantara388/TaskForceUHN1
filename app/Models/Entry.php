<?php
// app/Models/Entry.php
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Config.php';
require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Helpers.php';
require_once __DIR__ . '/Audit.php';

class Entry {
    /**
     * Mengambil daftar entri capaian dengan filter dan pencarian
     */
    public static function getAll(array $filters = []): array {
        $db = Database::getConnection();
        $conditions = [];
        $params = [];

        // Filter Unit
        if (!empty($filters['unit_code'])) {
            $conditions[] = "e.unit_code = ?";
            $params[] = $filters['unit_code'];
        }

        // Filter Periode Bulan
        if (!empty($filters['period_id'])) {
            $conditions[] = "e.period_id = ?";
            $params[] = $filters['period_id'];
        }

        // Filter Tier (SIGNATURE / REGULER)
        if (!empty($filters['tier_type'])) {
            $conditions[] = "e.tier_type = ?";
            $params[] = $filters['tier_type'];
        }

        // Filter Status Screening (DRAFT / SIAP_DIOLAH)
        if (!empty($filters['screening_status'])) {
            $conditions[] = "e.screening_status = ?";
            $params[] = $filters['screening_status'];
        }

        // Filter Status Komunikasi (BELUM_DIOLAH / SEDANG_DIOLAH / SUDAH_TERBIT)
        if (!empty($filters['comm_status'])) {
            $conditions[] = "e.comm_status = ?";
            $params[] = $filters['comm_status'];
        }

        // Filter Persetujuan Publikasi Nama (YA / TIDAK)
        if (!empty($filters['consent'])) {
            $conditions[] = "e.name_publication_consent = ?";
            $params[] = $filters['consent'];
        }

        // Filter Kata Kunci Pencarian (Judul atau Catatan)
        if (!empty($filters['keyword'])) {
            $kw = "%" . trim($filters['keyword']) . "%";
            $conditions[] = "(e.title LIKE ? OR e.short_description LIKE ? OR e.notes LIKE ?)";
            $params[] = $kw;
            $params[] = $kw;
            $params[] = $kw;
        }

        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

        $sql = "
            SELECT e.*, 
                   u.unit_name,
                   p.display_name as period_name,
                   creator.full_name as creator_name,
                   comm_user.full_name as comm_processor_name
            FROM reputation_entries e
            LEFT JOIN master_units u ON e.unit_code = u.unit_code
            LEFT JOIN reporting_periods p ON e.period_id = p.period_id
            LEFT JOIN users creator ON e.created_by = creator.id
            LEFT JOIN users comm_user ON e.comm_processed_by = comm_user.id
            {$whereClause}
            ORDER BY e.created_at DESC
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $entries = $stmt->fetchAll();

        // Tambahkan detail spesifik modul untuk tiap entri
        foreach ($entries as &$entry) {
            $entry['details'] = self::getSpecificDetails($entry['id'], $entry['unit_code']);
        }

        return $entries;
    }

    /**
     * Mengambil detail lengkap entri beserta relasi modulnya
     */
    public static function find(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT e.*, 
                   u.unit_name,
                   p.display_name as period_name,
                   creator.full_name as creator_name,
                   comm_user.full_name as comm_processor_name
            FROM reputation_entries e
            LEFT JOIN master_units u ON e.unit_code = u.unit_code
            LEFT JOIN reporting_periods p ON e.period_id = p.period_id
            LEFT JOIN users creator ON e.created_by = creator.id
            LEFT JOIN users comm_user ON e.comm_processed_by = comm_user.id
            WHERE e.id = ?
        ");
        $stmt->execute([$id]);
        $entry = $stmt->fetch();
        if (!$entry) return null;

        $entry['details'] = self::getSpecificDetails($id, $entry['unit_code']);
        return $entry;
    }

    private static function getSpecificDetails(int $entryId, string $unitCode): array {
        $db = Database::getConnection();
        switch ($unitCode) {
            case 'PPM':
                $stmt = $db->prepare("SELECT * FROM entry_ppm_details WHERE entry_id = ?");
                $stmt->execute([$entryId]);
                $ppm = $stmt->fetch() ?: [];

                // Ambil daftar inventor
                $invStmt = $db->prepare("SELECT * FROM ppm_inventors WHERE entry_id = ? ORDER BY sort_order ASC, id ASC");
                $invStmt->execute([$entryId]);
                $ppm['inventors'] = $invStmt->fetchAll();
                return $ppm;

            case 'PSDM':
                $stmt = $db->prepare("SELECT * FROM entry_psdm_details WHERE entry_id = ?");
                $stmt->execute([$entryId]);
                return $stmt->fetch() ?: [];

            case 'KEMAHASISWAAN':
                $stmt = $db->prepare("SELECT * FROM entry_kemahasiswaan_details WHERE entry_id = ?");
                $stmt->execute([$entryId]);
                return $stmt->fetch() ?: [];

            case 'ALUMNI_KARIR':
                $stmt = $db->prepare("SELECT * FROM entry_alumni_details WHERE entry_id = ?");
                $stmt->execute([$entryId]);
                return $stmt->fetch() ?: [];

            default:
                return [];
        }
    }

    /**
     * Menyimpan data capaian baru dengan validasi
     */
    public static function create(array $data, ?array $file = null): array {
        $errors = self::validateCommon($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $unitCode = $data['unit_code'];
        $unitErrors = self::validateUnitSpecific($unitCode, $data);
        if (!empty($unitErrors)) {
            return ['success' => false, 'errors' => $unitErrors];
        }

        // Upload berkas jika ada
        $attachment = self::handleFileUpload($file);
        if ($attachment['error']) {
            return ['success' => false, 'errors' => ['attachment' => $attachment['error']]];
        }

        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $currentUser = Auth::user();
            $userId = $currentUser['id'] ?? 1;

            $stmt = $db->prepare("
                INSERT INTO reputation_entries (
                    unit_code, period_id, tier_type, title, short_description,
                    screening_status, screening_submitted_at, screening_by,
                    name_publication_consent, attachment_path, attachment_name, attachment_size_bytes,
                    external_url, notes, created_by
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?
                )
            ");

            $screeningStatus = $data['screening_status'] ?? 'DRAFT';
            $screeningDate = ($screeningStatus === 'SIAP_DIOLAH') ? date('Y-m-d H:i:s') : null;
            $screeningBy = ($screeningStatus === 'SIAP_DIOLAH') ? $userId : null;

            $stmt->execute([
                $unitCode,
                $data['period_id'],
                $data['tier_type'],
                trim($data['title']),
                trim($data['short_description']),
                $screeningStatus,
                $screeningDate,
                $screeningBy,
                $data['name_publication_consent'] ?? 'YA',
                $attachment['path'],
                $attachment['name'],
                $attachment['size'],
                trim($data['external_url'] ?? ''),
                trim($data['notes'] ?? ''),
                $userId
            ]);

            $entryId = (int)$db->lastInsertId();

            // Simpan detail unit spesifik
            self::saveUnitSpecificDetails($db, $entryId, $unitCode, $data);

            $db->commit();

            Audit::log('CREATE', 'reputation_entries', $entryId, "Membuat capaian baru unit {$unitCode} ('{$data['title']}')");

            return ['success' => true, 'entry_id' => $entryId];
        } catch (Exception $e) {
            $db->rollBack();
            return ['success' => false, 'errors' => ['general' => $e->getMessage()]];
        }
    }

    /**
     * Memperbarui data capaian
     */
    public static function update(int $id, array $data, ?array $file = null): array {
        $entry = self::find($id);
        if (!$entry) {
            return ['success' => false, 'errors' => ['general' => 'Data tidak ditemukan.']];
        }

        if (!Auth::canManageUnit($entry['unit_code'])) {
            return ['success' => false, 'errors' => ['auth' => 'Anda tidak memiliki hak akses mengubah data unit ini.']];
        }

        $errors = self::validateCommon($data);
        $unitCode = $entry['unit_code'];
        $unitErrors = self::validateUnitSpecific($unitCode, $data);
        $mergedErrors = array_merge($errors, $unitErrors);
        if (!empty($mergedErrors)) {
            return ['success' => false, 'errors' => $mergedErrors];
        }

        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $currentUser = Auth::user();
            $userId = $currentUser['id'] ?? 1;

            $attachment = self::handleFileUpload($file);
            $attachmentSql = "";
            $params = [
                $data['period_id'],
                $data['tier_type'],
                trim($data['title']),
                trim($data['short_description']),
                $data['name_publication_consent'] ?? 'YA',
                trim($data['external_url'] ?? ''),
                trim($data['notes'] ?? ''),
                $userId,
                date('Y-m-d H:i:s')
            ];

            if ($attachment['path']) {
                $attachmentSql = ", attachment_path = ?, attachment_name = ?, attachment_size_bytes = ?";
                $params[] = $attachment['path'];
                $params[] = $attachment['name'];
                $params[] = $attachment['size'];
            }

            // Status screening jika diubah oleh PIC
            $screeningStatus = $data['screening_status'] ?? $entry['screening_status'];
            $screeningDate = ($screeningStatus === 'SIAP_DIOLAH' && $entry['screening_status'] !== 'SIAP_DIOLAH') 
                ? date('Y-m-d H:i:s') : $entry['screening_submitted_at'];
            $screeningBy = ($screeningStatus === 'SIAP_DIOLAH') ? $userId : $entry['screening_by'];

            $sql = "
                UPDATE reputation_entries SET
                    period_id = ?, tier_type = ?, title = ?, short_description = ?,
                    name_publication_consent = ?, external_url = ?, notes = ?,
                    updated_by = ?, updated_at = ?,
                    screening_status = ?, screening_submitted_at = ?, screening_by = ?
                    {$attachmentSql}
                WHERE id = ?
            ";

            $params[] = $screeningStatus;
            $params[] = $screeningDate;
            $params[] = $screeningBy;
            $params[] = $id;

            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            // Update spesifik unit
            self::updateUnitSpecificDetails($db, $id, $unitCode, $data);

            $db->commit();

            Audit::log('UPDATE', 'reputation_entries', $id, "Memperbarui entri #{$id} unit {$unitCode}");

            return ['success' => true, 'entry_id' => $id];
        } catch (Exception $e) {
            $db->rollBack();
            return ['success' => false, 'errors' => ['general' => $e->getMessage()]];
        }
    }

    /**
     * Memperbarui status kurasi oleh Bagian Komunikasi
     */
    public static function updateKomunikasi(int $id, string $commStatus, ?string $publishedUrl, ?string $notes): array {
        if (!Auth::canCurateKomunikasi()) {
            return ['success' => false, 'errors' => ['auth' => 'Hanya Bagian Komunikasi atau Admin yang dapat memutakhirkan status pengolahan.']];
        }

        $db = Database::getConnection();
        $currentUser = Auth::user();
        $userId = $currentUser['id'] ?? 1;

        $stmt = $db->prepare("
            UPDATE reputation_entries SET
                comm_status = ?,
                comm_published_url = ?,
                comm_processed_by = ?,
                notes = CASE WHEN ? != '' THEN ? ELSE notes END,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([
            $commStatus,
            trim($publishedUrl ?? ''),
            $userId,
            trim($notes ?? ''),
            trim($notes ?? ''),
            $id
        ]);

        Audit::log('KOMUNIKASI_PROCESS', 'reputation_entries', $id, "Ubah status komunikasi menjadi {$commStatus}");

        return ['success' => true];
    }

    /**
     * Memperbarui status screening (Draft <-> Siap Diolah)
     */
    public static function updateScreening(int $id, string $screeningStatus): array {
        $entry = self::find($id);
        if (!$entry || !Auth::canManageUnit($entry['unit_code'])) {
            return ['success' => false, 'errors' => ['auth' => 'Tidak berhak mengubah status screening unit ini.']];
        }

        $db = Database::getConnection();
        $currentUser = Auth::user();
        $userId = $currentUser['id'] ?? 1;

        $submittedAt = ($screeningStatus === 'SIAP_DIOLAH') ? date('Y-m-d H:i:s') : null;

        $stmt = $db->prepare("
            UPDATE reputation_entries SET
                screening_status = ?,
                screening_submitted_at = ?,
                screening_by = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$screeningStatus, $submittedAt, $userId, $id]);

        Audit::log('SCREENING', 'reputation_entries', $id, "Status screening diubah ke {$screeningStatus}");

        return ['success' => true];
    }

    // =========================================================================
    // INTERNAL HELPERS & VALIDATORS
    // =========================================================================

    private static function validateCommon(array $data): array {
        $errors = [];
        if (empty($data['period_id']) || !isset(PERIODS[$data['period_id']])) {
            $errors['period_id'] = 'Bulan pelaporan wajib dipilih dari daftar (Sep 2026 - Agu 2027).';
        }
        if (empty($data['tier_type']) || !in_array($data['tier_type'], ['SIGNATURE', 'REGULER'])) {
            $errors['tier_type'] = 'Status tier wajib dipilih (Signature atau Reguler).';
        }
        if (empty(trim($data['title'] ?? ''))) {
            $errors['title'] = 'Judul capaian wajib diisi.';
        }
        $desc = trim($data['short_description'] ?? '');
        if (empty($desc)) {
            $errors['short_description'] = 'Deskripsi singkat wajib diisi.';
        } else {
            $sentences = Helpers::countSentences($desc);
            if ($sentences > 3) {
                $errors['short_description'] = "Deskripsi maksimal 3 kalimat bahasa awam (terdeteksi: {$sentences} kalimat).";
            }
        }
        return $errors;
    }

    private static function validateUnitSpecific(string $unitCode, array $data): array {
        $errors = [];

        switch ($unitCode) {
            case 'PPM':
                if (empty($data['ki_type'])) $errors['ki_type'] = 'Jenis kekayaan intelektual wajib dipilih.';
                if (empty(trim($data['registration_number'] ?? ''))) $errors['registration_number'] = 'Nomor pendaftaran/sertifikat wajib diisi.';
                if (empty($data['filing_date'])) $errors['filing_date'] = 'Tanggal didaftarkan wajib diisi.';
                if (empty($data['ki_status'])) $errors['ki_status'] = 'Status KI wajib dipilih.';

                // Validasi Inventor
                $names = $data['inventor_name'] ?? [];
                $identifiers = $data['inventor_identifier'] ?? [];
                $prodis = $data['inventor_prodi'] ?? [];
                $types = $data['inventor_type'] ?? [];

                if (empty($names) || empty(trim($names[0] ?? ''))) {
                    $errors['inventors'] = 'Minimal harus mencantumkan 1 pencipta/inventor.';
                } else {
                    foreach ($identifiers as $idx => $idVal) {
                        if (!empty($names[$idx]) && !Helpers::isValidNumericIdentifier($idVal)) {
                            $errors["inventor_identifier_{$idx}"] = "NIDN atau NIM pencipta ke-" . ($idx + 1) . " hanya boleh berisi angka (tanpa huruf/spasi).";
                        }
                    }
                }
                break;

            case 'PSDM':
                if (empty(trim($data['employee_name'] ?? ''))) $errors['employee_name'] = 'Nama pegawai wajib diisi.';
                $empId = trim($data['employee_id'] ?? '');
                if (empty($empId)) {
                    $errors['employee_id'] = 'NIP atau NIDN wajib diisi.';
                } elseif (!Helpers::isValidNumericIdentifier($empId)) {
                    $errors['employee_id'] = 'NIP atau NIDN hanya boleh berisi angka.';
                }
                if (empty(trim($data['unit_or_prodi'] ?? ''))) $errors['unit_or_prodi'] = 'Unit atau prodi pegawai wajib diisi.';
                if (empty($data['employee_type'])) $errors['employee_type'] = 'Jenis pegawai wajib dipilih (Dosen / Tendik).';
                if (empty($data['achievement_type'])) $errors['achievement_type'] = 'Jenis capaian wajib dipilih.';
                if (empty(trim($data['issuer_organizer'] ?? ''))) $errors['issuer_organizer'] = 'Pemberi / penyelenggara wajib diisi.';
                if (empty($data['level'])) $errors['level'] = 'Tingkat wajib dipilih (Lokal / Nasional / Internasional).';
                if (empty($data['achievement_date'])) $errors['achievement_date'] = 'Tanggal capaian wajib diisi.';
                break;

            case 'KEMAHASISWAAN':
                if (empty(trim($data['student_name'] ?? ''))) $errors['student_name'] = 'Nama mahasiswa wajib diisi.';
                $nim = trim($data['student_nim'] ?? '');
                if (empty($nim)) {
                    $errors['student_nim'] = 'NIM mahasiswa wajib diisi.';
                } elseif (!Helpers::isValidNumericIdentifier($nim)) {
                    $errors['student_nim'] = 'NIM hanya boleh berisi angka.';
                }
                if (empty(trim($data['prodi'] ?? ''))) $errors['prodi'] = 'Program studi wajib diisi.';
                if (empty($data['cohort_year']) || !is_numeric($data['cohort_year'])) $errors['cohort_year'] = 'Angkatan wajib diisi angka tahun (misal: 2023).';
                if (empty(trim($data['event_name'] ?? ''))) $errors['event_name'] = 'Nama kegiatan/lomba wajib diisi.';
                if (empty(trim($data['organizer'] ?? ''))) $errors['organizer'] = 'Penyelenggara wajib diisi.';
                if (empty(trim($data['event_location'] ?? ''))) $errors['event_location'] = 'Lokasi kegiatan wajib diisi.';
                if (empty($data['event_date'])) $errors['event_date'] = 'Tanggal kegiatan wajib diisi.';
                if (empty($data['achievement_rank'])) $errors['achievement_rank'] = 'Jenis capaian juara wajib dipilih.';
                if (empty($data['level'])) $errors['level'] = 'Tingkat kejuaraan wajib dipilih.';
                if (empty($data['category'])) $errors['category'] = 'Kategori wajib dipilih (Akademik / Non-Akademik).';
                break;

            case 'ALUMNI_KARIR':
                if (empty(trim($data['alumni_name'] ?? ''))) $errors['alumni_name'] = 'Nama alumni wajib diisi.';
                if (empty(trim($data['prodi'] ?? ''))) $errors['prodi'] = 'Program studi wajib diisi.';
                if (empty($data['graduation_year']) || !is_numeric($data['graduation_year'])) $errors['graduation_year'] = 'Tahun kelulusan wajib berupa angka tahun.';
                if (empty($data['achievement_type'])) $errors['achievement_type'] = 'Jenis capaian alumni wajib dipilih.';
                if (empty(trim($data['company_or_institution'] ?? ''))) $errors['company_or_institution'] = 'Instansi / perusahaan wajib diisi.';
                if (empty(trim($data['position'] ?? ''))) $errors['position'] = 'Posisi / jabatan alumni wajib diisi.';
                break;
        }

        return $errors;
    }

    private static function handleFileUpload(?array $file): array {
        if (!$file || empty($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['path' => null, 'name' => null, 'size' => null, 'error' => null];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['path' => null, 'name' => null, 'size' => null, 'error' => 'Gagal mengunggah berkas. Kode error: ' . $file['error']];
        }

        if ($file['size'] > MAX_UPLOAD_SIZE) {
            return ['path' => null, 'name' => null, 'size' => null, 'error' => 'Ukuran berkas melebihi batas maksimal 10 MB (' . Helpers::formatBytes($file['size']) . ').'];
        }

        $allowedExts = ['pdf', 'jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts)) {
            return ['path' => null, 'name' => null, 'size' => null, 'error' => 'Format berkas tidak didukung. Harap unggah berkas PDF atau Foto (JPG/PNG).'];
        }

        $safeName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $file['name']);
        $uniqueFilename = time() . '_' . uniqid() . '_' . $safeName;
        $destPath = UPLOAD_DIR . '/' . $uniqueFilename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            return ['path' => null, 'name' => null, 'size' => null, 'error' => 'Gagal menyimpan berkas ke direktori server.'];
        }

        return [
            'path' => '/uploads/' . $uniqueFilename,
            'name' => $file['name'],
            'size' => $file['size'],
            'error' => null
        ];
    }

    private static function saveUnitSpecificDetails(PDO $db, int $entryId, string $unitCode, array $data): void {
        switch ($unitCode) {
            case 'PPM':
                $stmt = $db->prepare("INSERT INTO entry_ppm_details VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $entryId,
                    $data['ki_type'],
                    trim($data['registration_number']),
                    $data['filing_date'],
                    !empty($data['grant_date']) ? $data['grant_date'] : null,
                    $data['ki_status']
                ]);

                // Simpan list inventor
                $names = $data['inventor_name'] ?? [];
                $identifiers = $data['inventor_identifier'] ?? [];
                $prodis = $data['inventor_prodi'] ?? [];
                $types = $data['inventor_type'] ?? [];

                $invStmt = $db->prepare("INSERT INTO ppm_inventors (entry_id, name, identifier, prodi, inventor_type, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
                foreach ($names as $idx => $n) {
                    if (!empty(trim($n))) {
                        $invStmt->execute([
                            $entryId,
                            trim($n),
                            trim($identifiers[$idx] ?? ''),
                            trim($prodis[$idx] ?? ''),
                            $types[$idx] ?? 'DOSEN',
                            $idx + 1
                        ]);
                    }
                }
                break;

            case 'PSDM':
                $stmt = $db->prepare("INSERT INTO entry_psdm_details VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $entryId,
                    trim($data['employee_name']),
                    trim($data['employee_id']),
                    trim($data['unit_or_prodi']),
                    $data['employee_type'],
                    $data['achievement_type'],
                    trim($data['issuer_organizer']),
                    $data['level'],
                    $data['achievement_date']
                ]);
                break;

            case 'KEMAHASISWAAN':
                $stmt = $db->prepare("INSERT INTO entry_kemahasiswaan_details VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $entryId,
                    trim($data['student_name']),
                    trim($data['student_nim']),
                    trim($data['prodi']),
                    (int)$data['cohort_year'],
                    trim($data['event_name']),
                    trim($data['organizer']),
                    trim($data['event_location']),
                    $data['event_date'],
                    $data['achievement_rank'],
                    $data['level'],
                    $data['category'],
                    trim($data['advisor_name'] ?? '')
                ]);
                break;

            case 'ALUMNI_KARIR':
                $stmt = $db->prepare("INSERT INTO entry_alumni_details VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $entryId,
                    trim($data['alumni_name']),
                    trim($data['prodi']),
                    (int)$data['graduation_year'],
                    $data['achievement_type'],
                    trim($data['company_or_institution']),
                    trim($data['position']),
                    trim($data['testimonial'] ?? '')
                ]);
                break;
        }
    }

    private static function updateUnitSpecificDetails(PDO $db, int $entryId, string $unitCode, array $data): void {
        switch ($unitCode) {
            case 'PPM':
                $stmt = $db->prepare("
                    UPDATE entry_ppm_details SET
                        ki_type = ?, registration_number = ?, filing_date = ?,
                        grant_date = ?, ki_status = ?
                    WHERE entry_id = ?
                ");
                $stmt->execute([
                    $data['ki_type'],
                    trim($data['registration_number']),
                    $data['filing_date'],
                    !empty($data['grant_date']) ? $data['grant_date'] : null,
                    $data['ki_status'],
                    $entryId
                ]);

                // Reset dan simpan ulang inventor
                $db->prepare("DELETE FROM ppm_inventors WHERE entry_id = ?")->execute([$entryId]);
                $names = $data['inventor_name'] ?? [];
                $identifiers = $data['inventor_identifier'] ?? [];
                $prodis = $data['inventor_prodi'] ?? [];
                $types = $data['inventor_type'] ?? [];

                $invStmt = $db->prepare("INSERT INTO ppm_inventors (entry_id, name, identifier, prodi, inventor_type, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
                foreach ($names as $idx => $n) {
                    if (!empty(trim($n))) {
                        $invStmt->execute([
                            $entryId,
                            trim($n),
                            trim($identifiers[$idx] ?? ''),
                            trim($prodis[$idx] ?? ''),
                            $types[$idx] ?? 'DOSEN',
                            $idx + 1
                        ]);
                    }
                }
                break;

            case 'PSDM':
                $stmt = $db->prepare("
                    UPDATE entry_psdm_details SET
                        employee_name = ?, employee_id = ?, unit_or_prodi = ?,
                        employee_type = ?, achievement_type = ?, issuer_organizer = ?,
                        level = ?, achievement_date = ?
                    WHERE entry_id = ?
                ");
                $stmt->execute([
                    trim($data['employee_name']),
                    trim($data['employee_id']),
                    trim($data['unit_or_prodi']),
                    $data['employee_type'],
                    $data['achievement_type'],
                    trim($data['issuer_organizer']),
                    $data['level'],
                    $data['achievement_date'],
                    $entryId
                ]);
                break;

            case 'KEMAHASISWAAN':
                $stmt = $db->prepare("
                    UPDATE entry_kemahasiswaan_details SET
                        student_name = ?, student_nim = ?, prodi = ?, cohort_year = ?,
                        event_name = ?, organizer = ?, event_location = ?, event_date = ?,
                        achievement_rank = ?, level = ?, category = ?, advisor_name = ?
                    WHERE entry_id = ?
                ");
                $stmt->execute([
                    trim($data['student_name']),
                    trim($data['student_nim']),
                    trim($data['prodi']),
                    (int)$data['cohort_year'],
                    trim($data['event_name']),
                    trim($data['organizer']),
                    trim($data['event_location']),
                    $data['event_date'],
                    $data['achievement_rank'],
                    $data['level'],
                    $data['category'],
                    trim($data['advisor_name'] ?? ''),
                    $entryId
                ]);
                break;

            case 'ALUMNI_KARIR':
                $stmt = $db->prepare("
                    UPDATE entry_alumni_details SET
                        alumni_name = ?, prodi = ?, graduation_year = ?,
                        achievement_type = ?, company_or_institution = ?, position = ?,
                        testimonial = ?
                    WHERE entry_id = ?
                ");
                $stmt->execute([
                    trim($data['alumni_name']),
                    trim($data['prodi']),
                    (int)$data['graduation_year'],
                    $data['achievement_type'],
                    trim($data['company_or_institution']),
                    trim($data['position']),
                    trim($data['testimonial'] ?? ''),
                    $entryId
                ]);
                break;
        }
    }

    /**
     * Menghapus data capaian beserta relasi detailnya
     */
    public static function delete(int $id): bool {
        $entry = self::find($id);
        if (!$entry || !Auth::canManageUnit($entry['unit_code'])) {
            return false;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM reputation_entries WHERE id = ?");
        $stmt->execute([$id]);

        Audit::log('DELETE', 'reputation_entries', $id, "Menghapus capaian #{$id} unit {$entry['unit_code']}");
        return true;
    }

    /**
     * Mengelompokkan data berdasarkan klaster borang WURI
     */
    public static function getWuriGrouped(string $periodId = '', string $unitCode = ''): array {
        $filters = ['screening_status' => 'SIAP_DIOLAH'];
        if (!empty($periodId)) {
            $filters['period_id'] = $periodId;
        }
        if (!empty($unitCode)) {
            $filters['unit_code'] = $unitCode;
        }
        $entries = self::getAll($filters);

        $clusters = [
            'A1_INDUSTRIAL' => [
                'code' => 'A1',
                'title' => 'A1. Industrial Application & Tech Transfer',
                'description' => 'Hilirisasi riset, paten industri, serta wirausaha alumni berdampak ekonomi.',
                'items' => []
            ],
            'B1_MOBILITY' => [
                'code' => 'B1',
                'title' => 'B1. Student Mobility & Global Openness',
                'description' => 'Prestasi kejuaraan internasional mahasiswa dan alumni studi lanjut beasiswa bergengsi.',
                'items' => []
            ],
            'C1_ETHICS' => [
                'code' => 'C1',
                'title' => 'C1. Crisis Management, Ethics & Leadership',
                'description' => 'Kepemimpinan akademik, narasumber forum global (UNESCO), dan sertifikasi tata kelola.',
                'items' => []
            ]
        ];

        foreach ($entries as $e) {
            if ($e['unit_code'] === 'PPM') {
                $clusters['A1_INDUSTRIAL']['items'][] = $e;
            } elseif ($e['unit_code'] === 'ALUMNI_KARIR') {
                $ach = $e['details']['achievement_type'] ?? '';
                if ($ach === 'WIRAUSAHA') {
                    $clusters['A1_INDUSTRIAL']['items'][] = $e;
                } else {
                    $clusters['B1_MOBILITY']['items'][] = $e;
                }
            } elseif ($e['unit_code'] === 'KEMAHASISWAAN') {
                $clusters['B1_MOBILITY']['items'][] = $e;
            } elseif ($e['unit_code'] === 'PSDM') {
                $clusters['C1_ETHICS']['items'][] = $e;
            }
        }

        return $clusters;
    }

    /**
     * Mengambil data statistik visual untuk render Chart.js di Dashboard
     */
    public static function getChartData(string $periodId): array {
        $db = Database::getConnection();
        $targetUnits = ['PPM', 'PSDM', 'KEMAHASISWAAN', 'ALUMNI_KARIR'];
        
        $signatureCounts = [];
        $regulerCounts = [];

        foreach ($targetUnits as $u) {
            $stmt = $db->prepare("SELECT count(*) FROM reputation_entries WHERE unit_code = ? AND period_id = ? AND tier_type = 'SIGNATURE'");
            $stmt->execute([$u, $periodId]);
            $signatureCounts[] = (int)$stmt->fetchColumn();

            $stmt = $db->prepare("SELECT count(*) FROM reputation_entries WHERE unit_code = ? AND period_id = ? AND tier_type = 'REGULER'");
            $stmt->execute([$u, $periodId]);
            $regulerCounts[] = (int)$stmt->fetchColumn();
        }

        // Status kurasi komunikasi
        $stmt = $db->prepare("SELECT count(*) FROM reputation_entries WHERE period_id = ? AND comm_status = 'BELUM_DIOLAH' AND screening_status = 'SIAP_DIOLAH'");
        $stmt->execute([$periodId]);
        $commBelum = (int)$stmt->fetchColumn();

        $stmt = $db->prepare("SELECT count(*) FROM reputation_entries WHERE period_id = ? AND comm_status = 'SEDANG_DIOLAH'");
        $stmt->execute([$periodId]);
        $commSedang = (int)$stmt->fetchColumn();

        $stmt = $db->prepare("SELECT count(*) FROM reputation_entries WHERE period_id = ? AND comm_status = 'SUDAH_TERBIT'");
        $stmt->execute([$periodId]);
        $commTerbit = (int)$stmt->fetchColumn();

        return [
            'units' => $targetUnits,
            'signature' => $signatureCounts,
            'reguler' => $regulerCounts,
            'comm_labels' => ['Belum Diolah', 'Sedang Diolah', 'Sudah Terbit'],
            'comm_values' => [$commBelum, $commSedang, $commTerbit]
        ];
    }
}
