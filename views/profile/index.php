<?php
// views/profile/index.php
require_once __DIR__ . '/../../app/Config.php';
require_once __DIR__ . '/../../app/Database.php';
require_once __DIR__ . '/../../app/Auth.php';
require_once __DIR__ . '/../../app/Helpers.php';

if (!Auth::check()) {
    header("Location: /index.php?page=login");
    exit;
}

$currentUser = Auth::user();
$db = Database::getConnection();

// Ambil data terbaru langsung dari database
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$currentUser['id']]);
$userData = $stmt->fetch() ?: $currentUser;

// Ambil 5 audit log terakhir milik akun ini
$auditStmt = $db->prepare("SELECT * FROM audit_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$auditStmt->execute([$currentUser['id']]);
$recentAudits = $auditStmt->fetchAll();

// Inisial untuk avatar
$nameParts = explode(' ', trim($userData['full_name'] ?? $userData['name'] ?? 'User'));
$initials = '';
if (count($nameParts) >= 2) {
    $initials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1));
} else {
    $initials = strtoupper(substr($nameParts[0], 0, 2));
}
?>

<div class="space-y-6 max-w-5xl mx-auto">

    <!-- Header Profil Card (Crimson Maroon Styling) -->
    <div class="bg-white rounded-3xl shadow-sm border border-rose-100 p-6 md:p-8 relative overflow-hidden">
        <!-- Background Pattern subtle glow -->
        <div class="absolute -right-16 -top-16 w-56 h-56 bg-gradient-to-br from-rose-100 to-rose-50 rounded-full opacity-60 pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="flex items-center gap-5">
                <!-- Avatar Circle -->
                <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-gradient-to-br from-uhn-950 via-uhn-900 to-uhn-800 text-white flex items-center justify-center font-black text-xl sm:text-2xl shadow-lg shadow-rose-950/20 border-2 border-white ring-4 ring-rose-100/80 flex-shrink-0">
                    <?= htmlspecialchars($initials) ?>
                </div>

                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-0.5 rounded-full bg-rose-100 text-uhn-900 text-xs font-bold border border-rose-200 mb-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Akun Aktif</span>
                        <span class="text-rose-300">&bull;</span>
                        <span><?= htmlspecialchars(Auth::formatRoleLabel($userData['role'], $userData['unit_code'])) ?></span>
                    </div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 leading-tight">
                        <?= htmlspecialchars($userData['full_name'] ?? $userData['name'] ?? '') ?>
                    </h1>
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500 mt-1">
                        <span class="font-mono text-slate-600 font-semibold flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            @<?= htmlspecialchars($userData['username']) ?>
                        </span>
                        <span class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <?= htmlspecialchars($userData['email']) ?>
                        </span>
                        <?php if (!empty($userData['phone'])): ?>
                            <span class="inline-flex items-center gap-1 text-emerald-700 font-semibold font-mono">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                                <?= Helpers::formatPhoneNumber($userData['phone']) ?>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($userData['unit_code'])): ?>
                            <span class="bg-rose-50 text-uhn-900 px-2 py-0.5 rounded font-bold border border-rose-100">
                                Unit: <?= htmlspecialchars($userData['unit_code']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Info Badge (Login Terakhir) -->
            <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200/80 text-xs space-y-1.5 w-full md:w-auto min-w-[200px]">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Aktivitas Sesi</div>
                <div class="text-slate-700 font-semibold flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Terakhir Masuk:</span>
                </div>
                <div class="text-slate-900 font-mono text-[11px] pl-5.5">
                    <?= !empty($userData['last_login_at']) ? date('d M Y, H:i', strtotime($userData['last_login_at'])) . ' WIB' : 'Baru saja' ?>
                </div>
                <?php if (!empty($userData['last_login_ip'])): ?>
                    <div class="text-slate-400 text-[10px] pl-5.5 font-mono">
                        IP: <?= htmlspecialchars($userData['last_login_ip']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Grid: Form Profil & Ganti Password -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Form 1: Ubah Informasi Profil Pribadi -->
        <div class="bg-white rounded-3xl shadow-sm border border-rose-100 p-5 sm:p-6 space-y-5">
            <div class="flex items-center gap-3 border-b border-rose-100 pb-3">
                <div class="w-8 h-8 rounded-xl bg-rose-100 text-uhn-800 flex items-center justify-center font-bold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </div>
                <div>
                    <h2 class="text-base font-black text-slate-900">Ubah Data Pribadi</h2>
                    <p class="text-xs text-slate-500">Perbarui nama lengkap, nomor WhatsApp, dan email kontak institusi.</p>
                </div>
            </div>

            <form action="/index.php?action=profile_update" method="POST" class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Username Institusi</label>
                    <input type="text" value="<?= htmlspecialchars($userData['username']) ?>" disabled
                           class="w-full bg-slate-100 border border-slate-200 rounded-xl p-3 font-mono text-slate-500 cursor-not-allowed select-none">
                    <p class="text-[11px] text-slate-400 mt-1">Username terdaftar di sistem dan tidak dapat diubah secara mandiri.</p>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Lengkap &amp; Gelar <span class="text-red-500">*</span></label>
                    <input type="text" name="full_name" required value="<?= htmlspecialchars($userData['full_name'] ?? $userData['name'] ?? '') ?>" 
                           placeholder="Contoh: Dr. Ir. Ahmad Dahlan, M.T."
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-uhn-700 focus:border-uhn-700 transition-all">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Email Institusi <span class="text-red-500">*</span></label>
                    <input type="email" name="email" required value="<?= htmlspecialchars($userData['email']) ?>" 
                           placeholder="nama@harkatnegeri.ac.id"
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-uhn-700 focus:border-uhn-700 transition-all">
                    <p class="text-[11px] text-slate-400 mt-1">Digunakan untuk koordinasi task force dan notifikasi pengingat target.</p>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">No. WhatsApp / HP Aktif</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($userData['phone'] ?? '') ?>" 
                           placeholder="Contoh: 081234567890 atau 6281234567890"
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-mono text-slate-900 focus:bg-white focus:ring-2 focus:ring-uhn-700 focus:border-uhn-700 transition-all">
                    <p class="text-[11px] text-slate-400 mt-1">Nomor WhatsApp aktif untuk menerima pesan pengingat target bulanan dari Administrator.</p>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-1">
                    <div>
                        <label class="block font-bold text-slate-500 mb-1 text-[11px]">Role Sistem</label>
                        <div class="bg-slate-100 border border-slate-200 rounded-xl p-2.5 font-bold text-slate-600">
                            <?= htmlspecialchars($userData['role']) ?>
                        </div>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-500 mb-1 text-[11px]">Unit Ditugaskan</label>
                        <div class="bg-slate-100 border border-slate-200 rounded-xl p-2.5 font-bold text-slate-600">
                            <?= htmlspecialchars($userData['unit_code'] ?: 'Semua Unit') ?>
                        </div>
                    </div>
                </div>

                <div class="pt-3">
                    <button type="submit" 
                            class="w-full py-3 bg-gradient-to-r from-uhn-800 to-uhn-700 hover:from-uhn-900 hover:to-uhn-800 text-white rounded-xl font-bold shadow-md shadow-rose-950/20 transition-all flex items-center justify-center gap-2">
                        <svg class="w-4 h-4 text-rose-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Simpan Perubahan Profil
                    </button>
                </div>
            </form>
        </div>

        <!-- Form 2: Ganti Kata Sandi Mandiri (Bcrypt Protected) -->
        <div class="bg-white rounded-3xl shadow-sm border border-rose-100 p-5 sm:p-6 space-y-5">
            <div class="flex items-center gap-3 border-b border-rose-100 pb-3">
                <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center font-bold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </div>
                <div>
                    <h2 class="text-base font-black text-slate-900">Ganti Kata Sandi</h2>
                    <p class="text-xs text-slate-500">Perkuat keamanan akses dengan kata sandi terstandar.</p>
                </div>
            </div>

            <!-- Box Ketentuan Kata Sandi -->
            <div class="bg-rose-50/50 border border-rose-200/70 rounded-2xl p-3.5 text-xs text-slate-600 space-y-1">
                <div class="font-bold text-uhn-900 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-uhn-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Ketentuan Kata Sandi Aman:
                </div>
                <ul class="list-disc list-inside text-[11px] text-slate-600 pl-1 space-y-0.5">
                    <li>Minimal <strong>8 karakter</strong></li>
                    <li>Wajib mengombinasikan <strong>huruf</strong> dan <strong>angka</strong></li>
                    <li>Diankripsi dengan standar algoritma satu arah <strong>Bcrypt</strong></li>
                </ul>
            </div>

            <form action="/index.php?action=profile_password" method="POST" class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Kata Sandi Saat Ini <span class="text-red-500">*</span></label>
                    <input type="password" name="current_password" required placeholder="Masukkan kata sandi lama Anda"
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-mono text-slate-900 focus:bg-white focus:ring-2 focus:ring-uhn-700 focus:border-uhn-700 transition-all">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Kata Sandi Baru <span class="text-red-500">*</span></label>
                    <input type="password" name="new_password" required minlength="8" placeholder="Minimal 8 karakter (huruf & angka)"
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-mono text-slate-900 focus:bg-white focus:ring-2 focus:ring-uhn-700 focus:border-uhn-700 transition-all">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Ulangi Kata Sandi Baru <span class="text-red-500">*</span></label>
                    <input type="password" name="confirm_password" required minlength="8" placeholder="Ketik ulang kata sandi baru"
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-mono text-slate-900 focus:bg-white focus:ring-2 focus:ring-uhn-700 focus:border-uhn-700 transition-all">
                </div>

                <div class="pt-3">
                    <button type="submit" 
                            class="w-full py-3 bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-700 hover:to-amber-800 text-white rounded-xl font-bold shadow-md shadow-amber-900/20 transition-all flex items-center justify-center gap-2">
                        <svg class="w-4 h-4 text-amber-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                        Perbarui Kata Sandi
                    </button>
                </div>
            </form>
        </div>

    </div>

    <!-- Riwayat Aktivitas & Audit Akun Terakhir -->
    <div class="bg-white rounded-3xl shadow-sm border border-rose-100 overflow-hidden">
        <div class="px-5 sm:px-6 py-4 bg-gradient-to-r from-uhn-950 via-uhn-900 to-uhn-800 text-white flex items-center justify-between">
            <h2 class="text-sm font-bold flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                Audit Trail &amp; Catatan Aktivitas Akun Ini
            </h2>
            <span class="text-xs text-rose-200 hidden sm:inline">Keamanan &amp; Akuntabilitas</span>
        </div>

        <div class="p-4 sm:p-6">
            <?php if (empty($recentAudits)): ?>
                <div class="text-center py-6 text-slate-400 text-xs italic">
                    Belum ada riwayat aktivitas tercatat untuk akun ini.
                </div>
            <?php else: ?>
                <div class="divide-y divide-rose-50 text-xs">
                    <?php foreach ($recentAudits as $log): 
                        $actLabel = $log['action_type'] ?? $log['action'] ?? 'INFO';
                        $descLabel = $log['details'] ?? $log['description'] ?? '-';
                        $targetId = $log['entry_id'] ?? $log['record_id'] ?? null;
                    ?>
                        <div class="py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2 hover:bg-rose-50/20 px-2 rounded-xl transition-colors">
                            <div class="flex items-start gap-3">
                                <span class="px-2 py-0.5 rounded font-mono font-bold text-[10px] bg-slate-100 text-slate-700 border border-slate-200 flex-shrink-0 mt-0.5">
                                    <?= htmlspecialchars($actLabel) ?>
                                </span>
                                <div>
                                    <div class="text-slate-800 font-medium">
                                        <?= htmlspecialchars($descLabel) ?>
                                    </div>
                                    <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                                        <?= $targetId ? 'Target ID: #' . htmlspecialchars((string)$targetId) . ' &bull; ' : '' ?>IP: <?= htmlspecialchars($log['ip_address'] ?? '127.0.0.1') ?>
                                    </div>
                                </div>
                            </div>
                            <div class="text-[11px] text-slate-400 font-mono whitespace-nowrap self-end sm:self-center">
                                <?= date('d M Y H:i:s', strtotime($log['created_at'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>
