<?php
/**
 * Улучшенные функции для работы с данными врача
 */

/**
 * Получает расширенную информацию о враче
 */
function getDoctorExtendedInfo($database, $doctorEmail = null, $doctorId = null) {
    if ($doctorEmail) {
        // Получаем информацию о враче по email
        $sql = "SELECT d.*, 
                       '' as specialties,
                       COUNT(DISTINCT a.appoid) as total_appointments,
                       COUNT(DISTINCT CASE WHEN a.appodate >= CURDATE() THEN a.appoid END) as upcoming_appointments
                FROM doctor d
                LEFT JOIN schedule sch ON d.docid = sch.docid
                LEFT JOIN appointment a ON sch.scheduleid = a.scheduleid
                WHERE d.docemail = ?
                GROUP BY d.docid";
        
        $stmt = $database->prepare($sql);
        $stmt->bind_param("s", $doctorEmail);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    } elseif ($doctorId) {
        // Получаем информацию о враче по ID
        $sql = "SELECT d.*, 
                       '' as specialties,
                       COUNT(DISTINCT a.appoid) as total_appointments,
                       COUNT(DISTINCT CASE WHEN a.appodate >= CURDATE() THEN a.appoid END) as upcoming_appointments
                FROM doctor d
                LEFT JOIN schedule sch ON d.docid = sch.docid
                LEFT JOIN appointment a ON sch.scheduleid = a.scheduleid
                WHERE d.docid = ?
                GROUP BY d.docid";
        
        $stmt = $database->prepare($sql);
        $stmt->bind_param("s", $doctorId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }
    
    return null;
}

/**
 * Получает записи врача с фильтрацией и пагинацией
 */
function getDoctorAppointmentsAdvanced($database, $doctorId, $filters = [], $limit = null, $offset = 0) {
    $sql = "SELECT a.*, p.pname, p.ptel, p.pemail, s.scheduletime,
                   CASE 
                       WHEN a.appodate < CURDATE() THEN 'completed'
                       WHEN a.appodate = CURDATE() AND s.scheduletime < CURTIME() THEN 'completed'
                       WHEN a.appodate = CURDATE() AND s.scheduletime >= CURTIME() THEN 'today'
                       ELSE 'upcoming'
                   END as status
            FROM appointment a
            INNER JOIN patient p ON a.pid = p.pid
            INNER JOIN schedule s ON a.scheduleid = s.scheduleid
            WHERE s.docid = ?";
    
    $params = [$doctorId];
    $types = "s";
    
    // Добавляем фильтры
    if (!empty($filters['date'])) {
        $sql .= " AND a.appodate = ?";
        $params[] = $filters['date'];
        $types .= "s";
    }
    
    if (!empty($filters['status'])) {
        switch ($filters['status']) {
            case 'today':
                $sql .= " AND a.appodate = CURDATE()";
                break;
            case 'upcoming':
                $sql .= " AND a.appodate > CURDATE()";
                break;
            case 'completed':
                $sql .= " AND (a.appodate < CURDATE() OR (a.appodate = CURDATE() AND s.scheduletime < CURTIME()))";
                break;
        }
    }
    
    if (!empty($filters['patient_name'])) {
        $sql .= " AND p.pname LIKE ?";
        $params[] = "%" . $filters['patient_name'] . "%";
        $types .= "s";
    }
    
    $sql .= " ORDER BY a.appodate DESC, s.scheduletime DESC";
    
    if ($limit !== null) {
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";
    }
    
    $stmt = $database->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    
    return $stmt->get_result();
}

/**
 * Получает статистику врача
 */
function getDoctorStatistics($database, $doctorId) {
    $today = date('Y-m-d');
    $thisWeek = date('Y-m-d', strtotime('-7 days'));
    $thisMonth = date('Y-m-d', strtotime('-30 days'));
    
    $stats = [];
      // Всего записей
    $result = $database->query("SELECT COUNT(*) as total FROM appointment a INNER JOIN schedule s ON a.scheduleid = s.scheduleid WHERE s.docid = '$doctorId'");
    $stats['total_appointments'] = $result->fetch_assoc()['total'];
    
    // Записи на сегодня
    $result = $database->query("SELECT COUNT(*) as today FROM appointment a INNER JOIN schedule s ON a.scheduleid = s.scheduleid WHERE s.docid = '$doctorId' AND a.appodate = '$today'");
    $stats['today_appointments'] = $result->fetch_assoc()['today'];
    
    // Предстоящие записи
    $result = $database->query("SELECT COUNT(*) as upcoming FROM appointment a INNER JOIN schedule s ON a.scheduleid = s.scheduleid WHERE s.docid = '$doctorId' AND a.appodate > '$today'");
    $stats['upcoming_appointments'] = $result->fetch_assoc()['upcoming'];
    
    // Записи за неделю
    $result = $database->query("SELECT COUNT(*) as week FROM appointment a INNER JOIN schedule s ON a.scheduleid = s.scheduleid WHERE s.docid = '$doctorId' AND a.appodate >= '$thisWeek'");
    $stats['week_appointments'] = $result->fetch_assoc()['week'];
    
    // Записи за месяц
    $result = $database->query("SELECT COUNT(*) as month FROM appointment a INNER JOIN schedule s ON a.scheduleid = s.scheduleid WHERE s.docid = '$doctorId' AND a.appodate >= '$thisMonth'");
    $stats['month_appointments'] = $result->fetch_assoc()['month'];
    
    // Уникальные пациенты
    $result = $database->query("SELECT COUNT(DISTINCT a.pid) as patients FROM appointment a INNER JOIN schedule s ON a.scheduleid = s.scheduleid WHERE s.docid = '$doctorId'");
    $stats['unique_patients'] = $result->fetch_assoc()['patients'];
    
    // Расписание на сегодня
    $result = $database->query("SELECT COUNT(*) as schedule FROM schedule WHERE docid = $doctorId AND scheduledate = '$today'");
    $stats['today_schedule'] = $result->fetch_assoc()['schedule'];
    
    return $stats;
}

/**
 * Получает пациентов врача
 */
function getDoctorPatients($database, $doctorId, $searchTerm = '', $showOnlyMy = true) {    if ($showOnlyMy) {
        $sql = "SELECT DISTINCT p.*, 
                       COUNT(a.appoid) as appointment_count,
                       MAX(a.appodate) as last_appointment
                FROM patient p
                INNER JOIN appointment a ON p.pid = a.pid
                INNER JOIN schedule s ON a.scheduleid = s.scheduleid
                WHERE s.docid = ?";
        
        $params = [$doctorId];
        $types = "s";
        
        if (!empty($searchTerm)) {
            $sql .= " AND (p.pname LIKE ? OR p.pemail LIKE ? OR p.ptel LIKE ?)";
            $searchParam = "%" . $searchTerm . "%";
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
            $types .= "sss";
        }
        
        $sql .= " GROUP BY p.pid ORDER BY last_appointment DESC";
    } else {
        $sql = "SELECT p.*, 0 as appointment_count, NULL as last_appointment
                FROM patient p
                WHERE 1=1";
        
        $params = [];
        $types = "";
        
        if (!empty($searchTerm)) {
            $sql .= " AND (p.pname LIKE ? OR p.pemail LIKE ? OR p.ptel LIKE ?)";
            $searchParam = "%" . $searchTerm . "%";
            $params = [$searchParam, $searchParam, $searchParam];
            $types = "sss";
        }
        
        $sql .= " ORDER BY p.pname";
    }
    
    $stmt = $database->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    
    return $stmt->get_result();
}

/**
 * Получает расписание врача
 */
function getDoctorSchedule($database, $doctorId, $dateFrom = null, $dateTo = null, $scheduleId = null) {
    $sql = "SELECT s.*, d.docname,
                   COUNT(a.appoid) as booked_slots,
                   GROUP_CONCAT(CONCAT(s.scheduletime, ' - ', p.pname) SEPARATOR '; ') as appointments
            FROM schedule s
            INNER JOIN doctor d ON s.docid = d.docid
            LEFT JOIN appointment a ON s.scheduleid = a.scheduleid
            LEFT JOIN patient p ON a.pid = p.pid
            WHERE s.docid = ?";
    
    $params = [$doctorId];
    $types = "s";
    
    if ($scheduleId) {
        $sql .= " AND s.scheduleid = ?";
        $params[] = $scheduleId;
        $types .= "i";
    }
    
    if ($dateFrom) {
        $sql .= " AND s.scheduledate >= ?";
        $params[] = $dateFrom;
        $types .= "s";
    }
    
    if ($dateTo) {
        $sql .= " AND s.scheduledate <= ?";
        $params[] = $dateTo;
        $types .= "s";
    }
    
    $sql .= " GROUP BY s.scheduleid ORDER BY s.scheduledate ASC, s.scheduletime ASC";
    
    $stmt = $database->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    
    return $stmt->get_result();
}

/**
 * Получает предстоящие сеансы врача
 */
function getDoctorUpcomingSessions($database, $doctorId, $limit = 10) {
    $today = date('Y-m-d');
    $nextWeek = date('Y-m-d', strtotime('+1 week'));
    
    $sql = "SELECT s.scheduleid, d.docname, s.scheduledate, s.scheduletime
            FROM schedule s
            INNER JOIN doctor d ON s.docid = d.docid
            WHERE s.docid = ? AND s.scheduledate >= ? AND s.scheduledate <= ?
            ORDER BY s.scheduledate ASC, s.scheduletime ASC
            LIMIT ?";
      $stmt = $database->prepare($sql);
    $stmt->bind_param("sssi", $doctorId, $today, $nextWeek, $limit);
    $stmt->execute();
    
    return $stmt->get_result();
}

/**
 * Удаляет запись
 */
function cancelAppointment($database, $appointmentId, $doctorId = null) {    if ($doctorId) {
        // Проверяем, что запись принадлежит врачу
        $checkSql = "SELECT a.appoid FROM appointment a INNER JOIN schedule s ON a.scheduleid = s.scheduleid WHERE a.appoid = ? AND s.docid = ?";
        $checkStmt = $database->prepare($checkSql);
        $checkStmt->bind_param("is", $appointmentId, $doctorId);
        $checkStmt->execute();
        
        if ($checkStmt->get_result()->num_rows === 0) {
            return false; // Запись не найдена или не принадлежит врачу
        }
    }
    
    // Удаляем запись
    $deleteSql = "DELETE FROM appointment WHERE appoid = ?";
    $deleteStmt = $database->prepare($deleteSql);
    $deleteStmt->bind_param("i", $appointmentId);
    
    return $deleteStmt->execute();
}

/**
 * Получает быструю статистику для дашборда
 */
function getDashboardQuickStats($database, $doctorId) {
    $today = date('Y-m-d');
    
    // Все врачи
    $allDoctors = $database->query("SELECT COUNT(*) as count FROM doctor")->fetch_assoc()['count'];
    
    // Все пациенты
    $allPatients = $database->query("SELECT COUNT(*) as count FROM patient")->fetch_assoc()['count'];
    
    // Новые записи (на будущее)
    $newAppointments = $database->query("SELECT COUNT(*) as count FROM appointment WHERE appodate >= '$today'")->fetch_assoc()['count'];
      // Сегодняшние записи для врача
    $todayAppointments = $database->query("SELECT COUNT(*) as count FROM schedule WHERE docid = '$doctorId' AND scheduledate = '$today'")->fetch_assoc()['count'];
    
    return [
        'all_doctors' => $allDoctors,
        'all_patients' => $allPatients,
        'new_appointments' => $newAppointments,
        'today_appointments' => $todayAppointments
    ];
}

/**
 * Валидирует данные врача для обновления профиля
 */
function validateDoctorData($database, $email, $phone, $password, $confirmPassword, $doctorId, $oldEmail) {
    // Проверка email
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error_code' => '3', 'message' => 'Некорректный email адрес'];
    }
    
    // Проверка, не занят ли email другим врачом
    if ($email !== $oldEmail) {
        $checkSql = "SELECT docid FROM doctor WHERE docemail = ? AND docid != ?";
        $checkStmt = $database->prepare($checkSql);
        $checkStmt->bind_param("si", $email, $doctorId);
        $checkStmt->execute();
        
        if ($checkStmt->get_result()->num_rows > 0) {
            return ['success' => false, 'error_code' => '1', 'message' => 'Email уже используется другим врачом'];
        }
    }
    
    // Проверка телефона
    if (!empty($phone) && !preg_match('/^[\d\-\+\(\)\s]+$/', $phone)) {
        return ['success' => false, 'error_code' => '3', 'message' => 'Некорректный номер телефона'];
    }
    
    // Проверка пароля
    if (empty($password) || strlen($password) < 6) {
        return ['success' => false, 'error_code' => '3', 'message' => 'Пароль должен содержать минимум 6 символов'];
    }
    
    // Проверка совпадения паролей
    if ($password !== $confirmPassword) {
        return ['success' => false, 'error_code' => '2', 'message' => 'Пароли не совпадают'];
    }
    
    return ['success' => true];
}

/**
 * Обновляет профиль врача
 */
function updateDoctorProfile($database, $doctorId, $email, $phone, $password, $oldEmail) {
    try {
        $database->autocommit(false);
        
        // Хешируем пароль
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Обновляем данные врача
        $updateDoctorSql = "UPDATE doctor SET docemail = ?, doctel = ?, docpassword = ? WHERE docid = ?";
        $updateDoctorStmt = $database->prepare($updateDoctorSql);
        $updateDoctorStmt->bind_param("sssi", $email, $phone, $hashedPassword, $doctorId);
        
        if (!$updateDoctorStmt->execute()) {
            throw new Exception("Ошибка обновления данных врача");
        }
        
        // Обновляем email в таблице webuser, если он изменился
        if ($email !== $oldEmail) {
            $updateUserSql = "UPDATE webuser SET email = ? WHERE email = ?";
            $updateUserStmt = $database->prepare($updateUserSql);
            $updateUserStmt->bind_param("ss", $email, $oldEmail);
            
            if (!$updateUserStmt->execute()) {
                throw new Exception("Ошибка обновления email в системе");
            }
        }
        
        $database->commit();
        $database->autocommit(true);
        
        // Логируем действие
        logDoctorAction($database, $doctorId, 'profile_update', "Email: $email, Phone: $phone");
        
        return true;
        
    } catch (Exception $e) {
        $database->rollback();
        $database->autocommit(true);
        error_log("Ошибка обновления профиля врача: " . $e->getMessage());
        return false;
    }
}

/**
 * Логирует действия врача
 */
function logDoctorAction($database, $doctorId, $action, $details = '') {
    $sql = "INSERT INTO doctor_activity_log (docid, action, details, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("iss", $doctorId, $action, $details);
    return $stmt->execute();
}
?>
