<?php
/**
 * This script searches for the closest Geographic data (Country, Region, and city)
 * for pair of coordinates (latitude and longitude).
 *
 * The script is called after a geo-correction has changed lat/long fields in
 * ip_addr_info table.
 *
 *
 * @param None (triggered by cron every 20 min)
 *
 * @return None (updated table ip_addr_info)
 *
 * @since Updated Jul 2020
 * @author IXmaps.ca (Anto, Colin)
 *
 */
// header('Access-Control-Allow-Origin: *');
chdir(dirname(__FILE__));
require_once('../config.php');
require_once('../model/IXmapsGeoCorrection.php');

// look for IPs updated by corr-latlong.sh (p_status = G)
$ipAddrData = IXmapsGeoCorrection::getIpAddrInfo('G');
$p_status = 'F';

// if no, update some of the ips missing a city (p_status = U, mm_city is null). This is now necessary since Maxmind does not always provide a city...
if (!$ipAddrData) {
  $ipAddrData = IXmapsGeoCorrection::getIpAddrInfo('U');
  $p_status = 'U';
}

if (isset($_GET['m'])) {
  $matchLimit = $_GET['m'];
} else {
  $matchLimit = 30;
}

// check if nothing to do
if (!$ipAddrData) {
  echo "\nNothing to do at ".date('Y/m/d H:i:s', strtotime('-4 hours'));
} else {
  // Update geodata
  foreach ($ipAddrData as $key => $ipData) {
    $ipToGeoData = array();
    $ipToGeoData = IXmapsGeoCorrection::getClosestGeoData($ipData, $matchLimit);;

    $bestMatchCountry = array ();
    $bestMatchRegion = array ();
    $bestMatchCity = array ();
    $bestMatchPostal = array ();

    // Add distance to each match
    foreach ($ipToGeoData as $key1 => $geoLocMatch) {
      $latitudeFrom = $ipAddrData[0]['lat'];
      $longitudeFrom = $ipAddrData[0]['long'];
      $latitudeTo = $geoLocMatch['latitude'];
      $longitudeTo = $geoLocMatch['longitude'];
      $distance = IXmapsGeoCorrection::distanceBetweenCoords($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo);
      $ipToGeoData[$key1]['distance'] = $distance;

      // Exclude null country names
      if ($geoLocMatch['region'] != "") {
        if (!isset($bestMatchCountry[$geoLocMatch['region']])) {
          $bestMatchRegion[$geoLocMatch['region']] = 1;
        } else {
          $bestMatchRegion[$geoLocMatch['region']] += 1;
        }
      }

      // Exclude null region names
      if ($geoLocMatch['country'] != "") {
        if (!isset($bestMatchCountry[$geoLocMatch['country']])) {
          $bestMatchCountry[$geoLocMatch['country']] = 1;
        } else {
          $bestMatchCountry[$geoLocMatch['country']] += 1;
        }
      }

      // Exclude null city names
      if ($geoLocMatch['city'] != "") {
        if (!isset($bestMatchCity[$geoLocMatch['city']])) {
          $bestMatchCity[$geoLocMatch['city']] = 1;
        } else {
          $bestMatchCity[$geoLocMatch['city']] += 1;
        }
      }

      // Exclude null postal_code names
      if ($geoLocMatch['postal_code'] != "") {
        if (!isset($bestMatchPostal[$geoLocMatch['postal_code']])) {
          $bestMatchPostal[$geoLocMatch['postal_code']] = 1;
        } else {
          $bestMatchPostal[$geoLocMatch['postal_code']] += 1;
        }
      }

    } // end for find best match

    // add best match geoData
    arsort($bestMatchCountry);
    arsort($bestMatchRegion);
    arsort($bestMatchCity);
    arsort($bestMatchPostal);

    // add best match
    $ipAddrData[$key]["mm_country_update"] = key($bestMatchCountry);
    $ipAddrData[$key]["mm_region_update"] = key($bestMatchRegion);
    $ipAddrData[$key]["mm_city_update"] = key($bestMatchCity);
    $ipAddrData[$key]["mm_postal_update"] = key($bestMatchPostal);

    // add all matches: for reference
    $ipAddrData[$key]['closest_matches'] = $ipToGeoData;

    // update
    $updateGeoData = IXmapsGeoCorrection::updateGeoData($ipAddrData[$key], $p_status);

  } // end for set of ips
  echo "\nCompleted at ".date('Y/m/d H:i:s', strtotime('-4 hours'));;
}