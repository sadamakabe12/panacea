<?php
/**
 * Функции для работы с записями пациентов
 */

/**
 * Получает список записей пациента
 * @param object $database Объект подключения к БД
 * @param int $pid ID пациента
 * @param bool $include_completed Включать ли завершенные записи
 * @return array Массив записей
 */
function get_patient_appointments($database, $pid, $include_completed = true) {
    $sql = "SELECT a.*, d.docname, d.docid, s.scheduledate, s.scheduletime 
            FROM appointment a 
            JOIN schedule s ON a.scheduleid = s.scheduleid 
            JOIN doctor d ON s.docid = d.docid 
            WHERE a.pid = ?";
    if (!$include_completed) {
        $sql .= " AND s.scheduledate >= CURDATE()";
    }
    
    $sql .= " ORDER BY s.scheduledate, s.scheduletime";
    
    $stmt = $database->prepare($sql);
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $appointments = [];
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
    
    return $appointments;
}

/**
 * Получает отфильтрованный список записей пациента
 * @param object $database Объект подключения к БД
 * @param int $pid ID пациента
 * @param string|null $filter_date Дата для фильтрации (опционально)
 * @return object Результат запроса с записями
 */
function get_filtered_patient_appointments($database, $pid, $filter_date = null) {
    $sqlmain = "SELECT appointment.appoid, schedule.scheduleid, doctor.docname, 
                schedule.scheduledate, schedule.scheduletime, appointment.apponum, 
                appointment.appodate 
                FROM schedule 
                INNER JOIN appointment ON schedule.scheduleid=appointment.scheduleid 
                INNER JOIN doctor ON schedule.docid=doctor.docid 
                WHERE appointment.pid=?";
    
    $params = [$pid];
    $types = "i";

    // Фильтрация по дате
    if ($filter_date !== null) {
        $sqlmain .= " AND schedule.scheduledate=?";
        $params[] = $filter_date;
        $types .= "s";
    }

    $sqlmain .= " ORDER BY appointment.appodate ASC";
    $stmt = $database->prepare($sqlmain);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Получает список записей врача
 * @param object $database Объект подключения к БД
 * @param int $docid ID врача
 * @param string|null $date Дата (опционально)
 * @param bool $include_completed Включать ли завершенные записи
 * @return mixed Результат запроса с записями или массив записей
 */
function get_doctor_appointments($database, $docid, $date = null, $include_completed = true) {
    // Получаем обратную трассировку вызовов для определения контекста
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $from_appointment_page = isset($backtrace[1]['file']) && 
                            $backtrace[1]['file'] === 'c:\xampp\htdocs\doctor\appointment.php';
    
    // Формируем SQL запрос в зависимости от контекста
    if ($from_appointment_page) {
        // Это вызов из appointment.php - используем совместимый формат вывода
        $sql = "SELECT appointment.appoid, schedule.scheduleid, doctor.docname, 
                patient.pname, schedule.scheduledate, schedule.scheduletime, 
                appointment.apponum, appointment.appodate 
                FROM schedule 
                INNER JOIN appointment ON schedule.scheduleid=appointment.scheduleid 
                INNER JOIN patient ON patient.pid=appointment.pid 
                INNER JOIN doctor ON schedule.docid=doctor.docid 
                WHERE doctor.docid=?";
    } else {
        // Исходный формат для других мест
        $sql = "SELECT a.*, p.pname, p.pid, s.scheduledate, s.scheduletime 
                FROM appointment a 
                JOIN patient p ON a.pid = p.pid 
                JOIN schedule s ON a.scheduleid = s.scheduleid 
                WHERE s.docid = ?";
    }
    
    $params = [$docid];
    $types = "i";
    
    if ($date) {
        $sql .= " AND " . ($from_appointment_page ? "schedule.scheduledate" : "s.scheduledate") . "=?";
        $params[] = $date;
        $types .= "s";
    } elseif (!$include_completed && !$from_appointment_page) {
        $sql .= " AND s.scheduledate >= CURDATE()";
    }
    
    // Добавляем сортировку
    $sql .= " ORDER BY " . ($from_appointment_page ? "schedule.scheduledate" : "s.scheduledate") . 
            ", " . ($from_appointment_page ? "schedule.scheduletime" : "s.scheduletime");
    
    $stmt = $database->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
      // Всегда возвращаем результат запроса для единообразия
    return $stmt->get_result();
}

/**
 * Создает новую запись
 * @param object $database Объект подключения к БД
 * @param int $pid ID пациента
 * @param int $scheduleid ID расписания
 * @param string $reason Причина посещения
 * @return bool|int ID созданной записи или false в случае ошибки
 */
// function create_appointment($database, $pid, $scheduleid, $reason = '') {
//     // Проверяем, свободно ли это время
//     $check = $database->prepare("SELECT * FROM appointment WHERE scheduleid = ?");
//     $check->bind_param("i", $scheduleid);
//     $check->execute();
//     if ($check->get_result()->num_rows > 0) {
//         // Это время уже занято
//         return false;
//     }
    
//     // Поле docid отсутствует в таблице appointment, получаем его от расписания при необходимости
//     $stmt = $database->prepare("INSERT INTO appointment (pid, scheduleid, appodate) VALUES (?, ?, CURDATE())");
//     $stmt->bind_param("ii", $pid, $scheduleid);
    
//     if ($stmt->execute()) {
//         return $stmt->insert_id;
//     }
    
//     return false;
// }

/**
 * Отменяет запись
 * @param object $database Объект подключения к БД
 * @param int $appointment_id ID записи
 * @param bool $update_schedule Обновлять ли статус расписания
 * @return bool Результат операции
 */
function cancel_appointment($database, $appointment_id, $update_schedule = true) {
    // Начинаем транзакцию
    $database->begin_transaction();
    
    try {
        // Если нужно обновить статус расписания, сначала получаем ID расписания
        if ($update_schedule) {
            $stmt = $database->prepare("SELECT scheduleid FROM appointment WHERE appoid = ?");
            $stmt->bind_param("i", $appointment_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $scheduleid = $row['scheduleid'];
                
                // Обновляем статус в таблице schedule
                $stmt = $database->prepare("UPDATE schedule SET status = 1 WHERE scheduleid = ?");
                $stmt->bind_param("i", $scheduleid);
                $stmt->execute();
            }
        }
        
        // Удаляем запись
        $stmt = $database->prepare("DELETE FROM appointment WHERE appoid = ?");
        $stmt->bind_param("i", $appointment_id);
        $result = $stmt->execute();
        
        // Если всё прошло успешно, подтверждаем транзакцию
        $database->commit();
        return $result;
    } catch (Exception $e) {
        // В случае ошибки откатываем все изменения
        $database->rollback();
        return false;
    }
}

/**
 * Проверяет, можно ли записаться на это время
 * @param object $database Объект подключения к БД
 * @param int $scheduleid ID расписания
 * @return bool True если время свободно, false если занято
 */
function is_schedule_available($database, $scheduleid) {
    $stmt = $database->prepare("SELECT * FROM appointment WHERE scheduleid = ?");
    $stmt->bind_param("i", $scheduleid);
    $stmt->execute();
    return $stmt->get_result()->num_rows === 0;
}

/**
 * Получает доступное расписание врача (не занятое другими пациентами)
 * @param object $database Объект подключения к БД
 * @param int $docid ID врача
 * @param string|null $date Дата (опционально)
 * @return array Доступное расписание
 */
function get_available_schedule($database, $docid, $date = null) {
    $sql = "SELECT s.* 
            FROM schedule s
            LEFT JOIN appointment a ON s.scheduleid = a.scheduleid
            WHERE s.docid = ? AND a.scheduleid IS NULL";
    
    $params = [$docid];
    $types = "i";
    
    if ($date) {
        $sql .= " AND s.scheduledate = ?";
        $params[] = $date;
        $types .= "s";
    } else {
        $sql .= " AND s.scheduledate >= CURDATE()";
    }
    
    $sql .= " ORDER BY s.scheduledate, s.scheduletime";
    
    $stmt = $database->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $schedule = [];
    while ($row = $result->fetch_assoc()) {
        $schedule[] = $row;
    }
    
    return $schedule;
}

/**
 * Получает данные пациента по email
 * @param object $database Объект подключения к БД
 * @param string $email Email пациента
 * @return array Данные пациента
 */
function get_patient_by_email($database, $email) {
    $sql = "SELECT * FROM patient WHERE pemail=?";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Получает данные врача по email
 * @param object $database Объект подключения к БД
 * @param string $email Email врача
 * @return array Данные врача
 */
function get_doctor_by_email($database, $email) {
    $sql = "SELECT * FROM doctor WHERE docemail=?";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Подсчитывает общее количество записей
 * @param object $database Объект подключения к БД
 * @return int Количество записей
 */
function count_all_appointments($database) {
    $query = "SELECT COUNT(*) as count FROM appointment";
    $result = $database->query($query);
    $row = $result->fetch_assoc();
    return $row['count'];
}

/**
 * Получает все записи для администратора
 * @param object $database Объект подключения к БД
 * @return object Результат запроса с записями
 */
function get_all_appointments($database) {
    $sql = "SELECT appointment.appoid, schedule.scheduleid, doctor.docname, 
            patient.pname, schedule.scheduledate, schedule.scheduletime, 
            appointment.apponum, appointment.appodate, sp.sname as specialty_name
            FROM schedule 
            INNER JOIN appointment ON schedule.scheduleid=appointment.scheduleid 
            INNER JOIN patient ON patient.pid=appointment.pid 
            INNER JOIN doctor ON schedule.docid=doctor.docid 
            LEFT JOIN specialties sp ON schedule.specialty_id = sp.id
            ORDER BY schedule.scheduledate DESC";
    
    return $database->query($sql);
}

/**
 * Получает отфильтрованные записи для администратора
 * @param object $database Объект подключения к БД
 * @param string|null $date Дата для фильтрации
 * @param int|null $docid ID врача для фильтрации
 * @return object Результат запроса с записями
 */
function get_filtered_admin_appointments($database, $date = null, $docid = null) {
    $sql = "SELECT appointment.appoid, schedule.scheduleid, doctor.docname, 
            patient.pname, schedule.scheduledate, schedule.scheduletime, 
            appointment.apponum, appointment.appodate, sp.sname as specialty_name
            FROM schedule 
            INNER JOIN appointment ON schedule.scheduleid=appointment.scheduleid 
            INNER JOIN patient ON patient.pid=appointment.pid 
            INNER JOIN doctor ON schedule.docid=doctor.docid
            LEFT JOIN specialties sp ON schedule.specialty_id = sp.id";
    
    $conditions = [];
    if ($date !== null) {
        $conditions[] = "schedule.scheduledate='" . $database->real_escape_string($date) . "'";
    }
    if ($docid !== null) {
        $conditions[] = "doctor.docid=" . intval($docid);
    }
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $sql .= " ORDER BY schedule.scheduledate DESC";
    
    return $database->query($sql);
}

/**
 * Завершает бронирование записи на прием
 * @param object $database Объект подключения к БД
 * @param int $userid ID пациента
 * @param int $apponum Номер записи
 * @param int $scheduleid ID расписания
 * @param string $date Дата записи
 * @return bool Результат операции
 */
function complete_booking($database, $userid, $apponum, $scheduleid, $date) {
    // Начинаем транзакцию
    $database->begin_transaction();
    
    try {
        // Добавляем запись в таблицу appointment
        $sql2 = "INSERT INTO appointment(pid, apponum, scheduleid, appodate) VALUES (?, ?, ?, ?)";
        $stmt2 = $database->prepare($sql2);
        $stmt2->bind_param("iiis", $userid, $apponum, $scheduleid, $date);
        $result2 = $stmt2->execute();
        
        if (!$result2) {
            // Если не удалось добавить запись, откатываем транзакцию
            $database->rollback();
            return false;
        }
        
        // Обновляем статус расписания
        $sql3 = "UPDATE schedule SET status = 0 WHERE scheduleid = ?";
        $stmt3 = $database->prepare($sql3);
        $stmt3->bind_param("i", $scheduleid);
        $result3 = $stmt3->execute();
        
        if (!$result3) {
            // Если не удалось обновить статус, откатываем транзакцию
            $database->rollback();
            return false;
        }
        
        // Если всё прошло успешно, подтверждаем транзакцию
        $database->commit();
        return true;
    } catch (Exception $e) {
        // В случае ошибки откатываем транзакцию
        $database->rollback();
        return false;
    }
}
