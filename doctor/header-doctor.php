<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("../includes/init.php");
include("../includes/doctor_template_functions.php");
include("../includes/doctor_advanced_functions.php");

// Проверяем доступ (только для докторов)
check_access('d');

$useremail = $_SESSION["user"];

// Получаем данные врача с помощью функции
$userfetch = get_doctor_by_email($database, $useremail);
$userid = $userfetch["docid"];
$username = $userfetch["docname"];

// Функция для определения активной страницы в меню
function isDoctorActiveMenu($file) {
    return strpos($_SERVER['SCRIPT_NAME'], $file) !== false ? ' menu-active menu-icon-' . basename($file, '.php') . '-active' : '';
}

// Устанавливаем заголовок страницы по умолчанию
$pageTitle = isset($pageTitle) ? $pageTitle : 'Панель врача';

// Массив пунктов меню
$menuItems = [
    'index.php' => ['icon' => 'dashbord', 'title' => 'Панель управления'],
    'appointment.php' => ['icon' => 'appoinment', 'title' => 'Приёмы'],
    'schedule.php' => ['icon' => 'session', 'title' => 'Мое расписание'],
    'medical_records.php' => ['icon' => 'session', 'title' => 'Медицинские записи'],
    'patient.php' => ['icon' => 'patient', 'title' => 'База пациентов'],
    'settings.php' => ['icon' => 'settings', 'title' => 'Профиль и настройки']
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/animations.css">  
    <link rel="stylesheet" href="../css/main.css">  
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/appointments.css">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
</head>
<body>
<div class="container">
    <div class="menu">
        <table class="menu-container" border="0">
            <tr>
                <td style="padding:10px" colspan="2">
                    <table border="0" class="profile-container">
                        <tr>
                            <td width="30%" style="padding-left:20px">
                                <img src="../img/user.png" alt="" width="100%" style="border-radius:50%">
                            </td>
                            <td style="padding:0px;margin:0px;">
                                <p class="profile-title"><?php echo htmlspecialchars(substr($username,0,60)); ?></p>
                                <p class="profile-subtitle"><?php echo htmlspecialchars(substr($useremail,0,22)); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <a href="../logout.php"><input type="button" value="Выйти" class="logout-btn btn-primary-soft btn"></a>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            
            <?php foreach ($menuItems as $file => $item): ?>
            <tr class="menu-row">
                <td class="menu-btn menu-icon-<?php echo $item['icon'] . isDoctorActiveMenu($file); ?>">
                    <a href="<?php echo $file; ?>" class="non-style-link-menu">
                        <div><p class="menu-text"><?php echo $item['title']; ?></p></div>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <div class="main-content dash-body">
<!-- основной контент страницы -->
