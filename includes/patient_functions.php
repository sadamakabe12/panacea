<?php
/**
 * Функции для работы с пациентами
 */

/**
 * Добавляет нового пациента в систему
 * @param object $database Объект подключения к БД
 * @param string $name Имя пациента
 * @param string $email Email пациента
 * @param string $tele Телефон пациента
 * @param string $password Пароль пациента
 * @param string $cpassword Подтверждение пароля
 * @param string $dob Дата рождения пациента
 * @param string $address Адрес пациента (опционально)
 * @return string Код ошибки: '1' - email существует, '2' - пароли не совпадают, '4' - успешно добавлен
 */
function add_patient($database, $name, $email, $tele, $password, $cpassword, $dob, $address = '') {
    $error = '3';
    if ($password === $cpassword) {
        $stmt = $database->prepare("SELECT 1 FROM webuser WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = '1'; // Email уже существует
        } else {
            // Начинаем транзакцию для гарантии целостности данных
            $database->begin_transaction();
            
            try {
                // Добавляем пациента
                $stmt1 = $database->prepare("INSERT INTO patient (pemail, pname, ppassword, ptel, pdob, paddress) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt1->bind_param("ssssss", $email, $name, $password, $tele, $dob, $address);
                $stmt1->execute();
                $stmt1->close();
                
                // Добавляем пользователя
                $stmt2 = $database->prepare("INSERT INTO webuser (email, usertype) VALUES (?, 'p')");
                $stmt2->bind_param("s", $email);
                $stmt2->execute();
                $stmt2->close();
                
                // Подтверждаем транзакцию
                $database->commit();
                $error = '4'; // Успешно добавлен
            } catch (Exception $e) {
                // В случае ошибки откатываем изменения
                $database->rollback();
                $error = '3'; // Общая ошибка
            }
        }
        $stmt->close();
    } else {
        $error = '2'; // Пароли не совпадают
    }
    
    return $error;
}

/**
 * Удаляет пациента из системы
 * @param object $database Объект подключения к БД
 * @param int $id ID пациента для удаления
 * @return bool Результат операции
 */
function delete_patient($database, $id) {
    // Начинаем транзакцию
    $database->begin_transaction();
    $success = false;
    
    try {
        // Получаем email пациента
        $stmt = $database->prepare("SELECT pemail FROM patient WHERE pid = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($email);
        $stmt->fetch();
        $stmt->close();
        
        if ($email) {
            // Удаляем пользователя
            $stmt1 = $database->prepare("DELETE FROM webuser WHERE email = ?");
            $stmt1->bind_param("s", $email);
            $stmt1->execute();
            $stmt1->close();
            
            // Удаляем пациента
            $stmt2 = $database->prepare("DELETE FROM patient WHERE pemail = ?");
            $stmt2->bind_param("s", $email);
            $stmt2->execute();
            $stmt2->close();
            
            // Подтверждаем транзакцию
            $database->commit();
            $success = true;
        } else {
            // Если email не найден, откатываем транзакцию
            $database->rollback();
        }
    } catch (Exception $e) {
        // В случае ошибки откатываем изменения
        $database->rollback();
    }
    
    return $success;
}

/**
 * Удаляет аккаунт пациента и все связанные данные
 * @param object $database Объект подключения к БД
 * @param int $id ID пациента
 * @return bool Результат операции
 */
function delete_patient_account($database, $id) {
    // Начинаем транзакцию
    $database->begin_transaction();
    $success = false;
    
    try {
        // Получаем email пациента
        $stmt = $database->prepare("SELECT pemail FROM patient WHERE pid = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($email);
        $stmt->fetch();
        $stmt->close();
        
        if ($email) {
            // Удаляем записи к врачу для этого пациента
            $stmt1 = $database->prepare("DELETE FROM appointment WHERE pid = ?");
            $stmt1->bind_param("i", $id);
            $stmt1->execute();
            $stmt1->close();
            
            // Удаляем пользователя из таблицы webuser
            $stmt2 = $database->prepare("DELETE FROM webuser WHERE email = ?");
            $stmt2->bind_param("s", $email);
            $stmt2->execute();
            $stmt2->close();
            
            // Удаляем пациента
            $stmt3 = $database->prepare("DELETE FROM patient WHERE pemail = ?");
            $stmt3->bind_param("s", $email);
            $stmt3->execute();
            $stmt3->close();
            
            // Подтверждаем транзакцию
            $database->commit();
            $success = true;
        } else {
            // Если email не найден, откатываем транзакцию
            $database->rollback();
        }
    } catch (Exception $e) {
        // В случае ошибки откатываем изменения
        $database->rollback();
    }
    
    return $success;
}

/**
 * Обновляет данные пациента
 * @param object $database Объект подключения к БД
 * @param int $id ID пациента
 * @param string $name Имя пациента
 * @param string $email Новый email пациента
 * @param string $oldemail Старый email пациента
 * @param string $tele Телефон пациента
 * @param string $address Адрес пациента
 * @param string $password Пароль (опционально)
 * @return string Код ошибки: '1' - email существует, '2' - ошибка пароля, '4' - успешно обновлен
 */
function update_patient($database, $id, $name, $email, $oldemail, $tele, $address, $password = null) {
    // Начинаем транзакцию
    $database->begin_transaction();
    
    try {
        // Проверяем, существует ли уже пользователь с таким email (если email изменился)
        if ($email != $oldemail) {
            $stmt = $database->prepare("SELECT patient.pid FROM patient INNER JOIN webuser ON patient.pemail = webuser.email WHERE webuser.email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $id2 = $result->fetch_assoc()["pid"];
                if ($id2 != $id) {
                    // Email уже используется другим пользователем
                    $database->rollback();
                    return '1';
                }
            }
        }
        
        // Обновляем данные пациента
        if ($password) {
            // Если передан новый пароль, обновляем и его
            $stmt = $database->prepare("UPDATE patient SET pname = ?, pemail = ?, ppassword = ?, ptel = ?, paddress = ? WHERE pid = ?");
            $stmt->bind_param("sssssi", $name, $email, $password, $tele, $address, $id);
        } else {
            // Если пароль не передан, оставляем прежний
            $stmt = $database->prepare("UPDATE patient SET pname = ?, pemail = ?, ptel = ?, paddress = ? WHERE pid = ?");
            $stmt->bind_param("ssssi", $name, $email, $tele, $address, $id);
        }
        $stmt->execute();
        
        // Если email изменился, обновляем его в таблице webuser
        if ($email != $oldemail) {
            $stmt = $database->prepare("UPDATE webuser SET email = ? WHERE email = ?");
            $stmt->bind_param("ss", $email, $oldemail);
            $stmt->execute();
        }
        
        // Подтверждаем транзакцию
        $database->commit();
        return '4'; // Успешно обновлен
    } catch (Exception $e) {
        // В случае ошибки откатываем изменения
        $database->rollback();
        return '3'; // Общая ошибка
    }
}

/**
 * Получает список всех пациентов
 * @param object $database Объект подключения к БД
 * @return array Массив с информацией о пациентах
 */
function get_all_patients($database) {
    $result = $database->query("SELECT * FROM patient ORDER BY pname");
    $patients = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $patients[] = $row;
        }
    }
    
    return $patients;
}

/**
 * Получает информацию о пациенте по ID
 * @param object $database Объект подключения к БД
 * @param int $id ID пациента
 * @return array|null Массив с информацией о пациенте или null, если пациент не найден
 */
function get_patient_by_id($database, $id) {
    $stmt = $database->prepare("SELECT * FROM patient WHERE pid = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}
