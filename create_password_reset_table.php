<?php
/**
 * Скрипт для создания таблицы password_reset_tokens
 * Запустите этот файл через браузер: http://localhost/create_password_reset_table.php
 */

require_once 'includes/init.php';

echo "<h2>Создание таблицы password_reset_tokens</h2>";

$sql = "CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_token_expiry` (`token`, `expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

try {
    if ($database->query($sql)) {
        echo "<p style='color: green;'>✅ Таблица password_reset_tokens успешно создана!</p>";
        
        // Проверяем, что таблица действительно создана
        $check = $database->query("SHOW TABLES LIKE 'password_reset_tokens'");
        if ($check->num_rows > 0) {
            echo "<p style='color: blue;'>📋 Таблица существует в базе данных</p>";
            
            // Показываем структуру таблицы
            $structure = $database->query("DESCRIBE password_reset_tokens");
            echo "<h3>Структура таблицы:</h3>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Поле</th><th>Тип</th><th>Null</th><th>Ключ</th><th>По умолчанию</th></tr>";
            while ($row = $structure->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['Field'] . "</td>";
                echo "<td>" . $row['Type'] . "</td>";
                echo "<td>" . $row['Null'] . "</td>";
                echo "<td>" . $row['Key'] . "</td>";
                echo "<td>" . $row['Default'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p style='color: red;'>❌ Ошибка создания таблицы: " . $database->error . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Исключение: " . $e->getMessage() . "</p>";
}

echo "<br><hr>";
echo "<p><a href='forgot-password.php'>🔗 Тестировать восстановление пароля</a></p>";
echo "<p><a href='login.php'>🔗 Вернуться к входу</a></p>";
?>
