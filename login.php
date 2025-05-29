<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/animations.css">  
    <link rel="stylesheet" href="css/main.css">  
    <link rel="stylesheet" href="css/login.css">
    
    <title>Вход</title>
</head>
<body>
    <?php
    session_start();    $_SESSION["user"] = "";
    $_SESSION["usertype"] = "";
    
    date_default_timezone_set('Asia/Kolkata');
    $date = date('Y-m-d');
    $_SESSION["date"] = $date;

    include("includes/init.php");    if ($_POST) {
        $email = $_POST['useremail'];
        $password = $_POST['userpassword'];
        
        $error = '<label for="promter" class="form-label"></label>';

        // Используем нашу новую функцию аутентификации
        $auth_result = authenticate_user($database, $email, $password);
        
        if ($auth_result === false) {
            $error = '<label for="promter" class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Мы не нашли аккаунт с таким email.</label>';
        } elseif ($auth_result['error'] === 'account_inactive') {
            $error = '<label for="promter" class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Ваш аккаунт деактивирован. Обратитесь в поддержку.</label>';
        } elseif ($auth_result['error'] === 'invalid_credentials') {
            $error = '<label for="promter" class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Неверный email или пароль.</label>';
        } else {
            // Успешная аутентификация
            $_SESSION['user'] = $auth_result['email'];
            $_SESSION['usertype'] = $auth_result['usertype'];
            $_SESSION['username'] = $auth_result['name'];
            
            // Перенаправление в зависимости от типа пользователя
            $redirect_urls = [
                'p' => 'patient/index.php',
                'd' => 'doctor/index.php',
                'a' => 'admin/index.php'
            ];
            
            header('location: ' . $redirect_urls[$auth_result['usertype']]);
            exit();
        }
    } else {
        $error = '<label for="promter" class="form-label">&nbsp;</label>';
    }
    ?>

    <center>
    <div class="container">
        <table border="0" style="margin: 0;padding: 0;width: 60%;">
            <tr>
                <td>
                    <p class="header-text">Добро пожаловать!</p>
                </td>
            </tr>
        <div class="form-body">
            <tr>
                <td>
                    <p class="sub-text">Введите данные, чтобы продолжить</p>
                </td>
            </tr>
            <tr>
                <form action="" method="POST">
                <td class="label-td">
                    <label for="useremail" class="form-label">Email: </label>
                </td>
            </tr>
            <tr>
                <td class="label-td">
                    <input type="email" name="useremail" class="input-text" placeholder="Электронная почта" required>
                </td>
            </tr>
            <tr>
                <td class="label-td">
                    <label for="userpassword" class="form-label">Пароль: </label>
                </td>
            </tr>
            <tr>
                <td class="label-td">
                    <input type="password" name="userpassword" class="input-text" placeholder="Пароль" required>
                </td>
            </tr>
            <tr>
                <td><br>
                <?php echo $error ?>
                </td>
            </tr>
            <tr>
                <td>
                    <input type="submit" value="Войти" class="login-btn btn-primary btn">
                </td>
            </tr>
        </div>
            <tr>
                <td>
                    <br>
                    <label for="" class="sub-text" style="font-weight: 280;">Нет аккаунта&#63; </label>
                    <a href="signup.php" class="hover-link1 non-style-link">Зарегистрироваться</a>
                    <br><br><br>
                </td>
            </tr>
        </form>
        </table>
    </div>
</center>
</body>
</html>