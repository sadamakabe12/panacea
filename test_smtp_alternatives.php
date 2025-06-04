<?php
/**
 * Тест альтернативных SMTP серверов для России
 */

echo "=== ТЕСТ РОССИЙСКИХ SMTP СЕРВЕРОВ ===\n";

// Российские SMTP серверы, которые могут быть менее заблокированы
$smtp_servers = [
    'Yandex TLS' => ['smtp.yandex.ru', 465],
    'Yandex STARTTLS' => ['smtp.yandex.ru', 587],
    'Mail.ru TLS' => ['smtp.mail.ru', 465],
    'Mail.ru STARTTLS' => ['smtp.mail.ru', 587],
    'Rambler' => ['smtp.rambler.ru', 587],
    'Gmail через TLS' => ['smtp.gmail.com', 465],
    'Outlook' => ['smtp-mail.outlook.com', 587],
    'Почта России' => ['smtp.pochta.ru', 587],
];

foreach ($smtp_servers as $name => $config) {
    list($host, $port) = $config;
    
    echo "\nТестируем {$name} ({$host}:{$port}):\n";
    
    // Краткий тест подключения
    $start = microtime(true);
    $context = stream_context_create([
        'socket' => [
            'bindto' => '0:0',
        ],
    ]);
    
    $fp = @stream_socket_client(
        "tcp://{$host}:{$port}", 
        $errno, 
        $errstr, 
        5, 
        STREAM_CLIENT_CONNECT,
        $context
    );
    
    $time = round((microtime(true) - $start) * 1000, 2);
    
    if ($fp) {
        echo "  ✓ Подключение успешно ({$time}ms)\n";
        
        // Читаем приветствие SMTP сервера
        $greeting = fgets($fp, 1024);
        echo "  📧 Приветствие: " . trim($greeting) . "\n";
        
        fclose($fp);
    } else {
        echo "  ✗ Ошибка #{$errno}: {$errstr} ({$time}ms)\n";
    }
}

echo "\n=== ТЕСТ ЗАВЕРШЕН ===\n";

// Проверка настроек PHP
echo "\n=== НАСТРОЙКИ PHP ===\n";
echo "allow_url_fopen: " . (ini_get('allow_url_fopen') ? 'ON' : 'OFF') . "\n";
echo "user_agent: " . ini_get('user_agent') . "\n";
echo "default_socket_timeout: " . ini_get('default_socket_timeout') . "s\n";
echo "auto_detect_line_endings: " . (ini_get('auto_detect_line_endings') ? 'ON' : 'OFF') . "\n";

// Проверка cURL
echo "\n=== ТЕСТ CURL ===\n";
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://httpbin.org/ip');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    if ($result && $httpCode == 200) {
        $data = json_decode($result, true);
        echo "✓ Внешний IP: " . ($data['origin'] ?? 'неизвестен') . "\n";
    } else {
        echo "✗ Ошибка cURL: {$error} (HTTP: {$httpCode})\n";
    }
    curl_close($ch);
} else {
    echo "✗ cURL не доступен\n";
}

?>
