<?php
// views/entries/index.php
require_once __DIR__ . '/../../app/Models/Entry.php';
require_once __DIR__ . '/../../app/Config.php';
require_once __DIR__ . '/../../app/Auth.php';
require_once __DIR__ . '/../../app/Helpers.php';

$currentUser = Auth::user();

// Tangkap filter dari GET
$filters = [
    'period_id' => $_GET['period_id'] ?? '',
    'unit_code' => $_GET['unit_code'] ?? '',
    'tier_type' => $_GET['tier_type'] ?? '',
    'screening_status' => $_GET['screening_status'] ?? '',
    'comm_status' => $_GET['comm_status'] ?? '',
    'consent' => $_GET['consent'] ?? '',
    'keyword' => $_GET['keyword'] ?? ''
];

// Jika role adalah PIC_UNIT, kunci unit hanya untuk unitnya sendiri
if (Auth::isPIC() && Auth::unit()) {
    $filters['unit_code'] = Auth::unit();
}

$entries = Entry::getAll($filters);
?>

<div class="space-y-6">

    <!-- Top Action Bar (Crimson Style) -->
    <div class="bg-white rounded-3xl shadow-sm border border-rose-100 p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900">
                Inventarisasi Data Capaian Reputasi
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Penyaringan (screening) capaian institusi yang terverifikasi dan layak publikasi.
                <?php if (Auth::isPIC()): ?>
                    <span class="inline-block font-extrabold text-uhn-900 bg-rose-100 px-2.5 py-0.5 rounded-full text-xs ml-1">
                        Unit Aktif: <?= htmlspecialchars(Auth::unit()) ?>
                    </span>
                <?php endif; ?>
            </p>
        </div>

        <!-- Tombol Tambah Data Berdasarkan Unit -->
        <div class="flex items-center gap-2">
            <?php if (Auth::isPIC() && Auth::unit()): ?>
                <a href="/index.php?page=entries_create&unit_code=<?= urlencode(Auth::unit()) ?>" 
                   class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-uhn-800 to-uhn-700 hover:from-uhn-900 hover:to-uhn-800 text-white rounded-xl text-xs font-black shadow-md shadow-rose-950/20 transition-all">
                    <svg class="w-4 h-4 text-rose-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    + Input Capaian Unit <?= htmlspecialchars(Auth::unit()) ?>
                </a>
            <?php elseif (Auth::isAdmin()): ?>
                <div class="relative inline-block text-left" id="tambahDropdownContainer">
                    <button type="button" onclick="document.getElementById('tambahMenu').classList.toggle('hidden')" 
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-uhn-800 to-uhn-700 hover:from-uhn-900 hover:to-uhn-800 text-white rounded-xl text-xs font-black shadow-md shadow-rose-950/20 transition-all">
                        <svg class="w-4 h-4 text-rose-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        + Input Data Baru
                        <svg class="w-3.5 h-3.5 ml-1 text-rose-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div id="tambahMenu" class="hidden absolute right-0 mt-2 w-60 rounded-2xl shadow-xl bg-white ring-1 ring-black/5 z-20 divide-y divide-rose-50 border border-rose-100 overflow-hidden">
                        <div class="py-1.5 text-xs">
                            <a href="/index.php?page=entries_create&unit_code=PPM" class="block px-4 py-2.5 text-slate-700 hover:bg-rose-50 hover:text-uhn-900 font-bold">Modul 1: PPM (Paten/HKI)</a>
                            <a href="/index.php?page=entries_create&unit_code=PSDM" class="block px-4 py-2.5 text-slate-700 hover:bg-rose-50 hover:text-uhn-900 font-bold">Modul 2: PSDM (Pegawai)</a>
                            <a href="/index.php?page=entries_create&unit_code=KEMAHASISWAAN" class="block px-4 py-2.5 text-slate-700 hover:bg-rose-50 hover:text-uhn-900 font-bold">Modul 3: Kemahasiswaan (Lomba)</a>
                            <a href="/index.php?page=entries_create&unit_code=ALUMNI_KARIR" class="block px-4 py-2.5 text-slate-700 hover:bg-rose-50 hover:text-uhn-900 font-bold">Modul 4: Alumni Karir</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Filter Pills: Akses Cepat Filter Kategori (Swipeable di Mobile) -->
    <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1 sm:flex-wrap">
        <span class="text-xs font-bold text-slate-500 mr-1 whitespace-nowrap flex-shrink-0">Filter Cepat:</span>
        <a href="/index.php?page=entries<?= (Auth::isPIC() && Auth::unit()) ? '&unit_code='.urlencode(Auth::unit()) : '' ?>" 
           class="whitespace-nowrap flex-shrink-0 px-3.5 py-1.5 rounded-full text-xs font-bold <?= (empty($filters['tier_type']) && empty($filters['screening_status']) && empty($filters['comm_status']) && empty($filters['consent'])) ? 'bg-uhn-950 text-white shadow-sm' : 'bg-white text-slate-700 border border-rose-100 hover:bg-rose-50' ?>">
            Semua Data
        </a>
        <a href="/index.php?page=entries&tier_type=SIGNATURE<?= (Auth::isPIC() && Auth::unit()) ? '&unit_code='.urlencode(Auth::unit()) : '' ?>" 
           class="whitespace-nowrap flex-shrink-0 px-3.5 py-1.5 rounded-full text-xs font-bold <?= ($filters['tier_type'] === 'SIGNATURE') ? 'bg-gradient-to-r from-uhn-800 to-rose-700 text-white shadow-sm' : 'bg-rose-50 text-uhn-900 border border-rose-200 hover:bg-rose-100' ?>">
            ★ Signature Saja
        </a>
        <a href="/index.php?page=entries&screening_status=SIAP_DIOLAH<?= (Auth::isPIC() && Auth::unit()) ? '&unit_code='.urlencode(Auth::unit()) : '' ?>" 
           class="whitespace-nowrap flex-shrink-0 px-3.5 py-1.5 rounded-full text-xs font-bold <?= ($filters['screening_status'] === 'SIAP_DIOLAH' && empty($filters['comm_status'])) ? 'bg-emerald-600 text-white shadow-sm' : 'bg-emerald-50 text-emerald-900 border border-emerald-200 hover:bg-emerald-100' ?>">
            ✓ Lolos Screening (Siap Diolah)
        </a>
        <a href="/index.php?page=entries&screening_status=SIAP_DIOLAH&comm_status=BELUM_DIOLAH<?= (Auth::isPIC() && Auth::unit()) ? '&unit_code='.urlencode(Auth::unit()) : '' ?>" 
           class="whitespace-nowrap flex-shrink-0 px-3.5 py-1.5 rounded-full text-xs font-bold <?= ($filters['screening_status'] === 'SIAP_DIOLAH' && $filters['comm_status'] === 'BELUM_DIOLAH') ? 'bg-amber-600 text-white shadow-sm' : 'bg-amber-50 text-amber-900 border border-amber-200 hover:bg-amber-100' ?>">
            ⌛ Antrean Kurasi Komunikasi
        </a>
        <a href="/index.php?page=entries&comm_status=SUDAH_TERBIT<?= (Auth::isPIC() && Auth::unit()) ? '&unit_code='.urlencode(Auth::unit()) : '' ?>" 
           class="whitespace-nowrap flex-shrink-0 px-3.5 py-1.5 rounded-full text-xs font-bold <?= ($filters['comm_status'] === 'SUDAH_TERBIT') ? 'bg-teal-700 text-white shadow-sm' : 'bg-teal-50 text-teal-900 border border-teal-200 hover:bg-teal-100' ?>">
            🌐 Sudah Terbit Media
        </a>
        <a href="/index.php?page=entries&consent=TIDAK<?= (Auth::isPIC() && Auth::unit()) ? '&unit_code='.urlencode(Auth::unit()) : '' ?>" 
           class="whitespace-nowrap flex-shrink-0 px-3.5 py-1.5 rounded-full text-xs font-bold <?= ($filters['consent'] === 'TIDAK') ? 'bg-uhn-800 text-white shadow-sm' : 'bg-rose-50 text-uhn-900 border border-rose-200 hover:bg-rose-100' ?>">
            ⚠️ Restricted PDP
        </a>
    </div>

    <!-- Filter & Pencarian Box (Modern White Rounded-3xl Card, Compact 2-Col Grid di Mobile) -->
    <div class="bg-white rounded-3xl shadow-sm border border-rose-100 p-4 sm:p-5">
        <form action="/index.php" method="GET" class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-2.5 sm:gap-3 text-xs">
            <input type="hidden" name="page" value="entries">

            <!-- Filter Bulan -->
            <div>
                <label class="block font-bold text-slate-600 mb-1">Bulan Pelaporan</label>
                <select name="period_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-2 focus:ring-2 focus:ring-uhn-700 focus:border-uhn-700">
                    <option value="">Semua Periode</option>
                    <?php foreach (PERIODS as $pid => $pname): ?>
                        <option value="<?= $pid ?>" <?= ($filters['period_id'] === $pid) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pname) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Filter Unit -->
            <div>
                <label class="block font-bold text-slate-600 mb-1">Unit Kerja</label>
                <?php if (Auth::isPIC() && Auth::unit()): ?>
                    <input type="text" readonly value="<?= htmlspecialchars(Auth::unit()) ?>" 
                           class="w-full bg-slate-100 border border-slate-200 rounded-xl px-2.5 py-2 text-slate-500 font-bold cursor-not-allowed">
                    <input type="hidden" name="unit_code" value="<?= htmlspecialchars(Auth::unit()) ?>">
                <?php else: ?>
                    <select name="unit_code" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-2 focus:ring-2 focus:ring-uhn-700 focus:border-uhn-700">
                        <option value="">Semua Unit</option>
                        <option value="PPM" <?= ($filters['unit_code'] === 'PPM') ? 'selected' : '' ?>>PPM</option>
                        <option value="PSDM" <?= ($filters['unit_code'] === 'PSDM') ? 'selected' : '' ?>>PSDM</option>
                        <option value="KEMAHASISWAAN" <?= ($filters['unit_code'] === 'KEMAHASISWAAN') ? 'selected' : '' ?>>Kemahasiswaan</option>
                        <option value="ALUMNI_KARIR" <?= ($filters['unit_code'] === 'ALUMNI_KARIR') ? 'selected' : '' ?>>Alumni Karir</option>
                    </select>
                <?php endif; ?>
            </div>

            <!-- Filter Tier -->
            <div>
                <label class="block font-bold text-slate-600 mb-1">Status Tier</label>
                <select name="tier_type" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-2 focus:ring-2 focus:ring-uhn-700 focus:border-uhn-700">
                    <option value="">Semua Tier</option>
                    <option value="SIGNATURE" <?= ($filters['tier_type'] === 'SIGNATURE') ? 'selected' : '' ?>>Signature (Unggulan)</option>
                    <option value="REGULER" <?= ($filters['tier_type'] === 'REGULER') ? 'selected' : '' ?>>Reguler</option>
                </select>
            </div>

            <!-- Filter Status Screening -->
            <div>
                <label class="block font-bold text-slate-600 mb-1">Screening Unit</label>
                <select name="screening_status" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-2 focus:ring-2 focus:ring-uhn-700 focus:border-uhn-700">
                    <option value="">Semua Status</option>
                    <option value="DRAFT" <?= ($filters['screening_status'] === 'DRAFT') ? 'selected' : '' ?>>Draft</option>
                    <option value="SIAP_DIOLAH" <?= ($filters['screening_status'] === 'SIAP_DIOLAH') ? 'selected' : '' ?>>Siap Diolah</option>
                </select>
            </div>

            <!-- Filter Status Komunikasi -->
            <div>
                <label class="block font-bold text-slate-600 mb-1">Status Komunikasi</label>
                <select name="comm_status" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-2 focus:ring-2 focus:ring-uhn-700 focus:border-uhn-700">
                    <option value="">Semua Status</option>
                    <option value="BELUM_DIOLAH" <?= ($filters['comm_status'] === 'BELUM_DIOLAH') ? 'selected' : '' ?>>Belum Diolah</option>
                    <option value="SEDANG_DIOLAH" <?= ($filters['comm_status'] === 'SEDANG_DIOLAH') ? 'selected' : '' ?>>Sedang Diolah</option>
                    <option value="SUDAH_TERBIT" <?= ($filters['comm_status'] === 'SUDAH_TERBIT') ? 'selected' : '' ?>>Sudah Terbit</option>
                </select>
            </div>

            <!-- Filter Consent PII -->
            <div>
                <label class="block font-bold text-slate-600 mb-1">Consent Nama</label>
                <select name="consent" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-2 focus:ring-2 focus:ring-uhn-700 focus:border-uhn-700">
                    <option value="">Semua Consent</option>
                    <option value="YA" <?= ($filters['consent'] === 'YA') ? 'selected' : '' ?>>Ya (Bebas Rilis)</option>
                    <option value="TIDAK" <?= ($filters['consent'] === 'TIDAK') ? 'selected' : '' ?>>Tidak (Restricted)</option>
                </select>
            </div>

            <!-- Kata Kunci -->
            <div class="col-span-2 md:col-span-2 lg:col-span-1">
                <label class="block font-bold text-slate-600 mb-1">Kata Kunci</label>
                <div class="flex items-center gap-1.5">
                    <input type="text" name="keyword" value="<?= htmlspecialchars($filters['keyword']) ?>" placeholder="Cari judul..." 
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-uhn-700 focus:border-uhn-700">
                    <button type="submit" class="px-3.5 py-2 bg-uhn-800 text-white rounded-xl hover:bg-uhn-900 font-bold transition-colors flex-shrink-0">
                        Cari
                    </button>
                    <?php if (array_filter($filters)): ?>
                        <a href="/index.php?page=entries<?= (Auth::isPIC() && Auth::unit()) ? '&unit_code='.urlencode(Auth::unit()) : '' ?>" 
                           class="px-2.5 py-2 bg-slate-100 text-slate-600 hover:bg-slate-200 rounded-xl font-bold transition-colors text-center flex-shrink-0" title="Reset Filter">
                            ✕
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <!-- Tabel & Kartu Data Capaian (Rounded 3xl Container) -->
    <div class="bg-white rounded-3xl shadow-sm border border-rose-100 overflow-hidden">
        <div class="px-4 sm:px-6 py-4 bg-rose-50/50 border-b border-rose-100 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-600">
            <div>
                Menampilkan <strong><?= count($entries) ?></strong> data capaian institusi
            </div>
            <a href="/index.php?page=export<?= !empty($filters['period_id']) ? '&period_id=' . urlencode($filters['period_id']) : '' ?>" 
               class="text-uhn-800 hover:text-uhn-900 font-bold flex items-center gap-1 hover:underline">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Ekspor ke Excel / Borang WURI
            </a>
        </div>

        <!-- MOBILE CARD VIEW (< md) -->
        <div class="md:hidden divide-y divide-rose-100">
            <?php if (empty($entries)): ?>
                <div class="p-8 text-center text-slate-400 text-xs italic">
                    Tidak ada data yang sesuai dengan filter pencarian.
                </div>
            <?php else: ?>
                <?php foreach ($entries as $item): 
                    $canSeePrivate = Auth::isAdmin() || Auth::canManageUnit($item['unit_code']);
                ?>
                    <div class="p-4 space-y-3 hover:bg-rose-50/20 transition-colors">
                        <!-- Top Row: ID, Unit, Period, Tier -->
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="font-mono text-xs font-black text-slate-900">#<?= $item['id'] ?></span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-uhn-900 border border-rose-200">
                                    <?= htmlspecialchars($item['unit_code']) ?>
                                </span>
                                <span class="text-[11px] text-slate-500 font-medium">
                                    &bull; <?= htmlspecialchars($item['period_name'] ?? $item['period_id']) ?>
                                </span>
                            </div>
                            <div class="flex-shrink-0">
                                <?= Helpers::badgeTier($item['tier_type']) ?>
                            </div>
                        </div>

                        <!-- Title & Short Description -->
                        <div>
                            <h4 class="font-bold text-slate-900 text-sm leading-snug">
                                <a href="/index.php?page=entries_show&id=<?= $item['id'] ?>" class="hover:text-uhn-800 transition-colors">
                                    <?= htmlspecialchars($item['title']) ?>
                                </a>
                            </h4>
                            <p class="text-xs text-slate-500 mt-1 line-clamp-2">
                                <?= htmlspecialchars($item['short_description']) ?>
                            </p>
                        </div>

                        <!-- Snapshot Detail Modul -->
                        <div class="text-[11px] text-slate-600 bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                            <?php if ($item['unit_code'] === 'PPM'): ?>
                                <span>Jenis KI: <strong><?= htmlspecialchars($item['details']['ki_type'] ?? '-') ?></strong></span>
                                <span class="mx-1">&bull;</span>
                                <span>No: <strong><?= htmlspecialchars($item['details']['registration_number'] ?? '-') ?></strong></span>
                            <?php elseif ($item['unit_code'] === 'PSDM'): ?>
                                <span>Pegawai: <strong><?= Helpers::maskNameIfRestricted($item['details']['employee_name'] ?? '-', $item['name_publication_consent'], $canSeePrivate) ?></strong></span>
                                <span class="mx-1">&bull;</span>
                                <span>Tingkat: <strong><?= htmlspecialchars($item['details']['level'] ?? '-') ?></strong></span>
                            <?php elseif ($item['unit_code'] === 'KEMAHASISWAAN'): ?>
                                <span>Mahasiswa: <strong><?= Helpers::maskNameIfRestricted($item['details']['student_name'] ?? '-', $item['name_publication_consent'], $canSeePrivate) ?></strong></span>
                                <span class="mx-1">&bull;</span>
                                <span>Juara: <strong><?= htmlspecialchars($item['details']['achievement_rank'] ?? '-') ?></strong></span>
                            <?php elseif ($item['unit_code'] === 'ALUMNI_KARIR'): ?>
                                <span>Alumni: <strong><?= Helpers::maskNameIfRestricted($item['details']['alumni_name'] ?? '-', $item['name_publication_consent'], $canSeePrivate) ?></strong></span>
                                <span class="mx-1">&bull;</span>
                                <span>Perusahaan: <strong><?= htmlspecialchars($item['details']['company_or_institution'] ?? '-') ?></strong></span>
                            <?php endif; ?>
                        </div>

                        <!-- Status Badges Row -->
                        <div class="flex flex-wrap items-center gap-1.5 pt-0.5">
                            <?= Helpers::badgeScreening($item['screening_status']) ?>
                            <?= Helpers::badgeComm($item['comm_status']) ?>
                            <?= Helpers::badgeConsent($item['name_publication_consent']) ?>
                            <?php if (!empty($item['comm_published_url'])): ?>
                                <a href="<?= htmlspecialchars($item['comm_published_url']) ?>" target="_blank" 
                                   class="text-[11px] text-uhn-800 font-bold hover:underline inline-flex items-center gap-0.5 ml-auto">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    Rilis
                                </a>
                            <?php endif; ?>
                        </div>

                        <!-- Action Buttons Row -->
                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                            <a href="/index.php?page=entries_show&id=<?= $item['id'] ?>" 
                               class="flex-1 text-center py-2 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors">
                                Detail Capaian
                            </a>
                            <?php if (Auth::canManageUnit($item['unit_code'])): ?>
                                <a href="/index.php?page=entries_edit&id=<?= $item['id'] ?>" 
                                   class="py-2 px-3.5 bg-rose-50 hover:bg-rose-100 text-uhn-900 border border-rose-200/70 rounded-xl text-xs font-bold transition-colors">
                                    Edit
                                </a>
                                <form action="/index.php?action=entries_delete&id=<?= $item['id'] ?>" method="POST" class="inline" 
                                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus data capaian #<?= $item['id'] ?>?');">
                                    <button type="submit" 
                                            class="py-2 px-3.5 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200/70 rounded-xl text-xs font-bold transition-colors">
                                        Hapus
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- DESKTOP TABLE VIEW (>= md) -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-700">
                <thead class="bg-white text-slate-500 uppercase text-[11px] font-bold border-b border-rose-100">
                    <tr>
                        <th class="px-4 py-3.5">ID &amp; Unit</th>
                        <th class="px-3 py-3.5">Bulan</th>
                        <th class="px-3 py-3.5">Tier</th>
                        <th class="px-6 py-3.5">Judul Capaian &amp; Narasi</th>
                        <th class="px-4 py-3.5">Screening Unit</th>
                        <th class="px-4 py-3.5">Status Komunikasi</th>
                        <th class="px-3 py-3.5">Consent Nama</th>
                        <th class="px-4 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-rose-50">
                    <?php if (empty($entries)): ?>
                        <tr>
                            <td colspan="8" class="px-6 py-10 text-center text-slate-400 italic">
                                Tidak ada data yang sesuai dengan filter pencarian.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($entries as $item): ?>
                            <tr class="hover:bg-rose-50/30 transition-colors">
                                <td class="px-4 py-4 align-top">
                                    <div class="font-mono text-xs font-black text-slate-900">#<?= $item['id'] ?></div>
                                    <span class="inline-block mt-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-uhn-900 border border-rose-200">
                                        <?= htmlspecialchars($item['unit_code']) ?>
                                    </span>
                                </td>
                                <td class="px-3 py-4 align-top text-xs text-slate-600 whitespace-nowrap font-medium">
                                    <?= htmlspecialchars($item['period_name'] ?? $item['period_id']) ?>
                                </td>
                                <td class="px-3 py-4 align-top whitespace-nowrap">
                                    <?= Helpers::badgeTier($item['tier_type']) ?>
                                </td>
                                <td class="px-6 py-4 align-top max-w-md">
                                    <h4 class="font-bold text-slate-900 text-sm leading-snug">
                                        <a href="/index.php?page=entries_show&id=<?= $item['id'] ?>" class="hover:text-uhn-800 transition-colors">
                                            <?= htmlspecialchars($item['title']) ?>
                                        </a>
                                    </h4>
                                    <p class="text-xs text-slate-500 mt-1 line-clamp-2">
                                        <?= htmlspecialchars($item['short_description']) ?>
                                    </p>
                                    
                                    <!-- Snapshot Detail Modul -->
                                    <div class="mt-2 text-[11px] text-slate-600 bg-slate-50/70 p-2 rounded-xl border border-slate-100">
                                        <?php 
                                        $canSeePrivate = Auth::isAdmin() || Auth::canManageUnit($item['unit_code']);
                                        if ($item['unit_code'] === 'PPM'): ?>
                                            <span>Jenis KI: <strong><?= htmlspecialchars($item['details']['ki_type'] ?? '-') ?></strong></span>
                                            <span class="mx-1">&bull;</span>
                                            <span>No: <strong><?= htmlspecialchars($item['details']['registration_number'] ?? '-') ?></strong></span>
                                        <?php elseif ($item['unit_code'] === 'PSDM'): ?>
                                            <span>Pegawai: <strong><?= Helpers::maskNameIfRestricted($item['details']['employee_name'] ?? '-', $item['name_publication_consent'], $canSeePrivate) ?></strong></span>
                                            <span class="mx-1">&bull;</span>
                                            <span>Tingkat: <strong><?= htmlspecialchars($item['details']['level'] ?? '-') ?></strong></span>
                                        <?php elseif ($item['unit_code'] === 'KEMAHASISWAAN'): ?>
                                            <span>Mahasiswa: <strong><?= Helpers::maskNameIfRestricted($item['details']['student_name'] ?? '-', $item['name_publication_consent'], $canSeePrivate) ?></strong></span>
                                            <span class="mx-1">&bull;</span>
                                            <span>Juara: <strong><?= htmlspecialchars($item['details']['achievement_rank'] ?? '-') ?></strong></span>
                                        <?php elseif ($item['unit_code'] === 'ALUMNI_KARIR'): ?>
                                            <span>Alumni: <strong><?= Helpers::maskNameIfRestricted($item['details']['alumni_name'] ?? '-', $item['name_publication_consent'], $canSeePrivate) ?></strong></span>
                                            <span class="mx-1">&bull;</span>
                                            <span>Perusahaan: <strong><?= htmlspecialchars($item['details']['company_or_institution'] ?? '-') ?></strong></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-4 py-4 align-top whitespace-nowrap">
                                    <?= Helpers::badgeScreening($item['screening_status']) ?>
                                </td>
                                <td class="px-4 py-4 align-top whitespace-nowrap">
                                    <?= Helpers::badgeComm($item['comm_status']) ?>
                                    <?php if (!empty($item['comm_published_url'])): ?>
                                        <div class="mt-1">
                                            <a href="<?= htmlspecialchars($item['comm_published_url']) ?>" target="_blank" 
                                               class="text-[11px] text-uhn-800 hover:text-uhn-900 font-bold hover:underline flex items-center gap-0.5">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                Tautan Rilis
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-3 py-4 align-top whitespace-nowrap">
                                    <?= Helpers::badgeConsent($item['name_publication_consent']) ?>
                                </td>
                                <td class="px-4 py-4 align-top text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="/index.php?page=entries_show&id=<?= $item['id'] ?>" 
                                           class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors">
                                            Detail
                                        </a>
                                        <?php if (Auth::canManageUnit($item['unit_code'])): ?>
                                            <a href="/index.php?page=entries_edit&id=<?= $item['id'] ?>" 
                                               class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-uhn-900 rounded-xl text-xs font-bold transition-colors">
                                                Edit
                                            </a>
                                            <form action="/index.php?action=entries_delete&id=<?= $item['id'] ?>" method="POST" class="inline" 
                                                  onsubmit="return confirm('Apakah Anda yakin ingin menghapus data capaian #<?= $item['id'] ?>?');">
                                                <button type="submit" 
                                                        class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 rounded-xl text-xs font-bold transition-colors">
                                                    Hapus
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
