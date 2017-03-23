<?php

class MapSearch
{

	/**
		TODO: separete this function
	*/
	public static function countTrsIntersect($data)
	{
		global $dbconn;

	}

	/**
		Quick search for Map Page
	*/
	public static function countTrs($data)
	{
		global $dbconn;
		
		// independent constraint
		$sql = "SELECT COUNT(DISTINCT traceroute.id) FROM as_users, tr_item, traceroute, ip_addr_info WHERE (tr_item.traceroute_id=traceroute.id) AND (ip_addr_info.ip_addr=tr_item.ip_addr) AND (as_users.num=ip_addr_info.asnum)";

		// intersect
		$sql1 = "SELECT DISTINCT traceroute.id FROM as_users, tr_item, traceroute, ip_addr_info WHERE (tr_item.traceroute_id=traceroute.id) AND (ip_addr_info.ip_addr=tr_item.ip_addr) AND (as_users.num=ip_addr_info.asnum)";

		//$sqlLimit = " LIMIT 100";
		$paramsCounter=0;
		$sqlParamsArray = array();

		$doesNotChk=false;
		$params = array(); // build count independet constraint
		$params1 = array(); // build count insersect all constraints
		$sqlRun = "";
		$sqlIntersectArray = array();
		$sqlIntersect = "";
		$totIntersect = 0;


		$filterResults = array();
		// count trs for each of the constraints
		foreach ($data as $key => $constraint) {
			$paramsCounter++;

			$params1 = Traceroute::buildWhere($constraint, $doesNotChk, $paramsCounter);

			$params = Traceroute::buildWhere($constraint);

			$sqlRun = $sql.$params[0];
			$sqlParamsArray[] = $params[1];
			$sqlIntersectArray[]= $sql1 . $params1[0];

			$result = pg_query_params($dbconn, $sqlRun, array($params[1])) or die('countTrResults: Query failed: incorrect parameters');

			//echo "\n".$sqlRun."\n".$params[1];;

			$trArr = pg_fetch_all($result);
			//print_r($trArr);

			$filterResults[''.$paramsCounter] = array(
				"total"=>$trArr[0]['count'],
				"constraint"=>$constraint
				);

		} // end for each

		// query intersect all constraints
		if(count($sqlIntersectArray)>1){
			$c = 0;
			foreach ($sqlIntersectArray as $key1 => $sqlI) {
				$c++;

				// first item
				if ($c==1){
					$sqlIntersect.="".$sqlI."";
				// last item
				} else {
					$sqlIntersect.="
					INTERSECT
					".$sqlI."
					";
				}
			} // end for

			//echo "\nSQL intersect: ".$sqlIntersect;

			$result1 = pg_query_params($dbconn, $sqlIntersect, $sqlParamsArray) or die('countTrResults: Query failed: incorrect parameters');
			$trArrIntersect = pg_fetch_all($result1);

			//print_r($trArrIntersect);

			$totIntersect = count($trArrIntersect);

		} else {
			$totIntersect = 0;
		}

		return array(
			"results"=>$filterResults,
			"total"=>$totIntersect,
		);
	}
}

?>