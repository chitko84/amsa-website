-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 02, 2026 at 07:08 AM
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
-- Database: `amsa_website`
--

-- --------------------------------------------------------

--
-- Table structure for table `image`
--

CREATE TABLE `image` (
  `id` int(11) NOT NULL,
  `post_id` int(11) DEFAULT NULL,
  `img_name` varchar(255) DEFAULT NULL
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
(7, 6, '1776531481_EcoSip_ Sustainable Cup Innovation (1).png');

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
(1, 'Come to the events Come to the events Come to the events Come to the events Come to the events Come to the events Come to the events Come to the events Come to the events Come to the events Come to the events Come to the events Come to the events Come to the events Come to the events Come to the events Come to the events Come to the events Come to the events Come to the events Come to the events Come to the events Come to the events Come to the events Come to the events Come to the events Come to the events Come to the events Come to the events Come to the events Come to the events Come to the events Come to the events Come to the events Come to the events Come to the events Come to the events.', 'community_engagement', 'Amsa Fundraising', '2026-04-18 18:28:26', '2026-04-18 18:37:10', 1, 1),
(2, 'nay kgg lr nay kgg lr vvvnay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr nay kgg lr', 'community_engagement', 'Thingyan', '2026-04-19 00:08:14', NULL, 1, NULL),
(3, 'The first Myanmar student who got that award in 2024-2025. The first Myanmar student who got that award in 2024-2025.The first Myanmar student who got that award in 2024-2025.The first Myanmar student who got that award in 2024-2025.The first Myanmar student who got that award in 2024-2025.The first Myanmar student who got that award in 2024-2025.The first Myanmar student who got that award in 2024-2025.The first Myanmar student who got that award in 2024-2025.The first Myanmar student who got that award in 2024-2025.The first Myanmar student who got that award in 2024-2025.The first Myanmar student who got that award in 2024-2025.', 'achievement', 'Chancellor Award Recipient', '2026-04-19 00:34:31', NULL, 1, NULL),
(4, 'Pyae Sone Kyaw is handsome. Pyae Sone Kyaw is handsome. Pyae Sone Kyaw is handsome. Pyae Sone Kyaw is handsome. Pyae Sone Kyaw is handsome. Pyae Sone Kyaw is handsome. Pyae Sone Kyaw is handsome. Pyae Sone Kyaw is handsome. Pyae Sone Kyaw is handsome. Pyae Sone Kyaw is handsome. Pyae Sone Kyaw is handsome. Pyae Sone Kyaw is handsome. Pyae Sone Kyaw is handsome. Pyae Sone Kyaw is handsome. Pyae Sone Kyaw is handsome. Pyae Sone Kyaw is handsome. Pyae Sone Kyaw is handsome.', 'testimonial', 'Pyae Sone Kyaw', '2026-04-19 00:36:22', NULL, 1, NULL),
(5, 'Nay Naing is handsome. Nay Naing is handsome. Nay Naing is handsome. Nay Naing is handsome. Nay Naing is handsome. Nay Naing is handsome. Nay Naing is handsome. Nay Naing is handsome. Nay Naing is handsome. Nay Naing is handsome. Nay Naing is handsome. Nay Naing is handsome. Nay Naing is handsome. Nay Naing is handsome. Nay Naing is handsome. Nay Naing is handsome. Nay Naing is handsome. Nay Naing is handsome. Nay Naing is handsome. Nay Naing is handsome. Nay Naing is handsome. Nay Naing is handsome. Nay Naing is handsome. Nay Naing is handsome. Nay Naing is handsome. Nay Naing is handsome. Nay Naing is handsome. Nay Naing is handsome. Nay Naing is handsome. Nay Naing is handsome. Nay Naing is handsome.', 'testimonial', 'Nyi Nyi Nay Naing', '2026-04-19 00:55:34', NULL, 1, NULL),
(6, 'Thain Lar Min Min is beautiful. Thain Lar Min Min is beautiful. Thain Lar Min Min is beautiful. Thain Lar Min Min is beautiful. Thain Lar Min Min is beautiful. Thain Lar Min Min is beautiful. Thain Lar Min Min is beautiful. Thain Lar Min Min is beautiful. Thain Lar Min Min is beautiful. Thain Lar Min Min is beautiful. Thain Lar Min Min is beautiful. Thain Lar Min Min is beautiful. Thain Lar Min Min is beautiful. Thain Lar Min Min is beautiful. Thain Lar Min Min is beautiful. Thain Lar Min Min is beautiful. Thain Lar Min Min is beautiful. Thain Lar Min Min is beautiful. Thain Lar Min Min is beautiful. Thain Lar Min Min is beautiful. Thain Lar Min Min is beautiful. Thain Lar Min Min is beautiful. Thain Lar Min Min is beautiful. Thain Lar Min Min is beautiful. Thain Lar Min Min is beautiful. Thain Lar Min Min is beautiful.', 'testimonial', 'Thain Lar Min Min', '2026-04-19 00:58:01', NULL, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(150) NOT NULL,
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `name`, `password`, `email`, `last_login`) VALUES
(1, 'Admin User', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@amsa.com', '2026-04-19 00:10:51');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `image`
--
ALTER TABLE `image`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_post_image` (`post_id`);

--
-- Indexes for table `post`
--
ALTER TABLE `post`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_uploaded_by` (`uploaded_by`),
  ADD KEY `fk_edited_by` (`edited_by`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `image`
--
ALTER TABLE `image`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `post`
--
ALTER TABLE `post`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `image`
--
ALTER TABLE `image`
  ADD CONSTRAINT `fk_post_image` FOREIGN KEY (`post_id`) REFERENCES `post` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `post`
--
ALTER TABLE `post`
  ADD CONSTRAINT `fk_edited_by` FOREIGN KEY (`edited_by`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `user` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
-- Add point system tables to amsa_website database

-- Table for point categories
CREATE TABLE `point_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `points` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table for point requests
CREATE TABLE `point_request` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `point_category_id` int(11) NOT NULL,
  `eop_evidence` varchar(255) NOT NULL COMMENT 'End of Participation evidence file path',
  `description` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_remarks` text DEFAULT NULL,
  `request_date` datetime DEFAULT current_timestamp(),
  `review_date` datetime DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_point_request_user` (`user_id`),
  KEY `fk_point_request_category` (`point_category_id`),
  KEY `fk_point_request_reviewer` (`reviewed_by`),
  CONSTRAINT `fk_point_request_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_point_request_category` FOREIGN KEY (`point_category_id`) REFERENCES `point_category` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_point_request_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table to track user points (total points per user)
CREATE TABLE `user_points` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `total_points` int(11) DEFAULT 0,
  `last_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user` (`user_id`),
  CONSTRAINT `fk_user_points_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert some sample point categories
INSERT INTO `point_category` (`category_name`, `points`, `description`, `status`) VALUES
('Community Event Participation', 10, 'Participate in any AMSA community event', 'active'),
('Fundraising Campaign', 20, 'Contribute to fundraising activities', 'active'),
('Volunteer Work', 15, 'Volunteer for AMSA projects', 'active'),
('Event Organization', 30, 'Organize or coordinate AMSA events', 'active'),
('Workshop Attendance', 5, 'Attend AMSA workshops or seminars', 'active'),
('Leadership Role', 50, 'Serve in a leadership position', 'active');

-- Initialize user_points for existing users
INSERT INTO `user_points` (`user_id`, `total_points`)
SELECT id, 0 FROM `user` WHERE id NOT IN (SELECT user_id FROM user_points);

COMMIT;