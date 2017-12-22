<?php
class GeolocPtr
{

  /** Return the completed GeoJson to the requester
    *
    * @param geoJson to return to requester, $statusObj to append to geoJson
    *
    * @return void
    *
    */
  public static function returnGeoJson($geoJson, $statusObj)
  {
    $geoJson['status'] = GeolocPtr::generateStatusObj($statusObj);
    header('Content-type: application/json');
    echo json_encode($geoJson);
    die;                              // is this bad practice?
  }


  /** Validate the incoming PTR JSON
    *
    * @param $ptrJsonStructure for now, perhaps moving the ptrJsonStruc into this file (or elsewhere)
    *
    * @return statusObj
    *         {
    *           code: 123,
    *           message: 'abc'
    *         }
    */
  public static function validateInputPtr($ptrJsonStructure)
  {
    $code = 201;
    $kind = '';

    // note the implied hierarchy here, 401 will be shown first (this might be too implicit)
    // 2. confirm that all keys are not blank (TODO: do we want to include this error check?)
    foreach ($_POST as $key => $value) {
      if (empty($_POST[$key])) {
        $kind = $key;
        $code = 402;
      }
    }
    // 1. confirm that all required keys are present in the submission
    foreach ($ptrJsonStructure as $key => $value) {
      if (is_null($_POST[$key])) {
        $kind = $key;
        $code = 401;
      }
    }

    $statusObj = array(
      "code" => $code,
      "kind" => $kind
    );

    return $statusObj;
  }



  /** Generate any error / success code objects for requests
    * (this may want to move into a more general class at some point, if we have more API reqs)
    *
    * @param statusObj
    *         {
    *           code: 123,
    *           message: 'abc'
    *         }
    *
    * @return statusJson
    *         {
    *           code: 123,
    *           message: 'abc'
    *         }
    */
  public static function generateStatusObj($statusObj)
  {
    switch ($statusObj["code"]) {
      case 201:
        $message = "Success";
        break;
      case 401:
        $message = "Malformed JSON, missing key - " . $statusObj["kind"];
        break;
      case 402:
        $message = "Malformed JSON, unset value for key - " . $statusObj["kind"];
        break;
    }

    $statusJson = array(
      "code" => $statusObj["code"],
      "message" => $message
    );

    return $statusJson;
  }


}  // end of class
?>