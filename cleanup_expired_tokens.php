<?php
/**
 * Скрипт для очистки истекших токенов сброса пароля
 * Можно запускать через cron или вручную
 */

require_once __DIR__ . '/includes/init.php';

// Удаляем истекшие токены
$stmt = $database->prepare("DELETE FROM password_reset_tokens WHERE expires_at < NOW()");
$result = $stmt->execute();

if ($result) {
    $deleted_count = $database->affected_rows;
    echo date('Y-m-d H:i:s') . " - Удалено истекших токенов: $deleted_count\n";
    
    // Логируем в файл
    $log_message = date('Y-m-d H:i:s') . " - Cleanup: Deleted $deleted_count expired password reset tokens\n";
    file_put_contents(__DIR__ . '/logs/cleanup.log', $log_message, FILE_APPEND | LOCK_EX);
} else {
    echo date('Y-m-d H:i:s') . " - Ошибка при очистке токенов\n";
}
?>
