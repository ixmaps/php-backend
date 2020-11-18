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


require_once ('../model/Geolocation.php');

if (isset($_POST['ip'])) {
  $ip = $_POST['ip'];
} else {
  $ip = $_SERVER['REMOTE_ADDR'];
  if ($ip = '127.0.0.1') {
    $ip = '128.101.101.101';
  }
}
// dirty check for localhost - throw in the MM default test value
// NB - this is broken

$geo = new Geolocation($ip);

echo '<h3>A Comparison of Data Sources</h3>';
echo '<div>This service provides a geolocation for the following data sources: IXmaps, Mamxind, IpInfo, IP2location</div>';
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
        if ($ip != '') {
        ?>

        var ixLatLng = new google.maps.LatLng(<?php echo $geo->getIXLat().','.$geo->getIXlong()?>);
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

        var mmLatLng = new google.maps.LatLng(<?php echo $geo->getMMLat().','.$geo->getMMlong()?>);
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

        var iiLatLng = new google.maps.LatLng(<?php echo $geo->getIILat().','.$geo->getIIlong()?>);
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

        var i2LatLng = new google.maps.LatLng(<?php echo $geo->getI2Lat().','.$geo->getI2long()?>);
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
      <div>
        <h4>IXmaps</h4>
        <div id="ix-map-canvas"></div>
        <div>
          <div>Lat: <?php echo $geo->getIXLat()?></div>
          <div>Long: <?php echo $geo->getIXLong()?></div>
          <div>City: <?php echo $geo->getIXCity()?></div>
          <div>Region: <?php echo $geo->getIXRegion()?></div>
          <div>Country: <?php echo $geo->getIXCountryCode()?></div>
          <div>Postal code: <?php echo $geo->getIXPostalCode()?></div>
          <div>ASnum: <?php echo $geo->getIXASNum()?></div>
          <div>ASname: <?php echo $geo->getIXASName()?></div>
          <div>Hostname: <?php echo $geo->getIXHostname()?></div>
        </div>
      </div>
      <div>
        <h4>Maxmind</h4>
        <div id="mm-map-canvas"></div>
        <div>
          <div>Lat: <?php echo $geo->getMMLat()?></div>
          <div>Long: <?php echo $geo->getMMLong()?></div>
          <div>City: <?php echo $geo->getMMCity()?></div>
          <div>Region: <?php echo $geo->getMMRegion()?></div>
          <div>Country: <?php echo $geo->getMMCountryCode()?></div>
          <div>Postal code: <?php echo $geo->getMMPostalCode()?></div>
          <div>ASnum: <?php echo $geo->getMMASNum()?></div>
          <div>ASname: <?php echo $geo->getMMASName()?></div>
          <div>Hostname: <?php echo $geo->getMMHostname()?></div>
        </div>
      </div>
      <div>
        <h4>IpInfo</h4>
        <div id="ii-map-canvas"></div>
        <div>
          <div>Lat: <?php echo $geo->getIILat()?></div>
          <div>Long: <?php echo $geo->getIILong()?></div>
          <div>City: <?php echo $geo->getIICity()?></div>
          <div>Region: <?php echo $geo->getIIRegion()?></div>
          <div>Country: <?php echo $geo->getIICountryCode()?></div>
          <div>Postal code: <?php echo $geo->getIIPostalCode()?></div>
          <div>ASnum: N/A</div>
          <div>ASname: N/A</div>
          <div>Hostname: N/A</div>
        </div>
      </div>
      <div>
        <h4>IP2Location</h4>
        <div id="i2-map-canvas"></div>
        <div>
          <div>Lat: <?php echo $geo->getI2Lat()?></div>
          <div>Long: <?php echo $geo->getI2Long()?></div>
          <div>City: <?php echo $geo->getI2City()?></div>
          <div>Region: <?php echo $geo->getI2Region()?></div>
          <div>Country: <?php echo $geo->getI2CountryCode()?></div>
          <div>Postal code: <?php echo $geo->getI2PostalCode()?></div>
          <div>ASnum: <?php echo $geo->getI2ASNum()?></div>
          <div>ASname: <?php echo $geo->getI2ASName()?></div>
          <div>Hostname: N/A</div>
        </div>
      </div>
    </div>
  </body>
</html>
