-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 14, 2026 at 07:10 AM
-- Server version: 8.0.46-cll-lve
-- PHP Version: 8.4.24

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `magena_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `id` bigint UNSIGNED NOT NULL,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `creator_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `groups`
--

INSERT INTO `groups` (`id`, `title`, `creator_id`, `created_at`, `updated_at`) VALUES
(1, 'تست', 1, '2026-09-05 15:29:27', '2026-09-12 07:25:45'),
(2, 'دوستان', 1, '2026-09-12 11:05:42', '2026-09-12 11:05:42'),
(3, 'خانواده', 1, '2026-09-12 11:06:02', '2026-09-12 11:06:02');

-- --------------------------------------------------------

--
-- Table structure for table `group_invites`
--

CREATE TABLE `group_invites` (
  `id` bigint UNSIGNED NOT NULL,
  `group_id` bigint UNSIGNED NOT NULL,
  `token` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `group_invites`
--

INSERT INTO `group_invites` (`id`, `group_id`, `token`, `created_at`) VALUES
(1, 1, '7wwMxFYf', '2026-09-05 15:44:00'),
(2, 2, 'JuYPfpde', '2026-09-12 12:09:12');

-- --------------------------------------------------------

--
-- Table structure for table `group_members`
--

CREATE TABLE `group_members` (
  `id` bigint UNSIGNED NOT NULL,
  `group_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `role` enum('owner','member') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'member',
  `joined_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `group_members`
--

INSERT INTO `group_members` (`id`, `group_id`, `user_id`, `role`, `joined_at`) VALUES
(1, 1, 1, 'owner', '2026-09-05 15:29:30'),
(9, 2, 1, 'owner', '2026-09-12 11:05:42'),
(10, 3, 1, 'owner', '2026-09-12 11:06:02');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `actor_user_id` bigint UNSIGNED DEFAULT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `group_id` bigint UNSIGNED DEFAULT NULL,
  `order_id` bigint UNSIGNED DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `actor_user_id`, `type`, `title`, `message`, `group_id`, `order_id`, `is_read`, `created_at`, `read_at`) VALUES
(1, 2, NULL, 'order_created', 'سفارش جدید', 'سفارش «عرق» به گروه اضافه شد.', 1, 2, 1, '2026-09-07 14:54:22', '2026-09-10 10:29:33'),
(2, 1, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «عرق» توسط یکی از اعضای گروه رزرو شد.', 1, 2, 1, '2026-09-07 16:35:45', '2026-09-07 16:36:42'),
(3, 2, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «عرق» توسط یکی از اعضای گروه رزرو شد.', 1, 2, 1, '2026-09-07 16:35:45', '2026-09-10 10:29:33'),
(4, 1, NULL, 'order_completed', 'سفارش خریداری شد', 'سفارش «عرق» خریداری شد.', 1, 2, 1, '2026-09-07 16:35:59', '2026-09-07 16:36:42'),
(5, 2, NULL, 'order_completed', 'سفارش خریداری شد', 'سفارش «عرق» خریداری شد.', 1, 2, 1, '2026-09-07 16:35:59', '2026-09-10 10:29:33'),
(6, 1, NULL, 'order_created', 'سفارش جدید', 'سفارش «کوکی» به گروه اضافه شد.', 1, 3, 1, '2026-09-07 16:41:36', '2026-09-07 16:42:05'),
(7, 2, NULL, 'order_created', 'سفارش جدید', 'سفارش «کوکی» به گروه اضافه شد.', 1, 3, 1, '2026-09-07 16:41:36', '2026-09-10 10:29:33'),
(8, 1, NULL, 'order_created', 'سفارش جدید', 'سفارش «چیپس» به گروه اضافه شد.', 1, 4, 1, '2026-09-07 16:42:55', '2026-09-07 16:43:07'),
(9, 2, NULL, 'order_created', 'سفارش جدید', 'سفارش «چیپس» به گروه اضافه شد.', 1, 4, 1, '2026-09-07 16:42:55', '2026-09-10 10:29:33'),
(10, 2, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «کوکی» توسط یکی از اعضای گروه رزرو شد.', 1, 3, 1, '2026-09-09 13:23:41', '2026-09-10 10:29:33'),
(11, 3, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «کوکی» توسط یکی از اعضای گروه رزرو شد.', 1, 3, 0, '2026-09-09 13:23:41', NULL),
(12, 2, NULL, 'order_completed', 'سفارش خریداری شد', 'سفارش «کوکی» خریداری شد.', 1, 3, 1, '2026-09-09 13:23:49', '2026-09-10 10:29:33'),
(13, 3, NULL, 'order_completed', 'سفارش خریداری شد', 'سفارش «کوکی» خریداری شد.', 1, 3, 0, '2026-09-09 13:23:49', NULL),
(14, 2, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «چیپس» توسط یکی از اعضای گروه رزرو شد.', 1, 4, 1, '2026-09-09 13:28:22', '2026-09-10 10:29:33'),
(15, 3, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «چیپس» توسط یکی از اعضای گروه رزرو شد.', 1, 4, 0, '2026-09-09 13:28:22', NULL),
(16, 2, NULL, 'order_unassigned', 'لغو مسئولیت سفارش', 'مسئولیت سفارش «چیپس» لغو شد.', 1, 4, 1, '2026-09-10 09:41:14', '2026-09-10 10:29:33'),
(17, 3, NULL, 'order_unassigned', 'لغو مسئولیت سفارش', 'مسئولیت سفارش «چیپس» لغو شد.', 1, 4, 0, '2026-09-10 09:41:14', NULL),
(18, 1, NULL, 'order_created', 'سفارش جدید', 'سفارش «آب» به گروه اضافه شد.', 1, NULL, 1, '2026-09-10 09:42:34', '2026-09-10 09:45:00'),
(19, 3, NULL, 'order_created', 'سفارش جدید', 'سفارش «آب» به گروه اضافه شد.', 1, NULL, 0, '2026-09-10 09:42:34', NULL),
(20, 2, NULL, 'order_completed', 'سفارش خریداری شد', 'سفارش «آب» خریداری شد.', 1, NULL, 1, '2026-09-10 09:42:54', '2026-09-10 10:29:33'),
(21, 3, NULL, 'order_completed', 'سفارش خریداری شد', 'سفارش «آب» خریداری شد.', 1, NULL, 0, '2026-09-10 09:42:54', NULL),
(22, 2, NULL, 'order_created', 'سفارش جدید', 'سفارش «تست» به گروه اضافه شد.', 1, 6, 1, '2026-09-10 09:43:14', '2026-09-10 10:29:33'),
(23, 3, NULL, 'order_created', 'سفارش جدید', 'سفارش «تست» به گروه اضافه شد.', 1, 6, 0, '2026-09-10 09:43:14', NULL),
(24, 2, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «تست» توسط یکی از اعضای گروه رزرو شد.', 1, 6, 1, '2026-09-10 09:43:28', '2026-09-10 10:29:33'),
(25, 3, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «تست» توسط یکی از اعضای گروه رزرو شد.', 1, 6, 0, '2026-09-10 09:43:28', NULL),
(26, 2, NULL, 'order_unassigned', 'لغو مسئولیت سفارش', 'مسئولیت سفارش «تست» لغو شد.', 1, 6, 1, '2026-09-10 09:43:44', '2026-09-10 10:29:33'),
(27, 3, NULL, 'order_unassigned', 'لغو مسئولیت سفارش', 'مسئولیت سفارش «تست» لغو شد.', 1, 6, 0, '2026-09-10 09:43:44', NULL),
(28, 1, NULL, 'order_created', 'سفارش جدید', 'سفارش «تست» به گروه اضافه شد.', 1, NULL, 1, '2026-09-10 09:44:39', '2026-09-10 09:45:00'),
(29, 3, NULL, 'order_created', 'سفارش جدید', 'سفارش «تست» به گروه اضافه شد.', 1, NULL, 0, '2026-09-10 09:44:39', NULL),
(30, 1, NULL, 'order_created', 'سفارش جدید', 'سفارش «تست3» به گروه اضافه شد.', 1, NULL, 1, '2026-09-10 09:44:56', '2026-09-10 09:45:00'),
(31, 3, NULL, 'order_created', 'سفارش جدید', 'سفارش «تست3» به گروه اضافه شد.', 1, NULL, 0, '2026-09-10 09:44:56', NULL),
(32, 2, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «تست» توسط یکی از اعضای گروه رزرو شد.', 1, 6, 1, '2026-09-12 06:30:48', '2026-09-12 06:56:08'),
(33, 3, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «تست» توسط یکی از اعضای گروه رزرو شد.', 1, 6, 0, '2026-09-12 06:30:48', NULL),
(34, 2, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «چیپس» توسط یکی از اعضای گروه رزرو شد.', 1, 4, 1, '2026-09-12 06:30:59', '2026-09-12 06:56:08'),
(35, 3, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «چیپس» توسط یکی از اعضای گروه رزرو شد.', 1, 4, 0, '2026-09-12 06:30:59', NULL),
(36, 2, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «تست3» توسط یکی از اعضای گروه رزرو شد.', 1, NULL, 1, '2026-09-12 06:31:16', '2026-09-12 06:56:08'),
(37, 3, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «تست3» توسط یکی از اعضای گروه رزرو شد.', 1, NULL, 0, '2026-09-12 06:31:16', NULL),
(38, 2, NULL, 'order_unassigned', 'لغو مسئولیت سفارش', 'مسئولیت سفارش «تست» لغو شد.', 1, 6, 1, '2026-09-12 06:34:38', '2026-09-12 06:56:08'),
(39, 3, NULL, 'order_unassigned', 'لغو مسئولیت سفارش', 'مسئولیت سفارش «تست» لغو شد.', 1, 6, 0, '2026-09-12 06:34:38', NULL),
(40, 2, NULL, 'order_completed', 'سفارش خریداری شد', 'سفارش «تست» خریداری شد.', 1, 6, 1, '2026-09-12 06:34:45', '2026-09-12 06:56:08'),
(41, 3, NULL, 'order_completed', 'سفارش خریداری شد', 'سفارش «تست» خریداری شد.', 1, 6, 0, '2026-09-12 06:34:45', NULL),
(42, 2, NULL, 'order_unassigned', 'لغو مسئولیت سفارش', 'مسئولیت سفارش «تست3» لغو شد.', 1, NULL, 1, '2026-09-12 06:34:51', '2026-09-12 06:56:08'),
(43, 3, NULL, 'order_unassigned', 'لغو مسئولیت سفارش', 'مسئولیت سفارش «تست3» لغو شد.', 1, NULL, 0, '2026-09-12 06:34:51', NULL),
(44, 1, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «تست3» توسط یکی از اعضای گروه رزرو شد.', 1, NULL, 1, '2026-09-12 06:34:54', '2026-09-12 06:40:38'),
(45, 3, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «تست3» توسط یکی از اعضای گروه رزرو شد.', 1, NULL, 0, '2026-09-12 06:34:54', NULL),
(46, 2, NULL, 'order_completed', 'سفارش خریداری شد', 'سفارش «چیپس» خریداری شد.', 1, 4, 1, '2026-09-12 06:35:08', '2026-09-12 06:56:08'),
(47, 3, NULL, 'order_completed', 'سفارش خریداری شد', 'سفارش «چیپس» خریداری شد.', 1, 4, 0, '2026-09-12 06:35:08', NULL),
(48, 1, NULL, 'order_unassigned', 'لغو مسئولیت سفارش', 'مسئولیت سفارش «تست3» لغو شد.', 1, NULL, 1, '2026-09-12 06:35:22', '2026-09-12 06:40:38'),
(49, 3, NULL, 'order_unassigned', 'لغو مسئولیت سفارش', 'مسئولیت سفارش «تست3» لغو شد.', 1, NULL, 0, '2026-09-12 06:35:22', NULL),
(50, 2, NULL, 'order_completed', 'سفارش خریداری شد', 'سفارش «تست3» خریداری شد.', 1, NULL, 1, '2026-09-12 06:35:31', '2026-09-12 06:56:08'),
(51, 3, NULL, 'order_completed', 'سفارش خریداری شد', 'سفارش «تست3» خریداری شد.', 1, NULL, 0, '2026-09-12 06:35:31', NULL),
(52, 1, NULL, 'order_created', 'سفارش جدید', 'سفارش «تست» به گروه اضافه شد.', 1, NULL, 1, '2026-09-12 06:35:42', '2026-09-12 06:40:38'),
(53, 3, NULL, 'order_created', 'سفارش جدید', 'سفارش «تست» به گروه اضافه شد.', 1, NULL, 0, '2026-09-12 06:35:42', NULL),
(54, 1, NULL, 'order_completed', 'سفارش خریداری شد', 'سفارش «تست» خریداری شد.', 1, NULL, 1, '2026-09-12 06:35:46', '2026-09-12 06:40:38'),
(55, 3, NULL, 'order_completed', 'سفارش خریداری شد', 'سفارش «تست» خریداری شد.', 1, NULL, 0, '2026-09-12 06:35:46', NULL),
(56, 1, NULL, 'order_created', 'سفارش جدید', 'سفارش «تست» به گروه اضافه شد.', 1, NULL, 1, '2026-09-12 06:35:57', '2026-09-12 06:40:38'),
(57, 3, NULL, 'order_created', 'سفارش جدید', 'سفارش «تست» به گروه اضافه شد.', 1, NULL, 0, '2026-09-12 06:35:57', NULL),
(58, 1, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «تست» توسط یکی از اعضای گروه رزرو شد.', 1, NULL, 1, '2026-09-12 06:35:58', '2026-09-12 06:40:38'),
(59, 3, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «تست» توسط یکی از اعضای گروه رزرو شد.', 1, NULL, 0, '2026-09-12 06:35:58', NULL),
(60, 1, NULL, 'order_unassigned', 'لغو مسئولیت سفارش', 'مسئولیت سفارش «تست» لغو شد.', 1, NULL, 1, '2026-09-12 06:36:12', '2026-09-12 06:40:38'),
(61, 3, NULL, 'order_unassigned', 'لغو مسئولیت سفارش', 'مسئولیت سفارش «تست» لغو شد.', 1, NULL, 0, '2026-09-12 06:36:12', NULL),
(62, 2, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «تست» توسط یکی از اعضای گروه رزرو شد.', 1, NULL, 1, '2026-09-12 06:36:17', '2026-09-12 06:56:08'),
(63, 3, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «تست» توسط یکی از اعضای گروه رزرو شد.', 1, NULL, 0, '2026-09-12 06:36:17', NULL),
(64, 2, NULL, 'order_unassigned', 'لغو مسئولیت سفارش', 'مسئولیت سفارش «تست» لغو شد.', 1, NULL, 1, '2026-09-12 06:39:55', '2026-09-12 06:56:08'),
(65, 3, NULL, 'order_unassigned', 'لغو مسئولیت سفارش', 'مسئولیت سفارش «تست» لغو شد.', 1, NULL, 0, '2026-09-12 06:39:55', NULL),
(66, 1, NULL, 'order_completed', 'سفارش خریداری شد', 'سفارش «تست» خریداری شد.', 1, NULL, 1, '2026-09-12 06:40:01', '2026-09-12 06:40:38'),
(67, 3, NULL, 'order_completed', 'سفارش خریداری شد', 'سفارش «تست» خریداری شد.', 1, NULL, 0, '2026-09-12 06:40:01', NULL),
(68, 2, NULL, 'order_created', 'سفارش جدید', 'سفارش «تست» به گروه اضافه شد.', 1, 11, 1, '2026-09-12 06:40:54', '2026-09-12 06:56:08'),
(69, 3, NULL, 'order_created', 'سفارش جدید', 'سفارش «تست» به گروه اضافه شد.', 1, 11, 0, '2026-09-12 06:40:54', NULL),
(70, 2, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «تست» توسط یکی از اعضای گروه رزرو شد.', 1, 11, 1, '2026-09-12 06:40:58', '2026-09-12 06:56:08'),
(71, 3, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «تست» توسط یکی از اعضای گروه رزرو شد.', 1, 11, 0, '2026-09-12 06:40:58', NULL),
(72, 2, NULL, 'order_unassigned', 'لغو مسئولیت سفارش', 'مسئولیت سفارش «تست» لغو شد.', 1, 11, 1, '2026-09-12 06:41:10', '2026-09-12 06:56:08'),
(73, 3, NULL, 'order_unassigned', 'لغو مسئولیت سفارش', 'مسئولیت سفارش «تست» لغو شد.', 1, 11, 0, '2026-09-12 06:41:10', NULL),
(74, 1, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «تست» توسط یکی از اعضای گروه رزرو شد.', 1, 11, 1, '2026-09-12 06:41:14', '2026-09-12 07:25:48'),
(75, 3, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «تست» توسط یکی از اعضای گروه رزرو شد.', 1, 11, 0, '2026-09-12 06:41:14', NULL),
(76, 1, NULL, 'order_completed', 'سفارش خریداری شد', 'سفارش «تست» خریداری شد.', 1, 11, 1, '2026-09-12 06:41:17', '2026-09-12 07:25:48'),
(77, 3, NULL, 'order_completed', 'سفارش خریداری شد', 'سفارش «تست» خریداری شد.', 1, 11, 0, '2026-09-12 06:41:17', NULL),
(78, 1, NULL, 'order_created', 'سفارش جدید', 'سفارش «تست» به گروه اضافه شد.', 1, NULL, 1, '2026-09-12 06:48:25', '2026-09-12 07:25:48'),
(79, 3, NULL, 'order_created', 'سفارش جدید', 'سفارش «تست» به گروه اضافه شد.', 1, NULL, 0, '2026-09-12 06:48:25', NULL),
(80, 1, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «تست» توسط یکی از اعضای گروه رزرو شد.', 1, NULL, 1, '2026-09-12 06:48:28', '2026-09-12 07:25:48'),
(81, 3, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «تست» توسط یکی از اعضای گروه رزرو شد.', 1, NULL, 0, '2026-09-12 06:48:28', NULL),
(82, 1, NULL, 'order_unassigned', 'لغو مسئولیت سفارش', 'مسئولیت سفارش «تست» لغو شد.', 1, NULL, 1, '2026-09-12 06:48:47', '2026-09-12 07:25:48'),
(83, 3, NULL, 'order_unassigned', 'لغو مسئولیت سفارش', 'مسئولیت سفارش «تست» لغو شد.', 1, NULL, 0, '2026-09-12 06:48:47', NULL),
(84, 1, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «تست» توسط یکی از اعضای گروه رزرو شد.', 1, NULL, 1, '2026-09-12 06:50:55', '2026-09-12 07:25:48'),
(85, 3, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «تست» توسط یکی از اعضای گروه رزرو شد.', 1, NULL, 0, '2026-09-12 06:50:55', NULL),
(86, 2, NULL, 'member_removed', 'از گروه حذف شدید', 'شما از گروه «گروه تست» حذف شدید.', 1, NULL, 1, '2026-09-12 07:11:47', '2026-09-12 07:12:30'),
(87, 1, NULL, 'member_removed', 'عضو از گروه حذف شد', 'کاربر تست از گروه «گروه تست» حذف شد.', 1, NULL, 1, '2026-09-12 07:11:47', '2026-09-12 07:25:48'),
(88, 3, NULL, 'member_removed', 'عضو از گروه حذف شد', 'کاربر تست از گروه «گروه تست» حذف شد.', 1, NULL, 0, '2026-09-12 07:11:47', NULL),
(89, 1, NULL, 'order_created', 'سفارش جدید', 'سفارش «تست» به گروه اضافه شد.', 1, NULL, 1, '2026-09-12 07:14:14', '2026-09-12 07:25:48'),
(90, 3, NULL, 'order_created', 'سفارش جدید', 'سفارش «تست» به گروه اضافه شد.', 1, NULL, 0, '2026-09-12 07:14:14', NULL),
(91, 1, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «تست» توسط یکی از اعضای گروه رزرو شد.', 1, NULL, 1, '2026-09-12 07:14:21', '2026-09-12 07:25:48'),
(92, 3, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «تست» توسط یکی از اعضای گروه رزرو شد.', 1, NULL, 0, '2026-09-12 07:14:21', NULL),
(93, 2, NULL, 'member_removed', 'از گروه حذف شدید', 'شما از گروه «گروه تست» حذف شدید.', 1, NULL, 1, '2026-09-12 07:14:38', '2026-09-12 07:14:44'),
(94, 1, NULL, 'member_removed', 'عضو از گروه حذف شد', 'کاربر تست از گروه «گروه تست» حذف شد.', 1, NULL, 1, '2026-09-12 07:14:38', '2026-09-12 07:25:48'),
(95, 3, NULL, 'member_removed', 'عضو از گروه حذف شد', 'کاربر تست از گروه «گروه تست» حذف شد.', 1, NULL, 0, '2026-09-12 07:14:38', NULL),
(96, 1, NULL, 'order_created', 'سفارش جدید', 'سفارش «تست4» به گروه اضافه شد.', 1, NULL, 1, '2026-09-12 07:15:27', '2026-09-12 07:25:48'),
(97, 3, NULL, 'order_created', 'سفارش جدید', 'سفارش «تست4» به گروه اضافه شد.', 1, NULL, 0, '2026-09-12 07:15:27', NULL),
(98, 1, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «تست4» توسط یکی از اعضای گروه رزرو شد.', 1, NULL, 1, '2026-09-12 07:15:28', '2026-09-12 07:25:48'),
(99, 3, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «تست4» توسط یکی از اعضای گروه رزرو شد.', 1, NULL, 0, '2026-09-12 07:15:28', NULL),
(100, 1, NULL, 'order_created', 'سفارش جدید', 'سفارش «تست4» به گروه اضافه شد.', 1, NULL, 1, '2026-09-12 07:15:47', '2026-09-12 07:25:48'),
(101, 3, NULL, 'order_created', 'سفارش جدید', 'سفارش «تست4» به گروه اضافه شد.', 1, NULL, 0, '2026-09-12 07:15:47', NULL),
(102, 1, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «تست4» توسط یکی از اعضای گروه رزرو شد.', 1, NULL, 1, '2026-09-12 07:16:09', '2026-09-12 07:25:48'),
(103, 3, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «تست4» توسط یکی از اعضای گروه رزرو شد.', 1, NULL, 0, '2026-09-12 07:16:09', NULL),
(104, 2, NULL, 'member_removed', 'از گروه حذف شدید', 'شما از گروه «گروه تست» حذف شدید.', 1, NULL, 1, '2026-09-12 07:16:21', '2026-09-12 07:17:56'),
(105, 1, NULL, 'member_removed', 'عضو از گروه حذف شد', 'کاربر تست از گروه «گروه تست» حذف شد.', 1, NULL, 1, '2026-09-12 07:16:21', '2026-09-12 07:25:48'),
(106, 3, NULL, 'member_removed', 'عضو از گروه حذف شد', 'کاربر تست از گروه «گروه تست» حذف شد.', 1, NULL, 0, '2026-09-12 07:16:21', NULL),
(107, 2, NULL, 'order_created', 'سفارش جدید', 'سفارش «تست4» به گروه اضافه شد.', 1, 16, 1, '2026-09-12 07:17:36', '2026-09-12 07:17:56'),
(108, 3, NULL, 'order_created', 'سفارش جدید', 'سفارش «تست4» به گروه اضافه شد.', 1, 16, 0, '2026-09-12 07:17:36', NULL),
(109, 1, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «تست4» توسط یکی از اعضای گروه رزرو شد.', 1, 16, 1, '2026-09-12 07:17:43', '2026-09-12 07:25:48'),
(110, 3, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «تست4» توسط یکی از اعضای گروه رزرو شد.', 1, 16, 0, '2026-09-12 07:17:43', NULL),
(111, 2, NULL, 'member_removed', 'از گروه حذف شدید', 'شما از گروه «گروه تست» حذف شدید.', 1, NULL, 1, '2026-09-12 07:17:54', '2026-09-12 07:17:56'),
(112, 1, NULL, 'member_removed', 'عضو از گروه حذف شد', 'کاربر تست از گروه «گروه تست» حذف شد.', 1, NULL, 1, '2026-09-12 07:17:54', '2026-09-12 07:25:48'),
(113, 3, NULL, 'member_removed', 'عضو از گروه حذف شد', 'کاربر تست از گروه «گروه تست» حذف شد.', 1, NULL, 0, '2026-09-12 07:17:54', NULL),
(114, 1, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «تست4» توسط یکی از اعضای گروه رزرو شد.', 1, 16, 1, '2026-09-12 07:19:00', '2026-09-12 07:25:48'),
(115, 3, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «تست4» توسط یکی از اعضای گروه رزرو شد.', 1, 16, 0, '2026-09-12 07:19:00', NULL),
(116, 1, NULL, 'order_completed', 'سفارش خریداری شد', 'سفارش «تست4» خریداری شد.', 1, 16, 1, '2026-09-12 07:19:04', '2026-09-12 07:25:48'),
(117, 3, NULL, 'order_completed', 'سفارش خریداری شد', 'سفارش «تست4» خریداری شد.', 1, 16, 0, '2026-09-12 07:19:04', NULL),
(118, 2, NULL, 'member_removed', 'از گروه حذف شدید', 'شما از گروه «گروه تست» حذف شدید.', 1, NULL, 1, '2026-09-12 07:19:18', '2026-09-12 07:19:26'),
(119, 1, NULL, 'member_removed', 'عضو از گروه حذف شد', 'کاربر تست از گروه «گروه تست» حذف شد.', 1, NULL, 1, '2026-09-12 07:19:18', '2026-09-12 07:25:48'),
(120, 3, NULL, 'member_removed', 'عضو از گروه حذف شد', 'کاربر تست از گروه «گروه تست» حذف شد.', 1, NULL, 0, '2026-09-12 07:19:18', NULL),
(121, 2, NULL, 'order_created', 'سفارش جدید', 'سفارش «تست 5» به گروه اضافه شد.', 1, NULL, 1, '2026-09-12 07:30:54', '2026-09-12 12:09:39'),
(122, 3, NULL, 'order_created', 'سفارش جدید', 'سفارش «تست 5» به گروه اضافه شد.', 1, NULL, 0, '2026-09-12 07:30:54', NULL),
(123, 2, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «تست 5» توسط یکی از اعضای گروه رزرو شد.', 1, NULL, 1, '2026-09-12 07:30:55', '2026-09-12 12:09:39'),
(124, 3, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «تست 5» توسط یکی از اعضای گروه رزرو شد.', 1, NULL, 0, '2026-09-12 07:30:55', NULL),
(125, 1, NULL, 'order_created', 'سفارش جدید', 'سفارش «تست6» به گروه اضافه شد.', 1, NULL, 1, '2026-09-12 07:32:09', '2026-09-12 08:55:44'),
(126, 3, NULL, 'order_created', 'سفارش جدید', 'سفارش «تست6» به گروه اضافه شد.', 1, NULL, 0, '2026-09-12 07:32:09', NULL),
(127, 1, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «تست6» توسط یکی از اعضای گروه رزرو شد.', 1, NULL, 1, '2026-09-12 07:32:10', '2026-09-12 08:55:44'),
(128, 3, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «تست6» توسط یکی از اعضای گروه رزرو شد.', 1, NULL, 0, '2026-09-12 07:32:10', NULL),
(129, 2, NULL, 'order_created', 'سفارش جدید', 'سفارش «تست 7» به گروه اضافه شد.', 1, 19, 1, '2026-09-12 07:32:25', '2026-09-12 12:09:39'),
(130, 3, NULL, 'order_created', 'سفارش جدید', 'سفارش «تست 7» به گروه اضافه شد.', 1, 19, 0, '2026-09-12 07:32:25', NULL),
(131, 1, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «تست 7» توسط یکی از اعضای گروه رزرو شد.', 1, 19, 1, '2026-09-12 07:32:35', '2026-09-12 08:55:44'),
(132, 3, NULL, 'order_reserved', 'سفارش رزرو شد', 'سفارش «تست 7» توسط یکی از اعضای گروه رزرو شد.', 1, 19, 0, '2026-09-12 07:32:35', NULL),
(133, 1, NULL, 'member_left', 'عضو از گروه خارج شد', 'کاربر تست از گروه «تست» خارج شد.', 1, NULL, 1, '2026-09-12 07:50:01', '2026-09-12 08:55:44'),
(134, 3, NULL, 'member_left', 'عضو از گروه خارج شد', 'کاربر تست از گروه «تست» خارج شد.', 1, NULL, 0, '2026-09-12 07:50:01', NULL),
(135, 1, 2, 'order_created', 'سفارش جدید', 'سفارش «شیر کاله» به گروه اضافه شد.', 2, NULL, 1, '2026-09-12 12:09:58', '2026-09-12 12:10:07'),
(136, 2, 1, 'order_assigned', 'مسئولیت سفارش پذیرفته شد', 'مسئولیت سفارش «شیر کاله» به عهده گرفته شد.', 2, NULL, 0, '2026-09-12 12:10:36', NULL),
(137, 3, 1, 'order_assigned', 'مسئولیت سفارش پذیرفته شد', 'مسئولیت سفارش «تست 7» به عهده گرفته شد.', 1, 19, 0, '2026-09-12 19:47:19', NULL),
(138, 3, 1, 'order_unassigned', 'لغو مسئولیت سفارش', 'مسئولیت سفارش «تست 7» لغو شد.', 1, 19, 0, '2026-09-12 19:47:25', NULL),
(139, 3, 1, 'order_unassigned', 'لغو مسئولیت سفارش', 'مسئولیت سفارش «تست 5» لغو شد.', 1, NULL, 0, '2026-09-12 19:47:30', NULL),
(140, 3, 1, 'order_assigned', 'مسئولیت سفارش پذیرفته شد', 'مسئولیت سفارش «تست 7» به عهده گرفته شد.', 1, 19, 0, '2026-09-13 18:12:23', NULL),
(141, 3, 1, 'order_assigned', 'مسئولیت سفارش پذیرفته شد', 'مسئولیت سفارش «تست 5» به عهده گرفته شد.', 1, NULL, 0, '2026-09-13 18:12:24', NULL),
(142, 3, 1, 'order_unassigned', 'لغو مسئولیت سفارش', 'مسئولیت سفارش «تست 7» لغو شد.', 1, 19, 0, '2026-09-13 18:12:25', NULL),
(143, 3, 1, 'order_unassigned', 'لغو مسئولیت سفارش', 'مسئولیت سفارش «تست 5» لغو شد.', 1, NULL, 0, '2026-09-13 18:12:26', NULL),
(144, 3, 1, 'order_assigned', 'مسئولیت سفارش پذیرفته شد', 'مسئولیت سفارش «تست 5» به عهده گرفته شد.', 1, NULL, 0, '2026-09-13 18:16:16', NULL),
(145, 3, 1, 'order_assigned', 'مسئولیت سفارش پذیرفته شد', 'مسئولیت سفارش «تست 7» به عهده گرفته شد.', 1, 19, 0, '2026-09-13 18:16:16', NULL),
(146, 3, 1, 'order_completed', 'سفارش خریداری شد', 'سفارش «تست 7» خریداری شد.', 1, 19, 0, '2026-09-13 18:19:09', NULL),
(147, 3, 1, 'member_removed', 'از گروه حذف شدید', 'شما از گروه «تست» حذف شدید.', 1, NULL, 0, '2026-09-13 18:19:39', NULL),
(148, 1, 1, 'member_removed', 'عضو از گروه حذف شد', 'روژین تمیمی از گروه «تست» حذف شد.', 1, NULL, 0, '2026-09-13 18:19:39', NULL),
(149, 2, 1, 'member_removed', 'از گروه حذف شدید', 'شما از گروه «دوستان» حذف شدید.', 2, NULL, 0, '2026-09-13 18:20:26', NULL),
(150, 1, 1, 'member_removed', 'عضو از گروه حذف شد', 'کاربر تست از گروه «دوستان» حذف شد.', 2, NULL, 0, '2026-09-13 18:20:26', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` bigint UNSIGNED NOT NULL,
  `group_id` bigint UNSIGNED NOT NULL,
  `created_by` bigint UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `priority` enum('low','medium','high') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `status` enum('pending','reserved','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `deadline` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `group_id`, `created_by`, `title`, `quantity`, `priority`, `status`, `deadline`, `created_at`, `updated_at`) VALUES
(2, 1, 1, 'عرق', '۱ بطری', 'medium', 'completed', NULL, '2026-09-07 14:54:22', '2026-09-07 16:35:59'),
(3, 1, 3, 'کوکی', 'یک جار', 'medium', 'completed', NULL, '2026-09-07 16:41:36', '2026-09-09 13:23:49'),
(4, 1, 3, 'چیپس', '2', 'medium', 'completed', NULL, '2026-09-07 16:42:55', '2026-09-12 06:35:08'),
(6, 1, 1, 'تست', '1', 'low', 'completed', NULL, '2026-09-10 09:43:14', '2026-09-12 06:34:45'),
(11, 1, 1, 'تست', '2', 'medium', 'completed', NULL, '2026-09-12 06:40:54', '2026-09-12 06:41:17'),
(16, 1, 1, 'تست4', '4', 'medium', 'completed', NULL, '2026-09-12 07:17:36', '2026-09-12 07:19:04'),
(19, 1, 1, 'تست 7', '5یر', 'medium', 'completed', NULL, '2026-09-12 07:32:25', '2026-09-13 18:19:09'),
(21, 3, 1, 'تست9', '1', 'medium', 'completed', NULL, '2026-09-12 13:29:15', '2026-09-12 13:29:33'),
(22, 3, 1, 'نون سنگک', '۳ تا', 'medium', 'completed', NULL, '2026-09-13 12:00:29', '2026-09-13 12:01:04'),
(23, 3, 1, 'گوشت', '۳ کیلو', 'medium', 'completed', NULL, '2026-09-13 12:02:11', '2026-09-13 12:02:22'),
(24, 3, 1, 'بستنی قیفی مهن', '۱', 'medium', 'pending', NULL, '2026-09-13 12:06:51', '2026-09-13 12:06:51');

-- --------------------------------------------------------

--
-- Table structure for table `order_assignments`
--

CREATE TABLE `order_assignments` (
  `id` bigint UNSIGNED NOT NULL,
  `order_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_assignments`
--

INSERT INTO `order_assignments` (`id`, `order_id`, `user_id`, `assigned_at`, `completed_at`, `cancelled_at`) VALUES
(2, 2, 3, '2026-09-07 16:35:40', '2026-09-07 16:35:59', NULL),
(3, 3, 1, '2026-09-09 13:23:41', '2026-09-09 13:23:49', NULL),
(4, 4, 1, '2026-09-09 13:28:22', '2026-09-12 06:35:08', '2026-09-10 09:41:14'),
(5, 6, 1, '2026-09-10 09:43:28', '2026-09-12 06:34:45', '2026-09-10 09:43:44'),
(6, 6, 1, '2026-09-12 06:30:48', NULL, '2026-09-12 06:34:38'),
(7, 4, 1, '2026-09-12 06:30:59', NULL, NULL),
(12, 11, 1, '2026-09-12 06:40:58', NULL, '2026-09-12 06:41:10'),
(13, 11, 2, '2026-09-12 06:41:14', '2026-09-12 06:41:17', NULL),
(19, 16, 2, '2026-09-12 07:17:43', NULL, '2026-09-12 07:17:54'),
(20, 16, 2, '2026-09-12 07:19:00', '2026-09-12 07:19:04', NULL),
(23, 19, 2, '2026-09-12 07:32:35', NULL, '2026-09-12 07:50:01'),
(25, 21, 1, '2026-09-12 13:29:24', '2026-09-12 13:29:33', NULL),
(26, 19, 1, '2026-09-12 19:47:19', NULL, '2026-09-12 19:47:25'),
(27, 22, 1, '2026-09-13 12:00:33', '2026-09-13 12:01:04', NULL),
(28, 19, 1, '2026-09-13 18:12:23', NULL, '2026-09-13 18:12:25'),
(31, 19, 1, '2026-09-13 18:16:16', '2026-09-13 18:19:09', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `otp_codes`
--

CREATE TABLE `otp_codes` (
  `id` int UNSIGNED NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `otp_codes`
--

INSERT INTO `otp_codes` (`id`, `phone`, `ip_address`, `code`, `attempts`, `expires_at`, `used_at`, `created_at`) VALUES
(2, '09038680838', '5.126.127.230', '$2y$10$oqOWezCC6dRO/cZGJ7MVBOiOZtyUuxM7roQrVoIS9yEnmg5SZ3.LS', 0, '2026-09-13 17:59:59', '2026-09-13 14:30:12', '2026-09-13 14:27:59'),
(4, '09038680838', '5.126.127.230', '$2y$10$wxqWJj0sst4mTegpzm6hR.JSbwll7vWfCNY76FuORpKazt/RoFbby', 0, '2026-09-13 18:07:54', '2026-09-13 14:36:35', '2026-09-13 14:35:54'),
(5, '09153113119', '5.126.127.230', '$2y$10$aZi14kh/1uisUbABIE3kmO0ngX1OWbZH3JQjrviQvxeG01x6FOulG', 0, '2026-09-13 18:18:09', '2026-09-13 14:46:22', '2026-09-13 14:46:09'),
(6, '09038680838', '5.126.190.103', '$2y$10$/1aiTzPeqnl9Vx5M.MPCou54h.aEuoCRQT8NTjM8G2i9u6AYcpJ9a', 0, '2026-09-13 21:42:31', '2026-09-13 18:10:45', '2026-09-13 18:10:31');

-- --------------------------------------------------------

--
-- Table structure for table `temporary_login_users`
--

CREATE TABLE `temporary_login_users` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `temporary_login_users`
--

INSERT INTO `temporary_login_users` (`id`, `user_id`, `username`, `password_hash`, `created_at`) VALUES
(1, 1, 'amirsalar', '$2y$10$1VOO9tELm6sHmf.Gi4rVYOG8Bqdc/ynajCzBn5DqoABN8gZNbrKtG', '2026-09-05 15:06:33'),
(2, 2, 'test', '$2y$10$Sr/PVQRIKg0qDgAoD96JIOXiT4htAlIypIUSAblGyvssL4S/C.XCq', '2026-09-07 14:04:51'),
(3, 3, 'rozhin', '$2y$10$17zClIhNtPJJUUcjdF7co.XWqu/cdGnokiNVvUruCRoT4D/eM2Aty', '2026-09-07 16:33:36');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `phone`, `first_name`, `last_name`, `created_at`, `updated_at`) VALUES
(1, '09038680838', 'ت', 'ت', '2026-09-05 15:06:33', '2026-09-05 15:06:33'),
(2, '09038680828', 'کاربر', 'تست', '2026-09-07 14:04:51', '2026-09-07 14:04:51'),
(3, '09016502580', 'روژین', 'تمیمی', '2026-09-07 16:33:36', '2026-09-07 16:33:36'),
(4, '09153113119', 'امیرسالار', 'کروجی', '2026-09-13 14:46:22', '2026-09-13 14:46:36');

-- --------------------------------------------------------

--
-- Table structure for table `user_tokens`
--

CREATE TABLE `user_tokens` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_tokens`
--

INSERT INTO `user_tokens` (`id`, `user_id`, `token`, `expires_at`, `created_at`) VALUES
(1, 1, '38cd9a0e4ef3ebc31eb50cc584e583ce6ca033dd2de52ea3f2e7b9a505a8b9a8', '2026-10-05 18:36:33', '2026-09-05 15:06:33'),
(2, 1, 'b566a94ca9c014868a69e93fda23e6f2b200d04a7c0a7fe96ffdf6e742777aab', '2026-10-05 18:37:12', '2026-09-05 15:07:12'),
(3, 1, '824edc6cfffbfb85848f4d1bc886e020bd97a5fe005c9bde6ebd1e08fa36d11f', '2026-10-05 18:52:37', '2026-09-05 15:22:37'),
(4, 1, '1b310807574b20873ca07b9bbc12a7d16f778945271eb5df39fd5ed54de98caf', '2026-10-05 19:13:31', '2026-09-05 15:43:31'),
(5, 1, 'c1822e1a8bceef69f9025718184361321496457e15a028e7b73e7fe20ed9aca6', '2026-10-05 19:28:35', '2026-09-05 15:58:35'),
(6, 1, 'abd41e418c516d81608c35ec2831945b8cc9d222c8d665fa5f46c405f1405d99', '2026-10-07 16:55:31', '2026-09-07 13:25:31'),
(7, 2, '77d6d0d0368f96a952a991895da9d0750530896abac2e878cceffdf9c539f5bf', '2026-10-07 17:34:51', '2026-09-07 14:04:51'),
(8, 1, '443a88f908db76ee82290195b20fe2a3967854d8a9c34185b174eadff85bb04a', '2026-10-07 18:24:06', '2026-09-07 14:54:06'),
(9, 3, '022f640a610f59d8896af76bf869113a5a62d9e137a096fd3dab978120a48d71', '2026-10-07 20:03:36', '2026-09-07 16:33:36'),
(10, 1, '2f8d233de353d2a089a0f6f22a08341f2f307e01b1f31fe901a419ec4066a654', '2026-10-09 16:52:18', '2026-09-09 13:22:18'),
(11, 1, '971ad6810d4b2c0445da4848ee4fc602dc4bc87662e4ef75c2e95cdd5cb0bbee', '2026-10-09 18:19:07', '2026-09-09 14:49:07'),
(12, 1, '5a274aa046b77d48d792f4c39a78d579e78fc090b9525b1d8403de70b1d3249f', '2026-10-09 18:21:36', '2026-09-09 14:51:36'),
(13, 1, 'bb7c10439438c739647eb6cb137871d38cf3a7b8bc38847e23da6231710d5d29', '2026-10-10 10:58:39', '2026-09-10 07:28:39'),
(14, 1, 'e7a4c87b3968d0842967c88bdffdf8f99a5078dac8e77be8ab5dd044dfe85fca', '2026-10-10 12:45:58', '2026-09-10 09:15:58'),
(15, 2, 'e5d70260efd53981d145e7d648801540b8669a6233e22e39defe5428233b9168', '2026-10-10 13:10:55', '2026-09-10 09:40:55'),
(16, 1, 'a8ac3a506d746d4b4f9eb3fd3a57dbf4ad4539e927e1d5f12388a639e15ed8a5', '2026-10-10 13:10:56', '2026-09-10 09:40:56'),
(17, 1, 'd0888760629b3f90114d2af95ed1596ea89ac6146cacdd8ee22cbd5b5b25d64d', '2026-10-10 13:34:25', '2026-09-10 10:04:25'),
(18, 2, '9c0c2b6a5ecc777f597c153437c482b5a825098ebaa760f6e851664d9d5c7e11', '2026-10-10 13:38:12', '2026-09-10 10:08:12'),
(19, 2, 'c551e6dd2a579be09a45b32aeec1512c186e3a7ebb91b94632f1483361c3a0bf', '2026-10-10 13:41:14', '2026-09-10 10:11:14'),
(20, 2, '2ebe868306b268cb5daa1772eb9c2989786cdafc6824bc1668254f90dc3452e9', '2026-10-10 13:45:59', '2026-09-10 10:15:59'),
(21, 2, '8df168a8b66d72fbb5c170f31ada3ff28509c5e4c97046f8151936e699c75e2c', '2026-10-10 13:53:31', '2026-09-10 10:23:31'),
(22, 2, 'aa3b154dfa6cf241017a6bc899b67911d27d91e842d3b25a728e0c638c4c39b3', '2026-10-10 14:00:27', '2026-09-10 10:30:27'),
(23, 1, 'd695fc6fa8c7119bd9646a39c4ea76c57b72fdf7bf08192050ccf23b9546edaa', '2026-10-10 14:11:43', '2026-09-10 10:41:43'),
(24, 2, 'a07916c67ac2f2f5227854d1cf5a6ce7d6f78e5b942dd7f922f1fdfeaebac671', '2026-10-12 10:00:32', '2026-09-12 06:30:32'),
(25, 1, '730bd89b22539a8faf03fa6ee61a1ef2bbe4cfe38e7ab1921a510f69b342e7f6', '2026-10-12 10:00:35', '2026-09-12 06:30:35'),
(26, 1, '560df0409102d760f057f5ab7a72e1a9ef29cf42cef5840a88f7fc26004ffc1b', '2026-10-12 10:32:52', '2026-09-12 07:02:52'),
(27, 1, 'e4f33f1985f05a9da3b72a16adf19d7498cfecdc10b4caf0c7cbe48f9220f546', '2026-10-12 10:38:30', '2026-09-12 07:08:30'),
(28, 2, '0c3750e0f71255a90b27c20aa8c79810c017f6a8995f413c720568be410061b4', '2026-10-12 10:42:28', '2026-09-12 07:12:28'),
(29, 1, 'e80576e294e6a4f1a64d2913554e5c08ba8b0ccb81d68eec0327e52976e76422', '2026-10-12 11:18:50', '2026-09-12 07:48:50'),
(30, 2, '0a9fc4e2be1bd10032eb9d3826d4be911f373487861f0fdd6e7ea78ac43959c7', '2026-10-12 11:19:10', '2026-09-12 07:49:10'),
(31, 1, '939d63f1084d556b64c903aaa4841ccc4183c8a39333ba9ae2f3f6b1dd3ac80d', '2026-10-12 11:20:29', '2026-09-12 07:50:29'),
(32, 1, 'a6692f3916678d56d33269d87e05591025a604abd7ced16f884eb3f53aeaca97', '2026-10-12 12:24:05', '2026-09-12 08:54:05'),
(33, 1, '5e456571d0fd2cc8a6a31b0b668bb421d6c729d98a7631f6f423f0bfa7208e6e', '2026-10-12 13:57:29', '2026-09-12 10:27:29'),
(34, 1, '3a48c15f145402748242fb3286949e3d31746121b25ec65540f35ba063c7f838', '2026-10-12 14:18:54', '2026-09-12 10:48:54'),
(35, 1, 'b97660319da3ca47b7f95181808272fe27c1516394d74d5a458350f9c9c6f991', '2026-10-12 14:25:32', '2026-09-12 10:55:32'),
(36, 1, 'c52b22582a0ea569b053fffd47b75d0aec8884154ea4ff68f02165ad13e246b2', '2026-10-12 14:34:04', '2026-09-12 11:04:04'),
(37, 2, '5b1f76cf499fd1b1b9b4a4994af5146cde30b945d0fdffab499a07517daf827d', '2026-10-12 15:38:58', '2026-09-12 12:08:58'),
(38, 1, 'bf724591427ab4ccab823a9aa8a15cdc9528a093253d4f93ac7888cab8ec1926', '2026-10-12 16:47:19', '2026-09-12 13:17:19'),
(39, 1, 'b7dfdeddee24e8e96b66487cc058b854b56df24967577562d40304dc3796574b', '2026-10-12 17:41:51', '2026-09-12 14:11:51'),
(40, 1, '1dec4e1c1565968402b280efa0543dbdff5469ea4792c5d0d7e123d106a1bf0c', '2026-10-12 17:46:27', '2026-09-12 14:16:27'),
(41, 1, '85e0dc4ee5f6a1ef8b95e1fe1435e585226bde4b73994397b35621ff28b8b8d2', '2026-10-12 17:51:37', '2026-09-12 14:21:37'),
(42, 1, '7015886244bb50c6db3f0d1553a1d3af9a3c691834bdfb3100415bea5dc65de5', '2026-10-13 18:06:35', '2026-09-13 14:36:35'),
(43, 4, '172c6874dc4166dfc9e272e72f13107a739c6f06bcc8cf5dee8d588101d6ee88', '2026-10-13 18:16:22', '2026-09-13 14:46:22'),
(44, 1, 'd33e762abb6ace3adb7a007dc6eca163950bf457e8a32731717aa807ff6b6653', '2026-10-13 21:40:45', '2026-09-13 18:10:45');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_groups_creator` (`creator_id`);

--
-- Indexes for table `group_invites`
--
ALTER TABLE `group_invites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD UNIQUE KEY `unique_group_invite` (`group_id`),
  ADD UNIQUE KEY `unique_invite_token` (`token`);

--
-- Indexes for table `group_members`
--
ALTER TABLE `group_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_group_member` (`group_id`,`user_id`),
  ADD KEY `fk_group_members_user` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user_id` (`user_id`),
  ADD KEY `idx_notifications_user_read` (`user_id`,`is_read`),
  ADD KEY `idx_notifications_created_at` (`created_at`),
  ADD KEY `idx_notifications_group_id` (`group_id`),
  ADD KEY `idx_notifications_order_id` (`order_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_orders_group` (`group_id`),
  ADD KEY `fk_orders_creator` (`created_by`);

--
-- Indexes for table `order_assignments`
--
ALTER TABLE `order_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_assignments_order` (`order_id`),
  ADD KEY `fk_assignments_user` (`user_id`);

--
-- Indexes for table `otp_codes`
--
ALTER TABLE `otp_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_otp_phone` (`phone`),
  ADD KEY `idx_otp_ip_created` (`ip_address`,`created_at`);

--
-- Indexes for table `temporary_login_users`
--
ALTER TABLE `temporary_login_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_temporary_login_username` (`username`),
  ADD UNIQUE KEY `uq_temporary_login_user` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- Indexes for table `user_tokens`
--
ALTER TABLE `user_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_tokens_token` (`token`),
  ADD KEY `idx_user_tokens_user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `groups`
--
ALTER TABLE `groups`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `group_invites`
--
ALTER TABLE `group_invites`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `group_members`
--
ALTER TABLE `group_members`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=151;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `order_assignments`
--
ALTER TABLE `order_assignments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `otp_codes`
--
ALTER TABLE `otp_codes`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `temporary_login_users`
--
ALTER TABLE `temporary_login_users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user_tokens`
--
ALTER TABLE `user_tokens`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `groups`
--
ALTER TABLE `groups`
  ADD CONSTRAINT `fk_groups_creator` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `group_invites`
--
ALTER TABLE `group_invites`
  ADD CONSTRAINT `group_invites_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `group_members`
--
ALTER TABLE `group_members`
  ADD CONSTRAINT `fk_group_members_group` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_group_members_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_group` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_notifications_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_orders_group` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_assignments`
--
ALTER TABLE `order_assignments`
  ADD CONSTRAINT `fk_assignments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_assignments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `temporary_login_users`
--
ALTER TABLE `temporary_login_users`
  ADD CONSTRAINT `fk_temporary_login_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_tokens`
--
ALTER TABLE `user_tokens`
  ADD CONSTRAINT `fk_user_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
