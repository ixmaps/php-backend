<?php
/**
 *
 * Repository for MaxMind geolocation - handles interaction with the MaxMind data objects (mmdbs)
 *
 * @author IXmaps.ca (Colin)
 * @since Nov 2020
 *
 */

require_once('../config.php');
require_once('../../vendor/autoload.php');
require_once('../model/Geolocation.php');
use GeoIp2\Database\Reader;

class MaxMindGeolocationRepository
{
  private $cityReader;
  private $asnReader;

  public function __construct()
  {
    global $MMDatDir;

    $this->geo = new Geolocation();

    try {
      $this->cityReader = new Reader($MMDatDir."/GeoLite2-City.mmdb");
      $this->asnReader = new Reader($MMDatDir."/GeoLite2-ASN.mmdb");
    } catch (Exception $e) {
      throw new Exception($e);
    }
  }

  /**
    * @param $ip string
    *
    * @return MM Geolocation object
    *
    */
  public function getByIp($ip)
  {
    try {
      $cityRecord = $this->cityReader->city($ip);
    } catch (Exception $e) {
      $cityRecord = NULL;
    }
    try {
      $asnRecord = $this->asnReader->asn($ip);
    } catch (Exception $e) {
      $asnRecord = NULL;
    }

    // we can admit defeat at this point...
    if (!$cityRecord && !$asnRecord) {
      return false;
    }
    // built in php function
    $hostname = gethostbyaddr($ip);

    return $this->hydrate($ip, $cityRecord, $asnRecord, $hostname);
  }

  /**
    * turns raw db data into a Geolocation object
    */
  private function hydrate($ip, $cityRecord, $asnRecord, $hostname)
  {
    $this->geo->setIp($ip);
    $this->geo->setHostname($hostname);
    $this->geo->setGeoSource("MaxMind");
    $this->geo->setAsnSource("MaxMind");

    if ($cityRecord) {
      $this->geo->setLat($cityRecord->location->latitude);
      $this->geo->setLong($cityRecord->location->longitude);
      $this->geo->setCity($cityRecord->city->name);
      $this->geo->setRegion($cityRecord->mostSpecificSubdivision->name);
      $this->geo->setRegionCode($cityRecord->mostSpecificSubdivision->isoCode);
      $this->geo->setCountry($cityRecord->country->name);
      $this->geo->setCountryCode($cityRecord->country->isoCode);
      $this->geo->setPostalCode($cityRecord->postal->code);
    }
    if ($asnRecord) {
      $this->geo->setASNum($asnRecord->autonomousSystemNumber);
      $this->geo->setASName($asnRecord->autonomousSystemOrganization);
    }

    return $this->geo;
  }

}