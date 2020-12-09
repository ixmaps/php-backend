<?php
/**
 *
 * Inject dependencies and create a GeolocationService object
 *
 * @author IXmaps.ca (Colin)
 * @since Nov 2020
 *
 */

require_once('../services/GeolocationService.php');
require_once('../services/IXmapsGeolocationService.php');
require_once('../services/IPInfoGeolocationService.php');
require_once('../services/IP2LocationGeolocationService.php');
require_once('../repositories/GeolocationRepository.php');
require_once('../repositories/IXmapsGeolocationRepository.php');
require_once('../repositories/IPInfoGeolocationRepository.php');
require_once('../repositories/IP2LocationGeolocationRepository.php');

class GeolocationWrapperService {

  public $geolocationService;

  function __construct() {
    $IXgeoRepo = new IXmapsGeolocationRepository();
    $IXgeoService = new IXmapsGeolocationService($IXgeoRepo);

    $IIgeoRepo = new IPInfoGeolocationRepository();
    $IIgeoService = new IPInfoGeolocationService($IIgeoRepo);

    $I2geoRepo = new IP2LocationGeolocationRepository();
    $I2geoService = new IP2LocationGeolocationService($I2geoRepo);

    $geoRepo = new GeolocationRepository();
    $this->geolocationService = new GeolocationService($geoRepo, $IXgeoService, $IIgeoService, $I2geoService);
  }


} // end class
