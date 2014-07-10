DROP TABLE IF EXISTS `#__userlogin_tracking`;

CREATE TABLE IF NOT EXISTS `#__userlogin_tracking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `username` varchar(40) NOT NULL,
  `ip` varchar(20) NOT NULL,
  `realtime` varchar(40) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
