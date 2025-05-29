<?php 
include "header.php";
require_once "../includes/patient_functions.php";

// Обработка запроса на обновление данных пользователя 
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST["update_patient"])) {
    $id = $_POST['id00'];
    $name = $_POST['name'];
    $nic = $_POST['nic'] ?? '';
    $oldemail = $_POST["oldemail"];
    $address = $_POST['address'] ?? '';
    $email = $_POST['email'];
    $tele = $_POST['Tele'];
    $password = $_POST['password'];
    $cpassword = $_POST['cpassword'];
    
    // Проверяем совпадение паролей
    if ($password == $cpassword) {
        // Используем централизованную функцию update_patient для обновления данных
        $error = update_patient($database, $id, $name, $email, $oldemail, $tele, $address, $password);
        header("location: settings.php?action=edit&error=".$error."&id=".$id);
        exit;
    } else {
        $error = '2'; // Пароли не совпадают
        header("location: settings.php?action=edit&error=".$error."&id=".$id);
        exit;
    }
}

// Обработка запроса на обновление предпочтений уведомлений
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST["update_notifications"])) {
    $patient_id = intval($_POST['patient_id']);
    $notification_preference = $_POST['notification_preference'];
    
    // Проверяем что пациент обновляет свои настройки
    if ($patient_id == $userid) {
        require_once "../includes/notification_functions.php";
        
        // Сохраняем предпочтения
        if (save_patient_notification_preference($database, $patient_id, $notification_preference)) {
            header("location: settings.php?action=notifications&success=1");
        } else {
            header("location: settings.php?action=notifications&error=1");
        }
    } else {
        header("location: ../logout.php");
    }
    exit;
}

// Обработка запроса на удаление аккаунта
if (isset($_GET["action"]) && $_GET["action"] == "delete_confirm" && isset($_GET["id"])) {
    $id = intval($_GET["id"]);
    
    // Проверяем, что пациент удаляет свой аккаунт
    if ($id != $userid) {
        header("location: ../logout.php");
        exit;
    }
    
    // Используем централизованную функцию для удаления аккаунта пациента
    if (delete_patient_account($database, $id)) {
        // Удаляем сессию и перенаправляем на главную
        session_destroy();
        header("location: ../index.html");
    } else {
        // Ошибка при удалении
        header("location: settings.php?action=delete-failed");
    }
    exit;
}
?>
<tr>
    <td colspan="4">
        <center>
        <table class="filter-container" style="border: none;" border="0">
            <tr>
                <td colspan="4">
                    <p style="font-size: 20px">&nbsp;</p>
                </td>
            </tr>
            <tr>
                <td style="width: 25%;">
                    <a href="?action=edit&id=<?php echo $userid ?>&error=0" class="non-style-link">
                    <div  class="dashboard-items setting-tabs"  style="padding:20px;margin:auto;width:95%;display: flex">
                        <div class="btn-icon-back dashboard-icons-setting" style="background-image: url('../img/icons/doctors-hover.svg');"></div>
                        <div>
                                <div class="h1-dashboard">
                                    Настройки аккаунта  &nbsp;

                                </div><br>
                                <div class="h3-dashboard" style="font-size: 15px;">
                                    Редактировать данные аккаунта и изменить пароль
                                </div>
                        </div>
                                
                    </div>
                    </a>
                </td>
            </tr>
            <tr>
                <td colspan="4">
                    <p style="font-size: 5px">&nbsp;</p>
                </td>
            </tr>
            <tr>
            <td style="width: 25%;">
                    <a href="?action=view&id=<?php echo $userid ?>" class="non-style-link">
                    <div  class="dashboard-items setting-tabs"  style="padding:20px;margin:auto;width:95%;display: flex;">
                        <div class="btn-icon-back dashboard-icons-setting " style="background-image: url('../img/icons/view-iceblue.svg');"></div>
                        <div>
                                <div class="h1-dashboard" >
                                    Просмотр данных аккаунта
                                    
                                </div><br>
                                <div class="h3-dashboard"  style="font-size: 15px;">
                                    Просмотр личной информации о вашем аккаунте
                                </div>
                        </div>
                                
                    </div>
                    </a>
                </td>
            </tr>
            <tr>
                <td colspan="4">
                    <p style="font-size: 5px">&nbsp;</p>
                </td>
            </tr>
            <tr>
            <td style="width: 25%;">
                    <a href="?action=drop&id=<?php echo $userid.'&name='.$username ?>" class="non-style-link">
                    <div  class="dashboard-items setting-tabs"  style="padding:20px;margin:auto;width:95%;display: flex;">
                        <div class="btn-icon-back dashboard-icons-setting" style="background-image: url('../img/icons/patients-hover.svg');"></div>
                        <div>
                                <div class="h1-dashboard" style="color: #ff5050;">
                                    Удалить аккаунт
                                    
                                </div><br>
                                <div class="h3-dashboard"  style="font-size: 15px;">
                                    Будет навсегда удален ваш аккаунт
                                </div>
                        </div>
                    </div>
                    </a>
                </td>
            </tr>
            <tr>
                <td colspan="4">
                    <p style="font-size: 5px">&nbsp;</p>
                </td>
            </tr>
            <tr>
            <td style="width: 25%;">
                    <a href="?action=notifications&id=<?php echo $userid ?>" class="non-style-link">
                    <div  class="dashboard-items setting-tabs"  style="padding:20px;margin:auto;width:95%;display: flex;">
                        <div class="btn-icon-back dashboard-icons-setting" style="background-image: url('../img/icons/notifications.svg');"></div>
                        <div>
                                <div class="h1-dashboard">
                                    Настройки уведомлений
                                    
                                </div><br>
                                <div class="h3-dashboard"  style="font-size: 15px;">
                                    Настройка уведомлений о приемах
                                </div>
                        </div>
                                
                    </div>
                    </a>
                </td>
            </tr>
        </table>
    </center>
    </td>
</tr>
<?php 
if($_GET){
    
    $id=$_GET["id"];
    $action=$_GET["action"];
    if($action=='drop'){
        $nameget=$_GET["name"];
        echo '
        <div id="popup1" class="overlay">
                <div class="popup">
                <center>
                    <h2>Вы уверены?</h2>
                    <a class="close" href="settings.php">&times;</a>
                    <div class="content">
                        Вы хотите удалить этот аккаунт?<br>('.substr($nameget,0,40).').
                        
                    </div>                    <div style="display: flex;justify-content: center;">
                    <a href="settings.php?action=delete_confirm&id='.$id.'" class="non-style-link"><button  class="btn-primary btn"  style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;"<font class="tn-in-text">&nbsp;Да&nbsp;</font></button></a>&nbsp;&nbsp;&nbsp;
                    <a href="settings.php" class="non-style-link"><button  class="btn-primary btn"  style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;"><font class="tn-in-text">&nbsp;&nbsp;Нет&nbsp;&nbsp;</font></button></a>

                    </div>
                </center>
        </div>
        </div>
        ';
    }elseif($action=='view'){
        $sqlmain= "select * from patient where pid=?";
        $stmt = $database->prepare($sqlmain);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $name = isset($row["pname"]) ? htmlspecialchars($row["pname"]) : '';
        $email = isset($row["pemail"]) ? htmlspecialchars($row["pemail"]) : '';
        $tele = isset($row['ptel']) ? htmlspecialchars($row['ptel']) : '';
        $dob = isset($row["pdob"]) ? htmlspecialchars($row["pdob"]) : '';
        echo '
        <div id="popup1" class="overlay">
            <div class="popup">
                <center>
                    <h2></h2>
                    <a class="close" href="settings.php">&times;</a>
                    <div class="content"><br></div>
                    <div style="display: flex;justify-content: center;">
                        <table width="80%" class="sub-table scrolldown add-doc-form-container" border="0">
                            <tr><td><p style="padding: 0;margin: 0;text-align: left;font-size: 25px;font-weight: 500;">Просмотр данных</p><br><br></td></tr>
                            <tr><td class="label-td" colspan="2"><label class="form-label">Имя: </label></td></tr>
                            <tr><td class="label-td" colspan="2">'.$name.'<br><br></td></tr>
                            <tr><td class="label-td" colspan="2"><label class="form-label">Почта: </label></td></tr>
                            <tr><td class="label-td" colspan="2">'.$email.'<br><br></td></tr>
                            <tr><td class="label-td" colspan="2"><label class="form-label">Номер телефона: </label></td></tr>
                            <tr><td class="label-td" colspan="2">'.$tele.'<br><br></td></tr>
                            <tr><td class="label-td" colspan="2"><label class="form-label">Дата рождения: </label></td></tr>
                            <tr><td class="label-td" colspan="2">'.$dob.'<br><br></td></tr>
                            <tr><td colspan="2"><a href="settings.php"><input type="button" value="ОК" class="login-btn btn-primary-soft btn"></a></td></tr>
                        </table>
                    </div>
                </center>
                <br><br>
            </div>
        </div>';    }elseif($action=='notifications'){
        // Получаем текущие настройки уведомлений пациента
        require_once "../includes/notification_functions.php";
        
        // Проверяем, существует ли столбец notification_preference
        $checkColumnQuery = "SHOW COLUMNS FROM patient LIKE 'notification_preference'";
        $column_check = $database->query($checkColumnQuery);
        
        if ($column_check->num_rows == 0) {
            // Если столбец не существует, добавляем его
            $alterTableQuery = "ALTER TABLE patient ADD COLUMN notification_preference ENUM('email', 'sms', 'both', 'none') DEFAULT 'email'";
            $database->query($alterTableQuery);
            $notification_preference = 'email'; // Значение по умолчанию
        } else {
            // Столбец существует, получаем значение
            $sqlmain= "SELECT notification_preference FROM patient WHERE pid=?";
            $stmt = $database->prepare($sqlmain);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row=$result->fetch_assoc();
            $notification_preference = $row["notification_preference"] ?? 'email';
        }
        
        // Проверяем успешность операции
        $success = isset($_GET["success"]) ? intval($_GET["success"]) : 0;
        $error = isset($_GET["error"]) ? intval($_GET["error"]) : 0;
        
        echo '
        <div id="popup1" class="overlay">
            <div class="popup">
                <center>
                    <a class="close" href="settings.php">&times;</a>
                    <div style="display: flex;justify-content: center;">
                        <div class="abc">
                            <form action="settings.php" method="POST" class="add-new-form">
                                <table width="80%" class="sub-table scrolldown add-doc-form-container" border="0">';
                                
        if ($success) {
            echo '<tr><td colspan="2"><label class="form-label" style="color:rgb(50, 180, 50);text-align:center;">Настройки уведомлений успешно обновлены!</label></td></tr>';
        } else if ($error) {
            echo '<tr><td colspan="2"><label class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Не удалось обновить настройки уведомлений. Попробуйте позже.</label></td></tr>';
        }
                                
        echo '
                                    <tr><td colspan="2"><p style="text-align: left;font-size: 25px;font-weight: 500;">Настройки уведомлений</p>Идентификатор пользователя : '.$id.'<br><br></td></tr>
                                    <tr><td colspan="2">
                                        <label class="form-label">Метод уведомлений: </label>
                                        <input type="hidden" value="'.$id.'" name="patient_id">
                                        <select name="notification_preference" class="box form-control" required>
                                            <option value="email" '.($notification_preference == 'email' ? 'selected' : '').'>Только по электронной почте</option>
                                            <option value="sms" '.($notification_preference == 'sms' ? 'selected' : '').'>Только по SMS</option>
                                            <option value="both" '.($notification_preference == 'both' ? 'selected' : '').'>По электронной почте и SMS</option>
                                            <option value="none" '.($notification_preference == 'none' ? 'selected' : '').'>Не получать уведомления</option>
                                        </select>
                                        <br>
                                    </td></tr>
                                    <tr>
                                        <td colspan="2">
                                            <p style="font-size: 14px; color: #555;">
                                                Выберите, как вы хотите получать уведомления о записях на прием:
                                                <ul style="text-align: left;">
                                                    <li>Подтверждения новых записей</li>
                                                    <li>Напоминания о предстоящих приемах</li>
                                                    <li>Уведомления об отмене или изменении записи</li>
                                                </ul>
                                            </p>
                                        </td>
                                    </tr>
                                    <tr><td colspan="2"><input type="reset" value="Сбросить" class="login-btn btn-primary-soft btn">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="update_notifications" value="Сохранить" class="login-btn btn-primary btn"></td></tr>
                                </table>
                            </form>
                        </div>
                    </div>
                </center>
                <br><br>
            </div>
        </div>';
        
    }elseif($action=='edit'){
        $sqlmain= "select * from patient where pid=?";
        $stmt = $database->prepare($sqlmain);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row=$result->fetch_assoc();
        $name=htmlspecialchars($row["pname"]);
        $email=htmlspecialchars($row["pemail"]);
        $tele=htmlspecialchars($row['ptel']);
        $error_1=$_GET["error"] ?? '0';
        $errorlist= array(
            '1'=>'<label class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Учетная запись с таким адресом электронной почты уже существует.</label>',
            '2'=>'<label class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Ошибка подтверждения пароля! Повторно введите пароль</label>',
            '3'=>'<label class="form-label" style="color:rgb(255, 62, 62);text-align:center;"></label>',
            '4'=>'',
            '0'=>''
        );
        if($error_1!='4'){
            echo '
            <div id="popup1" class="overlay">
                <div class="popup">
                    <center>
                        <a class="close" href="settings.php">&times;</a>
                        <div style="display: flex;justify-content: center;">                            <div class="abc">
                                <form action="settings.php" method="POST" class="add-new-form">
                                    <table width="80%" class="sub-table scrolldown add-doc-form-container" border="0">
                                        <tr><td colspan="2">'.$errorlist[$error_1].'</td></tr>
                                        <tr><td colspan="2"><p style="text-align: left;font-size: 25px;font-weight: 500;">Редактировать пользовательские данные аккаунта</p>Идентификатор пользователя : '.$id.'<br><br></td></tr>
                                        <tr><td colspan="2"><label class="form-label">Почта: </label><input type="hidden" value="'.$id.'" name="id00"><input type="hidden" name="oldemail" value="'.$email.'"><input type="email" name="email" class="input-text" placeholder="Введите адрес эл. почты" value="'.$email.'" required><br></td></tr>
                                        <tr><td colspan="2"><label class="form-label">Имя: </label><input type="text" name="name" class="input-text" placeholder="Введите имя пользователя" value="'.$name.'" required><br></td></tr>
                                        <tr><td colspan="2"><label class="form-label">Номер телефона: </label><input type="tel" name="Tele" class="input-text" placeholder="Введите ваш номер телефона" value="'.$tele.'" required><br></td></tr>                                        <tr><td colspan="2"><label class="form-label">Пароль: </label><input type="password" name="password" class="input-text" placeholder="Введите пароль" required><br></td></tr>
                                        <tr><td colspan="2"><label class="form-label">Подтверждение пароля: </label><input type="password" name="cpassword" class="input-text" placeholder="Повторно введите новый пароль" required><br></td></tr>
                                        <tr><td colspan="2"><input type="reset" value="Сбросить" class="login-btn btn-primary-soft btn">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="update_patient" value="Сохранить" class="login-btn btn-primary btn"></td></tr>
                                    </table>
                                </form>
                            </div>
                        </div>
                    </center>
                    <br><br>
                </div>
            </div>';
        }else{
            echo '
            <div id="popup1" class="overlay">
                <div class="popup">
                    <center>
                        <br><br><br><br>
                        <h2>Редактирование прошло успешно!</h2>
                        <a class="close" href="settings.php">&times;</a>
                        <div class="content">Если вы измените свою электронную почту, пожалуйста, выйдите из системы и войдите снова с новой электронной почтой</div>
                        <div style="display: flex;justify-content: center;">
                            <a href="settings.php" class="non-style-link"><button class="btn-primary btn" style="margin:10px;padding:10px;"><font class="tn-in-text">&nbsp;&nbsp;OK&nbsp;&nbsp;</font></button></a>
                            <a href="../logout.php" class="non-style-link"><button class="btn-primary-soft btn" style="margin:10px;padding:10px;"><font class="tn-in-text">&nbsp;&nbsp;Выйти&nbsp;&nbsp;</font></button></a>
                        </div>
                        <br><br>
                    </center>
                </div>
            </div>';
        }
    }
}
?>
<?php include "footer.php"; ?>
