<?php 
include "header.php";
require_once "../includes/appointment_functions.php";

// Обработка запроса на удаление/отмену записи
if (isset($_GET["action"]) && $_GET["action"] == "delete" && isset($_GET["id"])) {
    $id = intval($_GET["id"]);
    
    // Используем функцию из appointment_functions.php
    if (cancel_appointment($database, $id)) {
        // Успешное удаление
        header("location: appointment.php?action=deleted");
        exit();
    } else {
        // Ошибка при удалении
        header("location: appointment.php?action=error");
        exit();
    }
}

// Get all appointments for the patient with optional date filtering
$params = [$userid];
$filter_date = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST["sheduledate"])) {
    $filter_date = $_POST["sheduledate"];
}

$result = get_filtered_patient_appointments($database, $userid, $filter_date);
?>

<div class="dash-body">
    <table border="0" width="100%" style=" border-spacing: 0;margin:0;padding:0;margin-top:25px; ">
        <tr>
            <td width="13%">
                <a href="javascript:history.back()"><button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px"><font class="tn-in-text">Назад</font></button></a>
            </td>
            <td>
                <p style="font-size: 23px;padding-left:12px;font-weight: 600;">История записей</p>
            </td>
            <td width="15%">
                <p style="font-size: 14px;color: rgb(119, 119, 119);padding: 0;margin: 0;text-align: right;">Сегодняшняя дата</p>
                <p class="heading-sub12" style="padding: 0;margin: 0;">
                    <?php 
                    date_default_timezone_set('Asia/Yekaterinburg');
                    $today = date('d-m-Y');
                    echo $today;
                    ?>
                </p>
            </td>
            <td width="10%">
                <button class="btn-label" style="display: flex;justify-content: center;align-items: center;"><img src="../img/calendar.svg" width="100%"></button>
            </td>
        </tr>
        <tr>
            <td colspan="4" style="padding-top:10px;width: 100%;" >
                <p class="heading-main12" style="margin-left: 45px;font-size:18px;color:rgb(49, 49, 49)">Записи (<?php echo $result->num_rows; ?>)</p>
            </td>
        </tr>
        <tr>
            <td colspan="4" style="padding-top:0px;width: 100%;" >
                <center>
                <table class="filter-container" border="0" >
                    <tr>
                        <td width="10%"></td> 
                        <td width="5%" style="text-align: center;">Дата:</td>
                        <td width="30%">
                            <form action="" method="post">
                                <input type="date" name="sheduledate" id="date" class="input-text filter-container-items" style="margin: 0;width: 95%;">
                        </td>
                        <td width="12%">
                            <input type="submit" name="filter" value=" Фильтр" class="btn-primary-soft btn button-icon btn-filter" style="padding: 15px; margin :0;width:100%">
                            </form>
                        </td>
                    </tr>
                </table>
                </center>
            </td>
        </tr>
        <tr>
            <td colspan="4">
                <center>
                <div class="abc scroll">
                <table width="93%" class="sub-table scrolldown" border="0" style="border:none">
                    <tbody>
                        <?php
                        if($result->num_rows==0){
                            echo '<tr>
                            <td colspan="7">
                            <br><br><br><br>
                            <center>
                            <img src="../img/notfound.svg" width="25%">
                            <br>
                            <p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">Мы не смогли найти ничего, связанного с вашими ключевыми словами!</p>
                            <a class="non-style-link" href="appointment.php"><button class="login-btn btn-primary-soft btn" style="display: flex;justify-content: center;align-items: center;margin-left:20px;">&nbsp; Показать все записи &nbsp;</button></a>
                            </center>
                            <br><br><br><br>
                            </td>
                            </tr>';
                        } else {
                            $rows = $result->fetch_all(MYSQLI_ASSOC);
                            $chunks = array_chunk($rows, 3);
                            foreach ($chunks as $rowset) {
                                echo "<tr>";
                                foreach ($rowset as $row) {
                                    $scheduleid = $row["scheduleid"];
                                    $docname = $row["docname"];
                                    $scheduledate = $row["scheduledate"];
                                    $scheduletime = $row["scheduletime"];
                                    $apponum = $row["apponum"];
                                    $appodate = $row["appodate"];
                                    $appoid = $row["appoid"];
                                    if($scheduleid==""){
                                        break;
                                    }
                                    echo '
                                    <td style="width: 25%;">
                                        <div class="dashboard-items search-items">
                                            <div style="width:100%;">
                                                <div class="h3-search">
                                                    Дата бронирования: '.substr($appodate,0,30).'<br>
                                                    Номер ссылки: OC-000-'.$appoid.' <br>
                                                    Номер записи: 0'.$apponum.'
                                                </div>
                                                <div class="h1-search"></div>
                                                <div class="h3-search">'.substr($docname,0,60).'</div>
                                                <div class="h4-search">
                                                    Запланированная дата: '.$scheduledate.'<br>Начало: <b>'.substr($scheduletime,0,5).'</b>
                                                </div>
                                                <br>
                                                <a href="?action=delete&id='.$appoid.'"><button class="login-btn btn-primary-soft btn " style="padding-top:11px;padding-bottom:11px;width:100%"><font class="tn-in-text">Отменить запись</font></button></a>
                                            </div>
                                        </div>
                                    </td>';
                                }
                                echo "</tr>";
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

<?php include "footer.php"; ?>
