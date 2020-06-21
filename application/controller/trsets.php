<?php
/**
 *
 * This controller handles requests regarding trsets and trset_targets
 *
 * Input is a post with data object containing 'action' key
 *
 * @author IXmaps.ca (Colin)
 * @since 2020 June 21
 *
 */
header("Access-Control-Allow-Origin: *");
include('../config.php');

$action = $_POST['action'];
if (!isset($_POST) || count($_POST) == 0 || empty($action)) {
  echo json_encode("Invalid parameters");
}
if ($action == 'getTrsetsAndTargets') {
  getTrsetsAndTargets();
}

function getTrsetsAndTargets() {
  global $dbconn;
  $sql = "select tt.id, ts.name, ts.description, ts.notes trset_notes, tt.id, tt.url, tt.category, tt.notes as target_notes from trset ts join trset_target tt on ts.id = tt.trset_id order by ts.id, tt.id;";
  $result = pg_query($dbconn, $sql) or die('Trset query failed: ' . pg_last_error());
  $dataArr = pg_fetch_all($result);
  echo json_encode($dataArr);
}

function getTrsets() {
  global $dbconn;
  $sql = "select tt.id, ts.name, ts.description, ts.notes trset_notes, tt.id, tt.url, tt.category, tt.notes as target_notes from trset ts join trset_target tt on ts.id = tt.trset_id order by ts.id, tt.id;";
  $result = pg_query($dbconn, $sql) or die('Trset query failed: ' . pg_last_error());
  $dataArr = pg_fetch_all($result);
  echo json_encode($dataArr);
}

function getTrsetTargetsForTrset() {

}