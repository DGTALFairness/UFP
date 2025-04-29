-- Universal Fairness Protocol (UFP)
-- Copyright (C) 2024-2025 DgtalFairness
--
-- This project is dual-licensed under the following terms:
-- 
-- 1. GNU GENERAL PUBLIC LICENSE v3.0
--    You may copy, distribute, and modify this software under the terms of the GPLv3.
--    https://www.gnu.org/licenses/gpl-3.0.html
--
-- 2. COMMERCIAL LICENSE
--    For proprietary or commercial use, please visit:
--    https://universalfairnessprotocol.org/commercial-license
--
-- Project Author: DgtalFairness
-- Website: https://universalfairnessprotocol.com
-- Email: info@universalfairnessprotocol.com
--
-- GitHub: https://github.com/DGTALFairness
-- GitLab: https://gitlab.com/DGTALFairness
-- Codeberg: https://codeberg.org/DGTALFairness
-- SourceHut: https://sr.ht/~dgtalfairness/
--
-- This SQL file contains the required structure and optional seed data
-- for initializing UFP-compatible environments.


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


CREATE TABLE `settings_task_addon` (
  `name` varchar(100) NOT NULL,
  `value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `settings_task_addon` (`name`, `value`) VALUES
('auto_approve_tasks', '0'),
('max_pending_per_user', '3'),
('reset_approved_jobs', '1');

CREATE TABLE `task_addon_done` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `task_id` int(10) UNSIGNED NOT NULL,
  `round_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `submission_data` text DEFAULT NULL,
  `submitted_at` int(10) UNSIGNED NOT NULL,
  `approved_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `approved_by` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `task_addon_jobs` (
  `task_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `input_type` varchar(50) DEFAULT 'text',
  `input_name` varchar(100) DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `example_format` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `task_addon_jobs` (`task_id`, `title`, `description`, `input_type`, `input_name`, `instructions`, `example_format`, `is_active`, `created_at`) VALUES
(3, 'Visit Our Facebook Page', 'Follow or Facebook page and provide your username.', 'text', 'username', 'enter username', '', 1, 1743794225),
(4, 'Follow Us on Twitter', 'Follow our official Twitter account and enter your Twitter handle.', 'text', 'twitter_handle', 'Enter your Twitter @handle', '@yourhandle', 1, 1745600000),
(5, 'Join Our Discord Server', 'Join our community on Discord and provide your Discord username.', 'text', 'discord_username', 'Enter your Discord name with tag', 'Username#1234', 1, 1745600010),
(6, 'Comment on YouTube Video', 'Leave a comment on our latest YouTube video and paste the link to your comment.', 'url', 'youtube_comment', 'Paste the URL to your comment', 'https://www.youtube.com/watch?v=abc123&lc=xyz456', 1, 1745600020),
(7, 'Submit a Screenshot', 'Upload a screenshot showing your engagement on our app.', 'file', 'screenshot', 'Submit a clear image file (jpg, png, etc.)', '', 1, 1745600030);

CREATE TABLE `task_addon_missed` (
  `id` int(10) UNSIGNED NOT NULL,
  `task_id` int(10) UNSIGNED NOT NULL,
  `round_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `submission_data` text DEFAULT NULL,
  `submitted_at` int(10) UNSIGNED NOT NULL,
  `approved_at` int(11) UNSIGNED DEFAULT NULL,
  `approved_by` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `task_addon_pending` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `task_id` int(10) UNSIGNED NOT NULL,
  `round_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `submission_data` text DEFAULT NULL,
  `submitted_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


ALTER TABLE `settings_task_addon`
  ADD PRIMARY KEY (`name`);

ALTER TABLE `task_addon_done`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `task_addon_jobs`
  ADD PRIMARY KEY (`task_id`),
  ADD UNIQUE KEY `task_id` (`task_id`);

ALTER TABLE `task_addon_missed`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `task_addon_pending`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `task_addon_done`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26624;

ALTER TABLE `task_addon_jobs`
  MODIFY `task_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

ALTER TABLE `task_addon_missed`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `task_addon_pending`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
