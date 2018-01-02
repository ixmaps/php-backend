<?php
header('Access-Control-Allow-Origin: *');
include('../config.php');
include('../model/GatherTr.php');           // leaving this for now - we'll need it
include('../model/IXmapsMaxMind.php');
include('../model/GeolocPtr.php');
include('../model/TracerouteUtility.php');
include('../model/ResponseCode.php');


// do some utility parsing of the received json
$hopData = json_decode($_POST["hop_data"], TRUE);
/***
 *** construct the basic GEO JSON object
 ***/
$geoJson = [];


/***
 *** validate the received JSON, if invalid immediately return with error code
 ***/
/**  returned status of each validate function is an array of structure:
  * {
  *   statusCode: 123,
  *   statusMsg: 'abc'
  * }
  * success status are 2xx. Only current success status is 201
  */
$responseObj = GeolocPtr::validateInputPtr();
if ($responseObj->getCode() != 201) {        // TODO make 2xx
  GeolocPtr::returnGeoJson($geoJson, $responseObj);
}

/***
 *** send the json to ingest_tr_cira (phase 2)
 ***/



/***
 *** construct the GEO-JSON return
 ***/

// create the mm object
$mm = new IXmapsMaxMind();

// figure out which pass we want to use, *now just using first pass*.
// NB: we cannot necessarily assume the hop_data array is ordered (eg pass1 != hopdata[0])
// $trByHop = GatherTr::analyzeIfInconsistentPasses($trData); maybe? And others
$chosenPass = $hopData[0];
$hops = $chosenPass["hops"];

// add the header type stuff for GEO-JSON return
$geoJson["request_id"] = $_POST["request_id"];
$geoJson["ixmaps_id"] = 0;
$geoJson["hop_count"] = count($hops);
$geoJson["terminate"] = $chosenPass["terminate"];
$geoJson["boomerang"] = TracerouteUtility::checkIfBoomerang($hops);

// add the lat/long data to each hop of GEO-JSON
// (I assume we can do this with GatherTr.php, so just some dummy data here)


$overlayData = array();
foreach ($hops as $hop) {
  // 1. see if we have the IP in the DB
  $existsInDB = TracerouteUtility::checkIpExists($hop["ip"]);

  if ($existsInDB) {

  } else {
    // 2. see if Maxmind has the IP
    $ipData = $mm->getGeoIp($hop["ip"]);
  }

  // 3 some kind of default if MM doesn't have it

  $attributeObj = array(
    "asnum" => $ipData["asn"],
    "asname" => $ipData["isp"],
    "country" => $ipData["geoip"]["country_code"],
    "nsa" => "TODO",
    "georeliability" => "TBD"
  );
  $overlayHop = array(
    "hop" => $hop["hop_num"],
    "lat" => $ipData["geoip"]["latitude"],
    "long" => $ipData["geoip"]["longitude"],
    "attributes" => $attributeObj
  );
  array_push($overlayData, $overlayHop);
}
$geoJson["overlay_data"] = $overlayData;


// close MaxMind files
$mm->closeDatFiles();


/***
 *** return the GEO-JSON to CIRA
 ***/
GeolocPtr::returnGeoJson($geoJson, $responseObj);
