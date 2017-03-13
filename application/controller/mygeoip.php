<?php
include('../config.php');
include('../model/IXmapsMaxMind.php');

$myIp = $_SERVER['REMOTE_ADDR'];
//$myIp = "186.108.108.134"; // Buenos Aires: TEST
//$myIp = "128.100.72.189"; // Toronto: TEST
//$myIp = "66.163.72.177"; // Toronto
//$myIp = "4.15.136.14"; // Wichita

$mm = new IXmapsMaxMind();
$geoIp = $mm->getGeoIp($myIp);
//print_r($geoIp);
//$geoIpByRadidu = $mm->getGeoDataByPopulationRadius($geoIp);
//print_r($geoIpByRadidu);

$mm->closeDatFiles();

$myCountry = "";
$myCity = "";
$myAsn = "";
$myIsp = "";
$myLat = "";
$myLong = "";

if(isset($geoIp['geoip']['country_code'])){
    $myCountry = $geoIp['geoip']['country_code'];
}
if(isset($geoIp['geoip']['city'])){
    $myCity = $geoIp['geoip']['city'];
}
if(isset($geoIp['asn'])){
    $myAsn = $geoIp['asn'];
}
if(isset($geoIp['isp'])){
    $myIsp = $geoIp['isp'];
}
if(isset($geoIp['geoip']['latitude'])){
    $myLat = $geoIp['geoip']['latitude'];
}
if(isset($geoIp['geoip']['longitude'])){
    $myLong = $geoIp['geoip']['longitude'];
}

/* Testing find most populated city in a radius*/
if($myCity==""){

}

$result = array(
	"myIp" => $myIp,
	"myCountry" => $myCountry,
	"myCity" => $myCity,
	"myAsn" => $myAsn,
	"myIsp" => $myIsp,
	"myLat" => $myLat,
	"myLong" => $myLong
	);

echo json_encode($result);
?>