<?php

header("Access-Control-Allow-Origin: *");
include('../config.php');
include('../model/Traceroute.php');

$resultStats = array();

$result = pg_query($dbconn, "SELECT COUNT (DISTINCT traceroute.id) FROM public.traceroute");
if (!$result) {
  //echo "An error occured.\n";
  //exit;
}
$totTrs = pg_fetch_all($result);
$resultStats['traceroutes'] = $totTrs[0]['count'];

$result = pg_query($dbconn, "SELECT COUNT (DISTINCT traceroute.submitter) FROM public.traceroute");
$totSubmitters = pg_fetch_all($result);
$resultStats['submitters'] = $totSubmitters[0]['count'];

$result = pg_query($dbconn, "SELECT COUNT (DISTINCT traceroute.dest) FROM public.traceroute");
$totDestinations = pg_fetch_all($result);
$resultStats['destinations'] = $totDestinations[0]['count'];

$result = pg_query($dbconn, "SELECT traceroute.sub_time  FROM public.traceroute ORDER BY id DESC LIMIT 1;");
$latestContribution = pg_fetch_all($result);

$date = explode(' ', $latestContribution[0]['sub_time']);
$date1 = date_create($date[0]);
$resultStats['latest_contribution'] = date_format($date1, 'd M Y');

echo json_encode($resultStats);
?>