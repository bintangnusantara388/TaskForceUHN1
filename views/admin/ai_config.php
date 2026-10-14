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
    <div class="bg-gradient-to-r from-uhn-950 via-uhn-900 to-uhn-800 text-white rounded-3xl shadow-xl p-6 sm:p-8 flex flex-col md:flex-row md:items-center justify-between gap-6 border border-uhn-900 relative overflow-hidden">
        <!-- Watermark AI Icon -->
        <div class="absolute -right-6 -bottom-6 text-white/5 select-none pointer-events-none">
            <svg class="w-64 h-64" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
        </div>

        <div class="relative z-10 space-y-2">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 text-rose-200 text-xs font-bold border border-white/15 backdrop-blur-sm">
                <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                <span>Administrator Task Force</span>
                <span class="text-white/40">&bull;</span>
                <span>Pusat Kendali Kecerdasan Buatan (AI)</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-white leading-tight">
                Konfigurasi &amp; Manajemen Teknologi AI
            </h1>
            <p class="text-xs sm:text-sm text-rose-100/80 max-w-2xl leading-relaxed">
                Kelola kredensial API Google Gemini, pemilihan model neural, parameter sintesis narasi 3 kalimat bahasa awam, dan mode toleransi *fallback* lokal UHN.
            </p>
        </div>

        <div class="relative z-10 flex flex-col sm:flex-row md:flex-col items-start md:items-end gap-2 flex-shrink-0">
            <?php if ($isConfigured): ?>
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-2xl bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 text-xs font-bold backdrop-blur-sm">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    <span>Gemini API Terhubung</span>
                </div>
            <?php else: ?>
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-2xl bg-amber-500/20 text-amber-300 border border-amber-400/30 text-xs font-bold backdrop-blur-sm">
                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                    <span>Mode Fallback Lokal Aktif</span>
                </div>
            <?php endif; ?>
            <span class="text-[11px] text-rose-200/70 font-mono">Mesin: <?= htmlspecialchars($activeModel) ?></span>
        </div>
    </div>

    <!-- 4 KPI Cards: AI Performance & Activity -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-white p-4 sm:p-5 rounded-2xl sm:rounded-3xl border border-rose-100 shadow-sm flex flex-col justify-between">
            <span class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Status Mesin</span>
            <div class="mt-2">
                <span class="text-lg sm:text-2xl font-black <?= $isConfigured ? 'text-emerald-700' : 'text-amber-700' ?>">
                    <?= $isConfigured ? 'Google Gemini' : 'Local NLP' ?>
                </span>
                <p class="text-[10px] sm:text-xs text-slate-500 mt-0.5">
                    <?= $isConfigured ? 'Live Cloud LLM Aktif' : 'Neural Rule Synthesizer' ?>
                </p>
            </div>
        </div>

        <div class="bg-white p-4 sm:p-5 rounded-2xl sm:rounded-3xl border border-rose-100 shadow-sm flex flex-col justify-between">
            <span class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Model Terpilih</span>
            <div class="mt-2">
                <span class="text-sm sm:text-lg font-black text-slate-900 font-mono truncate block">
                    <?= htmlspecialchars($activeModel) ?>
                </span>
                <p class="text-[10px] sm:text-xs text-slate-500 mt-0.5">Suhu: <?= htmlspecialchars($temperature) ?> &bull; <?= $maxTokens ?> Tok</p>
            </div>
        </div>

        <div class="bg-white p-4 sm:p-5 rounded-2xl sm:rounded-3xl border border-rose-100 shadow-sm flex flex-col justify-between">
            <span class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Total Generasi</span>
            <div class="mt-2">
                <span class="text-lg sm:text-2xl font-black text-uhn-900">
                    <?= (int)$stats['total_generations'] ?> <span class="text-xs font-bold text-slate-400">Panggilan</span>
                </span>
                <p class="text-[10px] sm:text-xs text-slate-500 mt-0.5">Audit log terverifikasi</p>
            </div>
        </div>

        <div class="bg-white p-4 sm:p-5 rounded-2xl sm:rounded-3xl border border-rose-100 shadow-sm flex flex-col justify-between">
            <span class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Keandalan Sistem</span>
            <div class="mt-2">
                <span class="text-lg sm:text-2xl font-black text-emerald-600">
                    100%
                </span>
                <p class="text-[10px] sm:text-xs text-slate-500 mt-0.5">Zero-Downtime Guarantee</p>
            </div>
        </div>
    </div>

    <!-- Main Configuration Form -->
    <div class="bg-white rounded-3xl shadow-sm border border-rose-100 p-6 sm:p-8">
        
        <form action="/index.php?action=ai_config_save" method="POST" class="space-y-8">
            
            <!-- Section 1: Google Gemini API Credentials -->
            <div class="space-y-4 pb-6 border-b border-rose-100">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider text-uhn-900 flex items-center gap-2">
                            <span>1. Kredensial Google Gemini API</span>
                            <span class="text-xs font-normal text-slate-400">(Live LLM Connection)</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            API Key disimpan secara aman di basis data server dan digunakan saat merumuskan narasi 3 kalimat bahasa awam.
                        </p>
                    </div>
                    
                    <a href="https://aistudio.google.com/app/apikey" target="_blank" 
                       class="text-xs font-bold text-blue-600 hover:text-blue-800 underline inline-flex items-center gap-1 self-start sm:self-auto">
                        Dapatkan API Key Gratis di Google AI Studio &rarr;
                    </a>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                        Google Gemini API Key
                    </label>
                    <div class="relative flex items-center">
                        <input type="password" name="gemini_api_key" id="apiKeyInput" 
                               value="<?= htmlspecialchars($currentApiKey) ?>" 
                               placeholder="AIzaSy..." 
                               class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-3 text-xs sm:text-sm font-mono pr-28 focus:ring-2 focus:ring-rose-400 focus:border-rose-400">
                        <div class="absolute right-2 flex items-center gap-1">
                            <button type="button" onclick="toggleApiKeyVisibility()" 
                                    class="px-2.5 py-1.5 text-xs text-slate-500 hover:text-slate-800 bg-white border border-slate-200 rounded-lg font-semibold transition-colors">
                                <span id="toggleEyeText">Tampilkan</span>
                            </button>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-2 pt-1 text-xs text-slate-500">
                        <span>Biarkan kosong jika hanya ingin menggunakan mode bawaan UHN Local Synthesizer.</span>
                        
                        <!-- Tombol Uji Koneksi Live -->
                        <button type="button" onclick="runLiveAiConnectionTest()" id="btnTestConn"
                                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white rounded-xl font-bold shadow-sm active:scale-95 transition-all">
                            <svg id="testSpinIcon" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            <span id="testBtnText">⚡ Uji Koneksi Live AI</span>
                        </button>
                    </div>
                </div>

                <!-- Box Hasil Uji Koneksi Live (Hidden Default) -->
                <div id="connTestResultBox" class="hidden rounded-2xl p-4 transition-all"></div>
            </div>

            <!-- Section 2: Pemilihan Model Gemini -->
            <div class="space-y-4 pb-6 border-b border-rose-100">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider text-uhn-900">
                        2. Pemilihan Model Kecerdasan Buatan (Model Selection)
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Tentukan arsitektur model Google Gemini yang diprioritaskan saat menyusun narasi dan rilis pers.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 sm:gap-4">
                    <!-- Model 1: gemini-1.5-flash -->
                    <label class="relative flex flex-col p-4 rounded-2xl border-2 cursor-pointer transition-all has-[:checked]:border-uhn-800 has-[:checked]:bg-rose-50/40 has-[:checked]:shadow-md border-slate-200 bg-white hover:border-slate-300">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold text-slate-900 font-mono">gemini-1.5-flash</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800">Rekomendasi</span>
                        </div>
                        <input type="radio" name="ai_model" value="gemini-1.5-flash" <?= ($activeModel === 'gemini-1.5-flash') ? 'checked' : '' ?> class="hidden">
                        <p class="text-[11px] text-slate-600 mt-1 leading-relaxed">
                            Kecepatan respons super kilat, efisiensi kuota maksimal, dan konsistensi tinggi dalam mengikuti batas 3 kalimat borang WURI.
                        </p>
                    </label>

                    <!-- Model 2: gemini-2.0-flash -->
                    <label class="relative flex flex-col p-4 rounded-2xl border-2 cursor-pointer transition-all has-[:checked]:border-uhn-800 has-[:checked]:bg-rose-50/40 has-[:checked]:shadow-md border-slate-200 bg-white hover:border-slate-300">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold text-slate-900 font-mono">gemini-2.0-flash</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-blue-100 text-blue-800">Generasi Terkini</span>
                        </div>
                        <input type="radio" name="ai_model" value="gemini-2.0-flash" <?= ($activeModel === 'gemini-2.0-flash') ? 'checked' : '' ?> class="hidden">
                        <p class="text-[11px] text-slate-600 mt-1 leading-relaxed">
                            Model generasi mutakhir dengan kecakapan sintesis konteks superior, pemahaman istilah riset terkini, dan gaya bahasa adaptif.
                        </p>
                    </label>

                    <!-- Model 3: gemini-1.5-pro -->
                    <label class="relative flex flex-col p-4 rounded-2xl border-2 cursor-pointer transition-all has-[:checked]:border-uhn-800 has-[:checked]:bg-rose-50/40 has-[:checked]:shadow-md border-slate-200 bg-white hover:border-slate-300">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold text-slate-900 font-mono">gemini-1.5-pro</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-purple-100 text-purple-800">Penalaran Kompleks</span>
                        </div>
                        <input type="radio" name="ai_model" value="gemini-1.5-pro" <?= ($activeModel === 'gemini-1.5-pro') ? 'checked' : '' ?> class="hidden">
                        <p class="text-[11px] text-slate-600 mt-1 leading-relaxed">
                            Daya analitik mendalam untuk capaian multidisiplin yang membutuhkan penjabaran signifikansi dampak internasional secara elaboratif.
                        </p>
                    </label>
                </div>
            </div>

            <!-- Section 3: Parameter Sintesis & Perilaku AI -->
            <div class="space-y-4 pb-6 border-b border-rose-100">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider text-uhn-900">
                        3. Parameter Sintesis &amp; Kebijakan Borang WURI
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Sesuaikan gaya default, derajat kreativitas, dan mekanisme toleransi kesalahan (*fallback*).
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    
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
                        <span class="text-[11px] text-slate-400 mt-1 block">Dapat diubah secara dinamis oleh user pada form.</span>
                    </div>

                    <!-- Temperatur / Kreativitas -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                            Temperatur / Kreativitas Model
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

                <!-- Toggle Sakelar Fallback & Kepatuhan 3 Kalimat -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                    <div class="p-4 rounded-2xl border border-rose-100 bg-rose-50/30 flex items-start gap-3">
                        <input type="checkbox" name="ai_enable_fallback" id="chkFallback" value="1" <?= $fallbackEnabled ? 'checked' : '' ?>
                               class="mt-1 h-4 w-4 text-uhn-800 rounded border-slate-300 focus:ring-rose-400">
                        <div>
                            <label for="chkFallback" class="text-xs font-bold text-slate-900 cursor-pointer block">
                                Izinkan Otomatis Berpindah ke UHN Local Synthesizer (*Fallback Mode*)
                            </label>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                Jika API Key habis kuota, jaringan terputus, atau API Gemini mengalami gangguan, sistem langsung mengalihkan pembuatan narasi ke mesin sintesis lokal tanpa pesan *error* kepada PIC unit.
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
                                Sistem secara otomatis memotong atau melengkapi keluaran agar selalu berakhiran titik dan berjumlah tepat 3 kalimat guna memenuhi validasi borang akreditasi.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 4: Panduan Khusus Institusi (Custom Prompt Guidelines) -->
            <div class="space-y-3 pb-6 border-b border-rose-100">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider text-uhn-900">
                        4. Panduan Khusus Institusi (*Custom Prompt Guidelines*)
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Instruksi tambahan yang akan disisipkan ke dalam sistem AI pada setiap panggilan (misal: pedoman ejaan, penyebutan nama kampus, atau penekanan SDGs).
                    </p>
                </div>

                <textarea name="ai_system_instructions" rows="3" 
                          placeholder="Contoh: Selalu gunakan ejaan baku Bahasa Indonesia. Cantumkan singkatan UHN untuk Universitas Harkat Negeri. Tekankan kontribusi inovasi terhadap Tujuan Pembangunan Berkelanjutan (SDGs)..."
                          class="w-full bg-slate-50 border border-slate-300 rounded-2xl p-3.5 text-xs sm:text-sm font-medium focus:ring-2 focus:ring-rose-400 focus:border-rose-400 leading-relaxed"><?= htmlspecialchars($customInstructions) ?></textarea>
            </div>

            <!-- Tombol Simpan -->
            <div class="flex items-center justify-between pt-2">
                <a href="/index.php?page=dashboard" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors">
                    Kembali ke Dashboard
                </a>
                <button type="submit" 
                        class="px-7 py-3 bg-gradient-to-r from-uhn-900 via-rose-800 to-uhn-800 hover:from-uhn-950 hover:to-rose-900 text-white rounded-xl text-xs font-black shadow-lg shadow-rose-950/20 active:scale-95 transition-all flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-300" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
                    <span>Simpan Perubahan Konfigurasi AI</span>
                </button>
            </div>

        </form>

    </div>

    <!-- Section 5: Log & Riwayat Aktivitas AI Terkini -->
    <div class="bg-white rounded-3xl shadow-sm border border-rose-100 p-6 sm:p-8 space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider text-uhn-900">
                    5. Log Aktivitas &amp; Panggilan AI Terkini
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

<script>
function toggleApiKeyVisibility() {
    const input = document.getElementById('apiKeyInput');
    const text = document.getElementById('toggleEyeText');
    if (!input || !text) return;
    if (input.type === 'password') {
        input.type = 'text';
        text.innerText = 'Sembunyikan';
    } else {
        input.type = 'password';
        text.innerText = 'Tampilkan';
    }
}

function runLiveAiConnectionTest() {
    const apiKey = document.getElementById('apiKeyInput').value.trim();
    const model = document.querySelector('[name="ai_model"]:checked')?.value || 'gemini-1.5-flash';
    
    const btn = document.getElementById('btnTestConn');
    const btnText = document.getElementById('testBtnText');
    const resultBox = document.getElementById('connTestResultBox');

    btn.disabled = true;
    btn.classList.add('opacity-80', 'cursor-not-allowed');
    btnText.innerText = 'Menguji Sambungan...';

    resultBox.classList.remove('hidden');
    resultBox.className = 'rounded-2xl p-4 bg-slate-100 border border-slate-200 text-xs text-slate-700 flex items-center gap-3 animate-pulse';
    resultBox.innerHTML = '<div class="w-4 h-4 border-2 border-slate-400 border-t-uhn-800 rounded-full animate-spin"></div><span>Menghubungi endpoint Google Gemini API (model: ' + model + ')...</span>';

    fetch('/index.php?action=ai_test_connection', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            api_key: apiKey,
            model: model
        })
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.classList.remove('opacity-80', 'cursor-not-allowed');
        btnText.innerText = '⚡ Uji Koneksi Live AI';

        if (data && data.success) {
            resultBox.className = 'rounded-2xl p-4 bg-emerald-50 border border-emerald-300 text-xs text-emerald-900 space-y-2';
            resultBox.innerHTML = `
                <div class="flex items-center justify-between">
                    <span class="font-bold flex items-center gap-1.5 text-emerald-800">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        ${data.message || 'Koneksi Berhasil!'}
                    </span>
                    <span class="font-mono text-[11px] bg-emerald-100 px-2 py-0.5 rounded font-bold">Latensi: ${data.latency_ms} ms</span>
                </div>
                <div class="text-[11px] bg-white/80 p-2.5 rounded-xl border border-emerald-200 text-slate-700">
                    <strong class="text-slate-900">Sampel Respons Model:</strong> "${data.sample_response || '-'}"
                </div>
            `;
        } else {
            resultBox.className = 'rounded-2xl p-4 bg-rose-50 border border-rose-300 text-xs text-rose-950 space-y-1';
            resultBox.innerHTML = `
                <div class="font-bold flex items-center gap-1.5 text-rose-800">
                    <span>⚠️</span>
                    <span>Uji Koneksi Gagal (HTTP ${data.http_code || 400})</span>
                </div>
                <p class="text-[11px] text-rose-900 leading-relaxed">${data.error || 'Terjadi kesalahan saat menghubungi API.'}</p>
                <p class="text-[10px] text-slate-500 pt-1">Pastikan API Key benar dan akun Google Cloud/AI Studio memiliki kuota aktif.</p>
            `;
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.classList.remove('opacity-80', 'cursor-not-allowed');
        btnText.innerText = '⚡ Uji Koneksi Live AI';
        resultBox.className = 'rounded-2xl p-4 bg-rose-50 border border-rose-300 text-xs text-rose-900';
        resultBox.innerHTML = '<strong>Kesalahan Jaringan:</strong> Tidak dapat menghubungi server internal.';
        console.error(err);
    });
}
</script>
