<?php
/**
 *
 * @author IXmaps.ca (Colin)
 * @since Nov 2020
 *
 */

// COME BACK TO THIS ONCE WE'RE WORKING



require_once('../model/IXmapsMaxMind.php');
require_once('../model/IXmapsIpInfo.php');
require_once('../model/IXmapsIp2Location.php');
require_once('../model/IXmapsGeoCorrection.php');

class GeolocationService {
  // these look ups are done in another class, since they need to be reusable (already the case, sort of... working for MaxMind)
  public function __construct($ixmapsIpData, $maxmindIpData) {}

  /**
    *
    * @param string
    *
    * @return Geolocation object or null
    */
  public function fetchGeolocation($ipAddress)
  {
    $geolocation = new Geolocation();

    // set all to NULL


    // deal with geo_source stuff
    // if ($ixmapsIpData && $ixmapsIpData['gl_override']!=null) // should have proper getters
    // $geolocation->setLat($ixmapsIpData['lat']);
    // $geolocation->setLong($ixmapsIpData['long']);
    // $geolocation->setCity($ixmapsIpData['city']);
    // ...
    // $geolocation->setGeoSource('ixmaps');
    // else
    // $geolocation->setLat($mmIpData['lat']);
    // $geolocation->setLong($mmIpData['long']);
    // $geolocation->setCity($mmIpData['city']);
    // ...
    // $geolocation->setGeoSource('maxmind');


    // deal with asn_source stuff
    // if ($ixmapsIpData['asnum']!=-1)
    // $geolocation->setASNum($ixmapsIpData['asnum']);
    // $geolocation->setASName($ixmapsIpData['asname']);
    // $geolocation->setAsnSource('ixmaps');
    // else
    // $geolocation->setASNum($mmIpData['asnum']);
    // $geolocation->setASName($mmIpData['asname']);
    // $geolocation->setAsnSource('maxmind');

    if (!geolocationData) {
      $geolocationData = $this->maxmindRepository->fetchByIp($ipAddress);
    }

    if (!geolocationData) {
      return null; // or do we want an empty Geolocation object?
    }

    return $this->hydrate($geolocationData);
  }

  /**
    * turns raw db data into a Geolocation object
    */
  private function hydrate(Array $rawDBBusiness)
  {
    $geolocation = new Geolocation();

    $geolocation->setLat($rawDBBusiness['lat']);
    // ... etc

    return $geolocation;
  }

} // end class
