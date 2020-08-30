<?php
/**
 * Evolving script to compare IP address values between different data sources
 *
 */

chdir(dirname(__FILE__));
require_once('../config.php');
require_once('../model/IXmapsMaxMind.php');
require_once('../model/IXmapsIpInfo.php');
require_once('../model/IXmapsIp2Location.php');

compareIps();

function compareIps() {
  global $dbconn;

  $outfile = fopen('ip_compare_outfile.csv', 'w');
  $headers = "IP, IX_country, MM_country, IP_country, IP2_country, IX_city, MM_city, IP_city, IP2_city, IX_lat, MM_lat, IP_lat, ip2_lat, IX_long, MM_long, IP_long, IP2_long, IXMM_distance, IXIP_distance, IXIP2_distance, MMIP_distance, MMIP2_distance, IPIP2_distance, IXMMIPIP2_distance_agreement, IXMMIPIP2_country_agreement\n";
  // IXMM_country, IXIP_country, IXIP2_country, MMIP_country, MMIP2_country, IPIP2_country, IXMMIPIP2_country_agreement,
  fwrite($outfile, $headers);

  // ca_focal_sample
  $sql = "SELECT * FROM ip_addr_info where ip_addr in (select ip_addr from temp1)";
  $result = pg_query($dbconn, $sql) or die('compareIP query failed: ' . pg_last_error());
  $ips = pg_fetch_all($result);
  pg_free_result($result);

  $startTime = microtime(true);

  if (!$ips) {
    echo "No IPs, exiting...";
    exit;
  }

  // $IxEqMmCountryCount      = 0;
  // $IxEqIpInfoCountryCount  = 0;
  // $MmEqIpInfoCountryCount  = 0;
  $AllEqCountryCount           = 0;
  $IxEqMmDistanceCount         = 0;
  $IxEqIpInfoDistanceCount     = 0;
  $IxEqIp2LocDistanceCount     = 0;
  $MmEqIpInfoDistanceCount     = 0;
  $MmEqIp2LocDistanceCount     = 0;
  $IpInfoEqIp2LocDistanceCount = 0;
  $AllEqDistanceCount          = 0;

  foreach ($ips as $key => $ix) {
    $AllCountryFlag = 0;
    $AllDistanceFlag = 0;

    $mm = new IXmapsMaxMind($ix["ip_addr"]);
    $ipinfo = new IXmapsIpInfo($ix["ip_addr"]);
    $ip2loc = new IXmapsIp2Location($ix["ip_addr"]);

    echo "\nip: ".$ix["ip_addr"];
    echo "\nIX lat: ".$ix["lat"];
    echo "\nIX long: ".$ix["long"];
    echo "\nMM lat: ".$mm->getLat();
    echo "\nMM long: ".$mm->getLong();
    echo "\nIP lat: ".$ipinfo->getLat();
    echo "\nIP long: ".$ipinfo->getLong();
    echo "\nIP2 lat: ".$ip2loc->getLat();
    echo "\nIP2 long: ".$ip2loc->getLong();
    $IXMMDistance =  pythagDistanceBetweenPoints($ix["lat"], $ix["long"], $mm->getLat(), $mm->getLong());
    $IXIPDistance =  pythagDistanceBetweenPoints($ix["lat"], $ix["long"], $ipinfo->getLat(), $ipinfo->getLong());
    $IXIP2Distance = pythagDistanceBetweenPoints($ix["lat"], $ix["long"], $ip2loc->getLat(), $ip2loc->getLong());
    $MMIPDistance =  pythagDistanceBetweenPoints($mm->getLat(), $mm->getLong(), $ipinfo->getLat(), $ipinfo->getLong());
    $MMIP2Distance = pythagDistanceBetweenPoints($mm->getLat(), $mm->getLong(), $ip2loc->getLat(), $ip2loc->getLong());
    $IPIP2Distance = pythagDistanceBetweenPoints($ipinfo->getLat(), $ipinfo->getLong(), $ip2loc->getLat(), $ip2loc->getLong());
    echo "\nDistance IXMM: ".$IXMMDistance;
    echo "\nDistance IXIP: ".$IXIPDistance;
    echo "\nDistance MMIP: ".$MMIPDistance;

    // if ($ix["mm_country"] == $mm->getCountryCode()) {
    //   $IxEqMmCountryCount++;
    // }
    // if ($ix["mm_country"] == $ipinfo->getCountryCode()) {
    //   $IxEqIpInfoCountryCount++;
    // }
    // if ($mm->getCountryCode() == $ipinfo->getCountryCode()) {
    //   $MmEqIpInfoCountryCount++;
    // }
    if ($ix["mm_country"] == $mm->getCountryCode() &&
        $ix["mm_country"] == $ipinfo->getCountryCode() &&
        $ix["mm_country"] == $ip2loc->getCountryCode() &&
        $mm->getCountryCode() == $ipinfo->getCountryCode() &&
        $mm->getCountryCode() == $ip2loc->getCountryCode() &&
        $ipinfo->getCountryCode() == $ip2loc->getCountryCode())
    {
      $AllEqCountryCount++;
      $AllCountryFlag = 1;
    }

    if ($IXMMDistance <= 25) {
      $IxEqMmDistanceCount++;
    }
    if ($IXIPDistance <= 25) {
      $IxEqIpInfoDistanceCount++;
    }
    if ($IXIP2Distance <= 25) {
      $IxEqIp2LocDistanceCount++;
    }
    if ($MMIPDistance <= 25) {
      $MmEqIpInfoDistanceCount++;
    }
    if ($MMIP2Distance <= 25) {
      $MmEqIp2LocDistanceCount++;
    }
    if ($MMIP2Distance <= 25) {
      $IpInfoEqIp2LocDistanceCount++;
    }

    if ($IXMMDistance <= 25 && $IXIPDistance <= 25 && $MMIPDistance <= 25) {
      $AllEqDistanceCount++;
      $AllDistanceFlag = 1;
    }

    $row = $ix["ip_addr"].",".
           $ix["mm_country"].",".$mm->getCountryCode().",".$ipinfo->getCountryCode().",".$ip2loc->getCountryCode().",".
           $ix["mm_city"].",".$mm->getCity().",".$ipinfo->getCity().",".$ip2loc->getCity().",".
           $ix["lat"].",".$mm->getLat().",".$ipinfo->getLat().",".$ip2loc->getLat().",".
           $ix["long"].",".$mm->getLong().",".$ipinfo->getLong().",".$ip2loc->getLong().",".
           $IXMMDistance.",".$IXIPDistance.",".$IXIP2Distance.",".$MMIPDistance.",".$MMIP2Distance.",".$IPIP2Distance.",".$AllDistanceFlag.",".$AllCountryFlag;

    echo "\n".$row;
    fwrite($outfile, $row."\n");

    $AllCountryFlag = 0;
    $AllDistanceFlag = 0;
  }


  $timeElapsedSecs = microtime(true) - $startTime;
  echo "\nDuration: ".$timeElapsedSecs;
  echo "\nTotal IPs: ".sizeof($ips);
  // echo "\nIX equals MM country count: ".$IxEqMmCountryCount;
  // fwrite($outfile, "\nIX equals MM country count: ".$IxEqMmCountryCount);
  // echo "\nIX equals IP country count: ".$IxEqIpInfoCountryCount;
  // fwrite($outfile, "\nIX equals IP country count: ".$IxEqIpInfoCountryCount);
  // echo "\nMM equals IP country count: ".$MmEqIpInfoCountryCount;
  // fwrite($outfile, "\nMM equals IP country count: ".$MmEqIpInfoCountryCount);
  echo "\nAll equal country count: ".$AllEqCountryCount;
  fwrite($outfile, "\nAll equal country count: ".$AllEqCountryCount);

  echo "\nIX within distance MM count: ".$IxEqMmDistanceCount;
  fwrite($outfile, "\nIX within distance MM count: ".$IxEqMmDistanceCount);
  echo "\nIX within distance IPInfo count: ".$IxEqIpInfoDistanceCount;
  fwrite($outfile, "\nIX within distance IPInfo count: ".$IxEqIpInfoDistanceCount);
  echo "\nIX within distance IP2Loc count: ".$IxEqIp2LocDistanceCount;
  fwrite($outfile, "\nIX within distance IP2Loc count: ".$IxEqIp2LocDistanceCount);

  echo "\nMM within distance IPInfo count: ".$MmEqIpInfoDistanceCount;
  fwrite($outfile, "\nMM within distance IPInfo count: ".$MmEqIpInfoDistanceCount);
  echo "\nMM within distance IP2Loc count: ".$MmEqIp2LocDistanceCount;
  fwrite($outfile, "\nMM within distance IP2Loc count: ".$MmEqIp2LocDistanceCount);

  echo "\nIPInfo within distance IP2Loc count: ".$IpInfoEqIp2LocDistanceCount;
  fwrite($outfile, "\nIPInfo within distance IP2Loc count: ".$IpInfoEqIp2LocDistanceCount);

  echo "\nAll within distance count: ".$AllEqDistanceCount;
  fwrite($outfile, "\nAll within distance count: ".$AllEqDistanceCount);

  fclose($outfile);
  pg_close($dbconn);
}


function pythagDistanceBetweenPoints($lat1, $long1, $lat2, $long2) {
  if (!empty($lat1) && !empty($long1) && !empty($lat2) && !empty($long2)) {
    $x = abs($lat1 - $lat2);
    $y = abs($long1 - $long2);
    return sqrt(pow($x, 2) + pow($y, 2));
  }
  return 9999;
}