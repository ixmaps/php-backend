<?php
header('Access-Control-Allow-Origin: *');
include('../config.php');
include('../model/GatherTr.php');
include('../model/IXmapsMaxMind.php');

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
// move this structure to... somewhere. DB? config?
$ptrJsonStructure = json_decode($ptrJsonStructureStr, TRUE);
// received json
$hopData = json_decode($_POST["hop_data"], TRUE);

/***
 *** construct the basic GEO JSON object
 ***/
$geoJson = array();


/***
 *** validate the received JSON, if invalid immediately return with error code
 ***/
// status of each validate function is an array of structure:
// statusCode: 123,
// statusMsg: 'abc'
// success status are 2xx. Only current success status is 201
$statusObj = validateInputPTR($ptrJsonStructure);
if ($statusObj["code"] != 201) {        // TODO make 2xx
  returnGeoJson($geoJson, $statusObj);
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
$geoJson["boomerang"] = checkIfBoomerang($hops, $mm);

// add the lat/long data to each hop of GEO-JSON
// (I assume we can do this with GatherTr.php, so just some dummy data here)
// 1. see if we have the IP in the DB

// 2. see if Maxmind has the IP
$overlayData = array();
foreach ($hops as $hop) {
  $ipData = $mm->getGeoIp($hop["ip"]);

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

// 3 some kind of default if MM doesn't have it

// close MaxMind files
$mm->closeDatFiles();


/***
 *** return the GEO-JSON to CIRA
 ***/
returnGeoJson($geoJson);



/*************************************************************************************/
/*************************************************************************************/
/*************************************************************************************/


/* move these all to the model - but which model? Probably at least two, CIRA and some kind of general purpose model */

// MOST LIKELY MODEL: CIRA (will need to $_POST back as a param, I think - not sure how 'superglobal variables' work)
function validateInputPTR($ptrJsonStructure) {
  $code = 201;
  $message = '';

  // note the implied hierarchy here, 401 will be shown first (this might be too implicit)
  // 2. confirm that all keys are not blank (TODO: do we want to include this error check?)
  foreach ($_POST as $key => $value) {
    if (empty($_POST[$key])) {
      $message = $key;
      $code = 402;
    }
  }
  // 1. confirm that all required keys are present in the submission
  foreach ($ptrJsonStructure as $key => $value) {
    if (is_null($_POST[$key])) {
      $message = $key;
      $code = 401;
    }
  }

  $statusObj = array(
    "code" => $code,
    "message" => $message
  );

  return $statusObj;
}

// MOST LIKELY MODEL: ? (something general) ?
function generateStatusObj($statusObj) {
  switch ($statusObj["code"]) {
    case 201:
      $message = "Success";
      break;
    case 401:
      $message = "Malformed JSON, missing key - " . $statusObj["message"];
      break;
    case 402:
      $message = "Malformed JSON, unset value for key - " . $statusObj["message"];
      break;
  }

  $statusJson = array(
    "code" => $statusObj["code"],
    "message" => $message
  );
  return $statusJson;
}

// MOST LIKELY MODEL: ? (something general) ? Probably want to remove the mm param at that point
function checkIfBoomerang($hops, $mm) {
  $originateCA = false;
  $viaUS = false;
  $terminateCA = false;

  if ($mm->getGeoIp($hops[0]["ip"])["geoip"]["country_code"] == 'CA') {
    $originateCA = true;
  }

  foreach ($hops as $key => $hop) {
    $hopCountry = $mm->getGeoIp($hop["ip"])["geoip"]["country_code"];

    if ($hopCountry == 'US') {
      $viaUs = true;
    }

    if ($key == sizeof($hops) && $hopCountry == 'CA') {
      $terminateCA = true;
    }
  }

  if ($originateCA == true && $viaUS == true && $terminateCA == true) {
    return true;
  } else {
    return false;
  }
}


// maybe in the CIRA model? Or maybe just leave here?
function returnGeoJson($geoJson, $statusObj) {
  $geoJson['status'] = generateStatusObj($statusObj);
  header('Content-type: application/json');
  echo json_encode($geoJson);
  die;                              // is this bad practice?
}

?>


