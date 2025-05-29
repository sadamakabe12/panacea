<?php
$pageTitle = 'Главная панель врача';
include "header-doctor.php";

// Получаем статистику
$stats = getDashboardQuickStats($database, $userid);
$doctorStats = getDoctorStatistics($database, $userid);

// Получаем предстоящие сеансы
$upcomingSessions = getDoctorUpcomingSessions($database, $userid, 5);

date_default_timezone_set('Asia/Yekaterinburg');
$today = date('d-m-Y');
?>

<div class="dash-body" style="margin-top: 15px">
    <?php echo renderDoctorPageHeader('Главная', false); ?>
    
    <tr>
        <td colspan="4">
            <center>
                <table class="filter-container doctor-header" style="border: none;width:95%" border="0">
                    <tr>
                        <td>
                            <h3>Добро пожаловать!</h3>
                            <h1><?php echo htmlspecialchars($username); ?>.</h1>
                            <p>Спасибо, что присоединились к нам. Мы всегда стараемся предоставить вам полный сервис<br>
                            Вы можете просмотреть свое ежедневное расписание, доступ к записям пациентов на дому!<br><br></p>                            <a href="appointments.php" class="non-style-link">
                                <button class="btn-primary btn" style="width:30%">Просмотреть записи</button>
                            </a>
                            <br><br>
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
                    <td width="50%">
                        <center>
                            <table class="filter-container" style="border: none;" border="0">
                                <tr>
                                    <td colspan="4">
                                        <p style="font-size: 20px;font-weight:600;padding-left: 12px;">Статус</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width: 25%;">
                                        <?php echo renderDashboardCard(
                                            'Все врачи', 
                                            $stats['all_doctors'], 
                                            '../img/icons/doctors-hover.svg',
                                            'dashboard-items doctors-card'
                                        ); ?>
                                    </td>
                                    <td style="width: 25%;">
                                        <?php echo renderDashboardCard(
                                            'Все пациенты', 
                                            $stats['all_patients'], 
                                            '../img/icons/patients-hover.svg',
                                            'dashboard-items patients-card'
                                        ); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width: 25%;">
                                        <?php echo renderDashboardCard(
                                            'Новые записи', 
                                            $stats['new_appointments'], 
                                            '../img/icons/book-hover.svg',
                                            'dashboard-items appointments-card'
                                        ); ?>
                                    </td>
                                    <td style="width: 25%;">
                                        <?php echo renderDashboardCard(
                                            'Сегодняшние записи', 
                                            $stats['today_appointments'], 
                                            '../img/icons/session-iceblue.svg',
                                            'dashboard-items today-card'
                                        ); ?>
                                    </td>
                                </tr>
                            </table>
                        </center>
                    </td>
                    
                    <td>
                        <p style="font-size: 20px;font-weight:600;padding-left: 40px;">Ваши предстоящие сеансы</p>
                        <center>
                            <div class="abc scroll" style="height: 250px;padding: 0;margin: 0;">
                                <table width="85%" class="sub-table scrolldown" border="0">
                                    <thead>
                                        <tr>
                                            <th class="table-headin">Запланированный день</th>
                                            <th class="table-headin">Время</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($upcomingSessions->num_rows == 0) {
                                            echo '<tr><td colspan="2" style="text-align:center;"><br><br><img src="../img/notfound.svg" width="25%"><br><p style="font-size:20px;">Сессий не найдено!</p></td></tr>';
                                        } else {
                                            while ($row = $upcomingSessions->fetch_assoc()) {
                                                echo '<tr>';
                                                echo '<td style="padding:20px;font-size:20px;">' . substr($row["scheduledate"], 0, 10) . '</td>';
                                                echo '<td style="text-align:center;">' . substr($row["scheduletime"], 0, 20) . '</td>';
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
            </table>
        </td>
    </tr>
</table>
</div>

<?php include "footer.php"; ?>
                    <table border="0" width="100%">
                        <tr>
                            <td width="50%">
                                <center>
                                    <table class="filter-container" style="border: none;" border="0">
                                        <tr>
                                            <td colspan="4">
                                                <p style="font-size: 20px;font-weight:600;padding-left: 12px;">Статус</p>
                                            </td>
                                        </tr>
                                        <tr>                                            <td style="width: 25%;">
                                                <div class="dashboard-items doctors-card">
                                                    <div class="dashboard-card-content">
                                                        <div class="h1-dashboard"><?php echo $doctorrow->num_rows; ?></div>
                                                        <div class="h3-dashboard">Все врачи</div>
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
                                                        <div class="h1-dashboard"><?php echo $patientrow->num_rows; ?></div>
                                                        <div class="h3-dashboard">Все пациенты</div>
                                                    </div>
                                                    <div class="dashboard-card-icon">
                                                        <div class="dashboard-icons">
                                                            <img src="../img/icons/patients-hover.svg" alt="Patients Icon">
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>                                            <td style="width: 25%;">
                                                <div class="dashboard-items appointments-card">
                                                    <div class="dashboard-card-content">
                                                        <div class="h1-dashboard"><?php echo $appointmentrow->num_rows; ?></div>
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
                                                        <div class="h1-dashboard"><?php echo $schedulerow->num_rows; ?></div>
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
                            <td>
                                <p id="anim" style="font-size: 20px;font-weight:600;padding-left: 40px;">Ваши предстоящие сеансы</p>
                                <center>
                                    <div class="abc scroll" style="height: 250px;padding: 0;margin: 0;">
                                        <table width="85%" class="sub-table scrolldown" border="0">
                                            <thead>
                                                <tr>
                                                    <th class="table-headin">Запланированный день</th>
                                                    <th class="table-headin">Время</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $nextweek = date("Y-m-d", strtotime("+1 week"));
                                                $sqlmain = "SELECT schedule.scheduleid,doctor.docname,schedule.scheduledate,schedule.scheduletime FROM schedule INNER JOIN doctor ON schedule.docid=doctor.docid WHERE schedule.docid = '$userid' AND schedule.scheduledate>='".date('Y-m-d')."' AND schedule.scheduledate<='$nextweek' ORDER BY schedule.scheduledate ASC, schedule.scheduletime ASC";
                                                $result = $database->query($sqlmain);
                                                if ($result->num_rows == 0) {
                                                    echo '<tr><td colspan="4"><br><br><br><br><center><img src="../img/notfound.svg" width="25%"><br><p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">Мы не смогли найти ничего, связанного с вашими ключевыми словами!</p><a class="non-style-link" href="schedule.php"><button class="login-btn btn-primary-soft btn" style="display: flex;justify-content: center;align-items: center;margin-left:20px;">&nbsp; Показать все сессии &nbsp;</button></a></center><br><br><br><br></td></tr>';
                                                } else {
                                                    while ($row = $result->fetch_assoc()) {
                                                        echo '<tr><td style="padding:20px;font-size:20px;">'.substr($row["scheduledate"],0,10).'</td><td style="text-align:center;">'.substr($row["scheduletime"],0,20).'</td></tr>';
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
    </div>
</div>
<?php include "footer.php"; ?>