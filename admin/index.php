<?php include "header.php"; ?>

<?php
// Получаем сегодняшнюю дату
$today = date('Y-m-d');
$nextweek = date('Y-m-d', strtotime('+1 week'));

$patientrow = $database->query("SELECT * FROM patient;");
$doctorrow = $database->query("SELECT * FROM doctor;");
$appointmentrow = $database->query("SELECT * FROM appointment WHERE appodate>='$today';");
$schedulerow = $database->query("SELECT * FROM schedule WHERE scheduledate='$today';");
?>

<div class="dash-body" style="margin-top: 15px">
    <table border="0" width="100%" style=" border-spacing: 0;margin:0;padding:0;">
        <tr>
            <td colspan="2" class="nav-bar">
                <form action="doctors.php" method="post" class="header-search">
                    <input type="search" name="search" class="input-text header-searchbar" placeholder="Поиск по имени врача или электронной почте" list="doctors">&nbsp;&nbsp;
                    <?php
                        echo '<datalist id="doctors">';
                        $list11 = $database->query("SELECT docname, docemail FROM doctor;");
                        while ($row00 = $list11->fetch_assoc()) {
                            $d = $row00["docname"];
                            $c = $row00["docemail"];
                            echo "<option value='$d'><br/>";
                            echo "<option value='$c'><br/>";
                        }
                        echo ' </datalist>';
                    ?>
                    <input type="Submit" value="Поиск" class="login-btn btn-primary-soft btn" style="padding-left: 25px;padding-right: 25px;padding-top: 10px;padding-bottom: 10px;">
                </form>
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
            <td colspan="4">
                <center>
                    <table class="filter-container" style="border: none;" border="0">
                        <tr>
                            <td colspan="4">
                                <p style="font-size: 20px;font-weight:600;padding-left: 12px;">Статус</p>
                            </td>
                        </tr>
                        <tr>                            <td style="width: 25%;">
                                <div class="dashboard-items doctors-card">
                                    <div class="dashboard-card-content">
                                        <div class="h1-dashboard"><?php echo $doctorrow->num_rows ?></div>
                                        <div class="h3-dashboard">Врачи</div>
                                    </div>
                                    <div class="dashboard-card-icon">
                                        <div class="dashboard-icons">
                                            <img src="../img/icons/doctors-hover.svg" alt="Doctors Icon">
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td style="width: 25%;">
                                <div class="dashboard-items patients-card">
                                    <div class="dashboard-card-content">
                                        <div class="h1-dashboard"><?php echo $patientrow->num_rows ?></div>
                                        <div class="h3-dashboard">Пациенты</div>
                                    </div>
                                    <div class="dashboard-card-icon">
                                        <div class="dashboard-icons">
                                            <img src="../img/icons/patients-hover.svg" alt="Patients Icon">
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td style="width: 25%;">
                                <div class="dashboard-items appointments-card">
                                    <div class="dashboard-card-content">
                                        <div class="h1-dashboard"><?php echo $appointmentrow->num_rows ?></div>
                                        <div class="h3-dashboard">Новые записи</div>
                                    </div>
                                    <div class="dashboard-card-icon">
                                        <div class="dashboard-icons">
                                            <img src="../img/icons/book-hover.svg" alt="Appointments Icon">
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td style="width: 25%;">
                                <div class="dashboard-items today-card">
                                    <div class="dashboard-card-content">
                                        <div class="h1-dashboard"><?php echo $schedulerow->num_rows ?></div>
                                        <div class="h3-dashboard">Сегодняшние записи</div>
                                    </div>
                                    <div class="dashboard-card-icon">
                                        <div class="dashboard-icons">
                                            <img src="../img/icons/session-iceblue.svg" alt="Today Icon">
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </center>
            </td>
        </tr>
        <tr>
            <td colspan="4">
                <table width="100%" border="0" class="dashbord-tables">
                    <tr>
                        <td>
                            <p style="padding:10px;padding-left:48px;padding-bottom:0;font-size:23px;font-weight:700;color:var(--primarycolor);">Предстоящие записи</p>
                            <p style="padding-bottom:19px;padding-left:50px;font-size:15px;font-weight:500;color:#212529e3;line-height: 20px;">Здесь вы можете быстро получить доступ к предстоящим записям в течение 7 дней.<br>Более подробная информация доступна в разделе записи.</p>
                        </td>
                        <td>
                            <p style="text-align:right;padding:10px;padding-right:48px;padding-bottom:0;font-size:23px;font-weight:700;color:var(--primarycolor);">Предстоящие свободные сеансы</p>
                            <p style="padding-bottom:19px;text-align:right;padding-right:50px;font-size:15px;font-weight:500;color:#212529e3;line-height: 20px;">Здесь вы можете быстро получить доступ к предстоящим сеансам, запланированным на 7 дней вперед.<br>Добавляйте, удаляйте и используйте множество других функций в разделе расписание.</p>
                        </td>
                    </tr>
                    <tr>
                        <td width="50%">
                            <center>
                                <div class="abc scroll" style="height: 200px;">
                                    <table width="85%" class="sub-table scrolldown" border="0">
                                        <thead>
                                            <tr>
                                                <th class="table-headin" style="font-size: 12px;">Номер записи</th>
                                                <th class="table-headin">Имя пациента</th>
                                                <th class="table-headin">Доктор</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $sqlmain = "SELECT appointment.appoid,schedule.scheduleid,doctor.docname,patient.pname,schedule.scheduledate,schedule.scheduletime,appointment.apponum,appointment.appodate FROM schedule INNER JOIN appointment ON schedule.scheduleid=appointment.scheduleid INNER JOIN patient ON patient.pid=appointment.pid INNER JOIN doctor ON schedule.docid=doctor.docid WHERE schedule.scheduledate>='$today' AND schedule.scheduledate<='$nextweek' ORDER BY schedule.scheduledate DESC";
                                            $result = $database->query($sqlmain);
                                            if ($result->num_rows == 0) {
                                                echo '<tr><td colspan="3"><br><br><br><br><center><img src="../img/notfound.svg" width="25%"><br><p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">Мы не смогли найти ничего, связанного с вашими ключевыми словами!</p><a class="non-style-link" href="appointment.php"><button class="login-btn btn-primary-soft btn" style="display: flex;justify-content: center;align-items: center;margin-left:20px;">&nbsp; Показать все записи &nbsp;</button></a></center><br><br><br><br></td></tr>';
                                            } else {
                                                while ($row = $result->fetch_assoc()) {
                                                    $apponum = $row["apponum"];
                                                    $pname = $row["pname"];
                                                    $docname = $row["docname"];
                                                    echo '<tr>';
                                                    echo '<td style="text-align:center;font-size:23px;font-weight:500; color: var(--btnnicetext);padding:20px;">' . htmlspecialchars($apponum) . '</td>';
                                                    echo '<td style="font-weight:600;">&nbsp;' . htmlspecialchars($pname) . '</td>';
                                                    echo '<td style="font-weight:600;">&nbsp;' . htmlspecialchars($docname) . '</td>';
                                                    echo '</tr>';
                                                }
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </center>
                        </td>
                        <td width="50%" style="padding: 0;">
                            <center>
                                <div class="abc scroll" style="height: 200px;padding: 0;margin: 0;">
                                    <table width="85%" class="sub-table scrolldown" border="0">
                                        <thead>
                                            <tr>
                                                <th class="table-headin">Доктор</th>
                                                <th class="table-headin">Дата и время</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $sqlmain = "SELECT schedule.scheduleid,doctor.docname,schedule.scheduledate,schedule.scheduletime FROM schedule INNER JOIN doctor ON schedule.docid=doctor.docid WHERE schedule.scheduledate>='$today' AND schedule.scheduledate<='$nextweek' ORDER BY schedule.scheduledate ASC, schedule.scheduletime ASC";
                                            $result = $database->query($sqlmain);
                                            if ($result->num_rows == 0) {
                                                echo '<tr><td colspan="3"><br><br><br><br><center><img src="../img/notfound.svg" width="25%"><br><p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">Мы не смогли найти ничего, связанного с вашими ключевыми словами!</p><a class="non-style-link" href="appointment.php"><button class="login-btn btn-primary-soft btn" style="display: flex;justify-content: center;align-items: center;margin-left:20px;">&nbsp; Показать все записи &nbsp;</button></a></center><br><br><br><br></td></tr>';
                                            } else {
                                                while ($row = $result->fetch_assoc()) {
                                                    $docname = $row["docname"];
                                                    $scheduledate = $row["scheduledate"];
                                                    $scheduletime = $row["scheduletime"];
                                                    echo '<tr>';
                                                    echo '<td style="font-weight:600;">' . htmlspecialchars($docname) . '</td>';
                                                    echo '<td style="text-align:center;">' . htmlspecialchars($scheduledate) . ' ' . htmlspecialchars($scheduletime) . '</td>';
                                                    echo '</tr>';
                                                }
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </center>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <center>
                                <a href="appointment.php" class="non-style-link"><button class="btn-primary btn" style="width:85%">Показать все записи</button></a>
                            </center>
                        </td>
                        <td>
                            <center>
                                <a href="schedule.php" class="non-style-link"><button class="btn-primary btn" style="width:85%">Показать все записи</button></a>
                            </center>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>

<?php include "footer.php"; ?>