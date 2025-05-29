<?php
/**
 * Функции для работы с уведомлениями о записях на прием
 */

/**
 * Инициализирует компоненты системы уведомлений
 * @param object $database Объект подключения к БД
 * @return bool Успешно ли выполнена инициализация
 */
function initialize_notification_system($database) {
    // Проверяем и создаем таблицу для уведомлений если она не существует
    $table_exists_query = "SHOW TABLES LIKE 'appointment_notifications'";
    $result = $database->query($table_exists_query);
    
    if ($result->num_rows == 0) {
        // Таблица не существует, создаем её
        $create_table_sql = "CREATE TABLE IF NOT EXISTS `appointment_notifications` (
            `notification_id` INT PRIMARY KEY AUTO_INCREMENT,
            `appoid` INT NOT NULL,
            `notification_type` ENUM('confirmation', 'reminder', 'cancellation', 'reschedule', 'other', 'email', 'sms', 'system') NOT NULL,
            `recipient_type` ENUM('patient', 'doctor') NOT NULL,
            `sent_at` TIMESTAMP NULL DEFAULT NULL,
            `status` ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
            `error_message` TEXT DEFAULT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`appoid`) REFERENCES `appointment`(`appoid`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        if (!$database->query($create_table_sql)) {
            error_log("Ошибка создания таблицы appointment_notifications: " . $database->error);
            return false;
        }
    }
    
    // Проверяем и добавляем столбец notification_preference если он не существует
    $success = add_notification_preference_to_patient_table($database);
    
    return $success;
}

// Автоматическая инициализация при подключении файла
if (isset($database)) {
    initialize_notification_system($database);
}

/**
 * Отправляет уведомление по электронной почте
 * @param string $to Email получателя
 * @param string $subject Тема письма
 * @param string $message Содержание письма
 * @param string $type Тип уведомления (confirmation, reminder, cancellation, rescheduled)
 * @return bool Успешно ли отправлено письмо
 */
function send_email_notification($to, $subject, $message, $type = 'general') {
    // Заголовки письма
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Медицинская клиника <no-reply@clinic.com>" . "\r\n";
    
    // Запись в журнал
    error_log("Отправка email уведомления типа $type на адрес $to");
    
    // Отправка письма
    return mail($to, $subject, $message, $headers);
}

/**
 * Отправляет SMS уведомление
 * @param string $phone Номер телефона получателя
 * @param string $message Текст сообщения
 * @param string $type Тип уведомления (confirmation, reminder, cancellation, rescheduled)
 * @return bool Успешно ли отправлено сообщение
 * 
 * Примечание: Эта функция требует настройки SMS-шлюза
 */
function send_sms_notification($phone, $message, $type = 'general') {
    // В этой реализации мы только логируем запрос на отправку SMS
    // В реальном проекте здесь должна быть интеграция с SMS-шлюзом (Twilio, Infobip, и т.д.)
    error_log("Запрос на отправку SMS уведомления типа $type на номер $phone: $message");
    
    // Для тестирования всегда возвращаем успех
    return true;
    
    // Пример интеграции с Twilio:
    /*
    require_once 'vendor/autoload.php';
    
    $account_sid = 'ВАШ_TWILIO_ACCOUNT_SID';
    $auth_token = 'ВАШ_TWILIO_AUTH_TOKEN';
    $twilio_number = 'ВАШ_TWILIO_НОМЕР';
    
    $client = new Twilio\Rest\Client($account_sid, $auth_token);
    
    try {
        $message = $client->messages->create(
            $phone,
            [
                'from' => $twilio_number,
                'body' => $message
            ]
        );
        
        return true;
    } catch (Exception $e) {
        error_log("Ошибка отправки SMS: " . $e->getMessage());
        return false;
    }
    */
}

/**
 * Создает HTML шаблон для email уведомления
 * @param string $title Заголовок уведомления
 * @param string $content Содержание уведомления
 * @param array $appointment Детали записи на прием
 * @return string HTML шаблон
 */
function create_email_template($title, $content, $appointment = null) {
    $clinic_address = "ул. Примерная, 123, г. Город";
    $clinic_phone = "+7 (123) 456-78-90";
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 600px;
                margin: 0 auto;
            }
            .container {
                padding: 20px;
                border: 1px solid #eee;
            }
            .header {
                background-color: #4B77BE;
                color: white;
                padding: 15px;
                text-align: center;
            }
            .content {
                padding: 20px;
            }
            .footer {
                font-size: 12px;
                text-align: center;
                margin-top: 30px;
                color: #777;
            }
            .appointment-details {
                background-color: #f9f9f9;
                padding: 15px;
                margin: 20px 0;
                border-left: 4px solid #4B77BE;
            }
            .appointment-details p {
                margin: 5px 0;
            }
            .button {
                display: inline-block;
                padding: 10px 20px;
                background-color: #4B77BE;
                color: white;
                text-decoration: none;
                border-radius: 4px;
                margin-top: 15px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>' . $title . '</h2>
            </div>
            <div class="content">
                ' . $content;
                
    if ($appointment) {
        $html .= '
                <div class="appointment-details">
                    <h3>Детали записи:</h3>
                    <p><strong>Дата и время:</strong> ' . $appointment['appointment_date'] . ' ' . $appointment['appointment_time'] . '</p>
                    <p><strong>Врач:</strong> ' . $appointment['doctor_name'] . '</p>
                    <p><strong>Специальность:</strong> ' . $appointment['specialty'] . '</p>
                    <p><strong>Адрес:</strong> ' . $clinic_address . '</p>
                </div>';
    }
    
    $html .= '
                <p>С уважением,<br>Медицинская клиника</p>
            </div>
            <div class="footer">
                <p>Адрес: ' . $clinic_address . ' | Телефон: ' . $clinic_phone . '</p>
                <p>Если у вас возникли вопросы, свяжитесь с нами по телефону или email.</p>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}

/**
 * Отправляет уведомление о подтверждении записи на прием
 * @param object $database Объект подключения к БД
 * @param int $appointment_id ID записи на прием
 * @param string $notification_method Метод уведомления (email, sms, both)
 * @return bool Успешно ли отправлено уведомление
 */
function send_appointment_confirmation($database, $appointment_id, $notification_method = 'email') {
    // Получаем данные о записи на прием
    $sql = "SELECT a.*, p.pname as patient_name, p.pemail as patient_email, p.ptel as patient_phone, 
                   d.docname as doctor_name, d.specialties as specialty
            FROM appointment a
            JOIN patient p ON a.pid = p.pid
            JOIN doctor d ON a.docid = d.docid
            WHERE a.appoid = ?";
    
    $stmt = $database->prepare($sql);
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        error_log("Ошибка: Запись на прием #$appointment_id не найдена");
        return false;
    }
    
    $appointment = $result->fetch_assoc();
    
    // Создаем контент для уведомления
    $title = "Подтверждение записи на прием";
    $content = "
        <p>Уважаемый(ая) {$appointment['patient_name']},</p>
        <p>Ваша запись на прием была успешно подтверждена.</p>
        <p>Пожалуйста, приходите за 10-15 минут до назначенного времени.</p>
    ";
    
    $email_sent = false;
    $sms_sent = false;
    
    // Отправляем email, если указан
    if ($notification_method == 'email' || $notification_method == 'both') {
        $email_template = create_email_template($title, $content, $appointment);
        $email_sent = send_email_notification($appointment['patient_email'], $title, $email_template, 'confirmation');
    }
    
    // Отправляем SMS, если указан
    if ($notification_method == 'sms' || $notification_method == 'both') {
        $sms_message = "Ваша запись к врачу {$appointment['doctor_name']} на {$appointment['appointment_date']} {$appointment['appointment_time']} подтверждена.";
        $sms_sent = send_sms_notification($appointment['patient_phone'], $sms_message, 'confirmation');
    }
    
    // Записываем в журнал уведомлений
    $sql = "INSERT INTO appointment_notifications (appointment_id, notification_type, notification_method, is_sent, sent_at) 
            VALUES (?, 'confirmation', ?, ?, NOW())";
    
    $is_sent = ($notification_method == 'both') ? ($email_sent && $sms_sent) : ($email_sent || $sms_sent);
    $stmt = $database->prepare($sql);
    $stmt->bind_param("isi", $appointment_id, $notification_method, $is_sent);
    $stmt->execute();
    
    return $is_sent;
}

/**
 * Отправляет напоминание о записи на прием
 * @param object $database Объект подключения к БД
 * @param int $appointment_id ID записи на прием
 * @param string $notification_method Метод уведомления (email, sms, both)
 * @return bool Успешно ли отправлено уведомление
 */
function send_appointment_reminder($database, $appointment_id, $notification_method = 'email') {
    // Получаем данные о записи на прием
    $sql = "SELECT a.*, p.pname as patient_name, p.pemail as patient_email, p.ptel as patient_phone, 
                   d.docname as doctor_name, d.specialties as specialty
            FROM appointment a
            JOIN patient p ON a.pid = p.pid
            JOIN doctor d ON a.docid = d.docid
            WHERE a.appoid = ?";
    
    $stmt = $database->prepare($sql);
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        error_log("Ошибка: Запись на прием #$appointment_id не найдена");
        return false;
    }
    
    $appointment = $result->fetch_assoc();
    
    // Создаем контент для уведомления
    $title = "Напоминание о записи на прием";
    $content = "
        <p>Уважаемый(ая) {$appointment['patient_name']},</p>
        <p>Напоминаем вам о предстоящей записи на прием.</p>
        <p>Пожалуйста, приходите за 10-15 минут до назначенного времени.</p>
        <p>Если вы не можете прийти на прием, пожалуйста, отмените его как можно скорее.</p>
    ";
    
    $email_sent = false;
    $sms_sent = false;
    
    // Отправляем email, если указан
    if ($notification_method == 'email' || $notification_method == 'both') {
        $email_template = create_email_template($title, $content, $appointment);
        $email_sent = send_email_notification($appointment['patient_email'], $title, $email_template, 'reminder');
    }
    
    // Отправляем SMS, если указан
    if ($notification_method == 'sms' || $notification_method == 'both') {
        $sms_message = "Напоминание: у вас запись к врачу {$appointment['doctor_name']} завтра в {$appointment['appointment_time']}. Для отмены звоните по тел.";
        $sms_sent = send_sms_notification($appointment['patient_phone'], $sms_message, 'reminder');
    }
    
    // Записываем в журнал уведомлений
    $sql = "INSERT INTO appointment_notifications (appointment_id, notification_type, notification_method, is_sent, sent_at) 
            VALUES (?, 'reminder', ?, ?, NOW())";
    
    $is_sent = ($notification_method == 'both') ? ($email_sent && $sms_sent) : ($email_sent || $sms_sent);
    $stmt = $database->prepare($sql);
    $stmt->bind_param("isi", $appointment_id, $notification_method, $is_sent);
    $stmt->execute();
    
    return $is_sent;
}

/**
 * Отправляет уведомление об отмене записи на прием
 * @param object $database Объект подключения к БД
 * @param int $appointment_id ID записи на прием
 * @param string $reason Причина отмены
 * @param string $notification_method Метод уведомления (email, sms, both)
 * @return bool Успешно ли отправлено уведомление
 */
function send_appointment_cancellation($database, $appointment_id, $reason = null, $notification_method = 'email') {
    // Получаем данные о записи на прием
    $sql = "SELECT a.*, p.pname as patient_name, p.pemail as patient_email, p.ptel as patient_phone, 
                   d.docname as doctor_name, d.specialties as specialty
            FROM appointment a
            JOIN patient p ON a.pid = p.pid
            JOIN doctor d ON a.docid = d.docid
            WHERE a.appoid = ?";
    
    $stmt = $database->prepare($sql);
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        error_log("Ошибка: Запись на прием #$appointment_id не найдена");
        return false;
    }
    
    $appointment = $result->fetch_assoc();
    
    // Создаем контент для уведомления
    $title = "Отмена записи на прием";
    $content = "
        <p>Уважаемый(ая) {$appointment['patient_name']},</p>
        <p>Ваша запись на прием была отменена.</p>
    ";
    
    if ($reason) {
        $content .= "<p>Причина: $reason</p>";
    }
    
    $content .= "
        <p>Если у вас есть вопросы или вы хотите перенести прием, пожалуйста, свяжитесь с нами.</p>
    ";
    
    $email_sent = false;
    $sms_sent = false;
    
    // Отправляем email, если указан
    if ($notification_method == 'email' || $notification_method == 'both') {
        $email_template = create_email_template($title, $content, $appointment);
        $email_sent = send_email_notification($appointment['patient_email'], $title, $email_template, 'cancellation');
    }
    
    // Отправляем SMS, если указан
    if ($notification_method == 'sms' || $notification_method == 'both') {
        $sms_message = "Ваша запись к врачу {$appointment['doctor_name']} на {$appointment['appointment_date']} {$appointment['appointment_time']} отменена" . ($reason ? ". Причина: $reason" : "");
        $sms_sent = send_sms_notification($appointment['patient_phone'], $sms_message, 'cancellation');
    }
    
    // Записываем в журнал уведомлений
    $sql = "INSERT INTO appointment_notifications (appointment_id, notification_type, notification_method, is_sent, sent_at, additional_data) 
            VALUES (?, 'cancellation', ?, ?, NOW(), ?)";
    
    $additional_data = json_encode(['reason' => $reason]);
    $is_sent = ($notification_method == 'both') ? ($email_sent && $sms_sent) : ($email_sent || $sms_sent);
    $stmt = $database->prepare($sql);
    $stmt->bind_param("isis", $appointment_id, $notification_method, $is_sent, $additional_data);
    $stmt->execute();
    
    return $is_sent;
}

/**
 * Отправляет уведомление о переносе записи на прием
 * @param object $database Объект подключения к БД
 * @param int $appointment_id ID записи на прием
 * @param string $old_date Старая дата/время
 * @param string $old_doctor Старый врач
 * @param string $notification_method Метод уведомления (email, sms, both)
 * @return bool Успешно ли отправлено уведомление
 */
function send_appointment_reschedule($database, $appointment_id, $old_date = null, $old_time = null, $old_doctor = null, $notification_method = 'email') {
    // Получаем данные о записи на прием
    $sql = "SELECT a.*, p.pname as patient_name, p.pemail as patient_email, p.ptel as patient_phone, 
                   d.docname as doctor_name, d.specialties as specialty
            FROM appointment a
            JOIN patient p ON a.pid = p.pid
            JOIN doctor d ON a.docid = d.docid
            WHERE a.appoid = ?";
    
    $stmt = $database->prepare($sql);
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        error_log("Ошибка: Запись на прием #$appointment_id не найдена");
        return false;
    }
    
    $appointment = $result->fetch_assoc();
    
    // Создаем контент для уведомления
    $title = "Изменение записи на прием";
    $content = "
        <p>Уважаемый(ая) {$appointment['patient_name']},</p>
        <p>Ваша запись на прием была изменена.</p>
    ";
    
    if ($old_date && $old_time) {
        $content .= "<p>Предыдущая дата и время: $old_date $old_time</p>";
    }
    
    if ($old_doctor) {
        $content .= "<p>Предыдущий врач: $old_doctor</p>";
    }
    
    $content .= "
        <p>Новые детали записи указаны ниже.</p>
    ";
    
    $email_sent = false;
    $sms_sent = false;
    
    // Отправляем email, если указан
    if ($notification_method == 'email' || $notification_method == 'both') {
        $email_template = create_email_template($title, $content, $appointment);
        $email_sent = send_email_notification($appointment['patient_email'], $title, $email_template, 'reschedule');
    }
    
    // Отправляем SMS, если указан
    if ($notification_method == 'sms' || $notification_method == 'both') {
        $sms_message = "Ваша запись перенесена на {$appointment['appointment_date']} {$appointment['appointment_time']} к врачу {$appointment['doctor_name']}";
        $sms_sent = send_sms_notification($appointment['patient_phone'], $sms_message, 'reschedule');
    }
    
    // Записываем в журнал уведомлений
    $sql = "INSERT INTO appointment_notifications (appointment_id, notification_type, notification_method, is_sent, sent_at, additional_data) 
            VALUES (?, 'reschedule', ?, ?, NOW(), ?)";
    
    $additional_data = json_encode([
        'old_date' => $old_date,
        'old_time' => $old_time,
        'old_doctor' => $old_doctor
    ]);
    
    $is_sent = ($notification_method == 'both') ? ($email_sent && $sms_sent) : ($email_sent || $sms_sent);
    $stmt = $database->prepare($sql);
    $stmt->bind_param("isis", $appointment_id, $notification_method, $is_sent, $additional_data);
    $stmt->execute();
    
    return $is_sent;
}

/**
 * Запускает отправку напоминаний о предстоящих приемах
 * @param object $database Объект подключения к БД
 * @param int $days_ahead Сколько дней вперед проверять (по умолчанию 1 для напоминаний за день)
 * @return int Количество отправленных напоминаний
 */
function send_appointment_reminders_batch($database, $days_ahead = 1) {
    // Получаем дату для проверки (завтрашний день для напоминаний за день до приема)
    $check_date = date('Y-m-d', strtotime("+{$days_ahead} days"));
      // Получаем все записи на эту дату, которые еще не отменены
    $sql = "SELECT a.appoid
            FROM appointment a
            JOIN schedule s ON a.scheduleid = s.scheduleid
            WHERE s.scheduledate = ?
            AND a.status != 'canceled'
            AND NOT EXISTS (
                SELECT 1 FROM appointment_notifications n 
                WHERE n.appoid = a.appoid 
                AND n.notification_type = 'reminder'
                AND DATE(n.sent_at) = CURDATE()
            )";
    
    $stmt = $database->prepare($sql);
    $stmt->bind_param("s", $check_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sent_count = 0;
    while ($row = $result->fetch_assoc()) {
        $appointment_id = $row['appoid'];
        
        // Получаем предпочтительный метод уведомления пациента
        $notification_method = get_patient_notification_preference($database, $appointment_id);
        
        // Отправляем напоминание
        if (send_appointment_reminder($database, $appointment_id, $notification_method)) {
            $sent_count++;
        }
    }
    
    return $sent_count;
}

/**
 * Получает предпочтительный метод уведомления для пациента
 * @param object $database Объект подключения к БД
 * @param int $appointment_id ID записи на прием
 * @return string Метод уведомления (email, sms, both)
 */
function get_patient_notification_preference($database, $appointment_id) {
    // Проверяем, существует ли столбец notification_preference
    $checkColumnQuery = "SHOW COLUMNS FROM patient LIKE 'notification_preference'";
    $column_check = $database->query($checkColumnQuery);
    
    if ($column_check->num_rows == 0) {
        // Если столбец не существует, добавляем его
        $alterTableQuery = "ALTER TABLE patient ADD COLUMN notification_preference ENUM('email', 'sms', 'both', 'none') DEFAULT 'email'";
        $database->query($alterTableQuery);
        return 'email'; // По умолчанию используем email
    }
    
    // Получаем ID пациента из записи на прием
    $sql = "SELECT a.pid, p.notification_preference
            FROM appointment a
            JOIN patient p ON a.pid = p.pid
            WHERE a.appoid = ?";
    
    $stmt = $database->prepare($sql);
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // Если у пациента есть конкретное предпочтение, возвращаем его
        if (!empty($row['notification_preference'])) {
            return $row['notification_preference'];
        }
    }
    
    // По умолчанию используем email
    return 'email';
}

/**
 * Сохраняет предпочтение пациента по уведомлениям
 * @param object $database Объект подключения к БД
 * @param int $patient_id ID пациента
 * @param string $preference Предпочтение (email, sms, both, none)
 * @return bool Успешно ли сохранено предпочтение
 */
function save_patient_notification_preference($database, $patient_id, $preference) {
    $sql = "UPDATE patient SET notification_preference = ? WHERE pid = ?";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("si", $preference, $patient_id);
    
    return $stmt->execute();
}

/**
 * Создает таблицу для отслеживания уведомлений о записях на прием
 * @param object $database Объект подключения к БД
 * @return bool Успешно ли создана таблица
 */
function create_appointment_notifications_table($database) {
    $sql = "CREATE TABLE IF NOT EXISTS appointment_notifications (
        notification_id INT PRIMARY KEY AUTO_INCREMENT,
        appointment_id INT NOT NULL,
        notification_type ENUM('confirmation', 'reminder', 'cancellation', 'reschedule', 'other') NOT NULL,
        notification_method ENUM('email', 'sms', 'both') NOT NULL,
        is_sent TINYINT(1) NOT NULL DEFAULT 0,
        sent_at TIMESTAMP NOT NULL,
        additional_data TEXT DEFAULT NULL,
        FOREIGN KEY (appointment_id) REFERENCES appointment(appoid) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    return $database->query($sql);
}

/**
 * Проверяет и создает столбец notification_preference в таблице пациентов, если он отсутствует
 * @param object $database Объект подключения к БД
 * @return bool Успешно ли выполнена операция
 */
function add_notification_preference_to_patient_table($database) {
    // Проверяем, существует ли уже столбец notification_preference
    $query = "SHOW COLUMNS FROM patient LIKE 'notification_preference'";
    $result = $database->query($query);
    
    if ($result->num_rows == 0) {
        // Столбец не существует, добавляем его
        $sql = "ALTER TABLE patient ADD COLUMN notification_preference ENUM('email', 'sms', 'both', 'none') DEFAULT 'email'";
        return $database->query($sql);
    }
    
    return true; // Столбец уже существует
}
