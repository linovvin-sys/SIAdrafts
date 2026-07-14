-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 03, 2026 at 04:00 PM
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
-- Database: `enrollment_db_sia`
--

-- --------------------------------------------------------

--
-- Table structure for table `applicants`
--

CREATE TABLE `applicants` (
  `applicant_id` int(11) NOT NULL,
  `reference_id` varchar(14) NOT NULL,
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
  `id_verified_by` varchar(9) DEFAULT NULL,
  `admission_status` enum('pending_verification','verified') NOT NULL DEFAULT 'pending_verification',
  `verified_at` timestamp NULL DEFAULT NULL,
  `program` varchar(50) NOT NULL DEFAULT 'BSIT',
  `course_id` int(11) DEFAULT NULL,
  `year_level` tinyint(4) NOT NULL DEFAULT 1,
  `start_term` varchar(20) NOT NULL,
  `applicant_type` varchar(30) DEFAULT NULL,
  `applicant_type_id` int(11) NOT NULL DEFAULT 1,
  `status` enum('Pending','Downpayment Paid','Fully Paid') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applicants`
--

INSERT INTO `applicants` (`applicant_id`, `reference_id`, `last_name`, `first_name`, `middle_name`, `birth_date`, `sex`, `civil_status`, `contact_number`, `email`, `home_address`, `guardian_name`, `guardian_relationship`, `guardian_contact`, `guardian_id_type`, `guardian_id_number`, `id_verified_by`, `admission_status`, `verified_at`, `program`, `course_id`, `year_level`, `start_term`, `applicant_type`, `applicant_type_id`, `status`, `created_at`, `updated_at`) VALUES
(3, '2026-00001', 'Bulado', 'Waffa Bea', 'Villorente', '2005-11-25', 'Female', 'Single', '09994850299', 'bea@example.com', 'Block 3, Lot 2, Langkaan 2, Dasmarinas City, Cavite', 'Abigaille Villorente', 'Mother', '09260557058', 'Philippine National ID', '123456', '2026-0004', 'verified', '2026-07-03 02:02:06', 'Bachelor of Science in Information Technology', 1, 1, '2026-2027', 'Freshman', 1, 'Downpayment Paid', '2026-07-03 01:51:22', '2026-07-03 06:50:19'),
(8, 'REF-00001-001', 'Reyes', 'Ian', 'Arvie', '2007-07-25', 'Male', 'Single', '09875647899', 'ian@example.com', 'Block 5, Lot 18, Via Verde, Dasmarinas City, Cavite', 'Arlene Reyes', 'Mother', '09456679856', 'PhilHealth ID', '465788', '2026-0004', 'verified', '2026-07-03 08:27:54', 'Bachelor of Science in Information Technology', 1, 1, '2026-2027', 'New', 1, 'Downpayment Paid', '2026-07-03 08:20:37', '2026-07-03 09:25:04');

-- --------------------------------------------------------

--
-- Table structure for table `applicant_documents`
--

CREATE TABLE `applicant_documents` (
  `document_id` int(11) NOT NULL,
  `applicant_id` int(11) NOT NULL,
  `document_name` varchar(100) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','submitted') NOT NULL DEFAULT 'pending',
  `verified_by` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
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
(16, 8, '2x2 ID Photos', NULL, 'submitted', 6, '2026-07-03 08:27:54');

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
(1, 3, 'NCST SHS', 'Dasmarinas City', '2025', 'STEM', 99.00),
(5, 8, 'NCST SHS', 'Aguinaldo, Dasmarinas City, Cavite', '2025', 'STEM', 97.00);

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
(1, 'BSIT', 'Bachelor of Science in Information Technology', 120);

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
(1, 'CSD', 'Computes of Studies Department', '2026-06-27 23:06:53'),
(2, 'GEN ED', 'General Education Department', '2026-06-27 23:06:53'),
(3, 'NSTP', 'National Service Training Program', '2026-06-27 23:06:53'),
(4, 'PE', 'Physical Education Department', '2026-06-27 23:06:53');

-- --------------------------------------------------------

--
-- Table structure for table `enrollment`
--

CREATE TABLE `enrollment` (
  `enrollment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
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

INSERT INTO `enrollment` (`enrollment_id`, `student_id`, `school_year`, `semester`, `year_level`, `section_id`, `status`, `created_at`, `updated_at`, `type_id`) VALUES
(1, 3, '2026-2027', 1, 1, 1, 'Enrolled', '2026-07-03 02:01:19', '2026-07-03 02:02:06', 1),
(2, 8, '2026-2027', 1, 1, 1, 'Enrolled', '2026-07-03 09:18:12', '2026-07-03 09:25:04', 1);

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
(1, 1, 4, 'Enrolled', '2026-07-03 02:01:19'),
(2, 2, 4, 'Enrolled', '2026-07-03 09:18:12');

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
(1, 1, '2026-2027', 23500.00, 1, '2026-06-30 17:04:33', '2026-06-30 17:04:33'),
(2, 2, '2026-2027', 25500.00, 1, '2026-06-30 17:04:33', '2026-06-30 17:04:33'),
(3, 3, '2026-2027', 26500.00, 1, '2026-06-30 17:04:33', '2026-06-30 17:04:33'),
(4, 4, '2026-2027', 26500.00, 1, '2026-06-30 17:04:33', '2026-06-30 17:04:33');

-- --------------------------------------------------------

--
-- Table structure for table `fee_schedule_item`
--

CREATE TABLE `fee_schedule_item` (
  `item_id` int(11) NOT NULL,
  `fee_schedule_id` int(11) NOT NULL,
  `label` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `is_per_unit` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0
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
(1, 1, 10750.00, 3000.00, '2026-07-06', 'Down Payment Paid', '2026-07-03 02:02:06', 5, '2026-07-03 02:01:19', '2026-07-03 02:02:06'),
(2, 2, 10750.00, 3000.00, '2026-07-06', 'Down Payment Paid', '2026-07-03 09:25:04', 5, '2026-07-03 09:18:12', '2026-07-03 09:25:04');

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
(12, 2, 'Registration / ID Fee', 500.00, 6);

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
(1, 1, 3000.00, '2026-07-03 02:02:06', 'cash', 5),
(2, 2, 3000.00, '2026-07-03 09:25:04', 'cash', 5);

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
(1, 'Santos', 'John', NULL, 1, 1, '2026-06-30 03:14:33', '2026-06-30 03:14:33'),
(2, 'Reyes', 'Maria', NULL, 2, 1, '2026-06-30 03:14:33', '2026-06-30 03:14:33'),
(3, 'Garcia', 'Anthony', NULL, 1, 1, '2026-06-30 03:14:33', '2026-06-30 03:14:33'),
(4, 'Dela Cruz', 'Louise', NULL, 4, 1, '2026-06-30 03:14:33', '2026-06-30 03:14:33');

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
(1, 'Admin', '2026-06-27 23:00:09'),
(2, 'Admission', '2026-06-27 23:00:09'),
(3, 'Staff', '2026-06-27 23:00:09'),
(4, 'Treasury', '2026-07-01 04:39:31');

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
(1, 'Computer Laboratory 1', 'Lecture', 40, '2026-06-30 03:04:03'),
(2, 'Room 301', 'Lecture', 40, '2026-06-30 03:04:03'),
(3, 'Room 205', 'Lecture', 40, '2026-06-30 03:04:03'),
(4, 'Gymnasium', 'Lecture', 40, '2026-06-30 03:04:03');

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
(9, 4, 2, 1, 1, 'Monday', '07:00:00', '10:00:00', '2026-2027', 1, '2026-07-03 01:56:27', '2026-07-03 01:56:27', 1);

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
(1, 'BSIT A1', 40, 1);

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
(1, 'Active', '2026-06-27 23:00:09'),
(2, 'Inactive', '2026-06-27 23:00:09');

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `student_id` int(11) NOT NULL,
  `student_no` varchar(10) DEFAULT NULL,
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

INSERT INTO `student` (`student_id`, `student_no`, `student_name`, `last_name`, `first_name`, `middle_name`, `birth_date`, `sex`, `contact_number`, `email`, `address`, `applicant_id`, `section_id`, `type_id`) VALUES
(1, '2026-00001', 'Waffa Bea Bulado', 'Bulado', 'Waffa Bea', 'Villorente', '2005-11-25', 'Female', '09994850299', 'bea@example.com', 'Block 3, Lot 2, Langkaan 2, Dasmarinas City, Cavite', 3, 1, 1),
(2, '2026-00002', 'Ian Reyes', 'Reyes', 'Ian', 'Arvie', '2007-07-25', 'Male', '09875647899', 'ian@example.com', 'Block 5, Lot 18, Via Verde, Dasmarinas City, Cavite', 8, 1, 1);

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
  `course_id` int(11) NOT NULL DEFAULT 1,
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

INSERT INTO `subject` (`subject_id`, `course_id`, `subject_code`, `subject_name`, `units`, `category_id`, `year_level`, `semester`, `prereq_id`) VALUES
(1, 1, 'Ge 101', 'UTS', 3.00, 2, 1, 1, NULL),
(2, 1, 'GE102', 'Readings in Philippine History', 3.00, 1, 1, 1, NULL),
(3, 1, 'GEE101', 'Environmental Science', 3.00, 2, 1, 2, NULL),
(4, 1, 'IT101', 'Introduction to Computing', 3.00, 3, 1, 1, NULL),
(5, 1, 'IT102', 'Computer Programming 1', 3.00, 3, 1, 1, NULL),
(6, 1, 'IT201', 'Data Structures', 3.00, 3, 2, 1, 5),
(7, 1, 'NSTP101', 'National Service Training Program 1', 3.00, 4, 1, 1, NULL),
(8, 1, 'PATHFIT1', 'Movement Competency Training', 2.00, 5, 1, 1, NULL),
(9, 1, 'NCST101', 'NCST Institutional Orientation', 1.00, 6, 1, 1, NULL);

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
(5, 'PATHFIT'),
(7, 'PSYCH');

-- --------------------------------------------------------

--
-- Table structure for table `subject_course`
--

CREATE TABLE `subject_course` (
  `subject_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `staff_id` varchar(9) DEFAULT NULL,
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

INSERT INTO `users` (`user_id`, `staff_id`, `first_name`, `middle_name`, `last_name`, `email`, `phone_number`, `username`, `password`, `role_id`, `status_id`, `created_at`, `updated_at`) VALUES
(4, '2026-0002', 'Admin', NULL, 'User', 'admin@eduschool.local', NULL, 'admin', '$2y$10$X7r/FJQSDhHNGTjLFN35l.yaSL.s.3s3VY/G4Yaf9194g7bXBrd2i', 1, 1, '2026-06-30 15:43:43', '2026-07-01 12:26:27'),
(5, '2026-0003', 'Frankyz', 'AHzee', 'Malbog', 'frankyz@example.com', '09987675364', 'azii', '$2y$10$A/9kCBCpDcwQ78UrEWOjlOt7p1I3.0uk2NmNIeQ94wFBG4oNWJLxG', 4, 1, '2026-07-03 01:25:13', '2026-07-03 01:25:13'),
(6, '2026-0004', 'Ann', 'Eme', 'Lubo', 'ann@example.com', '09987675456', 'tintin', '$2y$10$XTLI5bOdpyVi6iZIpYZVmO/.dq0VKn2GSnOk2EcNgVAXqEEwLTNvy', 2, 1, '2026-07-03 01:25:54', '2026-07-03 01:25:54'),
(7, '2026-0005', 'Ian', 'Arvie', 'Reyes', 'ian@example.com', '09928786456', 'ian', '$2y$10$yH0vCSL5g2yzKG7dcPYage7YGhgSjntaxahswj4Gvibkzg2nXGZaG', 3, 1, '2026-07-03 01:26:29', '2026-07-03 01:26:29');

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
  MODIFY `applicant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `applicant_documents`
--
ALTER TABLE `applicant_documents`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `applicant_school_history`
--
ALTER TABLE `applicant_school_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `course`
--
ALTER TABLE `course`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `department`
--
ALTER TABLE `department`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `enrollment`
--
ALTER TABLE `enrollment`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `enrollment_subject`
--
ALTER TABLE `enrollment_subject`
  MODIFY `enrollment_subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payment_breakdown`
--
ALTER TABLE `payment_breakdown`
  MODIFY `breakdown_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `professor`
--
ALTER TABLE `professor`
  MODIFY `professor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `room`
--
ALTER TABLE `room`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `schedule`
--
ALTER TABLE `schedule`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `section`
--
ALTER TABLE `section`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `statuses`
--
ALTER TABLE `statuses`
  MODIFY `status_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `unpaid_students`
--
ALTER TABLE `unpaid_students`
  MODIFY `unpaid_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
