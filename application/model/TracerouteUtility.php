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
    $hops_valid_ip = array();
    foreach ($hops as $key1 => $hop1) {
        //if(is_null($hop1['ip'])){
        if($hop1['ip']!=""){
            //unset($hops[$key1]);
            $hops_valid_ip[]=$hop1;
            //print_r($hop1);
        }
    }
    //print_r($hops);
    $firstHop = new Geolocation($hops_valid_ip[0]["ip"]);
    $lastHop = new Geolocation(end($hops_valid_ip)["ip"]);
    // we called end on the array, so we need to reset the cursor
    reset($hops_valid_ip);

    // if first hop is not CA
    if ($firstHop->getCountry() != 'CA') {
      return false;
    }

    // if last hop is not CA
    if ($lastHop->getCountry() != 'CA') {
      return false;
    }

    foreach ($hops_valid_ip as $key => $hop) {
      $myHop = new Geolocation($hop["ip"]);
      if ($myHop->getCountry() == 'US') {
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
