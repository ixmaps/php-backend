<?php
/**
 *
 * Class to handle interaction with the IpInfo API
 *
 * Created Nov 2020
 * @author IXmaps.ca (Colin)
 *
 */
require_once('../config.php');
require_once('../../vendor/autoload.php');
use ipinfo\ipinfo\IPinfo;


class IXmapsIpInfo
{
  private $lat;
  private $long;
  private $city;
  private $region;
  private $countryCode;
  private $postal;
  private $hostname;
  private $asnum;
  private $asname;

  public function __construct($ip) {
    global $IIAccessToken;

    $client = new IPinfo($IIAccessToken);

    try {
      $results = $client->getDetails($ip);
      $this->lat = $results->latitude;
      $this->long = $results->longitude;
      $this->city = $results->city;
      $this->region = $results->region;
      $this->countryCode = $results->country;
      $this->postal = $results->postal;
      $this->hostname = $results->hostname;
      $this->asnum = $this->determineASNValues($results)[0];
      $this->asname = $this->determineASNValues($results)[1];
    } catch(Exception $e) {
      // should we null all the values? Probably
      echo "IP not found";
      // should we log this?
    }

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
    return $this->region;
  }

  public function getPostalCode() {
    return $this->postal;
  }

  public function getCountry() {
    return $this->countryCode;
  }

  public function getCountryCode() {
    return $this->countryCode;
  }

  public function getHostname() {
    return $this->hostname;
  }

  public function getASNum() {
    return $this->asnum;
  }

  public function getASName() {
    return $this->asname;
  }

  private function determineASNValues($results) {
    if (isset($results->org)) {
      $asnDetails = explode(" ", $results->org, 2);
      $asnum = substr($asnDetails[0], 2);
      $asname = $asnDetails[1];
      return [$asnum, $asname];
    }

    return [NULL, ''];
  }
}