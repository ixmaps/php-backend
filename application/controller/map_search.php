<?php
header("Access-Control-Allow-Origin: *");
ini_set( "display_errors", 0); // force show errors for debug

include('../config.php');
include('../model/MapSearch.php');

$debugTrSearch = false;
$r = MapSearch::countTrs($_POST);

echo json_encode($r);