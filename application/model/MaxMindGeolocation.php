<?php
/**
 *
 * Thin MaxMind data representation of ip address to location relationship
 *
 * Updated Nov 2020
 * @author IXmaps.ca (Colin)
 *
 */

class MaxMindGeolocation
{
  private $ip;
  private $lat;
  private $long;
  private $city;
  private $region;
  private $regionCode;
  private $country;
  private $countryCode;
  private $postalCode;
  private $asnum;
  private $asname;
  private $hostname;

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
  public function setRegionCode($regionCode) {
    $this->regionCode = $regionCode;
  }
  public function setCountry($country) {
    $this->country = $country;
  }
  public function setCountryCode($countryCode) {
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
    return $this->region;
  }
  public function getRegionCode() {
    return $this->regionCode;
  }
  public function getCountry() {
    return $this->country;
  }
  public function getCountryCode() {
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
}
?>