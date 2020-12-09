<?php
/**
 *
 * Repository for IXmaps geolocation
 *
 * @author IXmaps.ca (Colin)
 * @since Nov 2020
 *
 */

require_once('../config.php');

class IPInfoGeolocationRepository
{
  private $db;

  public function __construct($geo)
  {
    global $dbconn;
    $this->geo = $geo;
    $this->db = $dbconn;
  }

  /**
    * @param $ip string
    *
    * @return most recently added IX Geolocation for the ip
    */
  public function getByIp(string $ip)
  {
    $sql = "SELECT * FROM ipinfo_ip_addr WHERE ip_addr = '".$ip."'";

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

    return $this->hydrate($geoData);
  }


  /**
    * @param $geoData object from ipinfo api (TODO, lock this down)
    *
    * @return hydrated IPInfoGeolocation object or false
    */
  public function create($geoData)
  {
    $sql = "INSERT INTO ipinfo_ip_addr (ip_addr, asnum, asname, lat, long, hostname, country, region, city, postal) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10);";
    $ipData = array($geoData->getIp(), $geoData->getASNum(), $geoData->getASName(), $geoData->getLat(), $geoData->getLong(), $geoData->getHostname(), $geoData->getCountry(), $geoData->getRegion(), $geoData->getCity(), $geoData->getPostalCode());

    try {
      $result = pg_query_params($this->db, $sql, $ipData);
      pg_free_result($result);

      return $this->getByIp($geoData->getIp());

    } catch (Exception $e) {
      // currently we're throwing on duplicate ip eg. Perhaps better to return false? Or build out a status code object?
      throw new Exception($e);
    }

    return false;
  }


  /**
    * @param $geoData object from ipinfo api
    *
    * @return hydrated IPInfoGeolocation object or false
    */
  public function update($geoData)
  {
    $sql = "UPDATE ipinfo_ip_addr SET
    asnum = $1,
    asname = $2,
    lat = $3,
    long = $4,
    hostname = $5,
    country = $6,
    region = $7,
    city = $8,
    postal = $9
    WHERE ip_addr = '".$geoData->getIp()."'";
    $ipData = array($geoData->getASNum(), $geoData->getASName(), $geoData->getLat(), $geoData->getLong(), $geoData->getHostname(), $geoData->getCountry(), $geoData->getRegion(), $geoData->getCity(), $geoData->getPostalCode());

    try {
      $result = pg_query_params($this->db, $sql, $ipData);
      pg_free_result($result);

      return $this->getByIp($geoData->getIp());

    } catch (Exception $e) {
      throw new Exception($e);
    }

    return false;
  }


  // not currently used...
  public function upsert($geoData)
  {
    $geo = $this->getByIp($geoData->getIp());

    // if exists, update
    if ($geo) {
      return $this->update($geoData);
    }

    return $this->create($geoData);
  }


  /**
    * @param string
    *
    * @return Bool on success/failure
    */
  public function deleteByIp(string $ip)
  {
    $sql = "DELETE FROM ipinfo_ip_addr WHERE ip_addr = '".$ip."'";
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
  private function hydrate(Object $geoData)
  {
    $this->geo->setIp($geoData->ip_addr);
    $this->geo->setLat($geoData->lat);
    $this->geo->setLong($geoData->long);
    $this->geo->setCity($geoData->city);
    $this->geo->setRegion($geoData->region);
    $this->geo->setCountry($geoData->country);
    $this->geo->setPostalCode($geoData->postal);
    $this->geo->setASNum($geoData->asnum);
    $this->geo->setASName($geoData->asname);
    $this->geo->setHostname($geoData->hostname);

    return $this->geo;
  }

}