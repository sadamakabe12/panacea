-- Создание таблицы для хранения токенов сброса пароля
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_token_expiry` (`token`, `expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Очистка истекших токенов (опционально, можно добавить в cron)
-- DELETE FROM password_reset_tokens WHERE expires_at < NOW();
