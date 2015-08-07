CREATE TABLE IF NOT EXISTS `sessions` (
  `rownum` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `auth` bigint(20) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

ALTER TABLE `sessions`
  ADD PRIMARY KEY (`rownum`);

ALTER TABLE `sessions`
  MODIFY `rownum` int(11) NOT NULL AUTO_INCREMENT;
