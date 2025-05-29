<?php
/**
 * Общие функции для проекта
 */

/**
 * Функция форматирования даты
 * @param string $date Дата в формате Y-m-d
 * @param string $format Формат даты (по умолчанию d.m.Y)
 * @return string Отформатированная дата
 */
function format_date($date, $format = 'd.m.Y') {
    return date($format, strtotime($date));
}

/**
 * Функция для безопасного вывода данных
 * @param string $str Строка для экранирования
 * @return string Экранированная строка
 */
function safe_output($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Функция проверки авторизации пользователя
 * @param string $user_type Тип пользователя (admin, doctor, patient)
 * @return bool Результат проверки
 */
function check_auth($user_type = '') {
    session_start();
    if (!isset($_SESSION["user"])) {
        return false;
    }
    if ($user_type && $_SESSION["usertype"] != $user_type) {
        return false;
    }
    return true;
}

/**
 * Перенаправление пользователя
 * @param string $url URL для перенаправления
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Функция generate_csrf_token перенесена в auth_functions.php
 * @see auth_functions.php
 */

/**
 * Функция verify_csrf_token перенесена в auth_functions.php
 * @see auth_functions.php
 */

/**
 * Логирование действий пользователя
 * @param string $action Действие пользователя
 * @param string $details Дополнительные детали
 */
function log_action($action, $details = '') {
    // Здесь можно добавить логирование в файл или БД
    // Пример: file_put_contents('logs/activity.log', date('Y-m-d H:i:s') . " - $action - $details\n", FILE_APPEND);
}

/**
 * Функция register_patient перенесена в auth_functions.php
 * @see auth_functions.php
 */

/**
 * Очищает данные от потенциально опасных элементов
 * @param mixed $data Данные для очистки
 * @param object $database Объект подключения к БД (опционально)
 * @return mixed Очищенные данные
 */
function sanitize_data($data, $database = null) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitize_data($value, $database);
        }
        return $data;
    }
    
    // Убираем лишние пробелы
    if (is_string($data)) {
        $data = trim($data);
    }
    
    // Экранируем спецсимволы для безопасного вывода в HTML
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    // Экранируем спецсимволы для SQL-запросов, если передан объект базы данных
    if ($database !== null && is_string($data)) {
        $data = $database->real_escape_string($data);
    }
    
    return $data;
}

/**
 * Проверяет и очищает POST-данные
 * @param string $key Ключ в массиве $_POST
 * @param object $database Объект подключения к БД (опционально)
 * @param mixed $default Значение по умолчанию
 * @return mixed Очищенные данные или значение по умолчанию
 */
function get_post($key, $database = null, $default = '') {
    return isset($_POST[$key]) ? sanitize_data($_POST[$key], $database) : $default;
}

/**
 * Проверяет и очищает GET-данные
 * @param string $key Ключ в массиве $_GET
 * @param object $database Объект подключения к БД (опционально)
 * @param mixed $default Значение по умолчанию
 * @return mixed Очищенные данные или значение по умолчанию
 */
function get_query($key, $database = null, $default = '') {
    return isset($_GET[$key]) ? sanitize_data($_GET[$key], $database) : $default;
}
