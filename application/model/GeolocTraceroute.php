<?php
// todo-declare(strict_types = 1); TODO set all params with var types

class GeolocTraceroute {
  private $status;
  private $request_id;
  private $ixmaps_id;
  private $hop_count;
  private $terminate;
  private $boomerang;
  private $overlay_data;


  public function setStatus() {
    // default response
    $this->status = new ResponseCode(201);

    // check that GLTR has no null values (cannot check status yet, since this is setting it)
    foreach ($this as $key => $value) {
      if (is_null($value) && $key != 'status') {
        $this->status = new ResponseCode(502, $key);
      }
    }
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

  public function setTerminate($terminate) {
    $this->terminate = $terminate;
  }
  public function getTerminate() {
    return $this->terminate;
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


