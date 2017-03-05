<?php
class PrivacyReport
{
	public static function getPrivacyData(){
		global $dbconn, $ixmaps_debug_mode;

		$sql1="SELECT privacy_stars.* FROM privacy_stars";
		$sql2="SELECT privacy_scores.* FROM privacy_scores order by asn, star_id";

		//echo $sql;
		$result1 = pg_query($dbconn, $sql1) or die('Query privacy_stars failed: ' . pg_last_error());
		$result2 = pg_query($dbconn, $sql2) or die('Query privacy_scores failed: ' . pg_last_error());

		// loop and format the data
		$stars = array();
		$scores = array();

		// stars
		while ($line1 = pg_fetch_array($result1, null, PGSQL_ASSOC)) {
		    $stars[$line1['star_id']] = $line1;
		}
		
		while ($line2 = pg_fetch_array($result2, null, PGSQL_ASSOC)) {
		    $scores[$line2['asn']][] = $line2;
		}
		
		//$stars = pg_fetch_all($result1);
		//$scores = pg_fetch_all($result2);

		pg_free_result($result1);
		pg_free_result($result2);

		$privacy = array(
			'stars'=>$stars,
			'scores'=>$scores
			);
		pg_close($dbconn);

		//print_r($privacy);
		
		return $privacy;
	}
}
?>