<?php
header("Access-Control-Allow-Origin: *");
ini_set( "display_errors", 0); // force show errors for debug
/* include files */
include('../config.php');
include('../model/MapSearch.php');
include('../model/Traceroute.php');

$debugTrSearch = false;

//print_r($_POST);

$testArray = array(
  "filter-constraint1" => array
    (
      "constraint1" => "does",
      "constraint2" => "originate",
      "constraint3" => "country",
      "constraint4" => "AR",
      "constraint5" => "and"
    ),
  "filter-constraint2" => array
    (
      "constraint1" => "does",
      "constraint2" => "originate",
      "constraint3" => "city",
      "constraint4" => "Buenos Aires",
      "constraint5" => "and"
    ),
  "filter-constraint3" => array
    (
      "constraint1" => "does",
      "constraint2" => "originate",
      "constraint3" => "asnum",
      "constraint4" => "7303",
      "constraint5" => "and"
    )
);

//print_r($testArray);

$testArray = $_POST;

$r = MapSearch::countTrs($testArray);

echo json_encode($r);

//print_r($dataArray);
//print_r($_POST);

/*if(!isset($_POST) || count($_POST)==0){
  $error = array(
    "error"=> "No parameters sent to Map Search Backend"
    );
  echo json_encode($error);

} else {
  $r = MapSearch::countTrResults($_POST);
  echo json_encode($r);
}*/