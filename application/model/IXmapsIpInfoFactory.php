<?php
/**
 *
 * Factory for IXmapsIpInfo
 *
 * Created Nov 2020
 * @author IXmaps.ca (Colin)
 *
 */

class IXmapsIpInfoFactory
{
  public static function build($ip): IXmapsIpInfo
  {
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
      throw new Exception('Not a valid IP address');
    }

    // other exceptions to handle?

    $ii = new IXmapsIpInfo();
    $ii->hydrate($ip);
    return $ii;
  }

}