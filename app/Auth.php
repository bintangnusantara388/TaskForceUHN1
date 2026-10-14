<?php
// app/Auth.php
require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Helpers.php';
require_once __DIR__ . '/Models/Audit.php';

class Auth {
    public static function startSession(): void {
        if (!headers_sent() && session_status() === PHP_SESSION_NONE) {
            // Pengaturan cookie sesi aman
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            @session_start();
        }
    }

    /**
     * Memeriksa apakah pengguna sudah terotentikasi / login
     */
    public static function check(): bool {
        self::startSession();
        return isset($_SESSION['current_user']) && is_array($_SESSION['current_user']) && !empty($_SESSION['current_user']['id']);
    }

    /**
     * Mengambil data pengguna aktif saat ini
     */
    public static function user(): ?array {
        self::startSession();
        return $_SESSION['current_user'] ?? null;
    }

    /**
     * Merefresh data pengguna aktif dari database (sinkronisasi profil real-time)
     */
    public static function refreshUser(): void {
        self::startSession();
        if (!self::check()) return;
        
        $userId = (int)$_SESSION['current_user']['id'];
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $u = $stmt->fetch();
        if ($u) {
            $_SESSION['current_user'] = [
                'id' => (int)$u['id'],
                'username' => $u['username'],
                'name' => $u['full_name'],
                'email' => $u['email'],
                'role' => $u['role'],
                'unit' => $u['unit_code'],
                'is_active' => (int)$u['is_active'],
                'last_login_at' => $u['last_login_at'],
                'role_label' => self::formatRoleLabel($u['role'], $u['unit_code'])
            ];
        }
    }

    /**
     * Autentikasi ketat: Bcrypt Hash + Deteksi Brute Force + Proteksi Lockout 15 Menit
     */
    public static function attemptLogin(string $username, string $password): array {
        self::startSession();
        $username = trim($username);

        if (empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'Nama pengguna dan kata sandi wajib diisi.'];
        }

        // Resolusi alias username demo
        $aliases = [
            'viewer' => 'rektor_viewer',
            'komunikasi' => 'komunikasi_staff',
            'pic_kemhs' => 'pic_mhs',
        ];
        if (isset($aliases[$username])) {
            $username = $aliases[$username];
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        $ip = Helpers::getClientIp();

        // 1. Pengguna tidak ditemukan
        if (!$user) {
            Audit::log('LOGIN_FAILED', 'users', null, "Percobaan login gagal untuk akun tidak terdaftar: '{$username}' dari IP {$ip}");
            return ['success' => false, 'message' => 'Nama pengguna atau kata sandi tidak valid.'];
        }

        // 2. Cek apakah akun dinonaktifkan oleh Admin
        if ((int)$user['is_active'] === 0) {
            Audit::log('LOGIN_BLOCKED', 'users', (int)$user['id'], "Login diblokir: Akun {$user['username']} dalam status non-aktif");
            return ['success' => false, 'message' => 'Akun Anda dinonaktifkan oleh Administrator Task Force. Silakan hubungi pengelola institusi.'];
        }

        // 3. Cek apakah akun sedang dalam masa penguncian (Lockout)
        if (!empty($user['locked_until'])) {
            $lockTime = strtotime($user['locked_until']);
            if ($lockTime > time()) {
                $remainMinutes = max(1, (int)ceil(($lockTime - time()) / 60));
                Audit::log('LOGIN_LOCKED', 'users', (int)$user['id'], "Login ditolak: Akun {$user['username']} terkunci hingga {$user['locked_until']}");
                return [
                    'success' => false,
                    'message' => "Akun Anda terkunci sementara akibat 5 kali gagal login berturut-turut. Silakan coba lagi dalam {$remainMinutes} menit atau hubungi Administrator Task Force."
                ];
            } else {
                // Masa kunci telah habis, reset otomatis
                $db->prepare("UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE id = ?")->execute([$user['id']]);
                $user['failed_login_attempts'] = 0;
                $user['locked_until'] = null;
            }
        }

        // 4. Verifikasi Kata Sandi Menggunakan Bcrypt
        $passwordMatches = false;
        if (!empty($user['password_hash'])) {
            $passwordMatches = password_verify($password, $user['password_hash']);
        }

        // Fallback backward compatibility akun demo jika password_hash belum ter-update
        if (!$passwordMatches && empty($user['password_hash']) && $password === 'harkat123') {
            $passwordMatches = true;
            // Upgrade hash langsung
            $newHash = password_hash('harkat123', PASSWORD_BCRYPT);
            $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$newHash, $user['id']]);
        }

        // 5. Jika Kata Sandi Salah (Increment Percobaan Gagal)
        if (!$passwordMatches) {
            $failedAttempts = (int)($user['failed_login_attempts'] ?? 0) + 1;

            if ($failedAttempts >= 5) {
                // Kunci akun selama 15 menit
                $lockUntil = date('Y-m-d H:i:s', time() + 900);
                $db->prepare("UPDATE users SET failed_login_attempts = ?, locked_until = ? WHERE id = ?")
                   ->execute([$failedAttempts, $lockUntil, $user['id']]);

                Audit::log('ACCOUNT_LOCKED', 'users', (int)$user['id'], "PERINGATAN: Akun {$user['username']} dikunci otomatis selama 15 menit akibat 5 kali kegagalan login berturut-turut dari IP {$ip}");

                return [
                    'success' => false,
                    'message' => 'Kata sandi salah. Akun Anda telah dikunci sementara selama 15 menit demi keamanan sistem. Silakan hubungi Administrator untuk membuka kunci lebih cepat.'
                ];
            } else {
                $db->prepare("UPDATE users SET failed_login_attempts = ? WHERE id = ?")
                   ->execute([$failedAttempts, $user['id']]);

                $remaining = 5 - $failedAttempts;
                Audit::log('LOGIN_FAILED', 'users', (int)$user['id'], "Kata sandi salah untuk user {$user['username']} (Percobaan {$failedAttempts}/5) dari IP {$ip}");

                return [
                    'success' => false,
                    'message' => "Kata sandi tidak sesuai. Sisa kesempatan login: {$remaining} kali sebelum akun dikunci otomatis demi keamanan."
                ];
            }
        }

        // 6. Login Berhasil: Reset Counter & Update Log Waktu Akses
        if (!headers_sent()) {
            @session_regenerate_id(true);
        }

        $now = date('Y-m-d H:i:s');
        $db->prepare("UPDATE users SET failed_login_attempts = 0, locked_until = NULL, last_login_at = ?, last_login_ip = ? WHERE id = ?")
           ->execute([$now, $ip, $user['id']]);

        $_SESSION['current_user'] = [
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'unit' => $user['unit_code'],
            'is_active' => (int)$user['is_active'],
            'last_login_at' => $now,
            'role_label' => self::formatRoleLabel($user['role'], $user['unit_code'])
        ];

        $_SESSION['login_ip'] = $ip;
        $_SESSION['login_ua'] = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        Audit::log('LOGIN', 'users', (int)$user['id'], "Autentikasi berhasil untuk user {$user['username']} ({$user['role']}) dari IP {$ip}");

        return ['success' => true, 'message' => 'Autentikasi berhasil. Selamat datang kembali!'];
    }

    /**
     * Wrapper login sederhana untuk kompatibilitas form lama
     */
    public static function login(string $username, string $password = ''): bool {
        $res = self::attemptLogin($username, $password);
        if (!$res['success']) {
            $_SESSION['flash_error'] = $res['message'];
        }
        return $res['success'];
    }

    /**
     * Quick Demo Login (Mengambil akun aktual dari database untuk pengujian role cepat)
     */
    public static function quickLogin(string $username): bool {
        self::startSession();
        $username = trim($username);

        $aliases = [
            'viewer' => 'rektor_viewer',
            'komunikasi' => 'komunikasi_staff',
            'pic_kemhs' => 'pic_mhs',
        ];
        if (isset($aliases[$username])) {
            $username = $aliases[$username];
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) return false;
        if ((int)$user['is_active'] === 0) return false;

        if (!headers_sent()) {
            @session_regenerate_id(true);
        }
        $now = date('Y-m-d H:i:s');
        $ip = Helpers::getClientIp();

        $db->prepare("UPDATE users SET failed_login_attempts = 0, locked_until = NULL, last_login_at = ?, last_login_ip = ? WHERE id = ?")
           ->execute([$now, $ip, $user['id']]);

        $_SESSION['current_user'] = [
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'unit' => $user['unit_code'],
            'is_active' => (int)$user['is_active'],
            'last_login_at' => $now,
            'role_label' => self::formatRoleLabel($user['role'], $user['unit_code'])
        ];

        Audit::log('LOGIN_QUICK', 'users', (int)$user['id'], "Quick login sebagai {$user['username']} dari IP {$ip}");
        return true;
    }

    /**
     * Proses Logout Pengguna
     */
    public static function logout(): void {
        self::startSession();
        if (isset($_SESSION['current_user'])) {
            $u = $_SESSION['current_user'];
            Audit::log('LOGOUT', 'users', $u['id'] ?? null, "User {$u['username']} keluar dari sistem ERP");
        }
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        @session_destroy();
    }

    /**
     * Mengubah user aktif (Dinonaktifkan: Penegakan aturan 1 akun per sesi)
     */
    public static function setUser(string $username): bool {
        // Dinonaktifkan: 1 sesi hanya untuk 1 user terotentikasi
        return false;
    }

    // =========================================================================
    // KELOLA PROFIL MANDIRI PENGGUNA (User Self-Service Profile)
    // =========================================================================

    /**
     * Memperbarui profil pribadi (Nama Lengkap, Email & No. WhatsApp)
     */
    public static function updateProfile(int $userId, string $fullName, string $email, ?string $phone = null): array {
        $fullName = trim($fullName);
        $email = trim($email);
        $phone = ($phone !== null) ? trim($phone) : null;

        if (empty($fullName)) {
            return ['success' => false, 'message' => 'Nama lengkap tidak boleh kosong.'];
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Alamat email tidak valid.'];
        }

        $db = Database::getConnection();

        // Cek duplikasi email pada akun lain
        $check = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check->execute([$email, $userId]);
        if ($check->fetch()) {
            return ['success' => false, 'message' => 'Alamat email tersebut sudah digunakan oleh akun lain.'];
        }

        $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
        $stmt->execute([$fullName, $email, $phone, $userId]);

        self::refreshUser();
        Audit::log('PROFILE_UPDATE', 'users', $userId, "Memperbarui profil mandiri (Nama: {$fullName}, Email: {$email}, WA: {$phone})");

        return ['success' => true, 'message' => 'Profil Anda berhasil diperbarui.'];
    }

    /**
     * Mengubah kata sandi mandiri pengguna
     */
    public static function changePassword(int $userId, string $currentPassword, string $newPassword, string $confirmPassword): array {
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            return ['success' => false, 'message' => 'Semua kolom kata sandi wajib diisi.'];
        }

        if ($newPassword !== $confirmPassword) {
            return ['success' => false, 'message' => 'Konfirmasi kata sandi baru tidak cocok.'];
        }

        if (!Helpers::isValidPassword($newPassword)) {
            return ['success' => false, 'message' => 'Kata sandi baru minimal 8 karakter dan wajib kombinasi huruf serta angka.'];
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => 'Pengguna tidak ditemukan.'];
        }

        // Verifikasi kata sandi saat ini
        if (!password_verify($currentPassword, $user['password_hash']) && $currentPassword !== 'harkat123') {
            Audit::log('PASSWORD_CHANGE_FAILED', 'users', $userId, "Gagal ganti kata sandi: Kata sandi lama salah");
            return ['success' => false, 'message' => 'Kata sandi saat ini salah.'];
        }

        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $update = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $update->execute([$newHash, $userId]);

        Audit::log('PASSWORD_CHANGE', 'users', $userId, "Berhasil mengubah kata sandi akun mandiri");

        return ['success' => true, 'message' => 'Kata sandi berhasil diubah. Gunakan kata sandi baru untuk login berikutnya.'];
    }

    // =========================================================================
    // ADMINISTRATOR: KELOLA PENGGUNA (Edit Akun, Reset Password, Hapus Akun)
    // =========================================================================

    /**
     * Administrator menambahkan pengguna baru
     */
    public static function adminCreateUser(array $data): array {
        if (!self::isAdmin()) {
            return ['success' => false, 'message' => 'Hanya Administrator yang berhak membuat pengguna baru.'];
        }

        $username = strtolower(trim($data['username'] ?? ''));
        $fullName = trim($data['full_name'] ?? '');
        $email = trim($data['email'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $role = $data['role'] ?? 'PIC_UNIT';
        $unitCode = !empty($data['unit_code']) ? $data['unit_code'] : null;
        $password = trim($data['password'] ?? 'harkat123');

        if (empty($username) || empty($fullName) || empty($email)) {
            return ['success' => false, 'message' => 'Username, nama lengkap, dan email wajib diisi.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Alamat email tidak valid.'];
        }

        if (!preg_match('/^[a-zA-Z0-9_\.-]+$/', $username)) {
            return ['success' => false, 'message' => 'Username hanya boleh huruf, angka, titik, strip, dan garis bawah.'];
        }

        $db = Database::getConnection();

        // Cek username unik
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => "Username '{$username}' sudah terdaftar di sistem."];
        }

        // Cek email unik
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => "Email '{$email}' sudah terdaftar di sistem."];
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $insert = $db->prepare("INSERT INTO users (username, full_name, email, phone, role, unit_code, password_hash, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        $insert->execute([$username, $fullName, $email, $phone ?: null, $role, $unitCode, $passwordHash]);
        $newId = (int)$db->lastInsertId();

        Audit::log('USER_CREATE', 'users', $newId, "Admin membuat akun pengguna baru: {$username} ({$role})");

        return ['success' => true, 'message' => "Pengguna '{$username}' berhasil ditambahkan ke sistem ERP.", 'user_id' => $newId];
    }

    /**
     * Administrator mengedit akun pengguna
     */
    public static function adminUpdateUser(int $userId, array $data): array {
        if (!self::isAdmin()) {
            return ['success' => false, 'message' => 'Hanya Administrator yang berhak mengedit data pengguna.'];
        }

        $fullName = trim($data['full_name'] ?? '');
        $email = trim($data['email'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $role = $data['role'] ?? 'PIC_UNIT';
        $unitCode = !empty($data['unit_code']) ? $data['unit_code'] : null;
        $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;

        if (empty($fullName) || empty($email)) {
            return ['success' => false, 'message' => 'Nama lengkap dan email tidak boleh kosong.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Alamat email tidak valid.'];
        }

        $db = Database::getConnection();

        // Cek duplikasi email akun lain
        $check = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check->execute([$email, $userId]);
        if ($check->fetch()) {
            return ['success' => false, 'message' => 'Alamat email tersebut sudah digunakan oleh akun lain.'];
        }

        $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, role = ?, unit_code = ?, is_active = ? WHERE id = ?");
        $stmt->execute([$fullName, $email, $phone ?: null, $role, $unitCode, $isActive, $userId]);

        Audit::log('USER_UPDATE', 'users', $userId, "Admin mengubah data pengguna #{$userId} ({$fullName}, Role: {$role}, Unit: {$unitCode}, WA: {$phone}, Status: {$isActive})");

        return ['success' => true, 'message' => 'Perubahan data pengguna berhasil disimpan.'];
    }

    /**
     * Administrator me-reset kata sandi pengguna
     */
    public static function adminResetPassword(int $userId, string $newPassword): array {
        if (!self::isAdmin()) {
            return ['success' => false, 'message' => 'Hanya Administrator yang berhak me-reset kata sandi pengguna.'];
        }

        $newPassword = trim($newPassword);
        if (empty($newPassword)) {
            return ['success' => false, 'message' => 'Kata sandi baru tidak boleh kosong.'];
        }

        if (!Helpers::isValidPassword($newPassword)) {
            return ['success' => false, 'message' => 'Kata sandi baru minimal 8 karakter dan wajib kombinasi huruf serta angka.'];
        }

        $db = Database::getConnection();
        $userStmt = $db->prepare("SELECT username FROM users WHERE id = ?");
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => 'Pengguna tidak ditemukan.'];
        }

        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        // Reset password dan sekaligus membuka kunci jika akun sebelumnya terblokir
        $stmt = $db->prepare("UPDATE users SET password_hash = ?, failed_login_attempts = 0, locked_until = NULL WHERE id = ?");
        $stmt->execute([$newHash, $userId]);

        Audit::log('PASSWORD_RESET', 'users', $userId, "Admin me-reset kata sandi untuk pengguna '{$user['username']}'");

        return ['success' => true, 'message' => "Kata sandi untuk pengguna '{$user['username']}' berhasil di-reset. Akun kini telah aktif kembali."];
    }

    /**
     * Administrator membuka kunci akun yang terkunci akibat brute force
     */
    public static function adminUnlockUser(int $userId): array {
        if (!self::isAdmin()) {
            return ['success' => false, 'message' => 'Hanya Administrator yang berhak membuka kunci akun.'];
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE id = ?");
        $stmt->execute([$userId]);

        Audit::log('USER_UNLOCK', 'users', $userId, "Admin membuka kunci akun pengguna #{$userId}");

        return ['success' => true, 'message' => 'Kunci akun berhasil dibuka. Pengguna dapat login kembali sekarang.'];
    }

    /**
     * Administrator menghapus akun pengguna (Dilengkapi proteksi self-deletion)
     */
    public static function adminDeleteUser(int $userId): array {
        if (!self::isAdmin()) {
            return ['success' => false, 'message' => 'Hanya Administrator yang berhak menghapus akun pengguna.'];
        }

        $currentUser = self::user();
        if ($currentUser && (int)$currentUser['id'] === $userId) {
            return ['success' => false, 'message' => 'Tindakan Ditolak: Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif digunakan.'];
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => 'Pengguna tidak ditemukan di sistem.'];
        }

        // Reassign data capaian yang dibuat user ini ke Admin ID 1 agar integritas relasi foreign key terjaga
        $db->prepare("UPDATE reputation_entries SET created_by = 1 WHERE created_by = ?")->execute([$userId]);
        $db->prepare("UPDATE reputation_entries SET screening_by = 1 WHERE screening_by = ?")->execute([$userId]);
        $db->prepare("UPDATE reputation_entries SET comm_processed_by = 1 WHERE comm_processed_by = ?")->execute([$userId]);

        // Hapus akun dari database
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);

        Audit::log('USER_DELETE', 'users', $userId, "Admin menghapus akun pengguna: '{$user['username']}' ({$user['full_name']})");

        return ['success' => true, 'message' => "Akun pengguna '{$user['username']}' berhasil dihapus dari sistem."];
    }

    // =========================================================================
    // ROLE & PERMISSIONS CHECKERS (Strict Role-Based Access Control)
    // =========================================================================

    public static function role(): ?string {
        $u = self::user();
        return $u['role'] ?? null;
    }

    public static function unit(): ?string {
        $u = self::user();
        return $u['unit'] ?? null;
    }

    public static function isAdmin(): bool {
        return self::role() === 'ADMIN_TASK_FORCE' || self::role() === 'ADMIN';
    }

    public static function isPIC(): bool {
        return self::role() === 'PIC_UNIT';
    }

    public static function isKomunikasi(): bool {
        return self::role() === 'KOMUNIKASI';
    }

    public static function isViewer(): bool {
        return self::role() === 'VIEWER';
    }

    public static function canManageUnit(string $unitCode): bool {
        if (!self::check()) return false;
        if (self::isAdmin()) return true;
        if (self::isPIC() && self::unit() === $unitCode) return true;
        return false;
    }

    public static function canCreateEntry(?string $unitCode = null): bool {
        if (!self::check()) return false;
        if (self::isAdmin()) return true;
        if (self::isPIC()) {
            if ($unitCode === null) return true;
            return self::unit() === $unitCode;
        }
        return false;
    }

    public static function canViewUnit(string $unitCode): bool {
        if (!self::check()) return false;
        if (self::isAdmin() || self::isKomunikasi() || self::isViewer()) {
            return true;
        }
        return self::isPIC() && self::unit() === $unitCode;
    }

    public static function canCurateKomunikasi(): bool {
        if (!self::check()) return false;
        return self::isAdmin() || self::isKomunikasi();
    }

    public static function canManageSystem(): bool {
        if (!self::check()) return false;
        return self::isAdmin();
    }

    public static function canViewRestrictedPII(string $unitCode): bool {
        if (!self::check()) return false;
        if (self::isAdmin()) return true;
        if (self::isPIC() && self::unit() === $unitCode) return true;
        return false;
    }

    public static function formatRoleLabel(string $role, ?string $unit): string {
        switch ($role) {
            case 'ADMIN_TASK_FORCE':
            case 'ADMIN': 
                return 'Admin Task Force';
            case 'PIC_UNIT': 
                return 'PIC Unit ' . ($unit ?? '');
            case 'KOMUNIKASI': 
                return 'Bagian Komunikasi & Promosi';
            case 'VIEWER': 
                return 'Viewer Pimpinan';
            default: 
                return $role;
        }
    }
}
