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
 * @see http://doc.4d.com/4D-Language-Reference-12.4/Subrecords/Get-subrecord-key.301-977448.en.html
 * @see http://doc.4d.com/4D-SQL-Reference-12.1/Using-SQL-in-4D/Principles-for-integrating-4D-and-the-4D-SQL-engine.300-494388.en.html
 */

include_once "FourD.php";
include_once "FourDDump.php";


/**
 * Print command line help
 */
function help() {
  print<<<EOD
4d-mysqldump V1.0
Copyright 2013 Fine Arts Museums of San Francisco.
Dumps structure and contents of 4D databases and tables to MySQL.

Usage: 4d-mysqldump.php [OPTIONS]
  -H, --help          Display this help and exit.
  -h, --host=name     4D server hostname or IP (required.)
  -u, --user=name     4D username (required.)
  -p, --password=password
                      4D password (required.)
  -r, --retries=count Number of connection attempt retries (default 3, for a
                      total of 4.)
  -l, --list          List all tables and exit.
  -t, --table=name    Dumps a specific table instead of all tables.
  -b, --ignore-binary Ignore picture/blob columns. They contain binary data
                      which may significantly increase the export size, but may
                      not be needed.
  -s, --skip-structure
                      Only print data, don't print table structure.
  -o, --offset=count  The offset to use during the export.
  -c, --limit=count   The limit to use during the export.
  -w, --allow-spaces  Don't removed spaces from column names.

EOD;
  exit();
}

function print_error($msg) {
  fwrite(STDERR, $msg . PHP_EOL);
}

// Create an array of command line options
$options = array(
  array(
    'short'    => 'H',
    'long'     => 'help',
    'bool'     => TRUE,
  ),
  array(
    'short'    => 'h',
    'long'     => 'host',
    'bool'     => FALSE,
  ),
  array(
    'short'    => 'u',
    'long'     => 'user',
    'bool'     => FALSE,
  ),
  array(
    'short'    => 'p',
    'long'     => 'password',
    'bool'     => FALSE,
  ),
  array(
    'short'    => 'r',
    'long'     => 'retries',
    'bool'     => FALSE,
  ),
  array(
    'short'    => 'i',
    'long'     => 'test',
    'bool'     => TRUE,
  ),
  array(
    'short'    => 't',
    'long'     => 'table',
    'bool'     => FALSE,
  ),
  array(
    'short'    => 'b',
    'long'     => 'ignore-binary',
    'bool'     => TRUE,
  ),
  array(
    'short'    => 's',
    'long'     => 'skip-structure',
    'bool'     => TRUE,
  ),
  array(
    'short'    => 'o',
    'long'     => 'offset',
    'bool'     => FALSE,
  ),
  array(
    'short'    => 'c',
    'long'     => 'limit',
    'bool'     => FALSE,
  ),
  array(
    'short'    => 'l',
    'long'     => 'list',
    'bool'     => TRUE,
  ),
  array(
    'short'    => 'w',
    'long'     => 'allow-spaces',
    'bool'     => TRUE,
  ),
  // Internal use only to manage threads to avoid excessive memory use.
  array(
    'short'    => 'z',
    'long'     => 'internal-thread',
    'bool'     => TRUE,
  ),
);

$opts = parseopt($options);

// Check for help
if ($opts['help']) {
  help();
}

// Check required options
if (!$opts['host']) {
  print_error('ERROR: 4D hostname is required.');
  help();
}
if (!$opts['user']) {
  print_error('ERROR: 4D username is required.');
  help();
}
// Technically it *should* be required, but apparently 4D doesn't check this!
if (!$opts['password']) {
  print_error('ERROR: 4D password is required.');
  help();
}

// Set retries default as needed.
if (!$opts['retries']) {
  $opts['retries'] = 3;
}

$fourd_dump = new FourDDump($opts);

//Done!

function parseopt($options) {
  $short_opts = '';
  $long_opts = array();

  // Build short/long option arrays
  foreach ($options as $option) {
    $short_opts .= $option['short'];
    $long_name = $option['long'];

    // If not boolean collect values. Values are always optional here.
    if(!$option['bool']) {
      $short_opts .= '::';
      $long_name .= '::';
    }
    $long_opts[] = $long_name;
  }

  // Get the options
  $opt = getopt($short_opts, $long_opts);

  foreach ($options as $option) {
    $short = $option['short'];
    $long = $option['long'];
    // If short is set and long isn't, move short into long values.
    if (isset($opt[$short]) && !isset($opt[$long])) {
      // Move the value into the long name.
      $opt[$long] = $opt[$short];
    }
    // Remove the short name either way (if both are set, short is ignored.)
    unset($opt[$short]);
    // Make all booleans values exist as real booleans.
    if ($option['bool']) {
      $opt[$long] = isset($opt[$long]) ? TRUE : FALSE;
    }
    // If not a bool and long is not set, set to FALSE
    elseif (!isset($opt[$long])) {
      $opt[$long] = FALSE;
    }
  }
  //var_dump($opt);exit();
  // Return the cleaned up array
  return $opt;
}