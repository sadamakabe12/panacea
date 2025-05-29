<?php
/**
 * Функции для работы с медицинскими записями
 */

/**
 * Создает новую медицинскую запись
 * @param object $database Объект подключения к БД
 * @param int $patient_id ID пациента
 * @param int $doctor_id ID врача
 * @param int|null $appointment_id ID записи на прием (опционально)
 * @param string $chief_complaint Основная жалоба пациента
 * @param string $anamnesis История заболевания
 * @param string $examination_results Результаты осмотра
 * @param bool $is_finalized Завершена ли запись
 * @return int|bool ID созданной записи или false в случае ошибки
 */
function create_medical_record($database, $patient_id, $doctor_id, $appointment_id = null, $chief_complaint = '', $anamnesis = '', $examination_results = '', $is_finalized = false) {
    $sql = "INSERT INTO medical_records (patient_id, doctor_id, appointment_id, chief_complaint, anamnesis, examination_results, is_finalized) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("iiisssi", $patient_id, $doctor_id, $appointment_id, $chief_complaint, $anamnesis, $examination_results, $is_finalized);
    
    if ($stmt->execute()) {
        return $database->insert_id;
    } else {
        error_log("Ошибка создания медицинской записи: " . $stmt->error);
        return false;
    }
}

/**
 * Check if a doctor-patient relationship exists
 * @param object $database Database connection
 * @param int $doctor_id Doctor ID
 * @param int $patient_id Patient ID
 * @return bool True if relationship exists, false otherwise
 */
function check_doctor_patient_relationship($database, $doctor_id, $patient_id) {
    $query = "SELECT COUNT(*) as count FROM doctor_patient 
              WHERE doctor_id = ? AND patient_id = ?";
    $stmt = $database->prepare($query);
    $stmt->bind_param("ii", $doctor_id, $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    return ($data['count'] > 0);
}
/**
 * Обновляет существующую медицинскую запись
 * @param object $database Объект подключения к БД
 * @param int $record_id ID медицинской записи
 * @param string $chief_complaint Основная жалоба пациента
 * @param string $anamnesis История заболевания
 * @param string $examination_results Результаты осмотра
 * @param bool $is_finalized Завершена ли запись
 * @return bool Успешно ли выполнено обновление
 */
function update_medical_record($database, $record_id, $chief_complaint, $anamnesis, $examination_results, $is_finalized) {
    $sql = "UPDATE medical_records 
            SET chief_complaint = ?, anamnesis = ?, examination_results = ?, is_finalized = ? 
            WHERE record_id = ?";
    $stmt = $database->prepare($sql);
    $finalized_int = $is_finalized ? 1 : 0;
    $stmt->bind_param("sssii", $chief_complaint, $anamnesis, $examination_results, $finalized_int, $record_id);
    
    if ($stmt->execute()) {
        return true;
    } else {
        error_log("Ошибка обновления медицинской записи: " . $stmt->error);
        return false;
    }
}

/**
 * Получает медицинскую запись по ID
 * @param object $database Объект подключения к БД
 * @param int $record_id ID медицинской записи
 * @return array|null Данные записи или null если не найдена
 */
function get_medical_record($database, $record_id) {
    $sql = "SELECT mr.*, p.pname as patient_name, d.docname as doctor_name 
            FROM medical_records mr
            JOIN patient p ON mr.patient_id = p.pid
            JOIN doctor d ON mr.doctor_id = d.docid
            WHERE mr.record_id = ?";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("i", $record_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Получает все медицинские записи пациента
 * @param object $database Объект подключения к БД
 * @param int $patient_id ID пациента
 * @return array Массив медицинских записей
 */
function get_patient_medical_records($database, $patient_id) {
    $sql = "SELECT mr.*, d.docname as doctor_name 
            FROM medical_records mr
            JOIN doctor d ON mr.doctor_id = d.docid
            WHERE mr.patient_id = ?
            ORDER BY mr.record_date DESC";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    
    return $records;
}

/**
 * Получает все медицинские записи врача
 * @param object $database Объект подключения к БД
 * @param int $doctor_id ID врача
 * @param bool $only_recent Только недавние записи (за последний месяц)
 * @return array Массив медицинских записей
 */
function get_doctor_medical_records($database, $doctor_id, $only_recent = false) {
    $sql = "SELECT mr.*, p.pname as patient_name 
            FROM medical_records mr
            JOIN patient p ON mr.patient_id = p.pid
            WHERE mr.doctor_id = ?";
    
    if ($only_recent) {
        $sql .= " AND mr.record_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
    }
    
    $sql .= " ORDER BY mr.record_date DESC";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    
    return $records;
}

/**
 * Добавляет диагноз в медицинскую запись
 * @param object $database Объект подключения к БД
 * @param int $record_id ID медицинской записи
 * @param string $diagnosis_name Название диагноза
 * @param string $icd10_code Код МКБ-10 (опционально)
 * @param string $diagnosis_type Тип диагноза ('primary', 'secondary', 'complication')
 * @return int|bool ID созданного диагноза или false в случае ошибки
 */
function add_diagnosis($database, $record_id, $diagnosis_name, $icd10_code = null, $diagnosis_type = 'primary') {
    $sql = "INSERT INTO diagnoses (record_id, diagnosis_name, icd10_code, diagnosis_type) 
            VALUES (?, ?, ?, ?)";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("isss", $record_id, $diagnosis_name, $icd10_code, $diagnosis_type);
    
    if ($stmt->execute()) {
        return $database->insert_id;
    } else {
        error_log("Ошибка добавления диагноза: " . $stmt->error);
        return false;
    }
}

/**
 * Получает диагнозы для медицинской записи
 * @param object $database Объект подключения к БД
 * @param int $record_id ID медицинской записи
 * @return array Массив диагнозов
 */
function get_record_diagnoses($database, $record_id) {
    $sql = "SELECT d.*, i.description as icd10_description 
            FROM diagnoses d
            LEFT JOIN icd10_codes i ON d.icd10_code = i.code
            WHERE d.record_id = ?
            ORDER BY d.created_at DESC";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("i", $record_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $diagnoses = [];
    while ($row = $result->fetch_assoc()) {
        $diagnoses[] = $row;
    }
    
    return $diagnoses;
}

/**
 * Добавляет рецепт в медицинскую запись
 * @param object $database Объект подключения к БД
 * @param int $record_id ID медицинской записи
 * @param string $medication_name Название лекарства
 * @param string $dosage Дозировка
 * @param string $frequency Частота приема
 * @param string $duration Продолжительность лечения
 * @param string $instructions Инструкции по применению
 * @return int|bool ID созданного рецепта или false в случае ошибки
 */
function add_prescription($database, $record_id, $medication_name, $dosage, $frequency, $duration, $instructions = '') {
    $sql = "INSERT INTO prescriptions (record_id, medication_name, dosage, frequency, duration, instructions) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("isssss", $record_id, $medication_name, $dosage, $frequency, $duration, $instructions);
    
    if ($stmt->execute()) {
        return $database->insert_id;
    } else {
        error_log("Ошибка добавления рецепта: " . $stmt->error);
        return false;
    }
}

/**
 * Получает рецепты для медицинской записи
 * @param object $database Объект подключения к БД
 * @param int $record_id ID медицинской записи
 * @return array Массив рецептов
 */
function get_record_prescriptions($database, $record_id) {
    $sql = "SELECT * FROM prescriptions WHERE record_id = ? ORDER BY created_at DESC";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("i", $record_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $prescriptions = [];
    while ($row = $result->fetch_assoc()) {
        $prescriptions[] = $row;
    }
    
    return $prescriptions;
}

/**
 * Добавляет лабораторный тест в медицинскую запись
 * @param object $database Объект подключения к БД
 * @param int $record_id ID медицинской записи
 * @param string $test_name Название теста
 * @param string $test_date Дата теста
 * @param string $test_result Результат теста
 * @param string $reference_range Референсные значения
 * @param bool $is_abnormal Является ли результат аномальным
 * @param string $notes Примечания
 * @return int|bool ID созданного теста или false в случае ошибки
 */
function add_lab_test($database, $record_id, $test_name, $test_date, $test_result = null, $reference_range = null, $is_abnormal = false, $notes = null) {
    $sql = "INSERT INTO lab_tests (record_id, test_name, test_date, test_result, reference_range, is_abnormal, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $database->prepare($sql);
    $abnormal_int = $is_abnormal ? 1 : 0;
    $stmt->bind_param("isssssi", $record_id, $test_name, $test_date, $test_result, $reference_range, $abnormal_int, $notes);
    
    if ($stmt->execute()) {
        return $database->insert_id;
    } else {
        error_log("Ошибка добавления лабораторного теста: " . $stmt->error);
        return false;
    }
}

/**
 * Получает лабораторные тесты для медицинской записи
 * @param object $database Объект подключения к БД
 * @param int $record_id ID медицинской записи
 * @return array Массив лабораторных тестов
 */
function get_record_lab_tests($database, $record_id) {
    $sql = "SELECT * FROM lab_tests WHERE record_id = ? ORDER BY test_date DESC";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("i", $record_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $lab_tests = [];
    while ($row = $result->fetch_assoc()) {
        $lab_tests[] = $row;
    }
    
    return $lab_tests;
}

/**
 * Добавляет визуальное исследование в медицинскую запись
 * @param object $database Объект подключения к БД
 * @param int $record_id ID медицинской записи
 * @param string $study_type Тип исследования ('x-ray', 'ultrasound', 'mri', 'ct', 'other')
 * @param string $study_date Дата исследования
 * @param string $study_result Результат исследования
 * @param string $image_path Путь к изображению
 * @param string $notes Примечания
 * @return int|bool ID созданного исследования или false в случае ошибки
 */
function add_imaging_study($database, $record_id, $study_type, $study_date, $study_result = null, $image_path = null, $notes = null) {
    $sql = "INSERT INTO imaging_studies (record_id, study_type, study_date, study_result, image_path, notes) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("isssss", $record_id, $study_type, $study_date, $study_result, $image_path, $notes);
    
    if ($stmt->execute()) {
        return $database->insert_id;
    } else {
        error_log("Ошибка добавления визуального исследования: " . $stmt->error);
        return false;
    }
}

/**
 * Получает визуальные исследования для медицинской записи
 * @param object $database Объект подключения к БД
 * @param int $record_id ID медицинской записи
 * @return array Массив визуальных исследований
 */
function get_record_imaging_studies($database, $record_id) {
    $sql = "SELECT * FROM imaging_studies WHERE record_id = ? ORDER BY study_date DESC";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("i", $record_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $imaging_studies = [];
    while ($row = $result->fetch_assoc()) {
        $imaging_studies[] = $row;
    }
    
    return $imaging_studies;
}

/**
 * Добавляет план лечения в медицинскую запись
 * @param object $database Объект подключения к БД
 * @param int $record_id ID медицинской записи
 * @param string $plan_description Описание плана
 * @param string $start_date Дата начала
 * @param string $end_date Дата окончания
 * @param string $status Статус ('planned', 'in_progress', 'completed', 'cancelled')
 * @return int|bool ID созданного плана или false в случае ошибки
 */
function add_treatment_plan($database, $record_id, $plan_description, $start_date = null, $end_date = null, $status = 'planned') {
    $sql = "INSERT INTO treatment_plans (record_id, plan_description, start_date, end_date, status) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("issss", $record_id, $plan_description, $start_date, $end_date, $status);
    
    if ($stmt->execute()) {
        return $database->insert_id;
    } else {
        error_log("Ошибка добавления плана лечения: " . $stmt->error);
        return false;
    }
}

/**
 * Получает планы лечения для медицинской записи
 * @param object $database Объект подключения к БД
 * @param int $record_id ID медицинской записи
 * @return array Массив планов лечения
 */
function get_record_treatment_plans($database, $record_id) {
    $sql = "SELECT * FROM treatment_plans WHERE record_id = ? ORDER BY created_at DESC";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("i", $record_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $treatment_plans = [];
    while ($row = $result->fetch_assoc()) {
        $treatment_plans[] = $row;
    }
    
    return $treatment_plans;
}

/**
 * Поиск кодов МКБ-10 по ключевому слову
 * @param object $database Объект подключения к БД
 * @param string $keyword Ключевое слово для поиска
 * @return array Массив найденных кодов МКБ-10
 */
function search_icd10_codes($database, $keyword) {
    $query = "%$keyword%";
    $sql = "SELECT * FROM icd10_codes WHERE code LIKE ? OR description LIKE ? ORDER BY code LIMIT 20";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("ss", $query, $query);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $codes = [];
    while ($row = $result->fetch_assoc()) {
        $codes[] = $row;
    }
    
    return $codes;
}

/**
 * Получает историю болезни пациента
 * @param object $database Объект подключения к БД
 * @param int $patient_id ID пациента
 * @return array Массив всей истории болезни
 */
function get_patient_medical_history($database, $patient_id) {
    // Получаем все медицинские записи пациента
    $records = get_patient_medical_records($database, $patient_id);
    
    $history = [];
    foreach ($records as $record) {
        $record_id = $record['record_id'];
        
        // Получаем диагнозы, рецепты, тесты и т.д. для каждой записи
        $record['diagnoses'] = get_record_diagnoses($database, $record_id);
        $record['prescriptions'] = get_record_prescriptions($database, $record_id);
        $record['lab_tests'] = get_record_lab_tests($database, $record_id);
        $record['imaging_studies'] = get_record_imaging_studies($database, $record_id);
        $record['treatment_plans'] = get_record_treatment_plans($database, $record_id);
        
        $history[] = $record;
    }
    
    return $history;
}

/**
 * Получает общее количество медицинских записей
 * @param object $database Объект подключения к БД
 * @return int Количество записей
 */
function get_total_medical_records_count($database) {
    $sql = "SELECT COUNT(*) as count FROM medical_records";
    $result = $database->query($sql);
    $row = $result->fetch_assoc();
    return $row['count'];
}

/**
 * Получает общее количество диагнозов
 * @param object $database Объект подключения к БД
 * @return int Количество диагнозов
 */
function get_total_diagnoses_count($database) {
    $sql = "SELECT COUNT(*) as count FROM diagnoses";
    $result = $database->query($sql);
    $row = $result->fetch_assoc();
    return $row['count'];
}

/**
 * Получает общее количество рецептов
 * @param object $database Объект подключения к БД
 * @return int Количество рецептов
 */
function get_total_prescriptions_count($database) {
    $sql = "SELECT COUNT(*) as count FROM prescriptions";
    $result = $database->query($sql);
    $row = $result->fetch_assoc();
    return $row['count'];
}

/**
 * Получает список всех медицинских записей для админ-панели с учетом фильтров
 * @param object $database Объект подключения к БД
 * @param int|null $patient_id ID пациента для фильтрации (опционально)
 * @param int|null $doctor_id ID врача для фильтрации (опционально)
 * @param string|null $date_from Дата начала периода (опционально)
 * @param string|null $date_to Дата окончания периода (опционально)
 * @param int $limit Количество записей на странице
 * @param int $offset Смещение для пагинации
 * @return array Массив медицинских записей
 */
function get_all_medical_records_admin($database, $patient_id = null, $doctor_id = null, $date_from = null, $date_to = null, $limit = 10, $offset = 0) {
    $where_clauses = [];
    $params = [];
    $types = "";
    
    // Базовый SQL запрос с соединениями для получения имен пациентов и врачей
    $sql = "SELECT mr.*, 
                  p.pname as patient_name, 
                  d.docname as doctor_name,
                  (SELECT COUNT(*) FROM diagnoses WHERE record_id = mr.record_id) as diagnoses_count,
                  (SELECT COUNT(*) FROM prescriptions WHERE record_id = mr.record_id) as prescriptions_count
           FROM medical_records mr
           JOIN patient p ON mr.patient_id = p.pid
           JOIN doctor d ON mr.doctor_id = d.docid";
    
    // Добавление условий фильтрации
    if ($patient_id) {
        $where_clauses[] = "mr.patient_id = ?";
        $params[] = $patient_id;
        $types .= "i";
    }
    
    if ($doctor_id) {
        $where_clauses[] = "mr.doctor_id = ?";
        $params[] = $doctor_id;
        $types .= "i";
    }
    
    if ($date_from) {
        $where_clauses[] = "DATE(mr.record_date) >= ?";
        $params[] = $date_from;
        $types .= "s";
    }
    
    if ($date_to) {
        $where_clauses[] = "DATE(mr.record_date) <= ?";
        $params[] = $date_to;
        $types .= "s";
    }
    
    // Объединение условий WHERE
    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }
    
    // Добавление сортировки и пагинации
    $sql .= " ORDER BY mr.record_date DESC LIMIT ?, ?";
    $params[] = $offset;
    $params[] = $limit;
    $types .= "ii";
    
    $stmt = $database->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    
    return $records;
}

/**
 * Получает количество отфильтрованных медицинских записей для пагинации
 * @param object $database Объект подключения к БД
 * @param int|null $patient_id ID пациента для фильтрации (опционально)
 * @param int|null $doctor_id ID врача для фильтрации (опционально)
 * @param string|null $date_from Дата начала периода (опционально)
 * @param string|null $date_to Дата окончания периода (опционально)
 * @return int Количество записей
 */
function get_filtered_medical_records_count($database, $patient_id = null, $doctor_id = null, $date_from = null, $date_to = null) {
    $where_clauses = [];
    $params = [];
    $types = "";
    
    // Базовый SQL запрос
    $sql = "SELECT COUNT(*) as count FROM medical_records mr";
    
    // Добавление условий фильтрации
    if ($patient_id) {
        $where_clauses[] = "mr.patient_id = ?";
        $params[] = $patient_id;
        $types .= "i";
    }
    
    if ($doctor_id) {
        $where_clauses[] = "mr.doctor_id = ?";
        $params[] = $doctor_id;
        $types .= "i";
    }
    
    if ($date_from) {
        $where_clauses[] = "DATE(mr.record_date) >= ?";
        $params[] = $date_from;
        $types .= "s";
    }
    
    if ($date_to) {
        $where_clauses[] = "DATE(mr.record_date) <= ?";
        $params[] = $date_to;
        $types .= "s";
    }
    
    // Объединение условий WHERE
    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }
    
    $stmt = $database->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'];
}

/**
 * Получает простой список врачей для фильтров
 * @param object $database Объект подключения к БД
 * @return array Массив врачей с docid и docname
 */
function get_all_doctors_simple($database) {
    $sql = "SELECT docid, docname FROM doctor ORDER BY docname";
    $result = $database->query($sql);
    
    $doctors = [];
    while ($row = $result->fetch_assoc()) {
        $doctors[] = $row;
    }
    
    return $doctors;
}

/**
 * Получает простой список пациентов для фильтров
 * @param object $database Объект подключения к БД
 * @return array Массив пациентов с pid и pname
 */
function get_all_patients_simple($database) {
    $sql = "SELECT pid, pname FROM patient ORDER BY pname";
    $result = $database->query($sql);
    
    $patients = [];
    while ($row = $result->fetch_assoc()) {
        $patients[] = $row;
    }
    
    return $patients;
}

/**
 * Функции для журнала аудита медицинских записей
 */

/**
 * Записывает действие в журнал аудита
 * 
 * @param object $database Объект подключения к БД
 * @param int $user_id ID пользователя (docid, pid или email для admin)
 * @param string $user_type Тип пользователя (admin, doctor, patient)
 * @param int $record_id ID медицинской записи
 * @param string $action Тип действия (view, create, update, export, delete)
 * @param string $action_details Дополнительные детали действия (опционально)
 * @return bool Успешно ли выполнено добавление записи
 */
function log_medical_record_action($database, $user_id, $user_type, $record_id, $action, $action_details = null) {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    
    $sql = "INSERT INTO medical_records_audit (user_id, user_type, record_id, action, action_details, ip_address) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("isssss", $user_id, $user_type, $record_id, $action, $action_details, $ip_address);
    
    if ($stmt->execute()) {
        return true;
    } else {
        error_log("Ошибка записи в журнал аудита: " . $stmt->error);
        return false;
    }
}

/**
 * Получает журнал аудита для конкретной медицинской записи
 * 
 * @param object $database Объект подключения к БД
 * @param int $record_id ID медицинской записи
 * @return array Массив записей журнала аудита
 */
function get_medical_record_audit_log($database, $record_id) {
    $sql = "SELECT * FROM medical_records_audit WHERE record_id = ? ORDER BY timestamp DESC";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("i", $record_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $audit_logs = [];
    while ($row = $result->fetch_assoc()) {
        $audit_logs[] = $row;
    }
    
    return $audit_logs;
}

/**
 * Получает журнал аудита для конкретного пользователя
 * 
 * @param object $database Объект подключения к БД
 * @param int $user_id ID пользователя
 * @param string $user_type Тип пользователя (admin, doctor, patient)
 * @param int $limit Ограничение количества записей
 * @return array Массив записей журнала аудита
 */
function get_user_audit_log($database, $user_id, $user_type, $limit = 100) {
    $sql = "SELECT a.*, mr.patient_id, p.pname as patient_name, d.docname as doctor_name
            FROM medical_records_audit a
            JOIN medical_records mr ON a.record_id = mr.record_id
            JOIN patient p ON mr.patient_id = p.pid
            JOIN doctor d ON mr.doctor_id = d.docid
            WHERE a.user_id = ? AND a.user_type = ?
            ORDER BY a.timestamp DESC
            LIMIT ?";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("isi", $user_id, $user_type, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $audit_logs = [];
    while ($row = $result->fetch_assoc()) {
        $audit_logs[] = $row;
    }
    
    return $audit_logs;
}

/**
 * Получает журнал аудита для администратора (все записи)
 * 
 * @param object $database Объект подключения к БД
 * @param int $limit Ограничение количества записей
 * @param int $offset Смещение для пагинации
 * @param array $filters Дополнительные фильтры (user_type, action, date_from, date_to)
 * @return array Массив записей журнала аудита
 */
function get_admin_audit_log($database, $limit = 100, $offset = 0, $filters = []) {
    $where_clauses = [];
    $params = [];
    $types = "";
    
    $sql = "SELECT a.*, mr.patient_id, p.pname as patient_name, d.docname as doctor_name
            FROM medical_records_audit a
            JOIN medical_records mr ON a.record_id = mr.record_id
            JOIN patient p ON mr.patient_id = p.pid
            JOIN doctor d ON mr.doctor_id = d.docid";
    
    // Применение фильтров
    if (!empty($filters['user_type'])) {
        $where_clauses[] = "a.user_type = ?";
        $params[] = $filters['user_type'];
        $types .= "s";
    }
    
    if (!empty($filters['action'])) {
        $where_clauses[] = "a.action = ?";
        $params[] = $filters['action'];
        $types .= "s";
    }
    
    if (!empty($filters['date_from'])) {
        $where_clauses[] = "DATE(a.timestamp) >= ?";
        $params[] = $filters['date_from'];
        $types .= "s";
    }
    
    if (!empty($filters['date_to'])) {
        $where_clauses[] = "DATE(a.timestamp) <= ?";
        $params[] = $filters['date_to'];
        $types .= "s";
    }
    
    // Объединение условий WHERE
    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }
    
    // Добавление сортировки и пагинации
    $sql .= " ORDER BY a.timestamp DESC LIMIT ?, ?";
    $params[] = $offset;
    $params[] = $limit;
    $types .= "ii";
    
    $stmt = $database->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $audit_logs = [];
    while ($row = $result->fetch_assoc()) {
        $audit_logs[] = $row;
    }
    
    return $audit_logs;
}

/**
 * Получает количество записей журнала аудита с учетом фильтров
 * 
 * @param object $database Объект подключения к БД
 * @param array $filters Дополнительные фильтры (user_type, action, date_from, date_to)
 * @return int Количество записей
 */
function get_filtered_audit_log_count($database, $filters = []) {
    $where_clauses = [];
    $params = [];
    $types = "";
    
    $sql = "SELECT COUNT(*) as count FROM medical_records_audit a";
    
    // Применение фильтров
    if (!empty($filters['user_type'])) {
        $where_clauses[] = "a.user_type = ?";
        $params[] = $filters['user_type'];
        $types .= "s";
    }
    
    if (!empty($filters['action'])) {
        $where_clauses[] = "a.action = ?";
        $params[] = $filters['action'];
        $types .= "s";
    }
    
    if (!empty($filters['date_from'])) {
        $where_clauses[] = "DATE(a.timestamp) >= ?";
        $params[] = $filters['date_from'];
        $types .= "s";
    }
    
    if (!empty($filters['date_to'])) {
        $where_clauses[] = "DATE(a.timestamp) <= ?";
        $params[] = $filters['date_to'];
        $types .= "s";
    }
    
    // Объединение условий WHERE
    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }
    
    $stmt = $database->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'];
}

/**
 * Добавляет информацию об аллергии пациента
 * @param object $database Объект подключения к БД
 * @param int $patient_id ID пациента
 * @param string $allergy_name Название аллергии
 * @param string $reaction Реакция на аллерген
 * @param string $severity Степень тяжести (mild, moderate, severe, life-threatening)
 * @param string $date_identified Дата выявления
 * @param string $notes Примечания
 * @return int|bool ID созданной записи или false в случае ошибки
 */
function add_patient_allergy($database, $patient_id, $allergy_name, $reaction = null, $severity = null, $date_identified = null, $notes = null) {
    $sql = "INSERT INTO allergies (patient_id, allergy_name, reaction, severity, date_identified, notes) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("isssss", $patient_id, $allergy_name, $reaction, $severity, $date_identified, $notes);
    
    if ($stmt->execute()) {
        return $database->insert_id;
    } else {
        error_log("Ошибка добавления аллергии: " . $stmt->error);
        return false;
    }
}

/**
 * Получает аллергии пациента
 * @param object $database Объект подключения к БД
 * @param int $patient_id ID пациента
 * @param bool $active_only Только активные аллергии
 * @return array Массив аллергий
 */
function get_patient_allergies($database, $patient_id, $active_only = true) {
    $sql = "SELECT * FROM allergies WHERE patient_id = ?";
    
    if ($active_only) {
        $sql .= " AND status = 'active'";
    }
    
    $sql .= " ORDER BY date_identified DESC, created_at DESC";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $allergies = [];
    while ($row = $result->fetch_assoc()) {
        $allergies[] = $row;
    }
    
    return $allergies;
}

/**
 * Обновляет статус аллергии пациента
 * @param object $database Объект подключения к БД
 * @param int $allergy_id ID аллергии
 * @param string $status Статус (active, inactive)
 * @return bool Успешно ли выполнено обновление
 */
function update_allergy_status($database, $allergy_id, $status) {
    $sql = "UPDATE allergies SET status = ? WHERE allergy_id = ?";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("si", $status, $allergy_id);
    
    if ($stmt->execute()) {
        return true;
    } else {
        error_log("Ошибка обновления статуса аллергии: " . $stmt->error);
        return false;
    }
}

/**
 * Добавляет показатели жизнедеятельности в медицинскую запись
 * @param object $database Объект подключения к БД
 * @param int $record_id ID медицинской записи
 * @param float $temperature Температура тела
 * @param int $heart_rate Пульс
 * @param int $blood_pressure_systolic Систолическое давление
 * @param int $blood_pressure_diastolic Диастолическое давление
 * @param int $respiratory_rate Частота дыхания
 * @param int $oxygen_saturation Насыщение крови кислородом
 * @param float $height Рост в см
 * @param float $weight Вес в кг
 * @param float $bmi Индекс массы тела
 * @param int $pain_level Уровень боли (0-10)
 * @param string $notes Примечания
 * @return int|bool ID созданной записи или false в случае ошибки
 */
function add_vitals($database, $record_id, $temperature = null, $heart_rate = null, $blood_pressure_systolic = null, 
                   $blood_pressure_diastolic = null, $respiratory_rate = null, $oxygen_saturation = null, 
                   $height = null, $weight = null, $bmi = null, $pain_level = null, $notes = null) {
    
    // Автоматический расчет BMI если предоставлены рост и вес
    if ($height && $weight && !$bmi) {
        // Формула: вес (кг) / (рост (м) * рост (м))
        $height_m = $height / 100; // перевод из см в м
        $bmi = round($weight / ($height_m * $height_m), 2);
    }
    
    $sql = "INSERT INTO vitals (record_id, temperature, heart_rate, blood_pressure_systolic, 
                              blood_pressure_diastolic, respiratory_rate, oxygen_saturation, 
                              height, weight, bmi, pain_level, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $database->prepare($sql);
    $stmt->bind_param("idiiiiidddis", $record_id, $temperature, $heart_rate, $blood_pressure_systolic, 
                     $blood_pressure_diastolic, $respiratory_rate, $oxygen_saturation, 
                     $height, $weight, $bmi, $pain_level, $notes);
    
    if ($stmt->execute()) {
        return $database->insert_id;
    } else {
        error_log("Ошибка добавления показателей жизнедеятельности: " . $stmt->error);
        return false;
    }
}

/**
 * Получает показатели жизнедеятельности для медицинской записи
 * @param object $database Объект подключения к БД
 * @param int $record_id ID медицинской записи
 * @return array|null Данные показателей или null если не найдены
 */
function get_vitals($database, $record_id) {
    $sql = "SELECT * FROM vitals WHERE record_id = ? ORDER BY measured_at DESC LIMIT 1";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("i", $record_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Получает историю показателей жизнедеятельности пациента
 * @param object $database Объект подключения к БД
 * @param int $patient_id ID пациента
 * @param int $limit Ограничение количества записей
 * @return array Массив показателей жизнедеятельности
 */
function get_patient_vitals_history($database, $patient_id, $limit = 10) {
    $sql = "SELECT v.* 
            FROM vitals v
            JOIN medical_records mr ON v.record_id = mr.record_id
            WHERE mr.patient_id = ?
            ORDER BY v.measured_at DESC
            LIMIT ?";
    
    $stmt = $database->prepare($sql);
    $stmt->bind_param("ii", $patient_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $vitals = [];
    while ($row = $result->fetch_assoc()) {
        $vitals[] = $row;
    }
    
    return $vitals;
}
