<?php
/**
 *
 * Repository for IXmaps geolocation
 *
 * @author IXmaps.ca (Colin)
 * @since Nov 2020
 *
 */
MAYBE?
require_once('../model/IXmapsGeolocation.php');
require_once('../services/MaxMindGeolocationService.php');         // temp, switch to ipinfo soon

class IXmapsGeolocationRepository
{
  private $db;

  public function __construct($db)
  {
    $this->db = $db;
  }

  public function getByIp($ip)
  {
    $sql = "SELECT ip_addr_info.ip_addr, ip_addr_info.hostname, ip_addr_info.asnum, as_users.name, as_users.short_name, ip_addr_info.lat, ip_addr_info.long, ip_addr_info.mm_country, ip_addr_info.mm_region, ip_addr_info.mm_city, ip_addr_info.mm_postal, ip_addr_info.gl_override FROM ip_addr_info LEFT JOIN as_users ON ip_addr_info.asnum = as_users.num WHERE ip_addr_info.ip_addr = $1";
    $params = array($ip);

    try {
      $result = pg_query_params($this->db, $sql, $params);
    } catch (Exception $e) {
      throw new Exception($e);
    }
    // leaving this as a fetch all for if / when we have multiple rows for an ip_addr
    $geoValues = pg_fetch_all($result);
    pg_free_result($result);

    // null check, ie IP does not exist
    if ($geoValues == false) {
      return false;
    }

    return $this->hydrate($geoValues[0]);
  }


  public function create($ip)
  {
    // temp - all of this will be rethought for the canonical ip_addr_info table. Perhaps want to pass createData? Or Model?
    $geoservice = new MaxMindGeolocationService();
    $geo = $geoservice->getByIp($ip);

    $sql = "INSERT INTO ip_addr_info (ip_addr, asnum, mm_lat, mm_long, hostname, mm_country, mm_region, mm_city, mm_postal, p_status, lat, long, gl_override) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13);";
    $ipData = array($ip, $geo->getASNum(), $geo->getLat(), $geo->getLong(), $geo->getHostname(), $geo->getCountryCode(), $geo->getRegion(), $geo->getCity(), $geo->getPostalCode(), "N", $geo->getLat(), $geo->getLong(), NULL);

    try {
      $result = pg_query_params($this->db, $sql, $ipData);
      pg_free_result($result);
      return true;
    } catch (Exception $e) {
      throw new Exception($e);
    }
  }


  public function deleteByIp($ip)
  {
    $sql = "DELETE FROM ip_addr_info WHERE ip_addr = '".$ip."'";
    try {
      $result = pg_query($this->db, $sql);
      $rowsDeleted = pg_affected_rows($result);
    } catch (Exception $e) {
      throw new Exception($e);
    }
    pg_free_result($result);

    if ($rowsDeleted == 0) {
      return false;
    }

    return true;
  }

  /**
    * turns raw db data into a Geolocation object
    */
  private function hydrate(Array $dbData)
  {
    $geo = new IXmapsGeolocation();
    $geo->setIp($dbData['ip_addr']);
    $geo->setLat($dbData['lat']);
    $geo->setLong($dbData['long']);
    $geo->setCity($dbData['mm_city']);
    $geo->setRegion($dbData['mm_region']);
    $geo->setCountry($dbData['mm_country']);
    $geo->setPostalCode($dbData['mm_postal']);
    $geo->setASNum($dbData['asnum']);
    $geo->setASName($dbData['asname']);
    $geo->setHostname($dbData['hostname']);

    return $geo;
  }
}