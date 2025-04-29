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


CREATE TABLE `active_addons` (
  `addon_name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT INTO `active_addons` (`addon_name`, `is_active`) VALUES
('checkin_sim', 1),
('lottery_sim', 1),
('task_sim', 1);

CREATE TABLE `completed_round_tickets` (
  `ticket_id` int(10) UNSIGNED NOT NULL,
  `round_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `date` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `cron_timestamps` (
  `id` int(10) UNSIGNED NOT NULL,
  `task_name` varchar(50) NOT NULL,
  `last_run` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT INTO `cron_timestamps` (`id`, `task_name`, `last_run`) VALUES
(1, '5_min', 1745444163),
(2, '15_min', 1745444283),
(3, 'hourly', 1745442182),
(4, 'daily', 1745358304),
(5, '1_minute_cron', 1745444343),
(6, 'weekly', 1744916883);

CREATE TABLE `current_round_tickets` (
  `ticket_id` int(10) UNSIGNED NOT NULL,
  `round_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `date` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `fairness_ticket_logs` (
  `round_id` int(10) UNSIGNED NOT NULL,
  `ticket_list` text NOT NULL,
  `hash` varchar(64) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `rehash_chain` text DEFAULT NULL,
  `segment_chain` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`segment_chain`)),
  `segment_chain_updated` tinyint(1) DEFAULT 0,
  `segment_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE `rounds` (
  `id` int(11) UNSIGNED NOT NULL,
  `round_id` int(11) UNSIGNED NOT NULL,
  `position` tinyint(2) UNSIGNED NOT NULL DEFAULT 1,
  `date` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `expected_end_date` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `entry_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `end_date` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `prize` varchar(255) NOT NULL DEFAULT '0',
  `reward_received` varchar(255) NOT NULL DEFAULT '0',
  `tickets_purchased` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `total_checkins` int(11) DEFAULT 0,
  `total_tasks` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `winner_id` int(11) UNSIGNED DEFAULT NULL,
  `winner_tickets` int(11) UNSIGNED DEFAULT 0,
  `winning_ticket` int(11) UNSIGNED DEFAULT 0,
  `winning_segment` varchar(10) DEFAULT NULL,
  `hash_used_for_segment` text DEFAULT NULL,
  `hash_tier_for_segment` int(11) DEFAULT NULL,
  `total_winners` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_users` int(10) UNSIGNED DEFAULT 0,
  `ticket_hash` varchar(64) DEFAULT NULL,
  `rehash_count` int(10) UNSIGNED DEFAULT 0,
  `rehash_chain` text DEFAULT NULL,
  `closed` smallint(1) UNSIGNED NOT NULL DEFAULT 0,
  `saved` tinyint(1) NOT NULL DEFAULT 0,
  `use_custom_rewards` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `reward_label` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

INSERT INTO `rounds` (`id`, `round_id`, `position`, `date`, `expected_end_date`, `entry_price`, `end_date`, `prize`, `reward_received`, `tickets_purchased`, `total_checkins`, `total_tasks`, `winner_id`, `winner_tickets`, `winning_ticket`, `winning_segment`, `hash_used_for_segment`, `hash_tier_for_segment`, `total_winners`, `total_users`, `ticket_hash`, `rehash_count`, `rehash_chain`, `closed`, `saved`, `use_custom_rewards`, `reward_label`) VALUES
(1, 1, 1, 1745444345, 1745444935, 20.00, 0, 'iPad', '0', 0, 0, 0, NULL, 0, 0, NULL, NULL, NULL, 0, 0, NULL, 0, NULL, 0, 0, 1, 'iPad'),
(2, 1, 2, 1745444345, 1745444935, 20.00, 0, 'Cell Phone', '0', 0, 0, 0, NULL, 0, 0, NULL, NULL, NULL, 0, 0, NULL, 0, NULL, 0, 0, 1, 'Cell Phone'),
(3, 1, 3, 1745444345, 1745444935, 20.00, 0, 'Gift Card', '0', 0, 0, 0, NULL, 0, 0, NULL, NULL, NULL, 0, 0, NULL, 0, NULL, 0, 0, 1, 'Gift Card'),
(4, 1, 4, 1745444345, 1745444935, 20.00, 0, 'wssw', '0', 0, 0, 0, NULL, 0, 0, NULL, NULL, NULL, 0, 0, NULL, 0, NULL, 0, 0, 1, 'wssw'),
(5, 1, 5, 1745444345, 1745444935, 20.00, 0, 'sw', '0', 0, 0, 0, NULL, 0, 0, NULL, NULL, NULL, 0, 0, NULL, 0, NULL, 0, 0, 1, 'sw'),
(6, 1, 6, 1745444345, 1745444935, 20.00, 0, 'swsw', '0', 0, 0, 0, NULL, 0, 0, NULL, NULL, NULL, 0, 0, NULL, 0, NULL, 0, 0, 1, 'swsw'),
(7, 1, 7, 1745444345, 1745444935, 20.00, 0, 'ssw', '0', 0, 0, 0, NULL, 0, 0, NULL, NULL, NULL, 0, 0, NULL, 0, NULL, 0, 0, 1, 'ssw'),
(8, 1, 8, 1745444345, 1745444935, 20.00, 0, 'sw', '0', 0, 0, 0, NULL, 0, 0, NULL, NULL, NULL, 0, 0, NULL, 0, NULL, 0, 0, 1, 'sw'),
(9, 1, 9, 1745444345, 1745444935, 20.00, 0, 'sw', '0', 0, 0, 0, NULL, 0, 0, NULL, NULL, NULL, 0, 0, NULL, 0, NULL, 0, 0, 1, 'sw'),
(10, 1, 10, 1745444345, 1745444935, 20.00, 0, 'sw', '0', 0, 0, 0, NULL, 0, 0, NULL, NULL, NULL, 0, 0, NULL, 0, NULL, 0, 0, 1, 'sw');

CREATE TABLE `rounds_archive` (
  `id` int(11) UNSIGNED NOT NULL,
  `round_id` int(11) UNSIGNED NOT NULL,
  `position` tinyint(2) UNSIGNED NOT NULL DEFAULT 1,
  `date` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `expected_end_date` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `end_date` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `prize` decimal(32,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `reward_received` decimal(32,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `tickets_purchased` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `winner_id` int(11) UNSIGNED DEFAULT NULL,
  `winner_tickets` int(11) UNSIGNED DEFAULT 0,
  `winning_ticket` int(11) UNSIGNED DEFAULT 0,
  `winning_segment` varchar(10) DEFAULT NULL,
  `total_winners` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_users` int(10) UNSIGNED DEFAULT 0,
  `ticket_hash` varchar(64) DEFAULT NULL,
  `rehash_count` int(10) UNSIGNED DEFAULT 0,
  `rehash_chain` text DEFAULT NULL,
  `closed` smallint(1) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT INTO `settings` (`id`, `name`, `value`) VALUES
(2, 'round_duration', '10'),
(3, 'starting_prize', '500'),
(4, 'round_duration_type', 'minutes'),
(5, 'number_of_winners', '10'),
(6, 'payouts', '[40,30,20,6,2,0.9,0.5,0.3,0.2,0.1]'),
(10, 'reward_prefix', '$'),
(14, 'reward_suffix', 'Points'),
(16, 'prefix_or_suffix', '2'),
(17, 'ticket_middle_text', 'checked-in-ticket-id'),
(18, 'use_custom_rewards', '1'),
(19, 'reward_labels', '[\"iPad\",\"Cell Phone\",\"Gift Card\",\"Wireless Headphones\",\"Bluetooth Speaker\",\"Power Bank\",\"T-Shirt\",\"Sticker Pack\",\"Keychain\",\"Thank You Note\"]\n'),
(20, 'reward_number_of_winners', '10');

CREATE TABLE `users` (
  `id` int(11) UNSIGNED NOT NULL,
  `username` varchar(32) NOT NULL,
  `email` varchar(128) NOT NULL,
  `password` varchar(64) NOT NULL,
  `account_balance` decimal(12,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `today_revenue` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_revenue` decimal(10,2) NOT NULL DEFAULT 0.00,
  `reg_time` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `last_activity` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `users` (`id`, `username`, `email`, `password`, `account_balance`, `today_revenue`, `total_revenue`, `reg_time`, `last_activity`, `is_admin`) VALUES
(1, 'admin', 'admin@universalfairnessprotocol.com', '$2y$10$svZatBCdLYmaj.YcceJRvuctInOsSw2uAVdFRrE1tc3h/m6zJtI2m', 171630.62, 357065.82, 357065.82, 1741639886, 1745372019, 1),
(2, 'TestUser1', 'testuser1@universalfairnessprotocol.com', '$2y$10$svZatBCdLYmaj.YcceJRvuctInOsSw2uAVdFRrE1tc3h/m6zJtI2m', 2556828.22, 1943426.98, 1943426.98, 1741639886, 1745443322, 0),
(3, 'TestUser2', 'testuser2@universalfairnessprotocol.com', '$2y$10$svZatBCdLYmaj.YcceJRvuctInOsSw2uAVdFRrE1tc3h/m6zJtI2m', 2405941.14, 1792825.67, 1792825.67, 1741639886, 1745443562, 0),
(4, 'TestUser3', 'testuser3@universalfairnessprotocol.com', '$2y$10$svZatBCdLYmaj.YcceJRvuctInOsSw2uAVdFRrE1tc3h/m6zJtI2m', 2449384.88, 1835904.72, 1835904.72, 1741639886, 1745444042, 0),
(5, 'TestUser4', 'testuser4@universalfairnessprotocol.com', '$2y$10$svZatBCdLYmaj.YcceJRvuctInOsSw2uAVdFRrE1tc3h/m6zJtI2m', 2447334.61, 1833094.32, 1833094.32, 1741639886, 1745443923, 0),
(6, 'TestUser5', 'testuser5@universalfairnessprotocol.com', '$2y$10$svZatBCdLYmaj.YcceJRvuctInOsSw2uAVdFRrE1tc3h/m6zJtI2m', 2440777.84, 1823781.25, 1823781.25, 1741639886, 1745443923, 0),
(7, 'TestUser6', 'testuser6@universalfairnessprotocol.com', '$2y$10$svZatBCdLYmaj.YcceJRvuctInOsSw2uAVdFRrE1tc3h/m6zJtI2m', 2482077.48, 1860035.03, 1860035.03, 1741639886, 1745443682, 0),
(8, 'TestUser7', 'testuser7@universalfairnessprotocol.com', '$2y$10$svZatBCdLYmaj.YcceJRvuctInOsSw2uAVdFRrE1tc3h/m6zJtI2m', 2448966.40, 1837577.48, 1837577.48, 1741639886, 1745444282, 0),
(9, 'TestUser8', 'testuser8@universalfairnessprotocol.com', '$2y$10$svZatBCdLYmaj.YcceJRvuctInOsSw2uAVdFRrE1tc3h/m6zJtI2m', 2511711.72, 1903320.56, 1903320.56, 1741639886, 1745443082, 0),
(10, 'TestUser9', 'testuser9@universalfairnessprotocol.com', '$2y$10$svZatBCdLYmaj.YcceJRvuctInOsSw2uAVdFRrE1tc3h/m6zJtI2m', 2470585.66, 1863951.35, 1863951.35, 1741639886, 1745443682, 0),
(11, 'TestUser10', 'testuser10@universalfairnessprotocol.com', '$2y$10$svZatBCdLYmaj.YcceJRvuctInOsSw2uAVdFRrE1tc3h/m6zJtI2m', 2541256.96, 1924189.50, 1924189.50, 1741639886, 1745444282, 0),
(12, 'TestUser11', 'testuser11@universalfairnessprotocol.com', '$2y$10$svZatBCdLYmaj.YcceJRvuctInOsSw2uAVdFRrE1tc3h/m6zJtI2m', 2505106.50, 1892161.50, 1892161.50, 1741639886, 1745443923, 0),
(13, 'TestUser12', 'testuser12@universalfairnessprotocol.com', '$2y$10$svZatBCdLYmaj.YcceJRvuctInOsSw2uAVdFRrE1tc3h/m6zJtI2m', 2523223.66, 1896984.66, 1896984.66, 1741639886, 1745444282, 0),
(14, 'TestUser13', 'testuser13@universalfairnessprotocol.com', '$2y$10$svZatBCdLYmaj.YcceJRvuctInOsSw2uAVdFRrE1tc3h/m6zJtI2m', 2471375.12, 1843066.12, 1843066.12, 1741639886, 1745442723, 0),
(15, 'TestUser14', 'testuser14@universalfairnessprotocol.com', '$2y$10$svZatBCdLYmaj.YcceJRvuctInOsSw2uAVdFRrE1tc3h/m6zJtI2m', 2475996.73, 1860584.73, 1860584.73, 1741639886, 1745442963, 0),
(16, 'TestUser15', 'testuser15@universalfairnessprotocol.com', '$2y$10$svZatBCdLYmaj.YcceJRvuctInOsSw2uAVdFRrE1tc3h/m6zJtI2m', 2487269.59, 1870667.59, 1870667.59, 1741639886, 1745444282, 0),
(17, 'TestUser16', 'testuser16@universalfairnessprotocol.com', '$2y$10$svZatBCdLYmaj.YcceJRvuctInOsSw2uAVdFRrE1tc3h/m6zJtI2m', 2465501.81, 1845038.81, 1845038.81, 1741639886, 1745444282, 0),
(18, 'TestUser17', 'testuser17@universalfairnessprotocol.com', '$2y$10$svZatBCdLYmaj.YcceJRvuctInOsSw2uAVdFRrE1tc3h/m6zJtI2m', 2434459.44, 1812612.44, 1812612.44, 1741639886, 1745444042, 0),
(19, 'TestUser18', 'testuser18@universalfairnessprotocol.com', '$2y$10$svZatBCdLYmaj.YcceJRvuctInOsSw2uAVdFRrE1tc3h/m6zJtI2m', 2473897.88, 1856090.88, 1856090.88, 1741639886, 1745443923, 0),
(20, 'TestUser19', 'testuser19@universalfairnessprotocol.com', '$2y$10$svZatBCdLYmaj.YcceJRvuctInOsSw2uAVdFRrE1tc3h/m6zJtI2m', 2481863.01, 1862360.01, 1862360.01, 1741639886, 1745443805, 0);

CREATE TABLE `winning_round_tickets` (
  `id` int(11) UNSIGNED NOT NULL,
  `ticket_id` int(11) UNSIGNED NOT NULL,
  `round_id` int(11) NOT NULL,
  `position` tinyint(2) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `segment` varchar(10) NOT NULL,
  `hash_tier` int(11) NOT NULL,
  `hash` varchar(64) NOT NULL,
  `reward_amount` decimal(10,2) NOT NULL,
  `reward_label` varchar(255) DEFAULT NULL,
  `entry_price` decimal(10,2) NOT NULL,
  `date` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;


ALTER TABLE `active_addons`
  ADD PRIMARY KEY (`addon_name`);

ALTER TABLE `completed_round_tickets`
  ADD PRIMARY KEY (`ticket_id`),
  ADD KEY `idx_round_id` (`round_id`),
  ADD KEY `idx_user_id` (`user_id`);

ALTER TABLE `cron_timestamps`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `task_name` (`task_name`);

ALTER TABLE `current_round_tickets`
  ADD PRIMARY KEY (`ticket_id`),
  ADD KEY `idx_round_id` (`round_id`),
  ADD KEY `idx_user_id` (`user_id`);

ALTER TABLE `fairness_ticket_logs`
  ADD PRIMARY KEY (`round_id`);

ALTER TABLE `rounds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `closed` (`closed`),
  ADD KEY `winner_id` (`winner_id`);

ALTER TABLE `rounds_archive`
  ADD PRIMARY KEY (`id`),
  ADD KEY `closed` (`closed`),
  ADD KEY `winner_id` (`winner_id`);

ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reg_time` (`reg_time`);

ALTER TABLE `winning_round_tickets`
  ADD PRIMARY KEY (`segment`),
  ADD KEY `idx_ticket_id` (`ticket_id`),
  ADD KEY `idx_round_id` (`round_id`),
  ADD KEY `idx_user_id` (`user_id`);


ALTER TABLE `cron_timestamps`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

ALTER TABLE `current_round_tickets`
  MODIFY `ticket_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rounds`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

ALTER TABLE `rounds_archive`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

ALTER TABLE `users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
