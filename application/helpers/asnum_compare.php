<?php
/**
 * One off to compare our ASNs to MM
 *
 */

chdir(dirname(__FILE__));
require_once('../config.php');
require_once('../model/IXmapsMaxMind.php');

compareASNs();

function compareASNs() {
  global $dbconn;

  $sql = "SELECT * FROM ip_addr_info";
  $result = pg_query($dbconn, $sql) or die('compareASN query failed: ' . pg_last_error());
  $ips = pg_fetch_all($result);
  pg_free_result($result);

  if (!$ips) {
    echo "No IPs, exiting...";
    exit;
  }

  $startTime = microtime(true);
  $same = 0;
  $different = 0;

  foreach ($ips as $key => $ip) {
    $mm = new IXmapsMaxMind($ip["ip_addr"]);
    $mmAsn = $mm->getAsnum();
    echo "\nIX: ".$ip["asnum"]." | MM: ".$mmAsn;
    if ($mmAsn == $ip["asnum"]) {
      $same++;
    } else {
      $different++;
    }
  }

  $timeElapsedSecs = microtime(true) - $startTime;
  echo "\nDuration: ".$timeElapsedSecs;
  echo "\n".$same. " of our ASNs are the same as Maxmind\n";
  echo "\n".$different. " of our ASNs are different from Maxmind\n";

  pg_close($dbconn);
}
