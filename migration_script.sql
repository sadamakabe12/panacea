-- ======================================================================
-- Скрипт миграции данных из старой БД в нормализованную структуру
-- Медицинская система "Панацея"
-- ======================================================================

-- ВНИМАНИЕ: Перед выполнением убедитесь, что:
-- 1. Создана новая база данных medical_system_panacea
-- 2. Выполнен скрипт medical_system_normalized.sql
-- 3. Создана резервная копия старой базы данных

USE `medical_system_panacea`;

-- Отключаем проверки внешних ключей для миграции
SET FOREIGN_KEY_CHECKS = 0;

-- ======================================================================
-- 1. МИГРАЦИЯ ПОЛЬЗОВАТЕЛЕЙ И РОЛЕЙ
-- ======================================================================

-- Миграция администраторов
INSERT INTO `users` (`email`, `password_hash`, `role_id`, `is_active`, `email_verified`)
SELECT 
    a.aemail,
    a.apassword,
    (SELECT role_id FROM user_roles WHERE role_name = 'administrator'),
    1,
    1
FROM bebebe.admin a
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = a.aemail);

-- Создание профилей администраторов
INSERT INTO `administrators` (`user_id`, `first_name`, `last_name`, `department`, `position`)
SELECT 
    u.user_id,
    'Системный',
    'Администратор',
    'IT отдел',
    'Администратор системы'
FROM users u
JOIN bebebe.admin a ON u.email = a.aemail
WHERE u.role_id = (SELECT role_id FROM user_roles WHERE role_name = 'administrator');

-- Миграция врачей
INSERT INTO `users` (`email`, `password_hash`, `role_id`, `is_active`, `email_verified`)
SELECT 
    d.docemail,
    d.docpassword,
    (SELECT role_id FROM user_roles WHERE role_name = 'doctor'),
    1,
    1
FROM bebebe.doctor d
WHERE d.docemail IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM users WHERE email = d.docemail);

-- Создание профилей врачей
INSERT INTO `doctors` (`user_id`, `first_name`, `last_name`, `phone_number`, `license_number`, `is_accepting_patients`)
SELECT 
    u.user_id,
    SUBSTRING_INDEX(TRIM(d.docname), ' ', 1) as first_name,
    CASE 
        WHEN CHAR_LENGTH(TRIM(d.docname)) - CHAR_LENGTH(REPLACE(TRIM(d.docname), ' ', '')) > 0 
        THEN SUBSTRING(TRIM(d.docname), LOCATE(' ', TRIM(d.docname)) + 1)
        ELSE 'Не указано'
    END as last_name,
    d.doctel,
    CONCAT('LIC-', LPAD(d.docid, 6, '0')),
    1
FROM users u
JOIN bebebe.doctor d ON u.email = d.docemail
WHERE u.role_id = (SELECT role_id FROM user_roles WHERE role_name = 'doctor');

-- Миграция специальностей врачей
INSERT INTO `doctor_specialties` (`doctor_id`, `specialty_id`, `is_primary`)
SELECT 
    doc.doctor_id,
    ds.specialty_id,
    1
FROM bebebe.doctor_specialty ds
JOIN bebebe.doctor d ON ds.docid = d.docid
JOIN doctors doc ON doc.license_number = CONCAT('LIC-', LPAD(d.docid, 6, '0'))
WHERE ds.specialty_id <= (SELECT MAX(specialty_id) FROM medical_specialties);

-- Миграция пациентов
INSERT INTO `users` (`email`, `password_hash`, `role_id`, `is_active`, `email_verified`)
SELECT 
    p.pemail,
    p.ppassword,
    (SELECT role_id FROM user_roles WHERE role_name = 'patient'),
    COALESCE(p.is_active, 1),
    1
FROM bebebe.patient p
WHERE p.pemail IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM users WHERE email = p.pemail);

-- Создание профилей пациентов
INSERT INTO `patients` (`user_id`, `first_name`, `last_name`, `date_of_birth`, `phone_number`, `insurance_number`, `notification_preference`)
SELECT 
    u.user_id,
    SUBSTRING_INDEX(TRIM(p.pname), ' ', 1) as first_name,
    CASE 
        WHEN CHAR_LENGTH(TRIM(p.pname)) - CHAR_LENGTH(REPLACE(TRIM(p.pname), ' ', '')) > 0 
        THEN SUBSTRING(TRIM(p.pname), LOCATE(' ', TRIM(p.pname)) + 1)
        ELSE 'Не указано'
    END as last_name,
    COALESCE(p.pdob, '1970-01-01'),
    p.ptel,
    CONCAT('INS-', LPAD(p.pid, 8, '0')),
    COALESCE(p.notification_preference, 'email')
FROM users u
JOIN bebebe.patient p ON u.email = p.pemail
WHERE u.role_id = (SELECT role_id FROM user_roles WHERE role_name = 'patient');

-- ======================================================================
-- 2. МИГРАЦИЯ АЛЛЕРГИЙ
-- ======================================================================

INSERT INTO `patient_allergies` (
    `patient_id`, `allergen_name`, `reaction_description`, 
    `severity_level`, `date_identified`, `status`, `notes`
)
SELECT 
    pat.patient_id,
    a.allergy_name,
    a.reaction,
    COALESCE(a.severity, 'mild'),
    a.date_identified,
    COALESCE(a.status, 'active'),
    a.notes
FROM bebebe.allergies a
JOIN bebebe.patient p ON a.patient_id = p.pid
JOIN patients pat ON pat.insurance_number = CONCAT('INS-', LPAD(p.pid, 8, '0'))
WHERE a.allergy_name IS NOT NULL;

-- ======================================================================
-- 3. МИГРАЦИЯ РАСПИСАНИЯ
-- ======================================================================

-- Создание слотов из старого расписания
INSERT INTO `appointment_slots` (
    `doctor_id`, `appointment_date`, `start_time`, `end_time`, 
    `max_patients`, `slot_status`
)
SELECT 
    doc.doctor_id,
    s.scheduledate,
    s.scheduletime,
    ADDTIME(s.scheduletime, '00:30:00'), -- Добавляем 30 минут к времени начала
    1,
    CASE WHEN s.status = 1 THEN 'available' ELSE 'blocked' END
FROM bebebe.schedule s
JOIN bebebe.doctor d ON CAST(s.docid AS UNSIGNED) = d.docid
JOIN doctors doc ON doc.license_number = CONCAT('LIC-', LPAD(d.docid, 6, '0'))
WHERE s.scheduledate IS NOT NULL 
AND s.scheduletime IS NOT NULL
AND s.docid REGEXP '^[0-9]+$'; -- Проверяем, что docid содержит только цифры

-- ======================================================================
-- 4. МИГРАЦИЯ ЗАПИСЕЙ НА ПРИЕМ
-- ======================================================================

INSERT INTO `appointments` (
    `slot_id`, `patient_id`, `appointment_number`, 
    `appointment_status`, `reason_for_visit`
)
SELECT 
    slot.slot_id,
    pat.patient_id,
    COALESCE(a.apponum, 1),
    CASE 
        WHEN a.status = 'scheduled' THEN 'scheduled'
        WHEN a.status = 'confirmed' THEN 'confirmed'
        WHEN a.status = 'completed' THEN 'completed'
        WHEN a.status = 'canceled' THEN 'cancelled'
        WHEN a.status = 'no_show' THEN 'no_show'
        ELSE 'scheduled'
    END,
    a.reason
FROM bebebe.appointment a
JOIN bebebe.patient p ON a.pid = p.pid
JOIN patients pat ON pat.insurance_number = CONCAT('INS-', LPAD(p.pid, 8, '0'))
JOIN bebebe.schedule s ON a.scheduleid = s.scheduleid
JOIN bebebe.doctor d ON CAST(s.docid AS UNSIGNED) = d.docid
JOIN doctors doc ON doc.license_number = CONCAT('LIC-', LPAD(d.docid, 6, '0'))
JOIN appointment_slots slot ON slot.doctor_id = doc.doctor_id 
    AND slot.appointment_date = a.appodate
    AND slot.start_time = s.scheduletime
WHERE a.pid IS NOT NULL 
AND a.scheduleid IS NOT NULL
AND s.docid REGEXP '^[0-9]+$';

-- ======================================================================
-- 5. МИГРАЦИЯ МЕДИЦИНСКИХ ЗАПИСЕЙ
-- ======================================================================

INSERT INTO `medical_records` (
    `appointment_id`, `patient_id`, `doctor_id`, `record_date`,
    `chief_complaint`, `medical_history`, `examination_findings`, 
    `record_status`
)
SELECT 
    app.appointment_id,
    pat.patient_id,
    doc.doctor_id,
    mr.record_date,
    mr.chief_complaint,
    mr.anamnesis,
    mr.examination_results,
    CASE WHEN mr.is_finalized = 1 THEN 'completed' ELSE 'draft' END
FROM bebebe.medical_records mr
JOIN bebebe.patient p ON mr.patient_id = p.pid
JOIN patients pat ON pat.insurance_number = CONCAT('INS-', LPAD(p.pid, 8, '0'))
JOIN bebebe.doctor d ON mr.doctor_id = d.docid
JOIN doctors doc ON doc.license_number = CONCAT('LIC-', LPAD(d.docid, 6, '0'))
LEFT JOIN bebebe.appointment a ON mr.appointment_id = a.appoid
LEFT JOIN appointment_slots slot ON slot.doctor_id = doc.doctor_id 
LEFT JOIN appointments app ON app.slot_id = slot.slot_id AND app.patient_id = pat.patient_id;

-- ======================================================================
-- 6. МИГРАЦИЯ ДИАГНОЗОВ
-- ======================================================================

INSERT INTO `record_diagnoses` (
    `record_id`, `icd10_code`, `diagnosis_description`, 
    `diagnosis_type`, `diagnosis_status`
)
SELECT 
    nr.record_id,
    d.icd10_code,
    d.diagnosis_name,
    COALESCE(d.diagnosis_type, 'primary'),
    'confirmed'
FROM bebebe.diagnoses d
JOIN bebebe.medical_records mr ON d.record_id = mr.record_id
JOIN bebebe.patient p ON mr.patient_id = p.pid
JOIN patients pat ON pat.insurance_number = CONCAT('INS-', LPAD(p.pid, 8, '0'))
JOIN medical_records nr ON nr.patient_id = pat.patient_id;

-- ======================================================================
-- 7. МИГРАЦИЯ ЖИЗНЕННЫХ ПОКАЗАТЕЛЕЙ
-- ======================================================================

INSERT INTO `vital_signs` (
    `record_id`, `measurement_time`, `body_temperature`, `heart_rate`,
    `blood_pressure_systolic`, `blood_pressure_diastolic`, `respiratory_rate`,
    `oxygen_saturation`, `height_cm`, `weight_kg`, `bmi`, `pain_scale`, `notes`
)
SELECT 
    nr.record_id,
    v.measured_at,
    v.temperature,
    v.heart_rate,
    v.blood_pressure_systolic,
    v.blood_pressure_diastolic,
    v.respiratory_rate,
    v.oxygen_saturation,
    v.height,
    v.weight,
    v.bmi,
    v.pain_level,
    v.notes
FROM bebebe.vitals v
JOIN bebebe.medical_records mr ON v.record_id = mr.record_id
JOIN bebebe.patient p ON mr.patient_id = p.pid
JOIN patients pat ON pat.insurance_number = CONCAT('INS-', LPAD(p.pid, 8, '0'))
JOIN medical_records nr ON nr.patient_id = pat.patient_id;

-- ======================================================================
-- 8. МИГРАЦИЯ НАЗНАЧЕНИЙ
-- ======================================================================

INSERT INTO `prescriptions` (
    `record_id`, `medication_name`, `dosage_instructions`, 
    `frequency`, `duration_days`, `special_instructions`,
    `prescription_status`, `prescribed_date`
)
SELECT 
    nr.record_id,
    pr.medication_name,
    pr.instructions,
    COALESCE(pr.frequency, 'По назначению врача'),
    CASE 
        WHEN pr.duration REGEXP '^[0-9]+' THEN CAST(SUBSTRING(pr.duration, 1, LOCATE(' ', CONCAT(pr.duration, ' ')) - 1) AS UNSIGNED)
        ELSE NULL
    END,
    pr.instructions,
    CASE WHEN pr.is_active = 1 THEN 'active' ELSE 'completed' END,
    CURDATE()
FROM bebebe.prescriptions pr
JOIN bebebe.medical_records mr ON pr.record_id = mr.record_id
JOIN bebebe.patient p ON mr.patient_id = p.pid
JOIN patients pat ON pat.insurance_number = CONCAT('INS-', LPAD(p.pid, 8, '0'))
JOIN medical_records nr ON nr.patient_id = pat.patient_id;

-- ======================================================================
-- 9. МИГРАЦИЯ ЛАБОРАТОРНЫХ ТЕСТОВ
-- ======================================================================

INSERT INTO `laboratory_tests` (
    `record_id`, `test_name`, `ordered_date`, `result_date`,
    `test_result`, `reference_range`, `result_status`, `abnormal_flag`
)
SELECT 
    nr.record_id,
    lt.test_name,
    lt.test_date,
    lt.test_date,
    lt.test_result,
    lt.reference_range,
    'completed',
    COALESCE(lt.is_abnormal, 0)
FROM bebebe.lab_tests lt
JOIN bebebe.medical_records mr ON lt.record_id = mr.record_id
JOIN bebebe.patient p ON mr.patient_id = p.pid
JOIN patients pat ON pat.insurance_number = CONCAT('INS-', LPAD(p.pid, 8, '0'))
JOIN medical_records nr ON nr.patient_id = pat.patient_id;

-- ======================================================================
-- 10. МИГРАЦИЯ ИНСТРУМЕНТАЛЬНЫХ ИССЛЕДОВАНИЙ
-- ======================================================================

INSERT INTO `imaging_studies` (
    `record_id`, `study_type`, `study_name`, `ordered_date`,
    `performed_date`, `findings`, `study_status`, `image_file_path`
)
SELECT 
    nr.record_id,
    CASE 
        WHEN img.study_type = 'x-ray' THEN 'x_ray'
        WHEN img.study_type = 'ultrasound' THEN 'ultrasound'
        WHEN img.study_type = 'mri' THEN 'mri'
        WHEN img.study_type = 'ct' THEN 'ct_scan'
        ELSE 'other'
    END,
    CONCAT(img.study_type, ' исследование'),
    img.study_date,
    img.study_date,
    img.study_result,
    'completed',
    img.image_path
FROM bebebe.imaging_studies img
JOIN bebebe.medical_records mr ON img.record_id = mr.record_id
JOIN bebebe.patient p ON mr.patient_id = p.pid
JOIN patients pat ON pat.insurance_number = CONCAT('INS-', LPAD(p.pid, 8, '0'))
JOIN medical_records nr ON nr.patient_id = pat.patient_id;

-- ======================================================================
-- 11. МИГРАЦИЯ ПЛАНОВ ЛЕЧЕНИЯ
-- ======================================================================

INSERT INTO `treatment_plans` (
    `record_id`, `plan_name`, `plan_description`, 
    `start_date`, `expected_end_date`, `plan_status`
)
SELECT 
    nr.record_id,
    'План лечения',
    tp.plan_description,
    tp.start_date,
    tp.end_date,
    COALESCE(tp.status, 'active')
FROM bebebe.treatment_plans tp
JOIN bebebe.medical_records mr ON tp.record_id = mr.record_id
JOIN bebebe.patient p ON mr.patient_id = p.pid
JOIN patients pat ON pat.insurance_number = CONCAT('INS-', LPAD(p.pid, 8, '0'))
JOIN medical_records nr ON nr.patient_id = pat.patient_id;

-- ======================================================================
-- 12. МИГРАЦИЯ АУДИТА
-- ======================================================================

INSERT INTO `medical_records_audit` (
    `user_id`, `record_id`, `action_type`, `action_details`, 
    `ip_address`, `timestamp`
)
SELECT 
    CASE 
        WHEN mra.user_type = 'admin' THEN (SELECT user_id FROM users u JOIN administrators a ON u.user_id = a.user_id LIMIT 1)
        WHEN mra.user_type = 'doctor' THEN (SELECT user_id FROM users u JOIN doctors d ON u.user_id = d.user_id LIMIT 1)
        WHEN mra.user_type = 'patient' THEN (SELECT user_id FROM users u JOIN patients p ON u.user_id = p.user_id LIMIT 1)
        ELSE 1
    END,
    nr.record_id,
    mra.action,
    mra.action_details,
    mra.ip_address,
    mra.timestamp
FROM bebebe.medical_records_audit mra
JOIN bebebe.medical_records mr ON mra.record_id = mr.record_id
JOIN bebebe.patient p ON mr.patient_id = p.pid
JOIN patients pat ON pat.insurance_number = CONCAT('INS-', LPAD(p.pid, 8, '0'))
JOIN medical_records nr ON nr.patient_id = pat.patient_id;

-- Включаем обратно проверки внешних ключей
SET FOREIGN_KEY_CHECKS = 1;

-- ======================================================================
-- ПРОВЕРКА РЕЗУЛЬТАТОВ МИГРАЦИИ
-- ======================================================================

SELECT 'Результаты миграции:' as status;

SELECT 
    'Пользователи' as table_name,
    COUNT(*) as migrated_count
FROM users
UNION ALL
SELECT 
    'Администраторы',
    COUNT(*)
FROM administrators
UNION ALL
SELECT 
    'Врачи',
    COUNT(*)
FROM doctors
UNION ALL
SELECT 
    'Пациенты',
    COUNT(*)
FROM patients
UNION ALL
SELECT 
    'Аллергии',
    COUNT(*)
FROM patient_allergies
UNION ALL
SELECT 
    'Слоты для записи',
    COUNT(*)
FROM appointment_slots
UNION ALL
SELECT 
    'Записи на прием',
    COUNT(*)
FROM appointments
UNION ALL
SELECT 
    'Медицинские записи',
    COUNT(*)
FROM medical_records
UNION ALL
SELECT 
    'Диагнозы',
    COUNT(*)
FROM record_diagnoses
UNION ALL
SELECT 
    'Жизненные показатели',
    COUNT(*)
FROM vital_signs
UNION ALL
SELECT 
    'Назначения',
    COUNT(*)
FROM prescriptions;

-- Создаем системный лог о завершении миграции
INSERT INTO `system_logs` (`log_level`, `log_category`, `message`, `additional_data`)
VALUES ('INFO', 'DATA_MIGRATION', 'Завершена миграция данных из старой БД в нормализованную структуру', 
        JSON_OBJECT('migration_date', NOW(), 'status', 'completed'));

SELECT 'Миграция данных завершена успешно!' as status;
