<?php
/**
 *
 * Service for IXmaps geolocation (ip_addr_info)
 *
 * @author IXmaps.ca (Colin)
 * @since Nov 2020
 *
 */

// require_once('../model/Utility.php');

require_once('../repositories/IXmapsGeolocationRepository.php');

class IXmapsGeolocationService {
  // IXmapsGeolocationService vs GeolocationService
  // These look ups are done in another class, since they need to be reusable?
  // public function __construct($ixmapsIpData, $maxmindIpData) {
  public function __construct($dbconn) {
    $this->repository = new IXmapsGeolocationRepository($dbconn);
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
    return $this->repository->getByIpAndDate($ip, date("Y-m-d"));
  }

  /**
    *
    * @param string
    *
    * @return Geolocation object or null
    */
  public function getByIpAndDate($ip, $date)
  {
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
      throw new Exception('Not a valid IP address');
    }
    return $this->repository->getByIpAndDate($ip, $date);
  }


  /**
    *
    * @param string
    *
    * @return Boolean success value
    */
  public function create($ip)
  {
    return $this->repository->create($ip);
  }


  // TODO: update

  /**
    *
    * @param string
    *
    * @return Boolean success value (false if ip does not exist in db)
    */
  public function deleteByIp($ip)
  {
    return $this->repository->deleteByIp($ip);
  }


} // end class
