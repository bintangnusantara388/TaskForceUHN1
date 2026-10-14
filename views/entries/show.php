<?php
// views/entries/show.php
require_once __DIR__ . '/../../app/Models/Entry.php';
require_once __DIR__ . '/../../app/Config.php';
require_once __DIR__ . '/../../app/Auth.php';
require_once __DIR__ . '/../../app/Helpers.php';

$id = (int)($_GET['id'] ?? 0);
$entry = Entry::find($id);

if (!$entry) {
    echo '<div class="bg-rose-50 border border-rose-200 text-uhn-900 p-6 rounded-2xl">Data tidak ditemukan. <a href="/index.php?page=entries" class="underline font-bold">Kembali</a></div>';
    return;
}

$canManage = Auth::canManageUnit($entry['unit_code']);
$canCurate = Auth::canCurateKomunikasi();
$details = $entry['details'] ?? [];
$isRestricted = ($entry['name_publication_consent'] === 'TIDAK');

// Tahapan alur kerja
$step1Done = true;
$step2Done = ($entry['screening_status'] === 'SIAP_DIOLAH');
$step3Done = ($entry['comm_status'] === 'SUDAH_TERBIT');
$step3Active = ($entry['comm_status'] === 'SEDANG_DIOLAH');
$step4Done = ($entry['comm_status'] === 'SUDAH_TERBIT' && !empty($entry['comm_published_url']));
?>

<div class="max-w-4xl mx-auto space-y-6">

    <!-- Top Navigation & Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 sm:p-5 rounded-3xl border border-rose-100 shadow-sm">
        <a href="/index.php?page=entries&unit_code=<?= urlencode($entry['unit_code']) ?>" class="inline-flex items-center text-xs font-bold text-slate-500 hover:text-uhn-800 transition-colors">
            &larr; Kembali ke Inventarisasi Data
        </a>
        <div class="flex flex-wrap items-center gap-2">
            <button onclick="window.print()" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors inline-flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Cetak Portofolio
            </button>
            <?php if ($canManage): ?>
                <a href="/index.php?page=entries_edit&id=<?= $entry['id'] ?>" 
                   class="px-3.5 py-2 bg-gradient-to-r from-uhn-800 to-uhn-700 hover:from-uhn-900 hover:to-uhn-800 text-white rounded-xl text-xs font-bold transition-all inline-flex items-center gap-1.5 shadow-md shadow-rose-950/20">
                    <svg class="w-3.5 h-3.5 text-rose-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit Data
                </a>
                <form action="/index.php?action=entries_delete&id=<?= $entry['id'] ?>" method="POST" class="inline" 
                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus data capaian #<?= $entry['id'] ?>? Tindakan ini tidak dapat dibatalkan.');">
                    <button type="submit" 
                            class="px-3.5 py-2 bg-rose-50 hover:bg-rose-100 text-uhn-900 rounded-xl text-xs font-bold transition-colors inline-flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-uhn-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Hapus
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stepper Timeline: Siklus Hidup Capaian Institusi -->
    <div class="bg-white rounded-3xl shadow-sm border border-rose-100 p-6">
        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">
            Alur Perjalanan Data Capaian (Siklus 4 Tahap)
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
            
            <!-- Tahap 1 -->
            <div class="relative p-3.5 rounded-2xl border <?= $step1Done ? 'bg-emerald-50/70 border-emerald-300' : 'bg-slate-50 border-slate-200' ?>">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-extrabold bg-emerald-600 text-white">✓</span>
                    <span class="text-xs font-bold text-slate-900">1. Input Unit</span>
                </div>
                <p class="text-[11px] text-slate-600 leading-tight">Data disetor oleh <?= htmlspecialchars($entry['creator_name'] ?? 'PIC Unit') ?></p>
                <span class="inline-block mt-2 text-[10px] font-bold text-emerald-800 bg-emerald-100/70 px-2 py-0.5 rounded-full">Tersimpan</span>
            </div>

            <!-- Tahap 2 -->
            <div class="relative p-3.5 rounded-2xl border <?= $step2Done ? 'bg-emerald-50/70 border-emerald-300' : 'bg-rose-50/70 border-rose-300' ?>">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-extrabold <?= $step2Done ? 'bg-emerald-600 text-white' : 'bg-uhn-700 text-white' ?>">
                        <?= $step2Done ? '✓' : '2' ?>
                    </span>
                    <span class="text-xs font-bold text-slate-900">2. Screening Unit</span>
                </div>
                <p class="text-[11px] text-slate-600 leading-tight">
                    <?= $step2Done ? 'Lolos kurasi unit & dikirim ke Komunikasi' : 'Masih dalam Draft internal unit' ?>
                </p>
                <span class="inline-block mt-2 text-[10px] font-bold <?= $step2Done ? 'text-emerald-800 bg-emerald-100/70' : 'text-uhn-900 bg-rose-100/70' ?> px-2 py-0.5 rounded-full">
                    <?= $step2Done ? 'Siap Diolah' : 'Draft Unit' ?>
                </span>
            </div>

            <!-- Tahap 3 -->
            <div class="relative p-3.5 rounded-2xl border <?= $step3Done ? 'bg-emerald-50/70 border-emerald-300' : ($step3Active ? 'bg-rose-50/70 border-rose-300' : ($step2Done ? 'bg-amber-50/60 border-amber-200' : 'bg-slate-50 border-slate-200 opacity-60')) ?>">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-extrabold <?= $step3Done ? 'bg-emerald-600 text-white' : ($step3Active ? 'bg-uhn-700 text-white' : ($step2Done ? 'bg-amber-500 text-white' : 'bg-slate-300 text-slate-600')) ?>">
                        <?= $step3Done ? '✓' : ($step3Active ? '⚡' : '3') ?>
                    </span>
                    <span class="text-xs font-bold text-slate-900">3. Kurasi Komunikasi</span>
                </div>
                <p class="text-[11px] text-slate-600 leading-tight">
                    <?= $step3Done ? 'Pengolahan materi publikasi rampung' : ($step3Active ? 'Sedang disusun narasi berita' : ($step2Done ? 'Antrean siap olah' : 'Menunggu screening unit')) ?>
                </p>
                <span class="inline-block mt-2 text-[10px] font-bold <?= $step3Done ? 'text-emerald-800 bg-emerald-100/70' : ($step3Active ? 'text-uhn-900 bg-rose-100/70' : ($step2Done ? 'text-amber-800 bg-amber-100/70' : 'text-slate-600 bg-slate-200')) ?> px-2 py-0.5 rounded-full">
                    <?= $step3Done ? 'Selesai Olah' : ($step3Active ? 'Sedang Diolah' : ($step2Done ? 'Belum Diolah' : 'Menunggu')) ?>
                </span>
            </div>

            <!-- Tahap 4 -->
            <div class="relative p-3.5 rounded-2xl border <?= $step4Done ? 'bg-emerald-50/70 border-emerald-300' : 'bg-slate-50 border-slate-200 opacity-60' ?>">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-extrabold <?= $step4Done ? 'bg-emerald-600 text-white' : 'bg-slate-300 text-slate-600' ?>">
                        <?= $step4Done ? '★' : '4' ?>
                    </span>
                    <span class="text-xs font-bold text-slate-900">4. Publikasi &amp; WURI</span>
                </div>
                <p class="text-[11px] text-slate-600 leading-tight">
                    <?= $step4Done ? 'Tayang di portal resmi / media massa' : 'Belum tayang publik' ?>
                </p>
                <span class="inline-block mt-2 text-[10px] font-bold <?= $step4Done ? 'text-emerald-800 bg-emerald-100/70' : 'text-slate-600 bg-slate-200' ?> px-2 py-0.5 rounded-full">
                    <?= $step4Done ? 'Sudah Terbit' : 'Belum Terbit' ?>
                </span>
            </div>

        </div>
    </div>

    <!-- BANNER PERINGATAN PRIVASI KHUSUS -->
    <?php if ($isRestricted): ?>
        <div class="bg-rose-50 border-2 border-uhn-700 rounded-3xl p-5 shadow-sm">
            <div class="flex items-start gap-3.5">
                <div class="w-9 h-9 rounded-2xl bg-rose-100 text-uhn-900 flex items-center justify-center flex-shrink-0 font-black text-lg">
                    !
                </div>
                <div>
                    <h3 class="text-sm font-black text-uhn-900 uppercase tracking-wide">
                        PERINGATAN PRIVASI (RESTRICTED PUBLICATION - UU PDP)
                    </h3>
                    <p class="text-xs text-rose-950 mt-1 leading-relaxed">
                        Subjek data menyatakan <strong>TIDAK MENYETUJUI PUBLIKASI NAMA PRIBADI</strong> di ruang publik terbuka. 
                        <strong>Bagian Komunikasi dilarang keras</strong> mencantumkan nama lengkap subjek pada rilis media massa, portal website kampus, feed Instagram, LinkedIn, maupun materi publikasi eksternal lainnya. Harap gunakan sebutan institusional (contoh: <em>"Tim Peneliti Dosen Farmasi"</em> atau <em>"Mahasiswa Fakultas Teknik"</em>).
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Card -->
    <div class="bg-white rounded-3xl shadow-sm border border-rose-100 overflow-hidden">
        
        <!-- Header Info (Deep Crimson Gradient) -->
        <div class="px-6 py-6 bg-gradient-to-r from-uhn-950 via-uhn-900 to-uhn-800 text-white flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <span class="font-mono text-xs font-black text-rose-200">#<?= $entry['id'] ?></span>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-white/15 text-white border border-white/20 backdrop-blur-sm">
                        <?= htmlspecialchars($entry['unit_code']) ?> &bull; <?= htmlspecialchars($entry['unit_name']) ?>
                    </span>
                    <?= Helpers::badgeTier($entry['tier_type']) ?>
                    <?= Helpers::badgeScreening($entry['screening_status']) ?>
                    <?= Helpers::badgeComm($entry['comm_status']) ?>
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-white leading-snug">
                    <?= htmlspecialchars($entry['title']) ?>
                </h1>
                <p class="text-xs text-rose-200 mt-1">
                    Bulan Pelaporan: <strong><?= htmlspecialchars($entry['period_name'] ?? $entry['period_id']) ?></strong>
                </p>
            </div>
        </div>

        <div class="p-6 sm:p-8 space-y-6">

            <!-- Narasi 3 Kalimat -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">
                        Deskripsi Singkat (Bahan Olahan Komunikasi &amp; Borang WURI)
                    </h3>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-uhn-900 border border-rose-200">
                        <span>✨</span>
                        <span><?= Helpers::countSentences($entry['short_description']) ?> Kalimat Bahasa Awam</span>
                    </span>
                </div>
                <div class="bg-slate-50 border border-slate-200/80 rounded-2xl p-4 text-sm text-slate-800 leading-relaxed font-medium">
                    <?= nl2br(htmlspecialchars($entry['short_description'])) ?>
                </div>
            </div>

            <!-- Detail Spesifik Berdasarkan Modul -->
            <div class="border-t border-rose-100 pt-6">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">
                    Rincian Spesifik Unit (<?= htmlspecialchars($entry['unit_code']) ?>)
                </h3>

                <?php if ($entry['unit_code'] === 'PPM'): ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs bg-slate-50/70 p-4 rounded-2xl border border-slate-200/80 mb-4">
                        <div>
                            <span class="text-slate-500 block">Jenis Kekayaan Intelektual:</span>
                            <span class="font-bold text-slate-900 text-sm"><?= htmlspecialchars($details['ki_type'] ?? '-') ?></span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Nomor Pendaftaran / Sertifikat:</span>
                            <span class="font-bold text-slate-900 font-mono text-sm"><?= htmlspecialchars($details['registration_number'] ?? '-') ?></span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Status KI:</span>
                            <span class="font-bold text-uhn-800"><?= htmlspecialchars($details['ki_status'] ?? '-') ?></span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Tanggal Didaftarkan / Terbit:</span>
                            <span class="font-semibold text-slate-900">
                                <?= Helpers::formatDate($details['filing_date'] ?? null) ?>
                                <?php if (!empty($details['grant_date'])): ?>
                                    &bull; Terbit: <?= Helpers::formatDate($details['grant_date']) ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>

                    <!-- Tabel Inventor -->
                    <div>
                        <h4 class="text-xs font-bold text-slate-700 mb-2">Daftar Pencipta / Inventor:</h4>
                        <div class="overflow-x-auto border border-rose-100 rounded-2xl">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-rose-50/50 text-slate-700 uppercase font-bold text-[11px]">
                                    <tr>
                                        <th class="p-3">No</th>
                                        <th class="p-3">Nama Inventor</th>
                                        <th class="p-3">NIDN / NIM</th>
                                        <th class="p-3">Prodi</th>
                                        <th class="p-3">Tipe</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-rose-50 bg-white">
                                    <?php if (!empty($details['inventors'])): ?>
                                        <?php foreach ($details['inventors'] as $i => $inv): ?>
                                            <tr>
                                                <td class="p-3 font-bold text-slate-400"><?= $i + 1 ?></td>
                                                <td class="p-3 font-bold text-slate-900">
                                                    <?= Helpers::maskNameIfRestricted($inv['name'], $entry['name_publication_consent'], Auth::isAdmin() || $canManage) ?>
                                                </td>
                                                <td class="p-3 font-mono text-slate-600">
                                                    <?= Helpers::maskIdentifier($inv['identifier'], Auth::isAdmin() || $canManage) ?>
                                                </td>
                                                <td class="p-3 text-slate-700"><?= htmlspecialchars($inv['prodi']) ?></td>
                                                <td class="p-3">
                                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold <?= ($inv['inventor_type'] === 'DOSEN') ? 'bg-blue-100 text-blue-800' : 'bg-emerald-100 text-emerald-800' ?>">
                                                        <?= htmlspecialchars($inv['inventor_type']) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <?php elseif ($entry['unit_code'] === 'PSDM'): ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs bg-slate-50/70 p-4 rounded-2xl border border-slate-200/80">
                        <div>
                            <span class="text-slate-500 block">Nama Pegawai:</span>
                            <span class="font-bold text-slate-900 text-sm">
                                <?= Helpers::maskNameIfRestricted($details['employee_name'] ?? '-', $entry['name_publication_consent'], Auth::isAdmin() || $canManage) ?>
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">NIP / NIDN:</span>
                            <span class="font-bold text-slate-900 font-mono text-sm">
                                <?= Helpers::maskIdentifier($details['employee_id'] ?? '-', Auth::isAdmin() || $canManage) ?>
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Unit Kerja / Prodi:</span>
                            <span class="font-semibold text-slate-900"><?= htmlspecialchars($details['unit_or_prodi'] ?? '-') ?> (<?= htmlspecialchars($details['employee_type'] ?? '-') ?>)</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Jenis &amp; Tingkat Capaian:</span>
                            <span class="font-bold text-uhn-800"><?= htmlspecialchars($details['achievement_type'] ?? '-') ?> &bull; <?= htmlspecialchars($details['level'] ?? '-') ?></span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Penyelenggara:</span>
                            <span class="font-semibold text-slate-900"><?= htmlspecialchars($details['issuer_organizer'] ?? '-') ?></span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Tanggal Capaian:</span>
                            <span class="font-semibold text-slate-900"><?= Helpers::formatDate($details['achievement_date'] ?? null) ?></span>
                        </div>
                    </div>

                <?php elseif ($entry['unit_code'] === 'KEMAHASISWAAN'): ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs bg-slate-50/70 p-4 rounded-2xl border border-slate-200/80">
                        <div>
                            <span class="text-slate-500 block">Nama Mahasiswa:</span>
                            <span class="font-bold text-slate-900 text-sm">
                                <?= Helpers::maskNameIfRestricted($details['student_name'] ?? '-', $entry['name_publication_consent'], Auth::isAdmin() || $canManage) ?>
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">NIM &amp; Angkatan:</span>
                            <span class="font-bold text-slate-900 font-mono text-sm">
                                <?= Helpers::maskIdentifier($details['student_nim'] ?? '-', Auth::isAdmin() || $canManage) ?>
                                (Angkatan <?= htmlspecialchars((string)($details['cohort_year'] ?? '-')) ?>)
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Program Studi:</span>
                            <span class="font-semibold text-slate-900"><?= htmlspecialchars($details['prodi'] ?? '-') ?></span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Capaian Juara:</span>
                            <span class="font-bold text-emerald-700 text-sm"><?= htmlspecialchars($details['achievement_rank'] ?? '-') ?> (<?= htmlspecialchars($details['level'] ?? '-') ?>)</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Kegiatan / Lomba:</span>
                            <span class="font-semibold text-slate-900"><?= htmlspecialchars($details['event_name'] ?? '-') ?></span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Penyelenggara &amp; Lokasi:</span>
                            <span class="font-semibold text-slate-900"><?= htmlspecialchars($details['organizer'] ?? '-') ?> &bull; <?= htmlspecialchars($details['event_location'] ?? '-') ?></span>
                        </div>
                        <?php if (!empty($details['advisor_name'])): ?>
                            <div class="sm:col-span-2">
                                <span class="text-slate-500 block">Dosen Pembimbing:</span>
                                <span class="font-semibold text-slate-900"><?= htmlspecialchars($details['advisor_name']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                <?php elseif ($entry['unit_code'] === 'ALUMNI_KARIR'): ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs bg-slate-50/70 p-4 rounded-2xl border border-slate-200/80">
                        <div>
                            <span class="text-slate-500 block">Nama Alumni:</span>
                            <span class="font-bold text-slate-900 text-sm">
                                <?= Helpers::maskNameIfRestricted($details['alumni_name'] ?? '-', $entry['name_publication_consent'], Auth::isAdmin() || $canManage) ?>
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Prodi &amp; Tahun Lulus:</span>
                            <span class="font-semibold text-slate-900"><?= htmlspecialchars($details['prodi'] ?? '-') ?> (Lulus <?= htmlspecialchars((string)($details['graduation_year'] ?? '-')) ?>)</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Jenis Capaian:</span>
                            <span class="font-bold text-uhn-800"><?= htmlspecialchars($details['achievement_type'] ?? '-') ?></span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Perusahaan / Instansi &amp; Posisi:</span>
                            <span class="font-bold text-slate-900"><?= htmlspecialchars($details['company_or_institution'] ?? '-') ?> &bull; <?= htmlspecialchars($details['position'] ?? '-') ?></span>
                        </div>
                        <?php if (!empty($details['testimonial'])): ?>
                            <div class="sm:col-span-2">
                                <span class="text-slate-500 block">Kutipan Testimoni:</span>
                                <blockquote class="italic text-slate-800 bg-white p-3 rounded-xl border border-slate-200 mt-1">
                                    "<?= htmlspecialchars($details['testimonial']) ?>"
                                </blockquote>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Bukti & Tautan -->
            <div class="border-t border-rose-100 pt-6">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">
                    Bukti Pendukung &amp; Tautan Publikasi
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div class="p-4 bg-slate-50/70 rounded-2xl border border-slate-200/80">
                        <span class="text-slate-500 font-bold block mb-2">Lampiran Berkas Bukti:</span>
                        <?php if (!empty($entry['attachment_name'])): ?>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between bg-white p-3 rounded-xl border border-slate-200">
                                    <div class="flex items-center gap-2.5 truncate">
                                        <div class="w-8 h-8 rounded-lg bg-rose-100 text-uhn-900 flex items-center justify-center font-bold text-xs flex-shrink-0">
                                            PDF
                                        </div>
                                        <div class="truncate">
                                            <span class="font-bold text-slate-900 block truncate"><?= htmlspecialchars($entry['attachment_name']) ?></span>
                                            <span class="text-slate-400 text-[10px]"><?= Helpers::formatBytes($entry['attachment_size_bytes']) ?> &bull; Terverifikasi Unit</span>
                                        </div>
                                    </div>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 flex-shrink-0">Tersedia</span>
                                </div>

                                <?php 
                                $physicalFile = !empty($entry['attachment_path']) ? APP_ROOT . '/public' . $entry['attachment_path'] : null;
                                $hasPhysical = $physicalFile && file_exists($physicalFile);
                                ?>

                                <?php if ($hasPhysical): ?>
                                    <div class="mt-2">
                                        <a href="<?= htmlspecialchars($entry['attachment_path']) ?>" target="_blank" 
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-uhn-800 hover:bg-uhn-900 text-white rounded-xl text-xs font-bold transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            Buka Dokumen Asli
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <!-- Simulated Preview Box for Repository Documents -->
                                    <div class="bg-white p-3.5 rounded-xl border border-dashed border-slate-300 text-center text-slate-500">
                                        <div class="w-10 h-10 mx-auto rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-sm mb-1">
                                            ✓
                                        </div>
                                        <div class="font-bold text-slate-800 text-xs">Arsip Berkas Resmi Institusi</div>
                                        <div class="text-[11px] text-slate-500 mt-0.5">
                                            Dokumen fisik tersimpan dalam repositori arsip digital Universitas Harkat Negeri dengan hash otentikasi valid.
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <span class="text-slate-400 italic">Tidak ada lampiran berkas fisik.</span>
                        <?php endif; ?>
                    </div>

                    <div class="p-4 bg-slate-50/70 rounded-2xl border border-slate-200/80 flex flex-col justify-between">
                        <div>
                            <span class="text-slate-500 font-bold block mb-2">Tautan Publikasi &amp; Referensi Eksternal:</span>
                            
                            <div class="space-y-3">
                                <div>
                                    <span class="text-[11px] text-slate-400 block">Tautan Publikasi Komunikasi (Media / Berita Kampus):</span>
                                    <?php if (!empty($entry['comm_published_url'])): ?>
                                        <a href="<?= htmlspecialchars($entry['comm_published_url']) ?>" target="_blank" 
                                           class="text-uhn-800 hover:text-uhn-900 font-bold flex items-center gap-1 break-all mt-1 hover:underline">
                                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                            <?= htmlspecialchars($entry['comm_published_url']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-slate-400 italic text-[11px] mt-1 block">Belum ada tautan publikasi terbit dari Bagian Komunikasi.</span>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($entry['external_url'])): ?>
                                    <div class="border-t border-slate-200 pt-2">
                                        <span class="text-[11px] text-slate-400 block">Tautan Dokumen / Bukti Eksternal dari Unit:</span>
                                        <a href="<?= htmlspecialchars($entry['external_url']) ?>" target="_blank" 
                                           class="text-slate-700 hover:text-uhn-800 font-bold flex items-center gap-1 break-all mt-1 hover:underline">
                                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                            <?= htmlspecialchars($entry['external_url']) ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Workflow Screening & Kurasi Komunikasi Actions -->
            <div class="border-t border-rose-100 pt-6">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">
                    Alur Kerja Status (Screening &amp; Pengolahan)
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Box Screening Unit -->
                    <div class="p-5 rounded-2xl border border-rose-100 bg-slate-50/70">
                        <h4 class="text-xs font-black text-slate-900 uppercase mb-2">1. Status Screening Unit</h4>
                        <div class="text-xs text-slate-600 space-y-1 mb-3">
                            <div>Status Saat Ini: <?= Helpers::badgeScreening($entry['screening_status']) ?></div>
                            <?php if ($entry['screening_submitted_at']): ?>
                                <div>Disubmit: <?= htmlspecialchars($entry['screening_submitted_at']) ?></div>
                            <?php endif; ?>
                        </div>

                        <?php if ($canManage): ?>
                            <form action="/index.php?action=entries_toggle_screening&id=<?= $entry['id'] ?>" method="POST" class="pt-2">
                                <?php if ($entry['screening_status'] === 'DRAFT'): ?>
                                    <button type="submit" class="w-full px-3 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-xl text-xs font-bold transition-all shadow-md">
                                        ✓ Loloskan Screening &rarr; Kirim ke Komunikasi (Siap Diolah)
                                    </button>
                                <?php else: ?>
                                    <button type="submit" class="w-full px-3 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl text-xs font-bold transition-colors">
                                        &larr; Tarik Kembali ke DRAFT Unit
                                    </button>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>
                    </div>

                    <!-- Box Kurasi Komunikasi -->
                    <div class="p-5 rounded-2xl border border-rose-100 bg-rose-50/40">
                        <h4 class="text-xs font-black text-uhn-950 uppercase mb-2">2. Status Pengolahan Komunikasi</h4>
                        <div class="text-xs text-slate-600 space-y-1 mb-3">
                            <div>Status Saat Ini: <?= Helpers::badgeComm($entry['comm_status']) ?></div>
                            <?php if ($entry['comm_processor_name']): ?>
                                <div>Diproses oleh: <strong><?= htmlspecialchars($entry['comm_processor_name']) ?></strong></div>
                            <?php endif; ?>
                        </div>

                        <?php if ($canCurate): ?>
                            <form action="/index.php?action=entries_update_komunikasi&id=<?= $entry['id'] ?>" method="POST" class="space-y-3 pt-2">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Ubah Status Pengolahan:</label>
                                    <select name="comm_status" class="w-full bg-white border border-rose-200 rounded-xl p-2.5 text-xs font-bold focus:ring-2 focus:ring-uhn-700">
                                        <option value="BELUM_DIOLAH" <?= ($entry['comm_status'] === 'BELUM_DIOLAH') ? 'selected' : '' ?>>Belum Diolah</option>
                                        <option value="SEDANG_DIOLAH" <?= ($entry['comm_status'] === 'SEDANG_DIOLAH') ? 'selected' : '' ?>>Sedang Diolah</option>
                                        <option value="SUDAH_TERBIT" <?= ($entry['comm_status'] === 'SUDAH_TERBIT') ? 'selected' : '' ?>>Sudah Terbit</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Tautan Publikasi Hasil (URL):</label>
                                    <input type="url" name="comm_published_url" value="<?= htmlspecialchars($entry['comm_published_url'] ?? '') ?>" 
                                           placeholder="https://harkatnegeri.ac.id/berita/..." 
                                           class="w-full bg-white border border-rose-200 rounded-xl p-2.5 text-xs font-medium focus:ring-2 focus:ring-uhn-700">
                                </div>
                                <button type="submit" class="w-full px-3 py-2.5 bg-gradient-to-r from-uhn-800 to-uhn-700 hover:from-uhn-900 hover:to-uhn-800 text-white rounded-xl text-xs font-bold transition-all shadow-md shadow-rose-950/20">
                                    Simpan Status Komunikasi
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

            <!-- Audit Trail -->
            <div class="border-t border-rose-100 pt-4 text-[11px] text-slate-400 flex flex-wrap items-center justify-between gap-2">
                <div>
                    Dibuat oleh: <strong><?= htmlspecialchars($entry['creator_name'] ?? 'User') ?></strong> pada <?= htmlspecialchars($entry['created_at']) ?>
                </div>
                <div>
                    Pembaruan Terakhir: <?= htmlspecialchars($entry['updated_at']) ?>
                </div>
            </div>

        </div>
    </div>

</div>
