<?php
error_reporting(E_ALL);
/**
 * Functionality to update the annotated_traceroutes and traceroute_traits tables
 *
 * @param None
 *        Called manually to bulk insert the two tables (getTracerouteIdsToInsert)
 *        Called by gather-tr.php for single insert for now trs
 *        Called by cronjob (getTracerouteIdsToUpdate) to create array of trs to be updated
 *        as a result of a modified ip_addr (in the last 24 hrs)
 *
 * @return None (updated tables traceroute_traits and annotated_traceroutes)
 *
 * @since Updated Apr 2020
 * @author IXmaps.ca (Colin)
 *
 */

chdir(dirname(__FILE__));
require_once('../config.php');
require_once('../model/DerivedTable.php');

// BULK INSERTS
// $limit = 1000;
// getTracerouteIdsToInsert(1140654);

// CALLED BY CRONJOB
getTracerouteIdsToUpdate();


/**
  Selects all traceroute ids that need to be updated (date_modified has changed in the last day) and passes the trIds to updateTracerouteTraitsForTrId for an update. Intended for incomplete rows.
*/
function getTracerouteIdsToUpdate() {
  global $dbconn;

  $sql = "SELECT DISTINCT tr_item.traceroute_id as id FROM tr_item WHERE tr_item.ip_addr IN (SELECT ip_addr FROM ip_addr_info WHERE modified_at > date(current_date - 1) AND created_at < date(current_date - 1))";
  $result = pg_query($dbconn, $sql) or die('getTracerouteIdsToUpdate query failed: ' . pg_last_error());
  $trArr = pg_fetch_all($result);
  pg_free_result($result);

  echo "\n Newly modified ip_addrs found ".date("Y/m/d")."\n";

  loopOverTrIdsForDerivedTable($trArr);

  pg_close($dbconn);
}


/**
  Selects all traceroutes from trIdLast and passes the trIds to updateTracerouteTraitsForTrId for an update. Intended for incomplete rows.
*/
function getTracerouteIdsToInsert($trIdLast) {
  global $dbconn, $limit;

  $sql = "SELECT traceroute.id FROM traceroute WHERE traceroute.id > ".$trIdLast." order by traceroute.id LIMIT ".$limit;
  $result = pg_query($dbconn, $sql) or die('getTracerouteIdsToInsert query failed: ' . pg_last_error());
  $trArr = pg_fetch_all($result);
  pg_free_result($result);

  loopOverTrIdsForDerivedTable($trArr);

  pg_close($dbconn);
}


/**
  Loops over all of the trs that are passed in, sending them on to updateTracerouteTraitsForTrId
*/
function loopOverTrIdsForDerivedTable($trArr) {
  $startTime = microtime(true);
  $connGen = 0;

  if ($trArr) {
    foreach ($trArr as $key => $trId) {
      DerivedTable::updateForTrId($trId["id"]);
      $connGen++;
    }
  }

  if ($connGen == 0) {
    echo "\nNothing to do for now\n";
  } else {
    $timeElapsedSecs = microtime(true) - $startTime;
    echo "\nDuration: ".$timeElapsedSecs;
    echo "\n".$connGen. " TRs for traceroute_traits and annotate_traceroutes generated\n";
  }
}