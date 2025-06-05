<?php
/**
 * Главный файл для подключения всех функций проекта
 */

// Подключаем все файлы с функциями
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/database_functions.php';
require_once __DIR__ . '/popup_functions.php';
require_once __DIR__ . '/appointment_functions.php';
require_once __DIR__ . '/auth_functions.php';
require_once __DIR__ . '/doctor_functions.php';


// Инициализация соединения с базой данных (если не инициализировано)
if (!isset($database)) {
    $database = connect_database();
    
    // Устанавливаем часовой пояс MySQL равным PHP
    $php_timezone = date_default_timezone_get();
    $database->query("SET time_zone = '" . date('P') . "'");
}
