#!/usr/bin/php
<?
/**
 * 4d-mysqldump: 4D Database Dump to MySQL
 * Copyright (C) 2013 Fine Arts Museums of San Francisco
 * Authored by Brad Erickson <eosrei at gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * Command line script to dump a 4D database to a compliant MySQL dump file.
 * The PDO_4D PHP extension is required: http://www.php.net/manual/en/ref.pdo-4d.php
 *
 * @todo Load h/u/p from ~/.4d.conf
 * @todo Implement Foreign Keys.
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
  print PHP_EOL;
  print 'Dumps structure and contents of 4D databases and tables to MySQL.' . PHP_EOL;
  print 'Usage: 4d-mysqldump -hHostname -uUsername -pPassword [-rRetries] [-tTableName] [-l]' . PHP_EOL;
  print PHP_EOL;
  print 'Options:' . PHP_EOL;
  print '  -h    Hostname' . PHP_EOL;
  print '  -u    Username' . PHP_EOL;
  print '  -p    Password' . PHP_EOL;
  print '  -r    Number of connection attempt tries (default 3)' . PHP_EOL;
  print '  -t    Specific table to dump (used internally)' . PHP_EOL;
  print '  -l    List all tables and exit' . PHP_EOL;

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
// @todo: Test database, look for problems. Change -t and use -i for that.

$fourd_dump = new FourDDump($options['h'], $options['u'], $options['p'], $retries, $select_table, $list_tables);

//Done!