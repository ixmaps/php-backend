<?php
// turn ON/OFF global php error message
ini_set( "display_errors", 0);

/* Db configuration */
$dbname     = 'ixmaps';
$dbuser     = 'ixmaps';
$dbpassword = 'xxxx';
$dbport     = '5432';

try {
  // Connecting, selecting database
  $dbconn = pg_connect("host=localhost dbname=$dbname user=$dbuser password=$dbpassword")
    or die('Could not connect to DB: ' . pg_last_error());
} catch(PDOException $e) {
  echo $e->getMessage();
}

#### URL and app directory
$webUrl = "https://www.ixmaps.ca";
$appRootPath = '/srv/www/website';
$savePath = $appRootPath.'/gm-temp';
$searchLog = "/home/ixmaps/log/search.log";

#### MaxMind data and include files
$MMDatDir = "/home/ixmaps/ix-data/mm-data";

#### IpInfo credentials
$IIAccessToken = 'fillMeIn';
// ip goes stale after 1 day
$ipStaleDate = 1;

$ixmapsDebugMode = true;
$trNumLimit = 500;
// CA, Toronto, US, US, Europe, China, Germany, Brazil
$genericMMLatLongs = ['60, -95', '43.6319, -79.3716', '38, -97', '37.751, -97.822', '47, 8', '35, 105', '51, 9', '-10, -55'];
?>
