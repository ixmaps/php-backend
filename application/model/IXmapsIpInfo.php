<?php
/**
 *
 * Class to handle interaction with the IpInfo API
 *
 * Created Nov 2021
 * @author IXmaps.ca (Colin)
 *
 */
require_once('../config.php');
require_once('../../vendor/autoload.php');
use ipinfo\ipinfo\IPinfo;


class IXmapsIpInfo
{
  private $lat;
  private $long;
  private $city;
  private $region;
  private $countryCode;
  private $postal;
  private $hostname;
  private $asnum;
  private $asname;

  function __construct() {

  }

  public function hydrate($ip) {
    global $dbconn;
    global $IIAccessToken;

    // CDM: this is a bit of a hack put in on 20211207. Instead of *always* looking it up (why?!),
    // look if we have it in the table first. Then log if we need to look it up.
    // I put this in cause we got an email from ipinfo saying we were doing 115K lookups per week
    // and I'm skeptical. This isn't really the right way to do this, but this codebase is on its
    // last legs, and I'm not going to put in too much more work...

    // check if it's in the DB
    $sql = "SELECT * FROM ipinfo_ip_addr WHERE ip_addr ='".$ip."'";
    $result = pg_query($dbconn, $sql) or die('ipinfo_ip_addr lookup failed: ' . pg_last_error());
    $ip_results = pg_fetch_all($result);
    pg_free_result($result);
    $ip_info = $ip_results[0];

    if (isset($ip_info)) {
      $this->ip = $ip_info["ip_addr"];
      $this->lat = $ip_info["lat"];
      $this->long = $ip_info["long"];
      $this->city = $ip_info["city"];
      $this->region = $ip_info["region"];
      $this->countryCode = $ip_info["country"];
      $this->postal = $ip_info["postal"];
      $this->hostname = $ip_info["hostname"];

    // otherwise get it from the service
    } else {
      $client = new IPinfo($IIAccessToken);
      $results = $client->getDetails($ip);

      $message = date("Y-m-d H:i:s")."\nRequesting and hydrating an IPinfo object for ".$ip;
      $logfile = "../../log/ipinfo.log";
      error_log("\n".$message."\n", 3, $logfile);

      try {
        $this->ip = $ip;
        $this->lat = $results->latitude;
        $this->long = $results->longitude;
        $this->city = $results->city;
        $this->region = $results->region;
        $this->countryCode = $results->country;
        $this->postal = $results->postal;
        $this->hostname = $results->hostname;
        $this->asnum = $this->determineASNValues($results)[0];
        $this->asname = $this->determineASNValues($results)[1];
      } catch(Exception $e) {
        echo 'Caught IXmapsIpInfo exception: ',  $e->getMessage();
      }
    }

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
    return $this->region;
  }

  public function getPostalCode() {
    return $this->postal;
  }

  public function getCountry() {
    return $this->countryCode;
  }

  public function getCountryCode() {
    return $this->countryCode;
  }

  public function getHostname() {
    return $this->hostname;
  }

  public function getASNum() {
    return $this->asnum;
  }

  public function getASName() {
    return $this->asname;
  }

  private function determineASNValues($results) {
    if (isset($results->org)) {
      $asnDetails = explode(" ", $results->org, 2);
      $asnum = substr($asnDetails[0], 2);
      $asname = $asnDetails[1];
      return [$asnum, $asname];
    }

    return [NULL, ''];
  }
}