<?php
header("Access-Control-Allow-Origin: *");
ini_set( "display_errors", 0); // force show errors for debug

include('../config.php');
include('../model/MapEntrySearch.php');
include('../model/Traceroute.php');

$r = MapEntrySearch::getSearchCounts($_POST);

echo json_encode($r);