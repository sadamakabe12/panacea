<?php
/**
 * Простой тест отправки email
 * Откройте в браузере: http://localhost/test_email_simple.php
 */

echo "<h2>Тест отправки email - МИС Панацея</h2>";

// Включаем отображение ошибок для отладки
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/email_config.php';

// Замените на свой email для тестирования
$test_email = 'your-email@example.com'; // ← ИЗМЕНИТЕ НА СВОЙ EMAIL

$subject = 'Тест SMTP - МИС Панацея ' . date('Y-m-d H:i:s');
$body = '
<html>
<head><title>Тест SMTP</title></head>
<body>
    <h2 style="color: #2c5aa0;">Тест отправки email</h2>
    <p>Если вы получили это письмо, SMTP настроен правильно!</p>
    <p><strong>Время отправки:</strong> ' . date('Y-m-d H:i:s') . '</p>
    <p><strong>Сервер:</strong> ' . SMTP_HOST . ':' . SMTP_PORT . '</p>
    <p><strong>Отправитель:</strong> ' . FROM_EMAIL . '</p>
    <hr>
    <p style="font-size: 12px; color: #666;">
        С уважением,<br>
        МИС "Панацея"
    </p>
</body>
</html>';

$altBody = "Тест SMTP - МИС Панацея. Время: " . date('Y-m-d H:i:s');

echo "<p><strong>Настройки SMTP:</strong></p>";
echo "<ul>";
echo "<li>Хост: " . SMTP_HOST . "</li>";
echo "<li>Порт: " . SMTP_PORT . "</li>";
echo "<li>Шифрование: " . SMTP_ENCRYPTION . "</li>";
echo "<li>Отправитель: " . FROM_EMAIL . "</li>";
echo "<li>Получатель: " . $test_email . "</li>";
echo "</ul>";

echo "<p>Отправка email...</p>";

try {
    // Используем debug режим для детальной диагностики
    if (send_email($test_email, $subject, $body, $altBody, true)) {
        echo "<p style='color: green; font-size: 18px;'>✅ Email отправлен успешно!</p>";
        echo "<p>Проверьте почтовый ящик: <strong>$test_email</strong></p>";
        echo "<p><em>Если письмо не пришло, проверьте папку \"Спам\"</em></p>";
    } else {
        echo "<p style='color: red; font-size: 18px;'>❌ Ошибка отправки email</p>";
        echo "<p>Детальная диагностика показана выше</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Исключение при отправке: " . $e->getMessage() . "</p>";
}

echo "<br><hr>";
echo "<h3>Инструкции по настройке:</h3>";
echo "<ol>";
echo "<li>Замените \$test_email на свой реальный email адрес</li>";
echo "<li>Убедитесь, что в includes/email_config.php указан правильный пароль приложения Yandex</li>";
echo "<li>Если используете Yandex, создайте пароль приложения в настройках безопасности</li>";
echo "</ol>";

echo "<p><a href='Yandex_SMTP_Setup.md' target='_blank'>📖 Подробные инструкции по настройке Yandex</a></p>";
echo "<p><a href='create_password_reset_table.php'>🗄️ Создать таблицу для восстановления пароля</a></p>";
echo "<p><a href='login.php'>🔗 Вернуться к входу</a></p>";
?>
