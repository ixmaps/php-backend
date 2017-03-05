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
		$this->gi1 = geoip_open($this->MM_dat_dir."/GeoLiteCity.dat",GEOIP_STANDARD);
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
		$this->geoIp = geoip_record_by_addr($this->gi1,$ip);
		//echo "\n"."getGeoIp()"."\n";
		//var_dump($this->geoIp);
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
		/*echo "\n geoip_name_by_addr:\n";
		var_dump($ipAsn);*/
		if($ipAsn!=NULL){
			$asn_isp = $this->extractAsn($ipAsn);
			$r['asn'] = $asn_isp[0];
			$r['isp'] = $asn_isp[1];
		}
		return $r;
	}
	
	/**
	 * Close MM dat files. Use this after all transactions are completed 
	 */
	public function closeDatFiles(){
		geoip_close($this->gi1);
		geoip_close($this->giasn);
	}

	/**
	 * Parse asn and isp from MM data string
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
	* Update IP address information: geo data, asn, and hostname
	*/
	public function updateIpAddrInfo($data, $ip)
	{
		global $dbconn;
		print_r($data);
		
		$sql = "UPDATE ip_addr_info SET ";
		
		if (isset($data['latitude']) && isset($data['longitude'])){
			$sql.="mm_lat=".$data['latitude'].", lat=".$data['latitude'].", mm_long=".$data['longitude'].", long=".$data['longitude'];
		}
		if ($data['country_code']!=""){
			$sql.=", mm_country='".$data['country_code']."'";

		}
		if ($data['region']!=""){
			$sql.=", mm_region='".$data['region']."'";
		}
		if ($data['city']!=""){
			$sql.=", mm_city='".$data['city']."'";

		}
		if ($data['postal_code']!=""){
			$sql.=", mm_postal='".$data['postal_code']."'";

		}
		if ($data['area_code']!=""){
			$sql.=", mm_area_code=".$data['area_code']."";

		}
		if ($data['dma_code']!=""){
			$sql.=", mm_dma_code=".$data['dma_code']."";

		}
		// set hostname
		$sql.=", hostname='".$data['hostname']."'";

		$sql.=" WHERE ip_addr = '".$ip."' AND gl_override is NULL";

		echo "\n".$sql;
		$result = pg_query($dbconn, $sql) or die('updateIpAddrInfo: Query failed'.pg_last_error());
		//$dataA = pg_fetch_all($result);
		//pg_free_result($result);
		//return $dataA;
	}
}
?>