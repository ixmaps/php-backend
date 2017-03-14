<?php
header("Access-Control-Allow-Origin: *");
ini_set( "display_errors", 0); // force show errors for debug
/* include files */
include('../config.php');
include('../model/Traceroute.php');
include('../model/IXmapsMaxMind.php'); 

$myIp = $_SERVER['REMOTE_ADDR'];

/* MM: needed for search log */
$mm = new IXmapsMaxMind();
//$myIp = $_SERVER['REMOTE_ADDR']; // Get user IP for search log
//$myIp = "186.108.108.134";
$geoIp = $mm->getGeoIp($myIp);

//print_r($geoIp);

$myCity = "";
$myCountry = "";

if(isset($geoIp['geoip']['city'])){
	$myCity = $geoIp['geoip']['city'];
}
if(isset($geoIp['geoip']['country_code'])){
	$myCountry = $geoIp['geoip']['country_code'];
}

/* TODO: Refine search of geodata location based on proximity to major city. Reuse other functions  */

$mm->closeDatFiles();

/* Performance vars */
$mtime = microtime();
$mtime = explode(" ",$mtime);
$mtime = $mtime[1] + $mtime[0];
$starttime = $mtime;

if(!isset($_POST) || count($_POST)==0)
{
	$error = array(
		"error"=> "No parameters sent to Map Backend"
		);
	echo json_encode($error);
	
} else {

	//print_r($_POST);

	$dbQueryHtml = "";
	$trHtmlTable = "";
	$dbQuerySummary = "";
	$totTrFound = 0;

	$totFilters = count($_POST);
	$dataArray = array();

	foreach($_POST as $constraint)
	{
		$dataArray[] = $constraint;
	}

	if ($dataArray[0]['constraint1']=="quickLink") {
		$b = Traceroute::processQuickLink($dataArray);
	} else {
		$b = Traceroute::getTraceRoute($dataArray);
	}

	$data = json_encode($dataArray);
	$saveLog = Traceroute::saveSearch($data);
	
	if(count($b)!=0) {
		$ixMapsData = Traceroute::getIxMapsData($b);
		//print_r($ixMapsData);

		$ixMapsDataT = Traceroute::dataTransform($ixMapsData);
		//print_r($ixMapsDataT);

		$ixMapsDataStats = Traceroute::generateDataForGoogleMaps($ixMapsDataT);

		$trHtmlTable = Traceroute::renderTrSets($ixMapsDataT);

/*		print_r($trHtmlTable);
		print_r($ixMapsDataT);*/
	}

		// end calculation of execution time
		$mtime = microtime();
		$mtime = explode(" ",$mtime);
		$mtime = $mtime[1] + $mtime[0];
		$endtime = $mtime;
		$totaltime = ($endtime - $starttime);
		$totaltime = number_format($totaltime,2);
		//echo "<hr/>This page was created in <b>".$totaltime."</b> seconds";

		// add db query results/errors
		$ixMapsDataStats['querySummary']=$dbQuerySummary;
		$ixMapsDataStats['queryLogs']=$dbQueryHtml;

		//$ixMapsDataStats['queryLogs']=.$dbQueryHtml.'<hr/>'.$saveLog;


		// add excec time
		$ixMapsDataStats['execTime']=$totaltime;

		// add server side renerated table;
		$ixMapsDataStats['trsTable']=$trHtmlTable;
		$ixMapsDataStats['totTrsFound']=$totTrFound;
		

		//print_r($ixMapsDataStats);

		echo json_encode($ixMapsDataStats);

}