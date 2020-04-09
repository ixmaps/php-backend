<?php
/**
 *
 * This handles queries from the tr-details frontend requests
 *
 * @param $_POST traceroute id
 *
 * @return two arrays of latency strings, formatted for tr details
 *
 * @author IXmaps.ca (Colin)
 *
 */
header("Access-Control-Allow-Origin: *");
include('../config.php');
include('../model/Traceroute.php');

if (!isset($_POST)) {
  echo '<br/><hr/>No parameters sent.';
} else {
  echo json_encode(Traceroute::getLatenciesForTraceroute($_POST['trId']));
}
?>
