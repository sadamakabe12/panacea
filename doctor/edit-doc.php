<?php
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["user"] == "" || $_SESSION['usertype'] != 'd') {
    header("location: ../login.php");
    exit();
}

include("../includes/init.php");
include("../includes/doctor_advanced_functions.php");

if ($_POST) {
    // Получаем и валидируем данные
    $name = trim($_POST['name']);
    $oldemail = trim($_POST["oldemail"]);
    $email = trim($_POST['email']);
    $tele = trim($_POST['Tele']);
    $password = $_POST['password'];
    $cpassword = $_POST['cpassword'];
    $id = intval($_POST['id00']);
    
    // Валидация данных
    $validation = validateDoctorData($database, $email, $tele, $password, $cpassword, $id, $oldemail);
    
    if ($validation['success']) {
        // Обновляем данные врача
        $updateResult = updateDoctorProfile($database, $id, $email, $tele, $password, $oldemail);
        
        if ($updateResult) {
            $error = '4'; // Успех
        } else {
            $error = '3'; // Ошибка обновления
        }
    } else {
        $error = $validation['error_code'];
    }
} else {
    $error = '3';
}

header("location: settings.php?action=edit&error=" . $error . "&id=" . $id);
exit();
?>

<?php include "../admin/footer.php"; ?>