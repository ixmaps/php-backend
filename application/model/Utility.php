<?php
/**
 *
 * Misc utilities (eg not related to geolocation, traceroutes, etc)
 *
 * @author IXmaps.ca (Colin)
 * @since Nov 2020
 *
 */

require_once('../config.php');

class Utility {

  public function getNow() {
    global $timezone;
    $tz = new DateTimeZone($timezone);
    $now = new DateTime();
    return $now->setTimezone($tz);
  }

} // end of class


