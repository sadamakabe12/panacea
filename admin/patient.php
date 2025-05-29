<?php
include "header.php";
include("../includes/init.php");
date_default_timezone_set('Asia/Yekaterinburg');
$today = date('d-m-Y');

// Обработка POST-запроса для добавления пациента
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_patient'])) {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $tele = $_POST['tel'] ?? '';
    $password = $_POST['password'] ?? '';
    $cpassword = $_POST['cpassword'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $address = $_POST['address'] ?? '';
    
    // Используем централизованную функцию для добавления пациента
    $error = add_patient($database, $name, $email, $tele, $password, $cpassword, $dob, $address);
    
    header("Location: patient.php?action=add&error=$error");
    exit();
}

// --- Поиск и фильтрация ---
$list11 = $database->query("SELECT pname, pemail FROM patient;");
$patients_count = $database->query("SELECT * FROM patient;")->num_rows;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['add_patient'])) {
    $keyword = $_POST["search"] ?? '';
    $sqlmain = "SELECT * FROM patient WHERE pemail='$keyword' OR pname='$keyword' OR pname LIKE '$keyword%' OR pname LIKE '%$keyword' OR pname LIKE '%$keyword%' ";
} else {
    $sqlmain = "SELECT * FROM patient ORDER BY pid DESC";
}
$result = $database->query($sqlmain);
// --- Popup генераторы ---

// Эти функции теперь в popup_functions.php

// --- Вывод popups ---
$popup = '';
if (!empty($_GET['action'])) {
    $action = $_GET['action'];
    $id = $_GET['id'] ?? null;
    $error_1 = $_GET['error'] ?? '0';
    if ($action === 'view' && $id) $popup = popup_view_patient($database, $id);
    elseif ($action === 'add' && $error_1 != '4') $popup = popup_add_patient($error_1);
    elseif ($action === 'add' && $error_1 == '4') $popup = popup_add_success();
}
?>
<div class="dash-body">
    <table border="0" width="100%" style="border-spacing: 0;margin:0;padding:0;margin-top:25px;">
        <tr>
            <td width="13%">
                <a href="javascript:history.back()"><button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px"><font class="tn-in-text">Назад</font></button></a>
            </td>
            <td>
                <form action="" method="post" class="header-search">
                    <input type="search" name="search" class="input-text header-searchbar" placeholder="Поиск по имени пациента или по электронной почте" list="patient">&nbsp;&nbsp;
                    <?php
                    echo '<datalist id="patient">';
                    $list11 = $database->query("SELECT pname, pemail FROM patient;");
                    while($row00 = $list11->fetch_assoc()) {
                        echo "<option value='".$row00["pname"]."'>";
                        echo "<option value='".$row00["pemail"]."'>";
                    }
                    echo '</datalist>';
                    ?>
                    <input type="Submit" value="Поиск" class="login-btn btn-primary btn" style="padding-left: 25px;padding-right: 25px;padding-top: 10px;padding-bottom: 10px;">
                </form>
            </td>
            <td width="15%">
                <p style="font-size: 14px;color: rgb(119, 119, 119);padding: 0;margin: 0;text-align: right;">Сегодняшняя дата</p>
                <p class="heading-sub12" style="padding: 0;margin: 0;"><?php echo $today; ?></p>
            </td>
            <td width="10%">
                <button class="btn-label" style="display: flex;justify-content: center;align-items: center;"><img src="../img/calendar.svg" width="100%"></button>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="padding-top:30px;">
                <p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">Добавить нового пациента</p>
            </td>
            <td colspan="2">
                <a href="?action=add&id=none&error=0" class="non-style-link"><button class="login-btn btn-primary btn button-icon" style="display: flex;justify-content: center;align-items: center;margin-left:75px;background-image: url('../img/icons/add.svg');">Добавить нового</button></a>
            </td>
        </tr>
        <tr>
            <td colspan="4" style="padding-top:10px;">
                <p class="heading-main12" style="margin-left: 45px;font-size:18px;color:rgb(49, 49, 49)">Все пациенты (<?php echo $patients_count; ?>)</p>
            </td>
        </tr>
        <tr>
            <td colspan="4">
                <center>
                    <div class="abc scroll">
                        <table width="93%" class="sub-table scrolldown" style="border-spacing:0;">
                            <thead>
                                <tr>
                                    <th class="table-headin">Имя</th>
                                    <th class="table-headin">Номер телефона</th>
                                    <th class="table-headin">Email</th>
                                    <th class="table-headin">Дата рождения</th>
                                    <th class="table-headin">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if($result->num_rows==0){
                                    echo '<tr><td colspan="5"><br><br><br><br><center><img src="../img/notfound.svg" width="25%"><br><p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">Мы не смогли найти ничего, связанного с вашими ключевыми словами!</p><a class="non-style-link" href="patient.php"><button class="login-btn btn-primary-soft btn" style="display: flex;justify-content: center;align-items: center;margin-left:20px;">&nbsp; Показать всех пациентов &nbsp;</button></a></center><br><br><br><br></td></tr>';
                                } else {
                                    while($row = $result->fetch_assoc()) {
                                        echo '<tr><td>&nbsp;'.htmlspecialchars($row['pname']).'</td><td>'.htmlspecialchars($row['ptel']).'</td><td>'.htmlspecialchars($row['pemail']).'</td><td>'.htmlspecialchars($row['pdob']).'</td><td><div style="display:flex;justify-content: center;"><a href="?action=view&id='.$row['pid'].'" class="non-style-link"><button class="btn-primary-soft btn button-icon btn-view" style="padding-left: 40px;padding-top: 12px;padding-bottom: 12px;margin-top: 10px;"><font class="tn-in-text">Подробности</font></button></a></div></td></tr>';
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </center>
            </td>
        </tr>
    </table>
</div>
<?php echo $popup; ?>
<?php include "footer.php"; ?>