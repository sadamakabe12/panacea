<?php
include "header.php";
require_once("../includes/medical_records_functions.php");

$useremail = $_SESSION["user"];
$userfetch = get_patient_by_email($database, $useremail);
$userid = $userfetch["pid"];
$username = $userfetch["pname"];

date_default_timezone_set('Asia/Yekaterinburg');
$today = date('Y-m-d');

// Получить медицинские записи пациента
$medical_records = get_patient_medical_records($database, $userid);

// Детали конкретной записи, если выбрана
$record_id = isset($_GET['record_id']) ? intval($_GET['record_id']) : null;
$medical_record = null;
$diagnoses = [];
$prescriptions = [];
$lab_tests = [];
$imaging_studies = [];
$treatment_plans = [];
$vitals = null;
$allergies = [];

if ($record_id) {
    // Загрузка медицинской записи и связанных данных
    $medical_record = get_medical_record($database, $record_id);
    
    // Проверка принадлежности записи текущему пациенту для безопасности
    if ($medical_record && $medical_record['patient_id'] == $userid) {
        $diagnoses = get_record_diagnoses($database, $record_id);
        $prescriptions = get_record_prescriptions($database, $record_id);
        $lab_tests = get_record_lab_tests($database, $record_id);
        $imaging_studies = get_record_imaging_studies($database, $record_id);
        $treatment_plans = get_record_treatment_plans($database, $record_id);
        $vitals = get_vitals($database, $record_id);
        $allergies = get_patient_allergies($database, $userid, false);
        
        // Логируем просмотр в журнал аудита
        $action_details = json_encode([
            'viewed_by' => $username,
            'view_type' => 'full_record',
            'doctor_id' => $medical_record['doctor_id'],
            'doctor_name' => $medical_record['doctor_name']
        ]);
        log_medical_record_action($database, $userid, 'patient', $record_id, 'view', $action_details);
    } else {
        // Запись не принадлежит текущему пациенту или не существует
        header("location: medical_records.php?error=not_found");
        exit();
    }
}
?>

<div class="dash-body">
    <table border="0" width="100%" style="border-spacing: 0;margin:0;padding:0;margin-top:25px;">
        <tr>
            <td width="13%">
                <a href="javascript:history.back()"><button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px"><font class="tn-in-text">Назад</font></button></a>
            </td>
            <td>
                <p style="font-size: 23px;padding-left:12px;font-weight: 600;">Мои медицинские записи</p>
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
                <?php if (isset($_GET['error']) && $_GET['error'] == 'not_found'): ?>
                <div class="alert alert-danger" style="margin: 20px; padding: 15px; background-color: #f8d7da; color: #721c24; border-radius: 5px;">
                    Медицинская запись не найдена или у вас нет доступа к ней.
                </div>
                <?php endif; ?>
                
                <?php if ($record_id && $medical_record): ?>
                <!-- Отображение выбранной медицинской записи -->                <div style="margin: 20px;">
                    <div class="record-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3>Медицинская запись #<?php echo $record_id; ?></h3>
                        <div>
                            <a href="export_pdf.php?id=<?php echo $record_id; ?>" class="btn-primary btn" style="padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;">Экспорт PDF</a>
                            <a href="medical_records.php" class="btn-primary-soft btn" style="padding: 10px 20px; text-decoration: none; border-radius: 5px;">Вернуться к списку</a>
                        </div>
                    </div>
                    
                    <div class="record-details" style="margin-bottom: 30px; background-color: #f8f9fa; padding: 20px; border-radius: 5px;">
                        <table style="width: 100%;">
                            <tr>
                                <td width="25%"><strong>Врач:</strong></td>
                                <td><?php echo htmlspecialchars($medical_record['doctor_name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Дата записи:</strong></td>
                                <td><?php echo date('d.m.Y', strtotime($medical_record['record_date'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Статус:</strong></td>
                                <td><?php echo $medical_record['is_finalized'] ? 'Завершена' : 'В процессе'; ?></td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="record-content" style="margin-bottom: 30px;">
                        <div class="card" style="margin-bottom: 20px; border: 1px solid #ddd; border-radius: 5px; padding: 20px;">
                            <h4 style="margin-top: 0;">Основная жалоба</h4>
                            <p><?php echo nl2br(htmlspecialchars($medical_record['chief_complaint'])); ?></p>
                        </div>
                        
                        <div class="card" style="margin-bottom: 20px; border: 1px solid #ddd; border-radius: 5px; padding: 20px;">
                            <h4 style="margin-top: 0;">Анамнез</h4>
                            <p><?php echo nl2br(htmlspecialchars($medical_record['anamnesis'])); ?></p>
                        </div>
                        
                        <div class="card" style="margin-bottom: 20px; border: 1px solid #ddd; border-radius: 5px; padding: 20px;">
                            <h4 style="margin-top: 0;">Результаты осмотра</h4>
                            <p><?php echo nl2br(htmlspecialchars($medical_record['examination_results'])); ?></p>
                        </div>
                    </div>
                      <div class="tabs-container">
                        <div class="tabs" style="display: flex; border-bottom: 1px solid #ddd;">
                            <div class="tab active" data-tab="diagnoses" style="padding: 10px 20px; cursor: pointer; background-color: #f0f0f0; border-radius: 5px 5px 0 0; font-weight: bold;">Диагнозы</div>
                            <div class="tab" data-tab="prescriptions" style="padding: 10px 20px; cursor: pointer; border-radius: 5px 5px 0 0;">Рецепты</div>
                            <div class="tab" data-tab="lab-tests" style="padding: 10px 20px; cursor: pointer; border-radius: 5px 5px 0 0;">Лабораторные тесты</div>
                            <div class="tab" data-tab="imaging-studies" style="padding: 10px 20px; cursor: pointer; border-radius: 5px 5px 0 0;">Визуальные исследования</div>
                            <div class="tab" data-tab="treatment-plans" style="padding: 10px 20px; cursor: pointer; border-radius: 5px 5px 0 0;">Планы лечения</div>
                            <div class="tab" data-tab="vitals" style="padding: 10px 20px; cursor: pointer; border-radius: 5px 5px 0 0;">Показатели жизнедеятельности</div>
                            <div class="tab" data-tab="allergies" style="padding: 10px 20px; cursor: pointer; border-radius: 5px 5px 0 0;">Аллергии</div>
                        </div>
                        
                        <!-- Вкладка Диагнозы -->
                        <div class="tab-content active" id="diagnoses-content" style="padding: 20px; border: 1px solid #ddd; border-top: none;">
                            <h3>Диагнозы</h3>
                            
                            <?php if (empty($diagnoses)): ?>
                                <p>Диагнозы не добавлены</p>
                            <?php else: ?>
                                <table class="sub-table scrolldown" border="0" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>Название</th>
                                            <th>Код МКБ-10</th>
                                            <th>Тип</th>
                                            <th>Дата</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($diagnoses as $diagnosis): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($diagnosis['diagnosis_name']); ?></td>
                                                <td><?php echo $diagnosis['icd10_code'] ? htmlspecialchars($diagnosis['icd10_code']) . ' - ' . htmlspecialchars($diagnosis['icd10_description']) : 'Не указан'; ?></td>
                                                <td>
                                                    <?php 
                                                    switch ($diagnosis['diagnosis_type']) {
                                                        case 'primary':
                                                            echo 'Основной';
                                                            break;
                                                        case 'secondary':
                                                            echo 'Сопутствующий';
                                                            break;
                                                        case 'complication':
                                                            echo 'Осложнение';
                                                            break;
                                                        default:
                                                            echo htmlspecialchars($diagnosis['diagnosis_type']);
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo date('d.m.Y', strtotime($diagnosis['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Вкладка Рецепты -->
                        <div class="tab-content" id="prescriptions-content" style="padding: 20px; border: 1px solid #ddd; border-top: none; display: none;">
                            <h3>Рецепты</h3>
                            
                            <?php if (empty($prescriptions)): ?>
                                <p>Рецепты не добавлены</p>
                            <?php else: ?>
                                <table class="sub-table scrolldown" border="0" style="width: 100%;">
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
                            <?php endif; ?>
                        </div>
                        
                        <!-- Вкладка Лабораторные тесты -->
                        <div class="tab-content" id="lab-tests-content" style="padding: 20px; border: 1px solid #ddd; border-top: none; display: none;">
                            <h3>Лабораторные тесты</h3>
                            
                            <?php if (empty($lab_tests)): ?>
                                <p>Лабораторные тесты не добавлены</p>
                            <?php else: ?>
                                <table class="sub-table scrolldown" border="0" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>Название</th>
                                            <th>Дата</th>
                                            <th>Результат</th>
                                            <th>Референсные значения</th>
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
                                                <td><?php echo $test['is_abnormal'] ? '<span style="color: red;">Отклонение</span>' : '<span style="color: green;">Норма</span>'; ?></td>
                                                <td><?php echo htmlspecialchars($test['notes']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Вкладка Визуальные исследования -->
                        <div class="tab-content" id="imaging-studies-content" style="padding: 20px; border: 1px solid #ddd; border-top: none; display: none;">
                            <h3>Визуальные исследования</h3>
                            
                            <?php if (empty($imaging_studies)): ?>
                                <p>Визуальные исследования не добавлены</p>
                            <?php else: ?>
                                <table class="sub-table scrolldown" border="0" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>Тип</th>
                                            <th>Дата</th>
                                            <th>Результат</th>
                                            <th>Изображение</th>
                                            <th>Примечания</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($imaging_studies as $study): ?>
                                            <tr>
                                                <td>
                                                    <?php 
                                                    switch ($study['study_type']) {
                                                        case 'x-ray':
                                                            echo 'Рентген';
                                                            break;
                                                        case 'ultrasound':
                                                            echo 'УЗИ';
                                                            break;
                                                        case 'mri':
                                                            echo 'МРТ';
                                                            break;
                                                        case 'ct':
                                                            echo 'КТ';
                                                            break;
                                                        default:
                                                            echo 'Другое';
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($study['study_date']); ?></td>
                                                <td><?php echo htmlspecialchars($study['study_result']); ?></td>
                                                <td>
                                                    <?php if ($study['image_path']): ?>
                                                        <a href="../<?php echo htmlspecialchars($study['image_path']); ?>" target="_blank">Просмотреть</a>
                                                    <?php else: ?>
                                                        Нет изображения
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($study['notes']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Вкладка Планы лечения -->
                        <div class="tab-content" id="treatment-plans-content" style="padding: 20px; border: 1px solid #ddd; border-top: none; display: none;">
                            <h3>Планы лечения</h3>
                            
                            <?php if (empty($treatment_plans)): ?>
                                <p>Планы лечения не добавлены</p>
                            <?php else: ?>
                                <table class="sub-table scrolldown" border="0" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>Описание</th>
                                            <th>Дата начала</th>
                                            <th>Дата окончания</th>
                                            <th>Статус</th>
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
                                                    switch ($plan['status']) {
                                                        case 'planned':
                                                            echo 'Запланировано';
                                                            break;
                                                        case 'in_progress':
                                                            echo 'В процессе';
                                                            break;
                                                        case 'completed':
                                                            echo 'Завершено';
                                                            break;
                                                        case 'cancelled':
                                                            echo 'Отменено';
                                                            break;
                                                        default:
                                                            echo htmlspecialchars($plan['status']);
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Вкладка Показатели жизнедеятельности -->
                    <div class="tab-content" id="vitals-content" style="padding: 20px; border: 1px solid #ddd; border-top: none; display: none;">
                        <h3>Показатели жизнедеятельности</h3>
                        
                        <div class="vitals-data" style="margin-bottom: 20px;">
                            <?php if ($vitals): ?>
                                <table class="sub-table" border="0" style="width: 100%;">
                                    <tbody>
                                        <tr>
                                            <th style="width: 30%;">Параметр</th>
                                            <th>Значение</th>
                                            <th>Норма</th>
                                        </tr>
                                        <tr>
                                            <td>Температура</td>
                                            <td><?php echo $vitals['temperature'] ? htmlspecialchars($vitals['temperature']) . ' °C' : 'Не измерено'; ?></td>
                                            <td>36.0 - 37.0 °C</td>
                                        </tr>
                                        <tr>
                                            <td>Пульс</td>
                                            <td><?php echo $vitals['heart_rate'] ? htmlspecialchars($vitals['heart_rate']) . ' уд/мин' : 'Не измерено'; ?></td>
                                            <td>60 - 100 уд/мин</td>
                                        </tr>
                                        <tr>
                                            <td>Артериальное давление</td>
                                            <td>
                                                <?php 
                                                if ($vitals['blood_pressure_systolic'] && $vitals['blood_pressure_diastolic']) {
                                                    echo htmlspecialchars($vitals['blood_pressure_systolic']) . '/' . 
                                                         htmlspecialchars($vitals['blood_pressure_diastolic']) . ' мм рт.ст.';
                                                } else {
                                                    echo 'Не измерено';
                                                }
                                                ?>
                                            </td>
                                            <td>90/60 - 120/80 мм рт.ст.</td>
                                        </tr>
                                        <tr>
                                            <td>Частота дыхания</td>
                                            <td><?php echo $vitals['respiratory_rate'] ? htmlspecialchars($vitals['respiratory_rate']) . ' вд/мин' : 'Не измерено'; ?></td>
                                            <td>12 - 20 вд/мин</td>
                                        </tr>
                                        <tr>
                                            <td>Сатурация кислорода</td>
                                            <td><?php echo $vitals['oxygen_saturation'] ? htmlspecialchars($vitals['oxygen_saturation']) . ' %' : 'Не измерено'; ?></td>
                                            <td>95 - 100 %</td>
                                        </tr>
                                        <tr>
                                            <td>Рост</td>
                                            <td><?php echo $vitals['height'] ? htmlspecialchars($vitals['height']) . ' см' : 'Не измерено'; ?></td>
                                            <td>-</td>
                                        </tr>
                                        <tr>
                                            <td>Вес</td>
                                            <td><?php echo $vitals['weight'] ? htmlspecialchars($vitals['weight']) . ' кг' : 'Не измерено'; ?></td>
                                            <td>-</td>
                                        </tr>
                                        <tr>
                                            <td>ИМТ (индекс массы тела)</td>
                                            <td><?php echo $vitals['bmi'] ? htmlspecialchars($vitals['bmi']) . ' кг/м²' : 'Не рассчитано'; ?></td>
                                            <td>18.5 - 24.9 кг/м²</td>
                                        </tr>
                                        <tr>
                                            <td>Уровень боли (0-10)</td>
                                            <td><?php echo $vitals['pain_level'] !== null ? htmlspecialchars($vitals['pain_level']) : 'Не оценено'; ?></td>
                                            <td>0</td>
                                        </tr>
                                        <?php if ($vitals['notes']): ?>
                                        <tr>
                                            <td>Примечания</td>
                                            <td colspan="2"><?php echo htmlspecialchars($vitals['notes']); ?></td>
                                        </tr>
                                        <?php endif; ?>                                        <tr>
                                            <td>Измерено</td>
                                            <td colspan="2" style="font-style: italic; color: #666;"><?php echo date('d.m.Y H:i', strtotime($vitals['measured_at'])); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="alert" style="background-color: #f8f9fa; padding: 15px; border-left: 4px solid #2196F3; border-radius: 4px;">
                                    <p>Данные не найдены. В вашей медицинской карте отсутствуют записи о показателях жизнедеятельности.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Вкладка Аллергии -->
                    <div class="tab-content" id="allergies-content" style="padding: 20px; border: 1px solid #ddd; border-top: none; display: none;">
                        <h3>Мои аллергии</h3>
                        
                        <div class="allergies-list" style="margin-bottom: 20px;">                            <?php if (empty($allergies)): ?>
                                <p>Аллергии не зарегистрированы</p>
                            <?php else: ?>
                                <table class="sub-table scrolldown" border="0" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>Аллерген</th>
                                            <th>Реакция</th>
                                            <th>Тяжесть</th>
                                            <th>Дата выявления</th>
                                            <th>Статус</th>
                                            <th>Примечания</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($allergies as $allergy): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($allergy['allergy_name']); ?></td>
                                                <td><?php echo htmlspecialchars($allergy['reaction'] ?: 'Не указана'); ?></td>                                                <td>
                                                    <?php 
                                                    switch ($allergy['severity']) {
                                                        case 'mild':
                                                            echo '<span style="color: #3498db;">Легкая</span>';
                                                            break;
                                                        case 'moderate':
                                                            echo '<span style="color: #f39c12;">Умеренная</span>';
                                                            break;
                                                        case 'severe':
                                                            echo '<span style="color: #e74c3c;">Тяжелая</span>';
                                                            break;
                                                        case 'life-threatening':
                                                            echo '<span style="color: #c0392b; font-weight: bold;">Жизнеугрожающая</span>';
                                                            break;
                                                        default:
                                                            echo 'Не указана';
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo $allergy['date_identified'] ? date('d.m.Y', strtotime($allergy['date_identified'])) : 'Не указана'; ?></td>
                                                <td>
                                                    <?php if ($allergy['status'] == 'active'): ?>
                                                        <span style="color: #e74c3c; font-weight: bold;">Активна</span>
                                                    <?php else: ?>
                                                        <span style="color: #7f8c8d;">Неактивна</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($allergy['notes'] ?: ''); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <?php else: ?>
                <!-- Список всех медицинских записей пациента -->
                <div style="margin: 20px;">
                    <h3>История медицинских записей</h3>
                    
                    <?php if (empty($medical_records)): ?>
                        <div class="no-records" style="text-align: center; padding: 50px 0;">
                            <img src="../img/notfound.svg" width="25%">
                            <p class="heading-main12" style="margin-top: 20px;font-size:20px;color:rgb(49, 49, 49)">У вас еще нет медицинских записей</p>
                        </div>
                    <?php else: ?>
                        <div class="records-list">
                            <table class="sub-table scrolldown" border="0" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th class="table-headin">Дата</th>
                                        <th class="table-headin">Врач</th>
                                        <th class="table-headin">Основная жалоба</th>
                                        <th class="table-headin">Статус</th>
                                        <th class="table-headin">Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($medical_records as $record): ?>
                                        <tr>
                                            <td><?php echo date('d.m.Y', strtotime($record['record_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($record['doctor_name']); ?></td>
                                            <td><?php echo mb_strlen($record['chief_complaint']) > 50 ? htmlspecialchars(mb_substr($record['chief_complaint'], 0, 50)) . '...' : htmlspecialchars($record['chief_complaint']); ?></td>
                                            <td><?php echo $record['is_finalized'] ? '<span style="color: green;">Завершена</span>' : '<span style="color: orange;">В процессе</span>'; ?></td>
                                            <td>
                                                <a href="medical_records.php?record_id=<?php echo $record['record_id']; ?>" class="non-style-link">
                                                    <button class="btn-primary-soft btn button-icon btn-view" style="padding-left: 40px;padding-top: 12px;padding-bottom: 12px;margin-top: 10px;">
                                                        <font class="tn-in-text">Просмотр</font>
                                                    </button>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </td>
        </tr>
    </table>
</div>

<!-- JavaScript для вкладок -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Получение всех вкладок и контента
    const tabs = document.querySelectorAll('.tab');
    const tabContents = document.querySelectorAll('.tab-content');
    
    // Добавление обработчика событий для каждой вкладки
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            // Удаление класса active у всех вкладок и контента
            tabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(content => content.style.display = 'none');
            
            // Добавление класса active текущей вкладке
            tab.classList.add('active');
            
            // Отображение соответствующего контента
            const tabId = tab.getAttribute('data-tab');
            document.getElementById(tabId + '-content').style.display = 'block';
        });
    });
    
    // Добавление стилей для вкладок
    tabs.forEach(tab => {
        tab.addEventListener('mouseover', () => {
            if (!tab.classList.contains('active')) {
                tab.style.backgroundColor = '#e9e9e9';
            }
        });
        
        tab.addEventListener('mouseout', () => {
            if (!tab.classList.contains('active')) {
                tab.style.backgroundColor = '';
            }
        });
    });
});
</script>

<?php include "footer.php"; ?>
