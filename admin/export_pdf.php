<?php
// admin/export_pdf.php - Экспорт медицинской записи в PDF

// Отключаем вывод ошибок для чистого PDF
ini_set('display_errors', 0);
error_reporting(0);

include("../includes/init.php");
require_once("../includes/medical_records_functions.php");
require_once("../includes/patient_functions.php");
require_once("../includes/appointment_functions.php");

// Проверяем, авторизован ли пользователь как админ
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["user"] == "" || $_SESSION['usertype'] != 'a') {
    header("location: ../login.php");
    exit();
}

// Получаем данные администратора для журнала аудита
$admin_email = $_SESSION["user"];

// Проверяем ID записи
$record_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$record_id) {
    header("Location: medical_records.php");
    exit;
}

// Получаем данные медицинской записи
$medical_record = get_medical_record($database, $record_id);
if (!$medical_record) {
    die("Медицинская запись не найдена");
}

// Логируем экспорт в PDF в журнал аудита
$admin_id = 1; // Администратор обычно имеет ID 1 в таблице admin
$action_details = json_encode([
    'exported_by' => $admin_email,
    'export_type' => 'pdf',
    'patient_id' => $medical_record['patient_id'],
    'patient_name' => $medical_record['patient_name'],
    'doctor_id' => $medical_record['doctor_id'],
    'doctor_name' => $medical_record['doctor_name']
]);
log_medical_record_action($database, $admin_id, 'admin', $record_id, 'export', $action_details);

// Получаем связанные данные
$diagnoses = get_record_diagnoses($database, $record_id);
$prescriptions = get_record_prescriptions($database, $record_id);
$lab_tests = get_record_lab_tests($database, $record_id);
$imaging_studies = get_record_imaging_studies($database, $record_id);
$treatment_plans = get_record_treatment_plans($database, $record_id);
$vitals = get_vitals($database, $record_id);
$allergies = get_patient_allergies($database, $medical_record['patient_id'], false);

// Получаем информацию о пациенте
$patient = get_patient_by_id($database, $medical_record['patient_id']);

// Устанавливаем заголовки для PDF-файла
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="medical_record_' . $record_id . '.pdf"');

// Создаем PDF-документ
require_once('../vendor/autoload.php');
require_once('pdf_generator.php');
$pdf = new PDFGenerator("Медицинская карта #" . $record_id);

// Добавляем информацию о пациенте
$pdf->addSection("Информация о пациенте");
$pdf->addParagraph("ФИО: " . $medical_record['patient_name']);
$pdf->addParagraph("Дата рождения: " . date('d.m.Y', strtotime($patient['pdob'])));
$pdf->addParagraph("Телефон: " . $patient['ptel']);
$pdf->addParagraph("Email: " . $patient['pemail']);

// Добавляем основную информацию о записи
$pdf->addSection("Основная информация");
$pdf->addParagraph("Дата приема: " . date('d.m.Y H:i', strtotime($medical_record['record_date'])));
$pdf->addParagraph("Врач: " . $medical_record['doctor_name']);
$pdf->addParagraph("Статус: " . ($medical_record['is_finalized'] ? 'Завершена' : 'В процессе'));

// Добавляем содержимое медицинской записи
$pdf->addSection("Содержание медицинской записи");
$pdf->addSubsection("Основная жалоба:");
$pdf->addParagraph($medical_record['chief_complaint'] ?: 'Не указано');
$pdf->addSubsection("Анамнез:");
$pdf->addParagraph($medical_record['anamnesis'] ?: 'Не указано');
$pdf->addSubsection("Результаты осмотра:");
$pdf->addParagraph($medical_record['examination_results'] ?: 'Не указано');

// Добавляем диагнозы
$pdf->addSection("Диагнозы");
if (empty($diagnoses)) {
    $pdf->addParagraph("Диагнозы не указаны");
} else {
    foreach ($diagnoses as $diagnosis) {
        $type = '';
        switch($diagnosis['diagnosis_type']) {
            case 'primary': $type = 'Основной'; break;
            case 'secondary': $type = 'Сопутствующий'; break;
            case 'complication': $type = 'Осложнение'; break;
            default: $type = $diagnosis['diagnosis_type'];
        }
        
        $pdf->addParagraph(
            "• " . $diagnosis['diagnosis_name'] . 
            ($diagnosis['icd10_code'] ? " (Код МКБ-10: " . $diagnosis['icd10_code'] . ")" : "") . 
            " - " . $type
        );
    }
}

// Добавляем рецепты
$pdf->addSection("Рецепты");
if (empty($prescriptions)) {
    $pdf->addParagraph("Рецепты не назначены");
} else {
    foreach ($prescriptions as $prescription) {
        $pdf->addParagraph(
            "• " . $prescription['medication_name'] . 
            ($prescription['dosage'] ? ", дозировка: " . $prescription['dosage'] : "") . 
            ($prescription['frequency'] ? ", частота: " . $prescription['frequency'] : "") . 
            ($prescription['duration'] ? ", продолжительность: " . $prescription['duration'] : "")
        );
        if ($prescription['instructions']) {
            $pdf->addParagraph("  Инструкции: " . $prescription['instructions']);
        }
    }
}

// Добавляем лабораторные анализы
$pdf->addSection("Лабораторные анализы");
if (empty($lab_tests)) {
    $pdf->addParagraph("Лабораторные анализы не назначены");
} else {
    foreach ($lab_tests as $test) {
        $pdf->addParagraph(
            "• " . $test['test_name'] . " (" . date('d.m.Y', strtotime($test['test_date'])) . ")" . 
            ($test['test_result'] ? ", результат: " . $test['test_result'] : ", результат: ожидается") . 
            ($test['reference_range'] ? ", норма: " . $test['reference_range'] : "")
        );
        if ($test['notes']) {
            $pdf->addParagraph("  Примечания: " . $test['notes']);
        }
    }
}

// Добавляем визуальные исследования
$pdf->addSection("Визуальные исследования");
if (empty($imaging_studies)) {
    $pdf->addParagraph("Визуальные исследования не назначены");
} else {
    foreach ($imaging_studies as $study) {
        $type = '';
        switch($study['study_type']) {
            case 'x-ray': $type = 'Рентген'; break;
            case 'ultrasound': $type = 'УЗИ'; break;
            case 'mri': $type = 'МРТ'; break;
            case 'ct': $type = 'КТ'; break;
            default: $type = 'Другое';
        }
        
        $pdf->addParagraph(
            "• " . $type . " (" . date('d.m.Y', strtotime($study['study_date'])) . ")" . 
            ($study['study_result'] ? ", результат: " . $study['study_result'] : ", результат: ожидается")
        );
        if ($study['notes']) {
            $pdf->addParagraph("  Примечания: " . $study['notes']);
        }
    }
}

// Добавляем план лечения
$pdf->addSection("План лечения");
if (empty($treatment_plans)) {
    $pdf->addParagraph("План лечения не составлен");
} else {
    foreach ($treatment_plans as $plan) {
        $status = '';
        switch($plan['status']) {
            case 'planned': $status = 'Запланировано'; break;
            case 'in_progress': $status = 'В процессе'; break;
            case 'completed': $status = 'Завершено'; break;
            case 'cancelled': $status = 'Отменено'; break;
            default: $status = $plan['status'];
        }
        
        $pdf->addParagraph("• " . $plan['plan_description']);
        $date_info = "";
        if ($plan['start_date']) {
            $date_info .= "Начало: " . date('d.m.Y', strtotime($plan['start_date']));
        }
        if ($plan['end_date']) {
            $date_info .= ($date_info ? ", " : "") . "Окончание: " . date('d.m.Y', strtotime($plan['end_date']));
        }
        if ($date_info) {
            $pdf->addParagraph("  " . $date_info);
        }
        $pdf->addParagraph("  Статус: " . $status);
    }
}

// Добавляем показатели жизнедеятельности
$pdf->addSection("Показатели жизнедеятельности");
if (!$vitals) {
    $pdf->addParagraph("Показатели жизнедеятельности не зарегистрированы");
} else {
    if ($vitals['temperature']) {
        $pdf->addParagraph("• Температура: " . $vitals['temperature'] . " °C (норма: 36.0 - 37.0 °C)");
    }
    
    if ($vitals['heart_rate']) {
        $pdf->addParagraph("• Пульс: " . $vitals['heart_rate'] . " уд/мин (норма: 60 - 100 уд/мин)");
    }
    
    if ($vitals['blood_pressure_systolic'] && $vitals['blood_pressure_diastolic']) {
        $pdf->addParagraph("• Артериальное давление: " . $vitals['blood_pressure_systolic'] . "/" . 
                        $vitals['blood_pressure_diastolic'] . " мм рт.ст. (норма: 90/60 - 120/80 мм рт.ст.)");
    }
    
    if ($vitals['respiratory_rate']) {
        $pdf->addParagraph("• Частота дыхания: " . $vitals['respiratory_rate'] . " вд/мин (норма: 12 - 20 вд/мин)");
    }
    
    if ($vitals['oxygen_saturation']) {
        $pdf->addParagraph("• Сатурация кислорода: " . $vitals['oxygen_saturation'] . " % (норма: 95 - 100 %)");
    }
    
    if ($vitals['height']) {
        $pdf->addParagraph("• Рост: " . $vitals['height'] . " см");
    }
    
    if ($vitals['weight']) {
        $pdf->addParagraph("• Вес: " . $vitals['weight'] . " кг");
    }
    
    if ($vitals['bmi']) {
        $pdf->addParagraph("• ИМТ: " . $vitals['bmi'] . " кг/м² (норма: 18.5 - 24.9 кг/м²)");
    }
    
    if ($vitals['pain_level'] !== null) {
        $pdf->addParagraph("• Уровень боли: " . $vitals['pain_level'] . " /10 (норма: 0)");
    }
    
    if ($vitals['notes']) {
        $pdf->addSubsection("Примечания:");
        $pdf->addParagraph($vitals['notes']);
    }
    
    $pdf->addParagraph("Измерено: " . date('d.m.Y H:i', strtotime($vitals['measured_at'])));
}

// Добавляем аллергии
$pdf->addSection("Аллергии");
if (empty($allergies)) {
    $pdf->addParagraph("Аллергии не зарегистрированы");
} else {
    foreach ($allergies as $allergy) {
        $severity = '';
        switch($allergy['severity']) {
            case 'mild': $severity = 'Легкая'; break;
            case 'moderate': $severity = 'Умеренная'; break;
            case 'severe': $severity = 'Тяжелая'; break;
            case 'life-threatening': $severity = 'Жизнеугрожающая'; break;
            default: $severity = 'Не указана';
        }
        
        $status = ($allergy['status'] == 'active') ? 'Активна' : 'Неактивна';
        
        $pdf->addParagraph("• " . $allergy['allergy_name'] . " - " . $severity . ", статус: " . $status);
        
        if ($allergy['reaction']) {
            $pdf->addParagraph("  Реакция: " . $allergy['reaction']);
        }
        
        if ($allergy['date_identified']) {
            $pdf->addParagraph("  Дата выявления: " . date('d.m.Y', strtotime($allergy['date_identified'])));
        }
        
        if ($allergy['notes']) {
            $pdf->addParagraph("  Примечания: " . $allergy['notes']);
        }
    }
}

// Добавляем футер с датой формирования отчета
$pdf->addFooter("Отчет сформирован: " . date('d.m.Y H:i:s'));

// Выводим PDF
$pdf->output();
?>
