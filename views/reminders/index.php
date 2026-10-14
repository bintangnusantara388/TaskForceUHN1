<?php
// views/reminders/index.php
require_once __DIR__ . '/../../app/Models/Reminder.php';
require_once __DIR__ . '/../../app/Config.php';
require_once __DIR__ . '/../../app/Auth.php';
require_once __DIR__ . '/../../app/Helpers.php';

// Hak Akses Eksklusif Administrator Task Force
if (!Auth::isAdmin()) {
    echo '<div class="p-8 bg-rose-50 border border-rose-200 text-uhn-900 rounded-3xl font-bold">
        <h3 class="text-base font-black">Akses Terbatas: Khusus Administrator Task Force</h3>
        <p class="text-xs text-rose-800 mt-1">Sesuai kebijakan tata kelola institusi, pengingat target capaian hanya memiliki wewenang untuk diakses dan dijalankan oleh Administrator Task Force langsung ke PIC unit masing-masing.</p>
    </div>';
    return;
}

$activePeriod = $_GET['period_id'] ?? '2026-10';
$unitDetails = Reminder::getUnitPICDetails($activePeriod);
$history = Reminder::getNotificationHistory(30);

// Hitung unit yang belum terpenuhi
$unfulfilledCount = 0;
$unfulfilledNames = [];
foreach ($unitDetails as $uCode => $d) {
    if (!$d['is_signature_fulfilled']) {
        $unfulfilledCount++;
        $unfulfilledNames[] = "{$d['pic_name']} ({$uCode})";
    }
}
$unfulfilledStr = !empty($unfulfilledNames) ? implode(', ', $unfulfilledNames) : 'Seluruh unit telah memenuhi target!';
?>

<div class="space-y-6">

    <!-- Header Box (Crimson Modern Header Khusus Administrator) -->
    <div class="bg-white rounded-3xl shadow-sm border border-rose-100 p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-rose-100 text-uhn-900 text-xs font-bold border border-rose-200 mb-1">
                <span class="w-2 h-2 rounded-full bg-rose-600 animate-pulse"></span>
                <span>Wewenang Eksklusif Administrator Task Force</span>
                <span class="text-slate-300">&bull;</span>
                <span>Saluran Direct Email &amp; WhatsApp PIC Unit</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 mt-1">
                Pengingat Target Kepatuhan Signature Bulanan
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl leading-relaxed">
                Setiap unit kerja wajib menyetorkan minimal <strong>1 data Signature</strong> per bulan untuk pemenuhan borang pemeringkatan WURI dan siaran media nasional. Kirimkan pengingat langsung ke alamat email dan nomor WhatsApp resmi PIC masing-masing.
            </p>
        </div>

        <form action="/index.php" method="GET" class="flex items-center gap-2 bg-slate-50 p-2 rounded-2xl border border-rose-100 self-start md:self-auto flex-shrink-0">
            <input type="hidden" name="page" value="reminders">
            <label class="text-xs font-bold text-slate-600 pl-2">Bulan:</label>
            <select name="period_id" onchange="this.form.submit()" 
                    class="bg-white border-0 text-slate-900 text-xs font-bold rounded-xl px-3 py-2 focus:ring-2 focus:ring-uhn-700 shadow-sm cursor-pointer">
                <?php foreach (PERIODS as $pid => $pname): ?>
                    <option value="<?= $pid ?>" <?= ($pid === $activePeriod) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($pname) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <!-- Banner Ringkasan Status Kepatuhan PIC -->
    <div class="bg-gradient-to-r from-uhn-950 via-uhn-900 to-uhn-800 text-white rounded-3xl p-5 sm:p-6 shadow-md border border-uhn-900 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-rose-200">
                <span class="font-bold">Periode: <?= htmlspecialchars(PERIODS[$activePeriod]) ?></span>
                <span>&bull;</span>
                <span>Target: Minimal 1 Signature / Unit</span>
            </div>
            <h2 class="text-lg sm:text-xl font-black text-white mt-1">
                <?= (4 - $unfulfilledCount) ?> dari 4 Unit Telah Memenuhi Target
            </h2>
            <p class="text-xs text-rose-100/80 mt-0.5">
                <?php if ($unfulfilledCount > 0): ?>
                    <span class="text-rose-300 font-bold"><?= $unfulfilledCount ?> unit belum setor:</span> <?= htmlspecialchars($unfulfilledStr) ?>
                <?php else: ?>
                    <span class="text-emerald-300 font-bold">Luar biasa! Seluruh PIC unit telah menyetorkan data capaian Signature.</span>
                <?php endif; ?>
            </p>
        </div>

        <!-- Tombol Aksi Massal Administrator -->
        <div class="flex flex-wrap items-center gap-2 self-start md:self-auto">
            <?php if ($unfulfilledCount > 0): ?>
                <form action="/index.php?action=reminders_trigger" method="POST" class="inline"
                      onsubmit="return confirm('Kirimkan email pengingat resmi ke seluruh PIC yang belum setor signature?');">
                    <input type="hidden" name="period_id" value="<?= htmlspecialchars($activePeriod) ?>">
                    <input type="hidden" name="phase" value="MID_MONTH">
                    <button type="submit" 
                            class="px-4 py-2.5 bg-gradient-to-r from-rose-600 to-rose-700 hover:from-rose-700 hover:to-rose-800 text-white rounded-xl text-xs font-bold shadow-md shadow-rose-950/20 transition-all flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-rose-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Email ke Semua PIC Belum Setor (<?= $unfulfilledCount ?>)
                    </button>
                </form>

                <form action="/index.php?action=reminders_trigger" method="POST" class="inline"
                      onsubmit="return confirm('Kirimkan email darurat akhir bulan ke seluruh PIC yang belum setor signature?');">
                    <input type="hidden" name="period_id" value="<?= htmlspecialchars($activePeriod) ?>">
                    <input type="hidden" name="phase" value="END_MONTH">
                    <button type="submit" 
                            class="px-3.5 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 rounded-xl text-xs font-bold shadow-sm transition-all flex items-center gap-1.5"
                            title="Pengingat fase akhir bulan (Tgl 26)">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        Fase Akhir Bulan
                    </button>
                </form>
            <?php else: ?>
                <div class="px-4 py-2 bg-emerald-500/20 text-emerald-200 border border-emerald-400/30 rounded-xl text-xs font-bold flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Semua Unit Telah Patuh
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- KARTU DIRECT EMAIL & WHATSAPP KE PIC UNIT MASING-MASING -->
    <!-- ========================================================================= -->
    <div>
        <div class="flex items-center justify-between mb-3 px-1">
            <h2 class="text-sm font-black text-slate-800 uppercase tracking-wider flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-uhn-800"></span>
                Daftar Kontak Direct PIC &amp; Status Kepatuhan Unit
            </h2>
            <span class="text-xs text-slate-400 font-medium">Kirim langsung via Email institusi atau WhatsApp personal</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php foreach ($unitDetails as $uCode => $pic): 
                $isFulfilled = $pic['is_signature_fulfilled'];
            ?>
                <div class="bg-white rounded-3xl border <?= $isFulfilled ? 'border-emerald-200' : 'border-rose-200 ring-2 ring-rose-100' ?> p-5 flex flex-col justify-between shadow-sm hover:shadow-md transition-all space-y-4">
                    
                    <!-- Header Kartu: Unit Code & Status Badge -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <span class="px-2.5 py-1 rounded-full text-xs font-black bg-rose-100 text-uhn-900 border border-rose-200">
                                <?= $uCode ?>
                            </span>
                            <?php if ($isFulfilled): ?>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 flex items-center gap-1">
                                    <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    Terpenuhi
                                </span>
                            <?php else: ?>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-800 border border-rose-300 animate-pulse flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span>
                                    Belum Setor
                                </span>
                            <?php endif; ?>
                        </div>

                        <div>
                            <h3 class="font-extrabold text-slate-900 text-sm leading-snug">
                                <?= htmlspecialchars($pic['unit_name']) ?>
                            </h3>
                            <div class="text-[11px] text-slate-500 mt-1 flex items-center justify-between">
                                <span>Capaian Signature:</span>
                                <strong class="font-bold <?= $isFulfilled ? 'text-emerald-700' : 'text-rose-700' ?>"><?= $pic['signature_count'] ?> Data</strong>
                            </div>
                            <div class="text-[11px] text-slate-500 flex items-center justify-between">
                                <span>Total Data Masuk:</span>
                                <span class="font-bold text-slate-700"><?= $pic['total_entries'] ?> Data</span>
                            </div>
                        </div>

                        <!-- Profil Kontak PIC Unit -->
                        <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100 space-y-1.5 text-xs">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-xl bg-gradient-to-tr from-uhn-950 to-uhn-800 text-white flex items-center justify-center font-bold text-[11px] flex-shrink-0">
                                    <?= strtoupper(substr($pic['pic_name'], 0, 1)) ?>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="font-black text-slate-900 text-xs truncate" title="<?= htmlspecialchars($pic['pic_name']) ?>">
                                        <?= htmlspecialchars($pic['pic_name']) ?>
                                    </div>
                                    <div class="text-[10px] text-slate-400 font-mono">
                                        PIC Resmi Unit
                                    </div>
                                </div>
                            </div>

                            <!-- Email PIC -->
                            <div class="pt-1 border-t border-slate-200/60 flex items-center justify-between text-[11px]">
                                <span class="text-slate-400 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    Email:
                                </span>
                                <a href="mailto:<?= htmlspecialchars($pic['pic_email']) ?>" class="font-mono font-bold text-uhn-800 hover:underline truncate max-w-[140px]" title="<?= htmlspecialchars($pic['pic_email']) ?>">
                                    <?= htmlspecialchars($pic['pic_email']) ?>
                                </a>
                            </div>

                            <!-- WA PIC -->
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="text-slate-400 flex items-center gap-1">
                                    <svg class="w-3 h-3 text-emerald-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.664-.698c.99.54 1.787.829 2.796.829 3.183 0 5.77-2.587 5.77-5.766.001-3.187-2.575-5.77-5.77-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86.173.086.275.072.376-.044.101-.116.433-.506.549-.68.116-.173.231-.145.39-.086.159.058 1.011.477 1.184.564.173.087.289.13.332.202.043.073.043.419-.101.824z"/></svg>
                                    WA:
                                </span>
                                <span class="font-mono font-bold text-slate-700">
                                    <?= htmlspecialchars($pic['pic_phone_display']) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Aksi Direct (Email & WA ke PIC) -->
                    <div class="space-y-2 pt-2 border-t border-slate-100">
                        <!-- 1. Tombol Direct WhatsApp -->
                        <a href="<?= $pic['wa_link'] ?>" target="_blank" 
                           class="w-full py-2.5 px-3 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white rounded-xl text-xs font-bold transition-all shadow-sm flex items-center justify-center gap-1.5"
                           title="Buka WhatsApp langsung ke <?= htmlspecialchars($pic['pic_name']) ?>">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.664-.698c.99.54 1.787.829 2.796.829 3.183 0 5.77-2.587 5.77-5.766.001-3.187-2.575-5.77-5.77-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86.173.086.275.072.376-.044.101-.116.433-.506.549-.68.116-.173.231-.145.39-.086.159.058 1.011.477 1.184.564.173.087.289.13.332.202.043.073.043.419-.101.824z"/></svg>
                            <span>Chat WA PIC</span>
                        </a>

                        <!-- 2. Tombol Direct Email -->
                        <form action="/index.php?action=reminders_send_single" method="POST"
                              onsubmit="return confirm('Kirimkan email pengingat target langsung ke PIC <?= $uCode ?> (<?= htmlspecialchars($pic['pic_email']) ?>)?');">
                            <input type="hidden" name="unit_code" value="<?= $uCode ?>">
                            <input type="hidden" name="period_id" value="<?= htmlspecialchars($activePeriod) ?>">
                            <input type="hidden" name="channel" value="EMAIL">
                            <button type="submit" 
                                    class="w-full py-2.5 px-3 bg-gradient-to-r from-uhn-800 to-uhn-700 hover:from-uhn-900 hover:to-uhn-800 active:scale-95 text-white rounded-xl text-xs font-bold transition-all shadow-sm flex items-center justify-center gap-1.5"
                                    title="Kirim email langsung ke <?= htmlspecialchars($pic['pic_email']) ?>">
                                <svg class="w-4 h-4 text-rose-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                <span>Kirim Email PIC</span>
                            </button>
                        </form>

                        <!-- 3. Tombol Salin Format WA -->
                        <button type="button" 
                                onclick="navigator.clipboard.writeText(<?= htmlspecialchars(json_encode($pic['wa_message'])) ?>); alert('Teks WhatsApp untuk PIC <?= $uCode ?> (<?= addslashes($pic['pic_name']) ?>) berhasil disalin ke clipboard!');"
                                class="w-full py-1.5 px-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-[11px] font-bold transition-colors text-center">
                            Salin Teks Format WA
                        </button>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TEMPLATE PESAN RESMI (WHATSAPP & NOTA DINAS EMAIL) -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Pratinjau Pesan WhatsApp Personal -->
        <div class="bg-white rounded-3xl shadow-sm border border-rose-100 p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        <h3 class="text-sm font-bold text-slate-900">Format Personalisasi Pesan WhatsApp PIC</h3>
                    </div>
                    <span class="text-[10px] font-bold bg-emerald-100 text-emerald-800 px-2.5 py-0.5 rounded-full">WhatsApp Direct</span>
                </div>
                <p class="text-xs text-slate-500 mb-3">
                    Setiap tombol <em>"Chat WA PIC"</em> di atas secara otomatis menyisipkan nama lengkap PIC, unit yang bersangkutan, serta tautan langsung modul unit ke WhatsApp:
                </p>
                <div class="bg-slate-50/80 border border-slate-200 rounded-2xl p-4 font-mono text-xs text-slate-800 leading-relaxed whitespace-pre-wrap select-all">🔔 *PENGINGAT TARGET SIGNATURE BULANAN UHN*
Periode: *<?= htmlspecialchars(PERIODS[$activePeriod]) ?>*

Yth. Bapak/Ibu *[Nama Lengkap PIC Unit]*,
Selaku PIC Unit *[Nama Unit Kerja]* Universitas Harkat Negeri.

Berdasarkan rekapitulasi data ERP Task Force per <?= date('d F Y') ?>, unit Anda tercatat *BELUM MENYETORKAN CAPAIAN SIGNATURE* (capaian unggulan) untuk bulan <?= htmlspecialchars(PERIODS[$activePeriod]) ?>.

📌 *Ketentuan Wajib:*
1. Setiap unit kerja wajib menyetor minimal 1 data Signature setiap bulan.
2. Mohon pastikan data telah lolos screening unit (*SIAP DIOLAH*) agar langsung diproduksi oleh Bagian Komunikasi.

🔗 *Tautan Input Langsung:*
<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost:8088') ?>/index.php?page=entries_create&unit_code=[UNIT]

_Administrator Tim Task Force Kerjasama & Reputasi Institusi UHN_</div>
            </div>

            <div class="mt-4 pt-3 border-t border-rose-100 text-right">
                <span class="text-xs text-slate-400 font-mono">Direct API https://wa.me/{clean_phone}</span>
            </div>
        </div>

        <!-- Pratinjau Email Resmi Institusi -->
        <div class="bg-white rounded-3xl shadow-sm border border-rose-100 p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-uhn-800"></span>
                        <h3 class="text-sm font-bold text-slate-900">Format Nota Email Langsung PIC</h3>
                    </div>
                    <span class="text-[10px] font-bold bg-rose-100 text-uhn-900 px-2.5 py-0.5 rounded-full">Email Direct</span>
                </div>
                <p class="text-xs text-slate-500 mb-3">
                    Notifikasi email institusi resmi terkirim langsung ke akun email `@harkatnegeri.ac.id` masing-masing PIC unit:
                </p>
                <div class="bg-slate-50/80 border border-slate-200 rounded-2xl p-4 text-xs text-slate-800 leading-relaxed font-sans whitespace-pre-wrap">
<strong>UNIVERSITAS HARKAT NEGERI</strong>
<em>Tim Task Force Kerjasama Internasional &amp; Reputasi Institusi</em>
Perihal: [URGENT] Pengingat Penyetoran Data Signature Periode <?= htmlspecialchars(PERIODS[$activePeriod]) ?>

Yth. Bapak/Ibu PIC Unit Kerja,
Menindaklanjuti program akselerasi reputasi universitas pada pemeringkatan WURI, Uniranks, dan Webometrics, kami mengimbau unit kerja yang belum menyetor data unggulan (Signature) bulan <?= htmlspecialchars(PERIODS[$activePeriod]) ?> untuk segera melakukan input data melalui portal ERP.

Data capaian yang diinput akan langsung diproses oleh Bagian Komunikasi menjadi siaran media massa nasional dan evidensi akreditasi.</div>
            </div>

            <div class="mt-4 pt-3 border-t border-rose-100 text-right">
                <span class="text-xs text-slate-400 italic">Dicatat di log audit dan tabel reminder_notifications</span>
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- RIWAYAT PENGIRIMAN NOTIFIKASI PENGINGAT SISTEM -->
    <!-- ========================================================================= -->
    <div class="bg-white rounded-3xl shadow-sm border border-rose-100 overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-uhn-950 via-uhn-900 to-uhn-800 text-white flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h3 class="text-sm font-bold flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                    Riwayat Pengiriman Notifikasi Pengingat oleh Administrator
                </h3>
                <p class="text-[11px] text-rose-200/80">Jejak akuntabilitas pengiriman email &amp; pesan WhatsApp langsung ke PIC unit</p>
            </div>
            <span class="text-xs text-rose-200 font-mono">Terekam di Basis Data</span>
        </div>

        <!-- Mobile Card View (< md) -->
        <div class="md:hidden divide-y divide-rose-100">
            <?php if (empty($history)): ?>
                <div class="p-8 text-center text-slate-400 text-xs italic">
                    Belum ada riwayat pengiriman pengingat. Gunakan tombol di atas untuk mengirimkan pengingat ke PIC.
                </div>
            <?php else: ?>
                <?php foreach ($history as $h): ?>
                    <div class="p-4 space-y-2 hover:bg-rose-50/20 transition-colors text-xs">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-1.5">
                                <span class="font-bold text-slate-900 bg-rose-100 px-2 py-0.5 rounded text-[10px] text-uhn-900">
                                    <?= htmlspecialchars($h['unit_code']) ?>
                                </span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= ($h['channel'] === 'WHATSAPP') ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800' ?>">
                                    <?= htmlspecialchars($h['channel'] ?? 'EMAIL') ?>
                                </span>
                            </div>
                            <span class="font-mono text-[10px] text-slate-400">
                                <?= htmlspecialchars($h['sent_at']) ?>
                            </span>
                        </div>

                        <div>
                            <div class="font-bold text-slate-800"><?= htmlspecialchars($h['recipient_name'] ?? $h['recipient_email']) ?></div>
                            <div class="text-[11px] text-slate-500 font-mono"><?= htmlspecialchars($h['recipient_email']) ?> <?= !empty($h['recipient_phone']) ? '&bull; ' . htmlspecialchars($h['recipient_phone']) : '' ?></div>
                        </div>

                        <div class="flex items-center justify-between pt-1 text-[11px]">
                            <span class="text-slate-500">Periode: <strong><?= htmlspecialchars($h['period_name'] ?? $h['period_id']) ?></strong></span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <?= htmlspecialchars($h['status']) ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Desktop Table View (>= md) -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700">
                <thead class="bg-rose-50/50 text-slate-500 uppercase font-bold text-[11px] border-b border-rose-100">
                    <tr>
                        <th class="px-6 py-3.5">Waktu Kirim</th>
                        <th class="px-4 py-3.5">Periode</th>
                        <th class="px-4 py-3.5">Unit Kerja</th>
                        <th class="px-5 py-3.5">PIC Penerima</th>
                        <th class="px-4 py-3.5">Kanal</th>
                        <th class="px-4 py-3.5">Fase</th>
                        <th class="px-6 py-3.5 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-rose-50">
                    <?php if (empty($history)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-slate-400 italic">
                                Belum ada riwayat notifikasi pengingat yang tersimpan. Gunakan tombol aksi di atas untuk mengirimkan pengingat ke PIC.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($history as $h): ?>
                            <tr class="hover:bg-rose-50/30 transition-colors">
                                <td class="px-6 py-3.5 whitespace-nowrap text-slate-500 font-mono text-[11px]">
                                    <?= htmlspecialchars($h['sent_at']) ?>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap font-medium text-slate-700">
                                    <?= htmlspecialchars($h['period_name'] ?? $h['period_id']) ?>
                                </td>
                                <td class="px-4 py-3.5 font-bold text-slate-900">
                                    <span class="bg-rose-100 text-uhn-900 px-2 py-0.5 rounded text-[10px] font-black border border-rose-200">
                                        <?= htmlspecialchars($h['unit_code']) ?>
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="font-bold text-slate-900"><?= htmlspecialchars($h['recipient_name'] ?? $h['recipient_email']) ?></div>
                                    <div class="text-[10px] text-slate-400 font-mono"><?= htmlspecialchars($h['recipient_email']) ?> <?= !empty($h['recipient_phone']) ? '&bull; ' . htmlspecialchars($h['recipient_phone']) : '' ?></div>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <span class="px-2.5 py-0.5 rounded-full font-bold text-[10px] <?= ($h['channel'] === 'WHATSAPP') ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800' ?>">
                                        <?= htmlspecialchars($h['channel'] ?? 'EMAIL') ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
                                        <?= htmlspecialchars($h['reminder_phase'] ?? '-') ?>
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 text-right whitespace-nowrap">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        TERKIRIM (<?= htmlspecialchars($h['status']) ?>)
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
