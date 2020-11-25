<?php
/**
 *
 * @author IXmaps.ca (Colin)
 * @since Nov 2020
 *
 */

require_once('../config.php');
require_once('../model/IXmapsGeolocation.php');
require_once('../repositories/IXmapsGeolocationRepository.php');

class GeolocationService {
  // IXmapsGeolocationService vs GeolocationService
  // Next step is to expand so that this one service is used for multiple lookups? That would mean passing in model and repo, weird
  // that feels like we want one more layer then? The hydrate needs to know too much about the specifics of the raw dbdata, ix.asnum vs ipinfo.num

  // These look ups are done in another class, since they need to be reusable?
  // public function __construct($ixmapsIpData, $maxmindIpData) {
  public function __construct() {
    global $dbconn;
    $this->repository = new IXmapsGeolocationRepository($dbconn);
    $this->geolocation = new IXmapsGeolocation();
  }


  /**
    *
    * @param string
    *
    * @return Geolocation object or null
    */
  public function fetchGeolocation($ip)
  {

    return $this->hydrate($this->repository->getByIp($ip));
  }


  /**
    *
    * @param string
    *
    * @return Success value?
    */
  public function saveGeolocation($createData)
  {
    $this->repository->save($createData);
    // is it better to use create and hydrate a model here, then save it?
  }


  /**
    * turns raw db data into a Geolocation object
    */
  private function hydrate(Array $dbData)
  {
    $this->geolocation->setIp($dbData['ip_addr']);
    $this->geolocation->setLat($dbData['lat']);
    $this->geolocation->setLong($dbData['long']);

    return $this->geolocation;
  }

} // end class
