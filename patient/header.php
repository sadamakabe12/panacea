<?php
// patient/header.php
session_start();
include("../includes/init.php");

// Проверяем доступ (только для пациентов)
check_access('p');

$useremail = $_SESSION["user"];

// Получаем данные пациента с помощью функции
$userfetch = get_patient_by_email($database, $useremail);
$userid = $userfetch["pid"];
$username = $userfetch["pname"];

// Функция для определения активной страницы в меню
function isActiveMenu($file) {
    return basename($_SERVER['PHP_SELF']) === $file ? ' menu-active menu-icon-' . basename($file, '.php') . '-active' : '';
}
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
    <title>Личный кабинет</title>
    <style>
        .dashbord-tables{animation: transitionIn-Y-over 0.5s;}
        .filter-container{animation: transitionIn-Y-bottom  0.5s;}
        .sub-table,.anime{animation: transitionIn-Y-bottom 0.5s;}
    </style>
</head>
<body>
<div class="container">
    <div class="menu">
        <table class="menu-container" border="0">
            <tr>
                <td style="padding:10px" colspan="2">
                    <table border="0" class="profile-container">
                        <tr>
                            <td width="30%" style="padding-left:20px" >
                                <img src="../img/user.png" alt="" width="100%" style="border-radius:50%">
                            </td>
                            <td style="padding:0px;margin:0px;">
                                <p class="profile-title"><?php echo substr($username,0,45); ?></p>
                                <p class="profile-subtitle"><?php echo substr($useremail,0,22); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <a href="../logout.php" ><input type="button" value="Выйти" class="logout-btn btn-primary-soft btn"></a>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr class="menu-row">
                <td class="menu-btn menu-icon-home<?php echo isActiveMenu('index.php'); ?>">
                    <a href="index.php" class="non-style-link-menu"><div><p class="menu-text">Главная</p></div></a>
                </td>
            </tr>
            <tr class="menu-row">
                <td class="menu-btn menu-icon-doctor<?php echo isActiveMenu('doctors.php'); ?>">
                    <a href="doctors.php" class="non-style-link-menu"><div><p class="menu-text">Все врачи</p></div></a>
                </td>
            </tr>
            <tr class="menu-row">
                <td class="menu-btn menu-icon-session<?php echo isActiveMenu('schedule.php'); ?>">
                    <a href="schedule.php" class="non-style-link-menu"><div><p class="menu-text">Свободные записи</p></div></a>
                </td>
            </tr>
            <tr class="menu-row">
                <td class="menu-btn menu-icon-appoinment<?php echo isActiveMenu('appointment.php'); ?>">
                    <a href="appointment.php" class="non-style-link-menu"><div><p class="menu-text">Записи</p></div></a>
                </td>
            </tr>
            <tr class="menu-row">
                <td class="menu-btn menu-icon-session<?php echo isActiveMenu('medical_records.php'); ?>">
                    <a href="medical_records.php" class="non-style-link-menu"><div><p class="menu-text">Медкарта</p></div></a>
                </td>
            </tr>
            <tr class="menu-row">
                <td class="menu-btn menu-icon-settings<?php echo isActiveMenu('settings.php'); ?>">
                    <a href="settings.php" class="non-style-link-menu"><div><p class="menu-text">Настройки</p></div></a>
                </td>
            </tr>
        </table>
    </div>
    <div class="dash-body" style="margin-top: 15px">
