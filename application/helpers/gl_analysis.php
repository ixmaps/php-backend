<?php
/**
 * Evolving script to compare IP address values between different data sources
 *
 */

chdir(dirname(__FILE__));
require_once('../config.php');
require_once('../model/Geolocation.php');
require_once('../model/IXmapsGeoCorrection.php');

// eventually remake the table with the correct column order?

function main() {
  global $dbconn;

  $METRO_AREA = 25;
  $DATA_SOURCES = ["IX", "MM", "II", "I2"];


  /****** setting up ******/

  $outfile = fopen('gl_analysis.csv', 'w');
  $columns =  [
                "ip_addr",
                "count",
                "IX_hostname",
                "MM_hostname",
                "II_hostname",
                "hostname_inconsistent",
                "non_IX_hostname_inconsistent",
                "IX_asn",
                "IX_asn_dev",
                "MM_asn",
                "MM_asn_dev",
                "I2_asn",
                "I2_asn_dev",
                "IX_country",
                "IX_country_dev",
                "MM_country",
                "MM_country_dev",
                "II_country",
                "II_country_dev",
                "I2_country",
                "I2_country_dev",
                "IX_updated_country",
                "countries_inconsistent",
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
                "created_at",
                "updated_at",
                "gl_override",
                "earliest_occurrence",
                "earliest_occurrence_tr_id",
                "latest_occurrence",
                "latest_occurrence_tr_id",
                "lowest_hop",
                "lowest_hop_tr_id",
                "lowest_latency",
                "lowest_latency_tr_id",
                "percent_prev_hop_sol_violation",
                "percent_origin_sol_violation",
                "percent_jittery"
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

    $geoloc = new Geolocation($ix["ip_addr"]);

    echo "\n\n --------------------------------------- \n";
    echo "\nIP: ".$ix["ip_addr"];
    echo "\nIX ASN: ".$geoloc->getIXASnum();
    echo "\nMM ASN: ".$geoloc->getMMASNum();
    echo "\nIP2 ASN: ".$geoloc->getI2ASNum();
    echo "\nIX lat: ".$geoloc->getIXLat();
    echo "\nIX long: ".$geoloc->getIXLong();
    echo "\nMM lat: ".$geoloc->getMMLat();
    echo "\nMM long: ".$geoloc->getMMLong();
    echo "\nIP lat: ".$geoloc->getIILat();
    echo "\nIP long: ".$geoloc->getIILong();
    echo "\nIP2 lat: ".$geoloc->getI2Lat();
    echo "\nIP2 long: ".$geoloc->getI2Long();
    $IXMMDistance = distanceBetweenPoints($geoloc->getIXLat(), $geoloc->getIXLong(), $geoloc->getMMLat(), $geoloc->getMMLong());
    $IXIIDistance = distanceBetweenPoints($geoloc->getIXLat(), $geoloc->getIXLong(), $geoloc->getIILat(), $geoloc->getIILong());
    $IXI2Distance = distanceBetweenPoints($geoloc->getIXLat(), $geoloc->getIXLong(), $geoloc->getI2Lat(), $geoloc->getI2Long());
    $MMIIDistance = distanceBetweenPoints($geoloc->getMMLat(), $geoloc->getMMLong(), $geoloc->getIILat(), $geoloc->getIILong());
    $MMI2Distance = distanceBetweenPoints($geoloc->getMMLat(), $geoloc->getMMLong(), $geoloc->getI2Lat(), $geoloc->getI2Long());
    $III2Distance = distanceBetweenPoints($geoloc->getIILat(), $geoloc->getIILong(), $geoloc->getI2Lat(), $geoloc->getI2Long());

    if ($geoloc->getIXCountryCode() == $geoloc->getMMCountryCode() &&
        $geoloc->getIXCountryCode() == $geoloc->getIICountryCode() &&
        $geoloc->getIXCountryCode() == $geoloc->getI2CountryCode() &&
        $geoloc->getMMCountryCode() == $geoloc->getIICountryCode() &&
        $geoloc->getMMCountryCode() == $geoloc->getI2CountryCode() &&
        $geoloc->getIICountryCode() == $geoloc->getI2CountryCode())
    {
      $AllEqCountryCount++;
      $AllCountryFlag = 1;
    }

    if ($IXMMDistance <= $METRO_AREA) {
      $IxEqMmDistanceCount++;
    }
    if ($IXIIDistance <= $METRO_AREA) {
      $IxEqIpInfoDistanceCount++;
    }
    if ($IXI2Distance <= $METRO_AREA) {
      $IxEqIp2LocDistanceCount++;
    }
    if ($MMIIDistance <= $METRO_AREA) {
      $MmEqIpInfoDistanceCount++;
    }
    if ($MMI2Distance <= $METRO_AREA) {
      $MmEqIp2LocDistanceCount++;
    }
    if ($MMI2Distance <= $METRO_AREA) {
      $IpInfoEqIp2LocDistanceCount++;
    }
    if ($IXMMDistance <= $METRO_AREA && $IXIIDistance <= $METRO_AREA && $MMIIDistance <= $METRO_AREA &&
        $IXI2Distance <= $METRO_AREA && $MMI2Distance <= $METRO_AREA && $III2Distance <= $METRO_AREA)
    {
      $AllEqDistanceCount++;
      $AllDistanceFlag = 1;
    }

    /****** subselect time *****/
    $sql = "SELECT * FROM full_routes_gl_analysis WHERE ip_addr = '".$ix["ip_addr"]."' ORDER BY sub_time limit 1";
    $result = pg_query($dbconn, $sql) or die('earliest occurrence query failed: ' . pg_last_error());
    $earliestOccurrence = pg_fetch_all($result)[0];
    pg_free_result($result);

    $sql = "SELECT * FROM full_routes_gl_analysis WHERE ip_addr = '".$ix["ip_addr"]."' ORDER BY sub_time desc limit 1";
    $result = pg_query($dbconn, $sql) or die('latest occurrence query failed: ' . pg_last_error());
    $latestOccurrence = pg_fetch_all($result)[0];
    pg_free_result($result);

    $sql = "SELECT * FROM full_routes_gl_analysis WHERE ip_addr = '".$ix["ip_addr"]."' ORDER BY hop limit 1";
    $result = pg_query($dbconn, $sql) or die('lowest hop query failed: ' . pg_last_error());
    $lowestHop = pg_fetch_all($result)[0];
    pg_free_result($result);

    $sql = "SELECT * FROM full_routes_gl_analysis WHERE ip_addr = '".$ix["ip_addr"]."' and min_latency < 10 and min_latency > -1 ORDER BY min_latency limit 1";
    // using min_latency for now, keep an eye on this
    $result = pg_query($dbconn, $sql) or die('lowest lat query failed: ' . pg_last_error());
    $lowestLatency = pg_fetch_all($result);
    pg_free_result($result);

    $sql = "SELECT ROUND((SELECT count(*) FROM annotated_traceroutes WHERE ip_addr = '".$ix["ip_addr"]."' and prev_hop_sol_violation is true)::numeric / (SELECT count(*) FROM annotated_traceroutes WHERE ip_addr = '".$ix["ip_addr"]."') * 100) percent";
    $result = pg_query($dbconn, $sql) or die('prev_hop_sol_violation percent query failed: ' . pg_last_error());
    $prevHopSolViolation = pg_fetch_all($result)[0];
    pg_free_result($result);

    $sql = "SELECT ROUND((SELECT count(*) FROM annotated_traceroutes WHERE ip_addr = '".$ix["ip_addr"]."' and origin_sol_violation is true)::numeric / (SELECT count(*) FROM annotated_traceroutes WHERE ip_addr = '".$ix["ip_addr"]."') * 100) percent";
    $result = pg_query($dbconn, $sql) or die('origin_sol_violation percent query failed: ' . pg_last_error());
    $originSolViolation = pg_fetch_all($result)[0];
    pg_free_result($result);

    $sql = "SELECT ROUND((SELECT count(*) FROM annotated_traceroutes WHERE ip_addr = '".$ix["ip_addr"]."' and jittery is true)::numeric / (SELECT count(*) FROM annotated_traceroutes WHERE ip_addr = '".$ix["ip_addr"]."') * 100) percent";
    $result = pg_query($dbconn, $sql) or die('jittery percent query failed: ' . pg_last_error());
    $jittery = pg_fetch_all($result)[0];
    pg_free_result($result);


    $outputs = array(
      $ix["ip_addr"],
      $ix["count"],
      $geoloc->getIXHostname(),
      $geoloc->getMMHostname(),
      $geoloc->getIIHostname(),
      $geoloc->getIXHostname() == $geoloc->getMMHostname() && $geoloc->getIXHostname() == $geoloc->getIIHostname() ? 0 : 1,
      $geoloc->getMMHostname() == $geoloc->getIIHostname() ? 0 : 1,
      $geoloc->getIXASnum(),
      determineDeviance($DATA_SOURCES, "IX", $geoloc, "ASNum"),
      $geoloc->getMMASNum(),
      determineDeviance($DATA_SOURCES, "MM", $geoloc, "ASNum"),
      $geoloc->getI2ASNum(),
      determineDeviance($DATA_SOURCES, "I2", $geoloc, "ASNum"),
      $geoloc->getIXCountryCode(),
      determineDeviance($DATA_SOURCES, "IX", $geoloc, "CountryCode"),
      $geoloc->getMMCountryCode(),
      determineDeviance($DATA_SOURCES, "MM", $geoloc, "CountryCode"),
      $geoloc->getIICountryCode(),
      determineDeviance($DATA_SOURCES, "II", $geoloc, "CountryCode"),
      $geoloc->getI2CountryCode(),
      determineDeviance($DATA_SOURCES, "I2", $geoloc, "CountryCode"),
      determineIXUpdatedCountry($geoloc),
      determineInconsistentCountries($geoloc),
      $geoloc->getIXCity(),
      $geoloc->getMMCity(),
      $geoloc->getIICity(),
      $geoloc->getI2City(),
      $geoloc->getIXLat(),
      $geoloc->getMMLat(),
      $geoloc->getIILat(),
      $geoloc->getI2Lat(),
      $geoloc->getIXLong(),
      $geoloc->getMMLong(),
      $geoloc->getIILong(),
      $geoloc->getI2Long(),
      $IXMMDistance,
      $IXIIDistance,
      $IXI2Distance,
      $MMIIDistance,
      $MMI2Distance,
      $III2Distance,
      $AllDistanceFlag,
      $AllCountryFlag,
      $ix["created_at"] == "2016-10-16 08:26:52.356954-04" ? "<2016-10" : $ix["created_at"],
      $ix["updated_at"] == "2016-10-16 08:26:52.356954-04" ? "<2016-10" : $ix["updated_at"],
      $ix["gl_override"],
      $earliestOccurrence["sub_time"],
      $earliestOccurrence["traceroute_id"],
      $latestOccurrence["sub_time"],
      $latestOccurrence["traceroute_id"],
      $lowestHop["hop"],
      $lowestHop["traceroute_id"],
      $lowestLatency[0]["min_latency"],
      $lowestLatency[0]["traceroute_id"],
      $prevHopSolViolation["percent"],
      $originSolViolation["percent"],
      $jittery["percent"]
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

  echo "\nAll equal country count: $AllEqCountryCount";
  fwrite($outfile, "\nAll equal country count: ".$AllEqCountryCount);
  echo "\nIX within distance MM count: $IxEqMmDistanceCount";
  fwrite($outfile, "\nIX within distance MM count: ".$IxEqMmDistanceCount);
  echo "\nIX within distance IPInfo count: $IxEqIpInfoDistanceCount";
  fwrite($outfile, "\nIX within distance IPInfo count: ".$IxEqIpInfoDistanceCount);
  echo "\nIX within distance IP2Loc count: $IxEqIp2LocDistanceCount";
  fwrite($outfile, "\nIX within distance IP2Loc count: ".$IxEqIp2LocDistanceCount);
  echo "\nMM within distance IPInfo count: $MmEqIpInfoDistanceCount";
  fwrite($outfile, "\nMM within distance IPInfo count: ".$MmEqIpInfoDistanceCount);
  echo "\nMM within distance IP2Loc count: $MmEqIp2LocDistanceCount";
  fwrite($outfile, "\nMM within distance IP2Loc count: ".$MmEqIp2LocDistanceCount);
  echo "\nIPInfo within distance IP2Loc count: $IpInfoEqIp2LocDistanceCount";
  fwrite($outfile, "\nIPInfo within distance IP2Loc count: ".$IpInfoEqIp2LocDistanceCount);
  echo "\nAll within distance count: $AllEqDistanceCount";
  fwrite($outfile, "\nAll within distance count: ".$AllEqDistanceCount);

  fclose($outfile);
  pg_close($dbconn);
}


// If IX value exists, use it
// Elseif all other equal, use that
// Else null
function determineIXUpdatedCountry($geoloc) {
  if (!empty($geoloc->getIXCountryCode())) {
    return $geoloc->getIXCountryCode();
  }
  if ($geoloc->getMMCountryCode() == $geoloc->getIICountryCode() && $geoloc->getMMCountryCode() == $geoloc->getI2CountryCode()) {
    return $geoloc->getMMCountryCode();
  }
  return NULL;
}

function determineInconsistentCountries($geoloc) {
  $updatedIXCountry = determineIXUpdatedCountry($geoloc);
  if ($updatedIXCountry == $geoloc->getMMCountryCode() &&
      $updatedIXCountry == $geoloc->getIICountryCode() &&
      $updatedIXCountry == $geoloc->getI2CountryCode()
    )
  {
    return NULL;
  }

  return 1;
}

// determines how this ASN/Country compares to the ASNs/Countries from other data sources
// Possible outputs:
// - return 0 if all are equal
// - return positive int, where +1 for each differing non-null ASN
// - return null if the ASN passed in is null
function determineDeviance($DATA_SOURCES, $dataSource, $geoloc, $devianceKind) {
  if (empty($geoloc->{"get".$dataSource.$devianceKind}())) {
    return NULL;
  }

  // we don't want to compare to itself
  $asnSources = array_diff($DATA_SOURCES, array($dataSource));
  // there is no II ASN
  if ($devianceKind == "ASNum") {
    $asnSources = array_diff($asnSources, array('II'));
  }

  $deviance = 0;
  foreach ($asnSources as $source) {
    if ($geoloc->{"get".$source.$devianceKind}() && $geoloc->{"get".$dataSource.$devianceKind}() != $geoloc->{"get".$source.$devianceKind}()) {
      $deviance++;
    }
  }

  return $deviance;
}

function dbFetchAll($dbconn) {
  $sql = "SELECT c.count,i.* FROM ca_focal_sample c JOIN ip_addr_info i ON c.ip_addr = i.ip_addr ORDER BY c.count DESC";
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
  // a.jittery,
  // into full_routes_gl_analysis
  // from traceroute_traits t
  // join annotated_traceroutes a on t.traceroute_id = a.traceroute_id
  // join ip_addr_info i on a.ip_addr = i.ip_addr
  // where a.ip_addr in (select ip_addr from ca_focal_sample)
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

function distanceBetweenPoints($lat1, $long1, $lat2, $long2) {
  if (!empty($lat1) && !empty($long1) && !empty($lat2) && !empty($long2)) {
    $distanceMeters = IXmapsGeoCorrection::distanceBetweenCoords($lat1, $long1, $lat2, $long2);
    return round($distanceMeters/1000);
  }
  return 9999;
}

main();