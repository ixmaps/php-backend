<?php
/**
 *
 * Service for IXmaps geolocation (ip_addr_info)
 *
 * @author IXmaps.ca (Colin)
 * @since Nov 2020
 *
 */

require_once('../services/IXmapsGeolocationService.php');
require_once('../services/IPInfoGeolocationService.php');
require_once('../services/IP2LocationGeolocationService.php');
require_once('../model/Geolocation.php');

class GeolocationService {

  function __construct($geoRepo, $IXgeoService, $IIgeoService, $I2geoService) {
    $this->geo = new Geolocation();
    $this->repository = $geoRepo;
    $this->IXgeoService = $IXgeoService;
    $this->IIgeoService = $IIgeoService;
    $this->I2geoService = $I2geoService;
  }


  /**
    *
    * @param string
    *
    * @return Geolocation object or false (most recent date)
    */
  public function getByIp(string $ip)
  {
    return $this->getByIpAndDate($ip, date("Y-m-d"));
  }

  /**
    *
    * @param $ip string and $date string
    *
    * @return Geolocation object or false
    */
  public function getByIpAndDate(string $ip, string $date)
  {
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
      throw new Exception('Not a valid IP address');
    }

    $IXgeo = $this->IXgeoService->getByIpAndDate($ip, $date);

    // handle non-existent and stale ips
    if ($IXgeo == false || $date == date("Y-m-d") && $IXgeo->getStaleStatus() == true) {
      $IXgeo = $this->upsert($ip);
    }
    // if the ip still doesn't exist in the db, eg if ipinfo doesn't have it
    if ($IXgeo == false) {
      return false;
    }
    // potential TODO - what about missing values?
    // eg if lat / long / country missing, refresh the data?
    // if $IXgeo still missing, use ip2loc instead of returning false? What constitutes success?

    $this->buildByIXGeo($IXgeo);

    // if we don't have an asn, use ip2loc
    // only do this for most recent, since we don't have backdated values for eg ip2. That is, it would be misleading to show most recent ASN values for a router if the request is for geoloc/asn data from years ago
    if ($date == date("Y-m-d") && ($this->geo->getASNum() == NULL || $this->geo->getASNum() == -1)) {
      $IP2geo = $this->I2geoService->getByIp($ip);
      $this->buildByIP2Geo($IP2geo);
    }

    return $this->geo;
  }


  /**
    * Insert ix and ii
    *
    * @param string
    *
    * @return IXmapsGeolocation object (!!!)
    */
  public function create($ip)
  {
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
      throw new Exception('Not a valid IP address');
    }
    return $this->repository->create($ip, $this->IXgeoService, $this->IIgeoService);
  }

  /**
    * Insert ix and upsert ii
    *
    * @param string
    *
    * @return IXmapsGeolocation object (!!!)
    */
  public function upsert($ip)
  {
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
      throw new Exception('Not a valid IP address');
    }
    return $this->repository->upsert($ip, $this->IXgeoService, $this->IIgeoService);
  }


  // TODO: think about moving these to the repo as a hydrate

  /**
    *
    * @param IXmapsGeolocation object
    *
    * @return The object (never used, could remove) or false (not used yet)
    */
  private function buildByIXGeo($IXgeo) {
    if ($IXgeo == false) {
      return false;
    }

    $this->geo->setGeoSource('IXmaps');
    $this->geo->setASNsource('IXmaps');
    $this->geo->setIp($IXgeo->getIp());
    $this->geo->setLat($IXgeo->getLat());
    $this->geo->setLong($IXgeo->getLong());
    $this->geo->setCity($IXgeo->getCity());
    $this->geo->setRegion($IXgeo->getRegion());
    $this->geo->setCountry($IXgeo->getCountry());
    $this->geo->setPostalCode($IXgeo->getPostalCode());
    $this->geo->setASNum($IXgeo->getASNum());
    $this->geo->setASName($IXgeo->getASName());
    $this->geo->setHostname($IXgeo->getHostname());
    $this->geo->setCreatedAt($IXgeo->getCreatedAt());
    $this->geo->setUpdatedAt($IXgeo->getUpdatedAt());

    return $this->geo;
  }


  /**
    *
    * @param IP2LocationGeolocation object
    *
    * @return The object (never used, could remove) or false (not used yet)
    */
  private function buildByIP2Geo($IP2geo) {
    if ($IP2geo == false) {
      return false;
    }

    $this->geo->setASNsource('IP2Location');
    $this->geo->setASNum($IP2geo->getASNum());
    $this->geo->setASName($IP2geo->getASName());

    return $this->geo;
  }

} // end class
