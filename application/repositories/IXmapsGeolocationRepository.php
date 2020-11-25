<?php

class IXmapsGeolocationRepository
{
  private $db;

  public function __construct($db)
  {
    $this->db = $db;
  }

  public function getByIp($ip)
  {
    $sql = "SELECT ip_addr_info.ip_addr, ip_addr_info.hostname, ip_addr_info.asnum, as_users.name, as_users.short_name, ip_addr_info.lat, ip_addr_info.long, ip_addr_info.mm_country, ip_addr_info.mm_region, ip_addr_info.mm_city, ip_addr_info.mm_postal, ip_addr_info.gl_override FROM ip_addr_info LEFT JOIN as_users ON ip_addr_info.asnum = as_users.num WHERE ip_addr_info.ip_addr = $1";
    $params = array($ip);

    try {
      $result = pg_query_params($this->db, $sql, $params) or die('fetchIXgeoloc: Query failed '.pg_last_error());
    } catch (Exception $e) {
      throw new Exception(pg_last_error());
    }

    $ipAddr = pg_fetch_all($result);
    pg_free_result($result);
    return $ipAddr[0];
  }

  // This belongs in the IpInfoRepository?

  // public function save(IXmapsGeolocation $ig)
  // {
  //   $asn = $mm->getASNum();
  //   if ($mm->getASNum() == null || $mm->getASNum() == "") {
  //     $asn = -1;
  //   }

  //   $sql = "INSERT INTO ip_addr_info (ip_addr, asnum, mm_lat, mm_long, hostname, mm_country, mm_region, mm_city, mm_postal, p_status, lat, long, gl_override) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13);";
  //   $ipData = array($ip, $asn, $mm->getLat(), $mm->getLong(), $mm->getHostname(), $mm->getCountryCode(), $mm->getRegion(), $mm->getCity(), $mm->getPostalCode(), "N", $mm->getLat(), $mm->getLong(), NULL);
  // }

  // upsert?

  public function delete(IXmapsGeolocation $ig)
  {

  }
}