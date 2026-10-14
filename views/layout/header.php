<?php
// views/layout/header.php
require_once __DIR__ . '/../../app/Config.php';
require_once __DIR__ . '/../../app/Auth.php';
require_once __DIR__ . '/../../app/Helpers.php';

$currentUser = Auth::user();
$activePeriod = $_GET['period_id'] ?? '2026-10';
$currentPage = $_GET['page'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title><?= htmlspecialchars($pageTitle ?? APP_NAME) ?> - <?= APP_INSTITUTION ?></title>
    <!-- PWA Mobile App Support -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#680015">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="TaskForce HN">
    <link rel="apple-touch-icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='22' fill='%23680015'/><text x='50%' y='64%' dominant-baseline='middle' text-anchor='middle' font-family='sans-serif' font-weight='900' font-size='48' fill='%23ffffff'>HN</text></svg>">
    
    <!-- Favicon SVG -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='22' fill='%23680015'/><text x='50%' y='64%' dominant-baseline='middle' text-anchor='middle' font-family='sans-serif' font-weight='900' font-size='48' fill='%23ffffff'>HN</text></svg>">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Chart.js CDN untuk Visualisasi Analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        uhn: {
                            50: '#fff1f3',
                            100: '#ffe4e7',
                            200: '#fecdd4',
                            300: '#fda4b0',
                            400: '#fb7186',
                            500: '#f43f5f',
                            600: '#e11d49',
                            700: '#a30029', // Crimson
                            800: '#8b0020', // Deep Maroon
                            900: '#680015', // Dark Burgundy
                            950: '#43000d',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }
        .curved-banner {
            border-bottom-left-radius: 2rem;
            border-bottom-right-radius: 2rem;
        }
        /* Custom scrollbar for sidebar */
        .sidebar-scroll::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
        }
        /* Hide scrollbar for mobile horizontal chip strips */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        /* Safe area bottom for mobile devices with gesture bar */
        .safe-area-bottom {
            padding-bottom: env(safe-area-inset-bottom, 0px);
        }
    </style>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(() => {});
            });
        }
        function toggleSidebar() {
            const sidebar = document.getElementById('mainSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            if (!sidebar || !backdrop) return;
            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                backdrop.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            } else {
                sidebar.classList.add('-translate-x-full');
                backdrop.classList.add('hidden');
                document.body.style.overflow = '';
            }
        }
    </script>
</head>
<body class="bg-[#FAF7F8] text-slate-800 min-h-screen flex antialiased selection:bg-rose-200 selection:text-uhn-950">

    <!-- Mobile Drawer Overlay Backdrop -->
    <div id="sidebarBackdrop" onclick="toggleSidebar()" class="fixed inset-0 bg-black/60 z-40 backdrop-blur-sm hidden lg:hidden transition-opacity"></div>

    <!-- ========================================================================= -->
    <!-- LEFT SIDEBAR NAVIGATION (Desktop Sticky & Mobile Slide-Over Drawer) -->
    <!-- ========================================================================= -->
    <aside id="mainSidebar" 
           class="fixed inset-y-0 left-0 z-50 w-72 lg:w-64 bg-[#680015] text-white flex flex-col shadow-2xl transition-transform duration-300 ease-in-out -translate-x-full lg:translate-x-0 lg:static lg:h-screen lg:sticky lg:top-0 border-r border-rose-950/40 flex-shrink-0">
        
        <!-- Header Sidebar: Logo & Nama Institusi -->
        <div class="p-5 flex items-center space-x-3 border-b border-white/10">
            <a href="/index.php" class="flex items-center space-x-3 group min-w-0">
                <div class="w-11 h-11 rounded-2xl bg-white text-uhn-900 flex items-center justify-center font-black text-xl shadow-md group-hover:scale-105 transition-transform flex-shrink-0">
                    HN
                </div>
                <div class="min-w-0">
                    <div class="font-extrabold text-sm tracking-wide text-white leading-tight">
                        HARKAT<br>NEGERI
                    </div>
                    <div class="text-[10px] text-rose-200/90 font-medium leading-tight mt-0.5 truncate">
                        Tim Task Force Reputasi
                    </div>
                </div>
            </a>
            <!-- Mobile Close Button inside sidebar -->
            <button type="button" onclick="toggleSidebar()" class="lg:hidden ml-auto p-2 text-rose-200 hover:text-white rounded-xl bg-white/5 hover:bg-white/15 active:scale-95 transition-all" aria-label="Tutup Menu">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Mobile User Card inside Drawer (Tampil di Mobile saat buka menu) -->
        <?php if ($currentUser): ?>
        <div class="lg:hidden px-4 py-3.5 bg-black/20 border-b border-white/10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-rose-600 to-rose-400 text-white flex items-center justify-center font-black text-sm shadow-sm flex-shrink-0 border border-white/20">
                    <?= htmlspecialchars(strtoupper(substr($currentUser['name'] ?? 'U', 0, 1))) ?>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-bold text-white truncate"><?= htmlspecialchars($currentUser['name']) ?></div>
                    <div class="text-[10px] text-rose-200/90 font-medium truncate"><?= htmlspecialchars($currentUser['role_label']) ?></div>
                    <div class="inline-flex items-center gap-1 mt-0.5 text-[9px] text-emerald-300 font-semibold">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        Sesi Tunggal Aktif (@<?= htmlspecialchars($currentUser['username']) ?>)
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Menu Navigasi Vertikal (Pill Buttons) -->
        <nav class="flex-1 p-3 space-y-1.5 overflow-y-auto sidebar-scroll">
            
            <!-- 1. Dashboard -->
            <a href="/index.php?page=dashboard&period_id=<?= urlencode($activePeriod) ?>" 
               class="flex items-center gap-3 px-3.5 py-3 rounded-2xl text-xs sm:text-sm font-semibold transition-all <?= (!isset($_GET['page']) || $_GET['page'] === 'dashboard') ? 'bg-white/15 text-white font-bold backdrop-blur-sm shadow-sm border border-white/20' : 'text-rose-100/80 hover:text-white hover:bg-white/10' ?>">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span>Dashboard</span>
            </a>

            <!-- 2. Inventarisasi Data -->
            <a href="/index.php?page=entries&period_id=<?= urlencode($activePeriod) ?>" 
               class="flex items-center gap-3 px-3.5 py-3 rounded-2xl text-xs sm:text-sm font-semibold transition-all <?= (isset($_GET['page']) && str_starts_with($_GET['page'], 'entries')) ? 'bg-white/15 text-white font-bold backdrop-blur-sm shadow-sm border border-white/20' : 'text-rose-100/80 hover:text-white hover:bg-white/10' ?>">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                <span>Inventarisasi Data</span>
            </a>

            <!-- 3. Meja Kurasi Komunikasi -->
            <?php if (Auth::canCurateKomunikasi() || Auth::isAdmin() || Auth::isViewer()): ?>
            <a href="/index.php?page=komunikasi&period_id=<?= urlencode($activePeriod) ?>" 
               class="flex items-center gap-3 px-3.5 py-3 rounded-2xl text-xs sm:text-sm font-semibold transition-all <?= (isset($_GET['page']) && $_GET['page'] === 'komunikasi') ? 'bg-white/15 text-white font-bold backdrop-blur-sm shadow-sm border border-white/20' : 'text-rose-100/80 hover:text-white hover:bg-white/10' ?>">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                <span>Meja Kurasi Komunikasi</span>
            </a>
            <?php endif; ?>

            <!-- 4. Borang WURI & Ekspor -->
            <a href="/index.php?page=export&period_id=<?= urlencode($activePeriod) ?>" 
               class="flex items-center gap-3 px-3.5 py-3 rounded-2xl text-xs sm:text-sm font-semibold transition-all <?= (isset($_GET['page']) && $_GET['page'] === 'export') ? 'bg-white/15 text-white font-bold backdrop-blur-sm shadow-sm border border-white/20' : 'text-rose-100/80 hover:text-white hover:bg-white/10' ?>">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                <span>Borang WURI &amp; Ekspor</span>
            </a>

            <!-- Menu Khusus Administrator Task Force -->
            <?php if (Auth::isAdmin()): ?>
            <!-- 5. Pengingat Target (Admin ke PIC Unit Direct) -->
            <a href="/index.php?page=reminders&period_id=<?= urlencode($activePeriod) ?>" 
               class="flex items-center gap-3 px-3.5 py-3 rounded-2xl text-xs sm:text-sm font-semibold transition-all <?= (isset($_GET['page']) && $_GET['page'] === 'reminders') ? 'bg-white/15 text-white font-bold backdrop-blur-sm shadow-sm border border-white/20' : 'text-rose-100/80 hover:text-white hover:bg-white/10' ?>">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <span>Pengingat Target PIC</span>
            </a>

            <!-- 6. Pengguna (Khusus Admin) -->
            <a href="/index.php?page=users" 
               class="flex items-center gap-3 px-3.5 py-3 rounded-2xl text-xs sm:text-sm font-semibold transition-all <?= (isset($_GET['page']) && $_GET['page'] === 'users') ? 'bg-white/15 text-white font-bold backdrop-blur-sm shadow-sm border border-white/20' : 'text-rose-100/80 hover:text-white hover:bg-white/10' ?>">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <span>Manajemen Pengguna</span>
            </a>

            <!-- 7. Konfigurasi AI (Khusus Admin) -->
            <a href="/index.php?page=ai_config" 
               class="flex items-center gap-3 px-3.5 py-3 rounded-2xl text-xs sm:text-sm font-semibold transition-all <?= (isset($_GET['page']) && $_GET['page'] === 'ai_config') ? 'bg-white/15 text-white font-bold backdrop-blur-sm shadow-sm border border-white/20' : 'text-rose-100/80 hover:text-white hover:bg-white/10' ?>">
                <svg class="w-5 h-5 flex-shrink-0 text-amber-300" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2L14.4 7.6L20 10L14.4 12.4L12 18L9.6 12.4L4 10L9.6 7.6L12 2Z"/>
                    <path d="M19 16L20.2 18.8L23 20L20.2 21.2L19 24L17.8 21.2L15 20L17.8 18.8L19 16Z"/>
                    <path d="M5 16L6.2 18.8L9 20L6.2 21.2L5 24L3.8 21.2L1 20L3.8 18.8L5 16Z"/>
                </svg>
                <div class="flex items-center justify-between flex-1">
                    <span>Konfigurasi AI</span>
                    <span class="px-1.5 py-0.5 text-[9px] font-black rounded-md bg-amber-400/20 text-amber-300 border border-amber-400/30 uppercase tracking-wider">AI</span>
                </div>
            </a>

            <!-- 8. Audit Trail (Khusus Admin) -->
            <a href="/index.php?page=audit" 
               class="flex items-center gap-3 px-3.5 py-3 rounded-2xl text-xs sm:text-sm font-semibold transition-all <?= (isset($_GET['page']) && $_GET['page'] === 'audit') ? 'bg-white/15 text-white font-bold backdrop-blur-sm shadow-sm border border-white/20' : 'text-rose-100/80 hover:text-white hover:bg-white/10' ?>">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span>Audit Trail</span>
            </a>
            <?php endif; ?>

            <!-- 8. Profil Saya (Semua Pengguna) -->
            <a href="/index.php?page=profile" 
               class="flex items-center gap-3 px-3.5 py-3 rounded-2xl text-xs sm:text-sm font-semibold transition-all <?= (isset($_GET['page']) && $_GET['page'] === 'profile') ? 'bg-white/15 text-white font-bold backdrop-blur-sm shadow-sm border border-white/20' : 'text-rose-100/80 hover:text-white hover:bg-white/10' ?>">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span>Profil &amp; Kata Sandi</span>
            </a>

        </nav>

        <!-- Sidebar Footer: Tombol Logout -->
        <div class="p-3 border-t border-white/10">
            <a href="/index.php?action=logout" 
               class="flex items-center gap-3 px-3.5 py-3 rounded-2xl text-xs font-bold text-rose-200/90 hover:text-white hover:bg-rose-700/50 transition-colors">
                <svg class="w-4 h-4 text-rose-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                <span>Keluar (Logout)</span>
            </a>
        </div>
    </aside>

    <!-- ========================================================================= -->
    <!-- MAIN CONTENT WRAPPER -->
    <!-- ========================================================================= -->
    <div class="flex-1 flex flex-col min-w-0 min-h-screen">

        <!-- Top App Bar (Responsive Mobile & Desktop Header) -->
        <header class="bg-gradient-to-r from-uhn-950 via-uhn-900 to-uhn-800 text-white sticky top-0 z-30 shadow-md border-b border-white/10">
            <div class="px-3 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-14 sm:h-16 gap-2">
                    
                    <!-- Sisi Kiri Header: Toggle Hamburger & Identitas Mobile -->
                    <div class="flex items-center gap-2.5 min-w-0">
                        <button type="button" onclick="toggleSidebar()" 
                                class="lg:hidden p-2 rounded-xl bg-white/10 text-white hover:bg-white/20 active:scale-95 transition-all focus:outline-none focus:ring-2 focus:ring-rose-400 flex-shrink-0"
                                aria-label="Buka Menu Navigasi">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </button>
                        
                        <a href="/index.php" class="lg:hidden flex items-center gap-2 min-w-0">
                            <div class="w-8 h-8 rounded-xl bg-white text-uhn-900 flex items-center justify-center font-black text-xs shadow-sm flex-shrink-0">
                                HN
                            </div>
                            <div class="min-w-0">
                                <div class="font-extrabold text-[11px] sm:text-xs tracking-wide text-white leading-tight truncate">
                                    HARKAT NEGERI
                                </div>
                                <div class="text-[9px] text-rose-200/90 font-medium leading-tight truncate">
                                    ERP Reputasi Institusi
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Sisi Kanan Header: Identitas Sesi Tunggal & Aksi Cepat -->
                    <div class="flex items-center gap-1.5 sm:gap-2.5 ml-auto flex-shrink-0">
                        <?php if ($currentUser): ?>
                            <!-- Tampilan Mobile (< sm): Pill Ringkas + Buka Sheet Akun -->
                            <button type="button" onclick="document.getElementById('mobileUserModal').classList.remove('hidden')"
                                    class="sm:hidden flex items-center gap-2 bg-black/25 border border-white/15 rounded-xl pl-2 pr-2.5 py-1 text-left active:scale-95 transition-all">
                                <div class="w-6 h-6 rounded-lg bg-gradient-to-tr from-rose-600 to-rose-400 text-white flex items-center justify-center font-black text-[10px] shadow-sm flex-shrink-0">
                                    <?= htmlspecialchars(strtoupper(substr($currentUser['name'] ?? 'U', 0, 1))) ?>
                                </div>
                                <div class="max-w-[95px]">
                                    <div class="text-[10px] font-bold text-white leading-tight truncate"><?= htmlspecialchars($currentUser['name']) ?></div>
                                    <div class="text-[8px] text-rose-200 leading-tight truncate flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 flex-shrink-0"></span>
                                        <?= htmlspecialchars($currentUser['role']) ?>
                                    </div>
                                </div>
                            </button>

                            <!-- Tampilan Desktop (>= sm): Identitas Akun Sesi Tunggal Lengkap -->
                            <div class="hidden sm:flex items-center gap-2.5 bg-black/20 backdrop-blur-sm border border-white/10 rounded-2xl px-3 py-1.5 shadow-inner">
                                <div class="w-7 h-7 rounded-lg bg-gradient-to-tr from-rose-600 to-rose-400 text-white flex items-center justify-center font-black text-xs shadow-sm flex-shrink-0">
                                    <?= htmlspecialchars(strtoupper(substr($currentUser['name'] ?? 'U', 0, 1))) ?>
                                </div>
                                <div class="flex flex-col text-left">
                                    <span class="text-xs font-bold text-white leading-tight truncate max-w-[160px] md:max-w-[220px]">
                                        <?= htmlspecialchars($currentUser['name']) ?>
                                    </span>
                                    <div class="flex items-center gap-1.5 text-[10px] text-rose-200/90 leading-tight mt-0.5">
                                        <span class="font-medium"><?= htmlspecialchars($currentUser['role_label']) ?></span>
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400" title="Sesi Terotentikasi Tunggal"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Tombol Kelola Profil & Sandi (Desktop & Tablet) -->
                            <a href="/index.php?page=profile" 
                               class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 bg-white/10 hover:bg-white/20 text-rose-100 hover:text-white border border-rose-300/30 rounded-xl text-xs font-bold transition-all shadow-sm"
                               title="Kelola Profil & Kata Sandi">
                                <svg class="w-3.5 h-3.5 text-rose-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                <span>Profil</span>
                            </a>

                            <!-- Tombol Logout Aman -->
                            <a href="/index.php?action=logout" 
                               class="inline-flex items-center gap-1.5 p-2 sm:px-3 sm:py-1.5 bg-black/25 hover:bg-rose-700/80 text-rose-100 hover:text-white border border-rose-400/30 rounded-xl text-xs font-bold transition-all shadow-sm active:scale-95"
                               title="Keluar dari Sistem (Logout)">
                                <svg class="w-4 h-4 sm:w-3.5 sm:h-3.5 text-rose-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                <span class="hidden sm:inline">Logout</span>
                            </a>
                        <?php else: ?>
                            <a href="/index.php?page=login" class="px-3.5 py-1.5 bg-white text-uhn-900 rounded-xl text-xs font-bold hover:bg-rose-50 shadow-sm transition-colors">
                                Login
                            </a>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </header>

        <!-- Main Container Content (pb-28 pada mobile agar tidak tertutup bottom navigation bar) -->
        <main class="flex-grow max-w-7xl w-full mx-auto px-3.5 sm:px-6 lg:px-8 py-4 sm:py-6 pb-28 md:pb-8">
            
            <!-- Flash Alert Message Handler -->
            <?php if (!empty($_SESSION['flash_success'])): ?>
                <div class="mb-4 sm:mb-5 bg-emerald-50 border-l-4 border-emerald-500 p-3.5 sm:p-4 rounded-r-2xl shadow-sm flex items-start gap-3">
                    <svg class="w-5 h-5 text-emerald-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <div class="text-xs sm:text-sm font-medium text-emerald-800 leading-relaxed">
                        <?= htmlspecialchars($_SESSION['flash_success']) ?>
                    </div>
                </div>
                <?php unset($_SESSION['flash_success']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['flash_error'])): ?>
                <div class="mb-4 sm:mb-5 bg-rose-50 border-l-4 border-uhn-700 p-3.5 sm:p-4 rounded-r-2xl shadow-sm flex items-start gap-3">
                    <svg class="w-5 h-5 text-uhn-700 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    <div class="text-xs sm:text-sm font-medium text-uhn-900 leading-relaxed">
                        <?= htmlspecialchars($_SESSION['flash_error']) ?>
                    </div>
                </div>
                <?php unset($_SESSION['flash_error']); ?>
            <?php endif; ?>
