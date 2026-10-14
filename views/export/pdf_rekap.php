<?php
// views/export/pdf_rekap.php
require_once __DIR__ . '/../../app/Config.php';
require_once __DIR__ . '/../../app/Database.php';
require_once __DIR__ . '/../../app/Auth.php';
require_once __DIR__ . '/../../app/Helpers.php';
require_once __DIR__ . '/../../app/Models/Entry.php';

if (!Auth::check()) {
    header("Location: /index.php?page=login");
    exit;
}

$periodId = $_GET['period_id'] ?? '';
$unitCode = $_GET['unit_code'] ?? '';
$maskPII = !empty($_GET['mask_pii']);

// Jika PIC, kunci ke unitnya
if (Auth::isPIC() && Auth::unit()) {
    $unitCode = Auth::unit();
}

$entries = Entry::getAll([
    'period_id' => $periodId,
    'unit_code' => $unitCode
]);

$periodName = !empty($periodId) ? (PERIODS[$periodId] ?? $periodId) : 'Seluruh Periode (Sep 2026 - Agu 2027)';
$unitName = !empty($unitCode) ? (UNITS[$unitCode] ?? $unitCode) : 'Seluruh Unit Kerja Strategis (PPM, PSDM, Kemahasiswaan, Alumni)';

// Ringkasan statistik
$totalData = count($entries);
$totalSignature = count(array_filter($entries, fn($e) => $e['tier_type'] === 'SIGNATURE'));
$totalSiap = count(array_filter($entries, fn($e) => $e['screening_status'] === 'SIAP_DIOLAH'));
$totalTerbit = count(array_filter($entries, fn($e) => $e['comm_status'] === 'SUDAH_TERBIT'));

$docNumber = "UHN/TF-REKAP/" . date('Y') . "/" . strtoupper($periodId ?: 'ALL') . "/" . date('mdHi');
$securityHash = strtoupper(substr(hash('sha256', $docNumber . 'UHN_REKAP_SECRET_KEY_' . date('Ymd')), 0, 16));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekapitulasi Capaian Reputasi UHN - <?= htmlspecialchars($docNumber) ?></title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='22' fill='%23680015'/><text x='50%' y='64%' dominant-baseline='middle' text-anchor='middle' font-family='sans-serif' font-weight='900' font-size='48' fill='%23ffffff'>PDF</text></svg>">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap');
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #0f172a;
            background-color: #f1f5f9;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        @page {
            size: A4 landscape;
            margin: 10mm 10mm 12mm 10mm;
        }

        @media print {
            body {
                background-color: #ffffff;
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .page-container {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
            }
            thead {
                display: table-header-group;
            }
            tfoot {
                display: table-footer-group;
            }
            tr {
                page-break-inside: avoid;
                break-inside: avoid;
            }
        }

        .double-border {
            border-bottom: 3.5px solid #680015;
            position: relative;
        }
        .double-border::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            right: 0;
            height: 1px;
            background-color: #680015;
        }
    </style>
</head>
<body class="p-3 sm:p-6 md:p-8">

    <!-- FLOATING TOP BAR KONTROL CETAK / SIMPAN PDF (NO PRINT) -->
    <div class="no-print fixed top-4 left-1/2 -translate-x-1/2 z-50 bg-slate-950/95 text-white backdrop-blur-md px-5 py-3 rounded-2xl shadow-2xl flex items-center justify-between gap-4 border border-rose-900/60 max-w-2xl w-[94%] sm:w-auto">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-rose-600 to-rose-800 flex items-center justify-center font-black text-white shadow-md flex-shrink-0">
                PDF
            </div>
            <div class="text-xs">
                <div class="font-extrabold text-white flex items-center gap-1.5">
                    <span>Laporan Eksekutif Capaian Institusi</span>
                    <span class="text-[10px] bg-emerald-500/20 text-emerald-300 px-1.5 py-0.2 rounded font-mono font-bold">Siap Cetak A4</span>
                </div>
                <div class="text-[10px] text-slate-300">Pilih tujuan <span class="text-amber-300 font-bold">"Save as PDF"</span> pada jendela cetak browser</div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button onclick="window.print()" 
                    class="px-4 py-2 bg-gradient-to-r from-rose-600 to-rose-700 hover:from-rose-500 hover:to-rose-600 active:scale-95 text-white rounded-xl text-xs font-black shadow-lg shadow-rose-950/40 flex items-center gap-1.5 transition-all">
                <svg class="w-4 h-4 text-rose-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Cetak / Unduh PDF</span>
            </button>
            <button onclick="window.close()" 
                    class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-bold transition-all">
                Tutup
            </button>
        </div>
    </div>

    <!-- HALAMAN UTAMA DOKUMEN RESMI (A4 LANDSCAPE) -->
    <div class="page-container max-w-[1120px] mx-auto bg-white p-6 sm:p-10 md:p-12 rounded-3xl shadow-xl border border-slate-200 text-slate-800 space-y-6">

        <!-- KOP SURAT RESMI UNIVERSITAS HARKAT NEGERI -->
        <div class="flex items-center gap-5 pb-4 double-border">
            <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-[#43000d] via-[#680015] to-[#8b0020] text-white flex items-center justify-center font-black text-3xl shadow-md border-2 border-amber-300/80 flex-shrink-0">
                UHN
            </div>

            <div class="flex-grow text-center pr-6">
                <div class="text-[11px] font-bold uppercase tracking-widest text-slate-600">
                    KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI
                </div>
                <h1 class="text-2xl sm:text-3xl font-black text-[#680015] tracking-tight uppercase mt-0.5">
                    UNIVERSITAS HARKAT NEGERI
                </h1>
                <div class="text-xs font-extrabold text-slate-800 uppercase tracking-wide mt-0.5">
                    TIM TASK FORCE KERJASAMA INTERNASIONAL &amp; REPUTASI INSTITUSI
                </div>
                <div class="text-[10px] text-slate-500 mt-1 leading-snug">
                    Status Akreditasi Institusi: <strong>UNGGUL (BAN-PT)</strong> &bull; Kampus Terpadu: Jl. Harkat Kampus No. 1, Kota Mandiri 15220<br>
                    Telepon: (021) 8899-7700 &bull; Faks: (021) 8899-7701 &bull; Laman: <span class="text-[#680015] font-semibold">www.harkatnegeri.ac.id</span> &bull; Pos-el: <span class="text-[#680015] font-semibold">taskforce@harkatnegeri.ac.id</span>
                </div>
            </div>

            <!-- Security QR Code di Header -->
            <div class="hidden sm:flex flex-col items-center justify-center p-2 bg-slate-50 rounded-2xl border border-slate-200 text-center flex-shrink-0">
                <svg class="w-12 h-12 text-slate-800" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M2 2h8v8H2V2zm2 2v4h4V4H4zm10-2h8v8h-8V2zm2 2v4h4V4h-4zM2 14h8v8H2v-8zm2 2v4h4v-4H4zm10-2h3v3h-3v-3zm5 0h3v3h-3v-3zm-5 5h3v3h-3v-3zm5 0h3v3h-3v-3z"/>
                </svg>
                <span class="text-[8px] font-mono text-slate-400 mt-0.5 font-bold">SECURE VERIFIED</span>
            </div>
        </div>

        <!-- JUDUL DOKUMEN LAPORAN EKSEKUTIF -->
        <div class="text-center pt-2">
            <span class="inline-block px-3 py-1 bg-rose-50 text-[#680015] border border-rose-200 text-xs font-black uppercase tracking-wider rounded-full mb-1.5">
                Executive Brief &bull; Institutional Reputation Report
            </span>
            <h2 class="text-xl font-black text-slate-900 uppercase tracking-tight">
                LAPORAN REKAPITULASI CAPAIAN REPUTASI &amp; BRANDING INSTITUSI
            </h2>
            <div class="text-xs font-bold text-slate-600 mt-0.5">
                Bahan Evidensi Pemeringkatan: <strong>WURI &bull; Webometrics &bull; Uniranks &bull; Akreditasi Unggul</strong>
            </div>
            
            <div class="mt-3 flex flex-wrap items-center justify-center gap-x-6 gap-y-1 text-xs text-slate-500 bg-slate-50 p-2.5 rounded-xl border border-slate-200/80">
                <span><strong>No. Dokumen:</strong> <span class="font-mono text-slate-700"><?= htmlspecialchars($docNumber) ?></span></span>
                <span>&bull;</span>
                <span><strong>Periode:</strong> <?= htmlspecialchars($periodName) ?></span>
                <span>&bull;</span>
                <span><strong>Cakupan Unit:</strong> <?= htmlspecialchars($unitName) ?></span>
                <span>&bull;</span>
                <span><strong>Tanggal Cetak:</strong> <?= date('d F Y, H:i') ?> WIB</span>
            </div>
        </div>

        <!-- STATISTIK RINGKAS CAPAIAN -->
        <div class="grid grid-cols-4 gap-3 text-xs">
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-center">
                <span class="text-slate-500 block text-[10px] uppercase font-bold">Total Entri Capaian</span>
                <span class="text-xl font-black text-slate-900"><?= $totalData ?></span>
                <span class="text-[10px] text-slate-400 block">4 Unit Strategis</span>
            </div>
            <div class="p-3 bg-rose-50/70 rounded-xl border border-rose-200 text-center">
                <span class="text-rose-900 block text-[10px] uppercase font-bold">Capaian Signature</span>
                <span class="text-xl font-black text-[#680015]"><?= $totalSignature ?></span>
                <span class="text-[10px] text-rose-700 block">Kategori Prestisius</span>
            </div>
            <div class="p-3 bg-amber-50/70 rounded-xl border border-amber-200 text-center">
                <span class="text-amber-900 block text-[10px] uppercase font-bold">Lolos Screening</span>
                <span class="text-xl font-black text-amber-700"><?= $totalSiap ?></span>
                <span class="text-[10px] text-amber-600 block">Siap Diolah Komunikasi</span>
            </div>
            <div class="p-3 bg-emerald-50/70 rounded-xl border border-emerald-200 text-center">
                <span class="text-emerald-900 block text-[10px] uppercase font-bold">Rilis Publikasi Media</span>
                <span class="text-xl font-black text-emerald-700"><?= $totalTerbit ?></span>
                <span class="text-[10px] text-emerald-600 block">Tayang di Media Resmi</span>
            </div>
        </div>

        <!-- TABEL RINCIAN CAPAIAN LENGKAP -->
        <div class="border border-slate-200 rounded-2xl overflow-hidden shadow-sm pt-2">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-[#680015] text-white font-extrabold text-[11px]">
                        <th class="p-2.5 w-10 text-center border-r border-rose-900">ID</th>
                        <th class="p-2.5 w-24 border-r border-rose-900">Unit &amp; Tier</th>
                        <th class="p-2.5 w-24 border-r border-rose-900">Bulan</th>
                        <th class="p-2.5 border-r border-rose-900">Judul Capaian &amp; Narasi Ringkas</th>
                        <th class="p-2.5 w-36 border-r border-rose-900">Subjek / Inovator</th>
                        <th class="p-2.5 w-24 text-center border-r border-rose-900">Screening</th>
                        <th class="p-2.5 w-24 text-center border-r border-rose-900">Komunikasi</th>
                        <th class="p-2.5 w-40">Tautan Rilis Media</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-[11px]">
                    <?php if (empty($entries)): ?>
                        <tr>
                            <td colspan="8" class="p-6 text-center text-slate-400 italic">
                                Tidak ada data capaian yang sesuai dengan kriteria filter.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($entries as $e): 
                            $isSignature = ($e['tier_type'] === 'SIGNATURE');
                            $isRestricted = ($e['name_publication_consent'] === 'TIDAK') || $maskPII;

                            $subj = '-';
                            if ($e['unit_code'] === 'PPM') {
                                $invs = array_map(fn($i) => $i['name'], $e['details']['inventors'] ?? []);
                                $subj = implode(', ', $invs);
                            } elseif ($e['unit_code'] === 'PSDM') {
                                $subj = $e['details']['employee_name'] ?? '-';
                            } elseif ($e['unit_code'] === 'KEMAHASISWAAN') {
                                $subj = $e['details']['student_name'] ?? '-';
                            } elseif ($e['unit_code'] === 'ALUMNI_KARIR') {
                                $subj = $e['details']['alumni_name'] ?? '-';
                            }

                            $displaySubj = $isRestricted ? Helpers::maskNameIfRestricted($subj, 'TIDAK', false) : $subj;
                        ?>
                            <tr class="<?= $isSignature ? 'bg-rose-50/25' : 'hover:bg-slate-50' ?>">
                                <td class="p-2.5 text-center font-bold text-slate-400 border-r border-slate-200">#<?= $e['id'] ?></td>
                                <td class="p-2.5 border-r border-slate-200">
                                    <div class="font-extrabold text-slate-900"><?= htmlspecialchars($e['unit_code']) ?></div>
                                    <span class="inline-block mt-0.5 px-1.5 py-0.2 rounded text-[9px] font-black <?= $isSignature ? 'bg-rose-100 text-[#680015] border border-rose-300' : 'bg-slate-100 text-slate-600' ?>">
                                        <?= $e['tier_type'] ?>
                                    </span>
                                </td>
                                <td class="p-2.5 border-r border-slate-200 whitespace-nowrap text-slate-600 font-mono text-[10px]">
                                    <?= htmlspecialchars($e['period_name'] ?? $e['period_id']) ?>
                                </td>
                                <td class="p-2.5 border-r border-slate-200">
                                    <div class="font-bold text-slate-900 text-xs leading-snug">
                                        <?= htmlspecialchars($e['title']) ?>
                                    </div>
                                    <div class="text-[10px] text-slate-600 mt-1 leading-relaxed">
                                        <?= htmlspecialchars($e['short_description']) ?>
                                    </div>
                                </td>
                                <td class="p-2.5 border-r border-slate-200 font-medium text-slate-700">
                                    <?= htmlspecialchars($displaySubj) ?>
                                    <?php if ($e['name_publication_consent'] === 'TIDAK'): ?>
                                        <span class="block text-[9px] text-amber-700 font-bold">(Dilindungi UU PDP)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-2.5 border-r border-slate-200 text-center whitespace-nowrap">
                                    <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold <?= ($e['screening_status'] === 'SIAP_DIOLAH') ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' ?>">
                                        <?= $e['screening_status'] ?>
                                    </span>
                                </td>
                                <td class="p-2.5 border-r border-slate-200 text-center whitespace-nowrap">
                                    <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold <?= ($e['comm_status'] === 'SUDAH_TERBIT') ? 'bg-emerald-100 text-emerald-800' : (($e['comm_status'] === 'SEDANG_DIOLAH') ? 'bg-rose-100 text-[#680015]' : 'bg-amber-100 text-amber-800') ?>">
                                        <?= $e['comm_status'] ?>
                                    </span>
                                </td>
                                <td class="p-2.5 text-slate-600 break-all text-[10px]">
                                    <?php if (!empty($e['comm_published_url'])): ?>
                                        <a href="<?= htmlspecialchars($e['comm_published_url']) ?>" class="text-[#680015] font-bold underline">
                                            <?= htmlspecialchars($e['comm_published_url']) ?>
                                        </a>
                                    <?php elseif (!empty($e['external_url'])): ?>
                                        <a href="<?= htmlspecialchars($e['external_url']) ?>" class="text-blue-700 underline">
                                            <?= htmlspecialchars($e['external_url']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-slate-400 italic">Arsip Internal</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- LEMBAR PENGESAHAN DOKUMEN RESMI (LEGALITAS & VERIFIKASI) -->
        <div class="pt-6 border-t border-slate-200 text-xs">
            <div class="text-[11px] text-slate-500 italic mb-4">
                Dokumen ini merupakan salinan sah data capaian tridarma dan reputasi institusi Universitas Harkat Negeri yang diinventarisasi oleh 4 Unit Kerja Strategis (PPM, PSDM, Kemahasiswaan, dan Alumni Karir) untuk keperluan evaluasi berkala Rektorat dan pelaporan akreditasi institusi.
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 items-end pt-2">
                <!-- Pihak Menyetujui: Ketua Task Force -->
                <div class="text-left space-y-1">
                    <div class="text-slate-500 font-semibold">Menyetujui,</div>
                    <div class="font-bold text-slate-900 leading-tight">Ketua Tim Task Force Kerjasama &amp; Reputasi Institusi</div>
                    
                    <!-- Cap Digital Signature Task Force -->
                    <div class="py-2">
                        <div class="inline-flex items-center gap-2 p-2 bg-rose-50 border border-rose-200 rounded-xl">
                            <div class="w-7 h-7 rounded-lg bg-rose-100 text-[#680015] flex items-center justify-center font-bold text-xs">
                                ✓
                            </div>
                            <div class="text-[9px] text-slate-600 leading-tight">
                                <span class="font-extrabold text-[#680015] block uppercase">TTE Terverifikasi</span>
                                <span class="font-mono text-slate-500">ID: <?= substr($securityHash, 0, 8) ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="font-bold text-slate-900 underline text-sm">Dr. Hendra Gunawan, S.Kom., M.T.</div>
                    <div class="text-slate-500 text-[11px]">NIP. 19780512 200312 1 002</div>
                </div>

                <!-- Bagian Tengah: QR Code & Security Stamp -->
                <div class="text-center space-y-2 p-3 bg-slate-50/80 rounded-2xl border border-slate-200/80">
                    <div class="text-[9px] uppercase tracking-wider font-extrabold text-slate-400">Verifikasi Integritas Data</div>
                    <div class="inline-block p-1.5 bg-white border border-slate-200 rounded-xl shadow-xs">
                        <svg class="w-16 h-16 text-slate-900 mx-auto" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M2 2h8v8H2V2zm2 2v4h4V4H4zm10-2h8v8h-8V2zm2 2v4h4V4h-4zM2 14h8v8H2v-8zm2 2v4h4v-4H4zm10-2h3v3h-3v-3zm5 0h3v3h-3v-3zm-5 5h3v3h-3v-3zm5 0h3v3h-3v-3z"/>
                        </svg>
                    </div>
                    <div class="text-[9px] font-mono text-slate-500">
                        Kode Hash: <strong><?= $securityHash ?></strong>
                    </div>
                    <div class="text-[8px] text-slate-400">
                        Pindai QR untuk memeriksa keaslian arsip di server ERP UHN
                    </div>
                </div>

                <!-- Pihak Mengetahui: Rektor -->
                <div class="text-right space-y-1">
                    <div class="text-slate-500 font-semibold">Kota Mandiri, <?= date('d F Y') ?></div>
                    <div class="text-slate-500 font-semibold">Mengetahui,</div>
                    <div class="font-bold text-slate-900 leading-tight">Rektor Universitas Harkat Negeri</div>
                    
                    <!-- Cap Stempel Resmi Rektorat UHN -->
                    <div class="py-2 flex justify-end">
                        <div class="inline-flex items-center gap-2 p-2 bg-amber-50/80 border border-amber-200 rounded-xl">
                            <div class="w-7 h-7 rounded-lg bg-amber-100 text-amber-800 flex items-center justify-center font-bold text-xs">
                                ★
                            </div>
                            <div class="text-[9px] text-left text-slate-600 leading-tight">
                                <span class="font-extrabold text-amber-900 block uppercase">Cap Stempel Rektorat</span>
                                <span class="font-mono text-slate-500">Sah &bull; UHN-REK-<?= date('Y') ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="font-bold text-slate-900 underline text-sm">Prof. Dr. Ir. H. Mulyadi, M.Sc.</div>
                    <div class="text-slate-500 text-[11px]">NIP. 19650410 199003 1 001</div>
                </div>
            </div>
        </div>

        <!-- FOOTER DOKUMEN -->
        <div class="text-center text-[10px] text-slate-400 pt-4 border-t border-slate-100 flex items-center justify-between">
            <span>Sistem Informasi Koordinasi Task Force Reputasi &copy; 2026 Universitas Harkat Negeri</span>
            <span class="font-mono">Lembar Verifikasi Resmi &bull; Rekapitulasi Capaian Institusi</span>
        </div>

    </div>

    <script>
        <?php if (!empty($_GET['auto_print'])): ?>
            window.addEventListener('load', function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            });
        <?php endif; ?>
    </script>
</body>
</html>
