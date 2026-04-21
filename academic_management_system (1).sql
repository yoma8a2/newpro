-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: 21 أبريل 2026 الساعة 08:39
-- إصدار الخادم: 10.4.24-MariaDB
-- PHP Version: 8.1.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `academic_management_system`
--

-- --------------------------------------------------------

--
-- بنية الجدول `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  `lecture_date` date DEFAULT NULL,
  `status` enum('حاضر','غائب','متأخر','مستأذن') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `audit_log`
--

CREATE TABLE `audit_log` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `table_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `colleges`
--

CREATE TABLE `colleges` (
  `college_id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `colleges`
--

INSERT INTO `colleges` (`college_id`, `name`) VALUES
(1, 'كلية الحاسبات وتقنية المعلومات'),
(2, 'كلية الهندسة'),
(3, 'كلية العلوم');

-- --------------------------------------------------------

--
-- بنية الجدول `courses`
--

CREATE TABLE `courses` (
  `course_id` int(11) NOT NULL,
  `course_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `program_id` int(11) DEFAULT NULL,
  `level_id` int(11) DEFAULT NULL,
  `credit_hours` int(11) NOT NULL DEFAULT 3,
  `has_practical` enum('نعم','لا') COLLATE utf8mb4_unicode_ci DEFAULT 'لا'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `courses`
--

INSERT INTO `courses` (`course_id`, `course_code`, `name`, `program_id`, `level_id`, `credit_hours`, `has_practical`) VALUES
(3, 'CS201', 'قواعد بيانات', 4, 15, 3, 'نعم'),
(4, NULL, 'هكر', 1, 1, 3, 'لا'),
(5, NULL, 'برمجة امنه', 3, 12, 3, 'لا'),
(8, NULL, 'شبكات متقدمة ', 5, 17, 3, 'لا');

-- --------------------------------------------------------

--
-- بنية الجدول `departments`
--

CREATE TABLE `departments` (
  `dept_id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `college_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `departments`
--

INSERT INTO `departments` (`dept_id`, `name`, `college_id`) VALUES
(1, 'علوم الحاسبات', 1),
(2, 'نظم المعلومات الإدارية', 1),
(3, 'الأمن السيبراني', 1),
(4, 'هندسة البرمجيات', 1),
(5, 'الذكاء الاصطناعي', 1),
(6, 'علوم البيانات', 1),
(7, 'هندسة معمارية ', 2);

-- --------------------------------------------------------

--
-- بنية الجدول `enrollments`
--

CREATE TABLE `enrollments` (
  `enrollment_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  `enrollment_date` date DEFAULT curdate(),
  `status` enum('active','completed','dropped') COLLATE utf8mb4_unicode_ci DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `grades`
--

CREATE TABLE `grades` (
  `grade_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  `midterm` int(11) DEFAULT 0,
  `final` int(11) DEFAULT 0,
  `assignments` int(11) DEFAULT 0,
  `practical` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `grades_summary`
-- (See below for the actual view)
--
CREATE TABLE `grades_summary` (
`grade_id` int(11)
,`student_id` int(11)
,`section_id` int(11)
,`midterm` int(11)
,`final` int(11)
,`assignments` int(11)
,`practical` int(11)
,`total` bigint(14)
,`grade_letter` varchar(1)
);

-- --------------------------------------------------------

--
-- بنية الجدول `instructors`
--

CREATE TABLE `instructors` (
  `instructor_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qualification` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `specialization` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `academic_rank` enum('محاضر','أستاذ مساعد','أستاذ مشارك','أستاذ') COLLATE utf8mb4_unicode_ci DEFAULT 'محاضر',
  `hire_date` date DEFAULT NULL,
  `contract_type` enum('دائم','جزئي','ساعات') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `office_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','on_leave','resigned') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `dept_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `instructors`
--

INSERT INTO `instructors` (`instructor_id`, `user_id`, `name`, `email`, `phone`, `qualification`, `specialization`, `academic_rank`, `hire_date`, `contract_type`, `office_number`, `status`, `dept_id`) VALUES
(60, 60, 'علي صالح', NULL, NULL, NULL, NULL, 'محاضر', NULL, NULL, NULL, 'active', NULL),
(1001, 2, 'د. أحمد العمري', 'ahmed@csc.edu.sa', '0501122334', 'دكتوراه', 'قواعد البيانات', 'أستاذ', '2010-08-25', 'دائم', NULL, 'active', 1),
(1002, 3, 'د. نورة الشهري', 'nora@csc.edu.sa', '0552233445', 'دكتوراه', 'الذكاء الاصطناعي', 'أستاذ مشارك', '2015-01-10', 'دائم', NULL, 'active', 5);

-- --------------------------------------------------------

--
-- بنية الجدول `levels`
--

CREATE TABLE `levels` (
  `level_id` int(11) NOT NULL,
  `program_id` int(11) DEFAULT NULL,
  `level_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `levels`
--

INSERT INTO `levels` (`level_id`, `program_id`, `level_name`) VALUES
(1, 1, 'المستوى الأول'),
(3, 1, 'المستوى الثاني '),
(4, 1, 'المستوى ثالث'),
(5, 1, 'المستوى رابع'),
(7, 2, 'المستوى الأول'),
(8, 2, 'المستوى الثاني '),
(9, 2, 'المستوى الأول'),
(10, 2, 'المستوى الثاني '),
(11, 3, 'المستوى الثالث'),
(12, 3, 'المستوى الرابع'),
(13, 4, 'المستوى الأول'),
(14, 4, 'المستوى الثاني '),
(15, 4, 'المستوى الثالث '),
(16, 4, 'المستوى رابع'),
(17, 5, 'المستوى الأول'),
(18, 5, 'المستوى الثاني '),
(19, 5, 'المستوى الثالث'),
(20, 5, 'المستوى الرابع '),
(21, 7, 'المستوى 1'),
(22, 7, 'المستوى 2'),
(23, 7, 'المستوى 3'),
(24, 7, 'المستوى 4');

-- --------------------------------------------------------

--
-- بنية الجدول `mandatory_courses`
--

CREATE TABLE `mandatory_courses` (
  `mandatory_id` int(11) NOT NULL,
  `program_id` int(11) DEFAULT NULL,
  `level_id` int(11) DEFAULT NULL,
  `semester` int(11) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `programs`
--

CREATE TABLE `programs` (
  `program_id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dept_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `programs`
--

INSERT INTO `programs` (`program_id`, `name`, `dept_id`) VALUES
(1, 'بكالوريوس علوم الحاسبات', 1),
(2, 'بكالوريوس نظم المعلومات الإدارية', 2),
(3, 'بكالوريوس الأمن السيبراني', 3),
(4, 'بكالوريوس هندسة البرمجيات', 4),
(5, 'بكالوريوس الذكاء الاصطناعي', 5),
(6, 'بكالوريوس علوم البيانات', 6),
(7, 'هندسة معمارية ', 7);

-- --------------------------------------------------------

--
-- بنية الجدول `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`) VALUES
(1, 'admin'),
(2, 'instructor'),
(3, 'student');

-- --------------------------------------------------------

--
-- بنية الجدول `sections`
--

CREATE TABLE `sections` (
  `section_id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `instructor_id` int(11) DEFAULT NULL,
  `semester` int(11) DEFAULT NULL,
  `year` year(4) DEFAULT NULL,
  `lecture_time` time DEFAULT NULL,
  `level_id` int(11) DEFAULT NULL,
  `group_name` enum('A','B','C','D') COLLATE utf8mb4_unicode_ci DEFAULT 'A',
  `section_number` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `day_of_week` enum('Sun','Mon','Tue','Wed','Thu') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `room_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `max_capacity` int(11) DEFAULT 50,
  `current_enrollment` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `sections`
--

INSERT INTO `sections` (`section_id`, `course_id`, `instructor_id`, `semester`, `year`, `lecture_time`, `level_id`, `group_name`, `section_number`, `day_of_week`, `start_time`, `end_time`, `room_number`, `max_capacity`, `current_enrollment`) VALUES
(56, 3, 1002, 2, 2026, '10:00:00', 11, 'B', NULL, NULL, NULL, NULL, NULL, 50, 0),
(57, 4, 60, 1, 2026, '14:00:00', 9, 'B', NULL, NULL, NULL, NULL, NULL, 50, 0);

-- --------------------------------------------------------

--
-- بنية الجدول `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `program_id` int(11) DEFAULT NULL,
  `dept_id` int(11) DEFAULT NULL,
  `level_id` int(11) DEFAULT NULL,
  `enrollment_year` year(4) DEFAULT NULL,
  `gpa` decimal(3,2) DEFAULT 0.00,
  `status` enum('active','graduated','suspended') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `group_name` enum('A','B','C','D') COLLATE utf8mb4_unicode_ci DEFAULT 'A'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `students`
--

INSERT INTO `students` (`student_id`, `user_id`, `name`, `phone`, `program_id`, `dept_id`, `level_id`, `enrollment_year`, `gpa`, `status`, `group_name`) VALUES
(51, 51, 'سما الصانع', NULL, 4, NULL, 14, NULL, '0.00', 'active', 'A'),
(61, 61, 'خالد حمود', NULL, 3, NULL, 11, NULL, '0.00', 'active', 'B'),
(2024003, 17, 'نور عبداللطيف', NULL, 2, NULL, 8, NULL, '0.00', 'active', 'B'),
(2024004, 55, 'وئام الصانع', NULL, 3, NULL, 11, NULL, '0.00', 'active', 'B'),
(2024005, 62, 'صالح عبده', NULL, 3, NULL, 11, NULL, '0.00', 'active', 'B');

-- --------------------------------------------------------

--
-- بنية الجدول `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `status` enum('active','suspended') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `login_attempts` int(11) DEFAULT 0,
  `lock_until` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `users`
--

INSERT INTO `users` (`user_id`, `username`, `password_hash`, `email`, `role_id`, `status`, `login_attempts`, `lock_until`) VALUES
(1, 'admin', '$2y$10$v0N.Dtm89f.uIu7iY1gC/O6m7.jQ8X.9y8y8y8y8y8y8y8y8y8y', 'admin@csc.edu.sa', 1, 'active', 0, NULL),
(2, 'dr.ahmed', '$2y$10$v0N.Dtm89f.uIu7iY1gC/O6m7.jQ8X.9y8y8y8y8y8y8y8y8y8y', 'ahmed@csc.edu.sa', 2, 'active', 0, NULL),
(3, 'dr.nora', '$2y$10$v0N.Dtm89f.uIu7iY1gC/O6m7.jQ8X.9y8y8y8y8y8y8y8y8y8y', 'nora@csc.edu.sa', 2, 'active', 0, NULL),
(9, 'ali', '$2y$10$DgaU.q.D75es4hHiKV7S8O7TGkc4NcdeAkobDMTCy7JI5zlkc1xum', 'ali1@gmail.com', 1, 'active', 0, NULL),
(17, 'نور عبداللطيف', '$2y$10$10YACq7tE.muoW/YK8Sh6uyeFcyfifjfPR9350huiWJDDrFQPAK.S', 'nour@gmail.com', 3, 'active', 0, NULL),
(51, 'سما الصانع', '$2y$10$LLgfARv0WmTm29F.ykyQQu.HWL5Ov34l9wWAz1TeRlZ1cFbKbEGpW', 'sama@gmail.com', 3, 'active', 0, NULL),
(55, 'وئام الصانع', '$2y$10$5ODnOSgLvAgWIQ4RbzNx2udPu4Ql.seeJLpq.Y40s2tQC1bNOS3TG', 'weam@gmail.com', 3, 'active', 0, NULL),
(59, 'سميه فؤاد', '$2y$10$kSOSKc5raXgK1Ty.aXCUNeMxpJwxrn5Haplei2h4c.OrW9lQ.KAvO', 'somai@gmail.com', 1, 'active', 0, NULL),
(60, 'علي صالح', '$2y$10$6KIgGzNNHG4GCbxS7H1Dme8Sos1wCtEefz6/8jE5q3qs3W/WifQu6', 'salah@gmail.com', 2, 'active', 0, NULL),
(61, 'خالد حمود', '$2y$10$uYQAWVfORnvb7ty/2xATtOVYQ4tTAT/w.0ZLiY10A7XsNr64D13Na', 'khald50@gmail.com', 3, 'active', 0, NULL),
(62, 'صالح عبده', '$2y$10$AHiVbGo8EMvT1TjTNXW4ZOK7BWGawBVk/Lfy12KxxYtilBlyInFOK', 'salah12@gmail.com', 3, 'active', 0, NULL);

-- --------------------------------------------------------

--
-- Structure for view `grades_summary`
--
DROP TABLE IF EXISTS `grades_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `grades_summary`  AS SELECT `grades`.`grade_id` AS `grade_id`, `grades`.`student_id` AS `student_id`, `grades`.`section_id` AS `section_id`, `grades`.`midterm` AS `midterm`, `grades`.`final` AS `final`, `grades`.`assignments` AS `assignments`, `grades`.`practical` AS `practical`, `grades`.`midterm`+ `grades`.`final` + `grades`.`assignments` + `grades`.`practical` AS `total`, CASE WHEN `grades`.`midterm` + `grades`.`final` + `grades`.`assignments` + `grades`.`practical` >= 90 THEN 'A' WHEN `grades`.`midterm` + `grades`.`final` + `grades`.`assignments` + `grades`.`practical` >= 80 THEN 'B' WHEN `grades`.`midterm` + `grades`.`final` + `grades`.`assignments` + `grades`.`practical` >= 70 THEN 'C' WHEN `grades`.`midterm` + `grades`.`final` + `grades`.`assignments` + `grades`.`practical` >= 60 THEN 'D' ELSE 'F' END AS `grade_letter` FROM `grades``grades`  ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD UNIQUE KEY `unique_attendance` (`student_id`,`section_id`,`lecture_date`),
  ADD KEY `fk_attendance_section_v3` (`section_id`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `colleges`
--
ALTER TABLE `colleges`
  ADD PRIMARY KEY (`college_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`),
  ADD KEY `fk_course_program` (`program_id`),
  ADD KEY `fk_course_level` (`level_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`dept_id`),
  ADD KEY `college_id` (`college_id`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD UNIQUE KEY `unique_student_section` (`student_id`,`section_id`),
  ADD KEY `section_id` (`section_id`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`grade_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `section_id` (`section_id`);

--
-- Indexes for table `instructors`
--
ALTER TABLE `instructors`
  ADD PRIMARY KEY (`instructor_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `dept_id` (`dept_id`);

--
-- Indexes for table `levels`
--
ALTER TABLE `levels`
  ADD PRIMARY KEY (`level_id`),
  ADD KEY `program_id` (`program_id`);

--
-- Indexes for table `mandatory_courses`
--
ALTER TABLE `mandatory_courses`
  ADD PRIMARY KEY (`mandatory_id`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `level_id` (`level_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`program_id`),
  ADD KEY `dept_id` (`dept_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`section_id`),
  ADD KEY `fk_sections_course_v3` (`course_id`),
  ADD KEY `fk_sections_instructor_v3` (`instructor_id`),
  ADD KEY `fk_sections_level_v3` (`level_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `level_id` (`level_id`),
  ADD KEY `fk_student_dept` (`dept_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `colleges`
--
ALTER TABLE `colleges`
  MODIFY `college_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `dept_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `grade_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `levels`
--
ALTER TABLE `levels`
  MODIFY `level_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `mandatory_courses`
--
ALTER TABLE `mandatory_courses`
  MODIFY `mandatory_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `program_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- قيود الجداول المحفوظة
--

--
-- القيود للجدول `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  ADD CONSTRAINT `fk_attendance_section_v3` FOREIGN KEY (`section_id`) REFERENCES `sections` (`section_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- القيود للجدول `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- القيود للجدول `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `fk_course_level` FOREIGN KEY (`level_id`) REFERENCES `levels` (`level_id`),
  ADD CONSTRAINT `fk_course_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`program_id`) ON DELETE CASCADE;

--
-- القيود للجدول `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`college_id`);

--
-- القيود للجدول `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`section_id`) REFERENCES `sections` (`section_id`) ON DELETE CASCADE;

--
-- القيود للجدول `grades`
--
ALTER TABLE `grades`
  ADD CONSTRAINT `grades_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grades_ibfk_2` FOREIGN KEY (`section_id`) REFERENCES `sections` (`section_id`) ON DELETE CASCADE;

--
-- القيود للجدول `instructors`
--
ALTER TABLE `instructors`
  ADD CONSTRAINT `instructors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `instructors_ibfk_2` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`dept_id`);

--
-- القيود للجدول `levels`
--
ALTER TABLE `levels`
  ADD CONSTRAINT `levels_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `programs` (`program_id`);

--
-- القيود للجدول `mandatory_courses`
--
ALTER TABLE `mandatory_courses`
  ADD CONSTRAINT `mandatory_courses_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `programs` (`program_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mandatory_courses_ibfk_2` FOREIGN KEY (`level_id`) REFERENCES `levels` (`level_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mandatory_courses_ibfk_3` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE;

--
-- القيود للجدول `programs`
--
ALTER TABLE `programs`
  ADD CONSTRAINT `programs_ibfk_1` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`dept_id`);

--
-- القيود للجدول `sections`
--
ALTER TABLE `sections`
  ADD CONSTRAINT `fk_sections_course_v3` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sections_instructor_v3` FOREIGN KEY (`instructor_id`) REFERENCES `instructors` (`instructor_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sections_level_v3` FOREIGN KEY (`level_id`) REFERENCES `levels` (`level_id`) ON DELETE CASCADE;

--
-- القيود للجدول `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `fk_student_dept` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`dept_id`),
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`program_id`) REFERENCES `programs` (`program_id`),
  ADD CONSTRAINT `students_ibfk_3` FOREIGN KEY (`level_id`) REFERENCES `levels` (`level_id`);

--
-- القيود للجدول `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
