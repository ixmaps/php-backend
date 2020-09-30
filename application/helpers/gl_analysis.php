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

main();

// eventually remake the table with the correct column order?

function main() {
  global $dbconn;
  $outfile = fopen('gl_analysis.csv', 'w');
  /*
    lowest_latency
    lowest_latency_tr_id
  */

  /****** setting up ******/

  $columns =  [
                "ip_addr",
                "count",
                "IX_asn",
                "MM_asn",
                "I2_asn",
                "IX_country",
                "MM_country",
                "II_country",
                "I2_country",
                "IX_city",
                "MM_city",
                "II_city",
                "I2_city",
                "IX_lat",
                "MM_lat",
                "II_lat",
                "I2_lat",
                "IX_long",
                "MM_long",
                "II_long",
                "I2_long",
                "IXMM_distance",
                "IXII_distance",
                "IXI2_distance",
                "MMII_distance",
                "MMI2_distance",
                "III2_distance",
                "IXMMIII2_distance_agreement",
                "IXMMIII2_country_agreement",
                "gl_override",
                "earliest_occurrence",
                "earliest_occurrence_tr_id",
                "latest_occurrence",
                "latest_occurrence_tr_id",
                "lowest_hop",
                "lowest_hop_tr_id"
              ];

  $headers = "";
  foreach($columns as $c) {
    $headers = $headers.$c.",";
  }
  // remove trailing comma
  $headers = substr($headers, 0, -1);
  fwrite($outfile, $headers."\n");



  /****** begin main tasks ******/

  $startTime = microtime(true);
  $ips = dbFetchAll($dbconn);

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
    echo "\nIP2 ASName: ".$ip2loc->getASName();
    $IXMMDistance =  pythagDistanceBetweenPoints($ix["lat"], $ix["long"], $mm->getLat(), $mm->getLong());
    $IXIPDistance =  pythagDistanceBetweenPoints($ix["lat"], $ix["long"], $ipinfo->getLat(), $ipinfo->getLong());
    $IXIP2Distance = pythagDistanceBetweenPoints($ix["lat"], $ix["long"], $ip2loc->getLat(), $ip2loc->getLong());
    $MMIPDistance =  pythagDistanceBetweenPoints($mm->getLat(), $mm->getLong(), $ipinfo->getLat(), $ipinfo->getLong());
    $MMIP2Distance = pythagDistanceBetweenPoints($mm->getLat(), $mm->getLong(), $ip2loc->getLat(), $ip2loc->getLong());
    $IPIP2Distance = pythagDistanceBetweenPoints($ipinfo->getLat(), $ipinfo->getLong(), $ip2loc->getLat(), $ip2loc->getLong());

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

    /****** subselect time *****/
    // make a generic query machine for these, as there will be at least 3

    // $ips = dbFetchFullRouteData($dbconn, $ix["ip_addr"]);
    // function dbFetchFullRouteData($dbconn, $ip_addr) {
    //   $sql = "SELECT * FROM full_routes where";
    //   $result = pg_query($dbconn, $sql) or die('compareIP query failed: ' . pg_last_error());
    //   $ips = pg_fetch_all($result);
    //   pg_free_result($result);

    //   if (!$ips) {
    //     echo "No IPs, exiting...";
    //     exit;
    //   }
    // }

    // duration for one IP: 0.05 base, 1.9 earliest, 3.8 latest, 6.2 lowestHop
    $sql = "SELECT * FROM full_routes WHERE ip_addr = '".$ix["ip_addr"]."' ORDER BY sub_time limit 1";
    $result = pg_query($dbconn, $sql) or die('earliest occurrence query failed: ' . pg_last_error());
    $earliestOccurrence = pg_fetch_all($result)[0];
    pg_free_result($result);
    $sql = "SELECT * FROM full_routes WHERE ip_addr = '".$ix["ip_addr"]."' ORDER BY sub_time desc limit 1";
    $result = pg_query($dbconn, $sql) or die('latest occurrence query failed: ' . pg_last_error());
    $latestOccurrence = pg_fetch_all($result)[0];
    pg_free_result($result);
    $sql = "SELECT * FROM full_routes WHERE ip_addr = '".$ix["ip_addr"]."' ORDER BY hop limit 1";
    $result = pg_query($dbconn, $sql) or die('latest occurrence query failed: ' . pg_last_error());
    $lowestHop = pg_fetch_all($result)[0];
    pg_free_result($result);


    $outputs = array(
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
      $AllCountryFlag,
      $ix["gl_override"],
      $earliestOccurrence["sub_time"],
      $earliestOccurrence["traceroute_id"],
      $latestOccurrence["sub_time"],
      $latestOccurrence["traceroute_id"],
      $lowestHop["hop"],
      $lowestHop["lowest_hop_tr_id"]
    );

    writeToOutfile($outfile, $outputs);
    writeToGlAnalysis($dbconn, $headers, $columns, $outputs);

    $AllCountryFlag = 0;
    $AllDistanceFlag = 0;
  }


  /****** finished main tasks, cleaning up and shutting it down ******/

  $timeElapsedSecs = microtime(true) - $startTime;
  echo "\nDuration: ".$timeElapsedSecs;
  fwrite($outfile, "\nScript duration: ".$timeElapsedSecs);

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


function dbFetchAll($dbconn) {
  $sql = "SELECT c.count,i.* FROM ca_focal_sample c JOIN ip_addr_info i ON c.ip_addr = i.ip_addr WHERE c.ip_addr = '66.163.76.22'  ORDER BY c.count DESC";
  $result = pg_query($dbconn, $sql) or die('compareIP query failed: ' . pg_last_error());
  $ips = pg_fetch_all($result);
  pg_free_result($result);

  if (!$ips) {
    echo "No IPs, exiting...";
    exit;
  }

  // add the check for full_routes
  // select t.*,
  // a.hop,
  // a.ip_addr,
  // a.hostname,
  // a.asnum,
  // a.asname,
  // a.mm_lat,
  // a.mm_long,
  // a.lat,
  // a.long,
  // a.mm_city,
  // a.mm_region,
  // a.mm_country,
  // a.mm_postal,
  // a.gl_override,
  // a.rtt1,
  // a.rtt2,
  // a.rtt3,
  // a.rtt4,
  // a.min_latency,
  // a.transited_country,
  // a.transited_asnum,
  // a.prev_hop_sol_violation,
  // a.origin_sol_violation,
  // a.jittery
  // into full_routes
  // from traceroute_traits t join annotated_traceroutes a on t.traceroute_id = a.traceroute_id
  // order by a.traceroute_id, a.hop;

  return $ips;
}


function writeToGlAnalysis($dbconn, $headers, $columns, $outputs) {
  $placeholders = "";
  foreach($columns as $key => $c) {
    $placeholders = $placeholders."$".strval($key+1).",";
  }
  $placeholders = substr($placeholders, 0, -1);

  $sql = "INSERT INTO gl_analysis (".$headers.") VALUES (".$placeholders.")";
  $result = pg_query_params($dbconn, $sql, $outputs);
  if ($result === false) {
    echo "maxmind_ip_addr insert query failed: " . pg_last_error();
  }
  pg_free_result($result);
}


function writeToOutfile($outfile, $outputs) {
  $row = "";
  foreach($outputs as $output) {
    $row = $row.$output.",";
  }
  $row = substr($row, 0, -1);

  echo "\n".$row;
  fwrite($outfile, $row."\n");
}


function pythagDistanceBetweenPoints($lat1, $long1, $lat2, $long2) {
  if (!empty($lat1) && !empty($long1) && !empty($lat2) && !empty($long2)) {
    $x = abs($lat1 - $lat2);
    $y = abs($long1 - $long2);
    return sqrt(pow($x, 2) + pow($y, 2));
  }
  return 9999;
}