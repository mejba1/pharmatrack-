-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 10, 2026 at 11:35 AM
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
-- Table structure for table `countries`
--

CREATE TABLE `countries` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(3) NOT NULL,
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
(18, '2026_01_01_000016_create_reports_table', 1);

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
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Indexes for table `countries`
--
ALTER TABLE `countries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `countries_code_unique` (`code`),
  ADD KEY `countries_region_index` (`region`),
  ADD KEY `countries_regulatory_status_index` (`regulatory_status`);

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `countries`
--
ALTER TABLE `countries`
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
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_country_registrations`
--
ALTER TABLE `product_country_registrations`
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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
