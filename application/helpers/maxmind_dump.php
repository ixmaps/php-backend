<?php
// error_reporting(E_ALL);
/**
 * One off to dump Maxmind into our DB
 *
 */

chdir(dirname(__FILE__));
require_once('../config.php');
require_once('../model/IXmapsMaxMind.php');

dumpMM();

function dumpMM() {
  global $dbconn;

  $sql = "SELECT ip_addr FROM ipinfo_ip_addr WHERE ip_addr NOT IN (SELECT ip_addr FROM maxmind_ip_addr) LIMIT 10000";
  $result = pg_query($dbconn, $sql) or die('dumpMM query failed: ' . pg_last_error());
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
    echo "\n".$mm->getIpAddr();
    if (!is_null($mm->getIpAddr())) {
      $sql = "INSERT INTO maxmind_ip_addr (
          ip_addr,
          hostname,
          asnum,
          city,
          region,
          country,
          postal,
          lat,
          long
        ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)";
        $data = array(
          $mm->getIpAddr(),
          $mm->getHostname(),
          $mm->getASNum(),
          $mm->getCity(),
          $mm->getRegionCode(),
          $mm->getCountryCode(),
          $mm->getPostalCode(),
          $mm->getLat(),
          $mm->getLong()
        );
      $result = pg_query_params($dbconn, $sql, $data);
      if ($result === false) {
        echo "maxmind_ip_addr insert query failed: " . pg_last_error();
      }
      pg_free_result($result);

      $updates++;
    } else {
      $mmMisses++;
    }
  }

  $timeElapsedSecs = microtime(true) - $startTime;
  echo "\nDate: ".Date("d m Y");
  echo "\nDuration: ".$timeElapsedSecs;
  echo "\n".$updates. " IPs inserted\n";
  echo "\n".$mmMisses. " IPs had bad ipAddrs in Maxmind\n";

  pg_close($dbconn);
}
