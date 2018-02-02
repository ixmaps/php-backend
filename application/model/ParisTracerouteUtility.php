<?php
class ParisTracerouteUtility
{
  /**
    * Check if PTR is valid
    *
    * @param PTR post
    *
    * @return Boolean
    *
    */
  public static function isValid($postArr)
  {
    $rc = self::determineStatus($postArr);

    if ($rc->getCode() == 201) {
      return true;
    } else {
      return false;
    }
  }

  /**
    * Determine correct ResponseCode for the return
    * TODO: decide if this is specific to PTR (and not GeolocTR), if so get rid of the 201, refactor a bit
    *
    * @param PTR post
    *
    * @return ResponseCode object
    *
    */
  public static function determineStatus($postArr) {
    $ptrJsonStructure = json_decode(self::ptrJsonStructureString, TRUE);

    // check if PTR has all keys
    foreach ($ptrJsonStructure as $key => $value) {
      if (is_null($postArr[$key])) {
        return new ResponseCode(401, $key);
      }
    }
    // check if PTR has no null values
    // TODO: [AG]: This validation needs to be refined. Need to agree on which fields can be empty. Changing validation logic and adding a few exceptions
    $allowed_empty_fields = array(
      'ipt_server_postal_code', 
      'ipt_client_postal_code'
    );
    foreach ($postArr as $key => $value) {

      if (empty($postArr[$key]) && !in_array($key, $allowed_empty_fields)) {
        return new ResponseCode(402, $key);
      }
    }

    return new ResponseCode(201);
  }

  /**
    * The archetypal JSON structure for PTR input
    */
  const ptrJsonStructureString = '{
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

}  // end of class
