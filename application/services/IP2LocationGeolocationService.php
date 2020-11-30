<?php
/**
 *
 * Service for IP2Location geolocation
 *
 * @author IP2Location.ca (Colin)
 * @since Nov 2020
 *
 */

require_once('../repositories/IP2LocationGeolocationRepository.php');

class IP2LocationGeolocationService {
  public function __construct($db) {
    $this->repository = new IP2LocationGeolocationRepository($db);
  }

  /**
    *
    * @param string
    *
    * @return Geolocation object or null
    */
  public function getByIp($ip)
  {
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
      throw new Exception('Not a valid IP address');
    }
    return $this->repository->getByIp($ip);
  }


} // end class
