<?php
include('../config.php');
include('../model/Traceroute.php');

if(!isset($_POST) || count($_POST)==0)
{
	echo '<br/><hr/>No parameters sent.';
} else {
	$sField = $_POST['field'];
	$sKeyword = $_POST['keyword'];
	echo Traceroute::getAutoCompleteData($sField, $sKeyword);
}
?>