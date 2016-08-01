<?php

  ini_set('memory_limit','2000M');

  require_once __DIR__ . '/DB.class.php';
  $db = new DB();

  $column_names = array('profileid', 'phoneuid', 'source');

  // $response = $db->selectBatch(true, 'lucktastic.nonfbprofiles', 0, 100);
  // $arr = json_decode($response);
  // $data = $arr->data;
  // print_r($data);

  $db->writeToBatchFile(true, 'lucktastic.nonfbprofiles', 'lucktastic.nonfb', $column_names, 10000);
?>
