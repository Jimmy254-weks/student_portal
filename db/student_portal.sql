-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jun 26, 2025 at 06:21 PM
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
-- Database: `student_portal`
--

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `duration` int(11) DEFAULT NULL COMMENT 'Duration in semesters'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `days`
--

CREATE TABLE `days` (
  `id` int(11) NOT NULL,
  `name` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `days`
--

INSERT INTO `days` (`id`, `name`) VALUES
(1, 'Monday'),
(2, 'Tuesday'),
(3, 'Wednesday'),
(4, 'Thursday'),
(5, 'Friday'),
(6, 'Saturday');

-- --------------------------------------------------------

--
-- Table structure for table `exam_schedule`
--

CREATE TABLE `exam_schedule` (
  `id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `semester` enum('First Semester','Second Semester','Special Semester') NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `exam_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `venue` varchar(100) NOT NULL,
  `instructions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fees`
--

CREATE TABLE `fees` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` varchar(255) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `due_date` date NOT NULL,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `status` enum('pending','partial','paid') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fees`
--

INSERT INTO `fees` (`id`, `student_id`, `amount`, `description`, `semester`, `academic_year`, `due_date`, `paid_amount`, `status`, `created_at`) VALUES
(1, 1, 50000.00, 'Tuition Fee', 'First Semester', '2025/2026', '2025-09-30', 0.00, 'pending', '2025-06-15 20:05:59'),
(2, 1, 15000.00, 'Registration Fee', 'First Semester', '2025/2026', '2025-08-15', 15000.00, 'paid', '2025-06-15 20:05:59'),
(3, 1, 8000.00, 'Library Fee', 'First Semester', '2025/2026', '2025-09-30', 4000.00, 'partial', '2025-06-15 20:05:59'),
(4, 1, 20000.00, 'Hostel Fee', 'First Semester', '2025/2026', '2025-10-15', 0.00, 'pending', '2025-06-15 20:05:59'),
(5, 1, 45000.00, 'Tuition Fee', 'Second Semester', '2024/2025', '2025-03-31', 45000.00, 'paid', '2025-06-15 20:05:59'),
(40, 2, 52000.00, 'Tuition Fee - First Semester', 'First Semester', '2025/2026', '2025-09-30', 26000.00, 'partial', '2025-06-15 20:27:56'),
(41, 2, 12000.00, 'Examination Fee - First Semester', 'First Semester', '2025/2026', '2025-10-15', 12000.00, 'paid', '2025-06-15 20:27:56'),
(42, 2, 5000.00, 'Student Union Fee - First Semester', 'First Semester', '2025/2026', '2025-08-31', 5000.00, 'paid', '2025-06-15 20:27:56'),
(43, 2, 15000.00, 'Hostel Deposit - First Semester', 'First Semester', '2025/2026', '2025-08-20', 15000.00, 'paid', '2025-06-15 20:27:56'),
(44, 2, 8000.00, 'Medical Fee - First Semester', 'First Semester', '2025/2026', '2025-09-15', 0.00, 'pending', '2025-06-15 20:27:56'),
(45, 2, 48000.00, 'Tuition Fee - Second Semester', 'Second Semester', '2024/2025', '2025-02-28', 48000.00, 'paid', '2025-06-15 20:27:56'),
(49, 3, 50000.00, 'Tuition Fee - First Semester', 'First Semester', '2025/2026', '2025-02-01', 50000.00, 'paid', '2025-06-24 12:03:04'),
(50, 3, 15000.00, 'Registration Fee - First Semester', 'First Semester', '2025/2026', '2025-01-15', 15000.00, 'paid', '2025-06-24 12:03:04'),
(51, 3, 50000.00, 'Tuition Fee - Second Semester', 'Second Semester', '2025/2026', '2025-06-01', 50000.00, 'paid', '2025-06-24 12:03:04'),
(52, 3, 15000.00, 'Registration Fee - Second Semester', 'Second Semester', '2025/2026', '2025-05-13', 15000.00, 'paid', '2025-06-24 12:03:04');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `fee_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `reference_number` varchar(50) DEFAULT NULL,
  `receipt_number` varchar(50) DEFAULT NULL,
  `confirmed_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `fee_id`, `amount`, `payment_date`, `payment_method`, `reference_number`, `receipt_number`, `confirmed_by`, `notes`, `created_at`) VALUES
(1, 2, 15000.00, '2025-08-10', 'M-Pesa', 'MPESA123456', 'RCT-20250810-00001', 1, 'Initial registration payment', '2025-06-15 20:05:59'),
(2, 3, 4000.00, '2025-09-15', 'Bank Transfer', 'BANK789012', 'RCT-20250915-00002', 1, 'Partial library payment', '2025-06-15 20:05:59'),
(3, 5, 45000.00, '2025-03-20', 'Bank Transfer', 'BANK345678', 'RCT-20250320-00003', 1, 'Full tuition payment', '2025-06-15 20:05:59'),
(12, 43, 15000.00, '2025-08-10', 'M-Pesa', 'MPESA2A1B2C', 'RCT-20250810-00007', 1, 'Hostel deposit payment', '2025-06-15 20:27:56'),
(13, 42, 5000.00, '2025-08-25', 'M-Pesa', 'MPESA3D4E5F', 'RCT-20250825-00008', 1, 'Student union fee', '2025-06-15 20:27:56'),
(14, 41, 12000.00, '2025-09-05', 'Bank Transfer', 'BANK2X3Y4Z', 'RCT-20250905-00009', 1, 'Examination fee', '2025-06-15 20:27:56'),
(15, 40, 26000.00, '2025-09-20', 'Bank Transfer', 'BANK5A6B7C', 'RCT-20250920-00010', 1, 'First tuition installment', '2025-06-15 20:27:56'),
(16, 45, 48000.00, '2025-02-15', 'Bank Transfer', 'BANK8D9E0F', 'RCT-20250215-00011', 1, 'Full tuition payment', '2025-06-15 20:27:56'),
(20, 49, 50000.00, '2025-01-25', 'M-Pesa', 'MPESA384820', 'RCT-20250125-00049', 1, 'Semester fee payment', '2025-06-24 12:03:04'),
(21, 50, 15000.00, '2025-01-08', 'M-Pesa', 'MPESA132504', 'RCT-20250108-00050', 1, 'Semester fee payment', '2025-06-24 12:03:04'),
(22, 51, 50000.00, '2025-05-25', 'M-Pesa', 'MPESA508059', 'RCT-20250525-00051', 1, 'Semester fee payment', '2025-06-24 12:03:04'),
(23, 52, 15000.00, '2025-05-06', 'M-Pesa', 'MPESA142784', 'RCT-20250506-00052', 1, 'Semester fee payment', '2025-06-24 12:03:04');

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) NOT NULL,
  `duration` int(11) NOT NULL COMMENT 'In semesters',
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`id`, `name`, `code`, `duration`, `description`) VALUES
(1, 'Bachelor of Science in Computer Science', 'BSC-CS', 8, 'Computer Science Degree Program'),
(2, 'Bachelor of Commerce', 'BCOM', 8, 'Commerce Degree Program');

-- --------------------------------------------------------

--
-- Table structure for table `semester_registrations`
--

CREATE TABLE `semester_registrations` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `semester` enum('First Semester','Second Semester','Special Semester') NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `registration_date` datetime NOT NULL DEFAULT current_timestamp(),
  `deadline_date` date NOT NULL,
  `status` enum('pending','approved','rejected','completed') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `semester_registrations`
--

INSERT INTO `semester_registrations` (`id`, `student_id`, `semester`, `academic_year`, `registration_date`, `deadline_date`, `status`, `approved_by`, `approved_at`, `notes`) VALUES
(124, 3, 'First Semester', '2021/2022', '2021-01-05 09:00:00', '2021-01-15', 'approved', 1, '2021-01-05 14:30:00', NULL),
(125, 3, 'Second Semester', '2021/2022', '2021-05-03 09:00:00', '2021-05-13', 'approved', 1, '2021-05-03 14:30:00', NULL),
(126, 3, 'First Semester', '2022/2023', '2022-01-04 09:00:00', '2022-01-14', 'approved', 1, '2022-01-04 14:30:00', NULL),
(127, 3, 'Second Semester', '2022/2023', '2022-05-02 09:00:00', '2022-05-12', 'approved', 1, '2022-05-02 14:30:00', NULL),
(128, 3, 'First Semester', '2023/2024', '2023-01-03 09:00:00', '2023-01-13', 'approved', 1, '2023-01-03 14:30:00', NULL),
(129, 3, 'Second Semester', '2023/2024', '2023-05-01 09:00:00', '2023-05-11', 'approved', 1, '2023-05-01 14:30:00', NULL),
(130, 3, 'First Semester', '2024/2025', '2024-01-02 09:00:00', '2024-01-12', 'approved', 1, '2024-01-02 14:30:00', NULL),
(131, 3, 'Second Semester', '2024/2025', '2024-04-30 09:00:00', '2024-05-10', 'approved', 1, '2024-04-30 14:30:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `admission_no` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `date_of_birth` date NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `county` varchar(50) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT 'default.png',
  `program_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `user_id`, `admission_no`, `first_name`, `last_name`, `gender`, `date_of_birth`, `phone`, `address`, `county`, `profile_image`, `program_id`) VALUES
(1, 1, 'STU00001', 'James', 'Wekesa', 'Male', '1999-07-20', '0723007834', 'Nairobi', 'Bungoma', 'default.png', 1),
(2, 2, 'STU00002', 'Emmanuel', 'Wekesa', 'Male', '2004-06-30', '0745678979', 'Moi avenue, Bungoma', 'Bungoma', 'default.png', 1),
(3, 3, 'STU00003', 'Sharon', 'Njoki', 'Female', '2000-02-01', '0762585987', 'Moi Avenue, Nakuru', 'Eldoret', 'default.png', 2);

-- --------------------------------------------------------

--
-- Table structure for table `student_courses`
--

CREATE TABLE `student_courses` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `enrollment_date` date NOT NULL,
  `completion_date` date DEFAULT NULL,
  `status` enum('active','completed','dropped') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_marks`
--

CREATE TABLE `student_marks` (
  `id` int(11) NOT NULL,
  `registration_id` int(11) NOT NULL COMMENT 'References student_units.id',
  `cat_mark` decimal(5,2) DEFAULT NULL,
  `exam_mark` decimal(5,2) DEFAULT NULL,
  `total_score` decimal(5,2) DEFAULT NULL,
  `grade` varchar(2) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_marks`
--

INSERT INTO `student_marks` (`id`, `registration_id`, `cat_mark`, `exam_mark`, `total_score`, `grade`, `remarks`, `created_at`, `updated_at`) VALUES
(6, 150, NULL, NULL, NULL, 'EX', 'Unit exempted based on prior learning', '2025-06-20 14:29:33', '2025-06-20 14:29:33'),
(35, 436, 20.00, 55.00, 75.00, 'A', 'Strong performance', '2025-06-24 15:28:41', '2025-06-24 15:28:41'),
(36, 437, 18.00, 50.00, 68.00, 'B', 'Consistent', '2025-06-24 15:28:41', '2025-06-24 15:28:41'),
(37, 438, 19.00, 53.00, 72.00, 'A', 'Good application', '2025-06-24 15:28:41', '2025-06-24 15:28:41'),
(38, 442, 22.00, 54.00, 76.00, 'A', 'Excellent', '2025-06-24 15:28:41', '2025-06-24 15:28:41'),
(39, 443, 24.00, 50.00, 74.00, 'A', 'Well done', '2025-06-24 15:28:41', '2025-06-24 15:28:41'),
(40, 444, 21.00, 48.00, 69.00, 'B', 'Strong effort', '2025-06-24 15:28:41', '2025-06-24 15:28:41'),
(41, 445, 23.00, 56.00, 79.00, 'A', 'Outstanding', '2025-06-24 15:28:41', '2025-06-24 15:28:41'),
(42, 446, 20.00, 52.00, 72.00, 'A', 'Very good', '2025-06-24 15:28:41', '2025-06-24 15:28:41'),
(43, 447, 21.00, 50.00, 71.00, 'A', 'Impressive', '2025-06-24 15:28:41', '2025-06-24 15:28:41'),
(44, 448, 25.00, 55.00, 80.00, 'A', 'Outstanding work', '2025-06-24 15:28:41', '2025-06-24 15:28:41'),
(45, 449, 24.00, 52.00, 76.00, 'A', 'Excellent progress', '2025-06-24 15:28:41', '2025-06-24 15:28:41'),
(46, 450, 26.00, 53.00, 79.00, 'A', 'Impressive mastery', '2025-06-24 15:28:41', '2025-06-24 15:28:41'),
(47, 451, 23.00, 51.00, 74.00, 'A', 'Consistent excellence', '2025-06-24 15:28:41', '2025-06-24 15:28:41'),
(48, 452, 22.00, 56.00, 78.00, 'A', 'Strong fundamentals', '2025-06-24 15:28:41', '2025-06-24 15:28:41'),
(49, 453, 24.00, 50.00, 74.00, 'A', 'Well-rounded understanding', '2025-06-24 15:28:41', '2025-06-24 15:28:41');

-- --------------------------------------------------------

--
-- Table structure for table `student_units`
--

CREATE TABLE `student_units` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `day_id` int(11) DEFAULT NULL,
  `timeslot_id` int(11) DEFAULT NULL,
  `semester` varchar(20) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `registration_date` datetime NOT NULL DEFAULT current_timestamp(),
  `schedule_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_units`
--

INSERT INTO `student_units` (`id`, `student_id`, `unit_id`, `day_id`, `timeslot_id`, `semester`, `academic_year`, `registration_date`, `schedule_id`) VALUES
(150, 1, 11, NULL, NULL, 'First Semester', '2025/2026', '2025-06-19 20:28:49', NULL),
(151, 1, 13, NULL, NULL, 'First Semester', '2025/2026', '2025-06-19 20:28:49', NULL),
(152, 1, 15, NULL, NULL, 'First Semester', '2025/2026', '2025-06-19 20:28:49', NULL),
(436, 3, 6, NULL, NULL, 'First Semester', '2022/2023', '2022-01-04 10:00:00', NULL),
(437, 3, 17, NULL, NULL, 'First Semester', '2022/2023', '2022-01-04 10:00:00', NULL),
(438, 3, 7, NULL, NULL, 'First Semester', '2022/2023', '2022-01-04 10:00:00', NULL),
(439, 3, 8, NULL, NULL, 'First Semester', '2022/2023', '2022-01-04 10:00:00', NULL),
(440, 3, 20, NULL, NULL, 'First Semester', '2022/2023', '2022-01-04 10:00:00', NULL),
(441, 3, 22, NULL, NULL, 'First Semester', '2022/2023', '2022-01-04 10:00:00', NULL),
(442, 3, 19, NULL, NULL, 'Second Semester', '2022/2023', '2022-05-02 10:00:00', NULL),
(443, 3, 28, NULL, NULL, 'Second Semester', '2022/2023', '2022-05-02 10:00:00', NULL),
(444, 3, 27, NULL, NULL, 'Second Semester', '2022/2023', '2022-05-02 10:00:00', NULL),
(445, 3, 21, NULL, NULL, 'Second Semester', '2022/2023', '2022-05-02 10:00:00', NULL),
(446, 3, 16, NULL, NULL, 'Second Semester', '2022/2023', '2022-05-02 10:00:00', NULL),
(447, 3, 18, NULL, NULL, 'Second Semester', '2022/2023', '2022-05-02 10:00:00', NULL),
(448, 3, 7, NULL, NULL, 'First Semester', '2023/2024', '2023-01-03 10:00:00', NULL),
(449, 3, 8, NULL, NULL, 'First Semester', '2023/2024', '2023-01-03 10:00:00', NULL),
(450, 3, 19, NULL, NULL, 'First Semester', '2023/2024', '2023-01-03 10:00:00', NULL),
(451, 3, 22, NULL, NULL, 'First Semester', '2023/2024', '2023-01-03 10:00:00', NULL),
(452, 3, 16, NULL, NULL, 'First Semester', '2023/2024', '2023-01-03 10:00:00', NULL),
(453, 3, 20, NULL, NULL, 'First Semester', '2023/2024', '2023-01-03 10:00:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `timeslots`
--

CREATE TABLE `timeslots` (
  `id` int(11) NOT NULL,
  `name` varchar(20) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timeslots`
--

INSERT INTO `timeslots` (`id`, `name`, `start_time`, `end_time`) VALUES
(1, 'Morning', '08:00:00', '11:00:00'),
(2, 'Midday', '11:00:00', '13:00:00'),
(3, 'Afternoon', '14:00:00', '17:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `units`
--

CREATE TABLE `units` (
  `id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `semester` enum('First Semester','Second Semester','Both') NOT NULL,
  `program_id` int(11) DEFAULT NULL,
  `credits` int(11) NOT NULL DEFAULT 3,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `units`
--

INSERT INTO `units` (`id`, `code`, `name`, `description`, `semester`, `program_id`, `credits`, `is_active`) VALUES
(1, 'CS101', 'Introduction to Programming', 'Basic programming concepts', 'First Semester', 1, 3, 1),
(2, 'CS102', 'Data Structures', 'Fundamental data structures', 'Second Semester', 1, 3, 1),
(3, 'CS103', 'Database Systems', 'Introduction to databases', 'First Semester', 1, 3, 1),
(4, 'CS104', 'Computer Networks', 'Networking fundamentals', 'Second Semester', 1, 3, 1),
(5, 'CS105', 'Software Engineering', 'Software development process', 'First Semester', 1, 3, 1),
(6, 'ACC101', 'Financial Accounting', 'Basic accounting principles', 'First Semester', 2, 3, 1),
(7, 'MKT101', 'Marketing Principles', 'Introduction to marketing', 'First Semester', 2, 3, 1),
(8, 'FIN101', 'Business Finance', 'Financial management basics', 'Second Semester', 2, 3, 1),
(9, 'CS201', 'Object-Oriented Programming', 'Advanced OOP concepts', 'First Semester', 1, 3, 1),
(10, 'CS202', 'Algorithms', 'Algorithm design and analysis', 'Second Semester', 1, 3, 1),
(11, 'CS203', 'Operating Systems', 'OS concepts and design', 'First Semester', 1, 3, 1),
(12, 'CS204', 'Computer Architecture', 'Hardware organization', 'Second Semester', 1, 3, 1),
(13, 'CS205', 'Web Development', 'Modern web technologies', 'First Semester', 1, 3, 1),
(14, 'CS206', 'Mobile App Development', 'Cross-platform mobile dev', 'Second Semester', 1, 3, 1),
(15, 'CS207', 'Artificial Intelligence', 'AI fundamentals', 'First Semester', 1, 3, 1),
(16, 'ACC201', 'Managerial Accounting', 'Accounting for managers', 'Second Semester', 2, 3, 1),
(17, 'MKT201', 'Consumer Behavior', 'Understanding customers', 'First Semester', 2, 3, 1),
(18, 'FIN201', 'Investment Analysis', 'Security analysis and valuation', 'Second Semester', 2, 3, 1),
(19, 'BUS201', 'Business Law', 'Legal aspects of business', 'First Semester', 2, 3, 1),
(20, 'ECO201', 'Microeconomics', 'Individual economic behavior', 'Second Semester', 2, 3, 1),
(21, 'ECO202', 'Macroeconomics', 'Economy-wide phenomena', 'First Semester', 2, 3, 1),
(22, 'MGT201', 'Organizational Behavior', 'Human behavior in organizations', 'Second Semester', 2, 3, 1),
(23, 'CS208', 'Data Science Fundamentals', 'Introduction to data analysis and visualization', 'Second Semester', 1, 3, 1),
(24, 'CS209', 'Cybersecurity Basics', 'Information security principles', 'First Semester', 1, 3, 1),
(25, 'CS210', 'Cloud Computing', 'Cloud services and architecture', 'Second Semester', 1, 3, 1),
(26, 'CS211', 'Software Project Management', 'Managing software development projects', 'Both', 1, 3, 1),
(27, 'MKT202', 'Digital Marketing', 'Online marketing strategies', 'Both', 2, 3, 1),
(28, 'FIN202', 'Risk Management', 'Financial risk assessment', 'First Semester', 2, 3, 1),
(29, 'BUS202', 'Entrepreneurship', 'Starting and managing businesses', 'Second Semester', 2, 3, 1),
(30, 'ACC202', 'Taxation', 'Principles of taxation', 'Both', 2, 3, 1);

-- --------------------------------------------------------

--
-- Table structure for table `university`
--

CREATE TABLE `university` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `country` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `paybill_number` varchar(20) NOT NULL,
  `bank_name` varchar(100) NOT NULL,
  `bank_account_name` varchar(100) NOT NULL,
  `bank_account_number` varchar(50) NOT NULL,
  `logo_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `university`
--

INSERT INTO `university` (`id`, `name`, `address`, `city`, `country`, `phone`, `email`, `paybill_number`, `bank_name`, `bank_account_name`, `bank_account_number`, `logo_path`) VALUES
(1, 'University of Nairobi', 'University Way', 'Nairobi', 'Kenya', '+254 20 491 0000', 'info@uonbi.ac.ke', '123456', 'Kenya Commercial Bank', 'UoN Fees Account', '1234567890', NULL),
(2, 'University of Nairobi', 'University Way', 'Nairobi', 'Kenya', '+254 20 491 0000', 'info@uonbi.ac.ke', '123456', 'Kenya Commercial Bank', 'UoN Fees Account', '1234567890', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','admin','superadmin','registrar') DEFAULT 'student',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`, `last_login`, `is_active`) VALUES
(1, '1036637', 'jamesweks2019@gmail.com', '$2y$10$gyq6zZgVW0Uv1qWCXeHi2OTy3EkB8ynshtVTmrH9WIGxLJrP/jWnq', 'student', '2025-05-23 20:40:21', '2025-06-24 15:56:30', 1),
(2, '1036638', 'jameswekesa002@gmail.com', '$2y$10$ysS41ZCQx/pbiRGXjVBKOetolB0EGDGJHu01VweIJwutmu/qHVcHK', 'student', '2025-05-23 21:12:25', '2025-06-19 19:29:54', 1),
(3, '1036639', 'sharonnjoki@gmail.com', '$2y$10$qpWZWBeYEB.yZdAEoO6zeuWdR5LQZkMtTr1H2doJXHl5b2TP7kilW', 'student', '2025-06-15 21:32:35', '2025-06-23 18:32:46', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `days`
--
ALTER TABLE `days`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `exam_schedule`
--
ALTER TABLE `exam_schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `unit_id` (`unit_id`);

--
-- Indexes for table `fees`
--
ALTER TABLE `fees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receipt_number` (`receipt_number`),
  ADD KEY `fee_id` (`fee_id`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `semester_registrations`
--
ALTER TABLE `semester_registrations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admission_no` (`admission_no`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `student_courses`
--
ALTER TABLE `student_courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `student_marks`
--
ALTER TABLE `student_marks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `registration_id` (`registration_id`);

--
-- Indexes for table `student_units`
--
ALTER TABLE `student_units`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_registration` (`student_id`,`unit_id`,`semester`,`academic_year`),
  ADD KEY `unit_id` (`unit_id`),
  ADD KEY `student_units_ibfk_3` (`schedule_id`),
  ADD KEY `fk_student_units_day` (`day_id`),
  ADD KEY `fk_student_units_timeslot` (`timeslot_id`);

--
-- Indexes for table `timeslots`
--
ALTER TABLE `timeslots`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `university`
--
ALTER TABLE `university`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `days`
--
ALTER TABLE `days`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `exam_schedule`
--
ALTER TABLE `exam_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fees`
--
ALTER TABLE `fees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `semester_registrations`
--
ALTER TABLE `semester_registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=132;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `student_courses`
--
ALTER TABLE `student_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_marks`
--
ALTER TABLE `student_marks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `student_units`
--
ALTER TABLE `student_units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=454;

--
-- AUTO_INCREMENT for table `timeslots`
--
ALTER TABLE `timeslots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `units`
--
ALTER TABLE `units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `university`
--
ALTER TABLE `university`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `exam_schedule`
--
ALTER TABLE `exam_schedule`
  ADD CONSTRAINT `exam_schedule_ibfk_1` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fees`
--
ALTER TABLE `fees`
  ADD CONSTRAINT `fees_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`fee_id`) REFERENCES `fees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `semester_registrations`
--
ALTER TABLE `semester_registrations`
  ADD CONSTRAINT `semester_registrations_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_courses`
--
ALTER TABLE `student_courses`
  ADD CONSTRAINT `student_courses_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_courses_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_marks`
--
ALTER TABLE `student_marks`
  ADD CONSTRAINT `student_marks_ibfk_1` FOREIGN KEY (`registration_id`) REFERENCES `student_units` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_units`
--
ALTER TABLE `student_units`
  ADD CONSTRAINT `fk_student_units_day` FOREIGN KEY (`day_id`) REFERENCES `days` (`id`),
  ADD CONSTRAINT `fk_student_units_timeslot` FOREIGN KEY (`timeslot_id`) REFERENCES `timeslots` (`id`),
  ADD CONSTRAINT `student_units_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_units_ibfk_2` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_units_ibfk_3` FOREIGN KEY (`schedule_id`) REFERENCES `unit_schedules` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
