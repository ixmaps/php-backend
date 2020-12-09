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
  public function getByIp($ip)
  {
    return $this->getByIpAndDate($ip, date("Y-m-d"));
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
  public function deleteByIp($ip)
  {
    return $this->repository->deleteByIp($ip);
  }


} // end class
