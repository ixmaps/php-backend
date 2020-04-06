<?php
/**
 *
 * Update the derived tables that are used by the query engine:
 * -- traceroute_traits -- LIST
 * -- annotated_traceroutes --
 * 
 *
 * @param TBD
 *
 * @return None (updated tables traceroute_traits and annotated_traceroutes)
 *
 * @since Updated Apr 2020
 * @author IXmaps.ca (Colin)
 *
 */
chdir(dirname(__FILE__));
require_once('../config.php');
require_once('../model/IXmapsGeoCorrection.php');

// $lastTrId = getLastTrIdGen();
// echo 'Last TRid generated: '.$lastTrId;

$limit = 1;
getTracerouteIdsToUpdate(274743);
// getTracerouteIdsForModifiedIpAddr();

/**
  Selects all traceroute ids that need to be updated (date_modified has changed in the last day) and passes the trIds to updateTracerouteTraitsForTrId for an update. Intended for incomplete rows.
*/
function getTracerouteIdsForModifiedIpAddr() {
  global $dbconn;

  $sql = "SELECT DISTINCT traceroute_id as id FROM full_routes_last_hop WHERE ip_addr IN (SELECT ip_addr FROM ip_addr_info WHERE modified_at > date(current_date - 1))";
  $result = pg_query($dbconn, $sql) or die('Update modified query failed: ' . pg_last_error());
  $trArr = pg_fetch_all($result);
  pg_free_result($result);

  echo "\n".date("Y/m/d")."\n";

  loopOverTrIdsForTracerouteTraits($trArr);

  pg_close($dbconn);
}


/**
  Selects all traceroutes from trIdLast and passes the trIds to updateTracerouteTraitsForTrId for an update. Intended for incomplete rows.
*/
function getTracerouteIdsToUpdate($trIdLast) {
  global $dbconn, $limit;

  $sql = "SELECT traceroute.id FROM traceroute WHERE traceroute.id > ".$trIdLast." order by traceroute.id LIMIT ".$limit;
  $result = pg_query($dbconn, $sql) or die('TR ID query failed: ' . pg_last_error());
  $trArr = pg_fetch_all($result);
  pg_free_result($result);

  loopOverTrIdsForTracerouteTraits($trArr);

  pg_close($dbconn);
}


/**
  Loops over all of the trs that are passed in, sending them on to updateTracerouteTraitsForTrId
*/
function loopOverTrIdsForTracerouteTraits($trArr) {
  $startTime = microtime(true);
  $connGen = 0;
  
  if ($trArr) {
    foreach ($trArr as $key => $trId) {
      updateTracerouteTraitsForTrId($trId["id"]);
      $connGen++;
    }  
  }

  $timeElapsedSecs = microtime(true) - $startTime;
  echo "\nDuration: ".$timeElapsedSecs;

  if ($connGen == 0) {
    echo "\nNothing to do for now";
  } else {
    echo "\n".$connGen. " TRs for traceroute traits generated\n";
  }
}

function updateTracerouteTraitsForTrId($trId) {
  global $dbconn, $genericMMLatLongs;

  echo "\n*** Traceroute id: ".$trId." ***\n";

  // HANDLE
  // traceroute_id, last_hop_num, last_hop_ip_addr, terminated
  // INSERT INTO traceroute_traits(traceroute_id, last_hop_num, last_hop_ip_addr, terminated) (select traceroute_id_lh as traceroute_id, hop_lh as last_hop_num, ip_addr_lh as last_hop_ip_addr, cast(reached as boolean) as terminated FROM tr_last_hops);

  // HANDLE
  // nsa
  // ca_us_ca_boomerang
  // Handled by separate update queries
  // UPDATE traceroute_traits SET ca_us_ca_boomerang = true where traceroute_traits.traceroute_id in (select distinct(traceroute_id) from boomerang_routes order by traceroute_id);
  // UPDATE traceroute_traits SET nsa = true where traceroute_traits.traceroute_id in (select distinct(traceroute_id) from nsa_routes order by traceroute_id);


  // TODO: we will need ip blocks for origin
  // HANDLE
  // origin_ip_addr
  // origin_asnum
  // origin_city
  // origin_country
  // THESE GENERALLY WONT WORK - we will need netmask for this...
  // $sqlOrigin = "SELECT tr_contributions.submitter_ip, ip_addr_info.asnum, ip_addr_info.mm_city, ip_addr_info.mm_country, ip_addr_info.lat, ip_addr_info.long FROM tr_contributions, ip_addr_info WHERE tr_contributions.submitter_ip=ip_addr_info.ip_addr AND tr_contributions.traceroute_id=".$trId;
  // $result = pg_query($dbconn, $sqlOrigin) or die('Query failed: ' . pg_last_error());
  // $originArr = pg_fetch_all($result);
  // pg_free_result($result);

  // $origin_ip_addr = $originArr[0]["submitter_ip"];
  // echo "Origin ip: {$origin_ip_addr}\n";
  // $origin_asnum = null;
  // $origin_city = null;
  // $origin_country = null;
  // $originLat = null;
  // $originLong = null;
  // if ($origin_ip_addr != null) {
  //   $origin_asnum = $originArr[0]["asnum"];
  //   $origin_city = pg_escape_string($originArr[0]["mm_city"]);
  //   $origin_country = pg_escape_string($originArr[0]["mm_country"]);
  //   $originLat = $originArr[0]["lat"];
  //   $originLong = $originArr[0]["long"];
  //   echo "Origin asnum: {$origin_asnum}\n";
  //   echo "Origin city: {$origin_city}\n";
  //   echo "Origin country: {$origin_country}\n";
  // }
  // TODO: origin_asname


  /****
  HANDLE
  submitter
  sub_time
  submitter_zip_code
  dest
  dest_ip_addr
  dest_asnum
  dest_asname
  dest_city
  dest_country
  ****/
  $sqlDest = "SELECT traceroute.submitter, traceroute.sub_time, traceroute.zip_code, traceroute.dest, traceroute.dest_ip, ip_addr_info.asnum, ip_addr_info.mm_city, ip_addr_info.mm_country FROM ip_addr_info, traceroute WHERE ip_addr_info.ip_addr=traceroute.dest_ip AND traceroute.id=".$trId;
  $result = pg_query($dbconn, $sqlDest) or die('Query failed: ' . pg_last_error());
  $destArr = pg_fetch_all($result);
  pg_free_result($result);
  $submitter = pg_escape_string($destArr[0]["submitter"]);
  $sub_time = $destArr[0]["sub_time"];
  $submitter_zip_code = $destArr[0]["zip_code"];
  $dest = pg_escape_string($destArr[0]["dest"]);
  $dest_ip_addr = $destArr[0]["dest_ip"];
  $dest_asnum = $destArr[0]["asnum"];
  $dest_city = pg_escape_string($destArr[0]["mm_city"]);
  $dest_country = pg_escape_string($destArr[0]["mm_country"]);
  $dest_asname = getAsname($dest_asnum);
  echo "Submitter: {$submitter}\n";
  echo "Submission time: {$sub_time}\n";
  echo "Submitter zip code: {$submitter_zip_code}\n";
  echo "Dest: {$dest}\n";
  echo "Dest ip: {$dest_ip_addr}\n";
  echo "Dest asnum: {$dest_asnum}\n";
  echo "Dest asname: {$dest_asname}\n";
  echo "Dest city: {$dest_city}\n";
  echo "Dest country: {$dest_country}\n";


  /****
  HANDLE TRACEROUTE_TRAITS
  first_hop_ip_addr
  first_hop_asnum
  first_hop_city
  first_hop_country
  last_hop_num
  last_hop_ip
  last_hop_asnum
  last_hop_city
  last_hop_country
  num_hops
  terminated
  boomerang
  transits_us
  num_transited_countries
  num_transited_asnums
  list_transited_countries
  list_transited_asnums
  num_skipped_hops
  num_default_mm_location_hops
  num_gl_override_hops
  num_aba_hops
  num_prev_hop_sol_violation_hops
  num_origin_sol_violation_hops

  HANDLE ANNOTATED_TRACEROUTES
  min_latency
  transited_country
  transited_asnum
  prev_hop_sol_violation
  origin_sol_violation
  jittery
  this will need to be rewritten for GatherTr?
  ****/

  $sqlTraversal = "SELECT * FROM full_routes_last_hop WHERE traceroute_id=".$trId." ORDER BY hop;";
  $result = pg_query($dbconn, $sqlTraversal) or die('Query failed: ' . pg_last_error());
  $tracerouteArr = pg_fetch_all($result);
  pg_free_result($result);

  if ($tracerouteArr && count($tracerouteArr) > 0) {

    $first_hop_ip_addr = "0.0.0.0";
    if (strLen($tracerouteArr[0]["ip_addr"]) > 0) {
      $first_hop_ip_addr = $tracerouteArr[0]["ip_addr"];
    }
    $first_hop_city = pg_escape_string($tracerouteArr[0]["mm_city"]);
    $first_hop_country = pg_escape_string($tracerouteArr[0]["mm_country"]);
    $first_hop_asnum = -1;
    if (strLen($tracerouteArr[0]["asnum"]) > 0) {
      $first_hop_asnum = $tracerouteArr[0]["asnum"];  
    }
    $last_hop_num = end($tracerouteArr)["hop"];
    $last_hop_ip = end($tracerouteArr)["ip_addr"];
    $last_hop_ip_addr = "0.0.0.0";
    if (strLen(end($tracerouteArr)["ip_addr"]) > 0) {
      $last_hop_ip_addr = end($tracerouteArr)["ip_addr"];
    }
    $last_hop_city = pg_escape_string(end($tracerouteArr)["mm_city"]);
    $last_hop_country = pg_escape_string(end($tracerouteArr)["mm_country"]);
    $last_hop_asnum = -1;
    if (strLen(end($tracerouteArr)["asnum"]) > 0) {
      $last_hop_asnum = end($tracerouteArr)["asnum"];
    }
    $num_hops = 0;
    $num_gl_override_hops = 0;
    $num_default_mm_location_hops = 0;
    $abaTracker = array();
    $num_aba_hops = 0;
    $solViolationsTracker = array();
    $num_prev_hop_sol_violation_hops = 0;
    $num_origin_sol_violation_hops = 0;
    $num_jittery_hops = 0;
    $boomerang = false;
    $transits_us = false;
    $num_transited_countries = 0;
    $num_transited_asnums = 0;
    $list_transited_countries = array();
    $list_transited_asnums = array();

    $annotated_traceroute_min_latency = 0;
    
    foreach ($tracerouteArr as $tr => $hop) {
      // echo "\nHop: ".$hop["hop"]."\n";

      $annotated_traceroute_transited_country = false;
      $annotated_traceroute_transited_asnum = false;
      $prev_hop_annotated_traceroute_sol_violation = false;
      $origin_annotated_traceroute_sol_violation = false;
      $annotated_traceroute_jittery = false;

      $num_hops++;

      if ($hop["gl_override"] != NULL) {
        $num_gl_override_hops++;
      }

      $latLongStr = ($hop["lat"].", ".$hop["long"]);
      if (in_array($latLongStr, $genericMMLatLongs)) {
        $num_default_mm_location_hops++;
      }

      if ($hop["mm_city"] != NULL) {
        if (count($abaTracker) == 2) {
          if ($hop["mm_city"] == $abaTracker[0] && $hop["mm_city"] != $abaTracker[1]) {
            $num_aba_hops++;
          }
          array_shift($abaTracker);
        }
        array_push($abaTracker, $hop["mm_city"]);
      }

      // TODO: we don't have originLat / originLong (see above), so doing this for now
      $minRtt = min([$hop["rtt1"], $hop["rtt2"], $hop["rtt3"], $hop["rtt4"]]);
      if (IXmapsGeoCorrection::doesViolateSol($tracerouteArr[0]["lat"], $tracerouteArr[0]["long"], $hop["lat"], $hop["long"], $minRtt)) {

        $num_origin_sol_violation_hops++;
        $annotated_traceroute_origin_sol_violation = true;
      }

      if ($solViolationsTracker["lat"] && $solViolationsTracker["long"] && $solViolationsTracker["minRtt"]) {

        $deltaRtt = abs($minRtt - $solViolationsTracker["minRtt"]);
        if (IXmapsGeoCorrection::doesViolateSol($solViolationsTracker["lat"], $solViolationsTracker["long"], $hop["lat"], $hop["long"], $deltaRtt)) {

          $num_prev_hop_sol_violation_hops++;
          $annotated_traceroute_prev_hop_sol_violation = true;
        }
      }
      $solViolationsTracker["lat"] = $hop["lat"];
      $solViolationsTracker["long"] = $hop["long"];
      $solViolationsTracker["minRtt"] = $minRtt;

      if (IXmapsGeoCorrection::hopHasExcessiveJitter($hop)) {
        $num_jittery_hops++;
        $annotated_traceroute_jittery = true;
      }
      
      // if this it not the first or last hop
      if ($hop["hop"] != $tracerouteArr[0]["hop"] && $hop["hop"] != $hop["hop_lh"]) {
        $mm_country = $hop["mm_country"];
        $asnum = $hop["asnum"];

        // checking not first or last because transited means not first/last
        if ($mm_country != $first_hop_country && $mm_country != $last_hop_country && end($list_transited_countries) !== $mm_country) {
            
          $num_transited_countries++;
          array_push($list_transited_countries, $mm_country);
          $annotated_traceroute_transited_country = true;
        }

        // checking not first or last because transited means not first/last
        if ($asnum != $first_hop_asnum && $asnum != $last_hop_asnum && $asnum != -1 && end($list_transited_asnums) !== $asnum) {

          $num_transited_asnums++;
          array_push($list_transited_asnums, $asnum);
          $annotated_traceroute_transited_asnum = true;
        }

        if ($first_hop_country != "US" && $last_hop_country != "US" && $mm_country == "US") {
          $transits_us = true;  
        }
      }

      $sql = "UPDATE annotated_traceroutes SET (
          transited_country,
          transited_asnum,
          prev_hop_sol_violation,
          origin_sol_violation,
          jittery
        ) = ($1, $2, $3, $4, $5)
        WHERE traceroute_id=".$trId." and hop=".$hop["hop"];

      $trData = array(
        json_encode($annotated_traceroute_transited_country),
        json_encode($annotated_traceroute_transited_asnum),
        json_encode($annotated_traceroute_prev_hop_sol_violation),
        json_encode($annotated_traceroute_origin_sol_violation),
        json_encode($annotated_traceroute_jittery)
      );

      $result = pg_query_params($dbconn, $sql, $trData);

      if ($result === false) {
        echo "annotated_traceroutes update query failed for tr ".$trId." hop ".$hop["hop"].": " . pg_last_error();
      }
      pg_free_result($result);

    } // end foreach over hops

    $terminated = true;
    if ($last_hop_ip != $tracerouteArr[0]["dest_ip"]) {
      $terminated = false;
    }
    $num_skipped_hops = $last_hop_num - $num_hops;

    if ($first_hop_country == $last_hop_country && $num_transited_countries > 0) {
      $boomerang = true;
    }

    $list_transited_countries = implode(" > ", $list_transited_countries);
    $list_transited_asnums = implode(" > ", $list_transited_asnums);

    $first_hop_asname = getAsname($first_hop_asnum);
    $last_hop_asname = getAsname($last_hop_asnum);

    echo "First hop ip: {$first_hop_ip_addr}\n";
    echo "First hop asnum: {$first_hop_asnum}\n";
    echo "First hop asname: {$first_hop_asname}\n";
    echo "First hop city: {$first_hop_city}\n";
    echo "First hop country: {$first_hop_country}\n";
    echo "Last hop num: {$last_hop_num}\n";
    echo "Last hop ip: {$last_hop_ip}\n";
    echo "Last hop asnum: {$last_hop_asnum}\n";
    echo "Last hop asname: {$last_hop_asname}\n";
    echo "Last hop city: {$last_hop_city}\n";
    echo "Last hop country: {$last_hop_country}\n";
    echo "Number of hops: {$num_hops}\n";
    echo "Number of skipped hops: {$num_skipped_hops}\n";
    echo "Number of gl overrides: {$num_gl_override_hops}\n";
    echo "Number of default mm locations: {$num_default_mm_location_hops}\n";
    echo "Number of aba hops: {$num_aba_hops}\n";
    echo "Number of prev hop sol violations: {$num_prev_hop_sol_violation_hops}\n";
    echo "Number of origin sol violations: {$num_origin_sol_violation_hops}\n";
    echo "Number of jittery hops: {$num_jittery_hops}\n";
    echo "Boomerang: ".json_encode($boomerang)."\n";
    echo "Transits US: ".json_encode($transits_us)."\n";
    echo "Number of transited countries: {$num_transited_countries}\n";
    echo "Number of transited ASNs: {$num_transited_asnums}\n";
    echo "List of transited countries: {$list_transited_countries}\n";
    echo "List of transited ASNs: {$list_transited_asnums}\n";



    // NB: traceroute_id, nsa, boomerang_ca_us_ca not included here

    $sql = "UPDATE traceroute_traits SET (
      num_hops, 
      submitter, 
      sub_time, 
      submitter_zip_code, 
      dest, 
      dest_ip, 
      dest_asnum, 
      dest_asname, 
      dest_city, 
      dest_country, 
      first_hop_ip_addr, 
      first_hop_asnum, 
      first_hop_asname, 
      first_hop_city, 
      first_hop_country, 
      last_hop_num, 
      last_hop_ip_addr, 
      last_hop_asnum, 
      last_hop_asname, 
      last_hop_city, 
      last_hop_country, 
      terminated,
      boomerang, 
      transits_us, 
      num_transited_countries, 
      num_transited_asnums, 
      list_transited_countries, 
      list_transited_asnums, 
      num_skipped_hops, 
      num_default_mm_location_hops, 
      num_gl_override_hops, 
      num_aba_hops, 
      num_prev_hop_sol_violation_hops, 
      num_origin_sol_violation_hops, 
      num_jittery_hops) = ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17, $18, $19, $20, $21, $22, $23, $24, $25, $26, $27, $28, $29, $30, $31, $32, $33, $34, $35)
      WHERE traceroute_id=".$trId;

    $trData = array(
      $num_hops, 
      $submitter, 
      $sub_time, 
      $submitter_zip_code, 
      $dest, 
      $dest_ip, 
      $dest_asnum, 
      $dest_asname, 
      $dest_city, 
      $dest_country, 
      $first_hop_ip_addr, 
      $first_hop_asnum, 
      $first_hop_asname, 
      $first_hop_city, 
      $first_hop_country, 
      $last_hop_num, 
      $last_hop_ip_addr, 
      $last_hop_asnum, 
      $last_hop_asname, 
      $last_hop_city, 
      $last_hop_country,
      json_encode($terminated),
      json_encode($boomerang), 
      json_encode($transits_us), 
      $num_transited_countries, 
      $num_transited_asnums, 
      $list_transited_countries, 
      $list_transited_asnums, 
      $num_skipped_hops, 
      $num_default_mm_location_hops, 
      $num_gl_override_hops, 
      $num_aba_hops, 
      $num_prev_hop_sol_violation_hops, 
      $num_origin_sol_violation_hops, 
      $num_jittery_hops);

    $result = pg_query_params($dbconn, $sql, $trData);

    if ($result === false) {
      echo "traceroute_traits update query failed: " . pg_last_error();
    } else {
      // $retVal = pg_fetch_all($result);
      
    }
    pg_free_result($result);

  } else {
    echo "No valid result returned for ".$trId."\n";
  }
}


function getAsname($num) {
  global $dbconn;

  if (!$num) {
    return null;
  } else {
    $sqlAsnames = "SELECT name, short_name FROM as_users WHERE num = ".$num;
    $result = pg_query($dbconn, $sqlAsnames) or die('Query getAsname failed: ' . pg_last_error());
    $asnameArr = pg_fetch_all($result);
    pg_free_result($result);

    if ($asnameArr[0]["short_name"] != null) {
      return $asnameArr[0]["short_name"];
    } else {
      return $asnameArr[0]["name"];
    }
  }

}

// This clearly belongs in the Hop model
// this name is misleading - it's lowestRtt really
// function getMinLatencyForHop($hop) {
//   return min([$hop["rtt1"], $hop["rtt2"], $hop["rtt3"], $hop["rtt4"]]);
// }


// function getLastTrIdGen() {
//   global $dbconn;
//   $sql = "SELECT traceroute_id_lh FROM traceroute_traits ORDER BY traceroute_id_lh DESC LIMIT 1";
//   $result = pg_query($dbconn, $sql) or die('Query failed: ' . pg_last_error());
//   $lastTRId = pg_fetch_all($result);
//   pg_free_result($result);
//   $lastId = $lastTRId[0]['traceroute_id_lh'];
//   return $lastId;
// }
