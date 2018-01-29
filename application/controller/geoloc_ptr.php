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
 *** construct the basic GL TR
 ***/
// I need this for my local testing - how do we resolve this?
//$hops = json_decode($_POST["hops"], TRUE);
$hops = $_POST["hops"];
$geolocTr = new GeolocTraceroute();
$geolocTr->setRequestId($ptr->getRequestId());
$geolocTr->setIXmapsId(0);
$geolocTr->setHopCount(count($hops));
$geolocTr->setCompleted(TracerouteUtility::checkIfCompleted($hops, $ptr->getClientIp()));
$geolocTr->setBoomerang(TracerouteUtility::checkIfBoomerang($hops));


/***
 *** construct the hop_data object
 ***/
// TODO: create hops model, move this all in
$overlayData = array();
foreach ($hops as $hop) {
  /*TODO: add ip format validation */
  if($hop["ip"]!=null && $hop["ip"]!=""){
    $myHop = new Geolocation($hop["ip"]);

    $attributeObj = array(
      "asnum" => $myHop->getASNum(),
      "asname" => $myHop->getASName(),
      "country" => $myHop->getCountry(),
      "city" => $myHop->getCity(),
      "nsa" => $myHop->getNsa(),
      //"georeliability" => "",//$myHop->getSource() // Do we want to add the source here?
      "asn_source" => $myHop->getAsnSource(),
      "geodata_source" => $myHop->getGeodataSource()

    );
    $overlayHop = array(
      "hop" => $hop["num"],
      "ip" => $hop["ip"],
      "hostname" => $myHop->getHostname(),
      "lat" => $myHop->getLat(),
      "long" => $myHop->getLong(),
      "attributes" => $attributeObj
    );
    array_push($overlayData, $overlayHop);    
  }

}
$geolocTr->setOverlayData($overlayData);
$geolocTr->setStatus($geolocTr->determineStatus());

$mm->closeDatFiles();

/***
 *** return the GEO-JSON to CIRA
 ***/
GeolocTracerouteUtility::encodeAndReturn($geolocTr);
