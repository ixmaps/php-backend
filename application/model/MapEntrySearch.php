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

    // pump up the memory for this query
    ini_set('memory_limit', '256M');

    // return empty for non params
    if (count($data) == 0) {
      return array(
        "results" => array(),
        "total" => 0,
      );
    }

    $sqlBase = "SELECT DISTINCT traceroute_traits.traceroute_id FROM annotated_traceroutes, traceroute_traits WHERE annotated_traceroutes.traceroute_id = traceroute_traits.traceroute_id";

    $filterResults = array();
    // count trs for each of the constraints
    foreach ($data as $constraintKind => $constraint) {
      $constraintCount = 0;

      $constr = $constraint["constraint3"];
      if ($constraint["constraint3"] == "city") {
        $constr = "mm_city";
      }

      $sqlCount = "SELECT DISTINCT traceroute_id FROM annotated_traceroutes WHERE hop = 1 AND {$constr} = '{$constraint["constraint4"]}';";
      
      $result = pg_query($dbconn, $sqlCount) or die('countTrResults: Query failed: incorrect parameters');
      $trCountArr = pg_fetch_all($result);
      pg_free_result($result);
      if ($trCountArr !== false) {
        $constraintCount = count($trCountArr);
      }

      // handle the case of only 1 param being passed
      $intersectCount = $constraintCount;

      $filterResults[$constraintKind] = array(
        "total" => $constraintCount,
        "constraint" => $constraint
      );
    } // end for each

    // for the intersect
    if (count($data) > 1) {
      $asnum = $filterResults["myAsn"]["constraint"]["constraint4"];
      $city = $filterResults["myCity"]["constraint"]["constraint4"];
      $sqlTotalCount = "SELECT DISTINCT traceroute_id FROM annotated_traceroutes WHERE hop = 1 AND asnum = {$asnum} AND mm_city = '{$city}';";

      $result = pg_query($dbconn, $sqlTotalCount) or die('countTrResults: Query failed: incorrect parameters');
      $trTotalCount = pg_fetch_all($result);
      pg_free_result($result);
      if ($trTotalCount !== false) {
        $intersectCount = count($trTotalCount);
      }
    }

    ini_set('memory_limit', '128M');

    return array(
              "results" => $filterResults,
              "total" => $intersectCount,
            );
  }
}

?>