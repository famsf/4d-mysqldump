<?php
   
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

}