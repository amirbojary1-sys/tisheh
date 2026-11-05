-- TISHEH NETWORK 1 - CLEAN INSTALL INTO A NEW EMPTY DATABASE ONLY.
-- No users, private identity records, credentials, sessions or real orders.
-- Existing sites: backup first and use migrations; do not import over existing data.

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `subject_type` varchar(255) NOT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_id_foreign` (`user_id`),
  KEY `audit_logs_action_index` (`action`),
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cart_checkout_orders` (
  `cart_checkout_id` bigint(20) unsigned NOT NULL,
  `material_order_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`cart_checkout_id`,`material_order_id`),
  UNIQUE KEY `cart_checkout_orders_material_order_id_unique` (`material_order_id`),
  CONSTRAINT `cart_checkout_orders_cart_checkout_id_foreign` FOREIGN KEY (`cart_checkout_id`) REFERENCES `cart_checkouts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_checkout_orders_material_order_id_foreign` FOREIGN KEY (`material_order_id`) REFERENCES `material_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `cart_checkout_orders` WRITE;
/*!40000 ALTER TABLE `cart_checkout_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `cart_checkout_orders` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cart_checkouts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `idempotency_key` char(36) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cart_checkouts_user_id_idempotency_key_unique` (`user_id`,`idempotency_key`),
  CONSTRAINT `cart_checkouts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `cart_checkouts` WRITE;
/*!40000 ALTER TABLE `cart_checkouts` DISABLE KEYS */;
/*!40000 ALTER TABLE `cart_checkouts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `content_pages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `published` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `content_pages_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `content_pages` WRITE;
/*!40000 ALTER TABLE `content_pages` DISABLE KEYS */;
INSERT INTO `content_pages` VALUES
(1,'help','راهنمای استفاده از تیشه','چگونه درخواست نیروی کار ثبت کنم؟\nبا حساب کارفرما وارد شوید، تخصص و اطلاعات ضروری پروژه را تکمیل و برای بررسی ارسال کنید. پس از تأیید مدیر، آگهی در جستجو نمایش داده می‌شود.\n\nچگونه برای کار اعلام آمادگی کنم؟\nبا حساب کارگر وارد آگهی منتشرشده شوید و پیام معرفی خود را ثبت کنید. نتیجه در پروفایل و اعلان‌های شما قابل مشاهده است.\n\nچگونه مصالح سفارش بدهم؟\nدر صفحه محصول، مقدار و آدرس تحویل را وارد کنید. موجودی رزرو و سفارش برای فروشنده ثبت می‌شود. پیگیری از سفارش‌های من انجام می‌شود.\n\nآیا پرداخت آنلاین یا کیف پول فعال است؟\nخیر. سفارش‌های فعلی فقط مستقیم هستند. تیشه هیچ پرداخت بانکی یا تسویه‌ای را تأیید نمی‌کند. اطلاعات کارت بانکی خود را در سایت یا گفتگو ارسال نکنید.\n\nچگونه مشکل را پیگیری کنم؟\nاز بخش پشتیبانی، موضوع را ثبت و پاسخ مدیر را در گزارش‌های من پیگیری کنید.',1,'2026-11-05 18:19:01','2026-11-05 18:19:01'),
(2,'about','درباره تیشه','تیشه مسیر ارتباط کارفرما، نیروی متخصص و فروشنده مصالح ساختمانی است. هدف ما ساده‌کردن جستجو و شفافیت اطلاعات است. این نسخه در حال توسعه است؛ آگهی‌ها و قیمت‌های اولیه نمونه‌اند و برای معامله واقعی مناسب نیستند. بررسی انتشار آگهی به معنی احراز هویت یا تضمین کیفیت نیست.',1,'2026-11-05 18:19:01','2026-11-05 18:19:01');
/*!40000 ALTER TABLE `content_pages` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `conversations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `initiator_id` bigint(20) unsigned NOT NULL,
  `recipient_id` bigint(20) unsigned NOT NULL,
  `context_type` varchar(20) NOT NULL,
  `context_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `initiator_read_at` timestamp NULL DEFAULT NULL,
  `recipient_read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `conversations_initiator_id_context_type_context_id_unique` (`initiator_id`,`context_type`,`context_id`),
  KEY `conversations_recipient_id_foreign` (`recipient_id`),
  CONSTRAINT `conversations_initiator_id_foreign` FOREIGN KEY (`initiator_id`) REFERENCES `users` (`id`),
  CONSTRAINT `conversations_recipient_id_foreign` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `conversations` WRITE;
/*!40000 ALTER TABLE `conversations` DISABLE KEYS */;
/*!40000 ALTER TABLE `conversations` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `countries` (
  `code` char(2) NOT NULL,
  `name` varchar(255) NOT NULL,
  `currency` varchar(3) NOT NULL,
  `dial_code` varchar(6) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `countries` WRITE;
/*!40000 ALTER TABLE `countries` DISABLE KEYS */;
INSERT INTO `countries` VALUES
('IQ','عراق','IQD','+964',0),
('IR','ایران','IRR','+98',1);
/*!40000 ALTER TABLE `countries` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `demo_payment_attempts` (
  `id` char(36) NOT NULL,
  `material_order_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `provider_reference` varchar(255) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'demo_pending',
  `amount` bigint(20) unsigned NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'IRR',
  `is_demo` tinyint(1) NOT NULL DEFAULT 1,
  `expires_at` timestamp NOT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `demo_payment_attempts_provider_reference_unique` (`provider_reference`),
  KEY `demo_payment_attempts_material_order_id_foreign` (`material_order_id`),
  KEY `demo_payment_attempts_user_id_foreign` (`user_id`),
  KEY `demo_payment_attempts_status_index` (`status`),
  CONSTRAINT `demo_payment_attempts_material_order_id_foreign` FOREIGN KEY (`material_order_id`) REFERENCES `material_orders` (`id`),
  CONSTRAINT `demo_payment_attempts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `demo_payment_attempts` WRITE;
/*!40000 ALTER TABLE `demo_payment_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `demo_payment_attempts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_applications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `job_request_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `message` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_applications_job_request_id_user_id_unique` (`job_request_id`,`user_id`),
  KEY `job_applications_user_id_foreign` (`user_id`),
  CONSTRAINT `job_applications_job_request_id_foreign` FOREIGN KEY (`job_request_id`) REFERENCES `job_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `job_applications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `job_applications` WRITE;
/*!40000 ALTER TABLE `job_applications` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_applications` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_categories_name_unique` (`name`),
  UNIQUE KEY `job_categories_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `job_categories` WRITE;
/*!40000 ALTER TABLE `job_categories` DISABLE KEYS */;
INSERT INTO `job_categories` VALUES
(1,'بنایی و سفت‌کاری','masonry','2026-11-05 18:19:01','2026-11-05 18:19:01'),
(2,'برق‌کاری','electric','2026-11-05 18:19:01','2026-11-05 18:19:01'),
(3,'تأسیسات و لوله‌کشی','plumbing','2026-11-05 18:19:01','2026-11-05 18:19:01'),
(4,'جوشکاری','welding','2026-11-05 18:19:01','2026-11-05 18:19:01'),
(5,'نقاشی ساختمان','painting','2026-11-05 18:19:01','2026-11-05 18:19:01'),
(6,'کارگر ساده','general','2026-11-05 18:19:01','2026-11-05 18:19:01'),
(7,'کاشی و سرامیک','tiling','2026-11-05 18:19:01','2026-11-05 18:19:01'),
(8,'مهندسی عمران','civil-engineering','2026-11-05 18:19:01','2026-11-05 18:19:01'),
(9,'معماری و طراحی','architecture','2026-11-05 18:19:01','2026-11-05 18:19:01'),
(10,'نقشه‌برداری','surveying','2026-11-05 18:19:01','2026-11-05 18:19:01'),
(11,'تهویه و سرمایش','hvac','2026-11-05 18:19:01','2026-11-05 18:19:01'),
(12,'نجاری و کابینت','carpentry','2026-11-05 18:19:01','2026-11-05 18:19:01'),
(13,'آسانسور','elevator','2026-11-05 18:19:01','2026-11-05 18:19:01'),
(14,'سقف و شیروانی','roofing','2026-11-05 18:19:01','2026-11-05 18:19:01'),
(15,'عایق‌کاری','insulation','2026-11-05 18:19:01','2026-11-05 18:19:01'),
(16,'تخریب و بازسازی','demolition','2026-11-05 18:19:01','2026-11-05 18:19:01'),
(17,'مدیریت و نظارت پروژه','project-management','2026-11-05 18:19:01','2026-11-05 18:19:01'),
(18,'محوطه‌سازی','landscaping','2026-11-05 18:19:01','2026-11-05 18:19:01');
/*!40000 ALTER TABLE `job_categories` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_category_user` (
  `user_id` bigint(20) unsigned NOT NULL,
  `job_category_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`job_category_id`),
  KEY `job_category_user_job_category_id_foreign` (`job_category_id`),
  CONSTRAINT `job_category_user_job_category_id_foreign` FOREIGN KEY (`job_category_id`) REFERENCES `job_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `job_category_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `job_category_user` WRITE;
/*!40000 ALTER TABLE `job_category_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_category_user` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `job_category_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `skills` text DEFAULT NULL,
  `city` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `workers_count` smallint(5) unsigned NOT NULL,
  `start_date` date NOT NULL,
  `duration_days` smallint(5) unsigned NOT NULL,
  `wage` bigint(20) unsigned DEFAULT NULL,
  `currency` char(3) NOT NULL DEFAULT 'IRR',
  `payment_type` varchar(20) NOT NULL,
  `contact_method` varchar(20) NOT NULL DEFAULT 'mobile',
  `status` varchar(24) NOT NULL DEFAULT 'pending',
  `moderation_note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `job_requests_user_id_foreign` (`user_id`),
  KEY `job_requests_job_category_id_foreign` (`job_category_id`),
  KEY `job_requests_status_city_job_category_id_index` (`status`,`city`,`job_category_id`),
  KEY `job_requests_city_index` (`city`),
  KEY `job_requests_status_index` (`status`),
  CONSTRAINT `job_requests_job_category_id_foreign` FOREIGN KEY (`job_category_id`) REFERENCES `job_categories` (`id`),
  CONSTRAINT `job_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `job_requests` WRITE;
/*!40000 ALTER TABLE `job_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_requests` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `material_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `material_categories_name_unique` (`name`),
  UNIQUE KEY `material_categories_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `material_categories` WRITE;
/*!40000 ALTER TABLE `material_categories` DISABLE KEYS */;
INSERT INTO `material_categories` VALUES
(1,'سیمان و گچ','cement','2026-11-05 18:19:01','2026-11-05 18:19:01'),
(2,'آهن و فولاد','steel','2026-11-05 18:19:01','2026-11-05 18:19:01'),
(3,'آجر و بلوک','brick','2026-11-05 18:19:01','2026-11-05 18:19:01'),
(4,'کاشی و سرامیک','tile','2026-11-05 18:19:01','2026-11-05 18:19:01'),
(5,'شن و ماسه','sand','2026-11-05 18:19:01','2026-11-05 18:19:01'),
(6,'ابزار و تجهیزات','tools','2026-11-05 18:19:01','2026-11-05 18:19:01');
/*!40000 ALTER TABLE `material_categories` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `material_order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `material_order_id` bigint(20) unsigned NOT NULL,
  `material_product_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `brand` varchar(255) NOT NULL,
  `unit` varchar(255) NOT NULL,
  `unit_price` bigint(20) unsigned NOT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `line_total` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `material_order_items_material_order_id_foreign` (`material_order_id`),
  KEY `material_order_items_material_product_id_foreign` (`material_product_id`),
  CONSTRAINT `material_order_items_material_order_id_foreign` FOREIGN KEY (`material_order_id`) REFERENCES `material_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `material_order_items_material_product_id_foreign` FOREIGN KEY (`material_product_id`) REFERENCES `material_products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `material_order_items` WRITE;
/*!40000 ALTER TABLE `material_order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `material_order_items` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `material_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` char(36) NOT NULL,
  `idempotency_key` char(36) NOT NULL,
  `buyer_id` bigint(20) unsigned NOT NULL,
  `seller_id` bigint(20) unsigned NOT NULL,
  `status` varchar(24) NOT NULL DEFAULT 'awaiting_seller',
  `currency` char(3) NOT NULL DEFAULT 'IRR',
  `subtotal` bigint(20) unsigned NOT NULL,
  `shipping_cost` bigint(20) unsigned DEFAULT NULL,
  `total` bigint(20) unsigned DEFAULT NULL,
  `payment_method` varchar(16) NOT NULL DEFAULT 'direct',
  `payment_status` varchar(24) NOT NULL DEFAULT 'unverified_external',
  `delivery_address` text NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `reservation_expires_at` timestamp NULL DEFAULT NULL,
  `quote_acceptance_required` tinyint(1) NOT NULL DEFAULT 0,
  `quote_accepted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `material_orders_reference_unique` (`reference`),
  UNIQUE KEY `material_orders_buyer_id_idempotency_key_unique` (`buyer_id`,`idempotency_key`),
  KEY `material_orders_seller_id_foreign` (`seller_id`),
  KEY `material_orders_status_index` (`status`),
  KEY `material_orders_reservation_expires_at_index` (`reservation_expires_at`),
  CONSTRAINT `material_orders_buyer_id_foreign` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`),
  CONSTRAINT `material_orders_seller_id_foreign` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `material_orders` WRITE;
/*!40000 ALTER TABLE `material_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `material_orders` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `material_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `material_category_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `brand` varchar(255) NOT NULL,
  `specifications` text NOT NULL,
  `price` bigint(20) unsigned NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'IRR',
  `unit` varchar(24) NOT NULL,
  `stock` int(10) unsigned NOT NULL,
  `minimum_order` int(10) unsigned NOT NULL DEFAULT 1,
  `shipping_cost` bigint(20) unsigned DEFAULT NULL,
  `sale_terms` text DEFAULT NULL,
  `city` varchar(255) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `moderation_note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `material_products_user_id_foreign` (`user_id`),
  KEY `material_products_material_category_id_foreign` (`material_category_id`),
  KEY `material_products_status_material_category_id_price_index` (`status`,`material_category_id`,`price`),
  KEY `material_products_city_index` (`city`),
  KEY `material_products_status_index` (`status`),
  CONSTRAINT `material_products_material_category_id_foreign` FOREIGN KEY (`material_category_id`) REFERENCES `material_categories` (`id`),
  CONSTRAINT `material_products_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `material_products` WRITE;
/*!40000 ALTER TABLE `material_products` DISABLE KEYS */;
/*!40000 ALTER TABLE `material_products` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `body` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `messages_conversation_id_foreign` (`conversation_id`),
  KEY `messages_user_id_foreign` (`user_id`),
  CONSTRAINT `messages_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES
(1,'0001_01_01_000000_create_users_table',1),
(2,'0001_01_01_000001_create_cache_table',1),
(3,'0001_01_01_000002_create_jobs_table',1),
(4,'2026_10_24_000001_create_tisheh_foundation',1),
(5,'2026_10_24_000002_expand_administration_and_workflows',1),
(6,'2026_10_24_000003_scope_order_idempotency',1),
(7,'2026_10_24_000004_add_demo_otp_and_gateway',1),
(8,'2026_10_24_000005_finalize_web_operations',1),
(9,'2026_11_05_000001_create_persistent_shopping_carts',1),
(10,'2026_11_05_000002_add_network_profiles_and_roles',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) unsigned NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `material_order_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_events_material_order_id_foreign` (`material_order_id`),
  KEY `order_events_user_id_foreign` (`user_id`),
  CONSTRAINT `order_events_material_order_id_foreign` FOREIGN KEY (`material_order_id`) REFERENCES `material_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `order_events` WRITE;
/*!40000 ALTER TABLE `order_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_events` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `otp_challenges` (
  `id` char(36) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `code_hash` varchar(255) NOT NULL,
  `browser_token_hash` varchar(64) NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `is_demo` tinyint(1) NOT NULL DEFAULT 1,
  `expires_at` timestamp NOT NULL,
  `consumed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `otp_challenges_user_id_foreign` (`user_id`),
  CONSTRAINT `otp_challenges_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `otp_challenges` WRITE;
/*!40000 ALTER TABLE `otp_challenges` DISABLE KEYS */;
/*!40000 ALTER TABLE `otp_challenges` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permission_role` (
  `role_id` bigint(20) unsigned NOT NULL,
  `permission_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `permission_role_permission_id_foreign` (`permission_id`),
  CONSTRAINT `permission_role_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `permission_role_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `permission_role` WRITE;
/*!40000 ALTER TABLE `permission_role` DISABLE KEYS */;
INSERT INTO `permission_role` VALUES
(2,1),
(3,2),
(4,3),
(4,4);
/*!40000 ALTER TABLE `permission_role` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES
(3,'admin.access'),
(4,'identity.review'),
(1,'jobs.create'),
(2,'products.create');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `portfolio_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `title` varchar(120) NOT NULL,
  `description` text NOT NULL,
  `completed_year` smallint(5) unsigned DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `portfolio_entries_user_id_created_at_index` (`user_id`,`created_at`),
  CONSTRAINT `portfolio_entries_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `portfolio_entries` WRITE;
/*!40000 ALTER TABLE `portfolio_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `portfolio_entries` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_images` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `material_product_id` bigint(20) unsigned NOT NULL,
  `path` varchar(255) NOT NULL,
  `position` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_images_material_product_id_foreign` (`material_product_id`),
  CONSTRAINT `product_images_material_product_id_foreign` FOREIGN KEY (`material_product_id`) REFERENCES `material_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `product_images` WRITE;
/*!40000 ALTER TABLE `product_images` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_images` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `subject_type` varchar(20) NOT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `reason` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `admin_response` text DEFAULT NULL,
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reports_user_id_foreign` (`user_id`),
  KEY `reports_assigned_to_foreign` (`assigned_to`),
  KEY `reports_status_index` (`status`),
  CONSTRAINT `reports_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`),
  CONSTRAINT `reports_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `reports` WRITE;
/*!40000 ALTER TABLE `reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `reports` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_user` (
  `user_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `role_user_role_id_foreign` (`role_id`),
  CONSTRAINT `role_user_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `role_user` WRITE;
/*!40000 ALTER TABLE `role_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `role_user` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES
(1,'worker','متخصص و نیروی کار'),
(2,'employer','کارفرما و پیمانکار'),
(3,'seller','فروشنده مصالح'),
(4,'admin','مدیر سامانه');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_locations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `seller_locations_user_id_unique` (`user_id`),
  CONSTRAINT `seller_locations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `seller_locations` WRITE;
/*!40000 ALTER TABLE `seller_locations` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_locations` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `key` varchar(255) NOT NULL,
  `value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`value`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shopping_cart_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shopping_cart_id` bigint(20) unsigned NOT NULL,
  `material_product_id` bigint(20) unsigned NOT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shopping_cart_items_shopping_cart_id_material_product_id_unique` (`shopping_cart_id`,`material_product_id`),
  KEY `shopping_cart_items_material_product_id_foreign` (`material_product_id`),
  CONSTRAINT `shopping_cart_items_material_product_id_foreign` FOREIGN KEY (`material_product_id`) REFERENCES `material_products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shopping_cart_items_shopping_cart_id_foreign` FOREIGN KEY (`shopping_cart_id`) REFERENCES `shopping_carts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `shopping_cart_items` WRITE;
/*!40000 ALTER TABLE `shopping_cart_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `shopping_cart_items` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shopping_carts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shopping_carts_user_id_unique` (`user_id`),
  CONSTRAINT `shopping_carts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `shopping_carts` WRITE;
/*!40000 ALTER TABLE `shopping_carts` DISABLE KEYS */;
/*!40000 ALTER TABLE `shopping_carts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_profiles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `country_code` char(2) NOT NULL DEFAULT 'IR',
  `province` varchar(255) DEFAULT NULL,
  `city` varchar(255) NOT NULL,
  `specialty` varchar(255) DEFAULT NULL,
  `business_name` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `locale` varchar(8) NOT NULL DEFAULT 'fa',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `national_id` text DEFAULT NULL,
  `contact_phone` text DEFAULT NULL,
  `private_address` text DEFAULT NULL,
  `postal_code` text DEFAULT NULL,
  `national_id_fingerprint` varchar(64) DEFAULT NULL,
  `identity_status` varchar(24) NOT NULL DEFAULT 'not_submitted',
  `identity_revision` int(10) unsigned NOT NULL DEFAULT 0,
  `identity_submitted_at` timestamp NULL DEFAULT NULL,
  `identity_reviewed_at` timestamp NULL DEFAULT NULL,
  `identity_reviewer_id` bigint(20) unsigned DEFAULT NULL,
  `identity_review_note` text DEFAULT NULL,
  `headline` varchar(120) DEFAULT NULL,
  `activity_type` varchar(32) NOT NULL DEFAULT 'worker',
  `experience_years` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `availability` varchar(20) NOT NULL DEFAULT 'available',
  `directory_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `directory_status` varchar(24) NOT NULL DEFAULT 'draft',
  `directory_revision` int(10) unsigned NOT NULL DEFAULT 0,
  `directory_review_note` text DEFAULT NULL,
  `public_contact` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_profiles_user_id_unique` (`user_id`),
  UNIQUE KEY `user_profiles_national_id_fingerprint_unique` (`national_id_fingerprint`),
  KEY `user_profiles_country_code_foreign` (`country_code`),
  KEY `user_profiles_identity_reviewer_id_foreign` (`identity_reviewer_id`),
  KEY `directory_public_city` (`directory_status`,`directory_enabled`,`city`),
  KEY `user_profiles_identity_status_index` (`identity_status`),
  CONSTRAINT `user_profiles_country_code_foreign` FOREIGN KEY (`country_code`) REFERENCES `countries` (`code`),
  CONSTRAINT `user_profiles_identity_reviewer_id_foreign` FOREIGN KEY (`identity_reviewer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `user_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `user_profiles` WRITE;
/*!40000 ALTER TABLE `user_profiles` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_profiles` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `role_id` bigint(20) unsigned DEFAULT NULL,
  `last_name` varchar(255) NOT NULL DEFAULT '',
  `mobile` varchar(20) DEFAULT NULL,
  `mobile_verified_at` timestamp NULL DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_mobile_unique` (`mobile`),
  KEY `users_role_id_foreign` (`role_id`),
  KEY `users_status_index` (`status`),
  CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

