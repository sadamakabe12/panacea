-- ======================================================================
-- Нормализованная база данных медицинской системы "Панацея"
-- Соответствует 1НФ, 2НФ, 3НФ с понятными названиями таблиц
-- Дата создания: 2 июня 2025
-- ======================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- Создание базы данных
CREATE DATABASE IF NOT EXISTS `medical_system_panacea` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `medical_system_panacea`;

-- ======================================================================
-- ТАБЛИЦЫ УПРАВЛЕНИЯ ПОЛЬЗОВАТЕЛЯМИ И БЕЗОПАСНОСТИ
-- ======================================================================

-- Роли пользователей в системе
CREATE TABLE `user_roles` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL UNIQUE,
  `role_description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Основная таблица пользователей системы
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL UNIQUE,
  `password_hash` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `failed_login_attempts` int(11) NOT NULL DEFAULT 0,
  `account_locked_until` timestamp NULL DEFAULT NULL,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  KEY `idx_email` (`email`),
  KEY `idx_role_id` (`role_id`),
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `user_roles` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Профили администраторов
CREATE TABLE `administrators` (
  `admin_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL UNIQUE,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`admin_id`),
  CONSTRAINT `fk_administrators_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================================
-- СПРАВОЧНЫЕ ТАБЛИЦЫ
-- ======================================================================

-- Медицинские специальности
CREATE TABLE `medical_specialties` (
  `specialty_id` int(11) NOT NULL AUTO_INCREMENT,
  `specialty_name` varchar(100) NOT NULL UNIQUE,
  `specialty_code` varchar(10) UNIQUE DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`specialty_id`),
  KEY `idx_specialty_name` (`specialty_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Категории МКБ-10 (отдельная таблица для нормализации)
CREATE TABLE `icd10_categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_code` varchar(10) UNIQUE DEFAULT NULL COMMENT 'Код категории МКБ-10 (например, I00-I99)',
  `category_name` varchar(255) NOT NULL COMMENT 'Название категории (например, Болезни системы кровообращения)',
  `category_description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`category_id`),
  KEY `idx_category_name` (`category_name`),
  KEY `idx_category_code` (`category_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Справочник кодов МКБ-10
CREATE TABLE `icd10_diagnoses` (
  `icd10_code` varchar(10) NOT NULL,
  `diagnosis_name` text NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`icd10_code`),
  KEY `idx_category_id` (`category_id`),
  CONSTRAINT `fk_icd10_diagnoses_category` FOREIGN KEY (`category_id`) REFERENCES `icd10_categories` (`category_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================================
-- ТАБЛИЦЫ МЕДИЦИНСКОГО ПЕРСОНАЛА
-- ======================================================================

-- Профили врачей
CREATE TABLE `doctors` (
  `doctor_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL UNIQUE,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `license_number` varchar(50) UNIQUE DEFAULT NULL,
  `experience_years` int(11) DEFAULT NULL,
  `education` text DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `consultation_fee` decimal(10,2) DEFAULT NULL,
  `is_accepting_patients` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`doctor_id`),
  KEY `idx_full_name` (`last_name`, `first_name`),
  KEY `idx_license` (`license_number`),
  CONSTRAINT `fk_doctors_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Связь врачей со специальностями (многие ко многим)
CREATE TABLE `doctor_specialties` (
  `doctor_specialty_id` int(11) NOT NULL AUTO_INCREMENT,
  `doctor_id` int(11) NOT NULL,
  `specialty_id` int(11) NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `certification_date` date DEFAULT NULL,
  `certification_number` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`doctor_specialty_id`),
  UNIQUE KEY `unique_doctor_specialty` (`doctor_id`, `specialty_id`),
  KEY `idx_doctor_id` (`doctor_id`),
  KEY `idx_specialty_id` (`specialty_id`),
  CONSTRAINT `fk_doctor_specialties_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_doctor_specialties_specialty` FOREIGN KEY (`specialty_id`) REFERENCES `medical_specialties` (`specialty_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================================
-- ТАБЛИЦЫ ПАЦИЕНТОВ
-- ======================================================================

-- Профили пациентов
CREATE TABLE `patients` (
  `patient_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL UNIQUE,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('male','female','other') NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `emergency_contact_name` varchar(200) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `insurance_number` varchar(50) DEFAULT NULL,
  `blood_type` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') DEFAULT NULL,
  `notification_preference` enum('email','sms','both','none') NOT NULL DEFAULT 'email',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`patient_id`),
  KEY `idx_full_name` (`last_name`, `first_name`),
  KEY `idx_birth_date` (`date_of_birth`),
  KEY `idx_insurance` (`insurance_number`),
  CONSTRAINT `fk_patients_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Адреса пациентов
CREATE TABLE `patient_addresses` (
  `address_id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `address_type` enum('home','work','temporary') NOT NULL DEFAULT 'home',
  `country` varchar(100) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `street_address` varchar(255) NOT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`address_id`),
  KEY `idx_patient_id` (`patient_id`),
  KEY `idx_city` (`city`),  CONSTRAINT `fk_patient_addresses_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Справочник аллергенов
CREATE TABLE `allergens` (
  `allergen_id` int(11) NOT NULL AUTO_INCREMENT,
  `allergen_name` varchar(255) NOT NULL UNIQUE,
  `allergen_category` enum('food','medication','environmental','contact','other') NOT NULL,
  `common_reactions` text DEFAULT NULL COMMENT 'Типичные реакции на данный аллерген',
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`allergen_id`),
  KEY `idx_allergen_name` (`allergen_name`),
  KEY `idx_allergen_category` (`allergen_category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Аллергии пациентов
CREATE TABLE `patient_allergies` (
  `allergy_id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `allergen_id` int(11) NOT NULL,
  `reaction_description` text DEFAULT NULL,
  `severity_level` enum('mild','moderate','severe','life_threatening') NOT NULL,
  `date_identified` date DEFAULT NULL,
  `status` enum('active','inactive','resolved') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`allergy_id`),
  UNIQUE KEY `unique_patient_allergen` (`patient_id`, `allergen_id`),
  KEY `idx_patient_id` (`patient_id`),
  KEY `idx_allergen_id` (`allergen_id`),
  KEY `idx_severity` (`severity_level`),
  CONSTRAINT `fk_patient_allergies_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_patient_allergies_allergen` FOREIGN KEY (`allergen_id`) REFERENCES `allergens` (`allergen_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================================
-- РАСПИСАНИЕ И ШАБЛОНЫ ПРИЕМОВ
-- ======================================================================

-- Шаблоны приемов врачей
CREATE TABLE `appointment_templates` (
  `template_id` int(11) NOT NULL AUTO_INCREMENT,
  `doctor_id` int(11) NOT NULL,
  `template_name` varchar(255) NOT NULL,
  `duration_minutes` int(11) NOT NULL DEFAULT 30,
  `appointment_type` enum('consultation','examination','procedure','follow_up','emergency') NOT NULL DEFAULT 'consultation',
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`template_id`),
  KEY `idx_doctor_id` (`doctor_id`),
  CONSTRAINT `fk_appointment_templates_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Регулярное расписание врачей
CREATE TABLE `doctor_regular_schedules` (
  `schedule_id` int(11) NOT NULL AUTO_INCREMENT,
  `doctor_id` int(11) NOT NULL,
  `day_of_week` tinyint(1) NOT NULL COMMENT '1=Понедельник, 7=Воскресенье',
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `break_start_time` time DEFAULT NULL,
  `break_end_time` time DEFAULT NULL,
  `appointment_duration_minutes` int(11) NOT NULL DEFAULT 30,
  `max_patients_per_day` int(11) DEFAULT NULL COMMENT 'NULL = без ограничений',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`schedule_id`),
  UNIQUE KEY `unique_doctor_day` (`doctor_id`, `day_of_week`),
  KEY `idx_doctor_id` (`doctor_id`),
  KEY `idx_day_of_week` (`day_of_week`),
  CONSTRAINT `fk_doctor_schedules_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE,
  CONSTRAINT `chk_day_of_week` CHECK (`day_of_week` BETWEEN 1 AND 7),
  CONSTRAINT `chk_work_hours` CHECK (`start_time` < `end_time`),
  CONSTRAINT `chk_break_hours` CHECK (`break_start_time` IS NULL OR `break_end_time` IS NULL OR `break_start_time` < `break_end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Конкретные слоты для записи
CREATE TABLE `appointment_slots` (
  `slot_id` int(11) NOT NULL AUTO_INCREMENT,
  `doctor_id` int(11) NOT NULL,
  `template_id` int(11) DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `max_patients` int(11) NOT NULL DEFAULT 1,
  `current_bookings` int(11) NOT NULL DEFAULT 0,
  `slot_status` enum('available','booked','blocked','cancelled') NOT NULL DEFAULT 'available',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`slot_id`),
  UNIQUE KEY `unique_doctor_datetime` (`doctor_id`, `appointment_date`, `start_time`),
  KEY `idx_doctor_date` (`doctor_id`, `appointment_date`),
  KEY `idx_status` (`slot_status`),
  KEY `idx_template_id` (`template_id`),
  CONSTRAINT `fk_appointment_slots_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_appointment_slots_template` FOREIGN KEY (`template_id`) REFERENCES `appointment_templates` (`template_id`) ON DELETE SET NULL,
  CONSTRAINT `chk_slot_times` CHECK (`start_time` < `end_time`),
  CONSTRAINT `chk_bookings_count` CHECK (`current_bookings` <= `max_patients`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Исключения в расписании (отпуска, больничные и т.д.)
CREATE TABLE `schedule_exceptions` (
  `exception_id` int(11) NOT NULL AUTO_INCREMENT,
  `doctor_id` int(11) NOT NULL,
  `exception_date` date NOT NULL,
  `exception_type` enum('vacation','sick_leave','conference','emergency','personal') NOT NULL,
  `start_time` time DEFAULT NULL COMMENT 'NULL = весь день',
  `end_time` time DEFAULT NULL COMMENT 'NULL = весь день',
  `description` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`exception_id`),
  KEY `idx_doctor_date` (`doctor_id`, `exception_date`),
  KEY `idx_exception_type` (`exception_type`),
  CONSTRAINT `fk_schedule_exceptions_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================================
-- ЗАПИСИ НА ПРИЕМ
-- ======================================================================

-- Записи на прием
CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL AUTO_INCREMENT,
  `slot_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `appointment_number` int(11) NOT NULL COMMENT 'Номер по порядку в рамках слота',
  `appointment_status` enum('scheduled','confirmed','in_progress','completed','cancelled','no_show') NOT NULL DEFAULT 'scheduled',
  `reason_for_visit` text DEFAULT NULL,
  `patient_notes` text DEFAULT NULL,
  `doctor_notes` text DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancelled_by_user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`appointment_id`),
  UNIQUE KEY `unique_slot_number` (`slot_id`, `appointment_number`),
  KEY `idx_patient_id` (`patient_id`),
  KEY `idx_status` (`appointment_status`),
  KEY `idx_cancelled_by` (`cancelled_by_user_id`),
  CONSTRAINT `fk_appointments_slot` FOREIGN KEY (`slot_id`) REFERENCES `appointment_slots` (`slot_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_appointments_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_appointments_cancelled_by` FOREIGN KEY (`cancelled_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Уведомления о записях
CREATE TABLE `appointment_notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `appointment_id` int(11) NOT NULL,
  `notification_type` enum('confirmation','reminder','cancellation','rescheduling') NOT NULL,
  `recipient_type` enum('patient','doctor','both') NOT NULL,
  `delivery_method` enum('email','sms','both') NOT NULL DEFAULT 'email',
  `notification_status` enum('pending','sent','failed','cancelled') NOT NULL DEFAULT 'pending',
  `scheduled_send_time` timestamp NULL DEFAULT NULL,
  `actual_send_time` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `notification_content` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`notification_id`),
  KEY `idx_appointment_id` (`appointment_id`),
  KEY `idx_status_send_time` (`notification_status`, `scheduled_send_time`),
  CONSTRAINT `fk_appointment_notifications_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================================
-- МЕДИЦИНСКИЕ ЗАПИСИ
-- ======================================================================

-- Основная таблица медицинских записей
CREATE TABLE `medical_records` (
  `record_id` int(11) NOT NULL AUTO_INCREMENT,
  `appointment_id` int(11) DEFAULT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `record_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `visit_type` enum('scheduled','emergency','follow_up','consultation') NOT NULL DEFAULT 'scheduled',
  `chief_complaint` text DEFAULT NULL,
  `medical_history` text DEFAULT NULL,
  `examination_findings` text DEFAULT NULL,
  `clinical_impression` text DEFAULT NULL,
  `treatment_plan` text DEFAULT NULL,
  `follow_up_instructions` text DEFAULT NULL,
  `record_status` enum('draft','completed','reviewed','archived') NOT NULL DEFAULT 'draft',
  `next_appointment_recommended` tinyint(1) DEFAULT 0,
  `next_appointment_timeframe` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`record_id`),
  KEY `idx_appointment_id` (`appointment_id`),
  KEY `idx_patient_id` (`patient_id`),
  KEY `idx_doctor_id` (`doctor_id`),
  KEY `idx_record_date` (`record_date`),
  KEY `idx_status` (`record_status`),
  CONSTRAINT `fk_medical_records_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_medical_records_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_medical_records_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Диагнозы
CREATE TABLE `record_diagnoses` (
  `diagnosis_id` int(11) NOT NULL AUTO_INCREMENT,
  `record_id` int(11) NOT NULL,
  `icd10_code` varchar(10) DEFAULT NULL,
  `diagnosis_description` text NOT NULL,
  `diagnosis_type` enum('primary','secondary','differential','rule_out') NOT NULL DEFAULT 'primary',
  `diagnosis_status` enum('suspected','confirmed','ruled_out') NOT NULL DEFAULT 'suspected',
  `diagnosis_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`diagnosis_id`),
  KEY `idx_record_id` (`record_id`),
  KEY `idx_icd10_code` (`icd10_code`),
  KEY `idx_diagnosis_type` (`diagnosis_type`),
  CONSTRAINT `fk_record_diagnoses_record` FOREIGN KEY (`record_id`) REFERENCES `medical_records` (`record_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_record_diagnoses_icd10` FOREIGN KEY (`icd10_code`) REFERENCES `icd10_diagnoses` (`icd10_code`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Жизненные показатели
CREATE TABLE `vital_signs` (
  `vital_signs_id` int(11) NOT NULL AUTO_INCREMENT,
  `record_id` int(11) NOT NULL,
  `measurement_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `body_temperature` decimal(4,1) DEFAULT NULL COMMENT 'Температура тела в °C',
  `heart_rate` int(11) DEFAULT NULL COMMENT 'Частота сердечных сокращений уд/мин',
  `blood_pressure_systolic` int(11) DEFAULT NULL COMMENT 'Систолическое давление мм рт.ст.',
  `blood_pressure_diastolic` int(11) DEFAULT NULL COMMENT 'Диастолическое давление мм рт.ст.',
  `respiratory_rate` int(11) DEFAULT NULL COMMENT 'Частота дыхания в минуту',
  `oxygen_saturation` int(11) DEFAULT NULL COMMENT 'Сатурация кислорода %',
  `height_cm` decimal(5,2) DEFAULT NULL COMMENT 'Рост в см',
  `weight_kg` decimal(5,2) DEFAULT NULL COMMENT 'Вес в кг',
  `bmi` decimal(4,2) DEFAULT NULL COMMENT 'Индекс массы тела',
  `pain_scale` int(11) DEFAULT NULL COMMENT 'Шкала боли 0-10',
  `glucose_level` decimal(5,2) DEFAULT NULL COMMENT 'Уровень глюкозы ммоль/л',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`vital_signs_id`),
  KEY `idx_record_id` (`record_id`),
  KEY `idx_measurement_time` (`measurement_time`),
  CONSTRAINT `fk_vital_signs_record` FOREIGN KEY (`record_id`) REFERENCES `medical_records` (`record_id`) ON DELETE CASCADE,
  CONSTRAINT `chk_pain_scale` CHECK (`pain_scale` IS NULL OR (`pain_scale` >= 0 AND `pain_scale` <= 10)),
  CONSTRAINT `chk_oxygen_saturation` CHECK (`oxygen_saturation` IS NULL OR (`oxygen_saturation` >= 0 AND `oxygen_saturation` <= 100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Назначения и рецепты
CREATE TABLE `prescriptions` (
  `prescription_id` int(11) NOT NULL AUTO_INCREMENT,
  `record_id` int(11) NOT NULL,
  `medication_name` varchar(255) NOT NULL,
  `active_ingredient` varchar(255) DEFAULT NULL,
  `dosage_form` varchar(100) DEFAULT NULL COMMENT 'таблетки, капсулы, раствор и т.д.',
  `strength` varchar(100) DEFAULT NULL COMMENT 'концентрация/сила действия',
  `dosage_instructions` text NOT NULL,
  `frequency` varchar(100) NOT NULL COMMENT 'частота приема',
  `duration_days` int(11) DEFAULT NULL,
  `quantity_prescribed` varchar(100) DEFAULT NULL,
  `refills_allowed` int(11) DEFAULT 0,
  `special_instructions` text DEFAULT NULL,
  `prescription_status` enum('active','completed','cancelled','expired') NOT NULL DEFAULT 'active',
  `prescribed_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `pharmacy_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`prescription_id`),
  KEY `idx_record_id` (`record_id`),
  KEY `idx_medication_name` (`medication_name`),
  KEY `idx_status` (`prescription_status`),
  KEY `idx_prescribed_date` (`prescribed_date`),
  CONSTRAINT `fk_prescriptions_record` FOREIGN KEY (`record_id`) REFERENCES `medical_records` (`record_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Лабораторные исследования
CREATE TABLE `laboratory_tests` (
  `test_id` int(11) NOT NULL AUTO_INCREMENT,
  `record_id` int(11) NOT NULL,
  `test_category` varchar(100) DEFAULT NULL COMMENT 'биохимия, клинический анализ и т.д.',
  `test_name` varchar(255) NOT NULL,
  `test_code` varchar(50) DEFAULT NULL,
  `ordered_date` date NOT NULL,
  `sample_collected_date` date DEFAULT NULL,
  `result_date` date DEFAULT NULL,
  `test_result` text DEFAULT NULL,
  `reference_range` varchar(255) DEFAULT NULL,
  `result_status` enum('ordered','collected','in_progress','completed','cancelled') NOT NULL DEFAULT 'ordered',
  `abnormal_flag` tinyint(1) DEFAULT 0,
  `critical_flag` tinyint(1) DEFAULT 0,
  `laboratory_name` varchar(255) DEFAULT NULL,
  `technician_notes` text DEFAULT NULL,
  `doctor_comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`test_id`),
  KEY `idx_record_id` (`record_id`),
  KEY `idx_test_name` (`test_name`),
  KEY `idx_ordered_date` (`ordered_date`),
  KEY `idx_result_status` (`result_status`),
  CONSTRAINT `fk_laboratory_tests_record` FOREIGN KEY (`record_id`) REFERENCES `medical_records` (`record_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Инструментальные исследования
CREATE TABLE `imaging_studies` (
  `study_id` int(11) NOT NULL AUTO_INCREMENT,
  `record_id` int(11) NOT NULL,
  `study_type` enum('x_ray','ultrasound','ct_scan','mri','mammography','fluoroscopy','other') NOT NULL,
  `study_name` varchar(255) NOT NULL,
  `body_part` varchar(100) DEFAULT NULL,
  `study_indication` text DEFAULT NULL,
  `ordered_date` date NOT NULL,
  `performed_date` date DEFAULT NULL,
  `study_status` enum('ordered','scheduled','in_progress','completed','cancelled') NOT NULL DEFAULT 'ordered',
  `findings` text DEFAULT NULL,
  `impression` text DEFAULT NULL,
  `radiologist_name` varchar(255) DEFAULT NULL,
  `imaging_facility` varchar(255) DEFAULT NULL,
  `contrast_used` tinyint(1) DEFAULT 0,
  `image_file_path` varchar(500) DEFAULT NULL,
  `dicom_study_id` varchar(100) DEFAULT NULL,
  `doctor_comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`study_id`),
  KEY `idx_record_id` (`record_id`),
  KEY `idx_study_type` (`study_type`),
  KEY `idx_ordered_date` (`ordered_date`),
  KEY `idx_study_status` (`study_status`),
  CONSTRAINT `fk_imaging_studies_record` FOREIGN KEY (`record_id`) REFERENCES `medical_records` (`record_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Планы лечения
CREATE TABLE `treatment_plans` (
  `plan_id` int(11) NOT NULL AUTO_INCREMENT,
  `record_id` int(11) NOT NULL,
  `plan_name` varchar(255) NOT NULL,
  `plan_description` text NOT NULL,
  `treatment_goals` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `expected_end_date` date DEFAULT NULL,
  `actual_end_date` date DEFAULT NULL,
  `plan_status` enum('draft','active','on_hold','completed','cancelled') NOT NULL DEFAULT 'draft',
  `progress_notes` text DEFAULT NULL,
  `outcome_assessment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`plan_id`),
  KEY `idx_record_id` (`record_id`),
  KEY `idx_plan_status` (`plan_status`),
  KEY `idx_start_date` (`start_date`),
  CONSTRAINT `fk_treatment_plans_record` FOREIGN KEY (`record_id`) REFERENCES `medical_records` (`record_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================================
-- СИСТЕМА АУДИТА И ЛОГИРОВАНИЯ
-- ======================================================================

-- Аудит доступа к медицинским записям
CREATE TABLE `medical_records_audit` (
  `audit_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `action_type` enum('view','create','update','delete','export','print') NOT NULL,
  `action_details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`audit_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_record_id` (`record_id`),
  KEY `idx_action_type` (`action_type`),
  KEY `idx_timestamp` (`timestamp`),
  CONSTRAINT `fk_medical_records_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_medical_records_audit_record` FOREIGN KEY (`record_id`) REFERENCES `medical_records` (`record_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Категории системных логов
CREATE TABLE `log_categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL UNIQUE,
  `category_description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`category_id`),
  KEY `idx_category_name` (`category_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Системные логи
CREATE TABLE `system_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `log_level` enum('DEBUG','INFO','WARNING','ERROR','CRITICAL') NOT NULL,
  `category_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `additional_data` json DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `idx_log_level` (`log_level`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_timestamp` (`timestamp`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `fk_system_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_system_logs_category` FOREIGN KEY (`category_id`) REFERENCES `log_categories` (`category_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================================
-- ЗАПОЛНЕНИЕ СПРАВОЧНЫХ ДАННЫХ
-- ======================================================================

-- Роли пользователей
INSERT INTO `user_roles` (`role_name`, `role_description`) VALUES
('administrator', 'Системный администратор с полными правами доступа'),
('doctor', 'Врач с доступом к медицинским записям и расписанию'),
('patient', 'Пациент с доступом к собственным медицинским данным');

-- Медицинские специальности
INSERT INTO `medical_specialties` (`specialty_name`, `specialty_code`, `description`) VALUES
('Аллергология и иммунология', 'ALLERGY', 'Диагностика и лечение аллергических заболеваний'),
('Кардиология', 'CARDIO', 'Диагностика и лечение заболеваний сердечно-сосудистой системы'),
('Дерматология', 'DERMA', 'Диагностика и лечение заболеваний кожи'),
('Эндокринология', 'ENDO', 'Диагностика и лечение заболеваний эндокринной системы'),
('Гастроэнтерология', 'GASTRO', 'Диагностика и лечение заболеваний пищеварительной системы'),
('Общая терапия', 'THERAPY', 'Общая врачебная практика и терапия'),
('Неврология', 'NEURO', 'Диагностика и лечение заболеваний нервной системы'),
('Акушерство и гинекология', 'OBGYN', 'Женское здоровье, беременность и роды'),
('Офтальмология', 'OPHTH', 'Диагностика и лечение заболеваний глаз'),
('Ортопедия', 'ORTHO', 'Диагностика и лечение заболеваний опорно-двигательного аппарата'),
('Педиатрия', 'PEDIATR', 'Детская медицина'),
('Пластическая хирургия', 'PLASTIC', 'Пластическая и реконструктивная хирургия'),
('Психиатрия', 'PSYCHI', 'Диагностика и лечение психических расстройств'),
('Пульмонология', 'PULMO', 'Диагностика и лечение заболеваний дыхательной системы'),
('Урология', 'URO', 'Диагностика и лечение заболеваний мочеполовой системы');

-- Категории системных логов
INSERT INTO `log_categories` (`category_name`, `category_description`) VALUES
('authentication', 'Логи аутентификации и авторизации пользователей'),
('appointment_booking', 'Логи записи на прием и управления расписанием'),
('medical_records', 'Логи работы с медицинскими записями'),
('user_management', 'Логи управления пользователями и ролями'),
('system_maintenance', 'Логи системного обслуживания и обновлений'),
('data_export', 'Логи экспорта данных и генерации отчетов'),
('security', 'Логи безопасности и подозрительной активности'),
('database', 'Логи работы с базой данных'),
('api_access', 'Логи доступа к API'),
('notification', 'Логи отправки уведомлений'),
('audit', 'Логи аудита действий пользователей'),
('error_handling', 'Логи обработки ошибок'),
('performance', 'Логи производительности системы'),
('backup', 'Логи резервного копирования'),
('integration', 'Логи интеграции с внешними системами');

-- Категории МКБ-10
INSERT INTO `icd10_categories` (`category_code`, `category_name`, `category_description`) VALUES
('A00-B99', 'Инфекционные и паразитарные болезни', 'Заболевания, вызванные инфекционными агентами'),
('C00-D48', 'Новообразования', 'Доброкачественные и злокачественные новообразования'),
('D50-D89', 'Болезни крови и кроветворных органов', 'Анемии, нарушения свертывания крови и иммунитета'),
('E00-E90', 'Болезни эндокринной системы', 'Заболевания эндокринных желез и обмена веществ'),
('F00-F99', 'Психические расстройства и расстройства поведения', 'Психические и поведенческие нарушения'),
('G00-G99', 'Болезни нервной системы', 'Заболевания центральной и периферической нервной системы'),
('H00-H59', 'Болезни глаза и его придаточного аппарата', 'Офтальмологические заболевания'),
('H60-H95', 'Болезни уха и сосцевидного отростка', 'ЛОР-заболевания органов слуха'),
('I00-I99', 'Болезни системы кровообращения', 'Заболевания сердца и сосудов'),
('J00-J99', 'Болезни органов дыхания', 'Заболевания дыхательной системы'),
('K00-K93', 'Болезни органов пищеварения', 'Заболевания пищеварительной системы'),
('L00-L99', 'Болезни кожи и подкожной клетчатки', 'Дерматологические заболевания'),
('M00-M99', 'Болезни костно-мышечной системы', 'Заболевания опорно-двигательного аппарата'),
('N00-N99', 'Болезни мочеполовой системы', 'Урологические и гинекологические заболевания'),
('O00-O99', 'Беременность, роды и послеродовой период', 'Акушерско-гинекологические состояния'),
('P00-P96', 'Состояния, возникающие в перинатальном периоде', 'Заболевания новорожденных'),
('Q00-Q99', 'Врожденные аномалии', 'Пороки развития и хромосомные нарушения'),
('R00-R99', 'Симптомы, признаки и отклонения от нормы', 'Неспецифические симптомы и синдромы'),
('S00-T98', 'Травмы, отравления и воздействия внешних причин', 'Механические травмы и токсические воздействия'),
('V01-Y98', 'Внешние причины заболеваемости и смертности', 'Коды причин травм и отравлений'),
('Z00-Z99', 'Факторы, влияющие на состояние здоровья', 'Профилактические осмотры и факторы риска');

-- Справочник аллергенов
INSERT INTO `allergens` (`allergen_name`, `allergen_category`, `common_reactions`, `description`) VALUES
-- Пищевые аллергены
('Арахис', 'food', 'Крапивница, отек, анафилаксия', 'Один из наиболее частых пищевых аллергенов'),
('Молоко', 'food', 'Диарея, рвота, кожные высыпания', 'Аллергия на белки коровьего молока'),
('Яйца', 'food', 'Кожные высыпания, расстройство ЖКТ', 'Аллергия на белки куриных яиц'),
('Пшеница', 'food', 'Расстройство ЖКТ, кожные реакции', 'Аллергия на глютен и другие белки пшеницы'),
('Соя', 'food', 'Крапивница, отек, расстройство ЖКТ', 'Аллергия на соевые белки'),
('Рыба', 'food', 'Крапивница, отек, анафилаксия', 'Аллергия на белки рыбы'),
('Моллюски', 'food', 'Крапивница, отек, анафилаксия', 'Аллергия на морепродукты'),
('Орехи', 'food', 'Крапивница, отек, анафилаксия', 'Аллергия на древесные орехи'),
('Клубника', 'food', 'Крапивница, зуд', 'Псевдоаллергическая реакция'),
('Цитрусовые', 'food', 'Кожные высыпания, зуд', 'Реакция на цитрусовые фрукты'),

-- Лекарственные аллергены
('Пенициллин', 'medication', 'Крапивница, отек, анафилаксия', 'Антибиотик группы пенициллинов'),
('Аспирин', 'medication', 'Крапивница, бронхоспазм', 'Нестероидный противовоспалительный препарат'),
('Ибупрофен', 'medication', 'Кожные реакции, бронхоспазм', 'НПВС препарат'),
('Сульфаниламиды', 'medication', 'Синдром Стивенса-Джонсона, крапивница', 'Группа антибактериальных препаратов'),
('Йод', 'medication', 'Контактный дерматит, системные реакции', 'Контрастное вещество'),
('Лидокаин', 'medication', 'Контактный дерматит, системные реакции', 'Местный анестетик'),
('Новокаин', 'medication', 'Контактный дерматит, анафилаксия', 'Местный анестетик'),

-- Экологические аллергены
('Пыльца березы', 'environmental', 'Ринит, конъюнктивит, астма', 'Весенний аллерген'),
('Пыльца полыни', 'environmental', 'Ринит, конъюнктивит', 'Летне-осенний аллерген'),
('Домашняя пыль', 'environmental', 'Ринит, астма, экзема', 'Клещи домашней пыли'),
('Плесень', 'environmental', 'Ринит, астма', 'Споры плесневых грибов'),
('Шерсть животных', 'environmental', 'Ринит, астма, крапивница', 'Белки слюны и кожи животных'),
('Перья птиц', 'environmental', 'Ринит, астма', 'Белки перьев и пуха'),

-- Контактные аллергены
('Латекс', 'contact', 'Контактный дерматит, крапивница', 'Натуральный каучук'),
('Никель', 'contact', 'Контактный дерматит', 'Металл в украшениях и предметах быта'),
('Формальдегид', 'contact', 'Контактный дерматит', 'Консервант в косметике'),
('Парафенилендиамин', 'contact', 'Контактный дерматит', 'Компонент красок для волос'),

-- Прочие аллергены
('Укусы пчел', 'other', 'Местная реакция, анафилаксия', 'Яд перепончатокрылых насекомых'),
('Укусы ос', 'other', 'Местная реакция, анафилаксия', 'Яд перепончатокрылых насекомых');

-- Основные коды МКБ-10
INSERT INTO `icd10_diagnoses` (`icd10_code`, `diagnosis_name`, `category_id`) VALUES
('A00', 'Холера', 1),
('A09', 'Диарея и гастроэнтерит предположительно инфекционного происхождения', 1),
('E10', 'Инсулинзависимый сахарный диабет', 4),
('E11', 'Инсулиннезависимый сахарный диабет', 4),
('I10', 'Эссенциальная (первичная) гипертензия', 9),
('I20', 'Стенокардия', 9),
('J00', 'Острый назофарингит (насморк)', 10),
('J06', 'Острые инфекции верхних дыхательных путей множественной и неуточненной локализации', 10),
('K29', 'Гастрит и дуоденит', 11),
('K59', 'Другие функциональные кишечные нарушения', 11),
('M54', 'Дорсалгия', 13),
('M79', 'Другие болезни мягких тканей', 13),
('R05', 'Кашель', 18),
('R50', 'Лихорадка неуточненная', 18),
('Z00', 'Общее обследование и осмотр лиц без жалоб или установленного диагноза', 21);

-- Создание индексов для оптимизации производительности
CREATE INDEX `idx_appointments_date_status` ON `appointments` (`appointment_status`, `updated_at`);
CREATE INDEX `idx_medical_records_patient_date` ON `medical_records` (`patient_id`, `record_date`);
CREATE INDEX `idx_appointment_slots_doctor_date` ON `appointment_slots` (`doctor_id`, `appointment_date`, `slot_status`);
CREATE INDEX `idx_prescriptions_patient_date` ON `prescriptions` (`record_id`, `prescribed_date`);
CREATE INDEX `idx_laboratory_tests_patient_date` ON `laboratory_tests` (`record_id`, `ordered_date`);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
