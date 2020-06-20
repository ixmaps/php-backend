<?php
header("Access-Control-Allow-Origin: *");
include('../config.php');

getTrsets();

function getTrsets() {
  // TODO - add order by
  global $dbconn;
  $sql = "select * from trsets join trset_targets on trsets.id = trset_targets.trset_id;";
  $result = pg_query($dbconn, $sql) or die('Trset query failed: ' . pg_last_error());
  $dataArr = pg_fetch_all($result);
  echo json_encode($dataArr);
}