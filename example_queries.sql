-- ======================================================================
-- Примеры запросов для нормализованной БД медицинской системы "Панацея"
-- ======================================================================

USE `medical_system_panacea`;

-- ======================================================================
-- 1. ЗАПРОСЫ ДЛЯ УПРАВЛЕНИЯ ПОЛЬЗОВАТЕЛЯМИ
-- ======================================================================

-- Получение всех врачей с их специальностями
SELECT 
    d.doctor_id,
    CONCAT(d.last_name, ' ', d.first_name, COALESCE(CONCAT(' ', d.middle_name), '')) as full_name,
    d.phone_number,
    d.license_number,
    d.experience_years,
    GROUP_CONCAT(ms.specialty_name SEPARATOR ', ') as specialties,
    d.consultation_fee,
    d.is_accepting_patients
FROM doctors d
JOIN users u ON d.user_id = u.user_id
LEFT JOIN doctor_specialties ds ON d.doctor_id = ds.doctor_id
LEFT JOIN medical_specialties ms ON ds.specialty_id = ms.specialty_id
WHERE u.is_active = 1 AND d.is_accepting_patients = 1
GROUP BY d.doctor_id
ORDER BY d.last_name, d.first_name;

-- Получение информации о пациенте с адресами и аллергиями
SELECT 
    p.patient_id,
    CONCAT(p.last_name, ' ', p.first_name) as full_name,
    p.date_of_birth,
    p.gender,
    p.phone_number,
    p.blood_type,
    pa.street_address,
    pa.city,
    pa.postal_code,
    GROUP_CONCAT(DISTINCT pal.allergen_name SEPARATOR ', ') as allergies
FROM patients p
JOIN users u ON p.user_id = u.user_id
LEFT JOIN patient_addresses pa ON p.patient_id = pa.patient_id AND pa.is_primary = 1
LEFT JOIN patient_allergies pal ON p.patient_id = pal.patient_id AND pal.status = 'active'
WHERE u.is_active = 1
GROUP BY p.patient_id
ORDER BY p.last_name, p.first_name;

-- ======================================================================
-- 2. ЗАПРОСЫ ДЛЯ РАСПИСАНИЯ И ЗАПИСЕЙ
-- ======================================================================

-- Доступные слоты для записи на определенную дату
SELECT 
    slot.slot_id,
    CONCAT(d.last_name, ' ', d.first_name) as doctor_name,
    GROUP_CONCAT(DISTINCT ms.specialty_name SEPARATOR ', ') as specialties,
    slot.appointment_date,
    slot.start_time,
    slot.end_time,
    (slot.max_patients - slot.current_bookings) as available_spots,
    d.consultation_fee
FROM appointment_slots slot
JOIN doctors d ON slot.doctor_id = d.doctor_id
JOIN users u ON d.user_id = u.user_id
LEFT JOIN doctor_specialties ds ON d.doctor_id = ds.doctor_id
LEFT JOIN medical_specialties ms ON ds.specialty_id = ms.specialty_id
WHERE slot.appointment_date = '2025-06-05'
    AND slot.slot_status = 'available'
    AND slot.current_bookings < slot.max_patients
    AND u.is_active = 1
    AND d.is_accepting_patients = 1
GROUP BY slot.slot_id
ORDER BY slot.start_time;

-- Расписание врача на неделю
SELECT 
    slot.appointment_date,
    slot.start_time,
    slot.end_time,
    slot.current_bookings,
    slot.max_patients,
    slot.slot_status,
    COUNT(app.appointment_id) as confirmed_appointments
FROM appointment_slots slot
LEFT JOIN appointments app ON slot.slot_id = app.slot_id 
    AND app.appointment_status IN ('scheduled', 'confirmed')
WHERE slot.doctor_id = 1
    AND slot.appointment_date BETWEEN '2025-06-02' AND '2025-06-08'
GROUP BY slot.slot_id
ORDER BY slot.appointment_date, slot.start_time;

-- ======================================================================
-- 3. ЗАПРОСЫ ДЛЯ МЕДИЦИНСКИХ ЗАПИСЕЙ
-- ======================================================================

-- История болезни пациента
SELECT 
    mr.record_date,
    CONCAT(d.last_name, ' ', d.first_name) as doctor_name,
    GROUP_CONCAT(DISTINCT ms.specialty_name SEPARATOR ', ') as doctor_specialties,
    mr.visit_type,
    mr.chief_complaint,
    mr.clinical_impression,
    GROUP_CONCAT(DISTINCT rd.diagnosis_description SEPARATOR '; ') as diagnoses,
    mr.record_status
FROM medical_records mr
JOIN doctors d ON mr.doctor_id = d.doctor_id
LEFT JOIN doctor_specialties ds ON d.doctor_id = ds.doctor_id
LEFT JOIN medical_specialties ms ON ds.specialty_id = ms.specialty_id
LEFT JOIN record_diagnoses rd ON mr.record_id = rd.record_id AND rd.diagnosis_status = 'confirmed'
WHERE mr.patient_id = 1
GROUP BY mr.record_id
ORDER BY mr.record_date DESC
LIMIT 10;

-- Жизненные показатели пациента за последний месяц
SELECT 
    vs.measurement_time,
    vs.body_temperature,
    vs.heart_rate,
    CONCAT(vs.blood_pressure_systolic, '/', vs.blood_pressure_diastolic) as blood_pressure,
    vs.respiratory_rate,
    vs.oxygen_saturation,
    vs.weight_kg,
    vs.bmi,
    vs.glucose_level
FROM vital_signs vs
JOIN medical_records mr ON vs.record_id = mr.record_id
WHERE mr.patient_id = 1
    AND vs.measurement_time >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
ORDER BY vs.measurement_time DESC;

-- Текущие назначения пациента
SELECT 
    p.medication_name,
    p.dosage_instructions,
    p.frequency,
    p.duration_days,
    p.prescribed_date,
    p.expiry_date,
    p.special_instructions,
    CONCAT(d.last_name, ' ', d.first_name) as prescribing_doctor
FROM prescriptions p
JOIN medical_records mr ON p.record_id = mr.record_id
JOIN doctors d ON mr.doctor_id = d.doctor_id
WHERE mr.patient_id = 1
    AND p.prescription_status = 'active'
    AND (p.expiry_date IS NULL OR p.expiry_date > CURDATE())
ORDER BY p.prescribed_date DESC;

-- ======================================================================
-- 4. АНАЛИТИЧЕСКИЕ ЗАПРОСЫ
-- ======================================================================

-- Статистика по записям за месяц
SELECT 
    DATE(slot.appointment_date) as appointment_date,
    COUNT(app.appointment_id) as total_appointments,
    SUM(CASE WHEN app.appointment_status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN app.appointment_status = 'no_show' THEN 1 ELSE 0 END) as no_shows,
    SUM(CASE WHEN app.appointment_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
FROM appointment_slots slot
LEFT JOIN appointments app ON slot.slot_id = app.slot_id
WHERE slot.appointment_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
GROUP BY DATE(slot.appointment_date)
ORDER BY appointment_date DESC;

-- Топ диагнозов по частоте
SELECT 
    rd.diagnosis_description,
    rd.icd10_code,
    icd.category_name,
    COUNT(*) as frequency,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM record_diagnoses WHERE diagnosis_status = 'confirmed'), 2) as percentage
FROM record_diagnoses rd
LEFT JOIN icd10_diagnoses icd ON rd.icd10_code = icd.icd10_code
WHERE rd.diagnosis_status = 'confirmed'
    AND rd.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
GROUP BY rd.diagnosis_description, rd.icd10_code
ORDER BY frequency DESC
LIMIT 20;

-- Загруженность врачей
SELECT 
    CONCAT(d.last_name, ' ', d.first_name) as doctor_name,
    GROUP_CONCAT(DISTINCT ms.specialty_name SEPARATOR ', ') as specialties,
    COUNT(DISTINCT slot.slot_id) as total_slots,
    COUNT(DISTINCT app.appointment_id) as booked_appointments,
    ROUND(COUNT(DISTINCT app.appointment_id) * 100.0 / NULLIF(COUNT(DISTINCT slot.slot_id), 0), 2) as occupancy_rate,
    SUM(CASE WHEN app.appointment_status = 'completed' THEN 1 ELSE 0 END) as completed_appointments
FROM doctors d
LEFT JOIN doctor_specialties ds ON d.doctor_id = ds.doctor_id
LEFT JOIN medical_specialties ms ON ds.specialty_id = ms.specialty_id
LEFT JOIN appointment_slots slot ON d.doctor_id = slot.doctor_id 
    AND slot.appointment_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
LEFT JOIN appointments app ON slot.slot_id = app.slot_id
WHERE d.is_accepting_patients = 1
GROUP BY d.doctor_id
ORDER BY occupancy_rate DESC;

-- ======================================================================
-- 5. ЗАПРОСЫ ДЛЯ ОТЧЕТНОСТИ
-- ======================================================================

-- Отчет по лабораторным исследованиям
SELECT 
    lt.test_category,
    lt.test_name,
    COUNT(*) as total_tests,
    SUM(CASE WHEN lt.result_status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN lt.abnormal_flag = 1 THEN 1 ELSE 0 END) as abnormal_results,
    SUM(CASE WHEN lt.critical_flag = 1 THEN 1 ELSE 0 END) as critical_results,
    AVG(DATEDIFF(lt.result_date, lt.ordered_date)) as avg_turnaround_days
FROM laboratory_tests lt
WHERE lt.ordered_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
GROUP BY lt.test_category, lt.test_name
HAVING COUNT(*) >= 5
ORDER BY total_tests DESC;

-- Отчет по выписанным рецептам
SELECT 
    p.medication_name,
    COUNT(*) as prescription_count,
    COUNT(DISTINCT mr.patient_id) as unique_patients,
    AVG(p.duration_days) as avg_duration_days,
    GROUP_CONCAT(DISTINCT ms.specialty_name SEPARATOR ', ') as prescribing_specialties
FROM prescriptions p
JOIN medical_records mr ON p.record_id = mr.record_id
JOIN doctors d ON mr.doctor_id = d.doctor_id
LEFT JOIN doctor_specialties ds ON d.doctor_id = ds.doctor_id
LEFT JOIN medical_specialties ms ON ds.specialty_id = ms.specialty_id
WHERE p.prescribed_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
GROUP BY p.medication_name
HAVING prescription_count >= 10
ORDER BY prescription_count DESC;

-- ======================================================================
-- 6. ЗАПРОСЫ ДЛЯ УВЕДОМЛЕНИЙ
-- ======================================================================

-- Записи, требующие напоминания (за 24 часа до приема)
SELECT 
    app.appointment_id,
    CONCAT(p.last_name, ' ', p.first_name) as patient_name,
    p.phone_number,
    u_patient.email as patient_email,
    CONCAT(d.last_name, ' ', d.first_name) as doctor_name,
    slot.appointment_date,
    slot.start_time,
    p.notification_preference
FROM appointments app
JOIN appointment_slots slot ON app.slot_id = slot.slot_id
JOIN patients p ON app.patient_id = p.patient_id
JOIN users u_patient ON p.user_id = u_patient.user_id
JOIN doctors d ON slot.doctor_id = d.doctor_id
WHERE app.appointment_status IN ('scheduled', 'confirmed')
    AND TIMESTAMP(slot.appointment_date, slot.start_time) BETWEEN NOW() + INTERVAL 23 HOUR AND NOW() + INTERVAL 25 HOUR
    AND NOT EXISTS (
        SELECT 1 FROM appointment_notifications 
        WHERE appointment_id = app.appointment_id 
        AND notification_type = 'reminder' 
        AND notification_status = 'sent'
    );

-- ======================================================================
-- 7. ЗАПРОСЫ ДЛЯ АУДИТА И БЕЗОПАСНОСТИ
-- ======================================================================

-- Аудит доступа к медицинским записям конкретного пациента
SELECT 
    mra.timestamp,
    CASE 
        WHEN a.admin_id IS NOT NULL THEN CONCAT('Admin: ', a.first_name, ' ', a.last_name)
        WHEN d.doctor_id IS NOT NULL THEN CONCAT('Doctor: ', d.first_name, ' ', d.last_name)
        WHEN p.patient_id IS NOT NULL THEN CONCAT('Patient: ', p.first_name, ' ', p.last_name)
        ELSE 'Unknown User'
    END as user_name,
    mra.action_type,
    mra.action_details,
    mra.ip_address
FROM medical_records_audit mra
JOIN users u ON mra.user_id = u.user_id
LEFT JOIN administrators a ON u.user_id = a.user_id
LEFT JOIN doctors d ON u.user_id = d.user_id
LEFT JOIN patients p ON u.user_id = p.user_id
JOIN medical_records mr ON mra.record_id = mr.record_id
WHERE mr.patient_id = 1
ORDER BY mra.timestamp DESC
LIMIT 50;

-- Системные логи по категориям за последние 24 часа
SELECT 
    sl.log_level,
    sl.log_category,
    COUNT(*) as log_count,
    MAX(sl.timestamp) as last_occurrence
FROM system_logs sl
WHERE sl.timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY sl.log_level, sl.log_category
ORDER BY 
    FIELD(sl.log_level, 'CRITICAL', 'ERROR', 'WARNING', 'INFO', 'DEBUG'),
    log_count DESC;

-- ======================================================================
-- 8. СЛОЖНЫЕ АНАЛИТИЧЕСКИЕ ЗАПРОСЫ
-- ======================================================================

-- Анализ эффективности лечения по диагнозам
WITH diagnosis_outcomes AS (
    SELECT 
        rd.diagnosis_description,
        rd.icd10_code,
        COUNT(DISTINCT mr.patient_id) as total_patients,
        AVG(
            CASE 
                WHEN tp.plan_status = 'completed' AND tp.outcome_assessment IS NOT NULL 
                THEN DATEDIFF(tp.actual_end_date, tp.start_date)
                ELSE NULL
            END
        ) as avg_treatment_duration,
        SUM(CASE WHEN tp.plan_status = 'completed' THEN 1 ELSE 0 END) as successful_treatments,
        COUNT(DISTINCT tp.plan_id) as total_treatment_plans
    FROM record_diagnoses rd
    JOIN medical_records mr ON rd.record_id = mr.record_id
    LEFT JOIN treatment_plans tp ON mr.record_id = tp.record_id
    WHERE rd.diagnosis_status = 'confirmed'
        AND mr.record_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
    GROUP BY rd.diagnosis_description, rd.icd10_code
    HAVING total_patients >= 5
)
SELECT 
    diagnosis_description,
    icd10_code,
    total_patients,
    total_treatment_plans,
    successful_treatments,
    ROUND(successful_treatments * 100.0 / NULLIF(total_treatment_plans, 0), 2) as success_rate_percent,
    ROUND(avg_treatment_duration, 1) as avg_treatment_days
FROM diagnosis_outcomes
ORDER BY success_rate_percent DESC, total_patients DESC;

-- Прогнозирование загруженности на следующую неделю
SELECT 
    future_date,
    DAYNAME(future_date) as day_name,
    COUNT(slot.slot_id) as available_slots,
    SUM(slot.max_patients) as max_capacity,
    ROUND(AVG(historical_occupancy.avg_occupancy), 2) as predicted_occupancy_rate
FROM (
    SELECT CURDATE() + INTERVAL (a.i + 1) DAY as future_date
    FROM (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6) a
) dates
LEFT JOIN appointment_slots slot ON slot.appointment_date = dates.future_date
    AND slot.slot_status = 'available'
LEFT JOIN (
    SELECT 
        DAYOFWEEK(slot.appointment_date) as day_of_week,
        AVG(slot.current_bookings * 100.0 / slot.max_patients) as avg_occupancy
    FROM appointment_slots slot
    WHERE slot.appointment_date >= DATE_SUB(CURDATE(), INTERVAL 4 WEEK)
        AND slot.appointment_date < CURDATE()
        AND slot.slot_status = 'available'
    GROUP BY DAYOFWEEK(slot.appointment_date)
) historical_occupancy ON DAYOFWEEK(dates.future_date) = historical_occupancy.day_of_week
GROUP BY future_date
ORDER BY future_date;
