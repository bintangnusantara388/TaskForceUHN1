<?php
// app/Models/Audit.php
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Auth.php';

class Audit {
    public static function log(string $action, ?string $tableName = null, ?int $recordId = null, ?string $details = null): void {
        try {
            $db = Database::getConnection();
            $user = Auth::user();
            $userId = $user['id'] ?? 1;

            $stmt = $db->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id, details) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $action, $tableName, $recordId, $details]);
        } catch (Exception $e) {
            // Silently fail logging so business process is not interrupted
            error_log("Audit log failed: " . $e->getMessage());
        }
    }

    public static function getRecent(int $limit = 50): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT a.*, u.full_name as user_name, u.role as user_role, u.unit_code as user_unit
            FROM audit_logs a
            LEFT JOIN users u ON a.user_id = u.id
            ORDER BY a.id DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}
