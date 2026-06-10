-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 03, 2026 at 06:39 PM
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
-- Database: `amsa_web`
--

--
-- Phase 10.5 live migration compatibility:
-- ALTER TABLE `user` MODIFY `role` enum('admin','member','president','vice_president','secretary','male_treasurer','female_treasurer','system_admin') NOT NULL DEFAULT 'member';
-- UPDATE `user` SET `role` = 'system_admin' WHERE `role` = 'admin';
-- ALTER TABLE `user` MODIFY `role` enum('member','president','vice_president','secretary','male_treasurer','female_treasurer','system_admin') NOT NULL DEFAULT 'member';
--

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `whatsapp_number` varchar(30) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text DEFAULT NULL,
  `submission_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `image`
--

CREATE TABLE `image` (
  `id` int(11) NOT NULL,
  `post_id` int(11) DEFAULT NULL,
  `img_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fundraising`
--

CREATE TABLE IF NOT EXISTS `fundraising` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('published','draft') DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fundraising_images`
--

CREATE TABLE IF NOT EXISTS `fundraising_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fundraising_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `display_order` int(11) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_fundraising_images_fundraising` (`fundraising_id`),
  CONSTRAINT `fk_fundraising_images_fundraising` FOREIGN KEY (`fundraising_id`) REFERENCES `fundraising` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `image`
--

INSERT INTO `image` (`id`, `post_id`, `img_name`) VALUES
(2, 1, '1776508630_IMG_7701.PNG'),
(3, 2, '1776528494_Gemini_Generated_Image_6pawzk6pawzk6paw.png'),
(4, 3, '1776530071_White Pink Illustrative Communication Tips Poster.png'),
(5, 4, '1776530182_IMG_7720.JPG'),
(6, 5, '1776531334_future.jpg'),
(7, 6, '1776531481_EcoSip_ Sustainable Cup Innovation (1).png'),
(9, 8, '1777903671_0_3 zero clubs.webp'),
(10, 9, '1777904094_0_Coding bootcamp (2).png'),
(12, 10, '1777905545_0_WhatsApp Image 2026-03-24 at 4.51.05 PM (1).jpeg'),
(13, 11, '1777908316_0_Screenshot (121).png'),
(14, 11, '1777908316_1_Screenshot (122).png'),
(15, 11, '1777908316_2_Screenshot (123).png');

-- --------------------------------------------------------

--
-- Table structure for table `point_category`
--

CREATE TABLE `point_category` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `points` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `point_category`
--

INSERT INTO `point_category` (`id`, `category_name`, `points`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Community Event Participation', 10, 'Participate in any AMSA community event', 'active', '2026-05-04 12:00:00', NULL),
(2, 'Fundraising Campaign', 20, 'Contribute to fundraising activities', 'active', '2026-05-04 12:00:00', NULL),
(3, 'Volunteer Work', 15, 'Volunteer for AMSA projects', 'active', '2026-05-04 12:00:00', NULL),
(4, 'Event Organization', 30, 'Organize or coordinate AMSA events', 'active', '2026-05-04 12:00:00', NULL),
(5, 'Workshop Attendance', 5, 'Attend AMSA workshops or seminars', 'active', '2026-05-04 12:00:00', NULL),
(6, 'Leadership Role', 50, 'Serve in a leadership position', 'active', '2026-05-04 12:00:00', NULL),
(7, 'mkm', 1000, 'If you are mkm then you are eligible for this', 'active', '2026-05-04 22:28:11', NULL),
(8, 'web dev', 1000, 'web dev', 'active', '2026-05-04 23:11:27', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `point_request`
--

CREATE TABLE `point_request` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `point_category_id` int(11) NOT NULL,
  `eop_evidence` varchar(255) NOT NULL COMMENT 'End of Participation evidence file path',
  `description` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_remarks` text DEFAULT NULL,
  `request_date` datetime DEFAULT current_timestamp(),
  `review_date` datetime DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `point_request`
--

INSERT INTO `point_request` (`id`, `user_id`, `point_category_id`, `eop_evidence`, `description`, `status`, `admin_remarks`, `request_date`, `review_date`, `reviewed_by`) VALUES
(1, 1, 7, 'uploads/eop/1777905034_Screenshot (1001).png', 'I am MKM', 'approved', 'nice this is mkm I am sure 100 percent.', '2026-05-04 22:30:34', '2026-05-04 22:31:34', 1),
(2, 1, 8, 'uploads/eop/1777907569_ChatGPT Image May 3, 2026, 12_08_13 AM.png', 'i am ckk', 'approved', 'mhn tl', '2026-05-04 23:12:49', '2026-05-04 23:13:30', 1),
(3, 1, 2, 'uploads/eop/1777907811_ML Indivi assignment (1).pdf', 'www', 'pending', NULL, '2026-05-04 23:16:51', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `point_transactions`
--

CREATE TABLE `point_transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `point_request_id` int(11) NOT NULL,
  `points` int(11) NOT NULL,
  `transaction_type` enum('award','adjustment','reversal') NOT NULL DEFAULT 'award',
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `point_transactions`
--

INSERT INTO `point_transactions` (`id`, `user_id`, `point_request_id`, `points`, `transaction_type`, `description`, `created_by`, `created_at`) VALUES
(1, 1, 1, 1000, 'award', 'Approved point request: mkm', 1, '2026-05-04 22:31:34'),
(2, 1, 2, 1000, 'award', 'Approved point request: web dev', 1, '2026-05-04 23:13:30');

-- --------------------------------------------------------

--
-- Table structure for table `post`
--

CREATE TABLE `post` (
  `id` int(11) NOT NULL,
  `content` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `title` varchar(200) DEFAULT NULL,
  `upload_date` datetime DEFAULT current_timestamp(),
  `edit_date` datetime DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `edited_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `post`
--

INSERT INTO `post` (`id`, `content`, `category`, `title`, `upload_date`, `edit_date`, `uploaded_by`, `edited_by`) VALUES
(1, 'AMSA fundraising initiatives help support student-led programs, welfare activities, and community projects. Campaign updates should include the purpose, collection method, timeline, and impact after completion.', 'community_engagement', 'AMSA Fundraising Initiative', '2026-04-18 18:28:26', '2026-04-18 18:37:10', 1, 1),
(2, 'Thingyan brings students together to share Myanmar culture, food, music, and fellowship within the AIU community. Event details should be updated by the committee when the program schedule is confirmed.', 'community_engagement', 'Thingyan Cultural Gathering', '2026-04-19 00:08:14', NULL, 1, NULL),
(3, 'AMSA recognizes students who represent the community with strong academic performance, leadership, and service. Achievement posts should highlight verified milestones and the contribution behind them.', 'achievement', 'Student Achievement Recognition', '2026-04-19 00:34:31', NULL, 1, NULL),
(4, 'AMSA creates a supportive space where Myanmar students can connect, contribute, and grow as part of the AIU community.', 'testimonial', 'AMSA Member Reflection', '2026-04-19 00:36:22', NULL, 1, NULL),
(5, 'The association encourages students to take part in events, volunteer work, and leadership opportunities throughout the academic year.', 'testimonial', 'Community Participation', '2026-04-19 00:55:34', NULL, 1, NULL),
(6, 'AMSA activities help students build confidence, teamwork, and a sense of belonging while studying at AIU.', 'testimonial', 'Student Experience', '2026-04-19 00:58:01', NULL, 1, NULL),
(8, 'AMSA news posts should be used for official association updates, announcements, and student community information.', 'news', 'AMSA News Update', '2026-05-04 22:07:51', NULL, 1, NULL),
(9, 'AMSA AIU shares timely updates about student activities, committee announcements, workshops, and opportunities for members to participate in the association.', 'news', 'Latest AMSA Update', '2026-05-04 22:14:54', NULL, 1, NULL),
(10, 'This announcement space is for verified AMSA notices such as event briefings, registration reminders, volunteer calls, and committee communications.', 'announcement', 'AMSA Announcement', '2026-05-04 22:26:13', '2026-05-04 22:39:42', 1, 1),
(11, 'Volunteer posts should describe the activity, responsibilities, schedule, and expected community impact for participating members.', 'volunteer', 'Volunteer Opportunity', '2026-05-04 23:25:16', NULL, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(150) NOT NULL,
  `role` enum('member','president','vice_president','secretary','male_treasurer','female_treasurer','system_admin') NOT NULL DEFAULT 'member',
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `profile_image` varchar(255) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `name`, `password`, `email`, `role`, `status`, `profile_image`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'Admin User', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@amsa.com', 'system_admin', 'active', NULL, '2026-06-03 22:35:53', '2026-05-04 12:00:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_points`
--

CREATE TABLE `user_points` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_points` int(11) DEFAULT 0,
  `last_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_points`
--

INSERT INTO `user_points` (`id`, `user_id`, `total_points`, `last_updated`) VALUES
(1, 1, 2000, '2026-05-04 23:13:30');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(100) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `contact_messages`
--

ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contact_messages_submission_date` (`submission_date`);

--
-- Indexes for table `image`
--
ALTER TABLE `image`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_post_image` (`post_id`);

--
-- Indexes for table `point_category`
--
ALTER TABLE `point_category`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `point_request`
--
ALTER TABLE `point_request`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_point_request_user` (`user_id`),
  ADD KEY `fk_point_request_category` (`point_category_id`),
  ADD KEY `fk_point_request_reviewer` (`reviewed_by`),
  ADD KEY `idx_point_request_status_date` (`status`,`request_date`),
  ADD KEY `idx_point_request_user_status` (`user_id`,`status`);

--
-- Indexes for table `point_transactions`
--

ALTER TABLE `point_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_point_request_transaction` (`point_request_id`),
  ADD KEY `fk_point_transactions_user` (`user_id`),
  ADD KEY `fk_point_transactions_created_by` (`created_by`),
  ADD KEY `idx_point_transactions_user_date` (`user_id`,`created_at`);

--
-- Indexes for table `post`
--
ALTER TABLE `post`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_uploaded_by` (`uploaded_by`),
  ADD KEY `fk_edited_by` (`edited_by`),
  ADD KEY `idx_post_category_date` (`category`,`upload_date`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_user_role_status` (`role`,`status`),
  ADD KEY `idx_user_role_status_created` (`role`,`status`,`created_at`);

--
-- Indexes for table `user_points`
--
ALTER TABLE `user_points`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user` (`user_id`),
  ADD KEY `idx_user_points_total` (`total_points`);

--
-- Indexes for table `audit_logs`
--

ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_audit_logs_user` (`user_id`),
  ADD KEY `idx_audit_logs_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_audit_logs_created_at` (`created_at`),
  ADD KEY `idx_audit_logs_action` (`action`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `contact_messages`
--

ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `image`
--
ALTER TABLE `image`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `point_category`
--
ALTER TABLE `point_category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `point_request`
--
ALTER TABLE `point_request`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `point_transactions`
--

ALTER TABLE `point_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `post`
--
ALTER TABLE `post`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_points`
--
ALTER TABLE `user_points`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `audit_logs`
--

ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `image`
--
ALTER TABLE `image`
  ADD CONSTRAINT `fk_post_image` FOREIGN KEY (`post_id`) REFERENCES `post` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `point_request`
--
ALTER TABLE `point_request`
  ADD CONSTRAINT `fk_point_request_category` FOREIGN KEY (`point_category_id`) REFERENCES `point_category` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_point_request_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_point_request_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `point_transactions`
--

ALTER TABLE `point_transactions`
  ADD CONSTRAINT `fk_point_transactions_created_by` FOREIGN KEY (`created_by`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_point_transactions_request` FOREIGN KEY (`point_request_id`) REFERENCES `point_request` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_point_transactions_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `post`
--
ALTER TABLE `post`
  ADD CONSTRAINT `fk_edited_by` FOREIGN KEY (`edited_by`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `user` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_points`
--
ALTER TABLE `user_points`
  ADD CONSTRAINT `fk_user_points_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_logs`
--

ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_logs_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
