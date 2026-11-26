-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 15, 2026 at 06:09 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sample_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `add_archive_info`
--

CREATE TABLE `add_archive_info` (
  `id` int(5) NOT NULL,
  `add_id` int(5) NOT NULL,
  `img_ext` varchar(10) NOT NULL,
  `title` varchar(255) NOT NULL,
  `link` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `cat_id` int(5) UNSIGNED ZEROFILL DEFAULT NULL,
  `status` int(1) NOT NULL,
  `last_modify` date NOT NULL,
  `remove_at` date NOT NULL,
  `last_modify_id` int(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ad_info`
--

CREATE TABLE `ad_info` (
  `ad_id` int(11) NOT NULL,
  `ad_page` varchar(3) NOT NULL,
  `cat_id` int(3) DEFAULT NULL,
  `ad_title` varchar(250) NOT NULL,
  `ad_link` varchar(500) NOT NULL,
  `ad_size` int(2) DEFAULT NULL,
  `ad_status` tinyint(1) NOT NULL,
  `ad_start_date` date NOT NULL,
  `ad_end_date` date NOT NULL,
  `ad_pub_date` varchar(100) NOT NULL,
  `ad_publisher` int(5) NOT NULL,
  `img_ext` varchar(25) NOT NULL,
  `last_modify` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `app_config`
--

CREATE TABLE `app_config` (
  `id` int(5) NOT NULL,
  `time_zone` varchar(100) DEFAULT NULL,
  `portal_name` varchar(50) DEFAULT NULL,
  `portal_link` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `contact` varchar(100) DEFAULT NULL,
  `web_title` varchar(100) DEFAULT NULL,
  `web_keyword` varchar(255) DEFAULT NULL,
  `web_description` varchar(500) DEFAULT NULL,
  `address` varchar(100) DEFAULT NULL,
  `portal_fb_link` varchar(250) DEFAULT NULL,
  `portal_tw_link` varchar(250) DEFAULT NULL,
  `portal_yt_link` varchar(250) DEFAULT NULL,
  `portal_ig_link` varchar(250) DEFAULT NULL,
  `portal_logo` varchar(10) DEFAULT NULL,
  `youtube` varchar(50) DEFAULT NULL,
  `last_modifier` int(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `app_config`
--

INSERT INTO `app_config` (`id`, `time_zone`, `portal_name`, `portal_link`, `email`, `contact`, `web_title`, `web_keyword`, `web_description`, `address`, `portal_fb_link`, `portal_tw_link`, `portal_yt_link`, `portal_ig_link`, `portal_logo`, `youtube`, `last_modifier`) VALUES
(1, 'Asia/Dhaka', 'NagriNews', '#', 'info@nagrinews.com', '', 'Nagri News', '', 'Nagri News delivers the latest breaking news, national and international updates, politics, business, sports, entertainment, technology, and local news in Bangla with accurate and timely reporting.', 'Sylhet, Bangladesh', 'https://www.facebook.com/nagrinews?mibextid=wwXIfr&amp;amp;amp;amp;rdid=wcsfXrem19CFL6Vw&amp;amp;amp;amp;share_url=https%3A%2F%2Fwww.facebook.com%2Fshare%2F18xApLhNC1%2F%3Fmibextid%3DwwXIfr#', '', 'http://youtube.com/@nagrinewsmultimedia?si=q5vTINv9Og6mZq0V', '', '.png', '', 3);

-- --------------------------------------------------------

--
-- Table structure for table `app_setting_info`
--

CREATE TABLE `app_setting_info` (
  `app_setting_id` int(3) NOT NULL,
  `app_token` varchar(50) NOT NULL,
  `app_key` varchar(50) NOT NULL,
  `subscription_token` varchar(255) DEFAULT NULL,
  `last_modify_at` datetime DEFAULT NULL,
  `last_modify_by` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `app_user_info`
--

CREATE TABLE `app_user_info` (
  `app_user_id` int(11) NOT NULL,
  `app_user_email` varchar(25) NOT NULL,
  `app_user_status` tinyint(4) NOT NULL,
  `gcmID` varchar(200) NOT NULL,
  `doc` varchar(25) NOT NULL,
  `dom` varchar(25) NOT NULL,
  `categoryList` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `app_version_info`
--

CREATE TABLE `app_version_info` (
  `version_id` int(3) NOT NULL,
  `version_name` varchar(100) NOT NULL,
  `version_details` varchar(250) NOT NULL,
  `active_status` tinyint(1) NOT NULL,
  `doc` timestamp NOT NULL DEFAULT current_timestamp(),
  `dom` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  `creator` int(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `category_info`
--

CREATE TABLE `category_info` (
  `cat_id` int(3) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `cat_en_name` varchar(100) DEFAULT NULL,
  `cat_name` varchar(100) NOT NULL,
  `cat_key_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `keyword` varchar(255) DEFAULT NULL,
  `cat_status` tinyint(1) NOT NULL,
  `cat_doc` timestamp NOT NULL DEFAULT current_timestamp(),
  `cat_dom` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `cat_creator` int(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ci_sessions`
--

CREATE TABLE `ci_sessions` (
  `id` varchar(40) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `timestamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `data` blob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `common_details`
--

CREATE TABLE `common_details` (
  `news_id` int(25) NOT NULL,
  `news_type` varchar(250) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `news_award` varchar(500) NOT NULL,
  `news_details` longtext CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `news_date` varchar(100) NOT NULL,
  `news_pic_type` varchar(25) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daily_visitor_info`
--

CREATE TABLE `daily_visitor_info` (
  `day_id` int(11) NOT NULL,
  `day_date` varchar(11) NOT NULL,
  `day_visitor` int(7) NOT NULL,
  `day_doc` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `districts`
--

CREATE TABLE `districts` (
  `id` int(2) NOT NULL,
  `division_id` int(1) NOT NULL,
  `name` varchar(25) NOT NULL,
  `bn_name` varchar(25) NOT NULL,
  `lat` varchar(15) DEFAULT NULL,
  `lon` varchar(15) DEFAULT NULL,
  `url` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `districts`
--

INSERT INTO `districts` (`id`, `division_id`, `name`, `bn_name`, `lat`, `lon`, `url`) VALUES
(1, 1, 'Comilla', 'কুমিল্লা', '23.4682747', '91.1788135', 'www.comilla.gov.bd'),
(2, 1, 'Feni', 'ফেনী', '23.023231', '91.3840844', 'www.feni.gov.bd'),
(3, 1, 'Brahmanbaria', 'ব্রাহ্মণবাড়িয়া', '23.9570904', '91.1119286', 'www.brahmanbaria.gov.bd'),
(4, 1, 'Rangamati', 'রাঙ্গামাটি', '22.65561018', '92.17541121', 'www.rangamati.gov.bd'),
(5, 1, 'Noakhali', 'নোয়াখালী', '22.869563', '91.099398', 'www.noakhali.gov.bd'),
(6, 1, 'Chandpur', 'চাঁদপুর', '23.2332585', '90.6712912', 'www.chandpur.gov.bd'),
(7, 1, 'Lakshmipur', 'লক্ষ্মীপুর', '22.942477', '90.841184', 'www.lakshmipur.gov.bd'),
(8, 1, 'Chattogram', 'চট্টগ্রাম', '22.335109', '91.834073', 'www.chittagong.gov.bd'),
(9, 1, 'Coxsbazar', 'কক্সবাজার', '21.44315751', '91.97381741', 'www.coxsbazar.gov.bd'),
(10, 1, 'Khagrachhari', 'খাগড়াছড়ি', '23.119285', '91.984663', 'www.khagrachhari.gov.bd'),
(11, 1, 'Bandarban', 'বান্দরবান', '22.1953275', '92.2183773', 'www.bandarban.gov.bd'),
(12, 2, 'Sirajganj', 'সিরাজগঞ্জ', '24.4533978', '89.7006815', 'www.sirajganj.gov.bd'),
(13, 2, 'Pabna', 'পাবনা', '23.998524', '89.233645', 'www.pabna.gov.bd'),
(14, 2, 'Bogura', 'বগুড়া', '24.8465228', '89.377755', 'www.bogra.gov.bd'),
(15, 2, 'Rajshahi', 'রাজশাহী', '24.37230298', '88.56307623', 'www.rajshahi.gov.bd'),
(16, 2, 'Natore', 'নাটোর', '24.420556', '89.000282', 'www.natore.gov.bd'),
(17, 2, 'Joypurhat', 'জয়পুরহাট', '25.09636876', '89.04004280', 'www.joypurhat.gov.bd'),
(18, 2, 'Chapainawabganj', 'চাঁপাইনবাবগঞ্জ', '24.5965034', '88.2775122', 'www.chapainawabganj.gov.bd'),
(19, 2, 'Naogaon', 'নওগাঁ', '24.83256191', '88.92485205', 'www.naogaon.gov.bd'),
(20, 3, 'Jashore', 'যশোর', '23.16643', '89.2081126', 'www.jessore.gov.bd'),
(21, 3, 'Satkhira', 'সাতক্ষীরা', NULL, NULL, 'www.satkhira.gov.bd'),
(22, 3, 'Meherpur', 'মেহেরপুর', '23.762213', '88.631821', 'www.meherpur.gov.bd'),
(23, 3, 'Narail', 'নড়াইল', '23.172534', '89.512672', 'www.narail.gov.bd'),
(24, 3, 'Chuadanga', 'চুয়াডাঙ্গা', '23.6401961', '88.841841', 'www.chuadanga.gov.bd'),
(25, 3, 'Kushtia', 'কুষ্টিয়া', '23.901258', '89.120482', 'www.kushtia.gov.bd'),
(26, 3, 'Magura', 'মাগুরা', '23.487337', '89.419956', 'www.magura.gov.bd'),
(27, 3, 'Khulna', 'খুলনা', '22.815774', '89.568679', 'www.khulna.gov.bd'),
(28, 3, 'Bagerhat', 'বাগেরহাট', '22.651568', '89.785938', 'www.bagerhat.gov.bd'),
(29, 3, 'Jhenaidah', 'ঝিনাইদহ', '23.5448176', '89.1539213', 'www.jhenaidah.gov.bd'),
(30, 4, 'Jhalakathi', 'ঝালকাঠি', NULL, NULL, 'www.jhalakathi.gov.bd'),
(31, 4, 'Patuakhali', 'পটুয়াখালী', '22.3596316', '90.3298712', 'www.patuakhali.gov.bd'),
(32, 4, 'Pirojpur', 'পিরোজপুর', NULL, NULL, 'www.pirojpur.gov.bd'),
(33, 4, 'Barisal', 'বরিশাল', NULL, NULL, 'www.barisal.gov.bd'),
(34, 4, 'Bhola', 'ভোলা', '22.685923', '90.648179', 'www.bhola.gov.bd'),
(35, 4, 'Barguna', 'বরগুনা', NULL, NULL, 'www.barguna.gov.bd'),
(36, 5, 'Sylhet', 'সিলেট', '24.8897956', '91.8697894', 'www.sylhet.gov.bd'),
(37, 5, 'Moulvibazar', 'মৌলভীবাজার', '24.482934', '91.777417', 'www.moulvibazar.gov.bd'),
(38, 5, 'Habiganj', 'হবিগঞ্জ', '24.374945', '91.41553', 'www.habiganj.gov.bd'),
(39, 5, 'Sunamganj', 'সুনামগঞ্জ', '25.0658042', '91.3950115', 'www.sunamganj.gov.bd'),
(40, 6, 'Narsingdi', 'নরসিংদী', '23.932233', '90.71541', 'www.narsingdi.gov.bd'),
(41, 6, 'Gazipur', 'গাজীপুর', '24.0022858', '90.4264283', 'www.gazipur.gov.bd'),
(42, 6, 'Shariatpur', 'শরীয়তপুর', NULL, NULL, 'www.shariatpur.gov.bd'),
(43, 6, 'Narayanganj', 'নারায়ণগঞ্জ', '23.63366', '90.496482', 'www.narayanganj.gov.bd'),
(44, 6, 'Tangail', 'টাঙ্গাইল', '24.26361358', '89.91794786', 'www.tangail.gov.bd'),
(45, 6, 'Kishoreganj', 'কিশোরগঞ্জ', '24.444937', '90.776575', 'www.kishoreganj.gov.bd'),
(46, 6, 'Manikganj', 'মানিকগঞ্জ', NULL, NULL, 'www.manikganj.gov.bd'),
(47, 6, 'Dhaka', 'ঢাকা', '23.7115253', '90.4111451', 'www.dhaka.gov.bd'),
(48, 6, 'Munshiganj', 'মুন্সিগঞ্জ', NULL, NULL, 'www.munshiganj.gov.bd'),
(49, 6, 'Rajbari', 'রাজবাড়ী', '23.7574305', '89.6444665', 'www.rajbari.gov.bd'),
(50, 6, 'Madaripur', 'মাদারীপুর', '23.164102', '90.1896805', 'www.madaripur.gov.bd'),
(51, 6, 'Gopalganj', 'গোপালগঞ্জ', '23.0050857', '89.8266059', 'www.gopalganj.gov.bd'),
(52, 6, 'Faridpur', 'ফরিদপুর', '23.6070822', '89.8429406', 'www.faridpur.gov.bd'),
(53, 7, 'Panchagarh', 'পঞ্চগড়', '26.3411', '88.5541606', 'www.panchagarh.gov.bd'),
(54, 7, 'Dinajpur', 'দিনাজপুর', '25.6217061', '88.6354504', 'www.dinajpur.gov.bd'),
(55, 7, 'Lalmonirhat', 'লালমনিরহাট', NULL, NULL, 'www.lalmonirhat.gov.bd'),
(56, 7, 'Nilphamari', 'নীলফামারী', '25.931794', '88.856006', 'www.nilphamari.gov.bd'),
(57, 7, 'Gaibandha', 'গাইবান্ধা', '25.328751', '89.528088', 'www.gaibandha.gov.bd'),
(58, 7, 'Thakurgaon', 'ঠাকুরগাঁও', '26.0336945', '88.4616834', 'www.thakurgaon.gov.bd'),
(59, 7, 'Rangpur', 'রংপুর', '25.7558096', '89.244462', 'www.rangpur.gov.bd'),
(60, 7, 'Kurigram', 'কুড়িগ্রাম', '25.805445', '89.636174', 'www.kurigram.gov.bd'),
(61, 8, 'Sherpur', 'শেরপুর', '25.0204933', '90.0152966', 'www.sherpur.gov.bd'),
(62, 8, 'Mymensingh', 'ময়মনসিংহ', '24.7465670', '90.4072093', 'www.mymensingh.gov.bd'),
(63, 8, 'Jamalpur', 'জামালপুর', '24.937533', '89.937775', 'www.jamalpur.gov.bd'),
(64, 8, 'Netrokona', 'নেত্রকোণা', '24.870955', '90.727887', 'www.netrokona.gov.bd');

-- --------------------------------------------------------

--
-- Table structure for table `divisions`
--

CREATE TABLE `divisions` (
  `id` int(1) NOT NULL,
  `name` varchar(25) NOT NULL,
  `bn_name` varchar(25) NOT NULL,
  `url` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `divisions`
--

INSERT INTO `divisions` (`id`, `name`, `bn_name`, `url`) VALUES
(1, 'Chattagram', 'চট্টগ্রাম', 'www.chittagongdiv.gov.bd'),
(2, 'Rajshahi', 'রাজশাহী', 'www.rajshahidiv.gov.bd'),
(3, 'Khulna', 'খুলনা', 'www.khulnadiv.gov.bd'),
(4, 'Barisal', 'বরিশাল', 'www.barisaldiv.gov.bd'),
(5, 'Sylhet', 'সিলেট', 'www.sylhetdiv.gov.bd'),
(6, 'Dhaka', 'ঢাকা', 'www.dhakadiv.gov.bd'),
(7, 'Rangpur', 'রংপুর', 'www.rangpurdiv.gov.bd'),
(8, 'Mymensingh', 'ময়মনসিংহ', 'www.mymensinghdiv.gov.bd');

-- --------------------------------------------------------

--
-- Table structure for table `email_subscriber_info`
--

CREATE TABLE `email_subscriber_info` (
  `sub_id` int(10) NOT NULL,
  `sub_email` varchar(30) NOT NULL,
  `sub_status` tinyint(4) NOT NULL,
  `sub_doc` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `epaper_info`
--

CREATE TABLE `epaper_info` (
  `ep_id` int(11) NOT NULL,
  `ep_subject` varchar(255) NOT NULL,
  `ep_date` date NOT NULL,
  `img_ext` varchar(10) NOT NULL,
  `ep_file` varchar(10) NOT NULL,
  `ep_doc` timestamp NULL DEFAULT current_timestamp(),
  `ep_dom` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ep_creator` int(5) NOT NULL,
  `ep_status` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(40) NOT NULL,
  `login` varchar(50) NOT NULL,
  `time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `media_info`
--

CREATE TABLE `media_info` (
  `media_id` int(11) NOT NULL,
  `media_name` varchar(250) NOT NULL,
  `media_type` int(5) NOT NULL,
  `media_en_name` varchar(250) NOT NULL,
  `img_ext` varchar(25) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `media_news_info`
--

CREATE TABLE `media_news_info` (
  `media_news_id` int(11) NOT NULL,
  `media_id` int(5) NOT NULL,
  `media_news_headline` varchar(250) NOT NULL,
  `media_news_link` varchar(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `member_info`
--

CREATE TABLE `member_info` (
  `id` int(11) NOT NULL,
  `member_name` varchar(50) NOT NULL,
  `member_designation` varchar(80) NOT NULL,
  `member_email` varchar(50) NOT NULL,
  `member_phone` varchar(15) NOT NULL,
  `member_group` int(11) NOT NULL,
  `img_ext` varchar(15) NOT NULL,
  `doc` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `dom` datetime NOT NULL,
  `creator` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `rank` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `news_common_info`
--

CREATE TABLE `news_common_info` (
  `news_id` int(11) NOT NULL,
  `news_headline` varchar(250) NOT NULL,
  `headline_tag` varchar(100) NOT NULL,
  `news_details_brief` varchar(400) NOT NULL,
  `news_reporter` varchar(100) NOT NULL,
  `author_id` int(5) DEFAULT NULL,
  `img_ext` varchar(10) NOT NULL,
  `cat_id` int(3) NOT NULL,
  `sub_cat_id` int(2) NOT NULL,
  `page_id` int(2) DEFAULT NULL,
  `news_status` tinyint(1) NOT NULL,
  `news_type` int(1) DEFAULT 1,
  `spc_type` int(2) DEFAULT 0,
  `catStatus` tinyint(1) NOT NULL,
  `latestStatus` tinyint(1) NOT NULL,
  `news_pub_date` date NOT NULL,
  `news_pub_time` time NOT NULL,
  `news_mod_date` date NOT NULL,
  `news_mod_time` time NOT NULL,
  `news_publisher` int(5) NOT NULL,
  `news_approver` int(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `news_config`
--

CREATE TABLE `news_config` (
  `special_type` tinyint(1) DEFAULT NULL,
  `news_caption` tinyint(1) DEFAULT NULL,
  `news_page` tinyint(1) DEFAULT NULL,
  `location` tinyint(1) DEFAULT NULL,
  `backdated_news` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `news_config`
--

INSERT INTO `news_config` (`special_type`, `news_caption`, `news_page`, `location`, `backdated_news`) VALUES
(0, 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `news_feature`
--

CREATE TABLE `news_feature` (
  `id` int(5) NOT NULL,
  `name` varchar(50) NOT NULL,
  `file_ext` varchar(10) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `last_modify` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `modifier` int(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `news_gallery_info`
--

CREATE TABLE `news_gallery_info` (
  `img_id` int(11) NOT NULL,
  `img_caption` varchar(300) NOT NULL,
  `img_ext` varchar(25) NOT NULL,
  `pub_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `gallery_status` tinyint(1) NOT NULL,
  `creator` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `news_gallery_info`
--

INSERT INTO `news_gallery_info` (`img_id`, `img_caption`, `img_ext`, `pub_date`, `gallery_status`, `creator`) VALUES
(1, 'সিলেট এর জাফলং এর দৃশ্য', '.png', '2022-03-29 12:42:50', 1, '2'),
(2, 'বিছনাকান্দি পর্যটন কেন্দ্র', '.jpg', '2021-12-27 04:02:16', 1, '2');

-- --------------------------------------------------------

--
-- Table structure for table `news_info`
--

CREATE TABLE `news_info` (
  `news_id` int(11) NOT NULL,
  `news_slug` varchar(250) NOT NULL,
  `news_sub_headline` varchar(400) NOT NULL,
  `news_details` longtext NOT NULL,
  `img_caption` varchar(150) NOT NULL,
  `video_link` varchar(150) DEFAULT NULL,
  `video_caption` varchar(250) DEFAULT NULL,
  `audio_link` varchar(150) DEFAULT NULL,
  `audio_caption` varchar(250) DEFAULT NULL,
  `news_source` varchar(150) DEFAULT NULL,
  `news_source_link` varchar(500) DEFAULT NULL,
  `news_area` varchar(100) DEFAULT NULL,
  `news_zone` varchar(100) DEFAULT NULL,
  `copy_protection_status` tinyint(1) NOT NULL,
  `division_id` int(1) DEFAULT NULL,
  `district_id` int(2) DEFAULT NULL,
  `upazila_id` int(3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `news_page_info`
--

CREATE TABLE `news_page_info` (
  `page_id` int(2) NOT NULL,
  `name` varchar(20) NOT NULL,
  `name_bn` varchar(20) NOT NULL,
  `rank` int(2) DEFAULT NULL,
  `last_modify` datetime NOT NULL,
  `status` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `news_reader_info`
--

CREATE TABLE `news_reader_info` (
  `news_id` int(10) NOT NULL,
  `news_reader` int(9) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `news_segment`
--

CREATE TABLE `news_segment` (
  `segment_id` int(11) NOT NULL,
  `segment_title` varchar(100) NOT NULL,
  `segment_title_show` tinyint(1) NOT NULL,
  `segment_tag` varchar(100) DEFAULT NULL,
  `segment_seo_title` varchar(100) NOT NULL,
  `segment_seo_details` text DEFAULT NULL,
  `segment_seo_keywords` varchar(500) DEFAULT NULL,
  `segment_banner_img` varchar(10) DEFAULT NULL,
  `banner_show` tinyint(1) NOT NULL,
  `segment_bg_status` tinyint(1) NOT NULL,
  `segment_bg_type` int(1) DEFAULT NULL,
  `segment_bg_img` varchar(10) DEFAULT NULL,
  `segment_bg_color` varchar(20) DEFAULT NULL,
  `segment_bg_border` varchar(20) DEFAULT NULL,
  `segment_title_color` varchar(20) DEFAULT NULL,
  `segment_headline_color` varchar(20) DEFAULT NULL,
  `segment_details_status` tinyint(1) DEFAULT NULL,
  `segment_details_color` varchar(20) DEFAULT NULL,
  `segment_time_status` tinyint(1) DEFAULT NULL,
  `segment_time_color` varchar(20) DEFAULT NULL,
  `segment_link_hover` varchar(20) DEFAULT NULL,
  `segment_start_date` date NOT NULL,
  `segment_end_date` date NOT NULL,
  `created_by` int(5) NOT NULL,
  `updated_by` int(5) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `status` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `news_seo_info`
--

CREATE TABLE `news_seo_info` (
  `news_seo_id` int(11) NOT NULL,
  `news_id` int(11) NOT NULL,
  `seo_title` varchar(250) NOT NULL,
  `seo_keyword` varchar(350) NOT NULL,
  `seo_description` varchar(400) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `news_tag_info`
--

CREATE TABLE `news_tag_info` (
  `news_tag_id` int(11) NOT NULL,
  `news_id` int(11) NOT NULL,
  `news_tag` varchar(250) NOT NULL,
  `relevant_article` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `news_type_info`
--

CREATE TABLE `news_type_info` (
  `news_type_id` int(3) NOT NULL,
  `type_name` varchar(50) NOT NULL,
  `slug_name` varchar(50) DEFAULT NULL,
  `last_modify` datetime NOT NULL,
  `creator` int(3) NOT NULL,
  `type_status` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='This is for News Type - Like Normal, Audio, Video;';

-- --------------------------------------------------------

--
-- Table structure for table `news_writer_info`
--

CREATE TABLE `news_writer_info` (
  `news_writer_id` int(10) NOT NULL,
  `news_id` int(11) DEFAULT NULL,
  `writer_type` int(1) NOT NULL,
  `author_ids` varchar(255) DEFAULT NULL,
  `reporter_ids` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `page_info`
--

CREATE TABLE `page_info` (
  `page_id` int(3) NOT NULL,
  `page_name` varchar(150) NOT NULL,
  `page_key_name` varchar(100) NOT NULL,
  `page_status` tinyint(1) NOT NULL,
  `page_doc` varchar(100) NOT NULL,
  `page_dom` varchar(100) NOT NULL,
  `page_creator` int(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pol_info`
--

CREATE TABLE `pol_info` (
  `pol_id` int(11) NOT NULL,
  `pol_title` varchar(500) NOT NULL,
  `pol_start_date` date NOT NULL,
  `pol_end_date` date NOT NULL,
  `pol_status` tinyint(1) NOT NULL,
  `pol_publisher` int(5) NOT NULL,
  `yes` int(10) NOT NULL,
  `no` int(10) NOT NULL,
  `no_com` int(10) NOT NULL,
  `pol_pub_date` varchar(100) NOT NULL,
  `pol_mod_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prayer_table`
--

CREATE TABLE `prayer_table` (
  `timing_date` date NOT NULL,
  `Fajr` varchar(5) DEFAULT NULL,
  `Dhuhr` varchar(5) DEFAULT NULL,
  `Asr` varchar(5) DEFAULT NULL,
  `Maghrib` varchar(5) DEFAULT NULL,
  `Isha` varchar(5) DEFAULT NULL,
  `Sunrise` varchar(5) DEFAULT NULL,
  `Sunset` varchar(5) DEFAULT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscription_tbl`
--

CREATE TABLE `subscription_tbl` (
  `subscription_id` int(11) NOT NULL,
  `subscription_email` varchar(255) NOT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` int(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sub_category_info`
--

CREATE TABLE `sub_category_info` (
  `sub_category_id` int(3) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `sub_cat_name` varchar(100) NOT NULL,
  `sub_cat_en_name` varchar(100) DEFAULT NULL,
  `sub_cat_key_name` varchar(100) NOT NULL,
  `keywords` varchar(250) DEFAULT NULL,
  `description` varchar(250) DEFAULT NULL,
  `category_id` int(3) NOT NULL,
  `sub_cat_status` tinyint(1) NOT NULL,
  `doc` datetime NOT NULL,
  `dom` timestamp NULL DEFAULT NULL,
  `creator` int(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_photos_gallery`
--

CREATE TABLE `tbl_photos_gallery` (
  `gallery_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `sub_title` varchar(255) DEFAULT NULL,
  `gallery_contents` text NOT NULL,
  `doc` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `dom` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `creator` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_share`
--

CREATE TABLE `tbl_share` (
  `share_id` int(2) NOT NULL,
  `img_ext` varchar(10) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `creator` int(5) NOT NULL,
  `doc` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `dom` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `temp_news_info`
--

CREATE TABLE `temp_news_info` (
  `temp_news_id` int(11) NOT NULL,
  `upload_time` datetime NOT NULL,
  `news_headline` varchar(255) NOT NULL,
  `news_sub_headline` varchar(400) NOT NULL,
  `news_details_brief` varchar(400) DEFAULT NULL,
  `headline_tag` varchar(100) DEFAULT NULL,
  `news_details` longtext NOT NULL,
  `img_ext` varchar(10) DEFAULT NULL,
  `img_caption` varchar(100) DEFAULT NULL,
  `category` int(3) NOT NULL,
  `sub_category` int(3) DEFAULT NULL,
  `news_status` int(2) NOT NULL,
  `news_type` int(2) DEFAULT NULL,
  `news_spc_type` int(2) DEFAULT NULL,
  `page_id` int(5) DEFAULT NULL,
  `author` varchar(250) DEFAULT NULL,
  `reporter` varchar(250) DEFAULT NULL,
  `video_src` varchar(50) DEFAULT NULL,
  `video_link` varchar(100) DEFAULT NULL,
  `audio_src` varchar(50) DEFAULT NULL,
  `audio_link` varchar(250) DEFAULT NULL,
  `news_src` varchar(50) DEFAULT NULL,
  `news_link` varchar(250) DEFAULT NULL,
  `news_tag` varchar(100) DEFAULT NULL,
  `division_id` int(1) DEFAULT NULL,
  `district_id` int(2) DEFAULT NULL,
  `upazila_id` int(3) DEFAULT NULL,
  `seo_headline` varchar(250) DEFAULT NULL,
  `seo_tag` varchar(200) DEFAULT NULL,
  `seo_details` varchar(400) DEFAULT NULL,
  `temp_news_status` int(2) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `creator` int(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `user_full_name` varchar(150) NOT NULL,
  `user_bn_name` varchar(50) DEFAULT NULL,
  `user_address` varchar(250) NOT NULL,
  `user_type` int(10) NOT NULL,
  `email` varchar(100) NOT NULL,
  `user_mobile` varchar(100) NOT NULL,
  `user_web` varchar(255) DEFAULT NULL,
  `user_bio` tinytext DEFAULT NULL,
  `user_designation` varchar(50) DEFAULT NULL,
  `img_ext` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `activated` tinyint(1) NOT NULL DEFAULT 1,
  `banned` tinyint(1) NOT NULL DEFAULT 0,
  `ban_reason` varchar(255) DEFAULT NULL,
  `new_password_key` varchar(50) DEFAULT NULL,
  `new_password_requested` datetime DEFAULT NULL,
  `new_email` varchar(100) DEFAULT NULL,
  `new_email_key` varchar(50) DEFAULT NULL,
  `last_ip` varchar(40) NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_publisher` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `user_autologin`
--

CREATE TABLE `user_autologin` (
  `key_id` char(32) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `user_agent` varchar(150) NOT NULL,
  `last_ip` varchar(40) NOT NULL,
  `last_login` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

CREATE TABLE `user_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `country` varchar(20) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `add_archive_info`
--
ALTER TABLE `add_archive_info`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ad_info`
--
ALTER TABLE `ad_info`
  ADD PRIMARY KEY (`ad_id`);

--
-- Indexes for table `app_config`
--
ALTER TABLE `app_config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `app_setting_info`
--
ALTER TABLE `app_setting_info`
  ADD PRIMARY KEY (`app_setting_id`);

--
-- Indexes for table `app_user_info`
--
ALTER TABLE `app_user_info`
  ADD PRIMARY KEY (`app_user_id`);

--
-- Indexes for table `app_version_info`
--
ALTER TABLE `app_version_info`
  ADD PRIMARY KEY (`version_id`);

--
-- Indexes for table `category_info`
--
ALTER TABLE `category_info`
  ADD PRIMARY KEY (`cat_id`),
  ADD KEY `cat_id` (`cat_id`);

--
-- Indexes for table `ci_sessions`
--
ALTER TABLE `ci_sessions`
  ADD KEY `ci_sessions_timestamp` (`timestamp`);

--
-- Indexes for table `common_details`
--
ALTER TABLE `common_details`
  ADD PRIMARY KEY (`news_id`);

--
-- Indexes for table `daily_visitor_info`
--
ALTER TABLE `daily_visitor_info`
  ADD PRIMARY KEY (`day_id`);

--
-- Indexes for table `districts`
--
ALTER TABLE `districts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `division_id` (`division_id`);

--
-- Indexes for table `divisions`
--
ALTER TABLE `divisions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `email_subscriber_info`
--
ALTER TABLE `email_subscriber_info`
  ADD PRIMARY KEY (`sub_id`);

--
-- Indexes for table `epaper_info`
--
ALTER TABLE `epaper_info`
  ADD PRIMARY KEY (`ep_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `media_info`
--
ALTER TABLE `media_info`
  ADD PRIMARY KEY (`media_id`);

--
-- Indexes for table `media_news_info`
--
ALTER TABLE `media_news_info`
  ADD PRIMARY KEY (`media_news_id`);

--
-- Indexes for table `member_info`
--
ALTER TABLE `member_info`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `news_common_info`
--
ALTER TABLE `news_common_info`
  ADD PRIMARY KEY (`news_id`),
  ADD KEY `news_id` (`news_id`),
  ADD KEY `news_id_2` (`news_id`),
  ADD KEY `cat_id` (`cat_id`),
  ADD KEY `sub_cat_id` (`sub_cat_id`),
  ADD KEY `news_id_3` (`news_id`);

--
-- Indexes for table `news_feature`
--
ALTER TABLE `news_feature`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `news_gallery_info`
--
ALTER TABLE `news_gallery_info`
  ADD PRIMARY KEY (`img_id`);

--
-- Indexes for table `news_info`
--
ALTER TABLE `news_info`
  ADD PRIMARY KEY (`news_id`),
  ADD KEY `news_id` (`news_id`),
  ADD KEY `news_id_2` (`news_id`),
  ADD KEY `news_sub_headline` (`news_sub_headline`);

--
-- Indexes for table `news_page_info`
--
ALTER TABLE `news_page_info`
  ADD PRIMARY KEY (`page_id`);

--
-- Indexes for table `news_reader_info`
--
ALTER TABLE `news_reader_info`
  ADD PRIMARY KEY (`news_id`),
  ADD KEY `news_id` (`news_id`),
  ADD KEY `news_id_2` (`news_id`),
  ADD KEY `news_reader` (`news_reader`),
  ADD KEY `news_reader_2` (`news_reader`),
  ADD KEY `news_id_3` (`news_id`);

--
-- Indexes for table `news_segment`
--
ALTER TABLE `news_segment`
  ADD PRIMARY KEY (`segment_id`);

--
-- Indexes for table `news_seo_info`
--
ALTER TABLE `news_seo_info`
  ADD PRIMARY KEY (`news_seo_id`),
  ADD KEY `news_seo_id` (`news_seo_id`);

--
-- Indexes for table `news_tag_info`
--
ALTER TABLE `news_tag_info`
  ADD PRIMARY KEY (`news_tag_id`);

--
-- Indexes for table `news_type_info`
--
ALTER TABLE `news_type_info`
  ADD PRIMARY KEY (`news_type_id`);

--
-- Indexes for table `news_writer_info`
--
ALTER TABLE `news_writer_info`
  ADD PRIMARY KEY (`news_writer_id`);

--
-- Indexes for table `page_info`
--
ALTER TABLE `page_info`
  ADD PRIMARY KEY (`page_id`);

--
-- Indexes for table `pol_info`
--
ALTER TABLE `pol_info`
  ADD PRIMARY KEY (`pol_id`);

--
-- Indexes for table `prayer_table`
--
ALTER TABLE `prayer_table`
  ADD PRIMARY KEY (`timing_date`);

--
-- Indexes for table `subscription_tbl`
--
ALTER TABLE `subscription_tbl`
  ADD PRIMARY KEY (`subscription_id`),
  ADD UNIQUE KEY `subscription_email` (`subscription_email`);

--
-- Indexes for table `sub_category_info`
--
ALTER TABLE `sub_category_info`
  ADD PRIMARY KEY (`sub_category_id`);

--
-- Indexes for table `tbl_photos_gallery`
--
ALTER TABLE `tbl_photos_gallery`
  ADD PRIMARY KEY (`gallery_id`);

--
-- Indexes for table `tbl_share`
--
ALTER TABLE `tbl_share`
  ADD PRIMARY KEY (`share_id`);

--
-- Indexes for table `temp_news_info`
--
ALTER TABLE `temp_news_info`
  ADD PRIMARY KEY (`temp_news_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_autologin`
--
ALTER TABLE `user_autologin`
  ADD PRIMARY KEY (`key_id`,`user_id`);

--
-- Indexes for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ad_info`
--
ALTER TABLE `ad_info`
  MODIFY `ad_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `category_info`
--
ALTER TABLE `category_info`
  MODIFY `cat_id` int(3) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `common_details`
--
ALTER TABLE `common_details`
  MODIFY `news_id` int(25) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `daily_visitor_info`
--
ALTER TABLE `daily_visitor_info`
  MODIFY `day_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `districts`
--
ALTER TABLE `districts`
  MODIFY `id` int(2) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `divisions`
--
ALTER TABLE `divisions`
  MODIFY `id` int(1) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `email_subscriber_info`
--
ALTER TABLE `email_subscriber_info`
  MODIFY `sub_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `epaper_info`
--
ALTER TABLE `epaper_info`
  MODIFY `ep_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `media_info`
--
ALTER TABLE `media_info`
  MODIFY `media_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `media_news_info`
--
ALTER TABLE `media_news_info`
  MODIFY `media_news_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `member_info`
--
ALTER TABLE `member_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `news_feature`
--
ALTER TABLE `news_feature`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `news_gallery_info`
--
ALTER TABLE `news_gallery_info`
  MODIFY `img_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `news_info`
--
ALTER TABLE `news_info`
  MODIFY `news_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `news_page_info`
--
ALTER TABLE `news_page_info`
  MODIFY `page_id` int(2) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `news_segment`
--
ALTER TABLE `news_segment`
  MODIFY `segment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `news_seo_info`
--
ALTER TABLE `news_seo_info`
  MODIFY `news_seo_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `news_tag_info`
--
ALTER TABLE `news_tag_info`
  MODIFY `news_tag_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `news_type_info`
--
ALTER TABLE `news_type_info`
  MODIFY `news_type_id` int(3) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `news_writer_info`
--
ALTER TABLE `news_writer_info`
  MODIFY `news_writer_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `page_info`
--
ALTER TABLE `page_info`
  MODIFY `page_id` int(3) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pol_info`
--
ALTER TABLE `pol_info`
  MODIFY `pol_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscription_tbl`
--
ALTER TABLE `subscription_tbl`
  MODIFY `subscription_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sub_category_info`
--
ALTER TABLE `sub_category_info`
  MODIFY `sub_category_id` int(3) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_photos_gallery`
--
ALTER TABLE `tbl_photos_gallery`
  MODIFY `gallery_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `temp_news_info`
--
ALTER TABLE `temp_news_info`
  MODIFY `temp_news_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_profiles`
--
ALTER TABLE `user_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `districts`
--
ALTER TABLE `districts`
  ADD CONSTRAINT `districts_ibfk_2` FOREIGN KEY (`division_id`) REFERENCES `divisions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
