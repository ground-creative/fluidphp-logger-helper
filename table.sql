/*Table structure for the logger helper component */

CREATE TABLE `log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `logtype` varchar(255) DEFAULT NULL,
  `message` varchar(255) DEFAULT NULL,
  `time_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data` text,
  `request_data` text,
  `ip_address` varchar(255) DEFAULT NULL,
  `user_agent` text,
  `domain` varchar(255) DEFAULT NULL,
  `request_uri` text,
  `referer` text,
  `method` varchar(255) DEFAULT NULL,
  `line` int(11) NOT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
);
