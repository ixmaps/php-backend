<?php
/**
 *
 * This class manages transactions with geolocation sources/databases and services
 * and returns a geolocation object for a given ip
 *
 * It's expected that new geolocation sources will be included as methods
 *
 * @author IXmaps.ca (Antonio, Colin)
 * @since 2018 Jan 1 (last updated Aug 2019)
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
  function __construct($ip, $debugMode=false) {
    $this->ip = $ip;
    $mm = new IXmapsMaxMind($ip);

    // TODO: validate ip format
    $ipIsValid = false;
    if ($ip != "") {
      $ipIsValid = true;
    }

    // 1. Check if IP exists in IXmaps DB
    if ($ip != "" && $ip != null) {
      $this->ixmaps_ip_data = $this->checkIpIxmapsDb($ip, $debugMode);
    } else {
      $this->ixmaps_ip_data = null;
    }

    if ($this->ixmaps_ip_data) {
      // Check if ip has been geo corrected
      if ($this->ixmaps_ip_data['gl_override'] != null) {
        if ($debugMode) {
          echo "\n\t(".$ip.") : In IXmaps DB, geocorrected\n\n";
        }
        // Use IXmaps geo data
        $this->lat = (float)$this->ixmaps_ip_data['lat'];
        $this->long = (float)$this->ixmaps_ip_data['long'];
        $this->city = $this->ixmaps_ip_data['mm_city'];
        $this->country = $this->ixmaps_ip_data['mm_country'];

        // these are never used in the PTR return. Do we need to rethink what this class actually does?
        if ($this->ixmaps_ip_data['asnum'] != -1) {
          if ($debugMode) {
            echo "\n\t\tUsing asnum and asname from IXmaps DB\n\n";
          }
          $this->asnum = $this->ixmaps_ip_data['asnum'];
          $this->asname = $this->ixmaps_ip_data['name']."";
          $this->hostname = $this->ixmaps_ip_data['hostname'];
          $this->asn_source = "ixmaps";

        } else {
          // use MM for asn and asname
          if ($debugMode) {
            echo "\n\t\tUsing asnum and asname from MM DB\n\n";
          }
          $this->asnum = $mm->getASNum();
          $this->asname = $mm->getASName();
          $this->hostname = $mm->getHostname();
          $this->asn_source = "maxmind";

        }
        $this->geo_source = "ixmaps";
      } else {
        if ($debugMode) {
          echo "\n\t(".$ip.") : In IXmaps DB, NOT geocorrected\n\n";
        }
        // TODO: do something if the ip exists in IXmaps db but it has not been geo-corrected?
        // using MM for now
        $this->lat = $mm->getLat();
        $this->long = $mm->getLong();
        $this->city = $mm->getCity();
        $this->country = $mm->getCountryCode();
        $this->hostname = $mm->getHostname();
        $this->geo_source = "maxmind";

        if ($mm->getASNum() == null && $mm->getASNum() != -1) {
          if ($debugMode) {
            echo "\n\t\tasnum is null in MM but valid in IXmaps db\n\n";
          }
          $this->asnum = $this->ixmaps_ip_data['asnum'];
          $this->asname = $this->ixmaps_ip_data['name'];
          $this->asn_source = "ixmaps";
        } else {
          if ($debugMode) {
            echo "\n\t\tUsing asnum and asname from MM\n\n";
          }
          $this->asnum = $mm->getASNum();
          $this->asname = $mm->getASName();
          $this->asn_source = "maxmind";
        }

      }

    // 2. Check Maxmind data
    } else if ($mm->getCountryCode()) {

      // Insert new ip in IXmaps Db for logging purposes?
      // $this->insertNewIpAddress($ip, $this->mm);

      if ($debugMode) {
        echo "\n\t(".$ip.") : In MM DB\n\n";
      }

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
      if ($debugMode) {
        echo "\n\t(".$ip.") : Not in IXmaps nor in MM DBs\n\n";
      }
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

    /* TODO: 4. check other geo-data sources. */

  }

  /**
    * Check if an ip exists in IXmaps DB and collect geo data
    *
    * @param $ip inet ip address
    *
    * @return $ip_addr array Geo data or Bool false
    *
    */
  private function checkIpIxmapsDb($ip, $debugMode) {
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

  /**
    * Insert New Ip address into IXmaps DB
    * @param $ip_data array IP, Geodata and asn
    */
  // CM: suggest this does not belong in this class
  private function insertNewIpAddress($ip, $mm){
    global $dbconn;
    $sql = "SELECT ip_addr FROM ip_addr_info WHERE ip_addr = $1";
    $params = $ip;
    $result = pg_query_params($dbconn, $sql, $params) or die();
    $id_data = pg_fetch_all($result);
    if (!$id_data) {
      $sql = "INSERT INTO ip_addr_info (ip_addr, asnum, mm_lat, mm_long, hostname, mm_country, mm_region, mm_city, mm_postal, lat, long, p_status, gl_override) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15)";
      $params = array($ip, $mm->getASNum(), $mm->getLat(), $mm->getLong(), $mm->getHostname() , $mm->getCountryCode(), $mm->getRegion(), $mm->getCity(), $mm->getPostalCode(), $mm->getLat(), $mm->getLong(), 'x', NULL);

        // TODO: add error handling that is consistent with PTR approach
        $result = pg_query_params($dbconn, $sql, $params) or die();
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
