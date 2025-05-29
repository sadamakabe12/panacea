<?php
// admin/add_medical_record.php
include("header.php");
require_once("../includes/medical_records_functions.php");
require_once("../includes/patient_functions.php");
require_once("../includes/doctor_functions.php");

// Проверка авторизации
if (!isset($_SESSION["user"]) || $_SESSION["usertype"] != 'a') {
    header("location: ../login.php");
    exit();
}

$admin_email = $_SESSION["user"];

date_default_timezone_set('Asia/Yekaterinburg');
$today = date('Y-m-d');

// Инициализация переменных
$patients = get_all_patients_simple($database);
$doctors = get_all_doctors_simple($database);
$message = '';
$error = '';

// Обработка POST запроса
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'create_record') {
        $patient_id = isset($_POST['patient_id']) ? intval($_POST['patient_id']) : 0;
        $doctor_id = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : 0;
        $chief_complaint = $_POST['chief_complaint'] ?? '';
        $anamnesis = $_POST['anamnesis'] ?? '';
        $examination_results = $_POST['examination_results'] ?? '';
        $is_finalized = isset($_POST['is_finalized']) ? true : false;
        
        if ($patient_id && $doctor_id) {
            $record_id = create_medical_record($database, $patient_id, $doctor_id, null, $chief_complaint, $anamnesis, $examination_results, $is_finalized);
            
            if ($record_id) {
                // Логируем создание в журнал аудита
                $patient_info = get_patient_by_id($database, $patient_id);
                $doctor_info = get_doctor_by_id($database, $doctor_id);
                
                $action_details = json_encode([
                    'created_by' => $admin_email,
                    'patient_id' => $patient_id,
                    'patient_name' => $patient_info['pname'],
                    'doctor_id' => $doctor_id,
                    'doctor_name' => $doctor_info['docname']
                ]);
                
                log_medical_record_action($database, 1, 'admin', $record_id, 'create', $action_details);
                
                $message = "Медицинская запись успешно создана. ID записи: " . $record_id;
            } else {
                $error = "Ошибка при создании медицинской записи";
            }
        } else {
            $error = "Необходимо выбрать пациента и врача";
        }
    }
}
?>

<div class="dash-body">
    <table border="0" width="100%" style="border-spacing: 0;margin:0;padding:0;margin-top:25px;">
        <tr>
            <td width="13%">
                <a href="medical_records.php"><button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px"><font class="tn-in-text">Назад</font></button></a>
            </td>
            <td>
                <p style="font-size: 23px;padding-left:12px;font-weight: 600;">Добавление новой медицинской записи</p>
            </td>
            <td width="15%">
                <p style="font-size: 14px;color: rgb(119, 119, 119);padding: 0;margin: 0;text-align: right;">Сегодняшняя дата</p>
                <p class="heading-sub12" style="padding: 0;margin: 0;"><?php echo $today; ?></p>
            </td>
            <td width="10%">
                <button class="btn-label" style="display: flex;justify-content: center;align-items: center;"><img src="../img/calendar.svg" width="100%"></button>
            </td>
        </tr>
        
        <tr>
            <td colspan="4">
                <?php if (!empty($message)): ?>
                <div class="alert alert-success" style="margin: 20px; padding: 15px; background-color: #d4edda; color: #155724; border-radius: 5px;">
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                <div class="alert alert-danger" style="margin: 20px; padding: 15px; background-color: #f8d7da; color: #721c24; border-radius: 5px;">
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <div style="margin: 20px; background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                    <h3>Создание новой медицинской записи</h3>
                    <form method="post" action="">
                        <input type="hidden" name="action" value="create_record">
                        
                        <div class="row" style="display: flex; flex-wrap: wrap; margin: 0 -10px;">
                            <div class="col" style="flex: 1; min-width: 300px; padding: 0 10px; margin-bottom: 20px;">
                                <label for="patient_id" style="display: block; margin-bottom: 8px; font-weight: bold;">Пациент:</label>
                                <select id="patient_id" name="patient_id" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                                    <option value="">Выберите пациента</option>
                                    <?php foreach ($patients as $patient): ?>
                                        <option value="<?php echo $patient['pid']; ?>"><?php echo htmlspecialchars($patient['pname']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col" style="flex: 1; min-width: 300px; padding: 0 10px; margin-bottom: 20px;">
                                <label for="doctor_id" style="display: block; margin-bottom: 8px; font-weight: bold;">Врач:</label>
                                <select id="doctor_id" name="doctor_id" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                                    <option value="">Выберите врача</option>
                                    <?php foreach ($doctors as $doctor): ?>
                                        <option value="<?php echo $doctor['docid']; ?>"><?php echo htmlspecialchars($doctor['docname']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label for="chief_complaint" style="display: block; margin-bottom: 8px; font-weight: bold;">Основная жалоба:</label>
                            <textarea id="chief_complaint" name="chief_complaint" rows="3" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"></textarea>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label for="anamnesis" style="display: block; margin-bottom: 8px; font-weight: bold;">Анамнез:</label>
                            <textarea id="anamnesis" name="anamnesis" rows="5" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"></textarea>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label for="examination_results" style="display: block; margin-bottom: 8px; font-weight: bold;">Результаты осмотра:</label>
                            <textarea id="examination_results" name="examination_results" rows="5" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"></textarea>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="display: inline-flex; align-items: center;">
                                <input type="checkbox" name="is_finalized" value="1">
                                <span style="margin-left: 8px;">Запись завершена</span>
                            </label>
                        </div>
                        
                        <div style="margin-top: 30px;">
                            <button type="submit" class="btn-primary btn" style="padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">Создать медицинскую запись</button>
                        </div>
                    </form>
                </div>
            </td>
        </tr>
    </table>
</div>

<style>
    .row {
        display: flex;
        flex-wrap: wrap;
        margin: 0 -10px;
    }
    
    .col {
        flex: 1;
        min-width: 300px;
        padding: 0 10px;
        margin-bottom: 20px;
    }
</style>

<?php include("footer.php"); ?>
