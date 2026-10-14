<?php
// views/auth/login.php
require_once __DIR__ . '/../../app/Config.php';
require_once __DIR__ . '/../../app/Auth.php';

// Jika sudah login, redirect langsung ke dashboard
if (Auth::check()) {
    header("Location: /index.php?page=dashboard");
    exit;
}

$error = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_error']);

$success = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Login Keamanan - <?= APP_NAME ?> - <?= APP_INSTITUTION ?></title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='22' fill='%238b0020'/><text x='50%' y='64%' dominant-baseline='middle' text-anchor='middle' font-family='sans-serif' font-weight='900' font-size='48' fill='%23ffffff'>HN</text></svg>">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#680015">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <script src="https://cdn.tailwindcss.com"></script>
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
                            700: '#a30029',
                            800: '#8b0020',
                            900: '#680015',
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
    </style>
</head>
<body class="bg-[#FAF7F8] text-slate-800 min-h-screen flex flex-col justify-center items-center p-3 sm:p-4">

    <!-- Container Login Utama (Responsive Mobile First + Desktop 2-Column Card) -->
    <div class="w-full max-w-4xl mx-auto grid grid-cols-1 md:grid-cols-12 bg-white rounded-3xl shadow-2xl overflow-hidden border border-rose-100/70 text-slate-800">
        
        <!-- Kolom Kiri / Atas (Mobile Compact Header, Desktop Full Branding) -->
        <div class="md:col-span-5 bg-gradient-to-br from-uhn-950 via-uhn-900 to-uhn-800 text-white p-5 sm:p-8 md:p-10 flex flex-col justify-between relative overflow-hidden">
            <!-- Subtle Decorative Circles -->
            <div class="absolute -right-16 -top-16 w-52 h-52 rounded-full bg-white/5 pointer-events-none"></div>
            <div class="absolute -left-12 -bottom-12 w-48 h-48 rounded-full bg-rose-500/10 pointer-events-none"></div>

            <div class="relative z-10">
                <div class="flex items-center justify-between md:justify-start space-x-3 mb-3 md:mb-8">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-white text-uhn-900 flex items-center justify-center font-black text-xl sm:text-2xl shadow-lg shadow-black/20 flex-shrink-0">
                            HN
                        </div>
                        <div>
                            <h1 class="font-extrabold text-sm sm:text-base tracking-wide text-white leading-tight">
                                UNIVERSITAS HARKAT NEGERI
                            </h1>
                            <p class="text-[11px] sm:text-xs text-rose-200 font-semibold">
                                Tim Task Force Kerjasama &amp; Reputasi
                            </p>
                        </div>
                    </div>
                    <span class="md:hidden px-2.5 py-1 rounded-full bg-white/10 text-rose-200 text-[10px] font-bold border border-white/15 flex-shrink-0">
                        ERP v2.4
                    </span>
                </div>

                <div class="hidden md:inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 text-rose-200 text-xs font-bold border border-white/15 mb-3 backdrop-blur-sm">
                    <span class="w-2 h-2 rounded-full bg-rose-400 animate-pulse"></span>
                    Portal Inventarisasi Reputasi
                </div>

                <h2 class="text-lg sm:text-2xl font-black text-white leading-snug mb-1.5 md:mb-3">
                    Pengumpulan Data Pendukung Branding
                </h2>
                <p class="text-[11px] sm:text-xs text-rose-100/80 leading-relaxed mb-3 md:mb-6">
                    Sistem terintegrasi pengumpulan capaian tridarma untuk publikasi media dan borang pemeringkatan <strong>WURI</strong>, <strong>Uniranks</strong>, &amp; <strong>Webometrics</strong>.
                </p>

                <!-- Matriks Hak Akses: Collapsible di Mobile agar tidak memakan tinggi layar, Terbuka di Desktop -->
                <details class="md:hidden bg-black/20 backdrop-blur-md rounded-2xl border border-white/10 text-[11px] overflow-hidden">
                    <summary class="px-3.5 py-2.5 font-bold text-rose-200 cursor-pointer flex items-center justify-between select-none">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-rose-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            Lihat Matriks Keamanan Akses (RBAC)
                        </span>
                        <span class="text-[10px] text-rose-300">▼</span>
                    </summary>
                    <div class="px-3.5 pb-3 pt-1 space-y-1.5 text-rose-100/90 border-t border-white/10">
                        <div>&bull; <strong>Admin TF:</strong> Akses penuh semua unit &amp; user.</div>
                        <div>&bull; <strong>PIC Unit:</strong> Input &amp; kelola capaian unitnya sendiri.</div>
                        <div>&bull; <strong>Komunikasi:</strong> Kurasi rilis &amp; kepatuhan UU PDP.</div>
                        <div>&bull; <strong>Viewer:</strong> Pemantauan eksekutif rektorat.</div>
                    </div>
                </details>

                <div class="hidden md:block space-y-2.5 bg-black/20 backdrop-blur-md p-4 rounded-2xl border border-white/10 text-[11px]">
                    <div class="font-bold text-rose-200 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-rose-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        Matriks Keamanan Akses (RBAC)
                    </div>
                    <div class="flex items-start gap-1.5">
                        <span class="text-rose-400 font-bold">&bull;</span>
                        <span><strong>Admin TF:</strong> Akses penuh semua unit, data, user &amp; backup.</span>
                    </div>
                    <div class="flex items-start gap-1.5">
                        <span class="text-rose-400 font-bold">&bull;</span>
                        <span><strong>PIC Unit:</strong> Input &amp; edit capaian unitnya sendiri saja.</span>
                    </div>
                    <div class="flex items-start gap-1.5">
                        <span class="text-rose-400 font-bold">&bull;</span>
                        <span><strong>Komunikasi:</strong> Kurasi data siap olah &amp; perlindungan privasi PDP.</span>
                    </div>
                    <div class="flex items-start gap-1.5">
                        <span class="text-rose-400 font-bold">&bull;</span>
                        <span><strong>Viewer (Pimpinan):</strong> Pemantauan dashboard ringkasan saja.</span>
                    </div>
                </div>
            </div>

            <div class="hidden md:flex relative z-10 pt-6 border-t border-white/10 text-[10px] text-rose-200/80 items-center justify-between">
                <span>Versi ERP 2.4.0 (Crimson Edition)</span>
                <span>UU PDP No. 27/2022</span>
            </div>
        </div>

        <!-- Kolom Kanan: Form Login & Daftar yang Akan Dikelola -->
        <div class="md:col-span-7 p-5 sm:p-8 md:p-10 bg-white flex flex-col justify-between">
            <div>
                <div class="mb-4 sm:mb-6 flex items-center justify-between">
                    <div>
                        <h3 class="text-xl sm:text-2xl font-black text-slate-900">Autentikasi Pengguna</h3>
                        <p class="text-xs text-slate-500 mt-0.5 font-medium">1 Sesi hanya untuk 1 pengguna terotentikasi</p>
                    </div>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-200">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        Bcrypt
                    </span>
                </div>

                <!-- Alert Pesan Error / Sukses -->
                <?php if ($error): ?>
                    <div class="mb-4 bg-rose-50 border-l-4 border-uhn-700 p-3.5 rounded-r-xl text-xs font-semibold text-uhn-900 flex items-start gap-2">
                        <svg class="w-4 h-4 text-uhn-700 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="mb-4 bg-emerald-50 border-l-4 border-emerald-500 p-3.5 rounded-r-xl text-xs font-semibold text-emerald-800 flex items-start gap-2">
                        <svg class="w-4 h-4 text-emerald-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span><?= htmlspecialchars($success) ?></span>
                    </div>
                <?php endif; ?>

                <!-- Form Login -->
                <form action="/index.php?action=login" method="POST" class="space-y-3.5 sm:space-y-4">
                    <div>
                        <label for="username" class="block text-xs font-bold text-slate-700 mb-1">
                            Nama Pengguna (Username / Email)
                        </label>
                        <input type="text" name="username" id="username" required placeholder="Masukkan nama pengguna atau email" 
                               class="w-full bg-slate-50/80 border border-slate-200 rounded-xl px-4 py-3 text-sm sm:text-xs focus:ring-2 focus:ring-uhn-700 focus:border-uhn-700 focus:bg-white text-slate-900 font-medium transition-all">
                    </div>

                    <div>
                        <label for="password" class="block text-xs font-bold text-slate-700 mb-1">
                            Kata Sandi (Password)
                        </label>
                        <input type="password" name="password" id="password" required placeholder="Masukkan kata sandi akun" 
                               class="w-full bg-slate-50/80 border border-slate-200 rounded-xl px-4 py-3 text-sm sm:text-xs focus:ring-2 focus:ring-uhn-700 focus:border-uhn-700 focus:bg-white text-slate-900 font-medium transition-all">
                    </div>

                    <button type="submit" 
                            class="w-full py-3.5 sm:py-3 px-4 bg-gradient-to-r from-uhn-800 to-uhn-700 hover:from-uhn-900 hover:to-uhn-800 text-white rounded-xl text-xs sm:text-xs font-extrabold shadow-lg shadow-rose-950/20 active:scale-[0.99] transition-all flex items-center justify-center gap-2">
                        <svg class="w-4 h-4 text-rose-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                        Masuk ke Sistem ERP
                    </button>
                </form>

                <!-- Pemisah: Daftar yang Akan Dikelola -->
                <div class="relative my-5">
                    <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-slate-200"></div></div>
                    <div class="relative flex justify-center text-[10px] uppercase font-bold text-slate-400 bg-white px-3 tracking-wider">
                        Daftar yang Akan Dikelola
                    </div>
                </div>

                <!-- Status Kosong: Belum Ada Akun yang Dikelola -->
                <div class="p-5 rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/70 text-center flex flex-col items-center justify-center">
                    <div class="w-10 h-10 mb-2.5 rounded-2xl bg-white border border-slate-200 shadow-sm flex items-center justify-center text-slate-400">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <span class="text-xs font-bold text-slate-600">Belum Ada Akun yang Dikelola</span>
                    <p class="text-[11px] text-slate-400 mt-1 max-w-xs leading-relaxed">
                        Daftar akun dan unit kerja yang akan dikelola saat ini masih kosong. Silakan masuk menggunakan akun Administrator untuk menambahkan dan mengelola pengguna.
                    </p>
                </div>
            </div>

            <div class="mt-5 text-center text-[10px] sm:text-[11px] text-slate-400">
                Pusat Data dan Informasi Kerjasama &amp; Reputasi Institusi &copy; 2026 Universitas Harkat Negeri
            </div>
        </div>

    </div>

</body>
</html>
