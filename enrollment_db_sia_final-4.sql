-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Jul 16, 2026 at 10:38 AM
-- Server version: 8.0.44
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `enrollment_db_sia_final`
--

-- --------------------------------------------------------

--
-- Table structure for table `applicants`
--

CREATE TABLE `applicants` (
  `applicant_id` int NOT NULL,
  `reference_id` varchar(14) COLLATE utf8mb4_general_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `middle_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `birth_date` date NOT NULL,
  `sex` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `civil_status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Single',
  `contact_number` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `home_address` text COLLATE utf8mb4_general_ci NOT NULL,
  `guardian_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `guardian_relationship` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `guardian_contact` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `guardian_id_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `guardian_id_number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_verified_by` varchar(9) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `admission_status` enum('pending_verification','verified') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending_verification',
  `verified_at` timestamp NULL DEFAULT NULL,
  `program` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'BSIT',
  `course_id` int DEFAULT NULL,
  `year_level` tinyint NOT NULL DEFAULT '1',
  `start_term` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `applicant_type` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `applicant_type_id` int NOT NULL DEFAULT '1',
  `status` enum('Pending','Downpayment Paid','Fully Paid') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applicants`
--

INSERT INTO `applicants` (`applicant_id`, `reference_id`, `last_name`, `first_name`, `middle_name`, `birth_date`, `sex`, `civil_status`, `contact_number`, `email`, `home_address`, `guardian_name`, `guardian_relationship`, `guardian_contact`, `guardian_id_type`, `guardian_id_number`, `id_verified_by`, `admission_status`, `verified_at`, `program`, `course_id`, `year_level`, `start_term`, `applicant_type`, `applicant_type_id`, `status`, `created_at`, `updated_at`) VALUES
(3, '2026-00001', 'Bulado', 'Waffa Bea', 'Villorente', '2005-11-25', 'Female', 'Single', '09994850299', 'bea@example.com', 'Block 3, Lot 2, Langkaan 2, Dasmarinas City, Cavite', 'Abigaille Villorente', 'Mother', '09260557058', 'Philippine National ID', '123456', '2026-0004', 'verified', '2026-07-03 02:02:06', 'Bachelor of Science in Information Technology', 1, 1, '2026-2027', 'Freshman', 1, 'Downpayment Paid', '2026-07-03 01:51:22', '2026-07-03 06:50:19'),
(8, 'REF-00001-001', 'Reyes', 'Ian', 'Arvie', '2007-07-25', 'Male', 'Single', '09875647899', 'ian@example.com', 'Block 5, Lot 18, Via Verde, Dasmarinas City, Cavite', 'Arlene Reyes', 'Mother', '09456679856', 'PhilHealth ID', '465788', '2026-0004', 'verified', '2026-07-03 08:27:54', 'Bachelor of Science in Information Technology', 1, 1, '2026-2027', 'New', 1, 'Downpayment Paid', '2026-07-03 08:20:37', '2026-07-03 09:25:04'),
(9, 'REF-00002-002', 'Lualhati', 'Aiko', 'Nishihira', '2005-05-28', 'Female', 'Single', '09875647895', 'aiko@example.com', 'Block 3, lot 7, Regina, Trece Martires, Cavite', 'Nina Lualhati', 'Mother', '09976354765', 'Passport', '354765', '2026-0004', 'verified', '2026-07-03 14:38:43', 'Bachelor of Science in Information Technology', 1, 1, '2026-2027', 'New', 1, 'Pending', '2026-07-03 14:38:05', '2026-07-03 14:38:43'),
(10, 'REF-00003-003', 'Felices', 'Paul Kenneth', 'Eme', '2005-07-04', 'Male', 'Single', '09875647894', 'khen@example.com', 'Block 2, Lot 9, Don Placidos, Dasmarinas City, Cavite', 'Marife Gagalac', 'Mother', '09976354762', 'Driver&#039;s License', '236542', '2026-0004', 'verified', '2026-07-03 14:42:09', 'Bachelor of Science in Information Technology', 1, 1, '2026-2027', 'New', 1, 'Fully Paid', '2026-07-03 14:40:57', '2026-07-03 14:50:12'),
(11, 'REF-00004-001', 'Malbog', 'Frankyz', 'Ahzee', '2006-07-03', 'Male', 'Single', '09994758699', 'ahzee@example.com', 'Block 7, Lot 17, Ivory Crest, Dasmarinas City, Cavite', 'Marife Gagalac', 'Mother', '09976354766', 'Philhealth ID', '876980', '2026-0004', 'verified', '2026-07-04 04:31:13', 'Bachelor of Science in Information Technology', 1, 1, '2026-2027', 'New', 1, 'Downpayment Paid', '2026-07-04 04:28:26', '2026-07-04 04:36:05'),
(12, 'REF-00005-001', 'Ruazol', 'Holger', 'Eme', '2006-06-08', 'Male', 'Single', '09994758697', 'holger@example.com', 'akfssgafsfhsdgdhfhgdhfgsdfsdfg', 'Liwndasd', 'mother', '09976354786', 'Philhealth ID', '123456', '2026-0004', 'verified', '2026-07-11 02:37:13', 'Bachelor of Science in Information Technology', 1, 1, '2026-2027', 'New', 1, 'Pending', '2026-07-11 02:36:22', '2026-07-11 02:37:13'),
(13, 'REF-00006-002', 'asdfghdfsa', 'asdsghdgsf', 'asdfd', '2004-07-07', 'Male', 'Single', '09994758586', 'eme@example.com', 'uiashsgdhcasdfqwd12jw', 'sfsdgsdhsehetr', 'mother', '09976351028', 'Philhealth ID', '234675', '2026-0004', 'verified', '2026-07-11 11:06:33', 'Bachelor of Science in Information Technology', 1, 1, '2026-2027', 'New', 1, 'Pending', '2026-07-11 11:06:04', '2026-07-11 11:06:33'),
(14, 'REF-00007-001', 'Timbal', 'Ivan', 'Philip', '2005-07-07', 'Male', 'Single', '09994758509', 'ivan@example.com', 'Basta taga dun sa may area E', 'Marife Gagalac', 'Mother', '09456679398', 'Philhealth ID', '123654', NULL, 'pending_verification', NULL, 'Bachelor of Science in Information Technology', 1, 1, '2026-2027', 'Transferee', 1, 'Pending', '2026-07-12 03:47:09', '2026-07-12 03:47:09');

-- --------------------------------------------------------

--
-- Table structure for table `applicant_documents`
--

CREATE TABLE `applicant_documents` (
  `document_id` int NOT NULL,
  `applicant_id` int NOT NULL,
  `document_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('pending','submitted') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `verified_by` int DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applicant_documents`
--

INSERT INTO `applicant_documents` (`document_id`, `applicant_id`, `document_name`, `file_path`, `status`, `verified_by`, `uploaded_at`) VALUES
(1, 3, 'Form 137 / SHS Card', NULL, 'submitted', NULL, '2026-07-03 01:51:22'),
(2, 3, 'Certificate of Good Moral', NULL, 'submitted', NULL, '2026-07-03 01:51:22'),
(3, 3, 'Birth Certificate (PSA)', NULL, 'submitted', NULL, '2026-07-03 01:51:22'),
(4, 3, '2x2 ID Photos', NULL, 'submitted', NULL, '2026-07-03 01:51:22'),
(13, 8, 'Form 137 / SHS Card', NULL, 'submitted', 6, '2026-07-03 08:27:54'),
(14, 8, 'Birth Certificate (PSA)', NULL, 'submitted', 6, '2026-07-03 08:27:54'),
(15, 8, 'Certificate of Good Moral', NULL, 'submitted', 6, '2026-07-03 08:27:54'),
(16, 8, '2x2 ID Photos', NULL, 'submitted', 6, '2026-07-03 08:27:54'),
(17, 9, 'Form 137 / SHS Card', NULL, 'submitted', 6, '2026-07-03 14:38:43'),
(18, 9, 'Birth Certificate (PSA)', NULL, 'submitted', 6, '2026-07-03 14:38:43'),
(19, 9, 'Certificate of Good Moral', NULL, 'submitted', 6, '2026-07-03 14:38:43'),
(20, 9, '2x2 ID Photos', NULL, 'submitted', 6, '2026-07-03 14:38:43'),
(21, 10, 'Certificate of Good Moral', NULL, 'submitted', 6, '2026-07-03 14:42:09'),
(22, 10, '2x2 ID Photos', NULL, 'submitted', 6, '2026-07-03 14:42:09'),
(23, 10, 'Form 137 / SHS Card', NULL, 'submitted', 6, '2026-07-03 14:42:09'),
(24, 10, 'Birth Certificate (PSA)', NULL, 'submitted', 6, '2026-07-03 14:42:09'),
(25, 11, 'Form 137 / SHS Card', NULL, 'submitted', 6, '2026-07-04 04:31:13'),
(26, 11, 'Birth Certificate (PSA)', NULL, 'submitted', 6, '2026-07-04 04:31:13'),
(27, 11, 'Certificate of Good Moral', NULL, 'submitted', 6, '2026-07-04 04:31:13'),
(28, 11, '2x2 ID Photos', NULL, 'submitted', 6, '2026-07-04 04:31:13'),
(29, 12, 'Certificate of Good Moral', NULL, 'submitted', 6, '2026-07-11 02:37:13'),
(30, 12, '2x2 ID Photos', NULL, 'submitted', 6, '2026-07-11 02:37:13'),
(31, 12, 'Form 137 / SHS Card', NULL, 'submitted', 6, '2026-07-11 02:37:13'),
(32, 12, 'Birth Certificate (PSA)', NULL, 'submitted', 6, '2026-07-11 02:37:13'),
(33, 13, 'Form 137 / SHS Card', NULL, 'submitted', 6, '2026-07-11 11:06:33'),
(34, 13, 'Birth Certificate (PSA)', NULL, 'submitted', 6, '2026-07-11 11:06:33'),
(35, 13, 'Certificate of Good Moral', NULL, 'submitted', 6, '2026-07-11 11:06:33'),
(36, 13, '2x2 ID Photos', NULL, 'submitted', 6, '2026-07-11 11:06:33');

-- --------------------------------------------------------

--
-- Table structure for table `applicant_school_history`
--

CREATE TABLE `applicant_school_history` (
  `history_id` int NOT NULL,
  `applicant_id` int NOT NULL,
  `school_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `school_address` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `school_year` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `school_strand` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `school_gpa` decimal(4,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applicant_school_history`
--

INSERT INTO `applicant_school_history` (`history_id`, `applicant_id`, `school_name`, `school_address`, `school_year`, `school_strand`, `school_gpa`) VALUES
(1, 3, 'NCST SHS', 'Dasmarinas City', '2025', 'STEM', 99.00),
(5, 8, 'NCST SHS', 'Aguinaldo, Dasmarinas City, Cavite', '2025', 'STEM', 97.00),
(6, 9, 'NCST SHS', 'Aguinaldo, Dasmarinas City, Cavite', '2025', 'STEM', 99.00),
(7, 10, 'NCST SHS', 'Aguinaldo, Dasmarinas City, Cavite', '2025', 'STEM', 99.00),
(8, 11, 'NCST SHS', 'Aguinaldo, Dasmarinas City, Cavite', '2025', 'STEM', 99.00),
(9, 12, 'NCST SHS', 'Aguinaldo, Dasmarinas City, Cavite', '2025', 'STEM', 99.00),
(10, 13, 'NCST SHS', 'Aguinaldo, Dasmarinas City, Cavite', '2025', 'STEM', 99.00),
(11, 14, 'NCST SHS', 'Aguinaldo, Dasmarinas City, Cavite', '2025', 'STEM', 97.00);

-- --------------------------------------------------------

--
-- Table structure for table `applicant_subject_credit`
--

CREATE TABLE `applicant_subject_credit` (
  `credit_id` int NOT NULL,
  `applicant_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `credited_by` varchar(9) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course`
--

CREATE TABLE `course` (
  `course_id` int NOT NULL,
  `course_code` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `course_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `total_units` int NOT NULL DEFAULT '0',
  `status` enum('Pending','Approved','Rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Approved',
  `requested_by` int DEFAULT NULL,
  `reviewed_by` int DEFAULT NULL,
  `review_note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course`
--

INSERT INTO `course` (`course_id`, `course_code`, `course_name`, `total_units`, `status`, `requested_by`, `reviewed_by`, `review_note`) VALUES
(1, 'BSIT', 'Bachelor of Science in Information Technology', 120, 'Approved', NULL, NULL, NULL),
(2, 'BSPSYCH', 'Bachelor of Science in Psychology', 126, 'Approved', NULL, NULL, NULL),
(3, 'BSCRIM', 'Bachelor of Science in Criminology', 126, 'Approved', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `department`
--

CREATE TABLE `department` (
  `department_id` int NOT NULL,
  `department_code` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `department_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `department`
--

INSERT INTO `department` (`department_id`, `department_code`, `department_name`, `created_at`) VALUES
(1, 'CSD', 'Computes of Studies Department', '2026-06-27 23:06:53'),
(2, 'GEN ED', 'General Education Department', '2026-06-27 23:06:53'),
(3, 'NSTP', 'National Service Training Program', '2026-06-27 23:06:53'),
(4, 'PE', 'Physical Education Department', '2026-06-27 23:06:53');

-- --------------------------------------------------------

--
-- Table structure for table `enrollment`
--

CREATE TABLE `enrollment` (
  `enrollment_id` int NOT NULL,
  `student_id` int NOT NULL,
  `school_year` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `semester` tinyint NOT NULL,
  `year_level` tinyint NOT NULL,
  `section_id` int DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Enrolled',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `type_id` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollment`
--

INSERT INTO `enrollment` (`enrollment_id`, `student_id`, `school_year`, `semester`, `year_level`, `section_id`, `status`, `created_at`, `updated_at`, `type_id`) VALUES
(1, 3, '2026-2027', 1, 1, 1, 'Enrolled', '2026-07-03 02:01:19', '2026-07-03 02:02:06', 1),
(2, 8, '2026-2027', 1, 1, 1, 'Enrolled', '2026-07-03 09:18:12', '2026-07-03 09:25:04', 1),
(3, 10, '2026-2027', 1, 1, 1, 'Enrolled', '2026-07-03 14:46:34', '2026-07-03 14:49:02', 1),
(4, 11, '2026-2027', 1, 1, 1, 'Enrolled', '2026-07-04 04:32:59', '2026-07-04 04:36:05', 1),
(5, 12, '2026-2027', 1, 1, NULL, 'Pending Payment', '2026-07-11 03:06:01', '2026-07-11 03:06:01', 2);

-- --------------------------------------------------------

--
-- Table structure for table `enrollment_subject`
--

CREATE TABLE `enrollment_subject` (
  `enrollment_subject_id` int NOT NULL,
  `enrollment_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `schedule_id` int DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Enrolled',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollment_subject`
--

INSERT INTO `enrollment_subject` (`enrollment_subject_id`, `enrollment_id`, `subject_id`, `schedule_id`, `status`, `created_at`) VALUES
(1, 1, 4, NULL, 'Enrolled', '2026-07-03 02:01:19'),
(2, 2, 4, NULL, 'Enrolled', '2026-07-03 09:18:12'),
(3, 3, 1, NULL, 'Enrolled', '2026-07-03 14:46:34'),
(4, 3, 2, NULL, 'Enrolled', '2026-07-03 14:46:34'),
(5, 3, 4, NULL, 'Enrolled', '2026-07-03 14:46:34'),
(6, 3, 5, NULL, 'Enrolled', '2026-07-03 14:46:34'),
(7, 3, 8, NULL, 'Enrolled', '2026-07-03 14:46:34'),
(8, 3, 9, NULL, 'Enrolled', '2026-07-03 14:46:34'),
(9, 4, 1, NULL, 'Enrolled', '2026-07-04 04:32:59'),
(10, 4, 2, NULL, 'Enrolled', '2026-07-04 04:32:59'),
(11, 4, 4, NULL, 'Enrolled', '2026-07-04 04:32:59'),
(12, 4, 5, NULL, 'Enrolled', '2026-07-04 04:32:59'),
(13, 4, 8, NULL, 'Enrolled', '2026-07-04 04:32:59'),
(14, 4, 9, NULL, 'Enrolled', '2026-07-04 04:32:59'),
(15, 5, 1, 10, 'Enrolled', '2026-07-11 03:06:01'),
(16, 5, 2, 11, 'Enrolled', '2026-07-11 03:06:01'),
(17, 5, 4, 9, 'Enrolled', '2026-07-11 03:06:01');

-- --------------------------------------------------------

--
-- Table structure for table `fee_schedule`
--

CREATE TABLE `fee_schedule` (
  `fee_schedule_id` int NOT NULL,
  `year_level` int NOT NULL,
  `school_year` varchar(9) COLLATE utf8mb4_general_ci NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fee_schedule`
--

INSERT INTO `fee_schedule` (`fee_schedule_id`, `year_level`, `school_year`, `total_amount`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, '2026-2027', 23500.00, 1, '2026-06-30 17:04:33', '2026-06-30 17:04:33'),
(2, 2, '2026-2027', 25500.00, 1, '2026-06-30 17:04:33', '2026-06-30 17:04:33'),
(3, 3, '2026-2027', 26500.00, 1, '2026-06-30 17:04:33', '2026-06-30 17:04:33'),
(4, 4, '2026-2027', 26500.00, 1, '2026-06-30 17:04:33', '2026-06-30 17:04:33');

-- --------------------------------------------------------

--
-- Table structure for table `fee_schedule_item`
--

CREATE TABLE `fee_schedule_item` (
  `item_id` int NOT NULL,
  `fee_schedule_id` int NOT NULL,
  `label` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `is_per_unit` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fee_schedule_item`
--

INSERT INTO `fee_schedule_item` (`item_id`, `fee_schedule_id`, `label`, `amount`, `is_per_unit`, `sort_order`) VALUES
(1, 1, 'Tuition Fee', 750.00, 1, 1),
(2, 1, 'Laboratory Fee', 3500.00, 0, 2),
(3, 1, 'Miscellaneous Fee', 2500.00, 0, 3),
(4, 1, 'Library Fee', 1000.00, 0, 4),
(5, 1, 'Athletic / Medical Fee', 1000.00, 0, 5),
(6, 1, 'Registration / ID Fee', 500.00, 0, 6),
(7, 2, 'Tuition Fee', 750.00, 1, 1),
(8, 2, 'Laboratory Fee', 4500.00, 0, 2),
(9, 2, 'Miscellaneous Fee', 2500.00, 0, 3),
(10, 2, 'Library Fee', 1000.00, 0, 4),
(11, 2, 'Athletic / Medical Fee', 1000.00, 0, 5),
(12, 2, 'Registration / ID Fee', 500.00, 0, 6),
(13, 3, 'Tuition Fee', 750.00, 1, 1),
(14, 3, 'Laboratory Fee', 5000.00, 0, 2),
(15, 3, 'Miscellaneous Fee', 2500.00, 0, 3),
(16, 3, 'Library Fee', 1000.00, 0, 4),
(17, 3, 'Athletic / Medical Fee', 1000.00, 0, 5),
(18, 3, 'Registration / ID Fee', 500.00, 0, 6),
(19, 4, 'Tuition Fee', 750.00, 1, 1),
(20, 4, 'Laboratory Fee', 2000.00, 0, 2),
(21, 4, 'Miscellaneous Fee', 3000.00, 0, 3),
(22, 4, 'Thesis / Practicum Fee', 4000.00, 0, 4),
(23, 4, 'Library Fee', 1000.00, 0, 5),
(24, 4, 'Athletic / Medical Fee', 1000.00, 0, 6),
(25, 4, 'Registration / ID Fee', 500.00, 0, 7);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int NOT NULL,
  `sender_id` int NOT NULL,
  `recipient_id` int NOT NULL,
  `body` text COLLATE utf8mb4_general_ci NOT NULL,
  `attachment_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `attachment_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `attachment_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `attachment_size` int UNSIGNED DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`message_id`, `sender_id`, `recipient_id`, `body`, `attachment_path`, `attachment_name`, `attachment_type`, `attachment_size`, `sent_at`, `read_at`) VALUES
(1, 16, 15, 'hello', NULL, NULL, NULL, NULL, '2026-07-15 01:25:45', '2026-07-15 15:26:21'),
(3, 16, 15, 'hello???', NULL, NULL, NULL, NULL, '2026-07-15 16:15:40', '2026-07-15 16:16:07'),
(4, 16, 15, '<script> alert(\'Hello World\')</script>', NULL, NULL, NULL, NULL, '2026-07-15 16:23:45', '2026-07-15 16:34:45'),
(5, 16, 15, 'hiii', NULL, NULL, NULL, NULL, '2026-07-15 16:24:16', '2026-07-15 16:34:45'),
(6, 16, 15, 'Please check my minecraft', 'messages/68e6ab5c4095345001e0482b7614a8d4.png', 'Screen Shot 2026-06-11 at 9.40.59 PM.png', 'image/png', 659527, '2026-07-15 16:30:14', '2026-07-15 16:34:45'),
(7, 16, 15, 'https://open.spotify.com/playlist/5D9zLsE3VcP5TosuCDiFvF', NULL, NULL, NULL, NULL, '2026-07-15 16:30:42', '2026-07-15 16:34:45'),
(8, 16, 15, 'check my cheatsheet', 'messages/f10d7d0f1953ceeaa5562c54815da22a.pdf', '752791931-Cheat-Sheet-HTML-Css-Js.pdf', 'application/pdf', 581680, '2026-07-15 16:33:15', '2026-07-15 16:34:45'),
(9, 15, 17, 'Hi lino Welcome', NULL, NULL, NULL, NULL, '2026-07-15 16:54:43', NULL),
(10, 15, 17, 'hi bossing', NULL, NULL, NULL, NULL, '2026-07-15 16:58:11', NULL),
(11, 15, 16, 'hey', NULL, NULL, NULL, NULL, '2026-07-15 16:58:15', '2026-07-16 10:26:21');

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `payment_id` int NOT NULL,
  `enrollment_id` int NOT NULL,
  `amount_due` decimal(10,2) NOT NULL,
  `downpayment` decimal(10,2) NOT NULL DEFAULT '0.00',
  `balance` decimal(10,2) GENERATED ALWAYS AS ((`amount_due` - `downpayment`)) STORED,
  `due_date` date NOT NULL,
  `payment_status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Unpaid',
  `paid_at` timestamp NULL DEFAULT NULL,
  `received_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`payment_id`, `enrollment_id`, `amount_due`, `downpayment`, `due_date`, `payment_status`, `paid_at`, `received_by`, `created_at`, `updated_at`) VALUES
(1, 1, 10750.00, 3000.00, '2026-07-06', 'Down Payment Paid', '2026-07-03 02:02:06', 5, '2026-07-03 02:01:19', '2026-07-03 02:02:06'),
(2, 2, 10750.00, 3000.00, '2026-07-06', 'Down Payment Paid', '2026-07-03 09:25:04', 5, '2026-07-03 09:18:12', '2026-07-03 09:25:04'),
(3, 3, 19750.00, 19750.00, '2026-07-06', 'Fully Paid', '2026-07-03 14:50:12', 5, '2026-07-03 14:46:34', '2026-07-03 14:50:12'),
(4, 4, 19750.00, 3000.00, '2026-07-07', 'Down Payment Paid', '2026-07-04 04:36:05', 5, '2026-07-04 04:32:59', '2026-07-04 04:36:05'),
(5, 5, 15250.00, 0.00, '2026-07-14', 'Unpaid', NULL, NULL, '2026-07-11 03:06:01', '2026-07-11 03:06:01');

-- --------------------------------------------------------

--
-- Table structure for table `payment_breakdown`
--

CREATE TABLE `payment_breakdown` (
  `breakdown_id` int NOT NULL,
  `payment_id` int NOT NULL,
  `label` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_breakdown`
--

INSERT INTO `payment_breakdown` (`breakdown_id`, `payment_id`, `label`, `amount`, `sort_order`) VALUES
(1, 1, 'Tuition Fee', 2250.00, 1),
(2, 1, 'Laboratory Fee', 3500.00, 2),
(3, 1, 'Miscellaneous Fee', 2500.00, 3),
(4, 1, 'Library Fee', 1000.00, 4),
(5, 1, 'Athletic / Medical Fee', 1000.00, 5),
(6, 1, 'Registration / ID Fee', 500.00, 6),
(7, 2, 'Tuition Fee', 2250.00, 1),
(8, 2, 'Laboratory Fee', 3500.00, 2),
(9, 2, 'Miscellaneous Fee', 2500.00, 3),
(10, 2, 'Library Fee', 1000.00, 4),
(11, 2, 'Athletic / Medical Fee', 1000.00, 5),
(12, 2, 'Registration / ID Fee', 500.00, 6),
(13, 3, 'Tuition Fee', 11250.00, 1),
(14, 3, 'Laboratory Fee', 3500.00, 2),
(15, 3, 'Miscellaneous Fee', 2500.00, 3),
(16, 3, 'Library Fee', 1000.00, 4),
(17, 3, 'Athletic / Medical Fee', 1000.00, 5),
(18, 3, 'Registration / ID Fee', 500.00, 6),
(19, 4, 'Tuition Fee', 11250.00, 1),
(20, 4, 'Laboratory Fee', 3500.00, 2),
(21, 4, 'Miscellaneous Fee', 2500.00, 3),
(22, 4, 'Library Fee', 1000.00, 4),
(23, 4, 'Athletic / Medical Fee', 1000.00, 5),
(24, 4, 'Registration / ID Fee', 500.00, 6),
(25, 5, 'Tuition Fee', 6750.00, 1),
(26, 5, 'Laboratory Fee', 3500.00, 2),
(27, 5, 'Miscellaneous Fee', 2500.00, 3),
(28, 5, 'Library Fee', 1000.00, 4),
(29, 5, 'Athletic / Medical Fee', 1000.00, 5),
(30, 5, 'Registration / ID Fee', 500.00, 6);

-- --------------------------------------------------------

--
-- Table structure for table `payment_transactions`
--

CREATE TABLE `payment_transactions` (
  `transaction_id` int NOT NULL,
  `payment_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `paid_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `remarks` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `received_by` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_transactions`
--

INSERT INTO `payment_transactions` (`transaction_id`, `payment_id`, `amount`, `paid_at`, `remarks`, `received_by`) VALUES
(1, 1, 3000.00, '2026-07-03 02:02:06', 'cash', 5),
(2, 2, 3000.00, '2026-07-03 09:25:04', 'cash', 5),
(3, 3, 3000.00, '2026-07-03 14:49:02', 'cash', 5),
(4, 3, 1000.00, '2026-07-03 14:49:26', 'cash', 5),
(5, 3, 15750.00, '2026-07-03 14:50:12', 'cash', 5),
(6, 4, 3000.00, '2026-07-04 04:36:05', 'cash', 5);

-- --------------------------------------------------------

--
-- Table structure for table `professor`
--

CREATE TABLE `professor` (
  `professor_id` int NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `middle_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `department_id` int NOT NULL,
  `status_id` int NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `professor`
--

INSERT INTO `professor` (`professor_id`, `last_name`, `first_name`, `middle_name`, `department_id`, `status_id`, `created_at`, `updated_at`) VALUES
(1, 'Santos', 'John', NULL, 1, 1, '2026-06-30 03:14:33', '2026-06-30 03:14:33'),
(2, 'Reyes', 'Maria', NULL, 2, 1, '2026-06-30 03:14:33', '2026-06-30 03:14:33'),
(3, 'Garcia', 'Anthony', NULL, 1, 1, '2026-06-30 03:14:33', '2026-06-30 03:14:33'),
(4, 'Dela Cruz', 'Louise', NULL, 4, 1, '2026-06-30 03:14:33', '2026-06-30 03:14:33');

-- --------------------------------------------------------

--
-- Table structure for table `readmission_request`
--

CREATE TABLE `readmission_request` (
  `request_id` int NOT NULL,
  `student_id` int NOT NULL,
  `reason` text COLLATE utf8mb4_general_ci NOT NULL,
  `requested_school_year` varchar(9) COLLATE utf8mb4_general_ci NOT NULL,
  `requested_semester` tinyint NOT NULL,
  `is_shifting` tinyint(1) NOT NULL DEFAULT '0',
  `new_course_id` int DEFAULT NULL,
  `processed_by` varchar(9) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('Pending','Approved','Rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Pending',
  `reviewed_by` varchar(9) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `review_note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int NOT NULL,
  `role_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`, `created_at`) VALUES
(1, 'Admin', '2026-06-27 23:00:09'),
(2, 'Admission', '2026-06-27 23:00:09'),
(3, 'Staff', '2026-06-27 23:00:09'),
(4, 'Treasury', '2026-07-01 04:39:31'),
(5, 'Head Registrar', '2026-07-12 23:22:59'),
(6, 'Registrar Staff', '2026-07-12 23:22:59');

-- --------------------------------------------------------

--
-- Table structure for table `room`
--

CREATE TABLE `room` (
  `room_id` int NOT NULL,
  `room_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `room_type` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Lecture',
  `capacity` int NOT NULL DEFAULT '40',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room`
--

INSERT INTO `room` (`room_id`, `room_name`, `room_type`, `capacity`, `created_at`) VALUES
(1, 'Computer Laboratory 1', 'Lecture', 40, '2026-06-30 03:04:03'),
(2, 'Room 301', 'Lecture', 40, '2026-06-30 03:04:03'),
(3, 'Room 205', 'Lecture', 40, '2026-06-30 03:04:03'),
(4, 'Gymnasium', 'Lecture', 40, '2026-06-30 03:04:03');

-- --------------------------------------------------------

--
-- Table structure for table `schedule`
--

CREATE TABLE `schedule` (
  `schedule_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `professor_id` int NOT NULL,
  `section_id` int NOT NULL,
  `room_id` int NOT NULL,
  `day` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `time_start` time NOT NULL,
  `time_end` time NOT NULL,
  `school_year` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `semester` tinyint NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `status` enum('Pending','Approved','Rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Approved',
  `requested_by` int DEFAULT NULL,
  `reviewed_by` int DEFAULT NULL,
  `review_note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedule`
--

INSERT INTO `schedule` (`schedule_id`, `subject_id`, `professor_id`, `section_id`, `room_id`, `day`, `time_start`, `time_end`, `school_year`, `semester`, `created_at`, `updated_at`, `is_active`, `status`, `requested_by`, `reviewed_by`, `review_note`) VALUES
(9, 4, 2, 1, 1, 'Monday', '07:00:00', '10:00:00', '2026-2027', 1, '2026-07-03 01:56:27', '2026-07-03 01:56:27', 1, 'Approved', NULL, NULL, NULL),
(10, 1, 4, 1, 3, 'Monday', '11:00:00', '12:30:00', '2026-2027', 1, '2026-07-03 05:41:51', '2026-07-03 05:41:51', 1, 'Approved', NULL, NULL, NULL),
(11, 2, 4, 1, 2, 'Tuesday', '09:00:00', '11:30:00', '2026-2027', 1, '2026-07-03 05:42:30', '2026-07-03 05:42:30', 1, 'Approved', NULL, NULL, NULL),
(15, 4, 2, 1, 1, 'Tuesday', '11:30:00', '13:30:00', '2026-2027', 1, '2026-07-03 05:44:51', '2026-07-03 05:44:51', 1, 'Approved', NULL, NULL, NULL),
(16, 9, 1, 1, 4, 'Wednesday', '12:00:00', '13:00:00', '2026-2027', 1, '2026-07-03 05:45:50', '2026-07-03 05:45:50', 1, 'Approved', NULL, NULL, NULL),
(17, 5, 3, 1, 1, 'Wednesday', '08:00:00', '11:00:00', '2026-2027', 1, '2026-07-03 05:47:02', '2026-07-03 05:47:02', 1, 'Approved', NULL, NULL, NULL),
(18, 8, 1, 1, 4, 'Thursday', '09:00:00', '11:00:00', '2026-2027', 1, '2026-07-03 05:48:48', '2026-07-03 05:48:48', 1, 'Approved', NULL, NULL, NULL),
(19, 5, 3, 1, 1, 'Thursday', '13:00:00', '16:00:00', '2026-2027', 1, '2026-07-03 05:49:22', '2026-07-03 05:49:22', 1, 'Approved', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `section`
--

CREATE TABLE `section` (
  `section_id` int NOT NULL,
  `section_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `capacity` int NOT NULL DEFAULT '40',
  `status` enum('Pending','Approved','Rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Approved',
  `requested_by` int DEFAULT NULL,
  `reviewed_by` int DEFAULT NULL,
  `review_note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `course_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `section`
--

INSERT INTO `section` (`section_id`, `section_name`, `capacity`, `status`, `requested_by`, `reviewed_by`, `review_note`, `course_id`) VALUES
(1, 'BSIT A1', 40, 'Approved', NULL, NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `statuses`
--

CREATE TABLE `statuses` (
  `status_id` int NOT NULL,
  `status_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `statuses`
--

INSERT INTO `statuses` (`status_id`, `status_name`, `created_at`) VALUES
(1, 'Active', '2026-06-27 23:00:09'),
(2, 'Inactive', '2026-06-27 23:00:09');

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `student_id` int NOT NULL,
  `student_no` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `student_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `middle_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `sex` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contact_number` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_general_ci,
  `applicant_id` int DEFAULT NULL,
  `section_id` int DEFAULT NULL,
  `type_id` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`student_id`, `student_no`, `student_name`, `last_name`, `first_name`, `middle_name`, `birth_date`, `sex`, `contact_number`, `email`, `address`, `applicant_id`, `section_id`, `type_id`) VALUES
(1, '2026-00001', 'Waffa Bea Bulado', 'Bulado', 'Waffa Bea', 'Villorente', '2005-11-25', 'Female', '09994850299', 'bea@example.com', 'Block 3, Lot 2, Langkaan 2, Dasmarinas City, Cavite', 3, 1, 1),
(2, '2026-00002', 'Ian Reyes', 'Reyes', 'Ian', 'Arvie', '2007-07-25', 'Male', '09875647899', 'ian@example.com', 'Block 5, Lot 18, Via Verde, Dasmarinas City, Cavite', 8, 1, 1),
(3, '2026-00003', 'Paul Kenneth Felices', 'Felices', 'Paul Kenneth', 'Eme', '2005-07-04', 'Male', '09875647894', 'khen@example.com', 'Block 2, Lot 9, Don Placidos, Dasmarinas City, Cavite', 10, 1, 1),
(4, '2026-00004', 'Frankyz Malbog', 'Malbog', 'Frankyz', 'Ahzee', '2006-07-03', 'Male', '09994758699', 'ahzee@example.com', 'Block 7, Lot 17, Ivory Crest, Dasmarinas City, Cavite', 11, 1, 1),
(5, '2026-00005', 'Holger Ruazol', 'Ruazol', 'Holger', 'Eme', '2006-06-08', 'Male', '09994758697', 'holger@example.com', 'akfssgafsfhsdgdhfhgdhfgsdfsdfg', 12, NULL, 2);

-- --------------------------------------------------------

--
-- Table structure for table `student_type`
--

CREATE TABLE `student_type` (
  `type_id` int NOT NULL,
  `type_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_type`
--

INSERT INTO `student_type` (`type_id`, `type_name`) VALUES
(2, 'Irregular'),
(1, 'Regular'),
(4, 'Returnee'),
(3, 'Transferee');

-- --------------------------------------------------------

--
-- Table structure for table `subject`
--

CREATE TABLE `subject` (
  `subject_id` int NOT NULL,
  `course_id` int NOT NULL DEFAULT '1',
  `subject_code` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `subject_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `units` decimal(4,2) NOT NULL DEFAULT '3.00',
  `category_id` int NOT NULL,
  `year_level` tinyint NOT NULL,
  `semester` tinyint NOT NULL,
  `prereq_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subject`
--

INSERT INTO `subject` (`subject_id`, `course_id`, `subject_code`, `subject_name`, `units`, `category_id`, `year_level`, `semester`, `prereq_id`) VALUES
(1, 1, 'Ge 101', 'UTS', 3.00, 2, 1, 1, NULL),
(2, 1, 'GE102', 'Readings in Philippine History', 3.00, 1, 1, 1, NULL),
(3, 1, 'GEE101', 'Environmental Science', 3.00, 2, 1, 2, NULL),
(4, 1, 'IT101', 'Introduction to Computing', 3.00, 3, 1, 1, NULL),
(5, 1, 'IT102', 'Computer Programming 1', 3.00, 3, 1, 1, NULL),
(6, 1, 'IT201', 'Data Structures', 3.00, 3, 2, 1, 5),
(7, 1, 'NSTP101', 'National Service Training Program 1', 3.00, 4, 1, 1, NULL),
(8, 1, 'PATHFIT1', 'Movement Competency Training', 2.00, 5, 1, 1, NULL),
(9, 1, 'NCST101', 'NCST Institutional Orientation', 1.00, 6, 1, 1, NULL),
(10, 1, 'GEE102', 'Purposive Communication', 3.00, 1, 1, 2, NULL),
(11, 1, 'NSTP102', 'National Service Training Program 2', 3.00, 4, 1, 2, NULL),
(12, 1, 'PATHFIT2', 'Exercise-based Fitness Activities', 2.00, 5, 1, 2, NULL),
(13, 1, 'GE103', 'Mathematics in the Modern World', 3.00, 1, 2, 1, NULL),
(14, 1, 'PATHFIT3', 'Dance/Sports/Recreation', 2.00, 5, 2, 1, NULL),
(15, 1, 'GEE103', 'Art Appreciation', 3.00, 2, 2, 2, NULL),
(16, 1, 'PATHFIT4', 'Health Optimizing Physical Education', 2.00, 5, 2, 2, NULL),
(17, 1, 'GE104', 'The Contemporary World', 3.00, 1, 3, 1, NULL),
(18, 1, 'GEE104', 'Life and Works of Rizal', 3.00, 2, 3, 2, NULL),
(19, 1, 'IT103', 'Computer Programming 2', 3.00, 3, 1, 2, 5),
(20, 1, 'IT202', 'Information Management', 3.00, 3, 2, 1, 6),
(21, 1, 'IT203', 'Object-Oriented Programming', 3.00, 3, 2, 1, 19),
(22, 1, 'IT204', 'Database Management Systems', 3.00, 3, 2, 2, 20),
(23, 1, 'IT205', 'Web Systems and Technologies', 3.00, 3, 2, 2, 21),
(24, 1, 'IT301', 'Networking 1', 3.00, 3, 3, 1, 22),
(25, 1, 'IT302', 'Systems Analysis and Design', 3.00, 3, 3, 1, 22),
(26, 1, 'IT303', 'Human-Computer Interaction', 3.00, 3, 3, 1, NULL),
(27, 1, 'IT304', 'Networking 2', 3.00, 3, 3, 2, 24),
(28, 1, 'IT305', 'Information Assurance and Security', 3.00, 3, 3, 2, 22),
(29, 1, 'IT306', 'Application Development', 3.00, 3, 3, 2, 23),
(30, 1, 'IT401', 'Capstone Project 1', 3.00, 3, 4, 1, 29),
(31, 1, 'IT402', 'Systems Integration and Architecture', 3.00, 3, 4, 1, 28),
(32, 1, 'IT403', 'IT Elective 1', 3.00, 3, 4, 1, NULL),
(33, 1, 'IT404', 'Capstone Project 2', 3.00, 3, 4, 2, 30),
(34, 1, 'IT405', 'Practicum/On-the-Job Training (IT)', 6.00, 3, 4, 2, 31),
(35, 2, 'PSY101', 'General Psychology', 3.00, 7, 1, 1, NULL),
(36, 2, 'PSY102', 'Developmental Psychology', 3.00, 7, 1, 2, 35),
(37, 2, 'PSY201', 'Abnormal Psychology', 3.00, 7, 2, 1, 36),
(38, 2, 'PSY202', 'Theories of Personality', 3.00, 7, 2, 1, 35),
(39, 2, 'PSY203', 'Social Psychology', 3.00, 7, 2, 2, 35),
(40, 2, 'PSY204', 'Psychological Statistics', 3.00, 7, 2, 2, NULL),
(41, 2, 'PSY301', 'Industrial-Organizational Psychology', 3.00, 7, 3, 1, 39),
(42, 2, 'PSY302', 'Psychological Assessment 1', 3.00, 7, 3, 1, 40),
(43, 2, 'PSY303', 'Counseling Psychology', 3.00, 7, 3, 2, 37),
(44, 2, 'PSY304', 'Psychological Assessment 2', 3.00, 7, 3, 2, 42),
(45, 2, 'PSY401', 'Undergraduate Thesis 1 (Psychology)', 3.00, 7, 4, 1, 44),
(46, 2, 'PSY402', 'Psychopathology', 3.00, 7, 4, 1, 37),
(47, 2, 'PSY403', 'Undergraduate Thesis 2 (Psychology)', 3.00, 7, 4, 2, 45),
(48, 2, 'PSY404', 'Practicum/On-the-Job Training (Psychology)', 6.00, 7, 4, 2, 43),
(49, 3, 'CRIM101', 'Introduction to Criminology', 3.00, 8, 1, 1, NULL),
(50, 3, 'CRIM102', 'Criminal Law 1 (Revised Penal Code Book 1)', 3.00, 8, 1, 2, 49),
(51, 3, 'CRIM201', 'Criminal Law 2 (Revised Penal Code Book 2)', 3.00, 8, 2, 1, 50),
(52, 3, 'CRIM202', 'Criminalistics 1', 3.00, 8, 2, 1, 49),
(53, 3, 'CRIM203', 'Criminalistics 2', 3.00, 8, 2, 2, 52),
(54, 3, 'CRIM204', 'Law Enforcement Administration', 3.00, 8, 2, 2, 49),
(55, 3, 'CRIM301', 'Forensic Science', 3.00, 8, 3, 1, 53),
(56, 3, 'CRIM302', 'Criminal Procedure', 3.00, 8, 3, 1, 51),
(57, 3, 'CRIM303', 'Correctional Administration', 3.00, 8, 3, 2, 54),
(58, 3, 'CRIM304', 'Evidence and Special Rules of Evidence', 3.00, 8, 3, 2, 56),
(59, 3, 'CRIM401', 'Undergraduate Thesis 1 (Criminology)', 3.00, 8, 4, 1, 58),
(60, 3, 'CRIM402', 'Criminological Research', 3.00, 8, 4, 1, 58),
(61, 3, 'CRIM403', 'Undergraduate Thesis 2 (Criminology)', 3.00, 8, 4, 2, 59),
(62, 3, 'CRIM404', 'Practicum/On-the-Job Training (Criminology)', 6.00, 8, 4, 2, 57);

-- --------------------------------------------------------

--
-- Table structure for table `subject_category`
--

CREATE TABLE `subject_category` (
  `category_id` int NOT NULL,
  `category_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subject_category`
--

INSERT INTO `subject_category` (`category_id`, `category_name`) VALUES
(8, 'CRIM'),
(2, 'GE ELECTIVE'),
(1, 'GEN ED'),
(3, 'IT'),
(6, 'NCST'),
(4, 'NSTP'),
(5, 'PATHFIT'),
(7, 'PSYCH');

-- --------------------------------------------------------

--
-- Table structure for table `subject_course`
--

CREATE TABLE `subject_course` (
  `subject_id` int NOT NULL,
  `course_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subject_course`
--

INSERT INTO `subject_course` (`subject_id`, `course_id`) VALUES
(1, 2),
(2, 2),
(3, 2),
(7, 2),
(8, 2),
(9, 2),
(10, 2),
(11, 2),
(12, 2),
(13, 2),
(14, 2),
(15, 2),
(16, 2),
(17, 2),
(18, 2),
(1, 3),
(2, 3),
(3, 3),
(7, 3),
(8, 3),
(9, 3),
(10, 3),
(11, 3),
(12, 3),
(13, 3),
(14, 3),
(15, 3),
(16, 3),
(17, 3),
(18, 3);

-- --------------------------------------------------------

--
-- Table structure for table `unpaid_students`
--

CREATE TABLE `unpaid_students` (
  `unpaid_id` int NOT NULL,
  `enrollment_id` int NOT NULL,
  `student_id` int NOT NULL,
  `payment_id` int NOT NULL,
  `school_year` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `semester` tinyint NOT NULL,
  `amount_due` decimal(10,2) NOT NULL,
  `downpayment` decimal(10,2) NOT NULL DEFAULT '0.00',
  `balance` decimal(10,2) NOT NULL,
  `due_date` date NOT NULL,
  `transferred_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `remarks` text COLLATE utf8mb4_general_ci,
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int NOT NULL,
  `staff_id` varchar(9) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `middle_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `phone_number` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `role_id` int NOT NULL,
  `status_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `staff_id`, `first_name`, `middle_name`, `last_name`, `email`, `phone_number`, `username`, `password`, `role_id`, `status_id`, `created_at`, `updated_at`, `last_login`) VALUES
(4, '2026-0002', 'Admin', NULL, 'User', 'admin@eduschool.local', NULL, 'admin', '$2y$10$X7r/FJQSDhHNGTjLFN35l.yaSL.s.3s3VY/G4Yaf9194g7bXBrd2i', 1, 1, '2026-06-30 15:43:43', '2026-07-16 10:25:28', '2026-07-16 18:25:28'),
(5, '2026-0003', 'Frankyz', 'AHzee', 'Malbog', 'frankyz@example.com', '09987675364', 'azii', '$2y$10$A/9kCBCpDcwQ78UrEWOjlOt7p1I3.0uk2NmNIeQ94wFBG4oNWJLxG', 4, 1, '2026-07-03 01:25:13', '2026-07-03 01:25:13', NULL),
(6, '2026-0004', 'Ann', 'Eme', 'Lubo', 'ann@example.com', '09987675456', 'tintin', '$2y$10$XTLI5bOdpyVi6iZIpYZVmO/.dq0VKn2GSnOk2EcNgVAXqEEwLTNvy', 2, 1, '2026-07-03 01:25:54', '2026-07-03 01:25:54', NULL),
(7, '2026-0005', 'Ian', 'Arvie', 'Reyes', 'ian@example.com', '09928786456', 'ian', '$2y$10$yH0vCSL5g2yzKG7dcPYage7YGhgSjntaxahswj4Gvibkzg2nXGZaG', 3, 1, '2026-07-03 01:26:29', '2026-07-03 01:26:29', NULL),
(15, '2026-9901', 'Katrina', NULL, 'Villanueva', 'head.registrar@eduschool.local', NULL, 'headregistrar', '$2b$10$tCi5R/ht3jC9nXgpxWnfvOd7fXTZoSfZsJM/3qzRqC9ojsYbRjeAO', 5, 1, '2026-07-12 23:26:13', '2026-07-16 10:33:51', '2026-07-16 18:33:51'),
(16, '2026-9902', 'Mark', NULL, 'Delos Santos', 'registrar.staff@eduschool.local', NULL, 'registrarstaff', '$2b$10$tCi5R/ht3jC9nXgpxWnfvOd7fXTZoSfZsJM/3qzRqC9ojsYbRjeAO', 6, 1, '2026-07-12 23:26:13', '2026-07-16 10:25:58', '2026-07-16 18:25:58'),
(17, '2026-9903', 'Lino', 'Gonzalvo', 'De La Cruz', 'lino@example.com', '09987675399', 'linux', '$2y$10$eB4c07u2UAxfjd/8Lv.kCusisbPD6K5I6//acctyZbhUhZHw9auUG', 6, 1, '2026-07-15 16:49:00', '2026-07-15 16:50:59', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applicants`
--
ALTER TABLE `applicants`
  ADD PRIMARY KEY (`applicant_id`),
  ADD UNIQUE KEY `uq_applicants_reference_id` (`reference_id`),
  ADD KEY `fk_applicant_type` (`applicant_type_id`),
  ADD KEY `fk_applicant_verified_by` (`id_verified_by`),
  ADD KEY `fk_applicants_course` (`course_id`);

--
-- Indexes for table `applicant_documents`
--
ALTER TABLE `applicant_documents`
  ADD PRIMARY KEY (`document_id`),
  ADD KEY `fk_document_applicant` (`applicant_id`),
  ADD KEY `fk_document_verified_by` (`verified_by`);

--
-- Indexes for table `applicant_school_history`
--
ALTER TABLE `applicant_school_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `fk_history_applicant` (`applicant_id`);

--
-- Indexes for table `applicant_subject_credit`
--
ALTER TABLE `applicant_subject_credit`
  ADD PRIMARY KEY (`credit_id`),
  ADD UNIQUE KEY `uq_applicant_subject` (`applicant_id`,`subject_id`),
  ADD KEY `fk_credit_subject` (`subject_id`),
  ADD KEY `fk_credit_staff` (`credited_by`);

--
-- Indexes for table `course`
--
ALTER TABLE `course`
  ADD PRIMARY KEY (`course_id`),
  ADD UNIQUE KEY `course_code` (`course_code`,`course_name`);

--
-- Indexes for table `department`
--
ALTER TABLE `department`
  ADD PRIMARY KEY (`department_id`),
  ADD UNIQUE KEY `department_code` (`department_code`);

--
-- Indexes for table `enrollment`
--
ALTER TABLE `enrollment`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD UNIQUE KEY `uq_enrollment` (`student_id`,`school_year`,`semester`),
  ADD KEY `fk_enrollment_type` (`type_id`),
  ADD KEY `fk_enrollment_section` (`section_id`);

--
-- Indexes for table `enrollment_subject`
--
ALTER TABLE `enrollment_subject`
  ADD PRIMARY KEY (`enrollment_subject_id`),
  ADD UNIQUE KEY `uq_es` (`enrollment_id`,`subject_id`),
  ADD KEY `fk_es_subject` (`subject_id`),
  ADD KEY `fk_enrollment_subject_schedule` (`schedule_id`);

--
-- Indexes for table `fee_schedule`
--
ALTER TABLE `fee_schedule`
  ADD PRIMARY KEY (`fee_schedule_id`),
  ADD UNIQUE KEY `uniq_year_sy` (`year_level`,`school_year`);

--
-- Indexes for table `fee_schedule_item`
--
ALTER TABLE `fee_schedule_item`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `fee_schedule_id` (`fee_schedule_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `fk_msg_sender` (`sender_id`),
  ADD KEY `fk_msg_recipient` (`recipient_id`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`payment_id`),
  ADD UNIQUE KEY `uq_payment_enrollment` (`enrollment_id`),
  ADD KEY `fk_payment_received_by` (`received_by`);

--
-- Indexes for table `payment_breakdown`
--
ALTER TABLE `payment_breakdown`
  ADD PRIMARY KEY (`breakdown_id`),
  ADD KEY `payment_id` (`payment_id`);

--
-- Indexes for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `payment_id` (`payment_id`),
  ADD KEY `received_by` (`received_by`);

--
-- Indexes for table `professor`
--
ALTER TABLE `professor`
  ADD PRIMARY KEY (`professor_id`),
  ADD KEY `fk_professor_department` (`department_id`),
  ADD KEY `fk_professor_status` (`status_id`);

--
-- Indexes for table `readmission_request`
--
ALTER TABLE `readmission_request`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `fk_readmit_student` (`student_id`),
  ADD KEY `fk_readmit_course` (`new_course_id`),
  ADD KEY `fk_readmit_processed_by` (`processed_by`),
  ADD KEY `fk_readmit_reviewed_by` (`reviewed_by`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `room`
--
ALTER TABLE `room`
  ADD PRIMARY KEY (`room_id`),
  ADD UNIQUE KEY `room_name` (`room_name`);

--
-- Indexes for table `schedule`
--
ALTER TABLE `schedule`
  ADD PRIMARY KEY (`schedule_id`),
  ADD UNIQUE KEY `uq_room_schedule` (`room_id`,`day`,`time_start`,`school_year`,`semester`),
  ADD UNIQUE KEY `uq_professor_schedule` (`professor_id`,`day`,`time_start`,`school_year`,`semester`),
  ADD UNIQUE KEY `uq_section_schedule` (`section_id`,`day`,`time_start`,`school_year`,`semester`),
  ADD KEY `fk_schedule_subject` (`subject_id`);

--
-- Indexes for table `section`
--
ALTER TABLE `section`
  ADD PRIMARY KEY (`section_id`),
  ADD UNIQUE KEY `section_name` (`section_name`),
  ADD KEY `fk_section_course` (`course_id`);

--
-- Indexes for table `statuses`
--
ALTER TABLE `statuses`
  ADD PRIMARY KEY (`status_id`),
  ADD UNIQUE KEY `status_name` (`status_name`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `uq_student_student_no` (`student_no`),
  ADD UNIQUE KEY `contact_number` (`contact_number`,`email`),
  ADD KEY `fk_student_section` (`section_id`),
  ADD KEY `fk_student_type` (`type_id`),
  ADD KEY `fk_student_applicant` (`applicant_id`);

--
-- Indexes for table `student_type`
--
ALTER TABLE `student_type`
  ADD PRIMARY KEY (`type_id`),
  ADD UNIQUE KEY `type_name` (`type_name`);

--
-- Indexes for table `subject`
--
ALTER TABLE `subject`
  ADD PRIMARY KEY (`subject_id`),
  ADD UNIQUE KEY `subject_code` (`subject_code`),
  ADD UNIQUE KEY `subject_name` (`subject_name`),
  ADD KEY `fk_subject_category` (`category_id`),
  ADD KEY `fk_subject_prereq` (`prereq_id`),
  ADD KEY `fk_subject_course` (`course_id`);

--
-- Indexes for table `subject_category`
--
ALTER TABLE `subject_category`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `subject_course`
--
ALTER TABLE `subject_course`
  ADD PRIMARY KEY (`subject_id`,`course_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `unpaid_students`
--
ALTER TABLE `unpaid_students`
  ADD PRIMARY KEY (`unpaid_id`),
  ADD KEY `fk_unpaid_enrollment` (`enrollment_id`),
  ADD KEY `fk_unpaid_student` (`student_id`),
  ADD KEY `fk_unpaid_payment` (`payment_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `phone_number` (`phone_number`),
  ADD UNIQUE KEY `uq_users_staff_id` (`staff_id`),
  ADD KEY `fk_user_role` (`role_id`),
  ADD KEY `fk_user_status` (`status_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applicants`
--
ALTER TABLE `applicants`
  MODIFY `applicant_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `applicant_documents`
--
ALTER TABLE `applicant_documents`
  MODIFY `document_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `applicant_school_history`
--
ALTER TABLE `applicant_school_history`
  MODIFY `history_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `applicant_subject_credit`
--
ALTER TABLE `applicant_subject_credit`
  MODIFY `credit_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course`
--
ALTER TABLE `course`
  MODIFY `course_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `department`
--
ALTER TABLE `department`
  MODIFY `department_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `enrollment`
--
ALTER TABLE `enrollment`
  MODIFY `enrollment_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `enrollment_subject`
--
ALTER TABLE `enrollment_subject`
  MODIFY `enrollment_subject_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `fee_schedule`
--
ALTER TABLE `fee_schedule`
  MODIFY `fee_schedule_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `fee_schedule_item`
--
ALTER TABLE `fee_schedule_item`
  MODIFY `item_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `payment_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `payment_breakdown`
--
ALTER TABLE `payment_breakdown`
  MODIFY `breakdown_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  MODIFY `transaction_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `professor`
--
ALTER TABLE `professor`
  MODIFY `professor_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `readmission_request`
--
ALTER TABLE `readmission_request`
  MODIFY `request_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `room`
--
ALTER TABLE `room`
  MODIFY `room_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `schedule`
--
ALTER TABLE `schedule`
  MODIFY `schedule_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `section`
--
ALTER TABLE `section`
  MODIFY `section_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `statuses`
--
ALTER TABLE `statuses`
  MODIFY `status_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `student_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `student_type`
--
ALTER TABLE `student_type`
  MODIFY `type_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `subject`
--
ALTER TABLE `subject`
  MODIFY `subject_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `subject_category`
--
ALTER TABLE `subject_category`
  MODIFY `category_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `unpaid_students`
--
ALTER TABLE `unpaid_students`
  MODIFY `unpaid_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applicants`
--
ALTER TABLE `applicants`
  ADD CONSTRAINT `fk_applicant_type` FOREIGN KEY (`applicant_type_id`) REFERENCES `student_type` (`type_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_applicant_verified_by` FOREIGN KEY (`id_verified_by`) REFERENCES `users` (`staff_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_applicants_course` FOREIGN KEY (`course_id`) REFERENCES `course` (`course_id`);

--
-- Constraints for table `applicant_documents`
--
ALTER TABLE `applicant_documents`
  ADD CONSTRAINT `fk_document_applicant` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`applicant_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_document_verified_by` FOREIGN KEY (`verified_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `applicant_school_history`
--
ALTER TABLE `applicant_school_history`
  ADD CONSTRAINT `fk_history_applicant` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`applicant_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `applicant_subject_credit`
--
ALTER TABLE `applicant_subject_credit`
  ADD CONSTRAINT `fk_credit_applicant` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`applicant_id`),
  ADD CONSTRAINT `fk_credit_staff` FOREIGN KEY (`credited_by`) REFERENCES `users` (`staff_id`),
  ADD CONSTRAINT `fk_credit_subject` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`subject_id`);

--
-- Constraints for table `enrollment`
--
ALTER TABLE `enrollment`
  ADD CONSTRAINT `fk_enroll_applicant` FOREIGN KEY (`student_id`) REFERENCES `applicants` (`applicant_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_enrollment_section` FOREIGN KEY (`section_id`) REFERENCES `section` (`section_id`),
  ADD CONSTRAINT `fk_enrollment_type` FOREIGN KEY (`type_id`) REFERENCES `student_type` (`type_id`) ON UPDATE CASCADE;

--
-- Constraints for table `enrollment_subject`
--
ALTER TABLE `enrollment_subject`
  ADD CONSTRAINT `fk_enrollment_subject_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `schedule` (`schedule_id`),
  ADD CONSTRAINT `fk_es_enrollment` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollment` (`enrollment_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_es_subject` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`subject_id`) ON UPDATE CASCADE;

--
-- Constraints for table `fee_schedule_item`
--
ALTER TABLE `fee_schedule_item`
  ADD CONSTRAINT `fee_schedule_item_ibfk_1` FOREIGN KEY (`fee_schedule_id`) REFERENCES `fee_schedule` (`fee_schedule_id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_msg_recipient` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_msg_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `fk_payment_enrollment` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollment` (`enrollment_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_payment_received_by` FOREIGN KEY (`received_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `payment_breakdown`
--
ALTER TABLE `payment_breakdown`
  ADD CONSTRAINT `payment_breakdown_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payment` (`payment_id`) ON DELETE CASCADE;

--
-- Constraints for table `professor`
--
ALTER TABLE `professor`
  ADD CONSTRAINT `fk_professor_department` FOREIGN KEY (`department_id`) REFERENCES `department` (`department_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_professor_status` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`status_id`) ON UPDATE CASCADE;

--
-- Constraints for table `readmission_request`
--
ALTER TABLE `readmission_request`
  ADD CONSTRAINT `fk_readmit_course` FOREIGN KEY (`new_course_id`) REFERENCES `course` (`course_id`),
  ADD CONSTRAINT `fk_readmit_processed_by` FOREIGN KEY (`processed_by`) REFERENCES `users` (`staff_id`),
  ADD CONSTRAINT `fk_readmit_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`staff_id`),
  ADD CONSTRAINT `fk_readmit_student` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`);

--
-- Constraints for table `schedule`
--
ALTER TABLE `schedule`
  ADD CONSTRAINT `fk_schedule_professor` FOREIGN KEY (`professor_id`) REFERENCES `professor` (`professor_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_schedule_room` FOREIGN KEY (`room_id`) REFERENCES `room` (`room_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_schedule_section` FOREIGN KEY (`section_id`) REFERENCES `section` (`section_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_schedule_subject` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`subject_id`) ON UPDATE CASCADE;

--
-- Constraints for table `section`
--
ALTER TABLE `section`
  ADD CONSTRAINT `fk_section_course` FOREIGN KEY (`course_id`) REFERENCES `course` (`course_id`) ON UPDATE CASCADE;

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `fk_student_applicant` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`applicant_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_student_section` FOREIGN KEY (`section_id`) REFERENCES `section` (`section_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_student_type` FOREIGN KEY (`type_id`) REFERENCES `student_type` (`type_id`) ON UPDATE CASCADE;

--
-- Constraints for table `subject`
--
ALTER TABLE `subject`
  ADD CONSTRAINT `fk_subject_category` FOREIGN KEY (`category_id`) REFERENCES `subject_category` (`category_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_subject_course` FOREIGN KEY (`course_id`) REFERENCES `course` (`course_id`),
  ADD CONSTRAINT `fk_subject_prereq` FOREIGN KEY (`prereq_id`) REFERENCES `subject` (`subject_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `subject_course`
--
ALTER TABLE `subject_course`
  ADD CONSTRAINT `subject_course_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`subject_id`),
  ADD CONSTRAINT `subject_course_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `course` (`course_id`);

--
-- Constraints for table `unpaid_students`
--
ALTER TABLE `unpaid_students`
  ADD CONSTRAINT `fk_unpaid_enrollment` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollment` (`enrollment_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_unpaid_payment` FOREIGN KEY (`payment_id`) REFERENCES `payment` (`payment_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_unpaid_student` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_status` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`status_id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
