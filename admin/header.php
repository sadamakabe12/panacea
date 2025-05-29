<?php
// admin/header.php
session_start();
include("../includes/init.php");

// Проверяем доступ (только для администраторов)
check_access('a');

function isActiveMenu($file) {
    return strpos($_SERVER['SCRIPT_NAME'], $file) !== false ? ' menu-active menu-icon-' . basename($file, '.php') . '-active' : '';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">    <link rel="stylesheet" href="../css/animations.css">  
    <link rel="stylesheet" href="../css/main.css">  
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/tooltip.css">
    <title>Панель администратора</title>
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
                                <p class="profile-title">Администратор</p>
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
                <td class="menu-btn menu-icon-dashbord<?php echo isActiveMenu('index.php'); ?>">
                    <a href="index.php" class="non-style-link-menu"><div><p class="menu-text">Панель управления</p></div></a>
                </td>
            </tr>
            <tr class="menu-row">
                <td class="menu-btn menu-icon-doctor<?php echo isActiveMenu('doctors.php'); ?>">
                    <a href="doctors.php" class="non-style-link-menu"><div><p class="menu-text">Управление врачами</p></div></a>
                </td>
            </tr>
            <tr class="menu-row">
                <td class="menu-btn menu-icon-schedule<?php echo isActiveMenu('schedule.php'); ?>">
                    <a href="schedule.php" class="non-style-link-menu"><div><p class="menu-text">Управление расписанием</p></div></a>
                </td>
            </tr>            <tr class="menu-row">
                <td class="menu-btn menu-icon-appoinment<?php echo isActiveMenu('appointment.php'); ?>">
                    <a href="appointment.php" class="non-style-link-menu"><div><p class="menu-text">Журнал приёмов</p></div></a>
                </td>
            </tr>            <tr class="menu-row">
                <td class="menu-btn menu-icon-patient<?php echo isActiveMenu('patient.php'); ?>">
                    <a href="patient.php" class="non-style-link-menu"><div><p class="menu-text">Учёт пациентов</p></div></a>
                </td>
            </tr>
            <tr class="menu-row">
                <td class="menu-btn menu-icon-settings<?php echo isActiveMenu('medical_records.php'); ?>">
                    <a href="medical_records.php" class="non-style-link-menu"><div><p class="menu-text">Медицинские записи</p></div></a>
                </td>
            </tr>
            <tr class="menu-row">
                <td class="menu-btn menu-icon-session<?php echo isActiveMenu('audit_log.php'); ?>">
                    <a href="audit_log.php" class="non-style-link-menu"><div><p class="menu-text">Журнал аудита</p></div></a>
                </td>
            </tr>
        </table>
    </div>
    <div class="main-content dash-body">
