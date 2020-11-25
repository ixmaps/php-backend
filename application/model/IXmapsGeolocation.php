<?php
/**
 *
 *
 * @author IXmaps.ca (Colin)
 * @since Nov 2020
 *
 */


class IXmapsGeolocation {
  private $ip;
  private $lat;
  private $long;
  private $city;
  private $region;
  private $countryCode;
  private $postalCode;
  private $asnum;
  private $asname;
  private $hostname;

  // temp
  private $mm_lat;
  private $mm_long;

  // ip_addr | asnum | mm_lat | mm_long | hostname | mm_country | mm_region | mm_city | mm_postal | p_status | lat | long | gl_override | flagged | created_at | updated_at


  /**
   *
   * Creates a Geolocation object for a given IP.
   * Current Geolocation sources: ixmaps (ix), maxmind (mm), ipinfo (ii), ip2location (i2)
   *
   * @param inet $ip Ip address in inet/string format
   */
  function __construct() { }

  public function setIp($ip) {
    $this->ip = $ip;
  }
  public function setLat($lat) {
    $this->lat = $lat;
  }
  public function setLong($long) {
    $this->long = $long;
  }
  public function setCity($city) {
    $this->city = $city;
  }
  public function setRegion($region) {
    $this->region = $region;
  }
  public function setCountry($countryCode) {
    $this->countryCode = $countryCode;
  }
  public function setPostalCode($postalCode) {
    $this->postalCode = $postalCode;
  }
  public function setASNum($asnum) {
    $this->asnum = $asnum;
  }
  public function setASName($asname) {
    $this->asname = $asname;
  }
  public function setHostname($hostname) {
    $this->hostname = $hostname;
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
    return $this->countryCode;
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

} // end class
