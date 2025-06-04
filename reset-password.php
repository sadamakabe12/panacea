<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/animations.css">  
    <link rel="stylesheet" href="css/main.css">  
    <link rel="stylesheet" href="css/login.css">
    
    <title>Сброс пароля</title>
</head>
<body>
    <?php
    session_start();
    include("includes/init.php");
    include("includes/email_config.php");
    
    $error = '';
    $success = '';
    $token = '';
    $valid_token = false;
    
    // Проверяем токен
    if (isset($_GET['token'])) {
        $token = $_GET['token'];
        
        // Проверяем валидность токена
        $stmt = $database->prepare("SELECT email FROM password_reset_tokens WHERE token = ? AND expires_at > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $valid_token = true;
            $token_data = $result->fetch_assoc();
            $email = $token_data['email'];
        } else {
            $error = '<label class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Ссылка недействительна или истек срок действия.</label>';
        }
    } else {
        $error = '<label class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Некорректная ссылка для сброса пароля.</label>';
    }
    
    // Обработка формы сброса пароля
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token && isset($_POST['password']) && isset($_POST['confirm_password'])) {
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Валидация пароля
        if (!preg_match('/^[A-Za-z0-9!@#$%^&*()_+=-]{6,20}$/', $password)) {
            $error = '<label class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Пароль должен содержать только английские буквы, цифры и символы (6-20 символов)!</label>';
        } elseif ($password !== $confirm_password) {
            $error = '<label class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Пароли не совпадают!</label>';
        } else {
            // Определяем тип пользователя и обновляем пароль
            $stmt = $database->prepare("SELECT usertype FROM webuser WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $usertype = $user['usertype'];
            
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $updated = false;
            
            switch ($usertype) {
                case 'p': // Пациент
                    $stmt = $database->prepare("UPDATE patient SET ppassword = ? WHERE pemail = ?");
                    $stmt->bind_param("ss", $hashed_password, $email);
                    $updated = $stmt->execute();
                    break;
                    
                case 'd': // Доктор
                    $stmt = $database->prepare("UPDATE doctor SET docpassword = ? WHERE docemail = ?");
                    $stmt->bind_param("ss", $hashed_password, $email);
                    $updated = $stmt->execute();
                    break;
                    
                case 'a': // Администратор
                    $stmt = $database->prepare("UPDATE admin SET apassword = ? WHERE aemail = ?");
                    $stmt->bind_param("ss", $hashed_password, $email);
                    $updated = $stmt->execute();
                    break;
            }
            
            if ($updated) {
                // Удаляем использованный токен
                $stmt = $database->prepare("DELETE FROM password_reset_tokens WHERE token = ?");
                $stmt->bind_param("s", $token);
                $stmt->execute();
                
                $success = '<label class="form-label" style="color:rgb(0, 150, 0);text-align:center;">Пароль успешно изменен! Теперь вы можете войти в систему.</label>';
                $valid_token = false; // Скрываем форму
            } else {
                $error = '<label class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Ошибка при обновлении пароля. Попробуйте позже.</label>';
            }
        }
    }
    ?>

    <center>
    <div class="container">
        <table border="0" style="margin: 0;padding: 0;width: 60%;">
            <tr>
                <td>
                    <p class="header-text">Сброс пароля</p>
                </td>
            </tr>
        <div class="form-body">
            <?php if ($valid_token): ?>
            <tr>
                <td>
                    <p class="sub-text">Введите новый пароль</p>
                </td>
            </tr>
            <tr>
                <form action="" method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <td class="label-td">
                    <label for="password" class="form-label">Новый пароль: </label>
                </td>
            </tr>
            <tr>
                <td class="label-td">
                    <input type="password" name="password" class="input-text" placeholder="Новый пароль" required>
                </td>
            </tr>
            <tr>
                <td class="label-td">
                    <label for="confirm_password" class="form-label">Подтвердите пароль: </label>
                </td>
            </tr>
            <tr>
                <td class="label-td">
                    <input type="password" name="confirm_password" class="input-text" placeholder="Подтвердите пароль" required>
                </td>
            </tr>
            <tr>
                <td><br>
                <?php echo $error; ?>
                </td>
            </tr>
            <tr>
                <td>
                    <input type="submit" value="Сменить пароль" class="login-btn btn-primary btn">
                </td>
            </tr>
            </form>
            <?php else: ?>
            <tr>
                <td><br>
                <?php echo $error . $success; ?>
                </td>
            </tr>
            <?php endif; ?>
            <tr>
                <td>
                    <br>
                    <a href="login.php" class="hover-link1 non-style-link">← Вернуться к входу</a>
                    <br><br><br>
                </td>
            </tr>
        </div>
        </table>
    </div>
</center>
</body>
</html>
