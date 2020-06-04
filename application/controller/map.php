<?php

/**
 *
 * This handles queries from the map.js frontend
 *
 * @param $_POST json
 *
 * @return some kind of JSON object - TODO
 *
 * @author IXmaps.ca (Colin, Antonio)
 * @since 2020 Jun
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



if (!isset($_POST)) {
  $error = array(
    "error" => "No parameters sent to Map Backend"
  );
  echo json_encode($error);

} else {

  $postArr = json_decode(file_get_contents('php://input'), TRUE);
  $data = json_encode($postArr);
  $saveLog = Traceroute::saveSearch($data);

  if ($postArr[0]['constraint1'] == "quickLink") {
    $trIds = Traceroute::processQuickLink($postArr);
  } else {
    $trIds = Traceroute::getTracerouteIdsForConstraints($postArr);
  }

  if (count($trIds) != 0) {
    $ixMapsData = Traceroute::getTracerouteDataForIds($trIds);
  }

  // end calculation of execution time
  $mtime = microtime();
  $mtime = explode(" ",$mtime);
  $mtime = $mtime[1] + $mtime[0];
  $endtime = $mtime;
  $totaltime = ($endtime - $starttime);
  $totaltime = number_format($totaltime, 2);

  // add exec time
  $ixMapsData['execTime'] = $totaltime;

  echo json_encode($ixMapsData);
}
?>