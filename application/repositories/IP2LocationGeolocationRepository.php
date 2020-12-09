<?php
/**
  *
  * Repository for IP2Location geolocation - handles interaction with the IP2Location data objects
  *
  * @author IXmaps.ca (Colin)
  * @since Nov 2020
  *
  */

require_once('../config.php');
require_once('../model/Geolocation.php');

class IP2LocationGeolocationRepository
{
  private $db;

  public function __construct()
  {
    global $dbconn;
    $this->geo = new Geolocation();
    $this->db = $dbconn;
  }

  /**
    * Convert ip addresses to numeric
    *
    * @param inet $ip
    *
    * @return long
    *
    */
  public function getByIp($ip)
  {
    $ipLong = $this->ip2long($ip);
    $sql = "SELECT * FROM ip2location_ip_addr WHERE ip_from <= ".$ipLong." and ip_to >= ".$ipLong;
    try {
      $result = pg_query($this->db, $sql);
    } catch (Exception $e) {
      throw new Exception($e);
    }
    $geoData = pg_fetch_object($result);
    pg_free_result($result);

    // null check, ie IP does not exist
    if ($geoData == false) {
      return false;
    }

    if (empty($geoData->asnum)) {
      [$geoData->asnum, $geoData->asname] = $this->determineAsn($ipLong);
    }

    // some cleanup, since we prefer blank values to '-'
    if ($geoData->country == "-" || $geoData->country == "- ") {
      $geoData->country = "";
    }
    if ($geoData->city == "-") {
      $geoData->city = "";
    }
    if ($geoData->postal == "-") {
      $geoData->postal = "";
    }
    if ($geoData->asnum == "-") {
      $geoData->asnum = NULL;
    }
    if ($geoData->asname == "-") {
      $geoData->asname = "";
    }

    return $this->hydrate($ip, $geoData);
  }


  /**
    * turns raw db data into a Geolocation object
    */
  private function hydrate(string $ip, Object $dbData)
  {
    $this->geo->setIp($ip);
    $this->geo->setLat($dbData->lat);
    $this->geo->setLong($dbData->long);
    $this->geo->setCity($dbData->city);
    $this->geo->setRegion($dbData->region);
    $this->geo->setCountry($dbData->country);
    $this->geo->setCountryCode($dbData->country_name);
    $this->geo->setPostalCode($dbData->postal);
    $this->geo->setASNum($dbData->asnum);
    $this->geo->setASName($dbData->asname);
    $this->geo->setGeoSource("IP2Location");
    $this->geo->setAsnSource("IP2Location");

    return $this->geo;
  }

  /**
    * Convert ip addresses to numeric
    *
    * @param inet $ip
    *
    * @return long
    *
    */
  private function ip2long($ip) {
    if (is_numeric($ip)) {
      return sprintf( "%u", floatval($ip) );
    } else {
      return sprintf( "%u", floatval(ip2long($ip) ));
    }
  }

  /**
    * Since not all of the ip2_location_ip_addrs have an asn, check if it's available in the ip2_location_asn table
    *
    * @param inet $ip
    *
    * @return Array size 2 with asnum string and asname string
    *
    */
  public function determineAsn($ip) {
    $ipLong = $this->ip2long($ip);
    $sql = "SELECT * FROM ip2location_asn WHERE ip_from <= '".$ipLong."' and ip_to >= '".$ipLong."'";
    try {
      $result = pg_query($this->db, $sql);
    } catch (Exception $e) {
      throw new Exception($e);
    }

    $asnData = pg_fetch_object($result);
    pg_free_result($result);

    return [$asnData->asn, $asnData->as];

  }
}