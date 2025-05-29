<?php
/**
 * Функции для всплывающих окон
 */

/**
 * Генерирует всплывающее окно для просмотра деталей записи
 * @param object $database Объект подключения к БД
 * @param int $id ID записи в расписании
 * @return string HTML-код всплывающего окна
 */
function popup_view($database, $id) {
    $id = intval($id);
    ob_start();
    $res = $database->query("SELECT s.scheduleid, s.docid, s.scheduledate, s.scheduletime, d.docname, d.docid
    FROM schedule s
    INNER JOIN doctor d ON s.docid = d.docid
    WHERE s.scheduleid = $id;");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $docname = htmlspecialchars($row['docname']);
        $scheduleid = $row['scheduleid'];
        $scheduledate = $row['scheduledate'];
        $scheduletime = substr($row['scheduletime'], 0, 5);
        
        $formatted_date = date('d.m.Y', strtotime($scheduledate));
        
        echo '
        <div id="popup1" class="overlay">
            <div class="popup">
                <center>
                    <h2>Детали записи</h2>
                    <a class="close" href="schedule.php">&times;</a>
                    <div class="content">
                        <table width="80%" class="sub-table scrolldown" border="0">
                            <tr>
                                <td><b>ID:</b></td>
                                <td>'.$scheduleid.'</td>
                            </tr>
                            <tr>
                                <td><b>Врач:</b></td>
                                <td>'.$docname.'</td>
                            </tr>
                            <tr>
                                <td><b>Дата:</b></td>
                                <td>'.$formatted_date.'</td>
                            </tr>
                            <tr>
                                <td><b>Время:</b></td>
                                <td>'.$scheduletime.'</td>
                            </tr>
                        </table>
                    </div>
                    <div style="display: flex;justify-content: center;">
                        <a href="schedule.php" class="non-style-link">
                            <button class="btn-primary btn" style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;">
                                <font class="tn-in-text">&nbsp;&nbsp;OK&nbsp;&nbsp;</font>
                            </button>
                        </a>
                    </div>
                </center>
            </div>
        </div>
        ';
    } else {
        echo '<div id="popup1" class="overlay">
            <div class="popup">
                <center>
                    <h2>Ошибка</h2>
                    <a class="close" href="schedule.php">&times;</a>
                    <div class="content">
                        Запись не найдена
                    </div>
                    <div style="display: flex;justify-content: center;">
                        <a href="schedule.php" class="non-style-link">
                            <button class="btn-primary btn" style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;">
                                <font class="tn-in-text">&nbsp;&nbsp;OK&nbsp;&nbsp;</font>
                            </button>
                        </a>
                    </div>
                </center>
            </div>
        </div>';
    }
    return ob_get_clean();
}

/**
 * Генерирует всплывающее окно для подтверждения удаления записи
 * @param int $id ID записи для удаления
 * @return string HTML-код всплывающего окна
 */
function popup_drop($id) {
    return '<div id="popup1" class="overlay">
        <div class="popup">
            <center>
                <h2>Вы уверены?</h2>
                <div class="content">
                    Вы действительно хотите удалить эту запись в расписании?<br><br>
                    <div style="display: flex;justify-content: center;">
                        <form method="POST" action="schedule.php">
                            <input type="hidden" name="delete_session" value="'.$id.'">
                            <button type="submit" class="btn-primary btn" style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;">
                                <font class="tn-in-text">&nbsp;Да&nbsp;</font>
                            </button>
                        </form>
                        <a href="schedule.php" class="non-style-link">
                            <button class="btn-primary btn" style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;">
                                <font class="tn-in-text">&nbsp;&nbsp;Нет&nbsp;&nbsp;</font>
                            </button>
                        </a>
                    </div>
                </div>
            </center>
        </div>
    </div>';
}

/**
 * Генерирует всплывающее окно для подтверждения успешного добавления записи
 * @return string HTML-код всплывающего окна
 */
function popup_session_added() {
    return '<div id="popup1" class="overlay">
        <div class="popup">
            <center>
                <br><br>
                <h2>Запись успешно добавлена!</h2>
                <a class="close" href="schedule.php">&times;</a>
                <div class="content"></div>
                <div style="display: flex;justify-content: center;">
                    <a href="schedule.php" class="non-style-link">
                        <button class="btn-primary btn" style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;">
                            <font class="tn-in-text">&nbsp;&nbsp;OK&nbsp;&nbsp;</font>
                        </button>
                    </a>
                </div>
                <br><br>
            </center>
        </div>
    </div>';
}

/**
 * Генерирует всплывающее окно для просмотра информации о пациенте
 * @param object $database Объект подключения к БД
 * @param int $id ID пациента
 * @return string HTML-код всплывающего окна
 */
function popup_view_patient($database, $id) {
    // Код функции можно перенести из patient.php
    $id = intval($id);
    ob_start();
    $res = $database->query("SELECT * FROM patient WHERE pid=$id");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $pid = $row["pid"];
        $name = htmlspecialchars($row["pname"]);
        $email = htmlspecialchars($row["pemail"]);
        $phone = htmlspecialchars($row["ptel"]);
        $dob = htmlspecialchars($row["pdob"]);
        $address = htmlspecialchars($row["paddress"]);
        echo '
        <div id="popup1" class="overlay">
            <div class="popup">
                <center>
                    <h2>Информация о пациенте</h2>
                    <a class="close" href="patient.php">&times;</a>
                    <div class="content">
                        <table width="80%" class="sub-table scrolldown" border="0">
                            <tr>
                                <td><b>ID:</b></td>
                                <td>'.$pid.'</td>
                            </tr>
                            <tr>
                                <td><b>ФИО:</b></td>
                                <td>'.$name.'</td>
                            </tr>
                            <tr>
                                <td><b>Email:</b></td>
                                <td>'.$email.'</td>
                            </tr>
                            <tr>
                                <td><b>Телефон:</b></td>
                                <td>'.$phone.'</td>
                            </tr>
                            <tr>
                                <td><b>Дата рождения:</b></td>
                                <td>'.$dob.'</td>
                            </tr>
                            <tr>
                                <td><b>Адрес:</b></td>
                                <td>'.$address.'</td>
                            </tr>
                        </table>
                    </div>
                    <div style="display: flex;justify-content: center;">
                        <a href="patient.php" class="non-style-link">
                            <button class="btn-primary btn" style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;">
                                <font class="tn-in-text">&nbsp;&nbsp;OK&nbsp;&nbsp;</font>
                            </button>
                        </a>
                    </div>
                </center>
            </div>
        </div>
        ';
    } else {
        echo '<div id="popup1" class="overlay">
            <div class="popup">
                <center>
                    <h2>Ошибка</h2>
                    <a class="close" href="patient.php">&times;</a>
                    <div class="content">
                        Пациент не найден
                    </div>
                    <div style="display: flex;justify-content: center;">
                        <a href="patient.php" class="non-style-link">
                            <button class="btn-primary btn" style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;">
                                <font class="tn-in-text">&nbsp;&nbsp;OK&nbsp;&nbsp;</font>
                            </button>
                        </a>
                    </div>
                </center>
            </div>
        </div>';
    }
    return ob_get_clean();
}

/**
 * Генерирует всплывающее окно для добавления нового пациента
 * @param string $error_1 Код ошибки (0 - нет ошибки, 1 - email существует, 2 - ошибка пароля)
 * @return string HTML-код всплывающего окна
 */
function popup_add_patient($error_1 = '0') {
    $errorlist = [
        '1'=>'<label class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Аккаунт с данной электронной почтой уже существует.</label>',
        '2'=>'<label class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Ошибка подтверждения пароля!</label>',
        '3'=>'', '4'=>'', '0'=>''
    ];
    ob_start();
    echo '<div id="popup1" class="overlay"><div class="popup"><center><a class="close" href="patient.php">&times;</a><div style="display: flex;justify-content: center;"><div class="abc"><table width="80%" class="sub-table scrolldown add-doc-form-container" border="0"><tr><td class="label-td" colspan="2">'.$errorlist[$error_1].'</td></tr><tr><td><p style="padding: 0;margin: 0;text-align: left;font-size: 25px;font-weight: 500;">Добавить нового пациента.</p><br><br></td></tr><tr><form action="patient.php" method="POST" class="add-new-form"><input type="hidden" name="add_patient" value="1"><td class="label-td" colspan="2"><label class="form-label">Имя: </label></td></tr><tr><td class="label-td" colspan="2"><input type="text" name="name" class="input-text" placeholder="Имя пациента" required><br></td></tr><tr><td class="label-td" colspan="2"><label class="form-label">Email: </label></td></tr><tr><td class="label-td" colspan="2"><input type="email" name="email" class="input-text" placeholder="Электронная почта" required><br></td></tr><tr><td class="label-td" colspan="2"><label class="form-label">Номер телефона: </label></td></tr><tr><td class="label-td" colspan="2"><input type="tel" name="tel" class="input-text" placeholder="Номер телефона" required><br></td></tr><tr><td class="label-td" colspan="2"><label class="form-label">Дата рождения: </label></td></tr><tr><td class="label-td" colspan="2"><input type="date" name="dob" class="input-text" required></td></tr><tr><td class="label-td" colspan="2"><label class="form-label">Адрес: </label></td></tr><tr><td class="label-td" colspan="2"><input type="text" name="address" class="input-text" placeholder="Адрес пациента"><br></td></tr><tr><td class="label-td" colspan="2"><label class="form-label">Пароль: </label></td></tr><tr><td class="label-td" colspan="2"><input type="password" name="password" class="input-text" placeholder="Введите пароль" required><br></td></tr><tr><td class="label-td" colspan="2"><label class="form-label">Подтвердите пароль: </label></td></tr><tr><td class="label-td" colspan="2"><input type="password" name="cpassword" class="input-text" placeholder="Подтвердите пароль" required><br></td></tr><tr><td colspan="2"><input type="reset" value="Сбросить" class="login-btn btn-primary-soft btn">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" value="Добавить" class="login-btn btn-primary btn"></td></tr></form></table></div></div></center><br><br></div></div>';
    return ob_get_clean();
}

/**
 * Генерирует всплывающее окно для успешного добавления пациента
 * @return string HTML-код всплывающего окна
 */
function popup_add_success() {
    return '<div id="popup1" class="overlay"><div class="popup"><center><br><br><br><br><h2>Новый пациент успешно добавлен!</h2><a class="close" href="patient.php">&times;</a><div class="content"></div><div style="display: flex;justify-content: center;"><a href="patient.php" class="non-style-link"><button class="btn-primary btn" style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;"><font class="tn-in-text">&nbsp;&nbsp;OK&nbsp;&nbsp;</font></button></a></div><br><br></center></div></div>';
}

/**
 * Генерирует всплывающее окно для добавления нового врача
 * @param object $database Объект подключения к БД
 * @param string $error_1 Код ошибки (0 - нет ошибки, 1 - email существует, 2 - ошибка пароля)
 * @return string HTML-код всплывающего окна
 */
function popup_add_doctor($database, $error_1 = '0') {
    $errorlist = [
        '1'=>'<label class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Аккаунт с данной электронной почтой уже существует.</label>',
        '2'=>'<label class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Ошибка подтверждения пароля!</label>',
        '3'=>'', '4'=>'', '0'=>''
    ];
    
    // Формируем HTML для выбора специальностей заранее
    $specialty_select_html = '<div style="max-height:320px;min-height:220px;width:100%;overflow-y:auto;border:1px solid #e0e0e0;border-radius:6px;padding:6px 0 6px 6px;background:#fafbfc;">';
    $specialty_select_html .= '<select name="spec[]" id="spec" class="box" multiple size="14" style="width:100%;border:none;background:transparent;min-height:200px;">';
    $list11 = $database->query("SELECT * FROM specialties ORDER BY sname ASC;");
    while ($row00 = $list11->fetch_assoc()) {
        $sn = htmlspecialchars($row00["sname"]);
        $id00 = $row00["id"];
        $specialty_select_html .= "<option value=\"$id00\">$sn</option>";
    }
    $specialty_select_html .= '</select></div>';
    $specialty_select_html .= '<small style="color:#888;">Выберите одну или несколько специальностей (Ctrl/Cmd для множественного выбора)</small><br><br>';
    
    ob_start();
    echo '<div id="popup1" class="overlay">
        <div class="popup">
            <center>
                <a class="close" href="doctors.php">&times;</a>
                <div style="display: flex;justify-content: center;">
                    <div class="abc">
                        <table width="80%" class="sub-table scrolldown add-doc-form-container" border="0">
                            <tr>
                                <td class="label-td" colspan="2">'.$errorlist[$error_1].'</td>
                            </tr>
                            <tr>
                                <td>
                                    <p style="padding: 0;margin: 0;text-align: left;font-size: 25px;font-weight: 500;">Добавить нового врача.</p><br><br>
                                </td>
                            </tr>
                            <tr>
                                <form action="doctors.php" method="POST" class="add-new-form">
                                    <input type="hidden" name="add_doctor" value="1">
                                    <td class="label-td" colspan="2">
                                        <label class="form-label">Имя: </label>
                                    </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <input type="text" name="name" class="input-text" placeholder="Имя врача" required><br>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <label class="form-label">Email: </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <input type="email" name="email" class="input-text" placeholder="Электронная почта" required><br>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <label class="form-label">Номер телефона: </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <input type="tel" name="Tele" class="input-text" placeholder="Номер телефона" required><br>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <label class="form-label">Выберите специальность</label>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    '.$specialty_select_html.'
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <label class="form-label">Пароль: </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <input type="password" name="password" class="input-text" placeholder="Введите пароль" required><br>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <label class="form-label">Подтвердите пароль: </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <input type="password" name="cpassword" class="input-text" placeholder="Подтвердите пароль" required><br>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <input type="reset" value="Сбросить" class="login-btn btn-primary-soft btn">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    <input type="submit" value="Добавить" class="login-btn btn-primary btn">
                                </td>
                            </tr>
                                </form>
                        </table>
                    </div>
                </div>
            </center><br><br>
        </div>
    </div>';
    return ob_get_clean();
}

/**
 * Генерирует всплывающее окно для успешного добавления врача
 * @return string HTML-код всплывающего окна
 */
function popup_add_doctor_success() {
    return '<div id="popup1" class="overlay">
        <div class="popup">
            <center><br><br><br><br>
                <h2>Новый врач успешно добавлен!</h2>
                <a class="close" href="doctors.php">&times;</a>
                <div class="content"></div>
                <div style="display: flex;justify-content: center;">
                    <a href="doctors.php" class="non-style-link">
                        <button class="btn-primary btn" style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;">
                            <font class="tn-in-text">&nbsp;&nbsp;OK&nbsp;&nbsp;</font>
                        </button>
                    </a>
                </div>
                <br><br>
            </center>
        </div>
    </div>';
}
