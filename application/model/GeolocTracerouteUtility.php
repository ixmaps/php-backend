<?php
class GeolocTracerouteUtility {
  /** Encode the GLTR for into JSON to prep for returning
    *
    * @param GeolocTraceroute
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

} // end of class


