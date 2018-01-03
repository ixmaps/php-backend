<?php
class GeolocPtr
{
  /** Validate the incoming PTR JSON
    *
    * @param PTR post
    *
    * @return Bool
    *
    */
  public static function validatePtr($postArr)
  {
    $ptrJsonStructure = json_decode(self::ptrJsonStructureString, TRUE);

    // 1. confirm that all required keys are present in the submission
    foreach ($ptrJsonStructure as $key => $value) {
      if (is_null($postArr[$key])) {
        return false;
      }
    }
    // 2. confirm that all keys are not blank (TODO: do we want to include this error check?)
    foreach ($postArr as $key => $value) {
      if (empty($postArr[$key])) {
        return false;
      }
    }

    return true;
  }

  /** Handle errors specific to CIRA PTR submissions
    *
    * @param PTR post
    *
    * @return void (and die)
    *
    */
  public static function handleMalformedPtr($postArr)
  {
    // figure out error code
    // TODO: warp these elsewhere (from validatePtr)
    $ptrJsonStructure = json_decode(self::ptrJsonStructureString, TRUE);

    // not sure about this... feels insanely verbose
    $rc;
    foreach ($ptrJsonStructure as $key => $value) {
      if (is_null($postArr[$key])) {
        $rc = new ResponseCode(401, $key);
      }
    }
    foreach ($postArr as $key => $value) {
      if (empty($postArr[$key])) {
        $rc = new ResponseCode(402, $key);
      }
    }

    // create the status to send back to requester
    $response = array(
      "code" => $rc->getCode(),
      "message" => $rc->getMessage()
    );
    // send back the response to requester
    header('Content-type: application/json');
    echo json_encode($response);
    die;
  }


  /**
    * The archetypal JSON structure for PTR input
    */
  const ptrJsonStructureString = '{
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
