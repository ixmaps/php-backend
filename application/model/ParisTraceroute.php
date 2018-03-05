<?php
/**
 *
 * Paris Traceroute class. Bit of a misnomer, as this object is not a full Paris Traceroute.
 * Rather, this class handles the CIRA submitted JSON object that has been refined
 * from a Paris Traceroute
 *
 * @author IXmaps.ca (Colin, Antonio)
 * @since 2018 Jan 1
 *
 */
class ParisTraceroute {
  private $request_id;
  private $ipt_timestamp;
  private $ipt_client_ip;
  private $ipt_client_postal_code;
  private $ipt_client_asn;
  private $submitter;
  private $ipt_server_ip;
  private $ipt_server_city;
  private $ipt_server_postal_code;
  private $os;
  private $protocol;
  private $hops;

  function __construct(array $postArr) {
    foreach($postArr as $key => $val) {
      if(property_exists(__CLASS__, $key)) {
        $this->$key = $val;
      }
    }
    $this->saveData($postArr);
  }

  /**
    * Check if submitted PTR json is valid
    *
    * @param PTR post
    *
    * @return Boolean
    *
    */
  public static function isValid($postJson)
  {
    $rc = self::determineStatus($postJson);

    if ($rc->getCode() == 201) {
      return true;
    } else {
      return false;
    }
  }

  /**
    * Determine correct ResponseCode for the return
    *
    * @param PTR post
    *
    * @return ResponseCode object
    *
    */
  public static function determineStatus($postJson) {
    $ptrJsonStructure = json_decode(self::PTR_JSON_STRUCTURE_STRING, TRUE);

    // check if PTR is correctly formatted JSON
    // passing in the postJson, then decoding it to see if it is valid JSON
    $postArr = json_decode($postJson, TRUE);
    switch (json_last_error()) {
      case JSON_ERROR_DEPTH:
        return new ResponseCode(400, 'Maximum stack depth exceeded');
      break;
      case JSON_ERROR_STATE_MISMATCH:
        return new ResponseCode(400, 'Underflow or the modes mismatch');
      break;
      case JSON_ERROR_CTRL_CHAR:
        return new ResponseCode(400, 'Unexpected control character found');
      break;
      case JSON_ERROR_SYNTAX:
        return new ResponseCode(400, 'Syntax error');
      break;
      case JSON_ERROR_UTF8:
        return new ResponseCode(400, 'Malformed UTF-8 characters, possibly incorrectly encoded');
      break;
      default:
        //return new ResponseCode(400, 'Unknown error');
      break;
    }

    // check if PTR has all keys
    foreach ($ptrJsonStructure as $key => $value) {
      if (is_null($postArr[$key])) {
        return new ResponseCode(401, $key);
      }
    }

    // check if PTR has no null values
    // TODO: [AG]: This validation needs to be refined. Need to agree on which fields can be empty. Changing validation logic and adding a few exceptions
    // CM: agreed - maybe it's best to remove the validation empty field check all together?
    $permittedEmptyFields = array(
      'ipt_server_postal_code',
      'ipt_client_postal_code'
    );
    foreach ($postArr as $key => $value) {
      if (empty($postArr[$key]) && !in_array($key, $permittedEmptyFields)) {
        return new ResponseCode(402, $key);
      }
    }

    return new ResponseCode(201);
  }

  // CM: I suggest that this functionality does not belong in this class
  private function saveData($data){
    global $dbconn;
    $sql = "INSERT INTO ptr_contributions (ptr_json, ip_addr) VALUES ($1, $2)";
    $params = array(json_encode($data), $_SERVER['REMOTE_ADDR']);

    // TODO: add error handling that is consistent with PTR approach
    $result = pg_query_params($dbconn, $sql, $params);
  }

  public function getRequestId() {
    return $this->request_id;
  }
  public function getIptTimestamp() {
    return $this->ipt_timestamp;
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
  public function getServerIp() {
    return $this->ipt_server_ip;
  }
  public function getServerCity() {
    return $this->ipt_server_city;
  }
  public function getServerPostalCode() {
    return $this->ipt_server_postal_code;
  }
  public function getOs() {
    return $this->os;
  }
  public function getProtocol() {
    return $this->protocol;
  }
  public function getHops() {
    return $this->hops;
  }

  /**
    * The archetypal JSON structure for PTR input
    */
  const PTR_JSON_STRUCTURE_STRING = '{
    "request_id": 12345,
    "ipt_timestamp": "2017-06-09 01:23:24+05",
    "ipt_client_ip": "69.157.148.199",
    "ipt_client_postal_code": "J1L2B1",
    "ipt_client_asn": 32123,
    "submitter": "CIRA IPT",
    "ipt_server_ip": "162.219.49.25",
    "ipt_server_city": "Montreal",
    "ipt_server_postal_code": "T1U2V3",
    "os": "Darwin",
    "protocol": "ICMP",
    "hops": [
      {
        "num": 1,
        "ip": "162.219.49.1",
        "rtts": ["0.251", "0.281", "0.331"]
      },
      {
        "num": 2,
        "ip": "184.105.64.89",
        "rtts": ["14.703", "17.144", "19.620"]
      },
      {
        "num": 3,
        "ip": "198.32.118.113",
        "rtts": ["12.540", "12.792", "13.013"]
      },
      {
        "num": 4,
        "ip": "64.230.79.90",
        "rtts": ["24.443", "26.865", "27.917"]
      },
      {
        "num": 5,
        "ip": "64.230.79.151",
        "rtts": ["24.975", "27.147", "28.284"]
      },
      {
        "num": 6,
        "ip": "64.230.91.57",
        "rtts": ["21.878", "21.970", "22.113"]
      },
      {
        "num": 7,
        "ip": "10.178.206.152",
        "rtts": ["22.132", "22.248", "22.380"]
      },
      {
        "num": 8,
        "ip": "69.157.148.1",
        "rtts": ["26.955", "28.098", "33.291"]
      },
      {
        "num": 9,
        "ip": "69.157.148.199",
        "rtts": ["37.846", "38.430", "38.872"]
      }
    ]
  }';
}
