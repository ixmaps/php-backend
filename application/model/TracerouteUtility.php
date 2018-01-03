<?php
class TracerouteUtility
{

  /**
    * Check if an ip exists in DB
    *
    * @param $ip address of type ???
    *
    * @return boolean
    *
    */
  public static function checkIpExists($ip)
  {
    // global $dbconn;

    // // START HERE: ip needs to be cast to... something? not float (check how it works elsewhere)
    // // perhaps int ip2long ( string $ip_address )

    // $sql = "SELECT ip_addr_info.ip_addr FROM ip_addr_info WHERE ip_addr = '".$ip."'";
    // // look up pg_query_params, much safer

    // // add error handling that is consistent with PTR approach
    // $result = pg_query($dbconn, $sql) or die('checkIpExists: Query failed'.pg_last_error());
    // $ip_addr = pg_fetch_all($result);

    // if ($ip_addr) {
    //   return true;
    // }

    // pg_free_result($result);

    return false;
  }
  // public static function checkIpExists($ip)
  // {
  //   global $dbconn;

  //   /* TODO: Check is a valid ip: a bit redundant?*/
  //   $sql = "SELECT ip_addr_info.ip_addr FROM ip_addr_info WHERE ip_addr = '".$ip."'";

  //   $result = pg_query($dbconn, $sql) or die('checkIpExists: Query failed'.pg_last_error());
  //   $dataA = pg_fetch_all($result);
  //   pg_free_result($result);
  //   return $dataA;
  // }



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

    // NB: this currently only checks the MM object
    // Next job is to create a public function that checks IXmaps then MM then ?

    // if first hop is not CA
    if ($mm->getMMCountry($hops[0]["ip"]) != 'CA') {
      return false;
    }

    // if last hop is not CA
    if ($mm->getMMCountry(end($hops)["ip"]) != 'CA') {
      return false;
    }

    // we called end on the array, so we need to reset the cursor
    reset($hops);

    foreach ($hops as $key => $hop) {
      if ($mm->getMMCountry($hop["ip"]) == 'US') {
        return true;
      }
    }

    // if we get here, route has CA orig and CA dest
    return false;
  }

}  // end of class
