<?php
// app/Models/Reminder.php
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Config.php';
require_once __DIR__ . '/Audit.php';

class Reminder {
    /**
     * Mengambil Progress Meter untuk seluruh unit pada suatu periode pelaporan
     */
    public static function getProgressMeter(string $periodId): array {
        $db = Database::getConnection();
        $targetUnits = ['PPM', 'PSDM', 'KEMAHASISWAAN', 'ALUMNI_KARIR'];
        $result = [];

        foreach ($targetUnits as $uCode) {
            $uName = UNITS[$uCode] ?? $uCode;

            // Total entri
            $stmt = $db->prepare("SELECT count(*) FROM reputation_entries WHERE unit_code = ? AND period_id = ?");
            $stmt->execute([$uCode, $periodId]);
            $totalEntries = (int)$stmt->fetchColumn();

            // Total Signature
            $stmt = $db->prepare("SELECT count(*) FROM reputation_entries WHERE unit_code = ? AND period_id = ? AND tier_type = 'SIGNATURE'");
            $stmt->execute([$uCode, $periodId]);
            $signatureCount = (int)$stmt->fetchColumn();

            // Siap Diolah
            $stmt = $db->prepare("SELECT count(*) FROM reputation_entries WHERE unit_code = ? AND period_id = ? AND screening_status = 'SIAP_DIOLAH'");
            $stmt->execute([$uCode, $periodId]);
            $siapDiolah = (int)$stmt->fetchColumn();

            // Belum Diolah oleh Komunikasi (dari data yang sudah siap diolah)
            $stmt = $db->prepare("SELECT count(*) FROM reputation_entries WHERE unit_code = ? AND period_id = ? AND screening_status = 'SIAP_DIOLAH' AND comm_status = 'BELUM_DIOLAH'");
            $stmt->execute([$uCode, $periodId]);
            $belumDiolahKom = (int)$stmt->fetchColumn();

            // Sedang Diolah
            $stmt = $db->prepare("SELECT count(*) FROM reputation_entries WHERE unit_code = ? AND period_id = ? AND comm_status = 'SEDANG_DIOLAH'");
            $stmt->execute([$uCode, $periodId]);
            $sedangDiolah = (int)$stmt->fetchColumn();

            // Sudah Terbit
            $stmt = $db->prepare("SELECT count(*) FROM reputation_entries WHERE unit_code = ? AND period_id = ? AND comm_status = 'SUDAH_TERBIT'");
            $stmt->execute([$uCode, $periodId]);
            $sudahTerbit = (int)$stmt->fetchColumn();

            $result[$uCode] = [
                'unit_code' => $uCode,
                'unit_name' => $uName,
                'total_entries' => $totalEntries,
                'signature_count' => $signatureCount,
                'is_signature_fulfilled' => ($signatureCount >= 1),
                'siap_diolah' => $siapDiolah,
                'belum_diolah_kom' => $belumDiolahKom,
                'sedang_diolah' => $sedangDiolah,
                'sudah_terbit' => $sudahTerbit,
            ];
        }

        return $result;
    }

    /**
     * Mengambil Detail Lengkap PIC Unit beserta Kepatuhan Target & Tautan Komunikasi Direct (Email + WA)
     */
    public static function getUnitPICDetails(string $periodId): array {
        $db = Database::getConnection();
        $meter = self::getProgressMeter($periodId);
        $periodLabel = PERIODS[$periodId] ?? $periodId;
        $details = [];

        $defaultPICs = [
            'PPM' => [
                'full_name' => 'Dr. Ir. Ahmad Baiquni, M.T.',
                'email' => 'pic_ppm@harkatnegeri.ac.id',
                'phone' => '6281234567801'
            ],
            'PSDM' => [
                'full_name' => 'Dr. Sri Wahyuni, M.Biotech.',
                'email' => 'pic_psdm@harkatnegeri.ac.id',
                'phone' => '6281234567802'
            ],
            'KEMAHASISWAAN' => [
                'full_name' => 'Andi Pratama Kusuma, S.T.',
                'email' => 'pic_kemhs@harkatnegeri.ac.id',
                'phone' => '6281234567803'
            ],
            'ALUMNI_KARIR' => [
                'full_name' => 'Dimas Surya Pratama, S.Kom.',
                'email' => 'pic_alumni@harkatnegeri.ac.id',
                'phone' => '6281234567804'
            ],
        ];

        foreach ($meter as $uCode => $m) {
            // Ambil PIC terdaftar di database
            $stmt = $db->prepare("SELECT id, username, full_name, email, phone, last_login_at FROM users WHERE role = 'PIC_UNIT' AND unit_code = ? AND is_active = 1 LIMIT 1");
            $stmt->execute([$uCode]);
            $userPic = $stmt->fetch();

            $picName = !empty($userPic['full_name']) ? $userPic['full_name'] : ($defaultPICs[$uCode]['full_name'] ?? "PIC {$uCode}");
            $picEmail = !empty($userPic['email']) ? $userPic['email'] : ($defaultPICs[$uCode]['email'] ?? "pic.{$uCode}@harkatnegeri.ac.id");
            $rawPhone = !empty($userPic['phone']) ? $userPic['phone'] : ($defaultPICs[$uCode]['phone'] ?? "6281200000000");

            // Normalisasi nomor HP untuk tautan WhatsApp internasional (format: 628...)
            $cleanPhone = preg_replace('/[^0-9]/', '', $rawPhone);
            if (str_starts_with($cleanPhone, '0')) {
                $cleanPhone = '62' . substr($cleanPhone, 1);
            }

            // Dynamic base URL portal
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8088';
            $portalUrl = "{$protocol}://{$host}/index.php?page=entries_create&unit_code={$uCode}";

            // Susun Pesan WhatsApp Personalisasi
            if (!$m['is_signature_fulfilled']) {
                $waText = "🔔 *PENGINGAT TARGET SIGNATURE BULANAN UHN*\n"
                    . "Periode: *{$periodLabel}*\n\n"
                    . "Yth. Bapak/Ibu *{$picName}*,\n"
                    . "Selaku PIC Unit *{$m['unit_name']}* Universitas Harkat Negeri.\n\n"
                    . "Berdasarkan rekapitulasi data ERP Task Force per " . date('d F Y') . ", unit Anda tercatat *BELUM MENYETORKAN CAPAIAN SIGNATURE* (capaian unggulan) untuk bulan {$periodLabel}.\n\n"
                    . "📌 *Ketentuan Wajib:* \n"
                    . "1. Setiap unit kerja wajib menyetor minimal 1 data Signature setiap bulan.\n"
                    . "2. Mohon pastikan data telah lolos screening unit (*SIAP DIOLAH*) agar langsung diproduksi oleh Bagian Komunikasi.\n\n"
                    . "🔗 *Tautan Input Langsung Unit {$uCode}:*\n"
                    . "{$portalUrl}\n\n"
                    . "Mohon kesediaannya untuk segera menindaklanjuti. Terima kasih atas dedikasi dan kerja sama Bapak/Ibu.\n\n"
                    . "_Administrator Tim Task Force Kerjasama & Reputasi Institusi UHN_";

                $emailSubject = "[URGENT] Pengingat Penyetoran Data Signature Periode {$periodLabel} - Unit {$uCode}";
                $emailBody = "Yth. Bapak/Ibu {$picName},\n\n"
                    . "Mengingatkan kembali bahwa per tanggal " . date('d F Y') . ", Unit {$m['unit_name']} tercatat belum menyetorkan data capaian berstatus 'SIGNATURE' untuk periode pelaporan {$periodLabel}.\n\n"
                    . "Sesuai SOP Branding & Reputasi Institusi, data capaian unggulan unit sangat dibutuhkan untuk bahan siaran pers media nasional dan pengisian borang WURI.\n\n"
                    . "Silakan akses portal input data pada tautan berikut:\n"
                    . "{$portalUrl}\n\n"
                    . "Terima kasih atas kerja samanya.\n\n"
                    . "Salam hormat,\n"
                    . "Administrator Tim Task Force Kerjasama & Reputasi Institusi\n"
                    . "Universitas Harkat Negeri";
            } else {
                $waText = "✅ *KONFIRMASI TARGET SIGNATURE TERPENUHI UHN*\n"
                    . "Periode: *{$periodLabel}*\n\n"
                    . "Yth. Bapak/Ibu *{$picName}* (PIC {$m['unit_name']}),\n\n"
                    . "Terima kasih banyak. Unit Anda telah memenuhi kewajiban setoran bulanan dengan *{$m['signature_count']} data Signature* aktif.\n\n"
                    . "Data telah masuk antrean kurasi Bagian Komunikasi untuk rilis media dan borang WURI.\n\n"
                    . "_Administrator Tim Task Force Kerjasama & Reputasi Institusi UHN_";

                $emailSubject = "Target Signature Periode {$periodLabel} Telah Terpenuhi - Unit {$uCode}";
                $emailBody = "Yth. Bapak/Ibu {$picName},\n\n"
                    . "Terima kasih atas penyetoran data capaian Signature untuk periode {$periodLabel}. Unit Anda telah memenuhi target kepatuhan bulanan ({$m['signature_count']} Signature).\n\n"
                    . "Salam hormat,\nAdministrator Tim Task Force Kerjasama & Reputasi Institusi UHN";
            }

            $waLink = "https://wa.me/{$cleanPhone}?text=" . rawurlencode($waText);

            $details[$uCode] = array_merge($m, [
                'pic_user_id' => $userPic['id'] ?? null,
                'pic_username' => $userPic['username'] ?? null,
                'pic_name' => $picName,
                'pic_email' => $picEmail,
                'pic_phone' => $cleanPhone,
                'pic_phone_display' => Helpers::formatPhoneNumber($cleanPhone),
                'pic_last_login' => $userPic['last_login_at'] ?? null,
                'wa_message' => $waText,
                'wa_link' => $waLink,
                'email_subject' => $emailSubject,
                'email_body' => $emailBody
            ]);
        }

        return $details;
    }

    /**
     * Menghitung ringkasan global universitas
     */
    public static function getGlobalMetrics(string $periodId): array {
        $meter = self::getProgressMeter($periodId);
        $totalEntries = 0;
        $unitsFulfilled = 0;
        $totalBelumDiolahKom = 0;
        $totalSudahTerbit = 0;

        foreach ($meter as $m) {
            $totalEntries += $m['total_entries'];
            if ($m['is_signature_fulfilled']) {
                $unitsFulfilled++;
            }
            $totalBelumDiolahKom += $m['belum_diolah_kom'];
            $totalSudahTerbit += $m['sudah_terbit'];
        }

        return [
            'total_entries' => $totalEntries,
            'units_fulfilled' => $unitsFulfilled,
            'units_total' => 4,
            'all_fulfilled' => ($unitsFulfilled === 4),
            'total_belum_diolah_kom' => $totalBelumDiolahKom,
            'total_sudah_terbit' => $totalSudahTerbit,
            'meter' => $meter
        ];
    }

    /**
     * Mengirim notifikasi pengingat DIRECT per PIC Unit (Admin Only)
     */
    public static function sendDirectReminder(string $unitCode, string $periodId, string $channel = 'EMAIL', string $phase = 'DIRECT_ADMIN'): array {
        $db = Database::getConnection();
        $unitDetails = self::getUnitPICDetails($periodId);

        if (!isset($unitDetails[$unitCode])) {
            return ['success' => false, 'message' => "Unit {$unitCode} tidak valid."];
        }

        $pic = $unitDetails[$unitCode];
        $isFulfilled = $pic['is_signature_fulfilled'] ? 1 : 0;
        $body = ($channel === 'WHATSAPP') ? $pic['wa_message'] : $pic['email_body'];

        $stmt = $db->prepare("INSERT INTO reminder_notifications 
            (period_id, unit_code, reminder_phase, is_signature_fulfilled, sent_at, recipient_name, recipient_email, recipient_phone, channel, status, message_body) 
            VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, ?, ?, ?, ?, 'SENT', ?)");
        $stmt->execute([
            $periodId,
            $unitCode,
            $phase,
            $isFulfilled,
            $pic['pic_name'],
            $pic['pic_email'],
            $pic['pic_phone'],
            $channel,
            $body
        ]);
        $notifId = (int)$db->lastInsertId();

        Audit::log('REMINDER_DIRECT', 'reminder_notifications', $notifId, 
            "Administrator mengirim pengingat target Signature langsung ke PIC {$unitCode} ({$pic['pic_name']}) via {$channel}");

        return [
            'success' => true,
            'notif_id' => $notifId,
            'pic' => $pic,
            'channel' => $channel,
            'message' => "Pengingat langsung berhasil dikirimkan ke PIC {$unitCode} ({$pic['pic_name']}) via {$channel}."
        ];
    }

    /**
     * Memproses pengiriman notifikasi pengingat email ke seluruh PIC unit yang belum setor (Admin Broadcast)
     */
    public static function triggerReminders(string $periodId, string $phase = 'MID_MONTH'): array {
        $db = Database::getConnection();
        $unitDetails = self::getUnitPICDetails($periodId);
        $periodLabel = PERIODS[$periodId] ?? $periodId;
        $dispatched = [];

        foreach ($unitDetails as $uCode => $data) {
            $isFulfilled = $data['is_signature_fulfilled'];
            
            if (!$isFulfilled) {
                $urgency = ($phase === 'END_MONTH') ? '[URGENT - AKHIR BULAN]' : '[PENGINGAT PERTENGAHAN BULAN]';
                $subject = "{$urgency} Setoran Capaian Unggulan (Signature) Periode {$periodLabel} - {$uCode}";
                $body = $data['email_body'];

                $stmt = $db->prepare("INSERT INTO reminder_notifications 
                    (period_id, unit_code, reminder_phase, is_signature_fulfilled, sent_at, recipient_name, recipient_email, recipient_phone, channel, status, message_body) 
                    VALUES (?, ?, ?, 0, CURRENT_TIMESTAMP, ?, ?, ?, 'EMAIL', 'SENT', ?)");
                $stmt->execute([
                    $periodId, 
                    $uCode, 
                    $phase, 
                    $data['pic_name'],
                    $data['pic_email'],
                    $data['pic_phone'],
                    $body
                ]);

                $dispatched[] = [
                    'unit_code' => $uCode,
                    'unit_name' => $data['unit_name'],
                    'pic_name' => $data['pic_name'],
                    'recipient' => $data['pic_email'],
                    'phone' => $data['pic_phone'],
                    'status' => 'TERKIRIM (PERINGATAN)',
                    'subject' => $subject,
                    'body' => $body
                ];
            } else {
                $dispatched[] = [
                    'unit_code' => $uCode,
                    'unit_name' => $data['unit_name'],
                    'pic_name' => $data['pic_name'],
                    'recipient' => $data['pic_email'],
                    'phone' => $data['pic_phone'],
                    'status' => 'LEWAT (TARGET SUDAH TERPENUHI ✓)',
                    'subject' => "Target Signature {$periodLabel} Sudah Terpenuhi",
                    'body' => "Unit telah memiliki {$data['signature_count']} data Signature aktif."
                ];
            }
        }

        return $dispatched;
    }

    /**
     * Mengambil riwayat pengiriman notifikasi pengingat
     */
    public static function getNotificationHistory(int $limit = 25): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT r.*, p.display_name as period_name, m.unit_name
            FROM reminder_notifications r
            LEFT JOIN reporting_periods p ON r.period_id = p.period_id
            LEFT JOIN master_units m ON r.unit_code = m.unit_code
            ORDER BY r.id DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}
