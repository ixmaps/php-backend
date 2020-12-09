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
    */
  public function create(string $ip, $IXgeoService, $IIgeoService)
  {
    // this feels odd... passing in raw data to a different service? Or is this actually the right way for this and others?
    // this also just feels like a weird place to do this...
    $geoData = new IPInfoAPIService($ip);

    // these should be returning objects - create
    $IIgeoService->create($geoData);
    return $IXgeoService->create($geoData);
  }

}