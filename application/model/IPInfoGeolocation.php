<?php
/**
 *
 * Thin representation of IpInfo ip address to location relationship
 *
 * @since Nov 2020
 * @author IXmaps.ca (Colin)
 *
 */

class IPInfoGeolocation
{
  private $lat;
  private $long;
  private $city;
  private $region;
  private $country;
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
  public function setCountry($country) {
    $this->country = $country;
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
  public function getPostalCode() {
    return $this->postal;
  }
  public function getCountry() {
    return $this->country;
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