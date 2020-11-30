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


// NB - switch tables when ready: ipinfo_ip_addr_paid -> ipinfo_ip_addr



class IPInfoGeolocationRepository
{
  private $db;

  public function __construct($db)
  {
    $this->db = $db;
  }

  public function getByIp($ip)
  {
    // TODO: modularize this when we do insert / upsert / update

    // First we look in our DB to see if the IP is there
    $sql = "SELECT * FROM ipinfo_ip_addr_paid WHERE ip_addr = '".$ip."'";

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

    // If no, we'll call out to their API for the data. TODO, move this to GeolocationService
    // if ($geoData == false) {
    //   $geoData = new IPInfoAPIService($ip);
    //   // TODO: add insert into our DB here, or is that too much magic? If we add insert, we need to switch the create to not use this func!
    // }

    return $this->hydrate($geoData);
  }


  public function create($ip)
  {
    $geo = $this->getByIp($ip);

    $sql = "INSERT INTO ipinfo_ip_addr_paid (ip_addr, asnum, asname, lat, long, hostname, country, region, city, postal) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10);";
    $ipData = array($ip, $geo->getASNum(), $geo->getASName(), $geo->getLat(), $geo->getLong(), $geo->getHostname(), $geo->getCountry(), $geo->getRegion(), $geo->getCity(), $geo->getPostalCode());

    try {
      $result = pg_query_params($this->db, $sql, $ipData);
      pg_free_result($result);
      return true;
    } catch (Exception $e) {
      throw new Exception($e);
    }

    return false;
  }


  public function deleteByIp($ip)
  {
    $sql = "DELETE FROM ipinfo_ip_addr_paid WHERE ip_addr = '".$ip."'";
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