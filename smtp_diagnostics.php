<?php
/**
 * SMTP Диагностический скрипт для системы Панацея
 * Проверяет различные аспекты SMTP подключения
 */

// Подключаем PHPMailer если есть
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
}

echo "=== SMTP ДИАГНОСТИКА ===\n";
echo "Время: " . date('Y-m-d H:i:s') . "\n\n";

// 1. Проверка PHP расширений
echo "1. ПРОВЕРКА PHP РАСШИРЕНИЙ:\n";
$required_extensions = ['openssl', 'sockets', 'curl'];
foreach ($required_extensions as $ext) {
    $status = extension_loaded($ext) ? '✓ ЕСТЬ' : '✗ НЕТ';
    echo "   - {$ext}: {$status}\n";
}

// 2. Проверка функций
echo "\n2. ПРОВЕРКА PHP ФУНКЦИЙ:\n";
$required_functions = ['fsockopen', 'stream_socket_client', 'curl_init'];
foreach ($required_functions as $func) {
    $status = function_exists($func) ? '✓ ЕСТЬ' : '✗ НЕТ';
    echo "   - {$func}: {$status}\n";
}

// 3. Тест сокетного подключения
echo "\n3. ТЕСТ СОКЕТНОГО ПОДКЛЮЧЕНИЯ:\n";

$smtp_servers = [
    'Gmail' => ['smtp.gmail.com', 587],
    'Yandex' => ['smtp.yandex.ru', 587],
    'Mail.ru' => ['smtp.mail.ru', 587],
    'Rambler' => ['smtp.rambler.ru', 587]
];

foreach ($smtp_servers as $name => [$host, $port]) {
    echo "   Тестируем {$name} ({$host}:{$port}):\n";
    
    // Тест с fsockopen
    $start_time = microtime(true);
    $fp = @fsockopen($host, $port, $errno, $errstr, 10);
    $connect_time = round((microtime(true) - $start_time) * 1000, 2);
    
    if ($fp) {
        echo "     ✓ fsockopen: Успешно ({$connect_time}ms)\n";
        fclose($fp);
    } else {
        echo "     ✗ fsockopen: Ошибка #{$errno}: {$errstr}\n";
    }
    
    // Тест с stream_socket_client
    $start_time = microtime(true);
    $context = stream_context_create();
    $socket = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $context);
    $connect_time = round((microtime(true) - $start_time) * 1000, 2);
    
    if ($socket) {
        echo "     ✓ stream_socket_client: Успешно ({$connect_time}ms)\n";
        fclose($socket);
    } else {
        echo "     ✗ stream_socket_client: Ошибка #{$errno}: {$errstr}\n";
    }
    
    echo "\n";
}

// 4. Тест с curl
echo "4. ТЕСТ HTTP ПОДКЛЮЧЕНИЙ (CURL):\n";
$test_urls = [
    'Google DNS' => 'http://8.8.8.8',
    'Yandex' => 'https://yandex.ru',
    'Mail.ru' => 'https://mail.ru'
];

foreach ($test_urls as $name => $url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $start_time = microtime(true);
    $result = curl_exec($ch);
    $connect_time = round((microtime(true) - $start_time) * 1000, 2);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($result !== false && $http_code > 0) {
        echo "   ✓ {$name}: HTTP {$http_code} ({$connect_time}ms)\n";
    } else {
        echo "   ✗ {$name}: Ошибка - {$error}\n";
    }
}

// 5. Проверка DNS
echo "\n5. ПРОВЕРКА DNS:\n";
$hosts_to_resolve = ['smtp.gmail.com', 'smtp.yandex.ru', 'smtp.mail.ru'];
foreach ($hosts_to_resolve as $host) {
    $ip = gethostbyname($host);
    if ($ip !== $host) {
        echo "   ✓ {$host} → {$ip}\n";
    } else {
        echo "   ✗ {$host} → DNS не разрешился\n";
    }
}

// 6. Проверка файрвола (попытка определить)
echo "\n6. СИСТЕМНАЯ ИНФОРМАЦИЯ:\n";
echo "   - PHP версия: " . PHP_VERSION . "\n";
echo "   - ОС: " . PHP_OS . "\n";
echo "   - Временная зона: " . date_default_timezone_get() . "\n";
echo "   - Максимальное время выполнения: " . ini_get('max_execution_time') . "s\n";
echo "   - Разрешены ли URL fopen: " . (ini_get('allow_url_fopen') ? 'Да' : 'Нет') . "\n";

// 7. Тест простого SMTP с отладкой
echo "\n7. ТЕСТ PHPMailer (если установлен):\n";
if (file_exists('vendor/autoload.php')) {    require_once 'vendor/autoload.php';
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    
    $mail = new PHPMailer(true);
    
    try {
        // Включаем отладку
        $mail->SMTPDebug = SMTP::DEBUG_CONNECTION;
        $mail->Debugoutput = function($str, $level) {
            echo "   PHPMailer: " . trim($str) . "\n";
        };
        
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->Port = 587;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Timeout = 10;
        
        echo "   Попытка подключения к Gmail SMTP...\n";
        
        // Только подключение, без аутентификации
        $result = $mail->smtpConnect();
        
        if ($result) {
            echo "   ✓ PHPMailer: Подключение к SMTP успешно!\n";
            $mail->smtpClose();
        } else {
            echo "   ✗ PHPMailer: Ошибка подключения\n";
        }
        
    } catch (Exception $e) {
        echo "   ✗ PHPMailer Exception: " . $e->getMessage() . "\n";
    }
} else {
    echo "   - PHPMailer не найден\n";
}

echo "\n=== ДИАГНОСТИКА ЗАВЕРШЕНА ===\n";
echo "Если все тесты прошли успешно, проблема может быть в:\n";
echo "- Неправильных учетных данных\n";
echo "- Настройках безопасности аккаунта\n";
echo "- Блокировке провайдером\n";
echo "- Антивирусе или файрволе\n\n";
?>
