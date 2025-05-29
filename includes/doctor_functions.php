<?php
/**
 * Функции для работы с врачами
 */

/**
 * Добавляет нового врача в систему
 * @param object $database Объект подключения к БД
 * @param string $name Имя врача
 * @param array $spec Массив ID специальностей врача
 * @param string $email Email врача
 * @param string $tele Телефон врача
 * @param string $password Пароль врача
 * @param string $cpassword Подтверждение пароля
 * @return string Код ошибки: '1' - email существует, '2' - пароли не совпадают, '4' - успешно добавлен
 */
function add_doctor($database, $name, $spec, $email, $tele, $password, $cpassword) {
    $error = '3';
    if ($password === $cpassword) {
        $exists = $database->prepare("SELECT 1 FROM webuser WHERE email=?");
        $exists->bind_param("s", $email);
        $exists->execute();
        $exists->store_result();
        if ($exists->num_rows > 0) {
            $error = '1'; // Email уже существует
        } else {
            // Начинаем транзакцию для гарантии целостности данных
            $database->begin_transaction();
            
            try {
                // Добавляем врача
                $stmt1 = $database->prepare("INSERT INTO doctor (docemail, docname, docpassword, doctel) VALUES (?, ?, ?, ?)");
                $stmt1->bind_param("ssss", $email, $name, $password, $tele);
                $stmt1->execute();
                $docid = $database->insert_id;
                $stmt1->close();
                
                // Добавляем пользователя
                $stmt2 = $database->prepare("INSERT INTO webuser (email, usertype) VALUES (?, 'd')");
                $stmt2->bind_param("s", $email);
                $stmt2->execute();
                $stmt2->close();
                
                // Добавляем специальности, если они указаны
                if (!empty($spec)) {
                    foreach ($spec as $spec_id) {
                        $spec_id = intval($spec_id);
                        $stmt3 = $database->prepare("INSERT INTO doctor_specialty (docid, specialty_id) VALUES (?, ?)");
                        $stmt3->bind_param("ii", $docid, $spec_id);
                        $stmt3->execute();
                        $stmt3->close();
                    }
                }
                
                // Подтверждаем транзакцию
                $database->commit();
                $error = '4'; // Успешно добавлен
            } catch (Exception $e) {
                // В случае ошибки откатываем изменения
                $database->rollback();
                $error = '3'; // Общая ошибка
            }
        }
        $exists->close();
    } else {
        $error = '2'; // Пароли не совпадают
    }
    
    return $error;
}

/**
 * Удаляет врача из системы
 * @param object $database Объект подключения к БД
 * @param int $id ID врача для удаления
 * @return bool Результат операции
 */
function delete_doctor($database, $id) {
    // Начинаем транзакцию
    $database->begin_transaction();
    $success = false;
    
    try {
        // Получаем email врача
        $result001 = $database->prepare("SELECT docemail FROM doctor WHERE docid = ?");
        $result001->bind_param("i", $id);
        $result001->execute();
        $result001->bind_result($email);
        $result001->fetch();
        $result001->close();
        
        if ($email) {
            // Удаляем пользователя
            $stmt1 = $database->prepare("DELETE FROM webuser WHERE email = ?");
            $stmt1->bind_param("s", $email);
            $stmt1->execute();
            $stmt1->close();
            
            // Удаляем врача
            $stmt2 = $database->prepare("DELETE FROM doctor WHERE docemail = ?");
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
 * Получает список всех врачей
 * @param object $database Объект подключения к БД
 * @return array Массив с информацией о врачах
 */
function get_all_doctors($database) {
    $result = $database->query("SELECT * FROM doctor ORDER BY docname");
    $doctors = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $doctors[] = $row;
        }
    }
    
    return $doctors;
}

/**
 * Получает информацию о враче по ID
 * @param object $database Объект подключения к БД
 * @param int $id ID врача
 * @return array|null Массив с информацией о враче или null, если врач не найден
 */
function get_doctor_by_id($database, $id) {
    $stmt = $database->prepare("SELECT * FROM doctor WHERE docid = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}
