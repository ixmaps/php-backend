<?php
/**
 *
 * Little script designed to create a table to compare MaxMind values from GeoLite1 and Geolite2
 *
 * Created Sept 2019
 * @author Colin
 *
 */

require_once('../model/IXmapsMaxMind.php');
require_once('../model/Geolocation.php');

/*
  since most of our IPs were added before we switched to geolite2, we can use this opportunity to see how the lat/long values of the ips in our DB (analogous to geolite1) compare to the lat/long values in geolite2
*/

// create table geolite_comparison 
// ( 
//   ip_addr inet not null, 
//   ix_lat double precision,
//   ix_long double precision,
//   mm1_lat double precision,
//   mm1_long double precision,
//   mm2_lat double precision,
//   mm2_long double precision,
//   ix_city character varying(60),
//   ix_country character(2),
//   mm2_city character varying(60),
//   mm2_country character(2)
// );
// comment on table geolite_comparison is 'Comparison of l/l for ix(geolite1 or geocorrected), mm1 (geolite1), mm2 (geolite2)';


// TODO: put the relevant functions into a model for the future (IpAddress with getters and setters)


# for each IP in our DB, excluding those produced with geolite2 (via date)
global $dbconn;

$sql = "SELECT ip_addr, lat, long, mm_lat, mm_long, mm_city, mm_country FROM ip_addr_info WHERE datecreated < date('2019-08-28')";
$resp = pg_query($dbconn, $sql) or die('Error message: ' . pg_last_error());

$count = 0;
while ($row = pg_fetch_assoc($resp)) {
  $ip = $row['ip_addr'];
  $ix_lat = $row['lat'];
  $ix_long = $row['long'];
  $mm1_lat = $row['mm_lat'];
  $mm1_long = $row['mm_long'];
  $ix_city = $row['mm_city'];
  $ix_country = $row['mm_country'];

  # look up the MM value 
  $mm = new IXmapsMaxMind($ip);
  $mm2_lat = $mm->getLat();
  $mm2_long = $mm->getlong();
  $mm2_city = $mm->getCity();
  $mm2_country = $mm->getCountryCode();

  $sql = "INSERT INTO geolite_comparison (ip_addr, ix_lat, ix_long, mm1_lat, mm1_long, mm2_lat, mm2_long, ix_city, ix_country, mm2_city, mm2_country) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11);";

  $data = array($ip, $ix_lat, $ix_long, $mm1_lat, $mm1_long, $mm2_lat, $mm2_long, $ix_city, $ix_country, $mm2_city, $mm2_country);

  $result = pg_query_params($dbconn, $sql, $data);

  if ($result === false) {
    echo "Error: ".pg_last_error()."\n";
  } else {
    $count++;
    pg_free_result($result);
  }
}

// echo "Total inserted: ".$count."\n";
$fp = fopen('geolite_comparison_log.txt', 'w');
fwrite($fp, 'Complete with '.$myc.' inserted');
fclose($fp);
pg_free_result($resp);