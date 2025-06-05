<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/animations.css">  
    <link rel="stylesheet" href="css/main.css">  
    <link rel="stylesheet" href="css/login.css">
    
    <title>Восстановление пароля</title>
</head>
<body>
    <?php
    session_start();
    include("includes/init.php");
    include("includes/email_config.php");
    
    $error = '';
    $success = '';
      if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
        $email = trim($_POST['email']);
        
        // Проверяем, существует ли пользователь с таким email
        $stmt = $database->prepare("SELECT * FROM webuser WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error = '<label class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Пользователь с таким email не найден.</label>';
        } else {
            $user = $result->fetch_assoc();
            
            // Проверяем, существует ли таблица password_reset_tokens
            $table_check = $database->query("SHOW TABLES LIKE 'password_reset_tokens'");
            if ($table_check->num_rows === 0) {
                $error = '<label class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Система восстановления пароля не настроена. <a href="create_password_reset_table.php">Создать таблицу</a></label>';
            } else {                // Генерируем токен для сброса пароля
                $reset_token = generate_reset_token();
                $expiry_seconds = RESET_TOKEN_EXPIRY; // Переменная для bind_param
                
                // Сохраняем токен в базе данных (используем MySQL для вычисления времени истечения)
                $stmt = $database->prepare("INSERT INTO password_reset_tokens (email, token, expires_at, created_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND), NOW()) ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at), created_at = NOW()");
                $stmt->bind_param("ssi", $email, $reset_token, $expiry_seconds);
                
                if ($stmt->execute()) {
                    // Отправляем письмо
                    $reset_link = create_reset_link($reset_token);
                    $subject = "Восстановление пароля - МИС Панацея";
                    
                    $body = "
                    <html>
                    <head>
                        <title>Восстановление пароля</title>
                        <meta charset='UTF-8'>
                    </head>
                    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                            <h2 style='color: #2c5aa0;'>Восстановление пароля</h2>
                            <p>Здравствуйте!</p>
                            <p>Вы запросили восстановление пароля для вашей учетной записи в медицинской информационной системе «Панацея».</p>
                            <p>Для сброса пароля перейдите по ссылке ниже:</p>
                            <p style='margin: 20px 0;'>
                                <a href='$reset_link' style='background-color: #2c5aa0; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;'>Восстановить пароль</a>
                            </p>
                            <p><strong>Важно:</strong> Ссылка действительна в течение 1 часа.</p>
                            <p>Если вы не запрашивали восстановление пароля, просто проигнорируйте это письмо.</p>
                            <hr style='margin: 30px 0; border: none; border-top: 1px solid #eee;'>
                            <p style='font-size: 12px; color: #666;'>
                                С уважением,<br>
                                Команда МИС «Панацея»
                            </p>
                        </div>
                    </body>
                    </html>";
                    
                    $altBody = "Здравствуйте! Вы запросили восстановление пароля. Перейдите по ссылке: $reset_link (действительна 1 час)";
                    
                    if (send_email($email, $subject, $body, $altBody)) {
                        $success = '<label class="form-label" style="color:rgb(0, 150, 0);text-align:center;">Инструкции по восстановлению пароля отправлены на вашу электронную почту.</label>';
                    } else {
                        $error = '<label class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Ошибка отправки письма. <a href="test_email_simple.php">Проверить настройки SMTP</a></label>';
                    }
                } else {
                    $error = '<label class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Произошла ошибка. Попробуйте позже.</label>';
                }
            }
        }
    }
    ?>

    <center>
    <div class="container">
        <table border="0" style="margin: 0;padding: 0;width: 60%;">
            <tr>
                <td>
                    <p class="header-text">Забыли пароль?</p>
                </td>
            </tr>
        <div class="form-body">
            <tr>
                <td>
                    <p class="sub-text">Введите ваш email для восстановления пароля</p>
                </td>
            </tr>
            <tr>
                <form action="" method="POST">
                <td class="label-td">
                    <label for="email" class="form-label">Email: </label>
                </td>
            </tr>
            <tr>
                <td class="label-td">
                    <input type="email" name="email" class="input-text" placeholder="Электронная почта" required>
                </td>
            </tr>
            <tr>
                <td><br>
                <?php echo $error . $success; ?>
                </td>
            </tr>
            <tr>
                <td>
                    <input type="submit" value="Восстановить пароль" class="login-btn btn-primary btn">
                </td>
            </tr>
        </div>
            <tr>
                <td>
                    <br>
                    <label for="" class="sub-text" style="font-weight: 280;">Вспомнили пароль? </label>
                    <a href="login.php" class="hover-link1 non-style-link">Войти</a>
                    <br><br><br>
                </td>
            </tr>
        </form>
        </table>
    </div>
</center>
</body>
</html>
