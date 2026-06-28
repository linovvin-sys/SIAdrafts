-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 28, 2026 at 09:18 AM
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
-- Database: `enrollment_db`
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
) ;

-- --------------------------------------------------------

--
-- Table structure for table `applicants`
--

CREATE TABLE `applicants` (
  `applicant_id` int(11) NOT NULL,
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
  `applicant_type_id` int(11) NOT NULL DEFAULT 1,
  `status` varchar(20) NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

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
) ;

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
  `status` varchar(20) NOT NULL DEFAULT 'Enrolled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `type_id` int(11) NOT NULL DEFAULT 1
) ;

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
) ;

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
) ;

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
) ;

--
-- Dumping data for table `room`
--

INSERT INTO `room` (`room_id`, `room_name`, `room_type`, `capacity`, `created_at`) VALUES
(1, 'Room 101', 'Lecture', 40, '2026-06-28 07:07:24'),
(2, 'Room 102', 'Lecture', 40, '2026-06-28 07:07:24'),
(3, 'Room 103', 'Lecture', 40, '2026-06-28 07:07:24'),
(4, 'Lab 1', 'Laboratory', 30, '2026-06-28 07:07:24'),
(5, 'Lab 2', 'Laboratory', 30, '2026-06-28 07:07:24');

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `section`
--

CREATE TABLE `section` (
  `section_id` int(11) NOT NULL,
  `section_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `section`
--

INSERT INTO `section` (`section_id`, `section_name`) VALUES
(1, 'BSIT-1A'),
(2, 'BSIT-1B'),
(3, 'BSIT-2A'),
(4, 'BSIT-2B'),
(5, 'BSIT-3A'),
(6, 'BSIT-3B'),
(7, 'BSIT-4A'),
(8, 'BSIT-4B');

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
) ;

--
-- Dumping data for table `subject`
--

INSERT INTO `subject` (`subject_id`, `subject_code`, `subject_name`, `units`, `category_id`, `year_level`, `semester`, `prereq_id`) VALUES
(1, 'GE 003C', 'Mathematics in the Modern World', 3.00, 1, 1, 1, NULL),
(2, 'GE 005', 'Science, Technology & Society', 3.00, 1, 1, 1, NULL),
(3, 'GE 007', 'Contemporary World', 3.00, 1, 1, 1, NULL),
(4, 'GELEC 004-IT', 'Living in the IT Era', 3.00, 2, 1, 1, NULL),
(5, 'IT 101', 'Introduction to Computing', 3.00, 3, 1, 1, NULL),
(6, 'IT 102', 'Computer Programming 1', 3.00, 3, 1, 1, NULL),
(7, 'NCST 001', 'Nation Builders (NCST Culture 1)', 3.00, 6, 1, 1, NULL),
(8, 'NSTP 001', 'National Service Training Program 1', 0.00, 4, 1, 1, NULL),
(9, 'PATHFIT 1', 'Physical Activities Towards Health & Fitness 1', 2.00, 5, 1, 1, NULL),
(10, 'GE 004', 'Understanding the Self', 3.00, 1, 1, 2, NULL),
(11, 'GE 008', 'Art Appreciation', 3.00, 1, 1, 2, NULL),
(12, 'GELEC 012-IT', 'Reading Visual Art', 3.00, 2, 1, 2, NULL),
(13, 'IT 103', 'Computer Programming 2', 3.00, 3, 1, 2, 6),
(14, 'IT 104', 'Web System Technologies 1', 3.00, 3, 1, 2, 6),
(15, 'IT 105', 'Discrete Structures 1', 3.00, 3, 1, 2, NULL),
(16, 'IT 106', 'Networking 1', 3.00, 3, 1, 2, 5),
(17, 'NSTP 002', 'National Service Training Program 2', 0.00, 4, 1, 2, 8),
(18, 'PATHFIT 2', 'Physical Activities Towards Health & Fitness 2', 2.00, 5, 1, 2, NULL);

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
) ;

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
  ADD KEY `fk_enroll_admission` (`admission_id`);

--
-- Indexes for table `enrollment_subject`
--
ALTER TABLE `enrollment_subject`
  ADD PRIMARY KEY (`enrollment_subject_id`),
  ADD UNIQUE KEY `uq_es` (`enrollment_id`,`subject_id`),
  ADD KEY `fk_es_subject` (`subject_id`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`payment_id`),
  ADD UNIQUE KEY `uq_payment_enrollment` (`enrollment_id`),
  ADD KEY `fk_payment_received_by` (`received_by`);

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
  ADD PRIMARY KEY (`section_id`);

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
  MODIFY `applicant_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `applicant_documents`
--
ALTER TABLE `applicant_documents`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `applicant_school_history`
--
ALTER TABLE `applicant_school_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `department`
--
ALTER TABLE `department`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `enrollment`
--
ALTER TABLE `enrollment`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `enrollment_subject`
--
ALTER TABLE `enrollment_subject`
  MODIFY `enrollment_subject_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `professor`
--
ALTER TABLE `professor`
  MODIFY `professor_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `room`
--
ALTER TABLE `room`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `schedule`
--
ALTER TABLE `schedule`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `section`
--
ALTER TABLE `section`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `statuses`
--
ALTER TABLE `statuses`
  MODIFY `status_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_type`
--
ALTER TABLE `student_type`
  MODIFY `type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `subject`
--
ALTER TABLE `subject`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT;

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
  ADD CONSTRAINT `fk_enroll_student` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_enrollment_type` FOREIGN KEY (`type_id`) REFERENCES `student_type` (`type_id`) ON UPDATE CASCADE;

--
-- Constraints for table `enrollment_subject`
--
ALTER TABLE `enrollment_subject`
  ADD CONSTRAINT `fk_es_enrollment` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollment` (`enrollment_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_es_subject` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`subject_id`) ON UPDATE CASCADE;

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `fk_payment_enrollment` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollment` (`enrollment_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_payment_received_by` FOREIGN KEY (`received_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

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
