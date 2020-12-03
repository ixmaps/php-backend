<?php
/**
 *
 * Service for IXmaps geolocation (ip_addr_info)
 *
 * @author IXmaps.ca (Colin)
 * @since Nov 2020
 *
 */


require_once('../model/Geolocation.php');
require_once('../services/IXmapsGeolocationService.php');
require_once('../services/IPInfoGeolocationService.php');
require_once('../services/IP2LocationGeolocationService.php');
require_once('../services/IPInfoAPIService.php');

class GeolocationService {

  public function __construct($dbconn) {
    $this->db = $dbconn;
    $this->IXgeoservice = new IXmapsGeolocationService($this->db);
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
  }

  public function getByIpAndDate($ip, $date)
  {
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
      throw new Exception('Not a valid IP address');
    }

    $geo = $this->hydrate($this->IXgeoservice->getByIpAndDate($ip, $date));


    // if doesn't exist
    // if ($geo == false) {
    //   $this->createNewIp($ip);
    //   $geo = $this->hydrate($this->IXgeoservice->getByIpAndDate($ip, $date));
    // }

    // check if we need a stale update. Performance issue? Better to use a cron?
    // $this->updateStaleDataIfNeeded($ip)




    // if the ip doesn't exist or is stale (and the date is recent enough to warrant a check)
    // is this two tightly coupled? Do we want the controller to make this decision?
    // this is getting convoluted enough that it points to something being wrong structurally
    // TODO: this isn't going to work! How often will most recent eg getByIp be requested? Since requests for TRs for the map will use by date. Do we need a cron as well?
    if ($date == date("Y-m-d") && ($geo == false || $geo->getStaleStatus() == true)) {
      // TODO: we want to add a new non-stale entry to ix and do an update on ipinfo
      $this->createNewIp($ip);
      $geo = $this->hydrate($this->IXgeoservice->getByIpAndDate($ip, $date));
    }

    // if the ip still doesn't exist in the db, eg if ipinfo doesn't have it
    // potential TODO - what about missing values?
    // eg if lat / long / country missing, refresh the data?
    // if lat / long / country still missing, use ip2loc?
    if ($geo == false) {
      return false;
    }

    // if we don't have an asn, use ip2loc
    // only do this for most recent, since we don't have backdated values for eg ip2. That is, it would be misleading to show most recent ASN values for a router if the request is for geoloc/asn data from years ago
    // TODO - combine with above
    if ($date == date("Y-m-d") && ($geo->getASNum() == NULL || $geo->getASNum() == -1)) {
      $I2geoservice = new IP2LocationGeolocationService($this->db);
      $i2geo = $I2geoservice->getByIp($geo->getIp());
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
    * @return None
    */
  public function createNewIp($ip)
  {
    $IIgeoservice = new IPInfoGeolocationService($this->db);
    // this feels odd... passing in raw data to a different service? Or is this actually the right way for this and others?
    // this also just feels like a weird place to do this...
    $geoData = new IPInfoAPIService($ip);
    $IIgeoservice->create($geoData);
    $this->IXgeoservice->create($geoData);
  }


  private function hydrate($ixgeo) {
    if ($ixgeo == false) {
      return false;
    }

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
