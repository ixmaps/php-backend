<?php
class IXmapsGeoCorrection
{

  /**
   * Return a set of ip addresses to geo_update_cities
   */
  public static function getIpAddrInfo($kind, $limit = 100)
  {
    global $dbconn;

    // select geo-corrected ips
    if ($kind == '') {
      $sql1 = "SELECT ip_addr, asnum, hostname, lat, long, mm_country, mm_region, mm_city FROM ip_addr_info WHERE p_status='G' LIMIT $limit;";

    // select ips to which MM did not assign a city
    } else if ($kind == '') {
      $sql1 = "SELECT ip_addr, asnum, hostname, lat, long, mm_country, mm_region, mm_city FROM ip_addr_info WHERE p_status='U' and mm_lat is not null and mm_lat is not null and mm_long != 0 and mm_long != 0 and (mm_city is null or mm_city = '' LIMIT $limit;";
    } else {
      throw new Exception('Kind specified incorrectly');
    }

    $result1 = pg_query($dbconn, $sql1);
    $ipAddrInfo = pg_fetch_all($result1);

    return $ipAddrInfo;
  }

  /**
   * Get closest geo data for a given pair of coordinates
   */
  public static function getClosestGeoData($ipData, $limit=5)
  {
    global $dbconn;

    // Get closest geodata for lat/long
    $sql = "SELECT country, region, city, postal_code, latitude, longitude FROM geolite_city_location ORDER BY location <-> st_setsrid(st_makepoint(".$ipData['long'].",".$ipData['lat']."),4326) LIMIT $limit;";
    $result = pg_query($dbconn, $sql) or die('analyzeClosestGeoData failed'.pg_last_error());
    $geodata = pg_fetch_all($result);
    return $geodata;
  }

  /**
   * Updates country, region, and city in ip_addr_info table
   * @param array $ipData geodata
   * @param string $p_status target p_status for the update
   * @return int
   */
  public static function updateGeoData($ipData, $p_status)
  {
    global $dbconn;
    // Update geo data for ip
    $sql = "UPDATE ip_addr_info SET mm_country = '".$ipData['mm_country_update']."', mm_region = '".pg_escape_string($ipData['mm_region_update'])."',  mm_city = '".pg_escape_string($ipData['mm_city_update'])."', mm_postal = '".pg_escape_string([$ipData['mm_postal_update']])."', p_status = '".$p_status."', updated_at = 'NOW()' WHERE ip_addr = '".$ipData['ip_addr']."';";

    $result = pg_query($dbconn, $sql) or die('updateGeoData failed'.pg_last_error());
    pg_free_result($result);
    return 1;
  }

  /**
   * Queries WHOIS db and extract all: Name, Country-Code, and City data
   * @param inet $ip IP address
   * @return array of country and city data
   * CURRENTLY NOT USED
   */
  public static function getWhoisData($ip, $whoisHost="")
  {
    $whoisOutput = shell_exec('whois '.$ip);
    $whoisOutputArr = explode("\n", $whoisOutput);
    echo "\n------------------------\n";
    echo "\n------------------------\n".$whoisOutput;

    if (strpos($whoisOutput, 'Connection reset by peer') !== false) {
      echo "\nError with whois request";
      return 0;
    } else {

      $conn = 0;
      $dataArray = array();
      $contactCounter = 0;
      $itemArray = array(
        "ip_addr"=>$ip,
        "arin_net_name"=>"",
        "arin_country"=>"",
        "arin_city"=>"",
        "contact" => array()
        );

      foreach ($whoisOutputArr as $key => $line) {

        if (strpos($line, 'NetName: ') !== false) {
          $dArray = explode(":", $line);
          $data = $dArray[1];
          $data = trim($data);
          $itemArray["arin_net_name"] = $data;
        }

        if (strpos($line, 'Country: ') !== false) {
          $dArray = explode(":", $line);
          $data = $dArray[1];
          $data = trim($data);
          $itemArray["arin_country"] = $data;
        }

        if (strpos($line, 'City: ') !== false) {
          $dArray = explode(":", $line);
          $data = $dArray[1];
          $data = str_replace("'", " ", $data);
          $data = trim($data);
          $itemArray["arin_city"] = $data;
        }

        if (strpos($line, 'contact:Name:') !== false) {
          $contactCounter++; //!!
          $dArray = explode(":", $line);
          $data = $dArray[2];
          $data = trim($data);
          $data = str_replace("'", " ", $data);
          $itemArray["contact"][$contactCounter]['name'] = $data;
        }

        if (strpos($line, 'contact:Company:') !== false) {
          $dArray = explode(":", $line);
          $data = $dArray[2];
          $data = trim($data);
          $data = str_replace("'", " ", $data);
          $itemArray["contact"][$contactCounter]['company'] = $data;
        }

        if (strpos($line, 'contact:Country-Code:') !== false) {
          $dArray = explode(":", $line);
          $data = $dArray[2];
          $data = trim($data);
          $itemArray["contact"][$contactCounter]['country'] = $data;
        }

        if (strpos($line, 'contact:City:') !== false) {
          $dArray = explode(":", $line);
          $data = $dArray[2];
          $data = trim($data);
          $data = str_replace("'", " ", $data);
          $itemArray["contact"][$contactCounter]['city'] = $data;
        }
      }
      echo "\n";
      print_r($itemArray);
      echo "\n------------------------\n";

      return $itemArray;
    } // end if skip
  }

  /**
   * Uses distance and time to calculate if the hop was faster than speed of light (from origin)
   * @param float $lat1 Latitude of location 1
   * @param float $long1 Longitude of location 1
   * @param float $lat1 Latitude of location 2
   * @param float $long1 Longitude of location 2
   * @param integer $rtt Delta of rtt between to locations (origin rtt is 0)
   * @return boolean if the hop violates speed of light, return true
   */
  public static function doesViolateSol($lat1, $long1, $lat2, $long2, $rtt)
  {
    // Speed of light = 300000 meters / millisecond
    // Speed of light in fiber = 200000 meters / millisecond
    // Speed of light in fiber going both directions (as an rtt) = 100000 meters / millisecond
    $SoL = 100000;
    $distanceMeters = IXmapsGeoCorrection::distanceBetweenCoords($lat1, $long1, $lat2, $long2);

    // Excluding rtt delta of 0 both for div by zero, but also cause that would flag way too many hops
    if ($rtt > 0 && ($distanceMeters/$rtt > $SoL)) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Truth value for hop jitteriness (subjective as hell)
   * @param 'hop' object - TODO: update to a properly fleshed out model
   * @return boolean - if the hop 'is too jittery', return true
   */
  public static function hopHasExcessiveJitter($hop)
  {
    $rttArr = [$hop["rtt1"], $hop["rtt2"], $hop["rtt3"], $hop["rtt4"]];
    // removing all -1s
    $rttArr = array_diff($rttArr, [-1]);
    $rttArr = array_diff($rttArr, [NULL]);

    if (count($rttArr) > 0) {
      $minRtt = min($rttArr);
      rsort($rttArr);
      array_pop($rttArr);
      $diffArr = array();

      foreach ($rttArr as $rtt) {
        array_push($diffArr, ($rtt - $minRtt));
      }

      // If any one of the differences is <= to the sqrt of the min, it is not jittery
      if (count($diffArr) > 0 && min($diffArr) <= 2) {
        return false;
      } else {
        return true;
      }
    } else {
      // I guess a hop with all rtts = -1 or null is not jittery...
      return false;
    }


    // Another option is to just use an abs value of eg 2 for the differences (on all hops)
    // This captures 3442 jittery hops vs sqrt method's 2864 (in first 1000 routes)
    // Sticking with sqrt method for now...
  }

  /**
   * Calculates the great-circle distance between two points, with
   * the Haversine formula.
   * @param float $latitudeFrom Latitude of start point in [deg decimal]
   * @param float $longitudeFrom Longitude of start point in [deg decimal]
   * @param float $latitudeTo Latitude of target point in [deg decimal]
   * @param float $longitudeTo Longitude of target point in [deg decimal]
   * @param float $earthRadius Mean earth radius in [m]
   * @return float Distance between points in [m]
   */
  public static function distanceBetweenCoords(
    $latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
  {
    // convert from degrees to radians
    $latFrom = deg2rad($latitudeFrom);
    $lonFrom = deg2rad($longitudeFrom);
    $latTo = deg2rad($latitudeTo);
    $lonTo = deg2rad($longitudeTo);

    $latDelta = $latTo - $latFrom;
    $lonDelta = $lonTo - $lonFrom;

    $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
      cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
    return $angle * $earthRadius;
  }
}
?>