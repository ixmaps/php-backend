<?php
/**
 *
 * Repository for 'amalgam' geolocation object
 *
 * @author IXmaps.ca (Colin)
 * @since Nov 2020
 *
 */

require_once('../services/IPInfoAPIService.php');

class GeolocationRepository
{
  function __construct() { }

  /**
    * Writes to both ip_addr_info and ipinfo_ip_addr
    *
    * @param string
    *
    * @return IXmapsGeolocation object (WARNING: big gotcha potential. IXmapsGeolocation, and *not* Geolocation!)
    * or false if the data is too junky
    *
    */
  public function create(string $ip, $IXgeoService, $IIgeoService)
  {
    $geoData = new IPInfoAPIService($ip);

    if ($geoData->getLat() == NULL && $geoData->getLat() == NULL && $geoData->getASNum() == NULL){
      return false;
    }

    $IIgeoService->create($geoData);
    return $IXgeoService->create($geoData);
  }

  /**
    * Insert into ip_addr_info and upsert for ipinfo_ip_addr
    *
    * @param string
    *
    * @return IXmapsGeolocation object (WARNING: big gotcha potential. IXmapsGeolocation, and *not* Geolocation!)
    * or false if the data is too junky
    *
    */
  public function upsert(string $ip, $IXgeoService, $IIgeoService)
  {
    $geoData = new IPInfoAPIService($ip);

    if ($geoData->getLat() == NULL && $geoData->getLat() == NULL && $geoData->getASNum() == NULL){
      return false;
    }

    // only insert / upsert if there is some kind of useful data

    $IIgeoService->upsert($geoData);
    return $IXgeoService->create($geoData);
  }

}