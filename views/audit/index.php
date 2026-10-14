<?php
// views/audit/index.php
require_once __DIR__ . '/../../app/Models/Audit.php';
require_once __DIR__ . '/../../app/Config.php';
require_once __DIR__ . '/../../app/Auth.php';
require_once __DIR__ . '/../../app/Helpers.php';

if (!Auth::isAdmin()) {
    echo '<div class="p-6 bg-rose-50 border border-rose-200 text-uhn-900 rounded-2xl">Akses ditolak. Halaman ini hanya untuk Administrator Task Force.</div>';
    return;
}

$logs = Audit::getRecent(50);
$dbSize = file_exists(DB_PATH) ? filesize(DB_PATH) : 0;
?>

<div class="space-y-6">

    <!-- Top Info & Backup Card (Crimson Style) -->
    <div class="bg-white rounded-3xl shadow-sm border border-rose-100 p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-rose-100 text-uhn-900 text-xs font-bold border border-rose-200 mb-1">
                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                <span>Keamanan &amp; Audit Trail</span>
                <span class="text-slate-300">&bull;</span>
                <span>Kepatuhan UU PDP No. 27/2022</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 mt-1">
                Log Aktivitas Sistem &amp; Pencadangan Data
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl leading-relaxed">
                Setiap aksi pembuatan, pengubahan, screening, kurasi komunikasi, dan ekspor data sensitif personil dicatat secara permanen untuk jejak akuntabilitas.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="/index.php?action=backup_download" 
               class="px-4 py-2.5 bg-gradient-to-r from-uhn-800 to-uhn-700 hover:from-uhn-900 hover:to-uhn-800 text-white font-bold text-xs rounded-xl shadow-md shadow-rose-950/20 transition-all flex items-center gap-2">
                <svg class="w-4 h-4 text-rose-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                Unduh Snapshot Database (<?= Helpers::formatBytes($dbSize) ?>)
            </a>
        </div>
    </div>

    <!-- Kebijakan Backup -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="p-5 bg-white rounded-3xl border border-rose-100 shadow-sm">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Jadwal Backup Harian</span>
            <p class="text-base font-black text-slate-900 mt-1">Setiap Hari Pukul 02:00 WIB</p>
            <p class="text-xs text-slate-500 mt-1">Otomatisasi skrip via cron scheduler server institusi.</p>
        </div>

        <div class="p-5 bg-white rounded-3xl border border-rose-100 shadow-sm">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Retensi Log Audit</span>
            <p class="text-base font-black text-slate-900 mt-1">365 Hari (1 Tahun Penuh)</p>
            <p class="text-xs text-slate-500 mt-1">Tersimpan permanen untuk verifikasi asesor akreditasi.</p>
        </div>

        <div class="p-5 bg-white rounded-3xl border border-rose-100 shadow-sm">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Integritas Basis Data</span>
            <p class="text-base font-black text-emerald-600 mt-1">SQLite 3 (WAL Mode Active)</p>
            <p class="text-xs text-slate-500 mt-1">ACID compliant dengan isolasi transaksi penuh.</p>
        </div>
    </div>

    <!-- Tabel Log Audit Terkini -->
    <div class="bg-white rounded-3xl shadow-sm border border-rose-100 overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-uhn-950 via-uhn-900 to-uhn-800 text-white flex items-center justify-between">
            <h2 class="text-sm font-bold flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                50 Rekaman Jejak Audit Terkini
            </h2>
            <span class="text-xs text-rose-200">Real-time Logging</span>
        </div>

        <!-- MOBILE CARD VIEW (< md) -->
        <div class="md:hidden divide-y divide-rose-100">
            <?php if (empty($logs)): ?>
                <div class="p-8 text-center text-slate-400 text-xs italic">
                    Belum ada catatan audit trail.
                </div>
            <?php else: ?>
                <?php foreach ($logs as $l): 
                    $action = $l['action'] ?? ($l['action_type'] ?? 'INFO');
                    $badgeClass = str_starts_with($action, 'CREATE') ? 'bg-emerald-100 text-emerald-800' : 
                                 (str_starts_with($action, 'UPDATE') ? 'bg-blue-100 text-blue-800' : 
                                 (str_starts_with($action, 'DELETE') ? 'bg-red-100 text-red-800' : 'bg-purple-100 text-purple-800'));
                ?>
                    <div class="p-4 space-y-2 hover:bg-rose-50/20 transition-colors">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold <?= $badgeClass ?>">
                                    <?= htmlspecialchars($action) ?>
                                </span>
                                <?php if (!empty($l['record_id']) || !empty($l['entry_id'])): ?>
                                    <span class="font-mono text-xs font-bold text-slate-500">
                                        #<?= htmlspecialchars((string)($l['record_id'] ?? $l['entry_id'])) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <span class="font-mono text-[10px] text-slate-400">
                                <?= htmlspecialchars($l['created_at']) ?>
                            </span>
                        </div>

                        <div class="text-xs text-slate-700 font-medium leading-relaxed">
                            <?= htmlspecialchars($l['details'] ?? '') ?>
                        </div>

                        <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1">
                            <div class="flex items-center gap-1.5">
                                <span class="font-bold text-slate-800"><?= htmlspecialchars($l['user_name'] ?? 'System') ?></span>
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-600">
                                    <?= htmlspecialchars($l['user_role'] ?? '-') ?>
                                </span>
                            </div>
                            <span class="font-mono text-[10px] text-slate-400">
                                IP: <?= htmlspecialchars($l['ip_address'] ?? '127.0.0.1') ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- DESKTOP TABLE VIEW (>= md) -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700">
                <thead class="bg-rose-50/50 text-slate-500 uppercase font-bold text-[11px] border-b border-rose-100">
                    <tr>
                        <th class="px-6 py-3.5">Waktu Akses</th>
                        <th class="px-4 py-3.5">Aktor / Pengguna</th>
                        <th class="px-4 py-3.5">Role</th>
                        <th class="px-4 py-3.5">Aksi / Operasi</th>
                        <th class="px-4 py-3.5">Target Entri</th>
                        <th class="px-6 py-3.5">Rincian Perubahan &amp; IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-rose-50 font-mono">
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-400 italic">
                                Belum ada catatan audit trail.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $l): ?>
                            <tr class="hover:bg-rose-50/30 font-sans">
                                <td class="px-6 py-3.5 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    <?= htmlspecialchars($l['created_at']) ?>
                                </td>
                                <td class="px-4 py-3.5 font-bold text-slate-900">
                                    <?= htmlspecialchars($l['user_name'] ?? 'System') ?>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
                                        <?= htmlspecialchars($l['user_role'] ?? '-') ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <?php $action = $l['action'] ?? ($l['action_type'] ?? 'INFO'); ?>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold 
                                        <?= str_starts_with($action, 'CREATE') ? 'bg-emerald-100 text-emerald-800' : 
                                           (str_starts_with($action, 'UPDATE') ? 'bg-blue-100 text-blue-800' : 
                                           (str_starts_with($action, 'DELETE') ? 'bg-red-100 text-red-800' : 'bg-purple-100 text-purple-800')) ?>">
                                        <?= htmlspecialchars($action) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 font-mono text-slate-500 font-bold">
                                    <?= (!empty($l['record_id']) || !empty($l['entry_id'])) ? '#' . ($l['record_id'] ?? $l['entry_id']) : '-' ?>
                                </td>
                                <td class="px-6 py-3.5 text-xs text-slate-600">
                                    <span><?= htmlspecialchars($l['details'] ?? '') ?></span>
                                    <span class="text-[10px] text-slate-400 block font-mono">IP: <?= htmlspecialchars($l['ip_address'] ?? '127.0.0.1') ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
