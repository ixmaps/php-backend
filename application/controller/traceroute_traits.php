<?php
/**
 *
 * Update the 'helper' tables that is used by the query engine:
 * -- traceroute_traits --
 * 
 *
 * @param TBD
 *
 * @return None (updated tables traceroute_traits)
 *
 * @since Updated Apr 2020
 * @author IXmaps.ca (Colin)
 *
 */
chdir(dirname(__FILE__));
require_once('../config.php');

// $lastTrId = getLastTrIdGen();
// echo 'Last TRid generated: '.$lastTrId;
getTracerouteIdsToUpdate(1948);
// getTracerouteIdsForModifiedIpAddr();

/**
  Selects all traceroute ids that need to be updated (date_modified has changed in the last day) and passes the trIds to updateTracerouteTraitsForTrId for an update. Intended for incomplete rows.
*/
function getTracerouteIdsForModifiedIpAddr() {
  global $dbconn, $savePath;

  $sql = "SELECT DISTINCT traceroute_id as id FROM full_routes_last_hop WHERE ip_addr IN (SELECT ip_addr FROM ip_addr_info WHERE modified_at > date(current_date - 1))";
  $result = pg_query($dbconn, $sql) or die('Update modified query failed: ' . pg_last_error());
  $trArr = pg_fetch_all($result);

  echo "\n".date("Y/m/d")."\n";

  loopOverTrIdsForTracerouteTraits($trArr);

  pg_free_result($result);
  pg_close($dbconn);
}


/**
  Selects all traceroutes from trIdLast and passes the trIds to updateTracerouteTraitsForTrId for an update. Intended for incomplete rows.
*/
function getTracerouteIdsToUpdate($trIdLast) {
  global $dbconn, $savePath;

  $sql = "SELECT traceroute.id FROM traceroute WHERE traceroute.id > ".$trIdLast." order by traceroute.id LIMIT 1";
  $result = pg_query($dbconn, $sql) or die('TR ID query failed: ' . pg_last_error());
  $trArr = pg_fetch_all($result);

  loopOverTrIdsForTracerouteTraits($trArr);

  pg_free_result($result);
  pg_close($dbconn);
}


/**
  Loops over all of the trs that are passed in, sending them on to updateTracerouteTraitsForTrId
*/
function loopOverTrIdsForTracerouteTraits($trArr) {
  $start_time = microtime(true);
  $connGen = 0;
  
  if ($trArr) {
    foreach ($trArr as $key => $trId) {
      updateTracerouteTraitsForTrId($trId["id"]);
      $connGen++;
    }  
  }

  $time_elapsed_secs = microtime(true) - $start_time;
  echo "\nDuration: ".$time_elapsed_secs;

  if ($connGen == 0) {
    echo "\nNothing to do for now";
  } else {
    echo "\nStarted at: ".$trIdLast."\n". $connGen. " TRs for traceroute traits generated";
  }

  // look at cron way of doing it?
  // $fp = fopen('traceroute_traits_log.txt', 'w');
  // fwrite($fp, 'Complete with '.$myc.' inserted');
  // fclose($fp);
  // pg_free_result($resp);
}

function updateTracerouteTraitsForTrId($trId) {
  global $dbconn, $savePath, $genericMMLatLongs;
  
  $sqlLastHop = "SELECT tr_item.hop, tr_item.traceroute_id, traceroute.id, traceroute.dest, traceroute.dest_ip, ip_addr_info.ip_addr, ip_addr_info.asnum, ip_addr_info.mm_city, ip_addr_info.mm_country FROM tr_item, traceroute, ip_addr_info, nsa_cities WHERE (tr_item.traceroute_id=traceroute.id) AND (ip_addr_info.ip_addr=tr_item.ip_addr) AND tr_item.attempt = 1 AND tr_item.hop > 1 and traceroute.id=".$trId." order by tr_item.hop DESC LIMIT 1";

  $result = pg_query($dbconn, $sqlLastHop) or die('Last hop query failed: ' . pg_last_error());
  $lastHopArr = pg_fetch_all($result);
  pg_free_result($result);

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


  // HANDLE
  // terminated (done above)
  // $terminated = true;
  // if ($lastHopArr[0]["ip_addr"] != $lastHopArr[0]["dest_ip"]) {
  //   $terminated = false;
  // }

  // HANDLE
  // last_hop_asnum
  // last_hop_city
  // last_hop_country
  // PULL THIS OUT OF FULL_ROUTES INTO A TABLE AND THEN INSERT AS PER LH
  $last_hop_asnum = $lastHopArr[0]["asnum"];
  $last_hop_city = pg_escape_string($lastHopArr[0]["mm_city"]);
  $last_hop_country = pg_escape_string($lastHopArr[0]["mm_country"]);
  echo "Last hop asnum: {$last_hop_asnum}\n";
  echo "Last hop city: {$last_hop_city}\n";
  echo "Last hop country: {$last_hop_country}\n";

  // HANDLE
  // first_hop_asnum
  // first_hop_city
  // first_hop_country
  $sqlFirstHop = "SELECT ip_addr, asnum, mm_city, mm_country FROM full_routes_last_hop WHERE traceroute_id=".$trId." AND hop=1";
  $result = pg_query($dbconn, $sqlFirstHop) or die('Query failed: ' . pg_last_error());
  $firstHopArr = pg_fetch_all($result);
  pg_free_result($result);
  $first_hop_ip = '0.0.0.0';
  if (strLen($firstHopArr[0]["ip_addr"]) > 0) {
    $first_hop_ip = $firstHopArr[0]["ip_addr"];
  }
  $first_hop_asnum = $firstHopArr[0]["asnum"];
  $first_hop_city = pg_escape_string($firstHopArr[0]["mm_city"]);
  $first_hop_country = pg_escape_string($firstHopArr[0]["mm_country"]);
  echo "First hop ip: {$first_hop_ip}\n";
  echo "First hop asnum: {$first_hop_asnum}\n";
  echo "First hop city: {$first_hop_city}\n";
  echo "First hop country: {$first_hop_country}\n";

  // HANDLE
  // dest_country
  $sqlDestCountry = "SELECT ip_addr_info.mm_country FROM ip_addr_info, traceroute WHERE ip_addr_info.ip_addr=traceroute.dest_ip AND traceroute.id=".$trId;
  $result = pg_query($dbconn, $sqlDestCountry) or die('Query failed: ' . pg_last_error());
  $destCountryArr = pg_fetch_all($result);
  pg_free_result($result);
  $destCountry = pg_escape_string($destCountryArr[0]["mm_country"]);
  echo "Dest country: {$destCountry}\n";

  // HANDLE
  // num_hops
  // boomerang
  // transits_us
  // num_transited_countries
  // num_transited_asnums
  // list_transited_countries
  // list_transited_asnums
  // think about adding the last_hop_xyz as well
  // this will need to be rewritten for GatherTr
  $sqlTraversal = "SELECT * from full_routes_last_hop where traceroute_id=".$trId." ORDER BY hop;";
  $result = pg_query($dbconn, $sqlTraversal) or die('Query failed: ' . pg_last_error());
  $tracerouteArr = pg_fetch_all($result);
  pg_free_result($result);

  $num_hops = 0;
  $num_gl_override_hops = 0;
  $num_default_mm_location_hops = 0;
  $aba_tracker = [];
  $num_aba_hops = 0;
  $boomerang = false;
  $transits_us = false;
  $num_transited_countries = 0;
  $num_transited_asnums = 0;
  $list_transited_countries = [];
  $list_transited_asnums = [];
  $first_hop_country = $tracerouteArr[0]["mm_country"];
  $last_hop_country = end($tracerouteArr)["mm_country"];
  $first_hop_asnum = $tracerouteArr[0]["asnum"];
  $last_hop_asnum = end($tracerouteArr)["asnum"];
  
  foreach ($tracerouteArr as $tr => $hop) {
    $num_hops++;

    if ($hop["gl_override"] != NULL) {
      $num_gl_override_hops++;
    }

    $latLngStr = ($hop["lat"].", ".$hop["long"]);
    if (in_array($latLngStr, $genericMMLatLongs)) {
      $num_default_mm_location_hops++;
    }

    if ($hop["mm_city"] != NULL) {
      if (count($aba_tracker) == 2) {
        if ($hop["mm_city"] == $aba_tracker[0] && $hop["mm_city"] != $aba_tracker[1]) {
          $num_aba_hops++;
        }
        array_shift($aba_tracker);
      }
      array_push($aba_tracker, $hop["mm_city"]);
    }
    
    if ($hop["hop"] != 1 && $hop["hop"] != $hop["hop_lh"]) {
      $mm_country = $hop["mm_country"];
      $asnum = $hop["asnum"];

      if ($mm_country != $first_hop_country && $mm_country != $last_hop_country && end($list_transited_countries) !== $mm_country) {
          
        $num_transited_countries++;
        array_push($list_transited_countries, $mm_country);
      }

      if ($asnum != $first_hop_asnum && $asnum != $last_hop_asnum && end($list_transited_asnums) !== $asnum) {

        $num_transited_asnums++;
        array_push($list_transited_asnums, $asnum);
      }

      if ($first_hop_country != "US" && $last_hop_country != "US" && $mm_country == "US") {
        $transits_us = true;  
      }
      
    }
  }

  $num_skipped_hops = $lastHopArr[0]["hop"] - $num_hops;
  if ($first_hop_country == $last_hop_country && $num_transited_countries > 0) {
    $boomerang = true;
  }
  $list_transited_countries = implode(" > ", $list_transited_countries);
  $list_transited_asnums = implode(" > ", $list_transited_asnums);

  echo "Number of hops: ".$num_hops."\n";
  echo "Number of skipped hops: ".$num_skipped_hops."\n";
  echo "Number of gl overrides: ".$num_gl_override_hops."\n";
  echo "Number of default mm locations: ".$num_default_mm_location_hops."\n";
  echo "Number of aba hops: ".$num_aba_hops."\n";
  echo "Boomerang: ".json_encode($boomerang)."\n";
  echo "Transits US: ".json_encode($transits_us)."\n";
  echo "Number of transited countries: ".$num_transited_countries."\n";
  echo "Number of transited ASNs: ".$num_transited_asnums."\n";
  echo "List of transited countries: ".$list_transited_countries."\n";
  echo "List of transited ASNs: ".$list_transited_asnums."\n";

  $sqlInsert = "UPDATE traceroute_traits SET dest_country='{$dest_country}', first_hop_ip_addr='{$first_hop_ip}', first_hop_asnum={$first_hop_asnum}, first_hop_city='{$first_hop_city}', first_hop_country='{$first_hop_country}', last_hop_asnum={$last_hop_asnum},  last_hop_city='{$last_hop_city}', last_hop_country='{$last_hop_country}', num_hops={$num_hops}, num_skipped_hops={$num_skipped_hops}, num_gl_override_hops={$num_gl_override_hops}, num_default_mm_location_hops={$num_default_mm_location_hops}, num_aba_hops={$num_aba_hops}, boomerang=".json_encode($boomerang).", transits_us=".json_encode($transits_us).", num_transited_countries=".$num_transited_countries.", num_transited_asnums=".$num_transited_asnums.", list_transited_countries='".$list_transited_countries."', list_transited_asnums='".$list_transited_asnums."' WHERE traceroute_id=".$trId.";";
  
  // $sqlInsert = "UPDATE traceroute_traits SET dest_country='".$destCountryArr[0]["mm_country"]."', first_hop_ip_addr='".$firstHopArr[0]["ip_addr"]."', first_hop_asnum=".$firstHopArr[0]["asnum"].", first_hop_city='".$firstHopArr[0]["mm_city"]."', first_hop_country='".$firstHopArr[0]["mm_country"]."', last_hop_asnum=".$lastHopArr[0]["asnum"].",  last_hop_city='".$lastHopArr[0]["mm_city"]."', last_hop_country='".$lastHopArr[0]["mm_country"]."' WHERE traceroute_id=".$trId.";";

  try {
    if ($sqlInsert != "INSERT INTO traceroute_traits VALUES (, , '', 1);") {
      pg_query($dbconn, $sqlInsert) or die('Query failed: ' . pg_last_error());
    } else {
      echo "<br/>This TR has no hops. Empty record...";
    }

  } catch(Exception $e) {
    echo "db error.";
  }
}



// function getLastTrIdGen() {
//   global $dbconn;
//   $sql = "SELECT traceroute_id_lh FROM traceroute_traits ORDER BY traceroute_id_lh DESC LIMIT 1";
//   $result = pg_query($dbconn, $sql) or die('Query failed: ' . pg_last_error());
//   $lastTRId = pg_fetch_all($result);
//   pg_free_result($result);
//   $lastId = $lastTRId[0]['traceroute_id_lh'];
//   return $lastId;
// }
