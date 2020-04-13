<?php

/**
 *
 *  This Model is strangely named - it does not relate to Map and Search.
 *  Rather, it is used to gather some data for the initial model that pops
 *  up when a user first visits the map page
 *
 * @param Called from map_search.php controller
 *
 * @return set of traceroute objects (TODO - confirm this)
 *
 * @since Updated Apr 2020
 * @author IXmaps.ca (Antonio, Colin)
 *
 */

class MapSearch
{
  /**
    Quick search for Map Page
  */
  public static function countTrs($data)
  {
    global $dbconn, $debugTrSearch;

    // start here, rename all the nonsense var names

    // return empty for non params
    if (count($data) == 0) {
      $resultA =  array(
        "results" => array(),
        "total" => 0,
      );

    } else {
      // sql for each constraint
      $sql = "SELECT COUNT(DISTINCT traceroute_traits.traceroute_id) FROM annotated_traceroutes, traceroute_traits WHERE annotated_traceroutes.traceroute_id = traceroute_traits.traceroute_id";

      // sql for intersect constraints
      $sql1 = "SELECT COUNT(DISTINCT traceroute_traits.traceroute_id) FROM annotated_traceroutes, traceroute_traits WHERE annotated_traceroutes.traceroute_id = traceroute_traits.traceroute_id";

      $paramsCounter = 0;
      $sqlParamsArray = array();

      $params = array(); // build count independent constraint
      $params1 = array(); // build count intersect all constraints
      $sqlRun = "";
      $sqlIntersectArray = array();
      $sqlIntersect = "";
      $totIntersect = 0;

      $filterResults = array();
      // count trs for each of the constraints
      foreach ($data as $key => $constraint) {

        $params = Traceroute::buildWhere($constraint);
        $sqlRun = $sql.$params[0]; // add where conditions

        $result = pg_query_params($dbconn, $sqlRun, array($params[1])) or die('countTrResults: Query failed: incorrect parameters');

        $trArr = pg_fetch_all($result);

        // sql for intersect statements
        if ($trArr[0]['count']!=0) {
          $paramsCounter++;
          $params1 = Traceroute::buildWhere($constraint, $paramsCounter);

          $sqlIntersectArray[] = $sql1 . $params1[0]; // add sql where
          $sqlParamsArray[] = $params[1]; // collect params array
        }

        // TODO - why is this done twice?
        if ($debugTrSearch) {
          $filterResults[$key] = array(
            "total"=>$trArr[0]['count'],
            "constraint"=>$constraint,
            "sql"=>$sqlRun,
            "params"=>$params[1]
          );
        } else {
          $filterResults[$key] = array(
            "total"=>$trArr[0]['count'],
            "constraint"=>$constraint
          );
        }

      } // end for each

      // query intersect for more than one constraint
      if (count($sqlIntersectArray) > 1) {
        $c = 0;
        foreach ($sqlIntersectArray as $key1 => $sqlI) {
          $c++;

          // first item
          if ($c == 1) {
            $sqlIntersect.="".$sqlI."";
          // last item
          } else {
            $sqlIntersect.="
            INTERSECT
            ".$sqlI."
            ";
          }
        } // end for

        $result1 = pg_query_params($dbconn, $sqlIntersect, $sqlParamsArray) or die('countTrResults: Query failed: incorrect parameters');

        $trArrIntersect = pg_fetch_all($result1);

        if (isset($trArrIntersect[0]['tracereoute_id'])) {
          $totIntersect = count($trArrIntersect);
        } else {
          $totIntersect = 0;
        }

      } else {
        // only one constraint
        foreach ($filterResults as $key2 => $res) {
          if ($res['total']!=0) {
            $totIntersect = $res['total'];
          }
        }
      }

      if ($debugTrSearch) {
        $resultA =  array(
          "results"=>$filterResults,
          "total"=>$totIntersect,
          "sql" => $sqlIntersect,
          "params" => $sqlParamsArray
        );
      } else {
        $resultA =  array(
          "results"=>$filterResults,
          "total"=>$totIntersect,
        );
      }
    }

    return $resultA;
  }
}

?>