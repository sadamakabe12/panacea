<?php 
include "header.php";

// Получаем сегодняшнюю дату в формате YYYY-MM-DD для SQL
$today = date('Y-m-d');
$searchtype = "Все";
$insertkey = '';
$q = '';

// Поиск
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['search'])) {
    $keyword = trim($_POST['search']);
    $searchtype = "Результаты поиска: ";
    $insertkey = htmlspecialchars($keyword);
    $q = '"';
    $sql = "SELECT s.*, d.docname, d.docid FROM schedule s INNER JOIN doctor d ON s.docid = d.docid WHERE s.scheduledate >= ? AND s.status = 1 AND (d.docname = ? OR d.docname LIKE CONCAT(?, '%') OR d.docname LIKE CONCAT('%', ?) OR d.docname LIKE CONCAT('%', ?, '%') OR s.scheduledate LIKE CONCAT(?, '%') OR s.scheduledate LIKE CONCAT('%', ?) OR s.scheduledate LIKE CONCAT('%', ?, '%') OR s.scheduledate = ?) ORDER BY s.scheduledate ASC";
    $stmt = $database->prepare($sql);
    $stmt->bind_param('sssssssss', $today, $keyword, $keyword, $keyword, $keyword, $keyword, $keyword, $keyword, $keyword);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sql = "SELECT s.*, d.docname, d.docid FROM schedule s INNER JOIN doctor d ON s.docid = d.docid WHERE s.scheduledate >= ? AND s.status = 1 ORDER BY s.scheduledate ASC";
    $stmt = $database->prepare($sql);
    $stmt->bind_param('s', $today);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>
<div class="dash-body">
    <table border="0" width="100%" style=" border-spacing: 0;margin:0;padding:0;margin-top:25px; ">
        <tr>
            <td width="13%">
                <a href="javascript:history.back()"><button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px"><font class="tn-in-text">Назад</font></button></a>
            </td>
            <td>
                <form action="" method="post" class="header-search">
                    <input type="search" name="search" class="input-text header-searchbar" placeholder="Поиск по имени врача, электронной почте или дате (ГГГГ-ММ-ДД)" list="doctors" value="<?php echo $insertkey ?>">&nbsp;&nbsp;
                    <?php
                        echo '<datalist id="doctors">';
                        $list11 = $database->query("SELECT DISTINCT docname FROM doctor;");
                        while ($row00 = $list11->fetch_assoc()) {
                            $d = htmlspecialchars($row00["docname"]);
                            echo "<option value='$d'>";
                        }
                        echo '</datalist>';
                    ?>
                    <input type="Submit" value="Поиск" class="login-btn btn-primary btn" style="padding-left: 25px;padding-right: 25px;padding-top: 10px;padding-bottom: 10px;">
                </form>
            </td>
            <td width="15%">
                <p style="font-size: 14px;color: rgb(119, 119, 119);padding: 0;margin: 0;text-align: right;">
                    Сегодняшняя дата
                </p>
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
            <td colspan="4" style="padding-top:10px;width: 100%;" >
                <p class="heading-main12" style="margin-left: 45px;font-size:18px;color:rgb(49, 49, 49)"><?php echo $searchtype." записи(".$result->num_rows.")"; ?> </p>
                <p class="heading-main12" style="margin-left: 45px;font-size:22px;color:rgb(49, 49, 49)"><?php echo $q.$insertkey.$q ; ?> </p>
            </td>
        </tr>
        <tr>
            <td colspan="4">
                <center>
                    <div class="abc scroll">
                        <table width="100%" class="sub-table scrolldown" border="0" style="padding: 50px;border:none">
                            <tbody>
                                <?php
                                if ($result->num_rows == 0) {
                                    echo '<tr><td colspan="4"><br><br><br><br><center><img src="../img/notfound.svg" width="25%"><br><p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">Ничего не найдено по вашему запросу!</p><a class="non-style-link" href="schedule.php"><button class="login-btn btn-primary-soft btn" style="display: flex;justify-content: center;align-items: center;margin-left:20px;">&nbsp; Показать все записи &nbsp;</button></a></center><br><br><br><br></td></tr>';
                                } else {
                                    $rows = $result->fetch_all(MYSQLI_ASSOC);
                                    $chunks = array_chunk($rows, 3);
                                    foreach ($chunks as $rowset) {
                                        echo "<tr>";
                                        foreach ($rowset as $row) {
                                            $scheduleid = (int)$row["scheduleid"];
                                            $docname = htmlspecialchars($row["docname"]);
                                            $docid = (int)$row["docid"];
                                            $scheduledate = htmlspecialchars($row["scheduledate"]);
                                            $scheduletime = htmlspecialchars($row["scheduletime"]);
                                            // Получаем специальности врача
                                            $sql_specialty = "SELECT GROUP_CONCAT(s.sname SEPARATOR ', ') as specialties FROM doctor_specialty ds JOIN specialties s ON ds.specialty_id = s.id WHERE ds.docid = ?";
                                            $stmt2 = $database->prepare($sql_specialty);
                                            $stmt2->bind_param('i', $docid);
                                            $stmt2->execute();
                                            $res2 = $stmt2->get_result();
                                            $specialties = '';
                                            if ($row2 = $res2->fetch_assoc()) {
                                                $specialties = htmlspecialchars($row2['specialties']);
                                            }
                                            $stmt2->close();
                                            echo '<td style="width: 25%;"><div class="dashboard-items search-items"><div style="width:100%"><div class="h3-search">'.$docname.'</div><div class="h4-search">'.$specialties.'</div><div class="h4-search">'.$scheduledate.'<br>Начало: <b>'.substr($scheduletime,0,5).'</b></div><br><a href="booking.php?id='.$scheduleid.'"><button class="login-btn btn-primary-soft btn " style="padding-top:11px;padding-bottom:11px;width:100%"><font class="tn-in-text">Записаться</font></button></a></div></div></td>';
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
