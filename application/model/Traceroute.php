<?php
/**
 *
 * Traceroute class deals with a variety of things, we're in the process of tightening this up.
 * Key functions relate to map.php, receiving a set of constraints from the frontend, parsing
 * those for relevant tr ids, and generating traceroute objects for those ids
 *
 * @author IXmaps.ca (Colin, Antonio)
 * @since 2020 Apr
 *
 */
class Traceroute
{
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

    $trSet = array();
    $w = '';
    $table = '';

    $constraintValue = trim($c['constraint4']);
    // apply some default formating to constraint's value

    // DOES / DOESNOT
    if ($c['constraint1'] == 'does') {
      $compartorExact = '=';
      $compartorLike = 'LIKE';
    } else if ($c['constraint1'] == 'doesNot') {
      $compartorExact = '<>';
      $compartorLike = 'NOT LIKE';
    }

    // ORIGINATE / CONTAIN / GOVIA / TERMINATE?
    // TODO?
    if ($c['constraint2'] == 'originate') {
      $w .= " AND annotated_traceroutes.hop = 1";

    } else if($c['constraint2'] == 'terminate') {

    } else if($c['constraint2'] == 'goVia') {

      // this is a wrong assumption.
      //The destination ip is not always the last hop
      //$w.=" AND tr_item.attempt = 1 AND tr_item.hop > 1 AND (traceroute.dest_ip<>ip_addr_info.ip_addr)";

      $w .= " AND annotated_traceroutes.hop != 1";
      // TODO TEST THIS
      // $w.=" AND (annotated_traceroutes.hop != 1 AND annotated_traceroutes.hop != traceroute_traits.last_hop_num)";

    } else if($c['constraint2'] == 'contain') {

      // TODO TEST THIS
      // $w.=" AND tr_item.attempt = 1 ";
      $w = " ";
    }


    // CONSTRAINT3
    /* setting constraints associated to table annotated_traceroutes */
    if($c['constraint3'] == 'country') {
      $constraintValue = strtoupper($constraintValue);
      $table = 'annotated_traceroutes';
      $field = 'mm_country';
    } else if($c['constraint3'] == 'region') {
      $constraintValue = strtoupper($constraintValue);
      $table = 'annotated_traceroutes';
      $field = 'mm_region';
    } else if($c['constraint3'] == 'city') {
      $constraintValue = ucwords(strtolower($constraintValue));
      $table = 'annotated_traceroutes';
      $field = 'mm_city';
    } else if($c['constraint3'] == 'zipCode') {
      $table = 'annotated_traceroutes';
      $field = 'mm_postal';
    } else if($c['constraint3'] == 'ipAddr') {
      $table = 'annotated_traceroutes';
      $field = 'ip_addr';
    } else if($c['constraint3'] == 'hostName') {
      $table = 'annotated_traceroutes';
      $field = 'hostname';
    } else if($c['constraint3'] == 'asnum') {
      $table = 'annotated_traceroutes';
      $field = 'asnum';
    } else if($c['constraint3'] == 'ISP') {
      $table = 'annotated_traceroutes';
      $field = 'asname';
    /* setting constraints associated to table traceroute_traits */
    } else if($c['constraint3'] == 'submitter') {
      $table = 'traceroute_traits';
      $field = 'submitter';
    } else if($c['constraint3'] == 'zipCodeSubmitter') {
      $table = 'traceroute_traits';
      $field = 'submitter_zip_code';
    } else if($c['constraint3'] == 'destHostName') {
      $table = 'traceroute_traits';
      $field = 'dest_hostname';
    } else if($c['constraint3'] == 'trId') {
      $table = 'traceroute_traits';
      $field = 'traceroute_id';
    }


    // AND / OR
    if ($c['constraint5'] == '') {
      $operand = 'AND';
    } else  {
      $operand = $c['constraint5'];
    }


    // Exact matches - TODO, add a lot more exacts, the like fields are destroying performance
    if ($field == 'asnum' || $field == 'traceroute_id' || $field == 'ip_addr') {
      $w.=" AND $table.$field $compartorExact $".$paramNum;

    // Similar matches
    } else {
      $w.=" AND $table.$field $compartorLike $".$paramNum;
      // TODO test why only this includes the constraintValue (then rename it)
      $constraintValue = "%".$constraintValue."%";
    }

    $rParams = array($w, $constraintValue);
    return $rParams;
  }


  /**
   *
   * Get set of traceroute ids for given sql
   *
   * @param sql string (and a where value - may be eliminated soon)
   *
   * @return array of TR ids
   *
   */
  public static function getTrSet($sql, $wParam)
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
    $data1 = array_unique($data);
    $dbQuerySummary .= " | Traceroutes: <b>".count($data1).'</b>';
    pg_free_result($result);
    unset($data);

    return $data1;
  }


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
    $trSets = array();
    $constraintNum = 0;

    // loop over the constraints
    foreach ($data as $constraint) {
      // Something to display on the frontend - gets passed around as a global and added to by various pieces. Probably not the best way to handle this...
      if ($constraintNum > 0) {
        $dbQuerySummary .= '<br>';
      }
      $dbQuerySummary .= '<b>'.$constraint['constraint1'].' : '.$constraint['constraint2'].' : '.$constraint['constraint3'].' : '.$constraint['constraint4'].' : '.$constraint['constraint5'].'</b>';

      $wParams = array();

      $sql = "SELECT annotated_traceroutes.traceroute_id FROM annotated_traceroutes, traceroute_traits WHERE annotated_traceroutes.traceroute_id = traceroute_traits.traceroute_id";

      // $sql = "SELECT as_users.num, tr_item.traceroute_id, traceroute.id, ip_addr_info.mm_city, ip_addr_info.ip_addr, ip_addr_info.asnum FROM as_users, tr_item, traceroute, ip_addr_info WHERE (tr_item.traceroute_id=traceroute.id) AND (ip_addr_info.ip_addr=tr_item.ip_addr) AND (as_users.num=ip_addr_info.asnum)";


      // "SELECT as_users.num, tr_item.traceroute_id, traceroute.id, ip_addr_info.mm_city, ip_addr_info.ip_addr,
      // ip_addr_info.asnum FROM as_users, tr_item, traceroute, ip_addr_info WHERE (tr_item.traceroute_id=traceroute.id) AND
      // (ip_addr_info.ip_addr=tr_item.ip_addr) AND (as_users.num=ip_addr_info.asnum) AND tr_item.attempt = 1 AND
      // ip_addr_info.mm_city NOT LIKE $1 order by tr_item.traceroute_id, tr_item.hop, tr_item.attempt"

      $sqlOrder = ' order by annotated_traceroutes.traceroute_id, annotated_traceroutes.hop';

      // adding exception for doesnot cases
      if ($constraint['constraint1'] == 'doesNot' && $constraint['constraint2'] != 'originate' && $constraint['constraint2'] != 'terminate') {

        $wParams = Traceroute::buildWhere($constraint);
        $sql .= $wParams[0].$sqlOrder;

        $trSets[$constraintNum] = Traceroute::getTrSet($sql, $wParams[1]);

        // feels like this can be eliminated entirely - why the exception?
        // to go back to prev, will need to compare to master

        // $positiveSet = array();
        // $sqlPositive = $sql;
        // $wParams = Traceroute::buildWhere($constraint);
        // $sqlPositive .= $wParams[0].$sqlOrder;
        // $positiveSet = Traceroute::getTrSet($sqlPositive, $wParams[1]);

        // $oppositeSet = array();
        // $sqlOposite = $sql;
        // $wParams = Traceroute::buildWhere($constraint);
        // $sqlOposite .= $wParams[0].$sqlOrder;
        // $oppositeSet = Traceroute::getTrSet($sqlOposite, $wParams[1]);

        // $trSets[$constraintNum] = $positiveSet;// array_diff($positiveSet, $oppositeSet);

        // unset($oppositeSet);
        // unset($positiveSet);

      // adding an exception for "terminate"
      } else if ($constraint['constraint2'] == 'terminate') {
        // TODO!
        $sql = "SELECT as_users.num, traceroute_traits.last_hop_num, traceroute_traits.terminated, traceroute.id, ip_addr_info.mm_city, ip_addr_info.ip_addr, ip_addr_info.asnum FROM traceroute_traits, as_users, traceroute, ip_addr_info WHERE (as_users.num = ip_addr_info.asnum) AND (traceroute.id = traceroute_traits.last_hop_num) AND (ip_addr_info.ip_addr = traceroute_traits.last_hop_ip_addr) ";

        $sqlOrder = ' order by traceroute.id';

        $wParams = Traceroute::buildWhere($constraint);
        $sql .= $wParams[0].$sqlOrder;

        $trSets[$constraintNum] = Traceroute::getTrSet($sql, $wParams[1]);

      } else {
        $wParams = Traceroute::buildWhere($constraint);
        $sql .= $wParams[0].$sqlOrder;

        $trSets[$constraintNum] = Traceroute::getTrSet($sql, $wParams[1]);
      }

      $constraintNum++;

    } // end foreach

    // var_dump($trSets);die;

    $trSetResult = array();
    // merge sets based on AND/OR conditions
    for ($i = 0; $i < $constraintNum; $i++) {
      $trSetResultTemp = array();
      // only one constraint
      if ($i == 0) {
        $trSetResult = array_merge($trSetResult, $trSets[0]);

      // all in between
      } else if ($i > 0) {
        // OR cases
        if ($data[$i-1]['constraint5'] == 'OR') {
          $trSetResultTemp = array_merge($trSetResult, $trSets[$i]);
          $trSetResultTemp = array_unique($trSetResultTemp);
          $trSetResult =  array_merge($trSetResult, $trSetResultTemp);

        // AND cases
        } else {
          $trSetResultTemp = array_intersect($trSetResult, $trSets[$i]);
        }

        $trSetResult =  array();
        $empty = array();
        $trSetResult = array_merge($empty, $trSetResultTemp);
      }
    } // end for
    $trSetResultLast =  array_unique($trSetResult);

    $dbQuerySummary .= "<br/>";

    unset($trSetResult);
    unset($trSetResultTemp);
    unset($trSets);

    return $trSetResultLast;
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
      $sql = "select traceroute_id as id from traceroute_traits where nsa = true";
      return Traceroute::getTrSet($sql, "");

    } else if ($qlArray[0]['constraint2'] == "lastSubmission") {
      $sql = "select id from traceroute order by sub_time desc limit 20";
      return Traceroute::getTrSet($sql, "");

    } else if ($qlArray[0]['constraint2'] == "singleRoute") {
      $sql = "select id from traceroute where id = ".$qlArray[0]['constraint3'];
      return Traceroute::getTrSet($sql, "");

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

    $sampleOfTrIds = array_rand(array_flip($trIds), $trNumLimit);
    $idsStr = implode(", ", $sampleOfTrIds);

    // free some memory
    unset($trIds);

    $sql = "
      SELECT at.traceroute_id, at.hop, at.rtt1, at.rtt2, at.rtt3, at.rtt4, at.min_latency, at.ip_addr, at.hostname, at.lat, at.long, at.mm_city, at.mm_country, at.gl_override, at.asnum, at.asname, ip_addr_info.flagged, tt.submitter, tt.sub_time, tt.submitter_zip_code, tt.origin_asnum, tt.origin_asname, tt.origin_city, tt.origin_country, tt.first_hop_asnum, tt.first_hop_asname, tt.first_hop_city, tt.first_hop_country, tt.last_hop_asnum, tt.last_hop_asname, tt.last_hop_city, tt.last_hop_country, tt.dest_hostname, tt.dest_ip_addr, tt.dest_asnum, tt.dest_asname, tt.dest_city, tt.dest_country, tt.last_hop_ip_addr, tt.terminated
      FROM annotated_traceroutes at, ip_addr_info, traceroute_traits tt
      WHERE at.traceroute_id = tt.traceroute_id AND at.ip_addr = ip_addr_info.ip_addr
      AND tt.traceroute_id IN (".$idsStr.")
      order by at.traceroute_id, at.hop";

    $result = pg_query($dbconn, $sql) or die('Query failed: ' . pg_last_error());
    $trArr = pg_fetch_all($result);

    $trData = array();
    $totHops = 0;
    $traceroute = array();
    $allTraceroutes = array();

    // start loop over tr data array where i is an index of all hops
    for ($i = 0; $i < count($trArr); $i++) {
      $totHops++;

      // if we've finished up a previous route, save the route to the results structure
      if ($i !== 0 && $traceroute["traceroute_id"] != $trArr[$i-1]['traceroute_id']) {
        $allTraceroutes[$traceroute["traceroute_id"]] = $traceroute;
      }

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

    } // end for

    $results = array(
      'trsFound' => $trsFound,
      'trsReturned' => $trNumLimit,
      'totHops' => $totHops,
      'result' => json_encode($allTraceroutes)
    );

    return $results;
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
    $tSelect = 'SELECT';

    if ($sField == "country") {
      $tColumn = 'mm_country';
      $tOrder = $tColumn;
    } else if ($sField == "region") {
      $tColumn = 'mm_region';
      $tOrder = $tColumn;
    } else if ($sField == "city") {
      $tColumn = 'mm_city';
      $tOrder = $tColumn;
    } else if ($sField == "zipCode") {
      $tColumn = 'mm_postal';
      $tOrder = $tColumn;
    } else if ($sField == "ISP") {
      $tColumn = 'num, name';
      $tOrder = "name";
      $tTable = "as_users";
      $tWhere = "WHERE short_name is not null";
    } else if ($sField == "submitter") {
      $tSelect = 'SELECT distinct';
      $tColumn = 'submitter';
      $tOrder = "submitter";
      $tTable = "traceroute";
      $tWhere = "";
    }

    // loading all approach
    $sql = "$tSelect $tColumn FROM $tTable $tWhere ORDER BY $tOrder";
    $result = array();
    $autoC = array();

    $result = pg_query($dbconn, $sql) or die('Query failed: ' . pg_last_error());

    while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {
      if ($sField == "ISP") {
        $autoC[$line['num']] = $line['name'];
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


