<?php
// views/admin/ai_config.php
require_once __DIR__ . '/../../app/Config.php';
require_once __DIR__ . '/../../app/Auth.php';
require_once __DIR__ . '/../../app/Helpers.php';
require_once __DIR__ . '/../../app/Services/AIService.php';

// Strict Guard: Hanya Administrator Task Force yang berhak mengakses
if (!Auth::isAdmin()) {
    echo '<div class="p-6 bg-rose-50 border border-rose-200 text-uhn-900 rounded-2xl">Akses Ditolak: Halaman Konfigurasi AI hanya dapat dikelola oleh Administrator Task Force.</div>';
    return;
}

$engines = AIService::getAllEngines();
$health = AIService::getEngineHealthSummary();
$failoverLogs = AIService::getFailoverLogs(15);
$settings = AIService::getAllSettings();
$stats = AIService::getStats();
$isConfigured = AIService::isGeminiConfigured();
$currentApiKey = $settings['gemini_api_key'] ?? '';
$activeModel = $settings['ai_model'] ?? 'gemini-1.5-flash';
$defaultTone = $settings['ai_default_tone'] ?? 'STANDAR';
$temperature = $settings['ai_temperature'] ?? '0.4';
$fallbackEnabled = ($settings['ai_enable_fallback'] ?? '1') === '1';
$maxTokens = $settings['ai_max_tokens'] ?? '400';
$customInstructions = $settings['ai_system_instructions'] ?? '';
?>

<div class="space-y-6">

    <!-- Header Banner Studio AI (Crimson Gradient) -->
    <div class="bg-gradient-to-r from-uhn-950 via-uhn-900 to-uhn-800 text-white rounded-3xl shadow-xl p-6 sm:p-8 flex flex-col lg:flex-row lg:items-center justify-between gap-6 border border-uhn-900 relative overflow-hidden">
        <!-- Watermark AI Icon -->
        <div class="absolute -right-6 -bottom-6 text-white/5 select-none pointer-events-none">
            <svg class="w-64 h-64" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
        </div>

        <div class="relative z-10 space-y-2">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 text-rose-200 text-xs font-bold border border-white/15 backdrop-blur-sm">
                <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                <span>Administrator Task Force</span>
                <span class="text-white/40">&bull;</span>
                <span>Enterprise Multi-Engine AI Architecture</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-white leading-tight">
                Pusat Kendali &amp; Manajemen Multi-Engine AI
            </h1>
            <p class="text-xs sm:text-sm text-rose-100/80 max-w-2xl leading-relaxed">
                Kelola armada penyedia AI (Google Gemini, DeepSeek, OpenAI, Groq, Synthesizer Lokal UHN), atur prioritas auto-failover, monitor status latensi real-time, dan konfigurasikan mekanisme percobaan ulang (retry).
            </p>
        </div>

        <div class="relative z-10 flex flex-wrap items-center gap-2.5 flex-shrink-0">
            <!-- Button: Uji Semua Engine -->
            <button type="button" onclick="testAllEnginesAjax()" id="btnTestAll"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white rounded-2xl font-black text-xs shadow-md active:scale-95 transition-all">
                <svg id="testAllIcon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                <span id="testAllText">⚡ Uji Semua Engine (Test All)</span>
            </button>

            <!-- Button: Tambah Engine Baru -->
            <button type="button" onclick="openAddEngineModal()"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-white text-uhn-900 hover:bg-rose-50 rounded-2xl font-black text-xs shadow-md active:scale-95 transition-all border border-white/20">
                <svg class="w-4 h-4 text-uhn-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Tambah AI Engine Baru</span>
            </button>
        </div>
    </div>

    <!-- 4 KPI Cards: AI Fleet Health Monitoring -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <!-- Card 1: Total Engines -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl sm:rounded-3xl border border-rose-100 shadow-sm flex flex-col justify-between">
            <span class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Total Engine Terdaftar</span>
            <div class="mt-2">
                <span class="text-xl sm:text-2xl font-black text-slate-900">
                    <?= (int)$health['total'] ?> <span class="text-xs font-bold text-slate-400">Penyedia / Model</span>
                </span>
                <p class="text-[10px] sm:text-xs text-slate-500 mt-0.5">
                    Cloud LLM &amp; Local Offline Synthesizer
                </p>
            </div>
        </div>

        <!-- Card 2: Active Engines -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl sm:rounded-3xl border border-rose-100 shadow-sm flex flex-col justify-between">
            <span class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Engine Aktif &amp; Siaga</span>
            <div class="mt-2">
                <span class="text-xl sm:text-2xl font-black text-uhn-900">
                    <?= (int)$health['active'] ?> <span class="text-xs font-bold text-emerald-600">Siap Eksekusi</span>
                </span>
                <p class="text-[10px] sm:text-xs text-slate-500 mt-0.5">
                    <?= (int)$health['disabled'] ?> engine dalam mode *standby*
                </p>
            </div>
        </div>

        <!-- Card 3: Health Status -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl sm:rounded-3xl border border-rose-100 shadow-sm flex flex-col justify-between">
            <span class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Status Kesehatan Live</span>
            <div class="mt-2">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-black bg-emerald-100 text-emerald-800">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <?= (int)$health['healthy'] ?> Sehat
                    </span>
                    <?php if ($health['error'] > 0): ?>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-black bg-rose-100 text-rose-800">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                            <?= (int)$health['error'] ?> Terkendala
                        </span>
                    <?php endif; ?>
                </div>
                <p class="text-[10px] sm:text-xs text-slate-500 mt-1">Uji koneksi otomatis berkala</p>
            </div>
        </div>

        <!-- Card 4: Auto-Failover Resilience -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl sm:rounded-3xl border border-rose-100 shadow-sm flex flex-col justify-between">
            <span class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">AI Auto-Failover</span>
            <div class="mt-2">
                <span class="text-xl sm:text-2xl font-black text-emerald-600">
                    100% Aktif
                </span>
                <p class="text-[10px] sm:text-xs text-slate-500 mt-0.5">Zero-Downtime Resilience</p>
            </div>
        </div>
    </div>

    <!-- Alert Box for Live Test All Result (Hidden Default) -->
    <div id="bulkTestAlertBox" class="hidden rounded-2xl p-4 transition-all"></div>

    <!-- SECTION A: FLEET MANAGEMENT & AUTO-FAILOVER PRIORITY -->
    <div class="bg-white rounded-3xl shadow-sm border border-rose-100 overflow-hidden">
        <div class="p-6 sm:p-8 border-b border-rose-100 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-50/50">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full bg-rose-100 text-uhn-900 text-[10px] font-black uppercase tracking-wider">
                    Konfigurasi &amp; Prioritas Eksekusi
                </div>
                <h2 class="text-lg font-black text-slate-900 mt-1 flex items-center gap-2">
                    <span>Daftar AI Engine &amp; Urutan Auto-Failover</span>
                </h2>
                <p class="text-xs text-slate-500 mt-0.5 max-w-3xl leading-relaxed">
                    Sistem akan memanggil <strong>Engine Prioritas 1</strong> terlebih dahulu. Jika terjadi kegagalan (kuota habis, timeout, atau jaringan), sistem akan melakukan percobaan ulang otomatis sesuai batas retry, lalu langsung mengalihkan beban kerja ke engine berikutnya secara mulus.
                </p>
            </div>
            <div class="flex items-center gap-2 text-xs text-slate-500">
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Sehat
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-rose-500 ml-2"></span> Terkendala
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-slate-400 ml-2"></span> Nonaktif
            </div>
        </div>

        <div class="divide-y divide-rose-50">
            <?php foreach ($engines as $idx => $eng): ?>
                <?php 
                    $engId = (int)$eng['id'];
                    $priority = (int)$eng['priority'];
                    $isActive = (bool)$eng['is_active'];
                    $provider = strtoupper(trim($eng['provider_type']));
                    $lastStatus = strtoupper(trim($eng['last_status'] ?? 'UNTESTED'));
                    $isLocal = ($provider === 'LOCAL_SYNTHESIZER' || $engId === 4);

                    // Badge Provider
                    $providerBadgeClass = match($provider) {
                        'GEMINI' => 'bg-blue-50 text-blue-700 border-blue-200',
                        'OPENAI_COMPATIBLE' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        'LOCAL_SYNTHESIZER' => 'bg-purple-50 text-purple-700 border-purple-200',
                        default => 'bg-slate-50 text-slate-700 border-slate-200'
                    };

                    // Badge Priority
                    $priorityLabel = match(true) {
                        $priority === 1 => '👑 Prioritas 1 (Utama)',
                        $priority === 2 => '🛡️ Prioritas 2 (Cadangan 1)',
                        $priority === 3 => '🛡️ Prioritas 3 (Cadangan 2)',
                        $priority >= 90 => '⚡ Cadangan Final (Offline)',
                        default => "🛡️ Prioritas {$priority} (Cadangan)"
                    };

                    $priorityBadgeClass = match(true) {
                        $priority === 1 => 'bg-amber-100 text-amber-900 border-amber-300 font-black',
                        $priority >= 90 => 'bg-purple-100 text-purple-900 border-purple-200 font-bold',
                        default => 'bg-slate-100 text-slate-800 border-slate-200 font-bold'
                    };

                    // Live Health Badge
                    $healthBadgeClass = match($lastStatus) {
                        'HEALTHY' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                        'ERROR' => 'bg-rose-100 text-rose-800 border-rose-300',
                        'DISABLED' => 'bg-slate-100 text-slate-500 border-slate-200',
                        default => 'bg-amber-50 text-amber-800 border-amber-200'
                    };

                    $healthDot = match($lastStatus) {
                        'HEALTHY' => 'bg-emerald-500',
                        'ERROR' => 'bg-rose-500',
                        'DISABLED' => 'bg-slate-400',
                        default => 'bg-amber-400'
                    };

                    $healthText = match($lastStatus) {
                        'HEALTHY' => '🟢 Sehat (' . (int)$eng['last_latency_ms'] . ' ms)',
                        'ERROR' => '🔴 Gagal Terkoneksi',
                        'DISABLED' => '⚪ Nonaktif / Standby',
                        default => '🟡 Belum Diuji'
                    };
                ?>
                <div class="p-6 transition-colors hover:bg-slate-50/70 flex flex-col lg:flex-row lg:items-center justify-between gap-6" id="engine-card-<?= $engId ?>">
                    
                    <!-- Left: Identity & Specs -->
                    <div class="space-y-2.5 max-w-2xl">
                        <div class="flex flex-wrap items-center gap-2">
                            <!-- Priority Badge -->
                            <span class="inline-flex items-center px-3 py-1 rounded-xl text-xs border <?= $priorityBadgeClass ?>">
                                <?= $priorityLabel ?>
                            </span>

                            <!-- Provider Badge -->
                            <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-bold border <?= $providerBadgeClass ?>">
                                <?= htmlspecialchars($provider) ?>
                            </span>

                            <!-- Status Operasional Toggle Badge -->
                            <?php if ($isActive): ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-500 border border-slate-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Dinonaktifkan
                                </span>
                            <?php endif; ?>

                            <!-- Live Health Badge -->
                            <span id="health-badge-<?= $engId ?>" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold border <?= $healthBadgeClass ?>">
                                <span class="w-1.5 h-1.5 rounded-full <?= $healthDot ?>"></span>
                                <span id="health-text-<?= $engId ?>"><?= $healthText ?></span>
                            </span>
                        </div>

                        <!-- Name & Model -->
                        <div>
                            <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                                <span><?= htmlspecialchars($eng['name']) ?></span>
                                <span class="text-xs font-mono font-medium text-slate-500 bg-slate-100 px-2 py-0.5 rounded-lg border border-slate-200">
                                    <?= htmlspecialchars($eng['model_name']) ?>
                                </span>
                            </h3>
                            <?php if (!empty($eng['endpoint_url'])): ?>
                                <p class="text-[11px] text-slate-500 font-mono mt-0.5 truncate max-w-xl">
                                    Endpoint: <?= htmlspecialchars($eng['endpoint_url']) ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <!-- Technical Meta Chips -->
                        <div class="flex flex-wrap items-center gap-2 text-[11px] text-slate-600">
                            <span class="bg-rose-50/60 border border-rose-100 px-2.5 py-0.5 rounded-lg">
                                <strong>Percobaan Ulang:</strong> <?= (int)$eng['max_retries'] ?>x Retry
                            </span>
                            <span class="bg-rose-50/60 border border-rose-100 px-2.5 py-0.5 rounded-lg">
                                <strong>Batas Waktu:</strong> <?= (int)$eng['timeout_seconds'] ?> Detik
                            </span>
                            <span class="bg-rose-50/60 border border-rose-100 px-2.5 py-0.5 rounded-lg">
                                <strong>Suhu:</strong> <?= htmlspecialchars((string)$eng['temperature']) ?>
                            </span>
                            <span class="bg-rose-50/60 border border-rose-100 px-2.5 py-0.5 rounded-lg">
                                <strong>Maks Token:</strong> <?= (int)$eng['max_tokens'] ?>
                            </span>
                            <?php if (!empty($eng['last_checked_at'])): ?>
                                <span class="text-slate-400">
                                    &bull; Terakhir diuji: <?= htmlspecialchars($eng['last_checked_at']) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Last Error Message if any -->
                        <?php if ($lastStatus === 'ERROR' && !empty($eng['last_error_message'])): ?>
                            <div class="p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 text-[11px] font-mono leading-relaxed">
                                <strong class="font-sans font-bold">Rincian Galat Terakhir:</strong> <?= htmlspecialchars($eng['last_error_message']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Right: Action Buttons -->
                    <div class="flex flex-wrap items-center gap-2 flex-shrink-0">
                        <!-- Uji Koneksi Live Button -->
                        <button type="button" onclick="testSingleEngineAjax(<?= $engId ?>)" id="btn-test-<?= $engId ?>"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold shadow-sm active:scale-95 transition-all">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            <span>Uji Koneksi</span>
                        </button>

                        <!-- Priority Move Buttons -->
                        <div class="inline-flex rounded-xl shadow-sm border border-slate-200 bg-white overflow-hidden">
                            <a href="/index.php?action=admin_ai_engine_priority&id=<?= $engId ?>&dir=UP" 
                               class="p-2 text-slate-600 hover:bg-slate-100 border-r border-slate-200 <?= ($idx === 0) ? 'opacity-40 pointer-events-none' : '' ?>" 
                               title="Pindahkan Urutan Naik (Prioritas Lebih Utama)">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                            </a>
                            <a href="/index.php?action=admin_ai_engine_priority&id=<?= $engId ?>&dir=DOWN" 
                               class="p-2 text-slate-600 hover:bg-slate-100 <?= ($idx === count($engines) - 1) ? 'opacity-40 pointer-events-none' : '' ?>" 
                               title="Pindahkan Urutan Turun (Prioritas Cadangan)">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </a>
                        </div>

                        <!-- Toggle Active Switch -->
                        <a href="/index.php?action=admin_ai_engine_toggle&id=<?= $engId ?>" 
                           class="px-3 py-1.5 rounded-xl text-xs font-bold border transition-colors <?= $isActive ? 'bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-300' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border-emerald-300' ?>"
                           title="<?= $isActive ? 'Nonaktifkan Engine Ini' : 'Aktifkan Engine Ini' ?>">
                            <?= $isActive ? 'Nonaktifkan' : 'Aktifkan' ?>
                        </a>

                        <!-- Edit Button -->
                        <button type="button" onclick='openEditEngineModal(<?= json_encode($eng) ?>)'
                                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-xl text-xs font-bold border border-slate-200 transition-colors">
                            Edit
                        </button>

                        <!-- Delete Button (Protected for local synthesizer) -->
                        <?php if (!$isLocal): ?>
                            <a href="/index.php?action=admin_ai_engine_delete&id=<?= $engId ?>" 
                               onclick="return confirm('Apakah Anda yakin ingin menghapus AI Engine ini?');"
                               class="p-2 text-rose-500 hover:bg-rose-50 rounded-xl transition-colors" title="Hapus AI Engine">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </a>
                        <?php else: ?>
                            <span class="p-2 text-slate-300 cursor-not-allowed" title="Engine lokal dilindungi oleh sistem">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            </span>
                        <?php endif; ?>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- SECTION B: AUTO-FAILOVER & RESILIENCE AUDIT LOG -->
    <div class="bg-white rounded-3xl shadow-sm border border-rose-100 p-6 sm:p-8 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-black uppercase tracking-wider">
                    Ketahanan Sistem &amp; Zero Downtime
                </div>
                <h3 class="text-base font-extrabold text-slate-900 uppercase tracking-wider text-uhn-900 mt-1">
                    Riwayat Insiden &amp; Penyelamatan AI Auto-Failover
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    Mencatat setiap kali request dialihkan dari engine utama yang terkendala menuju engine cadangan secara otomatis tanpa gangguan bagi pengguna.
                </p>
            </div>
            <?php if (!empty($failoverLogs)): ?>
                <a href="/index.php?action=admin_ai_failover_clear" 
                   onclick="return confirm('Bersihkan seluruh riwayat log auto-failover?');"
                   class="px-3.5 py-1.5 bg-slate-100 hover:bg-rose-50 hover:text-rose-700 text-slate-600 rounded-xl text-xs font-bold transition-colors self-start sm:self-auto">
                    Bersihkan Riwayat Log
                </a>
            <?php endif; ?>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border border-slate-200 rounded-2xl overflow-hidden">
                <thead class="bg-slate-100 text-slate-700 font-bold uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="p-3">Waktu</th>
                        <th class="p-3">Tipe Panggilan</th>
                        <th class="p-3">Engine Utama Terkendala</th>
                        <th class="p-3">Penyelamatan Auto-Failover</th>
                        <th class="p-3">Status Pengguna</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($failoverLogs)): ?>
                        <tr>
                            <td colspan="5" class="p-6 text-center text-slate-400 italic">
                                Belum ada insiden perpindahan auto-failover. Seluruh engine beroperasi normal dan sehat.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($failoverLogs as $flog): ?>
                            <tr class="hover:bg-rose-50/30 transition-colors">
                                <td class="p-3 text-slate-500 font-mono text-[11px] whitespace-nowrap">
                                    <?= htmlspecialchars($flog['created_at']) ?>
                                </td>
                                <td class="p-3 font-semibold text-slate-900">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                                        <?= htmlspecialchars($flog['request_type']) ?>
                                    </span>
                                </td>
                                <td class="p-3 text-rose-800">
                                    <div class="font-bold flex items-center gap-1.5">
                                        <span>⚠️ <?= htmlspecialchars($flog['failed_engine_name'] ?? 'Engine Utama') ?></span>
                                        <span class="text-[10px] bg-rose-100 px-1.5 py-0.2 rounded font-mono font-normal"><?= (int)$flog['retry_count'] ?>x Retry</span>
                                    </div>
                                    <p class="text-[10px] text-rose-600 font-mono truncate max-w-sm mt-0.5">
                                        <?= htmlspecialchars($flog['error_message'] ?? 'Timeout / Quota Exceeded') ?>
                                    </p>
                                </td>
                                <td class="p-3 text-emerald-800">
                                    <div class="font-bold flex items-center gap-1">
                                        <span>🛡️ Dialihkan ke:</span>
                                        <span class="text-slate-900"><?= htmlspecialchars($flog['rescued_engine_name'] ?? 'UHN Neural Synthesizer') ?></span>
                                    </div>
                                </td>
                                <td class="p-3">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-900">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Terselamatkan (Zero Error)
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- SECTION C: GLOBAL PARAMETERS & INSTITUTIONAL GUIDELINES -->
    <div class="bg-white rounded-3xl shadow-sm border border-rose-100 p-6 sm:p-8">
        <form action="/index.php?action=ai_config_save" method="POST" class="space-y-6">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full bg-rose-100 text-uhn-900 text-[10px] font-black uppercase tracking-wider">
                    Pedoman Sintesis &amp; Borang WURI
                </div>
                <h3 class="text-base font-extrabold text-slate-900 uppercase tracking-wider text-uhn-900 mt-1">
                    Parameter Global &amp; Kebijakan Borang WURI
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    Sesuaikan gaya default, derajat kreativitas, dan instruksi institusional yang disisipkan ke semua AI Engine saat merumuskan narasi 3 kalimat bahasa awam.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                <!-- Gaya Bahasa Bawaan -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Gaya Bahasa Bawaan (Default Tone)
                    </label>
                    <select name="ai_default_tone" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2.5 text-xs sm:text-sm font-bold focus:ring-rose-400 focus:border-rose-400">
                        <option value="STANDAR" <?= ($defaultTone === 'STANDAR') ? 'selected' : '' ?>>🏛️ Standar WURI (Institusional Resmi)</option>
                        <option value="MEDIA" <?= ($defaultTone === 'MEDIA') ? 'selected' : '' ?>>📰 Rilis Berita (Media &amp; Publik)</option>
                        <option value="INSPIRATIF" <?= ($defaultTone === 'INSPIRATIF') ? 'selected' : '' ?>>💡 Inspiratif (Human Interest)</option>
                    </select>
                    <span class="text-[11px] text-slate-400 mt-1 block">Dapat diubah secara dinamis oleh user pada form entri.</span>
                </div>

                <!-- Temperatur / Kreativitas -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Temperatur / Derajat Kreativitas
                    </label>
                    <select name="ai_temperature" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2.5 text-xs sm:text-sm font-bold focus:ring-rose-400 focus:border-rose-400">
                        <option value="0.2" <?= ($temperature === '0.2') ? 'selected' : '' ?>>0.2 (Presisi Tinggi &bull; Ketat Standar Borang)</option>
                        <option value="0.4" <?= ($temperature === '0.4') ? 'selected' : '' ?>>0.4 (Seimbang &bull; Sangat Direkomendasikan)</option>
                        <option value="0.7" <?= ($temperature === '0.7') ? 'selected' : '' ?>>0.7 (Kreatif &bull; Kosakata Jurnalistik Bervariasi)</option>
                    </select>
                    <span class="text-[11px] text-slate-400 mt-1 block">Temperatur lebih rendah menghasilkan teks lebih konsisten.</span>
                </div>

                <!-- Batas Token Output -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Maksimal Token Output
                    </label>
                    <input type="number" name="ai_max_tokens" value="<?= htmlspecialchars((string)$maxTokens) ?>" min="150" max="1000" step="50"
                           class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2.5 text-xs sm:text-sm font-mono focus:ring-rose-400 focus:border-rose-400">
                    <span class="text-[11px] text-slate-400 mt-1 block">Standar 3 kalimat optimal pada 300 - 400 token.</span>
                </div>
            </div>

            <!-- Panduan Khusus Institusi -->
            <div class="space-y-2">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                    Instruksi Khusus Institusi (*Custom Prompt Guidelines*)
                </label>
                <textarea name="ai_system_instructions" rows="3" 
                          placeholder="Contoh: Selalu gunakan ejaan baku Bahasa Indonesia. Cantumkan singkatan UHN untuk Universitas Harkat Negeri. Tekankan kontribusi inovasi terhadap Tujuan Pembangunan Berkelanjutan (SDGs)..."
                          class="w-full bg-slate-50 border border-slate-300 rounded-2xl p-3.5 text-xs sm:text-sm font-medium focus:ring-2 focus:ring-rose-400 focus:border-rose-400 leading-relaxed"><?= htmlspecialchars($customInstructions) ?></textarea>
            </div>

            <!-- Toggle Sakelar Fallback & Kepatuhan 3 Kalimat -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <div class="p-4 rounded-2xl border border-rose-100 bg-rose-50/30 flex items-start gap-3">
                    <input type="checkbox" name="ai_enable_fallback" id="chkFallback" value="1" <?= $fallbackEnabled ? 'checked' : '' ?>
                           class="mt-1 h-4 w-4 text-uhn-800 rounded border-slate-300 focus:ring-rose-400">
                    <div>
                        <label for="chkFallback" class="text-xs font-bold text-slate-900 cursor-pointer block">
                            Izinkan Auto-Failover ke UHN Local Synthesizer (*Zero Downtime*)
                        </label>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            Jika seluruh API Cloud habis kuota atau timeout, sistem otomatis beralih ke Neural Synthesizer offline tanpa kegagalan pada PIC unit.
                        </p>
                    </div>
                </div>

                <div class="p-4 rounded-2xl border border-rose-100 bg-rose-50/30 flex items-start gap-3">
                    <input type="checkbox" name="ai_auto_format_3_sentences" id="chkThree" value="1" checked disabled
                           class="mt-1 h-4 w-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-400">
                    <div>
                        <label for="chkThree" class="text-xs font-bold text-slate-900 block">
                            Penegakan Ketat Tepat 3 Kalimat Bahasa Awam (Wajib Sistem)
                        </label>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            Sistem secara otomatis memotong atau melengkapi narasi agar selalu berakhiran titik dan berjumlah tepat 3 kalimat guna memenuhi validasi borang akreditasi WURI.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Tombol Simpan Parameter -->
            <div class="flex items-center justify-between pt-2">
                <a href="/index.php?page=dashboard" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors">
                    Kembali ke Dashboard
                </a>
                <button type="submit" 
                        class="px-7 py-3 bg-gradient-to-r from-uhn-900 via-rose-800 to-uhn-800 hover:from-uhn-950 hover:to-rose-900 text-white rounded-xl text-xs font-black shadow-lg shadow-rose-950/20 active:scale-95 transition-all flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-300" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
                    <span>Simpan Parameter Sintesis Borang</span>
                </button>
            </div>
        </form>
    </div>

    <!-- SECTION D: LOG AUDIT TRAIL TERKINI -->
    <div class="bg-white rounded-3xl shadow-sm border border-rose-100 p-6 sm:p-8 space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 text-[10px] font-black uppercase tracking-wider">
                    Transparansi &amp; Tata Kelola
                </div>
                <h3 class="text-base font-extrabold text-slate-900 uppercase tracking-wider text-uhn-900 mt-1">
                    Log Audit &amp; Panggilan AI Terkini
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    Riwayat 10 transaksi generasi narasi dan rilis pers AI yang tercatat dalam audit trail institusi.
                </p>
            </div>
            <a href="/index.php?page=audit" class="text-xs font-bold text-uhn-800 hover:underline">
                Lihat Seluruh Log Audit &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border border-slate-200 rounded-2xl overflow-hidden">
                <thead class="bg-slate-100 text-slate-700 font-bold uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="p-3">Waktu</th>
                        <th class="p-3">Pengguna</th>
                        <th class="p-3">Aksi</th>
                        <th class="p-3">Rincian Transaksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($stats['recent_logs'])): ?>
                        <tr>
                            <td colspan="4" class="p-6 text-center text-slate-400 italic">
                                Belum ada aktivitas panggilan AI yang tercatat dalam log sistem.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($stats['recent_logs'] as $log): ?>
                            <tr class="hover:bg-rose-50/30 transition-colors">
                                <td class="p-3 text-slate-500 font-mono text-[11px] whitespace-nowrap">
                                    <?= htmlspecialchars($log['created_at']) ?>
                                </td>
                                <td class="p-3 font-semibold text-slate-900">
                                    <?= htmlspecialchars($log['full_name'] ?? 'System') ?>
                                    <span class="text-slate-400 font-normal">(@<?= htmlspecialchars($log['username'] ?? '-') ?>)</span>
                                </td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-100 text-uhn-900 border border-rose-200">
                                        <?= htmlspecialchars($log['action']) ?>
                                    </span>
                                </td>
                                <td class="p-3 text-slate-600 font-medium max-w-md truncate">
                                    <?= htmlspecialchars($log['details']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- ========================================================================= -->
<!-- MODAL: TAMBAH AI ENGINE BARU -->
<!-- ========================================================================= -->
<div id="modalAddEngine" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full p-6 sm:p-8 space-y-6 border border-rose-100 my-8">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-rose-100 text-uhn-900 text-[10px] font-black uppercase tracking-wider">
                    Registrasi Penyedia Baru
                </div>
                <h3 class="text-lg font-black text-slate-900 mt-1">Tambah AI Engine Baru</h3>
                <p class="text-xs text-slate-500">Konfigurasikan model Google Gemini, DeepSeek, OpenAI, atau Groq.</p>
            </div>
            <button type="button" onclick="closeAddEngineModal()" class="text-slate-400 hover:text-slate-600 p-2 text-xl font-bold">&times;</button>
        </div>

        <!-- Quick Presets -->
        <div>
            <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Preset Cepat (Pilih untuk Mengisi Otomatis):</label>
            <div class="flex flex-wrap gap-2">
                <button type="button" onclick="applyAddPreset('GEMINI_15_FLASH')" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 transition-colors">
                    + Gemini 1.5 Flash
                </button>
                <button type="button" onclick="applyAddPreset('GEMINI_20_FLASH')" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 transition-colors">
                    + Gemini 2.0 Flash
                </button>
                <button type="button" onclick="applyAddPreset('DEEPSEEK_V3')" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 transition-colors">
                    + DeepSeek V3
                </button>
                <button type="button" onclick="applyAddPreset('GROQ_LLAMA')" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 transition-colors">
                    + Groq Llama 3.3
                </button>
            </div>
        </div>

        <form action="/index.php?action=admin_ai_engine_add" method="POST" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Mesin (Label Tampilan)</label>
                    <input type="text" name="name" id="addName" required placeholder="Contoh: DeepSeek V3 Production" 
                           class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm font-semibold focus:ring-rose-400 focus:border-rose-400">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Tipe Protokol Provider</label>
                    <select name="provider_type" id="addProviderType" onchange="toggleAddEndpointVisibility()" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm font-bold focus:ring-rose-400 focus:border-rose-400">
                        <option value="GEMINI">Google Gemini API</option>
                        <option value="OPENAI_COMPATIBLE">OpenAI-Compatible (DeepSeek, Groq, Mistral, Ollama)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama / Identitas Model</label>
                    <input type="text" name="model_name" id="addModelName" required placeholder="gemini-1.5-flash / deepseek-chat" 
                           class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm font-mono focus:ring-rose-400 focus:border-rose-400">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Prioritas Eksekusi (1 = Utama)</label>
                    <input type="number" name="priority" id="addPriority" value="2" min="1" max="98" 
                           class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm font-bold focus:ring-rose-400 focus:border-rose-400">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">API Key / Kredensial Akses</label>
                <input type="password" name="api_key" id="addApiKey" placeholder="AIzaSy... / sk-..." 
                       class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm font-mono focus:ring-rose-400 focus:border-rose-400">
                <span class="text-[11px] text-slate-400 mt-1 block">Biarkan kosong jika ingin menggunakan kredensial global.</span>
            </div>

            <div id="addEndpointGroup">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Custom Endpoint URL</label>
                <input type="url" name="endpoint_url" id="addEndpointUrl" placeholder="https://api.deepseek.com/v1/chat/completions" 
                       class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm font-mono focus:ring-rose-400 focus:border-rose-400">
                <span class="text-[11px] text-slate-400 mt-1 block">Diperlukan untuk OpenAI-compatible (DeepSeek, Groq, Ollama).</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2">
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Maks Retry</label>
                    <select name="max_retries" id="addMaxRetries" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold">
                        <option value="1">1x Percobaan</option>
                        <option value="2" selected>2x Percobaan</option>
                        <option value="3">3x Percobaan</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Timeout (Detik)</label>
                    <input type="number" name="timeout_seconds" id="addTimeout" value="6" min="2" max="30" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-mono">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Temperatur</label>
                    <select name="temperature" id="addTemperature" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold">
                        <option value="0.2">0.2</option>
                        <option value="0.4" selected>0.4</option>
                        <option value="0.7">0.7</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Maks Token</label>
                    <input type="number" name="max_tokens" id="addMaxTokens" value="400" min="150" max="1000" step="50" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-mono">
                </div>
            </div>

            <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200 flex items-center gap-3">
                <input type="checkbox" name="is_active" id="addIsActive" value="1" checked class="h-4 w-4 text-uhn-800 rounded border-slate-300 focus:ring-rose-400">
                <label for="addIsActive" class="text-xs font-bold text-slate-800 cursor-pointer">
                    Aktifkan Engine Ini Sekarang (Siap Melayani Permintaan)
                </label>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeAddEngineModal()" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-6 py-2.5 bg-uhn-900 hover:bg-uhn-950 text-white rounded-xl text-xs font-black shadow-md transition-all">
                    Simpan AI Engine
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: EDIT AI ENGINE -->
<!-- ========================================================================= -->
<div id="modalEditEngine" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full p-6 sm:p-8 space-y-6 border border-rose-100 my-8">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-900 text-[10px] font-black uppercase tracking-wider">
                    Perbarui Konfigurasi
                </div>
                <h3 class="text-lg font-black text-slate-900 mt-1">Edit AI Engine</h3>
                <p class="text-xs text-slate-500">Sesuaikan kredensial, model, retry, timeout, atau urutan prioritas.</p>
            </div>
            <button type="button" onclick="closeEditEngineModal()" class="text-slate-400 hover:text-slate-600 p-2 text-xl font-bold">&times;</button>
        </div>

        <form action="/index.php?action=admin_ai_engine_update" method="POST" class="space-y-4">
            <input type="hidden" name="id" id="editId">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Mesin (Label Tampilan)</label>
                    <input type="text" name="name" id="editName" required 
                           class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm font-semibold focus:ring-rose-400 focus:border-rose-400">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Tipe Protokol Provider</label>
                    <select name="provider_type" id="editProviderType" onchange="toggleEditEndpointVisibility()" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm font-bold focus:ring-rose-400 focus:border-rose-400">
                        <option value="GEMINI">Google Gemini API</option>
                        <option value="OPENAI_COMPATIBLE">OpenAI-Compatible (DeepSeek, Groq, Mistral, Ollama)</option>
                        <option value="LOCAL_SYNTHESIZER">UHN Local Synthesizer (Offline Zero-Downtime)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama / Identitas Model</label>
                    <input type="text" name="model_name" id="editModelName" required 
                           class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm font-mono focus:ring-rose-400 focus:border-rose-400">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Prioritas Eksekusi (1 = Utama)</label>
                    <input type="number" name="priority" id="editPriority" min="1" max="99" 
                           class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm font-bold focus:ring-rose-400 focus:border-rose-400">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">API Key / Kredensial Akses</label>
                <input type="password" name="api_key" id="editApiKey" placeholder="(Biarkan kosong jika tidak ingin mengubah API key)" 
                       class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm font-mono focus:ring-rose-400 focus:border-rose-400">
                <span class="text-[11px] text-slate-400 mt-1 block">Kredensial disimpan secara aman dan dienkripsi di basis data server.</span>
            </div>

            <div id="editEndpointGroup">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Custom Endpoint URL</label>
                <input type="url" name="endpoint_url" id="editEndpointUrl" placeholder="https://api.deepseek.com/v1/chat/completions" 
                       class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm font-mono focus:ring-rose-400 focus:border-rose-400">
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2">
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Maks Retry</label>
                    <select name="max_retries" id="editMaxRetries" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold">
                        <option value="1">1x Percobaan</option>
                        <option value="2">2x Percobaan</option>
                        <option value="3">3x Percobaan</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Timeout (Detik)</label>
                    <input type="number" name="timeout_seconds" id="editTimeout" min="2" max="30" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-mono">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Temperatur</label>
                    <select name="temperature" id="editTemperature" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold">
                        <option value="0.2">0.2</option>
                        <option value="0.4">0.4</option>
                        <option value="0.7">0.7</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Maks Token</label>
                    <input type="number" name="max_tokens" id="editMaxTokens" min="150" max="1000" step="50" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-mono">
                </div>
            </div>

            <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200 flex items-center gap-3">
                <input type="checkbox" name="is_active" id="editIsActive" value="1" class="h-4 w-4 text-uhn-800 rounded border-slate-300 focus:ring-rose-400">
                <label for="editIsActive" class="text-xs font-bold text-slate-800 cursor-pointer">
                    Aktifkan Engine Ini (Siap Melayani Permintaan)
                </label>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeEditEngineModal()" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-6 py-2.5 bg-uhn-900 hover:bg-uhn-950 text-white rounded-xl text-xs font-black shadow-md transition-all">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// =========================================================================
// MODAL CONTROLLERS & PRESETS
// =========================================================================

function openAddEngineModal() {
    const modal = document.getElementById('modalAddEngine');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
}

function closeAddEngineModal() {
    const modal = document.getElementById('modalAddEngine');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}

function toggleAddEndpointVisibility() {
    const type = document.getElementById('addProviderType').value;
    const group = document.getElementById('addEndpointGroup');
    if (type === 'OPENAI_COMPATIBLE') {
        group.classList.remove('hidden');
    } else {
        group.classList.add('hidden');
    }
}

function applyAddPreset(preset) {
    const nameEl = document.getElementById('addName');
    const provEl = document.getElementById('addProviderType');
    const modelEl = document.getElementById('addModelName');
    const endpointEl = document.getElementById('addEndpointUrl');

    if (preset === 'GEMINI_15_FLASH') {
        nameEl.value = 'Google Gemini 1.5 Flash';
        provEl.value = 'GEMINI';
        modelEl.value = 'gemini-1.5-flash';
        endpointEl.value = '';
    } else if (preset === 'GEMINI_20_FLASH') {
        nameEl.value = 'Google Gemini 2.0 Flash';
        provEl.value = 'GEMINI';
        modelEl.value = 'gemini-2.0-flash';
        endpointEl.value = '';
    } else if (preset === 'DEEPSEEK_V3') {
        nameEl.value = 'DeepSeek V3';
        provEl.value = 'OPENAI_COMPATIBLE';
        modelEl.value = 'deepseek-chat';
        endpointEl.value = 'https://api.deepseek.com/v1/chat/completions';
    } else if (preset === 'GROQ_LLAMA') {
        nameEl.value = 'Groq Llama 3.3 70B';
        provEl.value = 'OPENAI_COMPATIBLE';
        modelEl.value = 'llama-3.3-70b-versatile';
        endpointEl.value = 'https://api.groq.com/openai/v1/chat/completions';
    }
    toggleAddEndpointVisibility();
}

function openEditEngineModal(eng) {
    document.getElementById('editId').value = eng.id || '';
    document.getElementById('editName').value = eng.name || '';
    document.getElementById('editProviderType').value = eng.provider_type || 'GEMINI';
    document.getElementById('editModelName').value = eng.model_name || '';
    document.getElementById('editPriority').value = eng.priority || 1;
    document.getElementById('editApiKey').value = '';
    document.getElementById('editEndpointUrl').value = eng.endpoint_url || '';
    document.getElementById('editMaxRetries').value = eng.max_retries || 2;
    document.getElementById('editTimeout').value = eng.timeout_seconds || 6;
    document.getElementById('editTemperature').value = eng.temperature || 0.4;
    document.getElementById('editMaxTokens').value = eng.max_tokens || 400;
    document.getElementById('editIsActive').checked = (parseInt(eng.is_active) === 1);

    toggleEditEndpointVisibility();

    const modal = document.getElementById('modalEditEngine');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
}

function closeEditEngineModal() {
    const modal = document.getElementById('modalEditEngine');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}

function toggleEditEndpointVisibility() {
    const type = document.getElementById('editProviderType').value;
    const group = document.getElementById('editEndpointGroup');
    if (type === 'OPENAI_COMPATIBLE') {
        group.classList.remove('hidden');
    } else {
        group.classList.add('hidden');
    }
}

// Initial toggle
toggleAddEndpointVisibility();

// =========================================================================
// LIVE HEALTH & CONNECTION TESTING
// =========================================================================

function testSingleEngineAjax(engineId) {
    const btn = document.getElementById('btn-test-' + engineId);
    const badge = document.getElementById('health-badge-' + engineId);
    const text = document.getElementById('health-text-' + engineId);

    if (btn) {
        btn.disabled = true;
        btn.classList.add('opacity-70', 'cursor-not-allowed');
        btn.innerHTML = `<span class="animate-spin inline-block w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full"></span> <span>Menguji...</span>`;
    }

    if (badge && text) {
        badge.className = 'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold border bg-amber-50 text-amber-800 border-amber-200 animate-pulse';
        text.innerText = 'Menguji Sambungan...';
    }

    fetch('/index.php?action=admin_ai_engine_test&id=' + engineId)
        .then(res => res.json())
        .then(data => {
            if (btn) {
                btn.disabled = false;
                btn.classList.remove('opacity-70', 'cursor-not-allowed');
                btn.innerHTML = `<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg> <span>Uji Koneksi</span>`;
            }

            if (data && data.success) {
                if (badge && text) {
                    badge.className = 'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold border bg-emerald-100 text-emerald-800 border-emerald-300';
                    text.innerText = `🟢 Sehat (${data.latency_ms} ms)`;
                }
                alert(`✅ Koneksi Berhasil!\n\nEngine: ${data.model}\nLatensi: ${data.latency_ms} ms\nRespon: ${data.sample_response || 'OK'}`);
            } else {
                if (badge && text) {
                    badge.className = 'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold border bg-rose-100 text-rose-800 border-rose-300';
                    text.innerText = '🔴 Gagal Terkoneksi';
                }
                alert(`⚠️ Uji Koneksi Gagal!\n\nEngine: ${data.model || '-'}\nError: ${data.error || data.message || 'Koneksi gagal atau model tidak memberikan respon valid.'}`);
            }
        })
        .catch(err => {
            if (btn) {
                btn.disabled = false;
                btn.classList.remove('opacity-70', 'cursor-not-allowed');
                btn.innerHTML = `<span>Uji Koneksi</span>`;
            }
            alert('❌ Terjadi kesalahan jaringan saat menghubungi server lokal.');
            console.error(err);
        });
}

function testAllEnginesAjax() {
    const btn = document.getElementById('btnTestAll');
    const icon = document.getElementById('testAllIcon');
    const text = document.getElementById('testAllText');
    const alertBox = document.getElementById('bulkTestAlertBox');

    btn.disabled = true;
    btn.classList.add('opacity-80', 'cursor-not-allowed');
    text.innerText = 'Menguji Seluruh Engine AI...';

    alertBox.classList.remove('hidden');
    alertBox.className = 'rounded-2xl p-4 bg-slate-100 border border-slate-300 text-xs text-slate-700 flex items-center gap-3 animate-pulse';
    alertBox.innerHTML = '<div class="w-4 h-4 border-2 border-slate-400 border-t-uhn-800 rounded-full animate-spin"></div><span>Menghubungi seluruh penyedia AI aktif untuk mengukur latensi dan memeriksa ketersediaan auto-failover...</span>';

    fetch('/index.php?action=admin_ai_engine_test_all')
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.classList.remove('opacity-80', 'cursor-not-allowed');
            text.innerText = '⚡ Uji Semua Engine (Test All)';

            if (data && data.success && Array.isArray(data.results)) {
                let successCount = 0;
                let failCount = 0;

                data.results.forEach(res => {
                    const engId = res.id;
                    const badge = document.getElementById('health-badge-' + engId);
                    const btext = document.getElementById('health-text-' + engId);

                    if (res.success) {
                        successCount++;
                        if (badge && btext) {
                            badge.className = 'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold border bg-emerald-100 text-emerald-800 border-emerald-300';
                            btext.innerText = `🟢 Sehat (${res.latency_ms} ms)`;
                        }
                    } else {
                        failCount++;
                        if (badge && btext) {
                            badge.className = 'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold border bg-rose-100 text-rose-800 border-rose-300';
                            btext.innerText = '🔴 Gagal Terkoneksi';
                        }
                    }
                });

                alertBox.className = 'rounded-2xl p-4 bg-emerald-50 border border-emerald-300 text-xs text-emerald-950 space-y-1';
                alertBox.innerHTML = `
                    <div class="font-extrabold flex items-center gap-2 text-emerald-800 text-sm">
                        <span>✅ Selesai Menguji Semua Engine AI</span>
                    </div>
                    <p class="text-xs text-emerald-900 leading-relaxed">
                        Total diuji: <strong>${data.results.length} engine</strong> &bull; 
                        <span class="text-emerald-700 font-bold">${successCount} Sehat</span> &bull; 
                        <span class="${failCount > 0 ? 'text-rose-700 font-bold' : 'text-slate-500'}">${failCount} Terkendala</span>.
                        Sistem AI Auto-Failover siap mengalihkan beban kerja jika terjadi kendala pada engine utama.
                    </p>
                `;
            } else {
                alertBox.className = 'rounded-2xl p-4 bg-rose-50 border border-rose-300 text-xs text-rose-900';
                alertBox.innerHTML = '<strong>Pengujian Gagal:</strong> Tidak dapat memproses hasil pengujian engine.';
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.classList.remove('opacity-80', 'cursor-not-allowed');
            text.innerText = '⚡ Uji Semua Engine (Test All)';
            alertBox.className = 'rounded-2xl p-4 bg-rose-50 border border-rose-300 text-xs text-rose-900';
            alertBox.innerHTML = '<strong>Kesalahan Jaringan:</strong> Gagal terhubung ke endpoint pengujian internal.';
            console.error(err);
        });
}
</script>
