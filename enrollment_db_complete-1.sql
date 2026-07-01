-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 01, 2026 at 12:44 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `enrollment_db_complete`
--

-- --------------------------------------------------------

--
-- Table structure for table `admission`
--

CREATE TABLE `admission` (
  `admission_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `applicant_id` int(11) DEFAULT NULL,
  `school_year` varchar(10) NOT NULL,
  `semester` tinyint(4) NOT NULL,
  `year_level` tinyint(4) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Pending',
  `remarks` text DEFAULT NULL,
  `admitted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `type_id` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applicants`
--

CREATE TABLE `applicants` (
  `applicant_id` int(11) NOT NULL,
  `student_id` varchar(10) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `birth_date` date NOT NULL,
  `sex` varchar(10) NOT NULL,
  `civil_status` varchar(20) NOT NULL DEFAULT 'Single',
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `home_address` text NOT NULL,
  `guardian_name` varchar(100) NOT NULL,
  `guardian_relationship` varchar(50) NOT NULL,
  `guardian_contact` varchar(20) NOT NULL,
  `guardian_id_type` varchar(50) DEFAULT NULL,
  `guardian_id_number` varchar(50) DEFAULT NULL,
  `id_verified_by` int(11) DEFAULT NULL,
  `program` varchar(50) NOT NULL DEFAULT 'BSIT',
  `year_level` tinyint(4) NOT NULL DEFAULT 1,
  `start_term` varchar(20) NOT NULL,
  `applicant_type` varchar(30) DEFAULT NULL,
  `applicant_type_id` int(11) NOT NULL DEFAULT 1,
  `status` varchar(20) NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applicants`
--

INSERT INTO `applicants` (`applicant_id`, `student_id`, `last_name`, `first_name`, `middle_name`, `birth_date`, `sex`, `civil_status`, `contact_number`, `email`, `home_address`, `guardian_name`, `guardian_relationship`, `guardian_contact`, `guardian_id_type`, `guardian_id_number`, `id_verified_by`, `program`, `year_level`, `start_term`, `applicant_type`, `applicant_type_id`, `status`, `created_at`, `updated_at`) VALUES
(1, '2026-00001', 'Bulado', 'Waffa Bea', 'Villorente', '2006-11-25', 'Female', 'Single', '09994850299', 'waffabulado@example.com', 'Langkaan 2, Dasmarinas City, Cavite', 'Marife Bulado', 'Mother', '09260515705', 'Philippine National ID', '234567', 2, 'BS Information Technology', 1, '2026-2027', 'Freshman', 1, 'Pending', '2026-06-30 10:05:42', '2026-06-30 10:05:42'),
(2, '2026-00002', 'Dela Cruz', 'Lino Vincent', 'Gonzalvo', '2006-07-25', 'Male', 'Single', '09994850299', 'lino@example.com', 'Block 3, Lot 2, Canberra St., Crescent Hills, dasmarinas City, Cavite', 'Florence Gonzalvo', 'Mother', '09260515705', 'Philippine National ID', '343354', 2, 'BS Information Technology', 1, '2026-2027', 'Freshman', 1, 'Pending', '2026-06-30 12:17:50', '2026-06-30 12:17:50'),
(3, '2026-00003', 'Verguela', 'Rizen Angelica', 'Rada', '2006-06-17', 'Female', 'Single', '09958766767', 'rizen@example.com', 'Block 17, Lot 3, Seville St., Sunny Crest, Salitran 2, Dasmarinas City, Cavite', 'Ronni Verguela', 'Father', '09987864576', 'Philippine National ID', '2342342', 2, 'BS Information Technology', 1, '2026-2027', 'Freshman', 1, 'Pending', '2026-06-30 13:15:04', '2026-06-30 13:15:04'),
(4, '2026-00004', 'Reyes', 'Ian Arvie', 'Amora', '2010-01-10', 'Male', 'Single', '09123456666', '', '123Street', 'JB Suarez', 'Tito', '09123444444', 'Driver&#039;s License', '191919', 2, 'BS Information Technology', 1, '2024-2025', 'Freshman', 1, 'Pending', '2026-06-30 14:28:18', '2026-06-30 14:28:18'),
(5, '2026-00005', 'bean', 'bean', 'bean', '2010-01-01', 'Male', 'Single', '09111111111', '', 'Streetahahah', 'BNBNB', 'MOther', '09122222222', 'Driver&#039;s License', '233232', 2, 'BS Information Technology', 1, '2026-2027', 'Freshman', 1, 'Pending', '2026-06-30 15:51:14', '2026-06-30 15:51:14'),
(6, '2026-00006', 'Ruazol', 'Holger', 'Gonzalvo', '2006-06-08', 'Male', 'Single', '09224546758', 'holger@example.com', 'Block 3, Lot 2, Canberra St., Crescent Hills, Dasmarinas City, Cavite', 'Bobby Ruazol', 'Father', '09057673488', 'Philippine National ID', '987465', 2, 'BS Information Technology', 1, '2026-2027', 'Freshman', 1, 'Pending', '2026-07-01 01:08:18', '2026-07-01 01:08:18'),
(7, '2026-00007', 'frank', 'frank', 'frank', '2010-10-01', 'Male', 'Single', '09333333333', '', 'jb street', 'ben', 'mother', '09222222222', 'Voter&#039;s ID', '101010', 2, 'BS Information Technology', 1, '2026-2027', 'Freshman', 1, 'Pending', '2026-07-01 01:49:23', '2026-07-01 01:49:23');

-- --------------------------------------------------------

--
-- Table structure for table `applicant_documents`
--

CREATE TABLE `applicant_documents` (
  `document_id` int(11) NOT NULL,
  `applicant_id` int(11) NOT NULL,
  `document_name` varchar(100) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Pending',
  `verified_by` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applicant_documents`
--

INSERT INTO `applicant_documents` (`document_id`, `applicant_id`, `document_name`, `file_path`, `status`, `verified_by`, `uploaded_at`) VALUES
(10, 1, 'Form 137 / SHS Card', NULL, 'Pending', NULL, '2026-06-30 10:05:42'),
(11, 1, 'Certificate of Good Moral', NULL, 'Pending', NULL, '2026-06-30 10:05:42'),
(12, 1, 'Birth Certificate (PSA)', NULL, 'Pending', NULL, '2026-06-30 10:05:42'),
(13, 1, '2x2 ID Photos', NULL, 'Pending', NULL, '2026-06-30 10:05:42'),
(14, 2, 'Form 137 / SHS Card', NULL, 'Pending', NULL, '2026-06-30 12:17:50'),
(15, 2, 'Certificate of Good Moral', NULL, 'Pending', NULL, '2026-06-30 12:17:50'),
(16, 2, 'Birth Certificate (PSA)', NULL, 'Pending', NULL, '2026-06-30 12:17:50'),
(17, 2, '2x2 ID Photos', NULL, 'Pending', NULL, '2026-06-30 12:17:50'),
(18, 3, 'Form 137 / SHS Card', NULL, 'Pending', NULL, '2026-06-30 13:15:04'),
(19, 3, 'Certificate of Good Moral', NULL, 'Pending', NULL, '2026-06-30 13:15:04'),
(20, 3, 'Birth Certificate (PSA)', NULL, 'Pending', NULL, '2026-06-30 13:15:04'),
(21, 3, '2x2 ID Photos', NULL, 'Pending', NULL, '2026-06-30 13:15:04'),
(22, 4, 'Form 137 / SHS Card', NULL, 'Pending', NULL, '2026-06-30 14:28:18'),
(23, 4, 'Certificate of Good Moral', NULL, 'Pending', NULL, '2026-06-30 14:28:18'),
(24, 4, 'Birth Certificate (PSA)', NULL, 'Pending', NULL, '2026-06-30 14:28:18'),
(25, 4, '2x2 ID Photos', NULL, 'Pending', NULL, '2026-06-30 14:28:18'),
(26, 5, 'Form 137 / SHS Card', NULL, 'Pending', NULL, '2026-06-30 15:51:14'),
(27, 5, 'Certificate of Good Moral', NULL, 'Pending', NULL, '2026-06-30 15:51:14'),
(28, 5, 'Birth Certificate (PSA)', NULL, 'Pending', NULL, '2026-06-30 15:51:14'),
(29, 5, '2x2 ID Photos', NULL, 'Pending', NULL, '2026-06-30 15:51:14'),
(30, 6, 'Form 137 / SHS Card', NULL, 'Pending', NULL, '2026-07-01 01:08:18'),
(31, 6, 'Certificate of Good Moral', NULL, 'Pending', NULL, '2026-07-01 01:08:18'),
(32, 6, 'Birth Certificate (PSA)', NULL, 'Pending', NULL, '2026-07-01 01:08:18'),
(33, 6, '2x2 ID Photos', NULL, 'Pending', NULL, '2026-07-01 01:08:18'),
(34, 7, 'Form 137 / SHS Card', NULL, 'Pending', NULL, '2026-07-01 01:49:23'),
(35, 7, 'Certificate of Good Moral', NULL, 'Pending', NULL, '2026-07-01 01:49:23'),
(36, 7, 'Birth Certificate (PSA)', NULL, 'Pending', NULL, '2026-07-01 01:49:23'),
(37, 7, '2x2 ID Photos', NULL, 'Pending', NULL, '2026-07-01 01:49:23');

-- --------------------------------------------------------

--
-- Table structure for table `applicant_school_history`
--

CREATE TABLE `applicant_school_history` (
  `history_id` int(11) NOT NULL,
  `applicant_id` int(11) NOT NULL,
  `school_name` varchar(150) NOT NULL,
  `school_address` varchar(255) NOT NULL,
  `school_year` varchar(20) NOT NULL,
  `school_strand` varchar(50) DEFAULT NULL,
  `school_gpa` decimal(4,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applicant_school_history`
--

INSERT INTO `applicant_school_history` (`history_id`, `applicant_id`, `school_name`, `school_address`, `school_year`, `school_strand`, `school_gpa`) VALUES
(3, 1, 'NCST Senior High', 'Amafel bldg., Aguinaldo Highway, Dasmarinas City, Cavite', '2025', 'HUMSS', 97.00),
(4, 2, 'Great Mercy Academy of Cavite Inc.', 'Salitran 2, Dasmarinas City, Cavite', '2025', 'STEM', 98.00),
(5, 3, 'GMACI', 'Salitran 2', '2025', 'STEM', 99.00),
(6, 4, 'ERCIHS', 'Poinsettia Street', '2024', 'STEM', 99.00),
(7, 5, 'BNB', 'NBN', '2025', 'STEM', 99.00),
(8, 6, 'GMACI SHS', 'Salitran 2, Dasmarinas City, Cavite', '2025', 'STEM', 99.00),
(9, 7, 'jb uni', 'jb street', '2025', 'stem', 99.00);

-- --------------------------------------------------------

--
-- Table structure for table `course`
--

CREATE TABLE `course` (
  `course_id` int(11) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `total_units` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course`
--

INSERT INTO `course` (`course_id`, `course_code`, `course_name`, `total_units`) VALUES
(1, 'BSIT', 'Batchelor of Science in Information Technology', 0);

-- --------------------------------------------------------

--
-- Table structure for table `department`
--

CREATE TABLE `department` (
  `department_id` int(11) NOT NULL,
  `department_code` varchar(20) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `department`
--

INSERT INTO `department` (`department_id`, `department_code`, `department_name`, `created_at`) VALUES
(1, 'CSD', 'Computes of Studies Department', '2026-06-28 07:06:53'),
(2, 'GEN ED', 'General Education Department', '2026-06-28 07:06:53'),
(3, 'NSTP', 'National Service Training Program', '2026-06-28 07:06:53'),
(4, 'PE', 'Physical Education Department', '2026-06-28 07:06:53');

-- --------------------------------------------------------

--
-- Table structure for table `enrollment`
--

CREATE TABLE `enrollment` (
  `enrollment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `admission_id` int(11) DEFAULT NULL,
  `school_year` varchar(10) NOT NULL,
  `semester` tinyint(4) NOT NULL,
  `year_level` tinyint(4) NOT NULL,
  `section_id` int(11) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Enrolled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `type_id` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollment`
--

INSERT INTO `enrollment` (`enrollment_id`, `student_id`, `admission_id`, `school_year`, `semester`, `year_level`, `section_id`, `status`, `created_at`, `updated_at`, `type_id`) VALUES
(1, 1, NULL, '2025-2026', 1, 1, 1, 'Enrolled', '2026-06-30 11:15:39', '2026-06-30 11:15:39', 1),
(2, 2, NULL, '2025-2026', 2, 1, NULL, 'Pending Payment', '2026-06-30 12:18:22', '2026-06-30 12:18:22', 1),
(3, 3, NULL, '2025-2026', 1, 1, 1, 'Pending Payment', '2026-06-30 13:15:32', '2026-06-30 13:15:32', 1),
(4, 1, NULL, '2025-2026', 2, 1, NULL, 'Pending Payment', '2026-06-30 14:32:28', '2026-06-30 14:32:28', 1),
(5, 5, NULL, '2025-2026', 2, 1, NULL, 'Pending Payment', '2026-06-30 15:51:43', '2026-06-30 15:51:43', 1),
(6, 4, NULL, '2026-2027', 1, 1, 1, 'Pending Payment', '2026-07-01 00:49:20', '2026-07-01 00:49:20', 1),
(7, 6, NULL, '2026-2027', 1, 1, 1, 'Pending Payment', '2026-07-01 01:09:20', '2026-07-01 01:09:20', 1),
(8, 7, NULL, '2026-2027', 1, 1, 1, 'Pending Payment', '2026-07-01 01:50:56', '2026-07-01 01:50:56', 1),
(9, 5, NULL, '2026-2027', 1, 1, 1, 'Pending Payment', '2026-07-01 02:06:38', '2026-07-01 02:06:38', 1),
(10, 1, NULL, '2026-2027', 1, 1, 1, 'Pending Payment', '2026-07-01 02:17:02', '2026-07-01 02:17:02', 1);

-- --------------------------------------------------------

--
-- Table structure for table `enrollment_subject`
--

CREATE TABLE `enrollment_subject` (
  `enrollment_subject_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Enrolled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollment_subject`
--

INSERT INTO `enrollment_subject` (`enrollment_subject_id`, `enrollment_id`, `subject_id`, `status`, `created_at`) VALUES
(1, 1, 1, 'Enrolled', '2026-06-30 11:15:39'),
(2, 1, 2, 'Enrolled', '2026-06-30 11:15:39'),
(3, 1, 4, 'Enrolled', '2026-06-30 11:15:39'),
(4, 1, 5, 'Enrolled', '2026-06-30 11:15:39'),
(5, 1, 9, 'Enrolled', '2026-06-30 11:15:39'),
(6, 1, 7, 'Enrolled', '2026-06-30 11:15:39'),
(7, 1, 8, 'Enrolled', '2026-06-30 11:15:39'),
(8, 2, 3, 'Enrolled', '2026-06-30 12:18:22'),
(9, 3, 1, 'Enrolled', '2026-06-30 13:15:32'),
(10, 3, 2, 'Enrolled', '2026-06-30 13:15:32'),
(11, 3, 4, 'Enrolled', '2026-06-30 13:15:32'),
(12, 3, 5, 'Enrolled', '2026-06-30 13:15:32'),
(13, 3, 9, 'Enrolled', '2026-06-30 13:15:32'),
(14, 3, 7, 'Enrolled', '2026-06-30 13:15:32'),
(15, 3, 8, 'Enrolled', '2026-06-30 13:15:32'),
(16, 4, 3, 'Enrolled', '2026-06-30 14:32:28'),
(17, 5, 3, 'Enrolled', '2026-06-30 15:51:43'),
(18, 6, 1, 'Enrolled', '2026-07-01 00:49:20'),
(19, 6, 2, 'Enrolled', '2026-07-01 00:49:20'),
(20, 6, 4, 'Enrolled', '2026-07-01 00:49:20'),
(21, 6, 5, 'Enrolled', '2026-07-01 00:49:20'),
(22, 6, 7, 'Enrolled', '2026-07-01 00:49:20'),
(23, 6, 8, 'Enrolled', '2026-07-01 00:49:20'),
(24, 6, 9, 'Enrolled', '2026-07-01 00:49:20'),
(25, 7, 1, 'Enrolled', '2026-07-01 01:09:20'),
(26, 7, 2, 'Enrolled', '2026-07-01 01:09:20'),
(27, 7, 4, 'Enrolled', '2026-07-01 01:09:20'),
(28, 7, 5, 'Enrolled', '2026-07-01 01:09:20'),
(29, 7, 7, 'Enrolled', '2026-07-01 01:09:20'),
(30, 7, 8, 'Enrolled', '2026-07-01 01:09:20'),
(31, 7, 9, 'Enrolled', '2026-07-01 01:09:20'),
(32, 8, 1, 'Enrolled', '2026-07-01 01:50:56'),
(33, 8, 2, 'Enrolled', '2026-07-01 01:50:56'),
(34, 8, 4, 'Enrolled', '2026-07-01 01:50:56'),
(35, 8, 5, 'Enrolled', '2026-07-01 01:50:56'),
(36, 8, 7, 'Enrolled', '2026-07-01 01:50:56'),
(37, 8, 8, 'Enrolled', '2026-07-01 01:50:56'),
(38, 8, 9, 'Enrolled', '2026-07-01 01:50:56'),
(39, 9, 1, 'Enrolled', '2026-07-01 02:06:38'),
(40, 9, 2, 'Enrolled', '2026-07-01 02:06:38'),
(41, 9, 4, 'Enrolled', '2026-07-01 02:06:38'),
(42, 9, 5, 'Enrolled', '2026-07-01 02:06:38'),
(43, 9, 7, 'Enrolled', '2026-07-01 02:06:38'),
(44, 9, 8, 'Enrolled', '2026-07-01 02:06:38'),
(45, 9, 9, 'Enrolled', '2026-07-01 02:06:38'),
(46, 10, 1, 'Enrolled', '2026-07-01 02:17:02'),
(47, 10, 2, 'Enrolled', '2026-07-01 02:17:02'),
(48, 10, 4, 'Enrolled', '2026-07-01 02:17:02'),
(49, 10, 5, 'Enrolled', '2026-07-01 02:17:02'),
(50, 10, 7, 'Enrolled', '2026-07-01 02:17:02'),
(51, 10, 8, 'Enrolled', '2026-07-01 02:17:02'),
(52, 10, 9, 'Enrolled', '2026-07-01 02:17:02');

-- --------------------------------------------------------

--
-- Table structure for table `fee_schedule`
--

CREATE TABLE `fee_schedule` (
  `fee_schedule_id` int(11) NOT NULL,
  `year_level` int(11) NOT NULL,
  `school_year` varchar(9) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fee_schedule`
--

INSERT INTO `fee_schedule` (`fee_schedule_id`, `year_level`, `school_year`, `total_amount`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, '2026-2027', 23500.00, 1, '2026-07-01 01:04:33', '2026-07-01 01:04:33'),
(2, 2, '2026-2027', 25500.00, 1, '2026-07-01 01:04:33', '2026-07-01 01:04:33'),
(3, 3, '2026-2027', 26500.00, 1, '2026-07-01 01:04:33', '2026-07-01 01:04:33'),
(4, 4, '2026-2027', 26500.00, 1, '2026-07-01 01:04:33', '2026-07-01 01:04:33');

-- --------------------------------------------------------

--
-- Table structure for table `fee_schedule_item`
--

CREATE TABLE `fee_schedule_item` (
  `item_id` int(11) NOT NULL,
  `fee_schedule_id` int(11) NOT NULL,
  `label` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fee_schedule_item`
--

INSERT INTO `fee_schedule_item` (`item_id`, `fee_schedule_id`, `label`, `amount`, `sort_order`) VALUES
(1, 1, 'Tuition Fee', 15000.00, 1),
(2, 1, 'Laboratory Fee', 3500.00, 2),
(3, 1, 'Miscellaneous Fee', 2500.00, 3),
(4, 1, 'Library Fee', 1000.00, 4),
(5, 1, 'Athletic / Medical Fee', 1000.00, 5),
(6, 1, 'Registration / ID Fee', 500.00, 6),
(7, 2, 'Tuition Fee', 16000.00, 1),
(8, 2, 'Laboratory Fee', 4500.00, 2),
(9, 2, 'Miscellaneous Fee', 2500.00, 3),
(10, 2, 'Library Fee', 1000.00, 4),
(11, 2, 'Athletic / Medical Fee', 1000.00, 5),
(12, 2, 'Registration / ID Fee', 500.00, 6),
(13, 3, 'Tuition Fee', 16500.00, 1),
(14, 3, 'Laboratory Fee', 5000.00, 2),
(15, 3, 'Miscellaneous Fee', 2500.00, 3),
(16, 3, 'Library Fee', 1000.00, 4),
(17, 3, 'Athletic / Medical Fee', 1000.00, 5),
(18, 3, 'Registration / ID Fee', 500.00, 6),
(19, 4, 'Tuition Fee', 15000.00, 1),
(20, 4, 'Laboratory Fee', 2000.00, 2),
(21, 4, 'Miscellaneous Fee', 3000.00, 3),
(22, 4, 'Thesis / Practicum Fee', 4000.00, 4),
(23, 4, 'Library Fee', 1000.00, 5),
(24, 4, 'Athletic / Medical Fee', 1000.00, 6),
(25, 4, 'Registration / ID Fee', 500.00, 7);

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `payment_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `amount_due` decimal(10,2) NOT NULL,
  `downpayment` decimal(10,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(10,2) GENERATED ALWAYS AS (`amount_due` - `downpayment`) STORED,
  `due_date` date NOT NULL,
  `payment_status` varchar(20) NOT NULL DEFAULT 'Unpaid',
  `paid_at` timestamp NULL DEFAULT NULL,
  `received_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`payment_id`, `enrollment_id`, `amount_due`, `downpayment`, `due_date`, `payment_status`, `paid_at`, `received_by`, `created_at`, `updated_at`) VALUES
(1, 1, 23500.00, 13000.00, '2026-07-03', 'Down Payment Paid', '2026-06-30 13:07:40', 2, '2026-06-30 11:15:39', '2026-06-30 13:07:40'),
(2, 2, 23500.00, 1000.00, '2026-07-03', 'Down Payment Paid', '2026-06-30 13:09:25', 2, '2026-06-30 12:50:22', '2026-06-30 13:09:25'),
(3, 3, 23500.00, 3000.00, '2026-07-03', 'Down Payment Paid', '2026-06-30 13:16:48', 2, '2026-06-30 13:15:46', '2026-06-30 13:16:48'),
(4, 5, 1000.00, 1000.00, '2026-06-30', 'Fully Paid', '2026-06-30 15:55:02', 2, '2026-06-30 15:54:47', '2026-06-30 15:55:02'),
(5, 4, 23500.00, 0.00, '2026-07-03', 'Unpaid', NULL, NULL, '2026-07-01 01:06:20', '2026-07-01 01:06:20'),
(6, 6, 23500.00, 0.00, '2026-07-03', 'Unpaid', NULL, NULL, '2026-07-01 01:06:21', '2026-07-01 01:06:21'),
(7, 7, 23500.00, 0.00, '2026-07-04', 'Unpaid', NULL, NULL, '2026-07-01 01:09:20', '2026-07-01 01:09:20'),
(8, 8, 23500.00, 23500.00, '2026-07-04', 'Fully Paid', '2026-07-01 01:51:23', 2, '2026-07-01 01:50:56', '2026-07-01 01:51:23'),
(9, 9, 23500.00, 0.00, '2026-07-04', 'Unpaid', NULL, NULL, '2026-07-01 02:06:38', '2026-07-01 02:06:38'),
(10, 10, 23500.00, 23500.00, '2026-07-04', 'Fully Paid', '2026-07-01 02:17:35', 2, '2026-07-01 02:17:02', '2026-07-01 02:17:35');

-- --------------------------------------------------------

--
-- Table structure for table `payment_breakdown`
--

CREATE TABLE `payment_breakdown` (
  `breakdown_id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `label` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_breakdown`
--

INSERT INTO `payment_breakdown` (`breakdown_id`, `payment_id`, `label`, `amount`, `sort_order`) VALUES
(1, 7, 'Tuition Fee', 15000.00, 1),
(2, 7, 'Laboratory Fee', 3500.00, 2),
(3, 7, 'Miscellaneous Fee', 2500.00, 3),
(4, 7, 'Library Fee', 1000.00, 4),
(5, 7, 'Athletic / Medical Fee', 1000.00, 5),
(6, 7, 'Registration / ID Fee', 500.00, 6),
(7, 8, 'Tuition Fee', 15000.00, 1),
(8, 8, 'Laboratory Fee', 3500.00, 2),
(9, 8, 'Miscellaneous Fee', 2500.00, 3),
(10, 8, 'Library Fee', 1000.00, 4),
(11, 8, 'Athletic / Medical Fee', 1000.00, 5),
(12, 8, 'Registration / ID Fee', 500.00, 6),
(13, 9, 'Tuition Fee', 15000.00, 1),
(14, 9, 'Laboratory Fee', 3500.00, 2),
(15, 9, 'Miscellaneous Fee', 2500.00, 3),
(16, 9, 'Library Fee', 1000.00, 4),
(17, 9, 'Athletic / Medical Fee', 1000.00, 5),
(18, 9, 'Registration / ID Fee', 500.00, 6),
(19, 10, 'Tuition Fee', 15000.00, 1),
(20, 10, 'Laboratory Fee', 3500.00, 2),
(21, 10, 'Miscellaneous Fee', 2500.00, 3),
(22, 10, 'Library Fee', 1000.00, 4),
(23, 10, 'Athletic / Medical Fee', 1000.00, 5),
(24, 10, 'Registration / ID Fee', 500.00, 6);

-- --------------------------------------------------------

--
-- Table structure for table `payment_transactions`
--

CREATE TABLE `payment_transactions` (
  `transaction_id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `paid_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `remarks` varchar(255) DEFAULT NULL,
  `received_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_transactions`
--

INSERT INTO `payment_transactions` (`transaction_id`, `payment_id`, `amount`, `paid_at`, `remarks`, `received_by`) VALUES
(1, 4, 1000.00, '2026-06-30 15:55:02', '', 2),
(2, 8, 23500.00, '2026-07-01 01:51:23', '', 2),
(3, 10, 23500.00, '2026-07-01 02:17:35', '', 2);

-- --------------------------------------------------------

--
-- Table structure for table `professor`
--

CREATE TABLE `professor` (
  `professor_id` int(11) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `department_id` int(11) NOT NULL,
  `status_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `professor`
--

INSERT INTO `professor` (`professor_id`, `last_name`, `first_name`, `middle_name`, `department_id`, `status_id`, `created_at`, `updated_at`) VALUES
(1, 'Santos', 'John', NULL, 1, 1, '2026-06-30 11:14:33', '2026-06-30 11:14:33'),
(2, 'Reyes', 'Maria', NULL, 2, 1, '2026-06-30 11:14:33', '2026-06-30 11:14:33'),
(3, 'Garcia', 'Anthony', NULL, 1, 1, '2026-06-30 11:14:33', '2026-06-30 11:14:33'),
(4, 'Dela Cruz', 'Louise', NULL, 4, 1, '2026-06-30 11:14:33', '2026-06-30 11:14:33');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`, `created_at`) VALUES
(1, 'Admin', '2026-06-28 07:00:09'),
(2, 'Admission', '2026-06-28 07:00:09'),
(3, 'Staff', '2026-06-28 07:00:09');

-- --------------------------------------------------------

--
-- Table structure for table `room`
--

CREATE TABLE `room` (
  `room_id` int(11) NOT NULL,
  `room_name` varchar(50) NOT NULL,
  `room_type` varchar(20) NOT NULL DEFAULT 'Lecture',
  `capacity` int(11) NOT NULL DEFAULT 40,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room`
--

INSERT INTO `room` (`room_id`, `room_name`, `room_type`, `capacity`, `created_at`) VALUES
(1, 'Computer Laboratory 1', 'Lecture', 40, '2026-06-30 11:04:03'),
(2, 'Room 301', 'Lecture', 40, '2026-06-30 11:04:03'),
(3, 'Room 205', 'Lecture', 40, '2026-06-30 11:04:03'),
(4, 'Gymnasium', 'Lecture', 40, '2026-06-30 11:04:03');

-- --------------------------------------------------------

--
-- Table structure for table `schedule`
--

CREATE TABLE `schedule` (
  `schedule_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `professor_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `day` varchar(10) NOT NULL,
  `time_start` time NOT NULL,
  `time_end` time NOT NULL,
  `school_year` varchar(10) NOT NULL,
  `semester` tinyint(4) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedule`
--

INSERT INTO `schedule` (`schedule_id`, `subject_id`, `professor_id`, `section_id`, `room_id`, `day`, `time_start`, `time_end`, `school_year`, `semester`, `created_at`, `updated_at`, `is_active`) VALUES
(8, 1, 1, 1, 2, 'Monday', '08:00:00', '09:30:00', '2026-2027', 1, '2026-06-30 11:14:48', '2026-07-01 00:18:04', 1),
(9, 2, 2, 1, 2, 'Tuesday', '08:00:00', '09:30:00', '2026-2027', 1, '2026-06-30 11:14:48', '2026-07-01 00:18:04', 1),
(10, 4, 3, 1, 1, 'Wednesday', '10:00:00', '11:30:00', '2026-2027', 1, '2026-06-30 11:14:48', '2026-07-01 00:18:04', 1),
(11, 5, 3, 1, 1, 'Thursday', '10:00:00', '11:30:00', '2026-2027', 1, '2026-06-30 11:14:48', '2026-07-01 00:18:04', 1),
(12, 7, 4, 1, 3, 'Friday', '08:00:00', '11:00:00', '2026-2027', 1, '2026-06-30 11:14:48', '2026-07-01 00:18:04', 1),
(13, 8, 4, 1, 4, 'Friday', '01:00:00', '03:00:00', '2026-2027', 1, '2026-06-30 11:14:48', '2026-07-01 00:18:04', 1),
(14, 9, 1, 1, 2, 'Saturday', '08:00:00', '09:00:00', '2026-2027', 1, '2026-06-30 11:14:48', '2026-07-01 00:18:04', 1),
(15, 1, 4, 2, 1, 'Monday', '13:00:00', '14:00:00', '2026-2027', 1, '2026-07-01 10:34:53', '2026-07-01 10:34:53', 1);

-- --------------------------------------------------------

--
-- Table structure for table `section`
--

CREATE TABLE `section` (
  `section_id` int(11) NOT NULL,
  `section_name` varchar(50) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 40,
  `course_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `section`
--

INSERT INTO `section` (`section_id`, `section_name`, `capacity`, `course_id`) VALUES
(1, 'BSIT 1A', 45, 1),
(2, 'BSIT 1B', 40, 1),
(3, 'BSIT 2A', 35, 1);

-- --------------------------------------------------------

--
-- Table structure for table `statuses`
--

CREATE TABLE `statuses` (
  `status_id` int(11) NOT NULL,
  `status_name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `statuses`
--

INSERT INTO `statuses` (`status_id`, `status_name`, `created_at`) VALUES
(1, 'Active', '2026-06-28 07:00:09'),
(2, 'Inactive', '2026-06-28 07:00:09');

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `student_id` int(11) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `sex` varchar(10) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `applicant_id` int(11) DEFAULT NULL,
  `section_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`student_id`, `student_name`, `last_name`, `first_name`, `middle_name`, `birth_date`, `sex`, `contact_number`, `email`, `address`, `applicant_id`, `section_id`, `type_id`) VALUES
(1, 'Waffa Bea Bulado', 'Bulado', 'Waffa Bea', 'Villorente', '2006-11-25', 'Female', '09994850299', 'waffabulado@example.com', 'Langkaan 2, Dasmarinas City, Cavite', 1, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `student_type`
--

CREATE TABLE `student_type` (
  `type_id` int(11) NOT NULL,
  `type_name` varchar(50) NOT NULL
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
  `subject_id` int(11) NOT NULL,
  `subject_code` varchar(20) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `units` decimal(4,2) NOT NULL DEFAULT 3.00,
  `category_id` int(11) NOT NULL,
  `year_level` tinyint(4) NOT NULL,
  `semester` tinyint(4) NOT NULL,
  `prereq_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subject`
--

INSERT INTO `subject` (`subject_id`, `subject_code`, `subject_name`, `units`, `category_id`, `year_level`, `semester`, `prereq_id`) VALUES
(1, 'GE101', 'Understanding the Self', 3.00, 1, 1, 1, NULL),
(2, 'GE102', 'Readings in Philippine History', 3.00, 1, 1, 1, NULL),
(3, 'GEE101', 'Environmental Science', 3.00, 2, 1, 2, NULL),
(4, 'IT101', 'Introduction to Computing', 3.00, 3, 1, 1, NULL),
(5, 'IT102', 'Computer Programming 1', 3.00, 3, 1, 1, NULL),
(6, 'IT201', 'Data Structures', 3.00, 3, 2, 1, 5),
(7, 'NSTP101', 'National Service Training Program 1', 3.00, 4, 1, 1, NULL),
(8, 'PATHFIT1', 'Movement Competency Training', 2.00, 5, 1, 1, NULL),
(9, 'NCST101', 'NCST Institutional Orientation', 1.00, 6, 1, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `subject_category`
--

CREATE TABLE `subject_category` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subject_category`
--

INSERT INTO `subject_category` (`category_id`, `category_name`) VALUES
(2, 'GE ELECTIVE'),
(1, 'GEN ED'),
(3, 'IT'),
(6, 'NCST'),
(4, 'NSTP'),
(5, 'PATHFIT');

-- --------------------------------------------------------

--
-- Table structure for table `unpaid_students`
--

CREATE TABLE `unpaid_students` (
  `unpaid_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `school_year` varchar(10) NOT NULL,
  `semester` tinyint(4) NOT NULL,
  `amount_due` decimal(10,2) NOT NULL,
  `downpayment` decimal(10,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(10,2) NOT NULL,
  `due_date` date NOT NULL,
  `transferred_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `remarks` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `status_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `middle_name`, `last_name`, `email`, `phone_number`, `username`, `password`, `role_id`, `status_id`, `created_at`, `updated_at`) VALUES
(2, 'Ian', NULL, 'Reyes', 'ian.reyes@gmail.com', '09123456799', 'ian', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 1, '2026-06-29 06:34:17', '2026-06-30 16:37:55'),
(4, 'Admin', NULL, 'User', 'admin@eduschool.local', NULL, 'admin', '$2y$10$X7r/FJQSDhHNGTjLFN35l.yaSL.s.3s3VY/G4Yaf9194g7bXBrd2i', 1, 1, '2026-06-30 15:43:43', '2026-06-30 15:43:43');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admission`
--
ALTER TABLE `admission`
  ADD PRIMARY KEY (`admission_id`),
  ADD UNIQUE KEY `uq_admission` (`student_id`,`school_year`,`semester`),
  ADD KEY `fk_admission_type` (`type_id`),
  ADD KEY `fk_admission_applicant` (`applicant_id`);

--
-- Indexes for table `applicants`
--
ALTER TABLE `applicants`
  ADD PRIMARY KEY (`applicant_id`),
  ADD KEY `fk_applicant_type` (`applicant_type_id`),
  ADD KEY `fk_applicant_verified_by` (`id_verified_by`);

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
-- Indexes for table `course`
--
ALTER TABLE `course`
  ADD PRIMARY KEY (`course_id`);

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
  ADD KEY `fk_enroll_admission` (`admission_id`),
  ADD KEY `fk_enrollment_section` (`section_id`);

--
-- Indexes for table `enrollment_subject`
--
ALTER TABLE `enrollment_subject`
  ADD PRIMARY KEY (`enrollment_subject_id`),
  ADD UNIQUE KEY `uq_es` (`enrollment_id`,`subject_id`),
  ADD KEY `fk_es_subject` (`subject_id`);

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
  ADD KEY `fk_subject_category` (`category_id`),
  ADD KEY `fk_subject_prereq` (`prereq_id`);

--
-- Indexes for table `subject_category`
--
ALTER TABLE `subject_category`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

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
  ADD KEY `fk_user_role` (`role_id`),
  ADD KEY `fk_user_status` (`status_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admission`
--
ALTER TABLE `admission`
  MODIFY `admission_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `applicants`
--
ALTER TABLE `applicants`
  MODIFY `applicant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `applicant_documents`
--
ALTER TABLE `applicant_documents`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `applicant_school_history`
--
ALTER TABLE `applicant_school_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `course`
--
ALTER TABLE `course`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `department`
--
ALTER TABLE `department`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `enrollment`
--
ALTER TABLE `enrollment`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `enrollment_subject`
--
ALTER TABLE `enrollment_subject`
  MODIFY `enrollment_subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `fee_schedule`
--
ALTER TABLE `fee_schedule`
  MODIFY `fee_schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `fee_schedule_item`
--
ALTER TABLE `fee_schedule_item`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `payment_breakdown`
--
ALTER TABLE `payment_breakdown`
  MODIFY `breakdown_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `professor`
--
ALTER TABLE `professor`
  MODIFY `professor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `room`
--
ALTER TABLE `room`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `schedule`
--
ALTER TABLE `schedule`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `section`
--
ALTER TABLE `section`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `statuses`
--
ALTER TABLE `statuses`
  MODIFY `status_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `student_type`
--
ALTER TABLE `student_type`
  MODIFY `type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `subject`
--
ALTER TABLE `subject`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `subject_category`
--
ALTER TABLE `subject_category`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `unpaid_students`
--
ALTER TABLE `unpaid_students`
  MODIFY `unpaid_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admission`
--
ALTER TABLE `admission`
  ADD CONSTRAINT `fk_admission_applicant` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`applicant_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_admission_student` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_admission_type` FOREIGN KEY (`type_id`) REFERENCES `student_type` (`type_id`) ON UPDATE CASCADE;

--
-- Constraints for table `applicants`
--
ALTER TABLE `applicants`
  ADD CONSTRAINT `fk_applicant_type` FOREIGN KEY (`applicant_type_id`) REFERENCES `student_type` (`type_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_applicant_verified_by` FOREIGN KEY (`id_verified_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

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
-- Constraints for table `enrollment`
--
ALTER TABLE `enrollment`
  ADD CONSTRAINT `fk_enroll_admission` FOREIGN KEY (`admission_id`) REFERENCES `admission` (`admission_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_enroll_applicant` FOREIGN KEY (`student_id`) REFERENCES `applicants` (`applicant_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_enrollment_section` FOREIGN KEY (`section_id`) REFERENCES `section` (`section_id`),
  ADD CONSTRAINT `fk_enrollment_type` FOREIGN KEY (`type_id`) REFERENCES `student_type` (`type_id`) ON UPDATE CASCADE;

--
-- Constraints for table `enrollment_subject`
--
ALTER TABLE `enrollment_subject`
  ADD CONSTRAINT `fk_es_enrollment` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollment` (`enrollment_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_es_subject` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`subject_id`) ON UPDATE CASCADE;

--
-- Constraints for table `fee_schedule_item`
--
ALTER TABLE `fee_schedule_item`
  ADD CONSTRAINT `fee_schedule_item_ibfk_1` FOREIGN KEY (`fee_schedule_id`) REFERENCES `fee_schedule` (`fee_schedule_id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `fk_subject_prereq` FOREIGN KEY (`prereq_id`) REFERENCES `subject` (`subject_id`) ON DELETE SET NULL ON UPDATE CASCADE;

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
