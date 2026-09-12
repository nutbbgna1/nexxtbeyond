CREATE TABLE IF NOT EXISTS `calculator_tracks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `icon` varchar(10) NOT NULL,
  `description` text NOT NULL,
  `weights` json NOT NULL,
  `min_score` decimal(5,2) NOT NULL DEFAULT '58.00',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `calculator_tracks` (`id`, `name`, `icon`, `description`, `weights`, `min_score`, `is_active`, `sort_order`) VALUES
(1, 'แพทยศาสตร์ / ทันตแพทยศาสตร์', '🩺', 'กสพท - TPAT1 30% + A-Level 70%', '{"tpat":0.3,"math1":0.2,"physics":0.2,"english":0.3}', 68.00, 1, 1),
(2, 'วิศวกรรมศาสตร์', '⚙️', 'TGAT 20% + TPAT3 30% + A-Level คณิต/ฟิสิกส์ 50%', '{"tgat":0.2,"tpat":0.3,"math1":0.25,"physics":0.25}', 58.00, 1, 2),
(3, 'บริหารธุรกิจ / บัญชี', '📊', 'TGAT 30% + A-Level คณิต 40% + อังกฤษ 30%', '{"tgat":0.3,"math1":0.4,"english":0.3}', 58.00, 1, 3),
(4, 'นิติศาสตร์ / รัฐศาสตร์', '⚖️', 'TGAT 40% + A-Level สังคม 20% + ไทย 20% + อังกฤษ 20%', '{"tgat":0.4,"social":0.2,"thai":0.2,"english":0.2}', 58.00, 1, 4)
ON DUPLICATE KEY UPDATE name=VALUES(name);
