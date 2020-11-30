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

require_once('../../vendor/autoload.php');
use ipinfo\ipinfo\IPinfo;

class IPInfoAPIService {
  public $ip_addr;
  public $lat;
  public $long;
  public $city;
  public $region;
  public $country;
  public $postal;
  public $asnum;
  public $asname;
  public $hostname;

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
    $this->ip_addr = $results->ip;
    $this->lat = $results->latitude;
    $this->long = $results->longitude;
    $this->city = $results->city;
    $this->region = $results->region;
    $this->country = $results->country;
    $this->postal = $results->postal;
    [$this->asnum, $this->asname] = $this->determineASNValues($results);
    $this->hostname = $results->hostname;
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



