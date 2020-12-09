<?php
/**
 *
 * This code demonstrates how to lookup the country, region, city,
 * postal code, latitude, and longitude by IP Address.
 * Current data sources: ix, mm, ii, i2
 *
 * Updated Nov 2020
 * @author Colin, Anto
 *
 */

require_once('../services/IXmapsGeolocationService.php');
require_once('../services/MaxMindGeolocationService.php');
require_once('../services/IPInfoGeolocationService.php');
require_once('../services/IP2LocationGeolocationService.php');
require_once('../repositories/IXmapsGeolocationRepository.php');
require_once('../repositories/MaxMindGeolocationRepository.php');
require_once('../repositories/IPInfoGeolocationRepository.php');
require_once('../repositories/IP2LocationGeolocationRepository.php');

if (isset($_POST['ip'])) {
  $ip = $_POST['ip'];
} else {
  $ip = $_SERVER['REMOTE_ADDR'];
  if ($ip = '127.0.0.1') {
    $ip = '128.101.101.101';
  }
}

$IXgeoRepo = new IXmapsGeolocationRepository();
$IXgeoService = new IXmapsGeolocationService($IXgeoRepo);

$MMgeoRepo = new MaxMindGeolocationRepository();
$MMgeoService = new MaxMindGeolocationService($MMgeoRepo);

$IIgeoRepo = new IPInfoGeolocationRepository();
$IIgeoService = new IPInfoGeolocationService($IIgeoRepo);

$I2geoRepo = new IP2LocationGeolocationRepository();
$I2geoService = new IP2LocationGeolocationService($I2geoRepo);


// we only need to try / catch the first one
try {
  $ixgeo = $IXgeoService->getByIp($ip);
} catch (Exception $e) {
  echo '<h3>Invalid IP address</h3>'; die;
}
$mmgeo = $MMgeoService->getByIp($ip);
$iigeo = $IIgeoService->getByIp($ip);
$i2geo = $I2geoService->getByIp($ip);


echo '<h3>A Comparison of Data Sources</h3>';
echo '<div>This service provides a geolocation for the following data sources: IXmaps, Mamxind, IpInfo, IP2location. Maps and location data shown only IP exists in data source.</div>';
echo '<br>';

?>

<!DOCTYPE html>
<html>
  <head>
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no">
    <meta charset="utf-8">
    <title>Locate IP</title>

    <STYLE type="text/css">
      body {
        margin: 25px;
      }
      #ix-map-canvas, #mm-map-canvas, #ii-map-canvas, #i2-map-canvas {
        height: 400px;
        width: 400px;
        display: inline-block;
        margin-right: 25px;
        margin-bottom: 10px;
      }
      .sources-container {
        margin-top: 30px;
        display: inline-flex;
      }
    </STYLE>

    <script src="../config.js"></script>
    <script language="JavaScript">

      function initialize() {
        <?php
        if ($ixgeo) {
        ?>
        var ixLatLng = new google.maps.LatLng(<?php echo $ixgeo->getLat().','.$ixgeo->getlong()?>);
        var ixMapOptions = {
          zoom: 5,
          center: ixLatLng,
          mapTypeId: google.maps.MapTypeId.ROADMAP
        };
        var ixMap = new google.maps.Map(document.getElementById('ix-map-canvas'), ixMapOptions);
        var ixMarker = new google.maps.Marker({
          position: ixLatLng,
          map: ixMap,
          title:'<?php echo $ip;?>'
        });
        <?php } ?>

        <?php
        if ($mmgeo) {
        ?>
        var mmLatLng = new google.maps.LatLng(<?php echo $mmgeo->getLat().','.$mmgeo->getlong()?>);
        var mmMapOptions = {
          zoom: 5,
          center: mmLatLng,
          mapTypeId: google.maps.MapTypeId.ROADMAP
        };
        var mmMap = new google.maps.Map(document.getElementById('mm-map-canvas'), mmMapOptions);
        var mmMarker = new google.maps.Marker({
          position: mmLatLng,
          map: mmMap,
          title:'<?php echo $ip;?>'
        });
        <?php } ?>

        <?php
        if ($iigeo) {
        ?>
        var iiLatLng = new google.maps.LatLng(<?php echo $iigeo->getLat().','.$iigeo->getlong()?>);
        var iiMapOptions = {
          zoom: 5,
          center: iiLatLng,
          mapTypeId: google.maps.MapTypeId.ROADMAP
        };
        var iiMap = new google.maps.Map(document.getElementById('ii-map-canvas'), iiMapOptions);
        var iiMarker = new google.maps.Marker({
          position: iiLatLng,
          map: iiMap,
          title:'<?php echo $ip;?>'
        });
        <?php } ?>

        <?php
        if ($i2geo) {
        ?>
        var i2LatLng = new google.maps.LatLng(<?php echo $i2geo->getLat().','.$i2geo->getlong()?>);
        var i2MapOptions = {
          zoom: 5,
          center: i2LatLng,
          mapTypeId: google.maps.MapTypeId.ROADMAP
        };
        var i2Map = new google.maps.Map(document.getElementById('i2-map-canvas'), i2MapOptions);
        var i2Marker = new google.maps.Marker({
          position: i2LatLng,
          map: i2Map,
          title:'<?php echo $ip;?>'
        });
        <?php } ?>

      }

      var scriptEl = document.createElement('script');
      scriptEl.type = 'text/javascript';
      scriptEl.src = 'https://maps.google.com/maps/api/js?v=3&libraries=geometry&key='+config.gmaps.key+'&callback=initialize';
      document.body.appendChild(scriptEl);
    </script>
  </head>
  <body>
    <div>
      <form action="sandbox.php" method="post">
        Enter IP here: <input name="ip" type="text" value="<?php echo $ip;?>"/> <input type="submit" value="Geocode"/>
      </form>
    </div>
    <div class="sources-container">
      <?php
      if ($ixgeo) {
      ?>
      <div>
        <h4>IXmaps</h4>
        <div id="ix-map-canvas"></div>
        <div>
          <div>Lat: <?php echo $ixgeo->getLat()?></div>
          <div>Long: <?php echo $ixgeo->getLong()?></div>
          <div>City: <?php echo $ixgeo->getCity()?></div>
          <div>Region: <?php echo $ixgeo->getRegion()?></div>
          <div>Country: <?php echo $ixgeo->getCountry()?></div>
          <div>Postal code: <?php echo $ixgeo->getPostalCode()?></div>
          <div>ASnum: <?php echo $ixgeo->getASNum()?></div>
          <div>ASname: <?php echo $ixgeo->getASName()?></div>
          <div>Hostname: <?php echo $ixgeo->getHostname()?></div>
        </div>
      </div>
      <?php
      }
      if ($mmgeo) {
      ?>
      <div>
        <h4>Maxmind</h4>
        <div id="mm-map-canvas"></div>
        <div>
          <div>Lat: <?php echo $mmgeo->getLat()?></div>
          <div>Long: <?php echo $mmgeo->getLong()?></div>
          <div>City: <?php echo $mmgeo->getCity()?></div>
          <div>Region: <?php echo $mmgeo->getRegion()?></div>
          <div>Country: <?php echo $mmgeo->getCountry()?></div>
          <div>Postal code: <?php echo $mmgeo->getPostalCode()?></div>
          <div>ASnum: <?php echo $mmgeo->getASNum()?></div>
          <div>ASname: <?php echo $mmgeo->getASName()?></div>
          <div>Hostname: <?php echo $mmgeo->getHostname()?></div>
        </div>
      </div>
      <?php
      }
      if ($iigeo) {
      ?>
      <div>
        <h4>IpInfo</h4>
        <div id="ii-map-canvas"></div>
        <div>
          <div>Lat: <?php echo $iigeo->getLat()?></div>
          <div>Long: <?php echo $iigeo->getLong()?></div>
          <div>City: <?php echo $iigeo->getCity()?></div>
          <div>Region: <?php echo $iigeo->getRegion()?></div>
          <div>Country: <?php echo $iigeo->getCountry()?></div>
          <div>Postal code: <?php echo $iigeo->getPostalCode()?></div>
          <div>ASnum: <?php echo $iigeo->getASNum()?></div>
          <div>ASname: <?php echo $iigeo->getASName()?></div>
          <div>Hostname: <?php echo $iigeo->getHostname()?></div>
        </div>
      </div>
      <?php
      }
      if ($i2geo) {
      ?>
      <div>
        <h4>IP2Location</h4>
        <div id="i2-map-canvas"></div>
        <div>
          <div>Lat: <?php echo $i2geo->getLat()?></div>
          <div>Long: <?php echo $i2geo->getLong()?></div>
          <div>City: <?php echo $i2geo->getCity()?></div>
          <div>Region: <?php echo $i2geo->getRegion()?></div>
          <div>Country: <?php echo $i2geo->getCountry()?></div>
          <div>Postal code: <?php echo $i2geo->getPostalCode()?></div>
          <div>ASnum: <?php echo $i2geo->getASNum()?></div>
          <div>ASname: <?php echo $i2geo->getASName()?></div>
          <div>Hostname: N/A</div>
        </div>
      </div>
      <?php } ?>
    </div>
  </body>
</html>
