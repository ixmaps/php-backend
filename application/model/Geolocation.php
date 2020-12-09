<?php
/**
 *
 * Generic geolocation model representation
 *
 *
 * @author IXmaps.ca (Colin)
 * @since Oct 2020
 *
 */

require_once('../model/Utility.php');

class Geolocation {
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
  private $createdAt;
  private $updatedAt;
  private $geoSource;
  private $asnSource;
  private $staleDate;
  private $staleStatus;


  function __construct() {
    global $IPStaleDate;
    $this->staleDate = $IPStaleDate;
    $this->staleStatus = false;

    $this->setIp(NULL);
    $this->setLat(NULL);
    $this->setLong(NULL);
    $this->setCity(NULL);
    $this->setRegion(NULL);
    $this->setCountry(NULL);
    $this->setPostalCode(NULL);
    $this->setASNum(NULL);
    $this->setASName(NULL);
    $this->setHostname(NULL);
    $this->setCreatedAt(NULL);
    $this->setUpdatedAt(NULL);
  }

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
  public function setCreatedAt($createdAt) {
    $this->createdAt = $createdAt;
    // NB: we're using the createdAt as the metric to determine if an ip is stale
    // We might want to consider interpretation of updatedAt
    if ($createdAt && date_diff(Utility::getNow(), $createdAt)->format('%a') > $this->staleDate) {
      $this->staleStatus = true;
    }
  }
  public function setUpdatedAt($updatedAt) {
    $this->updatedAt = $updatedAt;
  }
  public function setGeosource($geoSource) {
    $this->geoSource = $geoSource;
  }
  public function setASNsource($asnSource) {
    $this->asnSource = $asnSource;
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
  public function getCreatedAt() {
    return $this->createdAt;
  }
  public function getUpdatedAt() {
    return $this->updatedAt;
  }
  public function getHostname() {
    return $this->hostname;
  }
  public function getAsnSource() {
    return $this->asnSource;
  }
  public function getGeoSource() {
    return $this->geoSource;
  }
  public function getStaleStatus() {
    return $this->staleStatus;
  }

} // end class
