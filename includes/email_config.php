<?php
/**
 * Конфигурация электронной почты для МИС "Панацея"
 */

// Настройки SMTP для отправки писем (Gmail)
// Согласно официальной документации Google
define('SMTP_HOST', 'smtp.gmail.com'); // Сервер исходящей почты Gmail
define('SMTP_PORT', 587); // Порт для TLS соединения
define('SMTP_USERNAME', 'machaxaha@gmail.com'); // ЗАМЕНИТЕ на ваш Gmail адрес
define('SMTP_PASSWORD', 'awbd wesj arzc vtpc'); // ЗАМЕНИТЕ на пароль приложения Google (16 символов)
define('SMTP_ENCRYPTION', 'tls'); // TLS для порта 587

// Настройки отправителя
define('FROM_EMAIL', 'machaxaha@gmail.com'); // Email отправителя (должен совпадать с SMTP_USERNAME)
define('FROM_NAME', 'Клиника Панацея'); // Имя отправителя
define('REPLY_TO_EMAIL', 'machaxaha@gmail.com'); // Email для ответов

// Настройки для восстановления пароля
define('RESET_TOKEN_EXPIRY', 3600); // Время жизни токена в секундах (1 час)
define('SITE_URL', 'http://localhost/'); // Базовый URL сайта

// Подключаем автозагрузчик Composer
$composer_autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composer_autoload)) {
    require_once $composer_autoload;
} else {
    // Альтернативный путь для случаев, когда файл вызывается из другой директории
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Отправка email с использованием PHPMailer
 * @param string $to Email получателя
 * @param string $subject Тема письма
 * @param string $body Содержимое письма (HTML)
 * @param string $altBody Альтернативное текстовое содержимое
 * @param bool $debug Включить детальную отладку
 * @return bool Результат отправки
 */
function send_email($to, $subject, $body, $altBody = '', $debug = false) {
    
    $mail = new PHPMailer(true);
    
    try {
        // Включаем детальную отладку при необходимости
        if ($debug) {
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            $mail->Debugoutput = 'html';
        }
        
        // Настройки сервера для Yandex
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION; // SSL для порта 465
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        
        // Дополнительные настройки для Yandex
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // Timeout настройки
        $mail->Timeout = 30;
        $mail->SMTPKeepAlive = true;
        
        // Настройки отправителя и получателя
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addAddress($to);
        $mail->addReplyTo(REPLY_TO_EMAIL, FROM_NAME);
        
        // Содержимое письма
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        if ($altBody) {
            $mail->AltBody = $altBody;
        }
        
        // Отправка
        $result = $mail->send();
        
        if ($debug) {
            echo "<p style='color: green;'>✅ Письмо отправлено успешно!</p>";
        }
        
        return $result;
        
    } catch (Exception $e) {
        $error_message = "Ошибка отправки письма: {$mail->ErrorInfo}";
        error_log($error_message);
        
        if ($debug) {
            echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 10px; margin: 10px 0;'>";
            echo "<h4 style='color: #d32f2f; margin-top: 0;'>❌ Ошибка отправки email:</h4>";
            echo "<p><strong>Сообщение:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><strong>Детали:</strong> " . htmlspecialchars($mail->ErrorInfo) . "</p>";
            
            // Проверяем распространенные проблемы
            if (strpos($e->getMessage(), 'Authentication failed') !== false) {
                echo "<h5 style='color: #d32f2f;'>Возможные причины:</h5>";
                echo "<ul>";
                echo "<li>Неверный пароль приложения Yandex</li>";
                echo "<li>Не включена двухфакторная аутентификация в Yandex</li>";
                echo "<li>Пароль приложения не создан или устарел</li>";
                echo "</ul>";
                echo "<p><a href='Yandex_SMTP_Setup.md' target='_blank'>📖 Инструкции по настройке Yandex</a></p>";
            }
            
            if (strpos($e->getMessage(), 'Connection failed') !== false) {
                echo "<h5 style='color: #d32f2f;'>Проблемы с подключением:</h5>";
                echo "<ul>";
                echo "<li>Проверьте интернет-соединение</li>";
                echo "<li>Возможно заблокирован порт 465</li>";
                echo "<li>Проблемы с SSL сертификатом</li>";
                echo "</ul>";
            }
            
            echo "</div>";
        }
        
        return false;
    }
}

/**
 * Генерация безопасного токена для сброса пароля
 * @return string Токен
 */
function generate_reset_token() {
    return bin2hex(random_bytes(32));
}

/**
 * Создание ссылки для сброса пароля
 * @param string $token Токен сброса
 * @return string URL для сброса пароля
 */
function create_reset_link($token) {
    return SITE_URL . "reset-password.php?token=" . urlencode($token);
}
?>
