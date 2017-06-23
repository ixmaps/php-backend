<?php
include('../config.php');

//createLastHopsTb();

//echo getLastTrIdGen();
$lastTrId = getLastTrIdGen();
echo 'Last TRid generated: '.$lastTrId;
collectLastHop($lastTrId);

//echo 'Nothing to do for now.';

/**
	Iterates for all Traceroutes and collects last hop data and generates a SQL file 
	This is needed for the new approach in query search. The destination is now calculated using last hop and not destination ip.
*/
//function collectLastHop($trId1, $trId2)
function collectLastHop($trIdLast)
{
	global $dbconn, $dbQueryHtml, $savePath;

	// initialize writing file
	$sqlCo='';
		//$sqlFile = $trId1."-".$trId2.".sql";
		//$mySqlFile = $savePath."/".$sqlFile;
		//$fsql = fopen($mySqlFile, 'w') or die("can't open/create ".$sqlFile." file.");

	// old approach: for bulk generation
	//$sql="SELECT traceroute.id FROM traceroute WHERE traceroute.id between ".$trId1. " AND "." $trId2 order by traceroute.id";

	// production approach
	$sql="SELECT traceroute.id FROM traceroute WHERE traceroute.id > ".$trIdLast." order by traceroute.id";

	//echo $sql;
	$result = pg_query($dbconn, $sql) or die('Query failed: ' . pg_last_error());
	//$trArr = array();
	$trArr = pg_fetch_all($result);
	//echo '<hr/>';
	//var_dump($trArr);
	//echo '<hr/>';
	
	$conn=0;
	$connGen=0;

	if($trArr){
		foreach ($trArr as $key => $trId) {
			
			//$sqlLastHop="SELECT as_users.num, tr_item.hop, tr_item.attempt, tr_item.traceroute_id, traceroute.id, traceroute.dest, traceroute.dest_ip, ip_addr_info.mm_country, ip_addr_info.mm_city, ip_addr_info.ip_addr, ip_addr_info.asnum FROM as_users, tr_item, traceroute, ip_addr_info WHERE (tr_item.traceroute_id=traceroute.id) AND (ip_addr_info.ip_addr=tr_item.ip_addr) AND (as_users.num=ip_addr_info.asnum) AND tr_item.attempt = 1 AND tr_item.hop > 1 and traceroute.id=".$trId['id']." order by tr_item.hop DESC LIMIT 1";

			$sqlLastHop="SELECT tr_item.hop, tr_item.traceroute_id, traceroute.id, traceroute.dest, traceroute.dest_ip, ip_addr_info.ip_addr FROM tr_item, traceroute, ip_addr_info WHERE (tr_item.traceroute_id=traceroute.id) AND (ip_addr_info.ip_addr=tr_item.ip_addr) AND tr_item.attempt = 1 AND tr_item.hop > 1 and traceroute.id=".$trId['id']." order by tr_item.hop DESC LIMIT 1";

			//echo "<hr/>".$sqlLastHop;
			
			$result1 = pg_query($dbconn, $sqlLastHop) or die('Query failed: ' . pg_last_error());
			$lastHopArr = pg_fetch_all($result1);
			//print_r($lastHopArr);

			// clean up city name : not used for now
			//$lastHopArr[0]["mm_city"] = str_replace("'","\'", $lastHopArr[0]["mm_city"]);
			
			$reached = 1;
			if($lastHopArr[0]["ip_addr"]!=$lastHopArr[0]["dest_ip"]){

				//echo "<br/>TR ".$lastHopArr[0]["traceroute_id"]." : Ip: ".$lastHopArr[0]["ip_addr"]." : Dest: ".$lastHopArr[0]["dest_ip"];
				$reached = 0;
				$conn++;
			}

			// insert record in log 

			//$sqlInsert = "INSERT INTO tr_last_hops (traceroute_id, hop, attempt, ip_addr, dest, dest_ip, mm_country, mm_city, asnum) VALUES (".$lastHopArr[0]["traceroute_id"].", ".$lastHopArr[0]["hop"].", ".$lastHopArr[0]["attempt"].", '".$lastHopArr[0]["ip_addr"]."', '".$lastHopArr[0]["dest"]."', '".$lastHopArr[0]["dest_ip"]."', '".$lastHopArr[0]["mm_country"]."', '".$lastHopArr[0]["mm_city"]."', ".$lastHopArr[0]["asnum"].");";

			$sqlInsert = "INSERT INTO tr_last_hops VALUES (".$lastHopArr[0]["traceroute_id"].", ".$lastHopArr[0]["hop"].", '".$lastHopArr[0]["ip_addr"]."', ".$reached.");";

			//echo "<hr/>".$sqlInsert;

			// save
			//fwrite($fsql, $sqlInsert);
			try {

				if($sqlInsert!="INSERT INTO tr_last_hops VALUES (, , '', 1);"){
            		pg_query($dbconn, $sqlInsert) or die('Query failed: ' . pg_last_error());
            	} else {
            		echo "<br/>This TR has no hops. Empty record...";
            	}

	        } catch(Exception $e){
	        	echo "db error.";
	        }
			

			$connGen++;

		} // end foreach
	}// end if not empty

	//echo "<hr/>From : ".$trId1." to: ".$trId2."<br/><b>". $conn. "</b> : TRs never reached its destination.";

	if($connGen==0){
		echo '<hr/>Nothing to do for now. ';
	} else {

		echo "<hr/>Starting at : ".$trIdLast.". <br/><b>". $connGen. "</b> TRs last hop generated.";
	}

	pg_free_result($result);
	//pg_free_result($result1);

	// close
	//fclose($fsql);

	//??
	pg_close($dbconn);
	
	//return $trArr;
}

function getLastTrIdGen(){
	global $dbconn;
	$sql="SELECT traceroute_id_lh FROM tr_last_hops ORDER BY traceroute_id_lh DESC LIMIT 1";
	$result = pg_query($dbconn, $sql) or die('Query failed: ' . pg_last_error());
	$lastTRId = pg_fetch_all($result);
	//print_r($lastTRId);
	pg_free_result($result);
	//pg_close($dbconn);
	$lastId = $lastTRId[0]['traceroute_id_lh'];
	return $lastId;
}
?>