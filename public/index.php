<?php
// public/index.php
require_once __DIR__ . '/../app/Config.php';
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Models/Entry.php';
require_once __DIR__ . '/../app/Models/Reminder.php';
require_once __DIR__ . '/../app/Models/Audit.php';
require_once __DIR__ . '/../app/Services/AIService.php';

Auth::startSession();

$action = $_GET['action'] ?? ($_POST['action'] ?? null);
$page = $_GET['page'] ?? 'dashboard';

// =============================================================================
// CONTROLLER: AUTHENTICATION ACTIONS (Bisa diakses publik/unauthenticated)
// =============================================================================

// 1. Proses Login Form (Autentikasi Ketat Bcrypt & Lockout)
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Keamanan Sesi Tunggal: Cegah multi-user dalam 1 sesi
    if (Auth::check()) {
        $_SESSION['flash_error'] = "Anda saat ini sudah masuk sebagai " . Auth::user()['name'] . ". Hanya diperbolehkan 1 akun pengguna dalam satu sesi. Silakan logout terlebih dahulu.";
        header("Location: /index.php?page=dashboard");
        exit;
    }

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $res = Auth::attemptLogin($username, $password);
    if ($res['success']) {
        $_SESSION['flash_success'] = "Selamat datang kembali, " . Auth::user()['name'] . " (" . Auth::user()['role_label'] . ")!";
        header("Location: /index.php?page=dashboard");
        exit;
    } else {
        $_SESSION['flash_error'] = $res['message'];
        header("Location: /index.php?page=login");
        exit;
    }
}

// 2. Quick Demo Login (1-Klik untuk Kemudahan Pengujian Role Saat Belum Login)
if ($action === 'login_quick') {
    // Keamanan Sesi Tunggal: Cegah pengalihan akun tanpa logout
    if (Auth::check()) {
        $_SESSION['flash_error'] = "Sesi aktif sedang digunakan oleh " . Auth::user()['name'] . ". Hanya diperbolehkan 1 akun pengguna dalam satu sesi. Silakan logout terlebih dahulu.";
        header("Location: /index.php?page=dashboard");
        exit;
    }

    $userId = $_GET['user_id'] ?? 'admin_tf';
    if (Auth::quickLogin($userId)) {
        $_SESSION['flash_success'] = "Berhasil masuk sebagai: " . Auth::user()['role_label'] . " (" . Auth::user()['name'] . ")";
        header("Location: /index.php?page=dashboard");
        exit;
    } else {
        $_SESSION['flash_error'] = "Gagal login dengan akun tersebut atau akun dinonaktifkan.";
        header("Location: /index.php?page=login");
        exit;
    }
}

// 3. Proses Logout (Keluar Aman)
if ($action === 'logout') {
    if (Auth::check()) {
        $u = Auth::user();
        Audit::log('LOGOUT', 'users', $u['id'] ?? null, "User {$u['username']} keluar dari sistem ERP");
        Auth::logout();
        $_SESSION['flash_success'] = "Anda telah berhasil keluar dari sistem ERP. Sesi aman diakhiri.";
    }
    header("Location: /index.php?page=login");
    exit;
}

// =============================================================================
// SESSION AUTHENTICATION GUARD
// =============================================================================

// Jika halaman yang dituju adalah login
if ($page === 'login') {
    require_once __DIR__ . '/../views/auth/login.php';
    exit;
}

// Jika belum login, paksa ke halaman login
if (!Auth::check()) {
    $_SESSION['flash_error'] = "Sesi Anda belum aktif. Silakan masuk untuk mengakses portal ERP.";
    header("Location: /index.php?page=login");
    exit;
}

// =============================================================================
// CONTROLLER: AUTHENTICATED ACTIONS (Strict RBAC Protection)
// =============================================================================
if ($action) {

    // Keamanan Sesi Tunggal: Dilarang beralih akun dalam satu sesi aktif
    if ($action === 'switch_user') {
        $_SESSION['flash_error'] = "Kebijakan Keamanan: Hanya diperbolehkan 1 akun pengguna dalam satu sesi aktif. Silakan keluar (Logout) terlebih dahulu untuk masuk dengan akun lain.";
        header("Location: /index.php?page=dashboard");
        exit;
    }

    // =========================================================================
    // TEKNOLOGI AI: GENERATOR NARASI 3 KALIMAT & RILIS PERS OTOMATIS
    // =========================================================================

    // 1. Endpoint AJAX: Generate Narasi 3 Kalimat Bahasa Awam
    if ($action === 'ai_generate_narrative') {
        header('Content-Type: application/json; charset=utf-8');
        
        $rawInput = file_get_contents('php://input');
        $jsonData = json_decode($rawInput, true);
        $payload = is_array($jsonData) ? $jsonData : $_POST;
        
        $tone = $payload['tone'] ?? 'STANDAR';
        $result = AIService::generateNarrative($payload, $tone);
        
        Audit::log('AI_GENERATE', 'ai_assistant', null, "Generate narasi AI unit {$payload['unit_code']} (Tone: {$tone}, Provider: {$result['provider']})");
        
        echo json_encode($result);
        exit;
    }

    // 2. Endpoint AJAX: Generate Siaran Pers Lengkap untuk Bagian Komunikasi
    if ($action === 'ai_generate_press_release') {
        header('Content-Type: application/json; charset=utf-8');
        
        $id = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));
        if ($id > 0) {
            $entry = Entry::find($id);
            if (!$entry) {
                echo json_encode(['success' => false, 'message' => 'Data entri tidak ditemukan.']);
                exit;
            }
            $pr = AIService::generatePressRelease($entry);
            Audit::log('AI_PRESS_RELEASE', 'reputation_entries', $id, "Generate rilis pers AI untuk capaian #{$id}");
            echo json_encode(array_merge(['success' => true, 'entry' => $entry], $pr));
            exit;
        }

        $rawInput = file_get_contents('php://input');
        $jsonData = json_decode($rawInput, true);
        $payload = is_array($jsonData) ? $jsonData : $_POST;
        $pr = AIService::generatePressRelease($payload);
        echo json_encode(array_merge(['success' => true], $pr));
        exit;
    }

    // 3. Simpan Pengaturan Konfigurasi AI (Admin Only)
    if ($action === 'ai_config_save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!Auth::isAdmin()) {
            http_response_code(403);
            $_SESSION['flash_error'] = "Akses Ditolak: Hanya Admin Task Force yang dapat mengubah konfigurasi AI.";
            header("Location: /index.php?page=dashboard");
            exit;
        }

        AIService::setSetting('gemini_api_key', trim($_POST['gemini_api_key'] ?? ''));
        AIService::setSetting('ai_model', $_POST['ai_model'] ?? 'gemini-1.5-flash');
        AIService::setSetting('ai_default_tone', $_POST['ai_default_tone'] ?? 'STANDAR');
        AIService::setSetting('ai_temperature', $_POST['ai_temperature'] ?? '0.4');
        AIService::setSetting('ai_enable_fallback', !empty($_POST['ai_enable_fallback']) ? '1' : '0');
        AIService::setSetting('ai_max_tokens', $_POST['ai_max_tokens'] ?? '400');
        AIService::setSetting('ai_system_instructions', trim($_POST['ai_system_instructions'] ?? ''));

        Audit::log('AI_CONFIG_UPDATE', 'system_settings', null, "Administrator memperbarui konfigurasi AI (Model: " . ($_POST['ai_model'] ?? 'gemini-1.5-flash') . ")");

        $_SESSION['flash_success'] = "Konfigurasi dan parameter teknologi AI berhasil disimpan ke sistem.";
        header("Location: /index.php?page=ai_config");
        exit;
    }

    // 4. Uji Koneksi Live Google Gemini AI (Admin Only - AJAX)
    if ($action === 'ai_test_connection') {
        if (!Auth::isAdmin()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Akses Ditolak: Fitur ini khusus Administrator.']);
            exit;
        }

        $rawInput = file_get_contents('php://input');
        $jsonData = json_decode($rawInput, true);
        $payload = is_array($jsonData) ? $jsonData : $_POST;

        $apiKey = $payload['api_key'] ?? null;
        $model = $payload['model'] ?? null;

        $result = AIService::testConnection($apiKey, $model);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result);
        exit;
    }

    // Simpan Entri Baru
    if ($action === 'entries_store' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $unitCode = $_POST['unit_code'] ?? '';
        
        // Strict RBAC: Hanya Admin atau PIC dari unit tersebut yang boleh input
        if (!Auth::canManageUnit($unitCode)) {
            http_response_code(403);
            $_SESSION['flash_error'] = "Akses Ditolak: Anda tidak memiliki hak akses menginput data untuk unit {$unitCode}.";
            header("Location: /index.php?page=entries");
            exit;
        }

        $res = Entry::create($_POST, $_FILES['attachment'] ?? null);
        if ($res['success']) {
            $_SESSION['flash_success'] = "Data capaian unit {$unitCode} berhasil disimpan.";
            header("Location: /index.php?page=entries_show&id=" . $res['entry_id']);
        } else {
            $_SESSION['flash_error'] = "Gagal menyimpan data: " . implode(" | ", $res['errors']);
            header("Location: /index.php?page=entries_create&unit_code=" . urlencode($unitCode));
        }
        exit;
    }

    // Update Entri
    if ($action === 'entries_update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = (int)($_GET['id'] ?? 0);
        $entry = Entry::find($id);

        if (!$entry || !Auth::canManageUnit($entry['unit_code'])) {
            http_response_code(403);
            $_SESSION['flash_error'] = "Akses Ditolak: Anda tidak berhak mengubah data capaian unit ini.";
            header("Location: /index.php?page=entries");
            exit;
        }

        $res = Entry::update($id, $_POST, $_FILES['attachment'] ?? null);
        if ($res['success']) {
            $_SESSION['flash_success'] = "Perubahan data capaian berhasil disimpan.";
            header("Location: /index.php?page=entries_show&id={$id}");
        } else {
            $_SESSION['flash_error'] = "Gagal mengubah data: " . implode(" | ", $res['errors']);
            header("Location: /index.php?page=entries_edit&id={$id}");
        }
        exit;
    }

    // Hapus Entri
    if ($action === 'entries_delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = (int)($_GET['id'] ?? 0);
        $entry = Entry::find($id);

        if (!$entry || !Auth::canManageUnit($entry['unit_code'])) {
            http_response_code(403);
            $_SESSION['flash_error'] = "Akses Ditolak: Anda tidak memiliki hak akses menghapus data capaian unit ini.";
            header("Location: /index.php?page=entries");
            exit;
        }

        if (Entry::delete($id)) {
            $_SESSION['flash_success'] = "Data capaian #{$id} berhasil dihapus.";
        } else {
            $_SESSION['flash_error'] = "Gagal menghapus data capaian.";
        }
        header("Location: /index.php?page=entries");
        exit;
    }

    // Toggle Screening Status (Draft <-> Siap Diolah)
    if ($action === 'entries_toggle_screening' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = (int)($_GET['id'] ?? 0);
        $entry = Entry::find($id);

        if (!$entry || !Auth::canManageUnit($entry['unit_code'])) {
            http_response_code(403);
            $_SESSION['flash_error'] = "Akses Ditolak: Anda tidak berhak mengubah status screening capaian unit ini.";
            header("Location: /index.php?page=entries_show&id={$id}");
            exit;
        }

        $newStatus = ($entry['screening_status'] === 'DRAFT') ? 'SIAP_DIOLAH' : 'DRAFT';
        $res = Entry::updateScreening($id, $newStatus);
        if ($res['success']) {
            $_SESSION['flash_success'] = "Status screening unit berhasil diubah menjadi: {$newStatus}";
        } else {
            $_SESSION['flash_error'] = "Gagal mengubah status screening: " . implode(" | ", $res['errors']);
        }
        header("Location: /index.php?page=entries_show&id={$id}");
        exit;
    }

    // Update Kurasi Komunikasi
    if ($action === 'entries_update_komunikasi' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!Auth::canCurateKomunikasi()) {
            http_response_code(403);
            $_SESSION['flash_error'] = "Akses Ditolak: Hanya Bagian Komunikasi atau Admin yang berhak memproses materi kurasi.";
            header("Location: /index.php?page=entries");
            exit;
        }

        $id = (int)($_GET['id'] ?? 0);
        $commStatus = $_POST['comm_status'] ?? 'BELUM_DIOLAH';
        $publishedUrl = $_POST['comm_published_url'] ?? '';
        $notes = $_POST['notes'] ?? '';

        $res = Entry::updateKomunikasi($id, $commStatus, $publishedUrl, $notes);
        if ($res['success']) {
            $_SESSION['flash_success'] = "Status pengolahan Komunikasi berhasil diperbarui.";
        } else {
            $_SESSION['flash_error'] = "Gagal memperbarui status kurasi: " . implode(" | ", $res['errors']);
        }
        $referer = $_SERVER['HTTP_REFERER'] ?? "/index.php?page=entries_show&id={$id}";
        header("Location: {$referer}");
        exit;
    }

    // Trigger Notifikasi Pengingat Massal ke Seluruh PIC Unit Belum Setor (Khusus Administrator)
    if ($action === 'reminders_trigger' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!Auth::isAdmin()) {
            http_response_code(403);
            $_SESSION['flash_error'] = "Akses Ditolak: Pengingat target hanya dapat dilakukan oleh Administrator Task Force.";
            header("Location: /index.php?page=dashboard");
            exit;
        }

        $periodId = $_POST['period_id'] ?? '2026-10';
        $phase = $_POST['phase'] ?? 'MID_MONTH';
        $results = Reminder::triggerReminders($periodId, $phase);
        Audit::log('REMINDER_TRIGGER', 'reminder_notifications', null, "Administrator mengeksekusi pengingat target Signature massal fase {$phase} periode {$periodId}");
        
        $notifiedCount = count(array_filter($results, fn($r) => str_contains($r['status'], 'TERKIRIM')));
        $_SESSION['flash_success'] = "Pengingat target ({$phase}) berhasil dikirimkan langsung ke email seluruh PIC unit yang belum setor ({$notifiedCount} PIC).";
        header("Location: /index.php?page=reminders&period_id=" . urlencode($periodId));
        exit;
    }

    // Kirim Pengingat Langsung ke 1 PIC Unit Tertentu (Khusus Administrator)
    if ($action === 'reminders_send_single' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!Auth::isAdmin()) {
            http_response_code(403);
            $_SESSION['flash_error'] = "Akses Ditolak: Pengingat target hanya dapat dilakukan oleh Administrator Task Force.";
            header("Location: /index.php?page=dashboard");
            exit;
        }

        $unitCode = $_POST['unit_code'] ?? '';
        $periodId = $_POST['period_id'] ?? '2026-10';
        $channel = $_POST['channel'] ?? 'EMAIL';
        $phase = $_POST['phase'] ?? 'DIRECT_ADMIN';

        $res = Reminder::sendDirectReminder($unitCode, $periodId, $channel, $phase);
        if ($res['success']) {
            $_SESSION['flash_success'] = $res['message'];
        } else {
            $_SESSION['flash_error'] = $res['message'];
        }
        header("Location: /index.php?page=reminders&period_id=" . urlencode($periodId));
        exit;
    }

    // =========================================================================
    // KELOLA PROFIL PENGGUNA MANDIRI
    // =========================================================================

    // Update Profil Pribadi
    if ($action === 'profile_update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $userId = (int)Auth::user()['id'];
        $res = Auth::updateProfile($userId, $_POST['full_name'] ?? '', $_POST['email'] ?? '', $_POST['phone'] ?? '');
        if ($res['success']) {
            $_SESSION['flash_success'] = $res['message'];
        } else {
            $_SESSION['flash_error'] = $res['message'];
        }
        header("Location: /index.php?page=profile");
        exit;
    }

    // Ganti Kata Sandi Mandiri
    if ($action === 'profile_password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $userId = (int)Auth::user()['id'];
        $res = Auth::changePassword(
            $userId, 
            $_POST['current_password'] ?? '', 
            $_POST['new_password'] ?? '', 
            $_POST['confirm_password'] ?? ''
        );
        if ($res['success']) {
            $_SESSION['flash_success'] = $res['message'];
        } else {
            $_SESSION['flash_error'] = $res['message'];
        }
        header("Location: /index.php?page=profile");
        exit;
    }

    // =========================================================================
    // ADMINISTRATOR: KELOLA PENGGUNA (TAMBAH, EDIT, RESET PASSWORD, HAPUS AKUN)
    // =========================================================================

    // Tambah Pengguna Baru (Admin Only)
    if ($action === 'users_store' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!Auth::isAdmin()) {
            http_response_code(403);
            $_SESSION['flash_error'] = "Akses Ditolak: Hanya Admin Task Force yang dapat menambahkan pengguna baru.";
            header("Location: /index.php?page=dashboard");
            exit;
        }

        $res = Auth::adminCreateUser($_POST);
        if ($res['success']) {
            $_SESSION['flash_success'] = $res['message'];
        } else {
            $_SESSION['flash_error'] = $res['message'];
        }
        header("Location: /index.php?page=users");
        exit;
    }

    // Update Data Pengguna (Admin Only)
    if ($action === 'users_update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!Auth::isAdmin()) {
            http_response_code(403);
            $_SESSION['flash_error'] = "Akses Ditolak: Hanya Admin Task Force yang dapat mengedit data pengguna.";
            header("Location: /index.php?page=dashboard");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $res = Auth::adminUpdateUser($id, $_POST);
        if ($res['success']) {
            $_SESSION['flash_success'] = $res['message'];
        } else {
            $_SESSION['flash_error'] = $res['message'];
        }
        header("Location: /index.php?page=users");
        exit;
    }

    // Reset Kata Sandi Pengguna (Admin Only)
    if ($action === 'users_reset_password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!Auth::isAdmin()) {
            http_response_code(403);
            $_SESSION['flash_error'] = "Akses Ditolak: Hanya Admin Task Force yang dapat me-reset kata sandi.";
            header("Location: /index.php?page=dashboard");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $newPass = $_POST['new_password'] ?? '';
        $res = Auth::adminResetPassword($id, $newPass);
        if ($res['success']) {
            $_SESSION['flash_success'] = $res['message'];
        } else {
            $_SESSION['flash_error'] = $res['message'];
        }
        header("Location: /index.php?page=users");
        exit;
    }

    // Buka Kunci Akun Pengguna (Admin Only)
    if ($action === 'users_unlock' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!Auth::isAdmin()) {
            http_response_code(403);
            $_SESSION['flash_error'] = "Akses Ditolak: Hanya Admin Task Force yang dapat membuka kunci akun.";
            header("Location: /index.php?page=dashboard");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $res = Auth::adminUnlockUser($id);
        if ($res['success']) {
            $_SESSION['flash_success'] = $res['message'];
        } else {
            $_SESSION['flash_error'] = $res['message'];
        }
        header("Location: /index.php?page=users");
        exit;
    }

    // Hapus Akun Pengguna (Admin Only)
    if ($action === 'users_delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!Auth::isAdmin()) {
            http_response_code(403);
            $_SESSION['flash_error'] = "Akses Ditolak: Hanya Admin Task Force yang dapat menghapus akun.";
            header("Location: /index.php?page=dashboard");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $res = Auth::adminDeleteUser($id);
        if ($res['success']) {
            $_SESSION['flash_success'] = $res['message'];
        } else {
            $_SESSION['flash_error'] = $res['message'];
        }
        header("Location: /index.php?page=users");
        exit;
    }

    // Ekspor Data (CSV / WURI / Excel Styled Stream)
    if ($action === 'export_download') {
        $format = $_GET['format'] ?? 'wuri';
        $periodId = $_GET['period_id'] ?? '';
        $unitCode = $_GET['unit_code'] ?? '';
        $maskPII = !empty($_GET['mask_pii']);

        // Jika PIC, kunci unit ekspor ke unitnya sendiri
        if (Auth::isPIC() && Auth::unit()) {
            $unitCode = Auth::unit();
        }

        $entries = Entry::getAll([
            'period_id' => $periodId,
            'unit_code' => $unitCode
        ]);

        Audit::log('EXPORT', 'reputation_entries', null, "Ekspor data reputasi format {$format} periode {$periodId}");

        if ($format === 'pdf_wuri' || $format === 'pdf') {
            require_once __DIR__ . '/../views/export/pdf_wuri.php';
            exit;
        }

        if ($format === 'pdf_rekap') {
            require_once __DIR__ . '/../views/export/pdf_rekap.php';
            exit;
        }

        if ($format === 'excel' || $format === 'excel_styled') {
            $filename = "Rekap_Capaian_UHN_" . ($periodId ?: 'Semua') . "_" . date('Ymd_His') . ".xls";
            header('Content-Type: application/vnd.ms-excel; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');

            echo "<html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">";
            echo "<head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">";
            echo "<style>
                body { font-family: Calibri, sans-serif; }
                .header-title { font-size: 16pt; font-weight: bold; color: #680015; }
                .sub-title { font-size: 11pt; color: #475569; }
                th { background-color: #680015; color: #ffffff; font-weight: bold; padding: 8px 12px; text-align: left; }
                td { padding: 6px 10px; vertical-align: top; border: 1px solid #e2e8f0; }
                .badge-sig { background-color: #fee2e2; color: #680015; font-weight: bold; }
                .badge-reg { background-color: #f1f5f9; color: #475569; }
                .badge-yes { color: #047857; font-weight: bold; }
                .badge-no { color: #b91c1c; font-weight: bold; }
            </style>";
            echo "</head>";
            echo "<body>";
            echo "<p class='header-title'>UNIVERSITAS HARKAT NEGERI</p>";
            echo "<p class='sub-title'>TIM TASK FORCE KERJASAMA INTERNASIONAL &amp; REPUTASI INSTITUSI<br>";
            echo "Rekapitulasi Inventarisasi Data Capaian Branding &amp; Reputasi Institusi<br>";
            echo "Periode Laporan: <strong>" . ($periodId ? (PERIODS[$periodId] ?? $periodId) : "Semua Periode (Sep 2026 - Agu 2027)") . "</strong> | Tanggal Unduh: " . date('d F Y H:i:s') . "</p>";
            
            echo "<table border='1'>";
            echo "<tr>";
            echo "<th>ID</th><th>Unit Kerja</th><th>Periode</th><th>Tier</th><th>Judul Capaian</th><th>Subjek / Personil</th><th>Nomor Identitas</th><th>Deskripsi Singkat (Bahasa Awam)</th><th>Screening Unit</th><th>Status Komunikasi</th><th>Tautan Publikasi</th><th>Persetujuan Nama (UU PDP)</th>";
            echo "</tr>";

            foreach ($entries as $e) {
                $isRestricted = ($e['name_publication_consent'] === 'TIDAK') || $maskPII;
                
                // Ekstrak nama subjek dan nomor identitas
                $subjName = '-';
                $subjId = '-';
                if ($e['unit_code'] === 'PPM') {
                    $invs = $e['details']['inventors'] ?? [];
                    if (!empty($invs)) {
                        $nArr = [];
                        $idArr = [];
                        foreach ($invs as $inv) {
                            $nArr[] = $isRestricted ? (substr($inv['name'], 0, 1) . '. [Anonim]') : $inv['name'];
                            $idArr[] = $maskPII ? Helpers::maskIdentifier($inv['identifier'], false) : $inv['identifier'];
                        }
                        $subjName = implode(', ', $nArr);
                        $subjId = implode(', ', $idArr);
                    }
                } elseif ($e['unit_code'] === 'PSDM') {
                    $rawName = $e['details']['employee_name'] ?? '-';
                    $rawId = $e['details']['employee_id'] ?? '-';
                    $subjName = $isRestricted ? (substr($rawName, 0, 1) . '. [Anonim/Dirahasiakan]') : $rawName;
                    $subjId = $maskPII ? Helpers::maskIdentifier($rawId, false) : $rawId;
                } elseif ($e['unit_code'] === 'KEMAHASISWAAN') {
                    $rawName = $e['details']['student_name'] ?? '-';
                    $rawId = $e['details']['student_nim'] ?? '-';
                    $subjName = $isRestricted ? (substr($rawName, 0, 1) . '. [Anonim/Dirahasiakan]') : $rawName;
                    $subjId = $maskPII ? Helpers::maskIdentifier($rawId, false) : $rawId;
                } elseif ($e['unit_code'] === 'ALUMNI_KARIR') {
                    $rawName = $e['details']['alumni_name'] ?? '-';
                    $subjName = $isRestricted ? (substr($rawName, 0, 1) . '. [Anonim]') : $rawName;
                    $subjId = 'Lulus ' . ($e['details']['graduation_year'] ?? '-');
                }

                $safeTitle = htmlspecialchars($e['title']);
                $tierClass = ($e['tier_type'] === 'SIGNATURE') ? 'badge-sig' : 'badge-reg';
                $consentClass = ($e['name_publication_consent'] === 'YA') ? 'badge-yes' : 'badge-no';

                echo "<tr>";
                echo "<td>#{$e['id']}</td>";
                echo "<td>" . htmlspecialchars($e['unit_name'] ?? $e['unit_code']) . "</td>";
                echo "<td>" . htmlspecialchars($e['period_name'] ?? $e['period_id']) . "</td>";
                echo "<td class='{$tierClass}'>" . htmlspecialchars($e['tier_type']) . "</td>";
                echo "<td>{$safeTitle}</td>";
                echo "<td>" . htmlspecialchars($subjName) . "</td>";
                echo "<td>" . htmlspecialchars($subjId) . "</td>";
                echo "<td>" . htmlspecialchars($e['short_description']) . "</td>";
                echo "<td>" . htmlspecialchars($e['screening_status']) . "</td>";
                echo "<td>" . htmlspecialchars($e['comm_status']) . "</td>";
                echo "<td>" . (!empty($e['comm_published_url']) ? "<a href='{$e['comm_published_url']}'>" . htmlspecialchars($e['comm_published_url']) . "</a>" : '-') . "</td>";
                echo "<td class='{$consentClass}'>" . htmlspecialchars($e['name_publication_consent']) . "</td>";
                echo "</tr>";
            }
            echo "</table></body></html>";
            exit;
        }

        if ($format === 'wuri') {
            $filename = "Borang_WURI_UHN_" . ($periodId ?: 'All') . "_" . date('Ymd_His') . ".csv";
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($output, ['ID', 'WURI Category / Focus', 'Initiative / Achievement Title', 'Implementing Unit', 'Impact Summary', 'Evidence / Media URL', 'Status Verification'], ',', '"', "\\");

            foreach ($entries as $e) {
                if ($e['screening_status'] !== 'SIAP_DIOLAH') continue;

                $wuriCategory = match($e['unit_code']) {
                    'PPM' => 'A1 - Industrial Application & Tech Transfer',
                    'KEMAHASISWAAN' => 'B1 - Student Mobility & Openness',
                    'ALUMNI_KARIR' => ($e['details']['achievement_type'] === 'WIRAUSAHA') 
                        ? 'A1 - Industrial Application & Tech Transfer (Startup Impact)'
                        : 'B1 - Student Mobility & Global Placement',
                    'PSDM' => 'C1 - Ethical Leadership, Crisis Management & Tridarma Excellence',
                    default => 'General Institutional Advancement'
                };

                $url = !empty($e['comm_published_url']) ? $e['comm_published_url'] : (!empty($e['external_url']) ? $e['external_url'] : 'Dokumen Internal UHN');

                fputcsv($output, [
                    $e['id'],
                    $wuriCategory,
                    $e['title'],
                    $e['unit_name'] ?? $e['unit_code'],
                    $e['short_description'],
                    $url,
                    $e['comm_status'] === 'SUDAH_TERBIT' ? 'Published & Validated' : 'Screened by Unit'
                ], ',', '"', "\\");
            }

            fclose($output);
            exit;
        }

        if ($format === 'webometrics') {
            $filename = "Evidensi_Webometrics_Uniranks_UHN_" . ($periodId ?: 'All') . "_" . date('Ymd_His') . ".csv";
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($output, ['ID', 'Pilar Webometrics / Uniranks', 'Unit Kerja', 'Periode', 'Judul Inovasi / Capaian', 'Status / Tingkat', 'Tautan Evidensi Rilis', 'Status Kurasi'], ',', '"', "\\");

            foreach ($entries as $e) {
                $indicator = match($e['unit_code']) {
                    'PPM' => 'Excellence & Scholar / Intellectual Property',
                    'PSDM' => 'Transparency / Openness & Faculty Leadership',
                    'KEMAHASISWAAN' => 'Visibility / Web Presence & Student Achievements',
                    'ALUMNI_KARIR' => 'Impact / Institutional Reputation & Employability',
                    default => 'Institutional Presence'
                };

                $statusInfo = match($e['unit_code']) {
                    'PPM' => $e['details']['ki_status'] ?? '-',
                    'PSDM' => ($e['details']['level'] ?? '-') . ' / ' . ($e['details']['achievement_type'] ?? '-'),
                    'KEMAHASISWAAN' => ($e['details']['achievement_rank'] ?? '-') . ' (' . ($e['details']['level'] ?? '-') . ')',
                    'ALUMNI_KARIR' => ($e['details']['achievement_type'] ?? '-') . ' di ' . ($e['details']['company_or_institution'] ?? '-'),
                    default => '-'
                };

                $url = !empty($e['comm_published_url']) ? $e['comm_published_url'] : ($e['external_url'] ?? '-');

                fputcsv($output, [
                    $e['id'],
                    $indicator,
                    $e['unit_name'] ?? $e['unit_code'],
                    $e['period_name'] ?? $e['period_id'],
                    $e['title'],
                    $statusInfo,
                    $url,
                    $e['comm_status']
                ], ',', '"', "\\");
            }

            fclose($output);
            exit;
        }

        // CSV Standar
        $filename = "Data_Reputasi_UHN_" . date('Ymd_His') . ".csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, ['ID', 'Unit Code', 'Unit Name', 'Period', 'Tier Type', 'Title', 'Subject / Inovator', 'Short Description', 'Screening Status', 'Comm Status', 'Published URL', 'Name Consent', 'Created At'], ',', '"', "\\");

        foreach ($entries as $e) {
            $isRestricted = ($e['name_publication_consent'] === 'TIDAK') || $maskPII;
            $subjName = '-';
            if ($e['unit_code'] === 'PPM') {
                $invs = $e['details']['inventors'] ?? [];
                $nArr = [];
                foreach ($invs as $inv) {
                    $nArr[] = $isRestricted ? (substr($inv['name'], 0, 1) . '. [Anonim]') : $inv['name'];
                }
                $subjName = implode(', ', $nArr);
            } elseif ($e['unit_code'] === 'PSDM') {
                $raw = $e['details']['employee_name'] ?? '-';
                $subjName = $isRestricted ? (substr($raw, 0, 1) . '. [Anonim]') : $raw;
            } elseif ($e['unit_code'] === 'KEMAHASISWAAN') {
                $raw = $e['details']['student_name'] ?? '-';
                $subjName = $isRestricted ? (substr($raw, 0, 1) . '. [Anonim]') : $raw;
            } elseif ($e['unit_code'] === 'ALUMNI_KARIR') {
                $raw = $e['details']['alumni_name'] ?? '-';
                $subjName = $isRestricted ? (substr($raw, 0, 1) . '. [Anonim]') : $raw;
            }

            fputcsv($output, [
                $e['id'],
                $e['unit_code'],
                $e['unit_name'] ?? $e['unit_code'],
                $e['period_name'] ?? $e['period_id'],
                $e['tier_type'],
                $e['title'],
                $subjName,
                $e['short_description'],
                $e['screening_status'],
                $e['comm_status'],
                $e['comm_published_url'] ?? '',
                $e['name_publication_consent'],
                $e['created_at']
            ], ',', '"', "\\");
        }
        fclose($output);
        exit;
    }

    // Unduh Backup SQLite Database (Admin Only)
    if ($action === 'backup_download') {
        if (!Auth::isAdmin()) {
            http_response_code(403);
            die('Akses Ditolak: Hanya Admin Task Force yang berhak mengunduh cadangan basis data.');
        }

        if (file_exists(DB_PATH)) {
            Audit::log('BACKUP_DOWNLOAD', 'system', null, "Mengunduh file cadangan database SQLite");
            $filename = "UHN_Reputasi_DB_Backup_" . date('Ymd_His') . ".sqlite";
            header('Content-Type: application/x-sqlite3');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize(DB_PATH));
            readfile(DB_PATH);
            exit;
        } else {
            die('Database file tidak ditemukan.');
        }
    }
}

// =============================================================================
// ROUTER: VIEW RENDERING (Strict View Authorization)
// =============================================================================

// Halaman Manajemen Pengguna (Admin Only)
if ($page === 'users' && !Auth::isAdmin()) {
    http_response_code(403);
    $_SESSION['flash_error'] = "Akses Ditolak: Halaman Kelola Pengguna hanya dapat diakses oleh Admin Task Force.";
    header("Location: /index.php?page=dashboard");
    exit;
}

// Halaman Audit Trail (Admin Only)
if ($page === 'audit' && !Auth::isAdmin()) {
    http_response_code(403);
    $_SESSION['flash_error'] = "Akses Ditolak: Halaman Audit Trail hanya dapat diakses oleh Admin Task Force.";
    header("Location: /index.php?page=dashboard");
    exit;
}

// Halaman Pengingat Target (Admin Only - Pengingat target hanya dilakukan oleh administrator ke PIC unit masing-masing)
if ($page === 'reminders' && !Auth::isAdmin()) {
    http_response_code(403);
    $_SESSION['flash_error'] = "Akses Ditolak: Pengingat target hanya dapat diakses dan dilakukan oleh Administrator Task Force.";
    header("Location: /index.php?page=dashboard");
    exit;
}

// Halaman Konfigurasi AI (Admin Only)
if ($page === 'ai_config' && !Auth::isAdmin()) {
    http_response_code(403);
    $_SESSION['flash_error'] = "Akses Ditolak: Halaman Konfigurasi AI hanya dapat diakses oleh Admin Task Force.";
    header("Location: /index.php?page=dashboard");
    exit;
}

// Halaman Meja Kurasi Komunikasi (Admin & Komunikasi Only)
if ($page === 'komunikasi' && !Auth::canCurateKomunikasi()) {
    http_response_code(403);
    $_SESSION['flash_error'] = "Akses Ditolak: Meja Kurasi Komunikasi hanya dapat diakses oleh Bagian Komunikasi dan Admin Task Force.";
    header("Location: /index.php?page=dashboard");
    exit;
}

// Halaman Input Capaian Baru (Viewer & Komunikasi Dilarang)
if ($page === 'entries_create') {
    $requestedUnit = $_GET['unit_code'] ?? Auth::unit() ?? 'PPM';
    if (!Auth::canCreateEntry($requestedUnit)) {
        http_response_code(403);
        $_SESSION['flash_error'] = "Akses Ditolak: Role Anda (" . Auth::user()['role_label'] . ") tidak diizinkan menginput capaian untuk unit {$requestedUnit}.";
        header("Location: /index.php?page=entries");
        exit;
    }
}

// Halaman Edit Capaian (Viewer, Komunikasi, atau PIC Unit Lain Dilarang)
if ($page === 'entries_edit') {
    $editId = (int)($_GET['id'] ?? 0);
    $entryToEdit = Entry::find($editId);
    if (!$entryToEdit || !Auth::canManageUnit($entryToEdit['unit_code'])) {
        http_response_code(403);
        $_SESSION['flash_error'] = "Akses Ditolak: Anda tidak memiliki izin untuk mengedit data capaian unit ini.";
        header("Location: /index.php?page=entries");
        exit;
    }
}

// Render Layout & View
require_once __DIR__ . '/../views/layout/header.php';

switch ($page) {
    case 'dashboard':
        require_once __DIR__ . '/../views/dashboard.php';
        break;

    case 'entries':
        require_once __DIR__ . '/../views/entries/index.php';
        break;

    case 'entries_create':
        $unitCode = $_GET['unit_code'] ?? Auth::unit() ?? 'PPM';
        if ($unitCode === 'PPM') {
            require_once __DIR__ . '/../views/entries/form_ppm.php';
        } elseif ($unitCode === 'PSDM') {
            require_once __DIR__ . '/../views/entries/form_psdm.php';
        } elseif ($unitCode === 'KEMAHASISWAAN') {
            require_once __DIR__ . '/../views/entries/form_kemhs.php';
        } elseif ($unitCode === 'ALUMNI_KARIR') {
            require_once __DIR__ . '/../views/entries/form_alumni.php';
        } else {
            require_once __DIR__ . '/../views/entries/form_ppm.php';
        }
        break;

    case 'entries_edit':
        $id = (int)($_GET['id'] ?? 0);
        $entry = Entry::find($id);
        if (!$entry) {
            echo '<div class="p-6 bg-red-50 text-red-700 rounded-xl">Data tidak ditemukan.</div>';
        } else {
            $unitCode = $entry['unit_code'];
            if ($unitCode === 'PPM') {
                require_once __DIR__ . '/../views/entries/form_ppm.php';
            } elseif ($unitCode === 'PSDM') {
                require_once __DIR__ . '/../views/entries/form_psdm.php';
            } elseif ($unitCode === 'KEMAHASISWAAN') {
                require_once __DIR__ . '/../views/entries/form_kemhs.php';
            } elseif ($unitCode === 'ALUMNI_KARIR') {
                require_once __DIR__ . '/../views/entries/form_alumni.php';
            }
        }
        break;

    case 'entries_show':
        require_once __DIR__ . '/../views/entries/show.php';
        break;

    case 'komunikasi':
        require_once __DIR__ . '/../views/komunikasi/index.php';
        break;

    case 'export':
        require_once __DIR__ . '/../views/export/index.php';
        break;

    case 'reminders':
        require_once __DIR__ . '/../views/reminders/index.php';
        break;

    case 'audit':
        require_once __DIR__ . '/../views/audit/index.php';
        break;

    case 'users':
        require_once __DIR__ . '/../views/users/index.php';
        break;

    case 'ai_config':
        require_once __DIR__ . '/../views/admin/ai_config.php';
        break;

    case 'profile':
        require_once __DIR__ . '/../views/profile/index.php';
        break;

    default:
        require_once __DIR__ . '/../views/dashboard.php';
        break;
}

require_once __DIR__ . '/../views/layout/footer.php';
