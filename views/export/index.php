<?php
// views/export/index.php
require_once __DIR__ . '/../../app/Models/Entry.php';
require_once __DIR__ . '/../../app/Config.php';
require_once __DIR__ . '/../../app/Auth.php';
require_once __DIR__ . '/../../app/Helpers.php';

$activePeriod = $_GET['period_id'] ?? '';
$activeUnit = $_GET['unit_code'] ?? '';
$activeTab = $_GET['tab'] ?? 'download';

$wuriClusters = Entry::getWuriGrouped($activePeriod);
?>

<div class="space-y-6">

    <!-- Header Box (Crimson Modern Header) -->
    <div class="bg-white rounded-3xl shadow-sm border border-rose-100 p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-uhn-800 flex items-center justify-center font-bold">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900">
                    Ekspor Data &amp; Pemetaan Borang Pemeringkatan
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                    Konversi data capaian menjadi format borang WURI (World University Rankings for Innovation), Webometrics, Uniranks, serta rekapitulasi Excel/CSV.
                </p>
            </div>
        </div>

        <!-- Tab Selector Button Strip -->
        <div class="flex items-center gap-1.5 bg-rose-50/70 p-1.5 rounded-2xl border border-rose-100 self-start md:self-auto">
            <a href="/index.php?page=export&tab=download<?= !empty($activePeriod) ? '&period_id=' . urlencode($activePeriod) : '' ?>" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all <?= ($activeTab === 'download') ? 'bg-uhn-950 text-white shadow-sm' : 'text-slate-600 hover:text-uhn-800' ?>">
                Unduh Berkas
            </a>
            <a href="/index.php?page=export&tab=preview_wuri<?= !empty($activePeriod) ? '&period_id=' . urlencode($activePeriod) : '' ?>" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all <?= ($activeTab === 'preview_wuri') ? 'bg-gradient-to-r from-uhn-800 to-rose-700 text-white shadow-sm' : 'text-slate-600 hover:text-uhn-800' ?>">
                Pratinjau Borang WURI
            </a>
        </div>
    </div>

    <?php if ($activeTab === 'download'): ?>
        <!-- TAB 1: FORMULIR UNDUH BERKAS -->
        <div class="max-w-4xl mx-auto bg-white rounded-3xl shadow-sm border border-rose-100 p-6 sm:p-8">
            <h2 class="text-xs font-extrabold text-slate-400 uppercase tracking-wider mb-4">
                Konfigurasi Unduh Laporan
            </h2>

            <form action="/index.php" method="GET" target="_blank" class="space-y-6">
                <input type="hidden" name="action" value="export_download">

                <!-- Filter Parameter -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-5 bg-slate-50/70 rounded-2xl border border-rose-100">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Pilih Bulan Pelaporan:</label>
                        <select name="period_id" class="w-full bg-white border border-slate-200 rounded-xl p-3 text-xs font-bold focus:ring-2 focus:ring-uhn-700">
                            <option value="">Semua Periode (Sep 2026 - Agu 2027)</option>
                            <?php foreach (PERIODS as $pid => $pname): ?>
                                <option value="<?= $pid ?>" <?= ($pid === $activePeriod) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($pname) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Pilih Unit Kerja:</label>
                        <select name="unit_code" class="w-full bg-white border border-slate-200 rounded-xl p-3 text-xs font-bold focus:ring-2 focus:ring-uhn-700">
                            <option value="">Semua Unit Kerja</option>
                            <option value="PPM">PPM (Paten, HKI &amp; Riset)</option>
                            <option value="PSDM">PSDM (Dosen &amp; Tendik)</option>
                            <option value="KEMAHASISWAAN">Kemahasiswaan (Prestasi Mahasiswa)</option>
                            <option value="ALUMNI_KARIR">Alumni Karir (Kiprah &amp; Wirausaha)</option>
                        </select>
                    </div>
                </div>

                <!-- Pilihan Format Template -->
                <div class="space-y-3">
                    <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider">
                        Pilih Format Template Ekspor <span class="text-rose-600">*</span>
                    </label>

                    <!-- Opsi PDF 1: Dokumen Resmi Borang WURI -->
                    <label class="flex items-start gap-3 p-4 rounded-2xl border-2 border-rose-200 hover:border-uhn-700 bg-rose-50/40 cursor-pointer transition-all group shadow-sm">
                        <input type="radio" name="format" value="pdf_wuri" checked class="mt-1 text-uhn-700 focus:ring-uhn-700">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-bold text-slate-900 group-hover:text-uhn-800">Dokumen Resmi Borang WURI (.PDF Siap Cetak / Save as PDF)</span>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-rose-700 text-white shadow-sm">Rekomendasi WURI</span>
                            </div>
                            <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                                Format resmi A4 Landscape lengkap dengan Kop Surat Universitas Harkat Negeri, pengelompokan Klaster WURI internasional (Industrial Application, Student Mobility, Leadership), statistik kelayakan, tautan evidensi rilis media, serta lembar pengesahan Rektor &amp; Ketua Task Force.
                            </p>
                        </div>
                    </label>

                    <!-- Opsi PDF 2: Laporan Rekapitulasi Capaian Institusi -->
                    <label class="flex items-start gap-3 p-4 rounded-2xl border-2 border-slate-200 hover:border-uhn-700 bg-slate-50/50 cursor-pointer transition-all group">
                        <input type="radio" name="format" value="pdf_rekap" class="mt-1 text-uhn-700 focus:ring-uhn-700">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-bold text-slate-900 group-hover:text-uhn-800">Laporan Eksekutif Rekapitulasi Capaian Reputasi (.PDF)</span>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-slate-800 text-white">Eksekutif / Rektorat</span>
                            </div>
                            <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                                Rekapitulasi lengkap seluruh capaian 4 unit strategis (PPM, PSDM, Kemahasiswaan, Alumni) dengan matriks tier capaian (Signature vs Reguler), status screening unit, dan sensor kepatuhan UU PDP.
                            </p>
                        </div>
                    </label>

                    <!-- Opsi 1: Borang WURI CSV -->
                    <label class="flex items-start gap-3 p-4 rounded-2xl border border-rose-100 hover:border-uhn-700 bg-white cursor-pointer transition-all group">
                        <input type="radio" name="format" value="wuri" class="mt-1 text-uhn-700 focus:ring-uhn-700">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-bold text-slate-900 group-hover:text-uhn-800">Format Data Borang WURI (.CSV Spreadsheet)</span>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-uhn-900">Data Raw WURI</span>
                            </div>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                Data dikelompokkan secara otomatis ke dalam klaster WURI: <em>Industrial Applications</em>, <em>Student Mobility &amp; Openness</em>, dan <em>Crisis Management, Ethics &amp; Leadership</em>.
                            </p>
                        </div>
                    </label>

                    <!-- Opsi 2: Excel Berwarna / Terformat (.xls HTML) -->
                    <label class="flex items-start gap-3 p-4 rounded-2xl border border-rose-100 hover:border-uhn-700 bg-white cursor-pointer transition-all group">
                        <input type="radio" name="format" value="excel_styled" class="mt-1 text-uhn-700 focus:ring-uhn-700">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-bold text-slate-900 group-hover:text-uhn-800">Format Spreadsheet Excel Berwarna (.XLS Terformat)</span>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-900">Excel Siap Cetak</span>
                            </div>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                Dilengkapi kop surat resmi Universitas Harkat Negeri, header tabel berwarna Crimson Maroon &amp; Gold, serta status badge siap presentasi.
                            </p>
                        </div>
                    </label>

                    <!-- Opsi 3: Rekap CSV Standar -->
                    <label class="flex items-start gap-3 p-4 rounded-2xl border border-rose-100 hover:border-uhn-700 bg-white cursor-pointer transition-all group">
                        <input type="radio" name="format" value="excel" class="mt-1 text-uhn-700 focus:ring-uhn-700">
                        <div>
                            <span class="text-sm font-bold text-slate-900 group-hover:text-uhn-800">Rekapitulasi CSV Standar (.CSV)</span>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                Seluruh kolom data capaian, audit trail, status screening unit, dan tautan publikasi hasil untuk olah data SPSS / Python.
                            </p>
                        </div>
                    </label>

                    <!-- Opsi 4: Evidensi Webometrics & Uniranks -->
                    <label class="flex items-start gap-3 p-4 rounded-2xl border border-rose-100 hover:border-uhn-700 bg-white cursor-pointer transition-all group">
                        <input type="radio" name="format" value="webometrics" class="mt-1 text-uhn-700 focus:ring-uhn-700">
                        <div>
                            <span class="text-sm font-bold text-slate-900 group-hover:text-uhn-800">Evidensi Reputasi Webometrics &amp; Uniranks (.CSV)</span>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                Khusus menyaring data yang telah memiliki tautan publikasi rilis pers dan sertifikasi/paten granted.
                            </p>
                        </div>
                    </label>
                </div>

                <!-- Proteksi Privasi & UU PDP -->
                <div class="bg-rose-50/70 border border-rose-200 rounded-2xl p-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="mask_pii" value="1" checked class="rounded text-uhn-700 focus:ring-uhn-700">
                        <span class="text-xs font-bold text-uhn-950">
                            Sensor otomatis nomor identitas pribadi (NIP/NIM/NIDN) dan nama bagi yang berstatus "Persetujuan: TIDAK"
                        </span>
                    </label>
                    <p class="text-[11px] text-rose-900/80 mt-1 ml-6">
                        Memastikan file ekspor mematuhi regulasi privasi data personil institusi bila dibagikan ke pihak eksternal atau asesor akreditasi.
                    </p>
                </div>

                <!-- Tombol Download -->
                <div class="pt-4 flex items-center justify-end">
                    <button type="submit" class="px-6 py-3.5 bg-gradient-to-r from-uhn-800 to-uhn-700 hover:from-uhn-900 hover:to-uhn-800 text-white rounded-xl text-xs font-extrabold shadow-md shadow-rose-950/20 transition-all flex items-center gap-2">
                        <svg class="w-4 h-4 text-rose-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Unduh Berkas Sekarang
                    </button>
                </div>

            </form>
        </div>

    <?php else: ?>
        <!-- TAB 2: PRATINJAU LANGSUNG BORANG WURI (INTERAKTIF DI LAYAR) -->
        <div class="space-y-6">

            <!-- Action Toolbar Pratinjau WURI -->
            <div class="bg-white p-4 sm:p-5 rounded-3xl border border-rose-100 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-rose-100 text-uhn-900 flex items-center justify-center font-black">
                        W
                    </div>
                    <div>
                        <h2 class="text-sm font-black text-slate-900">
                            Pratinjau Borang WURI Periode <?= htmlspecialchars(!empty($activePeriod) ? (PERIODS[$activePeriod] ?? $activePeriod) : 'Semua Siklus Pelaporan') ?>
                        </h2>
                        <p class="text-xs text-slate-500">
                            Data dikelompokkan otomatis berdasarkan klaster pemeringkatan global WURI.
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Filter Periode Cepat Langsung di Tab 2 -->
                    <form action="/index.php" method="GET" class="flex items-center gap-1.5 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200">
                        <input type="hidden" name="page" value="export">
                        <input type="hidden" name="tab" value="preview_wuri">
                        <label class="text-[11px] font-bold text-slate-500 uppercase">Periode:</label>
                        <select name="period_id" onchange="this.form.submit()" 
                                class="bg-white border border-slate-200 text-slate-800 text-xs font-bold rounded-lg px-2.5 py-1 focus:ring-2 focus:ring-uhn-700 cursor-pointer">
                            <option value="">Semua Periode (12 Bulan)</option>
                            <?php foreach (PERIODS as $pid => $pname): ?>
                                <option value="<?= $pid ?>" <?= ($pid === $activePeriod) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($pname) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>

                    <a href="/index.php?action=export_download&format=pdf_wuri&period_id=<?= urlencode($activePeriod) ?>&auto_print=1" target="_blank"
                       class="px-4 py-2.5 bg-gradient-to-r from-uhn-800 to-rose-700 hover:from-uhn-900 hover:to-rose-800 text-white rounded-xl text-xs font-bold shadow-md shadow-rose-950/20 inline-flex items-center gap-2 transition-all">
                        <svg class="w-4 h-4 text-rose-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        <span>Cetak / Unduh PDF WURI</span>
                    </a>

                    <a href="/index.php?action=export_download&format=wuri&period_id=<?= urlencode($activePeriod) ?>" 
                       class="px-3.5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold inline-flex items-center gap-1.5 transition-colors">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <span>Unduh CSV</span>
                    </a>
                </div>
            </div>

            <?php foreach ($wuriClusters as $cKey => $cluster): ?>
                <div class="bg-white rounded-3xl shadow-sm border border-rose-100 overflow-hidden">
                    <div class="px-6 py-5 bg-gradient-to-r from-uhn-950 via-uhn-900 to-uhn-800 text-white flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-white text-uhn-900">
                                Klaster <?= $cluster['code'] ?>
                            </span>
                            <h2 class="text-base font-bold text-white mt-1.5"><?= htmlspecialchars($cluster['title']) ?></h2>
                            <p class="text-xs text-rose-200"><?= htmlspecialchars($cluster['description']) ?></p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-white/15 text-rose-100 border border-white/20 self-start sm:self-auto">
                            <?= count($cluster['items']) ?> Inovasi Terdata
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-700">
                            <thead class="bg-rose-50/50 text-slate-500 uppercase font-bold text-[11px] border-b border-rose-100">
                                <tr>
                                    <th class="px-4 py-3 w-12 text-center">No</th>
                                    <th class="px-4 py-3">Unit &amp; Tier</th>
                                    <th class="px-6 py-3">Judul Program / Inovasi &amp; Deskripsi</th>
                                    <th class="px-4 py-3">Subjek / Inovator</th>
                                    <th class="px-4 py-3">Status Evidensi</th>
                                    <th class="px-4 py-3 text-right">Tautan Rilis</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-rose-50">
                                <?php if (empty($cluster['items'])): ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-8 text-center text-slate-400 italic">
                                            Belum ada capaian yang terverifikasi untuk klaster ini pada periode terpilih.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php $no = 1; foreach ($cluster['items'] as $item): ?>
                                        <tr class="hover:bg-rose-50/30 transition-colors">
                                            <td class="px-4 py-3.5 text-center font-bold text-slate-400"><?= $no++ ?></td>
                                            <td class="px-4 py-3.5 whitespace-nowrap">
                                                <span class="font-bold text-slate-900"><?= htmlspecialchars($item['unit_code']) ?></span>
                                                <div class="mt-1"><?= Helpers::badgeTier($item['tier_type']) ?></div>
                                            </td>
                                            <td class="px-6 py-3.5 max-w-md">
                                                <a href="/index.php?page=entries_show&id=<?= $item['id'] ?>" class="font-bold text-slate-900 text-sm hover:text-uhn-800 block">
                                                    <?= htmlspecialchars($item['title']) ?>
                                                </a>
                                                <p class="text-xs text-slate-500 mt-1 line-clamp-2">
                                                    <?= htmlspecialchars($item['short_description']) ?>
                                                </p>
                                            </td>
                                            <td class="px-4 py-3.5">
                                                <?php 
                                                $subj = '-';
                                                if ($item['unit_code'] === 'PPM') {
                                                    $invs = array_map(fn($i) => $i['name'], $item['details']['inventors'] ?? []);
                                                    $subj = implode(', ', $invs);
                                                } elseif ($item['unit_code'] === 'PSDM') {
                                                    $subj = $item['details']['employee_name'] ?? '-';
                                                } elseif ($item['unit_code'] === 'KEMAHASISWAAN') {
                                                    $subj = $item['details']['student_name'] ?? '-';
                                                } elseif ($item['unit_code'] === 'ALUMNI_KARIR') {
                                                    $subj = $item['details']['alumni_name'] ?? '-';
                                                }
                                                echo Helpers::maskNameIfRestricted($subj, $item['name_publication_consent'], Auth::isAdmin());
                                                ?>
                                            </td>
                                            <td class="px-4 py-3.5 whitespace-nowrap">
                                                <?= Helpers::badgeComm($item['comm_status']) ?>
                                            </td>
                                            <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                                <?php if (!empty($item['comm_published_url'])): ?>
                                                    <a href="<?= htmlspecialchars($item['comm_published_url']) ?>" target="_blank" 
                                                       class="inline-flex items-center gap-1 text-xs font-bold text-uhn-800 hover:text-uhn-900 hover:underline">
                                                        Buka Berita &rarr;
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-slate-400 italic">Belum Rilis</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>
