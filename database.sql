-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Время создания: Май 27 2025 г., 15:44
-- Версия сервера: 10.4.32-MariaDB
-- Версия PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `bebebe`
--

-- --------------------------------------------------------

--
-- Структура таблицы `admin`
--

CREATE TABLE `admin` (
  `aemail` varchar(255) NOT NULL,
  `apassword` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `admin`
--

INSERT INTO `admin` (`aemail`, `apassword`) VALUES
('admin@edoc.com', '$2y$10$hQYSoOwiAXC5AE4LEZ6o7eZ9u2a237aBhilDLHWjwot3b6wv7Q0jy');

-- --------------------------------------------------------

--
-- Структура таблицы `allergies`
--

CREATE TABLE `allergies` (
  `allergy_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `allergy_name` varchar(255) NOT NULL,
  `reaction` text DEFAULT NULL,
  `severity` enum('mild','moderate','severe','life-threatening') DEFAULT NULL,
  `date_identified` date DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `appointment`
--

CREATE TABLE `appointment` (
  `appoid` int(11) NOT NULL,
  `pid` int(10) DEFAULT NULL,
  `apponum` int(3) DEFAULT NULL,
  `scheduleid` int(10) DEFAULT NULL,
  `appodate` date DEFAULT NULL,
  `status` enum('scheduled','confirmed','completed','canceled','no_show') NOT NULL DEFAULT 'scheduled',
  `duration` int(11) NOT NULL DEFAULT 30 COMMENT 'Длительность приема в минутах',
  `reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `appointment`
--

INSERT INTO `appointment` (`appoid`, `pid`, `apponum`, `scheduleid`, `appodate`, `status`, `duration`, `reason`, `notes`, `created_at`, `updated_at`) VALUES
(36, 22, 1, 285, '2025-05-20', 'scheduled', 30, NULL, NULL, '2025-05-21 15:45:47', '2025-05-21 15:45:47'),
(37, 1, 1, 288, '2025-05-28', 'scheduled', 30, NULL, NULL, '2025-05-27 12:22:31', '2025-05-27 12:22:31'),
(38, 1, 1, 289, '2025-05-28', 'scheduled', 30, NULL, NULL, '2025-05-27 12:23:40', '2025-05-27 12:23:40');

-- --------------------------------------------------------

--
-- Структура таблицы `appointment_notifications`
--

CREATE TABLE `appointment_notifications` (
  `notification_id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `notification_type` enum('email','sms','system') NOT NULL,
  `notification_method` enum('email','sms','both') NOT NULL DEFAULT 'email',
  `recipient_type` enum('patient','doctor') NOT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_sent` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `appointment_templates`
--

CREATE TABLE `appointment_templates` (
  `template_id` int(11) NOT NULL,
  `docid` int(11) NOT NULL,
  `template_name` varchar(255) NOT NULL,
  `duration` int(11) NOT NULL DEFAULT 30,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `diagnoses`
--

CREATE TABLE `diagnoses` (
  `diagnosis_id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `icd10_code` varchar(10) DEFAULT NULL,
  `diagnosis_name` text NOT NULL,
  `diagnosis_type` enum('primary','secondary','complication') NOT NULL DEFAULT 'primary',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `doctor`
--

CREATE TABLE `doctor` (
  `docid` int(11) NOT NULL,
  `docemail` varchar(255) DEFAULT NULL,
  `docname` varchar(255) DEFAULT NULL,
  `docpassword` varchar(255) DEFAULT NULL,
  `doctel` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `doctor`
--

INSERT INTO `doctor` (`docid`, `docemail`, `docname`, `docpassword`, `doctel`) VALUES
(1, 'doctor@edoc.com', 'Тестовый доктор', '$2y$10$xhhTD8rtugPdUJd609CH0eVxgN5Fc3.ZqkyucLgj7cNwpbluYf3uW', '0110000000'),
(2, 'allergist@edoc.com', 'Иван Иванов', '$2y$10$NHPgxErjeGc/5aRVlhEKhOK4ZT9G5R8cLZhluMWEGjX48aNctYJrm', '0110000001'),
(3, 'cardiologist@edoc.com', 'Мария Смирнова', '$2y$10$bVcfNijyMCgKK6wHkg/HH.N04Wy4z7z1e2P5j27ZiUgF7lthRjXU2', '0110000002'),
(4, 'dermatologist@edoc.com', 'Алексей Петров', '$2y$10$Xn70ztLHS4QwMvi8X.BFOOc3hbPvwAG//hhNe4rrrqPfn1ak3nNtK', '0110000003'),
(5, 'endocrinologist@edoc.com', 'Татьяна Лебедева', '$2y$10$702OdTNdGjz9Mc6f1uhFhePQ1mjYmZgdUbf1stgDcaDeoVGXMCRt2', '0110000004'),
(6, 'gastroenterologist@edoc.com', 'Дмитрий Кузнецов', '$2y$10$1/mEvNkqv.GBf2e3lj0PS.wvm3QCPL9JJezG3cFLMuuLrDF83uO1K', '0110000005'),
(7, 'therapist@edoc.com', 'Екатерина Васильева', '$2y$10$3abMHOiLQr/YZwP1bFHIQeaDDg.SAOS7nlmTKc97DyH8s.NwQ17zG', '0110000006'),
(8, 'neurologist@edoc.com', 'Игорь Васильев', '$2y$10$k.G3Cx2bP7bvnkZgIjPJJu6d9T2e7o0zOPUzyuPLbd3dnNAzTUrI6', '0110000007'),
(9, 'gynecologist@edoc.com', 'Елена Чернова', '$2y$10$owttFMUmqWX5hU2feCvGN./hzyHsI.wpfQY2wiaHy4phdJD5ddI8y', '0110000008'),
(10, 'ophthalmologist@edoc.com', 'Владимир Федоров', '$2y$10$DOuWkp4CEVKQElGUPqX9pO1ny9j0xxyNaDBtm46J2MlAicMYTnGaK', '0110000009'),
(11, 'orthopedist@edoc.com', 'Анатолий Орлов', '$2y$10$JxZ7PWfy03tCz83Mt/wy.eZNnEHTOqpfBmvcKYia9.cz8JwGkDI0C', '0110000010'),
(12, 'pediatrician@edoc.com', 'Наталья Морозова', '$2y$10$rxZTedqHVUyZHD1W5BYWcegnOvfdJttX8AUcwoxf70jF51KLXcWY6', '0110000011'),
(13, 'plastic_surgeon@edoc.com', 'Сергей Лазарев', '$2y$10$HsCgAdRLoRRWc.nJZ/B/pOhsuGT6DlL6h9Wm0bhzOYOCmXmLlbGMC', '0110000012'),
(14, 'psychiatrist@edoc.com', 'Юлия Сергеева', '$2y$10$uUrimRhivvRP.hOV4blAVeYLJ6JNEWzNCH2hwerLD16Edl5cxzwde', '0110000013'),
(15, 'pulmonologist@edoc.com', 'Петр Ильин', '$2y$10$Fm2AwLPZ3f0VyfxaTPp/auj1/BbHc5CLdN.IV1JH96aXQTTFCa3wy', '0110000014'),
(16, 'urologist@edoc.com', 'Николай Павлов', '', '0110000015');

-- --------------------------------------------------------

--
-- Структура таблицы `doctor_schedules`
--

CREATE TABLE `doctor_schedules` (
  `schedule_id` int(11) NOT NULL,
  `docid` int(11) NOT NULL,
  `day_of_week` tinyint(1) NOT NULL COMMENT '1-Пн, 2-Вт, 3-Ср, 4-Чт, 5-Пт, 6-Сб, 7-Вс',
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `break_start` time DEFAULT NULL,
  `break_end` time DEFAULT NULL,
  `appointment_duration` int(11) NOT NULL DEFAULT 30 COMMENT 'Длительность приема в минутах',
  `max_patients` int(11) NOT NULL DEFAULT 0 COMMENT 'Максимальное количество пациентов (0 - без ограничений)',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `doctor_specialty`
--

CREATE TABLE `doctor_specialty` (
  `docid` int(11) NOT NULL,
  `specialty_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `doctor_specialty`
--

INSERT INTO `doctor_specialty` (`docid`, `specialty_id`) VALUES
(1, 4),
(1, 8),
(2, 1),
(3, 2),
(4, 3),
(5, 4),
(6, 5),
(7, 6),
(8, 7),
(9, 8),
(10, 9),
(11, 10),
(12, 11),
(13, 12),
(14, 13),
(15, 14),
(16, 1),
(16, 2);

-- --------------------------------------------------------

--
-- Структура таблицы `icd10_codes`
--

CREATE TABLE `icd10_codes` (
  `code` varchar(10) NOT NULL,
  `description` text NOT NULL,
  `category` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `icd10_codes`
--

INSERT INTO `icd10_codes` (`code`, `description`, `category`) VALUES
('A00', 'Холера', 'Инфекционные болезни'),
('E11', 'Инсулиннезависимый сахарный диабет', 'Эндокринные заболевания'),
('I10', 'Эссенциальная (первичная) гипертензия', 'Болезни системы кровообращения'),
('J00', 'Острый назофарингит [насморк]', 'Болезни органов дыхания'),
('K29.7', 'Гастрит неуточненный', 'Болезни органов пищеварения'),
('M54.5', 'Боль внизу спины', 'Болезни костно-мышечной системы');

-- --------------------------------------------------------

--
-- Структура таблицы `imaging_studies`
--

CREATE TABLE `imaging_studies` (
  `study_id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `study_type` enum('x-ray','ultrasound','mri','ct','other') NOT NULL,
  `study_date` date NOT NULL,
  `study_result` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `lab_tests`
--

CREATE TABLE `lab_tests` (
  `test_id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `test_name` varchar(255) NOT NULL,
  `test_date` date NOT NULL,
  `test_result` text DEFAULT NULL,
  `reference_range` varchar(255) DEFAULT NULL,
  `is_abnormal` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `medical_records`
--

CREATE TABLE `medical_records` (
  `record_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `record_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `chief_complaint` text DEFAULT NULL,
  `anamnesis` text DEFAULT NULL,
  `examination_results` text DEFAULT NULL,
  `is_finalized` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `medical_records_audit`
--

CREATE TABLE `medical_records_audit` (
  `audit_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('admin','doctor','patient') NOT NULL,
  `record_id` int(11) NOT NULL,
  `action` enum('view','create','update','export','delete') NOT NULL,
  `action_details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `patient`
--

CREATE TABLE `patient` (
  `pid` int(11) NOT NULL,
  `pemail` varchar(255) DEFAULT NULL,
  `pname` varchar(255) DEFAULT NULL,
  `ppassword` varchar(255) DEFAULT NULL,
  `pdob` date DEFAULT NULL,
  `ptel` varchar(15) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `notification_preference` enum('email','sms','both','none') DEFAULT 'email'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `patient`
--

INSERT INTO `patient` (`pid`, `pemail`, `pname`, `ppassword`, `pdob`, `ptel`, `is_active`, `notification_preference`) VALUES
(1, 'patient@edoc.com', 'Тестовый пациент', '$2y$10$KYARMpcEoqG7xOZ5y8z3c.EkS6AHJxg99s9EVpzf1Bf4b8d8ebk6e', '2000-01-01', '+79533867129', 1, 'email'),
(2, 'emhashenudara@gmail.com', 'Hashen Udara', '$2y$10$MJIz3i/8ovmTPXJYpSvDg.lhsQiJlKwuHmBS2XiRHmfSxQsEmXl.a', '2022-06-03', '+71234567890', 1, 'email'),
(21, 'admin124321@edoc.com', 'AOSDPAPSDOK', '$2y$10$7tC2cYzyc4E0Wnrk2Axk9.0fRDlPJD/sqH1LdYs6xzfvE76Q07mSe', '2005-01-12', '+71231234567', 1, 'email'),
(22, 'Ivan_Ivanov@mail.ru', 'Иван Иванов', '$2y$10$KjdWktVYk5PMh9lhs9asFOzT4GAow..sbsdDawWhADwR3dF5sWLJK', '2005-01-01', '+79532518171', 1, 'email');

-- --------------------------------------------------------

--
-- Структура таблицы `prescriptions`
--

CREATE TABLE `prescriptions` (
  `prescription_id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `medication_name` varchar(255) NOT NULL,
  `dosage` varchar(100) DEFAULT NULL,
  `frequency` varchar(100) DEFAULT NULL,
  `duration` varchar(100) DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `schedule`
--

CREATE TABLE `schedule` (
  `scheduleid` int(11) NOT NULL,
  `docid` varchar(255) DEFAULT NULL,
  `specialty_id` int(11) DEFAULT NULL,
  `scheduledate` date DEFAULT NULL,
  `scheduletime` time DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `schedule`
--

INSERT INTO `schedule` (`scheduleid`, `docid`, `specialty_id`, `scheduledate`, `scheduletime`, `status`) VALUES
(202, '1', NULL, '2025-03-13', '09:00:00', 0),
(203, '1', NULL, '2025-03-14', '09:30:00', 0),
(204, '1', NULL, '2025-04-13', '10:00:00', 0),
(206, '2', NULL, '2025-03-13', '09:30:00', 0),
(207, '2', NULL, '2025-03-14', '10:00:00', 0),
(208, '2', NULL, '2025-04-13', '10:30:00', 0),
(210, '3', NULL, '2025-03-13', '10:00:00', 0),
(211, '3', NULL, '2025-03-14', '10:30:00', 0),
(212, '3', NULL, '2025-04-13', '11:00:00', 0),
(214, '4', NULL, '2025-03-13', '10:30:00', 0),
(215, '4', NULL, '2025-03-14', '11:00:00', 0),
(216, '4', NULL, '2025-04-13', '11:30:00', 0),
(218, '5', NULL, '2025-03-13', '11:00:00', 0),
(219, '5', NULL, '2025-03-14', '11:30:00', 0),
(220, '5', NULL, '2025-04-13', '12:00:00', 0),
(222, '6', NULL, '2025-03-13', '11:30:00', 0),
(223, '6', NULL, '2025-03-14', '12:00:00', 0),
(224, '6', NULL, '2025-04-13', '12:30:00', 0),
(226, '7', NULL, '2025-03-13', '12:00:00', 0),
(227, '7', NULL, '2025-03-14', '12:30:00', 0),
(228, '7', NULL, '2025-04-13', '13:00:00', 0),
(230, '8', NULL, '2025-03-13', '12:30:00', 0),
(231, '8', NULL, '2025-03-14', '13:00:00', 0),
(232, '8', NULL, '2025-04-13', '13:30:00', 0),
(234, '9', NULL, '2025-03-13', '13:00:00', 0),
(235, '9', NULL, '2025-03-14', '13:30:00', 0),
(236, '9', NULL, '2025-04-13', '14:00:00', 0),
(238, '10', NULL, '2025-03-13', '13:30:00', 0),
(239, '10', NULL, '2025-03-14', '14:00:00', 0),
(240, '10', NULL, '2025-04-13', '14:30:00', 0),
(241, '10', NULL, '2025-04-14', '15:00:00', 0),
(242, '11', NULL, '2025-03-13', '14:00:00', 0),
(243, '11', NULL, '2025-03-14', '14:30:00', 0),
(244, '11', NULL, '2025-04-13', '15:00:00', 0),
(245, '11', NULL, '2025-04-14', '15:30:00', 0),
(246, '12', NULL, '2025-03-13', '14:30:00', 0),
(247, '12', NULL, '2025-03-14', '15:00:00', 0),
(248, '12', NULL, '2025-04-13', '15:30:00', 0),
(249, '12', NULL, '2025-04-14', '16:00:00', 0),
(250, '13', NULL, '2025-03-13', '15:00:00', 0),
(251, '13', NULL, '2025-03-14', '15:30:00', 0),
(252, '13', NULL, '2025-04-13', '16:00:00', 0),
(253, '13', NULL, '2025-04-14', '16:30:00', 0),
(254, '14', NULL, '2025-03-13', '15:30:00', 0),
(255, '14', NULL, '2025-03-14', '16:00:00', 0),
(256, '14', NULL, '2025-04-13', '16:30:00', 0),
(258, '15', NULL, '2025-03-13', '16:00:00', 0),
(259, '15', NULL, '2025-03-14', '16:30:00', 0),
(260, '15', NULL, '2025-04-13', '17:00:00', 0),
(263, '16', NULL, '2025-03-14', '17:00:00', 0),
(264, '16', NULL, '2025-04-13', '17:30:00', 0),
(285, '14', NULL, '2025-05-20', '00:00:09', 0),
(286, '14', NULL, '2025-05-20', '00:00:09', 0),
(287, '14', NULL, '2025-05-20', '00:00:09', 0),
(288, '1', NULL, '2025-05-28', '10:00:00', 1),
(289, '1', NULL, '2025-05-28', '10:00:00', 1);

-- --------------------------------------------------------

--
-- Структура таблицы `schedule_exceptions`
--

CREATE TABLE `schedule_exceptions` (
  `exception_id` int(11) NOT NULL,
  `docid` int(11) NOT NULL,
  `exception_date` date NOT NULL,
  `exception_type` enum('vacation','sick_leave','personal','custom') NOT NULL,
  `start_time` time DEFAULT NULL COMMENT 'Если NULL, то весь день',
  `end_time` time DEFAULT NULL COMMENT 'Если NULL, то весь день',
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `specialties`
--

CREATE TABLE `specialties` (
  `id` int(2) NOT NULL,
  `sname` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `specialties`
--

INSERT INTO `specialties` (`id`, `sname`) VALUES
(1, 'Аллергология'),
(2, 'Кардиология'),
(3, 'Дерматология'),
(4, 'Эндокринология'),
(5, 'Гастроэнтерология'),
(6, 'Терапевт'),
(7, 'Неврология'),
(8, 'Акушерство и гинекология'),
(9, 'Офтальмология'),
(10, 'Ортопедия'),
(11, 'Педиатрия'),
(12, 'Пластическая хирургия'),
(13, 'Психиатрия'),
(14, 'Пульмонология'),
(15, 'Урология');

-- --------------------------------------------------------

--
-- Структура таблицы `treatment_plans`
--

CREATE TABLE `treatment_plans` (
  `plan_id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `plan_description` text NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('planned','in_progress','completed','cancelled') NOT NULL DEFAULT 'planned',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `vitals`
--

CREATE TABLE `vitals` (
  `vital_id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `temperature` decimal(4,1) DEFAULT NULL,
  `heart_rate` int(11) DEFAULT NULL,
  `blood_pressure_systolic` int(11) DEFAULT NULL,
  `blood_pressure_diastolic` int(11) DEFAULT NULL,
  `respiratory_rate` int(11) DEFAULT NULL,
  `oxygen_saturation` int(11) DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `bmi` decimal(4,2) DEFAULT NULL,
  `pain_level` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `measured_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `webuser`
--

CREATE TABLE `webuser` (
  `email` varchar(255) NOT NULL,
  `usertype` char(1) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `webuser`
--

INSERT INTO `webuser` (`email`, `usertype`, `is_active`) VALUES
('1@e.com', 'p', 0),
('admin123@edoc.com', 'p', 1),
('admin124321@edoc.com', 'p', 1),
('admin@edoc.com', 'a', 1),
('doctor@edoc.com', 'd', 1),
('emhashenudara@gmail.com', 'p', 1),
('Ivan_Ivanov@mail.ru', 'p', 1),
('patient@edoc.com', 'p', 1),
('qwe@edoc.com', 'p', 1),
('qwewe@edoc.com', 'p', 1),
('qweweqwe@edoc.com', 'p', 1),
('qweweqweeqwewe@edoc.com', 'p', 1),
('qweweqweewe@edoc.com', 'p', 1);

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`aemail`);

--
-- Индексы таблицы `allergies`
--
ALTER TABLE `allergies`
  ADD PRIMARY KEY (`allergy_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Индексы таблицы `appointment`
--
ALTER TABLE `appointment`
  ADD PRIMARY KEY (`appoid`),
  ADD KEY `pid` (`pid`),
  ADD KEY `scheduleid` (`scheduleid`),
  ADD KEY `appodate_index` (`appodate`),
  ADD KEY `appoid_status_index` (`appoid`,`status`);

--
-- Индексы таблицы `appointment_notifications`
--
ALTER TABLE `appointment_notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `appoid` (`appointment_id`);

--
-- Индексы таблицы `appointment_templates`
--
ALTER TABLE `appointment_templates`
  ADD PRIMARY KEY (`template_id`),
  ADD KEY `docid` (`docid`);

--
-- Индексы таблицы `diagnoses`
--
ALTER TABLE `diagnoses`
  ADD PRIMARY KEY (`diagnosis_id`),
  ADD KEY `record_id` (`record_id`),
  ADD KEY `icd10_code` (`icd10_code`);

--
-- Индексы таблицы `doctor`
--
ALTER TABLE `doctor`
  ADD PRIMARY KEY (`docid`);

--
-- Индексы таблицы `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `docid` (`docid`);

--
-- Индексы таблицы `doctor_specialty`
--
ALTER TABLE `doctor_specialty`
  ADD PRIMARY KEY (`docid`,`specialty_id`),
  ADD KEY `specialty_id` (`specialty_id`);

--
-- Индексы таблицы `icd10_codes`
--
ALTER TABLE `icd10_codes`
  ADD PRIMARY KEY (`code`);

--
-- Индексы таблицы `imaging_studies`
--
ALTER TABLE `imaging_studies`
  ADD PRIMARY KEY (`study_id`),
  ADD KEY `record_id` (`record_id`);

--
-- Индексы таблицы `lab_tests`
--
ALTER TABLE `lab_tests`
  ADD PRIMARY KEY (`test_id`),
  ADD KEY `record_id` (`record_id`);

--
-- Индексы таблицы `medical_records`
--
ALTER TABLE `medical_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Индексы таблицы `medical_records_audit`
--
ALTER TABLE `medical_records_audit`
  ADD PRIMARY KEY (`audit_id`),
  ADD KEY `record_id` (`record_id`);

--
-- Индексы таблицы `patient`
--
ALTER TABLE `patient`
  ADD PRIMARY KEY (`pid`);

--
-- Индексы таблицы `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`prescription_id`),
  ADD KEY `record_id` (`record_id`);

--
-- Индексы таблицы `schedule`
--
ALTER TABLE `schedule`
  ADD PRIMARY KEY (`scheduleid`),
  ADD KEY `docid` (`docid`),
  ADD KEY `specialty_id` (`specialty_id`);

--
-- Индексы таблицы `schedule_exceptions`
--
ALTER TABLE `schedule_exceptions`
  ADD PRIMARY KEY (`exception_id`),
  ADD KEY `docid` (`docid`),
  ADD KEY `exception_date` (`exception_date`);

--
-- Индексы таблицы `specialties`
--
ALTER TABLE `specialties`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `treatment_plans`
--
ALTER TABLE `treatment_plans`
  ADD PRIMARY KEY (`plan_id`),
  ADD KEY `record_id` (`record_id`);

--
-- Индексы таблицы `vitals`
--
ALTER TABLE `vitals`
  ADD PRIMARY KEY (`vital_id`),
  ADD KEY `record_id` (`record_id`);

--
-- Индексы таблицы `webuser`
--
ALTER TABLE `webuser`
  ADD PRIMARY KEY (`email`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `allergies`
--
ALTER TABLE `allergies`
  MODIFY `allergy_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `appointment`
--
ALTER TABLE `appointment`
  MODIFY `appoid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT для таблицы `appointment_notifications`
--
ALTER TABLE `appointment_notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `appointment_templates`
--
ALTER TABLE `appointment_templates`
  MODIFY `template_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `diagnoses`
--
ALTER TABLE `diagnoses`
  MODIFY `diagnosis_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `doctor`
--
ALTER TABLE `doctor`
  MODIFY `docid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT для таблицы `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `imaging_studies`
--
ALTER TABLE `imaging_studies`
  MODIFY `study_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `lab_tests`
--
ALTER TABLE `lab_tests`
  MODIFY `test_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `medical_records`
--
ALTER TABLE `medical_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `medical_records_audit`
--
ALTER TABLE `medical_records_audit`
  MODIFY `audit_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `patient`
--
ALTER TABLE `patient`
  MODIFY `pid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT для таблицы `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `prescription_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `schedule`
--
ALTER TABLE `schedule`
  MODIFY `scheduleid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=290;

--
-- AUTO_INCREMENT для таблицы `schedule_exceptions`
--
ALTER TABLE `schedule_exceptions`
  MODIFY `exception_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `specialties`
--
ALTER TABLE `specialties`
  MODIFY `id` int(2) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT для таблицы `treatment_plans`
--
ALTER TABLE `treatment_plans`
  MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `vitals`
--
ALTER TABLE `vitals`
  MODIFY `vital_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `allergies`
--
ALTER TABLE `allergies`
  ADD CONSTRAINT `allergies_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patient` (`pid`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `appointment_notifications`
--
ALTER TABLE `appointment_notifications`
  ADD CONSTRAINT `appointment_notifications_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointment` (`appoid`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `appointment_templates`
--
ALTER TABLE `appointment_templates`
  ADD CONSTRAINT `appointment_templates_ibfk_1` FOREIGN KEY (`docid`) REFERENCES `doctor` (`docid`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `diagnoses`
--
ALTER TABLE `diagnoses`
  ADD CONSTRAINT `diagnoses_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `medical_records` (`record_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `diagnoses_ibfk_2` FOREIGN KEY (`icd10_code`) REFERENCES `icd10_codes` (`code`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  ADD CONSTRAINT `doctor_schedules_ibfk_1` FOREIGN KEY (`docid`) REFERENCES `doctor` (`docid`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `doctor_specialty`
--
ALTER TABLE `doctor_specialty`
  ADD CONSTRAINT `doctor_specialty_ibfk_1` FOREIGN KEY (`docid`) REFERENCES `doctor` (`docid`) ON DELETE CASCADE,
  ADD CONSTRAINT `doctor_specialty_ibfk_2` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `imaging_studies`
--
ALTER TABLE `imaging_studies`
  ADD CONSTRAINT `imaging_studies_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `medical_records` (`record_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `lab_tests`
--
ALTER TABLE `lab_tests`
  ADD CONSTRAINT `lab_tests_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `medical_records` (`record_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `medical_records`
--
ALTER TABLE `medical_records`
  ADD CONSTRAINT `medical_records_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointment` (`appoid`) ON DELETE SET NULL,
  ADD CONSTRAINT `medical_records_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patient` (`pid`),
  ADD CONSTRAINT `medical_records_ibfk_3` FOREIGN KEY (`doctor_id`) REFERENCES `doctor` (`docid`);

--
-- Ограничения внешнего ключа таблицы `medical_records_audit`
--
ALTER TABLE `medical_records_audit`
  ADD CONSTRAINT `medical_records_audit_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `medical_records` (`record_id`);

--
-- Ограничения внешнего ключа таблицы `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD CONSTRAINT `prescriptions_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `medical_records` (`record_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `schedule`
--
ALTER TABLE `schedule`
  ADD CONSTRAINT `schedule_ibfk_2` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `schedule_exceptions`
--
ALTER TABLE `schedule_exceptions`
  ADD CONSTRAINT `schedule_exceptions_ibfk_1` FOREIGN KEY (`docid`) REFERENCES `doctor` (`docid`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `treatment_plans`
--
ALTER TABLE `treatment_plans`
  ADD CONSTRAINT `treatment_plans_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `medical_records` (`record_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `vitals`
--
ALTER TABLE `vitals`
  ADD CONSTRAINT `vitals_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `medical_records` (`record_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
