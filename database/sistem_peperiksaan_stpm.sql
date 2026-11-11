-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 18, 2026 at 11:05 AM
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
-- Database: `sistem_peperiksaan_stpm`
--
CREATE DATABASE IF NOT EXISTS `sistem_peperiksaan_stpm` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `sistem_peperiksaan_stpm`;


-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `admin_id` varchar(20) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `admin_level` enum('admin','super_admin') DEFAULT 'admin',
  `phone` varchar(20) DEFAULT NULL,
  `status` enum('Aktif','Tidak Aktif') DEFAULT 'Aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `user_id`, `admin_id`, `full_name`, `admin_level`, `phone`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'AD001', 'Zakiah binti Zakaria', 'super_admin', '0123456789', 'Aktif', '2026-04-13 15:26:03', '2026-04-15 04:24:29'),
(2, 6, 'AD002', 'Admin Biasa', 'admin', '0198765432', 'Aktif', '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(100, 131, 'AD100', 'khai', 'admin', '0147531597', 'Aktif', '2026-04-18 02:48:59', '2026-04-18 02:48:59');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `target` enum('all','students','teachers','admin') DEFAULT 'all',
  `class_id` int(11) DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT 0,
  `publish_date` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `target`, `class_id`, `is_published`, `publish_date`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Tarikh Peperiksaan Akhir Tahun', 'Peperiksaan akhir tahun akan diadakan pada 20 Oktober 2024. Sila buat persediaan awal.', 'all', NULL, 1, '2026-04-13', 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(2, 'Keputusan Peperiksaan Pertengahan Tahun', 'Keputusan peperiksaan pertengahan tahun telah dikeluarkan. Sila semak di panel pelajar.', 'students', NULL, 1, '2026-04-13', 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(3, 'Mesyuarat Guru', 'Mesyuarat guru akan diadakan pada 15 September 2024 jam 2.30 petang di Bilik Mesyuarat.', 'teachers', NULL, 1, '2026-04-13', 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03');

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `class_name` varchar(50) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `status` enum('Aktif','Tidak Aktif') DEFAULT 'Aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `class_name`, `teacher_id`, `status`, `created_at`, `updated_at`) VALUES
(1, '6 AL KHALID', 105, 'Aktif', '2026-04-13 15:26:03', '2026-04-17 18:43:31'),
(2, '6 AL HAKAM', 2, 'Aktif', '2026-04-13 15:26:03', '2026-04-18 09:04:32'),
(3, '6 AL RAZAQ', 1, 'Aktif', '2026-04-13 15:26:03', '2026-04-17 18:53:29'),
(4, '6 AL QUDDUS', 102, 'Aktif', '2026-04-13 15:26:03', '2026-04-17 18:48:56'),
(100, '6 AL FATAH', 2, 'Aktif', '2026-04-14 15:20:23', '2026-04-15 02:45:11'),
(101, '6 AL MALIK', 108, 'Aktif', '2026-04-15 04:19:24', '2026-04-17 18:48:27');

-- --------------------------------------------------------

--
-- Table structure for table `class_subjects`
--

CREATE TABLE `class_subjects` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `semester` int(11) DEFAULT 1,
  `status` enum('Aktif','Tidak Aktif') DEFAULT 'Aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_subjects`
--

INSERT INTO `class_subjects` (`id`, `class_id`, `subject_id`, `teacher_id`, `semester`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1, 'Aktif', '2026-04-13 15:26:03', '2026-04-17 19:14:49'),
(2, 1, 2, 2, 1, 'Aktif', '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(3, 1, 3, 1, 1, 'Aktif', '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(4, 1, 5, 1, 1, 'Aktif', '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(5, 1, 6, 1, 1, 'Aktif', '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(6, 1, 10, 2, 1, 'Aktif', '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(7, 100, 10, 1, 1, 'Aktif', '2026-04-16 01:12:44', '2026-04-16 01:12:44'),
(8, 2, 10, 2, 1, 'Aktif', '2026-04-16 01:54:42', '2026-04-16 01:54:42'),
(9, 101, 10, 2, 1, 'Aktif', '2026-04-16 01:54:56', '2026-04-16 01:54:56'),
(10, 4, 10, 2, 1, 'Aktif', '2026-04-16 01:55:11', '2026-04-16 01:55:11'),
(11, 3, 10, 2, 1, 'Aktif', '2026-04-16 01:55:43', '2026-04-16 01:55:43'),
(12, 1, 9, 108, 1, 'Aktif', '2026-04-17 19:10:07', '2026-04-17 19:10:07'),
(13, 101, 9, 108, 1, 'Tidak Aktif', '2026-04-17 19:10:42', '2026-04-17 19:22:25'),
(14, 3, 101, 106, 1, 'Aktif', '2026-04-17 19:11:26', '2026-04-17 19:11:26'),
(15, 2, 9, 105, 1, 'Aktif', '2026-04-17 19:11:51', '2026-04-17 19:11:51'),
(16, 2, 100, 100, 2, 'Aktif', '2026-04-18 08:48:15', '2026-04-18 08:48:15');

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `id` int(11) NOT NULL,
  `exam_type_id` int(11) NOT NULL,
  `exam_name` varchar(200) NOT NULL,
  `semester` int(11) DEFAULT NULL,
  `academic_year` varchar(10) DEFAULT NULL,
  `exam_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `venue` varchar(200) DEFAULT NULL,
  `total_marks` int(11) DEFAULT 100,
  `passing_marks` int(11) DEFAULT 50,
  `status` enum('Draf','Aktif','Selesai','Batal') DEFAULT 'Draf',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exams`
--

INSERT INTO `exams` (`id`, `exam_type_id`, `exam_name`, `semester`, `academic_year`, `exam_date`, `start_time`, `end_time`, `venue`, `total_marks`, `passing_marks`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 3, 'Ujian Bulanan 1', 1, '2024/2025', '2024-02-15', '08:00:00', '10:00:00', 'Bilik Darjah 6 Sains 1', 100, 50, 'Selesai', 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(2, 3, 'Ujian Bulanan 2', 1, '2024/2025', '2024-03-15', '08:00:00', '10:00:00', 'Bilik Darjah 6 Sains 1', 100, 50, 'Selesai', 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(3, 1, 'Peperiksaan Pertengahan Tahun', 1, '2024/2025', '2024-05-20', '08:00:00', '11:00:00', 'Dewan Utama', 100, 50, 'Selesai', 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(6, 2, 'Peperiksaan Akhir Tahun', 2, '2024/2025', '2024-10-20', '08:00:00', '11:00:00', 'Dewan Utama', 100, 50, 'Aktif', 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(10, 4, 'Peperiksaan Percubaan', 3, '2024/2025', '2024-09-15', '08:00:00', '11:00:00', 'Dewan Utama', 100, 50, 'Aktif', 1, '2026-04-13 15:31:14', '2026-04-13 15:31:14'),
(101, 2, 'PEPERIKSAAN ULANGAN', 1, '2025/2026', '2026-04-30', '20:00:00', '22:15:00', 'Bilik Darjah', 100, 40, 'Aktif', 100, '2026-04-18 07:44:08', '2026-04-18 07:44:08');

-- --------------------------------------------------------

--
-- Table structure for table `exam_types`
--

CREATE TABLE `exam_types` (
  `id` int(11) NOT NULL,
  `exam_name` varchar(100) NOT NULL,
  `exam_code` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `exam_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_types`
--

INSERT INTO `exam_types` (`id`, `exam_name`, `exam_code`, `description`, `exam_order`, `is_active`, `created_at`) VALUES
(1, 'Peperiksaan Pertengahan Tahun', 'PPT', 'Peperiksaan pertengahan tahun', 1, 1, '2026-04-13 15:26:03'),
(2, 'Peperiksaan Akhir Tahun', 'PAT', 'Peperiksaan akhir tahun', 2, 1, '2026-04-13 15:26:03'),
(3, 'Ujian Bulanan', 'UB', 'Ujian bulanan', 3, 1, '2026-04-13 15:26:03'),
(4, 'Peperiksaan Percubaan', 'PERC', 'Peperiksaan percubaan STPM', 4, 1, '2026-04-13 15:26:03'),
(5, 'Kuiz', 'KUIZ', 'Kuiz mingguan', 5, 1, '2026-04-13 15:31:14'),
(6, 'Tugasan', 'TGS', 'Tugasan projek', 6, 1, '2026-04-13 15:31:14');

-- --------------------------------------------------------

--
-- Table structure for table `marks`
--

CREATE TABLE `marks` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `semester` int(11) NOT NULL,
  `exam_id` int(11) DEFAULT NULL,
  `marks` decimal(5,2) DEFAULT NULL,
  `grade` char(2) DEFAULT NULL,
  `pointer` decimal(3,2) DEFAULT NULL,
  `entered_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `marks`
--

INSERT INTO `marks` (`id`, `student_id`, `subject_id`, `class_id`, `semester`, `exam_id`, `marks`, `grade`, `pointer`, `entered_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1, 1, 85.00, 'A', 4.00, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(2, 1, 2, 1, 1, 1, 78.00, 'A-', 3.67, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(3, 1, 3, 1, 1, 1, 92.00, 'A', 4.00, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(4, 1, 5, 1, 1, 1, 88.00, 'A', 4.00, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(5, 1, 6, 1, 1, 1, 75.00, 'A-', 3.67, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(6, 1, 10, 1, 1, 1, 82.00, 'A', 4.00, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(7, 2, 1, 1, 1, 1, 75.00, 'A-', 3.67, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(8, 2, 2, 1, 1, 1, 82.00, 'A', 4.00, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(9, 2, 3, 1, 1, 1, 78.00, 'A-', 3.67, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(10, 2, 5, 1, 1, 1, 72.00, 'B+', 3.33, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(11, 2, 6, 1, 1, 1, 80.00, 'A', 4.00, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(12, 2, 10, 1, 1, 1, 88.00, 'A', 4.00, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(13, 1, 1, 1, 1, 2, 88.00, 'A', 4.00, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(14, 1, 2, 1, 1, 2, 82.00, 'A', 4.00, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(15, 1, 3, 1, 1, 2, 95.00, 'A', 4.00, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(16, 1, 5, 1, 1, 2, 90.00, 'A', 4.00, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(17, 1, 6, 1, 1, 2, 78.00, 'A-', 3.67, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(18, 1, 10, 1, 1, 2, 85.00, 'A', 4.00, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(19, 2, 1, 1, 1, 2, 78.00, 'A-', 3.67, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(20, 2, 2, 1, 1, 2, 85.00, 'A', 4.00, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(21, 2, 3, 1, 1, 2, 82.00, 'A', 4.00, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(22, 2, 5, 1, 1, 2, 75.00, 'A-', 3.67, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(23, 2, 6, 1, 1, 2, 78.00, 'A-', 3.67, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(24, 2, 10, 1, 1, 2, 90.00, 'A', 4.00, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(25, 1, 1, 1, 1, 3, 90.00, 'A', 4.00, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(26, 1, 2, 1, 1, 3, 85.00, 'A', 4.00, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(27, 1, 3, 1, 1, 3, 98.00, 'A', 4.00, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(28, 1, 5, 1, 1, 3, 92.00, 'A', 4.00, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(29, 1, 6, 1, 1, 3, 80.00, 'A', 4.00, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(30, 1, 10, 1, 1, 3, 88.00, 'A', 4.00, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(31, 2, 1, 1, 1, 3, 80.00, 'A', 4.00, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(32, 2, 2, 1, 1, 3, 88.00, 'A', 4.00, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(33, 2, 3, 1, 1, 3, 85.00, 'A', 4.00, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(34, 2, 5, 1, 1, 3, 78.00, 'A-', 3.67, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(35, 2, 6, 1, 1, 3, 82.00, 'A', 4.00, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(36, 2, 10, 1, 1, 3, 92.00, 'A', 4.00, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(37, 1, 1, 1, 2, NULL, 82.00, 'A', 4.00, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(38, 1, 2, 1, 2, NULL, 80.00, 'A', 4.00, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(39, 1, 3, 1, 2, NULL, 88.00, 'A', 4.00, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(40, 1, 5, 1, 2, NULL, 85.00, 'A', 4.00, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(41, 1, 6, 1, 2, NULL, 76.00, 'A-', 3.67, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(42, 1, 10, 1, 2, NULL, 80.00, 'A', 4.00, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(43, 2, 1, 1, 2, NULL, 76.00, 'A-', 3.67, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(44, 2, 2, 1, 2, NULL, 78.00, 'A-', 3.67, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(45, 2, 3, 1, 2, NULL, 75.00, 'A-', 3.67, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(46, 2, 5, 1, 2, NULL, 70.00, 'B+', 3.33, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(47, 2, 6, 1, 2, NULL, 74.00, 'B+', 3.33, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(48, 2, 10, 1, 2, NULL, 82.00, 'A', 4.00, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(49, 1, 1, 1, 2, NULL, 85.00, 'A', 4.00, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(50, 1, 2, 1, 2, NULL, 82.00, 'A', 4.00, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(51, 1, 3, 1, 2, NULL, 90.00, 'A', 4.00, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(52, 1, 5, 1, 2, NULL, 88.00, 'A', 4.00, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(53, 1, 6, 1, 2, NULL, 78.00, 'A-', 3.67, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(54, 1, 10, 1, 2, NULL, 85.00, 'A', 4.00, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(55, 2, 1, 1, 2, NULL, 78.00, 'A-', 3.67, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(56, 2, 2, 1, 2, NULL, 80.00, 'A', 4.00, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(57, 2, 3, 1, 2, NULL, 78.00, 'A-', 3.67, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(58, 2, 5, 1, 2, NULL, 72.00, 'B+', 3.33, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(59, 2, 6, 1, 2, NULL, 76.00, 'A-', 3.67, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(60, 2, 10, 1, 2, NULL, 85.00, 'A', 4.00, 1, '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(100, 1, 3, 1, 1, 6, 70.00, 'B+', 3.33, 1, '2026-04-13 15:41:33', '2026-04-13 15:41:33'),
(101, 1, 10, 1, 1, 10, 70.00, 'B+', 3.33, 1, '2026-04-16 01:30:20', '2026-04-16 01:30:20'),
(102, 100, 10, 1, 1, 10, 70.00, 'B+', 3.33, 1, '2026-04-16 01:30:25', '2026-04-16 01:30:25'),
(103, 2, 10, 1, 1, 10, 45.00, 'D', 0.00, 1, '2026-04-16 01:30:32', '2026-04-16 01:30:32'),
(104, 101, 10, 100, 1, 6, 50.00, 'C', 2.00, 1, '2026-04-16 01:39:03', '2026-04-16 01:39:03'),
(105, 101, 10, 100, 1, 10, 60.00, 'C+', 2.67, 1, '2026-04-16 02:08:28', '2026-04-16 02:08:28'),
(106, 1, 10, 1, 1, 6, 90.00, 'A', 4.00, 1, '2026-04-17 16:02:27', '2026-04-17 16:03:21'),
(107, 100, 10, 1, 1, 6, 85.00, 'A', 4.00, 1, '2026-04-17 16:02:42', '2026-04-17 16:02:48'),
(108, 100, 10, 1, 2, 10, 85.00, 'A', 4.00, 1, '2026-04-17 16:03:47', '2026-04-17 16:03:47'),
(109, 1, 10, 1, 2, 10, 90.00, 'A', 4.00, 1, '2026-04-17 16:06:06', '2026-04-17 16:06:06'),
(110, 2, 10, 1, 1, 6, 52.00, 'C', 2.00, 1, '2026-04-17 16:07:17', '2026-04-17 16:07:17'),
(111, 1, 10, 1, 3, 6, 80.00, 'A', 4.00, 1, '2026-04-17 16:13:49', '2026-04-17 16:13:49'),
(112, 100, 10, 1, 3, 6, 86.00, 'A', 4.00, 1, '2026-04-17 16:13:56', '2026-04-17 16:13:56'),
(113, 2, 10, 1, 3, 6, 84.00, 'A', 4.00, 1, '2026-04-17 16:14:12', '2026-04-17 16:14:12'),
(114, 1, 10, 1, 3, 10, 75.00, 'A-', 3.67, 1, '2026-04-17 16:18:10', '2026-04-17 16:18:10');

--
-- Triggers `marks`
--
DELIMITER $$
CREATE TRIGGER `before_mark_insert` BEFORE INSERT ON `marks` FOR EACH ROW BEGIN
        IF NEW.marks IS NOT NULL THEN IF NEW.marks >= 80 THEN
    SET NEW
        .grade = 'A',
        NEW.pointer = 4.00 ; ELSEIF NEW.marks >= 75 THEN
    SET NEW
        .grade = 'A-',
        NEW.pointer = 3.67 ; ELSEIF NEW.marks >= 70 THEN
    SET NEW
        .grade = 'B+',
        NEW.pointer = 3.33 ; ELSEIF NEW.marks >= 65 THEN
    SET NEW
        .grade = 'B',
        NEW.pointer = 3.00 ; ELSEIF NEW.marks >= 60 THEN
    SET NEW
        .grade = 'C+',
        NEW.pointer = 2.67 ; ELSEIF NEW.marks >= 50 THEN
    SET NEW
        .grade = 'C',
        NEW.pointer = 2.00 ; ELSE
    SET NEW
        .grade = 'D',
        NEW.pointer = 0.00 ;
    END IF ;
    END IF ;
    END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_mark_update` BEFORE UPDATE ON `marks` FOR EACH ROW BEGIN
        IF NEW.marks IS NOT NULL THEN IF NEW.marks >= 80 THEN
    SET NEW
        .grade = 'A',
        NEW.pointer = 4.00 ; ELSEIF NEW.marks >= 75 THEN
    SET NEW
        .grade = 'A-',
        NEW.pointer = 3.67 ; ELSEIF NEW.marks >= 70 THEN
    SET NEW
        .grade = 'B+',
        NEW.pointer = 3.33 ; ELSEIF NEW.marks >= 65 THEN
    SET NEW
        .grade = 'B',
        NEW.pointer = 3.00 ; ELSEIF NEW.marks >= 60 THEN
    SET NEW
        .grade = 'C+',
        NEW.pointer = 2.67 ; ELSEIF NEW.marks >= 50 THEN
    SET NEW
        .grade = 'C',
        NEW.pointer = 2.00 ; ELSE
    SET NEW
        .grade = 'D',
        NEW.pointer = 0.00 ;
    END IF ;
END IF ;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_questions`
--

CREATE TABLE `security_questions` (
  `id` int(11) NOT NULL,
  `question` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `security_questions`
--

INSERT INTO `security_questions` (`id`, `question`, `is_active`, `created_at`) VALUES
(1, 'Apakah nama ibu anda?', 1, '2026-04-13 15:26:03'),
(2, 'Apakah nama bapa anda?', 1, '2026-04-13 15:26:03'),
(3, 'Apakah nama sekolah rendah anda?', 1, '2026-04-13 15:26:03'),
(4, 'Apakah nama haiwan peliharaan anda?', 1, '2026-04-13 15:26:03'),
(5, 'Apakah nama bandar kelahiran anda?', 1, '2026-04-13 15:26:03'),
(6, 'Apakah makanan kegemaran anda?', 1, '2026-04-13 15:26:03'),
(7, 'Apakah warna kegemaran anda?', 1, '2026-04-13 15:26:03'),
(8, 'Apakah nama jalan rumah anda?', 1, '2026-04-13 15:29:27'),
(9, 'Apakah nama rakan baik anda?', 1, '2026-04-13 15:29:27'),
(10, 'Apakah cita-cita anda?', 1, '2026-04-13 15:29:27'),
(11, 'Apakah nama filem kegemaran anda?', 1, '2026-04-13 15:29:27'),
(12, 'Apakah nama buku kegemaran anda?', 1, '2026-04-13 15:29:27');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `matric_no` varchar(20) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `ic_number` varchar(20) DEFAULT NULL,
  `class_id` int(11) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` enum('Aktif','Tidak Aktif') DEFAULT 'Aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `user_id`, `matric_no`, `full_name`, `ic_number`, `class_id`, `phone`, `address`, `status`, `created_at`, `updated_at`) VALUES
(1, 4, 'STPM2024001', 'Ahmad Faiz bin Razak', '010101010101', 1, '0123456789', 'No. 1, Jalan 1, Taman Perdana, 43000 Kajang, Selangor', 'Aktif', '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(2, 5, 'STPM2024002', 'Nur Aisyah binti Mohd', '020202020202', 1, '0198765432', 'No. 2, Jalan 2, Taman Mutiara, 43000 Kajang, Selangor', 'Aktif', '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(100, 100, 'STPM2026001', 'MUHAMMAD SHAHRUL FITRI', '051027030307', 1, '0133852962', 'POLIBESUT', 'Aktif', '2026-04-15 02:48:23', '2026-04-15 02:48:23'),
(101, 101, 'STPM2026007', 'NUR AZIERA SOFIA BINTI SOFIAN', '020728030316', 100, '0133860649', 'politeknik besut\r\njalan kampung raja', 'Aktif', '2026-04-15 03:16:47', '2026-04-15 03:16:47'),
(102, 102, 'STPM2026008', 'khai', '050718030467', 100, '0172513686', 'lot864,kgbukit tanah', 'Aktif', '2026-04-17 02:24:10', '2026-04-17 02:24:10'),
(103, 129, 'STPM2026012', 'Muhammad Danish', '040973050763', 100, '0135649513', NULL, 'Aktif', '2026-04-18 02:26:25', '2026-04-18 03:25:53');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `subject_code` varchar(20) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `stream` varchar(50) DEFAULT NULL,
  `status` enum('Aktif','Tidak Aktif') DEFAULT 'Aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `stream`, `status`, `created_at`, `updated_at`) VALUES
(1, '9001', 'Bahasa Melayu', 'Semua', 'Aktif', '2026-04-13 15:26:03', '2026-04-17 18:12:24'),
(2, '9002', 'Bahasa Inggeris', 'Sastera', 'Tidak Aktif', '2026-04-13 15:26:03', '2026-04-14 14:48:12'),
(3, '9003', 'Mathematics T', 'Sains', 'Tidak Aktif', '2026-04-13 15:26:03', '2026-04-14 14:48:06'),
(4, '9004', 'Mathematics S', 'Sastera', 'Tidak Aktif', '2026-04-13 15:26:03', '2026-04-14 14:47:34'),
(5, '9005', 'Physics', 'Sains', 'Tidak Aktif', '2026-04-13 15:26:03', '2026-04-14 14:47:58'),
(6, '9006', 'Chemistry', 'Sains', 'Tidak Aktif', '2026-04-13 15:26:03', '2026-04-14 14:47:52'),
(7, '9007', 'Biology', 'Sains', 'Tidak Aktif', '2026-04-13 15:26:03', '2026-04-14 14:47:46'),
(8, '9008', 'Sejarah', 'Semua', 'Aktif', '2026-04-13 15:26:03', '2026-04-17 18:09:52'),
(9, '9009', 'Ekonomi', 'Sastera', 'Aktif', '2026-04-13 15:26:03', '2026-04-13 15:26:03'),
(10, '9010', 'Pengajian Am', 'Semua', 'Aktif', '2026-04-13 15:26:03', '2026-04-17 19:36:03'),
(100, '9012', 'geografi', 'Sastera', 'Aktif', '2026-04-14 14:49:03', '2026-04-14 14:49:03'),
(101, '9014', 'Kesusteraan Melayu Klasik', 'Sastera', 'Aktif', '2026-04-14 14:50:07', '2026-04-14 14:50:07'),
(102, '9018', 'Seni Visual', 'Teknikal', 'Tidak Aktif', '2026-04-14 14:50:47', '2026-04-18 07:44:49'),
(103, '9020', 'Seni Visual', 'Sastera', 'Aktif', '2026-04-18 07:45:05', '2026-04-18 07:45:05');

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_logs`
--

INSERT INTO `system_logs` (`id`, `user_id`, `action`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'LOGIN', 'Super Admin log masuk', '127.0.0.1', 'System', '2026-04-13 15:26:03'),
(2, 1, 'ADD_STUDENT', 'Menambah pelajar: STPM2024001 - Ahmad Faiz', '127.0.0.1', 'System', '2026-04-13 15:26:03'),
(3, 1, 'ADD_STUDENT', 'Menambah pelajar: STPM2024002 - Nur Aisyah', '127.0.0.1', 'System', '2026-04-13 15:26:03'),
(4, 1, 'ADD_TEACHER', 'Menambah guru: GU001 - En. Ahmad bin Abdullah', '127.0.0.1', 'System', '2026-04-13 15:26:03'),
(5, 1, 'ADD_TEACHER', 'Menambah guru: GU002 - Pn. Siti binti Hassan', '127.0.0.1', 'System', '2026-04-13 15:26:03'),
(6, 1, 'ADD_SUBJECT', 'Menambah subjek: 9001 - Bahasa Melayu', '127.0.0.1', 'System', '2026-04-13 15:26:03'),
(7, 1, 'ADD_EXAM', 'Menambah peperiksaan: Peperiksaan Pertengahan Tahun', '127.0.0.1', 'System', '2026-04-13 15:26:03'),
(8, 1, 'ADD_EXAM', 'Menambah peperiksaan: Peperiksaan Akhir Tahun', '127.0.0.1', 'System', '2026-04-13 15:26:03'),
(9, 1, 'SAVE_MARK', 'Menyimpan markah untuk pelajar', '127.0.0.1', 'System', '2026-04-13 15:26:03'),
(100, 2, 'PASSWORD_RESET', 'Kata laluan telah direset melalui soalan keselamatan', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-13 15:40:07'),
(101, 2, 'LOGIN', 'Pengguna log masuk sebagai guru', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-13 15:40:21'),
(102, 2, 'SAVE_MARK', 'Menyimpan markah untuk pelajar ID: 1, subjek ID: 3, exam ID: 6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-13 15:41:33'),
(103, 2, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-13 15:41:55'),
(104, 1, 'PASSWORD_RESET', 'Kata laluan telah direset melalui soalan keselamatan', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-13 15:43:09'),
(105, 6, 'PASSWORD_RESET', 'Kata laluan telah direset melalui soalan keselamatan', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-13 15:43:36'),
(106, 3, 'PASSWORD_RESET', 'Kata laluan telah direset melalui soalan keselamatan', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-13 15:44:00'),
(107, 4, 'PASSWORD_RESET', 'Kata laluan telah direset melalui soalan keselamatan', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-13 15:44:24'),
(108, 5, 'PASSWORD_RESET', 'Kata laluan telah direset melalui soalan keselamatan', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-13 15:44:59'),
(109, 6, 'LOGIN', 'Pengguna log masuk sebagai admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-13 15:45:17'),
(110, 6, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-13 15:46:16'),
(111, 1, 'LOGIN', 'Pengguna log masuk sebagai admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-13 15:46:32'),
(112, 1, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-13 16:11:33'),
(113, 2, 'LOGIN', 'Pengguna log masuk sebagai guru', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-13 16:11:42'),
(114, 2, 'UPDATE_TEACHER_SUBJECTS', 'Guru ID: 1 mengemaskini subjek: Sejarah, kelas: 6 Sains 1, 6 Sains 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-13 16:12:33'),
(115, 2, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-13 16:13:30'),
(116, 1, 'LOGIN', 'Pengguna log masuk sebagai admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 14:44:42'),
(117, 1, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 14:45:10'),
(118, 4, 'LOGIN', 'Pengguna log masuk sebagai pelajar', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 14:45:22'),
(119, 4, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 14:47:07'),
(120, 6, 'LOGIN', 'Pengguna log masuk sebagai admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 14:47:23'),
(121, 6, 'DELETE_SUBJECT', 'Menamatkan subjek ID: 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 14:47:34'),
(122, 6, 'DELETE_SUBJECT', 'Menamatkan subjek ID: 7', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 14:47:46'),
(123, 6, 'DELETE_SUBJECT', 'Menamatkan subjek ID: 6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 14:47:52'),
(124, 6, 'DELETE_SUBJECT', 'Menamatkan subjek ID: 5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 14:47:58'),
(125, 6, 'DELETE_SUBJECT', 'Menamatkan subjek ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 14:48:06'),
(126, 6, 'DELETE_SUBJECT', 'Menamatkan subjek ID: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 14:48:12'),
(127, 6, 'ADD_SUBJECT', 'Menambah subjek: 9012 - geografi', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 14:49:03'),
(128, 6, 'ADD_SUBJECT', 'Menambah subjek: 9014 - Kesusteraan Melayu Klasik', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 14:50:07'),
(129, 6, 'ADD_SUBJECT', 'Menambah subjek: 9018 - Seni Visual', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 14:50:47'),
(130, 6, 'ADD_CLASS', 'Menambah kelas: AL FATAH', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 15:20:23'),
(131, 6, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 15:27:36'),
(132, 2, 'LOGIN', 'Pengguna log masuk sebagai guru', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 15:27:56'),
(133, 2, 'UPDATE_TEACHER_SUBJECTS', 'Guru ID: 1 mengemaskini subjek: geografi, kelas: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 15:28:24'),
(134, 2, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 15:28:41'),
(135, 6, 'LOGIN', 'Pengguna log masuk sebagai admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 15:31:55'),
(136, 3, 'LOGIN', 'Pengguna log masuk sebagai guru', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 00:51:02'),
(137, 3, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 01:03:51'),
(138, 1, 'LOGIN', 'Pengguna log masuk sebagai admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 01:04:07'),
(139, 1, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 01:04:36'),
(140, 6, 'LOGIN', 'Pengguna log masuk sebagai admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 01:04:50'),
(141, 6, 'EDIT_CLASS', 'Mengemaskini kelas ID: 100 kepada: 6 AL FATAH', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 02:45:11'),
(142, 6, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 02:46:42'),
(143, 100, 'LOGIN', 'Pengguna log masuk sebagai pelajar', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 02:48:43'),
(144, 100, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 02:51:39'),
(145, 3, 'LOGIN', 'Pengguna log masuk sebagai guru', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 02:52:07'),
(146, 3, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 02:52:30'),
(147, 101, 'LOGIN', 'Pengguna log masuk sebagai pelajar', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 03:18:07'),
(148, 101, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 03:22:22'),
(149, 6, 'LOGIN', 'Pengguna log masuk sebagai admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 03:23:05'),
(150, 6, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 03:26:05'),
(151, 101, 'LOGIN', 'Pengguna log masuk sebagai pelajar', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 03:26:18'),
(152, 101, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 03:56:05'),
(153, 1, 'LOGIN', 'Pengguna log masuk sebagai admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 04:07:23'),
(154, 1, 'ADD_CLASS', 'Menambah kelas: AL MAllik', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 04:19:24'),
(155, 1, 'EDIT_CLASS', 'Mengemaskini kelas ID: 101 kepada: AL MALIK', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 04:19:39'),
(156, 1, 'EDIT_CLASS', 'Mengemaskini kelas ID: 1 kepada: 6 AL KHALID', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 04:20:02'),
(157, 1, 'EDIT_CLASS', 'Mengemaskini kelas ID: 101 kepada: 6 AL MALIK', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 04:20:17'),
(158, 1, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 04:21:15'),
(159, 6, 'LOGIN', 'Pengguna log masuk sebagai admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 04:22:16'),
(160, 6, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 04:24:38'),
(161, 1, 'LOGIN', 'Pengguna log masuk sebagai admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 04:24:53'),
(162, 1, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 04:42:27'),
(163, 101, 'LOGIN', 'Pengguna log masuk sebagai pelajar', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 04:55:31'),
(164, 101, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 04:56:56'),
(165, 1, 'LOGIN', 'Pengguna log masuk sebagai admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 04:57:20'),
(166, 1, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 05:10:40'),
(167, 101, 'LOGIN', 'Pengguna log masuk sebagai pelajar', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 05:10:52'),
(168, 101, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 05:11:50'),
(169, 2, 'LOGIN', 'Pengguna log masuk sebagai guru', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 05:12:35'),
(170, 2, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 05:35:26'),
(171, 6, 'LOGIN', 'Pengguna log masuk sebagai admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 01:12:06'),
(172, 6, 'ASSIGN_SUBJECT', 'Assign subjek ID: 10 ke kelas ID: 100 untuk semester 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 01:12:44'),
(173, 6, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 01:29:48'),
(174, 2, 'LOGIN', 'Pengguna log masuk sebagai guru', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 01:29:58'),
(175, 2, 'SAVE_MARK', 'Menyimpan markah untuk pelajar ID: 1, subjek ID: 10, exam ID: 10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 01:30:20'),
(176, 2, 'SAVE_MARK', 'Menyimpan markah untuk pelajar ID: 100, subjek ID: 10, exam ID: 10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 01:30:25'),
(177, 2, 'SAVE_MARK', 'Menyimpan markah untuk pelajar ID: 2, subjek ID: 10, exam ID: 10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 01:30:32'),
(178, 2, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 01:35:18'),
(179, 101, 'LOGIN', 'Pengguna log masuk sebagai pelajar', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 01:35:26'),
(180, 101, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 01:36:08'),
(181, 101, 'LOGIN', 'Pengguna log masuk sebagai pelajar', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 01:38:26'),
(182, 101, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 01:38:29'),
(183, 2, 'LOGIN', 'Pengguna log masuk sebagai guru', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 01:38:40'),
(184, 2, 'SAVE_MARK', 'Menyimpan markah untuk pelajar ID: 101, subjek ID: 10, exam ID: 6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 01:39:03'),
(185, 2, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 01:39:20'),
(186, 101, 'LOGIN', 'Pengguna log masuk sebagai pelajar', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 01:39:29'),
(187, 101, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 01:46:02'),
(188, 6, 'LOGIN', 'Pengguna log masuk sebagai admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 01:54:07'),
(189, 6, 'ASSIGN_SUBJECT', 'Assign subjek ID: 10 ke kelas ID: 2 untuk semester 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 01:54:42'),
(190, 6, 'ASSIGN_SUBJECT', 'Assign subjek ID: 10 ke kelas ID: 101 untuk semester 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 01:54:56'),
(191, 6, 'ASSIGN_SUBJECT', 'Assign subjek ID: 10 ke kelas ID: 4 untuk semester 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 01:55:11'),
(192, 6, 'ASSIGN_SUBJECT', 'Assign subjek ID: 10 ke kelas ID: 3 untuk semester 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 01:55:43'),
(193, 6, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 02:07:47'),
(194, 2, 'LOGIN', 'Pengguna log masuk sebagai guru', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 02:08:02'),
(195, 2, 'SAVE_MARK', 'Menyimpan markah untuk pelajar ID: 101, subjek ID: 10, exam ID: 10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 02:08:28'),
(196, 2, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 02:08:32'),
(197, 101, 'LOGIN', 'Pengguna log masuk sebagai pelajar', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 02:08:42'),
(198, 101, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 02:14:36'),
(199, 2, 'LOGIN', 'Pengguna log masuk sebagai guru', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 02:14:50'),
(200, 2, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 02:14:53'),
(201, 6, 'LOGIN', 'Pengguna log masuk sebagai admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 02:15:09'),
(202, 6, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 06:31:37'),
(203, 3, 'LOGIN', 'Pengguna log masuk sebagai guru', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 06:32:03'),
(204, 3, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 06:33:02'),
(205, 103, 'LOGIN', 'Pengguna log masuk sebagai guru', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 02:49:34'),
(206, 103, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 02:52:24'),
(207, 1, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 15:36:37'),
(208, 101, 'LOGIN', 'Pengguna log masuk sebagai pelajar', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 15:37:05'),
(209, 101, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 15:37:38'),
(210, 3, 'LOGIN', 'Pengguna log masuk sebagai guru', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 15:37:52'),
(211, 3, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 15:58:52'),
(212, 101, 'LOGIN', 'Pengguna log masuk sebagai pelajar', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 16:01:09'),
(213, 101, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 16:01:48'),
(214, 2, 'LOGIN', 'Pengguna log masuk sebagai guru', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 16:02:02'),
(215, 2, 'SAVE_MARK', 'Menyimpan markah untuk pelajar ID: 1, subjek ID: 10, exam ID: 6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 16:02:27'),
(216, 2, 'SAVE_MARK', 'Menyimpan markah untuk pelajar ID: 1, subjek ID: 10, exam ID: 6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 16:02:36'),
(217, 2, 'SAVE_MARK', 'Menyimpan markah untuk pelajar ID: 100, subjek ID: 10, exam ID: 6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 16:02:42'),
(218, 2, 'SAVE_MARK', 'Menyimpan markah untuk pelajar ID: 100, subjek ID: 10, exam ID: 6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 16:02:48'),
(219, 2, 'SAVE_MARK', 'Menyimpan markah untuk pelajar ID: 1, subjek ID: 10, exam ID: 6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 16:03:21'),
(220, 2, 'SAVE_MARK', 'Menyimpan markah untuk pelajar ID: 100, subjek ID: 10, exam ID: 10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 16:03:47'),
(221, 2, 'SAVE_MARK', 'Menyimpan markah untuk pelajar ID: 1, subjek ID: 10, exam ID: 10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 16:06:06'),
(222, 2, 'SAVE_MARK', 'Menyimpan markah untuk pelajar ID: 2, subjek ID: 10, exam ID: 6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 16:07:17'),
(223, 2, 'SAVE_MARK', 'Menyimpan markah untuk pelajar ID: 1, subjek ID: 10, exam ID: 6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 16:13:49'),
(224, 2, 'SAVE_MARK', 'Menyimpan markah untuk pelajar ID: 100, subjek ID: 10, exam ID: 6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 16:13:56'),
(225, 2, 'SAVE_MARK', 'Menyimpan markah untuk pelajar ID: 2, subjek ID: 10, exam ID: 6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 16:14:12'),
(226, 2, 'SAVE_MARK', 'Menyimpan markah untuk pelajar ID: 1, subjek ID: 10, exam ID: 10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 16:18:10'),
(227, 2, 'UPDATE_PROFILE', 'Mengemaskini profil guru', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 16:19:56'),
(228, 2, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 16:20:16'),
(229, 3, 'LOGIN', 'Pengguna log masuk sebagai guru', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 16:21:25'),
(230, 3, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 16:55:04'),
(231, 1, 'LOGIN', 'Pengguna log masuk sebagai admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 16:55:38'),
(232, 1, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 16:55:40'),
(233, 6, 'LOGIN', 'Pengguna log masuk sebagai admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 16:55:52'),
(234, 6, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 16:56:22'),
(235, 1, 'LOGIN', 'Pengguna log masuk sebagai admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 16:56:48'),
(236, 1, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 16:59:36'),
(237, 1, 'LOGIN', 'Pengguna log masuk sebagai admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 16:59:52'),
(238, 1, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 17:00:53'),
(239, 1, 'LOGIN', 'Pengguna log masuk sebagai admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 17:01:03'),
(240, 1, 'ADD_TEACHER', 'Menambah guru: GU004 - Hasnah Binti Hassan', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 17:28:32'),
(241, 1, 'ADD_TEACHER', 'Menambah guru: GU005 - Ramlah Binti Ram', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 17:29:11'),
(242, 1, 'ADD_TEACHER', 'Menambah guru: GU006 - Hakim bin Haslan', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 17:36:51'),
(243, 1, 'DELETE_TEACHER', 'Menamatkan guru: GU006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 17:40:42'),
(244, 1, 'ADD_TEACHER', 'Menambah guru: GU006 - Hakim bin Haslan', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 17:41:31'),
(245, 1, 'ADD_TEACHER', 'Menambah guru: GU006 - Hakim bin Haslan', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 17:42:15'),
(246, 1, 'ADD_TEACHER', 'Menambah guru: GU007 - Minah Binti Min', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 17:43:52'),
(247, 1, 'ADD_TEACHER', 'Menambah guru: GU008 - Syihabuddin bin ismail', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 17:46:50'),
(248, 1, 'DELETE_TEACHER', 'Menamatkan guru: GU008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 17:47:20'),
(249, 1, 'ADD_TEACHER', 'Menambah guru: GU008 - Syihabuddin bin ismail', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 17:47:52'),
(250, 1, 'EDIT_SUBJECT', 'Mengemaskini subjek ID: 8', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 18:09:52'),
(251, 1, 'EDIT_SUBJECT', 'Mengemaskini subjek ID: 102', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 18:10:00'),
(252, 1, 'EDIT_SUBJECT', 'Mengemaskini subjek ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 18:12:24'),
(253, 1, 'EDIT_CLASS', 'Mengemaskini kelas ID: 2 kepada: 6 AL HAKAM', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 18:13:15'),
(254, 1, 'EDIT_CLASS', 'Mengemaskini kelas ID: 2 kepada: 6 AL HAKAM', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 18:17:13'),
(255, 1, 'DELETE_EXAM', 'Membatalkan peperiksaan ID: 5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 18:17:29'),
(256, 1, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 18:37:59'),
(257, 6, 'LOGIN', 'Pengguna log masuk sebagai admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 18:38:35'),
(258, 6, 'EDIT_CLASS', 'Mengemaskini kelas ID: 100 kepada: 6 AL FATAH', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 18:39:11'),
(259, 6, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 18:41:46'),
(260, 1, 'LOGIN', 'Pengguna log masuk sebagai admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 18:42:15'),
(261, 1, 'EDIT_CLASS', 'Mengemaskini kelas ID: 2 kepada: 6 AL HAKAM', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 18:43:21'),
(262, 1, 'EDIT_CLASS', 'Mengemaskini kelas ID: 1 kepada: 6 AL KHALID', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 18:43:31'),
(263, 1, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 18:44:58'),
(264, 6, 'LOGIN', 'Pengguna log masuk sebagai admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 18:45:10'),
(265, 6, 'UPDATE_CLASS_SUBJECT_TEACHER', 'Kemaskini guru untuk class_subject ID: 6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 18:47:39'),
(266, 6, 'UPDATE_CLASS_SUBJECT_TEACHER', 'Kemaskini guru untuk class_subject ID: 9', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 18:47:58'),
(267, 6, 'EDIT_CLASS', 'Mengemaskini kelas ID: 101 kepada: 6 AL MALIK', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 18:48:27'),
(268, 6, 'EDIT_CLASS', 'Mengemaskini kelas ID: 4 kepada: 6 AL QUDDUS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 18:48:56'),
(269, 6, 'UPDATE_CLASS_SUBJECT_TEACHER', 'Kemaskini guru untuk class_subject ID: 10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 18:51:45'),
(270, 6, 'EDIT_CLASS', 'Mengemaskini kelas ID: 3 kepada: 6 AL RAZAQ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 18:53:29'),
(271, 6, 'UPDATE_CLASS_SUBJECT_TEACHER', 'Kemaskini guru untuk class_subject ID: 9', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 18:56:16'),
(272, 6, 'UPDATE_CLASS_SUBJECT_TEACHER', 'Kemaskini guru untuk class_subject ID: 7', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 18:58:37'),
(273, 6, 'UPDATE_CLASS_SUBJECT_TEACHER', 'Kemaskini guru untuk class_subject ID: 6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 18:58:41'),
(274, 6, 'UPDATE_CLASS_SUBJECT_TEACHER', 'Kemaskini guru untuk class_subject ID: 8', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 18:58:50'),
(275, 6, 'UPDATE_CLASS_SUBJECT_TEACHER', 'Kemaskini guru untuk class_subject ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 19:02:42'),
(276, 6, 'ASSIGN_SUBJECT', 'Assign subjek ID: 9 ke kelas ID: 1 untuk semester 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 19:10:07'),
(277, 6, 'ASSIGN_SUBJECT', 'Assign subjek ID: 9 ke kelas ID: 101 untuk semester 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 19:10:42'),
(278, 6, 'ASSIGN_SUBJECT', 'Assign subjek ID: 101 ke kelas ID: 3 untuk semester 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 19:11:26'),
(279, 6, 'ASSIGN_SUBJECT', 'Assign subjek ID: 9 ke kelas ID: 2 untuk semester 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 19:11:51'),
(280, 6, 'UPDATE_CLASS_SUBJECT_TEACHER', 'Kemaskini guru untuk class_subject ID: 10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 19:22:12'),
(281, 6, 'REMOVE_CLASS_SUBJECT', 'Buang class_subject ID: 13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 19:22:25'),
(282, 6, 'UPDATE_CLASS_SUBJECT_TEACHER', 'Kemaskini guru untuk class_subject ID: 10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 19:22:31'),
(283, 6, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 19:23:20'),
(284, 1, 'LOGIN', 'Pengguna log masuk sebagai admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 19:33:30'),
(285, 1, 'EDIT_SUBJECT', 'Mengemaskini subjek ID: 10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 19:35:57'),
(286, 1, 'EDIT_SUBJECT', 'Mengemaskini subjek ID: 10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 19:36:03'),
(287, 1, 'EDIT_CLASS', 'Mengemaskini kelas ID: 2 kepada: 6 AL HAKAM', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 19:36:14'),
(288, 1, 'EDIT_CLASS', 'Mengemaskini kelas ID: 2 kepada: 6 AL HAKAM', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 19:36:20'),
(289, 1, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 19:36:54'),
(290, 101, 'LOGIN', 'Pengguna log masuk sebagai pelajar', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 19:37:34'),
(291, 101, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 19:37:41'),
(292, 3, 'LOGIN', 'Pengguna log masuk sebagai guru', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 19:38:09'),
(293, 3, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 19:38:30'),
(294, 2, 'LOGIN', 'Pengguna log masuk sebagai guru', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 19:38:43'),
(295, 2, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 19:38:51'),
(296, 3, 'LOGIN', 'Pengguna log masuk sebagai guru', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 19:39:03'),
(297, 3, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 19:40:07'),
(298, 1, 'LOGIN', 'Pengguna log masuk sebagai admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 01:46:29'),
(299, 1, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 01:49:01'),
(300, 6, 'LOGIN', 'Pengguna log masuk sebagai admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 01:49:14'),
(301, 6, 'UPDATE_CLASS_SUBJECT_TEACHER', 'Kemaskini guru untuk class_subject ID: 7', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 01:50:04'),
(302, 6, 'UPDATE_CLASS_SUBJECT_TEACHER', 'Kemaskini guru untuk class_subject ID: 7', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 01:50:20'),
(303, 6, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 01:52:44'),
(304, 1, 'LOGIN', 'Pengguna log masuk sebagai admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 01:52:55'),
(305, 1, 'ADD_STUDENT', 'Menambah pelajar: STPM2026012 - Muhammad khairul', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 02:26:25'),
(306, 1, 'ADD_TEACHER', 'Menambah guru: GU010 - Zubaidah', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 02:45:03'),
(307, 1, 'ADD_ADMIN', 'Menambah admin: AD100 - khai', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 02:48:59'),
(308, 1, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 02:56:31'),
(309, 131, 'LOGIN', 'Pengguna log masuk sebagai admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 02:56:45'),
(310, 131, 'UPDATE_CLASS_SUBJECT_TEACHER', 'Kemaskini guru untuk class_subject ID: 7', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 03:09:50'),
(311, 131, 'EDIT_STUDENT', 'Mengemaskini pelajar: STPM2026012 - Muhammad Danish', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 03:25:53'),
(312, 131, 'UPDATE_CLASS_SUBJECT_TEACHER', 'Kemaskini guru untuk class_subject ID: 7', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 03:26:21'),
(313, 131, 'ADD_EXAM', 'Menambah peperiksaan: PEPERIKSAAN ULANGAN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 07:44:08'),
(314, 131, 'DELETE_SUBJECT', 'Menamatkan subjek ID: 102', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 07:44:49'),
(315, 131, 'ADD_SUBJECT', 'Menambah subjek: 9020 - Seni Visual', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 07:45:05'),
(316, 131, 'UPDATE_CLASS_SUBJECT_TEACHER', 'Kemaskini guru untuk class_subject ID: 7', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 08:40:05'),
(317, 131, 'UPDATE_CLASS_SUBJECT_TEACHER', 'Kemaskini guru untuk class_subject ID: 7', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 08:44:31'),
(318, 131, 'ASSIGN_SUBJECT', 'Assign subjek ID: 100 ke kelas ID: 2 untuk semester 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 08:48:15'),
(319, 131, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 09:03:55'),
(320, 1, 'LOGIN', 'Pengguna log masuk sebagai admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 09:04:11'),
(321, 1, 'EDIT_CLASS', 'Mengemaskini kelas ID: 2 kepada: 6 AL HAKAM', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 09:04:32'),
(322, 1, 'LOGOUT', 'Pengguna telah log keluar dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 09:04:37');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `teacher_id` varchar(20) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `teacher_type` enum('guru_kelas','guru_subjek') NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subjects` text DEFAULT NULL,
  `class_assigned` varchar(100) DEFAULT NULL,
  `status` enum('Aktif','Tidak Aktif') DEFAULT 'Aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `user_id`, `teacher_id`, `full_name`, `teacher_type`, `phone`, `subjects`, `class_assigned`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 'GU001', 'En. Ahmad bin Abdul', 'guru_subjek', '0134567890', 'geografi', '', 'Aktif', '2026-04-13 15:26:03', '2026-04-17 16:19:56'),
(2, 3, 'GU002', 'Pn. Siti binti Hassan', 'guru_kelas', '0145678901', 'Bahasa Melayu, Sejarah', '6 AL FATAH', 'Aktif', '2026-04-13 15:26:03', '2026-04-18 08:45:13'),
(100, 103, 'GU003', 'Zamri', 'guru_subjek', '0171235674', NULL, NULL, 'Aktif', '2026-04-17 02:49:24', '2026-04-17 02:49:24'),
(101, 116, 'GU004', 'Hasnah Binti Hassan', 'guru_subjek', '0125465875', NULL, NULL, 'Aktif', '2026-04-17 17:28:32', '2026-04-17 17:28:32'),
(102, 117, 'GU005', 'Ramlah Binti Ram', 'guru_subjek', '01554879654', NULL, NULL, 'Aktif', '2026-04-17 17:29:11', '2026-04-17 17:29:11'),
(105, 120, 'GU006', 'Hakim bin Haslan', 'guru_subjek', '01457854622', NULL, NULL, 'Aktif', '2026-04-17 17:42:15', '2026-04-17 17:42:15'),
(106, 121, 'GU007', 'Minah Binti Min', 'guru_subjek', '0114548520', NULL, NULL, 'Aktif', '2026-04-17 17:43:52', '2026-04-17 17:43:52'),
(108, 123, 'GU008', 'Syihabuddin bin ismail', 'guru_subjek', '0154887452', NULL, NULL, 'Aktif', '2026-04-17 17:47:52', '2026-04-17 17:47:52'),
(109, 130, 'GU010', 'Zubaidah', 'guru_subjek', '0125197531', NULL, NULL, 'Aktif', '2026-04-18 02:45:03', '2026-04-18 02:45:03');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('pelajar','guru','admin') NOT NULL,
  `sub_role` varchar(50) DEFAULT NULL,
  `security_question_id` int(11) DEFAULT NULL,
  `security_answer_hash` varchar(255) DEFAULT NULL,
  `status` enum('Aktif','Tidak Aktif') DEFAULT 'Aktif',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `sub_role`, `security_question_id`, `security_answer_hash`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'AD001', '$2y$10$l03A3dQb3nN.g2IkvOiDvO96hltubleX2WS10FiTISQ2gHDNtLiuC', 'superadmin@exam.edu.my', 'admin', 'super_admin', 4, '2ffe4e77325d9a7152f7086ea7aa5114', 'Aktif', '2026-04-18 17:04:11', '2026-04-13 15:26:03', '2026-04-18 09:04:11'),
(2, 'GU001', '$2y$10$Af3ypbXTjvX9AkF273RRUOehFU3Hii3KujFUgz7Cha3ssPP.ySO.e', 'guru1@exam.edu.my', 'guru', 'guru_subjek', 2, 'd93ec75bca4b7ef88df5a6c591654422', 'Aktif', '2026-04-18 03:38:43', '2026-04-13 15:26:03', '2026-04-17 19:38:43'),
(3, 'GU002', '$2y$10$KCuAGVsn0IAPkGwk8VCM3u8aW1epek1a1EGCFzmNFnt3Y8Atr5uSG', 'guru2@exam.edu.my', 'guru', 'guru_kelas', 2, 'f04e1fd4cbfeecfdce8aa2ad6e9cf4ac', 'Aktif', '2026-04-18 03:39:03', '2026-04-13 15:26:03', '2026-04-17 19:39:03'),
(4, 'STPM2024001', '$2y$10$Z9u0XHr7pf24ViGBoNm.EegJUqpDGITln1ErY4sYuuazg2T0It6OO', 'student1@exam.edu.my', 'pelajar', NULL, 1, '90b74c589f46e8f3a484082d659308bd', 'Aktif', '2026-04-14 22:45:22', '2026-04-13 15:26:03', '2026-04-14 14:45:22'),
(5, 'STPM2024002', '$2y$10$.jlc9S6/TIczSarrqu97iu9u.Lv8gbTPlarYTEWhLcPAM/Gn20vZq', 'student2@exam.edu.my', 'pelajar', NULL, 1, 'db04eb4b07e0aaf8d1d477ae342bdff9', 'Aktif', NULL, '2026-04-13 15:26:03', '2026-04-13 15:44:59'),
(6, 'AD002', '$2y$10$sR7uly5gfMAawHALCf/hFOmPt1P9JLoKyzbw6oriluZ1KTMcbq4m6', 'admin2@exam.edu.my', 'admin', 'admin', 3, '81404ea2c7d2d1e84e46f8c1acae0eb8', 'Aktif', '2026-04-18 09:49:14', '2026-04-13 15:26:03', '2026-04-18 01:49:14'),
(100, 'STPM2026001', '$2y$10$/RaaT.HgPkieViW32H4Ij.jWw73Ons7vgO9g0f0j74KaK08k5tsm6', 'Sfitri2710@gmail.com', 'pelajar', NULL, 1, 'bb400da73f11df2567553c2a6a166167', 'Aktif', '2026-04-15 10:48:43', '2026-04-15 02:48:23', '2026-04-15 02:48:43'),
(101, 'STPM2026007', '$2y$10$b.FsDHqcmiMF.WXAQnlhXeHfdLUSSmTZ7iR6E1rvKM6HRPdqU.83q', 'ziera.sofia@gmail.com', 'pelajar', NULL, 7, 'bffc68f8f0052cc5b8e0affaa651acb5', 'Aktif', '2026-04-18 03:37:34', '2026-04-15 03:16:47', '2026-04-17 19:37:34'),
(102, 'STPM2026008', '$2y$10$K99HkajEmTt7FHvQzpvSQu8Y1uQbB0ohMX39DXjMzuClguLHqfXvm', 'irdhinkhairul@gmail.com', 'pelajar', NULL, 10, '288d78dd363fb0f4736f8e30c1f663a8', 'Aktif', NULL, '2026-04-17 02:24:10', '2026-04-17 02:24:10'),
(103, 'GU003', '$2y$10$n.iSsZpEUHnSQb18nAzdu.GLcynxBHOxi5bsad7pzDsLu5vH/IFQO', 'anis@gmail.com', 'guru', 'guru_subjek', 9, '1630937c3d00b4f4b153599d93469963', 'Aktif', '2026-04-17 10:49:34', '2026-04-17 02:49:24', '2026-04-17 02:49:34'),
(116, 'GU004', '$2y$10$LFfYuJ7jjISDgt71txoU2.UytlzW3HAkt.rZ7Jd7xnFjZJRI0CtIa', 'hasnahsan@gmail.com', 'guru', 'guru_subjek', NULL, NULL, 'Aktif', NULL, '2026-04-17 17:28:32', '2026-04-17 17:28:32'),
(117, 'GU005', '$2y$10$LZDjzciyO/Z0YecFQmn1QOf1v0eyatTXVhHJ0H2PiSgIRohfN0BhS', 'ramlahRam@gmail.com', 'guru', 'guru_subjek', NULL, NULL, 'Aktif', NULL, '2026-04-17 17:29:11', '2026-04-17 17:29:11'),
(120, 'GU006', '$2y$10$EdukKGHSIGwsygkg3Q0CquWYLJCEfbZXkGo7NTnSQ4N.YgDBFNmkK', 'akimaslan@gmail.com', 'guru', 'guru_subjek', NULL, NULL, 'Aktif', NULL, '2026-04-17 17:42:15', '2026-04-17 17:42:15'),
(121, 'GU007', '$2y$10$nJfU2voxBK78ruXoWobL5OpxWZhRycgg.X0pkBOfh6SFSvwxpYhUu', 'Minah@gmail.com', 'guru', 'guru_subjek', NULL, NULL, 'Aktif', NULL, '2026-04-17 17:43:52', '2026-04-17 17:43:52'),
(123, 'GU008', '$2y$10$tNJQxqGzhiJ0UL9JhBIFf.1A8f3yOhIV7n0wrNBJB5VuwnFR.XZii', 'syihabudinismail@gmail.com', 'guru', 'guru_subjek', NULL, NULL, 'Aktif', NULL, '2026-04-17 17:47:52', '2026-04-17 17:47:52'),
(129, 'STPM2026012', '$2y$10$ctQ22tbOUTP7TzD0l7vPJ.n6MMGaFjWRcTaS1Jr1v7uTXGsWUtSJK', 'jayjerung@gmail.com', 'pelajar', NULL, 4, '527bd5b5d689e2c32ae974c6229ff785', 'Aktif', NULL, '2026-04-18 02:26:25', '2026-04-18 02:26:25'),
(130, 'GU010', '$2y$10$eQ.drrdyUq1jsVMiyfJlpOLrNSPm/gvV/8UYjJ8YqMkIW2uDx4oMy', 'zubaidah@gmail.com', 'guru', 'guru_subjek', 11, '1350ea97b605bee6e42f7e459d61db1f', 'Aktif', NULL, '2026-04-18 02:45:03', '2026-04-18 02:45:03'),
(131, 'AD100', '$2y$10$H7T5bgrZ7QTRfaNSBc9TCe5tKBMJRvEW1i6q6ZXjef81EjjaUhT3e', 'khairul@gmail.com', 'admin', 'admin', 1, 'c54e982ea7c0b3c7bc20795d097168a4', 'Aktif', '2026-04-18 10:56:45', '2026-04-18 02:48:59', '2026-04-18 02:56:45');

-- --------------------------------------------------------

--
-- Table structure for table `user_security_answers`
--

CREATE TABLE `user_security_answers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_security_answers`
--

INSERT INTO `user_security_answers` (`id`, `user_id`, `question_id`, `answer_hash`, `created_at`) VALUES
(15, 4, 1, '90b74c589f46e8f3a484082d659308bd', '2026-04-13 15:31:14'),
(16, 4, 5, 'c06b3f99301fd839ca0797246f4f092b', '2026-04-13 15:31:14'),
(17, 5, 1, 'db04eb4b07e0aaf8d1d477ae342bdff9', '2026-04-13 15:31:14'),
(18, 5, 5, 'c183dc7d97fd89bd457dbf8768bd150b', '2026-04-13 15:31:14'),
(19, 2, 2, 'd93ec75bca4b7ef88df5a6c591654422', '2026-04-13 15:31:14'),
(20, 2, 3, '61363ed704b311eb5a2482483b8f1d7f', '2026-04-13 15:31:14'),
(21, 3, 2, 'f04e1fd4cbfeecfdce8aa2ad6e9cf4ac', '2026-04-13 15:31:14'),
(22, 3, 6, 'e30f70e9a2603bd845a9a892d20f447e', '2026-04-13 15:31:14'),
(23, 1, 4, '2ffe4e77325d9a7152f7086ea7aa5114', '2026-04-13 15:31:14'),
(24, 1, 7, 'bffc68f8f0052cc5b8e0affaa651acb5', '2026-04-13 15:31:14'),
(25, 6, 3, '81404ea2c7d2d1e84e46f8c1acae0eb8', '2026-04-13 15:31:14'),
(26, 6, 7, '104d578115f1eb5b48b3e575b97fd167', '2026-04-13 15:31:14');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_class_performance`
-- (See below for the actual view)
--
CREATE TABLE `v_class_performance` (
`class_id` int(11)
,`class_name` varchar(50)
,`total_students` bigint(21)
,`total_subjects` bigint(21)
,`avg_marks` decimal(5,1)
,`avg_pointer` decimal(4,2)
,`pass_rate` decimal(27,1)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_student_marks_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_student_marks_summary` (
`student_id` int(11)
,`matric_no` varchar(20)
,`student_name` varchar(100)
,`class_name` varchar(50)
,`semester` int(11)
,`avg_marks` decimal(6,2)
,`avg_pointer` decimal(4,2)
,`total_subjects` bigint(21)
,`subjects_passed` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_subject_performance`
-- (See below for the actual view)
--
CREATE TABLE `v_subject_performance` (
`subject_id` int(11)
,`subject_code` varchar(20)
,`subject_name` varchar(100)
,`total_marks` bigint(21)
,`avg_marks` decimal(5,1)
,`avg_pointer` decimal(4,2)
,`pass_rate` decimal(27,1)
);

-- --------------------------------------------------------

--
-- Structure for view `v_class_performance`
--
DROP TABLE IF EXISTS `v_class_performance`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_class_performance`  AS SELECT `c`.`id` AS `class_id`, `c`.`class_name` AS `class_name`, count(distinct `s`.`id`) AS `total_students`, count(distinct `m`.`subject_id`) AS `total_subjects`, round(avg(`m`.`marks`),1) AS `avg_marks`, round(avg(`m`.`pointer`),2) AS `avg_pointer`, round(sum(case when `m`.`marks` >= 50 then 1 else 0 end) / nullif(count(`m`.`id`),0) * 100,1) AS `pass_rate` FROM ((`classes` `c` left join `students` `s` on(`c`.`id` = `s`.`class_id` and `s`.`status` = 'Aktif')) left join `marks` `m` on(`s`.`id` = `m`.`student_id`)) WHERE `c`.`status` = 'Aktif' GROUP BY `c`.`id` ;

-- --------------------------------------------------------

--
-- Structure for view `v_student_marks_summary`
--
DROP TABLE IF EXISTS `v_student_marks_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_student_marks_summary`  AS SELECT `s`.`id` AS `student_id`, `s`.`matric_no` AS `matric_no`, `s`.`full_name` AS `student_name`, `c`.`class_name` AS `class_name`, `m`.`semester` AS `semester`, round(avg(`m`.`marks`),2) AS `avg_marks`, round(avg(`m`.`pointer`),2) AS `avg_pointer`, count(`m`.`id`) AS `total_subjects`, sum(case when `m`.`marks` >= 50 then 1 else 0 end) AS `subjects_passed` FROM ((`students` `s` left join `classes` `c` on(`s`.`class_id` = `c`.`id`)) left join `marks` `m` on(`s`.`id` = `m`.`student_id`)) WHERE `s`.`status` = 'Aktif' GROUP BY `s`.`id`, `c`.`id`, `m`.`semester` ;

-- --------------------------------------------------------

--
-- Structure for view `v_subject_performance`
--
DROP TABLE IF EXISTS `v_subject_performance`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_subject_performance`  AS SELECT `sub`.`id` AS `subject_id`, `sub`.`subject_code` AS `subject_code`, `sub`.`subject_name` AS `subject_name`, count(distinct `m`.`id`) AS `total_marks`, round(avg(`m`.`marks`),1) AS `avg_marks`, round(avg(`m`.`pointer`),2) AS `avg_pointer`, round(sum(case when `m`.`marks` >= 50 then 1 else 0 end) / nullif(count(`m`.`id`),0) * 100,1) AS `pass_rate` FROM (`subjects` `sub` left join `marks` `m` on(`sub`.`id` = `m`.`subject_id`)) WHERE `sub`.`status` = 'Aktif' GROUP BY `sub`.`id` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admin_id` (`admin_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `class_subjects`
--
ALTER TABLE `class_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_class_subject` (`class_id`,`subject_id`,`semester`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_type_id` (`exam_type_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `exam_types`
--
ALTER TABLE `exam_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `exam_code` (`exam_code`);

--
-- Indexes for table `marks`
--
ALTER TABLE `marks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_mark` (`student_id`,`subject_id`,`class_id`,`semester`,`exam_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `exam_id` (`exam_id`),
  ADD KEY `entered_by` (`entered_by`),
  ADD KEY `idx_marks_student` (`student_id`),
  ADD KEY `idx_marks_subject` (`subject_id`),
  ADD KEY `idx_marks_semester` (`semester`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `security_questions`
--
ALTER TABLE `security_questions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `matric_no` (`matric_no`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subject_code` (`subject_code`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_system_logs_action` (`action`),
  ADD KEY `idx_system_logs_created` (`created_at`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `teacher_id` (`teacher_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `security_question_id` (`security_question_id`),
  ADD KEY `idx_users_username` (`username`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_status` (`status`);

--
-- Indexes for table `user_security_answers`
--
ALTER TABLE `user_security_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `question_id` (`question_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=102;

--
-- AUTO_INCREMENT for table `class_subjects`
--
ALTER TABLE `class_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=102;

--
-- AUTO_INCREMENT for table `exam_types`
--
ALTER TABLE `exam_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `marks`
--
ALTER TABLE `marks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=115;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT for table `security_questions`
--
ALTER TABLE `security_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=323;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=132;

--
-- AUTO_INCREMENT for table `user_security_answers`
--
ALTER TABLE `user_security_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `admins_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `announcements_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `class_subjects`
--
ALTER TABLE `class_subjects`
  ADD CONSTRAINT `class_subjects_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_subjects_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `exams`
--
ALTER TABLE `exams`
  ADD CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`exam_type_id`) REFERENCES `exam_types` (`id`),
  ADD CONSTRAINT `exams_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `marks`
--
ALTER TABLE `marks`
  ADD CONSTRAINT `marks_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `marks_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `marks_ibfk_3` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `marks_ibfk_4` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `marks_ibfk_5` FOREIGN KEY (`entered_by`) REFERENCES `teachers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_security_answers`
--
ALTER TABLE `user_security_answers`
  ADD CONSTRAINT `user_security_answers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_security_answers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `security_questions` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
