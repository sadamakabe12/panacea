<?php
$pageTitle = 'Медицинская карта пациента';
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["user"] == "" || $_SESSION['usertype'] != 'd') {
    header("location: ../login.php");
    exit();
}

include_once "../includes/init.php";
include_once "../includes/medical_records_functions.php";
include_once "../includes/patient_functions.php";
include "header-doctor.php";

$useremail = $_SESSION["user"];
$userrow = $database->query("SELECT * FROM doctor WHERE docemail='$useremail'");
$userfetch = $userrow->fetch_assoc();
$userid = $userfetch["docid"];
$username = $userfetch["docname"];

date_default_timezone_set('Asia/Yekaterinburg');
$today = date('Y-m-d');

// Получение параметров
$appointment_id = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : null;
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : null;
$record_id = isset($_GET['record_id']) ? intval($_GET['record_id']) : null;

// Проверка доступа врача к записи
if (!$patient_id) {
    header("location: appointment.php");
    exit();
}

// Получение информации о пациенте
$patient = get_patient_by_id($database, $patient_id);
if (!$patient) {
    header("location: appointment.php");
    exit();
}

// Получение информации о записи на прием
$appointment = null;
if ($appointment_id) {
    $sql = "SELECT * FROM appointment WHERE appoid = ? AND scheduleid IN (SELECT scheduleid FROM schedule WHERE docid = ?)";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("ii", $appointment_id, $userid);
    $stmt->execute();
    $result = $stmt->get_result();
    $appointment = $result->fetch_assoc();
}

// Получение существующей медицинской записи или создание новой
$medical_record = null;
$diagnoses = [];
$prescriptions = [];
$lab_tests = [];
$imaging_studies = [];
$treatment_plans = [];
$vitals = null;

if ($record_id) {
    // Загрузка существующей записи
    $medical_record = get_medical_record($database, $record_id);
    if (!$medical_record || $medical_record['doctor_id'] != $userid) {
        header("location: appointment.php");
        exit();
    }
} else if ($appointment_id) {
    // Поиск существующей записи по записи на прием
    $sql = "SELECT * FROM medical_records WHERE appointment_id = ? AND doctor_id = ?";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("ii", $appointment_id, $userid);
    $stmt->execute();
    $result = $stmt->get_result();
    $medical_record = $result->fetch_assoc();
}

if ($medical_record) {
    $record_id = $medical_record['record_id'];
    $diagnoses = get_record_diagnoses($database, $record_id);
    $prescriptions = get_record_prescriptions($database, $record_id);
    $lab_tests = get_record_lab_tests($database, $record_id);
    $imaging_studies = get_record_imaging_studies($database, $record_id);
    $treatment_plans = get_record_treatment_plans($database, $record_id);
    $vitals = get_vitals($database, $record_id);
}

// Обработка POST-запросов
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_record':
                $chief_complaint = $_POST['chief_complaint'] ?? '';
                $anamnesis = $_POST['anamnesis'] ?? '';
                $examination_results = $_POST['examination_results'] ?? '';
                $is_finalized = isset($_POST['is_finalized']) ? 1 : 0;
                
                $new_record_id = create_medical_record($database, $patient_id, $userid, $appointment_id, $chief_complaint, $anamnesis, $examination_results, $is_finalized);
                
                if ($new_record_id) {
                    // Обновление статуса записи на прием
                    if ($appointment_id) {
                        $sql = "UPDATE appointment SET status = 'completed' WHERE appoid = ?";
                        $stmt = $database->prepare($sql);
                        $stmt->bind_param("i", $appointment_id);
                        $stmt->execute();
                    }
                    
                    $message = "Медицинская запись успешно создана";
                    header("location: medical_record.php?record_id=$new_record_id&patient_id=$patient_id" . ($appointment_id ? "&appointment_id=$appointment_id" : ""));
                    exit();
                } else {
                    $error = "Ошибка при создании медицинской записи";
                }
                break;
                
            case 'update_record':
                if ($medical_record) {
                    $chief_complaint = $_POST['chief_complaint'] ?? '';
                    $anamnesis = $_POST['anamnesis'] ?? '';
                    $examination_results = $_POST['examination_results'] ?? '';
                    $is_finalized = isset($_POST['is_finalized']) ? 1 : 0;
                    
                    $sql = "UPDATE medical_records SET chief_complaint = ?, anamnesis = ?, examination_results = ?, is_finalized = ?, updated_at = NOW() WHERE record_id = ?";
                    $stmt = $database->prepare($sql);
                    $stmt->bind_param("sssii", $chief_complaint, $anamnesis, $examination_results, $is_finalized, $record_id);
                    
                    if ($stmt->execute()) {
                        $message = "Медицинская запись успешно обновлена";
                        // Перезагрузка данных
                        $medical_record = get_medical_record($database, $record_id);
                    } else {
                        $error = "Ошибка при обновлении медицинской записи";
                    }
                }
                break;
                
            case 'add_diagnosis':
                if ($medical_record) {
                    $diagnosis_name = $_POST['diagnosis_name'] ?? '';
                    $icd10_code = $_POST['icd10_code'] ?? null;
                    $diagnosis_type = $_POST['diagnosis_type'] ?? 'primary';
                    
                    if ($diagnosis_name) {
                        $diagnosis_id = add_diagnosis($database, $record_id, $diagnosis_name, $icd10_code, $diagnosis_type);
                        if ($diagnosis_id) {
                            $message = "Диагноз успешно добавлен";
                            $diagnoses = get_record_diagnoses($database, $record_id);
                        } else {
                            $error = "Ошибка при добавлении диагноза";
                        }
                    }
                }
                break;
                
            case 'add_prescription':
                if ($medical_record) {
                    $medication_name = $_POST['medication_name'] ?? '';
                    $dosage = $_POST['dosage'] ?? '';
                    $frequency = $_POST['frequency'] ?? '';
                    $duration = $_POST['duration'] ?? '';
                    $instructions = $_POST['instructions'] ?? '';
                    
                    if ($medication_name) {
                        $prescription_id = add_prescription($database, $record_id, $medication_name, $dosage, $frequency, $duration, $instructions);
                        if ($prescription_id) {
                            $message = "Рецепт успешно добавлен";
                            $prescriptions = get_record_prescriptions($database, $record_id);
                        } else {
                            $error = "Ошибка при добавлении рецепта";
                        }
                    }
                }
                break;
                
            case 'add_lab_test':
                if ($medical_record) {
                    $test_name = $_POST['test_name'] ?? '';
                    $test_date = $_POST['test_date'] ?? $today;
                    $test_result = $_POST['test_result'] ?? '';
                    $reference_range = $_POST['reference_range'] ?? '';
                    $is_abnormal = isset($_POST['is_abnormal']) ? 1 : 0;
                    $notes = $_POST['test_notes'] ?? '';
                    
                    if ($test_name) {
                        $test_id = add_lab_test($database, $record_id, $test_name, $test_date, $test_result, $reference_range, $is_abnormal, $notes);
                        if ($test_id) {
                            $message = "Лабораторный тест успешно добавлен";
                            $lab_tests = get_record_lab_tests($database, $record_id);
                        } else {
                            $error = "Ошибка при добавлении лабораторного теста";
                        }
                    }
                }
                break;
                
            case 'add_treatment_plan':
                if ($medical_record) {
                    $plan_description = $_POST['plan_description'] ?? '';
                    $start_date = $_POST['start_date'] ?? null;
                    $end_date = $_POST['end_date'] ?? null;
                    $plan_status = $_POST['plan_status'] ?? 'planned';
                    
                    if ($plan_description) {
                        $plan_id = add_treatment_plan($database, $record_id, $plan_description, $start_date, $end_date, $plan_status);
                        if ($plan_id) {
                            $message = "План лечения успешно добавлен";
                            $treatment_plans = get_record_treatment_plans($database, $record_id);
                        } else {
                            $error = "Ошибка при добавлении плана лечения";
                        }
                    }
                }
                break;
        }
    }
}

// Получение ICD-10 кодов для автодополнения
$icd10_codes = [];
if (isset($_GET['search_icd10']) && strlen($_GET['search_icd10']) >= 2) {
    $icd10_codes = search_icd10_codes($database, $_GET['search_icd10']);
    header('Content-Type: application/json');
    echo json_encode($icd10_codes);
    exit();
}
?>

<div class="dash-body">
    <table border="0" width="100%" style="border-spacing: 0;margin:0;padding:0;margin-top:25px;">
        <tr>
            <td width="13%">
                <a href="<?php echo $appointment_id ? 'appointment.php' : 'patient.php'; ?>">
                    <button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px">
                        <font class="tn-in-text">Назад</font>
                    </button>
                </a>
            </td>
            <td>
                <p style="font-size: 23px;padding-left:12px;font-weight: 600;">
                    Медицинская карта - <?php echo htmlspecialchars($patient['pname']); ?>
                </p>
            </td>
            <td width="15%">
                <p style="font-size: 14px;color: rgb(119, 119, 119);padding: 0;margin: 0;text-align: right;">
                    Сегодняшняя дата
                </p>
                <p class="heading-sub12" style="padding: 0;margin: 0;"><?php echo date('d.m.Y'); ?></p>
            </td>
            <td width="10%">
                <button class="btn-label" style="display: flex;justify-content: center;align-items: center;">
                    <img src="../img/calendar.svg" width="100%">
                </button>
            </td>
        </tr>
        
        <!-- Сообщения -->
        <?php if ($message): ?>
        <tr>
            <td colspan="4">
                <div class="alert alert-success" style="margin: 20px; padding: 15px; background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; color: #155724;">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            </td>
        </tr>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <tr>
            <td colspan="4">
                <div class="alert alert-danger" style="margin: 20px; padding: 15px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            </td>
        </tr>
        <?php endif; ?>
        
        <!-- Информация о пациенте -->
        <tr>
            <td colspan="4">
                <div class="patient-info" style="margin: 20px; padding: 20px; background-color: #f8f9fa; border-radius: 10px; border-left: 4px solid #007bff;">
                    <h3 style="margin-top: 0;">Информация о пациенте</h3>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                        <div>
                            <strong>Имя:</strong> <?php echo htmlspecialchars($patient['pname']); ?><br>
                            <strong>Email:</strong> <?php echo htmlspecialchars($patient['pemail']); ?><br>
                            <strong>Телефон:</strong> <?php echo htmlspecialchars($patient['ptel']); ?>
                        </div>                        <div>
                            <strong>Дата рождения:</strong> <?php echo htmlspecialchars($patient['pdob']); ?><br>
                            <strong>Адрес:</strong> <?php echo htmlspecialchars(isset($patient['paddress']) ? $patient['paddress'] : 'Не указан'); ?>
                            <?php if ($appointment): ?>
                            <br><strong>Время приема:</strong> <?php echo date('d.m.Y H:i', strtotime($appointment['appodate'] . ' ' . (isset($appointment['appotime']) ? $appointment['appotime'] : '00:00:00'))); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
        
        <!-- Основная медицинская запись -->
        <tr>
            <td colspan="4">
                <div class="medical-record-form" style="margin: 20px;">
                    <?php if (!$medical_record): ?>
                    <!-- Форма создания новой записи -->
                    <div class="card" style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <h3>Создать медицинскую запись</h3>
                        
                        <form method="POST" style="margin-top: 20px;">
                            <input type="hidden" name="action" value="create_record">
                            
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label for="chief_complaint" style="display: block; font-weight: bold; margin-bottom: 5px;">Основная жалоба пациента:</label>
                                <textarea name="chief_complaint" id="chief_complaint" rows="3" class="input-text" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;" required></textarea>
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label for="anamnesis" style="display: block; font-weight: bold; margin-bottom: 5px;">Анамнез (история заболевания):</label>
                                <textarea name="anamnesis" id="anamnesis" rows="4" class="input-text" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"></textarea>
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label for="examination_results" style="display: block; font-weight: bold; margin-bottom: 5px;">Результаты осмотра:</label>
                                <textarea name="examination_results" id="examination_results" rows="4" class="input-text" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"></textarea>
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label style="display: flex; align-items: center;">
                                    <input type="checkbox" name="is_finalized" value="1" style="margin-right: 10px;">
                                    Завершить запись
                                </label>
                            </div>
                            
                            <button type="submit" class="btn-primary btn" style="padding: 12px 25px;">
                                Создать медицинскую запись
                            </button>
                        </form>
                    </div>
                    <?php else: ?>
                    <!-- Форма редактирования существующей записи -->
                    <div class="card" style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <h3>Медицинская запись #<?php echo $medical_record['record_id']; ?></h3>
                            <div>
                                <span class="status-badge" style="padding: 5px 15px; border-radius: 15px; color: white; background-color: <?php echo $medical_record['is_finalized'] ? '#28a745' : '#ffc107'; ?>;">
                                    <?php echo $medical_record['is_finalized'] ? 'Завершена' : 'В процессе'; ?>
                                </span>
                                <small style="color: #666; margin-left: 15px;">
                                    Создана: <?php echo date('d.m.Y H:i', strtotime($medical_record['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                        
                        <form method="POST" style="margin-top: 20px;">
                            <input type="hidden" name="action" value="update_record">
                            
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label for="chief_complaint" style="display: block; font-weight: bold; margin-bottom: 5px;">Основная жалоба пациента:</label>
                                <textarea name="chief_complaint" id="chief_complaint" rows="3" class="input-text" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;" required><?php echo htmlspecialchars($medical_record['chief_complaint']); ?></textarea>
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label for="anamnesis" style="display: block; font-weight: bold; margin-bottom: 5px;">Анамнез (история заболевания):</label>
                                <textarea name="anamnesis" id="anamnesis" rows="4" class="input-text" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"><?php echo htmlspecialchars($medical_record['anamnesis']); ?></textarea>
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label for="examination_results" style="display: block; font-weight: bold; margin-bottom: 5px;">Результаты осмотра:</label>
                                <textarea name="examination_results" id="examination_results" rows="4" class="input-text" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"><?php echo htmlspecialchars($medical_record['examination_results']); ?></textarea>
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label style="display: flex; align-items: center;">
                                    <input type="checkbox" name="is_finalized" value="1" <?php echo $medical_record['is_finalized'] ? 'checked' : ''; ?> style="margin-right: 10px;">
                                    Завершить запись
                                </label>
                            </div>
                            
                            <button type="submit" class="btn-primary btn" style="padding: 12px 25px;">
                                Обновить медицинскую запись
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        
        <?php if ($medical_record): ?>
        <!-- Вкладки для дополнительных данных -->
        <tr>
            <td colspan="4">
                <div class="tabs-container" style="margin: 20px;">
                    <div class="tabs" style="display: flex; border-bottom: 2px solid #ddd; background-color: white; border-radius: 10px 10px 0 0;">
                        <div class="tab active" data-tab="vitals" style="padding: 15px 25px; cursor: pointer; background-color: #007bff; color: white; border-radius: 10px 0 0 0; font-weight: bold;">
                            Показатели жизнедеятельности
                        </div>
                        <div class="tab" data-tab="diagnoses" style="padding: 15px 25px; cursor: pointer; background-color: #f8f9fa; border-bottom: 2px solid #ddd;">
                            Диагнозы
                        </div>
                        <div class="tab" data-tab="prescriptions" style="padding: 15px 25px; cursor: pointer; background-color: #f8f9fa; border-bottom: 2px solid #ddd;">
                            Рецепты
                        </div>
                        <div class="tab" data-tab="lab-tests" style="padding: 15px 25px; cursor: pointer; background-color: #f8f9fa; border-bottom: 2px solid #ddd;">
                            Лабораторные тесты
                        </div>
                        <div class="tab" data-tab="treatment-plans" style="padding: 15px 25px; cursor: pointer; background-color: #f8f9fa; border-bottom: 2px solid #ddd;">
                            Планы лечения
                        </div>
                    </div>
                    
                    <!-- Вкладка Показатели жизнедеятельности -->
                    <div class="tab-content active" id="vitals-content" style="padding: 25px; border: 1px solid #ddd; border-top: none; background-color: white; border-radius: 0 0 10px 10px;">
                        <h4>Показатели жизнедеятельности</h4>
                        
                        <?php if ($vitals): ?>
                        <div class="vitals-display" style="margin-bottom: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 5px;">
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
                                <div><strong>Температура:</strong> <?php echo $vitals['temperature'] ? $vitals['temperature'] . ' °C' : 'Не измерено'; ?></div>
                                <div><strong>Пульс:</strong> <?php echo $vitals['heart_rate'] ? $vitals['heart_rate'] . ' уд/мин' : 'Не измерено'; ?></div>
                                <div><strong>АД:</strong> 
                                    <?php 
                                    if ($vitals['blood_pressure_systolic'] && $vitals['blood_pressure_diastolic']) {
                                        echo $vitals['blood_pressure_systolic'] . '/' . $vitals['blood_pressure_diastolic'] . ' мм рт.ст.';
                                    } else {
                                        echo 'Не измерено';
                                    }
                                    ?>
                                </div>
                                <div><strong>Частота дыхания:</strong> <?php echo $vitals['respiratory_rate'] ? $vitals['respiratory_rate'] . ' вд/мин' : 'Не измерено'; ?></div>
                                <div><strong>Сатурация:</strong> <?php echo $vitals['oxygen_saturation'] ? $vitals['oxygen_saturation'] . ' %' : 'Не измерено'; ?></div>
                                <div><strong>Рост:</strong> <?php echo $vitals['height'] ? $vitals['height'] . ' см' : 'Не измерено'; ?></div>
                                <div><strong>Вес:</strong> <?php echo $vitals['weight'] ? $vitals['weight'] . ' кг' : 'Не измерено'; ?></div>
                                <div><strong>ИМТ:</strong> <?php echo $vitals['bmi'] ? $vitals['bmi'] . ' кг/м²' : 'Не рассчитано'; ?></div>
                                <div><strong>Уровень боли:</strong> <?php echo $vitals['pain_level'] !== null ? $vitals['pain_level'] . '/10' : 'Не оценено'; ?></div>
                            </div>
                            <?php if ($vitals['notes']): ?>
                            <div style="margin-top: 15px;"><strong>Примечания:</strong> <?php echo htmlspecialchars($vitals['notes']); ?></div>
                            <?php endif; ?>
                            <div style="margin-top: 10px; font-size: 12px; color: #666;">
                                Измерено: <?php echo date('d.m.Y H:i', strtotime($vitals['measured_at'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Форма добавления показателей -->
                        <div class="vitals-form">
                            <h5>Добавить показатели жизнедеятельности</h5>
                            <div id="vitals-form-container" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 15px;">
                                <div>
                                    <label>Температура (°C):</label>
                                    <input type="number" step="0.1" id="temperature" class="input-text" style="width: 100%; padding: 8px; margin-top: 5px;" placeholder="36.6">
                                </div>
                                <div>
                                    <label>Пульс (уд/мин):</label>
                                    <input type="number" id="heart_rate" class="input-text" style="width: 100%; padding: 8px; margin-top: 5px;" placeholder="72">
                                </div>
                                <div>
                                    <label>Систолическое АД:</label>
                                    <input type="number" id="bp_systolic" class="input-text" style="width: 100%; padding: 8px; margin-top: 5px;" placeholder="120">
                                </div>
                                <div>
                                    <label>Диастолическое АД:</label>
                                    <input type="number" id="bp_diastolic" class="input-text" style="width: 100%; padding: 8px; margin-top: 5px;" placeholder="80">
                                </div>
                                <div>
                                    <label>Частота дыхания:</label>
                                    <input type="number" id="respiratory_rate" class="input-text" style="width: 100%; padding: 8px; margin-top: 5px;" placeholder="16">
                                </div>
                                <div>
                                    <label>Сатурация (%):</label>
                                    <input type="number" id="oxygen_saturation" class="input-text" style="width: 100%; padding: 8px; margin-top: 5px;" placeholder="98">
                                </div>
                                <div>
                                    <label>Рост (см):</label>
                                    <input type="number" step="0.1" id="height" class="input-text" style="width: 100%; padding: 8px; margin-top: 5px;" placeholder="170">
                                </div>
                                <div>
                                    <label>Вес (кг):</label>
                                    <input type="number" step="0.1" id="weight" class="input-text" style="width: 100%; padding: 8px; margin-top: 5px;" placeholder="70">
                                </div>
                                <div>
                                    <label>Уровень боли (0-10):</label>
                                    <input type="number" min="0" max="10" id="pain_level" class="input-text" style="width: 100%; padding: 8px; margin-top: 5px;" placeholder="0">
                                </div>
                            </div>
                            <div style="margin-top: 15px;">
                                <label>Примечания:</label>
                                <textarea id="vitals_notes" class="input-text" style="width: 100%; padding: 8px; margin-top: 5px;" rows="2"></textarea>
                            </div>
                            <button type="button" onclick="saveVitals()" class="btn-primary btn" style="margin-top: 15px; padding: 10px 20px;">
                                Сохранить показатели
                            </button>
                        </div>
                    </div>
                    
                    <!-- Вкладка Диагнозы -->
                    <div class="tab-content" id="diagnoses-content" style="padding: 25px; border: 1px solid #ddd; border-top: none; background-color: white; display: none;">
                        <h4>Диагнозы</h4>
                        
                        <!-- Список диагнозов -->
                        <?php if (!empty($diagnoses)): ?>
                        <div class="diagnoses-list" style="margin-bottom: 25px;">
                            <table class="styled-table" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Диагноз</th>
                                        <th>Код МКБ-10</th>
                                        <th>Тип</th>
                                        <th>Дата</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($diagnoses as $diagnosis): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($diagnosis['diagnosis_name']); ?></td>
                                        <td>
                                            <?php if ($diagnosis['icd10_code']): ?>
                                                <?php echo htmlspecialchars($diagnosis['icd10_code']); ?> - 
                                                <?php echo htmlspecialchars($diagnosis['icd10_description']); ?>
                                            <?php else: ?>
                                                Не указан
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $type_labels = [
                                                'primary' => 'Основной',
                                                'secondary' => 'Сопутствующий',
                                                'complication' => 'Осложнение'
                                            ];
                                            echo $type_labels[$diagnosis['diagnosis_type']] ?? $diagnosis['diagnosis_type'];
                                            ?>
                                        </td>
                                        <td><?php echo date('d.m.Y', strtotime($diagnosis['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Форма добавления диагноза -->
                        <div class="diagnosis-form">
                            <h5>Добавить диагноз</h5>
                            <form method="POST" style="margin-top: 15px;">
                                <input type="hidden" name="action" value="add_diagnosis">
                                
                                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 15px;">
                                    <div>
                                        <label>Название диагноза:</label>
                                        <input type="text" name="diagnosis_name" class="input-text" style="width: 100%; padding: 8px; margin-top: 5px;" required>
                                    </div>
                                    <div>
                                        <label>Код МКБ-10:</label>
                                        <input type="text" name="icd10_code" class="input-text" style="width: 100%; padding: 8px; margin-top: 5px;" id="icd10_search" placeholder="Введите код или название">
                                        <div id="icd10_suggestions" style="position: absolute; background: white; border: 1px solid #ddd; max-height: 200px; overflow-y: auto; z-index: 1000; display: none;"></div>
                                    </div>
                                    <div>
                                        <label>Тип диагноза:</label>
                                        <select name="diagnosis_type" class="input-text" style="width: 100%; padding: 8px; margin-top: 5px;">
                                            <option value="primary">Основной</option>
                                            <option value="secondary">Сопутствующий</option>
                                            <option value="complication">Осложнение</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn-primary btn" style="margin-top: 15px; padding: 10px 20px;">
                                    Добавить диагноз
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Вкладка Рецепты -->
                    <div class="tab-content" id="prescriptions-content" style="padding: 25px; border: 1px solid #ddd; border-top: none; background-color: white; display: none;">
                        <h4>Рецепты</h4>
                        
                        <!-- Список рецептов -->
                        <?php if (!empty($prescriptions)): ?>
                        <div class="prescriptions-list" style="margin-bottom: 25px;">
                            <table class="styled-table" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Лекарство</th>
                                        <th>Дозировка</th>
                                        <th>Частота</th>
                                        <th>Длительность</th>
                                        <th>Инструкции</th>
                                        <th>Дата</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($prescriptions as $prescription): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($prescription['medication_name']); ?></td>
                                        <td><?php echo htmlspecialchars($prescription['dosage']); ?></td>
                                        <td><?php echo htmlspecialchars($prescription['frequency']); ?></td>
                                        <td><?php echo htmlspecialchars($prescription['duration']); ?></td>
                                        <td><?php echo htmlspecialchars($prescription['instructions']); ?></td>
                                        <td><?php echo date('d.m.Y', strtotime($prescription['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Форма добавления рецепта -->
                        <div class="prescription-form">
                            <h5>Добавить рецепт</h5>
                            <form method="POST" style="margin-top: 15px;">
                                <input type="hidden" name="action" value="add_prescription">
                                
                                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 15px;">
                                    <div>
                                        <label>Название лекарства:</label>
                                        <input type="text" name="medication_name" class="input-text" style="width: 100%; padding: 8px; margin-top: 5px;" required>
                                    </div>
                                    <div>
                                        <label>Дозировка:</label>
                                        <input type="text" name="dosage" class="input-text" style="width: 100%; padding: 8px; margin-top: 5px;" placeholder="500 мг">
                                    </div>
                                    <div>
                                        <label>Частота:</label>
                                        <input type="text" name="frequency" class="input-text" style="width: 100%; padding: 8px; margin-top: 5px;" placeholder="2 раза в день">
                                    </div>
                                    <div>
                                        <label>Длительность:</label>
                                        <input type="text" name="duration" class="input-text" style="width: 100%; padding: 8px; margin-top: 5px;" placeholder="7 дней">
                                    </div>
                                </div>
                                
                                <div style="margin-top: 15px;">
                                    <label>Инструкции:</label>
                                    <textarea name="instructions" class="input-text" style="width: 100%; padding: 8px; margin-top: 5px;" rows="2" placeholder="Принимать после еды"></textarea>
                                </div>
                                
                                <button type="submit" class="btn-primary btn" style="margin-top: 15px; padding: 10px 20px;">
                                    Добавить рецепт
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Вкладка Лабораторные тесты -->
                    <div class="tab-content" id="lab-tests-content" style="padding: 25px; border: 1px solid #ddd; border-top: none; background-color: white; display: none;">
                        <h4>Лабораторные тесты</h4>
                        
                        <!-- Список тестов -->
                        <?php if (!empty($lab_tests)): ?>
                        <div class="lab-tests-list" style="margin-bottom: 25px;">
                            <table class="styled-table" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Название теста</th>
                                        <th>Дата</th>
                                        <th>Результат</th>
                                        <th>Референс</th>
                                        <th>Статус</th>
                                        <th>Примечания</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lab_tests as $test): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($test['test_name']); ?></td>
                                        <td><?php echo htmlspecialchars($test['test_date']); ?></td>
                                        <td><?php echo htmlspecialchars($test['test_result']); ?></td>
                                        <td><?php echo htmlspecialchars($test['reference_range']); ?></td>
                                        <td>
                                            <?php if ($test['is_abnormal']): ?>
                                                <span style="color: #dc3545;">Отклонение</span>
                                            <?php else: ?>
                                                <span style="color: #28a745;">Норма</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($test['notes']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Форма добавления теста -->
                        <div class="lab-test-form">
                            <h5>Добавить лабораторный тест</h5>
                            <form method="POST" style="margin-top: 15px;">
                                <input type="hidden" name="action" value="add_lab_test">
                                
                                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 15px;">
                                    <div>
                                        <label>Название теста:</label>
                                        <input type="text" name="test_name" class="input-text" style="width: 100%; padding: 8px; margin-top: 5px;" required>
                                    </div>
                                    <div>
                                        <label>Дата теста:</label>
                                        <input type="date" name="test_date" class="input-text" style="width: 100%; padding: 8px; margin-top: 5px;" value="<?php echo $today; ?>">
                                    </div>
                                    <div>
                                        <label>Результат:</label>
                                        <input type="text" name="test_result" class="input-text" style="width: 100%; padding: 8px; margin-top: 5px;">
                                    </div>
                                    <div>
                                        <label>Референсные значения:</label>
                                        <input type="text" name="reference_range" class="input-text" style="width: 100%; padding: 8px; margin-top: 5px;">
                                    </div>
                                </div>
                                
                                <div style="margin-top: 15px;">
                                    <label style="display: flex; align-items: center;">
                                        <input type="checkbox" name="is_abnormal" value="1" style="margin-right: 10px;">
                                        Результат выходит за пределы нормы
                                    </label>
                                </div>
                                
                                <div style="margin-top: 15px;">
                                    <label>Примечания:</label>
                                    <textarea name="test_notes" class="input-text" style="width: 100%; padding: 8px; margin-top: 5px;" rows="2"></textarea>
                                </div>
                                
                                <button type="submit" class="btn-primary btn" style="margin-top: 15px; padding: 10px 20px;">
                                    Добавить тест
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Вкладка Планы лечения -->
                    <div class="tab-content" id="treatment-plans-content" style="padding: 25px; border: 1px solid #ddd; border-top: none; background-color: white; display: none;">
                        <h4>Планы лечения</h4>
                        
                        <!-- Список планов лечения -->
                        <?php if (!empty($treatment_plans)): ?>
                        <div class="treatment-plans-list" style="margin-bottom: 25px;">
                            <table class="styled-table" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Описание</th>
                                        <th>Дата начала</th>
                                        <th>Дата окончания</th>
                                        <th>Статус</th>
                                        <th>Создан</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($treatment_plans as $plan): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($plan['plan_description']); ?></td>
                                        <td><?php echo $plan['start_date'] ? htmlspecialchars($plan['start_date']) : 'Не указана'; ?></td>
                                        <td><?php echo $plan['end_date'] ? htmlspecialchars($plan['end_date']) : 'Не указана'; ?></td>
                                        <td>
                                            <?php 
                                            $status_labels = [
                                                'planned' => 'Запланировано',
                                                'in_progress' => 'В процессе',
                                                'completed' => 'Завершено',
                                                'cancelled' => 'Отменено'
                                            ];
                                            echo $status_labels[$plan['status']] ?? $plan['status'];
                                            ?>
                                        </td>
                                        <td><?php echo date('d.m.Y', strtotime($plan['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Форма добавления плана лечения -->
                        <div class="treatment-plan-form">
                            <h5>Добавить план лечения</h5>
                            <form method="POST" style="margin-top: 15px;">
                                <input type="hidden" name="action" value="add_treatment_plan">
                                
                                <div style="margin-bottom: 15px;">
                                    <label>Описание плана лечения:</label>
                                    <textarea name="plan_description" class="input-text" style="width: 100%; padding: 8px; margin-top: 5px;" rows="3" required></textarea>
                                </div>
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                                    <div>
                                        <label>Дата начала:</label>
                                        <input type="date" name="start_date" class="input-text" style="width: 100%; padding: 8px; margin-top: 5px;">
                                    </div>
                                    <div>
                                        <label>Дата окончания:</label>
                                        <input type="date" name="end_date" class="input-text" style="width: 100%; padding: 8px; margin-top: 5px;">
                                    </div>
                                    <div>
                                        <label>Статус:</label>
                                        <select name="plan_status" class="input-text" style="width: 100%; padding: 8px; margin-top: 5px;">
                                            <option value="planned">Запланировано</option>
                                            <option value="in_progress">В процессе</option>
                                            <option value="completed">Завершено</option>
                                            <option value="cancelled">Отменено</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn-primary btn" style="margin-top: 15px; padding: 10px 20px;">
                                    Добавить план лечения
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
        
        <!-- Кнопки экспорта -->
        <tr>
            <td colspan="4">
                <div class="export-buttons" style="margin: 20px; text-align: center;">
                    <a href="export_pdf.php?record_id=<?php echo $record_id; ?>" class="btn-primary btn" style="padding: 12px 25px; margin-right: 15px; text-decoration: none;">
                        Экспорт в PDF
                    </a>
                    <a href="patient.php?id=<?php echo $patient_id; ?>" class="btn-primary-soft btn" style="padding: 12px 25px; text-decoration: none;">
                        История пациента
                    </a>
                </div>
            </td>
        </tr>
        <?php endif; ?>
    </table>
</div>

<script>
// Функция для переключения вкладок
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.tab');
    const tabContents = document.querySelectorAll('.tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');

            // Удаляем активные классы
            tabs.forEach(t => {
                t.classList.remove('active');
                t.style.backgroundColor = '#f8f9fa';
                t.style.color = '#333';
            });
            
            tabContents.forEach(content => {
                content.classList.remove('active');
                content.style.display = 'none';
            });

            // Добавляем активные классы
            this.classList.add('active');
            this.style.backgroundColor = '#007bff';
            this.style.color = 'white';
            
            const targetContent = document.getElementById(targetTab + '-content');
            if (targetContent) {
                targetContent.classList.add('active');
                targetContent.style.display = 'block';
            }
        });
    });

    // ICD-10 автодополнение
    const icd10Input = document.getElementById('icd10_search');
    const icd10Suggestions = document.getElementById('icd10_suggestions');

    if (icd10Input) {
        let debounceTimer;
        
        icd10Input.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const query = this.value.trim();
            
            if (query.length >= 2) {
                debounceTimer = setTimeout(() => {
                    fetch(`medical_record.php?search_icd10=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(data => {
                            icd10Suggestions.innerHTML = '';
                            if (data.length > 0) {
                                data.forEach(item => {
                                    const div = document.createElement('div');
                                    div.style.padding = '10px';
                                    div.style.cursor = 'pointer';
                                    div.style.borderBottom = '1px solid #eee';
                                    div.innerHTML = `<strong>${item.code}</strong> - ${item.description}`;
                                    div.addEventListener('click', function() {
                                        icd10Input.value = item.code;
                                        icd10Suggestions.style.display = 'none';
                                    });
                                    icd10Suggestions.appendChild(div);
                                });
                                icd10Suggestions.style.display = 'block';
                            } else {
                                icd10Suggestions.style.display = 'none';
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching ICD-10 codes:', error);
                            icd10Suggestions.style.display = 'none';
                        });
                }, 300);
            } else {
                icd10Suggestions.style.display = 'none';
            }
        });

        // Скрыть предложения при клике вне поля
        document.addEventListener('click', function(e) {
            if (!icd10Input.contains(e.target) && !icd10Suggestions.contains(e.target)) {
                icd10Suggestions.style.display = 'none';
            }
        });
    }
});

// Функция для сохранения показателей жизнедеятельности
function saveVitals() {
    const formData = new FormData();
    formData.append('record_id', <?php echo $record_id ?? 0; ?>);
    formData.append('temperature', document.getElementById('temperature').value);
    formData.append('heart_rate', document.getElementById('heart_rate').value);
    formData.append('blood_pressure_systolic', document.getElementById('bp_systolic').value);
    formData.append('blood_pressure_diastolic', document.getElementById('bp_diastolic').value);
    formData.append('respiratory_rate', document.getElementById('respiratory_rate').value);
    formData.append('oxygen_saturation', document.getElementById('oxygen_saturation').value);
    formData.append('height', document.getElementById('height').value);
    formData.append('weight', document.getElementById('weight').value);
    formData.append('pain_level', document.getElementById('pain_level').value);
    formData.append('notes', document.getElementById('vitals_notes').value);

    fetch('../includes/add_vitals.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Показатели жизнедеятельности успешно сохранены');
            location.reload();
        } else {
            alert('Ошибка при сохранении: ' + (data.message || 'Неизвестная ошибка'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Произошла ошибка при сохранении показателей');
    });
}
</script>

<style>
.styled-table {
    border-collapse: collapse;
    width: 100%;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.15);
    border-radius: 10px;
    overflow: hidden;
}

.styled-table thead tr {
    background-color: #007bff;
    color: #ffffff;
    text-align: left;
}

.styled-table th,
.styled-table td {
    padding: 12px 15px;
    border-bottom: 1px solid #dddddd;
}

.styled-table tbody tr {
    background-color: #f8f9fa;
}

.styled-table tbody tr:nth-of-type(even) {
    background-color: #ffffff;
}

.styled-table tbody tr:hover {
    background-color: #f1f3f4;
}

.card {
    transition: box-shadow 0.3s ease;
}

.card:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.tab {
    transition: all 0.3s ease;
}

.tab:hover {
    background-color: #e9ecef !important;
}

.form-group label {
    color: #495057;
    font-weight: 500;
}

.input-text:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
    outline: 0;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

#icd10_suggestions div:hover {
    background-color: #f8f9fa;
}
</style>

<?php include "footer.php"; ?>
