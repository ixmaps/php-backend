<?php

// turn ON/OFF global php error message
ini_set( "display_errors", 0);

/* Db configuration */
$dbname   = 'ixmaps';
$dbuser   = 'postgres';
$dbpassword = 'xxxx';
$dbport   = '5432';

try {
  // Connecting, selecting database
  $dbconn = pg_connect("host=localhost dbname=$dbname user=$dbuser password=$dbpassword")
    or die('Could not connect to DB: ' . pg_last_error());
}
catch(PDOException $e)
{
  echo $e->getMessage();
}

#### URL and app directory
$webUrl = "https://www.ixmaps.ca";
$appRootPath = '/var/www/ixmaps';
$savePath = $appRootPath.'/gm-temp';

#### MaxMind data and include files
// (IXmaps server)
$MM_geoip_dir = "/var/www/ixmaps/application/geoip";
$MM_dat_dir = "/home/ixmaps/ix-data/mm-data";

//////////////////////////////////////////////////////////
$trNumLimit=500;
$ixmaps_debug_mode = true;
$ixmaps_hands_off_config = array();

//////////////////////////////////////////////////////////
$coordExclude = array(
  '60,-95',
  '38,-97',
  '37.751,-97.822'
);
?>
