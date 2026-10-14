<?php
// views/komunikasi/index.php
require_once __DIR__ . '/../../app/Models/Entry.php';
require_once __DIR__ . '/../../app/Config.php';
require_once __DIR__ . '/../../app/Auth.php';
require_once __DIR__ . '/../../app/Helpers.php';

$activePeriod = $_GET['period_id'] ?? '2026-10';
$activeUnit = $_GET['unit_code'] ?? '';
$activeCommStatus = $_GET['comm_status'] ?? '';

$filters = [
    'period_id' => $activePeriod,
    'unit_code' => $activeUnit,
    'comm_status' => $activeCommStatus,
    'screening_status' => 'SIAP_DIOLAH' // Hanya data yang sudah lolos screening unit
];

$entries = Entry::getAll($filters);

// Hitung statistik antrian
$allQueued = Entry::getAll(['period_id' => $activePeriod, 'screening_status' => 'SIAP_DIOLAH']);
$countBelum = count(array_filter($allQueued, fn($e) => $e['comm_status'] === 'BELUM_DIOLAH'));
$countSedang = count(array_filter($allQueued, fn($e) => $e['comm_status'] === 'SEDANG_DIOLAH'));
$countTerbit = count(array_filter($allQueued, fn($e) => $e['comm_status'] === 'SUDAH_TERBIT'));
?>

<div class="space-y-6">

    <!-- Header Meja Kerja (Crimson Gradient) -->
    <div class="bg-gradient-to-r from-uhn-950 via-uhn-900 to-uhn-800 text-white rounded-3xl shadow-xl p-6 sm:p-8 flex flex-col md:flex-row md:items-center justify-between gap-6 border border-uhn-900 relative overflow-hidden">
        <div class="relative z-10">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 text-rose-200 text-xs font-bold border border-white/15 backdrop-blur-sm mb-2">
                <span class="w-2 h-2 rounded-full bg-rose-400 animate-pulse"></span>
                <span>Meja Kerja Kurasi</span>
                <span class="text-white/40">&bull;</span>
                <span>Bagian Komunikasi &amp; Promosi UHN</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-white mt-1">
                Pengolahan Konten Branding &amp; Reputasi
            </h1>
            <p class="text-xs sm:text-sm text-rose-100/80 mt-1 max-w-2xl leading-relaxed">
                Kurasi data capaian terverifikasi unit untuk diproduksi menjadi siaran pers, artikel website, konten media sosial, dan laporan capaian universitas.
            </p>
        </div>

        <form action="/index.php" method="GET" class="relative z-10 bg-black/20 backdrop-blur-md p-2 rounded-2xl border border-white/15 flex items-center gap-2 self-start md:self-auto">
            <input type="hidden" name="page" value="komunikasi">
            <label class="text-xs font-semibold text-rose-200 pl-2">Bulan:</label>
            <select name="period_id" onchange="this.form.submit()" 
                    class="bg-white border-0 text-slate-900 text-xs font-bold rounded-xl px-3 py-2 focus:ring-2 focus:ring-rose-400 shadow-sm cursor-pointer">
                <?php foreach (PERIODS as $pid => $pname): ?>
                    <option value="<?= $pid ?>" <?= ($pid === $activePeriod) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($pname) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <!-- Status Antrian Kurasi (Compact 3-Col Grid di Mobile, 3-Col di Desktop) -->
    <div class="grid grid-cols-3 gap-2.5 sm:gap-4">
        <a href="/index.php?page=komunikasi&period_id=<?= urlencode($activePeriod) ?>&comm_status=BELUM_DIOLAH" 
           class="bg-white p-3.5 sm:p-5 rounded-2xl sm:rounded-3xl border <?= ($activeCommStatus === 'BELUM_DIOLAH') ? 'border-amber-500 ring-2 ring-amber-200' : 'border-rose-100' ?> shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-2 hover:border-amber-400 transition-all group">
            <div>
                <p class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider leading-tight">Belum Diolah</p>
                <h3 class="text-xl sm:text-3xl font-black text-amber-600 mt-1"><?= $countBelum ?> <span class="text-xs sm:text-xl font-bold">Data</span></h3>
                <p class="hidden sm:block text-[11px] text-slate-500 mt-0.5">Perlu dijadwalkan produksi</p>
            </div>
            <div class="hidden sm:flex w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 items-center justify-center font-black text-lg group-hover:scale-105 transition-transform flex-shrink-0">
                !
            </div>
        </a>

        <a href="/index.php?page=komunikasi&period_id=<?= urlencode($activePeriod) ?>&comm_status=SEDANG_DIOLAH" 
           class="bg-white p-3.5 sm:p-5 rounded-2xl sm:rounded-3xl border <?= ($activeCommStatus === 'SEDANG_DIOLAH') ? 'border-uhn-700 ring-2 ring-rose-200' : 'border-rose-100' ?> shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-2 hover:border-uhn-700 transition-all group">
            <div>
                <p class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider leading-tight">Sedang Diolah</p>
                <h3 class="text-xl sm:text-3xl font-black text-uhn-800 mt-1"><?= $countSedang ?> <span class="text-xs sm:text-xl font-bold">Data</span></h3>
                <p class="hidden sm:block text-[11px] text-slate-500 mt-0.5">Dalam proses penulisan / desain</p>
            </div>
            <div class="hidden sm:flex w-12 h-12 rounded-2xl bg-rose-50 text-uhn-800 items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                <svg class="w-6 h-6 animate-spin text-uhn-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            </div>
        </a>

        <a href="/index.php?page=komunikasi&period_id=<?= urlencode($activePeriod) ?>&comm_status=SUDAH_TERBIT" 
           class="bg-white p-3.5 sm:p-5 rounded-2xl sm:rounded-3xl border <?= ($activeCommStatus === 'SUDAH_TERBIT') ? 'border-emerald-500 ring-2 ring-emerald-200' : 'border-rose-100' ?> shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-2 hover:border-emerald-400 transition-all group">
            <div>
                <p class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider leading-tight">Sudah Terbit</p>
                <h3 class="text-xl sm:text-3xl font-black text-emerald-600 mt-1"><?= $countTerbit ?> <span class="text-xs sm:text-xl font-bold">Data</span></h3>
                <p class="hidden sm:block text-[11px] text-slate-500 mt-0.5">Telah rilis dengan URL aktif</p>
            </div>
            <div class="hidden sm:flex w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
        </a>
    </div>

    <!-- Filter Tab Unit (Swipeable di Mobile) -->
    <div class="flex items-center gap-2 overflow-x-auto no-scrollbar border-b border-rose-100 pb-3 sm:flex-wrap">
        <span class="text-xs font-bold text-slate-500 mr-1 whitespace-nowrap flex-shrink-0">Saring Unit:</span>
        <a href="/index.php?page=komunikasi&period_id=<?= urlencode($activePeriod) ?>" 
           class="whitespace-nowrap flex-shrink-0 px-3.5 py-1.5 rounded-full text-xs font-bold <?= empty($activeUnit) ? 'bg-uhn-950 text-white shadow-sm' : 'bg-white text-slate-700 border border-rose-100 hover:bg-rose-50' ?>">
            Semua Unit
        </a>
        <?php foreach (['PPM', 'PSDM', 'KEMAHASISWAAN', 'ALUMNI_KARIR'] as $u): ?>
            <a href="/index.php?page=komunikasi&period_id=<?= urlencode($activePeriod) ?>&unit_code=<?= $u ?>" 
               class="whitespace-nowrap flex-shrink-0 px-3.5 py-1.5 rounded-full text-xs font-bold <?= ($activeUnit === $u) ? 'bg-gradient-to-r from-uhn-800 to-rose-700 text-white shadow-sm' : 'bg-white text-slate-700 border border-rose-100 hover:bg-rose-50' ?>">
                <?= $u ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Antrian Kurasi Cards -->
    <div class="space-y-4">
        <?php if (empty($entries)): ?>
            <div class="bg-white rounded-3xl p-10 text-center border border-rose-100 text-slate-400 italic">
                Tidak ada data "Siap Diolah" untuk kriteria filter ini.
            </div>
        <?php else: ?>
            <?php foreach ($entries as $item): ?>
                <?php $isRestricted = ($item['name_publication_consent'] === 'TIDAK'); ?>
                <div class="bg-white rounded-3xl shadow-sm border <?= $isRestricted ? 'border-uhn-700 ring-2 ring-rose-200' : 'border-rose-100' ?> p-4 sm:p-6 space-y-4 hover:shadow-md transition-shadow">
                    
                    <!-- Alert Privasi Jika Consent == TIDAK -->
                    <?php if ($isRestricted): ?>
                        <div class="bg-rose-50 border border-rose-300 rounded-2xl p-4 flex items-start gap-3">
                            <span class="text-lg text-uhn-900 font-bold">⚠️</span>
                            <div>
                                <span class="text-xs font-black text-uhn-900 uppercase">PERINGATAN PRIVASI (SUBJEK MENOLAK PUBLIKASI NAMA - UU PDP):</span>
                                <p class="text-xs text-rose-950 mt-0.5 leading-relaxed">
                                    Dilarang mencantumkan nama lengkap personil ini di portal publik maupun media sosial. Gunakan sebutan kolektif/departemen.
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="flex flex-col md:flex-row md:items-start justify-between gap-6">
                        <div class="space-y-2.5 flex-grow">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-uhn-900 border border-rose-200">
                                    <?= htmlspecialchars($item['unit_code']) ?>
                                </span>
                                <?= Helpers::badgeTier($item['tier_type']) ?>
                                <?= Helpers::badgeComm($item['comm_status']) ?>
                                <?= Helpers::badgeConsent($item['name_publication_consent']) ?>
                                <span class="text-xs text-slate-400 font-medium">Disubmit: <?= htmlspecialchars($item['screening_submitted_at'] ?? $item['created_at']) ?></span>
                            </div>

                            <h3 class="text-lg font-bold text-slate-900 leading-snug">
                                <a href="/index.php?page=entries_show&id=<?= $item['id'] ?>" class="hover:text-uhn-800 transition-colors">
                                    <?= htmlspecialchars($item['title']) ?>
                                </a>
                            </h3>

                            <p class="text-xs text-slate-700 bg-slate-50/80 p-3.5 rounded-2xl border border-slate-100 leading-relaxed">
                                <strong class="text-slate-900">Bahan Narasi:</strong> <?= htmlspecialchars($item['short_description']) ?>
                            </p>

                            <div class="flex items-center gap-2 pt-0.5">
                                <button type="button" onclick="openAiPressReleaseModal(<?= (int)$item['id'] ?>)" 
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-rose-50 to-amber-50 hover:from-rose-100 hover:to-amber-100 border border-rose-200 text-uhn-900 rounded-xl text-xs font-bold transition-all shadow-sm active:scale-95">
                                    <span>✨</span>
                                    <span>AI Siaran Pers &amp; Poles Narasi</span>
                                </button>
                            </div>

                            <div class="text-xs text-slate-500 flex flex-wrap items-center gap-3 pt-1">
                                <?php if (!empty($item['attachment_name'])): ?>
                                    <span class="flex items-center gap-1 text-uhn-800 font-bold">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                        <?= htmlspecialchars($item['attachment_name']) ?> (<?= Helpers::formatBytes($item['attachment_size_bytes']) ?>)
                                    </span>
                                <?php endif; ?>

                                <?php if (!empty($item['notes'])): ?>
                                    <span class="text-slate-400 italic">Catatan Unit: "<?= htmlspecialchars($item['notes']) ?>"</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Form Cepat Update Status Pengolahan Komunikasi -->
                        <div class="w-full md:w-80 bg-rose-50/40 p-4 sm:p-5 rounded-2xl border border-rose-100 flex-shrink-0">
                            <form action="/index.php?action=entries_update_komunikasi&id=<?= $item['id'] ?>" method="POST" class="space-y-3">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Status Pengolahan:</label>
                                    <select name="comm_status" class="w-full bg-white border border-rose-200 rounded-xl p-2.5 text-xs font-bold focus:ring-2 focus:ring-uhn-700">
                                        <option value="BELUM_DIOLAH" <?= ($item['comm_status'] === 'BELUM_DIOLAH') ? 'selected' : '' ?>>Belum Diolah</option>
                                        <option value="SEDANG_DIOLAH" <?= ($item['comm_status'] === 'SEDANG_DIOLAH') ? 'selected' : '' ?>>Sedang Diolah</option>
                                        <option value="SUDAH_TERBIT" <?= ($item['comm_status'] === 'SUDAH_TERBIT') ? 'selected' : '' ?>>Sudah Terbit</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Tautan Publikasi Hasil:</label>
                                    <input type="url" name="comm_published_url" value="<?= htmlspecialchars($item['comm_published_url'] ?? '') ?>" 
                                           placeholder="https://harkatnegeri.ac.id/berita/..." 
                                           class="w-full bg-white border border-rose-200 rounded-xl p-2.5 text-xs font-medium focus:ring-2 focus:ring-uhn-700">
                                </div>

                                <button type="submit" class="w-full py-2.5 px-3 bg-gradient-to-r from-uhn-800 to-uhn-700 hover:from-uhn-900 hover:to-uhn-800 text-white rounded-xl text-xs font-bold transition-all shadow-md shadow-rose-950/20">
                                    Simpan Status Kurasi
                                </button>
                            </form>
                        </div>
                    </div>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<!-- ========================================================================= -->
<!-- MODAL: STUDIO AI KOMUNIKASI & POLES SIARAN PERS -->
<!-- ========================================================================= -->
<div id="aiPressModal" class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-sm flex items-center justify-center p-3 sm:p-6 overflow-y-auto">
    <div class="bg-white w-full max-w-3xl rounded-3xl shadow-2xl border border-rose-100 overflow-hidden my-auto max-h-[92vh] flex flex-col animate-in fade-in zoom-in-95 duration-150">
        
        <!-- Header Modal (Deep Crimson Gradient) -->
        <div class="px-6 py-5 bg-gradient-to-r from-uhn-950 via-uhn-900 to-uhn-800 text-white flex items-center justify-between gap-4 flex-shrink-0">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-2xl bg-white/10 text-amber-300 flex items-center justify-center font-black text-lg border border-white/15 shadow-sm">
                    ✨
                </span>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-base sm:text-lg font-black text-white leading-tight">
                            Studio AI: Poles Narasi &amp; Siaran Pers
                        </h3>
                        <span id="modalAiProviderBadge" class="hidden text-[10px] font-bold px-2 py-0.5 rounded-full bg-white/20 text-rose-100 border border-white/20"></span>
                    </div>
                    <p class="text-xs text-rose-200 mt-0.5">
                        Otomatisasi perumusan materi publikasi, narasi WURI, dan artikel berita kampus UHN
                    </p>
                </div>
            </div>
            <button type="button" onclick="closeAiPressReleaseModal()" 
                    class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center font-bold text-sm transition-colors">
                &times;
            </button>
        </div>

        <!-- Modal Body (Scrollable) -->
        <div class="p-6 space-y-6 overflow-y-auto flex-grow" id="aiModalContentArea">
            
            <!-- Loading Indicator -->
            <div id="aiModalLoading" class="py-12 flex flex-col items-center justify-center space-y-4">
                <div class="w-12 h-12 rounded-full border-4 border-rose-200 border-t-uhn-800 animate-spin"></div>
                <div class="text-center">
                    <h4 class="text-sm font-bold text-slate-800">AI Sedang Menyusun Siaran Pers...</h4>
                    <p class="text-xs text-slate-500 mt-1">Menganalisis capaian unit, merumuskan headline, narasi 3 kalimat, dan naskah berita.</p>
                </div>
            </div>

            <!-- Content Area (Muncul setelah data siap) -->
            <div id="aiModalData" class="hidden space-y-6">

                <!-- 1. Narasi 3 Kalimat WURI -->
                <div class="bg-rose-50/50 border border-rose-200 rounded-2xl p-4 sm:p-5 space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-black text-uhn-950 uppercase tracking-wider">
                                1. Narasi Ringkas WURI (3 Kalimat Bahasa Awam)
                            </span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">
                                Wajib Borang
                            </span>
                        </div>
                        <div class="flex items-center gap-1">
                            <button type="button" onclick="changeModalAiTone('STANDAR')" id="modalToneStandar" class="px-2 py-1 rounded-lg text-[11px] font-bold border transition-colors bg-uhn-900 text-white border-uhn-900">
                                🏛️ Standar
                            </button>
                            <button type="button" onclick="changeModalAiTone('MEDIA')" id="modalToneMedia" class="px-2 py-1 rounded-lg text-[11px] font-semibold border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition-colors">
                                📰 Media
                            </button>
                            <button type="button" onclick="changeModalAiTone('INSPIRATIF')" id="modalToneInspiratif" class="px-2 py-1 rounded-lg text-[11px] font-semibold border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition-colors">
                                💡 Inspiratif
                            </button>
                        </div>
                    </div>

                    <div id="modalNarrativeText" class="text-xs sm:text-sm text-slate-800 bg-white p-4 rounded-xl border border-rose-100 leading-relaxed font-medium">
                        <!-- Teks narasi diinjeksi via JS -->
                    </div>

                    <div class="flex items-center justify-between pt-1">
                        <span class="text-[11px] text-slate-400" id="modalNarrativeCount">3 / 3 Kalimat</span>
                        <button type="button" onclick="copyModalField('modalNarrativeText', this)" 
                                class="px-3 py-1.5 bg-white hover:bg-rose-50 text-uhn-900 border border-rose-200 rounded-xl text-xs font-bold transition-colors shadow-sm">
                            📋 Salin Narasi
                        </button>
                    </div>
                </div>

                <!-- 2. Naskah Siaran Pers Lengkap -->
                <div class="border border-slate-200 rounded-2xl p-4 sm:p-5 space-y-4 bg-white shadow-sm">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-black text-slate-900 uppercase tracking-wider">
                            2. Naskah Siaran Pers / Berita Portal Kampus
                        </h4>
                        <button type="button" onclick="copyFullPressRelease(this)" 
                                class="px-3 py-1.5 bg-uhn-900 hover:bg-uhn-950 text-white rounded-xl text-xs font-bold transition-all shadow-sm">
                            📋 Salin Seluruh Naskah Berita
                        </button>
                    </div>

                    <!-- Headline -->
                    <div>
                        <span class="text-[11px] font-bold text-slate-400 uppercase block mb-1">Rekomendasi Judul Berita (Headline):</span>
                        <div id="modalHeadlineText" class="text-sm sm:text-base font-extrabold text-uhn-950 bg-rose-50/60 p-3 rounded-xl border border-rose-100">
                            <!-- Headline diinjeksi via JS -->
                        </div>
                    </div>

                    <!-- Body Berita -->
                    <div>
                        <span class="text-[11px] font-bold text-slate-400 uppercase block mb-1">Batang Tubuh Siaran Pers (Lead &amp; Quotes):</span>
                        <div id="modalBodyText" class="text-xs sm:text-sm text-slate-700 bg-slate-50 p-4 rounded-xl border border-slate-200 leading-relaxed whitespace-pre-line font-normal">
                            <!-- Body diinjeksi via JS -->
                        </div>
                    </div>
                </div>

                <!-- 3. Caption Media Sosial & Kategori WURI -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="bg-amber-50/50 border border-amber-200 rounded-2xl p-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-amber-950 uppercase tracking-wide">3. Caption Media Sosial</span>
                            <button type="button" onclick="copyModalField('modalSocialText', this)" class="text-[11px] font-bold text-amber-900 hover:underline">
                                Salin
                            </button>
                        </div>
                        <div id="modalSocialText" class="text-xs text-slate-800 bg-white p-3 rounded-xl border border-amber-100 whitespace-pre-line leading-relaxed max-h-40 overflow-y-auto">
                            <!-- Caption diinjeksi via JS -->
                        </div>
                    </div>

                    <div class="bg-blue-50/50 border border-blue-200 rounded-2xl p-4 space-y-2 flex flex-col justify-between">
                        <div>
                            <span class="text-xs font-bold text-blue-950 uppercase tracking-wide">4. Rekomendasi Klaster WURI</span>
                            <p class="text-xs text-slate-600 mt-1">Kategori borang pemeringkatan reputasi institusional yang paling selaras:</p>
                            <div id="modalWuriCategory" class="mt-2 text-xs font-black text-blue-900 bg-white p-3 rounded-xl border border-blue-100">
                                <!-- Klaster diinjeksi via JS -->
                            </div>
                        </div>
                        <div class="text-[11px] text-slate-400 italic">
                            Disusun secara otomatis oleh UHN AI Assistant.
                        </div>
                    </div>
                </div>

            </div>

        </div>

        <!-- Footer Modal -->
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end flex-shrink-0">
            <button type="button" onclick="closeAiPressReleaseModal()" 
                    class="px-5 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl text-xs font-bold transition-colors">
                Tutup
            </button>
        </div>

    </div>
</div>

<script>
window.currentModalEntry = null;
window.currentModalAiData = null;

function openAiPressReleaseModal(entryId) {
    const modal = document.getElementById('aiPressModal');
    const loading = document.getElementById('aiModalLoading');
    const dataArea = document.getElementById('aiModalData');

    modal.classList.remove('hidden');
    loading.classList.remove('hidden');
    dataArea.classList.add('hidden');

    fetch('/index.php?action=ai_generate_press_release&id=' + entryId)
    .then(res => res.json())
    .then(data => {
        loading.classList.add('hidden');
        if (data && data.success) {
            window.currentModalAiData = data;
            window.currentModalEntry = data.entry;
            renderModalData(data);
            dataArea.classList.remove('hidden');
        } else {
            alert('Gagal memuat siaran pers AI: ' + (data.message || 'Terjadi kesalahan sistem'));
            closeAiPressReleaseModal();
        }
    })
    .catch(err => {
        loading.classList.add('hidden');
        console.error(err);
        alert('Terjadi kesalahan koneksi.');
        closeAiPressReleaseModal();
    });
}

function closeAiPressReleaseModal() {
    document.getElementById('aiPressModal').classList.add('hidden');
}

function renderModalData(data) {
    const provBadge = document.getElementById('modalAiProviderBadge');
    if (provBadge) {
        if (data.provider_label) {
            provBadge.innerText = '⚡ ' + data.provider_label;
            provBadge.classList.remove('hidden');
        } else {
            provBadge.classList.add('hidden');
        }
    }

    document.getElementById('modalHeadlineText').innerText = data.headline || '-';
    document.getElementById('modalBodyText').innerText = data.body || '-';
    document.getElementById('modalSocialText').innerText = data.social_caption || '-';
    document.getElementById('modalWuriCategory').innerText = data.recommended_wuri_category || '-';

    const narrative = (data.entry && data.entry.short_description) ? data.entry.short_description : '';
    document.getElementById('modalNarrativeText').innerText = narrative;
}

function changeModalAiTone(tone) {
    if (!window.currentModalEntry) return;

    ['Standar', 'Media', 'Inspiratif'].forEach(t => {
        const btn = document.getElementById('modalTone' + t);
        if (btn) {
            btn.className = 'px-2 py-1 rounded-lg text-[11px] font-semibold border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition-colors';
        }
    });
    const activeBtn = document.getElementById('modalTone' + (tone.charAt(0) + tone.slice(1).toLowerCase()));
    if (activeBtn) {
        activeBtn.className = 'px-2 py-1 rounded-lg text-[11px] font-bold border transition-colors bg-uhn-900 text-white border-uhn-900';
    }

    const payload = Object.assign({}, window.currentModalEntry);
    payload.tone = tone;

    const narEl = document.getElementById('modalNarrativeText');
    narEl.innerHTML = '<span class="text-slate-400 italic">Menyusun ulang narasi gaya ' + tone + '...</span>';

    fetch('/index.php?action=ai_generate_narrative', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data && data.success && data.narrative) {
            narEl.innerText = data.narrative;
        }
    })
    .catch(err => console.error(err));
}

function copyModalField(elementId, btn) {
    const el = document.getElementById(elementId);
    if (!el) return;
    const text = el.innerText;
    navigator.clipboard.writeText(text).then(() => {
        const originalText = btn.innerText;
        btn.innerText = '✓ Tersalin!';
        btn.classList.add('bg-emerald-50', 'text-emerald-700', 'border-emerald-300');
        setTimeout(() => {
            btn.innerText = originalText;
            btn.classList.remove('bg-emerald-50', 'text-emerald-700', 'border-emerald-300');
        }, 1500);
    });
}

function copyFullPressRelease(btn) {
    const headline = document.getElementById('modalHeadlineText')?.innerText || '';
    const body = document.getElementById('modalBodyText')?.innerText || '';
    const full = headline + "\n\n" + body;

    navigator.clipboard.writeText(full).then(() => {
        const originalText = btn.innerText;
        btn.innerText = '✓ Naskah Tersalin!';
        btn.classList.add('bg-emerald-700');
        setTimeout(() => {
            btn.innerText = originalText;
            btn.classList.remove('bg-emerald-700');
        }, 1500);
    });
}
</script>
