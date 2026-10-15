<?php
// app/Services/AIService.php
require_once __DIR__ . '/../Config.php';
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Helpers.php';

class AIService {

    /**
     * Dapatkan nilai konfigurasi AI dari tabel system_settings
     */
    public static function getSetting(string $key, $default = null): ?string {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ? LIMIT 1");
            $stmt->execute([$key]);
            $val = $stmt->fetchColumn();
            return ($val !== false && $val !== null) ? (string)$val : $default;
        } catch (Exception $e) {
            return $default;
        }
    }

    /**
     * Simpan / Perbarui nilai konfigurasi AI ke tabel system_settings
     */
    public static function setSetting(string $key, ?string $value): bool {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                INSERT INTO system_settings (setting_key, setting_value, updated_at) 
                VALUES (?, ?, CURRENT_TIMESTAMP)
                ON CONFLICT(setting_key) DO UPDATE SET 
                    setting_value = excluded.setting_value,
                    updated_at = CURRENT_TIMESTAMP
            ");
            return $stmt->execute([$key, $value]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Dapatkan seluruh konfigurasi AI dengan nilai bawaan (defaults)
     */
    public static function getAllSettings(): array {
        $defaults = [
            'gemini_api_key' => '',
            'ai_model' => 'gemini-1.5-flash',
            'ai_default_tone' => 'STANDAR',
            'ai_temperature' => '0.4',
            'ai_enable_fallback' => '1',
            'ai_auto_format_3_sentences' => '1',
            'ai_max_tokens' => '400',
            'ai_system_instructions' => ''
        ];

        try {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT setting_key, setting_value FROM system_settings");
            $saved = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
            return array_merge($defaults, $saved);
        } catch (Exception $e) {
            return $defaults;
        }
    }

    /**
     * Dapatkan API Key Gemini (Prioritas: Database Settings -> Config.php -> Environment)
     */
    public static function getApiKey(): ?string {
        // 1. Cek dari database system_settings (yang diinput admin di UI)
        $dbKey = self::getSetting('gemini_api_key');
        if ($dbKey && !empty(trim($dbKey))) {
            return trim($dbKey);
        }
        // 2. Cek dari konstanta Config.php
        if (defined('GEMINI_API_KEY') && !empty(GEMINI_API_KEY)) {
            return GEMINI_API_KEY;
        }
        // 3. Cek dari environment variable
        $envKey = getenv('GEMINI_API_KEY');
        if ($envKey && !empty(trim($envKey))) {
            return trim($envKey);
        }
        if (!empty($_ENV['GEMINI_API_KEY'])) {
            return trim($_ENV['GEMINI_API_KEY']);
        }
        return null;
    }

    /**
     * Cek apakah API Gemini terkonfigurasi
     */
    public static function isGeminiConfigured(): bool {
        return self::getApiKey() !== null;
    }

    /**
     * Generate Narasi 3 Kalimat Bahasa Awam Otomatis
     * Mendukung pemanggilan Live Gemini API dengan fallback cerdas ke Local AI Synthesizer.
     *
     * @param array $payload Data entri capaian dari form/database
     * @param string $tone Gaya bahasa: 'STANDAR', 'MEDIA', 'INSPIRATIF'
     * @return array
     */
    public static function generateNarrative(array $payload, string $tone = 'STANDAR'): array {
        $apiKey = self::getApiKey();
        $unitCode = strtoupper(trim($payload['unit_code'] ?? 'PPM'));
        $title = trim($payload['title'] ?? '');

        // Validasi minimal
        if (empty($title)) {
            $title = 'Capaian Strategis Institusi';
            $payload['title'] = $title;
        }

        // Coba Live Gemini API jika API Key tersedia
        if ($apiKey) {
            $geminiResult = self::callGeminiNarrative($payload, $apiKey, $tone);
            if ($geminiResult && !empty($geminiResult['narrative'])) {
                $cleaned = self::enforceThreeSentences($geminiResult['narrative']);
                return [
                    'success' => true,
                    'narrative' => $cleaned['text'],
                    'sentence_count' => $cleaned['count'],
                    'provider' => 'gemini',
                    'provider_label' => 'Google Gemini AI (' . $geminiResult['model'] . ')',
                    'tone' => $tone,
                    'sentences' => $cleaned['sentences'],
                    'message' => 'Narasi 3 kalimat berhasil digenerate menggunakan Google Gemini AI.'
                ];
            }
        }

        // Cek apakah fallback lokal diizinkan
        $fallbackAllowed = (self::getSetting('ai_enable_fallback', '1') === '1');
        if (!$fallbackAllowed && empty($apiKey)) {
            return [
                'success' => false,
                'narrative' => '',
                'sentence_count' => 0,
                'provider' => 'none',
                'provider_label' => 'Tidak Aktif',
                'tone' => $tone,
                'sentences' => [],
                'message' => 'API Key Gemini belum diatur dan Mode Fallback Lokal dinonaktifkan oleh Administrator Task Force.'
            ];
        }

        // Fallback: Engine Sintesis NLP Cerdas Berbasis Domain Institusi (100% Reliable Offline)
        $localResult = self::synthesizeLocalNarrative($payload, $tone);
        return [
            'success' => true,
            'narrative' => $localResult['narrative'],
            'sentence_count' => $localResult['sentence_count'],
            'provider' => 'local_ai',
            'provider_label' => 'UHN Neural Synthesizer (Built-in Rule NLP)',
            'tone' => $tone,
            'sentences' => $localResult['sentences'],
            'message' => 'Narasi 3 kalimat berhasil disintesis secara cerdas sesuai standar borang WURI & rilis pers.'
        ];
    }

    /**
     * Memanggil Google Gemini API untuk pembuatan narasi 3 kalimat
     */
    private static function callGeminiNarrative(array $payload, string $apiKey, string $tone): ?array {
        $unitCode = $payload['unit_code'] ?? 'PPM';
        $title = $payload['title'] ?? '';
        $tier = $payload['tier_type'] ?? 'REGULER';
        $consent = $payload['name_publication_consent'] ?? 'YA';

        // Deteksi subjek
        $subject = self::extractSubjectSummary($payload);

        $toneInstructions = [
            'STANDAR' => 'Gaya bahasa institusional resmi, lugas, mengedepankan relevansi borang WURI (World\'s Universities with Real Impact) dan kemanfaatan nyata bagi masyarakat/industri.',
            'MEDIA' => 'Gaya bahasa jurnalisme populer ramah media massa, memikat, bernilai berita tinggi (news-worthy), dan mudah dipahami masyarakat luas tanpa istilah teknis yang rumit.',
            'INSPIRATIF' => 'Gaya bahasa bercerita (human interest) yang menggugah, menonjolkan dedikasi, kerja keras, dan dampak inspiratif bagi generasi muda kampus.'
        ];
        $selectedToneGuide = $toneInstructions[$tone] ?? $toneInstructions['STANDAR'];

        $privacyGuide = ($consent === 'TIDAK') 
            ? "PERINGATAN PRIVASI (UU PDP): Subjek TIDAK mengizinkan nama pribadinya dipublikasikan. JANGAN sebut nama asli personil. Gunakan sebutan kolektif seperti 'Tim Peneliti Universitas Harkat Negeri' atau 'Sivitas Akademika UHN'." 
            : "Nama subjek boleh dicantumkan: " . $subject['name'] . ".";

        $prompt = "Sebagai Senior Editor Komunikasi & Spesialis Pemeringkatan WURI di Universitas Harkat Negeri (UHN), susunlah deskripsi narasi capaian berikut dalam TEPAT 3 KALIMAT BAHASA AWAM (Bahasa Indonesia).

DATA CAPAIAN:
- Unit Kerja: {$unitCode}
- Judul Capaian: {$title}
- Klasifikasi Tier: {$tier}
- Subjek / Personil: {$subject['summary']}
- Rincian Spesifik: {$subject['details']}
- {$privacyGuide}

KETENTUAN WAJIB STRUKTUR 3 KALIMAT:
1. Kalimat 1: Jelaskan subjek inovator/pelaksana, karya/prestasi utama, dan afiliasinya di Universitas Harkat Negeri.
2. Kalimat 2: Uraikan manfaat konkret, solusi yang dihadirkan, atau keunggulan capaian bagi masyarakat/industri/mitra.
3. Kalimat 3: Tegaskan dampak strategis capaian terhadap penguatan reputasi institusi dan kontribusinya pada pemeringkatan internasional WURI.

ATURAN KETAT:
- WAJIB TEPAT 3 KALIMAT. Jangan kurang dari 3 kalimat, jangan lebih dari 3 kalimat.
- Gunakan bahasa awam yang komunikatif, hindari jargon teknis rumit.
- {$selectedToneGuide}
- Output HANYA berupa teks narasi 3 kalimat tersebut. Jangan berikan kalimat pembuka/penutup, jangan gunakan tanda petik ganda membungkus seluruh teks, dan jangan gunakan bullet points.";

        // Tambahkan instruksi kustom institusi jika ada
        $customInst = self::getSetting('ai_system_instructions', '');
        if (!empty(trim($customInst))) {
            $prompt .= "\n\nPANDUAN KHUSUS INSTITUSI:\n" . trim($customInst);
        }

        $selectedModel = self::getSetting('ai_model', 'gemini-1.5-flash');
        $temperature = (float)self::getSetting('ai_temperature', '0.4');
        $maxTokens = (int)self::getSetting('ai_max_tokens', '400');

        $models = [$selectedModel];
        if ($selectedModel !== 'gemini-1.5-flash') $models[] = 'gemini-1.5-flash';
        if ($selectedModel !== 'gemini-2.0-flash') $models[] = 'gemini-2.0-flash';

        foreach ($models as $model) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($apiKey);

            $reqBody = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => $temperature,
                    'maxOutputTokens' => $maxTokens
                ]
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($reqBody));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 6); // Timeout cepat agar UX responsif
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            $resp = curl_exec($ch);
            if ($resp === false && in_array(curl_errno($ch), [60, 77], true)) {
                // Retry jika SSL cert bundle lokal bermasalah pada environment tertentu
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $resp = curl_exec($ch);
            }
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            @curl_close($ch);

            if ($httpCode === 200 && $resp) {
                $data = json_decode($resp, true);
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                if ($text) {
                    return [
                        'narrative' => trim($text),
                        'model' => $model
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Sintesis Narasi Heuristik Cerdas (Domain-Specific NLP Template Synthesizer)
     * Menjamin 100% ketersediaan (Zero-Downtime) dengan kualitas penulisan profesional.
     */
    public static function synthesizeLocalNarrative(array $payload, string $tone = 'STANDAR'): array {
        $unitCode = strtoupper(trim($payload['unit_code'] ?? 'PPM'));
        $title = trim($payload['title'] ?? 'Capaian Unggulan Institusi');
        $tier = strtoupper(trim($payload['tier_type'] ?? 'REGULER'));
        $consent = strtoupper(trim($payload['name_publication_consent'] ?? 'YA'));

        $sub = self::extractSubjectSummary($payload);
        $subjectName = ($consent === 'TIDAK') ? $sub['anonymous_name'] : $sub['name'];

        $s1 = '';
        $s2 = '';
        $s3 = '';

        if ($unitCode === 'PPM') {
            $kiType = !empty($payload['ki_type']) ? ucwords(strtolower(str_replace('_', ' ', $payload['ki_type']))) : 'Kekayaan Intelektual';
            $regNum = !empty($payload['registration_number']) ? trim($payload['registration_number']) : '';
            $regSnippet = !empty($regNum) ? " dengan nomor pendaftaran {$regNum}" : "";

            if ($tone === 'MEDIA') {
                $s1 = "Tim periset Universitas Harkat Negeri yang dikoordinasikan oleh {$subjectName} berhasil menelurkan inovasi {$kiType} unggulan berjudul \"{$title}\"{$regSnippet}.";
                $s2 = "Inovasi ini dikembangkan untuk memberikan solusi praktis berdaya guna tinggi bagi masyarakat serta mempercepat adopsi teknologi di sektor industri hilir.";
                $s3 = "Karya aplikatif ini memperkuat komitmen Universitas Harkat Negeri dalam menyajikan riset solutif yang berdampak nyata sekaligus mengatrol posisi reputasi institusi pada pemeringkatan global WURI.";
            } elseif ($tone === 'INSPIRATIF') {
                $s1 = "Melalui dedikasi riset berkelanjutan, {$subjectName} dari Universitas Harkat Negeri sukses menuntaskan pendaftaran {$kiType} inovatif bertajuk \"{$title}\"{$regSnippet}.";
                $s2 = "Karya ilmiah yang lahir dari kepekaan terhadap tantangan riil masyarakat ini menjadi bukti nyata kemampuan sivitas akademika dalam menciptakan teknologi tepat guna.";
                $s3 = "Pencapaian membanggakan ini tidak hanya menginspirasi generasi muda peneliti, namun juga mengokohkan reputasi institusi sebagai kampus berdampak nyata di kancah WURI.";
            } else { // STANDAR WURI
                $s1 = "Pusat Penelitian dan Pengabdian Masyarakat Universitas Harkat Negeri melalui tim inovator {$subjectName} resmi mencatatkan {$kiType} bertajuk \"{$title}\"{$regSnippet}.";
                $s2 = "Teknologi ini dirancang guna menjawab tantangan efisiensi sistem dan memiliki potensi kemanfaatan ekonomi serta hilirisasi langsung bagi kebutuhan mitra masyarakat dan dunia usaha.";
                $s3 = "Capaian kekayaan intelektual ini memperkaya portofolio inovasi institusi sekaligus mendukung pemenuhan indikator reputasi perguruan tinggi pada pemeringkatan reputasi internasional WURI.";
            }
        } elseif ($unitCode === 'PSDM') {
            $achType = !empty($payload['achievement_type']) ? ucwords(strtolower(str_replace('_', ' ', $payload['achievement_type']))) : 'Sertifikasi Kompetensi';
            $level = !empty($payload['level']) ? ucwords(strtolower($payload['level'])) : 'Nasional';
            $organizer = !empty($payload['issuer_organizer']) ? trim($payload['issuer_organizer']) : 'lembaga penguji terakreditasi';

            if ($tone === 'MEDIA') {
                $s1 = "Dosen dan tenaga pendidik Universitas Harkat Negeri, {$subjectName}, sukses mengukuhkan rekognisi tingkat {$level} dalam bidang {$achType} yang diterbitkan oleh {$organizer}.";
                $s2 = "Pengakuan profesional ini mencerminkan keunggulan mutu pengajar kampus dalam menguasai standar keahlian terkini yang relevan dengan transformasi industri masa depan.";
                $s3 = "Peningkatan kualitas sumber daya manusia ini semakin memantapkan kredibilitas Universitas Harkat Negeri sebagai pusat keunggulan akademik yang diakui secara luas.";
            } elseif ($tone === 'INSPIRATIF') {
                $s1 = "Komitmen menembus batas keunggulan dibuktikan oleh {$subjectName} dari Universitas Harkat Negeri melalui keberhasilan meraih {$achType} tingkat {$level} dari {$organizer}.";
                $s2 = "Pencapaian prestisius ini menjadi teladan inspiratif bagi seluruh sivitas akademika dalam terus meningkatkan kapasitas diri di tengah persaingan era global.";
                $s3 = "Kiprah teladan ini memberikan andil signifikan bagi penguatan modal intelektual kampus dan pengakuan reputasi institusional Universitas Harkat Negeri.";
            } else { // STANDAR WURI
                $s1 = "Pengembangan Sumber Daya Manusia Universitas Harkat Negeri mencatat capaian strategis melalui perolehan {$achType} tingkat {$level} oleh {$subjectName} dari {$organizer}.";
                $s2 = "Keberhasilan pemenuhan kualifikasi ini memperkuat standar profesionalisme tenaga pendidik dalam memberikan pengajaran berkualitas dan transfer pengetahuan berbasis kompetensi.";
                $s3 = "Peningkatan rekognisi kompetensi pendidik ini berkontribusi langsung pada penguatan indikator kepemimpinan akademik institusi dalam pemeringkatan reputasi institusional WURI.";
            }
        } elseif ($unitCode === 'KEMAHASISWAAN') {
            $event = !empty($payload['event_name']) ? trim($payload['event_name']) : 'Kompetisi Inovasi';
            $rank = !empty($payload['achievement_rank']) ? trim($payload['achievement_rank']) : 'Juara';
            $level = !empty($payload['level']) ? ucwords(strtolower($payload['level'])) : 'Nasional';
            $organizer = !empty($payload['organizer']) ? trim($payload['organizer']) : 'penyelenggara resmi';

            if ($tone === 'MEDIA') {
                $s1 = "Mahasiswa Universitas Harkat Negeri, {$subjectName}, berhasil menorehkan prestasi gemilang dengan meraih {$rank} pada ajang {$event} tingkat {$level} yang dihelat oleh {$organizer}.";
                $s2 = "Dalam kompetisi bergengsi tersebut, karya bertajuk \"{$title}\" berhasil memukau dewan juri berkat pendekatan pemecahan masalah yang inovatif dan berorientasi solusi nyata.";
                $s3 = "Prestasi membanggakan ini menegaskan daya saing talenta muda Universitas Harkat Negeri di panggung kompetisi sekaligus melejitkan reputasi institusi pada pemeringkatan WURI.";
            } elseif ($tone === 'INSPIRATIF') {
                $s1 = "Semangat juang generasi muda Universitas Harkat Negeri kembali berbuah manis setelah {$subjectName} sukses menyabet {$rank} dalam ajang {$event} tingkat {$level}.";
                $s2 = "Melalui karya \"{$title}\", mahasiswa menunjukkan dedikasi pantang menyerah dan kecerdasan analitis untuk menghasilkan karya yang solutif bagi sesama.";
                $s3 = "Keberhasilan ini menjadi suntikan motivasi bagi seluruh mahasiswa dan mempertebal reputasi Universitas Harkat Negeri sebagai rahim lahirnya generasi inovator unggul.";
            } else { // STANDAR WURI
                $s1 = "Biro Kemahasiswaan Universitas Harkat Negeri melaporkan torehan prestasi mahasiswa atas nama {$subjectName} yang meraih {$rank} pada kompetisi {$event} tingkat {$level}.";
                $s2 = "Gagasan kreatif bertajuk \"{$title}\" dinilai unggul dalam aspek originalitas ide serta implementasi metode yang berdampak positif bagi pemecahan isu sosial dan teknologi.";
                $s3 = "Capaian prestasi mahasiswa ini memperkuat rekam jejak kepemimpinan etis dan prestasi generasi muda kampus dalam kerangka pemeringkatan reputasi internasional WURI.";
            }
        } else { // ALUMNI_KARIR
            $achType = !empty($payload['achievement_type']) ? ucwords(strtolower(str_replace('_', ' ', $payload['achievement_type']))) : 'Karier Profesional';
            $company = !empty($payload['company_or_institution']) ? trim($payload['company_or_institution']) : 'institusi terkemuka';
            $position = !empty($payload['position']) ? trim($payload['position']) : 'posisi strategis';
            $gradYear = !empty($payload['graduation_year']) ? " (Alumni Angkatan " . trim($payload['graduation_year']) . ")" : "";

            if ($tone === 'MEDIA') {
                $s1 = "Alumni Universitas Harkat Negeri, {$subjectName}{$gradYear}, menorehkan kiprah membanggakan dengan dipercaya menjabat sebagai {$position} di {$company}.";
                $s2 = "Karier cemerlang ini membuktikan keandalan kompetensi lulusan kampus dalam menjawab tantangan profesional dan berdaya saing di ekosistem kerja global.";
                $s3 = "Keberhasilan alumni di kancah industri mempertegas kontribusi nyata Universitas Harkat Negeri dalam melahirkan sumber daya manusia unggul yang berdampak bagi kemajuan bangsa.";
            } elseif ($tone === 'INSPIRATIF') {
                $s1 = "Jejak inspiratif diukir oleh {$subjectName}{$gradYear}, alumni Universitas Harkat Negeri yang kini sukses berkiprah sebagai {$position} di {$company}.";
                $s2 = "Perjalanan karier ini menjadi bukti bahwa modal karakter tangguh dan bekal keilmuan dari almamater mampu mengantarkan lulusan bersinar di pentas persaingan profesional.";
                $s3 = "Kiprah luar biasa ini membangkitkan rasa bangga sivitas akademika dan memperkokoh reputasi institusi sebagai pencetak pemimpin masa depan.";
            } else { // STANDAR WURI
                $s1 = "Pusat Karir dan Hubungan Alumni Universitas Harkat Negeri mencatat capaian kiprah lulusan {$subjectName}{$gradYear} yang berkarier sebagai {$position} pada {$company}.";
                $s2 = "Pencapaian ini mencerminkan relevansi kurikulum pendidikan tinggi dan kesiapan daya serap alumni dalam menempati posisi kunci di dunia usaha dan dunia industri.";
                $s3 = "Keterserapan dan kepemimpinan lulusan di sektor strategis menjadi indikator kunci yang menyokong pengakuan reputasi global institusi dalam pemeringkatan WURI.";
            }
        }

        $sentences = [trim($s1), trim($s2), trim($s3)];
        $fullNarrative = implode(' ', $sentences);

        return [
            'narrative' => $fullNarrative,
            'sentence_count' => 3,
            'sentences' => $sentences
        ];
    }

    /**
     * Generate Naskah Rilis Pers Lengkap untuk Bagian Komunikasi
     */
    public static function generatePressRelease(array $entry): array {
        $apiKey = self::getApiKey();

        // 1. Coba gunakan Google Gemini API jika kredensial aktif
        if (!empty($apiKey)) {
            $geminiResult = self::callGeminiPressRelease($entry, $apiKey);
            if ($geminiResult !== null) {
                $geminiResult['provider'] = 'gemini';
                $geminiResult['provider_label'] = 'Google Gemini AI (' . (self::getSetting('ai_model') ?? 'gemini-1.5-flash') . ')';
                return $geminiResult;
            }
        }

        // 2. Fallback: Sintesis Berita Jurnalistik Bawaan UHN (100% Reliable Offline)
        $localResult = self::synthesizeLocalPressRelease($entry);
        $localResult['provider'] = 'local_ai';
        $localResult['provider_label'] = 'UHN Newsroom Synthesizer';
        return $localResult;
    }

    /**
     * Memanggil Google Gemini API untuk menyusun Naskah Rilis Pers Jurnalistik Lengkap
     */
    private static function callGeminiPressRelease(array $entry, string $apiKey): ?array {
        $unitCode = $entry['unit_code'] ?? 'PPM';
        $title = $entry['title'] ?? 'Capaian Unggulan UHN';
        $shortDesc = $entry['short_description'] ?? '';
        $details = $entry['details'] ?? [];
        $consent = $entry['name_publication_consent'] ?? 'YA';

        $sub = self::extractSubjectSummary(array_merge($entry, $details));
        $subjectName = ($consent === 'TIDAK') ? $sub['anonymous_name'] : $sub['name'];

        $privacyWarning = ($consent === 'TIDAK')
            ? "PERINGATAN PRIVASI (UU PDP): Subjek menolak publikasi nama pribadi. DILARANG mencantumkan nama orang, gunakan sebutan institusional seperti 'Tim Peneliti UHN' atau 'Sivitas Akademika UHN'."
            : "Nama subjek yang dapat dicantumkan: {$subjectName}.";

        $customInst = self::getSetting('ai_system_instructions', '');
        $selectedModel = self::getSetting('ai_model', 'gemini-1.5-flash');

        $prompt = "Sebagai Senior Public Relations Officer & Chief Editor Kantor Komunikasi Publik Universitas Harkat Negeri (UHN), susunlah paket siaran pers lengkap dan caption media sosial untuk rilis media massa nasional dan pemeringkatan WURI.

DATA CAPAIAN INSTITUSI:
- Unit Kerja: {$unitCode}
- Judul Capaian: {$title}
- Ringkasan Awal: {$shortDesc}
- Rincian Subjek: {$subjectName} ({$sub['details']})
- {$privacyWarning}

" . (!empty(trim($customInst)) ? "PANDUAN KHUSUS INSTITUSI:\n" . trim($customInst) . "\n\n" : "") . "
FORMAT OUTPUT HARUS BERUPA JSON VALID PERSIS DENGAN STRUKTUR BERIKUT (tanpa pembuka/penutup selain JSON):
{
  \"headline\": \"Judul berita yang memikat, berstandar media cetak nasional (Kompas/Tempo style)\",
  \"lead\": \"Paragraf pembuka piramida terbalik (5W+1H) dengan dateline KAMPUS HARKAT NEGERI\",
  \"body\": \"Naskah berita 3-4 paragraf yang menguraikan konteks masalah, inovasi/solusi yang dihadirkan, kutipan apresiasi Rektor Universitas Harkat Negeri (Prof. Dr. Ir. H. Mulyadi, M.Sc.), serta relevansi dampak strategis pada pemeringkatan internasional WURI.\",
  \"social_caption\": \"Caption media sosial (Instagram/LinkedIn) yang menarik, rapi dengan emoji dan hashtag resmi #UniversitasHarkatNegeri #UHNImpact #WURIRankings #KampusBerdampak\",
  \"recommended_wuri_category\": \"Nama Klaster WURI yang paling tepat (misal: Industrial Application / Student Mobility / Leadership / Entrepreneurial Spirit)\",
  \"tags\": [\"Universitas Harkat Negeri\", \"WURI 2026/2027\", \"{$unitCode}\", \"Inovasi Kampus\", \"Reputasi\"]
}";

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$selectedModel}:generateContent?key=" . urlencode($apiKey);
        $reqBody = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.4,
                'maxOutputTokens' => 800
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($reqBody));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $resp = curl_exec($ch);
        if ($resp === false && in_array(curl_errno($ch), [60, 77], true)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $resp = curl_exec($ch);
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        @curl_close($ch);

        if ($httpCode === 200 && $resp) {
            $data = json_decode($resp, true);
            $rawText = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
            if ($rawText) {
                // Bersihkan Markdown block ```json ... ```
                $cleanJson = preg_replace('/^```(?:json)?\s*/i', '', trim($rawText));
                $cleanJson = preg_replace('/\s*```$/', '', $cleanJson);
                $parsed = json_decode(trim($cleanJson), true);

                if (is_array($parsed) && !empty($parsed['headline']) && !empty($parsed['body'])) {
                    return [
                        'headline' => trim($parsed['headline']),
                        'lead' => trim($parsed['lead'] ?? ''),
                        'body' => trim($parsed['body']),
                        'social_caption' => trim($parsed['social_caption'] ?? ''),
                        'recommended_wuri_category' => trim($parsed['recommended_wuri_category'] ?? 'Industrial Application (Klaster A1)'),
                        'tags' => is_array($parsed['tags'] ?? null) ? $parsed['tags'] : ['Universitas Harkat Negeri', 'WURI 2026/2027', $unitCode]
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Sintesis Berita Jurnalistik Bawaan UHN (Local Template)
     */
    private static function synthesizeLocalPressRelease(array $entry): array {
        $unitCode = $entry['unit_code'] ?? 'PPM';
        $title = $entry['title'] ?? 'Capaian Unggulan UHN';
        $shortDesc = $entry['short_description'] ?? '';
        $details = $entry['details'] ?? [];
        $consent = $entry['name_publication_consent'] ?? 'YA';

        $sub = self::extractSubjectSummary(array_merge($entry, $details));
        $subjectName = ($consent === 'TIDAK') ? $sub['anonymous_name'] : $sub['name'];

        // Kategori WURI yang direkomendasikan
        $wuriCategory = 'Industrial Application (Klaster A1) / Research Impact';
        if ($unitCode === 'PSDM') {
            $wuriCategory = 'Leadership & Capacity Building (Klaster B1)';
        } elseif ($unitCode === 'KEMAHASISWAAN') {
            $wuriCategory = 'Student Mobility & Innovation (Klaster B2) / Ethical Leadership';
        } elseif ($unitCode === 'ALUMNI_KARIR') {
            $wuriCategory = 'Entrepreneurial Spirit (Klaster C1) / Industry Placement';
        }

        $headline = "Torehkan Dampak Nyata: Universitas Harkat Negeri Catatkan Capaian Strategis Lewat \"{$title}\"";
        if ($unitCode === 'KEMAHASISWAAN') {
            $rank = $details['achievement_rank'] ?? 'Juara';
            $event = $details['event_name'] ?? 'Ajang Prestisius';
            $headline = "Banggakan Almamater, Mahasiswa UHN Raih {$rank} di Ajang {$event}";
        } elseif ($unitCode === 'PPM') {
            $ki = $details['ki_type'] ?? 'Kekayaan Intelektual';
            $headline = "Inovasi Hilirisasi UHN: Riset \"{$title}\" Resmi Peroleh Pengakuan {$ki}";
        } elseif ($unitCode === 'ALUMNI_KARIR') {
            $pos = $details['position'] ?? 'Posisi Strategis';
            $comp = $details['company_or_institution'] ?? 'Industri';
            $headline = "Kiprah Global Alumni UHN: Menembus Peran {$pos} di {$comp}";
        } elseif ($unitCode === 'PSDM') {
            $ach = $details['achievement_type'] ?? 'Kompetensi';
            $headline = "Dosen UHN Perkuat Kualifikasi Unggul Lewat Capaian {$ach}";
        }

        $lead = "KAMPUS HARKAT NEGERI — Universitas Harkat Negeri (UHN) kembali menegaskan komitmennya sebagai perguruan tinggi berorientasi dampak nyata bagi masyarakat dan industri.";
        
        $body = "{$lead}\n\n" .
                "Melalui koordinasi unit {$unitCode}, institusi mengumumkan capaian unggulan berupa \"{$title}\". " .
                (!empty($shortDesc) ? "{$shortDesc}\n\n" : "") .
                "Rektor Universitas Harkat Negeri, Prof. Dr. Ir. H. Mulyadi, M.Sc., menyampaikan apresiasi tinggi atas capaian ini. " .
                "\"Setiap karya dan prestasi yang lahir dari sivitas akademika UHN merupakan cerminan nyata dari visi kampus kami untuk hadir memberikan solusi konkret bagi bangsa, sekaligus memperkuat daya saing reputasi internasional institusi di pemeringkatan WURI,\" ungkapnya.\n\n" .
                "Ke depan, hasil capaian ini akan terus didorong melalui strategi diseminasi publik dan integrasi kemitraan lintas sektor demi keberlanjutan dampak positif.";

        $socialCaption = "✨ Capaian Membanggakan Universitas Harkat Negeri! ✨\n\n" .
                         "Sivitas akademika UHN kembali menorehkan prestasi membanggakan melalui: \"{$title}\".\n\n" .
                         "Karya ini menjadi bukti nyata komitmen UHN dalam menghadirkan inovasi dan kepemimpinan yang berdampak luas bagi kemajuan bangsa. Mari terus berkarya dan menginspirasi!\n\n" .
                         "#UniversitasHarkatNegeri #UHNImpact #WURIRankings #KampusBerdampak #BanggaUHN";

        return [
            'headline' => $headline,
            'lead' => $lead,
            'body' => $body,
            'social_caption' => $socialCaption,
            'recommended_wuri_category' => $wuriCategory,
            'tags' => ['Universitas Harkat Negeri', 'WURI 2026/2027', $unitCode, 'Inovasi Kampus', 'Reputasi Institusi']
        ];
    }

    /**
     * Memastikan string tepat berisi 3 kalimat
     */
    private static function enforceThreeSentences(string $text): array {
        $clean = trim($text);
        // Hapus petik pembungkus jika ada
        $clean = trim($clean, "\"'` \t\n\r\0\x0B");

        $sentences = preg_split('/(?<=[.!?])\s+/', $clean, -1, PREG_SPLIT_NO_EMPTY);
        $cleanSentences = [];
        foreach ($sentences as $s) {
            $st = trim($s);
            if (!empty($st)) {
                // Pastikan ada titik di ujung jika belum ada
                if (!preg_match('/[.!?]$/', $st)) {
                    $st .= '.';
                }
                $cleanSentences[] = $st;
            }
        }

        // Jika kurang dari 3 kalimat, lengkapi kalimat penutup WURI
        while (count($cleanSentences) < 3) {
            $cleanSentences[] = "Capaian strategis ini berkontribusi nyata pada penguatan reputasi Universitas Harkat Negeri di kancah nasional maupun internasional.";
        }

        // Jika lebih dari 3 kalimat, ambil 3 kalimat pertama
        if (count($cleanSentences) > 3) {
            $cleanSentences = array_slice($cleanSentences, 0, 3);
        }

        $resultText = implode(' ', $cleanSentences);
        return [
            'text' => $resultText,
            'count' => count($cleanSentences),
            'sentences' => $cleanSentences
        ];
    }

    /**
     * Ekstrak ringkasan subjek dari berbagai bentuk payload form
     */
    private static function extractSubjectSummary(array $payload): array {
        $unitCode = strtoupper($payload['unit_code'] ?? 'PPM');

        $name = 'Sivitas Akademika UHN';
        $anonymousName = 'Tim Inovator Kampus';
        $details = '';

        if ($unitCode === 'PPM') {
            $invNames = $payload['inventor_name'] ?? ($payload['inventors'] ?? []);
            if (is_array($invNames) && !empty($invNames)) {
                if (is_array($invNames[0])) {
                    $first = $invNames[0]['name'] ?? 'Peneliti UHN';
                } else {
                    $first = $invNames[0];
                }
                $count = count($invNames);
                $name = ($count > 1) ? "{$first} bersama tim peneliti" : $first;
                $details = "Inventor: " . (is_array($invNames[0]) ? implode(', ', array_column($invNames, 'name')) : implode(', ', $invNames));
            }
            $anonymousName = 'Tim Peneliti Pusat Penelitian dan Pengabdian Masyarakat UHN';
        } elseif ($unitCode === 'PSDM') {
            $name = $payload['employee_name'] ?? 'Dosen UHN';
            $prodi = $payload['prodi_or_unit'] ?? '';
            $details = "NIP: " . ($payload['employee_id'] ?? '-') . " | Unit/Prodi: " . $prodi;
            $anonymousName = 'Tenaga Pendidik Universitas Harkat Negeri';
        } elseif ($unitCode === 'KEMAHASISWAAN') {
            $name = $payload['student_name'] ?? 'Mahasiswa Berprestasi UHN';
            $prodi = $payload['prodi'] ?? '';
            $advisor = $payload['advisor_name'] ?? '';
            $details = "NIM: " . ($payload['student_nim'] ?? '-') . " | Prodi: " . $prodi . ($advisor ? " | Pembimbing: {$advisor}" : '');
            $anonymousName = 'Perwakilan Mahasiswa Berprestasi UHN';
        } elseif ($unitCode === 'ALUMNI_KARIR') {
            $name = $payload['alumni_name'] ?? 'Alumni UHN';
            $prodi = $payload['prodi'] ?? '';
            $year = $payload['graduation_year'] ?? '';
            $details = "Prodi: " . $prodi . ($year ? " | Lulus: {$year}" : '');
            $anonymousName = 'Alumni Berprestasi Universitas Harkat Negeri';
        }

        return [
            'name' => $name,
            'anonymous_name' => $anonymousName,
            'details' => $details,
            'summary' => "{$name} ({$details})"
        ];
    }

    /**
     * Uji Koneksi Live ke Google Gemini API (Ping & Latensi)
     */
    public static function testConnection(?string $testKey = null, ?string $testModel = null): array {
        $apiKey = $testKey ?: self::getApiKey();
        if (empty($apiKey)) {
            return [
                'success' => false,
                'latency_ms' => 0,
                'http_code' => 400,
                'error' => 'Google Gemini API Key belum diisi. Masukkan API key Anda pada formulir di bawah.'
            ];
        }

        $model = $testModel ?: self::getSetting('ai_model', 'gemini-1.5-flash');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($apiKey);

        $testPrompt = "Jawab dalam 1 kalimat pendek: Konfirmasi koneksi API Google Gemini dengan ERP Universitas Harkat Negeri berhasil.";
        $reqBody = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $testPrompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.2,
                'maxOutputTokens' => 60
            ]
        ];

        $start = microtime(true);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($reqBody));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $resp = curl_exec($ch);
        if ($resp === false && in_array(curl_errno($ch), [60, 77], true)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $resp = curl_exec($ch);
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $latency = round((microtime(true) - $start) * 1000);
        @curl_close($ch);

        if ($httpCode === 200 && $resp) {
            $data = json_decode($resp, true);
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Respons valid diterima dari model Gemini.';
            return [
                'success' => true,
                'latency_ms' => $latency,
                'model' => $model,
                'sample_response' => trim($text),
                'http_code' => 200,
                'message' => "Koneksi ke Google Gemini AI berhasil! Latensi: {$latency} ms."
            ];
        }

        $errorMessage = $curlError ?: "HTTP Error {$httpCode}";
        if ($resp) {
            $errData = json_decode($resp, true);
            if (!empty($errData['error']['message'])) {
                $errorMessage = $errData['error']['message'];
            }
        }

        return [
            'success' => false,
            'latency_ms' => $latency,
            'model' => $model,
            'error' => $errorMessage,
            'http_code' => $httpCode
        ];
    }

    /**
     * Dapatkan Statistik & Riwayat Penggunaan AI untuk Dashboard Admin
     */
    public static function getStats(): array {
        try {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT count(*) FROM audit_logs WHERE action IN ('AI_GENERATE', 'AI_PRESS_RELEASE')");
            $totalAI = (int)$stmt->fetchColumn();

            $stmtGemini = $db->query("SELECT count(*) FROM audit_logs WHERE action = 'AI_GENERATE' AND details LIKE '%gemini%'");
            $countGemini = (int)$stmtGemini->fetchColumn();

            $stmtLocal = $db->query("SELECT count(*) FROM audit_logs WHERE action = 'AI_GENERATE' AND details LIKE '%local_ai%'");
            $countLocal = (int)$stmtLocal->fetchColumn();

            $stmtRecent = $db->query("
                SELECT l.*, u.full_name, u.username 
                FROM audit_logs l 
                LEFT JOIN users u ON l.user_id = u.id 
                WHERE l.action IN ('AI_GENERATE', 'AI_PRESS_RELEASE', 'AI_CONFIG_UPDATE')
                ORDER BY l.id DESC LIMIT 10
            ");
            $recentLogs = $stmtRecent->fetchAll();

            return [
                'total_generations' => $totalAI,
                'gemini_count' => $countGemini,
                'local_count' => $countLocal,
                'recent_logs' => $recentLogs
            ];
        } catch (Exception $e) {
            return [
                'total_generations' => 0,
                'gemini_count' => 0,
                'local_count' => 0,
                'recent_logs' => []
            ];
        }
    }
}
