<?php
/**
 *
 * Service to handle calls out to IPInfo API
 *
 * @author IXmaps.ca (Colin)
 * @since Nov 2020
 *
 *
 */

require('../config.php');
require_once('../../vendor/autoload.php');
use ipinfo\ipinfo\IPinfo;

class IPInfoAPIService {
  private $ip;
  private $lat;
  private $long;
  private $city;
  private $region;
  private $country;
  private $postalCode;
  private $asnum;
  private $asname;
  private $hostname;

  /**
    * We want to restructure the object returned such that it mirrors our DB
    */
  public function __construct($ip) {
    global $IIAccessToken;

    $ipinfoClient = new IPinfo($IIAccessToken);

    try {
      $results = $ipinfoClient->getDetails($ip);
    } catch (Exception $e) {
      throw new Exception($e);
    }
    $this->ip = $results->ip;
    $this->lat = $results->latitude;
    $this->long = $results->longitude;
    $this->city = $results->city;
    $this->region = $results->region;
    $this->country = $results->country;
    $this->postalCode = $results->postal;
    [$this->asnum, $this->asname] = $this->determineASNValues($results);
    $this->hostname = $results->hostname;
  }

  public function getIp() {
    return $this->ip;
  }
  public function getLat() {
    return $this->lat;
  }
  public function getLong() {
    return $this->long;
  }
  public function getCity() {
    return $this->city;
  }
  public function getRegion() {
    return $this->city;
  }
  public function getCountry() {
    return $this->country;
  }
  public function getPostalCode() {
    return $this->postalCode;
  }
  public function getASNum() {
    return $this->asnum;
  }
  public function getASName() {
    return $this->asname;
  }
  public function getHostname() {
    return $this->hostname;
  }

  /**
    * IpInfo provides AS values as eg "AS3257 GTT Communications Inc.", so we split them here
    */
  private function determineASNValues($results) {
    if (isset($results->org)) {
      $asnDetails = explode(" ", $results->org, 2);
      $asnum = substr($asnDetails[0], 2);
      $asname = $asnDetails[1];
      return [$asnum, $asname];
    }

    // null asnum and empty string asname
    return [NULL, ''];
  }

} // end class



