<?php
// error_reporting(E_ALL);
/**
 * One off to correct the -1 and NULL ASNs
 *
 */

chdir(dirname(__FILE__));
require_once('../config.php');
require_once('../model/IXmapsMaxMind.php');

getBadAsns();

function getBadAsns() {
  global $dbconn;

  $sql = "SELECT ip_addr FROM ip_addr_info WHERE asnum = -1 OR asnum is NULL";
  $result = pg_query($dbconn, $sql) or die('badASN query failed: ' . pg_last_error());
  $ips = pg_fetch_all($result);
  pg_free_result($result);

  if (!$ips) {
    echo "No IPs, exiting...";
    exit;
  }

  $startTime = microtime(true);
  $updates = 0;
  $mmMisses = 0;

  foreach ($ips as $key => $ip) {
    $mm = new IXmapsMaxMind($ip["ip_addr"]);
    $newAsn = $mm->getAsnum();
    echo "\n".$newAsn;
    if (!is_null($newAsn) && $newAsn != -1) {
      $updates++;
    } else {
      $mmMisses++;
    }
  }

  $timeElapsedSecs = microtime(true) - $startTime;
  echo "\nDate: ".Date("d m Y");
  echo "\nDuration: ".$timeElapsedSecs;
  echo "\n".$updates. " IPs updated\n";
  echo "\n".$mmMisses. " IPs had bad asns in Maxmind\n";

  pg_close($dbconn);
}
