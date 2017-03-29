<?php
header('Access-Control-Allow-Origin: *'); 
//ini_set( "display_errors", 0); // use only in production 
include('../config.php');
include('../model/GatherTr.php');


// use only for debugging 
if((isset($_REQUEST['trid']) && $_REQUEST['trid']!="") || (isset($_REQUEST['tr_c_id']) && $_REQUEST['tr_c_id']!=""))
{
	if(isset($_REQUEST['trid'])){
		$trid = $_REQUEST['trid'];
	} else {
		$trid = 0;
	}

	if(isset($_REQUEST['tr_c_id'])){
		$tr_c_id = $_REQUEST['tr_c_id'];
	} else {
		$tr_c_id = 0;
	}

	$c = GatherTr::getTrContribution($tr_c_id, $trid);
	//echo "TR Data saved!\n\n";
	// fix json return: assume now not order in the contributions
	for ($i=0; $i < count($c['traceroute_submissions']); $i++) { 
		if($c['traceroute_submissions'][$i]['data_type']=="json"){
			$c['traceroute_submissions'][$i]['tr_data']= json_decode($c['traceroute_submissions'][$i]['tr_data']);
		}
	}
	echo json_encode($c);

} else {
	echo 'No tr_c_id or trid sent.';
}
?>