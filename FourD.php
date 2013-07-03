<?php

define("FOURD_SQL_COLUMN_LIMIT", 30);

/**
 * Connects to 4D and gets requested information.
 *
 * @todo Get cache results to reduce SQL calls!!!
 */
class FourD {
  private $db;

  function __construct($hostname, $username, $password) {
    $dsn = '4D:host=' . $hostname . ';charset=UTF-8';
    try {
      $this->db = new PDO($dsn, $username, $password);
    }
    catch (PDOException $e) {
      trigger_error('4D Connection Failed:' . $e->getMessage(), E_USER_ERROR);
      exit();
    }
  }

  function query($query) {
    //print($query);
    $statement = $this->db->prepare($query);
    $statement->execute();
    return $statement->fetchAll();
  }

  function getTables() {
    $query = 'SELECT * FROM _USER_TABLES;';
    return $this->query($query);
  }
  /**
   * Get the columns in the specified table.
   *
   * @param string $table_id
   * @return array Row data
   */
  function getColumns($table_id) {
    $query = "SELECT * FROM _USER_COLUMNS WHERE TABLE_ID=" . $table_id . ";";
    return $this->query($query);
  }
  function getIndexes($table_id) {
    $query = "SELECT * FROM _USER_INDEXES WHERE TABLE_ID=" . $table_id . ";";
    return $this->query($query);
  }
  /**
   * Get the columns in the specified index
   *
   * @param string $index_id
   * @return array Row data
   */
  function getIndexColumns($index_id) {
    $query = "SELECT * FROM _USER_IND_COLUMNS	WHERE INDEX_ID='" . $index_id . "';";
    return $this->query($query);
  }

  function getRows($table_name, $column_list = array()) {
    $column_list_list = array($column_list);
    // Break the column_name array into an array of arrays with max lenght of FOURD_SQL_COLUMN_LIMIT
    // @todo: Fix the column ordering, this method puts the last columns at the start.
    while (count($column_list_list[0]) > FOURD_SQL_COLUMN_LIMIT) {
      $column_list_list[] = array_splice($column_list_list[0], 0, FOURD_SQL_COLUMN_LIMIT);
    }

    // Run the column lists as separate select queries, then combine the data
    // afterward. Select queries with large numbers of column names cause PHP
    // segmentation faults in PDO in $statement->fetch(). This is a workaround.
    // Segmentation fault details:
    //   Program received signal SIGSEGV, Segmentation fault.
    //     pdo_4d_stmt_get_col (stmt=<optimized out>, colno=<optimized out>, ptr=0x7fffffffad58,
    //         len=0x7fffffffad60, caller_frees=<optimized out>)
    //         at pdo_4d/4d_statement.c:141
    //     141						*ptr=emalloc(b->length);

    $results = array();
    foreach($column_list_list as $column_list) {
      $columns = implode(',', $column_list);
      $query = "SELECT " . $columns . " FROM " . $table_name . " LIMIT 20000;";

      $statement = $this->db->prepare($query);
      $statement->execute();
      // Reset the counter
      $count = 0;
      // Run the query
      while ($row = $statement->fetch()) {
        // Build a new result array
        foreach ($row as $key => $value) {
          // Ignore the numeric keys.
          if (!is_numeric($key)) {
            $results[$count][$key] = $value;
          }
        }
        $count++;
      }
    }
    return $results;

  }
}