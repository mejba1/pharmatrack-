-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 18, 2026 at 12:44 PM
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
-- Database: `pharmatrack`
--

-- --------------------------------------------------------

--
-- Table structure for table `alert_rules`
--

CREATE TABLE `alert_rules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `event_type` varchar(255) NOT NULL,
  `conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`conditions`)),
  `severity` enum('info','warning','critical') NOT NULL DEFAULT 'info',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `frequency` enum('once','daily','weekly','on_every_match') NOT NULL DEFAULT 'once',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `alert_rule_recipients`
--

CREATE TABLE `alert_rule_recipients` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `alert_rule_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `role` varchar(255) DEFAULT NULL,
  `channel` enum('in_app','email','sms') NOT NULL DEFAULT 'in_app',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `anti_counterfeit_codes`
--

CREATE TABLE `anti_counterfeit_codes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `batch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `uuc` varchar(255) NOT NULL,
  `serial_number` varchar(255) DEFAULT NULL,
  `aggregation_code` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `issued_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `anti_counterfeit_scans`
--

CREATE TABLE `anti_counterfeit_scans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code_id` bigint(20) UNSIGNED NOT NULL,
  `scanned_by` bigint(20) UNSIGNED DEFAULT NULL,
  `distributor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `scan_location` varchar(255) DEFAULT NULL,
  `country_code` varchar(5) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `device_id` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `result` enum('authentic','counterfeit','duplicate_scan','expired','unknown_code','error') NOT NULL DEFAULT 'authentic',
  `scan_count_at_time` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `scanned_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `batches`
--

CREATE TABLE `batches` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `brn` varchar(255) NOT NULL,
  `batch_number` varchar(255) NOT NULL,
  `lot_number` varchar(255) DEFAULT NULL,
  `manufacture_date` date NOT NULL,
  `expiry_date` date NOT NULL,
  `quantity_produced` int(11) NOT NULL,
  `quantity_available` int(11) NOT NULL DEFAULT 0,
  `quantity_extended` int(11) NOT NULL DEFAULT 0,
  `manufacturing_site` varchar(255) DEFAULT NULL,
  `manufacturing_country` varchar(5) DEFAULT NULL,
  `qc_status` enum('pending','released','quarantine','rejected','recalled') NOT NULL DEFAULT 'pending',
  `qc_approved_by` varchar(255) DEFAULT NULL,
  `qc_approval_date` date DEFAULT NULL,
  `coa_document_path` varchar(255) DEFAULT NULL,
  `storage_conditions` varchar(255) DEFAULT NULL,
  `storage_temp_min` decimal(5,2) DEFAULT NULL,
  `storage_temp_max` decimal(5,2) DEFAULT NULL,
  `status` enum('active','expired','recalled','quarantine','depleted') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `batches`
--

INSERT INTO `batches` (`id`, `product_id`, `brn`, `batch_number`, `lot_number`, `manufacture_date`, `expiry_date`, `quantity_produced`, `quantity_available`, `quantity_extended`, `manufacturing_site`, `manufacturing_country`, `qc_status`, `qc_approved_by`, `qc_approval_date`, `coa_document_path`, `storage_conditions`, `storage_temp_min`, `storage_temp_max`, `status`, `notes`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 2, 'BRN-00002-2606-001', '3030303', '78967', '2026-06-18', '2027-06-18', 100, 100, 0, 'Beacon Pharmaceuticals', 'BD', 'released', NULL, '2026-06-18', 'batches/1/coa/1781774888_6a33ba2837d88_uuc-report-wjxspjalhx-4.pdf', NULL, NULL, NULL, 'active', NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `batch_extensions`
--

CREATE TABLE `batch_extensions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `batch_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `partial_ref` varchar(255) NOT NULL,
  `additional_quantity` int(11) NOT NULL,
  `serial_mode` enum('continue','restart') NOT NULL DEFAULT 'continue',
  `serial_start` int(10) UNSIGNED NOT NULL,
  `serial_end` int(10) UNSIGNED NOT NULL,
  `manufacture_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `performed_by` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `batch_units`
--

CREATE TABLE `batch_units` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `batch_id` bigint(20) UNSIGNED NOT NULL,
  `partial_batch_ref` varchar(255) DEFAULT NULL,
  `serial_number` int(10) UNSIGNED NOT NULL,
  `secret_code` varchar(16) NOT NULL,
  `unique_number` varchar(255) NOT NULL,
  `status` enum('generated','printing','packed','scanned','blocked','active','inactive','verified','expired') NOT NULL DEFAULT 'generated',
  `lock_reason` varchar(500) DEFAULT NULL,
  `locked_at` timestamp NULL DEFAULT NULL,
  `blocked_scan_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `last_blocked_scan_at` timestamp NULL DEFAULT NULL,
  `vpn_scan_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `last_vpn_scan_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `batch_units`
--

INSERT INTO `batch_units` (`id`, `batch_id`, `partial_batch_ref`, `serial_number`, `secret_code`, `unique_number`, `status`, `lock_reason`, `locked_at`, `blocked_scan_count`, `last_blocked_scan_at`, `vpn_scan_count`, `last_vpn_scan_at`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 1, 'UC75PCV8ZU', '6950291150', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(2, 1, NULL, 2, 'OUTSBC7LOX', '8945722689', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(3, 1, NULL, 3, 'A3BRY5SUEX', '7968990594', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(4, 1, NULL, 4, 'JOQJ1OBVOJ', '2114336622', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(5, 1, NULL, 5, 'GHQXVZKUIG', '6516184863', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(6, 1, NULL, 6, 'LUJV6QTBJF', '5708385958', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(7, 1, NULL, 7, 'YYNUTNIUF7', '5590317035', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(8, 1, NULL, 8, 'SJ6SKHZ91D', '7747598377', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(9, 1, NULL, 9, 'A01FND8W8W', '7728981752', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(10, 1, NULL, 10, 'DZXNIXAUP1', '6683625433', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(11, 1, NULL, 11, 'TPGJHODCAU', '9597471666', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(12, 1, NULL, 12, 'JMWIPKQM2B', '3466436551', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(13, 1, NULL, 13, 'E8CSHQTLBJ', '6795671341', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(14, 1, NULL, 14, 'Z8SZVTULFS', '1894862598', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(15, 1, NULL, 15, 'YZ4NVAXHWQ', '2622000039', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(16, 1, NULL, 16, 'RTK4V6JNEF', '4918768245', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(17, 1, NULL, 17, 'O1QSXYW2MT', '9168787182', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(18, 1, NULL, 18, 'YNDI8AZV5S', '9717347503', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(19, 1, NULL, 19, 'LG8TEMZWOY', '9581899656', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(20, 1, NULL, 20, 'OQ5GGIZ8BF', '8936091768', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(21, 1, NULL, 21, 'D7HQE2AZRF', '7706043944', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(22, 1, NULL, 22, '3RTSR7EDF5', '9556001143', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(23, 1, NULL, 23, 'JHFMLCO9BC', '8482200222', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(24, 1, NULL, 24, 'OF6EIN7LEZ', '5226055043', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(25, 1, NULL, 25, '9MUJPENTMQ', '1165319240', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(26, 1, NULL, 26, 'SEPQTE9U2P', '5257115150', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(27, 1, NULL, 27, 'O4AJZWLLQV', '9323193136', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(28, 1, NULL, 28, '0THPKMRK5P', '8366852557', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(29, 1, NULL, 29, 'SFWFGNAFCP', '8445840844', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(30, 1, NULL, 30, 'BSLG5JNOWM', '8644607689', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(31, 1, NULL, 31, 'HSDWSDLP62', '8932402792', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(32, 1, NULL, 32, 'GU0IZAXP27', '1246475940', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(33, 1, NULL, 33, 'TVKX90LMOV', '6807022064', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(34, 1, NULL, 34, 'HNOB2DFYNU', '3718627289', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(35, 1, NULL, 35, 'MVQXWTR2E8', '9628066965', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(36, 1, NULL, 36, 'TI7KDVTOPR', '5626225318', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(37, 1, NULL, 37, 'O8L6PVQHUO', '1254567106', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(38, 1, NULL, 38, 'RA00AQVIRO', '8226220513', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(39, 1, NULL, 39, '8JKHVYYJAU', '2375616169', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(40, 1, NULL, 40, 'KTMG2BW8UJ', '2080859208', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(41, 1, NULL, 41, 'NXYPVCQJPW', '8515196753', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(42, 1, NULL, 42, 'H0SGXKCRTS', '9995336705', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(43, 1, NULL, 43, '1WCIOGWEA1', '6020260046', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(44, 1, NULL, 44, 'LXUFOQXMUW', '2596365079', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(45, 1, NULL, 45, 'WJBDJ9HLVD', '6804983116', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(46, 1, NULL, 46, 'D37UPFSDDD', '8899793487', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(47, 1, NULL, 47, 'MR49WLO1NN', '9856771688', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(48, 1, NULL, 48, 'ONHR3UPAYG', '9530867839', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(49, 1, NULL, 49, '67CQBHLY6L', '9334516609', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(50, 1, NULL, 50, '8AI3SB4P7M', '1376187589', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(51, 1, NULL, 51, 'PPGXX0HYEM', '6224680658', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(52, 1, NULL, 52, '0HL7LC8RPF', '8489055505', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(53, 1, NULL, 53, 'WDYVGV30IX', '1456742932', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(54, 1, NULL, 54, 'E5YIGUDKBR', '5931185298', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(55, 1, NULL, 55, 'HM5LKLEW9G', '1942736687', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(56, 1, NULL, 56, 'STNRBPSBXG', '8156831277', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(57, 1, NULL, 57, '7AE1MTKLEM', '7776648290', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(58, 1, NULL, 58, '2LG35ZRZDW', '7616211404', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(59, 1, NULL, 59, 'D40V7WEQFX', '1528248691', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(60, 1, NULL, 60, 'MWVRHMHNIX', '6381815768', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(61, 1, NULL, 61, 'GKDZASTGZ4', '4235008833', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(62, 1, NULL, 62, 'WAFSX0VNVH', '9165523681', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(63, 1, NULL, 63, 'VQWAXFE8ZX', '6976182303', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(64, 1, NULL, 64, 'CTRBWF0LG2', '4518740732', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(65, 1, NULL, 65, 'NQAK3LNBOX', '7412332566', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(66, 1, NULL, 66, 'K230XWMHV6', '7436978756', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(67, 1, NULL, 67, 'NRZJCJ92TI', '9336180442', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(68, 1, NULL, 68, '0RWLHNG72X', '6342505750', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(69, 1, NULL, 69, 'UPVGKZCD1G', '4947000247', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(70, 1, NULL, 70, '7EJCPQKV44', '1319010187', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(71, 1, NULL, 71, 'PUPWPD6U8F', '1791175203', 'generated', NULL, NULL, 1, '2026-06-18 04:38:39', 0, NULL, '2026-06-18 03:28:08', '2026-06-18 04:38:39'),
(72, 1, NULL, 72, 'SJYH4KK2W8', '6043804777', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(73, 1, NULL, 73, 'UXB2GUHRCM', '2731926796', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(74, 1, NULL, 74, 'Z6DMBG2EOI', '1182471314', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(75, 1, NULL, 75, 'NXSN5KV3DO', '4595124929', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(76, 1, NULL, 76, 'MLCFFTWZLC', '7648410552', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(77, 1, NULL, 77, 'RRQG9SJECR', '7259015093', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(78, 1, NULL, 78, 'MUYFEQJ5FG', '6954768405', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(79, 1, NULL, 79, 'CXNDFQZGO2', '2536974120', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(80, 1, NULL, 80, 'FFUMFEVREB', '2734736910', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(81, 1, NULL, 81, 'ESPXYHQFZN', '9217625745', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(82, 1, NULL, 82, 'IOQBFY2KD8', '9978614419', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(83, 1, NULL, 83, 'D9LGHYYZVN', '7570075442', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(84, 1, NULL, 84, 'VRCL1SYKNK', '4968172204', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(85, 1, NULL, 85, '0OS4ZZXP7G', '2136878836', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(86, 1, NULL, 86, 'ROASEXIHMO', '2667050499', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(87, 1, NULL, 87, 'CO26NZMUJ3', '7344561070', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(88, 1, NULL, 88, 'IUM05X6DA3', '6244131374', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(89, 1, NULL, 89, 'VEI0U7ZUS5', '9726349197', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(90, 1, NULL, 90, 'CGHLLFTL6G', '9620625196', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(91, 1, NULL, 91, 'GLR6GMWNRU', '4929883842', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(92, 1, NULL, 92, 'QSLPKK4HOB', '5296957603', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(93, 1, NULL, 93, 'R5ZYO72MH3', '4989224681', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(94, 1, NULL, 94, 'LCEOSUETAY', '7410191802', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(95, 1, NULL, 95, 'NUYIUJHYPM', '3608719034', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(96, 1, NULL, 96, 'KGLOJMZJRN', '8373711869', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(97, 1, NULL, 97, 'GOCFB1VNS7', '1842323585', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(98, 1, NULL, 98, 'OPJNGFW470', '8441841894', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(99, 1, NULL, 99, 'O3TAO3YXKF', '1356566511', 'generated', NULL, NULL, 0, NULL, 0, NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(100, 1, NULL, 100, 'XGFDTPHGQ3', '5663488012', 'generated', 'Auto-locked: 8 repeated scans from 127.0.0.1.', '2026-06-18 04:39:38', 6, '2026-06-18 04:40:45', 0, NULL, '2026-06-18 03:28:08', '2026-06-18 04:40:45');

-- --------------------------------------------------------

--
-- Table structure for table `batch_unit_logs`
--

CREATE TABLE `batch_unit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `batch_id` bigint(20) UNSIGNED NOT NULL,
  `batch_unit_id` bigint(20) UNSIGNED DEFAULT NULL,
  `event` varchar(255) NOT NULL,
  `from_status` varchar(255) DEFAULT NULL,
  `to_status` varchar(255) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `performed_by` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `batch_unit_logs`
--

INSERT INTO `batch_unit_logs` (`id`, `batch_id`, `batch_unit_id`, `event`, `from_status`, `to_status`, `quantity`, `note`, `performed_by`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'units_generated', NULL, 'generated', 100, 'Generated 100 units on batch creation.', NULL, '2026-06-18 03:28:08', '2026-06-18 03:28:08'),
(2, 1, 100, 'scanned', NULL, NULL, NULL, 'Verification scan via QR code.', 'public', '2026-06-18 03:28:47', '2026-06-18 03:28:47'),
(3, 1, 100, 'scanned', NULL, NULL, NULL, 'Verification scan via QR code.', 'public', '2026-06-18 03:59:36', '2026-06-18 03:59:36'),
(4, 1, 100, 'scanned', NULL, NULL, NULL, 'Verification scan via QR code.', 'public', '2026-06-18 04:17:51', '2026-06-18 04:17:51'),
(5, 1, 100, 'scanned', NULL, NULL, NULL, 'Verification scan via QR code.', 'public', '2026-06-18 04:36:55', '2026-06-18 04:36:55'),
(6, 1, 100, 'scanned', NULL, NULL, NULL, 'Verification scan via QR code.', 'public', '2026-06-18 04:36:58', '2026-06-18 04:36:58'),
(7, 1, 100, 'scanned', NULL, NULL, NULL, 'Verification scan via QR code.', 'public', '2026-06-18 04:37:03', '2026-06-18 04:37:03'),
(8, 1, 100, 'scanned', NULL, NULL, NULL, 'Verification scan via QR code.', 'public', '2026-06-18 04:37:48', '2026-06-18 04:37:48'),
(9, 1, 71, 'scanned', NULL, NULL, NULL, 'Verification scan via QR code.', 'public', '2026-06-18 04:37:56', '2026-06-18 04:37:56'),
(10, 1, 71, 'scanned', NULL, NULL, NULL, 'Verification scan via QR code.', 'public', '2026-06-18 04:37:59', '2026-06-18 04:37:59'),
(11, 1, 71, 'scanned', NULL, NULL, NULL, 'Verification scan via QR code.', 'public', '2026-06-18 04:38:17', '2026-06-18 04:38:17'),
(12, 1, 71, 'scanned', NULL, NULL, NULL, 'Verification scan via QR code.', 'public', '2026-06-18 04:38:18', '2026-06-18 04:38:18'),
(13, 1, 71, 'scanned', NULL, NULL, NULL, 'Verification scan via QR code.', 'public', '2026-06-18 04:38:39', '2026-06-18 04:38:39'),
(14, 1, 73, 'scanned', NULL, NULL, NULL, 'Verification scan via QR code.', 'public', '2026-06-18 04:38:46', '2026-06-18 04:38:46'),
(15, 1, 100, 'scanned', NULL, NULL, NULL, 'Verification scan via QR code.', 'public', '2026-06-18 04:39:38', '2026-06-18 04:39:38'),
(16, 1, 100, 'scanned', NULL, NULL, NULL, 'Verification scan via QR code.', 'public', '2026-06-18 04:40:05', '2026-06-18 04:40:05'),
(17, 1, 100, 'scanned', NULL, NULL, NULL, 'Verification scan via QR code.', 'public', '2026-06-18 04:40:45', '2026-06-18 04:40:45'),
(18, 1, 73, 'scanned', NULL, NULL, NULL, 'Verification scan via QR code.', 'public', '2026-06-18 04:42:35', '2026-06-18 04:42:35'),
(19, 1, 73, 'scanned', NULL, NULL, NULL, 'Verification scan via QR code.', 'public', '2026-06-18 04:43:07', '2026-06-18 04:43:07'),
(20, 1, 73, 'scanned', NULL, NULL, NULL, 'Verification scan via QR code.', 'public', '2026-06-18 04:43:24', '2026-06-18 04:43:24');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `commercial_invoices`
--

CREATE TABLE `commercial_invoices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ci_number` varchar(255) NOT NULL,
  `proforma_invoice_id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `ci_date` date NOT NULL,
  `hs_code` varchar(20) NOT NULL,
  `country_of_origin` varchar(5) NOT NULL DEFAULT 'US',
  `incoterms` varchar(10) DEFAULT NULL,
  `port_of_loading` varchar(255) DEFAULT NULL,
  `port_of_discharge` varchar(255) DEFAULT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `payment_terms` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `bank_account_number` varchar(255) DEFAULT NULL,
  `bank_swift_code` varchar(15) DEFAULT NULL,
  `subtotal` decimal(16,2) NOT NULL DEFAULT 0.00,
  `freight` decimal(16,2) NOT NULL DEFAULT 0.00,
  `insurance` decimal(16,2) NOT NULL DEFAULT 0.00,
  `total_value` decimal(16,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','pending_approval','approved','shipment_created','cancelled') NOT NULL DEFAULT 'draft',
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `commercial_invoice_lines`
--

CREATE TABLE `commercial_invoice_lines` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `commercial_invoice_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `batch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `line_number` int(11) NOT NULL,
  `product_description` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(12,4) NOT NULL,
  `line_total` decimal(16,2) NOT NULL,
  `unit_of_measure` varchar(20) NOT NULL DEFAULT 'unit',
  `net_weight_kg` decimal(10,3) DEFAULT NULL,
  `gross_weight_kg` decimal(10,3) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `consignments`
--

CREATE TABLE `consignments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `consignment_number` varchar(255) NOT NULL,
  `qr_code` varchar(255) NOT NULL,
  `origin` varchar(255) NOT NULL DEFAULT 'Factory',
  `destination` varchar(255) DEFAULT NULL,
  `carrier` varchar(255) DEFAULT NULL,
  `vehicle_no` varchar(255) DEFAULT NULL,
  `status` enum('created','dispatched','in_transit','received','closed') NOT NULL DEFAULT 'created',
  `cartons_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `units_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `dispatched_at` timestamp NULL DEFAULT NULL,
  `received_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `consignment_scans`
--

CREATE TABLE `consignment_scans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `consignment_id` bigint(20) UNSIGNED NOT NULL,
  `event` varchar(255) NOT NULL,
  `performed_by` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `counterfeit_reports`
--

CREATE TABLE `counterfeit_reports` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuc_code` varchar(255) NOT NULL,
  `verification_log_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `batch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `reporter_name` varchar(255) NOT NULL,
  `reporter_phone` varchar(255) NOT NULL,
  `reporter_email` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'new',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `countries`
--

CREATE TABLE `countries` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(3) NOT NULL,
  `flag` varchar(16) DEFAULT NULL,
  `dial_code` varchar(10) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `region` varchar(255) DEFAULT NULL,
  `currency_code` varchar(3) DEFAULT NULL,
  `import_permitted` tinyint(1) NOT NULL DEFAULT 1,
  `import_license_required` tinyint(1) NOT NULL DEFAULT 0,
  `gmp_certificate_required` tinyint(1) NOT NULL DEFAULT 0,
  `product_registration_required` tinyint(1) NOT NULL DEFAULT 1,
  `regulatory_authority` varchar(255) DEFAULT NULL,
  `regulatory_status` enum('approved','restricted','pending','banned') NOT NULL DEFAULT 'approved',
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `countries`
--

INSERT INTO `countries` (`id`, `code`, `flag`, `dial_code`, `name`, `region`, `currency_code`, `import_permitted`, `import_license_required`, `gmp_certificate_required`, `product_registration_required`, `regulatory_authority`, `regulatory_status`, `notes`, `is_active`, `created_at`, `updated_at`, `deleted_at`) VALUES
(7, 'BD', '🇧🇩', '+880', 'Bangladesh', 'Asia', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:53', '2026-06-10 23:12:53', NULL),
(8, 'IN', '🇮🇳', '+91', 'India', 'Asia', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:53', '2026-06-10 23:12:53', NULL),
(9, 'US', '🇺🇸', '+1', 'United States', 'North America', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:52', '2026-06-10 23:12:52', NULL),
(10, 'CA', '🇨🇦', '+1', 'Canada', 'North America', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:52', '2026-06-10 23:12:52', NULL),
(11, 'MX', '🇲🇽', '+52', 'Mexico', 'North America', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:52', '2026-06-10 23:12:52', NULL),
(12, 'BR', '🇧🇷', '+55', 'Brazil', 'South America', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:52', '2026-06-10 23:12:52', NULL),
(13, 'AR', '🇦🇷', '+54', 'Argentina', 'South America', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:52', '2026-06-15 04:08:59', NULL),
(14, 'CL', '🇨🇱', '+56', 'Chile', 'South America', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:52', '2026-06-10 23:12:52', NULL),
(15, 'CO', '🇨🇴', '+57', 'Colombia', 'South America', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:52', '2026-06-10 23:12:52', NULL),
(16, 'GB', '🇬🇧', '+44', 'United Kingdom', 'Europe', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:52', '2026-06-10 23:12:52', NULL),
(17, 'IE', '🇮🇪', '+353', 'Ireland', 'Europe', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:52', '2026-06-10 23:12:52', NULL),
(18, 'FR', '🇫🇷', '+33', 'France', 'Europe', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:52', '2026-06-10 23:12:52', NULL),
(19, 'DE', '🇩🇪', '+49', 'Germany', 'Europe', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:52', '2026-06-10 23:12:52', NULL),
(20, 'ES', '🇪🇸', '+34', 'Spain', 'Europe', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:52', '2026-06-10 23:12:52', NULL),
(21, 'PT', '🇵🇹', '+351', 'Portugal', 'Europe', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:52', '2026-06-10 23:12:52', NULL),
(22, 'IT', '🇮🇹', '+39', 'Italy', 'Europe', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:52', '2026-06-10 23:12:52', NULL),
(23, 'NL', '🇳🇱', '+31', 'Netherlands', 'Europe', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:52', '2026-06-10 23:12:52', NULL),
(24, 'BE', '🇧🇪', '+32', 'Belgium', 'Europe', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:52', '2026-06-10 23:12:52', NULL),
(25, 'CH', '🇨🇭', '+41', 'Switzerland', 'Europe', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:52', '2026-06-10 23:12:52', NULL),
(26, 'AT', '🇦🇹', '+43', 'Austria', 'Europe', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:52', '2026-06-10 23:12:52', NULL),
(27, 'SE', '🇸🇪', '+46', 'Sweden', 'Europe', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:52', '2026-06-10 23:12:52', NULL),
(28, 'NO', '🇳🇴', '+47', 'Norway', 'Europe', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:52', '2026-06-10 23:12:52', NULL),
(29, 'DK', '🇩🇰', '+45', 'Denmark', 'Europe', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:52', '2026-06-10 23:12:52', NULL),
(30, 'FI', '🇫🇮', '+358', 'Finland', 'Europe', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:52', '2026-06-10 23:12:52', NULL),
(31, 'PL', '🇵🇱', '+48', 'Poland', 'Europe', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:53', '2026-06-10 23:12:53', NULL),
(32, 'CZ', '🇨🇿', '+420', 'Czechia', 'Europe', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:53', '2026-06-10 23:12:53', NULL),
(33, 'GR', '🇬🇷', '+30', 'Greece', 'Europe', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:53', '2026-06-10 23:12:53', NULL),
(34, 'RU', '🇷🇺', '+7', 'Russia', 'Europe', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:53', '2026-06-10 23:12:53', NULL),
(35, 'TR', '🇹🇷', '+90', 'Turkey', 'Europe', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:53', '2026-06-10 23:12:53', NULL),
(36, 'ZA', '🇿🇦', '+27', 'South Africa', 'Africa', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:53', '2026-06-10 23:12:53', NULL),
(37, 'NG', '🇳🇬', '+234', 'Nigeria', 'Africa', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:53', '2026-06-10 23:12:53', NULL),
(38, 'KE', '🇰🇪', '+254', 'Kenya', 'Africa', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:53', '2026-06-10 23:12:53', NULL),
(39, 'EG', '🇪🇬', '+20', 'Egypt', 'Africa', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:53', '2026-06-10 23:12:53', NULL),
(40, 'GH', '🇬🇭', '+233', 'Ghana', 'Africa', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:53', '2026-06-10 23:12:53', NULL),
(41, 'ET', '🇪🇹', '+251', 'Ethiopia', 'Africa', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:53', '2026-06-10 23:12:53', NULL),
(42, 'TZ', '🇹🇿', '+255', 'Tanzania', 'Africa', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:53', '2026-06-10 23:12:53', NULL),
(43, 'MA', '🇲🇦', '+212', 'Morocco', 'Africa', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:53', '2026-06-10 23:12:53', NULL),
(44, 'SA', '🇸🇦', '+966', 'Saudi Arabia', 'Middle East', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:53', '2026-06-10 23:12:53', NULL),
(45, 'AE', '🇦🇪', '+971', 'United Arab Emirates', 'Middle East', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:53', '2026-06-10 23:12:53', NULL),
(46, 'IL', '🇮🇱', '+972', 'Israel', 'Middle East', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:53', '2026-06-10 23:12:53', NULL),
(47, 'JO', '🇯🇴', '+962', 'Jordan', 'Middle East', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:53', '2026-06-10 23:12:53', NULL),
(48, 'PK', '🇵🇰', '+92', 'Pakistan', 'Asia', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:53', '2026-06-10 23:12:53', NULL),
(49, 'LK', '🇱🇰', '+94', 'Sri Lanka', 'Asia', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:53', '2026-06-10 23:12:53', NULL),
(50, 'CN', '🇨🇳', '+86', 'China', 'Asia', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:53', '2026-06-10 23:12:53', NULL),
(51, 'JP', '🇯🇵', '+81', 'Japan', 'Asia', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:53', '2026-06-10 23:12:53', NULL),
(52, 'KR', '🇰🇷', '+82', 'South Korea', 'Asia', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:53', '2026-06-10 23:12:53', NULL),
(53, 'ID', '🇮🇩', '+62', 'Indonesia', 'Asia', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:53', '2026-06-10 23:12:53', NULL),
(54, 'MY', '🇲🇾', '+60', 'Malaysia', 'Asia', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:53', '2026-06-10 23:12:53', NULL),
(55, 'SG', '🇸🇬', '+65', 'Singapore', 'Asia', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:53', '2026-06-10 23:12:53', NULL),
(56, 'TH', '🇹🇭', '+66', 'Thailand', 'Asia', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:53', '2026-06-10 23:12:53', NULL),
(57, 'VN', '🇻🇳', '+84', 'Vietnam', 'Asia', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:53', '2026-06-10 23:12:53', NULL),
(58, 'PH', '🇵🇭', '+63', 'Philippines', 'Asia', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:53', '2026-06-10 23:12:53', NULL),
(59, 'AU', '🇦🇺', '+61', 'Australia', 'Oceania', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:53', '2026-06-10 23:12:53', NULL),
(60, 'NZ', '🇳🇿', '+64', 'New Zealand', 'Oceania', NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:12:53', '2026-06-10 23:12:53', NULL),
(61, 'CAD', NULL, NULL, 'Cade Ramos', NULL, NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:06:07', '2026-06-10 23:06:07', NULL),
(62, 'QUO', NULL, NULL, 'Quo quisquam soluta', NULL, NULL, 1, 0, 0, 1, NULL, 'approved', NULL, 1, '2026-06-10 23:06:07', '2026-06-10 23:06:07', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `country_authorizations`
--

CREATE TABLE `country_authorizations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `country_code` varchar(2) NOT NULL,
  `country_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dispensing_records`
--

CREATE TABLE `dispensing_records` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `prescription_line_id` bigint(20) UNSIGNED DEFAULT NULL,
  `patient_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `batch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dispensed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `distributor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `quantity_dispensed` decimal(10,2) NOT NULL,
  `unit_of_measure` varchar(30) NOT NULL DEFAULT 'unit',
  `lot_number` varchar(60) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `unit_price` decimal(12,4) DEFAULT NULL,
  `total_price` decimal(16,2) DEFAULT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `notes` text DEFAULT NULL,
  `dispensed_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `distributors`
--

CREATE TABLE `distributors` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `country_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('manufacturer','national_distributor','regional_distributor','sub_distributor','retailer','hospital','pharmacy') NOT NULL,
  `license_number` varchar(255) DEFAULT NULL,
  `gmp_certificate_number` varchar(255) DEFAULT NULL,
  `license_expiry` date DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` enum('active','suspended','expired','pending') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `master_cartons`
--

CREATE TABLE `master_cartons` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `consignment_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `batch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `carton_number` varchar(255) NOT NULL,
  `qr_code` varchar(255) NOT NULL,
  `carton_type` enum('standard','generic') NOT NULL DEFAULT 'standard',
  `label` varchar(255) DEFAULT NULL,
  `capacity` int(10) UNSIGNED NOT NULL,
  `packed_quantity` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `serial_start` int(10) UNSIGNED DEFAULT NULL,
  `serial_end` int(10) UNSIGNED DEFAULT NULL,
  `status` varchar(40) NOT NULL DEFAULT 'created',
  `carton_condition` varchar(20) NOT NULL DEFAULT 'good',
  `condition_note` text DEFAULT NULL,
  `evidence_path` varchar(255) DEFAULT NULL,
  `received_location` varchar(255) DEFAULT NULL,
  `dispatched_at` timestamp NULL DEFAULT NULL,
  `received_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `master_carton_contents`
--

CREATE TABLE `master_carton_contents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `master_carton_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `batch_id` bigint(20) UNSIGNED NOT NULL,
  `partial_batch_ref` varchar(255) DEFAULT NULL,
  `serial_start` int(10) UNSIGNED NOT NULL,
  `serial_end` int(10) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `master_carton_scans`
--

CREATE TABLE `master_carton_scans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `master_carton_id` bigint(20) UNSIGNED NOT NULL,
  `event` enum('scanned','dispatched','received') NOT NULL DEFAULT 'scanned',
  `performed_by` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000001_create_cache_table', 1),
(2, '0001_01_01_000002_create_jobs_table', 1),
(3, '2026_01_01_000001_create_users_table', 1),
(4, '2026_01_01_000002_create_countries_table', 1),
(5, '2026_01_01_000003_create_distributors_table', 1),
(6, '2026_01_01_000004_create_products_table', 1),
(7, '2026_01_01_000005_create_batches_table', 1),
(8, '2026_01_01_000006_create_purchase_orders_table', 1),
(9, '2026_01_01_000007_create_sales_orders_table', 1),
(10, '2026_01_01_000008_create_proforma_invoices_table', 1),
(11, '2026_01_01_000009_create_commercial_invoices_table', 1),
(12, '2026_01_01_000010_create_shipments_table', 1),
(13, '2026_01_01_000011_create_order_documents_table', 1),
(14, '2026_01_01_000012_create_anti_counterfeit_scans_table', 1),
(15, '2026_01_01_000013_create_document_vault_table', 1),
(16, '2026_01_01_000014_create_patient_records_table', 1),
(17, '2026_01_01_000015_create_notifications_table', 1),
(18, '2026_01_01_000016_create_reports_table', 1),
(19, '2026_06_10_000001_add_therapeutic_class_to_products', 2),
(20, '2026_06_10_000002_create_product_images_table', 2),
(21, '2026_06_11_000001_add_website_and_pdf_to_products', 3),
(22, '2026_06_11_000002_create_therapeutic_classes_table', 4),
(23, '2026_06_11_000003_add_flag_and_dial_code_to_countries', 5),
(24, '2026_06_11_000004_create_batch_units_table', 6),
(25, '2026_06_11_000005_create_batch_unit_logs_table', 7),
(26, '2026_06_14_000001_add_partial_batch_support', 8),
(27, '2026_06_14_000002_create_batch_extensions_table', 8),
(28, '2026_06_14_000003_add_dates_to_batch_extensions', 9),
(29, '2026_06_14_000004_create_master_cartons_table', 10),
(30, '2026_06_14_000005_create_master_carton_scans_table', 10),
(31, '2026_06_14_000006_add_contents_support_to_master_cartons', 11),
(32, '2026_06_15_000001_create_consignments_table', 12),
(33, '2026_06_15_000002_expand_carton_status_and_condition', 13),
(34, '2026_06_15_000003_add_scale_indexes', 14),
(35, '2026_06_15_000004_add_consignment_summary_columns', 14),
(36, '2026_06_16_000001_create_anti_counterfeit_tables', 15),
(37, '2026_06_16_000002_create_verification_policies_table', 16),
(38, '2026_06_16_000003_create_verification_daily_stats_table', 17),
(39, '2026_06_16_000004_create_counterfeit_reports_table', 18),
(40, '2026_06_16_000005_create_product_info_requests_table', 19),
(41, '2026_06_16_000006_create_settings_table', 20),
(42, '2026_06_17_000001_add_verify_open_to_products', 21),
(43, '2026_06_18_000001_add_lock_tracking_to_batch_units', 22),
(44, '2026_06_18_000002_add_ip_vpn_controls_to_policies', 23);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `alert_rule_id` bigint(20) UNSIGNED DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  `severity` enum('info','warning','critical') NOT NULL DEFAULT 'info',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `notifiable_type` varchar(255) DEFAULT NULL,
  `notifiable_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action_url` varchar(255) DEFAULT NULL,
  `action_label` varchar(80) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `is_dismissed` tinyint(1) NOT NULL DEFAULT 0,
  `dismissed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_documents`
--

CREATE TABLE `order_documents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `documentable_type` varchar(255) NOT NULL,
  `documentable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `disk` varchar(255) NOT NULL DEFAULT 'local',
  `file_type` varchar(50) DEFAULT NULL,
  `extension` varchar(10) DEFAULT NULL,
  `file_size` bigint(20) UNSIGNED DEFAULT NULL,
  `icon_class` varchar(30) DEFAULT NULL,
  `uploaded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `version` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `patient_ref` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other','prefer_not_to_say') DEFAULT NULL,
  `national_id` varchar(50) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `country_id` bigint(20) UNSIGNED DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `chronic_conditions` text DEFAULT NULL,
  `blood_group` varchar(5) DEFAULT NULL,
  `registered_by` bigint(20) UNSIGNED DEFAULT NULL,
  `distributor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `prescription_ref` varchar(255) NOT NULL,
  `patient_id` bigint(20) UNSIGNED NOT NULL,
  `prescribing_doctor` varchar(255) DEFAULT NULL,
  `prescribing_facility` varchar(255) DEFAULT NULL,
  `prescribed_date` date NOT NULL,
  `valid_until` date DEFAULT NULL,
  `diagnosis_notes` text DEFAULT NULL,
  `status` enum('active','dispensed','partially_dispensed','expired','cancelled') NOT NULL DEFAULT 'active',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prescription_lines`
--

CREATE TABLE `prescription_lines` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `prescription_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `dosage_instructions` varchar(255) NOT NULL,
  `quantity_prescribed` decimal(10,2) NOT NULL,
  `unit_of_measure` varchar(30) NOT NULL DEFAULT 'unit',
  `duration_days` int(11) DEFAULT NULL,
  `quantity_dispensed` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_fully_dispensed` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `prn` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `generic_name` varchar(255) DEFAULT NULL,
  `brand_name` varchar(255) DEFAULT NULL,
  `dosage_form` enum('tablet','capsule','injection','syrup','cream','ointment','drops','inhaler','other') NOT NULL,
  `strength` varchar(255) DEFAULT NULL,
  `pack_size` varchar(255) DEFAULT NULL,
  `atc_code` varchar(20) DEFAULT NULL,
  `therapeutic_class` varchar(255) DEFAULT NULL,
  `hs_code` varchar(20) DEFAULT NULL,
  `controlled_substance` enum('no','schedule_1','schedule_2','schedule_3') NOT NULL DEFAULT 'no',
  `manufacturer_name` varchar(255) DEFAULT NULL,
  `manufacturing_site` varchar(255) DEFAULT NULL,
  `country_of_origin` varchar(5) DEFAULT NULL,
  `shelf_life` varchar(255) DEFAULT NULL,
  `storage_conditions` varchar(255) DEFAULT NULL,
  `temperature_sensitivity` enum('ambient','cool_chain','cold_chain','frozen') NOT NULL DEFAULT 'ambient',
  `unit_cost` decimal(12,4) DEFAULT NULL,
  `unit_of_measure` varchar(20) NOT NULL DEFAULT 'unit',
  `status` enum('active','discontinued','pending_approval') NOT NULL DEFAULT 'active',
  `verify_open` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `website_url` varchar(255) DEFAULT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `prn`, `name`, `generic_name`, `brand_name`, `dosage_form`, `strength`, `pack_size`, `atc_code`, `therapeutic_class`, `hs_code`, `controlled_substance`, `manufacturer_name`, `manufacturing_site`, `country_of_origin`, `shelf_life`, `storage_conditions`, `temperature_sensitivity`, `unit_cost`, `unit_of_measure`, `status`, `verify_open`, `notes`, `website_url`, `pdf_path`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'PRN-VELIT-INJ-00001', 'Salvador Vinson1', 'Nissim Vasquez', NULL, 'injection', 'Tempore sed quo inv', 'Eos Nam eligendi vo', 'Dolorem ad provident', NULL, 'Do omnis ex mollitia', 'schedule_1', 'Allegra Daniels', 'Dolores aliquid rem', 'Velit', 'Et quia nobis odio a', 'Distinctio Non et e', 'cool_chain', 50.0000, 'unit', 'active', 0, 'Aut est autem in com', NULL, NULL, '2026-06-10 04:34:44', '2026-06-10 21:45:40', '2026-06-10 21:45:40'),
(2, 'PRN-OMNIS-CRM-00001', 'Baricinix 2', 'Baricitinib', NULL, 'tablet', '2 mg', 'Tablet', 'N/A', 'Oncology / Antineoplastics', 'N/A', 'no', 'Beacon Pharmaceuticals PLC', NULL, 'BD', 'Deserunt et qui pers', 'Corrupti nemo et en', 'frozen', 90.0000, 'unit', 'active', 0, 'Omnis soluta iste qu', 'https://baricinix.com', NULL, '2026-06-10 21:05:22', '2026-06-17 03:07:36', NULL),
(5, 'PRN-APERI-CRM-00001', 'Tagrix 80 mg', 'Osimartinib', NULL, 'tablet', '80 mg', '3x10\'s Alu-Alu', 'Delectus qui cupida', 'Oncology / Antineoplastics', 'TG2', 'schedule_3', 'Beacon Pharmaceuticals PLC', 'Non accusamus vitae', 'BD', 'Sit incididunt id ad', 'Dolores rerum dicta', 'frozen', 200.0000, 'unit', 'active', 0, 'Nisi rerum qui volup', 'https://tagrix.net', 'products/5/docs/1781151397_6a2a36a5a9d03_general-product-list-english.pdf', '2026-06-10 22:16:37', '2026-06-14 04:16:09', NULL),
(7, 'PRN-CAD-INJ-00001', 'Lenvanix 10', 'Lenvatinib', NULL, 'capsule', '4 mg', '30\'s Pot', 'N/A', 'Oncology / Antineoplastics', 'N/A', 'schedule_1', 'Beacon Pharmaceuticals PLC', NULL, 'BD', '36', 'Doloremque quis fugi', 'frozen', 12.0000, 'unit', 'active', 0, 'Porro anim reiciendi', 'https://www.lenvanix.com', 'products/7/docs/1781411253_6a2e2db540ae8_special-product-china.pdf', '2026-06-10 23:06:07', '2026-06-14 04:18:09', NULL),
(9, 'PRN-CH-SYR-00001', 'Maite Howell', 'Dillon Potts', NULL, 'syrup', 'Sit sed dolores in', 'Ipsam voluptates qui', 'Aspernatur irure eum', 'Antibiotics / Antimicrobials', 'Sint veritatis non a', 'schedule_3', 'Irene Morris', 'Laborum Et mollitia', 'CH', 'Modi aut elit possi', 'Non est veritatis d', 'ambient', 10.0000, 'unit', 'active', 1, 'Omnis consequat Min', 'https://www.sado.ws', NULL, '2026-06-17 21:54:02', '2026-06-17 21:54:44', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_country_registrations`
--

CREATE TABLE `product_country_registrations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `country_id` bigint(20) UNSIGNED NOT NULL,
  `local_registration_number` varchar(255) DEFAULT NULL,
  `registration_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` enum('approved','pending','rejected','expired') NOT NULL DEFAULT 'approved',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `path` varchar(255) NOT NULL,
  `mime_type` varchar(50) DEFAULT NULL,
  `size` bigint(20) UNSIGNED DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `filename`, `original_name`, `path`, `mime_type`, `size`, `is_primary`, `sort_order`, `created_at`, `updated_at`) VALUES
(4, 5, '1781151397_6a2a36a5a6e9d_tagrix-osimertinib-tablets.png', 'Tagrix-Osimertinib-Tablets.png', 'products/5/1781151397_6a2a36a5a6e9d_tagrix-osimertinib-tablets.png', 'image/png', 71053, 1, 0, '2026-06-10 22:16:37', '2026-06-17 21:53:28'),
(6, 2, '1781431647_6a2e7d5f00ade_baricinix-2-baricitinib.jpg', 'baricinix-2-baricitinib.jpg', 'products/2/1781431647_6a2e7d5f00ade_baricinix-2-baricitinib.jpg', 'image/jpeg', 91465, 1, 0, '2026-06-14 04:07:27', '2026-06-14 04:16:21'),
(7, 7, '1781754741_6a336b75eaafc_lenvanix-10-lenvatinib.jpg', 'lenvanix-10-lenvatinib.jpg', 'products/7/1781754741_6a336b75eaafc_lenvanix-10-lenvatinib.jpg', 'image/jpeg', 28273, 1, 0, '2026-06-17 21:52:21', '2026-06-17 21:52:21'),
(8, 5, '1781754808_6a336bb8d92c5_tagrix-osimertinib-80.jpg', 'tagrix-osimertinib-80.jpg', 'products/5/1781754808_6a336bb8d92c5_tagrix-osimertinib-80.jpg', 'image/jpeg', 49366, 0, 1, '2026-06-17 21:53:28', '2026-06-17 21:53:28');

-- --------------------------------------------------------

--
-- Table structure for table `product_info_requests`
--

CREATE TABLE `product_info_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuc_code` varchar(255) NOT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `batch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `partner_name` varchar(255) DEFAULT NULL,
  `partner_phone` varchar(255) DEFAULT NULL,
  `whatsapp` varchar(255) DEFAULT NULL,
  `emailed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_recalls`
--

CREATE TABLE `product_recalls` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `recall_number` varchar(255) NOT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `batch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `scope` varchar(255) NOT NULL DEFAULT 'batch',
  `country_code` varchar(2) DEFAULT NULL,
  `severity` varchar(255) NOT NULL DEFAULT 'normal',
  `reason` text DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `recalled_by` varchar(255) DEFAULT NULL,
  `recalled_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `proforma_invoices`
--

CREATE TABLE `proforma_invoices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `pi_number` varchar(255) NOT NULL,
  `sales_order_id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `pi_date` date NOT NULL,
  `valid_until` date NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `incoterms` varchar(10) DEFAULT NULL,
  `port_of_loading` varchar(255) DEFAULT NULL,
  `payment_terms` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `bank_account_number` varchar(255) DEFAULT NULL,
  `bank_swift_code` varchar(15) DEFAULT NULL,
  `bank_iban` varchar(255) DEFAULT NULL,
  `subtotal` decimal(16,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(16,2) NOT NULL DEFAULT 0.00,
  `freight` decimal(16,2) NOT NULL DEFAULT 0.00,
  `total_value` decimal(16,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','sent','pending_approval','approved','rejected') NOT NULL DEFAULT 'draft',
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `proforma_invoice_lines`
--

CREATE TABLE `proforma_invoice_lines` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `proforma_invoice_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `batch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `line_number` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(12,4) NOT NULL,
  `line_total` decimal(16,2) NOT NULL,
  `unit_of_measure` varchar(20) NOT NULL DEFAULT 'unit',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `po_number` varchar(255) NOT NULL,
  `buyer_id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `po_date` date NOT NULL,
  `required_by_date` date NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `payment_terms` varchar(255) NOT NULL,
  `incoterms` varchar(10) DEFAULT NULL,
  `port_of_loading` varchar(255) DEFAULT NULL,
  `port_of_discharge` varchar(255) DEFAULT NULL,
  `subtotal` decimal(16,2) NOT NULL DEFAULT 0.00,
  `freight` decimal(16,2) NOT NULL DEFAULT 0.00,
  `total_value` decimal(16,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','sent','acknowledged','cancelled') NOT NULL DEFAULT 'draft',
  `acknowledged_date` date DEFAULT NULL,
  `acknowledged_by` bigint(20) UNSIGNED DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_lines`
--

CREATE TABLE `purchase_order_lines` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `purchase_order_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `line_number` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(12,4) NOT NULL,
  `line_total` decimal(16,2) NOT NULL,
  `unit_of_measure` varchar(20) NOT NULL DEFAULT 'unit',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `report_definitions`
--

CREATE TABLE `report_definitions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `module` enum('orders','shipments','inventory','finance','compliance','patients','anti_counterfeit','custom') NOT NULL DEFAULT 'custom',
  `report_type` varchar(255) NOT NULL,
  `default_filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`default_filters`)),
  `columns` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`columns`)),
  `sort` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sort`)),
  `chart_type` varchar(255) DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `report_runs`
--

CREATE TABLE `report_runs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `report_definition_id` bigint(20) UNSIGNED NOT NULL,
  `run_by` bigint(20) UNSIGNED DEFAULT NULL,
  `trigger` enum('manual','scheduled') NOT NULL DEFAULT 'manual',
  `applied_filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`applied_filters`)),
  `status` enum('queued','processing','completed','failed') NOT NULL DEFAULT 'queued',
  `output_format` varchar(10) DEFAULT NULL,
  `output_path` varchar(255) DEFAULT NULL,
  `disk` varchar(255) NOT NULL DEFAULT 'local',
  `output_size` bigint(20) UNSIGNED DEFAULT NULL,
  `row_count` int(10) UNSIGNED DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `report_schedules`
--

CREATE TABLE `report_schedules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `report_definition_id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `cron_expression` varchar(255) NOT NULL,
  `timezone` varchar(255) NOT NULL DEFAULT 'UTC',
  `output_format` varchar(10) NOT NULL DEFAULT 'pdf',
  `filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters`)),
  `email_recipients` tinyint(1) NOT NULL DEFAULT 0,
  `recipient_emails` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`recipient_emails`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_run_at` timestamp NULL DEFAULT NULL,
  `next_run_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `risk_alerts`
--

CREATE TABLE `risk_alerts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `alert_number` varchar(255) NOT NULL,
  `verification_log_id` bigint(20) UNSIGNED DEFAULT NULL,
  `batch_unit_id` bigint(20) UNSIGNED DEFAULT NULL,
  `batch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `uuc_code` varchar(255) DEFAULT NULL,
  `category` varchar(255) NOT NULL,
  `risk_level` varchar(255) NOT NULL,
  `risk_score` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `country` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'open',
  `is_case` tinyint(1) NOT NULL DEFAULT 0,
  `assigned_to` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `risk_alerts`
--

INSERT INTO `risk_alerts` (`id`, `alert_number`, `verification_log_id`, `batch_unit_id`, `batch_id`, `product_id`, `uuc_code`, `category`, `risk_level`, `risk_score`, `country`, `latitude`, `longitude`, `description`, `status`, `is_case`, `assigned_to`, `notes`, `resolved_at`, `created_at`, `updated_at`) VALUES
(1, 'ALT-20260618-000001', 6, 100, 1, 2, 'XGFDTPHGQ3', 'scan_limit_exceeded', 'high', 25, NULL, NULL, NULL, '6 scans exceed the limit of 5 for this All UUC codes (global).', 'open', 0, NULL, NULL, NULL, '2026-06-18 04:37:03', '2026-06-18 04:37:03'),
(2, 'ALT-20260618-000002', 7, 100, 1, 2, 'XGFDTPHGQ3', 'scan_limit_exceeded', 'high', 25, NULL, NULL, NULL, '7 scans exceed the limit of 5 for this All UUC codes (global).', 'open', 0, NULL, NULL, NULL, '2026-06-18 04:37:48', '2026-06-18 04:37:48'),
(3, 'ALT-20260618-000003', 14, 100, 1, 2, 'XGFDTPHGQ3', 'scan_limit_exceeded', 'high', 25, NULL, NULL, NULL, '8 scans exceed the limit of 5 for this All UUC codes (global).', 'open', 0, NULL, NULL, NULL, '2026-06-18 04:39:38', '2026-06-18 04:39:38'),
(4, 'ALT-20260618-000004', 15, 100, 1, 2, 'XGFDTPHGQ3', 'locked_scope', 'critical', 40, NULL, NULL, NULL, 'Verification is locked for this Specific code(s) by policy \'Auto lock — XGFDTPHGQ3\'.', 'open', 0, NULL, NULL, NULL, '2026-06-18 04:40:05', '2026-06-18 04:40:05'),
(5, 'ALT-20260618-000005', 16, 100, 1, 2, 'XGFDTPHGQ3', 'locked_scope', 'critical', 40, NULL, NULL, NULL, 'Verification is locked for this Specific code(s) by policy \'Auto lock — XGFDTPHGQ3\'.', 'open', 0, NULL, NULL, NULL, '2026-06-18 04:40:45', '2026-06-18 04:40:45');

-- --------------------------------------------------------

--
-- Table structure for table `sales_orders`
--

CREATE TABLE `sales_orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `so_number` varchar(255) NOT NULL,
  `purchase_order_id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `ship_to_country_id` bigint(20) UNSIGNED DEFAULT NULL,
  `so_date` date NOT NULL,
  `estimated_delivery_date` date DEFAULT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `payment_terms` varchar(255) DEFAULT NULL,
  `incoterms` varchar(10) DEFAULT NULL,
  `port_of_loading` varchar(255) DEFAULT NULL,
  `port_of_discharge` varchar(255) DEFAULT NULL,
  `subtotal` decimal(16,2) NOT NULL DEFAULT 0.00,
  `freight` decimal(16,2) NOT NULL DEFAULT 0.00,
  `total_value` decimal(16,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','confirmed','pi_issued','completed','cancelled') NOT NULL DEFAULT 'draft',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales_order_lines`
--

CREATE TABLE `sales_order_lines` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sales_order_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `batch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `line_number` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(12,4) NOT NULL,
  `line_total` decimal(16,2) NOT NULL,
  `unit_of_measure` varchar(20) NOT NULL DEFAULT 'unit',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES
(61, 'verify_style', 'style5', '2026-06-17 02:13:14', '2026-06-17 02:42:49'),
(62, 'brand_name', 'PharmaTrack', '2026-06-17 02:13:14', '2026-06-17 02:13:14'),
(63, 'primary', '#059669', '2026-06-17 02:13:14', '2026-06-17 02:13:14'),
(64, 'button_color', '#059669', '2026-06-17 02:13:14', '2026-06-17 02:13:14'),
(65, 'footer_color', '#94a3b8', '2026-06-17 02:13:14', '2026-06-17 02:13:14'),
(66, 'genuine_title', 'Verified Authentic', '2026-06-17 02:13:14', '2026-06-17 02:13:14'),
(67, 'genuine_subtitle', '', '2026-06-17 02:13:14', '2026-06-17 02:13:14'),
(68, 'footer', 'Protected by PharmaTrack Anti-Counterfeit', '2026-06-17 02:13:14', '2026-06-17 02:13:14'),
(69, 'report_form', '1', '2026-06-17 02:13:14', '2026-06-17 02:42:49'),
(70, 'info_form', '1', '2026-06-17 02:13:14', '2026-06-17 02:13:14'),
(71, 'verify_button', '0', '2026-06-17 02:13:14', '2026-06-17 02:13:14'),
(72, 'verify_button_text', 'Verify authenticity', '2026-06-17 02:13:14', '2026-06-17 02:13:14'),
(73, 'verify_button_pos', 'hero', '2026-06-17 02:13:14', '2026-06-17 02:13:14'),
(74, 'show_product_photo', '1', '2026-06-17 02:13:14', '2026-06-17 02:44:30'),
(75, 'verify_code_panel', '0', '2026-06-17 02:13:14', '2026-06-18 02:53:57'),
(76, 'show_journey', '0', '2026-06-17 02:13:14', '2026-06-17 02:44:10'),
(77, 'history_limit', '10', '2026-06-17 02:13:14', '2026-06-17 02:13:14'),
(78, 'show_country', '1', '2026-06-17 02:13:14', '2026-06-17 02:13:14'),
(79, 'show_city', '1', '2026-06-17 02:13:14', '2026-06-17 02:13:14'),
(80, 'show_ip', '1', '2026-06-17 02:13:14', '2026-06-17 02:31:00'),
(81, 'show_device', '1', '2026-06-17 02:13:14', '2026-06-17 02:13:14'),
(82, 'show_verification', '0', '2026-06-17 02:13:14', '2026-06-17 02:14:01'),
(83, 'show_leaflet', '1', '2026-06-17 02:13:14', '2026-06-17 02:14:01'),
(84, 'leaflet_label', 'Download insert / leaflet', '2026-06-17 02:13:14', '2026-06-17 02:13:14'),
(85, 'card_left_label', 'Batch', '2026-06-17 02:13:14', '2026-06-17 02:13:14'),
(86, 'card_left_field', 'batch', '2026-06-17 02:13:14', '2026-06-17 02:13:14'),
(87, 'card_right_label', 'Expires in', '2026-06-17 02:13:14', '2026-06-17 02:13:14'),
(88, 'card_right_field', 'expires_in', '2026-06-17 02:13:14', '2026-06-17 02:13:14'),
(89, 'fields', '[\"product\",\"generic\",\"strength\",\"batch\",\"manufactured\",\"expires\",\"serial\",\"country\"]', '2026-06-17 02:13:14', '2026-06-17 21:58:43'),
(90, 'messages', '{\"fake\":{\"title\":\"This is NOT a Genuine Product\",\"text\":\"This code doesn\'t match any product in our records. It may be counterfeit \\u2014 do not use it.\"},\"recalled\":{\"title\":\"Product Recalled\",\"text\":\"This product has been recalled. Do not use it and contact the manufacturer immediately.\"},\"locked\":{\"title\":\"Locked by Administrator\",\"text\":\"This product can only be shown by the administrator. No further information is available.\"},\"expired\":{\"title\":\"Product Expired\",\"text\":\"This product has passed its expiry date and should not be used.\"},\"invalid\":{\"title\":\"Not Valid for Sale\",\"text\":\"This unit is marked not valid for sale.\"},\"country\":{\"title\":\"May Be Counterfeit\",\"text\":\"This product is authorized for sale only in other countries. If you bought it in your country, it may be counterfeit \\u2014 please report it.\"},\"city\":{\"title\":\"Not Sold in Your City\",\"text\":\"This product is not authorized for sale in your city. If you bought it here, it may be counterfeit \\u2014 please report it.\"},\"device\":{\"title\":\"Device Limit Reached\",\"text\":\"This product has reached its allowed number of verification devices.\"},\"hit\":{\"title\":\"Verification Limit Reached\",\"text\":\"This code has reached its maximum number of verifications.\"}}', '2026-06-17 02:13:14', '2026-06-17 02:13:14'),
(92, 'scan_intelligence', '{\"multi_country\":{\"enabled\":true,\"threshold\":2,\"records\":3,\"report\":true,\"title\":\"Scanned from Multiple Countries\",\"text\":\"This product has been scanned from multiple countries. Please verify that you are purchasing from an authorized seller.\"},\"repeat_ip_block\":{\"enabled\":true,\"threshold\":5,\"records\":5,\"report\":true,\"title\":\"Multiple Scans From Your Device\",\"text\":\"This product has been scanned multiple times from your device. The system has automatically blocked further attempts. Please contact the administrator at info@beaconpharma.com.bd for assistance.\"},\"multi_ip_diff_country\":{\"enabled\":true,\"threshold\":3,\"records\":3,\"report\":false,\"title\":\"Scanned From Different Networks\",\"text\":\"This product has been scanned from different devices or networks across various regions. Please ensure authenticity before purchase.\"},\"multi_ip_same_country\":{\"enabled\":true,\"threshold\":3,\"records\":3,\"report\":false,\"title\":\"Scanned From Multiple Devices\",\"text\":\"This product has been scanned from multiple devices within the same country. Please confirm you are purchasing from an authorized seller.\"},\"same_ip_autolock\":{\"enabled\":true,\"threshold\":8,\"records\":5,\"report\":true,\"title\":\"Locked For Security\",\"text\":\"This product has been scanned repeatedly from your device and is now locked for security reasons (possible counterfeit activity detected).\"},\"recent_3day\":{\"enabled\":true,\"days\":5,\"records\":5,\"report\":true,\"title\":\"Already Scanned Recently\",\"text\":\"You have already scanned this product within the last few days. Why do you need to check again?\"},\"high_freq\":{\"enabled\":true,\"window_min\":10,\"max\":5,\"records\":0,\"report\":false,\"title\":\"Suspicious Activity Detected\",\"text\":\"Too many scans detected in a short period of time. Suspicious activity logged.\"},\"region_mismatch\":{\"enabled\":true,\"records\":3,\"report\":true,\"title\":\"Not Intended For Your Region\",\"text\":\"This product is not intended for use in your region. Please check with the official distributor.\"},\"returning_scan\":{\"enabled\":true,\"days\":10,\"records\":3,\"report\":true,\"title\":\"Welcome Back\",\"text\":\"You scanned this product some days ago. If you need a confirmation document, fill in your details to download a genuine-product certificate.\"}}', '2026-06-18 03:33:56', '2026-06-18 03:33:56'),
(93, 'loader_enabled', '1', '2026-06-18 04:37:46', '2026-06-18 04:37:46'),
(94, 'loader_style', 'spinner', '2026-06-18 04:37:46', '2026-06-18 04:38:15'),
(95, 'loader_text', 'Wait Information is loading...', '2026-06-18 04:37:46', '2026-06-18 04:43:23'),
(96, 'loader_min_ms', '5000', '2026-06-18 04:37:46', '2026-06-18 04:38:36');

-- --------------------------------------------------------

--
-- Table structure for table `shipments`
--

CREATE TABLE `shipments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shipment_number` varchar(255) NOT NULL,
  `commercial_invoice_id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `origin_country` varchar(5) DEFAULT NULL,
  `origin_port` varchar(255) DEFAULT NULL,
  `destination_country` varchar(5) DEFAULT NULL,
  `destination_port` varchar(255) DEFAULT NULL,
  `mode` enum('sea','air','road','rail','courier') NOT NULL DEFAULT 'sea',
  `carrier_name` varchar(255) DEFAULT NULL,
  `vessel_or_flight` varchar(255) DEFAULT NULL,
  `container_number` varchar(255) DEFAULT NULL,
  `bill_of_lading_number` varchar(255) DEFAULT NULL,
  `tracking_number` varchar(255) DEFAULT NULL,
  `booking_date` date DEFAULT NULL,
  `departure_date` date DEFAULT NULL,
  `estimated_arrival_date` date DEFAULT NULL,
  `actual_arrival_date` date DEFAULT NULL,
  `customs_cleared` tinyint(1) NOT NULL DEFAULT 0,
  `customs_cleared_date` date DEFAULT NULL,
  `customs_declaration_number` varchar(255) DEFAULT NULL,
  `total_packages` int(11) DEFAULT NULL,
  `gross_weight_kg` decimal(10,3) DEFAULT NULL,
  `volume_cbm` decimal(10,3) DEFAULT NULL,
  `status` enum('draft','booked','in_transit','customs_hold','delivered','delayed','cancelled') NOT NULL DEFAULT 'draft',
  `assigned_to` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipment_events`
--

CREATE TABLE `shipment_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shipment_id` bigint(20) UNSIGNED NOT NULL,
  `event_type` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `event_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `recorded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `is_milestone` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `therapeutic_classes`
--

CREATE TABLE `therapeutic_classes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `therapeutic_classes`
--

INSERT INTO `therapeutic_classes` (`id`, `name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(2, 'Nadine Mcneil', NULL, 1, '2026-06-10 22:52:32', '2026-06-10 22:52:32'),
(3, 'Antibiotics / Antimicrobials', NULL, 1, '2026-06-10 23:03:14', '2026-06-10 23:03:14'),
(4, 'Analgesics / Pain Relief', 'vvv', 1, '2026-06-10 23:03:14', '2026-06-15 04:08:30'),
(5, 'Anti-inflammatory / NSAIDs', NULL, 1, '2026-06-10 23:03:14', '2026-06-10 23:03:14'),
(6, 'Antidiabetics / Insulin', NULL, 1, '2026-06-10 23:03:14', '2026-06-10 23:03:14'),
(7, 'Cardiovascular', NULL, 1, '2026-06-10 23:03:14', '2026-06-10 23:03:14'),
(8, 'Antihypertensives', NULL, 1, '2026-06-10 23:03:14', '2026-06-10 23:03:14'),
(9, 'Antifungals', NULL, 1, '2026-06-10 23:03:14', '2026-06-10 23:03:14'),
(10, 'Antivirals', NULL, 1, '2026-06-10 23:03:14', '2026-06-10 23:03:14'),
(11, 'Antiparasitics', NULL, 1, '2026-06-10 23:03:14', '2026-06-10 23:03:14'),
(12, 'Respiratory / Bronchodilators', NULL, 1, '2026-06-10 23:03:14', '2026-06-10 23:03:14'),
(13, 'CNS / Neurological', NULL, 1, '2026-06-10 23:03:14', '2026-06-10 23:03:14'),
(14, 'Gastrointestinal', NULL, 1, '2026-06-10 23:03:14', '2026-06-10 23:03:14'),
(15, 'Endocrinology / Hormones', NULL, 1, '2026-06-10 23:03:14', '2026-06-10 23:03:14'),
(16, 'Vaccines / Immunologicals', NULL, 1, '2026-06-10 23:03:14', '2026-06-10 23:03:14'),
(17, 'Vitamins / Supplements', NULL, 1, '2026-06-10 23:03:14', '2026-06-10 23:03:14'),
(18, 'Dermatology', NULL, 1, '2026-06-10 23:03:14', '2026-06-10 23:03:14'),
(19, 'Ophthalmology', NULL, 1, '2026-06-10 23:03:14', '2026-06-10 23:03:14'),
(20, 'Oncology / Antineoplastics', NULL, 1, '2026-06-10 23:03:14', '2026-06-10 23:03:14'),
(21, 'Haematology', NULL, 1, '2026-06-10 23:03:14', '2026-06-10 23:03:14'),
(22, 'Musculoskeletal', NULL, 1, '2026-06-10 23:03:14', '2026-06-10 23:03:14'),
(23, 'Urological', NULL, 1, '2026-06-10 23:03:14', '2026-06-10 23:07:48'),
(24, 'Other', NULL, 1, '2026-06-10 23:03:14', '2026-06-10 23:03:14');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','manufacturer','distributor','finance','logistics','qc_officer') NOT NULL DEFAULT 'distributor',
  `initials` varchar(5) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `avatar_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vault_documents`
--

CREATE TABLE `vault_documents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `folder_id` bigint(20) UNSIGNED DEFAULT NULL,
  `distributor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `country_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `document_number` varchar(255) DEFAULT NULL,
  `document_type` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `disk` varchar(255) NOT NULL DEFAULT 'local',
  `file_type` varchar(50) DEFAULT NULL,
  `extension` varchar(10) DEFAULT NULL,
  `file_size` bigint(20) UNSIGNED DEFAULT NULL,
  `version` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `reminder_days_before` smallint(5) UNSIGNED NOT NULL DEFAULT 30,
  `expiry_alerted` tinyint(1) NOT NULL DEFAULT 0,
  `access_level` enum('public','internal','restricted','confidential') NOT NULL DEFAULT 'internal',
  `uploaded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vault_document_versions`
--

CREATE TABLE `vault_document_versions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `vault_document_id` bigint(20) UNSIGNED NOT NULL,
  `version` smallint(5) UNSIGNED NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `disk` varchar(255) NOT NULL DEFAULT 'local',
  `file_size` bigint(20) UNSIGNED DEFAULT NULL,
  `replaced_by` bigint(20) UNSIGNED DEFAULT NULL,
  `change_notes` text DEFAULT NULL,
  `replaced_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vault_folders`
--

CREATE TABLE `vault_folders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `access_level` enum('public','internal','restricted','confidential') NOT NULL DEFAULT 'internal',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `verification_daily_stats`
--

CREATE TABLE `verification_daily_stats` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `day` date NOT NULL,
  `total` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `genuine` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `suspicious` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `invalid` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `alerts` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `verification_logs`
--

CREATE TABLE `verification_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `verification_number` varchar(255) NOT NULL,
  `uuc_code` varchar(255) NOT NULL,
  `batch_unit_id` bigint(20) UNSIGNED DEFAULT NULL,
  `batch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `result` varchar(255) NOT NULL DEFAULT 'genuine',
  `risk_score` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `country_code` varchar(2) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `region` varchar(255) DEFAULT NULL,
  `isp` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `gps_accuracy` int(10) UNSIGNED DEFAULT NULL,
  `is_proxy` tinyint(1) NOT NULL DEFAULT 0,
  `browser` varchar(255) DEFAULT NULL,
  `os` varchar(255) DEFAULT NULL,
  `device_type` varchar(255) DEFAULT NULL,
  `language` varchar(255) DEFAULT NULL,
  `timezone` varchar(255) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `verification_logs`
--

INSERT INTO `verification_logs` (`id`, `verification_number`, `uuc_code`, `batch_unit_id`, `batch_id`, `product_id`, `result`, `risk_score`, `ip_address`, `country`, `country_code`, `city`, `region`, `isp`, `latitude`, `longitude`, `gps_accuracy`, `is_proxy`, `browser`, `os`, `device_type`, `language`, `timezone`, `user_agent`, `created_at`, `updated_at`) VALUES
(1, 'VER-20260618-000001', 'XGFDTPHGQ3', 100, 1, 2, 'genuine', 0, '127.0.0.1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'Chrome', 'Windows 10/11', 'desktop', 'en_US', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 03:28:47', '2026-06-18 03:28:47'),
(2, 'VER-20260618-000002', 'XGFDTPHGQ3', 100, 1, 2, 'genuine', 0, '127.0.0.1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'Chrome', 'Windows 10/11', 'desktop', 'en_US', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 03:59:36', '2026-06-18 03:59:36'),
(3, 'VER-20260618-000003', 'XGFDTPHGQ3', 100, 1, 2, 'genuine', 0, '127.0.0.1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'Chrome', 'Windows 10/11', 'desktop', 'en_US', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 04:17:51', '2026-06-18 04:17:51'),
(4, 'VER-20260618-000004', 'XGFDTPHGQ3', 100, 1, 2, 'genuine', 0, '127.0.0.1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'Chrome', 'Windows 10/11', 'desktop', 'en_US', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 04:36:55', '2026-06-18 04:36:55'),
(5, 'VER-20260618-000005', 'XGFDTPHGQ3', 100, 1, 2, 'blocked', 0, '127.0.0.1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'Chrome', 'Windows 10/11', 'desktop', 'en_US', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 04:36:58', '2026-06-18 04:36:58'),
(6, 'VER-20260618-000006', 'XGFDTPHGQ3', 100, 1, 2, 'blocked', 25, '127.0.0.1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'Chrome', 'Windows 10/11', 'desktop', 'en_US', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 04:37:03', '2026-06-18 04:37:03'),
(7, 'VER-20260618-000007', 'XGFDTPHGQ3', 100, 1, 2, 'blocked', 25, '127.0.0.1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'Chrome', 'Windows 10/11', 'desktop', 'en_US', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 04:37:48', '2026-06-18 04:37:48'),
(8, 'VER-20260618-000008', 'PUPWPD6U8F', 71, 1, 2, 'genuine', 0, '127.0.0.1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'Chrome', 'Windows 10/11', 'desktop', 'en_US', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 04:37:56', '2026-06-18 04:37:56'),
(9, 'VER-20260618-000009', 'PUPWPD6U8F', 71, 1, 2, 'genuine', 0, '127.0.0.1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'Chrome', 'Windows 10/11', 'desktop', 'en_US', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 04:37:59', '2026-06-18 04:37:59'),
(10, 'VER-20260618-000010', 'PUPWPD6U8F', 71, 1, 2, 'genuine', 0, '127.0.0.1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'Chrome', 'Windows 10/11', 'desktop', 'en_US', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 04:38:17', '2026-06-18 04:38:17'),
(11, 'VER-20260618-000011', 'PUPWPD6U8F', 71, 1, 2, 'genuine', 0, '127.0.0.1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'Chrome', 'Windows 10/11', 'desktop', 'en_US', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 04:38:18', '2026-06-18 04:38:18'),
(12, 'VER-20260618-000012', 'PUPWPD6U8F', 71, 1, 2, 'blocked', 0, '127.0.0.1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'Chrome', 'Windows 10/11', 'desktop', 'en_US', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 04:38:39', '2026-06-18 04:38:39'),
(13, 'VER-20260618-000013', 'UXB2GUHRCM', 73, 1, 2, 'genuine', 0, '127.0.0.1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'Chrome', 'Windows 10/11', 'desktop', 'en_US', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 04:38:46', '2026-06-18 04:38:46'),
(14, 'VER-20260618-000014', 'XGFDTPHGQ3', 100, 1, 2, 'blocked', 25, '127.0.0.1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'Unknown', 'Unknown', 'desktop', NULL, NULL, 'curl/8.19.0', '2026-06-18 04:39:38', '2026-06-18 04:39:38'),
(15, 'VER-20260618-000015', 'XGFDTPHGQ3', 100, 1, 2, 'locked', 40, '127.0.0.1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'Unknown', 'Unknown', 'desktop', NULL, NULL, 'curl/8.19.0', '2026-06-18 04:40:05', '2026-06-18 04:40:05'),
(16, 'VER-20260618-000016', 'XGFDTPHGQ3', 100, 1, 2, 'locked', 40, '127.0.0.1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'Chrome', 'Windows 10/11', 'desktop', 'en_US', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 04:40:45', '2026-06-18 04:40:45'),
(17, 'VER-20260618-000017', 'UXB2GUHRCM', 73, 1, 2, 'genuine', 0, '127.0.0.1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'Chrome', 'Windows 10/11', 'desktop', 'en_US', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 04:42:35', '2026-06-18 04:42:35'),
(18, 'VER-20260618-000018', 'UXB2GUHRCM', 73, 1, 2, 'genuine', 0, '127.0.0.1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'Chrome', 'Windows 10/11', 'desktop', 'en_US', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 04:43:07', '2026-06-18 04:43:07'),
(19, 'VER-20260618-000019', 'UXB2GUHRCM', 73, 1, 2, 'genuine', 0, '127.0.0.1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'Chrome', 'Windows 10/11', 'desktop', 'en_US', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 04:43:24', '2026-06-18 04:43:24');

-- --------------------------------------------------------

--
-- Table structure for table `verification_policies`
--

CREATE TABLE `verification_policies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `scope_type` varchar(255) NOT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `batch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `uuc_codes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`uuc_codes`)),
  `locked` tinyint(1) NOT NULL DEFAULT 0,
  `allow_all` tinyint(1) NOT NULL DEFAULT 0,
  `allowed_countries` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allowed_countries`)),
  `allowed_cities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allowed_cities`)),
  `ip_whitelist` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ip_whitelist`)),
  `ip_blacklist` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ip_blacklist`)),
  `block_vpn` tinyint(1) NOT NULL DEFAULT 0,
  `vpn_allowed_countries` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`vpn_allowed_countries`)),
  `scan_limit` int(10) UNSIGNED DEFAULT NULL,
  `device_limit` int(10) UNSIGNED DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `verification_policies`
--

INSERT INTO `verification_policies` (`id`, `name`, `scope_type`, `product_id`, `batch_id`, `uuc_codes`, `locked`, `allow_all`, `allowed_countries`, `allowed_cities`, `ip_whitelist`, `ip_blacklist`, `block_vpn`, `vpn_allowed_countries`, `scan_limit`, `device_limit`, `active`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Global verification policy (all UUC codes)', 'global', NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 0, NULL, 5, 3, 1, NULL, '2026-06-18 03:30:30', '2026-06-18 03:30:30'),
(3, 'Auto lock — XGFDTPHGQ3', 'codes', NULL, NULL, '[\"XGFDTPHGQ3\"]', 1, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 1, 'Auto-locked: 8 repeated scans from 127.0.0.1.', '2026-06-18 04:39:38', '2026-06-18 04:39:38');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `alert_rules`
--
ALTER TABLE `alert_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `alert_rules_created_by_foreign` (`created_by`),
  ADD KEY `alert_rules_event_type_index` (`event_type`),
  ADD KEY `alert_rules_is_active_index` (`is_active`);

--
-- Indexes for table `alert_rule_recipients`
--
ALTER TABLE `alert_rule_recipients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rule_user_channel_unique` (`alert_rule_id`,`user_id`,`channel`),
  ADD KEY `alert_rule_recipients_user_id_foreign` (`user_id`),
  ADD KEY `alert_rule_recipients_alert_rule_id_index` (`alert_rule_id`);

--
-- Indexes for table `anti_counterfeit_codes`
--
ALTER TABLE `anti_counterfeit_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `anti_counterfeit_codes_uuc_unique` (`uuc`),
  ADD KEY `anti_counterfeit_codes_product_id_index` (`product_id`),
  ADD KEY `anti_counterfeit_codes_batch_id_index` (`batch_id`);

--
-- Indexes for table `anti_counterfeit_scans`
--
ALTER TABLE `anti_counterfeit_scans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `anti_counterfeit_scans_scanned_by_foreign` (`scanned_by`),
  ADD KEY `anti_counterfeit_scans_distributor_id_foreign` (`distributor_id`),
  ADD KEY `anti_counterfeit_scans_code_id_index` (`code_id`),
  ADD KEY `anti_counterfeit_scans_result_index` (`result`),
  ADD KEY `anti_counterfeit_scans_scanned_at_index` (`scanned_at`),
  ADD KEY `anti_counterfeit_scans_country_code_scanned_at_index` (`country_code`,`scanned_at`);

--
-- Indexes for table `batches`
--
ALTER TABLE `batches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `batches_brn_unique` (`brn`),
  ADD KEY `batches_product_id_index` (`product_id`),
  ADD KEY `batches_qc_status_index` (`qc_status`),
  ADD KEY `batches_status_index` (`status`),
  ADD KEY `batches_expiry_date_index` (`expiry_date`);

--
-- Indexes for table `batch_extensions`
--
ALTER TABLE `batch_extensions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `batch_extensions_partial_ref_unique` (`partial_ref`),
  ADD KEY `batch_extensions_batch_id_index` (`batch_id`),
  ADD KEY `batch_extensions_product_id_index` (`product_id`);

--
-- Indexes for table `batch_units`
--
ALTER TABLE `batch_units`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `batch_units_unique_number_unique` (`unique_number`),
  ADD UNIQUE KEY `batch_units_batch_partial_serial_unique` (`batch_id`,`partial_batch_ref`,`serial_number`),
  ADD KEY `batch_units_secret_code_index` (`secret_code`),
  ADD KEY `batch_units_status_index` (`status`),
  ADD KEY `batch_units_partial_batch_ref_index` (`partial_batch_ref`);

--
-- Indexes for table `batch_unit_logs`
--
ALTER TABLE `batch_unit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `batch_unit_logs_batch_unit_id_foreign` (`batch_unit_id`),
  ADD KEY `batch_unit_logs_batch_id_event_index` (`batch_id`,`event`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `commercial_invoices`
--
ALTER TABLE `commercial_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `commercial_invoices_ci_number_unique` (`ci_number`),
  ADD UNIQUE KEY `commercial_invoices_proforma_invoice_id_unique` (`proforma_invoice_id`),
  ADD KEY `commercial_invoices_created_by_foreign` (`created_by`),
  ADD KEY `commercial_invoices_approved_by_foreign` (`approved_by`),
  ADD KEY `commercial_invoices_status_index` (`status`),
  ADD KEY `commercial_invoices_ci_date_index` (`ci_date`),
  ADD KEY `commercial_invoices_hs_code_index` (`hs_code`);

--
-- Indexes for table `commercial_invoice_lines`
--
ALTER TABLE `commercial_invoice_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `commercial_invoice_lines_product_id_foreign` (`product_id`),
  ADD KEY `commercial_invoice_lines_batch_id_foreign` (`batch_id`),
  ADD KEY `commercial_invoice_lines_commercial_invoice_id_index` (`commercial_invoice_id`);

--
-- Indexes for table `consignments`
--
ALTER TABLE `consignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `consignments_consignment_number_unique` (`consignment_number`),
  ADD UNIQUE KEY `consignments_qr_code_unique` (`qr_code`),
  ADD KEY `consignments_status_index` (`status`),
  ADD KEY `consignments_qr_code_index` (`qr_code`),
  ADD KEY `cons_status_id_idx` (`status`,`id`),
  ADD KEY `cons_created_id_idx` (`created_at`,`id`);

--
-- Indexes for table `consignment_scans`
--
ALTER TABLE `consignment_scans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `consignment_scans_consignment_id_index` (`consignment_id`);

--
-- Indexes for table `counterfeit_reports`
--
ALTER TABLE `counterfeit_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `counterfeit_reports_verification_log_id_foreign` (`verification_log_id`),
  ADD KEY `counterfeit_reports_product_id_foreign` (`product_id`),
  ADD KEY `counterfeit_reports_batch_id_foreign` (`batch_id`),
  ADD KEY `counterfeit_reports_uuc_code_index` (`uuc_code`),
  ADD KEY `counterfeit_reports_status_index` (`status`);

--
-- Indexes for table `countries`
--
ALTER TABLE `countries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `countries_code_unique` (`code`),
  ADD KEY `countries_region_index` (`region`),
  ADD KEY `countries_regulatory_status_index` (`regulatory_status`);

--
-- Indexes for table `country_authorizations`
--
ALTER TABLE `country_authorizations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `country_authorizations_product_id_country_code_unique` (`product_id`,`country_code`);

--
-- Indexes for table `dispensing_records`
--
ALTER TABLE `dispensing_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dispensing_records_prescription_line_id_foreign` (`prescription_line_id`),
  ADD KEY `dispensing_records_product_id_foreign` (`product_id`),
  ADD KEY `dispensing_records_dispensed_by_foreign` (`dispensed_by`),
  ADD KEY `dispensing_records_distributor_id_foreign` (`distributor_id`),
  ADD KEY `dispensing_records_patient_id_index` (`patient_id`),
  ADD KEY `dispensing_records_batch_id_index` (`batch_id`),
  ADD KEY `dispensing_records_dispensed_at_index` (`dispensed_at`);

--
-- Indexes for table `distributors`
--
ALTER TABLE `distributors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `distributors_license_number_unique` (`license_number`),
  ADD KEY `distributors_country_id_foreign` (`country_id`),
  ADD KEY `distributors_parent_id_index` (`parent_id`),
  ADD KEY `distributors_type_index` (`type`),
  ADD KEY `distributors_status_index` (`status`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `master_cartons`
--
ALTER TABLE `master_cartons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `master_cartons_carton_number_unique` (`carton_number`),
  ADD UNIQUE KEY `master_cartons_qr_code_unique` (`qr_code`),
  ADD KEY `master_cartons_batch_id_index` (`batch_id`),
  ADD KEY `master_cartons_product_id_index` (`product_id`),
  ADD KEY `master_cartons_status_index` (`status`),
  ADD KEY `master_cartons_qr_code_index` (`qr_code`),
  ADD KEY `master_cartons_consignment_id_index` (`consignment_id`),
  ADD KEY `master_cartons_carton_condition_index` (`carton_condition`),
  ADD KEY `mc_status_id_idx` (`status`,`id`),
  ADD KEY `mc_recon_idx` (`consignment_id`,`carton_condition`,`received_at`),
  ADD KEY `mc_created_id_idx` (`created_at`,`id`);

--
-- Indexes for table `master_carton_contents`
--
ALTER TABLE `master_carton_contents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `master_carton_contents_product_id_foreign` (`product_id`),
  ADD KEY `master_carton_contents_master_carton_id_index` (`master_carton_id`),
  ADD KEY `master_carton_contents_batch_id_index` (`batch_id`),
  ADD KEY `mcc_serial_idx` (`batch_id`,`serial_start`,`serial_end`);

--
-- Indexes for table `master_carton_scans`
--
ALTER TABLE `master_carton_scans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `master_carton_scans_master_carton_id_index` (`master_carton_id`),
  ADD KEY `master_carton_scans_event_index` (`event`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_alert_rule_id_foreign` (`alert_rule_id`),
  ADD KEY `notifications_user_id_index` (`user_id`),
  ADD KEY `notifications_user_id_is_read_index` (`user_id`,`is_read`),
  ADD KEY `notif_morph_idx` (`notifiable_type`,`notifiable_id`),
  ADD KEY `notifications_type_index` (`type`),
  ADD KEY `notifications_severity_index` (`severity`),
  ADD KEY `notifications_created_at_index` (`created_at`);

--
-- Indexes for table `order_documents`
--
ALTER TABLE `order_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_docs_morph_idx` (`documentable_type`,`documentable_id`),
  ADD KEY `order_documents_uploaded_by_index` (`uploaded_by`),
  ADD KEY `order_documents_category_index` (`category`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `patients_patient_ref_unique` (`patient_ref`),
  ADD KEY `patients_country_id_foreign` (`country_id`),
  ADD KEY `patients_registered_by_foreign` (`registered_by`),
  ADD KEY `patients_patient_ref_index` (`patient_ref`),
  ADD KEY `patients_last_name_first_name_index` (`last_name`,`first_name`),
  ADD KEY `patients_distributor_id_index` (`distributor_id`);

--
-- Indexes for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `prescriptions_prescription_ref_unique` (`prescription_ref`),
  ADD KEY `prescriptions_created_by_foreign` (`created_by`),
  ADD KEY `prescriptions_patient_id_index` (`patient_id`),
  ADD KEY `prescriptions_status_index` (`status`);

--
-- Indexes for table `prescription_lines`
--
ALTER TABLE `prescription_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prescription_lines_product_id_foreign` (`product_id`),
  ADD KEY `prescription_lines_prescription_id_index` (`prescription_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `products_prn_unique` (`prn`),
  ADD KEY `products_status_index` (`status`),
  ADD KEY `products_dosage_form_index` (`dosage_form`);
ALTER TABLE `products` ADD FULLTEXT KEY `products_name_generic_name_brand_name_fulltext` (`name`,`generic_name`,`brand_name`);

--
-- Indexes for table `product_country_registrations`
--
ALTER TABLE `product_country_registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_country_registrations_product_id_country_id_unique` (`product_id`,`country_id`),
  ADD KEY `product_country_registrations_country_id_foreign` (`country_id`),
  ADD KEY `product_country_registrations_expiry_date_index` (`expiry_date`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_images_product_id_is_primary_index` (`product_id`,`is_primary`),
  ADD KEY `product_images_product_id_sort_order_index` (`product_id`,`sort_order`);

--
-- Indexes for table `product_info_requests`
--
ALTER TABLE `product_info_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_info_requests_product_id_foreign` (`product_id`),
  ADD KEY `product_info_requests_batch_id_foreign` (`batch_id`),
  ADD KEY `product_info_requests_uuc_code_index` (`uuc_code`);

--
-- Indexes for table `product_recalls`
--
ALTER TABLE `product_recalls`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_recalls_recall_number_unique` (`recall_number`),
  ADD KEY `product_recalls_product_id_foreign` (`product_id`),
  ADD KEY `product_recalls_batch_id_active_index` (`batch_id`,`active`),
  ADD KEY `product_recalls_active_index` (`active`);

--
-- Indexes for table `proforma_invoices`
--
ALTER TABLE `proforma_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `proforma_invoices_pi_number_unique` (`pi_number`),
  ADD UNIQUE KEY `proforma_invoices_sales_order_id_unique` (`sales_order_id`),
  ADD KEY `proforma_invoices_created_by_foreign` (`created_by`),
  ADD KEY `proforma_invoices_approved_by_foreign` (`approved_by`),
  ADD KEY `proforma_invoices_status_index` (`status`),
  ADD KEY `proforma_invoices_pi_date_index` (`pi_date`),
  ADD KEY `proforma_invoices_valid_until_index` (`valid_until`);

--
-- Indexes for table `proforma_invoice_lines`
--
ALTER TABLE `proforma_invoice_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proforma_invoice_lines_product_id_foreign` (`product_id`),
  ADD KEY `proforma_invoice_lines_batch_id_foreign` (`batch_id`),
  ADD KEY `proforma_invoice_lines_proforma_invoice_id_index` (`proforma_invoice_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `purchase_orders_po_number_unique` (`po_number`),
  ADD KEY `purchase_orders_created_by_foreign` (`created_by`),
  ADD KEY `purchase_orders_acknowledged_by_foreign` (`acknowledged_by`),
  ADD KEY `purchase_orders_buyer_id_index` (`buyer_id`),
  ADD KEY `purchase_orders_status_index` (`status`),
  ADD KEY `purchase_orders_po_date_index` (`po_date`);

--
-- Indexes for table `purchase_order_lines`
--
ALTER TABLE `purchase_order_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_order_lines_product_id_foreign` (`product_id`),
  ADD KEY `purchase_order_lines_purchase_order_id_index` (`purchase_order_id`);

--
-- Indexes for table `report_definitions`
--
ALTER TABLE `report_definitions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `report_definitions_slug_unique` (`slug`),
  ADD KEY `report_definitions_module_is_active_index` (`module`,`is_active`),
  ADD KEY `report_definitions_created_by_index` (`created_by`);

--
-- Indexes for table `report_runs`
--
ALTER TABLE `report_runs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_runs_report_definition_id_index` (`report_definition_id`),
  ADD KEY `report_runs_status_index` (`status`),
  ADD KEY `report_runs_run_by_index` (`run_by`);

--
-- Indexes for table `report_schedules`
--
ALTER TABLE `report_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_schedules_created_by_foreign` (`created_by`),
  ADD KEY `report_schedules_report_definition_id_index` (`report_definition_id`),
  ADD KEY `report_schedules_is_active_next_run_at_index` (`is_active`,`next_run_at`);

--
-- Indexes for table `risk_alerts`
--
ALTER TABLE `risk_alerts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `risk_alerts_alert_number_unique` (`alert_number`),
  ADD KEY `risk_alerts_verification_log_id_foreign` (`verification_log_id`),
  ADD KEY `risk_alerts_batch_unit_id_foreign` (`batch_unit_id`),
  ADD KEY `risk_alerts_batch_id_foreign` (`batch_id`),
  ADD KEY `risk_alerts_product_id_foreign` (`product_id`),
  ADD KEY `risk_alerts_risk_level_status_index` (`risk_level`,`status`),
  ADD KEY `risk_alerts_category_created_at_index` (`category`,`created_at`),
  ADD KEY `risk_alerts_uuc_code_index` (`uuc_code`),
  ADD KEY `risk_alerts_risk_level_index` (`risk_level`),
  ADD KEY `risk_alerts_status_index` (`status`),
  ADD KEY `risk_alerts_is_case_index` (`is_case`);

--
-- Indexes for table `sales_orders`
--
ALTER TABLE `sales_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sales_orders_so_number_unique` (`so_number`),
  ADD UNIQUE KEY `sales_orders_purchase_order_id_unique` (`purchase_order_id`),
  ADD KEY `sales_orders_created_by_foreign` (`created_by`),
  ADD KEY `sales_orders_ship_to_country_id_foreign` (`ship_to_country_id`),
  ADD KEY `sales_orders_customer_id_index` (`customer_id`),
  ADD KEY `sales_orders_status_index` (`status`),
  ADD KEY `sales_orders_so_date_index` (`so_date`);

--
-- Indexes for table `sales_order_lines`
--
ALTER TABLE `sales_order_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sales_order_lines_product_id_foreign` (`product_id`),
  ADD KEY `sales_order_lines_batch_id_foreign` (`batch_id`),
  ADD KEY `sales_order_lines_sales_order_id_index` (`sales_order_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `settings_key_unique` (`key`);

--
-- Indexes for table `shipments`
--
ALTER TABLE `shipments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `shipments_shipment_number_unique` (`shipment_number`),
  ADD UNIQUE KEY `shipments_commercial_invoice_id_unique` (`commercial_invoice_id`),
  ADD KEY `shipments_created_by_foreign` (`created_by`),
  ADD KEY `shipments_status_index` (`status`),
  ADD KEY `shipments_departure_date_index` (`departure_date`),
  ADD KEY `shipments_estimated_arrival_date_index` (`estimated_arrival_date`);

--
-- Indexes for table `shipment_events`
--
ALTER TABLE `shipment_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shipment_events_recorded_by_foreign` (`recorded_by`),
  ADD KEY `shipment_events_shipment_id_index` (`shipment_id`),
  ADD KEY `shipment_events_event_at_index` (`event_at`);

--
-- Indexes for table `therapeutic_classes`
--
ALTER TABLE `therapeutic_classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `therapeutic_classes_name_unique` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_role_index` (`role`),
  ADD KEY `users_is_active_index` (`is_active`);

--
-- Indexes for table `vault_documents`
--
ALTER TABLE `vault_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vault_documents_country_id_foreign` (`country_id`),
  ADD KEY `vault_documents_uploaded_by_foreign` (`uploaded_by`),
  ADD KEY `vault_documents_folder_id_index` (`folder_id`),
  ADD KEY `vault_documents_document_type_index` (`document_type`),
  ADD KEY `vault_documents_expiry_date_index` (`expiry_date`),
  ADD KEY `vault_documents_distributor_id_document_type_index` (`distributor_id`,`document_type`),
  ADD KEY `vault_documents_product_id_country_id_index` (`product_id`,`country_id`);

--
-- Indexes for table `vault_document_versions`
--
ALTER TABLE `vault_document_versions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vault_document_versions_replaced_by_foreign` (`replaced_by`),
  ADD KEY `vault_document_versions_vault_document_id_index` (`vault_document_id`);

--
-- Indexes for table `vault_folders`
--
ALTER TABLE `vault_folders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vault_folders_slug_unique` (`slug`),
  ADD KEY `vault_folders_parent_id_foreign` (`parent_id`);

--
-- Indexes for table `verification_daily_stats`
--
ALTER TABLE `verification_daily_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `verification_daily_stats_day_unique` (`day`);

--
-- Indexes for table `verification_logs`
--
ALTER TABLE `verification_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `verification_logs_verification_number_unique` (`verification_number`),
  ADD KEY `verification_logs_batch_unit_id_foreign` (`batch_unit_id`),
  ADD KEY `verification_logs_product_id_foreign` (`product_id`),
  ADD KEY `verification_logs_batch_id_created_at_index` (`batch_id`,`created_at`),
  ADD KEY `verification_logs_country_code_created_at_index` (`country_code`,`created_at`),
  ADD KEY `verification_logs_uuc_code_index` (`uuc_code`),
  ADD KEY `verification_logs_result_index` (`result`),
  ADD KEY `verification_logs_country_index` (`country`);

--
-- Indexes for table `verification_policies`
--
ALTER TABLE `verification_policies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `verification_policies_scope_type_active_index` (`scope_type`,`active`),
  ADD KEY `verification_policies_batch_id_active_index` (`batch_id`,`active`),
  ADD KEY `verification_policies_product_id_active_index` (`product_id`,`active`),
  ADD KEY `verification_policies_active_index` (`active`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `alert_rules`
--
ALTER TABLE `alert_rules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `alert_rule_recipients`
--
ALTER TABLE `alert_rule_recipients`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `anti_counterfeit_codes`
--
ALTER TABLE `anti_counterfeit_codes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `anti_counterfeit_scans`
--
ALTER TABLE `anti_counterfeit_scans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `batches`
--
ALTER TABLE `batches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `batch_extensions`
--
ALTER TABLE `batch_extensions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `batch_units`
--
ALTER TABLE `batch_units`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `batch_unit_logs`
--
ALTER TABLE `batch_unit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `commercial_invoices`
--
ALTER TABLE `commercial_invoices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `commercial_invoice_lines`
--
ALTER TABLE `commercial_invoice_lines`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `consignments`
--
ALTER TABLE `consignments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `consignment_scans`
--
ALTER TABLE `consignment_scans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `counterfeit_reports`
--
ALTER TABLE `counterfeit_reports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `countries`
--
ALTER TABLE `countries`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `country_authorizations`
--
ALTER TABLE `country_authorizations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dispensing_records`
--
ALTER TABLE `dispensing_records`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `distributors`
--
ALTER TABLE `distributors`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `master_cartons`
--
ALTER TABLE `master_cartons`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `master_carton_contents`
--
ALTER TABLE `master_carton_contents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `master_carton_scans`
--
ALTER TABLE `master_carton_scans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_documents`
--
ALTER TABLE `order_documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prescription_lines`
--
ALTER TABLE `prescription_lines`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `product_country_registrations`
--
ALTER TABLE `product_country_registrations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `product_info_requests`
--
ALTER TABLE `product_info_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_recalls`
--
ALTER TABLE `product_recalls`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `proforma_invoices`
--
ALTER TABLE `proforma_invoices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `proforma_invoice_lines`
--
ALTER TABLE `proforma_invoice_lines`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_order_lines`
--
ALTER TABLE `purchase_order_lines`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `report_definitions`
--
ALTER TABLE `report_definitions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `report_runs`
--
ALTER TABLE `report_runs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `report_schedules`
--
ALTER TABLE `report_schedules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `risk_alerts`
--
ALTER TABLE `risk_alerts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `sales_orders`
--
ALTER TABLE `sales_orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales_order_lines`
--
ALTER TABLE `sales_order_lines`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- AUTO_INCREMENT for table `shipments`
--
ALTER TABLE `shipments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shipment_events`
--
ALTER TABLE `shipment_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `therapeutic_classes`
--
ALTER TABLE `therapeutic_classes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vault_documents`
--
ALTER TABLE `vault_documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vault_document_versions`
--
ALTER TABLE `vault_document_versions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vault_folders`
--
ALTER TABLE `vault_folders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `verification_daily_stats`
--
ALTER TABLE `verification_daily_stats`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `verification_logs`
--
ALTER TABLE `verification_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `verification_policies`
--
ALTER TABLE `verification_policies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `alert_rules`
--
ALTER TABLE `alert_rules`
  ADD CONSTRAINT `alert_rules_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `alert_rule_recipients`
--
ALTER TABLE `alert_rule_recipients`
  ADD CONSTRAINT `alert_rule_recipients_alert_rule_id_foreign` FOREIGN KEY (`alert_rule_id`) REFERENCES `alert_rules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `alert_rule_recipients_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `anti_counterfeit_codes`
--
ALTER TABLE `anti_counterfeit_codes`
  ADD CONSTRAINT `anti_counterfeit_codes_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `anti_counterfeit_codes_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `anti_counterfeit_scans`
--
ALTER TABLE `anti_counterfeit_scans`
  ADD CONSTRAINT `anti_counterfeit_scans_code_id_foreign` FOREIGN KEY (`code_id`) REFERENCES `anti_counterfeit_codes` (`id`),
  ADD CONSTRAINT `anti_counterfeit_scans_distributor_id_foreign` FOREIGN KEY (`distributor_id`) REFERENCES `distributors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `anti_counterfeit_scans_scanned_by_foreign` FOREIGN KEY (`scanned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `batches`
--
ALTER TABLE `batches`
  ADD CONSTRAINT `batches_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `batch_extensions`
--
ALTER TABLE `batch_extensions`
  ADD CONSTRAINT `batch_extensions_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `batch_extensions_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `batch_units`
--
ALTER TABLE `batch_units`
  ADD CONSTRAINT `batch_units_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `batch_unit_logs`
--
ALTER TABLE `batch_unit_logs`
  ADD CONSTRAINT `batch_unit_logs_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `batch_unit_logs_batch_unit_id_foreign` FOREIGN KEY (`batch_unit_id`) REFERENCES `batch_units` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `commercial_invoices`
--
ALTER TABLE `commercial_invoices`
  ADD CONSTRAINT `commercial_invoices_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `commercial_invoices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `commercial_invoices_proforma_invoice_id_foreign` FOREIGN KEY (`proforma_invoice_id`) REFERENCES `proforma_invoices` (`id`);

--
-- Constraints for table `commercial_invoice_lines`
--
ALTER TABLE `commercial_invoice_lines`
  ADD CONSTRAINT `commercial_invoice_lines_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `commercial_invoice_lines_commercial_invoice_id_foreign` FOREIGN KEY (`commercial_invoice_id`) REFERENCES `commercial_invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `commercial_invoice_lines_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `consignment_scans`
--
ALTER TABLE `consignment_scans`
  ADD CONSTRAINT `consignment_scans_consignment_id_foreign` FOREIGN KEY (`consignment_id`) REFERENCES `consignments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `counterfeit_reports`
--
ALTER TABLE `counterfeit_reports`
  ADD CONSTRAINT `counterfeit_reports_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `counterfeit_reports_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `counterfeit_reports_verification_log_id_foreign` FOREIGN KEY (`verification_log_id`) REFERENCES `verification_logs` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `country_authorizations`
--
ALTER TABLE `country_authorizations`
  ADD CONSTRAINT `country_authorizations_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dispensing_records`
--
ALTER TABLE `dispensing_records`
  ADD CONSTRAINT `dispensing_records_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `dispensing_records_dispensed_by_foreign` FOREIGN KEY (`dispensed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `dispensing_records_distributor_id_foreign` FOREIGN KEY (`distributor_id`) REFERENCES `distributors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `dispensing_records_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`),
  ADD CONSTRAINT `dispensing_records_prescription_line_id_foreign` FOREIGN KEY (`prescription_line_id`) REFERENCES `prescription_lines` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `dispensing_records_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `distributors`
--
ALTER TABLE `distributors`
  ADD CONSTRAINT `distributors_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`),
  ADD CONSTRAINT `distributors_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `distributors` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `master_cartons`
--
ALTER TABLE `master_cartons`
  ADD CONSTRAINT `master_cartons_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `master_cartons_consignment_id_foreign` FOREIGN KEY (`consignment_id`) REFERENCES `consignments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `master_cartons_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `master_carton_contents`
--
ALTER TABLE `master_carton_contents`
  ADD CONSTRAINT `master_carton_contents_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`),
  ADD CONSTRAINT `master_carton_contents_master_carton_id_foreign` FOREIGN KEY (`master_carton_id`) REFERENCES `master_cartons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `master_carton_contents_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `master_carton_scans`
--
ALTER TABLE `master_carton_scans`
  ADD CONSTRAINT `master_carton_scans_master_carton_id_foreign` FOREIGN KEY (`master_carton_id`) REFERENCES `master_cartons` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_alert_rule_id_foreign` FOREIGN KEY (`alert_rule_id`) REFERENCES `alert_rules` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_documents`
--
ALTER TABLE `order_documents`
  ADD CONSTRAINT `order_documents_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `patients_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `patients_distributor_id_foreign` FOREIGN KEY (`distributor_id`) REFERENCES `distributors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `patients_registered_by_foreign` FOREIGN KEY (`registered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD CONSTRAINT `prescriptions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `prescriptions_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`);

--
-- Constraints for table `prescription_lines`
--
ALTER TABLE `prescription_lines`
  ADD CONSTRAINT `prescription_lines_prescription_id_foreign` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prescription_lines_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `product_country_registrations`
--
ALTER TABLE `product_country_registrations`
  ADD CONSTRAINT `product_country_registrations_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_country_registrations_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_info_requests`
--
ALTER TABLE `product_info_requests`
  ADD CONSTRAINT `product_info_requests_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `product_info_requests_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_recalls`
--
ALTER TABLE `product_recalls`
  ADD CONSTRAINT `product_recalls_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `product_recalls_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `proforma_invoices`
--
ALTER TABLE `proforma_invoices`
  ADD CONSTRAINT `proforma_invoices_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `proforma_invoices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `proforma_invoices_sales_order_id_foreign` FOREIGN KEY (`sales_order_id`) REFERENCES `sales_orders` (`id`);

--
-- Constraints for table `proforma_invoice_lines`
--
ALTER TABLE `proforma_invoice_lines`
  ADD CONSTRAINT `proforma_invoice_lines_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `proforma_invoice_lines_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `proforma_invoice_lines_proforma_invoice_id_foreign` FOREIGN KEY (`proforma_invoice_id`) REFERENCES `proforma_invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_acknowledged_by_foreign` FOREIGN KEY (`acknowledged_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_orders_buyer_id_foreign` FOREIGN KEY (`buyer_id`) REFERENCES `distributors` (`id`),
  ADD CONSTRAINT `purchase_orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `purchase_order_lines`
--
ALTER TABLE `purchase_order_lines`
  ADD CONSTRAINT `purchase_order_lines_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `purchase_order_lines_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `report_definitions`
--
ALTER TABLE `report_definitions`
  ADD CONSTRAINT `report_definitions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `report_runs`
--
ALTER TABLE `report_runs`
  ADD CONSTRAINT `report_runs_report_definition_id_foreign` FOREIGN KEY (`report_definition_id`) REFERENCES `report_definitions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `report_runs_run_by_foreign` FOREIGN KEY (`run_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `report_schedules`
--
ALTER TABLE `report_schedules`
  ADD CONSTRAINT `report_schedules_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `report_schedules_report_definition_id_foreign` FOREIGN KEY (`report_definition_id`) REFERENCES `report_definitions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `risk_alerts`
--
ALTER TABLE `risk_alerts`
  ADD CONSTRAINT `risk_alerts_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `risk_alerts_batch_unit_id_foreign` FOREIGN KEY (`batch_unit_id`) REFERENCES `batch_units` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `risk_alerts_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `risk_alerts_verification_log_id_foreign` FOREIGN KEY (`verification_log_id`) REFERENCES `verification_logs` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sales_orders`
--
ALTER TABLE `sales_orders`
  ADD CONSTRAINT `sales_orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `sales_orders_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `distributors` (`id`),
  ADD CONSTRAINT `sales_orders_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`),
  ADD CONSTRAINT `sales_orders_ship_to_country_id_foreign` FOREIGN KEY (`ship_to_country_id`) REFERENCES `countries` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sales_order_lines`
--
ALTER TABLE `sales_order_lines`
  ADD CONSTRAINT `sales_order_lines_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sales_order_lines_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `sales_order_lines_sales_order_id_foreign` FOREIGN KEY (`sales_order_id`) REFERENCES `sales_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shipments`
--
ALTER TABLE `shipments`
  ADD CONSTRAINT `shipments_commercial_invoice_id_foreign` FOREIGN KEY (`commercial_invoice_id`) REFERENCES `commercial_invoices` (`id`),
  ADD CONSTRAINT `shipments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `shipment_events`
--
ALTER TABLE `shipment_events`
  ADD CONSTRAINT `shipment_events_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `shipment_events_shipment_id_foreign` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vault_documents`
--
ALTER TABLE `vault_documents`
  ADD CONSTRAINT `vault_documents_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `vault_documents_distributor_id_foreign` FOREIGN KEY (`distributor_id`) REFERENCES `distributors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `vault_documents_folder_id_foreign` FOREIGN KEY (`folder_id`) REFERENCES `vault_folders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `vault_documents_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `vault_documents_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `vault_document_versions`
--
ALTER TABLE `vault_document_versions`
  ADD CONSTRAINT `vault_document_versions_replaced_by_foreign` FOREIGN KEY (`replaced_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `vault_document_versions_vault_document_id_foreign` FOREIGN KEY (`vault_document_id`) REFERENCES `vault_documents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vault_folders`
--
ALTER TABLE `vault_folders`
  ADD CONSTRAINT `vault_folders_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `vault_folders` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `verification_logs`
--
ALTER TABLE `verification_logs`
  ADD CONSTRAINT `verification_logs_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `verification_logs_batch_unit_id_foreign` FOREIGN KEY (`batch_unit_id`) REFERENCES `batch_units` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `verification_logs_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `verification_policies`
--
ALTER TABLE `verification_policies`
  ADD CONSTRAINT `verification_policies_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `verification_policies_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
