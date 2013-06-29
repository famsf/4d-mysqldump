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
 */


// 4D column data type definitions.
// @todo: Fill in missing information.
define('FOURD_DATA_BOOL', 1);
//define('FOURD_DATA_', 2);
define('FOURD_DATA_INT_16', 3);
define('FOURD_DATA_INT_32', 4);
define('FOURD_DATA_INT_64', 5);
define('FOURD_DATA_REAL', 6);
//define('FOURD_DATA_', 7);
define('FOURD_DATA_DATETIME', 8);
define('FOURD_DATA_TIME', 9);
define('FOURD_DATA_TEXT', 10);
//define('FOURD_DATA_', 11);
define('FOURD_DATA_PICTURE', 12);
//define('FOURD_DATA_', 13);
//define('FOURD_DATA_', 14);
define('FOURD_DATA_SUBTABLE_RELATION', 15);
define('FOURD_DATA_SUBTABLE_RELATION_MACHINE', 16);
//define('FOURD_DATA_', 17);
define('FOURD_DATA_BLOB', 18);

/**
 * Print command line help
 */
function help() {
  print '4d-mysqldump V 1.0' . PHP_EOL;
  print 'Copyright 2013 Brad Erickson' . PHP_EOL;
  print '' . PHP_EOL;
  print 'Dumps structure and contents of 4D databases and tables to MySQL.' . PHP_EOL;
  print 'Usage: 4d-mysqldump -h hostname -u username -p password' . PHP_EOL;
  print '' . PHP_EOL;
}

// Get required command line arguments host, username, password.
$options = getopt("h:u:p:");

if (!isset($options['h'])) {
  echo "ERROR: host is required." . PHP_EOL . PHP_EOL;
  help();
  exit();
}

if (!isset($options['u'])) {
  echo "ERROR: username is required." . PHP_EOL . PHP_EOL;
  help();
  exit();
}

if (!isset($options['p'])) {
  echo "ERROR: password is required." . PHP_EOL . PHP_EOL;
  help();
  exit();
}

// Create 4d DB connection.
$fourd = new FourD($options['h'], $options['u'], $options['p']);

foreach($fourd->getTables() as $table) {
  // If the table is not temporary, locate its columns.
  if ($table['TEMPORARY'] == 1) {
    $columns = $fourd->getColumns($table['TABLE_ID']);
    //$indexes = $fourd->getIndexes($table['TABLE_ID']);

    print_table($table, $columns);
    //print_data($table);

  }

  //exit;
}

/**
 * Parses and prints table structure definitions.
 *
 * @param type $table
 * @param type $columns
 * @todo: Indexes
 */
function print_table($table, $columns) {
  print('--' . PHP_EOL);
  printf('-- Table structure for table `%s`' . PHP_EOL, $table['TABLE_NAME']);
  print('--' . PHP_EOL);
  print(PHP_EOL);
  printf('DROP TABLE IF EXISTS `%s`' . PHP_EOL, $table['TABLE_NAME']);
  printf('CREATE TABLE `%s` (' . PHP_EOL, $table['TABLE_NAME']);

  foreach ($columns as $column) {
    // Is set to true if invalid/unhandled data is located.
    $skip_column = FALSE;
    switch($column['DATA_TYPE']) {
      case FOURD_DATA_BOOL: //id:1
        $column_type = ' BOOL';
        break;
      case FOURD_DATA_INT_16: //id:3
      case FOURD_DATA_INT_32: //id:4
      case FOURD_DATA_INT_64: //id:5
        // 32/64 Bit isn't really supported in MySQL. I hope this works.
        $column_type = ' INT';
        break;
      case FOURD_DATA_REAL: //id:6
        $column_type = ' REAL';
        break;
      case FOURD_DATA_DATETIME: //id:8
        $column_type = ' DATETIME';
        break;
      case FOURD_DATA_TIME: //id:9
        $column_type = ' TIME';
        break;
      case FOURD_DATA_TEXT: //id:10
        // If $var_length is zero, this is a MySQL TEXT field.
        if ($column['DATA_LENGTH'] == 0) {
          $column_type = ' TEXT';
        }
        // If $var_length is not zero, this is a VARCHAR.
        else {
          // 4D stores data length as bytes + EOL. To convert to MySQL var
          // length, we minus four and divide by two. (514 - 4)/2 = 255.
          $varchar_length = ($column['DATA_LENGTH'] - 4) / 2;
          $column_type = sprintf(' VARCHAR(%d)', $varchar_length);
        }
        break;
      case FOURD_DATA_PICTURE: //id:12
        trigger_error('Unhandled 4D Data Type: Subtable Relation', E_USER_NOTICE);
        $skip_column = TRUE;
        break;
      case FOURD_DATA_SUBTABLE_RELATION: //id:15
        trigger_error('Unhandled 4D Data Type: Subtable Relation', E_USER_NOTICE);
        $skip_column = TRUE;
        break;
      case FOURD_DATA_SUBTABLE_RELATION_MACHINE: //id:16
        trigger_error('Unhandled 4D Data Type: Subtable Relation Machine', E_USER_NOTICE);
        $skip_column = TRUE;
        break;
      case FOURD_DATA_BLOB:
        $column_type = ' BLOB';
        break;
      default:
        // Trigger warning for unknown data types.
        trigger_error('Unknown 4D Data Type. ID: ' . $column['DATA_TYPE'] .
            ' Table: ' . $table['TABLE_NAME'] .
            ' Column: ' . $column['COLUMN_NAME'], E_USER_WARNING);
        $skip_column = TRUE;
        break;
    }

    // Unknown/Unhandled columns are skiped
    if (!$skip_column) {
      // Print the column name
      printf('  `%s`', $column['COLUMN_NAME']);
      // Print the column data type
      print($column_type);

      // If nullable is true, then set NOT NULL.
      // @todo: Is this reversed? Seems correct.
      if ($column['NULLABLE'] == 1) {
        print(' NOT NULL');
      }
      print(',' . PHP_EOL);
    }
  }
  print(') ENGINE=InnoDB DEFAULT CHARSET=utf8;' . PHP_EOL);

}

class FourD {
  private $db;

  function __construct($hostname, $username, $password) {
    $dsn = '4D:host=' . $hostname . ';charset=UTF-8';
    try {
      $this->db = new PDO($dsn, $username, $password);
    }
    catch (PDOException $e) {
      // TODO: More error checking
      echo 'Connection failed: ' . $e->getMessage();
    }
  }

  function query($query) {
    $statement = $this->db->prepare($query);
    $statement->execute();
    return $statement->fetchAll();
  }

  function getTables() {
    $query = 'SELECT * FROM _USER_TABLES;';
    return $this->query($query);
  }
  function getColumns($table_id) {
    $query = 'SELECT * FROM _USER_COLUMNS WHERE TABLE_ID=' . $table_id . ';';
    return $this->query($query);
  }

}

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

