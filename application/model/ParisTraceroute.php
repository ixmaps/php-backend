<?php
class ParisTraceroute {
  private $request_id;
  private $ipt_timestamp;
  private $timeout;
  private $queries;     // this one is not clear enough - rename?
  private $ipt_client_ip;
  private $ipt_client_postal_code;
  private $ipt_client_asn;
  private $submitter;
  private $submitter_ip;
  private $ipt_server_city;
  private $ipt_server_postal_code;
  private $maxhops;
  private $os;
  private $protocol;
  private $hop_data;

  function __construct(array $ptrJson) {
    foreach($ptrJson as $key => $val) {
      if(property_exists(__CLASS__, $key)) {
        $this->$key = $val;
      }
    }
  }

  public function getRequestId() {
    return $this->request_id;
  }

  public function getIptTimestamp() {
    return $this->ipt_timestamp;
  }

  public function getTimeout() {
    return $this->timeout;
  }

  public function getQueries() {
    return $this->queries;
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

  public function getSubmitterIp() {
    return $this->submitter_ip;
  }

  public function getServerCity() {
    return $this->ipt_server_city;
  }

  public function getServerPostalCode() {
    return $this->ipt_server_postal_code;
  }

  public function getMaxHops() {
    return $this->maxhops;
  }

  public function getOs() {
    return $this->os;
  }

  public function getProtocol() {
    return $this->protocol;
  }

  public function getHopData() {
    return $this->hop_data;
  }

}


