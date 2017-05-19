<?php
header('Access-Control-Allow-Origin: *'); 
include('../config.php');
include('../model/IXmapsMaxMind.php'); 
include('../model/IXmapsGeoCorrection.php'); 

if(isset($_GET['ip'])){
	$ip=$_GET['ip'];
} else {
	$ip=$_SERVER['REMOTE_ADDR'];
}

if(isset($_GET['m'])){
	$matchLimit = $_GET['m'];
} else {
	$matchLimit = 5;
}

$mm = new IXmapsMaxMind();
$geoIp = $mm->getGeoIp($ip);
//print_r($geoIp);
$mm->closeDatFiles();

if($geoIp['geoip']['city']==null){
	$ipData = array(
		"lat"=>$geoIp['geoip']['latitude'],
		"long"=>$geoIp['geoip']['longitude']
		);
	$ipToGeoData = IXmapsGeoCorrection::getClosestGeoData($ipData, $matchLimit);
	$bestMatchGeoData = IXmapsGeoCorrection::getBestMatchforGeoData($ipToGeoData);

	$geoIp['best_geodata'] = $bestMatchGeoData;
	$geoIp['matches']=$ipToGeoData;
}

echo json_encode($geoIp);
?>