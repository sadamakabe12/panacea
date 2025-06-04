<?php
/**
 * Быстрая SMTP диагностика
 */

echo "=== БЫСТРАЯ SMTP ДИАГНОСТИКА ===\n";

// 1. Проверка базовых расширений
echo "1. PHP расширения:\n";
echo "   - openssl: " . (extension_loaded('openssl') ? 'ОК' : 'НЕТ') . "\n";
echo "   - sockets: " . (extension_loaded('sockets') ? 'ОК' : 'НЕТ') . "\n";

// 2. Тест DNS
echo "\n2. DNS проверка:\n";
$test_host = 'smtp.gmail.com';
$ip = gethostbyname($test_host);
echo "   - {$test_host} → " . ($ip !== $test_host ? $ip : 'НЕ РАЗРЕШИЛСЯ') . "\n";

// 3. Простой тест сокета
echo "\n3. Тест подключения к Gmail SMTP:\n";
$host = 'smtp.gmail.com';
$port = 587;
$timeout = 5;

$start = microtime(true);
$fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
$time = round((microtime(true) - $start) * 1000, 2);

if ($fp) {
    echo "   ✓ Подключение успешно ({$time}ms)\n";
    fclose($fp);
} else {
    echo "   ✗ Ошибка #{$errno}: {$errstr} ({$time}ms)\n";
}

// 4. Тест других SMTP серверов
echo "\n4. Тест других SMTP серверов:\n";
$servers = [
    'Yandex' => 'smtp.yandex.ru',
    'Mail.ru' => 'smtp.mail.ru'
];

foreach ($servers as $name => $host) {
    $start = microtime(true);
    $fp = @fsockopen($host, 587, $errno, $errstr, 3);
    $time = round((microtime(true) - $start) * 1000, 2);
    
    if ($fp) {
        echo "   ✓ {$name}: OK ({$time}ms)\n";
        fclose($fp);
    } else {
        echo "   ✗ {$name}: Ошибка #{$errno} ({$time}ms)\n";
    }
}

// 5. Проверка PHPMailer
echo "\n5. PHPMailer:\n";
if (file_exists('vendor/autoload.php')) {
    echo "   ✓ Автозагрузчик найден\n";
    require_once 'vendor/autoload.php';
    
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        echo "   ✓ PHPMailer класс загружен\n";
    } else {
        echo "   ✗ PHPMailer класс не найден\n";
    }
} else {
    echo "   ✗ vendor/autoload.php не найден\n";
}

echo "\n=== ДИАГНОСТИКА ЗАВЕРШЕНА ===\n";
?>
