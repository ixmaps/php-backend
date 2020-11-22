<?php
class TracerouteUtility
{
  /**
    * Check if a set of hops is a CA-US-CA boomerang routes
    *
    * @param
    * $hops is a set of hops of structure
    *   [
    *       {
    *         "num": 1,
    *         "ip": "162.219.49.1",
    *         "rtts": ["0.251", "0.281", "0.331"]
    *       },
    *       {
    *         "num": 2,
    *         "ip": "184.105.64.89",
    *         "rtts": ["14.703", "17.144", "19.620"]
    *       },
    *       ...
    *   ]
    *
    * @return boolean
    *
    */
  public static function checkIfBoomerang($hops) {
    // unset hops with no response
    $cleanedHops = array();
    foreach ($hops as $key1 => $hop1) {
      if($hop1['ip']!=""){
        $cleanedHops[]=$hop1;
      }
    }

    $firstHop = new Geolocation($cleanedHops[0]["ip"]);
    $lastHop = new Geolocation(end($cleanedHops)["ip"]);
    // we called end on the array, so we need to reset the cursor
    reset($cleanedHops);

    // if first hop is not CA
    if ($firstHop->getCountryCode() != 'CA') {
      return false;
    }

    // if last hop is not CA
    if ($lastHop->getCountryCode() != 'CA') {
      return false;
    }

    foreach ($cleanedHops as $key => $hop) {
      $myHop = new Geolocation($hop["ip"]);
      if ($myHop->getCountryCode() == 'US') {
        return true;
      }
    }

    // if we get to this point, route has CA orig and CA dest but no US middle
    return false;
  }


  /**
    * Check if a set of hops completes
    * Completing is currently defined as last_hop = destination
    *
    * @param $hops, $destIp
    * $hops is a set of hops of structure
    *   [
    *       {
    *         "num": 1,
    *         "ip": "162.219.49.1",
    *         "rtts": ["0.251", "0.281", "0.331"]
    *       },
    *       {
    *         "num": 2,
    *         "ip": "184.105.64.89",
    *         "rtts": ["14.703", "17.144", "19.620"]
    *       },
    *       ...
    *   ]
    *
    * @return boolean
    *
    */
  public static function checkIfCompleted($hops, $destIp) {
    // if first hop is not CA
    if (end($hops)["ip"] == $destIp) {
      return true;
    }
    return false;
  }

}  // end of class
