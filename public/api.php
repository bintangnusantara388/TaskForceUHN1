<?php
// public/api.php - REST API Gateway untuk Aplikasi Mobile (Flutter/Android/iOS)
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../app/Config.php';
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Models/Entry.php';
require_once __DIR__ . '/../app/Models/Reminder.php';
require_once __DIR__ . '/../app/Models/Audit.php';

// Pastikan DB siap
Database::getConnection();

// Simulasi autentikasi API: jika ada header X-User-Role atau query user, gunakan itu
$userKey = $_GET['user'] ?? $_SERVER['HTTP_X_USER_ROLE'] ?? 'admin_tf';
if (isset(DEFAULT_USERS[$userKey])) {
    Auth::setUser($userKey);
}

$endpoint = $_GET['endpoint'] ?? '';

try {
    switch ($endpoint) {
        
        // 1. Dashboard & Progress Meter API
        case 'dashboard':
            $periodId = $_GET['period_id'] ?? '2026-10';
            $progress = Reminder::getProgressMeter($periodId);
            $chartData = Entry::getChartData($periodId);

            $summary = [
                'total_entries' => 0,
                'signature_fulfilled' => 0,
                'total_units' => count($progress),
                'pending_comm' => 0
            ];

            foreach ($progress as $p) {
                $summary['total_entries'] += $p['total_entries'];
                if ($p['is_signature_fulfilled']) $summary['signature_fulfilled']++;
                $summary['pending_comm'] += $p['belum_diolah_kom'];
            }

            echo json_encode([
                'success' => true,
                'period_id' => $periodId,
                'period_name' => PERIODS[$periodId] ?? $periodId,
                'summary' => $summary,
                'progress_meter' => array_values($progress),
                'chart_data' => $chartData
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            break;

        // 2. Daftar Entri Capaian (Filterable)
        case 'entries':
            $filters = [
                'period_id' => $_GET['period_id'] ?? '',
                'unit_code' => $_GET['unit_code'] ?? '',
                'tier_type' => $_GET['tier_type'] ?? '',
                'screening_status' => $_GET['screening_status'] ?? '',
                'comm_status' => $_GET['comm_status'] ?? '',
                'consent' => $_GET['consent'] ?? '',
                'keyword' => $_GET['keyword'] ?? ''
            ];

            // Jika mobile login sebagai PIC Unit tertentu
            if (Auth::isPIC() && Auth::unit()) {
                $filters['unit_code'] = Auth::unit();
            }

            $entries = Entry::getAll($filters);

            // Sanitasi privasi nama jika subjek menolak publikasi
            $canBypass = Auth::isAdmin() || (Auth::isPIC() && Auth::unit() === ($filters['unit_code'] ?? ''));
            foreach ($entries as &$item) {
                if ($item['name_publication_consent'] === 'TIDAK' && !$canBypass) {
                    $item['title_safe'] = $item['title'];
                    $item['is_restricted'] = true;
                } else {
                    $item['is_restricted'] = false;
                }
            }

            echo json_encode([
                'success' => true,
                'count' => count($entries),
                'filters_applied' => $filters,
                'data' => $entries
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            break;

        // 3. Detail Capaian Tunggal
        case 'entry_detail':
            $id = (int)($_GET['id'] ?? 0);
            $entry = Entry::find($id);

            if (!$entry) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan.'], JSON_PRETTY_PRINT);
                exit;
            }

            echo json_encode([
                'success' => true,
                'data' => $entry
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            break;

        // 4. Input Capaian Baru dari Mobile (Mendukung upload kamera via multipart)
        case 'entry_create':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Gunakan metode POST.'], JSON_PRETTY_PRINT);
                exit;
            }

            // Tangkap payload POST (baik multipart/form-data maupun json)
            $payload = $_POST;
            if (empty($payload)) {
                $raw = file_get_contents('php://input');
                $payload = json_decode($raw, true) ?: [];
            }

            $unitCode = $payload['unit_code'] ?? '';
            if (!Auth::canManageUnit($unitCode)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => "Akses Ditolak: Role Anda (" . (Auth::user()['role_label'] ?? 'Guest') . ") tidak berhak menginput data unit {$unitCode}."], JSON_PRETTY_PRINT);
                exit;
            }

            $file = $_FILES['attachment'] ?? null;
            $res = Entry::create($payload, $file);

            if ($res['success']) {
                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'message' => 'Data capaian berhasil disimpan dari aplikasi mobile.',
                    'entry_id' => $res['entry_id']
                ], JSON_PRETTY_PRINT);
            } else {
                http_response_code(422);
                echo json_encode([
                    'success' => false,
                    'errors' => $res['errors']
                ], JSON_PRETTY_PRINT);
            }
            break;

        // 5. Toggle Screening (Draft -> Siap Diolah)
        case 'toggle_screening':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Gunakan metode POST.'], JSON_PRETTY_PRINT);
                exit;
            }

            $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
            $entry = Entry::find($id);
            if (!$entry) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan.'], JSON_PRETTY_PRINT);
                exit;
            }

            if (!Auth::canManageUnit($entry['unit_code'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Akses Ditolak: Anda tidak berhak mengubah screening unit ini.'], JSON_PRETTY_PRINT);
                exit;
            }

            $newStatus = ($entry['screening_status'] === 'DRAFT') ? 'SIAP_DIOLAH' : 'DRAFT';
            $res = Entry::updateScreening($id, $newStatus);

            echo json_encode([
                'success' => $res['success'],
                'new_status' => $newStatus,
                'message' => $res['success'] ? "Status screening berhasil diubah menjadi {$newStatus}" : 'Gagal'
            ], JSON_PRETTY_PRINT);
            break;

        // 6. Update Status Kurasi Komunikasi
        case 'update_komunikasi':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Gunakan metode POST.'], JSON_PRETTY_PRINT);
                exit;
            }

            if (!Auth::canCurateKomunikasi()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Akses Ditolak: Hanya Bagian Komunikasi atau Admin yang berhak memproses kurasi.'], JSON_PRETTY_PRINT);
                exit;
            }

            $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
            $commStatus = $_POST['comm_status'] ?? 'SEDANG_DIOLAH';
            $publishedUrl = $_POST['comm_published_url'] ?? '';
            $notes = $_POST['notes'] ?? '';

            $res = Entry::updateKomunikasi($id, $commStatus, $publishedUrl, $notes);

            echo json_encode([
                'success' => $res['success'],
                'message' => $res['success'] ? 'Status kurasi Komunikasi berhasil diperbarui.' : 'Gagal memperbarui'
            ], JSON_PRETTY_PRINT);
            break;

        // 7. Pengingat & Notifikasi
        case 'reminders':
            $periodId = $_GET['period_id'] ?? '2026-10';
            $meter = Reminder::getProgressMeter($periodId);
            $unfulfilled = array_filter($meter, fn($m) => !$m['is_signature_fulfilled']);

            echo json_encode([
                'success' => true,
                'period_id' => $periodId,
                'unfulfilled_count' => count($unfulfilled),
                'urgent_units' => array_values($unfulfilled),
                'phase_schedule' => [
                    'mid_month_date' => '15 ' . (PERIODS[$periodId] ?? ''),
                    'end_month_date' => '26 ' . (PERIODS[$periodId] ?? '')
                ]
            ], JSON_PRETTY_PRINT);
            break;

        // Master Data (Units, Periods, Roles)
        case 'master_data':
            echo json_encode([
                'success' => true,
                'institution' => APP_INSTITUTION,
                'taskforce' => APP_TASKFORCE,
                'units' => UNITS,
                'periods' => PERIODS,
                'available_users' => DEFAULT_USERS
            ], JSON_PRETTY_PRINT);
            break;

        default:
            echo json_encode([
                'success' => true,
                'api' => 'ERP Reputasi Institusi - Mobile REST API Gateway',
                'institution' => APP_INSTITUTION,
                'endpoints_available' => [
                    'GET /api.php?endpoint=dashboard&period_id=2026-10',
                    'GET /api.php?endpoint=entries',
                    'GET /api.php?endpoint=entry_detail&id=101',
                    'POST /api.php?endpoint=entry_create',
                    'POST /api.php?endpoint=toggle_screening&id=101',
                    'POST /api.php?endpoint=update_komunikasi&id=101',
                    'GET /api.php?endpoint=reminders&period_id=2026-10',
                    'GET /api.php?endpoint=master_data'
                ]
            ], JSON_PRETTY_PRINT);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
