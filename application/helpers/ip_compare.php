<?php
/**
 * Evolving script to compare IP address values between different data sources
 *
 */

chdir(dirname(__FILE__));
require_once('../config.php');
require_once('../model/IXmapsMaxMind.php');
require_once('../model/IXmapsIpInfo.php');

compareIps();

function compareIps() {
  global $dbconn;

  $outfile = fopen('ip_compare_outfile.csv', 'w');
  $headers = "IP, IX_country, MM_country, IP_country, IX_city, MM_city, IP_city, IX_lat, MM_lat, IP_lat, IX_long, MM_long, IP_long\n";
  fwrite($outfile, $headers);

  $sql = "SELECT * FROM ip_addr_info WHERE ip_addr in (SELECT ip_addr FROM ipinfo_ip_addrs) LIMIT 50000";
  $result = pg_query($dbconn, $sql) or die('compareIP query failed: ' . pg_last_error());
  $ips = pg_fetch_all($result);
  pg_free_result($result);

  $startTime = microtime(true);

  if (!$ips) {
    echo "No IPs, exiting...";
    exit;
  }

  $ix_eq_mm_country_count = 0;
  $ix_eq_ip_country_count = 0;
  $mm_eq_ip_country_count = 0;
  $all_eq_country_count   = 0;

  foreach ($ips as $key => $ix) {
    $mm = new IXmapsMaxMind($ix["ip_addr"]);
    $ipinfo = new IXmapsIpInfo($ix["ip_addr"]);

    echo "\nip: ".$ix["ip_addr"];
    echo "\nIX: ".$ix["mm_country"];
    echo "\nMM: ".$mm->getCountryCode();
    echo "\nIP: ".$ipinfo->getCountryCode();

    if ($ix["mm_country"] == $mm->getCountryCode()) {
      $ix_eq_mm_country_count++;
    }
    if ($ix["mm_country"] == $ipinfo->getCountryCode()) {
      $ix_eq_ip_country_count++;
    }
    if ($mm->getCountryCode() == $ipinfo->getCountryCode()) {
      $mm_eq_ip_country_count++;
    }
    if ($ix["mm_country"] == $mm->getCountryCode() && $mm->getCountryCode() == $ipinfo->getCountryCode()) {
      $all_eq_country_count++;
    }

    $row = $ix["ip_addr"].",".
           $ix["mm_country"].",".$mm->getCountryCode().",".$ipinfo->getCountryCode().",".
           $ix["mm_city"].",".$mm->getCity().",".$ipinfo->getCity().",".
           $ix["mm_lat"].",".$mm->getLat().",".$ipinfo->getLat().",".
           $ix["mm_long"].",".$mm->getLong().",".$ipinfo->getLong();

    fwrite($outfile, $row."\n");
  }


  $timeElapsedSecs = microtime(true) - $startTime;
  echo "\nDuration: ".$timeElapsedSecs;
  echo "\nTotal IPs: ".sizeof($ips);
  echo "\nIX equals MM count: ".$ix_eq_mm_country_count;
  fwrite($outfile, "\nIX equals MM count: ".$ix_eq_mm_country_count);
  echo "\nIX equals IP count: ".$ix_eq_ip_country_count;
  fwrite($outfile, "\nIX equals IP count: ".$ix_eq_ip_country_count);
  echo "\nMM equals IP count: ".$mm_eq_ip_country_count;
  fwrite($outfile, "\nMM equals IP count: ".$mm_eq_ip_country_count);
  echo "\nAll equal count: ".$all_eq_country_count;
  fwrite($outfile, "\nAll equal count: ".$all_eq_country_count);

  fclose($outfile);
  pg_close($dbconn);
}
