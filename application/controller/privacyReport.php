<?php
include('../config.php');
include('../model/PrivacyReport.php');
if(!isset($_POST) || count($_POST)==0)
{
	echo '<br/><hr/>No parameters sent.';
} else {
	$privacyReport = PrivacyReport::getPrivacyData();	
	echo json_encode($privacyReport);
}
?>