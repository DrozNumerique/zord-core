CREATE TABLE `users` (
  `login` varchar(191) NOT NULL,
  `password` varchar(256) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `ipv4` varchar(2048) DEFAULT NULL,
  `ipv6` varchar(2048) DEFAULT NULL,
  `activate` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`login`)
) ENGINE = INNODB;

INSERT INTO `users` (`login`) VALUES ('admin');

CREATE TABLE `user_has_address` (
  `user` varchar(191) NOT NULL,
  `ip` int(10) UNSIGNED NOT NULL,
  `mask` tinyint(4) DEFAULT '32',
  `include` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`user`,`ip`,`mask`,`include`)
) ENGINE = INNODB;

INSERT INTO `user_has_address` (`user`, `ip`) VALUES ('admin', INET_ATON('127.0.0.1'));

CREATE TABLE `user_has_role` (
  `user` varchar(191) NOT NULL,
  `role` varchar(191) NOT NULL,
  `context` varchar(191) NOT NULL,
  `start` date DEFAULT '1970-01-01',
  `end` date DEFAULT '2038-01-19',
  PRIMARY KEY (`user`,`role`,`context`)
) ENGINE = INNODB;

INSERT INTO `user_has_role` (`user`, `role`, `context`) VALUES ('admin', '*', '*');

CREATE TABLE `user_has_session` (
  `user` varchar(255) NOT NULL,
  `session` varchar(191) NOT NULL,
  `last` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`session`)
) ENGINE = INNODB;

CREATE TABLE `process` (
  `pid` varchar(191) NOT NULL,
  `class` varchar(255) NOT NULL,
  `user` varchar(255) NOT NULL DEFAULT 'admin',
  `lang` varchar(10) NOT NULL DEFAULT 'fr-FR',
  `params` text DEFAULT NULL,
  `start` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `step` varchar(255) NOT NULL DEFAULT 'init',
  `progress` int(3) NOT NULL DEFAULT 0,
  PRIMARY KEY (`pid`)
) ENGINE = INNODB;

