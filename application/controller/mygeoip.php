<?php
/**
 *
 * This gets hit on load by:
 * - Map page on load
 * - IXmapsClient - NO! Not yet at least
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
// $myIp = "66.163.72.177"; // Toronto
// $myIp = "4.15.136.14"; // Wichita
// $myIp = "183.89.98.35"; // Thailand

// yikes. If myIp is null the returned JSON is malformed. Adding some error handling, but defaulting to random IP in Toronto still isn't great - open to suggestions

// it would be better to skip this if the IP is null... it's confusing to users to choose a random value look at where this is called
if (ip2long($myIp) === false) {
  $myIp = "66.163.72.177";
}

$mm = new IXmapsMaxMind($myIp);

$result = array(
  "myIp" => $myIp,
  "myCountry" => $mm->getCountryCode(),
  "myCountryName" => $mm->getCountry(),
  "myCity" => $mm->getCity(),
  "myAsn" => $mm->getASNum(),
  "myIsp" => $mm->getASName(),
  "myLat" => $mm->getLat(),
  "myLong" => $mm->getLong()
);

echo json_encode($result);
?>