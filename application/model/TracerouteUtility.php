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
    $firstHop = new Geolocation($hops[0]["ip"]);
    $lastHop = new Geolocation(end($hops)["ip"]);
    // we called end on the array, so we need to reset the cursor
    reset($hops);

    // if first hop is not CA
    if ($firstHop->getCountry() != 'CA') {
      return false;
    }

    // if last hop is not CA
    if ($lastHop->getCountry() != 'CA') {
      return false;
    }

    foreach ($hops as $key => $hop) {
      $myHop = new Geolocation($hop["ip"]);
      if ($myHop->getCountry() == 'US') {
        return true;
      }
    }

    // if we get here, route has CA orig and CA dest but no US middle
    return false;
  }

}  // end of class
