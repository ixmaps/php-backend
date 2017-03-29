<?php
// anto test
class SLog
{
	public static function jsonToDb() {
		global $dbconn;

		/*$result = pg_query($dbconn, $sql);
		$data = pg_fetch_all($result);*/

		//$jsonF = file_get_contents('../json/hostname_data_001_0.json');

		//$jsonF = file_get_contents('../json/hostname_data_001_a.json');
		//$jsonF = file_get_contents('../json/hostname_data_001_b.json');

		//$jsonF = file_get_contents('../json/hostname_data_002a.json');
		//$jsonF = file_get_contents('../json/hostname_data_002b.json');

		$jsonF="";
		
		
		$jsonA = json_decode($jsonF);
		//print_r($jsonF);
		
		/*foreach ($jsonA as $key => $value) {
			print_r($value);
		}*/

		//$date = md5(date('d-m-o_G-i-s'));
		$cNum = 0;
		foreach ($jsonA as $key => $value) {
			//print_r($value);
			$sql = "INSERT INTO ripe_hostnames 
				(
					ripe_user_id, 
					created, 
					hostname, 
					confidence, 
					georesult,
					lat,
					lon	
				) VALUES ($1, $2, $3, $4, $5, $6, $7);
				";
				//echo "<hr/>".$sql;

				$hostData = array(
					$value->user, 
					"NOW()",
					$value->hostname,
					$value->confidence,
					$value->georesult,
					$value->lat,
					$value->lon
				);

				$result = pg_query_params($dbconn, $sql, $hostData) or die('Query failed: incorrect parameters');

				//$result = pg_query($dbconn, $sql);
				$cNum++;
		} // end for
		pg_free_result($result);
		pg_close($dbconn);
		echo "TOT: ".$cNum;
	}

	public static function compareMMData() {
		global $dbconn;
		$sql = "SELECT * from s_log where ip<>'' order by id";
		$result = pg_query($dbconn, $sql);
		$data = pg_fetch_all($result);

		//print_r($data);

		foreach ($data as $key => $value) {

			$mm_09_2012 = unserialize($value['mm_09_2012']);
			$mm_07_2014 = unserialize($value['mm_07_2014']);
			
			echo "<hr/>".$value['ip'];

			foreach ($mm_09_2012 as $key1 => $value1) {
				echo "<br/>".$key1.": ".$value1;
			}
			echo "<br/>";
			foreach ($mm_07_2014 as $key2 => $value2) {
				echo "<br/>".$key2.": ".$value2;
			}
		}

		pg_free_result($result);
		pg_close($dbconn);
	}

	public static function geoLocIp($ip, $MM_version) {
		global $dbconn;
		$pathToGIP = "/Applications/XAMPP/xamppfiles/htdocs/mywebapps/ixmaps.ca/git-ixmaps.ca/application/geoip";
		/*GeoLiteCity*/
		$d_GeoLiteCity = geoip_open($pathToGIP."/dat/".$MM_version."/GeoLiteCity.dat",GEOIP_STANDARD);
		//$gi1 = geoip_open("dat/GeoLiteCity.dat",GEOIP_STANDARD);
		$record1 = geoip_record_by_addr($d_GeoLiteCity,$ip);
		return $record1;
		geoip_close($d_GeoLiteCity);
	}

	public static function countRequestByIp() {
		global $dbconn;

		$sql = "SELECT id, ip from s_log Where timestamp >= '2014-05-01' and timestamp < '2014-05-02' order by ip";
		
		$result = pg_query($dbconn, $sql);
		$data = pg_fetch_all($result);
		$ipC = array();

		foreach ($data as $key => $value) {
			$ipC[$value['ip']][] = $value['id'];
		}

		print_r($ipC);

		/*foreach ($ipC as $key1 => $value1) {
			echo "Key". 
		}*/
		
		pg_free_result($result);
		pg_close($dbconn);

		return $data;
	}
	
	public static function updateGeoData() {
		global $dbconn;

		//$MM_version = "09-2012"; 
		$MM_version = "07-2014";
		

		$sql = "SELECT id, ip from s_log where ip<>'' order by id";
		$result = pg_query($dbconn, $sql);
		$data = pg_fetch_all($result);

		//echo "<hr/>".$sql;
		//$result = pg_query_params($dbconn, $sql, array($id)) or die('Query failed: incorrect parameters');
		$result = pg_query($dbconn, $sql);
		$data = pg_fetch_all($result);
		
		//print_r($data);

		echo "<br/>TOT: ".count($data);

		foreach ($data as $key => $value) {
			$geo_ip_obj=SLog::geoLocIp($value['ip'],$MM_version);
			//print_r($geo_ip_obj);
			
			//$geo_ip_obj_json=json_encode($geo_ip_obj);
				//$geo_ip_obj_serialized=serialize($geo_ip_obj);
			//print_r(expression)

				//$geo_ip_obj_serialized = str_replace("'", "\'", $geo_ip_obj_serialized);

			//$unS = unserialize($geo_ip_obj_serialized);
			//echo "<hr/>";
			//print_r($unS);
			
			//echo $geo_ip_obj_json;
			
			//$updateSql = "UPDATE s_log SET mm_09_2012 = '".$geo_ip_obj_serialized."' WHERE id = ".$value['id'].";";
			//$updateSql = "UPDATE s_log SET mm_07_2014 = '".$geo_ip_obj_serialized."' WHERE id = ".$value['id'].";";

			$updateSql = "UPDATE s_log SET country_code = '".$geo_ip_obj->country_code."' WHERE id = ".$value['id'].";";

			

			//$updateSql = "UPDATE s_log SET mm_07_2014 = '".$geo_ip_obj_json."' WHERE id = ".$value['id'];
			$result = pg_query($dbconn, $updateSql);

			//echo "<hr/>".$value['ip']."<br/>".$MM_version."<br/>".$updateSql;
			echo "
".$updateSql;
		}

		pg_free_result($result);
		pg_close($dbconn);
	}


	public static function collectExploreStats() {
		global $dbconn;

		$s1 = "SELECT COUNT(id) FROM s_log";
		$s2 = "SELECT COUNT(DISTINCT ip) FROM s_log";
		$s3 = "SELECT COUNT(id) FROM s_log WHERE log like '%recentRoutes%'";
		$s4 = "SELECT COUNT(id) FROM s_log WHERE log like '%lastSubmission%'";
		
		$r1 = pg_query($dbconn, $s1);
		$d1 = pg_fetch_all($r1);
		
		$r2 = pg_query($dbconn, $s2);
		$d2 = pg_fetch_all($r2);
		
		$r3 = pg_query($dbconn, $s3);
		$d3 = pg_fetch_all($r3);
		
		$r4 = pg_query($dbconn, $s4);
		$d4 = pg_fetch_all($r4);

		$queryStats = array(
			"Total Queries"=>$d1[0]['count'],
			"Total Unique IP addresses"=>$d2[0]['count'],
			"Recent Routes"=>$d3[0]['count'],
			"Last Submission"=>$d4[0]['count']
			);

		pg_free_result($r1);
		pg_free_result($r2);
		pg_free_result($r3);
		pg_free_result($r4);

		//print_r($queryStats);
		pg_close($dbconn);
		return $queryStats;
		
	}


	public static function renderSearchLogD3($data, $filter=false) {
		global $dbconn;

		if($filter){
			$date1 = $data['date1'];
			$date2 = $data['date2'];
		} else {
			//$date=date("j, n, Y");                       // 10, 3, 2001
			//$date=date("Y-n-j");
			$date2 = '2014-07-31';
			$date1 = '2014-07-01';		
		}


		// collect some stats

		//$sql = "select * from s_log order by id DESC";
		$sql = "SELECT * from s_log Where timestamp >= $1 and timestamp < $2 order by id";
		//echo "<hr/>".$sql."<br/>".$date1." - ".$date2;
		$result = pg_query_params($dbconn, $sql, array($date1, $date2)) or die('Query failed: incorrect parameters');
		$data = pg_fetch_all($result);
		$data_temp=array();
		$data_to_d3=array();

		if(count($data)!=0){
		
			// collect data
			foreach ($data as $key => $value) {
				//echo "<br/>".$value['timestamp'];
				$dd=explode(" ", $value['timestamp']);
				$dC = "";
				$dC = $dd[0];
				//echo "<br/>".$dC;
				$data_temp[$dC][]=array(
					//'ip'=>$value['ip'],
					'timestamp'=>$value['timestamp'],
					'city'=>$value['city'],
					'log'=>$value['log']
					);
			}
			// generate data for d3
			foreach ($data_temp as $key1 => $value1) {
				$data_to_d3[]=array(
					//'ip'=>$value['ip'],
					'date'=>$key1,
					'total'=>count($value1)
					);
			}
		}// end if

		return array(
			"slogData"=>$data,
			"slogDataD3"=>$data_to_d3,
			"queryStats"=>$queryStats,
			"date1"=>$date1,
			"date2"=>$date2
			);
		
		pg_free_result($result);
		pg_close($dbconn);
	}

	public static function renderSearchLog()
	{
		global $dbconn;
		$html = '<table border="1">';
		$c = 0;
		$sql = "select * from s_log order by id DESC LIMIT 1000";

		$result = pg_query($dbconn, $sql) or die('Query failed: ' . pg_last_error());
		while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {
		    $id=$line['id']; 
		    $ip=$line['ip']; 
		    $city=$line['city']; 
		    $timestamp=$line['timestamp'];
		    $log=$line['log'];
		    $log=str_replace('"[', '[', $log); 
		    $log=str_replace(']"', ']', $log); 
		    $logToArray = json_decode($log, true);

		    $c++;
			
			$html .= '<tr>';
			$html .= '<td><a href="#">'.$id.'</a></td>';
			$html .= '<td>'.$ip.'</td>';
			$html .= '<td>'.$city.'</td>';
			$html .= '<td>'.$timestamp.'</td>';

			$q = '<td>';
			foreach ($logToArray as $constraint) {
				$q .='<br/> | '
				.$constraint['constraint1'].' | '
				.$constraint['constraint2'].' | '
				.$constraint['constraint3'].' | '
				.$constraint['constraint4'].' | '
				.$constraint['constraint5'].' | ';
				//print_r($constraint);
			}

			//$q .= $log.'<hr/>'.$queryOp.'</td>';
			$q .= '</td>';
			$html .= ''.$q;
			
			$html .= '</tr>';
		}
		$html .= '</table>';
		pg_free_result($result);
		pg_close($dbconn);
		echo 'Tot queries: '.$c.'<hr/>';
		echo $html;
	}

} // end class

?>


