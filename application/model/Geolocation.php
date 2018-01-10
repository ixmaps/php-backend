<?php
class Geolocation {
  private $ipData;      // TEMP!
  private $lat;
  private $long;
  private $city;
  private $country;
  private $asnum;
  private $asname;


  // move the IXmapsMaxMind class into here? Maybe slower to constantly open and close Dat files? Otherwise? Hopefully not use globals :(

  // perhaps return ip + 'source' (eg MM). That would speak to using a setup with a verbose constructor like this

  // NOTE: current setup is an alternative to this kind of structure:
  // $lat = Geolocation::getLat($myIp);
  // $city = Geolocation::getLat($myIp);

  function __construct($ip) {
    global $mm;     // TEMP?

    $ipData = $mm->getGeoIp($ip);

    $this->lat = $ipData["geoip"]["latitude"];
    $this->long = $ipData["geoip"]["longitude"];
    $this->city = 'TODO'; //$ipData->city;
    $this->country = $ipData->country;
    $this->asnum = $ipData["asn"];
    $this->asname = $ipData["isp"];
    $this->country = $ipData["geoip"]["country_code"];

    // TODO

    // 1. see if we have the IP in the DB
    //$existsInDB = checkIpExists($hop["ip"]); ?

    // if ($existsInDB) {
    //   // TODO
    // } else {
    //   // 2. see if Maxmind has the IP
    //   $ipData = $mm->getGeoIp($hop["ip"]);
    // }

    // 3 some kind of default if MM doesn't have it
    // TODO

    /*
    - Check if ip exists in IXmaps DB
    - Check if ip has been geo-corrected in IXmaps DB
    - Determine what to do if the ip does exist but was never geo-corrected in IXmaps DB
    - if none of the previous cases apply, use MM data
    - Define a case for completing MM data in case that only coordinates are present in MM data but no information about Country, City, Province, etc. are available.
    - Determine what do do if the IP does not exist in MM
    - Define yet another cases for ip geolocation lookup, e.g. verification of other geo location services or databases
    */

  }

  public function getLat() {
    return $this->lat;
  }

  public function getLong() {
    return $this->long;
  }

  public function getCity() {
    return $this->city;
  }

  public function getCountry() {
    return $this->country;
  }

  public function getASNum() {
    return $this->asnum;
  }

  public function getASName() {
    return $this->asname;
  }


  /**
    * Check if an ip exists in DB
    *
    * @param $ip address of type ???
    *
    * @return boolean
    *
    */
  public static function checkIpExists($ip)
  {
    // global $dbconn;

    // CAREFUL - the following is prone to SQL Injection attacks

    // $sql = "SELECT ip_addr_info.ip_addr FROM ip_addr_info WHERE ip_addr = '".$ip."'";
    // // look up pg_query_params, much safer

    // // add error handling that is consistent with PTR approach
    // $result = pg_query($dbconn, $sql) or die('checkIpExists: Query failed'.pg_last_error());
    // $ip_addr = pg_fetch_all($result);

    // if ($ip_addr) {
    //   return true;
    // }

    // pg_free_result($result);

    return false;
  }
}