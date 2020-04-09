<?php
class IXmapsGeoCorrection
{

  public static function getIpAddrInfo($limit, $type=0, $ip='', $offset=0, $asn=0)
  {
    global $dbconn;

    // select geo-corrected ips
    if ($type == 1) {
      $sql1 = "SELECT ip_addr, asnum, hostname, lat, long, mm_country, mm_region, mm_city FROM ip_addr_info WHERE p_status='G' LIMIT $limit;";

    // select an ip
    } else if ($type == 2) {
      $sql1 = "SELECT ip_addr, asnum, hostname, lat, long, mm_country, mm_region, mm_city FROM ip_addr_info WHERE ip_addr = '".$ip."';";

    // select ips not geo corrected, with valid coordinates, but with no city name
    } else if ($type == 3) {
      $sql1 = "SELECT ip_addr, gl_override, p_status, asnum, hostname, lat, long, mm_country, mm_region, mm_city FROM ip_addr_info WHERE mm_city = '' and p_status<>'F' and p_status<>'G' and p_status<>'U' and lat <> 0 and gl_override is not null order by ip_addr OFFSET $offset LIMIT $limit;";

    // select ips by asn
    } else if ($type == 4) {
      $sql1 = "SELECT ip_addr, gl_override, p_status, asnum, hostname, lat, long, mm_country, mm_region, mm_city FROM ip_addr_info WHERE mm_city = '' and p_status<>'F' and p_status<>'G' and p_status<>'U' and lat <> 0 and gl_override is null and asn=$asn order by ip_addr OFFSET $offset LIMIT $limit;";
    }

    $result1 = pg_query($dbconn, $sql1);
    $ipAddrInfo = pg_fetch_all($result1);

    return $ipAddrInfo;
  }


  /**
  * Update IP address LOG.
    This function performs the following for each ip on ip_addr_info:

    1) Gets current lat, long, mm_lat, mm_log from ip_addr_info table
    2) Queries MM latest DB and extracts geo-data: lat, long, city, country, ... etc
    3) Calculates geo-location and geo-correction distance between 3 points.
      a. old MM
      b. current geo data in IXmaps db
      c. latest geo data MM db


      UPDATE AUG 2019 - this appears to be legacy, is now broken with GeoLite2 (I'm not updating it until it's proved necessary)
  */
  // public static function insertLogIpAddrInfo($data)
  // {
  //   global $dbconn, $appPath;
  //   require_once($appPath.'/model/IXmapsMaxMind.php');

  //   $mm = new IXmapsMaxMind();

  //   $columns = array('ip_addr', 'asnum', 'mm_lat', 'mm_long', 'hostname', 'mm_country', 'mm_region', 'mm_city', 'mm_postal', 'mm_area_code', 'mm_dma_code', 'p_status', 'lat', 'long', 'gl_override', 'flagged', 'created_at', 'modified_at', 'updated_asn', 'updated_mm_lat', 'updated_mm_long', 'updated_mm_country', 'updated_mm_region', 'updated_mm_city', 'updated_mm_postal', 'updated_mm_area_code', 'updated_mm_dma_code', 'updated_mm_asn', 'dis_mm_first_updated', 'dis_mm_first_corrected', 'dis_mm_updated_corrected');

  //   foreach ($data as $key => $ip) {

  //     $sql = "INSERT INTO log_ip_addr_info (ip_addr, asnum, mm_lat, mm_long, hostname, mm_country, mm_region, mm_city, mm_postal, mm_area_code, mm_dma_code, p_status, lat, long, gl_override, flagged, created_at, modified_at, updated_asn, updated_hostname, updated_mm_lat, updated_mm_long, updated_mm_country, updated_mm_region, updated_mm_city, updated_mm_postal, updated_mm_area_code, updated_mm_dma_code, dis_mm_first_updated, dis_mm_first_corrected, dis_mm_updated_corrected) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17, $18, $19, $20, $21, $22, $23, $24, $25, $26, $27, $28, $29, $30, $31);";

  //     // Get geo data
  //     $geoIp = $mm->getGeoIp($ip['ip_addr']);
  //     //print_r($geoIp);

  //     $ip['updated_asn'] = $geoIp['asn'];
  //     $ip['updated_hostname'] = $geoIp['hostname'];
  //     $ip['updated_mm_lat'] = $geoIp['geoip']['latitude'];
  //     $ip['updated_mm_long'] = $geoIp['geoip']['longitude'];
  //     $ip['updated_mm_country'] = $geoIp['geoip']['country_code'];
  //     $ip['updated_mm_region'] = $geoIp['geoip']['region'];
  //     $ip['updated_mm_city'] = $geoIp['geoip']['city'];
  //     $ip['updated_mm_postal'] = $geoIp['geoip']['postal_code'];
  //     $ip['updated_mm_area_code'] = $geoIp['geoip']['area_code'];
  //     $ip['updated_mm_dma_code'] = $geoIp['geoip']['dma_code'];

  //     $ip['dis_mm_first_updated'] = round(IXmapsGeoCorrection::distanceBetweenCoords(
  //       $ip['mm_lat'], $ip['mm_long'], $geoIp['geoip']['latitude'], $geoIp['geoip']['longitude']));

  //     $ip['dis_mm_first_corrected'] = round(IXmapsGeoCorrection::distanceBetweenCoords(
  //       $ip['mm_lat'], $ip['mm_long'], $ip['lat'], $ip['long']));

  //     $ip['dis_mm_updated_corrected']  = round(IXmapsGeoCorrection::distanceBetweenCoords(
  //       $geoIp['geoip']['latitude'], $geoIp['geoip']['longitude'], $ip['lat'], $ip['long']));

  //   $sqlParams = array ($ip['ip_addr'], $ip['asnum'], $ip['mm_lat'], $ip['mm_long'], $ip['hostname'], $ip['mm_country'], $ip['mm_region'], $ip['mm_city'], $ip['mm_postal'], $ip['mm_area_code'], $ip['mm_dma_code'], $ip['p_status'], $ip['lat'], $ip['long'], $ip['gl_override'], $ip['flagged'], $ip['datecreated'], $ip['datemodified'], $ip['updated_asn'], $ip['updated_hostname'],  $ip['updated_mm_lat'], $ip['updated_mm_long'], $ip['updated_mm_country'], $ip['updated_mm_region'], $ip['updated_mm_city'], $ip['updated_mm_postal'], $ip['updated_mm_area_code'], $ip['updated_mm_dma_code'], $ip['dis_mm_first_updated'], $ip['dis_mm_first_corrected'], $ip['dis_mm_updated_corrected']);

  //     //echo "\n".$sql ;
  //     //print_r($sqlParams);

  //     $result = pg_query_params($dbconn, $sql, $sqlParams) or die('insertLogIpAddrInfo failed'.pg_last_error());

  //     pg_free_result($result);

  //     $lastIp = $ip['ip_addr'];

  //   } // end foreach

  //   $mm->closeDatFiles();
  //   return $lastIp;
  // }

  /**
   * Get Closest Geo Data for a given pair of coordinates
   */
  public static function getClosestGeoData($ipData, $limit=5)
  {
    global $dbconn;

    // Get closest geodata for lat/long
    $sql = "SELECT country, region, city, latitude, longitude FROM geolite_city_location ORDER BY location <-> st_setsrid(st_makepoint(".$ipData['long'].",".$ipData['lat']."),4326) LIMIT $limit;";
    $result = pg_query($dbconn, $sql) or die('analyzeClosestGeoData failed'.pg_last_error());
    $geodata = pg_fetch_all($result);
    return $geodata;
  }

  /**
   * Updates country, region, and city in ip_addr_info table
   * @param array $ipData Geodata
   * @param string $p_status target p_status for the update
   * @return int
   */
  public static function updateGeoData($ipData, $p_status='F')
  {
    global $dbconn;
    // Update geo data for ip
    $sql = "UPDATE ip_addr_info SET mm_country = '".$ipData['mm_country_update']."', mm_region = '".$ipData['mm_region_update']."',  mm_city = '".$ipData['mm_city_update']."', p_status = '".$p_status."', modified_at = 'NOW()' WHERE ip_addr = '".$ipData['ip_addr']."';";

    $result = pg_query($dbconn, $sql) or die('updateGeoData failed'.pg_last_error());
    pg_free_result($result);
    return 1;
  }

  /**
   * Updates arin whois data on table log_ip_addr_info
   * CURRENTLY NOT USED
   */
  // public static function updateArinWhois($whoisData)
  // {
  //   global $dbconn;

  //   $sql = "UPDATE log_ip_addr_info SET arin_net_name='".$whoisData['arin_net_name']."', arin_country = '".$whoisData['arin_country']."', arin_city = '".$whoisData['arin_city']."',  arin_contact = '".json_encode($whoisData['contact'])."', arin_updated=1 WHERE ip_addr = '".$whoisData['ip_addr']."';";

  //   $result = pg_query($dbconn, $sql) or die('updateArinWhois failed'.pg_last_error());

  //   pg_free_result($result);
  // }

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
   * @return boolean if the hop 'is too jittery', return true
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