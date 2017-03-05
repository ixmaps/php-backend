<?php
// NEW config.php: updated Dec 1, 2015

// turn ON/OFF global php error message
ini_set( "display_errors", 1);

/* Db configuration */
$dbname 	= 'ixmaps';
$dbuser		= 'postgres';
$dbpassword	= 'postgres123';
$dbport		= '5432';

// Connecting, selecting database
/*$dbconn = pg_connect("host=localhost dbname=$dbname user=$dbuser password=$dbpassword")
    or die('Could not connect to DB: ' . pg_last_error());*/


try {
	// Connecting, selecting database
	$dbconn = pg_connect("host=localhost dbname=$dbname user=$dbuser password=$dbpassword")
	    or die('Could not connect to DB: ' . pg_last_error());

   //$dbconn = new PDO('pgsql:host=localhost;port='.$dbport.';dbname='.$dbname.';user='.$dbuser.';password='.$dbpassword);
   //echo "PDO connection object created";
}
catch(PDOException $e)
{
      echo $e->getMessage();
}

#### URL and app directory
$webUrl = "http://localhost";
$appRootPath = '/Users/antonio/mywebapps/website2017';
$savePath = $appRootPath.'/gm-temp';

#### MaxMind data and include files
// (IXmaps server)
/*$MM_geoip_dir = "/var/www/ixmaps/application/geoip";
$MM_dat_dir = "/home/ixmaps/ix-data/mm-data";*/

// (ANTO LOCAL)
$MM_geoip_dir = "/Users/antonio/mywebapps/ixmaps-php-backend/application/geoip";
$MM_dat_dir = "/Users/antonio/mywebapps/ixmaps-php-backend/mm-data";


//////////////////////////////////////////////////////////
//$trNumLimit=800; // 500 is very safe num with the new approach
$trNumLimit=10; 
$ixmaps_debug_mode = true;
$ixmaps_hands_off_config = array();

//////////////////////////////////////////////////////////
$coordExclude = array(
	'60,-95',
	'38,-97'
	);
?>
