<?php
/**
 *
 * Class to handle interaction with the MaxMind data objects (mmdbs)
 * Entire rewrite to handle GeoLite2 and use proper getters instead of
 * implicit MM object getting passed around to various other classes
 *
 * Updated Aug 2019
 * @author IXmaps.ca (Colin)
 *
 */
require_once('../config.php');
require_once('../../vendor/autoload.php');
use ipinfo\ipinfo\IPinfo;

$access_token = '61a406bb0bae69';
$client = new IPinfo($access_token);

$ip_address = '216.239.36.21';
$details = $client->getDetails($ip_address);

echo $details->city;