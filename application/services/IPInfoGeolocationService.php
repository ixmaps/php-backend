<?php
/**
 *
 * Service for IPInfo geolocation
 *
 * @author IXmaps.ca (Colin)
 * @since Nov 2020
 *
 */

class IPInfoGeolocationService {
  public function __construct($geoRepo) {
    $this->repository = $geoRepo;
  }


  /**
    *
    * @param string
    *
    * @return IPInfoGeolocation object or false
    */
  public function getByIp(string $ip)
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
    * @return IPInfoGeolocation object or false
    */
  // public function create(string $ip)
  public function create($geoData)
  {
    return $this->repository->create($geoData);
  }


  /**
    *
    * @param string
    *
    * @return Boolean success value
    */
  // not currently used - TBD on the general GeolocationService. TODO
  // public function upsert(string $ip)
  public function upsert($geoData)
  {
    return $this->repository->upsert($ip);
  }


  /**
    *
    * @param string
    *
    * @return Boolean success value (false if ip does not exist in db)
    */
  public function deleteByIp(string $ip)
  {
    return $this->repository->deleteByIp($ip);
  }


} // end class
