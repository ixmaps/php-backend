<?php

/**
 *
 *  This model  is used to gather some data for the initial modal that pops
 *  up when a user first visits the map page
 *
 * @param Called from map_entry_search.php controller
 *
 * @return obj specifying counts and constraints, eg:
 *   results: {
 *     myAsn: {
 *       total: "565"
 *       constraint: {constraint1: "does", constraint2: "originate",
 *        constraint3: "asnum", constraint4: "6939", constraint5: "AND"}
 *     },
 *     myCity: {
 *       total: "0"
 *       constraint: {constraint1: "does", constraint2: "originate",
 *        constraint3: "city", constraint4: "Pendergrass", constraint5: "AND"}
 *     }
 *   }
 *
 *
 * @since Updated Apr 2020
 * @author IXmaps.ca (Antonio, Colin)
 *
 */

class MapEntrySearch
{
  /**
    Quick search for Map Page
  */
  public static function getSearchCounts($data)
  {
    global $dbconn, $debugTrSearch;

    // return empty for non params
    if (count($data) == 0) {
      return array(
        "results" => array(),
        "total" => 0,
      );

    } else {
      $sqlBase = "SELECT DISTINCT traceroute_traits.traceroute_id FROM annotated_traceroutes, traceroute_traits WHERE annotated_traceroutes.traceroute_id = traceroute_traits.traceroute_id";

      $constraintNum = 0;
      $sqlParamsArray = array();
      $constraintWhereParams = array(); // build count independent constraint
      $intersectWhereParams = array(); // build count intersect all constraints
      $sqlIntersectArray = array();

      $filterResults = array();
      // count trs for each of the constraints
      foreach ($data as $constraintKind => $constraint) {

        $constraintWhereParams = Traceroute::buildWhere($constraint);
        $sqlCount = $sqlBase.$constraintWhereParams[0]; // add where conditions

        $constrtaintCount = 0;
        $result = pg_query_params($dbconn, $sqlCount, array($constraintWhereParams[1])) or die('countTrResults: Query failed: incorrect parameters');
        $trCountArr = pg_fetch_all($result);
        pg_free_result($result);
        if ($trCountArr !== false) {
          $constrtaintCount = count($trCountArr);
        }

        // sql for intersect statements
        if ($constrtaintCount != 0) {
          $constraintNum++;
          $intersectWhereParams = Traceroute::buildWhere($constraint, $constraintNum);
          $sqlIntersectArray[] = $sqlBase.$intersectWhereParams[0]; // add sql where
          $sqlParamsArray[] = $constraintWhereParams[1]; // collect params array
        }

        $filterResults[$constraintKind] = array(
          "total" => $constrtaintCount,
          "constraint" => $constraint
        );
      } // end for each

      // query intersect of the constraints to determine 'Number of routes found that meet all above checked conditions' value
      $sqlIntersect = "";
      if (count($sqlIntersectArray) > 1) {
        $c = 0;
        foreach ($sqlIntersectArray as $sqlI) {
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

        $result = pg_query_params($dbconn, $sqlIntersect, $sqlParamsArray) or die('countTrResults: Query failed: incorrect parameters');
        $trArrIntersect = pg_fetch_all($result);
        pg_free_result($result);

        if ($trArrIntersect == false) {
          $trIdCount = 0;
        } else {
          $trIdCount = count($trArrIntersect);
        }

      } else {
        // only one constraint
        $val = array_values($filterResults)[0];
        $trIdCount = $val['total'];
      }
    }

    return array(
             "results" => $filterResults,
             "total" => $trIdCount,
           );
  }
}

?>