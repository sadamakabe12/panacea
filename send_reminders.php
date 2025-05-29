<?php
/**
 * Скрипт для отправки напоминаний о предстоящих записях на прием
 * 
 * Этот скрипт предназначен для запуска через планировщик задач (cron) 
 * и отправляет напоминания пациентам о записях, запланированных на следующий день.
 */

// Включаем необходимые файлы
require_once(__DIR__ . '/includes/db.php');
require_once(__DIR__ . '/includes/notification_functions.php');

// Инициализируем систему уведомлений
initialize_notification_system($database);

// Переходим в режим CLI (для запуска через cron)
if (php_sapi_name() !== 'cli') {
    // Если запущен через веб, устанавливаем заголовки и проверяем авторизацию
    header('Content-Type: text/plain');
    
    session_start();
    if (!isset($_SESSION['user_id']) || $_SESSION['type'] !== 'admin') {
        echo "Ошибка: Доступ запрещен.\n";
        exit;
    }
    
    echo "Запуск отправки напоминаний в режиме браузера...\n";
} else {
    echo "Запуск отправки напоминаний в режиме CLI...\n";
}

// Запускаем фактическую отправку напоминаний
echo "Отправка напоминаний за 1 день до приема...\n";
$sent_count = send_appointment_reminders_batch($database, 1);
echo "Отправлено напоминаний: $sent_count\n";

// Также можно отправить напоминания за 3 дня до приема
echo "Отправка напоминаний за 3 дня до приема...\n";
$sent_count_3days = send_appointment_reminders_batch($database, 3);
echo "Отправлено напоминаний: $sent_count_3days\n";

echo "Готово!\n";
