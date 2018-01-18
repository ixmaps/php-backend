<?php
class GeolocTracerouteUtility {
  /** Encode the GLTR for into JSON to prep for returning
    *
    * @param GeolocTraceroute $tr
    *
    * @return encoded array
    *
    */
  public function encodeAndReturn(GeolocTraceroute $tr) {
    $status = array(
      "code" => $tr->getStatus()->getCode(),
      "message" => $tr->getStatus()->getMessage()
    );

    if (self::isValid($tr)) {
      $jsonArr = array(
        "status" => $status,
        "request_id" => $tr->getRequestId(),
        "ixmaps_id" => $tr->getIxmapsId(),
        "hop_count" => $tr->getHopCount(),
        "completed" => $tr->getCompleted(),
        "boomerang" => $tr->getBoomerang(),
        "overlay_data" => $tr->getOverlayData()
      );
      header('Content-type: application/json');
      echo json_encode($jsonArr);
    } else {
      // send back the response to requester
      header('Content-type: application/json');
      echo json_encode($status);
      die;
    }
  }

  /** Check if GLTR is valid
    *
    * @param GLTR class object
    *
    * @return Boolean
    *
    */
  public function isValid(GeolocTraceroute $tr) {
    if ($tr->getStatus()->getCode() == 201) {
      return true;
    } else {
      return false;
    }
  }


  /**
    * The archetypal JSON structure for GeoLoc output
    */
  const geolocJsonStructureString = '{
    "status": {
      "code": 201,
      "message": "Success"
    },
    "request_id": "12345",
    "ixmaps_id": 0,
    "hop_count": 9,
    "completed": true,
    "boomerang": true,
    "overlay_data": [
      {
        "hop": "1",
        "ip": "162.219.49.1",
        "lat": 45.399,
        "long": -75.6871,
        "attributes": {
          "asnum": null,
          "asname": null,
          "country": "CA",
          "city": "Ottawa",
          "nsa": "TODO",
          "georeliability": "maxmind"
        }
      },
      {
        "hop": "2",
        "ip": "184.105.64.89",
        "lat": "40.72",
        "long": "-74",
        "attributes": {
          "asnum": "6939",
          "asname": "HURRICANE - Hurricane Electric, Inc.",
          "country": "US",
          "city": "New York",
          "nsa": "TODO",
          "georeliability": "ixmaps"
        }
      },
      {
        "hop": "3",
        "ip": "198.32.118.113",
        "lat": "40.740697",
        "long": "-74.002089",
        "attributes": {
          "asnum": "9498",
          "asname": "BHARTI Airtel Ltd.",
          "country": "US",
          "city": "New York",
          "nsa": "TODO",
          "georeliability": "ixmaps"
        }
      },
      {
        "hop": "4",
        "ip": "64.230.79.90",
        "lat": 43.6319,
        "long": -79.3716,
        "attributes": {
          "asnum": "577",
          "asname": "Bell",
          "country": "CA",
          "city": null,
          "nsa": "TODO",
          "georeliability": "maxmind"
        }
      },
      {
        "hop": "5",
        "ip": "64.230.79.151",
        "lat": "45.51",
        "long": "-73.55",
        "attributes": {
          "asnum": "577",
          "asname": "Bell",
          "country": "CA",
          "city": "Montreal",
          "nsa": "TODO",
          "georeliability": "ixmaps"
        }
      },
      {
        "hop": "6",
        "ip": "64.230.91.57",
        "lat": 43.6319,
        "long": -79.3716,
        "attributes": {
          "asnum": null,
          "asname": null,
          "country": "CA",
          "city": null,
          "nsa": "TODO",
          "georeliability": "maxmind"
        }
      },
      {
        "hop": "7",
        "ip": "10.178.206.152",
        "lat": null,
        "long": null,
        "attributes": {
          "asnum": null,
          "asname": null,
          "country": null,
          "city": null,
          "nsa": "TODO",
          "georeliability": null
        }
      },
      {
        "hop": "8",
        "ip": "69.157.148.1",
        "lat": 45.4024,
        "long": -71.8479,
        "attributes": {
          "asnum": "577",
          "asname": "Bell Canada",
          "country": "CA",
          "city": "Sherbrooke",
          "nsa": "TODO",
          "georeliability": "maxmind"
        }
      },
      {
        "hop": "9",
        "ip": "69.157.148.199",
        "lat": 45.4024,
        "long": -71.8479,
        "attributes": {
          "asnum": "577",
          "asname": "Bell Canada",
          "country": "CA",
          "city": "Sherbrooke",
          "nsa": "TODO",
          "georeliability": "maxmind"
        }
      }
    ]
  }';

} // end of class


