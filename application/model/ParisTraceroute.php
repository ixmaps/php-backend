<?php
class ParisTraceroute {
  private $request_id;
  private $ipt_timestamp;
  private $ipt_client_ip;
  private $ipt_client_postal_code;
  private $ipt_client_asn;
  private $submitter;
  private $ipt_server_ip;
  private $ipt_server_city;
  private $ipt_server_postal_code;
  private $os;
  private $protocol;
  private $hops;

  function __construct(array $ptrJson) {
    foreach($ptrJson as $key => $val) {
      if(property_exists(__CLASS__, $key)) {
        $this->$key = $val;
      }
    }
    $this->saveData($ptrJson);
  }

  private function saveData($data){
    global $dbconn;
    $sql = "INSERT INTO ptr_contributions (ptr_json) VALUES ($1)";
    $params = array(json_encode($data));

    // TODO: add error handling that is consistent with PTR approach
    $result = pg_query_params($dbconn, $sql, $params);
  }

  public function getRequestId() {
    return $this->request_id;
  }

  public function getIptTimestamp() {
    return $this->ipt_timestamp;
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

  public function getServerIp() {
    return $this->ipt_server_ip;
  }
  public function getServerCity() {
    return $this->ipt_server_city;
  }
  public function getServerPostalCode() {
    return $this->ipt_server_postal_code;
  }

  public function getOs() {
    return $this->os;
  }

  public function getProtocol() {
    return $this->protocol;
  }

  public function getHopData() {
    return $this->hops;
  }

}


