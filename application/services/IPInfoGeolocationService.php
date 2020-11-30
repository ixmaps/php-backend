<?php
/**
 *
 * Service for IPInfo geolocation
 *
 * @author IXmaps.ca (Colin)
 * @since Nov 2020
 *
 */

require_once('../repositories/IpInfoGeolocationRepository.php');

class IPInfoGeolocationService {
  public function __construct($dbconn) {
    $this->repository = new IPInfoGeolocationRepository($dbconn);
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


  /**
    *
    * @param string
    *
    * @return Boolean success value
    */
  // public function create($createData)
  public function create($ip)
  {
    return $this->repository->create($ip);
  }

  // this one will need an update, perhaps an upsert


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
