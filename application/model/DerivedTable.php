<?php
/**
 *
 * Update the derived tables that are used by the query engine:
 * -- traceroute_traits --
 * traceroute_id
 * num_hops
 * submitter
 * sub_time
 * submitter_zip_code
 * origin_ip_addr
 * origin_asnum
 * origin_asname
 * origin_city
 * origin_country
 * dest
 * dest_ip_addr
 * dest_asnum
 * dest_asname
 * dest_city
 * dest_country
 * first_hop_ip_addr
 * first_hop_asnum
 * first_hop_asname
 * first_hop_city
 * first_hop_country
 * last_hop_num
 * last_hop_ip_addr
 * last_hop_asnum
 * last_hop_asname
 * last_hop_city
 * last_hop_country
 * terminated
 * nsa
 * boomerang
 * boomerang_ca_us_ca
 * transits_us
 * num_transited_countries
 * num_transited_asnums
 * list_transited_countries
 * list_transited_asnums
 * num_skipped_hops
 * num_default_mm_location_hops
 * num_gl_override_hops
 * num_aba_hops
 * num_prev_hop_sol_violation_hops
 * num_origin_sol_violation_hops
 * num_jittery_hops
 *
 * -- annotated_traceroutes --
 * traceroute_id
 * hop
 * ip_addr
 * hostname
 * asnum
 * asname
 * mm_lat
 * mm_long
 * lat
 * long
 * mm_city
 * mm_region
 * mm_country
 * mm_postal
 * gl_override
 * rtt1
 * rtt2
 * rtt3
 * rtt4
 * min_latency
 * transited_country
 * transited_asnum
 * prev_hop_sol_violation
 * origin_sol_violation
 * jittery
 *
 * @param Called from derived_tables.php controller and from gather-tr.php controller
 *
 * @return None (updated tables traceroute_traits and annotated_traceroutes)
 *
 * @since Updated Apr 2020
 * @author IXmaps.ca (Colin)
 *
 */

require_once('../config.php');
require_once('../model/IXmapsGeoCorrection.php');
require_once('../model/IXmapsMaxMind.php');

class DerivedTable
{
  public static function updateForTrId($trId) {
    global $dbconn, $genericMMLatLongs;

    echo "\n*** Traceroute id: ".$trId." ***\n";

    $sql = "SELECT traceroute_id FROM traceroute_traits WHERE traceroute_id=".$trId;
    $result = pg_query($dbconn, $sql) or die('Query insert of update failed: ' . pg_last_error());
    $traitsArr = pg_fetch_all($result);
    pg_free_result($result);
    $shouldUpdate = false;
    if ($traitsArr) {
      $shouldUpdate = true;
    }

    // HANDLE
    // origin_ip_addr
    // origin_asnum
    // origin_asname
    // origin_city
    // origin_country
    $sqlOrigin = "SELECT submitter_ip FROM tr_contributions WHERE traceroute_id=".$trId;
    $result = pg_query($dbconn, $sqlOrigin) or die('Query failed: ' . pg_last_error());
    $originArr = pg_fetch_all($result);
    pg_free_result($result);

    $origin_ip_addr = $originArr[0]["submitter_ip"];
    $origin_asnum = null;
    $origin_asname = null;
    $origin_city = null;
    $origin_country = null;
    $originLat = null;
    $originLong = null;

    if ($origin_ip_addr) {
      $mm = new IXmapsMaxMind($origin_ip_addr);
      $origin_asnum = $mm->getAsnum();
      $origin_asname = $mm->getAsname();
      $origin_city = $mm->getCity();
      $origin_country = $mm->getCountryCode();
      $originLat = $mm->getLat();
      $originLong = $mm->getLong();
      // echo "Origin ip: {$origin_ip_addr}\n";
      // echo "Origin asnum: {$origin_asnum}\n";
      // echo "Origin asname: {$origin_asname}\n";
      // echo "Origin city: {$origin_city}\n";
      // echo "Origin country: {$origin_country}\n";
    }

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

    $sqlDest = "SELECT traceroute.submitter, traceroute.sub_time, traceroute.zip_code, traceroute.dest, traceroute.dest_ip, ip_addr_info.asnum, ip_addr_info.mm_city, ip_addr_info.mm_country FROM traceroute LEFT JOIN ip_addr_info on traceroute.dest_ip=ip_addr_info.ip_addr WHERE traceroute.id=".$trId;
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
    $dest_asname = DerivedTable::getAsname($dest_asnum);
    // echo "Submitter: {$submitter}\n";
    // echo "Submission time: {$sub_time}\n";
    // echo "Submitter zip code: {$submitter_zip_code}\n";
    // echo "Dest: {$dest}\n";
    // echo "Dest ip: {$dest_ip_addr}\n";
    // echo "Dest asnum: {$dest_asnum}\n";
    // echo "Dest asname: {$dest_asname}\n";
    // echo "Dest city: {$dest_city}\n";
    // echo "Dest country: {$dest_country}\n";


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
    ****/

    // NB: this will skip all hops with null ip_addr, which I think is what we want...
    $sqlTraversal = "SELECT ti.traceroute_id, ti.hop, r[1] rtt1, r[2] rtt2, r[3] rtt3, r[4] rtt4, ip.ip_addr, ip.hostname, ip.asnum, ip.mm_city, ip.mm_region, ip.mm_country, ip.mm_postal, ip.mm_lat, ip.mm_long, ip.lat, ip.long, ip.gl_override FROM (select traceroute_id, hop, ip_addr, array_agg(rtt_ms) r from tr_item group by hop, 1, ip_addr order by 1) ti, traceroute tr, ip_addr_info ip WHERE ti.traceroute_id = tr.id AND ip.ip_addr = ti.ip_addr AND tr.id = ".$trId;
    $result = pg_query($dbconn, $sqlTraversal) or die('Query failed: ' . pg_last_error());
    $tracerouteArr = pg_fetch_all($result);
    pg_free_result($result);

    // is there a result to analyse?
    if ($tracerouteArr && count($tracerouteArr) > 0) {

      // do the min latency array first (for later insert)
      $latencies = array();
      $minLatencies = array();
      // arbitrary starting value that should be higher than any latency
      $lowestLat = 9999;
      // go backwards through the array, starting on last hop
      foreach (array_reverse($tracerouteArr) as $tr => $hop) {
        $rttArr = [$hop["rtt1"], $hop["rtt2"], $hop["rtt3"], $hop["rtt4"]];
        // removing all -1s
        $filteredHopLats = array_diff($rttArr, [-1]);
        $filteredHopLats = array_diff($filteredHopLats, [NULL]);
        // if there is at least one non -1 value in the hop
        if ($filteredHopLats) {
          $lat = min($filteredHopLats);

          // if this hop lat is lower than previous hop lats
          if ($lat < $lowestLat) {
            $lowestLat = $lat;
            array_push($minLatencies, $lat);
          // otherwise we use the previously lowest value
          } else {
            array_push($minLatencies, $lowestLat);
          }
        } else {
          array_push($minLatencies, -1);
        }
      }
      $minLatencies = array_reverse($minLatencies);

      // set all of the default values
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
      $citiesInRoute = array();
      $abaTracker = array();
      $num_aba_hops = 0;
      $solViolationsTracker = array("lat" => null, "long" => null, "minRtt" => null);
      $num_prev_hop_sol_violation_hops = 0;
      $num_origin_sol_violation_hops = 0;
      $num_jittery_hops = 0;
      $transits_us = false;
      $num_transited_countries = 0;
      $num_transited_asnums = 0;
      // for the first hop, prime the list_ values so they know what to compare to in the future
      // these values will be removed later
      $list_transited_countries = array($first_hop_country);
      $list_transited_asnums = array($first_hop_asnum);

      foreach ($tracerouteArr as $i => $hop) {
        // echo "\nHop: ".$hop["hop"]."\n";

        // set annotated_traceroutes default values
        $annotated_traceroute_transited_country = false;
        $annotated_traceroute_transited_asnum = false;
        $annotated_traceroute_prev_hop_sol_violation = false;
        $annotated_traceroute_origin_sol_violation = false;
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
          array_push($citiesInRoute, $hop["mm_city"]);

          if (count($abaTracker) == 2) {
            if ($hop["mm_city"] == $abaTracker[0] && $hop["mm_city"] != $abaTracker[1]) {
              $num_aba_hops++;
            }
            array_shift($abaTracker);
          }
          array_push($abaTracker, $hop["mm_city"]);
        }

        $rttArr = [$hop["rtt1"], $hop["rtt2"], $hop["rtt3"], $hop["rtt4"]];
        // removing all -1s
        $rttArr = array_diff($rttArr, [-1]);
        $minRtt = min($rttArr);
        if (IXmapsGeoCorrection::doesViolateSol($originLat, $originLong, $hop["lat"], $hop["long"], $minRtt)) {

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
        if ($hop["hop"] != $tracerouteArr[0]["hop"] && $hop["hop"] != end($tracerouteArr)["hop"]) {
          $mm_country = $hop["mm_country"];
          $asnum = $hop["asnum"];

          echo $mm_country;
          echo "\n";
          echo $asnum;
          echo "\n";

          if (end($list_transited_countries) !== $mm_country) {

            echo "transited country";
            echo "\n";

            $num_transited_countries++;
            array_push($list_transited_countries, $mm_country);
            $annotated_traceroute_transited_country = true;
          }

          if ($asnum != -1 && end($list_transited_asnums) !== $asnum) {

            echo "transited asnum";
            echo "\n";

            $num_transited_asnums++;
            array_push($list_transited_asnums, $asnum);
            $annotated_traceroute_transited_asnum = true;
          }

          if ($first_hop_country != "US" && $last_hop_country != "US" && $mm_country == "US") {
            $transits_us = true;
          }
        }

        // do the insert/update for this hop (annotated_traceroutes)
        // UPDATE
        if ($shouldUpdate) {
          $data = array(
            $hop["lat"],
            $hop["long"],
            $hop["mm_city"],
            $hop["mm_region"],
            $hop["mm_country"],
            $hop["mm_postal"],
            $hop["gl_override"],
            json_encode($annotated_traceroute_transited_country),
            json_encode($annotated_traceroute_prev_hop_sol_violation),
            json_encode($annotated_traceroute_origin_sol_violation)
          );
          DerivedTable::updateAnnotatedTraceroute($trId, $hop["hop"], $data);

        // INSERT
        } else {
          $data = array(
            $trId,
            $hop["hop"],
            $hop["ip_addr"],
            $hop["hostname"],
            $hop["asnum"],
            DerivedTable::getAsname($hop["asnum"]),
            $hop["mm_lat"],
            $hop["mm_long"],
            $hop["lat"],
            $hop["long"],
            $hop["mm_city"],
            $hop["mm_region"],
            $hop["mm_country"],
            $hop["mm_postal"],
            $hop["gl_override"],
            $hop["rtt1"],
            $hop["rtt2"],
            $hop["rtt3"],
            $hop["rtt4"],
            $minLatencies[$i],
            json_encode($annotated_traceroute_transited_country),
            json_encode($annotated_traceroute_transited_asnum),
            json_encode($annotated_traceroute_prev_hop_sol_violation),
            json_encode($annotated_traceroute_origin_sol_violation),
            json_encode($annotated_traceroute_jittery)
          );
          DerivedTable::insertAnnotatedTraceroute($data);

        }

      } // end foreach over hops


      $sqlNsa = "SELECT city FROM nsa_cities;";
      $result = pg_query($dbconn, $sqlNsa) or die('Query failed: ' . pg_last_error());
      $nsaArr = pg_fetch_all($result);
      pg_free_result($result);

      $intersection = array_intersect($citiesInRoute, array_column($nsaArr, "city"));

      $nsa = false;
      if (count($intersection) > 0) {
        $nsa = true;
      }

      $terminated = true;
      if ($last_hop_ip_addr != $dest_ip_addr) {
        $terminated = false;
      }

      $num_skipped_hops = $last_hop_num - $num_hops;

      $boomerang = false;
      if ($first_hop_country == $last_hop_country && $num_transited_countries > 0) {
        $boomerang = true;
      }
      $boomerang_ca_us_ca = false;
      if ($first_hop_country == "CA" && $last_hop_country == "CA" && $transits_us == true) {
        $boomerang_ca_us_ca = true;
      }

      // remove the priming values for both lists (since it's not a transiting value, but
      // only used to compare later values)
      array_shift($list_transited_countries);
      array_shift($list_transited_asnums);
      // yucky string lists for these
      $list_transited_countries = implode(" > ", $list_transited_countries);
      $list_transited_asnums = implode(" > ", $list_transited_asnums);

      // we can only get asname after the loop is done
      $first_hop_asname = DerivedTable::getAsname($first_hop_asnum);
      $last_hop_asname = DerivedTable::getAsname($last_hop_asnum);

      // echo "First hop ip: {$first_hop_ip_addr}\n";
      // echo "First hop asnum: {$first_hop_asnum}\n";
      // echo "First hop asname: {$first_hop_asname}\n";
      // echo "First hop city: {$first_hop_city}\n";
      // echo "First hop country: {$first_hop_country}\n";
      // echo "Last hop num: {$last_hop_num}\n";
      // echo "Last hop ip: {$last_hop_ip}\n";
      // echo "Last hop asnum: {$last_hop_asnum}\n";
      // echo "Last hop asname: {$last_hop_asname}\n";
      // echo "Last hop city: {$last_hop_city}\n";
      // echo "Last hop country: {$last_hop_country}\n";
      // echo "Number of hops: {$num_hops}\n";
      // echo "Number of skipped hops: {$num_skipped_hops}\n";
      // echo "Number of gl overrides: {$num_gl_override_hops}\n";
      // echo "Number of default mm locations: {$num_default_mm_location_hops}\n";
      // echo "Number of aba hops: {$num_aba_hops}\n";
      // echo "Number of prev hop sol violations: {$num_prev_hop_sol_violation_hops}\n";
      // echo "Number of origin sol violations: {$num_origin_sol_violation_hops}\n";
      // echo "Number of jittery hops: {$num_jittery_hops}\n";
      // echo "Boomerang: ".json_encode($boomerang)."\n";
      // echo "CA US CA boomerang: ".json_encode($boomerang_ca_us_ca)."\n";
      // echo "Transits US: ".json_encode($transits_us)."\n";
      // echo "Number of transited countries: {$num_transited_countries}\n";
      // echo "Number of transited ASNs: {$num_transited_asnums}\n";
      // echo "List of transited countries: {$list_transited_countries}\n";
      // echo "List of transited ASNs: {$list_transited_asnums}\n";

      // UPDATE
      if ($shouldUpdate) {
        echo "Updating...\n";
        $trData = array(
          $origin_city,
          $origin_country,
          $dest_city,
          $dest_country,
          $first_hop_city,
          $first_hop_country,
          $last_hop_city,
          $last_hop_country,
          json_encode($nsa),
          json_encode($boomerang),
          json_encode($boomerang_ca_us_ca),
          json_encode($transits_us),
          $num_transited_countries,
          $list_transited_countries,
          $num_default_mm_location_hops,
          $num_gl_override_hops,
          $num_aba_hops,
          $num_prev_hop_sol_violation_hops,
          $num_origin_sol_violation_hops,
        );
        DerivedTable::updateTracerouteTrait($trId, $trData);

      // INSERT
      } else {
        echo "Inserting...\n";
        $trData = array(
          $trId,
          $num_hops,
          $submitter,
          $sub_time,
          $submitter_zip_code,
          $origin_ip_addr,
          $origin_asnum,
          $origin_asname,
          $origin_city,
          $origin_country,
          $dest,
          $dest_ip_addr,
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
          json_encode($nsa),
          json_encode($boomerang),
          json_encode($boomerang_ca_us_ca),
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
          $num_jittery_hops
        );
        DerivedTable::insertTracerouteTrait($trData);
      }

    } else {
      echo "No valid result returned for ".$trId."\n";
    }
  }


  public static function insertAnnotatedTraceroute($data) {
    global $dbconn;

    $sql = "INSERT INTO annotated_traceroutes (
        traceroute_id,
        hop,
        ip_addr,
        hostname,
        asnum,
        asname,
        mm_lat,
        mm_long,
        lat,
        long,
        mm_city,
        mm_region,
        mm_country,
        mm_postal,
        gl_override,
        rtt1,
        rtt2,
        rtt3,
        rtt4,
        min_latency,
        transited_country,
        transited_asnum,
        prev_hop_sol_violation,
        origin_sol_violation,
        jittery
      ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17, $18, $19, $20, $21, $22, $23, $24, $25)";

    $result = pg_query_params($dbconn, $sql, $data);

    if ($result === false) {
      echo "annotated_traceroutes update query failed for tr ".$data[0]." hop ".$data[1].": " . pg_last_error();
    }
    pg_free_result($result);
  }

  public static function updateAnnotatedTraceroute($trId, $hop, $data) {
    global $dbconn;

    $sql = "UPDATE annotated_traceroutes SET (
        lat,
        long,
        mm_city,
        mm_region,
        mm_country,
        mm_postal,
        gl_override,
        transited_country,
        prev_hop_sol_violation,
        origin_sol_violation
      ) = ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)
      WHERE traceroute_id = ".$trId." and hop = ".$hop;

    $result = pg_query_params($dbconn, $sql, $data);

    if ($result === false) {
      echo "annotated_traceroutes update query failed for tr ".$trId." hop ".$hop.": " . pg_last_error();
    }
    pg_free_result($result);
  }

  public static function insertTracerouteTrait($data) {
    global $dbconn;

    $sql = "INSERT INTO traceroute_traits (
        traceroute_id,
        num_hops,
        submitter,
        sub_time,
        submitter_zip_code,
        origin_ip_addr,
        origin_asnum,
        origin_asname,
        origin_city,
        origin_country,
        dest,
        dest_ip_addr,
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
        nsa,
        boomerang,
        boomerang_ca_us_ca,
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
        num_jittery_hops
      ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17, $18, $19, $20, $21, $22, $23, $24, $25, $26, $27, $28, $29, $30, $31, $32, $33, $34, $35, $36, $37, $38, $39, $40, $41, $42, $43)";

    $result = pg_query_params($dbconn, $sql, $data);
    if ($result === false) {
      echo "traceroute_traits insert query failed: " . pg_last_error();
    }
    pg_free_result($result);
  }

  public static function updateTracerouteTrait($trId, $data) {
    global $dbconn;

    $sql = "UPDATE traceroute_traits SET (
        origin_city,
        origin_country,
        dest_city,
        dest_country,
        first_hop_city,
        first_hop_country,
        last_hop_city,
        last_hop_country,
        nsa,
        boomerang,
        boomerang_ca_us_ca,
        transits_us,
        num_transited_countries,
        list_transited_countries,
        num_default_mm_location_hops,
        num_gl_override_hops,
        num_aba_hops,
        num_prev_hop_sol_violation_hops,
        num_origin_sol_violation_hops
      ) = ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17, $18, $19)
      WHERE traceroute_id = ".$trId;

    $result = pg_query_params($dbconn, $sql, $data);
    if ($result === false) {
      echo "traceroute_traits insert query failed: " . pg_last_error();
    }
    pg_free_result($result);
  }


  public static function getAsname($num) {
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

}
?>