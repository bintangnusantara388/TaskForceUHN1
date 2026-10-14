<?php
// views/entries/form_alumni.php
require_once __DIR__ . '/../../app/Config.php';
require_once __DIR__ . '/../../app/Auth.php';
require_once __DIR__ . '/../../app/Helpers.php';

$isEdit = isset($entry) && $entry !== null;
$actionUrl = $isEdit ? "/index.php?action=entries_update&id={$entry['id']}" : "/index.php?action=entries_store";
$unitCode = 'ALUMNI_KARIR';
$selectedPeriod = $entry['period_id'] ?? ($_GET['period_id'] ?? '2026-10');
$tierType = $entry['tier_type'] ?? 'SIGNATURE';
$consent = $entry['name_publication_consent'] ?? 'YA';
$screeningStatus = $entry['screening_status'] ?? 'DRAFT';

$details = $entry['details'] ?? [];
$alumniName = $details['alumni_name'] ?? '';
$prodi = $details['prodi'] ?? '';
$gradYear = $details['graduation_year'] ?? 2021;
$achType = $details['achievement_type'] ?? 'PENEMPATAN_KERJA';
$company = $details['company_or_institution'] ?? '';
$position = $details['position'] ?? '';
$testimonial = $details['testimonial'] ?? '';
?>

<div class="max-w-4xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <a href="/index.php?page=entries&unit_code=ALUMNI_KARIR" class="inline-flex items-center text-xs font-bold text-slate-500 hover:text-uhn-800 transition-colors">
            &larr; Kembali ke Daftar Inventarisasi
        </a>
        <span class="px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-uhn-900 border border-rose-200">
            Modul 4: Alumni Karir (Kiprah, Wirausaha &amp; Studi Lanjut)
        </span>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-rose-100 overflow-hidden">
        <div class="px-6 py-5 bg-gradient-to-r from-uhn-950 via-uhn-900 to-uhn-800 text-white">
            <h2 class="text-lg font-bold">
                <?= $isEdit ? 'Ubah Data Kiprah Alumni' : 'Form Input Kiprah &amp; Rekam Jejak Alumni (Alumni Karir)' ?>
            </h2>
            <p class="text-xs text-rose-200 mt-0.5">
                Mendokumentasikan penempatan kerja global, pendiri usaha berdampak, dan studi lanjut di universitas papan atas dunia.
            </p>
        </div>

        <form action="<?= $actionUrl ?>" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
            <input type="hidden" name="unit_code" value="ALUMNI_KARIR">

            <!-- Section 1: Periode & Tier -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pb-6 border-b border-slate-200">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Bulan Pelaporan <span class="text-red-500">*</span>
                    </label>
                    <select name="period_id" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500 font-semibold">
                        <?php foreach (PERIODS as $pid => $pname): ?>
                            <option value="<?= $pid ?>" <?= ($selectedPeriod === $pid) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pname) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Klasifikasi Tier <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center gap-4 mt-2">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="tier_type" value="SIGNATURE" <?= ($tierType === 'SIGNATURE') ? 'checked' : '' ?> class="text-amber-600 focus:ring-amber-500">
                            <span class="text-xs font-bold text-amber-900 bg-amber-100 px-2.5 py-1 rounded-full border border-amber-300">
                                ★ SIGNATURE (Unggulan Bulanan)
                            </span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="tier_type" value="REGULER" <?= ($tierType === 'REGULER') ? 'checked' : '' ?> class="text-slate-600 focus:ring-slate-500">
                            <span class="text-xs font-medium text-slate-700 bg-slate-100 px-2 py-1 rounded-full">
                                Reguler (Tambahan)
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Section 2: Data Alumni -->
            <div class="space-y-4 pb-6 border-b border-slate-200">
                <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider text-amber-900">
                    Data Identitas Alumni
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Nama Lengkap Alumni Beserta Gelar <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="alumni_name" value="<?= htmlspecialchars($alumniName) ?>" required placeholder="Contoh: Dimas Surya Pratama, S.Kom."
                               class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Tahun Kelulusan <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="graduation_year" value="<?= htmlspecialchars((string)$gradYear) ?>" required min="2000" max="2026" placeholder="2020"
                               class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Program Studi Asal <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="prodi" value="<?= htmlspecialchars($prodi) ?>" required placeholder="Contoh: Teknik Informatika / Agribisnis / Ilmu Pemerintahan"
                           class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500">
                </div>
            </div>

            <!-- Section 3: Kiprah Karir / Bisnis / Studi -->
            <div class="space-y-4 pb-6 border-b border-slate-200">
                <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider text-amber-900">
                    Kiprah &amp; Capaian Alumni
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Jenis Capaian <span class="text-red-500">*</span>
                        </label>
                        <select name="achievement_type" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500 font-semibold">
                            <?php 
                            $achTypes = [
                                'PENEMPATAN_KERJA' => 'Penempatan Kerja (Industri / Global / BUMN)',
                                'WIRAUSAHA' => 'Wirausaha / Founder Startup Berdampak',
                                'STUDI_LANJUT' => 'Studi Lanjut (S2/S3 Dalam/Luar Negeri)',
                                'PENGHARGAAN' => 'Penghargaan / Kiprah Sosial Berdampak',
                                'LAINNYA' => 'Lainnya'
                            ];
                            foreach ($achTypes as $ak => $av): ?>
                                <option value="<?= $ak ?>" <?= ($achType === $ak) ? 'selected' : '' ?>><?= $av ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Judul / Sorotan Capaian <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="title" value="<?= htmlspecialchars($entry['title'] ?? '') ?>" required placeholder="Contoh: Alumni Berkarir sebagai Senior AI Engineer di Google Zurich"
                               class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm font-semibold focus:ring-amber-500 focus:border-amber-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Instansi / Perusahaan / Kampus Lanjutan <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="company_or_institution" value="<?= htmlspecialchars($company) ?>" required placeholder="Contoh: Google Switzerland GmbH / PT Hydro Nusantara / University of Oxford"
                               class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Posisi / Jabatan / Status Studi <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="position" value="<?= htmlspecialchars($position) ?>" required placeholder="Contoh: Senior AI Infrastructure Engineer / Founder & CEO / Awardee MPP Oxford"
                               class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500">
                    </div>
                </div>
            </div>

            <!-- Section 4: Narasi, Testimoni & Bukti -->
            <div class="space-y-4 pb-6 border-b border-slate-200">
                <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider text-amber-900">
                    Narasi, Testimoni &amp; Bukti Foto
                </h3>

                <div>
                    <?php 
                    $widgetUnitCode = 'ALUMNI_KARIR';
                    include __DIR__ . '/../components/ai_narrative_widget.php'; 
                    ?>

                    <div class="flex items-center justify-between mb-1 mt-2">
                        <label class="block text-xs font-semibold text-slate-700">
                            Deskripsi Singkat (Bahasa Awam) <span class="text-red-500">*</span>
                        </label>
                        <span id="sentenceCountBadge" class="text-xs font-bold text-slate-500">
                            0 / 3 Kalimat
                        </span>
                    </div>
                    <textarea name="short_description" id="shortDescInput" rows="3" required oninput="checkSentenceCount(this)"
                              placeholder="Maksimal 3 kalimat menceritakan keberhasilan alumni, proses seleksi, dan dampak inspiratifnya..."
                              class="w-full bg-slate-50 border border-slate-300 rounded-lg p-3 text-sm focus:ring-amber-500 focus:border-amber-500"><?= htmlspecialchars($entry['short_description'] ?? '') ?></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Kutipan Testimoni Alumni (Jika Ada)
                    </label>
                    <textarea name="testimonial" rows="2" placeholder="Contoh: 'Pondasi algoritma dan etos kerja yang ditempa dosen-dosen UHN memberi modal tangguh bagi saya untuk bersaing di panggung teknologi global.'"
                              class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2.5 text-xs italic focus:ring-amber-500 focus:border-amber-500"><?= htmlspecialchars($testimonial) ?></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Bukti Dokumen / Foto Verifikasi (PDF/Foto, Maks. 10 MB)
                        </label>
                        <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png"
                               class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 text-xs focus:ring-amber-500 focus:border-amber-500">
                        <?php if (!empty($entry['attachment_name'])): ?>
                            <div class="mt-1 text-xs text-slate-600">
                                Berkas: <strong class="text-amber-700"><?= htmlspecialchars($entry['attachment_name']) ?></strong> (<?= Helpers::formatBytes($entry['attachment_size_bytes']) ?>)
                            </div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Tautan Profil LinkedIn / Website Perusahaan
                        </label>
                        <input type="url" name="external_url" value="<?= htmlspecialchars($entry['external_url'] ?? '') ?>" placeholder="https://linkedin.com/in/..."
                               class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500">
                    </div>
                </div>
            </div>

            <!-- Section 5: Privasi & Screening -->
            <div class="space-y-4">
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                    <label class="block text-xs font-bold text-amber-900 uppercase tracking-wider mb-2">
                        Persetujuan Publikasi Nama Alumni <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center gap-6">
                        <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-800 cursor-pointer">
                            <input type="radio" name="name_publication_consent" value="YA" <?= ($consent === 'YA') ? 'checked' : '' ?> class="text-emerald-600 focus:ring-emerald-500">
                            <span>YA (Izinkan nama dicantumkan dalam artikel profil alumni &amp; promosi kampus)</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-xs font-semibold text-red-800 cursor-pointer">
                            <input type="radio" name="name_publication_consent" value="TIDAK" <?= ($consent === 'TIDAK') ? 'checked' : '' ?> class="text-red-600 focus:ring-red-500">
                            <span>TIDAK (Gunakan anonim/hanya inisial)</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Internal</label>
                    <input type="text" name="notes" value="<?= htmlspecialchars($entry['notes'] ?? '') ?>" placeholder="Misal: Prioritas borang WURI Industrial Application..."
                           class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500">
                </div>

                <div class="pt-2">
                    <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">
                        Status Screening Unit <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center gap-6 bg-slate-50 p-3 rounded-lg border border-slate-200">
                        <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer">
                            <input type="radio" name="screening_status" value="DRAFT" <?= ($screeningStatus === 'DRAFT') ? 'checked' : '' ?>>
                            <span><strong>Simpan DRAFT</strong> (Masih ditinjau di internal Alumni Karir)</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-xs font-bold text-emerald-800 cursor-pointer">
                            <input type="radio" name="screening_status" value="SIAP_DIOLAH" <?= ($screeningStatus === 'SIAP_DIOLAH') ? 'checked' : '' ?>>
                            <span><strong>SIAP DIOLAH</strong> (Kirim ke antrian Bagian Komunikasi)</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Tombol Aksi -->
            <div class="pt-6 border-t border-rose-100 flex items-center justify-between">
                <a href="/index.php?page=entries&unit_code=ALUMNI_KARIR" class="px-4 py-2.5 border border-slate-300 text-slate-600 hover:bg-slate-100 rounded-xl text-xs font-bold transition-colors">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-uhn-800 to-uhn-700 hover:from-uhn-900 hover:to-uhn-800 text-white rounded-xl text-xs font-black shadow-md shadow-rose-950/20 transition-all">
                    <?= $isEdit ? 'Simpan Perubahan Kiprah Alumni' : 'Simpan Kiprah Alumni' ?>
                </button>
            </div>

        </form>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    checkSentenceCount(document.getElementById('shortDescInput'));
});
</script>
