<?php
/**
 *
 * Service for IXmaps geolocation (ip_addr_info)
 *
 * @author IXmaps.ca (Colin)
 * @since Nov 2020
 *
 */


class IXmapsGeolocationService {

  function __construct($geoRepo) {
    $this->repository = $geoRepo;
  }


  /**
    *
    * @param string
    *
    * @return Geolocation object or null
    */
  public function getByIp(string $ip)
  {
    return $this->getByIpAndDate($ip, date("Y-m-d"));
  }

  /**
    *
    * @param string
    *
    * @return Geolocation object or null
    */
  public function getByIpAndDate(string $ip, $date)
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
  public function create($geoData)
  {
    return $this->repository->create($geoData);
  }


  // TODO: update

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


  /**
    *
    * @param string
    *
    * @return Boolean success value (false if ip does not exist in db)
    */
  public function deleteByIpAndCreatedAt(string $ip, $date)
  {
    return $this->repository->deleteByIpAndCreatedAt($ip, $date);
  }


} // end class
