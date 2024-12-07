SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+06:00";

CREATE TABLE `plab_active_users` (
  `id` int(11) NOT NULL,
  `json_data` text DEFAULT NULL,
  `date_time` varchar(255) DEFAULT NULL,
  `is_updated` int(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `plab_holidays` (
  `id` int(11) NOT NULL,
  `type` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `date_time` varchar(255) DEFAULT NULL,
  `validity` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

ALTER TABLE `plab_active_users` ADD PRIMARY KEY (`id`);
ALTER TABLE `plab_active_users` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `plab_holidays` ADD PRIMARY KEY (`id`);
ALTER TABLE `plab_holidays` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;