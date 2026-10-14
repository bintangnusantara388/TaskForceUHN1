<?php
// app/Config.php

// Muat environment variables dari berkas .env jika tersedia (Lightweight Dotenv Parser)
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile) && is_readable($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            [$key, $val] = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val);
            if (
                (str_starts_with($val, '"') && str_ends_with($val, '"')) ||
                (str_starts_with($val, "'") && str_ends_with($val, "'"))
            ) {
                $val = substr($val, 1, -1);
            }
            if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                putenv("{$key}={$val}");
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
            }
        }
    }
}

// Helper untuk membaca env
if (!function_exists('env')) {
    function env(string $key, $default = null) {
        $val = getenv($key);
        if ($val !== false && $val !== '') {
            return $val;
        }
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return $_SERVER[$key];
        }
        return $default;
    }
}

// Deteksi lingkungan Serverless (Vercel)
$isVercel = !empty($_ENV['VERCEL']) || !empty(getenv('VERCEL')) || !empty($_SERVER['VERCEL']);
$defaultDbPath = $isVercel ? '/tmp/app.sqlite' : dirname(__DIR__) . '/database/app.sqlite';
$defaultUploadDir = $isVercel ? '/tmp/uploads' : dirname(__DIR__) . '/public/uploads';

define('APP_NAME', env('APP_NAME', 'ERP Inventarisasi Reputasi Institusi'));
define('APP_ENV', env('APP_ENV', $isVercel ? 'production' : 'local'));
define('APP_DEBUG', filter_var(env('APP_DEBUG', !$isVercel), FILTER_VALIDATE_BOOLEAN));
define('APP_URL', env('APP_URL', 'http://localhost:8088'));
define('APP_INSTITUTION', env('APP_INSTITUTION', 'Universitas Harkat Negeri'));
define('APP_TASKFORCE', env('APP_TASKFORCE', 'Tim Task Force Kerjasama Internasional & Reputasi Institusi'));
define('APP_ROOT', dirname(__DIR__));
define('DB_PATH', env('DB_PATH', $defaultDbPath));
define('UPLOAD_DIR', env('UPLOAD_DIR', $defaultUploadDir));
define('MAX_UPLOAD_SIZE', (int)env('MAX_UPLOAD_SIZE', 10 * 1024 * 1024)); // 10 Megabytes

// Konfigurasi Teknologi AI (Google Gemini API & UHN Neural Synthesizer)
define('GEMINI_API_KEY', env('GEMINI_API_KEY', ''));

// Daftar Unit Resmi
const UNITS = [
    'PPM' => 'Pusat Penelitian dan Pengabdian Masyarakat',
    'PSDM' => 'Pengembangan Sumber Daya Manusia',
    'KEMAHASISWAAN' => 'Biro Kemahasiswaan dan Alumni',
    'ALUMNI_KARIR' => 'Pusat Karir dan Hubungan Alumni',
    'KOMUNIKASI' => 'Bagian Komunikasi dan Promosi',
    'TASK_FORCE' => 'Tim Task Force Kerjasama & Reputasi'
];

// Daftar Periode Pelaporan (Sep 2026 s.d. Agu 2027)
const PERIODS = [
    '2026-09' => 'September 2026',
    '2026-10' => 'Oktober 2026',
    '2026-11' => 'November 2026',
    '2026-12' => 'Desember 2026',
    '2027-01' => 'Januari 2027',
    '2027-02' => 'Februari 2027',
    '2027-03' => 'Maret 2027',
    '2027-04' => 'April 2027',
    '2027-05' => 'Mei 2027',
    '2027-06' => 'Juni 2027',
    '2027-07' => 'Juli 2027',
    '2027-08' => 'Agustus 2027'
];

// Role & Pengguna Bawaan (untuk simulasi & testing)
const DEFAULT_USERS = [
    'admin_tf' => [
        'id' => 1,
        'username' => 'admin_tf',
        'name' => 'Dr. Hendra Gunawan, S.T., M.T.',
        'role' => 'ADMIN_TASK_FORCE',
        'unit' => 'TASK_FORCE',
        'role_label' => 'Admin Task Force'
    ],
    'pic_ppm' => [
        'id' => 2,
        'username' => 'pic_ppm',
        'name' => 'Siti Rahmawati, M.Si.',
        'role' => 'PIC_UNIT',
        'unit' => 'PPM',
        'role_label' => 'PIC Unit PPM'
    ],
    'pic_psdm' => [
        'id' => 3,
        'username' => 'pic_psdm',
        'name' => 'Bambang Sudiro, S.Kom., M.M.',
        'role' => 'PIC_UNIT',
        'unit' => 'PSDM',
        'role_label' => 'PIC Unit PSDM'
    ],
    'pic_mhs' => [
        'id' => 4,
        'username' => 'pic_mhs',
        'name' => 'Dewi Sartika, M.Pd.',
        'role' => 'PIC_UNIT',
        'unit' => 'KEMAHASISWAAN',
        'role_label' => 'PIC Unit Kemahasiswaan'
    ],
    'pic_alumni' => [
        'id' => 5,
        'username' => 'pic_alumni',
        'name' => 'Rudi Hartono, S.E., M.B.A.',
        'role' => 'PIC_UNIT',
        'unit' => 'ALUMNI_KARIR',
        'role_label' => 'PIC Unit Alumni Karir'
    ],
    'komunikasi_staff' => [
        'id' => 6,
        'username' => 'komunikasi_staff',
        'name' => 'Nadia Putri, S.I.Kom.',
        'role' => 'KOMUNIKASI',
        'unit' => 'KOMUNIKASI',
        'role_label' => 'Bagian Komunikasi'
    ],
    'rektor_viewer' => [
        'id' => 7,
        'username' => 'rektor_viewer',
        'name' => 'Prof. Dr. Ir. H. Mulyadi, M.Sc. (Rektor)',
        'role' => 'VIEWER',
        'unit' => 'TASK_FORCE',
        'role_label' => 'Viewer (Pimpinan)'
    ]
];
