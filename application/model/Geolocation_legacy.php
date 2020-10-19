<?php
/**
 *
 * This class manages transactions with geolocation sources/databases and services
 * and returns a geolocation object for a given ip
 *
 * Full overhaul to include other data sources in Oct 2020 - this is now legacy (except for PTR? Check this)
 *
 * @author IXmaps.ca (Colin)
 * @since Jan 2018
 * Updated Oct 2020
 *
 */
require_once('../model/IXmapsMaxMind.php');

class Geolocation {
  private $ixmaps_ip_data; //TEMP
  private $mm;
  private $ip;         // not sure if we will need this here. It's a bit redundant
  private $hostname;   // Added for debugging/analysis purposes
  private $lat;
  private $long;
  private $city;
  private $nsa;
  private $country;
  private $asnum;
  private $asname;
  private $asn_source;
  private $geo_source;

  /**
   *
   * Creates a Geolocation object for a given IP.
   * Current Geolocation sources: ixmaps, maxmind.
   *
   * @param inet $ip Ip address in inet/string format
   */
  function __construct($ip) {
    $this->ip = $ip;
    $mm = new IXmapsMaxMind($ip);

    // TODO: validate ip format
    $ipIsValid = false;
    if ($ip != "") {
      $ipIsValid = true;
    }

    // 1. Check if IP exists in IXmaps DB
    if ($ip != "" && $ip != null) {
      $this->ixmaps_ip_data = $this->checkIpIxmapsDb($ip);
    } else {
      $this->ixmaps_ip_data = null;
    }

    if ($this->ixmaps_ip_data) {
      // Check if ip has been geo corrected
      if ($this->ixmaps_ip_data['gl_override'] != null) {
        // Use IXmaps geo data
        $this->lat = (float)$this->ixmaps_ip_data['lat'];
        $this->long = (float)$this->ixmaps_ip_data['long'];
        $this->city = $this->ixmaps_ip_data['mm_city'];
        $this->country = $this->ixmaps_ip_data['mm_country'];

        // these are never used in the PTR return. Do we need to rethink what this class actually does?
        if ($this->ixmaps_ip_data['asnum'] != -1) {
          $this->asnum = $this->ixmaps_ip_data['asnum'];
          $this->asname = $this->ixmaps_ip_data['name']."";
          $this->hostname = $this->ixmaps_ip_data['hostname'];
          $this->asn_source = "ixmaps";

        } else {
          $this->asnum = $mm->getASNum();
          $this->asname = $mm->getASName();
          $this->hostname = $mm->getHostname();
          $this->asn_source = "maxmind";

        }
        $this->geo_source = "ixmaps";
      } else {
        // TODO: do something if the ip exists in IXmaps db but it has not been geo-corrected?
        // using MM for now
        $this->lat = $mm->getLat();
        $this->long = $mm->getLong();
        $this->city = $mm->getCity();
        $this->country = $mm->getCountryCode();
        $this->hostname = $mm->getHostname();
        $this->geo_source = "maxmind";

        if ($mm->getASNum() == null && $mm->getASNum() != -1) {
          $this->asnum = $this->ixmaps_ip_data['asnum'];
          $this->asname = $this->ixmaps_ip_data['name'];
          $this->asn_source = "ixmaps";
        } else {
          $this->asnum = $mm->getASNum();
          $this->asname = $mm->getASName();
          $this->asn_source = "maxmind";
        }

      }

    // 2. Check MaxMind data
    } else if ($mm->getCountryCode()) {

      // Use MM geo data
      $this->lat = $mm->getLat();
      $this->long = $mm->getLong();
      $this->city = $mm->getCity();
      $this->country = $mm->getCountryCode();
      $this->asnum = $mm->getASNum();
      $this->asname = $mm->getASName();
      $this->geo_source = "maxmind";
      $this->asn_source = "maxmind";
      $this->hostname = NULL;

    // 3. Set default geo data
    } else {
      $this->lat = NULL;
      $this->long = NULL;
      $this->city = NULL;
      $this->country = NULL;
      $this->asnum = NULL;
      $this->asname = NULL;
      $this->source = NULL;
      $this->geo_source = NULL;
      $this->asn_source = NULL;
      $this->hostname = NULL;
    }

    $this->setNsa($this->city);

  }

  /**
    * Check if an ip exists in IXmaps DB and collect geo data
    *
    * @param $ip inet ip address
    *
    * @return $ip_addr array Geo data or Bool false
    *
    */
  private function checkIpIxmapsDb($ip) {
    global $dbconn;

    $sql = "SELECT ip_addr_info.hostname, ip_addr_info.asnum, as_users.name, ip_addr_info.lat, ip_addr_info.long, ip_addr_info.mm_country, ip_addr_info.mm_city, ip_addr_info.p_status, ip_addr_info.gl_override FROM ip_addr_info, as_users WHERE (ip_addr_info.asnum = as_users.num) AND ip_addr_info.ip_addr = $1";

    // TODO: add error handling that is consistent with PTR approach
    $params = array($ip);
    $result = pg_query_params($dbconn, $sql, $params);// or die('checkIxmapsDb: Query failed'.pg_last_error());

    $ip_addr = pg_fetch_all($result);
    pg_free_result($result);

    if ($ip_addr) {
      return $ip_addr[0];
    } else {
      return false;
    }
  }

  // CM: suggest this does not belong in this class
  private function setNsa($cityName = "") {
    $nsaCities = ["San Francisco", "Los Angeles", "New York", "Dallas", "Washington", "Ashburn", "Seattle", "San Jose", "San Diego", "Miami", "Boston", "Phoenix", "Salt Lake City", "Nashville", "Denver", "Saint Louis", "Bridgeton", "Bluffdale", "Houston", "Chicago", "Atlanta", "Portland"];
    if (in_array($cityName, $nsaCities)) {
      $this->nsa = true;
    } else {
      $this->nsa = false;
    }
  }

  public function getHostname() {
    return $this->hostname;
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

  public function getNsa() {
    return $this->nsa;
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

  public function getAsnSource() {
    return $this->asn_source;
  }

  public function getGeoSource() {
    return $this->geo_source;
  }
} // end class
