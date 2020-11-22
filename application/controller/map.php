<?php

/**
 *
 * This handles queries from the map.js frontend
 *
 * @param $_POST
    [
      {
        constraint1: "does",
        constraint2: "originate",
        constraint3: "submitter",
        constraint4: "CDM28062015test",
        constraint5: "and"
      },
      {
        maxTrCount: 100
      }
    ]
 *
 * @return some kind of JSON object - TODO
 *
 * @author IXmaps.ca (Colin, Antonio)
 * @since 2020 Jun
 *
 */
header("Access-Control-Allow-Origin: *");
require_once('../config.php');
require_once('../model/Traceroute.php');

$myIp = $_SERVER['REMOTE_ADDR'];

global $trNumLimit;
global $searchLog;

/* TODO: Refine search of geodata location based on proximity to major city. Reuse other functions  */

/* Performance vars */
$logfile = fopen($searchLog, "a+") or exit("Unable to open file!");
$starttime = getNow();

if (!isset($_POST)) {
  $error = array(
    "error" => "No parameters sent to Map Backend"
  );
  echo json_encode($error);

} else {

  $postArr = json_decode(file_get_contents('php://input'), TRUE);

  // if the frontend has passed in a max tr to return value, overwrite the default
  $maxTrCount = $trNumLimit;
  if (key(end($postArr)) == "maxTrCount") {
    $maxTrCount = end($postArr)["maxTrCount"];
    array_pop($postArr);
  }

  $data = json_encode($postArr);

  $saveLog = Traceroute::saveSearch($data);

  if ($postArr[0]['constraint1'] == "quickLink") {
    $trIds = Traceroute::processQuickLink($postArr);
  } else {
    $trIds = Traceroute::getTracerouteIdsForConstraints($postArr);
  }

  fwrite($logfile, "Time after tr ids selected: ".executionTime($starttime)."\n");

  if (count($trIds) != 0) {
    $ixMapsData = Traceroute::getTracerouteDataForIds($trIds, $maxTrCount);
  }

  fwrite($logfile, "Time after data selected: ".executionTime($starttime)."\n");

  // add total execution time
  $ixMapsData['execTime'] = executionTime($starttime);

  fclose($logfile);

  echo json_encode($ixMapsData);
}

function executionTime($starttime) {
  $endtime = getNow();
  $totaltime = ($endtime - $starttime);
  return number_format($totaltime, 2);
}

function getNow() {
  $mtime = microtime();
  $mtime = explode(" ",$mtime);
  return $mtime[1] + $mtime[0];
}
?>