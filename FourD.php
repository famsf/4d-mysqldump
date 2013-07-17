<?php
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
 */

// Maximum number of columns to use in a single select statement; reduces
// query string overall length.
define("FOURD_SQL_COLUMN_LIMIT", 30);

/**
 * Connects to 4D and gets requested information.
 *
 * @todo Get cache results to reduce SQL calls!!!
 * @todo Fix SQL injections.
 */
class FourD {
  // Stores the 4d connection
  private $db;
  // Stores the in process 4D table query statements
  private $statements;
  // Count of total connection attempts
  private $total_attempts;
  // Stores the passed opt array with command line arguments.
  private $opt;

  function __construct($opt) {
    $this->opt = $opt;

    $dsn = '4D:host=' . $this->opt['host'] . ';charset=UTF-8';
    $connected = FALSE;
    $attempts = 0;
    // Try to connect multiple times. (My 4D won't connect about 1/2 the time.)
    while(!$connected) {
      try {
        $this->db = new PDO($dsn, $this->opt['user'], $this->opt['password']);
      }
      catch (PDOException $e) {
        // Total allowed attempts = retries plus the intial attempt.
        if ($attempts > $this->opt['retries']) {

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

  function getTables($select_table = FALSE) {
    if ($select_table) {
      $query = "SELECT * FROM _USER_TABLES WHERE TABLE_NAME='" . $select_table . "'";
    }
    else {
      $query = "SELECT * FROM _USER_TABLES;";
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

  /**
   * Get the indexes/keys in the specified table.
   *
   * @param string $table_id
   * @return array Row data
   */
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
    // Add brackets around every column name to allow spaces and sql reserved
    // words as column names.
    foreach ($column_names as &$column) {
      $column = '[' . $column . ']';
    }
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
      // Create the column list.
      $columns_print = implode(',', $columns_list[$i]);
      // Create the query.
      $query = "SELECT " . $columns_print . " FROM " . $table_name;

      // If limit is set, add it to the query.
      if($this->opt['limit']) {
        $query .= ' LIMIT ' . $this->opt['limit'];
      }
      // If offset is set, add it to the query.
      if($this->opt['offset']) {
        $query .= ' OFFSET ' . $this->opt['offset'];
      }
      $query .= ";";

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
