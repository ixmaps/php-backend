<?php
/**
 * 
 * This class manages transactions with geolocation sources/databases and services and returns a geolocation object for a given ip
 * It's expected that new geolocation sources will be included as methods 
 * @author IXmaps.ca
 *
 */
class Geolocation {
  private $mm_ip_data; // REMOVE THIS! deal with this inside functions
  private $ip;         // not sure if we will need this here. It's a bit redundant
  private $lat;
  private $long;
  private $city;
  private $country;
  private $asnum;
  private $asname;
  private $source;        // do we like this name? Open to suggestions


  // move the IXmapsMaxMind class into here? Maybe slower to constantly open and close Dat files? Otherwise? Hopefully not use globals :(

  // perhaps return ip + 'source' (eg MM). That would speak to using a setup with a verbose constructor like this

  // NOTE: current setup is an alternative to this kind of structure:
  // $lat = Geolocation::getLat($myIp);
  // $city = Geolocation::getLat($myIp);


  /**
   * 
   * Creates a Geolocation object for a given IP. Checks different Geolocation sources:  (ixmaps, maxmind)
   * @param inet $ip Ip address in inet/string format
   */
  function __construct($ip) {
    global $mm;
    $this->ip = $ip; 
    
    // Query MM object
    $this->mm_ip_data = $mm->getGeoIp($ip);

    // 1. Check if IP exists in IXmaps DB
    $ixmapsDb = $this->checkIpIxmapsDb($ip);
    if ($ixmapsDb) {
      // Check if ip has been geo corrected
      if($ixmapsDb['gl_override']!=null){
        // Use IXmaps geo data
        $this->lat = $ixmapsDb['lat'];
        $this->long = $ixmapsDb['long'];
        $this->city = $ixmapsDb['mm_city'];
        $this->country = $ixmapsDb['mm_country'];
        if($ixmapsDb['asnum']!="-1"){
          $this->asnum = $ixmapsDb['asnum'];
        } else {
          $this->asnum = NULL;
        }
        $this->asname = $mm_ip_data["isp"];
        $this->source = "ixmaps";
      } else {
        // TODO: do something if the ip exists in IXmaps db but it has not been geo-corrected?
        // using MM for now
        $this->lat = $this->mm_ip_data["geoip"]["latitude"];
        $this->long = $this->mm_ip_data["geoip"]["longitude"];
        $this->city = $this->mm_ip_data["geoip"]["city"];
        $this->country = $this->mm_ip_data["geoip"]["country_code"];
        $this->asnum = $this->mm_ip_data["asn"];
        $this->asname = $this->mm_ip_data["isp"];
        $this->source = "maxmind";
      }

    // 2. Check Maxmind data
    } else if (isset($this->mm_ip_data['geoip']['country_code'])) {
          
      // Insert new ip in IXmaps Db for logging purposes??
      /*$this->insertNewIpAddress($mm_ip_data);*/

      // Use MM geo data
      $this->lat = $this->mm_ip_data["geoip"]["latitude"];
      $this->long = $this->mm_ip_data["geoip"]["longitude"];
      $this->city = $this->mm_ip_data["geoip"]["city"];
      $this->country = $this->mm_ip_data["geoip"]["country_code"];
      $this->asnum = $this->mm_ip_data["asn"];
      $this->asname = $this->mm_ip_data["isp"];
      $this->source = "maxmind";

    // 3. Set default geo data
    } else {
      $this->lat = NULL;
      $this->long = NULL;
      $this->city = NULL;
      $this->country = NULL;
      $this->asnum = NULL;
      $this->asname = NULL;
      $this->source = NULL;
    }

    /* TODO: 4. check other geo-data sources. */


    //$this->printVars(); 
  }

  /**
    * Check if an ip exists in IXmaps DB and collect geo data
    *
    * @param $ip inet ip address
    *
    * @return $ip_addr array Geo data 
    *
    */
  private function checkIpIxmapsDb($ip){
    global $dbconn;

    $sql = "SELECT ip_addr_info.hostname, ip_addr_info.lat, ip_addr_info.long, ip_addr_info.mm_country, ip_addr_info.mm_city, ip_addr_info.asnum, ip_addr_info.p_status, ip_addr_info.gl_override FROM ip_addr_info WHERE ip_addr = $1";

    // TODO: add error handling that is consistent with PTR approach
    $params = array($ip);
    $result = pg_query_params($dbconn, $sql, $params);// or die('checkIxmapsDb: Query failed'.pg_last_error());

    //$result = pg_query($dbconn, $sql) or die('checkIpIxmapsDb: Query failed'.pg_last_error());
    $ip_addr = pg_fetch_all($result);
    //print_r($ip_addr);
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

  /**
    * Print class variables: for debugging only
    */
  private function printVars(){
    echo "\n************************";
    echo "\nGeolocation Variables";
    echo "\n************************";
    echo "\nMM data:\n";
    print_r($this->mm_ip_data);
    echo "\nlat: ".$this->lat;
    echo "\nlong: ".$this->long;
    echo "\ncity: ".$this->city;
    echo "\ncountry: ".$this->country;
    echo "\nasnum: ".$this->asnum;
    echo "\nasname: ".$this->asname;
    echo "\nsource: ".$this->source;
    echo "\n************************";
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
} // end class
