<?php


// FIXME - make minimal (check on frontend too)
// localhost:8000 issue?

/**
 *
 * This handles queries from the tr-details frontend requests
 * 
 * @param $_POST traceroute id
 *
 * @return array of latency strings, formatted for tr details
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
