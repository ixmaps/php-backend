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
    // TODO - consider if the below try / catches are appropriate for error handling?
    // They're necessary for the case where the MM files don't have the IP
    // Could also consider another value to explicitly show whether or not those values exist
    // eg $this->cityRecordExists
    
    global $MM_dat_dir;

    if (filter_var($ip, FILTER_VALIDATE_IP) == false || $ip == "") {
      throw new Exception("Not a valid IP address");
    }

    try {
      $cityReader = new Reader($MM_dat_dir."/GeoLite2-City.mmdb");
      $asnReader = new Reader($MM_dat_dir."/GeoLite2-ASN.mmdb");
    } catch(Exception $e) {
      echo 'Caught exception: ',  $e->getMessage();
    }

    try {
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


  // TODO: this obviously doesn't belong here - move it to somewhere more logical or remove

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