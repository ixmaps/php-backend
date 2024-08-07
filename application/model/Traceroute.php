<?php
/**
 *
 * Traceroute class deals with a variety of things, we're in the process of tightening this up.
 * Key functions relate to map.php, receiving a set of constraints from the frontend, parsing
 * those for relevant tr ids, and generating traceroute objects for those ids to be returned to
 * the frontend
 *
 * @author IXmaps.ca (Colin, Antonio)
 * @since 2020 June
 *
 */
require_once('../model/Logging.php');

class Traceroute
{
  /**
   *
   * Get set of traceroute ids for constraints
   *
   * @param constraints passed in from the frontend (via map.php), eg:
   * [
   *   {
   *     "constraint1": "does",
   *     "constraint2": "contain",
   *     "constraint3": "country",
   *     "constraint4": "CA",
   *     "constraint5": "and"
   *   }
   * ]
   *
   * @return array of TR ids
   *
   */
  public static function getTracerouteIdsForConstraints($data, $log)
  {
    global $dbconn;
    global $sampleCap;

    // we need to bump the memory for the 'contains' and 'goes via' multi-constraint searches
    ini_set('memory_limit', '256M');

    $trIdsForConstraint = array();
    $constraintNum = 0;

    // loop over the constraints
    foreach ($data as $constraint) {

      // this query is sufficiently common and slow that we make an exception
      if ($constraint['constraint1'] == "does" &&
          $constraint['constraint2'] == "goVia" &&
          $constraint['constraint3'] == "country" &&
          $constraint['constraint4'] == "US") {

        $sql = "SELECT traceroute_id FROM traceroute_traits WHERE transits_us is true";
        $trIdsForConstraint[$constraintNum] = Traceroute::getTrIds($sql, "");
        $log->search("Loop ".strval($constraintNum + 1).": goViaUS");

      } else {
        $sql = "SELECT annotated_traceroutes.traceroute_id FROM annotated_traceroutes, traceroute_traits WHERE annotated_traceroutes.traceroute_id = traceroute_traits.traceroute_id";
        // add the constraint to the sql
        $wParams = Traceroute::buildWhere($constraint);
        $sql .= $wParams[0];
        $log->search("Loop ".strval($constraintNum + 1).": buildWhere ".$sql);

        // execute the sql (getting the TR data to return)
        $trIdsForConstraint[$constraintNum] = Traceroute::getTrIds($sql, $wParams[1]);
        $log->search("Loop ".strval($constraintNum + 1).": getTrIds");
      }

      $constraintNum++;

    } // end foreach


    $log->search("Constraints loop");

    // merge sets of ids based on AND/OR conditions
    $trIds = array();
    for ($i = 0; $i < $constraintNum; $i++) {

      // only one constraint
      if ($i == 0) {
        $trIds = $trIdsForConstraint[0];

      // more than one constraint
      } else {
        // OR cases
        if ($data[$i-1]['constraint5'] == 'or') {
          $trIds = array_merge($trIds, $trIdsForConstraint[$i]);
        // AND cases
        } else {
          $trIds = array_intersect($trIds, $trIdsForConstraint[$i]);
        }
      }
    } // end for

    $trIds = array_unique($trIds);

    unset($trIdsForConstraint);

    // reset to the default
    ini_set('memory_limit', '128M');

    return $trIds;
  }

  /**
    * Process the quicklinks with canned SQL
    *
    * @param constraint object that defines type of quicklink
    *
    * @return array of tr ids
    *
    */
  public static function processQuickLink($qlArray)
  {
    if ($qlArray[0]['constraint2'] == "viaNSACity") {
      $sql = "select traceroute_id from traceroute_traits where nsa = true";
      return Traceroute::getTrIds($sql, "");

    } else if ($qlArray[0]['constraint2'] == "boomerang") {
      $sql = "select traceroute_id from traceroute_traits where boomerang = true and first_hop_country = '".$qlArray[0]['constraint4']."'";
      return Traceroute::getTrIds($sql, "");

    } else if ($qlArray[0]['constraint2'] == "lastSubmission") {
      $sql = "select traceroute_id from traceroute_traits order by sub_time desc limit 20";
      return Traceroute::getTrIds($sql, "");

    } else if ($qlArray[0]['constraint2'] == "singleRoute") {
      $sql = "select traceroute_id from traceroute_traits where traceroute_id = ".$qlArray[0]['constraint3'];
      return Traceroute::getTrIds($sql, "");

    } else {
      return array();
    }
  }

  /**
    * Get tr metadata, hop geodata, hop metadata
    *
    * @param set of trIds
    *
    * @return array of traceroutes
    *
    */
  public static function getTracerouteDataForIds($trIds, $maxTrCount)
  {
    global $dbconn;

    $trsFound = count($trIds);

    // if there is more than 1 tr, take a sampling
    if ($trsFound > $maxTrCount) {
      $numTrsSampled = $maxTrCount;
      $trIds = array_rand(array_flip($trIds), $numTrsSampled);
    } else {
      $numTrsSampled = $trsFound;
    }
    $idsStr = implode(", ", $trIds);

    // free some memory
    unset($trIds);

    // two queries actually turns out to be slightly faster than one with an extra join
    $hopSql = "
      SELECT at.traceroute_id, at.hop, at.rtt1, at.rtt2, at.rtt3, at.rtt4, at.min_latency, at.ip_addr, at.hostname, at.lat, at.long, at.mm_city, at.mm_country, at.gl_override, at.asnum, at.asname, ip_addr_info.flagged
      FROM annotated_traceroutes at, ip_addr_info
      WHERE at.ip_addr = ip_addr_info.ip_addr
      AND at.traceroute_id IN (".$idsStr.")
      order by at.traceroute_id desc, at.hop";

    $result = pg_query($dbconn, $hopSql) or die('getTracerouteDataForIds query failed: ' . pg_last_error());
    $hopArr = pg_fetch_all($result);
    pg_free_result($result);

    $metadataSql = "
      SELECT traceroute_id, submitter, sub_time, submitter_zip_code, origin_asnum, origin_asname, origin_city, origin_country, first_hop_num, first_hop_asnum, first_hop_asname, first_hop_city, first_hop_country, last_hop_asnum, last_hop_asname, last_hop_city, last_hop_country, dest_hostname, dest_ip_addr, dest_asnum, dest_asname, dest_city, dest_country, last_hop_ip_addr, terminated
      FROM traceroute_traits
      WHERE traceroute_id IN (".$idsStr.")
      order by traceroute_id desc";

    $result = pg_query($dbconn, $metadataSql) or die('getTracerouteDataForIds query failed: ' . pg_last_error());
    $metadataArr = pg_fetch_all($result);
    pg_free_result($result);

    $traceroute = array();
    $allTraceroutes = array();

    foreach ($metadataArr as $i => $tr) {
      $traceroute["traceroute_id"] = $tr['traceroute_id'];
      $traceroute["metadata"] = $tr;
      $traceroute["hops"] = array();

      $allTraceroutes[$tr["traceroute_id"]] = $traceroute;
    }
    foreach ($hopArr as $key => $hop) {
      array_push($allTraceroutes[$hop["traceroute_id"]]["hops"], $hop);
    }

    $results = array(
      'trsFound' => $trsFound,
      'trsReturned' => $numTrsSampled,
      'totHops' => count($hopArr),
      'result' => json_encode($allTraceroutes)
    );

    return $results;
  }


  /**
   *
   * Creates a where SQL string based on passed in constraint
   *
   * @param Constraint obj, eg
   *  {
   *    "constraint1": "does",
   *    "constraint2": "contain",
   *    "constraint3": "country",
   *    "constraint4": "CA",
   *    "constraint5": "and"
   *  }
   *
   * @return SQL string
   *
   */
  public static function buildWhere($c, $paramNum = 1)
  {
    global $dbconn;

    $whereConditions = '';

    // apply some default formating to constraint's value
    $constraintValue = trim($c['constraint4']);

    // DOES / DOESNOT
    if ($c['constraint1'] == 'does') {
      $comparatorExact = '=';
      $comparatorLike = 'ILIKE';
    } else if ($c['constraint1'] == 'doesNot') {
      $comparatorExact = '<>';
      $comparatorLike = 'NOT LIKE';
    }

    // ORIGINATE / CONTAIN / GOVIA / TERMINATE
    // if param3 is at the traceroute level (instead of hop level), we want to use contains (which means no where constraint)
    $tracerouteLevelParams = ['submitter', 'zipCodeSubmitter', 'destHostname', 'trId', 'subTimeGreaterThan', 'subTimeLessThan'];

    if ($c['constraint2'] == 'contains' || in_array($c['constraint3'], $tracerouteLevelParams)) {
      $whereConditions = "";

    } else if ($c['constraint2'] == 'originate') {
      $whereConditions .= " AND annotated_traceroutes.hop = 1";

    } else if ($c['constraint2'] == 'terminate') {
      $whereConditions .= " AND annotated_traceroutes.hop = traceroute_traits.last_hop_num";

    } else if ($c['constraint2'] == 'goVia') {
      $whereConditions.=" AND (annotated_traceroutes.hop != 1 AND annotated_traceroutes.hop != traceroute_traits.last_hop_num)";

    }

    // CONSTRAINT3
    /* setting constraints associated to table annotated_traceroutes */
    if ($c['constraint3'] == 'country') {
      $table = 'annotated_traceroutes';
      $field = 'mm_country';
    } else if ($c['constraint3'] == 'region') {
      $table = 'annotated_traceroutes';
      $field = 'mm_region';
    } else if ($c['constraint3'] == 'city') {
      $table = 'annotated_traceroutes';
      $field = 'mm_city';
    } else if ($c['constraint3'] == 'zipCode') {
      $table = 'annotated_traceroutes';
      $field = 'mm_postal';
    } else if ($c['constraint3'] == 'ipAddr') {
      $table = 'annotated_traceroutes';
      $field = 'ip_addr';
    } else if ($c['constraint3'] == 'hostname') {
      $table = 'annotated_traceroutes';
      $field = 'hostname';
    } else if ($c['constraint3'] == 'asnum') {
      $table = 'annotated_traceroutes';
      $field = 'asnum';
    } else if ($c['constraint3'] == 'ISP') {
      $table = 'annotated_traceroutes';
      $field = 'asname';
    /* setting constraints associated to table traceroute_traits */
    } else if ($c['constraint3'] == 'submitter') {
      $table = 'traceroute_traits';
      $field = 'submitter';
    } else if ($c['constraint3'] == 'zipCodeSubmitter') {
      $table = 'traceroute_traits';
      $field = 'submitter_zip_code';
    } else if ($c['constraint3'] == 'destHostname') {
      $table = 'traceroute_traits';
      $field = 'dest_hostname';
    } else if ($c['constraint3'] == 'trId') {
      $table = 'traceroute_traits';
      $field = 'traceroute_id';
    } else if ($c['constraint3'] == 'subTimeGreaterThan') {
      $table = 'traceroute_traits';
      $field = 'sub_time > ';
    } else if ($c['constraint3'] == 'subTimeLessThan') {
      $table = 'traceroute_traits';
      $field = 'sub_time < ';
    }


    // AND / OR
    if ($c['constraint5'] == '') {
      $operand = 'and';
    } else  {
      $operand = $c['constraint5'];
    }

    // datetimes
    if ($field == 'sub_time > ' || $field == 'sub_time < ') {
      $whereConditions .= " AND $table.$field $".$paramNum;

    // exact matches
    } else if ($field == 'asnum' || $field == 'ip_addr' || $field == 'traceroute_id') {
      $whereConditions .= " AND $table.$field $comparatorExact $".$paramNum;

    // similar matches
    } else {
      $whereConditions .= " AND $table.$field $comparatorLike $".$paramNum;
      $constraintValue = "%".$constraintValue."%";
    }

    $rParams = array($whereConditions, $constraintValue);
    return $rParams;
  }


  /**
   *
   * Get set of traceroute ids for given sql
   *
   * @param sql string (and TODO a where value - may be eliminated soon)
   *
   * @return array of TR ids
   *
   */
  public static function getTrIds($sql, $wParam)
  {
    global $dbconn;

    // old approach: used only for quick links and 'goesViaUS' constraint
    if ($wParam == "") {
      $result = pg_query($dbconn, $sql) or die('getTrIds query failed: ' . pg_last_error());
    } else {
      $result = pg_query_params($dbconn, $sql, array($wParam)) or die('getTrIds query failed: incorrect parameters');
    }

    $data = array();
    $lastId = 0;

    while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {
      $id = $line['traceroute_id'];
      if ($id != $lastId) {
        $data[] = $id;
      }
      $lastId = $id;
    }
    pg_free_result($result);

    $data = array_unique($data);

    return $data;
  }


  /**

  */
  public static function saveSearch($qArray)
  {
    global $dbconn, $myIp, $myCity;
    $data_json = json_encode($qArray);
    if ($myCity == "") {
      $myCity = "--";
    }
    $myCity = mb_convert_encoding($myCity, 'UTF-8', 'ISO-8859-1');

    $sql = "INSERT INTO s_log (timestamp, log, ip, city) VALUES (NOW(), '".$data_json."', '".$myIp."', '".$myCity."');";
    pg_query($dbconn, $sql) or die('Error! Insert Log failed: ' . pg_last_error());
  }


  /**
    A function to calculate distance between a pair of coordinates
  */
  public static function distance($lat1, $lng1, $lat2, $lng2, $miles = true)
  {
    $pi80 = M_PI / 180;
    $lat1 *= $pi80;
    $lng1 *= $pi80;
    $lat2 *= $pi80;
    $lng2 *= $pi80;

    $r = 6372.797; // mean radius of Earth in km
    $dlat = $lat2 - $lat1;
    $dlng = $lng2 - $lng1;
    $a = sin($dlat / 2) * sin($dlat / 2) + cos($lat1) * cos($lat2) * sin($dlng / 2) * sin($dlng / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $km = $r * $c;

    return ($miles ? ($km * 0.621371192) : $km);
  }

  public static function getColor()
  {
    $rand = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f');
    $color = '#'.$rand[rand(0,15)].$rand[rand(0,15)].$rand[rand(0,15)].$rand[rand(0,15)].$rand[rand(0,15)].$rand[rand(0,15)];
    return $color;
  }

  // functions for autocomplete ajax calls
  public static function getAutoCompleteData($sField)
  {
    global $dbconn;

    $tColumn = "";
    $tTable = "ip_addr_info";
    $tOrder = "";
    $tWhere = "";
    $tSelect = "SELECT distinct";
    $tLimit = "";

    if ($sField == "country") {
      $tColumn = "mm_country";
      $tOrder = $tColumn;
    } else if ($sField == "region") {
      $tColumn = "mm_region";
      $tOrder = $tColumn;
    } else if ($sField == "city") {
      $tColumn = "mm_city";
      $tOrder = $tColumn;
    } else if ($sField == "zipCode") {
      $tColumn = "mm_postal";
      $tOrder = $tColumn;
    } else if ($sField == "ISP") {
      $tTable = "as_users";
      $tColumn = "num, name";
      $tWhere = "WHERE short_name is not null";
      $tOrder = "name";
    } else if ($sField == "submitter") {
      $tTable = "traceroute";
      $tColumn = "submitter";
      $tOrder = "submitter";
    } else if ($sField == "destHostname") {
      $tTable = "traceroute";
      $tColumn = "dest";
      $tOrder = $tColumn;
    } else if ($sField == "subTimeGreaterThan" || "subTimeLessThan") {
      $tTable = "traceroute";
      $tColumn = "to_char(sub_time, 'YYYY-MM-DD')";
      $tOrder = "to_char desc";
      $tLimit = "LIMIT 10";
    }

    // loading all approach
    $sql = "$tSelect $tColumn FROM $tTable $tWhere ORDER BY $tOrder $tLimit";
    $result = array();
    $autoC = array();

    $result = pg_query($dbconn, $sql) or die('getAutoCompleteData query failed: ' . pg_last_error());

    while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {
      if ($sField == "ISP") {
        $autoC[$line['num']] = $line['name'];
      } else if ($tColumn == "to_char(sub_time, 'YYYY-MM-DD')") {
        $autoC[] = $line["to_char"];
      } else {
        $autoC[] = $line[$tColumn];
      }
    }
    $unique = array_unique($autoC);
    sort($unique);
    pg_free_result($result);
    pg_close($dbconn);

    return json_encode($unique);
  }
} // end class
?>