<?php
class GeolocPtr
{
  /** Return the completed GeoJson to the requester
    *
    * @param geoJson to return to requester, $statusObj to append to geoJson
    *
    * @return void
    *
    */
  public static function returnGeoJson($geoJson, $statusObj)
  {
    $geoJson['status'] = GeolocPtr::generateStatusObj($statusObj);
    header('Content-type: application/json');
    echo json_encode($geoJson);
    die;                              // is this bad practice?
  }


  /** Validate the incoming PTR JSON
    *
    * @param none
    *
    * @return statusObj
    *         {
    *           code: 123,
    *           message: 'abc'
    *         }
    *
    */
  public static function validateInputPtr()
  {
    $ptrJsonStructure = json_decode($ptrJsonStructureString, TRUE);

    // 1. confirm that all required keys are present in the submission
    foreach ($ptrJsonStructure as $key => $value) {
      if (is_null($_POST[$key])) {
        return new ResponseCode(401, $key);
      }
    }
    // 2. confirm that all keys are not blank (TODO: do we want to include this error check?)
    foreach ($_POST as $key => $value) {
      if (empty($_POST[$key])) {
        return new ResponseCode(402, $key);
      }
    }

    return new ResponseCode(201, '');
  }


  /** Generate any error / success code objects for requests
    * (this may want to move into a more general class at some point, if we have more API reqs)
    *
    * @param statusObj
    *         {
    *           code: 123,
    *           message: 'abc'
    *         }
    *
    * @return statusJson
    *         {
    *           code: 123,
    *           message: 'abc'
    *         }
    *
    */
  public static function generateStatusObj($responseObj)
  {

    switch ($responseObj->getCode()) {
      case 201:
        $message = "Success";
        break;
      case 401:
        $message = "Malformed JSON, missing key - " . $responseObj->getMessage();
        break;
      case 402:
        $message = "Malformed JSON, unset value for key - " . $responseObj->getMessage();
        break;
    }

    $statusJson = array(
      "code" => $responseObj->getCode(),
      "message" => $message
    );

    return $statusJson;
  }


  /**
    * The archetypal JSON structure for PTR input
    */
  private static $ptrJsonStructureString = '{
    "request_id": 123456789,
    "ipt_timestamp": "2017-12-31 23:59:59",
    "timeout": 750,
    "queries": 4,
    "ipt_client_ip": "321.321.321.321",
    "ipt_client_postal_code": "Saanichton",
    "ipt_client_asn": 32123,
    "submitter": "CIRA IPT",
    "submitter_ip": "162.219.50.11",
    "ipt_server_city": "Calgary",
    "ipt_server_postal_code": "T1U2V3",
    "maxhops": 24,
    "os": "Darwin",
    "protocol": "ICMP",
    "hop_data": [
      {
        "pass_num": 1,
        "terminate": false,
        "hops": [
          {
            "hop_num": 1,
            "ip": "70.67.160.1",
            "rtt": "9.65"
          },
          {
            "hop_num": 2,
            "ip": "70.67.160.1",
            "rtt": "9.65"
          }
        ]
      },
      {
        "pass_num": 2,
        "terminate": true,
        "hops": []
      }
    ]
  }';

}  // end of class