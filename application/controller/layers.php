<?php
header("Access-Control-Allow-Origin: *");
include('../config.php');

getChotelData();

function getChotelData(){
  global $dbconn;
  $sql="select * from chotel";
  $result = pg_query($dbconn, $sql) or die('Query failed: ' . pg_last_error());
  $dataArr = pg_fetch_all($result);
  //return $dataArr;
  //print_r($dataArr);
  echo json_encode($dataArr);
}
?>