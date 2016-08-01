<?php
  ini_set('memory_limit','2000M');

  require_once __DIR__ . '/Database/DB.class.php';
  $db = new DB();

  //Array of column names for the table you want to migrate data TO.
  $column_names = array('profileid', 'phoneuid', 'source');

  /**
   * @param {Boolean} Is an API endpoint provided?
   * @param {String} Table to read from
   * @param {String} Table to write to
   * @param {Array} Array containing column names
   * @param {Integer} Batch size to read from DB
   **/
  $db->writeToBatchFile(true, 'lucktastic.nonfbprofiles', 'lucktastic.nonfbprofiles', $column_names, 10000);

?>
