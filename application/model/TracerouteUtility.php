<?php
class TracerouteUtility
{
  /** Check if a set of hops is a CA-US-CA boomerang routes
    *
    * @param $hops is a set of hops of structure
    *          [
    *            {
    *              "hop_num": 1,
    *              "ip": 123.234.345.456,
    *              "rtt": 12.3
    *            },
    *            {
    *              "hop_num": 2,
    *              "ip": 987.876.765.654,
    *              "rtt": 34.5
    *            }
    *            ...
    *          ]
    *
    * @return boolean
    *
    */
  public static function checkIfBoomerang($hops) {
    global $mm;       // not sure this is best approach, please confirm

    $originateCA = false;
    $viaUS = false;
    $terminateCA = false;

    // NB: this currently only checks the MM object
    // Next job is to create a public function that checks IXmaps then MM then ?

    if ($mm->getGeoIp($hops[0]["ip"])["geoip"]["country_code"] == 'CA') {
      $originateCA = true;
    }

    foreach ($hops as $key => $hop) {
      $hopCountry = $mm->getGeoIp($hop["ip"])["geoip"]["country_code"];

      if ($hopCountry == 'US') {
        $viaUs = true;
      }

      if ($key == sizeof($hops) && $hopCountry == 'CA') {
        $terminateCA = true;
      }
    }

    if ($originateCA == true && $viaUS == true && $terminateCA == true) {
      return true;
    } else {
      return false;
    }
  }

}  // end of class
?>