<?php
header('Access-Control-Allow-Origin: *');
include('../config.php');
include('../model/GatherTr.php');
include('../model/IXmapsMaxMind.php');

// move this to... somewhere. DB? config?
$ptrJsonStructureStr = '{
  "request_id": 123456789,
  "ipt_timestamp": "2017-12-31 23:59:59",
  "timeout": 750,
  "queries": 4,
  "ipt_client_ip": "321.321.321.321",
  "ipt_client_postal_code": "Saanichton",
  "ipt_client_asn": 32123,
  "submitter": "CIRA IPT",
  "submitter_ip": "162.219.50.11",
  "ipt_server_city": "Calgary",
  "ipt_server_postal_code": "T1U2V3",
  "maxhops": 24,
  "os": "Darwin",
  "protocol": "ICMB",
  "hop_data": [
    {
      "pass_num": 1,
      "terminate": false,
      "hops": [
        {
          "hop_num": 1,
          "ip": "70.67.160.1",
          "rtt": "9.65"
        },
        {
          "hop_num": 2,
          "ip": "70.67.160.1",
          "rtt": "9.65"
        }
      ]
    },
    {
      "pass_num": 2,
      "terminate": true,
      "hops": []
    }
  ]
}';
// the archetypal json structure for input
$ptrJsonStructure = json_decode($ptrJsonStructureStr, TRUE);

// received json
$hopData = json_decode($_POST["hop_data"], TRUE);

// status starts as 201 (success) and can be modified
$statusCode = 201;
// $statusCode of 401 = missing key
// $statusCode of 402 = missing value

/***
 *** validate the received JSON (eventually put this as a modal class in model CIRAsomename)
 ***/

// 1. confirm that all required keys are present in the submission
$missingKey = '';
foreach ($ptrJsonStructure as $key => $value) {
  if (is_null($_POST[$key])) {
    $missingKey = $key;
    $statusCode = 401;
  }
}
// 2. confirm that all keys are not blank (TODO: do we want to include this error check?)
$missingValueForKey = '';
foreach ($_POST as $key => $value) {
  if (empty($_POST[$key])) {
    $missingValueForKey = $key;
    $statusCode = 402;
  }
}
// TODO - we want to move this out to validateInputPTR($ptrJsonStructure)
// but if so:
// - is there a hierarchy of error codes? if no, it has to be an obj / array
// - remember that this needs to also accom the 'message' value
// - perhaps this wants to be combined with the appendSuccessStatus function



/***
 *** send the json to ingest_tr_cira (phase 2)
 ***/



/***
 *** construct the basic GEO-JSON return
 ***/

// create the mm object
$mm = new IXmapsMaxMind();

// figure out which pass we want to use, *now just using first pass*.
// we cannot necessarily assume the hop_data array is ordered (eg pass1 != hopdata[0])
// $trByHop = GatherTr::analyzeIfInconsistentPasses($trData); maybe? And others
$chosenPass = $hopData[0];
$hops = $chosenPass["hops"];
//var_dump($hops); die;

// construct the header type stuff for GEO-JSON return
$geoJson = array(
  "request_id" => $_POST["request_id"],
  "ixmaps_id" => 0,
  "hop_count" => count($hops),
  "terminate" => $chosenPass["terminate"],
  "boomerang" => checkIfBoomerang($hops)
);

// add the lat/long data to each hop of GEO-JSON
// I assume we can do this with GatherTr.php, so just some dummy data here
// 1. see if we have the IP in the DB

// 2. see if Maxmind has the IP
$overlayData = array();
foreach ($hops as $hop) {
  $ipData = $mm->getGeoIp($hop["ip"]);

  $attributeObj = array(
    "asnum" => $ipData["asn"],
    "asname" => $ipData["isp"],
    "country" => $ipData['geoip']['country_code'],
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

// 3 some kind of default if MM doesn't have it



// add boomerang value to the obj


// close MaxMind files
$mm->closeDatFiles();


/***
 *** return the GEO-JSON (or error code) to CIRA
 ***/
$geoJson['status'] = appendSuccessStatus($statusCode);
// think about destroying the geoJson object here is status is not 201, just returning status json?
header('Content-type: application/json');
echo json_encode($geoJson);



/*************************************************************************************/
/*************************************************************************************/
/*************************************************************************************/


/* move these all to the model - but which model? Probably at least two, CIRA and some kind of general purpose model */

// MOST LIKELY MODEL: CIRA
// function validateInputPTR($ptrJsonStructure) {

// }

// MOST LIKELY MODEL: ? (something general) ?
function appendSuccessStatus($statusCode) {
  $message = '';

  switch ($statusCode) {
    case 201:
      $message = "Success";
      break;
    case 401:
      $message = "Malformed JSON, missing key - " . $missingKey;
      break;
    case 402:
      $message = "Malformed JSON, unset value for key - " . $missingValueForKey;
      break;
  }

  $statusJson = array(
    "code" => $statusCode,
    "message" => $message
  );
  return $statusJson;
}

// MOST LIKELY MODEL: ? (something general) ?
function checkIfBoomerang($hops) {
  return true;
}

?>


