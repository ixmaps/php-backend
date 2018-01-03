<?php
class GeolocTraceroute {
  private $status;
  private $request_id;
  private $ixmaps_id;
  private $hop_count;     // this one is not clear enough
  private $terminate;
  private $boomerang;
  private $overlay_data;

  // function __construct() {

  // }

  // public function getStatusCode() {
  //   return $this->status;
  // }

  // public function getStatusCode() {
  //   return $this->status;
  // }

  public function getStatus() {
    return $this->status;
  }

  public function getRequestId() {
    return $this->request_id;
  }

  public function getIXmapsId() {
    return $this->ixmaps_id;
  }

  public function getHopCount() {
    return $this->hop_count;
  }

  public function doesTerminate() {
    return $this->terminate;
  }

  public function doesBoomerang() {
    return $this->boomerang;
  }

  public function getOverlayData() {
    return $this->overlay_data;
  }
}


