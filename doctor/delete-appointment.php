<?php
/**
 * Скрипт для удаления/отмены записи на прием из панели доктора
 * 
 * Использует централизованную функцию cancel_appointment из appointment_functions.php
 * для обеспечения консистентности отмены записей во всей системе.
 */
include "header-doctor.php";

// Проверяем, передан ли ID записи
if (isset($_GET["id"])) {
    $id = intval($_GET["id"]); // Безопасное преобразование в целое число
    
    // Используем функцию из appointment_functions.php
    if (cancel_appointment($database, $id)) {
        // Успешное удаление
        header("location: appointment.php?action=deleted");
        exit();
    } else {
        // Ошибка при удалении
        header("location: appointment.php?action=error");
        exit();
    }
} else {
    // Если ID не передан, перенаправляем обратно
    header("location: appointment.php");
    exit();
}
?>