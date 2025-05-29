<?php 
include "header.php";

date_default_timezone_set('Asia/Yekaterinburg');
$today = date('Y-m-d');

// Получаем предстоящие записи
$sqlmain = "SELECT appointment.apponum, doctor.docname, schedule.scheduledate, schedule.scheduletime FROM schedule 
    INNER JOIN appointment ON schedule.scheduleid=appointment.scheduleid 
    INNER JOIN patient ON patient.pid=appointment.pid 
    INNER JOIN doctor ON schedule.docid=doctor.docid  
    WHERE patient.pid=? AND schedule.scheduledate>=?
    ORDER BY schedule.scheduledate ASC";
$stmt = $database->prepare($sqlmain);
$stmt->bind_param("is", $userid, $today);
$stmt->execute();
$result = $stmt->get_result();
?>
<table border="0" width="100%" style=" border-spacing: 0;margin:0;padding:0;" >
    <tr>
        <td colspan="1" class="nav-bar" >
            <p style="font-size: 23px;padding-left:12px;font-weight: 600;margin-left:20px;">Главная</p>
        </td>
        <td width="25%"></td>
        <td width="15%">
            <p style="font-size: 14px;color: rgb(119, 119, 119);padding: 0;margin: 0;text-align: right;">Сегодняшняя дата</p>
            <p class="heading-sub12" style="padding: 0;margin: 0;">
                <?php echo date('d-m-Y'); ?>
            </p>
        </td>
        <td width="10%">
            <button class="btn-label" style="display: flex;justify-content: center;align-items: center;">
                <img src="../img/calendar.svg" width="100%">
            </button>
        </td>
    </tr>
    <tr>
        <td colspan="4" >
            <center>
            <table class="filter-container doctor-header patient-header" style="border: none;width:95%" border="0" >
                <tr>
                    <td>
                        <h3>Добро пожаловать!</h3>
                        <h1><?php echo $username; ?>.</h1>
                        <p>Не знаете, к какому врачу обратиться? Не проблема, перейдите в раздел 
                            <a href="doctors.php" class="non-style-link"><b>"Все врачи"</b></a> или 
                            <a href="schedule.php" class="non-style-link"><b>"Свободные записи"</b> </a><br>
                            Отслеживайте историю своих прошлых и будущих записей.<br>Также узнайте ожидаемое время прибытия вашего врача или медицинского консультанта.<br>
                            Просматривайте свою <a href="medical_records.php" class="non-style-link"><b>"Медицинскую карту"</b></a> с историей заболеваний, диагнозами и назначениями.<br><br>
                        </p>
                        <h3>Записаться к врачу здесь</h3>
                        <form action="schedule.php" method="post" style="display: flex">
                            <input type="search" name="search" class="input-text " placeholder="Поиск врача, и мы найдем доступные сессии" list="doctors" style="width:45%;">&nbsp;&nbsp;
                            <?php
                                echo '<datalist id="doctors">';
                                $list11 = $database->query("SELECT docname FROM doctor;");
                                while ($row00 = $list11->fetch_assoc()) {
                                    $d = $row00["docname"];
                                    echo "<option value='$d'>";
                                }
                                echo '</datalist>';
                            ?>
                            <input type="Submit" value="Поиск" class="login-btn btn-primary btn" style="padding-left: 25px;padding-right: 25px;padding-top: 10px;padding-bottom: 10px;">
                        </form>
                    </td>
                </tr>
            </table>
            </center>
        </td>
    </tr>
    <tr>
        <td colspan="4">
            <table border="0" width="100%">
                <tr>
                    <td>
                        <p style="font-size: 20px;font-weight:600;padding-left: 90px;" class="anime">Ваши предстоящие записи</p>
                        <center>
                            <div class="abc scroll" style="height: 550px;padding: 0;margin: 0;">
                                <table width="85%" class="sub-table scrolldown" border="0" >
                                    <thead>
                                        <tr>
                                            <th class="table-headin">Номер записи</th>
                                            <th class="table-headin">Врач</th>
                                            <th class="table-headin">Дата и время</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($result->num_rows == 0) {
                                            echo '<tr>
                                                <td colspan="4">
                                                <center>
                                                <img src="../img/notfound.svg" width="25%">                                                <br>
                                                <p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">Здесь ничего нет!</p>
                                                <a class="non-style-link" href="schedule.php">
                                                    <button class="login-btn btn-primary-soft btn" style="display: flex;justify-content: center;align-items: center;margin-left:20px;">&nbsp; Выбрать врача &nbsp;</button>
                                                </a>
                                                </center>
                                                </td>
                                            </tr>';
                                        } else {
                                            while ($row = $result->fetch_assoc()) {
                                                $apponum = $row["apponum"];
                                                $docname = $row["docname"];
                                                $scheduledate = $row["scheduledate"];
                                                $scheduletime = $row["scheduletime"];
                                                echo '<tr>
                                                    <td style="padding:30px;font-size:25px;font-weight:700;">&nbsp;' . $apponum . '</td>
                                                    <td>' . htmlspecialchars($docname) . '</td>
                                                    <td style="text-align:center;">' . htmlspecialchars($scheduledate) . ' ' . htmlspecialchars(substr($scheduletime, 0, 5)) . '</td>
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
        </td>
    </tr>
</table>
<?php include "footer.php"; ?>