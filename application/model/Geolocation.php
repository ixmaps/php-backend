<?php
/**
 *
 * This class manages transactions with geolocation sources/databases and services
 * and returns a geolocation object for a given ip
 *
 * Full overhaul to include other data sources in Oct 2020 - file name is the same, but this
 * is (currently) fundamentally different than what we used to have
 *
 * Short term plan is to use this file as a master geoloc object with all data sources
 * Long term, this will be opinionated about geoloc, this will determine 'true' geoloc values
 * from amongst data sources
 *
 * @author IXmaps.ca (Colin)
 * @since Oct 2020
 *
 */
require_once('../model/IXmapsMaxMind.php');
require_once('../model/IXmapsIpInfo.php');
require_once('../model/IXmapsIpInfoFactory.php');
require_once('../model/IXmapsIp2Location.php');
require_once('../model/IXmapsGeoCorrection.php');

class Geolocation {
  private $ip;
  private $lat;
  private $long;
  private $city;
  private $region;
  private $countryCode;
  private $postalCode;
  private $asnum;
  private $asname;
  private $hostname;
  // TODO - these will be derived values (plus more?)
  // private $asnSource;
  // private $geoSource;

  private $ixLat;
  private $ixLong;
  private $ixCity;
  private $ixRegion;
  private $ixCountryCode;
  private $ixPostalCode;
  private $ixASnum;
  private $ixASname;
  private $ixHostname;

  private $mmLat;
  private $mmLong;
  private $mmCity;
  private $mmRegion;
  private $mmRegionCode;
  private $mmCountry;
  private $mmCountryCode;
  private $mmPostalCode;
  private $mmASnum;
  private $mmASname;
  private $mmHostname;

  private $iiLat;
  private $iiLong;
  private $iiCity;
  private $iiRegion;
  private $iiCountryCode;
  private $iiPostalCode;
  private $iiASnum;
  private $iiASname;
  private $iiHostname;

  private $i2Lat;
  private $i2Long;
  private $i2City;
  private $i2Region;
  private $i2CountryCode;
  private $i2PostalCode;
  private $i2ASnum;
  private $i2ASname;


  /**
   *
   * Creates a Geolocation object for a given IP.
   * Current Geolocation sources: ixmaps (ix), maxmind (mm), ipinfo (ii), ip2location (i2)
   *
   * @param inet $ip Ip address in inet/string format
   */
  function __construct($ip) {
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
      throw new Exception('Not a valid IP address');
    }

    // mostly empty constructor
    // only the ~10 default values, remove the rest
    // asn and geosource
    // factory or service builds with a asnsource / geosource, sets those


  }

  /**
    * Check if an ip exists in IXmaps DB and collect geo data
    *
    * @param $ip inet ip address
    *
    * @return $ip_addr array geo data or bool false
    *
    */
  private function fetchIXgeoloc($ip) {
    global $dbconn;

    $sql = "SELECT ip_addr_info.hostname, ip_addr_info.asnum, as_users.name, as_users.short_name, ip_addr_info.lat, ip_addr_info.long, ip_addr_info.mm_country, ip_addr_info.mm_region, ip_addr_info.mm_city, ip_addr_info.mm_postal, ip_addr_info.gl_override FROM ip_addr_info LEFT JOIN as_users ON ip_addr_info.asnum = as_users.num WHERE ip_addr_info.ip_addr = $1";

    $params = array($ip);
    $result = pg_query_params($dbconn, $sql, $params) or die('fetchIXgeoloc: Query failed '.pg_last_error());
    $ip_addr = pg_fetch_all($result);
    pg_free_result($result);

    if ($ip_addr) {
      return $ip_addr[0];
    }

    return false;
  }

  public function getIp() {
    return $this->ip;
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
  public function getRegion() {
    return $this->city;
  }
  public function getCountry() {
    return $this->countryCode;
  }
  public function getPostalCode() {
    return $this->postalCode;
  }
  public function getASNum() {
    return $this->asnum;
  }
  public function getASName() {
    return $this->asname;
  }
  public function getHostname() {
    return $this->hostname;
  }

  // public function getAsnSource() {
  //   return $this->asnSource;
  // }

  // public function getGeoSource() {
  //   return $this->geoSource;
  // }


  public function getIXLat() {
    return $this->ixLat;
  }
  public function getIXLong() {
    return $this->ixLong;
  }
  public function getIXCity() {
    return $this->ixCity;
  }
  public function getIXRegion() {
    return $this->ixRegion;
  }
  public function getIXCountryCode() {
    return $this->ixCountryCode;
  }
  public function getIXPostalCode() {
    return $this->ixPostalCode;
  }
  public function getIXASNum() {
    return $this->ixASnum;
  }
  public function getIXASName() {
    return $this->ixASname;
  }
  public function getIXHostname() {
    return $this->ixHostname;
  }

  public function getMMLat() {
    return $this->mmLat;
  }
  public function getMMLong() {
    return $this->mmLong;
  }
  public function getMMCity() {
    return $this->mmCity;
  }
  public function getMMRegion() {
    return $this->mmRegion;
  }
  public function getMMRegionCode() {
    return $this->mmRegionCode;
  }
  public function getMMCountry() {
    return $this->mmCountry;
  }
  public function getMMCountryCode() {
    return $this->mmCountryCode;
  }
  public function getMMPostalCode() {
    return $this->mmPostalCode;
  }
  public function getMMASNum() {
    return $this->mmASnum;
  }
  public function getMMASName() {
    return $this->mmASname;
  }
  public function getMMHostname() {
    return $this->mmHostname;
  }

  public function getIILat() {
    return $this->iiLat;
  }
  public function getIILong() {
    return $this->iiLong;
  }
  public function getIICity() {
    return $this->iiCity;
  }
  public function getIIRegion() {
    return $this->iiRegion;
  }
  public function getIICountryCode() {
    return $this->iiCountryCode;
  }
  public function getIIPostalCode() {
    return $this->iiPostalCode;
  }
  public function getIIHostname() {
    return $this->iiHostname;
  }

  public function getI2Lat() {
    return $this->i2Lat;
  }
  public function getI2Long() {
    return $this->i2Long;
  }
  public function getI2City() {
    return $this->i2City;
  }
  public function getI2Region() {
    return $this->i2Region;
  }
  public function getI2CountryCode() {
    return $this->i2CountryCode;
  }
  public function getI2PostalCode() {
    return $this->i2PostalCode;
  }
  public function getI2ASNum() {
    return $this->i2ASnum;
  }
  public function getI2ASName() {
    return $this->i2ASname;
  }
} // end class
