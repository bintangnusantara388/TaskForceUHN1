<?php
// views/dashboard.php
require_once __DIR__ . '/../app/Models/Reminder.php';
require_once __DIR__ . '/../app/Models/Entry.php';
require_once __DIR__ . '/../app/Config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Services/AIService.php';

$activePeriod = $_GET['period_id'] ?? '2026-10';
if (!isset(PERIODS[$activePeriod])) {
    $activePeriod = '2026-10';
}

$metrics = Reminder::getGlobalMetrics($activePeriod);
$meter = $metrics['meter'];
$chartData = Entry::getChartData($activePeriod);

// Ambil entri terkini untuk periode ini
$recentEntries = Entry::getAll(['period_id' => $activePeriod]);
?>

<div class="space-y-4 sm:space-y-6">

    <!-- Top Hero Banner (Crimson Design Pattern: Modern Card Hero) -->
    <div class="bg-gradient-to-r from-uhn-950 via-uhn-900 to-uhn-800 text-white rounded-3xl p-5 sm:p-8 shadow-xl relative overflow-hidden border border-uhn-900">
        <!-- Subtle decorative background rings -->
        <div class="absolute -right-12 -top-12 w-48 h-48 rounded-full bg-white/5 pointer-events-none"></div>
        <div class="absolute right-32 -bottom-16 w-40 h-40 rounded-full bg-rose-500/10 pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4 sm:gap-6">
            <div>
                <div class="inline-flex items-center gap-1.5 sm:gap-2 px-2.5 sm:px-3 py-1 rounded-full bg-white/10 text-rose-200 text-[10px] sm:text-xs font-bold border border-white/15 backdrop-blur-sm mb-2">
                    <span class="w-2 h-2 rounded-full bg-rose-400 animate-pulse"></span>
                    <span>Monitoring Reputasi</span>
                    <span class="text-white/40">&bull;</span>
                    <span class="text-white font-medium"><?= htmlspecialchars(PERIODS[$activePeriod]) ?></span>
                </div>
                <h1 class="text-xl sm:text-3xl font-black text-white tracking-tight mt-0.5">
                    Dashboard &amp; Progress Meter
                </h1>
                <p class="text-xs sm:text-sm text-rose-100/80 mt-1 max-w-2xl leading-relaxed">
                    Pemantauan data capaian 4 unit strategis untuk publikasi media dan bukti borang pemeringkatan internasional <strong>WURI</strong>, <strong>Uniranks</strong>, &amp; <strong>Webometrics</strong>.
                </p>
            </div>

            <!-- Filter Periode Bulan -->
            <form action="/index.php" method="GET" class="flex items-center justify-between sm:justify-start gap-2 bg-black/25 backdrop-blur-md p-2 rounded-2xl border border-white/15 w-full sm:w-auto self-start md:self-auto">
                <input type="hidden" name="page" value="dashboard">
                <label for="period_select" class="text-xs font-semibold text-rose-200 pl-2 whitespace-nowrap">Periode:</label>
                <select name="period_id" id="period_select" onchange="this.form.submit()" 
                        class="flex-1 sm:flex-initial bg-white border-0 text-slate-900 text-xs font-bold rounded-xl px-3 py-2 focus:ring-2 focus:ring-rose-400 shadow-sm cursor-pointer">
                    <?php foreach (PERIODS as $pid => $pname): ?>
                        <option value="<?= $pid ?>" <?= ($pid === $activePeriod) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pname) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>

    <?php if (Auth::isAdmin()): ?>
    <!-- Quick Admin Banner: AI Engine Status & Configuration -->
    <?php
    $aiSettings = AIService::getAllSettings();
    $hasApiKey = !empty($aiSettings['gemini_api_key'] ?? '') || !empty(GEMINI_API_KEY ?? '');
    ?>
    <div class="bg-gradient-to-r from-amber-500/10 via-rose-50 to-amber-50 border border-amber-200/80 rounded-2xl p-3.5 sm:p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
        <div class="flex items-center gap-3 min-w-0">
            <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center font-black flex-shrink-0 shadow-sm">
                <svg class="w-5 h-5 text-amber-100" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2L14.4 7.6L20 10L14.4 12.4L12 18L9.6 12.4L4 10L9.6 7.6L12 2Z"/>
                    <path d="M19 16L20.2 18.8L23 20L20.2 21.2L19 24L17.8 21.2L15 20L17.8 18.8L19 16Z"/>
                    <path d="M5 16L6.2 18.8L9 20L6.2 21.2L5 24L3.8 21.2L1 20L3.8 18.8L5 16Z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-black text-slate-900">Teknologi AI Generator WURI &amp; Rilis Pers</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $hasApiKey ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' ?>">
                        <?= $hasApiKey ? '● Gemini API Aktif' : '○ Standby (UHN Neural Synthesizer)' ?>
                    </span>
                    <span class="hidden md:inline-block text-[11px] text-slate-500">Model: <?= htmlspecialchars($aiSettings['ai_model'] ?? 'gemini-1.5-flash') ?></span>
                </div>
                <p class="text-xs text-slate-600 truncate mt-0.5">
                    Kelola API Key Google Gemini, atur model LLM, parameter kreativitas, dan panduan narasi institusional.
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0 self-start sm:self-center">
            <a href="/index.php?page=ai_config" 
               class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-uhn-900 hover:bg-uhn-950 text-white rounded-xl text-xs font-bold shadow-sm transition-all hover:scale-102">
                <span>Kelola AI Engine</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- 4 Metrik Kartu Ringkasan (2x2 Grid di Mobile, 4 Kolom di Desktop) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        
        <!-- Total Data Masuk -->
        <div class="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-5 border border-rose-100 shadow-sm relative overflow-hidden transition-all hover:shadow-md flex flex-col justify-between group">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">TOTAL CAPAIAN</p>
                    <div class="flex items-baseline gap-1.5 mt-1">
                        <h3 class="text-2xl sm:text-3xl font-black text-slate-900"><?= $metrics['total_entries'] ?></h3>
                        <span class="text-xs sm:text-sm text-slate-400 font-medium">Data</span>
                    </div>
                </div>
                <div class="w-9 h-9 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-rose-50 text-uhn-800 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-rose-50 flex items-center justify-between text-[11px] sm:text-xs">
                <a href="/index.php?page=entries&period_id=<?= urlencode($activePeriod) ?>" class="text-uhn-800 font-bold hover:underline">Lihat Data &rarr;</a>
            </div>
        </div>

        <!-- Target Signature Bulanan -->
        <div class="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-5 border border-rose-100 shadow-sm relative overflow-hidden transition-all hover:shadow-md flex flex-col justify-between group">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">SIGNATURE</p>
                    <div class="flex items-baseline gap-1 mt-1">
                        <h3 class="text-2xl sm:text-3xl font-black <?= $metrics['all_fulfilled'] ? 'text-emerald-600' : 'text-uhn-800' ?>">
                            <?= $metrics['units_fulfilled'] ?>
                        </h3>
                        <span class="text-xs sm:text-sm text-slate-400 font-medium">/ 4 Unit</span>
                    </div>
                </div>
                <div class="w-9 h-9 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-rose-50 text-uhn-800 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 fill-uhn-800" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-rose-50 flex items-center justify-between text-[11px] sm:text-xs">
                <a href="/index.php?page=reminders&period_id=<?= urlencode($activePeriod) ?>" class="text-uhn-800 font-bold hover:underline">Pengingat &rarr;</a>
            </div>
        </div>

        <!-- Antrian Belum Diolah Komunikasi -->
        <div class="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-5 border border-rose-100 shadow-sm relative overflow-hidden transition-all hover:shadow-md flex flex-col justify-between group">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">ANTRIAN OLAH</p>
                    <div class="flex items-baseline gap-1.5 mt-1">
                        <h3 class="text-2xl sm:text-3xl font-black text-amber-600"><?= $metrics['total_belum_diolah_kom'] ?></h3>
                        <span class="text-xs sm:text-sm text-slate-400 font-medium">Siap</span>
                    </div>
                </div>
                <div class="w-9 h-9 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-rose-50 flex items-center justify-between text-[11px] sm:text-xs">
                <?php if (Auth::canCurateKomunikasi()): ?>
                    <a href="/index.php?page=komunikasi&period_id=<?= urlencode($activePeriod) ?>" class="text-amber-700 font-bold hover:underline">Meja Kurasi &rarr;</a>
                <?php else: ?>
                    <span class="text-slate-400 truncate">Tim Komunikasi</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sudah Terbit -->
        <div class="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-5 border border-rose-100 shadow-sm relative overflow-hidden transition-all hover:shadow-md flex flex-col justify-between group">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">SUDAH TERBIT</p>
                    <div class="flex items-baseline gap-1.5 mt-1">
                        <h3 class="text-2xl sm:text-3xl font-black text-emerald-600"><?= $metrics['total_sudah_terbit'] ?></h3>
                        <span class="text-xs sm:text-sm text-slate-400 font-medium">Rilis</span>
                    </div>
                </div>
                <div class="w-9 h-9 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-rose-50 flex items-center justify-between text-[11px] sm:text-xs">
                <a href="/index.php?page=export&period_id=<?= urlencode($activePeriod) ?>" class="text-emerald-700 font-bold hover:underline">Borang WURI &rarr;</a>
            </div>
        </div>
    </div>

    <!-- PROGRESS METER PER UNIT (Mobile Card View + Desktop Table) -->
    <div class="bg-white rounded-3xl shadow-sm border border-rose-100 overflow-hidden">
        <div class="px-5 sm:px-6 py-4 sm:py-5 bg-gradient-to-r from-uhn-950 via-uhn-900 to-uhn-800 text-white flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-base sm:text-lg font-black flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-400"></span>
                    Progress Meter Pemenuhan per Unit
                </h2>
                <p class="text-[11px] sm:text-xs text-rose-200 mt-0.5">
                    Periode: <?= htmlspecialchars(PERIODS[$activePeriod]) ?> (Wajib min. 1 Signature/bulan)
                </p>
            </div>
            <a href="/index.php?page=reminders&period_id=<?= urlencode($activePeriod) ?>" 
               class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 bg-white text-uhn-900 hover:bg-rose-50 rounded-xl text-xs font-black shadow-sm transition-all self-start sm:self-auto">
                <svg class="w-3.5 h-3.5 text-uhn-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                Uji Kirim Pengingat
            </a>
        </div>

        <!-- A. TAMPILAN MOBILE CARD LIST (< md) -->
        <div class="md:hidden divide-y divide-rose-100">
            <?php foreach ($meter as $uCode => $m): 
                $pct = $m['is_signature_fulfilled'] ? 100 : 0;
            ?>
                <div class="p-4 space-y-3">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-black text-slate-900 text-sm"><?= htmlspecialchars($m['unit_code']) ?></span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                                    <?= $m['total_entries'] ?> Data
                                </span>
                            </div>
                            <div class="text-xs text-slate-500 mt-0.5"><?= htmlspecialchars($m['unit_name']) ?></div>
                        </div>
                        <?php if ($m['is_signature_fulfilled']): ?>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 flex-shrink-0">
                                ✓ <?= $m['signature_count'] ?> Signature
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-rose-100 text-uhn-900 border border-rose-300 animate-pulse flex-shrink-0">
                                ✗ Belum Setor
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Progress Bar Mobile -->
                    <div>
                        <div class="flex items-center justify-between text-[11px] mb-1">
                            <span class="text-slate-500 font-medium">Pemenuhan Target Bulanan</span>
                            <span class="font-bold <?= $pct >= 100 ? 'text-emerald-700' : 'text-uhn-800' ?>"><?= $pct ?>%</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden border border-slate-200">
                            <div class="h-2 rounded-full <?= $pct >= 100 ? 'bg-gradient-to-r from-emerald-500 to-teal-500' : 'bg-gradient-to-r from-uhn-800 to-rose-600' ?>" style="width: <?= $pct ?>%"></div>
                        </div>
                    </div>

                    <!-- Mini Badges Status Kurasi -->
                    <div class="grid grid-cols-3 gap-2 text-center text-[11px]">
                        <div class="py-1.5 px-2 rounded-xl bg-emerald-50/70 text-emerald-800 font-semibold border border-emerald-100">
                            <?= $m['siap_diolah'] ?> Siap
                        </div>
                        <div class="py-1.5 px-2 rounded-xl bg-amber-50/70 text-amber-800 font-semibold border border-amber-100">
                            <?= $m['sedang_diolah'] ?> Olah
                        </div>
                        <div class="py-1.5 px-2 rounded-xl bg-blue-50/70 text-blue-800 font-semibold border border-blue-100">
                            <?= $m['sudah_terbit'] ?> Terbit
                        </div>
                    </div>

                    <!-- Tombol Aksi Mobile -->
                    <div class="flex items-center gap-2 pt-1">
                        <?php if (Auth::canManageUnit($uCode)): ?>
                            <a href="/index.php?page=entries_create&unit_code=<?= $uCode ?>&period_id=<?= urlencode($activePeriod) ?>" 
                               class="flex-1 py-2 px-3 bg-gradient-to-r from-uhn-800 to-uhn-700 text-white rounded-xl text-xs font-bold text-center shadow-sm">
                                + Input Capaian
                            </a>
                        <?php endif; ?>
                        <a href="/index.php?page=entries&unit_code=<?= $uCode ?>&period_id=<?= urlencode($activePeriod) ?>" 
                           class="flex-1 py-2 px-3 bg-slate-100 text-slate-700 hover:bg-slate-200 rounded-xl text-xs font-bold text-center">
                            Lihat Rincian &rarr;
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- B. TAMPILAN DESKTOP TABLE (>= md) -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-700">
                <thead class="bg-rose-50/50 text-slate-500 uppercase text-[11px] font-bold border-b border-rose-100">
                    <tr>
                        <th class="px-6 py-3.5 font-bold">Unit Kerja</th>
                        <th class="px-4 py-3.5 font-bold text-center">Total Data</th>
                        <th class="px-4 py-3.5 font-bold text-center">Status Target Signature</th>
                        <th class="px-4 py-3.5 font-bold text-center">Tingkat Pemenuhan</th>
                        <th class="px-4 py-3.5 font-bold text-center">Screening &amp; Kurasi</th>
                        <th class="px-6 py-3.5 font-bold text-right">Aksi Cepat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-rose-50">
                    <?php foreach ($meter as $uCode => $m): ?>
                        <?php $pct = $m['is_signature_fulfilled'] ? 100 : 0; ?>
                        <tr class="hover:bg-rose-50/30 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-extrabold text-slate-900"><?= htmlspecialchars($m['unit_code']) ?></div>
                                <div class="text-xs text-slate-500"><?= htmlspecialchars($m['unit_name']) ?></div>
                            </td>
                            <td class="px-4 py-4 text-center font-black text-slate-900">
                                <span class="text-base"><?= $m['total_entries'] ?></span>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <?php if ($m['is_signature_fulfilled']): ?>
                                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        TERPENUHI (<?= $m['signature_count'] ?> Signature)
                                    </div>
                                <?php else: ?>
                                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-rose-100 text-uhn-900 border border-rose-300 animate-pulse">
                                        <svg class="w-4 h-4 text-uhn-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                        BELUM TERPENUHI (0)
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 text-center w-44">
                                <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden border border-slate-200">
                                    <div class="h-2.5 rounded-full <?= $pct >= 100 ? 'bg-gradient-to-r from-emerald-500 to-teal-500' : 'bg-gradient-to-r from-uhn-800 to-rose-600' ?>" style="width: <?= $pct ?>%"></div>
                                </div>
                                <span class="text-[11px] font-bold <?= $pct >= 100 ? 'text-emerald-700' : 'text-uhn-800' ?> mt-1.5 block">
                                    <?= $pct ?>% Terpenuhi
                                </span>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <div class="flex items-center justify-center gap-1.5 text-xs">
                                    <span class="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-800 font-semibold" title="Siap Diolah">
                                        <?= $m['siap_diolah'] ?> Siap
                                    </span>
                                    <span class="px-2 py-0.5 rounded-md bg-amber-50 text-amber-800 font-semibold" title="Sedang Diolah">
                                        <?= $m['sedang_diolah'] ?> Olah
                                    </span>
                                    <span class="px-2 py-0.5 rounded-md bg-blue-50 text-blue-800 font-semibold" title="Sudah Terbit">
                                        <?= $m['sudah_terbit'] ?> Terbit
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <?php if (Auth::canManageUnit($uCode)): ?>
                                        <a href="/index.php?page=entries_create&unit_code=<?= $uCode ?>&period_id=<?= urlencode($activePeriod) ?>" 
                                           class="inline-flex items-center gap-1 px-3 py-1.5 bg-rose-50 text-uhn-900 hover:bg-rose-100 rounded-xl text-xs font-bold transition-colors">
                                            + Input Data
                                        </a>
                                    <?php endif; ?>
                                    <a href="/index.php?page=entries&unit_code=<?= $uCode ?>&period_id=<?= urlencode($activePeriod) ?>" 
                                       class="inline-flex items-center px-3 py-1.5 bg-slate-100 text-slate-700 hover:bg-slate-200 rounded-xl text-xs font-semibold transition-colors">
                                        Rincian &rarr;
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ANALYTICS GRAFIK CHART.JS (CRIMSON PALETTE) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
        <!-- Chart 1: Distribusi Capaian per Unit -->
        <div class="bg-white rounded-3xl shadow-sm border border-rose-100 p-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Distribusi Capaian per Unit</h3>
                    <p class="text-xs text-slate-500">Perbandingan Data Signature vs Reguler (<?= htmlspecialchars(PERIODS[$activePeriod]) ?>)</p>
                </div>
            </div>
            <div class="relative h-56 sm:h-64">
                <canvas id="unitDistChart"></canvas>
            </div>
        </div>

        <!-- Chart 2: Status Pengolahan Komunikasi -->
        <div class="bg-white rounded-3xl shadow-sm border border-rose-100 p-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Status Kurasi Konten Komunikasi</h3>
                    <p class="text-xs text-slate-500">Kesiapan publikasi media dari data masuk</p>
                </div>
            </div>
            <div class="relative h-56 sm:h-64 flex items-center justify-center">
                <canvas id="commStatusChart"></canvas>
            </div>
        </div>
    </div>

    <!-- PANDUAN & DAFTAR CAPAIAN TERKINI -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
        
        <!-- Kolom Kiri (2 span): Daftar Data Masuk Bulan Ini -->
        <div class="lg:col-span-2 bg-white rounded-3xl shadow-sm border border-rose-100 p-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-black text-slate-900">Daftar Capaian Masuk Periode Ini</h3>
                    <p class="text-xs text-slate-500"><?= count($recentEntries) ?> data capaian tercatat pada <?= htmlspecialchars(PERIODS[$activePeriod]) ?></p>
                </div>
                <a href="/index.php?page=entries&period_id=<?= urlencode($activePeriod) ?>" class="text-xs font-bold text-uhn-800 hover:text-uhn-900 hover:underline whitespace-nowrap">
                    Semua &rarr;
                </a>
            </div>

            <div class="space-y-3">
                <?php if (empty($recentEntries)): ?>
                    <p class="text-sm text-slate-500 py-6 text-center italic">Belum ada data capaian diinput untuk periode ini.</p>
                <?php else: ?>
                    <?php foreach (array_slice($recentEntries, 0, 5) as $entry): ?>
                        <div class="p-4 rounded-2xl border border-rose-100 hover:border-rose-300 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white hover:bg-rose-50/20 shadow-sm">
                            <div class="space-y-1.5">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-800">
                                        <?= htmlspecialchars($entry['unit_code']) ?>
                                    </span>
                                    <?= Helpers::badgeTier($entry['tier_type']) ?>
                                    <?= Helpers::badgeScreening($entry['screening_status']) ?>
                                    <?= Helpers::badgeComm($entry['comm_status']) ?>
                                    <?php if ($entry['name_publication_consent'] === 'TIDAK'): ?>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-100 text-uhn-900 border border-rose-300" title="Subjek menolak publikasi nama">
                                            ⚠️ Restricted PDP
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <h4 class="text-sm font-bold text-slate-900 leading-snug">
                                    <a href="/index.php?page=entries_show&id=<?= $entry['id'] ?>" class="hover:text-uhn-800 transition-colors">
                                        <?= htmlspecialchars($entry['title']) ?>
                                    </a>
                                </h4>
                                <p class="text-xs text-slate-500 line-clamp-2 sm:line-clamp-1">
                                    <?= htmlspecialchars($entry['short_description']) ?>
                                </p>
                            </div>
                            <div class="flex-shrink-0 self-end sm:self-center">
                                <a href="/index.php?page=entries_show&id=<?= $entry['id'] ?>" 
                                   class="inline-block px-3.5 py-1.5 bg-rose-50/60 border border-rose-200 text-uhn-800 hover:bg-rose-100 rounded-xl text-xs font-bold shadow-sm transition-colors">
                                    Detail &rarr;
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Kolom Kanan: SOP & Ketentuan Reputasi -->
        <div class="bg-gradient-to-br from-uhn-950 via-uhn-900 to-uhn-800 text-white rounded-3xl shadow-sm p-5 sm:p-7 flex flex-col justify-between border border-uhn-900">
            <div>
                <div class="w-10 h-10 rounded-2xl bg-white/10 text-rose-200 flex items-center justify-center mb-4 border border-white/15">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3 class="text-base font-black text-white mb-2">Pedoman Screening &amp; Reputasi</h3>
                <ul class="space-y-2.5 text-xs text-rose-100/80 leading-relaxed">
                    <li class="flex items-start gap-2">
                        <span class="text-rose-300 font-bold">&bull;</span>
                        <span><strong>Minimal 1 Signature/bulan:</strong> Setiap unit wajib menyetor minimal 1 data unggulan untuk menjaga ritme rilis media.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-rose-300 font-bold">&bull;</span>
                        <span><strong>Screening Mandiri:</strong> Hanya data yang lolos verifikasi kelayakan publikasi di unit yang diubah ke <em>Siap Diolah</em>.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-rose-300 font-bold">&bull;</span>
                        <span><strong>Perlindungan Privasi PDP:</strong> Perhatikan persetujuan nama. Data bertanda <em>TIDAK</em> tidak boleh dirilis nama aslinya ke publik.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-rose-300 font-bold">&bull;</span>
                        <span><strong>Jadwal Pengingat:</strong> Notifikasi sistem otomatis terkirim setiap tanggal 15 (Mid-Month) dan tanggal 26 (End-Month).</span>
                    </li>
                </ul>
            </div>

            <div class="mt-6 pt-4 border-t border-white/10">
                <a href="/index.php?page=export&period_id=<?= urlencode($activePeriod) ?>" 
                   class="w-full inline-flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-white text-uhn-900 hover:bg-rose-50 text-xs font-black shadow-md transition-colors">
                    <svg class="w-4 h-4 text-uhn-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Unduh Rekap Borang WURI &amp; Excel
                </a>
            </div>
        </div>

    </div>

</div>

<!-- Inisialisasi Grafik Chart.js (Crimson Maroon Color Palette) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctxUnit = document.getElementById('unitDistChart');
    if (ctxUnit) {
        new Chart(ctxUnit, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chartData['units']) ?>,
                datasets: [
                    {
                        label: 'Signature (Unggulan)',
                        data: <?= json_encode($chartData['signature']) ?>,
                        backgroundColor: '#8b0020',
                        borderRadius: 8
                    },
                    {
                        label: 'Reguler',
                        data: <?= json_encode($chartData['reguler']) ?>,
                        backgroundColor: '#fda4b0',
                        borderRadius: 8
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    }

    const ctxComm = document.getElementById('commStatusChart');
    if (ctxComm) {
        new Chart(ctxComm, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($chartData['comm_labels']) ?>,
                datasets: [{
                    data: <?= json_encode($chartData['comm_values']) ?>,
                    backgroundColor: ['#f59e0b', '#e11d49', '#10b981'],
                    borderWidth: 3,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }
});
</script>
