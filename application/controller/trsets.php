<?php
/**
 *
 * This controller handles requests regarding trsets and trset_targets
 *
 * Input is a post with data object containing 'action' key (and sometimes a 'trset_name' key)
 *
 * @author IXmaps.ca (Colin)
 * @since 2020 June 21
 *
 */
header("Access-Control-Allow-Origin: *");
include('../config.php');

$post = file_get_contents('php://input');
$postArr = json_decode($post, TRUE);
$action = $postArr['action'];

if ($action == 'getTrsetsAndTargets') {
  getTrsetsAndTargets();
} else if ($action == 'getTrsets') {
  getTrsets();
} else if ($action == 'getTrset') {
  $trset_name = $postArr['target'];
  getTrset($trset_name);
}

// used by website trsets.php
function getTrsetsAndTargets() {
  global $dbconn;
  $sql = "select tt.id, ts.name, ts.description, ts.notes trset_notes, tt.id, tt.url, tt.category, tt.notes as target_notes, tt.reachable from trset ts join trset_target tt on ts.id = tt.trset_id order by ts.id, tt.id;";
  $result = pg_query($dbconn, $sql) or die('Trset query failed: ' . pg_last_error());
  $dataArr = pg_fetch_all($result);
  echo json_encode($dataArr);
}

// used by IXmapsClient
function getTrsets() {
  global $dbconn;
  $sql = "select name from trset;";
  $result = pg_query($dbconn, $sql) or die('Trset query failed: ' . pg_last_error());
  $dataArr = pg_fetch_all($result);
  echo json_encode($dataArr);
}

// used by IXmapsClient
function getTrset($trset_name) {
  global $dbconn;
  if ($trset_name == "All targets") {
    $sql = "select url from trset_target where reachable = true;";
  } else {
    $sql = "select url from trset_target where reachable = true and trset_id = (select id from trset where name='".$trset_name."');";
  }

  $result = pg_query($dbconn, $sql) or die('Trset query failed: ' . pg_last_error());
  $dataArr = pg_fetch_all($result);
  echo json_encode($dataArr);
}