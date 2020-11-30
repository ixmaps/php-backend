<?php
/**
 *
 * Repository for IP2Location geolocation - handles interaction with the IP2Location data objects ()
 *
 * @author IXmaps.ca (Colin)
 * @since Nov 2020
 *
 */

require_once('../model/IP2LocationGeolocation.php');

class IP2LocationGeolocationRepository
{
  private $db;

  public function __construct($db)
  {
    $this->db = $db;
  }


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
  private function hydrate($ip, Object $dbData)
  {
    $geo = new IP2LocationGeolocation();
    $geo->setIp($ip);
    $geo->setLat($dbData->lat);
    $geo->setLong($dbData->long);
    $geo->setCity($dbData->city);
    $geo->setRegion($dbData->region);
    $geo->setCountry($dbData->country);
    $geo->setPostalCode($dbData->postal);
    $geo->setASNum($dbData->asnum);
    $geo->setASName($dbData->asname);

    return $geo;
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
    */
  private function determineAsn($ip) {
    $sql = "SELECT * FROM ip2location_asn WHERE ip_from <= ".$ip." and ip_to >= ".$ip;
    // TODO, fix this garbage old sql
    $result = pg_query($this->db, $sql) or die('determineAsn query failed: ' . pg_last_error());
    $row = pg_fetch_row($result);
    pg_free_result($result);

    return [$row[3],$row[4]];
  }
}