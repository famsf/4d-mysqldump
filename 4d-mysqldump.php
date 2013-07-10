#!/usr/bin/php
<?
/**
 * 4D Database Dump to MySQL
 * Copyright 2013 Fine Arts Museums of San Francisco
 * by Brad Erickson <eosrei at gmail.com>
 *
 * Command line script to dump a 4D database to a compliant MySQL dump file.
 * The PDO_4D PHP extension is required: http://www.php.net/manual/en/ref.pdo-4d.php
 *
 * @todo Load h/u/p from ~/.4d.conf
 * @todo Add -t option to specify tables to export
 * @todo Implement Foreign Keys.
 * @todo Check 4D bool values, and flip as needed
 * @todo Correct all notices to status report comments.
 * @todo Support automatic Subtable relationships if possible.
 * @see http://doc.4d.com/4D-Language-Reference-12.4/Subrecords/Get-subrecord-key.301-977448.en.html
 * @see http://doc.4d.com/4D-SQL-Reference-12.1/Using-SQL-in-4D/Principles-for-integrating-4D-and-the-4D-SQL-engine.300-494388.en.html
 */

include_once "FourD.php";
include_once "FourDDump.php";

/**
 * Print command line help
 */
function help() {
  print '4d-mysqldump V1.0' . PHP_EOL;
  print 'Copyright 2013 Fine Arts Museums of San Francisco. Created by Brad Erickson.' . PHP_EOL;
  print '' . PHP_EOL;
  print 'Dumps structure and contents of 4D databases and tables to MySQL.' . PHP_EOL;
  print 'Usage: 4d-mysqldump -hHostname -uUsername -pPassword [-rRetries] [-tTableName] [-l]' . PHP_EOL;
  // @todo: Document options
  print '' . PHP_EOL;
}

// Get required command line arguments host, username, password.
$options = getopt("h:u:p:r::lt::s::");

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

// Retry connection # of times. -r (excludes intitial attempt)
$retries = 3;
if (isset($options['r'])) {
  $retries = $options['r'];
}

// List all tables in database
$list_tables = isset($options['l']) ? TRUE : FALSE;

// Specify table to dump -t (Case must match exactly!)
$select_table = NULL;
if (isset($options['t'])) {
  $select_table = $options['t'];
}

// @todo: Run SQL query -s
// @todo: Limit/Offset
// @todo: Test database, look for problems. Change -t?

$fourd_dump = new FourDDump($options['h'], $options['u'], $options['p'], $retries, $select_table, $list_tables);

//Done!