<?php
// views/layout/footer.php
$currentPage = $_GET['page'] ?? 'dashboard';
?>
    </main>

    <!-- Footer Institusi (Desktop) -->
    <footer class="bg-white border-t border-rose-100 mt-auto hidden md:block">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="md:flex md:items-center md:justify-between text-xs text-slate-500">
                <div class="flex items-center space-x-2">
                    <span class="font-bold text-uhn-900"><?= APP_INSTITUTION ?></span>
                    <span>&bull;</span>
                    <span><?= APP_TASKFORCE ?></span>
                </div>
                <div class="mt-3 md:mt-0 flex items-center space-x-4">
                    <span>Target Borang: WURI &bull; Uniranks &bull; Webometrics</span>
                    <span>&bull;</span>
                    <span class="text-slate-400">Periode Aktif: Sep 2026 - Agu 2027</span>
                </div>
            </div>
        </div>
    </footer>

    </div> <!-- Penutup Flex-1 Main Content Wrapper dari header.php -->

    <!-- ========================================================================= -->
    <!-- MOBILE BOTTOM NAVIGATION BAR (Native App Ergonomics & Safe-Area Support) -->
    <!-- ========================================================================= -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-lg border-t border-rose-100 text-slate-500 shadow-[0_-8px_30px_rgba(104,0,21,0.08)] safe-area-bottom">
        <div class="grid grid-cols-5 h-16 items-center px-1">
            
            <!-- 1. Beranda / Dashboard -->
            <a href="/index.php?page=dashboard" class="relative flex flex-col items-center justify-center h-full transition-all active:scale-95 <?= ($currentPage === 'dashboard') ? 'text-uhn-800 font-extrabold' : 'text-slate-400 hover:text-uhn-700' ?>">
                <?php if ($currentPage === 'dashboard'): ?>
                    <span class="absolute top-0 w-8 h-1 rounded-b-full bg-uhn-800"></span>
                <?php endif; ?>
                <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="<?= ($currentPage === 'dashboard') ? '2.5' : '2' ?>" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span class="text-[10px] tracking-tight">Beranda</span>
            </a>

            <!-- 2. Data Capaian -->
            <a href="/index.php?page=entries" class="relative flex flex-col items-center justify-center h-full transition-all active:scale-95 <?= (str_starts_with($currentPage, 'entries')) ? 'text-uhn-800 font-extrabold' : 'text-slate-400 hover:text-uhn-700' ?>">
                <?php if (str_starts_with($currentPage, 'entries')): ?>
                    <span class="absolute top-0 w-8 h-1 rounded-b-full bg-uhn-800"></span>
                <?php endif; ?>
                <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="<?= (str_starts_with($currentPage, 'entries')) ? '2.5' : '2' ?>" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                <span class="text-[10px] tracking-tight">Capaian</span>
            </a>

            <!-- 3. Tombol Tengah: Smart RBAC Quick Action Floating Pill -->
            <div class="flex flex-col items-center justify-center">
                <?php if (Auth::isPIC() && Auth::unit()): ?>
                    <!-- PIC Unit langsung ke form unitnya (1-Tap Input) -->
                    <a href="/index.php?page=entries_create&unit_code=<?= urlencode(Auth::unit()) ?>" 
                       class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-uhn-950 via-uhn-800 to-rose-600 text-white flex items-center justify-center font-black shadow-lg shadow-rose-900/35 ring-4 ring-white transform -translate-y-3 active:scale-90 transition-all"
                       title="Input Capaian <?= htmlspecialchars(Auth::unit()) ?>">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    </a>
                <?php elseif (Auth::canCreateEntry()): ?>
                    <!-- Admin membuka pilihan 4 unit -->
                    <button type="button" onclick="document.getElementById('mobileQuickAddModal').classList.remove('hidden')" 
                            class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-uhn-950 via-uhn-800 to-rose-600 text-white flex items-center justify-center font-black shadow-lg shadow-rose-900/35 ring-4 ring-white transform -translate-y-3 active:scale-90 transition-all focus:outline-none"
                            title="Tambah Capaian">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    </button>
                <?php elseif (Auth::isKomunikasi()): ?>
                    <a href="/index.php?page=komunikasi" 
                       class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-uhn-950 via-rose-700 to-rose-500 text-white flex items-center justify-center font-black shadow-lg shadow-rose-900/35 ring-4 ring-white transform -translate-y-3 active:scale-90 transition-all"
                       title="Kurasi Komunikasi">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </a>
                <?php else: ?>
                    <a href="/index.php?page=export" 
                       class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-uhn-950 via-uhn-800 to-rose-700 text-white flex items-center justify-center font-black shadow-lg shadow-rose-900/35 ring-4 ring-white transform -translate-y-3 active:scale-90 transition-all"
                       title="Borang WURI">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </a>
                <?php endif; ?>
            </div>

            <!-- 4. Meja Komunikasi / Borang WURI -->
            <?php if (Auth::canCurateKomunikasi()): ?>
            <a href="/index.php?page=komunikasi" class="relative flex flex-col items-center justify-center h-full transition-all active:scale-95 <?= ($currentPage === 'komunikasi') ? 'text-uhn-800 font-extrabold' : 'text-slate-400 hover:text-uhn-700' ?>">
                <?php if ($currentPage === 'komunikasi'): ?>
                    <span class="absolute top-0 w-8 h-1 rounded-b-full bg-uhn-800"></span>
                <?php endif; ?>
                <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="<?= ($currentPage === 'komunikasi') ? '2.5' : '2' ?>" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                <span class="text-[10px] tracking-tight">Kurasi</span>
            </a>
            <?php else: ?>
            <a href="/index.php?page=export" class="relative flex flex-col items-center justify-center h-full transition-all active:scale-95 <?= ($currentPage === 'export') ? 'text-uhn-800 font-extrabold' : 'text-slate-400 hover:text-uhn-700' ?>">
                <?php if ($currentPage === 'export'): ?>
                    <span class="absolute top-0 w-8 h-1 rounded-b-full bg-uhn-800"></span>
                <?php endif; ?>
                <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="<?= ($currentPage === 'export') ? '2.5' : '2' ?>" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span class="text-[10px] tracking-tight">Borang</span>
            </a>
            <?php endif; ?>

            <!-- 5. Menu Akun & Profil -->
            <button type="button" onclick="document.getElementById('mobileUserModal').classList.remove('hidden')" 
                    class="relative flex flex-col items-center justify-center h-full transition-all active:scale-95 focus:outline-none <?= (in_array($currentPage, ['profile', 'users', 'audit', 'reminders'])) ? 'text-uhn-800 font-extrabold' : 'text-slate-400 hover:text-uhn-800' ?>">
                <?php if (in_array($currentPage, ['profile', 'users', 'audit', 'reminders'])): ?>
                    <span class="absolute top-0 w-8 h-1 rounded-b-full bg-uhn-800"></span>
                <?php endif; ?>
                <div class="w-5 h-5 rounded-full bg-rose-100 text-uhn-800 flex items-center justify-center text-[10px] font-black mb-0.5 border border-rose-300">
                    <?= strtoupper(substr(Auth::user()['name'] ?? 'U', 0, 1)) ?>
                </div>
                <span class="text-[10px] tracking-tight">Akun</span>
            </button>

        </div>
    </nav>

    <!-- Modal Mobile Quick Add Drawer (Bottom Sheet Style) -->
    <div id="mobileQuickAddModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white w-full max-w-md rounded-t-3xl sm:rounded-3xl p-5 sm:p-6 shadow-2xl border border-rose-100 space-y-4 safe-area-bottom">
            <!-- Handle Bar Mobile -->
            <div class="w-12 h-1.5 bg-slate-200 rounded-full mx-auto sm:hidden"></div>

            <div class="flex items-center justify-between border-b border-rose-100 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-rose-100 text-uhn-800 flex items-center justify-center font-black text-base">
                        +
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Input Data Capaian</h3>
                        <p class="text-xs text-slate-500">Pilih modul unit kerja institusi:</p>
                    </div>
                </div>
                <button type="button" onclick="document.getElementById('mobileQuickAddModal').classList.add('hidden')" 
                        class="p-2 rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 active:scale-95">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="grid grid-cols-1 gap-2.5 text-xs">
                <?php if (Auth::canManageUnit('PPM')): ?>
                <a href="/index.php?page=entries_create&unit_code=PPM" class="flex items-center gap-3 p-3.5 rounded-2xl border border-rose-100 bg-white hover:border-uhn-700 active:bg-rose-50 shadow-sm transition-all group">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs flex-shrink-0">
                        PPM
                    </div>
                    <div>
                        <div class="font-bold text-slate-900 text-sm group-hover:text-uhn-800">Modul 1: PPM (Paten &amp; HKI)</div>
                        <div class="text-[11px] text-slate-500">Paten granted, hak cipta, merek &amp; riset tridarma.</div>
                    </div>
                </a>
                <?php endif; ?>

                <?php if (Auth::canManageUnit('PSDM')): ?>
                <a href="/index.php?page=entries_create&unit_code=PSDM" class="flex items-center gap-3 p-3.5 rounded-2xl border border-rose-100 bg-white hover:border-uhn-700 active:bg-rose-50 shadow-sm transition-all group">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs flex-shrink-0">
                        SDM
                    </div>
                    <div>
                        <div class="font-bold text-slate-900 text-sm group-hover:text-uhn-800">Modul 2: PSDM (Capaian Pegawai)</div>
                        <div class="text-[11px] text-slate-500">Sertifikasi internasional, jafung &amp; narasumber.</div>
                    </div>
                </a>
                <?php endif; ?>

                <?php if (Auth::canManageUnit('KEMAHASISWAAN')): ?>
                <a href="/index.php?page=entries_create&unit_code=KEMAHASISWAAN" class="flex items-center gap-3 p-3.5 rounded-2xl border border-rose-100 bg-white hover:border-uhn-700 active:bg-rose-50 shadow-sm transition-all group">
                    <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-xs flex-shrink-0">
                        MHS
                    </div>
                    <div>
                        <div class="font-bold text-slate-900 text-sm group-hover:text-uhn-800">Modul 3: Kemahasiswaan (Prestasi)</div>
                        <div class="text-[11px] text-slate-500">Medali juara 1/2/3, kompetisi nasional &amp; global.</div>
                    </div>
                </a>
                <?php endif; ?>

                <?php if (Auth::canManageUnit('ALUMNI_KARIR')): ?>
                <a href="/index.php?page=entries_create&unit_code=ALUMNI_KARIR" class="flex items-center gap-3 p-3.5 rounded-2xl border border-rose-100 bg-white hover:border-uhn-700 active:bg-rose-50 shadow-sm transition-all group">
                    <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center font-bold text-xs flex-shrink-0">
                        ALM
                    </div>
                    <div>
                        <div class="font-bold text-slate-900 text-sm group-hover:text-uhn-800">Modul 4: Alumni Karir &amp; Bisnis</div>
                        <div class="text-[11px] text-slate-500">Kiprah profesional multinasional &amp; wirausaha.</div>
                    </div>
                </a>
                <?php endif; ?>
            </div>

            <button type="button" onclick="document.getElementById('mobileQuickAddModal').classList.add('hidden')" 
                    class="w-full py-3 bg-slate-100 text-slate-700 rounded-xl font-bold text-xs hover:bg-slate-200 transition-colors">
                Tutup
            </button>
        </div>
    </div>

    <!-- Modal Mobile Account & Quick Menu Sheet -->
    <div id="mobileUserModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white w-full max-w-md rounded-t-3xl sm:rounded-3xl p-5 sm:p-6 shadow-2xl border border-rose-100 space-y-4 safe-area-bottom">
            <!-- Handle Bar Mobile -->
            <div class="w-12 h-1.5 bg-slate-200 rounded-full mx-auto sm:hidden"></div>

            <div class="flex items-center justify-between border-b border-rose-100 pb-3">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Akun &amp; Menu Cepat</h3>
                    <p class="text-xs text-slate-500">Informasi sesi aktif &amp; pengaturan akun</p>
                </div>
                <button type="button" onclick="document.getElementById('mobileUserModal').classList.add('hidden')" 
                        class="p-2 rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 active:scale-95">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Kartu Akun Sesi Aktif (Keamanan Sesi Tunggal) -->
            <div class="p-4 rounded-2xl bg-gradient-to-br from-uhn-950 via-uhn-900 to-uhn-800 text-white space-y-2.5 text-xs shadow-md">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-white text-uhn-900 flex items-center justify-center font-black text-base shadow-sm flex-shrink-0">
                        <?= strtoupper(substr(Auth::user()['name'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="font-bold text-white text-sm truncate"><?= htmlspecialchars(Auth::user()['name'] ?? '') ?></div>
                        <div class="text-[11px] text-rose-200 font-mono truncate">@<?= htmlspecialchars(Auth::user()['username'] ?? '') ?> &bull; <?= htmlspecialchars(Auth::user()['email'] ?? '') ?></div>
                        <div class="inline-flex items-center gap-1.5 mt-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-white/15 text-white border border-white/20">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                            <?= htmlspecialchars(Auth::user()['role_label'] ?? '') ?>
                        </div>
                    </div>
                </div>
                <div class="text-[10px] text-rose-200/80 pt-2 border-t border-white/10 flex items-center justify-between">
                    <span>Keamanan Sesi Tunggal Aktif</span>
                    <span class="font-bold text-white bg-white/10 px-2 py-0.5 rounded">1 User / Sesi</span>
                </div>
            </div>

            <!-- Pintasan Navigasi Mobile -->
            <div class="grid grid-cols-2 gap-2 text-xs">
                <a href="/index.php?page=profile" 
                   class="p-3 rounded-2xl bg-rose-50/70 hover:bg-rose-100 text-uhn-900 border border-rose-200/80 font-bold flex items-center gap-2 transition-colors">
                    <svg class="w-4 h-4 text-uhn-700 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span>Profil &amp; Sandi</span>
                </a>
                <?php if (Auth::isAdmin()): ?>
                <a href="/index.php?page=ai_config" 
                   class="p-3 rounded-2xl bg-gradient-to-r from-amber-50 to-rose-50 hover:bg-amber-100 text-uhn-900 border border-amber-200 font-bold flex items-center gap-2 transition-colors">
                    <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
                    <span class="flex items-center gap-1">Konfigurasi AI <span class="text-[9px] bg-amber-400/30 text-amber-900 px-1 rounded font-black">AI</span></span>
                </a>
                <a href="/index.php?page=reminders" 
                   class="p-3 rounded-2xl bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 font-bold flex items-center gap-2 transition-colors">
                    <svg class="w-4 h-4 text-slate-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <span>Pengingat PIC</span>
                </a>
                <a href="/index.php?page=users" 
                   class="p-3 rounded-2xl bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 font-bold flex items-center gap-2 transition-colors">
                    <svg class="w-4 h-4 text-slate-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <span>Kelola User</span>
                </a>
                <a href="/index.php?page=audit" 
                   class="p-3 rounded-2xl bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 font-bold flex items-center gap-2 transition-colors">
                    <svg class="w-4 h-4 text-slate-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span>Audit Trail</span>
                </a>
                <?php else: ?>
                <a href="/index.php?page=export" 
                   class="p-3 rounded-2xl bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 font-bold flex items-center gap-2 transition-colors">
                    <svg class="w-4 h-4 text-slate-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Borang WURI</span>
                </a>
                <?php endif; ?>
            </div>

            <!-- Tombol Logout Mobile -->
            <a href="/index.php?action=logout" 
               class="block w-full py-3 bg-gradient-to-r from-uhn-800 to-uhn-700 hover:from-uhn-900 hover:to-uhn-800 text-white rounded-xl font-bold text-xs text-center shadow-md transition-colors flex items-center justify-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                Keluar (Logout) dari Akun Ini
            </a>

            <button type="button" onclick="document.getElementById('mobileUserModal').classList.add('hidden')" 
                    class="w-full py-2.5 bg-slate-100 text-slate-600 rounded-xl font-bold text-xs hover:bg-slate-200">
                Tutup
            </button>
        </div>
    </div>

</body>
</html>
