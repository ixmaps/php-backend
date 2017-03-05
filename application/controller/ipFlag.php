<?php
include('../config.php');
include('../model/IpFlag.php');

$myIp = $_SERVER['REMOTE_ADDR'];

if(!isset($_POST['action']) && !isset($_GET['action'])) {
	echo 'No parameters';
} else if(isset($_POST)) {
	//print_r($_POST);
	if($_POST['action']=='saveIpFlag'){
		echo IpFlag::saveFlags($_POST);
	} else if($_POST['action']=='getIpFlag'){
		$r = array(
			'ip_addr_info'=>IpFlag::getIpAddrInfo($_POST['ip_addr_f']),
			'ip_flags'=>IpFlag::getFlags($_POST)
		);
		//print_r($r);
		echo json_encode($r);	
	
	} else if($_GET['action']=='getFlagsLogs'){
		$fLog = IpFlag::getFlagsLogs();
		IpFlag::renderFlagLogs($fLog);

	}
}

?>