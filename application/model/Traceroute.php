<?php

class Traceroute
{
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

  /**
    A handy function to clean up html code
  */
  public static function strip_only($str, $tags, $stripContent = false)
  {
    $content = '';
    if(!is_array($tags)) {
      $tags = (strpos($str, '>') !== false ? explode('>', str_replace('<', '', $tags)) : array($tags));
      if(end($tags) == ''){
        array_pop($tags);
      }
    }
    foreach($tags as $tag) {
      if ($stripContent){
        $content = '(.+</'.$tag.'[^>]*>|)';
      }
      $str = preg_replace('#</?'.$tag.'[^>]*>'.$content.'#is', '', $str);
    }
    return $str;
  } // end function

  /**
    Key function !! creates a where SQL based on explore submitted constraints
  */
  public static function buildWhere($c, $doesNotChk = false, $paramNum = 1)
  {
    global $dbconn, $ixmaps_debug_mode;

    $trSet = array();
    $w = '';
    $table = '';

    $constraint_value = trim($c['constraint4']);
    // apply some default formating to constraint's value

    if ($c['constraint1'] == 'does' || $doesNotChk == true) {
      $selector_s = 'LIKE';
      $selector_i = '=';
    } else {
      $selector_s = 'NOT LIKE';
      $selector_i = '<>';
    }

    if ($c['constraint5'] == '') {
      $operand = 'AND';
    } else  {
      $operand = $c['constraint5'];
    }

    /* setting constraints associated to table ip_addr_info */
    if($c['constraint3']=='country') {
      $constraint_value = strtoupper($constraint_value);
      $table = 'ip_addr_info';
      $field='mm_country';
    } else if($c['constraint3']=='region') {
      $constraint_value = strtoupper($constraint_value);
      $table = 'ip_addr_info';
      $field='mm_region';
    } else if($c['constraint3']=='city') {
      $constraint_value = ucwords(strtolower($constraint_value));
      $table = 'ip_addr_info';
      $field='mm_city';
    } else if($c['constraint3']=='ISP') {
      //$constraint_value = ucwords(strtolower($constraint_value));
      $constraint_value = $constraint_value;
      $table = 'as_users';
      $field='name';
    } else if($c['constraint3']=='NSA') {
      $table = 'ip_addr_info';
      $field='mm_city';
    } else if($c['constraint3']=='zipCode') {
      //$constraint_value = strtoupper($constraint_value);
      $table = 'ip_addr_info';
      $field='mm_postal';
    } else if($c['constraint3']=='asnum') {
      $table = 'ip_addr_info';
      $field='asnum';
    } else if($c['constraint3']=='submitter') {
      $table = 'traceroute';
      $field='submitter';
    } else if($c['constraint3']=='zipCodeSubmitter') {
      $table = 'traceroute';
      $field='zip_code';
    } else if($c['constraint3']=='destHostName') {
      $table = 'traceroute';
      $field='dest';
    } else if($c['constraint3']=='ipAddr') {
      $table = 'ip_addr_info';
      $field='ip_addr';
    } else if($c['constraint3']=='trId') {
      $table = 'traceroute';
      $field='id';
    } else if($c['constraint3']=='hostName') {
      $table = 'ip_addr_info';
      $field='hostname';
    }

    if($c['constraint2']=='originate') {
      $w.=" AND tr_item.hop = 1 AND tr_item.attempt = 1";
    } else if($c['constraint2']=='terminate') {


    } else if($c['constraint2']=='goVia') {

      // this is a wrong assumption.
      //The destination ip is not always the last hop
      //$w.=" AND tr_item.attempt = 1 AND tr_item.hop > 1 AND (traceroute.dest_ip<>ip_addr_info.ip_addr)";

      $w.=" AND tr_item.attempt = 1 AND tr_item.hop > 1 ";

      // FIX ME. need to exclude last ip.


    } else if($c['constraint2']=='contain') {

      $w.=" AND tr_item.attempt = 1 ";

    }

    // Using pg_query_params
    if (($field=='asnum') || ($field=='id') || ($field=='ip_addr')) {
      $w.=" AND $table.$field $selector_i $".$paramNum;
    } else {
      $w.=" AND $table.$field $selector_s $".$paramNum;
      $constraint_value = "%".$constraint_value."%";
    }
    $rParams = array($w, $constraint_value);
    return $rParams;
  }

  /**
    Key function !! Get TR data for a given sql query
  */
  public static function getTrSet($sql, $wParam)
  {
    global $dbconn, $dbQuerySummary;
    $trSet = array();

    // old approach: used only for quick links
    if ($wParam == "") {
      $result = pg_query($dbconn, $sql) or die('Query failed: ' . pg_last_error());
    } else {
      $result = pg_query_params($dbconn, $sql, array($wParam)) or die('Query failed: incorrect parameters');
    }

    $data = array();
    $lastId = 0;

    $c = 0;
    while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {
      $c++;
      $id = $line['id'];
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
    Get TR details data for a current TR: this is quite expensive computationally and memory
    Only used to perform advanced analysis that need to look at all the attempts and all the hops in a TR
  */
  public static function getTracerouteAll($trId)
  {
    global $dbconn;
    $result = array();
    $trArr = array();
    // adding exception to prevent error with tr id with no tr_items
    if ($trId!='') {
      $sql = 'SELECT traceroute.id, tr_item.* FROM traceroute, tr_item WHERE (tr_item.traceroute_id=traceroute.id) AND traceroute.id = '.$trId.' ORDER BY tr_item.traceroute_id, tr_item.hop, tr_item.attempt';

      $result = pg_query($dbconn, $sql) or die('Query failed on getTracerouteAll: ' . pg_last_error() . 'SQL: '. $sql . " TRid: ".var_dump($trId));

      //$tot = pg_num_rows($result);
      // get all data in a single array
      $trArr = pg_fetch_all($result);
    }
    return $trArr;
  }


  /**
    * CM: this needs a lot of work, but I think the idea is to process the constraints
    * and return a set of tr_ids (?)
    *
    * @param $data is a set of constraints received from the frontend
    *
    * @return array of tr_ids
    *
    */
  public static function getTracerouteIdsForConstraints($data)
  {
    global $dbconn, $dbQuerySummary;
    $result = array();
    $trSets = array();
    $conn = 0;
    $offset = 0;
    $doesNotChk = false;

    // loop over the constraints
    foreach($data as $constraint)
    {
      // Something to display on the frontend - gets passed around as a global and added to by various pieces. This should obviously be built on the front end instead...
      if ($conn > 0) {
        $dbQuerySummary .= '<br>';
      }
      $dbQuerySummary .= '<b>'.$constraint['constraint1'].' : '.$constraint['constraint2'].' : '.$constraint['constraint3'].' : '.$constraint['constraint4'].' : '.$constraint['constraint5'].'</b>';

      $w = '';
      $wParams = array();

      $sql = "SELECT as_users.num, tr_item.traceroute_id, traceroute.id, ip_addr_info.mm_city, ip_addr_info.ip_addr, ip_addr_info.asnum FROM as_users, tr_item, traceroute, ip_addr_info WHERE (tr_item.traceroute_id=traceroute.id) AND (ip_addr_info.ip_addr=tr_item.ip_addr) AND (as_users.num=ip_addr_info.asnum)";

      $sqlOrder = ' order by tr_item.traceroute_id, tr_item.hop, tr_item.attempt';

      // adding exception for doesnot cases
      if ($constraint['constraint1'] == 'doesNot' && $constraint['constraint2'] != 'originate' && $constraint['constraint2'] != 'terminate') {

        $oppositeSet = array();
        $positiveSet = array();

        $wParams = Traceroute::buildWhere($constraint);
        $sqlTemp = $sql;
        $sqlTemp.=$wParams[0].$sqlOrder;
        $positiveSet = Traceroute::getTrSet($sqlTemp, $wParams[1]);

        $doesNotChk = true;

        $sqlOposite = $sql;

        $wParams = Traceroute::buildWhere($constraint, $doesNotChk);
        $sqlOposite .= $wParams[0].$sqlOrder;
        $oppositeSet = Traceroute::getTrSet($sqlOposite, $wParams[1]);

        $trSets[$conn] = array_diff($positiveSet, $oppositeSet);

        $doesNotChk = false;
        unset($oppositeSet);
        unset($positiveSet);

      // adding an exception for "terminate"
      } else if ($constraint['constraint2'] == 'terminate') {
        $sql = "SELECT as_users.num, traceroute_traits.last_hop_num, traceroute_traits.terminated, traceroute.id, ip_addr_info.mm_city, ip_addr_info.ip_addr, ip_addr_info.asnum FROM traceroute_traits, as_users, traceroute, ip_addr_info WHERE (as_users.num = ip_addr_info.asnum) AND (traceroute.id = traceroute_traits.last_hop_num) AND (ip_addr_info.ip_addr = traceroute_traits.last_hop_ip_addr) ";

        $sqlOrder = ' order by traceroute.id';

        $wParams = Traceroute::buildWhere($constraint);
        $w.=''.$wParams[0];

        $sql .=$w.$sqlOrder;

        $trSets[$conn] = Traceroute::getTrSet($sql, $wParams[1]);
        $operands[$conn] = $constraint['constraint5'];

      } else {
        $wParams = Traceroute::buildWhere($constraint);
        $w.=''.$wParams[0];

        $sql .=$w.$sqlOrder;

        $trSets[$conn] = Traceroute::getTrSet($sql, $wParams[1]);
        $operands[$conn] = $constraint['constraint5'];
      }

      $conn++;

    } // end foreach

    $trSetResult = array();

    for ($i = 0; $i < $conn; $i++) {
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

    // FIXME: move this to the client. make this count based on the # of TR resulting in the set
    // It's already done. need to fix UI loading of data

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
    * @return array of tr_ids
    *
    */
  public static function processQuickLink($qlArray)
  {
    global $dbQuerySummary;

    if ($qlArray[0]['constraint2'] == "viaNSACity") {
      $sql = "select distinct(tr_item.traceroute_id) as id from tr_item join ip_addr_info on tr_item.ip_addr = ip_addr_info.ip_addr where ip_addr_info.mm_city in ('San Francisco', 'Los Angeles', 'New York', 'Dallas', 'Washington', 'Ashburn', 'Seattle', 'San Jose', 'San Diego', 'Miami', 'Boston', 'Phoenix', 'Salt Lake City', 'Nashville', 'Denver', 'Saint Louis', 'Bridgeton', 'Bluffdale', 'Houston', 'Chicago', 'Atlanta', 'Portland');";
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
    * @param set of tr_ids (?)
    *
    * @return array of traceroutes
    *
    */
  public static function getTracerouteDataForIds($data)
  {
    global $dbconn, $trNumLimit;

    $trsFound = count($data);

    // set index increase if total traceroutes is > $trNumLimit
    if ($trsFound > $trNumLimit) {
      $indexJump = $trsFound / $trNumLimit;
      $indexJump = intval($indexJump) + 1;
    } else {
      $indexJump = 1;
    }

    $wTrs = '';

    $c = 0;
    // build SQL where for the given TR set
    for ($i = 0; $i < $trsFound; $i += $indexJump) {
      $trCollected[] = $data[$i];
      if ($c == 0) {
        $wTrs.=' tt.traceroute_id='.$data[$i];
      } else {
        $wTrs.=' OR tt.traceroute_id='.$data[$i];
      }
      $c++;
    }

    // free some memory
    unset($data);

    $sql = "
      SELECT at.traceroute_id, at.hop, at.rtt1, at.rtt2, at.rtt3, at.rtt4, at.min_latency, at.ip_addr, at.hostname, at.lat, at.long, at.mm_city, at.mm_country, at.gl_override, at.asnum, at.asname, ip_addr_info.flagged, tt.submitter, tt.sub_time, tt.submitter_zip_code, tt.origin_asnum, tt.origin_asname, tt.origin_city, tt.origin_country, tt.first_hop_asnum, tt.first_hop_asname, tt.first_hop_city, tt.first_hop_country, tt.last_hop_asnum, tt.last_hop_asname, tt.last_hop_city, tt.last_hop_country, tt.dest_hostname, tt.dest_ip_addr, tt.dest_asnum, tt.dest_asname, tt.dest_city, tt.dest_country, tt.last_hop_ip_addr, tt.terminated
      FROM annotated_traceroutes at, ip_addr_info, traceroute_traits tt
      WHERE at.traceroute_id = tt.traceroute_id AND at.ip_addr = ip_addr_info.ip_addr";
    $sql.=" AND (".$wTrs.")";
    $sql.=" order by at.traceroute_id, at.hop";

    // free some memory
    $wTrs = '';

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


