<?php
/**
 *
 * Class to handle interaction with the Maxmind data objects (mmdbs)
 * Entire rewrite to handle GeoLite2 and use proper getters instead of
 * implicit MM object getting passed around to various other classes
 *
 * Updated Aug 2019
 * @author IXmaps.ca (Colin)
 *
 */
require_once('../config.php');
require_once('../../vendor/autoload.php');
use GeoIp2\Database\Reader;

class IXmapsMaxMind
{
  private $MM_dat_dir = "";
  // private $MM_geoip_dir = "";
  // private $giasn;
  // private $gi1;

  private $lat;
  private $long;
  private $city;
  private $region;
  private $regionCode;
  private $postal;
  private $country;
  private $countryCode;
  private $asnum;
  private $asname;
  private $hostname;

  /**
   *
   */
  function __construct($ip) {
    // TODO - first check if $ip is null or malformed, and fail loudly if not
    // TODO - consider if the below try / catches are appropriate for error handling?
    // They're necessary for the case where the MM files don't have the IP
    // Could also consider another value to explicitly show whether or not those values exist
    // eg $this->mmContainsASNvalue
    global $MM_dat_dir;     // why is this a global? - TODO
    $this->MM_dat_dir = $MM_dat_dir;
    // $this->MM_geoip_dir = $MM_geoip_dir;
    // $this->loadGeoIpIncFiles();
    // $this->giasn = geoip_open($this->MM_dat_dir."/GeoIPASNum.dat", GEOIP_STANDARD);
    // $this->gi1 = geoip_open($this->MM_dat_dir."/GeoLiteCity.dat", GEOIP_STANDARD);
    if (filter_var($ip, FILTER_VALIDATE_IP) == false) {
      throw new Exception("Not a valid IP address");
    }

    try {
      $cityReader = new Reader($MM_dat_dir."/GeoLite2-City.mmdb");
      $cityRecord = $cityReader->city($ip);
      $this->lat = $cityRecord->location->latitude;
      $this->long = $cityRecord->location->longitude;
      $this->city = $cityRecord->city->name;
      $this->region = $cityRecord->mostSpecificSubdivision->name;
      $this->regionCode = $cityRecord->mostSpecificSubdivision->isoCode;
      $this->postal = $cityRecord->postal->code;
      $this->country = $cityRecord->country->name;
      $this->countryCode = $cityRecord->country->isoCode;
    } catch(Exception $e) {
      $this->lat = NULL;
      $this->long = NULL;
      $this->city = NULL;
      $this->region = NULL;
      $this->regionCode = NULL;
      $this->postal = NULL;
      $this->country = NULL;
      $this->countryCode = NULL;
    }

    try {
      $asnReader = new Reader($MM_dat_dir."/GeoLite2-ASN.mmdb");
      $asnRecord = $asnReader->asn($ip);
      $this->asnum = $asnRecord->autonomousSystemNumber;
      $this->asname = $asnRecord->autonomousSystemOrganization;
    } catch(Exception $e) {
      $this->asnum = NULL;
      $this->asname = NULL;
    }

    $this->hostname = gethostbyaddr($ip);

    // TODO - do we need to unset or otherwise close the Reader object?
    // eg unset($cityReader);
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
    return $this->region;
  }

  public function getRegionCode() {
    return $this->regionCode;
  }

  public function getPostalCode() {
    return $this->postal;
  }

  public function getCountry() {
    return $this->country;
  }

  public function getCountryCode() {
    return $this->countryCode;
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

  /**
   * Get Geo IP and ASN data from MaxMind local files
   * @param string $ip
   */
  public function getGeoIp($ip) {
    $this->geoIp = geoip_record_by_addr($this->gi1, $ip);

    if(isset($this->geoIp->city) && $this->geoIp->city!=""){
      $this->geoIp->city = mb_convert_encoding($this->geoIp->city, "UTF-8", "iso-8859-1");
    }

    $r = array(
      "ip"=>$ip,
      "geoip"=>(array)$this->geoIp,
      "asn"=>NULL,
      "isp"=>NULL,
      "hostname"=>gethostbyaddr($ip)
    );
    $ipAsn = geoip_name_by_addr($this->giasn, $ip);
    if($ipAsn!=NULL){
      $asn_isp = $this->extractAsn($ipAsn);
      $r['asn'] = $asn_isp[0];
      $r['isp'] = $asn_isp[1];
    }

    return $r;
  }

  // public function loadGeoIpIncFiles() {
  //   // load MM dat files
  //   include($this->MM_geoip_dir."/geoip.inc");
  //   include($this->MM_geoip_dir."/geoipcity.inc");
  //   include($this->MM_geoip_dir."/geoipregionvars.php");
  // }


  /**
   * Return country of IP
   * @param string $ip
   */
  // public function getMMCountry($ip) {
  //   $mmRecord = geoip_record_by_addr($this->gi1, $ip);
  //   return $mmRecord->country_code;
  // }


  /**
    Close MM dat files. Use this after all transactions are completed
  */
  // public function closeDatFiles(){
  //   geoip_close($this->gi1);
  //   geoip_close($this->giasn);
  // }

  // /**
  //   Parse asn and isp from MM data string
  // */
  // private function extractAsn($asnString) {
  //   $asnArray = explode(' ', $asnString);
  //   if(isset($asnArray[0])){
  //     $asn = $asnArray[0];
  //     $asn = substr($asn, 2);
  //     $isp = "";

  //     for ($i=1; $i < count($asnArray); $i++) {
  //       $isp .= $asnArray[$i]." ";

  //     }
  //     $isp = trim($isp);
  //   } else {
  //     $asn = "";
  //     $isp = "";
  //   }
  //   return array($asn, $isp);
  // }


  // TODO: this obviously doesn't belong here

  /**
    Get Closest Geo Data using mm world_cities DB Based on city population and radius
  */
  // public function getGeoDataByPopulationRadius($currentMmData, $limit=1, $radius=50000)
  // {
  //   global $dbconn;

  //   // Get closest geodata for lat/long
  //   $sql = "SELECT country, name, admin1, population, latitude, longitude FROM geoname WHERE population is not null and ST_DWithin(the_geom, ST_SetSRID(ST_MakePoint(".$currentMmData['geoip']['longitude'].",".$currentMmData['geoip']['latitude']."), 4326), $radius) and country = '".$currentMmData['geoip']['country_code']."' ORDER BY population DESC limit $limit;";
  //   $result = pg_query($dbconn, $sql) or die('getGeoDataRadius failed'.pg_last_error());
  //   $geodata = pg_fetch_all($result);
  //   return $geodata;
  // }
}
?>