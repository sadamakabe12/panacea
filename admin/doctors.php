<?php 
include_once "header.php"; 
include_once "../includes/init.php";
include_once "../includes/specialty_checkbox_functions.php";
?>
<?php
// Обработка POST-запроса для добавления врача
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_doctor'])) {
    $name = $_POST['name'] ?? '';
    $spec = isset($_POST['spec']) ? $_POST['spec'] : array();
    $email = $_POST['email'] ?? '';
    $tele = $_POST['Tele'] ?? '';
    $password = $_POST['password'] ?? '';
    $cpassword = $_POST['cpassword'] ?? '';
    
    // Используем централизованную функцию для добавления врача
    $error = add_doctor($database, $name, $spec, $email, $tele, $password, $cpassword);
    
    header("Location: doctors.php?action=add&error=$error");
    exit();
}

// Обработка POST-запроса для удаления врача
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_doctor'])) {
    $id = intval($_POST['delete_doctor']);
    
    // Используем централизованную функцию для удаления врача
    if (delete_doctor($database, $id)) {
        header("Location: doctors.php?action=deleted");
    } else {
        header("Location: doctors.php?action=error");
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $keyword = $_POST["search"];
    $sqlmain = "SELECT * FROM doctor WHERE docemail='$keyword' OR docname='$keyword' OR docname LIKE '$keyword%' OR docname LIKE '%$keyword' OR docname LIKE '%$keyword%'";
} else {
    $sqlmain = "SELECT * FROM doctor ORDER BY docid DESC";
}
$list11 = $database->query("SELECT docname, docemail FROM doctor;");
$result = $database->query($sqlmain);
?>

<div class="dash-body">
    <table border="0" width="100%" style="border-spacing: 0;margin:0;padding:0;margin-top:25px;">
        <tr>
            <td width="13%">
                <a href="javascript:history.back()"><button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px"><font class="tn-in-text">Назад</font></button></a>
            </td>
            <td>
                <form action="" method="post" class="header-search">
                    <input type="search" name="search" class="input-text header-searchbar" placeholder="Поиск по имени врача или электронной почте" list="doctors">&nbsp;&nbsp;
                    <datalist id="doctors">
                        <?php while ($row00 = $list11->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($row00['docname']); ?>">
                            <option value="<?php echo htmlspecialchars($row00['docemail']); ?>">
                        <?php endwhile; ?>
                    </datalist>
                    <input type="Submit" value="Поиск" class="login-btn btn-primary btn" style="padding-left: 25px;padding-right: 25px;padding-top: 10px;padding-bottom: 10px;">
                </form>
            </td>
            <td width="15%">
                <p style="font-size: 14px;color: rgb(119, 119, 119);padding: 0;margin: 0;text-align: right;">Сегодняшняя дата</p>
                <p class="heading-sub12" style="padding: 0;margin: 0;">
                    <?php date_default_timezone_set('Asia/Yekaterinburg'); echo date('d-m-Y'); ?>
                </p>
            </td>
            <td width="10%">
                <button class="btn-label" style="display: flex;justify-content: center;align-items: center;"><img src="../img/calendar.svg" width="100%"></button>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="padding-top:30px;">
                <p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">Добавить нового врача</p>
            </td>
            <td colspan="2">
                <a href="?action=add&id=none&error=0" class="non-style-link"><button class="login-btn btn-primary btn button-icon" style="display: flex;justify-content: center;align-items: center;margin-left:75px;background-image: url('../img/icons/add.svg');">Добавить</button></a>
            </td>
        </tr>
        <tr>
            <td colspan="4" style="padding-top:10px;">
                <p class="heading-main12" style="margin-left: 45px;font-size:18px;color:rgb(49, 49, 49)">Все врачи (<?php echo $result ? $result->num_rows : 0; ?>)</p>
            </td>
        </tr>
        <tr>
            <td colspan="4">
                <center>
                    <div class="abc scroll">
                        <table width="93%" class="sub-table scrolldown" border="0">
                            <thead>
                                <tr>
                                    <th class="table-headin">Имя доктора</th>
                                    <th class="table-headin">Email</th>
                                    <th class="table-headin">Специальность</th>
                                    <th class="table-headin">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($result && $result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        $docid = $row["docid"];
                                        $name = $row["docname"];
                                        $email = $row["docemail"];
                                        // Получаем все специальности врача
                                        $spcil_res = $database->query("SELECT s.sname FROM doctor_specialty ds JOIN specialties s ON ds.specialty_id = s.id WHERE ds.docid = '{$row['docid']}'");
                                        $specialties = [];
                                        while ($spcil_row = $spcil_res->fetch_assoc()) {
                                            $specialties[] = $spcil_row["sname"];
                                        }
                                        $spcil_names = implode(', ', $specialties);
                                        echo '<tr>';
                                        echo '<td>&nbsp;' . htmlspecialchars($name) . '</td>';
                                        echo '<td>' . htmlspecialchars($email) . '</td>';
                                        echo '<td>' . htmlspecialchars($spcil_names) . '</td>';                                       
                                        $doctor_phone_query = $database->query("SELECT doctel FROM doctor WHERE docid = '{$row['docid']}'");
                                        $doctor_phone_row = $doctor_phone_query->fetch_assoc();
                                        $doctor_phone = $doctor_phone_row['doctel'] ?? 'Не указан';
                                        
                                        echo '<td><div style="display:flex;justify-content: center;">'
                                            . '<a href="?action=edit&id=' . $docid . '&error=0" class="non-style-link"><button class="btn-primary-soft btn button-icon btn-edit" style="padding-left: 40px;padding-top: 12px;padding-bottom: 12px;margin-top: 10px;"><font class="tn-in-text">Редактировать</font></button></a>'
                                            . '&nbsp;&nbsp;&nbsp;'                                            . '<div class="tooltip-container">'
                                            . '<button class="btn-primary-soft btn button-icon btn-view" style="padding-left: 40px;padding-top: 12px;padding-bottom: 12px;margin-top: 10px;"><font class="tn-in-text">Подробности</font></button>'
                                            . '<div class="doctor-tooltip position-top">'
                                            . '<div class="tooltip-header">Информация о враче</div>'
                                            . '<div class="tooltip-content">'
                                            . '<div class="tooltip-row tooltip-name"><span class="tooltip-label">Имя:</span><span class="tooltip-value">' . htmlspecialchars($name) . '</span></div>'
                                            . '<div class="tooltip-row tooltip-email"><span class="tooltip-label">Email:</span><span class="tooltip-value">' . htmlspecialchars($email) . '</span></div>'
                                            . '<div class="tooltip-row tooltip-phone"><span class="tooltip-label">Телефон:</span><span class="tooltip-value">' . htmlspecialchars($doctor_phone) . '</span></div>'
                                            . '<div class="tooltip-row tooltip-spec"><span class="tooltip-label">Специальность:</span><span class="tooltip-value">' . htmlspecialchars($spcil_names) . '</span></div>'
                                            . '</div>'
                                            . '</div>'
                                            . '</div>'
                                            . '&nbsp;&nbsp;&nbsp;'
                                            . '<a href="doctor_schedule_manager.php?docid=' . $docid . '" class="non-style-link"><button class="btn-primary btn button-icon btn-schedule" style="padding-left: 40px;padding-top: 12px;padding-bottom: 12px;margin-top: 10px;"><font class="tn-in-text">Расписание</font></button></a>'
                                            . '&nbsp;&nbsp;&nbsp;'
                                            . '<a href="?action=drop&id=' . $docid . '&name=' . urlencode($name) . '" class="non-style-link"><button class="btn-primary-soft btn button-icon btn-delete" style="padding-left: 40px;padding-top: 12px;padding-bottom: 12px;margin-top: 10px;"><font class="tn-in-text">Удалить</font></button></a>'
                                            . '</div></td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="4"><br><br><br><br><center><img src="../img/notfound.svg" width="25%"><br><p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">Мы не смогли найти ничего, связанного с вашими ключевыми словами!</p><a class="non-style-link" href="doctors.php"><button class="login-btn btn-primary-soft btn" style="display: flex;justify-content: center;align-items: center;margin-left:20px;">&nbsp; Показать всех врачей &nbsp;</button></a></center><br><br><br><br></td></tr>';
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
include_once dirname(__DIR__) . '/includes/init.php';
if (isset($_GET['action'])) {
    $id = $_GET['id'] ?? null;
    $action = $_GET['action'];
    $error_1 = $_GET["error"] ?? '0';
      if ($action === 'drop' && $id) {
        // Теперь используем форму для отправки POST-запроса вместо прямой ссылки
        echo '<div id="popup1" class="overlay" style="display: block;">
            <div class="popup">
            <center>
                <h2>Вы уверены?</h2>
                <a class="close" href="doctors.php">&times;</a>
                <div class="content">Вы хотите удалить эту запись?</div>
                <div style="display: flex;justify-content: center;">
                    <form method="POST" action="doctors.php">
                        <input type="hidden" name="delete_doctor" value="'.$id.'">
                        <button type="submit" class="btn-primary btn" style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;">
                            <font class="tn-in-text">&nbsp;Да&nbsp;</font>
                        </button>
                    </form>
                    <a href="doctors.php" class="non-style-link"><button class="btn-primary btn" style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;"><font class="tn-in-text">&nbsp;&nbsp;Нет&nbsp;&nbsp;</font></button></a>
                </div>
            </center>
            </div>
        </div>
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            const popup = document.getElementById("popup1");
            if (popup) {
                popup.style.display = "block";
                popup.style.visibility = "visible";
                popup.style.opacity = "1";
            }
        });
        </script>';
    } elseif ($action === 'edit' && $id) {
        // Показываем попап редактирования только если есть error != 4
        $error_1 = $_GET["error"] ?? '0';
        if ($error_1 != '4') {
            // Используем новую функцию для отображения специальностей с чекбоксами
            echo popup_edit_doctor_with_checkboxes($database, $id, $error_1);
        } else {
            echo '<div id="popup1" class="overlay"><div class="popup"><center><br><br><br><br><h2>Редактирование прошло успешно!</h2><a class="close" href="doctors.php">&times;</a><div class="content"></div><div style="display: flex;justify-content: center;"><a href="doctors.php" class="non-style-link"><button class="btn-primary btn" style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;"><font class="tn-in-text">&nbsp;&nbsp;OK&nbsp;&nbsp;</font></button></a></div><br><br></center></div></div>';
        }
    } elseif ($action === 'add' && $id === 'none') {
        // Показываем popup добавления нового врача
        if ($error_1 != '4') {
            // Используем новую функцию с чекбоксами для специальностей
            echo popup_add_doctor_with_checkboxes($database, $error_1);
        } else {
            echo popup_add_doctor_success();
        }
    }
}
?>

<?php include "footer.php"; ?>

<!-- Подключаем JS файл с системой tooltip -->
<script src="../js/tooltip.js"></script>
<script>
    // Инициализируем tooltip систему для текущей страницы
    // Параметры: селектор контейнера, селектор tooltip, селектор кнопки, режим отладки
    initTooltipSystem('.tooltip-container', '.doctor-tooltip', 'button', true);
    console.log('Doctors page: Tooltip system initialized');
</script>