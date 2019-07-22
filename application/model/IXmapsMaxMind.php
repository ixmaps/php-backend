<?php
class IXmapsMaxMind
{
  private $MM_dat_dir = "";
  private $MM_geoip_dir = "";
  private $giasn;
  private $gi1;
  /**
   *
   */
  function __construct(){
    global $MM_dat_dir, $MM_geoip_dir;
    $this->MM_dat_dir = $MM_dat_dir;
    $this->MM_geoip_dir = $MM_geoip_dir;
    $this->loadGeoIpIncFiles();
    $this->giasn = geoip_open($this->MM_dat_dir."/GeoIPASNum.dat", GEOIP_STANDARD);
    $this->gi1 = geoip_open($this->MM_dat_dir."/GeoLiteCity.dat", GEOIP_STANDARD);
  }
  
  public function loadGeoIpIncFiles() {
    // load MM dat files
    include($this->MM_geoip_dir."/geoip.inc");
    include($this->MM_geoip_dir."/geoipcity.inc");
    include($this->MM_geoip_dir."/geoipregionvars.php");
  }

  /**
   * Get Geo IP and ASN data from MaxMind local files
   * @param string $ip
   */
  public function getGeoIp($ip) {
    $this->geoIp = geoip_record_by_addr($this->gi1, $ip);

    if(isset($this->geoIp->city) && $this->geoIp->city!=""){
      $this->geoIp->city = mb_convert_encoding($this->geoIp->city, "UTF-8", "iso-8859-1");
    }

    $r = array(
      "ip"=>$ip,
      "geoip"=>(array)$this->geoIp,
      "asn"=>NULL,
      "isp"=>NULL,
      "hostname"=>gethostbyaddr($ip)
    );
    $ipAsn = geoip_name_by_addr($this->giasn, $ip);
    if($ipAsn!=NULL){
      $asn_isp = $this->extractAsn($ipAsn);
      $r['asn'] = $asn_isp[0];
      $r['isp'] = $asn_isp[1];
    }

    return $r;
  }


  /**
   * Return country of IP
   * @param string $ip
   */
  public function getMMCountry($ip) {
    $mmRecord = geoip_record_by_addr($this->gi1, $ip);
    return $mmRecord->country_code;
  }


  /**
    Close MM dat files. Use this after all transactions are completed
  */
  public function closeDatFiles(){
    geoip_close($this->gi1);
    geoip_close($this->giasn);
  }

  /**
    Parse asn and isp from MM data string
  */
  private function extractAsn($asnString) {
    $asnArray = explode(' ', $asnString);
    if(isset($asnArray[0])){
      $asn = $asnArray[0];
      $asn = substr($asn, 2);
      $isp = "";

      for ($i=1; $i < count($asnArray); $i++) {
        $isp .= $asnArray[$i]." ";

      }
      $isp = trim($isp);
    } else {
      $asn = "";
      $isp = "";
    }
    return array($asn, $isp);
  }

  /**
    Get Closest Geo Data using mm world_cities DB Based on city population and radius
  */
  public function getGeoDataByPopulationRadius($currentMmData, $limit=1, $radius=50000)
  {
    global $dbconn;

    // Get closest geodata for lat/long
    $sql = "SELECT country, name, admin1, population, latitude, longitude FROM geoname WHERE population is not null and ST_DWithin(the_geom, ST_SetSRID(ST_MakePoint(".$currentMmData['geoip']['longitude'].",".$currentMmData['geoip']['latitude']."), 4326), $radius) and country = '".$currentMmData['geoip']['country_code']."' ORDER BY population DESC limit $limit;";
    $result = pg_query($dbconn, $sql) or die('getGeoDataRadius failed'.pg_last_error());
    $geodata = pg_fetch_all($result);
    return $geodata;
  }
}
?>