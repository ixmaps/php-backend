<?php
/**
 *
 * Class to handle interaction with the MaxMind data objects (mmdbs)
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
  private $ipAddr;
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

    global $MMDatDir;

    if (filter_var($ip, FILTER_VALIDATE_IP) == false || $ip == "") {
      throw new Exception("Not a valid IP address");
    }

    try {
      $cityReader = new Reader($MMDatDir."/GeoLite2-City.mmdb");
      $asnReader = new Reader($MMDatDir."/GeoLite2-ASN.mmdb");
    } catch(Exception $e) {
      echo 'Caught exception: ',  $e->getMessage();
    }

    try {
      $this->ipAddr = $ip;
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
      $this->ipAddr = NULL;
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

  public function getIpAddr() {
    return $this->ipAddr;
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
}
?>