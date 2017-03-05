<?php
class IpFlag
{
	public static function renderFlagLogs($data){
		//print_r($data);
		$html = '<table id="ip-flag-log-table">';
			$html .='<tr>';
			$html .='<td>ID</td>';
			$html .='<td>Flagged IP</td>';
			$html .='<td>Date</td>';
			$html .='<td>User IP</td>';
			$html .='<td>Username</td>';
			$html .='<td>Message</td>';
			$html .='<td>Suggested Location</td>';
			$html .='</tr>';
		foreach ($data as $key => $value) {
			$html .='<tr>';
			$html .='<td>'.$value['id_f'].'</td>';
			$html .='<td>'.$value['ip_addr_f'].'</td>';
			$html .='<td>'.$value['date_f'].'</td>';
			$html .='<td>'.$value['user_ip'].'</td>';
			$html .='<td>'.$value['user_nick'].'</td>';
			$html .='<td>'.$value['user_msg'].'</td>';
			$html .='<td>'.$value['ip_new_loc'].'</td>';
			$html .='</tr>';
		}

		$html .= "</table>";
		echo $html;
	}

	public static function getFlagsLogs(){
		global $dbconn, $ixmaps_debug_mode;
		$sql="SELECT * FROM ip_flagged_items ORDER BY id_f DESC";
		//echo $sql;
		$result = pg_query($dbconn, $sql) or die('Query failed: ' . pg_last_error());
		$fArr = pg_fetch_all($result);
		//echo '------';
		//print_r($fArr);
		pg_free_result($result);
		pg_close($dbconn);
		return $fArr;
	}
	public static function getIpAddrInfo($ip){
		global $dbconn;

		//$sql="SELECT as_users.num, as_users.name, ip_addr_info.ip_addr, ip_addr_info.asnum, ip_addr_info.hostname, ip_addr_info.mm_country, ip_addr_info.mm_region, ip_addr_info.mm_city, ip_addr_info.mm_postal, ip_addr_info.gl_override, ip_addr_info.flagged, glo_reason.reason, glo_reason.evidence FROM as_users, ip_addr_info, glo_reason WHERE (ip_addr_info.gl_override=glo_reason.id) AND (as_users.num=ip_addr_info.asnum) AND ip_addr_info.ip_addr = '".$ip."'";

		$sql="SELECT as_users.num, as_users.name, ip_addr_info.ip_addr, ip_addr_info.asnum, ip_addr_info.hostname, ip_addr_info.mm_country, ip_addr_info.mm_region, ip_addr_info.mm_city, ip_addr_info.mm_postal, ip_addr_info.mm_lat, ip_addr_info.mm_long, ip_addr_info.lat, ip_addr_info.long, ip_addr_info.gl_override, ip_addr_info.flagged FROM as_users, ip_addr_info, glo_reason WHERE (as_users.num=ip_addr_info.asnum) AND ip_addr_info.ip_addr = '".$ip."'";

		//echo $sql;
		$result = pg_query($dbconn, $sql) or die('Query failed: ' . pg_last_error());
		$fArr = pg_fetch_all($result);
		pg_free_result($result);
		return $fArr;
	}

	public static function getFlags($data){
		global $dbconn;

		$sql="SELECT ip_flagged_items.*, ip_addr_info.asnum, ip_addr_info.mm_country, ip_addr_info.mm_city, ip_addr_info.mm_postal, ip_addr_info.flagged FROM ip_flagged_items, ip_addr_info WHERE (ip_addr_info.ip_addr=ip_flagged_items.ip_addr_f) AND ip_flagged_items.ip_addr_f = '".$data['ip_addr_f']."' ORDER BY ip_flagged_items.id_f DESC";

		//echo $sql;
		$result = pg_query($dbconn, $sql) or die('Query failed: ' . pg_last_error());
		$fArr = pg_fetch_all($result);
		pg_free_result($result);
		pg_close($dbconn);
		return $fArr;
	}

	public static function saveFlags($data){
		global $dbconn, $ixmaps_debug_mode;
  		date_default_timezone_set('America/Toronto');
		$date = date('Y-m-d H:i:s');

		// conduct some safe replaces
		$data['user_nick'] = str_replace("'","´",$data['user_nick']);
		$data['user_reasons_types'] = str_replace("'","´",$data['user_reasons_types']);
		$data['user_msg'] = str_replace("'","´",$data['user_msg']);
		$data['ip_new_loc'] = str_replace("'","´",$data['ip_new_loc']);

		$sql="INSERT INTO ip_flagged_items (ip_addr_f, date_f, user_ip, user_nick, user_reasons_types, user_msg, ip_new_loc) VALUES ('".$data['ip_addr_f']."','".$date."','".$data['user_ip']."','".$data['user_nick']."','".$data['user_reasons_types']."','".$data['user_msg']."','".$data['ip_new_loc']."');";
		//echo "<hr/>".$sql;
		$result = pg_query($dbconn, $sql) or die('Query failed: ' . pg_last_error());
		pg_free_result($result);

		$updateSql = "UPDATE ip_addr_info SET flagged = 1 WHERE ip_addr = '".$data['ip_addr_f']."'";
		pg_query($dbconn, $updateSql) or die('Query failed updating ip_addr_info: ' . pg_last_error());
		//echo $updateSql;
		pg_close($dbconn);
		return 1;
	}

}
?>