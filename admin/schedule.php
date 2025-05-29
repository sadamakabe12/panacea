<?php
include_once "header.php";
include_once "../includes/init.php";
date_default_timezone_set('Asia/Yekaterinburg');
$today = date('d-m-Y');

// Получаем список всех врачей
$doctors_query = "SELECT d.docid, d.docname, d.docemail, d.doctel, 
                         COUNT(DISTINCT s.scheduleid) as total_sessions,
                         COUNT(DISTINCT a.appoid) as total_appointments,
                         GROUP_CONCAT(DISTINCT sp.sname SEPARATOR ', ') as specialties
                  FROM doctor d 
                  LEFT JOIN schedule s ON d.docid = s.docid 
                  LEFT JOIN appointment a ON s.scheduleid = a.scheduleid
                  LEFT JOIN doctor_specialty ds ON d.docid = ds.docid
                  LEFT JOIN specialties sp ON ds.specialty_id = sp.id
                  GROUP BY d.docid, d.docname, d.docemail, d.doctel
                  ORDER BY d.docname ASC";

// Фильтрация врачей
$search_filter = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['search_doctor'])) {
    $search_term = $database->real_escape_string($_POST['search_doctor']);
    $doctors_query = "SELECT d.docid, d.docname, d.docemail, d.doctel, 
                             COUNT(DISTINCT s.scheduleid) as total_sessions,
                             COUNT(DISTINCT a.appoid) as total_appointments,
                             GROUP_CONCAT(DISTINCT sp.sname SEPARATOR ', ') as specialties
                      FROM doctor d 
                      LEFT JOIN schedule s ON d.docid = s.docid 
                      LEFT JOIN appointment a ON s.scheduleid = a.scheduleid
                      LEFT JOIN doctor_specialty ds ON d.docid = ds.docid
                      LEFT JOIN specialties sp ON ds.specialty_id = sp.id
                      WHERE d.docname LIKE '%$search_term%' OR d.docemail LIKE '%$search_term%'
                      GROUP BY d.docid, d.docname, d.docemail, d.doctel
                      ORDER BY d.docname ASC";
    $search_filter = $search_term;
}

$result = $database->query($doctors_query);

?>
<div class="dash-body" style="overflow-x:hidden;">
    <table border="0" width="100%" style="border-spacing: 0;margin:0;padding:0;margin-top:25px;">
        <tr>
            <td width="13%">
                <a href="javascript:history.back()"><button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px"><font class="tn-in-text">Назад</font></button></a>
            </td>
            <td>
                <p style="font-size: 23px;padding-left:12px;font-weight: 600;">Управление расписанием врачей</p>
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
            <td colspan="4" style="padding-top:10px;width: 100%;">
                <p class="heading-main12" style="margin-left: 45px;font-size:18px;color:rgb(49, 49, 49)">Выберите врача для управления расписанием (<?php echo $result->num_rows; ?>)</p>
            </td>
        </tr>
        <tr>
            <td colspan="4" style="padding-top:0px;width: 100%;">
                <center>
                    <table class="filter-container" border="0">
                        <tr>
                            <td width="20%"></td>
                            <td width="10%" style="text-align: center;">Поиск врача:</td>
                            <td width="40%">
                                <form action="" method="post">
                                    <input type="text" name="search_doctor" id="search_doctor" placeholder="Введите имя или email врача" value="<?php echo htmlspecialchars($search_filter); ?>" class="input-text filter-container-items" style="margin: 0;width: 95%;">
                            </td>
                            <td width="15%">
                                <input type="submit" name="filter" value="Найти" class="btn-primary-soft btn button-icon btn-filter" style="padding: 15px; margin:0;width:100%">
                                </form>
                            </td>
                            <td width="15%">
                                <a href="schedule.php" class="non-style-link">
                                    <button class="btn-primary-soft btn" style="padding: 15px; margin:0;width:100%;background-color:#666;">Сбросить</button>
                                </a>
                            </td>
                        </tr>
                    </table>
                </center>
            </td>
        </tr>        <tr>
            <td colspan="4">
                <center>
                    <div class="abc scroll">
                        <div class="schedule-table-scroll" style="overflow-x:auto; width:100%;">
                            <table width="93%" class="sub-table scrolldown" border="0">
                                <thead>
                                    <tr>
                                        <th class="table-headin">Врач</th>
                                        <th class="table-headin">Контактная информация</th>
                                        <th class="table-headin">Специальности</th>
                                        <th class="table-headin">Статистика</th>
                                        <th class="table-headin">Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($result->num_rows == 0) {
                                        echo '<tr>
                                        <td colspan="5">
                                        <br><br><br><br>
                                        <center>
                                        <img src="../img/notfound.svg" width="25%">
                                        <br>
                                        <p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">Врачи не найдены!</p>
                                        <a class="non-style-link" href="schedule.php"><button class="login-btn btn-primary-soft btn" style="display: flex;justify-content: center;align-items: center;margin-left:20px;">&nbsp; Показать всех врачей &nbsp;</button></a>
                                        </center>
                                        <br><br><br><br>
                                        </td>
                                        </tr>';
                                    } else {
                                        while ($row = $result->fetch_assoc()) {
                                            echo '<tr style="height: 70px;">
                                            <td style="padding: 15px;">
                                                <div style="font-weight: 600; font-size: 16px; color: #333; margin-bottom: 5px;">' . htmlspecialchars($row["docname"]) . '</div>
                                                <div style="font-size: 12px; color: #666;">ID: ' . $row["docid"] . '</div>
                                            </td>
                                            <td style="padding: 15px;">
                                                <div style="margin-bottom: 3px;"><strong>Email:</strong> ' . htmlspecialchars($row["docemail"]) . '</div>
                                                <div><strong>Телефон:</strong> ' . htmlspecialchars($row["doctel"] ?? "Не указан") . '</div>
                                            </td>
                                            <td style="padding: 15px;">
                                                <div style="font-size: 14px;">' . htmlspecialchars($row["specialties"] ?? "Не указаны") . '</div>
                                            </td>
                                            <td style="padding: 15px;">
                                                <div style="margin-bottom: 3px;"><span style="color: #2196F3; font-weight: 600;">' . $row["total_sessions"] . '</span> сессий в расписании</div>
                                                <div><span style="color: #4CAF50; font-weight: 600;">' . $row["total_appointments"] . '</span> записей пациентов</div>
                                            </td>
                                            <td style="padding: 15px;">
                                                <div style="display:flex;justify-content: center;flex-direction: column;gap: 10px;">
                                                    <a href="doctor_schedule_manager.php?docid=' . $row["docid"] . '" class="non-style-link">
                                                        <button class="btn-primary-soft btn" style="padding: 10px 20px;width: 100%;background-color: #2196F3;color: white;border-radius: 5px;">
                                                            <font class="tn-in-text">Управление расписанием</font>
                                                        </button>
                                                    </a>
                                                    <a href="doctors.php?action=view&id=' . $row["docid"] . '" class="non-style-link">
                                                        <button class="btn-primary-soft btn" style="padding: 8px 15px;width: 100%;background-color: #9E9E9E;color: white;border-radius: 5px;font-size: 12px;">
                                                            <font class="tn-in-text">Профиль врача</font>
                                                        </button>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>';
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </center>
            </td>
        </tr>
    </table>
</div>
<?php echo $popup; ?>

<script>
function toggleDeleteButtons(id) {
    const deleteBtn = document.getElementById('deleteBtn_' + id);
    const confirmBtns = document.getElementById('confirmBtns_' + id);
    
    if (deleteBtn.style.display === 'none') {
        // Показываем кнопку удаления, скрываем кнопки Да/Нет
        deleteBtn.style.display = 'inline-block';
        confirmBtns.style.display = 'none';
    } else {
        // Скрываем кнопку удаления, показываем кнопки Да/Нет
        deleteBtn.style.display = 'none';
        confirmBtns.style.display = 'inline-block';
    }
}
</script>

<?php include "footer.php"; ?>