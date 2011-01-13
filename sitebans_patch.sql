
# Dump of table sitebans
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sitebans`;

CREATE TABLE `sitebans` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` varchar(20) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `playerid` int(11) DEFAULT NULL,
  `ip_mask` varchar(50),
  `reason` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

