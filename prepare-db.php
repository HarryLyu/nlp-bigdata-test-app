<?php
echo 'Clear DB for new data' . PHP_EOL;
$dbConn->executeSql('DROP TABLE IF EXISTS groups;');
$dbConn->executeSql('CREATE TABLE groups (
  id int(11) NOT NULL AUTO_INCREMENT,
  vk_id int(11) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY vk_id (vk_id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;');

$dbConn->executeSql('DROP TABLE IF EXISTS members;');
$dbConn->executeSql('CREATE TABLE members (
  id int(11) NOT NULL AUTO_INCREMENT,
  vk_id int(11) NOT NULL,
  sex tinyint(4) DEFAULT NULL,
  birth_date date DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY vk_id (vk_id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;');