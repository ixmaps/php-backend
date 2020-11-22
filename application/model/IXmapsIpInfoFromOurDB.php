<?php
/**
 *
 * Class to handle ipinfo ip address values
 *
 * Updated Aug 2020
 * @author IXmaps.ca (Colin)
 *
 */
require_once('../config.php');

class IXmapsIpInfoFromOurDB
{
  private $lat;
  private $long;
  private $city;
  private $region;
  private $countryCode;
  private $postal;
  private $hostname;

  /**
   *
   */
  function __construct($ip) {
    global $dbconn;

    if (filter_var($ip, FILTER_VALIDATE_IP) == false || $ip == "") {
      throw new Exception("Not a valid IP address");
    }

    $sql = "SELECT * FROM ipinfo_ip_addr WHERE ip_addr ='".$ip."'";
    $result = pg_query($dbconn, $sql) or die('compareASN query failed: ' . pg_last_error());
    $ip = pg_fetch_all($result);
    pg_free_result($result);

    $ip = $ip[0];

    try {
      $this->lat = $ip["lat"];
      $this->long = $ip["long"];
      $this->city = $ip["city"];
      $this->region = $ip["region"];
      $this->countryCode = $ip["country"];
      $this->postal = $ip["postal"];
      $this->hostname = $ip["hostname"];
    } catch(Exception $e) {
      $this->lat = NULL;
      $this->long = NULL;
      $this->city = NULL;
      $this->region = NULL;
      $this->countryCode = NULL;
      $this->postal = NULL;
      $this->hostname = NULL;
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

  public function getRegionCode() {
    return $this->regionCode;
  }

  public function getPostalCode() {
    return $this->postal;
  }

  public function getCountry() {
    return $this->country;
  }

  public function getCountryCode() {
    return $this->countryCode;
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
}
?>