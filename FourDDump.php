<?php
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
define('FOURD_DATA_SUBTABLE_RELATION_AUTO', 16);
//define('FOURD_DATA_', 17);
define('FOURD_DATA_BLOB', 18);

// Define 4D Booleans, because they are non-standard.
define('FOURD_TRUE', 0);
define('FOURD_FALSE', 1);

class FourDDump {
  private $fourd;
  private $tables;

  function __construct($hostname, $username, $password) {
    // An array to store table structure.
    $this->tables = array();

    // Create the 4d DB connection.
    $this->fourd = new FourD($hostname, $username, $password);

    $this->parseTables();
    foreach ($this->tables as $table) {
      $this->dumpTable($table);
    }
  }

  function parseTables() {
    // Process every table in the database.
    foreach($this->fourd->getTables() as $fourd_table) {
      // Only work with permanent (non-temporary) tables.
      if ($fourd_table['TEMPORARY'] == FOURD_FALSE) {
        $this->tables[$fourd_table['TABLE_NAME']] = $this->parseTable($fourd_table);
      }
      //print_r($tables);
      //break;
    }
  }

  function parseTable($fourd_table) {
    $table = new stdClass();

    // Store the table id/name
    $table->id = $fourd_table['TABLE_ID'];
    $table->name = $fourd_table['TABLE_NAME'];

    $table->columns = array();

    foreach ($this->fourd->getColumns($fourd_table['TABLE_ID']) as $fourd_column) {
      // Check for duplicates
      //if (isset($table->columns[$fourd_column['COLUMN_NAME']])) {

        //$table->columns[$fourd_column['COLUMN_NAME']] = $table->columns[$fourd_column['COLUMN_NAME']]
      //}
      $column = $this->parseColumn($fourd_column);
      // If column is valid, store it
      if ($column) {
        $table->columns[$fourd_column['COLUMN_NAME']] = $column;
      }
    }
    //foreach ($this->fourd->getIndexes($fourd_table['TABLE_ID'] as $four_index) {

    //}
    return $table;
  }

  function parseColumn($fourd_column) {
    $column = new stdClass();

    // Store the column id/name
    $column->id = $fourd_column['COLUMN_ID'];
    $column->name = $fourd_column['COLUMN_NAME'];

    switch($fourd_column['DATA_TYPE']) {
      case FOURD_DATA_BOOL: //id:1
        $column->type = ' bool';
        break;
      case FOURD_DATA_INT_16: //id:3
      case FOURD_DATA_INT_32: //id:4
      case FOURD_DATA_INT_64: //id:5
        // 32/64 Bit isn't really supported in MySQL...
        $column->type = 'int';
        break;
      case FOURD_DATA_REAL: //id:6
        $column->type = 'real';
        break;
      case FOURD_DATA_DATETIME: //id:8
        $column->type = 'datetime';
        break;
      case FOURD_DATA_TIME: //id:9
        $column->type = 'time';
        break;
      case FOURD_DATA_TEXT: //id:10
        // If $var_length is zero, this is a MySQL TEXT field.
        if ($fourd_column['DATA_LENGTH'] == 0) {
          $column->type = 'text';
        }
        // If $var_length is not zero, this is a VARCHAR.
        else {
          // 4D stores data length as bytes + EOL. To convert to MySQL var
          // length, we minus four and divide by two. (514 - 4)/2 = 255.
          $varchar_length = ($fourd_column['DATA_LENGTH'] - 4) / 2;
          $column->type = sprintf('varchar(%d)', $varchar_length);
        }
        break;
      case FOURD_DATA_PICTURE: //id:12
        trigger_error('Unhandled 4D Data Type: Subtable Relation', E_USER_NOTICE);
        return FALSE;
      case FOURD_DATA_SUBTABLE_RELATION: //id:15
        trigger_error('Unhandled 4D Data Type: Subtable Relation', E_USER_NOTICE);
        return FALSE;
      case FOURD_DATA_SUBTABLE_RELATION_AUTO: //id:16
        trigger_error('Unhandled 4D Data Type: Subtable Relation Automatic', E_USER_NOTICE);
        return FALSE;
      case FOURD_DATA_BLOB: //id:18
        $column->type = 'blob';
        break;
      default:
        // Trigger warning for unknown data types.
        trigger_error('Unknown 4D Data Type. ID: ' . $fourd_column['DATA_TYPE'] .
            ' Column: ' . $fourd_column['COLUMN_NAME'], E_USER_WARNING);
        return FALSE;
    }

    // Record if NULL values are allowed.
    $column->null = ($fourd_column['NULLABLE'] == FOURD_TRUE);

    return $column;
  }

  /**
   * Parses and prints table structure definitions.
   *
   * @param type $table
   * @param type $columns
   * @todo: Indexes
   */
  function dumpTable($table) {
    print(PHP_EOL . '--');
    printf(PHP_EOL . '-- Table structure for table `%s`', $table->name);
    print(PHP_EOL . '--');
    print(PHP_EOL);
    printf(PHP_EOL . 'DROP TABLE IF EXISTS `%s`;', $table->name);
    printf(PHP_EOL . 'CREATE TABLE `%s` (', $table->name);

    // An array to store each line of the column/key data
    $lines = array();

    // Loop through each column
    foreach ($table->columns as $column) {
      // Prep data for output
      $null = $column->null ? 'NULL' : 'NOT NULL';
      // Create the lines
      $lines[] = sprintf(PHP_EOL . '  `%s` %s %s', $column->name, $column->type, $null);
    }

    $print_lines = implode(',', $lines);
    print($print_lines);

    print(PHP_EOL . ') ENGINE=InnoDB DEFAULT CHARSET=utf8;');
    print(PHP_EOL);

  }
}


