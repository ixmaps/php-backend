<?php
/**
 *
 * This class manages transactions with geolocation sources/databases and services and returns a geolocation object for a given ip
 * It's expected that new geolocation sources will be included as methods
 * @author IXmaps.ca
 *
 */
class Geolocation {
  private $ixmaps_ip_data; //TEMP
  private $mm_ip_data; //TEMP
  private $ip;         // not sure if we will need this here. It's a bit redundant
  private $hostname;  // Added for debugging/analysis purposes
  private $lat;
  private $long;
  private $city;
  private $country;
  private $asnum;
  private $asname;
  private $source;        // do we like this name? Open to suggestions
  private $asn_source;
  private $geodata_source;


  // move the IXmapsMaxMind class into here? Maybe slower to constantly open and close Dat files? Otherwise? Hopefully not use globals :(


  /**
   *
   * Creates a Geolocation object for a given IP. Checks different Geolocation sources:  (ixmaps, maxmind)
   * @param inet $ip Ip address in inet/string format
   */
  function __construct($ip, $debug_mode=false) {
    global $mm;
    $this->ip = $ip;

    // TODO: validate ip format
    $ip_is_valid = false;
    if($ip!=""){
      // Query MM object
      $this->mm_ip_data = $mm->getGeoIp($ip);
      $ip_is_valid = true;
    } else {
      //exit();
    }

    // 1. Check if IP exists in IXmaps DB
    if($ip!="" && $ip!=null){
      $this->ixmaps_ip_data = $this->checkIpIxmapsDb($ip, $debug_mode);  
    } else {
      $this->ixmaps_ip_data = null;
    }
    

    if ($this->ixmaps_ip_data) {
      // Check if ip has been geo corrected
      if($this->ixmaps_ip_data['gl_override']!=null){
        if($debug_mode){
          echo "\n\t(".$ip.") : In IXmaps DB, geocorrected\n\n";
        }
        // Use IXmaps geo data
        $this->lat = $this->ixmaps_ip_data['lat'];
        $this->long = $this->ixmaps_ip_data['long'];
        $this->city = $this->ixmaps_ip_data['mm_city'];
        $this->country = $this->ixmaps_ip_data['mm_country'];

        if($this->ixmaps_ip_data['asnum']!=-1){
          if($debug_mode){
            echo "\n\t\tUsing asnum and asname from IXmaps DB\n\n";
          }
          $this->asnum = $this->ixmaps_ip_data['asnum'];
          $this->asname = $this->ixmaps_ip_data['name']."";
          $this->hostname = $this->ixmaps_ip_data['hostname'];
          $this->asn_source = "ixmaps";
          

        } else {
          // use MM for asn and asname
          if($debug_mode){
            echo "\n\t\tUsing asnum and asname from MM DB\n\n";
          }
          $this->asnum = $this->mm_ip_data['asn'];
          $this->asname = $this->mm_ip_data['isp'];
          $this->hostname = $this->mm_ip_data['hostname'];
          $this->asn_source = "maxmind";
          
          if($this->mm_ip_data['asn']!=null){
            /*
              TODO: update IXmapsDB with known ASN from MM ?
            */
          }
        }
        $this->geodata_source = "ixmaps";
      } else {
        if($debug_mode){
          echo "\n\t(".$ip.") : In IXmaps DB, NOT geocorrected\n\n";
        }
        // TODO: do something if the ip exists in IXmaps db but it has not been geo-corrected?
        // using MM for now
        $this->lat = $this->mm_ip_data["geoip"]["latitude"];
        $this->long = $this->mm_ip_data["geoip"]["longitude"];
        $this->city = $this->mm_ip_data["geoip"]["city"];
        $this->country = $this->mm_ip_data["geoip"]["country_code"];
        $this->geodata_source = "maxmind";
        $this->hostname = $this->mm_ip_data['hostname'];
        $this->geodata_source = "maxmind";

        if($this->mm_ip_data["asn"]==null && $this->ixmaps_ip_data['asnum']!=-1){
          if($debug_mode){
            echo "\n\t\tasnum is null in MM but valid in IXmaps db\n\n";
          }
          $this->asnum = $this->ixmaps_ip_data['asnum'];
          $this->asname = $this->ixmaps_ip_data['name'];
          $this->asn_source = "ixmaps";
        } else {
          if($debug_mode){
            echo "\n\t\tUsing asnum and asname from MM\n\n";
          }
          $this->asnum = $this->mm_ip_data["asn"];
          $this->asname = $this->mm_ip_data["isp"];
          $this->asn_source = "maxmind";
        }
        
      }

    // 2. Check Maxmind data
    } else if (isset($this->mm_ip_data['geoip']['country_code'])) {

      // Insert new ip in IXmaps Db for logging purposes??
      /*$this->insertNewIpAddress($mm_ip_data);*/
      if($debug_mode){
        echo "\n\t(".$ip.") : In MM DB\n\n";
      }

      // Use MM geo data
      $this->lat = $this->mm_ip_data["geoip"]["latitude"];
      $this->long = $this->mm_ip_data["geoip"]["longitude"];
      $this->city = $this->mm_ip_data["geoip"]["city"];
      $this->country = $this->mm_ip_data["geoip"]["country_code"];
      $this->asnum = $this->mm_ip_data["asn"];
      $this->asname = $this->mm_ip_data["isp"];
      //$this->source = "maxmind";
      $this->geodata_source = "maxmind";
      $this->asn_source = "maxmind";
      $this->hostname = $this->mm_ip_data['hostname'];

    // 3. Set default geo data
    } else {
      if($debug_mode){
        echo "\n\t(".$ip.") : Not in IXmaps nor in MM DBs\n\n";
      }
      $this->lat = NULL;
      $this->long = NULL;
      $this->city = NULL;
      $this->country = NULL;
      $this->asnum = NULL;
      $this->asname = NULL;
      $this->source = NULL;
      $this->geodata_source = NULL;
      $this->asn_source = NULL;
      $this->hostname = NULL;
    }

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
  private function checkIpIxmapsDb($ip, $debug_mode){
    global $dbconn;

    $sql = "SELECT ip_addr_info.hostname, ip_addr_info.asnum, as_users.name, ip_addr_info.lat, ip_addr_info.long, ip_addr_info.mm_country, ip_addr_info.mm_city, ip_addr_info.p_status, ip_addr_info.gl_override FROM ip_addr_info, as_users WHERE (ip_addr_info.asnum = as_users.num) AND ip_addr_info.ip_addr = $1";

    // TODO: add error handling that is consistent with PTR approach
    $params = array($ip);
    $result = pg_query_params($dbconn, $sql, $params);// or die('checkIxmapsDb: Query failed'.pg_last_error());

    //$result = pg_query($dbconn, $sql) or die('checkIpIxmapsDb: Query failed'.pg_last_error());
    $ip_addr = pg_fetch_all($result);
    pg_free_result($result);
    if ($ip_addr) {
      //print_r($ip_addr);
      //echo "\n exists";
      return $ip_addr[0];
    } else {
      return false;
    }
  }

  /**
    * Insert New Ip address into IXmaps DB
    * @param $ip_data array IP, Geodata and asn
    */
  private function insertNewIpAddress($ip_data){
    global $dbconn;
    $sql = "INSERT INTO ip_addr_info (ip_addr, asnum, mm_lat, mm_long, mm_country, mm_region, mm_city, mm_postal, mm_area_code, mm_dma_code, lat, long, gl_override) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13)";
    $params = array($ip_data['ip'], $ip_data['asn'], $ip_data['geoip']['latitude'], $ip_data['geoip']['longitude'], $ip_data['geoip']['country_code'], $ip_data['geoip']['region'], $ip_data['geoip']['city'], $ip_data['geoip']['postal_code'], $ip_data['geoip']['area_code'], $ip_data['geoip']['dma_code'], $ip_data['geoip']['latitude'], $ip_data['geoip']['longitude'], NULL);

      /*echo "\n".$sql;
      print_r($params);*/

    // TODO: add error handling that is consistent with PTR approach
    $result = pg_query_params($dbconn, $sql, $params);//
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

  public function getCountry() {
    return $this->country;
  }

  public function getASNum() {
    return $this->asnum;
  }

  public function getASName() {
    return $this->asname;
  }

  public function getSource() {
    return $this->source;
  }
  
  public function getAsnSource() {
    return $this->asn_source;
  }
  
  public function getGeodataSource() {
    return $this->geodata_source;
  }
} // end class
