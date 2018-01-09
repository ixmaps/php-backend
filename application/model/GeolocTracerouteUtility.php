<?php
class GeolocTracerouteUtility {

  public function checkStatus(GeolocTraceroute $tr) {
    $geolocJsonStructure = json_decode(self::geolocJsonStructureString, TRUE);

    // TODO: commented out for now - something feels wrong about this validation structure
    // need to rethink this, it's too superficial right now, and not abstracted enough
    // I really think the validation should be in the model, sort of in the backbone.js
    // style with a trigger 'onupdate' or similar

    // TODO: neither of these will work, since you cannot loop over the (private) attributes of a class
    // check if GLTR has all keys
    // foreach ($geolocJsonStructure as $key => $value) {
    //   if (is_null($postArr[$key])) {
    //     return new ResponseCode(501, $key);
    //   }
    // }
    // check if GLTR has no null values
    // foreach ($tr as $key => $value) {
    //   if (empty($tr[$key])) {
    //     return new ResponseCode(502, $key);
    //   }
    // }
    return new ResponseCode(201);
  }

  public function encodeForReturn(GeolocTraceroute $tr) {
    $statusObj = $tr->getStatus();
    $statusArr = array(
      "code" => $statusObj->getCode(),
      "message" => $statusObj->getMessage()
    );
    $jsonArr = array(
      "status" => $statusArr,
      "request_id" => $tr->getRequestId(),
      "ixmaps_id" => $tr->getIxmapsId(),
      "hop_count" => $tr->getHopCount(),
      "terminate" => $tr->getTerminate(),
      "boomerang" => $tr->getBoomerang(),
      "overlay_data" => $tr->getOverlayData()
    );

    return json_encode($jsonArr);
  }


  /**
    * The archetypal JSON structure for geoloc traceroute output
    */
  const geolocJsonStructureString = '{
    "status": {
      "code": 201,
      "message": "Success"
    },
    "request_id": 12345,
    "ixmaps_id": 0,
    "hop_count": 12,
    "terminate": false,
    "boomerang": false,
    "overlay_data": [
      {
        "hop": 1,
        "lat": null,
        "long": null,
        "attributes": {
          "asnum": null,
          "asname": null,
          "country": null,
          "nsa": "TODO",
          "georeliability": "TBD"
        }
      },
      {
        "hop": 2,
        "lat": 48.6496,
        "long": -123.4026,
        "attributes": {
          "asnum": "6327",
          "asname": "Shaw Communications Inc.",
          "country": "CA",
          "nsa": "TODO",
          "georeliability": "TBD"
        }
      }
    ]
  }';
} // end of class


