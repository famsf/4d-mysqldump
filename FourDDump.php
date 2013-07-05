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
  private $retries;
  private $select_table;

  function __construct($hostname, $username, $password, $retries, $select_table = NULL, $list_tables) {
    $this->retries = $retries;
    $this->select_table = $select_table;

    // Create the 4d DB connection.
    $this->fourd = new FourD($hostname, $username, $password, $this->retries);
    
    if($list_tables) {      
      foreach($this->fourd->getTables($select_table) as $fourd_table) {
        print($fourd_table['TABLE_NAME'] . PHP_EOL);        
      }
      exit();
    }
        
    if(is_null($select_table)) {
      // Process all tables
      foreach($this->fourd->getTables($select_table) as $fourd_table) {
        passthru($_SERVER['PHP_SELF'] . ' -h'. $hostname . ' -u' . $username . ' -p' . $password . ' -r' . $retries . ' -t' . $fourd_table['TABLE_NAME']);
      }
    }
    else {
      // Process specified table.
      foreach($this->fourd->getTables($select_table) as $fourd_table) {
        $table = $this->parseTable($fourd_table);

        if (count($table->columns) == 0) {
          trigger_error('4D Table has no existing columns:' .
            $table->name, E_USER_NOTICE);
        }
        else {
          $this->dumpTableStructure($table);
          $this->dumpTableData($table);
        }
      }
    }
  }

  function parseTable($fourd_table) {
    $table = new stdClass();
    // @todo: Mark table as temporary

    // Store the table id/name
    $table->id = $fourd_table['TABLE_ID'];
    // Lower case for consistency with column names.
    $table->name = strtolower($fourd_table['TABLE_NAME']);

    // An array of column data stored by name
    $table->columns = array();
    // Create/recreate a hash of the columns by name
    $column_by_name = array();

    // Loop through each column
    foreach ($this->fourd->getColumns($fourd_table['TABLE_ID']) as $fourd_column) {
      $column = $this->parseColumn($fourd_column);
      // If column is valid, store it
      if ($column) {
        // If there is a duplicate name, add the id number to the end.
        if (isset($column_by_name[$column->name])) {
          $column->name .= '-' . $column->id;
        }

        // Store the column.
        $table->columns[$column->id] = $column;
        // Store the by-name lookup hash for determining duplicates.
        $column_by_name[$column->name] = $table->columns[$column->id];
      }
    }

    $table->indexes = array();
    // Loop through each index/key
    foreach ($this->fourd->getIndexes($fourd_table['TABLE_ID']) as $fourd_index) {
      // @todo: Determine a way to use 4D's INNER JOIN to reduce queries... as in they don't work.
      // Get the columns in the index
      $fourd_index_cols = $this->fourd->getIndexColumns($fourd_index['INDEX_ID']);
      // Parse the 4d index data into a MySQL key.
      $index = $this->parseIndex($table->columns, $fourd_index, $fourd_index_cols);
      // If key is valid, store it.
      if ($index) {
        // Store by name instead of ID to force unique keys. Dupes are found in my 4D database.
        $table->indexes[$index->name] = $index;
      }
    }
    return $table;
  }

  function parseColumn($fourd_column) {
    $column = new stdClass();

    // Store the column id/name
    $column->id = $fourd_column['COLUMN_ID'];
    // Converting all column names to lowercase because 4D allows duplicate
    // column names and we need unique names.
    $column->name = strtolower($fourd_column['COLUMN_NAME']);
    // If column name is a SQL reserved word, drop/ignore table.
    if ($column->name == 'group') {
      trigger_error('Invalid 4D column name (SQL Reserved):' .
        $fourd_column['COLUMN_NAME'], E_USER_NOTICE);
      return FALSE;
    }
    // If column name contains a space, drop/ignore table.
    if (strpos($column->name, ' ') !== FALSE) {
      trigger_error('Invalid 4D column name (Contains spaces):' .
        $fourd_column['COLUMN_NAME'], E_USER_NOTICE);
      return FALSE;
    }

    // Store original name for select statement to ignore dropped tables.
    $column->original_name = $fourd_column['COLUMN_NAME'];

    switch($fourd_column['DATA_TYPE']) {
      case FOURD_DATA_BOOL: //id:1
        $column->type = 'bool';
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
        trigger_error('Unhandled 4D Data Type Picture for:' .
            $fourd_column['COLUMN_NAME'], E_USER_NOTICE);
        return FALSE;
      case FOURD_DATA_SUBTABLE_RELATION: //id:15
        trigger_error('Unhandled 4D Data Type Subtable Relation for:' .
            $fourd_column['COLUMN_NAME'], E_USER_NOTICE);
        return FALSE;
      case FOURD_DATA_SUBTABLE_RELATION_AUTO: //id:16
        // @todo: Fix.
        trigger_error('Unhandled 4D Data Type Subtable Relation Automatic for:' .
            $fourd_column['COLUMN_NAME'], E_USER_NOTICE);
        return FALSE;
      case FOURD_DATA_BLOB: //id:18
        // @todo: Fix.
        trigger_error('Ignoring binary data in Blob column:' .
            $fourd_column['COLUMN_NAME'], E_USER_NOTICE);
        $column->type = 'blob';
        return FALSE;
      default:
        // Trigger warning for unknown data types and skip them.
        trigger_error('Unknown 4D Data Type. ID: ' . $fourd_column['DATA_TYPE'] .
            ' Column: ' . $fourd_column['COLUMN_NAME'], E_USER_WARNING);
        return FALSE;
    }

    // Record if NULL values are allowed.
    $column->null = ($fourd_column['NULLABLE'] == FOURD_TRUE);

    return $column;
  }

  function parseIndex(&$columns, $fourd_index, $index_cols) {
    $index = new stdClass();

    $index->id = $fourd_index['INDEX_ID'];

    $index->columns = array();

    // Store each column in the index/key.
    foreach ($index_cols as $index_col) {
      $col_id = $index_col['COLUMN_ID'];
      // Get column position as an integer
      $col_position = (int)$index_col['COLUMN_POSITION'];
      // If column does not exist, skip index. Unhandled columns are dropped.
      if (!isset($columns[$col_id])) {
        return FALSE;
      }
      // If column is a blob/text, drop the index to avoid severe performance problems.
      if ($columns[$col_id]->type == 'blob' || $columns[$col_id]->type == 'text') {
        // @todo: Perhaps add this to the SQL as a comment?
        return FALSE;
      }
      // Point to the actual column data.
      $index->columns[$col_position] = $columns[$col_id]->name;
    }

    // If the index is named use it, otherwise make up a name.
    if (empty($fourd_index['INDEX_NAME'])) {
      // Name = "column1 x column2"
      $index->name = implode(' x ', $index->columns);
    }
    else {
      $index->name = $fourd_index['INDEX_NAME'];
    }

    // Store if key/index is unique
    $index->unique = ($fourd_index['UNIQUENESS'] == FOURD_TRUE);

    return $index;
  }


  /**
   * Parses and prints table structure definitions.
   *
   * @param string $table
   */
  function dumpTableStructure($table) {
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

    // Loop through each unique key
    foreach ($table->indexes as $index) {
      if ($index->unique) {
        $lines[] = $this->dumpIndexKey($index, TRUE);
      }
    }

    // Loop through each non-unique key
    foreach ($table->indexes as $index) {
      if (!$index->unique) {
        $lines[] = $this->dumpIndexKey($index, FALSE);
      }
    }

    // Put a comma after every line except for the last.
    $print_lines = implode(',', $lines);
    print($print_lines);

    print(PHP_EOL . ') ENGINE=InnoDB DEFAULT CHARSET=utf8;');
    print(PHP_EOL);
  }

  function dumpIndexKey($index, $print_unique) {
    // Prep columns for output, names enclosed in ` separated by ,
    $key_columns = array();
    foreach($index->columns as $column) {
      $key_columns[] = sprintf('`%s`', $column);
    }
    $key_columns = implode(',', $key_columns);

    // If unique print unique.
    $unique = $print_unique ? 'UNIQUE ' : '';
    // Create the key lines
    return sprintf(PHP_EOL . '  %sKEY `%s` (%s)', $unique, $index->name, $key_columns);
  }

  /**
   * Prints table data
   *
   * @param string $table
   */
  function dumpTableData($table) {
    $original_columns = array();
    foreach($table->columns as $column) {
      $original_columns[] = $column->original_name;
    }
    $this->fourd->startSelect($table->name, $original_columns);

    print(PHP_EOL . '--');
    printf(PHP_EOL . '-- Dumping data for table `%s`', $table->name);
    print(PHP_EOL . '--');
    print(PHP_EOL);
    printf(PHP_EOL . 'LOCK TABLES `%s` WRITE;', $table->name);
    print(PHP_EOL . 'set autocommit=0;');

    // Loop through each row and column
    while ($row = $this->fourd->getRow()) {
      $values = array();
      foreach ($table->columns as $column) {
        $value = $row[strtoupper($column->original_name)];
        if($column->type == 'int') {
          $values[] = sprintf("%d", $value);
        }
        else {
          $values[] = sprintf("'%s'", mysql_real_escape_string($value));
        }
      }
      // Put a comma after every line except for the last.
      $print_values = implode(',', $values);
      printf(PHP_EOL . 'INSERT INTO `%s` VALUES (%s);', $table->name, $print_values);
    }

    print(PHP_EOL . 'UNLOCK TABLES;');
    print(PHP_EOL . 'commit;');
    print(PHP_EOL);
  }

}