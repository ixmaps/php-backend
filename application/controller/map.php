<?php
/**
 *
 * This handles queries from the map.js frontend
 *
 * @param $_POST json
 *
 * @return some kind of JSON object - TODO
 *
 * @author IXmaps.ca (Anto)
 *
 */
header("Access-Control-Allow-Origin: *");
ini_set( "display_errors", 0); // force show errors for debug
require_once('../config.php');
require_once('../model/Traceroute.php');

$myIp = $_SERVER['REMOTE_ADDR'];

/* TODO: Refine search of geodata location based on proximity to major city. Reuse other functions  */

/* Performance vars */
$mtime = microtime();
$mtime = explode(" ",$mtime);
$mtime = $mtime[1] + $mtime[0];
$starttime = $mtime;


if (!isset($_POST) || count($_POST) == 0) {
  $error = array(
    "error"=> "No parameters sent to Map Backend"
  );
  echo json_encode($error);

} else {
  $dbQueryHtml = "";
  $trHtmlTable = "";
  $dbQuerySummary = "";
  $totTrFound = 0;

  $totFilters = count($_POST);
  $dataArray = array();

  foreach ($_POST as $constraint) {
    $dataArray[] = $constraint;
  }

  if ($dataArray[0]['constraint1'] == "quickLink") {
    $tr_ids = Traceroute::processQuickLink($dataArray);
  } else {
    $tr_ids = Traceroute::getTraceRoute($dataArray);
  }

  // CM: turning this off for now in a futile attempt to speed up query engine
  // $data = json_encode($dataArray);
  // $saveLog = Traceroute::saveSearch($data);

  // TODO: this is part of where the inefficiencies in query time come from
  // getTraceRoute does a complicated join to get a set of ids, then getIxMapsData
  // does essentially the same join with a where id = the previously gen'd set of ids.
  if (count($tr_ids) != 0) {
    $ixMapsData = Traceroute::getIxMapsData($tr_ids);
    $ixMapsDataT = Traceroute::dataTransform($ixMapsData);
    $ixMapsDataStats = Traceroute::generateDataForGoogleMaps($ixMapsDataT);
    $trHtmlTable = Traceroute::renderTrSets($ixMapsDataT);
  }

  // end calculation of execution time
  $mtime = microtime();
  $mtime = explode(" ",$mtime);
  $mtime = $mtime[1] + $mtime[0];
  $endtime = $mtime;
  $totaltime = ($endtime - $starttime);
  $totaltime = number_format($totaltime,2);

  // add db query results/errors
  $ixMapsDataStats['querySummary']=$dbQuerySummary;
  $ixMapsDataStats['queryLogs']=$dbQueryHtml;

  // add exec time
  $ixMapsDataStats['execTime']=$totaltime;

  // add server side generated table;
  $ixMapsDataStats['trsTable']=$trHtmlTable;
  $ixMapsDataStats['totTrsFound']=$totTrFound;

  echo json_encode($ixMapsDataStats);
}
?>