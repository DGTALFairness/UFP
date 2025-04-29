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


CREATE TABLE `settings_lottery_addon` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings_lottery_addon` (`id`, `name`, `value`) VALUES
(11, 'ticket_price', '20'),
(12, 'ticket_fee', '0.3');


ALTER TABLE `settings_lottery_addon`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);


ALTER TABLE `settings_lottery_addon`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
