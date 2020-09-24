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

// $ip2loc = new IXmapsIp2Location('209.148.229.229');
// echo "\nInt ip: ".ip2long('209.148.229.229');
// echo "\nNum: ".$ip2loc->getASNum();
// echo "\nName: ".$ip2loc->getASName();
// 3516196325
// 3516194816 | 3516203007
// SELECT * FROM ip2location_asn WHERE ip_from <= 3516196325 and ip_to >= 3516196325;
// die;

compareIps();

function compareIps() {
  global $dbconn;

  $outfile = fopen('gl_analysis.csv', 'w');
  $headers = "IP, count, IX_asn, MM_asn, I2_asn, IX_country, MM_country, II_country, I2_country, IX_city, MM_city, II_city, I2_city, IX_lat, MM_lat, II_lat, I2_lat, IX_long, MM_long, II_long, I2_long, IXMM_distance, IXII_distance, IXI2_distance, MMII_distance, MMI2_distance, III2_distance, IXMMIII2_distance_agreement, IXMMIII2_country_agreement\n";
  fwrite($outfile, $headers);

  $sql = "SELECT c.count,i.* FROM ca_focal_sample c JOIN ip_addr_info i ON c.ip_addr = i.ip_addr ORDER BY c.count DESC";
  $result = pg_query($dbconn, $sql) or die('compareIP query failed: ' . pg_last_error());
  $ips = pg_fetch_all($result);
  pg_free_result($result);

  $startTime = microtime(true);

  if (!$ips) {
    echo "No IPs, exiting...";
    exit;
  }

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

    if ($ix["asnum"] == -1) {
      $ix["asnum"] = NULL;
    }

    $mm = new IXmapsMaxMind($ix["ip_addr"]);
    $ipinfo = new IXmapsIpInfo($ix["ip_addr"]);
    $ip2loc = new IXmapsIp2Location($ix["ip_addr"]);

    echo "\n\n --------------------------------------- \n";
    echo "\nip: ".$ix["ip_addr"];
    echo "\nIX lat: ".$ix["lat"];
    echo "\nIX long: ".$ix["long"];
    echo "\nMM lat: ".$mm->getLat();
    echo "\nMM long: ".$mm->getLong();
    echo "\nIP lat: ".$ipinfo->getLat();
    echo "\nIP long: ".$ipinfo->getLong();
    echo "\nIP2 lat: ".$ip2loc->getLat();
    echo "\nIP2 long: ".$ip2loc->getLong();
    echo "\nIX ASN: ".$ix["asnum"];
    echo "\nMM ASN: ".$mm->getASNum();
    echo "\nIP2 ASN: ".$ip2loc->getASNum();
    $IXMMDistance =  pythagDistanceBetweenPoints($ix["lat"], $ix["long"], $mm->getLat(), $mm->getLong());
    $IXIPDistance =  pythagDistanceBetweenPoints($ix["lat"], $ix["long"], $ipinfo->getLat(), $ipinfo->getLong());
    $IXIP2Distance = pythagDistanceBetweenPoints($ix["lat"], $ix["long"], $ip2loc->getLat(), $ip2loc->getLong());
    $MMIPDistance =  pythagDistanceBetweenPoints($mm->getLat(), $mm->getLong(), $ipinfo->getLat(), $ipinfo->getLong());
    $MMIP2Distance = pythagDistanceBetweenPoints($mm->getLat(), $mm->getLong(), $ip2loc->getLat(), $ip2loc->getLong());
    $IPIP2Distance = pythagDistanceBetweenPoints($ipinfo->getLat(), $ipinfo->getLong(), $ip2loc->getLat(), $ip2loc->getLong());
    echo "\nDistance IXMM: ".$IXMMDistance;
    echo "\nDistance IXIP: ".$IXIPDistance;
    echo "\nDistance MMIP: ".$MMIPDistance;


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

    $row = $ix["ip_addr"].",".$ix["count"].",".
           $ix["asnum"].",".$mm->getASNum().",".$ip2loc->getASNum().",".
           $ix["mm_country"].",".$mm->getCountryCode().",".$ipinfo->getCountryCode().",".$ip2loc->getCountryCode().",".
           $ix["mm_city"].",".$mm->getCity().",".$ipinfo->getCity().",".$ip2loc->getCity().",".
           $ix["lat"].",".$mm->getLat().",".$ipinfo->getLat().",".$ip2loc->getLat().",".
           $ix["long"].",".$mm->getLong().",".$ipinfo->getLong().",".$ip2loc->getLong().",".
           $IXMMDistance.",".$IXIPDistance.",".$IXIP2Distance.",".$MMIPDistance.",".$MMIP2Distance.",".$IPIP2Distance.",".$AllDistanceFlag.",".$AllCountryFlag;

    echo "\n".$row;
    fwrite($outfile, $row."\n");

    $sql = "INSERT INTO gl_analysis (
      ip_addr,
      count,
      IX_asn,
      MM_asn,
      I2_asn,
      IX_country,
      MM_country,
      II_country,
      I2_country,
      IX_city,
      MM_city,
      II_city,
      I2_city,
      IX_lat,
      MM_lat,
      II_lat,
      I2_lat,
      IX_long,
      MM_long,
      II_long,
      I2_long,
      IXMM_distance,
      IXII_distance,
      IXI2_distance,
      MMII_distance,
      MMI2_distance,
      III2_distance,
      IXMMIII2_distance_agreement,
      IXMMIII2_country_agreement
    ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17, $18, $19, $20, $21, $22, $23, $24, $25, $26, $27, $28, $29)";
    $data = array(
      $ix["ip_addr"],
      $ix["count"],
      $ix["asnum"],
      $mm->getASNum(),
      $ip2loc->getASNum(),
      $ix["mm_country"],
      $mm->getCountryCode(),
      $ipinfo->getCountryCode(),
      $ip2loc->getCountryCode(),
      $ix["mm_city"],
      $mm->getCity(),
      $ipinfo->getCity(),
      $ip2loc->getCity(),
      $ix["lat"],
      $mm->getLat(),
      $ipinfo->getLat(),
      $ip2loc->getLat(),
      $ix["long"],
      $mm->getLong(),
      $ipinfo->getLong(),
      $ip2loc->getLong(),
      $IXMMDistance,
      $IXIPDistance,
      $IXIP2Distance,
      $MMIPDistance,
      $MMIP2Distance,
      $IPIP2Distance,
      $AllDistanceFlag,
      $AllCountryFlag
    );
    $result = pg_query_params($dbconn, $sql, $data);
    if ($result === false) {
      echo "maxmind_ip_addr insert query failed: " . pg_last_error();
    }
    pg_free_result($result);


    $AllCountryFlag = 0;
    $AllDistanceFlag = 0;
  }


  $timeElapsedSecs = microtime(true) - $startTime;
  echo "\nDuration: ".$timeElapsedSecs;
  echo "\nTotal IPs: ".sizeof($ips);


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