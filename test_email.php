<?php
/**
 * Тестовый скрипт для проверки отправки email
 */

// Подключаем необходимые файлы
include("includes/init.php");
include("includes/email_config.php");

// Тестовая отправка письма
$test_email = "test@example.com"; // Замените на ваш реальный email для тестирования
$subject = "Тест отправки письма - МИС Панацея";
$body = "
<html>
<head>
    <title>Тестовое письмо</title>
    <meta charset='UTF-8'>
</head>
<body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
    <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
        <h2 style='color: #2c5aa0;'>Тест системы отправки писем</h2>
        <p>Это тестовое письмо для проверки работы PHPMailer в МИС «Панацея».</p>
        <p>Если вы получили это письмо, значит система отправки работает корректно!</p>
        <p><strong>Время отправки:</strong> " . date('Y-m-d H:i:s') . "</p>
        <hr style='margin: 30px 0; border: none; border-top: 1px solid #eee;'>
        <p style='font-size: 12px; color: #666;'>
            С уважением,<br>
            Команда МИС «Панацея»
        </p>
    </div>
</body>
</html>";

$altBody = "Тестовое письмо МИС Панацея. Время отправки: " . date('Y-m-d H:i:s');

echo "<h2>Тест отправки email</h2>";
echo "<p>Проверяем настройки SMTP...</p>";

// Выводим текущие настройки (без пароля)
echo "<h3>Текущие настройки:</h3>";
echo "<ul>";
echo "<li>SMTP Host: " . SMTP_HOST . "</li>";
echo "<li>SMTP Port: " . SMTP_PORT . "</li>";
echo "<li>SMTP Username: " . SMTP_USERNAME . "</li>";
echo "<li>SMTP Encryption: " . SMTP_ENCRYPTION . "</li>";
echo "<li>From Email: " . FROM_EMAIL . "</li>";
echo "<li>From Name: " . FROM_NAME . "</li>";
echo "</ul>";

// Попытка отправки
echo "<h3>Результат отправки:</h3>";
if (send_email($test_email, $subject, $body, $altBody)) {
    echo "<p style='color: green;'>✅ Письмо успешно отправлено на $test_email</p>";
} else {
    echo "<p style='color: red;'>❌ Ошибка при отправке письма</p>";
    echo "<p>Проверьте:</p>";
    echo "<ul>";
    echo "<li>Правильность SMTP настроек</li>";
    echo "<li>Корректность логина и пароля</li>";
    echo "<li>Наличие интернет-соединения</li>";
    echo "<li>Логи ошибок в error.log</li>";
    echo "</ul>";
}

echo "<br><a href='login.php'>← Вернуться к входу</a>";
?>
