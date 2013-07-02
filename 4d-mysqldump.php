#!/usr/bin/php
<?
/**
 * 4D Database Dump to MySQL
 * by Brad Erickson <eosrei@gmail.com> 2013
 *
 * Command line script to dump a 4D database to a compliant MySQL dump file.
 * The PDO_4D PHP extension is required: http://www.php.net/manual/en/ref.pdo-4d.php
 *
 * @todo Load h/u/p from ~/.4d.conf
 * @todo Add -t option to specify table names
 * @todo Implement Foreign Keys.
 * @todo Support Subtable relationships: http://doc.4d.com/4D-Language-Reference-12.4/Subrecords/Get-subrecord-key.301-977448.en.html
 * @see http://doc.4d.com/4D-SQL-Reference-12.1/Using-SQL-in-4D/Principles-for-integrating-4D-and-the-4D-SQL-engine.300-494388.en.html
 */

include_once "FourD.php";
include_once "FourDDump.php";

/**
 * Print command line help
 */
function help() {
  print '4d-mysqldump V1.0' . PHP_EOL;
  print 'Copyright 2013 Brad Erickson' . PHP_EOL;
  print '' . PHP_EOL;
  print 'Dumps structure and contents of 4D databases and tables to MySQL.' . PHP_EOL;
  print 'Usage: 4d-mysqldump -h hostname -u username -p password' . PHP_EOL;
  print '' . PHP_EOL;
}

// Get required command line arguments host, username, password.
$options = getopt("h:u:p:");

// Check host option
if (!isset($options['h'])) {
  echo "ERROR: host is required." . PHP_EOL . PHP_EOL;
  help();
  exit();
}

// Check username option
if (!isset($options['u'])) {
  echo "ERROR: username is required." . PHP_EOL . PHP_EOL;
  help();
  exit();
}

// Check password option
if (!isset($options['p'])) {
  echo "ERROR: password is required." . PHP_EOL . PHP_EOL;
  help();
  exit();
}


$fourd_dump = new FourDDump($options['h'], $options['u'], $options['p']);

//--
//-- Dumping data for table `actions`
//--
//
//LOCK TABLES `actions` WRITE;
///*!40000 ALTER TABLE `actions` DISABLE KEYS */;
//set autocommit=0;
//INSERT INTO `actions` VALUES ('comment_publish_action','comment','comment_publish_action','','Publish comment');
///*!40000 ALTER TABLE `actions` ENABLE KEYS */;
//UNLOCK TABLES;
//commit;

