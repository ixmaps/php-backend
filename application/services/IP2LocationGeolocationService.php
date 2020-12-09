 <?php
/**
 *
 * Service for IP2Location geolocation
 *
 * @author IP2Location.ca (Colin)
 * @since Nov 2020
 *
 */

class IP2LocationGeolocationService {
  public function __construct($geoRepo) {
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
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
      throw new Exception('Not a valid IP address');
    }
    return $this->repository->getByIp($ip);
  }


} // end class
