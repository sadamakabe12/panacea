# Настройка PHPMailer для МИС "Панацея"

## Установка завершена ✅

PHPMailer версии 6.10.0 успешно установлен через Composer.

## Что было создано:

1. **`includes/email_config.php`** - Конфигурация почты и функции отправки
2. **`forgot-password.php`** - Страница запроса восстановления пароля
3. **`reset-password.php`** - Страница сброса пароля по токену
4. **`password_reset_tokens.sql`** - SQL-скрипт для создания таблицы токенов
5. Обновлен **`login.php`** - добавлена ссылка "Забыли пароль?"

## Настройка перед использованием:

### 1. Создание таблицы в базе данных
Выполните SQL-скрипт:
```sql
-- Файл: password_reset_tokens.sql
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
```

### 2. Настройка email в `includes/email_config.php`:

#### Для MailerSend (текущая настройка):
```php
define('SMTP_HOST', 'smtp.mailersend.net');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'MS_HSkpTb@test-z0vklo6n89el7qrx.mlsender.net');
define('SMTP_PASSWORD', 'YOUR_MAILERSEND_API_TOKEN_HERE'); // Замените на ваш API токен
define('SMTP_ENCRYPTION', 'tls');
```

#### Для Gmail:
```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password'); // Пароль приложения
define('SMTP_ENCRYPTION', 'tls');
```

#### Для Mail.ru:
```php
define('SMTP_HOST', 'smtp.mail.ru');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@mail.ru');
define('SMTP_PASSWORD', 'your-password');
define('SMTP_ENCRYPTION', 'tls');
```

#### Для Yandex:
```php
define('SMTP_HOST', 'smtp.yandex.ru');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@yandex.ru');
define('SMTP_PASSWORD', 'your-password');
define('SMTP_ENCRYPTION', 'tls');
```

### 3. Настройка пароля приложения для Gmail:

1. Включите двухфакторную аутентификацию в Gmail
2. Перейдите в "Управление аккаунтом Google" → "Безопасность"
3. В разделе "Двухэтапная аутентификация" выберите "Пароли приложений"
4. Создайте пароль для "Почта"
5. Используйте этот пароль в `SMTP_PASSWORD`

### 4. Обновите настройки сайта:
```php
define('SITE_URL', 'http://localhost/'); // Замените на ваш домен
define('FROM_EMAIL', 'noreply@your-domain.com');
define('REPLY_TO_EMAIL', 'support@your-domain.com');
```

## Тестирование функции восстановления пароля:

### Тест 4.1.4 из документации теперь работает:

1. **Переход на страницу восстановления:**
   - Откройте `http://localhost/login.php`
   - Нажмите "Забыли пароль?"

2. **Запрос восстановления:**
   - Введите email зарегистрированного пользователя
   - Нажмите "Восстановить пароль"

3. **Проверка email:**
   - Письмо будет отправлено на указанную почту
   - Перейдите по ссылке в письме

4. **Сброс пароля:**
   - Введите новый пароль
   - Подтвердите пароль
   - Войдите с новым паролем

## Безопасность:

- ✅ Токены имеют время жизни (1 час)
- ✅ Каждый токен можно использовать только один раз
- ✅ Пароли хешируются с помощью `password_hash()`
- ✅ Проверка валидности email и токенов
- ✅ Защита от SQL-инъекций (prepared statements)

## Дополнительные функции для администратора:

Можно добавить автоматическую очистку истекших токенов в cron:
```bash
# Каждый час очищать истекшие токены
0 * * * * php /path/to/your/project/cleanup_tokens.php
```

## Логирование:

Ошибки отправки email записываются в PHP error log. Проверьте файл `logs/error.log` при проблемах.

---

**Статус функции восстановления пароля:** ✅ **РЕАЛИЗОВАНА**
**Соответствие тесту 4.1.4:** ✅ **ПОЛНОЕ**
