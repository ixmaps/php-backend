<?php
/**
 *
 * This gets hit on load by:
 * - IXmapsClient. I believe this should be temp - TODO, remove me
 * 
 * @return structured geoloc values for front end user's ip
 *
 * Updated Aug 2019
 * @author IXmaps.ca (Anto, Colin)
 *
 */
header('Access-Control-Allow-Origin: *'); 
header('Content-type: application/json');
ini_set( "display_errors", 0); // use only in production 
include('../config.php');
require_once('../model/IXmapsMaxMind.php');

// MaxMind Include Files
// include('geoip.inc');
// include('geoipcity.inc');
// include('geoipregionvars.php');

if (isset($_POST)) {
  $inputJSON = file_get_contents('php://input');
} else {
  $inputJSON = "";
}

if ($inputJSON != "") {
  $input= json_decode( $inputJSON, TRUE); 
  if (isset($input['ip_address']) && filter_var($input['ip_address'], FILTER_VALIDATE_IP)) {
    $myIp = $input['ip_address'];
  } else {
    $errorA = array("error"=>"No ip_address provided or ip_address is not valid");
    echo json_encode($errorA);
  }
  
} else {
  $myIp = $_SERVER['REMOTE_ADDR'];
}

// collect geo-location data
// $gi1 = geoip_open($MM_dat_dir."/GeoLiteCity.dat",GEOIP_STANDARD);
// $record1 = geoip_record_by_addr($gi1,$myIp);
// $myCity = mb_convert_encoding($record1->city, "UTF-8"); 
// geoip_close($gi1);

// // collect ASN
// $giasn = geoip_open($MM_dat_dir."/GeoIPASNum.dat", GEOIP_STANDARD);
// $myAsnS = geoip_name_by_addr($giasn, $myIp);
// geoip_close($giasn);  

// if($myAsnS!=""){
//   $asnIspArray = extractAsn($myAsnS);
// } else {
//   $asnIspArray = array (null,null);
// }

$mm = new IXmapsMaxMind($myIp);

$result = array(
  "ip_address" => $myIp,
  "city" => $mm->getCity(),
  "region" => $mm->getRegion(),
  "country" => $mm->getCountryCode(),
  "postal_code"=> $mm->getPostalCode(),
  "asn" => $mm->getASNum(),
  "isp" => $mm->getASName(), 
  "lat" => $mm->getLat(),
  "lon" => $mm->getLong()
);
echo json_encode($result);


// $MaxMindArray = array(
//   "ip_address" => $myIp,
//   "city" => $myCity,
//   "region" => $record1->region,
//   "country" => $record1->country_code,
//   "postal_code"=>$record1->postal_code,
//   "asn" => $asnIspArray[0],
//   "isp" => $asnIspArray[1], 
//   "lat" =>$record1->latitude,
//   "lon" => $record1->longitude
// );
// echo json_encode($MaxMindArray);

// function extractAsn($asnString){
//   $asnArray = explode(' ', $asnString);
//   $asn = $asnArray[0];
//   $asn = substr($asn, 2);
//   $isp = "";
//   for ($i=1; $i < count($asnArray); $i++) { 
//     $isp .= $asnArray[$i]." ";
//   }
//   $isp = trim($isp);
//   return array($asn, $isp);
// }