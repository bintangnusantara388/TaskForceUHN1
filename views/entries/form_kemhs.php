<?php
// views/entries/form_kemhs.php
require_once __DIR__ . '/../../app/Config.php';
require_once __DIR__ . '/../../app/Auth.php';
require_once __DIR__ . '/../../app/Helpers.php';

$isEdit = isset($entry) && $entry !== null;
$actionUrl = $isEdit ? "/index.php?action=entries_update&id={$entry['id']}" : "/index.php?action=entries_store";
$unitCode = 'KEMAHASISWAAN';
$selectedPeriod = $entry['period_id'] ?? ($_GET['period_id'] ?? '2026-10');
$tierType = $entry['tier_type'] ?? 'SIGNATURE';
$consent = $entry['name_publication_consent'] ?? 'YA';
$screeningStatus = $entry['screening_status'] ?? 'DRAFT';

$details = $entry['details'] ?? [];
$studentName = $details['student_name'] ?? '';
$studentNim = $details['student_nim'] ?? '';
$prodi = $details['prodi'] ?? '';
$cohortYear = $details['cohort_year'] ?? 2023;
$eventName = $details['event_name'] ?? '';
$organizer = $details['organizer'] ?? '';
$eventLocation = $details['event_location'] ?? '';
$eventDate = $details['event_date'] ?? date('Y-m-d');
$rank = $details['achievement_rank'] ?? 'JUARA_1';
$level = $details['level'] ?? 'NASIONAL';
$category = $details['category'] ?? 'AKADEMIK';
$advisor = $details['advisor_name'] ?? '';
?>

<div class="max-w-4xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <a href="/index.php?page=entries&unit_code=KEMAHASISWAAN" class="inline-flex items-center text-xs font-bold text-slate-500 hover:text-uhn-800 transition-colors">
            &larr; Kembali ke Daftar Inventarisasi
        </a>
        <span class="px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-uhn-900 border border-rose-200">
            Modul 3: Kemahasiswaan (Prestasi Lomba &amp; Kejuaraan)
        </span>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-rose-100 overflow-hidden">
        <div class="px-6 py-5 bg-gradient-to-r from-uhn-950 via-uhn-900 to-uhn-800 text-white">
            <h2 class="text-lg font-bold">
                <?= $isEdit ? 'Ubah Data Prestasi Mahasiswa' : 'Form Input Prestasi Mahasiswa (Kemahasiswaan)' ?>
            </h2>
            <p class="text-xs text-rose-200 mt-0.5">
                Pengumpulan data juara lomba akademik dan non-akademik bereputasi untuk eviden borang WURI Student Mobility dan publikasi.
            </p>
        </div>

        <form action="<?= $actionUrl ?>" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
            <input type="hidden" name="unit_code" value="KEMAHASISWAAN">

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

            <!-- Section 2: Data Mahasiswa -->
            <div class="space-y-4 pb-6 border-b border-slate-200">
                <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider text-emerald-900">
                    Data Mahasiswa
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Nama Mahasiswa <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="student_name" value="<?= htmlspecialchars($studentName) ?>" required placeholder="Contoh: Andi Pratama Kusuma"
                               class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            NIM (Hanya Angka) <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="student_nim" value="<?= htmlspecialchars($studentNim) ?>" required pattern="[0-9]+" title="Hanya angka diperbolehkan" placeholder="Contoh: 230401088"
                               class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm font-mono focus:ring-amber-500 focus:border-amber-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Program Studi <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="prodi" value="<?= htmlspecialchars($prodi) ?>" required placeholder="Contoh: Teknik Lingkungan"
                               class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Tahun Angkatan <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="cohort_year" value="<?= htmlspecialchars((string)$cohortYear) ?>" required min="2018" max="2027" placeholder="2023"
                               class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500">
                    </div>
                </div>
            </div>

            <!-- Section 3: Data Kejuaraan / Lomba -->
            <div class="space-y-4 pb-6 border-b border-slate-200">
                <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider text-emerald-900">
                    Detail Kompetisi &amp; Capaian Juara
                </h3>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Nama Kegiatan / Kompetisi / Lomba <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="event_name" value="<?= htmlspecialchars($eventName) ?>" required placeholder="Contoh: World Youth Invention and Innovation Award (WYIIA) 2026"
                           class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm font-semibold focus:ring-amber-500 focus:border-amber-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Penyelenggara <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="organizer" value="<?= htmlspecialchars($organizer) ?>" required placeholder="Contoh: IYSA & Universiti Malaya"
                               class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Lokasi Kegiatan <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="event_location" value="<?= htmlspecialchars($eventLocation) ?>" required placeholder="Contoh: Kuala Lumpur, Malaysia"
                               class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Jenis Capaian Juara <span class="text-red-500">*</span>
                        </label>
                        <select name="achievement_rank" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500 font-semibold">
                            <?php 
                            $ranks = [
                                'JUARA_1' => 'Juara 1', 'JUARA_2' => 'Juara 2', 'JUARA_3' => 'Juara 3',
                                'HARAPAN' => 'Juara Harapan', 'FINALIS' => 'Finalis',
                                'MEDALI_EMAS' => 'Medali Emas', 'MEDALI_PERAK' => 'Medali Perak', 'MEDALI_PERUNGGU' => 'Medali Perunggu',
                                'LAINNYA' => 'Lainnya'
                            ];
                            foreach ($ranks as $rk => $rv): ?>
                                <option value="<?= $rk ?>" <?= ($rank === $rk) ? 'selected' : '' ?>><?= $rv ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Tingkat <span class="text-red-500">*</span>
                        </label>
                        <select name="level" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500 font-semibold">
                            <option value="INTERNASIONAL" <?= ($level === 'INTERNASIONAL') ? 'selected' : '' ?>>Internasional</option>
                            <option value="NASIONAL" <?= ($level === 'NASIONAL') ? 'selected' : '' ?>>Nasional</option>
                            <option value="PROVINSI" <?= ($level === 'PROVINSI') ? 'selected' : '' ?>>Provinsi</option>
                            <option value="KABUPATEN_KOTA" <?= ($level === 'KABUPATEN_KOTA') ? 'selected' : '' ?>>Kabupaten / Kota</option>
                            <option value="KAMPUS" <?= ($level === 'KAMPUS') ? 'selected' : '' ?>>Internal Kampus</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Kategori <span class="text-red-500">*</span>
                        </label>
                        <select name="category" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500">
                            <option value="AKADEMIK" <?= ($category === 'AKADEMIK') ? 'selected' : '' ?>>Akademik (KIR/Inovasi/Debat)</option>
                            <option value="NON_AKADEMIK" <?= ($category === 'NON_AKADEMIK') ? 'selected' : '' ?>>Non-Akademik (Olahraga/Seni)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Tanggal Kegiatan <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="event_date" value="<?= htmlspecialchars($eventDate) ?>" required
                               class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Judul Singkat Entri <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="title" value="<?= htmlspecialchars($entry['title'] ?? '') ?>" required placeholder="Contoh: Juara 1 & Gold Medal WYIIA 2026 Malaysia"
                               class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm font-semibold focus:ring-amber-500 focus:border-amber-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Dosen Pembimbing (Jika Ada)
                        </label>
                        <input type="text" name="advisor_name" value="<?= htmlspecialchars($advisor) ?>" placeholder="Contoh: Dr. Ir. Kusuma Wardhana, M.T."
                               class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500">
                    </div>
                </div>
            </div>

            <!-- Section 4: Narasi, Bukti & Privasi -->
            <div class="space-y-4 pb-6 border-b border-slate-200">
                <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider text-emerald-900">
                    Narasi &amp; Bukti Sertifikat
                </h3>

                <div>
                    <?php 
                    $widgetUnitCode = 'KEMAHASISWAAN';
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
                              placeholder="Maksimal 3 kalimat menjelaskan inovasi/lomba, jumlah peserta/negara, dan kebanggaan bagi universitas..."
                              class="w-full bg-slate-50 border border-slate-300 rounded-lg p-3 text-sm focus:ring-amber-500 focus:border-amber-500"><?= htmlspecialchars($entry['short_description'] ?? '') ?></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Bukti Sertifikat / Dokumentasi (PDF/Foto, Maks. 10 MB)
                        </label>
                        <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png"
                               class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 text-xs focus:ring-amber-500 focus:border-amber-500">
                        <?php if (!empty($entry['attachment_name'])): ?>
                            <div class="mt-1 text-xs text-slate-600">
                                Berkas: <strong class="text-emerald-600"><?= htmlspecialchars($entry['attachment_name']) ?></strong> (<?= Helpers::formatBytes($entry['attachment_size_bytes']) ?>)
                            </div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Tautan Bukti / Situs Resmi Kompetisi
                        </label>
                        <input type="url" name="external_url" value="<?= htmlspecialchars($entry['external_url'] ?? '') ?>" placeholder="https://..."
                               class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500">
                    </div>
                </div>
            </div>

            <!-- Section 5: Privasi & Screening -->
            <div class="space-y-4">
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                    <label class="block text-xs font-bold text-amber-900 uppercase tracking-wider mb-2">
                        Persetujuan Publikasi Nama Mahasiswa <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center gap-6">
                        <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-800 cursor-pointer">
                            <input type="radio" name="name_publication_consent" value="YA" <?= ($consent === 'YA') ? 'checked' : '' ?> class="text-emerald-600 focus:ring-emerald-500">
                            <span>YA (Izinkan nama dicantumkan dalam siaran media &amp; berita)</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-xs font-semibold text-red-800 cursor-pointer">
                            <input type="radio" name="name_publication_consent" value="TIDAK" <?= ($consent === 'TIDAK') ? 'checked' : '' ?> class="text-red-600 focus:ring-red-500">
                            <span>TIDAK (Wajib gunakan inisial/tim dalam publikasi luar)</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Internal</label>
                    <input type="text" name="notes" value="<?= htmlspecialchars($entry['notes'] ?? '') ?>" placeholder="Catatan untuk tim kurasi komunikasi..."
                           class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500">
                </div>

                <div class="pt-2">
                    <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">
                        Status Screening Unit <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center gap-6 bg-slate-50 p-3 rounded-lg border border-slate-200">
                        <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer">
                            <input type="radio" name="screening_status" value="DRAFT" <?= ($screeningStatus === 'DRAFT') ? 'checked' : '' ?>>
                            <span><strong>Simpan DRAFT</strong> (Masih ditinjau di Kemahasiswaan)</span>
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
                <a href="/index.php?page=entries&unit_code=KEMAHASISWAAN" class="px-4 py-2.5 border border-slate-300 text-slate-600 hover:bg-slate-100 rounded-xl text-xs font-bold transition-colors">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-uhn-800 to-uhn-700 hover:from-uhn-900 hover:to-uhn-800 text-white rounded-xl text-xs font-black shadow-md shadow-rose-950/20 transition-all">
                    <?= $isEdit ? 'Simpan Perubahan Prestasi' : 'Simpan Prestasi Mahasiswa' ?>
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
