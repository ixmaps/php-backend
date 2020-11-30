<?php
/**
 *
 * Service for IXmaps geolocation (ip_addr_info)
 *
 * @author IXmaps.ca (Colin)
 * @since Nov 2020
 *
 */


// TODO - still using all?
require_once('../model/Geolocation.php');
require_once('../services/IXmapsGeolocationService.php');
require_once('../services/IPInfoGeolocationService.php');
require_once('../services/IP2LocationGeolocationService.php');
require_once('../services/IPInfoAPIService.php');

class GeolocationService {

  public function __construct($dbconn) {
    $this->db = $dbconn;
    // move to getByIpAndDate if we're only using it once
    $this->ixgeoservice = new IXmapsGeolocationService($dbconn);
  }


  /**
    *
    * @param string
    *
    * @return Geolocation object or null (most recent date)
    */
  public function getByIp($ip)
  {
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
      throw new Exception('Not a valid IP address');
    }
    return $this->getByIpAndDate($ip, date("Y-m-d"));

    // START HERE - geosource, asnsource etc
    // Then work back to create

    // TODO
    // if ($geo == false || $geo->getStaleState() == true) {
    //   // this feels wrong... passing in raw data to the service? Or is this actually the right way for this and others?
    //   $geoData = new IPInfoAPIService($ip);
    //   $this->IXGeoservice->create($geoData);
    //   // or do these chain?
    //   $this->IIGeoservice->create($geoData);
    // }

    // - use most recent value
    // If we are missing values
    // - we use ip2loc for asn
    // - use something for other values?
  }


  public function getByIpAndDate($ip, $date)
  {
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
      throw new Exception('Not a valid IP address');
    }

    $geo = $this->hydrate($this->ixgeoservice->getByIpAndDate($ip, $date));

    // if ip is missing or stale, refresh it
    // if ($geo == false || $geo->getStaleState() == true) {
    //   $this->create($ip);

    // potential TODO - what about missing values?
    // eg if lat / long / country missing, refresh the data
    // if lat / long / country still missing, use ip2loc?

    // if we don't have an asn, use ip2loc
    if ($geo->getASNum() == NULL || $geo->getASNum() == -1) {
      $i2geoservice = new IP2LocationGeolocationService($this->db);
      $i2geo = $i2geoservice->getByIp($ip);
      $geo->setASNum($i2geo->getASnum());
      $geo->setASName($i2geo->getASname());
      $geo->setASNsource('IP2Location');
    }

    return $geo;
  }


  /**
    *
    * @param string
    *
    * @return Boolean success value
    */
  public function create($ip)
  {
    // return $this->repository->create($ip);
    // create in both ix and ip
  }


  private function hydrate($ixgeo) {
    $geo = new Geolocation();
    $geo->setIp($ixgeo->getIp());
    $geo->setLat($ixgeo->getLat());
    $geo->setLong($ixgeo->getLong());
    $geo->setCity($ixgeo->getCity());
    $geo->setRegion($ixgeo->getRegion());
    $geo->setCountry($ixgeo->getCountry());
    $geo->setPostalCode($ixgeo->getPostalCode());
    $geo->setASNum($ixgeo->getASNum());
    $geo->setASName($ixgeo->getASName());
    $geo->setHostname($ixgeo->getHostname());
    $geo->setCreatedAt($ixgeo->getCreatedAt());
    $geo->setUpdatedAt($ixgeo->getUpdatedAt());
    return $geo;
  }

} // end class
