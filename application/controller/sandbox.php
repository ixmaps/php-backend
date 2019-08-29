<?php
/**
 *
 * Updated for GeoLite2
 * This code demonstrates how to lookup the country, region, city,
 * postal code, latitude, and longitude by IP Address.
 *
 * Updated Aug 2019
 * @author Colin, Anto
 *
 */

require_once('../model/IXmapsMaxMind.php');

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

$mm = new IXmapsMaxMind($ip);

echo '<b>Values derived from GeoLite2-City.mmdb</b><br/>';
echo 'Lat: ' . $mm->getLat() . "<br/>";
echo 'Long: ' . $mm->getlong() . "<br/>";

echo 'City: ' . $mm->getCity() . "<br/>";
echo 'Region: ' . $mm->getRegion() . "<br/>";
echo 'Region code: ' . $mm->getRegionCode() . "<br/>";
echo 'Postal code: ' . $mm->getPostalCode() . "<br/>";
echo 'Country: ' . $mm->getCountry() . "<br/>";
echo 'Country code: ' . $mm->getCountryCode() . "<br/>";

echo 'ASN: ' . $mm->getASNum() . "<br/>";
echo 'ASN name: ' . $mm->getASName() . "<br/>";

echo '<br/><br/>';
?>

<!DOCTYPE html>
<html>
  <head>
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no">
    <meta charset="utf-8">
    <title>Locate IP</title>

    <STYLE type="text/css">
      #map_canvas {
        height: 500px;
        width: 680px;
      }
    </STYLE>

    <script src="../config.js"></script>
    <script language="JavaScript">

      function initialize() {
        <?php
        if ($ip != '') {
        ?>
        var myLatLng = new google.maps.LatLng(<?php echo $mm->getLat().','.$mm->getlong()?>);
        var mapOptions = {
          zoom: 6,
          center: myLatLng,
          mapTypeId: google.maps.MapTypeId.ROADMAP
        };
        var map = new google.maps.Map(document.getElementById('map_canvas'), mapOptions);
        var ip_ixmaps = new google.maps.LatLng(<?php echo $mm->getLat().','.$mm->getlong()?>);
        var marker_IXmaps = new google.maps.Marker({
          position: ip_ixmaps,
          map: map,
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
        IP: <input name="ip" type="text" value="<?php echo $ip;?>"/> <input type="submit" value="Geocode"/>
      </form>
    </div>
    <div id="map_canvas"></div>
    <p>
      This product includes GeoLite2 data created by MaxMind, available from
      <a href="https://www.maxmind.com">https://www.maxmind.com</a>.
    </p>
  </body>
</html>