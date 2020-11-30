<?php
/**
 *
 * Repository for MaxMind geolocation - handles interaction with the MaxMind data objects (mmdbs)
 *
 * @author IXmaps.ca (Colin)
 * @since Nov 2020
 *
 */

require_once('../model/MaxMindGeolocation.php');
require_once('../../vendor/autoload.php');
use GeoIp2\Database\Reader;

class MaxMindGeolocationRepository
{
  private $cityReader;
  private $asnReader;

  public function __construct()
  {
    // we could finally get rid of this global now, since this should be the only place that uses it
    global $MMDatDir;

    try {
      $this->cityReader = new Reader($MMDatDir."/GeoLite2-City.mmdb");
      $this->asnReader = new Reader($MMDatDir."/GeoLite2-ASN.mmdb");
    } catch (Exception $e) {
      throw new Exception($e);
    }
  }

  public function getByIp($ip)
  {
    try {
      $cityRecord = $this->cityReader->city($ip);
    } catch (Exception $e) {
      throw new Exception($e);
    }

    try {
      $asnRecord = $this->asnReader->asn($ip);
    } catch (Exception $e) {
      throw new Exception($e);
    }

    try {
      // built in php function
      $hostname = gethostbyaddr($ip);
    } catch (Exception $e) {
      throw new Exception($e);
    }

    return $this->hydrate($ip, $cityRecord, $asnRecord, $hostname);
  }

  /**
    * turns raw db data into a Geolocation object
    */
  private function hydrate($ip, $cityRecord, $asnRecord, $hostname)
  {
    $geo = new MaxMindGeolocation();
    $geo->setIp($ip);
    $geo->setLat($cityRecord->location->latitude);
    $geo->setLong($cityRecord->location->longitude);
    $geo->setCity($cityRecord->city->name);
    $geo->setRegion($cityRecord->mostSpecificSubdivision->name);
    $geo->setRegionCode($cityRecord->mostSpecificSubdivision->isoCode);
    $geo->setCountry($cityRecord->country->name);
    $geo->setCountryCode($cityRecord->country->isoCode);
    $geo->setPostalCode($cityRecord->postal->code);
    $geo->setASNum($asnRecord->autonomousSystemNumber);
    $geo->setASName($asnRecord->autonomousSystemOrganization);
    $geo->setHostname($hostname);

    return $geo;

  }
}