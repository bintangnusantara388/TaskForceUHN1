<?php
// database/migrate_phone.php
require_once __DIR__ . '/../app/Database.php';

$db = Database::getConnection();

// 1. Update users table with phone column
$cols = $db->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_COLUMN, 1);
if (!in_array('phone', $cols)) {
    $db->exec("ALTER TABLE users ADD COLUMN phone TEXT DEFAULT NULL");
    echo "Added column 'phone' to users table.\n";
} else {
    echo "Column 'phone' already exists in users table.\n";
}

// 2. Set default active WhatsApp phone numbers for existing PICs
$phoneUpdates = [
    'pic_ppm' => '6281234567801',
    'pic_psdm' => '6281234567802',
    'pic_kemhs' => '6281234567803',
    'pic_alumni' => '6281234567804',
    'admin' => '6281112223330',
    'tim_komunikasi' => '6281298765432',
    'pimpinan_viewer' => '6281311223344'
];

$stmt = $db->prepare("UPDATE users SET phone = ? WHERE username = ? AND (phone IS NULL OR phone = '')");
foreach ($phoneUpdates as $uname => $ph) {
    $stmt->execute([$ph, $uname]);
}
echo "Populated default phone numbers for PIC accounts.\n";

// 3. Update reminder_notifications table with recipient_name, recipient_phone, channel
$remCols = $db->query("PRAGMA table_info(reminder_notifications)")->fetchAll(PDO::FETCH_COLUMN, 1);
if (!in_array('recipient_name', $remCols)) {
    $db->exec("ALTER TABLE reminder_notifications ADD COLUMN recipient_name TEXT DEFAULT NULL");
    echo "Added column 'recipient_name' to reminder_notifications.\n";
}
if (!in_array('recipient_phone', $remCols)) {
    $db->exec("ALTER TABLE reminder_notifications ADD COLUMN recipient_phone TEXT DEFAULT NULL");
    echo "Added column 'recipient_phone' to reminder_notifications.\n";
}
if (!in_array('channel', $remCols)) {
    $db->exec("ALTER TABLE reminder_notifications ADD COLUMN channel TEXT DEFAULT 'EMAIL'");
    echo "Added column 'channel' to reminder_notifications.\n";
}

echo "Database migration for direct PIC email & WhatsApp reminders completed successfully.\n";
