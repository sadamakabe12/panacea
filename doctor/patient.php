<?php
$pageTitle = 'Мои пациенты';
include "header-doctor.php";

// Обработка поиска и фильтров
$searchTerm = '';
$showOnlyMy = true;
$currentFilter = 'Только о моих пациентах';

if ($_POST) {
    if (isset($_POST["search"])) {
        $searchTerm = $_POST["search12"];
    }
    
    if (isset($_POST["filter"])) {
        $showOnlyMy = $_POST["showonly"] !== 'all';
        $currentFilter = $showOnlyMy ? 'Только о моих пациентах' : 'Все пациенты';
    }
}

// Получаем пациентов
$result = getDoctorPatients($database, $userid, $searchTerm, $showOnlyMy);

// Подготавливаем данные для таблицы
$patients = [];
$patientNames = [];

while ($row = $result->fetch_assoc()) {
    $patientNames[] = $row['pname'];
    $patientNames[] = $row['pemail'];
    
    $patients[] = [
        '&nbsp;' . safeValue(substr($row['pname'], 0, 50)),
        safeValue(substr($row['ptel'], 0, 50)),
        safeValue(substr($row['pemail'], 0, 50)),
        safeValue(substr($row['pdob'], 0, 50)),
        renderRowActions([
            [
                'url' => '?action=view&id=' . $row['pid'],
                'text' => 'Просмотр',
                'class' => 'btn-primary-soft btn button-icon btn-view',
                'style' => 'padding-left: 40px;padding-top: 12px;padding-bottom: 12px;margin-top: 10px;'
            ]
        ])
    ];
}
?>

<div class="dash-body">
    <?php echo renderDoctorPageHeader('Мои пациенты'); ?>
    
    <tr>
        <td>
            <?php echo renderSearchForm(
                'Поиск по имени пациента или по электронной почте',
                'Поиск',
                $patientNames
            ); ?>
        <td width="10%">
            <button class="btn-label" style="display: flex;justify-content: center;align-items: center;">
                <img src="../img/calendar.svg" width="100%">
            </button>
        </td>
    </tr>
    
    <tr>
        <td colspan="4" style="padding-top:10px;width: 100%;">
            <p class="heading-main12" style="margin-left: 45px;font-size:18px;color:rgb(49, 49, 49)">
                <?php echo $currentFilter; ?> (<?php echo count($patients); ?>)
            </p>
        </td>
    </tr>
    
    <tr>
        <td colspan="4" style="padding-top:0px;width: 100%;">
            <center>
                <table class="filter-container" border="0">
                    <tr>
                        <td width="10%"></td>
                        <td width="5%" style="text-align: center;">Фильтр:</td>
                        <td width="30%">
                            <form action="" method="post">
                                <select name="showonly" id="" class="box filter-container-items" style="width:90%;height: 37px;margin: 0;">
                                    <option value="my" <?php echo $showOnlyMy ? 'selected' : ''; ?>>Только мои пациенты</option>
                                    <option value="all" <?php echo !$showOnlyMy ? 'selected' : ''; ?>>Все пациенты</option>
                                </select>
                        </td>
                        <td width="12%">
                            <input type="submit" name="filter" value="Фильтр" class="btn-primary-soft btn button-icon btn-filter" style="padding: 15px; margin:0;width:100%">
                            </form>
                        </td>
                    </tr>
                </table>
            </center>
        </td>
    </tr>
    
    <?php 
    echo renderDataTable(
        ['Имя пациента', 'Номер телефона', 'Email', 'Дата рождения', 'Действия'],
        $patients,
        'Мы не смогли найти ничего, связанного с вашими ключевыми словами!',
        'Показать всех пациентов',
        'patient.php'
    );
    ?>
    
</table>
</div>                    <?php
                    echo '<datalist id="patient">';
                    foreach ($patientNames as $name) {
                        echo "<option value='" . htmlspecialchars($name) . "'>";
                    }
                    echo '</datalist>';
                    ?>
                    <input type="Submit" value="Поиск" name="search" class="login-btn btn-primary btn" style="padding-left: 25px;padding-right: 25px;padding-top: 10px;padding-bottom: 10px;">
                </form>
            </td>
            <td width="10%">
                <button class="btn-label" style="display: flex;justify-content: center;align-items: center;"><img src="../img/calendar.svg" width="100%"></button>
            </td>
        </tr>
        <tr>
            <td colspan="4" style="padding-top:10px;">
                <p class="heading-main12" style="margin-left: 45px;font-size:18px;color:rgb(49, 49, 49)"><?php echo $selecttype . " пациенты (" . $list11->num_rows . ")"; ?></p>
            </td>
        </tr>
        <tr>
            <td colspan="4" style="padding-top:0px;width: 100%;">
                <center>
                    <table class="filter-container" border="0">
                        <form action="" method="post">
                            <td style="text-align: right;">
                                Показать детали о : &nbsp;
                            </td>
                            <td width="30%">
                                <select name="showonly" id="" class="box filter-container-items" style="width:90% ;height: 37px;margin: 0;">
                                    <option value="" disabled selected hidden><?php echo $current ?></option><br/>
                                    <option value="my">Только о моих пациентах</option><br/>
                                    <option value="all">Всех пациентах</option><br/>
                                </select>
                            </td>
                            <td width="12%">
                                <input type="submit" name="filter" value=" Фильтр" class="btn-primary-soft btn button-icon btn-filter" style="padding: 15px; margin :0;width:100%">
                            </td>
                        </form>
                    </table>
                </center>
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
                                $result = $database->query($sqlmain);
                                if ($result->num_rows == 0) {
                                    echo '<tr>
                                        <td colspan="4">
                                            <br><br><br><br>
                                            <center>
                                                <img src="../img/notfound.svg" width="25%">
                                                <br>
                                                <p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">Мы не смогли найти ничего, связанного с вашими ключевыми словами!</p>
                                                <a class="non-style-link" href="patient.php"><button class="login-btn btn-primary-soft btn" style="display: flex;justify-content: center;align-items: center;margin-left:20px;">&nbsp; Показать всех пациентов &nbsp;</font></button></a>
                                            </center>
                                            <br><br><br><br>
                                        </td>
                                    </tr>';
                                } else {
                                    for ($x = 0; $x < $result->num_rows; $x++) {
                                        $row = $result->fetch_assoc();
                                        $pid = $row["pid"];
                                        $name = $row["pname"];
                                        $email = $row["pemail"];
                                        $dob = $row["pdob"];
                                        $tel = $row["ptel"];
                                        echo '<tr>
                                            <td>&nbsp;' . substr($name, 0, 50) . '</td>
                                            <td>' . substr($tel, 0, 50) . '</td>
                                            <td>' . substr($email, 0, 50) . '</td>
                                            <td>' . substr($dob, 0, 50) . '</td>
                                            <td>
                                                <div style="display:flex;justify-content: center;">
                                                    <a href="?action=view&id=' . $pid . '" class="non-style-link"><button class="btn-primary-soft btn button-icon btn-view" style="padding-left: 40px;padding-top: 12px;padding-bottom: 12px;margin-top: 10px;"><font class="tn-in-text">Подробности</font></button></a>
                                                </div>
                                            </td>
                                        </tr>';
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

<?php
if ($_GET) {
    $id = $_GET["id"];
    $action = $_GET["action"];
    $sqlmain = "SELECT * FROM patient WHERE pid='$id'";
    $result = $database->query($sqlmain);
    $row = $result->fetch_assoc();
    $name = $row["pname"];
    $email = $row["pemail"];
    $dob = $row["pdob"];
    $tele = $row["ptel"];
    $address = $row["paddress"];
    echo '
    <div id="popup1" class="overlay">
        <div class="popup">
            <center>
                <a class="close" href="patient.php">&times;</a>
                <div class="content"></div>
                <div style="display: flex;justify-content: center;">
                    <table width="80%" class="sub-table scrolldown add-doc-form-container" border="0">
                        <tr>
                            <td>
                                <p style="padding: 0;margin: 0;text-align: left;font-size: 25px;font-weight: 500;">Просмотр деталей</p><br><br>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <a href="patient_medical_records.php?patient_id='.$id.'" class="non-style-link">
                                    <button class="btn-primary btn button-icon" style="margin: 0; width: 100%; margin-bottom: 15px; background-image: none; padding: 8px; display: flex; justify-content: center;">
                                        <font class="tn-in-text">Просмотреть медицинскую карту</font>
                                    </button>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td class="label-td" colspan="2">
                                <label for="name" class="form-label">Patient ID: </label>
                            </td>
                        </tr>
                        <tr>
                            <td class="label-td" colspan="2">
                                P-' . $id . '<br><br>
                            </td>
                        </tr>
                        <tr>
                            <td class="label-td" colspan="2">
                                <label for="name" class="form-label">Имя: </label>
                            </td>
                        </tr>
                        <tr>
                            <td class="label-td" colspan="2">
                                ' . $name . '<br><br>
                            </td>
                        </tr>
                        <tr>
                            <td class="label-td" colspan="2">
                                <label for="Email" class="form-label">Email: </label>
                            </td>
                        </tr>
                        <tr>
                            <td class="label-td" colspan="2">
                                ' . $email . '<br><br>
                            </td>
                        </tr>
                        <tr>
                            <td class="label-td" colspan="2">
                                <label for="Tele" class="form-label">Номер телефона: </label>
                            </td>
                        </tr>
                        <tr>
                            <td class="label-td" colspan="2">
                                ' . $tele . '<br><br>
                            </td>
                        </tr>
                        <tr>
                            <td class="label-td" colspan="2">
                                <label for="name" class="form-label">Дата рождения: </label>
                            </td>
                        </tr>
                        <tr>
                            <td class="label-td" colspan="2">
                                ' . $dob . '<br><br>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <a href="patient.php"><input type="button" value="OK" class="login-btn btn-primary-soft btn"></a>
                            </td>
                        </tr>
                    </table>
                </div>
            </center>
            <br><br>
        </div>
    </div>
    ';
}
?>

<?php include "footer.php"; ?>