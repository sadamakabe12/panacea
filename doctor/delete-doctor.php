<?php
// Подключаем необходимые файлы
include("../includes/init.php");

// Проверяем доступ (только для докторов)
check_access('d');

if (isset($_GET['id'])) {
    $doctorId = $_GET['id'];
    
    try {
        // Удаляем записи к этому врачу
        $stmt1 = $database->prepare("DELETE FROM appointment WHERE docid = ?");
        $stmt1->bind_param("i", $doctorId);
        $stmt1->execute();
        
        // Удаляем расписание врача  
        $stmt2 = $database->prepare("DELETE FROM schedule WHERE docid = ?");
        $stmt2->bind_param("i", $doctorId);
        $stmt2->execute();
        
        // Удаляем связи со специальностями
        $stmt3 = $database->prepare("DELETE FROM doctor_specialty WHERE docid = ?");
        $stmt3->bind_param("i", $doctorId);
        $stmt3->execute();
        
        // Удаляем самого врача
        $stmt4 = $database->prepare("DELETE FROM doctor WHERE docid = ?");
        $stmt4->bind_param("i", $doctorId);
        $stmt4->execute();
        
        // Выходим из системы после удаления аккаунта
        session_destroy();
        header("Location: ../login.php?msg=account_deleted");
        exit();
        
    } catch (Exception $e) {
        // В случае ошибки перенаправляем обратно с сообщением об ошибке
        header("Location: settings.php?error=delete_failed");
        exit();
    }
} else {
    // Нет ID врача - перенаправляем назад
    header("Location: settings.php");
    exit();
}
?>
