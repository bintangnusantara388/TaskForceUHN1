<?php
// app/Services/AIService.php
// Enterprise Multi-Provider AI Engine Manager with Auto-Failover, Priority Queue & Health Monitoring

require_once __DIR__ . '/../Config.php';
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Helpers.php';

class AIService {

    // =========================================================================
    // 1. SYSTEM SETTINGS METHODS
    // =========================================================================

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

    public static function getApiKey(): ?string {
        $dbKey = self::getSetting('gemini_api_key');
        if ($dbKey && !empty(trim($dbKey))) {
            return trim($dbKey);
        }
        if (defined('GEMINI_API_KEY') && !empty(GEMINI_API_KEY)) {
            return GEMINI_API_KEY;
        }
        $envKey = getenv('GEMINI_API_KEY');
        if ($envKey && !empty(trim($envKey))) {
            return trim($envKey);
        }
        if (!empty($_ENV['GEMINI_API_KEY'])) {
            return trim($_ENV['GEMINI_API_KEY']);
        }
        return null;
    }

    public static function isGeminiConfigured(): bool {
        return self::getApiKey() !== null;
    }

    // =========================================================================
    // 2. ENGINE REGISTRY & CRUD MANAGEMENT
    // =========================================================================

    public static function getAllEngines(): array {
        try {
            $db = Database::getConnection();
            return $db->query("SELECT * FROM ai_engines ORDER BY priority ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public static function getActiveEngines(): array {
        try {
            $db = Database::getConnection();
            return $db->query("SELECT * FROM ai_engines WHERE is_active = 1 ORDER BY priority ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public static function getEngine(int $id): ?array {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM ai_engines WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    public static function createEngine(array $data): int|false {
        try {
            $db = Database::getConnection();
            $maxPriority = (int)$db->query("SELECT MAX(priority) FROM ai_engines WHERE priority < 99")->fetchColumn() ?: 0;
            $priority = !empty($data['priority']) ? (int)$data['priority'] : ($maxPriority + 1);

            $stmt = $db->prepare("
                INSERT INTO ai_engines (
                    name, provider_type, model_name, api_key, endpoint_url,
                    is_active, priority, max_retries, timeout_seconds,
                    temperature, max_tokens, last_status, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'UNTESTED', CURRENT_TIMESTAMP)
            ");
            $ok = $stmt->execute([
                trim($data['name'] ?? 'AI Engine Baru'),
                strtoupper(trim($data['provider_type'] ?? 'GEMINI')),
                trim($data['model_name'] ?? 'gemini-1.5-flash'),
                trim($data['api_key'] ?? ''),
                trim($data['endpoint_url'] ?? ''),
                isset($data['is_active']) ? (int)$data['is_active'] : 1,
                $priority,
                max(1, (int)($data['max_retries'] ?? 2)),
                max(1, (int)($data['timeout_seconds'] ?? 6)),
                (float)($data['temperature'] ?? 0.4),
                (int)($data['max_tokens'] ?? 400)
            ]);

            return $ok ? (int)$db->lastInsertId() : false;
        } catch (Exception $e) {
            return false;
        }
    }

    public static function updateEngine(int $id, array $data): bool {
        try {
            $db = Database::getConnection();
            $existing = self::getEngine($id);
            if (!$existing) return false;

            $apiKey = isset($data['api_key']) && trim($data['api_key']) !== '' 
                ? trim($data['api_key']) 
                : $existing['api_key'];

            $stmt = $db->prepare("
                UPDATE ai_engines SET 
                    name = ?,
                    provider_type = ?,
                    model_name = ?,
                    api_key = ?,
                    endpoint_url = ?,
                    is_active = ?,
                    priority = ?,
                    max_retries = ?,
                    timeout_seconds = ?,
                    temperature = ?,
                    max_tokens = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            return $stmt->execute([
                trim($data['name'] ?? $existing['name']),
                strtoupper(trim($data['provider_type'] ?? $existing['provider_type'])),
                trim($data['model_name'] ?? $existing['model_name']),
                $apiKey,
                trim($data['endpoint_url'] ?? $existing['endpoint_url']),
                isset($data['is_active']) ? (int)$data['is_active'] : (int)$existing['is_active'],
                isset($data['priority']) ? (int)$data['priority'] : (int)$existing['priority'],
                max(1, (int)($data['max_retries'] ?? $existing['max_retries'])),
                max(1, (int)($data['timeout_seconds'] ?? $existing['timeout_seconds'])),
                (float)($data['temperature'] ?? $existing['temperature']),
                (int)($data['max_tokens'] ?? $existing['max_tokens']),
                $id
            ]);
        } catch (Exception $e) {
            return false;
        }
    }

    public static function deleteEngine(int $id): bool {
        try {
            // Lindungi engine local synthesizer (ID 4) agar selalu ada
            if ($id === 4) return false;
            $db = Database::getConnection();
            $stmt = $db->prepare("DELETE FROM ai_engines WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            return false;
        }
    }

    public static function toggleEngine(int $id, ?int $forceStatus = null): bool {
        try {
            $db = Database::getConnection();
            $existing = self::getEngine($id);
            if (!$existing) return false;
            $newStatus = ($forceStatus !== null) ? $forceStatus : ($existing['is_active'] ? 0 : 1);
            $newLastStatus = ($newStatus === 0) ? 'DISABLED' : ($existing['last_status'] === 'DISABLED' ? 'UNTESTED' : $existing['last_status']);

            $stmt = $db->prepare("UPDATE ai_engines SET is_active = ?, last_status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            return $stmt->execute([$newStatus, $newLastStatus, $id]);
        } catch (Exception $e) {
            return false;
        }
    }

    public static function setPriority(int $id, int $priority): bool {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("UPDATE ai_engines SET priority = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            return $stmt->execute([$priority, $id]);
        } catch (Exception $e) {
            return false;
        }
    }

    public static function swapPriority(int $id, string $direction): bool {
        try {
            $engines = self::getAllEngines();
            $targetIndex = null;
            foreach ($engines as $idx => $eng) {
                if ((int)$eng['id'] === $id) {
                    $targetIndex = $idx;
                    break;
                }
            }
            if ($targetIndex === null) return false;

            $swapWithIndex = null;
            if ($direction === 'UP' && $targetIndex > 0) {
                $swapWithIndex = $targetIndex - 1;
            } elseif ($direction === 'DOWN' && $targetIndex < count($engines) - 1) {
                $swapWithIndex = $targetIndex + 1;
            }

            if ($swapWithIndex !== null) {
                $engA = $engines[$targetIndex];
                $engB = $engines[$swapWithIndex];
                self::setPriority((int)$engA['id'], (int)$engB['priority']);
                self::setPriority((int)$engB['id'], (int)$engA['priority']);
                return true;
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    public static function updateEngineStatus(int $id, string $status, int $latencyMs = 0, ?string $errorMessage = null): void {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                UPDATE ai_engines SET 
                    last_status = ?,
                    last_latency_ms = ?,
                    last_checked_at = CURRENT_TIMESTAMP,
                    last_error_message = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$status, $latencyMs, $errorMessage, $id]);
        } catch (Exception $e) {}
    }

    public static function logFailover(string $requestType, ?int $failedId, ?string $failedName, string $error, int $retries, ?int $rescuedId, ?string $rescuedName): bool {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                INSERT INTO ai_failover_logs (request_type, failed_engine_id, failed_engine_name, error_message, retry_count, rescued_engine_id, rescued_engine_name)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([$requestType, $failedId, $failedName, $error, $retries, $rescuedId, $rescuedName]);
        } catch (Exception $e) {
            return false;
        }
    }

    public static function getFailoverLogs(int $limit = 20): array {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM ai_failover_logs ORDER BY id DESC LIMIT ?");
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public static function clearFailoverLogs(): bool {
        try {
            $db = Database::getConnection();
            return (bool)$db->exec("DELETE FROM ai_failover_logs");
        } catch (Exception $e) {
            return false;
        }
    }

    // =========================================================================
    // 3. MONITORING & HEALTH CHECKS
    // =========================================================================

    public static function getEngineHealthSummary(): array {
        $engines = self::getAllEngines();
        $total = count($engines);
        $active = 0;
        $healthy = 0;
        $error = 0;
        $disabled = 0;

        foreach ($engines as $eng) {
            if ($eng['is_active']) {
                $active++;
                if ($eng['last_status'] === 'HEALTHY') $healthy++;
                elseif ($eng['last_status'] === 'ERROR') $error++;
            } else {
                $disabled++;
            }
        }

        return [
            'total' => $total,
            'active' => $active,
            'healthy' => $healthy,
            'error' => $error,
            'disabled' => $disabled,
            'engines' => $engines
        ];
    }

    public static function testEngine(int $id): array {
        $engine = self::getEngine($id);
        if (!$engine) {
            return ['success' => false, 'message' => 'Engine AI tidak ditemukan.', 'latency_ms' => 0];
        }

        $provider = strtoupper(trim($engine['provider_type']));
        $start = microtime(true);
        $testPrompt = "Konfirmasi singkat: ERP Universitas Harkat Negeri terhubung.";

        if ($provider === 'LOCAL_SYNTHESIZER') {
            $latency = round((microtime(true) - $start) * 1000);
            self::updateEngineStatus($id, 'HEALTHY', $latency, null);
            return [
                'success' => true,
                'provider' => 'LOCAL_SYNTHESIZER',
                'model' => $engine['model_name'],
                'latency_ms' => $latency,
                'sample_response' => 'UHN Neural Synthesizer siap digunakan (Zero Latency Offline).',
                'http_code' => 200,
                'message' => 'Engine lokal offline sehat dan siap digunakan 100% tanpa jaringan.'
            ];
        }

        $apiKey = trim($engine['api_key'] ?? '') ?: self::getApiKey();
        if (empty($apiKey)) {
            $msg = 'API Key belum diisi untuk engine ini.';
            self::updateEngineStatus($id, 'ERROR', 0, $msg);
            return [
                'success' => false,
                'provider' => $provider,
                'model' => $engine['model_name'],
                'latency_ms' => 0,
                'error' => $msg,
                'message' => $msg
            ];
        }

        $rawOutput = null;
        $errDetail = null;

        try {
            if ($provider === 'GEMINI') {
                $rawOutput = self::callGeminiApi($engine, $testPrompt, 50, 0.2);
            } elseif ($provider === 'OPENAI_COMPATIBLE') {
                $rawOutput = self::callOpenAiCompatibleApi($engine, $testPrompt, 50, 0.2);
            }
        } catch (Exception $e) {
            $errDetail = $e->getMessage();
        }

        $latency = round((microtime(true) - $start) * 1000);

        if ($rawOutput !== null && !empty(trim($rawOutput))) {
            self::updateEngineStatus($id, 'HEALTHY', $latency, null);
            return [
                'success' => true,
                'provider' => $provider,
                'model' => $engine['model_name'],
                'latency_ms' => $latency,
                'sample_response' => trim($rawOutput),
                'http_code' => 200,
                'message' => "Koneksi ke {$engine['name']} berhasil! Waktu respon: {$latency} ms."
            ];
        }

        $failMsg = $errDetail ?: "Koneksi gagal atau model tidak memberikan respon valid.";
        self::updateEngineStatus($id, 'ERROR', $latency, $failMsg);
        return [
            'success' => false,
            'provider' => $provider,
            'model' => $engine['model_name'],
            'latency_ms' => $latency,
            'error' => $failMsg,
            'message' => "Pengujian ke {$engine['name']} gagal: {$failMsg}"
        ];
    }

    public static function testAllEngines(): array {
        $engines = self::getActiveEngines();
        $results = [];
        $healthyCount = 0;
        $errorCount = 0;

        foreach ($engines as $eng) {
            $res = self::testEngine((int)$eng['id']);
            $results[] = array_merge($res, [
                'engine_id' => $eng['id'],
                'name' => $eng['name'],
                'priority' => $eng['priority']
            ]);
            if ($res['success']) $healthyCount++;
            else $errorCount++;
        }

        return [
            'total_tested' => count($engines),
            'healthy_count' => $healthyCount,
            'error_count' => $errorCount,
            'results' => $results
        ];
    }

    // =========================================================================
    // 4. AUTO-FAILOVER & RETRY MECHANISM
    // =========================================================================

    public static function executeWithRetry(array $engine, string $type, array $payload, string $tone = 'STANDAR'): array {
        $maxRetries = max(1, (int)($engine['max_retries'] ?? 2));
        $provider = strtoupper(trim($engine['provider_type'] ?? 'LOCAL_SYNTHESIZER'));
        $timeout = (int)($engine['timeout_seconds'] ?? 6);
        $temperature = (float)($engine['temperature'] ?? 0.4);
        $maxTokens = (int)($engine['max_tokens'] ?? ($type === 'NARRATIVE' ? 400 : 800));

        // 1. Local Synthesizer Execution (Instant Zero-Network)
        if ($provider === 'LOCAL_SYNTHESIZER') {
            $start = microtime(true);
            if ($type === 'NARRATIVE') {
                $res = self::synthesizeLocalNarrative($payload, $tone);
                $clean = self::enforceThreeSentences($res['narrative']);
                return [
                    'success' => true,
                    'narrative' => $clean['text'],
                    'sentence_count' => $clean['count'],
                    'sentences' => $clean['sentences'],
                    'latency_ms' => round((microtime(true) - $start) * 1000),
                    'provider' => 'local_ai',
                    'provider_label' => $engine['name'],
                    'engine_id' => $engine['id'],
                    'attempts' => 1
                ];
            } else {
                $res = self::synthesizeLocalPressRelease($payload);
                return [
                    'success' => true,
                    'press_release' => $res,
                    'latency_ms' => round((microtime(true) - $start) * 1000),
                    'provider' => 'local_ai',
                    'provider_label' => $engine['name'],
                    'engine_id' => $engine['id'],
                    'attempts' => 1
                ];
            }
        }

        // 2. Persiapan Prompt Cloud
        if ($type === 'NARRATIVE') {
            $prompt = self::buildNarrativePrompt($payload, $tone);
        } else {
            $prompt = self::buildPressReleasePrompt($payload);
        }

        $lastError = 'Tidak ada respon dari provider';
        $attempts = 0;
        $startOverall = microtime(true);

        // 3. Retry Loop untuk engine ini
        for ($i = 1; $i <= $maxRetries; $i++) {
            $attempts++;
            $startAttempt = microtime(true);
            $rawOutput = null;

            try {
                if ($provider === 'GEMINI') {
                    $rawOutput = self::callGeminiApi($engine, $prompt, $maxTokens, $temperature);
                } elseif ($provider === 'OPENAI_COMPATIBLE') {
                    $rawOutput = self::callOpenAiCompatibleApi($engine, $prompt, $maxTokens, $temperature);
                }
            } catch (Exception $e) {
                $lastError = $e->getMessage();
            }

            if ($rawOutput !== null && !empty(trim($rawOutput))) {
                $latency = round((microtime(true) - $startAttempt) * 1000);
                self::updateEngineStatus((int)$engine['id'], 'HEALTHY', $latency, null);

                if ($type === 'NARRATIVE') {
                    $clean = self::enforceThreeSentences($rawOutput);
                    return [
                        'success' => true,
                        'narrative' => $clean['text'],
                        'sentence_count' => $clean['count'],
                        'sentences' => $clean['sentences'],
                        'latency_ms' => $latency,
                        'provider' => strtolower($provider),
                        'provider_label' => "{$engine['name']} ({$engine['model_name']})",
                        'engine_id' => $engine['id'],
                        'attempts' => $attempts
                    ];
                } else {
                    $parsed = self::parsePressReleaseJson($rawOutput, $payload);
                    return [
                        'success' => true,
                        'press_release' => $parsed,
                        'latency_ms' => $latency,
                        'provider' => strtolower($provider),
                        'provider_label' => "{$engine['name']} ({$engine['model_name']})",
                        'engine_id' => $engine['id'],
                        'attempts' => $attempts
                    ];
                }
            }

            // Backoff singkat sebelum retry (300ms)
            if ($i < $maxRetries) {
                usleep(300000);
            }
        }

        $latency = round((microtime(true) - $startOverall) * 1000);
        $errorMsg = "Gagal setelah {$attempts}x percobaan: {$lastError}";
        self::updateEngineStatus((int)$engine['id'], 'ERROR', $latency, $errorMsg);

        return [
            'success' => false,
            'error' => $errorMsg,
            'latency_ms' => $latency,
            'engine_id' => $engine['id'],
            'attempts' => $attempts
        ];
    }

    /**
     * Generate Narasi 3 Kalimat dengan Multi-Engine Priority Queue & Auto-Failover
     */
    public static function generateNarrative(array $payload, string $tone = 'STANDAR'): array {
        $title = trim($payload['title'] ?? '');
        if (empty($title)) {
            $payload['title'] = 'Capaian Strategis Institusi';
        }

        $engines = self::getActiveEngines();
        if (empty($engines)) {
            $fallback = self::synthesizeLocalNarrative($payload, $tone);
            return [
                'success' => true,
                'narrative' => $fallback['narrative'],
                'sentence_count' => $fallback['sentence_count'],
                'provider' => 'local_ai',
                'provider_label' => 'UHN Neural Synthesizer (Zero Config Fallback)',
                'tone' => $tone,
                'sentences' => $fallback['sentences'],
                'failover_chain' => [],
                'failover_occurred' => false,
                'message' => 'Narasi digenerate menggunakan engine lokal bawaan.'
            ];
        }

        $failedEngines = [];
        $failoverChain = [];

        foreach ($engines as $engine) {
            $res = self::executeWithRetry($engine, 'NARRATIVE', $payload, $tone);

            if ($res['success']) {
                $failoverOccurred = count($failedEngines) > 0;
                if ($failoverOccurred) {
                    $lastFailed = end($failedEngines);
                    self::logFailover(
                        'NARRATIVE',
                        $lastFailed['id'],
                        $lastFailed['name'],
                        $lastFailed['error'],
                        $lastFailed['attempts'],
                        $engine['id'],
                        $engine['name']
                    );
                }

                return [
                    'success' => true,
                    'narrative' => $res['narrative'],
                    'sentence_count' => $res['sentence_count'],
                    'provider' => $res['provider'],
                    'provider_label' => $res['provider_label'],
                    'tone' => $tone,
                    'sentences' => $res['sentences'],
                    'engine_id' => $res['engine_id'],
                    'attempts' => $res['attempts'],
                    'latency_ms' => $res['latency_ms'],
                    'failover_occurred' => $failoverOccurred,
                    'failover_chain' => $failoverChain,
                    'message' => $failoverOccurred 
                        ? "Auto-Failover Aktif: Dialihkan ke {$res['provider_label']} setelah engine prioritas sebelumnya mengalami kendala."
                        : "Narasi 3 kalimat berhasil digenerate menggunakan {$res['provider_label']}."
                ];
            } else {
                $failedEngines[] = [
                    'id' => $engine['id'],
                    'name' => $engine['name'],
                    'error' => $res['error'],
                    'attempts' => $res['attempts']
                ];
                $failoverChain[] = "{$engine['name']} ({$res['attempts']}x retry gagal)";
            }
        }

        // Jaring pengaman terakhir: Local Synthesizer
        $fallback = self::synthesizeLocalNarrative($payload, $tone);
        return [
            'success' => true,
            'narrative' => $fallback['narrative'],
            'sentence_count' => $fallback['sentence_count'],
            'provider' => 'local_ai',
            'provider_label' => 'UHN Neural Synthesizer (Auto-Failover Final)',
            'tone' => $tone,
            'sentences' => $fallback['sentences'],
            'failover_occurred' => true,
            'failover_chain' => $failoverChain,
            'message' => 'Seluruh engine cloud mengalami kendala; sistem secara otomatis beralih ke UHN Neural Synthesizer agar layanan tetap berjalan.'
        ];
    }

    /**
     * Generate Siaran Pers Lengkap dengan Multi-Engine Priority Queue & Auto-Failover
     */
    public static function generatePressRelease(array $entry): array {
        $engines = self::getActiveEngines();
        if (empty($engines)) {
            $localResult = self::synthesizeLocalPressRelease($entry);
            $localResult['provider'] = 'local_ai';
            $localResult['provider_label'] = 'UHN Newsroom Synthesizer';
            return $localResult;
        }

        $failedEngines = [];
        $failoverChain = [];

        foreach ($engines as $engine) {
            $res = self::executeWithRetry($engine, 'PRESS_RELEASE', $entry);

            if ($res['success']) {
                $failoverOccurred = count($failedEngines) > 0;
                if ($failoverOccurred) {
                    $lastFailed = end($failedEngines);
                    self::logFailover(
                        'PRESS_RELEASE',
                        $lastFailed['id'],
                        $lastFailed['name'],
                        $lastFailed['error'],
                        $lastFailed['attempts'],
                        $engine['id'],
                        $engine['name']
                    );
                }

                $pr = $res['press_release'];
                $pr['provider'] = $res['provider'];
                $pr['provider_label'] = $res['provider_label'];
                $pr['failover_occurred'] = $failoverOccurred;
                $pr['failover_chain'] = $failoverChain;
                return $pr;
            } else {
                $failedEngines[] = [
                    'id' => $engine['id'],
                    'name' => $engine['name'],
                    'error' => $res['error'],
                    'attempts' => $res['attempts']
                ];
                $failoverChain[] = "{$engine['name']} ({$res['attempts']}x retry)";
            }
        }

        $localResult = self::synthesizeLocalPressRelease($entry);
        $localResult['provider'] = 'local_ai';
        $localResult['provider_label'] = 'UHN Newsroom Synthesizer (Auto-Failover Final)';
        $localResult['failover_occurred'] = true;
        $localResult['failover_chain'] = $failoverChain;
        return $localResult;
    }

    // =========================================================================
    // 5. CALLERS PER PROVIDER (API PROTOCOLS)
    // =========================================================================

    private static function callGeminiApi(array $engine, string $prompt, int $maxTokens, float $temperature): ?string {
        $model = trim($engine['model_name'] ?? 'gemini-1.5-flash');
        $apiKey = trim($engine['api_key'] ?? '') ?: self::getApiKey();
        $timeout = max(2, (int)($engine['timeout_seconds'] ?? 6));

        if (empty($apiKey)) return null;

        $endpointTemplate = !empty(trim($engine['endpoint_url'] ?? ''))
            ? trim($engine['endpoint_url'])
            : 'https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent';

        $endpoint = str_replace('{model}', $model, $endpointTemplate);
        if (!str_contains($endpoint, 'key=')) {
            $separator = str_contains($endpoint, '?') ? '&' : '?';
            $endpoint .= $separator . 'key=' . urlencode($apiKey);
        }

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

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($reqBody));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
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
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
            if ($text && !empty(trim($text))) {
                return trim($text);
            }
        }
        return null;
    }

    private static function callOpenAiCompatibleApi(array $engine, string $prompt, int $maxTokens, float $temperature): ?string {
        $endpoint = !empty(trim($engine['endpoint_url'] ?? '')) 
            ? trim($engine['endpoint_url']) 
            : 'https://api.openai.com/v1/chat/completions';
        $apiKey = trim($engine['api_key'] ?? '');
        $model = trim($engine['model_name'] ?? 'gpt-4o-mini');
        $timeout = max(2, (int)($engine['timeout_seconds'] ?? 8));

        $body = [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => $temperature,
            'max_tokens' => $maxTokens
        ];

        $headers = ['Content-Type: application/json'];
        if (!empty($apiKey)) {
            $headers[] = 'Authorization: Bearer ' . $apiKey;
        }

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
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
            $text = $data['choices'][0]['message']['content'] ?? null;
            if ($text && !empty(trim($text))) {
                return trim($text);
            }
        }
        return null;
    }

    // =========================================================================
    // 6. PROMPT BUILDERS & UTILITIES
    // =========================================================================

    private static function buildNarrativePrompt(array $payload, string $tone): string {
        $unitCode = $payload['unit_code'] ?? 'PPM';
        $title = $payload['title'] ?? '';
        $tier = $payload['tier_type'] ?? 'REGULER';
        $consent = $payload['name_publication_consent'] ?? 'YA';

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
- Unit: {$unitCode}
- Judul Capaian: {$title}
- Tingkat Prioritas: {$tier}
- Subjek/Pelaku: {$subject['name']} ({$subject['details']})
- {$privacyGuide}

STRUKTUR 3 KALIMAT WAJIB:
1. Kalimat 1: Jelaskan capaian apa yang diraih atau dihasilkan oleh sivitas akademika Universitas Harkat Negeri secara jelas dan membanggakan.
2. Kalimat 2: Uraikan manfaat konkret, solusi yang dihadirkan, atau keunggulan capaian bagi masyarakat/industri/mitra.
3. Kalimat 3: Tegaskan dampak strategis capaian terhadap penguatan reputasi institusi dan kontribusinya pada pemeringkatan internasional WURI.

ATURAN KETAT:
- WAJIB TEPAT 3 KALIMAT. Jangan kurang dari 3 kalimat, jangan lebih dari 3 kalimat.
- Gunakan bahasa awam yang komunikatif, hindari jargon teknis rumit.
- {$selectedToneGuide}
- Output HANYA berupa teks narasi 3 kalimat tersebut. Jangan berikan kalimat pembuka/penutup, jangan gunakan tanda petik ganda membungkus seluruh teks, dan jangan gunakan bullet points.";

        $customInst = self::getSetting('ai_system_instructions', '');
        if (!empty(trim($customInst))) {
            $prompt .= "\n\nPANDUAN KHUSUS INSTITUSI:\n" . trim($customInst);
        }

        return $prompt;
    }

    private static function buildPressReleasePrompt(array $entry): string {
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

        return $prompt;
    }

    private static function parsePressReleaseJson(string $rawText, array $payload): array {
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
                'tags' => is_array($parsed['tags'] ?? null) ? $parsed['tags'] : ['Universitas Harkat Negeri', 'WURI 2026/2027', $payload['unit_code'] ?? 'UHN']
            ];
        }

        return self::synthesizeLocalPressRelease($payload);
    }

    public static function enforceThreeSentences(string $text): array {
        $clean = trim($text);
        $clean = trim($clean, "\"'` \t\n\r\0\x0B");

        $sentences = preg_split('/(?<=[.!?])\s+/', $clean, -1, PREG_SPLIT_NO_EMPTY);
        $cleanSentences = [];
        foreach ($sentences as $s) {
            $st = trim($s);
            if (!empty($st)) {
                $cleanSentences[] = $st;
            }
        }

        $cnt = count($cleanSentences);
        if ($cnt === 3) {
            return [
                'text' => implode(' ', $cleanSentences),
                'count' => 3,
                'sentences' => $cleanSentences
            ];
        }

        if ($cnt > 3) {
            $cleanSentences = array_slice($cleanSentences, 0, 3);
            return [
                'text' => implode(' ', $cleanSentences),
                'count' => 3,
                'sentences' => $cleanSentences
            ];
        }

        while (count($cleanSentences) < 3) {
            $cleanSentences[] = "Capaian ini semakin memantapkan peran aktif institusi dalam memberikan dampak nyata bagi masyarakat dan reputasi internasional.";
        }

        return [
            'text' => implode(' ', $cleanSentences),
            'count' => 3,
            'sentences' => $cleanSentences
        ];
    }

    public static function extractSubjectSummary(array $item): array {
        $unit = $item['unit_code'] ?? 'PPM';
        $name = 'Sivitas Akademika UHN';
        $anon = 'Sivitas Akademika UHN (Anonim)';
        $details = 'Tridarma Perguruan Tinggi';

        if ($unit === 'PPM') {
            if (!empty($item['inventors']) && is_array($item['inventors'])) {
                $lead = $item['inventors'][0] ?? [];
                $name = $lead['name'] ?? 'Tim Peneliti PPM';
                $prodi = $lead['prodi'] ?? 'Riset';
                $details = "Inovator {$prodi}";
            } else {
                $name = $item['lead_inventor_name'] ?? 'Tim Periset UHN';
                $details = 'Inventor Kekayaan Intelektual';
            }
            $anon = 'Tim Periset Universitas Harkat Negeri';
        } elseif ($unit === 'PSDM') {
            $name = $item['employee_name'] ?? 'Pegawai Berprestasi UHN';
            $unitProdi = $item['unit_or_prodi'] ?? 'UHN';
            $ach = $item['achievement_type'] ?? 'Kompetensi';
            $details = "{$ach} - {$unitProdi}";
            $anon = 'Dosen/Tenaga Kependidikan UHN';
        } elseif ($unit === 'KEMAHASISWAAN') {
            $name = $item['student_name'] ?? 'Mahasiswa Berprestasi UHN';
            $prodi = $item['prodi'] ?? 'Program Studi UHN';
            $event = $item['event_name'] ?? 'Kompetisi';
            $details = "Mahasiswa {$prodi} ({$event})";
            $anon = 'Delegasi Mahasiswa UHN';
        } elseif ($unit === 'ALUMNI_KARIR') {
            $name = $item['alumni_name'] ?? 'Alumni Berprestasi UHN';
            $prodi = $item['prodi'] ?? 'Alumni UHN';
            $comp = $item['company_or_institution'] ?? 'Mitra Industri';
            $details = "Alumni {$prodi} di {$comp}";
            $anon = 'Alumni Berprestasi UHN';
        }

        return [
            'name' => $name,
            'anonymous_name' => $anon,
            'details' => $details
        ];
    }

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
            } else {
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
            } else {
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
            } else {
                $s1 = "Biro Kemahasiswaan Universitas Harkat Negeri melaporkan torehan prestasi mahasiswa atas nama {$subjectName} yang meraih {$rank} pada kompetisi {$event} tingkat {$level}.";
                $s2 = "Gagasan kreatif bertajuk \"{$title}\" dinilai unggul dalam aspek originalitas ide serta implementasi metode yang berdampak positif bagi pemecahan isu sosial dan teknologi.";
                $s3 = "Capaian prestasi mahasiswa ini memperkuat rekam jejak kepemimpinan etis dan prestasi generasi muda kampus dalam kerangka pemeringkatan reputasi internasional WURI.";
            }
        } else {
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
            } else {
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

    public static function synthesizeLocalPressRelease(array $entry): array {
        $unitCode = $entry['unit_code'] ?? 'PPM';
        $title = $entry['title'] ?? 'Capaian Strategis Kampus';
        $shortDesc = $entry['short_description'] ?? '';
        $details = $entry['details'] ?? [];
        $consent = $entry['name_publication_consent'] ?? 'YA';

        $sub = self::extractSubjectSummary(array_merge($entry, $details));
        $subjectName = ($consent === 'TIDAK') ? $sub['anonymous_name'] : $sub['name'];

        $wuriCategory = 'Industrial Application (Klaster A1)';
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
