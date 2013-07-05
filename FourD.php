<?php

define("FOURD_SQL_COLUMN_LIMIT", 30);

/**
 * Connects to 4D and gets requested information.
 *
 * @todo Get cache results to reduce SQL calls!!!
 * @todo Fix SQL injections.
 */
class FourD {
  private $hostname;
  private $username;
  private $password;
  private $db;
  private $statements;
  private $retries;
  private $total_attempts;

  function __construct($hostname, $username, $password, $retries) {   
    $this->hostname = $hostname;
    $this->username = $username;
    $this->password = $password;
    $this->retries = $retries;

    $dsn = '4D:host=' . $this->hostname . ';charset=UTF-8';
    $connected = FALSE;
    $attempts = 0;
    // Try to connect multiple times.
    while(!$connected) {      
      try {
        $this->db = new PDO($dsn, $this->username, $this->password);
      }
      catch (PDOException $e) {
        // Total allowed attempts = retries plus the intial attempt.
        if ($attempts > $this->retries) {
          
          trigger_error('4D connection failed, after ' . $attempts . ' attempt(s):' . $e->getMessage(), E_USER_ERROR);
          exit();          
        }
      }
      if ($this->db) {
        $connected = TRUE;
      }
      // Increase the counters
      $attempts++;
      $this->total_attempts++;
    }
  }

  function query($query) {
    $statement = $this->db->prepare($query);
    $statement->execute();
    return $statement->fetchAll(PDO::FETCH_ASSOC);
  }

  function getTables($select_table) {
    if (is_null($select_table)) {
      $query = "SELECT * FROM _USER_TABLES;";
    }
    else {
      $query = "SELECT * FROM _USER_TABLES WHERE TABLE_NAME='" . $select_table . "'";
    }
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

  function startSelect($table_name, $column_names = array()) {
    // Break the column_name array into an array of arrays with max length of
    // FOURD_SQL_COLUMN_LIMIT. Long statements segfault the PDO 4D extension.
    $columns_list = array_chunk($column_names, FOURD_SQL_COLUMN_LIMIT);

    // We run the column lists as separate select queries, then combine the data
    // afterward. Select queries with large numbers of column names cause PHP
    // segmentation faults at PDO in $statement->fetch(). This is a workaround.
    // Segmentation fault details:
    //   Program received signal SIGSEGV, Segmentation fault.
    //     pdo_4d_stmt_get_col (stmt=<optimized out>, colno=<optimized out>, ptr=0x7fffffffad58,
    //         len=0x7fffffffad60, caller_frees=<optimized out>)
    //         at pdo_4d/4d_statement.c:141
    //     141						*ptr=emalloc(b->length);

    $this->statements = array();

    for($i = 0; $i < count($columns_list); $i++) {

      $columns_print = implode(',', $columns_list[$i]);
      $query = "SELECT " . $columns_print . " FROM " . $table_name . " LIMIT 10000;";//" LIMIT 50000 OFFSET 50000 ;";

      $this->statements[$i] = $this->db->prepare($query);
      $this->statements[$i]->execute();
    }
  }

  function getRow() {
    $result = array();
    foreach($this->statements as $statement) {
      if ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $result = array_merge($result, $row);
      }
      else {
        return FALSE;
      }
    }
    return $result;
  }
}
