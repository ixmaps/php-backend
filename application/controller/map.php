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
require_once('../model/Logging.php');

$myIp = $_SERVER['REMOTE_ADDR'];

global $trNumLimit;

/* TODO: Refine search of geodata location based on proximity to major city. Reuse other functions  */

/* Performance vars */
$log = new Logging();

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
    $trIds = Traceroute::getTracerouteIdsForConstraints($postArr, $log);
  }

  $log->search("Tr ids selected");

  if (count($trIds) != 0) {
    $ixMapsData = Traceroute::getTracerouteDataForIds($trIds, $maxTrCount);
  }

  $log->search("Final data selected");

  // add total execution time
  $ixMapsData['execTime'] = $log->executionTime();

  echo json_encode($ixMapsData);
}
?>