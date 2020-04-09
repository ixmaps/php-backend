<?php
/**
 *
 * This gets hit on load by:
 * - Frontend Map page on load
 * - eventually IXmapsClient, once we can recompile it
 *
 * @return structured geoloc values for front end user's ip
 *
 * Updated Aug 2019
 * @author IXmaps.ca (Anto, Colin)
 *
 */
header("Access-Control-Allow-Origin: *");
require_once('../model/IXmapsMaxMind.php');

$myIp = $_SERVER['REMOTE_ADDR'];
// $myIp = "216.66.39.113"; // Pendergrass
// $myIp = "4.15.136.14"; // Wichita
// $myIp = "183.89.98.35"; // Thailand
$defaultVal = false;

// it would be better to skip this if the IP is null... it's confusing to users to choose a random value look at where this is called. For now I've added a bool to identify the returned object as using the default
if (ip2long($myIp) === false) {
  $myIp = "216.66.39.113";
  $defaultVal = true;
}

$mm = new IXmapsMaxMind($myIp);

$result = array(
  "myIp" => $myIp,
  "myCountry" => $mm->getCountryCode(),
  "myCountryName" => $mm->getCountry(),
  "myCity" => $mm->getCity(),
  "myPostalCode" => $mm->getPostalCode(),
  "myAsn" => $mm->getASNum(),
  "myIsp" => $mm->getASName(),
  "myLat" => $mm->getLat(),
  "myLong" => $mm->getLong(),
  "defaultValue" => $defaultVal
);

echo json_encode($result);
?>