<?php
/**
 *
 * Geolocated traceroute class. Used as an object to structure the returned JSON for
 * the geoloc_ptr class (expandable)
 *
 * @author IXmaps.ca (Colin, Antonio)
 * @since 2018 Jan 1
 *
 */
class GeolocTraceroute {
  private $status;
  private $request_id;
  private $ixmaps_id;
  private $hop_count;
  private $completed;
  private $boomerang;
  private $overlay_data;

  function __construct() {
    $this->status = new ResponseCode(100);
  }

  // this must be in this class, needs access to the privates - otherwise we could move this and the PTR ones to the same class. Or maybe move more into here?
  public function determineStatus() {
    // check that GLTR has no null values (cannot check status yet, since this is setting it)
    foreach ($this as $key => $value) {
      if (is_null($value)) {
        return new ResponseCode(502, $key);
      }
    }
    // default response
    return new ResponseCode(201);
  }
  public function setStatus(ResponseCode $status) {
    $this->status = $status;
  }
  public function getStatus() {
    return $this->status;
  }

  public function setRequestId($request_id) {
    $this->request_id = $request_id;
  }
  public function getRequestId() {
    return $this->request_id;
  }

  public function setIXmapsId($ixmaps_id) {
    $this->ixmaps_id = $ixmaps_id;
  }
  public function getIXmapsId() {
    return $this->ixmaps_id;
  }

  public function setHopCount($hop_count) {
    $this->hop_count = $hop_count;
  }
  public function getHopCount() {
    return $this->hop_count;
  }

  public function setCompleted($completed) {
    $this->completed = $completed;
  }
  public function getCompleted() {
    return $this->completed;
  }

  public function setBoomerang($boomerang) {
    $this->boomerang = $boomerang;
  }
  public function getBoomerang() {
    return $this->boomerang;
  }

  public function setOverlayData($overlay_data) {
    $this->overlay_data = $overlay_data;
  }
  public function getOverlayData() {
    return $this->overlay_data;
  }

  /**
    * The archetypal JSON structure for GeoLoc output
    */
  const geolocJsonStructureString = '{
    "status": {
      "code": 201,
      "message": "Success"
    },
    "request_id": "12345",
    "ixmaps_id": 0,
    "hop_count": 9,
    "completed": true,
    "boomerang": true,
    "overlay_data": [
      {
        "hop": "1",
        "ip": "162.219.49.1",
        "lat": 45.399,
        "long": -75.6871,
        "attributes": {
          "asnum": null,
          "asname": null,
          "country": "CA",
          "city": "Ottawa",
          "nsa": false,
          "as_source": "maxmind",
          "geo_source": "ixmaps"
        }
      },
      {
        "hop": "2",
        "ip": "184.105.64.89",
        "lat": "40.72",
        "long": "-74",
        "attributes": {
          "asnum": "6939",
          "asname": "HURRICANE - Hurricane Electric, Inc.",
          "country": "US",
          "city": "New York",
          "nsa": true,
          "as_source": "ixmaps",
          "geo_source": "ixmaps"
        }
      },
      {
        "hop": "3",
        "ip": "198.32.118.113",
        "lat": "40.740697",
        "long": "-74.002089",
        "attributes": {
          "asnum": "9498",
          "asname": "BHARTI Airtel Ltd.",
          "country": "US",
          "city": "New York",
          "nsa": true,
          "as_source": "ixmaps",
          "geo_source": "ixmaps"
        }
      },
      {
        "hop": "4",
        "ip": "64.230.79.90",
        "lat": 43.6319,
        "long": -79.3716,
        "attributes": {
          "asnum": "577",
          "asname": "Bell",
          "country": "CA",
          "city": null,
          "nsa": false,
          "as_source": "maxmind",
          "geo_source": "ixmaps"
        }
      },
      {
        "hop": "5",
        "ip": "64.230.79.151",
        "lat": "45.51",
        "long": "-73.55",
        "attributes": {
          "asnum": "577",
          "asname": "Bell",
          "country": "CA",
          "city": "Montreal",
          "nsa": false,
          "as_source": "ixmaps",
          "geo_source": "ixmaps"
        }
      },
      {
        "hop": "6",
        "ip": "64.230.91.57",
        "lat": 43.6319,
        "long": -79.3716,
        "attributes": {
          "asnum": null,
          "asname": null,
          "country": "CA",
          "city": null,
          "nsa": false,
          "as_source": "maxmind",
          "geo_source": "ixmaps"
        }
      },
      {
        "hop": "7",
        "ip": "10.178.206.152",
        "lat": null,
        "long": null,
        "attributes": {
          "asnum": null,
          "asname": null,
          "country": null,
          "city": null,
          "nsa": false,
          "as_source": null,
          "geo_source": "ixmaps"
        }
      },
      {
        "hop": "8",
        "ip": "69.157.148.1",
        "lat": 45.4024,
        "long": -71.8479,
        "attributes": {
          "asnum": "577",
          "asname": "Bell Canada",
          "country": "CA",
          "city": "Sherbrooke",
          "nsa": false,
          "as_source": "maxmind",
          "geo_source": "ixmaps"
        }
      },
      {
        "hop": "9",
        "ip": "69.157.148.199",
        "lat": 45.4024,
        "long": -71.8479,
        "attributes": {
          "asnum": "577",
          "asname": "Bell Canada",
          "country": "CA",
          "city": "Sherbrooke",
          "nsa": false,
          "as_source": "maxmind",
          "geo_source": "ixmaps"
        }
      }
    ]
  }';

} // end of class


