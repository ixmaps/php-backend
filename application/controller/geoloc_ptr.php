<?php
/**
 *
 * This controller acts as an API to handle geolocation POST requests
 *
 * Input is 'ptr' JSON structure, output is a geolocated JSON structure
 *
 * @author IXmaps.ca (Colin, Antonio)
 * @since 2018 Jan 1
 *
 */
header('Access-Control-Allow-Origin: *');
include('../config.php');
include('../model/IXmapsMaxMind.php');
include('../model/Geolocation.php');
include('../model/TracerouteUtility.php');
include('../model/ParisTraceroute.php');
include('../model/ParisTracerouteFactory.php');
include('../model/GeolocTraceroute.php');
include('../model/ResponseCode.php');

/***
 *** validate the incoming PTR JSON and build ptr object
 ***/
$ptr = ParisTracerouteFactory::build(file_get_contents('php://input'));
$mm = new IXmapsMaxMind();


/***
 *** send the json to ingest_tr_cira (phase 2)
 ***/


/***
 *** construct the basic GL TR
 ***/
$geolocTr = new GeolocTraceroute();
$hops = $ptr->getHops();
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
  // TODO: add ip format validation
  if($hop["ip"]!=null && $hop["ip"]!=""){

    $myHop = new Geolocation($hop["ip"]);

    $attributeObj = array(
      "asnum" => $myHop->getASNum(),
      "asname" => $myHop->getASName(),
      "country" => $myHop->getCountry(),
      "city" => $myHop->getCity(),
      "nsa" => $myHop->getNsa(),
      "asn_source" => $myHop->getAsnSource(),
      "geo_source" => $myHop->getGeoSource()
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
echo json_encode($geolocTr);