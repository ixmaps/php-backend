<?php
header('Access-Control-Allow-Origin: *');
include('../config.php');
include('../model/GatherTr.php');           // leaving this for now - we'll need it
include('../model/IXmapsMaxMind.php');
include('../model/GeolocPtr.php');
include('../model/TracerouteUtility.php');

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
$statusObj = GeolocPtr::validateInputPtr($ptrJsonStructure);
if ($statusObj["code"] != 201) {        // TODO make 2xx
  GeolocPtr::returnGeoJson($geoJson, $statusObj);
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
GeolocPtr::returnGeoJson($geoJson);

?>


