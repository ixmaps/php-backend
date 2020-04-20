<?php
/**
 *
 * Traceroute class deals with a variety of things, we're in the process of tightening this up.
 * Key functions relate to map.php, receiving a set of constraints from the frontend, parsing
 * those for relevant tr ids, and generating traceroute objects for those ids to be returned to
 * the frontend
 *
 * @author IXmaps.ca (Colin, Antonio)
 * @since 2020 Apr
 *
 */
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
  public static function getTracerouteIdsForConstraints($data)
  {
    global $dbconn, $dbQuerySummary;
    $trIdsForConstraint = array();
    $constraintNum = 0;

    // loop over the constraints
    foreach ($data as $constraint) {
      // Something to display on the frontend - gets passed around as a global and added to by various pieces. Probably not the best way to handle this...
      if ($constraintNum > 0) {
        $dbQuerySummary .= '<br>';
      }
      $dbQuerySummary .= '<b>'.$constraint['constraint1'].' | '.$constraint['constraint2'].' | '.$constraint['constraint3'].' | '.$constraint['constraint4'].' | '.$constraint['constraint5'].'</b><br />';

      $wParams = array();

      $sql = "SELECT annotated_traceroutes.traceroute_id FROM annotated_traceroutes, traceroute_traits WHERE annotated_traceroutes.traceroute_id = traceroute_traits.traceroute_id";

      // add the constraint to the sql
      $wParams = Traceroute::buildWhere($constraint);
      $sql .= $wParams[0];

      // execute the sql (getting the TR data to return)
      $trIdsForConstraint[$constraintNum] = Traceroute::getTrIds($sql, $wParams[1]);

      $constraintNum++;

    } // end foreach

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

    $dbQuerySummary .= "<br/>";

    unset($trIdsForConstraint);

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
  public static function getTracerouteDataForIds($trIds)
  {
    global $dbconn, $trNumLimit;

    $trsFound = count($trIds);

    // if there is more than 1 tr, take a sampling
    if ($trsFound > $trNumLimit) {
      $numTrsSampled = $trNumLimit;
      $trIds = array_rand(array_flip($trIds), $numTrsSampled);
    } else {
      $numTrsSampled = $trsFound;
    }
    $idsStr = implode(", ", $trIds);

    // free some memory
    unset($trIds);

    $sql = "
      SELECT at.traceroute_id, at.hop, at.rtt1, at.rtt2, at.rtt3, at.rtt4, at.min_latency, at.ip_addr, at.hostname, at.lat, at.long, at.mm_city, at.mm_country, at.gl_override, at.asnum, at.asname, ip_addr_info.flagged, tt.submitter, tt.sub_time, tt.submitter_zip_code, tt.origin_asnum, tt.origin_asname, tt.origin_city, tt.origin_country, tt.first_hop_num, tt.first_hop_asnum, tt.first_hop_asname, tt.first_hop_city, tt.first_hop_country, tt.last_hop_asnum, tt.last_hop_asname, tt.last_hop_city, tt.last_hop_country, tt.dest_hostname, tt.dest_ip_addr, tt.dest_asnum, tt.dest_asname, tt.dest_city, tt.dest_country, tt.last_hop_ip_addr, tt.terminated
      FROM annotated_traceroutes at, ip_addr_info, traceroute_traits tt
      WHERE at.traceroute_id = tt.traceroute_id AND at.ip_addr = ip_addr_info.ip_addr
      AND tt.traceroute_id IN (".$idsStr.")
      order by at.traceroute_id desc, at.hop";

    $result = pg_query($dbconn, $sql) or die('Query failed: ' . pg_last_error());
    $trArr = pg_fetch_all($result);

    $trData = array();
    $totHops = 0;
    $traceroute = array();
    $allTraceroutes = array();

    // start loop over tr data array where i is an index of all hops
    for ($i = 0; $i < count($trArr); $i++) {
      $totHops++;

      // for each new route
      if ($i == 0 || $traceroute["traceroute_id"] != $trArr[$i-1]['traceroute_id']) {
        $traceroute["traceroute_id"] = $trArr[$i]['traceroute_id'];
        // set the metadata for this route
        $traceroute["metadata"] = array(
          'traceroute_id' => $trArr[$i]['traceroute_id'],
          'submitter' => $trArr[$i]['submitter'],
          'sub_time' => $trArr[$i]['sub_time'],
          'submitter_zip_code' => $trArr[$i]['submitter_zip_code'],
          'origin_asnum' => $trArr[$i]['origin_asnum'],
          'origin_asname' => $trArr[$i]['origin_asname'],
          'origin_city' => $trArr[$i]['origin_city'],
          'origin_country' => $trArr[$i]['origin_country'],
          'first_hop_num' => $trArr[$i]['first_hop_num'],
          'first_hop_asnum' => $trArr[$i]['first_hop_asnum'],
          'first_hop_asname' => $trArr[$i]['first_hop_asname'],
          'first_hop_city' => $trArr[$i]['first_hop_city'],
          'first_hop_country' => $trArr[$i]['first_hop_country'],
          'last_hop_asnum' => $trArr[$i]['last_hop_asnum'],
          'last_hop_asname' => $trArr[$i]['last_hop_asname'],
          'last_hop_city' => $trArr[$i]['last_hop_city'],
          'last_hop_country' => $trArr[$i]['last_hop_country'],
          'dest_hostname' => $trArr[$i]['dest_hostname'],
          'dest_ip_addr' => $trArr[$i]['dest_ip_addr'],
          'dest_asnum' => $trArr[$i]['dest_asnum'],
          'dest_asname' => $trArr[$i]['dest_asname'],
          'dest_city' => $trArr[$i]['dest_city'],
          'dest_country' => $trArr[$i]['dest_country'],
          'last_hop_ip_addr' => $trArr[$i]['last_hop_ip_addr'],
          'terminated' => $trArr[$i]['terminated'],
        );
      }

      // add the hop for each iteration of the loop
      $traceroute["hops"][$trArr[$i]['hop']] = array(
        'hop' => $trArr[$i]['hop'],
        'rtt1' => $trArr[$i]['rtt1'],
        'rtt2' => $trArr[$i]['rtt2'],
        'rtt3' => $trArr[$i]['rtt3'],
        'rtt4' => $trArr[$i]['rtt4'],
        'min_latency' => $trArr[$i]['min_latency'],
        'ip_addr' => $trArr[$i]['ip_addr'],
        'hostname' => $trArr[$i]['hostname'],
        'lat' => $trArr[$i]['lat'],
        'long' => $trArr[$i]['long'],
        'mm_city' => $trArr[$i]['mm_city'],
        'mm_country' => $trArr[$i]['mm_country'],
        'gl_override' => $trArr[$i]['gl_override'],
        'asnum' => $trArr[$i]['asnum'],
        'asname' => $trArr[$i]['asname'],
        'flagged' => $trArr[$i]['flagged']
      );

      // if this route is finished, aka if this is the last hop or
      // the next hop has a different tr_id, save this route to allTraceroutes
      if ($i+1 == count($trArr) || $traceroute["traceroute_id"] != $trArr[$i+1]['traceroute_id']) {
        $allTraceroutes[$traceroute["traceroute_id"]] = $traceroute;
      }

    } // end for

    $results = array(
      'trsFound' => $trsFound,
      'trsReturned' => $numTrsSampled,
      'totHops' => $totHops,
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
    global $dbconn, $ixmaps_debug_mode;

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
    // we ignore the contain, since it does not limit the where conditions
    if ($c['constraint2'] == 'originate') {
      // $whereConditions .= " AND annotated_traceroutes.hop = traceroute_traits.first_hop_num";
      $whereConditions .= " AND annotated_traceroutes.hop = 1";

    } else if($c['constraint2'] == 'terminate') {
      $whereConditions .= " AND annotated_traceroutes.hop = traceroute_traits.last_hop_num";

    } else if($c['constraint2'] == 'goVia') {
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
    } else if ($c['constraint3'] == 'destHostName') {
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
    global $dbconn, $dbQuerySummary;

    // old approach: used only for quick links
    if ($wParam == "") {
      $result = pg_query($dbconn, $sql) or die('Query failed: ' . pg_last_error());
    } else {
      $result = pg_query_params($dbconn, $sql, array($wParam)) or die('Query failed: incorrect parameters');
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
    $dbQuerySummary .= " Traceroutes: <b>".count($data).'</b>';

    return $data;
  }


  /**

  */
  public static function saveSearch($qArray)
  {
    global $dbconn, $myIp, $myCity;
    $data_json = json_encode($qArray);
    if ($myCity == "") {
      $myCity="--";
    }
    $myCity=utf8_encode($myCity);
    // last
    $sql = "INSERT INTO s_log (timestamp, log, ip, city) VALUES (NOW(), '".$data_json."', '".$myIp."', '".$myCity."');";
    pg_query($dbconn, $sql) or die('Error! Insert Log failed: ' . pg_last_error());
  }

  public static function renderSearchLog()
  {
    global $dbconn;
    $html = '<table border="1">';
    $c = 0;
    $sql = "select * from s_log order by id DESC";
    $result = pg_query($dbconn, $sql) or die('Query failed: ' . pg_last_error());
    while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {
      $id=$line['id'];
      $ip=$line['ip'];
      $city=$line['city'];
      $timestamp=$line['timestamp'];
      $log=$line['log'];
      $log=str_replace('"[', '[', $log);
      $log=str_replace(']"', ']', $log);
      $logToArray = json_decode($log, true);

      $c++;

      $html .= '<tr>';
      $html .= '<td><a href="#">'.$id.'</a></td>';
      $html .= '<td>'.$ip.'</td>';
      $html .= '<td>'.$city.'</td>';
      $html .= '<td>'.$timestamp.'</td>';

      $q = '<td>';
      foreach ($logToArray as $constraint) {
        $q .='<br/> | '
        .$constraint['constraint1'].' | '
        .$constraint['constraint2'].' | '
        .$constraint['constraint3'].' | '
        .$constraint['constraint4'].' | '
        .$constraint['constraint5'].' | ';
      }

      $q .= '</td>';
      $html .= ''.$q;
      $html .= '</tr>';
    }
    $html .= '</table>';
    pg_free_result($result);
    pg_close($dbconn);
    echo 'Tot queries: '.$c.'<hr/>';
    echo $html;
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
    $tSelect = "SELECT";

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
      $tSelect = "SELECT distinct";
      $tTable = "traceroute";
      $tColumn = "submitter";
      $tOrder = "submitter";
    } else if ($sField == "destHostname") {
      $tSelect = "SELECT distinct";
      $tTable = "traceroute";
      $tColumn = "dest";
      $tOrder = $tColumn;
    } else if ($sField == "subTimeGreaterThan" || "subTimeLessThan") {
      $tSelect = "SELECT distinct";
      $tTable = "traceroute";
      $tColumn = "to_char(sub_time, 'YYYY-MM-DD')";
      $tOrder = "to_char asc";
    }

    // loading all approach
    $sql = "$tSelect $tColumn FROM $tTable $tWhere ORDER BY $tOrder";
    $result = array();
    $autoC = array();

    $result = pg_query($dbconn, $sql) or die('Query failed: ' . pg_last_error());

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

$as_num_color = array (
   "174"  => "E431EB",
   "3356"  => "EB7231",
   "7018"  => "42EDEA",
   "7132"  => "42EDEA",
   "-1"  => "676A6B",
   "577"  => "3D49EB",
   "1239"  => "ECF244",
   "6461"  => "E3AEEB",
   "6327"  => "9C6846",
   "6453"  => "676A6B",
   "3561"  => "676A6B",
   "812"  => "ED0924",
   "20453"  => "ED0924",
   "852"  => "4BE625",
   "13768"  => "419C6B",
   "3257"  => "676A6B",
   "1299"  => "676A6B",
   "22822"  => "676A6B",
   "6939"  => "676A6B",
   "376"  => "676A6B",
   "32613"  => "676A6B",
   "6539"  => "3D49EB",
   "15290"  => "676A6B",
   "5769"  => "676A6B",
   "855"  => "676A6B",
   "26677"  => "676A6B",
   "271"  => "676A6B",
   "6509"  => "676A6B",
   "3320"  => "676A6B",
   "23498"  => "676A6B",
   "549"  => "676A6B",
   "239"  => "676A6B",
   "11260"  => "676A6B",
   "1257"  => "676A6B",
   "20940"  => "676A6B",
   "23136"  => "676A6B",
   "5645"  => "676A6B",
   "21949"  => "676A6B",
   "8111"  => "676A6B",
   "13826"  => "676A6B",
   "16580"  => "676A6B",
   "9498"  => "676A6B",
   "802"  => "676A6B",
   "19752"  => "676A6B",
   "11854"  => "676A6B",
   "7992"  => "676A6B",
   "17001"  => "676A6B",
   "611"  => "676A6B",
   "19080"  => "676A6B",
   "26788"  => "676A6B",
   "12021"  => "676A6B",
   "33554"  => "676A6B",
   "30528"  => "676A6B",
   "16462"  => "676A6B",
   "11700"  => "676A6B",
   "14472"  => "676A6B",
   "13601"  => "676A6B",
   "11032"  => "676A6B",
   "12093"  => "676A6B",
   "10533"  => "676A6B",
   "26071"  => "676A6B",
   "32156"  => "676A6B",
   "5764"  => "676A6B",
   "27168"  => "676A6B",
   "33361"  => "676A6B",
   "32489"  => "676A6B",
   "15296"  => "676A6B",
   "10400"  => "676A6B",
   "10965"  => "676A6B",
   "18650"  => "676A6B",
   "36522"  => "676A6B",
   "19086"  => "676A6B"
);
?>


