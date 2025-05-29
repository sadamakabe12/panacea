<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/animations.css">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/signup.css">
    <title>Sign Up</title>
    <script>
        function capitalizeFirstLetter(input) {
            input.value = input.value.charAt(0).toUpperCase() + input.value.slice(1);
        }
    </script>
</head>
<body>
<?php

session_start();

$_SESSION["user"] = "";
$_SESSION["usertype"] = "";

date_default_timezone_set('Asia/Kolkata');
$date = date('Y-m-d');

$_SESSION["date"] = $date;

$error = "";

if ($_POST) {
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];

    // Регулярное выражение для валидации
    if (preg_match('/^[А-ЯЁA-Z][а-яёa-z]+$/u', $fname) && preg_match('/^[А-ЯЁA-Z][а-яёa-z]+$/u', $lname)) {
        $_SESSION["personal"] = array(
            'fname' => $fname,
            'lname' => $lname,
            'dob' => $_POST['dob']
        );

        print_r($_SESSION["personal"]);
        header("location: create-account.php");
    } else {
        $error = '<label for="promter" class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Имя и фамилия должны начинаться с заглавной буквы и содержать только буквы.</label>';
    }
}
?>

<center>
<div class="container">
    <form action="" method="POST">
    <table border="0">
        <tr>
            <td colspan="2">
                <p class="header-text">Давайте начнем</p>
                <p class="sub-text">Добавьте свои личные данные, чтобы продолжить</p>
                <?php echo $error; ?>
            </td>
        </tr>
        <tr>
            <td class="label-td" colspan="2">
                <label for="name" class="form-label">Личные данные: </label>
            </td>
        </tr>
        <tr>
            <td class="label-td">
                <input type="text" name="fname" class="input-text" placeholder="Имя" required oninput="capitalizeFirstLetter(this)">
            </td>
            <td class="label-td">
                <input type="text" name="lname" class="input-text" placeholder="Фамилия" required oninput="capitalizeFirstLetter(this)">
            </td>
        </tr>
        <tr>
            <td class="label-td" colspan="2">
                <label for="dob" class="form-label">Дата рождения: </label>
            </td>
        </tr>
        <tr>
            <td class="label-td" colspan="2">
                <input type="date" name="dob" class="input-text" required>
            </td>
        </tr>
        <tr>
            <td>
                <input type="reset" value="Сбросить" class="login-btn btn-primary-soft btn">
            </td>
            <td>
                <input type="submit" value="Дальше" class="login-btn btn-primary btn">
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <br>
                <label for="" class="sub-text" style="font-weight: 280;">Уже есть аккаунт&#63; </label>
                <a href="login.php" class="hover-link1 non-style-link">Войти</a>
                <br><br><br>
            </td>
        </tr>
    </table>
    </form>
</div>
</center>
</body>
</html>
