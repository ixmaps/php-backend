<?php
/**
 *
 * Repository for IXmaps geolocation
 *
 * @author IXmaps.ca (Colin)
 * @since Nov 2020
 *
 */

require_once('../model/IXmapsGeolocation.php');
require_once('../services/IPInfoGeolocationService.php');


class IXmapsGeolocationRepository
{
  private $db;
  private $selectSql;

  public function __construct($db)
  {
    $this->db = $db;
    $this->selectSql = "SELECT ip_addr_info.ip_addr, ip_addr_info.hostname, ip_addr_info.asnum, as_users.name, as_users.short_name, ip_addr_info.lat, ip_addr_info.long, ip_addr_info.mm_country, ip_addr_info.mm_region, ip_addr_info.mm_city, ip_addr_info.mm_postal, ip_addr_info.created_at, ip_addr_info.updated_at FROM ip_addr_info LEFT JOIN as_users ON ip_addr_info.asnum = as_users.num WHERE ip_addr_info.ip_addr = $1";
  }

  /**
    * @param $ip string
    *
    * @return most recently added IX Geolocation for the ip
    */
  public function getByIp($ip)
  {
    return $this->selectByIp($this->selectSql, array($ip));
  }

  /**
    * @param $ip string, $date string
    *
    * @return IX Geolocation object closest to date
    */
  public function getByIpAndDate($ip, $date) {
    $sql = $this->selectSql." ORDER BY abs(extract(epoch from (created_at - $2))) LIMIT 1";

    return $this->selectByIp($sql, array($ip, $date));
  }


  /**
    * @param $geoData object (from ipinfo api, but other options can be plug/played)
    *
    * @return true if insert successful
    */
  public function create($geoData)
  {
    $sql = "INSERT INTO ip_addr_info (ip_addr, asnum, mm_lat, mm_long, hostname, mm_country, mm_region, mm_city, mm_postal, p_status, lat, long, gl_override) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13);";
    $ipData = array($geoData->getIp(), $geoData->getASNum(), $geoData->getLat(), $geoData->getLong(), $geoData->getHostname(), $geoData->getCountry(), $geoData->getRegion(), $geoData->getCity(), $geoData->getPostalCode(), "N", $geoData->getLat(), $geoData->getLong(), NULL);

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


  private function selectByIp($sql, $params) {
    try {
      $result = pg_query_params($this->db, $sql, $params);
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
    * turns raw db data into a Geolocation object
    */
  private function hydrate(Object $dbData)
  {
    $asname = $dbData->short_name ?: $dbData->asname;
    $createdAt = new DateTime($dbData->created_at);
    $updatedAt = new DateTime($dbData->updated_at);

    $geo = new IXmapsGeolocation();
    $geo->setIp($dbData->ip_addr);
    $geo->setLat($dbData->lat);
    $geo->setLong($dbData->long);
    $geo->setCity($dbData->mm_city);
    $geo->setRegion($dbData->mm_region);
    $geo->setCountry($dbData->mm_country);
    $geo->setPostalCode($dbData->mm_postal);
    $geo->setASNum($dbData->asnum);
    $geo->setASName($asname);
    $geo->setHostname($dbData->hostname);
    $geo->setCreatedAt($createdAt);
    $geo->setUpdatedAt($updatedAt);

    return $geo;
  }
}