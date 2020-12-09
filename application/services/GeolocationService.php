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

  function __construct($geoRepo, $IXgeoRepo, $IIgeoRepo, $I2geoRepo) {
    $this->geo = new Geolocation();
    $this->repository = $geoRepo;
    $this->IXgeoService = new IXmapsGeolocationService($IXgeoRepo);
    $this->IIgeoService = new IPInfoGeolocationService($IIgeoRepo);
    $this->I2geoService = new IP2LocationGeolocationService($I2geoRepo);
  }

  // TODO: move the hydrate/build to the repo


  /**
    *
    * @param string
    *
    * @return Geolocation object or null (most recent date)
    */
  public function getByIp(string $ip)
  {
    return $this->getByIpAndDate($ip, date("Y-m-d"));
  }

  /**
    *
    * @param $ip string and $date string
    *
    * @return Geolocation object or null (most recent date)
    */
  public function getByIpAndDate(string $ip, string $date)
  {
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
      throw new Exception('Not a valid IP address');
    }

    $IXgeo = $this->IXgeoService->getByIpAndDate($ip, $date);
    $this->buildByIXGeo($IXgeo);

    // if doesn't exist
    // if ($geo == false) {
    //   $this->createNewIp($ip);
    //   $geo = $this->buildByIXGeo($this->IXgeoService->getByIpAndDate($ip, $date));
    // }

    // check if we need a stale update. Performance issue? Better to use a cron?
    // $this->updateStaleDataIfNeeded($ip)

    // if the ip doesn't exist or is stale (and the date is recent enough to warrant a check)
    // is this two tightly coupled? Do we want the controller to make this decision?
    // this is getting convoluted enough that it points to something being wrong structurally
    // TODO: this isn't going to work! How often will most recent eg getByIp be requested? Since requests for TRs for the map will use by date. Do we need a cron as well?
    // if ($date == date("Y-m-d") && ($geo == false || $geo->getStaleStatus() == true)) {
    //   // TODO: we want to add a new non-stale entry to ix and do an update on ipinfo

    //   // BROKEN - this will try to create multiple ipinfos if stale. Switch to upsert?
    //   $IXgeo = $this->create($ip, $this->IXgeoService, $this->IIgeoService);
    //   $this->buildByIXGeo($IXgeo);
    // }

    // if the ip still doesn't exist in the db, eg if ipinfo doesn't have it
    // potential TODO - what about missing values?
    // eg if lat / long / country missing, refresh the data?
    // if lat / long / country still missing, use ip2loc?
    if ($this->geo == false) {
      return false;
    }

    // if we don't have an asn, use ip2loc
    // only do this for most recent, since we don't have backdated values for eg ip2. That is, it would be misleading to show most recent ASN values for a router if the request is for geoloc/asn data from years ago
    // TODO - combine with above?

    if ($date == date("Y-m-d") && ($this->geo->getASNum() == NULL || $this->geo->getASNum() == -1)) {
      $i2geo = $this->I2geoService->getByIp($ip);
      $this->buildByIP2Geo($i2geo);
    }

    return $this->geo;
  }


  /**
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


  // TODO: think about moving these to the repo

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
