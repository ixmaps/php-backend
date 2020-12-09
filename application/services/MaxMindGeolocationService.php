<?php
/**
  *
  * Service for MaxMind geolocation
  *
  * @author IXmaps.ca (Colin)
  * @since Nov 2020
  *
  */



class MaxMindGeolocationService {
  public function __construct($geoRepo) {
    $this->repository = $geoRepo;
  }

  /**
    *
    * @param string
    *
    * @return MaxMind Geolocation object or false
    */
  public function getByIp($ip)
  {
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
      throw new Exception('Not a valid IP address');
    }
    return $this->repository->getByIp($ip);
  }

} // end class
