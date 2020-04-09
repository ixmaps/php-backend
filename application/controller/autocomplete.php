<?php
header("Access-Control-Allow-Origin: *");
include('../config.php');
include('../model/Traceroute.php');

if (!isset($_POST) || count($_POST) == 0)
{
  echo '<br/><hr/>No parameters sent.';
} else {
  $sField = $_POST['field'];
  echo Traceroute::getAutoCompleteData($sField);
}
?>