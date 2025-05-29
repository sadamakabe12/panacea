<?php
/**
 * Функции аутентификации и авторизации
 */

/**
 * Аутентифицирует пользователя
 * @param object $database Объект подключения к БД
 * @param string $email Email пользователя
 * @param string $password Пароль пользователя
 * @return array|false Массив с данными пользователя или false при ошибке
 */
function authenticate_user($database, $email, $password) {
    // Проверяем, существует ли пользователь
    $stmt = $database->prepare("SELECT * FROM webuser WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows != 1) {
        return false;
    }
    
    $user = $result->fetch_assoc();
    
    // Проверяем активность аккаунта
    if ($user['is_active'] == 0) {
        return ['error' => 'account_inactive'];
    }
    
    $usertype = $user['usertype'];
    
    // Проверяем пароль в зависимости от типа пользователя
    switch ($usertype) {
        case 'p': // Пациент
            $checker = $database->prepare("SELECT * FROM patient WHERE pemail=?");
            $checker->bind_param("s", $email);
            $checker->execute();
            $checked = $checker->get_result();
            
            if ($checked->num_rows == 1) {
                $patient = $checked->fetch_assoc();
                if (verify_password($password, $patient['ppassword'])) {
                    return [
                        'id' => $patient['pid'],
                        'name' => $patient['pname'],
                        'email' => $email,
                        'usertype' => 'p',
                        'error' => null
                    ];
                }
            }
            break;
            
        case 'd': // Доктор
            $checker = $database->prepare("SELECT * FROM doctor WHERE docemail=?");
            $checker->bind_param("s", $email);
            $checker->execute();
            $checked = $checker->get_result();
            
            if ($checked->num_rows == 1) {
                $doctor = $checked->fetch_assoc();
                if (verify_password($password, $doctor['docpassword'])) {
                    return [
                        'id' => $doctor['docid'],
                        'name' => $doctor['docname'],
                        'email' => $email,
                        'usertype' => 'd',
                        'error' => null
                    ];
                }
            }
            break;
            
        case 'a': // Администратор
            $checker = $database->prepare("SELECT * FROM admin WHERE aemail=?");
            $checker->bind_param("s", $email);
            $checker->execute();
            $checked = $checker->get_result();
            
            if ($checked->num_rows == 1) {
                $admin = $checked->fetch_assoc();
                if (verify_password($password, $admin['apassword'])) {
                    return [
                        'id' => 0,
                        'name' => 'Admin',
                        'email' => $email,
                        'usertype' => 'a',
                        'error' => null
                    ];
                }
            }
            break;
    }
    
    return ['error' => 'invalid_credentials'];
}

/**
 * Проверяет доступ к странице на основе типа пользователя
 * @param string $required_type Требуемый тип пользователя ('a', 'd', 'p')
 * @param string $redirect URL для перенаправления при ошибке доступа
 * @return void
 */
function check_access($required_type, $redirect = '../login.php') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION["user"]) || $_SESSION["user"] == "" || $_SESSION['usertype'] != $required_type) {
        header("location: " . $redirect);
        exit();
    }
}

/**
 * Регистрирует нового пациента
 * @param object $database Объект подключения к БД
 * @param string $email Email пациента
 * @param string $name Полное имя пациента
 * @param string $password Пароль пациента
 * @param string $dob Дата рождения (формат Y-m-d)
 * @param string $tele Номер телефона
 * @return bool Результат операции
 */
function register_patient($database, $email, $name, $password, $dob, $tele) {
    // Проверяем, существует ли уже такой email
    $stmt = $database->prepare("SELECT * FROM webuser WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return false;
    }
    
    // Начинаем транзакцию для обеспечения целостности данных
    $database->begin_transaction();
    
    try {        // Хешируем пароль
        $hashed_password = hash_password($password);
        
        // Добавляем пользователя в базу данных
        $stmt = $database->prepare("INSERT INTO patient (pemail, pname, ppassword, pdob, ptel) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $email, $name, $hashed_password, $dob, $tele);
        $patient_result = $stmt->execute();
        
        // Добавляем запись в таблицу webuser
        $usertype = 'p'; // 'p' для пациента
        $stmt = $database->prepare("INSERT INTO webuser (email, usertype) VALUES (?, ?)");
        $stmt->bind_param("ss", $email, $usertype);
        $webuser_result = $stmt->execute();
        
        // Если обе операции успешны, подтверждаем транзакцию
        if ($patient_result && $webuser_result) {
            $database->commit();
            return true;
        } else {
            throw new Exception("Ошибка при добавлении пользователя");
        }
    } catch (Exception $e) {
        // В случае ошибки откатываем транзакцию
        $database->rollback();
        return false;
    }
}

/**
 * Выход пользователя из системы
 * @param string $redirect URL для перенаправления после выхода
 * @return void
 */
function logout_user($redirect = 'login.php?action=logout') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION = array();
    
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-86400, '/');
    }
    
    session_destroy();
    
    // Перенаправляем пользователя на страницу входа
    header('Location: ' . $redirect);
    exit();
}

/**
 * Хеширует пароль безопасным образом
 * @param string $password Пароль для хеширования
 * @return string Хешированный пароль
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Проверяет, совпадает ли пароль с хешем
 * @param string $password Проверяемый пароль
 * @param string $hash Хеш для сравнения
 * @return bool Результат проверки
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Генерирует защиту от CSRF-атак
 * @return string CSRF-токен
 */
function generate_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Проверяет валидность CSRF-токена
 * @param string $token Токен для проверки
 * @return bool Результат проверки
 */
function verify_csrf_token($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

/**
 * Обновляет CSRF-токен
 */
function refresh_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
