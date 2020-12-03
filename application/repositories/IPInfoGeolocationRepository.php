<?php
/**
 *
 * Repository for IXmaps geolocation
 *
 * @author IXmaps.ca (Colin)
 * @since Nov 2020
 *
 */

require_once('../model/IPInfoGeolocation.php');
require_once('../services/IPInfoAPIService.php');


class IPInfoGeolocationRepository
{
  private $db;

  public function __construct($db)
  {
    $this->db = $db;
  }

  public function getByIp($ip)
  {
    // TODO: rename table

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


  public function create($geoData)
  {
    $sql = "INSERT INTO ipinfo_ip_addr (ip_addr, asnum, asname, lat, long, hostname, country, region, city, postal) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10);";
    $ipData = array($geoData->getIp(), $geoData->getASNum(), $geoData->getASName(), $geoData->getLat(), $geoData->getLong(), $geoData->getHostname(), $geoData->getCountry(), $geoData->getRegion(), $geoData->getCity(), $geoData->getPostalCode());

    try {
      $result = pg_query_params($this->db, $sql, $ipData);
      pg_free_result($result);
      return true;
    } catch (Exception $e) {
      // currently we're throwing on duplicate ip. Perhaps better to return false? Or build out a status code object?
      throw new Exception($e);
    }

    return false;
  }


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
      return true;
    } catch (Exception $e) {
      throw new Exception($e);
    }

    return false;
  }


  // not currently used any more...
  public function upsert($geoData)
  {
    $geo = $this->getByIp($geoData->getIp());

    // if exists, update
    if ($geo) {
      return $this->update($geoData);
    }

    return $this->create($geoData);
  }


  public function deleteByIp($ip)
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
    $geo = new IPInfoGeolocation();
    $geo->setIp($geoData->ip_addr);
    $geo->setLat($geoData->lat);
    $geo->setLong($geoData->long);
    $geo->setCity($geoData->city);
    $geo->setRegion($geoData->region);
    $geo->setCountry($geoData->country);
    $geo->setPostalCode($geoData->postal);
    $geo->setASNum($geoData->asnum);
    $geo->setASName($geoData->asname);
    $geo->setHostname($geoData->hostname);

    return $geo;
  }

}