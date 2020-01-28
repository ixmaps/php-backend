<?php
/**
 * We no longer use this. The functionality has been added to gather_tr, so that the
 * tr_last_hops inserts happen when a new route is submitted by the client.
 *
 * Update the 'helper' tables that is used by the query engine:
 * -- tr_last_hop --
 * Shows last hop of traceroutes. This is needed for the new approach in query search,
 * as the destination is now calculated using last hop and not destination ip.
 * 
 *
 * @param Triggered by cron (check if there are new traceroutes every 20 minute)
 *
 * @return None (updated tables tr_last_hop)
 *
 * @since Updated Oct 2019
 * @author IXmaps.ca (Anto, Colin)
 *
 */
chdir(dirname(__FILE__));
require_once('../config.php');

$lastTrId = getLastTrIdGen();
echo 'Last TRid generated: '.$lastTrId;
collectLastHop($lastTrId);

/**
  Iterates over all Traceroutes and collects last hop data and generates a SQL file
  This is needed for the new approach in query search. The destination is now calculated using last hop and not destination ip.
*/
function collectLastHop($trIdLast)
{
  global $dbconn, $dbQueryHtml, $savePath;

  $start = microtime(true);

  $sqlCo = '';

  // production approach
  $sql = "SELECT traceroute.id FROM traceroute WHERE traceroute.id > ".$trIdLast." order by traceroute.id LIMIT 200";

  $result = pg_query($dbconn, $sql) or die('Query failed: ' . pg_last_error());
  $trArr = pg_fetch_all($result);

  $conn = 0;
  $connGen = 0;

  if ($trArr) {
    foreach ($trArr as $key => $trId) {

      $sqlLastHop = "SELECT tr_item.hop, tr_item.traceroute_id, traceroute.id, traceroute.dest, traceroute.dest_ip, ip_addr_info.ip_addr FROM tr_item, traceroute, ip_addr_info WHERE (tr_item.traceroute_id=traceroute.id) AND (ip_addr_info.ip_addr=tr_item.ip_addr) AND tr_item.attempt = 1 AND tr_item.hop > 1 and traceroute.id=".$trId['id']." order by tr_item.hop DESC LIMIT 1";


      $result1 = pg_query($dbconn, $sqlLastHop) or die('Query failed: ' . pg_last_error());
      $lastHopArr = pg_fetch_all($result1);

      $reached = 1;
      if ($lastHopArr[0]["ip_addr"] != $lastHopArr[0]["dest_ip"]) {
        $reached = 0;
        $conn++;
      }

      $sqlInsert = "INSERT INTO tr_last_hops VALUES (".$lastHopArr[0]["traceroute_id"].", ".$lastHopArr[0]["hop"].", '".$lastHopArr[0]["ip_addr"]."', ".$reached.");";

      try {
        if ($sqlInsert!="INSERT INTO tr_last_hops VALUES (, , '', 1);") {
          pg_query($dbconn, $sqlInsert) or die('Query failed: ' . pg_last_error());
        } else {
          echo "<br/>This TR has no hops. Empty record...";
        }

      } catch(Exception $e) {
        echo "db error.";
      }

      $connGen++;

    } // end foreach
  }// end if not empty

  $time_elapsed_secs = microtime(true) - $start;

  if ($connGen == 0) {
    echo "Nothing to do for now\n";
  } else {
    echo "\nStarting at : ".$trIdLast.". \n". $connGen. " TRs last hop generated.
    \nDuration: ".$time_elapsed_secs;
  }

  pg_free_result($result);
  pg_close($dbconn);
}

function getLastTrIdGen() {
  global $dbconn;
  $sql = "SELECT traceroute_id_lh FROM tr_last_hops ORDER BY traceroute_id_lh DESC LIMIT 1";
  $result = pg_query($dbconn, $sql) or die('Query failed: ' . pg_last_error());
  $lastTRId = pg_fetch_all($result);
  pg_free_result($result);
  $lastId = $lastTRId[0]['traceroute_id_lh'];
  return $lastId;
}
