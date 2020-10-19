<?php
/**
 *
 * Class to handle ip2location ip address values
 *
 * @since Aug 2020
 * Updated Oct 2020
 * @author IXmaps.ca (Colin)
 *
 */
require_once('../config.php');

class IXmapsIp2Location
{
  private $lat;
  private $long;
  private $city;
  private $region;
  private $countryCode;
  private $postal;
  private $asnum;
  private $asname;

  /**
   *
   */
  function __construct($ip) {
    global $dbconn;

    if (filter_var($ip, FILTER_VALIDATE_IP) == false || $ip == "") {
      throw new Exception("Not a valid IP address");
    }

    // https://lite.ip2location.com/database/ip-country-region-city-latitude-longitude-zipcode

    // translate to decimal format
    $ip = $this->ip2long($ip);
    $sql = "SELECT * FROM ip2location_ip_addr WHERE ip_from <= ".$ip." and ip_to >= ".$ip;
    $result = pg_query($dbconn, $sql) or die('compareASN query failed: ' . pg_last_error());
    $row = pg_fetch_all($result);
    pg_free_result($result);

    $row = $row[0];

    if (empty($row["asnum"])) {
      $asVals = $this->determineAsn($ip);
      $row["asnum"] = $asVals[0];
      $row["asname"] = $asVals[1];
    }

    // some cleanup, since we prefer blank values to '-'
    if ($row["country"] == "-" || $row["country"] == "- ") {
      $row["country"] = "";
    }
    if ($row["city"] == "-") {
      $row["city"] = "";
    }
    if ($row["postal"] == "-") {
      $row["postal"] = "";
    }
    if ($row["asnum"] == "-") {
      $row["asnum"] = NULL;
    }
    if ($row["asname"] == "-") {
      $row["asname"] = "";
    }

    try {
      $this->lat = $row["lat"];
      $this->long = $row["long"];
      $this->city = $row["city"];
      $this->region = $row["region"];
      $this->countryCode = $row["country"];
      $this->postal = $row["postal"];
      $this->asnum = $row["asnum"];
      $this->asname = $row["asname"];
    } catch(Exception $e) {
      $this->lat = NULL;
      $this->long = NULL;
      $this->city = NULL;
      $this->region = NULL;
      $this->countryCode = NULL;
      $this->postal = NULL;
      $this->asnum = NULL;
      $this->asname = NULL;
    }
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

  private function ip2long($ip) {
    if (is_numeric($ip)) {
      return sprintf( "%u", floatval($ip) );
    } else {
      return sprintf( "%u", floatval(ip2long($ip) ));
    }
  }

  private function determineAsn($ip) {
    global $dbconn;
    // since not all of the ip2_location_ip_addrs have an asn, check if it's available in the ip2_location_asn table
    $sql = "SELECT * FROM ip2location_asn WHERE ip_from <= ".$ip." and ip_to >= ".$ip;
    $result = pg_query($dbconn, $sql) or die('determineAsn query failed: ' . pg_last_error());
    $row = pg_fetch_row($result);
    pg_free_result($result);

    return [$row[3],$row[4]];
  }
}
?>