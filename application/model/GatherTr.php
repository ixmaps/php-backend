<?php
class GatherTr
{
	/**
		Extract ASN
	*/
	public static function extractAsn($asnString){
		$asnArray = explode(' ', $asnString);
		$asn = $asnArray[0];
		$asn = substr($asn, 2);
		$isp = "";

		//AS5645 TekSavvy Solutions, Inc.

		for ($i=1; $i < count($asnArray); $i++) { 
			$isp .= $asnArray[$i]." ";
	
		}
		$isp = trim($isp);
		return array($asn, $isp);
	}

	/**
		Anonymize ip
	*/
	public static function anonymizeIp($ip){
		$ipQuads = explode('.', $ip);
		$ipAmonim = "";

		for ($i=0; $i < count($ipQuads); $i++) { 
			if($i==count($ipQuads)-1){
				$ipAmonim.=".0";

			} else if ($i==0) {
				$ipAmonim.= "".$ipQuads[$i];

			} else {
				$ipAmonim.= ".".$ipQuads[$i];
			}
		}
		return $ipAmonim;
	}

	/**
		Save incomming traceroute data (Header)
	*/
	public static function saveTrContribution($data) 
	{
		//print_r($data);
		global $dbconn, $ixmaps_debug_mode, $pg_error;


		$data['submitter_ip'] = GatherTr::anonymizeIp($data['submitter_ip']);

		$sql = "INSERT INTO tr_contributions (traceroute_id, sub_time, dest, dest_ip, city, country, submitter, submitter_ip, submitter_os, postal_code, privacy, timeout, queries, maxhops, tr_flag, error_log, client_params, submitter_asnum, metadata) VALUES (NULL, NOW(), $1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17) RETURNING tr_c_id;";

		if(!isset($data['error'])){
			$data['error'] = "";
		}
		if(!isset($data['client_params'])){
			$data['client_params'] = "";
		}
		if(!isset($data['metadata'])){
			$data['metadata'] = "";
		}

		// trim submitter name if longer than 25 characters
		if(strlen($data['submitter'])>25){
			$data['submitter']=mb_strimwidth($data['submitter'], 0, 25, "...");
		}

		// trim postal_code field char limit = 10
		if(strlen($data['postal_code'])>10){
			$data['postal_code'] = substr($data['postal_code'],0,10);
		}
		
		$trData = array($data['dest'], $data['dest_ip'], $data['city'], $data['country'], $data['submitter'], $data['submitter_ip'], $data['os'], $data['postal_code'], $data['privacy'], $data['timeout'], $data['queries'], $data['maxhops'], 0, $data['error'], $data['client_params'], $data['submitter_asnum'], $data['metadata']);

		//$result = pg_query_params($dbconn, $sql, $trData) or die('saveTrContribution: Query failed: incorrect parameters: '.pg_last_error());
		$result = pg_query_params($dbconn, $sql, $trData);

		// catch errors
		if ($result === false) {
			$pg_error="saveTrContribution: Incorrect parameters: ".pg_last_error();
			$tr_c_id=0;
		} else {
			$pg_error="";
			//$result = pg_query($dbconn, $sql1) or die('saveContribution: Query failed: incorrect parameters'.pg_last_error());
			$lastId = pg_fetch_all($result);
			$tr_c_id = $lastId[0]['tr_c_id'];
			pg_free_result($result);
		}

		return array(
			"error" => $pg_error,
			"tr_c_id"=>$tr_c_id);

	}

	/**
		Save incomming traceroute data (Contributions)
	*/
	public static function saveTrContributionData($data, $tr_c_id) 
	{
		global $dbconn, $ixmaps_debug_mode;

		foreach ($data['traceroute_submissions'] as $key => $trDataItem) {

			if(is_array($trDataItem['tr_data'])){				
				$trDataItem['tr_data'] = json_encode($trDataItem['tr_data']);
				$trDataItem['data_type'] = "json";
			} else {
				$trDataItem['data_type'] = "txt";
			}

			$sql = "INSERT INTO tr_contribution_data (tr_c_id, sub_time, client, protocol, data_type, tr_invocation, tr_data, tr_flag) VALUES (".$tr_c_id.", NOW(), $1, $2, $3, $4, $5, $6)";
			
			if(!isset($trDataItem['tr_invocation'])){
				$trDataItem['tr_invocation'] = '';
			}
			$trData = array($trDataItem['client'], $trDataItem['protocol'], $trDataItem['data_type'],$trDataItem['tr_invocation'], $trDataItem['tr_data'], 0);

			$result = pg_query_params($dbconn, $sql, $trData) or die('saveContributionData: Query failed: incorrect parameters'.pg_last_error());
		}

		pg_free_result($result);

		return 1;

	}

	/**
		Get TR contribution
	*/
	public static function getTrContribution($tr_c_id, $trId=0) 
	{
		global $dbconn, $ixmaps_debug_mode;

		// Find tr_c_id for a trId
		if($trId!=0){
			$sql1 = "SELECT tr_contributions.* FROM tr_contributions WHERE traceroute_id=$1;";
			$sql2 = "SELECT tr_contribution_data.* FROM tr_contribution_data WHERE tr_c_id=$1;";
			
			$sqlParams1 = array($trId);
			$result1 = pg_query_params($dbconn, $sql1, $sqlParams1) or die('getTrContribution: tr_contributions failed');
			$dataArr1 = pg_fetch_all($result1);
			$tr_c_id = $dataArr1[0]['tr_c_id'];
			$sqlParams2 = array($tr_c_id);
			$result2 = pg_query_params($dbconn, $sql2, $sqlParams2) or die('getTrContribution: tr_contribution_data failed');
			$dataArr2 = pg_fetch_all($result2);

		} else {
			$sql1 = "SELECT tr_contributions.* FROM tr_contributions WHERE tr_c_id=$1;";
			$sql2 = "SELECT tr_contribution_data.* FROM tr_contribution_data WHERE tr_c_id=$1;";
			$sqlParams1 = array($tr_c_id);
			$sqlParams2 = array($tr_c_id);
			$result1 = pg_query_params($dbconn, $sql1, $sqlParams1) or die('getTrContribution: tr_contributions failed');
			$result2 = pg_query_params($dbconn, $sql2, $sqlParams2) or die('getTrContribution: tr_contribution_data failed');
			$dataArr1 = pg_fetch_all($result1);
			$dataArr2 = pg_fetch_all($result2);
		}
		
		$dataArr1[0]['traceroute_submissions'] = $dataArr2;

		pg_free_result($result1);
		pg_free_result($result2);
		
		return $dataArr1[0];
	}


	/**
		Check if hops in all passes are exactly the same and in the same sequence
	*/
	public static function analyzePassesExactMatch($TrByHop)
	{
		$hopsDifIps = array();

		//hop
		foreach ($TrByHop as $key => $hop) {
			//print_r($hop);
			$conn = 0;
			$ip_current = "";
			$ip_last = "";
			$exactMatch = true;

			// passes [ip/latency]
			foreach ($hop as $key => $pass) {
				//print_r($pass);
				if(isset($pass['ip'])){
					//echo "\n[".$pass['pass']."] ".$pass['ip'];
					// first ip
					if($conn==0){
						$ip_current = $pass['ip'];
					} else {
						$ip_current = $pass['ip'];
						if($ip_last!=$ip_current && $exactMatch){
							$exactMatch = false;
							//echo "\n Diff ips at hop: ".$pass['hop'];
							$hopsDifIps[$pass['hop']] = $pass['pass'];
							$exactMatch = false;
						}
					}
					$ip_last = $ip_current;
					$conn++;					
				}
			}
		}
		return $hopsDifIps;
	}


	/**
		Format TR data in an array of [hops][latencies]
		Assumes that the data is structured as follows: [tr_data][pass][hop][query]
	*/
	public static function formatTrData($data) 
	{
		$TrByHop = array();

		// submissions
		foreach ($data['traceroute_submissions'] as $key1 => $submission) {

			// check contribution type
			if($submission['data_type']=='json'){
				$trDataPasses = json_decode($submission['tr_data'], true); 
				
				// passes
				foreach ($trDataPasses as $key2 => $trPass) {

					// hops
					foreach ($trPass as $key3 => $passHop) {
						//collect hops and latencies from all passes
						$TrByHop[$passHop['hop']][]=$passHop;

					} // end hops
			
				} //end passes
				//echo '\n\n';
				//print_r($TrByHop);

				// testing exact match
				//$a = GatherTr::analyzePassesExactMatch($TrByHop);
				return $TrByHop;

			} else if($submission['data_type']=='txt'){
				//echo "\nis txt";
				//$data = analyzeRawTracerouteTxt(); (Not implemented)
				return 0;

			} else {
				return 0;	
			}
			
		}

	}

	/**
		Analyze TR data. 
		This function assumes that the tr data is structured as follows:
		[tr_data][hop][queries]
	*/
	public static function selectBestIp($trHops) 
	{
		$TR = array();
		$flag = 0;

		//hops
		foreach ($trHops as $key2 => $hop) {
			$hopNum = $key2;
			$latencies = array();
			$ip_rank = array();

			// queries/passes
			foreach ($hop as $key3 => $hopPass) {
				
				//$hopPass['rtt'] = $hopPass['rtt']*100;
				$latencies[] = $hopPass['rtt'];
				
				// prevent ip !set
				if(isset($hopPass['ip'])){
					$ip_latencies[$hopPass['ip']][]  = $hopPass['rtt'];
					if(!isset($ip_rank[$hopPass['ip']]))
					{
						$ip_rank[$hopPass['ip']] = 1;	
					} else {
						$ip_rank[$hopPass['ip']] += 1;
					}
				} else {
					// error at this hop
				}

			} // end queries

			// Complete a max of 4 latencies
			$totLatencies = count($latencies);
			if($totLatencies<4){
				for ($i=$totLatencies; $i < 4; $i++) { 
					$latencies[]=-1;
				}
			}

			sort($latencies);
			arsort($ip_rank);					
			$keys=array_keys($ip_rank);
			
			if(isset($keys[0])){
				$winnerIp = $keys[0];
			} else {
				$winnerIp = "";
			}

			/*TODO: more than 4 queries/attepts in "official" tables needes further examination*/

			/*Preventing the submission of more that 4 attempts/queries bacause it brakes TR details page
			Note: this needs further discussion. In this case submitting the lowest 4 latencies. */
/*			$totQueries = count($latencies);
			if($totQueries>4) {
				for($i=4; $i < $totQueries; $i++){
					if(isset($latencies[$i])){
						unset($latencies[$i]);
					}
				}
			}*/

			//echo "\nWinner IP: ".$winnerIp;

			$TR['hops'][$hopNum]['latencies'] = $latencies;
			$TR['hops'][$hopNum]['winIp'] = $winnerIp;
			
		} //end hop
		//print_r($TR);
		//$data['ip_analysis'] = $TR;

		return $TR;

	}
	/**
		Analyze TR data. 
		This function assumes that the tr data is structured as follows:
		[tr_data][hop][queries]
	*/
	public static function selectBestIpOld($tr_c_id) 
	{
		global $dbconn;
		$data = GatherTr::getTrContribution($tr_c_id);
		//print_r($data);

		$TR = array();
		// submissions
		foreach ($data['traceroute_submissions'] as $key1 => $submission) {
			
			// check contribution type
			if($submission['data_type']=='json'){				
				$trHops = json_decode($submission['tr_data'], true);
				$totHops = count($trHops);
				//print_r($trHops);
				
				//hops
				foreach ($trHops as $key2 => $hop) {
					$hopNum = $key2+1;
					$latencies = array();
					$ip_rank = array();

					// queries
					foreach ($hop as $key3 => $hopPass) {
						
						//$hopPass['rtt'] = $hopPass['rtt']*100;
						$latencies[] = $hopPass['rtt'];
						$ip_latencies[$hopPass['ip']][]  = $hopPass['rtt'];
						
						// prevent ip !set
						if(isset($hopPass['ip'])){
							if(!isset($ip_rank[$hopPass['ip']]))
							{
								$ip_rank[$hopPass['ip']] = 1;	
							} else {
								$ip_rank[$hopPass['ip']] += 1;
							}
						} else {
							// error at this hop
						}

						//

					} // end queries

					sort($latencies);
					arsort($ip_rank);					
					$keys=array_keys($ip_rank);
					$winnerIp = $keys[0];

					//echo "\nWinner IP: ".$winnerIp;

					$TR['hops'][$hopNum]['latencies'] = $latencies;
					$TR['hops'][$hopNum]['winIp'] = $winnerIp;
					
				} //end hop
				//print_r($TR);
				$data['ip_analysis'] = $TR;
				return $data;

			} else if($submission['data_type']=='txt'){
				//echo "\nis txt";
				//$data = GatherTr::analyzeRawTracerouteTxt();		
				return 0; // TODO:

			} else {
				return 0;	
			}
			
		}

	}

	/**
		Publish TR data: new version:
		acceses MaxMind data
		resolves ip hostname
		inserts new ips
		inserts tracerotue data
		inserts traceroute items
		Only traceroutes with at least 2 valid hops are published
		Returns trid and errors back to IXmapsCliient
		Saves error (if any) when inserting into tables: ip_addr_info, traceroute, tr_item
	*/
	public static function publishTraceroute($data)
	{
		global $dbconn, $ixmaps_debug_mode, $gatherTrUri;
		$publishControl = false; 
		$validPublicIPs = 0;
		$trSubString = "";
		$trString ="";
		
		/*check tr status: does the TR reach its destination?*/
		end($data['ip_analysis']['hops']);
		$lastKey = key($data['ip_analysis']['hops']);
		$lastIp = $data['ip_analysis']['hops'][$lastKey]['winIp'];
		//echo "LastIP: ".$lastIp;

		if($data['dest_ip']==$lastIp){
			$trStatus = "c";
		} else {
			$trStatus = "i";
		}
		
		// Collecting the protocol used in the submission data_type = json
		foreach ($data['traceroute_submissions'] as $sub_data) {
			if($sub_data['data_type']=="json"){
				// convert to lowercase before comparison
				$sub_data['protocol'] = strtolower($sub_data['protocol']);
				if($sub_data['protocol']=="icmp"){
					$protocol = "i";
				} else if($sub_data['protocol']=="udp"){ 
					$protocol = "u";
				} else if($sub_data['protocol']=="tcp"){ 
					$protocol = "t";
				}		
			}
		}

		// TODO: check for null fields

		// convert timeout to seconds 
		$data['timeout'] = round($data['timeout']/1000);
		
		/*Format array for insert into Traceroute */
		$trInsertData = array (
			"trData"=>array(
				"dest"=>$data['dest'], 
				"dest_ip"=>$data['dest_ip'],
				"submitter"=>$data['submitter'],
				"zip_code"=>$data['postal_code'],
				"client"=>$data['traceroute_submissions'][0]['client'],
				"cl_ver"=>"1.0",
				"privacy"=>8,
				"timeout"=>$data['timeout'],
				"protocol"=>$protocol,
				"maxhops"=>$data['maxhops'],
				"attempts"=>$data['queries'],
				"status"=>$trStatus)
			);

		$hopCount=0;
		$foundFirstValidIp = false;
		
		// hops
		foreach ($data['ip_analysis']['hops'] as $key => $hop) {
			//echo "\n---- Hop data";
			//print_r($hop);
			// skip local ips, include empty ips
			if(!GatherTr::checkIpIsPrivate($hop['winIp']) || $hop['winIp']==""){

				if($hop['winIp']!=""){
					$validPublicIPs++; // count # of valid public ips
				}

				/*
					anonymize first valid and public ip. 
					
					This approach applies for the first not private ip, which is not necessarily user's public ip, but since the first public ip can be missing from the data, this approach in some cases will anonymize ips that don't need to be anonymized. (Requires further discussion)
				*/
				if(!$foundFirstValidIp && $hop['winIp']!=""){
					$foundFirstValidIp=true;
					//echo "\n First Valid IP: ".$hop['winIp'];
					$ipQuads = explode('.', $hop['winIp']);
					$ipAmonim = "";
				

					for ($i=0; $i < count($ipQuads); $i++) { 
						if($i==count($ipQuads)-1){
							$ipAmonim.=".0";

						} else if ($i==0) {
							$ipAmonim.= "".$ipQuads[$i];

						} else {
							$ipAmonim.= ".".$ipQuads[$i];
						}
					}
					$hop['winIp'] = $ipAmonim;
				}

				$hopCount++;
				$latencyCount = 0;
				// latencies
				foreach ($hop['latencies'] as $key1 => $latency) {
					$latencyCount++;
					
					$rtt_ms=0;
					
					if($latency==-1)
					{
						$status="t";
					} else {
						$status="r";
					}

					/*Colect TR items for insert*/
					$trInsertData["trItems"][$hopCount][]=array(
						"ip"=>$hop['winIp'],
						"status"=>$status,
						"rtt_ms"=>round($latency),
						"attempt"=>$latencyCount,
						"hop"=>$hopCount,
						);
				}

			} else {
				//echo "\n skiping ip: ".$hop['winIp']; // used for debug only
			} // end skip local ip

		}

	
		/*Enables publication of current TR data if there are at least 2 valid public IP addresses*/
		if($validPublicIPs>=2){
			$publishControl = true; 
		}

		/*$totItems = $hopCount*$data['queries'];
		$trString .= "&n_items=".$totItems;*/


		$resultArray = array(
			"trId"=>0,
			"publishControl"=>$publishControl,
			"tot_hops"=>$validPublicIPs,
			"error_type"=>""
			);
		
		if($publishControl){
			/*echo "\n".$trString."";
			$resultArray['trId'] = 0;*/ // For debug 

			if($publishControl){
				/* Insert Traceroute */
				$newTrId = GatherTr::saveTraceroute($trInsertData["trData"]);
				//echo "\nNew TRid: ".$NewTrId;

				/* Insert TrItems */
				if($newTrId!=0){
					GatherTr::manageNewIps($newTrId, $trInsertData["trItems"]);
					$resultArray['trId'] = $newTrId;
				}

			}
		}
		return $resultArray;
	}

	/**
		Publish TR data: old version using python cgi. No longer used
	*/
	public static function publishTracerouteCgi($data) 
	{
		global $dbconn, $ixmaps_debug_mode, $gatherTrUri;
		$publishControl = false; 
		$validPublicIPs = 0;
		$trSubString = "";
		$trString ="";
		
		/*check tr status: does the TR reach its destination?*/
		end($data['ip_analysis']['hops']);
		$lastKey = key($data['ip_analysis']['hops']);
		$lastIp = $data['ip_analysis']['hops'][$lastKey]['winIp'];
		//echo "LastIP: ".$lastIp;

		if($data['dest_ip']==$lastIp){
			$trStatus = "c";
		} else {
			$trStatus = "i";
		}
		
		// Collecting the protocol used in the submission data_type = json
		foreach ($data['traceroute_submissions'] as $sub_data) {
			if($sub_data['data_type']=="json"){
				// convert to lowercase before comparison
				$sub_data['protocol'] = strtolower($sub_data['protocol']);
				if($sub_data['protocol']=="icmp"){
					$protocol = "i";
				} else if($sub_data['protocol']=="udp"){ 
					$protocol = "u";
				} else if($sub_data['protocol']=="tcp"){ 
					$protocol = "t";
				}		
			}
		}

		// TODO: check for null fields

		// convert timeout to seconds 
		$data['timeout'] = round($data['timeout']/1000);

		$trString = "dest=".$data['dest']."&dest_ip=".$data['dest_ip']."&submitter=".urlencode($data['submitter'])."&zip_code=".urlencode($data['postal_code'])."&client=".urlencode($data['traceroute_submissions'][0]['client'])."&cl_ver=1.0&privacy=8&timeout=".$data['timeout']."&protocol=".$protocol."&maxhops=".$data['maxhops']."&attempts=".$data['queries']."&status=".$trStatus;
		
		$hopCount=0;
		$foundFirstValidIp = false;
		
		// hops
		foreach ($data['ip_analysis']['hops'] as $key => $hop) {

			// skip local ips, include empty ips
			if(!GatherTr::checkIpIsPrivate($hop['winIp']) || $hop['winIp']==""){

				if($hop['winIp']!=""){
					$validPublicIPs++; // count # of valid public ips
				}

				/*
					anonymize first valid and public ip. 
					
					This approach applies for the first not private ip, which is not necessarily user's public ip, but since the first public ip can be missing from the data, this approach in some cases will anonymize ips that don't need to be anonymized. (Requires further discussion)
				*/
				if(!$foundFirstValidIp && $hop['winIp']!=""){
					$foundFirstValidIp=true;
					//echo "\n First Valid IP: ".$hop['winIp'];
					$ipQuads = explode('.', $hop['winIp']);
					$ipAmonim = "";

					for ($i=0; $i < count($ipQuads); $i++) { 
						if($i==count($ipQuads)-1){
							$ipAmonim.=".0";

						} else if ($i==0) {
							$ipAmonim.= "".$ipQuads[$i];

						} else {
							$ipAmonim.= ".".$ipQuads[$i];
						}
					}
					$hop['winIp'] = $ipAmonim;
				}

				$hopCount++;
				$latencyCount = 0;
				// latencies
				foreach ($hop['latencies'] as $key1 => $latency) {
					$latencyCount++;
					
					$rtt_ms=0;
					
					if($latency==-1)
					{
						$status="t";
					} else {
						$status="r";
					}

					$trString .= "&status_".$hopCount."_".$latencyCount."=".$status."&ip_addr_".$hopCount."_".$latencyCount."=".$hop['winIp']."&rtt_ms_".$hopCount."_".$latencyCount."=".round($latency);
				}


			} else {
				//echo "\n skiping ip: ".$hop['winIp']; // used for debug only
			} // end skip local ip

		}
		$totItems = $hopCount*$data['queries'];
		$trString .= "&n_items=".$totItems;

		/*Enables publication of current TR data if there are at least 2 valid public IP addresses*/
		if($validPublicIPs>=2){
			$publishControl = true; 
		}

		$resultArray = array(
			"trId"=>0,
			"publishControl"=>$publishControl,
			"tot_hops"=>$validPublicIPs);
		
		if($publishControl){
			/*echo "\n".$trString."";
			$resultArray['trId'] = 0;*/ // For debug 
			
			// adding exceptions for SSL certificate
			$arrContextOptions=array(
			    "ssl"=>array(
			        "verify_peer"=>false,
			        "verify_peer_name"=>false,
			    ),
			);  

			/* Publish TR data */

			/* commenting this out for the moment: it's not needed to collect contributions from IXmapsClient*/
			/*$trResult = file_get_contents($gatherTrUri."?".$trString, false, stream_context_create($arrContextOptions));*/
			$trResult = file_get_contents($gatherTrUri."?".$trString);
			
			$search      = "new traceroute ID";
			$line_number = false;
			$tr_id_arr = explode("\n", $trResult);
			while (list($key, $line) = each($tr_id_arr) and !$line_number) {
			   $line_number = (strpos($line, $search) !== FALSE) ? $key + 1 : $line_number;
			}
			$tr_id_line = explode("=", $tr_id_arr[$line_number-1]);
			//echo "\nTR ID: ".$tr_id_line[1];
			if(count($tr_id_line)==2 && $tr_id_line[1]!=0){
				$resultArray['trId'] = $tr_id_line[1];
			} else {
				$resultArray['trId'] = 0; // error collecting trId
			}
		}
		return $resultArray;
	}


	/**
	Determine if the IP is Private/Reserved
	*/
	public static function checkIpIsPrivate($ip) 
	{
		if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	Flag TR contribution
	*/

	public static function flagContribution($tr_c_id, $traceroute_id, $tr_flag)
	{
		global $dbconn;
		if($tr_c_id!=0 && $traceroute_id!=0){
			$sql = "UPDATE tr_contributions SET traceroute_id = $traceroute_id, tr_flag=$tr_flag WHERE tr_c_id = $tr_c_id";
			$result = pg_query($dbconn, $sql) or die('flagContribution: Query failed'.pg_last_error());
			pg_free_result($result);
		} else if($tr_c_id!=0 && $traceroute_id==0){ // update only tr_flag when no TR_id is availale
			$sql = "UPDATE tr_contributions SET tr_flag=$tr_flag WHERE tr_c_id = $tr_c_id";
			$result = pg_query($dbconn, $sql) or die('flagContribution: Query failed'.pg_last_error());
			pg_free_result($result);
		}
	}

	/**
		Get an ASN for an IP: Using local DBs
	*/
	public static function getIpForAsn($ip='')
	{
		global $dbconn, $ixmaps_debug_mode;
		$sql = "SELECT asn_netmask.*, asn_carrier.name FROM asn_netmask, asn_carrier WHERE (asn_carrier.num=asn_netmask.asn) AND asn_netmask.netmask >>= inet('".$ip."');";
		//echo $sql;
		//$trParams = array($ip);
		//$result = pg_query_params($dbconn, $sql, $trParams) or die('getIpForAsn: Query failed'.pg_last_error());
		$result = pg_query($dbconn, $sql) or die('getIpForAsn: Query failed'.pg_last_error());
		$asnData = pg_fetch_all($result);
		//print_r($asnData);
		pg_free_result($result);
		return $asnData;
	}

	/**
		Checks TR submission for different number of hops and extracts the longest TR (unique routers)
		If the TR passes differ, it uses the other passes to collect as many valid latencies as possible.
	*/
	public static function analyzeIfInconsistentPasses($data)
	{
		$totContributions = 0;
		$totToFlag = 0;

		// contributions
		foreach ($data['traceroute_submissions'] as $key => $c) {
			// check only json submissions
			if($c['data_type']=="json"){
				$totContributions++;
				$passesArray = json_decode($c['tr_data'], true);
				$hasEqualNumOfHops = true;
				$totNumHopsInPass = array();
				$hopsSequenceInPass = array();

				// passes: 1st check
				$passNum = 0;
				foreach ($passesArray as $key1 => $hops1) {
					$passNum++;
					// collect total num of hops
					$totNumHopsInPass[$passNum]=count($hops1);
					//echo "\nTot Hops:".count($hops);
				}
				$totNumHopsInPass = array_unique($totNumHopsInPass);
				
				if(count($totNumHopsInPass)!=1){
					$hasEqualNumOfHops = false;
				}

				/*CASE 1: Analyzing contributions with different number of hops in at least two passes*/
				if(!$hasEqualNumOfHops){
					$totToFlag++;

					$passCounter = 0;
					$longestPassNum = 0;
					$longestPassVal = 0;

					// passes: 2nd check
					foreach ($passesArray as $key2 => $hops) {
						$passCounter++;
						$trPath = array();
						
						// hops
						foreach ($hops as $key => $hop) {
							// exception for no ip data
							if(isset($hop['ip'])){
								$trPath[$passCounter][]=$hop['ip'];
							} else {
								$trPath[$passCounter][]="";
							}
						} // end hops

						/* Look for longest (authoritaive) path with unique ips */
						$trPathTemp = array_unique($trPath[$passCounter]);
						
						if($longestPassVal < count($trPathTemp)){
							$longestPassVal = count($trPathTemp);
							$longestPassNum = $passCounter;
						}

						//echo "\ntrPath: ";
						//print_r($trPath);

					} // end // passes: 2nd check

					//echo "\nLongest path: Pass: ".$longestPassNum;

					// collect data from best path: formatTrData
					$TrByHop = array();
					$hopIndex = 0;
					foreach ($passesArray[$longestPassNum-1] as $key3 => $bestPassHop) {

						// collect hops from best pass
						$TrByHop[$bestPassHop['hop']][$hopIndex]=$bestPassHop;
						//echo "\n------Accessing best pass data \n";
						//print_r($bestPassHop);

						//check if there is consistent data in all other passes for this hop
						for ($passIndex=0; $passIndex < count($passesArray); $passIndex++) {
							// exclude the longest pass, already collected in pass 1
							if($passIndex!=$longestPassNum-1){
								if(isset($passesArray[$passIndex][$hopIndex]['ip'])){
									//echo "\nAccessing other passes data \n";
									//print_r($passesArray[$passIndex][$hopIndex]);

									// check is the same ip as the best pass data
									if(isset($bestPassHop['ip']) && isset($passesArray[$passIndex][$hopIndex]['ip']) && $bestPassHop['ip']==$passesArray[$passIndex][$hopIndex]['ip']){
										// collect hop data from current pass
										$TrByHop[$bestPassHop['hop']][]=$passesArray[$passIndex][$hopIndex];
									}
								}
							}
						}
						
						$hopIndex++;
					}
					//echo "\nBest Pass Data---";
					//print_r($TrByHop); // !!OK
					return array(
						"tr_by_hop"=>$TrByHop,
						"tr_flag"=>3 // different #  of hops in at least two passes 
						);

				} else { 
					$trByHopCase1 = GatherTr::formatTrData($data); 
					
					return array(
						"tr_by_hop"=>$trByHopCase1,
						"tr_flag"=>2 // same # of hops in all passes
						);
				}// end if !hasEqualNumOfHops				
			} // end if json contribution
		} // end loop contributions		
	}

	/**
		
	*/
	public static function getIpAddrInfo($ipCheck="") 
	{
		global $dbconn;
	
		$sql = "SELECT ip_addr_info.* FROM ip_addr_info WHERE gl_override is NULL and mm_lat = 0.0 and mm_long = 0.0 and lat = 0.0 and long = 0.0 and ip_addr = '".$ipCheck."'";

		//$sql = "SELECT ip_addr FROM ip_addr_info WHERE gl_override is NULL and mm_lat = 0.0 and mm_long = 0.0 and lat = 0.0 and long = 0.0 ORDER BY ip_addr";

		/*
		$sql = "SELECT ip_addr_info.ip_addr, tr_item.traceroute_id, tr_item.ip_addr, traceroute.id, traceroute.sub_time FROM ip_addr_info, tr_item , traceroute 
		WHERE (ip_addr_info.ip_addr=tr_item.ip_addr AND traceroute.id=tr_item.traceroute_id) AND tr_item.attempt = 1 
		AND gl_override is NULL and mm_lat = 0.0 and mm_long = 0.0 and lat = 0.0 and long = 0.0 
		ORDER BY tr_item.traceroute_id DESC LIMIT 100";*/

		$result = pg_query($dbconn, $sql) or die('getIpAddrInfo: Query failed'.pg_last_error());
		$dataA = pg_fetch_all($result);
		pg_free_result($result);
		return $dataA;
	}


	/**
		Insert data into traceroute table
	*/
	public static function saveTraceroute($data) 
	{
		global $dbconn, $tr_c_id;
		$newTRid=0;
		//echo "\n -----saveTraceroute()";
		//print_r($data);

		// Produce an sql error by changing a type: for testing save error_log only !!
		//$data['timeout'] = "b"; // the error is produced because the timeout is expected to be an integer

		$sql="INSERT INTO traceroute (sub_time, dest, dest_ip, submitter, zip_code, privacy, timeout, protocol, maxhops, status, attempts, metadata) VALUES (NOW(), $1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11) RETURNING id;";
		$trData = array(
			$data['dest'],
			$data['dest_ip'],
			$data['submitter'],
			$data['zip_code'],
			$data['privacy'],
			$data['timeout'],
			$data['protocol'],
			$data['maxhops'],
			$data['status'],
			$data['attempts'],
			""
			);

		/* Catch errors in sql statement */
		if (pg_send_query_params($dbconn, $sql, $trData)) {
			$result=pg_get_result($dbconn);
			if ($result) {
				$state = pg_result_error_field($result, PGSQL_DIAG_SQLSTATE);
				if ($state!=0) { // error catched 
					$errorData = array(
						"class"=>"GatherTr",
						"function"=>"saveTraceroute",
						"tr_c_id"=>$tr_c_id,
						"data"=>$data,
						"error"=>pg_last_error(),
						"E_USER_ERROR"=>E_USER_ERROR
						);
					GatherTr::saveError($errorData);
					$newTRid=0;	
				} else {
					// success ! ...
					$temp = pg_fetch_all($result);
					$newTRid = $temp[0]['id'];
				}
				pg_free_result($result);
			}
		}
		/* /Catch errors in sql statement */ 

		return $newTRid;
	}


	/**
		Insert data into tr_item table
	*/
	public static function saveTracerouteItem($trId, $data) 
	{
		//echo "\n------ saveTracerouteItem()";
		global $dbconn;
		//echo "\ndata['ip']:\n";
		//var_dump($data['ip']);
		if($data['ip']==''){
					$sql = "INSERT INTO tr_item (traceroute_id,hop,attempt,status,ip_addr,rtt_ms) VALUES ($1, $2, $3, $4, null, $5);";
			$trItemsData = array(
				$trId,
				$data['hop'],
				$data['attempt'],
				$data['status'],
				$data['rtt_ms'],
				);

		} else {
			$sql = "INSERT INTO tr_item (traceroute_id, hop, attempt, status, ip_addr, rtt_ms) VALUES ($1, $2, $3, $4, $5, $6);";
			$trItemsData = array(
				$trId,
				$data['hop'],
				$data['attempt'],
				$data['status'],
				$data['ip'],
				$data['rtt_ms'],
				);

		}

		//$sqlTest = "INSERT INTO tr_item (traceroute_id,hop,attempt,status,ip_addr,rtt_ms) VALUES (".$trId.", ".$data['hop'].", ".$data['attempt'].", '".$data['status']."', '".$data['ip']."', ".$data['rtt_ms'].");";
		
		//echo "\ntrId: ".$trId;
		//print_r($trItemsData);
		//echo "\n".$sqlTest."\n";

		/* Test invoke error on insert */
		//$trItemsData[0]="error";

		/* Catch errors in sql statement */
		if (pg_send_query_params($dbconn, $sql, $trItemsData)) {
			$result=pg_get_result($dbconn);
			if ($result) {
				$state = pg_result_error_field($result, PGSQL_DIAG_SQLSTATE);
				if ($state!=0) { // error catched 
					$errorData = array(
						"class"=>"GatherTr",
						"function"=>"saveTracerouteItem",
						"traceroute_id"=>$trId,
						"data"=>$data,
						"error"=>pg_last_error(),
						"E_USER_ERROR"=>E_USER_ERROR
						);
					GatherTr::saveError($errorData);
					return 0;
						
				} else {
					// success ! ...
					pg_free_result($result);
					return 1; // action completed
				}
				
			}
		}
		/* /Catch errors in sql statement */ 

	}
	

	/**
		Insert new IP
	*/
	public static function insertNewIp($data) 
	{
		//echo "\---- insertNewIp()";
		global $dbconn, $tr_c_id;

		/* TODO: check data types on all $data vars */
		if($data['asn'] == null || $data['asn'] == ""){
			$data['asn'] = -1;
		}
		
		/* test invoke error on insert */
			//$data['asn'] = "ANTO CREATED THIS ERROR FOR TESTING";
		
		$sql = "INSERT INTO ip_addr_info (ip_addr, asnum, mm_lat, mm_long, hostname, mm_country, mm_region, mm_city, mm_postal, mm_area_code, mm_dma_code, p_status, lat, long, gl_override) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15);";
		$ipData = array($data['ip'], $data['asn'], $data['geoip']['latitude'], $data['geoip']['longitude'], $data['hostname'], $data['geoip']['country_code'], $data['geoip']['region'], $data['geoip']['city'], $data['geoip']['postal_code'], $data['geoip']['area_code'], $data['geoip']['dma_code'], "N", $data['geoip']['latitude'], $data['geoip']['longitude'], NULL);

			/* Catch errors in sql statement */
			if (pg_send_query_params($dbconn, $sql, $ipData)) {
				$result=pg_get_result($dbconn);
				if ($result) {
					$state = pg_result_error_field($result, PGSQL_DIAG_SQLSTATE);
					if ($state!=0) {
						$errorData = array(
							"class"=>"GatherTr",
							"function"=>"insertNewIp",
							"tr_c_id"=>$tr_c_id,
							"data"=>$data,
							"error"=>pg_last_error(),
							"E_USER_ERROR"=>E_USER_ERROR
							);
						GatherTr::saveError($errorData);
						/* TODO: need to decide if we rollback the new TR ?? discuss this!!*/
						return 0;
					} else {
						// success
						pg_free_result($result);
						return 1; // action completed
					}
				}
			}
			/* /Catch errors in sql statement */ 

	}

	/**
		Check if an ip exists in DB
	*/
	public static function checkIpExists($ip) 
	{
		global $dbconn;

		/* TODO: Check is a valid ip: a bit redundant?*/
		$sql = "SELECT ip_addr_info.ip_addr FROM ip_addr_info WHERE ip_addr = '".$ip."'";

		$result = pg_query($dbconn, $sql) or die('checkIpExists: Query failed'.pg_last_error());
		$dataA = pg_fetch_all($result);
		pg_free_result($result);
		return $dataA;
	}

	/**
		Manage a collection of ip addreses and inserts TR items
	*/
	public static function manageNewIps($trId, $data) 
	{
		global $dbconn, $mm;
		//echo "\n------ manageNewIps()";
		//echo "\ntrID: ".$trId;

		//loop TR hops
		$hopCount = 0;
		foreach ($data as $key => $hop) {
			$hopCount++;
			/*echo "\n---- hop data\n";
			print_r($hop);*/
			
			if(filter_var($hop[0]["ip"], FILTER_VALIDATE_IP)) {
				//echo "\n[".$hopCount."] - Valid IP - ".$hop[0]["ip"]."";
				//print_r($hop);
				
				// Check if the ip exists
				$checkIpExists = GatherTr::checkIpExists($hop[0]["ip"]);
				if(!isset($checkIpExists[0]['ip_addr'])){
					//echo "\nIP Already exists ! ".$checkIpExists[0]['ip_addr'];
				
					//echo "\nNEW IP !  ".$hop[0]["ip"];
					$geoIp = $mm->getGeoIp($hop[0]["ip"]);
					/*echo "\n getGeoIp()";
					var_dump($geoIp);*/
					$newIpResult = GatherTr::insertNewIp($geoIp);
				} // end if check ip
				
				
			} else {
				//echo "\n[".$hopCount."] Not a valid IP";
				//print_r($hop);
			}

			/*Loop hop latencies - Insert TR item*/
			foreach ($hop as $key => $trItem) {
				/*echo "\n TR item\n";
				print_r($trItem);*/
				$saveTrItemResult = GatherTr::saveTracerouteItem($trId, $trItem);
				/* TODO: determine what action needs to be taker if at least one item could not be saved
					$saveTrItemResult == 0
				*/
			} // end loop latencies
			
		} // end loop TR hops
		
	}
		
	/*
		            [ip] => 69.196.136.132
		            [status] => r
		            [rtt_ms] => 11
		            [attempt] => 1
		            [hop] => 4
	*/

	/**
		Insert data into error_log
	*/
	public static function saveError($data) 
	{
		global $dbconn;
		$sql = "INSERT INTO error_log (log_date, error) VALUES (NOW(), $1);";
		$errorJson = json_encode($data);
		$errorData = array($errorJson);
		$result = pg_query_params($dbconn, $sql, $errorData);
		pg_free_result($result);
		return 1;
	}
	
	/**
		Get data from error_log
	*/
	public static function getError($errorId=0, $offset=0, $limit=0)
	{
		global $dbconn;
		if($errorId!=0){
			$sql = "SELECT * FROM error_log WHERE id = $1;";
		} else {
			$sql = "SELECT * FROM error_log WHERE id <> $1 ORDER BY id DESC OFFSET $offset LIMIT $limit;";
		}
		$errorData = array($errorId);
		$result = pg_query_params($dbconn, $sql, $errorData);
		$dataResult = pg_fetch_all($result);
		//print_r($dataResult);
		pg_free_result($result);
		return $dataResult;
	}

} // end class


?>