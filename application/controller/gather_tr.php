<?php
/**
 * Functionality to handle interaction btw IXmapsClient and backend/db (eg traceroute submission)
 *
 * @param Request POSTS from IXmapsClient
 *
 * @return JSON response to IXmapsClient
 *
 * @since Updated Aug 2019
 * @author IXmaps.ca (Anto, Colin)
 *
 */
header('Access-Control-Allow-Origin: *');
require_once('../config.php');
require_once('../model/GatherTr.php');
require_once('../model/IXmapsMaxMind.php');

// can we reliably assume that there will always be an IP?
$myIp = $_SERVER['REMOTE_ADDR'];

$mm = new IXmapsMaxMind($myIp);

$_POST['submitter_ip'] = $myIp;
$_POST['city'] = "";
if (!isset($_POST['city']) && $mm->getCity()) {
  $_POST['city'] = $mm->getCity();
}
$_POST['country'] = "";
if ($mm->getCity()) {
  $_POST['country'] = $mm->getCity();
}
$_POST['submitter_asnum'] = $mm->getASNum();
$_POST['privacy'] = 8;
$_POST['client'] = "";
$_POST['cl_version'] = "";


/*
  TODO: add exhaustive check for consistency and completeness of the TR data
  Requires discussion with tests on the IXmapsClient
*/
if (isset($_POST['dest_ip']) && $_POST['dest_ip'] != "") {
  $trGatherMessage = "";
  $saveTrResult = GatherTr::saveTrContribution($_POST);
  $tr_c_id = $saveTrResult['tr_c_id'];

  if ($saveTrResult['tr_c_id'] == 0) {
    $trGatherMessage.=" ".$saveTrResult['error'];
  }

  $b = GatherTr::saveTrContributionData($_POST, $tr_c_id);
  $trData = GatherTr::getTrContribution($tr_c_id);
  $trByHop = GatherTr::analyzeIfInconsistentPasses($trData);

  // Exclude contributions with less than 2 hops
  if (count($trByHop['tr_by_hop']) < 2) {
    $trData['tr_flag'] = 4;
    $trGatherMessage .= "Insufficient Traceroute responses. (Contribution id: ".$tr_c_id.")";
    $trId = 0;
  } else {
    $trGatherMessage = "";
    $trAnalyzed = GatherTr::selectBestIp($trByHop['tr_by_hop']);
    $trData['ip_analysis']=$trAnalyzed;
    $trData['tr_flag']=$trByHop['tr_flag'];

    $publishResult = GatherTr::publishTraceroute($trData);

    if (!$publishResult['publishControl']) {
      $trData['tr_flag'] = 4;
      $trGatherMessage = "Insufficient Traceroute responses. (Contribution id: ".$tr_c_id.")";
      $trId = 0;
    } else if ($publishResult['publishControl'] && $publishResult['trId'] == 0) {
      $trData['tr_flag'] = 5;
      $trGatherMessage = "An error occurred in the server when saving Traceroute data. The error has been saved for further analysis (Contribution id: ".$tr_c_id.")";
      $trId = 0;
    } else if ($publishResult['publishControl'] && $publishResult['trId'] != 0) {
      // Success: tr_flag = 2 or 3
      $trGatherMessage = "Traceroute data saved successfully. ".$publishResult['tot_hops']." Hops were found.";
      $trId = $publishResult['trId'];
    }
  }

  $f = GatherTr::flagContribution($tr_c_id, $trId, $trData['tr_flag']);
  $result = array(
    "TRid"=>$trId,
    "tr_c_id"=>$tr_c_id,
    "message"=> $trGatherMessage,
    "tr_flag"=>$trData['tr_flag']
  );

  // return json to the IXmapsClient
  echo json_encode($result);

} else {
  echo 'No parameters sent.';
}
?>