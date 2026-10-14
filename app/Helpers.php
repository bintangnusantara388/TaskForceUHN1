<?php
// app/Helpers.php

class Helpers {
    /**
     * Menghitung perkiraan jumlah kalimat dalam teks (berdasarkan pemisah . ! ?)
     */
    public static function countSentences(string $text): int {
        $clean = trim($text);
        if (empty($clean)) return 0;
        // Lindungi titik pada singkatan gelar & kata umum agar tidak memecah kalimat
        $clean = preg_replace('/\b(Dr|Prof|Ir|Drs|Dra|apt|Ns|S\.T|M\.T|S\.Kom|M\.Kom|S\.E|M\.M|S\.Si|M\.Si|S\.Pd|M\.Pd|M\.B\.A|Ph\.D|B\.Sc|M\.Sc|H|Hj|dkk|dll|dsb|dst|No)\./iu', '$1{{DOT}}', $clean);
        // Lindungi inisial huruf tunggal (misal: A. Fauzi)
        $clean = preg_replace('/\b([A-Z])\./', '$1{{DOT}}', $clean);
        $matches = preg_split('/[.!?]+(?:\s+|$)/', $clean, -1, PREG_SPLIT_NO_EMPTY);
        return is_array($matches) ? count($matches) : 1;
    }

    /**
     * Memvalidasi NIDN / NIP / NIM (hanya angka)
     */
    public static function isValidNumericIdentifier(string $str): bool {
        $trimmed = trim($str);
        return $trimmed !== '' && ctype_digit($trimmed);
    }

    /**
     * Sensor nomor identitas pribadi (PII) bila user bukan Admin / PIC pemilik
     */
    public static function maskIdentifier(string $idStr, bool $canSeeFull): string {
        if ($canSeeFull) {
            return htmlspecialchars($idStr);
        }
        $len = strlen($idStr);
        if ($len <= 4) {
            return str_repeat('*', $len);
        }
        return substr($idStr, 0, 3) . str_repeat('*', max(2, $len - 6)) . substr($idStr, -3);
    }

    /**
     * Sensor nama jika subjek tidak menyetujui publikasi nama (consent = 'TIDAK')
     */
    public static function maskNameIfRestricted(string $name, string $consent, bool $canSeePrivate): string {
        if ($consent === 'YA' || $canSeePrivate) {
            return htmlspecialchars($name);
        }
        // Inisial masking (misal "Nurul Hidayati" -> "N. H. (Anonim/Inisial)")
        $parts = explode(' ', trim($name));
        $initials = array_map(fn($p) => strtoupper(substr($p, 0, 1)) . '.', $parts);
        return implode(' ', $initials) . ' <span class="text-xs bg-rose-100 text-uhn-900 px-1.5 py-0.5 rounded font-mono font-bold">[Anonim]</span>';
    }

    /**
     * Format tanggal Indonesia
     */
    public static function formatDate(?string $date): string {
        if (!$date) return '-';
        $time = strtotime($date);
        if (!$time) return htmlspecialchars($date);
        $months = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        $d = date('j', $time);
        $m = $months[(int)date('n', $time)];
        $y = date('Y', $time);
        return "$d $m $y";
    }

    public static function formatBytes(?int $bytes): string {
        if (!$bytes) return '-';
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        }
        return number_format($bytes / 1024, 0) . ' KB';
    }

    public static function badgeTier(string $tier): string {
        if ($tier === 'SIGNATURE') {
            return '<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-black bg-rose-100 text-uhn-900 border border-rose-300 shadow-sm">
                <svg class="w-3.5 h-3.5 text-uhn-800 fill-uhn-800" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                SIGNATURE
            </span>';
        }
        return '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
            Reguler
        </span>';
    }

    public static function badgeScreening(string $status): string {
        if ($status === 'SIAP_DIOLAH') {
            return '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Siap Diolah
            </span>';
        }
        return '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">
            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Draft
        </span>';
    }

    public static function badgeComm(string $status): string {
        switch ($status) {
            case 'SUDAH_TERBIT':
                return '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-sm">
                    <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Sudah Terbit
                </span>';
            case 'SEDANG_DIOLAH':
                return '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-50 text-uhn-800 border border-rose-300 shadow-sm">
                    <span class="w-1.5 h-1.5 rounded-full bg-uhn-700 animate-pulse"></span> Sedang Diolah
                </span>';
            default:
                return '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-800 border border-amber-200">
                    Belum Diolah
                </span>';
        }
    }

    public static function badgeConsent(string $consent): string {
        if ($consent === 'YA') {
            return '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                Persetujuan: YA
            </span>';
        }
        return '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-extrabold bg-rose-100 text-uhn-900 border border-rose-300">
            <svg class="w-3 h-3 text-uhn-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            TIDAK (Restricted PDP)
        </span>';
    }

    /**
     * Mendapatkan alamat IP klien
     */
    public static function getClientIp(): string {
        return $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Format nomor WhatsApp/telepon untuk tampilan pengguna (misal +62 812-3456-7801)
     */
    public static function formatPhoneNumber(?string $phone): string {
        if (empty($phone)) return '-';
        $clean = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($clean, '62') && strlen($clean) >= 11) {
            return '+62 ' . substr($clean, 2, 3) . '-' . substr($clean, 5, 4) . '-' . substr($clean, 9);
        } elseif (str_starts_with($clean, '0') && strlen($clean) >= 10) {
            return substr($clean, 0, 4) . '-' . substr($clean, 4, 4) . '-' . substr($clean, 8);
        }
        return htmlspecialchars($phone);
    }
}

