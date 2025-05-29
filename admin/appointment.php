<?php include "header.php"; ?>

<?php
// Получение количества записей
$appointments_count = count_all_appointments($database);

// Фильтрация
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filter_date = !empty($_POST["sheduledate"]) ? $_POST["sheduledate"] : null;
    $filter_doctor = !empty($_POST["docid"]) ? intval($_POST["docid"]) : null;
    
    $result = get_filtered_admin_appointments($database, $filter_date, $filter_doctor);
} else {
    $result = get_all_appointments($database);
}
?>

<div class="dash-body">
    <table border="0" width="100%" style=" border-spacing: 0;margin:0;padding:0;margin-top:25px; ">
        <tr>
            <td width="13%">
                <a href="javascript:history.back()"><button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px"><font class="tn-in-text">Назад</font></button></a>
            </td>
            <td>
                <p style="font-size: 23px;padding-left:12px;font-weight: 600;">Менеджер записей</p>
            </td>
            <td width="15%">
                <p style="font-size: 14px;color: rgb(119, 119, 119);padding: 0;margin: 0;text-align: right;">Сегодняшняя дата</p>
                <p class="heading-sub12" style="padding: 0;margin: 0;">
                    <?php 
                        date_default_timezone_set('Asia/Yekaterinburg');
                        echo date('d-m-Y');
                    ?>
                </p>
            </td>
            <td width="10%">
                <button class="btn-label" style="display: flex;justify-content: center;align-items: center;"><img src="../img/calendar.svg" width="100%"></button>
            </td>
        </tr>
        <tr>
            <td colspan="4" style="padding-top:10px;width: 100%;">
                <p class="heading-main12" style="margin-left: 45px;font-size:18px;color:rgb(49, 49, 49)">Все записи (<?php echo $appointments_count; ?>)</p>
            </td>
        </tr>
        <tr>
            <td colspan="4" style="padding-top:0px;width: 100%;">
                <center>
                    <form action="" method="post">
                        <table class="filter-container" border="0">
                            <tr>
                                <td width="10%"></td>
                                <td width="5%" style="text-align: center;">Дата:</td>
                                <td width="30%">
                                    <input type="date" name="sheduledate" id="date" class="input-text filter-container-items" style="margin: 0;width: 95%;">
                                </td>
                                <td width="5%" style="text-align: center;">Доктор:</td>
                                <td width="30%">
                                    <select name="docid" class="box filter-container-items" style="width:90%;height: 37px;margin: 0;">
                                        <option value="" disabled selected hidden>Выбрать имя доктора из списка</option>
                                        <?php 
                                            $list11 = $database->query("SELECT * FROM doctor ORDER BY docname ASC;");
                                            while ($row00 = $list11->fetch_assoc()) {
                                                $sn = $row00["docname"];
                                                $id00 = $row00["docid"];
                                                echo "<option value='$id00'>$sn</option>";
                                            }
                                        ?>
                                    </select>
                                </td>
                                <td width="12%">
                                    <input type="submit" name="filter" value="Фильтр" class="btn-primary-soft btn button-icon btn-filter" style="padding: 15px; margin:0;width:100%">
                                </td>
                            </tr>
                        </table>
                    </form>
                </center>
            </td>
        </tr>
        <tr>
            <td colspan="4">
                <center>
                    <div class="abc scroll">                        <table width="93%" class="sub-table scrolldown" border="0">
                            <thead>
                                <tr>
                                    <th class="table-headin">Имя пациента</th>
                                    <th class="table-headin">Номер записи</th>
                                    <th class="table-headin">Доктор</th>
                                    <th class="table-headin">Специальность</th>
                                    <th class="table-headin" style="font-size:10px">Дата и время записи</th>
                                    <th class="table-headin">Время записи</th>
                                    <th class="table-headin">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($result && $result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        $appoid = $row["appoid"];
                                        $docname = $row["docname"];
                                        $scheduledate = $row["scheduledate"];
                                        $scheduletime = $row["scheduletime"];
                                        $pname = $row["pname"];
                                        $apponum = $row["apponum"];
                                        $appodate = $row["appodate"];
                                        $specialty_name = $row["specialty_name"] ?? 'Не указана';
                                        echo '<tr >
                                            <td style="font-weight:600;">&nbsp;' . htmlspecialchars($pname) . '</td>
                                            <td style="text-align:center;font-size:23px;font-weight:500; color: var(--btnnicetext);">' . htmlspecialchars($apponum) . '</td>
                                            <td>' . htmlspecialchars($docname) . '</td>
                                            <td style="text-align:center;"><span style="background-color: #e3f2fd; color: #1976d2; padding: 2px 6px; border-radius: 8px; font-size: 11px; font-weight: 500;">' . htmlspecialchars($specialty_name) . '</span></td>
                                            <td style="text-align:center;font-size:12px;">' . htmlspecialchars($scheduledate) . '<br>' . htmlspecialchars($scheduletime) . '</td>
                                            <td style="text-align:center;">' . htmlspecialchars($appodate) . '</td>
                                            <td><div style="display:flex;justify-content: center;">
                                                <a href="?action=drop&id=' . $appoid . '&name=' . urlencode($pname) . '&apponum=' . urlencode($apponum) . '" class="non-style-link"><button class="btn-primary-soft btn button-icon btn-delete" style="padding-left: 40px;padding-top: 12px;padding-bottom: 12px;margin-top: 10px;"><font class="tn-in-text">Отменить</font></button></a>
                                            </div></td>
                                        </tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="7"><br><br><br><br><center><img src="../img/notfound.svg" width="25%"><br><p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">Мы не смогли найти ничего, связанного с вашими ключевыми словами!</p><a class="non-style-link" href="appointment.php"><button class="login-btn btn-primary-soft btn" style="display: flex;justify-content: center;align-items: center;margin-left:20px;">&nbsp; Показать все записи &nbsp;</button></a></center><br><br><br><br></td></tr>';
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