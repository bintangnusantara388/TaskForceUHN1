<?php
// views/entries/form_psdm.php
require_once __DIR__ . '/../../app/Config.php';
require_once __DIR__ . '/../../app/Auth.php';
require_once __DIR__ . '/../../app/Helpers.php';

$isEdit = isset($entry) && $entry !== null;
$actionUrl = $isEdit ? "/index.php?action=entries_update&id={$entry['id']}" : "/index.php?action=entries_store";
$unitCode = 'PSDM';
$selectedPeriod = $entry['period_id'] ?? ($_GET['period_id'] ?? '2026-10');
$tierType = $entry['tier_type'] ?? 'SIGNATURE';
$consent = $entry['name_publication_consent'] ?? 'YA';
$screeningStatus = $entry['screening_status'] ?? 'DRAFT';

$details = $entry['details'] ?? [];
$empName = $details['employee_name'] ?? '';
$empId = $details['employee_id'] ?? '';
$empUnit = $details['unit_or_prodi'] ?? '';
$empType = $details['employee_type'] ?? 'DOSEN';
$achType = $details['achievement_type'] ?? 'SERTIFIKASI';
$organizer = $details['issuer_organizer'] ?? '';
$level = $details['level'] ?? 'NASIONAL';
$achDate = $details['achievement_date'] ?? date('Y-m-d');
?>

<div class="max-w-4xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <a href="/index.php?page=entries&unit_code=PSDM" class="inline-flex items-center text-xs font-bold text-slate-500 hover:text-uhn-800 transition-colors">
            &larr; Kembali ke Daftar Inventarisasi
        </a>
        <span class="px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-uhn-900 border border-rose-200">
            Modul 2: PSDM (Capaian Pegawai Dosen &amp; Tendik)
        </span>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-rose-100 overflow-hidden">
        <div class="px-6 py-5 bg-gradient-to-r from-uhn-950 via-uhn-900 to-uhn-800 text-white">
            <h2 class="text-lg font-bold">
                <?= $isEdit ? 'Ubah Data Capaian Pegawai' : 'Form Input Capaian Pegawai (PSDM)' ?>
            </h2>
            <p class="text-xs text-rose-200 mt-0.5">
                Inventarisasi prestasi, sertifikasi internasional, narasumber global, dan pengakuan kompetensi personil institusi.
            </p>
        </div>

        <form action="<?= $actionUrl ?>" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
            <input type="hidden" name="unit_code" value="PSDM">

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

            <!-- Section 2: Data Pegawai -->
            <div class="space-y-4 pb-6 border-b border-slate-200">
                <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider text-indigo-900">
                    Data Identitas Pegawai
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Nama Pegawai Beserta Gelar <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="employee_name" value="<?= htmlspecialchars($empName) ?>" required placeholder="Contoh: Prof. Dr. Ir. Teguh Wicaksono, M.Eng."
                               class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            NIP atau NIDN (Hanya Angka) <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="employee_id" value="<?= htmlspecialchars($empId) ?>" required pattern="[0-9]+" title="Hanya angka diperbolehkan" placeholder="Contoh: 197508101999031002"
                               class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm font-mono focus:ring-amber-500 focus:border-amber-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Unit Kerja / Program Studi <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="unit_or_prodi" value="<?= htmlspecialchars($empUnit) ?>" required placeholder="Contoh: Teknik Informatika / Biro Perencanaan"
                               class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Jenis Pegawai <span class="text-red-500">*</span>
                        </label>
                        <select name="employee_type" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500">
                            <option value="DOSEN" <?= ($empType === 'DOSEN') ? 'selected' : '' ?>>Dosen (Tenaga Pendidik)</option>
                            <option value="TENDIK" <?= ($empType === 'TENDIK') ? 'selected' : '' ?>>Tendik (Tenaga Kependidikan)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 3: Rincian Capaian Pegawai -->
            <div class="space-y-4 pb-6 border-b border-slate-200">
                <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider text-indigo-900">
                    Rincian Capaian &amp; Penyelenggara
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Jenis Capaian <span class="text-red-500">*</span>
                        </label>
                        <select name="achievement_type" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500">
                            <?php 
                            $achTypes = [
                                'SERTIFIKASI' => 'Sertifikasi Profesi / Kompetensi',
                                'JABATAN_FUNGSIONAL' => 'Jabatan Fungsional (Guru Besar/Lektor Kepala)',
                                'PENGHARGAAN' => 'Penghargaan / Award',
                                'NARASUMBER' => 'Narasumber / Keynote Speaker',
                                'PELATIHAN' => 'Pelatihan / Short Course Global',
                                'LAINNYA' => 'Lainnya'
                            ];
                            foreach ($achTypes as $k => $v): ?>
                                <option value="<?= $k ?>" <?= ($achType === $k) ? 'selected' : '' ?>><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Tingkat Capaian <span class="text-red-500">*</span>
                        </label>
                        <select name="level" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500 font-semibold">
                            <option value="INTERNASIONAL" <?= ($level === 'INTERNASIONAL') ? 'selected' : '' ?>>Internasional</option>
                            <option value="NASIONAL" <?= ($level === 'NASIONAL') ? 'selected' : '' ?>>Nasional</option>
                            <option value="LOKAL" <?= ($level === 'LOKAL') ? 'selected' : '' ?>>Lokal / Wilayah</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Judul / Nama Capaian <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="title" value="<?= htmlspecialchars($entry['title'] ?? '') ?>" required placeholder="Contoh: Keynote Speaker pada Forum UNESCO tentang AI Ethics"
                           class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm font-semibold focus:ring-amber-500 focus:border-amber-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Pemberi / Penyelenggara <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="issuer_organizer" value="<?= htmlspecialchars($organizer) ?>" required placeholder="Contoh: UNESCO Paris / Project Management Institute (PMI) USA"
                               class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Tanggal Capaian / Kegiatan <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="achievement_date" value="<?= htmlspecialchars($achDate) ?>" required
                               class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500">
                    </div>
                </div>
            </div>

            <!-- Section 4: Narasi, Lampiran & Privasi -->
            <div class="space-y-4 pb-6 border-b border-slate-200">
                <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider text-indigo-900">
                    Narasi &amp; Lampiran Bukti
                </h3>

                <div>
                    <?php 
                    $widgetUnitCode = 'PSDM';
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
                              placeholder="Maksimal 3 kalimat menjelaskan apa capaiannya, relevansinya, dan dampak bagi institusi..."
                              class="w-full bg-slate-50 border border-slate-300 rounded-lg p-3 text-sm focus:ring-amber-500 focus:border-amber-500"><?= htmlspecialchars($entry['short_description'] ?? '') ?></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Bukti Sertifikat / SK / Foto (PDF/Foto, Maks. 10 MB)
                        </label>
                        <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png"
                               class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 text-xs focus:ring-amber-500 focus:border-amber-500">
                        <?php if (!empty($entry['attachment_name'])): ?>
                            <div class="mt-1 text-xs text-slate-600">
                                Berkas: <strong class="text-indigo-600"><?= htmlspecialchars($entry['attachment_name']) ?></strong> (<?= Helpers::formatBytes($entry['attachment_size_bytes']) ?>)
                            </div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Tautan Bukti / Tautan Berita Eksternal
                        </label>
                        <input type="url" name="external_url" value="<?= htmlspecialchars($entry['external_url'] ?? '') ?>" placeholder="https://..."
                               class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500">
                    </div>
                </div>
            </div>

            <!-- Section 5: Privasi & Screening Unit -->
            <div class="space-y-4">
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                    <label class="block text-xs font-bold text-amber-900 uppercase tracking-wider mb-2">
                        Persetujuan Publikasi Nama Pegawai <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center gap-6">
                        <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-800 cursor-pointer">
                            <input type="radio" name="name_publication_consent" value="YA" <?= ($consent === 'YA') ? 'checked' : '' ?> class="text-emerald-600 focus:ring-emerald-500">
                            <span>YA (Izinkan nama dicantumkan dalam siaran publik)</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-xs font-semibold text-red-800 cursor-pointer">
                            <input type="radio" name="name_publication_consent" value="TIDAK" <?= ($consent === 'TIDAK') ? 'checked' : '' ?> class="text-red-600 focus:ring-red-500">
                            <span>TIDAK (Rahasiakan nama pribadi dari publikasi terbuka)</span>
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
                            <span><strong>Simpan DRAFT</strong> (Masih ditinjau di PSDM)</span>
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
                <a href="/index.php?page=entries&unit_code=PSDM" class="px-4 py-2.5 border border-slate-300 text-slate-600 hover:bg-slate-100 rounded-xl text-xs font-bold transition-colors">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-uhn-800 to-uhn-700 hover:from-uhn-900 hover:to-uhn-800 text-white rounded-xl text-xs font-black shadow-md shadow-rose-950/20 transition-all">
                    <?= $isEdit ? 'Simpan Perubahan Data PSDM' : 'Simpan Capaian Pegawai PSDM' ?>
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
