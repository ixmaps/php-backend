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


// Data stored in ~/ix-data/mm-data
require_once('../model/IXmapsMaxMind.php');
// country seems useless - maybe don't dl it?
// $reader = new Reader($MM_dat_dir."/GeoLite2-Country.mmdb");
// $record = $reader->country('128.101.101.101');

// $reader = new Reader($MM_dat_dir."/GeoLite2-City.mmdb");
// $record = $reader->city('128.101.101.101');

// print($record->country->isoCode . "<br/>"); // 'US'
// print($record->country->name . "<br/>"); // 'United States'
// print($record->country->names['zh-CN'] . "<br/>"); // '美国'

// print($record->mostSpecificSubdivision->name . "<br/>"); // 'Minnesota'
// print($record->mostSpecificSubdivision->isoCode . "<br/>"); // 'MN'

// print($record->city->name . "<br/>"); // 'Minneapolis'

// print($record->postal->code . "<br/>"); // '55455'

// print($record->location->latitude . "<br/>"); // 44.9733
// print($record->location->longitude . "<br/>"); // -93.2323

// // do we need this?
// unset($reader);

// $reader = new Reader($MM_dat_dir."/GeoLite2-ASN.mmdb");
// $record = $reader->asn('1.128.0.0');
// print($record->autonomousSystemNumber . "<br/>");
// print($record->autonomousSystemOrganization . "<br/>");


if(isset($_POST['ip'])) {
  $ip=$_POST['ip'];
} else {
  $ip=$_SERVER['REMOTE_ADDR'];
  if($ip = '127.0.0.1') {
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

    <script src="https://maps.googleapis.com/maps/api/js"></script>
    <script language="JavaScript">

      function initialize() {
        <?php
        if($ip!='')
        {
        ?>
        var myLatLng = new google.maps.LatLng(<?php echo $lat.','.$long;?>);
        var mapOptions = {
          zoom: 6,
          center: myLatLng,
          mapTypeId: google.maps.MapTypeId.ROADMAP
        };
        var map = new google.maps.Map(document.getElementById('map_canvas'), mapOptions);
        var ip_IXmaps = new google.maps.LatLng(<?php echo $lat.','.$long;?>);
        var marker_IXmaps = new google.maps.Marker({
          position: ip_IXmaps,
          map: map,
          title:'<?php echo $ip;?>'
        });
      <?php } ?>
      }
      // google.maps.event.addDomListener(window, 'load', initialize);
    </script>
  </head>
  <body>
    <div>
      <form action="index.php" method="post">
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
