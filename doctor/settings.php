<?php
$pageTitle = 'Настройки';
include "header-doctor.php";

// Получаем данные пользователя
if (!isset($userid) || !isset($username)) {
    $doctorInfo = getDoctorExtendedInfo($database, $useremail);
    $userid = $doctorInfo['docid'];
    $username = $doctorInfo['docname'];
}

// Обработка действий
$action = $_GET['action'] ?? '';
$id = intval($_GET['id'] ?? 0);
$error = $_GET['error'] ?? '0';

// Массив ошибок
$errorMessages = [
    '1' => 'Аккаунт с данной электронной почтой уже существует.',
    '2' => 'Ошибка подтверждения пароля!',
    '3' => 'Произошла ошибка при обновлении данных.',
    '4' => 'success'
];

// Функция для создания модального окна
function renderSettingsModal($title, $content, $modalId = 'popup1') {
    return "
    <div id=\"{$modalId}\" class=\"overlay\">
        <div class=\"popup\">
            <center>
                <h2>{$title}</h2>
                <a class=\"close\" href=\"settings.php\">&times;</a>
                <div class=\"content\">{$content}</div>
                <br><br>
            </center>
        </div>
    </div>
    <script>document.getElementById(\"{$modalId}\").style.display=\"block\";</script>";
}

// Функция для отображения формы редактирования
function renderEditForm($doctorData, $id, $error, $errorMessages) {
    $errorMsg = ($error !== '0' && $error !== '4') ? 
        "<div class=\"alert alert-error\">{$errorMessages[$error]}</div>" : '';
    
    return "
    <div style=\"display: flex;justify-content: center;\">
        <div class=\"abc\">
            <table width=\"80%\" class=\"sub-table scrolldown add-doc-form-container\" border=\"0\">
                <tr><td class=\"label-td\" colspan=\"2\">{$errorMsg}</td></tr>
                <tr>
                    <td>
                        <p style=\"padding: 0;margin: 0;text-align: left;font-size: 25px;font-weight: 500;\">
                            Редактировать данные аккаунта
                        </p>
                        Доктор ID : {$id} <br><br>
                    </td>
                </tr>
                <tr>
                    <td class=\"label-td\" colspan=\"2\">
                        <form action=\"edit-doc.php\" method=\"POST\" class=\"add-new-form\">
                            <input type=\"hidden\" value=\"{$id}\" name=\"id00\">
                            <input type=\"hidden\" name=\"oldemail\" value=\"" . htmlspecialchars($doctorData['docemail']) . "\">
                            
                            <label for=\"Email\" class=\"form-label\">Email:</label><br>
                            <input type=\"email\" name=\"email\" class=\"input-text\" 
                                   value=\"" . htmlspecialchars($doctorData['docemail']) . "\" required><br><br>
                            
                            <label for=\"name\" class=\"form-label\">Имя:</label><br>
                            <input type=\"text\" name=\"name\" class=\"input-text\" 
                                   value=\"" . htmlspecialchars($doctorData['docname']) . "\" 
                                   readonly style=\"background:#f5f5f5;color:#888;cursor:not-allowed;\"><br><br>
                            
                            <label for=\"Tele\" class=\"form-label\">Номер телефона:</label><br>
                            <input type=\"tel\" name=\"Tele\" class=\"input-text\" 
                                   value=\"" . htmlspecialchars($doctorData['doctel']) . "\" required><br><br>
                            
                            <label for=\"password\" class=\"form-label\">Пароль:</label><br>
                            <input type=\"password\" name=\"password\" class=\"input-text\" 
                                   placeholder=\"Сменить пароль\" required><br><br>
                            
                            <label for=\"cpassword\" class=\"form-label\">Подтвердите пароль:</label><br>
                            <input type=\"password\" name=\"cpassword\" class=\"input-text\" 
                                   placeholder=\"Подтвердите пароль\" required><br><br>
                            
                            <input type=\"reset\" value=\"Сбросить\" class=\"login-btn btn-primary-soft btn\">
                            <input type=\"submit\" value=\"Сохранить\" class=\"login-btn btn-primary btn\">
                        </form>
                    </td>
                </tr>
            </table>
        </div>
    </div>";
}

// Функция для отображения данных врача
function renderDoctorView($doctorData) {
    return "
    <div style=\"display: flex;justify-content: center;\">
        <table width=\"80%\" class=\"sub-table scrolldown add-doc-form-container\" border=\"0\">
            <tr>
                <td>
                    <p class=\"h1-dashboard\" style=\"padding: 0;margin: 0;text-align: left;font-size: 25px;font-weight: 600;\">
                        Просмотр деталей
                    </p><br><br>
                </td>
            </tr>
            <tr>
                <td class=\"label-td\" colspan=\"2\">
                    <label class=\"form-label\">Имя:</label><br>
                    " . htmlspecialchars($doctorData['docname'] ?: 'Не указано') . "<br><br>
                </td>
            </tr>
            <tr>
                <td class=\"label-td\" colspan=\"2\">
                    <label class=\"form-label\">Email:</label><br>
                    " . htmlspecialchars($doctorData['docemail'] ?: 'Не указано') . "<br><br>
                </td>
            </tr>
            <tr>
                <td class=\"label-td\" colspan=\"2\">
                    <label class=\"form-label\">Номер телефона:</label><br>
                    " . htmlspecialchars($doctorData['doctel'] ?: 'Не указано') . "<br><br>
                </td>
            </tr>
            <tr>
                <td class=\"label-td\" colspan=\"2\">
                    <label class=\"form-label\">Статус:</label><br>
                    <span style=\"color:#4CAF50\">Активный врач</span><br><br>
                </td>
            </tr>
            <tr>
                <td colspan=\"2\">
                    <a href=\"settings.php\">
                        <input type=\"button\" value=\"OK\" class=\"login-btn btn-primary-soft btn\">
                    </a>
                </td>
            </tr>
        </table>
    </div>";
}

echo renderDoctorPageHeader('Настройки', true, 'javascript:history.back()');
?>

<div class="dash-body" style="margin-top: 15px">
    <div style="max-width: 800px; margin: 0 auto; padding: 20px;">
        <div class="settings-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 30px;">
            
            <!-- Кнопка редактирования аккаунта -->
            <div class="settings-card" style="background: #fff; border-radius: 10px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-left: 4px solid #2196F3;">
                <div style="display: flex; align-items: center; margin-bottom: 15px;">
                    <img src="../img/icons/doctors-hover.svg" alt="Edit Account" style="width: 40px; height: 40px; margin-right: 15px;">
                    <div>
                        <h3 style="margin: 0; color: #333; font-size: 18px;">Настройки аккаунта</h3>
                        <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;">Редактировать данные аккаунта и изменить пароль</p>
                    </div>
                </div>
                <a href="?action=edit&id=<?php echo $userid; ?>&error=0" style="text-decoration: none;">
                    <button class="btn btn-primary" style="width: 100%; padding: 12px; background: #2196F3; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px;">
                        Редактировать аккаунт
                    </button>
                </a>
            </div>
            
            <!-- Кнопка просмотра данных -->
            <div class="settings-card" style="background: #fff; border-radius: 10px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-left: 4px solid #4CAF50;">
                <div style="display: flex; align-items: center; margin-bottom: 15px;">
                    <img src="../img/icons/view-iceblue.svg" alt="View Account" style="width: 40px; height: 40px; margin-right: 15px;">
                    <div>
                        <h3 style="margin: 0; color: #333; font-size: 18px;">Просмотр данных аккаунта</h3>
                        <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;">Просмотр личной информации о вашем аккаунте</p>
                    </div>
                </div>
                <a href="?action=view&id=<?php echo $userid; ?>" style="text-decoration: none;">
                    <button class="btn btn-success" style="width: 100%; padding: 12px; background: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px;">
                        Просмотреть данные
                    </button>
                </a>
            </div>
            
            <!-- Кнопка удаления аккаунта -->
            <div class="settings-card" style="background: #fff; border-radius: 10px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-left: 4px solid #f44336;">
                <div style="display: flex; align-items: center; margin-bottom: 15px;">
                    <img src="../img/icons/patients-hover.svg" alt="Delete Account" style="width: 40px; height: 40px; margin-right: 15px;">
                    <div>
                        <h3 style="margin: 0; color: #333; font-size: 18px;">Удалить аккаунт</h3>
                        <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;">Будет навсегда удален ваш аккаунт</p>
                    </div>
                </div>
                <a href="?action=drop&id=<?php echo $userid; ?>&name=<?php echo urlencode($username); ?>" style="text-decoration: none;">
                    <button class="btn btn-danger" style="width: 100%; padding: 12px; background: #f44336; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px;">
                        Удалить аккаунт
                    </button>
                </a>
            </div>
        </div>
    </div>
</div>

<?php
// Обработка действий
if ($action && $id) {
    switch ($action) {
        case 'drop':
            $nameget = $_GET["name"] ?? '';
            $content = "
                <h2>Вы уверены?</h2>
                <p>Вы хотите удалить эту запись?</p>
                <div style=\"display: flex;justify-content: center; gap: 10px; margin-top: 20px;\">
                    <a href=\"delete-doctor.php?id={$id}\" class=\"non-style-link\">
                        <button class=\"btn-primary btn\" style=\"padding:10px 20px;\">Да</button>
                    </a>
                    <a href=\"settings.php\" class=\"non-style-link\">
                        <button class=\"btn-primary-soft btn\" style=\"padding:10px 20px;\">Нет</button>
                    </a>
                </div>";
            echo renderSettingsModal('Подтверждение удаления', $content);
            break;
            
        case 'view':
            $doctorData = getDoctorExtendedInfo($database, null, $id);
            if ($doctorData) {
                $content = renderDoctorView($doctorData);
                echo renderSettingsModal('Информация о враче', $content);
            }
            break;
            
        case 'edit':
            $doctorData = getDoctorExtendedInfo($database, null, $id);
            if ($error === '4') {
                $content = "
                <center>
                    <br><br><br><br>
                    <h2>Редактирование прошло успешно!</h2>
                    <div class=\"content\">Если вы изменили email, пожалуйста, выйдите и войдите снова с новым email</div>
                    <div style=\"display: flex;justify-content: center; gap: 10px; margin-top: 20px;\">
                        <a href=\"settings.php\" class=\"non-style-link\">
                            <button class=\"btn-primary btn\" style=\"padding:10px 20px;\">OK</button>
                        </a>
                        <a href=\"../logout.php\" class=\"non-style-link\">
                            <button class=\"btn-primary-soft btn\" style=\"padding:10px 20px;\">Выйти</button>
                        </a>
                    </div>                    <br><br>
                </center>";
                echo renderSettingsModal('Успех', $content);
            } else {
                $content = renderEditForm($doctorData, $id, $error, $errorMessages);
                echo renderSettingsModal('Редактировать аккаунт', $content);
            }
            break;
    }
}

include "footer.php";
?>
