<?php
// views/entries/form_ppm.php
require_once __DIR__ . '/../../app/Config.php';
require_once __DIR__ . '/../../app/Auth.php';
require_once __DIR__ . '/../../app/Helpers.php';

$isEdit = isset($entry) && $entry !== null;
$actionUrl = $isEdit ? "/index.php?action=entries_update&id={$entry['id']}" : "/index.php?action=entries_store";
$unitCode = 'PPM';
$selectedPeriod = $entry['period_id'] ?? ($_GET['period_id'] ?? '2026-10');
$tierType = $entry['tier_type'] ?? 'SIGNATURE';
$consent = $entry['name_publication_consent'] ?? 'YA';
$screeningStatus = $entry['screening_status'] ?? 'DRAFT';

$details = $entry['details'] ?? [];
$kiType = $details['ki_type'] ?? 'PATEN';
$regNumber = $details['registration_number'] ?? '';
$filingDate = $details['filing_date'] ?? date('Y-m-d');
$grantDate = $details['grant_date'] ?? '';
$kiStatus = $details['ki_status'] ?? 'DALAM_PROSES';
$inventors = $details['inventors'] ?? [
    ['name' => '', 'identifier' => '', 'prodi' => '', 'inventor_type' => 'DOSEN']
];
?>

<div class="max-w-4xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <a href="/index.php?page=entries&unit_code=PPM" class="inline-flex items-center text-xs font-bold text-slate-500 hover:text-uhn-800 transition-colors">
            &larr; Kembali ke Daftar Inventarisasi
        </a>
        <span class="px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-uhn-900 border border-rose-200">
            Modul 1: PPM (Paten, HKI &amp; Riset)
        </span>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-rose-100 overflow-hidden">
        <div class="px-6 py-5 bg-gradient-to-r from-uhn-950 via-uhn-900 to-uhn-800 text-white">
            <h2 class="text-lg font-bold">
                <?= $isEdit ? 'Ubah Data Capaian Kekayaan Intelektual' : 'Form Input Capaian Kekayaan Intelektual &amp; Riset (PPM)' ?>
            </h2>
            <p class="text-xs text-rose-200 mt-0.5">
                Pastikan data yang diinput telah lolos screening unit dan layak diolah menjadi materi branding universitas.
            </p>
        </div>

        <form action="<?= $actionUrl ?>" method="POST" enctype="multipart/form-data" class="p-6 space-y-6" id="ppmForm">
            <input type="hidden" name="unit_code" value="PPM">

            <!-- Section 1: Periode & Status Tier -->
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
                    <span class="text-[11px] text-slate-400">Rentang tahun pelaporan: Sep 2026 s.d. Agu 2027</span>
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
                    <span class="text-[11px] text-slate-400">Unit wajib menyetor min. 1 data Signature per bulan</span>
                </div>
            </div>

            <!-- Section 2: Informasi Kekayaan Intelektual -->
            <div class="space-y-4 pb-6 border-b border-slate-200">
                <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider text-blue-900">
                    Informasi Kekayaan Intelektual
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Jenis Kekayaan Intelektual <span class="text-red-500">*</span>
                        </label>
                        <select name="ki_type" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500">
                            <?php foreach (['Paten', 'Paten Sederhana', 'Hak Cipta', 'Merek', 'Desain Industri', 'Lainnya'] as $ki): ?>
                                <option value="<?= strtoupper(str_replace(' ', '_', $ki)) ?>" <?= ($kiType === strtoupper(str_replace(' ', '_', $ki))) ? 'selected' : '' ?>>
                                    <?= $ki ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Nomor Pendaftaran / Sertifikat <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="registration_number" value="<?= htmlspecialchars($regNumber) ?>" required placeholder="Contoh: IDP000088921 / EC0020269910"
                               class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Judul Capaian Intelektual / Riset <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="title" value="<?= htmlspecialchars($entry['title'] ?? '') ?>" required placeholder="Contoh: Alat Sortir Kopi Berbasis AI Berdaya Rendah"
                           class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm font-semibold focus:ring-amber-500 focus:border-amber-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Status KI <span class="text-red-500">*</span>
                        </label>
                        <select name="ki_status" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500">
                            <option value="DALAM_PROSES" <?= ($kiStatus === 'DALAM_PROSES') ? 'selected' : '' ?>>Dalam Proses</option>
                            <option value="TERDAFTAR" <?= ($kiStatus === 'TERDAFTAR') ? 'selected' : '' ?>>Terdaftar</option>
                            <option value="GRANTED" <?= ($kiStatus === 'GRANTED') ? 'selected' : '' ?>>Granted</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Tanggal Didaftarkan <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="filing_date" value="<?= htmlspecialchars($filingDate) ?>" required
                               class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Tanggal Terbit (Jika Granted/Terdaftar)
                        </label>
                        <input type="date" name="grant_date" value="<?= htmlspecialchars($grantDate) ?>"
                               class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500">
                    </div>
                </div>
            </div>

            <!-- Section 3: Pencipta / Inventor Dinamis -->
            <div class="space-y-4 pb-6 border-b border-slate-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider text-blue-900">
                            Pencipta / Inventor
                        </h3>
                        <p class="text-xs text-slate-500">Bisa menambahkan lebih dari satu orang (Dosen maupun Mahasiswa). Validasi: NIDN/NIM wajib angka murni.</p>
                    </div>
                    <button type="button" onclick="addInventorRow()" 
                            class="px-3 py-1.5 bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 rounded-lg text-xs font-bold transition-colors">
                        + Tambah Inventor
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border border-slate-200 rounded-lg overflow-hidden" id="inventorTable">
                        <thead class="bg-slate-100 text-slate-700 font-semibold uppercase">
                            <tr>
                                <th class="p-2.5">Nama Lengkap &amp; Gelar</th>
                                <th class="p-2.5">NIDN / NIM (Hanya Angka)</th>
                                <th class="p-2.5">Program Studi</th>
                                <th class="p-2.5">Tipe</th>
                                <th class="p-2.5 text-center w-16">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200" id="inventorBody">
                            <?php foreach ($inventors as $idx => $inv): ?>
                                <tr class="inventor-row">
                                    <td class="p-2">
                                        <input type="text" name="inventor_name[]" value="<?= htmlspecialchars($inv['name']) ?>" required placeholder="Nama lengkap" 
                                               class="w-full bg-white border border-slate-300 rounded px-2.5 py-1.5 text-xs">
                                    </td>
                                    <td class="p-2">
                                        <input type="text" name="inventor_identifier[]" value="<?= htmlspecialchars($inv['identifier']) ?>" required pattern="[0-9]+" title="Hanya angka diperbolehkan" placeholder="NIDN/NIM" 
                                               class="w-full bg-white border border-slate-300 rounded px-2.5 py-1.5 text-xs font-mono">
                                    </td>
                                    <td class="p-2">
                                        <input type="text" name="inventor_prodi[]" value="<?= htmlspecialchars($inv['prodi']) ?>" required placeholder="Prodi" 
                                               class="w-full bg-white border border-slate-300 rounded px-2.5 py-1.5 text-xs">
                                    </td>
                                    <td class="p-2">
                                        <select name="inventor_type[]" class="w-full bg-white border border-slate-300 rounded px-2 py-1.5 text-xs">
                                            <option value="DOSEN" <?= (($inv['inventor_type'] ?? '') === 'DOSEN') ? 'selected' : '' ?>>Dosen</option>
                                            <option value="MAHASISWA" <?= (($inv['inventor_type'] ?? '') === 'MAHASISWA') ? 'selected' : '' ?>>Mahasiswa</option>
                                        </select>
                                    </td>
                                    <td class="p-2 text-center">
                                        <button type="button" onclick="removeInventorRow(this)" class="text-red-500 hover:text-red-700 font-bold px-2 py-1">
                                            &times;
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Section 4: Narasi Bahasa Awam & Lampiran -->
            <div class="space-y-4 pb-6 border-b border-slate-200">
                <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider text-blue-900">
                    Narasi Publikasi &amp; Lampiran Bukti
                </h3>

                <div>
                    <?php 
                    $widgetUnitCode = 'PPM';
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
                              placeholder="Maksimal 3 kalimat dengan bahasa awam dan menarik untuk diolah tim Komunikasi menjadi konten berita..."
                              class="w-full bg-slate-50 border border-slate-300 rounded-lg p-3 text-sm focus:ring-amber-500 focus:border-amber-500"><?= htmlspecialchars($entry['short_description'] ?? '') ?></textarea>
                    <p class="text-[11px] text-slate-400 mt-1">
                        Pedoman: Kalimat 1 (masalah/inovasi), Kalimat 2 (keunggulan/pengujian), Kalimat 3 (dampak bagi masyarakat/industri).
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Lampiran Sertifikat / Bukti Sahih (PDF/Foto, Maks. 10 MB)
                        </label>
                        <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png"
                               class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 text-xs focus:ring-amber-500 focus:border-amber-500">
                        <?php if (!empty($entry['attachment_name'])): ?>
                            <div class="mt-1 text-xs text-slate-600 flex items-center gap-1">
                                <span class="text-slate-400">Berkas saat ini:</span>
                                <span class="font-semibold text-blue-600"><?= htmlspecialchars($entry['attachment_name']) ?></span>
                                <span>(<?= Helpers::formatBytes($entry['attachment_size_bytes']) ?>)</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Tautan Pendukung / Pangkalan Data PDKI (Jika Ada)
                        </label>
                        <input type="url" name="external_url" value="<?= htmlspecialchars($entry['external_url'] ?? '') ?>" placeholder="https://pdki-indonesia.dgip.go.id/..."
                               class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500">
                    </div>
                </div>
            </div>

            <!-- Section 5: Privasi, Catatan, & Alur Screening Unit -->
            <div class="space-y-4">
                <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider text-blue-900">
                    Privasi &amp; Status Screening Unit
                </h3>

                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                    <label class="block text-xs font-bold text-amber-900 uppercase tracking-wider mb-2">
                        Persetujuan Publikasi Nama Subjek / Inventor <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center gap-6">
                        <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-800 cursor-pointer">
                            <input type="radio" name="name_publication_consent" value="YA" <?= ($consent === 'YA') ? 'checked' : '' ?> class="text-emerald-600 focus:ring-emerald-500">
                            <span>YA (Izinkan nama dicantumkan dalam siaran pers &amp; publikasi terbuka)</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-xs font-semibold text-red-800 cursor-pointer">
                            <input type="radio" name="name_publication_consent" value="TIDAK" <?= ($consent === 'TIDAK') ? 'checked' : '' ?> class="text-red-600 focus:ring-red-500">
                            <span>TIDAK (Wajib gunakan nama tim / anonim dalam rilis publik)</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Internal Unit</label>
                    <input type="text" name="notes" value="<?= htmlspecialchars($entry['notes'] ?? '') ?>" placeholder="Misal: Prioritas bahan borang WURI Industrial Application..."
                           class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500">
                </div>

                <div class="pt-2">
                    <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">
                        Status Screening Unit <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center gap-6 bg-slate-50 p-3 rounded-lg border border-slate-200">
                        <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer">
                            <input type="radio" name="screening_status" value="DRAFT" <?= ($screeningStatus === 'DRAFT') ? 'checked' : '' ?>>
                            <span><strong>Simpan DRAFT</strong> (Masih ditinjau di internal unit PPM)</span>
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
                <a href="/index.php?page=entries&unit_code=PPM" class="px-4 py-2.5 border border-slate-300 text-slate-600 hover:bg-slate-100 rounded-xl text-xs font-bold transition-colors">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-uhn-800 to-uhn-700 hover:from-uhn-900 hover:to-uhn-800 text-white rounded-xl text-xs font-black shadow-md shadow-rose-950/20 transition-all">
                    <?= $isEdit ? 'Simpan Perubahan Data PPM' : 'Simpan Capaian PPM' ?>
                </button>
            </div>

        </form>
    </div>

</div>

<script>
function addInventorRow() {
    const tbody = document.getElementById('inventorBody');
    const tr = document.createElement('tr');
    tr.className = 'inventor-row';
    tr.innerHTML = `
        <td class="p-2">
            <input type="text" name="inventor_name[]" required placeholder="Nama lengkap" 
                   class="w-full bg-white border border-slate-300 rounded px-2.5 py-1.5 text-xs">
        </td>
        <td class="p-2">
            <input type="text" name="inventor_identifier[]" required pattern="[0-9]+" title="Hanya angka diperbolehkan" placeholder="NIDN/NIM" 
                   class="w-full bg-white border border-slate-300 rounded px-2.5 py-1.5 text-xs font-mono">
        </td>
        <td class="p-2">
            <input type="text" name="inventor_prodi[]" required placeholder="Prodi" 
                   class="w-full bg-white border border-slate-300 rounded px-2.5 py-1.5 text-xs">
        </td>
        <td class="p-2">
            <select name="inventor_type[]" class="w-full bg-white border border-slate-300 rounded px-2 py-1.5 text-xs">
                <option value="DOSEN">Dosen</option>
                <option value="MAHASISWA">Mahasiswa</option>
            </select>
        </td>
        <td class="p-2 text-center">
            <button type="button" onclick="removeInventorRow(this)" class="text-red-500 hover:text-red-700 font-bold px-2 py-1">
                &times;
            </button>
        </td>
    `;
    tbody.appendChild(tr);
}

function removeInventorRow(btn) {
    const rows = document.querySelectorAll('.inventor-row');
    if (rows.length <= 1) {
        alert('Minimal harus ada 1 inventor yang dicantumkan.');
        return;
    }
    btn.closest('tr').remove();
}

// Inisialisasi hitungan saat load
document.addEventListener('DOMContentLoaded', function() {
    checkSentenceCount(document.getElementById('shortDescInput'));
});
</script>
