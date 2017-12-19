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
$ptrJsonStructure = json_decode($ptrJsonStructureStr, TRUE);

// status starts as 201 (success) and can be modified
$statusCode = 201;
// $statusCode of 401 = missing key
// $statusCode of 402 = missing value

/*
 * validate the received JSON (eventually put this as a modal class in model CIRAsomename)
 */

// 1. confirm that all required keys are present in the submission
$missingKey = '';
foreach ($ptrJsonStructure as $key => $value) {
  //echo "{$key} => {$value} \n";
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


/*
 * send the json to ingest_tr_cira (phase 2)
 */



/*
 * construct the basic GEO-JSON return
 * again, this will all be moved into a model at some point
 */

// create the mm object
$mm = new IXmapsMaxMind();

// figure out which pass we want to use, *now just using pass 1*
$passNum = 1; // NB: passNum is not pass_num from the data structure, it relates to the index of the hop_data array
//$trByHop = GatherTr::analyzeIfInconsistentPasses($trData); maybe?

// START HERE: broken
//There was an error parsing JSON data
//Unexpected end of JSON input
//$hopCount = countHops($_POST["hop_data"][$passNum-1]);


// construct the header type stuff for GEO-JSON return
$geoJson = array(
  "request_id" => $_POST["request_id"],
  "ixmaps_id" => 0,
  "hop_count" => $hopCount
);
// calculate the other values to put in the header
// hop_count value

// terminate value

// boomerang value

// add the lat/long data to each hop of GEO-JSON (limit to one attempt/pass)

// 1. see if we have the IP it in the DB

// 2. see if Maxmind has the IP

// 3 some kind of default


// close MaxMind files
$mm->closeDatFiles();


/*
 * return the GEO-JSON (or error code) to CIRA
 */
$geoJson['status'] = appendSuccessStatus($statusCode);
// think about destroying the geoJson object here is status is not 201, just returning status json?
header('Content-type: application/json');
//echo json_encode($geoJson);




/*************************************************************************************/
/*************************************************************************************/
/*************************************************************************************/


/* to be moved to model at some point */

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

function countHops($passObj) {
  $myVar = $passObj->hops;
  // {
  //   "pass_num": 1,
  //   "terminate": false,
  //   "hops": [
  //     {
  //       "hop_num": 1,
  //       "ip": "70.67.160.1",
  //       "rtt": "9.65"
  //     },
  //     {
  //       "hop_num": 2,
  //       "ip": "70.67.160.1",
  //       "rtt": "9.65"
  //     }
  //   ]
  // }
  return $myVar;
}


?>


