<?php
class ParisTraceroute {
  private $request_id;
  private $ipt_timestamp;
  private $timeout;
  private $queries;     // this one is not clear enough
  private $ipt_client_ip;
  private $ipt_client_postal_code;
  private $ipt_client_asn;
  private $submitter;
  private $submitter_ip;
  private $ipt_server_city;
  private $ipt_server_postal_code;
  private $maxhops;
  private $os;
  private $protocol;
  private $hop_data;
  //array to declare the param?
  function __construct(array $ptrJson) {
    foreach($ptrJson as $key => $val) {
      if(property_exists(__CLASS__, $key)) {
        $this->$key = $val;
      }
    }
  }

  // public function hasKey($key) {
  //   if () {
  //     return true;
  //   }

  //   return false;
  // }

  public function getRequestId() {
    return $this->request_id;
  }

  public function getIptTimestamp() {
    return $this->ipt_timestamp;
  }

  public function getTimeout() {
    return $this->timeout;
  }

  public function getQueries() {
    return $this->queries;
  }

  public function getClientIp() {
    return $this->ipt_client_ip;
  }

  public function getClientPostalCode() {
    return $this->ipt_client_postal_code;
  }

  public function getClientAsn() {
    return $this->ipt_client_asn;
  }

  public function getSubmitter() {
    return $this->submitter;
  }

  public function getSubmitterIp() {
    return $this->submitter_ip;
  }

  public function getServerCity() {
    return $this->ipt_server_city;
  }

  public function getServerPostalCode() {
    return $this->ipt_server_postal_code;
  }

  public function getMaxHops() {
    return $this->maxhops;
  }

  public function getOs() {
    return $this->os;
  }

  public function getProtocol() {
    return $this->protocol;
  }

  public function getHopData() {
    return $this->hop_data;
  }


  // This makes logical sense to have here, but the submission must be validated before the object is created... eg if the submission has a key/value pair that the object cannot set

  // /** Validate the incoming PTR JSON
  //   *
  //   * @param none
  //   *
  //   * @return statusObj
  //   *         {
  //   *           code: 123,
  //   *           message: 'abc'
  //   *         }
  //   *
  //   */
  // public static function isValid($ptr)
  // {
  //   // how do I access this outside of this function? I don't want to add it to the constructor
  //   $ptrJsonStructureString = '{
  //     "request_id": 123456789,
  //     "ipt_timestamp": "2017-12-31 23:59:59",
  //     "timeout": 750,
  //     "queries": 4,
  //     "ipt_client_ip": "321.321.321.321",
  //     "ipt_client_postal_code": "Saanichton",
  //     "ipt_client_asn": 32123,
  //     "submitter": "CIRA IPT",
  //     "submitter_ip": "162.219.50.11",
  //     "ipt_server_city": "Calgary",
  //     "ipt_server_postal_code": "T1U2V3",
  //     "maxhops": 24,
  //     "os": "Darwin",
  //     "protocol": "ICMP",
  //     "hop_data": [
  //       {
  //         "pass_num": 1,
  //         "terminate": false,
  //         "hops": [
  //           {
  //             "hop_num": 1,
  //             "ip": "70.67.160.1",
  //             "rtt": "9.65"
  //           },
  //           {
  //             "hop_num": 2,
  //             "ip": "70.67.160.1",
  //             "rtt": "9.65"
  //           }
  //         ]
  //       },
  //       {
  //         "pass_num": 2,
  //         "terminate": true,
  //         "hops": []
  //       }
  //     ]
  //   }';
  //   $ptrJsonStructure = json_decode($ptrJsonStructureString, TRUE);

  //   // 1. confirm that all required keys are present in the submission
  //   foreach ($ptrJsonStructure as $key => $value) {
  //     if (is_null($ptr->$key)) {
  //       return false;
  //     }
  //   }
  //   // 2. confirm that all keys are not blank (TODO: do we want to include this error check?)
  //   foreach ($ptr as $key => $value) {
  //     if (empty($ptr->$key)) {
  //       return false;
  //     }
  //   }

  //   return true;
  // }


  // public static function generateStatusObj($responseObj)
  // {
  //   switch ($responseObj->getCode()) {
  //     case 201:
  //       $message = "Success";
  //       break;
  //     case 401:
  //       $message = "Malformed JSON, missing key - " . $responseObj->getMessage();
  //       break;
  //     case 402:
  //       $message = "Malformed JSON, unset value for key - " . $responseObj->getMessage();
  //       break;
  //   }

  //   $statusJson = array(
  //     "code" => $responseObj->getCode(),
  //     "message" => $message
  //   );

  //   return $statusJson;
  // }



  // /**
  //   * The archetypal JSON structure for PTR input
  //   */
  // private static $ptrJsonStructureString = '{
  //   "request_id": 123456789,
  //   "ipt_timestamp": "2017-12-31 23:59:59",
  //   "timeout": 750,
  //   "queries": 4,
  //   "ipt_client_ip": "321.321.321.321",
  //   "ipt_client_postal_code": "Saanichton",
  //   "ipt_client_asn": 32123,
  //   "submitter": "CIRA IPT",
  //   "submitter_ip": "162.219.50.11",
  //   "ipt_server_city": "Calgary",
  //   "ipt_server_postal_code": "T1U2V3",
  //   "maxhops": 24,
  //   "os": "Darwin",
  //   "protocol": "ICMP",
  //   "hop_data": [
  //     {
  //       "pass_num": 1,
  //       "terminate": false,
  //       "hops": [
  //         {
  //           "hop_num": 1,
  //           "ip": "70.67.160.1",
  //           "rtt": "9.65"
  //         },
  //         {
  //           "hop_num": 2,
  //           "ip": "70.67.160.1",
  //           "rtt": "9.65"
  //         }
  //       ]
  //     },
  //     {
  //       "pass_num": 2,
  //       "terminate": true,
  //       "hops": []
  //     }
  //   ]
  // }';
}


