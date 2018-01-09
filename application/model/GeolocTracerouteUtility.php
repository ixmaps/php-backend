<?php
class GeolocTracerouteUtility {
  /** Encode the GLTR for into JSON to prep for returning
    *
    * @param GeolocTraceroute
    *
    * @return encoded array
    *
    */
  public function encodeForReturn(GeolocTraceroute $tr) {
    //if ($tr->getStatus()) {
      $statusObj = $tr->getStatus();
      $statusArr = array(
        "code" => $statusObj->getCode(),
        "message" => $statusObj->getMessage()
      );
    //}

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

} // end of class


