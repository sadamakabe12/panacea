<?php
/**
 * Функции для работы с расписанием врачей
 */

/**
 * Получает график работы врача на определенный день недели
 * 
 * @param object $database Объект подключения к БД
 * @param int $docid ID врача
 * @param int $day_of_week День недели (1-Пн, 2-Вт и т.д.)
 * @return array|false Данные расписания или false если не найдено
 */
function get_doctor_schedule_by_day($database, $docid, $day_of_week) {
    $sql = "SELECT * FROM doctor_schedules 
            WHERE docid = ? AND day_of_week = ? AND is_active = 1";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("ii", $docid, $day_of_week);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return false;
}

/**
 * Получает все расписание врача
 * 
 * @param object $database Объект подключения к БД
 * @param int $docid ID врача
 * @return array Массив с расписанием
 */
function get_doctor_full_schedule($database, $docid) {
    $sql = "SELECT * FROM doctor_schedules 
            WHERE docid = ? AND is_active = 1
            ORDER BY day_of_week";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("i", $docid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $schedule = [];
    while ($row = $result->fetch_assoc()) {
        $schedule[] = $row;
    }
    
    return $schedule;
}

/**
 * Добавляет или обновляет расписание врача для определенного дня недели
 * 
 * @param object $database Объект подключения к БД
 * @param int $docid ID врача
 * @param int $day_of_week День недели (1-Пн, 2-Вт и т.д.)
 * @param string $start_time Время начала работы (HH:MM)
 * @param string $end_time Время окончания работы (HH:MM)
 * @param string|null $break_start Время начала перерыва (HH:MM), nullable
 * @param string|null $break_end Время окончания перерыва (HH:MM), nullable
 * @param int $appointment_duration Длительность приема в минутах
 * @param int $max_patients Макс. кол-во пациентов (0 - без ограничений)
 * @return bool Результат операции
 */
function set_doctor_schedule($database, $docid, $day_of_week, $start_time, $end_time, $break_start = null, $break_end = null, $appointment_duration = 30, $max_patients = 0) {
    // Проверяем, существует ли уже расписание на этот день
    $existing = get_doctor_schedule_by_day($database, $docid, $day_of_week);
    
    if ($existing) {
        // Обновляем существующее расписание
        $sql = "UPDATE doctor_schedules SET 
                start_time = ?, end_time = ?, break_start = ?, break_end = ?, 
                appointment_duration = ?, max_patients = ?, is_active = 1
                WHERE schedule_id = ?";
        $stmt = $database->prepare($sql);
        $stmt->bind_param("ssssiis", $start_time, $end_time, $break_start, $break_end, 
                         $appointment_duration, $max_patients, $existing['schedule_id']);
    } else {
        // Создаем новое расписание
        $sql = "INSERT INTO doctor_schedules 
                (docid, day_of_week, start_time, end_time, break_start, break_end, appointment_duration, max_patients) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $database->prepare($sql);
        $stmt->bind_param("iissssii", $docid, $day_of_week, $start_time, $end_time, 
                         $break_start, $break_end, $appointment_duration, $max_patients);
    }
    
    return $stmt->execute();
}

/**
 * Удаляет расписание врача для определенного дня недели
 * 
 * @param object $database Объект подключения к БД
 * @param int $docid ID врача
 * @param int $day_of_week День недели (1-Пн, 2-Вт и т.д.)
 * @return bool Результат операции
 */
function delete_doctor_schedule($database, $docid, $day_of_week) {
    $sql = "UPDATE doctor_schedules SET is_active = 0 
            WHERE docid = ? AND day_of_week = ?";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("ii", $docid, $day_of_week);
    return $stmt->execute();
}

/**
 * Добавляет исключение в расписание врача (отпуск, больничный и т.д.)
 * 
 * @param object $database Объект подключения к БД
 * @param int $docid ID врача
 * @param string $exception_date Дата исключения (YYYY-MM-DD)
 * @param string $exception_type Тип исключения ('vacation', 'sick_leave', 'personal', 'custom')
 * @param string|null $start_time Время начала (HH:MM), null если весь день
 * @param string|null $end_time Время окончания (HH:MM), null если весь день
 * @param string|null $description Описание исключения
 * @return int|bool ID созданной записи или false в случае ошибки
 */
function add_schedule_exception($database, $docid, $exception_date, $exception_type, $start_time = null, $end_time = null, $description = null) {
    $sql = "INSERT INTO schedule_exceptions 
            (docid, exception_date, exception_type, start_time, end_time, description) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("isssss", $docid, $exception_date, $exception_type, $start_time, $end_time, $description);
    
    if ($stmt->execute()) {
        return $database->insert_id;
    }
    
    return false;
}

/**
 * Удаляет исключение из расписания врача
 * 
 * @param object $database Объект подключения к БД
 * @param int $exception_id ID исключения
 * @return bool Результат операции
 */
function delete_schedule_exception($database, $exception_id) {
    $sql = "DELETE FROM schedule_exceptions WHERE exception_id = ?";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("i", $exception_id);
    return $stmt->execute();
}

/**
 * Получает исключения в расписании врача на определенный период
 * 
 * @param object $database Объект подключения к БД
 * @param int $docid ID врача
 * @param string $start_date Начальная дата периода (YYYY-MM-DD)
 * @param string $end_date Конечная дата периода (YYYY-MM-DD)
 * @return array Массив исключений
 */
function get_doctor_schedule_exceptions($database, $docid, $start_date, $end_date) {
    $sql = "SELECT * FROM schedule_exceptions 
            WHERE docid = ? AND exception_date BETWEEN ? AND ? 
            ORDER BY exception_date";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("iss", $docid, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $exceptions = [];
    while ($row = $result->fetch_assoc()) {
        $exceptions[] = $row;
    }
    
    return $exceptions;
}

/**
 * Генерирует доступные слоты для записи на прием к врачу на определенную дату
 * 
 * @param object $database Объект подключения к БД
 * @param int $docid ID врача
 * @param string $date Дата (YYYY-MM-DD)
 * @return array Массив доступных временных слотов
 */
function get_available_appointment_slots($database, $docid, $date) {
    // Определяем день недели для указанной даты (1-7, где 1 - понедельник)
    $day_of_week = date('N', strtotime($date));
    
    // Получаем расписание врача на этот день недели
    $schedule = get_doctor_schedule_by_day($database, $docid, $day_of_week);
    if (!$schedule) {
        return []; // Врач не работает в этот день недели
    }
    
    // Проверяем исключения (отпуск, больничный и т.д.)
    $exceptions = get_doctor_schedule_exceptions($database, $docid, $date, $date);
    foreach ($exceptions as $exception) {
        if ($exception['start_time'] === null && $exception['end_time'] === null) {
            return []; // Исключение на весь день
        }
    }
    
    // Получаем уже существующие записи на эту дату
    $sql = "SELECT appotime, duration FROM appointment 
            WHERE docid = ? AND appodate = ? AND status != 'canceled'";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("is", $docid, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $booked_slots = [];
    while ($row = $result->fetch_assoc()) {
        $start_time = strtotime($date . ' ' . $row['appotime']);
        $end_time = $start_time + ($row['duration'] * 60);
        $booked_slots[] = [
            'start' => $start_time,
            'end' => $end_time
        ];
    }
    
    // Создаем временные метки для начала и конца рабочего дня
    $start_timestamp = strtotime($date . ' ' . $schedule['start_time']);
    $end_timestamp = strtotime($date . ' ' . $schedule['end_time']);
    
    // Создаем временные метки для перерыва (если есть)
    $break_start_timestamp = null;
    $break_end_timestamp = null;
    if ($schedule['break_start'] && $schedule['break_end']) {
        $break_start_timestamp = strtotime($date . ' ' . $schedule['break_start']);
        $break_end_timestamp = strtotime($date . ' ' . $schedule['break_end']);
    }
    
    // Длительность приема в секундах
    $slot_duration = $schedule['appointment_duration'] * 60;
    
    // Генерируем все возможные слоты в рабочем дне
    $available_slots = [];
    $current = $start_timestamp;
    
    while ($current + $slot_duration <= $end_timestamp) {
        $slot_end = $current + $slot_duration;
        
        // Проверяем, не попадает ли слот на перерыв
        $is_during_break = false;
        if ($break_start_timestamp && $break_end_timestamp) {
            if (($current >= $break_start_timestamp && $current < $break_end_timestamp) ||
                ($slot_end > $break_start_timestamp && $slot_end <= $break_end_timestamp) ||
                ($current <= $break_start_timestamp && $slot_end >= $break_end_timestamp)) {
                $is_during_break = true;
            }
        }
        
        // Проверяем, не перекрывается ли слот с существующими записями
        $is_booked = false;
        foreach ($booked_slots as $booked) {
            if (($current >= $booked['start'] && $current < $booked['end']) ||
                ($slot_end > $booked['start'] && $slot_end <= $booked['end']) ||
                ($current <= $booked['start'] && $slot_end >= $booked['end'])) {
                $is_booked = true;
                break;
            }
        }
        
        // Проверяем исключения (если есть частичный выходной)
        $is_exception = false;
        foreach ($exceptions as $exception) {
            if ($exception['start_time'] && $exception['end_time']) {
                $exception_start = strtotime($date . ' ' . $exception['start_time']);
                $exception_end = strtotime($date . ' ' . $exception['end_time']);
                
                if (($current >= $exception_start && $current < $exception_end) ||
                    ($slot_end > $exception_start && $slot_end <= $exception_end) ||
                    ($current <= $exception_start && $slot_end >= $exception_end)) {
                    $is_exception = true;
                    break;
                }
            }
        }
        
        // Если слот доступен, добавляем его в список
        if (!$is_during_break && !$is_booked && !$is_exception) {
            $available_slots[] = [
                'time' => date('H:i', $current),
                'timestamp' => $current,
                'time_end' => date('H:i', $slot_end),
                'duration' => $schedule['appointment_duration']
            ];
        }
        
        // Переходим к следующему слоту
        $current += $slot_duration;
    }
    
    return $available_slots;
}

/**
 * Создает запись на прием из доступного слота
 * 
 * @param object $database Объект подключения к БД
 * @param int $docid ID врача
 * @param int $pid ID пациента
 * @param string $date Дата приема (YYYY-MM-DD)
 * @param string $time Время приема (HH:MM)
 * @param int $duration Длительность приема в минутах
 * @param string $reason Причина визита
 * @param string $status Статус записи
 * @return int|bool ID созданной записи или false в случае ошибки
 */
function create_appointment($database, $docid, $pid, $date, $time, $duration = 30, $reason = '', $status = 'scheduled') {
    // Проверяем, доступен ли слот
    $available_slots = get_available_appointment_slots($database, $docid, $date);
    $slot_available = false;
    
    foreach ($available_slots as $slot) {
        if ($slot['time'] === $time) {
            $slot_available = true;
            break;
        }
    }
    
    if (!$slot_available) {
        return false; // Слот недоступен
    }
    
    // Создаем запись на прием
    $sql = "INSERT INTO appointment 
            (pid, docid, appodate, appotime, status, duration, reason) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("iisssss", $pid, $docid, $date, $time, $status, $duration, $reason);
    
    if ($stmt->execute()) {
        return $database->insert_id;
    }
    
    return false;
}

/**
 * Обновляет статус записи на прием
 * 
 * @param object $database Объект подключения к БД
 * @param int $appoid ID записи
 * @param string $status Новый статус
 * @param string|null $notes Примечания
 * @return bool Результат операции
 */
function update_appointment_status($database, $appoid, $status, $notes = null) {
    $sql = "UPDATE appointment SET status = ?, notes = ? WHERE appoid = ?";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("ssi", $status, $notes, $appoid);
    return $stmt->execute();
}

/**
 * Получает детальную информацию о записи на прием
 * 
 * @param object $database Объект подключения к БД
 * @param int $appoid ID записи
 * @return array|null Данные записи или null если не найдена
 */
function get_appointment_details($database, $appoid) {
    $sql = "SELECT a.*, 
            p.pname as patient_name, p.ptel as patient_phone, p.pemail as patient_email, 
            d.docname as doctor_name, d.doctel as doctor_phone, d.docemail as doctor_email
            FROM appointment a 
            JOIN patient p ON a.pid = p.pid 
            JOIN doctor d ON a.docid = d.docid 
            WHERE a.appoid = ?";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("i", $appoid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Получает все записи врача на определенную дату
 * 
 * @param object $database Объект подключения к БД
 * @param int $docid ID врача
 * @param string $date Дата (YYYY-MM-DD)
 * @return array Массив записей
 */
function get_doctor_appointments_by_date($database, $docid, $date) {
    $sql = "SELECT a.*, p.pname as patient_name, p.ptel as patient_phone, s.scheduletime as appotime,
                   sp.sname as specialty_name, s.scheduledate as appointment_date
            FROM appointment a 
            JOIN patient p ON a.pid = p.pid 
            JOIN schedule s ON a.scheduleid = s.scheduleid
            LEFT JOIN specialties sp ON s.specialty_id = sp.id
            WHERE s.docid = ? AND a.appodate = ? 
            ORDER BY s.scheduletime";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("is", $docid, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $appointments = [];
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
    
    return $appointments;
}

/**
 * Получает информацию о расписании врачей для пациента
 * 
 * @param object $database Объект подключения к БД
 * @param string $date Дата (YYYY-MM-DD)
 * @param int|null $specialty_id ID специальности (опционально)
 * @return array Массив с доступными врачами и их слотами
 */
function get_available_doctors_for_date($database, $date, $specialty_id = null) {
    // Определяем день недели для указанной даты (1-7, где 1 - понедельник)
    $day_of_week = date('N', strtotime($date));
    
    // Формируем базовый запрос
    $sql = "SELECT d.docid, d.docname, d.docemail, ds.* 
            FROM doctor d
            JOIN doctor_schedules ds ON d.docid = ds.docid
            WHERE ds.day_of_week = ? AND ds.is_active = 1";
    
    // Добавляем фильтр по специальности, если указана
    $params = [$day_of_week];
    $types = "i";
    
    if ($specialty_id !== null) {
        $sql .= " AND d.docid IN (SELECT docid FROM doctor_specialty WHERE specialty_id = ?)";
        $params[] = $specialty_id;
        $types .= "i";
    }
    
    $sql .= " ORDER BY d.docname";
    $stmt = $database->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $available_doctors = [];
    while ($row = $result->fetch_assoc()) {
        $docid = $row['docid'];
        
        // Проверяем исключения в расписании
        $exceptions = get_doctor_schedule_exceptions($database, $docid, $date, $date);
        $has_full_day_exception = false;
        
        foreach ($exceptions as $exception) {
            if ($exception['start_time'] === null && $exception['end_time'] === null) {
                $has_full_day_exception = true;
                break;
            }
        }
        
        if ($has_full_day_exception) {
            continue; // Пропускаем врача с исключением на весь день
        }
        
        // Получаем доступные слоты для записи
        $slots = get_available_appointment_slots($database, $docid, $date);
        
        if (!empty($slots)) {
            $available_doctors[] = [
                'doctor' => [
                    'docid' => $row['docid'],
                    'docname' => $row['docname'],
                    'docemail' => $row['docemail']
                ],
                'schedule' => [
                    'start_time' => $row['start_time'],
                    'end_time' => $row['end_time'],
                    'appointment_duration' => $row['appointment_duration']
                ],
                'available_slots' => $slots,
                'slot_count' => count($slots)
            ];
        }
    }
    
    return $available_doctors;
}

/**
 * Создает шаблон для записи на прием
 * 
 * @param object $database Объект подключения к БД
 * @param int $docid ID врача
 * @param string $template_name Название шаблона
 * @param int $duration Длительность приема в минутах
 * @param string|null $description Описание
 * @return int|bool ID созданного шаблона или false в случае ошибки
 */
function create_appointment_template($database, $docid, $template_name, $duration = 30, $description = null) {
    $sql = "INSERT INTO appointment_templates 
            (docid, template_name, duration, description) 
            VALUES (?, ?, ?, ?)";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("isis", $docid, $template_name, $duration, $description);
    
    if ($stmt->execute()) {
        return $database->insert_id;
    }
    
    return false;
}

/**
 * Получает шаблоны записей для врача
 * 
 * @param object $database Объект подключения к БД
 * @param int $docid ID врача
 * @return array Массив шаблонов
 */
function get_doctor_appointment_templates($database, $docid) {
    $sql = "SELECT * FROM appointment_templates WHERE docid = ? ORDER BY template_name";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("i", $docid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $templates = [];
    while ($row = $result->fetch_assoc()) {
        $templates[] = $row;
    }
    
    return $templates;
}

/**
 * Проверяет, есть ли у врача прием в указанное время
 * 
 * @param object $database Объект подключения к БД
 * @param int $docid ID врача
 * @param string $date Дата приема (YYYY-MM-DD)
 * @param string $time Время приема (HH:MM)
 * @return bool Результат проверки
 */
function is_doctor_available($database, $docid, $date, $time) {
    $slots = get_available_appointment_slots($database, $docid, $date);
    
    foreach ($slots as $slot) {
        if ($slot['time'] === $time) {
            return true;
        }
    }
    
    return false;
}

/**
 * Создает уведомление о записи на прием
 * 
 * @param object $database Объект подключения к БД
 * @param int $appoid ID записи
 * @param string $notification_type Тип уведомления ('email', 'sms', 'system')
 * @param string $recipient_type Тип получателя ('patient', 'doctor')
 * @return int|bool ID созданного уведомления или false в случае ошибки
 */
function create_appointment_notification($database, $appoid, $notification_type, $recipient_type) {
    $sql = "INSERT INTO appointment_notifications 
            (appoid, notification_type, recipient_type) 
            VALUES (?, ?, ?)";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("iss", $appoid, $notification_type, $recipient_type);
    
    if ($stmt->execute()) {
        return $database->insert_id;
    }
    
    return false;
}

/**
 * Получает данные недели (даты для каждого дня недели)
 * 
 * @param string $date Опорная дата (YYYY-MM-DD)
 * @return array Массив с датами на неделю
 */
function get_week_dates($date = null) {
    if ($date === null) {
        $date = date('Y-m-d');
    }
    
    $timestamp = strtotime($date);
    $current_day = date('N', $timestamp); // 1 (Пн) до 7 (Вс)
    
    // Вычисляем смещение до понедельника
    $monday_offset = $current_day - 1;
    $monday = date('Y-m-d', strtotime("-{$monday_offset} days", $timestamp));
    
    $week_dates = [];
    for ($i = 0; $i < 7; $i++) {
        $day_date = date('Y-m-d', strtotime("+{$i} days", strtotime($monday)));
        $week_dates[] = [
            'date' => $day_date,
            'day_of_week' => $i + 1,
            'day_name' => date('l', strtotime($day_date)),
            'day_short' => date('D', strtotime($day_date)),
            'day_number' => date('j', strtotime($day_date)),
            'month' => date('F', strtotime($day_date)),
            'month_short' => date('M', strtotime($day_date)),
            'is_today' => $day_date === date('Y-m-d')
        ];
    }
    
    return $week_dates;
}

/**
 * Получает данные месяца (даты для каждого дня месяца)
 * 
 * @param string $year_month Год и месяц (YYYY-MM)
 * @return array Массив с датами на месяц
 */
function get_month_dates($year_month = null) {
    if ($year_month === null) {
        $year_month = date('Y-m');
    }
    
    $first_day = $year_month . '-01';
    $last_day = date('Y-m-t', strtotime($first_day));
    
    $month_dates = [];
    $current = $first_day;
    
    while ($current <= $last_day) {
        $month_dates[] = [
            'date' => $current,
            'day_of_week' => date('N', strtotime($current)),
            'day_name' => date('l', strtotime($current)),
            'day_short' => date('D', strtotime($current)),
            'day_number' => date('j', strtotime($current)),
            'month' => date('F', strtotime($current)),
            'month_short' => date('M', strtotime($current)),
            'is_today' => $current === date('Y-m-d')
        ];
        
        $current = date('Y-m-d', strtotime("+1 day", strtotime($current)));
    }
    
    return $month_dates;
}
