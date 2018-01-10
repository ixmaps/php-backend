<?php
header('Access-Control-Allow-Origin: *');
include('../config.php');
include('../model/IXmapsMaxMind.php');
include('../model/Geolocation.php');
include('../model/TracerouteUtility.php');
include('../model/ParisTraceroute.php');
include('../model/ParisTracerouteUtility.php');
include('../model/GeolocTraceroute.php');
include('../model/GeolocTracerouteUtility.php');
include('../model/ResponseCode.php');

/***
 *** validate the incoming JSON
 ***/
if (ParisTracerouteUtility::isValid($_POST)) {
  $ptr = new ParisTraceroute($_POST);
} else {
  // handle malformed json submission
  $geolocTr = new GeolocTraceroute();
  $geolocTr->setStatus(ParisTracerouteUtility::determineStatus($_POST));
  GeolocTracerouteUtility::encodeAndReturn($geolocTr);
}
$mm = new IXmapsMaxMind();

/***
 *** send the json to ingest_tr_cira (phase 2)
 ***/



/***
 *** figure out which pass we want to use, currently just using first pass
 ***/
// NB: we cannot necessarily assume the hop_data array is ordered (eg pass1 != hopdata[0])
// $trByHop = GatherTr::analyzeIfInconsistentPasses($trData); maybe? Anto?
$hopData = json_decode($_POST["hop_data"], TRUE);
$chosenPass = $hopData[0];
$hops = $chosenPass["hops"];


/***
 *** construct the basic GL TR
 ***/
$geolocTr = new GeolocTraceroute();
$geolocTr->setRequestId($ptr->getRequestId());
$geolocTr->setIXmapsId(0);
$geolocTr->setHopCount(count($hops));
$geolocTr->setTerminate($chosenPass["terminate"]);
$geolocTr->setBoomerang(TracerouteUtility::checkIfBoomerang($hops));


/***
 *** construct the hop_data object
 ***/
// TODO: create hops model, move this all in
$overlayData = array();
foreach ($hops as $hop) {
  $myHop = new Geolocation($hop["ip"]);

  $attributeObj = array(
    "asnum" => $myHop->getASNum($hop["ip"]),
    "asname" => $myHop->getASName($hop["ip"]),
    "country" => $myHop->getCountry($hop["ip"]),
    "nsa" => "TODO",
    "georeliability" => "TBD"
  );
  $overlayHop = array(
    "hop" => $hop["hop_num"],
    "lat" => $myHop->getLat(),
    "long" => $myHop->getLong(),
    "attributes" => $attributeObj
  );
  array_push($overlayData, $overlayHop);
}

$geolocTr->setOverlayData($overlayData);
$geolocTr->setStatus($geolocTr->determineStatus());

$mm->closeDatFiles();

/***
 *** return the GEO-JSON to CIRA
 ***/
GeolocTracerouteUtility::encodeAndReturn($geolocTr);
