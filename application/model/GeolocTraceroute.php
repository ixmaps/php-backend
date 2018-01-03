<?php
class GeolocTraceroute {
  private $status;
  private $request_id;
  private $ixmaps_id;
  private $hop_count;
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

  public function setStatus($status) {
    return $this->status = $status;
  }
  public function getStatus() {
    return $this->status;
  }

  public function setRequestId($request_id) {
    return $this->request_id = $request_id;
  }
  public function getRequestId() {
    return $this->request_id;
  }

  public function setIXmapsId($ixmaps_id) {
    return $this->ixmaps_id = $ixmaps_id;
  }
  public function getIXmapsId() {
    return $this->ixmaps_id;
  }

  public function setHopCount($hop_count) {
    return $this->hop_count = $hop_count;
  }
  public function getHopCount() {
    return $this->hop_count;
  }

  public function setTerminate($terminate) {
    return $this->terminate = $terminate;
  }
  public function doesTerminate() {
    return $this->terminate;
  }

  public function setBoomerang($boomerang) {
    return $this->boomerang = $boomerang;
  }
  public function doesBoomerang() {
    return $this->boomerang;
  }

  public function setOverlayData($overlay_data) {
    return $this->overlay_data = $overlay_data;
  }
  public function getOverlayData() {
    return $this->overlay_data;
  }
}


