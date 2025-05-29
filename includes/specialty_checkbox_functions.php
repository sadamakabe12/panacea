<?php
// Проверяем, если функция уже существует, чтобы избежать повторного объявления
if (!function_exists('popup_add_doctor_success')) {
    /**
     * Генерирует HTML-код всплывающего окна об успешном добавлении врача
     * 
     * @return string HTML-код всплывающего окна
     */
    function popup_add_doctor_success() {
        ob_start();
        echo '<div id="popup1" class="overlay">
            <div class="popup">
                <center>
                    <br><br><br><br>
                    <h2>Врач успешно добавлен!</h2>
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
        return ob_get_clean();
    }
}
/**
 * Генерирует HTML-код для выбора специальностей с использованием чекбоксов
 * 
 * @param object $database Объект подключения к БД
 * @param array $selected_ids Массив с ID выбранных специальностей (для редактирования)
 * @return string HTML-код интерфейса выбора специальностей с чекбоксами
 */
 
function generate_specialties_checkboxes($database, $selected_ids = []) {
    // Группы специальностей (категории)
    $categories = [
        'Терапевтические' => [1, 2, 4, 5, 6, 7, 13, 14, 15], // Аллергология, Кардиология, Эндокринология, Гастроэнтерология, Терапевт, Неврология, Психиатрия, Пульмонология, Урология
        'Хирургические' => [8, 10, 11, 12], // Акушерство и гинекология, Ортопедия, Педиатрия, Пластическая хирургия
        'Диагностические' => [3, 9] // Дерматология, Офтальмология
    ];
    
    // Получаем все специальности
    $specialties = [];
    $list = $database->query("SELECT * FROM specialties ORDER BY sname ASC");
    while ($row = $list->fetch_assoc()) {
        $specialties[$row['id']] = $row;
    }
    
    // Определяем, в какую категорию входит каждая специальность
    $categorized = [];
    $uncategorized = [];
    
    foreach ($specialties as $id => $specialty) {
        $assigned = false;
        
        foreach ($categories as $category_name => $category_ids) {
            if (in_array($id, $category_ids)) {
                $categorized[$category_name][$id] = $specialty;
                $assigned = true;
                break;
            }
        }
        
        if (!$assigned) {
            $uncategorized[$id] = $specialty;
        }
    }
    
    // Если есть некатегоризованные специальности, добавим их в категорию "Другие"
    if (!empty($uncategorized)) {
        $categorized['Другие'] = $uncategorized;
    }
    
    // Генерируем HTML
    $html = '<div class="specialties-container" style="max-height: 350px; overflow-y: auto; border: 1px solid #e0e0e0; border-radius: 6px; padding: 10px; background: #fafbfc;">';
    
    // Добавляем стили для контейнера специальностей
    $html .= '
    <style>
        .specialty-group {
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .specialty-group-title {
            font-weight: 600;
            color: var(--primarycolor);
            margin-bottom: 8px;
            font-size: 14px;
        }
        .specialty-items {
            display: flex;
            flex-wrap: wrap;
        }
        .specialty-item {
            width: 50%;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
        }
        .specialty-checkbox {
            margin-right: 8px;
        }
        .specialty-label {
            font-size: 13px;
            cursor: pointer;
        }
        .specialty-select-all {
            font-size: 12px;
            margin-left: 10px;
            color: #666;
            cursor: pointer;
            text-decoration: underline;
        }
    </style>';
    
    // JavaScript для выбора всех специальностей в группе
    $html .= '
    <script>
        function selectAllInGroup(groupName) {
            const checkboxes = document.querySelectorAll(\'input[data-group="\' + groupName + \'"]\');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = !allChecked;
            });
        }
    </script>';
    
    // Генерируем разделы для каждой категории
    foreach ($categorized as $category_name => $specs) {
        $html .= '<div class="specialty-group">';
        $html .= '<div class="specialty-group-title">' . htmlspecialchars($category_name);
        $html .= '<div class="specialty-items">';
        
        foreach ($specs as $id => $specialty) {
            $checked = in_array($id, $selected_ids) ? 'checked' : '';
            $html .= '<div class="specialty-item">';
            $html .= '<input type="checkbox" name="spec[]" value="' . $id . '" id="spec_' . $id . '" ' . $checked . ' class="specialty-checkbox" data-group="' . htmlspecialchars($category_name) . '">';
            $html .= '<label for="spec_' . $id . '" class="specialty-label">' . htmlspecialchars($specialty['sname']) . '</label>';
            $html .= '</div>';
        }
        
        $html .= '</div>'; // закрываем specialty-items
        $html .= '</div>'; // закрываем specialty-group
    }
    
    $html .= '</div>'; // закрываем specialties-container
    $html .= '<small style="color:#888; display:block; margin-top: 5px;">Выберите одну или несколько специальностей</small><br>';
    
    return $html;
}


/**
 * Модифицированная функция для добавления врача с использованием нового интерфейса выбора специальностей
 * 
 * @param object $database Объект подключения к БД
 * @param string $error_1 Код ошибки
 * @return string HTML-код всплывающего окна
 */
function popup_add_doctor_with_checkboxes($database, $error_1 = '0') {
    $errorlist = [
        '1'=>'<label class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Аккаунт с данной электронной почтой уже существует.</label>',
        '2'=>'<label class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Ошибка подтверждения пароля!</label>',
        '3'=>'', '4'=>'', '0'=>''
    ];
    
    // Формируем HTML для выбора специальностей с чекбоксами
    $specialty_html = generate_specialties_checkboxes($database);
    
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
                                    <p style="padding: 0;margin: 0;text-align: left;font-size: 25px;font-weight: 500;">Добавить нового врача</p><br><br>
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
                                    '.$specialty_html.'
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
 * Генерирует HTML для редактирования врача с чекбоксами для специальностей
 * 
 * @param object $database Объект подключения к БД
 * @param int $id ID врача
 * @param string $error_1 Код ошибки
 * @return string HTML-код всплывающего окна
 */
function popup_edit_doctor_with_checkboxes($database, $id, $error_1 = '0') {
    $errorlist = [
        '1'=>'<label for="promter" class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Аккаунт с данной электронной почтой уже существует.</label>',
        '2'=>'<label for="promter" class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Ошибка подтверждения пароля!</label>',
        '3'=>'<label for="promter" class="form-label" style="color:rgb(255, 62, 62);text-align:center;"></label>',
        '4'=>"",
        '0'=>'',
    ];
    
    // Получаем данные врача
    $sqlmain = "SELECT * FROM doctor WHERE docid='$id'";
    $result = $database->query($sqlmain);
    $row = $result->fetch_assoc();
    $name = $row["docname"];
    $email = $row["docemail"];
    $tele = $row['doctel'];
    
    // Получаем все id специальностей врача
    $doctor_specialties = [];
    $spcil_res = $database->query("SELECT specialty_id FROM doctor_specialty WHERE docid = '$id'");
    while ($spcil_row = $spcil_res->fetch_assoc()) {
        $doctor_specialties[] = $spcil_row["specialty_id"];
    }
      // Генерируем HTML для выбора специальностей с чекбоксами
    $specialty_html = generate_specialties_checkboxes($database, $doctor_specialties);
    
    ob_start();
    echo '<div id="popup1" class="overlay">
        <div class="popup">
        <center>
            <a class="close" href="doctors.php">&times;</a>
            <div style="display: flex;justify-content: center;"><div class="abc">
            <table width="80%" class="sub-table scrolldown add-doc-form-container" border="0">
                <tr><td class="label-td" colspan="2">' . $errorlist[$error_1] . '</td></tr>
                <tr><td><p style="padding: 0;margin: 0;text-align: left;font-size: 25px;font-weight: 500;">Редактировать данные докторского аккаунта</p>Доктор ID : ' . $id . ' <br><br></td></tr>
                <tr><td class="label-td" colspan="2"><form action="edit-doc.php" method="POST" class="add-new-form"><label for="Email" class="form-label">Email: </label><input type="hidden" value="' . $id . '" name="id00"><input type="hidden" name="oldemail" value="' . htmlspecialchars($email) . '" ></td></tr>
                <tr><td class="label-td" colspan="2"><input type="email" name="email" class="input-text" placeholder="Электронная почта" value="' . htmlspecialchars($email) . '" required><br></td></tr>
                <tr><td class="label-td" colspan="2"><label for="name" class="form-label">Имя: </label></td></tr>
                <tr><td class="label-td" colspan="2"><input type="text" name="name" class="input-text" placeholder="Имя доктора" value="' . htmlspecialchars($name) . '" required><br></td></tr>
                <tr><td class="label-td" colspan="2"><label for="Tele" class="form-label">Номер телефона: </label></td></tr>
                <tr><td class="label-td" colspan="2"><input type="tel" name="Tele" class="input-text" placeholder="Номер телефона" value="' . htmlspecialchars($tele) . '" required><br></td></tr>
                <tr><td class="label-td" colspan="2"><label for="spec" class="form-label">Выберите специальность</label></td></tr>
                <tr><td class="label-td" colspan="2">'.$specialty_html.'</td></tr>
                <tr><td class="label-td" colspan="2"><label for="password" class="form-label">Пароль: </label></td></tr>
                <tr><td class="label-td" colspan="2"><input type="password" name="password" class="input-text" placeholder="Введите пароль"><br><small style="color:#888;">Оставьте пустым, чтобы сохранить текущий пароль</small><br></td></tr>
                <tr><td class="label-td" colspan="2"><label for="cpassword" class="form-label">Подтвердите пароль: </label></td></tr>
                <tr><td class="label-td" colspan="2"><input type="password" name="cpassword" class="input-text" placeholder="Подтвердите пароль"><br></td></tr>
                <tr><td colspan="2"><input type="reset" value="Сбросить" class="login-btn btn-primary-soft btn">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" value="Сохранить" class="login-btn btn-primary btn"></td></tr>
                </form></td></tr>
            </table></div></div>
        </center>
        <br><br></div>
    </div>';
    return ob_get_clean();
}
