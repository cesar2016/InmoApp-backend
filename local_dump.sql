-- MySQL dump 10.13  Distrib 8.0.45, for Linux (x86_64)
--
-- Host: localhost    Database: inmoapp_sanchezInmob
-- ------------------------------------------------------
-- Server version	8.0.45-0ubuntu0.22.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contracts`
--

DROP TABLE IF EXISTS `contracts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contracts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `property_id` bigint unsigned NOT NULL,
  `tenant_id` bigint unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `rent_amount` decimal(15,2) NOT NULL,
  `increase_frequency_months` int NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_increase_date` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contracts_property_id_foreign` (`property_id`),
  KEY `contracts_tenant_id_foreign` (`tenant_id`),
  CONSTRAINT `contracts_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contracts_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contracts`
--

LOCK TABLES `contracts` WRITE;
/*!40000 ALTER TABLE `contracts` DISABLE KEYS */;
INSERT INTO `contracts` VALUES (1,1,1,'2026-04-02','2027-04-02',256000.00,6,NULL,NULL,0,'2026-04-03 05:17:17','2026-04-03 14:43:50'),(2,1,1,'2026-04-02','2027-04-02',256000.00,6,NULL,NULL,1,'2026-04-03 14:43:50','2026-04-03 14:43:50'),(3,41,5,'2026-04-01','2027-03-31',240000.00,4,'contracts/1775502412_NUEVO CONTRATO MODELO 2026.doc',NULL,1,'2026-04-06 22:06:57','2026-04-06 22:06:57');
/*!40000 ALTER TABLE `contracts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `guarantors`
--

DROP TABLE IF EXISTS `guarantors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `guarantors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dni` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `whatsapp` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `tenant_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `guarantors_dni_unique` (`dni`),
  UNIQUE KEY `guarantors_email_unique` (`email`),
  KEY `guarantors_tenant_id_foreign` (`tenant_id`),
  CONSTRAINT `guarantors_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `guarantors`
--

LOCK TABLES `guarantors` WRITE;
/*!40000 ALTER TABLE `guarantors` DISABLE KEYS */;
INSERT INTO `guarantors` VALUES (1,'Carlos','Gomez','G62407108','Calle Garante 1','+5491167299928','carlos@gmail.com','2026-04-03 14:10:15','2026-04-03 14:43:50',NULL),(2,'Maria','Rodriguez','G76498624','Calle Garante 2','+5491197550210','maria@gmail.com','2026-04-03 14:10:15','2026-04-03 15:19:58',2),(3,'Juan','Perez','G95161116','Calle Garante 3','+5491143143506','juan@gmail.com','2026-04-03 14:10:15','2026-04-03 15:19:58',2),(4,'Ana','Garcia','G72380380','Calle Garante 4','+5491129504072','ana@gmail.com','2026-04-03 14:10:15','2026-04-03 14:43:50',NULL),(5,'Luis','Martinez','G53255059','Calle Garante 5','+5491189692592','luis@gmail.com','2026-04-03 14:10:15','2026-04-03 14:43:50',NULL),(6,'Elena','Lopez','G21519032','Calle Garante 6','+5491155040969','elena@gmail.com','2026-04-03 14:10:15','2026-04-03 14:43:50',NULL),(7,'Diego','Sanchez','G48443607','Calle Garante 7','+5491120186229','diego@gmail.com','2026-04-03 14:10:15','2026-04-03 14:43:50',NULL),(8,'Sofia','Gonzalez','G72230147','Calle Garante 8','+5491124278051','sofia@gmail.com','2026-04-03 14:10:15','2026-04-03 14:43:50',NULL),(9,'Pedro','Fernandez','G93741984','Calle Garante 9','+5491152874683','pedro@gmail.com','2026-04-03 14:10:15','2026-04-03 14:43:50',1),(10,'Laura','Diaz','G85616091','Calle Garante 10','+5491118554063','laura@gmail.com','2026-04-03 14:10:15','2026-04-03 14:43:50',1);
/*!40000 ALTER TABLE `guarantors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenances`
--

DROP TABLE IF EXISTS `maintenances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `maintenances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `property_id` bigint unsigned NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cost` decimal(15,2) NOT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `maintenances_property_id_foreign` (`property_id`),
  CONSTRAINT `maintenances_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenances`
--

LOCK TABLES `maintenances` WRITE;
/*!40000 ALTER TABLE `maintenances` DISABLE KEYS */;
/*!40000 ALTER TABLE `maintenances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_04_03_004701_create_owners_table',1),(5,'2026_04_03_004702_create_tenants_table',1),(6,'2026_04_03_004703_create_properties_table',1),(7,'2026_04_03_004704_create_contracts_table',1),(8,'2026_04_03_004705_create_guarantors_table',1),(9,'2026_04_03_004706_create_payments_table',1),(10,'2026_04_03_004707_create_maintenances_table',1),(11,'2026_04_03_010652_create_personal_access_tokens_table',2),(14,'2026_04_03_022246_move_guarantors_to_tenants',3),(15,'2026_04_03_215339_add_detailed_fields_to_payments_table',4),(16,'2026_04_04_181411_add_listing_type_to_properties_table',5),(17,'2026_04_06_013043_add_file_path_to_contracts_table',6);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `owners`
--

DROP TABLE IF EXISTS `owners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `owners` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dni` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `whatsapp` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `owners_dni_unique` (`dni`),
  UNIQUE KEY `owners_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `owners`
--

LOCK TABLES `owners` WRITE;
/*!40000 ALTER TABLE `owners` DISABLE KEYS */;
INSERT INTO `owners` VALUES (1,'Propietario','testing','298504545','Pringles 720','3408678989','propietario@test.com','2026-04-03 04:52:26','2026-04-03 04:52:26'),(2,'Benjamín','Rentería','65162131','Agustín  9042 Depto. 888\nEsparza del Norte, AR-J 99539','(989)15521-2713','iocampo@example.org','2026-04-03 05:04:12','2026-04-03 05:04:12'),(3,'Ariana','Velázquez','66510159','Pablo  64111 Piso 89\nArguello del Norte, AR-S 0907','205-447-4094','ysevilla@example.org','2026-04-03 05:04:12','2026-04-03 05:04:12'),(4,'Miguel','Polanco','15296866','Damián  38 2 C\nValentino del Este, AR-R 15972','(810)485-6684','eduardo38@example.net','2026-04-03 05:04:12','2026-04-03 05:04:12'),(5,'Damián','Vallejo','77964157','Antonella  1028\nMiguel Ángel del Mirador, AR-B 05641','+64(1)9356158333','dante.granados@example.org','2026-04-03 05:04:12','2026-04-03 05:04:12'),(6,'Adrián','Villegas','94520439','Macías  7 39 C\nVilla Lucía del Sur, AR-B 0929','(838)15555-1863','juanjose.campos@example.org','2026-04-03 05:04:13','2026-04-03 05:04:13'),(7,'Josué','Cardenas','10549066','Micaela  16894\nVilla Dylan, AR-F 7012','+81(6)7121359920','agustin83@example.com','2026-04-03 05:04:13','2026-04-03 05:04:13'),(8,'Ximena','Armijo','81839532','Joaquín  56 16 D\nGuillen del Este, AR-L 4284','(28)155500-6778','miguel.padron@example.org','2026-04-03 05:04:13','2026-04-03 05:04:13'),(9,'Nicole','Barrientos','40208400','Lucas  54496\nDelfina del Oeste, AR-S 5377','(321)487-2427','miguelangel.garrido@example.org','2026-04-03 05:04:13','2026-04-03 05:04:13'),(10,'Isabel','Bernal','36401810','Julia  218\nYbarra del Oeste, AR-N 8503','(04)155076-9855','teran.dante@example.net','2026-04-03 05:04:13','2026-04-03 05:04:13'),(11,'Josefa','Amaya','89570605','Menéndez  356\nGral. Ashley del Mar, AR-P 57884','(056)584-5476','vlozada@example.com','2026-04-03 05:04:13','2026-04-03 05:04:13'),(12,'Renata','Villa','63525757','Nadia  6445\nSan Carla del Oeste, AR-F 09994','(580)576-2333','otero.benjamin@example.com','2026-04-03 05:04:13','2026-04-03 05:04:13'),(13,'Lautaro','Aparicio','34614092','Carrillo  26032\nAdorno del Mirador, AR-Q 1967','(635)571-0299','daniel74@example.com','2026-04-03 05:28:27','2026-04-03 05:28:27'),(14,'Salomé','Quintanilla','23610594','Aponte  85549\nGral. Agustín, AR-A 1174','(65)4523-5697','nazario.ariadna@example.net','2026-04-03 05:28:27','2026-04-03 05:28:27'),(15,'Juan Manuel','Salcedo','88260011','Simón  906\nGral. Carla, AR-Q 63877','(372)15530-4258','jmatias@example.com','2026-04-03 05:28:27','2026-04-03 05:28:27'),(16,'Leonardo','Montalvo','88897107','Arguello  6\nGálvez del Oeste, AR-P 0710','110-279-8618','mariapaula96@example.net','2026-04-03 05:28:27','2026-04-03 05:28:27'),(17,'Ariana','Montes','22219702','Puga  5175 Piso 34\nAndrea del Mar, AR-A 65234','(8904)49-8854','aitana26@example.net','2026-04-03 05:28:27','2026-04-03 05:28:27'),(18,'Fátima','Magaña','73790978','Valles  8 8 6\nPuerto Daniela del Norte, AR-Z 5839','(30)6644-1476','hponce@example.com','2026-04-03 05:28:27','2026-04-03 05:28:27'),(19,'Julieta','Sánchez','36778401','Nazario  6 85 0\nFernando del Sur, AR-K 2788','(33)154193-3993','elias.adame@example.com','2026-04-03 05:28:27','2026-04-03 05:28:27'),(20,'Luciano','Benavides','57733768','Eduardo  73408\nGral. Josefina, AR-X 61836','(419)15538-4894','xulibarri@example.net','2026-04-03 05:28:27','2026-04-03 05:28:27'),(21,'Sara Sofía','Valle','55432537','Feliciano  13394\nSan Juan Esteban del Este, AR-M 21568','(4637)1541-4215','snaranjo@example.org','2026-04-03 05:28:27','2026-04-03 05:28:27'),(22,'Josefa','Girón','92944584','Rebeca  191\nVillalpando del Oeste, AR-M 56543','(58)155167-0548','zbarraza@example.org','2026-04-03 05:28:27','2026-04-03 05:28:27'),(23,'Carla','Negrete','20602104','Segovia  516 PB A\nCastro del Norte, AR-B 8704','(4329)49-1484','hugo.serrano@example.org','2026-04-03 05:28:27','2026-04-03 05:28:27'),(26,'HILDA CARINA','GRAMAGLIA','22308657','calle SAN GERONIMO S/N','3408676225','scinmueble@gmail.com','2026-04-06 22:06:57','2026-04-06 22:06:57');
/*!40000 ALTER TABLE `owners` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `contract_id` bigint unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `detail` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Alquiler mensual',
  `subtotal` decimal(15,2) DEFAULT NULL,
  `credit_balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `debit_balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total` decimal(15,2) DEFAULT NULL,
  `payment_date` date NOT NULL,
  `period_month` int NOT NULL,
  `period_year` int NOT NULL,
  `receipt_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payments_contract_id_foreign` (`contract_id`),
  CONSTRAINT `payments_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,3,240000.00,'Alquiler Mensual',240000.00,0.00,0.00,267298.00,'2026-04-06',4,2026,'2026045689','Nota de testing\n\nConceptos Adicionales:\n- Impuesto Inmobiliario: $8200\n- Tasa Municipal: $3499\n- Mantenimientos: $15599','2026-04-07 01:39:13','2026-04-07 01:39:13');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
INSERT INTO `personal_access_tokens` VALUES (1,'App\\Models\\User',1,'auth_token','72d84cb8d19d465a143b69a5d0fb29d86778d1b06b8a94e1734f1d78e3ad75d4','[\"*\"]','2026-04-03 04:42:06',NULL,'2026-04-03 04:41:03','2026-04-03 04:42:06'),(2,'App\\Models\\User',1,'auth_token','c48fc7f215f1a5f0d7487d6fa761103bcc4b8b260fdeb4ea15c8c1bc7c2bea30','[\"*\"]','2026-04-03 13:55:22',NULL,'2026-04-03 04:49:32','2026-04-03 13:55:22'),(3,'App\\Models\\User',1,'auth_token','ab2a74322c44385fce165b5edb39c05294189f5fa71afdef8156157fceada7fe','[\"*\"]','2026-04-03 19:56:36',NULL,'2026-04-03 13:55:42','2026-04-03 19:56:36'),(4,'App\\Models\\User',1,'auth_token','274d68de5827ada93c54222b87d127f6be9230a991fcd29b2a60c366796b8cd5','[\"*\"]','2026-04-03 23:05:27',NULL,'2026-04-03 23:04:56','2026-04-03 23:05:27'),(5,'App\\Models\\User',1,'auth_token','151e40f2bc1702da06b8ad824ed731fd9715baaced534b2cd2f9c1714ccd4cc6','[\"*\"]','2026-04-04 03:00:28',NULL,'2026-04-04 00:32:47','2026-04-04 03:00:28'),(6,'App\\Models\\User',1,'auth_token','4d9198041c9c8f0d819eb22bf85956c7f0772bd7cac7a854bfc6fb07f6a40e47','[\"*\"]','2026-04-06 14:51:36',NULL,'2026-04-04 05:28:54','2026-04-06 14:51:36'),(7,'App\\Models\\User',1,'auth_token','8cef621a32bcde124425a76376efe19596e34738ee9833c79b4ae028cbc9d4c6','[\"*\"]','2026-04-05 01:49:39',NULL,'2026-04-04 22:59:22','2026-04-05 01:49:39'),(8,'App\\Models\\User',1,'auth_token','84a1e878058bb8c2e1adca70314d50dffe7240823986c707d530980acf481fbf','[\"*\"]','2026-04-05 01:46:54',NULL,'2026-04-05 01:46:53','2026-04-05 01:46:54'),(9,'App\\Models\\User',1,'auth_token','8be1e83d09957f924d3b3a3c2eb715d4438fa074b2c517929c696f0e356ce48f','[\"*\"]','2026-04-07 02:16:25',NULL,'2026-04-06 15:40:00','2026-04-07 02:16:25'),(10,'App\\Models\\User',1,'auth_token','56a192eef144aa1b6e4b1907797544b161e8f32d1bb726cd8d48cc0b0e2fefa4','[\"*\"]','2026-04-07 16:48:31',NULL,'2026-04-07 03:44:04','2026-04-07 16:48:31'),(11,'App\\Models\\User',1,'auth_token','59a4a47be202ac64509c15c099d9b1a14adc49c330877ebe4b8882c800f354c9','[\"*\"]','2026-04-07 17:13:20',NULL,'2026-04-07 17:01:38','2026-04-07 17:13:20'),(12,'App\\Models\\User',1,'auth_token','22813d11101dd55b62d5aee4beaa2fa15a0b997c8cb2efd5b95332be84fe4776','[\"*\"]','2026-04-07 22:28:37',NULL,'2026-04-07 19:10:45','2026-04-07 22:28:37'),(13,'App\\Models\\User',1,'auth_token','445aa21d1e18f2a8caccb7620f165c804440c7e28c8e132827628a9da44d478a','[\"*\"]','2026-04-10 17:22:01',NULL,'2026-04-10 15:52:06','2026-04-10 17:22:01');
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `properties`
--

DROP TABLE IF EXISTS `properties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `properties` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('Casa','Dpto','Local','Otro') COLLATE utf8mb4_unicode_ci NOT NULL,
  `listing_type` enum('Alquiler','Venta','Ambos') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Alquiler',
  `real_estate_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `domain` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `street` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `floor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dept` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `properties_owner_id_foreign` (`owner_id`),
  CONSTRAINT `properties_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `owners` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `properties`
--

LOCK TABLES `properties` WRITE;
/*!40000 ALTER TABLE `properties` DISABLE KEYS */;
INSERT INTO `properties` VALUES (1,'Dpto','Alquiler','PART-615727','DOM-417782','Fonseca ','41350','5','o','Rosario',2,'2026-04-03 05:04:12','2026-04-03 05:04:12'),(2,'Casa','Venta','PART-549037','DOM-861820','Salas','9',NULL,NULL,'Rosario',3,'2026-04-03 05:04:12','2026-04-04 21:20:14'),(3,'Dpto','Alquiler','PART-480204','DOM-297092','Farías ','1563','8','i','Rosario',4,'2026-04-03 05:04:12','2026-04-03 05:04:12'),(4,'Casa','Ambos','PART-776318','DOM-164257','Juana','8243',NULL,NULL,'Rosario',5,'2026-04-03 05:04:12','2026-04-04 21:20:26'),(5,'Casa','Alquiler','PART-797426','DOM-965827','Isabella ','30',NULL,NULL,'Rosario',6,'2026-04-03 05:04:13','2026-04-03 05:04:13'),(6,'Dpto','Alquiler','PART-462765','DOM-203955','Rafaela ','629','6','l','Rosario',7,'2026-04-03 05:04:13','2026-04-03 05:04:13'),(7,'Dpto','Alquiler','PART-204222','DOM-978944','Silvana ','4','4','s','Rosario',8,'2026-04-03 05:04:13','2026-04-03 05:04:13'),(8,'Dpto','Alquiler','PART-850298','DOM-116170','Silvana ','4','4','d','Rosario',9,'2026-04-03 05:04:13','2026-04-03 05:04:13'),(9,'Casa','Alquiler','PART-384152','DOM-150311','Castillo ','610',NULL,NULL,'Rosario',10,'2026-04-03 05:04:13','2026-04-03 05:04:13'),(10,'Dpto','Alquiler','PART-4873','DOM-247255','Gaytán ','56275','2','w','Rosario',10,'2026-04-03 05:04:13','2026-04-03 05:04:13'),(11,'Local','Alquiler','PART-74489','DOM-469861','Lozada ','472',NULL,NULL,'Rosario',10,'2026-04-03 05:04:13','2026-04-03 05:04:13'),(12,'Casa','Alquiler','PART-392321','DOM-802112','Gabriela ','805',NULL,NULL,'Rosario',11,'2026-04-03 05:04:13','2026-04-03 05:04:13'),(13,'Dpto','Alquiler','PART-313487','DOM-467884','Florencia ','789','9','u','Rosario',11,'2026-04-03 05:04:13','2026-04-03 05:04:13'),(14,'Local','Alquiler','PART-979151','DOM-296807','Andrés ','293',NULL,NULL,'Rosario',11,'2026-04-03 05:04:13','2026-04-03 05:04:13'),(15,'Casa','Alquiler','PART-734412','DOM-237901','Benavides ','7',NULL,NULL,'Rosario',11,'2026-04-03 05:04:13','2026-04-03 05:04:13'),(16,'Casa','Alquiler','PART-418593','DOM-918498','Dueñas ','4255',NULL,NULL,'Rosario',12,'2026-04-03 05:04:13','2026-04-03 05:04:13'),(17,'Dpto','Alquiler','PART-365533','DOM-696546','Mariana ','8341','7','b','Rosario',12,'2026-04-03 05:04:13','2026-04-03 05:04:13'),(18,'Local','Alquiler','PART-806172','DOM-136423','Raya ','4776',NULL,NULL,'Rosario',12,'2026-04-03 05:04:13','2026-04-03 05:04:13'),(19,'Casa','Alquiler','PART-692552','DOM-882668','Carolina ','45',NULL,NULL,'Rosario',12,'2026-04-03 05:04:13','2026-04-03 05:04:13'),(20,'Dpto','Alquiler','PART-133820','DOM-605682','Josué ','1234','6','t','Rosario',12,'2026-04-03 05:04:13','2026-04-03 05:04:13'),(21,'Dpto','Alquiler','PART-270553','DOM-498037','Bianca ','5877','1','s','Rosario',13,'2026-04-03 05:28:27','2026-04-03 05:28:27'),(22,'Casa','Alquiler','PART-606024','DOM-898156','Olivera ','9',NULL,NULL,'Rosario',14,'2026-04-03 05:28:27','2026-04-03 05:28:27'),(23,'Casa','Alquiler','PART-458320','DOM-748483','Emily ','89',NULL,NULL,'Rosario',15,'2026-04-03 05:28:27','2026-04-03 05:28:27'),(24,'Local','Alquiler','PART-404126','DOM-849319','Nadia ','57145',NULL,NULL,'Rosario',16,'2026-04-03 05:28:27','2026-04-03 05:28:27'),(25,'Dpto','Alquiler','PART-529028','DOM-483011','Maximiliano ','812','1','s','Rosario',17,'2026-04-03 05:28:27','2026-04-03 05:28:27'),(26,'Dpto','Alquiler','PART-818750','DOM-777443','Páez ','3','10','m','Rosario',18,'2026-04-03 05:28:27','2026-04-03 05:28:27'),(27,'Local','Alquiler','PART-686183','DOM-720772','Fátima ','5',NULL,NULL,'Rosario',19,'2026-04-03 05:28:27','2026-04-03 05:28:27'),(28,'Dpto','Alquiler','PART-51161','DOM-979183','Salomé ','6','3','n','Rosario',20,'2026-04-03 05:28:27','2026-04-03 05:28:27'),(29,'Casa','Alquiler','PART-339734','DOM-933978','Fernández ','4',NULL,NULL,'Rosario',21,'2026-04-03 05:28:27','2026-04-03 05:28:27'),(30,'Dpto','Alquiler','PART-435735','DOM-18344','Gael ','13','4','g','Rosario',21,'2026-04-03 05:28:27','2026-04-03 05:28:27'),(31,'Local','Alquiler','PART-748090','DOM-774808','Camila ','28',NULL,NULL,'Rosario',21,'2026-04-03 05:28:27','2026-04-03 05:28:27'),(32,'Casa','Alquiler','PART-374081','DOM-963454','Ramírez ','804',NULL,NULL,'Rosario',22,'2026-04-03 05:28:27','2026-04-03 05:28:27'),(33,'Dpto','Alquiler','PART-522273','DOM-983484','Magdalena ','8555','1','h','Rosario',22,'2026-04-03 05:28:27','2026-04-03 05:28:27'),(34,'Local','Alquiler','PART-427671','DOM-274116','Valentino ','8794',NULL,NULL,'Rosario',22,'2026-04-03 05:28:27','2026-04-03 05:28:27'),(35,'Casa','Alquiler','PART-33135','DOM-632569','Ana ','74703',NULL,NULL,'Rosario',22,'2026-04-03 05:28:27','2026-04-03 05:28:27'),(36,'Casa','Alquiler','PART-897907','DOM-363186','Andrés ','12169',NULL,NULL,'Rosario',23,'2026-04-03 05:28:27','2026-04-03 05:28:27'),(37,'Dpto','Alquiler','PART-391415','DOM-129153','Silvana ','20','8','n','Rosario',23,'2026-04-03 05:28:27','2026-04-03 05:28:27'),(38,'Local','Alquiler','PART-114592','DOM-422054','Urbina ','384',NULL,NULL,'Rosario',23,'2026-04-03 05:28:27','2026-04-03 05:28:27'),(39,'Casa','Alquiler','PART-719298','DOM-679723','Armas ','1493',NULL,NULL,'Rosario',23,'2026-04-03 05:28:27','2026-04-03 05:28:27'),(40,'Dpto','Alquiler','PART-704262','DOM-940192','Castellanos ','33097','6','f','Rosario',23,'2026-04-03 05:28:27','2026-04-03 05:28:27'),(41,'Otro','Alquiler','S/D 1775502417','S/D','calle Salta','224',NULL,NULL,'San Cristóbal',26,'2026-04-06 22:06:57','2026-04-06 22:06:57');
/*!40000 ALTER TABLE `properties` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tenants`
--

DROP TABLE IF EXISTS `tenants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dni` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `whatsapp` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenants_dni_unique` (`dni`),
  UNIQUE KEY `tenants_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenants`
--

LOCK TABLES `tenants` WRITE;
/*!40000 ALTER TABLE `tenants` DISABLE KEYS */;
INSERT INTO `tenants` VALUES (1,'Inquilino','Test','12345678','Direccion test 1234','3408671111','inquilino@test.com','2026-04-03 04:50:51','2026-04-03 04:50:51'),(2,'Dario','DIaz','29850654','Dorrego 890','348963258','dario@testing.com','2026-04-03 15:19:58','2026-04-03 15:19:58'),(5,'SILVIA MARIELA','GRAMAGLIA','24342756','calle Belgrano N 1785','3408577336','scinmueble@gmail.com','2026-04-06 22:06:57','2026-04-06 22:06:57');
/*!40000 ALTER TABLE `tenants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'SuperAdmin','admin@inmoapp.com',NULL,'$2y$12$BtGFd8JiuhcFjKQDKj/3XeLYqgsnmf29ReQ1Ljb9t98oaMU8uxs1e',NULL,'2026-04-03 04:06:39','2026-04-03 05:04:12');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-10 11:30:08
