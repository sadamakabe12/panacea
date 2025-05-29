<?php
/**
 * Функции для работы с базой данных
 */

/**
 * Создает подключение к базе данных
 * @return mysqli Объект подключения к базе данных
 */
function connect_database() {
    $server_name = "localhost";
    $username = "root";
    $password = "";
    $database = "bebebe";
    
    // Создаем подключение
    $connection = new mysqli($server_name, $username, $password, $database);
    
    // Проверяем подключение
    if ($connection->connect_error) {
        die("Ошибка подключения к базе данных: " . $connection->connect_error);
    }
    
    // Устанавливаем кодировку
    $connection->set_charset("utf8");
    
    return $connection;
}

/**
 * Получает информацию о докторе по ID
 * @param object $database Объект подключения к БД
 * @param int $docid ID доктора
 * @return array|null Информация о докторе или null, если не найден
 */
function get_doctor_info($database, $docid) {
    $stmt = $database->prepare("SELECT * FROM doctor WHERE docid = ?");
    $stmt->bind_param('i', $docid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Получает список специальностей доктора
 * @param object $database Объект подключения к БД
 * @param int $docid ID доктора
 * @return array Список специальностей
 */
function get_doctor_specialties($database, $docid) {
    $stmt = $database->prepare("
        SELECT s.id, s.sname 
        FROM doctor_specialty ds 
        JOIN specialties s ON ds.specialty_id = s.id 
        WHERE ds.docid = ?
    ");
    $stmt->bind_param('i', $docid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $specialties = [];
    while ($row = $result->fetch_assoc()) {
        $specialties[] = $row;
    }
    
    return $specialties;
}

/**
 * Получает информацию о пациенте по ID
 * @param object $database Объект подключения к БД
 * @param int $pid ID пациента
 * @return array|null Информация о пациенте или null, если не найден
 */
function get_patient_info($database, $pid) {
    $stmt = $database->prepare("SELECT * FROM patient WHERE pid = ?");
    $stmt->bind_param('i', $pid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Получает расписание доктора
 * @param object $database Объект подключения к БД
 * @param int $docid ID доктора
 * @param string|null $date Дата (опционально)
 * @return array Расписание
 */
function get_doctor_schedule($database, $docid, $date = null) {
    $sql = "SELECT * FROM schedule WHERE docid = ?";
    $params = [$docid];
    $types = 'i';
    
    if ($date) {
        $sql .= " AND scheduledate = ?";
        $params[] = $date;
        $types .= 's';
    }
    
    $sql .= " ORDER BY scheduledate, scheduletime";
    
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

// Функция get_patient_appointments перенесена в appointment_functions.php

