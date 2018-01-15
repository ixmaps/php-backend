<?php
include("../config.php");
include('../model/IXmapsMaxMind.php');
include('../model/Geolocation.php');
$mm = new IXmapsMaxMind();

/*
*****************
   Class Tests
*****************
*/

// exists in IXmaps and gl_override is not null
$a = new Geolocation('4.35.108.230');
//$a = new Geolocation('64.230.161.138');

// exists in IXmaps and gl_override is null
//$a = new Geolocation('23.208.228.138');

// does not exist in IXmaps
//$a = new Geolocation('70.67.160.1');

// // does not exist anywhere
//$a = new Geolocation('127.0.0.1');


//$a = new Geolocation('64.230.79.90');


//$a = new Geolocation('10.178.206.152');

print_r($a);
?>