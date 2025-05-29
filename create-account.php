<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/animations.css">  
    <link rel="stylesheet" href="css/main.css">  
    <link rel="stylesheet" href="css/signup.css">
        
    <title>Создать аккаунт</title>
    <style>
        .container {
            animation: transitionIn-X 0.5s;
        }
    </style>
    <script>
        function enforcePhoneFormat(input) {
            if (!input.value.startsWith("+7")) {
                input.value = "+7";
            }
        }
    </script>
</head>
<body>

<?php
session_start();
$_SESSION["user"] = "";
$_SESSION["usertype"] = "";
date_default_timezone_set('Asia/Kolkata');
$_SESSION["date"] = date('Y-m-d');

// Подключаем наши функции
include("includes/init.php");

// Инициализируем сессию, если не существует
if (!isset($_SESSION['personal'])) {
    $_SESSION['personal'] = [];
}

// Безопасно получаем данные
$fname = $_SESSION['personal']['fname'] ?? '';
$lname = $_SESSION['personal']['lname'] ?? '';
$name = trim("$fname $lname");
$address = $_SESSION['personal']['address'] ?? '';
$nic = $_SESSION['personal']['nic'] ?? '';
$dob = $_SESSION['personal']['dob'] ?? '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['newemail']);
    $tele = trim($_POST['tele']);
    $newpassword = $_POST['newpassword'];
    $cpassword = $_POST['cpassword'];
    
    // Валидация
    if (!preg_match('/^\+7[0-9]{10}$/', $tele)) {
        $error = '<label class="form-label" style="color:red;text-align:center;">Неверный формат номера телефона!</label>';    } elseif (!preg_match('/^[A-Za-z0-9!@#$%^&*()_+=-]{6,20}$/', $newpassword)) {
        $error = '<label class="form-label" style="color:red;text-align:center;">Пароль должен содержать только английские буквы, цифры и символы (6-20 символов)!</label>';
    } elseif ($newpassword !== $cpassword) {
        $error = '<label class="form-label" style="color:red;text-align:center;">Ошибка подтверждения пароля!</label>';
    } else {
        // Используем нашу новую функцию для регистрации пациента
        if (register_patient($database, $email, $name, $newpassword, $dob, $tele)) {
            $_SESSION["user"] = $email;
            $_SESSION["usertype"] = "p";
            $_SESSION["username"] = $fname;

            header('Location: patient/index.php');
            exit();
        } else {
            $error = '<label class="form-label" style="color:red;text-align:center;">Аккаунт с данной электронной почтой уже существует.</label>';
        }    }
} else {
    $error = '<label class="form-label"></label>';
}
?>

<center>
    <div class="container">
        <table border="0" style="width: 69%;">
            <tr>
                <td colspan="2">
                    <p class="header-text">Давайте начнем</p>
                    <p class="sub-text">Теперь создайте учетную запись.</p>
                </td>
            </tr>
            <tr>
                <form action="" method="POST">
                <td class="label-td" colspan="2">
                    <label for="newemail" class="form-label">Email: </label>
                </td>
            </tr>
            <tr>
                <td class="label-td" colspan="2">
                    <input type="email" name="newemail" class="input-text" placeholder="Электронная почта" required>
                </td>
            </tr>
            <tr>
                <td class="label-td" colspan="2">
                    <label for="tele" class="form-label">Номер телефона: </label>
                </td>
            </tr>
            <tr>
                <td class="label-td" colspan="2">
                    <input type="tel" name="tele" class="input-text" placeholder="+7XXXXXXXXXX" 
                           pattern="\+7[0-9]{10}" required maxlength="12"
                           oninput="this.value = this.value.replace(/[^0-9+]/g, '').slice(0, 12); 
                                    if (!this.value.startsWith('+7')) this.value = '+7';">
                </td>
            </tr>
            <tr>
                <td class="label-td" colspan="2">
                    <label for="newpassword" class="form-label">Создайте новый пароль: </label>
                </td>
            </tr>
            <tr>
                <td class="label-td" colspan="2">
                    <input type="password" name="newpassword" class="input-text" placeholder="Пароль (6-20 символов)" 
                           pattern="[A-Za-z0-9!@#$%^&*()_+=-]{6,20}" required maxlength="20">
                </td>
            </tr>
            <tr>
                <td class="label-td" colspan="2">
                    <label for="cpassword" class="form-label">Подтвердите пароль: </label>
                </td>
            </tr>
            <tr>
                <td class="label-td" colspan="2">
                    <input type="password" name="cpassword" class="input-text" placeholder="Подтвердите пароль" required>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <?php echo $error ?>
                </td>
            </tr>
            <tr>
                <td>
                    <input type="reset" value="Сбросить" class="login-btn btn-primary-soft btn">
                </td>
                <td>
                    <input type="submit" value="Зарегистрироваться" class="login-btn btn-primary btn">
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <br>
                    <label class="sub-text">Уже есть аккаунт&#63; </label>
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
