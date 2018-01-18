<?php
class GeolocTraceroute {
  private $status;
  private $request_id;
  private $ixmaps_id;
  private $hop_count;
  private $completed;
  private $boomerang;
  private $overlay_data;

  function __construct() {
    $this->status = new ResponseCode(100);
  }

  // this must be in this class, needs access to the privates - otherwise we could move this and the PTR ones to the same class. Or maybe move more into here?
  public function determineStatus() {
    // check that GLTR has no null values (cannot check status yet, since this is setting it)
    foreach ($this as $key => $value) {
      if (is_null($value)) {
        return new ResponseCode(502, $key);
      }
    }
    // default response
    return new ResponseCode(201);
  }
  public function setStatus(ResponseCode $status) {
    $this->status = $status;
  }
  public function getStatus() {
    return $this->status;
  }

  public function setRequestId($request_id) {
    $this->request_id = $request_id;
  }
  public function getRequestId() {
    return $this->request_id;
  }

  public function setIXmapsId($ixmaps_id) {
    $this->ixmaps_id = $ixmaps_id;
  }
  public function getIXmapsId() {
    return $this->ixmaps_id;
  }

  public function setHopCount($hop_count) {
    $this->hop_count = $hop_count;
  }
  public function getHopCount() {
    return $this->hop_count;
  }

  public function setCompleted($completed) {
    $this->completed = $completed;
  }
  public function getCompleted() {
    return $this->completed;
  }

  public function setBoomerang($boomerang) {
    $this->boomerang = $boomerang;
  }
  public function getBoomerang() {
    return $this->boomerang;
  }

  public function setOverlayData($overlay_data) {
    $this->overlay_data = $overlay_data;
  }
  public function getOverlayData() {
    return $this->overlay_data;
  }

} // end of class


