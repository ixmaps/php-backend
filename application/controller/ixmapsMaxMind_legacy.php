<?php
/**
 *
 * TODO - what does this piece do? Is it legacy?
 *
 * @param ?
 *
 * @return some json to something?
 *
 * @author IXmaps.ca (Colin, Antonio)
 * @since 2018 Jan 1 (last updated Aug 2019)
 *
 */
header('Access-Control-Allow-Origin: *');
require_once('../config.php');
require_once('../model/IXmapsMaxMind.php');
require_once('../model/IXmapsGeoCorrection.php');

if (isset($_GET['ip'])) {
  $ip = $_GET['ip'];
} else {
  $ip = $_SERVER['REMOTE_ADDR'];
}

if (isset($_GET['m'])) {
  $matchLimit = $_GET['m'];
} else {
  $matchLimit = 5;
}

$mm = new IXmapsMaxMind($ip);

if ($mm->getCity() == null) {
  $ipData = array(
    "lat" => $mm->getLat(),
    "long" => $mm->getLong()
  );
  $ipToGeoData = IXmapsGeoCorrection::getClosestGeoData($ipData, $matchLimit);
  $bestMatchGeoData = IXmapsGeoCorrection::getBestMatchforGeoData($ipToGeoData);

  // GAVE UP HERE
  $geoIp['best_geodata'] = $bestMatchGeoData;
  $geoIp['matches'] = $ipToGeoData;
}

echo json_encode($geoIp);
?>