<?php
// views/users/index.php
require_once __DIR__ . '/../../app/Config.php';
require_once __DIR__ . '/../../app/Database.php';
require_once __DIR__ . '/../../app/Auth.php';
require_once __DIR__ . '/../../app/Helpers.php';

if (!Auth::isAdmin()) {
    echo '<div class="p-6 bg-rose-50 border border-rose-200 text-uhn-900 rounded-2xl">Akses ditolak. Halaman ini hanya untuk Administrator Task Force.</div>';
    return;
}

$db = Database::getConnection();
$users = $db->query("
    SELECT u.*, m.unit_name 
    FROM users u 
    LEFT JOIN master_units m ON u.unit_code = m.unit_code 
    ORDER BY u.id ASC
")->fetchAll();

$periods = $db->query("SELECT * FROM reporting_periods ORDER BY start_date ASC")->fetchAll();
$currentUserId = (int)Auth::user()['id'];
$nowTime = time();
?>

<div class="space-y-6">

    <!-- Header Box (Crimson Style) -->
    <div class="bg-white rounded-3xl shadow-sm border border-rose-100 p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-rose-100 text-uhn-900 text-xs font-bold border border-rose-200 mb-1">
                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                <span>Administrator Task Force</span>
                <span class="text-slate-300">&bull;</span>
                <span>Tata Kelola Pengguna &amp; Akses</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 mt-1">
                Manajemen Pengguna ERP &amp; Hak Akses Role
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl leading-relaxed">
                Kelola kredensial, hak akses, reset kata sandi, pembukaan akun terkunci, dan otorisasi PIC unit untuk integritas data institusi.
            </p>
        </div>

        <button type="button" onclick="document.getElementById('addUserModal').classList.remove('hidden')" 
                class="px-4 py-2.5 bg-gradient-to-r from-uhn-800 to-uhn-700 hover:from-uhn-900 hover:to-uhn-800 text-white rounded-xl text-xs font-bold shadow-md shadow-rose-950/20 transition-all flex items-center gap-1.5 self-start md:self-auto">
            <svg class="w-4 h-4 text-rose-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            + Tambah Pengguna Baru
        </button>
    </div>

    <!-- Tabel Daftar Pengguna -->
    <div class="bg-white rounded-3xl shadow-sm border border-rose-100 overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-uhn-950 via-uhn-900 to-uhn-800 text-white flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h2 class="text-sm font-bold flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                    Daftar Akun Pengguna Terdaftar (<?= count($users) ?> Akun)
                </h2>
                <div class="text-[11px] text-rose-200/80">Dilengkapi hashing Bcrypt &amp; pencegahan brute-force (Kunci otomatis setelah 5 kegagalan)</div>
            </div>
            <span class="text-xs text-rose-200 bg-white/10 px-3 py-1 rounded-full font-mono">Role-Based Access Control</span>
        </div>

        <!-- MOBILE CARD VIEW (< md) -->
        <div class="md:hidden divide-y divide-rose-100">
            <?php foreach ($users as $u): 
                $isLocked = !empty($u['locked_until']) && strtotime($u['locked_until']) > $nowTime;
                $isActive = (int)($u['is_active'] ?? 1) === 1;
                $isSelf = ((int)$u['id'] === $currentUserId);
            ?>
                <div class="p-4 space-y-3 hover:bg-rose-50/20 transition-colors">
                    <!-- Top Row: User Name, Self badge, Status badge -->
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="font-mono text-xs font-bold text-slate-400">#<?= $u['id'] ?></span>
                                <span class="font-bold text-slate-900 text-sm">
                                    <?= htmlspecialchars($u['full_name'] ?? $u['name'] ?? '') ?>
                                </span>
                                <?php if ($isSelf): ?>
                                    <span class="text-[10px] bg-rose-100 text-uhn-900 px-1.5 py-0.5 rounded font-bold border border-rose-200">Anda</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-xs text-slate-500 mt-0.5 flex items-center gap-2 flex-wrap">
                                <span class="font-mono font-semibold text-slate-700">@<?= htmlspecialchars($u['username']) ?></span>
                                <span>&bull;</span>
                                <span><?= htmlspecialchars($u['email']) ?></span>
                                <?php if (!empty($u['phone'])): ?>
                                    <span>&bull;</span>
                                    <span class="inline-flex items-center gap-1 text-emerald-700 font-semibold font-mono">
                                        <svg class="w-3 h-3 text-emerald-600" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                                        <?= Helpers::formatPhoneNumber($u['phone']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="flex-shrink-0">
                            <?php if ($isLocked): ?>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-600 animate-ping"></span>
                                    Terkunci
                                </span>
                            <?php elseif (!$isActive): ?>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                                    Nonaktif
                                </span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                    Aktif
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Role & Unit Info Pill Row -->
                    <div class="flex flex-wrap items-center justify-between gap-2 bg-slate-50 p-2.5 rounded-xl border border-slate-100 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded-full font-bold text-[10px] 
                                <?= ($u['role'] === 'ADMIN') ? 'bg-rose-100 text-uhn-900 border border-rose-200' : 
                                   (($u['role'] === 'KOMUNIKASI') ? 'bg-purple-100 text-purple-800' : 
                                   (($u['role'] === 'PIC_UNIT') ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-700')) ?>">
                                <?= htmlspecialchars($u['role']) ?>
                            </span>
                            <span class="text-slate-600 font-semibold">
                                <?= $u['unit_code'] ? 'Unit: ' . htmlspecialchars($u['unit_code']) : 'Semua Unit' ?>
                            </span>
                        </div>
                        <?php if (!empty($u['last_login_at'])): ?>
                            <span class="text-[10px] text-slate-400 font-mono">
                                Login: <?= date('d/m/y H:i', strtotime($u['last_login_at'])) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Touch Action Buttons Row -->
                    <div class="flex flex-wrap items-center gap-2 pt-1">
                        <?php if ($isLocked): ?>
                            <form action="/index.php?action=users_unlock" method="POST" class="inline" onsubmit="return confirm('Buka kunci akun ini agar pengguna dapat login kembali?');">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <button type="submit" 
                                        class="py-2 px-3 bg-amber-50 hover:bg-amber-100 text-amber-800 rounded-xl text-xs font-bold border border-amber-200 transition-colors inline-flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                                    Buka Kunci
                                </button>
                            </form>
                        <?php endif; ?>

                        <button type="button" 
                                onclick="openEditUserModal(<?= htmlspecialchars(json_encode($u)) ?>)"
                                class="flex-1 py-2 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors inline-flex items-center justify-center gap-1">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Edit
                        </button>

                        <button type="button" 
                                onclick="openResetPasswordModal(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['username'])) ?>', '<?= htmlspecialchars(addslashes($u['full_name'] ?? $u['name'] ?? '')) ?>')"
                                class="flex-1 py-2 px-3 bg-amber-50 hover:bg-amber-100 text-amber-800 rounded-xl text-xs font-bold border border-amber-200/80 transition-colors inline-flex items-center justify-center gap-1">
                            <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                            Reset Sandi
                        </button>

                        <?php if (!$isSelf): ?>
                            <button type="button" 
                                    onclick="openDeleteUserModal(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['username'])) ?>', '<?= htmlspecialchars(addslashes($u['full_name'] ?? $u['name'] ?? '')) ?>')"
                                    class="py-2 px-3 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-xl text-xs font-bold border border-rose-200 transition-colors inline-flex items-center justify-center gap-1">
                                <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Hapus
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- DESKTOP TABLE VIEW (>= md) -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700">
                <thead class="bg-rose-50/50 text-slate-500 uppercase font-bold text-[11px] border-b border-rose-100">
                    <tr>
                        <th class="px-5 py-3.5">ID</th>
                        <th class="px-5 py-3.5">Nama &amp; Kontak</th>
                        <th class="px-4 py-3.5">Username</th>
                        <th class="px-4 py-3.5">Role Sistem</th>
                        <th class="px-4 py-3.5">Unit Kerja</th>
                        <th class="px-4 py-3.5">Keamanan / Sesi</th>
                        <th class="px-5 py-3.5 text-right">Tindakan Admin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-rose-50">
                    <?php foreach ($users as $u): 
                        $isLocked = !empty($u['locked_until']) && strtotime($u['locked_until']) > $nowTime;
                        $isActive = (int)($u['is_active'] ?? 1) === 1;
                        $isSelf = ((int)$u['id'] === $currentUserId);
                    ?>
                        <tr class="hover:bg-rose-50/30 transition-colors">
                            <td class="px-5 py-4 font-mono font-bold text-slate-400">#<?= $u['id'] ?></td>
                            <td class="px-5 py-4">
                                <div class="font-bold text-slate-900 text-sm flex items-center gap-1.5">
                                    <?= htmlspecialchars($u['full_name'] ?? $u['name'] ?? '') ?>
                                    <?php if ($isSelf): ?>
                                        <span class="text-[10px] bg-rose-100 text-uhn-900 px-1.5 py-0.2 rounded font-bold border border-rose-200">Anda</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-slate-500 text-[11px] flex items-center gap-2 mt-0.5 flex-wrap">
                                    <span><?= htmlspecialchars($u['email']) ?></span>
                                    <?php if (!empty($u['phone'])): ?>
                                        <span>&bull;</span>
                                        <span class="inline-flex items-center gap-1 text-emerald-700 font-semibold font-mono">
                                            <svg class="w-3 h-3 text-emerald-600" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                                            <?= Helpers::formatPhoneNumber($u['phone']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-4 py-4 font-mono text-slate-700 font-semibold">
                                <?= htmlspecialchars($u['username']) ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-0.5 rounded-full font-bold text-[10px] 
                                    <?= ($u['role'] === 'ADMIN') ? 'bg-rose-100 text-uhn-900 border border-rose-200' : 
                                       (($u['role'] === 'KOMUNIKASI') ? 'bg-purple-100 text-purple-800' : 
                                       (($u['role'] === 'PIC_UNIT') ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-700')) ?>">
                                    <?= htmlspecialchars($u['role']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <?php if ($u['unit_code']): ?>
                                    <span class="font-bold text-slate-800"><?= htmlspecialchars($u['unit_code']) ?></span>
                                    <span class="text-[11px] text-slate-500 block"><?= htmlspecialchars($u['unit_name'] ?? '') ?></span>
                                <?php else: ?>
                                    <span class="text-slate-400 italic">Semua Unit</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4">
                                <?php if ($isLocked): ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-600 animate-ping"></span>
                                        Terkunci (<?= date('H:i', strtotime($u['locked_until'])) ?>)
                                    </span>
                                <?php elseif (!$isActive): ?>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                                        Nonaktif
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        Aktif
                                    </span>
                                <?php endif; ?>

                                <?php if (!empty($u['last_login_at'])): ?>
                                    <div class="text-[10px] text-slate-400 mt-1 font-mono">
                                        Login: <?= date('d/m/y H:i', strtotime($u['last_login_at'])) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    
                                    <!-- Jika Terkunci: Tombol Buka Kunci -->
                                    <?php if ($isLocked): ?>
                                        <form action="/index.php?action=users_unlock" method="POST" class="inline" onsubmit="return confirm('Buka kunci akun ini agar pengguna dapat login kembali?');">
                                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                            <button type="submit" 
                                                    class="p-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 rounded-lg text-xs font-bold border border-amber-200 transition-colors" 
                                                    title="Buka Kunci Akun">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <!-- Tombol Edit Akun -->
                                    <button type="button" 
                                            onclick="openEditUserModal(<?= htmlspecialchars(json_encode($u)) ?>)"
                                            class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition-colors inline-flex items-center gap-1"
                                            title="Edit Data Pengguna">
                                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        <span>Edit</span>
                                    </button>

                                    <!-- Tombol Reset Kata Sandi -->
                                    <button type="button" 
                                            onclick="openResetPasswordModal(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['username'])) ?>', '<?= htmlspecialchars(addslashes($u['full_name'] ?? $u['name'] ?? '')) ?>')"
                                            class="px-2.5 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 rounded-lg text-xs font-bold border border-amber-200/80 transition-colors inline-flex items-center gap-1"
                                            title="Reset Kata Sandi">
                                        <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                        <span>Reset Sandi</span>
                                    </button>

                                    <!-- Tombol Hapus Akun -->
                                    <?php if ($isSelf): ?>
                                        <button type="button" disabled 
                                                class="px-2 py-1.5 bg-slate-100 text-slate-400 rounded-lg text-xs font-semibold cursor-not-allowed opacity-60"
                                                title="Akun Anda yang sedang aktif tidak dapat dihapus">
                                            Hapus
                                        </button>
                                    <?php else: ?>
                                        <button type="button" 
                                                onclick="openDeleteUserModal(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['username'])) ?>', '<?= htmlspecialchars(addslashes($u['full_name'] ?? $u['name'] ?? '')) ?>')"
                                                class="px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg text-xs font-bold border border-rose-200 transition-colors inline-flex items-center gap-1"
                                                title="Hapus Akun Pengguna">
                                            <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            <span>Hapus</span>
                                        </button>
                                    <?php endif; ?>

                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tabel Periode Pelaporan (Siklus Pelaporan) -->
    <div class="bg-white rounded-3xl shadow-sm border border-rose-100 overflow-hidden">
        <div class="px-6 py-4 bg-rose-50/50 border-b border-rose-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900">
                Siklus Periode Pelaporan (Sep 2026 - Agu 2027)
            </h2>
            <span class="text-xs text-slate-500 font-mono">12 Siklus Bulanan</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700">
                <thead class="bg-white text-slate-500 uppercase font-bold text-[11px] border-b border-rose-100">
                    <tr>
                        <th class="px-6 py-3">Kode Periode</th>
                        <th class="px-6 py-3">Nama Bulan</th>
                        <th class="px-6 py-3">Rentang Tanggal</th>
                        <th class="px-6 py-3 text-right">Status Siklus</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-rose-50">
                    <?php foreach ($periods as $p): ?>
                        <tr class="hover:bg-rose-50/30">
                            <td class="px-6 py-3 font-mono font-bold text-slate-800"><?= htmlspecialchars($p['period_id']) ?></td>
                            <td class="px-6 py-3 font-bold text-slate-900"><?= htmlspecialchars($p['display_name'] ?? $p['period_name'] ?? $p['period_id']) ?></td>
                            <td class="px-6 py-3 text-slate-500"><?= htmlspecialchars($p['start_date']) ?> s.d. <?= htmlspecialchars($p['end_date']) ?></td>
                            <td class="px-6 py-3 text-right">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Terbuka
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- ========================================================================= -->
<!-- MODAL: TAMBAH PENGGUNA BARU -->
<!-- ========================================================================= -->
<div id="addUserModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm flex items-end sm:items-center justify-center p-0 sm:p-4">
    <div class="bg-white rounded-t-3xl sm:rounded-3xl max-w-md w-full p-6 pb-8 sm:pb-6 shadow-2xl border border-rose-100 space-y-4">
        <div class="w-10 h-1 bg-slate-200 rounded-full mx-auto mb-1 sm:hidden"></div>
        <div class="flex items-center justify-between border-b border-rose-100 pb-3">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-xl bg-rose-100 text-uhn-900 flex items-center justify-center font-bold">
                    +
                </div>
                <h3 class="text-base font-black text-slate-900">Tambah Akun Pengguna Baru</h3>
            </div>
            <button type="button" onclick="document.getElementById('addUserModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-xl font-bold">
                &times;
            </button>
        </div>

        <form action="/index.php?action=users_store" method="POST" class="space-y-4 text-xs">
            <div>
                <label class="block font-bold text-slate-700 mb-1">Nama Lengkap &amp; Gelar <span class="text-red-500">*</span></label>
                <input type="text" name="full_name" required placeholder="Contoh: Dr. Ir. Ahmad Dahlan, M.T." 
                       class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-medium focus:ring-2 focus:ring-uhn-700">
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Username <span class="text-red-500">*</span></label>
                <input type="text" name="username" required placeholder="Contoh: pic_kemhs2" 
                       class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono focus:ring-2 focus:ring-uhn-700">
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Email Institusi <span class="text-red-500">*</span></label>
                <input type="email" name="email" required placeholder="nama@harkatnegeri.ac.id" 
                       class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-medium focus:ring-2 focus:ring-uhn-700">
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">No. WhatsApp / HP Aktif</label>
                <input type="text" name="phone" placeholder="Contoh: 081234567890 atau 6281234567890" 
                       class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono focus:ring-2 focus:ring-uhn-700">
                <span class="text-[10px] text-slate-400">Nomor WhatsApp aktif untuk penerimaan pengingat target pelaporan dari Admin.</span>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Kata Sandi Awal <span class="text-red-500">*</span></label>
                <input type="password" name="password" required value="harkat123" minlength="8"
                       class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono focus:ring-2 focus:ring-uhn-700">
                <span class="text-[10px] text-slate-400">Minimal 8 karakter kombinasi huruf &amp; angka (Default: harkat123).</span>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Role <span class="text-red-500">*</span></label>
                    <select name="role" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-bold focus:ring-2 focus:ring-uhn-700">
                        <option value="PIC_UNIT">PIC Unit</option>
                        <option value="KOMUNIKASI">Komunikasi</option>
                        <option value="VIEWER">Viewer (Pimpinan)</option>
                        <option value="ADMIN">Admin Task Force</option>
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Unit Kerja</label>
                    <select name="unit_code" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-bold focus:ring-2 focus:ring-uhn-700">
                        <option value="">(Tidak Ada / Semua)</option>
                        <option value="PPM">PPM</option>
                        <option value="PSDM">PSDM</option>
                        <option value="KEMAHASISWAAN">Kemahasiswaan</option>
                        <option value="ALUMNI_KARIR">Alumni Karir</option>
                    </select>
                </div>
            </div>

            <div class="pt-4 border-t border-rose-100 flex items-center justify-end gap-2">
                <button type="button" onclick="document.getElementById('addUserModal').classList.add('hidden')" 
                        class="px-4 py-2.5 border border-slate-300 text-slate-600 rounded-xl font-bold hover:bg-slate-50">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-uhn-800 to-uhn-700 hover:from-uhn-900 hover:to-uhn-800 text-white rounded-xl font-bold shadow-md shadow-rose-950/20">
                    Simpan Pengguna
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: EDIT PENGGUNA -->
<!-- ========================================================================= -->
<div id="editUserModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm flex items-end sm:items-center justify-center p-0 sm:p-4">
    <div class="bg-white rounded-t-3xl sm:rounded-3xl max-w-md w-full p-6 pb-8 sm:pb-6 shadow-2xl border border-rose-100 space-y-4">
        <div class="w-10 h-1 bg-slate-200 rounded-full mx-auto mb-1 sm:hidden"></div>
        <div class="flex items-center justify-between border-b border-rose-100 pb-3">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center font-bold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900">Edit Akun Pengguna</h3>
                    <p class="text-[11px] text-slate-500" id="editUsernameLabel"></p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('editUserModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-xl font-bold">
                &times;
            </button>
        </div>

        <form action="/index.php?action=users_update" method="POST" class="space-y-4 text-xs">
            <input type="hidden" name="id" id="editUserId">

            <div>
                <label class="block font-bold text-slate-700 mb-1">Nama Lengkap &amp; Gelar <span class="text-red-500">*</span></label>
                <input type="text" name="full_name" id="editFullName" required
                       class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-medium focus:ring-2 focus:ring-uhn-700">
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Email Institusi <span class="text-red-500">*</span></label>
                <input type="email" name="email" id="editEmail" required
                       class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-medium focus:ring-2 focus:ring-uhn-700">
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">No. WhatsApp / HP Aktif</label>
                <input type="text" name="phone" id="editPhone" placeholder="Contoh: 081234567890 atau 6281234567890" 
                       class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono focus:ring-2 focus:ring-uhn-700">
                <span class="text-[10px] text-slate-400">Nomor WhatsApp aktif untuk penerimaan pengingat target pelaporan dari Admin.</span>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Role Sistem <span class="text-red-500">*</span></label>
                    <select name="role" id="editRole" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-bold focus:ring-2 focus:ring-uhn-700">
                        <option value="PIC_UNIT">PIC Unit</option>
                        <option value="KOMUNIKASI">Komunikasi</option>
                        <option value="VIEWER">Viewer (Pimpinan)</option>
                        <option value="ADMIN">Admin Task Force</option>
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Unit Kerja</label>
                    <select name="unit_code" id="editUnitCode" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-bold focus:ring-2 focus:ring-uhn-700">
                        <option value="">(Tidak Ada / Semua)</option>
                        <option value="PPM">PPM</option>
                        <option value="PSDM">PSDM</option>
                        <option value="KEMAHASISWAAN">Kemahasiswaan</option>
                        <option value="ALUMNI_KARIR">Alumni Karir</option>
                    </select>
                </div>
            </div>

            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between">
                <div>
                    <div class="font-bold text-slate-800">Status Akun Aktif</div>
                    <div class="text-[10px] text-slate-500">Jika dinonaktifkan, akun ini tidak dapat login ke sistem.</div>
                </div>
                <input type="checkbox" name="is_active" id="editIsActive" value="1" class="w-5 h-5 text-uhn-700 rounded focus:ring-uhn-700 cursor-pointer">
            </div>

            <div class="pt-4 border-t border-rose-100 flex items-center justify-end gap-2">
                <button type="button" onclick="document.getElementById('editUserModal').classList.add('hidden')" 
                        class="px-4 py-2.5 border border-slate-300 text-slate-600 rounded-xl font-bold hover:bg-slate-50">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-uhn-800 to-uhn-700 hover:from-uhn-900 hover:to-uhn-800 text-white rounded-xl font-bold shadow-md shadow-rose-950/20">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: RESET KATA SANDI OLEH ADMINISTRATOR -->
<!-- ========================================================================= -->
<div id="resetPasswordModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm flex items-end sm:items-center justify-center p-0 sm:p-4">
    <div class="bg-white rounded-t-3xl sm:rounded-3xl max-w-md w-full p-6 pb-8 sm:pb-6 shadow-2xl border border-rose-100 space-y-4">
        <div class="w-10 h-1 bg-slate-200 rounded-full mx-auto mb-1 sm:hidden"></div>
        <div class="flex items-center justify-between border-b border-rose-100 pb-3">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center font-bold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900">Reset Kata Sandi Pengguna</h3>
                    <p class="text-[11px] text-slate-500" id="resetTargetInfo"></p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('resetPasswordModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-xl font-bold">
                &times;
            </button>
        </div>

        <form action="/index.php?action=users_reset_password" method="POST" class="space-y-4 text-xs">
            <input type="hidden" name="id" id="resetUserId">

            <div class="bg-amber-50/70 border border-amber-200/80 rounded-2xl p-3 text-slate-600 text-[11px]">
                Administrator berwenang memberikan kata sandi baru. Setelah di-reset, jika akun pengguna sempat terkunci akibat percobaan gagal, kunci akun akan <strong>langsung dibuka</strong> secara otomatis.
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="font-bold text-slate-700">Kata Sandi Baru <span class="text-red-500">*</span></label>
                    <button type="button" onclick="generateRandomPass()" class="text-[10px] font-bold text-uhn-800 hover:underline">
                        + Buat Acak
                    </button>
                </div>
                <div class="relative">
                    <input type="text" name="new_password" id="resetNewPassInput" required minlength="8" 
                           placeholder="Minimal 8 karakter (huruf & angka)"
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono text-slate-900 focus:ring-2 focus:ring-amber-500">
                </div>
                <div class="text-[10px] text-slate-400 mt-1">Wajib minimal 8 karakter dan merupakan kombinasi huruf dan angka.</div>
            </div>

            <div class="pt-4 border-t border-rose-100 flex items-center justify-end gap-2">
                <button type="button" onclick="document.getElementById('resetPasswordModal').classList.add('hidden')" 
                        class="px-4 py-2.5 border border-slate-300 text-slate-600 rounded-xl font-bold hover:bg-slate-50">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-700 hover:to-amber-800 text-white rounded-xl font-bold shadow-md shadow-amber-900/20">
                    Tetapkan Sandi Baru
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: KONFIRMASI HAPUS PENGGUNA -->
<!-- ========================================================================= -->
<div id="deleteUserModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm flex items-end sm:items-center justify-center p-0 sm:p-4">
    <div class="bg-white rounded-t-3xl sm:rounded-3xl max-w-md w-full p-6 pb-8 sm:pb-6 shadow-2xl border border-rose-100 space-y-4">
        <div class="w-10 h-1 bg-slate-200 rounded-full mx-auto mb-1 sm:hidden"></div>
        <div class="flex items-center gap-3 border-b border-rose-100 pb-3">
            <div class="w-10 h-10 rounded-2xl bg-rose-100 text-uhn-900 flex items-center justify-center font-bold flex-shrink-0">
                <svg class="w-5 h-5 text-uhn-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </div>
            <div>
                <h3 class="text-base font-black text-slate-900">Konfirmasi Hapus Akun</h3>
                <p class="text-xs text-slate-500">Tindakan ini tidak dapat dibatalkan.</p>
            </div>
        </div>

        <form action="/index.php?action=users_delete" method="POST" class="space-y-4 text-xs">
            <input type="hidden" name="id" id="deleteUserId">

            <p class="text-slate-700 text-xs leading-relaxed">
                Apakah Anda yakin ingin menghapus akun pengguna <strong id="deleteUserDisplay" class="text-slate-900"></strong>?
            </p>

            <div class="p-3 bg-rose-50 border border-rose-200 rounded-2xl text-[11px] text-slate-600 space-y-1">
                <div class="font-bold text-uhn-900">Perlindungan Integritas Data Institusi:</div>
                <p>Data capaian yang pernah diinput atau diverifikasi oleh akun ini akan dialihkan secara aman ke Administrator Task Force agar riwayat borang akreditasi tetap utuh.</p>
            </div>

            <div class="pt-3 border-t border-rose-100 flex items-center justify-end gap-2">
                <button type="button" onclick="document.getElementById('deleteUserModal').classList.add('hidden')" 
                        class="px-4 py-2.5 border border-slate-300 text-slate-600 rounded-xl font-bold hover:bg-slate-50">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-rose-700 to-rose-800 hover:from-rose-800 hover:to-rose-900 text-white rounded-xl font-bold shadow-md shadow-rose-950/20">
                    Ya, Hapus Akun
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditUserModal(user) {
    document.getElementById('editUserId').value = user.id;
    document.getElementById('editFullName').value = user.full_name || user.name || '';
    document.getElementById('editEmail').value = user.email || '';
    document.getElementById('editPhone').value = user.phone || '';
    document.getElementById('editRole').value = user.role || 'PIC_UNIT';
    document.getElementById('editUnitCode').value = user.unit_code || '';
    document.getElementById('editIsActive').checked = (parseInt(user.is_active) === 1 || user.is_active === undefined);
    document.getElementById('editUsernameLabel').innerText = 'Username: @' + user.username;
    document.getElementById('editUserModal').classList.remove('hidden');
}

function openResetPasswordModal(id, username, fullName) {
    document.getElementById('resetUserId').value = id;
    document.getElementById('resetTargetInfo').innerText = fullName + ' (@' + username + ')';
    document.getElementById('resetNewPassInput').value = 'Harkat' + Math.floor(1000 + Math.random() * 9000);
    document.getElementById('resetPasswordModal').classList.remove('hidden');
}

function generateRandomPass() {
    const specials = ['!', '@', '#'];
    const spec = specials[Math.floor(Math.random() * specials.length)];
    const num = Math.floor(1000 + Math.random() * 9000);
    document.getElementById('resetNewPassInput').value = 'Harkat' + num + spec;
}

function openDeleteUserModal(id, username, fullName) {
    document.getElementById('deleteUserId').value = id;
    document.getElementById('deleteUserDisplay').innerText = fullName + ' (@' + username + ')';
    document.getElementById('deleteUserModal').classList.remove('hidden');
}
</script>
